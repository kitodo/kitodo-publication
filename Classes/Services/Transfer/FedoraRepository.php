<?php
namespace EWW\Dpf\Services\Transfer;

$extpath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');

require_once($extpath . '/Lib/Vendor/Httpful/bootstrap.php');
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
  
  
  public function __construct() {
    $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
    $this->swordHost = $confArr['swordHost'];
    $this->swordUser = $confArr['swordUser'];
    $this->swordPassword = $confArr['swordPassword'];
    $this->fedoraHost = $confArr['fedoraHost'];
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
        ->sendsType('application/vnd.qucosa.mets+xml')
        ->send();
                                                                             
      TransferLogger::Log($document, $response);
      
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
        ->sendsType('application/vnd.qucosa.mets+xml')
        ->send();
                                                                             
      TransferLogger::Log($document, $response);
      
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
   * @param \EWW\Dpf\Domain\Model\Document $id
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
    
     $remoteId = $document->getObjectIdentifier();
    
    try {    
      $response = Request::delete($this->swordHost . "/sword/qucosa:all/". $remoteId)               
        ->authenticateWith($this->swordUser, $this->swordPassword)            
        ->send();
                                                                             
      TransferLogger::Log($document, $response);
      
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
      
}


?>
