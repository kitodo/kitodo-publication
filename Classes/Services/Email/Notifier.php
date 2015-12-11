<?php
namespace EWW\Dpf\Services\Email;

class Notifier {
    
   /**
    * clientRepository
    *
    * @var \EWW\Dpf\Domain\Repository\ClientRepository
    * @inject
    */
   protected $clientRepository = NULL;
    
    
   public function sendNewDocumentNotification(\EWW\Dpf\Domain\Model\Document $document) {
          
       try {
          $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData()); 
          $urn = $mods->getQucosaUrn();
          
          $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
          $submitterName = $slub->getSubmitterName();
                                                
          // Notify client admin
          $client = $this->clientRepository->findAll()->current();          
          if ($client) {     
            $args['submitterName'] = $submitterName;
            $args['title'] = $document->getTitle();
            $args['author'] = implode("; ", $document->getAuthors());
            $args['urn'] =  $urn;
            $args['date'] = (new \DateTime)->format("d-m-Y H:i:s");     
            $clientAdminEmail = $client->getAdminEmail();
            if ($clientAdminEmail) {
                $adminReceiver = array();
                $adminReceiver[$clientAdminEmail] = $clientAdminEmail;              
                $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
                ->setFrom(array('noreply@qucosa.de' => 'noreply@qucosa.de'))
                ->setTo($adminReceiver)
                ->setSubject(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.admin.subject','dpf'))                                                 
                ->setBody(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.admin.body','dpf',$args));
                $message->send();           
                /*if($message->isSent()) {                    
                } else {                    
                }*/
            }  
          }
          
          // Notify submitter
          $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
          $submitterEmail = $slub->getSubmitterEmail();
          if ($submitterEmail) {              
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
            $submitterName = $slub->getSubmitterName();
            $args['submitterName'] = $submitterName; 
            $args['title'] = $document->getTitle();
            $args['author'] = implode("; ", $document->getAuthors());
            $args['urn'] =  $urn;
            $args['date'] = (new \DateTime)->format("d-m-Y H:i:s");   
            $emailReceiver = array();
            $emailReceiver[$submitterEmail] = $submitterEmail;                                                 
            if ($emailReceiver) {                                                                                             
                $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
                ->setFrom(array('noreply@qucosa.de' => 'noreply@qucosa.de'))
                ->setTo($emailReceiver)
                ->setSubject(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.submitter.subject','dpf'))                                                 
                ->setBody(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.submitter.body','dpf',$args));
                $message->send();           
                /*if($message->isSent()) {                    
                } else {                    
                }*/
            }  
          }

        } catch(\Exception $e) {}  
          
   }
   
   
    public function sendIngestNotification(\EWW\Dpf\Domain\Model\Document $document) {
    
      try {
        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData()); 
        $urn = $mods->getQucosaUrn();
        $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
        $submitterName = $slub->getSubmitterName();        
        $args['submitterName'] = $submitterName;       
        $args['title'] = $document->getTitle();
        $args['author'] = implode("; ", $document->getAuthors());
        $args['urn'] =  'http://nbn-resolving.de/'.$urn;
        $args['date'] = (new \DateTime)->format("d-m-Y H:i:s"); 
        $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
       
        // Notify submitter
        $submitterEmail = $slub->getSubmitterEmail();
        if ($submitterEmail) {              
            $emailReceiver = array();
            $emailReceiver[$submitterEmail] = $submitterEmail;                                                 
            if ($emailReceiver) {                                                                                             
                $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
                ->setFrom(array('noreply@qucosa.de' => 'noreply@qucosa.de'))
                ->setTo($emailReceiver)
                ->setSubject(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.ingestDocument.submitter.subject','dpf'))                                                 
                ->setBody(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.ingestDocument.submitter.body','dpf',$args));
                $message->send();           
                /*if($message->isSent()) {                    
                } else {                    
                }*/
            }  
        }
      } catch(\Exception $e) {}  
  
    }    
}


?>
