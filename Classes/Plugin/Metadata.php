<?php
namespace EWW\Dpf\Plugin;

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

use EWW\Dpf\Common\DpfDocumentLoader;

/**
 * Plugin 'DPF: Metadata' for the 'dpf' extension.
 *
 * Replaces the DLF Metadata plugin on the landing page. Uses the DPF
 * tx_dpf[qid] parameter namespace instead of tx_dlf[id], and loads the
 * METS document via GetFileController as an authenticated proxy.
 *
 * All metadata rendering logic is inherited from \Kitodo\Dlf\Plugin\Metadata.
 * Only loadDocument() is overridden via the DpfDocumentLoader trait.
 */
class Metadata extends \Kitodo\Dlf\Plugin\Metadata
{
    use DpfDocumentLoader;

    public $extKey = 'dpf';
    public $prefixId = 'tx_dpf';
    public $scriptRelPath = 'Classes/Plugin/Metadata.php';

    public function main($content, $conf)
    {
        // Merge plugin.tx_dpf.settings.* so apiPid, landingPage etc. are
        // available as $this->conf['apiPid'] — same pattern as MetaTags/Coins/etc.
        $dpfTSconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.'];
        if (is_array($dpfTSconfig['settings.'])) {
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($dpfTSconfig['settings.'], $conf, true, false);
            $conf = $dpfTSconfig['settings.'];
        }
        $result = parent::main($content, $conf);
        if (empty(trim((string) $result)) && !empty($this->piVars['qid'])) {
            return '<p class="dpf-document-unavailable">'
                . htmlspecialchars($this->pi_getLL('document_unavailable', 'The requested document could not be displayed.'))
                . '</p>';
        }
        return $result;
    }
}
