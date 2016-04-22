<?php

/*********************************************************************
* Extension configuration file for ext "templatedisplay".
*
* Generated by ext 22-04-2016 13:26 UTC
*
* https://github.com/t3elmar/Ext
*********************************************************************/

$EM_CONF[$_EXTKEY] = array (
  'title' => 'HTML-based Data Consumer - Tesseract project',
  'description' => 'Use HTML-based templates to display any kind of data returned by a Data Provider, thanks a to user-friendly mapping interface. More info on http://www.typo3-tesseract.com',
  'category' => 'fe',
  'author' => 'Francois Suter (Cobweb) / Fabien Udriot',
  'author_email' => 'typo3@cobweb.ch',
  'state' => 'stable',
  'uploadfolder' => 0,
  'createDirs' => '',
  'clearCacheOnLoad' => 0,
  'author_company' => '',
  'version' => '2.0.0',
  'constraints' => 
  array (
    'depends' => 
    array (
      'typo3' => '7.6.0-7.99.99',
      'tesseract' => '2.0.0-0.0.0',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  '_md5_values_when_last_written' => 'a:83:{s:9:"ChangeLog";s:4:"fced";s:11:"LICENSE.txt";s:4:"6404";s:10:"README.txt";s:4:"db38";s:13:"composer.json";s:4:"c5bf";s:12:"ext_icon.png";s:4:"aaa6";s:17:"ext_localconf.php";s:4:"8b82";s:14:"ext_tables.php";s:4:"b5f5";s:14:"ext_tables.sql";s:4:"45d3";s:28:"Classes/Ajax/AjaxHandler.php";s:4:"2038";s:34:"Classes/Component/DataConsumer.php";s:4:"8b09";s:45:"Classes/RenderingType/CustomTypeInterface.php";s:4:"347c";s:35:"Classes/RenderingType/PhoneType.php";s:4:"e5c2";s:29:"Classes/Sample/SampleHook.php";s:4:"6801";s:39:"Classes/Service/SoftReferenceParser.php";s:4:"8244";s:41:"Classes/UserFunction/CustomFormEngine.php";s:4:"c091";s:49:"Configuration/TCA/tx_templatedisplay_displays.php";s:4:"8bab";s:34:"Configuration/TypoScript/setup.txt";s:4:"d038";s:26:"Documentation/Includes.txt";s:4:"c83c";s:23:"Documentation/Index.rst";s:4:"746e";s:26:"Documentation/Settings.yml";s:4:"8d8c";s:25:"Documentation/Targets.rst";s:4:"cc7b";s:37:"Documentation/Configuration/Index.rst";s:4:"0d20";s:54:"Documentation/Configuration/AvailableMarkers/Index.rst";s:4:"5e3f";s:50:"Documentation/Configuration/ElementTypes/Index.rst";s:4:"3bef";s:50:"Documentation/Configuration/HtmlTemplate/Index.rst";s:4:"4c98";s:34:"Documentation/Developers/Index.rst";s:4:"18df";s:46:"Documentation/Developers/CustomTypes/Index.rst";s:4:"64fe";s:40:"Documentation/Developers/Hooks/Index.rst";s:4:"a45b";s:44:"Documentation/Images/ElementTypeSelector.png";s:4:"c0e8";s:41:"Documentation/Images/MappingIterfance.png";s:4:"89e2";s:42:"Documentation/Images/MappingTypoScript.png";s:4:"f3db";s:36:"Documentation/Installation/Index.rst";s:4:"4daf";s:36:"Documentation/Introduction/Index.rst";s:4:"5b73";s:35:"Documentation/KnownIssues/Index.rst";s:4:"209e";s:47:"Documentation/TyposcriptConfiguration/Index.rst";s:4:"63a2";s:64:"Documentation/TyposcriptConfiguration/DefaultRendering/Index.rst";s:4:"fc0a";s:61:"Documentation/TyposcriptConfiguration/OtherExamples/Index.rst";s:4:"c4c5";s:57:"Documentation/TyposcriptConfiguration/Reference/Index.rst";s:4:"9a60";s:70:"Resources/Private/Language/locallang_csh_txtemplatedisplaydisplays.xlf";s:4:"abfc";s:43:"Resources/Private/Language/locallang_db.xlf";s:4:"3be7";s:39:"Resources/Private/Snippets/snippets.xml";s:4:"af40";s:48:"Resources/Private/Templates/templatedisplay.html";s:4:"5a59";s:53:"Resources/Private/Templates/templatedisplay_html.html";s:4:"16d5";s:51:"Resources/Private/Templates/FormEngine/Mapping.html";s:4:"0aaf";s:51:"Resources/Public/Icons/AddTemplateDisplayWizard.png";s:4:"3cbc";s:42:"Resources/Public/Icons/TemplateDisplay.png";s:4:"3dbb";s:41:"Resources/Public/JavaScript/formatJson.js";s:4:"15f6";s:46:"Resources/Public/JavaScript/templatedisplay.js";s:4:"f56a";s:43:"Resources/Public/JavaScript/Library/LICENSE";s:4:"cf8e";s:48:"Resources/Public/JavaScript/Library/prototype.js";s:4:"d7fa";s:33:"Resources/Public/Styles/Patch.css";s:4:"973c";s:43:"Resources/Public/Styles/templatedisplay.css";s:4:"3d4d";s:34:"Resources/Public/images/accept.png";s:4:"036a";s:33:"Resources/Public/images/email.png";s:4:"e7f6";s:33:"Resources/Public/images/empty.png";s:4:"81a2";s:33:"Resources/Public/images/error.png";s:4:"c847";s:39:"Resources/Public/images/exclamation.png";s:4:"1ee9";s:33:"Resources/Public/images/files.png";s:4:"596d";s:33:"Resources/Public/images/image.png";s:4:"c2fe";s:41:"Resources/Public/images/imageResource.png";s:4:"d928";s:32:"Resources/Public/images/link.png";s:4:"49be";s:40:"Resources/Public/images/linkToDetail.png";s:4:"49be";s:38:"Resources/Public/images/linkToFile.png";s:4:"49be";s:38:"Resources/Public/images/linkToPage.png";s:4:"49be";s:35:"Resources/Public/images/loading.gif";s:4:"9c92";s:47:"Resources/Public/images/mappings_screenshot.png";s:4:"b9fb";s:33:"Resources/Public/images/media.png";s:4:"5ad1";s:41:"Resources/Public/images/missing_image.png";s:4:"279d";s:38:"Resources/Public/images/paintbrush.png";s:4:"247e";s:34:"Resources/Public/images/pencil.png";s:4:"a34e";s:31:"Resources/Public/images/raw.png";s:4:"dfcf";s:35:"Resources/Public/images/records.png";s:4:"655c";s:36:"Resources/Public/images/richtext.png";s:4:"97d0";s:36:"Resources/Public/images/tag_blue.png";s:4:"0824";s:37:"Resources/Public/images/tag_green.png";s:4:"8205";s:38:"Resources/Public/images/tag_orange.png";s:4:"97ce";s:36:"Resources/Public/images/tag_pink.png";s:4:"30f8";s:38:"Resources/Public/images/tag_purple.png";s:4:"af7c";s:35:"Resources/Public/images/tag_red.png";s:4:"5d63";s:38:"Resources/Public/images/tag_yellow.png";s:4:"c8a1";s:32:"Resources/Public/images/text.png";s:4:"2039";s:35:"Resources/Public/images/unknown.png";s:4:"b6ee";s:32:"Resources/Public/images/user.png";s:4:"3d3c";}',
  'user' => 'francois',
  'comment' => 'Verified compatibility with TYPO3 CMS 7; refactored using namespaces.',
);

?>