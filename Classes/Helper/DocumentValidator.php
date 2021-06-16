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
use EWW\Dpf\Domain\Model\DocumentFormGroup;
use EWW\Dpf\Domain\Model\MetadataMandatoryInterface;
use EWW\Dpf\Security\Security;


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
     * @param Document $document
     * @param bool $validateInvisableFields : If false, invisible form fields and groups are not validated.
     * @param bool $checkPrimaryFile
     * @return bool
     */
    public function validate(Document $document, $validateInvisableFields = true, $checkPrimaryFile = false) {

        /** @var  \EWW\Dpf\Domain\Model\DocumentForm $docForm */
        $docForm = $this->documentMapper->getDocumentForm($document);

        if ($checkPrimaryFile && !$docForm->hasFiles()) return FALSE;

        $pages = $docForm->getItems();
        foreach ($pages as $page) {
            foreach ($page[0]->getItems() as $groups) {
                /** @var  \EWW\Dpf\Domain\Model\DocumentFormGroup $groupItem */

                foreach ($groups as $groupItem) {
                    if (!$this->hasAllMandatoryGroupValues($groupItem, $docForm->hasFiles(), $validateInvisableFields)) {
                        return FALSE;
                    }
                }
            }
        }

        return TRUE;
    }

}
