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
            $documentFormField->setValue($object);
            
            $documentFormGroup->addItem($documentFormField);                                 
          }
        }
       
          $documentFormPage->addItem($documentFormGroup);                
        }  
      } 
      
      $documentForm->addItem($documentFormPage);            
    }
                           
    return $documentForm;
  }
                                    
}



?>
