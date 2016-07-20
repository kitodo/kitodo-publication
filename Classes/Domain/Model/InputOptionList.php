<?php
namespace Eww\Dpf\Domain\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * InputOptionList
 */
class InputOptionList extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

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
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the displayName
     *
     * @return string $displayName
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Returns the valueList
     *
     * @return string $valueList
     */
    public function getValueList()
    {
        return $this->valueList;
    }

    /**
     * Sets the valueList
     *
     * @param string $valueList
     * @return void
     */
    public function setValueList($valueList)
    {
        $this->valueList = $valueList;
    }

    /**
     * Returns the valueLabelList
     *
     * @return string $valueLabelList
     */
    public function getValueLabelList()
    {
        return $this->valueLabelList;
    }

    /**
     * Sets the valueLabelList
     *
     * @param string $valueLabelList
     * @return void
     */
    public function setValueLabelList($valueLabelList)
    {
        $this->valueLabelList = $valueLabelList;
    }

    /**
     * Sets the displayName
     *
     * @param string $displayName
     * @return void
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * Returns the inputOptions
     *
     * @return array $inputOptions
     * @throws \Exception
     */
    public function getInputOptions()
    {

        $values = explode("|", $this->getValueList());
        $labels = explode("|", $this->getValueLabelList());

        if (sizeof($values) != sizeof($labels)) {
            throw new \Exception('Invalid input option list configuration.');
        }

        return array_combine($values, $labels);
    }

    public function setL10nParent($l10nParent)
    {
        $this->l10nParent = $l10nParent;
    }

    public function setSysLanguageUid($sysLanguageUid)
    {
        $this->_languageUid = $sysLanguageUid;
    }

    /**
     * Returns the defaultValue
     *
     * @return string $defaultValue
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Sets the defaultValue
     *
     * @param string $defaultValue
     * @return void
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

}
