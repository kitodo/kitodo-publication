<?php
namespace EWW\Dpf\Helper;

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

use EWW\Dpf\Domain\Workflow\DocumentWorkflow;

class Session
{
    const ROOT_KEY = "tx_dpf";
    const WORKSPACE_SORT_KEY = "workspace_sort";
    const LIST_ACTION_KEY = "list_action";
    const WORKSPACE_FILTER_KEY = "workspace_filter";
    const WORKSPACE_EXCLUDE_FILTER_KEY = "workspace_exclude_filter";
    const WORKSPACE_ITEMS_PER_PAGE = "workspace_items_per_page";


    /**
     * Stores the sort field and order.
     *
     * @param $field
     * @param $order
     */
    public function setWorkspaceSort($field, $order)
    {
        $sessionData = $this->getData(self::ROOT_KEY);
        $sessionData[self::WORKSPACE_SORT_KEY] = [$field, $order];
        $this->setData($sessionData);
    }

    /**
     * Returns the stored sort field and order.
     *
     * @return array|mixed
     */
    public function getWorkspaceSort()
    {
        $sessionData = $this->getData();
        if (is_array($sessionData) && array_key_exists(self::WORKSPACE_SORT_KEY, $sessionData)) {
            return $sessionData[self::WORKSPACE_SORT_KEY];
        }
        return [];
    }

    /**
     * Deletes the sort values.
     */
    public function clearWorkspaceSort()
    {
        $sessionData = $this->getData();
        unset($sessionData[self::WORKSPACE_SORT_KEY]);
        $this->setData($sessionData);
    }


    /**
     * Deletes the filter values.
     */
    public function clearFilter()
    {
        $userGlobals = $this->getUserGlobals();

        if ($userGlobals) {
            $workspaceFilters = $userGlobals->getSessionData("workspaceFilters");
            $workspaceFilters = [];
            $userGlobals->setAndSaveSessionData("workspaceFilters", $workspaceFilters);
        }
    }


    /**
     * Stores the given action name, controller name and uri.
     *
     * @param $action
     * @param $controller
     */
    public function setListAction($action, $controller, $uri = null)
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
    public function getListAction()
    {
        $sessionData = $this->getData();
        if (is_array($sessionData) && array_key_exists(self::LIST_ACTION_KEY, $sessionData)) {
            return $sessionData[self::LIST_ACTION_KEY];
        }
        return [];
    }



    /**
     * Returns the exclude filters.
     *
     * @return array|mixed
     */
    public function getWorkspaceExcludeFilters()
    {
        $sessionData = $this->getData();
        if (is_array($sessionData) && array_key_exists(self::WORKSPACE_EXCLUDE_FILTER_KEY, $sessionData)) {
            return $sessionData[self::WORKSPACE_EXCLUDE_FILTER_KEY];
        }
        return [];
    }

    /**
     * Toggles the discarded documents filter.
     *
     */
    public function toggleWorkspaceExcludeDiscardedFilter()
    {
        $sessionData = $this->getData();

        $filters = [];
        if (is_array($sessionData) && array_key_exists(self::WORKSPACE_EXCLUDE_FILTER_KEY, $sessionData)) {
            $filters = $sessionData[self::WORKSPACE_EXCLUDE_FILTER_KEY];
        }

        if (array_key_exists('aliasState', $filters)) {
            unset($filters['aliasState']);
        } else {
            $filters['aliasState'] = [DocumentWorkflow::ALIAS_STATE_DISCARDED];
        }

        $sessionData[self::WORKSPACE_EXCLUDE_FILTER_KEY] = $filters;
        $this->setData($sessionData);
    }

    /**
     * Toggles the hide bookmarks filter.
     *
     */
    public function toggleWorkspaceBookmarksOnlyFilter()
    {
        $sessionData = $this->getData();

        $filters = [];
        if (is_array($sessionData) && array_key_exists(self::WORKSPACE_EXCLUDE_FILTER_KEY, $sessionData)) {
            $filters = $sessionData[self::WORKSPACE_EXCLUDE_FILTER_KEY];
        }

        if (array_key_exists('bookmarks', $filters)) {
            unset($filters['bookmarks']);
        } else {
            $filters['bookmarks'] = true;
        }

        $sessionData[self::WORKSPACE_EXCLUDE_FILTER_KEY] = $filters;
        $this->setData($sessionData);
    }


    /**
     * Set the items per page for the workspace list.
     *
     * @param int $itemsPerPage
     */
    public function setWorkspaceItemsPerPage($itemsPerPage)
    {
        $sessionData = $this->getData();
        $sessionData[self::WORKSPACE_ITEMS_PER_PAGE] = $itemsPerPage;
        $this->setData($sessionData);
    }

    /**
     * Get the items per page for the workspace list.
     *
     * @return int
     */
    public function getWorkspaceItemsPerPage()
    {
        $sessionData = $this->getData();

        if (is_array($sessionData) && array_key_exists(self::WORKSPACE_ITEMS_PER_PAGE, $sessionData)) {
            $itemsPerPage = $sessionData[self::WORKSPACE_ITEMS_PER_PAGE];
            return $itemsPerPage;
        }

        return 0;
    }


    /**
     * Set session data
     *
     * @param string $data
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
     * @return mixed
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
