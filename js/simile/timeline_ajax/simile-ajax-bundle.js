/*==================================================
 *  Platform Utility Functions and Constants
 *==================================================
 */

/*  This must be called after our jQuery has been loaded 
    but before control returns to user-code.
*/


/*==================================================
 *  REMEMBER to update the Version!
 *==================================================
 */
SimileAjax.version = 'pre 2.3.0';

//SimileAjax.jQuery = jQuery.noConflict(true);
if (typeof window["$"] == "undefined") {
    window.$ = SimileAjax.jQuery;
}

SimileAjax.Platform.os = {
    isMac:   false,
    isWin:   false,
    isWin32: false,
    isUnix:  false
};
SimileAjax.Platform.browser = {
    isIE:           false,
    isNetscape:     false,
    isMozilla:      false,
    isFirefox:      false,
    isOpera:        false,
    isSafari:       false,

    majorVersion:   0,
    minorVersion:   0
};

(function() {
    var an = navigator.appName.toLowerCase();
	var ua = navigator.userAgent.toLowerCase(); 
    
    /*
     *  Operating system
     */
	SimileAjax.Platform.os.isMac = (ua.indexOf('mac') != -1);
	SimileAjax.Platform.os.isWin = (ua.indexOf('win') != -1);
	SimileAjax.Platform.os.isWin32 = SimileAjax.Platform.isWin && (   
        ua.indexOf('95') != -1 || 
        ua.indexOf('98') != -1 || 
        ua.indexOf('nt') != -1 || 
        ua.indexOf('win32') != -1 || 
        ua.indexOf('32bit') != -1
    );
	SimileAjax.Platform.os.isUnix = (ua.indexOf('x11') != -1);
    
    /*
     *  Browser
     */
    SimileAjax.Platform.browser.isIE = (an.indexOf("microsoft") != -1);
    SimileAjax.Platform.browser.isNetscape = (an.indexOf("netscape") != -1);
    SimileAjax.Platform.browser.isMozilla = (ua.indexOf("mozilla") != -1);
    SimileAjax.Platform.browser.isFirefox = (ua.indexOf("firefox") != -1);
    SimileAjax.Platform.browser.isOpera = (an.indexOf("opera") != -1);
    SimileAjax.Platform.browser.isSafari = (an.indexOf("safari") != -1);
    
    var parseVersionString = function(s) {
        var a = s.split(".");
        SimileAjax.Platform.browser.majorVersion = parseInt(a[0]);
        SimileAjax.Platform.browser.minorVersion = parseInt(a[1]);
    };
    var indexOf = function(s, sub, start) {
        var i = s.indexOf(sub, start);
        return i >= 0 ? i : s.length;
    };
    
    if (SimileAjax.Platform.browser.isMozilla) {
        var offset = ua.indexOf("mozilla/");
        if (offset >= 0) {
            parseVersionString(ua.substring(offset + 8, indexOf(ua, " ", offset)));
        }
    }
    if (SimileAjax.Platform.browser.isIE) {
        var offset = ua.indexOf("msie ");
        if (offset >= 0) {
            parseVersionString(ua.substring(offset + 5, indexOf(ua, ";", offset)));
        }
    }
    if (SimileAjax.Platform.browser.isNetscape) {
        var offset = ua.indexOf("rv:");
        if (offset >= 0) {
            parseVersionString(ua.substring(offset + 3, indexOf(ua, ")", offset)));
        }
    }
    if (SimileAjax.Platform.browser.isFirefox) {
        var offset = ua.indexOf("firefox/");
        if (offset >= 0) {
            parseVersionString(ua.substring(offset + 8, indexOf(ua, " ", offset)));
        }
    }
    
    if (!("localeCompare" in String.prototype)) {
        String.prototype.localeCompare = function (s) {
            if (this < s) return -1;
            else if (this > s) return 1;
            else return 0;
        };
    }
})();

SimileAjax.Platform.getDefaultLocale = function() {
    return SimileAjax.Platform.clientLocale;
};


/*==================================================
 *  General, miscellaneous SimileAjax stuff
 *==================================================
 */

SimileAjax.ListenerQueue = function(wildcardHandlerName) {
    this._listeners = [];
    this._wildcardHandlerName = wildcardHandlerName;
};

SimileAjax.ListenerQueue.prototype.add = function(listener) {
    this._listeners.push(listener);
};

SimileAjax.ListenerQueue.prototype.remove = function(listener) {
    var listeners = this._listeners;
    for (var i = 0; i < listeners.length; i++) {
        if (listeners[i] == listener) {
            listeners.splice(i, 1);
            break;
        }
    }
};

SimileAjax.ListenerQueue.prototype.fire = function(handlerName, args) {
    var listeners = [].concat(this._listeners);
    for (var i = 0; i < listeners.length; i++) {
        var listener = listeners[i];
        if (handlerName in listener) {
            try {
                listener[handlerName].apply(listener, args);
            } catch (e) {
                SimileAjax.Debug.exception("Error firing event of name " + handlerName, e);
            }
        } else if (this._wildcardHandlerName != null &&
            this._wildcardHandlerName in listener) {
            try {
                listener[this._wildcardHandlerName].apply(listener, [ handlerName ]);
            } catch (e) {
                SimileAjax.Debug.exception("Error firing event of name " + handlerName + " to wildcard handler", e);
            }
        }
    }
};



/**
 * A basic set (in the mathematical sense) data structure
 *
 * @constructor
 * @param {Array or SimileAjax.Set} [a] an initial collection
 */
SimileAjax.Set = function(a) {
    this._hash = {};
    this._count = 0;
    
    if (a instanceof Array) {
        for (var i = 0; i < a.length; i++) {
            this.add(a[i]);
        }
    } else if (a instanceof SimileAjax.Set) {
        this.addSet(a);
    }
}

/**
 * Adds the given object to this set, assuming there it does not already exist
 *
 * @param {Object} o the object to add
 * @return {Boolean} true if the object was added, false if not
 */
SimileAjax.Set.prototype.add = function(o) {
    if (!(o in this._hash)) {
        this._hash[o] = true;
        this._count++;
        return true;
    }
    return false;
}

/**
 * Adds each element in the given set to this set
 *
 * @param {SimileAjax.Set} set the set of elements to add
 */
SimileAjax.Set.prototype.addSet = function(set) {
    for (var o in set._hash) {
        this.add(o);
    }
}

/**
 * Removes the given element from this set
 *
 * @param {Object} o the object to remove
 * @return {Boolean} true if the object was successfully removed,
 *   false otherwise
 */
SimileAjax.Set.prototype.remove = function(o) {
    if (o in this._hash) {
        delete this._hash[o];
        this._count--;
        return true;
    }
    return false;
}

/**
 * Removes the elements in this set that correspond to the elements in the
 * given set
 *
 * @param {SimileAjax.Set} set the set of elements to remove
 */
SimileAjax.Set.prototype.removeSet = function(set) {
    for (var o in set._hash) {
        this.remove(o);
    }
}

/**
 * Removes all elements in this set that are not present in the given set, i.e.
 * modifies this set to the intersection of the two sets
 *
 * @param {SimileAjax.Set} set the set to intersect
 */
SimileAjax.Set.prototype.retainSet = function(set) {
    for (var o in this._hash) {
        if (!set.contains(o)) {
            delete this._hash[o];
            this._count--;
        }
    }
}

/**
 * Returns whether or not the given element exists in this set
 *
 * @param {SimileAjax.Set} o the object to test for
 * @return {Boolean} true if the object is present, false otherwise
 */
SimileAjax.Set.prototype.contains = function(o) {
    return (o in this._hash);
}

/**
 * Returns the number of elements in this set
 *
 * @return {Number} the number of elements in this set
 */
SimileAjax.Set.prototype.size = function() {
    return this._count;
}

/**
 * Returns the elements of this set as an array
 *
 * @return {Array} a new array containing the elements of this set
 */
SimileAjax.Set.prototype.toArray = function() {
    var a = [];
    for (var o in this._hash) {
        a.push(o);
    }
    return a;
}

/**
 * Iterates through the elements of this set, order unspecified, executing the
 * given function on each element until the function returns true
 *
 * @param {Function} f a function of form f(element)
 */
SimileAjax.Set.prototype.visit = function(f) {
    for (var o in this._hash) {
        if (f(o) == true) {
            break;
        }
    }
}

/**
 * A sorted array data structure
 *
 * @constructor
 */
SimileAjax.SortedArray = function(compare, initialArray) {
    this._a = (initialArray instanceof Array) ? initialArray : [];
    this._compare = compare;
};

SimileAjax.SortedArray.prototype.add = function(elmt) {
    var sa = this;
    var index = this.find(function(elmt2) {
        return sa._compare(elmt2, elmt);
    });
    
    if (index < this._a.length) {
        this._a.splice(index, 0, elmt);
    } else {
        this._a.push(elmt);
    }
};

SimileAjax.SortedArray.prototype.remove = function(elmt) {
    var sa = this;
    var index = this.find(function(elmt2) {
        return sa._compare(elmt2, elmt);
    });
    
    while (index < this._a.length && this._compare(this._a[index], elmt) == 0) {
        if (this._a[index] == elmt) {
            this._a.splice(index, 1);
            return true;
        } else {
            index++;
        }
    }
    return false;
};

SimileAjax.SortedArray.prototype.removeAll = function() {
    this._a = [];
};

SimileAjax.SortedArray.prototype.elementAt = function(index) {
    return this._a[index];
};

SimileAjax.SortedArray.prototype.length = function() {
    return this._a.length;
};

SimileAjax.SortedArray.prototype.find = function(compare) {
    var a = 0;
    var b = this._a.length;
    
    while (a < b) {
        var mid = Math.floor((a + b) / 2);
        var c = compare(this._a[mid]);
        if (mid == a) {
            return c < 0 ? a+1 : a;
        } else if (c < 0) {
            a = mid;
        } else {
            b = mid;
        }
    }
    return a;
};

SimileAjax.SortedArray.prototype.getFirst = function() {
    return (this._a.length > 0) ? this._a[0] : null;
};

SimileAjax.SortedArray.prototype.getLast = function() {
    return (this._a.length > 0) ? this._a[this._a.length - 1] : null;
};

/*==================================================
 *  Event Index
 *==================================================
 */

SimileAjax.EventIndex = function(unit) {
    var eventIndex = this;
    
    this._unit = (unit != null) ? unit : SimileAjax.NativeDateUnit;
    this._events = new SimileAjax.SortedArray(
        function(event1, event2) {
            return eventIndex._unit.compare(event1.getStart(), event2.getStart());
        }
    );
    this._idToEvent = {};
    this._indexed = true;
};

SimileAjax.EventIndex.prototype.getUnit = function() {
    return this._unit;
};

SimileAjax.EventIndex.prototype.getEvent = function(id) {
    return this._idToEvent[id];
};

SimileAjax.EventIndex.prototype.add = function(evt) {
    this._events.add(evt);
    this._idToEvent[evt.getID()] = evt;
    this._indexed = false;
};

SimileAjax.EventIndex.prototype.removeAll = function() {
    this._events.removeAll();
    this._idToEvent = {};
    this._indexed = false;
};

SimileAjax.EventIndex.prototype.getCount = function() {
    return this._events.length();
};

SimileAjax.EventIndex.prototype.getIterator = function(startDate, endDate) {
    if (!this._indexed) {
        this._index();
    }
    return new SimileAjax.EventIndex._Iterator(this._events, startDate, endDate, this._unit);
};

SimileAjax.EventIndex.prototype.getReverseIterator = function(startDate, endDate) {
    if (!this._indexed) {
        this._index();
    }
    return new SimileAjax.EventIndex._ReverseIterator(this._events, startDate, endDate, this._unit);
};

SimileAjax.EventIndex.prototype.getAllIterator = function() {
    return new SimileAjax.EventIndex._AllIterator(this._events);
};

SimileAjax.EventIndex.prototype.getEarliestDate = function() {
    var evt = this._events.getFirst();
    return (evt == null) ? null : evt.getStart();
};

SimileAjax.EventIndex.prototype.getLatestDate = function() {
    var evt = this._events.getLast();
    if (evt == null) {
        return null;
    }
    
    if (!this._indexed) {
        this._index();
    }
    
    var index = evt._earliestOverlapIndex;
    var date = this._events.elementAt(index).getEnd();
    for (var i = index + 1; i < this._events.length(); i++) {
        date = this._unit.later(date, this._events.elementAt(i).getEnd());
    }
    
    return date;
};

SimileAjax.EventIndex.prototype._index = function() {
    /*
     *  For each event, we want to find the earliest preceding
     *  event that overlaps with it, if any.
     */
    
    var l = this._events.length();
    for (var i = 0; i < l; i++) {
        var evt = this._events.elementAt(i);
        evt._earliestOverlapIndex = i;
    }
    
    var toIndex = 1;
    for (var i = 0; i < l; i++) {
        var evt = this._events.elementAt(i);
        var end = evt.getEnd();
        
        toIndex = Math.max(toIndex, i + 1);
        while (toIndex < l) {
            var evt2 = this._events.elementAt(toIndex);
            var start2 = evt2.getStart();
            
            if (this._unit.compare(start2, end) < 0) {
                evt2._earliestOverlapIndex = i;
                toIndex++;
            } else {
                break;
            }
        }
    }
    this._indexed = true;
};

SimileAjax.EventIndex._Iterator = function(events, startDate, endDate, unit) {
    this._events = events;
    this._startDate = startDate;
    this._endDate = endDate;
    this._unit = unit;
    
    this._currentIndex = events.find(function(evt) {
        return unit.compare(evt.getStart(), startDate);
    });
    if (this._currentIndex - 1 >= 0) {
        this._currentIndex = this._events.elementAt(this._currentIndex - 1)._earliestOverlapIndex;
    }
    this._currentIndex--;
    
    this._maxIndex = events.find(function(evt) {
        return unit.compare(evt.getStart(), endDate);
    });
    
    this._hasNext = false;
    this._next = null;
    this._findNext();
};

SimileAjax.EventIndex._Iterator.prototype = {
    hasNext: function() { return this._hasNext; },
    next: function() {
        if (this._hasNext) {
            var next = this._next;
            this._findNext();
            
            return next;
        } else {
            return null;
        }
    },
    _findNext: function() {
        var unit = this._unit;
        while ((++this._currentIndex) < this._maxIndex) {
            var evt = this._events.elementAt(this._currentIndex);
            if (unit.compare(evt.getStart(), this._endDate) < 0 &&
                unit.compare(evt.getEnd(), this._startDate) > 0) {
                
                this._next = evt;
                this._hasNext = true;
                return;
            }
        }
        this._next = null;
        this._hasNext = false;
    }
};

