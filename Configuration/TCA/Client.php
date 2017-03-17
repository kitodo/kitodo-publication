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
$GLOBALS['TCA']['tx_dpf_domain_model_client']['ctrl']['requestUpdate'] = 'replace_niss_part';
$GLOBALS['TCA']['tx_dpf_domain_model_client']                          = array(
    'ctrl'      => $GLOBALS['TCA']['tx_dpf_domain_model_client']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, client, owner_id, 
        network_initial, library_identifier, admin_email, project, replace_niss_part, niss_part_search, niss_part_replace, 
        sword_host, sword_user, sword_password, sword_collection_namespace, fedora_host, fedora_user, fedora_password, 
        elastic_search_host, elastic_search_port, upload_directory, upload_domain, 
        admin_new_document_notification_subject, admin_new_document_notification_body, 
        submitter_new_document_notification_subject, submitter_new_document_notification_body, 
        submitter_ingest_notification_subject, submitter_ingest_notification_body'
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, 
        client, owner_id, network_initial, library_identifier, admin_email, project, replace_niss_part, niss_part_search, niss_part_replace, 
        --div--;SWORD, sword_host, sword_user, sword_password, sword_collection_namespace, 
        --div--;Fedora, fedora_host, fedora_user, fedora_password, 
        --div--;Elastic search, elastic_search_host, elastic_search_port, 
        --div--;Upload, upload_directory, upload_domain,
        --div--;Admin Notification, admin_new_document_notification_subject, admin_new_document_notification_body,
        --div--;Submitter Notification, submitter_new_document_notification_subject, submitter_new_document_notification_body, submitter_ingest_notification_subject, submitter_ingest_notification_body, 
        --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(

        'sys_language_uid'   => array(
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
        'l10n_parent'        => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_client',
                'foreign_table_where' => 'AND tx_dpf_domain_model_client.pid=###CURRENT_PID### AND tx_dpf_domain_model_client.sys_language_uid IN (-1,0)',
            ),
        ),
        'l10n_diffsource'    => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),

        't3ver_label'        => array(
            'label'  => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
            ),
        ),

        'hidden'             => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config'  => array(
                'type' => 'check',
            ),
        ),
        'starttime'          => array(
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
        'endtime'            => array(
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

        'project'            => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.project',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'client'             => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.client',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'network_initial'    => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.network_initial',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'library_identifier' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.library_identifier',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),

        'owner_id'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.owner_id',
            'config'    => array(
                'type' => 'input',
                'size' => 4,
                'max'  => 4,
                'eval' => 'trim,required',
            ),
        ),
        'admin_email'        => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_email',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'replace_niss_part'  => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.replace_niss_part',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'niss_part_search'   => array(
            'exclude'     => 1,
            'label'       => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.niss_part_search',
            'displayCond' => 'FIELD:replace_niss_part:=:1',
            'config'      => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ),
        ),
        'niss_part_replace'  => array(
            'exclude'     => 1,
            'label'       => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.niss_part_replace',
            'displayCond' => 'FIELD:replace_niss_part:=:1',
            'config'      => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ),
        ),        
        'sword_host' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.sword_host',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'sword_user' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.sword_user',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'sword_password' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.sword_password',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'sword_collection_namespace' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.sword_collection_namespace',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'fedora_host' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fedora_host',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'fedora_user' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fedora_user',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'fedora_password' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fedora_password',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'elastic_search_host' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.elastic_search_host',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'elastic_search_port' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.elastic_search_port',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'upload_directory' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.upload_directory',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'upload_domain' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.upload_domain',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'admin_new_document_notification_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_new_document_notification_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'admin_new_document_notification_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_new_document_notification_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
            'defaultExtras' => 'richtext[]'
        ),
        'submitter_new_document_notification_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.submitter_new_document_notification_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'submitter_new_document_notification_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.submitter_new_document_notification_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
            'defaultExtras' => 'richtext[]'
        ),
        'submitter_ingest_notification_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.submitter_ingest_notification_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'submitter_ingest_notification_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.submitter_ingest_notification_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
            'defaultExtras' => 'richtext[]'
        ),
    ),
);
