<?php
namespace EWW\Dpf\Controller;

class DocumentFormBEController extends AbstractDocumentFormController {  
  
  
  public function __construct() {
    parent::__construct();
           
  }
    
  protected function redirectToList() {   
    $this->redirect('list','Document',NULL,array());    
  }
  
  /**
    * action delete
    *
    * @param array $documentData
    * @return void
    */
    public function deleteAction($documentData) {

       if ( !$GLOBALS['BE_USER'] ) {
           throw new \Exception('Access denied');
       }      

       $document = $this->documentRepository->findByUid($documentData['documentUid']);
       $document->setRemoteAction(\Eww\Dpf\Domain\Model\Document::REMOTE_ACTION_DELETE);
       $document = $this->documentRepository->update($document);

       $this->redirectToList();
    } 
    }

?>
