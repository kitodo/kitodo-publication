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
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject',
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
        'searchFields'             => 'name, display_name, max_iteration, mandatory, data_type, validation, mapping, mods_extension, input_field, max_input_length, input_option_list, fill_out_service, gnd_field_uid, default_value, backend_only, consent',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/tx_dpf_domain_model_metadataobject.gif',
        'requestUpdate' => 'fill_out_service',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, name, display_name, max_iteration, mandatory, data_type, validation, mapping, mods_extension, input_field, max_input_length, input_option_list, fill_out_service, gnd_field_uid, default_value, backend_only, consent',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, --palette--;;1, name, display_name, max_iteration, mandatory, data_type, validation, mapping, mods_extension, input_field, max_input_length, input_option_list, fill_out_service, gnd_field_uid, default_value, backend_only, consent, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(

        'sys_language_uid'  => array(
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
            ),
        ),
        'l10n_parent'       => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_metadataobject',
                'foreign_table_where' => 'AND tx_dpf_domain_model_metadataobject.pid=###CURRENT_PID### AND tx_dpf_domain_model_metadataobject.sys_language_uid IN (-1,0)',
            ),
        ),
        'l10n_diffsource'   => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),

        't3ver_label'       => array(
            'label'  => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
            ),
        ),

        'hidden'            => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config'    => array(
                'type' => 'check',
            ),
        ),
        'starttime'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config'    => array(
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
        'endtime'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config'    => array(
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

        'name'              => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.name',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'display_name'      => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.display_name',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'max_iteration'     => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.max_iteration',
            'config'    => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
            ),
        ),
        'mandatory'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.mandatory',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'mapping'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.mapping',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'data_type'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.data_type',
            'config'    => array(
                'type'     => 'select',
                'renderType' => 'selectSingle',
                'items'    => array(
                    array('', ''),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.data_type.regexp', \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_REGEXP),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.data_type.date', \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_DATE),
                ),
                'size'     => 1,
                'maxitems' => 1,
                'eval'     => '',
            ),
        ),
        'validation'        => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.validation',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'mods_extension'    => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.mods_extension',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'backend_only'      => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.backend_only',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'consent'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.consent',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'input_field'       => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_field',
            'config'    => array(
                'type'     => 'select',
                'renderType' => 'selectSingle',
                'items'    => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.input', \EWW\Dpf\Domain\Model\MetadataObject::input),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.textarea', \EWW\Dpf\Domain\Model\MetadataObject::textarea),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.select', \EWW\Dpf\Domain\Model\MetadataObject::select),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.checkbox', \EWW\Dpf\Domain\Model\MetadataObject::checkbox),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.hidden', \EWW\Dpf\Domain\Model\MetadataObject::hidden),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.inputDropdown', \EWW\Dpf\Domain\Model\MetadataObject::INPUTDROPDOWN),
                ),
                'size'     => 1,
                'maxitems' => 1,
                'eval'     => '',
            ),
        ),
        'max_input_length'       => array(
            'displayCond' => array(
                'OR' => array(
                    'FIELD:input_field:REQ:false',
                    'FIELD:input_field:=:'.\EWW\Dpf\Domain\Model\MetadataObject::input,
                    'FIELD:input_field:=:'.\EWW\Dpf\Domain\Model\MetadataObject::textarea,
                ),
            ),
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.max_input_length',
            'config'    => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'trim,number',
            ),
        ),
        'input_option_list' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_option_list',
            'config'  => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_inputoptionlist',
                'foreign_table_where' => ' AND (tx_dpf_domain_model_inputoptionlist.pid=###CURRENT_PID###) AND (tx_dpf_domain_model_inputoptionlist.sys_language_uid = 0)',
                'minitems'            => 0,
                'maxitems'            => 1,
            ),
        ),
        'default_value'     => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.default_value',
            'config'  => array(
                'type' => 'text',
                'cols' => 20,
                'rows' => 3,
                'eval' => 'trim',
            ),
        ),
        'fill_out_service'  => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.fill_out_service',
            'config'    => array(
                'type'     => 'select',
                'renderType' => 'selectSingle',
                'items'    => array(
                    array('', 0),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.fill_out_service.urn', \EWW\Dpf\Domain\Model\MetadataObject::FILL_OUT_SERVICE_URN),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.fill_out_service.gnd', \EWW\Dpf\Domain\Model\MetadataObject::FILL_OUT_SERVICE_GND),
                ),
                'size'     => 1,
                'maxitems' => 1,
                'eval'     => '',
            ),
        ),
        'gnd_field_uid' => array(
            'displayCond' => 'FIELD:fill_out_service:=:'.\EWW\Dpf\Domain\Model\MetadataObject::FILL_OUT_SERVICE_GND,
            'exclude'   => 0,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.gnd_field_uid',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'metadatagroup'     => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
    ),
);
