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

$GLOBALS['TCA']['tx_dpf_domain_model_metadatapage'] = array(
    'ctrl'      => $GLOBALS['TCA']['tx_dpf_domain_model_metadatapage']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, name, display_name, page_number, backend_only, metadata_group',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, name, display_name, page_number, backend_only, metadata_group, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(

        'sys_language_uid' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config'  => array(
                //'readOnly' => 1,
                'type'                => 'select',
                'foreign_table'       => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items'               => array(
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0),
                ),
            ),
        ),
        'l10n_parent'      => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                //'readOnly' => 1,
                'type'                => 'select',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_metadatapage',
                'foreign_table_where' => 'AND tx_dpf_domain_model_metadatapage.pid=###CURRENT_PID### AND tx_dpf_domain_model_metadatapage.sys_language_uid IN (-1,0)',
            ),
        ),
        'l10n_diffsource'  => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),

        't3ver_label'      => array(
            'label'  => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
            ),
        ),

        'hidden'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config'    => array(
                'type' => 'check',
            ),
        ),
        'starttime'        => array(
            'exclude'   => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config'    => array(
                'type'     => 'input',
                'size'     => 13,
                'max'      => 20,
                'eval'     => 'datetime',
                'checkbox' => 0,
                'default'  => 0,
                'range'    => array(
                    'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
                ),
            ),
        ),
        'endtime'          => array(
            'exclude'   => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config'    => array(
                'type'     => 'input',
                'size'     => 13,
                'max'      => 20,
                'eval'     => 'datetime',
                'checkbox' => 0,
                'default'  => 0,
                'range'    => array(
                    'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
                ),
            ),
        ),

        'name'             => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage.name',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,uniqueInPid',
            ),
        ),
        'display_name'     => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage.display_name',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'page_number'      => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage.page_number',
            'config'    => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
            ),
        ),
        'backend_only'     => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage.backend_only',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'metadata_group'   => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage.metadata_group',
            'config'    => array(
                'type'                => 'select',
                'foreign_table'       => 'tx_dpf_domain_model_metadatagroup',
                'foreign_table_where' => ' AND (tx_dpf_domain_model_metadatagroup.pid=###CURRENT_PID###) AND (tx_dpf_domain_model_metadatagroup.sys_language_uid = 0) ORDER BY tx_dpf_domain_model_metadatagroup.name ASC',
                'MM'                  => 'tx_dpf_metadatapage_metadatagroup_mm',
                'size'                => 10,
                'autoSizeMax'         => 30,
                'maxitems'            => 9999,
                'multiple'            => 0,
                'wizards'             => array(
                    '_PADDING'  => 1,
                    '_VERTICAL' => 1,
                    'edit'      => array(
                        'type'                     => 'popup',
                        'title'                    => 'Edit',
                        'script'                   => 'wizard_edit.php',
                        'icon'                     => 'edit2.gif',
                        'popup_onlyOpenIfSelected' => 1,
                        'JSopenParams'             => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ),
                    'add'       => array(
                        'type'   => 'script',
                        'title'  => 'Create new',
                        'icon'   => 'add.gif',
                        'params' => array(
                            'table'    => 'tx_dpf_domain_model_metadatagroup',
                            'pid'      => '###CURRENT_PID###',
                            'setValue' => 'prepend',
                        ),
                        'script' => 'wizard_add.php',
                    ),
                ),
            ),
        ),

        'documenttype'     => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
    ),
);
