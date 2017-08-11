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

/**
 * DocumentFormController
 */
abstract class AbstractDocumentFormController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
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

        $this->view->assign('listtype', $this->settings['listtype']);

        $this->view->assign('documentTypes', $docTypes);
        $this->view->assign('documents', $documents);
    }

    /**
     * action show
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function showAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        $this->view->assign('document', $document);
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
            $document     = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');
            $document->setDocumentType($documentType);
            $mapper  = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');
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

            $formDataReader = $this->objectManager->get('EWW\Dpf\Helper\FormDataReader');
            $formDataReader->setFormData($documentData);

            $docForm                             = $formDataReader->getDocumentForm();
            $requestArguments['newDocumentForm'] = $docForm;

            if (!$formDataReader->uploadError()) {
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

        $documentMapper = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');
        $newDocument    = $documentMapper->getDocument($newDocumentForm);

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

        $notifier = $this->objectManager->get('\EWW\Dpf\Services\Email\Notifier');

        $notifier->sendNewDocumentNotification($newDocument);

        $requestArguments = $this->request->getArguments();

        if (array_key_exists('savecontinue', $requestArguments)) {

            $tmpDocument = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');

            $tmpDocument->setTitle($newDocument->getTitle());
            $tmpDocument->setAuthors($newDocument->getAuthors());
            $tmpDocument->setXmlData($newDocument->getXmlData());
            $tmpDocument->setSlubInfoData($newDocument->getSlubInfoData());
            $tmpDocument->setDocumentType($newDocument->getDocumentType());

            $this->forward('new', null, null, array('newDocumentForm' => $documentMapper->getDocumentForm($tmpDocument)));
        }

        $this->redirectToList('CREATE_OK');
    }

    public function initializeEditAction()
    {

        $requestArguments = $this->request->getArguments();

        if (array_key_exists('document', $requestArguments)) {
            $documentUid  = $this->request->getArgument('document');
            $document     = $this->documentRepository->findByUid($documentUid);
            $mapper       = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');
            $documentForm = $mapper->getDocumentForm($document);
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

            $formDataReader = $this->objectManager->get('EWW\Dpf\Helper\FormDataReader');
            $formDataReader->setFormData($documentData);
            $docForm = $formDataReader->getDocumentForm();

            $requestArguments['documentForm'] = $docForm;

            if (!$formDataReader->uploadError()) {
                $this->request->setArguments($requestArguments);
            } else {
                $t = $docForm->getNewFileNames();
                $this->redirect('list', 'DocumentManager', null, array('message' => 'UPLOAD_MAX_FILESIZE_ERROR', 'errorFiles' => $t));
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

        $requestArguments = $this->request->getArguments();

        $documentMapper = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');
        $updateDocument = $documentMapper->getDocument($documentForm);

        $objectIdentifier = $updateDocument->getObjectIdentifier();

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

        // add document to local es index
        $elasticsearchMapper = $this->objectManager->get('EWW\Dpf\Helper\ElasticsearchMapper');
        $json                = $elasticsearchMapper->getElasticsearchJson($updateDocument);

        $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
        // send document to index
        $elasticsearchRepository->add($updateDocument, $json);

        if (array_key_exists('savecontinue', $requestArguments)) {
            $this->forward('edit', null, null, array('documentForm' => $documentForm));
        }

        $this->redirectToList();
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

    public function initializeAction()
    {
        parent::initializeAction();
    }

    protected function redirectToList($message = null)
    {
        $this->redirect('list');
    }

}
