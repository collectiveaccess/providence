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
			unsavedChangesID: 'unsavedChanges',
			unsavedChangesMessage: 'Unsaved changes!', 
			inventoryCountsID: 'inventoryCounts',
			
			itemTemplateClass: "{idno}",
			
			inventorySetStatusButtonClass: '',
			
			displayTemplate: null,
			editorTemplateClass: null,
			
			inventoryContainerElementCode: null,
			inventoryFoundOptions: {},
			inventoryFoundOptionsDisplayText: {},
			inventoryFoundIcons: {},
			inventoryFoundBundle: null,
			
			inventoryFilterInputID: null,
			
			sorts: null,
			
			lookupURL: null,
			itemListURL: null,
			addItemToInventoryURL: null,
			removeItemFromInventoryURL: null,
			editInventoryItemsURL: null,			// url of inventory item editor (without item_id parameter key or value)
			
			editInventoryItemButton: null,			// html to use for edit inventory item button
			deleteInventoryItemButton: null,		// html to use for delete inventory item button
			
			initialValues: null,					// initial values to display
			items: [],								// currently loaded values
			
			itemsWithForms: {},						// item_ids for items with form opened
			
			counts: {},								// found/not found/not checked item counts
			
			currentFilters: {},
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
						jQuery.getJSON(that.addItemToInventoryURL, {'set_id': that.inventoryID, 'table_num': that.table_num, 'row_id': ui.item.id } , 
							function(data) { 
								if(data.status != 'ok') { 
									alert("Error adding item");
								} else {
									that.getItemList(0, 10000, null, null);
									jQuery('#' + that.inventoryItemAutocompleteID).val('');
														
									if(caBundleUpdateManager) { caBundleUpdateManager.reloadInspector(); }
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
			
			if(that.inventoryFilterInputID) {
				jQuery('#' + that.inventoryFilterInputID).on('keyup', function(e) {
					const s = jQuery('#' + that.inventoryFilterInputID).val().trim();
					if(s.length > 1) {
						that.refresh(null, {'search': s});
					} else if(s.length == 0) {
						that.refresh()
					}
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
				let items = [];
				for(let i in resp.data.order) {
					let d = resp.data.items[resp.data.order[i]];
					// preserve form data if defined
					for(let x in that.items) {
						if(that.itemsWithForms[resp.data.order[i]] && (that.items[x]['item_id'] == resp.data.order[i])) {
							const re = new RegExp('^' + that.inventoryContainerElementCode);
							for(let k in that.items[x]) {
								if(k.match(re) || (k === '_INVENTORY_STATUS_') || (k === '_INVENTORY_STATUS_ICON_')) {
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
				that._setUnsavedWarning(true);
			}
			return true;
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
		that.refresh = function(form_item_id=null, filters=null) {
			jQuery('#' + that.inventoryItemListID).empty();
			
			let c = 1;
			jQuery.each(that.items, function(k, v) {
				v['n'] = c;
				v['fieldNamePrefix'] = that.fieldNamePrefix;
				
				// filter display
				that.currentFilters = filters;
				if(filters) {
					if(filters['status'] && (Array.isArray(filters['status'])) && (filters['status'].length > 0)) {
						if(!filters['status'].includes('ALL')) {
							if(!filters['status'].includes(v['_INVENTORY_STATUS_'])) {
								return;
							}
						}
					}
					if(filters['search']) {
						filters['search'] = filters['search'].toLowerCase();
						let found = false;
						for(let kx in v) {
							if((typeof v[kx] === 'string') && (v[kx].toLowerCase().includes(filters['search']))) {
								found = true;
								break;
							}
						}
						if(!found) { return; }
					}
				}
			
				// replace values in template
				let item = jQuery('#' + that.container + ' textarea.' + that.itemTemplateClass).template(v);
				
				if((that.itemsWithForms[v['item_id']] === true) || (form_item_id && (form_item_id == v['item_id']))) {
					let editor = jQuery('#' + that.container + ' textarea.' + that.editorTemplateClass).template(v);
					console.log(editor, v);
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
									that.items[i]['_INVENTORY_STATUS_ICON_'] = that.inventoryFoundIcons[that.items[i]['_INVENTORY_STATUS_']] ?? that.inventoryFoundIcons['NOT_CHECKED'];
									that.updateCounts();
								}
								break;
							}
						}
						
						that._setUnsavedWarning(true);
					});
					
					// Editor "done" button
					jQuery(editor).find('.inventoryItemEditorDoneButton').on('click', function(e) {
						that.refresh(null, that.currentFilters);
						e.preventDefault();
					});
					
					if(!(form_item_id && (form_item_id == v['item_id']))) {
						jQuery(item).find('.inventoryItemEditorContainer').append(editor).hide();
						jQuery(item).find('.inventoryItemDescription').show();
					} else {
						jQuery(item).find('.inventoryItemEditorContainer').append(editor).show();
						jQuery(item).find('.inventoryItemDescription').hide();
					}
					that.itemsWithForms[form_item_id] = true;
				}
				
				if(that.inventorySetStatusButtonClass) {
					jQuery(item).find('.' + that.inventorySetStatusButtonClass).on('click', function(e) {
						const id = jQuery(this).attr('id');
						const item_id = id.match(/^inventory_([\d]+)/)[1] ?? null;
						that.refresh(item_id, that.currentFilters);
						e.preventDefault();
					});
				}
					
				// Delete item button
				jQuery(item).find('.inventoryItemDeleteButton').on('click', function(e) {
						const id = jQuery(this).attr('id');
						const item_id = id.match(/^inventory_([\d]+)/)[1] ?? null;
						jQuery.getJSON(that.removeItemFromInventoryURL, {'set_id': that.inventoryID, 'table_num': that.table_num, 'item_id': item_id } , 
							function(data) { 
								if(data.status != 'ok') { 
									alert("Error adding item");
								} else {
									that.getItemList(0, 10000, null, null);
									jQuery('#' + that.inventoryItemAutocompleteID).val('');
														
									if(caBundleUpdateManager) { caBundleUpdateManager.reloadInspector(); }
								}
							}
						);
				});

				jQuery('#' + that.inventoryItemListID).append(item);
				c++;
			});
			that.updateCounts();
		}
		
		// ------------------------------------------------------------------------------------
		//
		//
		//
		that._setUnsavedWarning = function(show=true) {
			if(caUI.utils.showUnsavedChangesWarning) { caUI.utils.showUnsavedChangesWarning(show);}
			if(that.unsavedChangesMessage) { 
				if(show) {
					jQuery('#' + that.unsavedChangesID).show();
				} else {
					jQuery('#' + that.unsavedChangesID).hide();
				}
			}
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
				let total = 0;
				['FOUND', 'NOT_FOUND', 'NOT_CHECKED'].forEach(function(k) {
					if(that.counts[k] > 0) { 
						count_list.push("<div><a href='#' id='" + that.container + '_filter_' + k + "'>" + that.inventoryFoundIcons[k] + ' ' + that.inventoryFoundOptionsDisplayText[k] + '</a>: ' + that.counts[k] + "</div>"); 
						total += that.counts[k];
					}
				});
				count_list.push("<div><a href='#' id='" + that.container + '_filter_' + 'ALL' + "'>" + that.inventoryFoundIcons['ALL'] + ' ' + that.inventoryFoundOptionsDisplayText['ALL'] + '</a>: ' + total + "</div>");
			
				jQuery('#' + that.inventoryCountsID).html(count_list.join('  '));
				
				['FOUND', 'NOT_FOUND', 'NOT_CHECKED', 'ALL'].forEach(function(k) {
					jQuery('#' + that.container + '_filter_' + k).on('click', function(e) {
						that.refresh(null, {'status': [k], 'search': jQuery('#' + that.inventoryFilterInputID).val().trim()});
						e.preventDefault();
					});
					
					if((k !== 'ALL') && that.currentFilters && that.currentFilters['status'] && (that.currentFilters['status'].includes(k))) {
						jQuery('#' + that.container + '_filter_' + k).addClass('inventoryActiveFilter');
					} else { 
						jQuery('#' + that.container + '_filter_' + k).removeClass('inventoryActiveFilter');
					}
				});
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
		
		that.initInventoryEditor();
		return that;
	};
})(jQuery);
