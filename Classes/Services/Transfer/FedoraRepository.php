<?php
namespace EWW\Dpf\Services\Transfer;

$extpath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');

require_once($extpath . '/Lib/Vendor/Httpful/Bootstrap.php');
\Httpful\Bootstrap::init();

//require_once($extpath . '/Lib/Vendor/Httpful/httpful.phar');

use \Httpful\Request;

use \EWW\Dpf\Services\Logger\TransferLogger;


class FedoraRepository implements Repository {
 
  /**
   * documentTransferLogRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentTransferLogRepository                                     
   * @inject
   */
  protected $documentTransferLogRepository;  
  
      
  /**
   * clientRepository
   *
   * @var \EWW\Dpf\Domain\Repository\ClientRepository                                  
   * @inject
   */
  protected $clientRepository;  
  
  
  /**
   * objectManager
   * 
   * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
   * @inject
   */
  protected $objectManager;
  
  
  protected $swordHost;
          
  protected $swordUser;

  protected $swordPassword;

  protected $fedoraHost;
  
  protected $response;
  
  protected $ownerId;
  
  const X_ON_BEHALF_OF = 'X-On-Behalf-Of';
  const QUCOSA_TYPE = 'application/vnd.qucosa.mets+xml';
  
  
  public function __construct() {
    $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
    $this->swordHost = $confArr['swordHost'];
    $this->swordUser = $confArr['swordUser'];
    $this->swordPassword = $confArr['swordPassword'];
    $this->fedoraHost = $confArr['fedoraHost'];      
    $this->fedoraUser = $confArr['fedoraUser'];
    $this->fedoraPassword = $confArr['fedoraPassword'];
  }
  
  /**
   * Saves a new document into the Fedora repository
   * 
   * @param \EWW\Dpf\Domain\Model\Document $document
   * @return string
   */   
  public function ingest($document, $metsXml) {
              
    try {    
      $response = Request::post($this->swordHost . "/sword/qucosa:all")      
        ->sendsXml()
        ->body($metsXml)
        ->authenticateWith($this->swordUser, $this->swordPassword)     
        ->sendsType(FedoraRepository::QUCOSA_TYPE)
        ->addHeader(FedoraRepository::X_ON_BEHALF_OF,$this->getOwnerId())  
        ->addHeader('Slug',$document->getReservedObjectIdentifier())  
        ->send();
                                                                             
      TransferLogger::Log('INGEST',$document->getUid(), NULL, $response);
      
      // if transfer successful 
      if ( !$response->hasErrors() && $response->code == 201 ) {
        return $this->getRemoteDocumentId($response);        
      }                             
    } catch(Exception $exception) {
      // curl error handling,
      // but extbase already catches all exceptions
      return NULL;
    }
                          
    return NULL;        
  }
         
  
  /**
   * Updates an existing document in the Fedora repository
   * 
   * @param \EWW\Dpf\Domain\Model\Document $document
   * @param string $metsXml
   * @return string
   */
  public function update($document, $metsXml) {
    
    $remoteId = $document->getObjectIdentifier();
    
    try {    
      $response = Request::put($this->swordHost . "/sword/qucosa:all/" . $remoteId)      
        ->sendsXml()
        ->body($metsXml)
        ->authenticateWith($this->swordUser, $this->swordPassword)     
        ->sendsType(FedoraRepository::QUCOSA_TYPE)
        ->addHeader(FedoraRepository::X_ON_BEHALF_OF,$this->getOwnerId())   
        ->send();
                                                                             
      TransferLogger::Log('UPDATE',$document->getUid(), $remoteId, $response);
      
      // if transfer successful 
      if ( !$response->hasErrors() && $response->code == 200 ) {
        return $this->getRemoteDocumentId($response);        
      }                             
    } catch(Exception $exception) {
      // curl error handling,
      // but extbase already catches all exceptions
      return NULL;
    }     
  }
  
  
  /**
   * Gets an existing document from the Fedora repository
   * 
   * @param string $remoteId
   * @return string
   */
  public function retrieve($remoteId) {
                     
//    fedora/objects/qucosa:136/methods/qucosa:SDef/getMETSDissemination
   
   try {    
      $response = Request::get($this->fedoraHost . "/fedora/objects/" . $remoteId . "/methods/qucosa:SDef/getMETSDissemination")             
        ->authenticateWith($this->fedoraUser, $this->fedoraPassword)     
        ->addHeader(FedoraRepository::X_ON_BEHALF_OF,$this->getOwnerId())   
        ->send();
                                                                             
      TransferLogger::Log('RETRIEVE',NULL, $remoteId, $response);
                                          
      // if transfer successful 
      if ( !$response->hasErrors() && $response->code == 200 ) {                                       
        return $response->__toString();                               
      }                             
    } catch(Exception $exception) {
      // curl error handling,
      // but extbase already catches all exceptions
      return NULL;
    }     
              
    return NULL;
  }
   
  
  /**
   * Reserves a new DocumentId (qucosa id) 
   * 
   * @param string $remoteId
   * @return string
   */
  public function getNextDocumentId() {
                     
//    fedora/objects/qucosa:136/methods/qucosa:SDef/getMETSDissemination
   
   try {    
      $response = Request::get($this->fedoraHost . "/fedora/management/getNextPID?numPIDs=1&namespace=qucosa&xml=true")             
        ->authenticateWith($this->fedoraUser, $this->fedoraPassword)     
        ->addHeader(FedoraRepository::X_ON_BEHALF_OF,$this->getOwnerId())
        //->addHeader()      
        ->send();
                                                                                           
      TransferLogger::Log('GET_NEXT_DOCUMENT_ID',NULL, $remoteId, $response);
                 
      // if transfer successful 
      if ( !$response->hasErrors() && $response->code == 200 ) {                                    
        return $response->__toString();                               
      }                            
    } catch(Exception $exception) {
      // curl error handling,
      // but extbase already catches all exceptions       
      return NULL;
    }     
              
    return NULL;
  }
  
