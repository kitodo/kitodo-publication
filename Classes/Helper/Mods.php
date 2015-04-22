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
                         
    foreach ($authorNode as $key => $author) {                                
                  
      $familyNodes = $xpath->query('mods:namePart[@type="family"]',$author);            
        
      $givenNodes = $xpath->query('mods:namePart[@type="given"]',$author);                             
             
      $name = array();
                  
      if ($givenNodes->length > 0) {
        $name[] = $givenNodes->item(0)->nodeValue;
      }
      
      if ($familyNodes->length > 0) {
        $name[] = $familyNodes->item(0)->nodeValue;
      }
                  
      $authors[$key] = implode(" ",$name);           
    }      
    
    return $authors;                   
  }
  
  public function setDateIssued($date) {       
    
    $originInfo = $this->getModsXpath()->query('/mods:mods/mods:originInfo[@eventType="distribution"]');  
    
    if ($originInfo->length > 0) {
       $dateIssued = $this->getModsXpath()->query('mods:dateIssued[@encoding="iso8601"][@keyDate="yes"]',$originInfo->item(0));            
    
       if ($dateIssued->length == 0) {          
           $newDateIssued = $this->modsDom->createElement('mods:dateIssued');
           $newDateIssued->setAttribute('encoding','iso8601');     
           $newDateIssued->setAttribute('keyDate','yes'); 
           $newDateIssued->nodeValue = $date;
           $originInfo->item(0)->appendChild($newDateIssued);  
       } else {           
           $dateIssued->item(0)->nodeValue = $date;           
       }
       
    } else {
        
        $rootNode = $this->getModsXpath()->query('/mods:mods');  
        
        if ($rootNode->length == 1) {        
            $newOriginInfo = $this->modsDom->createElement('mods:originInfo');            
            $newOriginInfo->setAttribute('eventType','distribution');            
            $rootNode->item(0)->appendChild($newOriginInfo);
        
            $newDateIssued = $this->modsDom->createElement('mods:dateIssued');
            $newDateIssued->setAttribute('encoding','iso8601');     
            $newDateIssued->setAttribute('keyDate','yes');     
            $newDateIssued->nodeValue = $date;
            $newOriginInfo->appendChild($newDateIssued);
        } else {
            throw \Exception('Invalid xml data.');
        }   
        
    }
              
  }
  
  
  public function getDateIssued() {  
      
      $dateIssued = $this->getModsXpath()->query('/mods:mods/mods:originInfo[@eventType="distribution"]/mods:dateIssued[@encoding="iso8601"][@keyDate="yes"]');   
      if ($dateIssued->length > 0) {
          return $dateIssued->item(0)->nodeValue;
      } 
      
      return NULL;
  }     
  
  public function removeDateIssued() {  
      
      $dateIssued = $this->getModsXpath()->query('/mods:mods/mods:originInfo[@eventType="distribution"]/mods:dateIssued[@encoding="iso8601"][@keyDate="yes"]');   
      if ($dateIssued->length > 0) {
          $dateIssued->item(0)->parentNode->removeChild($dateIssued->item(0));
      } 
           
  }     
  
}

?>
