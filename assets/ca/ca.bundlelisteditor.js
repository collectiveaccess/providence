/* ----------------------------------------------------------------------
 * js/ca/ca.bundlelisteditor.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2016 Whirl-i-Gig
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
 
//
// Note: requires jQuery UI.Sortable
//
 
var caUI = caUI || {};

(function ($) {
	caUI.bundlelisteditor = function(options) {
		var that = jQuery.extend({
		
			availableListID: 'bundleDisplayEditorAvailableList',
			toDisplayListID: 'bundleDisplayEditorToDisplayList',
			
			displayItemClass: 'bundleDisplayEditorPlacement',					/* CSS class assigned to each item in the display list */
			displayListClass: 'bundleDisplayEditorPlacementList',				/* CSS class assigned to display list <div> */
		
			availableDisplayList: null,											/* array of bundles that may be displayed */
			initialDisplayList: null,												/* array of bundles to display on load */
			initialDisplayListOrder: null,										/* id's to list display list in; required because Google Chrome doesn't iterate over keys in an object in insertion order [doh] */	
			
			displayBundleListID: null,											/* ID of hidden form element to contain list of selected bundles */
			
			allowSettings: true,
			settingsIcon: null
		}, options);

		// ------------------------------------------------------------------------------------
		that.initDisplayList = function() {
			if (!that.initialDisplayList) { return; }
			var displayListText = '';
			var usedBundles = {};
			
			jQuery.each(that.initialDisplayListOrder, function(k, v) {
				displayListText += that._formatForDisplay(that.initialDisplayList[v]);
				usedBundles[v.bundle] = true;
			});
			
			jQuery('#' + that.toDisplayListID)
				.html(displayListText);
			
			displayListText = '';
			jQuery.each(that.availableDisplayList, function(k, v) {
				displayListText += that._formatForDisplay(v);
			});
			jQuery('#' + that.availableListID).html(displayListText);
			
			that._makeDisplayListsSortable();
			that._updateBundleListFormElement();
		}
		// ------------------------------------------------------------------------------------
		// sortable lists
		that._makeDisplayListsSortable = function() {
			jQuery("#" + that.availableListID).sortable({ opacity: 0.7, 
				revert: 0.2, 
				scroll: true , 
				connectWith: "#" + that.toDisplayListID,
				update: function(event, ui) {
					that._updateBundleListFormElement();
				}
			});
			
			jQuery("#" + that.toDisplayListID).sortable({ opacity: 0.7, 
				revert: 0.2, 
				scroll: true , 
				connectWith: "#" + that.availableListID,
				update: function(event, ui) {
					that._updateBundleListFormElement();
				}
			});
		}
		// ------------------------------------------------------------------------------------
		that._formatForDisplay = function(placement_info) {
			var label = placement_info.display;
			var bundle = placement_info.bundle;
			var placementID = placement_info.placement_id;
			var settingsForm = '';
            var output;
			
			var id = bundle;
			if (placementID) { 
				settingsForm =  that.initialDisplayList[placementID] ?  that.initialDisplayList[placementID].settingsForm : '';
				id = id + '_' + placementID; 
			} else { 
				settingsForm =  that.availableDisplayList[bundle] ?  that.availableDisplayList[bundle].settingsForm : '';
				id = id + '_0'; 
			}

			// Store the settings form HTML for lazy insertion.
			// TODO: Use namespaces to prevent id collisions with multiple of this plugin on a single page.
			caUI.bundlelisteditor.settingsForms[id] = settingsForm;

			output =  "<div id='displayElement_" + id +"' class='" + that.displayItemClass + "'>";
			if (that.allowSettings) {
				output += "<div class='bundleDisplayElementSettingsControl'><a href='#' onclick='caUI.bundlelisteditor.initSettingsForm(\"" + id + "\"); jQuery(\".elementSettingsUI\").fadeOut(250); jQuery(\"#displayElementSettings_" +  id.replace(/\./g, "\\\\.") +"\").fadeIn(250); return false; '>" + that.settingsIcon + "</a></div>";
			}
			output += "<div style='width:75%'>" + label + " </div>";
			output += "</div>\n";	
			
			if (that.allowSettings) {		
				output += "<div id='displayElementSettings_" + id +"' class='elementSettingsUI' style='display: none;'>" + label + "<div class='settingsFormContainer'></div><a href='#' onclick='jQuery(\"#displayElementSettings_" +  id.replace(/\./g, "\\\\.") +"\").fadeOut(250); return false; '>" + that.settingsIcon + "</a></div>";
			}
			return output;
		}
		// ------------------------------------------------------------------------------------
		that._updateBundleListFormElement = function() {
			var bundle_list = [];
			jQuery.each(jQuery('#' + that.toDisplayListID + " div." + that.displayItemClass), function(k, v) { 
				bundle_list.push(jQuery(v).attr('id').replace('displayElement_', ''));
			});
			jQuery('#' + that.displayBundleListID).val(bundle_list.join(';'));
			
			jQuery('#' + that.availableListID + ' .' +  that.displayItemClass + ' .bundleDisplayElementSettingsControl').hide(0);
			jQuery('#' + that.availableListID + ' input').attr('disabled', true);
			
			jQuery('#' + that.toDisplayListID + ' .' +  that.displayItemClass + ' .bundleDisplayElementSettingsControl').show(0);
			jQuery('#' + that.toDisplayListID + ' input').attr('disabled', false);
		}
		// ------------------------------------------------------------------------------------
		
		that.initDisplayList();
		return that;
	};

	// Keys are ids and values are HTML for the popup settings form, stored here and lazily inserted into the DOM.
	caUI.bundlelisteditor.settingsForms = {};

	// Lazily insert popup settings form HTML from map.
	caUI.bundlelisteditor.initSettingsForm = function (id) {
		if (caUI.bundlelisteditor.settingsForms[id]) {
			$('#displayElementSettings_' + id.replace('.', '\\.') + ' .settingsFormContainer')		// don't forget to escape periods in DOM ids
				.html(caUI.bundlelisteditor
				.settingsForms[id])					
				.find("input:checked").change();	// trigger change handler to hide anything affected by hideOnSelect option for checkboxes
			delete caUI.bundlelisteditor.settingsForms[id];
		}
	}

})(jQuery);
