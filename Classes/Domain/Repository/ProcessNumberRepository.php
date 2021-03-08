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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 * The repository for ProcessNumbers
 */
class ProcessNumberRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function initializeObject() {
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
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

    /**
     * Get the connection for the process number table
     *
     * @return Connection
     */
    protected function getConnection()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(
            "tx_dpf_domain_model_processnumber"
        );
    }

    /**
     * Start transaction
     */
    public function startTransaction() {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function commitTransaction() {
        $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function rollbackTransaction() {
        $this->getConnection()->rollBack();
    }

}
