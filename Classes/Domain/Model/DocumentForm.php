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
  
}

?>
