/* ----------------------------------------------------------------------
 * js/ca/ca.bundlevisibilty.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
	caUI.initBundleVisibilityManager = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			bundles: [],
			cookieJar: jQuery.cookieJar('caBundleVisibility'),
			bundleStates: {}
		}, options);
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.registerBundle = function(id) {
			that.bundles.push(id);
			that.bundleStates[id] = (that.cookieJar.get(id) == 'closed') ? "closed" : "open";
			if (that.bundleStates[id] == "closed") {
				that.close(id, true);
			} else {
				that.open(id, true);
			}
		}
		
		// Set initial visibility of all registered bundles
		that.setAll = function() {
			jQuery.each(that.bundles, function(k, id) {
				if(that.bundleStates[id] == 'closed') {
					jQuery("#" + id).hide();
				} else {
					jQuery("#" + id).show();
				}
			});
		}
		
		// Open bundle
		that.toggle = function(id) {
			if(that.bundleStates[id] == 'closed') {
				that.open(id);
			} else {
				that.close(id);
			}
		}
		
		// Open bundle
		that.open = function(id, dontAnimate) {
			if (id === undefined) {
				jQuery.each(that.bundles, function(k, id) {
					that.open(id);
				});
			} else {
				jQuery("#" + id).slideDown(dontAnimate ? 0 : 250);
				that.bundleStates[id] = 'open';
				that.cookieJar.set(id, 'open');
				
				if (dontAnimate) {
					jQuery("#" + id + "VisToggleButton").rotate({ angle: 180 });
				} else {
					jQuery("#" + id + "VisToggleButton").rotate({ duration:500, angle: 0, animateTo: 180 });
				}
			}			
		}
		
		// Close bundle
		that.close = function(id, dontAnimate) {
			if (id === undefined) {
				jQuery.each(that.bundles, function(k, id) {
					that.close(id);
				});
			} else {
				jQuery("#" + id).slideUp(dontAnimate ? 0 : 250);
				that.bundleStates[id] = 'closed';
				that.cookieJar.set(id, 'closed');
				
				if (dontAnimate) {
					jQuery("#" + id + "VisToggleButton").rotate({ angle: 0 });
				} else {
					jQuery("#" + id + "VisToggleButton").rotate({ duration:500, angle: 180, animateTo: 0 });
				}
			}
		}
		
		// --------------------------------------------------------------------------------
		
		return that;
	};
	
	caBundleVisibilityManager = caUI.initBundleVisibilityManager();
})(jQuery);