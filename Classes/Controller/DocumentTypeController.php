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
 * DocumentTypeController
 */
class DocumentTypeController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $documentTypes = $this->documentTypeRepository->findAll();
        $this->view->assign('documentTypes', $documentTypes);
    }

    /**
     * action show
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
     * @return void
     */
    public function showAction(\EWW\Dpf\Domain\Model\DocumentType $documentType)
    {
        $this->view->assign('documentType', $documentType);
    }

    /**
     * action new
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $newDocumentType
     * @ignorevalidation $newDocumentType
     * @return void
     */
    public function newAction(\EWW\Dpf\Domain\Model\DocumentType $newDocumentType = null)
    {
        $this->view->assign('newDocumentType', $newDocumentType);
    }

    /**
     * action create
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $newDocumentType
     * @return void
     */
    public function createAction(\EWW\Dpf\Domain\Model\DocumentType $newDocumentType)
    {
        $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->documentTypeRepository->add($newDocumentType);
        $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
     * @ignorevalidation $documentType
     * @return void
     */
    public function editAction(\EWW\Dpf\Domain\Model\DocumentType $documentType)
    {
        $this->view->assign('documentType', $documentType);
    }

    /**
     * action update
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
     * @return void
     */
    public function updateAction(\EWW\Dpf\Domain\Model\DocumentType $documentType)
    {
        $this->addFlashMessage('The object was updated. Please be aware that this action is publicly accessible unless you implement an access check. ', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->documentTypeRepository->update($documentType);
        $this->redirect('list');
    }

    /**
     * action delete
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
     * @return void
     */
    public function deleteAction(\EWW\Dpf\Domain\Model\DocumentType $documentType)
    {
        $this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. ', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->documentTypeRepository->remove($documentType);
        $this->redirect('list');
    }

}
