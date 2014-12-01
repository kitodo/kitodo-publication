<?php
namespace EWW\Dpf\Domain\Model;

class DocumentFormField extends AbstractFormElement {
 
  protected $value;
  
  protected $inputField;
  
  
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
  
  
}

?>
