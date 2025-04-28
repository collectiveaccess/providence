/* ----------------------------------------------------------------------
 * js/ca.inventoryeditor.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
	caUI.inventoryeditor = function(options) {
		var that = jQuery.extend({
			container: null,
			inventoryID: null,
			table_num: null,
			fieldNamePrefix: null,
			inventoryEditorID: 'inventoryItemEditor',
			inventoryItemListID: 'inventoryItemList',	
			inventoryNoItemWarningID: 'inventoryNoItemsWarning',
			inventoryItemAutocompleteID: 'inventoryItemAutocompleter',
			sortControlID: 'inventorySortControl',
			
			itemTemplateClass: "{idno}",
			
			displayTemplate: null,
			editorTemplateClass: null,
			
			sorts: null,
			
			lookupURL: null,
			itemListURL: null,
			itemInfoURL: null,
			editInventoryItemsURL: null,			// url of inventory item editor (without item_id parameter key or value)
			
			editInventoryItemButton: null,		// html to use for edit inventory item button
			deleteInventoryItemButton: null,		// html to use for delete inventory item button
			
			initialValues: null,			// initial values to display
			items: [],						// currently loaded values
			
			debug: true
		}, options);
		
		
		// ------------------------------------------------------------------------------------
		//
		//
		//
		that.initInventoryEditor = function() {
			// setup autocompleter
			jQuery('#' + that.inventoryItemAutocompleteID).autocomplete(
				{
					source: that.lookupURL + "?quickadd=0&noInline=1&set_id=" + that.inventoryID,
					minLength: 3, max: 50, html: true,
					select: function(event, ui) {
						jQuery.getJSON(that.itemInfoURL, {'set_id': that.setID, 'table_num': that.table_num, 'row_id': ui.item.id, 'displayTemplate': that.displayTemplate} , 
							function(data) { 
								if(data.status != 'ok') { 
									alert("Error getting item information");
								} else {
									that.addItemToInventory(data.row_id, data, true, true);
									jQuery('#' + that.inventoryItemAutocompleteID).val('');
								}
							}
						);
					}
				}
			);
			
			// Set up sort control
			if(that.sortControlID) {
				jQuery('#' + that.sortControlID).on('change', that.sort);
			}
			
			// add initial items
			if (that.initialValues) {
				jQuery.each(that.initialValues, function(k, v) {
					that.addItemToInventory(v.row_id, v, false);
				});
			}
			
			that.refresh();
		}
		// ------------------------------------------------------------------------------------
		//
		// Load items via ajax call
		//
		that.getItemList = function(start, length, sort='', sortDirection='') {
			jQuery.getJSON(that.itemListURL, {
				'set_id': that.inventoryID, 'start': start, 'length': length, 
				'sort': sort, 'sortDirection': sortDirection
			}, function(resp) {
				that.items = [];
				for(let i in resp.data.order) {
					that.items.push(resp.data.items[resp.data.order[i]]);
				}
				that.refresh();
			});
		}
		// ------------------------------------------------------------------------------------
		//
		// Show full-window overlap editing interface
		//
		that.showGrid = function() {
			//jQuery('#caResultsEditorPanel').empty();
			jQuery('#caMediaPanel').show();
			
			jQuery.each(that.items, function(k, v) {
				jQuery('#caMediaPanelContentArea').append('<div style="color: white;">' + v['set_item_label']+ '</div>');
			});
		}
		
		// ------------------------------------------------------------------------------------
		// Adds item to inventory editor display
		that.addItemToInventory = function(rowID, valueArray, isNew, prepend) {
			if (isNew) { 
				var rowIDs = that.getRowIDs();
				if(jQuery.inArray(rowID, rowIDs) != -1) {	// don't allow dupes
					return false;
				}
			}
			that.items.push(valueArray);
			
			if (isNew) { 
				that.refresh(); 
				caUI.utils.showUnsavedChangesWarning(true);
			}
			return true;
		}
		// ------------------------------------------------------------------------------------
		//
		//
		//
		that.inventoryDeleteButton = function(rowID, itemID) {
			// var rID = rowID + ((itemID > 0) ? "_" + itemID : "");
// 			jQuery('#' + that.fieldNamePrefix + "InventoryItemDelete" + itemID).click(
// 				function() {
// 					jQuery('#' + that.fieldNamePrefix + "InventoryItem" + rID).fadeOut(250, function() { 
// 						jQuery('#' + that.fieldNamePrefix + "InventoryItem" + rID).remove(); 
// 						that.refresh();
// 					});
// 					caUI.utils.showUnsavedChangesWarning(true);
// 					return false;
// 				}
// 			);
		}
		// ------------------------------------------------------------------------------------
		// Returns list of subject ids (Eg. if inventory is of objects, ids are object_ids)
		//
		that.getRowIDs = function() {
			let rowIDs = that.items.map((v) => parseInt(v['row_id']));
			return rowIDs;
		}
		// ------------------------------------------------------------------------------------
		//
		// Refresh item list display
		//
		that.refresh = function() {
			jQuery('#' + that.inventoryItemListID).empty();
			jQuery.each(that.items, function(k, v) {
				console.log(v);
				// replace values in template
				let item = jQuery('#' + that.container + ' textarea.' + that.itemTemplateClass).template(v);
				jQuery('#' + that.inventoryItemListID).append(item);
				
				if(k==0) {
					let editor = jQuery('#' + that.container + ' textarea.' + that.editorTemplateClass).template(v);
					//console.log(editor);
					jQuery('#' + that.inventoryItemListID).append(editor);
				}

			});
			
		}
		// ------------------------------------------------------------------------------------
		//
		//
		//
		that.sort = function(e) {
			let sortBundle =  jQuery(e.currentTarget).val();
			let sortDirection = 'asc';
			if(that.debug) { console.log("[DEBUG] Sort set to ", sortBundle, sortDirection); }
			
			that.getItemList(0, 10000, sortBundle, sortDirection);
		}
		// ------------------------------------------------------------------------------------
		
		// ------------------------------------------------------------------------------------
		
		that.initInventoryEditor();
		return that;
	};
})(jQuery);
