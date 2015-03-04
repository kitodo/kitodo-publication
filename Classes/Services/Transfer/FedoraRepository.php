<?php
namespace EWW\Dpf\Services\Transfer;

$extpath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');

require($extpath . '/Lib/Vendor/Httpful/bootstrap.php');
\Httpful\Bootstrap::init();

//require($extpath . '/Lib/Vendor/Httpful/httpful.phar');

use \Httpful\Request;

class FedoraRepository implements Repository {
 
  /**
   * documentTransferLogRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentTransferLogRepository                                     
   * @inject
   */
  protected $documentTransferLogRepository;  
  
  
  /**
   * documenRepository
   *
   * @var \EWW\Dpf\Domain\Repository\DocumentRepository                                     
   * @inject
   */
  protected $documentRepository;  
  
  
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
   * @return boolean
   */   
  public function ingest($document) {
    
    $sentSuccessfully = FALSE;
        
    try {    
     $response = Request::post($this->swordHost . "/sword/qucosa:all")      
        ->sendsXml()
        ->body($document->getXmlData())
        ->authenticateWith($this->swordUser, $this->swordPassword)     
        ->sendsType('application/vnd.qucosa.mets+xml')
        ->send();
                                                                 
      $sentSuccessfully = !$response->hasErrors() && $response->code == 201;            
    } catch(Exception $exception) {
      // curl error handling,
      // but extbase already catches all exceptions
    }
          
    
    if ($sentSuccessfully) {
      
      // Get repository ID and write into document
      $responseDom = new \DOMDocument();
      $responseDom->loadXML($response->raw_body);
      $responseXpath = new \DOMXPath($responseDom);  
      $responseXpath->registerNamespace("atom", "http://www.w3.org/2005/Atom");        
      $responseNodes = $responseXpath->query("/atom:entry/atom:id");
                   
      if ($responseNodes->length == 1) {                     
        $repositoryId = $responseNodes->item(0)->nodeValue;
        $document->setRepositoryId($repositoryId);                                    
      } 
              
      $document->setTransferStatus(\EWW\Dpf\Domain\Model\Document::TRANSFER_SENT);             
      
    } else {            
      $document->setTransferStatus(\EWW\Dpf\Domain\Model\Document::TRANSFER_ERROR);                                   
    }
    
    $this->documentRepository->update($document);
        
    
    // Log transfer response
    $documentTransferLog = $this->objectManager->get('\EWW\Dpf\Domain\Model\DocumentTransferLog');            
    $documentTransferLog->setResponse(print_r($response,TRUE));      
    $documentTransferLog->setDocument($document);   
    $documentTransferLog->setDate(new \DateTime());   
    $this->documentTransferLogRepository->add($documentTransferLog);
    
    return $sentSuccessfully;        
  }
         
  
  /**
   * Updates an existing document in the Fedora repository
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
