<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (!defined ('TYPO3_MODE')) die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['EWW\Dpf\Tasks\TransferTask'] = array(
  'extension'        => $_EXTKEY,
  'title'            => 'Qucosa-Dokumente ans Repository übertragen.',
  'description'      => ''
);


if(isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']) == false) {
  $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array();
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'EWW\Dpf\Command\TransferCommandController';



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