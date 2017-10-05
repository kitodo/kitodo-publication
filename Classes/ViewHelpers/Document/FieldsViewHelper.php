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

class FieldsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param \EWW\Dpf\Domain\Model\MetadataGroup $groupType
     * @param  array $group
     *
     * @return string Rendered string
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function render($groupType, $group)
    {
        $output = "";

        $fieldTypes = $groupType->getMetadataObject();

        $fieldIterator = array();

        foreach ($fieldTypes as $fieldType) {
            $index = 0;
            $fieldCount = count($group[$fieldType->getUid()]);

            if (!key_exists($fieldType->getUid(),$group)) {
                $group[$fieldType->getUid()][] = "";
            }

            foreach ($group[$fieldType->getUid()] as $field) {
                $fieldIterator['index'] = $index;
                $fieldIterator['cycle'] = $index+1;
                $fieldIterator['isLast'] = $index+1 == $fieldCount;

                $this->templateVariableContainer->add('fieldType', $fieldType);
                $this->templateVariableContainer->add('fieldIterator', $fieldIterator);
                $this->templateVariableContainer->add('field', $field);
                $output .= $this->renderChildren();
                $this->templateVariableContainer->remove('field');
                $this->templateVariableContainer->remove('fieldIterator');
                $this->templateVariableContainer->remove('fieldType');
                ++$index;
            }

        }
        return $output;
    }
}