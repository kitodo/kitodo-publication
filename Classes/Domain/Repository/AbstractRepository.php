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

use EWW\Dpf\Domain\Model\Client;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * The abstract repository
 */
class AbstractRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Finds all records of all clients.
     *
     * @param bool $returnRawQueryResult
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function crossClientFindAll($returnRawQueryResult = TRUE) {
        /** @var $querySettings \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings */
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
        $query = $this->createQuery();
        return $query->execute($returnRawQueryResult);
    }

    public function crossClient($active = false)
    {
        /** @var $querySettings \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings */
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(!$active);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param int $pid
     */
    public function setStoragePid(int $storagePid)
    {
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setStoragePageIds([$storagePid]);
        $this->setDefaultQuerySettings($querySettings);
    }
}
