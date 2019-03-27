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
 * The repository for SysLanguage
 */
class SysLanguageRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * Finds all installed languages of the TYPO3 system (usually on pid 0).
     *
     * @return \EWW\Dpf\Domain\Model\SysLanguage | NULL
     */
    public function findInstalledLanguages()
    {
        $sysLanguages = NULL;

        foreach ($this->findAll() as $language) {
            $sysLanguages[] = $language;
        }

        return $sysLanguages;

    }

}
