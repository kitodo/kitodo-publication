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
 * The repository for ProcessNumbers
 */
class ProcessNumberRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function initializeObject() {
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Finds the highest case number of an owner id
     * for the current year
     *
     * @var string ownerId
     * @return array The found ProcessNumber Object
     */
    public function getHighestProcessNumberByOwnerIdAndYear($ownerId,$year)
    {

        $query = $this->createQuery();

        $constraints = array();
        $constraints[] = $query->equals('owner_id', $ownerId, false);

        $constraints[] = $query->equals('year', $year);

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        $query->setOrderings(array("counter" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING));

        $result = $query->execute();

        if ($result) {
            return $result->getFirst();
        }

        return NULL;
    }

    public function startTransaction() {
        $GLOBALS['TYPO3_DB']->sql_query('START TRANSACTION');
    }

    public function commitTransaction() {
        $GLOBALS['TYPO3_DB']->sql_query('COMMIT');
    }

    public function rollbackTransaction() {
        $GLOBALS['TYPO3_DB']->sql_query('ROLLBACK');
    }

}
