/* ----------------------------------------------------------------------
 * js/ca/ca.tableview.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2015 Whirl-i-Gig
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
			numRowsPerLoad: 20,
			
			dataSaveUrl: null,
			editLinkFormat: null,
			
			colHeaders: null,
			columns: null,
			colWidths: null,
			
			gridClassName: 'caResultsEditorContent',
			currentRowClassName: 'caResultsEditorCurrentRow',
			currentColClassName: 'caResultsEditorCurrentCol',
			readOnlyCellClassName: 'caResultsEditorReadOnlyCell',
			overlayEditorIconClassName: 'caResultsEditorOverlayIcon',
			statusDisplayClassName: 'caResultsEditorStatus',
			
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
			
			// Add "click to edit" icon
			if (cellProperties.editMode == 'overlay') {
				value += ' <div class="' + that.overlayEditorIconClassName + '"><i class="fa fa-pencil-square-o"></i></div>';
			}
			
			jQuery(td).empty().append(value);
			return td;
		};
		// --------------------------------------------------------------------------------
		
		that.colWidths = [];
		console.log(that.columns);
		jQuery.each(that.columns, function(i, v) {
			that.colWidths.push(200);
			switch(that.columns[i]['type']) {
				case 'DT_SELECT':
					delete(that.columns[i]['type']);
					that.columns[i]['type'] = 'autocomplete';
					that.columns[i]['editor'] = 'dropdown';
					break;
				case 'DT_LOOKUP':
					var d = that.columns[i]['data'];
					var lookupUrl = that.columns[i]['lookupURL'];
					var list = that.columns[i]['list'];
					that.columns[i] = {
						'data' : d,
						'type': 'autocomplete',
						'strict': false,
						'source' : function (query, process) {
							if (!query) { return; }
							$.ajax({
								url: lookupUrl,
								data: {
									term: query,
									list: list,
									simple: 0,
									
									
								},
								success: function (response) {
									var labels = [];
									that.lastLookupIDMap = {};
									for(var k in response) {
										labels.push(response[k]['label']);
										that.lastLookupIDMap[response[k]['label']] = k;
									}
									if (labels.length > 0) {
										process(labels);
									}
								}
							})
					}};
					break;
				default:
					delete(that.columns[i]['type']);
					that.columns[i]['renderer'] = that.htmlRenderer;
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
			
			var ht = jQuery(that.container + " ." + that.gridClassName).data('handsontable');
			
			// make map from item_id to row, and vice-versa
			var rowToItemID = {}, itemIDToRow = {}, rowData = {};
			jQuery.each(q, function(k, v) {
				rowToItemID[v['change'][0][0]] = v['id'];
				itemIDToRow[v['id']] = v['change'][0][0];
				rowData[v['change'][0][0]] = v;
			});
			
			that.saveQueue = [];
			jQuery.post(that.dataSaveUrl, { changes: q },
				function(data) {
					if (data.errors && (data.errors instanceof Object)) {
						var errorMessages = [];
						jQuery.each(data.errors, function(k, v) {
							errorMessages.push(that.errorMessagePrefix + ": " + v.message);
							ht.setDataAtRowProp(itemIDToRow[k], rowData[itemIDToRow[k]]['change'][0][1], rowData[itemIDToRow[k]]['change'][0][2], 'external');
						});
						jQuery("." + that.statusDisplayClassName).html(errorMessages.join('; '));
					} else {
						jQuery("." + that.statusDisplayClassName).html(that.saveSuccessMessage);
						
						if (data.messages) {
							jQuery.each(data.messages, function(k, v) {
								ht.setDataAtRowProp(itemIDToRow[k], rowData[itemIDToRow[k]]['change'][0][1], v['value'], 'external');
							});
							setTimeout(function() { jQuery('.' + that.statusDisplayClassName).fadeOut(500); }, 5000);
						}
					}
					
					that.saveQueueIsRunning = false;
					that._runSaveQueue(); // anything else to save?
				},
				'json'
			);
			
			return true;
		};
		// --------------------------------------------------------------------------------
		
		// Calculate full window dimensions
		var $window = jQuery(window);
		var availableWidth = $window.width() + $window.scrollLeft();
		var availableHeight = $window.height() + $window.scrollTop() - 30;	// leave 30 pixels of space for status bar
		
		// Set up HOT and load first batch of data
		jQuery.getJSON(that.dataLoadUrl, {s:0, c:that.numRowsPerLoad}, function(data) {
			that.initialData = data;
			var ht = jQuery(that.container + " ." + that.gridClassName).handsontable({
				data: that.initialData,
				columns: that.columns,
				
				colHeaders: that.colHeaders,
	
				contextMenu: that.contextMenu,
				columnSorting: that.columnSorting,
				width: (that.width > 0) ? that.width : availableWidth,
				height: (that.height > 0) ? that.height : availableHeight,
		
				currentRowClassName: that.currentRowClassName,
				currentColClassName: that.currentColClassName,
		
				stretchH: "all",
				colWidths: that.colWidths,
				
				cells: function (row, col, prop) {
					var cellProperties = {};
					
					cellProperties['editMode'] = that.initialData[row][prop + "_edit_mode"];
					cellProperties['readOnly'] = !(cellProperties['editMode'] == 'inline');
					
					return cellProperties;
				},
				afterChange: function (change, source) {
					if (source === 'loadData') {
						return; //don't save this change
					}
					
					//console.log("changes", change, source);
				
					for(var i in change) {
						var r = change[i][0];
						var row_id = that.initialData[r]['id'];
						that.save({'change': change[i], 'id': row_id});
					}
				}
			});
			
			// Load additional data in chunks
			var rowsLoaded = that.numRowsPerLoad;
			var _loadData = function(s, c) {
				if (that.statusDisplayClassName) { jQuery(that.container + " ." + that.statusDisplayClassName).html("Loading " + s + " - " + (s + c)); }	
				jQuery.getJSON(that.dataLoadUrl, {s:s, c:c}, function(data) {
					rowsLoaded += data.length;
					
					if (that.statusDisplayClassName) { jQuery(that.container + " ." + that.statusDisplayClassName).html("Loading complete"); }	
					jQuery.merge(that.initialData, data);
					
					var hot = jQuery(that.container + " ." + that.gridClassName).data('handsontable');
					if (hot) hot.render();
					if (that.rowCount > rowsLoaded) {
						_loadData(rowsLoaded, that.numRowsPerLoad);
					}
				});
			};
		
			if (that.rowCount > rowsLoaded) {
				_loadData(rowsLoaded, that.numRowsPerLoad);
			}
		});		
		
		return that;
		
		// --------------------------------------------------------------------------------
	};	
})(jQuery);