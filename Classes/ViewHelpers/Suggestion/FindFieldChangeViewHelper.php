<?php
namespace EWW\Dpf\ViewHelpers\Suggestion;

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

use EWW\Dpf\Services\Suggestion\FieldChange;
use EWW\Dpf\Services\Suggestion\GroupChange;

class FindFieldChangeViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('id', 'string', 'The id of the field.', true);
        $this->registerArgument('groupChange', 'mixed', 'The array of field changes.', true);
        $this->registerArgument('as', 'string', 'Name of the result variable.', true);
    }

    /**
     * @return bool
     */
    public function render()
    {
        /*
        if ($this->templateVariableContainer->exists($this->arguments['as']) === TRUE) {
            $this->templateVariableContainer->remove($this->arguments['as']);
        }

        $id = $this->arguments['id'];


        $groupChange = $this->arguments['groupChange'];

        $fieldChange = null;
        if ($groupChange instanceof GroupChange) {




            foreach ($groupChange->getFieldChanges() as $fieldChange) {

                if ($id === $fieldChange->getNewField()->getInputField()) {

                }

                $fieldChange = $groupChange->getFieldChange($id);
            }


        }

        $this->templateVariableContainer->add($this->arguments['as'], $fieldChange);

        $content = $this->renderChildren();

          */


        if ($this->templateVariableContainer->exists($this->arguments['as']) === TRUE) {
            $this->templateVariableContainer->remove($this->arguments['as']);
        }

        $id = $this->arguments['id'];

        /** @var GroupChange $groupChange  */
        $groupChange = $this->arguments['groupChange'];

        $fieldChange = null;
        if ($groupChange instanceof GroupChange) {
            $fieldChange = $groupChange->getFieldChange($id);
        }

        $this->templateVariableContainer->add($this->arguments['as'], $fieldChange);

        $content = $this->renderChildren();
    }

}
