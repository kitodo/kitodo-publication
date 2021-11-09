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

use EWW\Dpf\Domain\Model\DocumentForm;
use EWW\Dpf\Domain\Model\DocumentFormField;
use EWW\Dpf\Domain\Model\DocumentFormGroup;

class DocumentChanges
{
    /**
     * @var DocumentForm
     */
    protected $original;

    /**
     * @var DocumentForm
     */
    protected $suggestion;

    /**
     * @var array
     */
    protected $changes = [];

    /**
     * DocumentChanges constructor.
     * @param DocumentForm $original
     * @param DocumentForm $suggestion
     */
    public function __construct(DocumentForm $original, DocumentForm $suggestion)
    {
        $this->original = $original;
        $this->suggestion = $suggestion;
    }

    /**
     * @return DocumentForm
     */
    public function getOriginal(): DocumentForm
    {
        return $this->original;
    }

    /**
     * @param DocumentForm $original
     */
    public function setOriginal(DocumentForm $original): void
    {
        $this->original = $original;
    }

    /**
     * @return DocumentForm
     */
    public function getSuggestion(): DocumentForm
    {
        return $this->suggestion;
    }

    /**
     * @param DocumentForm $suggestion
     */
    public function setSuggestion(DocumentForm $suggestion): void
    {
        $this->suggestion = $suggestion;
    }


    public function addChange(GroupChange $change)
    {
        $this->changes[$change->getGroup()->getId()] = $change;
    }

    /**
     * @param string $groupId
     */
    public function acceptGroup(string $groupId)
    {
        if (array_key_exists($groupId, $this->changes)) {
            $this->changes[$groupId]->accept();
        }
    }

    /**
     * @param string $groupId
     * @param string $fieldId
     */
    public function acceptField(string $groupId, string $fieldId)
    {
        if (array_key_exists($groupId, $this->changes)) {
            $this->changes[$groupId]->acceptField($fieldId);
        }
    }

    /**
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @param string $id
     * @return GroupChange|null
     */
    public function getGroupChange(string $id): ?GroupChange
    {
        /** @var GroupChange $groupChange */
        foreach ($this->changes as $groupChange) {
            if ($groupChange->getGroup() instanceof DocumentFormGroup) {
                if ($groupChange->getGroup()->getId() === $id) {
                    return $groupChange;
                }
            }
        }

        return null;
    }

    /**
     * Accept all group and field changes
     */
    public function acceptAll()
    {
        /** @var GroupChange $groupChange */
        foreach ($this->getChanges() as $groupChange) {
            $groupChange->accept();
            /** @var FieldChange $fieldChange */
            foreach ($groupChange->getFieldChanges() as $fieldChange) {
                $fieldChange->accept();
            }
        }
    }
}
