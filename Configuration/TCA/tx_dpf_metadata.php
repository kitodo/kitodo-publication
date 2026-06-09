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
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata',
        'label'                    => 'label',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'sortby'                   => 'sorting',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled' => 'hidden',
        ),
        'searchFields'             => 'index_name, label, xpath',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden,
        index_name, label, wrap, is_listed, is_sortable, format, format_type, xpath, xpath_sorting, default_value',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden,
        index_name, label, wrap, is_listed, is_sortable, format, format_type, xpath, xpath_sorting, default_value'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config'  => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'special'             => 'languages',
                'items'               => array(
                    array(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple',
                    ),
                ),
                'default'             => 0,
            ),
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_metadata',
                'foreign_table_where' => 'AND tx_dpf_metadata.pid=###CURRENT_PID### AND tx_dpf_metadata.sys_language_uid IN (-1,0)',
                'default'             => 0,
            ),
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type'    => 'passthrough',
                'default' => '',
            ),
        ),
        'hidden' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config'  => array(
                'type' => 'check',
            ),
        ),
        'index_name' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.index_name',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
                'eval' => 'required,trim',
            ),
        ),
        'label' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.label',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
                'eval' => 'trim',
            ),
        ),
        'wrap' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.wrap',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
            ),
        ),
        'is_listed' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.is_listed',
            'config'  => array(
                'type' => 'check',
            ),
        ),
        'is_sortable' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.is_sortable',
            'config'  => array(
                'type' => 'check',
            ),
        ),
        'format' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.format',
            'config'  => array(
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
            ),
        ),
        'format_type' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.format_type',
            'config'  => array(
                'type'       => 'select',
                'renderType' => 'selectSingle',
                'items'      => array(
                    array('', ''),
                    array('MODS', 'MODS'),
                    array('SLUB', 'SLUB'),
                ),
                'default'    => '',
            ),
        ),
        'xpath' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.xpath',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'max'  => 1024,
                'eval' => 'trim',
            ),
        ),
        'xpath_sorting' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.xpath_sorting',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'max'  => 1024,
                'eval' => 'trim',
            ),
        ),
        'default_value' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata.default_value',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
                'eval' => 'trim',
            ),
        ),
    ),
);
