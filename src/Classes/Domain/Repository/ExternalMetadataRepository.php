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
 * The repository for to be imported external metadata
 */
class ExternalMetadataRepository extends \EWW\Dpf\Domain\Repository\AbstractRepository
{
    /**
     * Find external metadata by a frontend user uid and a publication identifier.
     *
     * @param int $feUser
     * @param string $identifier
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findOneByUserAndPublicationIdentifier($feUser, $identifier)
    {
        $query = $this->createQuery();

        $constraints = array();
        $constraints[] = $query->equals('publication_identifier', $identifier);
        $constraints[] = $query->equals('fe_user', $feUser);

        $query->matching($query->logicalAnd($constraints));
        return $query->execute()->getFirst();
    }

}
