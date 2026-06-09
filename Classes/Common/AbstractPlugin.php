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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for DPF pi_base plugins on the landing page.
 *
 * dpf-native port of \Kitodo\Dlf\Common\AbstractPlugin (v3.3.4): provides
 * init()/setCache()/getTemplate()/parseTS() and the document loading via
 * the DpfDocumentLoader trait — without any Kitodo.Presentation dependency.
 *
 * Plugins read tx_dpf[qid] (prefixId tx_dpf), routed through
 * GetFileController as an authenticated METS proxy.
 */
abstract class AbstractPlugin extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    use DpfDocumentLoader;

    public $extKey = 'dpf';
    public $prefixId = 'tx_dpf';
    public $scriptRelPath = 'Classes/Common/AbstractPlugin.php';
    // Plugins are cached by default (@see setCache()).
    public $pi_USER_INT_obj = false;
    public $pi_checkCHash = true;

    /**
     * This holds the current document
     *
     * @var \EWW\Dpf\Common\MetsDocument|null
     */
    protected $doc;

    /**
     * This holds the plugin's parsed template
     *
     * @var string
     */
    protected $template = '';

    /**
     * The unqualified class name, lowercased — used for TS keys, template
     * file names and the CSS base class.
     *
     * @return string
     */
    protected function getUnqualifiedClassName(): string
    {
        $className = get_class($this);
        $position = strrpos($className, '\\');
        if ($position !== false) {
            $className = substr($className, $position + 1);
        }
        return $className;
    }

    /**
     * Read and parse the template file
     *
     * @param string $part Name of the subpart to load
     * @return void
     */
    protected function getTemplate($part = '###TEMPLATE###')
    {
        if (!empty($this->conf['templateFile'])) {
            $templateFile = $this->conf['templateFile'];
        } else {
            $templateFile = 'EXT:' . $this->extKey . '/Resources/Private/Templates/'
                . $this->getUnqualifiedClassName() . '.tmpl';
        }
        $fileResource = $GLOBALS['TSFE']->tmpl->getFileName($templateFile);
        $this->template = $this->templateService->getSubpart(file_get_contents($fileResource), $part);
    }

    /**
     * All the needed configuration values are stored in class variables.
     * Priority: Flexforms > TS-Templates > Extension Configuration
     *
     * @param array $conf Configuration array from TS-Template
     * @return void
     */
    protected function init(array $conf)
    {
        // Read FlexForm configuration.
        $flexFormConf = [];
        $this->cObj->readFlexformIntoConf($this->cObj->data['pi_flexform'], $flexFormConf);
        if (!empty($flexFormConf)) {
            ArrayUtility::mergeRecursiveWithOverrule($flexFormConf, $conf);
            $conf = $flexFormConf;
        }
        // Read plugin TS configuration.
        $pluginConf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '_' . strtolower($this->getUnqualifiedClassName()) . '.'] ?? null;
        if (is_array($pluginConf)) {
            ArrayUtility::mergeRecursiveWithOverrule($pluginConf, $conf);
            $conf = $pluginConf;
        }
        // Read general TS configuration.
        $generalConf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.'] ?? null;
        if (is_array($generalConf)) {
            ArrayUtility::mergeRecursiveWithOverrule($generalConf, $conf);
            $conf = $generalConf;
        }
        // Read extension configuration.
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey]) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey])) {
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);
            if (is_array($extConf)) {
                ArrayUtility::mergeRecursiveWithOverrule($extConf, $conf);
                $conf = $extConf;
            }
        }
        $this->conf = $conf;
        // Set default plugin variables.
        $this->pi_setPiVarDefaults();
        // Load translation files.
        $this->pi_loadLL('EXT:' . $this->extKey . '/Resources/Private/Language/locallang.xlf');
    }

    /**
     * The main method of the PlugIn
     *
     * @param string $content The PlugIn content
     * @param array $conf The PlugIn configuration
     * @return string The content that is displayed on the website
     */
    abstract public function main($content, $conf);

    /**
     * Parses a string into a TypoScript array
     *
     * @param string $string The string to parse
     * @return array The resulting TypoScript array
     */
    protected function parseTS($string = '')
    {
        $parser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $parser->parse($string);
        return $parser->setup;
    }

    /**
     * Wraps the input string in a <div> with the plugin's CSS base class.
     *
     * The legacy tx-dlf-* class is kept alongside tx-dpf-* because site CSS
     * still targets it.
     *
     * @param string $content HTML content to wrap
     * @return string
     */
    public function pi_wrapInBaseClass($content)
    {
        $className = strtolower($this->getUnqualifiedClassName());
        if (!$this->frontendController->config['config']['disableWrapInBaseClass']) {
            $content = '<div class="tx-dpf-' . $className . ' tx-dlf-' . $className . '">' . $content . '</div>';
            if (!$this->frontendController->config['config']['disablePrefixComment']) {
                $content = "\n\n<!-- BEGIN: Content of extension '" . $this->extKey . "', plugin '" . $this->getUnqualifiedClassName() . "' -->\n\n"
                    . $content
                    . "\n\n<!-- END: Content of extension '" . $this->extKey . "', plugin '" . $this->getUnqualifiedClassName() . "' -->\n\n";
            }
        }
        return $content;
    }

    /**
     * Sets some configuration variables if the plugin is cached.
     *
     * @param bool $cache Should the plugin be cached?
     * @return void
     */
    protected function setCache($cache = true)
    {
        if ($cache) {
            // Set cObject type to "USER" (default).
            $this->pi_USER_INT_obj = false;
            $this->pi_checkCHash = true;
            if (count($this->piVars)) {
                // Check cHash or disable caching.
                $GLOBALS['TSFE']->reqCHash();
            }
        } else {
            // Set cObject type to "USER_INT".
            $this->pi_USER_INT_obj = true;
            $this->pi_checkCHash = false;
            // Plugins are of type "USER" by default, so convert it to "USER_INT".
            $this->cObj->convertToUserIntObject();
        }
    }
}
