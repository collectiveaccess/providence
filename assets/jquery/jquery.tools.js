/**
 * @license 
 * jQuery Tools @VERSION / Expose - Dim the lights
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/toolbox/expose.html
 *
 * Since: Mar 2010
 * Date: @DATE 
 */
(function($) {         

        // static constructs
        $.tools = $.tools || {version: '@VERSION'};
        
        var tool;
        
        tool = $.tools.expose = {
                
                conf: {        
                        maskId: 'exposeMask',
                        loadSpeed: 'slow',
                        closeSpeed: 'fast',
                        closeOnClick: true,
                        closeOnEsc: true,
                        
                        // css settings
                        zIndex: 9998,
                        opacity: 0.8,
                        startOpacity: 0,
                        color: '#fff',
                        
                        // callbacks
                        onLoad: null,
                        onClose: null
                }
        };

        /* one of the greatest headaches in the tool. finally made it */
        function viewport() {
                                
                // the horror case
               //  if ($.browser.msie) {
//                         
//                         // if there are no scrollbars then use window.height
//                         var d = $(document).height(), w = $(window).height();
//                         
//                         return [
//                                 window.innerWidth ||                                                         // ie7+
//                                 document.documentElement.clientWidth ||         // ie6  
//                                 document.body.clientWidth,                                         // ie6 quirks mode
//                                 d - w < 20 ? w : d
//                         ];
//                 } 
                
                // other well behaving browsers
                return [$(document).width(), $(document).height()]; 
        } 
        
        function call(fn) {
                if (fn) { return fn.call($.mask); }
        }
        
        var mask, exposed, loaded, config, overlayIndex;                
        
        
        $.mask = {
                
                load: function(conf, els) {
                        
                        // already loaded ?
                        if (loaded) { return this; }                        
                        
                        // configuration
                        if (typeof conf == 'string') {
                                conf = {color: conf};        
                        }
                        
                        // use latest config
                        conf = conf || config;
                        
                        config = conf = $.extend($.extend({}, tool.conf), conf);

                        // get the mask
                        mask = $("#" + conf.maskId);
                                
                        // or create it
                        if (!mask.length) {
                                mask = $('<div/>').attr("id", conf.maskId);
                                $("body").append(mask);
                        }
                        
                        // set position and dimensions                         
                        var size = viewport();
                                
                        mask.css({                                
                                position:'absolute', 
                                top: 0, 
                                left: 0,
                                width: size[0],
                                height: size[1],
                                display: 'none',
                                opacity: conf.startOpacity,                                                         
                                zIndex: conf.zIndex 
                        });
                        
                        if (conf.color) {
                                mask.css("backgroundColor", conf.color);        
                        }                        
                        
                        // onBeforeLoad
                        if (call(conf.onBeforeLoad) === false) {
                                return this;
                        }
                        
                        // esc button
                        if (conf.closeOnEsc) {                                                
                                $(document).on("keydown.mask", function(e) {                                                        
                                        if (e.keyCode == 27) {
                                                $.mask.close(e);        
                                        }                
                                });                        
                        }
                        
                        // mask click closes
                        if (conf.closeOnClick) {
                                mask.on("click.mask", function(e)  {
                                        $.mask.close(e);                
                                });                                        
                        }                        
                        
                        // resize mask when window is resized
                        $(window).on("resize.mask", function() {
                                $.mask.fit();
                        });
                        
                        // exposed elements
                        if (els && els.length) {
                                
                                overlayIndex = els.eq(0).css("zIndex");

                                // make sure element is positioned absolutely or relatively
                                $.each(els, function() {
                                        var el = $(this);
                                        if (!/relative|absolute|fixed/i.test(el.css("position"))) {
                                                el.css("position", "relative");                
                                        }                                        
                                });
                         
                                // make elements sit on top of the mask
                                exposed = els.css({ zIndex: Math.max(conf.zIndex + 1, overlayIndex == 'auto' ? 0 : overlayIndex)});                        
                        }        
                        
                        // reveal mask
                        mask.css({display: 'block'}).fadeTo(conf.loadSpeed, conf.opacity, function() {
                                $.mask.fit(); 
                                call(conf.onLoad);
                                loaded = "full";
                        });
                        
                        loaded = true;                        
                        return this;                                
                },
                
                close: function() {
                        if (loaded) {
                                
                                // onBeforeClose
                                if (call(config.onBeforeClose) === false) { return this; }
                                        
                                mask.fadeOut(config.closeSpeed, function()  {                                        
                                        call(config.onClose);                                        
                                        if (exposed) {
                                                exposed.css({zIndex: overlayIndex});                                                
                                        }                                
                                        loaded = false;
                                });                                
                                
                                // unbind various event listeners
                                $(document).off("keydown.mask");
                                mask.off("click.mask");
                                $(window).off("resize.mask");  
                        }
                        
                        return this; 
                },
                
                fit: function() {
                        if (loaded) {
                                var size = viewport();                                
                                mask.css({width: size[0], height: size[1]});
                        }                                
                },
                
                getMask: function() {
                        return mask;        
                },
                
                isLoaded: function(fully) {
                        return fully ? loaded == 'full' : loaded;        
                }, 
                
                getConf: function() {
                        return config;        
                },
                
                getExposed: function() {
                        return exposed;        
                }                
        };
        
        $.fn.mask = function(conf) {
                $.mask.load(conf);
                return this;                
        };                        
        
        $.fn.expose = function(conf) {
                $.mask.load(conf, this);
                return this;                        
        };


})(jQuery);

