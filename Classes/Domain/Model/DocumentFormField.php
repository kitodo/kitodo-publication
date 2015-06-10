<?php
namespace EWW\Dpf\Domain\Model;

class DocumentFormField extends AbstractFormElement {
 
  protected $value;
  
  protected $inputField;
  
  protected $selectOptions;
  
  public function getValue() {                   
    return $this->value;    
  }
  
  
  public function setValue($value) {        
    $this->value = $value;    
  }
  
  
  public function getInputField() {    
    return $this->inputField;    
  }
  
  
  public function setInputField($inputField) {
    $this->inputField = $inputField;    
  }
  
  public function getSelectOptions() {
      return $this->selectOptions;     
  }
  
  public function setSelectOptions($selectOptions) {
     $this->selectOptions = $selectOptions;     
  }
      
}

?>
