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
        'GetFile'     => 'index, mets, preview, dataCite, attachment, zip',
    ),
    // non-cacheable actions
    array(
        'GetFile'     => 'index, mets, preview, dataCite, attachment, zip',
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
            .'batch, batchRegister, batchRemove, batchRelease, editDocument, batchSetInProgress',
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
        'Message'          => 'list, retry, remove',
    ),
    // non-cacheable actions
    array(
        'Workspace'        => 'listWorkspace, initIndex, '
            .'batch, batchRegister, batchRemove, batchRelease, editDocument, batchSetInProgress',
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
        'Message'          => 'list, retry, remove',
    )
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'LandingPage',
    ['LandingPage' => 'show'],
    ['LandingPage' => 'show'] // USER_INT: always dynamic, never cache
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Metadata.php', '_metadata', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_metadata.userFunc = EWW\Dpf\Plugin\Metadata->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

$TYPO3_CONF_VARS['BE']['AJAX']['AjaxDocumentFormController:fieldAction'] = 'EXT:Dpf/Classes/Controller/AjaxDocumentFormController.php:AjaxDocumentFormController->fieldAction';

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');

// Register the custom log table
// $GLOBALS['TYPO3_CONF_VARS']['LOG']['EWW']['Dpf']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG] = [
//    \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [
//        'logTable' => 'tx_dpf_domain_model_log'
//    ],
// ];

// Register the custom logger
$GLOBALS['TYPO3_CONF_VARS']['LOG']['EWW']['Dpf']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        \EWW\Dpf\Services\Logger\Logger::class => [
            'logTable' => 'tx_dpf_domain_model_log'
        ],
    ]
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

// Public ElasticSearch index
$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\AbstractController::class,
    'indexPublicDocument',
    \EWW\Dpf\Services\ElasticSearch\PublicIndexer::class,
    'indexPublicDocument',
    false
);

