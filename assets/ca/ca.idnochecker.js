/* ----------------------------------------------------------------------
 * js/ca.idnochecker.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
			type_id: null,
			context_id: null,
			checkDupes: false,
			parentValue: null,
			includesSequence: false,
			
			singularAlreadyInUseMessage: 'Identifier is already in use',
			pluralAlreadyInUseMessage: 'Identifier is already in use %1 times',
			
			sequenceMessage: '&lt;Will be assigned %1 when saved&gt;',
			
			debounce: null
		}, options);
		
		
		that.checkIDNo = function() { 
			jQuery('#' + that.idnoStatusID).html((that.processIndicator ? that.processIndicator : ''));
			var ids = jQuery.makeArray(jQuery(that.idnoFormElementIDs.join(',')));
			var vals = [];
			jQuery.each(ids, function() {
				vals.push(this.value);
			});
			var idno = vals.join(that.separator);
			
			clearTimeout(that.debounce);
			
			that.debounce = setTimeout(function(){
                jQuery.getJSON(that.lookupUrl, { n: idno, id: that.row_id, type_id: that.type_id, _context_id: that.context_id, parentValue: that.parentValue }, 
                    function(data) {
                        jQuery('#' + that.idnoStatusID).html('').hide(0);
                        if(that.checkDupes) {
                            if (
                                (
                                    (data.matches) &&
                                    (data.matches.length > 1) &&
                                    (jQuery.inArray(that.row_id, data.matches) === -1)
                                ) ||
                                (
                                    (data.matches.length == 1) &&
                                    (parseInt(data.matches[0]) !== parseInt(that.row_id))
                                )
                            ) {
                                var msg;
                                if (data.matches.length == 1) {
                                    msg = that.singularAlreadyInUseMessage;
                                } else {
                                    msg = that.pluralAlreadyInUseMessage.replace('%1', '' + data.matches.length);
                                }
                                if (that.searchUrl) {
                                    msg = "<a href='" + that.searchUrl + idno + "'>" + msg + "</a>";
                                }
                                jQuery('#' + that.idnoStatusID).html((that.errorIcon ? that.errorIcon + ' ' : '') + msg).show(0);
                            }
                        }
                        if(that.includesSequence) {
                        	console.log(data);
                            for(var k in data.sequences) {
                                for(var j in that.idnoFormElementIDs) {
                                    if((that.idnoFormElementIDs[j] === ('#idno_' + k)) || (that.idnoFormElementIDs[j] === ('#idno_stub_' + k))) {
                                        jQuery(that.idnoFormElementIDs[j]).html(that.sequenceMessage.replace('%1', data.sequences[k]));
                                    }
                                }
                            }
                        }
                    }
                );
            }, 250);
		}
		
		jQuery(that.idnoFormElementIDs.join(',')).bind('change keyup', that.checkIDNo);
		
		that.checkIDNo();
		return that;
	};
})(jQuery);
