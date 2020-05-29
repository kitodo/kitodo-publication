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

$temporaryColumns = [

/*    'stored_searches' => [
        'exclude' => true,
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_dpf_domain_model_storedsearch',
            'foreign_field' => 'fe_user'
        ],

    ],
*/
    'stored_searches' => [
        'exclude' => true,
        'label' => 'stored_searches',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_dpf_domain_model_storedsearch',
            'foreign_field' => 'fe_user',
            'maxitems' => 9999,
            'appearance' => [
                'collapseAll' => 0,
                'levelLinksPosition' => 'top',
                'showSynchronizationLink' => 1,
                'showPossibleLocalizationRecords' => 1,
                'showAllLocalizationLink' => 1
            ],
        ],

    ],
];


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_users',
    $temporaryColumns
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'stored_searches',
    '',
    ''
);
