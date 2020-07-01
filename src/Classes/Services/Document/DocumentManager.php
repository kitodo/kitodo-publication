<?php
namespace EWW\Dpf\Services\Document;

use EWW\Dpf\Domain\Model\Bookmark;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Controller\AbstractController;

class DocumentManager
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @inject
     */
    protected $bookmarkRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * signalSlotDispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @inject
     */
    protected $signalSlotDispatcher = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;

    /**
     * Returns a document specified by repository object identifier or dataset uid.
     *
     * @param string $identifier
     * @param int $user_uid
     * @return Document|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function read($identifier)
    {
        if (!$identifier) {
            return null;
        }

        $document = $this->documentRepository->findByIdentifier($identifier);

        if ($document instanceof Document) {
            return $document;
        }

        /** @var \EWW\Dpf\Domain\Model\Document $document */
        $document = NULL;

        try {
            $document = $this->getDocumentTransferManager()->retrieve($identifier);

            // index the document
            $this->signalSlotDispatcher->dispatch(
                AbstractController::class, 'indexDocument', [$document]
            );

        } catch (\EWW\Dpf\Exceptions\RetrieveDocumentErrorException $exception) {
            return null;
        }

        if ($document instanceof Document) {
            return $document;
        }

        return null;
    }

    /**
     * Updates a document locally or remotely.
     *
     * @param Document $document
     * @param string $workflowTransition
     * @param array $deletedFiles
     * @param array $newFiles
     * @return string|false
     * @throws \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function update(Document $document, $workflowTransition = null, $deletedFiles = [], $newFiles = [])
    {
        // xml data fields are limited to 64 KB
        if (strlen($document->getXmlData()) >= 64 * 1024 || strlen($document->getSlubInfoData() >= 64 * 1024)) {
            throw new \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException("Maximum document size exceeded.");
        }

        /** @var \Symfony\Component\Workflow\Workflow $workflow */
        $workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();

        if ($workflowTransition) {
            if (!$workflow->can($document, $workflowTransition)) {
                return false;
            }
            $workflow->apply($document, $workflowTransition);
        }

        if ($document->isSuggestion()) {

            // if local suggestion copy
            $updateResult = false;

        } elseif ($document->isTemporaryCopy()) {

            // if temporary working copy
            $updateResult = $this->updateRemotely($document, $workflowTransition, $deletedFiles, $newFiles);

        } elseif (
            $document->isWorkingCopy() &&
            (
                $workflowTransition === DocumentWorkflow::TRANSITION_POSTPONE ||
                $workflowTransition === DocumentWorkflow::TRANSITION_DISCARD ||
                $workflowTransition === DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE
            )
        ) {

            // if local working copy with state change
            $updateResult = $this->updateRemotely($document, $workflowTransition, $deletedFiles, $newFiles);

        } elseif ($document->isWorkingCopy()) {

            // if local working copy with no state change
            $this->updateFiles($document, $deletedFiles, $newFiles);
            $this->documentRepository->update($document);
            $updateResult = $document->getDocumentIdentifier();

        } elseif ($workflowTransition == DocumentWorkflow::TRANSITION_RELEASE_PUBLISH) {
            // Fedora ingest
            if ($ingestedDocument = $this->getDocumentTransferManager()->ingest($document)) {

                // After ingest all related bookmarks need an update of the identifier into an fedora object identifier.
                if ($ingestedDocument instanceof Document) {
                    /** @var Bookmark $bookmark */
                    foreach ($this->bookmarkRepository->findByDocumentIdentifier($ingestedDocument->getUid()) as $bookmark) {
                        $bookmark->setDocumentIdentifier($ingestedDocument->getDocumentIdentifier());
                        $this->bookmarkRepository->update($bookmark);
                    }
                } else {
                    throw \Exception("Logical exception while updating bookmarks.");
                }

                // check embargo
                if(!$this->hasActiveEmbargo($document)){
                    $this->removeDocument($document);
                } else {
                    $document->setState(DocumentWorkflow::constructState(DocumentWorkflow::LOCAL_STATE_IN_PROGRESS, $document->getRemoteState()));
                }
                $updateResult = $document->getDocumentIdentifier();
            } else {
                $updateResult = false;
            }
        } else {

            $this->updateFiles($document, $deletedFiles, $newFiles);
            $this->documentRepository->update($document);
            $updateResult = $document->getDocumentIdentifier();
        }

     //   $this->persistenceManager->persistAll();

        if ($updateResult) {

            if (DocumentWorkflow::TRANSITION_RELEASE_PUBLISH) {
                // delete local document from index
                $this->signalSlotDispatcher->dispatch(
                    AbstractController::class, 'deleteDocumentFromIndex', [$document->getUid()]
                );
            }
            // index the document
            $this->signalSlotDispatcher->dispatch(
                AbstractController::class, 'indexDocument', [$document]
            );
        }

        return $updateResult;
    }

    /**
     * @return DocumentTransferManager
     */
    protected function getDocumentTransferManager()
    {
        /** @var DocumentTransferManager $documentTransferManager */
        $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);

        /** @var  FedoraRepository $remoteRepository */
        $remoteRepository = $this->objectManager->get(FedoraRepository::class);

        $documentTransferManager->setRemoteRepository($remoteRepository);

        return $documentTransferManager;
    }


    /**
     * Adds and delete file model objects attached to the document.
     *
     * @param Document $document
     * @param array $deletedFiles
     * @param array $newFiles
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function updateFiles(Document $document, $deletedFiles, $newFiles)
    {
        // Delete files
        /** @var File $deleteFile */
        foreach ($deletedFiles as $deleteFile) {
            $deleteFile->setStatus(File::STATUS_DELETED);
            $this->fileRepository->update($deleteFile);
        }

        // Add or update files
        /** @var File $newFile */
        foreach ($newFiles as $newFile) {

            if ($newFile->getUID()) {
                $this->fileRepository->update($newFile);
            } else {
                $document->addFile($newFile);
            }

        }
    }

    /**
     * Removes the document from the local database.
     *
     * @param $document
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    protected function removeDocument($document)
    {
        $files = $this->fileRepository->findByDocument($document->getUid());
        foreach ($files as $file) {
            $this->fileRepository->remove($file);
        }
        $this->documentRepository->remove($document);
    }



    /**
     * @param Document $document
     * @param string $workflowTransition
     * @param array $deletedFiles
     * @param array $newFiles
     * @return string
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function updateRemotely($document, $workflowTransition = null, $deletedFiles = [], $newFiles = [])
    {
        $lastModDate = $this->getDocumentTransferManager()->getLastModDate($document->getObjectIdentifier());
        $docLastModDate = $document->getRemoteLastModDate();
        if ($lastModDate !== $docLastModDate && !empty($docLastModDate)) {
            // There is a newer version in the fedora repository.
            return false;
        }

        $this->updateFiles($document, $deletedFiles, $newFiles);
        $this->documentRepository->update($document);

        switch ($workflowTransition) {
            case DocumentWorkflow::TRANSITION_POSTPONE:
                $transferState = DocumentTransferManager::INACTIVATE;
                break;

            case DocumentWorkflow::TRANSITION_DISCARD:
                $transferState = DocumentTransferManager::DELETE;
                break;

            case DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE:
                $transferState = DocumentTransferManager::REVERT;
                break;

            default:
                $transferState = null;
                break;
        }

        if ($transferState) {
            if (!$this->getDocumentTransferManager()->delete($document, $transferState)) {
                return false;
            }
        }

        if ($this->getDocumentTransferManager()->update($document)) {

            if(!$this->hasActiveEmbargo($document)){
                $this->removeDocument($document);
            } else {
                $document->setState(DocumentWorkflow::LOCAL_STATE_IN_PROGRESS . ':' . $document->getRemoteState());
            }
            return $document->getDocumentIdentifier();
        }

        return false;
    }

    /**
     * @param $document
     * @return bool (true: if no embargo is set or embargo is expired, false: embargo is active)
     * @throws \Exception
     */
    protected function hasActiveEmbargo($document)
    {
        $currentDate = new \DateTime('now');
        if($currentDate > $document->getEmbargoDate()){
            // embargo is expired
            return false;
        } else {
            return true;
        }

    }

}

