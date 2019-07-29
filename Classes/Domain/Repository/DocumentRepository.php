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

/**
 * The repository for Documents
 */
class DocumentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    const DOCUMENT_STATE_NEW = 'DOCUMENT_STATE_NEW';
    const DOCUMENT_STATE_IN_PROGRESS = 'DOCUMENT_STATE_IN_PROGRESS';

    /**
     * /**
     * Finds all documents filtered by owner uid and document state.
     * If all parameters are empty, all documents will be returned.
     *
     * @param int $ownerUid
     * @param string $documentState
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findAllFiltered($ownerUid = NULL, $documentState = NULL)
    {
        $query = $this->createQuery();
        $constraintsAnd = array();

        if ($documentState) {
            switch ($documentState) {
                case self::DOCUMENT_STATE_NEW:
                    // Finds all new documents
                    $constraintsAnd[] = $query->equals('object_identifier', '');
                    $constraintsAnd[] = $query->equals('changed', false);
                    $query->setOrderings(
                        array('transfer_date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
                    );
                    break;
                case self::DOCUMENT_STATE_IN_PROGRESS:
                    // Finds all documents in progress
                    $constraintsAnd[] = $query->logicalOr(
                        array(
                            $query->like('object_identifier', 'qucosa%'),
                            $query->equals('changed', true)
                        )
                    );
                    break;
            }
        }

        if ($ownerUid) {
            $constraintsAnd[] = $query->equals('owner', $ownerUid);
        }

        if (!empty($constraintsAnd)) {
            $query->matching($query->logicalAnd($constraintsAnd));
        }

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
