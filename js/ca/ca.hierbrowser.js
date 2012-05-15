/* ----------------------------------------------------------------------
 * js/ca/ca.hierbrowser.js
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
	caUI.initHierBrowser = function(container, options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			container: container,
			
			uiStyle: 'horizontal',	// 'horizontal' [default] means side-to-side scrolling browser; 'vertical' means <select>-based vertically oriented browser.
									//  The horizontal browser requires more space but it arguably easier and more pleasant to use with large hierarchies.
									//  The vertical browser is more compact and works well with smaller hierarchies 
			
			levelDataUrl: '',
			initDataUrl: '',
			editUrl: '',
			
			editUrlForFirstLevel: '',
			editDataForFirstLevel: '',	// name of key in data to use for item_id in first level, if different from other levels
			dontAllowEditForFirstLevel: false,
			
			name: options.name ? options.name : container.replace(/[^A-Za-z0-9]+/, ''),
			levelWidth: 230,
			browserWidth: 500,
			
			readOnly: false,	// if set to true, no navigation is allowed
			
			initItemID: null,		// if set, hierarchy opens with specified item_id selected
			defaultItemID: null,	// set to default value to show when no initItemID is set; note that initItemID is an ID to open with *and select.* defaultItemID merely specifies an item to open with, but not select.
			useAsRootID: null,		// if set to an item_id, that is used at the root of the display hierarchy
			
			className: 'hierarchyBrowserLevel',
			classNameSelected: 'hierarchyBrowserLevelSelected',
			classNameContainer: 'hierarchyBrowserContainer',
			
			currentSelectionDisplayID: '',
			currentSelectionDisplayFormat: '%1',
			currentSelectionIDID: '',
			
			allowExtractionFromHierarchy: false,
			extractFromHierarchyButtonIcon: null, 
			extractFromHierarchyMessage: null,
			
			selectOnLoad: false,
			onSelection: null,		/* function to call whenever an item is selected; passed item_id, parent_id, name, formatted display string and type_id */
			
			displayCurrentSelectionOnLoad: true,
			typeMenuID: '',
			
			indicatorUrl: '',
			editButtonIcon: '',
			
			hasChildrenIndicator: 'has_children',	/* name of key in data to use to determine if an item has children */
			alwaysShowChildCount: true,
			
			levelLists: [],
			selectedItemIDs: [],
			
			_numOpenLoads: 0,
			_openLoadsForLevel:[]
		}, options);
		
		if (!that.levelDataUrl) { 
			alert("No level data url specified for " + that.name + "!");
			return null;
		}
		
		if (!jQuery.inArray(that.uiStyle, ['horizontal', 'vertical'])) { that.uiStyle = 'horizontal'; }		// verify the uiStyle is valid
		
