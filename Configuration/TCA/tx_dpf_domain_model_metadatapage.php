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

return array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage',
        'label'                    => 'name',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'versioningWS'             => true,
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'name, display_name, page_number, access_restriction_roles, metadata_group',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, name, display_name, page_number, access_restriction_roles, metadata_group',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, --palette--;;1, name, display_name, page_number, access_restriction_roles, metadata_group, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config'  => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'foreign_table'       => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items'               => array(
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0),
                ),
                'default' => 0,
            ),
        ),
        'l10n_parent'      => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
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
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config'    => array(
                'behaviour' => array(
                    'allowLanguageSynchronization' => true
                ),
                'type'     => 'input',
                'renderType' => 'inputDateTime',
                'size'     => 13,
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
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config'    => array(
                'behaviour' => array(
                    'allowLanguageSynchronization' => true
                ),
                'type'     => 'input',
                'renderType' => 'inputDateTime',
                'size'     => 13,
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
        'access_restriction_roles' => array(
            'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage.access_restriction_roles',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 2,
                'maxitems' => 2,
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role_researcher', EWW\Dpf\Security\Security::ROLE_RESEARCHER),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_feusergroup.kitodo_role_librarian', EWW\Dpf\Security\Security::ROLE_LIBRARIAN),
                ),
            ),
        ),
        'metadata_group'   => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage.metadata_group',
            'config'    => array(
                'type'                => 'select',
                'renderType'          => 'selectMultipleSideBySide',
                'foreign_table'       => 'tx_dpf_domain_model_metadatagroup',
                'foreign_table_where' => ' AND (tx_dpf_domain_model_metadatagroup.pid=###CURRENT_PID###) AND (tx_dpf_domain_model_metadatagroup.sys_language_uid = 0) ORDER BY tx_dpf_domain_model_metadatagroup.name ASC',
                'MM'                  => 'tx_dpf_metadatapage_metadatagroup_mm',
                'size'                => 11,
                'autoSizeMax'         => 30,
                'maxitems'            => 9999,
                'multiple'            => 0,
                'wizards'             => array(
                    '_VERTICAL' => 1,
                ),
                'fieldControl' => array(
                    'editPopup' => array(
                        'disabled' => false,
                    ),
                    'addRecord' => array(
                        'disabled' => false,
                        'options' =>  array(
                            'setValue' => 'prepend',
                        ),
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
