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

use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Services\Email\Notifier;

class DocumentFormController extends AbstractDocumentFormController
{

    protected function redirectToList($message = null)
    {
        $this->redirect('list', 'DocumentForm', null, array('message' => $message));
    }

    /**
     * action new
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @param int $returnDocumentId
     * @ignorevalidation $newDocumentForm
     * @return void
     */
    public function newAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm = null, $returnDocumentId = 0)
    {
        $this->view->assign('documentForm', $newDocumentForm);
    }

    /**
     * action create
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @return void
     */
    public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm)
    {
        foreach ($newDocumentForm->getNewFiles() as $newFile) {
            $uid = $newFile->getUID();
            if (empty($uid)) {
                $newFile->setDownload(true);
            }
            $files[] = $newFile;
        }

        $newDocumentForm->setNewFiles($files);

        try {
            parent::createAction($newDocumentForm);

            /** @var \EWW\Dpf\Helper\DocumentMapper $documentMapper */
            $documentMapper = $this->objectManager->get(DocumentMapper::class);

            /** @var \EWW\Dpf\Domain\Model\Document $newDocument */
            $newDocument = $documentMapper->getDocument($newDocumentForm);

            /** @var Notifier $notifier */
            $notifier = $this->objectManager->get(Notifier::class);
            $notifier->sendNewDocumentNotification($newDocument);

            if (key_exists('afterDocSavedRedirectPage',$this->settings) && $this->settings['afterDocSavedRedirectPage']) {
                $uri = $this->uriBuilder
                    ->setTargetPageUid($this->settings['afterDocSavedRedirectPage'])
                    ->build();
                $this->redirectToUri($uri);
            } else {
                $this->redirectToList('CREATE_OK');
            }
        } catch (\Exception $exception) {

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(implode(" ", $message), '', $severity,true);
            $this->forward('new', 'DocumentForm', null, array('newDocumentForm' => $newDocumentForm));
        }
    }
}
