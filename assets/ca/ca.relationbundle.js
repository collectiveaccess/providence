/* ----------------------------------------------------------------------
 * js/ca.relationbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
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
		if (options.onAddItem) { options.onAddRelationshipItem = options.onAddItem; }
		
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
			
			// restrict to access point
			if (options && options.restrictToAccessPoint && options.restrictToAccessPoint.length) {
				if (!options.extraParams) { options.extraParams = {}; }
				if (typeof options.restrictToAccessPoint != 'string') { options.restrictToAccessPoint = ''; }
				options.extraParams.restrictToAccessPoint = options.restrictToAccessPoint;
			}
			
			// restrict to search expression
			if (options && options.restrictToSearch && options.restrictToSearch.length) {
				if (!options.extraParams) { options.extraParams = {}; }
				if (typeof options.restrictToSearch != 'string') { options.restrictToSearch = ''; }
				options.extraParams.restrictToSearch = options.restrictToSearch;
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
			
			jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id + ' option').remove();	// clear existing options
			jQuery.each(types, function (i, t) {
				var type_direction = (t.direction) ? t.direction+ "_" : '';
				jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id).append("<option value='" + type_direction + t.type_id + "'>" + t.typename + "</option>");
			});
			
			// select default
			jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id + " option[value=\"" + values['relationship_type_id'] + "\"], #" + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id + " option[value=\"" + values['rel_type_id'] + "\"]").prop('selected', true);
		
			// set current type
			jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id).data('item_type_id', item_type_id);
			
			if (caUI && caUI.utils && caUI.utils.showUnsavedChangesWarning) {
				// Attached change handler to form elements in relationship
				jQuery('#' + options.itemID + id + ' select, #' + options.itemID + id + ' input, #' + options.itemID + id + ' textarea').not('.dontTriggerUnsavedChangeWarning').change(function() { caUI.utils.showUnsavedChangesWarning(true); });
			}
		};
		
		options.onAddItem = function(id, options, isNew) {
			if (!isNew) { return; }
			
			var autocompleter_id = options.fieldNamePrefix + 'autocomplete' + id;

			jQuery('#' + autocompleter_id).relationshipLookup( 
				jQuery.extend({ 
					minLength: ((parseInt(options.minChars) > 0) ? options.minChars : 3), delay: 800, html: true,
					appendTo:options.container,
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
					
						// returnTextValues option allows free text to be entered; relationship autocomplete
						// values are returned as suggested test. The literal text entered is returned as the selected value
						// rather than the item_id
						if (!options.returnTextValues) {
							if(!parseInt(ui.item.id) && options.quickaddPanel) {
								that.triggerQuickAdd(ui.item._query, id);
							
								event.preventDefault();
								return;
							} else {
								if(!parseInt(ui.item.id) || (ui.item.id <= 0)) {
									jQuery('#' + autocompleter_id).val('');  // no matches so clear text input
									event.preventDefault();
									return;
								}
							}
						}
						
						options.select(id, ui.item);
						
						jQuery('#' + autocompleter_id).val(jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, '')));
						event.preventDefault();
					},
					change: function( event, ui ) {
						// If nothing has been selected remove all content from autocompleter text input
						if (!options.returnTextValues) {
								if(!jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'id' + id).val()) {
									jQuery('#' + autocompleter_id).val('');
								}
							}
						}
				}, options.autocompleteOptions)
			).on('click', null, {}, function() { this.select(); });
			
			if (options.onAddRelationshipItem) { options.onAddRelationshipItem(id, options, isNew); }
		};
		
		options.select = function(id, data) {
			if (!id) { id = 'new_' + (that.getCount() - 1); } // default to current "new" option
			var item_id = data.id;
			var type_id = (data.type_id) ? data.type_id : '';
			
			// transform with type map when available
			if(options.relationshipTypes['_type_map'][type_id]) {
				type_id = options.relationshipTypes['_type_map'][type_id];
			}
			if (parseInt(item_id) < 0) { return; }
			
			jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'id' + id).val(item_id);
			jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'type_id' + id).css('display', 'inline');
			
			var i, typeList, typesByParent = {};
			var default_type = 0;
	
			if (jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').data('item_type_id') == type_id) {
				// noop - don't change relationship types unless you have to
			} else {
				var typesOutput = {};
				if (options.relationshipTypes && (typeList = options.relationshipTypes[type_id])) {
					for(i=0; i < typeList.length; i++) {
						if(options.isSelfRelationship && options.subjectTypeID) {
							if(typeList[i].sub_type_left_id && typeList[i].sub_type_right_id) {
								if(!(
									((typeList[i].sub_type_left_id == type_id) && (typeList[i].sub_type_right_id == options.subjectTypeID))
									||
									((typeList[i].sub_type_left_id == options.subjectTypeID) && (typeList[i].sub_type_right_id == type_id))
								)) {
									continue;
								}
							}
						} else if(typeList[i].sub_type_left_id) {
							if((typeList[i].sub_type_left_id != type_id) && (typeList[i].sub_type_left_id != options.subjectTypeID)) { continue; }
						} else if(typeList[i].sub_type_right_id) {
							if((typeList[i].sub_type_right_id != type_id) && (typeList[i].sub_type_right_id != options.subjectTypeID)) { continue; }
						}
						typesOutput[typeList[i].type_id] = 1;
						if(!typeList[i].parent_id) { continue; }
						if(!typesByParent[typeList[i].parent_id]) { typesByParent[typeList[i].parent_id] = []; }
						typesByParent[typeList[i].parent_id].push(typeList[i]);
						
						if (parseInt(typeList[i].is_default) === 1) {
							default_type = (typeList[i].direction ? typeList[i].direction : '') + typeList[i].type_id;
						}
					}
				} 
				
				// look for null (these are unrestricted and therefore always displayed)
				if (options.relationshipTypes && (typeList = options.relationshipTypes['NULL'])) {
					for(i=0; i < typeList.length; i++) {
						let key = typeList[i].type_id + '/' + typeList[i].direction;
						if(typesOutput[key]) { continue };
						typesOutput[key] = typesOutput[parseInt(typeList[i].type_id)] = 1;
						
				        if(!typesByParent[typeList[i].parent_id]) { typesByParent[typeList[i].parent_id] = []; }
				        
				        var parent = that._findRelType(typeList[i].parent_id);
						if(parent && !typesOutput[parent.type_id]) { 
							let parentKey = parent.type_id + '/' + parent.direction;
							if(!typesByParent[parseInt(parent.parent_id)]) { typesByParent[parseInt(parent.parent_id)] = []; }
							typesByParent[parseInt(parent.parent_id)].push(parent);	
							typesOutput[parentKey] = typesOutput[parseInt(parent.type_id)] = 1;
						}
				        
						typesByParent[typeList[i].parent_id].push(typeList[i]);
						
						if (parseInt(typeList[i].is_default) === 1) {
							default_type = (typeList[i].direction ? typeList[i].direction : '') + typeList[i].type_id;
						}
					}
				}
				
		        var root_id = null;
		        for(var parent_id in typesByParent) {
		            if(!typesOutput[parseInt(parent_id)]) { root_id = parent_id; }
                    typesByParent[parent_id].sort(function(a,b) {
                        a.rank = parseInt(a.rank);
                        b.rank = parseInt(b.rank);
                    
                        if (a.rank != b.rank) {
                            return (a.rank > b.rank) ? 1 : ((b.rank > a.rank) ? -1 : 0);
                        } 
                        return (a.typename > b.typename) ? 1 : ((b.typename > a.typename) ? -1 : 0);
                    });
                }
                
                if(root_id > 0) {
                    types = that._flattenOptionList([], typesByParent[root_id], typesByParent);
                } else {
                    types = [
                        { type_id: -1, parent_id: null, direction: null, typename: "NO RELATIONSHIP TYPES DEFINED" }
                    ];
                }
		
				jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id + ' option').remove();	// clear existing options
				
				jQuery.each(types, function (i, t) {
					var type_direction = (t.direction) ? t.direction+ "_" : '';
					jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id).append("<option value='" + type_direction + t.type_id + "' " + (t.disabled ? "disabled='1'" : '') + ">" + t.typename + "</option>");
				});
		
				// select default
				jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id + " option[value=\"" + default_type + "\"]").prop('selected', true);
	
				// set current type
				jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id).data('item_type_id', type_id);
				
				if(jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id + " option").length == 1) {
					// Don't bother showing bundle if only one type
					jQuery('#' + options.itemID + id + ' select#' + options.fieldNamePrefix + 'type_id' + id).hide();
				}
			}
			that.showUnsavedChangesWarning(true);
		};
		
		options.sort = function(key, label) {
			if (caBundleUpdateManager) {			
				var sortDirection = jQuery('#' + that.fieldNamePrefix + 'RelationBundleSortDirectionControl').val();
				caBundleUpdateManager.reloadBundleByPlacementID(that.placementID, {'sort': key, 'sortDirection': sortDirection});
				that.loadedSort = key;
				that.loadedSortDirection = sortDirection;
			}
			return;
		};
	
		options.setDeleteButton = function(rowID) {
			var curRowID = rowID;
			if (!rowID) { return; }
			var n = rowID.split("_").pop();
			jQuery('#' + rowID + ' .caDeleteItemButton').on('click', null, {},
				function(e) { that.deleteFromBundle(n); e.preventDefault(); return false; }
			);
		};
		
		if(options.forceNewRelationships && options.forceNewRelationships.length > 0) {
			options['showEmptyFormsOnLoad'] = false;
		}
		
		var that = caUI.initBundle(container, options);
		
		
		that._flattenOptionList = function(acc, list, hier) {
		    for(var i in list) {
		        acc.push(list[i]);
		        if(hier[list[i].type_id]) {
		            acc = that._flattenOptionList(acc, hier[list[i].type_id], hier);
		        }
		    }  
		    return acc;
		};
		
		that._findRelType = function(type_id) {
			if (options.relationshipTypes) {
				for(var t in options.relationshipTypes) {
					for(var i in options.relationshipTypes[t]) {
						if(options.relationshipTypes[t][i].type_id == type_id) {
							return options.relationshipTypes[t][i];
						}
					}
				}
			}
			return null;
		};
		
		
		if (!that.forceNewRelationships) { that.forceNewRelationships = []; }
		jQuery.each(that.forceNewRelationships, function(k, v) {
			let initalizedCount = 0;
			v['_handleAsNew'] = true;
			if(that.types && that.types.length && that.types[0] && !that.types.includes(v['type_id'])) { 
				return; 
			}
			if(that.relationshipTypes && that.relationshipTypes.length && that.relationshipTypes[0] && !that.relationshipTypes.includes(v['relationship_type_id'])) { 
				console.log(v, that.relationshipTypes);
				return; 
			}
			
			that.addToBundle('new_' + k, v, true);
			if(that.select) {
				that.select('new_' + k, v);
			}
			initalizedCount++;
		});
		
		that.triggerQuickAdd = function(q, id, params=null, opts=null) {
			var autocompleter_id = options.fieldNamePrefix + 'autocomplete' + id;
			var panelUrl = options.quickaddUrl;
			if (options && options.types) {
				if(Object.prototype.toString.call(options.types) === '[object Array]') {
					options.types = options.types.join(",");
				}
				if (options.types.length > 0) {
					panelUrl += '/types/' + options.types;
				}
			}
			
			if (params && (typeof params === 'object')) {
				for(var k in params) {
					panelUrl += '/' + k + '/' + params[k];
				}
			}
			
			options.quickaddPanel.showPanel(panelUrl, null, null, {q: q, field_name_prefix: options.fieldNamePrefix});
			jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteRawID', id);
			jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteInputID', autocompleter_id);
			jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteItemIDID', options.itemID + id + ' #' + options.fieldNamePrefix + 'id' + id);
			jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteTypeIDID', options.itemID + id + ' #' + options.fieldNamePrefix + 'type_id' + id);
			jQuery('#' + options.quickaddPanel.getPanelContentID()).data('panel', options.quickaddPanel);
			jQuery('#' + options.quickaddPanel.getPanelContentID()).data('relationbundle', that);

			jQuery('#' + options.quickaddPanel.getPanelContentID()).data('autocompleteInput', jQuery("#" + options.autocompleteInputID + id).val());

			jQuery("#" + options.autocompleteInputID + id).val('');
			
			if(opts && opts.addBundle) {
				that.addToBundle(id);
			}
		};
	
		return that;
	};	
})(jQuery);
