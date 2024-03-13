<?php
namespace EWW\Dpf\ViewHelpers\Uri;

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

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PreviewViewHelper extends AbstractViewHelper
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $uriBuilder;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
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

        if (isset($settings['plugin.']['tx_dpf.']['settings.']['api.']['deliverInactiveSecretKey'])) {
            $this->secretKey = $settings['plugin.']['tx_dpf.']['settings.']['api.']['deliverInactiveSecretKey'];
        }
    }

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('documentIdentifier', 'string', '', true);
        $this->registerArgument('pageUid', 'int', '', true);
        $this->registerArgument('apiPid', 'int', '', true);
    }

    /**
     * Renders a record list as known from the TYPO3 list module
     * Note: This feature is experimental!
     *
     * @return string the rendered record list
     */
    public function render()
    {
        $documentIdentifier = $this->arguments['documentIdentifier'];
        $pageUid = $this->arguments['pageUid'];
        $apiPid = $this->arguments['apiPid'];
        $class = $this->arguments['class'];

        if ($documentIdentifier) {

            if (MathUtility::canBeInterpretedAsInteger($documentIdentifier)) {
                $document = $this->documentRepository->findByUid($documentIdentifier);
            } else {
                $document = $this->documentRepository->findByIdentifier($documentIdentifier);
            }

            $row['action'] = 'mets';

            if ($document) {
                $row['qid'] = $document->getUid();
            } else {
                $row['qid'] = $documentIdentifier;
            }

            // pass configured API secret key parameter to enable dissemination of inactive documents
            if (isset($this->secretKey)) {
                $row['deliverInactive'] = $this->secretKey;
            }

        }

        $previewMets = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($apiPid)
            ->setArguments(array( 'tx_dpf_getfile' => $row))
            ->setCreateAbsoluteUri(true)
            ->setUseCacheHash(FALSE)
            //->setNoCache(TRUE)
            ->buildFrontendUri();

        $additionalGetVars = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pageUid)
            ->setUseCacheHash(TRUE)
            ->setNoCache(TRUE)
            ->setArguments(
                array( 'tx_dlf' => array(
                    'id' => urldecode($previewMets),
                )
                )
            )
            ->setCreateAbsoluteUri(true)
            ->buildFrontendUri();

        return $additionalGetVars;
    }
}
