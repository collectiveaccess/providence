/* ----------------------------------------------------------------------
 * js/ca/ca.quickaddform.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
 
var caUI = caUI || {};

(function ($) {
	caUI.initQuickAddFormHandler = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			formID: null,
			formErrorsPanelID: null,
			formTypeSelectID: null,
			
			fileUploadUrl: null,
			saveUrl: null,
			
			headerText: "QuickAdd",
			saveText: "Saved record: %1",
			
			_files: {}
		}, options);
		
		// Grab files on change
		jQuery("#" + that.formID).on('change', 'input[type=file]', function(e) { 
			that._files[jQuery(e.target).prop('name')] = e.target.files; 
		});
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.save = function(e) {
			// Force CKEditor text into form elements where we can grab it
			jQuery.each(CKEDITOR.instances, function(k, instance) {
				instance.updateElement();
			});
			
			var formData = jQuery("#" + that.formID).serializeObject();
			
			if(Object.keys(that._files).length > 0) {
				// Copy files in form into a FormData instance
				var fileData = new FormData();
				jQuery.each(that._files, function(k, v) {
					fileData.append(k, v[0]); // only grab the first file out of each file <input>; assume no multiples
				});
				$.ajax({
					url: that.fileUploadUrl,
					type: 'POST',
					data: fileData,
					cache: false,
					dataType: 'json',
					processData: false, // don't let jQuery try to process the files
					contentType: false, // set content type to false as jQuery will tell the server its a query string request
					success: function(data, textStatus, jqXHR) {
						if(typeof data.error === 'undefined') {
							// success... add file paths to form data
							console.log("File paths on server", data);
							jQuery.each(data, function(k, v) {
								formData[k] = v;
							});

							// call function to process the form
							that.post(e, formData);
						} else {
							// handle errors here
							console.log('ERRORS: ' + data.error);
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						// handle errors here
						console.log('ERRORS: ' + textStatus);
					}
				});
			} else {
				// no files; just submit the form data
				that.post(e, formData);
			}
		};
		
		that.post = function(e, formData) {
			jQuery.post(that.saveUrl, formData, function(resp, textStatus) {
				if (resp.status == 0) {
					var inputID = jQuery("#" + that.formID).parent().data('autocompleteInputID');
					var itemIDID = jQuery("#" + that.formID).parent().data('autocompleteItemIDID');
					var typeIDID = jQuery("#" + that.formID).parent().data('autocompleteTypeIDID');
					var relationbundle = jQuery("#" + that.formID).parent().data('relationbundle');
				
					jQuery('#' + inputID).val(resp.display);
					jQuery('#' + itemIDID).val(resp.id);
					jQuery('#' + typeIDID).val(resp.type_id);
					
					relationbundle.select(null, resp);
					jQuery.jGrowl(that.saveText.replace('%1', resp.display), { header: that.headerText }); 
					jQuery("#" + that.formID).parent().data('panel').hidePanel();
				} else {
					// error
					var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
					for(var e in resp.errors) {
						content += '<li class="notification-error-box">' + e + '</li>';
					}
					content += '</ul></div>';
					
					jQuery("#" + that.formErrorsPanelID).html(content).slideDown(200);
					
					var quickAddClearErrorInterval = setInterval(function() {
						jQuery("#" + that.formErrorsPanelID).slideUp(500);
						clearInterval(quickAddClearErrorInterval);
					}, 3000);
				}
			}, "json");
		};
		
		that.switchForm = function() {
			jQuery.each(CKEDITOR.instances, function(k, instance) {
				instance.updateElement();
			});
			console.log("set", jQuery("#" + that.formID + " input[name=type_id]"), "to", jQuery("#" + that.formTypeSelectID).val(), that.formTypeSelectID);
			jQuery("#" + that.formID + " input[name=type_id]").val(jQuery("#" + that.formTypeSelectID).val());
			var formData = jQuery("#" + that.formID).serializeObject();
			jQuery("#" + that.formID).parent().load(that.formUrl, formData);
			
		};
		
		// --------------------------------------------------------------------------------
		
		return that;
	};
	
})(jQuery);