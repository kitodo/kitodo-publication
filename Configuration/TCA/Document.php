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

$GLOBALS['TCA']['tx_dpf_domain_model_document'] = array(
    'ctrl'      => $GLOBALS['TCA']['tx_dpf_domain_model_document']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, authors, xml_data, slub_info_data, document_type, date_issued, metadata, file',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, title, authors, xml_data, slub_info_data, document_type, date_issued, metadata, file, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(

        'sys_language_uid'           => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config'  => array(
                'type'                => 'select',
                'foreign_table'       => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items'               => array(
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0),
                ),
            ),
        ),
        'l10n_parent'                => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_document',
                'foreign_table_where' => 'AND tx_dpf_domain_model_document.pid=###CURRENT_PID### AND tx_dpf_domain_model_document.sys_language_uid IN (-1,0)',
            ),
        ),
        'l10n_diffsource'            => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),

        't3ver_label'                => array(
            'label'  => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
            ),
        ),

        'hidden'                     => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config'  => array(
                'type' => 'check',
            ),
        ),
        'starttime'                  => array(
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
        'endtime'                    => array(
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

        'title'                      => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.title',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),

        'authors'                    => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.authors',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),

        'xml_data'                   => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.xml_data',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'slub_info_data'             => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.slub_info_data',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'document_type'              => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.document_type',
            'config'  => array(
                'type'                => 'select',
                'foreign_table'       => 'tx_dpf_domain_model_documenttype',
                'foreign_table_where' => '  AND tx_dpf_domain_model_documenttype.pid=###CURRENT_PID### AND tx_dpf_domain_model_documenttype.sys_language_uid = 0',
                'minitems'            => 0,
                'maxitems'            => 1,
            ),
        ),

        'crdate'                     => array(
            'exclude' => 0,
            'label'   => 'Creation date',
            'config'  => array(
                'type'   => 'none',
                'format' => 'datetime',
                'eval'   => 'datetime',
            ),
        ),

        'tstamp'                     => array(
            'exclude' => 0,
            'label'   => 'Timestamp',
            'config'  => array(
                'type'   => 'none',
                'format' => 'datetime',
                'eval'   => 'datetime',
            ),
        ),

        'transfer_status'            => array(
            'exclude' => 0,
            'label'   => 'Transfer Status',
            'config'  => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ),
        ),

        'object_identifier'          => array(
            'exclude' => 0,
            'label'   => 'Object Identifier',
            'config'  => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ),
        ),

        'reserved_object_identifier' => array(
            'exclude' => 0,
            'label'   => 'Reserved Object Identifier',
            'config'  => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ),
        ),

        'process_number' => array(
            'exclude' => 0,
            'label'   => 'Process Number',
            'config'  => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ),
        ),

        'state'                      => array(
            'exclude' => 0,
            'label'   => 'Local State',
            'config'  => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ),
        ),

        'changed'                    => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.changed',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),

        'valid'                      => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.valid',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),

        'date_issued'                => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.date_issued',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),

        'metadata'                   => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.metadata',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),

        'file'                       => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.file',
            'config'  => array(
                'type'          => 'inline',
                'foreign_table' => 'tx_dpf_domain_model_file',
                'foreign_field' => 'document',
                'behaviour'     => array(
                    'disableMovingChildrenWithParent' => 1,
                    //'disableCopyingChildrenWithParent' => 1
                ),
                'maxitems'      => 9999,
                'appearance'    => array(
                    'collapseAll'                     => 0,
                    'levelLinksPosition'              => 'top',
                    'showSynchronizationLink'         => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink'         => 1,
                ),
            ),

        ),
    ),
);
