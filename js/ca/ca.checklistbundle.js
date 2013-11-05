/* ----------------------------------------------------------------------
 * js/ca/ca.checklistbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
	caUI.initChecklistBundle = function(container, options) {
		var that = jQuery.extend({
			container: container,
			templateValues: [],
			initialValues: {},
			errors: {},
			itemID: '',
			fieldNamePrefix: '',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			localeClassName: 'labelLocale',
			counter: 0,
			minRepeats: 0,
			maxRepeats: 65535,
			defaultValues: {},
			readonly: 0
		}, options);
		
		that.showUnsavedChangesWarning = function(b) {
			if(typeof caUI.utils.showUnsavedChangesWarning === 'function') {
				if (b === undefined) { b = true; }
				caUI.utils.showUnsavedChangesWarning(b);
			}
		}
		
		that.setupBundle = function(id, initialValues, dontUpdateBundleFormState) {
			// prepare template values
			var templateValues = {};
			
			templateValues.n = id;
			
			// print out any errors
			var errStrs = [];
			if (this.errors && this.errors[id]) {
				var i;
				for (i=0; i < this.errors[id].length; i++) {
					errStrs.push(this.errors[id][i].errorDescription);
				}
			}
			
			templateValues.error = errStrs.join('<br/>');
			templateValues.fieldNamePrefix = this.fieldNamePrefix; // always pass field name prefix to template
			
			// Set default value for new items
			if (!id) {
				jQuery.each(this.defaultValues, function(k, v) {
					if (v && !initialValues[k]) { initialValues[k] = v; }
				});
			}
			
			// replace values in template
			var jElement = jQuery(this.container + ' textarea.' + this.templateClassName).template(templateValues); 
			jQuery(this.container + " ." + this.itemListClassName).append(jElement);

			var that = this;	// for closures
						
			// set up add/delete triggers on check/uncheck of boxes
			jQuery(container + ' input[type=checkbox]').click(function(e) {
				var boundsViolation = !that.checkMaxMin();
				
				if (boundsViolation) {
					jQuery(this).prop('checked', !jQuery(this).prop('checked'));
				} else {
					that.showUnsavedChangesWarning(true);
					if (jQuery(this).prop('checked')) {
						jQuery(jQuery(this).attr('id') + "_delete").remove();
					} else {
						that.deleteValue(jQuery(this).attr('id'));
					}
				}
				
			});
			
			jQuery(container + ' input[type=checkbox]').each(function(i, input) {
				jQuery(input).attr('id', that.fieldNamePrefix + that.templateValues[0] + '_new_' + i);
				jQuery(input).attr('name', that.fieldNamePrefix + that.templateValues[0] + '_new_' + i);
			});
			
			// check current values
			jQuery.each(initialValues, function(i, v) {
				// If they are current rename them to be active values (eg. as if they were separate generic bundle values)
				jQuery(that.container + " input[value=" + (v[that.templateValues[0]]) + "]").prop('checked', true).attr('id', that.fieldNamePrefix + (that.templateValues[0]) + "_" + i).attr('name', that.fieldNamePrefix + (that.templateValues[0]) + "_" + i);
			});
			
			if (this.readonly) {
				jQuery(this.container + " input").prop("disabled", true);
				jQuery(this.container + " select").prop("disabled", true);
			}
		}
		
		that.deleteValue = function(id) {
			// Don't bother marking new values as deleted since the absence of a checkbox will prevent them from being submitted
			if (id.indexOf('_new_') == -1) {
				var re = new RegExp("([A-Za-z0-9_\-]+)_([0-9]+)_([0-9]+)_([0-9]+)", "g");
				var res;
				
				if (res = re.exec(id)) {		// is three number checklist elements (attribute_id/attribute_value_id/repeating index #)
					// We *do* have to mark existing values as deleted however, otherwise the attributes will not be removed server-side
					jQuery(this.container).append("<input type='hidden' name='" + res[1] + '_' + res[2] + '_' + res[4] + "_delete' value='1'/>");
				} else {
					re = new RegExp("([A-Za-z0-9_\-]+)_([0-9]+)", "g");
				
					if (res = re.exec(id)) {	// is one-part # (value)
						jQuery(this.container).append("<input type='hidden' name='" + res[1] + '_' + res[2] + "_delete' value='1'/>");
					}
				}
			}
			
			return this;
		};
		
		that.checkMaxMin = function() {
			var numChecked = jQuery(that.container + ' input:checked').length;
			if ((numChecked < that.minRepeats) || (numChecked > that.maxRepeats)) {
				return false;
			}
			return true;
		};
		
		// create empty form
		that.setupBundle('checklist', that.initialValues, true);
		
		return that;
	};
	
	
})(jQuery);