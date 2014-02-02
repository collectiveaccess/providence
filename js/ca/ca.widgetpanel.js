/* ----------------------------------------------------------------------
 * js/ca/ca.widgetpanel.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
	caUI.initWidgetPanel = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			widgetUrl: '',
			panelCSSClass: 'browseSelectPanel',
			
			useExpose: true,
			
			isChanging: false
		}, options);
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.showWidgetPanel = function() {
			that.isChanging = true;
			jQuery("#dashboardWidgetPanel").fadeIn(200, function() { that.isChanging = false; });
			
			if (that.useExpose) { 
				jQuery("#dashboardWidgetPanel").expose({api: true, color: '#000000', opacity: 0.5}).load(); 
			}
			jQuery("#dashboardWidgetPanelContent").load(that.widgetUrl, {});
		}
		
		that.hideWidgetPanel = function() {
			that.isChanging = true;
			jQuery("#dashboardWidgetPanel").fadeOut(200, function() { that.isChanging = false; });
			
			if (that.useExpose) {
				jQuery.mask.close();
			}
			jQuery("#dashboardWidgetPanelContent").empty();
		}
		
		that.WidgetPanelIsVisible = function() {
			return (jQuery("#dashboardWidgetPanel:visible").length > 0) ? true : false;
		}

		// --------------------------------------------------------------------------------
		// Set up handler to trigger appearance of browse panel
		// --------------------------------------------------------------------------------
		jQuery(document).ready(function() {
			
			// hide panel if click is outside of panel
			jQuery(document).click(function(event) {
				var p = jQuery(event.target).parents().map(function() { return this.id; }).get();
				if (!that.isChanging && that.WidgetPanelIsVisible() && (jQuery.inArray('dashboardWidgetPanel', p) == -1)) {
					that.hideWidgetPanel();
				}
			});
			
			// hide browse panel if escape key is clicked
			jQuery(document).keyup(function(event) {
				if ((event.keyCode == 27) && !that.isChanging && that.WidgetPanelIsVisible()) {
					that.hideWidgetPanel();
				}
			});
		});
		
		// --------------------------------------------------------------------------------
		
		return that;
	};	
})(jQuery);