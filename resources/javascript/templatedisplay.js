/***************************************************************
 *
 *  javascript functions regarding the templatedisplay extension
 *  relies on the javascript library "prototype"
 *
 *
 *  Copyright notice
 *
 *  (c) 2006-2008	Benjamin Mack <www.xnos.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 t3lib/ library provided by
 *  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
 *
 *  Released under GNU/GPL (see license file in tslib/)
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  This copyright notice MUST APPEAR in all copies of this script
 *
 * $Id: $
 ***************************************************************/

/**
 *
 * @author	Fabien Udriot
 */

var templatedisplay;

if (Prototype) {
    var Templatedisplay = Class.create({
		
        /**
         * Stores the datasource
         */
        records: '',

        /**
		 * Registers event listener and executes on DOM ready
		 */
        initialize: function() {
			
            //Event.observe(document, 'dom:loaded', function(){
            Event.observe(document, 'dom:loaded', function(){
                $$('#templatedisplay_templateBox a').each(function(element){
                    templatedisplay.initializeImages(element);
                    Event.observe(element, 'click', templatedisplay.selectField);
                });
                Event.observe($('templatedisplay_showJson'),'click',templatedisplay.toggleJsonBoxVisibility);
                Event.observe($('templatedisplay_editJson'),'click',templatedisplay.toggleJsonBoxReadonly);
                Event.observe($('templatedisplay_saveConfigurationBt'),'click',templatedisplay.saveConfiguration);
            });
			
        },
		
        /**
         * Fetch the form informations and save them into the datasource.
         */
        saveConfiguration: function(){

            // Make sure the select drop down contains something... True when auto detection has worked correctly or the user has set manually a field.
            if($('templatedisplay_fields').value != ''){
				// Cosmetic changes
				$('loadingBox').removeClassName('templatedisplay_hidden');

                var records = new Array();
				
                // Try parsing the existing datasource
                try{
                    if($('templatedisplay_json').value != ''){
                        records = $('templatedisplay_json').value.evalJSON(true);
                    }
                }
                catch(error){
                    alert('JSON transformation has failed!\n\n' + error)
                    return;
                }
				
                // Get the formular value
                var offset = '';
                var content = $('templatedisplay_fields').value.split('.');
                var type = $('templatedisplay_type').value;
                var configuration = $('templatedisplay_configuration').value;
                var newRecord = '{"table": "'+ content[0] +'", "field": "' + content[1] + '", "type": "' + type + '", "configuration": "' + protectJsonString(configuration) + '"}'
                newRecord = newRecord.evalJSON(true);
				
                // Make sure the newRecord does not exist in the datasource. If yes, remember the offset of the record for further use.
                $(records).each(function(record, index){
                    if(record.table == newRecord.table && record.field == newRecord.field){
                        offset = index;
                    }
                });
				
                // True, when this is a new record => new position in the datasource
                if(typeof(offset) == 'string'){
                    offset = records.length;
                }
                records[offset] = newRecord;
				
                // Reinject the JSON in the textarea
                //formatJson is a method from formatJson
                $('templatedisplay_json').update(formatJson(records));
				
                // Sends the content in an Ajax request
                new Ajax.Request("ajax.php", {
                    method: "post",
                    parameters: {
                        "ajaxID": "templatedisplay::saveConfiguration",
                        "uid" : tx_templatedisplay_uid,
                        "mappings" : $('templatedisplay_json').value
                    },
                    onComplete: function(xhr) {
                        if(xhr.responseText == 1){
                            // Change the accept icon and the type icon
							var image1 = $$('img[src="' + infomodule_path + 'pencil.png"]')[0];
							var image2 = image1.nextSibling;
                            image1.src = infomodule_path + 'accept.png';
                            image2.src = infomodule_path + type + '.png';
                            
							$('templatedisplay_typeBox').addClassName('templatedisplay_hidden');
							$('templatedisplay_configuationBox').addClassName('templatedisplay_hidden');
							$('templatedisplay_configuration').value = '';
							$('templatedisplay_fields').value = '';
							$('loadingBox').addClassName('templatedisplay_hidden');
                        }
						
                    }.bind(this),
                    onT3Error: function(xhr) {
						alert(xhr);
                    }.bind(this)
                });
            }
			
        },
		
        toggleJsonBoxVisibility: function(){
            //templatedisplay_hidden
            if($('templatedisplay_json').className == 'templatedisplay_hidden'){
                $('templatedisplay_json').className = '';
                $('templatedisplay_editJson').className = '';
                $('templatedisplay_labelEditJson').className = '';
            }
            else{
                $('templatedisplay_json').className = 'templatedisplay_hidden';
                $('templatedisplay_editJson').className = 'templatedisplay_hidden';
                $('templatedisplay_labelEditJson').className = 'templatedisplay_hidden';
            }
        },
		
        toggleJsonBoxReadonly: function(){
            if($('templatedisplay_json').getAttribute('readonly') == 'readonly'){
                $('templatedisplay_json').removeAttribute('readonly');
            }
            else{
                $('templatedisplay_json').setAttribute('readonly','readonly');
            }
        },

        /**
         * Define the images above the clickable markers. Can be exclamation.png or accept.png
         */
        initializeImages: function(element){
            // Extract the field name
            var field = element.innerHTML.replace(/#{3}FIELD\.([0-9a-zA-Z\_\-\.]+)#{3}/g,'$1');
			
            // Extract the table name's field
            var table = '';
			
            // Get a reference of the first image. (accept.png || exclamation.png)
            var image = $(element.nextSibling)
				
            // Add a little mark in order to be able to split the content in the right place
            image.src = '';
            var content = $$('#templatedisplay_templateBox')[0].innerHTML.split('src=""');
            content = content[0].split('###LOOP.');
            if(typeof(content[content.length - 1] == 'string')){
                content = content[content.length - 1].split(/#{3}/);
                table = content[0];
            }
			
            // True, when no JSON information is available -> put an empty icon
            if($('templatedisplay_json').value == ''){
                image.src = infomodule_path + 'exclamation.png';
                return;
            }
			
            // Fetch the records and store them for performance
            if(templatedisplay.records == ''){
                try{
                    templatedisplay.records = $('templatedisplay_json').value.evalJSON(true);
                }
                catch(error){
                    alert('JSON transformation has failed!\n You should check the datasource \n' + error)
                    return;
                }
            }
			
            // Make sure the newRecord does not exist in the datasource. If yes, remember the offset of the record for further use.
            var type = '';
            $(templatedisplay.records).each(function(record, index){
                if(record.table == table && record.field == field){
                    type = record.type;
                }
            });
            // Puts the right icon wheter a marker is defined or not
            if(type != ''){
                image.src = infomodule_path + 'accept.png';
				// Puts an other icon according to the type of the link
				$(image.nextSibling).src = infomodule_path + type + '.png';
            }
            else{
                image.src = infomodule_path + 'exclamation.png';
            }
        },
		
        /**
         * Try to guess an association between a field and a marker
         */
        selectField: function(){
			// Resets the local datasource
			templatedisplay.records = '';
			
            // Cosmetic: add an editing icon above the marker
            $$('#templatedisplay_templateBox a').each(function(element){
                templatedisplay.initializeImages(element);
            });
            $(this).next().src = infomodule_path + 'pencil.png';
			
            // Extract the field name
            var field = this.innerHTML.replace(/#{3}FIELD\.([0-9a-zA-Z\_\-\.]+)#{3}/g,'$1');
			
            // Extract the table name's field
            var content = $$('#templatedisplay_templateBox')[0].innerHTML.split('templatedisplay/resources/images/pencil.png');
            content = content[0].split('###LOOP.');
            var table = '';
            if(typeof(content[content.length - 1] == 'string')){
                content = content[content.length - 1].split(/#{3}/);
                table = content[0];
            }
			
            // Select the right entry in the select drop down
            if(table != '' && field != ''){
                $$('#templatedisplay_fields')[0].value = table + '.' + field;
            }
            else{
                $$('#templatedisplay_fields')[0].value = '';
            }
			
            // Show the other boxes that was hidden
            $('templatedisplay_fields').disabled = "";
            $('templatedisplay_typeBox').removeClassName('templatedisplay_hidden');
            $('templatedisplay_configuationBox').removeClassName('templatedisplay_hidden');
			
            // Inject the value in the field
            var currentRecord = '';
            records = $('templatedisplay_json').value.evalJSON(true);

			// Tries to find out which field has been clicked
            $(records).each(function(record, index){
                if(record.table == table && record.field == field){
                    currentRecord = record;
                }
            });
			
			// currentRecord is a reference to the ###FIELD.xxx###
            if(typeof(currentRecord) == 'object'){
                $('templatedisplay_type').value = currentRecord.type;
                $('templatedisplay_configuration').value = currentRecord.configuration;
            }
			// means the field has not been found for some reasons
            else{
                $('templatedisplay_type').value = 'text';
                $('templatedisplay_configuration').value = '';
            }
        }

    });

    // Initialize the object
    templatedisplay = new Templatedisplay();
	
}
else{
    alert('Problem loading templatedisplay library. Check if Prototype is loaded')
}


