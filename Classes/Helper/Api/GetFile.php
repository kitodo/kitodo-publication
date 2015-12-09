<?PHP
	namespace EWW\Dpf\Helper\Api;
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Alexander Bigga <alexander.bigga@slub-dresden.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
* API to return METS or Attachement from Fedora
*
* Example:
 *
 * 1. METS from Fedora
 *   http://localhost/api/qucosa:1234/mets
 *
 * 2. Attachment from Fedora
 *   http://localhost/api/qucosa:1234/attachment/ATT-0
 *
 * 3. METS from Goobi.Publication (this extension)
 *   http://localhost/api/3/preview
 *
*
* @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
*/

	use TYPO3\CMS\Core\Utility\GeneralUtility;

class GetFile {

	/**
	 * documentRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository;

	public function attachement($content, $conf) {

		$piVars = GeneralUtility::_GP('tx_dpf'); // get GET params from powermail

		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

		switch ($piVars['action']) {
			case 'mets':
				$path = rtrim($extConf['repositoryServerAddress'],"/").'/fedora/objects/'.$piVars['qid'].'/methods/qucosa:SDef/getMETSDissemination';
				break;
			case 'preview':
				$objectManager = GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');

				$this->documentRepository= $objectManager->get('\EWW\Dpf\Domain\Repository\DocumentRepository');

				$document = $this->documentRepository->findByUid($piVars['qid']);

				// Build METS-Data
				$exporter = new \EWW\Dpf\Services\MetsExporter();

				$fileData = $document->getCurrentFileData();

				$exporter->setFileData($fileData);

				$exporter->setMods($document->getXmlData());

				$exporter->setSlubInfo($document->getSlubInfoData());

				$exporter->buildMets();

				$metsXml = $exporter->getMetsData();

				header('Content-Type: text/xml; charset=UTF-8');

				return $metsXml;

			case 'attachment':
				$path = rtrim($extConf['repositoryServerAddress'],"/").'/fedora/objects/'.$piVars['qid'].'/datastreams/'.$piVars['attachment'].'/content';
				break;
			default:
				break;
		}

		// get remote header and set it before passtrough
		$headers = get_headers($path);

		foreach ($headers as $key => $value) {
			// set remote header information
			preg_match('/filename="(.*)"/', $value, $fileName);

			if($fileName[1]) {
				header('Content-Disposition: inline; filename="'.$fileName[1].'";');
				continue;
			}

			if(substr($value, 0, 13) == "Content-Type:") {
				header($value);
				continue;
			}

			if(substr($value, 0, 13) == "Content-Length:") {
				header($value);
				continue;
			}
		}

		if ($stream = fopen($path, 'r')) {

			fpassthru($stream);

			fclose($stream);

		}


	}

}
