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

            //
            // ****
            //
            $metadataForExporter = $this->getMetadataForExporter($document);

            $exporter = new \EWW\Dpf\Services\MetsExporter();

            // mods:mods
            $modsData['documentUid'] = $document->getUid();
            $modsData['metadata'] = $metadataForExporter['mods'];
            $modsData['files'] = array();

            $exporter->buildModsFromForm($modsData);
            $modsXml = $exporter->getModsData();

            $document->setXmlData($modsXml);

            $mods = new \EWW\Dpf\Helper\Mods($modsXml);

            $document->setTitle($mods->getTitle());
            $document->setAuthors($mods->getAuthors());

            // slub:info
            $slubInfoData['documentUid'] = $document->getUid();
            $slubInfoData['metadata'] = $metadataForExporter['slubInfo'];
            $slubInfoData['files'] = array();
            $exporter->buildSlubInfoFromForm($slubInfoData, $document->getDocumentType(),
                $document->getProcessNumber());
            $slubInfoXml = $exporter->getSlubInfoData();

            $document->setSlubInfoData($slubInfoXml);

            $this->documentRepository->update($document);
            $this->persistenceManager->persistAll();
            //
            // ****
            //


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


/*
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
*/
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

            $this->documentRepository->update($document);
            //$this->persistenceManager->persistAll();


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


            //
            // ****
            //
            $metadataForExporter = $this->getMetadataForExporter($document);

            $exporter = new \EWW\Dpf\Services\MetsExporter();

            // mods:mods
            $modsData['documentUid'] = $document->getUid();
            $modsData['metadata'] = $metadataForExporter['mods'];
            $modsData['files'] = array();

            $exporter->buildModsFromForm($modsData);
            $modsXml = $exporter->getModsData();

            $document->setXmlData($modsXml);

            $mods = new \EWW\Dpf\Helper\Mods($modsXml);

            $document->setTitle($mods->getTitle());
            $document->setAuthors($mods->getAuthors());

            // slub:info
            $slubInfoData['documentUid'] = $document->getUid();
            $slubInfoData['metadata'] = $metadataForExporter['slubInfo'];
            $slubInfoData['files'] = array();
            $exporter->buildSlubInfoFromForm($slubInfoData, $document->getDocumentType(),
                $document->getProcessNumber());
            $slubInfoXml = $exporter->getSlubInfoData();

            $document->setSlubInfoData($slubInfoXml);

            $this->documentRepository->update($document);
            $this->persistenceManager->persistAll();
            //
            // ****
            //


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


/*
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
*/
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


    protected function getMetadataForExporter($document)
    {
        foreach ($document->getMetadata() as $groupUid => $group) {

            foreach ($group as $groupIndex => $groupItem) {

                $item = array();

                $metadataGroup = $this->metadataGroupRepository->findByUid($groupUid);

                $item['mapping'] = $metadataGroup->getRelativeMapping();

                $item['modsExtensionMapping'] = $metadataGroup->getRelativeModsExtensionMapping();

                $item['modsExtensionReference'] = trim($metadataGroup->getModsExtensionReference(), " /");

                $item['groupUid'] = $groupUid;

                $fieldValueCount   = 0;
                $defaultValueCount = 0;
                $fieldCount        = 0;
                foreach ($groupItem as $fieldUid => $field) {
                    foreach ($field as $fieldIndex => $fieldItem) {
                        $metadataObject = $this->metadataObjectRepository->findByUid($fieldUid);

                        $fieldMapping = $metadataObject->getRelativeMapping();

                        $formField = array();

                        $value = $fieldItem;

                        if ($metadataObject->getDataType() == \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_DATE) {
                            $date = date_create_from_format('d.m.Y', trim($value));
                            if ($date) {
                                $value = date_format($date, 'Y-m-d');
                            }
                        }

                        $fieldCount++;
                        if (!empty($value)) {
                            $fieldValueCount++;
                            $defaultValue = $metadataObject->getDefaultValue();
                            if ($defaultValue) {
                                $defaultValueCount++;
                            }
                        }

                        $value = str_replace('"', "'", $value);
                        if ($value) {
                            $formField['modsExtension'] = $metadataObject->getModsExtension();

                            $formField['mapping'] = $fieldMapping;
                            $formField['value']   = $value;

                            if (strpos($fieldMapping, "@") === 0) {
                                $item['attributes'][] = $formField;
                            } else {
                                $item['values'][] = $formField;
                            }
                        }
                    }
                }

                if (!key_exists('attributes', $item)) {
                    $item['attributes'] = array();
                }

                if (!key_exists('values', $item)) {
                    $item['values'] = array();
                }

                if ($metadataGroup->getMandatory() || $defaultValueCount < $fieldValueCount || $defaultValueCount == $fieldCount) {
                    if ($metadataGroup->isSlubInfo($metadataGroup->getMapping())) {
                        $form['slubInfo'][] = $item;
                    } else {
                        $form['mods'][] = $item;
                    }
                }

            }

        }


        return $form;

    }


}
