<?php
namespace EWW\Dpf\Helper;


class FormDataReader {
  
  
  /**
   * documentTypeRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
   * @inject
   */
  protected $documentTypeRepository = NULL;        

    
  /**
   * metadataPageRepository
   *
   * @var \EWW\Dpf\Domain\Repository\MetadataPageRepository
   * @inject
   */
  protected $metadataPageRepository = NULL;        

  
  
  /**
   * metadataGroupRepository
   *
   * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
   * @inject
   */
  protected $metadataGroupRepository = NULL;        
  
    
  /**
   * metadataObjectRepository
   *
   * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
   * @inject
   */
  protected $metadataObjectRepository = NULL;
  
  
  /**
   * fileRepository
   *
   * @var \EWW\Dpf\Domain\Repository\FileRepository
   * @inject
   */
  protected $fileRepository = NULL;        
  
  
  /**
   * documentRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentRepository
   * @inject
   */
  protected $documentRepository = NULL;        
  
  
  /**
   * objectManager
   * 
   * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
   * @inject
   */
  protected $objectManager;
  
  
  /**
   * 
   * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
   * @inject
   */
  protected $configurationManager;

  
  /**
   * formData
   *     
   * @var array
   */
  protected $formData;
  
        
  /**
   * documentType
   * 
   * @var  
   */
  protected $documentType;
  
  
  
  
  /**
   * uploadPath
   * 
   * @var
   */
  protected $uploadPath; 
  
  
  /**
   * basePath
   * 
   * @var
   */
  protected $basePath;
          
  
  public function __construct() {

    $uploadDir = "uploads/tx_dpf/";
    
    $this->basePath = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
    
    $port = '';
    
    if ($_SERVER['SERVER_PORT'] && intval($_SERVER['SERVER_PORT']) != 80) {
        $port = ':'.$_SERVER['SERVER_PORT'];
    } 
        
    $this->basePath .= trim($_SERVER['SERVER_NAME'],"/").$port."/".$uploadDir;
        
    $this->uploadPath = PATH_site . $uploadDir;
        
  }
   
  
  /**
   * 
   * @param array $formData
   */
  public function setFormData($formData) {                                    
    $this->formData = $formData;             
    $this->documentType = $this->documentTypeRepository->findByUid($formData['type']);
  }
  
  
  protected function getFields() {
      
    $fields = array();
    
    if (is_array($this->formData['metadata'])) {
        foreach ($this->formData['metadata'] as $key => $value) {      
            $formField = new \EWW\Dpf\Helper\FormField($key,$value);      
            $fields[] = $formField;            
        }
    }    
    
    return $fields;    
  }
  
  
  protected function getDeletedFiles() {  
      
    $deletedFiles = array(); 
      
    if (is_array($this->formData['deleteFile'])) {
        foreach ($this->formData['deleteFile'] as $key => $value) {     
                                 
            $file = $this->fileRepository->findByUid($value);
      
            // Deleting the primary file is not allowed. 
            if (!$file->isPrimaryFile()) {
                $deletedFiles[] = $file;                                   
            }
            
        }
    }    
    
    return $deletedFiles;    
  }
  
   
  protected function getNewAndUpdatedFiles() {
                        
    $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
    $fullTextLabel = $frameworkConfiguration['settings']['defaultValue']['fullTextLabel'];  
      
    $newFiles = array();
    
    // Primary file                 
    if ($this->formData['primaryFile'] && $this->formData['primaryFile']['error'] != 4) {
                 
      // Use the existing file entry
      $file = NULL;
      $document = $this->documentRepository->findByUid($this->formData['documentUid']);            
      if ($document) {
        $file = $this->fileRepository->getPrimaryFileByDocument($document);
      }  
      
      $newPrimaryFile = $this->getUploadedFile($this->formData['primaryFile'], TRUE, $file); 
      $newPrimaryFile->setLabel($fullTextLabel);
      
      $newFiles[] = $newPrimaryFile;
    }
          
    if (is_array($this->formData['primFile'])) {
                       
          foreach ($this->formData['primFile'] as $fileId => $fileData) {
       
              $file = $this->fileRepository->findByUID($fileId);                                           
              $fileStatus = $file->getStatus();
                                                    
              if(empty($fileData['label'])) {               
                  $fileData['label'] = $fullTextLabel;
              }
              
              if ($file->getLabel() != $fileData['label'] || 
                  $file->getDownload() != !empty($fileData['download']) ||
                  $file->getArchive() != !empty($fileData['archive']) ) {              
                                                                                                 
                $file->setLabel($fileData['label']);
                $file->setDownload(!empty($fileData['download']));  
                $file->setArchive(!empty($fileData['archive']));
                
                if (empty($fileStatus)) {
                    $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);  
                }
                                     
                $newFiles[] = $file;
              }  
          }
             
      }
   
    
    // Secondary files
    if (is_array($this->formData['secondaryFiles'])) {
        foreach ($this->formData['secondaryFiles'] as $tmpFile ) {      
            if ($tmpFile['error'] != 4) {                                          
                $f = $this->getUploadedFile($tmpFile);               
                $newFiles[] = $f;                                      
            }
        }
    }    
          
