<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xml:tx_templatedisplay_displays',
		'label' => 'title',
		'descriptionColumn' => 'description',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'searchFields' => 'title,description,template',
		'typeicon_classes' => array(
			'default' => 'extensions-templatedisplay-display'
		),
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,title,description,mappings'
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xml:tx_templatedisplay_displays.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim',
			)
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xml:tx_templatedisplay_displays.description',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '4',
			)
		),
		'template' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xml:tx_templatedisplay_displays.template',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '4',
				'softref' => 'templatedisplay'
			)
		),
		'mappings' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xml:tx_templatedisplay_displays.mappings',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'Tesseract\\Templatedisplay\\UserFunction\\CustomFormEngine->mappingField',
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden, title, mappings, description')
	),
);
