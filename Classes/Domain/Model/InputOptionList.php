<?php
namespace Eww\Dpf\Domain\Model;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015
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
 * InputOptionList
 */
class InputOptionList extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

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
         * valueList
         * 
         * @var string
         */
        protected $valueList = '';
        
        
        /**
         * valueLabelList
         * 
         * @var string
         */
        protected $valueLabelList = '';
        
        
        /**
         * defaultValue
         * 
         * @var string
         */
        protected $defaultValue = '';
        
        
	/**
	 * inputOptions
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Eww\Dpf\Domain\Model\InputOption>
	 */
	protected $inputOptions = NULL;

	/**
	 * __construct
	 */
	public function __construct() {
		
	}
	
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
	 * Returns the valueList
	 *
	 * @return string $valueList
	 */
	public function getValueList() {
		return $this->valueList;
	}

	/**
	 * Sets the valueList
	 *
	 * @param string $valueList
	 * @return void
	 */
	public function setValueList($valueList) {
		$this->valueList = $valueList;
	}
        
        
        /**
	 * Returns the valueLabelList
	 *
	 * @return string $valueLabelList
	 */
	public function getValueLabelList() {
		return $this->valueLabelList;
	}

	/**
	 * Sets the valueLabelList
	 *
	 * @param string $valueLabelList
	 * @return void
	 */
	public function setValueLabelList($valueLabelList) {
		$this->valueLabelList = $valueLabelList;
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
	 * Returns the inputOptions
	 *
	 * @return array $inputOptions
	 */
	public function getInputOptions() {
            
                $values = explode("|",$this->getValueList());
                $labels = explode("|",$this->getValueLabelList());
            
                if (sizeof($values) !=  sizeof($labels)) {
                    throw new \Exception('Invalid input option list configuration.');
                }
                
		return array_combine($values, $labels);
	}

	/**
	 * Sets the inputOptions
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Eww\Dpf\Domain\Model\InputOption> $inputOptions
	 * @return void
	 */
	public function setInputOptions(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $inputOptions) {
		$this->inputOptions = $inputOptions;
	}
        
        
        public function setL10nParent($l10nParent) {
            $this->l10nParent = $l10nParent;   
        }
    
           
        public function setSysLanguageUid($sysLanguageUid) {
            $this->_languageUid = $sysLanguageUid;   
        }
        
        /**
	 * Returns the defaultValue
	 *
	 * @return string $defaultValue
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}

	/**
	 * Sets the defaultValue
	 *
	 * @param string $defaultValue
	 * @return void
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
	}

}