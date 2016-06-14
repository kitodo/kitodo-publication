<?php
namespace EWW\Dpf\Helper;

/**
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

class DocumentFormValidator
{

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * MetadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @inject
     */
    protected $metadataObjectRepository = null;

    /**
     * MetadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository = null;

    protected $documentType;

    protected $error;

    protected $formData;

    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    public function setFormData($formData)
    {
        $this->formData = $formData;
    }

    public function validate()
    {

        $result = true;

        if ($this->preValidate()) {

        }

        return $result;
    }

    public function preValidate()
    {
        if (!key_exists('metadata', $this->formData)) {
            return false;
        }

        if (!key_exists('p', $this->formData['metadata'])) {
            return false;
        }

        if (sizeof($this->formData['metadata']['p']) < 1) {
            return false;
        }

        foreach ($this->formData['metadata']['p'] as $pageUid => $page) {

            if (!key_exists('g', $page)) {
                return false;
            }

            if (sizeof($page['g']) < 1) {
                return false;
            }

        }

        return true;
    }

    protected function validateMandatoryFields()
    {

        $result = true;

        return $result;
    }

    protected function validateAttributes()
    {

        $result = true;

        $groups = $this->getGroups();
        foreach ($groups as $groupUid => $group) {

            $attributeFieldUids = $this->getAttributeFieldUidsByGroup($groupUid);

            $check               = array();
            $dublicateAttributes = array();

            foreach ($group as $groupIndex => $fields) {

                $attributeValues = array();

                foreach ($attributeFieldUids as $attributeFieldUid) {
                    $attributeValues[] = $fields['f'][$attributeFieldUid][0];
                }

                $checkKey = implode('-', $attributeValues);
                if (key_exists($checkKey, $check)) {
                    $result                           = $result && false;
                    $dublicateAttributes[$groupIndex] = array(
                        'groupUid'   => $groupUid,
                        'groupIndex' => $groupIndex,
                        'fieldUids'  => $attributeFieldUids,
                    );
                } else {
                    $check[$checkKey] = $groupIndex;
                }

            }

            if ($dublicateAttributes) {
                $this->error[] = $dublicateAttributes;
            }

        }

        return $result;
    }

    protected function getAttributeFieldUidsByGroup($groupUid)
    {

        $group = $this->metadataGroupRepository->findByUid($groupUid);

        $fields = $group->getMetadataObject();

        foreach ($fields as $field) {
            $mapping = $field->getRelativeMapping();
            if (strpos($mapping, "@") === 0) {
                $attributeFields[] = $field->getUid();
            }
        }

        return $attributeFields;
    }

    protected function getGroups()
    {
        $groups = array();
        foreach ($this->formData['metadata']['p'] as $pageUid => $page) {
            foreach ($page['g'] as $groupUid => $group) {
                $groups[$groupUid] = $group;
            }
        }
        return $groups;
    }

    private function debug($value, $die = false)
    {

        echo "<pre>";
        var_dump($value);
        echo "</pre>";

        if ($die) {
            die();
        }

    }

}
