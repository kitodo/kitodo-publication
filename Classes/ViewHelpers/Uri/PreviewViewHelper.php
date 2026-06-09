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

use EWW\Dpf\Security\PreviewToken;
use GuzzleHttp\Client as GuzzleClient;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PreviewViewHelper extends AbstractViewHelper
{
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
     * escapeOutput, activates / deactivates HTML escaping.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('documentIdentifier', 'string', '', true);
        $this->registerArgument('pageUid', 'int', '', true);
        $this->registerArgument('apiPid', 'int', 'Kept for template compatibility, unused', false);
        $this->registerArgument('action', 'string', 'GetFile action (mets or preview)', false, 'mets');
        $this->registerArgument('deliverInactive', 'string', 'Secret key for preview access', false, '');
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
        $action = $this->arguments['action'];
        $deliverInactive = $this->arguments['deliverInactive'];

        if (empty($documentIdentifier)) {
            return '';
        }

        if (MathUtility::canBeInterpretedAsInteger($documentIdentifier)) {
            $document = $this->documentRepository->findByUid($documentIdentifier);
            $qid = $document ? $document->getProcessNumber() : $documentIdentifier;
        } else {
            $qid = $this->resolveToObjectIdentifier($documentIdentifier);
        }

        $args = ['qid' => $qid];
        if ($action !== 'mets') {
            $args['action'] = $action;
        }
        if (!empty($deliverInactive)) {
            $args['deliverInactive'] = PreviewToken::generate($qid, $deliverInactive);
        }

        return $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pageUid)
            ->setArguments(['tx_dpf' => $args])
            ->setCreateAbsoluteUri(true)
            ->setUseCacheHash(true)
            ->buildFrontendUri();
    }

    private function resolveToObjectIdentifier(string $identifier): string
    {
        try {
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dpf');
            $host = trim($extConf['elasticSearchHost'] ?? 'localhost');
            $port = trim($extConf['elasticSearchPort'] ?? '9200');
            if (empty($host)) {
                return $identifier;
            }
            $url = "http://{$host}:{$port}/_all/_search";
            $response = (new GuzzleClient(['timeout' => 2]))
                ->post($url, [
                    'json' => [
                        'query' => ['term' => ['process_number' => $identifier]],
                        '_source' => ['objectIdentifier'],
                        'size' => 1,
                    ],
                ]);
            $data = json_decode($response->getBody()->getContents(), true);
            $objectIdentifier = $data['hits']['hits'][0]['_source']['objectIdentifier'] ?? null;
            if (!empty($objectIdentifier)) {
                return $objectIdentifier;
            }
        } catch (\Exception $e) {
            // fall through to original identifier
        }
        return $identifier;
    }
}
