<?php
namespace EWW\Dpf\Helper;

class DocumentFormValidator {
  
  
  /**
   * documentTypeRepository
   * 
   * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
   * @inject
   */
  protected $documentTypeRepository = NULL;     
        
  
  /**
   * MetadataObjectRepository
   * 
   * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
   * @inject
   */
  protected $metadataObjectRepository = NULL;     
  
  
  /**
   * MetadataGroupRepository
   * 
   * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
   * @inject
   */
  protected $metadataGroupRepository = NULL;     
  
    
  protected $documentType;    
  
  protected $error;
  
  protected $formData;
  
  
  
  public function setDocumentType($documentType) {    
    $this->documentType = $documentType;          
  }
  
  public function setFormData($formData) {        
    $this->formData = $formData;             
  }
  
  
  public function validate() {        
    
    $result = TRUE;
    
    if ($this->preValidate()) {            
                                     
    }
          
    return $result;    
  }
  
  
  public function preValidate() {        
    if (!key_exists('metadata', $this->formData)) {        
      return FALSE;      
    }        
      
    if (!key_exists('p', $this->formData['metadata'])) {                                        
      return FALSE;      
    }    
    
    if (sizeof($this->formData['metadata']['p']) < 1) {
      return FALSE;      
    }
     
    foreach ($this->formData['metadata']['p'] as $pageUid => $page) {
                
      if (!key_exists('g',$page)) {
        return FALSE;      
      }
      
      if (sizeof($page['g']) < 1) {
        return FALSE;      
      }
              
    }
          
    return TRUE;    
  }

  
  
  protected function validateMandatoryFields() {
    
    $result = TRUE;
    
    
     
    
     
     
     
     return $result;
  }
  
  
  
  protected function validateAttributes() {
    
    $result = TRUE;
    
    $groups = $this->getGroups();    
    foreach ($groups as $groupUid => $group) {
                       
      $attributeFieldUids = $this->getAttributeFieldUidsByGroup($groupUid);
      
      $check = array(); 
      $dublicateAttributes = array();
      
      foreach ($group as $groupIndex => $fields) {      
        
        $attributeValues = array();
        
        foreach ($attributeFieldUids as $attributeFieldUid) {
          $attributeValues[] = $fields['f'][$attributeFieldUid][0];                    
        }
                
        $checkKey = implode('-',$attributeValues);              
        if (key_exists($checkKey,$check)) { 
          $result = $result && FALSE;
          $dublicateAttributes[$groupIndex] = array(
              'groupUid' => $groupUid,
              'groupIndex' => $groupIndex,
              'fieldUids' =>  $attributeFieldUids                                                           
          );
        } else {
          $check[$checkKey] = $groupIndex;                              
        }
                                                   
      }
      
      if ($dublicateAttributes) $this->error[] = $dublicateAttributes;
            
    }

    return $result;
  } 

  
  
  protected function getAttributeFieldUidsByGroup($groupUid) {
        
    $group = $this->metadataGroupRepository->findByUid($groupUid);
    
    $fields = $group->getMetadataObject();
        
    foreach ($fields as $field) {            
      $mapping = $field->getRelativeMapping();                 
      if (strpos($mapping, "@") === 0) {        
        $attributeFields[] = $field->getUid();                
      }                          
    }
    
    return $attributeFields;        
  }
  
  


  protected function getGroups() {      
    $groups = array();     
    foreach ($this->formData['metadata']['p'] as $pageUid => $page) {
      foreach ($page['g'] as $groupUid => $group) {
        $groups[$groupUid] = $group;
      }
    }
    return $groups;
  } 




  private function debug($value,$die = FALSE) {
    
    echo "<pre>";
    var_dump($value);  
    echo "</pre>";   

    if ($die) die();
    
  }
  
  
}

?>


