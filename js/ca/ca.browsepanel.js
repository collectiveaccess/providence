/* ----------------------------------------------------------------------
 * js/ca/ca.browsepanel.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2014 Whirl-i-Gig
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
	caUI.initBrowsePanel = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			facetUrl: '',
			addCriteriaUrl: '',
			panelCSSClass: 'browseSelectPanel',
			panelID: 'splashBrowsePanel',
			panelContentID: 'splashBrowsePanelContent',
			
			facetSelectID: "browseFacetSelect",
			
			useExpose: true,
			useStaticDiv: false,									/* set if you want to use a visible <div> for the browse panel rather than a show/hide overlay <div> */
			
			isChanging: false,
			browseID: null,
			
			singleFacetValues: {},
			
			exposeBackgroundColor: '#000000',						/* color (in hex notation) of background masking out page content; include the leading '#' in the color spec */
			exposeBackgroundOpacity: 0.5,							/* opacity of background color masking out page content; 1.0 is opaque */
			panelTransitionSpeed: 200
		}, options);
		
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.showBrowsePanel = function(facet, modifyMode, modifyID, grouping, clear, target) {
			if (that.singleFacetValues[facet]) {
				document.location = that.addCriteriaUrl + "/facet/" + facet + "/id/" + that.singleFacetValues[facet];
				return true;
			}
			that.isChanging = true;
			if (!facet) { return; }
			
			if (!that.useStaticDiv) {
				jQuery("#" + that.panelID).fadeIn(that.panelTransitionSpeed, function() { that.isChanging = false; });
			
				if (that.useExpose) { 
					jQuery("#" + that.panelID).expose({api: true, color: that.exposeBackgroundColor, opacity: that.exposeBackgroundOpacity, zIndex: 99999}).load(); 
				}
			}
			if (!modifyID) { modifyID = ''; }
			
			var options = { facet: facet, modify: (modifyMode ? 1 : ''), id: modifyID, grouping: grouping, clear: clear ? 1 : 0 };
			if (that.browseID) { options['browse_id'] = that.browseID; }
			if (target) { options['target'] = target; }
			jQuery("#" + that.panelContentID).load(that.facetUrl, options);
		}
		
		that.hideBrowsePanel = function() {
			that.isChanging = true;
			
			if (!that.useStaticDiv) {
				jQuery("#" + that.panelID).fadeOut(that.panelTransitionSpeed, function() { that.isChanging = false; });
			
				if (that.useExpose) {
					jQuery.mask.close();
				}
			}
			jQuery("#" + that.panelContentID).empty();
		}
		
		that.browsePanelIsVisible = function() {
			return (jQuery("#" + that.panelID + ":visible").length > 0) ? true : false;
		}

		// --------------------------------------------------------------------------------
		// Set up handler to trigger appearance of browse panel
		// --------------------------------------------------------------------------------
		jQuery(document).ready(function() {
			jQuery('#' + that.facetSelectID).change(function() { 
				that.showBrowsePanel(jQuery('#' + that.facetSelectID).val());
			}).click(function() { jQuery('#' + that.facetSelectID).attr('selectedIndex', 0); });
			
			// hide browse panel if click is outside of panel
			jQuery(document).click(function(event) {
				var p = jQuery(event.target).parents().map(function() { return this.id; }).get();
				if (!that.isChanging && that.browsePanelIsVisible() && (jQuery.inArray(that.panelID, p) == -1)) {
					that.hideBrowsePanel();
				}
			});
			
			// hide browse panel if escape key is clicked
			jQuery(document).keyup(function(event) {
				if ((event.keyCode == 27) && !that.isChanging && that.browsePanelIsVisible()) {
					that.hideBrowsePanel();
				}
			});
		});
		
		// --------------------------------------------------------------------------------
		
		return that;
	};	
})(jQuery);