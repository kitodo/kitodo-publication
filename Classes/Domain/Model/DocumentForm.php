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
   * @var \Eww\Dpf\Domain\Model\File
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
   * @param type \Eww\Dpf\Domain\Model\File $primaryFile
   */
  public function setPrimaryFile($primaryFile) {
    $this->primaryFile = $primaryFile;
  }
  
  
  /**
   * 
   * @return \Eww\Dpf\Domain\Model\File
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
}

?>
