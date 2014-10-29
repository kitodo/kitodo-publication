<?php
namespace EWW\Dpf\Helper;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class FormGroup extends \EWW\Dpf\Helper\FormNode {

    /**
     * mandatory
     * 
     * @var boolean
     */
    protected $mandatory;
    

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

    
    /**
     *
     * @return boolean
     */
    public function hasMandatoryFields() {
        foreach ($this->children as $object) {
            if ($object->isMandatory() || $this->mandatory) {
                return TRUE;
            }
        }
        return FALSE;
    }
}


?>
