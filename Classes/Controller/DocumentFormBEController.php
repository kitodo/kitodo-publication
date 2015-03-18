<?php
namespace EWW\Dpf\Controller;

class DocumentFormBEController extends AbstractDocumentFormController {  
  
  
  public function __construct() {
    parent::__construct();
           
  }
    
  protected function redirectToList() {   
    $this->forward('list','Document',NULL,array());    
  }
                              
}

?>
