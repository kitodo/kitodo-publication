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


class DocumentValidator
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;


    /**
     * documentMapper
     *
     * @var \EWW\Dpf\Helper\DocumentMapper
     * @inject
     */
    protected $documentMapper;


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
     * @return bool
     */
    protected function hasAllMandatoryFieldValues(DocumentFormGroup $group, $hasFiles)
    {
        foreach ($group->getItems() as $fields) {
            foreach ($fields as $field) {
                switch ($field->getMandatory()) {
                    case MetadataMandatoryInterface::MANDATORY:
                        if (!$field->getValue()) return FALSE;
                        break;
                    case MetadataMandatoryInterface::MANDATORY_FILE_ONLY:
                        if ($hasFiles && !$field->getValue()) return FALSE;
                        break;
                }
            }
        }

        return TRUE;
    }


    /**
     * @param DocumentFormGroup $group
     * @param bool $hasFiles
     * @return bool
     */
    protected function hasAllMandatoryGroupValues(DocumentFormGroup $group, $hasFiles)
    {
        switch ($group->getMandatory()) {
            case MetadataMandatoryInterface::MANDATORY:
                return $this->hasFieldWithValue($group) && $this->hasAllMandatoryFieldValues($group, $hasFiles);
            case MetadataMandatoryInterface::MANDATORY_FILE_ONLY:
                if ($hasFiles) {
                    return $this->hasFieldWithValue($group) && $this->hasAllMandatoryFieldValues($group, $hasFiles);
                }
                break;
            default:
                if ($this->hasFieldWithValue($group)) {
                    return $this->hasAllMandatoryFieldValues($group, $hasFiles);
                }
                break;
        }

        return TRUE;
    }


    /**
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     */
    protected function hasFiles(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        return $documentForm->getPrimaryFile() || $documentForm->getSecondaryFiles();
    }


    /**
     * @param Document $document
     * @param bool $checkPrimaryFile
     * @return bool
     */
    public function validate(Document $document, $checkPrimaryFile = FALSE) {

        /** @var  \EWW\Dpf\Domain\Model\DocumentForm $docForm */
        $docForm = $this->documentMapper->getDocumentForm($document);

        $hasFiles = $this->hasFiles($docForm);

        if ($checkPrimaryFile && !$hasFiles) return FALSE;

        $pages = $docForm->getItems();
        foreach ($pages as $page) {
            foreach ($page[0]->getItems() as $groups) {
                /** @var  \EWW\Dpf\Domain\Model\DocumentFormGroup $groupItem */

                foreach ($groups as $groupItem) {
                    if (!$this->hasAllMandatoryGroupValues($groupItem, $hasFiles)) {
                        //die();
                        return FALSE;
                    }
                }
            }
        }

        return TRUE;
    }

}