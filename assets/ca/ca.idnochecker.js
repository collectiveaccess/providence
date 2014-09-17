/* ----------------------------------------------------------------------
 * js/ca/ca.idnochecker.js
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
	caUI.initIDNoChecker = function(options) {
		var that = jQuery.extend({
			errorIcon: null,
			processIndicator: null,
			idnoStatusID: 'idnoStatus',
			lookupUrl: null,
			searchUrl: null,
			idnoFormElementIDs: [],
			separator: '.',
			row_id: null,
			context_id: null,
			
			singularAlreadyInUseMessage: 'Identifier is already in use',
			pluralAlreadyInUseMessage: 'Identifier is already in use %1 times'
		}, options);
		
		
		that.checkIDNo = function() { 
			jQuery('#' + that.idnoStatusID).html((that.processIndicator ? '<img src=\'' + that.processIndicator + '\' border=\'0\'/>' : ''));
			var ids = jQuery.makeArray(jQuery(that.idnoFormElementIDs.join(',')));
			
			var vals = [];
			jQuery.each(ids, function() {
				vals.push(this.value);
			});
			var idno = vals.join(that.separator);
			jQuery.getJSON(that.lookupUrl, { n: idno, id: that.row_id, _context_id: that.context_id }, 
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
						var msg;
						if (data.length == 1) {
							msg = that.singularAlreadyInUseMessage;
						} else {
							msg = that.pluralAlreadyInUseMessage.replace('%1', '' + data.length);
						}
						if (that.searchUrl) {
							msg = "<a href='" + that.searchUrl + idno + "'>" + msg + "</a>";
						}
						jQuery('#' + that.idnoStatusID).html((that.errorIcon ? '<img src=\'' + that.errorIcon + '\' border=\'0\'/> ' : '') + msg).show(0);
					} else{
						jQuery('#' + that.idnoStatusID).html('').hide(0);
					}
				}
			);
		}
		
		jQuery(that.idnoFormElementIDs.join(',')).bind('change keyup', that.checkIDNo);
		
		that.checkIDNo();
		return that;
	};
})(jQuery);