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
   * documenTypeRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository                                     
   * @inject
   */
  protected $documentTypeRepository;  
  
  /**
   * fileRepository
   *
   * @var \EWW\Dpf\Domain\Repository\FileRepository                                     
   * @inject
   */
  protected $fileRepository;  
  
  
  /**
   * objectManager
   * 
   * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
   * @inject
   */
  protected $objectManager;
  
  
  /**
    * persistence manager
    *
    * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
    * @inject
    */
   protected $persistenceManager;

  
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
                
    $exporter->slubInfo(array('documentType' => $document->getDocumentType()->getName()));
    
    $exporter->buildMets();  
                   
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
    
    $exporter->slubInfo(array('documentType' => $document->getDocumentType()->getName()));
    
    $exporter->setMods($document->getXmlData());    
                    
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
   * @param string $remoteId
   * @return boolean
   */
  public function retrieve($remoteId) {
    
    $mets = $this->remoteRepository->retrieve($remoteId);
                        
    try {
    
      if ($mets) {      

        $metsDom = new \DOMDocument();
        $metsDom->loadXML($mets);           

        $metsXpath = new \DOMXPath($metsDom);  
        $metsXpath->registerNamespace("mods", "http://www.loc.gov/mods/v3");        
        $modsNodes = $metsXpath->query("/mets:mets/mets:dmdSec/mets:mdWrap/mets:xmlData/mods:mods");                         

        $metsXpath = new \DOMXPath($metsDom); 
        $metsXpath->registerNamespace("slub", "http://slub-dresden.de/");      
        $slubNodes = $metsXpath->query("/mets:mets/mets:amdSec/mets:rightsMD/mets:mdWrap/mets:xmlData/slub:info");   

        $metsXpath = new \DOMXPath($metsDom);  
        //$metsXpath->registerNamespace("slub", "http://slub-dresden.de/");      
        $fileNodes = $metsXpath->query("/mets:mets/mets:fileSec/mets:fileGrp/mets:file");   


        if ($modsNodes->length == 1 && $slubNodes->length == 1 ) {      
          $modsDom = new \DOMDocument();
          $modsDom->loadXML($metsDom->saveXML($modsNodes->item(0)));    
          $modsXpath = new \DOMXPath($modsDom);     

          $titleNode = $modsXpath->query("/mods:mods/mods:titleInfo/mods:title");
          $title = $titleNode->item(0)->nodeValue;          

          $slubDom = new \DOMDocument();
          $slubDom->loadXML($metsDom->saveXML($slubNodes->item(0)));    
          $slubXpath = new \DOMXPath($slubDom);     
          $documentTypeNode = $slubXpath->query("/slub:info/slub:documentType");
          $documentTypeName = $documentTypeNode->item(0)->nodeValue;            
          $documentType = $this->documentTypeRepository->findByName($documentTypeName);                                 

          $document = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');

          $document->setObjectIdentifier($remoteId);                                         
          $document->setTitle($title);
          $document->setDocumentType($documentType->current());
          $document->setXmlData($modsDom->saveXML());

          $this->documentRepository->add($document);  
          $this->persistenceManager->persistAll();

          foreach ($fileNodes as $item) {        
            $id = $item->getAttribute("ID");                
            $mimetype = $item->getAttribute("MIMETYPE");
            $url = $item->firstChild->getAttribute("xlin:href");               

            $fileTitle = $item->firstChild->getAttribute("xlin:title");               
            
            $file = $this->objectManager->get('\EWW\Dpf\Domain\Model\File');

            $file->setContentType($mimetype);
            $file->setDatastreamIdentifier($id);
            $file->setLink($url);
            $file->setTitle($fileTitle);

            if ($id == \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {
              $file->setPrimaryFile(TRUE);           
            }

            $file->setDocument($document);

            $this->fileRepository->add($file);
          }
        
                        
          return TRUE;
        }  
              
      } 
                            
    } catch(Exception $exception) {          
      return FALSE; 
    }
            
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
           'path' => $file->getLink(),
           'type' => $file->getContentType(),
           'id' => $fileId->getId($file),
           'title' => $file->getTitle(),  
           'use' => ''
         );                                
       } elseif (!empty($file->getDatastreamIdentifier())) {        
         $files[$file->getUid()] = array(
           'path' => $file->getLink(),
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
