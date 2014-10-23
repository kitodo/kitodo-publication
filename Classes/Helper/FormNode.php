<?php
namespace EWW\Dpf\Helper;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 /**
  * FormFieldset
  */
  class FormNode {
    
    /**
     * name
     *
     * @var string
     */
    protected $name;
    
 
    /**
     * children
     * 
     * @var array
     */
    protected $children;
    
   
    /**
     * 
     * @return string
     */
    public function getName() {
      return $this->name;      
    }

    
    /**
     * 
     * @param string $name
     */
    public function setName($name) {
      $this->name = $name;      
    }

    
    /**
     * 
     * @return array
     */
    public function getChildren() {
      return $this->children;      
    }
    
    
    /**
     * 
     * @param array $children
     */
    public function setChildren($children) {
      $this->children = $children;      
    }
    
    
    /**
     * 
     * @param \EWW\Dpf\Helper\FormNode $child
     */
    public function addChild($child) {
      $this->children[] = $child;
    }
  }

?>
