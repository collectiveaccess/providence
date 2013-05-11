/* ----------------------------------------------------------------------
 * js/ca/ca.hierbrowser.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
			
			bundle: '',
			
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
			allowSelection: true,
			
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
			
			_numOpenLoads: 0,					// number of AJAX loads pending
			_openLoadsForLevel:[],				// counts of loads pending per-level
			_pageLoadsForLevel:[],				// log of which pages per-level have been loaded already
			_queuedLoadsForLevel: [],			// parameters for pending loads per-level
			
			maxItemsPerHierarchyLevelPage: 300	// maximum number of items to load at one time into a level
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
		// BEGIN method definitions
		// --------------------------------------------------------------------------------
		// Set up initial state and all levels of hierarchy. The item_id parameter will be used to determine the root
		// of the hierarchy if set. If it is omitted then the useAsRootID option value will be used.
		//
		// @param int item_id The database id of the item to be used as the root of the hierarchy. If omitted the useAsRootID option value is used, or if that is not available whatever root the server decides to use.
		//
		that.setUpHierarchy = function(item_id) {
			if (!item_id) { that.setUpHierarchyLevel(0, that.useAsRootID ? that.useAsRootID : 0, 1, null, true); return; }
			that.levelLists = [];
			that.selectedItemIDs = [];
			jQuery.getJSON(that.initDataUrl, { id: item_id, bundle: that.bundle}, function(data, e, x) {
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
				
				if (data[0] == data[1]) {	// workaround for jQuery(?) but that replicates first item of list in json array
					data.shift();
				}
				var l = 0;
				console.log("mod", data);
				jQuery.each(data, function(i, id) {
					console.log("setup", i, id, item_id);
					that.setUpHierarchyLevel(i, id, 1, item_id);
					l++;
				});
				that.loadHierarchyLevelData();
				
				if (that.uiStyle == 'horizontal') {
					jQuery('#' + that.container + '_scrolling_container').animate({scrollLeft: l * that.levelWidth}, 500);
				}
			});
		}
		// --------------------------------------------------------------------------------
		// Clears hierarchy level display
		//
		// @param int level The level to be cleared
		//
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
		// Initialize a hierarchy level and load data into it for display.
		//
		// @param int level The level of the hierarchy to initialize.
		// @param int item_id The database id of the item for which child items will be loaded into the hierarchy level. This is the "parent" of the level, in other words.
		// @param bool is_init Flag indicating if this is the initial load of the hierarchy browser.
		// @param int selected_item_id The database id of the selected hierarchy item. This is the lowest selected item in the hierarchy; selection of its ancestors is implicit.
		// @param bool fetchData Flag indicating if queue should be processed immediately. Default is false. The queue can be subsequently processed by calling loadHierarchyLevelData().
		//
		that.setUpHierarchyLevel = function (level, item_id, is_init, selected_item_id, fetchData) {
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
			
			that.showIndicator(newLevelDivID);
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
					that.setUpHierarchyLevel(level + 1, item_id, 0, undefined, fetchData);
					that.selectItem(level, item_id, jQuery('#' + newLevelDivID).data('parent_id'), 0, {});
				}
			});
			that.showIndicator(newLevelDivID);
			
			// add first "choose something" item
			if (level > 0) {
				jQuery("#" + newLevelListID).append(jQuery("<option></option>").val('').html('-'));
			}
			
			jQuery("#" + newLevelDivID).parent().parent().scrollTo("0px");
	}
}	
			var l = jQuery('#' + newLevelDivID).data('level');
			that._pageLoadsForLevel[l] = [true];
			that.queueHierarchyLevelDataLoad(level, item_id, is_init, newLevelDivID, newLevelListID, selected_item_id, 0, fetchData);
			
			that.levelLists[level] = newLevelDivID;
			return newLevelDiv;
		}
		// --------------------------------------------------------------------------------
		// Queues load of hierarchy data into a level. Unless the fetchData parameter is set to true, data is not actually loaded until
		// loadHierarchyLevelData() is called. This enables you to bundle data loads for several levels into a single AJAX request, improving
		// performance.
		//
		// @param int level The level into which data will be loaded.
		// @param int item_id The database id of the item for which child items will be loaded into the hierarchy level. This is the "parent" of the level, in other words.
		// @param bool is_init Flag indicating if this is the initial load of the hierarchy browser.
		// @param string newLevelDivID The ID of the <div> containing the level
		// @param string newLevelListID The ID of the <ul> containing the level
		// @param int selected_item_id  The database id of the selected hierarchy item. This is the lowest selected item in the hierarchy; selection of its ancestors is implicit.
		// @param int start The offset into the level data to start loading at. For a given level only up to a maximum of {maxItemsPerHierarchyLevelPage} items are fetched per AJAX request. The start parameter is used to control from which item the returned list starts.
		// @param bool fetchData Flag indicating if queue should be processed immediately. Default is false. The queue can be subsequently processed by calling loadHierarchyLevelData().
		//
		that.queueHierarchyLevelDataLoad = function(level, item_id, is_init, newLevelDivID, newLevelListID, selected_item_id, start, fetchData) {
			if(!that._queuedLoadsForLevel[level]) { that._queuedLoadsForLevel[level] = []; }
			that._queuedLoadsForLevel[level].push({
				item_id: item_id, is_init: is_init, newLevelDivID: newLevelDivID, newLevelListID: newLevelListID, selected_item_id: selected_item_id, start: start
			});
			
			if (fetchData) { that.loadHierarchyLevelData(); }
		}
		// --------------------------------------------------------------------------------
		// Load "page" of hierarchy level via AJAX
		//
		that.loadHierarchyLevelData = function() {
			var id_list = [];
			var itemIDsToLevelInfo = {};
			
			var is_init = false;
			for(var l = 0; l < that._queuedLoadsForLevel.length; l++) {
				for(var i = 0; i < that._queuedLoadsForLevel[l].length; i++) {
					id_list.push(that._queuedLoadsForLevel[l][i]['item_id']+':'+that._queuedLoadsForLevel[l][i]['start']);
					itemIDsToLevelInfo[that._queuedLoadsForLevel[l][i]['item_id']] = {
						level: l,
						newLevelDivID: that._queuedLoadsForLevel[l][i]['newLevelDivID'],
						newLevelListID: that._queuedLoadsForLevel[l][i]['newLevelListID'],
						selected_item_id: that._queuedLoadsForLevel[l][i]['selected_item_id'],
						is_init: that._queuedLoadsForLevel[l][i]['is_init']
					}
					if (that._queuedLoadsForLevel[l][i]['is_init']) { is_init = true; }
					that._queuedLoadsForLevel[l].splice(i,1);
				}
			}
			
			if (is_init) {
				// attempt to renumber levels if required (sometimes first level is suppressed)
				var needsLevelShift = true;
				for(var k in itemIDsToLevelInfo) {
					if (itemIDsToLevelInfo[k]['level'] === 0) {
						needsLevelShift = false;
						break;
					}
				}
				
				if (needsLevelShift) {
					for(var k in itemIDsToLevelInfo) {
						var oldLevel = itemIDsToLevelInfo[k]['level'];
						var newLevel = oldLevel - 1;
						var re = new RegExp("_" + oldLevel + "$");
						itemIDsToLevelInfo[k]['newLevelDivID'] = itemIDsToLevelInfo[k]['newLevelDivID'].replace(re, "_" + newLevel);
						itemIDsToLevelInfo[k]['newLevelListID'] = itemIDsToLevelInfo[k]['newLevelListID'].replace(re, "_" + newLevel);
						itemIDsToLevelInfo[k]['level']--;
					}
				}
			}
			
			if (!id_list.length) { return; }
			
			var start = 0;
			jQuery.getJSON(that.levelDataUrl, { id: id_list.join(';'), bundle: that.bundle, init: is_init ? 1 : '', root_item_id: that.selectedItemIDs[0] ? that.selectedItemIDs[0] : '', start: start * that.maxItemsPerHierarchyLevelPage, max: (that.uiStyle == 'vertical') ? 0 : that.maxItemsPerHierarchyLevelPage }, function(dataForLevels) {
				jQuery.each(dataForLevels, function(key, data) {
					var tmp = key.split(":");
					var item_id = tmp[0];
					var start = tmp[1] ? tmp[1] : 0;
					
					if (!itemIDsToLevelInfo[item_id]) { return; }
					var level = itemIDsToLevelInfo[item_id]['level'];
					
					var is_init = itemIDsToLevelInfo[item_id]['is_init'];
					var newLevelDivID = itemIDsToLevelInfo[item_id]['newLevelDivID'];
					var newLevelListID = itemIDsToLevelInfo[item_id]['newLevelListID'];
					var selected_item_id = itemIDsToLevelInfo[item_id]['selected_item_id'];
					
					var foundSelected = false;
					jQuery('#' + newLevelDivID).data('itemCount', data['_itemCount']);
					jQuery.each(data, function(i, item) {
						if (!item) { return; }
						if (item['item_id']) {
							if ((is_init) && (level == 0) && (!that.selectedItemIDs[0])) {
								that.selectedItemIDs[0] = item['item_id'];
							}
							if (that.selectedItemIDs[level] == item['item_id']) {
								foundSelected = true;
							}
	if (that.uiStyle == 'horizontal') {
							var moreButton = '';
							if (that.editButtonIcon) {
								if (item.children > 0) {
									moreButton = "<div style='float: right;'><a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "_edit' >" + that.editButtonIcon + "</a></div>";
								} else {
									moreButton = "<div style='float: right;'><a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "_edit'  style='opacity: 0.3;'>" + that.editButtonIcon + "</a></div>";
								}
							}
							
							if ((level > 0) && (that.allowExtractionFromHierarchy) && (that.initItemID == item['item_id']) && that.extractFromHierarchyButtonIcon) {
								moreButton += "<div style='float: right; margin-right: 5px; opacity: 0.3;' id='hierBrowser_" + that.name + "_extract_container'><a href='#' id='hierBrowser_" + that.name + "_extract'>" + that.extractFromHierarchyButtonIcon + "</a></div>";
							}
							
							if ( (!((level == 0) && that.dontAllowEditForFirstLevel))) {
								jQuery('#' + newLevelListID).append(
									"<li class='" + that.className + "'>" + moreButton +"<a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "' class='" + that.className + "'>"  +  item.name + "</a></li>"
								);
							} else {
								jQuery('#' + newLevelListID).append(
									"<li class='" + that.className + "'>" + moreButton + item.name + "</li>"
								);
							}
							
							jQuery('#' + newLevelListID + " li:last a").data('item_id', item['item_id']);
							if(that.editDataForFirstLevel) {
								jQuery('#' + newLevelListID + " li:last a").data(that.editDataForFirstLevel, item[that.editDataForFirstLevel]);
							}
							
							if (that.hasChildrenIndicator) {
								jQuery('#' + newLevelListID + " li:last a").data('has_children', item[that.hasChildrenIndicator] ? true : false);
							}
							
							// edit button
							if (!((level == 0) && that.dontAllowEditForFirstLevel)) {
								var editUrl = '';
								var editData = 'item_id';
								if (that.editUrlForFirstLevel && (level == 0)) {
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
										that.selectItem(l, item_id, jQuery('#' + newLevelDivID).data('parent_id'), has_children, item);
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
									that.selectItem(l, item_id, jQuery('#' + newLevelDivID).data('parent_id'), has_children, item);
									
									// scroll to new level
									that.setUpHierarchyLevel(l + 1, item_id, 0, undefined, true);
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
									that.extractItemFromHierarchy(item['item_id'], item);
								});
							}
	} else {
		if (that.uiStyle == 'vertical') {
			jQuery("#" + newLevelListID).append(jQuery("<option></option>").val(item.item_id).text(item.name));
		}
	}
							// Pass item_id to caller if required
							if (is_init && that.selectOnLoad && that.onSelection && is_init && item['item_id'] == selected_item_id) {
								var formattedDisplayString = that.currentSelectionDisplayFormat.replace('%1', item.name);
								that.onSelection(item['item_id'], item.parent_id, item.name, formattedDisplayString, item.type_id);
							}
						} else {
							if (item.parent_id && (that.selectedItemIDs.length == 0)) { that.selectedItemIDs[0] = item.parent_id; }
						}
					});
					
					var dontDoSelectAndScroll = false;
					if (!foundSelected && that.selectedItemIDs[level]) {
						var p = jQuery('#' + newLevelDivID).data("page");
						if (!p || (p < 0)) { p = 0; }
						p++;
						jQuery('#' + newLevelDivID).data("page", p);
						if (jQuery('#' + newLevelDivID).data('itemCount') > (p * that.maxItemsPerHierarchyLevelPage)) { 
							if (!that._pageLoadsForLevel[level] || !that._pageLoadsForLevel[level][p]) {		// is page loaded?
								if (!that._pageLoadsForLevel[level]) { that._pageLoadsForLevel[level] = []; }
								that._pageLoadsForLevel[level][p] = true;		
						
								that.queueHierarchyLevelDataLoad(level, item_id, false, newLevelDivID, newLevelListID, selected_item_id, p * that.maxItemsPerHierarchyLevelPage, true);
							
								dontDoSelectAndScroll = true;	// we're still trying to find selected item so don't try to select it
							}
						}
					} else {
						// Treat sequential page load as init so selected item is highlighted
						is_init = true;
					}
					
				
					
	if (that.uiStyle == 'horizontal') {
					if (!is_init) {
						that.selectedItemIDs[level-1] = item_id;
						jQuery('#' + newLevelListID + ' a').removeClass(that.classNameSelected).addClass(that.className);
						jQuery('#hierBrowser_' + that.name + '_' + (level - 1) + ' a').removeClass(that.classNameSelected).addClass(that.className);
						jQuery('#hierBrowser_' + that.name + '_level_' + (level - 1) + '_item_' + item_id).addClass(that.classNameSelected);
					} else {
						if ((that.selectedItemIDs[level] !== undefined) && !dontDoSelectAndScroll) {
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
					that.setUpHierarchyLevel(level + 1, jQuery("#" + newLevelListID + " option:first").val(), 0, undefined, true);
				}
			}
		}
	}
	
					that._numOpenLoads--;
					that._openLoadsForLevel[level] = false;
					
					that.updateTypeMenu();
					
					// Handle loading of long hierarchy levels via ajax on scroll
	if (that.uiStyle == 'horizontal') {
					var selected_item_id_cl = selected_item_id;
					jQuery('#' + newLevelDivID).scroll(function () { 
						var curPage = jQuery('#' + newLevelDivID).data("page");
						if (!curPage) { curPage = 0; }
					   if (jQuery('#' + newLevelDivID).scrollTop() >= ((curPage * jQuery('#' + newLevelDivID).height()) - 10)) {
						  // get page #
						  var p = Math.ceil(jQuery('#' + newLevelDivID).scrollTop()/jQuery('#' + newLevelDivID).height());
						  if (p < 0) { p = 0; }
						  if (jQuery('#' + newLevelDivID).data('itemCount') <= (p * that.maxItemsPerHierarchyLevelPage)) { 
							return;
						  }
						  jQuery('#' + newLevelDivID).data("page", p);
						  var l = jQuery('#' + newLevelDivID).data('level');
						  if (!that._pageLoadsForLevel[l] || !that._pageLoadsForLevel[l][p]) {		// is page loaded?
							if (!that._pageLoadsForLevel[l]) { that._pageLoadsForLevel[l] = []; }
							that._pageLoadsForLevel[l][p] = true;		
								 	
							that.queueHierarchyLevelDataLoad(l, item_id, false, newLevelDivID, newLevelListID, selected_item_id_cl, p * that.maxItemsPerHierarchyLevelPage, true);
						  }
					   }
					});
	}
					that.hideIndicator(newLevelDivID);
					
					// try to load any outstanding level pages
					that.loadHierarchyLevelData();
				});
			});
		}
		// --------------------------------------------------------------------------------
		// Updates type menu and "add" message associated with hierarchy browser based upon 
		// current state of the hierarchy browser
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
		// Records user selection of an item
		//
		// @param int level The level where the selected item resides
		// @param int item_id The database id of the selected item
		// @param int parent_id The database id of the parent of the selected item
		// @param bool has_children Flag indicating if the selected item has child items or not
		// @param Object item A hash containing details, including the name, of the selected item
		//
		that.selectItem = function(level, item_id, parent_id, has_children, item) {
			if (!that.allowSelection) return false;
			
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
			
			while(that.selectedItemIDs.length > level) {
				that.selectedItemIDs.pop();
			}
			that.selectedItemIDs.push(item_id);
			jQuery("#hierBrowser_" + that.name + "_extract_container").css('opacity', 0.3);
			jQuery('#hierBrowser_' + that.name + '_' + level + ' a').removeClass(that.classNameSelected).addClass(that.className);
			jQuery('#hierBrowser_' + that.name + '_level_' + level + '_item_' + item_id).addClass(that.classNameSelected);
		}
		// --------------------------------------------------------------------------------
		//  Support for UI display when moving item in one hierarchy into another hierarchy (aka item "extraction).
		//
		// @param int item_id The database id of the item to extract
		// @param Object item A hash containing details, including the name, of the item to be extracted
		//
		that.extractItemFromHierarchy = function(item_id, item) {
			if (that.currentSelectionDisplayID) {
				jQuery('#' + that.currentSelectionDisplayID).html(that.extractFromHierarchyMessage);
			}
			
			if (that.currentSelectionIDID) {
				jQuery('#' + that.currentSelectionIDID).attr('value', "X");		// X=extract
			}
			jQuery("#hierBrowser_" + that.name + "_extract_container").css('opacity', 1.0);
			
			if (that.onSelection) {
				that.onSelection(item_id, null, item.name, that.extractFromHierarchyMessage, null);
			}
		}
		// --------------------------------------------------------------------------------
		// Display spinning progress indicator in specified level <div> 
		//
		// @param string newLevelDivID The ID of the <div> containing the level
		//
		that.showIndicator = function(newLevelDivID) {
			if (!that.indicatorUrl) { return; }
			if (jQuery('#' + newLevelDivID + ' img._indicator').length > 0) {
				jQuery('#' + newLevelDivID + ' img._indicator').show();
				return;
			}
			var level = jQuery('#' + newLevelDivID).data('level');
			if (that.uiStyle == 'vertical') {
				var indicator = document.createElement('img');
				indicator.src = that.indicatorUrl;
				indicator.className = '_indicator';
				if (level == 0) { jQuery('#' + newLevelDivID).append("<br/>"); }
				jQuery('#' + newLevelDivID).append(indicator);
			} else {
				var indicator = document.createElement('img');
				indicator.src = that.indicatorUrl;
				indicator.className = '_indicator';
				indicator.style.position = 'absolute';
				indicator.style.left = '50%';
				indicator.style.top = '50%';
				jQuery('#' + newLevelDivID).append(indicator);
			}
		}
		// --------------------------------------------------------------------------------
		// Remove spinning progress indicator from specified level <div> 
		//
		// @param string newLevelDivID The ID of the <div> containing the level
		//
		that.hideIndicator = function(newLevelDivID) {
			jQuery('#' + newLevelDivID + ' img._indicator').hide();		// hide loading indicator
		}
		// --------------------------------------------------------------------------------
		// Returns database id (the primary key in the database, *NOT* the DOM ID) of currently selected item
		//
		that.getSelectedItemID = function() {
			return that.selectedItemIDs[that.selectedItemIDs.length - 1];
		}
		// --------------------------------------------------------------------------------
		// Returns the number of levels that are currently displayed
		//
		that.numLevels = function() {
			return that.levelLists.length;
		}
		// --------------------------------------------------------------------------------
		// END method definitions
		// --------------------------------------------------------------------------------
		//
		// Initialize before returning object
		that.setUpHierarchy(that.initItemID ? that.initItemID : that.defaultItemID);
		
		return that;
		// --------------------------------------------------------------------------------
	};	
})(jQuery);