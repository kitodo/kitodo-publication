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

use EWW\Dpf\Domain\Model\Document;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * authorizationChecker
     *
     * @var \EWW\Dpf\Security\AuthorizationChecker
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $authorizationChecker = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $security = null;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientRepository = null;

    /**
     * signalSlotDispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $signalSlotDispatcher = null;

    /**
     * session
     *
     * @var \EWW\Dpf\Session\Session
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $session = null;

    /**
     * logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger = null;


    public function __construct()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view)
    {
        parent::initializeView($view);

        $client = $this->clientRepository->findAll()->current();

        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:manager.chooseClientMessage';
        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

        if (!$client) {
            $this->addFlashMessage(
                $message,
                $messageTitle = '',
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING,
                $storeInSession = true
            );
        } else {
            $view->assign('client', $client);
        }
    }

    /**
     * Set session data
     *
     * @param string $key
     * @param string $data
     */
    public function setSessionData($key, $data)
    {
        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {

            $userGlobals = $GLOBALS['TSFE']->fe_user;

        } else if (!empty($GLOBALS['BE_USER']) && is_object($GLOBALS['BE_USER'])) {

            $userGlobals = $GLOBALS['BE_USER'];

        }

        $userGlobals->setAndSaveSessionData($key, $data);

        return;
    }

    /**
     * Get session data
     *
     * @param string $key
     *
     * @return
     */
    public function getSessionData($key)
    {
        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {

            $userGlobals = $GLOBALS['TSFE']->fe_user;

        } else if (!empty($GLOBALS['BE_USER']) && is_object($GLOBALS['BE_USER'])) {

            $userGlobals = $GLOBALS['BE_USER'];

        }
        return $userGlobals->getSessionData($key);
    }

    /**
     * Safely gets Parameters from request
     * if they exist
     *
     * @param string $parameterName
     *
     * @return null|string
     */
    protected function getParametersSafely($parameterName)
    {
        if ($this->request->hasArgument($parameterName)) {
            return $this->filterSafelyParameters($this->request->getArgument($parameterName));
        }
        return null;
    }

    /**
     * remove XSS stuff recursively
     *
     * @param mixed $param
     *
     * @return string
     */
    protected function filterSafelyParameters($param)
    {
        if (is_array($param)) {
            foreach ($param as $key => $item) {
                $param[$key] = $this->filterSafelyParameters($item);
            }
            return $param;
        } else {
            // FIXME: removeXSS is deprecated;
            //return GeneralUtility::removeXSS($param)
            return $param;
        }
    }

    public function initializeAction()
    {
        parent::initializeAction();

        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch(get_class($this), 'actionChange', [$this->actionMethodName, get_class($this)]);
    }


    public function getCurrentAction()
    {
        return str_replace('Action', '', $this->actionMethodName);
    }

    public function getCurrentController()
    {
        $controllerName = end(explode('\\', get_Class($this)));
        return str_replace('Controller', '', $controllerName);
    }


    /**
     *
     * @param Document $document
     * @param string $key
     * @param int $severity
     * @param string $defaultMessage
     */
    protected function flashMessage(Document $document, string $key, int $severity, $defaultMessage = "")
    {
        // Show success or failure of the action in a flash message
        $args = [];
        if ($document) {
            $args[] = $document->getTitle();
            $args[] = $document->getObjectIdentifier();
        }

        $message = LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? $defaultMessage : $message;

        $this->addFlashMessage(
            $message,
            '',
            $severity,
            true
        );
    }


}
