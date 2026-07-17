<?php
declare(strict_types=1);
namespace EWW\Dpf\Controller;

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

use EWW\Dpf\Common\MetsDocument;
use EWW\Dpf\Services\LandingPage\LandingPageAssembler;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Single-controller replacement for the five dpf PI plugins on the landing
 * page (dpf_metadata, dpf_downloadtool, dpf_relatedlisttool, dpf_coins,
 * dpf_metatags). Loads the document once and delegates rendering to
 * LandingPageAssembler; meta tags are injected via PageRenderer.
 *
 * URL parameter: tx_dpf_landingpage[qid] (replaces the old tx_dpf[qid]
 * used by the PI plugins). Update tx_find landingPageUID constant and the
 * RelatedListTool link target when cutting over from the PI plugins.
 */
class LandingPageController extends ActionController
{
    public function showAction(): void
    {
        $qid = $this->request->hasArgument('qid')
            ? trim((string)$this->request->getArgument('qid'))
            : '';

        if (empty($qid) || !preg_match('/^[A-Za-z0-9_:-]+$/', $qid)) {
            $this->view->assign('hasDocument', false);
            return;
        }

        $apiPid = (int)($this->settings['apiPid'] ?? 0);
        $cPid   = (int)($this->settings['pages'] ?? 0);

        $additionalParams = '&tx_dpf_getfile[qid]=' . rawurlencode($qid) . '&tx_dpf_getfile[action]=mets';

        if ($this->request->hasArgument('deliverInactive')) {
            $token = (string)$this->request->getArgument('deliverInactive');
            if (!empty($token)) {
                $additionalParams .= '&tx_dpf_getfile[deliverInactive]=' . rawurlencode($token);
            }
        }

        /** @var ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $metsUrl = $cObj->typoLink_URL([
            'parameter'        => $apiPid,
            'additionalParams' => $additionalParams,
            'forceAbsoluteUrl' => true,
            'useCacheHash'     => 0,
        ]);

        $doc = MetsDocument::getInstance($metsUrl);

        if ($doc === null || !$doc->ready) {
            $this->view->assign('hasDocument', false);
            return;
        }

        $doc->cPid = $cPid;
        $metadata  = $doc->getTitleData($cPid);

        /** @var LandingPageAssembler $assembler */
        $assembler = GeneralUtility::makeInstance(LandingPageAssembler::class);

        // Inject <meta> tags into <head>
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        foreach ($assembler->getMetaTags($doc, $metadata, $this->settings) as $tagName => $values) {
            foreach ($values as $value) {
                $pageRenderer->addMetaTag(
                    '<meta name="' . $tagName . '" content="' . htmlspecialchars((string)$value) . '">'
                );
            }
        }

        $this->view->assignMultiple([
            'hasDocument'  => true,
            'qid'          => $qid,
            'metadataHtml' => $assembler->getMetadataHtml($doc, $metadata, $this->settings),
            'downloads'    => $assembler->getDownloads($doc, $this->settings),
            'relatedItems' => $assembler->getRelatedItems($doc, $this->settings),
            'coinsHtml'    => $assembler->getCoinsHtml($metadata),
        ]);
    }
}
