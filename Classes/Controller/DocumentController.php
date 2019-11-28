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
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Helper\ElasticsearchMapper;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use EWW\Dpf\Helper\DocumentMapper;
use TYPO3\CMS\Backend\Exception;
use EWW\Dpf\Domain\Model\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;


/**
 * DocumentController
 */
class DocumentController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

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

    /**
     * action list
     *
     * @param array $stateFilters
     *
     * @return void
     */
    public function listAction($stateFilters = array())
    {
        $this->setSessionData('redirectToDocumentListAction','list');
        $this->setSessionData('redirectToDocumentListController','Document');

        list($isWorkspace, $documents) = $this->getListViewData($stateFilters);

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

    public function listSuggestionsAction() {
        $this->setSessionData('redirectToDocumentListAction','listSuggestions');
        $this->setSessionData('redirectToDocumentListController','Document');

        list($isWorkspace, $documents) = $this->getListViewData([], true);

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

        $linkedUid = $document->getLinkedUid();

        /** @var \EWW\Dpf\Domain\Model\Document $originDocument */
        $originDocument = $this->documentRepository->findByUid($linkedUid);

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

            $this->documentRepository->add($originDocument);
            $this->documentRepository->remove($document);
        }

        $this->redirectToDocumentList();
    }


    public function showSuggestionDetailsAction(\EWW\Dpf\Domain\Model\Document $document) {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::SHOW_DETAILS, $document);

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        $linkedDocument = $this->documentRepository->findByUid($document->getLinkedUid());
        $linkedDocumentForm = $documentMapper->getDocumentForm($linkedDocument);

        $newDocumentForm = $documentMapper->getDocumentForm($document);

        $diff = $this->documentFormDiff($linkedDocumentForm, $newDocumentForm);

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

                                } else if ($valueRepeatField->getValue() != $value2[$keyRepeatField]->getValue() && !empty($value2[$keyRepeatField]->getValue())) {
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

    public function listRegisteredAction()
    {
        $this->setSessionData('redirectToDocumentListAction','listRegistered');
        $this->setSessionData('redirectToDocumentListController','Document');

        list($isWorkspace, $documents) = $this->getListViewData([DocumentWorkflow::STATE_REGISTERED_NONE]);

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        $this->view->assign('isWorkspace', $isWorkspace);
        $this->view->assign('documents', $documents);
    }

    public function listInProgressAction()
    {
        $this->setSessionData('redirectToDocumentListAction','listInProgress');
        $this->setSessionData('redirectToDocumentListController','Document');

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        list($isWorkspace, $documents) = $this->getListViewData(
            [
                DocumentWorkflow::STATE_IN_PROGRESS_NONE,
                DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE,
                DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE,
                DocumentWorkflow::STATE_IN_PROGRESS_DELETED,
            ]
        );

        $this->view->assign('isWorkspace', $isWorkspace);
        $this->view->assign('documents', $documents);
    }


    /**
     * action discard
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @param string $reason
     * @return void
     */
    public function discardAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp, $reason = NULL)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::DISCARD, $document)) {
            if ($document->getEditorUid()) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $document->setEditorUid($this->security->getUser()->getUid());

        if ($reason) {
            $timeStamp = (new \DateTime)->format("d.m.Y H:i:s");
            $note = "Das Dokument wurde verworfen: ".$timeStamp."\n".$reason;
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $slub->addNote($note);
            $document->setSlubInfoData($slub->getSlubXml());
        }

        try {
                if (
                    in_array(
                        $document->getState(),
                        [
                            DocumentWorkflow::STATE_IN_PROGRESS_DELETED,
                            DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE,
                            DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE
                        ]
                    )
                ) {
                    if ($document->getTemporary()) {
                        $noNewerVersion = $this->documentTransferManager->getLastModDate($document->getObjectIdentifier()) === $document->getRemoteLastModDate();
                    } else {
                        $noNewerVersion = $tstamp === $document->getTstamp();
                    }

                    if ($noNewerVersion) {
                        if ($this->documentTransferManager->delete($document, "") && $this->documentTransferManager->update($document)) {
                            $this->workflow->apply($document, DocumentWorkflow::TRANSITION_DISCARD);
                            $this->documentRepository->update($document);
                            $this->documentRepository->remove($document);
                            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.success';
                            $this->flashMessage($document, $key, AbstractMessage::OK);
                            $this->redirectToDocumentList();
                        } else {
                            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failure';
                            $this->flashMessage($document, $key, AbstractMessage::ERROR);
                            $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
                        }
                    } else {
                        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failureNewVersion';
                        $this->flashMessage($document, $key, AbstractMessage::ERROR);
                        $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
                    }
                } else {
                    if ($tstamp === $document->getTstamp()) {
                        $this->workflow->apply($document, DocumentWorkflow::TRANSITION_DISCARD);
                        $this->documentRepository->update($document);
                        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.success';
                        $this->flashMessage($document, $key, AbstractMessage::OK);
                        $this->redirectToDocumentList();
                    } else {
                        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failureNewVersion';
                        $this->flashMessage($document, $key, AbstractMessage::ERROR);
                        $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
                    }
                }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {
            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failure';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirectToDocumentList();
        }
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
            if ($document->getEditorUid()) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $document->setEditorUid($this->security->getUser()->getUid());

        if ($reason) {
            $timeStamp = (new \DateTime)->format("d.m.Y H:i:s");
            $note = "Das Dokument wurde zurückgestellt: ".$timeStamp."\n".$reason;
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $slub->addNote($note);
            $document->setSlubInfoData($slub->getSlubXml());
        }

        try {
            if (
                in_array(
                    $document->getState(),
                    [
                        DocumentWorkflow::STATE_IN_PROGRESS_DELETED,
                        DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE,
                        DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE
                    ]
                )
            ) {
                if ($document->getTemporary()) {
                    $noNewerVersion = $this->documentTransferManager->getLastModDate($document->getObjectIdentifier()) === $document->getRemoteLastModDate();
                } else {
                    $noNewerVersion = $tstamp === $document->getTstamp();
                }

                if ($noNewerVersion) {
                    if ($this->documentTransferManager->delete($document, "inactivate") && $this->documentTransferManager->update($document)) {
                        $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_POSTPONE);
                        $this->documentRepository->update($document);
                        $this->documentRepository->remove($document);
                        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.success';
                        $this->flashMessage($document, $key, AbstractMessage::OK);
                        $this->redirectToDocumentList();
                    } else {
                        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failure';
                        $this->flashMessage($document, $key, AbstractMessage::ERROR);
                        $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
                    }
                } else {
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failureNewVersion';
                    $this->flashMessage($document, $key, AbstractMessage::ERROR);
                    $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
                }
            } else {
                if ($tstamp === $document->getTstamp()) {
                    $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_POSTPONE);
                    $this->documentRepository->update($document);
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.success';
                    $this->flashMessage($document, $key, AbstractMessage::OK);
                    $this->redirectToDocumentList();
                } else {
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failureNewVersion';
                    $this->flashMessage($document, $key, AbstractMessage::ERROR);
                    $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
                }
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {
            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failed';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirectToDocumentList();
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
            if ($document->getEditorUid()) {
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

            $suggestions = $this->documentRepository->findByLinkedUid($document->getUid());
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
     * action duplicate
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function duplicateAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::DUPLICATE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        try {
            /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
            $newDocument = $this->objectManager->get(Document::class);

            $newDocument->setState(DocumentWorkflow::STATE_NEW_NONE);

            $newDocument->setTitle($document->getTitle());
            $newDocument->setAuthors($document->getAuthors());

            $newDocument->setOwner($this->security->getUser()->getUid());

            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $mods->clearAllUrn();
            $newDocument->setXmlData($mods->getModsXml());

            $newDocument->setDocumentType($document->getDocumentType());

            $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
            $processNumber = $processNumberGenerator->getProcessNumber();
            $newDocument->setProcessNumber($processNumber);

            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $slub->setProcessNumber($processNumber);
            $newDocument->setSlubInfoData($slub->getSlubXml());

            // send document to index
            $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);

            $elasticsearchMapper = $this->objectManager->get(ElasticsearchMapper::class);
            $json = $elasticsearchMapper->getElasticsearchJson($newDocument);

            $elasticsearchRepository->add($newDocument, $json);

            $this->documentRepository->add($newDocument);

            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.success';
            $this->flashMessage($document, $key, AbstractMessage::OK);
            $this->redirect('list');
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {
            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.failure';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('list');
        }
    }


    /**
     * releaseUpdateAction
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @return void
     */
    public function releaseUpdateAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::RELEASE_UPDATE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        try {
            if (
                $this->documentTransferManager->getLastModDate($document->getObjectIdentifier()) === $document->getRemoteLastModDate() &&
                $tstamp === $document->getTstamp()
            ) {
                if ($this->documentTransferManager->update($document)) {
                    $this->documentRepository->remove($document);
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success';
                    $this->flashMessage($document, $key, AbstractMessage::OK);
                    $this->redirect('list');
                }
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failureNewVersion';
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
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('list');
        }

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

        try {
            if ($tstamp === $document->getTstamp()) {
                if ($this->documentTransferManager->ingest($document)) {
                    $notifier = $this->objectManager->get(Notifier::class);
                    $notifier->sendIngestNotification($document);
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.success';
                    $this->flashMessage($document, $key, AbstractMessage::OK);
                    $this->redirectToDocumentList();
                } else {
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.failure';
                    $this->flashMessage($document, $key, AbstractMessage::ERROR);
                    $this->redirect('showDetails', 'Document', null, ['document' => $document]);
                }
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.failureNewVersion';
                $this->flashMessage($document, $key, AbstractMessage::ERROR);
                $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('list');
        }
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

        try {
            if (
                $this->documentTransferManager->getLastModDate($document->getObjectIdentifier()) === $document->getRemoteLastModDate() &&
                $tstamp === $document->getTstamp()
            ) {
                if ($this->documentTransferManager->delete($document, "revert") && $this->documentTransferManager->update($document)) {
                    $this->documentRepository->remove($document);
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.success';
                    $this->flashMessage($document, $key, AbstractMessage::OK);
                    $this->redirectToDocumentList();
                } else {
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.failure';
                    $this->flashMessage($document, $key, AbstractMessage::ERROR);
                    $this->redirect('showDetails', 'Document', null, ['document' => $document]);
                }
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.failureNewVersion';
                $this->flashMessage($document, $key, AbstractMessage::ERROR);
                $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {
            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('list');
        }
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

        if (!$this->documentValidator->validate($document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.missingValues';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        }

        $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_REGISTER);
        $this->documentRepository->update($document);

        $notifier = $this->objectManager->get(Notifier::class);
        $notifier->sendRegisterNotification($document);

        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.success';
        $this->flashMessage($document, $key, AbstractMessage::OK);
        $this->redirect('showDetails', 'Document', null, ['document' => $document]);
    }

    /**
     * action showDetails
     *
     * @param Document $document
     * @return void
     */
    public function showDetailsAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::SHOW_DETAILS, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_showDetails.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirectToDocumentList();
        }

        $postponeOptions = $this->inputOptionListRepository->findOneByName($this->settings['postponeOptionListName']);
        if ($postponeOptions) {
            $this->view->assign('postponeOptions', $postponeOptions->getInputOptions());
        }

        $discardOptions = $this->inputOptionListRepository->findOneByName($this->settings['discardOptionListName']);
        if ($discardOptions) {
            $this->view->assign('discardOptions', $discardOptions->getInputOptions());
        }

        $this->view->assign('document', $document);
    }


    public function cancelListTaskAction()
    {
        $this->redirectToDocumentList();
    }

    /**
     * action uploadFiles
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function uploadFilesAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
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
        $redirectAction = $this->getSessionData('redirectToDocumentListAction');
        $redirectController = $this->getSessionData('redirectToDocumentListController');
        $this->redirect($redirectAction, $redirectController, null, array('message' => $message));
    }


    /**
     * get list view data
     *
     * @param array $stateFilters
     * @param bool $suggestionsOnly
     *
     * @return array
     */
    protected function getListViewData($stateFilters = array(), bool $suggestionsOnly = false)
    {
        switch ($this->security->getUserRole()) {

            case Security::ROLE_LIBRARIAN:
                if ($suggestionsOnly) {
                    $documents = $this->documentRepository->findAllLibrarianDocumentSuggestions(
                        $this->security->getUser()->getUid()
                    );
                } else {
                    $documents = $this->documentRepository->findAllOfALibrarian(
                        $this->security->getUser()->getUid(),
                        $stateFilters
                    );
                }
                $isWorkspace = TRUE;
                break;

            case Security::ROLE_RESEARCHER;
                if ($suggestionsOnly) {
                    $documents = $this->documentRepository->findAllResearcherDocumentSuggestions(
                        $this->security->getUser()->getUid()
                    );
                } else {
                    $documents = $this->documentRepository->findAllOfAResearcher(
                        $this->security->getUser()->getUid(),
                        $stateFilters
                    );
                }
                break;

            default:
                $documents = NULL;
        }

        return array(
            $isWorkspace,
            $documents
        );
    }

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

        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? $defaultMessage : $message;

        $this->addFlashMessage(
            $message,
            '',
            $severity,
            true
        );

    }
}