SimileAjax.EventIndex._ReverseIterator = function(events, startDate, endDate, unit) {
    this._events = events;
    this._startDate = startDate;
    this._endDate = endDate;
    this._unit = unit;
    
    this._minIndex = events.find(function(evt) {
        return unit.compare(evt.getStart(), startDate);
    });
    if (this._minIndex - 1 >= 0) {
        this._minIndex = this._events.elementAt(this._minIndex - 1)._earliestOverlapIndex;
    }
    
    this._maxIndex = events.find(function(evt) {
        return unit.compare(evt.getStart(), endDate);
    });
    
    this._currentIndex = this._maxIndex;
    this._hasNext = false;
    this._next = null;
    this._findNext();
};

SimileAjax.EventIndex._ReverseIterator.prototype = {
    hasNext: function() { return this._hasNext; },
    next: function() {
        if (this._hasNext) {
            var next = this._next;
            this._findNext();
            
            return next;
        } else {
            return null;
        }
    },
    _findNext: function() {
        var unit = this._unit;
        while ((--this._currentIndex) >= this._minIndex) {
            var evt = this._events.elementAt(this._currentIndex);
            if (unit.compare(evt.getStart(), this._endDate) < 0 &&
                unit.compare(evt.getEnd(), this._startDate) > 0) {
                
                this._next = evt;
                this._hasNext = true;
                return;
            }
        }
        this._next = null;
        this._hasNext = false;
    }
};

SimileAjax.EventIndex._AllIterator = function(events) {
    this._events = events;
    this._index = 0;
};

SimileAjax.EventIndex._AllIterator.prototype = {
    hasNext: function() {
        return this._index < this._events.length();
    },
    next: function() {
        return this._index < this._events.length() ?
            this._events.elementAt(this._index++) : null;
    }
};

/**
 * @fileOverview A collection of date/time utility functions
 * @name SimileAjax.DateTime
 */

SimileAjax.DateTime = new Object();

SimileAjax.DateTime.MILLISECOND    = 0;
SimileAjax.DateTime.SECOND         = 1;
SimileAjax.DateTime.MINUTE         = 2;
SimileAjax.DateTime.HOUR           = 3;
SimileAjax.DateTime.DAY            = 4;
SimileAjax.DateTime.WEEK           = 5;
SimileAjax.DateTime.MONTH          = 6;
SimileAjax.DateTime.YEAR           = 7;
SimileAjax.DateTime.DECADE         = 8;
SimileAjax.DateTime.CENTURY        = 9;
SimileAjax.DateTime.MILLENNIUM     = 10;

SimileAjax.DateTime.EPOCH          = -1;
SimileAjax.DateTime.ERA            = -2;

/**
 * An array of unit lengths, expressed in milliseconds, of various lengths of
 * time.  The array indices are predefined and stored as properties of the
 * SimileAjax.DateTime object, e.g. SimileAjax.DateTime.YEAR.
 * @type Array
 */
SimileAjax.DateTime.gregorianUnitLengths = [];
    (function() {
        var d = SimileAjax.DateTime;
        var a = d.gregorianUnitLengths;
        
        a[d.MILLISECOND] = 1;
        a[d.SECOND]      = 1000;
        a[d.MINUTE]      = a[d.SECOND] * 60;
        a[d.HOUR]        = a[d.MINUTE] * 60;
        a[d.DAY]         = a[d.HOUR] * 24;
        a[d.WEEK]        = a[d.DAY] * 7;
        a[d.MONTH]       = a[d.DAY] * 31;
        a[d.YEAR]        = a[d.DAY] * 365;
        a[d.DECADE]      = a[d.YEAR] * 10;
        a[d.CENTURY]     = a[d.YEAR] * 100;
        a[d.MILLENNIUM]  = a[d.YEAR] * 1000;
    })();
    
SimileAjax.DateTime._dateRegexp = new RegExp(
    "^(-?)([0-9]{4})(" + [
        "(-?([0-9]{2})(-?([0-9]{2}))?)", // -month-dayOfMonth
        "(-?([0-9]{3}))",                // -dayOfYear
        "(-?W([0-9]{2})(-?([1-7]))?)"    // -Wweek-dayOfWeek
    ].join("|") + ")?$"
);
SimileAjax.DateTime._timezoneRegexp = new RegExp(
    "Z|(([-+])([0-9]{2})(:?([0-9]{2}))?)$"
);
SimileAjax.DateTime._timeRegexp = new RegExp(
    "^([0-9]{2})(:?([0-9]{2})(:?([0-9]{2})(\.([0-9]+))?)?)?$"
);

/**
 * Takes a date object and a string containing an ISO 8601 date and sets the
 * the date using information parsed from the string.  Note that this method
 * does not parse any time information.
 *
 * @param {Date} dateObject the date object to modify
 * @param {String} string an ISO 8601 string to parse
 * @return {Date} the modified date object
 */
SimileAjax.DateTime.setIso8601Date = function(dateObject, string) {
    /*
     *  This function has been adapted from dojo.date, v.0.3.0
     *  http://dojotoolkit.org/.
     */
     
    var d = string.match(SimileAjax.DateTime._dateRegexp);
    if(!d) {
        throw new Error("Invalid date string: " + string);
    }
    
    var sign = (d[1] == "-") ? -1 : 1; // BC or AD
    var year = sign * d[2];
    var month = d[5];
    var date = d[7];
    var dayofyear = d[9];
    var week = d[11];
    var dayofweek = (d[13]) ? d[13] : 1;

    dateObject.setUTCFullYear(year);
    if (dayofyear) { 
        dateObject.setUTCMonth(0);
        dateObject.setUTCDate(Number(dayofyear));
    } else if (week) {
        dateObject.setUTCMonth(0);
        dateObject.setUTCDate(1);
        var gd = dateObject.getUTCDay();
        var day =  (gd) ? gd : 7;
        var offset = Number(dayofweek) + (7 * Number(week));
        
        if (day <= 4) { 
            dateObject.setUTCDate(offset + 1 - day); 
        } else { 
            dateObject.setUTCDate(offset + 8 - day); 
        }
    } else {
        if (month) { 
            dateObject.setUTCDate(1);
            dateObject.setUTCMonth(month - 1); 
        }
        if (date) { 
            dateObject.setUTCDate(date); 
        }
    }
    
    return dateObject;
};

/**
 * Takes a date object and a string containing an ISO 8601 time and sets the
 * the time using information parsed from the string.  Note that this method
 * does not parse any date information.
 *
 * @param {Date} dateObject the date object to modify
 * @param {String} string an ISO 8601 string to parse
 * @return {Date} the modified date object
 */
SimileAjax.DateTime.setIso8601Time = function (dateObject, string) {
    /*
     *  This function has been adapted from dojo.date, v.0.3.0
     *  http://dojotoolkit.org/.
     */
    
    var d = string.match(SimileAjax.DateTime._timeRegexp);
    if(!d) {
        SimileAjax.Debug.warn("Invalid time string: " + string);
        return false;
    }
    var hours = d[1];
    var mins = Number((d[3]) ? d[3] : 0);
    var secs = (d[5]) ? d[5] : 0;
    var ms = d[7] ? (Number("0." + d[7]) * 1000) : 0;

    dateObject.setUTCHours(hours);
    dateObject.setUTCMinutes(mins);
    dateObject.setUTCSeconds(secs);
    dateObject.setUTCMilliseconds(ms);
    
    return dateObject;
};

/**
 * The timezone offset in minutes in the user's browser.
 * @type Number
 */
SimileAjax.DateTime.timezoneOffset = new Date().getTimezoneOffset();

/**
 * Takes a date object and a string containing an ISO 8601 date and time and 
 * sets the date object using information parsed from the string.
 *
 * @param {Date} dateObject the date object to modify
 * @param {String} string an ISO 8601 string to parse
 * @return {Date} the modified date object
 */
SimileAjax.DateTime.setIso8601 = function (dateObject, string){
    /*
     *  This function has been adapted from dojo.date, v.0.3.0
     *  http://dojotoolkit.org/.
     */
     
    var offset = null;
    var comps = (string.indexOf("T") == -1) ? string.split(" ") : string.split("T");
    
    SimileAjax.DateTime.setIso8601Date(dateObject, comps[0]);
    if (comps.length == 2) { 
        // first strip timezone info from the end
        var d = comps[1].match(SimileAjax.DateTime._timezoneRegexp);
        if (d) {
            if (d[0] == 'Z') {
                offset = 0;
            } else {
                offset = (Number(d[3]) * 60) + Number(d[5]);
                offset *= ((d[2] == '-') ? 1 : -1);
            }
            comps[1] = comps[1].substr(0, comps[1].length - d[0].length);
        }

        SimileAjax.DateTime.setIso8601Time(dateObject, comps[1]); 
    }
    if (offset == null) {
        offset = dateObject.getTimezoneOffset(); // local time zone if no tz info
    }
    dateObject.setTime(dateObject.getTime() + offset * 60000);
    
    return dateObject;
};

/**
 * Takes a string containing an ISO 8601 date and returns a newly instantiated
 * date object with the parsed date and time information from the string.
 *
 * @param {String} string an ISO 8601 string to parse
 * @return {Date} a new date object created from the string
 */
SimileAjax.DateTime.parseIso8601DateTime = function (string) {
    try {
        return SimileAjax.DateTime.setIso8601(new Date(0), string);
    } catch (e) {
        return null;
    }
};

/**
 * Takes a string containing a Gregorian date and time and returns a newly
 * instantiated date object with the parsed date and time information from the
 * string.  If the param is actually an instance of Date instead of a string, 
 * simply returns the given date instead.
 *
 * @param {Object} o an object, to either return or parse as a string
 * @return {Date} the date object
 */
SimileAjax.DateTime.parseGregorianDateTime = function(o) {
    if (o == null) {
        return null;
    } else if (o instanceof Date) {
        return o;
    }
    
    var s = o.toString();
    if (s.length > 0 && s.length < 8) {
        var space = s.indexOf(" ");
        if (space > 0) {
            var year = parseInt(s.substr(0, space));
            var suffix = s.substr(space + 1);
            if (suffix.toLowerCase() == "bc") {
                year = 1 - year;
            }
        } else {
            var year = parseInt(s);
        }
            
        var d = new Date(0);
        d.setUTCFullYear(year);
        
        return d;
    }
    
    try {
        return new Date(Date.parse(s));
    } catch (e) {
        return null;
    }
};

/**
 * Rounds date objects down to the nearest interval or multiple of an interval.
 * This method modifies the given date object, converting it to the given
 * timezone if specified.
 * 
 * @param {Date} date the date object to round
 * @param {Number} intervalUnit a constant, integer index specifying an 
 *   interval, e.g. SimileAjax.DateTime.HOUR
 * @param {Number} timeZone a timezone shift, given in hours
 * @param {Number} multiple a multiple of the interval to round by
 * @param {Number} firstDayOfWeek an integer specifying the first day of the
 *   week, 0 corresponds to Sunday, 1 to Monday, etc.
 */
SimileAjax.DateTime.roundDownToInterval = function(date, intervalUnit, timeZone, multiple, firstDayOfWeek) {
    var timeShift = timeZone * 
        SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.HOUR];
        
    var date2 = new Date(date.getTime() + timeShift);
    var clearInDay = function(d) {
        d.setUTCMilliseconds(0);
        d.setUTCSeconds(0);
        d.setUTCMinutes(0);
        d.setUTCHours(0);
    };
    var clearInYear = function(d) {
        clearInDay(d);
        d.setUTCDate(1);
        d.setUTCMonth(0);
    };
    
    switch(intervalUnit) {
    case SimileAjax.DateTime.MILLISECOND:
        var x = date2.getUTCMilliseconds();
        date2.setUTCMilliseconds(x - (x % multiple));
        break;
    case SimileAjax.DateTime.SECOND:
        date2.setUTCMilliseconds(0);
        
        var x = date2.getUTCSeconds();
        date2.setUTCSeconds(x - (x % multiple));
        break;
    case SimileAjax.DateTime.MINUTE:
        date2.setUTCMilliseconds(0);
        date2.setUTCSeconds(0);
        
        var x = date2.getUTCMinutes();
        date2.setTime(date2.getTime() - 
            (x % multiple) * SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.MINUTE]);
        break;
    case SimileAjax.DateTime.HOUR:
        date2.setUTCMilliseconds(0);
        date2.setUTCSeconds(0);
        date2.setUTCMinutes(0);
        
        var x = date2.getUTCHours();
        date2.setUTCHours(x - (x % multiple));
        break;
    case SimileAjax.DateTime.DAY:
        clearInDay(date2);
        break;
    case SimileAjax.DateTime.WEEK:
        clearInDay(date2);
        var d = (date2.getUTCDay() + 7 - firstDayOfWeek) % 7;
        date2.setTime(date2.getTime() - 
            d * SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.DAY]);
        break;
    case SimileAjax.DateTime.MONTH:
        clearInDay(date2);
        date2.setUTCDate(1);
        
        var x = date2.getUTCMonth();
        date2.setUTCMonth(x - (x % multiple));
        break;
    case SimileAjax.DateTime.YEAR:
        clearInYear(date2);
        
        var x = date2.getUTCFullYear();
        date2.setUTCFullYear(x - (x % multiple));
        break;
    case SimileAjax.DateTime.DECADE:
        clearInYear(date2);
        date2.setUTCFullYear(Math.floor(date2.getUTCFullYear() / 10) * 10);
        break;
    case SimileAjax.DateTime.CENTURY:
        clearInYear(date2);
        date2.setUTCFullYear(Math.floor(date2.getUTCFullYear() / 100) * 100);
        break;
    case SimileAjax.DateTime.MILLENNIUM:
        clearInYear(date2);
        date2.setUTCFullYear(Math.floor(date2.getUTCFullYear() / 1000) * 1000);
        break;
    }
    
    date.setTime(date2.getTime() - timeShift);
};

/**
 * Rounds date objects up to the nearest interval or multiple of an interval.
 * This method modifies the given date object, converting it to the given
 * timezone if specified.
 * 
 * @param {Date} date the date object to round
 * @param {Number} intervalUnit a constant, integer index specifying an 
 *   interval, e.g. SimileAjax.DateTime.HOUR
 * @param {Number} timeZone a timezone shift, given in hours
 * @param {Number} multiple a multiple of the interval to round by
 * @param {Number} firstDayOfWeek an integer specifying the first day of the
 *   week, 0 corresponds to Sunday, 1 to Monday, etc.
 * @see SimileAjax.DateTime.roundDownToInterval
 */
