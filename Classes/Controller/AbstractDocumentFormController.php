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
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Helper\FormDataReader;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Domain\Model\DepositLicenseLog;


/**
 * DocumentFormController
 */
abstract class AbstractDocumentFormController extends AbstractController
{

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
        }

        $requestArguments['newDocumentForm'] = $docForm;
        $this->request->setArguments($requestArguments);
    }

    /**
     * action new
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @param int $returnDocumentId
     * @ignorevalidation $newDocumentForm
     * @return void
     */
    public function newAction(DocumentForm $newDocumentForm = null, $returnDocumentId = 0)
    {
        $this->view->assign('returnDocumentId', $returnDocumentId);
        $this->view->assign('documentForm', $newDocumentForm);

        if ($this->fisDataService->getPersonData($this->security->getFisPersId())) {
            $this->view->assign('fisPersId', $this->security->getFisPersId());
        }
    }

    public function initializeCreateAction()
    {

        $requestArguments = $this->request->getArguments();

        if ($this->request->hasArgument('documentData')) {
            $documentData = $this->request->getArgument('documentData');

            $formDataReader = $this->objectManager->get(FormDataReader::class);
            $formDataReader->setFormData($documentData);

            $docForm                             = $formDataReader->getDocumentForm();
            $requestArguments['newDocumentForm'] = $docForm;

            $docTypeUid = $documentData['type'];
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
            $virtualType = $documentType->getVirtualType();

            if (!$formDataReader->uploadError() || $virtualType === true) {
                $this->request->setArguments($requestArguments);
            } else {
                $t = $docForm->getNewFileNames();
                $this->redirect('list', 'DocumentForm', null, array('message' => 'UPLOAD_MAX_FILESIZE_ERROR', 'errorFiles' => $t));
            }
        } else {
            $this->redirectToList("UPLOAD_POST_SIZE_ERROR");
        }
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

        $workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();

        if ($this->request->getPluginName() === "Backoffice") {
            $newDocument->setCreator($this->security->getUser()->getUid());
            $workflow->apply($newDocument, DocumentWorkflow::TRANSITION_CREATE);
        } else {
            $workflow->apply($newDocument, DocumentWorkflow::TRANSITION_CREATE_REGISTER);
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
            $depositLicenseLog->setUrn($newDocument->getQucosaUrn());
            $depositLicenseLog->setLicenceUri($newDocument->getDepositLicense());

            if ($newDocument->getFileData()) {

                $fileList = [];
                foreach ($newDocument->getFile() as $file) {
                    if (!$file->isFileGroupDeleted()) {
                        $fileList[] = $file->getTitle();
                    }
                }
                $depositLicenseLog->setFileNames(implode(", ", $fileList));
            }

            $this->depositLicenseLogRepository->add($depositLicenseLog);

            /** @var Notifier $notifier */
            $notifier = $this->objectManager->get(Notifier::class);
            $notifier->sendDepositLicenseNotification($newDocument);
        }

        // Add or update files
        $newFiles = $newDocumentForm->getNewFiles();

        if (is_array($newFiles)) {
            foreach ($newFiles as $newFile) {

                if ($newFile->getUID()) {
                    $this->fileRepository->update($newFile);
                } else {
                    $newFile->setDocument($newDocument);
                    $this->fileRepository->add($newFile);
                    $newDocument->addFile($newFile);
                    $this->documentRepository->update($newDocument);
                }
            }
        }
        $this->persistenceManager->persistAll();

        // index the document
        $this->signalSlotDispatcher->dispatch(AbstractController::class, 'indexDocument', [$newDocument]);

    }

    public function initializeEditAction()
    {
        $requestArguments = $this->request->getArguments();

        if (array_key_exists('document', $requestArguments)) {

            $document = $this->documentManager->read(
                $this->request->getArgument('document'),
                $this->security->getUser()->getUID()
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
     * @ignorevalidation $documentForm
     * @return void
     */
    public function editAction(DocumentForm $documentForm)
    {
        $this->view->assign('documentForm', $documentForm);

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

            $requestArguments['documentForm'] = $docForm;

            $docTypeUid = $documentData['type'];
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
            $virtualType = $documentType->getVirtualType();

            if (!$formDataReader->uploadError() || $virtualType === true) {
                $this->request->setArguments($requestArguments);
            } else {
                $t = $docForm->getNewFileNames();
                $this->redirect('list', 'Document', null, array('message' => 'UPLOAD_MAX_FILESIZE_ERROR', 'errorFiles' => $t));
            }
        } else {
            $this->redirectToList("UPLOAD_POST_SIZE_ERROR");
        }
    }

    /**
     * action update
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @return void
     */
    public function updateAction(DocumentForm $documentForm)
    {
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $updateDocument \EWW\Dpf\Domain\Model\Document */
        $updateDocument = $documentMapper->getDocument($documentForm);

        // xml data fields are limited to 64 KB
        if (strlen($updateDocument->getXmlData()) >= Document::XML_DATA_SIZE_LIMIT) {
            throw new \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException("Maximum document size exceeded.");
        }

        // add document to local es index
        $elasticsearchMapper = $this->objectManager->get(ElasticsearchMapper::class);
        $json                = $elasticsearchMapper->getElasticsearchJson($updateDocument);

        $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
        // send document to index
        $elasticsearchRepository->add($updateDocument, $json);

        $updateDocument->setChanged(true);
        $this->documentRepository->update($updateDocument);


        // Delete files
        foreach ($documentForm->getDeletedFiles() as $deleteFile) {
            $deleteFile->setStatus(File::STATUS_DELETED);
            $this->fileRepository->update($deleteFile);
        }

        // Add or update files
        foreach ($documentForm->getNewFiles() as $newFile) {

            if ($newFile->getUID()) {
                $this->fileRepository->update($newFile);
            } else {
                $updateDocument->addFile($newFile);
            }

        }

        // index the document
        $this->signalSlotDispatcher->dispatch(AbstractController::class, 'indexDocument', [$updateDocument]);
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

    protected function redirectToList($message = null)
    {
        $this->redirect('list');
    }

}
