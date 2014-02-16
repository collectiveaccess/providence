/* ----------------------------------------------------------------------
 * js/ca/ca.relationbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
	$.widget("ui.relationshipLookup", $.ui.autocomplete, {
		_renderItem: function( ul, item ) {
			var li = jQuery("<li>")
				.attr("data-value", item.value)
				.append(jQuery("<a>").html(item.label))
				.appendTo(ul);
			
			if (item.id <= 0) {
				jQuery(li).find("a").removeClass().addClass("quickaddMenuItem");
				jQuery(li).removeClass().addClass("quickaddMenuItem");
			}
			return li;
		}
	});
	caUI.initRelationBundle = function(container, options) {
		options.onInitializeItem = function(id, values, options) { 
			jQuery("#" + options.itemID + id + " select").css('display', 'inline');
			var i, typeList, types = [], lists = [];
			
			var item_type_id = values['item_type_id'];
			
			// use type map to convert a child type id to the parent type id used in the restriction
			if (options.relationshipTypes && options.relationshipTypes['_type_map'] && options.relationshipTypes['_type_map'][item_type_id]) { item_type_id = options.relationshipTypes['_type_map'][item_type_id]; }
			
			if (options.relationshipTypes && (typeList = options.relationshipTypes[item_type_id])) {
				for(i=0; i < typeList.length; i++) {
					types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});
				}
			} 
			
			var extraParams = {};
			
			// restrict to lists (for ca_list_items lookups)
			if (options && options.lists && options.lists.length) {
				if (!options.extraParams) { options.extraParams = {}; }
				if(typeof options.lists != 'object') { options.lists = [options.lists]; }
				options.extraParams.lists = options.lists.join(";");
			}
			
			// restrict to types (for all lookups) - limits lookup to specific types of items (NOT relationship types)
			if (options && options.types && options.types.length) {
				if (!options.extraParams) { options.extraParams = {}; }
				if(typeof options.types != 'object') { options.types = [options.types]; }
				options.extraParams.types = options.types.join(";");
			}
			
			// look for null
			if (options.relationshipTypes && (typeList = options.relationshipTypes['NULL'])) {
				for(i=0; i < typeList.length; i++) {
					types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});
				}
			}
		}
		
		options.onAddItem = function(id, options, isNew) {
			if (!isNew) { return; }
			
			var autocompleter_id = options.itemID + id + ' #' + options.fieldNamePrefix + 'autocomplete' + id;

			jQuery('#' + autocompleter_id).relationshipLookup( 
				jQuery.extend({ minLength: ((parseInt(options.minChars) > 0) ? options.minChars : 3), delay: 800, html: true,
					source: function( request, response ) {
						$.ajax({
							url: options.autocompleteUrl,
							dataType: "json",
							data: jQuery.extend(options.extraParams, { term: request.term }),
							success: function( data ) {
								response(data);
							}
						});
					}, 
					select: function( event, ui ) {
						if (options.autocompleteOptions && options.autocompleteOptions.onSelect) {
							if (!options.autocompleteOptions.onSelect(autocompleter_id, ui.item)) { return false; }
						}
						
						if(!parseInt(ui.item.id) && options.quickaddPanel) {
							var panelUrl = options.quickaddUrl;
							//if (ui.item._query) { panelUrl += '/q/' + escape(ui.item._query); }
							if (options && options.types) {
								if(Object.prototype.toString.call(options.types) === '[object Array]') {
									options.types = options.types.join(",");
								}
								if (options.types.length > 0) {
									panelUrl += '/types/' + options.types;
								}
							}
							//if (options.fieldNamePrefix && (options.fieldNamePrefix.length > 0)) {
							//	panelUrl += '/field_name_prefix/' + options.fieldNamePrefix;
							//}
							options.quickaddPanel.showPanel(panelUrl, null, null, {q: ui.item._query, field_name_prefix: options.fieldNamePrefix});
							jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteInputID', autocompleter_id);
							jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteItemIDID', options.itemID + id + ' #' + options.fieldNamePrefix + 'id' + id);
							jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteTypeIDID', options.itemID + id + ' #' + options.fieldNamePrefix + 'type_id' + id);
							jQuery('#' + options.quickaddPanel.getPanelContentID()).data('panel', options.quickaddPanel);
							jQuery('#' + options.quickaddPanel.getPanelContentID()).data('relationbundle', that);
					
							jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteInput', jQuery("#" + options.autocompleteInputID + id).val());
					
							jQuery("#" + options.autocompleteInputID + id).val('');
							
							event.preventDefault();
							return;
						} else {
							if(!parseInt(ui.item.id) || (ui.item.id <= 0)) {
								jQuery('#' + autocompleter_id).val('');  // no matches so clear text input
								event.preventDefault();
								return;
							}
						}
						options.select(id, ui.item);
						
						jQuery('#' + autocompleter_id).val(jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, '')));
						event.preventDefault();
					},
					change: function( event, ui ) {
						// If nothing has been selected remove all content from autocompleter text input
						if(!jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'id' + id).val()) {
							jQuery('#' + autocompleter_id).val('');
						}
					}
				}, options.autocompleteOptions)
			).click(function() { this.select(); });
		}
		
		options.select = function(id, data) {
			if (!id) { id = 'new_' + (that.getCount() - 1); } // default to current "new" option
			var item_id = data.id;
			var type_id = (data.type_id) ? data.type_id : '';
			if (parseInt(item_id) < 0) { return; }
			
			jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'id' + id).val(item_id);
			jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'type_id' + id).css('display', 'inline');
			var i, typeList, types = [];
			var default_index = 0;
			
			if (jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').data('item_type_id') == type_id) {
				// noop - don't change relationship types unless you have to
			} else {
				if (options.relationshipTypes && (typeList = options.relationshipTypes[type_id])) {
					for(i=0; i < typeList.length; i++) {
						types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});
						
						if (typeList[i].is_default === '1') {
							default_index = (types.length - 1);
						}
					}
				} 
				// look for null (these are unrestricted and therefore always displayed)
				if (options.relationshipTypes && (typeList = options.relationshipTypes['NULL'])) {
					for(i=0; i < typeList.length; i++) {
						types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});
						
						if (typeList[i].is_default === '1') {
							default_index = (types.length - 1);
						}
					}
				}
				
				jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + '] option').remove();	// clear existing options
				jQuery.each(types, function (i, t) {
					var type_direction = (t.direction) ? t.direction+ "_" : '';
					jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').append("<option value='" + type_direction + t.type_id + "'>" + t.typename + "</option>");
				});
				
				// select default
				jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').attr('selectedIndex', default_index);
			
				// set current type
				jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').data('item_type_id', type_id);
			}
			that.showUnsavedChangesWarning(true);
		}
		
		options.sort = function(key) {
			var indexedValues = {};
			jQuery.each(jQuery(that.container + ' .bundleContainer .' + that.itemListClassName + ' .roundedRel'), function(k, v) {
				var id_string = jQuery(v).attr('id');
				if (id_string) {
					var indexKey;
					if(key == 'name') {
						indexKey = jQuery('#' + id_string + ' .itemName').text() + "/" + id_string;
					} else {
						if (key == 'type') {
							indexKey = jQuery('#' + id_string + ' .itemType').text() + "/" + id_string;
						} else {
							if (key == 'idno') {
								indexKey = jQuery('#' + id_string + ' .itemIdno').text() + "/" + id_string;
							} else {
								indexKey = id_string;
							}
						}
					}
					indexedValues[indexKey] = v;
				}
				jQuery(v).remove();
			});
			indexedValues = caUI.utils.sortObj(indexedValues, true);
			
			var whatsLeft = jQuery(that.container + ' .bundleContainer .' + that.itemListClassName).html();
			jQuery(that.container + ' .bundleContainer .' + that.itemListClassName).html('');
			jQuery.each(indexedValues, function(k, v) {
				jQuery(that.container + ' .bundleContainer .' + that.itemListClassName).append(v);
				var id_string = jQuery(v).attr('id');
				that.setDeleteButton(id_string);
			});
			
			jQuery(that.container + ' .bundleContainer .' + that.itemListClassName).append(whatsLeft);
			
			caUI.utils.showUnsavedChangesWarning(true);
			that._updateSortOrderListIDFormElement();
		}
	
		options.setDeleteButton = function(rowID) {
			var curRowID = rowID;
			var n = rowID.split("_").pop();
			jQuery('#' + rowID + ' .caDeleteItemButton').click(
				function() { that.deleteFromBundle(n); }
			);
		}
		
		var that = caUI.initBundle(container, options);
		
		return that;
	};	
})(jQuery);