SimileAjax.DateTime.roundUpToInterval = function(date, intervalUnit, timeZone, multiple, firstDayOfWeek) {
    var originalTime = date.getTime();
    SimileAjax.DateTime.roundDownToInterval(date, intervalUnit, timeZone, multiple, firstDayOfWeek);
    if (date.getTime() < originalTime) {
        date.setTime(date.getTime() + 
            SimileAjax.DateTime.gregorianUnitLengths[intervalUnit] * multiple);
    }
};

/**
 * Increments a date object by a specified interval, taking into
 * consideration the timezone.
 *
 * @param {Date} date the date object to increment
 * @param {Number} intervalUnit a constant, integer index specifying an 
 *   interval, e.g. SimileAjax.DateTime.HOUR
 * @param {Number} timeZone the timezone offset in hours
 */
SimileAjax.DateTime.incrementByInterval = function(date, intervalUnit, timeZone) {
    timeZone = (typeof timeZone == 'undefined') ? 0 : timeZone;

    var timeShift = timeZone * 
        SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.HOUR];
        
    var date2 = new Date(date.getTime() + timeShift);

    switch(intervalUnit) {
    case SimileAjax.DateTime.MILLISECOND:
        date2.setTime(date2.getTime() + 1)
        break;
    case SimileAjax.DateTime.SECOND:
        date2.setTime(date2.getTime() + 1000);
        break;
    case SimileAjax.DateTime.MINUTE:
        date2.setTime(date2.getTime() + 
            SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.MINUTE]);
        break;
    case SimileAjax.DateTime.HOUR:
        date2.setTime(date2.getTime() + 
            SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.HOUR]);
        break;
    case SimileAjax.DateTime.DAY:
        date2.setUTCDate(date2.getUTCDate() + 1);
        break;
    case SimileAjax.DateTime.WEEK:
        date2.setUTCDate(date2.getUTCDate() + 7);
        break;
    case SimileAjax.DateTime.MONTH:
        date2.setUTCMonth(date2.getUTCMonth() + 1);
        break;
    case SimileAjax.DateTime.YEAR:
        date2.setUTCFullYear(date2.getUTCFullYear() + 1);
        break;
    case SimileAjax.DateTime.DECADE:
        date2.setUTCFullYear(date2.getUTCFullYear() + 10);
        break;
    case SimileAjax.DateTime.CENTURY:
        date2.setUTCFullYear(date2.getUTCFullYear() + 100);
        break;
    case SimileAjax.DateTime.MILLENNIUM:
        date2.setUTCFullYear(date2.getUTCFullYear() + 1000);
        break;
    }

    date.setTime(date2.getTime() - timeShift);
};

/**
 * Returns a new date object with the given time offset removed.
 *
 * @param {Date} date the starting date
 * @param {Number} timeZone a timezone specified in an hour offset to remove
 * @return {Date} a new date object with the offset removed
 */
SimileAjax.DateTime.removeTimeZoneOffset = function(date, timeZone) {
    return new Date(date.getTime() + 
        timeZone * SimileAjax.DateTime.gregorianUnitLengths[SimileAjax.DateTime.HOUR]);
};

/**
 * Returns the timezone of the user's browser.
 *
 * @return {Number} the timezone in the user's locale in hours
 */
SimileAjax.DateTime.getTimezone = function() {
    var d = new Date().getTimezoneOffset();
    return d / -60;
};

/*==================================================
 *  Debug Utility Functions
 *==================================================
 */

SimileAjax.Debug = {
    silent: false
};

SimileAjax.Debug.log = function(msg) {
    var f;
    if ("console" in window && "log" in window.console) { // FireBug installed
        f = function(msg2) {
            console.log(msg2);
        }
    } else {
        f = function(msg2) {
            if (!SimileAjax.Debug.silent) {
                alert(msg2);
            }
        }
    }
    SimileAjax.Debug.log = f;
    f(msg);
};

SimileAjax.Debug.warn = function(msg) {
    var f;
    if ("console" in window && "warn" in window.console) { // FireBug installed
        f = function(msg2) {
            console.warn(msg2);
        }
    } else {
        f = function(msg2) {
            if (!SimileAjax.Debug.silent) {
                alert(msg2);
            }
        }
    }
    SimileAjax.Debug.warn = f;
    f(msg);
};

SimileAjax.Debug.exception = function(e, msg) {
    var f, params = SimileAjax.parseURLParameters();
    if (params.errors == "throw" || SimileAjax.params.errors == "throw") {
        f = function(e2, msg2) {
            throw(e2); // do not hide from browser's native debugging features
        };
    } else if ("console" in window && "error" in window.console) { // FireBug installed
        f = function(e2, msg2) {
            if (msg2 != null) {
                console.error(msg2 + " %o", e2);
            } else {
                console.error(e2);
            }
            throw(e2); // do not hide from browser's native debugging features
        };
    } else {
        f = function(e2, msg2) {
            if (!SimileAjax.Debug.silent) {
                alert("Caught exception: " + msg2 + "\n\nDetails: " + ("description" in e2 ? e2.description : e2));
            }
            throw(e2); // do not hide from browser's native debugging features
        };
    }
    SimileAjax.Debug.exception = f;
    f(e, msg);
};

SimileAjax.Debug.objectToString = function(o) {
    return SimileAjax.Debug._objectToString(o, "");
};

SimileAjax.Debug._objectToString = function(o, indent) {
    var indent2 = indent + " ";
    if (typeof o == "object") {
        var s = "{";
        for (n in o) {
            s += indent2 + n + ": " + SimileAjax.Debug._objectToString(o[n], indent2) + "\n";
        }
        s += indent + "}";
        return s;
    } else if (typeof o == "array") {
        var s = "[";
        for (var n = 0; n < o.length; n++) {
            s += SimileAjax.Debug._objectToString(o[n], indent2) + "\n";
        }
        s += indent + "]";
        return s;
    } else {
        return o;
    }
};

/*==================================================
 *  DOM Utility Functions
 *==================================================
 */

SimileAjax.DOM = new Object();

SimileAjax.DOM.registerEventWithObject = function(elmt, eventName, obj, handlerName) {
    SimileAjax.DOM.registerEvent(elmt, eventName, function(elmt2, evt, target) {
        return obj[handlerName].call(obj, elmt2, evt, target);
    });
};

SimileAjax.DOM.registerEvent = function(elmt, eventName, handler) {
    var handler2 = function(evt) {
        evt = (evt) ? evt : ((event) ? event : null);
        if (evt) {
            var target = (evt.target) ? 
                evt.target : ((evt.srcElement) ? evt.srcElement : null);
            if (target) {
                target = (target.nodeType == 1 || target.nodeType == 9) ? 
                    target : target.parentNode;
            }
            
            return handler(elmt, evt, target);
        }
        return true;
    }
    
    if (SimileAjax.Platform.browser.isIE) {
        elmt.attachEvent("on" + eventName, handler2);
    } else {
        elmt.addEventListener(eventName, handler2, false);
    }
};

SimileAjax.DOM.getPageCoordinates = function(elmt) {
    var left = 0;
    var top = 0;
    
    if (elmt.nodeType != 1) {
        elmt = elmt.parentNode;
    }
    
    var elmt2 = elmt;
    while (elmt2 != null) {
        left += elmt2.offsetLeft;
        top += elmt2.offsetTop;
        elmt2 = elmt2.offsetParent;
    }
    
    var body = document.body;
    while (elmt != null && elmt != body) {
        if ("scrollLeft" in elmt) {
            left -= elmt.scrollLeft;
            top -= elmt.scrollTop;
        }
        elmt = elmt.parentNode;
    }
    
    return { left: left, top: top };
};

SimileAjax.DOM.getSize = function(elmt) {
	var w = this.getStyle(elmt,"width");
	var h = this.getStyle(elmt,"height");
	if (w.indexOf("px") > -1) w = w.replace("px","");
	if (h.indexOf("px") > -1) h = h.replace("px","");
	return {
		w: w,
		h: h
	}
}

SimileAjax.DOM.getStyle = function(elmt, styleProp) {
    if (elmt.currentStyle) { // IE
        var style = elmt.currentStyle[styleProp];
    } else if (window.getComputedStyle) { // standard DOM
        var style = document.defaultView.getComputedStyle(elmt, null).getPropertyValue(styleProp);
    } else {
    	var style = "";
    }
    return style;
}

SimileAjax.DOM.getEventRelativeCoordinates = function(evt, elmt) {
    if (SimileAjax.Platform.browser.isIE) {
      if (evt.type == "mousewheel") {
        var coords = SimileAjax.DOM.getPageCoordinates(elmt);
        return {
          x: evt.clientX - coords.left, 
          y: evt.clientY - coords.top
        };        
      } else {
        return {
          x: evt.offsetX,
          y: evt.offsetY
        };
      }
    } else {
        var coords = SimileAjax.DOM.getPageCoordinates(elmt);

        if ((evt.type == "DOMMouseScroll") &&
          SimileAjax.Platform.browser.isFirefox &&
          (SimileAjax.Platform.browser.majorVersion == 2)) {
          // Due to: https://bugzilla.mozilla.org/show_bug.cgi?id=352179                  

          return {
            x: evt.screenX - coords.left,
            y: evt.screenY - coords.top 
          };
        } else {
          return {
              x: evt.pageX - coords.left,
              y: evt.pageY - coords.top
          };
        }
    }
};

SimileAjax.DOM.getEventPageCoordinates = function(evt) {
    if (SimileAjax.Platform.browser.isIE) {
        return {
            x: evt.clientX + document.body.scrollLeft,
            y: evt.clientY + document.body.scrollTop
        };
    } else {
        return {
            x: evt.pageX,
            y: evt.pageY
        };
    }
};

SimileAjax.DOM.hittest = function(x, y, except) {
    return SimileAjax.DOM._hittest(document.body, x, y, except);
};

SimileAjax.DOM._hittest = function(elmt, x, y, except) {
    var childNodes = elmt.childNodes;
    outer: for (var i = 0; i < childNodes.length; i++) {
        var childNode = childNodes[i];
        for (var j = 0; j < except.length; j++) {
            if (childNode == except[j]) {
                continue outer;
            }
        }
        
        if (childNode.offsetWidth == 0 && childNode.offsetHeight == 0) {
            /*
             *  Sometimes SPAN elements have zero width and height but
             *  they have children like DIVs that cover non-zero areas.
             */
            var hitNode = SimileAjax.DOM._hittest(childNode, x, y, except);
            if (hitNode != childNode) {
                return hitNode;
            }
        } else {
            var top = 0;
            var left = 0;
            
            var node = childNode;
            while (node) {
                top += node.offsetTop;
                left += node.offsetLeft;
                node = node.offsetParent;
            }
            
            if (left <= x && top <= y && (x - left) < childNode.offsetWidth && (y - top) < childNode.offsetHeight) {
                return SimileAjax.DOM._hittest(childNode, x, y, except);
            } else if (childNode.nodeType == 1 && childNode.tagName == "TR") {
                /*
                 *  Table row might have cells that span several rows.
                 */
                var childNode2 = SimileAjax.DOM._hittest(childNode, x, y, except);
                if (childNode2 != childNode) {
                    return childNode2;
                }
            }
        }
    }
    return elmt;
};

SimileAjax.DOM.cancelEvent = function(evt) {
    evt.returnValue = false;
    evt.cancelBubble = true;
    if ("preventDefault" in evt) {
        evt.preventDefault();
    }
};

SimileAjax.DOM.appendClassName = function(elmt, className) {
    var classes = elmt.className.split(" ");
    for (var i = 0; i < classes.length; i++) {
        if (classes[i] == className) {
            return;
        }
    }
    classes.push(className);
    elmt.className = classes.join(" ");
};

SimileAjax.DOM.createInputElement = function(type) {
    var div = document.createElement("div");
    div.innerHTML = "<input type='" + type + "' />";
    
    return div.firstChild;
};

SimileAjax.DOM.createDOMFromTemplate = function(template) {
    var result = {};
    result.elmt = SimileAjax.DOM._createDOMFromTemplate(template, result, null);
    
    return result;
};

SimileAjax.DOM._createDOMFromTemplate = function(templateNode, result, parentElmt) {
    if (templateNode == null) {
        /*
        var node = doc.createTextNode("--null--");
        if (parentElmt != null) {
            parentElmt.appendChild(node);
        }
        return node;
        */
        return null;
    } else if (typeof templateNode != "object") {
        var node = document.createTextNode(templateNode);
        if (parentElmt != null) {
            parentElmt.appendChild(node);
        }
        return node;
    } else {
        var elmt = null;
        if ("tag" in templateNode) {
            var tag = templateNode.tag;
            if (parentElmt != null) {
                if (tag == "tr") {
                    elmt = parentElmt.insertRow(parentElmt.rows.length);
                } else if (tag == "td") {
                    elmt = parentElmt.insertCell(parentElmt.cells.length);
                }
            }
            if (elmt == null) {
                elmt = tag == "input" ?
                    SimileAjax.DOM.createInputElement(templateNode.type) :
                    document.createElement(tag);
                    
                if (parentElmt != null) {
                    parentElmt.appendChild(elmt);
                }
            }
        } else {
            elmt = templateNode.elmt;
            if (parentElmt != null) {
                parentElmt.appendChild(elmt);
            }
        }
        
        for (var attribute in templateNode) {
            var value = templateNode[attribute];
            
            if (attribute == "field") {
                result[value] = elmt;
                
            } else if (attribute == "className") {
                elmt.className = value;
            } else if (attribute == "id") {
                elmt.id = value;
            } else if (attribute == "title") {
                elmt.title = value;
            } else if (attribute == "type" && elmt.tagName == "input") {
                // do nothing
            } else if (attribute == "style") {
                for (n in value) {
                    var v = value[n];
                    if (n == "float") {
                        n = SimileAjax.Platform.browser.isIE ? "styleFloat" : "cssFloat";
                    }
                    elmt.style[n] = v;
                }
            } else if (attribute == "children") {
                for (var i = 0; i < value.length; i++) {
                    SimileAjax.DOM._createDOMFromTemplate(value[i], result, elmt);
                }
            } else if (attribute != "tag" && attribute != "elmt") {
                elmt.setAttribute(attribute, value);
            }
        }
        return elmt;
    }
}

