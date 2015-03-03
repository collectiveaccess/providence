/* ----------------------------------------------------------------------
 * js/ca/ca.displaytemplateparser.js
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
	caUI.initPrepopulateField = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			dontUpdateIDs : []
		}, options);
		// --------------------------------------------------------------------------------
		/**
		 * Process display template and insert value into a given form element
		 * @param id the form element (usually a text input)
		 * @param options array
		 */
		that.setUpPrepopulateField = function(id, options) {
			var t = options.template;
			// get tags from template
			var tagRegex = /\^([\/A-Za-z0-9]+\[[\@\[\]\=\'A-Za-z0-9\.\-\/]+|[A-Za-z0-9_\.:\/]+[%]{1}[^ \^\t\r\n\"\'<>\(\)\{\}\/]*|[A-Za-z0-9_\.~:\/]+)/g;
			var tagList = options.template.match(tagRegex);
			var domElementForID = jQuery(id); // to avoid duplicate j-queries

			// replace all tags that are present in the current form with the current values
			// (only works for attributes)
			jQuery.each(tagList, function(i, tag) {
				var tagProc = tag.replace("^", "");
				var replacement = jQuery("input[id*=_" + options.elementIDs[tagProc] +"_]");
				if(replacement && replacement.val()) {
					t=t.replace(tag, replacement.val());
				}
			});

			// run the rest through CA template processor via editor controller JSON service
			var isFormLoad = options.isFormLoad;
			jQuery.getJSON(options.lookupURL + '/template/' + encodeURIComponent(t), function(data) {
				// if this is the initial load, don't overwrite any data already in the form because it's probably custom user data.
				if(isFormLoad && (domElementForID.html().trim().length > 0)) {
					return;
				}
				domElementForID.html(data.trim());
			});

			// set up click() handler for reset button
			// when it's clicked, we populate the field with the current state
			options.isFormLoad = false;
			jQuery(options.resetButtonID).click(function() {
				caPrepopulateField.setUpPrepopulateField(id, options);
				jQuery(':input').bind('keyup', function(e) {
					caPrepopulateField.setUpPrepopulateField(id, options);
				});

				domElementForID.bind('focus', function(e) {
					domElementForID.unbind('focus');
				});

				return false;
			});
		};
		// --------------------------------------------------------------------------------
		// helpers
		// --------------------------------------------------------------------------------
		that.dontUpdateID = function(id) {
			that.dontUpdateIDs.push(id);
		};
		// --------------------------------------------------------------------------------
		that.updateID = function(id) {
			if(that.dontUpdateIDs.indexOf(id)) {
				that.dontUpdateIDs.splice(that.dontUpdateIDs.indexOf(id),1);
			}
		};
		// --------------------------------------------------------------------------------
		return that;
	};

	caPrepopulateField = caUI.initPrepopulateField();
})(jQuery);
