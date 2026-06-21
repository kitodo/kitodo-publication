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
        // Base: DLF extension configuration (provides fileGrpDownload, fileGrpFulltext, etc.)
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf'])) {
            $dlfExtConf = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
            if (is_array($dlfExtConf)) {
                ArrayUtility::mergeRecursiveWithOverrule($dlfExtConf, $conf);
                $conf = $dlfExtConf;
            }
        }
        // FlexForm wins over everything.
        $flexFormConf = [];
        $this->cObj->readFlexformIntoConf($this->cObj->data['pi_flexform'], $flexFormConf);
        if (!empty($flexFormConf)) {
            // mergeRecursiveWithOverrule modifies first arg in-place; $flexFormConf overrides $conf
            ArrayUtility::mergeRecursiveWithOverrule($conf, $flexFormConf);
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
     * Parse a TypoScript string into a setup array.
     *
     * Mirrors Kitodo\Dlf\Common\AbstractPlugin::parseTS().
     *
     * @param string $string
     * @return array
     */
    protected function parseTS($string = '')
    {
        $parser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $parser->parse($string);
        return $parser->setup;
    }

    /**
     * Load and cache the plugin template from conf['templateFile'] or a
     * default path derived from the class name under EXT:dlf/... (DLF installed).
     *
     * Mirrors Kitodo\Dlf\Common\AbstractPlugin::getTemplate().
     *
     * @param string $part Subpart marker to extract
     * @return void
     */
    protected function getTemplate($part = '###TEMPLATE###')
    {
        if (!empty($this->conf['templateFile'])) {
            $templateFile = $this->conf['templateFile'];
        } else {
            $className = basename(str_replace('\\', '/', get_class($this)));
            $templateFile = 'EXT:dlf/Resources/Private/Templates/Plugin/' . $className . '.tmpl';
        }
        $fileResource = $GLOBALS['TSFE']->tmpl->getFileName($templateFile);
        if (!empty($fileResource) && file_exists($fileResource)) {
            $this->template = $this->templateService->getSubpart(
                file_get_contents($fileResource),
                $part
            );
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
