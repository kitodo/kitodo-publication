<?php
namespace EWW\Dpf\Services\Document;

use Exception;
use EWW\Dpf\Domain\Model\Bookmark;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\FrontendUser;
use EWW\Dpf\Services\Storage\Fedora\FedoraTransaction;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Controller\AbstractController;
use EWW\Dpf\Services\Email\Notifier;

class DocumentManager
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fileRepository = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $bookmarkRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $persistenceManager;

    /**
     * signalSlotDispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $signalSlotDispatcher = null;

    /**
     * notifier
     *
     * @var \EWW\Dpf\Services\Email\Notifier
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $notifier = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $security = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository = null;

    /**
     * elasticSearch
     *
     * @var \EWW\Dpf\Services\ElasticSearch\ElasticSearch
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $elasticSearch = null;

    /**
     * queryBuilder
     *
     * @var \EWW\Dpf\Services\ElasticSearch\QueryBuilder
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $queryBuilder = null;

    /**
     * documentStorage
     *
     * @var \EWW\Dpf\Services\Storage\DocumentStorage
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentStorage = null;

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
                $document = $this->documentStorage->retrieve($localizedIdentifiers['objectIdentifier']);

                // index the document
                $this->signalSlotDispatcher->dispatch(
                    AbstractController::class, 'indexDocument', [$document]
                );

                return $document;
            } catch (\Exception $exception) {
                throw $exception;
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
     * @param bool $addedFisIdOnly
     * @return string|false
     * @throws \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function update(
        Document $document, $workflowTransition = null, $addedFisIdOnly = false
    )
    {
        // xml data fields are limited to 64 KB
        // FIXME: Code duplication should be removed and it should be encapsulated or made configurable.
        if (strlen($document->getXmlData()) >= Document::XML_DATA_SIZE_LIMIT) {
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
            $updateResult = $this->updateRemotely($document, $workflowTransition);

        } elseif (
            $document->isWorkingCopy() &&
            (
                $workflowTransition === DocumentWorkflow::TRANSITION_POSTPONE ||
                $workflowTransition === DocumentWorkflow::TRANSITION_DISCARD ||
                $workflowTransition === DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE
            )
        ) {
            // if local working copy with state change
            $updateResult = $this->updateRemotely($document, $workflowTransition);

        } elseif ($document->isWorkingCopy()) {

            // if local working copy with no state change
            $this->documentRepository->update($document);
            $updateResult = $document->getDocumentIdentifier();

        } elseif ($workflowTransition == DocumentWorkflow::TRANSITION_RELEASE_PUBLISH) {

            // Fedora ingest
            if ($ingestedDocument = $this->documentStorage->ingest($document)) {
                // After ingest all related bookmarks need an update of the identifier into an fedora object identifier.
                if ($ingestedDocument instanceof Document) {
                    /** @var Bookmark $bookmark */
                    foreach ($this->bookmarkRepository->findByDocumentIdentifier($ingestedDocument->getUid()) as $bookmark) {
                        $bookmark->setDocumentIdentifier($ingestedDocument->getDocumentIdentifier());
                        $this->bookmarkRepository->update($bookmark);
                    }
                    $this->persistenceManager->persistAll();
                } else {
                    throw new \Exception("Logical exception while updating bookmarks.");
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
            $this->documentRepository->update($document);
            $updateResult = $document->getDocumentIdentifier();
        }

        if ($updateResult) {

            if ($workflowTransition === DocumentWorkflow::TRANSITION_RELEASE_PUBLISH) {
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

          if ($document->getLocalState() !== DocumentWorkflow::LOCAL_STATE_IN_PROGRESS) {
                /** @var Notifier $notifier */
                $notifier = $this->objectManager->get(Notifier::class);
                $notifier->sendChangedDocumentNotification($document, $addedFisIdOnly);
            }
        }

        return $updateResult;
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
     * @return string|bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function updateRemotely(Document $document, $workflowTransition = null)
    {
        switch ($workflowTransition) {
            case DocumentWorkflow::TRANSITION_POSTPONE:
                $state = DocumentWorkflow::REMOTE_STATE_INACTIVE;
                break;

            case DocumentWorkflow::TRANSITION_DISCARD:
                $state = DocumentWorkflow::REMOTE_STATE_DELETED;
                break;

            case DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE:
                $state = DocumentWorkflow::REMOTE_STATE_ACTIVE;
                break;

            default:
                $state = null;
                break;
        }

        try {
            $this->documentStorage->update($document, $state);

            if(!$this->hasActiveEmbargo($document)){
                $this->removeDocument($document);
            } else {
                $document->setState(DocumentWorkflow::LOCAL_STATE_IN_PROGRESS . ':' . $document->getRemoteState());
                $this->documentRepository->update($document);
            }

            return $document->getDocumentIdentifier();

        } catch (Exception $exception) {

            throw $exception;

            // TODO: Log?
            return false;
        }

    }

    public function addSuggestion($editDocument, $restore = false, $comment = '') {
        // add new document
        /** @var Document $suggestionDocument */
        $suggestionDocument = $this->objectManager->get(Document::class);
        $this->documentRepository->add($suggestionDocument);
        $this->persistenceManager->persistAll();

        // copy properties from origin
        $suggestionDocument = $suggestionDocument->copy($editDocument);
        $suggestionDocument->setCreator($editDocument->getCreator());

        if ($suggestionDocument->isTemporary()) {
            $suggestionDocument->setTemporary(false);
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

        try {
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

        if ($document->getCreator()) {
            $users[$this->getCreatorUser($document)->getUid()] = $this->getCreatorUser($document);
        }

        foreach ($this->getAssignedUsers($document) as $user) {
            $users[$user->getUid()] = $user;
        }

        foreach ($this->getDocumentBookmarkUsers($document) as $user) {
            $users[$user->getUid()] = $user;
        }

        $recipients = [];

        /** @var FrontendUser $recipient */
        foreach ($users as $recipient) {
            // Fixme:  Refactoring is needed. The whole code inside this foreach is way too confusing.
            // Give expressions at least a name. Minize the deeply nested structure.
            // Maybe rethinking of the whole process of notifying could help, e.g. the recipients
            // could decide if a notification is wanted.
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

