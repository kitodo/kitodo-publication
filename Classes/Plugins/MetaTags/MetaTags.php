<?php
	namespace EWW\Dpf\Plugins\MetaTags;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
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

/**
 * Plugin 'DPF: MetaTags' for the 'dlf / dpf' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dpf
 * @access	public
 */
class MetaTags extends \tx_dlf_plugin {

	/**
	 * @type \TYPO3\CMS\Core\Page\PageRenderer
	 * @inject
	 */
	protected $pageRenderer;

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

		// Turn cache on.
		$this->setCache(TRUE);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values if not set.
			if (!isset($this->conf['rootline'])) {

				$this->conf['rootline'] = 0;

			}

		}

		$metadata = array ();

		if ($this->conf['rootline'] < 2) {

			// Get current structure's @ID.
			$ids = array ();

			if (!empty($this->doc->physicalPages[$this->piVars['page']]) && !empty($this->doc->smLinks['p2l'][$this->doc->physicalPages[$this->piVars['page']]])) {

				foreach ($this->doc->smLinks['p2l'][$this->doc->physicalPages[$this->piVars['page']]] as $logId) {

					$count = count($this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$logId.'"]/ancestor::*'));

					$ids[$count][] = $logId;

				}

			}

			ksort($ids);

			reset($ids);

			// Check if we should display all metadata up to the root.
			if ($this->conf['rootline'] == 1) {

				foreach ($ids as $id) {

					foreach ($id as $sid) {

						$data = $this->doc->getMetadata($sid, $this->conf['pages']);

						if (!empty($data)) {

							$data['_id'] = $sid;

							$metadata[] = $data;

						}

					}

				}

			} else {

				$id = array_pop($ids);

				if (is_array($id)) {

					foreach ($id as $sid) {

						$data = $this->doc->getMetadata($sid, $this->conf['pages']);

						if (!empty($data)) {

							$data['_id'] = $sid;

							$metadata[] = $data;

						}

					}

				}

			}

		}

		// Get titledata?
		if (empty($metadata) || ($this->conf['rootline'] == 1 && $metadata[0]['_id'] != $this->doc->toplevelId)) {

			$data = $this->doc->getTitleData($this->conf['pages']);

			$data['_id'] = $this->doc->toplevelId;

			array_unshift($metadata, $data);

		}

		if (empty($metadata)) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_metadata->main('.$content.', [data])] No metadata found for document with UID "'.$this->doc->uid.'"', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);

			}

			return;

		}

		ksort($metadata);


		$this->printMetaTags($metadata);

		return;

	}

	/**
	 * Prepares the metadata array for output
	 *
	 * @access	protected
	 *
	 * @param	array		$metadataArray: The metadata array
	 *
	 * @return	string		The metadata array ready for output
	 */
	protected function printMetaTags(array $metadataArray) {

		$output = '';

		// Parse the metadata arrays.
		foreach ($metadataArray as $metadata) {

			// Load all the metadata values into the content object's data array.
			foreach ($metadata as $index_name => $value) {

				switch ($index_name) {

					case 'author':

						if (is_array($value)) {

							foreach ($value as $id => $author) {

								$outArray['citation_author'][] = $author;

							}

						}

						break;

					default:

						break;

				}

			}

		}

		foreach ($outArray as $tagName => $values) {

			foreach ($values as $value) {

				$GLOBALS['TSFE']->getPageRenderer()->addMetaTag('<meta name="'. $tagName . '" content="' . $value .'">');

			}

		}

		return $output;

	}

}