/**
 * @license 
 * jQuery Tools @VERSION Scrollable - New wave UI design
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/scrollable.html
 *
 * Since: March 2008
 * Date: @DATE 
 */
(function($) { 

	// static constructs
	$.tools = $.tools || {version: '@VERSION'};
	
	$.tools.scrollable = {
		
		conf: {	
			activeClass: 'active',
			circular: false,
			clonedClass: 'cloned',
			disabledClass: 'disabled',
			easing: 'swing',
			initialIndex: 0,
			item: '> *',
			items: '.items',
			keyboard: true,
			mousewheel: false,
			next: '.next',   
			prev: '.prev', 
			size: 1,
			speed: 400,
			vertical: false,
			touch: true,
			wheelSpeed: 0
		} 
	};
					
	// get hidden element's width or height even though it's hidden
	function dim(el, key) {
		var v = parseInt(el.css(key), 10);
		if (v) { return v; }
		var s = el[0].currentStyle; 
		return s && s.width && parseInt(s.width, 10);	
	}

	function find(root, query) { 
		var el = $(query);
		return el.length < 2 ? el : root.parent().find(query);
	}
	
	var current;		
	
	// constructor
	function Scrollable(root, conf) {   
		
		// current instance
		var self = this, 
			 fire = root.add(self),
			 itemWrap = root.children(),
			 index = 0,
			 vertical = conf.vertical;
				
		if (!current) { current = self; } 
		if (itemWrap.length > 1) { itemWrap = $(conf.items, root); }
		
		
		// in this version circular not supported when size > 1
		if (conf.size > 1) { conf.circular = false; } 
		
		// methods
		$.extend(self, {
				
			getConf: function() {
				return conf;	
			},			
			
			getIndex: function() {
				return index;	
			}, 

			getSize: function() {
				return self.getItems().size();	
			},

			getNaviButtons: function() {
				return prev.add(next);	
			},
			
			getRoot: function() {
				return root;	
			},
			
			getItemWrap: function() {
				return itemWrap;	
			},
			
			getItems: function() {
				return itemWrap.find(conf.item).not("." + conf.clonedClass);	
			},
							
			move: function(offset, time) {
				return self.seekTo(index + offset, time);
			},
			
			next: function(time) {
				return self.move(conf.size, time);	
			},
			
			prev: function(time) {
				return self.move(-conf.size, time);	
			},
			
			begin: function(time) {
				return self.seekTo(0, time);	
			},
			
			end: function(time) {
				return self.seekTo(self.getSize() -1, time);	
			},	
			
			focus: function() {
				current = self;
				return self;
			},
			
			addItem: function(item) {
				item = $(item);
				
				if (!conf.circular)  {
					itemWrap.append(item);
					next.removeClass("disabled");
					
				} else {
					itemWrap.children().last().before(item);
					itemWrap.children().first().replaceWith(item.clone().addClass(conf.clonedClass)); 						
				}
				
				fire.trigger("onAddItem", [item]);
				return self;
			},
			
			
			/* all seeking functions depend on this */		
			seekTo: function(i, time, fn) {	
				
				// ensure numeric index
				if (!i.jquery) { i *= 1; }
				
				// avoid seeking from end clone to the beginning
				if (conf.circular && i === 0 && index == -1 && time !== 0) { return self; }
				
				// check that index is sane				
				if (!conf.circular && i < 0 || i > self.getSize() || i < -1) { return self; }
				
				var item = i;
			
				if (i.jquery) {
					i = self.getItems().index(i);	
					
				} else {
					item = self.getItems().eq(i);
				}  
				
				// onBeforeSeek
				var e = $.Event("onBeforeSeek"); 
				if (!fn) {
					fire.trigger(e, [i, time]);				
					if (e.isDefaultPrevented() || !item.length) { return self; }			
				}  
	
				var props = vertical ? {top: -item.position().top} : {left: -item.position().left};  
				
				index = i;
				current = self;  
				if (time === undefined) { time = conf.speed; }   
				
				itemWrap.animate(props, time, conf.easing, fn || function() { 
					fire.trigger("onSeek", [i]);		
				});	 
				
				return self; 
			}					
			
		});
				
		// callbacks	
		$.each(['onBeforeSeek', 'onSeek', 'onAddItem'], function(i, name) {
				
			// configuration
			if ($.isFunction(conf[name])) { 
				$(self).on(name, conf[name]); 
			}
			
			self[name] = function(fn) {
				if (fn) { $(self).on(name, fn); }
				return self;
			};
		});  
		
		// circular loop
		if (conf.circular) {
			
			var cloned1 = self.getItems().slice(-1).clone().prependTo(itemWrap),
				 cloned2 = self.getItems().eq(1).clone().appendTo(itemWrap);

			cloned1.add(cloned2).addClass(conf.clonedClass);
			
			self.onBeforeSeek(function(e, i, time) {
				
				if (e.isDefaultPrevented()) { return; }
				
				/*
					1. animate to the clone without event triggering
					2. seek to correct position with 0 speed
				*/
				if (i == -1) {
					self.seekTo(cloned1, time, function()  {
						self.end(0);		
					});          
					return e.preventDefault();
					
				} else if (i == self.getSize()) {
					self.seekTo(cloned2, time, function()  {
						self.begin(0);		
					});	
				}
				
			});

			// seek over the cloned item

			// if the scrollable is hidden the calculations for seekTo position
			// will be incorrect (eg, if the scrollable is inside an overlay).
			// ensure the elements are shown, calculate the correct position,
			// then re-hide the elements. This must be done synchronously to
			// prevent the hidden elements being shown to the user.

			// See: https://github.com/jquerytools/jquerytools/issues#issue/87

			var hidden_parents = root.parents().add(root).filter(function () {
				if ($(this).css('display') === 'none') {
					return true;
				}
			});
			if (hidden_parents.length) {
				hidden_parents.show();
				self.seekTo(0, 0, function() {});
				hidden_parents.hide();
			}
			else {
				self.seekTo(0, 0, function() {});
			}

		}
		
		// next/prev buttons
		var prev = find(root, conf.prev).click(function(e) { e.stopPropagation(); self.prev(); }),
			 next = find(root, conf.next).click(function(e) { e.stopPropagation(); self.next(); }); 
		
		if (!conf.circular) {
			self.onBeforeSeek(function(e, i) {
				setTimeout(function() {
					if (!e.isDefaultPrevented()) {
						prev.toggleClass(conf.disabledClass, i <= 0);
						next.toggleClass(conf.disabledClass, i >= self.getSize() -1);
					}
				}, 1);
			});
			
			if (!conf.initialIndex) {
				prev.addClass(conf.disabledClass);	
			}			
		}
			
		if (self.getSize() < 2) {
			prev.add(next).addClass(conf.disabledClass);	
		}
			
		// mousewheel support
		if (conf.mousewheel && $.fn.mousewheel) {
			root.mousewheel(function(e, delta)  {
				if (conf.mousewheel) {
					self.move(delta < 0 ? 1 : -1, conf.wheelSpeed || 50);
					return false;
				}
			});			
		}
		
		// touch event
		if (conf.touch) {
			var touch = {};
			
			itemWrap[0].ontouchstart = function(e) {
				var t = e.touches[0];
				touch.x = t.clientX;
				touch.y = t.clientY;
			};
			
			itemWrap[0].ontouchmove = function(e) {
				
				// only deal with one finger
				if (e.touches.length == 1 && !itemWrap.is(":animated")) {			
					var t = e.touches[0],
						 deltaX = touch.x - t.clientX,
						 deltaY = touch.y - t.clientY;
	
					self[vertical && deltaY > 0 || !vertical && deltaX > 0 ? 'next' : 'prev']();				
					e.preventDefault();
				}
			};
		}
		
		if (conf.keyboard)  {
			
			$(document).on("keydown.scrollable", function(evt) {

				// skip certain conditions
				if (!conf.keyboard || evt.altKey || evt.ctrlKey || evt.metaKey || $(evt.target).is(":input")) { 
					return; 
				}
				
				// does this instance have focus?
				if (conf.keyboard != 'static' && current != self) { return; }
					
				var key = evt.keyCode;
			
				if (vertical && (key == 38 || key == 40)) {
					self.move(key == 38 ? -1 : 1);
					return evt.preventDefault();
				}
				
				if (!vertical && (key == 37 || key == 39)) {					
					self.move(key == 37 ? -1 : 1);
					return evt.preventDefault();
				}	  
				
			});  
		}
		
		// initial index
		if (conf.initialIndex) {
			self.seekTo(conf.initialIndex, 0, function() {});
		}
	} 

		
	// jQuery plugin implementation
	$.fn.scrollable = function(conf) { 
			
		// already constructed --> return API
		var el = this.data("scrollable");
		if (el) { return el; }		 

		conf = $.extend({}, $.tools.scrollable.conf, conf); 
		
		this.each(function() {			
			el = new Scrollable($(this), conf);
			$(this).data("scrollable", el);	
		});
		
		return conf.api ? el: this; 
		
	};
			
	
})(jQuery);

