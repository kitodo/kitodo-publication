<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log',
        'label' => 'message',
        'searchFields' => 'request_id,component,message,data',
        'iconfile' => 'EXT:your_extension/Resources/Public/Icons/tx_dpf_log.svg',
        'hideTable' => true,
        'adminOnly' => true
    ],
    'interface' => [
        'showRecordFieldList' => 'request_id,time_micro,component,level,message,data,client_id'
    ],
    'columns' => [
        'request_id' => [
            'exclude' => true,
            'label' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log.request_id',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'eval' => 'trim'
            ],
        ],
        'time_micro' => [
            'exclude' => true,
            'label' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log.time_micro',
            'config' => [
                'type' => 'input',
                'size' => 16,
                'eval' => 'double4'
            ],
        ],
        'component' => [
            'exclude' => true,
            'label' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log.component',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'level' => [
            'exclude' => true,
            'label' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log.level',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Emergency', 0],
                    ['Alert', 1],
                    ['Critical', 2],
                    ['Error', 3],
                    ['Warning', 4],
                    ['Notice', 5],
                    ['Info', 6],
                    ['Debug', 7],
                ],
                'size' => 1,
                'maxitems' => 1
            ],
        ],
        'message' => [
            'exclude' => true,
            'label' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log.message',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'data' => [
            'exclude' => true,
            'label' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log.data',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'client_id' => [
            'exclude' => true,
            'label' => 'LLL:EXT:your_extension/Resources/Private/Language/locallang_db.xlf:tx_dpf_log.client_id',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'eval' => 'trim'
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'request_id, time_micro, component, level, message, data, client_id']
    ],
];
