<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'EWW.' . $_EXTKEY,
	'Qucosaform',
	array(                
		'DocumentForm' => 'list,show,new,create,edit,update,delete',
                'AjaxDocumentForm' => 'group,fileGroup,field',
	),
	// non-cacheable actions
	array(
		'DocumentForm' => 'list,show,new,create,edit,update,delete,ajaxGroup,ajaxFileGroup,ajaxField',
                'AjaxDocumentForm' => 'group,fileGroup,field',
	)
);


$TYPO3_CONF_VARS['BE']['AJAX']['AjaxDocumentFormController:fieldAction'] = 'EXT:Dpf/Classes/Controller/AjaxDocumentFormController.php:AjaxDocumentFormController->fieldAction';