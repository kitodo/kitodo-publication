<?php

namespace EWW\Dpf\Plugins\RelatedListTool;
    
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'DPF: RElatedListTool' for the 'dlf / dpf' extension. 
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dpf
 * @access	public
 */
class RelatedListTool extends \tx_dlf_plugin {
	

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// get the tx_dpf.settings too
		// Flexform wins over TS
		$dpfTSconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.'];

		if (is_array($dpfTSconfig['settings.'])) {

			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($dpfTSconfig['settings.'], $this->conf, TRUE, FALSE);
			$this->conf = $dpfTSconfig['settings.'];

		}

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {
			
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

		if(!empty($relatedItems)) {
			foreach ($relatedItems as $key => $value) {
				// set link
				if ($value['type'] == 'local') {
					$confApi = array(
						'useCacheHash' => 0,
						'parameter' => $this->conf['apiPid'],
						'additionalParams' => '&tx_dpf[qid]=' . $value['docId']. '&tx_dpf[action]=mets',
						'forceAbsoluteUrl' => TRUE
					);
                                                                              
                                        
                                        $metsApiUrl = urlencode($this->cObj->typoLink_URL($confApi));
                                                                               
                                        $conf = array(
						'useCacheHash' => 1,
						'parameter' => $GLOBALS['TSFE']->page['uid'],
						'additionalParams' => '&tx_dlf[id]=' . $metsApiUrl,
						'forceAbsoluteUrl' => TRUE
					);
                                                                                                                       
				} elseif($value['type'] == 'urn') {
					// use urn link
                                        $conf = array(
						'useCacheHash' => 0,
						'parameter' => 'http://nbn-resolving.de/'.$value['docId'],						
						'forceAbsoluteUrl' => TRUE
					);
                                        
				} else {
                                    
                                        $conf = array(
						'useCacheHash' => 0,											
						'forceAbsoluteUrl' => TRUE
					);
                                    
                                }
				
				$title = $value['title'] ? $value['title'] : $value['docId'];

				// replace uid with URI to dpf API				                                                                                                                                                                                                                                         

                                $markerArray['###ITEM###'] = $this->cObj->typoLink($title, $conf);
                                
				$content .= $this->cObj->substituteMarkerArray($subpartArray['items'], $markerArray);
			}
		}

		return $this->cObj->substituteSubpart($this->template, '###ITEMS###', $content, TRUE);

	}

	public function getRelatedItems()
	{                                                  
		$xPath = '//mods:relatedItem[@type="constituent"]';     
                                               
		$items = $this->doc->mets->xpath($xPath);                               

		foreach ($items as $key => $value) {
                                                                                                   
                        $title = (string)$value->xpath('mods:titleInfo/mods:title')[0];                                                                                                                                                                                         

		        $type = (string)$value->xpath('mods:identifier/@type')[0];
                                              
			$docId = (string)$value->xpath('mods:identifier[@type="'.$type.'"]')[0];

			$tempArray = array();
			$tempArray['type'] = $type;
			$tempArray['docId'] = $docId;
			$tempArray['title'] = $title;
                                                                                                              
			$relatedItems[] = $tempArray;

		}

		return $relatedItems;
	}

}

?>
