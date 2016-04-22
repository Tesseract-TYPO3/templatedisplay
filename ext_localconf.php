<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

// Register method with generic BE ajax calls handler
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
	'templatedisplay::saveConfiguration',
	'Tesseract\\Templatedisplay\\Ajax\\AjaxHandler->saveConfiguration'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
	'templatedisplay::saveTemplate',
	'Tesseract\\Templatedisplay\\Ajax\\AjaxHandler->saveTemplate'
);

// Register as Data Consumer service
// Note that the subtype corresponds to the name of the database table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
	'templatedisplay',
	// Service type
	'dataconsumer',
	// Service key
	'tx_templatedisplay_dataconsumer',
	array(
		'title' => 'HTML-based Data Consumer',
		'description' => 'Data Consumer for recordset-type data structures, based on HTML templates and markers',

		'subtype' => 'tx_templatedisplay_displays',

		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,

		'os' => '',
		'exec' => '',

		'className' => 'Tesseract\Templatedisplay\Component\DataConsumer',
	)
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser'][$_EXTKEY] = 'Tesseract\Templatedisplay\Service\SoftReferenceParser';
