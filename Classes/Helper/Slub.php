<?php
namespace EWW\Dpf\Helper;

class Slub {
  
  protected $slubDom;
  
  
  public function __construct($slubXml) {
    $this->setSlubXml($slubXml);
  }
  
  public function setSlubXml($slubXml) {
    $slubDom = new \DOMDocument();
    if (!empty($slubXml)) {
        $slubDom->loadXML($slubXml);    
    }    
    $this->slubDom = $slubDom;
  }
  
  
  public function getSlubXml() {
    return $this->slubDom->saveXML(); 
  }
  
  
  public function getSlubXpath() {                   
    $xpath = \EWW\Dpf\Helper\XPath::create($this->slubDom);       
    return $xpath;
  }
  
  
  public function getDocumentType() {                  
    $documentTypeNode = $this->getSlubXpath()->query("/slub:info/slub:documentType");
    return $documentTypeNode->item(0)->nodeValue;                       
  }
   
  
  public function getSubmitterEmail() {                  
    $emailNode = $this->getSlubXpath()->query("/slub:info/slub:submitter/foaf:Person/foaf:mbox");                                                                     
    return $emailNode->item(0)->nodeValue;                       
  }
  
}

?>
