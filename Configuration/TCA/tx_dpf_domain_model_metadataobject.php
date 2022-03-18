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
        'searchFields'             => 'name, display_name, max_iteration, mandatory, validator, validation, validation_error_message, mapping, mods_extension, json_mapping, input_field, licence_options, deposit_license, max_input_length, input_option_list, fill_out_service, gnd_field_uid, default_value, access_restriction_roles, consent, embargo, fis_person_mapping, fis_organisation_mapping, gnd_person_mapping, gnd_organisation_mapping, ror_mapping, zdb_mapping, unpaywall_mapping, orcid_person_mapping, help_text, object_type',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, name, display_name, max_iteration, mandatory, validator, validation, validation_error_message, mapping, mods_extension, json_mapping, input_field, licence_options, deposit_license, max_input_length, input_option_list, fill_out_service, gnd_field_uid, default_value, access_restriction_roles, consent, embargo, fis_person_mapping, fis_organisation_mapping, gnd_person_mapping, gnd_organisation_mapping, ror_mapping, zdb_mapping, unpaywall_mapping, orcid_person_mapping, help_text, object_type',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, --palette--;;1, name, display_name, max_iteration, mandatory, validator, validation, validation_error_message, mapping, mods_extension, json_mapping, input_field, licence_options, deposit_license, max_input_length, input_option_list, fill_out_service, gnd_field_uid, default_value, access_restriction_roles, consent, embargo, fis_person_mapping, fis_organisation_mapping, gnd_person_mapping, gnd_organisation_mapping, ror_mapping, zdb_mapping, unpaywall_mapping, orcid_person_mapping, help_text, object_type, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'),
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
                'default' => 0,
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
                'default' => 0,
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
        'endtime'           => array(
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
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'maxitems' => 1,
                'items' => array(
                    array('',''),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.mandatory_yes', \EWW\Dpf\Domain\Model\MetadataMandatoryInterface::MANDATORY),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.mandatory_file_only', \EWW\Dpf\Domain\Model\MetadataMandatoryInterface::MANDATORY_FILE_ONLY),
                ),
            ),
        ),
        'mapping'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.mapping',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ),
        ),
        'json_mapping'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.json_mapping',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'validator'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.validator',
            'config'    => array(
                'type'     => 'select',
                'renderType' => 'selectSingle',
                'items'    => array(
                    array('', ''),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.validator.regexp', \EWW\Dpf\Domain\Model\MetadataObject::VALIDATOR_REGEXP),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.validator.date', \EWW\Dpf\Domain\Model\MetadataObject::VALIDATOR_DATE),
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
        'validation_error_message'        => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.validation_error_message',
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
        'access_restriction_roles' => array(
            'label' => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.access_restriction_roles',
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
            'onChange' => 'reload',
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_field',
            'config'    => array(
                'onChange' => 'reload',
                'type'     => 'select',
                'renderType' => 'selectSingle',
                'items'    => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.input', \EWW\Dpf\Domain\Model\MetadataObject::input),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.textarea', \EWW\Dpf\Domain\Model\MetadataObject::textarea),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.select', \EWW\Dpf\Domain\Model\MetadataObject::select),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.checkbox', \EWW\Dpf\Domain\Model\MetadataObject::checkbox),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.hidden', \EWW\Dpf\Domain\Model\MetadataObject::hidden),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.inputDropdown', \EWW\Dpf\Domain\Model\MetadataObject::INPUTDROPDOWN),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.markdownTextarea', \EWW\Dpf\Domain\Model\MetadataObject::textareaMarkdown),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.fileUpload', \EWW\Dpf\Domain\Model\MetadataObject::FILE_UPLOAD),
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.input_type.licenceConsent', \EWW\Dpf\Domain\Model\MetadataObject::LICENCE_CONSENT)
                ),
                'size'     => 1,
                'maxitems' => 1,
                'eval'     => '',
            ),
        ),
        'licence_options' => array(
            'displayCond' => 'FIELD:input_field:=:'.\EWW\Dpf\Domain\Model\MetadataObject::LICENCE_CONSENT,
            'exclude' => 1,
            'label'   => 'Lizenzen',
            'config'  => array(
                'type'                => 'select',
                'renderType'          => 'selectMultipleSideBySide',
                'items'               => array(
                ),
                'foreign_table'       => 'tx_dpf_domain_model_depositlicense',
                'foreign_table_where' => ' AND (tx_dpf_domain_model_depositlicense.pid=###CURRENT_PID###) AND (tx_dpf_domain_model_depositlicense.sys_language_uid = 0)',
                'minitems'            => 1,
                'maxitems'            => 99,
                'default'             => 0,
            ),
        ),
        'deposit_license'       => array(
            'displayCond' => 'FIELD:input_field:=:'.\EWW\Dpf\Domain\Model\MetadataObject::checkbox,
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'Checkbox Value',
            'config'    => array(
                'type'     => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dpf_domain_model_depositlicense',
                'items'    => array(
                    array('', 0),
                ),
                'size'     => 1,
                'maxitems' => 1,
                'eval'     => '',
                'default'  => 0,
            ),
        ),
        'max_input_length'       => array(
            'displayCond' => array(
                'OR' => array(
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
                'default' => 0,
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
                'default'             => 0,
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
                    array('AUTOCOMPLETE', \EWW\Dpf\Domain\Model\MetadataObject::FILL_OUT_AUTOCOMPLETE),
                ),
                'size'     => 1,
                'maxitems' => 1,
                'eval'     => '',
            ),
            'onChange' => 'reload',
        ),
        'gnd_field_uid' => array(
            'displayCond' => array(
                'OR' => array(
                    'FIELD:fill_out_service:=:'.\EWW\Dpf\Domain\Model\MetadataObject::FILL_OUT_SERVICE_GND,
                    'FIELD:fill_out_service:=:'.\EWW\Dpf\Domain\Model\MetadataObject::FILL_OUT_AUTOCOMPLETE
                )
            ),
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
        'embargo'    => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.embargo',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'fis_person_mapping' => [
            'label' => 'FIS User Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'fis_organisation_mapping' => [
            'label' => 'FIS Organisation Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'gnd_person_mapping' => [
            'label' => 'GND User Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'gnd_organisation_mapping' => [
            'label' => 'GND Organisation Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'ror_mapping' => [
            'label' => 'ROR Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'zdb_mapping' => [
            'label' => 'ZDB Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'unpaywall_mapping' => [
            'label' => 'Unpaywall Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'orcid_person_mapping' => [
            'label' => 'ORCID Mapping',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'object_type' => [
            'label' => 'Field type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['---', ''],
                    ['Surname', 'surname'],
                    ['FIS-Person-ID', 'fispersonid'],
                    ['UnpaywallDoi', 'unpaywallDoi'],
                    ['File label', 'fileLabel'],
                    ['File download', 'fileDownload'],
                    ['File archive', 'fileArchive'],
                ],
            ],
        ],
        'help_text'                => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject.help_text',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ),
        ),
    ),
);
