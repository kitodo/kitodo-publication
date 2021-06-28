<?php
namespace EWW\Dpf\Controller;

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

use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Services\Identifier\Urn;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use TYPO3\CMS\Core\Core\Environment;

/**
 * DocumentFormController
 */
class AjaxDocumentFormController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataGroupRepository = null;

    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataObjectRepository = null;

    /**
     * fisDataService
     *
     * @var \EWW\Dpf\Services\FeUser\FisDataService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fisDataService = null;

    /**
     *
     * @param integer $pageUid
     * @param integer $groupUid
     * @param integer $groupIndex
     * @return void
     */
    public function groupAction($pageUid, $groupUid, $groupIndex)
    {
        /** @var MetadataGroup $group */
        $group = $this->metadataGroupRepository->findByUid($groupUid);

        //$groupItem = array();

        $groupItem = new \EWW\Dpf\Domain\Model\DocumentFormGroup();

        foreach ($group->getMetadataObject() as $object) {

            $field = new \EWW\Dpf\Domain\Model\DocumentFormField();

            $field->setUid($object->getUid());
            $field->setDisplayName($object->getDisplayName());
            $field->setMandatory($object->getMandatory());
            $field->setAccessRestrictionRoles($object->getAccessRestrictionRoles());
            $field->setInputField($object->getInputField());
            $field->setInputOptions($object->getInputOptionList());
            $field->setMaxIteration($object->getMaxIteration());
            $field->setFillOutService($object->getFillOutService());
            $field->setValidation($object->getValidation());
            $field->setValidationErrorMessage($object->getValidationErrorMessage());
            $field->setValidator($object->getValidator());
            $field->setGndFieldUid($object->getGndFieldUid());
            $field->setMaxInputLength($object->getMaxInputLength());
            $field->setValue("", $object->getDefaultValue());
            $field->setObjectType($object->getObjectType());

            $groupItem->addItem($field);
        }

        $groupItem->setGroupType($group->getGroupType());
        $groupItem->setMandatory($group->getMandatory());
        $groupItem->setMaxIteration($group->getMaxIteration());
        $groupItem->setEmptyGroup(true);

        if ($this->security->getFisPersId()) {
            $this->view->assign('fisPersId', $this->security->getFisPersId());
        }

        $this->view->assign('formPageUid', $pageUid);
        $this->view->assign('formGroupUid', $groupUid);
        $this->view->assign('formGroupDisplayName', $group->getDisplayName());
        $this->view->assign('groupIndex', $groupIndex);
        $this->view->assign('groupItem', $groupItem);

        if ($this->fisDataService->getPersonData($this->security->getFisPersId())) {
            $this->view->assign('fisPersId', $this->security->getFisPersId());
        }
    }

    /**
     *
     * @param integer $pageUid
     * @param integer $groupUid
     * @param integer $groupIndex
     * @param integer $fieldUid
     * @param integer $fieldIndex
     * @return void
     */
    public function fieldAction($pageUid, $groupUid, $groupIndex, $fieldUid, $fieldIndex)
    {

        $field = $this->metadataObjectRepository->findByUid($fieldUid);

        $fieldItem = new \EWW\Dpf\Domain\Model\DocumentFormField();

        $fieldItem->setUid($field->getUid());
        $fieldItem->setDisplayName($field->getDisplayName());
        $fieldItem->setMandatory($field->getMandatory());
        $fieldItem->setAccessRestrictionRoles($field->getAccessRestrictionRoles());
        $fieldItem->setInputField($field->getInputField());
        $fieldItem->setInputOptions($field->getInputOptionList());
        $fieldItem->setMaxIteration($field->getMaxIteration());
        $fieldItem->setFillOutService($field->getFillOutService());
        $fieldItem->setValidation($field->getValidation());
        $fieldItem->setValidationErrorMessage($field->getValidationErrorMessage());
        $fieldItem->setValidator($field->getValidator());
        $fieldItem->setGndFieldUid($field->getGndFieldUid());
        $fieldItem->setMaxInputLength($field->getMaxInputLength());
        $fieldItem->setValue("", $field->getDefaultValue());
        $fieldItem->setObjectType($field->getObjectType());

        $this->view->assign('formPageUid', $pageUid);
        $this->view->assign('formGroupUid', $groupUid);
        $this->view->assign('groupIndex', $groupIndex);
        //   $this->view->assign('formField',$formField);
        $this->view->assign('fieldIndex', $fieldIndex);
        $this->view->assign('fieldItem', $fieldItem);
        // $this->view->assign('countries',);
    }

    /**
     *
     * @return void
     */
    public function uploadAction($groupIndex)
    {
    }

    /**
     *
     * @param integer $groupIndex
     * @return void
     */
    public function secondaryUploadAction($groupIndex)
    {
        $this->view->assign('groupIndex', $groupIndex);
        //$this->view->assign('displayName','Sekundärdatei');
    }

    /**
     * @param int $fileUid
     * @param int $pageUid
     * @param int $groupUid
     * @param int $groupIndex
     * @param int $fieldUid
     * @param int $fieldIndex
     * @param int $isPrimary
     */
    public function deleteFileAction($fileUid, $pageUid, $groupUid, $groupIndex, $fieldUid, $fieldIndex, $isPrimary = 0)
    {
        $field = $this->metadataObjectRepository->findByUid($fieldUid);

        $fieldItem = new \EWW\Dpf\Domain\Model\DocumentFormField();

        $fieldItem->setUid($field->getUid());
        $fieldItem->setDisplayName($field->getDisplayName());
        $fieldItem->setMandatory($field->getMandatory());
        $fieldItem->setAccessRestrictionRoles($field->getAccessRestrictionRoles());
        $fieldItem->setInputField($field->getInputField());
        $fieldItem->setInputOptions($field->getInputOptionList());
        $fieldItem->setMaxIteration($field->getMaxIteration());
        $fieldItem->setFillOutService($field->getFillOutService());
        $fieldItem->setValidation($field->getValidation());
        $fieldItem->setDataType($field->getDataType());
        $fieldItem->setGndFieldUid($field->getGndFieldUid());
        $fieldItem->setMaxInputLength($field->getMaxInputLength());
        $fieldItem->setValue("", $field->getDefaultValue());
        $fieldItem->setObjectType($field->getObjectType());

        $this->view->assign('formPageUid', $pageUid);
        $this->view->assign('formGroupUid', $groupUid);
        $this->view->assign('groupIndex', $groupIndex);

        $this->view->assign('fieldIndex', $fieldIndex);
        $this->view->assign('fieldItem', $fieldItem);

        $this->view->assign('fileUid', $fileUid);
        $this->view->assign('isPrimary', $isPrimary);
    }

    /**
     *
     * @param string $fedoraPid
     * @return string
     */
    public function fillOutAction($fedoraPid)
    {
        try {
            $urnService = $this->objectManager->get(Urn::class);

            if (!empty($fedoraPid)) {
                $urn = $urnService->getUrn($fedoraPid);
            } else {
                $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
                $remoteRepository = $this->objectManager->get(FedoraRepository::class);
                $documentTransferManager->setRemoteRepository($remoteRepository);

                $fedoraPid = $documentTransferManager->getNextDocumentId();

                $urn = $urnService->getUrn($fedoraPid);

            }

            return json_encode(
                array(
                    'error' => false,
                    'fedoraPid' => $fedoraPid,
                    'value'    => $urn,
                )
            );

        } catch (\Exception $exception) {
            return json_encode(
                array(
                    'error' =>  true,
                    'fedoraPid' => null,
                    'value'    => null,
                )
            );
        }

    }

    /**
     * @return bool
     */
    public function fileUploadAction()
    {
    //    $uploadedFile = $_FILES['file'];

        $uploadFileUrl = new \EWW\Dpf\Helper\UploadFileUrl;

        $uploadBaseUrl = $uploadFileUrl->getUploadUrl() . "/";

        $uploadPath = Environment::getPublicPath() . "/" . $uploadFileUrl->getDirectory() . "/";

        $fileName = uniqid(time(), true)."test";

        if(is_writable(".") && isset($_FILES['file'])) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($_FILES['file']['filename'], $this->uploadPath . $fileName);
        }

        return $uploadedFile;

       // $file = $this->objectManager->get(File::class);

/*
        $uniqfileName = uniqid(time(), true);

        \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($tmpFile['tmp_name'], $this->uploadPath . $fileName);

        $finfo       = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $this->uploadPath . $fileName);
        finfo_close($finfo);

        $file->setContentType($contentType);

        $file->setTitle($tmpFile['name']);
        $file->setLink($fileName);
        $file->setPrimaryFile($primary);
        $file->setFileIdentifier(uniqid(time(), true));

        if ($primary) {
            if ($file->getDatastreamIdentifier()) {
                $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);
            } else {
                $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_ADDED);
            }
        } else {
            $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_ADDED);
        }

        return $file;
*/
        return true;
    }

}
