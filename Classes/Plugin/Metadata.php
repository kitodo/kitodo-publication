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

use EWW\Dpf\Common\MetsDocument;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * XClass replacement for \Kitodo\Dlf\Plugin\Metadata.
 *
 * Fetches METS via MetsDocument (Redis/Fedora direct — no HTTP self-loop).
 * Registered as XClass in ext_localconf.php — no TS userFunc override needed.
 *
 * Deviations from DLF Metadata (Qucosa-specific simplifications):
 * - No IIIF support
 * - No physical-structure / rootline page walking
 * - No parent-title lookup (getTitle)
 * - No hooks
 * - language translation via local ISO-639 XML; owner/type/collection raw
 */
class Metadata extends \Kitodo\Dlf\Plugin\Metadata
{
    public $scriptRelPath = 'Classes/Plugin/Metadata.php';

    public function main($content, $conf)
    {
        $this->init($conf);
        $this->setCache(true);

        $location = isset($this->piVars['id']) ? (string) $this->piVars['id'] : '';
        if (empty($location)) {
            return $content;
        }

        $doc = MetsDocument::getInstance($location);
        if ($doc === null) {
            return $content;
        }

        $data = $doc->getTitleData((int) ($this->conf['pages'] ?? 0));
        if (empty($data)) {
            return $content;
        }
        $data['_id'] = $doc->toplevelId;

        $content .= $this->printMetadata([$data]);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Render metadata rows using tx_dlf_metadata wrap config.
     *
     * Non-IIIF path only — port of DLF Plugin\Metadata::printMetadata().
     *
     * @param array $metadataArray
     * @return string
     */
    protected function printMetadata(array $metadataArray)
    {
        $this->getTemplate();
        if (empty($this->template)) {
            return '';
        }
        $output = '';
        $subpart = $this->templateService->getSubpart($this->template, '###BLOCK###');
        $cObjData = $this->cObj->data;
        $metaList = $this->getMetaList();

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
                    $value = $this->cObj->stdWrap($value, $fieldwrap['value.']);
                    if (!empty($value)) {
                        $parsedValue .= $value;
                    }
                } while (is_array($metadata[$index_name]) && count($metadata[$index_name]) > 0);

                if (!empty($parsedValue)) {
                    $field = $this->cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.']);
                    $field .= $parsedValue;
                    $markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);
                }
            }
            $output .= $this->templateService->substituteMarkerArray($subpart, $markerArray);
        }
        return $this->templateService->substituteSubpart($this->template, '###BLOCK###', $output, true);
    }

    /**
     * Read renderable fields from tx_dlf_metadata ordered by sorting.
     *
     * @return array index_name => ['wrap' => string, 'label' => string]
     */
    protected function getMetaList()
    {
        $pid = (int) ($this->conf['pages'] ?? 0);
        $langUid = (int) $GLOBALS['TSFE']->sys_language_uid;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');
        $result = $queryBuilder
            ->select('index_name', 'is_listed', 'wrap', 'sys_language_uid')
            ->from('tx_dlf_metadata')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('sys_language_uid', $langUid)
                    ),
                    $queryBuilder->expr()->eq('l18n_parent', 0)
                ),
                $queryBuilder->expr()->eq('pid', $pid)
            )
            ->orderBy('sorting')
            ->execute();

        $metaList = [];
        while ($resArray = $result->fetch()) {
            if (!$resArray) {
                continue;
            }
            if ($resArray['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content
                && $GLOBALS['TSFE']->sys_language_contentOL
            ) {
                $resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
                    'tx_dlf_metadata',
                    $resArray,
                    $GLOBALS['TSFE']->sys_language_content,
                    $GLOBALS['TSFE']->sys_language_contentOL
                );
            }
            if ($resArray && ($this->conf['showFull'] || $resArray['is_listed'])) {
                $label = \Kitodo\Dlf\Common\Helper::translate(
                    $resArray['index_name'],
                    'tx_dlf_metadata',
                    $pid
                );
                $metaList[$resArray['index_name']] = [
                    'wrap' => $resArray['wrap'],
                    'label' => $label ?: $resArray['index_name'],
                ];
            }
        }
        return $metaList;
    }

    /**
     * Resolve ISO-639 language code to localized name.
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
            $file = ExtensionManagementUtility::extPath('dlf')
                . 'Resources/Private/Data/iso-639-2b.xml';
        } elseif (preg_match('/^[a-z]{2}$/', $isoCode)) {
            $file = ExtensionManagementUtility::extPath('dlf')
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
