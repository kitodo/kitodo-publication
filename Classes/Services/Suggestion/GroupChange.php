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
use EWW\Dpf\Domain\Model\DocumentFormGroup;
use EWW\Dpf\Domain\Model\File;

class GroupChange implements Change
{
    /**
     * @var DocumentFormGroup
     */
    protected $oldGroup;

    /**
     * @var DocumentFormGroup
     */
    protected $newGroup;

    /**
     * @var bool
     */
    protected $deleted = false;

    /**
     * @var bool
     */
    protected $added = false;

    /**
     * @var bool
     */
    protected $accepted = false;

    /**
     * @var array
     */
    protected $fieldChanges = [];

    /**
     * GroupChanges constructor.
     * @param DocumentFormGroup|null $oldGroup
     * @param DocumentFormGroup|null $newGroup
     */
    public function __construct($oldGroup, $newGroup)
    {
        $this->oldGroup = $oldGroup;
        $this->newGroup = $newGroup;
    }

    /**
     * @param FieldChange $fieldChange
     */
    public function addFieldChange(FieldChange $fieldChange)
    {
        $this->fieldChanges[$fieldChange->getFieldId()] = $fieldChange;
    }

    /**
     * @param string $id
     * @return FieldChange|null
     */
    public function getFieldChange(string $id): ?FieldChange
    {
        /** @var FieldChange $fieldChange */
        foreach ($this->fieldChanges as $fieldChange) {
            if ($fieldChange->getOldField() instanceof DocumentFormField) {
                if ($fieldChange->getOldField()->getId() === $id) {
                    return $fieldChange;
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasFieldChanges(): bool
    {
        return !empty($this->fieldChanges);
    }

    /**
     * @return bool
     */
    public function hasAcceptedFieldChanges(): bool
    {
        if (!$this->hasFieldChanges()) {
            return true;
        }

        /** @var FieldChange $fieldChange */
        foreach ($this->fieldChanges as $fieldChange) {
            if ($fieldChange->isAccepted()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getFieldChanges(): array
    {
        return $this->fieldChanges;
    }

    /**
     * @param string $fieldId
     */
    public function acceptField(string $fieldId)
    {
        if (array_key_exists($fieldId, $this->fieldChanges)) {
            $this->fieldChanges[$fieldId]->accept();
        }
    }

    public function accept()
    {
        //foreach ($this->fieldChanges as $change) {
        //    $change->accept();
        //}

        $this->accepted = true;
    }

    public function reject()
    {
        foreach ($this->fieldChanges as $change) {
            $change->reject();
        }

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
     * @return File
     */
    public function getNewFile(): File
    {
        return $this->newFile;
    }

    /**
     * @return DocumentFormGroup
     */
    public function getGroup(): DocumentFormGroup
    {
        if ($this->isDeleted()) {
            return $this->oldGroup;
        }

        if ($this->isAdded()) {
            return $this->newGroup;
        }

        return $this->oldGroup;
    }

    /**
     * @return DocumentFormGroup
     */
    public function getOldGroup(): ?DocumentFormGroup
    {
        return $this->oldGroup;
    }

    /**
     * @return DocumentFormGroup
     */
    public function getNewGroup(): ?DocumentFormGroup
    {
        return $this->newGroup;
    }

}

