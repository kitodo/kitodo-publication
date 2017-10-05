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

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['EWW\Dpf\Tasks\TransferTask'] = array(
    'extension'   => $_EXTKEY,
    'title'       => 'Qucosa-Dokumente ans Repository übertragen.',
    'description' => '',
);

if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']) == false) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array();
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'EWW\Dpf\Command\TransferCommandController';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'Qucosaform',
    array(
        'Document'     => 'list,show,new,create,edit,update,delete,cancel',
        'AjaxDocument' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
    ),
    // non-cacheable actions
    array(
        'Document'     => 'list,show,new,create,edit,update,delete,cancel,ajaxGroup,ajaxFileGroup,ajaxField',
        'AjaxDocument' => 'group,fileGroup,field,deleteFile,primaryUpload,secondaryUpload,fillOut',
    )
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'EWW.' . $_EXTKEY,
    'Frontendsearch',
    array(
        'SearchFE' => 'search,showSearchForm',
    ),
    // non-cacheable actions
    array(
        'SearchFE' => 'search',
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/MetaTags/MetaTags.php', '_metatags', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_metatags.userFunc = EWW\Dpf\Plugins\MetaTags\MetaTags->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/DownloadTool/DownloadTool.php', '_downloadtool', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_downloadtool.userFunc = EWW\Dpf\Plugins\DownloadTool\DownloadTool->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/RelatedListTool/RelatedListTool.php', '_relatedlisttool', 'list_type', true);
$overrideSetup = 'plugin.tx_dpf_relatedlisttool.userFunc = EWW\Dpf\Plugins\RelatedListTool\RelatedListTool->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', $overrideSetup);

$TYPO3_CONF_VARS['BE']['AJAX']['AjaxDocumentController:fieldAction'] = 'EXT:Dpf/Classes/Controller/AjaxDocumentController.php:AjaxDocumentController->fieldAction';
