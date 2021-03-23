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
     * @param Document|string $document
     * @param int|null $feUserUid
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function removeBookmark($document, $feUserUid)
    {
        $query = $this->createQuery();

        if ($document instanceof Document) {
            // A document can be identified (documentIdentifier) by its Fedora PID or the document UID in case it hasn't been
            // published (that means it exits only locally in the TYPO3  db).
            // In order to find a bookmark that belongs to a document, it is essential to search for both identifiers.
            $constraintsAnd[] = $query->logicalOr(
                $query->equals('document_identifier', $document->getObjectIdentifier()),
                $query->equals('document_identifier', $document->getUid())
            );
        } else {
            // In case $document already contains a plain identifier the above distinction is not necessary.
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
     * @param Document|string $document
     * @param int $feUserUid
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function addBookmark($document, $feUserUid)
    {
        if ($document instanceof Document) {
            // The returned documentIdentifier is either a PID or the document UID (TYPO3 db), see also
            // the above method removeBookmark().
            $identifier = $document->getDocumentIdentifier();
        } else {
            // In case $document already contains a plain identifier we can use it directly.
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
