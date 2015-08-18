<?php
namespace EWW\Dpf\Domain\Model;

class DocumentFormField extends AbstractFormElement {
 
  protected $value;
  
  protected $inputField;
  
  protected $selectOptions;
  
  protected $inputOptions;
  
  
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
    
  
  /**
   * 
   * @return array
   */
  public function getInputOptions() {    
    return $this->inputOptions;    
  }

  /**
   * 
   * @param \Eww\Dpf\Domain\Model\InputOptionList $inputOptionList
   */   
  public function setInputOptions(\Eww\Dpf\Domain\Model\InputOptionList $inputOptionList = NULL) {         
    
    $this->inputOptions = array();
    
    if ($inputOptionList) {    
        $this->inputOptions[''] = '';
        foreach ($inputOptionList->getInputOptions() as $option => $label) {
            $this->inputOptions[$option] = $label;    
        }
    }
         
  }
        
}

?>
