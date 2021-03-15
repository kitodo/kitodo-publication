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


class BulkImportSessionData
{
    /**
     * @var string
     */
    protected $crossRefSearchField = 'author';

    /**
     * @var string
     */
    protected $pubMedSearchField = 'author';

    /**
     * @var string
     */
    protected $crossRefQuery = '';

    /**
     * @var string
     */
    protected $pubMedQuery = '';

    /**
     * @var array
     */
    protected $currentMetadataItems = [];

    /**
     * @var int
     */
    protected $itemsPerPage = 0;

    /**
     * @var array
     */
    protected $latestImportIdentifiers = [];

    /**
     * Gets the CrossRef search field.
     *
     * @return string
     */
    public function getCrossRefSearchField(): string
    {
        return $this->crossRefSearchField;
    }

    /**
     * Sets the CrossRef search field.
     *
     * @param string $searchField
     */
    public function setCrossRefSearchField($searchField): void
    {
        $this->crossRefSearchField = $searchField;
    }

    /**
     * @return string
     */
    public function getPubMedSearchField(): string
    {
        return $this->pubMedSearchField;
    }

    /**
     * @param string $pubMedSearchField
     */
    public function setPubMedSearchField(string $pubMedSearchField): void
    {
        $this->pubMedSearchField = $pubMedSearchField;
    }
    
    /**
     * @return string
     */
    public function getCrossRefQuery(): string
    {
        return $this->crossRefQuery;
    }

    /**
     * @param string $crossRefQuery
     */
    public function setCrossRefQuery(string $crossRefQuery): void
    {
        $this->crossRefQuery = $crossRefQuery;
    }

    /**
     * @return string
     */
    public function getPubMedQuery(): string
    {
        return $this->pubMedQuery;
    }

    /**
     * @param string $pubMedQuery
     */
    public function setPubMedQuery(string $pubMedQuery): void
    {
        $this->pubMedQuery = $pubMedQuery;
    }

    /**
     * @return array
     */
    public function getCurrentMetadataItems(): array
    {
        return $this->currentMetadataItems;
    }

    /**
     * @param array $currentMetadataItems
     */
    public function setCurrentMetadataItems(array $currentMetadataItems): void
    {
        $this->currentMetadataItems = $currentMetadataItems;
    }

    /**
     * Gets the items per page for the list.
     *
     * @return int
     */
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * Sets the items per page for the list.
     *
     * @param int $itemsPerPage
     */
    public function setItemsPerPage($itemsPerPage): void
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * @return array
     */
    public function getLatestImportIdentifiers(): array
    {
        return $this->latestImportIdentifiers;
    }

    /**
     * @param array $latestImportIdentifiers
     */
    public function setLatestImportIdentifiers(array $latestImportIdentifiers): void
    {
        $this->latestImportIdentifiers = $latestImportIdentifiers;
    }
}