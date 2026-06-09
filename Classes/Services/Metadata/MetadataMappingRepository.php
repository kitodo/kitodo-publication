<?php
namespace EWW\Dpf\Services\Metadata;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Direct-SQL access to the tx_dpf_metadata mapping table.
 *
 * Replaces DLF's queries against tx_dlf_metadata/tx_dlf_metadataformat/
 * tx_dlf_formats — the dpf table is denormalized, so no joins are needed.
 */
class MetadataMappingRepository
{
    const TABLE = 'tx_dpf_metadata';

    /**
     * Per-request memoization, keyed by query signature.
     *
     * @var array
     */
    protected static $cache = [];

    /**
     * All extraction rules for the given config PID and dmdSec format type.
     *
     * Two passes, merged in DLF order: rows with a format and XPath first,
     * then format-less default-value rows. Hidden rows are included —
     * DLF removes the HiddenRestriction for these queries.
     *
     * @param int $cPid
     * @param string $formatType 'MODS' or 'SLUB'
     * @return array rows of [index_name, xpath, xpath_sorting, is_sortable, default_value, format]
     */
    public function findExtractionRules(int $cPid, string $formatType): array
    {
        $cacheKey = 'rules:' . $cPid . ':' . $formatType;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        $withFormat = $connection->executeQuery(
            'SELECT index_name, xpath, xpath_sorting, is_sortable, default_value, format'
            . ' FROM ' . self::TABLE
            . ' WHERE deleted = 0 AND l18n_parent = 0 AND pid = ? AND format > 0 AND format_type = ?'
            . ' ORDER BY uid ASC',
            [$cPid, $formatType]
        )->fetchAll();

        $withoutFormat = $connection->executeQuery(
            'SELECT index_name, \'\' AS xpath, \'\' AS xpath_sorting, is_sortable, default_value, format'
            . ' FROM ' . self::TABLE
            . ' WHERE deleted = 0 AND l18n_parent = 0 AND pid = ? AND format = 0 AND default_value != \'\''
            . ' ORDER BY uid ASC',
            [$cPid]
        )->fetchAll();

        self::$cache[$cacheKey] = array_merge($withFormat, $withoutFormat);
        return self::$cache[$cacheKey];
    }

    /**
     * All renderable field definitions for the Metadata plugin, in sorting
     * order. Language filtering matches DLF: default/all languages plus the
     * given one; translation overlay is the caller's concern (needs TSFE).
     *
     * @param int $cPid
     * @param int $sysLanguageUid
     * @return array rows of [uid, index_name, label, wrap, is_listed, sys_language_uid]
     */
    public function findRenderableFields(int $cPid, int $sysLanguageUid = 0): array
    {
        $cacheKey = 'fields:' . $cPid . ':' . $sysLanguageUid;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        self::$cache[$cacheKey] = $connection->executeQuery(
            'SELECT uid, index_name, label, wrap, is_listed, sys_language_uid'
            . ' FROM ' . self::TABLE
            . ' WHERE deleted = 0 AND hidden = 0 AND l18n_parent = 0 AND pid = ?'
            . ' AND (sys_language_uid IN (-1, 0) OR sys_language_uid = ?)'
            . ' ORDER BY sorting ASC',
            [$cPid, $sysLanguageUid]
        )->fetchAll();

        return self::$cache[$cacheKey];
    }
}
