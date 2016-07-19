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

/**
 * The repository for Files
 */
class FileRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function getSecondaryFilesByDocument(\EWW\Dpf\Domain\Model\document $document)
    {

        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals("document", $document),
                $query->logicalNot($query->equals("status", \EWW\Dpf\Domain\Model\File::STATUS_DELETED)),
                $query->logicalNot($query->equals("primary_file", true))
            ));

        return $query->execute();
    }

    public function getPrimaryFileByDocument(\EWW\Dpf\Domain\Model\document $document)
    {

        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals("document", $document),
                $query->equals("primary_file", true),
                $query->logicalNot($query->equals("status", \EWW\Dpf\Domain\Model\File::STATUS_DELETED))
            ));

        $file = $query->execute();

        if ($file->count() > 0) {
            return $file->current();
        }

        return null;

    }

}
