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
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype',
        'label'                    => 'display_name',
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
        'searchFields'             => 'name, display_name, virtual_type, transformation_file_output, transformation_file_input, crossref_transformation, crossref_types,
            datacite_transformation, datacite_types, k10plus_transformation,
            pubmed_transformation, pubmed_types, bibtex_transformation, bibtex_types, riswos_transformation, riswos_types, metadata_page',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden,
            name, display_name, virtual_type, transformation_file_output, transformation_file_input, crossref_transformation, crossref_types,
            datacite_transformation, datacite_types, k10plus_transformation,
            pubmed_transformation, pubmed_types, bibtex_transformation, bibtex_types, riswos_transformation, riswos_types, metadata_page',
    ),
    'types'     => array(
        '1' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, --palette--;;1,
            name, display_name, virtual_type, transformation_file_output, transformation_file_input, crossref_transformation, crossref_types,
            datacite_transformation, datacite_types, k10plus_transformation,
            pubmed_transformation, pubmed_types, bibtex_transformation, bibtex_types, riswos_transformation, riswos_types, metadata_page,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(

        'sys_language_uid' => array(
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
        'l10n_parent'      => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config'      => array(
                'type'                => 'select',
                'renderType'          => 'selectSingle',
                'items'               => array(
                    array('', 0),
                ),
                'foreign_table'       => 'tx_dpf_domain_model_documenttype',
                'foreign_table_where' => 'AND tx_dpf_domain_model_documenttype.pid=###CURRENT_PID### AND tx_dpf_domain_model_documenttype.sys_language_uid IN (-1,0)',
            ),
        ),
        'l10n_diffsource'  => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),

        't3ver_label'      => array(
            'label'  => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max'  => 255,
            ),
        ),

        'hidden'           => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config'    => array(
                'type' => 'check',
            ),
        ),
        'starttime'        => array(
            'exclude'   => 1,
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
        'endtime'          => array(
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
        'name'             => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.name',
            'config'    => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,uniqueInPid',
            ),
        ),
        'display_name'     => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.display_name',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),
        'virtual_type'          => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.virtual_type',
            'config'    => array(
                'type'    => 'check',
                'default' => 0,
            ),
        ),
        'transformation_file_output' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.transformation_file_output',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'transformation_file_input' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.transformation_file_input',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'metadata_page'    => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.metadata_page',
            'config'    => array(
                'type'           => 'inline',
                'foreign_table'  => 'tx_dpf_domain_model_metadatapage',
                'foreign_field'  => 'documenttype',
                'foreign_label'  => 'display_name',
                'foreign_sortby' => 'page_number',
                'behaviour'      => array(
                    'disableMovingChildrenWithParent' => 1,
                ),
                'maxitems'       => 9999,
                'appearance'     => array(
                    'collapseAll'                     => 0,
                    'levelLinksPosition'              => 'top',
                    'showSynchronizationLink'         => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink'         => 1,
                ),
            ),
        ),
        'crossref_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.crossref_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'crossref_types'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.crossref_types',
            'config'    => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => \EWW\Dpf\Services\ImportExternalMetadata\CrossRefImporter::typeItems(
                    \EWW\Dpf\Services\ImportExternalMetadata\CrossRefImporter::types()
                ),
            ),
        ),
        'datacite_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.datacite_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'datacite_types'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.datacite_types',
            'config'    => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => array(
                ),
                'items' => \EWW\Dpf\Services\ImportExternalMetadata\DataCiteImporter::typeItems(
                    \EWW\Dpf\Services\ImportExternalMetadata\DataCiteImporter::types()
                ),
            ),
        ),
        'k10plus_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.k10plus_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'k10plus_types'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.k10plus_types',
            'config'    => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => array(
                ),
                'items' => \EWW\Dpf\Services\ImportExternalMetadata\K10plusImporter::typeItems(
                    \EWW\Dpf\Services\ImportExternalMetadata\K10plusImporter::types()
                ),
            ),
        ),
        'pubmed_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.pubmed_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'pubmed_types'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.pubmed_types',
            'config'    => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => array(
                ),
                'items' => \EWW\Dpf\Services\ImportExternalMetadata\PubMedImporter::typeItems(
                    \EWW\Dpf\Services\ImportExternalMetadata\PubMedImporter::types()
                ),
            ),
        ),
        'bibtex_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.bibtex_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'bibtex_types'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.bibtex_types',
            'config'    => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => array(
                ),
                'items' => \EWW\Dpf\Services\ImportExternalMetadata\BibTexFileImporter::typeItems(
                    \EWW\Dpf\Services\ImportExternalMetadata\BibTexFileImporter::types()
                ),
            ),
        ),
        'riswos_transformation' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.riswos_transformation',
            'config'    => [
                'items' => array(
                    array('LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.choose_transformation',0)
                ),
                'type'           => 'select',
                'renderType'     => 'selectSingle',
                'foreign_table'  => 'tx_dpf_domain_model_transformationfile',
                'maxitems'       => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'riswos_types'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype.ris_types',
            'config'    => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => array(
                ),
                'items' => \EWW\Dpf\Services\ImportExternalMetadata\RisWosFileImporter::typeItems(
                    \EWW\Dpf\Services\ImportExternalMetadata\RisWosFileImporter::types()
                ),
            ),
        ),
    ),
);
