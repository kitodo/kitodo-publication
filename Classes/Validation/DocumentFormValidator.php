<?php
namespace EWW\Dpf\Validation;

   class DocumentFormValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
    {
    /**
    * action isValid
    *   
    * @param string $value
    * @param string $errormessage
    * @return void
    */	
    public function isValid($value) {
      
      
      var_dump($value); die();
      
      
      $this->errors = array();
      //if(empty(trim($value))) {
       $this->addError("test");
      //  return false;
      //}
        //return true;
      }
    }
    
?>
