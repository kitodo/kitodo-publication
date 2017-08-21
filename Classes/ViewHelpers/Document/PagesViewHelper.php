<?php
namespace EWW\Dpf\ViewHelpers\Document;

/*
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

class PagesViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     *
     * @return string Rendered string
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function render($document)
    {
        $output = "";

        $pageTypes = $document->getDocumentType()->getMetadataPage();
        $index = 0;

        foreach ($pageTypes as $pageType) {
            $this->templateVariableContainer->add('groups', $document->getMetadata());
            $this->templateVariableContainer->add('pageType', $pageType);
            $output .= $this->renderChildren();
            $this->templateVariableContainer->remove('pageType');
            $this->templateVariableContainer->remove('groups');
            ++$index;
        }
        return $output;
    }
}