<?php
namespace EWW\Dpf\Services\Transfer;

class FileId {
  
  protected $id;
  
  
  public function __construct($document) {
    
    $idList = array();
    foreach ( $document->getFile() as $file ) {   
      $dsId = $file->getDatastreamIdentifier();                  
      if (!empty($dsId) && $dsId != \Eww\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {                        
        $id = split("_", $dsId);        
        $idList[] = $id[1];                      
      }                       
    }
         
    $this->id = max($idList);
  }  
  
  
  public function getId($file) {
    
      $fileId = $file->getDatastreamIdentifier();
      if (empty($fileId)) {
        if ($file->isPrimaryFile()) {
          return \Eww\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER;  
        } else { 
          $this->id = $this->id + 1; 
          return "FILE_" . $this->id;
        }                        
      } else {
        return $fileId;
      }                 
      
  }
  
}
?>
