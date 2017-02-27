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

    public function getObjectIdentifiers()
    {

        $query = $this->createQuery();
        $query->statement("SELECT * FROM tx_dpf_domain_model_document where object_identifier != '' and object_identifier IS NOT NULL and deleted = 0");

        $result = $query->execute();

        $objectIdentifiers = array();

        foreach ($result as $document) {
            $objectIdentifiers[$document->getObjectIdentifier()] = $document->getObjectIdentifier();
        }

        return $objectIdentifiers;
    }

    /**
     * Finds all new documents
     *
     * @return array The found Document Objects
     */
    public function getNewDocuments()
    {

        $query = $this->createQuery();

        $constraints = array();
        //$constraints[] = $query->equals('state', Document::OBJECT_STATE_NEW);
        $constraints[] = $query->equals('object_identifier', '');

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        // order by start_date -> start_time...
        $query->setOrderings(
            array('transfer_date' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    /**
     * Finds all documents in progress
     *
     * @return array The found Document Objects
     */
    public function getInProgressDocuments()
    {

        $query = $this->createQuery();

        $constraints = array();
        //$constraints[] = $query->logicalNot($query->equals('state', Document::OBJECT_STATE_NEW));
        $constraints[] = $query->like('object_identifier', 'qucosa%');

        if (count($constraints)) {
            $query->matching($query->logicalOr($constraints));
        }

        return $query->execute();
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
