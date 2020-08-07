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

    'notify_on_changes' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role',
        'config' => array (
            'type' => 'check',
            'items' => array (
                ['notify on changes', ''],
            ),
        )
    ),

    'notify_personal_link' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role',
        'config' => array (
            'type' => 'check',
            'items' => array (
                ['notify personal', ''],
            ),
        )
    ),
    'notify_status_change' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role',
        'config' => array (
            'type' => 'check',
            'items' => array (
                ['notify status change', ''],
            ),
        )
    ),
    'notify_fulltext_published' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role',
        'config' => array (
            'type' => 'check',
            'items' => array (
                ['notify fulltext published', ''],
            ),
        )
    ),
    'notify_new_publication_mypublication' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role',
        'config' => array (
            'type' => 'check',
            'items' => array (
                ['notify new publication in mypublication', ''],
            ),
        )
    ),
    'fis_pers_id' => array(
        'exclude' => 0,
        'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.fis_pers_id',
        'config'  => array(
            'type' => 'input',
            'size' => '30',
            'eval' => 'trim',
        ),
    ),

);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_users',
    $temporaryColumns
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'stored_searches,notify_on_changes,notify_personal_link,notify_status_change,notify_fulltext_published,notify_new_publication_mypublication,fis_pers_id',
    '',
    'after:title'
);
