/* ----------------------------------------------------------------------
 * js/ca/ca.displaytemplateparser.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2017 Whirl-i-Gig
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

(function (jQuery) {
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
        
        that.fractionTable = {
        	"½": "1/2",
        	"⅓": "1/3",
        	"¼": "1/4",
        	"⅛": "1/8",
        	"⅔": "2/3",
        	"¾": "3/4",
        	"⅜": "3/8",
        	"⅝": "5/8",
        	"⅞": "7/8",
        	"⅒": "1/10"
        }
        // --------------------------------------------------------------------------------
        // Define methods
        // --------------------------------------------------------------------------------
        that.processDependentTemplate = function(template, values, init) {
        	if (!template) return '';
            var t = template;
            
            // get tags from template
            var tagRegex = /\^([\/A-Za-z0-9]+\[[\@\[\]\=\'A-Za-z0-9\.\-\/]+|[A-Za-z0-9_\.:\/]+[%]{1}[^ \^\t\r\n\"\'<>\(\)\{\}\/]*|[A-Za-z0-9_\.~:\/]+)/g;
            var tagList = template.match(tagRegex);
            var unitRegex = /[\d\.\,]+(.*)$/;

            var bAtLeastOneValueIsSet = false;
            
            jQuery.each(tagList, function(i, tag) {
                var tagProc = tag.replace("^", "");
                if(tag.indexOf("~") === -1) {
                    var selected = jQuery('select' + values[tagProc] + ' option:selected');
                    
                    var d;
                    if (selected.length) {
                        t=t.replace(tag, d = selected.text());
                    } else {
                        t=t.replace(tag, d = jQuery(values[tagProc]).val());
                    }
                    if (d) { bAtLeastOneValueIsSet = true; }
                } else {
                    var tagBits = tag.split(/\~/);
                    var tagRoot = tagBits[0].replace("^", "");
                    var cmd = tagBits[1].split(/\:/);
                    
                    switch(cmd[0].toLowerCase()) {
                        case 'units':
                            var val = jQuery(values[tagRoot]).val();
                            val = val.replace(/[,]+/g, '');
                            if (val) { bAtLeastOneValueIsSet = true; }
                            
                            
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
                                switch(cmd[1]) {
                                    case 'units':
                                        t=t.replace(tag, qty.to(cmd[1]).toString());
                                        break;
                                    case 'infrac':
                                        var float = qty.to('in').toPrec(0.01).toString();
                                        t=t.replace(tag, that.convertLengthToFractions(float, 16));
                                        break;
                                    default:
                                        t=t.replace(tag, qty.to(cmd[1]).toPrec(0.01).toString());
                                        break;
                                }
                            } catch(e) {
                                // noop - replace tag with existing value
                                t=t.replace(tag, val);
                            }
                            break;
                    }
                }
            });

			if (init && !bAtLeastOneValueIsSet) {return; }
			
            // Process <ifdef> tags
            var h = jQuery("<div>" + t + "</div>");
            jQuery.each(tagList, function(k, tag) {
                var tagBits = tag.split(/\~/);
                var tagRoot = tagBits[0].replace("^", "");
                var val = jQuery(values[tagRoot]).val();

                if(val && (val.length > 0)) {
                    jQuery.each(h.find("ifdef[code=" + tagRoot + "]"), function(k, v) {
                        jQuery(v).replaceWith(jQuery(v).html());
                    });
                } else {
                    h.find("ifdef[code=" + tagRoot + "]").remove();
                }
            });
            return h.html().trim();
        };
        // --------------------------------------------------------------------------------
        // helpers
        // --------------------------------------------------------------------------------
        that.convertFractionalNumberToDecimal = function(fractionalExpression, locale) {
            if(!fractionalExpression) { return ''; }
            // convert ascii fractions (eg. 1/2) to decimal
            var matches;
            
            // Convert Unicode fractions to ascii text
			for(frac in that.fractionTable) {
				fractionalExpression = fractionalExpression.replace(frac, that.fractionTable[frac]);
			}
            if (matches = fractionalExpression.match(/^([\d]*)[ ]*([\d]+)\/([\d]+)/)) {
                var val = '';
                
                
                if (parseFloat(matches[2]) > 0) {
                    val = parseFloat(matches[2])/parseFloat(matches[3]);
                }

                if(parseFloat(matches[1]) > 0) {
                    val += parseFloat(matches[1]);
                }

                fractionalExpression = fractionalExpression.replace(matches[0], val);
            }

            return fractionalExpression;
        };
        // --------------------------------------------------------------------------------
        /**
         * This is the JS version of caLengthToFractions() in utilityHelpers.php
         * @param {string} inches
         * @param {int} denom
         * @returns {string}
         */
        that.convertLengthToFractions = function(inches, denom) {
            var inches_as_float = parseFloat(inches.replace(/[^0-9\.]+/, ''));

			if (String(inches_as_float).match("\.1[0]*$")) { 
				denom = 10; 
			}
			
            var num = Math.round(inches_as_float * denom);
            var int = parseInt(num / denom);

            num %= denom;

            if (!num) {
                return "" + int + " in";
            }

            // Use Euclid's algorithm to find the GCD.
            var a = num < 0 ? -num : num;
            var b = denom;
            while (b) {
                var t = b;
                b = a % t;
                a = t;
            }

            num /= a;
            denom /= a;
            
            frac = num + "/" + denom;
            for(f in that.fractionTable) {
            	if (that.fractionTable[f] === frac) {
            		frac = f;
            	}
            }

            if (int) {
                if (num < 0) {
                    num *= -1;
                }
                return "" + int + " " + frac + " in";
            }

            return "" + frac + " in";
        };
        // --------------------------------------------------------------------------------
        return that;
    };

    caDisplayTemplateParser = caUI.initDisplayTemplateParser();
})(jQuery);