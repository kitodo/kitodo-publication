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
 * Class ProcessNumber
 * @package EWW\Dpf\Domain\Model
 */
class ProcessNumber extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

    /**
     * ownerId
     *
     * @var string
     */
    protected $ownerId = '';

    /**
     * year
     *
     * @var int
     */
    protected $year;

    /**
     * counter
     *
     * @var int
     */
    protected $counter;


    /**
     * Returns the ownerId
     *
     * @return string $ownerId
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Sets the ownerId
     *
     * @param string $ownerId
     * @return void
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * Returns the year
     *
     * @return int $year
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Sets the year
     *
     * @param int $year
     * @return void
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * Returns the counter
     *
     * @return int $counter
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Sets the counter
     *
     * @param int $counter
     * @return void
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;
    }

    public function getProcessNumberString() {
        return strtoupper($this->getOwnerId()).'-'. $this->getYear().'-'.$this->getCounter();
    }
}