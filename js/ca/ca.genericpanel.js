/* ----------------------------------------------------------------------
 * js/ca/ca.genericpanel.js
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
	caUI.initPanel = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			panelID: 'caPanel',							/* id of enclosing panel div */
			panelContentID: 'caPanelContent',			/* id of div within enclosing panel div that contains content */
	
			useExpose: true,
			exposeBackgroundColor: '#000000',
			exposeBackgroundOpacity: 0.5,
			panelTransitionSpeed: 200,
			allowMobileSafariZooming: false,
			mobileSafariViewportTagID: '_msafari_viewport',
			mobileSafariInitialZoom: "1.0",
			mobileSafariMinZoom: "1.0",
			mobileSafariMaxZoom: "10.0",
			mobileSafariDeviceWidth:  "device-width",
			mobileSafariDeviceHeight:  "device-height",
			mobileSafariUserScaleable: false,
			onOpenCallback: null,
			onCloseCallback: null,
			
			center: false,
			centerHorizontal: false,
			centerVertical : false,
			
			isChanging: false,
			clearOnClose: false
		}, options);
		
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.showPanel = function(url, onCloseCallback, clearOnClose, postData) {
			that.setZoom(that.allowMobileSafariZooming);
			that.isChanging = true;
			
			
			if (that.center || that.centerHorizontal) {
				jQuery('#' + that.panelID).css("left", ((jQuery(window).width() - jQuery('#' + that.panelID).width())/2) + "px");
			}
			
			if (that.center || that.centerVertical) {
				jQuery('#' + that.panelID).css("top", ((jQuery(window).height() - jQuery('#' + that.panelID).height())/2) + "px");
			}
			
			jQuery('#' + that.panelID).fadeIn(that.panelTransitionSpeed, function() { that.isChanging = false; });
			
			if (that.useExpose) { 
				jQuery('#' + that.panelID).expose({api: true, color: that.exposeBackgroundColor , opacity: that.exposeBackgroundOpacity, closeOnClick : false, closeOnEsc: true}).load(); 
			}
			
			if (onCloseCallback) {
				that.onCloseCallback = onCloseCallback;
			}
			
			// Apply close behavior to selected elements
			if (!postData) { postData = {}; }
			if (url) {
				jQuery('#' + that.panelContentID).load(url, postData, that.closeButtonSelector ? function() {			
					jQuery(that.closeButtonSelector).click(function() {
						that.hidePanel();
					})
				} : null);
				that.clearOnClose = (clearOnClose == undefined) ? true : clearOnClose;
			} else {
				if (clearOnClose != undefined) { that.clearOnClose = clearOnClose; }
			}
			
			if (that.onOpenCallback) {
				that.onOpenCallback(url);
			}
		}
		
		that.hidePanel = function(opts) {
			if (that.onCloseCallback) {
				that.onCloseCallback();
			}
			that.setZoom(false);
			that.isChanging = true;
			jQuery('#' + that.panelID).fadeOut(that.panelTransitionSpeed, function() { that.isChanging = false; });
			
			if (that.useExpose && (!opts || !opts.dontCloseMask)) {
				jQuery.mask.close();
			}
			
			if (that.clearOnClose) {
				jQuery('#' + that.panelContentID).empty();
				that.clearOnClose = false;
			}
		}
		
		that.panelIsVisible = function() {
			return (jQuery('#' + that.panelID + ':visible').length > 0) ? true : false;
		}
		
		that.getPanelID = function() {
			return that.panelID;
		}
		
		that.getPanelContentID = function() {
			return that.panelContentID;
		}
		
		// --------------------------------------------------------------------------------
		// Mobile Safari zooming
		// --------------------------------------------------------------------------------
		that.setZoom = function(allow) {
			if (allow) {
				jQuery('#' + that.mobileSafariViewportTagID).attr('content','width=' + that.mobileSafariDeviceWidth + ', height=' + that.mobileSafariDeviceHeight + ', initial-scale=' + that.mobileSafariInitialZoom + ', minimum-scale=' + that.mobileSafariMinZoom + ', maximum-scale=' + that.mobileSafariMaxZoom + ', user-scalable=' + (that.mobileSafariUserScaleable ? 'yes' : 'no') + '');
			} else {
				jQuery('#' + that.mobileSafariViewportTagID).attr('content', 'width=' + that.mobileSafariDeviceWidth + ', height=' + that.mobileSafariDeviceHeight + ', initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=yes');
			}
		}

		// --------------------------------------------------------------------------------
		// Set up handler to trigger appearance of panel
		// --------------------------------------------------------------------------------
		jQuery(document).ready(function() {
			// hide panel if click is outside of panel
			//jQuery(document).click(function(event) {
			//	var p = jQuery(event.target).parents().map(function() { return this.id; }).get();
			//	if (!that.isChanging && that.panelIsVisible() && (jQuery.inArray(that.panelID, p) == -1)) {
				//	that.hidePanel();
			//	}
			//});
			
			// hide panel if escape key is clicked
			jQuery(document).keyup(function(event) {
				if ((event.keyCode == 27) && !that.isChanging && that.panelIsVisible()) {
					that.hidePanel();
				}
			});
		});
		
		// --------------------------------------------------------------------------------
		
		return that;
	};	
})(jQuery);