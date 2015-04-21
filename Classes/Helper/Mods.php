<?php
namespace EWW\Dpf\Helper;

class Mods {
  
  protected $modsDom;
  
  
  public function __construct($modsXml) {
    $this->setModsXml($modsXml);         
  }
 
  
  public function setModsXml($modsXml) {
    $modsDom = new \DOMDocument();
    $modsDom->loadXML($modsXml);     
    $this->modsDom = $modsDom;
  }
  
  
  public function getModsXml() {
    return $this->modsDom->saveXML();
  }
  
    
  public function getModsXpath() {               
    return new \DOMXPath($this->modsDom);             
  }
  
  
  public function getTitle() {       
   $titleNode = $this->getModsXpath()->query('/mods:mods/mods:titleInfo[@usage="primary"]/mods:title');
   
   if ($titleNode->length == 0) {
     $titleNode = $this->getModsXpath()->query("/mods:mods/mods:titleInfo/mods:title");    
   }
    return $titleNode->item(0)->nodeValue;   
  }
  
  
  public function getAuthors() {   
    $xpath = $this->getModsXpath();
    
    $authorNode = $xpath->query('/mods:mods/mods:name[mods:role/mods:roleTerm[@type="code"]="aut"]');    

    $authors = array();
                         
    foreach ($authorNode as $author) {                                
                  
      $familyNodes = $xpath->query('mods:namePart[@type="family"]',$author);            
        
      $givenNodes = $xpath->query('mods:namePart[@type="given"]',$author);                             
             
      $name = array();
      
      if ($givenNodes->length > 0) {
        $name[] = $givenNodes->item(0)->nodeValue;
      }
      
      if ($familyNodes->length > 0) {
        $name[] = $familyNodes->item(0)->nodeValue;
      }
                  
      $authors[] = implode(", ",$name);           
    }      
    
    return implode(", ",$authors);                   
  }
  
}

?>
