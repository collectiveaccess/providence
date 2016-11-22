/* ----------------------------------------------------------------------
 * js/ca/ca.tableview.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2016 Whirl-i-Gig
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
			numRowsForFirstLoad: 20,
			numRowsPerLoad: 200,
			
			dataSaveUrl: null,
			dataEditUrl: null,
			
			rowHeaders: null,
			colHeaders: null,
			columns: null,
			colWidths: null,
			
			gridClassName: 'caResultsEditorContent',
			loadingClassName: 'caResultsEditorLoading',
			currentRowClassName: 'caResultsEditorCurrentRow',
			currentColClassName: 'caResultsEditorCurrentCol',
			readOnlyCellClassName: 'caResultsEditorReadOnlyCell',			// "readonly" is for any cell that cannot be edited inline (ie. readonly in Handsontable)
			nonEditableCellClassName: 'caResultsEditorNonEditableCell',		// "nonEditable" is for a cell that cannot be edited inline or with an overlay
			overlayEditorIconClassName: 'caResultsEditorOverlayIcon',
			statusDisplayClassName: 'caResultsEditorStatus',
			errorCellClassName: 'caResultsEditorErrorCell',
			
			dataEditorID: null,
			
			saveMessage: "Saving...",
			errorMessagePrefix: "[Error]",
			saveSuccessMessage: "Saved changes",
			
			lastLookupIDMap: null,
			
			restoreOriginalValueOnError: false,
			
			saveQueue: [],
			saveQueueIsRunning: false,
			dataEditorPanel: null
		}, options);
	
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------

		that.htmlRenderer = function(instance, td, row, col, prop, value, cellProperties) {
			var colSpec = that.getColumnForField(prop);
			var jTd = jQuery(td);
			
			if (!value) { value = ''; }
			jTd.empty().off('click'); // clear cell
			
			if (cellProperties.readOnly) { jTd.addClass(that.readOnlyCellClassName); }
			if (colSpec['allowEditing'] === false) { jTd.addClass(that.nonEditableCellClassName);}
			if (cellProperties.error) {
				jTd.addClass(that.errorCellClassName);	
			} else {
				jTd.removeClass(that.errorCellClassName);
			}
			
			// Add "click to edit" icon and overlay handler
			if ((colSpec['allowEditing'] === true) && (cellProperties.editMode == 'overlay')) {
				value += ' <div class="' + that.overlayEditorIconClassName + '"><i class="fa fa-pencil-square-o"></i></div>';
				
				if (that.dataEditorPanel) {
					var p = prop, r = row, c = col, element = td;
					jQuery(element).on('click', function(e) {
						if (that.getColumnForField(p)) {
							var ht = jQuery(that.container + " ." + that.gridClassName).data('handsontable');
							ht.selectCell(r,c);
							var physicalIndex = ht.sortIndex[r] ? ht.sortIndex[r][0] : r;		// need to do translation in case user has sorted on a column
							var rowData = that.initialData[physicalIndex];
							var placementID = colSpec.placement_id;
							
							that.dataEditorPanel.showPanel(that.dataEditUrl + "/bundle/" + p + "/id/" + rowData['id'] + '/row/' + r + '/col/' + c + '/pl/' + placementID);
						}
					});
				}
			}
			jTd.append(value);
			
			return td;
		};
		// --------------------------------------------------------------------------------
		
		that.colWidths = [];
		
		jQuery.each(that.columns, function(i, v) {
			that.colWidths.push(200);
			switch(that.columns[i]['type']) {
				case 'DT_SELECT':
					delete(that.columns[i]['type']);
					that.columns[i]['type'] = 'dropdown';
					that.columns[i]['allowInvalid'] = false;
					
					break;
				default:
					delete(that.columns[i]['type']);
					that.columns[i]['renderer'] = that.htmlRenderer;
					break;
			}
		});
		// --------------------------------------------------------------------------------

		that.getColumnForField = function(f, returnIndex) {
			for(var i in that.columns) {
				if (that.columns[i]['data'] === f) { return returnIndex ? i : that.columns[i]; }
			} 
			return null;
		};
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
			var itemIDToRow = {}, rowData = {};
			jQuery.each(q, function(k, v) {
				// transform list values to ids?
				var c;
				if (c = that.getColumnForField(v['change'][1])) {
					if (c.sourceMap && (v['change'][3] in c.sourceMap)) {
						v['change'][3] = c.sourceMap[v['change'][3]];
					}
				}
			
				itemIDToRow[v['id']] = v['change'][0];
				rowData[v['change'][0]] = v;
			});
			
			that.saveQueue = [];
			jQuery.post(that.dataSaveUrl, { changes: q },
				function(data) {
					console.log("data", data);
					if (parseInt(data.status) !== 0) {
						var errorMessages = [];
						jQuery.each(data.errors, function(error, bundle) {
							//jQuery.each(errorList, function(id, error) {
								var id = data['id'];
								
        						var row = parseInt(itemIDToRow[id]);
        						var col = that.getColumnForField(rowData[row]['change'][1], true);
								
								errorMessages.push(that.errorMessagePrefix + ": " + error);
								
								if (that.restoreOriginalValueOnError) {
									// Restore original value
									ht.setDataAtRowProp(itemIDToRow[id], rowData[itemIDToRow[id]]['change'][1], rowData[itemIDToRow[id]]['change'][2], 'external');
								}
								
								ht.setCellMeta(row, col, 'comment', error);	// display error on cell
								ht.setCellMeta(row, col, 'error', true);
							//});
						});
						
        				ht.render();
        				
						jQuery("." + that.statusDisplayClassName).html(errorMessages.join('; ')).show();
					} else {
						jQuery("." + that.statusDisplayClassName).html(that.saveSuccessMessage).show();
						
						if (data && data.request && data.request.changes) {
							jQuery.each(data.request.changes, function(i, c) {
								var row = parseInt(itemIDToRow[c['id']]);
								var col = that.getColumnForField(c['change'][1], true);
							
								ht.setCellMeta(row, col, 'comment', null);  // clear comment
								ht.setCellMeta(row, col, 'error', false);
							});
						}
        				ht.render();
					}
					
					setTimeout(function() { jQuery('.' + that.statusDisplayClassName).fadeOut(500); }, 5000);
					
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
		jQuery.getJSON(that.dataLoadUrl, {s:0, c:that.numRowsForFirstLoad}, function(data) {
			that.initialData = data;
			var ht = jQuery(that.container + " ." + that.gridClassName).handsontable({
				data: that.initialData,
				columns: that.columns,
				
				rowHeaders: that.rowHeaders,
				colHeaders: that.colHeaders,
				
				autoRowSize: true,
				comments: true,
	
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
					if (that.columns[col] && !that.columns[col]['readOnly']) { cellProperties['readOnly'] = !(cellProperties['editMode'] == 'inline'); }
					
					return cellProperties;
				},
				afterChange: function (change, source) {
					if (source === 'edit') { // only save edits
						for(var i in change) {
							var r = change[i][0];
							var ht = jQuery(that.container + " ." + that.gridClassName).data('handsontable');
							
							var physicalIndex =  ht.sortIndex[r] ? ht.sortIndex[r][0] : r;		// need to do translation in case user has sorted on a column
				
							var row_id = that.initialData[physicalIndex]['id'];
							that.save({'change': change[i], 'id': row_id});
						}
					}
				}
			});
			
			if (that.dataEditorID) {
				that.dataEditorPanel = caUI.initPanel({ 
					panelID: that.dataEditorID,									/* DOM ID of the <div> enclosing the panel */
					panelContentID: that.dataEditorID + "Content",				/* DOM ID of the content area <div> in the panel */
					exposeBackgroundColor: "#000000",				
					exposeBackgroundOpacity: 0.7,					
					panelTransitionSpeed: 100,						
					closeButtonSelector: "#" +  that.dataEditorID + " .caResultsComplexDataEditorPanelClose",
					center: true,
					closeOnEsc: false
				});
			}
			
			// Ensure comments on cells are visible and non-editable
			jQuery('.htCommentsContainer, .htComments').css('zIndex', '99000');
			jQuery(".htCommentTextArea").attr("disabled","disabled");
			
			// Load additional data in chunks
			var rowsLoaded = that.numRowsForFirstLoad;
			
			if (that.loadingClassName) { jQuery("." + that.loadingClassName).hide(100); }
			var _loadData = function(s, c) {
				var percentLoaded = parseInt((s/that.rowCount) * 100);
				if (percentLoaded > 100) percentLoaded = 100;
				
				if (that.statusDisplayClassName) { jQuery(that.container + " ." + that.statusDisplayClassName).html("Loading " + percentLoaded + "%").show(); }	
				jQuery.getJSON(that.dataLoadUrl, {s:s, c:c}, function(data) {
					rowsLoaded += data.length;
					
					if (that.statusDisplayClassName) { jQuery(that.container + " ." + that.statusDisplayClassName).html("Loading complete").show(); }	
					jQuery.merge(that.initialData, data);
					
					var hot = jQuery(that.container + " ." + that.gridClassName).data('handsontable');
					if (hot) hot.render();
					if (that.rowCount > rowsLoaded) {
						_loadData(rowsLoaded, that.numRowsPerLoad);
					} else {
						setTimeout(function() { jQuery('.' + that.statusDisplayClassName).fadeOut(500); }, 5000);
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