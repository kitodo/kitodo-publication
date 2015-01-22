<?php
namespace EWW\Dpf\ViewHelpers\Form;

class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper {
    
  public function initializeArguments() {
    parent::initializeArguments();
      //$this->registerArgument('staticInfoTable', 'string', 'set the tablename of the StaticInfoTable to build the Select-Tag.');
      //$this->registerArgument('staticInfoTableSubselect', 'array', '{fieldname: fieldvalue}');
      //$this->registerArgument('defaultOptionLabel', 'string', 'if set, add default option with given label');
      //$this->registerArgument('defaultOptionValue', 'string', 'if set, add default option with given label');
  }
  
  
  public function getOptions() {
    
    $options = parent::getOptions();
             
    $languageOptions = $this->objectManager->get('EWW\Dpf\Helper\LanguageOptions');
    
    if ( !$options ) {
      return array("" => "") + $languageOptions->getOptions();
    }
        
    return $options;
        
  }

    
}


?>
