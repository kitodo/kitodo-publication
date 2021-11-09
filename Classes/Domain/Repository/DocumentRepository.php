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
use \EWW\Dpf\Services\Identifier\Identifier;

/**
 * The repository for Documents
 */
class DocumentRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{
    /**
     * Finds all suggestion documents of the given user role filtered by creator feuser uid
     *
     * @param string $role : The kitodo user role (Security::ROLE_LIBRARIAN, Security::ROLE_RESEARCHER)
     * @param int $creatorUid
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllDocumentSuggestions($role, $creatorUid)
    {
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
                            $query->equals('creator', $creatorUid)
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
    public function getObjectIdentifiers($temporary = false)
    {
        $query = $this->createQuery();

        $constraints = array(
            $query->logicalNot($query->equals('object_identifier', '')),
            $query->logicalNot($query->equals('object_identifier', null))
        );

        if (count($constraints)) {
            $constraints[] = $query->logicalAnd(
                $query->logicalNot(
                    $query->logicalOr(
                        $query->equals('temporary', true),
                        $query->equals('suggestion', true)
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
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = array();
        $constraints[] = $query->equals('process_number', '');
        $constraints[] = $query->equals('process_number', null);

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd(
                    $query->equals('temporary', false),
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

        $dateTimeObj = new \DateTime();
        $dateTimeObj->sub(new \DateInterval("PT" . $timeout . "S"));

        $constraints = array();
        $constraints[] = $query->lessThan('tstamp', $dateTimeObj->getTimestamp());

        $query->matching(
            $query->logicalAnd(
                $query->equals('temporary', true),
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

        $dateTimeObj = new \DateTime();
        $dateTimeObj->sub(new \DateInterval("PT" . $timeout . "S"));

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
            $query->logicalNot($query->equals('temporary', true)),
            $query->logicalNot($query->equals('suggestion', true))
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

        if (Identifier::isUid($identifier)) {
            $constraints = [
                $query->equals('uid', $identifier)
            ];
        } elseif (Identifier::isFedoraPid($identifier)) {
            $constraints = [
                $query->logicalAnd(
                    $query->equals('object_identifier', $identifier),
                    $query->equals('suggestion', false)
                )
            ];
        } elseif (Identifier::isProcessNumber($identifier)) {
            $constraints = [
                $query->logicalAnd(
                    $query->equals('process_number', $identifier),
                    $query->equals('suggestion', false)
                )
            ];
        } else {
            return null;
        }

        $query->setOrderings(array("tstamp" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING));
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


    /**
     * @param object $modifiedObject The modified object
     * @throws \Exception
     */
    public function update($modifiedObject)
    {
        /** @var Document $document */
        $document = $modifiedObject;

        if (trim($document->getObjectIdentifier()) && !$document->isTemporary() && !$document->isSuggestion()) {
            $query = $this->createQuery();
            $constraints[] = $query->equals('object_identifier', trim($document->getObjectIdentifier()));
            $constraints[] = $query->equals('temporary', false);
            $constraints[] = $query->equals('suggestion', false);
            $query->matching($query->logicalAnd($constraints));

            /** @var Document $workingCopy */
            foreach ($query->execute() as $workingCopy) {
                if ($workingCopy->getUid() !== $document->getUid()) {
                    throw new \Exception(
                        "Working copy for " . $document->getObjectIdentifier() . " already exists."
                    );
                }
            }
        }

        parent::update($document);
    }


    public function updateCreator()
    {

        $query = $this->createQuery();
        $query->statement("update tx_dpf_domain_model_document set creator = owner");
        $query->execute();

    }

    /**
     * Finds all records with embargo date
     * @return mixed
     */
    public function crossClientEmbargoFindAll()
    {
        /** @var $querySettings \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings */
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                array(
                    $query->logicalNot(
                        $query->equals('embargo_date', 0)
                    )
                )
            )
        );
        return $query->execute();
    }

    /**
     * @param string $identifier
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function searchForIdentifier(string $identifier)
    {
        $identifier = str_replace("*", "%", $identifier);

        $query = $this->createQuery();

        $constraints[] =
            $query->logicalAnd(
                $query->like('uid', $identifier),
                $query->equals('suggestion', false)
            );

        $constraints[] =
            $query->logicalAnd(
                $query->like('object_identifier', $identifier),
                $query->equals('suggestion', false)
            );

        $constraints[] =
            $query->logicalAnd(
                $query->like('process_number', $identifier),
                $query->equals('suggestion', false)
            );

        $query->setOrderings(array("tstamp" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING));
        $query->matching($query->logicalOr($constraints));

        return $query->execute();
    }

    /**
     * @param Document $document
     */
    public function findSuggestionByDocument(Document $document)
    {
        $query = $this->createQuery();

        $query->setOrderings(array("tstamp" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING));
        $query->matching($query->equals('linked_uid', $document->getUid()));

        return $query->execute()->getFirst();
    }
}
