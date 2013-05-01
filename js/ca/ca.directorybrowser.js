/* ----------------------------------------------------------------------
 * js/ca/ca.directorybrowser.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
	caUI.initDirectoryBrowser = function(container, options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			container: container,
			
			levelDataUrl: '',
			initDataUrl: '',
			
			name: options.name ? options.name : container.replace(/[^A-Za-z0-9]+/, ''),
			levelWidth: 230,
			browserWidth: 500,
			
			readOnly: false,	// if set to true, no navigation is allowed
			
			displayFiles: false,
			allowFileSelection: false,
			
			initItemID: null,		// if set, hierarchy opens with specified item_id selected
			defaultItemID: null,	// set to default value to show when no initItemID is set; note that initItemID is an ID to open with *and select.* defaultItemID merely specifies an item to open with, but not select.
			
			className: 'directoryBrowserLevel',
			classNameSelected: 'directoryBrowserLevelSelected',
			classNameContainer: 'directoryBrowserContainer',
			
			currentSelectionDisplayID: '',
			currentSelectionDisplayFormat: '%1',
			currentSelectionIDID: '',
			allowSelection: true,
			
			selectOnLoad: false,
			onSelection: null,		/* function to call whenever an item is selected; passed item_id, parent_id, name, formatted display string and type ("FILE" or "DIR") */
			
			displayCurrentSelectionOnLoad: true,
			
			allowDragAndDropUpload: true,
			dragAndDropUploadUrl: null,
			uploadProgressID: null,
			uploadProgressBarID: null,
			uploadProgressStatusID: null,
			uploadProgressMessage: "%1",
			
			indicatorUrl: '',
			openDirectoryIcon: '',
			folderIcon: '',
			fileIcon: '',
			
			hasChildrenIndicator: 'has_children',	/* name of key in data to use to determine if an item has children */
			
			levelLists: [],
			selectedItemIDs: [],
			
			_numOpenLoads: 0,					// number of AJAX loads pending
			_openLoadsForLevel:[],				// counts of loads pending per-level
			_pageLoadsForLevel:[],				// log of which pages per-level have been loaded already
			_queuedLoadsForLevel: [],			// parameters for pending loads per-level
			
			maxItemsPerHierarchyLevelPage: 100	// maximum number of items to load at one time into a level
		}, options);
		
		if (!that.levelDataUrl) { 
			alert("No level data url specified for " + that.name + "!");
			return null;
		}
		
		// create scrolling container
		jQuery('#' + that.container).append("<div class='" + that.classNameContainer + "' id='" + that.container + "_scrolling_container'></div>");
		
		// --------------------------------------------------------------------------------
		// BEGIN method definitions
		// --------------------------------------------------------------------------------
		// Set up initial state and all levels of hierarchy. The item_id parameter will be used to determine the root
		// of the hierarchy if set. 
		//
		// @param int item_id The database id of the item to be used as the root of the hierarchy. 
		//
		that.setUpHierarchy = function(item_id) {
			if (!item_id) { that.setUpHierarchyLevel(0, '/', 1, null, true); return; }
			that.levelLists = [];
			that.selectedItemIDs = [];
			jQuery.getJSON(that.initDataUrl, { id: item_id}, function(data) {
				if (data.length) {
					that.selectedItemIDs = data.join(';').split(';');
					data.unshift("/");
				} else {
					data = ["/"];
				}
				var l = 0;
				jQuery.each(data, function(i, id) {
					that.setUpHierarchyLevel(i, id, 1, item_id);
					l++;
				});
				that.loadHierarchyLevelData();
				
				jQuery('#' + that.container + '_scrolling_container').animate({scrollLeft: l * that.levelWidth}, 500);
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
			while(jQuery('#directoryBrowser_' + that.name + '_' + l).length > 0) {
				jQuery('#directoryBrowser_' + that.name + '_' + l).remove();
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
			
			if (!item_id) { item_id = '/'; }
			if (!is_init) { is_init = 0; }

			// Create div to enclose new level
			var newLevelDivID = 'directoryBrowser_' + that.name + '_' + level;
			var newLevelListID = 'directoryBrowser_' + that.name + '_list_' + level;
			
			if(!is_init) { jQuery('#' + newLevelDivID).remove(); }
			
			var newLevelDiv = "<div class='" + that.className + "' style='left:" + (that.levelWidth * level) + "px;' id='" + newLevelDivID + "'></div>";
			
			// Create new <ul> to display list of items
			var newLevelList = "<ul class='" + that.className + "' id='" + newLevelListID + "'></ul>";
			
			jQuery('#' + that.container + '_scrolling_container').append(newLevelDiv);
			jQuery('#' + newLevelDivID).data('level', level);
			jQuery('#' + newLevelDivID).data('parent_id', item_id);
			jQuery('#' + newLevelDivID).append(newLevelList);
			
			that.showIndicator(newLevelDivID);

			var l = jQuery('#' + newLevelDivID).data('level');
			that._pageLoadsForLevel[l] = [true];
			that.queueHierarchyLevelDataLoad(level, item_id, is_init, newLevelDivID, newLevelListID, selected_item_id, 0, fetchData);
			
			that.levelLists[level] = newLevelDivID;
			
			var cpath = that.selectedItemIDs.slice(0,level);
			
			if (that.allowDragAndDropUpload && that.dragAndDropUploadUrl) {
				jQuery('#' + newLevelDivID).fileupload({
					dataType: 'json',
					url: that.dragAndDropUploadUrl + "?path=" + encodeURIComponent("/" + cpath.join("/")),
					dropZone: jQuery('#' + newLevelDivID),
					singleFileUploads: false,
					done: function (e, data) {
						if (data.result.error) {
							if (that.uploadProgressStatusID) {
								jQuery("#" + that.uploadProgressID).show(250);
								jQuery("#" + that.uploadProgressStatusID).html(data.result.error);
								setTimeout(function() {
									jQuery("#" + that.uploadProgressID).hide(250);
								}, 3000);
							}
						} else {
							var msg = [];
							
							if (data.result.uploadMessage) {
								msg.push(data.result.uploadMessage);
							}
							if (data.result.skippedMessage) {
								msg.push(data.result.skippedMessage);
							}
							jQuery("#" + that.uploadProgressStatusID).html(msg.join('; '));
							setTimeout(function() {
									jQuery("#" + that.uploadProgressID).hide(250);
								}, 3000);
							that.setUpHierarchyLevel(level, item_id, is_init, selected_item_id, true);	// reload file list
						}
					},
					progressall: function (e, data) {
						if (that.uploadProgressID) {
							if (jQuery("#" + that.uploadProgressID).css('display') == 'none') {
								jQuery("#" + that.uploadProgressID).show(250);
							}
							var progress = parseInt(data.loaded / data.total * 100, 10);
							if (that.uploadProgressBarID) {
								jQuery('#' + that.uploadProgressBarID).progressbar("value", progress);
							}
							if (that.uploadProgressStatusID) {
								var msg = that.uploadProgressMessage;
								jQuery("#" + that.uploadProgressStatusID).html(msg.replace("%1", that.formatFilesize(data.loaded) + " (" + progress + "%)"));
							}
						}
					}
				});
			}
			
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
			var path = [];
			for(var l = 0; l < that._queuedLoadsForLevel.length; l++) {
				for(var i = 0; i < that._queuedLoadsForLevel[l].length; i++) {
					var p = that.selectedItemIDs.slice(0, (l > 0) ? l-1 : 0).join("/");
					
					var item_id = that._queuedLoadsForLevel[l][i]['item_id'];
					id_list.push(p + '/' + ((item_id != '/') ? item_id : '') +':'+that._queuedLoadsForLevel[l][i]['start']);
					
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
			if (!id_list.length) { return; }
			var start = 0;
			
			var params = { id: id_list.join(';'), init: is_init ? 1 : '', start: start * that.maxItemsPerHierarchyLevelPage, max: that.maxItemsPerHierarchyLevelPage };
			
			jQuery.getJSON(that.levelDataUrl, params, function(dataForLevels) {
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
							
							if (that.selectedItemIDs[level] == item['item_id']) {
								foundSelected = true;
								if (level >= (that.selectedItemIDs.length - 1)) {
									that.selectItem(level, that.selectedItemIDs[level], jQuery('#' + newLevelDivID).data('parent_id'), item[that.hasChildrenIndicator], item);
								}
							}
						
							var icon = '';
							var countText = '';
							var childCount = 0;
							switch(item.type) {
								case 'FILE':
									if (!that.displayFiles) { return; }
									icon = that.fileIcon;
									break;
								case 'DIR':
									icon = that.folderIcon;
									childCount = ((that.displayFiles) ? item.children : item.subdirectories);
									countText = ' (' + childCount + ')';
									break;
							}
							
							var moreButton = '';
							var item_id_for_css = item['item_id'].replace(/[^A-Za-z0-9_\-]+/g, '_');
							if ((that.openDirectoryIcon) && (item.type == 'DIR')) {
								if (childCount > 0) {
									moreButton = "<div style='float: right;'><a href='#' id='directoryBrowser_" + that.name + '_level_' + level + '_item_' + item_id_for_css + "_open' >" + that.openDirectoryIcon + "</a></div>";
								} else {
									moreButton = "<div style='float: right;'><a href='#' id='directoryBrowser_" + that.name + '_level_' + level + '_item_' + item_id_for_css + "_open'  style='opacity: 0.3;'>" + that.openDirectoryIcon + "</a></div>";
								}
							}
							
							
							if ((item.type == 'FILE') && (!that.allowFileSelection)) {
								jQuery('#' + newLevelListID).append(
									"<li class='" + that.className + "'><a href='#' id='directoryBrowser_" + that.name + '_level_' + level + '_item_' + item_id_for_css + "' class='" + that.className + "' title='" + item.fullname + "' style='opacity: 0.5;'>" + icon +  item.name + "</a></li>"
								);
							} else {
								jQuery('#' + newLevelListID).append(
									"<li class='" + that.className + "'>" + moreButton +"<a href='#' id='directoryBrowser_" + that.name + '_level_' + level + '_item_' + item_id_for_css + "' class='" + that.className + "' title='" + item.fullname + "'>" + icon +  item.name + countText + "</a></li>"
								);
							}
							
							jQuery('#' + newLevelListID + " li:last a").data('item_id', item['item_id']);
							
							if (that.hasChildrenIndicator) {
								jQuery('#' + newLevelListID + " li:last a").data('has_children', item[that.hasChildrenIndicator] ? true : false);
							}
							
							// select
							if ((item.type == 'DIR') || ((item.type == 'FILE') && (that.allowFileSelection))) {
								jQuery('#' + newLevelListID + " li:last a:last").click(function() { 						
									var l = jQuery(this).parent().parent().parent().data('level');
									var item_id = jQuery(this).data('item_id');
									var has_children = jQuery(this).data('has_children');
									that.clearLevelsStartingAt(l+1);
									that.selectItem(l, item_id, jQuery('#' + newLevelDivID).data('parent_id'), has_children, item);
									return false;
								});
							}
							
							if (item.type == 'DIR') {
								// open directory navigation
								if (!that.readOnly) { // && (item.children > 0)) {
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
								} else {
									jQuery('#' + newLevelListID + " li:last a:first").click(function() { 
										return false;
									});
								}
							}
	
							// Pass item_id to caller if required
							if (is_init && that.selectOnLoad && that.onSelection && is_init && item['item_id'] == selected_item_id) {
								var formattedDisplayString = that.currentSelectionDisplayFormat.replace('%1', item.name);
								that.onSelection(item['item_id'], that.selectedItemIDs.join("/"), item.name, item.type);
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
						
								that.queueHierarchyLevelDataLoad(level, item_id, is_init, newLevelDivID, newLevelListID, selected_item_id, p * that.maxItemsPerHierarchyLevelPage, true);
							
								dontDoSelectAndScroll = true;	// we're still trying to find selected item so don't try to select it
							}
						}
					} else {
						// Treat sequential page load as init so selected item is highlighted
						is_init = true;
					}
					
					if (!is_init) {
						that.selectedItemIDs[level-1] = item_id;
						var item_id_for_css = item_id.replace(/[^A-Za-z0-9_\-]+/g, '_');
						//jQuery('#' + newLevelListID + ' a').removeClass(that.classNameSelected).addClass(that.className);
						jQuery('#directoryBrowser_' + that.name + '_' + (level - 1) + ' a').removeClass(that.classNameSelected).addClass(that.className);
						jQuery('#directoryBrowser_' + that.name + '_level_' + (level - 1) + '_item_' + item_id_for_css).addClass(that.classNameSelected);
					} else {
						if ((that.selectedItemIDs[level] !== undefined) && !dontDoSelectAndScroll) {
							var item_id_for_css = that.selectedItemIDs[level].replace(/[^A-Za-z0-9_\-]+/g, '_');
							jQuery('#directoryBrowser_' + that.name + '_level_' + (level) + '_item_' + item_id_for_css).addClass(that.classNameSelected);
							jQuery('#directoryBrowser_' + that.name + '_' + level).scrollTo('#directoryBrowser_' + that.name + '_level_' + level + '_item_' + item_id_for_css);
						}
					}
	
					that._numOpenLoads--;
					that._openLoadsForLevel[level] = false;
					
					// Handle loading of long hierarchy levels via ajax on scroll

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
					
					that.hideIndicator(newLevelDivID);
					
					// try to load any outstanding level pages
					that.loadHierarchyLevelData();
				});
			});
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
			
			while(that.selectedItemIDs.length > level) {
				that.selectedItemIDs.pop();
			}
			that.selectedItemIDs.push(item_id);
			
			var item_id_for_css = item_id.replace(/[^A-Za-z0-9_\-]+/g, '_');
			jQuery('#directoryBrowser_' + that.name + '_' + level + ' a').removeClass(that.classNameSelected).addClass(that.className);
			jQuery('#directoryBrowser_' + that.name + '_level_' + level + '_item_' + item_id_for_css).addClass(that.classNameSelected);
		
			if (that.onSelection) {
				that.onSelection(item_id, that.selectedItemIDs.join("/"), item.name, item.type);
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
			
			var indicator = document.createElement('img');
			indicator.src = that.indicatorUrl;
			indicator.className = '_indicator';
			indicator.style.position = 'absolute';
			indicator.style.left = '50%';
			indicator.style.top = '50%';
			jQuery('#' + newLevelDivID).append(indicator);
		}
		// --------------------------------------------------------------------------------
		// Convert file size in bytes to display format 
		//
		// @param string The file size in bytes
		//
		that.formatFilesize = function(filesize) {
			if (filesize >= 1073741824) {
				filesize = that.formatNumber(filesize / 1073741824, 2, '.', '') + ' Gb';
			} else { 
				if (filesize >= 1048576) {
					filesize = that.formatNumber(filesize / 1048576, 2, '.', '') + ' Mb';
				} else { 
					if (filesize >= 1024) {
						filesize = that.formatNumber(filesize / 1024, 0) + ' Kb';
					} else {
						filesize = that.formatNumber(filesize, 0) + ' bytes';
					};
				};
			};
			return filesize;
		};
		
		that.formatNumber = function formatNumber( number, decimals, dec_point, thousands_sep ) {
			// http://kevin.vanzonneveld.net
			// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
			// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +     bugfix by: Michael White (http://crestidg.com)
			// +     bugfix by: Benjamin Lupton
			// +     bugfix by: Allan Jensen (http://www.winternet.no)
			// +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)    
			// *     example 1: number_format(1234.5678, 2, '.', '');
			// *     returns 1: 1234.57     
 
			var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
			var d = dec_point == undefined ? "," : dec_point;
			var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
			var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
 
			return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
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