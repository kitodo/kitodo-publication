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
            'Document'         => 'list, delete, discard, release, duplicate, convertTemplate,'
            . 'deleteConfirm, releaseConfirm, activateConfirm, inactivateConfirm, deleteConfirm, discardConfirm, restoreConfirm, '
            . 'listNew, listEdit, listTemplates, activate, inactivate, restore',

            'DocumentFormBE'   => 'list, show, new, create, edit, update, delete, cancel',
            'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
            'Search'           => 'list, search, import, doubletCheck, nextResults, extendedSearch, latest',
            'Gnd'              => 'search',
        ),
        array(
            'access'                => 'user,group',
            'icon'                  => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
            'labels'                => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_qucosa_mod_manager.xlf',
            'navigationComponentId' => 'typo3-pagetree',
        )
    );

}



\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_documenttype', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_documenttype.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_documenttype');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_document', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_document.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_document');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadatagroup', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadatagroup.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadatagroup');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_dpf_domain_model_metadataobject', 'EXT:dpf/Resources/Private/Language/locallang_csh_tx_dpf_domain_model_metadataobject.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_dpf_domain_model_metadataobject');

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
