<?php
namespace EWW\Dpf\Domain\Model;

class AbstractFormElement {
  
  protected $uid;
  
  protected $displayName;
  
  protected $name;
  
  protected $items;
  
  protected $mandatory;
  
  protected $maxIteration;
  
  
  public function getUid() {
    return $this->uid;    
  }
  
  
  public function setUid($uid) {
    $this->uid = $uid;     
  }
  
  
  public function getDisplayName() {
    return $this->displayName;    
  }
  
  
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;     
  }
  
  
  public function getName() {
    return $this->name;    
  }
  
  
  public function setName($name) {
    $this->name = $name;     
  }
  
  
  public function getItems() {
    return $this->items;
  }
  
  
  public function addItem($item) {   
    $uid = $item->getUid();    
    $this->items[$uid][] = $item;
  }
 
  
  public function getMandatory() {
    return $this->mandatory;    
  }
  
  
  public function setMandatory($mandatory) {
    $this->mandatory = $mandatory;     
  }

  
  public function getMaxIteration() {
    return $this->maxIteration;    
  }
  
  
  public function setMaxIteration($maxIteration) {
    $this->maxIteration = $maxIteration;     
  }
  
}

?>
