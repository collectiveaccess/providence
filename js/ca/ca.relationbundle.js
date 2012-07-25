/* ----------------------------------------------------------------------
 * js/ca/ca.relationbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
			//jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').data('item_type_id', item_type_id);

			//jQuery.each(types, function (i, t) {
			//	var type_direction = (t.direction) ? t.direction+ "_" : '';
			//	jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').append("<option value='" + type_direction + t.type_id + "'>" +  t.typename + "</option>");
			//});
			
			//var direction = (values['direction']) ? values['direction'] + "_" : '';
			//if (jQuery('#' + options.itemID + id + ' select option[value=' + direction + values['relationship_type_id'] + ']').attr('selected', '1').length  == 0) {
			//	jQuery('#' + options.itemID + id + ' select option[value=' + values['relationship_type_id'] + ']').attr('selected', '1');
			//}
		}
		
		options.onAddItem = function(id, options, isNew) {
			if (!isNew) { return; }
			
			var autocompleter_id = options.itemID + id + ' #' + options.fieldNamePrefix + 'autocomplete' + id;
			jQuery('#' + autocompleter_id).autocomplete(options.autocompleteUrl, 
				jQuery.extend({ minChars: ((parseInt(options.minChars) > 0) ? options.minChars : 3), matchSubset: 1, matchContains: 1, delay: 800, scroll: true, max: 100, extraParams: options.extraParams,
					formatResult: function(data, value) {
						return jQuery.trim(value.replace(/<\/?[^>]+>/gi, ''));
					}
				}, options.autocompleteOptions)
			);
			
			jQuery('#' + autocompleter_id).result(function(event, data, formatted) {
				if (options.autocompleteOptions && options.autocompleteOptions.onSelect) {
					if (!options.autocompleteOptions.onSelect(autocompleter_id, data)) { return false; }
				}
				options.select(id, data, formatted);
			});
		}
		
		options.select = function(id, data, formatted) {
			var item_id = data[1];
			var type_id = (data[2]) ? data[2] : '';
			
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