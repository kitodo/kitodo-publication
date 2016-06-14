<?php
namespace EWW\Dpf\ViewHelpers;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class AddResourcesViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper
{

    public function render()
    {
        $doc          = $this->getDocInstance();
        $pageRenderer = $doc->getPageRenderer();
        $extRelPath   = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath("dpf");

        $pageRenderer->addCssFile($extRelPath . "Resources/Public/css/qucosa.css");
        $pageRenderer->addCssFile($extRelPath . "Resources/Public/css/qucosabe.css");


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
