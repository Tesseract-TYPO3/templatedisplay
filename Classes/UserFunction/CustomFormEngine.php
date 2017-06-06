<?php
namespace Tesseract\Templatedisplay\UserFunction;

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

use Tesseract\Tesseract\Utility\Utilities;
use TYPO3\CMS\Backend\Form\Element\UserElement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Form Engine custom field for template mapping.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_templatedisplay
 */
class CustomFormEngine
{
    protected $extKey = 'templatedisplay';

    /**
     * Renders the user-defined mapping field,
     * i.e. the screen where data is mapped to the template markers.
     *
     * @param array $fieldParameters Information related to the field
     * @param UserElement $userElement Reference to calling TCEforms object
     *
     * @return string The HTML for the form field
     */
    public function mappingField($fieldParameters, UserElement $userElement)
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(
                FlashMessageQueue::class,
                'tx_templatedisplay'
        );
        $marker = array();
        $formField = '';
        // Get the related (primary) provider
        $provider = $this->getRelatedProvider($fieldParameters['row']);
        try {
            $fieldsArray = array();
            if ($provider) {
                $fieldsArray = $provider->getTablesAndFields();
            }
            $row = $fieldParameters['row'];

            // Read the snippets file
            $snippets = '';
            $JSSnippets = '';
            $xmlObject = simplexml_load_file(
                    GeneralUtility::getFileAbsFileName('EXT:templatedisplay/Resources/Private/Snippets/snippets.xml')
            );
            // Loop on the object types
            foreach ($xmlObject->type as $typeNode) {
                // Assemble a unique id per type
                // (this is used in JavaScript to show the correct snippet list depending on type)
                $id = 'templatedisplay_snippet' . (string)$typeNode['index'];
                $snippets .= '<div id="' . $id . '" class="templatedisplay_snippetBox templatedisplay_component templatedisplay_hidden">';
                // Loop on the snippets for the type
                foreach ($typeNode->snippet as $snippetNode) {
                    // Assemble a unique id per snippet
                    // (this is used in JavaScript to know which snippet is being loaded)
                    $id = 'templatedisplay_snippet' . (string)$snippetNode['id'];
                    $icon = $this->evaluateFileName($snippetNode['icon']);
                    $snippets .= '<span id="' . $id . '"><a href="#" onclick="return false"><img src="' . $icon . '" alt="' . $snippetNode['label'] . '" title="' . $snippetNode['label'] . '"/></a></span>';
                    // Assemble snippets as JavaScript objects
                    if (!empty($JSSnippets)) {
                        $JSSnippets .= ",\n";
                    }
                    // Each snippet is actually assembled as an array, one entry per line of snippet code
                    $typoScript = '';
                    $typoScriptLines = GeneralUtility::trimExplode("\n", (string)$snippetNode, true);
                    foreach ($typoScriptLines as $aLine) {
                        if (!empty($typoScript)) {
                            $typoScript .= ", ";
                        }
                        // Escape double quotes
                        $typoScript .= '"' . str_replace('"', '\"', $aLine) . '"';
                    }
                    $JSSnippets .= $id . ': [' . $typoScript . ']';
                }
                $snippets .= '</div>';
            }
            // Place the snippets' HTML in the template
            $marker['###SNIPPETS###'] = $snippets;

            // Prepare all content that depends on the list of element types:
            //		- options for type selector
            //		- localized string for global JS object
            //		- icons path for global JS object
            $JSLabels = '';
            $JSIcons = '';
            $marker['###TYPES_OPTIONS###'] = '';
            $extensionRelativePath = ExtensionManagementUtility::extRelPath('templatedisplay');
            // Loop on default types
            foreach (\Tesseract\Templatedisplay\Component\DataConsumer::$defaultTypes as $type) {
                $label = $this->getLL('tx_templatedisplay_displays.type') . ': ' . $this->getLL('tx_templatedisplay_displays.' . $type);
                $option = '<option value="' . $type . '">' . $label . '</option>';
                $marker['###TYPES_OPTIONS###'] .= $option;
                if (!empty($JSLabels)) {
                    $JSLabels .= ",\n";
                    $JSIcons .= ",\n";
                }
                $JSLabels .= $type . ': "' . $label . '"';
                $JSIcons .= $type . ': "' . $extensionRelativePath . 'Resources/Public/images/' . $type . '.png"';
            }
            // Loop on types added by extensions
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['types']) && count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['types']) > 0) {
                $unknownIcon = $extensionRelativePath . 'Resources/Public/images/unknown.png';
                $unknownLabel = $this->getLL('tx_templatedisplay_displays.type') . ': ' . $this->getLL('tx_templatedisplay_displays.unknown');
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['types'] as $type => $newTypeData) {
                    $label = $GLOBALS['LANG']->sL($newTypeData['label']);
                    if (empty($label)) {
                        $label = $unknownLabel;
                    } else {
                        $label = $this->getLL('tx_templatedisplay_displays.type') . ': ' . $label;
                    }
                    $option = '<option value="' . $type . '">' . $label . '</option>';
                    $marker['###TYPES_OPTIONS###'] .= $option;
                    if (!empty($JSLabels)) {
                        $JSLabels .= ",\n";
                        $JSIcons .= ",\n";
                    }
                    $JSLabels .= $type . ': "' . $label . '"';
                    $icon = $this->evaluateFileName($newTypeData['icon']);
                    // Use the "unknown type" icon if file was not found
                    if (empty($icon)) {
                        $icon = $unknownIcon;
                    }
                    $JSIcons .= $type . ': "' . $icon . '"';
                }
            }
            // Put all labels, icons paths and snippets definitions into global JS object
            $preJS = '
				var LOCALAPP = {
					labels : {' .
                    $JSLabels
                    . '},
					icons : {' .
                    $JSIcons
                    . '},
					snippets : {' .
                    $JSSnippets
                    . '}
				};
			';

            // Get the page renderer object from the BE module
            /** @var PageRenderer $pageRenderer */
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            // Load the necessary JavaScript
            // @todo: migrate away from Prototype
            $pageRenderer->addJsFile($extensionRelativePath . 'Resources/Public/JavaScript/Library/prototype.js');
            $pageRenderer->addJsFile($extensionRelativePath . 'Resources/Public/JavaScript/formatJson.js');
            $pageRenderer->addJsFile($extensionRelativePath . 'Resources/Public/JavaScript/templatedisplay.js');
            $pageRenderer->addCssFile($extensionRelativePath . 'Resources/Public/Styles/templatedisplay.css');
            if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) > 7000000) {
                $pageRenderer->addCssFile($extensionRelativePath . 'Resources/Public/Styles/Patch.css');
            }
            $pageRenderer->addJsLibrary('common', 'js/common.js');

            // Load the dynamically assembled JavaScript at top of form
            $pageRenderer->addJsInlineCode(
                    'tx_templatedisplay_formengine',
                    $preJS
            );

            $marker['###CONTENT_FROM_FILE###'] = '';
            $marker['###IMPORTED###'] = '';
            $marker['###TEMPLATE_CONTENT_SRC###'] = $row['template'];
            $templateContent = $row['template'];

            // True when the user has defined no template.
            if (empty($row['template'])) {
                $templateContent = $this->getLL('tx_templatedisplay_displays.noTemplateFoundError');

                // If the content starts with "FILE:" (or "file:"), handle file inclusion
            } elseif (stripos($row['template'], 'FILE:') === 0) {
                // Remove the "FILE:" key
                $filePath = str_ireplace('FILE:', '', $row['template']);
                // If the rest of the string is numeric, assume it is a reference to a sys_file
                if (is_numeric($filePath)) {
                    $filePath = 'file:' . (int)$filePath;
                }
                // Try getting the full file path and the content of referenced file
                try {
                    $fullFilePath = Utilities::getTemplateFilePath($filePath);
                    $marker['###IMPORTED###'] = '(' . $this->getLL('tx_templatedisplay_displays.imported') . ')';
                    $templateContent = file_get_contents($fullFilePath);
                    $templateContent = str_replace('	', '  ', $templateContent);
                } catch (\Exception $e) {
                    // The file reference could not be resolved, issue an error message
                    $templateContent = $this->getLL('tx_templatedisplay_displays.fileNotFound') . ' ' . $row['template'];
                }
            }

            // Initialize the select drop down which contains the fields
            $options = '';
            foreach ($fieldsArray as $keyTable => $fields) {
                $options .= '<optgroup label="' . $keyTable . '" class="c-divider">';
                $fieldList = $fields['fields'];
                ksort($fieldList);
                foreach ($fieldList as $keyField => $field) {
                    $options .= '<option value="' . $keyTable . '.' . $keyField . '">' . $keyField . '</option>';
                }
                $options .= '</optgroup>';
            }
            $marker['###AVAILABLE_FIELDS###'] = $options;
            $marker['###IS_FIELDS_ENABLED###'] = '';
            if (count($fieldsArray) == 0) {
                $marker['###IS_FIELDS_ENABLED###'] = 'style="display:none"';
            }

            // Reinitializes the array pointer
            reset($fieldsArray);

            // Initialize some template variable
            $marker['###DEFAULT_TABLE###'] = key($fieldsArray);
            $marker['###TEMPLATE_CONTENT###'] = $this->transformTemplateContent($templateContent);
            $marker['###STORED_FIELD_NAME###'] = $fieldParameters['itemFormElName'];
            $marker['###STORED_FIELD_NAME_TEMPLATE###'] = str_replace('mappings', 'template',
                    $fieldParameters['itemFormElName']);
            $marker['###STORED_FIELD_VALUE###'] = $row['mappings'];
            $marker['###INFOMODULE_PATH###'] = $extensionRelativePath . 'Resources/Public/images/';
            $marker['###UID###'] = $row['uid'];
            $marker['###SHOW_JSON###'] = $this->getLL('tx_templatedisplay_displays.showJson');
            $marker['###EDIT_JSON###'] = $this->getLL('tx_templatedisplay_displays.editJson');
            $marker['###EDIT_HTML###'] = $this->getLL('tx_templatedisplay_displays.editHtml');
            $marker['###MAPPING###'] = $this->getLL('tx_templatedisplay_displays.mapping');
            $marker['###TYPES###'] = $this->getLL('tx_templatedisplay_displays.types');
            $marker['###FIELDS###'] = $this->getLL('tx_templatedisplay_displays.fields');
            $marker['###CONFIGURATION###'] = $this->getLL('tx_templatedisplay_displays.configuration');
            $marker['###SAVE_FIELD_CONFIGURATION###'] = $this->getLL('tx_templatedisplay_displays.saveFieldConfiguration');

            // Parse the template and render it.
            $backendTemplatefile = GeneralUtility::getFileAbsFileName('EXT:templatedisplay/Resources/Private/Templates/templatedisplay.html');
            /** @var MarkerBasedTemplateService $templateService */
            $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
            $formField .= $templateService->substituteMarkerArray(
                    file_get_contents($backendTemplatefile),
                    $marker
            );
        } catch (\Exception $e) {
            /** @var $flashMessage FlashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $e->getMessage(),
                    '',
                    FlashMessage::ERROR
            );
            $flashMessageQueue->enqueue($flashMessage);
            $formField .= $flashMessageQueue->renderFlashMessages();
        }
        return $formField;
    }

    /**
     * Highlights and makes clickable some area of the template.
     *
     * - wraps IF markers with a different background
     * - wraps LOOP markers with a different background
     * - wraps FIELD markers and makes them reactive to a mouse click
     *
     * Also called externally from the Ajax Handler.
     *
     * @param    string $templateContent
     * @return    string    $templateContent, the content transformed
     * @see \Tesseract\Templatedisplay\Ajax\AjaxHandler
     */
    public function transformTemplateContent($templateContent)
    {
        $templateContent = htmlspecialchars($templateContent);

        # Wrap IF markers with a different background
        $pattern = $replacement = array();
        $pattern[] = "/(&lt;!-- *IF *\(.+--&gt;|&lt;!-- *ELSE *--&gt;|&lt;!-- *ENDIF *--&gt;)/isU";
        $replacement[] = '<span class="templatedisplay_if">$1</span>';

        $pattern[] = "/(&lt;!-- *EMPTY *--&gt;|&lt;!-- *ENDEMPTY *--&gt;)/isU";
        $replacement[] = '<span class="templatedisplay_empty">$1</span>';

        $pattern[] = "/(#{3}.+#{3})/isU";
        $replacement[] = '<span class="templatedisplay_label">$1</span>';

        #$pattern[] = "/(&lt;!-- *ENDIF *--&gt;)/isU";
        #$replacement[] = '<span class="templatedisplay_if">$1</span>';

        # LIMIT, UPPERCASE, LOWERCASE, UPPERCASE_FIRST
        $pattern[] = "/(PRINTF\(.+\)|LIMIT\(.+\)|UPPERCASE\(.+\)|FUNCTION:.*\(.+\)|LOWERCASE\(.+\)|UPPERCASE_FIRST\(.+\)|COUNT\(.+\)|PAGE_STATUS\(.+\))/isU";
        $replacement[] = '<span class="templatedisplay_function">$1</span>';

        # Wrap LOOP markers with a different background
        $pattern[] = "/(&lt;!-- *LOOP *\(.+--&gt;)/isU";
        $replacement[] = '<span class="templatedisplay_loop">$1</span>';

        $pattern[] = "/(&lt;!-- *ENDLOOP *--&gt;)/isU";
        $replacement[] = '<span class="templatedisplay_loop">$1</span>';

        # Wrap FIELD markers with a clickable href
        $pattern[] = '/(#{3}FIELD.+#{3}|#{3}OBJECT.+#{3})/isU';
        $path = ExtensionManagementUtility::extRelPath('templatedisplay') . 'Resources/Public/images/';
        $_replacement = '<span class="mapping_pictogrammBox">';
        $_replacement .= '<a href="#" onclick="return false">$1</a>';
        $_replacement .= '<img src="' . $path . 'empty.png" alt="" class="mapping_pictogramm1"/>';
        $_replacement .= '<img src="' . $path . 'empty.png" alt="" class="mapping_pictogramm2"/>';
        $_replacement .= '</span>';
        $replacement[] = $_replacement;

        return preg_replace($pattern, $replacement, $templateContent);
    }

    /**
     * Returns the translated string according to the key.
     *
     * @param string $key Key of the label
     */
    private function getLL($key)
    {
        $langReference = 'LLL:EXT:templatedisplay/Resources/Private/Language/locallang_db.xlf:';
        return $GLOBALS['LANG']->sL($langReference . $key);
    }

    /**
     * Takes a filename and evaluates it.
     *
     * The file name is transformed it into a relative path
     * if the name begins with "EXT:". Otherwise it is returned as is.
     * The name might be empty if the mentioned "EXT" is not found.
     *
     * @param string $filename The filename to interpret
     * @return string The interpreted filename
     */
    protected function evaluateFileName($filename)
    {
        $relFilePath = '';
        // If the file path begins with EXT:, interpret the path to the extension
        if (substr($filename, 0, 4) == 'EXT:') {
            list($extKey, $local) = explode('/', substr($filename, 4), 2);
            if (!empty($extKey) && ExtensionManagementUtility::isLoaded($extKey) && !empty($local)) {
                $relFilePath = ExtensionManagementUtility::extRelPath($extKey) . $local;
            }

            // If not, take path as is
        } else {
            $relFilePath = $filename;
        }
        return $relFilePath;
    }

    /**
     * Returns the names of all tables that store relations
     * between controllers and components.
     *
     * This has been abstracted in a method in case the way of retrieving this list is changed in the future.
     *
     * @return    array    List of table names
     */
    protected function getMMTablesList()
    {
        return $GLOBALS['T3_VAR']['EXT']['tesseract']['controller_mm_tables'];
    }

    /**
     * Retrieves a controller which calls this specific instance of template display.
     *
     * @param array $row Database record corresponding the instance of template display
     * @return \Tesseract\Tesseract\Component\DataProviderInterface
     */
    protected function getRelatedProvider($row)
    {
        $provider = null;
        // Get the list of tables where relations are stored
        $mmTables = $this->getMMTablesList();
        // In each table, try to find relations to the current templatedisplay component
        foreach ($mmTables as $aTable) {
            $relations = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    'uid_local, local_table, local_field',
                    $aTable,
                    'uid_foreign = ' . (int)$row['uid'] . ' AND tablenames = \'tx_templatedisplay_displays\''
            );
            $numRelations = count($relations);
            // Exit the loop as soon as at least one relation is found
            if ($numRelations > 0) {
                foreach ($relations as $aRelation) {
                    // Try to get the related controller
                    $table = $aRelation['local_table'];
                    $field = $aRelation['local_field'];
                    $uid = (int)$aRelation['uid_local'];
                    $where = 'uid = ' . $uid;
                    $deleteClause = BackendUtility::deleteClause($table);
                    if (!empty($deleteClause)) {
                        $where .= $deleteClause;
                    }
                    $relatedRecords = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            $field,
                            $table,
                            $where
                    );

                    // Continue if a controller was found
                    if (count($relatedRecords) > 0) {
                        // Instantiate the corresponding service and load the data into it
                        /** @var $controller \Tesseract\Tesseract\Component\DataControllerInterface */
                        $controller = GeneralUtility::makeInstanceService(
                                'datacontroller',
                                $relatedRecords[0][$field]
                        );
                        $controller->loadData($uid);
                        // Now get the provider an return it (no need to check other relations, if any)
                        // NOTE: getRelatedProvider() may throw an exception, but we just let it pass at this point
                        $provider = $controller->getRelatedProvider();
                        return $provider;
                    }
                }
            }
        }
        return $provider;
    }

    /**
     * Returns the global database object.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
