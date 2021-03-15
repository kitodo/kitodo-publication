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
       $aliasState = DocumentWorkflow::getAliasStateByLocalOrRepositoryState($status);

        if (empty($aliasState)) {
            // The status is likely to be an alias status.
            $aliasState = $status;
        }

        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
            "manager.documentList.state.".$aliasState,
            'dpf',
            $arguments = null
        );
    }
}
