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
     * fieldType
     *
     * @var string
     */
    protected $fieldType;


    /**
     * fieldId
     *
     * @var fieldId
     */
    protected $fieldId;

    /**
     * value
     *
     * @var string
     */
    protected $value;


    /**
     * mandatory
     *
     * @var boolean
     */
    protected $mandatory;

    
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
     * @return integer
     */
    public function getFieldType() {
      return $this->fieldType;
    }


    /**
     *
     * @param integer $fieldType
     */
    public function setFieldType($fieldType) {
      $this->fieldType = $fieldType;
    }


    /**
     *
     * @return string
     */
    public function getValue() {
      return $this->value;
    }


    /**
     *
     * @param string $value
     */
    public function setValue($value) {
      $this->value = $value;
    }


    /**
     *
     * @return string
     */
    public function getFieldId() {
        return $this->fieldId;
    }

    
    /**
     *
     * @param $fieldId
     * @return void
     */
    public function setFieldId($fieldId) {
         $this->fieldId = $fieldId;
        
    }


    /**
     *
     * @return boolean
     */
    public function getMandatory() {
        return $this->mandatory;
    }


    /**
     *
     * @param boolean $mandatory
     */
    public function setMandatory($mandatory) {
        $this->mandatory = $mandatory;
    }
    

}




?>
