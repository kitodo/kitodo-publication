<?php
namespace EWW\Dpf\ViewHelpers;

class IsElementAllowedViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper
{

    /**
     *
     * @param boolean $condition
     * @return string
     */
    public function render($condition)
    {
        if ((TYPO3_MODE === 'BE') || !$condition) {
            return $this->renderThenChild();
        }
        return $this->renderElseChild();
    }

}
