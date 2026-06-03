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
 * Base class for DPF pi_base plugins on the landing page.
 *
 * Overrides DLF AbstractPlugin::loadDocument() to read tx_dpf[qid]
 * instead of tx_dlf[id], routing through GetFileController as an
 * authenticated METS proxy.
 */
abstract class AbstractPlugin extends \Kitodo\Dlf\Common\AbstractPlugin
{
    use DpfDocumentLoader;
}
