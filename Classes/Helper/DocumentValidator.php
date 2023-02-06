<?php

namespace EWW\Dpf\Helper;

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


use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentForm;
use EWW\Dpf\Domain\Model\DocumentFormGroup;
use EWW\Dpf\Domain\Model\MetadataMandatoryInterface;


class DocumentValidator
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager;


    /**
     * documentMapper
     *
     * @var \EWW\Dpf\Helper\DocumentMapper
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentMapper;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $security = null;

    /**
     * @param DocumentFormGroup $group
     * @return bool
     */
    protected function hasFieldWithValue(DocumentFormGroup $group)
    {
        foreach ($group->getItems() as $fields) {
            foreach ($fields as $field) {
                switch ($group->getMandatory()) {
                    case MetadataMandatoryInterface::MANDATORY:
                    case MetadataMandatoryInterface::MANDATORY_FILE_ONLY:
                        if ($field->getValue()) {
                            return TRUE;
                        }
                        break;
                    default:
                        if ($field->getValue() && !$field->getHasDefaultValue()) {
                            return TRUE;
                        }
                        break;
                }
            }
        }

        return FALSE;
    }


    /**
     * @param DocumentFormGroup $group
     * @param bool $hasFiles
     * @param bool $validateInvisableFields : If false, invisible form fields are not validated.
     * @return bool
     */
    protected function hasAllMandatoryFieldValues(DocumentFormGroup $group, $hasFiles, $validateInvisableFields)
    {
        foreach ($group->getItems() as $fields) {
            foreach ($fields as $field) {

                $isFieldVisible = !(
                    $this->security !== null &&
                    $field->getAccessRestrictionRoles() &&
                    !in_array($this->security->getUserRole(), $group->getAccessRestrictionRoles())
                );

                switch ($field->getMandatory()) {
                    case MetadataMandatoryInterface::MANDATORY:
                        if ($validateInvisableFields || $isFieldVisible) {
                            if (!$field->getValue()) return FALSE;
                        }
                        break;
                    case MetadataMandatoryInterface::MANDATORY_FILE_ONLY:
                        if ($validateInvisableFields || $isFieldVisible) {
                            if ($hasFiles && !$field->getValue()) {
                                return false;
                            }
                        }
                        break;
                }
            }
        }

        return TRUE;
    }


    /**
     * @param DocumentFormGroup $group
     * @param bool $hasFiles
     * @param bool $validateInvisableFields : If false, invisible form groups and fields are not validated.
     * @return bool
     */
    protected function hasAllMandatoryGroupValues(DocumentFormGroup $group, $hasFiles, $validateInvisableFields)
    {
        $isGroupVisible = !(
            $this->security !== null &&
            $group->getAccessRestrictionRoles() &&
            !in_array($this->security->getUserRole(), $group->getAccessRestrictionRoles())
        );

        if (!$validateInvisableFields && !$isGroupVisible) {
            return true;
        }

        switch ($group->getMandatory()) {
            case MetadataMandatoryInterface::MANDATORY:
                return $this->hasFieldWithValue($group) && $this->hasAllMandatoryFieldValues($group, $hasFiles, $validateInvisableFields);
            case MetadataMandatoryInterface::MANDATORY_FILE_ONLY:
                if ($hasFiles) {
                    return $this->hasFieldWithValue($group) && $this->hasAllMandatoryFieldValues($group, $hasFiles, $validateInvisableFields);
                }
                break;
            default:
                if ($this->hasFieldWithValue($group)) {
                    return $this->hasAllMandatoryFieldValues($group, $hasFiles, $validateInvisableFields);
                }
                break;
        }

        return TRUE;
    }

    /**
     * Validate a given document form
     *
     * @param DocumentForm $documentForm Form to validate
     * @param bool $validateInvisableFields If false, invisible form fields and groups are not validated. Default true.
     *
     * @return bool True, if the form is valid
     */
    public function validateForm(DocumentForm $documentForm, $validateInvisableFields = true): bool
    {
        $pages = $documentForm->getItems();
        foreach ($pages as $page) {
            foreach ($page[0]->getItems() as $groups) {
                foreach ($groups as $groupItem) {
                    if (!$this->hasAllMandatoryGroupValues($groupItem, $documentForm->hasFiles(), $validateInvisableFields)) {
                        return FALSE;
                    }
                }
            }
        }

        return TRUE;
    }

    /**
     * Validate given Document
     *
     * @param Document $document
     * @param bool $validateInvisableFields If false, invisible form fields and groups are not validated. Default true.
     * @param bool $checkPrimaryFile If true, check if the document has any files. Default false.
     *
     * @return bool
     */
    public function validate(Document $document, $validateInvisableFields = true, $checkPrimaryFile = false)
    {
        /** @var  \EWW\Dpf\Domain\Model\DocumentForm $docForm */
        $docForm = $this->documentMapper->getDocumentForm($document);

        if ($checkPrimaryFile && !$docForm->hasFiles()) return FALSE;

        return $this->validateForm($docForm, $validateInvisableFields);
    }
}
