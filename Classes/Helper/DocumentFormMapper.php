<?php
namespace EWW\Dpf\Helper;

class DocumentFormMapper {
  
  
  protected $domXpath;
    
  public function getDocumentForm($documentType,$document) {

    $form['uid'] = $documentType->getUid();
    $form['displayName'] = $documentType->getDisplayName();
    $form['documentUid'] = $document->getUid();
    
    //$metsData = new \SimpleXMLElement($document->getXmlData());        
    //$modsData = $metsData->xpath("/mets:mets/mets:dmdSec/mets:mdWrap/mets:xmlData/mods:mods");

    // Get the mods data
    $metsDom = new \DOMDocument();
    $metsDom->loadXML($document->getXmlData());
    $metsXpath = new \DOMXPath($metsDom);  
    $metsXpath->registerNamespace("mods", "http://www.loc.gov/mods/v3");        
    $modsNodes = $metsXpath->query("/mets:mets/mets:dmdSec/mets:mdWrap/mets:xmlData/mods:mods");
                   
    $dom = new \DOMDocument();
            
    if ($modsNodes->length == 1) {      
      $dom->loadXML($metsDom->saveXML($modsNodes->item(0)));    
    } else {
     $dom->loadXML("");      
    } 
      
    $this->domXpath = new \DOMXPath($dom);     
    $form['pages'] = $this->readDocument($documentType);
    
    return $form;
  }

  
  protected function readDocument($node, \DOMNode $nodeData = NULL) {
      
    foreach ($node->getChildren() as $child) {

      $item = array();
      $field = array();

      switch (get_class($child)) {

        case 'EWW\Dpf\Domain\Model\MetadataGroup':
          $item['uid'] = $child->getUid();
          $item['displayName'] = $child->getDisplayName();
          $item['mandatory'] = $child->getMandatory();
          $item['maxIteration'] = $child->getMaxIteration();
          
          // Read the group data.                                     
          $groupData = $this->domXpath->query($child->getMapping());                                     
          
          if ($groupData->length > 0) {
            foreach ($groupData as $key => $data) {
              $item['items'][$key]['fields'] = $this->readDocument($child, $data);
            }
          } else {
            $item['items'][0]['fields'] = $this->readDocument($child);
          }
          break;

        case 'EWW\Dpf\Domain\Model\MetadataObject':         
          $item['uid'] = $child->getUid();
          $item['displayName'] = $child->getDisplayName();
          $item['mandatory'] = $child->getMandatory();
          $item['inputField'] = $child->getInputField();
          $item['maxIteration'] = $child->getMaxIteration();
                   
          if ($nodeData) {
           
              $objectMapping = $child->getMapping();
              $objectMapping = trim($objectMapping,'/');                                                     
              $objectData = $this->domXpath->query($objectMapping,$nodeData);              
                                                 
            if ($objectData->length > 0) { 
              foreach ($objectData as $key => $value) {                                               
                $item['items'][] = $value->nodeValue;
              }
            } else {
              $item['items'][] = NULL;
            }
          } else {
            $item['items'][] = NULL;
          }     
          break;

        default:
          $item['uid'] = $child->getUid();
          $item['displayName'] = $child->getDisplayName();
          $item['groups'] = $this->readDocument($child, $nodeData);
          break;
      }
     
      $form[] = $item;                          
    }                     
    
    return $form;              
  }                 

 
  public function getDocumentData($documentType, $formData) {
    
    $data['documentUid'] = $formData['documentUid'];
    
    $data['metadata'] = $this->readFormData($documentType, $formData['metadata']);

    $data['files'] = $formData['files'];

    return $data;
  }


