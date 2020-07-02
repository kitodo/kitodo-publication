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

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);
$composerAutoloadFile = $extensionPath . 'vendor/autoload.php';
if (is_file($composerAutoloadFile)) {
    require_once $composerAutoloadFile;
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['EWW\Dpf\Tasks\TransferTask'] = array(
    'extension'   => $_EXTKEY,
    'title'       => 'Qucosa-Dokumente ans Repository übertragen.',
    'description' => '',
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['EWW\Dpf\Tasks\EmbargoTask'] = array(
    'extension' => $_EXTKEY,
    'title' => 'Kitodo.Publication Embargo Task',
    'description' => 'Embargo task for sending information to admins or publish files automatically if the embargo date is expired',
);


if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']) == false) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array();
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'EWW\Dpf\Command\TransferCommandController';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'Qucosaform',
    array(
        'DocumentForm'     => 'list,new,create,edit,update,delete,cancel',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
        'Gnd'              => 'search',
    ),
    // non-cacheable actions
    array(
        'DocumentForm'     => 'list,new,create,edit,update,delete,cancel,ajaxGroup,ajaxFileGroup,ajaxField',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
        'Gnd'              => 'search',
    )
);


// obsolete plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'Frontendsearch',
    array(
        'SearchFE' => 'search,extendedSearch,showSearchForm',
    ),
    // non-cacheable actions
    array(
        'SearchFE' => 'search,extendedSearch'
    )
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'Getfile',
    array(
        'GetFile'     => 'attachment',
    ),
    // non-cacheable actions
    array(
        'GetFile'     => 'attachment',
    )
);


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'Backoffice',
    array(
        'Workspace'        => 'listWorkspace, initIndex, '
            .'batch, batchRegister, batchRemove, batchReleaseValidated, batchReleaseUnvalidated, uploadFiles',
        'Document'         => 'logout, showDetails, discard, postpone, deleteLocally, register, releasePublish, '
            . 'duplicate, deleteConfirm, activateConfirm, inactivateConfirm, deleteConfirm, discardConfirm, '
            . 'releaseActivate, cancelListTask, '
            . 'suggestRestore, suggestModification, listSuggestions, showSuggestionDetails, acceptSuggestion',
        'DocumentFormBackoffice'   => 'list, new, create, edit, update, updateDocument, cancelEdit, cancelNew, cancel, createSuggestionDocument',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
        'AjaxBackoffice'   => 'addBookmark, removeBookmark, addWorkspaceFilter, addWorkspaceSort, '
            .'toggleWorkspaceExcludeDiscarded, toggleWorkspaceBookmarksOnly, '
            .'setWorkspaceItemsPerPage, saveExtendedSearch, loadExtendedSearchList, loadExtendedSearch',
        'Search'           => 'search, extendedSearch, batch, batchBookmark, doubletCheck, latest',
        'Gnd'              => 'search',
        'User'             => 'settings, saveSettings',
    ),
    // non-cacheable actions
    array(
        'Workspace'        => 'listWorkspace, initIndex, '
            .'batch, batchRegister, batchRemove, batchReleaseValidated, batchReleaseUnvalidated, uploadFiles',
        'Document'         => 'logout, showDetails, discard, postpone, deleteLocally, register, releasePublish, '
            . 'duplicate, deleteConfirm, activateConfirm, inactivateConfirm, deleteConfirm, discardConfirm, '
            . 'releaseActivate, cancelListTask, '
            . 'suggestRestore, suggestModification, listSuggestions, showSuggestionDetails, acceptSuggestion',
        'DocumentFormBackoffice'   => 'list, new, create, edit, update, updateDocument, cancelEdit, cancelNew, cancel, createSuggestionDocument',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
        'AjaxBackoffice'   => 'addBookmark, removeBookmark, addWorkspaceFilter, addWorkspaceSort, '
            .'toggleWorkspaceExcludeDiscarded, toggleWorkspaceBookmarksOnly, '
            .'setWorkspaceItemsPerPage, saveExtendedSearch, loadExtendedSearchList, loadExtendedSearch',
        'Search'           => 'search, extendedSearch, batch, batchBookmark, doubletCheck, latest',
        'Gnd'              => 'search',
        'User'             => 'settings, saveSettings',
    )
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/MetaTags/MetaTags.php', '_metatags', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_metatags.userFunc = EWW\Dpf\Plugins\MetaTags\MetaTags->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/DownloadTool/DownloadTool.php', '_downloadtool', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_downloadtool.userFunc = EWW\Dpf\Plugins\DownloadTool\DownloadTool->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/RelatedListTool/RelatedListTool.php', '_relatedlisttool', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_relatedlisttool.userFunc = EWW\Dpf\Plugins\RelatedListTool\RelatedListTool->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

$TYPO3_CONF_VARS['BE']['AJAX']['AjaxDocumentFormController:fieldAction'] = 'EXT:Dpf/Classes/Controller/AjaxDocumentFormController.php:AjaxDocumentFormController->fieldAction';

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');

// Documents
$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\DocumentController::class,
    'actionChange',
    \EWW\Dpf\Services\Document\DocumentCleaner::class,
    'cleanUpDocuments',
    false
);
$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\SearchController::class,
    'actionChange',
    \EWW\Dpf\Services\Document\DocumentCleaner::class,
    'cleanUpDocuments',
    false
);
$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\DocumentFormBackofficeController::class,
    'actionChange',
    \EWW\Dpf\Services\Document\DocumentCleaner::class,
    'cleanUpDocuments',
    false
);
$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\WorkspaceController::class,
    'actionChange',
    \EWW\Dpf\Services\Document\DocumentCleaner::class,
    'cleanUpDocuments',
    false
);

// ElasticSearch
$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\AbstractController::class,
    'indexDocument',
    \EWW\Dpf\Services\ElasticSearch\ElasticSearch::class,
    'index',
    false
);

$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\AbstractController::class,
    'deleteDocumentFromIndex',
    \EWW\Dpf\Services\ElasticSearch\ElasticSearch::class,
    'delete',
    false
);