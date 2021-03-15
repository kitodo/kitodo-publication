<?php
namespace EWW\Dpf\ViewHelpers\Security;

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

class KitodoRoleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
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
     * @return string
     */
    public function render()
    {
        return $this->security->getUserRole();
    }
}
