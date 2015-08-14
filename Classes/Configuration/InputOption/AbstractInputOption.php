<?php
namespace EWW\Dpf\Configuration\InputOption;

class AbstractInputOption {
    
    /**
     * values
     * 
     * @var array
     */
    protected $values = array();
    
    
    public function getValues() {        
        return $this->values;
    }
    
    
    
}


?>