<?php
namespace EWW\Dpf\Session;

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

class Session
{
    const ROOT_KEY = "tx_dpf";
    const LIST_ACTION_KEY = "list_action";
    const WORKSPACE = "workspace";


    /**
     * @return SearchSessionData $data
     */
    public function getWorkspaceData()
    {
        $sessionData = $this->getData(self::ROOT_KEY);
        if (array_key_exists(self::WORKSPACE, $sessionData)) {
            return unserialize($sessionData[self::WORKSPACE]);
        }

        return new SearchSessionData();
    }

    /**
     * @param SearchSessionData $data
     */
    public function setWorkspaceData(SearchSessionData $data)
    {
        $sessionData[self::WORKSPACE] = serialize($data);
        $this->setData($sessionData);
    }


    /**
     * Stores the given action name, controller name and uri.
     *
     * @param $action
     * @param $controller
     */
    public function setStoredAction($action, $controller, $uri = null)
    {
        $sessionData = $this->getData(self::ROOT_KEY);
        $sessionData[self::LIST_ACTION_KEY] = [$action, $controller, $uri];
        $this->setData($sessionData);
    }

    /**
     * Returns the stored action name, controller name and uri.
     * [ 0 => 'action name', 1 => 'controller name', 2 => 'uri']
     *
     * @return array|mixed
     */
    public function getStoredAction()
    {
        $sessionData = $this->getData();
        if (is_array($sessionData) && array_key_exists(self::LIST_ACTION_KEY, $sessionData)) {
            return $sessionData[self::LIST_ACTION_KEY];
        }
        return [];
    }


    /**
     * Set session data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $userGlobals = $this->getUserGlobals();

        if ($userGlobals) {
            $userGlobals->setAndSaveSessionData(self::ROOT_KEY, $data);
        }

        return;
    }

    /**
     * Get session data
     *
     * @return array
     */
    public function getData()
    {
        $userGlobals = $this->getUserGlobals();

        $sessionData = null;

        if ($userGlobals) {
            $sessionData = $userGlobals->getSessionData(self::ROOT_KEY);
        }

        if ($sessionData && is_array($sessionData)) {
            return $sessionData;
        }

        return [];
    }

    /**
     * Gets the global user object.
     *
     * @return mixed|null
     */
    protected function getUserGlobals()
    {
        $userGlobals = null;

        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {

            $userGlobals = $GLOBALS['TSFE']->fe_user;

        } else if (!empty($GLOBALS['BE_USER']) && is_object($GLOBALS['BE_USER'])) {

            $userGlobals = $GLOBALS['BE_USER'];

        }

        return $userGlobals;
    }

}
