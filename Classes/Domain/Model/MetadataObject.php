<?php
namespace EWW\Dpf\Domain\Model;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * MetadataObject
 */
class MetadataObject extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * displayName
	 *
	 * @var string
	 */
	protected $displayName = '';

	/**
	 * maxIteration
	 *
	 * @var integer
	 */
	protected $maxIteration = 0;

	/**
	 * mandatory
	 *
	 * @var boolean
	 */
	protected $mandatory = FALSE;

	/**
	 * mapping
	 *
	 * @var string
	 */
	protected $mapping = '';

	/**
	 * inputField
	 *
	 * @var integer
	 */
	protected $inputField = 0;
        
        /**
         * selectOptions
         * 
         * @var string
         */        
        protected $selectOptions = '';
        
        const input = 0;
        const textarea = 1;         
        const language = 2;   
        const license = 3;   
        const select = 4;   
        const checkbox = 5;
        
        /**
	 * modsExtension
	 *
	 * @var boolean
	 */
	protected $modsExtension = FALSE;
        
        
        /**
	 * inputOptionList
	 *
	 * @var \Eww\Dpf\Domain\Model\InputOptionList
	 */
	protected $inputOptionList = NULL;
        
        
	/**
	 * Returns the name
	 *
	 * @return string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the displayName
	 *
	 * @return string $displayName
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * Sets the displayName
	 *
	 * @param string $displayName
	 * @return void
	 */
	public function setDisplayName($displayName) {
		$this->displayName = $displayName;
	}

	/**
	 * Returns the maxIteration
	 *
	 * @return integer $maxIteration
	 */
	public function getMaxIteration() {
		return $this->maxIteration;
	}

	/**
	 * Sets the maxIteration
	 *
	 * @param integer $maxIteration
	 * @return void
	 */
	public function setMaxIteration($maxIteration) {
		$this->maxIteration = $maxIteration;
	}

	/**
	 * Returns the mandatory
	 *
	 * @return boolean $mandatory
	 */
	public function getMandatory() {
		return $this->mandatory;
	}

	/**
	 * Sets the mandatory
	 *
	 * @param boolean $mandatory
	 * @return void
	 */
	public function setMandatory($mandatory) {
		$this->mandatory = $mandatory;
	}

	/**
	 * Returns the boolean state of mandatory
	 *
	 * @return boolean
	 */
	public function isMandatory() {
		return $this->mandatory;
	}
        
        /**
	 * Returns the selectOptions
	 *
	 * @return array $selectOptions
	 */
	public function getSelectOptions() {    
                $options = explode("|",trim($this->selectOptions," "));                        
                
                $selectOptions = array();
                
                foreach ($options as $value) {                    
                    $selectOptions[$value] = $value;
                }                
                
                return $selectOptions;
	}
        
        /**
	 * Sets the selectOptions
	 *
	 * @param array $selectOptions
	 * @return void
	 */
	public function setSelectOptions($selectOptions) {                
		$this->selectOptions = implode("|",$selectOptions);
	}

                        
        /**
	 * Returns the modsExtension
	 *
	 * @return boolean $modsExtension
	 */
	public function getModsExtension() {
		return $this->modsExtension;
	}

	/**
	 * Sets the modsExtension
	 *
	 * @param boolean $modsExtension
	 * @return void
	 */
	public function setModsExtension($modsExtension) {
		$this->modsExtension = $modsExtension;
	}

	/**
	 * Returns the boolean state of modsExtension
	 *
	 * @return boolean
	 */
	public function isModsExtension() {
		return $this->modsExtension;
	}
                                                                                                

	/**
	 * Returns the mapping
	 *
	 * @return string $mapping
	 */
	public function getMapping() {                                                
               return $this->mapping;
	}

	/**
	 * Sets the mapping
	 *
	 * @param string $mapping
	 * @return void
	 */
	public function setMapping($mapping) {
		$this->mapping = $mapping;
	}
        
        /**
	 * Returns the relative mapping
	 *
	 * @return string $relativeMapping
	 */
        public function getRelativeMapping() {
            $modsRegExp = "/^.*?mods:mods/i";        
            $mapping =  preg_replace($modsRegExp,"",$this->mapping);
            return trim($mapping," /");          
        }

                        
	/**
	 * Returns the inputField
	 *
	 * @return integer $inputField
	 */
	public function getInputField() {
		return $this->inputField;
	}

	/**
	 * Sets the inputField
	 *
	 * @param integer $inputField
	 * @return void
	 */
	public function setInputField($inputField) {
		$this->inputField = $inputField;
	}

        /**
         * Returns always NULL because an Object never has children.
         *
 	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject> $metadataObject
         */
        public function getChildren() {
           return NULL;
        }
        
        
        /**
	 * Returns the inputOptionList
	 *
	 * @return \Eww\Dpf\Domain\Model\InputOptionList $inputOptionList
	 */
	public function getInputOptionList() {
		return $this->inputOptionList;
	}

	/**
	 * Sets the inputOptionList
	 *
	 * @param \Eww\Dpf\Domain\Model\InputOptionList $inputOptionList
	 * @return void
	 */
	public function setInputOptionList(\Eww\Dpf2\Domain\Model\InputOptionList $inputOptionList) {
		$this->inputOptionList = $inputOptionList;
	}


}