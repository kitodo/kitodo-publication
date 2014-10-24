<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'EWW.' . $_EXTKEY,
	'Qucosaform',
	array(
		'FormBuilder' => 'show',	
	),
	// non-cacheable actions
	array(
		'FormBuilder' => 'show',
	)
);