SimileAjax.DOM._cachedParent = null;
SimileAjax.DOM.createElementFromString = function(s) {
    if (SimileAjax.DOM._cachedParent == null) {
        SimileAjax.DOM._cachedParent = document.createElement("div");
    }
    SimileAjax.DOM._cachedParent.innerHTML = s;
    return SimileAjax.DOM._cachedParent.firstChild;
};

SimileAjax.DOM.createDOMFromString = function(root, s, fieldElmts) {
    var elmt = typeof root == "string" ? document.createElement(root) : root;
    elmt.innerHTML = s;
    
    var dom = { elmt: elmt };
    SimileAjax.DOM._processDOMChildrenConstructedFromString(dom, elmt, fieldElmts != null ? fieldElmts : {} );
    
    return dom;
};

SimileAjax.DOM._processDOMConstructedFromString = function(dom, elmt, fieldElmts) {
    var id = elmt.id;
    if (id != null && id.length > 0) {
        elmt.removeAttribute("id");
        if (id in fieldElmts) {
            var parentElmt = elmt.parentNode;
            parentElmt.insertBefore(fieldElmts[id], elmt);
            parentElmt.removeChild(elmt);
            
            dom[id] = fieldElmts[id];
            return;
        } else {
            dom[id] = elmt;
        }
    }
    
    if (elmt.hasChildNodes()) {
        SimileAjax.DOM._processDOMChildrenConstructedFromString(dom, elmt, fieldElmts);
    }
};

SimileAjax.DOM._processDOMChildrenConstructedFromString = function(dom, elmt, fieldElmts) {
    var node = elmt.firstChild;
    while (node != null) {
        var node2 = node.nextSibling;
        if (node.nodeType == 1) {
            SimileAjax.DOM._processDOMConstructedFromString(dom, node, fieldElmts);
        }
        node = node2;
    }
};

/**
 * @fileOverview Graphics utility functions and constants
 * @name SimileAjax.Graphics
 */

SimileAjax.Graphics = new Object();

/**
 * A boolean value indicating whether PNG translucency is supported on the
 * user's browser or not.
 *
 * @type Boolean
 */
SimileAjax.Graphics.pngIsTranslucent = (!SimileAjax.Platform.browser.isIE) || (SimileAjax.Platform.browser.majorVersion > 6);
if (!SimileAjax.Graphics.pngIsTranslucent) {
    SimileAjax.includeCssFile(document, SimileAjax.urlPrefix + "styles/graphics-ie6.css");
}

/*==================================================
 *  Opacity, translucency
 *==================================================
 */
SimileAjax.Graphics._createTranslucentImage1 = function(url, verticalAlign) {
    var elmt = document.createElement("img");
    elmt.setAttribute("src", url);
    if (verticalAlign != null) {
        elmt.style.verticalAlign = verticalAlign;
    }
    return elmt;
};
SimileAjax.Graphics._createTranslucentImage2 = function(url, verticalAlign) {
    var elmt = document.createElement("img");
    elmt.style.width = "1px";  // just so that IE will calculate the size property
    elmt.style.height = "1px";
    elmt.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + url +"', sizingMethod='image')";
    elmt.style.verticalAlign = (verticalAlign != null) ? verticalAlign : "middle";
    return elmt;
};

/**
 * Creates a DOM element for an <code>img</code> tag using the URL given. This
 * is a convenience method that automatically includes the necessary CSS to
 * allow for translucency, even on IE.
 * 
 * @function
 * @param {String} url the URL to the image
 * @param {String} verticalAlign the CSS value for the image's vertical-align
 * @return {Element} a DOM element containing the <code>img</code> tag
 */
SimileAjax.Graphics.createTranslucentImage = SimileAjax.Graphics.pngIsTranslucent ?
    SimileAjax.Graphics._createTranslucentImage1 :
    SimileAjax.Graphics._createTranslucentImage2;

SimileAjax.Graphics._createTranslucentImageHTML1 = function(url, verticalAlign) {
    return "<img src=\"" + url + "\"" +
        (verticalAlign != null ? " style=\"vertical-align: " + verticalAlign + ";\"" : "") +
        " />";
};
SimileAjax.Graphics._createTranslucentImageHTML2 = function(url, verticalAlign) {
    var style = 
        "width: 1px; height: 1px; " +
        "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + url +"', sizingMethod='image');" +
        (verticalAlign != null ? " vertical-align: " + verticalAlign + ";" : "");
        
    return "<img src='" + url + "' style=\"" + style + "\" />";
};

/**
 * Creates an HTML string for an <code>img</code> tag using the URL given.
 * This is a convenience method that automatically includes the necessary CSS
 * to allow for translucency, even on IE.
 * 
 * @function
 * @param {String} url the URL to the image
 * @param {String} verticalAlign the CSS value for the image's vertical-align
 * @return {String} a string containing the <code>img</code> tag
 */
SimileAjax.Graphics.createTranslucentImageHTML = SimileAjax.Graphics.pngIsTranslucent ?
    SimileAjax.Graphics._createTranslucentImageHTML1 :
    SimileAjax.Graphics._createTranslucentImageHTML2;

/**
 * Sets the opacity on the given DOM element.
 *
 * @param {Element} elmt the DOM element to set the opacity on
 * @param {Number} opacity an integer from 0 to 100 specifying the opacity
 */
SimileAjax.Graphics.setOpacity = function(elmt, opacity) {
    if (SimileAjax.Platform.browser.isIE) {
        elmt.style.filter = "progid:DXImageTransform.Microsoft.Alpha(Style=0,Opacity=" + opacity + ")";
    } else {
        var o = (opacity / 100).toString();
        elmt.style.opacity = o;
        elmt.style.MozOpacity = o;
    }
};

/*==================================================
 *  Bubble
 *==================================================
 */

SimileAjax.Graphics.bubbleConfig = {
    containerCSSClass:              "simileAjax-bubble-container",
    innerContainerCSSClass:         "simileAjax-bubble-innerContainer",
    contentContainerCSSClass:       "simileAjax-bubble-contentContainer",
    
    borderGraphicSize:              50,
    borderGraphicCSSClassPrefix:    "simileAjax-bubble-border-",
    
    arrowGraphicTargetOffset:       33,  // from tip of arrow to the side of the graphic that touches the content of the bubble
    arrowGraphicLength:             100, // dimension of arrow graphic along the direction that the arrow points
    arrowGraphicWidth:              49,  // dimension of arrow graphic perpendicular to the direction that the arrow points
    arrowGraphicCSSClassPrefix:     "simileAjax-bubble-arrow-",
    
    closeGraphicCSSClass:           "simileAjax-bubble-close",
    
    extraPadding:                   20
};

/**
 * Creates a nice, rounded bubble popup with the given content in a div,
 * page coordinates and a suggested width. The bubble will point to the 
 * location on the page as described by pageX and pageY.  All measurements 
 * should be given in pixels.
 *
 * @param {Element} the content div
 * @param {Number} pageX the x coordinate of the point to point to
 * @param {Number} pageY the y coordinate of the point to point to
 * @param {Number} contentWidth a suggested width of the content
 * @param {String} orientation a string ("top", "bottom", "left", or "right")
 *   that describes the orientation of the arrow on the bubble
 * @param {Number} maxHeight. Add a scrollbar div if bubble would be too tall.
 *   Default of 0 or null means no maximum
 */
SimileAjax.Graphics.createBubbleForContentAndPoint = function(
       div, pageX, pageY, contentWidth, orientation, maxHeight) {
    if (typeof contentWidth != "number") {
        contentWidth = 300;
    }
    if (typeof maxHeight != "number") {
        maxHeight = 0;
    }

    div.style.position = "absolute";
    div.style.left = "-5000px";
    div.style.top = "0px";
    div.style.width = contentWidth + "px";
    document.body.appendChild(div);
    
    window.setTimeout(function() {
        var width = div.scrollWidth + 10;
        var height = div.scrollHeight + 10;
        var scrollDivW = 0; // width of the possible inner container when we want vertical scrolling
        if (maxHeight > 0 && height > maxHeight) {
          height = maxHeight;
          scrollDivW = width - 25;
        }  
       
        var bubble = SimileAjax.Graphics.createBubbleForPoint(pageX, pageY, width, height, orientation);
        
        document.body.removeChild(div);
        div.style.position = "static";
        div.style.left = "";
        div.style.top = "";
        
        // create a scroll div if needed
        if (scrollDivW > 0) {
          var scrollDiv = document.createElement("div");
          div.style.width = "";
          scrollDiv.style.width = scrollDivW + "px";
          scrollDiv.appendChild(div);
          bubble.content.appendChild(scrollDiv);
        } else {
          div.style.width = width + "px";
          bubble.content.appendChild(div);
        }
    }, 200);
};

/**
 * Creates a nice, rounded bubble popup with the given page coordinates and
 * content dimensions.  The bubble will point to the location on the page
 * as described by pageX and pageY.  All measurements should be given in
 * pixels.
 *
 * @param {Number} pageX the x coordinate of the point to point to
 * @param {Number} pageY the y coordinate of the point to point to
 * @param {Number} contentWidth the width of the content box in the bubble
 * @param {Number} contentHeight the height of the content box in the bubble
 * @param {String} orientation a string ("top", "bottom", "left", or "right")
 *   that describes the orientation of the arrow on the bubble
 * @return {Element} a DOM element for the newly created bubble
 */
SimileAjax.Graphics.createBubbleForPoint = function(pageX, pageY, contentWidth, contentHeight, orientation) {
    contentWidth = parseInt(contentWidth, 10); // harden against bad input bugs
    contentHeight = parseInt(contentHeight, 10); // getting numbers-as-strings
    
    var bubbleConfig = SimileAjax.Graphics.bubbleConfig;
    var pngTransparencyClassSuffix = 
        SimileAjax.Graphics.pngIsTranslucent ? "pngTranslucent" : "pngNotTranslucent";
    
    var bubbleWidth = contentWidth + 2 * bubbleConfig.borderGraphicSize;
    var bubbleHeight = contentHeight + 2 * bubbleConfig.borderGraphicSize;
    
    var generatePngSensitiveClass = function(className) {
        return className + " " + className + "-" + pngTransparencyClassSuffix;
    };
    
    /*
     *  Render container divs
     */
    var div = document.createElement("div");
    div.className = generatePngSensitiveClass(bubbleConfig.containerCSSClass);
    div.style.width = contentWidth + "px";
    div.style.height = contentHeight + "px";
    
    var divInnerContainer = document.createElement("div");
    divInnerContainer.className = generatePngSensitiveClass(bubbleConfig.innerContainerCSSClass);
    div.appendChild(divInnerContainer);
    
    /*
     *  Create layer for bubble
     */
    var close = function() { 
        if (!bubble._closed) {
            document.body.removeChild(bubble._div);
            bubble._doc = null;
            bubble._div = null;
            bubble._content = null;
            bubble._closed = true;
        }
    }
    var bubble = { _closed: false };
    var layer = SimileAjax.WindowManager.pushLayer(close, true, div);
    bubble._div = div;
    bubble.close = function() { SimileAjax.WindowManager.popLayer(layer); }
    
    /*
     *  Render border graphics
     */
    var createBorder = function(classNameSuffix) {
        var divBorderGraphic = document.createElement("div");
        divBorderGraphic.className = generatePngSensitiveClass(bubbleConfig.borderGraphicCSSClassPrefix + classNameSuffix);
        divInnerContainer.appendChild(divBorderGraphic);
    };
    createBorder("top-left");
    createBorder("top-right");
    createBorder("bottom-left");
    createBorder("bottom-right");
    createBorder("left");
    createBorder("right");
    createBorder("top");
    createBorder("bottom");
    
    /*
     *  Render content
     */
    var divContentContainer = document.createElement("div");
    divContentContainer.className = generatePngSensitiveClass(bubbleConfig.contentContainerCSSClass);
    divInnerContainer.appendChild(divContentContainer);
    bubble.content = divContentContainer;
    
    /*
     *  Render close button
     */
    var divClose = document.createElement("div");
    divClose.className = generatePngSensitiveClass(bubbleConfig.closeGraphicCSSClass);
    divInnerContainer.appendChild(divClose);
    SimileAjax.WindowManager.registerEventWithObject(divClose, "click", bubble, "close");
    
    (function() {
        var dims = SimileAjax.Graphics.getWindowDimensions();
        var docWidth = dims.w;
        var docHeight = dims.h;
        
        var halfArrowGraphicWidth = Math.ceil(bubbleConfig.arrowGraphicWidth / 2);
        
        var createArrow = function(classNameSuffix) {
            var divArrowGraphic = document.createElement("div");
            divArrowGraphic.className = generatePngSensitiveClass(bubbleConfig.arrowGraphicCSSClassPrefix + "point-" + classNameSuffix);
            divInnerContainer.appendChild(divArrowGraphic);
            return divArrowGraphic;
        };
        
        if (pageX - halfArrowGraphicWidth - bubbleConfig.borderGraphicSize - bubbleConfig.extraPadding > 0 &&
            pageX + halfArrowGraphicWidth + bubbleConfig.borderGraphicSize + bubbleConfig.extraPadding < docWidth) {
            
            /*
             *  Bubble can be positioned above or below the target point.
             */
            
            var left = pageX - Math.round(contentWidth / 2);
            left = pageX < (docWidth / 2) ?
                Math.max(left, bubbleConfig.extraPadding + bubbleConfig.borderGraphicSize) : 
                Math.min(left, docWidth - bubbleConfig.extraPadding - bubbleConfig.borderGraphicSize - contentWidth);
                
            if ((orientation && orientation == "top") || 
                (!orientation && 
                    (pageY 
                        - bubbleConfig.arrowGraphicTargetOffset 
                        - contentHeight 
                        - bubbleConfig.borderGraphicSize 
                        - bubbleConfig.extraPadding > 0))) {
                
                /*
                 *  Position bubble above the target point.
                 */
                
                var divArrow = createArrow("down");
                divArrow.style.left = (pageX - halfArrowGraphicWidth - left) + "px";
                
                div.style.left = left + "px";
                div.style.top = (pageY - bubbleConfig.arrowGraphicTargetOffset - contentHeight) + "px";
                
                return;
            } else if ((orientation && orientation == "bottom") || 
                (!orientation && 
                    (pageY 
                        + bubbleConfig.arrowGraphicTargetOffset 
                        + contentHeight 
                        + bubbleConfig.borderGraphicSize 
                        + bubbleConfig.extraPadding < docHeight))) {
                        
                /*
                 *  Position bubble below the target point.
                 */
                
                var divArrow = createArrow("up");
                divArrow.style.left = (pageX - halfArrowGraphicWidth - left) + "px";
                
                div.style.left = left + "px";
                div.style.top = (pageY + bubbleConfig.arrowGraphicTargetOffset) + "px";
                
                return;
            }
        }
        
        var top = pageY - Math.round(contentHeight / 2);
        top = pageY < (docHeight / 2) ?
            Math.max(top, bubbleConfig.extraPadding + bubbleConfig.borderGraphicSize) : 
            Math.min(top, docHeight - bubbleConfig.extraPadding - bubbleConfig.borderGraphicSize - contentHeight);
            
        if ((orientation && orientation == "left") || 
            (!orientation && 
                (pageX 
                    - bubbleConfig.arrowGraphicTargetOffset 
                    - contentWidth
                    - bubbleConfig.borderGraphicSize 
                    - bubbleConfig.extraPadding > 0))) {
            
            /*
             *  Position bubble left of the target point.
             */
            
            var divArrow = createArrow("right");
            divArrow.style.top = (pageY - halfArrowGraphicWidth - top) + "px";
            
            div.style.top = top + "px";
            div.style.left = (pageX - bubbleConfig.arrowGraphicTargetOffset - contentWidth) + "px";
        } else {
            
            /*
             *  Position bubble right of the target point, as the last resort.
             */
            
            var divArrow = createArrow("left");
            divArrow.style.top = (pageY - halfArrowGraphicWidth - top) + "px";
            
            div.style.top = top + "px";
            div.style.left = (pageX + bubbleConfig.arrowGraphicTargetOffset) + "px";
        }
    })();
    
    document.body.appendChild(div);
    
    return bubble;
};

