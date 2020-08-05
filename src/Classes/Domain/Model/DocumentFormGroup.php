<?php
namespace EWW\Dpf\Domain\Model;

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

class DocumentFormGroup extends AbstractFormElement
{

    /**
     * infoText
     *
     * @var string
     */
    protected $infoText;

    /**
     * @var bool
     */
    protected $emptyGroup = false;

    /**
     * Returns the infoText
     *
     * @return string $infoText
     */
    public function getInfoText()
    {
        return $this->infoText;
    }

    /**
     * Sets the infoText
     *
     * @param string $infoText
     * @return void
     */
    public function setInfoText($infoText)
    {
        $this->infoText = $infoText;
    }

    /**
     * @return bool
     */
    public function isEmptyGroup(): bool
    {
        return $this->emptyGroup;
    }

    /**
     * @param bool $emptyGroup
     */
    public function setEmptyGroup(bool $emptyGroup): void
    {
        $this->emptyGroup = $emptyGroup;
    }

}
