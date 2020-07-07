<?php
namespace EWW\Dpf\Domain\Repository;

class FrontendUserRepository extends \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
{
    // Fixme: We need a fobIdentifier field for the fe user to make these function obsolete.
    public function findByFisPersId($fobId) {
        return $this->findByFirstName($fobId);
    }
}
