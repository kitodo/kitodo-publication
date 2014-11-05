<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'EWW.' . $_EXTKEY,
	'Qucosaform',
	array(                
		'Document' => 'list,show,new,create,edit,update,delete',
	),
	// non-cacheable actions
	array(
		'Document' => 'list,show,new,create,edit,update,delete',
	)
);
