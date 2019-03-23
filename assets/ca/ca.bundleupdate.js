/* ----------------------------------------------------------------------
 * js/ca.bundleupdatemanager.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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
var caBundleUpdateManager = null;
(function ($) {
	caUI.initBundleUpdateManager = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			byBundle: {},
			byID: {},
			byPlacementID: {},
			inspectorID: 'widgets',
			
			key: null,
			id: null,
			url: null,
			screen: null
		}, options);
		
		// --------------------------------------------------------------------------------
		// Methods
		// --------------------------------------------------------------------------------
		that.registerBundle = function(id, bundle, placement_id) {
			that.byID[id] = that.byPlacementID[placement_id] = {
				id: id, bundle: bundle, placement_id: placement_id
			};
			if(!that.byBundle[bundle]) { that.byBundle[bundle] = []; }
			that.byBundle[bundle].push(that.byID[id]);
		}
		
		// --------------------------------------------------------------------------------
		that.registerBundles = function(list) {
			var l;
			for(l in list) {
				that.registerBundle(list[l].id, list[l].bundle, list[l].placement_id);
			}
			//console.log("list", list);
		}
		
		// --------------------------------------------------------------------------------
		that.reloadBundle = function(bundle, options) {
			var b = that.byBundle[bundle];
			if (b) {
				jQuery.each(b, function(k, v) {
					var loadURL = that.url + "/" + that.key + "/" + that.id + "/bundle/" + v.bundle + "/placement_id/" + v.placement_id;
					if (options) { 
					    for(var k in options) {
					        loadURL += "/" + k + "/" + options[k];
					    }
					}
					jQuery("#" + v.id).load(loadURL);
				});
			}
		}
		
		// --------------------------------------------------------------------------------
		that.reloadInspector = function() {
			var loadURL = that.url + "/" + that.key + "/" + that.id + "/bundle/__inspector__";
			jQuery("#" + that.inspectorID).load(loadURL);
		}
		
		// --------------------------------------------------------------------------------
		
		return that;
	};
})(jQuery);