<?php
namespace EWW\Dpf\ViewHelpers;

class ActiveManagerMenuViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {
  
    
  /**
   *      
   * @param string $controllerName The controller to be active.  
   */
  public function render($controllerName) {            

      //$this->controllerContext->getRequest()->getControllerActionName()
      
      if ($this->controllerContext->getRequest()->getControllerName() == $controllerName) {
          return 'active';
      }
      
      return '';
  }    
    
}

?>
