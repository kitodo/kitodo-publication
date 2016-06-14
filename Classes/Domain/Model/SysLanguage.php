<?php
namespace Eww\Dpf\Domain\Model;

/**
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

class SysLanguage extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * uid
     *
     * @var integer
     */
    protected $uid;

    /**
     * pid
     *
     * @var integer
     */
    protected $pid;

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * flag
     *
     * @var string
     */
    protected $flag = '';

    /**
     * langIsocode
     *
     * @var string
     */
    protected $langIsocode = '';

    /**
     * Returns the uid
     *
     * @return string $uid
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Sets the uid
     *
     * @param string $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * Returns the pid
     *
     * @return string $pid
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Sets the pid
     *
     * @param string $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the flag
     *
     * @return string $flag
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * Sets the flag
     *
     * @param string $flag
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    /**
     * Returns the langIsocode
     *
     * @return string $langIsocode
     */
    public function getLangIsocode()
    {
        return $this->langIsocode;
    }

    /**
     * Sets the langIsocode
     *
     * @param string $langIsocode
     */
    public function setLangIsocode($langIsocode)
    {
        $this->langIsocode = $langIsocode;
    }
}
