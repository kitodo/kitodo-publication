<?php
namespace EWW\Dpf\ViewHelpers;

class StringReplaceViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {
    /**
     * Replace the $searchFor string with $replaceString in $string
     *
     * @param $string string
     * @param $searchFor string
     * @param $replaceWith string
     * @return string
     */
    public function render($string, $searchFor, $replaceWith) {
        return str_replace($searchFor, $replaceWith, $string);
    }
}
?>