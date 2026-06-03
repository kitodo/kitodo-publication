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

use Kitodo\Dlf\Common\Document;

/**
 * Shared loadDocument() implementation for all DPF landing page plugins.
 *
 * Reads tx_dpf[qid] from piVars, constructs an authenticated GetFileController
 * METS URL, and populates $this->doc via DLF Document::getInstance().
 *
 * For BE-authenticated users, inactive documents are accessible via the
 * deliverInactive mechanism — the backoffice generates a short-lived HMAC token
 * from the master secret and embeds the token (not the secret) in the URL.
 */
trait DpfDocumentLoader
{
    protected function loadDocument()
    {
        $qid = $this->piVars['qid'] ?? null;
        if (empty($qid)) {
            return;
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $qid)) {
            return;
        }

        $action = $this->piVars['action'] ?? 'mets';
        if (!in_array($action, ['mets', 'preview'], true)) {
            $action = 'mets';
        }

        $additionalParams = '&tx_dpf_getfile[qid]=' . rawurlencode($qid)
                          . '&tx_dpf_getfile[action]=' . $action;

        $deliverInactive = $this->piVars['deliverInactive'] ?? '';
        if (!empty($deliverInactive)) {
            $additionalParams .= '&tx_dpf_getfile[deliverInactive]='
                . rawurlencode($deliverInactive);
        }

        $conf = [
            'parameter'        => $this->conf['apiPid'],
            'additionalParams' => $additionalParams,
            'forceAbsoluteUrl' => true,
            'useCacheHash'     => 0,
        ];
        $metsUrl = $this->cObj->typoLink_URL($conf);

        $pid = (!empty($this->conf['excludeOther']) && !empty($this->conf['pages']))
            ? intval($this->conf['pages'])
            : 0;

        $this->doc = Document::getInstance($metsUrl, $pid);

        if (!$this->doc->ready) {
            $this->doc = null;
        } else {
            $this->doc->cPid = $this->conf['pages'];
        }
    }
}
