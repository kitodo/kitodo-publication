<?php

namespace EWW\Dpf\Domain\Model;

use EWW\Dpf\Services\Suggestion\DocumentChanges;
use EWW\Dpf\Services\Suggestion\FieldChange;
use EWW\Dpf\Services\Suggestion\GroupChange;
use Exception;
use TypeError;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

/*
 * This file is part of the TYPO3 CMS project.(
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

class DocumentForm extends AbstractFormElement
{

    /**
     * @var string CSRF token for this form
     */
    protected $csrfToken;

    /**
     *
     * @var integer
     */
    protected $documentUid;

    /**
     *
     * @var boolean
     */
    protected $primaryFileMandatory;

    /**
     * @var string
     */
    protected $reservedFedoraPid;

    /**
     *
     * @var string
     */
    protected $fedoraPid;

    /**
     *
     * @var array
     */
    protected $files;

    /**
     *
     * @var string
     */
    protected $objectState;

    /**
     *
     * @var boolean
     */
    protected $valid = false;

    /**
     *
     * @var string
     */
    protected $processNumber;

    /**
     * @var bool
     */
    protected $temporary;

    /**
     * @var string
     */
    protected $comment = '';

    /**
     * Assign and persist CSRF token for later form validation.
     *
     * @param string $csrfToken
     */
    public function generateCsrfToken()
    {
        $formProtection = FormProtectionFactory::get();
        $this->csrfToken = $formProtection->generateToken('DocumentForm', 'construct', 'DocumentForm');
        $formProtection->persistSessionToken();
    }

    /**
     * Set the CSRF token for this form
     *
     * Used when creating a new instance from request form data.
     *
     * @param string $csrfToken CSRF token to set
     * @throws Exception if the given string is empty.
     * @throws TypeError if the given string is null
     */
    public function setCsrfToken(string $csrfToken)
    {
        if ($csrfToken === "")
        {
            throw new Exception("A forms CSRF token cannot be empty");
        }
        $this->csrfToken = $csrfToken;
    }


    /**
     * Returns the CSRF token of this form
     *
     * @return string CSRF token for this form
     */
    public function getCsrfToken()
    {
        return $this->csrfToken;
    }


    /**
     * Validates this forms assigned CSRF token with token stored in the TYPO3 session.
     *
     * @return bool True, is CSRF token is considered valid. False if the token is invalid or missing.
     */
    public function hasValidCsrfToken()
    {
        $formProtection = FormProtectionFactory::get();
        return $formProtection->validateToken($this->csrfToken, 'DocumentForm', 'construct', 'DocumentForm');
    }

    /**
     *
     * @return integer
     */
    public function getDocumentUid()
    {
        return $this->documentUid;
    }

    /**
     *
     * @param integer $documentUid
     */
    public function setDocumentUid($documentUid)
    {
        $this->documentUid = $documentUid;
    }

    /**
     *
     * @return boolean
     */
    public function getPrimaryFileMandatory()
    {
        return $this->primaryFileMandatory;
    }

    /**
     *
     * @param boolean $primaryFileMandatory
     */
    public function setPrimaryFileMandatory($primaryFileMandatory)
    {
        $this->primaryFileMandatory = boolval($primaryFileMandatory);
    }

    /**
     *
     * @return string
     */
    public function getReservedFedoraPid()
    {
        return $this->reservedFedoraPid;
    }

    /**
     *
     * @param string $reservedFedoraPid
     */
    public function setReservedFedoraPid($reservedFedoraPid)
    {
        $this->reservedFedoraPid = $reservedFedoraPid;
    }

    /**
     *
     * @param string
     */
    public function getFedoraPid()
    {
        return $this->fedoraPid;
    }

    /**
     *
     * @param string $fedoraPid
     */
    public function setFedoraPid($fedoraPid)
    {
        $this->fedoraPid = $fedoraPid;
    }

    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param string $fileIdentifier
     * @return File
     */
    public function getFileByFileIdentifier(string $fileIdentifier)
    {
        if (is_array($this->files)) {
            /** @var File $file */
            foreach ($this->files as $file) {
                if ($file->getFileIdentifier() === $fileIdentifier) {
                    return $file;
                }
            }
        }
        return null;
    }

    /**
     * @return bool
     */
    public function hasFiles()
    {
        return is_array($this->files) && !empty($this->files);
    }

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function addFile($file)
    {
        $this->files[] = $file;
    }

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid($valid)
    {
        $this->valid = boolval($valid);
    }

    public function getFileNames()
    {
        $fileNames = array();
        foreach ($this->getFiles() as $file) {
            $fileNames[] = $file->getTitle();
        }
        return $fileNames;
    }

    /**
     * Sets the process number
     *
     * @return string
     */
    public function getProcessNumber()
    {
        return $this->processNumber;
    }

    /**
     * Gets the process number
     *
     * @param string $processNumber
     */
    public function setProcessNumber($processNumber)
    {
        $this->processNumber = $processNumber;
    }

    /**
     * Returns if a document is a temporary document.
     *
     * @return bool
     */
    public function isTemporary()
    {
        return $this->temporary;
    }

    /**
     * Sets if a document is a temporary document or not.
     * @param bool $temporary
     */
    public function setTemporary($temporary)
    {
        $this->temporary = boolval($temporary);
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @param DocumentForm $targetForm
     * @return DocumentChanges
     */
    public function diff(DocumentForm $targetForm)
    {
        $documentChanges = new DocumentChanges($this, $targetForm);

        // pages
        foreach ($this->getItems() as $keyPage => $valuePage) {
            foreach ($valuePage as $keyRepeatPage => $valueRepeatPage) {

                // groups
                foreach ($valueRepeatPage->getItems() as $keyGroup => $valueGroup) {

                    $currentGroups = [];
                    foreach ($valueGroup as $keyRepeatGroup => $valueRepeatGroup) {
                        $currentGroups[$valueRepeatGroup->getId()] = $valueRepeatGroup;
                    }

                    $targetGroups = [];
                    $targetValueGroups = $targetForm->getItems()[$keyPage][$keyRepeatPage]->getItems()[$keyGroup];
                    foreach ($targetValueGroups as $keyRepeatGroup => $valueRepeatGroup) {
                        $targetGroups[$valueRepeatGroup->getId()] = $valueRepeatGroup;
                    }

                    foreach ($currentGroups as $currentGroupId => $currentGroup) {
                        // @var GroupChange $groupChange
                        $groupChange = null;

                        if (array_key_exists($currentGroupId, $targetGroups)) {
                            if ($currentGroup->isEmptyGroup() && !$targetGroups[$currentGroupId]->isEmptyGroup()) {
                                $groupChange = new GroupChange(null, ($targetGroups[$currentGroupId]));
                                $groupChange->setAdded();
                                break;
                            } else {
                                $groupChange = new GroupChange($currentGroup, $targetGroups[$currentGroupId]);
                            }

                            $currentFields = [];
                            foreach ($currentGroup->getItems() as $keyField => $valueField) {
                                foreach ($valueField as $keyRepeatField => $valueRepeatField) {
                                    $currentFields[$valueRepeatField->getId()] = $valueRepeatField;
                                }
                            }

                            $targetFields = [];
                            foreach ($targetGroups[$currentGroupId]->getItems() as $keyField => $valueField) {
                                foreach ($valueField as $keyRepeatField => $valueRepeatField) {
                                    $targetFields[$valueRepeatField->getId()] = $valueRepeatField;
                                }
                            }

                            foreach ($currentFields as $currentFieldId => $currentField) {
                                if (array_key_exists($currentFieldId, $targetFields)) {
                                    $currentValue = trim($currentField->getValue());
                                    $targetValue = trim($targetFields[$currentFieldId]->getValue());
                                    if ($currentValue !== $targetValue) {
                                        // @var FieldChange $fieldChange
                                        $fieldChange = new FieldChange($currentField, $targetFields[$currentFieldId]);
                                        $groupChange->addFieldChange($fieldChange);
                                    }
                                    unset($targetFields[$currentFieldId]);
                                } else {
                                    // @var FieldChange $fieldChange
                                    $fieldChange = new FieldChange($currentField, null);
                                    $fieldChange->setDeleted();
                                    $groupChange->addFieldChange($fieldChange);
                                }
                            }

                            foreach ($targetFields as $targetFieldId => $targetField) {
                                // @var FieldChange $fieldChange
                                $fieldChange = new FieldChange(null, $targetField);
                                $fieldChange->setAdded();
                                $groupChange->addFieldChange($fieldChange);
                            }

                            if ($groupChange->hasFieldChanges()) {
                                $documentChanges->addChange($groupChange);
                            }

                            unset($targetGroups[$currentGroupId]);
                        } else {
                            // @var GroupChange $groupChange
                            $groupChange = new GroupChange($currentGroup, null);
                            $groupChange->setDeleted();
                            $documentChanges->addChange($groupChange);
                        }
                    }

                    foreach ($targetGroups as $targetGroupId => $targetGroup) {
                        // @var GroupChange $groupChange
                        $groupChange = new GroupChange(null, $targetGroup);
                        $groupChange->setAdded();
                        $documentChanges->addChange($groupChange);
                    }
                }
            }
        }

        return $documentChanges;
    }

    /**
     * Applies the given changes to the documentForm
     *
     * @param DocumentChanges $docuemntChanges
     */
    public function applyChanges(DocumentChanges $documentChanges)
    {
        /** @var GroupChange $groupChange */
        foreach ($documentChanges->getChanges() as $groupChange) {
            if ($groupChange->isAccepted()) {
                // pages
                foreach ($this->getItems() as $keyPage => $valuePage) {
                    foreach ($valuePage as $keyRepeatPage => $valueRepeatPage) {
                        // groups
                        foreach ($valueRepeatPage->getItems() as $keyGroup => $valueGroup) {
                            if ($keyGroup === $groupChange->getGroup()->getUid()) {
                                $valueRepeatPage->applyGroupChange($groupChange);
                                break;
                            }
                        }
                    }
                }

                $group = $groupChange->getNewGroup();
                if ($group instanceof DocumentFormGroup && $group->isFileGroup() && $groupChange->hasAcceptedFieldChanges()) {
                    $file = $group->getFile();
                    if ($file instanceof File) {
                        $origFile = $this->getFileByFileIdentifier($file->getFileIdentifier());
                        if ($origFile instanceof File) {
                            $origFile->copy($file);
                        } else {
                            $newFile = new File();
                            $newFile->copy($file);
                            $this->addFile($newFile);
                        }
                    }
                }

            }
        }
    }

    /**
     * @param DocumentFormGroup $groupItem
     */
    protected function addGroupItem(DocumentFormGroup $groupItem)
    {
        foreach ($this->getItems() as $keyPage => $valuePage) {
            foreach ($valuePage as $keyRepeatPage => $valueRepeatPage) {
                foreach ($valueRepeatPage->getItems() as $keyGroup => $valueGroup) {
                    if ($keyGroup === $groupItem->getUid()) {
                        $valueRepeatPage->addItem($groupItem);
                        return;
                    }
                }
            }
        }
    }

    /**
     * @param DocumentFormGroup $groupItem
     */
    protected function removeGroupItem(DocumentFormGroup $groupItem)
    {
        foreach ($this->getItems() as $keyPage => $valuePage) {
            foreach ($valuePage as $keyRepeatPage => $valueRepeatPage) {
                foreach ($valueRepeatPage->getItems() as $keyGroup => $valueGroup) {
                    if ($keyGroup === $groupItem->getUid()) {
                        $valueRepeatPage->removeItem($groupItem);
                        return;
                    }
                }
            }
        }
    }
}
