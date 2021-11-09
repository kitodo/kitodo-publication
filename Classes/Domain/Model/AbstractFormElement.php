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

class AbstractFormElement extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     *
     * @var integer
     */
    protected $uid;

    /**
     *
     * @var string
     */
    protected $displayName;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var array
     */
    protected $items;

    /**
     *
     * @var string
     */
    protected $mandatory;

    /**
     * An array of roles to which access to the form element should be restricted.
     *
     * @var array
     */
    protected $accessRestrictionRoles = array();

    /**
     *
     * @var integer
     */
    protected $maxIteration;

    public function getUid()
    {
        return $this->uid;
    }

    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getItems()
    {
        if (is_array($this->items)) {
            return $this->items;
        } else {
            return [];
        }
    }

    public function addItem($item)
    {
        $uid                 = $item->getUid();
        $this->items[$uid][] = $item;
    }

    public function removeItem($item)
    {
        $uid = $item->getUid();

        foreach ($this->items[$uid] as $key => $value) {
            if ($item->getId() === $value->getId()) {
                unset($this->items[$uid][$key]);
            }
        }
    }

    public function replaceItem($item)
    {
        $uid = $item->getUid();

        foreach ($this->items[$uid] as $key => $value) {
            if ($item->getId() === $value->getId()) {
                $this->items[$uid][$key] = $item;
            }
        }
    }

    public function getMandatory()
    {
        return $this->mandatory;
    }

    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    public function getAccessRestrictionRoles()
    {
        return $this->accessRestrictionRoles;
    }

    public function setAccessRestrictionRoles($accessRestrictionRoles)
    {
        $this->accessRestrictionRoles = $accessRestrictionRoles;
    }

    public function getMaxIteration()
    {
        return $this->maxIteration;
    }

    public function setMaxIteration($maxIteration)
    {
        $this->maxIteration = $maxIteration;
    }

}
