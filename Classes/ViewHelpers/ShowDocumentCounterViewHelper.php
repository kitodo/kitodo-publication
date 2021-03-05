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

class ShowDocumentCounterViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('documentCount', 'array',
            'The number of documents.', true
        );
        $this->registerArgument('currentPage', 'array',
            'The current page uid.', true
        );
        $this->registerArgument('itemsPerPage', 'array',
            'The number of items per page.', true
        );
    }

    /**
     * @return string|null
     */
    public function render()
    {
        $documentCount = $this->arguments['documentCount'];
        $currentPage = $this->arguments['currentPage'];
        $itemsPerPage = $this->arguments['itemsPerPage'];

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

        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
            'manager.workspace.documentCounter',
            'dpf',
            [$from, $to, $documentCount]
        );

    }
}
