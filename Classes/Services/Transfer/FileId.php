<?php
namespace EWW\Dpf\Services\Transfer;

class FileId {
  
  protected $id = 0;
  
  
  public function __construct($document) {
    
    $idList = array();
    $this->id = 0;
         
    if (is_a($document->getFile(),'\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {                               
        foreach ( $document->getFile() as $file ) {   
          $dsId = $file->getDatastreamIdentifier();                  
          if (!empty($dsId) && $dsId != \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {                        
            $id = explode("-", $dsId);        
            $idList[] = $id[1];          
          }                       
        }        
    } else {
        throw new \Exception('Unexpected type!');
    }
    
    if (!empty($idList)) $this->id = max($idList);
  }  
  
  
  public function getId($file) {
    
      $fileId = $file->getDatastreamIdentifier();
      if (empty($fileId)) {
        if ($file->isPrimaryFile()) {
          return \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER;  
        } else {
          $this->id = $this->id + 1; 
          return \EWW\Dpf\Domain\Model\File::DATASTREAM_IDENTIFIER_PREFIX. $this->id;
        }                        
      } else {
        return $fileId;
      }                 
      
  }
  
}
?>
