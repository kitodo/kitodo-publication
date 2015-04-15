<?php
namespace EWW\Dpf\ViewHelpers\Form;


class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper {
    
  public function initializeArguments() {
    parent::initializeArguments();          
    $this->registerArgument('selectType', 'string','Type of the select field', FALSE);      
  }
  
   
  public function getOptions() {
    
    $options = parent::getOptions();
    
    $selectType = $this->arguments['selectType'];
                 
    $languageHelper = $this->objectManager->get('EWW\Dpf\Helper\LanguageHelper');
    
    if ( $selectType == 'language' ) {                       
      return array('' => '') + $languageHelper->getOptions();
    }
        
    return $options;
        
  }

    
}


?>
