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
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file',
        'label'                    => 'title',
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
        'searchFields'             => 'title, label, download, archive, file_group_deleted, content_type, link, status, datastream_identifier, primary_file, file_identifier, validation_results, document',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, label, download, archive, file_group_deleted, content_type, link, status, datastream_identifier, primary_file, file_identifier, validation_results, document',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, --palette--;;1, title, label, download, archive, file_group_deleted, content_type, link, status, datastream_identifier, primary_file, file_identifier, validation_results, document, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(

        'sys_language_uid'      => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config'  => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'foreign_table'       => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items'               => array(
                    array('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1),
                    array('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0),
                ),
                'default' => 0,
            ),
        ),
        'l10n_parent'           => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_file',
                'foreign_table_where' => 'AND tx_dpf_domain_model_file.pid=###CURRENT_PID### AND tx_dpf_domain_model_file.sys_language_uid IN (-1,0)',
                'default' => 0,
            ),
        ),
        'l10n_diffsource'       => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),

        't3ver_label'           => array(
            'label'  => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
            ),
        ),

        'hidden'                => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config'  => array(
                'type' => 'check',
            ),
        ),
        'starttime'             => array(
            'exclude'   => 1,
            'label'     => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
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
        'endtime'               => array(
            'exclude'   => 1,
            'label'     => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
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

        'title'                 => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.title',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),

        'label'                 => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.label',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'download'              => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.download',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'archive'               => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.archive',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'file_group_deleted'               => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.file_group_deleted',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'content_type'          => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.content_type',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'link'                  => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.link',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'status'                => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.status',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'datastream_identifier' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.datastream_identifier',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'primary_file'          => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.primary_file',
            'config'  => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'file_identifier'                  => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.file_identifier',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'validation_results'                  => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.validation_results',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'document'              => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file.document',
            'config'  => array(
                'type'          => 'select',
                'renderType'    => 'selectSingle',
                'foreign_table' => 'tx_dpf_domain_model_document',
                'minitems'      => 0,
                'maxitems'      => 1,
            ),
        ),
        'pid' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
    ),
);
