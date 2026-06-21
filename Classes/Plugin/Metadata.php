<?php
namespace EWW\Dpf\Plugin;

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

use EWW\Dpf\Common\AbstractPlugin;
use EWW\Dpf\Common\MetsDocument;
use EWW\Dpf\Helper\MetadataExtractor;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * dpf-native Metadata plugin. Replaces Kitodo\Dlf\Plugin\Metadata.
 *
 * Registered as list_type 'dpf_metadata'. Reads metadata configuration
 * from tx_dpf_metadata (migrated from tx_dlf_metadata + tx_dlf_metadataformat).
 * Fetches METS via Redis/Fedora direct — no HTTP self-loop.
 */
class Metadata extends AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Metadata.php';

    public function main($content, $conf)
    {
        $this->init($conf);
        $this->setCache(true);
        $this->loadDocument();

        if ($this->doc === null || !$this->doc->ready) {
            return $content;
        }

        $mods = $this->doc->getMods();
        if ($mods === null) {
            return $content;
        }

        $pid = (int) ($this->conf['pages'] ?? 0);
        $metaList = $this->getMetaList($pid);
        if (empty($metaList)) {
            return $content;
        }

        $metadata = MetadataExtractor::extract($mods, $metaList);

        if (!empty($this->doc->recordId) && !in_array($this->doc->recordId, $metadata['record_id'] ?? [])) {
            array_unshift($metadata['record_id'], $this->doc->recordId);
        }
        $metadata['_id'] = $this->doc->toplevelId;

        $content .= $this->printMetadata([$metadata], $metaList);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Render extracted metadata using wrap configuration from tx_dpf_metadata.
     *
     * @param array $metadataArray array of metadata records (index_name => [values])
     * @param array $metaList index_name => [label, wrap, ...]
     * @return string
     */
    protected function printMetadata(array $metadataArray, array $metaList)
    {
        $this->getTemplate();
        if (empty($this->template)) {
            return '';
        }

        $output = '';
        $subpart = $this->templateService->getSubpart($this->template, '###BLOCK###');
        $cObjData = $this->cObj->data;

        foreach ($metadataArray as $metadata) {
            $markerArray['###METADATA###'] = '';
            $this->cObj->data = $cObjData;

            foreach ($metadata as $index_name => $value) {
                $this->cObj->data[$index_name] = is_array($value)
                    ? implode($this->conf['separator'] ?? ', ', $value)
                    : $value;
            }

            foreach ($metaList as $index_name => $metaConf) {
                $parsedValue = '';
                $fieldwrap = $this->parseTS($metaConf['wrap']);

                do {
                    $value = @array_shift($metadata[$index_name]);
                    if ($index_name === 'language' && !empty($value)) {
                        $value = htmlspecialchars($this->getLanguageName($value));
                    } elseif (!empty($value)) {
                        $value = htmlspecialchars($value);
                    }
                    $value = $this->cObj->stdWrap($value, $fieldwrap['value.'] ?? []);
                    if (!empty($value)) {
                        $parsedValue .= $value;
                    }
                } while (is_array($metadata[$index_name]) && count($metadata[$index_name]) > 0);

                if (!empty($parsedValue)) {
                    $field = $this->cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.'] ?? []);
                    $field .= $parsedValue;
                    $markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.'] ?? []);
                }
            }

            $output .= $this->templateService->substituteMarkerArray($subpart, $markerArray);
        }

        return $this->templateService->substituteSubpart($this->template, '###BLOCK###', $output, true);
    }

    /**
     * Read displayable fields from tx_dpf_metadata for the given storage PID.
     *
     * Respects is_listed flag unless conf['showFull'] is set. Merges language
     * overlay rows so that the user's current language is preferred.
     *
     * @param int $pid Storage PID (cPid)
     * @return array index_name => [label, wrap, xpath, xpath_sorting, default_value, is_listed]
     */
    protected function getMetaList($pid)
    {
        $langUid = (int) $GLOBALS['TSFE']->sys_language_uid;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dpf_metadata');

        $result = $queryBuilder
            ->select('index_name', 'is_listed', 'wrap', 'label', 'xpath', 'xpath_sorting', 'default_value', 'sys_language_uid')
            ->from('tx_dpf_metadata')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('sys_language_uid', $langUid)
                    ),
                    $queryBuilder->expr()->eq('l18n_parent', 0),
                    $queryBuilder->expr()->eq('pid', $pid)
                )
            )
            ->orderBy('sorting')
            ->execute();

        $metaList = [];
        while ($row = $result->fetch()) {
            if (!$row) {
                continue;
            }

            if ($row['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content
                && $GLOBALS['TSFE']->sys_language_contentOL
            ) {
                $row = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
                    'tx_dpf_metadata',
                    $row,
                    $GLOBALS['TSFE']->sys_language_content,
                    $GLOBALS['TSFE']->sys_language_contentOL
                );
            }

            if ($row && ($this->conf['showFull'] || $row['is_listed'])) {
                $metaList[$row['index_name']] = [
                    'label' => $row['label'] ?: $row['index_name'],
                    'wrap' => $row['wrap'] ?? '',
                    'xpath' => $row['xpath'] ?? '',
                    'xpath_sorting' => $row['xpath_sorting'] ?? '',
                    'default_value' => $row['default_value'] ?? '',
                    'is_listed' => (int) $row['is_listed'],
                ];
            }
        }

        return $metaList;
    }

    /**
     * Resolve an ISO-639 language code to a localized name.
     *
     * Falls back to the raw code on lookup failure.
     *
     * @param string $code
     * @return string
     */
    protected function getLanguageName($code)
    {
        $isoCode = strtolower(trim($code));
        if (preg_match('/^[a-z]{3}$/', $isoCode)) {
            $file = ExtensionManagementUtility::extPath('dpf')
                . 'Resources/Private/Data/iso-639-2b.xml';
        } elseif (preg_match('/^[a-z]{2}$/', $isoCode)) {
            $file = ExtensionManagementUtility::extPath('dpf')
                . 'Resources/Private/Data/iso-639-1.xml';
        } else {
            return $code;
        }

        if (!file_exists($file)) {
            return $code;
        }

        $iso639 = $GLOBALS['TSFE']->readLLfile($file);
        if (!empty($iso639['default'][$isoCode])) {
            $name = $GLOBALS['TSFE']->getLLL($isoCode, $iso639);
            if (!empty($name)) {
                return $name;
            }
        }

        return $code;
    }
}
