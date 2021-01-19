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

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'EWW.Dpf',
    'Qucosaform',
    'DPF: QucosaForm'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'EWW.Dpf',
    'Qucosaxml',
    'DPF: QucosaXml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'EWW.Dpf',
    'Frontendsearch',
    'DPF: FrontendSearch'
);

// frontendsearch plugin configuration: additional fields
$pluginSignature = 'dpf_frontendsearch';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive,categories';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature]     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:dpf/Configuration/FlexForms/frontendsearch_plugin.xml');
// end of frontendsearch plugin configuration

// qucosaform plugin configuration: additional fields
$pluginSignature = 'dpf_qucosaform';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive,categories';
// end of qucosaform plugin configuration

// Plugin "MetaTags".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dpf_metatags'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dpf_metatags'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:tt_content.dpf_metatags',
        'dpf_metatags'),
    'list_type',
    'dpf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dpf_metatags', 'FILE:EXT:dpf/Classes/Plugins/MetaTags/flexform.xml');

// Plugin "DownloadTool".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dpf_downloadtool'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dpf_downloadtool'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:tt_content.dpf_downloadtool',
        'dpf_downloadtool'),
    'list_type',
    'dpf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dpf_downloadtool', 'FILE:EXT:dpf/Classes/Plugins/DownloadTool/flexform.xml');

// Plugin "RelatedListTool".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dpf_relatedlisttool'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dpf_relatedlisttool'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:tt_content.dpf_relatedlisttool',
        'dpf_relatedlisttool'),
    'list_type',
    'dpf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dpf_relatedlisttool', 'FILE:EXT:dpf/Classes/Plugins/RelatedListTool/flexform.xml');

// Plugin "Coins".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dpf_coins'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dpf_coins'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:tt_content.dpf_coins',
        'dpf_coins'),
    'list_type',
    'dpf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dpf_coins', 'FILE:EXT:dpf/Classes/Plugins/Coins/flexform.xml');
