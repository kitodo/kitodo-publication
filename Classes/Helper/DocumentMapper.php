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
  
        
  protected $domXpath; 
  
  
  public function getDocumentForm($document) { 
           
    $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();      
    $documentForm->setUid($document->getDocumentType()->getUid());    
    $documentForm->setDisplayName($document->getDocumentType()->getDisplayName());
    $documentForm->setName($document->getDocumentType()->getName());
    $documentForm->setDocumentUid($document->getUid());
   
    /*
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
    */
    
    $dom = new \DOMDocument();
    $dom->loadXML($document->getXmlData());           
    
    // $this->domXpath = new \DOMXPath($dom);
    $this->domXpath = \EWW\Dpf\Helper\XPath::create($dom);  
    
    $this->domXpath->registerNamespace("foaf", "http://xmlns.com/foaf/0.1/");
    $this->domXpath->registerNamespace("slub", "http://slub-dresden.de");
                       
   
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
          $documentFormGroup->setMaxIteration($metadataGroup->getMaxIteration());   
               
          // Read the group data.                                     
          $groupData = $this->domXpath->query($metadataGroup->getAbsoluteMapping());                                     
                                        
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
                $documentFormField->setInputField($metadataObject->getInputField()); 
                $documentFormField->setInputOptions($metadataObject->getInputOptionList()); 
                                                              
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
                
                
                if ($metadataObject->isModsExtension()) {
                    
                  $referenceAttribute = $metadataGroup->getModsExtensionReference();  
                  $modsExtensionGroupMapping = $metadataGroup->getAbsoluteModsExtensionMapping();                  
                  
                  $refID = $data->getAttribute("ID");                                     
                  $objectData = $this->domXpath->query($modsExtensionGroupMapping.'[@'.$referenceAttribute.'='.'"#'.$refID.'"]/'.$objectMapping);     
                                                                                                                      
                } else {                                
                  $objectData = $this->domXpath->query($objectMapping,$data);              
                }                                                                                                                                          
                
                if ($objectData->length > 0) { 
                                                       
                  foreach ($objectData as $key => $value) {   
                                                              
                    $documentFormFieldItem = clone($documentFormField);  
                                                           
                    $objectValue = $value->nodeValue;                                                                                                                                        
                    $objectValue = htmlspecialchars_decode($objectValue,ENT_QUOTES);    
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
              $documentFormField->setMaxIteration($metadataObject->getMaxIteration());   
              $documentFormField->setInputField($metadataObject->getInputField()); 
              $documentFormField->setInputOptions($metadataObject->getInputOptionList()); 
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
                            
    $data['documentUid'] = $documentForm->getDocumentUid();
    
    $data['metadata'] = $this->getMetadata($documentForm);   

    $data['files'] = array();
                      
    $exporter = new \EWW\Dpf\Services\MetsExporter();                
    $exporter->buildModsFromForm($data);       
    $modsXml = $exporter->getModsData();    
    $document->setXmlData($modsXml);                  
    
    $mods = new \EWW\Dpf\Helper\Mods($modsXml);                                        
    $document->setTitle($mods->getTitle());          
    $document->setAuthors($mods->getAuthors());
    
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
              $value = htmlspecialchars($value,ENT_QUOTES,'UTF-8');
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

          $form[] = $item; 

        }

      }                                                                  
    }
        
    return $form;
        
  }
       
}

?>