$signalSlotDispatcher->connect(
    \EWW\Dpf\Controller\AbstractController::class,
    'deletePublicDocumentFromIndex',
    \EWW\Dpf\Services\ElasticSearch\PublicIndexer::class,
    'deletePublicDocument',
    false
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfMigrateDlfMetadata'] =
    \EWW\Dpf\Updates\MigrateDlfMetadataUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixIsbnMetadataCollision'] =
    \EWW\Dpf\Updates\FixIsbnMetadataCollisionUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixMetadataXpathAndLabels'] =
    \EWW\Dpf\Updates\FixMetadataXpathAndLabelsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddTypeSpecificSourceCitationRows'] =
    \EWW\Dpf\Updates\AddTypeSpecificSourceCitationRowsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixLandingPageLayoutMetadata'] =
    \EWW\Dpf\Updates\FixLandingPageLayoutMetadataUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixInstitutionGroupMapping'] =
    \EWW\Dpf\Updates\FixInstitutionGroupMappingUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixMissingPlaceOfPublicationWrap'] =
    \EWW\Dpf\Updates\FixMissingPlaceOfPublicationWrapUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfEmbedHostLinkInQuellenangabe'] =
    \EWW\Dpf\Updates\EmbedHostLinkInQuellenangabeUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixMissingPlaceOfPublicationFormat'] =
    \EWW\Dpf\Updates\FixMissingPlaceOfPublicationFormatUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixIsbnOtherVersionScope'] =
    \EWW\Dpf\Updates\FixIsbnOtherVersionScopeUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixResearchDataUrl1LinkTypo'] =
    \EWW\Dpf\Updates\FixResearchDataUrl1LinkTypoUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddFrenchTranslatedTitleRows'] =
    \EWW\Dpf\Updates\AddFrenchTranslatedTitleRowsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixIssnOtherVersionScope'] =
    \EWW\Dpf\Updates\FixIssnOtherVersionScopeUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixQuellenangabeFieldRequiredGate'] =
    \EWW\Dpf\Updates\FixQuellenangabeFieldRequiredGateUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixOtherVersionLocal1Position'] =
    \EWW\Dpf\Updates\FixOtherVersionLocal1PositionUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixHostEditorPositionScope'] =
    \EWW\Dpf\Updates\FixHostEditorPositionScopeUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddThirdEditorSlotToConferenceQuellenangabe'] =
    \EWW\Dpf\Updates\AddThirdEditorSlotToConferenceQuellenangabeUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddEditorSlotsToMediaContributionQuellenangabe'] =
    \EWW\Dpf\Updates\AddEditorSlotsToMediaContributionQuellenangabeUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixSammelbandHostLinkAndFieldOrder'] =
    \EWW\Dpf\Updates\FixSammelbandHostLinkAndFieldOrderUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfMoveEditorGroupsAboveTitle'] =
    \EWW\Dpf\Updates\MoveEditorGroupsAboveTitleUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfHideDuplicateMetadataRows'] =
    \EWW\Dpf\Updates\HideDuplicateMetadataRowsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfMergeKonferenzbandEditors'] =
    \EWW\Dpf\Updates\MergeKonferenzbandEditorsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfRestoreHostSeriesPlaceholderRows'] =
    \EWW\Dpf\Updates\RestoreHostSeriesPlaceholderRowsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixAndereAusgabeUrlCopyPaste'] =
    \EWW\Dpf\Updates\FixAndereAusgabeUrlCopyPasteUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddBlogSourceCitationRow'] =
    \EWW\Dpf\Updates\AddBlogSourceCitationRowUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddInstitutionAuthorContributorRoles'] =
    \EWW\Dpf\Updates\AddInstitutionAuthorContributorRolesUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddMissingIdentifierRows'] =
    \EWW\Dpf\Updates\AddMissingIdentifierRowsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfFixPeerReviewFieldConfig'] =
    \EWW\Dpf\Updates\FixPeerReviewFieldConfigUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddMissingLanguageRows'] =
    \EWW\Dpf\Updates\AddMissingLanguageRowsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddDocumentTypeToPublikationstypPill'] =
    \EWW\Dpf\Updates\AddDocumentTypeToPublikationstypPillUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddProjectTitleEnglishRows'] =
    \EWW\Dpf\Updates\AddProjectTitleEnglishRowsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddSourceCitationPersonInstitutionRoles'] =
    \EWW\Dpf\Updates\AddSourceCitationPersonInstitutionRolesUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddPreprintSourceCitationRow'] =
    \EWW\Dpf\Updates\AddPreprintSourceCitationRowUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddAuthorTranslatorToMediaContributionQuellenangabe'] =
    \EWW\Dpf\Updates\AddAuthorTranslatorToMediaContributionQuellenangabeUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfAddRelationDetailFields'] =
    \EWW\Dpf\Updates\AddRelationDetailFieldsUpdate::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['dpfHideDuplicateSequenceRows'] =
    \EWW\Dpf\Updates\HideDuplicateSequenceRowsUpdate::class;

// Public search results vary per request and are never page-cached (plugin runs as USER_INT),
// so its GET parameters carry no caching risk and don't need a cHash.
// CacheHashCalculator::isExcludedParameter() does an exact match per key, no wildcards.
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = array_merge(
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] ?? [],
    [
        'tx_dpf_frontendsearch[q]',
        'tx_dpf_frontendsearch[from]',
        'tx_dpf_frontendsearch[sort]',
        'tx_dpf_frontendsearch[doctype]',
        'tx_dpf_frontendsearch[year]',
        'tx_dpf_frontendsearch[yearFrom]',
        'tx_dpf_frontendsearch[yearTo]',
        'tx_dpf_frontendsearch[query][search]',
        'tx_dpf_frontendsearch[query][fulltext]',
        'tx_dpf_frontendsearch[query][title]',
        'tx_dpf_frontendsearch[query][author]',
        'tx_dpf_frontendsearch[query][abstract]',
        'tx_dpf_frontendsearch[query][tag]',
        'tx_dpf_frontendsearch[query][corporation]',
        'tx_dpf_frontendsearch[query][doctype]',
        'tx_dpf_frontendsearch[query][from]',
        'tx_dpf_frontendsearch[query][till]',
        // Vorgänger/Nachfolger link to another landing page (#2046) - qid always
        // resolves to a real document or renders nothing, no cache-pollution
        // payoff for an attacker; excluding it just drops the ugly cHash param.
        'tx_dpf_landingpage[qid]',
    ]
);
