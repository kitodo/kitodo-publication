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

use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository = null;

    protected function initializeView($view)
    {
        parent::initializeView($view);

        $selectedPageId = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');

        if (TYPO3_MODE === 'BE') {
            if ($selectedPageId) {
                $client = $this->clientRepository->findAll()->current();
            }
        } else {
            $client = $this->clientRepository->findAll()->current();
        }

        if (!$client) {
            $this->addFlashMessage(
                "Es wurde kein gültiger Mandantenordner ausgewählt.",
                $messageTitle = '',
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING,
                $storeInSession = true
            );
        } else {

        }

        $view->assign('client', $client);

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
            return GeneralUtility::removeXSS($param);
        }
    }

}
