<?php
namespace EWW\Dpf\ViewHelpers;

class AddResourcesViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {
   
    
    public function render() {
        $doc = $this->getDocInstance();
        $pageRenderer = $doc->getPageRenderer();
        $extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath("dpf");

        $pageRenderer->addCssFile($extRelPath . "Resources/Public/css/qucosa.css");
        $pageRenderer->addCssFile($extRelPath . "Resources/Public/css/qucosabe.css");
        
        //$pageRenderer->addCssFile($extRelPath . "Resources/Public/css/bootstrap.min.css");

        //$pageRenderer->loadJquery();
        $pageRenderer->addJsFile($extRelPath . "Resources/Public/JavaScript/jQuery.min.js");
        $pageRenderer->addJsFile($extRelPath . "Resources/Public/JavaScript/jquery.tools.min.js");
        $pageRenderer->addJsFile($extRelPath . "Resources/Public/JavaScript/qucosa.js");

        $output = $this->renderChildren();
        $output = $doc->startPage("title") . $output;
        $output .= $doc->endPage();

        return $output;
        

    return "test";
    }
}
?>
