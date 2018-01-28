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
            'Document'         => 'list, show, new, create, edit, update, delete, discard, release, duplicate, '
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_documenttype', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_documenttype.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_documenttype');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_document', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_document.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_document');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadatagroup', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadatagroup.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadatagroup');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadataobject', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadataobject.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadataobject');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_fedoraconnection', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_fedoraconnection.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_fedoraconnection');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadatapage', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadatapage.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadatapage');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_documenttransferlog', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_documenttransferlog.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_documenttransferlog');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_file', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_file.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_file');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_client', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_client.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_client');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_inputoptionlist', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_inputoptionlist.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_inputoptionlist');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_processnumber', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_processnumber.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_processnumber');

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
