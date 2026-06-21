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

/**
 * Base class for dpf-3x landing-page pi_base plugins.
 *
 * Replaces \Kitodo\Dlf\Common\AbstractPlugin. Provides $doc and loadDocument()
 * using MetsDocument (direct Redis/Fedora fetch, no HTTP self-loop).
 *
 * $this->piVars, $this->conf, $this->cObj come from TYPO3's own AbstractPlugin.
 * $this->doc replaces the DLF-typed $doc with the dpf-native MetsDocument.
 */
class AbstractPlugin extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    /**
     * The current document or null if not loaded / not found.
     *
     * @var MetsDocument|null
     */
    public $doc = null;

    /**
     * Load the document from the METS URL in tx_dlf[id] / piVars['id'].
     *
     * Mirrors DLF AbstractPlugin::loadDocument(): reads the same piVar ('id'),
     * but delegates to MetsDocument::getInstance() instead of
     * Kitodo\Dlf\Common\Document::getInstance().
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
