<?php
namespace EWW\Dpf\Services\Logger;

class TransferLogger {
    
  /**
   * Logs the response of a document repository transfer    
   * 
   * @param 
   * @return void
   */
  static function log($document, $response) { 
    
    $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\Object\\ObjectManager');
    $documentTransferLogRepository = $objectManager->get('\\EWW\\Dpf\\Domain\\Repository\\DocumentTransferLogRepository');
        
    $documentTransferLog = $objectManager->get('\\EWW\\Dpf\\Domain\\Model\\DocumentTransferLog');            
    $documentTransferLog->setResponse(print_r($response,TRUE));      
    $documentTransferLog->setDocument($document);   
    $documentTransferLog->setDate(new \DateTime());   
    $documentTransferLogRepository->add($documentTransferLog);
  }
    
}

?>
