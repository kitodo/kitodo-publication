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
use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Helper\ElasticsearchMapper;
use EWW\Dpf\Helper\FormDataReader;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;

/**
 * DocumentFormController
 */
abstract class AbstractDocumentFormController extends \EWW\Dpf\Controller\AbstractController
{

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
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository = null;

    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @inject
     */
    protected $metadataObjectRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $documents = $this->documentRepository->findAll();

        $documentTypes = $this->documentTypeRepository->findAll();

        $data = array();
        $docTypes = array();
        $name = array();
        $type = array();

        foreach ($documentTypes as $docType) {
            $data[] = array(
                "name" => $docType->getDisplayName(),
                "type" => $docType,
            );
        }

        foreach ($data as $key => $row) {
            $name[$key] = $row['name'];
            $type[$key] = $row['type'];
        }

        array_multisort($name, SORT_ASC, SORT_LOCALE_STRING, $type, SORT_ASC, $data);

        foreach ($data as $item) {
            $docTypes[] = $item['type'];
        }

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
     * @ignorevalidation $newDocumentForm
     * @return void
     */
    public function newAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm = null)
    {
        $this->view->assign('documentForm', $newDocumentForm);
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
            $virtual = $documentType->getVirtual();

            if (!$formDataReader->uploadError() || $virtual === true) {
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
    public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm)
    {
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $newDocument    = $documentMapper->getDocument($newDocumentForm);

        $workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();

        if ($this->request->getPluginName() === "Backoffice") {
            $ownerUid = $this->security->getUser()->getUid();
            $newDocument->setOwner($ownerUid);
            $workflow->apply($newDocument, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_CREATE);
        } else {
            $workflow->apply($newDocument, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_CREATE_REGISTER);
        }

        // xml data fields are limited to 64 KB
        if (strlen($newDocument->getXmlData()) >= 64 * 1024 || strlen($newDocument->getSlubInfoData() >= 64 * 1024)) {
            throw new \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException("Maximum document size exceeded.");
        }

        $this->documentRepository->add($newDocument);
        $this->persistenceManager->persistAll();

        $newDocument = $this->documentRepository->findByUid($newDocument->getUid());
        $this->persistenceManager->persistAll();

        // Add or update files
        $newFiles = $newDocumentForm->getNewFiles();

        if (is_array($newFiles)) {
            foreach ($newFiles as $newFile) {

                if ($newFile->getUID()) {
                    $this->fileRepository->update($newFile);
                } else {
                    $newFile->setDocument($newDocument);
                    $this->fileRepository->add($newFile);
                }
            }
        }
    }

    public function initializeEditAction()
    {
        $requestArguments = $this->request->getArguments();

        if (array_key_exists('document', $requestArguments)) {

            if ($this->request->getArgument('document') instanceof \EWW\Dpf\Domain\Model\Document) {
                $document = $this->request->getArgument('document');
            } elseif (is_numeric($this->request->getArgument('document'))) {
                $document = $this->documentRepository->findByUid($this->request->getArgument('document'));
            }

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
    public function editAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        $this->view->assign('documentForm', $documentForm);
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
            $virtual = $documentType->getVirtual();

            if (!$formDataReader->uploadError() || $virtual === true) {
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
    public function updateAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $updateDocument \EWW\Dpf\Domain\Model\Document */
        $updateDocument = $documentMapper->getDocument($documentForm);

        // xml data fields are limited to 64 KB
        if (strlen($updateDocument->getXmlData()) >= 64 * 1024 || strlen($updateDocument->getSlubInfoData() >= 64 * 1024)) {
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
            $deleteFile->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_DELETED);
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
