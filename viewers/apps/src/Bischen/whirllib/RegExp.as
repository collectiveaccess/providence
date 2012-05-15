//---------------------------------------------------------------------------
// RegExp object for Flash5 ActionScript Ver1.01
//	Author: Pavils Jurjans
//	Email : pavils@mailbox.riga.lv
//      Default source for this file can be found at:
//              http://www.jurjans.lv/flash/RegExp.html
//---------------------------------------------------------------------------
// This class is provided for flash community for free with a kind request 
// to keep the copyright lines in AS file untouched. However, debugging and 
// development of class takes much time limiting my opportunities to earn 
// some income on other projects. To overcome this, I have set up an account 
// with PayPal (http://www.paypal.com). Please, if you find my work valuable,
// especially if you use it in commercial projects, make a donation 
// to pavils@mailbox.riga.lv of amount you feel is right. Please provide your
// E-mail address upon payment submission so I can enlist you in my upgrade newslist.
//---------------------------------------------------------------------------
class RegExp {
	public var const:String = null;
	public var source:String = null;
	public var global:Boolean = false;
	public var ignoreCase:Boolean = false;
	public var multiline:Boolean = false;
	public var lastIndex:Number = null;
	public static var _xrStatic:Number = null;
	public var _xr:Number = null;
	public static var _xp:Number = null;
	public static var _xxa:Array = null;
	public static var _xxlp:Number = null;
	public var _xq:Number = null;
	public var _xqc:Number = null;
	public static var d:Number = null;
	public static var _xiStatic:Number = null;
	public var _xi:Number = 0;
	public static var _xxlm:String = null;
	public static var _xxlc:String = null;
	public static var _xxrc:String = null;
	public static var lastMatch:String = null;
	public static var leftContext:String = null;
	public static var rightContext:String = null;
	public static var _xa:Array = new Array();
	public static var lastParen:String = null;
	public static var _xaStatic:Array = new Array();
	public static var $1:String = null;
	public static var $2:String = null;
	public static var $3:String = null;
	public static var $4:String = null;
	public static var $5:String = null;
	public static var $6:String = null;
	public static var $7:String = null;
	public static var $8:String = null;
	public static var $9:String = null;
	private static var _setString:Boolean = RegExp.setStringMethods();

