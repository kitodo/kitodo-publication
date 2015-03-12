<?php
namespace EWW\Dpf\Helper;

class DocumentFormReader {
  
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
   * documentForm 
   *  
   * @var EWW\Dpf\Domain\Model\DocumentForm;
   */
  protected $documentForm;
  
  
  /**
   * objectManager
   * 
   * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
   * @inject
   */
  protected $objectManager;
  
  
  /**
   * documentRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentRepository
   * @inject
   */
  protected $documentRepository = NULL;
  
  
  public function setDocumentForm($documentForm) {
    $this->documentForm = $documentForm;  
  }
  
  public function getMetsXML() {
            
    foreach ($this->documentForm->getItems() as $page) {                          
              
      foreach ($page[0]->getItems() as $group) {              

        foreach ($group as $groupItem) {    

          $item = array();

          $uid = $groupItem->getUid();
          $metadataGroup = $this->metadataGroupRepository->findByUid($uid);                
          $groupMapping =  "/" .  trim($metadataGroup->getMapping()," /");          

          $item['mapping'] = $groupMapping;
          $item['groupUid'] = $uid;

          foreach ($groupItem->getItems() as $field) {                                                       
            foreach ($field as $fieldItem) {                      
              $fieldUid = $fieldItem->getUid();
              $metadataObject = $this->metadataObjectRepository->findByUid($fieldUid);                
              $fieldMapping = trim($metadataObject->getMapping()," /");     

              $formField = array();

              $value = $fieldItem->getValue();
              if ($value) { 
                
                if ($metadataObject->getInputField() == \EWW\Dpf\Domain\Model\MetadataObject::language) {
                  // If field has type language: Map static_info_tables Database-ID to iso 639-2/B
                  $languageHelper = $this->objectManager->get('EWW\Dpf\Helper\LanguageHelper');                            
                  $value = $languageHelper->getIsoCodeA3ById($value);                                                                                                     
                } 
                                
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
    
    $data['documentUid'] = $this->documentForm->getDocumentUid();
    
    $data['metadata'] = $form;

    $data['files'] = array();
       
    $exporter = new \EWW\Dpf\Services\MetsExporter();                
    $exporter->buildModsFromForm($data);
    return $exporter->getMetsData();
  }
     
  /*
  protected function getFiles($documentUid) {
    
    $files = array();
    
    $deleteFiles = $this->documentForm->getDeletedFiles();
            
    $newAndUpdatedFiles = $this->documentForm->getNewFiles();
    
    foreach ($newAndUpdatedFiles as $file) {
            
      $id = $file->getDatastreamIdentifier();
      if (empty($id)) {
        if ($file->isPrimaryFile()) {
          $file->setDatastreamIdentifier(\Eww\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER); 
        } else {
          $file->setDatastreamIdentifier("FILE_".);
        }
      }
      
      
      
      $files[] = array( 
          'path' => $file->getFileUrl(),
          'type' => $file->getContentType(),
          'id' => 
        ); 
      
      
    }
        
    return $files;    
  }
    */  
}
?>
