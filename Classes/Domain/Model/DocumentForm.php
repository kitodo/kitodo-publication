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
  
  public function getObjectState() {
      return $this->objectState;
  }
  
  public function setObjectState($objectState) {
      $this->objectState = $objectState;
  }
  
  public function isObjectActive() {
      return $this->objectState == \EWW\Dpf\Domain\Model\Document::OBJECT_STATE_ACTIVE;
  }
  
  public function getRemoteAction() {
      return $this->remoteAction;
  }
  
  public function setRemoteAction($remoteAction) {
      $this->remoteAction = $remoteAction;
  }
  
  public function isRemoteDelete() {
      return $this->remoteAction == \EWW\Dpf\Domain\Model\Document::REMOTE_ACTION_DELETE;
  }
  
}

?>
