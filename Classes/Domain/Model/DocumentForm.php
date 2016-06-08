<?php
namespace EWW\Dpf\Domain\Model;

class DocumentForm extends AbstractFormElement {
  
  /**
   *
   * @var integer
   */  
  protected $documentUid;
  
  
  /**
   *
   * @var boolean
   */  
  protected $virtual;
  
  
  /**
   *
   * @var string
   */  
  protected $qucosaId;
  
    
  /**
   *
   * @var string
   */  
  protected $qucosaUrn;
  
   
  /**
   *
   * @var \EWW\Dpf\Domain\Model\File
   */
  protected $primaryFile;
  
  
  /**
   *
   * @var array
   */
  protected $secondaryFiles;
  
  
  /**
   *
   * @var array
   */
  protected $deletedFiles;
  
  
  /**
   *
   * @var array
   */
  protected $newFiles;
  
  
  /**
   * 
   * @var string
   */
  protected $objectState;
  
  
  /**
   * 
   * @var boolean
   */
  protected $deleteDisabled;
          
  /**
   * 
   * @var boolean
   */
  protected $saveDisabled;
  
  /**
   *
   * @var boolean
   */
  protected $valid = FALSE;
    
  /**
   * 
   * @return integer
   */
  public function getDocumentUid() {
    return $this->documentUid;    
  }

  /**
   * 
   * @param integer $documentUid
   */
  public function setDocumentUid($documentUid) {
    $this->documentUid = $documentUid;    
  }
  
  /**
   * 
   * @return boolean
   */
  public function getVirtual() {
    return $this->virtual;    
  }

  /**
   * 
   * @param boolean $virtual
   */
  public function setVirtual($virtual) {
    $this->virtual = $virtual;    
  }
  
  
  /**
   * 
   * @return string
   */
  public function getQucosaId() {
    return $this->qucosaId;    
  }

  /**
   * 
   * @param string $qucosaId
   */
  public function setQucosaId($qucosaId) {
    $this->qucosaId = $qucosaId;    
  }
  
  
  /**
   * 
   * @return string
   */
  public function getQucosaUrn() {
    return $this->qucosaUrn;    
  }

  /**
   * 
   * @param string $qucosaUrn
   */
  public function setQucosaUrn($qucosaUrn) {
    $this->qucosaUrn = $qucosaUrn;    
  }
  
  /**
   * 
   * @param type \EWW\Dpf\Domain\Model\File $primaryFile
   */
  public function setPrimaryFile($primaryFile) {
    $this->primaryFile = $primaryFile;
  }
  
  
  /**
   * 
   * @return \EWW\Dpf\Domain\Model\File
   */
  public function getPrimaryFile() {
    return $this->primaryFile;    
  }
      
  
  public function setSecondaryFiles($secondaryFiles) {
    $this->secondaryFiles = $secondaryFiles;    
  } 
  
  
  public function getSecondaryFiles() {
    return $this->secondaryFiles;    
  }
  
  
  public function getDeletedFiles() {
    return $this->deletedFiles; 
  }
  
  public function setDeletedFiles($deletedFiles) {
    $this->deletedFiles = $deletedFiles;
  }
  
  
  public function getNewFiles() {
    return $this->newFiles; 
  }
  
  public function setNewFiles($newFiles) {
    $this->newFiles = $newFiles;
  }
   
  public function getDeleteDisabled() {
      return $this->deleteDisabled;
  }
  
  public function setDeleteDisabled($deleteDisabled) {
      $this->deleteDisabled = $deleteDisabled;
  }

  public function getSaveDisabled() {
      return $this->saveDisabled;
  }
  
  public function setSaveDisabled($saveDisabled) {
      $this->saveDisabled = $saveDisabled;
  }
  
  public function getValid() {
      return $this->valid;
  }
  
  public function setValid($valid) {
      $this->valid = $valid;
  }

  public function getNewFileNames() {
    $fileNames = array();    
    foreach ($this->getNewFiles() as $file) {
      $fileNames[] = $file->getTitle();     
    }
    return $fileNames;
  }
  
}


?>
