<?php
namespace EWW\Dpf\Domain\Model;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
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
 * Document
 */
class Document extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

  
        /**
	 * crdate
	 *
	 * @var DateTime
	 */
	protected $crdate;    
                        
        
        /**
	 * tstamp
	 *
	 * @var DateTime
	 */
	protected $tstamp;    
        

        /**
	 * title
	 *
	 * @var string
	 */
	protected $title = '';
        
        
        /**
	 * authors
	 *
	 * @var string
	 */
	protected $authors = '';
        
  
	/**
	 * xmlData
	 *
	 * @var string
	 */
	protected $xmlData = '';

        
        /**
	 * slubInfoData
	 *
	 * @var string
	 */
	protected $slubInfoData = '';
        
               
	/**
	 * documentType
	 *
	 * @var \EWW\Dpf\Domain\Model\DocumentType
	 */
	protected $documentType = NULL;
                
        /**
         * objectIdentifier
         * 
         * @var string         
         */
        protected $objectIdentifier;     
        
        
        /**
         * reseredObjectIdentifier
         * 
         * @var string         
         */
        protected $reservedObjectIdentifier;     
        
        
        /**
         * state
         * 
         * @var string         
         */
        protected $state = self::OBJECT_STATE_NEW;  
        
        
        /**
         * transferStatus
         * 
         * @var string
         */
        protected $transferStatus;               
                
        /**
         *  transferDate
         * 
         *  @var integer
         */
        protected $transferDate;    
                                     
        /**
         * changed
         * 
         * @var boolean
         */
        protected $changed = FALSE;
        
        /**
         * valid
         * 
         * @var boolean
         */
        protected $valid = FALSE;

        
        /**
         *
         * @var string $dateIssued
         */
        protected $dateIssued;
        
        
        /**
	 * file
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File>
	 * @cascade remove
	 */
	protected $file = NULL;
        
                      
        const TRANSFER_ERROR = "ERROR";        
        const TRANSFER_QUEUED = "QUEUED";                     
        const TRANSFER_SENT = "SENT";
                                       
        const OBJECT_STATE_NEW = "NEW";                
        const OBJECT_STATE_ACTIVE = "ACTIVE";        
        const OBJECT_STATE_INACTIVE = "INACTIVE";
        const OBJECT_STATE_DELETED = "DELETED";
        const OBJECT_STATE_LOCALLY_DELETED = "LOCALLY_DELETED";
        
        /**
	 * Returns the title
	 *
	 * @return string $title
	 */
	public function getTitle() {
		return $this->title;
	}
                
        /**
	 * Sets the title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {          
                $this->title = $title; 
                //htmlspecialchars_decode($title,ENT_QUOTES);
	}
        
        
        /**
	 * Returns the authors
	 *
	 * @return array $authors
	 */
	public function getAuthors() {
            return array_map('trim',explode(";",$this->authors));
	}
                
        /**
	 * Sets the authors
	 *
	 * @param array $authors
	 * @return void
	 */
	public function setAuthors($authors) {
                $authors = implode("; ",$authors);		                
                $this->authors = $authors;
                //htmlspecialchars_decode($authors,ENT_QUOTES);
	}
        
	/**
	 * Returns the xmlData
	 *
	 * @return string $xmlData
	 */
	public function getXmlData() {                                  
		return $this->xmlData;
	}
                
	/**
	 * Sets the xmlData
	 *
	 * @param string $xmlData
	 * @return void
	 */
	public function setXmlData($xmlData) {
            $this->xmlData = $xmlData;
	}
        
        
        /**
	 * Returns the slubInfoData
	 *
	 * @return string $slubInfoData
	 */
	public function getSlubInfoData() {                                  
		return $this->slubInfoData;
	}
        
        
        /**
	 * Sets the slubInfoData
	 *
	 * @return string $slubInfoData
	 */
	public function setSlubInfoData($slubInfoData) {                                  
		$this->slubInfoData = $slubInfoData;
	}
        

	/**
	 * Returns the documentType
	 *
	 * @return \EWW\Dpf\Domain\Model\DocumentType $documentType
	 */
	public function getDocumentType() {
		return $this->documentType;
	}

	/**
	 * Sets the documentType
	 *
	 * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
	 * @return void
	 */
	public function setDocumentType(\EWW\Dpf\Domain\Model\DocumentType $documentType) {
		$this->documentType = $documentType;
	}
        
        /**
         * Returns the crdate
         * 
         * @return DateTime
         */        
        public function getCrdate() {
          return $this->crdate;
        }
        
        /**
         * Returns the objectIdentifier
         * 
         * @return string
         */
        public function getObjectIdentifier() {
          return $this->objectIdentifier;          
        }
        
        /**
         * Sets the objectIdentifier
         * 
         * @param string $objectIdentifier
         * @return void
         */
        public function setObjectIdentifier($objectIdentifier) {
          $this->objectIdentifier = $objectIdentifier;          
        }
        
        /**
         * Returns the reservedObjectIdentifier
         * 
         * @return string
         */
        public function getReservedObjectIdentifier() {
          return $this->reservedObjectIdentifier;          
        }
        
        /**
         * Sets the reservedObjectIdentifier
         * 
         * @param string $reservedObjectIdentifier
         * @return void
         */
        public function setReservedObjectIdentifier($reservedObjectIdentifier) {
          $this->reservedObjectIdentifier = $reservedObjectIdentifier;          
        }
                
        /**
         * Returns the state
         * 
         * @return string
         */
        public function getState() {
          return $this->state;          
        }
        
        /**
         * Sets the state
         * 
         * @param string $state
         * @return void
         */
        public function setState($state) {
          $this->state = $state;          
        }
                                                                       
        /**         
         * Returns the transferStatus
         * @var string
         */
        public function getTransferStatus() {
          return $this->transferStatus; 
        }               
                
        /**         
         * Sets the transferStatus
         * 
         * @param string
         * @return void
         */
        public function setTransferStatus($transferStatus) {
          $this->transferStatus = $transferStatus; 
        }               
                
        /**         
         * Returns the transferDate
         * 
         * @var integer
         */
        public function getTransferDate() {
          return $this->transferDate; 
        }               
                
        /**         
         * Sets the transferDate 
         * 
         * @param integer $transferDate
         * @return void
         */
        public function setTransferDate($transferDate) {
          $this->transferDate = $transferDate; 
        }               
                
        /**         
         * Returns the transferErrorCode
         * 
         * @var integer
         */
        public function getTransferErrorCode() {
          return $this->transferErrorCode; 
        }               
                
        /**         
         * Sets the transferErrorCode
         * 
         * @param integer $transferErrorCode
         * @return void
         */
        public function setTransferErrorCode($transferErrorCode) {
          $this->transferErrorCode = $transferErrorCode; 
        }               
                                 
        /**         
         * Returns the transferResponse
         * 
         * @var string
         */
        public function getTransferResponse() {
          return $this->transferResponse; 
        }               
                
        /**         
         * Sets the transferResponse
         * 
         * @param string $transferResponse
         * @return void
         */
        public function setTransferResponse($transferResponse) {
          $this->transferResponse = $transferResponse; 
        }               
        
        /**         
         * Returns the transferHttpStatus
         * 
         * @var integer
         */
        public function getTransferHttpStatus() {
          return $this->transferHttpStatus; 
        }               
                
        /**         
         * Sets the transferHttpStatus
         * 
         * @param integer $transferHttpStatus
         * @return void
         */
        public function setTransferHttpStatus($transferHttpStatus) {
          $this->transferHttpStatus = $transferHttpStatus; 
        } 
        
        
        /**
	 * Adds a File
	 *
	 * @param \EWW\Dpf\Domain\Model\File $file
	 * @return void
	 */
	public function addFile(\EWW\Dpf\Domain\Model\File $file) {                              
		$this->file->attach($file);
	}
         
	/**
	 * Removes a File
	 *
	 * @param \EWW\Dpf\Domain\Model\File $fileToRemove The File to be removed
	 * @return void
	 */
	public function removeFile(\EWW\Dpf\Domain\Model\File $fileToRemove) {
		$this->file->detach($fileToRemove);
	}

	/**
	 * Returns the file
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File> $file
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Sets the file
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File> $file
	 * @return void
	 */
	public function setFile(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $file) {
		$this->file = $file;
	}


    public function getFileData()
    {
        
        $fileId = new \EWW\Dpf\Services\Transfer\FileId($this);

        $files = array();
              
        if (is_a($this->getFile(),'\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {
          foreach ($this->getFile() as $file) {
 
                $fileStatus = $file->getStatus();

                //if (!empty($fileStatus)) {
                                
                    $tmpFile = array(
                        'path' => $file->getLink(),
                        'type' => $file->getContentType(),
                        'title' => (($file->getLabel())? $file->getLabel() : $file->getTitle()),                        
                        'download' => $file->getDownload(),
                        'archive' => $file->getArchive(),                              
                        'use' => '',                            
                        'id' => NULL,
                        'hasFLocat' => ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_ADDED)
                     );
                    
                    $grpUSE = ($file->getDownload())? 'download' : 'original';
                    
                    if ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_DELETED) {              
                      $dataStreamIdentifier = $file->getDatastreamIdentifier();                           
                      if (!empty($dataStreamIdentifier)) {
                        $tmpFile['id'] = $file->getDatastreamIdentifier();                           
                        $tmpFile['use'] = 'DELETE';
                        $files[$grpUSE][$file->getUid()] = $tmpFile;
                      }                       
                    } else {
                      $tmpFile['id'] = $fileId->getId($file);  
                      $tmpFile['use'] = ($file->getArchive())? 'ARCHIVE' : '';
                      $files[$grpUSE][$file->getUid()] = $tmpFile;
                    }
                    
                //}
                
            }
        }

        return $files;

    }
    
    
    
    public function getCurrentFileData()
    {
        
        $fileId = new \EWW\Dpf\Services\Transfer\FileId($this);

        $files = array();
              
        if (is_a($this->getFile(),'\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {
          foreach ($this->getFile() as $file) {
 
                $fileStatus = $file->getStatus();
                                    
                    $tmpFile = array(
                        'path' => $file->getLink(),
                        'type' => $file->getContentType(),
                        'title' => (($file->getLabel())? $file->getLabel() : $file->getTitle()),                        
                        'download' => $file->getDownload(),
                        'archive' => $file->getArchive(),                              
                        'use' => '',                            
                        'id' => NULL,
                        'hasFLocat' => ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_ADDED)
                     );
                               
                    $grpUSE = ($file->getDownload())? 'download' : 'original';
                    
                    if ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_DELETED) {                       
                      $dataStreamIdentifier = $file->getDatastreamIdentifier();                           
                      if (!empty($dataStreamIdentifier)) {
                        $tmpFile['id'] = $file->getDatastreamIdentifier();                           
                        $tmpFile['use'] = 'DELETE';
                        $files[$grpUSE][$file->getUid()] = $tmpFile;
                      }                       
                    } else {
                      $tmpFile['id'] = $fileId->getId($file);                        
                      $tmpFile['use'] = ($file->getArchive())? 'ARCHIVE' : '';
                      $files[$grpUSE][$file->getUid()] = $tmpFile;
                    }
                                             
            }
        }

        return $files;

    }
        
        public function isEdited() {                                       
          return $this->crdate->getTimestamp() != $this->tstamp->getTimestamp();
        }
                            
        /**
         * Returns the tstamp
         * 
         * @return DateTime
         */        
        public function getTstamp() {
          return $this->tstamp;
        }
        
        
        /**
	 * Returns the changed
	 *
	 * @return string $changed
	 */
	public function getChanged() {
		return $this->changed;
	}
                
        /**
	 * Sets the changed
	 *
	 * @param string $changed
	 * @return void
	 */
	public function setChanged($changed) {          
                $this->changed = $changed; 
	}
        
        /**
	 * Returns the valid
	 *
	 * @return string $valid
	 */
	public function getValid() {
		return $this->valid;
	}
                
        /**
	 * Sets the valid
	 *
	 * @param string $valid
	 * @return void
	 */
	public function setValid($valid) {          
                $this->valid = $valid; 
	}
        
        
        public function getDateIssued() {                                                      
            return $this->dateIssued;                          
        }
                 
        public function setDateIssued($dateIssued) {                                                      
            $this->dateIssued = $dateIssued;                        
        }
                       
        public function isDeleteAllowed() {
          return ($this->state == self::OBJECT_STATE_INACTIVE ||
                 $this->state == self::OBJECT_STATE_ACTIVE) &&
                 !empty($this->objectIdentifier);                  
        }
                
        public function isActive() {
            return $this->state == self::OBJECT_STATE_ACTIVE ||
                   $this->state == self::OBJECT_STATE_NEW ||
                   ($this->state != self::OBJECT_STATE_INACTIVE &&
                    $this->state != self::OBJECT_STATE_DELETED &&
                    $this->state != self::OBJECT_STATE_LOCALLY_DELETED);        
        }       
        
        public function isActivationChangeAllowed() {
          return $this->state == self::OBJECT_STATE_INACTIVE ||
                 $this->state == self::OBJECT_STATE_ACTIVE;                  
        }
        
        public function isDeleteRemote() {
          return $this->state == self::OBJECT_STATE_LOCALLY_DELETED;                  
        }
        
        public function isRestoreRemote() {
          return $this->state == self::OBJECT_STATE_DELETED;
        }
        
        public function isActivateRemote() {
          return $this->state == self::OBJECT_STATE_INACTIVE;
        }

        public function isInactivateRemote() {
            return $this->state == self::OBJECT_STATE_ACTIVE;
        }

        public function isIngestRemote() {
            return $this->state == self::OBJECT_STATE_NEW &&
                   empty($this->objectIdentifier);
        }
        
        public function isUpdateRemote() {
            return ($this->state == self::OBJECT_STATE_ACTIVE ||
                   $this->state == self::OBJECT_STATE_INACTIVE) &&                              
                   !empty($this->objectIdentifier);
        }
                                                      
        public function getIsNew() {        
            return empty($this->objectIdentifier);                
        }
}
