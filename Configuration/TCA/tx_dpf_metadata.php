<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

return [
    'ctrl' => [
        'title' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_metadata',
        'label' => 'index_name',
        'sortby' => 'sorting',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'searchFields' => 'index_name, label',
        'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_language.svg',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden, index_name, label, is_listed, is_sortable, xpath, xpath_sorting, default_value, wrap',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, index_name, label, is_listed, is_sortable, xpath, xpath_sorting, default_value, wrap'],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => ['type' => 'check'],
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => ['type' => 'language'],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [['', 0]],
                'foreign_table' => 'tx_dpf_metadata',
                'foreign_table_where' => 'AND {#tx_dpf_metadata}.{#pid}=###CURRENT_PID### AND {#tx_dpf_metadata}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'index_name' => [
            'label' => 'Index name',
            'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim,required'],
        ],
        'label' => [
            'label' => 'Label',
            'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim'],
        ],
        'is_listed' => [
            'label' => 'Show in list',
            'config' => ['type' => 'check', 'default' => 0],
        ],
        'is_sortable' => [
            'label' => 'Sortable',
            'config' => ['type' => 'check', 'default' => 0],
        ],
        'xpath' => [
            'label' => 'XPath',
            'config' => ['type' => 'input', 'size' => 80, 'eval' => 'trim', 'max' => 1024],
        ],
        'xpath_sorting' => [
            'label' => 'XPath (sorting)',
            'config' => ['type' => 'input', 'size' => 80, 'eval' => 'trim', 'max' => 1024],
        ],
        'default_value' => [
            'label' => 'Default value',
            'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim'],
        ],
        'wrap' => [
            'label' => 'Wrap (TypoScript)',
            'config' => ['type' => 'text', 'cols' => 30, 'rows' => 5],
        ],
    ],
];
