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
			inventoryCountsID: 'inventoryCounts',
			
			itemTemplateClass: "{idno}",
			
			inventorySetStatusButtonClass: '',
			
			displayTemplate: null,
			editorTemplateClass: null,
			
			inventoryContainerElementCode: null,
			inventoryFoundOptions: {},
			inventoryFoundBundle: null,
			
			sorts: null,
			
			lookupURL: null,
			itemListURL: null,
			itemInfoURL: null,
			editInventoryItemsURL: null,			// url of inventory item editor (without item_id parameter key or value)
			
			editInventoryItemButton: null,			// html to use for edit inventory item button
			deleteInventoryItemButton: null,		// html to use for delete inventory item button
			
			initialValues: null,					// initial values to display
			items: [],								// currently loaded values
			
			itemsWithForms: {},						// item_ids for items with form opened
			
			counts: {},								// found/not found/not checked item counts
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
			
			that.inventoryFoundBundleProc = that.inventoryFoundBundle.replace(/\./, '_');
			
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
				//that.items = [];
				let items = [];
				for(let i in resp.data.order) {
					let d = resp.data.items[resp.data.order[i]];
					// preserve form data if defined
					for(let x in that.items) {
						if(that.itemsWithForms[resp.data.order[i]] && (that.items[x]['item_id'] == resp.data.order[i])) {
							const re = new RegExp('^' + that.inventoryContainerElementCode);
							for(let k in that.items[x]) {
								if(k.match(re) || (k === '_INVENTORY_STATUS_')) {
									d[k] = that.items[x][k];
								}
							}
							break;
						}
					}
					items.push(d);
				}
				that.items = items;
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
		that.refresh = function(form_item_id=null) {
			jQuery('#' + that.inventoryItemListID).empty();
			
			jQuery.each(that.items, function(k, v) {
				// replace values in template
				let item = jQuery('#' + that.container + ' textarea.' + that.itemTemplateClass).template(v);
				
				if((that.itemsWithForms[v['item_id']] === true) || (form_item_id && (form_item_id == v['item_id']))) {
					let editor = jQuery('#' + that.container + ' textarea.' + that.editorTemplateClass).template(v);
				
					// set <select> elements
					let selects = jQuery(editor).find("select").each(function(ksel, vsel) {
						const id = jQuery(vsel).attr('id');
						const fn = id.match(/^inventory_[\d]+_(.*)$/)[1] ?? null;
						if(fn) {
							jQuery(editor).find('#' + id + ' option[value="' + v[fn] + '"]').attr('selected','selected');
						}
					});
					
					jQuery(editor).find("input,select,textarea").on("change", function(e) {
						const id = jQuery(this).attr('id');
						const m = id.match(/^inventory_([\d]+)_(.*)$/);
						const item_id = m[1];
						const fld = m[2];
						const v = jQuery(this).val();
						
						for(let i in that.items) {
							if(that.items[i]['item_id'] == item_id) {
								that.items[i][fld] = v;
								if(fld == that.inventoryFoundBundleProc) {
									that.items[i]['_INVENTORY_STATUS_'] = that.inventoryFoundOptions[v] ?? 'NOT_CHECKED';
									that.updateCounts();
								}
								break;
							}
						}
					});
					
					if(!(form_item_id && (form_item_id == v['item_id']))) {
						jQuery(editor).hide();
					}
				
					jQuery(item).append(editor);
					that.itemsWithForms[form_item_id] = true;
					
				}
				if(that.inventorySetStatusButtonClass) {
					jQuery(item).find('.' + that.inventorySetStatusButtonClass).on('click', function(e) {
						const id = jQuery(this).attr('id');
						const item_id = id.match(/^inventory_([\d]+)/)[1] ?? null;
						that.refresh(item_id);
					});
				}

				jQuery('#' + that.inventoryItemListID).append(item);
			});
			that.updateCounts();
		}
		// ------------------------------------------------------------------------------------
		//
		//
		//
		that.updateCounts = function(e) {
			let count_list = [];
			if(that.inventoryCountsID) {
				that.counts = { 'FOUND': 0, 'NOT_FOUND': 0, 'NOT_CHECKED': 0 };
				jQuery.each(that.items, function(k, v) {
					that.counts[v['_INVENTORY_STATUS_']]++;
				});
				['FOUND', 'NOT_FOUND', 'NOT_CHECKED'].forEach(function(k) {
					if(that.counts[k] > 0) { 
						count_list.push(k + ": " +that.counts[k]); 
					}
				});
				
				jQuery('#' + that.inventoryCountsID).html(count_list.join(' - '));
			}
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
