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
    'title'       => 'Kitodo.Publication-Dokumente ans Repository übertragen.',
    'description' => '',
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['EWW\Dpf\Tasks\EmbargoTask'] = array(
    'extension' => $_EXTKEY,
    'title' => 'Kitodo.Publication Embargo Task',
    'description' => 'Embargo task for sending information to admins or publish files automatically if the embargo date is expired',
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['EWW\Dpf\Tasks\FileValidationTask'] = array(
    'extension' => $_EXTKEY,
    'title' => 'Kitodo.Publication File validation Task',
    'description' => 'File validation task for the uploaded files, using an external validation api.',
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'KitodoPublicationForm',
    array(
        'DocumentForm'     => 'list,new,create,edit,update,delete,cancel,summary,register,delete',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut,remoteFileExists',
        'DataServiceAjax' => 'searchGndKeyword, autocomplete',
    ),
    // non-cacheable actions
    array(
        'DocumentForm'     => 'list,new,create,edit,update,delete,cancel,summary,register,delete,'
            . 'ajaxGroup,ajaxFileGroup,ajaxField',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut,remoteFileExists',
        'DataServiceAjax' => 'searchGndKeyword, autocomplete',
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
        'GetFile'     => 'index, mets, dataCite, attachment, zip',
    ),
    // non-cacheable actions
    array(
        'GetFile'     => 'index, mets, dataCite, attachment, zip',
    )
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'rest_api',
    [
        'Api' => 'list, show, create, suggestion, importDoiWithoutSaving, importPubmedWithoutSaving, importIsbnWithoutSaving, importBibtexWithoutSaving, importRisWithoutSaving, addFisId',
    ],
    [
        'Api' => 'list, show, create, suggestion, importDoiWithoutSaving, importPubmedWithoutSaving, importIsbnWithoutSaving, importBibtexWithoutSaving, importRisWithoutSaving, addFisId',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'BackofficeDocumentTypes',
    array(
        'DocumentType'     => 'list',
    ),
    // non-cacheable actions
    array(
        'DocumentType'     => 'list',
    )
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'InputAssistantDocumentTypes',
    array(
        'DocumentType'     => 'list',
    ),
    // non-cacheable actions
    array(
        'DocumentType'     => 'list',
    )
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'Backoffice',
    array(
        'Workspace'        => 'listWorkspace, initIndex, '
            .'batch, batchRegister, batchRemove, batchReleaseValidated, batchReleaseUnvalidated, editDocument, batchSetInProgress',
        'Document'         => 'logout, showDetails, discard, postpone, deleteLocally, deleteLocallySuggestion, register, releasePublish, '
            . 'duplicate, deleteConfirm, activateConfirm, inactivateConfirm, deleteConfirm, discardConfirm, '
            . 'releaseActivate, cancelListTask, '
            . 'suggestRestore, suggestModification, listSuggestions, showSuggestionDetails, acceptSuggestion, '
            . 'changeDocumentType, '
            . 'importListDocTypes, importSearchForm, import',
        'DocumentFormBackoffice' => 'list, new, create, edit, update, updateDocument, cancelEdit, cancelNew, cancel, createSuggestionDocument',
        'ExternalMetadataImport' => 'find, retrieve, import, createDocument, bulkStart, '
            .'bulkSearchCrossRef, bulkSearchPubMed, bulkResults, bulkImport, cancelBulkImport, bulkImportedDocuments, '
            .'uploadStart, uploadImportFile, importUploadedData, uploadedDocuments',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut,remoteFileExists',
        'AjaxBackoffice'   => 'addBookmark, removeBookmark, addWorkspaceFilter, addWorkspaceSort, '
            .'toggleWorkspaceExcludeDiscarded, toggleWorkspaceBookmarksOnly, '
            .'setWorkspaceItemsPerPage, saveExtendedSearch, loadExtendedSearchList, loadExtendedSearch, '
            .'searchFis, getFisData, searchGnd, getGndData, searchRor, getRorData, searchZdb, getZdbData, searchUnpaywall, getUnpaywallData, searchOrcid, getOrcidData, '
            .'toggleBulkImportRecord, toggleBulkImportAuthorSearch, '
            .'generateApiToken, removeApiToken, isDocumentEditable',
        'Search'           => 'search, extendedSearch, batch, batchBookmark, doubletCheck, latest',
        'User'             => 'settings, saveSettings',
        'DataServiceAjax'  => 'searchGndKeyword, autocomplete',
    ),
    // non-cacheable actions
    array(
        'Workspace'        => 'listWorkspace, initIndex, '
            .'batch, batchRegister, batchRemove, batchReleaseValidated, batchReleaseUnvalidated, editDocument, batchSetInProgress',
        'Document'         => 'logout, showDetails, discard, postpone, deleteLocally, deleteLocallySuggestion, register, releasePublish, '
            . 'duplicate, deleteConfirm, activateConfirm, inactivateConfirm, deleteConfirm, discardConfirm, '
            . 'releaseActivate, cancelListTask, '
            . 'suggestRestore, suggestModification, listSuggestions, showSuggestionDetails, acceptSuggestion, '
            . 'changeDocumentType, '
            . 'importListDocTypes, importSearchForm, import, preview',
        'DocumentFormBackoffice'   => 'list, new, create, edit, update, updateDocument, cancelEdit, cancelNew, cancel, createSuggestionDocument',
        'ExternalMetadataImport' => 'find, retrieve, import, createDocument, bulkStart, '
            .'bulkSearchCrossRef, bulkSearchPubMed, bulkResults, bulkImport, cancelBulkImport, bulkImportedDocuments, '
            .'uploadStart, uploadImportFile, importUploadedData, uploadedDocuments',
        'AjaxDocumentForm' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut,remoteFileExists',
        'AjaxBackoffice'   => 'addBookmark, removeBookmark, addWorkspaceFilter, addWorkspaceSort, '
            .'toggleWorkspaceExcludeDiscarded, toggleWorkspaceBookmarksOnly, '
            .'setWorkspaceItemsPerPage, saveExtendedSearch, loadExtendedSearchList, loadExtendedSearch, '
            .'searchFis, getFisData, searchGnd, getGndData, searchRor, getRorData, searchZdb, getZdbData, searchUnpaywall, getUnpaywallData, searchOrcid, getOrcidData, '
            .'toggleBulkImportRecord, toggleBulkImportAuthorSearch, '
            .'generateApiToken, removeApiToken, isDocumentEditable',
        'Search'           => 'search, extendedSearch, batch, batchBookmark, doubletCheck, latest',
        'User'             => 'settings, saveSettings',
        'DataServiceAjax'  => 'searchGndKeyword, autocomplete',
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Coins/Coins.php', '_coins', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_coins.userFunc = EWW\Dpf\Plugins\Coins\Coins->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

$TYPO3_CONF_VARS['BE']['AJAX']['AjaxDocumentFormController:fieldAction'] = 'EXT:Dpf/Classes/Controller/AjaxDocumentFormController.php:AjaxDocumentFormController->fieldAction';

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');

$GLOBALS['TYPO3_CONF_VARS']['LOG']['EWW']['Dpf']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG] = [
    \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [
        'logTable' => 'sys_log'
    ],
];


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
