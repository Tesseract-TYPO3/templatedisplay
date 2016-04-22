<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templatedisplay_displays');


// Register sprite icon for templatedisplay table
/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
        'tx_templatedisplay-display',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:templatedisplay/Resources/Public/Icons/TemplateDisplay.png'
        ]
);

// Add context sensitive help (csh) for this table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	'tx_templatedisplay_displays',
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Resources/Private/Language/locallang_csh_txtemplatedisplaydisplays.xlf'
);

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

// Define the path to the static TS files
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Template Display');
