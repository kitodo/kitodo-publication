<?php
namespace EWW\Dpf\Domain\Repository;

class FrontendUserRepository extends \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
{
    /**
     * @param $feUserUid
     * @param $clientPid
     * @return bool
     */
    public function isUserInClient($feUserUid, $clientPid)
    {
        /** @var $querySettings \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings */
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
        $query = $this->createQuery();

        $constraintsAnd[] = $query->equals('uid', $feUserUid);
        $constraintsAnd[] = $query->equals('pid', $clientPid);
        $query->matching($query->logicalAnd($constraintsAnd));

        return $query->execute()->count() > 0;
    }
}
