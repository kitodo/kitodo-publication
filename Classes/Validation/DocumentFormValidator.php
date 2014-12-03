<?php
namespace EWW\Dpf\Validation;

   class DocumentFormValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
    {
    /**
    * action isValid
    *   
    * @param string $value    
    * @return boolean
    */	
    public function isValid($value) {
      
      
   //  var_dump("sdfsdf"); die();
      
      
      $this->errors = array();
      //if(empty(trim($value))) {
      $this->addError("Qucosa Fehler"); //,NULL,array(),"Test");
      return false;
      //}
        //return true;
      }
    }
    
?>
