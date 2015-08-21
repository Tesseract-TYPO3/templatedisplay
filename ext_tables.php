<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templatedisplay_displays');

// Register sprite icon for templatedisplay table
$extensionRelativePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY);
$icon = array(
	'display' => $extensionRelativePath . 'Resources/Public/Icons/TemplateDisplay.png'
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
	$icon,
	$_EXTKEY
);

// Add context sensitive help (csh) for this table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	'tx_templatedisplay_displays',
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Resources/Private/Language/locallang_csh_txtemplatedisplaydisplays.xml'
);

// Add a wizard for adding a data consumer
$addTemplateDisplayWizard = array(
	'type' => 'script',
	'title' => 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xml:wizards.add_templatedisplay',
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

// Define the path to the static TS files
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Template Display');
