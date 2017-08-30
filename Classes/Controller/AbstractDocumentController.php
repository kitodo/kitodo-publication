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
 * DocumentController
 */
abstract class AbstractDocumentController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
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
    }

    /**
     * action new
     *
     * @param \EWW\Dpf\Domain\Model\Document $newDocument
     * @ignorevalidation $newDocument
     * @return void
     */
    public function newAction(\EWW\Dpf\Domain\Model\Document $newDocument = null)
    {
        $requestArguments = $this->request->getArguments();

        if (array_key_exists('documentType', $requestArguments)) {
            $docTypeUid   = $this->request->getArgument('documentType');
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
            $newDocument = \EWW\Dpf\Domain\Factory\DocumentFactory::create($documentType);
            $this->view->assign('document', $newDocument);
        } else {
            die('Error: Missing Document type.');
        }

    }

    public function initializeCreateAction()
    {
        $requestArguments = $this->request->getArguments();

        if ($this->request->hasArgument('document')) {
            $propertyMappingConfiguration = $this->arguments->getArgument('document')->getPropertyMappingConfiguration();
            $propertyMappingConfiguration->allowProperties('deleteFile');
            $propertyMappingConfiguration->allowProperties('primaryFile');
            $propertyMappingConfiguration->allowProperties('primFile');
            $propertyMappingConfiguration->allowProperties('secondaryFiles');
            $propertyMappingConfiguration->allowProperties('secFiles');

            // Metadata mapping
            if (key_exists('metadata', $requestArguments['document'])) {
               $metadata = $requestArguments['document']['metadata'];
               if (is_array($metadata)) {
                    $requestArguments['document']['metadata'] = serialize($metadata);
                    $this->request->setArguments($requestArguments);
               }
            }
        } else {
            $this->redirectToList("UPLOAD_POST_SIZE_ERROR");
        }
    }

    /**
     * action create
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function createAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $fileUploadMapper = $this->objectManager->get('EWW\Dpf\Helper\FileUploadMapper');
        $fileUploadMapper->setDocument($document);
        $newFiles = $fileUploadMapper->getNewAndUpdatedFiles();

        if ($fileUploadMapper->uploadError()) {

            $fileNames = array();
            foreach ($newFiles as $file) {
                $fileNames[] = $file->getTitle();
            }
            $this->redirect('list', 'Document', null, array('message' => 'UPLOAD_MAX_FILESIZE_ERROR', 'errorFiles' => $fileNames));

        } else {

            $processNumberGenerator = $this->objectManager->get("EWW\\Dpf\\Services\\ProcessNumber\\ProcessNumberGenerator");
            $processNumber = $processNumberGenerator->getProcessNumber();
            $document->setProcessNumber($processNumber);

            $this->documentRepository->add($document);
            $this->persistenceManager->persistAll();

            $document = $this->documentRepository->findByUid($document->getUid());
            $this->persistenceManager->persistAll();

            foreach ($newFiles as $newFile) {
                if ($newFile->getUID()) {
                    $this->fileRepository->update($newFile);
                } else {
                    $document->addFile($newFile);
                }
            }

            // ------------------------------------------------------------------------------
            // todo: no more use of metadata in xml format
            // ------------------------------------------------------------------------------
            $metadataExporter = $this->objectManager->get('EWW\Dpf\Helper\MetadataExporter');
            $modsXml = $metadataExporter->getModsXml($document);
            $mods = new \EWW\Dpf\Helper\Mods($modsXml);
            $document->setXmlData($modsXml);
            $document->setTitle($mods->getTitle());
            $document->setAuthors($mods->getAuthors());
            $document->setSlubInfoData($metadataExporter->getSlubInfoXml($document));
            $this->documentRepository->update($document);
            // ------------------------------------------------------------------------------

            $notifier = $this->objectManager->get('\EWW\Dpf\Services\Email\Notifier');

            $notifier->sendNewDocumentNotification($document);

            $requestArguments = $this->request->getArguments();
            if (array_key_exists('savecontinue', $requestArguments)) {
                $tmpDocument = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');
                $tmpDocument->setTitle($document->getTitle());
                $tmpDocument->setAuthors($document->getAuthors());
                $tmpDocument->setXmlData($document->getXmlData());
                $tmpDocument->setMetadata($document->getMetadata());
                $tmpDocument->setSlubInfoData($document->getSlubInfoData());
                $tmpDocument->setDocumentType($document->getDocumentType());
                $this->forward('new', null, null, array('document' => $tmpDocument));
            }

            $this->redirectToList('CREATE_OK');
        }
    }

    public function initializeEditAction()
    {
    }

    /**
     * action edit
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @ignorevalidation $document
     * @return void
     */
    public function editAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $files['primaryFile'] = $this->fileRepository->getPrimaryFileByDocument($document);
        $files['secondaryFiles'] = $this->fileRepository->getSecondaryFilesByDocument($document);

        // ---------------------------------------------------------------------------------
        // todo: replace workaround with an extension manager update script
        // Workaround to generate metadata array for old documents without an array
        // ---------------------------------------------------------------------------------
        $metadataExporter = $this->objectManager->get('EWW\Dpf\Helper\MetadataExporter');
        $metsXml = $metadataExporter->getMetsXml($document);
        $mets = new \EWW\Dpf\Helper\Mets($metsXml);
        $xpath = $mets->getMetsXpath();
        $xpath->registerNamespace("mets", "http://www.loc.gov/METS/");
        $dmdSec = $xpath->query("/mets:mets/mets:dmdSec");
        $dmdSec->item(0)->setAttribute("STATUS","ACTIVE");
        $tmpDocument = \EWW\Dpf\Domain\Factory\DocumentFactory::createFromMets("", $mets->getMetsXml());
        $document->setMetadata($tmpDocument->getMetadata());
        // ---------------------------------------------------------------------------------

        $this->view->assign('document', $document);
        $this->view->assign('files', $files);
    }

    public function initializeUpdateAction()
    {
        $requestArguments = $this->request->getArguments();

        if ($this->request->hasArgument('document')) {
            $propertyMappingConfiguration = $this->arguments->getArgument('document')->getPropertyMappingConfiguration();
            $propertyMappingConfiguration->allowProperties('deleteFile');
            $propertyMappingConfiguration->allowProperties('primaryFile');
            $propertyMappingConfiguration->allowProperties('primFile');
            $propertyMappingConfiguration->allowProperties('secondaryFiles');
            $propertyMappingConfiguration->allowProperties('secFiles');

            // Metadata mapping
            if (key_exists('metadata', $requestArguments['document'])) {
                $metadata = $requestArguments['document']['metadata'];
                if (is_array($metadata)) {
                    $requestArguments['document']['metadata'] = serialize($metadata);
                    $this->request->setArguments($requestArguments);
                }
            }
        } else {
            $this->redirectToList("UPLOAD_POST_SIZE_ERROR");
        }
    }

    /**
     * action update
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function updateAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $fileUploadMapper = $this->objectManager->get('EWW\Dpf\Helper\FileUploadMapper');
        $fileUploadMapper->setDocument($document);
        $newFiles = $fileUploadMapper->getNewAndUpdatedFiles();
        $deletedFiles = $fileUploadMapper->getDeletedFiles();

        if ($fileUploadMapper->uploadError()) {

            $fileNames = array();
            foreach ($newFiles as $file) {
                $fileNames[] = $file->getTitle();
            }
            $this->redirect('list', 'Document', null, array('message' => 'UPLOAD_MAX_FILESIZE_ERROR', 'errorFiles' => $fileNames));

        } else {
            $document->setChanged(true);
            $this->documentRepository->update($document);

            // Delete files
            foreach ($deletedFiles as $deleteFile) {
                $deleteFile->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_DELETED);
                $this->fileRepository->update($deleteFile);
            }

            // Add or update files
            foreach ($newFiles as $newFile) {
                if ($newFile->getUID()) {
                    $this->fileRepository->update($newFile);
                } else {
                    $document->addFile($newFile);
                }
            }

            // ------------------------------------------------------------------------------
            // todo: no more use of metadata in xml format
            // ------------------------------------------------------------------------------
            $metadataExporter = $this->objectManager->get('EWW\Dpf\Helper\MetadataExporter');
            $modsXml = $metadataExporter->getModsXml($document);
            $mods = new \EWW\Dpf\Helper\Mods($modsXml);
            $document->setXmlData($modsXml);
            $document->setTitle($mods->getTitle());
            $document->setAuthors($mods->getAuthors());
            $document->setSlubInfoData($metadataExporter->getSlubInfoXml($document));
            $this->documentRepository->update($document);
            // ------------------------------------------------------------------------------

            // add document to local es index
            $elasticsearchMapper = $this->objectManager->get('EWW\Dpf\Helper\ElasticsearchMapper');
            $json                = $elasticsearchMapper->getElasticsearchJson($document);

            $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
            // send document to index
            $elasticsearchRepository->add($document, $json);

            $requestArguments = $this->request->getArguments();
            if (array_key_exists('savecontinue', $requestArguments)) {
                $this->forward('edit', null, null, array('document' => $document));
            }

            $this->redirectToList();

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

    public function initializeAction()
    {
        parent::initializeAction();
    }

    protected function redirectToList($message = null)
    {
        $this->redirect('list');
    }

}
