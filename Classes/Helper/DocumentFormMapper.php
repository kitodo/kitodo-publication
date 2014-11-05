<?php
namespace EWW\Dpf\Helper;


class DocumentFormMapper {
  
  protected $form = array();
  
  protected $document;
  
  protected $xmlData;
  
    
  public function setDocument($document) {    
    $this->document = $document;    
    $this->xmlData = new \SimpleXMLElement($document->getXmlData());
  }      
  
  public function getForm($node) { 
    
    $form = array( 
        'uid' => $node->getUid(),
        'name' => $node->getDisplayName(),
        'children' => $this->getDocumentForm($node),
    );    
       
    return $form;
  }
  
  public function getDocumentForm($node) {
           
    $children = array();
    $type = NULL;
               
    switch (get_class($node)) {
      
      case 'EWW\Dpf\Domain\Model\DocumentType':
        $children = $node->getMetadataPage();
        $type = 'page';
       
        break;
      
      case 'EWW\Dpf\Domain\Model\MetadataPage':
        $children = $node->getMetadataGroup();  
        $type = 'group';
        break;
      
      case 'EWW\Dpf\Domain\Model\MetadataGroup':
        $children = $node->getMetadataObject();        
        $type = 'object';
        break;
        
      case 'EWW\Dpf\Domain\Model\MetadataObject':                
        break;      
    }
                     
    foreach ($children as $child) {
                                               
      
      if ($type == 'group') {
        $fields = $this->getObjectData($child->getMetadataObject(),$child->getMapping());
        $mapping = $child->getMapping();
      }                
      
      $form[] = array(          
          'uid' => $child->getUid(),
          'name' => $child->getDisplayName(),
          'children' => $this->getDocumentForm($child),
          'fields' => $fields,
          'mapping' => $mapping,
      );
                      
    }                     
    
    return $form;
      
    
    
  }               


  public function getObjectData($objects,$groupMapping) {
        
    $groupXml = $this->xmlData->xpath($groupMapping);
    
    foreach ($groupXml as $xml) {
                       
      // Map xml data to the fields
      foreach ($objects as $object) {
      
        $objectMapping = $object->getMapping();      
      
        preg_match("/^.*?[:|@](\w*?)(\[.*\]){0,1}$/", $objectMapping, $match);
        $nodeName = $match[1];  
                         
        
        if ($objectMapping) {
        
          $fieldsXml = $xml->xpath(trim($objectMapping,'/'));
        
               
          
          foreach ($fieldsXml as $fieldXml) {
            
                 echo "<pre>";
          //var_dump(trim($objectMapping,'/'));
          var_dump($fieldXml->asXML());
          echo "</pre>";   
       
              
            
              $result[$object->getDisplayName()][] = $fieldXml->asXML(); 
              
            
          }
          
        
        } else {
        
        // Error: Missing mapping.
        
        }                  
      }          
    }  
     die();
    return $result;  
    
  }
  
  
  public function getDocumentData($node) {
           
    $children = array();
    $type = NULL;
               
    switch (get_class($node)) {
      
      case 'EWW\Dpf\Domain\Model\DocumentType':
        $children = $node->getMetadataPage();
        $type = 'page';
       
        break;
      
      case 'EWW\Dpf\Domain\Model\MetadataPage':
        $children = $node->getMetadataGroup();  
        $type = 'group';
        break;
      
      case 'EWW\Dpf\Domain\Model\MetadataGroup':
        $children = $node->getMetadataObject();        
        $type = 'object';
        break;
        
      case 'EWW\Dpf\Domain\Model\MetadataObject':                
        break;      
    }
                     
    foreach ($children as $child) {
                                                                                                                     
      
                      
    }                     
    
    return $form;
      
    
    
  }               
  
  
  
}



?>
