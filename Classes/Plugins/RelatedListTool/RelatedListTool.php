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
class RelatedListTool extends \tx_dlf_plugin
{

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

            $this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

        } else {

            $this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dpf/Classes/Plugins/RelatedListTool/template.tmpl'), '###TEMPLATE###');

        }

        $subpartArray['items'] = $this->cObj->getSubpart($this->template, '###ITEMS###');

        $relatedItems = $this->getRelatedItems();

        $content = '';

        if (!empty($relatedItems)) {
            foreach ($relatedItems as $key => $value) {
                // set link
                if ($value['type'] == 'local') {
                    $confApi = array(
                        'useCacheHash'     => 0,
                        'parameter'        => $this->conf['apiPid'],
                        'additionalParams' => '&tx_dpf[qid]=' . $value['docId'] . '&tx_dpf[action]=mets',
                        'forceAbsoluteUrl' => true,
                    );

                    $metsApiUrl = urlencode($this->cObj->typoLink_URL($confApi));

                    $conf = array(
                        'useCacheHash'     => 1,
                        'parameter'        => $GLOBALS['TSFE']->page['uid'],
                        'additionalParams' => '&tx_dlf[id]=' . $metsApiUrl,
                        'forceAbsoluteUrl' => true,
                    );

                } elseif ($value['type'] == 'urn') {
                    // use urn link
                    $conf = array(
                        'useCacheHash'     => 0,
                        'parameter'        => 'http://nbn-resolving.de/' . $value['docId'],
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

                $content .= $this->cObj->substituteMarkerArray($subpartArray['items'], $markerArray);
            }
        }

        return $this->cObj->substituteSubpart($this->template, '###ITEMS###', $content, true);

    }

    public function getRelatedItems()
    {
        $xPath = '//mods:relatedItem[@type="constituent"]';

        $items = $this->doc->mets->xpath($xPath);

        foreach ($items as $key => $value) {

            $value->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

            $title = (string) $value->xpath('mods:titleInfo/mods:title')[0];

            $type = (string) $value->xpath('mods:identifier/@type')[0];

            $docId = (string) $value->xpath('mods:identifier[@type="' . $type . '"]')[0];

            $order = (string) $value->xpath('mods:part/@order')[0];

            $tempArray          = array();
            $tempArray['type']  = $type;
            $tempArray['docId'] = $docId;
            $tempArray['title'] = $title;

            $relatedItems[$order] = $tempArray;

        }

        ksort($relatedItems);
        return $relatedItems;
    }

}
