<?php
namespace Tesseract\Templatedisplay\Component;

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

use Cobweb\Expressions\ExpressionParser;
use Tesseract\Templatedisplay\RenderingType\CustomTypeInterface;
use Tesseract\Tesseract\Service\FrontendConsumerBase;
use Tesseract\Tesseract\Tesseract;
use Tesseract\Tesseract\Utility\Utilities;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Data Consumer for the 'templatedisplay' extension.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @author Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package TYPO3
 * @subpackage tx_templatedisplay
 */
class DataConsumer extends FrontendConsumerBase
{

    public $tsKey = 'tx_templatedisplay';
    public $extKey = 'templatedisplay';
    /**
     * @var array List of default rendering types
     */
    public static $defaultTypes = array(
            'raw',
            'text',
            'richtext',
            'image',
            'imageResource',
            'media',
            'files',
            'records',
            'linkToDetail',
            'linkToPage',
            'linkToFile',
            'email',
            'user'
    );
    /**
     * @var array TypoScript configuration
     */
    protected $conf;
    /**
     * @var string Name of the table where the details about the data display are stored
     */
    protected $table;
    /**
     * @var int Primary key of the record to fetch for the details
     */
    protected $uid;
    /**
     * @var array Input standardised data structure
     */
    protected $structure = array();
    /**
     * @var string The result of the processing by the Data Consumer
     */
    protected $result = '';
    /**
     * @var array List of counters from the various loops
     */
    protected $counter = array();
    /**
     * @var bool Debug flag
     */
    protected $debug = false;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    protected $labelMarkers = array();
    protected $datasourceFields = array();
    protected $datasourceObjects = array();
    protected $LLkey = 'default';
    protected $fieldMarkers = array();

    /**
     * @var    array $functions : list of function handled by templatedisplay 'LIMIT', 'UPPERCASE', 'LOWERCASE', 'UPPERCASE_FIRST
     */
    protected $functions = array(
            'FUNCTION',
            'LIMIT',
            'UPPERCASE',
            'LOWERCASE',
            'UPPERCASE_FIRST',
            'COUNT',
            'PRINTF',
            'STR_REPLACE',
            'STRIPSLASHES'
    );

    /**
     * @var array List of numerical markers
     */
    protected $numericalMarkers = array(
            'COUNTER',
            'TOTAL_RECORDS',
            'SUBTOTAL_RECORDS',
            'RECORD_OFFSET',
            'START_AT',
            'STOP_AT'
    );

    /**
     * @var ContentObjectRenderer Local rendering instance
     */
    protected $localCObj;

    public function __construct()
    {
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }

    /**
     * Resets values for a number of properties.
     *
     * This is necessary because services are managed as singletons.
     *
     * @return void
     */
    public function reset()
    {
        $this->structure = array();
        $this->result = '';
        $this->uid = '';
        $this->table = '';
        $this->conf = array();
        $this->datasourceFields = array();
        $this->datasourceObjects = array();
        $this->LLkey = 'default';
        $this->fieldMarkers = array();
    }

    /**
     * Returns the filter data.
     *
     * @return array
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Passes a TypoScript configuration (in array form) to the Data Consumer.
     *
     * @param array $conf TypoScript configuration for the extension
     */
    public function setTypoScript($conf)
    {
        $this->conf = $conf;
    }

    // Data Consumer interface methods

    /**
     * Returns the type of data structure that the Data Consumer can use.
     *
     * @return string Type of used data structures
     */
    public function getAcceptedDataStructure()
    {
        return Tesseract::RECORDSET_STRUCTURE_TYPE;
    }

    /**
     * Indicates whether the Data Consumer can use the type of data structure requested or not.
     *
     * @param string $type Type of data structure
     * @return boolean True if it can use the requested type, false otherwise
     */
    public function acceptsDataStructure($type)
    {
        return $type === Tesseract::RECORDSET_STRUCTURE_TYPE;
    }

    /**
     * Passes a data structure to the Data Consumer.
     *
     * @param array $structure Standardised data structure
     * @return void
     */
    public function setDataStructure($structure)
    {
        $this->structure = $structure;
    }

    /**
     * Passes a filter to the Data Consumer.
     *
     * @param array $filter Data Filter structure
     * @return void
     */
    public function setDataFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Gets the data structure.
     *
     * @return array $structure Standardised data structure
     */
    public function getDataStructure()
    {
        return $this->structure;
    }

