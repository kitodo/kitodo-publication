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
                      
          $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData()); 
              
          $args['title'] = $document->getTitle();
          $args['author'] = implode("; ", $document->getAuthors());
          $args['urn'] =  implode("; ", $mods->getUrnList());
          $args['date'] = (new \DateTime)->format("d-m-Y H:i:s");             
         
          
          $client = $this->clientRepository->findAll()->current();          
          if ($client) {              
            $clientAdminEmail = $client->getAdminEmail();
            if ($clientAdminEmail) {
                $adminReceiver = array();
                $adminReceiver[$clientAdminEmail] = $clientAdminEmail;              
                $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
                ->setFrom(array('noreply@qucosa.de' => 'noreply@qucosa.de'))
                ->setTo($adminReceiver)
                ->setSubject(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:newDocument_notification.subject','dpf'))                                                 
                ->setBody(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:newDocument_notification.body','dpf',$args));
                $message->send();           
                /*if($message->isSent()) {                    
                } else {                    
                }*/
            }  
          }
          
         
          $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
          $submitterEmail = $slub->getSubmitterEmail();
          if ($submitterEmail) {              
            $emailReceiver = array();
            $emailReceiver[$submitterEmail] = $submitterEmail;                                                 
            if ($emailReceiver) {                                                                                             
                $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
                ->setFrom(array('noreply@qucosa.de' => 'noreply@qucosa.de'))
                ->setTo($emailReceiver)
                ->setSubject(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:newDocument_notification.subject','dpf'))                                                 
                ->setBody(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:newDocument_notification.body','dpf',$args));
                $message->send();           
                /*if($message->isSent()) {                    
                } else {                    
                }*/
            }  
          }
   }
   
   
    public function sendIngestNotification(\EWW\Dpf\Domain\Model\Document $document) {
       
        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData()); 
              
        $args['title'] = $document->getTitle();
        $args['author'] = implode("; ", $document->getAuthors());
        $args['urn'] =  implode("; ", $mods->getUrnList());
        $args['date'] = (new \DateTime)->format("d-m-Y H:i:s");   
        
        $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData()); 
       
        $submitterEmail = $slub->getSubmitterEmail();
        if ($submitterEmail) {              
            $emailReceiver = array();
            $emailReceiver[$submitterEmail] = $submitterEmail;                                                 
            if ($emailReceiver) {                                                                                             
                $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
                ->setFrom(array('noreply@qucosa.de' => 'noreply@qucosa.de'))
                ->setTo($emailReceiver)
                ->setSubject(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:newDocument_notification.subject','dpf'))                                                 
                ->setBody(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:newDocument_notification.body','dpf',$args));
                $message->send();           
                /*if($message->isSent()) {                    
                } else {                    
                }*/
            }  
        }                
    }    
}


?>
