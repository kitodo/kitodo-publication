<?php
namespace EWW\Dpf\Domain\Repository;

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
 * The repository for editing lock objects
 */
class EditingLockRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{
    /**
     * Finds all outdated locks,
     *
     * @param integer $timeout : Time interval (in seconds) in which locks are not outdated.
     * @return array The found Document Objects
     */
    public function findOutdatedLocks($timeout)
    {
        $query = $this->createQuery();

        $dateTimeObj= new \DateTime();
        $dateTimeObj->sub(new \DateInterval("PT".$timeout."S"));

        $query->matching(
            $query->lessThan('tstamp', $dateTimeObj->getTimestamp())
        );

        return $query->execute();
    }

}
