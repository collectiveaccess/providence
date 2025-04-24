/* ----------------------------------------------------------------------
 * js/ca/ca.hierbrowser.js
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
 * Dependencies:
 *		jQuery.scrollTo
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
			
			uiDirection: 'up',	// direction of browse in vertical mode; value may be 'up' or 'down'

			bundle: '',

			levelDataUrl: '',
			initDataUrl: '',
			editUrl: '',
			sortSaveUrl: '',

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

			excludeItemIDs: [],		// Skip items with ids in this list

			className: 'hierarchyBrowserLevel',
			classNameSelected: 'hierarchyBrowserLevelSelected',
			classNameContainer: 'hierarchyBrowserContainer',
			classNameContainerReadOnly: 'hierarchyBrowserContainerReadOnly',

			currentSelectionDisplayID: '',
			currentSelectionDisplayFormat: '<ifdef code="hierarchy">^hierarchy%delimiter=_➜_ ➜ </ifdef>^current',
			currentSelectionDisplayPrefix: '',
			currentSelectionIDID: '',
			allowSelection: true,

			allowExtractionFromHierarchy: false,
			extractFromHierarchyButtonIcon: null,
			extractFromHierarchyMessage: null,

			selectOnLoad: false,
			onSelection: null,		/* function to call whenever an item is selected; passed item_id, parent_id, name, formatted display string and type_id */

			autoShrink: false,
			autoShrinkMaxHeightPx: 180,
			autoShrinkAnimateID: '',
			
			allowSecondarySelection: false,
			
			allowDragAndDropSorting: false,
			dragAndDropSortInProgress: false,
			dontAllowDragAndDropSortForFirstLevel: false,

			/* how do we treat disabled items in the browser? can be
			 *  - 'disable' : list items default behavior - i.e. show the item but don't make it a clickable link and apply the disabled class ('classNameDisabled' option)
			 *  - 'hide' : completely hide them from the browser
			 *  - 'full' : don't treat disabled items any differently
			 */
			disabledItems: 'disable',
			classNameDisabled: 'hierarchyBrowserLevelDisabled',

			displayCurrentSelectionOnLoad: true,
			typeMenuID: '',

			indicator: '',	
			indicatorUrl: '',
			incrementalLoadIndicator: '',
			
			editButtonIcon: '',
			disabledButtonIcon: '',

			hasChildrenIndicator: 'has_children',	/* name of key in data to use to determine if an item has children */
			alwaysShowChildCount: true,

			levelDivs: [],
			levelLists: [],
			selectedItemIDs: [],
			
			secondarySelectedItemIDs: [],		// list of checkbox-selected items when secondary selection are enabled
			secondarySelectionID: null,			// element to write semicolon-delimited list of checkbox-selected items
			defaultSecondarySelection: [],

			_numOpenLoads: 0,					// number of AJAX loads pending
			_openLoadsForLevel:[],				// counts of loads pending per-level
			_pageLoadsForLevel:[],				// log of which pages per-level have been loaded already
			_queuedLoadsForLevel: [],			// parameters for pending loads per-level,
			_foundSelectedForLevel: [],			// track whether selected has been loaded for each level,

			maxItemsPerHierarchyLevelPage: 500,	// maximum number of items to load at one time into a level
			
			selectMultiple: ''
		}, options);
		
		if (!that.incrementalLoadIndicator) { that.incrementalLoadIndicator = that.indicator; }
		
		that.useAsRootID = parseInt(that.useAsRootID);
		
		that.excludeItemIDs = that.excludeItemIDs.map(function(x) { return x + ""; });		// force all ids to string to ensure comparisons work (item ids from service are strings)

		if (!that.levelDataUrl) {
			alert("No level data url specified for " + that.name + "!");
			return null;
		}

		if (!jQuery.inArray(that.uiStyle, ['horizontal', 'vertical'])) { that.uiStyle = 'horizontal'; }		// verify the uiStyle is valid
		if (!jQuery.inArray(that.uiDirection, ['up', 'down'])) { that.uiDirection = 'up'; }		// verify the uiDirection is valid

		if (that.uiStyle == 'horizontal') {
			// create scrolling container
			jQuery('#' + that.container).append("<div class='" + that.classNameContainer + "' id='" + that.container + "_scrolling_container'></div>");
		} else {
			if (that.uiStyle == 'vertical') {
				jQuery('#' + that.container).append("<div class='" + that.classNameContainer + "' id='" + that.container + "_select_container'></div>");
			}
		}
		jQuery('#' + that.container).append("<div class='" + that.classNameContainerReadOnly + "' id='" + that.container + "_readonly'></div>");
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
		// @param bool selectOnLoad Select root item on hierarchy load. Sets hierarchy browser selectOnLoad option. If set to null (or omitted) the current value of the selectOnLoad option is used.
		//
		that.setUpHierarchy = function(item_id, selectOnLoad=null) {
			that.isReadOnly(that.readOnly, false);
			if (!item_id) { that.setUpHierarchyLevel(0, that.useAsRootID ? that.useAsRootID : 0, 1, null, true); return; }
			that.levelDivs = [];
			that.levelLists = [];
			that.selectedItemIDs = [];
			that.secondarySelectedItemIDs = that.defaultSecondarySelection;
			that._foundSelectedForLevel = [];
			
			if(selectOnLoad !== null) {
				that.selectOnLoad = selectOnLoad;
			}

			jQuery.getJSON(that.initDataUrl, { id: item_id, bundle: that.bundle}, function(data, e, x) {	// get ancestors for item_id and load each level
				if (typeof data === 'object') {
					var dataAsList = [];
					for(var o in data) {
						if (data.hasOwnProperty(o)) {
							dataAsList.push(data[o]);
						}
					}
					data = dataAsList;
				}


				if (data.length) {
					that.selectedItemIDs = data.join(';').split(';');

					if ((that.useAsRootID > 0) && (that.useAsRootID !== data[0])) {
						that.selectedItemIDs.shift();
						if (jQuery.inArray(that.useAsRootID, data) == -1) {
							data.unshift(that.useAsRootID);
						}
					} else {
						if (!that.useAsRootID) { data.unshift(0); }
					}
				} else {
					data = [that.useAsRootID ? that.useAsRootID : 0];
				}
				
				// Remove root from selected id list when present
				if (that.useAsRootID && (that.selectedItemIDs[0] == that.useAsRootID)) {
					that.selectedItemIDs.shift();
				}

				var l = 0;
				jQuery.each(data, function(i, id) {
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
		// 
		//
		// @param bool
		//
		that.isReadOnly = function(readonly, animate) {
		    if(animate === undefined) { animate = true; }
			if (readonly !== null) {
				that.readOnly = readonly;
				
				that.readOnly ? jQuery("#" + that.container + "_readonly").fadeIn(animate ? 500 : 0) : jQuery("#" + that.container + "_readonly").fadeOut(animate ? 500 : 0);
			}
			return that.readOnly;
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
				that.levelDivs[l] = undefined;
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

				var selected_item_id_cl = selected_item_id;

				jQuery('#' + newLevelDivID).scroll(function () {
					if(that.isLoadingLevel) { return; }		// Don't do scroll-based loading if we're already loading a level... otherwise we can get bouncy loads 
															// (Eg. lots of pages loaded at the same time)
					if((jQuery('#' + newLevelDivID).scrollTop() + jQuery('#' + newLevelDivID).height()) >= (jQuery('#' + newLevelDivID).prop('scrollHeight'))) {
						var p = jQuery('#' + newLevelDivID).data("page");

						if ((p === undefined) || (p == 0)) { p = 0; }	// always start with page one
						p++;

						// Are we at the end of the list?
						if (jQuery('#' + newLevelDivID).data('itemCount') <= (p * that.maxItemsPerHierarchyLevelPage)) {
							return false;
						}

						jQuery('#' + newLevelDivID).data("page", p);
						var l = jQuery('#' + newLevelDivID).data('level');
					
						if (!that._pageLoadsForLevel[l] || !that._pageLoadsForLevel[l][p]) {		// is page loaded?
							if (!that._pageLoadsForLevel[l]) { that._pageLoadsForLevel[l] = []; }
							that._pageLoadsForLevel[l][p] = true;
							that.showIncrementalLoadIndicator(newLevelListID);
							that.queueHierarchyLevelDataLoad(l, item_id, false, newLevelDivID, newLevelListID, selected_item_id_cl, p * that.maxItemsPerHierarchyLevelPage, true);
						}
					}
				});

				that.showIndicator(newLevelDivID);
			} else {
				if (that.uiStyle == 'vertical') {
					// Create new <select> to display list of items
					var newLevelList = "<select class='" + that.className + "' id='" + newLevelListID + "' name='" + newLevelListID + "' style='width: "+ (that.browserWidth - 32) + "px;'></select>";	// allow 24 pixels for spinner
					var newLevelDiv = "<div class='" + that.className + "' id='" + newLevelDivID + "'>";
					
					if (that.uiDirection == 'up') {
						newLevelDiv += newLevelList;
						if (level > 0) { newLevelDiv += "<br/>⬆</div>"; }
						jQuery('#' + that.container + '_select_container').prepend(newLevelDiv);
					} else {
						if (level > 0) { newLevelDiv += "⬇<br/>"; }
						newLevelDiv += newLevelList + "</div>";
						if (level == 0) {
							jQuery('#' + that.container + '_select_container').prepend(newLevelDiv);
						} else {
							jQuery('#' + that.container + '_select_container').append(newLevelDiv);
						}
					}
					
					jQuery('#' + newLevelDivID).data('level', level).data('parent_id', item_id);
					jQuery('#' + newLevelListID).change(function() {
						var item_id = jQuery("#" + newLevelListID + " option:selected").val();
						if (!item_id) {
							that.clearLevelsStartingAt(level + 1);
						} else {
							that.setUpHierarchyLevel(level + 1, item_id, 0, undefined, true);
							that.selectItem(level, item_id, jQuery('#' + newLevelDivID).data('parent_id'), 0, {});
						}
					}).attr('disabled', true);
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

			that.levelDivs[level] = newLevelDivID;
			that.levelLists[level] = newLevelListID;
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
			if (that.isLoadingLevel) { return; }
			that.isLoadingLevel = true;
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

			if (!id_list.length) { that.isLoadingLevel = false; return; }

			var start = 0;
			var onLoadSelection = null;
			
			jQuery.getJSON(that.levelDataUrl, { id: id_list.join(';'), bundle: that.bundle, init: is_init ? 1 : '', root_item_id: that.selectedItemIDs[0] ? that.selectedItemIDs[0] : '', start: start * that.maxItemsPerHierarchyLevelPage, max: (that.uiStyle == 'vertical') ? 0 : that.maxItemsPerHierarchyLevelPage }, function(dataForLevels) {
				var longestLevel = 0;
				
				jQuery.each(dataForLevels, function(key, data) {
					var tmp = key.split(":");
					var item_id = tmp[0];

					if (!itemIDsToLevelInfo[item_id]) { return; }
					var level = itemIDsToLevelInfo[item_id]['level'];

					var is_init = itemIDsToLevelInfo[item_id]['is_init'];
					var newLevelDivID = itemIDsToLevelInfo[item_id]['newLevelDivID'];
					var newLevelListID = itemIDsToLevelInfo[item_id]['newLevelListID'];
					var selected_item_id = itemIDsToLevelInfo[item_id]['selected_item_id'];

					var foundSelected = false;
					jQuery('#' + newLevelDivID).data('itemCount', data['_itemCount']);

					for(var i in data['_sortOrder']) {
						var item = data[data['_sortOrder'][i]];
						if (!item) { continue; }
						
						let item_label = item.name;
						let item_secondary_select = '';
						if(that.allowSecondarySelection) { 
							const checked = (that.secondarySelectedItemIDs.indexOf(item['item_id']) >= 0);
							item_secondary_select = "<input type='checkbox' data-item_id='" +item['item_id'] + "' class='hierBrowser_ss' id='hierBrowser_" + that.name + "_ss_" + item['item_id'] + "' " + (checked ? " checked='1'" : "") + "/> ";
						}
						
						if (!item_label) { item_label = '??? ' + item['item_id']; }
						if (item['item_id']) {
							if (that.excludeItemIDs && (Array.isArray(that.excludeItemIDs)) && (that.excludeItemIDs.length > 0) && (that.excludeItemIDs.indexOf(item['item_id']) >= 0)) {
								continue;
							}
							if (that.selectedItemIDs[level] == item['item_id']) {
								foundSelected = true;
								that._foundSelectedForLevel[level] = true;
							}
							if (that.uiStyle == 'horizontal') {
								var moreButton = '';
								if (that.editButtonIcon) {
									if (item.children > 0) {
										moreButton = "<div style='float: right;'><a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "_edit' aria-label='Expand hierarchy' >" + that.editButtonIcon + "</a></div>";
									} else {
										moreButton = "<div style='float: right;'><a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "_edit'  class='noChildren' aria-label='No children'>" + that.disabledButtonIcon + "</a></div>";
									}
								}

								if ((level > 0) && (that.allowExtractionFromHierarchy) && (that.initItemID == item['item_id']) && that.extractFromHierarchyButtonIcon) {
									moreButton += "<div style='float: right; margin-right: 5px; opacity: 0.3;' id='hierBrowser_" + that.name + "_extract_container'><a href='#' id='hierBrowser_" + that.name + "_extract'>" + that.extractFromHierarchyButtonIcon + "</a></div>";
								}

								var skipNextLevelNav = false;
								if ((item.is_enabled !== undefined) && (parseInt(item.is_enabled) === 0)) {
									switch (that.disabledItems) {
										case 'full':
											jQuery('#' + newLevelListID).append(
												"<li data-item_id='" +  item['item_id'] + "' class='" + that.className + "'>" + moreButton + "<a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "' class='" + that.className + "'>"  +  item_label + "</a></li>"
											);
											break;
										case 'hide': // item is hidden -> noop
											skipNextLevelNav = true; // skip adding the "navigate to the next level" code
											break;
										case 'disabled':
										default:
											jQuery('#' + newLevelListID).append(
												"<li data-item_id='" +  item['item_id'] + "' class='" + that.className + "'>" + moreButton +  '<span class="' + that.classNameDisabled + '">' + item_label + "</span></li>"
											);
											break;
									}
								} else if ((!((level == 0) && that.dontAllowEditForFirstLevel))) {
									jQuery('#' + newLevelListID).append(
										"<li data-item_id='" +  item['item_id'] + "' class='" + that.className + "'>" + item_secondary_select + moreButton +"<a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "' class='" + that.className + "'>"  +  item_label + "</a></li>"
									);
								} else {
									jQuery('#' + newLevelListID).append(
										"<li data-item_id='" +  item['item_id'] + "' class='" + that.className + "'>" +item_secondary_select +  moreButton + "<a href='#' id='hierBrowser_" + that.name + '_level_' + level + '_item_' + item['item_id'] + "' class='" + that.className + "'>"  +  item_label + "</a></li>"
									);
								}

								if(!skipNextLevelNav) {
									jQuery('#' + newLevelListID + " li:last a").data('item_id', item['item_id']);
									jQuery('#' + newLevelListID + " li:last a").data('item', item);
									if(that.editDataForFirstLevel) {
										jQuery('#' + newLevelListID + " li:last a").data(that.editDataForFirstLevel, item[that.editDataForFirstLevel]);
									}

									if (that.hasChildrenIndicator) {
										jQuery('#' + newLevelListID + " li:last a").data('has_children', item[that.hasChildrenIndicator] ? true : false);
									}
								}

								// edit button, if .. (trying to make this readable ...)
								if (
									(!((level == 0) && that.dontAllowEditForFirstLevel)) // this is not the first level or it is but we allow editing the first level, AND ..
									&&
									(
										(item.is_enabled === undefined) || 		// the item doesn't have a is_enabled property (e.g. places) OR ...
										(parseInt(item.is_enabled) === 1) || 	// it's enabled OR ...
										((parseInt(item.is_enabled) === 0) && that.disabledItems == 'full') // it's disabled, but the render mode tells us to not treat disabled items differently
									)
								) {
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
										jQuery('#' + newLevelListID + " li:last a:last").click(function(e) {
											if (that.dragAndDropSortInProgress) { e.preventDefault(); return false; }
											if(that.selectMultiple){
												// code to add + infront of items when multiple selections for or browse are permitted
												// #facet_apply is in ajax_browse_Facet_html.php
												if (jQuery(this).attr('facet_item_selected') == '1') {
													jQuery(this).attr('facet_item_selected', '');
												} else {
													jQuery(this).attr('facet_item_selected', '1');
												}

												if (jQuery('#' + newLevelListID).find("a." + that.className + "[facet_item_selected='1']").length > 0) {
													jQuery("#facet_apply").show();
												} else {
													jQuery("#facet_apply").hide();
												}
											}else{
												jQuery(document).attr('location', editUrl + jQuery(this).data(editData));
											}
											return false;
										});
									} else {
										jQuery('#' + newLevelListID + " li:last a:last").click(function(e) {
											if (that.dragAndDropSortInProgress) { e.preventDefault(); return false; }
											var l = jQuery(this).parent().parent().parent().data('level');
											var item_id = jQuery(this).data('item_id');
											var has_children = jQuery(this).data('has_children');
											that.selectItem(l, item_id, jQuery('#' + newLevelDivID).data('parent_id'), has_children, jQuery(this).data('item'));
											return false;
										});
									}
								}

								// hierarchy forward navigation
								
								jQuery('#' + newLevelListID + " li:last a:first").click(function() {
									if (that.readOnly) { return false; }
									var l = jQuery(this).parent().parent().parent().parent().data('level');
									var item_id = jQuery(this).data('item_id');
									var has_children = jQuery(this).data('has_children');
									that.selectItem(l, item_id, jQuery('#' + newLevelDivID).data('parent_id'), has_children, jQuery(this).data('item'));

									// scroll to new level
									that.setUpHierarchyLevel(l + 1, item_id, 0, undefined, true);
									jQuery('#' + that.container + '_scrolling_container').animate({scrollLeft: l * that.levelWidth}, 500);

									return false;
								});

								if ((that.allowExtractionFromHierarchy) && (that.extractFromHierarchyButtonIcon)) {
									jQuery('#' + newLevelListID + ' #hierBrowser_' + that.name + '_extract').unbind('click.extract').bind('click.extract', function() {
										that.extractItemFromHierarchy(item['item_id'], item);
									});
								}
							} else {
								if (that.uiStyle == 'vertical') {
									jQuery("#" + newLevelListID).append(jQuery("<option></option>").val(item.item_id).text(jQuery('<div />').html(item_label).text())).attr('disabled', false);
								}
							}
							// Pass item_id to caller if required
							if (is_init && that.selectOnLoad && that.onSelection && item['item_id'] == selected_item_id) {
								onLoadSelection = {
									'level': level,
									'item': item
								};
							}
						} else {
							if (item.parent_id && (that.selectedItemIDs.length == 0)) { that.selectedItemIDs[0] = item.parent_id; }
						}
					}
					
					if(that.allowSecondarySelection) {
						jQuery("#" + newLevelListID).on('click', 'input.hierBrowser_ss', function(e) {
							const item_id = jQuery(this).data('item_id');
							const index = that.secondarySelectedItemIDs.indexOf(item_id);
							const checked = jQuery(this).prop('checked');
							
							if(checked && (index < 0)) {
								that.secondarySelectedItemIDs.push(item_id);
							} else if(!checked && (index >= 0)) {
							 	that.secondarySelectedItemIDs.splice(index, 1); 
							}
							if(that.secondarySelectionID) {
								jQuery('#' + that.secondarySelectionID).val(that.secondarySelectedItemIDs.join(';'));
							}
						});
					}

					if (item_id && that.doDragAndDropSorting(item_id) && that.sortSaveUrl && (((level == 0) && !that.dontAllowDragAndDropSortForFirstLevel) || (level > 0))) {
						jQuery("#" + newLevelListID).sortable({ opacity: 0.7, 
							revert: 0.2, 
							scroll: true , 
							update: function(e, ui) {
								var dragged_dom_id = jQuery(ui.item).find("a").attr('id');
								var dragged_item_id = jQuery("#" + dragged_dom_id).data('item_id');
								
								var after_dom_id = jQuery(ui.item).prev().find("a").attr('id');
								var after_item_id = jQuery("#" + after_dom_id).data('item_id');
								
								jQuery.getJSON(that.sortSaveUrl, {'id': dragged_item_id, 'after_id': after_item_id}, function(d) {
									if (!d) { alert("Could not save reordering"); return false; }
									if (d.errors.length > 0) { alert("Could not save reordering: " + d.errors.join('; ')); return false; }
									if (d.timestamp) { jQuery("#" + newLevelListID).closest('form').find('input[name=form_timestamp]').val(d.timestamp); }
									return false;
								});
								
							},
							start: function(e, ui) {
								that.dragAndDropSortInProgress = true;
							},
							stop: function(e, ui) {
								that.dragAndDropSortInProgress = false;
							}
						});
					}

					var dontDoSelectAndScroll = false;
					if (!foundSelected && that.selectedItemIDs[level] && !that._foundSelectedForLevel[level]) {
						var p = jQuery('#' + newLevelDivID).data("page");
						if (!p || (p < 0)) { p = 0; }
						
						jQuery('#' + newLevelDivID).data("page", p);
						
						if (parseInt(jQuery('#' + newLevelDivID).data('itemCount')) > parseInt(p * that.maxItemsPerHierarchyLevelPage)) {
							if (!that._pageLoadsForLevel[level] || !that._pageLoadsForLevel[level][p + 1]) {		// is page loaded?
								if (!that._pageLoadsForLevel[level]) { that._pageLoadsForLevel[level] = []; }
								that._pageLoadsForLevel[level][p+1] = true;
								jQuery('#' + newLevelDivID).data("page", p+1);
								
								console.log("add to queue", item_id, newLevelDivID, p + 1);
								that.queueHierarchyLevelDataLoad(level, item_id, false, newLevelDivID, newLevelListID, selected_item_id, (p + 1) * that.maxItemsPerHierarchyLevelPage, true);

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
								
								if (jQuery('#hierBrowser_' + that.name + '_level_' + level + '_item_' + that.selectedItemIDs[level]).position()) {
									jQuery('#' + newLevelDivID).scrollTo(jQuery('#hierBrowser_' + that.name + '_level_' + level + '_item_' + that.selectedItemIDs[level]).position().top + 'px');
								}
							}
						}
					} else {
						if (that.uiStyle == 'vertical') {
							if(jQuery("#" + newLevelListID + " option").length <= ((level > 0) ? 1 : 0)) {
								jQuery("#" + newLevelListID).parent().remove();
							} else {
								if (is_init) {
									if (that.selectedItemIDs[level] !== undefined) {
										jQuery("#" + newLevelListID + " option[value=" + that.selectedItemIDs[level] + "]").prop('selected', 1);
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
					that.hideIncrementalLoadIndicator(newLevelListID);

					that.updateTypeMenu();

					that.hideIndicator(newLevelDivID);
				});

				// resize to fit items
				if((that.uiStyle == 'horizontal') && that.autoShrink && that.autoShrinkAnimateID) {
					var container = jQuery('#' + that.autoShrinkAnimateID);
					if(jQuery(container).is(':visible')) { // don't resize if the thing isn't visible
						var newHeight = 0; // start with 0 and make it bigger as needed

						// for each level
						for(var k in that.levelLists) {
							if(!that.levelLists.hasOwnProperty(k)) { continue; }
							// if the level warrants making the container bigger, do it
							var potentialHeight = jQuery('#' + that.levelLists[k]).height();
							if(newHeight < potentialHeight) {
								newHeight = potentialHeight;
							}
						}

						if(newHeight > that.autoShrinkMaxHeightPx) {
							newHeight = that.autoShrinkMaxHeightPx;
						}
						container.animate({ height: newHeight + 'px'}, 500);
					}
				}

				that.isLoadingLevel = false;
				that.loadHierarchyLevelData();	// try to load additional queued data requests
				
				// update current selection info after initial load if required
				if(onLoadSelection) {
					that.selectItem(onLoadSelection.level, onLoadSelection.item.item_id, onLoadSelection.item.parent_id, onLoadSelection.item.has_children, onLoadSelection.item);
				}
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
							jQuery('#' + that.currentSelectionDisplayID).show().html(that.currentSelectionDisplayPrefix + that._getCurrentSelectionStr(jQuery('#hierBrowser_' + that.name + '_level_' + l + '_item_' + selectedID).data('item'), l));
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
		// Determine if drag and drop sorting is permitted. The allowDragAndDropSorting option can be
		// either a boolean, in which case sorting is supported (or not) across the board, or an object
		// with properties set to trigger ids from first-level items and boolean values indicating whether
		// drag and drop sorting is permitted for the list under that first-level item. The object format
		// is used when displaying lists in the hierarchy browser to provide for per-list sort settings.
		//
		// @param int id 
		// @return mixed boolean and object with sorting map. 
		//
		that.doDragAndDropSorting = function(id) {
			if (typeof that.allowDragAndDropSorting !== 'object') return that.allowDragAndDropSorting;
			return that.allowDragAndDropSorting[id];
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
			var formattedDisplayString = that._getCurrentSelectionStr(item, level);

			if (that.currentSelectionDisplayID) {
				jQuery('#' + that.currentSelectionDisplayID).show().html(that.currentSelectionDisplayPrefix + formattedDisplayString);
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
				jQuery('#' + that.currentSelectionDisplayID).show().html(that.extractFromHierarchyMessage);
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
			if (!that.indicatorUrl && !that.indicator) { return; }
			
			if (jQuery('#' + newLevelDivID + ' div._indicator').length > 0) {
				jQuery('#' + newLevelDivID + ' div._indicator').show();
				return;
			}
			
			var level = jQuery('#' + newLevelDivID).data('level');
				
			if (that.indicatorUrl) {
				var img = document.createElement('img');
				img.src = that.indicatorUrl;
				img.className = '_indicatorImg';
				
				that.indicator = that.indicatorUrl;
			} 
				
				
			var indicator = document.createElement('div');
			if (that.uiStyle == 'vertical') {
				if (level == 0) { jQuery('#' + newLevelDivID).append("<br/>"); }
			} else {
				jQuery(indicator).append(that.indicator);
				indicator.className = '_indicator';
				indicator.style.position = 'absolute';
				indicator.style.left = '50%';
				indicator.style.top = '50%';
			}
			jQuery('#' + newLevelDivID).append(indicator);
			
			return;
		}
		// --------------------------------------------------------------------------------
		// Remove spinning progress indicator in specified level <div>
		//
		// @param string newLevelDivID The ID of the <div> containing the level
		//
		that.hideIndicator = function(newLevelDivID) {
			if (!that.indicatorUrl && !that.indicator) { return; }
			
			jQuery('#' + newLevelDivID + ' div._indicator').remove();		// hide loading indicator
		}
		// --------------------------------------------------------------------------------
		// Display spinning progress indicator in specified level list during partial load
		//
		// @param string newLevelDivID The ID of the <div> containing the level
		//
		that.showIncrementalLoadIndicator = function(newLevelListID) {
			if (that.uiStyle == 'horizontal') {
				var indicator = document.createElement('li');
				jQuery(indicator).append(that.incrementalLoadIndicator);
				indicator.className = '_indicator';
			
				jQuery('#' + newLevelListID).append(indicator);
			}
		}
		// --------------------------------------------------------------------------------
		// Remove spinning progress indicator from specified level list during partial load
		//
		// @param string newLevelDivID The ID of the <div> containing the level
		//
		that.hideIncrementalLoadIndicator = function(newLevelListID) {
			if (!that.indicatorUrl && !that.indicator) { return; }
			
			if (that.uiStyle == 'horizontal') {
				jQuery('#' + newLevelListID + ' li._indicator').remove();		// hide loading indicator
			}
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
			return that.levelDivs.length;
		}
		// --------------------------------------------------------------------------------
		// Generate display string for current location display. If a template is defined (in that.currentSelectionDisplayFormat)
		// and caDisplayTemplateParser has been loaded we will evaluate the template with the following tags: ^current (current location name),
		// ^parent (name of parent of current location) and ^hierarchy (the full hierarchical path to the current location). For hierarchy
		// a delimiter can be set by appending a delimiter option (Eg. ^hierarchy%delimiter=;)
		//
		that._getCurrentSelectionStr = function(item, level) {
			if(caDisplayTemplateParser.processTemplate) {
				var str = that.currentSelectionDisplayFormat;
				var tags = caDisplayTemplateParser.getTagList(str);
				
				var hierarchy = '';
				for(var i in tags) {
					var tag = tags[i];
					var opts = caDisplayTemplateParser.parseTagOpts(tag);
					if (opts.tag === 'hierarchy') {
						var h = [];
						var delimiter = opts['delimiter'];
						if (!delimiter) { delimiter = '; '; }
						
						var l = level - 1;
						var parent_id = item.parent_id;
						
						while((l >= 0) && (parent_id > 0)) {
							var pitem = jQuery('#hierBrowser_' + that.name + '_level_' + (l) + '_item_' + parent_id).data('item');
							if (!pitem) { break; }
							h.push(pitem.name);
							l--;
							parent_id = pitem.parent_id;
						}
						hierarchy = h.reverse().join(delimiter);
					}
				}
					
				var parent = jQuery('#hierBrowser_' + that.name + '_level_' + (level-1) + '_item_' + item.parent_id).data('item');
			
				return caDisplayTemplateParser.processTemplate(str, { 'current': jQuery("<div>" + item.name + "</div>").text(), 'parent': jQuery("<div>" + (parent ? parent.name : '') + "</div>").text(), 'hierarchy': jQuery("<div>" + hierarchy + "</div>").text() });
			}
			return item.name;
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
