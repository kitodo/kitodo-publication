<?php
	namespace EWW\Dpf\ViewHelpers\Link;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Alexander Bigga <alexander.bigga@slub-dresden.de>, SLUB Dresden
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

	use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
	use TYPO3\CMS\Core\Utility\GeneralUtility;
	use TYPO3\CMS\Core\Utility\MathUtility;

class PreviewViewHelper extends AbstractBackendViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * documentRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository;


	protected function initTSFE($id = 1, $typeNum = 0) {
		if (!is_object($GLOBALS['TT'])) {
			$GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
			$GLOBALS['TT']->start();
		}
		$GLOBALS['TSFE'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',  $GLOBALS['TYPO3_CONF_VARS'], $id, $typeNum);
		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();

		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl')) {
			$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id);
			$host = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootline);
			$_SERVER['HTTP_HOST'] = $host;
		}
	}

	/**
	 * Returns the View Icon with link
	 *
	 * @param array $row Data row
	 * @param integer $viewPage Detail View page id
	 * @param  integer $apiPid
	 * @param  string $insideText
	 * @param  string $class
	 * @return string html output
	 */
	protected function getViewIcon(array $row, $pageUid, $apiPid, $insideText, $class) {

		// Build typolink configuration array.
		$conf = array (
			'useCacheHash' => 0,
			'parameter' => $apiPid,
			'additionalParams' => '&tx_dpf[qid]=' . $row['uid'] . '&tx_dpf[action]=' . $row['action'],
			'forceAbsoluteUrl' => TRUE
		);

		// we need to make instance of cObj here because its not available in this context
		$this->initTSFE($apiPid, 0);
		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
		$cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

		// replace uid with URI to dpf API
		$previewMets = $cObj->typoLink_URL($conf);

		$additionalGetVars = '&tx_dlf[id]=' . urlencode($previewMets) . '&no_cache=1';
		$title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('manager.tooltip.preview', 'dpf', $arguments=NULL);
		$icon = '<a href="#" data-toggle="tooltip" class="'. $class . '" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($pageUid, $this->backPath, '', '', '', $additionalGetVars)) . '" title="' . $title . '">' .
			$insideText .'</a>';

		return $icon;

	}

	/**
	 * Renders a record list as known from the TYPO3 list module
	 * Note: This feature is experimental!
	 *
	 * @param array() $arguments
	 * @param  integer $pageUid
	 * @param  integer $apiPid
	 * @param  string $class
	 * @return string the rendered record list
	 */
	public function render(array $arguments, $pageUid, $apiPid, $class) {

		if ($arguments['document']) {

			// it's already a document object?
			if ($arguments['document'] instanceof \EWW\Dpf\Domain\Model\Document) {

				$document = $arguments['document'];

			} else if (MathUtility::canBeInterpretedAsInteger($arguments['document'])) {

				$document = $this->documentRepository->findByUid($arguments['document']);

			}

			// we found a valid document
			if ($document) {

				$row['uid'] = $document->getUid();

				$row['title'] = $document->getTitle();

				$row['action'] = 'preview';

			} else {

				// ok, nothing to render. So return empty content.
				return '';

			}

		} else if ($arguments['documentObjectIdentifier']) {

			$row['action'] = 'mets';

			$row['uid'] = $arguments['documentObjectIdentifier'];

		}

		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		$row['storagePid'] = $frameworkConfiguration['persistence']['storagePid'];

		$insideText = $this->renderChildren();

		$content = $this->getViewIcon($row, $pageUid, $apiPid, $insideText, $class);

		return $content;

	}
}
?>
