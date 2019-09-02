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

use \EWW\Dpf\Domain\Model\Document;
use \EWW\Dpf\Domain\Model\LocalDocumentStatus;
/**
 * The repository for Documents
 */
class DocumentRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{
    /**

     * /**
     * Finds all documents filtered by owner uid and local document status.
     * If all parameters are empty, all documents will be returned.
     *
     * @param int $ownerUid
     * @param array $excludeLocalStatuses
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findAllFiltered($ownerUid = NULL, $excludeLocalStatuses = array())
    {
        $query = $this->createQuery();
        $constraintsAnd = array();

        if (is_array($excludeLocalStatuses) && !empty($excludeLocalStatuses)) {
            foreach ($excludeLocalStatuses as $excludeLocalStatus) {
                $constraintsAnd[] = $query->logicalNot(
                    $query->equals('localStatus', $excludeLocalStatus)
                );
            }
        }

        if ($ownerUid) {
            $constraintsAnd[] = $query->equals('owner', $ownerUid);
        }

        if (!empty($constraintsAnd)) {
            $query->matching($query->logicalAnd($constraintsAnd));
        }

        $query->setOrderings(
            array('transfer_date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    /**
     *
     * Finds all documents filtered by owner uid for user role librarian
     *
     * @param int $ownerUid
     * @param string $localStatusFilter
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findAllOfALibrarian($ownerUid, $localStatusFilter = NULL)
    {
        $query = $this->createQuery();
        $constraintsOr = array();

        $constraintsOr[] = $query->logicalAnd(
            array(
                $query->equals('localStatus', LocalDocumentStatus::NEW),
                $query->equals('owner', $ownerUid)
            )
        );

        $constraintsOr[] = $query->logicalAnd(
            $query->logicalNot(
                $query->equals('localStatus', LocalDocumentStatus::NEW)
            )
        );

        if ($localStatusFilter) {
            $query->matching(
                $query->logicalAnd(
                    $query->logicalOr($constraintsOr),
                    $query->equals('localStatus', $localStatusFilter)
                )
            );
        } else {
            $query->matching($query->logicalOr($constraintsOr));
        }

        $query->setOrderings(
            array('transfer_date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    /**
     *
     * Finds all documents filtered by owner uid for user role researcher
     *
     * @param int $ownerUid
     * @param string $localStatusFilter
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findAllOfAResearcher($ownerUid, $localStatusFilter = NULL)
    {
        $query = $this->createQuery();

        $constraintsAnd = array(
            $query->equals('owner', $ownerUid),
            $query->logicalNot(
                $query->equals('localStatus', LocalDocumentStatus::DELETED)
            )
        );

        if ($localStatusFilter) {
            $constraintsAnd[] =   $query->equals('localStatus', $localStatusFilter);
        }

        $query->matching($query->logicalAnd($constraintsAnd));

        $query->setOrderings(
            array('transfer_date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }


    public function getObjectIdentifiers()
    {

        $query = $this->createQuery();

        $constraints = array(
            $query->logicalNot($query->equals('object_identifier', '')),
            $query->logicalNot($query->equals('object_identifier', NULL)));

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        $result = $query->execute();

        $objectIdentifiers = array();

        foreach ($result as $document) {
            $objectIdentifiers[$document->getObjectIdentifier()] = $document->getObjectIdentifier();
        }

        return $objectIdentifiers;
    }

    /**
     * Finds all documents without a process number,
     * storagePID will be ignored.
     *
     * @return array The found Document Objects
     */
    public function findDocumentsWithoutProcessNumber()
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(FALSE);

        $constraints = array();
        $constraints[] =  $query->equals('process_number', '');
        $constraints[] =  $query->equals('process_number', NULL);

        if (count($constraints)) {
            $query->matching($query->logicalOr($constraints));
        }

        return $query->execute();
    }

}