  public function readFormData($node, $nodeData) {
    
    $form = array();
    
    foreach ($node->getChildren() as $child) {
      
      $item = array();
      $field = array();
      
      switch (get_class($child)) {

        case 'EWW\Dpf\Domain\Model\MetadataGroup': 
          
          $groupMapping =  "/" .  trim($child->getMapping()," /");          
          $uid = $child->getUid();        
          $groupData = $nodeData['g'][$uid];
          
          foreach ($groupData as $index => $group) {
            $item = $this->readFormData($child, $group);            
            $item['mapping'] = $groupMapping;
            $item['groupUid'] = $uid;
            $form[] = $item; 
          }         
          
          break;

        case 'EWW\Dpf\Domain\Model\MetadataObject':          
          
          $fieldMapping = trim($child->getMapping()," /");                                                 
            
          $uid = $child->getUid();         
          $fieldData = $nodeData['f'][$uid];
                                                
          foreach ($fieldData as $index => $value) {
           
            // Do not save empty fields 
            if ($value) {
              $field['mapping'] = $fieldMapping;
              $field['value'] = $value;                                             
                                  
              if ( strpos($fieldMapping, "@") === 0) {
                $form['attributes'][] = $field;                     
              } else {
                $form['values'][] = $field;
              }
            }
            
            if (!key_exists('attributes', $form)) $form['attributes'] = array();
            if (!key_exists('values', $form)) $form['values'] = array();            
           
          }                                    
          break;          
        
        default:
          $data = $nodeData['p'][$child->getUid()];
          
          $items = $this->readFormData($child, $data);   
          
          foreach ($items as $item) {            
            $form[] = $item;            
          }                    
          break;
      }     
             
    }
   
     return $form;        
  }

  
  public function getDocumentForm2($documentType,$document) { 
    
    $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();      
    $documentForm->setUid($documentType->getUid());    
    $documentForm->setDisplayName($documentType->getDisplayName());
    $documentForm->setName($documentType->getName());
    $documentForm->setDocumentUid($document->getUid());
   
    // Get the mods data
    $metsDom = new \DOMDocument();
    $metsDom->loadXML($document->getXmlData());
    $metsXpath = new \DOMXPath($metsDom);  
    $metsXpath->registerNamespace("mods", "http://www.loc.gov/mods/v3");        
    $modsNodes = $metsXpath->query("/mets:mets/mets:dmdSec/mets:mdWrap/mets:xmlData/mods:mods");
                   
    $dom = new \DOMDocument();
            
    if ($modsNodes->length == 1) {      
      $dom->loadXML($metsDom->saveXML($modsNodes->item(0)));    
    } else {
     $dom->loadXML("");      
    } 
      
    $this->domXpath = new \DOMXPath($dom);     
    
       
    $documentData = array();    
        
    foreach ($documentType->getMetadataPage() as $metadataPage ) {                                    
      $documentFormPage = new \EWW\Dpf\Domain\Model\DocumentFormPage();
      $documentFormPage->setUid($metadataPage->getUid());
      $documentFormPage->setDisplayName($metadataPage->getDisplayName());
      $documentFormPage->setName($metadataPage->getName());                               
      
      var_dump($metadataPage->getName());
      
      foreach ($metadataPage->getMetadataGroup() as $metadataGroup ) {                             
          $documentFormGroup = new \EWW\Dpf\Domain\Model\DocumentFormGroup();
          $documentFormGroup->setUid($metadataGroup->getUid());
          $documentFormGroup->setDisplayName($metadataGroup->getDisplayName());
          $documentFormGroup->setName($metadataGroup->getName());
          $documentFormGroup->setMandatory($metadataGroup->getMandatory());
          $documentFormGroup->setMaxIteration($metadataGroup->getMaxIteration());   
               
          // Read the group data.                                     
          $groupData = $this->domXpath->query($metadataGroup->getMapping());                                     
                                              
          if ($groupData->length > 0) {
            foreach ($groupData as $key => $data) {              
              
              $documentFormGroupItem = clone($documentFormGroup);
              
              foreach ($metadataGroup->getMetadataObject() as $metadataObject ) {  
                
                $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
                $documentFormField->setUid($metadataObject->getUid());
                $documentFormField->setDisplayName($metadataObject->getDisplayName());
                $documentFormField->setName($metadataObject->getName());               
                $documentFormField->setMandatory($metadataObject->getMandatory());
                $documentFormField->setMaxIteration($metadataObject->getMaxIteration());   
                //$documentFormField->setValue($object);
          
                // $item['inputField'] = $child->getInputField();
                                                         
                $objectMapping = $metadataObject->getMapping();
                $objectMapping = trim($objectMapping,'/');                                                     
                $objectData = $this->domXpath->query($objectMapping,$data);              
                                                 
                if ($objectData->length > 0) { 
                  foreach ($objectData as $key => $value) {                                               
                    $documentFormField->setValue($value->nodeValue);
                  }
                }
           
                                              
                $documentFormGroupItem->addItem($documentFormField);                
              }
                                                                   
              $documentFormPage->addItem($documentFormGroupItem);                           
            }
          } else {            
            foreach ($metadataGroup->getMetadataObject() as $metadataObject ) {                  
              $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
              $documentFormField->setUid($metadataObject->getUid());
              $documentFormField->setDisplayName($metadataObject->getDisplayName());
              $documentFormField->setName($metadataObject->getName());               
              $documentFormField->setMandatory($metadataObject->getMandatory());
              $documentFormField->setMaxIteration($metadataObject->getMaxIteration());   
              $documentFormField->setValue("");
                               
              $documentFormGroup->addItem($documentFormField);                
            }
                        
            $documentFormPage->addItem($documentFormGroup);                       
          }
      }   
      /*  
        foreach ($groupItem as $group ) {   
                
            
          
        foreach ($group as $objectUid => $objectItem ) {     
          foreach ($objectItem as $objectItem => $object ) {  
            $metadataObject = $this->metadataObjectRepository->findByUid($objectUid);                               
            $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
            $documentFormField->setUid($metadataObject->getUid());
            $documentFormField->setDisplayName($metadataObject->getDisplayName());
            $documentFormField->setName($metadataObject->getName());
            $documentFormField->setValue($object);
            
            $documentFormGroup->addItem($documentFormField);                                 
          }
        }
       
          $documentFormPage->addItem($documentFormGroup);                
        }  
      } */
      
      $documentForm->addItem($documentFormPage);            
    }
                           
    return $documentForm;
  }
 
}

?>
