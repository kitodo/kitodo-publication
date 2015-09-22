<?php
namespace EWW\Dpf\Helper;

class DocumentMapper {
  
  /**
   * objectManager
   * 
   * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
   * @inject
   */
  protected $objectManager;
  
  
  /**
   * metadataGroupRepository
   *
   * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
   * @inject
   */
  protected $metadataGroupRepository = NULL;        
  
    
  /**
   * metadataObjectRepository
   *
   * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
   * @inject
   */
  protected $metadataObjectRepository = NULL;

            
  /**
   * documentTypeRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
   * @inject
   */
  protected $documentTypeRepository = NULL;        
  
  
  /**
   * documentRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentRepository
   * @inject
   */
  protected $documentRepository = NULL;        
  
  
  /**
   * fileRepository
   *
   * @var \EWW\Dpf\Domain\Repository\FileRepository
   * @inject
   */
  protected $fileRepository = NULL;        
    
  
  public function getDocumentForm($document) { 
        
    $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();      
    $documentForm->setUid($document->getDocumentType()->getUid());    
    $documentForm->setDisplayName($document->getDocumentType()->getDisplayName());
    $documentForm->setName($document->getDocumentType()->getName());
    $documentForm->setDocumentUid($document->getUid());
    
    $qucosaId = $document->getObjectIdentifier();
    
    if (empty($qucosaId)) {         
        $qucosaId = $document->getReservedObjectIdentifier();        
    }
    
    if (!empty($qucosaId)) {
        $urnService = $this->objectManager->get('EWW\\Dpf\\Services\\Identifier\\Urn');       
        $qucosaUrn = $urnService->getUrn($qucosaId);
        $documentForm->setQucosaUrn($qucosaUrn); 
    }
    
    $documentForm->setQucosaId($qucosaId);
                         
    $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());  
    $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
    
    
    $excludeGroupAttributes = array();
    
    $documentData = array();    
           
