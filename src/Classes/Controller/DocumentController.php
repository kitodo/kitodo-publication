<?php
namespace EWW\Dpf\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Domain\Model\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


/**
 * DocumentController
 */
class DocumentController extends AbstractController
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * inputOptionListRepository
     *
     * @var \EWW\Dpf\Domain\Repository\InputOptionListRepository
     * @inject
     */
    protected $inputOptionListRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @inject
     */
    protected $editingLockService = null;

    /**
     * documentValidator
     *
     * @var \EWW\Dpf\Helper\DocumentValidator
     * @inject
     */
    protected $documentValidator;

    /**
     * workflow
     *
     * @var \EWW\Dpf\Domain\Workflow\DocumentWorkflow
     */
    protected $workflow;

    /**
     * documentTransferManager
     *
     * @var \EWW\Dpf\Services\Transfer\DocumentTransferManager $documentTransferManager
     */
    protected $documentTransferManager;

    /**
     * fedoraRepository
     *
     * @var \EWW\Dpf\Services\Transfer\FedoraRepository $fedoraRepository
     */
    protected $fedoraRepository;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository = null;


    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @inject
     */
    protected $documentManager = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @inject
     */
    protected $bookmarkRepository = null;

    /**
     * DocumentController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->documentTransferManager = $objectManager->get(DocumentTransferManager::class);
        $this->fedoraRepository = $objectManager->get(FedoraRepository::class);
        $this->documentTransferManager->setRemoteRepository($this->fedoraRepository);
    }

    /**
     * action logout of the backoffice
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $uri = $cObj->typolink_URL([
            'parameter' => $this->settings['loginPage'],
            //'linkAccessRestrictedPages' => 1,
            'forceAbsoluteUrl' => 1,
            'additionalParams' => GeneralUtility::implodeArrayForUrl(NULL, ['logintype'=>'logout'])
        ]);

        $this->redirectToUri($uri);
    }

    public function listSuggestionsAction() {
        $this->session->setStoredAction($this->getCurrentAction(), $this->getCurrentController());

        $documents = NULL;
        $isWorkspace = $this->security->getUser()->getUserRole() === Security::ROLE_LIBRARIAN;

        if (
            $this->security->getUser()->getUserRole() == Security::ROLE_LIBRARIAN
        ) {
                $documents = $this->documentRepository->findAllDocumentSuggestions(
                    $this->security->getUser()->getUserRole(),
                    $this->security->getUser()->getUid()
                );
        }

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        $this->view->assign('currentUser', $this->security->getUser());
        $this->view->assign('isWorkspace', $isWorkspace);
        $this->view->assign('documents', $documents);
    }

    /**
     * @param Document $document
     * @param bool $acceptAll
     */
    public function acceptSuggestionAction(\EWW\Dpf\Domain\Model\Document $document, bool $acceptAll = true) {

        $args = $this->request->getArguments();

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        // Existing working copy?
        /** @var \EWW\Dpf\Domain\Model\Document $originDocument */
        $linkedUid = $document->getLinkedUid();
        $originDocument = $this->documentRepository->findWorkingCopy($linkedUid);

        if ($originDocument) {
            $linkedDocumentForm = $documentMapper->getDocumentForm($originDocument);
        } else {
            // get remote document
            $originDocument = $this->documentTransferManager->retrieve($document->getLinkedUid(), $this->security->getUser()->getUid());
            $linkedDocumentForm = $documentMapper->getDocumentForm($originDocument);
        }

        if ($acceptAll) {
            // all changes are confirmed
            // copy suggest to origin document
            $originDocument->copy($document, true);

            if ($originDocument->getTransferStatus() == 'RESTORE') {
                if ($originDocument->getObjectIdentifier()) {
                    $originDocument->setState(DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);
                } else {
                    $originDocument->setState(DocumentWorkflow::STATE_IN_PROGRESS_NONE);
                }
            }

            // copy files from suggest document
            foreach ($document->getFile() as $key => $file) {
                $newFile = $this->objectManager->get(File::class);
                $newFile->copy($file);
                $newFile->setDocument($originDocument);
                $this->fileRepository->add($newFile);
                $originDocument->addFile($newFile);

            }

            $this->documentRepository->update($originDocument);
            $this->documentRepository->remove($document);

            // redirect to document
            $this->redirect('showDetails', 'Document', null, ['document' => $originDocument]);
        }

        $this->redirectToDocumentList();
    }


    public function showSuggestionDetailsAction(\EWW\Dpf\Domain\Model\Document $document) {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::SHOW_DETAILS, $document);

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        $linkedUid = $document->getLinkedUid();
        $linkedDocument = $this->documentRepository->findWorkingCopy($linkedUid);

        if ($linkedDocument) {
            // Existing working copy
            $linkedDocumentForm = $documentMapper->getDocumentForm($linkedDocument);
        } else {
            // No existing working copy, get remote document from fedora
            $linkedDocument = $this->documentTransferManager->retrieve($document->getLinkedUid(), $this->security->getUser()->getUid());
            $linkedDocumentForm = $documentMapper->getDocumentForm($linkedDocument);
        }

        $newDocumentForm = $documentMapper->getDocumentForm($document);
        $diff = $this->documentFormDiff($linkedDocumentForm, $newDocumentForm);

        //$usernameString = $this->security->getUser()->getUsername();
        $user = $this->frontendUserRepository->findOneByUid($document->getCreator());

        if ($user) {
            $usernameString = $user->getUsername();
        }

        $this->view->assign('documentCreator', $usernameString);
        $this->view->assign('diff', $diff);
        $this->view->assign('document', $document);

    }

    public function documentFormDiff($docForm1, $docForm2) {
        $returnArray = ['changed' => ['new' => [], 'old' => []], 'deleted' => [], 'added' => []];

        // pages
        foreach ($docForm1->getItems() as $keyPage => $valuePage) {
            foreach ($valuePage as $keyRepeatPage => $valueRepeatPage) {

                // groups
                foreach ($valueRepeatPage->getItems() as $keyGroup => $valueGroup) {

                    $checkFieldsForAdding = false;
                    $valueGroupCounter = count($valueGroup);

                    if ($valueGroupCounter < count($docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup])) {
                        $checkFieldsForAdding = true;
                    }

                    foreach ($valueGroup as $keyRepeatGroup => $valueRepeatGroup) {

                        // fields
                        foreach ($valueRepeatGroup->getItems() as $keyField => $valueField) {
                            foreach ($valueField as $keyRepeatField => $valueRepeatField) {

                                $fieldCounter = count($docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup]);
                                $valueFieldCounter = count($valueField);

                                // check if group or field is not existing
                                $notExisting = false;
                                try {
                                    $flag = 'page';
                                    $value2 = $docForm2->getItems()[$keyPage];
                                    $flag = 'group';
                                    $value2 = $value2[$keyRepeatPage];
                                    $value2 = $value2->getItems()[$keyGroup];
                                    $value2 = $value2[$keyRepeatGroup]->getItems()[$keyField];
                                    $flag = 'field';
                                } catch (\Throwable $t) {
                                    $notExisting = true;
                                }

                                $item = NULL;
                                if ($flag == 'group') {
                                    $itemExisting = $valueRepeatGroup;
                                    $itemNew = $docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup];
                                } else if ($flag == 'field') {
                                    $itemExisting = $valueRepeatField;
                                    $itemNew = $docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup][$keyRepeatGroup]->getItems()[$keyField][$keyRepeatField];
                                }

                                if ($notExisting || ($valueRepeatField->getValue() != $value2[$keyRepeatField]->getValue() && empty($value2[$keyRepeatField]->getValue()))) {
                                    // deleted
                                    $returnArray['deleted'][] = $itemExisting;

                                } else if ($this->removeControlCharacterFromString($valueRepeatField->getValue()) != $this->removeControlCharacterFromString($value2[$keyRepeatField]->getValue())
                                    && !empty($value2[$keyRepeatField]->getValue())) {

                                    // changed
                                    $returnArray['changed']['old'][] = $itemExisting;
                                    $returnArray['changed']['new'][] = $itemNew;
                                }

                                if ($flag == 'group') {
                                    break 2;
                                }
                            }

                            // check if new document form has more field items as the existing form
                            if ($valueFieldCounter < $fieldCounter && !$checkFieldsForAdding) {
                                // field added
                                for ($i = count($valueField); $i < $fieldCounter;$i++) {
                                    $returnArray['added'][] = $docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup][$keyRepeatGroup]->getItems()[$keyField][$i];

                                }
                            }
                        }
                    }

                    // check if new document form has more group items as the existing form
                    if ($valueGroupCounter < count($docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup])) {
                        // group added
                        $counter = count($docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup]);
                        for ($i = $valueGroupCounter; $i < $counter;$i++) {
                            $returnArray['added'][] = $docForm2->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup][$i];
                        }
                    }
                }
            }

        }

        return $returnArray;

    }

    public function removeControlCharacterFromString($string) {
        return preg_replace('/\p{C}+/u', "", $string);
    }

    /**
     * action discard
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @param string $reason
     * @return void
     */
    public function discardAction(Document $document, $tstamp, $reason = NULL)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::DISCARD, $document)) {
            if (
                $this->editingLockService->isLocked(
                    $document->getDocumentIdentifier(),
                    $this->security->getUser()->getUid()
                )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->updateDocument($document, DocumentWorkflow::TRANSITION_DISCARD, $reason);
    }

    /**
     * action postpone
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @param string $reason
     * @return void
     */
    public function postponeAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp, $reason = NULL)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::POSTPONE, $document)) {
            if (
                $this->editingLockService->isLocked(
                    $document->getDocumentIdentifier(),
                    $this->security->getUser()->getUid()
                )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->updateDocument($document, DocumentWorkflow::TRANSITION_POSTPONE, $reason);

    }


    /**
     * action change document type
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param int $documentTypeUid
     * @return void
     */
    public function changeDocumentTypeAction(\EWW\Dpf\Domain\Model\Document $document, $documentTypeUid = 0)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::UPDATE, $document)) {
            if (
            $this->editingLockService->isLocked(
                $document->getDocumentIdentifier(),
                $this->security->getUser()->getUid()
            )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }


        $documentType = $this->documentTypeRepository->findByUid($documentTypeUid);

        if ($documentType instanceof DocumentType) {
            $document->setDocumentType($documentType);
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $slub->setDocumentType($documentType->getName());
            $document->setSlubInfoData($slub->getSlubXml());
            $this->updateDocument($document, '', null);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        } else {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }
    }


    /**
     * action deleteLocallyAction
     *
     * @param Document $document
     * @param integer $tstamp
     * @return void
     */
    public function deleteLocallyAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp)
    {
        if ($document->getObjectIdentifier()) {
            $voterAttribute = DocumentVoter::DELETE_WORKING_COPY;
        } else {
            $voterAttribute = DocumentVoter::DELETE_LOCALLY;
        }

        if (!$this->authorizationChecker->isGranted($voterAttribute, $document)) {
            if (
                $this->editingLockService->isLocked(
                    $document->getDocumentIdentifier(),
                    $this->security->getUser()->getUid()
                )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        if ($tstamp === $document->getTstamp()) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.success';
            $this->flashMessage($document, $key, AbstractMessage::OK);
            $this->documentRepository->remove($document);

            if ($document->isWorkingCopy() || $document->isTemporaryCopy()) {

                if ($document->isWorkingCopy()) {
                    if ($this->bookmarkRepository->removeBookmark($document, $this->security->getUser()->getUid())) {
                        $this->addFlashMessage(
                            LocalizationUtility::translate("manager.workspace.bookmarkRemoved.singular", "dpf"), '',
                            AbstractMessage::INFO
                        );
                    }
                }

                $this->persistenceManager->persistAll();
                $document = $this->documentManager->read($document->getDocumentIdentifier());

                // index the document
                $this->signalSlotDispatcher->dispatch(
                    \EWW\Dpf\Controller\AbstractController::class,
                    'indexDocument', [$document]
                );

                $this->documentRepository->remove($document);
            } else {
                $this->bookmarkRepository->removeBookmark($document, $this->security->getUser()->getUid());
                // delete document from index
                $this->signalSlotDispatcher->dispatch(
                    \EWW\Dpf\Controller\AbstractController::class,
                    'deleteDocumentFromIndex', [$document->getDocumentIdentifier()]
                );
            }

            $suggestions = $this->documentRepository->findByLinkedUid($document->getDocumentIdentifier());
            foreach ($suggestions as $suggestion) {
                $this->documentRepository->remove($suggestion);
            }

            $this->redirectToDocumentList();
        } else {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.failureNewVersion';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        }
    }


    /**
     * @param Document $document
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function duplicateAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::DUPLICATE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $newDocument = $this->objectManager->get(Document::class);

        $newDocument->setState(DocumentWorkflow::STATE_NEW_NONE);

        $copyTitle = LocalizationUtility::translate("manager.workspace.title.copy", "dpf").$document->getTitle();

        $newDocument->setTitle($copyTitle);

        $newDocument->setAuthors($document->getAuthors());

        $newDocument->setCreator($this->security->getUser()->getUid());

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
        $mods->clearAllUrn();
        $mods->setDateIssued('');
        $mods->setTitle($copyTitle);

        $newDocument->setXmlData($mods->getModsXml());

        $newDocument->setDocumentType($document->getDocumentType());

        $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
        $processNumber = $processNumberGenerator->getProcessNumber();
        $newDocument->setProcessNumber($processNumber);

        $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
        $slub->setProcessNumber($processNumber);
        $newDocument->setSlubInfoData($slub->getSlubXml());


        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /** @var $documentForm \EWW\Dpf\Domain\Model\DocumentForm */
        $newDocumentForm = $documentMapper->getDocumentForm($newDocument);

        $this->forward(
            'new',
            'DocumentFormBackoffice',
            NULL,
            ['newDocumentForm' => $newDocumentForm, 'returnDocumentId' => $document->getUid()]
        );

    }


    /**
     * releasePublishAction
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @return void
     */
    public function releasePublishAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::RELEASE_PUBLISH, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->updateDocument($document, DocumentWorkflow::TRANSITION_RELEASE_PUBLISH, null);

    }


    /**
     * releaseActivateAction
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @return void
     */
    public function releaseActivateAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::RELEASE_ACTIVATE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->updateDocument($document, DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE, null);

    }

    /**
     * action register
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function registerAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::REGISTER, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        }

        if (!$this->documentValidator->validate($document, false)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.missingValues';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        }

        $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_REGISTER);
        $this->documentRepository->update($document);


        if ($this->security->getUser()->getUserRole() === Security::ROLE_LIBRARIAN) {
            $this->bookmarkRepository->addBookmark($document, $this->security->getUser()->getUid());
        }

        // admin register notification
        $notifier = $this->objectManager->get(Notifier::class);
        $notifier->sendRegisterNotification($document);

        // index the document
        $this->signalSlotDispatcher->dispatch(\EWW\Dpf\Controller\AbstractController::class, 'indexDocument', [$document]);

        // document updated notification
        $recipients = $this->documentManager->getUpdateNotificationRecipients($document);
        $notifier->sendMyPublicationUpdateNotification($document, $recipients);

        $recipients = $this->documentManager->getNewPublicationNotificationRecipients($document);
        $notifier->sendMyPublicationNewNotification($document, $recipients);

        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.success';
        $this->flashMessage($document, $key, AbstractMessage::OK);
        $this->redirect('showDetails', 'Document', null, ['document' => $document]);
    }

    /**
     * action showDetails
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function showDetailsAction(Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::SHOW_DETAILS, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_showDetails.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirectToDocumentList();
        }

        $this->editingLockService->lock(
            ($document->getObjectIdentifier()? $document->getObjectIdentifier() : $document->getUid()),
            $this->security->getUser()->getUid()
        );

        $postponeOptions = $this->inputOptionListRepository->findOneByName($this->settings['postponeOptionListName']);
        if ($postponeOptions) {
            $this->view->assign('postponeOptions', $postponeOptions->getInputOptions());
        }

        $discardOptions = $this->inputOptionListRepository->findOneByName($this->settings['discardOptionListName']);
        if ($discardOptions) {
            $this->view->assign('discardOptions', $discardOptions->getInputOptions());
        }

        $mapper = $this->objectManager->get(DocumentMapper::class);
        $documentForm = $mapper->getDocumentForm($document, false);

        $documentTypes = [0 => ''];
        foreach ($this->documentTypeRepository->getDocumentTypesAlphabetically() as $documentType) {
            $documentTypes[$documentType->getUid()] = $documentType->getDisplayName();
        }

        $this->view->assign('documentTypes', $documentTypes);

        $this->view->assign('documentForm', $documentForm);

        $this->view->assign('document', $document);
    }


    public function cancelListTaskAction()
    {
        $this->redirectToDocumentList();
    }
    
    /**
     * action suggest restore
     *
     * @param Document $document
     * @return void
     */
    public function suggestRestoreAction(\EWW\Dpf\Domain\Model\Document $document) {

        if (!$this->authorizationChecker->isGranted(DocumentVoter::SUGGEST_RESTORE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_suggestRestore.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->view->assign('document', $document);
    }

    /**
     * @param Document $document
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function suggestModificationAction(\EWW\Dpf\Domain\Model\Document $document) {

        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::SUGGEST_MODIFICATION, $document);

        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $documentForm = $documentMapper->getDocumentForm($document);

        $this->view->assign('suggestMod', true);
        $this->forward('edit','DocumentFormBackoffice',NULL, ['documentForm' => $documentForm, 'suggestMod' => true]);
    }


    /**
     * initializeAction
     */
    public function initializeAction()
    {
        $this->authorizationChecker->denyAccessUnlessLoggedIn();

        parent::initializeAction();

        $this->workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();

        if ($this->request->hasArgument('document')) {
            $document = $this->request->getArgument('document');

            if (is_array($document) && key_exists("__identity", $document)) {
                $document = $document["__identity"];
            }

            $document = $this->documentManager->read($document, $this->security->getUser()->getUID());

            if (!$document) {
                $this->redirectToDocumentList();
            }

            $this->request->setArgument('document', $document);
        }
    }

    /**
     * Redirect to the current document list.
     *
     * @param null $message
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function redirectToDocumentList($message = null)
    {
        list($action, $controller, $redirectUri) = $this->session->getStoredAction();

        if ($redirectUri) {
            $this->redirectToUri($redirectUri);
        } else {
            $this->redirect($action, $controller);
        }
    }

    /**
     * Gets the storage PID of the current client
     *
     * @return mixed
     */
    protected function getStoragePID()
    {
        return $this->settings['persistence']['classes']['EWW\Dpf\Domain\Model\Document']['newRecordStoragePid'];
    }

    /**
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $key
     * @param string $severity
     * @param string $defaultMessage
     */
    protected function flashMessage(\EWW\Dpf\Domain\Model\Document $document, $key, $severity, $defaultMessage = "")
    {
        // Show success or failure of the action in a flash message
        if ($document) {
            $args[] = $document->getTitle();
            $args[] = $document->getObjectIdentifier();
        }

        $message = LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? $defaultMessage : $message;

        $this->addFlashMessage(
            $message,
            '',
            $severity,
            true
        );
    }

    /**
     * Updates the document in combination with a state transition.
     *
     * @param Document $document
     * @param $workflowTransition
     * @param string $reason
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function updateDocument(\EWW\Dpf\Domain\Model\Document $document, $workflowTransition, $reason)
    {
        switch ($workflowTransition) {
            case DocumentWorkflow::TRANSITION_DISCARD:
                $messageKeyPart = 'document_discard';
                break;
            case DocumentWorkflow::TRANSITION_POSTPONE:
                $messageKeyPart = 'document_postpone';
                break;
            case DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE:
                $messageKeyPart = 'document_activate';
                break;
            case DocumentWorkflow::TRANSITION_RELEASE_PUBLISH:
                $messageKeyPart = 'document_ingest';
                break;
            default:
                $messageKeyPart = "document_update";
                break;
        }

        try {
            if ($reason) {
                $timezone = new \DateTimeZone($this->settings['timezone']);
                $timeStamp = (new \DateTime('now', $timezone))->format("d.m.Y H:i:s");

                if ($workflowTransition == DocumentWorkflow::TRANSITION_DISCARD) {
                    $note = LocalizationUtility::translate(
                        "manager.document.discard.note", "dpf", [$timeStamp, $reason]
                    );
                } elseif ($workflowTransition == DocumentWorkflow::TRANSITION_POSTPONE) {
                    $note = LocalizationUtility::translate(
                        "manager.document.postpone.note", "dpf", [$timeStamp, $reason]
                    );
                }

                $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
                $slub->addNote($note);
                $document->setSlubInfoData($slub->getSlubXml());
            }

            if ($this->documentManager->update($document, $workflowTransition)) {

                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:'.$messageKeyPart.'.success';
                $this->flashMessage($document, $key, AbstractMessage::OK);

                if ($this->security->getUser()->getUserRole() === Security::ROLE_LIBRARIAN) {
                    switch ($document->getState()) {
                        case DocumentWorkflow::STATE_POSTPONED_NONE:
                        case DocumentWorkflow::STATE_DISCARDED_NONE:
                        case DocumentWorkflow::STATE_NONE_INACTIVE:
                        case DocumentWorkflow::STATE_NONE_ACTIVE:
                        case DocumentWorkflow::STATE_NONE_DELETED:

                            if (
                                $this->bookmarkRepository->removeBookmark(
                                    $document,
                                    $this->security->getUser()->getUid()
                                )
                            ) {
                                $this->addFlashMessage(
                                    LocalizationUtility::translate("manager.workspace.bookmarkRemoved.singular", "dpf"), '',
                                    AbstractMessage::INFO
                                );
                            }

                            break;
                    }
                }

                $this->redirectToDocumentList();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:'.$messageKeyPart.'.failure';
                $this->flashMessage($document, $key, AbstractMessage::ERROR);
                $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {
            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:'.$messageKeyPart.'.failure';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirectToDocumentList();
        }

    }
}
