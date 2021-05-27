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
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_bookmark',
        'label'                    => 'document_identifier',
        'searchFields'             => 'document_identifier, fe_user_uid',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'document_identifier, fe_user_uid',
    ),
    'types'     => array(
        '1' => array('showitem' => ',--palette--;;1,
        document_identifier, fe_user_uid,
        --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(

        'document_identifier' => array(
            'exclude' => 0,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_bookmark.document_identifier',
            'config'  => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ),
        ),
        'fe_user_uid'         => array(
            'exclude'   => 1,
            'l10n_mode' => 'exclude',
            'label'     => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_bookmark.feuser',
            'config'    => array(
                'type'    => 'input',
                'size'    => 30,
                'eval'    => 'trim'
            ),
        ),
        'pid' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
    ),
);
