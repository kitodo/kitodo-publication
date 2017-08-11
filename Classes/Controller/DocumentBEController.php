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

class DocumentBEController extends AbstractDocumentController
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function redirectToList($message = null)
    {
        $this->redirect('list', 'DocumentManager', null, array('message' => $message));
    }

    /**
     * action delete
     *
     * @param array $documentData
     * @throws \Exception
     */
    public function deleteAction($documentData)
    {

        if (!$GLOBALS['BE_USER']) {
            throw new \Exception('Access denied');
        }

        $document = $this->documentRepository->findByUid($documentData['documentUid']);

        $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
        // send document to index
        $elasticsearchRepository->delete($document, "");

        $document->setState(\EWW\Dpf\Domain\Model\Document::OBJECT_STATE_LOCALLY_DELETED);
        $document = $this->documentRepository->update($document);

        $this->redirectToList();
    }

    public function editAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {

        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());
        $this->view->assign('document', $document);
        parent::editAction($documentForm);
    }
}
