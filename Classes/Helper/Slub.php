<?php
namespace EWW\Dpf\Helper;

class Slub {
  
  protected $slubDom;
  
  
  public function __construct($slubXml) {
    $this->setSlubXml($slubXml);
  }
  
  public function setSlubXml($slubXml) {
    $slubDom = new \DOMDocument();
    $slubDom->loadXML($slubXml);    
    $this->slubDom = $slubDom;
  }
  
  
  public function getSlubXml() {
    return $this->slubDom->saveXML(); 
  }
  
  
  public function getSlubXpath() {              
    return new \DOMXPath($this->slubDom);             
  }
  
  
  public function getDocumentType() {                  
    $documentTypeNode = $this->getSlubXpath()->query("/slub:info/slub:documentType");
    return $documentTypeNode->item(0)->nodeValue;                       
  }
  
}

?>