SimileAjax.Graphics.getWindowDimensions = function() {
    if (typeof window.innerHeight == 'number') {
        return { w:window.innerWidth, h:window.innerHeight }; // Non-IE
    } else if (document.documentElement && document.documentElement.clientHeight) {
        return { // IE6+, in "standards compliant mode"
            w:document.documentElement.clientWidth,
            h:document.documentElement.clientHeight
        };
    } else if (document.body && document.body.clientHeight) {
        return { // IE 4 compatible
            w:document.body.clientWidth,
            h:document.body.clientHeight
        };
    }
};


/**
 * Creates a floating, rounded message bubble in the center of the window for
 * displaying modal information, e.g. "Loading..."
 *
 * @param {Document} doc the root document for the page to render on
 * @param {Object} an object with two properties, contentDiv and containerDiv,
 *   consisting of the newly created DOM elements
 */
SimileAjax.Graphics.createMessageBubble = function(doc) {
    var containerDiv = doc.createElement("div");
    if (SimileAjax.Graphics.pngIsTranslucent) {
        var topDiv = doc.createElement("div");
        topDiv.style.height = "33px";
        topDiv.style.background = "url(" + SimileAjax.urlPrefix + "images/message-top-left.png) top left no-repeat";
        topDiv.style.paddingLeft = "44px";
        containerDiv.appendChild(topDiv);
        
        var topRightDiv = doc.createElement("div");
        topRightDiv.style.height = "33px";
        topRightDiv.style.background = "url(" + SimileAjax.urlPrefix + "images/message-top-right.png) top right no-repeat";
        topDiv.appendChild(topRightDiv);
        
        var middleDiv = doc.createElement("div");
        middleDiv.style.background = "url(" + SimileAjax.urlPrefix + "images/message-left.png) top left repeat-y";
        middleDiv.style.paddingLeft = "44px";
        containerDiv.appendChild(middleDiv);
        
        var middleRightDiv = doc.createElement("div");
        middleRightDiv.style.background = "url(" + SimileAjax.urlPrefix + "images/message-right.png) top right repeat-y";
        middleRightDiv.style.paddingRight = "44px";
        middleDiv.appendChild(middleRightDiv);
        
        var contentDiv = doc.createElement("div");
        middleRightDiv.appendChild(contentDiv);
        
        var bottomDiv = doc.createElement("div");
        bottomDiv.style.height = "55px";
        bottomDiv.style.background = "url(" + SimileAjax.urlPrefix + "images/message-bottom-left.png) bottom left no-repeat";
        bottomDiv.style.paddingLeft = "44px";
        containerDiv.appendChild(bottomDiv);
        
        var bottomRightDiv = doc.createElement("div");
        bottomRightDiv.style.height = "55px";
        bottomRightDiv.style.background = "url(" + SimileAjax.urlPrefix + "images/message-bottom-right.png) bottom right no-repeat";
        bottomDiv.appendChild(bottomRightDiv);
    } else {
        containerDiv.style.border = "2px solid #7777AA";
        containerDiv.style.padding = "20px";
        containerDiv.style.background = "white";
        SimileAjax.Graphics.setOpacity(containerDiv, 90);
        
        var contentDiv = doc.createElement("div");
        containerDiv.appendChild(contentDiv);
    }
    
    return {
        containerDiv:   containerDiv,
        contentDiv:     contentDiv
    };
};

/*==================================================
 *  Animation
 *==================================================
 */

/**
 * Creates an animation for a function, and an interval of values.  The word
 * "animation" here is used in the sense of repeatedly calling a function with
 * a current value from within an interval, and a delta value.
 *
 * @param {Function} f a function to be called every 50 milliseconds throughout
 *   the animation duration, of the form f(current, delta), where current is
 *   the current value within the range and delta is the current change.
 * @param {Number} from a starting value
 * @param {Number} to an ending value
 * @param {Number} duration the duration of the animation in milliseconds
 * @param {Function} [cont] an optional function that is called at the end of
 *   the animation, i.e. a continuation.
 * @return {SimileAjax.Graphics._Animation} a new animation object
 */
SimileAjax.Graphics.createAnimation = function(f, from, to, duration, cont) {
    return new SimileAjax.Graphics._Animation(f, from, to, duration, cont);
};

SimileAjax.Graphics._Animation = function(f, from, to, duration, cont) {
    this.f = f;
    this.cont = (typeof cont == "function") ? cont : function() {};
    
    this.from = from;
    this.to = to;
    this.current = from;
    
    this.duration = duration;
    this.start = new Date().getTime();
    this.timePassed = 0;
};

/**
 * Runs this animation.
 */
SimileAjax.Graphics._Animation.prototype.run = function() {
    var a = this;
    window.setTimeout(function() { a.step(); }, 50);
};

/**
 * Increments this animation by one step, and then continues the animation with
 * <code>run()</code>.
 */
SimileAjax.Graphics._Animation.prototype.step = function() {
    this.timePassed += 50;
    
    var timePassedFraction = this.timePassed / this.duration;
    var parameterFraction = -Math.cos(timePassedFraction * Math.PI) / 2 + 0.5;
    var current = parameterFraction * (this.to - this.from) + this.from;
    
    try {
        this.f(current, current - this.current);
    } catch (e) {
    }
    this.current = current;
    
    if (this.timePassed < this.duration) {
        this.run();
    } else {
        this.f(this.to, 0);
        this["cont"]();
    }
};

/*==================================================
 *  CopyPasteButton
 *
 *  Adapted from http://spaces.live.com/editorial/rayozzie/demo/liveclip/liveclipsample/techPreview.html.
 *==================================================
 */

/**
 * Creates a button and textarea for displaying structured data and copying it
 * to the clipboard.  The data is dynamically generated by the given 
 * createDataFunction parameter.
 *
 * @param {String} image an image URL to use as the background for the 
 *   generated box
 * @param {Number} width the width in pixels of the generated box
 * @param {Number} height the height in pixels of the generated box
 * @param {Function} createDataFunction a function that is called with no
 *   arguments to generate the structured data
 * @return a new DOM element
 */
SimileAjax.Graphics.createStructuredDataCopyButton = function(image, width, height, createDataFunction) {
    var div = document.createElement("div");
    div.style.position = "relative";
    div.style.display = "inline";
    div.style.width = width + "px";
    div.style.height = height + "px";
    div.style.overflow = "hidden";
    div.style.margin = "2px";
    
    if (SimileAjax.Graphics.pngIsTranslucent) {
        div.style.background = "url(" + image + ") no-repeat";
    } else {
        div.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + image +"', sizingMethod='image')";
    }
    
    var style;
    if (SimileAjax.Platform.browser.isIE) {
        style = "filter:alpha(opacity=0)";
    } else {
        style = "opacity: 0";
    }
    div.innerHTML = "<textarea rows='1' autocomplete='off' value='none' style='" + style + "' />";
    
    var textarea = div.firstChild;
    textarea.style.width = width + "px";
    textarea.style.height = height + "px";
    textarea.onmousedown = function(evt) {
        evt = (evt) ? evt : ((event) ? event : null);
        if (evt.button == 2) {
            textarea.value = createDataFunction();
            textarea.select();
        }
    };
    
    return div;
};

/*==================================================
 *  getWidthHeight
 *==================================================
 */
SimileAjax.Graphics.getWidthHeight = function(el) {
    // RETURNS hash {width:  w, height: h} in pixels
    
    var w, h;
    // offsetWidth rounds on FF, so doesn't work for us.
    // See https://bugzilla.mozilla.org/show_bug.cgi?id=458617
    if (el.getBoundingClientRect == null) {
    	// use offsetWidth
      w = el.offsetWidth;
      h = el.offsetHeight;
    } else {
    	// use getBoundingClientRect
      var rect = el.getBoundingClientRect();

      w = Math.ceil(rect.right - rect.left);

    	h = Math.ceil(rect.bottom - rect.top);
    }
    return {
        width:  w,
        height: h
    };
};
 

/*==================================================
 *  FontRenderingContext
 *==================================================
 */
SimileAjax.Graphics.getFontRenderingContext = function(elmt, width) {
    return new SimileAjax.Graphics._FontRenderingContext(elmt, width);
};

SimileAjax.Graphics._FontRenderingContext = function(elmt, width) {
    this._elmt = elmt;
    this._elmt.style.visibility = "hidden";
    if (typeof width == "string") {
        this._elmt.style.width = width;
    } else if (typeof width == "number") {
        this._elmt.style.width = width + "px";
    }
};

SimileAjax.Graphics._FontRenderingContext.prototype.dispose = function() {
    this._elmt = null;
};

SimileAjax.Graphics._FontRenderingContext.prototype.update = function() {
    this._elmt.innerHTML = "A";
    this._lineHeight = this._elmt.offsetHeight;
};

SimileAjax.Graphics._FontRenderingContext.prototype.computeSize = function(text, className) {
    // className arg is optional
    var el = this._elmt;
    el.innerHTML = text;
    el.className = className === undefined ? '' : className;
    var wh = SimileAjax.Graphics.getWidthHeight(el);

    el.className = ''; // reset for the next guy
    
    return wh;
};

SimileAjax.Graphics._FontRenderingContext.prototype.getLineHeight = function() {
    return this._lineHeight;
};

/*======================================================================
 *  History
 *
 *  This is a singleton that keeps track of undoable user actions and 
 *  performs undos and redos in response to the browser's Back and 
 *  Forward buttons.
 *
 *  Call addAction(action) to register an undoable user action. action
 *  must have 4 fields:
 *
 *      perform: an argument-less function that carries out the action
 *      undo:    an argument-less function that undos the action
 *      label:   a short, user-friendly string describing the action
 *      uiLayer: the UI layer on which the action takes place
 *
 *  By default, the history keeps track of upto 10 actions. You can 
 *  configure this behavior by setting 
 *      SimileAjax.History.maxHistoryLength
 *  to a different number.
 *
 *  An iframe is inserted into the document's body element to track 
 *  onload events.
 *======================================================================
 */
 
SimileAjax.History = {
    maxHistoryLength:       10,
    historyFile:            "__history__.html",
    enabled:               true,
    
    _initialized:           false,
    _listeners:             new SimileAjax.ListenerQueue(),
    
    _actions:               [],
    _baseIndex:             0,
    _currentIndex:          0,
    
    _plainDocumentTitle:    document.title
};

SimileAjax.History.formatHistoryEntryTitle = function(actionLabel) {
    return SimileAjax.History._plainDocumentTitle + " {" + actionLabel + "}";
};

SimileAjax.History.initialize = function() {
    if (SimileAjax.History._initialized) {
        return;
    }
    
    if (SimileAjax.History.enabled) {
        var iframe = document.createElement("iframe");
        iframe.id = "simile-ajax-history";
        iframe.style.position = "absolute";
        iframe.style.width = "10px";
        iframe.style.height = "10px";
        iframe.style.top = "0px";
        iframe.style.left = "0px";
        iframe.style.visibility = "hidden";
        iframe.src = SimileAjax.History.historyFile + "?0";
        
        document.body.appendChild(iframe);
        SimileAjax.DOM.registerEvent(iframe, "load", SimileAjax.History._handleIFrameOnLoad);
        
        SimileAjax.History._iframe = iframe;
    }
    SimileAjax.History._initialized = true;
};

SimileAjax.History.addListener = function(listener) {
    SimileAjax.History.initialize();
    
    SimileAjax.History._listeners.add(listener);
};

SimileAjax.History.removeListener = function(listener) {
    SimileAjax.History.initialize();
    
    SimileAjax.History._listeners.remove(listener);
};

SimileAjax.History.addAction = function(action) {
    SimileAjax.History.initialize();
    
    SimileAjax.History._listeners.fire("onBeforePerform", [ action ]);
    window.setTimeout(function() {
        try {
            action.perform();
            SimileAjax.History._listeners.fire("onAfterPerform", [ action ]);
                
            if (SimileAjax.History.enabled) {
                SimileAjax.History._actions = SimileAjax.History._actions.slice(
                    0, SimileAjax.History._currentIndex - SimileAjax.History._baseIndex);
                    
                SimileAjax.History._actions.push(action);
                SimileAjax.History._currentIndex++;
                
                var diff = SimileAjax.History._actions.length - SimileAjax.History.maxHistoryLength;
                if (diff > 0) {
                    SimileAjax.History._actions = SimileAjax.History._actions.slice(diff);
                    SimileAjax.History._baseIndex += diff;
                }
                
                try {
                    SimileAjax.History._iframe.contentWindow.location.search = 
                        "?" + SimileAjax.History._currentIndex;
                } catch (e) {
                    /*
                     *  We can't modify location.search most probably because it's a file:// url.
                     *  We'll just going to modify the document's title.
                     */
                    var title = SimileAjax.History.formatHistoryEntryTitle(action.label);
                    document.title = title;
                }
            }
        } catch (e) {
            SimileAjax.Debug.exception(e, "Error adding action {" + action.label + "} to history");
        }
    }, 0);
};

