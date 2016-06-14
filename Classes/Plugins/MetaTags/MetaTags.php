<?php
namespace EWW\Dpf\Plugins\MetaTags;

/**
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

/**
 * Plugin 'DPF: MetaTags' for the 'dlf / dpf' extension.
 *
 * @author    Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package    TYPO3
 * @subpackage    tx_dpf
 * @access    public
 */
class MetaTags extends \tx_dlf_plugin
{

    /**
     * @type \TYPO3\CMS\Core\Page\PageRenderer
     * @inject
     */
    protected $pageRenderer;

    /**
     * The main method of the PlugIn
     *
     * @access    public
     *
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     *
     * @return    string        The content that is displayed on the website
     */
    public function main($content, $conf)
    {

        $this->init($conf);

        // get the tx_dpf.settings too
        // Flexform wins over TS
        $dpfTSconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.'];

        if (is_array($dpfTSconfig['settings.'])) {

            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($dpfTSconfig['settings.'], $this->conf, true, false);
            $this->conf = $dpfTSconfig['settings.'];

        }

        // Turn cache on.
        $this->setCache(true);

        // Load current document.
        $this->loadDocument();

        if ($this->doc === null) {

            // Quit without doing anything if required variables are not set.
            return $content;

        } else {

            // Set default values if not set.
            if (!isset($this->conf['rootline'])) {

                $this->conf['rootline'] = 0;

            }

        }

        $metadata = array();

        $metadata = $this->doc->getTitleData($this->conf['pages']);

        $metadata['_id'] = $this->doc->toplevelId;

        if (empty($metadata)) {

            if (TYPO3_DLOG) {

                GeneralUtility::devLog('[tx_dpf_metatags->main(' . $content . ', [data])] No metadata found for document with UID "' . $this->doc->uid . '"', 'tx_dpf', SYSLOG_SEVERITY_WARNING, $conf);

            }

            return;

        }

        ksort($metadata);

        $this->printMetaTags($metadata);

        return;

    }

    /**
     * Prepares the metadata array for output
     *
     * @access    protected
     *
     * @param    array        $metadata: The metadata array
     *
     * @return    string        The metadata array ready for output
     */
    protected function printMetaTags(array $metadata)
    {

        $output = '';

        // Load all the metadata values into the content object's data array.
        foreach ($metadata as $index_name => $values) {

            switch ($index_name) {

                case 'author':

                    if (is_array($values)) {

                        foreach ($values as $id => $value) {

                            $outArray['citation_author'][] = $value;

                        }

                    }

                    break;

                case 'title':

                    if (is_array($values)) {

                        $outArray['citation_title'][] = $values[0];

                    }

                    break;

                case 'dateissued':

                    if (is_array($values)) {

                        // Provide full dates in the "2010/5/12" format if available; or a year alone otherwise.
                        $outArray['citation_publication_date'][] = date('Y/m/d', strtotime($values[0]));

                    }

                    break;

                case 'record_id':

                    // Build typolink configuration array.
                    $conf = array(
                        'useCacheHash'     => 0,
                        'parameter'        => $this->conf['apiPid'],
                        'additionalParams' => '&tx_dpf[qid]=' . $values[0] . '&tx_dpf[action]=attachment&tx_dpf[attachment]=ATT-0',
                        'forceAbsoluteUrl' => true,
                    );

                    // we need to make instance of cObj here because its not available in this context
                    /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
                    $cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

                    // replace uid with URI to dpf API
                    $outArray['citation_pdf_url'][] = $cObj->typoLink_URL($conf);

                    break;

                default:

                    break;

            }

        }

        foreach ($outArray as $tagName => $values) {

            foreach ($values as $value) {

                $GLOBALS['TSFE']->getPageRenderer()->addMetaTag('<meta name="' . $tagName . '" content="' . $value . '">');

            }

        }

        return $output;

    }

}
