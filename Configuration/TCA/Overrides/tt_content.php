<?php

// Add a wizard for adding a data consumer
$addTemplateDisplayWizard = array(
	'type' => 'script',
	'title' => 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xlf:wizards.add_templatedisplay',
	'script' => 'wizard_add.php',
	'module' => array(
		'name' => 'wizard_add'
	),
	'icon' => 'EXT:templatedisplay/Resources/Public/Icons/AddTemplateDisplayWizard.png',
	'params' => array(
		'table' => 'tx_templatedisplay_displays',
		'pid' => '###CURRENT_PID###',
		'setValue' => 'set'
	)
);
$GLOBALS['TCA']['tt_content']['columns']['tx_displaycontroller_consumer']['config']['wizards']['add_templatedisplay'] = $addTemplateDisplayWizard;

// Register templatedisplay with the Display Controller as a Data Consumer
$GLOBALS['TCA']['tt_content']['columns']['tx_displaycontroller_consumer']['config']['allowed'] .= ',tx_templatedisplay_displays';