SimileAjax.History.addLengthyAction = function(perform, undo, label) {
    SimileAjax.History.addAction({
        perform:    perform,
        undo:       undo,
        label:      label,
        uiLayer:    SimileAjax.WindowManager.getBaseLayer(),
        lengthy:    true
    });
};

SimileAjax.History._handleIFrameOnLoad = function() {
    /*
     *  This function is invoked when the user herself
     *  navigates backward or forward. We need to adjust
     *  the application's state accordingly.
     */
    
    try {
        var q = SimileAjax.History._iframe.contentWindow.location.search;
        var c = (q.length == 0) ? 0 : Math.max(0, parseInt(q.substr(1)));
        
        var finishUp = function() {
            var diff = c - SimileAjax.History._currentIndex;
            SimileAjax.History._currentIndex += diff;
            SimileAjax.History._baseIndex += diff;
                
            SimileAjax.History._iframe.contentWindow.location.search = "?" + c;
        };
        
        if (c < SimileAjax.History._currentIndex) { // need to undo
            SimileAjax.History._listeners.fire("onBeforeUndoSeveral", []);
            window.setTimeout(function() {
                while (SimileAjax.History._currentIndex > c && 
                       SimileAjax.History._currentIndex > SimileAjax.History._baseIndex) {
                       
                    SimileAjax.History._currentIndex--;
                    
                    var action = SimileAjax.History._actions[SimileAjax.History._currentIndex - SimileAjax.History._baseIndex];
                    
                    try {
                        action.undo();
                    } catch (e) {
                        SimileAjax.Debug.exception(e, "History: Failed to undo action {" + action.label + "}");
                    }
                }
                
                SimileAjax.History._listeners.fire("onAfterUndoSeveral", []);
                finishUp();
            }, 0);
        } else if (c > SimileAjax.History._currentIndex) { // need to redo
            SimileAjax.History._listeners.fire("onBeforeRedoSeveral", []);
            window.setTimeout(function() {
                while (SimileAjax.History._currentIndex < c && 
                       SimileAjax.History._currentIndex - SimileAjax.History._baseIndex < SimileAjax.History._actions.length) {
                       
                    var action = SimileAjax.History._actions[SimileAjax.History._currentIndex - SimileAjax.History._baseIndex];
                    
                    try {
                        action.perform();
                    } catch (e) {
                        SimileAjax.Debug.exception(e, "History: Failed to redo action {" + action.label + "}");
                    }
                    
                    SimileAjax.History._currentIndex++;
                }
                
                SimileAjax.History._listeners.fire("onAfterRedoSeveral", []);
                finishUp();
            }, 0);
        } else {
            var index = SimileAjax.History._currentIndex - SimileAjax.History._baseIndex - 1;
            var title = (index >= 0 && index < SimileAjax.History._actions.length) ?
                SimileAjax.History.formatHistoryEntryTitle(SimileAjax.History._actions[index].label) :
                SimileAjax.History._plainDocumentTitle;
                
            SimileAjax.History._iframe.contentWindow.document.title = title;
            document.title = title;
        }
    } catch (e) {
        // silent
    }
};

SimileAjax.History.getNextUndoAction = function() {
    try {
        var index = SimileAjax.History._currentIndex - SimileAjax.History._baseIndex - 1;
        return SimileAjax.History._actions[index];
    } catch (e) {
        return null;
    }
};

SimileAjax.History.getNextRedoAction = function() {
    try {
        var index = SimileAjax.History._currentIndex - SimileAjax.History._baseIndex;
        return SimileAjax.History._actions[index];
    } catch (e) {
        return null;
    }
};

/*==================================================
 *  HTML Utility Functions
 *==================================================
 */

SimileAjax.HTML = new Object();

SimileAjax.HTML._e2uHash = {};
(function() {
    var e2uHash = SimileAjax.HTML._e2uHash;
    e2uHash['nbsp']= '\u00A0[space]';
    e2uHash['iexcl']= '\u00A1';
    e2uHash['cent']= '\u00A2';
    e2uHash['pound']= '\u00A3';
    e2uHash['curren']= '\u00A4';
    e2uHash['yen']= '\u00A5';
    e2uHash['brvbar']= '\u00A6';
    e2uHash['sect']= '\u00A7';
    e2uHash['uml']= '\u00A8';
    e2uHash['copy']= '\u00A9';
    e2uHash['ordf']= '\u00AA';
    e2uHash['laquo']= '\u00AB';
    e2uHash['not']= '\u00AC';
    e2uHash['shy']= '\u00AD';
    e2uHash['reg']= '\u00AE';
    e2uHash['macr']= '\u00AF';
    e2uHash['deg']= '\u00B0';
    e2uHash['plusmn']= '\u00B1';
    e2uHash['sup2']= '\u00B2';
    e2uHash['sup3']= '\u00B3';
    e2uHash['acute']= '\u00B4';
    e2uHash['micro']= '\u00B5';
    e2uHash['para']= '\u00B6';
    e2uHash['middot']= '\u00B7';
    e2uHash['cedil']= '\u00B8';
    e2uHash['sup1']= '\u00B9';
    e2uHash['ordm']= '\u00BA';
    e2uHash['raquo']= '\u00BB';
    e2uHash['frac14']= '\u00BC';
    e2uHash['frac12']= '\u00BD';
    e2uHash['frac34']= '\u00BE';
    e2uHash['iquest']= '\u00BF';
    e2uHash['Agrave']= '\u00C0';
    e2uHash['Aacute']= '\u00C1';
    e2uHash['Acirc']= '\u00C2';
    e2uHash['Atilde']= '\u00C3';
    e2uHash['Auml']= '\u00C4';
    e2uHash['Aring']= '\u00C5';
    e2uHash['AElig']= '\u00C6';
    e2uHash['Ccedil']= '\u00C7';
    e2uHash['Egrave']= '\u00C8';
    e2uHash['Eacute']= '\u00C9';
    e2uHash['Ecirc']= '\u00CA';
    e2uHash['Euml']= '\u00CB';
    e2uHash['Igrave']= '\u00CC';
    e2uHash['Iacute']= '\u00CD';
    e2uHash['Icirc']= '\u00CE';
    e2uHash['Iuml']= '\u00CF';
    e2uHash['ETH']= '\u00D0';
    e2uHash['Ntilde']= '\u00D1';
    e2uHash['Ograve']= '\u00D2';
    e2uHash['Oacute']= '\u00D3';
    e2uHash['Ocirc']= '\u00D4';
    e2uHash['Otilde']= '\u00D5';
    e2uHash['Ouml']= '\u00D6';
    e2uHash['times']= '\u00D7';
    e2uHash['Oslash']= '\u00D8';
    e2uHash['Ugrave']= '\u00D9';
    e2uHash['Uacute']= '\u00DA';
    e2uHash['Ucirc']= '\u00DB';
    e2uHash['Uuml']= '\u00DC';
    e2uHash['Yacute']= '\u00DD';
    e2uHash['THORN']= '\u00DE';
    e2uHash['szlig']= '\u00DF';
    e2uHash['agrave']= '\u00E0';
    e2uHash['aacute']= '\u00E1';
    e2uHash['acirc']= '\u00E2';
    e2uHash['atilde']= '\u00E3';
    e2uHash['auml']= '\u00E4';
    e2uHash['aring']= '\u00E5';
    e2uHash['aelig']= '\u00E6';
    e2uHash['ccedil']= '\u00E7';
    e2uHash['egrave']= '\u00E8';
    e2uHash['eacute']= '\u00E9';
    e2uHash['ecirc']= '\u00EA';
    e2uHash['euml']= '\u00EB';
    e2uHash['igrave']= '\u00EC';
    e2uHash['iacute']= '\u00ED';
    e2uHash['icirc']= '\u00EE';
    e2uHash['iuml']= '\u00EF';
    e2uHash['eth']= '\u00F0';
    e2uHash['ntilde']= '\u00F1';
    e2uHash['ograve']= '\u00F2';
    e2uHash['oacute']= '\u00F3';
    e2uHash['ocirc']= '\u00F4';
    e2uHash['otilde']= '\u00F5';
    e2uHash['ouml']= '\u00F6';
    e2uHash['divide']= '\u00F7';
    e2uHash['oslash']= '\u00F8';
    e2uHash['ugrave']= '\u00F9';
    e2uHash['uacute']= '\u00FA';
    e2uHash['ucirc']= '\u00FB';
    e2uHash['uuml']= '\u00FC';
    e2uHash['yacute']= '\u00FD';
    e2uHash['thorn']= '\u00FE';
    e2uHash['yuml']= '\u00FF';
    e2uHash['quot']= '\u0022';
    e2uHash['amp']= '\u0026';
    e2uHash['lt']= '\u003C';
    e2uHash['gt']= '\u003E';
    e2uHash['OElig']= '';
    e2uHash['oelig']= '\u0153';
    e2uHash['Scaron']= '\u0160';
    e2uHash['scaron']= '\u0161';
    e2uHash['Yuml']= '\u0178';
    e2uHash['circ']= '\u02C6';
    e2uHash['tilde']= '\u02DC';
    e2uHash['ensp']= '\u2002';
    e2uHash['emsp']= '\u2003';
    e2uHash['thinsp']= '\u2009';
    e2uHash['zwnj']= '\u200C';
    e2uHash['zwj']= '\u200D';
    e2uHash['lrm']= '\u200E';
    e2uHash['rlm']= '\u200F';
    e2uHash['ndash']= '\u2013';
    e2uHash['mdash']= '\u2014';
    e2uHash['lsquo']= '\u2018';
    e2uHash['rsquo']= '\u2019';
    e2uHash['sbquo']= '\u201A';
    e2uHash['ldquo']= '\u201C';
    e2uHash['rdquo']= '\u201D';
    e2uHash['bdquo']= '\u201E';
    e2uHash['dagger']= '\u2020';
    e2uHash['Dagger']= '\u2021';
    e2uHash['permil']= '\u2030';
    e2uHash['lsaquo']= '\u2039';
    e2uHash['rsaquo']= '\u203A';
    e2uHash['euro']= '\u20AC';
    e2uHash['fnof']= '\u0192';
    e2uHash['Alpha']= '\u0391';
    e2uHash['Beta']= '\u0392';
    e2uHash['Gamma']= '\u0393';
    e2uHash['Delta']= '\u0394';
    e2uHash['Epsilon']= '\u0395';
    e2uHash['Zeta']= '\u0396';
    e2uHash['Eta']= '\u0397';
    e2uHash['Theta']= '\u0398';
    e2uHash['Iota']= '\u0399';
    e2uHash['Kappa']= '\u039A';
    e2uHash['Lambda']= '\u039B';
    e2uHash['Mu']= '\u039C';
    e2uHash['Nu']= '\u039D';
    e2uHash['Xi']= '\u039E';
    e2uHash['Omicron']= '\u039F';
    e2uHash['Pi']= '\u03A0';
    e2uHash['Rho']= '\u03A1';
    e2uHash['Sigma']= '\u03A3';
    e2uHash['Tau']= '\u03A4';
    e2uHash['Upsilon']= '\u03A5';
    e2uHash['Phi']= '\u03A6';
    e2uHash['Chi']= '\u03A7';
    e2uHash['Psi']= '\u03A8';
    e2uHash['Omega']= '\u03A9';
    e2uHash['alpha']= '\u03B1';
    e2uHash['beta']= '\u03B2';
    e2uHash['gamma']= '\u03B3';
    e2uHash['delta']= '\u03B4';
    e2uHash['epsilon']= '\u03B5';
    e2uHash['zeta']= '\u03B6';
    e2uHash['eta']= '\u03B7';
    e2uHash['theta']= '\u03B8';
    e2uHash['iota']= '\u03B9';
    e2uHash['kappa']= '\u03BA';
    e2uHash['lambda']= '\u03BB';
    e2uHash['mu']= '\u03BC';
    e2uHash['nu']= '\u03BD';
    e2uHash['xi']= '\u03BE';
    e2uHash['omicron']= '\u03BF';
    e2uHash['pi']= '\u03C0';
    e2uHash['rho']= '\u03C1';
    e2uHash['sigmaf']= '\u03C2';
    e2uHash['sigma']= '\u03C3';
    e2uHash['tau']= '\u03C4';
    e2uHash['upsilon']= '\u03C5';
    e2uHash['phi']= '\u03C6';
    e2uHash['chi']= '\u03C7';
    e2uHash['psi']= '\u03C8';
    e2uHash['omega']= '\u03C9';
    e2uHash['thetasym']= '\u03D1';
    e2uHash['upsih']= '\u03D2';
    e2uHash['piv']= '\u03D6';
    e2uHash['bull']= '\u2022';
    e2uHash['hellip']= '\u2026';
    e2uHash['prime']= '\u2032';
    e2uHash['Prime']= '\u2033';
    e2uHash['oline']= '\u203E';
    e2uHash['frasl']= '\u2044';
    e2uHash['weierp']= '\u2118';
    e2uHash['image']= '\u2111';
    e2uHash['real']= '\u211C';
    e2uHash['trade']= '\u2122';
    e2uHash['alefsym']= '\u2135';
    e2uHash['larr']= '\u2190';
    e2uHash['uarr']= '\u2191';
    e2uHash['rarr']= '\u2192';
    e2uHash['darr']= '\u2193';
    e2uHash['harr']= '\u2194';
    e2uHash['crarr']= '\u21B5';
    e2uHash['lArr']= '\u21D0';
    e2uHash['uArr']= '\u21D1';
    e2uHash['rArr']= '\u21D2';
    e2uHash['dArr']= '\u21D3';
    e2uHash['hArr']= '\u21D4';
    e2uHash['forall']= '\u2200';
    e2uHash['part']= '\u2202';
    e2uHash['exist']= '\u2203';
    e2uHash['empty']= '\u2205';
    e2uHash['nabla']= '\u2207';
    e2uHash['isin']= '\u2208';
    e2uHash['notin']= '\u2209';
    e2uHash['ni']= '\u220B';
    e2uHash['prod']= '\u220F';
    e2uHash['sum']= '\u2211';
    e2uHash['minus']= '\u2212';
    e2uHash['lowast']= '\u2217';
    e2uHash['radic']= '\u221A';
    e2uHash['prop']= '\u221D';
    e2uHash['infin']= '\u221E';
    e2uHash['ang']= '\u2220';
    e2uHash['and']= '\u2227';
    e2uHash['or']= '\u2228';
    e2uHash['cap']= '\u2229';
    e2uHash['cup']= '\u222A';
    e2uHash['int']= '\u222B';
    e2uHash['there4']= '\u2234';
    e2uHash['sim']= '\u223C';
    e2uHash['cong']= '\u2245';
    e2uHash['asymp']= '\u2248';
    e2uHash['ne']= '\u2260';
    e2uHash['equiv']= '\u2261';
    e2uHash['le']= '\u2264';
    e2uHash['ge']= '\u2265';
    e2uHash['sub']= '\u2282';
    e2uHash['sup']= '\u2283';
    e2uHash['nsub']= '\u2284';
    e2uHash['sube']= '\u2286';
    e2uHash['supe']= '\u2287';
    e2uHash['oplus']= '\u2295';
    e2uHash['otimes']= '\u2297';
    e2uHash['perp']= '\u22A5';
    e2uHash['sdot']= '\u22C5';
    e2uHash['lceil']= '\u2308';
    e2uHash['rceil']= '\u2309';
    e2uHash['lfloor']= '\u230A';
    e2uHash['rfloor']= '\u230B';
    e2uHash['lang']= '\u2329';
    e2uHash['rang']= '\u232A';
    e2uHash['loz']= '\u25CA';
    e2uHash['spades']= '\u2660';
    e2uHash['clubs']= '\u2663';
    e2uHash['hearts']= '\u2665';
    e2uHash['diams']= '\u2666'; 
})();