/**
 * @license 
 * jQuery Tools @VERSION / Scrollable Autoscroll
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/scrollable/autoscroll.html
 *
 * Since: September 2009
 * Date: @DATE 
 */
(function($) {		

	var t = $.tools.scrollable; 
	
	t.autoscroll = {
		
		conf: {
			autoplay: true,
			interval: 3000,
			autopause: true
		}
	};	
	
	// jQuery plugin implementation
	$.fn.autoscroll = function(conf) { 

		if (typeof conf == 'number') {
			conf = {interval: conf};	
		}
		
		var opts = $.extend({}, t.autoscroll.conf, conf), ret;
		
		this.each(function() {		
				
			var api = $(this).data("scrollable"),
			    root = api.getRoot(),
			    // interval stuff
    			timer, stopped = false;

	    /**
      *
      *   Function to run autoscroll through event binding rather than setInterval
      *   Fixes this bug: http://flowplayer.org/tools/forum/25/72029
      */
      function scroll(){        
      	// Fixes https://github.com/jquerytools/jquerytools/issues/591
        if (timer) clearTimeout(timer); // reset timeout, especially for onSeek event
        timer = setTimeout(function(){
          api.next();
        }, opts.interval);
      }
			    
			if (api) { ret = api; }
			
			api.play = function() { 
				
				// do not start additional timer if already exists
				if (timer) { return; }
				
				stopped = false;
				root.on('onSeek', scroll);
				scroll();
			};	

			api.pause = function() {
				timer = clearTimeout(timer);  // clear any queued items immediately
				root.off('onSeek', scroll);
			};
			
			// resume playing if not stopped
			api.resume = function() {
				stopped || api.play();
			};
			
			// when stopped - mouseover won't restart 
			api.stop = function() {
			  stopped = true;
				api.pause();
			};
		
			/* when mouse enters, autoscroll stops */
			if (opts.autopause) {
				root.add(api.getNaviButtons()).hover(api.pause, api.resume);
			}
			
			if (opts.autoplay) {
				api.play();				
			}

		});
		
		return opts.api ? ret : this;
		
	}; 
	
})(jQuery);		

