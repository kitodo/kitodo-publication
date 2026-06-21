<?php
namespace EWW\Dpf\Common;

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

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for dpf-3x landing-page pi_base plugins.
 *
 * Replaces \Kitodo\Dlf\Common\AbstractPlugin. Provides init(), setCache(),
 * loadDocument() and the $templateService property that the four pi_base plugins
 * depend on, but without the Kitodo.Dlf composer dependency.
 *
 * $this->piVars, $this->conf, $this->cObj come from TYPO3's own AbstractPlugin.
 * $this->doc is a dpf-native MetsDocument fetched via Redis/Fedora (no HTTP).
 */
class AbstractPlugin extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    // Must match the piVar namespace used by the landing-page URLs (tx_dlf[id]).
    public $prefixId = 'tx_dlf';

    // Plugins are cached by default; setCache(false) converts to USER_INT.
    public $pi_USER_INT_obj = false;
    public $pi_checkCHash = true;

    /**
     * @var MetsDocument|null
     */
    public $doc = null;

    /**
     * Parsed template string (used by DownloadTool / RelatedListTool).
     *
     * @var string
     */
    protected $template = '';

    /**
     * @var MarkerBasedTemplateService|null
     */
    protected $templateService = null;

    /**
     * Initialise plugin: merge FlexForm + TS conf, set piVar defaults, init templateService.
     *
     * Mirrors Kitodo\Dlf\Common\AbstractPlugin::init() but scoped to tx_dpf.
     *
     * @param array $conf TypoScript configuration passed by TYPO3 dispatcher
     * @return void
     */
    protected function init(array $conf)
    {
        // FlexForm wins over TS.
        $flexFormConf = [];
        $this->cObj->readFlexformIntoConf($this->cObj->data['pi_flexform'], $flexFormConf);
        if (!empty($flexFormConf)) {
            ArrayUtility::mergeRecursiveWithOverrule($flexFormConf, $conf);
            $conf = $flexFormConf;
        }
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }

    /**
     * Control TYPO3 page caching for this plugin instance.
     *
     * Mirrors Kitodo\Dlf\Common\AbstractPlugin::setCache().
     *
     * @param bool $cache
     * @return void
     */
    protected function setCache($cache = true)
    {
        if ($cache) {
            $this->pi_USER_INT_obj = false;
            $this->pi_checkCHash = true;
            if (count($this->piVars)) {
                $GLOBALS['TSFE']->reqCHash();
            }
        } else {
            $this->pi_USER_INT_obj = true;
            $this->pi_checkCHash = false;
            $this->cObj->convertToUserIntObject();
        }
    }

    /**
     * Load the document from the METS URL in tx_dlf[id] / piVars['id'].
     *
     * Replaces DLF Document::getInstance() HTTP call with direct Redis/Fedora fetch.
     *
     * @return void
     */
    protected function loadDocument()
    {
        $location = isset($this->piVars['id']) ? (string) $this->piVars['id'] : '';
        if (empty($location)) {
            return;
        }
        $this->doc = MetsDocument::getInstance($location);
    }
}
