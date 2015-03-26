<?php
namespace EWW\Dpf\Controller;


abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

    
  /**
    * clientRepository
    *
    * @var \EWW\Dpf\Domain\Repository\ClientRepository
    * @inject
    */
  protected $clientRepository = NULL;
        
  
  protected Function initializeView($view) {
    parent::initializeView($view);
    
    $client = $this->clientRepository->findAll()->current();
    
    if (!$client) {
      $this->addFlashMessage(
        "Es wurde kein gültiger Mandantenordner ausgewählt.",
        $messageTitle = '',
        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING,
        $storeInSession = TRUE
      );    
    } else {
      /*
      $this->addFlashMessage(
        "Mandantenordner: ".$client->getClient(),
        $messageTitle = '',
        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
        $storeInSession = TRUE
      );       
      */    
    }
    
    $view->assign('client',$client);  
      
  }
             
}
?>
