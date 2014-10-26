<?php
namespace EWW\Dpf\Helper;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 /**
  * FormNode
  */
  abstract class FormNode {

    
    /**
     * name
     *
     * @var string
     */
    protected $name;

    /**
     * name
     *
     * @var string
     */
    protected $displayName;
 
    /**
     * children
     * 
     * @var array
     */
    protected $children;
    

    /**
     * uid
     *
     * @var integer
     */
    protected $uid;

    /**
     *
     * @return integer
     */
    public function getUid() {
      return $this->uid;
    }


    /**
     *
     * @param integer $uid
     */
    public function setUid($uid) {
      $this->uid = $uid;
    }


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
     * @return string
     */
    public function getDisplayName() {
      return $this->displayName;
    }


    /**
     *
     * @param string $displayName
     */
    public function setDisplayName($displayName) {
      $this->displayName = $displayName;
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
