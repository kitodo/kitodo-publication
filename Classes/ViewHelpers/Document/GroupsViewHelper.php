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

class GroupsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param \EWW\Dpf\Domain\Model\MetadataPage $pageType
     * @param array $groups
     *
     * @return string Rendered string
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function render($pageType, $groups)
    {

        $output = "";

        $groupTypes = $pageType->getMetadataGroup();
        $groupIterator = array();

        foreach ($groupTypes as $groupType) {

            $index = 0;
            $groupCount = count($groups[$groupType->getUid()]);

            foreach ($groups[$groupType->getUid()] as $group) {

                $groupIterator['index'] = $index;
                $groupIterator['cycle'] = $index+1;
                $groupIterator['isLast'] = $index+1 == $groupCount;

                $this->templateVariableContainer->add('groupType', $groupType);
                $this->templateVariableContainer->add('groupIterator', $groupIterator);
                $this->templateVariableContainer->add('group', $group);
                $output .= $this->renderChildren();
                $this->templateVariableContainer->remove('group');
                $this->templateVariableContainer->remove('groupIterator');
                $this->templateVariableContainer->remove('groupType');
                ++$index;
            }

        }
        return $output;
    }
}