<?php
namespace EWW\Dpf\ViewHelpers;

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

class ShowDocumentCounterViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @param int $documentCount
     * @param int $currentPage
     * @param int $itemsPerPage
     * @return string
     */
    public function render($documentCount, $currentPage, $itemsPerPage)
    {
        $from = ($currentPage > 1)? (($currentPage-1) * $itemsPerPage) + 1 : $currentPage;

        if ($currentPage >= 1) {
            if ($currentPage * $itemsPerPage > $documentCount) {
                $to = $documentCount;
            } else {
                $to = $currentPage * $itemsPerPage;
            }
        } else {
            $to = $documentCount;
        }

        return $from." ".\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('manager.workspace.to', 'dpf')." "
            .$to." ".\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('manager.workspace.of', 'dpf')." "
            .$documentCount;
    }
}
