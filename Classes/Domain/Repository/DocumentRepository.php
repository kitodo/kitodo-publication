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
        $constraintsAnd = array();

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

        $constraintsAnd[] = $query->logicalOr($constraintsOr);

        if ($stateFilters) {
            $constraintsAnd[] = $query->in('state', $stateFilters);
        }

        $constraintsAnd[] = $query->equals('suggestion', false);

        $constraintsAnd[] = $query->equals('temporary', FALSE);
        $query->matching($query->logicalAnd($constraintsAnd));

        $query->setOrderings(
            array('transfer_date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    public function findAllLibrarianDocumentSuggestions($ownerUid) {
        $query = $this->createQuery();

        $query->matching(
            $query->equals('suggestion', true)
        );

        return $query->execute();
    }

    public function findAllResearcherDocumentSuggestions($ownerUid) {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                array(
                    $query->equals('suggestion', true),
                    $query->equals('owner', $ownerUid)
                )
            )
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
            $query->equals('owner', $ownerUid),
            $query->equals('suggestion', 0)
        );

        if ($stateFilters) {
            $constraintsAnd[] = $query->in('state', $stateFilters);
        }

        $constraintsAnd[] = $query->equals('temporary', FALSE);
        $query->matching($query->logicalAnd($constraintsAnd));

        $query->setOrderings(
            array('transfer_date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    /**
     * @param boolean $temporary
     * @return array
     */
    public function getObjectIdentifiers($temporary = FALSE)
    {
        $query = $this->createQuery();

        $constraints = array(
            $query->logicalNot($query->equals('object_identifier', '')),
            $query->logicalNot($query->equals('object_identifier', NULL)));

        if (count($constraints)) {
            $constraints[] = $query->logicalAnd(
                $query->logicalNot(
                    $query->logicalOr(
                        $query->equals('temporary', TRUE),
                        $query->equals('suggestion', TRUE)
                    )
                )
            );
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
            $query->matching(
                $query->logicalAnd(
                    $query->equals('temporary', FALSE),
                    $query->logicalOr($constraints)
                )
            );
        }

        return $query->execute();
    }

    /**
     * Finds all outdated temporary documents,
     *
     * @param integer $timeout
     * @return array The found Document Objects
     */
    public function findOutdatedTemporaryDocuments($timeout = 3600)
    {
        $query = $this->createQuery();

        $dateTimeObj= new \DateTime();
        $dateTimeObj->sub(new \DateInterval("PT".$timeout."S"));

        $constraints = array();
        $constraints[] = $query->lessThan('tstamp', $dateTimeObj->getTimestamp());

        $query->matching(
            $query->logicalAnd(
                $query->equals('temporary', TRUE),
                $query->logicalOr($constraints)
            )
        );

        return $query->execute();
    }

    /**
     * Finds all outdated locked documents,
     *
     * @param integer $timeout
     * @return array The found Document Objects
     */
    public function findOutdatedLockedDocuments($timeout = 3600)
    {
        $query = $this->createQuery();

        $dateTimeObj= new \DateTime();
        $dateTimeObj->sub(new \DateInterval("PT".$timeout."S"));

        $constraints = array();
        $constraints[] = $query->lessThan('tstamp', $dateTimeObj->getTimestamp());

        $query->matching(
            $query->logicalAnd(
                $query->logicalNot($query->equals('editor_uid', 0)),
                $query->logicalOr($constraints)
            )
        );

        return $query->execute();
    }


    /**
     * @param string $objectIdentifier
     * @return array
     */
    public function findWorkingCopyByObjectIdentifier($objectIdentifier)
    {
        $query = $this->createQuery();

        $constraints = array(
            $query->equals('object_identifier', $objectIdentifier),
            $query->logicalNot($query->equals('temporary', TRUE)),
            $query->logicalNot($query->equals('suggestion', TRUE))
        );

        $query->matching($query->logicalAnd($constraints));

        return $query->execute()->getFirst();
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function findWorkingCopy($identifier)
    {
        $query = $this->createQuery();

        if (is_integer($identifier)) {
            $constraints = [
                $query->equals('uid', $identifier)
            ];
        } else {
            $constraints = [
                $query->equals('object_identifier', $identifier)
            ];
        }

        $constraints[] = $query->logicalNot($query->equals('temporary', TRUE));
        $constraints[] = $query->logicalNot($query->equals('suggestion', TRUE));

        $query->matching($query->logicalAnd($constraints));

        return $query->execute()->getFirst();
    }

}