SimileAjax.HTML.deEntify = function(s) {
    var e2uHash = SimileAjax.HTML._e2uHash;
    
    var re = /&(\w+?);/;
    while (re.test(s)) {
        var m = s.match(re);
        s = s.replace(re, e2uHash[m[1]]);
    }
    return s;
};

/*
 *  Copied directly from http://www.json.org/json.js.
 */

/*
    json.js
    2006-04-28

    This file adds these methods to JavaScript:

        object.toJSONString()

            This method produces a JSON text from an object. The
            object must not contain any cyclical references.

        array.toJSONString()

            This method produces a JSON text from an array. The
            array must not contain any cyclical references.

        string.parseJSON()

            This method parses a JSON text to produce an object or
            array. It will return false if there is an error.
*/

SimileAjax.JSON = new Object();

(function () {
    var m = {
        '\b': '\\b',
        '\t': '\\t',
        '\n': '\\n',
        '\f': '\\f',
        '\r': '\\r',
        '"' : '\\"',
        '\\': '\\\\'
    };
    var s = {
        array: function (x) {
            var a = ['['], b, f, i, l = x.length, v;
            for (i = 0; i < l; i += 1) {
                v = x[i];
                f = s[typeof v];
                if (f) {
                    v = f(v);
                    if (typeof v == 'string') {
                        if (b) {
                            a[a.length] = ',';
                        }
                        a[a.length] = v;
                        b = true;
                    }
                }
            }
            a[a.length] = ']';
            return a.join('');
        },
        'boolean': function (x) {
            return String(x);
        },
        'null': function (x) {
            return "null";
        },
        number: function (x) {
            return isFinite(x) ? String(x) : 'null';
        },
        object: function (x) {
            if (x) {
                if (x instanceof Array) {
                    return s.array(x);
                }
                var a = ['{'], b, f, i, v;
                for (i in x) {
                    v = x[i];
                    f = s[typeof v];
                    if (f) {
                        v = f(v);
                        if (typeof v == 'string') {
                            if (b) {
                                a[a.length] = ',';
                            }
                            a.push(s.string(i), ':', v);
                            b = true;
                        }
                    }
                }
                a[a.length] = '}';
                return a.join('');
            }
            return 'null';
        },
        string: function (x) {
            if (/["\\\x00-\x1f]/.test(x)) {
                x = x.replace(/([\x00-\x1f\\"])/g, function(a, b) {
                    var c = m[b];
                    if (c) {
                        return c;
                    }
                    c = b.charCodeAt();
                    return '\\u00' +
                        Math.floor(c / 16).toString(16) +
                        (c % 16).toString(16);
                });
            }
            return '"' + x + '"';
        }
    };

    SimileAjax.JSON.toJSONString = function(o) {
        if (o instanceof Object) {
            return s.object(o);
        } else if (o instanceof Array) {
            return s.array(o);
        } else {
            return o.toString();
        }
    };
    
    SimileAjax.JSON.parseJSON = function () {
        try {
            return !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(
                    this.replace(/"(\\.|[^"\\])*"/g, ''))) &&
                eval('(' + this + ')');
        } catch (e) {
            return false;
        }
    };
})();



/*==================================================
 *  This file is used to detect that all outstanding
 *  javascript files have been loaded. You can put
 *  a function reference into SimileAjax_onLoad
 *  to have it executed once all javascript files
 *  have loaded.
 *==================================================
 */
(function() {
    var substring = SimileAjax.urlPrefix + "scripts/signal.js";
    var heads = document.documentElement.getElementsByTagName("head");
    for (var h = 0; h < heads.length; h++) {
        var node = heads[h].firstChild;
        while (node != null) {
            if (node.nodeType == 1 && node.tagName.toLowerCase() == "script") {
                var url = node.src;
                var i = url.indexOf(substring);
                if (i >= 0) {
                    heads[h].removeChild(node); // remove it so we won't hit it again

                    var count = parseInt(url.substr(url.indexOf(substring) + substring.length + 1));
                    SimileAjax.loadingScriptsCount -= count;
                    if (SimileAjax.loadingScriptsCount == 0) {
                        var f = null;
                        if (typeof SimileAjax_onLoad == "string") {
                            f = eval(SimileAjax_onLoad);
                            SimileAjax_onLoad = null;
                        } else if (typeof SimileAjax_onLoad == "function") {
                            f = SimileAjax_onLoad;
                            SimileAjax_onLoad = null;
                        }
                        
                        if (f != null) {
                            f();
                        }
                    }
                    return;
                }
            }
            node = node.nextSibling;
        }
    }
})();

/*==================================================
 *  String Utility Functions and Constants
 *==================================================
 */

String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, '');
};

String.prototype.startsWith = function(prefix) {
    return this.length >= prefix.length && this.substr(0, prefix.length) == prefix;
};

String.prototype.endsWith = function(suffix) {
    return this.length >= suffix.length && this.substr(this.length - suffix.length) == suffix;
};

String.substitute = function(s, objects) {
    var result = "";
    var start = 0;
    while (start < s.length - 1) {
        var percent = s.indexOf("%", start);
        if (percent < 0 || percent == s.length - 1) {
            break;
        } else if (percent > start && s.charAt(percent - 1) == "\\") {
            result += s.substring(start, percent - 1) + "%";
            start = percent + 1;
        } else {
            var n = parseInt(s.charAt(percent + 1));
            if (isNaN(n) || n >= objects.length) {
                result += s.substring(start, percent + 2);
            } else {
                result += s.substring(start, percent) + objects[n].toString();
            }
            start = percent + 2;
        }
    }
    
    if (start < s.length) {
        result += s.substring(start);
    }
    return result;
};

/*==================================================
 *  Default Unit
 *==================================================
 */

SimileAjax.NativeDateUnit = new Object();

SimileAjax.NativeDateUnit.makeDefaultValue = function() {
    return new Date();
};

SimileAjax.NativeDateUnit.cloneValue = function(v) {
    return new Date(v.getTime());
};

SimileAjax.NativeDateUnit.getParser = function(format) {
    if (typeof format == "string") {
        format = format.toLowerCase();
    }
    return (format == "iso8601" || format == "iso 8601") ?
        SimileAjax.DateTime.parseIso8601DateTime : 
        SimileAjax.DateTime.parseGregorianDateTime;
};

SimileAjax.NativeDateUnit.parseFromObject = function(o) {
    return SimileAjax.DateTime.parseGregorianDateTime(o);
};

SimileAjax.NativeDateUnit.toNumber = function(v) {
    return v.getTime();
};

SimileAjax.NativeDateUnit.fromNumber = function(n) {
    return new Date(n);
};

SimileAjax.NativeDateUnit.compare = function(v1, v2) {
    var n1, n2;
    if (typeof v1 == "object") {
        n1 = v1.getTime();
    } else {
        n1 = Number(v1);
    }
    if (typeof v2 == "object") {
        n2 = v2.getTime();
    } else {
        n2 = Number(v2);
    }
    
    return n1 - n2;
};

SimileAjax.NativeDateUnit.earlier = function(v1, v2) {
    return SimileAjax.NativeDateUnit.compare(v1, v2) < 0 ? v1 : v2;
};

SimileAjax.NativeDateUnit.later = function(v1, v2) {
    return SimileAjax.NativeDateUnit.compare(v1, v2) > 0 ? v1 : v2;
};

SimileAjax.NativeDateUnit.change = function(v, n) {
    return new Date(v.getTime() + n);
};


/**
 * @fileOverview UI layers and window-wide dragging
 * @name SimileAjax.WindowManager
 */

/**
 *  This is a singleton that keeps track of UI layers (modal and 
 *  modeless) and enables/disables UI elements based on which layers
 *  they belong to. It also provides window-wide dragging 
 *  implementation.
 */ 
SimileAjax.WindowManager = {
    _initialized:       false,
    _listeners:         [],
    
    _draggedElement:                null,
    _draggedElementCallback:        null,
    _dropTargetHighlightElement:    null,
    _lastCoords:                    null,
    _ghostCoords:                   null,
    _draggingMode:                  "",
    _dragging:                      false,
    
    _layers:            []
};

SimileAjax.WindowManager.initialize = function() {
    if (SimileAjax.WindowManager._initialized) {
        return;
    }
    
    SimileAjax.DOM.registerEvent(document.body, "mousedown", SimileAjax.WindowManager._onBodyMouseDown);
    SimileAjax.DOM.registerEvent(document.body, "mousemove", SimileAjax.WindowManager._onBodyMouseMove);
    SimileAjax.DOM.registerEvent(document.body, "mouseup",   SimileAjax.WindowManager._onBodyMouseUp);
    SimileAjax.DOM.registerEvent(document, "keydown",       SimileAjax.WindowManager._onBodyKeyDown);
    SimileAjax.DOM.registerEvent(document, "keyup",         SimileAjax.WindowManager._onBodyKeyUp);
    
    SimileAjax.WindowManager._layers.push({index: 0});
    
    SimileAjax.WindowManager._historyListener = {
        onBeforeUndoSeveral:    function() {},
        onAfterUndoSeveral:     function() {},
        onBeforeUndo:           function() {},
        onAfterUndo:            function() {},
        
        onBeforeRedoSeveral:    function() {},
        onAfterRedoSeveral:     function() {},
        onBeforeRedo:           function() {},
        onAfterRedo:            function() {}
    };
    SimileAjax.History.addListener(SimileAjax.WindowManager._historyListener);
    
    SimileAjax.WindowManager._initialized = true;
};

SimileAjax.WindowManager.getBaseLayer = function() {
    SimileAjax.WindowManager.initialize();
    return SimileAjax.WindowManager._layers[0];
};

SimileAjax.WindowManager.getHighestLayer = function() {
    SimileAjax.WindowManager.initialize();
    return SimileAjax.WindowManager._layers[SimileAjax.WindowManager._layers.length - 1];
};

SimileAjax.WindowManager.registerEventWithObject = function(elmt, eventName, obj, handlerName, layer) {
    SimileAjax.WindowManager.registerEvent(
        elmt, 
        eventName, 
        function(elmt2, evt, target) {
            return obj[handlerName].call(obj, elmt2, evt, target);
        },
        layer
    );
};

SimileAjax.WindowManager.registerEvent = function(elmt, eventName, handler, layer) {
    if (layer == null) {
        layer = SimileAjax.WindowManager.getHighestLayer();
    }
    
    var handler2 = function(elmt, evt, target) {
        if (SimileAjax.WindowManager._canProcessEventAtLayer(layer)) {
            SimileAjax.WindowManager._popToLayer(layer.index);
            try {
                handler(elmt, evt, target);
            } catch (e) {
                SimileAjax.Debug.exception(e);
            }
        }
        SimileAjax.DOM.cancelEvent(evt);
        return false;
    }
    
    SimileAjax.DOM.registerEvent(elmt, eventName, handler2);
};

SimileAjax.WindowManager.pushLayer = function(f, ephemeral, elmt) {
    var layer = { onPop: f, index: SimileAjax.WindowManager._layers.length, ephemeral: (ephemeral), elmt: elmt };
    SimileAjax.WindowManager._layers.push(layer);
    
    return layer;
};

SimileAjax.WindowManager.popLayer = function(layer) {
    for (var i = 1; i < SimileAjax.WindowManager._layers.length; i++) {
        if (SimileAjax.WindowManager._layers[i] == layer) {
            SimileAjax.WindowManager._popToLayer(i - 1);
            break;
        }
    }
};

SimileAjax.WindowManager.popAllLayers = function() {
    SimileAjax.WindowManager._popToLayer(0);
};

SimileAjax.WindowManager.registerForDragging = function(elmt, callback, layer) {
    SimileAjax.WindowManager.registerEvent(
        elmt, 
        "mousedown", 
        function(elmt, evt, target) {
            SimileAjax.WindowManager._handleMouseDown(elmt, evt, callback);
        }, 
        layer
    );
};

SimileAjax.WindowManager._popToLayer = function(level) {
    while (level+1 < SimileAjax.WindowManager._layers.length) {
        try {
            var layer = SimileAjax.WindowManager._layers.pop();
            if (layer.onPop != null) {
                layer.onPop();
            }
        } catch (e) {
        }
    }
};

SimileAjax.WindowManager._canProcessEventAtLayer = function(layer) {
    if (layer.index == (SimileAjax.WindowManager._layers.length - 1)) {
        return true;
    }
    for (var i = layer.index + 1; i < SimileAjax.WindowManager._layers.length; i++) {
        if (!SimileAjax.WindowManager._layers[i].ephemeral) {
            return false;
        }
    }
    return true;
};

SimileAjax.WindowManager.cancelPopups = function(evt) {
    var evtCoords = (evt) ? SimileAjax.DOM.getEventPageCoordinates(evt) : { x: -1, y: -1 };
    
    var i = SimileAjax.WindowManager._layers.length - 1;
    while (i > 0 && SimileAjax.WindowManager._layers[i].ephemeral) {
        var layer = SimileAjax.WindowManager._layers[i];
        if (layer.elmt != null) { // if event falls within main element of layer then don't cancel
            var elmt = layer.elmt;
            var elmtCoords = SimileAjax.DOM.getPageCoordinates(elmt);
            if (evtCoords.x >= elmtCoords.left && evtCoords.x < (elmtCoords.left + elmt.offsetWidth) &&
                evtCoords.y >= elmtCoords.top && evtCoords.y < (elmtCoords.top + elmt.offsetHeight)) {
                break;
            }
        }
        i--;
    }
    SimileAjax.WindowManager._popToLayer(i);
};

