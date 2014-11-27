<?php
namespace EWW\Dpf\Domain\Model;

class DocumentForm extends AbstractFormElement {
  
  protected $documentUid;
  
  
  public function getDocumentUid() {
    return $this->documentUid;    
  }

  
  public function setDocumentUid($documentUid) {
    $this->documentUid = $documentUid;    
  }
  
}

?>
