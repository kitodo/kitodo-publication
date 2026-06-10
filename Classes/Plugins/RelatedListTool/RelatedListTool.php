<?php

namespace EWW\Dpf\Plugins\RelatedListTool;

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

/**
 * Plugin 'DPF: RElatedListTool' for the 'dlf / dpf' extension.
 *
 * @author    Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author    Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package    TYPO3
 * @subpackage    tx_dpf
 * @access    public
 */
class RelatedListTool extends \EWW\Dpf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugins/RelatedListTool.php';

    /**
     * The main method of the PlugIn
     *
     * @access    public
     *
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     *
     * @return    string        The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);

        // get the tx_dpf.settings too
        // Flexform wins over TS
        $dpfTSconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.'];
        if (is_array($dpfTSconfig['settings.'])) {
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($dpfTSconfig['settings.'], $this->conf, true, false);
            $this->conf = $dpfTSconfig['settings.'];
        }

        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return $content;
        }

        // Load template file.
        if (!empty($this->conf['templateFile'])) {
            $this->template = $this->templateService->getSubpart(file_get_contents($this->conf['templateFile']), '###TEMPLATE###');
        } else {
            $this->template = $this->templateService->getSubpart(file_get_contents($GLOBALS['TSFE']->tmpl->getFileName('EXT:dpf/Classes/Plugins/RelatedListTool/template.tmpl')), '###TEMPLATE###');
        }
        $subpartArray['items'] = $this->templateService->getSubpart($this->template, '###ITEMS###');

        $relatedItems = $this->getRelatedItems();
        $content = '';
        if (!empty($relatedItems)) {
            foreach ($relatedItems as $key => $value) {
                // set link
                if ($value['type'] == 'local') {
                    $conf = array(
                        'useCacheHash'     => 1,
                        'parameter'        => $this->conf['landingPage'] ?: $GLOBALS['TSFE']->page['uid'],
                        'additionalParams' => '&tx_dpf[qid]=' . rawurlencode(strtolower($value['docId'])),
                        'forceAbsoluteUrl' => true,
                    );
                } elseif ($value['type'] == 'urn') {
                    // use urn link
                    $conf = array(
                        'useCacheHash'     => 0,
                        'parameter'        => 'https://nbn-resolving.de/' . $value['docId'],
                        'forceAbsoluteUrl' => true,
                    );
                } else {
                    $conf = array(
                        'useCacheHash'     => 0,
                        'forceAbsoluteUrl' => true,
                    );
                }
                $title = $value['title'] ? $value['title'] : $value['docId'];
                // replace uid with URI to dpf API
                $markerArray['###ITEM###'] = $this->cObj->typoLink($title, $conf);
                $content .= $this->templateService->substituteMarkerArray($subpartArray['items'], $markerArray);
            }
        }
        return $this->templateService->substituteSubpart($this->template, '###ITEMS###', $content, true);
    }

    /**
     * First string value of an XPath query, or '' when nothing matches —
     * constituent relatedItem nodes may lack any of the optional children.
     *
     * @param \SimpleXMLElement $element
     * @param string $xpath
     * @return string
     */
    private function firstXPathValue(\SimpleXMLElement $element, $xpath)
    {
        $result = $element->xpath($xpath);
        if (!empty($result)) {
            return (string) $result[0];
        }
        return '';
    }

    private function compareByOrderVolumeTitle($a, $b)
    {
        $s1 = join(' ', array($a['order'], $a['volume'], $a['title']));
        $s2 = join(' ', array($b['order'], $b['volume'], $b['title']));
        return strnatcmp($s1, $s2);
    }

    public function getRelatedItems()
    {
        $xPath = '//mods:relatedItem[@type="constituent"]';
        $items = $this->doc->mets->xpath($xPath);
        $relatedItems = array();

        foreach ($items as $index => $relatedItemXmlElement) {
            $relatedItemXmlElement->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
            $relatedItemXmlElement->registerXPathNamespace('slub', 'http://slub-dresden.de/');

            $type = $this->firstXPathValue($relatedItemXmlElement, 'mods:identifier/@type');
            $title = $this->firstXPathValue($relatedItemXmlElement, 'mods:titleInfo/mods:title');
            $docId = '';
            if ($type !== '') {
                $docId = $this->firstXPathValue($relatedItemXmlElement, 'mods:identifier[@type="' . $type . '"]');
            }
            $order = $this->firstXPathValue($relatedItemXmlElement, 'mods:extension/slub:info/slub:sortingKey');
            $volume = $this->firstXPathValue($relatedItemXmlElement, 'mods:part[@type="volume" or @type="issue"]/mods:detail/mods:number');

            $element = array();
            $element['type'] = $type;
            $element['title'] = $title;
            $element['docId'] = $docId;
            $element['order'] = (!empty($order)) ? $order : null;
            $element['volume'] = (!empty($volume)) ? $volume : null;

            $relatedItems[$index] = $element;
        }
        usort($relatedItems, array('EWW\Dpf\Plugins\RelatedListTool\RelatedListTool', 'compareByOrderVolumeTitle'));
        return $relatedItems;
    }

}
