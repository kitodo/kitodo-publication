<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'EWW.' . $_EXTKEY,
	'Qucosaform',
	array(
		'DocumentType' => 'list, show, new, create, edit, update, delete',
		'Documents' => 'list, show, new, create, edit, update, delete',
		
	),
	// non-cacheable actions
	array(
		'DocumentType' => 'create, update, delete',
		'Documents' => 'create, update, delete',
		
	)
);
