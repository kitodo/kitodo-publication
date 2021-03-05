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

class GetStatusColorViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('status', 'string', '', true);
    }

    /**
     * Gets the related color for alias states.
     *
     * @return string
     */
    public function render()
    {
        $status = $this->arguments['status'];

        $aliasState = DocumentWorkflow::getAliasStateByLocalOrRepositoryState($status);

        switch ($aliasState) {

           case DocumentWorkflow::ALIAS_STATE_NEW:
               return 'secondary';
               break;
           case DocumentWorkflow::ALIAS_STATE_REGISTERED:
               return 'secondary';
               break;

           case DocumentWorkflow::ALIAS_STATE_IN_PROGRESS:
               return 'primary';
               break;

           case DocumentWorkflow::ALIAS_STATE_RELEASED:
               return 'success';
               break;

           case DocumentWorkflow::ALIAS_STATE_DISCARDED:
               return 'warning';
               break;

           case DocumentWorkflow::ALIAS_STATE_POSTPONED:
               return 'info';
               break;
           default:
               return 'light';
               break;
        }
    }
}