  /**
   * Removes an existing document from the Fedora repository
   * 
   * @param \EWW\Dpf\Domain\Model\Document $document
   * @return boolean
   */
  public function delete($document) {
    
     $remoteId = $document->getObjectIdentifier();
    
    try {    
      $response = Request::delete($this->swordHost . "/sword/qucosa:all/". $remoteId)               
        ->authenticateWith($this->swordUser, $this->swordPassword) 
        ->addHeader(FedoraRepository::X_ON_BEHALF_OF,$this->getOwnerId())   
        ->send();
                                                                             
      TransferLogger::Log('DELETE',$document->getUid(), $remoteId, $response);
      
      // if transfer successful 
      if ( !$response->hasErrors() && $response->code == 204 ) {
        return TRUE;     
      }                             
    } catch(Exception $exception) {
      // curl error handling,
      // but extbase already catches all exceptions
      return FALSE;
    }
                          
    return FALSE;        
  }
  
  /**
   * Gets the remoteDocumentId from the repository XML response.
   * 
   * @param  \Httpful\Response $response
   * @return string
   */
  protected function getRemoteDocumentId($response) {
            
    // Get repository ID and write into document
    $responseDom = new \DOMDocument();
    $responseDom->loadXML($response->raw_body);
    $responseXpath = new \DOMXPath($responseDom);  
    $responseXpath->registerNamespace("atom", "http://www.w3.org/2005/Atom");        
    $responseNodes = $responseXpath->query("/atom:entry/atom:id");
                   
    if ($responseNodes->length > 0) {                     
      $objectIdentifier = $responseNodes->item(0)->nodeValue;                 
      return $objectIdentifier;
    } 
    
    return NULL;      
  }
      
  
  protected function getOwnerId() {    
    
    if (empty($this->ownerId)) {
      $client = $this->clientRepository->findAll()->current();  
      $this->ownerId = $client->getOwnerId();
    }
    
    if (empty($this->ownerId)) {
        throw new \Exception('Owner id can not be empty or null!');
    }
    
    return $this->ownerId;
  }
  
}


?>
