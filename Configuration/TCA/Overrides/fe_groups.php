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

$temporaryColumns = array (
    'kitodo_role' => array (
        'exclude' => 0,
        'label' => 'examples_options',
        'config' => array (
        'type' => 'select',
        'items' => array (
            array('', ''),
            array('Forschender', \EWW\Dpf\Security\AuthorizationChecker::ROLE_RESEARCHER),
            array('Bibliothekar', \EWW\Dpf\Security\AuthorizationChecker::ROLE_LIBRARIAN),
        ),
            'size' => 1,
            'maxitems' => 1,
        )
    ),
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_groups',
    $temporaryColumns
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    'kitodo_role'
);