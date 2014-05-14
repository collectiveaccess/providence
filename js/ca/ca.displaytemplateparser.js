/* ----------------------------------------------------------------------
 * js/ca/ca.displaytemplateparser.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
	caUI.initDisplayTemplateParser = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			
		}, options);
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.processTemplate = function(template, values) {
			var t = template;
				
			// get tags from template
			var tagRegex = /\^([\/A-Za-z0-9]+\[[\@\[\]\=\'A-Za-z0-9\.\-\/]+|[A-Za-z0-9_\.:\/]+[%]{1}[^ \^\t\r\n\"\'<>\(\)\{\}\/]*|[A-Za-z0-9_\.~:\/]+)/g;
			var tagList = template.match(tagRegex)
			
			jQuery.each(tagList, function(i, tag) {
				if(tag.indexOf("~") == -1) {
					var tagProc = tag.replace("^", "");
					t=t.replace(tag, jQuery(values[tagProc]).val());
				} else {
					var tagBits = tag.split(/\~/);
					console.log(tagBits);
					var tagRoot = tagBits[0].replace("^", "");
					var tagProc = tag.replace("^", "");
					var cmd = tagBits[1].split(/\:/);
					switch(cmd[0].toLowerCase()) {
						case 'units':
							var val = jQuery(values[tagRoot]).val();
							
							val = val.replace('"', "in");
							val = val.replace("'", "ft");
							
							try {
								var qty = new Qty(val);
								t=t.replace(tag, qty.to(cmd[1]));
							} catch(e) {
								// noop
							}
							break;
					}
				}
			});
			return t;
		}
		
		
		
		// --------------------------------------------------------------------------------
		
		return that;
	};
	
	caDisplayTemplateParser = caUI.initDisplayTemplateParser();
})(jQuery);