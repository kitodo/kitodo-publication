<?php
	namespace EWW\Dpf\Plugins\DownloadTool;

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
 * Plugin 'DPF: DownloadTool' for the 'dlf / dpf' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dpf
 * @access	public
 */
class DownloadTool extends \tx_dlf_plugin {


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

		if ($this->doc === NULL || empty($this->conf['fileGrpDownload'])) {

			// Quit without doing anything if required variables are not set.
			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dpf/Classes/Plugins/DownloadTool/template.tmpl'), '###TEMPLATE###');

		}

		$subpartArray['downloads'] = $this->cObj->getSubpart($this->template, '###DOWNLOADS###');

		// Show all PDF Documents
		$attachments = $this->getAttachments();

		$content = '';

		if (is_array($attachments)) {

			foreach ($attachments as $id => $file) {

				$conf = array(
					'useCacheHash' => 0,
					'parameter' => $this->conf['apiPid'],
					'additionalParams' => '&tx_dpf[qid]=' . $this->doc->recordId . '&tx_dpf[action]=attachment' . '&tx_dpf[attachment]=' . $file['ID'],
					'forceAbsoluteUrl' => TRUE
				);

				$title = $file['LABEL'] ? $file['LABEL'] : $file['ID'];

				// replace uid with URI to dpf API
				$markerArray['###FILE###'] = $this->cObj->typoLink($title, $conf);


				$content .= $this->cObj->substituteMarkerArray($subpartArray['downloads'], $markerArray);

			}

		}

		return $this->cObj->substituteSubpart($this->template, '###DOWNLOADS###', $content, TRUE);

	}

	/**
	 * Get PDF document list
	 * @return html List of attachments
	 */
	protected function getAttachments() {

		// Get pdf documents

		$xPath = 'mets:fileSec/mets:fileGrp[@USE="'.$this->conf['fileGrpDownload'].'"]/mets:file';

		$this->doc->mets->registerXPathNamespace('mext', 'http://slub-dresden.de/mets');

		$files = $this->doc->mets->xpath($xPath);

		foreach ($files as $key => $file) {

			$singleFile = array();

			foreach ($file->attributes('mext', 1) as $attribute => $value) {

				$singleFile[$attribute] = $value;

			}

			foreach ($file->attributes() as $attribute => $value) {

				$singleFile[$attribute] = $value;

			}

			$attachments[] = $singleFile;
		}

		return $attachments;
	}

}
