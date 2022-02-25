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
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client',
        'label'                    => 'project',
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
        'searchFields'             => 'client, owner_id, network_initial, library_identifier, admin_email, project,
            replace_niss_part, niss_part_search, niss_part_replace,
            fedora_host, fedora_user, fedora_password, fedora_endpoint, fedora_root_container, fedora_collection_namespace,
            elastic_search_host, elastic_search_port, elastic_search_index_name, upload_directory, upload_domain,
            admin_new_document_notification_subject, admin_new_document_notification_body,
            submitter_new_document_notification_subject, submitter_new_document_notification_body,
            submitter_ingest_notification_subject, submitter_ingest_notification_body,
            admin_register_document_notification_subject, admin_register_document_notification_body,admin_new_suggestion_subject,
            admin_new_suggestion_body,
            admin_embargo_subject,admin_embargo_body,
            mypublications_update_notification_subject, mypublications_update_notification_body,
            mypublications_new_notification_subject, mypublications_new_notification_body,
            input_transformation, output_transformation, elastic_search_transformation,
            crossref_transformation, datacite_transformation, k10plus_transformation, pubmed_transformation, bibtex_transformation, riswos_transformation,
            admin_deposit_license_notification_subject, admin_deposit_license_notification_body, send_admin_deposit_license_notification,
            suggestion_flashmessage,
            fis_collections, active_messaging_suggestion_accept_url, active_messaging_suggestion_accept_url_body, active_messaging_suggestion_decline_url, active_messaging_suggestion_decline_url_body, active_messaging_new_document_url, active_messaging_new_document_url_body, active_messaging_changed_document_url, active_messaging_changed_document_url_body,
            fis_mapping,
            file_xpath, file_id_xpath, file_mimetype_xpath,
            file_href_xpath, file_download_xpath, file_archive_xpath, file_deleted_xpath ,file_title_xpath,
            date_xpath, publishing_year_xpath, urn_xpath, primary_urn_xpath, state_xpath, type_xpath, type_xpath_input, namespaces, title_xpath, process_number_xpath,
            submitter_name_xpath, submitter_email_xpath, submitter_notice_xpath,
            original_source_title_xpath, creator_xpath, creation_date_xpath, repository_creation_date_xpath,
            repository_last_mod_date_xpath, deposit_license_xpath, all_notes_xpath, private_notes_xpath,
            person_xpath, person_family_xpath, person_given_xpath, person_role_xpath, person_fis_identifier_xpath, person_affiliation_xpath, person_affiliation_identifier_xpath,
            person_author_role, person_publisher_role,
            validation_xpath, fis_id_xpath, source_details_xpaths, collection_xpath',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, client, owner_id,
        network_initial, library_identifier, admin_email, project, replace_niss_part, niss_part_search, niss_part_replace,
        fedora_host, fedora_user, fedora_password, fedora_endpoint, fedora_root_container,  fedora_collection_namespace,
        elastic_search_host, elastic_search_port, elastic_search_index_name, upload_directory, upload_domain,
        admin_new_document_notification_subject, admin_new_document_notification_body,
        submitter_new_document_notification_subject, submitter_new_document_notification_body,
        submitter_ingest_notification_subject, submitter_ingest_notification_body,
        admin_register_document_notification_subject, admin_register_document_notification_body,admin_new_suggestion_subject,
        admin_new_suggestion_body,
        admin_embargo_subject,admin_embargo_body,
        mypublications_update_notification_subject, mypublications_update_notification_body,
        mypublications_new_notification_subject, mypublications_new_notification_body,
        input_transformation, output_transformation, elastic_search_transformation,
        crossref_transformation, datacite_transformation, k10plus_transformation, pubmed_transformation, bibtex_transformation, riswos_transformation,
        admin_deposit_license_notification_subject, admin_deposit_license_notification_body, send_admin_deposit_license_notification,
        suggestion_flashmessage,
        fis_collections, active_messaging_suggestion_accept_url, active_messaging_suggestion_accept_url_body, active_messaging_suggestion_decline_url, active_messaging_suggestion_decline_url_body, active_messaging_new_document_url, active_messaging_new_document_url_body, active_messaging_changed_document_url, active_messaging_changed_document_url_body,
        fis_mapping,
        file_xpath, file_id_xpath, file_mimetype_xpath,
        file_href_xpath, file_download_xpath, file_archive_xpath, file_deleted_xpath ,file_title_xpath,
        date_xpath, publishing_year_xpath, urn_xpath, primary_urn_xpath, state_xpath, type_xpath, type_xpath_input, namespaces, title_xpath, process_number_xpath,
        submitter_name_xpath, submitter_email_xpath, submitter_notice_xpath,
        original_source_title_xpath, creator_xpath, creation_date_xpath, repository_creation_date_xpath,
        repository_last_mod_date_xpath, deposit_license_xpath, all_notes_xpath, private_notes_xpath,
        person_xpath, person_family_xpath, person_given_xpath, person_role_xpath, person_fis_identifier_xpath, person_affiliation_xpath, person_affiliation_identifier_xpath,
        person_author_role, person_publisher_role,
        validation_xpath, fis_id_xpath, source_details_xpaths, collection_xpath'
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, --palette--;;1,
        client, owner_id, network_initial, library_identifier, admin_email, project, replace_niss_part, niss_part_search, niss_part_replace,
        --div--;Static XML, namespaces, file_xpath,  file_id_xpath, file_mimetype_xpath,
        file_href_xpath, file_download_xpath, file_archive_xpath, file_deleted_xpath ,file_title_xpath,
        date_xpath, publishing_year_xpath, urn_xpath, primary_urn_xpath, state_xpath, type_xpath, type_xpath_input, title_xpath, process_number_xpath, submitter_name_xpath, submitter_email_xpath, submitter_notice_xpath,
        original_source_title_xpath, creator_xpath, creation_date_xpath, repository_creation_date_xpath,
        repository_last_mod_date_xpath, deposit_license_xpath, all_notes_xpath, private_notes_xpath,
        person_xpath, person_family_xpath, person_given_xpath, person_role_xpath, person_fis_identifier_xpath, person_affiliation_xpath, person_affiliation_identifier_xpath,
        person_author_role, person_publisher_role,
        validation_xpath, fis_id_xpath, source_details_xpaths, collection_xpath,
        --div--;Fedora, fedora_host, fedora_user, fedora_password, fedora_endpoint, fedora_root_container,  fedora_collection_namespace,
        --div--;Elastic search, elastic_search_host, elastic_search_port, elastic_search_index_name,
        --div--;Upload, upload_directory, upload_domain,
        --div--;Admin Notification, admin_new_document_notification_subject, admin_new_document_notification_body,admin_register_document_notification_subject, admin_register_document_notification_body,admin_new_suggestion_subject,admin_new_suggestion_body,admin_embargo_subject,admin_embargo_body,
        --div--;Submitter Notification, submitter_new_document_notification_subject, submitter_new_document_notification_body, submitter_ingest_notification_subject, submitter_ingest_notification_body,
        --div--;My Publications Notification, mypublications_update_notification_subject, mypublications_update_notification_body, mypublications_new_notification_subject, mypublications_new_notification_body,
        --div--;Deposit License Notification, send_admin_deposit_license_notification, admin_deposit_license_notification_subject, admin_deposit_license_notification_body,
        --div--;Messages, suggestion_flashmessage,
        --div--;Active Messaging, fis_collections, active_messaging_suggestion_accept_url, active_messaging_suggestion_accept_url_body, active_messaging_suggestion_decline_url, active_messaging_suggestion_decline_url_body, active_messaging_new_document_url, active_messaging_new_document_url_body, active_messaging_changed_document_url, active_messaging_changed_document_url_body,
        --div--;FIS, fis_mapping,
        --div--;Default Import-XSLT, crossref_transformation, datacite_transformation, k10plus_transformation, pubmed_transformation, bibtex_transformation, riswos_transformation,
        --div--;Default internal format XSLT, input_transformation, output_transformation,  elastic_search_transformation,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'),
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
        'l10n_parent'        => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_client',
                'foreign_table_where' => 'AND tx_dpf_domain_model_client.pid=###CURRENT_PID### AND tx_dpf_domain_model_client.sys_language_uid IN (-1,0)',
                'default' => 0,
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
        'endtime'            => array(
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
                'size' => 30,
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
            'onChange' => 'reload',
        ),
        'niss_part_search'   => array(
            'exclude'     => 1,
            'label'       => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.niss_part_search',
            'displayCond' => 'FIELD:replace_niss_part:=:1',
            'config'      => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
                'default' => '',
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
                'default' => '',
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
        'fedora_endpoint' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fedora_endpoint',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'fedora_root_container' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fedora_root_container',
            'config'       => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'fedora_collection_namespace' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fedora_collection_namespace',
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
        'elastic_search_index_name' => array(
            'exclude'      => 1,
            'l10n_mode'    => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label'        => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.elastic_search_index_name',
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
                'enableRichtext' => true,
            ),
        ),
        'admin_register_document_notification_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_register_document_notification_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'admin_register_document_notification_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_register_document_notification_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ),
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
                'enableRichtext' => true,
            ),
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
                'enableRichtext' => true,
            ),
        ),
        'admin_new_suggestion_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_new_suggestion_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'admin_new_suggestion_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_new_suggestion_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ),
        ),
        'admin_embargo_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_embargo_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'admin_embargo_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_embargo_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ),
        ),
        'suggestion_flashmessage' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.suggestion_flashmessage',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'file_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'file_id_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_id_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'file_mimetype_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_mimetype_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'file_href_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_href_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'file_download_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_download_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'file_archive_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_archive_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'file_deleted_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_deleted_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'file_title_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.file_title_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'state_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.state_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'type_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.type_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'type_xpath_input' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.type_xpath_input',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'date_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.date_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'publishing_year_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.publishing_year_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'urn_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.urn_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'primary_urn_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.primary_urn_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'namespaces' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.namespaces',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'title_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.title_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'process_number_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.process_number_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'submitter_name_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.submitter_name',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'submitter_email_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.submitter_email',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'submitter_notice_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.submitter_notice',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'original_source_title_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.original_source_title_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'creator_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.creator_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'creation_date_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.creation_date_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),

        'repository_creation_date_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.repository_creation_date_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'repository_last_mod_date_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.repository_last_mod_date_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'deposit_license_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.deposit_license_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'all_notes_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.all_notes_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'private_notes_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.private_notes_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_family_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_family_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_given_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_given_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_role_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_role_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_fis_identifier_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_fis_identifier_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_affiliation_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_affiliation_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_affiliation_identifier_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_affiliation_identifier_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'person_author_role' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_author_role',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'person_publisher_role' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.person_publisher_role',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'validation_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.validation_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'fis_id_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fis_id_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'source_details_xpaths' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.source_details_xpaths',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'collection_xpath' => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.collection_xpath',
            'config'    => array(
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ),
        ),
        'mypublications_update_notification_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.mypublications_update_notification_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'admin_deposit_license_notification_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_deposit_license_notification_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'mypublications_update_notification_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.mypublications_update_notification_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ),
        ),
        'admin_deposit_license_notification_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.admin_deposit_license_notification_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ),
        ),
        'mypublications_new_notification_subject' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.mypublications_new_notification_subject',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'mypublications_new_notification_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.mypublications_new_notification_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ),
        ),
        'output_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.output_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'input_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.input_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'elastic_search_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.elastic_search_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'crossref_transformation' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.crossref_transformation',
            'config'    => array(
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ),
        ),
        'datacite_transformation' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.datacite_transformation',
            'config'    => array(
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ),
        ),
        'k10plus_transformation' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.k10plus_transformation',
            'config'    => array(
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ),
        ),
        'pubmed_transformation' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.pubmed_transformation',
            'config'    => array(
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ),
        ),
        'bibtex_transformation' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.bibtex_transformation',
            'config'    => array(
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ),
        ),
        'riswos_transformation' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.riswos_transformation',
            'config'    => array(
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ),
        ),
        'send_admin_deposit_license_notification'  => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.send_admin_deposit_license_notification',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'active_messaging_suggestion_accept_url' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_suggestion_accept_url',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'active_messaging_suggestion_decline_url' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_suggestion_decline_url',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'active_messaging_new_document_url' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_new_document_url',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'active_messaging_changed_document_url' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_changed_document_url',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
        'active_messaging_suggestion_accept_url_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_suggestion_accept_url_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'active_messaging_suggestion_decline_url_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_suggestion_decline_url_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'active_messaging_new_document_url_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_new_document_url_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'active_messaging_changed_document_url_body' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.active_messaging_changed_document_url_body',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'fis_mapping' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fis_mapping',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'fis_collections' => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client.fis_collections',
            'config'  => array(
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ),
        ),
    ),
);
