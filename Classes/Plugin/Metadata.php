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

use EWW\Dpf\Services\Metadata\MetadataMappingRepository;

/**
 * Plugin 'DPF: Metadata' for the 'dpf' extension.
 *
 * dpf-native port of \Kitodo\Dlf\Plugin\Metadata (v3.3.4) rendering against
 * the dpf-owned tx_dpf_metadata table. Uses the tx_dpf[qid] parameter
 * namespace and loads the METS document via GetFileController as an
 * authenticated proxy.
 *
 * Deviations from DLF (single-format Qucosa documents, no DLF database):
 * - No IIIF support, no physical-structure/rootline walking — documents have
 *   no physical pages, rendering is always the toplevel title data.
 * - owner/type/collection/language values are translated via the TypoScript
 *   label map plugin.tx_dpf_metadata.labels.<index_name>.<value> instead of
 *   the tx_dlf_libraries/structures/collections tables; unmapped values
 *   render as-is.
 * - No parent-title lookup (conf getTitle) — DLF resolved it from
 *   tx_dlf_documents, which never applied to URL-loaded documents.
 */
class Metadata extends \EWW\Dpf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Metadata.php';

    public function main($content, $conf)
    {
        $this->init($conf);

        // Merge plugin.tx_dpf.settings.* so apiPid, landingPage etc. are
        // available as $this->conf['apiPid'] — same pattern as MetaTags/Coins/etc.
        $dpfTSconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.'];
        if (is_array($dpfTSconfig['settings.'])) {
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($dpfTSconfig['settings.'], $this->conf, true, false);
            $this->conf = $dpfTSconfig['settings.'];
        }

        $this->setCache(true);
        $this->loadDocument();

        if ($this->doc === null) {
            return $this->unavailableMessage($content);
        }

        $data = $this->doc->getTitleData((int) $this->conf['pages']);
        if (empty($data)) {
            return $this->unavailableMessage($content);
        }
        $data['_id'] = $this->doc->toplevelId;

        $content .= $this->printMetadata([$data]);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * @param string $content
     * @return string
     */
    protected function unavailableMessage($content)
    {
        if (!empty($this->piVars['qid'])) {
            return '<p class="dpf-document-unavailable">'
                . htmlspecialchars($this->pi_getLL('document_unavailable', 'The requested document could not be displayed.'))
                . '</p>';
        }
        return $content;
    }

    /**
     * Prepares the metadata array for output — port of DLF
     * Plugin\Metadata::printMetadata() (non-IIIF path) against
     * tx_dpf_metadata.
     *
     * @param array $metadataArray
     * @return string
     */
    protected function printMetadata(array $metadataArray)
    {
        // Load template file.
        $this->getTemplate();
        $output = '';
        $subpart['block'] = $this->templateService->getSubpart($this->template, '###BLOCK###');
        // Save original data array.
        $cObjData = $this->cObj->data;

        // Get list of metadata to show.
        $metaList = [];
        $repository = new MetadataMappingRepository();
        $fields = $repository->findRenderableFields(
            (int) $this->conf['pages'],
            (int) $GLOBALS['TSFE']->sys_language_uid
        );
        foreach ($fields as $resArray) {
            if (
                $resArray['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content
                && $GLOBALS['TSFE']->sys_language_contentOL
            ) {
                $resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
                    'tx_dpf_metadata',
                    $resArray,
                    $GLOBALS['TSFE']->sys_language_content,
                    $GLOBALS['TSFE']->sys_language_contentOL
                );
            }
            if ($resArray) {
                if ($this->conf['showFull'] || $resArray['is_listed']) {
                    $metaList[$resArray['index_name']] = [
                        'wrap' => $resArray['wrap'],
                        'label' => $resArray['label'] ?: $resArray['index_name'],
                    ];
                }
            }
        }

        // Parse the metadata arrays.
        foreach ($metadataArray as $metadata) {
            $markerArray['###METADATA###'] = '';
            // Reset content object's data array.
            $this->cObj->data = $cObjData;
            // Load all the metadata values into the content object's data array.
            foreach ($metadata as $index_name => $value) {
                if (is_array($value)) {
                    $this->cObj->data[$index_name] = implode($this->conf['separator'], $value);
                } else {
                    $this->cObj->data[$index_name] = $value;
                }
            }
            // Process each metadate.
            foreach ($metaList as $index_name => $metaConf) {
                $parsedValue = '';
                $fieldwrap = $this->parseTS($metaConf['wrap']);
                do {
                    $value = @array_shift($metadata[$index_name]);
                    if ($index_name == 'title') {
                        if (!empty($value)) {
                            $value = htmlspecialchars($value);
                            // Link title to the landing page (self-link).
                            if ($this->conf['linkTitle'] && $metadata['_id'] && !empty($this->piVars['qid'])) {
                                $value = $this->pi_linkTP(
                                    $value,
                                    [$this->prefixId => ['qid' => $this->piVars['qid']]],
                                    true,
                                    $this->conf['targetPid']
                                );
                            }
                        }
                    } elseif (in_array($index_name, ['owner', 'type', 'collection', 'language'], true) && !empty($value)) {
                        $value = htmlspecialchars($this->translateValue($index_name, $value));
                    } elseif (!empty($value)) {
                        // Sanitize value for output.
                        $value = htmlspecialchars($value);
                    }
                    // $value might be empty for aggregation metadata fields.
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
            $output .= $this->templateService->substituteMarkerArray($subpart['block'], $markerArray);
        }
        return $this->templateService->substituteSubpart($this->template, '###BLOCK###', $output, true);
    }

    /**
     * Translate a metadata value via the TypoScript label map
     * plugin.tx_dpf_metadata.labels.<index_name>.<value>; falls back to the
     * raw value.
     *
     * @param string $indexName
     * @param string $value
     * @return string
     */
    protected function translateValue($indexName, $value)
    {
        $labels = $this->conf['labels.'][$indexName . '.'] ?? null;
        if (is_array($labels) && isset($labels[$value]) && is_string($labels[$value])) {
            return $labels[$value];
        }
        return $value;
    }
}
