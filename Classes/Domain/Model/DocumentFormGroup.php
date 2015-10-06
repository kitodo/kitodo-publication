<?php
namespace EWW\Dpf\Domain\Model;

class DocumentFormGroup extends AbstractFormElement {
      
  
  /**
   * infoText
   * 
   * @var string
   */
  protected $infoText;  
    
    
  /**
   * Returns the infoText
   * 
   * @return string $infoText
   */
  public function getInfoText() {
    return $this->infoText;
  }

  /**
   * Sets the infoText
   * 
   * @param string $infoText
   * @return void
   */
  public function setInfoText($infoText) {
    $this->infoText = $infoText;
  }
    
}

?>
