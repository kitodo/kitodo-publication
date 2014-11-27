<?php
namespace EWW\Dpf\Helper;

class FormField {
  
  protected $pageUid;
  protected $groupUid;
  protected $groupIndex;
  protected $fieldUid;
  protected $fieldIndex;
  
  
  protected $value;
     
  public function __construct($id,$value) {        
    
    $id = explode('-',$id);        
    $this->pageUid = array_shift($id); 
    $this->groupUid = array_shift($id); 
    $this->groupIndex = array_shift($id); 
    $this->fieldUid = array_shift($id); 
    $this->fieldIndex = array_shift($id);         
    
    $this->value = $value;
  }
  
  public function getPageUid() {    
    return $this->pageUid;        
  }

  public function getGroupUid() {    
    return $this->groupUid;        
  }

  public function getGroupIndex() {    
    return $this->groupIndex;        
  }
  
  public function getFieldUid() {    
    return $this->fieldUid;        
  }
  
  public function getFieldIndex() {    
    return $this->fieldIndex;        
  }
  
  public function getValue() {    
    return $this->value;        
  }
}




?>
