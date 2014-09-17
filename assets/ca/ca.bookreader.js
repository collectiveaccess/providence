/* ----------------------------------------------------------------------
 * js/ca/ca.bookreader.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
	caUI.initBookReader = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			containerID: 'BookReader',
			bookreader: null,
			docURL: null,
			width: '100%',
			height: '100%',
			page: 1,
			sidebar: false,
			editButton: 'Edit',
			downloadButton: 'Download',
			closeButton: 'Close',
			sectionsAreSelectable: false,
			selectionRecordURL: null
		}, options);
		
		 that.bookreader = DV.load(that.docURL, {
			container: '#' + that.containerID,
			width: that.width,
			height: that.height,
			sidebar: that.sidebar,
			page: that.page,
			editButton: that.editButton,
			downloadButton: that.downloadButton,
			closeButton: that.closeButton,
			sectionsAreSelectable: that.sectionsAreSelectable,
			selectionRecordURL: that.selectionRecordURL,
			search: true, text: false
		  });
		
		// --------------------------------------------------------------------------------
		
		return that;
	};	
})(jQuery);
