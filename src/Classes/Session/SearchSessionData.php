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

use EWW\Dpf\Domain\Workflow\DocumentWorkflow;

class SearchSessionData
{
    /**
     * @var array
     */
    protected $excludeFilters = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var string
     */
    protected $sortField = '';

    /**
     * @var string
     */
    protected $sortOrder = '';

    /**
     * @var string
     */
    protected $simpleQuery = null;

    /**
     * @var int
     */
    protected $itemsPerPage;


    /**
     * Returns the exclude filters.
     *
     * @return array
     */
    public function getExcludeFilters()
    {
        return $this->excludeFilters;
    }


    /**
     * Toggles the discarded documents filter.
     *
     */
    public function toggleExcludeDiscardedFilter()
    {
        if (array_key_exists('aliasState', $this->excludeFilters)) {
            unset($this->excludeFilters['aliasState']);
        } else {
            $this->excludeFilters['aliasState'] = [DocumentWorkflow::ALIAS_STATE_DISCARDED];
        }
    }


    /**
     * Toggles the hide bookmarks filter.
     *
     */
    public function toggleBookmarksOnlyFilter()
    {
        if (array_key_exists('bookmarks', $this->excludeFilters)) {
            unset($this->excludeFilters['bookmarks']);
        } else {
            $this->excludeFilters['bookmarks'] = true;
        }
    }

    /**
     * Sets a filter
     *
     * @param $name
     * @param array $values
     */
    public function setFilter($name, $values = [])
    {
        if ($name && $values && is_array($values)) {
            $this->filters[$name] = $values;
        } elseif ($name) {
            unset($this->filters[$name]);
        }
    }


    /**
     * Removes all filters
     *
     * @return array
     */
    public function clearFilters()
    {
        $this->filters = [];
    }


    /**
     * Gets the filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }


    /**
     * Removes the sort information
     *
     */
    public function clearSort()
    {
        $this->sortField = '';
        $this->sortOrder = '';
    }

    /**
     * Gets the sort field
     *
     * @return string
     */
    public function getSortField()
    {
        return $this->sortField;
    }

    /**
     * Sets the sort field
     *
     * @param string $fieldName
     */
    public function setSortField($fieldName)
    {
        $this->sortField = $fieldName;
    }

    /**
     * Gets the sort order
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Sets the sort order
     *
     * @param string $order
     */
    public function setSortOrder($order)
    {
        $this->sortOrder = $order;
    }

    /**
     * Gets the simple query string
     *
     * @return string
     */
    public function getSimpleQuery()
    {
        return $this->simpleQuery;
    }

    /**
     * Sets the simple query string
     *
     * @param string $query
     */
    public function setSimpleQuery($query)
    {
        $this->simpleQuery = $query;
    }

    /**
     * Gets the items per page for the list.
     *
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * Sets the items per page for the list.
     *
     * @param int $itemsPerPage
     */
    public function setItemsPerPage($itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;
    }


}