<?php
namespace EWW\Dpf\Plugins\Coins;

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

/**
 * Plugin 'DPF: Coins' for the 'dlf / dpf' extension.
 *
 * @author    Erik Sommer <erik.sommer@slub-dresden.de>
 * @package    TYPO3
 * @subpackage    tx_dpf
 * @access    public
 */
class Coins extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugins/Coins.php';

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

        $this->setCache(true);

        $this->loadDocument();
        if ($this->doc === null) {
            return;
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
                GeneralUtility::devLog(
                    '[tx_dpf_metatags->main(' . $content . ', [data])] No metadata found for document with UID "' . $this->doc->uid . '"',
                    'tx_dpf',
                    SYSLOG_SEVERITY_WARNING,
                    $conf
                );
            }
            return;
        }

        return $this->generateCoins($metadata);
    }

    /**
     * Prepares the coins <span> for output
     *
     * @access    protected
     * @param    array        $metadata: The metadata array
     *
     * @return    string        The coins <span> ready for output
     */
     protected function generateCoins(array $metadata)
    {

        // The output follows the "Brief guide to Implementing OpenURL 1.0 Context Object for Journal Articles"
        // -> https://archive.is/a0Hgs

        // Formal specification of COinS
        $coins .= 'url_ver=Z39.88-2004';
        $coins .= '&ctx_ver=Z39.88-2004';

        // TODO: Get the document type to differentiate info:ofi/fmt:kev:mtx:[book/journal] and rft.genre=[…]
        $coins .= '&rft_val_fmt='. urlencode('info:ofi/fmt:kev:mtx:journal');
        $coins .= '&rft.genre=unknown';

        foreach ($metadata as $index_name => $values) {
            if (preg_match("/^author[[:digit:]]+/", $index_name)) {
                if (is_array($values)) {
                    foreach ($values as $id => $value) {
                        if ($value) {
                            $coins .= '&rft.au=' . urlencode($value);
                        }
                    }
                }
                continue;
            }

            if (preg_match("/^publisher[[:digit:]]+/", $index_name)) {
                if (is_array($values)) {
                    foreach ($values as $id => $value) {
                        if ($value) {
                            $coins .= '&rft.pub=' . urlencode($value);
                        }
                    }
                }
                continue;
            }

            switch ($index_name) {
                case 'record_id':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rfr_id=info:sid/qucosa.de:' . urlencode($values[0]);
                    }
                    break;

                case 'urn':
                case 'original_urn':
                case 'series_urn':
                case 'multivolume_urn':
                case 'doi':
                case 'original_doi':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft_id=' . urlencode($values[0]);
                    }
                    break;

                case 'isbn':
                case 'original_isbn':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.isbn=' . urlencode($values[0]);
                    }
                    break;

                case 'issn':
                case 'original_issn':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.issn=' . urlencode($values[0]);
                    }
                    break;

                case 'title':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.atitle=' . urlencode($values[0]);
                    }
                    break;

                case 'original_subtitle':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.stitle=' . urlencode($values[0]);
                    }
                    break;

                case 'original_title':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.jtitle=' . urlencode($values[0]);
                    }
                    break;

                case 'original_pages':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.spage=' . urlencode($values[0]);
                    }
                    break;

                case 'original_pages2':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.epage=' . urlencode($values[0]);
                    }
                    break;

                case 'issue':
                case 'original_issue':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.issue=' . urlencode($values[0]);
                    }
                    break;

                case 'volume':
                case 'original_volume':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.volume=' . urlencode($values[0]);
                    }
                    break;

                case 'original_corporation_publisher':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.pub=' . urlencode($values[0]);
                    }
                    break;

                case 'place':
                case 'original_place':
                    if (is_array($values) && $values[0]) {
                        if ($values[0]) {
                            $coins .= '&rft.place=' . urlencode($values[0]);
                        }
                    }
                    break;

                case 'dateissued':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.date=' . urlencode($this->safelyFormatDate("Y/m/d", $values[0]));
                    }
                    break;

                case 'language':
                    if (is_array($values) && $values[0]) {
                        $coins .= '&rft.language=' . urlencode($values[0]);
                    }
                    break;

                default:
                    break;
            }
        }

        return '<span class="Z3988" title="' . $coins . '"></span>';
    }

    /**
     * Format given date with given format, assuming the input date format is
     * parseable by strtotime(). If the input string has a length of 4 (like
     * "1989") the string is returned as is, without formatting.
     *
     * @param String $format Target string format
     * @param String $date   Date string to format
     *
     * @return Formatted date
     */
     protected function safelyFormatDate($format, $date)
    {
        return (strlen($date) == 4) ? $date : date($format, strtotime($date));
    }
}
