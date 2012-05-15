/* ----------------------------------------------------------------------
 * js/ca/ca.dashboard.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
	caUI.initDashboard = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			reorderURL: null,
			
			dashboardClass: 'dashboard',
			landingClass: 'dashboardLanding',
			columnClass: 'column',
			widgetClass: 'portlet',
			widgetRemoveClass: 'dashboardRemoveWidget',
			widgetSettingsClass: 'dashboardWidgetSettingsButton',
			
			addID: 'dashboardAddWidget',
			editID: 'dashboardEditWidget',
			doneEditingID: 'dashboardDoneEditingButton',
			clearID: 'dashboardClearButton',
			welcomeMessageID: 'dashboard_welcome_message',
			editMessageID: 'dashboard_edit_message'
		}, options);
		
		jQuery(document).ready(function() {
			jQuery("." + that.columnClass).sortable({
				connectWith: '.' + that.columnClass,
				stop: function(event, ui) {
					jQuery.getJSON(
						that.reorderURL,
						{
							'sort_column1': jQuery("#dashboard_column_1").sortable('toArray').join(';'), 
							'sort_column2': jQuery("#dashboard_column_2").sortable('toArray').join(';')
						} , 
						function(data) { 
							if(data.status != 'ok') { 
								alert('Error: ' + data.errors.join(';')); 
							}; 
							that.refreshDashboard(true);
						}
					);
				}
			});
	
			jQuery("." + that.columnClass).disableSelection();
			jQuery("." + that.landingClass).disableSelection();
			
			var edit = 0;
			var cookieJar = jQuery.cookieJar('caCookieJar');
			edit = cookieJar.get('caDashboardEdit');
			if (edit == null) { edit = 0; }
			edit = (edit == 0) ? 0 : 1;
			
			that.editDashboard(edit, true);
		});
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		//
		// Refresh dashboard display after a change
		that.refreshDashboard = function(noTransitions) {
			var cookieJar = jQuery.cookieJar('caCookieJar');
		
			// Activate/deactivate "landing" areas if a column is empty
			var allColumnsAreNotEmpty = false;
			jQuery.each(jQuery('.' + that.columnClass), function (index, val) {
				if (parseInt(cookieJar.get('caDashboardEdit')) == 1) {
					jQuery('#'  + val.id + ' .' + that.landingClass).css('display', (jQuery('#'  + val.id + " .portlet").length == 0) ? 'block' : 'none').css('height', '100px');
				} else {
					jQuery('#'  + val.id + ' .' + that.landingClass).css('display', 'none');
				}
				if (jQuery('#'  + val.id + " .portlet").length > 0) { allColumnsAreNotEmpty = true; }
			});
			
			// Show dashboard welcome text if dashboard is empty, hide it if widgets have been added
			
			if (parseInt(cookieJar.get('caDashboardEdit')) == 1) {
				jQuery('#' + that.editMessageID).slideDown(noTransitions ? 0 : 250);
				jQuery('#' + that.welcomeMessageID).hide();
			} else {
				jQuery('#' + that.editMessageID).slideUp(noTransitions ? 0 : 250);
				if (allColumnsAreNotEmpty) {
					jQuery('#' + that.welcomeMessageID).hide();
				} else {
					jQuery('#' + that.welcomeMessageID).show();
				}
				
			}
			
			// Rename widgets with tmp id's in preparation for renumbering 
			// (if we don't rename them we could end up with two widgets with the same id at some point)
			jQuery.each(jQuery('.' + that.columnClass + ' div.portlet:not(.' + that.landingClass + ')'), function (index, val) {
				if (!val.id) { return; }
				jQuery('#'  + val.id).attr('id', val.id + '_tmp');
			});
			
			// Renumber widgets to reflect their true positions; if we are refreshing after a drag-and-drop operation
			// the current id's will not reflect their current location
			var counters = [];
			jQuery.each(jQuery('.' + that.columnClass + ' div.portlet:not(.' + that.landingClass + ')'), function (index, val) {
				if (!val.id) { return; }
				
				var widgetID = jQuery('#'  + val.id).attr('id');
				var tmp = val.id.split('_');
				if (!(col = parseInt(tmp[1]))) { col = 1; }
				if (!(pos = parseInt(tmp[2]))) { pos = 0; }
				
				tmp = jQuery('#' + val.id).parent().attr('id').split('_');
				var currentCol = parseInt(tmp[2]);
				if (!counters[currentCol]) { counters[currentCol] = 0; }
				
				jQuery('#'  + val.id).attr('id', tmp[0] + '_' + currentCol + '_' + counters[currentCol]);
				jQuery('#'  + val.id).data('col', currentCol);
				jQuery('#'  + val.id).data('pos', counters[currentCol]);
				
				counters[currentCol]++;
			});

		}
		
		// --------------------------------------------------------------------------------
		that.editDashboard = function(edit, noTransitions) {
			if (edit === null) { edit = 0; }
			edit = parseInt(edit);
			
			var cookieJar = jQuery.cookieJar('caCookieJar');
			if (edit != 0) {
				jQuery('#' + that.addID).show(0);
				jQuery('#' + that.editID).hide(0);
				jQuery('#' + that.doneEditingID).show(0);
				jQuery('#' + that.clearID).show(0);
				
				jQuery("." + that.columnClass).sortable("enable");
				jQuery('.' + that.widgetRemoveClass).show(0);
				jQuery('.' + that.widgetSettingsClass).show(0);
				
				cookieJar.set('caDashboardEdit', 1);
			} else {
				jQuery('#' + that.addID).hide(0);
				jQuery('#' + that.editID).show(0);
				jQuery('#' + that.doneEditingID).hide(0);
				jQuery('#' + that.clearID).hide(0);
				
				jQuery("." + that.columnClass).sortable("disable");
				jQuery('.' + that.widgetRemoveClass).hide(0);
				jQuery('.' + that.widgetSettingsClass).hide(0);
				
				cookieJar.set('caDashboardEdit', 0);
			}
			
			that.refreshDashboard(noTransitions);
		}
		// --------------------------------------------------------------------------------
		
		return that;
	};	
})(jQuery);