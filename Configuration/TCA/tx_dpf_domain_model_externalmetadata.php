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
        'title'                    => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_externalmetadata',
        'label'                    => 'publication_identifier',
        'type'                     => 'record_type',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'delete'                   => 'deleted',
        'searchFields'             => 'data, fe_user, source, publication_identifier, record_type',
        'iconfile'                 => 'EXT:dpf/Resources/Public/Icons/default.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'data, fe_user, source, publication_identifier, record_type',
    ),
    'types'     => array(
        '0' => array('showitem' => 'data, fe_user, source, publication_identifier, record_type'),
    ),
    'palettes'  => array(
        '1' => array('showitem' => ''),
    ),
    'columns'   => array(
        'record_type' => array(
            'label' => 'Record type',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    ['', '0'],
                    ['CrossRefMetadata', 'EWW\Dpf\Domain\Model\CrossRefMetadata'],
                    ['DataCiteMetadata', 'EWW\Dpf\Domain\Model\DataCiteMetadata'],
                    ['K10plusMetadata', 'EWW\Dpf\Domain\Model\K10plusMetadata'],
                    ['PubMedMetadata', 'EWW\Dpf\Domain\Model\PubMedMetadata'],
                    ['BibTexMetadata', 'EWW\Dpf\Domain\Model\BibTexMetadata'],
                    ['RisWosMetadata', 'EWW\Dpf\Domain\Model\RisWosMetadata']
                ),
                'default' => '0'
            ),
        ),

        'data'                   => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_externalmetadata.data',
            'config'  => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),

        'source'                => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_externalmetadata.source',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),

        'publication_identifier'                => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_externalmetadata.publication_identifier',
            'config'  => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ),
        ),

        'fe_user'              => array(
            'exclude' => 1,
            'label'   => 'LLL:EXT:dpf/Resources/Private/Language/locallang_db.xlf:tx_dpf_domain_model_externalmetadata.fe_user',
            'config'  => array(
                'type'          => 'select',
                'items' => array (
                    array('', 0),
                ),
                'renderType'    => 'selectSingle',
                'foreign_table' => 'fe_users',
                'minitems'      => 0,
                'maxitems'      => 1,
            ),
        ),
    ),
);
