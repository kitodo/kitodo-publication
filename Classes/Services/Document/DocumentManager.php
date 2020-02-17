<?php
namespace EWW\Dpf\Services\Document;

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;

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
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

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

        $document = $this->getDocumentTransferManager()->retrieve($identifier);

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
     * @return Document|false
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function update(Document $document, $workflowTransition = null)
    {
        /** @var \Symfony\Component\Workflow\Workflow $workflow */
        $workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();

        $transferState = null;
        $ingest = false;

        if ($workflowTransition) {
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

                case DocumentWorkflow::TRANSITION_RELEASE_PUBLISH:
                    $ingest = true;
                    break;

                default:
                    break;
            }

            if (!$workflow->can($document, $workflowTransition)) {
                return false;
            }

            if ($document->isWorkingCopy()) {
                if (
                    $this->getDocumentTransferManager()->delete($document, $transferState) &&
                    $this->getDocumentTransferManager()->update($document)
                ) {
                    $workflow->apply($document, $workflowTransition);
                    $this->documentRepository->update($document);
                    $this->documentRepository->remove($document);
                    return $document;
                }
            } else {
                if ($ingest) {
                    if ($this->getDocumentTransferManager()->ingest($document)) {
                        $workflow->apply($document, $workflowTransition);
                        $this->documentRepository->update($document);
                        $this->documentRepository->remove($document);
                        return true;
                    }
                } else {
                    $workflow->apply($document, $workflowTransition);
                    $this->documentRepository->update($document);
                    return $document;
                }
            }
        } else {
            $this->documentRepository->update($document);
            return $document;
        }

        return false;
    }

    /**
     * @param Document $document
     * @param int $tstamp
     * @return bool
     */
    public function hasNewerVersion(Document $document, $tstamp)
    {
        if ($document->isWorkingCopy()) {
            return $this->getDocumentTransferManager()->getLastModDate($document->getObjectIdentifier()) !== $document->getRemoteLastModDate();
        }

        return $tstamp !== $document->getTstamp();
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

}

