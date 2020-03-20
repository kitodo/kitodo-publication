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

use EWW\Dpf\Domain\Model\Bookmark;
use EWW\Dpf\Domain\Model\Document;
use \EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use \EWW\Dpf\Security\Security;

/**
 * The repository for Bookmarks
 */
class BookmarkRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{
    /**
     * @param int $feUserUid
     * @param string $identifier
     * @return object
     */
    public function findBookmark($feUserUid, $identifier)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('document_identifier', $identifier),
                $query->equals('fe_user_uid', $feUserUid)
            )
        );

        return $query->execute()->getFirst();
    }

    /**
     * @param mixed $document
     * @param int|null $feUserUid
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function removeBookmark($document, $feUserUid)
    {
        $query = $this->createQuery();

        if ($document instanceof Document) {
            $constraintsAnd[] = $query->logicalOr(
                $query->equals('document_identifier', $document->getObjectIdentifier()),
                $query->equals('document_identifier', $document->getUid())
            );
        } else {
            $constraintsAnd[] = $query->equals('document_identifier', $document);
        }

        $constraintsAnd[] = $query->equals('fe_user_uid', $feUserUid);

        $query->matching($query->logicalAnd($constraintsAnd));

        $queryResult = $query->execute();

        /** @var Bookmark @$bookmark */
        foreach ($queryResult as $bookmark) {
            $this->remove($bookmark);
        }

        return $queryResult->count() > 0;
    }

    /**
     * @param int $feUserUid
     * @param Document|string $document
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function addBookmark($feUserUid, $document)
    {
        if ($document instanceof Document) {
            $identifier = $document->getDocumentIdentifier();
        } else {
            $identifier = $document;
        }

        $bookmark = $this->findBookmark($feUserUid, $identifier);
        if (!$bookmark) {
            $bookmark = new Bookmark();
            $bookmark->setDocumentIdentifier($identifier);
            $bookmark->setFeUserUid($feUserUid);
            $this->add($bookmark);
            return true;
        }

        return false;
    }

}
