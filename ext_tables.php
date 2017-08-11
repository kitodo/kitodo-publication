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

if (TYPO3_MODE === 'BE') {

    $modulName = 'qucosaMain';
    //Legt die Position des Moduls fest, hier nach Modul "web"
    if (!isset($TBE_MODULES[$modulName])) {
        $temp_TBE_MODULES = array();
        foreach ($TBE_MODULES as $key => $val) {
            if ($key == 'file') {
                $temp_TBE_MODULES[$key]       = $val;
                $temp_TBE_MODULES[$modulName] = '';
            } else {
                $temp_TBE_MODULES[$key] = $val;
            }
        }
        $TBE_MODULES = $temp_TBE_MODULES;
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'EWW.' . $_EXTKEY,
        'qucosaMain',
        '',
        '',
        array(),
        array(
            'access' => 'user,group',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_qucosa_mod_main.xlf',
        )
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'EWW.' . $_EXTKEY,
        'qucosaMain',
        'qucosamanager',
        '',
        array(
            'DocumentManager'         => 'list, show, new, create, edit, update, delete, discard, release, duplicate, '
            . 'deleteConfirm, releaseConfirm, activateConfirm, inactivateConfirm, deleteConfirm, discardConfirm, restoreConfirm, '
            . 'listNew, listEdit, activate, inactivate, restore',

            'DocumentFormBE'   => 'list, show, new, create, edit, update, delete, cancel',
            'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
            'Search'           => 'list, search, import, doubletCheck, nextResults, extendedSearch, latest',
        ),
        array(
            'access'                => 'user,group',
            'icon'                  => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
            'labels'                => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_qucosa_mod_manager.xlf',
            'navigationComponentId' => 'typo3-pagetree',
        )
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'EWW.' . $_EXTKEY,
        'qucosaMain',
        'admin',
        '',
        array(
            'Client' => 'new,create,default',
        ),
        array(
            'access'                => 'user,group',
            'icon'                  => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
            'labels'                => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_qucosa_mod_admin.xlf',
            'navigationComponentId' => 'typo3-pagetree',
        )
    );

}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'EWW.' . $_EXTKEY,
    'Qucosaform',
    'DPF: QucosaForm'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'EWW.' . $_EXTKEY,
    'Qucosaxml',
    'DPF: QucosaXml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'EWW.' . $_EXTKEY,
    'Frontendsearch',
    'DPF: FrontendSearch'
);

// frontendsearch plugin configuration: additional fields
$extensionName   = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY));
$pluginName      = strtolower('frontendsearch');
$pluginSignature = $extensionName . '_' . $pluginName;

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive,categories';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature]     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/frontendsearch_plugin.xml');
// end of frontendsearch plugin configuration

// qucosaform plugin configuration: additional fields
$extensionName   = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY));
$pluginName      = strtolower('Qucosaform');
$pluginSignature = $extensionName . '_' . $pluginName;

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive,categories';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature]     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/qucosaform_plugin.xml');
// end of qucosaform plugin configuration

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Qucosa Publication');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_documenttype', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_documenttype.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_documenttype');
$GLOBALS['TCA']['tx_dpf_domain_model_documenttype'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttype',
        'label'                    => 'name',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'name,display_name,metadata_page,',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/DocumentType.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_documenttype.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_document', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_document.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_document');
$GLOBALS['TCA']['tx_dpf_domain_model_document'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_document',
        'label'                    => 'title',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'xml_data,document_type,',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Document.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_document.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadatagroup', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadatagroup.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadatagroup');
$GLOBALS['TCA']['tx_dpf_domain_model_metadatagroup'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatagroup',
        'label'                    => 'name',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'name,display_name,mandatory,max_iteration,metadata_object,',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/MetadataGroup.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_metadatagroup.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadataobject', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadataobject.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadataobject');
$GLOBALS['TCA']['tx_dpf_domain_model_metadataobject'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadataobject',
        'label'                    => 'name',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'name,display_name,max_iteration,mandatory,mapping,mods_extension,input_field,',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/MetadataObject.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_metadataobject.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_fedoraconnection', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_fedoraconnection.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_fedoraconnection');
$GLOBALS['TCA']['tx_dpf_domain_model_fedoraconnection'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_fedoraconnection',
        'label'                    => 'url',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'url,user,password,',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/FedoraConnection.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_fedoraconnection.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadatapage', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadatapage.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadatapage');
$GLOBALS['TCA']['tx_dpf_domain_model_metadatapage'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_metadatapage',
        'label'                    => 'name',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'name,display_name,page_number,metadata_group,',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/MetadataPage.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_metadatapage.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_documenttransferlog', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_documenttransferlog.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_documenttransferlog');
$GLOBALS['TCA']['tx_dpf_domain_model_documenttransferlog'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_documenttransferlog',
        'label'                    => 'date',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'date,response,curl_error,document_uid,object_identifier,action',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/DocumentTransferLog.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_documenttransferlog.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_file', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_file.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_file');
$GLOBALS['TCA']['tx_dpf_domain_model_file'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_file',
        'label'                    => 'title',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => '',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/File.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_file.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_client', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_client.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_client');
$GLOBALS['TCA']['tx_dpf_domain_model_client'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_client',
        'label'                    => 'project',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'project,client,ownerId',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Client.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_client.gif',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_inputoptionlist', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_inputoptionlist.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_inputoptionlist');
$GLOBALS['TCA']['tx_dpf_domain_model_inputoptionlist'] = array(
    'ctrl' => array(
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_inputoptionlist',
        'label'                    => 'name',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,
        'sortby'                   => 'sorting',
        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'name,display_name,',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/InputOptionList.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_inputoptionlist.gif',
    ),
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_processnumber', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_processnumber.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_processnumber');
$GLOBALS['TCA']['tx_dpf_domain_model_processnumber'] = array(
    'ctrl' => array(
        'hideTable'                => 1,
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_processnumber',
        'label'                    => 'owner_id',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'dividers2tabs'            => true,

        'versioningWS'             => 2,
        'versioning_followPages'   => true,

        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete'                   => 'deleted',
        'enablecolumns'            => array(
            'disabled'  => 'hidden',
            'starttime' => 'starttime',
            'endtime'   => 'endtime',
        ),
        'searchFields'             => 'ownerId,year,counter',
        'dynamicConfigFile'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/ProcessNumber.php',
        'iconfile'                 => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_dpf_domain_model_processnumber.gif',
    ),
);

// Plugin "MetaTags".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_metatags'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_metatags'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:tt_content.dpf_metatags',
        $_EXTKEY . '_metatags'),
    'list_type',
    $_EXTKEY
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_metatags', 'FILE:EXT:' . $_EXTKEY . '/Classes/Plugins/MetaTags/flexform.xml');

// Plugin "DownloadTool".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_downloadtool'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_downloadtool'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:tt_content.dpf_downloadtool',
        $_EXTKEY . '_downloadtool'),
    'list_type',
    $_EXTKEY
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_downloadtool', 'FILE:EXT:' . $_EXTKEY . '/Classes/Plugins/DownloadTool/flexform.xml');

// Plugin "RelatedListTool".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_relatedlisttool'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_relatedlisttool'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:tt_content.dpf_relatedlisttool',
        $_EXTKEY . '_relatedlisttool'),
    'list_type',
    $_EXTKEY
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_relatedlisttool', 'FILE:EXT:' . $_EXTKEY . '/Classes/Plugins/RelatedListTool/flexform.xml');
