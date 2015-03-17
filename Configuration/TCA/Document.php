<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TCA']['tx_dpf_domain_model_document'] = array(
	'ctrl' => $GLOBALS['TCA']['tx_dpf_domain_model_document']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, xml_data, document_type, file',
	),
	'types' => array(
		'1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, title, xml_data, document_type, file, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(
                                                     	
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				),
			),
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_dpf_domain_model_document',
				'foreign_table_where' => 'AND tx_dpf_domain_model_document.pid=###CURRENT_PID### AND tx_dpf_domain_model_document.sys_language_uid IN (-1,0)',
			),
		),
		'l10n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),

		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),
	
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
			),
		),
		'starttime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'endtime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
            
                'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.title',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			)
		),

		'xml_data' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.xml_data',
			'config' => array(
				'type' => 'text',
				'cols' => 40,
				'rows' => 15,
				'eval' => 'trim'
			)
		),
		'document_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.document_type',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_dpf_domain_model_documenttype',
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
            
                'crdate' => Array (
                        'exclude' => 0,
                        'label' => 'Creation date',
                        'config' => Array (
                                'type' => 'none',
                                'format' => 'datetime',
                                'eval' => 'datetime',
                        )
                ),
            
                'transfer_status' => Array (
                        'exclude' => 0,
                        'label' => 'Transfer Status',
                        'config' => Array (
                                'type' => 'input',
                                'size' => '30',
                                'eval' => 'trim',
                        )
                ),
		
                'object_identifier' => Array (
                        'exclude' => 0,
                        'label' => 'Object Identifier',
                        'config' => Array (
                                'type' => 'input',
                                'size' => '30',
                                'eval' => 'trim',
                        )
                ),
            
                'remote_action' => Array (
                        'exclude' => 0,
                        'label' => 'Remote Action',
                        'config' => Array (
                                'type' => 'input',
                                'size' => '30',
                                'eval' => 'trim',
                        )
                ),
            
                'file' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document.file',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_dpf_domain_model_file',
				'foreign_field' => 'document',
				'maxitems'      => 9999,
				'appearance' => array(
					'collapseAll' => 0,
					'levelLinksPosition' => 'top',
					'showSynchronizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showAllLocalizationLink' => 1
				),
			),

		),
	),
);
