/* ----------------------------------------------------------------------
 * js/ca.displaytemplateparser.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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
            addPeriodAfterUnits: null,
            
            displayFractionsFor: null,
            useUnicodeFractionGlyphsFor: null,
            
            useMillimetersForDisplayUpTo: 1,
            useCentimetersForDisplayUpTo: 99,
            useMetersForDisplayUpTo: 99999,
            useInchesForDisplayUpTo: 72,
            useFeetForDisplayUpTo: 5279,
            
            inchDecimalPrecision: 2,
            feetDecimalPrecision: 1,
            mileDecimalPrecision: 1,
            millimeterDecimalPrecision: 0,
            centimeterDecimalPrecision: 1,
            meterDecimalPrecision: 1,
            kilometerDecimalPrecision: 1
        }, options);


        that.unitTable = {
            // Length
            '"': "in", "”": "in", "in.": "in", "inch": "in", "inches": "in", "in": "in",
            "'": "ft", "’": "ft", "ft.": "ft", "foot": "ft", "feet": "ft", "feet": "ft", "ft": "ft",
            "m.": "m", "meter": "m", "meters": "m", "metre": "m", "metres": "m", "mt": "m", "m": "m", 
            "cm": "cm", "cm.": "cm", "centimeter": "cm", "centimeters": "cm", "centimetre": "cm", "centimetres": "cm",
            "mm": "mm", "mm.": "mm", "millimeter": "mm", "millimeters": "mm", "millimetre": "mm", "millimetres": "mm",
            "kilometer": "kilometer", "kilometers": "kilometer", "k": "kilometer", "km": "kilometer", "kilometers": "kilometer", "kilometre": "kilometer", "kilometres": "kilometer",
            "pt": "point", "pt.": "point",
            "mile": "miles", "mi" : "miles", "miles": "miles", "mi" : "miles",

            // Weight
            "lbs": "pounds", "lb": "pounds", "lb.": "pounds", "pound": "pounds",
            "kg": "kilograms", "kg.": "kilograms", "kilo": "kilograms", "kilos": "kilograms", "kilogram": "kilograms",
            "g": "grams", "g.": "grams", "gr": "grams", "gr.": "grams", "gram": "grams",
            "mg": "milligrams", "mg.": "milligrams", "milligram": "milligrams",
            "oz": "ounces", "oz.": "ounces", "ounce": "ounces",
            "tons": "ton", "tonne": "ton", "tonnes": "ton", "t": "ton", "t." : "ton"
        };
        
        that.units2code = {
            'm': 'METER',
            'cm': 'CENTIMETER',
            'mm': 'MILLIMETER',
            'km': 'KILOMETER',
            
            'in': 'INCH',
            'ft': 'FEET',
            'miles': 'MILE',
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
        };
        // --------------------------------------------------------------------------------
        // Define methods
        // --------------------------------------------------------------------------------
        that.setOptions = function(o) {
            for(var k in o) {
                if (that[k] !== undefined) {
                    that[k] = o[k];
                }
            }
        };
        
        that.processDependentTemplate = function(template, values, init, omitRepeatingUnits=false) {
        	if (!template) return '';
            
            // get tags from template
            var tagRegex = /[\^]+([\/A-Za-z0-9]+\[[\@\[\]\=\'A-Za-z0-9\.\-\/]+|[A-Za-z0-9_\.:\/]+[%]{1}[^ \^\t\r\n\"\'<>\(\)\{\}\/]*|[A-Za-z0-9_\.~:\/]+)/g;
            var tagList = template.match(tagRegex);
            var fullTagList = tagList;
            
            
            // rewrite tags
            var j = 1;
            if (tagList) {
           		jQuery.each(tagList, function(i, tag) {
                if (tag.substring(0,6) === '^^join') {
                    var tagProc = "join_" + j;
                    j++;
                    fullTagList[i] = tagProc;
                    var opts = that.parseTagOpts(tag);
                    var delimiter = opts['delimiter'] ? opts['delimiter'] : "; ";
                    
                    var elementVals = [];
                    for(var i in opts['elements']) {
                        var e = "^" + opts['elements'][i];
                        var tagBits = e.split(/\~/);
                        var tagRoot = tagBits[0].replace("^", "");
                        elementVals.push(jQuery(values[tagRoot]).val());
                    }
                    var elementCount = elementVals.filter((v) => (v !== null) && v.length > 0).length;
                    
                    var acc = [];
                    for(var i in opts['elements']) {
                        var label = (opts['labels'] && opts['labels'][i] && (!opts['maxValuesToShowLabels'] || (parseInt(opts['maxValuesToShowLabels']) >= elementCount)))  ? opts['labels'][i] : '';

                        var e = "^" + opts['elements'][i];
                        if (label) { e = e + " " + label; }
                        
                        var val = elementVals[i];
                        if((val == null) || (val.length == 0))  { continue; }
                        acc.push(e);
                    } 
                    
                    values[tagProc] = acc.join(delimiter);
                    template = template.replace(tag, values[tagProc]);
                    
                    return;
                }
            });
            }
            tagList = template.match(tagRegex);
            var t = template;    
            var unitRegex = /([\.]{0,1}[\d]+[ \d\.\,\/]*)([^\d ]+)/g, bAtLeastOneValueIsSet = false;
            
            var templatevalues = [];
            var lastUnits = null;
            //tagList.reverse();
            
            if (tagList) {
            	jQuery.each(tagList, function(i, tag) {
                var tagProc = tag.replace("^", "");
                if(tag.indexOf("~") === -1) {
                    var selected = jQuery('select' + values[tagProc] + ' option:selected');
                    
                    var d;
                    if (selected.length) {
                        d = selected.text();
                    } else {
                        d = jQuery(values[tagProc]).val();
                    }
                    t=t.replace(tag, d.replace('&nbsp;', ' ').trim());
                    if (d) { bAtLeastOneValueIsSet = true; }
                } else {
                    var tagBits = tag.split(/\~/);
                    var tagRoot = tagBits[0].replace("^", "");
                    var cmd = tagBits[1].split(/\:/);
                    switch(cmd[0].toLowerCase()) {
                        case 'units':
                            var val = jQuery(values[tagRoot]).val();
                            if (val) { val = val.replace(/[,]+/g, ''); }
                            if (val) { bAtLeastOneValueIsSet = true; }
                            
                            val = that.convertFractionalNumberToDecimal(val);

                            var unitBits = null, v = '', total = 0, qty = null, foundValue = false;
                            do {
                                unitBits = unitRegex.exec(val);
                                if (!unitBits || (unitBits.length < 3)) {
                                    // no units - replace as-is so the user has something to look at
                                    if (!foundValue) t = t.replace(tag, val ? '?' : '');
                                    break;
                                }
                                var units = unitBits[2].trim(), unitCode = null, normalizedVal = null;
                                
                                if (that.unitTable[units]) {
                                    var unitDisplay = " " + that.unitTable[units];
                                    if ((unitCode = that.units2code[that.unitTable[units]]) && (that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf(unitCode) !== -1)) {
                                        unitDisplay += ".";
                                    }
                                
                                    val = val.replace(units, unitDisplay);
                                    normalizedVal = that.convertFractionalNumberToDecimal(unitBits[0]).replace(units, that.unitTable[units]);
                                    foundValue = true;
                                } else {
                                    // invalid units; display question mark to alert user to invalidosity
                                    t = t.replace(tag, "?");
                                    break;
                                }
                                try {
                                    if (!qty) {
                                        qty = new Qty(normalizedVal);
                                    } else {
                                        qty = qty.add(normalizedVal);
                                    }
                                } catch(e) {
                                    // noop - replace tag with existing value
                                    t = t.replace(tag, "?");
                                    break;
                                }
                            } while(unitBits);
                                        
                            // format value
                            if (qty) {
                                var emitUnits = (!omitRepeatingUnits || (lastUnits === null));
                                var q, u;
                                switch(cmd[1]) {
                                    case 'english':
                                        var inInches = qty.to('in').toPrec(0.001).scalar;
                                        
                                        if (inInches <= that.useInchesForDisplayUpTo) {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'in');
                                            q = qty.to('in').toPrec(that.getPrecisionForUnit("in")).scalar;
                                            lastUnits = 'in';
                                        } else if (inInches <= that.useFeetForDisplayUpTo) {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'ft');
                                            q = qty.to('ft').toPrec(that.getPrecisionForUnit("ft")).scalar;
                                            lastUnits = 'ft';
                                        } else {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'mi');
                                            q = qty.to('mi').toPrec(that.getPrecisionForUnit("mi")).scalar;
                                            lastUnits = 'mi';
                                        }
                                        templatevalues.push({'value': q, 'units': lastUnits, 'tag': tag, 'type': 'english'});
                                        break;
                                    case 'metric':
                                        var inCM = qty.to('cm').toPrec(0.001).scalar;
                                        
                                        if (inCM <= that.useMillimetersForDisplayUpTo) {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'mm');
                                            q =  qty.to('mm').toPrec(that.getPrecisionForUnit("mm")).scalar;
                                            lastUnits = 'mm';
                                        } else if (inCM <= that.useCentimetersForDisplayUpTo) {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'cm');
                                            q = qty.to('cm').toPrec(that.getPrecisionForUnit("cm")).scalar;
                                            lastUnits = 'cm';
                                        } else if (inCM <= that.useMetersForDisplayUpTo) {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'm');
                                            q = qty.to('m').toPrec(that.getPrecisionForUnit("m")).scalar;
                                            lastUnits = 'm';
                                        } else {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'km');
                                            q = qty.to('km').toPrec(that.getPrecisionForUnit("km")).scalar;
                                            lastUnits = 'km';
                                        }
                                        
                                        templatevalues.push({'value': q, 'units': lastUnits, 'tag': tag, 'type': 'metric'});
                                        break;
                                    case 'fractionalenglish':
                                    case 'infrac':
                                        var inInches = qty.to('in').toPrec(0.00001).scalar;
                                        if (inInches <= that.useInchesForDisplayUpTo) {
                                            emitUnits = (!omitRepeatingUnits) || (lastUnits !== 'in');
                                            q = that.convertLengthToFractions(qty.to('in').toPrec(0.00001).scalar + '', that.getLeastDenominator(), {'includeUnits': false, 'forceFractions': true, 'precision': that.getPrecisionForUnit("in")});
                                            u = 'in';
                                            lastUnits = 'in';
                                        } else if (inInches <= that.useFeetForDisplayUpTo) {
                                            var inFeet = parseInt(inInches / 12);
                                            inInches -= inFeet * 12;
                                            
                                            var vals = [];
                                            
                                            if ((inFeet > 0) && (inInches > 0)) { 
                                                emitUnits = true; 
                                            } else {
                                                emitUnits = (!omitRepeatingUnits) || (lastUnits !== ((inFeet > 0) ? 'ft' : 'in'));
                                            }
                                            if (inFeet > 0) { 
                                                if (inInches == 0) {
                                                    vals.push(inFeet);
                                                    lastUnits = u = 'ft';
                                                } else {
                                                    vals.push(inFeet + (emitUnits ? " ft" : "") + ((emitUnits && (that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf('FEET') !== -1)) ? "." : ""));
                                                    lastUnits = null; u = '';
                                                }
                                            }
                                            if (inInches > 0) {
                                                if (inFeet > 0) {
                                                    vals.push(that.convertLengthToFractions(new Qty(inInches + " in").to('in').toPrec(0.00001).scalar + '', that.getLeastDenominator(), {'includeUnits': emitUnits, 'forceFractions': true, 'precision': that.getPrecisionForUnit("in")}) + (((that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf('INCH') !== -1)) ? "." : ""));
                                                    lastUnits = null; u = '';
                                                } else {
                                                    vals.push(that.convertLengthToFractions(new Qty(inInches + " in").to('in').toPrec(0.00001).scalar + '', that.getLeastDenominator(), {'includeUnits': emitUnits, 'forceFractions': true, 'precision': that.getPrecisionForUnit("in")}));
                                                    lastUnits = u = 'in';
                                                }
                                            }
                                            q = vals.join(" ");
                                        } else {
                                            var inMiles = parseInt(inInches / (5280 * 12));
                                            inInches -= inMiles * (5280 * 12);
                                            var inFeet = parseInt(inInches / 12);
                                            inInches -= inFeet * 12;
                                            
                                            var vals = [];
                                            
                                            u = '';
                                            var x = 0;
                                            if (inMiles > 0) { emitUnits = (lastUnits !== 'mi');  x++; }
                                            if (inFeet > 0) { emitUnits = (lastUnits !== 'ft'); x++; }
                                            if (inInches > 0) { emitUnits = (lastUnits !== 'in'); x++; }
                                            if ((x > 1) || !omitRepeatingUnits) { emitUnits = true; }
                                            
                                            if (inMiles > 0) { 
                                                if (x > 1) {
                                                    vals.push(inMiles + (emitUnits ? " miles" : "") + (((that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf('MILE') !== -1)) ? "." : ""));
                                                } else {
                                                    vals.push(inMiles);
                                                    u = 'miles';
                                                }
                                            }
                                            if (inFeet > 0) { 
                                                if (x > 1) {
                                                    vals.push(inFeet + (emitUnits ? " ft" : "") + (((that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf('FEET') !== -1)) ? "." : ""));
                                                } else {
                                                    vals.push(inFeet);
                                                    u = 'ft';
                                                }
                                            }
                                            if (inInches > 0) {
                                                if (x > 1) {
                                                    vals.push(that.convertLengthToFractions(new Qty(inInches + " in").to('in').toPrec(0.00001).scalar + '', that.getLeastDenominator(), {'includeUnits': emitUnits, 'forceFractions': true, 'precision': that.getPrecisionForUnit("in")}) + ((emitUnits && (that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf('INCH') !== -1)) ? "." : ""));
                                                } else {
                                                    vals.push(that.convertLengthToFractions(new Qty(inInches + " in").to('in').toPrec(0.00001).scalar + '', that.getLeastDenominator(), {'includeUnits': false, 'forceFractions': true, 'precision': that.getPrecisionForUnit("in")}));
                                                    u = 'in';
                                                }
                                            }
                                            lastUnits = null;
                                            q = vals.join(" ");
                                        }
                                        
                                        templatevalues.push({'value': q, 'units': u, 'tag': tag, 'type': 'fractionalenglish'});
                                        break;
                                    // unit directly specified
                                    default:
                                        q = qty.to(cmd[1]).toPrec(that.getPrecisionForUnit(cmd[1])).scalar;
                                        lastUnits = cmd[1];
                                        templatevalues.push({'value': q, 'units': lastUnits, 'tag': tag, 'type': 'direct'});
                                        break;
                                }
                            }
                            break;
                    }
                }
            });
        	}
            templatevalues.push({'type': 'END'});   // Mark end of stream of values
            
            // Determine if and where units are displayed
            var unitMap = [], lastQType = null, acc = [];
            for(var k in templatevalues) {
                var v = templatevalues[k];
                if((lastQType !== null) && (lastQType !== v['type'])) {
                    // on type boundary
                    var uniqueUnits = acc.reduce(function(A, X) { if (A.indexOf(X['units']) === -1 ) { A.push(X['units']) }; return A; }, []);

                    if (uniqueUnits.length == 1) { // emit units only for last
                        var last = acc.pop();
                        jQuery.each(acc, function(k, value) {
                            t = t.replace(value['tag'], value['value']);
                        });
                        
                        t = t.replace(last['tag'], (last['value'] + ' ' + last['units']).trim() + (((that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf(that.units2code[last['units']]) !== -1)) ? "." : ""));
                    } else { // emit units for all
                        jQuery.each(acc, function(k, value) {
                            t = t.replace(value['tag'], (value['value'] + ' ' + value['units']).trim() + (((that.addPeriodAfterUnits) && (that.addPeriodAfterUnits.indexOf(that.units2code[value['units']]) !== -1)) ? "." : ""));
                        });
                    }  
                    
                    acc = [];
                }
                if(v['type'] == 'END') { break; }
                
                acc.push(v);
                lastQType = v['type'];
            }

			if (init && !bAtLeastOneValueIsSet) {return; }
			
            // Process <ifdef> tags
            var h = jQuery("<div>" + t + "</div>");
            
            if (fullTagList) {
				jQuery.each(fullTagList, function(k, tag) {
					var isJoin = false;
					var originalTag = tag;
					if (tag.match(/^join_/)) { 
						tag = values[tag]; 
						isJoin = true;
					}
					var tagBits = tag.split(/\~/);
					var tagRoot = tagBits[0].replace("^", "");
					var val = jQuery(values[tagRoot]).val();

					if(val && (val.length > 0)) {
						jQuery.each(h.find("ifdef[code=" + (isJoin ? originalTag : tagRoot) + "]"), function(k, v) {
							jQuery(v).replaceWith(jQuery(v).html());
						});
					} else {
						h.find("ifdef[code=" + (isJoin ? originalTag : tagRoot) + "]").remove();
					}
				});
			}
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
         *
         * @param {string} inches
         * @param {int} denom
         * @param {Object} opts 
         *      includeUnits = 
         *      precision = 
         *      forceFractions = 
         *      
         * @returns {string}
         */
        that.convertLengthToFractions = function(inches, denom, opts) {
            var includeUnits = opts['includeUnits'] ? true : false;
            var precision = opts['precision'];
            if (!precision) { precision = 0.001; }
            var inches_as_float = parseFloat(inches.replace(/[^0-9\.]+/, ''));

			if (String(inches_as_float).match("\.1[0]*$")) { 
				denom = 10; 
			}
			
            var num = (inches_as_float * denom);
            var i = parseInt(num / denom);
            num %= denom;
            if (!num) {
                return "" + new Qty(i + " in").toPrec(precision).scalar  + (includeUnits ? " in" : "");
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
                if (!that.useUnicodeFractionGlyphsFor || that.useUnicodeFractionGlyphsFor.indexOf(f) === -1) { continue; }  // skip if not in glyph list
            	if (that.fractionTable[f] === frac) {
            		frac = f;
            	}
            }
            
            if(opts['forceFractions']) {
                var v = num/denom;
                for(f in that.displayFractionsFor) {
                    var t = that.displayFractionsFor[f].split("/");
                    var tv = parseInt(t[0])/parseInt(t[1]);
                    if (tv >= v) { 
                        frac =  that.displayFractionsFor[f];
                        break;
                    }
                }
            }
            
            if(that.displayFractionsFor && (that.displayFractionsFor.indexOf(frac) === -1)) { return "" + new Qty(inches + " in").toPrec(precision).scalar + (includeUnits ? " in" : ""); }   // skip if not in fraction display list
            
            if (i) {
                if (num < 0) {
                    num *= -1;
                }
                return "" + i + " " + frac + (includeUnits ? " in" : "");
            }

            return "" + frac + (includeUnits ? " in" : "");
        };
        // --------------------------------------------------------------------------------
        that.isEnglish = function(units) {
            var u;
            if (u = that.unitTable[units]) {
                if (['in', 'ft','miles'].indexOf(u)) { return true; }
            }
            return false;
        };
        // --------------------------------------------------------------------------------
        that.getPrecisionForUnit = function(units) {
            if (u = that.unitTable[units]) {
                var v = null;
                switch(u) {
                    case 'kilometer':
                        v = that.kilometerDecimalPrecision;
                        break;
                    case 'm':
                        v = that.meterDecimalPrecision;
                        break;
                    case 'cm':
                        v = that.centimeterDecimalPrecision;
                        break;
                    case 'mm':
                        v = that.millimeterDecimalPrecision;
                        break;
                    case 'in':
                        v = that.inchDecimalPrecision;
                        break;
                    case 'ft':
                        v = that.feetDecimalPrecision;
                        break;
                    case 'miles':
                        v = that.mileDecimalPrecision;
                        break;
                }
                if (v !== null) {
                    return 1/Math.pow(10, v);
                }
            }
            return null;
        }
        // --------------------------------------------------------------------------------
        that.getLeastDenominator = function() {
           return that.displayFractionsFor.reduce(function(acc, v) { var tmp = v.split('/'); return (parseInt(tmp[1]) > acc) ? parseInt(tmp[1]) : acc}, 0);
        }
        // --------------------------------------------------------------------------------
        that.parseTagOpts = function(tag) {
            var s = tag.split(/[%&]+/);
            s.shift();
            
            var opts = {'_fields': []};
            for(var i in s) {
                var p = s[i].split(/=/);
                
                if (p[0] == 'delimiter') { p[1] = p[1].replace(/_/g, " "); }
                
                opts[p[0]] = p[1].split(/;/);   
                opts['_fields'].push(p[0]);
            }
            return opts;
        };
        // --------------------------------------------------------------------------------
        return that;
    };

    caDisplayTemplateParser = caUI.initDisplayTemplateParser();
})(jQuery);
