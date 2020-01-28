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

use EWW\Dpf\Domain\Model\Document;
use \EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use \EWW\Dpf\Security\Security;

/**
 * The repository for Documents
 */
class DocumentRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{
    /**
     * Finds all suggestion documents of the given user role filtered by owner uid
     *
     * @param string $role : The kitodo user role (Security::ROLE_LIBRARIAN, Security::ROLE_RESEARCHER)
     * @param int $ownerUid
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllDocumentSuggestions($role, $ownerUid) {
        $query = $this->createQuery();

        switch ($role) {

            case Security::ROLE_LIBRARIAN:
                $query->matching(
                    $query->equals('suggestion', true)
                );
                break;

            case Security::ROLE_RESEARCHER:
                $query->matching(
                    $query->logicalAnd(
                        array(
                            $query->equals('suggestion', true),
                            $query->equals('owner', $ownerUid)
                        )
                    )
                );
                break;
        }
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
     * @param integer $timeout : Time interval (in seconds) in which documents are not outdated.
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
     * @param integer $timeout : Time interval (in seconds) in which documents are not outdated.
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
     * @return Document
     */
    public function findByIdentifier($identifier)
    {
        $query = $this->createQuery();

        if (is_numeric($identifier)) {
            $constraints = [
                $query->equals('uid', $identifier)
            ];
        } else {
            $constraints = [
                $query->equals('object_identifier', $identifier)
            ];
        }

        $query->matching($query->logicalAnd($constraints));

        return $query->execute()->getFirst();
    }

    /**
     * @param string $identifier
     * @param bool $includeTemporary
     * @param bool $includeSuggestion
     * @return Document
     */
    public function findWorkingCopy($identifier, $includeTemporary = false, $includeSuggestion = false)
    {
        $query = $this->createQuery();

        if (is_numeric($identifier)) {
            $constraints = [
                $query->equals('uid', $identifier)
            ];
        } else {
            $constraints = [
                $query->equals('object_identifier', $identifier)
            ];
        }

        $constraints[] = $query->equals('temporary', $includeTemporary);
        $constraints[] = $query->equals('suggestion', $includeSuggestion);

        $query->matching($query->logicalAnd($constraints));

        return $query->execute()->getFirst();
    }

}