	function RegExp() {
		if (arguments[0] == null) {
		} else {
			const = "RegExp";
			compile.apply(this, arguments);
		}
	}
	public function invStr(sVal:String):String {
		var s = sVal;
		var l = length(s);
		var j;
		var c;
		var r = "";
		for (var i = 1; i<255; i++) {
			c = chr(i);
			j = 0;
			while (j<=l && substring(s, 1+j++, 1) != c) {
			}
			if (j>l) {
				r += c;
			}
		}
		return s;
	}
	public function compile() {
		this.source = arguments[0];
		if (arguments.length>1) {
			var flags = (arguments[1]+'').toLowerCase();
			for (var i = 0; i<length(flags); i++) {
				if (substring(flags, i+1, 1) == "g") {
					this.global = true;
				}
				if (substring(flags, i+1, 1) == "i") {
					this.ignoreCase = true;
				}
				if (substring(flags, i+1, 1) == "m") {
					this.multiline = true;
				}
			}
		}
		if (arguments.length < 3) {
			var root = true;
			RegExp._xrStatic = 1;
			//Paren counter
			var i = 0;
		} else {
			var root = false;
			this._xr = RegExp._xrStatic++;
			var i = arguments[2];
		}
		this.lastIndex = 0;
		/*
									Compile the regular expression
									The array of character definition objects will be created:
									  q[n].t    -->  type of match required: 0  = exact
									                                         1  = in char set
														 2  = not in char set
														 3  = paren
														 4  = ref to paren
														 7  = new "OR" section
														 9  = beginning of line
														 10 = end of line
									  q[n].s    -->  character or character set
									  q[n].a    -->  character has to repeat at least a times
									  q[n].b    -->  character has to repeat at most b times
									*/
		var re = this.source;
		var ex;
		var l = length(re);
		var q = [];
		var qc = 0;
		var s;
		var range = false;
		var ca;
		var cb;
		var atEnd = false;
		var char;
		for (i=i; i<l; ++i) {
			var thischar = substring(re, i+1, 1);
			if (thischar == "\\") {
				i++;
				char = false;
				thischar = substring(re, i+1, 1);
			} else {
				char = true;
			}
			var nextchar = substring(re, i+2, 1);
			q[qc] = new Object();
			q[qc].t = 0;
			q[qc].a = 0;
			q[qc].b = 999;
			q[qc].c = -10;
			if (char) {
				// Handle special characters
				if (thischar == "(") {
					//Opening paren
					ex = new RegExp(re, (this.ignoreCase ? "gi" : "g"), i+1);
					i = RegExp._xiStatic;
					q[qc].t = 3;
					thischar = ex;
					nextchar = substring(re, i+2, 1);
				} else if (!root && thischar == ")") {
					//Closing paren
					break;
				} else if (thischar == "^") {
					//Must be located at the beginning of string/line
					if (qc == 0 || q[qc-1].t == 7) {
						q[qc].t = 9;
						q[qc].a = 1;
						q[qc].b = 1;
						qc++;
					}
					continue;
				} else if (thischar == "$") {
					//Must be located at the end of string/line
					if (root) {
						atEnd = true;
					}
					continue;
				} else if (thischar == "[") {
					//This is a character set
					i++;
					if (nextchar == "^") {
						q[qc].t = 2;
						i++;
					} else {
						q[qc].t = 1;
					}
					thischar = "";
					range = false;
					while (i<l && (s=substring(re, 1+i++, 1)) != "]") {
						if (range) {
							//Previous char was "-", so create a range
							if (s == "\\") {
							}
							cb = s == "\\" ? (s == "b" ? chr(8) : substring(re, 1+i++, 1)) : s;
							ca = ord(substring(thischar, length(thischar), 1))+1;
							while (cb>=(s=chr(ca++))) {
								thischar += s;
							}
							range = false;
						} else {
							if (s == "-" && length(thischar)>0) {
								//Character range is being defined
								range = true;
							} else {
								if (s == "\\") {
									//Predefined char set may follow
									s = substring(re, 1+i++, 1);
									if (s == "d") {
										thischar += "0123456789";
									} else if (s == "D") {
										thischar += invStr("0123456789");
									} else if (s == "s") {
										thischar += " \f\n\r\t\v";
									} else if (s == "S") {
										thischar += invStr(" \f\n\r\t\v");
									} else if (s == "w") {
										thischar += "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_";
									} else if (s == "W") {
										thischar += invStr("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_");
									} else if (s == "b") {
										thischar += chr(8);
									} else if (s == "\\") {
										thischar += s;
									}
								} else {
									thischar += s;
								}
							}
						}
					}
					if (range) thischar += "-";
					i--;
					var nextchar = substring(re, i+2, 1);
				} else if (thischar == "|") {
					//OR section
					if (atEnd) {
						q[qc].t = 10;
						q[qc].a = 1;
						q[qc].b = 1;
						qc++;
						q[qc] = new Object();
						atEnd = false;
					}
					q[qc].t = 7;
					q[qc].a = 1;
					q[qc].b = 1;
					qc++;
					continue;
				} else if (thischar == ".") {
					q[qc].t = 2;
					thischar = "\n";
				} else if (thischar == "*" || thischar == "?" || thischar == "+") {
					continue;
				}
			} else {
				if (thischar>="1" && thischar<="9") {
					q[qc].t = 4;
				} else if (thischar == "b") {
					q[qc].t = 1;
					thischar = "--wb--";
				} else if (thischar == "B") {
					q[qc].t = 2;
					thischar = "--wb--";
				} else if (thischar == "d") {
					q[qc].t = 1;
					thischar = "0123456789";
				} else if (thischar == "D") {
					q[qc].t = 2;
					thischar = "0123456789";
				} else if (thischar == "s") {
					q[qc].t = 1;
					thischar = " \f\n\r\t\v";
				} else if (thischar == "S") {
					q[qc].t = 2;
					thischar = " \f\n\r\t\v";
				} else if (thischar == "w") {
					q[qc].t = 1;
					thischar = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_";
				} else if (thischar == "W") {
					q[qc].t = 2;
					thischar = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_";
				}
			}
			//Counting metacharacters
			if (nextchar == "*") {
				q[qc].s = thischar;
				qc++;
				i++;
			} else if (nextchar == "?") {
				q[qc].s = thischar;
				q[qc].b = 1;
				qc++;
				i++;
			} else if (nextchar == "+") {
				q[qc].s = thischar;
				q[qc].a = 1;
				qc++;
				i++;
			} else if (nextchar == "{") {
				var comma = false;
				var rangeA = 0;
				range = "";
				i++;
				while (i+1<l && (s=substring(re, 2+i++, 1)) != "}") {
					if (!comma && s == ",") {
						comma = true;
						rangeA = Number(range);
						rangeA = Math.floor(isNaN(rangeA) ? 0 : rangeA);
						if (rangeA<0) {
							rangeA = 0;
						}
						range = "";
					} else {
						range += s;
					}
				}
				var rangeB = Number(range);
				rangeB = Math.floor(isNaN(rangeB) ? 0 : rangeB);
				if (rangeB<1) {
					rangeB = 999;
				}
				if (rangeB<rangeA) {
					rangeB = rangeA;
				}
				q[qc].s = thischar;
				q[qc].b = rangeB;
				q[qc].a = comma ? rangeA : rangeB;
				qc++;
			} else {
				q[qc].s = thischar;
				q[qc].a = 1;
				q[qc].b = 1;
				qc++;
			}
		}
		if (root && atEnd) {
			q[qc] = new Object();
			q[qc].t = 10;
			q[qc].a = 1;
			q[qc].b = 1;
			qc++;
		}
		if (!root) {
			RegExp._xiStatic = i;
			this.source = substring(re, arguments[2]+1, i-arguments[2]);
		}
		if (RegExp.d) {
			for (var i = 0; i<qc; i++) {
				trace("xr"+this._xr+' '+q[i].t+" : "+q[i].a+" : "+q[i].b+" : "+q[i].s);
			}
		}
		this._xq = q;
		this._xqc = qc;
		RegExp._xp = 0;
	}
	public function test() {
		if (RegExp._xp++ == 0) {
			RegExp._xxa = [];
			//Temp array for storing paren matches
			RegExp._xxlp = 0;
			//Last paren
		}
		//  q[n].c  -->  count of matches
		//  q[n].i  -->  index within the string
		var str = arguments[0]+'';
		var re;
		var q = this._xq;
		var qc = this._xqc;
		var qb;
		var c;
		var cl;
		var ct;
		var s;
		var l = length(str);
		var ix = this.global ? this.lastIndex : 0;
		var ix_ = ix;
		var str_ = str;
		if (this.ignoreCase) {
			str = str.toLowerCase();
		}
		var r = new Object();
		r.i = -1;
		var i = -1;
		while (i<qc-1) {
			i++;
			if (RegExp.d) {
				trace("New section started at i="+i);
			}
			ix = ix_;
			qb = i;
			q[qb].c = -10;
			var atEnd = false;
			while (i>qb || ix<l+1) {
				if (q[i].t == 7) {
					//New "OR" section coming
					break;
				} else if (q[i].t == 9) {
					i++;
					if (i == qb+1) {
						var atStart = true;
						qb = i;
					}
					q[qb].c = -10;
					continue;
				} else {
					if (r.i>=0 && ix>=r.i) {
						//There is already better match, so quit searching
						break;
					}
					if (q[i].c == -10) {
						if (RegExp.d) {
							trace("Lookup #"+i+" at index "+ix+" for \\\\\\\\\\\\\\\\'"+q[i].s+"\\\\\\\\\\\\\\\\' type "+q[i].t);
						}
						//Count the # of matches
						var m = 0;
						q[i].i = ix;
						if (q[i].t == 0) {
							//Exact match
							c = this.ignoreCase ? q[i].s.toLowerCase() : q[i].s;
							while (m<q[i].b && ix<l) {
								if (substring(str, 1+ix, 1) == c) {
									m++;
									ix++;
								} else {
									break;
								}
							}
						} else if (q[i].t == 1) {
							//In char set
							if (q[i].s == "--wb--") {
								q[i].a = 1;
								if (ix>0 && ix<l) {
									ct = substring(str, ix, 1);
									if (ct == " " || ct == "\\\\\\\\\\\\\\\\n") {
										m = 1;
									}
									if (m == 0) {
										ct = substring(str, 1+ix, 1);
										if (ct == " " || ct == "\\\\\\\\\\\\\\\\n") {
											m = 1;
										}
									}
								} else {
									m = 1;
								}
							} else {
								c = this.ignoreCase ? q[i].s.toLowerCase() : q[i].s;
								cl = length(c);
								var cs;
								while (m<q[i].b && ix<l) {
									ct = substring(str, 1+ix, 1);
									cs = 0;
									while (cs<=cl && substring(c, 1+cs++, 1) != ct) {
									}
									if (cs<=cl) {
										m++;
										ix++;
									} else {
										break;
									}
								}
							}
						} else if (q[i].t == 2) {
							//Not in char set
							c = this.ignoreCase ? q[i].s.toLowerCase() : q[i].s;
							cl = length(c);
							if (q[i].s == "--wb--") {
								q[i].a = 1;
								if (ix>0 && ix<l) {
									ct = substring(str, ix, 1);
									s = substring(str, 1+ix, 1);
									if (ct != " " && ct != "\\\\\\\\\\\\\\\\n" && s != " " && s != "\\\\\\\\\\\\\\\\n") {
										m = 1;
									}
								} else {
									m = 0;
								}
							} else {
								while (m<q[i].b && ix<l) {
									ct = substring(str, 1+ix, 1);
									cs = 0;
									while (cs<=cl && substring(c, 1+cs++, 1) != ct) {
									}
									if (cs<=cl) {
										break;
									} else {
										m++;
										ix++;
									}
								}
							}
						} else if (q[i].t == 10) {
							//End of string/line must be next
							s = substring(str, 1+ix, 1);
							m = (this.multiline && (s == "\\\\\\\\\\\\\\\\n" || s == "\\\\\\\\\\\\\\\\r")) || ix == l ? 1 : 0;
						} else if (q[i].t == 3) {
							//Regular expression in parens
							re = q[i].s;
							q[i].ix = [];
							q[i].ix[m] = ix;
							//Save index if need to retreat
							re.lastIndex = ix;
							while (m<q[i].b && re.test(str_)) {
								cl = length(RegExp._xxlm);
								if (cl>0) {
									ix += cl;
									m++;
									q[i].ix[m] = ix;
								} else {
									m = q[i].a;
									q[i].ix[m-1] = ix;
									break;
								}
							}
							if (m == 0) {
								RegExp._xxlm = "";
							}
							if (re._xr>RegExp._xxlp) {
								RegExp._xxlp = re._xr;
							}
							RegExp._xxa[Number(re._xr)] = RegExp._xxlm;
						} else if (q[i].t == 4) {
							//Back reference to paren
							if (RegExp._xp>=(c=Number(q[i].s))) {
								c = RegExp._xxa[c];
								c = this.ignoreCase ? c.toLowerCase() : c;
								cl = length(c);
								q[i].ix = [];
								q[i].ix[m] = ix;
								if (cl>0) {
									while (m<q[i].b && ix<l) {
										if (substring(str, 1+ix, cl) == c) {
											m++;
											ix += cl;
											q[i].ix[m] = ix;
										} else {
											break;
										}
									}
								} else {
									m = 0;
									q[i].a = 0;
								}
							} else {
								//Paren is not ready, treat number as charcode
								c = chr(c);
								q[i].ix = [];
								q[i].ix[m] = ix;
								while (m<q[i].b && ix<l) {
									if (substring(str, 1+ix, 1) == c) {
										m++;
										ix++;
										q[i].ix[m] = ix;
									} else {
										break;
									}
								}
							}
						}
						q[i].c = m;
						if (RegExp.d) {
							trace("   "+m+" matches found");
						}
					}
					if (q[i].c<q[i].a) {
						if (RegExp.d) {
							trace("   not enough matches");
						}
						//Not enough matches
						if (i>qb) {
							//Retreat back and decrease # of assumed matches
							i--;
							q[i].c--;
							if (q[i].c>=0) {
								ix = (q[i].t == 3 || q[i].t == 4) ? q[i].ix[q[i].c] : (q[i].i+q[i].c);
							}
							if (RegExp.d) {
								trace("Retreat to #"+i+" c="+q[i].c+" index="+ix);
							}
						} else {
							if (RegExp._xp>1) {
								//If this is a paren, failing to find first match is fatal
								break;
							}
							if (atStart) {
								//Match must be at the start of string/line
								if (this.multiline) {
									//Jump to the beginning of the next line
									while (ix<=l) {
										s = substring(str, 1+ix++, 1);
										if (s == "\\\\\\\\\\\\\\\\n" || s == "\\\\\\\\\\\\\\\\r") {
											break;
										}
									}
									q[i].c = -10;
								} else {
									//No match
									break;
								}
							} else {
								//Start a new search from next position
								ix++;
								q[i].c = -10;
							}
						}
					} else {
						if (RegExp.d) {
							trace("   enough matches!");
						}
						//# of matches ok, proceed to next
						i++;
						if (i == qc || q[i].t == 7) {
							if (RegExp.d) {
								trace("Saving better result: r.i = q["+qb+"].i = "+q[qb].i);
							}
							r.i = q[qb].i;
							r.li = ix;
							break;
						} else {
							q[i].c = -10;
						}
					}
				}
			}
			while (i<qc && q[i].t != 7) {
				i++;
			}
			//Jump to the next "OR" section
		}
		if (r.i<0) {
			this.lastIndex = 0;
			if (RegExp._xp-- == 1) {
				RegExp._xxa = [];
				RegExp._xxlp = 0;
			}
			return false;
		} else {
			ix = r.li;
			this._xi = r.i;
			RegExp._xxlm = substring(str_, r.i+1, ix-r.i);
			RegExp._xxlc = substring(str_, 1, r.i);
			RegExp._xxrc = substring(str_, ix+1, l-ix);
			if (ix == r.i) {
				ix++;
			}
			this.lastIndex = ix;
			if (RegExp._xp-- == 1) {
				RegExp.lastMatch = RegExp._xxlm;
				RegExp.leftContext = RegExp._xxlc;
				RegExp.rightContext = RegExp._xxrc;
				RegExp._xaStatic = RegExp._xxa;
				RegExp.lastParen = RegExp._xxa[Number(RegExp._xxlp)];
				for (i=1; i<10; i++) {
					RegExp["$"+i] = RegExp._xaStatic[Number(i)];
				}
			}
			return true;
		}
	}
	public function exec() {
		var str = arguments[0]+'';
		if (str == '') {
			return false;
		}
		var t = this.test(str);
		if (t) {
			var ra = new Array();
			ra.index = this._xi;
			ra.input = str;
			ra[0] = RegExp.lastMatch;
			var l = RegExp._xaStatic.length;
			for (var i = 1; i<l; i++) {
				ra[i] = RegExp._xaStatic[Number(i)];
			}
		} else {
			var ra = null;
		}
		return ra;
	}
	public static function setStringMethods() {
        if(String.prototype.match != undefined) {
          return;
        }
		String.prototype.match = function() {
			if (typeof (arguments[0]) != "object") {
				return null;
			}
			if (arguments[0].const != "RegExp") {
				return null;
			}
			var re = arguments[0];
			var s = this.valueOf();
			var ip = 0;
			var rc = 0;
			if (re.global) {
				re.lastIndex = 0;
				while (re.test(s)) {
					if (rc == 0) {
						var ra = new Array();
					}
					ra[rc++] = RegExp.lastMatch;
					ip = re.lastIndex;
				}
				re.lastIndex = ip;
			} else {
				var ra = re.exec(s);
				rc++;
			}
			return (rc == 0) ? null : ra;
		};
		String.prototype.replace = function() {
			if (typeof (arguments[0]) != "object") {
				return null;
			}
			if (arguments[0].const != "RegExp") {
				return null;
			}
			var re = arguments[0];
			var rs = arguments[1]+'';
			var s = this;
			var r = "";
			re.lastIndex = 0;
			if (re.global) {
				var ip = 0;
				var ix = 0;
				while (re.test(s)) {
					//Replace backreferences in rs
					var i = 0;
					var l = length(rs);
					var c = "";
					var pc = "";
					var nrs = "";
					while (i<l) {
						c = substring(rs, 1+i++, 1);
						if (c == "$" && pc != "\\") {
							c = substring(rs, 1+i++, 1);
							if (isNaN(Number(c)) || Number(c)>9) {
								nrs += "$"+c;
							} else {
								nrs += RegExp._xaStatic[Number(c)];
							}
						} else {
							nrs += c;
						}
						pc = c;
					}
					r += substring(s, ix+1, re._xi-ix)+nrs;
					ix = re._xi+length(RegExp.lastMatch);
					ip = re.lastIndex;
				}
				re.lastIndex = ip;
			} else {
				if (re.test(s)) {
					r += RegExp.leftContext+rs;
				}
			}
			r += re.lastIndex == 0 ? s : RegExp.rightContext;
			return r;
		};
		String.prototype.search = function() {
			if (typeof (arguments[0]) != "object") {
				return null;
			}
			if (arguments[0].const != "RegExp") {
				return null;
			}
			var re = arguments[0];
			var s = this;
			re.lastIndex = 0;
			var t = re.test(s);
			return t ? re._xi : -1;
		};
		String.prototype.old_split = String.prototype.split;
		String.prototype.split = function() {
			if (typeof (arguments[0]) == "object" && arguments[0].const == "RegExp") {
				var re = arguments[0];
				var lm = arguments[1] == null ? 9999 : Number(arguments[1]);
				if (isNaN(lm)) {
					lm = 9999;
				}
				var s = this;
				var ra = new Array();
				var rc = 0;
				var gs = re.global;
				re.global = true;
				re.lastIndex = 0;
				var ip = 0;
				var ipp = 0;
				var ix = 0;
				while (rc<lm && re.test(s)) {
//trace(re._xi + " " + ix + " " + RegExp.lastMatch);
					if (re._xi != ix) {
						ra[rc++] = substring(s, ix+1, re._xi-ix);
					}
					ix = re._xi+length(RegExp.lastMatch);
					ipp = ip;
					ip = re.lastIndex;
				}
				if (rc == lm) {
					re.lastIndex = ipp;
				} else {
					re.lastIndex = ip;
				}
				if (rc == 0) {
					ra[rc] = s;
				} else {
					if (rc<lm && length(RegExp.rightContext)>0) {
						ra[rc++] = RegExp.rightContext;
					}
				}
				re.global = gs;
				return ra;
			} else {
				return this.old_split(arguments[0], arguments[1]);
			}
		};
        return true;
	}
}
