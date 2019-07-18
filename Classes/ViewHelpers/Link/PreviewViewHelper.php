<?php
namespace EWW\Dpf\ViewHelpers\Link;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class PreviewViewHelper extends AbstractBackendViewHelper
{

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
     * Secret API key for delivering inactive documents.
     * @var string
     */
    private $secretKey;


    /**
     * escapeOutput, activates / deactivates HTML escaping.
     *
     * @var bool
     */
    protected $escapeOutput = false;


    /**
     * Initialize secret key from plugin TYPOScript configuration.
     */
    public function initialize() {
        parent::initialize();

        $settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        if (isset($settings['plugin.']['tx_dpf.']['settings.']['deliverInactiveSecretKey'])) {
            $this->secretKey = $settings['plugin.']['tx_dpf.']['settings.']['deliverInactiveSecretKey'];
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
    protected function getViewIcon(array $row, $pageUid, $apiPid, $insideText, $class)
    {

        $previewMets = BackendUtility::getViewDomain($pageUid)
            . '/index.php?id=' . $apiPid
            . '&tx_dpf[qid]=' . $row['uid']
            . '&tx_dpf[action]=' . $row['action'];

        if (array_key_exists('deliverInactive', $row)) {
            $previewMets .= '&tx_dpf[deliverInactive]=' . $row['deliverInactive'];
        }

        $additionalGetVars = '&tx_dlf[id]=' . urlencode($previewMets) . '&no_cache=1';
        $title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('manager.tooltip.preview', 'dpf', $arguments = null);
        $icon = '<a href="#" data-toggle="tooltip" class="' . $class . '" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($pageUid, $this->backPath, '', '', '', $additionalGetVars)) . '" title="' . $title . '">' .
                          $insideText . '</a>';

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
    public function render(array $arguments, $pageUid, $apiPid, $class)
    {

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

            // pass configured API secret key parameter to enable dissemination of inactive documents
            if (isset($this->secretKey)) {
                $row['deliverInactive'] = $this->secretKey;
            }

        }

        $insideText = $this->renderChildren();

        $content = $this->getViewIcon($row, $pageUid, $apiPid, $insideText, $class);

        return $content;

    }
}
