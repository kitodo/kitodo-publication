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
 * AjaxDocumentController
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
     * metadataPageRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataPageRepository
     * @inject
     */
    protected $metadataPageRepository = null;

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
        $groupIterator['index'] = $groupIndex;
        $groupIterator['cycle'] = $groupIndex + 1;
        $groupIterator['isLast'] = TRUE;

        $pageType = $this->metadataPageRepository->findByUid($pageUid);
        $groupType = $this->metadataGroupRepository->findByUid($groupUid);

        $group = array();
        foreach ($groupType->getMetadataObject() as $object) {
            $group[0][$object->getUid()] = "";
        }

        $this->view->assign('pageType', $pageType);
        $this->view->assign('groupType', $groupType);
        $this->view->assign('group', $group);
        $this->view->assign('groupIterator', $groupIterator);
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
        $groupIterator['index'] = $groupIndex;
        $groupIterator['cycle'] = $groupIndex + 1;
        $groupIterator['isLast'] = TRUE;

        $fieldIterator['index'] = $fieldIndex;
        $fieldIterator['cycle'] = $fieldIndex + 1;
        $fieldIterator['isLast'] = TRUE;

        $pageType = $this->metadataPageRepository->findByUid($pageUid);

        $groupType = $this->metadataGroupRepository->findByUid($groupUid);

        $fieldType = $this->metadataObjectRepository->findByUid($fieldUid);
        $field[$fieldType->getUid()];

        $this->view->assign('pageType', $pageType);
        $this->view->assign('groupType', $groupType);
        $this->view->assign('fieldType', $fieldType);
        $this->view->assign('field', $field);
        $this->view->assign('groupIterator', $groupIterator);
        $this->view->assign('fieldIterator', $fieldIterator);
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
