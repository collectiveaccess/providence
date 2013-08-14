/* ----------------------------------------------------------------------
 * js/jquery/jquery-handsontable/jquery.handsontale-scrollload.js
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

/**
 * 
 * @constructor
 */
function HandsontableScrollLoad() {
	var plugin = this;
	var rowCheckMap = [];
	var doCheck = false;
	var table;
	
	var userIsLoading = false;
	var isAutoloading = false;
	
	var maxToLoad = 200;
	var autoLoadInterval = 750;

	var autoload = function () {
		if (userIsLoading || isAutoloading) { return; }
		
		var settings = table.getSettings();
		var data = settings.data;
		
		// Load for empty range of rows
		var i;
		var numRows = table.countRows();
		for(i=0; i < numRows; i++) {
			if (!rowCheckMap[i]) {
				isAutoloading = true;
				//console.log("Autoload data starting at " + i + " for " + maxToLoad + " rows");
				
				var start = i;
				var end = i + maxToLoad; 
				if (end > numRows) { end = numRows; }
				jQuery("." + settings.statusDisplayClassName).html("Loading " + start + "-" + end);
				jQuery.getJSON( settings.dataLoadUrl, { start: i, n: maxToLoad }, function(newData, textStatus, jqXHR) {
					jQuery.each(newData, function(k, v) {
						var rowHeaders = table.getRowHeader();
						var rowIndex = i + parseInt(k);

						rowCheckMap[rowIndex] = true;
						data[rowIndex] = v;
						rowHeaders[rowIndex] = (rowIndex + 1) + " " + settings.editLinkFormat.replace("%1", v['item_id']);
					});
					if (end >= numRows) { 
						jQuery("." + settings.statusDisplayClassName).html("Loading complete"); 
						setTimeout(function() {
							jQuery("." + settings.statusDisplayClassName).fadeOut(500);
						}, 5000);
						table.updateSettings({columnSorting: true});
						table.render();
					}
					isAutoloading = false;
				});
				return;
			}
		}
	};
	
	this.afterInit = function () {
		doCheck = true;
		table = this;
		
		setInterval(autoload, autoLoadInterval);
	};

	this.scrollDone = function () {
		if (!doCheck) { return; }

		var settings = table.getSettings();
		var data = settings.data;

		var curRowIndex = parseInt(table.rowOffset());
		var numRows = parseInt(table.countRows());
		
		// Load for empty rows up to half of rows before current
		var i = curRowIndex;
		var lowerBound = curRowIndex - Math.floor(maxToLoad/2);
		if (lowerBound < 0) { lowerBound = 0; }
		
		for(i = curRowIndex; i >= lowerBound; i--) {
			if(rowCheckMap[i]) {
				curRowIndex = i + 1;
				break;
			}
		}
		
		// If start row is not empty then look ahead and see if there's anything that needs to be loaded up ahead
		if(rowCheckMap[curRowIndex]) {
			for(i = curRowIndex; i <= curRowIndex + Math.ceil(maxToLoad/2); i++) {
				if (curRowIndex >= numRows) { break; }
				if(!rowCheckMap[i]) {
					curRowIndex = i;
					break;
				}
			}
		}
		if (curRowIndex >= numRows) { return; }
		if(!rowCheckMap[curRowIndex]) {
			//console.log("Load data starting at " + curRowIndex + " for " + maxToLoad + " rows");
			
			userIsLoading = true;
			var n = numRows - curRowIndex;
			if (n > maxToLoad) { n = maxToLoad; }
			if (n <= 0) { return; }
			jQuery.getJSON( settings.dataLoadUrl, { start: curRowIndex, n: n }, function(newData, textStatus, jqXHR) {
				jQuery.each(newData, function(k, v) {
					var rowHeaders = table.getRowHeader();
					var rowIndex = curRowIndex + parseInt(k);

					rowCheckMap[rowIndex] = true;
					data[rowIndex] = v;
					rowHeaders[rowIndex] = (rowIndex + 1) + " " + settings.editLinkFormat.replace("%1", v['item_id']);
				});
				userIsLoading = false;
				table.render();
			});
		}
	};
}
var htScrollLoad = new HandsontableScrollLoad();

Handsontable.PluginHooks.add('afterInit', htScrollLoad.afterInit);
Handsontable.PluginHooks.add('scrollDone', htScrollLoad.scrollDone);