SimileAjax.WindowManager._onBodyMouseDown = function(elmt, evt, target) {
    if (!("eventPhase" in evt) || evt.eventPhase == evt.BUBBLING_PHASE) {
        SimileAjax.WindowManager.cancelPopups(evt);
    }
};

SimileAjax.WindowManager._handleMouseDown = function(elmt, evt, callback) {
    SimileAjax.WindowManager._draggedElement = elmt;
    SimileAjax.WindowManager._draggedElementCallback = callback;
    SimileAjax.WindowManager._lastCoords = { x: evt.clientX, y: evt.clientY };
        
    SimileAjax.DOM.cancelEvent(evt);
    return false;
};

SimileAjax.WindowManager._onBodyKeyDown = function(elmt, evt, target) {
    if (SimileAjax.WindowManager._dragging) {
        if (evt.keyCode == 27) { // esc
            SimileAjax.WindowManager._cancelDragging();
        } else if ((evt.keyCode == 17 || evt.keyCode == 16) && SimileAjax.WindowManager._draggingMode != "copy") {
            SimileAjax.WindowManager._draggingMode = "copy";
            
            var img = SimileAjax.Graphics.createTranslucentImage(SimileAjax.urlPrefix + "images/copy.png");
            img.style.position = "absolute";
            img.style.left = (SimileAjax.WindowManager._ghostCoords.left - 16) + "px";
            img.style.top = (SimileAjax.WindowManager._ghostCoords.top) + "px";
            document.body.appendChild(img);
            
            SimileAjax.WindowManager._draggingModeIndicatorElmt = img;
        }
    }
};

SimileAjax.WindowManager._onBodyKeyUp = function(elmt, evt, target) {
    if (SimileAjax.WindowManager._dragging) {
        if (evt.keyCode == 17 || evt.keyCode == 16) {
            SimileAjax.WindowManager._draggingMode = "";
            if (SimileAjax.WindowManager._draggingModeIndicatorElmt != null) {
                document.body.removeChild(SimileAjax.WindowManager._draggingModeIndicatorElmt);
                SimileAjax.WindowManager._draggingModeIndicatorElmt = null;
            }
        }
    }
};

SimileAjax.WindowManager._onBodyMouseMove = function(elmt, evt, target) {
    if (SimileAjax.WindowManager._draggedElement != null) {
        var callback = SimileAjax.WindowManager._draggedElementCallback;
        
        var lastCoords = SimileAjax.WindowManager._lastCoords;
        var diffX = evt.clientX - lastCoords.x;
        var diffY = evt.clientY - lastCoords.y;
        
        if (!SimileAjax.WindowManager._dragging) {
            if (Math.abs(diffX) > 5 || Math.abs(diffY) > 5) {
                try {
                    if ("onDragStart" in callback) {
                        callback.onDragStart();
                    }
                    
                    if ("ghost" in callback && callback.ghost) {
                        var draggedElmt = SimileAjax.WindowManager._draggedElement;
                        
                        SimileAjax.WindowManager._ghostCoords = SimileAjax.DOM.getPageCoordinates(draggedElmt);
                        SimileAjax.WindowManager._ghostCoords.left += diffX;
                        SimileAjax.WindowManager._ghostCoords.top += diffY;
                        
                        var ghostElmt = draggedElmt.cloneNode(true);
                        ghostElmt.style.position = "absolute";
                        ghostElmt.style.left = SimileAjax.WindowManager._ghostCoords.left + "px";
                        ghostElmt.style.top = SimileAjax.WindowManager._ghostCoords.top + "px";
                        ghostElmt.style.zIndex = 1000;
                        SimileAjax.Graphics.setOpacity(ghostElmt, 50);
                        
                        document.body.appendChild(ghostElmt);
                        callback._ghostElmt = ghostElmt;
                    }
                    
                    SimileAjax.WindowManager._dragging = true;
                    SimileAjax.WindowManager._lastCoords = { x: evt.clientX, y: evt.clientY };
                    
                    document.body.focus();
                } catch (e) {
                    SimileAjax.Debug.exception("WindowManager: Error handling mouse down", e);
                    SimileAjax.WindowManager._cancelDragging();
                }
            }
        } else {
            try {
                SimileAjax.WindowManager._lastCoords = { x: evt.clientX, y: evt.clientY };
                
                if ("onDragBy" in callback) {
                    callback.onDragBy(diffX, diffY);
                }
                
                if ("_ghostElmt" in callback) {
                    var ghostElmt = callback._ghostElmt;
                    
                    SimileAjax.WindowManager._ghostCoords.left += diffX;
                    SimileAjax.WindowManager._ghostCoords.top += diffY;
                    
                    ghostElmt.style.left = SimileAjax.WindowManager._ghostCoords.left + "px";
                    ghostElmt.style.top = SimileAjax.WindowManager._ghostCoords.top + "px";
                    if (SimileAjax.WindowManager._draggingModeIndicatorElmt != null) {
                        var indicatorElmt = SimileAjax.WindowManager._draggingModeIndicatorElmt;
                        
                        indicatorElmt.style.left = (SimileAjax.WindowManager._ghostCoords.left - 16) + "px";
                        indicatorElmt.style.top = SimileAjax.WindowManager._ghostCoords.top + "px";
                    }
                    
                    if ("droppable" in callback && callback.droppable) {
                        var coords = SimileAjax.DOM.getEventPageCoordinates(evt);
                        var target = SimileAjax.DOM.hittest(
                            coords.x, coords.y, 
                            [   SimileAjax.WindowManager._ghostElmt, 
                                SimileAjax.WindowManager._dropTargetHighlightElement 
                            ]
                        );
                        target = SimileAjax.WindowManager._findDropTarget(target);
                        
                        if (target != SimileAjax.WindowManager._potentialDropTarget) {
                            if (SimileAjax.WindowManager._dropTargetHighlightElement != null) {
                                document.body.removeChild(SimileAjax.WindowManager._dropTargetHighlightElement);
                                
                                SimileAjax.WindowManager._dropTargetHighlightElement = null;
                                SimileAjax.WindowManager._potentialDropTarget = null;
                            }

                            var droppable = false;
                            if (target != null) {
                                if ((!("canDropOn" in callback) || callback.canDropOn(target)) &&
                                    (!("canDrop" in target) || target.canDrop(SimileAjax.WindowManager._draggedElement))) {
                                    
                                    droppable = true;
                                }
                            }
                            
                            if (droppable) {
                                var border = 4;
                                var targetCoords = SimileAjax.DOM.getPageCoordinates(target);
                                var highlight = document.createElement("div");
                                highlight.style.border = border + "px solid yellow";
                                highlight.style.backgroundColor = "yellow";
                                highlight.style.position = "absolute";
                                highlight.style.left = targetCoords.left + "px";
                                highlight.style.top = targetCoords.top + "px";
                                highlight.style.width = (target.offsetWidth - border * 2) + "px";
                                highlight.style.height = (target.offsetHeight - border * 2) + "px";
                                SimileAjax.Graphics.setOpacity(highlight, 30);
                                document.body.appendChild(highlight);
                                
                                SimileAjax.WindowManager._potentialDropTarget = target;
                                SimileAjax.WindowManager._dropTargetHighlightElement = highlight;
                            }
                        }
                    }
                }
            } catch (e) {
                SimileAjax.Debug.exception("WindowManager: Error handling mouse move", e);
                SimileAjax.WindowManager._cancelDragging();
            }
        }
        
        SimileAjax.DOM.cancelEvent(evt);
        return false;
    }
};

SimileAjax.WindowManager._onBodyMouseUp = function(elmt, evt, target) {
    if (SimileAjax.WindowManager._draggedElement != null) {
        try {
            if (SimileAjax.WindowManager._dragging) {
                var callback = SimileAjax.WindowManager._draggedElementCallback;
                if ("onDragEnd" in callback) {
                    callback.onDragEnd();
                }
                if ("droppable" in callback && callback.droppable) {
                    var dropped = false;
                    
                    var target = SimileAjax.WindowManager._potentialDropTarget;
                    if (target != null) {
                        if ((!("canDropOn" in callback) || callback.canDropOn(target)) &&
                            (!("canDrop" in target) || target.canDrop(SimileAjax.WindowManager._draggedElement))) {
                            
                            if ("onDropOn" in callback) {
                                callback.onDropOn(target);
                            }
                            target.ondrop(SimileAjax.WindowManager._draggedElement, SimileAjax.WindowManager._draggingMode);
                            
                            dropped = true;
                        }
                    }
                    
                    if (!dropped) {
                        // TODO: do holywood explosion here
                    }
                }
            }
        } finally {
            SimileAjax.WindowManager._cancelDragging();
        }
        
        SimileAjax.DOM.cancelEvent(evt);
        return false;
    }
};

SimileAjax.WindowManager._cancelDragging = function() {
    var callback = SimileAjax.WindowManager._draggedElementCallback;
    if ("_ghostElmt" in callback) {
        var ghostElmt = callback._ghostElmt;
        document.body.removeChild(ghostElmt);
        
        delete callback._ghostElmt;
    }
    if (SimileAjax.WindowManager._dropTargetHighlightElement != null) {
        document.body.removeChild(SimileAjax.WindowManager._dropTargetHighlightElement);
        SimileAjax.WindowManager._dropTargetHighlightElement = null;
    }
    if (SimileAjax.WindowManager._draggingModeIndicatorElmt != null) {
        document.body.removeChild(SimileAjax.WindowManager._draggingModeIndicatorElmt);
        SimileAjax.WindowManager._draggingModeIndicatorElmt = null;
    }
    
    SimileAjax.WindowManager._draggedElement = null;
    SimileAjax.WindowManager._draggedElementCallback = null;
    SimileAjax.WindowManager._potentialDropTarget = null;
    SimileAjax.WindowManager._dropTargetHighlightElement = null;
    SimileAjax.WindowManager._lastCoords = null;
    SimileAjax.WindowManager._ghostCoords = null;
    SimileAjax.WindowManager._draggingMode = "";
    SimileAjax.WindowManager._dragging = false;
};

SimileAjax.WindowManager._findDropTarget = function(elmt) {
    while (elmt != null) {
        if ("ondrop" in elmt && (typeof elmt.ondrop) == "function") {
            break;
        }
        elmt = elmt.parentNode;
    }
    return elmt;
};

/**
 * @fileOverview XmlHttp utility functions
 * @name SimileAjax.XmlHttp
 */

SimileAjax.XmlHttp = new Object();

/**
 *  Callback for XMLHttp onRequestStateChange.
 */
SimileAjax.XmlHttp._onReadyStateChange = function(xmlhttp, fError, fDone) {
    switch (xmlhttp.readyState) {
    // 1: Request not yet made
    // 2: Contact established with server but nothing downloaded yet
    // 3: Called multiple while downloading in progress
    
    // Download complete
    case 4:
        try {
            if (xmlhttp.status == 0     // file:// urls, works on Firefox
             || xmlhttp.status == 200   // http:// urls
            ) {
                if (fDone) {
                    fDone(xmlhttp);
                }
            } else {
                if (fError) {
                    fError(
                        xmlhttp.statusText,
                        xmlhttp.status,
                        xmlhttp
                    );
                }
            }
        } catch (e) {
            SimileAjax.Debug.exception("XmlHttp: Error handling onReadyStateChange", e);
        }
        break;
    }
};

/**
 *  Creates an XMLHttpRequest object. On the first run, this
 *  function creates a platform-specific function for
 *  instantiating an XMLHttpRequest object and then replaces
 *  itself with that function.
 */
SimileAjax.XmlHttp._createRequest = function() {
    if (SimileAjax.Platform.browser.isIE) {
        var programIDs = [
        "Msxml2.XMLHTTP",
        "Microsoft.XMLHTTP",
        "Msxml2.XMLHTTP.4.0"
        ];
        for (var i = 0; i < programIDs.length; i++) {
            try {
                var programID = programIDs[i];
                var f = function() {
                    return new ActiveXObject(programID);
                };
                var o = f();
                
                // We are replacing the SimileAjax._createXmlHttpRequest
                // function with this inner function as we've
                // found out that it works. This is so that we
                // don't have to do all the testing over again
                // on subsequent calls.
                SimileAjax.XmlHttp._createRequest = f;
                
                return o;
            } catch (e) {
                // silent
            }
        }
        // fall through to try new XMLHttpRequest();
    }

    try {
        var f = function() {
            return new XMLHttpRequest();
        };
        var o = f();
        
        // We are replacing the SimileAjax._createXmlHttpRequest
        // function with this inner function as we've
        // found out that it works. This is so that we
        // don't have to do all the testing over again
        // on subsequent calls.
        SimileAjax.XmlHttp._createRequest = f;
        
        return o;
    } catch (e) {
        throw new Error("Failed to create an XMLHttpRequest object");
    }
};

/**
 * Performs an asynchronous HTTP GET.
 *  
 * @param {Function} fError a function of the form 
     function(statusText, statusCode, xmlhttp)
 * @param {Function} fDone a function of the form function(xmlhttp)
 */
SimileAjax.XmlHttp.get = function(url, fError, fDone) {
    var xmlhttp = SimileAjax.XmlHttp._createRequest();
    
    xmlhttp.open("GET", url, true);
    xmlhttp.onreadystatechange = function() {
        SimileAjax.XmlHttp._onReadyStateChange(xmlhttp, fError, fDone);
    };
    xmlhttp.send(null);
};

/**
 * Performs an asynchronous HTTP POST.
 *  
 * @param {Function} fError a function of the form 
     function(statusText, statusCode, xmlhttp)
 * @param {Function} fDone a function of the form function(xmlhttp)
 */
SimileAjax.XmlHttp.post = function(url, body, fError, fDone) {
    var xmlhttp = SimileAjax.XmlHttp._createRequest();
    
    xmlhttp.open("POST", url, true);
    xmlhttp.onreadystatechange = function() {
        SimileAjax.XmlHttp._onReadyStateChange(xmlhttp, fError, fDone);
    };
    xmlhttp.send(body);
};

SimileAjax.XmlHttp._forceXML = function(xmlhttp) {
    try {
        xmlhttp.overrideMimeType("text/xml");
    } catch (e) {
        xmlhttp.setrequestheader("Content-Type", "text/xml");
    }
};