/* ----------------------------------------------------------------------
 * js/ca/ca.uniquenesschecker.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
	caUI.initUniquenessChecker = function(options) {
		var that = jQuery.extend({
			errorIcon: null,
			processIndicator: null,
			statusID: 'status',
			lookupUrl: null,
			formElementID: null,
			separator: '.',
			table_num: null,
			row_id: null,
			field: null,
			withinFields: {},
			
			alreadyInUseMessage: 'value is already in use',
		}, options);
		
		
		that.checkValue = function() { 
			jQuery('#' + that.statusID).html((that.processIndicator ? '<img src=\'' + that.processIndicator + '\' border=\'0\'/>' : ''));
			
			var val = jQuery('#' + that.formElementID).val();
			jQuery.getJSON(that.lookupUrl, { n: val, table_num: that.table_num, id: that.row_id, field: that.field, withinFields: that.withinFields}, 
				function(data) {
					if (
						(
							(data.length > 1) &&
							(jQuery.inArray(that.row_id, data) === -1)
						) ||
						(
							(data.length == 1) &&
							(parseInt(data) != parseInt(that.row_id))
						)
					) {
						var msg = that.alreadyInUseMessage;
						jQuery('#' + that.statusID).html((that.errorIcon ? '<img src=\'' + that.errorIcon + '\' border=\'0\'/> ' : '') + msg).show(0);
					} else{
						jQuery('#' + that.statusID).html('').hide(0);
					}
				}
			);
		}
		
		jQuery('#' + that.formElementID).bind('change keyup', that.checkValue);
		
		that.checkValue();
		return that;
	};
})(jQuery);