/* ----------------------------------------------------------------------
 * js/ca/ca.tableview.js
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
	caUI.initTableView = function(container, options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			container: container,
			initialData: null,
			rowCount: null,		// number of rows in result set to display
			contextMenu: false,
			columnSorting: false,
			
			dataLoadUrl: null,
			dataSaveUrl: null,
			editLinkFormat: null,
			
			rowHeaders: null,
			colHeaders: null,
			columns: null,
			
			currentRowClassName: 'caResultsEditorCurrentRow',
			currentColClassName: 'caResultsEditorCurrentCol',
			readOnlyCellClassName: 'caResultsEditorReadOnlyCell',
			
			saveMessage: "Saving...",
			errorMessagePrefix: "[Error]",
			saveSuccessMessage: "Saved changes"
		}, options);
		
	
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------

		that.htmlRenderer = function(instance, td, row, col, prop, value, cellProperties) {
			if (cellProperties.readOnly) {
				td.className = that.readOnlyCellClassName;
			}
			jQuery(td).empty().append(value);
			return td;
		};
		
		that.myAutocompleteRenderer = function(instance, td, row, col, prop, value, cellProperties) {
			Handsontable.AutocompleteCell.renderer.apply(this, arguments);
			td.style.fontStyle = 'italic';
		}
		
		that.caResultsEditorOpenFullScreen = function() {
			var ht = jQuery(that.container).data('handsontable');
			jQuery(that.container).toggleClass('caResultsEditorContentFullScreen');
		
			caResultsEditorPanel.showPanel();
		
			jQuery('#scrollingResults').toggleClass('caResultsEditorContainerFullScreen').prependTo('#caResultsEditorPanelContentArea'); 
		
			jQuery('.caResultsEditorToggleFullScreenButton').hide();
			jQuery("#caResultsEditorControls").show();
		
			ht.updateSettings({width: jQuery("#caResultsEditorPanelContentArea").width() - 15, height: jQuery("#caResultsEditorPanelContentArea").height() - 32});
			jQuery(that.container).width(jQuery("#caResultsEditorPanelContentArea").width() - 15).height(jQuery("#caResultsEditorPanelContentArea").height() - 32).resize();
		
			ht.render();
		}
	
		that.caResultsEditorCloseFullScreen = function(dontDoHide) {
			if (!dontDoHide) { caResultsEditorPanel.hidePanel(); }
			var ht = jQuery(that.container).data('handsontable');
	
			jQuery('#scrollingResults').toggleClass('caResultsEditorContainerFullScreen').prependTo('#caResultsEditorWrapper'); 
			jQuery(that.container).toggleClass('caResultsEditorContentFullScreen');
	
			jQuery('.caResultsEditorToggleFullScreenButton').show();
			jQuery("#caResultsEditorControls").hide();
	
			ht.updateSettings({width: 740, height: 500 });
			jQuery(that.container).width(740).height(500).resize();
			ht.render();
		}
		// --------------------------------------------------------------------------------
		
		jQuery.each(that.columns, function(i, v) {
			switch(that.columns[i]['type']) {
				case 'DT_SELECT':
					that.columns[i]['type'] = { renderer: that.myAutocompleteRenderer, editor: Handsontable.AutocompleteEditor, options: { items: 100 } };
					break;
				default:
					that.columns[i]['type'] = { renderer: that.htmlRenderer };
					break;
			}
		});
		
		var ht = jQuery(that.container).handsontable({
			data: that.initialData,
			rowHeaders: that.rowHeaders,
			colHeaders: that.colHeaders,
			
			minRows: that.rowCount,
			maxRows: that.rowCount,
			contextMenu: that.contextMenu,
			columnSorting: that.contextMenu,
			
			currentRowClassName: that.currentRowClassName,
			currentColClassName: that.currentColClassName,
			
			stretchH: "all",
			columns: that.columns,
			
			dataLoadUrl: that.dataLoadUrl,
			editLinkFormat: that.editLinkFormat,
			
			onChange: function (change, source) {
				if ((source === 'loadData') || (source === 'updateAfterRequest')) {
				  return; //don't save this change
				}
				jQuery(".caResultsEditorStatusDisplay").html(that.saveMessage).fadeIn(500);
				
				var ht = jQuery(this).data('handsontable');
				var item_id = ht.getDataAtRowProp(parseInt(change[0]), 'item_id');
				
				var pieces = change[0][1].split("-");
				var table = pieces.shift();
				var bundle = pieces.join('-');
				
				jQuery.getJSON(that.dataSaveUrl, { 'table' : table, 'bundle': bundle, 'id': item_id, 'value' : change[0][3] },
				function(data) {
					if (data.error > 0) {
						jQuery(".caResultsEditorStatusDisplay").html(that.errorMessagePrefix + ": " + data.message);
						ht.setDataAtRowProp(parseInt(change[0]), change[0][1], change[0][2], 'updateAfterRequest');
					} else {
						jQuery(".caResultsEditorStatusDisplay").html(that.saveSuccessMessage);
						if (data.value != undefined) { ht.setDataAtRowProp(parseInt(change[0]), change[0][1], data.value, 'updateAfterRequest'); }
						setInterval(function() { jQuery('.caResultsEditorStatusDisplay').fadeOut(500); }, 5000);
					}
				});
			}
			
		});
		
		return that;
	};	
})(jQuery);