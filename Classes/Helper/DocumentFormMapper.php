<?php
namespace EWW\Dpf\Helper;

class DocumentFormMapper {
    
  protected $flatFormData = array();


  public function getDocumentForm($documentType,$document) {

    $form['uid'] = $documentType->getUid();
    $form['displayName'] = $documentType->getDisplayName();
    
    $xmlData = new \SimpleXMLElement($document->getXmlData());
    
    $form['pages'] = $this->readDocument($documentType,$xmlData);
       
    return $form;
  }

  
  protected function readDocument($node, $nodeData = NULL) {
      
    foreach ($node->getChildren() as $child) {

      $item = array();
      $field = array();

      switch (get_class($child)) {

        case 'EWW\Dpf\Domain\Model\MetadataGroup':
          $item['uid'] = $child->getUid();
          $item['displayName'] = $child->getDisplayName();
          $item['mandatory'] = $child->getMandatory();

          // Read the group data.
          //$xmlData = $this->xmlData->xpath($child->getMapping());
          $groupData = $nodeData->xpath($child->getMapping());
          
          if ($groupData) {
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
                      
          if ($nodeData) {
                
              $objectMapping = $child->getMapping();
              $objectMapping = trim($objectMapping,'/');
              $objectData = $nodeData->xpath($objectMapping);
            
            if ($objectData) {
              foreach ($objectData as $key => $value) {
                $item['items'][] = (string)$value;
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

    $this->flatFormData = array();

    $this->readFormData($documentType, $formData['metadata']);
    
    $data['metadata'] = $this->flatFormData;
    $data['files'] = $formData['files'];

    return $data;
  }


  public function readFormData($node, $nodeData, $key=NULL, $mapping=NULL) {
      
    foreach ($node->getChildren() as $child) {
      
      $item = array();
      
      switch (get_class($child)) {

        case 'EWW\Dpf\Domain\Model\MetadataGroup':
          $groupKey = $key ."-g" . $child->getUid();
          $mapping =  "/" .  trim($child->getMapping()," /");
          
          $uid = $child->getUid();        
          $groupData = $nodeData['g'][$uid];
          foreach ($groupData as $index => $group) {
            $this->readFormData($child, $group, $groupKey."-".$index,$mapping);
          }         
          break;

        case 'EWW\Dpf\Domain\Model\MetadataObject':
          $fieldKey = $key . "-f" . $child->getUid();
          $fieldMapping = $mapping. "/". trim($child->getMapping()," /");

          $uid = $child->getUid();         
          $fieldData = $nodeData['f'][$uid];
          foreach ($fieldData as $index => $value) {
            $item['mapping'] = $fieldMapping;
            $item['value'] = $value;
            $this->flatFormData[$fieldKey."-".$index] = $item;
          }         
          break;

        default:
          $data = $nodeData['p'][$child->getUid()];
          $this->readFormData($child, $data, "p".$child->getUid());
          break;
      }
     
    }
  }


  
}

?>
