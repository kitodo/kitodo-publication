<?php
namespace EWW\Dpf\Helper;

class DocumentFormValidator {
    
  protected $documentType;    
  protected $error;
  
  public function __constructor($documentType) {    
    $this->documentType = $documentType;    
  }
  

  public function validate($formData) {        
    
    if ($this->preValidate($formData)) {
      
      
      
      foreach ($formData['metadata']['p'] as $pageUid => $page) {
        
        
        
        
        
        
      }
      
      
      
      
      return TRUE;
    }
          
    return FALSE;    
  }
  
  
  public function preValidate($formData) {        
    if (!key_exists('metadata', $formData)) {        
      return FALSE;      
    }        
      
    if (!key_exists('p', $formData['metadata'])) {                                        
      return FALSE;      
    }    
    
    if (sizeof($formData['metadata']['p']) < 1) {
      return FALSE;      
    }
     
    foreach ($pages as $pageUid => $page) {
                
      if (!key_exists('g',$page)) {
        return FALSE;      
      }
      
      if (sizeof($page['g']) < 1) {
        return FALSE;      
      }
              
    }
          
    return TRUE;    
  }

  
  private function debug($value,$die = FALSE) {
    
    echo "<pre>";
    var_dump($value);  
    echo "</pre>";   

    if ($die) die();
    
  }
  
  
}

?>