    foreach ($document->getDocumentType()->getMetadataPage() as $metadataPage ) {                                    
      $documentFormPage = new \EWW\Dpf\Domain\Model\DocumentFormPage();
      $documentFormPage->setUid($metadataPage->getUid());
      $documentFormPage->setDisplayName($metadataPage->getDisplayName());
      $documentFormPage->setName($metadataPage->getName());                               
               
      foreach ($metadataPage->getMetadataGroup() as $metadataGroup ) {                             
          
          $documentFormGroup = new \EWW\Dpf\Domain\Model\DocumentFormGroup();
          $documentFormGroup->setUid($metadataGroup->getUid());
          $documentFormGroup->setDisplayName($metadataGroup->getDisplayName());
          $documentFormGroup->setName($metadataGroup->getName());
          $documentFormGroup->setMandatory($metadataGroup->getMandatory());
          $documentFormGroup->setBackendOnly($metadataGroup->getBackendOnly());         
          $documentFormGroup->setMaxIteration($metadataGroup->getMaxIteration());   
                          
                   
          if ($metadataGroup->isSlubInfo()) {
              $xpath = $slub->getSlubXpath();                            
          } else {
              $xpath = $mods->getModsXpath();                 
          }

          
          // get fixed attributes from xpath configuration
          $fixedGroupAttributes = array();
          $groupMappingPathParts = explode('/',$metadataGroup->getAbsoluteMapping());  	
  	  $groupMappingPath = end($groupMappingPathParts);  	                    
          $groupMappingName = preg_replace('/\[@.+?\]/','',$groupMappingPath);          
                    
          if (preg_match_all('/\[@.+?\]/',$groupMappingPath,$matches)) {                              
                 $fixedGroupAttributes = $matches[0];                           
          }         
                         
          // build mapping path, previous fixed attributes which are differ from 
          // the own fixed attributes are excluded 
          $queryGroupMapping = $metadataGroup->getAbsoluteMapping();
          if (is_array($excludeGroupAttributes[$groupMappingName])) {
            foreach ($excludeGroupAttributes[$groupMappingName] as $excludeAttr => $excludeAttrValue) {              
              if (!in_array($excludeAttr, $fixedGroupAttributes)) {
                $queryGroupMapping .=  $excludeAttrValue;  
              }              
            }       
          }       
          
          // Read the group data.                        
          $groupData = $xpath->query($queryGroupMapping);  
         
          // Fixed attributes from groups must be excluded in following xpath queries  	                              
          foreach($fixedGroupAttributes as $excludeGroupAttribute) {                 
            $excludeGroupAttributes[$groupMappingName][$excludeGroupAttribute] = "[not(".trim($excludeGroupAttribute,"[] ").")]";
          }                                              

          
          if ($groupData->length > 0) {
            foreach ($groupData as $key => $data) {              
                                         
              $documentFormGroupItem = clone($documentFormGroup);
              
              foreach ($metadataGroup->getMetadataObject() as $metadataObject ) {  
                                                   
                $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
                $documentFormField->setUid($metadataObject->getUid());
                $documentFormField->setDisplayName($metadataObject->getDisplayName());
                $documentFormField->setName($metadataObject->getName());               
                $documentFormField->setMandatory($metadataObject->getMandatory());
                $documentFormField->setBackendOnly($metadataObject->getBackendOnly());
                $documentFormField->setMaxIteration($metadataObject->getMaxIteration());  
                $documentFormField->setInputField($metadataObject->getInputField()); 
                $documentFormField->setInputOptions($metadataObject->getInputOptionList()); 
                $documentFormField->setFillOutService($metadataObject->getFillOutService()); 
                                                              
                $objectMapping = "";                

                $objectMappingPath = explode("/", $metadataObject->getRelativeMapping());                               
                
                foreach ($objectMappingPath as $key => $value) {                                          
                    
                    // ensure that e.g. <mods:detail> and <mods:detail type="volume"> 
                    // are not recognized as the same node                 
                    if ((strpos($value,"@") === FALSE) && ($value != '.')) {                                                 
                        $objectMappingPath[$key] .= "[not(@*)]";                        
                    }                                                            
                }      
                
                $objectMapping = implode("/", $objectMappingPath); 
                
                if ($objectMapping == '[not(@*)]') {
                    $objectMapping = '.';
                }
               
                if ($metadataObject->isModsExtension()) {
                    
                  $referenceAttribute = $metadataGroup->getModsExtensionReference();  
                  $modsExtensionGroupMapping = $metadataGroup->getAbsoluteModsExtensionMapping();                  
                  
                  $refID = $data->getAttribute("ID");                                     
                  $objectData = $xpath->query($modsExtensionGroupMapping.'[@'.$referenceAttribute.'='.'"#'.$refID.'"]/'.$objectMapping);     
                                                                                                                      
                } else {                                                                                  
                  $objectData = $xpath->query($objectMapping,$data);              
                }                                                                                                                                          
                
                if ($objectData->length > 0) { 
                                                       
                  foreach ($objectData as $key => $value) {   
                                                              
                    $documentFormFieldItem = clone($documentFormField);  
                                                           
                    $objectValue = $value->nodeValue;                                                                                                                                        
                    //$objectValue = htmlspecialchars_decode($objectValue,ENT_QUOTES);
                    $objectValue = str_replace('"',"'",$objectValue);
                    
                    $documentFormFieldItem->setValue($objectValue);
                    
                    $documentFormField->setValue($objectValue);
                                    
                    $documentFormGroupItem->addItem($documentFormFieldItem);
                  }
                } else {
                  $documentFormGroupItem->addItem($documentFormField);  
                }           
                                
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
              $documentFormField->setBackendOnly($metadataObject->getBackendOnly());
              $documentFormField->setMaxIteration($metadataObject->getMaxIteration());   
              $documentFormField->setInputField($metadataObject->getInputField()); 
              $documentFormField->setInputOptions($metadataObject->getInputOptionList()); 
              $documentFormField->setFillOutService($metadataObject->getFillOutService()); 
              $documentFormField->setValue("");
                               
              $documentFormGroup->addItem($documentFormField);                
            }
                        
            $documentFormPage->addItem($documentFormGroup);                       
          }
      }   
           
      $documentForm->addItem($documentFormPage);            
    }
    
    
    // Files      
    $primaryFile = $this->fileRepository->getPrimaryFileByDocument($document);
    $documentForm->setPrimaryFile($primaryFile);
              
    $secondaryFiles = $this->fileRepository->getSecondaryFilesByDocument($document)->toArray();;   
    $documentForm->setSecondaryFiles($secondaryFiles);
            
    $documentForm->setObjectState($document->getObjectState());
    $documentForm->setRemoteAction($document->getRemoteAction());
    
    return $documentForm;
  }
 
  
  public function getDocument($documentForm) {
                               
    if ($documentForm->getDocumentUid()) {
      $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());  
    } else {
      $document = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');                  
    }  
        
