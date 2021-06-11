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
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role',
        'config' => array (
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array (
                array('', ''),
                array(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role_researcher',
                    \EWW\Dpf\Security\Security::ROLE_RESEARCHER
                ),
                array(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role_librarian',
                    \EWW\Dpf\Security\Security::ROLE_LIBRARIAN
                ),
            ),
            'size' => 1,
            'maxitems' => 1,
        )
    ),
    'access_to_groups' => [
        'exclude' => 0,
        'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.access_to_groups',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => 'tx_dpf_domain_model_metadatagroup',
            'foreign_table_where' => 'AND tx_dpf_domain_model_metadatagroup.pid=###CURRENT_PID### AND tx_dpf_domain_model_metadatagroup.sys_language_uid IN (-1,0) ORDER BY uid',
        ]
    ]
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_groups',
    $temporaryColumns
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    'kitodo_role,access_to_groups',
    '',
    'after:title'
);
