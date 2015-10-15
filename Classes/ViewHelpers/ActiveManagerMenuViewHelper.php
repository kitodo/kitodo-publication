<?php
namespace EWW\Dpf\ViewHelpers;

class ActiveManagerMenuViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {
  
    
  /**
   *      
   * @param string $controllerName The controller to be active.  
   * @param string $actionName The action to be active.     
   */
  public function render($controllerName, $actionName='') {            

      //$this->controllerContext->getRequest()->getControllerActionName()
      
      if ($this->controllerContext->getRequest()->getControllerName() == $controllerName) {
          
          if (empty($actionName)) {
            return 'active';   
          } elseif ($this->controllerContext->getRequest()->getControllerActionName() == $actionName) {          
            return 'active';
          } else {
            return '';  
          }
      }
      
      return '';
  }    
    
}

?>
