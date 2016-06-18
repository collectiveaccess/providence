/* ----------------------------------------------------------------------
 * js/ca/ca.floorplan.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
	caUI.initFloorplan = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			baseID: null,
			panelID: 'caMediaPanel',
			viewerID: 'caMediaOverlayTileViewer',
			elementID: null,
			singularMessage: "%1 annotation on this floor plan",
			pluralMessage: "%1 annotations on this floor plan"
		}, options);
		
		jQuery('.' + that.baseID + '_trigger').on('click', function(e) {
			caMediaPanel.showPanel();
			jQuery('#caMediaPanelContentArea').html(jQuery('#' + that.baseID + '_viewer').val());
		});
		
		jQuery('#' + that.panelID).on('tileviewer:saveAnnotations', '#' + that.viewerID, function(e) {
			var data = jQuery("#" + that.baseID).val();
			var l = 0;
			if (data) { l = JSON.parse(data).length; }
			
			jQuery("#" + that.baseID + "_stats").html(((l == 1) ? that.singularMessage.replace('%1', l) : that.pluralMessage.replace('%1', l)));
		});
			
		// --------------------------------------------------------------------------------
		
		return that;
	};	
})(jQuery);