<?php
namespace Eww\Dpf\Domain\Repository;

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
 * The repository for SysLanguage
 */
class SysLanguageRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * Finds all installed languages of the TYPO3 system (usually on pid 0).
     *
     * @return \EWW\dpf\Domain\Model\SysLanguage | NULL
     */
    public function findInstalledLanguages()
    {
        $result = $this->createQuery();
        $result->getQuerySettings()->setRespectStoragePage(false);
        $result->getQuerySettings()->setReturnRawQueryResult(true);
        $result->statement('SELECT l.uid,l.pid,l.title,l.flag,i.lg_iso_2 FROM sys_language as l LEFT JOIN static_languages as i ON i.uid = l.static_lang_isocode');

        if ($result->execute()) {
            foreach ($result->execute() as $language) {
                $sysLanguage = new \EWW\dpf\Domain\Model\SysLanguage();
                $sysLanguage->setUid($language['uid']);
                $sysLanguage->setPid($language['pid']);
                $sysLanguage->setTitle($language['title']);
                $sysLanguage->setFlag($language['flag']);
                $sysLanguage->setLangIsocode(strtolower($language['lg_iso_2']));

                $sysLanguages[] = $sysLanguage;
            }
            return $sysLanguages;
        }

        return null;
    }

}
