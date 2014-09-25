/* ----------------------------------------------------------------------
 * js/jquery.caus.js : javascript-based user interface controls
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2010 Whirl-i-Gig
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

/* Table sorter filtering code */
(function($){
	$.fn.caFormatListTable = function () {
		/* it's up to the user to pass a table, dunno what happens if you pass sth else */
		return this.tablesorter({
			cssAsc: 'list-header-sorted-desc',
			cssDesc: 'list-header-sorted-asc',
			cssHeader: 'list-header-unsorted',
			widgets: ['zebra', 'cookie']
		});
	}
	$.fn.caFilterTable = function (searchText) {
		if (!searchText) { 
			this.find('tbody tr').show();
			return;
		}
		this.find('tbody tr').hide();
		this.find('tbody tr:contains('+searchText+')').show();
		return this;
	}
})(jQuery);

/* Table sorter sort-order persistence widget using jquery.cookiejar */
/* From http://www.jdempster.com/2007/08/13/jquery-tablesorter-cookie-widget/ */
(function($){
	$.tablesorter.addWidget({
		id: 'cookie',
		format: function(table) {
			var sortList = table.config.sortList;
			var tablesorterCookieJar = $.cookieJar('tablesorter');
			if ( sortList.length > 0) {
				tablesorterCookieJar.set($(table).attr('id'), sortList);
			} else {
				var sortList = tablesorterCookieJar.get($(table).attr('id'));
				if (sortList && sortList.length > 0) {
					jQuery(table).trigger('sorton', [sortList]);
				}
			}
		}
	});
})(jQuery);