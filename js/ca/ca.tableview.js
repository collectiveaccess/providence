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
			columnSorting: true,
			
			dataLoadUrl: null,
			dataSaveUrl: null,
			editLinkFormat: null,
			
			rowHeaders: null,
			colHeaders: null,
			columns: null,
			colWidths: null,
			
			currentRowClassName: 'caResultsEditorCurrentRow',
			currentColClassName: 'caResultsEditorCurrentCol',
			readOnlyCellClassName: 'caResultsEditorReadOnlyCell',
			statusDisplayClassName: 'caResultsEditorStatusDisplay',
			
			saveMessage: "Saving...",
			errorMessagePrefix: "[Error]",
			saveSuccessMessage: "Saved changes",
			
			lastLookupIDMap: null,
			
			saveQueue: [],
			saveQueueIsRunning: false
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
		
		that.autocompleteRenderer = function(instance, td, row, col, prop, value, cellProperties) {
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
		
		that.colWidths = [];
		jQuery.each(that.columns, function(i, v) {
			that.colWidths.push(200);
			switch(that.columns[i]['type']) {
				case 'DT_SELECT':
					that.columns[i]['type'] = { renderer: that.autocompleteRenderer, editor: Handsontable.AutocompleteEditor, options: { items: 100 } };
					break;
				case 'DT_LOOKUP':
					that.columns[i]['type'] = { renderer: that.autocompleteRenderer, editor: Handsontable.AutocompleteEditor, options: { items: 100 } };
					that.columns[i]['source'] = function (query, process) {
						$.ajax({
							url: that.columns[i]['lookupURL'],
							data: {
								term: query,
								list: that.columns[i]['list'],
								simple: 0
							},
							success: function (response) {
								var labels = [];
								that.lastLookupIDMap = {};
								for(var k in response) {
									labels.push(response[k]['label']);
									that.lastLookupIDMap[response[k]['label']] = k;
								}
								process(labels);
							}
						})
					};
					break;
				default:
					that.columns[i]['type'] = { renderer: that.htmlRenderer };
					break;
			}
		});
		// --------------------------------------------------------------------------------
		
		that.save = function(change) {
			that.saveQueue.push(change);
			that._runSaveQueue();
		};
		// --------------------------------------------------------------------------------
		
		that._runSaveQueue = function() {
			if (that.saveQueueIsRunning) { 
				console.log("Queue is already running");	
				return false;
			}
			
			that.saveQueueIsRunning = true;
			var q = that.saveQueue;
			
			if (!q.length) { 
				that.saveQueueIsRunning = false;
				return false;
			}
			
			var ht = jQuery(that.container).data('handsontable');
			
			// make map from item_id to row, and vice-versa
			var rowToItemID = {}, itemIDToRow = {}, rowData = {};
			jQuery.each(q, function(k, v) {
				rowToItemID[v['change'][0][0]] = v['id'];
				itemIDToRow[v['id']] = v['change'][0][0];
				rowData[v['change'][0][0]] = v;
			});
			
			that.saveQueue = [];
			jQuery.getJSON(that.dataSaveUrl, { changes: q },
				function(data) {
					if (data.errors && (data.errors instanceof Object)) {
						var errorMessages = [];
						jQuery.each(data.errors, function(k, v) {
							errorMessages.push(that.errorMessagePrefix + ": " + v.message);
							ht.setDataAtRowProp(itemIDToRow[k], rowData[itemIDToRow[k]]['change'][0][1], rowData[itemIDToRow[k]]['change'][0][2], 'updateAfterRequest');
						});
						jQuery("." + that.statusDisplayClassName).html(errorMessages.join('; '));
					} else {
						jQuery("." + that.statusDisplayClassName).html(that.saveSuccessMessage);
						
						jQuery.each(data.messages, function(k, v) {
							ht.setDataAtRowProp(itemIDToRow[k], rowData[itemIDToRow[k]]['change'][0][1], v['value'], 'updateAfterRequest');
						});
						setTimeout(function() { jQuery('.' + that.statusDisplayClassName).fadeOut(500); }, 5000);
					}
					
					that.saveQueueIsRunning = false;
					that._runSaveQueue(); // anything else to save?
				}
			);
			
			return true;
		};
		// --------------------------------------------------------------------------------
		
		var ht = jQuery(that.container).handsontable({
			data: that.initialData,
			rowHeaders: that.rowHeaders,
			colHeaders: that.colHeaders,
			
			minRows: that.rowCount,
			maxRows: that.rowCount,
			contextMenu: that.contextMenu,
			columnSorting: that.columnSorting,
			
			currentRowClassName: that.currentRowClassName,
			currentColClassName: that.currentColClassName,
			
			stretchH: "all",
			columns: that.columns,
			colWidths: that.colWidths,
			
			dataLoadUrl: that.dataLoadUrl,
			editLinkFormat: that.editLinkFormat,
			statusDisplayClassName: that.statusDisplayClassName,
			
			onChange: function (change, source) {
				if ((source === 'loadData') || (source === 'updateAfterRequest')) {
				  return; //don't save this change
				}
				jQuery("." + that.statusDisplayClassName).html(that.saveMessage).fadeIn(500);
				
				var item_id = this.getDataAtRowProp(parseInt(change[0]), 'item_id');
				
				var pieces = change[0][1].split("-");
				var table = pieces.shift();
				var bundle = pieces.join('-');
				
				if (that.lastLookupIDMap) {
					if (that.lastLookupIDMap[change[0][3]]) {
						change[0][3] = that.lastLookupIDMap[change[0][3]];
					}
				}
				that.lastLookupIDMap = null;
				
				that.save({ 'table' : table, 'bundle': bundle, 'id': item_id, 'value' : change[0][3], 'change' : change, 'source': source });
			}
		});
		
		return that;
		
		// --------------------------------------------------------------------------------
	};	
})(jQuery);