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

use EWW\Dpf\Services\Suggestion\FieldChange;
use EWW\Dpf\Services\Suggestion\GroupChange;
use PhpParser\Comment\Doc;

class DocumentFormGroup extends AbstractFormElement
{

    /**
     * infoText
     *
     * @var string
     */
    protected $infoText;

    /**
     * @var string
     */
    protected $groupType;

    /**
     * @var bool
     */
    protected $emptyGroup = false;

    /**
     * @var string
     */
    protected $optionalGroups = '';

    /**
     * @var string
     */
    protected $requiredGroups = '';

    /**
     * string
     */
    protected $id = '';

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
     * @return mixed
     */
    public function getGroupType()
    {
        return $this->groupType;
    }

    /**
     * @param mixed $groupType
     */
    public function setGroupType($groupType)
    {
        $this->groupType = $groupType;
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
        $this->emptyGroup = boolval($emptyGroup);
    }

    /**
     * @return string
     */
    public function getOptionalGroups(): string
    {
        return $this->optionalGroups;
    }

    /**
     * @param string $optionalGroups
     */
    public function setOptionalGroups(string $optionalGroups): void
    {
        $this->optionalGroups = $optionalGroups;
    }

    /**
     * @return string
     */
    public function getRequiredGroups(): string
    {
        return $this->requiredGroups;
    }

    /**
     * @param string $requiredGroups
     */
    public function setRequiredGroups(string $requiredGroups): void
    {
        $this->requiredGroups = $requiredGroups;
    }

    /**
     * @return bool
     */
    public function isPrimaryFileGroup()
    {
        return $this->groupType == 'primary_file';
    }

    /**
     * @return bool
     */
    public function isFileGroup()
    {
        return strpos($this->groupType, 'file') !== false;
    }

    public function getFile() {
        foreach ($this->getItems() as $fieldItems) {
            /** @var DocumentFormField $fieldItem */
            foreach ($fieldItems as $field) {
                $file = $field->getFile();
                if ($file instanceof File) {
                    return $file;
                }
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function addItem($item)
    {
        $uid = $item->getUid();

        if ($item->getId()) {
            $id = explode('-', $item->getId());
            $index = $id[3];
        } else {
            $index = 0;
        }

        $this->items[$uid][$index] = $item;
    }

    /**
     * @param GroupChange $GroupChange
     */
    public function applyChanges(GroupChange $groupChange)
    {
        /** @var FieldChange $fieldChange */
        foreach ($groupChange->getFieldChanges() as $fieldChange) {
            if ($fieldChange->isAccepted()) {
                $this->applyFieldChange($fieldChange);
            }
        }
    }

    /**
     * @param FieldChange $fieldChange
     */
    protected function applyFieldChange(FieldChange $fieldChange)
    {
            if ($fieldChange->isAdded()) {
                foreach ($this->getItems() as $keyField => $valueField) {
                    foreach ($valueField as $keyRepeatField => $valueRepeatField) {
                        if ($keyField === $fieldChange->getNewField()->getUid()) {
                            $this->addItem($fieldChange->getNewField());
                            return;
                        }
                    }
                }
            } elseif ($fieldChange->isDeleted()) {
                foreach ($this->getItems() as $keyField => $valueField) {
                    foreach ($valueField as $keyRepeatField => $valueRepeatField) {
                        if ($valueRepeatField->getId() === $fieldChange->getFieldId()) {
                            $this->removeItem($fieldChange->getOldField());
                            return;
                        }
                    }
                }
            } else {
                foreach ($this->getItems() as $keyField => $valueField) {
                    foreach ($valueField as $keyRepeatField => $valueRepeatField) {
                        if ($valueRepeatField->getId() === $fieldChange->getFieldId()) {
                            $this->replaceItem($fieldChange->getNewField());
                            return;
                        }
                    }
                }
            }
        }
}
