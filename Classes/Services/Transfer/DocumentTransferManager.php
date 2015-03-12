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
    
    $remoteDocumentId = $this->remoteRepository->ingest($document);
    
                        
    if ($remoteDocumentId) {            
        $document->setObjectIdentifier($remoteDocumentId);                                                        
        $document->setTransferStatus(Document::TRANSFER_SENT);                   
        $this->documentRepository->update($document);
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
        
  }
  
    
  /**
   * Gets an existing document from the Fedora repository
   * 
   * @param integer $id
   * @return \EWW\Dpf\Domain\Model\Document
   */
  public function retrieve($id) {
    return NULL;
  }
  
    
  /**
   * Removes an existing document from the Fedora repository
   * 
   * @param type $id
   * @return \EWW\Dpf\Domain\Model\Document
   */
  public function delete($id) {
    return NULL;
  }
  
}


?>
