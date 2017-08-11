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

/**
 * DocumentFormController
 */
class AjaxDocumentController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository = null;

    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @inject
     */
    protected $metadataObjectRepository = null;

    /**
     *
     * @param integer $pageUid
     * @param integer $groupUid
     * @param integer $groupIndex
     * @return void
     */
    public function groupAction($pageUid, $groupUid, $groupIndex)
    {

        $group = $this->metadataGroupRepository->findByUid($groupUid);

        //$groupItem = array();

        $groupItem = new \EWW\Dpf\Domain\Model\DocumentFormGroup();

        foreach ($group->getMetadataObject() as $object) {

            $field = new \EWW\Dpf\Domain\Model\DocumentFormField();

            $field->setUid($object->getUid());
            $field->setDisplayName($object->getDisplayName());
            $field->setMandatory($object->getMandatory());
            $field->setBackendOnly($object->getBackendOnly());
            $field->setInputField($object->getInputField());
            $field->setInputOptions($object->getInputOptionList());
            $field->setMaxIteration($object->getMaxIteration());
            $field->setFillOutService($object->getFillOutService());
            $field->setValue("");

            $groupItem->addItem($field);
        }

        $this->view->assign('formPageUid', $pageUid);
        $this->view->assign('formGroupUid', $groupUid);
        $this->view->assign('formGroupDisplayName', $group->getDisplayName());
        $this->view->assign('groupIndex', $groupIndex);
        $this->view->assign('groupItem', $groupItem);
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
        $fieldItem->setBackendOnly($field->getBackendOnly());
        $fieldItem->setInputField($field->getInputField());
        $fieldItem->setInputOptions($field->getInputOptionList());
        $fieldItem->setMaxIteration($field->getMaxIteration());
        $fieldItem->setFillOutService($field->getFillOutService());
        $fieldItem->setValidation($field->getValidation());
        $fieldItem->setDataType($field->getDataType());
        $fieldItem->setValue("");

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
    public function primaryUploadAction($groupIndex)
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
     *
     * @param integer $fileUid
     * @param integer $isPrimary
     * @return void
     */
    public function deleteFileAction($fileUid, $isPrimary = 0)
    {
        $this->view->assign('fileUid', $fileUid);
        $this->view->assign('isPrimary', $isPrimary);
    }

    /**
     *
     * @param string $qucosaId
     * @return string
     */
    public function fillOutAction($qucosaId)
    {

        $urnService = $this->objectManager->get('EWW\\Dpf\\Services\\Identifier\\Urn');

        if (!empty($qucosaId)) {
            $urn = $urnService->getUrn($qucosaId);
        } else {
            $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
            $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
            $documentTransferManager->setRemoteRepository($remoteRepository);

            $qucosaId = $documentTransferManager->getNextDocumentId();

            $urn = $urnService->getUrn($qucosaId);

        }

        return json_encode(
            array(
                'qucosaId' => $qucosaId,
                'value'    => $urn,
            )
        );
    }

}
