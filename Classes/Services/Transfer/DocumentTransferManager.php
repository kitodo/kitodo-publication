<?php
namespace EWW\Dpf\Services\Transfer;

use \EWW\Dpf\Domain\Model\Document;


class DocumentTransferManager {
    
  /**
   * documenRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentRepository                                     
   * @inject
   */
  protected $documentRepository;  
  
  
  /**
   * fileRepository
   *
   * @var \EWW\Dpf\Domain\Repository\FileRepository                                     
   * @inject
   */
  protected $fileRepository;  
  
  /**
   * remoteRepository
   *
   * @var \EWW\Dpf\Services\Transfer\Repository                                       
   */
  protected $remoteRepository;
  
  
  /**
   * Sets the remote repository into which the documents will be stored 
   * 
   * @param \EWW\Dpf\Services\Transfer\Repository $remoteRepository
   */
  public function setRemoteRepository($remoteRepository) {   
    
    $this->remoteRepository = $remoteRepository;    
     
  }
  
  /**
   * Stores a document into the remote repository
   * 
   * @param \EWW\Dpf\Domain\Model\Document $document
   * @return boolean
   */   
  public function ingest($document) {
    
    $document->setTransferStatus(Document::TRANSFER_QUEUED); 
    $this->documentRepository->update($document);     
        
    $exporter = new \EWW\Dpf\Services\MetsExporter();  
    
    $fileData = $this->getFileData($document);
    $exporter->setFileData($fileData);    
    
    $exporter->setMods($document->getXmlData());    
        
    $exporter->buildMets();  
       
    // $exporter->setSlubData($slubData);
            
    $metsXml = $exporter->getMetsData();
        
    $remoteDocumentId = $this->remoteRepository->ingest($document, $metsXml);
                            
    if ($remoteDocumentId) {            
        $document->setObjectIdentifier($remoteDocumentId);                                                        
        $document->setTransferStatus(Document::TRANSFER_SENT);                           
        $this->documentRepository->update($document);
        $this->documentRepository->remove($document);
        return TRUE;
    } else {            
      $document->setTransferStatus(Document::TRANSFER_ERROR);                                   
      $this->documentRepository->update($document);
      return FALSE;
    }
                   
  }
  
  
  /**
   * Updates an existing document in the remote repository
   * 
   * @param \EWW\Dpf\Domain\Model\Document $document
   * @return boolean
   */
  public function update($document) {
    
    $document->setTransferStatus(Document::TRANSFER_QUEUED); 
    $this->documentRepository->update($document);  
        
    $exporter = new \EWW\Dpf\Services\MetsExporter();  
    
    $fileData = $this->getFileData($document);
           
    $exporter->setFileData($fileData);    
    
    $exporter->setMods($document->getXmlData());    
        
    // $exporter->setSlubData($slubData);
            
    $exporter->buildMets();  
     
    $metsXml = $exporter->getMetsData();
           
    if ($this->remoteRepository->update($document, $metsXml)) {                
      $document->setTransferStatus(Document::TRANSFER_SENT); 
      $this->documentRepository->update($document);          
      $this->documentRepository->remove($document);
      return TRUE;
    } else {
      $document->setTransferStatus(Document::TRANSFER_ERROR);                                   
      $this->documentRepository->update($document); 
      return FALSE;
    }  
    
  }
  
    
  /**
   * Gets an existing document from the Fedora repository
   * 
   * @param \EWW\Dpf\Domain\Model\Document $document
   * @return boolean
   */
  public function retrieve($document) {
    return FALSE;
  }
  
    
  /**
   * Removes an existing document from the Fedora repository
   * 
   * @param \EWW\Dpf\Domain\Model\Document $document
   * @return boolean
   */
  public function delete($document) {
   
    $document->setTransferStatus(Document::TRANSFER_QUEUED); 
    $this->documentRepository->update($document);  
    
    if ($this->remoteRepository->delete($document)) {                
      $document->setTransferStatus(Document::TRANSFER_SENT); 
      $this->documentRepository->update($document);          
      $this->documentRepository->remove($document);
      return TRUE;
    } else {
      $document->setTransferStatus(Document::TRANSFER_ERROR);                                   
      $this->documentRepository->update($document); 
      return FALSE;
    }            
  }
  
  
      
  protected function getFileData($document) {
        
   $fileId = new \EWW\Dpf\Services\Transfer\FileId($document);
          
   $files = array();
   
   foreach ( $document->getFile() as $file ) {                  
     
     if (!empty($file->getStatus())) {
       
      if ($file->getStatus() != \Eww\Dpf\Domain\Model\File::STATUS_DELETED) {                                
         $files[$file->getUid()] = array(
           'path' => $file->getFileUrl(),
           'type' => $file->getContentType(),
           'id' => $fileId->getId($file),
           'title' => $file->getTitle(),  
           'use' => '' 
         );                                
       } elseif (!empty($file->getDatastreamIdentifier())) {        
         $files[$file->getUid()] = array(
           'path' => $file->getFileUrl(),
           'type' => $file->getContentType(),
           'id' => $file->getDatastreamIdentifier(),
           'title' => $file->getTitle(),  
           'use' => 'DELETE'         
         );
       }
     }
     
    } 
    
    return $files;
    
  }
  
}


?>
