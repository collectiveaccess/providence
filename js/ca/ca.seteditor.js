/* ----------------------------------------------------------------------
 * js/ca/ca.seteditor.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 
//
// Note: requires jQuery UI.Sortable
//
 
var caUI = caUI || {};

(function ($) {
	caUI.seteditor = function(options) {
		var that = jQuery.extend({
			setID: null,
			table_num: null,
			fieldNamePrefix: null,
			setEditorID: 'setItemEditor',
			setItemListID: 'setItemList',	
			setNoItemWarningID: 'setNoItemsWarning',
			setItemAutocompleteID: 'setItemAutocompleter',
			rowIDListID: 'setRowIDList',
			
			lookupURL: null,
			itemInfoURL: null,
			editSetItemsURL: null,			// url of set item editor (without item_id parameter key or value)
			
			editSetItemButton: null,		// html to use for edit set item button
			
			initialValues: null,
			initialValueOrder: null,			/* id's to list display list in; required because Google Chrome doesn't iterate over keys in an object in insertion order [doh] */	
		
		}, options);
		
		
		// ------------------------------------------------------------------------------------
		that.initSetEditor = function() {
			
			// setup autocompleter
			jQuery('#' + that.setItemAutocompleteID).autocomplete(
				{
					source: that.lookupURL,
					minLength: 3, max: 50, html: true,
					select: function(event, ui) {
						jQuery.getJSON(that.itemInfoURL, {'set_id': that.setID, 'table_num': that.table_num, 'row_id': ui.item.id} , 
							function(data) { 
								if(data.status != 'ok') { 
									alert("Error getting item information");
								} else {
									that.addItemToSet(data.item_id, data, true, true);
									jQuery('#' + that.setItemAutocompleteID).attr('value', '');
								}
							}
						);
					}
				}
			);
			
			// add initial items
			if (that.initialValues) {
				jQuery.each(that.initialValueOrder, function(k, v) {
					that.addItemToSet(that.initialValues[v].row_id, that.initialValues[v], false);
				});
			}
			
			that.refresh();
		}
		// ------------------------------------------------------------------------------------
		// Adds item to set editor display
		that.addItemToSet = function(rowID, valueArray, isNew, prepend) {
			if (isNew) { 
				var id_list = that.getRowIDs();
				if(jQuery.inArray(rowID, id_list) != -1) {	// don't allow dupes
					return false;
				}
			}
			var repHTML = valueArray.representation_url || '';
			if (repHTML && that.editSetItemsURL && valueArray['item_id']) {
				repHTML = '<div style="background-image: url(\'' +  repHTML + '\'); width: ' + valueArray.representation_width + 'px; height: ' + valueArray.representation_height + 'px;"> </div>';
			}
			
			var editLinkHTML = '';
			if (that.editSetItemButton) {
				editLinkHTML = '<div style="float: left;"><a href="' + that.editSetItemsURL + '/item_id/' + valueArray['item_id'] + '" title="' + that.editSetItemToolTip +'" class="setItemEditButton">' + that.editSetItemButton + '</a></div> ';
			}
			
			var itemHTML = "<li class='setItem' id='" + that.fieldNamePrefix + "setItem" + rowID +"'><div id='" + that.fieldNamePrefix + "setItemContainer" + rowID + "' class='imagecontainer'><div class='remove'><a href='#' class='setDeleteButton' id='" + that.fieldNamePrefix + "setItemDelete" + rowID + "'>X</a></div><div class='setItemThumbnail'>" + editLinkHTML + repHTML + "</div><div class='setItemCaption'>" + valueArray.set_item_label + " [<span class='setItemIdentifier'>" + valueArray.idno + "</span>]</div><div class='setItemIdentifierSortable'>" + valueArray.idno_sort + "</div></div><br style='clear: both;'/></li>";
			
			if (prepend) {
				jQuery('#' + that.fieldNamePrefix + that.setItemListID).prepend(itemHTML);
			} else {
				jQuery('#' + that.fieldNamePrefix + that.setItemListID).append(itemHTML);
			}
			
			that.setDeleteButton(rowID);
			
			if (isNew) { 
				that.refresh(); 
				caUI.utils.showUnsavedChangesWarning(true);
			}
			return true;
		}
		// ------------------------------------------------------------------------------------
		that.setDeleteButton = function(rowID) {
			var curRowID = rowID;
			jQuery('#' + that.fieldNamePrefix + "setItemDelete" + rowID).click(
				function() {
					var id = curRowID;
					jQuery('#' + that.fieldNamePrefix + "setItem" + id).fadeOut(250, function() { 
						jQuery('#' + that.fieldNamePrefix + "setItem" + id).remove(); 
						that.refresh();
					});
					caUI.utils.showUnsavedChangesWarning(true);
					return false;
				}
			);
		}
		// ------------------------------------------------------------------------------------
		// Returns list of item row_ids in user-defined order
		that.getRowIDs = function() {
			var id_list = [];
			jQuery.each(jQuery('#' + that.fieldNamePrefix + that.setItemListID + ' .setItem'), function(k, v) {
				var id_string = jQuery(v).attr('id');
				if (id_string) { 
					id_list.push(id_string.replace(that.fieldNamePrefix + 'setItem', ''));
				}
			});
			return id_list;
		}
		// ------------------------------------------------------------------------------------
		that.refresh = function() {
			jQuery('#' + that.fieldNamePrefix + that.setItemListID).sortable({ opacity: 0.7, 
				revert: true, 
				scroll: true,
				update: function() {
					that.refresh();
					caUI.utils.showUnsavedChangesWarning(true);
				}
			});
			
			// set warning if no items on load
			jQuery('#' + that.fieldNamePrefix + that.setItemListID + ' li.setItem').length ? jQuery('#' + that.fieldNamePrefix + that.setNoItemWarningID).hide() : jQuery('#' + that.fieldNamePrefix + that.setNoItemWarningID).show();
			jQuery('#' + that.rowIDListID).val(that.getRowIDs().join(';'));
		}
		// ------------------------------------------------------------------------------------
		that.sort = function(key) {
			var indexedValues = {};
			var indexKeyClass = null;
			switch(key) {
				case 'name':
					indexKeyClass = 'setItemCaption';
					break;
				case 'idno':
					indexKeyClass = 'setItemIdentifierSortable';
					break;
				default:
					return false;
					break;
			}
			
			jQuery.each(jQuery('#' + that.fieldNamePrefix + that.setItemListID + ' .setItem'), function(k, v) {
				var id_string = jQuery(v).attr('id');
				if (id_string) {
					var indexKey = jQuery('#' + id_string + ' .imagecontainer .' + indexKeyClass).text();
					indexedValues[indexKey] = v;
				}
				jQuery(v).remove();
			});
			indexedValues = caUI.utils.sortObj(indexedValues, true);
			
			jQuery.each(indexedValues, function(k, v) {
				jQuery('#' + that.fieldNamePrefix + that.setItemListID).append(v);
				var id_string = jQuery(v).attr('id');
				var id = id_string.replace(that.fieldNamePrefix + 'setItem', '');
				that.setDeleteButton(id);
			});
			
			caUI.utils.showUnsavedChangesWarning(true);
			that.refresh();
		}
		// ------------------------------------------------------------------------------------
		
		// ------------------------------------------------------------------------------------
		
		that.initSetEditor();
		return that;
	};
})(jQuery);