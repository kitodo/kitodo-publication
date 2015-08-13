<?php
namespace Eww\Dpf\Domain\Repository;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * The repository for SysLanguage
 */
class SysLanguageRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

        /*
        */
    
        /*
         * Better but didn't work                 
         *       
        public function initializeObject() {
            $this->defaultQuerySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
            $this->defaultQuerySettings->setRespectStoragePage(FALSE);
        }

        public function findInstalledLanguages() {
            $result = $this->createQuery();
            $result->getQuerySettings()->setRespectStoragePage(FALSE);           
            $result->statement('SELECT * FROM sys_language');           
            return $result->execute();            
	}         
        */
        
        /**
         * Finds all installed languages of the TYPO3 system (usually on pid 0).
         * 
         * @return \EWW\dpf\Domain\Model\SysLanguage | NULL
         */        
    	public function findInstalledLanguages() {
            $result = $this->createQuery();
            $result->getQuerySettings()->setRespectStoragePage(FALSE);
            $result->getQuerySettings()->setReturnRawQueryResult(TRUE);          
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
            
            return NULL;                        
	}
	                
}