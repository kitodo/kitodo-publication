<?php
namespace EWW\Dpf\Controller;

class DocumentFormBEController extends AbstractDocumentFormController {  
  
  
  public function __construct() {
    parent::__construct();
           
  }
    
  protected function redirectToList() {   
    $this->redirect('list','Document',NULL,array());    
  }
  
  
}

?>
