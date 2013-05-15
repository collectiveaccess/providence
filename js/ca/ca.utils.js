/* ----------------------------------------------------------------------
 * js/ca/ca.utils.js
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
	caUI.initUtils = function(options) {
		var that = jQuery.extend({
			
			// Unsaved change warning options
			unsavedChangesWarningMessage: 'You have made changes in this form that you have not yet saved. If you navigate away from this form you will lose your unsaved changes.',
			disableUnsavedChangesWarning: false
		}, options);
		
		that.showUnsavedChangesWarningFlag = false;
		caUI.utils = {};
		//
		// Unsaved change warning methods
		//		
			// Sets whether warning should be shown if user tries to navigate away
			caUI.utils.showUnsavedChangesWarning = function(b) {
				if (b === undefined) { b = true; }
				that.showUnsavedChangesWarningFlag = b ? true : false;
				return this;
			};
			
			// Returns true if warning will be shown if user user tries to navigate away
			caUI.utils.shouldShowUnsavedChangesWarning = function() {
				return that.showUnsavedChangesWarningFlag;
			};
			
			// returns text of warning message
			caUI.utils.getUnsavedChangesWarningMessage = function() {
				return that.unsavedChangesWarningMessage;
			};
			
			// If set to true, no warning will be triggered
			caUI.utils.disableUnsavedChangesWarning = function(b) {
				that.disableUnsavedChangesWarning = b ? true : false;
			};
			
			caUI.utils.getDisableUnsavedChangesWarning = function(b) {
				return that.disableUnsavedChangesWarning;
			};
			
			// init event handler
			window.onbeforeunload = function() { 
				if(!caUI.utils.getDisableUnsavedChangesWarning() && caUI.utils.shouldShowUnsavedChangesWarning()) {
					return caUI.utils.getUnsavedChangesWarningMessage();
				}
			}
			
			// ------------------------------------------------------------------------------------
			
			caUI.utils.sortObj = function(arr, isCaseInsensitive) {
				var sortedKeys = new Array();
				var sortedObj = {};
				
				// Separate keys and sort them
				for (var i in arr){
					sortedKeys.push(i);
				}
				
				if (isCaseInsensitive) {
					sortedKeys.sort(caUI.utils._caseInsensitiveSort);
				} else {
					sortedKeys.sort();
				}
				
				// Reconstruct sorted obj based on keys
				for (var i in sortedKeys){
					sortedObj[sortedKeys[i]] = arr[sortedKeys[i]];
				}
				return sortedObj;
			};
			
			caUI.utils._caseInsensitiveSort = function(a, b) { 
			   var ret = 0;
			   a = a.toLowerCase();
			   b = b.toLowerCase();
			   if(a > b) 
				  ret = 1;
			   if(a < b) 
				  ret = -1; 
			   return ret;
			}
			
			// ------------------------------------------------------------------------------------
			// Update state/province form drop-down based upon country setting
			// Used by BaseModel for text fields with DISPLAY_TYPE DT_COUNTRY_LIST and DT_STATEPROV_LIST
			//
			caUI.utils.updateStateProvinceForCountry = function(e) {
				var data = e.data;
				var stateProvID = data.stateProvID;
				var countryID = data.countryID;
				var statesByCountryList = data.statesByCountryList;
				var stateValue = data.value;
				var mirrorStateProvID = data.mirrorStateProvID;
				var mirrorCountryID = data.mirrorCountryID;
				
				var origStateValue = jQuery('#' + stateProvID + '_select').val();
				
				jQuery('#' + stateProvID + '_select').empty();
				var countryCode = jQuery('#' + countryID).val();
				if (statesByCountryList[countryCode]) {
					for(k in statesByCountryList[countryCode]) {
						jQuery('#' + stateProvID + '_select').append('<option value="' + statesByCountryList[countryCode][k] + '">' + k + '</option>');
						
						if (!stateValue && (origStateValue == statesByCountryList[countryCode][k])) {
							stateValue = origStateValue;
						}
					}
					jQuery('#' + stateProvID + '_text').css('display', 'none').attr('name', stateProvID + '_text');
					jQuery('#' + stateProvID + '_select').css('display', 'inline').attr('name', stateProvID).val(stateValue);
					
					if (mirrorCountryID) {
						jQuery('#' + stateProvID + '_select').change(function() {
							jQuery('#' + mirrorStateProvID + '_select').val(jQuery('#' + stateProvID + '_select').val());
						});
						jQuery('#' + mirrorCountryID + '_select').val(jQuery('#' + countryID + '_select').val());
						caUI.utils.updateStateProvinceForCountry({ data: {stateProvID: mirrorStateProvID, countryID: mirrorCountryID, statesByCountryList: statesByCountryList, value: stateValue}});
					}
				} else {
					jQuery('#' + stateProvID + '_text').css('display', 'inline').attr('name', stateProvID);
					jQuery('#' + stateProvID + '_select').css('display', 'none').attr('name', stateProvID + '_select');
					
					if (mirrorCountryID) {
						jQuery('#' + stateProvID + '_text').change(function() {
							jQuery('#' + mirrorStateProvID + '_text').val(jQuery('#' + stateProvID + '_text').val());
						});
						jQuery('#' + mirrorCountryID + '_select').attr('selectedIndex', jQuery('#' + countryID + '_select').attr('selectedIndex'));
						
						caUI.utils.updateStateProvinceForCountry({ data: {stateProvID: mirrorStateProvID, countryID: mirrorCountryID, statesByCountryList: statesByCountryList}});
					}
				}
			};
			// --------------------------------------------------------------------------------
			// Convert file size in bytes to display format 
			//
			// @param string The file size in bytes
			//
			caUI.utils.formatFilesize = function(filesize) {
				if (filesize >= 1073741824) {
					filesize = caUI.utils.formatNumber(filesize / 1073741824, 2, '.', '') + ' Gb';
				} else { 
					if (filesize >= 1048576) {
						filesize = caUI.utils.formatNumber(filesize / 1048576, 2, '.', '') + ' Mb';
					} else { 
						if (filesize >= 1024) {
							filesize = caUI.utils.formatNumber(filesize / 1024, 0) + ' Kb';
						} else {
							filesize = caUI.utils.formatNumber(filesize, 0) + ' bytes';
						};
					};
				};
				return filesize;
			};
		
			caUI.utils.formatNumber = function formatNumber( number, decimals, dec_point, thousands_sep ) {
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
			};
			
			
			//
			// http://thecodeabode.blogspot.com
			// @author: Ben Kitzelman
			// @updated: 03-03-2013
			//
			caUI.utils.getAcrobatInfo = function() {

				var getBrowserName = function() {
					return this.name = this.name || function() {
						var userAgent = navigator ? navigator.userAgent.toLowerCase() : "other";

						if(userAgent.indexOf("chrome") > -1)        return "chrome";
						else if(userAgent.indexOf("safari") > -1)   return "safari";
						else if(userAgent.indexOf("msie") > -1)     return "ie";
						else if(userAgent.indexOf("firefox") > -1)  return "firefox";
						return userAgent;
					}();
				};

				var getActiveXObject = function(name) {
					try { return new ActiveXObject(name); } catch(e) {}
				};

				var getNavigatorPlugin = function(name) {
					for(key in navigator.plugins) {
						var plugin = navigator.plugins[key];
						if(plugin.name == name) return plugin;
					}
				};

				var getPDFPlugin = function() {
					return this.plugin = this.plugin || function() {
						if(getBrowserName() == 'ie') {
							//
							// load the activeX control
							// AcroPDF.PDF is used by version 7 and later
							// PDF.PdfCtrl is used by version 6 and earlier
							return getActiveXObject('AcroPDF.PDF') || getActiveXObject('PDF.PdfCtrl');
						} else {
							return getNavigatorPlugin('Adobe Acrobat') || getNavigatorPlugin('Chrome PDF Viewer') || getNavigatorPlugin('WebKit built-in PDF');
						}
					}();
				};

				var isAcrobatInstalled = function() {
					return !!getPDFPlugin();
				};

				var getAcrobatVersion = function() {
					try {
						var plugin = getPDFPlugin();

						if(getBrowserName() == 'ie') {
							var versions = plugin.GetVersions().split(',');
							var latest   = versions[0].split('=');
							return parseFloat(latest[1]);
						}

						if(plugin.version) return parseInt(plugin.version);
						return plugin.name
					} catch(e) {
						return null;
					}
				}

				//
				// The returned object
				// 
				return {
					browser:        getBrowserName(),
					acrobat:        isAcrobatInstalled() ? 'installed' : false,
					acrobatVersion: getAcrobatVersion()
				};
			};
			// ------------------------------------------------------------------------------------
		
		return that;
	};
	
	
})(jQuery);