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
use EWW\Dpf\Domain\Model\DocumentForm;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Helper\FormDataReader;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Domain\Model\DepositLicenseLog;
use Exception;

/**
 * DocumentFormController
 */
abstract class AbstractDocumentFormController extends AbstractController
{
    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentManager = null;

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
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataGroupRepository = null;

    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataObjectRepository = null;

    /**
     * depositLicenseLogRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseLogRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $depositLicenseLogRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $persistenceManager;

    /**
     * fisDataService
     *
     * @var \EWW\Dpf\Services\FeUser\FisDataService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fisDataService = null;

    /**
     * @var Document
     */
    protected $newDocument = null;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $documents = $this->documentRepository->findAll();
        $docTypes = $this->documentTypeRepository->getDocumentTypesAlphabetically();

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        $this->session->setStoredAction($this->getCurrentAction(), $this->getCurrentController(),
            $this->uriBuilder->getRequest()->getRequestUri()
        );

        $this->view->assign('documentTypes', $docTypes);
        $this->view->assign('documents', $documents);
    }

    /**
     * initialize newAction
     *
     * @return void
     */
    public function initializeNewAction()
    {
        $requestArguments = $this->request->getArguments();

        if (array_key_exists('documentData', $requestArguments)) {
            die('Error: initializeNewAction');
        } elseif (array_key_exists('documentType', $requestArguments)) {
            $docTypeUid   = $this->request->getArgument('documentType');
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
            $document     = $this->objectManager->get(Document::class);
            $document->setDocumentType($documentType);
            $mapper  = $this->objectManager->get(DocumentMapper::class);
            $docForm = $mapper->getDocumentForm($document);
        } elseif (array_key_exists('newDocumentForm', $requestArguments)) {
            $docForm = $this->request->getArgument('newDocumentForm');
            if (is_numeric($docForm)) {
                $sessionData = $this->session->getData();
                $docForm = null;
                if (array_key_exists('newDocumentForm', $sessionData)) {
                    $docForm = unserialize($sessionData['newDocumentForm']);
                }
            }
        }

        $requestArguments['newDocumentForm'] = $docForm;
        $this->request->setArguments($requestArguments);
    }

    /**
     * action new
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @param int $returnDocumentId
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("newDocumentForm")
     * @return void
     */
    public function newAction(DocumentForm $newDocumentForm = null, $returnDocumentId = 0)
    {
        $this->view->assign('returnDocumentId', $returnDocumentId);
        $this->view->assign('documentForm', $newDocumentForm);

        if (!empty($this->security->getUserAccessToGroups())) {
            $this->view->assign('currentUserAccessToGroup', $this->security->getUserAccessToGroups());
        }

        if ($this->fisDataService->getPersonData($this->security->getFisPersId())) {
            $this->view->assign('fisPersId', $this->security->getFisPersId());
        }
    }

    public function initializeCreateAction()
    {
        $this->documentFormMapping();
    }

    /**
     * action create
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @return void
     */
    public function createAction(DocumentForm $newDocumentForm)
    {
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $newDocument    = $documentMapper->getDocument($newDocumentForm);
        $this->newDocument = $newDocument;

        $workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();

        $workflow->apply($newDocument, DocumentWorkflow::TRANSITION_CREATE);

        if ($this->request->getPluginName() === "Backoffice") {
            $newDocument->setCreator($this->security->getUser()->getUid());
            //$workflow->apply($newDocument, DocumentWorkflow::TRANSITION_CREATE);
        } else {
            $newDocument->setCreator(0);
            $newDocument->setTemporary(true);
            //$workflow->apply($newDocument, DocumentWorkflow::TRANSITION_CREATE_REGISTER);
        }

        // xml data fields are limited to 64 KB
        if (strlen($newDocument->getXmlData()) >= Document::XML_DATA_SIZE_LIMIT) {
            throw new \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException("Maximum document size exceeded.");
        }

        $this->documentRepository->add($newDocument);
        $this->persistenceManager->persistAll();

        $newDocument = $this->documentRepository->findByUid($newDocument->getUid());
        $this->persistenceManager->persistAll();

        $depositLicenseLog = $this->depositLicenseLogRepository->findOneByProcessNumber($newDocument->getProcessNumber());
        if (empty($depositLicenseLog) && $newDocument->getDepositLicense()) {
            // Only if there was no deposit license a notification may be sent

            /** @var DepositLicenseLog $depositLicenseLog */
            $depositLicenseLog = $this->objectManager->get(DepositLicenseLog::class);
            $depositLicenseLog->setUsername($this->security->getUsername());
            $depositLicenseLog->setObjectIdentifier($newDocument->getObjectIdentifier());
            $depositLicenseLog->setProcessNumber($newDocument->getProcessNumber());
            $depositLicenseLog->setTitle($newDocument->getTitle());
            $depositLicenseLog->setUrn($newDocument->getPrimaryUrn());
            $depositLicenseLog->setLicenceUri($newDocument->getDepositLicense());

            if ($newDocument->hasFiles()) {
                $fileList = [];
                foreach ($newDocument->getFile() as $file) {
                    if (!$file->isFileGroupDeleted()) {
                        $fileList[] = $file->getTitle();
                    }
                }
                $depositLicenseLog->setFileNames(implode(", ", $fileList));
            }

            $this->depositLicenseLogRepository->add($depositLicenseLog);

            if (!$newDocument->isTemporary()) {
                /** @var Notifier $notifier */
                $notifier = $this->objectManager->get(Notifier::class);
                $notifier->sendDepositLicenseNotification($newDocument);
            }
        }

        // Add or update files
        $files = $newDocumentForm->getFiles();
        // TODO: Is this still necessary?
        if (is_array($files)) {
            foreach ($files as $file) {
                // TODO: Is this still necessary?
                if ($file->getUID()) {
                    $this->fileRepository->update($file);
                } else {
                    $file->setDocument($newDocument);
                    $this->fileRepository->add($file);
                    $newDocument->addFile($file);
                    $this->documentRepository->update($newDocument);
                }
            }
        }
        $this->persistenceManager->persistAll();

        if (!$newDocument->isTemporary()) {
            // index the document
            $this->signalSlotDispatcher->dispatch(AbstractController::class, 'indexDocument', [$newDocument]);
        }
    }

    public function initializeEditAction()
    {
        $requestArguments = $this->request->getArguments();

        if (array_key_exists('document', $requestArguments)) {

            $document = $this->documentManager->read(
                $this->request->getArgument('document')
            );

            if ($document) {
                $mapper = $this->objectManager->get(DocumentMapper::class);
                $documentForm = $mapper->getDocumentForm($document);
            }

        } elseif (array_key_exists('documentForm', $requestArguments)) {
            $documentForm = $this->request->getArgument('documentForm');
        }

        $requestArguments['documentForm'] = $documentForm;
        $this->request->setArguments($requestArguments);
    }

    /**
     * action edit
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("documentForm")
     * @return void
     */
    public function editAction(DocumentForm $documentForm)
    {
        $this->view->assign('documentForm', $documentForm);

        if (!empty($this->security->getUserAccessToGroups())) {
            $this->view->assign('currentUserAccessToGroup', $this->security->getUserAccessToGroups());
        }

        if ($this->fisDataService->getPersonData($this->security->getFisPersId())) {
            $this->view->assign('fisPersId', $this->security->getFisPersId());
        }
    }

    public function initializeUpdateAction()
    {
        $requestArguments = $this->request->getArguments();

        if ($this->request->hasArgument('documentData')) {
            $documentData = $this->request->getArgument('documentData');

            $formDataReader = $this->objectManager->get(FormDataReader::class);
            $formDataReader->setFormData($documentData);
            $docForm = $formDataReader->getDocumentForm();

            if (!$docForm->hasValidCsrfToken()) {
                throw new Exception("Invalid CSRF Token");
            }

            $requestArguments['documentForm'] = $docForm;

            $docTypeUid = $documentData['type'];
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
            $virtualType = $documentType->getVirtualType();

            $errorFiles = $formDataReader->uploadErrors();
            if (empty($errorFiles) || $virtualType === true) {
                $this->request->setArguments($requestArguments);
            } else {
                $this->redirectToList("UPLOAD_MAX_FILESIZE_ERROR", $errorFiles);
            }
        } else {
            $this->redirectToList("UPLOAD_POST_SIZE_ERROR");
        }
    }

    /**
     * action cancel
     *
     * @return void
     */
    public function cancelAction()
    {
        $this->redirectToList();
    }

    protected function redirectAfterUpdate()
    {
        $this->redirect('list');
    }

    /**
     * Redirects to the document list.
     *
     * @param null $message
     * @param array $errorFiles
     * @return mixed
     */
    protected function redirectToList($message = null, $errorFiles = [])
    {
        list($action, $controller, $redirectUri) = $this->session->getStoredAction();
        $this->redirect($action, $controller, null,
            [
                'message' => $message,
                'errorFiles' => $errorFiles,
            ]
        );
    }

    protected function documentFormMapping()
    {
        $requestArguments = $this->request->getArguments();

        if ($this->request->hasArgument('documentData')) {
            $documentData = $this->request->getArgument('documentData');

            $formDataReader = $this->objectManager->get(FormDataReader::class);
            $formDataReader->setFormData($documentData);

            $docForm = $formDataReader->getDocumentForm();

            if (!$docForm->hasValidCsrfToken()) {
                throw new Exception("Invalid CSRF Token");
            }

            $requestArguments['newDocumentForm'] = $docForm;

            $docTypeUid = $documentData['type'];
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
            $virtualType = $documentType->getVirtualType();

            $errorFiles = $formDataReader->uploadErrors();
            if (empty($errorFiles) || $virtualType === true) {
                $this->request->setArguments($requestArguments);
            } else {
                $this->redirectToList("UPLOAD_MAX_FILESIZE_ERROR", $errorFiles);
            }
        } else {
            $this->redirectToList("UPLOAD_POST_SIZE_ERROR");
        }
    }

}
