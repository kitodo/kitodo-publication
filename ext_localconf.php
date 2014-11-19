<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'EWW.' . $_EXTKEY,
	'Qucosaform',
	array(                
		'DocumentForm' => 'list,show,new,create,edit,update,delete,ajaxGroup,ajaxFileGroup',
	),
	// non-cacheable actions
	array(
		'DocumentForm' => 'list,show,new,create,edit,update,delete,ajaxGroup,ajaxFileGroup',
	)
);
