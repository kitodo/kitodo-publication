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

use \EWW\Dpf\Security\Security;

class CreatorRoleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * frontendUserHelper
     *
     * @var \EWW\Dpf\Helper\FrontendUserHelper
     * @inject
     */
    protected $frontendUserHelper = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;

    /**
     * Shows the frontend user name of the given frontenduser user id.
     *
     * @param int $feUserId
     * @return string
     */
    public function render($feUserId)
    {
        if ($this->security->getUser()->getUid() == $feUserId) {
            return "self";
        }

        $userRole = $this->frontendUserHelper->getUserRole($feUserId);

        if ($userRole === Security::ROLE_LIBRARIAN) {
            return "librarian";
        }

        if ($userRole == Security::ROLE_RESEARCHER) {
            return "user";
        }

        return null;
    }
}
