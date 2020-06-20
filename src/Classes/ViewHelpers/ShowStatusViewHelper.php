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

use EWW\Dpf\Domain\Workflow\DocumentWorkflow;

class ShowStatusViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Maps the internal states to more user friendly and localized state names.
     *
     * @param string $status
     * @return string
     */
    public function render($status)
    {
        // A,I and D are the states returned by a repository search.
        // The other states are the ones used in the document table.
        $statusMapping = [
            "A" => 'released',
            "I" => 'postponed',
            "D" => 'discarded',
            DocumentWorkflow::STATE_NEW_NONE => "new",
            DocumentWorkflow::STATE_REGISTERED_NONE => "registered",
            DocumentWorkflow::STATE_POSTPONED_NONE => "postponed",
            DocumentWorkflow::STATE_DISCARDED_NONE => "discarded",
            DocumentWorkflow::STATE_IN_PROGRESS_NONE => "in_progress",
            DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE => "in_progress",
            DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE => "in_progress",
            DocumentWorkflow::STATE_IN_PROGRESS_DELETED => "in_progress",
            DocumentWorkflow::STATE_NONE_ACTIVE => "released",
            DocumentWorkflow::STATE_NONE_INACTIVE => "postponed",
            DocumentWorkflow::STATE_NONE_DELETED => "discarded",
        ];


        if (array_key_exists($status, $statusMapping)) {
            $aliasState = $statusMapping[$status];
        } else {
            $aliasState = $status;
        }

        if ($aliasState) {
            return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                "manager.documentList.state.".$aliasState,
                'dpf',
                $arguments = null
            );
        }

        return "";
    }
}