if (that.uiStyle == 'horizontal') {
		// create scrolling container
		jQuery('#' + that.container).append("<div class='" + that.classNameContainer + "' id='" + that.container + "_scrolling_container'></div>");
} else {
	if (that.uiStyle == 'vertical') {
		jQuery('#' + that.container).append("<div class='" + that.classNameContainer + "' id='" + that.container + "_select_container'></div>");
	}
}

		if (that.typeMenuID) {
			jQuery('#' + that.typeMenuID).hide();
		}
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.setUpHierarchy = function(item_id) {
			if (!item_id) { that.setUpHierarchyLevel(0, that.useAsRootID ? that.useAsRootID : 0, 1); return; }
			that.levelLists = [];
			that.selectedItemIDs = [];
			jQuery.getJSON(that.initDataUrl, { id: item_id}, function(data) {
				if (data.length) {
					that.selectedItemIDs = data.join(';').split(';');
					
					if (that.useAsRootID > 0) {
						that.selectedItemIDs.shift();
						if (jQuery.inArray(that.useAsRootID, data) == -1) {
							data.unshift(that.useAsRootID);
						}
					} else {
						data.unshift(0);
					}
				} else {
					data = [that.useAsRootID ? that.useAsRootID : 0];
				}
				var l = 0;
				
				jQuery.each(data, function(i, id) {
					that.setUpHierarchyLevel(i, id, 1, item_id);
					l++;
				});
				
				if (that.uiStyle == 'horizontal') {
					jQuery('#' + that.container + '_scrolling_container').animate({scrollLeft: l * that.levelWidth}, 500);
				}
			});
		}
		// --------------------------------------------------------------------------------
		that.clearLevelsStartingAt = function(level) {
			var l = level;
			
			// remove all level divs above the current one
			while(jQuery('#hierBrowser_' + that.name + '_' + l).length > 0) {
				jQuery('#hierBrowser_' + that.name + '_' + l).remove();
				that.levelLists[l] = undefined;
				l++;
			}
			
		}
		// --------------------------------------------------------------------------------
		that.setUpHierarchyLevel = function (level, item_id, is_init, selected_item_id) {
			that._numOpenLoads++;
			if (that._openLoadsForLevel[level]) { return null; }	// load is already open for this level
			that._openLoadsForLevel[level] = true;
			
			// Remove any levels *after* the one we're populating
			that.clearLevelsStartingAt(level);
			
			if (!item_id) { item_id = 0; }
			if (!is_init) { is_init = 0; }

			// Create div to enclose new level
			var newLevelDivID = 'hierBrowser_' + that.name + '_' + level;
			var newLevelListID = 'hierBrowser_' + that.name + '_list_' + level;
			
			if(!is_init) { jQuery('#' + newLevelDivID).remove(); }
			
if (that.uiStyle == 'horizontal') {		
			var newLevelDiv = "<div class='" + that.className + "' style='left:" + (that.levelWidth * level) + "px;' id='" + newLevelDivID + "'></div>";
			
			// Create new <ul> to display list of items
			var newLevelList = "<ul class='" + that.className + "' id='" + newLevelListID + "'></ul>";
			
			jQuery('#' + that.container + '_scrolling_container').append(newLevelDiv);
			jQuery('#' + newLevelDivID).data('level', level);
			jQuery('#' + newLevelDivID).data('parent_id', item_id);
			jQuery('#' + newLevelDivID).append(newLevelList);
			
			if (that.indicatorUrl) {
				var indicator = document.createElement('img');
				indicator.src = that.indicatorUrl;
				indicator.className = '_indicator';
				indicator.style.position = 'absolute';
				indicator.style.left = '50%';
				indicator.style.top = '50%';
				jQuery('#' + newLevelDivID).append(indicator);
			}
} else {
	if (that.uiStyle == 'vertical') {
			// Create new <select> to display list of items
			var newLevelList = "<select class='" + that.className + "' id='" + newLevelListID + "' name='" + newLevelListID + "' style='width: "+ (that.browserWidth - 32) + "px;'></select>";	// allow 24 pixels for spinner
			var newLevelDiv = "<div class='" + that.className + "' id='" + newLevelDivID + "'>" + newLevelList;
			if (level > 0) { newLevelDiv += "<br/>⬆</div>"; }
			
			jQuery('#' + that.container + '_select_container').prepend(newLevelDiv);
			jQuery('#' + newLevelDivID).data('level', level);
			jQuery('#' + newLevelDivID).data('parent_id', item_id);
			jQuery('#' + newLevelListID).change(function() {
				var item_id = jQuery("#" + newLevelListID + " option:selected").val();
				if (!item_id) {
					that.clearLevelsStartingAt(level + 1);
				} else {
					that.setUpHierarchyLevel(level + 1, item_id);
					that.selectItem(level, item_id, jQuery('#' + newLevelDivID).data('parent_id'), 0, {});
				}
			});
			if (that.indicatorUrl) {
				var indicator = document.createElement('img');
				indicator.src = that.indicatorUrl;
				indicator.className = '_indicator';
				if (level == 0) { jQuery('#' + newLevelDivID).append("<br/>"); }
				jQuery('#' + newLevelDivID).append(indicator);
			}
			
			// add first "choose something" item
			if (level > 0) {
				jQuery("#" + newLevelListID).append(jQuery("<option></option>").val('').html('-'));
			}
			
			jQuery("#" + newLevelDivID).parent().parent().scrollTo("0px");
	}
}	
			var parent_id = item_id;
			
			jQuery.getJSON(that.levelDataUrl, { id: item_id, init: is_init ? 1 : '', root_item_id: that.selectedItemIDs[0] ? that.selectedItemIDs[0] : ''}, function(data) {
				var l = jQuery('#' + newLevelDivID).data('level');
				
				jQuery.each(data, function(i, item) {
					if (item[data._primaryKey]) {
						if ((is_init) && (l == 0) && (!that.selectedItemIDs[0])) {
							that.selectedItemIDs[0] = item[data._primaryKey];
						}
if (that.uiStyle == 'horizontal') {
						var moreButton = '';
						if (that.editButtonIcon) {
							if (item.children > 0) {
								moreButton = "<div style='float: right;'><a href='#' id='hierBrowser_" + that.name + '_level_' + l + '_item_' + item[data._primaryKey] + "_edit' >" + that.editButtonIcon + "</a></div>";
							} else {
								moreButton = "<div style='float: right;'><a href='#' id='hierBrowser_" + that.name + '_level_' + l + '_item_' + item[data._primaryKey] + "_edit'  style='opacity: 0.3;'>" + that.editButtonIcon + "</a></div>";
							}
						}
						
						if ((l > 0) && (that.allowExtractionFromHierarchy) && (that.initItemID == item[data._primaryKey]) && that.extractFromHierarchyButtonIcon) {
							moreButton += "<div style='float: right; margin-right: 5px; opacity: 0.3;' id='hierBrowser_" + that.name + "_extract_container'><a href='#' id='hierBrowser_" + that.name + "_extract'>" + that.extractFromHierarchyButtonIcon + "</a></div>";
						}
						
						if ( (!((l == 0) && that.dontAllowEditForFirstLevel))) {
							jQuery('#' + newLevelListID).append(
								"<li class='" + that.className + "'>" + moreButton +"<a href='#' id='hierBrowser_" + that.name + '_level_' + l + '_item_' + item[data._primaryKey] + "' class='" + that.className + "'>"  +  jQuery('<div/>').text(item.name).html() + "</a></li>"
							);
						} else {
							jQuery('#' + newLevelListID).append(
								"<li class='" + that.className + "'>" + moreButton + jQuery('<div/>').text(item.name).html() + "</li>"
							);
						}
						
						jQuery('#' + newLevelListID + " li:last a").data('item_id', item[data._primaryKey]);
						if(that.editDataForFirstLevel) {
							jQuery('#' + newLevelListID + " li:last a").data(that.editDataForFirstLevel, item[that.editDataForFirstLevel]);
						}
						
						if (that.hasChildrenIndicator) {
							jQuery('#' + newLevelListID + " li:last a").data('has_children', item[that.hasChildrenIndicator] ? true : false);
						}
						
						// edit button
						if (!((l == 0) && that.dontAllowEditForFirstLevel)) {
							var editUrl = '';
							var editData = 'item_id';
							if (that.editUrlForFirstLevel && (l == 0)) {
								editUrl = that.editUrlForFirstLevel;
								if(that.editDataForFirstLevel) {
									editData = that.editDataForFirstLevel;
								}
							} else {
								editUrl = that.editUrl;
							}
							if (editUrl) {
								jQuery('#' + newLevelListID + " li:last a:last").click(function() { 
									jQuery(document).attr('location', editUrl + jQuery(this).data(editData));
									return false;
								});
							} else {
								jQuery('#' + newLevelListID + " li:last a:last").click(function() { 
																
									var l = jQuery(this).parent().parent().parent().data('level');
									var item_id = jQuery(this).data('item_id');
									var has_children = jQuery(this).data('has_children');
									that.selectItem(l, item_id, parent_id, has_children, item);
									return false;
								});
							}
						}
						
						// hierarchy forward navigation
						if (!that.readOnly) {
							jQuery('#' + newLevelListID + " li:last a:first").click(function() { 								
								var l = jQuery(this).parent().parent().parent().parent().data('level');
								var item_id = jQuery(this).data('item_id');
								var has_children = jQuery(this).data('has_children');
								that.selectItem(l, item_id, parent_id, has_children, item);
								
								// scroll to new level
								that.setUpHierarchyLevel(l + 1, item_id, 0);
								jQuery('#' + that.container + '_scrolling_container').animate({scrollLeft: l * that.levelWidth}, 500);
								
								return false;
							});
						}
						
						if (that.readOnly) {
							jQuery('#' + newLevelListID + " li:first a").click(function() { 
								return false;
							});
						}
										
						if ((that.allowExtractionFromHierarchy) && (that.extractFromHierarchyButtonIcon)) {
							jQuery('#' + newLevelListID + ' #hierBrowser_' + that.name + '_extract').unbind('click.extract').bind('click.extract', function() {
								that.extractItemFromHierarchy(item[data._primaryKey], item);
							});
						}
} else {
	if (that.uiStyle == 'vertical') {
		jQuery("#" + newLevelListID).append(jQuery("<option></option>").val(item.item_id).text(item.name));
	}
}
						// Pass item_id to caller if required
						if (is_init && that.selectOnLoad && that.onSelection && is_init && item[data._primaryKey] == selected_item_id) {
							var formattedDisplayString = that.currentSelectionDisplayFormat.replace('%1', item.name);
							that.onSelection(item[data._primaryKey], item.parent_id, item.name, formattedDisplayString, item.type_id);
						}
					} else {
						if (parent_id && (that.selectedItemIDs.length == 0)) { that.selectedItemIDs[0] = parent_id; }
					}
				});
				
			
				
if (that.uiStyle == 'horizontal') {
				if (!is_init) {
					that.selectedItemIDs[level-1] = item_id;
					jQuery('#' + newLevelListID + ' a').removeClass(that.classNameSelected).addClass(that.className);
					jQuery('#hierBrowser_' + that.name + '_' + (level - 1) + ' a').removeClass(that.classNameSelected).addClass(that.className);
					jQuery('#hierBrowser_' + that.name + '_level_' + (level - 1) + '_item_' + item_id).addClass(that.classNameSelected);
				} else {
					if (that.selectedItemIDs[level] !== undefined) {
						jQuery('#hierBrowser_' + that.name + '_level_' + (level) + '_item_' + that.selectedItemIDs[level]).addClass(that.classNameSelected);
						jQuery('#hierBrowser_' + that.name + '_' + level).scrollTo('#hierBrowser_' + that.name + '_level_' + level + '_item_' + that.selectedItemIDs[level]);
					}
				}
} else {
	if (that.uiStyle == 'vertical') {
		if(jQuery("#" + newLevelListID + " option").length <= ((level > 0) ? 1 : 0)) {
			jQuery("#" + newLevelListID).parent().remove();
		} else {
			if (is_init) {
				if (that.selectedItemIDs[level] !== undefined) {
					jQuery("#" + newLevelListID + " option[value=" + that.selectedItemIDs[level] + "]").attr('selected', 1);
				}
			}
			
			if(
				(!is_init && (jQuery("#" + newLevelListID + " option").length == 1))
				||
				(is_init && ((jQuery("#" + newLevelListID + " option").length == 1) || ((that.selectedItemIDs.length <= 1) && (level == 0))))
			) {
				that.setUpHierarchyLevel(level + 1, jQuery("#" + newLevelListID + " option:first").val(), 0);
			}
		}
	}
}

				that._numOpenLoads--;
				that._openLoadsForLevel[l] = false;
				
				that.updateTypeMenu();
				
				jQuery('#' + newLevelDivID + ' img._indicator').remove();		// hide loading indicator
			});
			
			
			that.levelLists[level] = newLevelDivID;
			return newLevelDiv;
		}
		// --------------------------------------------------------------------------------
		//
		that.updateTypeMenu = function() {
			if ((that._numOpenLoads == 0) && that.currentSelectionDisplayID) {
				var selectedID = that.getSelectedItemID();
				var l = that.numLevels();
				while(l >= 0) {
					if (that.displayCurrentSelectionOnLoad && (jQuery('#hierBrowser_' + that.name + '_level_' + l + '_item_' + selectedID).length > 0)) {
						if (that.currentSelectionDisplayID) {
							jQuery('#' + that.currentSelectionDisplayID).html(that.currentSelectionDisplayFormat.replace('%1', jQuery('#hierBrowser_' + that.name + '_level_' + l + '_item_' + selectedID).html()));
						}
						break;
					}
					l--;
				}
				
				if ((that._numOpenLoads == 0) && that.typeMenuID) {
					jQuery('#' + that.typeMenuID).show(300);
				}
			}
		}
		// --------------------------------------------------------------------------------
		// 
		that.selectItem = function(l, item_id, parent_id, has_children, item) {
			// set current selection display
			var formattedDisplayString = that.currentSelectionDisplayFormat.replace('%1', item.name);
			
			if (that.currentSelectionDisplayID) {
				jQuery('#' + that.currentSelectionDisplayID).html(formattedDisplayString);
			}
			
			if (that.currentSelectionIDID) {
				jQuery('#' + that.currentSelectionIDID).attr('value', item_id);
			}
			
			if (that.onSelection) {
				that.onSelection(item_id, parent_id, item.name, formattedDisplayString, item.type_id);
			}
			
			while(that.selectedItemIDs.length > l) {
				that.selectedItemIDs.pop();
			}
			
			jQuery("#hierBrowser_" + that.name + "_extract_container").css('opacity', 0.3);
			
			jQuery('#hierBrowser_' + that.name + '_' + l + ' a').removeClass(that.classNameSelected).addClass(that.className);
			jQuery('#hierBrowser_' + that.name + '_level_' + l + '_item_' + item_id).addClass(that.classNameSelected);
		}
		// --------------------------------------------------------------------------------
		// 
		that.extractItemFromHierarchy = function(item_id, item) {
			
			if (that.currentSelectionDisplayID) {
				jQuery('#' + that.currentSelectionDisplayID).html(that.extractFromHierarchyMessage);
			}
			
			if (that.currentSelectionIDID) {
				jQuery('#' + that.currentSelectionIDID).attr('value', null);
			}
			jQuery("#hierBrowser_" + that.name + "_extract_container").css('opacity', 1.0);
			
			if (that.onSelection) {
				that.onSelection(item_id, null, item.name, that.extractFromHierarchyMessage, null);
			}
		}
		// --------------------------------------------------------------------------------
		// return database id (the primary key in the database, *NOT* the DOM ID) of currently selected item
		that.getSelectedItemID = function() {
			return that.selectedItemIDs[that.selectedItemIDs.length - 1];
		}
		// --------------------------------------------------------------------------------
		// returns the number of levels loaded
		that.numLevels = function() {
			return that.levelLists.length;
		}
		// --------------------------------------------------------------------------------
		// initialize before returning object
		that.setUpHierarchy(that.initItemID ? that.initItemID : that.defaultItemID);
		
		return that;
	};	
})(jQuery);