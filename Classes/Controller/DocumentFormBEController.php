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

use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Exceptions\DPFExceptionInterface;

class DocumentFormBEController extends AbstractDocumentFormController
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function redirectToList($message = null)
    {
        $this->redirect('list', 'Document', null, array('message' => $message));
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

        try {

            $document = $this->documentRepository->findByUid($documentData['documentUid']);

            $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
            // send document to index
            $elasticsearchRepository->delete($document, "");

            $document->setState(\EWW\Dpf\Domain\Model\Document::OBJECT_STATE_LOCALLY_DELETED);
            $this->documentRepository->update($document);

            $this->redirectToList();

        } catch (\Exception $exception) {

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $document = $this->documentRepository->findByUid($documentData['documentUid']);
            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.failure',
                'dpf',
                array($document->getTitle())
            );

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');
            $this->addFlashMessage(implode(" ", $message), '', $severity,true);

            $this->forward('edit', DocumentFormBE, null, array('document' => $document));
        }


    }

    public function editAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());
        $this->view->assign('document', $document);

        parent::editAction($documentForm);
    }

    public function updateAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        try {
            parent::updateAction($documentForm);
        } catch (\Exception $exception) {

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $documentMapper = $this->objectManager->get(\EWW\Dpf\Helper\DocumentMapper::class);
            $updateDocument = $documentMapper->getDocument($documentForm);

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure',
                'dpf',
                array($updateDocument->getTitle())
            );

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');


            $this->addFlashMessage(implode(" ", $message), '', $severity,true);

            $this->forward('edit', 'DocumentFormBE', null, array('document' => $updateDocument));
        }
    }

    public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm)
    {
        parent::createAction($newDocumentForm);
        $this->redirectToList('CREATE_OK');
    }

}
