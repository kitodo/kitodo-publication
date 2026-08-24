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

        $this->addCanonicalLink($cObj, $pageRenderer, $doc->recordId);

        $parentItems = array_merge(
            $assembler->getParentItems($doc, $this->settings),
            $assembler->getSequenceItems($doc, $this->settings)
        );
        $hostUrl     = $assembler->getHostUrl($parentItems);
        $metadataResult = $assembler->getMetadataHtml($doc, $metadata, $this->settings, $hostUrl, $parentItems);

        $this->view->assignMultiple([
            'hasDocument'  => true,
            'qid'          => $qid,
            'metadataHtml' => $metadataResult['html'],
            'downloads'    => $assembler->getDownloads($doc, $this->settings),
            'parentItems'  => $assembler->filterEmbeddedRelations($parentItems, $metadataResult['embeddedRelations']),
            'relatedItems' => $assembler->getRelatedItems($doc, $this->settings),
            'embargoInfo'  => $assembler->getEmbargoInfo($qid),
            'coinsHtml'    => $assembler->getCoinsHtml($metadata),
        ]);
    }

    /**
     * A landing page is reachable under either the document's process
     * number or its Fedora objectIdentifier (findByIdentifier() in
     * GetFileController accepts both). Two URLs for the same content is bad
     * for SEO/citation ambiguity, so declare the objectIdentifier form
     * canonical - it's what the public ES index _id and Fedora's own
     * container key already use.
     *
     * @param string $recordId The METS OBJID, i.e. the true objectIdentifier,
     *   resolved regardless of which form the request's qid came in as.
     */
    private function addCanonicalLink(ContentObjectRenderer $cObj, PageRenderer $pageRenderer, string $recordId): void
    {
        if (empty($recordId)) {
            return;
        }
        $canonicalUrl = $cObj->typoLink_URL([
            'parameter'        => $GLOBALS['TSFE']->id,
            'additionalParams' => '&tx_dpf_landingpage[qid]=' . rawurlencode($recordId),
            'forceAbsoluteUrl' => true,
            'useCacheHash'     => 0,
        ]);
        // 'useCacheHash' => 0 above doesn't reliably suppress cHash for
        // every TypoScript/routing configuration - strip it explicitly so
        // the canonical URL stays stable across requests/sessions instead
        // of embedding a value tied to this one request.
        $canonicalUrl = preg_replace('/[?&]cHash=[^&]*/', '', $canonicalUrl);
        $pageRenderer->addHeaderData(
            '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl) . '">'
        );
    }
}
