<?php
namespace EWW\Dpf\Services\Document;

use EWW\Dpf\Domain\Model\Bookmark;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Domain\Model\FrontendUser;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\ElasticSearch\ElasticSearch;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Controller\AbstractController;
use EWW\Dpf\Services\Email\Notifier;
use Symfony\Component\Workflow\Workflow;
use Httpful\Request;

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
     * notifier
     *
     * @var \EWW\Dpf\Services\Email\Notifier
     * @inject
     */
    protected $notifier = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository = null;

    /**
     * elasticSearch
     *
     * @var \EWW\Dpf\Services\ElasticSearch\ElasticSearch
     * @inject
     */
    protected $elasticSearch = null;

    /**
     * queryBuilder
     *
     * @var \EWW\Dpf\Services\ElasticSearch\QueryBuilder
     * @inject
     */
    protected $queryBuilder = null;

    /**
     * Returns the localized document identifiers (uid/objectIdentifier).
     *
     * @param $identifier
     * @return array
     */
    public function resolveIdentifier($identifier) {

        $localizedIdentifiers = [];

        $document = $this->documentRepository->findByIdentifier($identifier);

        if ($document instanceof Document) {

            if ($document->getObjectIdentifier()) {
                $localizedIdentifiers['objectIdentifier'] = $document->getObjectIdentifier();
            }

            if ($document->getUid()) {
                $localizedIdentifiers['uid'] = $document->getUid();
            }
        } else {

            $query = $this->queryBuilder->buildQuery(
                1, [], 0,
                [], [], [], null, null,
                'identifier:"'.$identifier.'"'
            );

            try {
                $results =  $this->elasticSearch->search($query, 'object');
                if (is_array($results) && $results['hits']['total']['value'] > 0) {
                    $localizedIdentifiers['objectIdentifier'] = $results['hits']['hits'][0]['_id'];
                }
            } catch (\Exception $e) {
                return [];
            }

        }

        return $localizedIdentifiers;
    }


    /**
     * Returns a document specified by repository object identifier, a typo3 uid or a process number.
     *
     * @param string $identifier
     * @return Document|null
     */
    public function read($identifier)
    {
        if (!$identifier) {
            return null;
        }

        $localizedIdentifiers = $this->resolveIdentifier($identifier);

        if (array_key_exists('uid', $localizedIdentifiers)) {
            return $this->documentRepository->findByUid($localizedIdentifiers['uid']);
        }

        if (array_key_exists('objectIdentifier', $localizedIdentifiers)) {
            try {
                /** @var \EWW\Dpf\Domain\Model\Document $document */
                $document = $this->getDocumentTransferManager()->retrieve($localizedIdentifiers['objectIdentifier']);

                // index the document
                $this->signalSlotDispatcher->dispatch(
                    AbstractController::class, 'indexDocument', [$document]
                );

                return $document;
            } catch (\Exception $exception) {
                return null;
            }
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
                    $this->persistenceManager->persistAll();
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

            // Notify assigned users
            $recipients = $this->getUpdateNotificationRecipients($document);
            $this->notifier->sendMyPublicationUpdateNotification($document, $recipients);

            $recipients = $this->getNewPublicationNotificationRecipients($document);
            $this->notifier->sendMyPublicationNewNotification($document, $recipients);

            /** @var Notifier $notifier */
            $notifier = $this->objectManager->get(Notifier::class);
            $notifier->sendChangedDocumentNotification($document);
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

    public function addSuggestion($editDocument, $restore = false, $comment = '') {

        // add new document
        /** @var Document $suggestionDocument */
        $suggestionDocument = $this->objectManager->get(Document::class);
        $this->documentRepository->add($suggestionDocument);
        $this->persistenceManager->persistAll();

        // copy properties from origin
        $suggestionDocument = $suggestionDocument->copy($editDocument);

        if ($suggestionDocument->isTemporary()) {
            $suggestionDocument->setTemporary(false);
        }

        if (empty($suggestionDocument->getFileData())) {
            // no files are linked to the document
            $hasFilesFlag = false;
        }

        if ($editDocument->getObjectIdentifier()) {
            $suggestionDocument->setLinkedUid($editDocument->getObjectIdentifier());
        } else {
            $suggestionDocument->setLinkedUid($editDocument->getUid());
        }

        $suggestionDocument->setSuggestion(true);
        if ($comment) {
            $suggestionDocument->setComment($comment);
        }

        if ($restore) {
            $suggestionDocument->setTransferStatus("RESTORE");
        }

//        if (!$hasFilesFlag) {
//            // Add or update files
//            foreach ($documentForm->getNewFiles() as $newFile) {
//                if ($newFile->getUID()) {
//                    $this->fileRepository->update($newFile);
//                } else {
//                    $newFile->setDocument($suggestionDocument);
//                    $this->fileRepository->add($newFile);
//                }
//
//                $suggestionDocument->addFile($newFile);
//            }
//        } else {
//            // remove files for suggest object
//            $suggestionDocument->setFile($this->objectManager->get(ObjectStorage::class));
//        }

        try {
//            $suggestionDocument->setCreator($this->security->getUser()->getUid());
            $this->documentRepository->add($suggestionDocument);
        } catch (\Throwable $t) {
            return null;
        }

        return $suggestionDocument;

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


    /**
     * @param Document $document
     * @return FrontendUser
     */
    public function getCreatorUser(Document $document) {
        return $this->frontendUserRepository->findByUid($document->getCreator());
    }

    /**
     * @param Document $document
     * @return array
     */
    public function getAssignedUsers(Document $document)
    {
        $assignedUsers = [];

        foreach ($document->getAssignedFobIdentifiers() as $fobId) {
            $feUsers = $this->frontendUserRepository->findByFisPersId($fobId);
            foreach ($feUsers as $feUser) {

                $assignedUsers[$feUser->getUid()] = $feUser;
            }
        }

        return $assignedUsers;
    }

    /**
     * @param Document $document
     * @return array
     */
    public function getNewlyAssignedUsers(Document $document)
    {
        $assignedUsers = [];

        foreach ($document->getNewlyAssignedFobIdentifiers() as $fobId) {
            $feUsers = $this->frontendUserRepository->findByFisPersId($fobId);
            foreach ($feUsers as $feUser) {
                $assignedUsers[$feUser->getUid()] = $feUser;
            }
        }

        return $assignedUsers;
    }

    /**
     * @param Document $document
     * @return array
     */
    public function getDocumentBookmarkUsers(Document $document) {

        $users = [];

        /** @var Bookmark $bookmark */
        $bookmarks = $this->bookmarkRepository->findByDocumentIdentifier($document->getDocumentIdentifier());
        foreach ($bookmarks as $bookmark) {
            $feUser = $this->frontendUserRepository->findByUid($bookmark->getFeUserUid());
            $users[$feUser->getUid()] = $feUser;
        }

        return $users;
    }

    /**
     * @param Document $document
     * @return array
     */
    public function getUpdateNotificationRecipients(Document $document)
    {
        $users = [];
        $users[$this->getCreatorUser($document)->getUid()] = $this->getCreatorUser($document);

        foreach ($this->getAssignedUsers($document) as $user) {
            $users[$user->getUid()] = $user;
        }

        foreach ($this->getDocumentBookmarkUsers($document) as $user) {
            $users[$user->getUid()] = $user;
        }

        $recipients = [];

        /** @var FrontendUser $recipient */
        foreach ($users as $recipient) {
            if (
                $recipient->getUid() !== $this->security->getUser()->getUid() &&
                $document->getState() !== DocumentWorkflow::STATE_NEW_NONE &&
                !(
                    in_array(
                        $recipient->getFisPersId(), $document->getNewlyAssignedFobIdentifiers()
                    ) ||
                    $document->isStateChange() &&
                    $document->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
                )
            ) {

                if ($recipient->isNotifyOnChanges()) {

                    if (
                        $recipient->isNotifyPersonalLink() ||
                        $recipient->isNotifyStatusChange() ||
                        $recipient->isNotifyFulltextPublished()
                    ) {
                        if (
                            $recipient->isNotifyPersonalLink() &&
                            in_array(
                                $recipient->getFisPersId(), $document->getAssignedFobIdentifiers()
                            ) &&
                            !(
                                $recipient->isNotifyNewPublicationMyPublication() &&
                                (
                                    in_array(
                                        $recipient->getFisPersId(), $document->getNewlyAssignedFobIdentifiers()
                                    ) ||
                                    $document->isStateChange() &&
                                    $document->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
                                )
                            )
                        ) {
                            $recipients[$recipient->getUid()] = $recipient;
                        }

                        if ($recipient->isNotifyStatusChange() && $document->isStateChange()) {
                            $recipients[$recipient->getUid()] = $recipient;
                        }

                        if ($recipient->isNotifyFulltextPublished()) {

                            $embargoDate = $document->getEmbargoDate();
                            $currentDate = new \DateTime('now');

                            $fulltextPublished = false;
                            foreach ($document->getFile() as $file) {
                                if ($file->getStatus() != 'added') {
                                    $fulltextPublished = false;
                                    break;
                                } else {
                                    $fulltextPublished = true;
                                }
                            }

                            if (
                                $document->getState() === DocumentWorkflow::STATE_NONE_ACTIVE &&
                                $fulltextPublished &&
                                (
                                   empty($embargoDate) ||
                                   $embargoDate < $currentDate
                                )
                            ) {
                                $recipients[$recipient->getUid()] = $recipient;
                            }
                        }

                    } else {
                       $recipients[$recipient->getUid()] = $recipient;
                    }
                }
            }
        }
        return $recipients;
    }

    /**
     * @param Document $document
     * @return array
     */
    public function getNewPublicationNotificationRecipients(Document $document)
    {
        $users = [];

        /** @var FrontendUser $user */
        foreach ($this->getAssignedUsers($document) as $user) {
            if (
                $user->getUid() !== $this->security->getUser()->getUid() &&
                $document->getState() !== DocumentWorkflow::STATE_NEW_NONE &&
                $user->getUid() !== $document->getCreator()
            ) {
                if (
                    $user->isNotifyNewPublicationMyPublication() &&
                    (
                        in_array(
                            $user->getFisPersId(), $document->getNewlyAssignedFobIdentifiers()
                        ) ||
                        $document->isStateChange() &&
                        $document->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
                    )
                ) {
                    $users[$user->getUid()] = $user;
                }
            }
        }

        return $users;
    }
}

