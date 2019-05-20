<?php
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

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

class ext_update {

    // Ideally the version corresponds with the extension version
    const VERSION = "v2.0.0";

    public function access() {
        $registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
        $version = $registry->get('tx_dpf','updatescript-'.self::VERSION);

        // If the version has already been registered in the table sys_register the updatscript will be blocked.
        if ($version) {
            return FALSE;
        }

        return TRUE;
    }

    public function main() {
        // This script registers itself into the sys_registry table to prevent a re-run with the same version number.
        $registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
        $registry->set('tx_dpf','updatescript-'.self::VERSION,TRUE);

        return "Das Updatescript wurde erfolgreich ausgeführt.";
    }


}