/* ----------------------------------------------------------------------
 * js/ca/ca.rolelistbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
	caUI.initrolelistbundle = function(container, options) {
		var that = jQuery.extend({
			container: container,
			templateValues: [],
			initialValues: {},
			errors: {},
			itemID: '',
			fieldNamePrefix: '',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			defaultValues: {},
			readonly: 0,
			bundlePreview: ''
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
			
			// set up access drop-downs
			jQuery.each(initialValues, function(k, v) {
				jQuery(that.container + ' #' + that.fieldNamePrefix + 'access_' + v['role_id']).val(v['access']);
			});			
			
			
			if (this.readonly) {
				jQuery(this.container + " input").prop("disabled", true);
				jQuery(this.container + " select").prop("disabled", true);
			}

			// Add bundle preview value text
			if(this.bundlePreview && (this.bundlePreview.length > 0)) {
				jQuery('#' + this.fieldNamePrefix + 'BundleContentPreview').text(this.bundlePreview);
			}
		}
		
		
		// create empty form
		that.setupBundle('rolelist', that.initialValues, true);
		
		return that;
	};
	
	
})(jQuery);