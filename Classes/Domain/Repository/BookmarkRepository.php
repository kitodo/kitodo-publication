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
use \EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use \EWW\Dpf\Security\Security;

/**
 * The repository for Bookmarks
 */
class BookmarkRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{
    /**
     * @param int $owner
     * @param string $identifier
     * @return object
     */
    public function findBookmark($owner, $identifier)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('document_identifier', $identifier),
                $query->equals('owner_uid', $owner)
            )
        );

        return $query->execute()->getFirst();
    }

}
