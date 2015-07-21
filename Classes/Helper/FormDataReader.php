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
   * 
   * @param array $formData
   */
  public function setFormData($formData) {
    $this->formData = $formData;             
    $this->documentType = $this->documentTypeRepository->findByUid($formData['type']);
  }
  
  
  protected function getFields() {     
    foreach ($this->formData['metadata'] as $key => $value) {      
      $formField = new \EWW\Dpf\Helper\FormField($key,$value);      
      $fields[] = $formField;            
    }
    
    return $fields;    
  }
  
  
  protected function getDeletedFiles() {     
    foreach ($this->formData['deleteFile'] as $key => $value) {        
      
      $file = $this->fileRepository->findByUid($value);
      
      // Deleting the primary file is not allowed. 
      if (!$file->isPrimaryFile()) {
        $deletedFiles[] = $file;                                   
      }
            
    }
    
    return $deletedFiles;    
  }
  
  
  protected function getNewAndUpdatedFiles() {
    
    $uploadPath = "uploads/tx_dpf/";
    
    $basePath = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
    
    $port = '';
    
    if ($_SERVER['SERVER_PORT'] && intval($_SERVER['SERVER_PORT']) != 80) {
        $port = ':'.$_SERVER['SERVER_PORT'];
    } 
        
    $basePath .= trim($_SERVER['SERVER_NAME'],"/").$port."/".$uploadPath;
        
    $fullUploadPath = PATH_site . $uploadPath;
    
    
    $newFiles = array();
    
    // Primary file                 
    if ($this->formData['primaryFile'] && $this->formData['primaryFile']['error'] != 4) {
      
      // Use the existing file entry
      $document = $this->documentRepository->findByUid($this->formData['documentUid']);
      if ($document) {
        $file = $this->fileRepository->getPrimaryFileByDocument($document);
      }  
      if (empty($file)) {              
        $file = $this->objectManager->get('EWW\Dpf\Domain\Model\File');              
      }
                  
      $tmpFile = $this->formData['primaryFile'];
                  
      $fileName = uniqid(time(),true);    
                          
      if (\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($tmpFile['tmp_name'],$fullUploadPath.$fileName) ) {            
        $file->setContentType($tmpFile['type']);  
        $file->setTitle($tmpFile['name']);
        $file->setLink($basePath.$fileName);
        $file->setPrimaryFile(TRUE);
                  
        if ($file->getDatastreamIdentifier()) {
          $file->setStatus( \Eww\Dpf\Domain\Model\File::STATUS_CHANGED);
        } else {
          $file->setStatus( \Eww\Dpf\Domain\Model\File::STATUS_ADDED);
        }  
      
        $newFiles[] = $file;
      } else {       
        die("File didn't upload: ".$tmpFile['name']);
      } 
            
    }
     
           
    // Secondary files
    foreach ($this->formData['secondaryFiles'] as $tmpFile ) {
      
      if ($tmpFile['error'] != 4) {
      
        $file = $this->objectManager->get('EWW\Dpf\Domain\Model\File');
           
        $fileName = uniqid(time(),true);
               //  die($fileName);                      
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($tmpFile['tmp_name'],$fullUploadPath.$fileName) ) {                    
           $file->setContentType($tmpFile['type']);  
           $file->setTitle($tmpFile['name']);
           $file->setLink($basePath.$fileName);
           $file->setPrimaryFile(FALSE);
           $file->setStatus( \Eww\Dpf\Domain\Model\File::STATUS_ADDED);

           $newFiles[] = $file;
        } else {
          die("File didn't upload: ".$tmpFile['name']);
        }                                                                      
      }
    }
    
    return $newFiles;
    
  }
  
  
  
  public function getDocumentForm() { 
   
    $fields = $this->getFields();
                        
    $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();      
    $documentForm->setUid($this->documentType->getUid());    
    $documentForm->setDisplayName($this->documentType->getDisplayName());
    $documentForm->setName($this->documentType->getName());
    $documentForm->setDocumentUid($this->formData['documentUid']);
    
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
      
      foreach ($page as $groupUid => $groupItem ) {           
        foreach ($groupItem as $group ) {   
          $metadataGroup = $this->metadataGroupRepository->findByUid($groupUid);        
          $documentFormGroup = new \EWW\Dpf\Domain\Model\DocumentFormGroup();
          $documentFormGroup->setUid($metadataGroup->getUid());
          $documentFormGroup->setDisplayName($metadataGroup->getDisplayName());
          $documentFormGroup->setName($metadataGroup->getName());
                 
        foreach ($group as $objectUid => $objectItem ) {     
          foreach ($objectItem as $objectItem => $object ) {  
            $metadataObject = $this->metadataObjectRepository->findByUid($objectUid);                               
            $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();                        
            $documentFormField->setUid($metadataObject->getUid());
            $documentFormField->setDisplayName($metadataObject->getDisplayName());
            $documentFormField->setName($metadataObject->getName());
            $documentFormField->setInputField($metadataObject->getInputField());                                               
            $documentFormField->setValue($object);
                       
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
