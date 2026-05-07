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

    public function findByObjectIdentifier(string $objectIdentifier)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('objectIdentifier', $objectIdentifier));
        return $query->execute()->getFirst();
    }

    public function getObjectIdentifiers()
    {

        $query = $this->createQuery();

        $constraints = array(
            $query->logicalNot($query->equals('object_identifier', '')),
            $query->logicalNot($query->equals('object_identifier', null)));

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
     * Finds all documents (excluding templates)
     *
     * @return array The found Document Objects
     */
    public function getAllDocuments()
    {

        $query = $this->createQuery();

        $constraints = array(
                $query->equals('is_template', false));

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        $query->setOrderings(
            array('uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING)
        );

        return $query->execute();
    }

    /**
     * Finds all new documents
     *
     * @return array The found Document Objects
     */
    public function getNewDocuments()
    {

        $query = $this->createQuery();

        $constraints = array(
                $query->equals('object_identifier', ''),
                $query->equals('changed', false),
                $query->equals('is_template', false));

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        $query->setOrderings(
            array('uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING)
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

        $orConstraints = array(
                $query->like('object_identifier', 'qucosa%'),
                $query->equals('changed', true));

        if (count($orConstraints)) {
            $constraints[] = $query->logicalOr($orConstraints);
        }

        $andConstraints = array(
          $query->like('is_template', false));

        if (count($andConstraints)) {
            $constraints[] = $query->logicalAnd($andConstraints);
        }

        $query->matching($query->logicalAnd($constraints));

        $query->setOrderings(
            array('uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING)
        );

        return $query->execute();
    }

    /**
     * Finds all templates
     *
     * @return array The found Document Objects
     */
    public function getTemplates()
    {

        $query = $this->createQuery();

        $constraints = array(
                $query->equals('is_template', true));

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        $query->setOrderings(
            array('uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING)
        );

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
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = array();
        $constraints[] =  $query->equals('process_number', '');
        $constraints[] =  $query->equals('process_number', null);

        if (count($constraints)) {
            $query->matching($query->logicalOr($constraints));
        }

        return $query->execute();
    }

    // -- Fast list methods: raw SQL excludes xml_data / slub_info_data blobs --

    protected function listColumns()
    {
        return 'uid, pid, tstamp, crdate, deleted, hidden, sys_language_uid, l10n_parent, t3ver_oid,'
            . ' title, authors, document_type, object_identifier, reserved_object_identifier,'
            . ' state, transfer_status, changed, valid, is_template, date_issued, process_number';
    }

    protected function storagePidList()
    {
        $query = $this->createQuery();
        $pids = $query->getQuerySettings()->getStoragePageIds();
        return implode(',', array_map('intval', $pids));
    }

    protected function findForList($where)
    {
        $cols = $this->listColumns();
        $pidList = $this->storagePidList();
        $sql = 'SELECT ' . $cols
            . ' FROM tx_dpf_domain_model_document'
            . ' WHERE ' . $where . ' AND pid IN (' . $pidList . ')'
            . ' ORDER BY uid DESC';
        $query = $this->createQuery();
        return $query->statement($sql)->execute();
    }

    // All documents (excluding templates)

    public function findAllForList()
    {
        return $this->findForList('is_template = 0 AND deleted = 0 AND hidden = 0');
    }

    // New documents (no object_identifier, not changed)

    public function findNewForList()
    {
        return $this->findForList(
            "object_identifier = '' AND changed = 0 AND is_template = 0 AND deleted = 0 AND hidden = 0"
        );
    }

    // In-progress documents (submitted to Fedora or locally changed)

    public function findInProgressForList()
    {
        return $this->findForList(
            "(object_identifier LIKE 'qucosa%' OR changed = 1) AND is_template = 0 AND deleted = 0 AND hidden = 0"
        );
    }

    // Templates

    public function findTemplatesForList()
    {
        return $this->findForList('is_template = 1 AND deleted = 0 AND hidden = 0');
    }
}
