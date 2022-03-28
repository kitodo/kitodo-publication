<?php

namespace EWW\Dpf\Services\Suggestion;

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

use EWW\Dpf\Domain\Model\DocumentFormField;

class FieldChange implements Change
{
    /**
     * @var DocumentFormField
     */
    protected $oldField;

    /**
     * @var DocumentFormField
     */
    protected $newField;

    /**
     * @var bool
     */
    protected $deleted = false;

    /**
     * @var bool
     */
    protected $added = false;

    /**
     * FieldChange constructor.
     * @param DocumentFormField|null $oldField
     * @param DocumentFormField|null $newField
     */
    public function __construct($oldField, $newField)
    {
        $this->oldField = $oldField;
        $this->newField = $newField;
    }

    /**
     * @var bool
     */
    protected $accepted = false;

    public function accept()
    {
        $this->accepted = true;
    }

    public function reject()
    {
        $this->accepted = false;
    }

    public function isAccepted()
    {
        return $this->accepted;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted = true): void
    {
        $this->deleted = $deleted;
    }

    /**
     * @return bool
     */
    public function isAdded(): bool
    {
        return $this->added;
    }

    /**
     * @param bool $added
     */
    public function setAdded(bool $added = true): void
    {
        $this->added = $added;
    }

    /**
     * @return DocumentFormField
     */
    public function getOldField(): ?DocumentFormField
    {
        return $this->oldField;
    }

    /**
     * @return DocumentFormField
     */
    public function getNewField(): ?DocumentFormField
    {
        return $this->newField;
    }

    public function getFieldId()
    {
        if ($this->newField) {
            return $this->newField->getId();
        }

        return $this->oldField->getId();
    }

}
