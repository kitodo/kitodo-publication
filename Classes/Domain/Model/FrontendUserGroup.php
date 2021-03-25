<?php
namespace EWW\Dpf\Domain\Model;

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

/**
* Frontend user group
*/
class FrontendUserGroup extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup
{
    /**
     * Gets the kitodo role
     *
     * @var string
     */
    protected $kitodoRole = '';

    /**
     * contains group ids which are allowed to access
     * @var string
     */
    protected $accessToGroups = '';

    public function getKitodoRole()
    {
        return $this->kitodoRole;
    }

    /**
     * Sets the kitodo role
     *
     * @param $kitodoRole
     */
    public function setKitodoRole($kitodoRole)
    {
        $this->kitodoRole = $kitodoRole;
    }

    /**
     * @return string
     */
    public function getAccessToGroups(): string
    {
        return $this->accessToGroups;
    }

    /**
     * @param string $accessToGroups
     */
    public function setAccessToGroups(string $accessToGroups): void
    {
        $this->accessToGroups = $accessToGroups;
    }



}