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

use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 * The repository for DocumentTypes
 */
class DocumentTypeRepository extends AbstractRepository
{
    public function getDocumentTypesAlphabetically()
    {
        $documentTypes = $this->findAll();

        $data = array();
        $docTypes = array();
        $name = array();
        $type = array();

        foreach ($documentTypes as $docType) {
            $data[] = array(
                "name" => $docType->getDisplayName(),
                "type" => $docType,
            );
        }

        foreach ($data as $key => $row) {
            $name[$key] = $row['name'];
            $type[$key] = $row['type'];
        }

        array_multisort($name, SORT_ASC, SORT_LOCALE_STRING, $type, SORT_ASC, $data);

        foreach ($data as $item) {
            $docTypes[] = $item['type'];
        }

        return $docTypes;
    }

    /**
     * @param string $type
     * @param string $externalTypesDbColumn
     * @return object
     */
    public function findOneByExternalType($type, $externalTypesDbColumn)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->like($externalTypesDbColumn, '%"'.$type.'"%')
        );

        return $query->execute()->getFirst();
    }

}
