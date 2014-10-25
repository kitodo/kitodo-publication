<?php
namespace EWW\Dpf\Helper;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class FormField {


    /**
     * uid
     *
     * @var integer
     */
    protected $uid;


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
     * inputField
     *
     * @var string
     */
    protected $inputField;


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
     * @return string
     */
    public function getInputField() {
      return $this->inputField;
    }


    /**
     *
     * @param string $displayName
     */
    public function setInputField($inputField) {
      $this->inputField = $inputField;
    }

}


?>
