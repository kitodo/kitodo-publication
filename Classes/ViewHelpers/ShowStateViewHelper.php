<?php
namespace EWW\Dpf\ViewHelpers;

class ShowStateViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param string $state
     *
     */
    public function render($state)
    {

        $key = "";

        switch ($state) {
            case 'A':
                $key = 'search.resultList.state.active';
                break;
            case 'I':
                $key = 'search.resultList.state.inactive';
                break;
            case 'D':
                $key = 'search.resultList.state.deleted';
                break;
            default:
                return "-";
                break;
        }

        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $arguments = null);
    }
}
