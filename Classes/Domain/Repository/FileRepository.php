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
 * The repository for Files
 */
class FileRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

  
  
  public function getSecondaryFilesByDocument(\EWW\Dpf\Domain\Model\document $document) {
    
    $query = $this->createQuery();
    $query->matching(
      $query->logicalAnd(
        $query->equals("document", $document),
        $query->logicalNot($query->equals("status", \Eww\Dpf\Domain\Model\File::FILE_DELETED)),
        $query->logicalNot($query->equals("primary_file", TRUE))
      ));
            
    return $query->execute();    
  }

  
  public function getPrimaryFileByDocument(\EWW\Dpf\Domain\Model\document $document) {
    
    $query = $this->createQuery();
    $query->matching(
      $query->logicalAnd(
        $query->equals("document", $document),
        $query->equals("primary_file", TRUE),
        $query->logicalNot($query->equals("status", \Eww\Dpf\Domain\Model\File::FILE_DELETED))
      ));
            
    $file = $query->execute();    
    
    if ($file) {
      return $file->current();
    }
    
    return NULL;
    
  }
  
}