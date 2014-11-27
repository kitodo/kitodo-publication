<?php
namespace EWW\Dpf\Domain\Model;

class DocumentFormField extends AbstractFormElement {
 
  protected $value;
  
  
  public function getValue() {    
    return $this->value;    
  }
  
  
  public function setValue($value) {
    $this->value = $value;    
  }
  
}

?>
