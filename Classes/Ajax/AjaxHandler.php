<?php
namespace Tesseract\Templatedisplay\Ajax;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Tesseract\Templatedisplay\UserFunction\CustomFormEngine;
use Tesseract\Tesseract\Utility\Utilities;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class answers to AJAX calls from the 'templatedisplay' extension
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package	TYPO3
 * @subpackage	tx_templatedisplay
 */
class AjaxHandler {

	/**
	 * Answers the AJAX call to save the mappings configuration.
	 *
	 * @param array	$parameters Empty array
	 * @param AjaxRequestHandler $ajaxObject AJAX response object
	 * @return void
	 */
	public function saveConfiguration($parameters, AjaxRequestHandler $ajaxObject) {
		$uid = (int)GeneralUtility::_GP('uid');
		$mappings = GeneralUtility::_GP('mappings');
		$record = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid',
			'tx_templatedisplay_displays',
			'uid = '. $uid
		);

		$result = 0;
		if (!empty($record)) {
			$updateArray = array(
				'mappings' => $mappings
			);
			$message = $this->getDatabaseConnection()->exec_UPDATEquery(
				'tx_templatedisplay_displays',
				'uid = '. $uid,
				$updateArray
			);

			if ($message == 1) {
				$result = 1;
			}
		}
		$ajaxObject->addContent('templatedisplay', $result);
	}

	/**
	 * Answers to the AJAX call to perform some highlighting on the template code.
	 *
	 * @param array	$parameters Empty array
	 * @param AjaxRequestHandler $ajaxObject AJAX response object
	 * @return void
	 */
	public function saveTemplate($parameters, AjaxRequestHandler $ajaxObject) {
		$uid = (int)GeneralUtility::_GP('uid');
		$template = trim(GeneralUtility::_GP('template'));
		$record = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid',
			'tx_templatedisplay_displays',
			'uid = ' . $uid
		);

		$result = 0;
		/** @var $tceforms CustomFormEngine */
		$tceforms = GeneralUtility::makeInstance(CustomFormEngine::class);

		if (!empty($record)) {
			// Replaces tabulations by spaces. It takes less space on the screen.
			$template = str_replace('	', '  ',$template);
			$updateArray['template'] = $template;
			$msg = $this->getDatabaseConnection()->exec_UPDATEquery(
				'tx_templatedisplay_displays',
				'uid = '. $uid,
				$updateArray
			);

			if ($msg == 1) {
				// If the content starts with "FILE:" (or "file:"), handle file inclusion
				if (stripos($template, 'FILE:') === 0) {
					// Remove the "FILE:" key
					$filePath = str_ireplace('FILE:', '' , $template);
					// If the rest of the string is numeric, assume it is a reference to a sys_file
					if (is_numeric($filePath)) {
						$filePath = 'file:' . (int)$filePath;
					}
					// Try getting the full file path and the content of referenced file
					try {
						$fullFilePath = Utilities::getTemplateFilePath($filePath);
						$template = file_get_contents($fullFilePath);
						$template = str_replace('	', '  ', $template);
					}
					catch (\Exception $e) {
						// The file reference could not be resolved, issue an error message
						$template = $GLOBALS['LANG']->sL('LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xml:tx_templatedisplay_displays.fileNotFound') . ' ' . $template;
					}
                }

				$result = $tceforms->transformTemplateContent($template);
			}
		}
		$ajaxObject->addContent('templatedisplay', $result);
	}

	/**
	 * Returns the global database object.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
