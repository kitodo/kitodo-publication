<?php
namespace EWW\Dpf\Domain\Model;

class Language {       
  
  protected $nameLocalized;  
  
  protected $isoCodeA3;  
  
  
  public function getNameLocalized() {
    return $this->nameLocalized;
  }
  
  public function setNameLocalized($nameLocalized) {
    $this->nameLocalized = $nameLocalized;
  }
  
  
  public function getIsoCodeA3() {
    return $this->isoCodeA3;
  }
  
  public function setIsoCodeA3($isoCodeA3) {
    $this->isoCodeA3 = $isoCodeA3;
  }
  
}

?>
