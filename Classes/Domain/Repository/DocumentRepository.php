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

use \EWW\Dpf\Domain\Workflow\DocumentWorkflow;

/**
 * The repository for Documents
 */
class DocumentRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{

    /**
     *
     * Finds all documents filtered by owner uid for user role librarian
     *
     * @param int $ownerUid
     * @param array $stateFilters
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findAllOfALibrarian($ownerUid, $stateFilters = array())
    {
        $query = $this->createQuery();
        $constraintsOr = array();

        $constraintsOr[] = $query->logicalAnd(
            array(
                $query->equals('state', DocumentWorkflow::STATE_NEW_NONE),
                $query->equals('owner', $ownerUid)
            )
        );

        $constraintsOr[] = $query->logicalAnd(
            $query->logicalNot(
                $query->equals('state', DocumentWorkflow::STATE_NEW_NONE)
            )
        );

        if ($stateFilters) {
            $query->matching(
                $query->logicalAnd(
                    $query->logicalOr($constraintsOr),
                    $query->in('state', $stateFilters)
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
     * @param array $stateFilters
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findAllOfAResearcher($ownerUid, $stateFilters = array())
    {
        $query = $this->createQuery();

        $constraintsAnd = array(
            $query->equals('owner', $ownerUid)
        );

        if ($stateFilters) {
            $constraintsAnd[] = $query->in('state', $stateFilters);
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