    $documentType = $this->documentTypeRepository->findByUid($documentForm->getUid());                   

    $document->setDocumentType($documentType);         
    
    $document->setReservedObjectIdentifier($documentForm->getQucosaId());
                        
    
    $formMetaData = $this->getMetadata($documentForm); 

    $exporter = new \EWW\Dpf\Services\MetsExporter();                

    // mods:mods
    $modsData['documentUid'] = $documentForm->getDocumentUid();        
    $modsData['metadata'] = $formMetaData['mods'];        
    $modsData['files'] = array();
                      
    $exporter->buildModsFromForm($modsData);       
    $modsXml = $exporter->getModsData();    
    $document->setXmlData($modsXml);                  

    $mods = new \EWW\Dpf\Helper\Mods($modsXml); 
    
    $document->setTitle($mods->getTitle());          
    $document->setAuthors($mods->getAuthors());
    
    // slub:info
    $slubInfoData['documentUid'] = $documentForm->getDocumentUid();        
    $slubInfoData['metadata'] = $formMetaData['slubInfo'];        
    $slubInfoData['files'] = array();                          
    $exporter->buildSlubInfoFromForm($slubInfoData, $documentType);       
    $slubInfoXml = $exporter->getSlubInfoData();    
    $document->setSlubInfoData($slubInfoXml);         
          
    return $document;      
  }
  
  
  protected function getMetadata($documentForm) {
           
    foreach ($documentForm->getItems() as $page) {                          
              
      foreach ($page[0]->getItems() as $group) {              

        foreach ($group as $groupItem) {    

          $item = array();

          $uid = $groupItem->getUid();
          $metadataGroup = $this->metadataGroupRepository->findByUid($uid);

          $item['mapping'] = $metadataGroup->getRelativeMapping();        
                       
          $item['modsExtensionMapping'] = $metadataGroup->getRelativeModsExtensionMapping();                                                           
       
          $item['modsExtensionReference'] = trim($metadataGroup->getModsExtensionReference()," /");                    
         
          $item['groupUid'] = $uid;

          foreach ($groupItem->getItems() as $field) {
            foreach ($field as $fieldItem) {
              $fieldUid = $fieldItem->getUid();
              $metadataObject = $this->metadataObjectRepository->findByUid($fieldUid);
              
              $fieldMapping = $metadataObject->getRelativeMapping();              
              
              $formField = array();

              $value = $fieldItem->getValue();
              // $value = htmlspecialchars($value,ENT_QUOTES,'UTF-8');    
              $value = str_replace('"',"'",$value);
              if ($value) {                 
                $formField['modsExtension'] = $metadataObject->getModsExtension();
                  
                $formField['mapping'] = $fieldMapping;
                $formField['value'] = $value;

                if ( strpos($fieldMapping, "@") === 0) {
                  $item['attributes'][] = $formField;                     
                } else {
                  $item['values'][] = $formField;
                }
              }                                                                                                                                                     
            }                                                             
          }

          if (!key_exists('attributes', $item)) $item['attributes'] = array();
          if (!key_exists('values', $item)) $item['values'] = array();  

          if ($metadataGroup->isSlubInfo()) {                       
            $form['slubInfo'][] = $item; 
          } else {
            $form['mods'][] = $item;    
          } 

        }

      }                                                                  
    }
        
    return $form;
        
  }
       
}

?>