    if (is_array($this->formData['secFiles'])) {
                          
          foreach ($this->formData['secFiles'] as $fileId => $fileData) {
     
              $file = $this->fileRepository->findByUID($fileId);                                           
              $fileStatus = $file->getStatus();
                                                    
              if ($file->getLabel() != $fileData['label'] || 
                  $file->getDownload() != !empty($fileData['download']) ||
                  $file->getArchive() != !empty($fileData['archive']) ) {              
                                                                                                 
                $file->setLabel($fileData['label']);
                $file->setDownload(!empty($fileData['download']));  
                $file->setArchive(!empty($fileData['archive']));
                
                if (empty($fileStatus)) {
                    $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);  
                }
                               
                $newFiles[] = $file;
              }  
          }
             
      }
                 
    return $newFiles;
    
  }
  
  
  protected function getUploadedFile($tmpFile , $primary = FALSE, \EWW\Dpf\Domain\Model\File $file = NULL) {
    
      if (empty($file)) {              
        $file = $this->objectManager->get('EWW\Dpf\Domain\Model\File');              
      }
                  
      $fileName = uniqid(time(),true);
                  
      if (\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($tmpFile['tmp_name'],$this->uploadPath.$fileName) ) {                    
                  
        $finfo = finfo_open(FILEINFO_MIME_TYPE); 
        $contentType = finfo_file($finfo, $this->uploadPath.$fileName);
        finfo_close($finfo);                   
          
        //$file->setContentType($tmpFile['type']);          
        $file->setContentType($contentType);          
                                                          
        $file->setTitle($tmpFile['name']);        
        $file->setLabel($tmpFile['label']);        
        $file->setDownload(!empty($tmpFile['download']));  
        $file->setArchive(!empty($tmpFile['archive']));
        $file->setLink($this->basePath.$fileName);
        $file->setPrimaryFile($primary);
                    
        if ($rimary) {                          
            if ($file->getDatastreamIdentifier()) {
                $file->setStatus( \EWW\Dpf\Domain\Model\File::STATUS_CHANGED);
            } else {
                $file->setStatus( \EWW\Dpf\Domain\Model\File::STATUS_ADDED);
            }  
        } else {           
            $file->setStatus( \EWW\Dpf\Domain\Model\File::STATUS_ADDED);                  
        }

        return $file;
    } else {
        die("File didn't upload: ".$tmpFile['name']);
    }                   
}        
  
  
  public function getDocumentForm() { 
   
    $fields = $this->getFields();
                        
    $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();      
    $documentForm->setUid($this->documentType->getUid());    
    $documentForm->setDisplayName($this->documentType->getDisplayName());
    $documentForm->setName($this->documentType->getName());
    $documentForm->setDocumentUid($this->formData['documentUid']);
    $documentForm->setQucosaId($this->formData['qucosaId']);    
    $documentForm->setValid(!empty($this->formData['validDocument']));    
     
    $documentData = array();
    
    foreach ($fields as $field) {                    
      $pageUid = $field->getPageUid();
      $groupUid = $field->getGroupUid();
      $groupIndex = $field->getGroupIndex();
      $fieldUid = $field->getFieldUid();
      $fieldIndex = $field->getFieldIndex();
      $value = $field->getValue();
      
      $documentData[$pageUid][$groupUid][$groupIndex][$fieldUid][$fieldIndex] = $value;      
    }
        
    foreach ($documentData as $pageUid => $page ) {               
      $metadataPage = $this->metadataPageRepository->findByUid($pageUid);            
      $documentFormPage = new \EWW\Dpf\Domain\Model\DocumentFormPage();
      $documentFormPage->setUid($metadataPage->getUid());
      $documentFormPage->setDisplayName($metadataPage->getDisplayName());
      $documentFormPage->setName($metadataPage->getName());                    
      $documentFormPage->setBackendOnly($metadataPage->getBackendOnly());
      
      foreach ($page as $groupUid => $groupItem ) {           
        foreach ($groupItem as $group ) {   
          $metadataGroup = $this->metadataGroupRepository->findByUid($groupUid);        
          $documentFormGroup = new \EWW\Dpf\Domain\Model\DocumentFormGroup();
          $documentFormGroup->setUid($metadataGroup->getUid());
          $documentFormGroup->setDisplayName($metadataGroup->getDisplayName());
          $documentFormGroup->setName($metadataGroup->getName());                    
          $documentFormGroup->setMandatory($metadataGroup->getMandatory());
          $documentFormGroup->setBackendOnly($metadataGroup->getBackendOnly());
          $documentFormGroup->setMaxIteration($metadataGroup->getMaxIteration());   
          
                 
        foreach ($group as $objectUid => $objectItem ) {     
          foreach ($objectItem as $objectItem => $object ) {  
            $metadataObject = $this->metadataObjectRepository->findByUid($objectUid);                               
            $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();                        
            $documentFormField->setUid($metadataObject->getUid());
            $documentFormField->setDisplayName($metadataObject->getDisplayName());
            $documentFormField->setName($metadataObject->getName());
            $documentFormField->setMandatory($metadataObject->getMandatory());
            $documentFormField->setBackendOnly($metadataObject->getBackendOnly());  
            $documentFormField->setConsent($metadataObject->getConsent());
            $documentFormField->setValidation($metadataObject->getValidation());
            $documentFormField->setDataType($metadataObject->getDataType());
            $documentFormField->setMaxIteration($metadataObject->getMaxIteration());     
            $documentFormField->setInputOptions($metadataObject->getInputOptionList());             
            $documentFormField->setInputField($metadataObject->getInputField());   
            $documentFormField->setFillOutService($metadataObject->getFillOutService());   
            $documentFormField->setValue($object,$metadataObject->getDefaultValue());
                                                                       
            $documentFormGroup->addItem($documentFormField);                                 
          }
        }
       
          $documentFormPage->addItem($documentFormGroup);                
        }  
      } 
      
      $documentForm->addItem($documentFormPage);            
    }
              
    
    $documentForm->setDeletedFiles($this->getDeletedFiles());
    
    $documentForm->setNewFiles($this->getNewAndUpdatedFiles());
    
    
    return $documentForm;
  }
                                    
}



?>