    /**
     * Returns the result of the work done by the Data Consumer (FE output or whatever else).
     *
     * @return mixed The result of the Data Consumer's work
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Sets the result. Useful for hooks.
     *
     * @param string $result Some existing result
     * @return void
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Starts the rendering process.
     *
     * @return void
     */
    public function startProcess()
    {

        // ************************************
        // ********** INITIALISATION **********
        // ************************************

        // Initializes local cObj
        $this->localCObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->debug = $this->controller->getDebug();

        $this->setPageTitle($this->conf);

        // ****************************************
        // ********** FETCHES DATASOURCE **********
        // ****************************************

        // Transforms the string from field mappings into a PHP array.
        // This array contains the mapping information btw a marker and a field.
        try {
            $datasource = json_decode($this->consumerData['mappings'], true);

            // Makes sure $datasource is an array
            if ($datasource === null) {
                $datasource = array();
            }
        } catch (\Exception $e) {
            // Issue error message and exit immediately
            $this->controller->addMessage(
                    $this->extKey,
                    'JSON decoding failed, rendering aborted',
                    '',
                    FlashMessage::ERROR,
                    array($this->consumerData['mappings'])
            );
            return;
        }

        $uniqueMarkers = array();

        // Formats TypoScript configuration as array
        /** @var $parseObj TypoScriptParser */
        $parseObj = GeneralUtility::makeInstance(TypoScriptParser::class);
        foreach ($datasource as $data) {
            if (trim($data['configuration']) != '') {

                // Clears the setup (to avoid typoscript incrementation)
                $parseObj->setup = array();
                $parseObj->parse($data['configuration']);
                $data['configuration'] = $parseObj->setup;
            } else {
                $data['configuration'] = array();
            }

            // Merges some data to create a new marker. Will look like: table.field
            $_marker = $data['table'] . '.' . $data['field'];

            // IMPORTANT NOTICE:
            // The idea is to make the field unique and to be able to know which field of the database is associated
            // Adds to ###FIELD.xxx### the value "table.field"
            // Ex: [###FIELD.title###] => ###FIELD.title.pages.title###
            $uniqueMarkers['###' . $data['marker'] . '###'] = '###' . $data['marker'] . '.' . $_marker . '###';

            // Builds the datasource as an associative array.
            // $data contains the following information: [marker], [table], [field], [type], [configuration]
            if (preg_match('/FIELD/', $data['marker'])) {
                $this->datasourceFields[$data['marker']] = $data;
            } else {
                $this->datasourceObjects[$data['marker']] = $data;
            }
        }

        // ***************************************
        // ********** BEGINS PROCESSING **********
        // ***************************************

        // LOCAL DOCUMENTATION:
        // $templateCode -> HTML template roughly extracted from the database
        // $templateContent -> HTML that is going to be output

        // Loads the template file
        $templateCode = $this->consumerData['template'];
        // If the content starts with "FILE:" (or "file:"), handle file inclusion
        if (stripos($templateCode, 'FILE:') === 0) {
            // Remove the "FILE:" key
            $filePath = str_ireplace('FILE:', '', $templateCode);
            // If the rest of the string is numeric, assume it is a reference to a sys_file
            if (is_numeric($filePath)) {
                $filePath = 'file:' . (int)$filePath;
            }
            // Try getting the full file path and the content of referenced file
            try {
                $fullFilePath = Utilities::getTemplateFilePath($filePath);
                $templateCode = file_get_contents($fullFilePath);
            } catch (\Exception $e) {
                // The file reference could not be resolved, set an empty template and issue an error message
                $templateCode = '';
                $this->controller->addMessage(
                        $this->extKey,
                        $e->getMessage() . ' (' . $e->getCode() . ')',
                        'Template file not found',
                        FlashMessage::ERROR
                );
            }
        }

        // Hook that enables to pre process the output)
        if (preg_match_all('/#{3}HOOK\.(.+)#{3}/isU', $templateCode, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $hookName = $match[1];
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preProcessResult'][$hookName])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preProcessResult'][$hookName] as $className) {
                        $preProcessor = GeneralUtility::getUserObj($className);
                        $templateCode = $preProcessor->preProcessResult($templateCode, $hookName, $this);
                    }
                }
            }
        }

        // Global markers are replaced first, so that they are available outise of any loop
        $globalVariablesMarkers = $this->getGlobalVariablesMarkers($templateCode);
        $templateCode = $this->templateService->substituteMarkerArray($templateCode, $globalVariablesMarkers);

        // Begins $templateCode transformation.
        // *Must* be at the beginning of startProcess()
        $templateCode = $this->checkPageStatus($templateCode);
        $templateCode = $this->preProcessIF($templateCode);
        $templateCode = $this->processOBJECTS($templateCode);
        $templateCode = $this->preProcessFUNCTIONS($templateCode);
        $templateCode = $this->processLOOP($templateCode); // Adds a LOOP marker of first level, if it does not exist.

        // Handles possible marker: ###LLL:EXT:myextension/localang.xml:myLable###, ###GP:###, ###TSFE:### etc...
        $LLLMarkers = $this->getLLLMarkers($templateCode);
        $expressionMarkers = $this->getAllExpressionMarkers($templateCode);
        $sortMarkers = $this->getSortMarkers($templateCode);
        $filterMarkers = $this->getFilterMarkers($templateCode);

        // Merges array, in order to have only one array (performance!)
        $markers = array_merge($uniqueMarkers, $LLLMarkers, $expressionMarkers, $sortMarkers, $filterMarkers);

        // First transformation of $templateCode. Substitutes $markers that can be already substituted. (LLL, GP, TSFE, etc...)
        $templateCode = $this->templateService->substituteMarkerArray($templateCode, $markers);

        // Cuts out the template into different part and organizes it in an array.
        $templateStructure = $this->getTemplateStructure($templateCode);

        // Debug
        $this->performDebug($markers, $templateStructure);

        // Transforms the HTML template to HTML content
        $templateContent = $templateCode;
        foreach ($templateStructure as &$_templateStructure) {
            if (!empty($this->structure['records'])) {
                $_content = $this->getContent($_templateStructure, $this->structure);
                $templateContent = str_replace($_templateStructure['template'], $_content, $templateContent);
            } else {
                // Checks if an empty value must replace the block.
                $_content = $this->getEmptyValue($_templateStructure);
                $templateContent = str_replace($_templateStructure['template'], $_content, $templateContent);
            }
        }

        // Useful when the data structure is empty (no records)
        if (!$this->getLabelMarkers($this->structure['name'])) {
            $this->setLabelMarkers($this->structure);
        }
        // Translates outer labels and fields.
        $fieldMarkers = array_merge($this->fieldMarkers, $this->getLabelMarkers($this->structure['name']),
                array('###COUNTER###' => '0'));
        $templateContent = $this->templateService->substituteMarkerArray($templateContent, $fieldMarkers);

        // Handles the page browser
        $templateContent = $this->processPageBrowser($templateContent);

        // Handles the <!--IF(###MARKER### == '')-->
        // Evaluates the condition and replaces the content whether it is necessary
        // Must be at the end of startProcess()
        $templateContent = $this->postProcessFUNCTIONS($templateContent);
        $this->result = $this->postProcessIF($templateContent);

        // Hook that enables to post process the output)
        if (preg_match_all('/#{3}HOOK\.(.+)#{3}/isU', $this->result, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $hookName = $match[1];
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['postProcessResult'][$hookName])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['postProcessResult'][$hookName] as $className) {
                        $postProcessor = GeneralUtility::getUserObj($className);
                        $this->result = $postProcessor->postProcessResult($this->result, $hookName, $this);
                    }
                }
            }
        }

        // Processes markers of type ###RECORD(tt_content,1)###
        $this->result = $this->processRECORDS($this->result);

        $this->result = $this->clearMarkers($this->result);
    }

    /**
     * Removes unreplaced markers (unless debug is active).
     *
     * @param string $content The prepared output
     * @return string Cleaned up output
     */
    function clearMarkers($content)
    {
        // Useful for debug purpose. Whenever the parameter is detected, it will not replace empty value.
        if (!$this->debug) {
            $content = preg_replace('/#{3}.+#{3}/isU', '', $content);
        }
        // Replace escaped markers
        $content = str_replace('\#\#\#', '###', $content);
        return $content;
    }

    /**
     * Processes markers of type ###RECORD('tt_content',1)###.
     *
     * @param string $content The content
     * @return string Processed content
     */
    protected function processRECORDS($content)
    {

        if (preg_match_all("/#{3}RECORD\((.+),(.+)\)#{3}/isU", $content, $matches, PREG_SET_ORDER)) {

            // Stores the filter. Templatedisplay is a singleton and the filter property will be overridden by a child call.
            $GLOBALS['tesseract']['filter']['parent'] = $this->filter;

            // Get the current controller's id
            // NOTE: At least in a FE context, it should never be missing,
            // since it will correspond to a tt_content record.
            try {
                $currentUid = $this->controller->getControllerDataValue('uid');
            } catch (\Tesseract\Tesseract\Exception\Exception $e) {
                $currentUid = 0;
            }

            foreach ($matches as $match) {
                $marker = $match[0];
                $table = trim($match[1]);
                $uid = trim($match[2]);

                // Avoids recursive call
                // Issue a warning if that is the case
                if ($currentUid == $uid) {
                    $this->controller->addMessage(
                            $this->extKey,
                            'Recursive call to RECORD ' . $table . ':' . $uid,
                            '',
                            FlashMessage::WARNING
                    );
                } else {
                    $conf = array();
                    $conf['source'] = $table . '_' . $uid;
                    $conf['tables'] = $table;
                    $_content = $this->localCObj->cObjGetSingle(
                            'RECORDS',
                            $conf
                    );
                    $content = str_replace($marker, $_content, $content);
                }
            }
        }
        return $content;
    }

    /**
     * Changes the page title if templatedisplay encounters TypoScript configuration.
     *
     * Typoscript configuration have the insertData syntax e.g. {table.field}.
     * This is done by changing the page title in the tslib_fe object.
     *
     * @param array $configuration Local TypoScript configuration
     * @return void
     */
    protected function setPageTitle($configuration)
    {
        // Checks whether the title of the template need to be changed
        if ($configuration['substitutePageTitle']) {
            $pageTitle = $configuration['substitutePageTitle'];

            // extracts the {table.field}
            if (preg_match_all('/\{(.+)\}/isU', $pageTitle, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $expression = $match[0];
                    $expressionInner = $match[1];
                    $values = explode('.', $expressionInner);

                    // Checks if table name is given or not.
                    if (count($values) == 1) {
                        $table = $this->structure['name'];
                        $field = $values[0];
                    } elseif (count($values) == 2) {
                        $table = $values[0];
                        $field = $values[1];
                    }
                    $expressionResult = $this->getValueFromStructure($this->structure, 0, $table, $field);
                    $pageTitle = str_replace($expression, $expressionResult, $pageTitle);
                }
            }
            $GLOBALS['TSFE']->page['title'] = $pageTitle;
        }
    }

    /**
     * Makes sure the operand does not contain the symbol "'".
     *
     * @param string $operand
     * @return string
     */
    protected function sanitizeOperand($operand)
    {
        $operand = trim($operand);
        if (substr($operand, 0, 1) == "'") {
            $operand = substr($operand, 1, strlen($operand) - 2);
            $operand = str_replace("'", "\'", $operand);
            $operand = "'" . $operand . "'";
        }
        return $operand;
    }

    /**
     * If found, returns markers of type SORT.
     *
     * Example of marker: ###SORT###
     *
     * @param    string $content HTML code
     * @return    string    $content transformed HTML code
     */
    protected function getSortMarkers($content)
    {
        $markers = array();
        if (preg_match_all('/#{3}SORT\.(.+)#{3}/isU', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $marker = $match[0];
                $markerContent = $match[1];
                // Get the position of the sort
                if (preg_match('/([0-9])$/is', $markerContent, $positions)) {
                    $position = $positions[0];
                } else {
                    $position = 1;
                }

                // Gets whether it is a sort or an order
                if (strpos($markerContent, 'sort') !== false) {
                    $sortTable = '';
                    if ($this->filter['orderby'][$position * 2 - 1]['table'] != '') {
                        $sortTable = $this->filter['orderby'][$position * 2 - 1]['table'] . '.';
                    }
                    $markers[$marker] = $sortTable . $this->filter['orderby'][$position * 2 - 1]['field'];
                } else {
                    if (strpos($markerContent, 'order') !== false) {
                        $markers[$marker] = $this->filter['orderby'][$position * 2 - 1]['order'];
                    }
                }
            }
        }
        // Post-process markers as possible expressions
        foreach ($markers as &$marker) {
            $marker = ExpressionParser::evaluateString($marker);
        }
        return $markers;
    }

    /**
     * If found, returns markers of type FILTER.
     *
     * Example of marker: ###FILTER###
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function getFilterMarkers($content)
    {
        $markers = array();
        if (preg_match_all('/#{3}FILTER\.(.+)#{3}/isU', $content, $matches, PREG_SET_ORDER)) {

            // Defines the filters array.
            // It can be the property of the object
            // But the filter can be given by the caller. @see method processRECORDS();
            if (isset($GLOBALS['tesseract']['filter']['parent'])) {
                $filters = $GLOBALS['tesseract']['filter']['parent'];
            } else {
                $filters = $this->filter;
            }

            // Traverse the FILTER markers
            foreach ($matches as $match) {
                $marker = $match[0];
                $markerInner = $match[1];

                // Traverses the array and finds the value
                if (isset($filters['parsed']['filters'][$markerInner])) {
                    $_filter = $filters['parsed']['filters'][$markerInner];
                    $_filter = reset($_filter); //retrieve the cell independently from the key
                    $markers[$marker] = $_filter['value'];
                }
            }
        }
        // Post-process markers as possible expressions
        foreach ($markers as &$marker) {
            $marker = ExpressionParser::evaluateString($marker);
        }
        return $markers;
    }

    /**
     * Returns all markers that correspond to subexpressions
     * and can be parsed using \Cobweb\Expressions\ExpressionParser.
     *
     * Example of GP marker: ###EXPRESSION.gp:parameter1|parameter2###
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function getAllExpressionMarkers($content)
    {
        $markers = array();
        if (preg_match_all('/#{3}EXPRESSION\.(.+)#{3}/isU', $content, $matches, PREG_SET_ORDER)) {
            $numberOfMatches = count($matches);
            if ($numberOfMatches > 0) {
                for ($index = 0; $index < $numberOfMatches; $index++) {
                    try {
                        $markers[$matches[$index][0]] = ExpressionParser::evaluateExpression($matches[$index][1]);
                    } catch (\Exception $e) {
                        $markers[$matches[$index][0]] = 'NULL';
                        $this->controller->addMessage(
                                $this->extKey,
                                'Problem parsing expression "' . $matches[$index][1] . '" (' . $e->getMessage() . ')',
                                '',
                                FlashMessage::WARNING
                        );
                    }
                }
            }
        }
        return $markers;
    }

    /**
     * If found, returns markers, of type $key (GP, TSFE, page).
     *
     * Example of GP marker: ###GP:tx_displaycontroller_pi2|parameter###
     *
     * @param string $key Maybe, tsfe, page, gp
     * @param array $source Source of data to search in
     * @param string $content HTML code
     * @throws \Tesseract\Tesseract\Exception\Exception
     * @return string Transformed HTML code
     */
    protected function getExpressionMarkers($key, &$source, $content)
    {

        // Makes sure $expression has a value
        if (empty($key)) {
            throw new \Tesseract\Tesseract\Exception\Exception(
                    'No key given to getExpressionMarkers()',
                    1340714264
            );
        }

        // Defines empty array.
        $markers = array();

        // Tests if $expressions is found
        $pattern = '/#{3}(' . $key . ':)(.+)#{3}/isU';
        if (preg_match_all($pattern, $content, $matches)) {
            if (isset($matches[2])) {
                $numberOfMatches = count($matches[0]);
                for ($index = 0; $index < $numberOfMatches; $index++) {
                    $markers[$matches[0][$index]] = $this->getValueFromArray($source, $matches[2][$index]);
                }
            }
        }
        return $markers;
    }

    /**
     * Returns markers, of type global template variable.
     *
     * Global template variable can be ###TOTAL_RECORDS### ###SUBTOTAL_RECORDS###
     *
     * @param string $content HTML content
     * @return string Transformed HTML content
     */
    protected function getGlobalVariablesMarkers($content)
    {
        $markers = array();
        if (preg_match('/#{3}TOTAL_RECORDS#{3}/isU', $content)) {
            $markers['###TOTAL_RECORDS###'] = $this->structure['totalCount'];
        }
        if (preg_match('/#{3}SUBTOTAL_RECORDS#{3}/isU', $content)) {
            $markers['###SUBTOTAL_RECORDS###'] = $this->structure['count'];
        }

        if (preg_match('/#{3}RECORD_OFFSET#{3}/isU', $content)) {
            $page = $this->getCurrentPage();

            // Computes the record offset
            $recordOffset = ($page + 1) * $this->filter['limit']['max'];
            if ($recordOffset > $this->structure['totalCount']) {
                $recordOffset = $this->structure['totalCount'];
            }
            $markers['###RECORD_OFFSET###'] = $recordOffset;
        }

        if (preg_match('/#{3}START_AT#{3}/isU', $content)) {
            $page = $this->getCurrentPage();

            // Computes the record offset
            $recordOffset = ($page + 1) * $this->filter['limit']['max'];
            if ($recordOffset > $this->structure['totalCount']) {
                $recordOffset = $this->structure['totalCount'];
            }
            $markers['###START_AT###'] = (int)$recordOffset - (int)$this->structure['count'] + 1;
        }
        if (preg_match('/#{3}STOP_AT#{3}/isU', $content)) {
            $page = $this->getCurrentPage();

            // Computes the record offset
            $stop_at = ($page + 1) * $this->filter['limit']['max'];
            if ($stop_at > $this->structure['totalCount']) {
                $stop_at = $this->structure['totalCount'];
            }
            $markers['###STOP_AT###'] = $stop_at;
        }
        return $markers;
    }

    /**
     * Gets the current page for pagination from the controller.
     *
     * @return int
     */
    protected function getCurrentPage()
    {
        try {
            $page = $this->controller->getControllerArgumentValue('page');
        } catch (\Tesseract\Tesseract\Exception\Exception $e) {
            $page = 0;
        }
        return $page;
    }

    /**
     * Gets a value from inside a multi-dimensional array or object.
     *
     * NOTE: this code is largely inspired by \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getGlobal().
     *
     * @param mixed $source Array or object to look into
     * @param string $indices "Path" of indices inside the multi-dimensional array, of the form index1|index2|...
     * @throws \Tesseract\Tesseract\Exception\Exception
     * @return mixed Whatever value was found in the array
     */
    protected function getValueFromArray($source, $indices)
    {
        if (empty($indices)) {
            throw new \Tesseract\Tesseract\Exception\Exception('No key given for source');
        } else {
            $indexList = GeneralUtility::trimExplode('|', $indices);
            $value = $source;
            foreach ($indexList as $key) {
                if (is_object($value) && isset($value->$key)) {
                    $value = $value->$key;
                } elseif (is_array($value) && isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    $value = ''; // no value found
                }
            }
        }
        return $value;
    }

    /**
     * Handles the page browser.
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function processPageBrowser($content)
    {
        $pattern = '/#{3}PAGE_BROWSER#{3}|#{3}PAGEBROWSER#{3}/isU';
        if (preg_match($pattern, $content)) {

            // Fetches the configuration
            $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_pagebrowse_pi1.'];

            if ($conf != null) {

                // Adds limit to the query and calculates the number of pages.
                if ($this->filter['limit']['max'] != '' && $this->filter['limit']['max'] != '0') {
                    //$conf['extraQueryString'] .= '&' . $this->controller->getPrefixId() . '[max]=' . $this->filter['limit']['max'];
                    $conf['numberOfPages'] = ceil($this->structure['totalCount'] / $this->filter['limit']['max']);
                    $conf['items_per_page'] = $this->filter['limit']['max'];
                    $conf['total_items'] = $this->structure['totalCount'];
                    $conf['total_pages'] = $conf['numberOfPages']; // duplicated, because $conf['numberOfPages'] is protected
                } else {
                    $conf['numberOfPages'] = 1;
                }

                // Can be tx_displaycontroller_pi1 OR tx_displaycontroller_pi1
                $conf['pageParameterName'] = $this->controller->getPrefixId() . '|page';

                // Defines pagebrowse configuration options
                $values = array('templateFile', 'enableMorePages', 'enableLessPages', 'pagesBefore', 'pagesAfter');

                // Set Page Browser from Flexform config
                foreach ($values as $value) {
                    if ($this->conf['pagebrowse.'][$value] != '') {
                        $conf[$value] = $this->conf['pagebrowse.'][$value];
                    }
                }

                // Debug pagebrowse
                if (isset($GLOBALS['_GET']['debug']['pagebrowse']) && isset($GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'])) {
                    \TYPO3\CMS\Core\Utility\DebugUtility::debug($conf);
                }

                $this->localCObj->start(array(), '');
                $pageBrowser = $this->localCObj->cObjGetSingle('USER', $conf);
            } else {
                $pageBrowser = '<span style="color:red; font-weight: bold">Error: extension pagebrowse not loaded</span>';
            }

            // Replaces the marker by some HTML content
            $content = preg_replace($pattern, $pageBrowser, $content);
        }
        return $content;
    }

    /**
     * Processe the function PAGE_STATUS.
     *
     * @param string $content HTML code
     * @return string Transformed HTML code if the datastructure is *not* empty.
     */
    protected function checkPageStatus($content)
    {

        // Check for the PAGE_STATUS() pattern
        $pattern = '/PAGE_STATUS\((.+)\)/isU';
        if ($this->structure['count'] == 0 && preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $expression = $match[0];
                $_match = explode(',', $match[1]);

                // Avoid possible problem with extra whitespaces and unitialized values
                $_match = array_map('trim', $_match);
                $errorCode = $_match[0];
                $redirect = $replace = '';

                // If target is prefixed with pid:, then we try to build a typolink to the pid
                if (isset($_match[1])) {
                    if (substr($_match[1], 0, 4) == 'pid:') {

                        /** @var $contentObject ContentObjectRenderer */
                        $contentObject = $GLOBALS['TSFE']->cObj;
                        $config = array(
                                'returnLast' => 'url',
                                'parameter' => substr($_match[1], 4),
                                'useCacheHash' => 1,
                                'addQueryString' => 1
                        );
                        $redirect = $contentObject->typolink('', $config);

                        // Target is a link to which we just point
                    } elseif (isset($_match[1])) {
                        $redirect = $_match[1];
                    }
                }

                switch ($errorCode) {
                    case '301' : // 301 Moved Permanently
                        header('Location: ' . $redirect, true, 301);
                        die();
                        break;
                    case '302' : // 302 Found
                        header('Location: /' . $redirect, true, 302);
                        die();
                        break;
                    case '303' : // 303 See Other
                        header('Location: ' . $redirect, true, 303);
                        die();
                        break;
                    case '307' : // 307 Temporary Redirect
                        header('Location: ' . $redirect, true, 307);
                        die();
                        break;
                    case '404' : // 404
                        if (empty($redirect)) {
                            $GLOBALS['TSFE']->pageNotFoundAndExit();
                        } else {
                            header('HTTP/1.1 404 Not Found');
                            header('Location: ' . $redirect, true, 302);
                            die();
                        }
                        break;
                    case '500' :
                        header('HTTP/1.1 500 Internal Server Error');
                        if ($redirect != '') {
                            header('Location: ' . $redirect, true, 302);
                            die();
                        }
                        break;
                    default :
                        $replace = 'Sorry the status ' . $errorCode . ' is not handled yet.';
                }
                $content = str_replace($expression, $replace, $content);
            }
        }
        return $content;
    }

    /**
     * Pre processes the <!--IF(###MARKER### == '')-->, puts a '' around the marker, if needed.
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function preProcessIF($content)
    {

        // Preprocesses the <!--IF(###MARKER### == '')-->, puts a '' around the marker, if needed
        $pattern = '/<!-- *IF *\((.+)\) *-->/isU';
        $matches = array();
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            $searches = array();
            $replacements = array();
            foreach ($matches as $match) {
                $searches[] = $match[0];
                $expression = $match[0];
                // Split the expression along the ### marks (keeping these marks)
                $expressionParts = preg_split('/(###)/', $expression, -1, PREG_SPLIT_DELIM_CAPTURE);
                $parsedExpressionParts = array();
                $notNumericalParts = array();
                foreach ($expressionParts as $index => $part) {
                    $parsedExpressionParts[$index] = $part;
                    // If the part is not just ###, analyze it further
                    if ($part !== '###') {
                        $subMatches = array();
                        // Get the code of the marker itself
                        if (preg_match('/^([A-Z]+)(\..+)?$/', $part, $subMatches)) {
                            // If it is not a numerical marker, keep it in mind
                            if (!$this->isNumericalMarker($subMatches[1])) {
                                $notNumericalParts[] = $index;
                            }
                        }
                        $parsedExpressionParts[$index] = $part;
                    }
                }
                // For all non-numerical markers, modify wrapping ### marks to add single quotes
                foreach ($notNumericalParts as $index) {
                    $parsedExpressionParts[$index - 1] = '\'###';
                    $parsedExpressionParts[$index + 1] = '###\'';
                }
                // Reassemble the parsed expression and store it as replacement
                $parsedExpression = implode('', $parsedExpressionParts);
                $replacements[] = $parsedExpression;
            }
            // Replace all expressions inside the content
            $content = str_replace($searches, $replacements, $content);
        }
        return $content;
    }

    /**
     * Adds a LOOP marker of first level, if it does not exist and close according to the table name.
     *
     * E.g. <!--ENDLOOP--> becomes <!--ENDLOOP(tablename)-->
     * This additional information allows a better cutting out of the template.
     *
     * @param string $content HTML code
     * @return string $content Transformed HTML code
     */
    protected function processLOOP($content)
    {

        // Matches the LOOP(table) with offset
        $pattern = '/<!-- *LOOP *\((.+)\) *-->/isU';
        if (preg_match_all($pattern, $content, $loopMatches, PREG_OFFSET_CAPTURE)) {
            preg_match_all('/<!-- *ENDLOOP *-->/isU', $content, $endLoopMatches, PREG_OFFSET_CAPTURE);

            // Traverses the array. Begins at the end
            $numberOfMatches = count($loopMatches[0]);
            for ($index = ($numberOfMatches - 1); $index >= 0; $index--) {
                $table = $loopMatches[1][$index][0];
                $offset = $loopMatches[1][$index][1];

                // Loops around the ENDLOOP.
                // Checks the value offset. The first bigger is the good one. -> remembers the table name.
                for ($index2 = 0; $index2 < $numberOfMatches; $index2++) {
                    $_offset = $endLoopMatches[0][$index2][1];
                    if ($_offset > $offset && !isset($endLoopMatches[0][$index2][2])) {
                        $endLoopMatches[0][$index2][2] = $table;
                        break;
                    }
                } // end for ENDLOOP
            } // end for LOOP

            // Builds replacement array
            $patterns = array();
            $replacements = array();
            for ($index = 0; $index < $numberOfMatches; $index++) {
                $patterns[$index] = '/<!-- *ENDLOOP *-->/isU';
                $replacements[$index] = '<!--ENDLOOP(' . $endLoopMatches[0][$index][2] . ')-->';
            }
            // Replacement with limit 1
            $content = preg_replace($patterns, $replacements, $content, 1);
        }

        // Wraps if LOOP
        if (!preg_match('/<!-- *LOOP *\( *' . $this->structure['name'] . ' *\)/isU', $content, $matches)) {
            $content = '<!--LOOP(' . $this->structure['name'] . ')-->' . chr(10) . $content . chr(10) . '<!--ENDLOOP(' . $this->structure['name'] . ')-->';
        }
        return $content;
    }

    /**
     * Pre-processes the template function LIMIT, UPPERCASE, LOWERCASE, UPPERCASE_FIRST, COUNT.
     *
     * Makes them recognizable by wrapping them with !--### ###--
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function preProcessFUNCTIONS($content)
    {
        foreach ($this->functions as $function) {
            $pattern = '/' . $function . '\(.+\)|' . $function . ':.*\(.+\)/sU';
            if (preg_match_all($pattern, $content, $matches)) {
                // Avoids multiple replacement, which could lead to multiple replacement, which is bad
                $matches = array_unique($matches[0]);
                foreach ($matches as $match) {
                    $_match = $match;
                    if (strpos($match, 'PRINTF(') !== null) {
                        $_match = str_replace(',', '%%%,%%%', $match);
                    }
                    $_match = str_replace(' ', '', $_match);
                    $content = str_replace($match, '!--###' . $_match . '###--', $content);
                }
            }
        }
        return $content;
    }

    /**
     * Post processes the <!--IF(###MARKER### == '')-->.
     *
     * Evaluates the condition and replaces the content when necessary.
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function postProcessIF($content)
    {
        $pattern = '/(<!-- *IF *\( *(.+)\) *-->)(.+)(<!-- *ENDIF *-->)/isU';
        if (preg_match_all($pattern, $content, $matches)) {
            // count number of IF
            $numberOfElements = count($matches[0]);

            // Evaluates the condition
            for ($index = 0; $index < $numberOfElements; $index++) {
                $condition = $matches[2][$index];

                // Extracts the conditions
                $expressions = preg_split('/(&&|\|\|)/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);
                $expressions = array_map('trim', $expressions);
                $evaluation = '';

                // Builds the evaluation string, useful for replacing ' => \'
                for ($index2 = 0; $index2 < count($expressions); $index2 = $index2 + 2) {

                    $klammerBegin = $klammerEnd = $logicalOperator = '';
                    $expression = $expressions[$index2];

                    // Tests whether the expression begins with a "(" in this case removes it
                    if (substr($expression, 0) == '(') {
                        $expression = substr($expression, 0);
                        $klammerBegin = '(';
                    }

                    // Tests whether the expression begins with a ")" in this case removes it
                    if (substr($expression, -1) == ')') {
                        $expression = substr($expression, 0, -1);
                        $klammerEnd = ')';
                    }

                    // Tests whether an logical operator exists or not
                    if (isset($expressions[$index2 + 1])) {
                        $logicalOperator = $expressions[$index2 + 1];
                    }

                    $operands = preg_split('/(!=|==|<>|<=|>=| < | > )/', $expression, -1, PREG_SPLIT_DELIM_CAPTURE);

                    // Makes sure the $condition is valid
                    if (count($operands) == 3) {
                        $operand1 = $this->sanitizeOperand($operands[0]);
                        $operator = $operands[1];
                        $operand2 = $this->sanitizeOperand($operands[2]);
                        $evaluation .= $klammerBegin . $operand1 . $operator . $operand2 . $klammerEnd . ' ' . $logicalOperator . ' ';
                    } elseif (count($operands) == 1) {
                        $operand1 = $this->sanitizeOperand($operands[0]);
                        $evaluation .= $klammerBegin . $operand1 . $klammerEnd . ' ' . $logicalOperator . ' ';
                    }
                }

                if (eval('$result = ' . $evaluation . ';') === false) {
                    if ($this->debug) {
                        $this->controller->addMessage(
                                'templatedisplay',
                                sprintf('Error evaluating expression "%s"', $evaluation),
                                '',
                                FlashMessage::WARNING
                        );
                    }
                }

                $searchContent = $matches[0][$index];
                $replaceContent = $matches[3][$index];
                // Tests the result
                if ($result) {
                    // checks if $replaceContent contains a <!-- ELSE -->
                    if (preg_match('/(.+)(<!-- *ELSE *-->)(.+)/is', $replaceContent, $_matches)) {
                        $replaceContent = $_matches[1];
                    }
                    // else is not necessary, it would be equal to write $replaceContent = $replaceContent;
                } else {
                    // checks if $replaceContent contains a <!-- ELSE -->
                    if (preg_match('/(.+)(<!-- *ELSE *-->)(.+)/is', $replaceContent, $_matches)) {
                        $replaceContent = $_matches[3];
                    } else {
                        $replaceContent = '';
                    }
                }
                $content = str_replace($searchContent, trim($replaceContent), $content);
            }
        }
        return $content;
    }


    /**
     * Handles the function: LIMIT, UPPERCASE, LOWERCASE, UPPERCASE_FIRST.
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    function postProcessFUNCTIONS($content)
    {
        foreach ($this->functions as $function) {
            $pattern = '/!--###' . $function . '\((.*)\)###--|!--###' . $function . ':(.+)\((.*)\)###--/isU';

            if (preg_match_all($pattern, $content, $matches)) {
                $numberOfMatches = count($matches[0]);
                for ($index = 0; $index < $numberOfMatches; $index++) {
                    $_marker = $matches[0][$index];

                    if ($function == 'FUNCTION') {
                        $functionName = $matches[2][$index];
                        $_content = $matches[3][$index];
                        // %%% is used to delimit the comma separated parameters.
                        $parameters = explode('%%%,%%%', $_content);
                        #t3lib_utility_Debug::debug($parameters, '$parameters');

                        $_content = call_user_func_array($functionName, $parameters);
                        $content = str_replace($_marker, $_content, $content);

                    } else {
                        $_content = $matches[1][$index];
                        switch ($function) {
                            case 'LIMIT':
                                $_values = explode('%%%,%%%', $_content);
                                $limit = $_values[1];

                                $__content = $this->limit($_values[0], $limit);
                                $content = str_replace($_marker, $__content, $content);
                                break;
                            case 'PRINTF':
                                // explode data
                                $_values = explode('%%%,%%%', $_content);
                                $_values = array_map('trim', $_values);

                                // call function passing argument in form an array
                                $_content = call_user_func_array('sprintf', $_values);
                                $content = str_replace($_marker, $_content, $content);
                                break;
                            case 'COUNT':
                                $numberOfRecords = 0;
                                if ($this->structure['name'] == $_content) {
                                    $numberOfRecords = $this->structure['count'];
                                } else {
                                    if (isset($this->structure['records'][0])) {
                                        foreach ($this->structure['records'][0]['__substructure'] as $structure) {
                                            if ($structure['name'] == $_content) {
                                                $numberOfRecords = $structure['count'];
                                                break;
                                            }
                                        }
                                    }
                                }
                                $content = str_replace($_marker, $numberOfRecords, $content);
                                break;
                            case 'UPPERCASE':
                                $content = str_replace($_marker, strtoupper($_content), $content);
                                break;
                            case 'LOWERCASE':
                                $content = str_replace($_marker, strtolower($_content), $content);
                                break;
                            case 'UPPERCASE_FIRST':
                                $content = str_replace($_marker, ucfirst($_content), $content);
                                break;
                            case 'STRIPSLASHES':
                                $content = str_replace($_marker, stripslashes($_content), $content);
                                break;
                            case 'STR_REPLACE':
                                $_values = explode('%%%,%%%', $_content);
                                $_values = array_map('trim', $_values);
                                $search = substr($_values[0], 1, -1);
                                $replace = substr($_values[1], 1, -1);
                                $_content = $_values[2];
                                // special case
                                if ($search == '\n') {
                                    $search = array("\r\n", "\n", "\r");
                                    $replace = ' ';
                                }
                                $content = str_replace($_marker, str_replace($search, $replace, $_content), $content);
                                break;
                        } // endswitch
                    } // endelse
                } //
            }
        }
        return $content;
    }

    /**
     * Shortens a text according to the parameter $limit.
     *
     * @param string $text Input text
     * @param int $limit Words limit
     * @return string Shortened text
     */
    protected function limit($text, $limit)
    {
        $text = strip_tags($text, '<br><br/><br />');
        $limit = $limit + substr_count($text, '<br>') + substr_count($text, '<br/>') + substr_count($text, '<br />');
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        if (count($words) > $limit) {
            $text = substr($text, 0, $pos[$limit]) . ' ...';
        }
        return $text;
    }

    /**
     * Analyses the template code and build a structure of type array.
     *
     * This method is called recursively whenever a LOOP is found.
     *
     * Synopsis of the structure
     *
     * [table]        =>    (string) tableName
     * [template]    =>    (string) template code with markers
     * [content]    =>    (string) HTML code without <LOOP> marker (outer)
     * [emptyLoops]    =>    (string) Contains the value if loops is empty.
     * [loops]        =>    (array) Contains a templateStructure array [table], [template], [content], [emptyLoops], [loops]
     *
     * @param string $template Template code with markers
     * @return array $templateStructure Multidimensional array
     */
    protected function getTemplateStructure($template)
    {

        // Default value
        $templateStructure = array();

        if (preg_match_all('/<!-- *LOOP\((.+)\) *-->(.+)<!-- *ENDLOOP\(\1\) *-->/isU', $template, $matches,
                PREG_SET_ORDER)) {

            $numberOfMatches = count($matches);

            // Traverses the array to find out table, template, content
            for ($index = 0; $index < $numberOfMatches; $index++) {

                // Initialize variable name
                $_template = $matches[$index][0];
                $_table = $matches[$index][1];
                $_content = trim($matches[$index][2]);

                $templateStructure[$index] = array();
                $templateStructure[$index]['table'] = $_table;
                $templateStructure[$index]['template'] = $_template;
                $templateStructure[$index]['content'] = $_content;
                $templateStructure[$index]['emptyLoops'] = '';
                $templateStructure[$index]['loops'] = array();

                // Handles the case when the user has defined content to be substitued when no records are found
                if (preg_match('/<!-- *EMPTY *-->(.*)<!-- *ENDEMPTY *-->/isU', $_content, $_match,
                        PREG_OFFSET_CAPTURE)) {

                    // Exctracts the code between the beginning and the frist <EMPTY>
                    $offset = $_match[0][1];
                    $subPartCode = substr($_content, 0, $offset);

                    // Makes sure the subParCode does not contain LOOP (means EMPTY content does not belong to this LOOP)
                    if (!preg_match('/<!-- *LOOP/isU', $subPartCode)) {

                        $_emptyLoopsTemplate = $_match[0][0];
                        $_emptyLoops = $_match[1][0];

                        // Replaces final content
                        $templateStructure[$index]['content'] = trim(str_replace($_emptyLoopsTemplate, '',
                                $templateStructure[$index]['content']));
                        $templateStructure[$index]['emptyLoops'] = trim($_emptyLoops);
                    }

                }

                // Gets recursively the template structure
                $templateStructure[$index]['loops'] = $this->getTemplateStructure($_content);
            }
        }
        return $templateStructure;
    }

    /**
     * Looks up for a value in a sds.
     *
     * @param array $sds Standard data structure
     * @param int $index The position in the array
     * @param string $table The name of the table
     * @param string $cellName The name of the field. Can be either 'totalCount' or 'count'
     * @return int If no value is found return NULL
     */
    protected function getTotalValueFromStructure(&$sds, $index, $table, $cellName)
    {

        // Default value is NULL
        $value = 0;

        // TRUE, the best case, means the table is found at the first dimension of the sds
        if ($sds['name'] == $table) {
            if (!isset($sds[$cellName])) {
                $cellName = 'count';
            }
            $value = $sds[$cellName];
        } else {
            // Maybe the $sds contains subtables, have a look into it to find out the value.
            if (!empty($sds['records'][$index]['__substructure'])) {

                // Traverses all subSds and call it recursively
                foreach ($sds['records'][$index]['__substructure'] as $subSds) {
                    if ($subSds['name'] == $table) {
                        if (!isset($subSds[$cellName])) {
                            $cellName = 'count';
                        }
                        $value = $subSds[$cellName];
                        break;
                    }
                }
            }
        }
        return $value;
    }

    /**
     * Looks up for a value in a sds.
     *
     * @param array $sds Standard data structure
     * @param int $index The position in the array
     * @param string $table The name of the table
     * @param string $field The name of the field
     * @return string If no value is found, returns NULL
     */
    protected function getValueFromStructure(&$sds, $index, $table, $field)
    {

        // Default value is NULL
        $value = null;

        // TRUE, the best case, means the table is found at the first dimension of the sds
        if ($sds['name'] == $table) {
            if (isset($sds['records'][$index][$field])) {
                $value = $sds['records'][$index][$field];
            }
        } else {
            // Maybe the $sds contains subtables, have a look into it to find out the value.
            if (!empty($sds['records'][$index]['__substructure'])) {

                // Traverses all subSds and call it recursively
                foreach ($sds['records'][$index]['__substructure'] as $subSds) {
                    $value = $this->getValueFromStructure($subSds, 0, $table, $field);
                    if ($value != null) {
                        break;
                    }
                }
            }
        }
        return $value;
    }

    /**
     * Initializes language label and stores the lables for a possible further use.
     *
     * @param $sds $sds Standard data structure
     * @return void
     */
    protected function setLabelMarkers(&$sds)
    {
        if (!isset($this->labelMarkers[$sds['name']]) && !empty($sds['header'])) {

            // Defines as array
            $this->labelMarkers[$sds['name']] = array();
            foreach ($sds['header'] as $index => $labelArray) {
                $this->labelMarkers[$sds['name']]['###LABEL.' . $index . '###'] = $labelArray['label'];
            }
        }
    }

    /**
     * Returns an array that contains LABEL.
     *
     * @param string $name Corresponds to a table name.
     * @return array $markers
     */
    protected function getLabelMarkers($name)
    {
        $markers = array();

        if (isset($this->labelMarkers[$name])) {
            $markers = $this->labelMarkers[$name];
        }
        return $markers;
    }


    /**
     * Gets the subpart template and substitutes content (label or field).
     *
     * @param array $templateStructure
     * @param array $sds Standard data structure
     * @param array $pRecords
     * @param array $fieldMarkers
     * @param array $totalfieldMarkers
     * @return string HTML content
     */
    protected function getContent(
            $templateStructure,
            &$sds,
            $pRecords = array(),
            $fieldMarkers = array(),
            $totalfieldMarkers = array()
    ) {

        // Intializes the label (header part of sds).
        $this->setLabelMarkers($sds);

        // Computes a possible loop limit
        // By default $numberOfLoops = 100000
        // This value may be set to a different one (e.g. NUMBER_OF_LOOPS xx)
        $numberOfLoops = 100000;
        $tableNameTemplate = $templateStructure['table'];
        if (preg_match('/(.*) NUMBER_OF_LOOPS ([0-9]+)/is', $tableNameTemplate, $loops)) {
            if (is_int((int)$loops[1])) {
                $tableNameTemplate = trim($loops[1]);
                $numberOfLoops = $loops[2];
            }
        }

        // Resets temporary content
        $content = '';
        $this->counter['###COUNTER(' . $tableNameTemplate . ')###'] = 0;

        // Retrieves the fields from the templateCode that needs a substitution
        // By the way catch the table name and the field name for futher use. -> "()"
        preg_match_all('/#{3}(FIELD\..+)\.(.+)\.(.+)#{3}/isU', $templateStructure['content'], $markers, PREG_SET_ORDER);

        $numbersOfRecords = $sds['count'];
        for ($index = 0; $index < $numbersOfRecords && $index < $numberOfLoops; $index++) {

            // Increments a counter
            $this->counter['###COUNTER(' . $tableNameTemplate . ')###'] = $index;

            $_content = $templateStructure['content'];

            // Initializes content object.
            $this->localCObj->start($sds['records'][$index]);

            // Loads a register
            foreach ($pRecords as $key => $value) {
                if (strpos($key, 'sds:') === false) {
                    $registerKey = 'parent.' . $key;
                    $GLOBALS['TSFE']->register[$registerKey] = $value;
                }
            }

            // TRAVERSES MARKERS
            foreach ($markers as $marker) {
                $markerName = $marker[0];
                $key = $marker[1];
                $table = $marker[2];
                $field = $marker[3];
                $value = $this->getValueFromStructure($sds, $index, $table, $field);

                #if ($value !== NULL) {
                $fieldMarkers[$markerName] = $this->getValue($this->datasourceFields[$key], $value, $sds);
                #}
            }

            // Returns the total_records
            preg_match_all('/#{3}(TOTAL_RECORDS)\((.+)\)#{3}|#{3}(SUBTOTAL_RECORDS)\((.+)\)#{3}/isU',
                    $templateStructure['content'], $totalRecordsMarkers, PREG_SET_ORDER);
            foreach ($totalRecordsMarkers as $totalRecordsMarker) {
                $totalMarkerName = $totalRecordsMarker[0];
                if ($totalRecordsMarker[1] == 'SUBTOTAL_RECORDS') {
                    $cellName = 'count';
                } else {
                    $cellName = 'totalCount';
                }
                $tablename = $totalRecordsMarker[2];
                $totalfieldMarkers[$totalMarkerName] = $this->getTotalValueFromStructure($sds, $index, $tablename,
                        $cellName);
            }

            // Means there is a LOOP in a LOOP
            if (!empty($templateStructure['loops'])) {

                // TRAVERSES (SUB) TEMPLATE STRUCTURE
                foreach ($templateStructure['loops'] as &$subTemplateStructure) {

                    $foundSubSds = array();
                    $sdsSubtables = $sds['records'][$index]['__substructure'];
                    if (!empty($sdsSubtables) && isset($sdsSubtables[$subTemplateStructure['table']])) {
                        $foundSubSds = $sdsSubtables[$subTemplateStructure['table']];
                    }

                    // Transform if $foundSubSds is valid subsds
                    if (!empty($foundSubSds)) {
                        $__content = $this->getContent($subTemplateStructure, $foundSubSds, $sds['records'][$index],
                                $fieldMarkers, $totalfieldMarkers);
                        $_content = str_replace($subTemplateStructure['template'], $__content, $_content);
                    } else {

                        // Handles the case when there is no record -> replace with other content
                        $fieldMarkers = array_merge($fieldMarkers, $totalfieldMarkers,
                                $this->getLabelMarkers($sds['name']), array('###COUNTER###' => '0'), $this->counter);
                        $__content = $this->getEmptyValue($subTemplateStructure, $fieldMarkers);
                        $_content = str_replace($subTemplateStructure['template'], $__content, $_content);
                    } // end else
                } // end foreach template structure
            } // end if

            // Merges array(FIELD, LABEL, COUNTER)
            $this->fieldMarkers = array_merge($fieldMarkers, $totalfieldMarkers, $this->getLabelMarkers($sds['name']),
                    array('###COUNTER###' => $index), $this->counter);

            // Substitutes content
            $content .= $this->templateService->substituteMarkerArray($_content, $this->fieldMarkers);

        } // end for (records)

        return $content;
    }

    /**
     *
     * @param array $templateStructure
     * @param array $markers
     * @return string
     */
    protected function getEmptyValue(&$templateStructure, $markers = array())
    {
        $content = '';
        if ($templateStructure['emptyLoops'] != '') {
            $content = $templateStructure['emptyLoops'];
        } else {
            // Checks the configuration
            $this->conf += array('parseEmptyLoops' => 0);
            $parseEmptyLoops = $this->conf['parseEmptyLoops'];
            if ((boolean)$parseEmptyLoops) {
                $content = $this->templateService->substituteMarkerArray($templateStructure['content'], $markers);

                // Removes remaining ###FIELD###
                $content = preg_replace('/#{3}FIELD.+#{3}/isU', '', $content);
            }
        }
        return $content;
    }

    /**
     * Replaces the marker ###OBJECT.userDefined###.
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function processOBJECTS($content)
    {
        $fieldMarkers = array();
        foreach ($this->datasourceObjects as $key => $datasource) {
            $fieldMarkers['###' . $key . '###'] = $this->getValue($datasource);
        }

        return $this->templateService->substituteMarkerArray($content, $fieldMarkers);
    }

    /**
     * Formats the $value given as input according to the $key.
     *
     * The variable $key will tell the type of $value. Then format the $value whenever there is TypoScript configuration.
     *
     * @param array $datasource Can be $this->datasourceObjects or $this->datasourceFields
     * @param string $value Makes sense for method getContent()
     * @param array $sds The datastructure
     * @return string The rendered value
     */
    protected function getValue(&$datasource, $value = '', &$sds = array())
    {
        $output = '';

        // Checks if the page title needs to be changed
        $this->setPageTitle($datasource['configuration']);

        // Get default rendering configuration for the given type
        $tsIndex = $datasource['type'] . '.';
        $configuration = isset($this->conf['defaultRendering.'][$tsIndex]) ? $this->conf['defaultRendering.'][$tsIndex] : array();
        // Merge base configuration with local configuration
        if (is_array($datasource['configuration'])) {
            ArrayUtility::mergeRecursiveWithOverrule($configuration, $datasource['configuration']);
        }
        // Render element based on type
        try {
            switch ($datasource['type']) {
                case 'raw':
                    $output = $value;
                    break;
                case 'text':
                    // Override configuration as needed
                    if (!isset($configuration['value'])) {
                        $configuration['value'] = $value;
                    }

                    $output = $this->localCObj->cObjGetSingle('TEXT', $configuration);
                    break;
                case 'richtext':
                    // Override configuration as needed
                    if (!isset($configuration['value'])) {
                        $configuration['value'] = $value;
                    }

                    $output = $this->localCObj->cObjGetSingle('TEXT', $configuration);
                    break;
                case 'image':
                    // Override configuration as needed
                    if (!isset($configuration['file'])) {
                        $configuration['file'] = $value;
                    }

                    // Sets the alt attribute
                    if (!isset($configuration['altText'])) {
                        // Gets the file name
                        $configuration['altText'] = $this->getFileName($configuration['file']);
                    } else {
                        $configuration['altText'] = $this->localCObj->stdWrap($configuration['altText'],
                                $configuration['altText.']);
                    }

                    // Sets the title attribute
                    if (!isset($configuration['titleText'])) {
                        // Gets the file name
                        $configuration['titleText'] = $this->getFileName($configuration['file']);
                    } else {
                        $configuration['titleText'] = $this->localCObj->stdWrap($configuration['titleText'],
                                $configuration['titleText.']);
                    }

                    $output = $this->localCObj->cObjGetSingle('IMAGE', $configuration);
                    if (empty($output)) {
                        $this->controller->addMessage(
                                $this->extKey,
                                'Image not found for marker: ' . $datasource['marker'],
                                '',
                                FlashMessage::WARNING,
                                $configuration
                        );
                    }
                    break;
                case 'imageResource':
                    // Override configuration as needed
                    if (!isset($configuration['file'])) {
                        $configuration['file'] = $value;
                    }
                    $output = $this->localCObj->cObjGetSingle('IMG_RESOURCE', $configuration);
                    break;
                case 'media':
                    // Override configuration as needed
                    if (!isset($configuration['file'])) {
                        $configuration['file'] = $value;
                    }
                    // Make sure to have a type (default to video)
                    if (!isset($configuration['type'])) {
                        $configuration['type'] = 'video';
                    }
                    // Make sure to have a render type (default to auto)
                    if (!isset($configuration['renderType'])) {
                        $configuration['renderType'] = 'auto';
                    }
                    $output = $this->localCObj->cObjGetSingle('MEDIA', $configuration);
                    break;
                case 'files':
                    // NOTE: there's no default configuration that would make sense in this case
                    $output = $this->localCObj->cObjGetSingle('FILES', $configuration);
                    break;
                case 'linkToDetail':
                    // Override configuration as needed
                    if (!isset($configuration['useCacheHash'])) {
                        $configuration['useCacheHash'] = 1;
                    }
                    if (!isset($configuration['returnLast'])) {
                        $configuration['returnLast'] = 'url';
                    }

                    $additionalParams = '&' . $this->controller->getPrefixId() . '[table]=' . $sds['trueName'] . '&' . $this->controller->getPrefixId() . '[showUid]=' . $value;
                    $configuration['additionalParams'] = $additionalParams . $this->localCObj->stdWrap($configuration['additionalParams'],
                                    $configuration['additionalParams.']);

                    // Generates the link
                    $output = $this->localCObj->typolink('', $configuration);
                    break;
                case 'linkToPage':
                    // Override configuration as needed
                    if (!isset($configuration['useCacheHash'])) {
                        $configuration['useCacheHash'] = 1;
                    }

                    // Defines parameter
                    if (!isset($configuration['parameter'])) {
                        $configuration['parameter'] = $value;
                    }

                    if (!isset($configuration['returnLast'])) {
                        $configuration['returnLast'] = 'url';
                    }
                    $configuration['additionalParams'] = $this->localCObj->stdWrap($configuration['additionalParams'],
                            $configuration['additionalParams.']);

                    // Generates the link
                    $output = $this->localCObj->typolink('', $configuration);
                    break;
                case 'linkToFile':
                    // Override configuration as needed
                    if (!isset($configuration['useCacheHash'])) {
                        $configuration['useCacheHash'] = 1;
                    }

                    if (!isset($configuration['returnLast'])) {
                        $configuration['returnLast'] = 'url';
                    }

                    if (!isset($configuration['parameter'])) {
                        $configuration['parameter'] = $value;
                    }

                    // Replaces white spaces in filename
                    $configuration['parameter'] = str_replace(' ', '%20', $configuration['parameter']);

                    // Generates the link
                    $output = $this->localCObj->typolink('', $configuration);
                    break;
                case 'email':
                    // Override configuration as needed
                    if (!isset($configuration['parameter'])) {
                        $configuration['parameter'] = $value;
                    }
                    // Generates the email
                    $output = $this->localCObj->typolink('', $configuration);
                    break;
                case 'records':
                    // Override configuration as needed
                    if (!isset($configuration['source'])) {
                        $configuration['source'] = $value;
                    }
                    $output = $this->localCObj->cObjGetSingle('RECORDS', $configuration);
                    break;
                case 'user':
                    // Override configuration as needed
                    if (!isset($configuration['parameter'])) {
                        $configuration['parameter'] = $value;
                    }
                    // Additional parameters may have stdWrap properties
                    if (isset($configuration['additionalParameters.'])) {
                        foreach ($configuration['additionalParameters.'] as $key => $propertyValue) {
                            $lastCharacter = strrchr($key, '.');
                            // If there's a dot and the last character is a dot, assume stdWrap is used
                            if (strpos($propertyValue, '.') !== false && !empty($lastCharacter)) {
                                $keyWithoutDot = substr($key, 0, -1);
                                $baseValue = (isset($configuration['additionalParameters.'][$keyWithoutDot])) ? $configuration['additionalParameters.'][$keyWithoutDot] : '';
                                // Evaluate stdWrap and replace actual value
                                $configuration['additionalParameters.'][$keyWithoutDot] = $this->localCObj->stdWrap(
                                        $baseValue,
                                        $configuration['additionalParameters.'][$key]
                                );
                                // Remove stdWrap configuration
                                unset($configuration['additionalParameters.'][$key]);
                            }
                        }
                    }
                    // Generates the user content
                    $output = $this->localCObj->cObjGetSingle('USER', $configuration);
                    break;
                default:
                    // Not a standard type, check if it matches a custom one
                    if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['types'][$datasource['type']]['class'])) {
                        $class = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['types'][$datasource['type']]['class']);
                        if ($class instanceof CustomTypeInterface) {
                            $output = $class->render($value, $configuration, $this);
                        } else {
                            $this->controller->addMessage(
                                    $this->extKey,
                                    'Invalid custom class "' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['types'][$datasource['type']]['class'] . '" provided for marker: ' . $datasource['marker'],
                                    '',
                                    FlashMessage::ERROR
                            );
                        }
                    } else {
                        $this->controller->addMessage(
                                $this->extKey,
                                'Unknow object type "' . $datasource['type'] . '" for marker: ' . $datasource['marker'],
                                '',
                                FlashMessage::ERROR
                        );
                    }
            } // end switch
        } catch (\Exception $e) {
            $this->controller->addMessage(
                    $this->extKey,
                    sprintf(
                            'Marker ###%s### could not be rendered properly. Following exception occurred: %s (%d)',
                            $datasource['marker'],
                            $e->getMessage(),
                            $e->getCode()
                    ),
                    'Rendering error',
                    FlashMessage::WARNING,
                    $configuration
            );
        }

        return $output;
    }

    /**
     * Extracts the filename of a path.
     *
     * @param string $filepath The path to parse
     * @return string The filename
     */
    protected function getFileName($filepath)
    {
        $filename = '';
        $fileInfo = GeneralUtility::split_fileref($filepath);
        if (isset($fileInfo['filebody'])) {
            $filename = $fileInfo['filebody'];
        }
        return $filename;
    }

    /**
     * If found, returns markers, of type LLL.
     *
     * Example of marker: ###LLL:EXT:myextension/localang.xml:myLabel###
     *
     * @param string $content HTML code
     * @return string Transformed HTML code
     */
    protected function getLLLMarkers($content)
    {
        $markers = array();
        if (preg_match_all('/#{3}(LLL:.+)#{3}/isU', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $marker) {
                $value = $GLOBALS['TSFE']->sL($marker[1]);
                if ($value != '') {
                    $markers[$marker[0]] = $value;
                }
            }
        }
        return $markers;
    }

    /**
     * Checks whether a given marker represents a numerical value or not.
     *
     * This is based on an internally defined list of markers.
     *
     * @param $marker
     * @return bool
     */
    protected function isNumericalMarker($marker)
    {
        $isNumericalMarker = false;
        // As a first, quick try, match in the array of numerical markers
        if (in_array($marker, $this->numericalMarkers)) {
            $isNumericalMarker = true;

            // If it didn't match, try a finer matching, as markers may have modifiers (e.g. TOTAL_RECORDS(foo))
        } else {
            foreach ($this->numericalMarkers as $numericalMarker) {
                if (strpos($marker, $numericalMarker) !== false) {
                    $isNumericalMarker = true;
                    break;
                }
            }
        }
        return $isNumericalMarker;
    }

    /**
     * Displays in the frontend or in the devlog some debug output
     *
     * @param array $markers
     * @param array $templateStructure
     * @return void
     */
    protected function performDebug($markers, $templateStructure)
    {
        if ($this->debug) {
            $this->controller->addMessage(
                    $this->extKey,
                    'Markers and their replacement values',
                    '',
                    FlashMessage::INFO,
                    $markers
            );
            $this->controller->addMessage(
                    $this->extKey,
                    'Template structure',
                    '',
                    FlashMessage::INFO,
                    $templateStructure
            );
            $this->controller->addMessage(
                    $this->extKey,
                    'Received data structure',
                    '',
                    FlashMessage::INFO,
                    $this->structure
            );
        }
    }

    /**
     * Returns the local instance of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer.
     *
     * @return ContentObjectRenderer
     */
    public function getLocalCObj()
    {
        return $this->localCObj;
    }
}
