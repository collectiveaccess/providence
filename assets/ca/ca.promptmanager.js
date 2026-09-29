/* ----------------------------------------------------------------------
 * js/ca.promptmanager.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
	caUI.initPromptManager = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			bundles: [],
			cookieJar: jQuery.cookieJar('caPromptManager'),
			prompts: []
		}, options);

		// --------------------------------------------------------------------------------
		/**
		 * @param id
		 * @param message
		 */
		that.addPrompt = function(id, message) {
			jQuery("#" + id + " .bundleLabel").attr('data-intro', message).attr('data-position', 'top');
			that.prompts.push({id: id, prompt: message });
		};
		
		// --------------------------------------------------------------------------------
		/**
		 * @param id
		 * @param message
		 */
		that.removePrompt = function(id, message) {
			// TODO
			jQuery("#" + id + " .bundleLabel").attr('data-intro', null);
		};


		// --------------------------------------------------------------------------------

		return that;
	};

	caPromptManager = caUI.initPromptManager();
})(jQuery);
