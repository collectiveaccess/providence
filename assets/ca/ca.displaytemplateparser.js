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


		that.unitTable = {
			// Length
			'"': "in", "”": "in", "in.": "in", "inch": "in", "inches": "in",
			"'": "ft", "’": "ft", "ft.": "ft", "foot": "ft", "feet": "ft",
			"m.": "m", "meter": "m", "meters": "m", "metre": "m", "metres": "m", "mt": "m",
			"cm.": "cm", "centimeter": "cm", "centimeters": "cm", "centimetre": "cm", "centimetres": "cm",
			"mm.": "mm", "millimeter": "mm", "millimeters": "mm", "millimetre": "mm", "millimetres": "mm",
			"k": "kilometer", "km": "kilometer", "kilometers": "kilometer", "kilometre": "kilometer", "kilometres": "kilometer",
			"pt": "point", "pt.": "point",
			"mile": "miles", "mi" : "miles",

			// Weight
			"lbs": "pounds", "lb": "pounds", "lb.": "pounds", "pound": "pounds",
			"kg": "kilograms", "kg.": "kilograms", "kilo": "kilograms", "kilos": "kilograms", "kilogram": "kilograms",
			"g": "grams", "g.": "grams", "gr": "grams", "gr.": "grams", "gram": "grams",
			"mg": "milligrams", "mg.": "milligrams", "milligram": "milligrams",
			"oz": "ounces", "oz.": "ounces", "ounce": "ounces",
			"tons": "ton", "tonne": "ton", "tonnes": "ton", "t": "ton", "t." : "ton"
		};
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.processDependentTemplate = function(template, values) {
			var t = template;

			// get tags from template
			var tagRegex = /\^([\/A-Za-z0-9]+\[[\@\[\]\=\'A-Za-z0-9\.\-\/]+|[A-Za-z0-9_\.:\/]+[%]{1}[^ \^\t\r\n\"\'<>\(\)\{\}\/]*|[A-Za-z0-9_\.~:\/]+)/g;
			var tagList = template.match(tagRegex);
			var unitRegex = /[\d\.\,]+(.*)$/;

			jQuery.each(tagList, function(i, tag) {
				var tagProc = tag.replace("^", "");
				if(tag.indexOf("~") === -1) {
					var selected = jQuery('select' + values[tagProc] + ' option:selected');
					if (selected.length) {
						t=t.replace(tag, selected.text());
					} else {
						t=t.replace(tag, jQuery(values[tagProc]).val());
					}
				} else {
					var tagBits = tag.split(/\~/);
					var tagRoot = tagBits[0].replace("^", "");
					var cmd = tagBits[1].split(/\:/);
					switch(cmd[0].toLowerCase()) {
						case 'units':
							var val = jQuery(values[tagRoot]).val();
							val = that.convertFractionalNumberToDecimal(val);

							var unitBits = val.match(unitRegex);
							if (!unitBits || unitBits.length < 2) {
								t = t.replace(tag, val);
								break;
							}
							var units = unitBits[1].trim();

							if (that.unitTable[units]) {
								val = val.replace(units, that.unitTable[units]);
							}

							try {
								var qty = new Qty(val);
								if(cmd[1] == units) { // do not round if unit is unchanged!
									t=t.replace(tag, qty.to(cmd[1]).toString());
								} else {
									t=t.replace(tag, qty.to(cmd[1]).toPrec(0.01).toString());
								}
							} catch(e) {
								// noop - replace tag with existing value
								t=t.replace(tag, val);
							}
							break;
					}
				}
			});

			// Process <ifdef> tags
			var $h = jQuery("<div>" + t + "</div>");
			jQuery.each(tagList, function(k, tag) {
				var tagBits = tag.split(/\~/);
				var tagRoot = tagBits[0].replace("^", "");
				var val = jQuery(values[tagRoot]).val();

				if(val && (val.length > 0)) {
					jQuery.each($h.find("ifdef[code=" + tagRoot + "]"), function(k, v) {
						jQuery(v).replaceWith(jQuery(v).html());
					});
				} else {
					$h.find("ifdef[code=" + tagRoot + "]").remove();
				}
			});
			return $h.html().trim();
		};
		// --------------------------------------------------------------------------------
		// helpers
		// --------------------------------------------------------------------------------
		that.convertFractionalNumberToDecimal = function(fractionalExpression, locale) {
			if(!fractionalExpression) { return ''; }
			// convert ascii fractions (eg. 1/2) to decimal
			var matches;
			if (matches = fractionalExpression.match(/^([\d]*)[ ]*([\d]+)\/([\d]+)/)) {
				var val = '';
				if (parseFloat(matches[2]) > 0) {
					val = parseFloat(matches[2])/parseFloat(matches[3]);
				}

				val += parseFloat(matches[1]);

				fractionalExpression = fractionalExpression.replace(matches[0], val);
			}

			return fractionalExpression;
		};

		// --------------------------------------------------------------------------------

		return that;
	};

	caDisplayTemplateParser = caUI.initDisplayTemplateParser();
})(jQuery);
