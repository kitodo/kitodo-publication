<?php
namespace EWW\Dpf\Helper;

class DocumentFormMapper {
  
  /**
   * documentTypeRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
   * @inject
   */
  protected $documentTypeRepository = NULL;        
  
  
  /**
   * fileRepository
   *
   * @var \EWW\Dpf\Domain\Repository\FileRepository
   * @inject
   */
  protected $fileRepository = NULL;        
  
  
  /**
   * objectManager
   * 
   * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
   * @inject
   */
  protected $objectManager;
    
  protected $domXpath; 
  
  
  public function getDocumentForm($document) { 
               
    $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();      
    $documentForm->setUid($document->getDocumentType()->getUid());    
    $documentForm->setDisplayName($document->getDocumentType()->getDisplayName());
    $documentForm->setName($document->getDocumentType()->getName());
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
                $documentFormField->setInputField($metadataObject->getInputField());   
                //$documentFormField->setValue($object);
          
                // $item['inputField'] = $child->getInputField();
                                                         
                $objectMapping = $metadataObject->getMapping();
                $objectMapping = trim($objectMapping,'/');                                                     
                $objectData = $this->domXpath->query($objectMapping,$data);              
                                                 
                if ($objectData->length > 0) { 
                  foreach ($objectData as $key => $value) {          
                    
                    $objectValue = $value->nodeValue;
                                                           
                    if ($metadataObject->getInputField() == \EWW\Dpf\Domain\Model\MetadataObject::language) {
                      // If field has type language: Map static_info_tables Database-ID to iso 639-2/B
                      $languageHelper = $this->objectManager->get('EWW\Dpf\Helper\LanguageHelper');                            
                      $objectValue = $languageHelper->getIdByIsoCodeA3($objectValue);                                                                                                                                
                    } 
                                                         
                    $documentFormField->setValue($objectValue);
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
              $documentFormField->setInputField($metadataObject->getInputField());   
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
    
    
    // Files      
    $primaryFile = $this->fileRepository->getPrimaryFileByDocument($document);
    $documentForm->setPrimaryFile($primaryFile);
              
    $secondaryFiles = $this->fileRepository->getSecondaryFilesByDocument($document)->toArray();;   
    $documentForm->setSecondaryFiles($secondaryFiles);
            
    return $documentForm;
  }
 
}

?>
