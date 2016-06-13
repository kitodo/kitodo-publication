<?php
namespace EWW\Dpf\ViewHelpers;

class InArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * @param mixed $needle The searched value
     * @param mixed $array The array
     */
    public function render($needle, $array)
    {
        return in_array($needle, $array);
    }

}