/**
 * @license 
 * jQuery Tools @VERSION / Scrollable Navigator
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 *
 * http://flowplayer.org/tools/scrollable/navigator.html
 *
 * Since: September 2009
 * Date: @DATE 
 */
(function($) {
		
	var t = $.tools.scrollable; 
	
	t.navigator = {
		
		conf: {
			navi: '.navi',
			naviItem: null,		
			activeClass: 'active',
			indexed: false,
			idPrefix: null,
			
			// 1.2
			history: false
		}
	};		
	
	function find(root, query) {
		var el = $(query);
		return el.length < 2 ? el : root.parent().find(query);
	}
	
	// jQuery plugin implementation
	$.fn.navigator = function(conf) {

		// configuration
		if (typeof conf == 'string') { conf = {navi: conf}; } 
		conf = $.extend({}, t.navigator.conf, conf);
		
		var ret;
		
		this.each(function() {
				
			var api = $(this).data("scrollable"),
				 navi = conf.navi.jquery ? conf.navi : find(api.getRoot(), conf.navi), 
				 buttons = api.getNaviButtons(),
				 cls = conf.activeClass,
				 hashed = conf.history && !!history.pushState,
				 size = api.getConf().size;
				 

			// @deprecated stuff
			if (api) { ret = api; }
			
			api.getNaviButtons = function() {
				return buttons.add(navi);	
			}; 
			
			
			if (hashed) {
				history.pushState({i: 0}, '');
				
				$(window).on("popstate", function(evt) {
					var s = evt.originalEvent.state;
					if (s) { api.seekTo(s.i); }
				});					
			}
			
			function doClick(el, i, e) {
				api.seekTo(i);
				e.preventDefault(); 
				if (hashed) { history.pushState({i: i}, ''); }
			}
			
			function els() {
				return navi.find(conf.naviItem || '> *');	
			}
			
			function addItem(i) {  
				
				var item = $("<" + (conf.naviItem || 'a') + "/>").click(function(e)  {
					doClick($(this), i, e);					
				});
				
				// index number / id attribute
				if (i === 0) {  item.addClass(cls); }
				if (conf.indexed)  { item.text(i + 1); }
				if (conf.idPrefix) { item.attr("id", conf.idPrefix + i); }
				
				return item.appendTo(navi);
			}
			
			
			// generate navigator
			if (els().length) {
				els().each(function(i) { 
					$(this).click(function(e)  {
						doClick($(this), i, e);		
					});
				});
				
			} else {				
				$.each(api.getItems(), function(i) {
					if (i % size == 0) addItem(i); 
				});
			}   
			
			// activate correct entry
			api.onBeforeSeek(function(e, index) {
				setTimeout(function() {
					if (!e.isDefaultPrevented()) {	
						var i = index / size,
							 el = els().eq(i);
							 
						if (el.length) { els().removeClass(cls).eq(i).addClass(cls); }
					}
				}, 1);
			}); 
			
			// new item being added
			api.onAddItem(function(e, item) {
				var i = api.getItems().index(item);
				if (i % size == 0) addItem(i);
			});
			
		});		
		
		return conf.api ? ret : this;
		
	};
	
})(jQuery);			