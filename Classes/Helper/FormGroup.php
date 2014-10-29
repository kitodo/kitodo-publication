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
     * @var boolean
     */
    protected $last;

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
    public function getHasMandatoryFields() { 
        foreach ($this->children as $object) {
            if ($object->getMandatory() || $this->mandatory) {
                return TRUE;
            }
        }
        return FALSE;
    }


    /**
     *
     * @param boolean $last
     */
    public function setLast($last) {
        $this->last = $last;
    }


    /**
     *
     * @return boolean
     */
    public function getLast() {
        return $this->last;
    }
}


?>
