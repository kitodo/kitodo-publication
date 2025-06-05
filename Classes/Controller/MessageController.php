<?php

namespace EWW\Dpf\Controller;

use EWW\Dpf\Domain\Model\Message;
use EWW\Dpf\Services\Email\Notifier;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller to show and resend failed fis messages.
 */
class MessageController extends AbstractController
{
    /**
     * @var \EWW\Dpf\Domain\Repository\MessageRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $messageRepository;

    /**
     * @var \EWW\Dpf\Services\Email\Notifier
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $notifier;

    /**
     * @return void
     */
    public function initializeAction()
    {
        $this->authorizationChecker->denyAccessUnlessIsLibrarian();
        parent::initializeAction();
    }

    /**
     * Shows all failed messages.
     */
    public function listAction() {
        $messages = $this->messageRepository->findAll();
        $this->view->assign('messages', $messages);
    }

    /**
     * Retries the sending of a message.
     *
     * @param \EWW\Dpf\Domain\Model\Message $message
     */
    public function retryAction(Message $message)
    {
        $result = $this->notifier->retryActiveMessage($message);

        if ($result === true) {
            $this->addFlashMessage(
                LocalizationUtility::translate('manager.message.retrySuccessfull', 'dpf'),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::OK
            );
        } elseif (is_array($result) &&  $result['type'] === Notifier::ACTIVE_MESSAGE_ERROR_TYPE_HTTP) {
            $this->addFlashMessage(
                LocalizationUtility::translate('manager.message.retryNotDelivered', 'dpf'),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        } elseif (is_array($result) &&  $result['type'] === Notifier::ACTIVE_MESSAGE_ERROR_TYPE_CURL) {
            $this->addFlashMessage(
                LocalizationUtility::translate('manager.message.retryNotSent', 'dpf'),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        } else {
            $this->addFlashMessage(
                LocalizationUtility::translate('manager.message.retryFailed', 'dpf'),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }

        $this->redirect('list');
    }

    /**
     * Removes a failed message from the database.
     *
     * @param \EWW\Dpf\Domain\Model\Message $message
     */
    public function removeAction(Message $message)
    {
        $this->messageRepository->remove($message);
        $this->addFlashMessage(
            LocalizationUtility::translate('manager.message.removeSuccessfull', 'dpf'),
            '',
            \TYPO3\CMS\Core\Messaging\AbstractMessage::OK
        );

        $this->redirect('list');
    }
}
