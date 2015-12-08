<?php
namespace EWW\Dpf\Controller;

class DocumentFormController extends AbstractDocumentFormController {  
  
  
  public function __construct() {
    parent::__construct();
                   
  }
  
  protected function redirectToList($success=FALSE) {
    $this->redirect('list','DocumentForm',NULL,array('success' => $success));    
  }
  
}

?>
