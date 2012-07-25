/* 

TileViewer HTML5 client

    Version: 2.0.0

    This plugin is tested with following dependencies
    * JQuery 1.3.2
    * Brandon Aaron's (http://brandonaaron.net) mousewheel jquery plugin 3.0.3

The MIT License

    Copyright (c) 2011 Soichi Hayashi (https://sites.google.com/site/soichih/)

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.

	*****************
	MODIFIED TO WORK WITH CollectiveAccess and Tilepic format February 2012 by SK
	*****************
*/

(function($){
var methods = {
    ///////////////////////////////////////////////////////////////////////////////////
    // Initializes if it's not already initialized
    init: function (options) {
		methods.tileCounts = undefined;
        var defaults = {
            src: null,
            empty: "#cccccc", //color of empty (loading) tile - if no subtile is available
            width: 400, //canvas width - not image width
            height: 300, //canvas height - not image height
            zoom_sensitivity: 16, 
            thumbnail: false,//display thumbnail
            magnifier: false,//display magnifier
            debug: false,
            pixel: true,
            magnifier_view_size: 196, //view size
            magnifier_view_area: 48, //pixel w/h sizes to zoom
            graber_size: 12, //size of the grabber area
            maximum_pixelsize: 4,//set this to >1 if you want to let user to zoom image after reaching its original resolution (also consider using magnifier..)
            thumb_depth: 2 //level depth when thumbnail should appear
        };

        return this.each(function() {
            var $this = $(this);
            options = $.extend(defaults, options);//override defaults with options
            $this.data("options", options);

            ///////////////////////////////////////////////////////////////////////////////////
            // Now we can start initializing
            //If the plugin hasn't been initialized yet..
            var view = $this.data("view");
            if(!view) {
                var layer = {
                    info:  null, 

                    //current view offset - not absolute pixel offset
                    xpos: 0,
                    ypos: 0,

                    //number of tiles on the current level
                    xtilenum: null,
                    ytilenum: null,

                    //current tile level/size (size is usually 128-256)
                    level: null, 
                    tilesize: null,

                    thumb: null, //thumbnail image
                    
                    loader: {
                        loading: 0, //actual number of images that are currently loaded
                        max_loading: 6, //max number of image that can be loaded simultaneously
                        tile_count: 0, //number of tiles in tile dictionary (not all of them are actually loaded)
                        max_tiles: 200 //max number of images that can be stored in tiles dictionary
                    },
                    tiles: [] //tiles dictionary 
                }; //layer definition
                $this.data("layer", layer);

                var view = {
                    canvas: document.createElement("canvas"),
                    status: document.createElement("p"),
                    controls: document.createElement("p"),
                    mode: null, //current mouse left button mode (pan, sel2d, sel1d, etc..)
                    pan: {
                        //pan destination
                        xdest: null,//(pixel pos)
                        ydest: null,//(pixel pos)
                        leveldest: null,
                    },
                    select: {
                        x: null,
                        y: null,
                        width: null,
                        height: null
                    },
                    magnifier_canvas: document.createElement("canvas"),
                    //current mouse position (client pos)
                    xnow: null,
                    ynow: null,
                    mousedown: false,
                    framerate: null,//current framerate (1000 msec / drawtime msec)
                    needdraw: false, //flag used to request for frameredraw 

                    ///////////////////////////////////////////////////////////////////////////////////
                    // internal functions
                    draw: function() {
                        view.needdraw = false;
                        if(layer.info == null) { return; }

                        var start = new Date().getTime();

                        var ctx = view.canvas.getContext("2d");
                        view.canvas.width = $this.width();//clear
                        view.canvas.height = $this.height();//clear

                        view.draw_tiles(ctx);

                        if(options.magnifier) {
                            view.draw_magnifier(ctx);
                        }

                        switch(view.mode) {
                        case "pan":
                            if(options.thumbnail) {
                                //only draw thumbnail if we are zoomed in far enough
                                //if(layer.info._maxlevel - layer.level > options.thumb_depth) {
                                    view.draw_thumb(ctx);
                                //}
                            }
                            break;
                        case "select_1d":
                            view.draw_select_1d(ctx);
                            break;
                        case "select_2d":
                            view.draw_select_2d(ctx);
                            break;
                        }

                        //calculate framerate
                        var end = new Date().getTime();
                        var time = end - start;
                        view.framerate = Math.round(1000/time);

                        view.update_status();
                        view.update_controls();
                    },

                    //TODO - let user override this
                    update_status: function() {
                        if(options.debug) {
                            var pixel_pos = view.client2pixel(view.xnow, view.ynow);
                            $(view.status).html(
                                "width: " + layer.info.width + 
                                "<br/>height: " + layer.info.height + 
                                "<br/>level:" + Math.round((layer.level + layer.info.tilesize/layer.tilesize-1)*100)/100 + 
                                    " (tsize:"+Math.round(layer.tilesize*100)/100+")"+
                                "<br/>framerate: " + view.framerate + 
                                "<br/>images loading: " + layer.loader.loading + 
                                "<br/>tiles in dict: " + layer.loader.tile_count + 
                                "<br/>x:" + pixel_pos.x + 
                                "<br/>y:" + pixel_pos.y  
                            );
                        } else {
                            $(view.status).empty();
                        }
                    },
                    
                    update_controls: function() {
                        if (!$(view.controls).html()) {
							$(view.controls).append("<a href='#' id='" + options.id + "ControlZoomIn' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/zoom_in.png'/></a>");
							$(view.controls).append("<a href='#' id='" + options.id + "ControlZoomOut' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/zoom_out.png'/></a>");
							$(view.controls).append("<a href='#' id='" + options.id + "ControlOverview' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/overview.png'/></a>");
							$(view.controls).append("<a href='#' id='" + options.id + "ControlMagnify' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/magnify.png'/></a>");
							
							jQuery("#" + options.id + "ControlOverview").click(function() {
								options.thumbnail = !options.thumbnail;
								view.draw();
								jQuery(this).css("opacity", options.thumbnail ? 1.0 : 0.5);
							});
							
							jQuery("#" + options.id + "ControlMagnify").click(function() {
								options.magnifier = !options.magnifier;
								view.draw();
								jQuery(this).css("opacity", options.magnifier ? 1.0 : 0.5);
							});
							
							jQuery("#" + options.id + "ControlZoomIn").mousedown(function() {
								view.mousedown = true;
								
								var w = jQuery(view.canvas).width();
								var h = jQuery(view.canvas).height();
								
								jQuery('#' + options.id + "ControlZoomIn").css("opacity", 1.0);
								jQuery('#' + options.id + "ControlZoomOut").css("opacity", 0.5);
								
								view.interval = setInterval(function() {
									if (!view.mousedown) { 
										clearInterval(view.interval);
										
										jQuery('#' + options.id + "ControlZoomIn").css("opacity", 0.5);
										jQuery('#' + options.id + "ControlZoomOut").css("opacity", 0.5);
									}
									view.change_zoom(20, w/2, h/2);
								}, 50);
							});
							
							jQuery("#" + options.id + "ControlZoomOut").mousedown(function() {
								view.mousedown = true;
								
								var w = jQuery(view.canvas).width();
								var h = jQuery(view.canvas).height();
								
								jQuery('#' + options.id + "ControlZoomIn").css("opacity", 0.5);
								jQuery('#' + options.id + "ControlZoomOut").css("opacity", 1.0);
								
								view.interval = setInterval(function() {
									if (!view.mousedown) { 
										clearInterval(view.interval);
																				
										jQuery('#' + options.id + "ControlZoomIn").css("opacity", 0.5);
										jQuery('#' + options.id + "ControlZoomOut").css("opacity", 0.5);
									}
									view.change_zoom(-20, w/2, h/2);
								}, 50);
							});
							
							jQuery(document).bind('keypress', '] +', function() { 	// zoom in using keyboard "]" or "+"						
								var w = jQuery(view.canvas).width();
								var h = jQuery(view.canvas).height();
								view.change_zoom(15, w/2, h/2); 
							});
							jQuery(document).bind('keypress', '[ -', function() { 	// zoom out using keyboard "[" or "-"						
								var w = jQuery(view.canvas).width();
								var h = jQuery(view.canvas).height();
								view.change_zoom(-15, w/2, h/2); 
							});
							
							jQuery(document).bind('keydown', 'left', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x - 50;
								view.pan.ydest = p.y;
								view.pan.level = layer.level;
								view.pan(); 
							});
							jQuery(document).bind('keydown', 'right', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x + 50;
								view.pan.ydest = p.y;
								view.pan.level = layer.level;
								view.pan(); 
							});
							jQuery(document).bind('keydown', 'up', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x;
								view.pan.ydest = p.y - 50;
								view.pan.level = layer.level;
								view.pan(); 
							});
							
							jQuery(document).bind('keydown', 'c', function() { // show/hide controls	
								jQuery(view.controls).fadeToggle(100);
							});
							
							jQuery(document).bind('keydown', 'down', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x;
								view.pan.ydest = p.y + 50;
								view.pan.level = layer.level;
								view.pan(); 
							});
						}
                    },

                    draw_tiles: function(ctx) {
                        //display tiles
                        var xmin = Math.max(0, Math.floor(-layer.xpos/layer.tilesize));
                        var ymin = Math.max(0, Math.floor(-layer.ypos/layer.tilesize));
                        var xmax = Math.min(layer.xtilenum, Math.ceil((view.canvas.clientWidth-layer.xpos)/layer.tilesize));
                        var ymax = Math.min(layer.ytilenum, Math.ceil((view.canvas.clientHeight-layer.ypos)/layer.tilesize));
                        for(var y = ymin; y < ymax; y++) {
                            for(var x  = xmin; x < xmax; x++) {
                                view.draw_tile(ctx,x,y);
                            }
                        }
                    },

                    draw_thumb: function(ctx) {
                      
                        //set shadow
                        ctx.shadowOffsetX = 3;
                        ctx.shadowOffsetY = 3;
                        ctx.shadowBlur    = 4;
                        ctx.shadowColor   = 'rgba(0,0,0,1)';
                     

                        //draw thumbnail image
                        ctx.drawImage(layer.thumb, 1, 1, layer.thumb.width, layer.thumb.height);

                        //draw current view
                        var rect = view.get_viewpos();
                        var factor = layer.thumb.height/layer.info.height;
                        ctx.strokeStyle = '#ff0000'; 
                        ctx.lineWidth   = 1;
                        
                        var x = rect.x*factor;
                        var y = rect.y*factor;
                        var w = rect.width*factor;
                        var h = rect.height*factor;
                        
                        // Don't let highlight rect extend past thumbnail 'cos that'd be ugly
                        if (x < 0) { w = w + x; x = 0; }
                        if (y < 0) { h = h + y; y = 0; }
                        if ((x + w) > layer.thumb.width) { w = layer.thumb.width - x; }
                        if ((y + h) > layer.thumb.height) { h = layer.thumb.height - y; }
                        
                        ctx.strokeRect(x, y, w, h);
                    },

                    draw_tile: function(ctx,x,y) {
                        var tileid = x + y*layer.xtilenum;
                        var url = options.src + methods.getTilepicTileNum(layer.level, tileid, layer);
                        var img = layer.tiles[url];

                        var dodraw = function() {
                            var xsize = layer.tilesize;
                            var ysize = layer.tilesize;
                            if(x == layer.xtilenum-1) {
                                xsize = (layer.tilesize/layer.info.tilesize)*layer.tilesize_xlast;
                            }
                            if(y == layer.ytilenum-1) {
                                ysize = (layer.tilesize/layer.info.tilesize)*layer.tilesize_ylast;
                            }
							ctx.drawImage(img, Math.floor(layer.xpos+x*layer.tilesize), Math.floor(layer.ypos+y*layer.tilesize),    
								Math.ceil(xsize),Math.ceil(ysize));
                          
                        }

                        if(img == null) {
                            view.loader_request(url);
                        } else {
                            if(img.loaded) {
                                img.timestamp = new Date().getTime();
                                dodraw(); //good.. we have the image.. dodraw
                                return;
                            } else {
                                //not loaded yet ... update timestamp so that this image will get loaded soon
                                img.timestamp = new Date().getTime();
                            }
                        }

                        //meanwhile .... draw subtile instead
                        var xsize = layer.tilesize;
                        var ysize = layer.tilesize;
                        if(x == layer.xtilenum-1) {
                            xsize = (layer.tilesize/layer.info.tilesize)*layer.tilesize_xlast;
                        }
                        if(y == layer.ytilenum-1) {
                            ysize = (layer.tilesize/layer.info.tilesize)*layer.tilesize_ylast;
                        }
                        //look for available subtile of the highest quaility
                        var down = 1;
                        var factor = 1;
                        while(layer.level+down <= layer.info._maxlevel) {
                            factor <<=1;
                            var xtilenum_up = Math.ceil(layer.info.width/Math.pow(2,layer.level+down)/layer.info.tilesize);
                            var subtileid = Math.floor(x/factor) + Math.floor(y/factor)*xtilenum_up;
                        	var url = options.src + methods.getTilepicTileNum(layer.level+down, subtileid, layer);
                            var img = layer.tiles[url];
                            if(img && img.loaded) {
                                //crop the source section
                                var half_tilesize = layer.info.tilesize/factor;
                                var sx = (x%factor)*half_tilesize;
                                var sy = (y%factor)*half_tilesize;
                                var sw = half_tilesize;
                                if(x == layer.xtilenum-1) sw = layer.tilesize_xlast/factor;
                                var sh = half_tilesize;
                                if(y == layer.ytilenum-1) sh = layer.tilesize_ylast/factor;
                                
								ctx.drawImage(img, sx, sy, sw, sh, 
									Math.floor(layer.xpos+x*layer.tilesize), Math.floor(layer.ypos+y*layer.tilesize), 
									Math.ceil(xsize),Math.ceil(ysize));
									
                                img.timestamp = new Date().getTime();//we should keep this image.. 
                                return;
                            }
                            //try another level
                            down++;
                        }

/* let's not do anything 
                        //nosubtile available.. draw empty rectangle as the last resort
                        ctx.fillStyle = options.empty;
                        ctx.fillRect(layer.xpos+x*layer.tilesize, layer.ypos+y*layer.tilesize, xsize, ysize);
*/
                    },

                    loader_request: function(url) {
                        var img = new Image();
                        img.loaded = false;
                        img.loading = false;
                        img.level_loaded_for = layer.level;
                        img.request_src = url;
                        img.timestamp = new Date().getTime();
                        img.onload = function() {
                            this.loaded = true;
                            this.loading = false;
                            if(this.level_loaded_for == layer.level) {
                                view.needdraw = true;
                            }
                            layer.loader.loading--;
                            view.loader_load(null);
                        };
                        layer.tiles[url] = img;
                        layer.loader.tile_count++;
                        view.loader_load(img);
                        view.loader_shift();
                    },
                    loader_load: function(img) {
                        //if we can load more image, load it
                        if(layer.loader.loading < layer.loader.max_loading) {
                            if(img == null) {
                                //find the latest image to load (unless specified)
                                var latest_img = null;
                                for (var url in layer.tiles) {
                                    img = layer.tiles[url];
                                    if(img.loaded == false && img.loading == false && (latest_img == null || img.timestamp > latest_img.timestamp)) {
                                        latest_img = img;
                                    }
                                }
                                img = latest_img;
                            }
                            if(img != null) {
                                //start loading!
                                img.src = img.request_src;
                                layer.loader.loading++;
                                img.loading = true;
                                view.loader_load(); //recurse to see if we can load more image
                            }
                        }
                    },
                    loader_shift: function() {
                        //if we have too many images in the dictionary... remove oldest used image
                        if(layer.loader.tile_count >= layer.loader.max_tiles) {
                            var oldest_img = null;
                            for (var url in layer.tiles) {
                                img = layer.tiles[url];
                                if(img.loaded == true && (oldest_img == null || img.timestamp < oldest_img.timestamp)) {
                                    oldest_img = img;
                                }
                            }
                            if(oldest_img != null) {
                                //get rid of this guy
                                delete layer.tiles[oldest_img.src];
                                layer.loader.tile_count--;
                            }
                        }
                    },

                    draw_magnifier:  function(ctx) {
                        //set shadow
                        ctx.shadowOffsetX = 3;
                        ctx.shadowOffsetY = 3;
                        ctx.shadowBlur    = 4;
                        ctx.shadowColor   = 'rgba(0,0,0,1)';
                     
                        //grab magnifier image
                        var mcontext = view.magnifier_canvas.getContext("2d");
                        var marea = ctx.getImageData(view.xnow-options.magnifier_view_area/2, view.ynow-options.magnifier_view_area/2, options.magnifier_view_area,options.magnifier_view_area);
                        mcontext.putImageData(marea, 0,0);//draw to canvas so that I can zoom it up

                        //display on the bottom left corner
                        ctx.drawImage(view.magnifier_canvas, 0, view.canvas.clientHeight-options.magnifier_view_size, options.magnifier_view_size, options.magnifier_view_size);

                    },

                    draw_select_1d: function(ctx) {

        /*
                        ctx.shadowOffsetX = 1;
                        ctx.shadowOffsetY = 1;
                        ctx.shadowBlur    = 2;
                        ctx.shadowColor   = 'rgba(0,0,0,0.5)';
        */ 
                        //draw line..
                        ctx.beginPath();
                        ctx.moveTo(view.select.x, view.select.y);
                        ctx.lineTo(view.select.x + view.select.width, view.select.y + view.select.height);
                        ctx.strokeStyle = "#0c0";
                        ctx.lineWidth = 3;
                        ctx.stroke();

                        //draw grabbers & line between
                        ctx.beginPath();
                        ctx.arc(view.select.x, view.select.y,options.graber_size/2,0,Math.PI*2,false);
                        ctx.arc(view.select.x+view.select.width, view.select.y+view.select.height,options.graber_size/2,0,Math.PI*2,false);
                        ctx.fillStyle = "#0c0";
                        ctx.fill();
                    },

                    draw_select_2d: function(ctx) {
        /*
                        ctx.shadowOffsetX = 2;
                        ctx.shadowOffsetY = 2;
                        ctx.shadowBlur    = 2;
                        ctx.shadowColor   = 'rgba(0,0,0,0.5)';
        */
                        ctx.shadowOffsetX = 0;
                        ctx.shadowOffsetY = 0;
                        ctx.shadowBlur    = 0;
                        ctx.shadowColor   = 'rgba(0,0,0,0)';
                        ctx.strokeStyle = '#0c0'; 
                        ctx.lineWidth   = 2;
                        ctx.fillStyle = '#0c0';

                        //draw box
                        ctx.strokeRect(view.select.x, view.select.y, view.select.width, view.select.height);

                        //draw grabbers
                        ctx.beginPath();
                        ctx.arc(view.select.x, view.select.y, options.graber_size/2, 0, Math.PI*2, false);//topleft
                        ctx.fill();

                        ctx.beginPath();
                        ctx.arc(view.select.x+view.select.width, view.select.y, options.graber_size/2, 0, Math.PI*2, false);//topright
                        ctx.fill();

                        ctx.beginPath();
                        ctx.arc(view.select.x, view.select.y+view.select.height, options.graber_size/2, 0, Math.PI*2, false);//bottomleft
                        ctx.fill();

                        ctx.beginPath();
                        ctx.arc(view.select.x+view.select.width, view.select.y+view.select.height, options.graber_size/2, 0, Math.PI*2, false);//bottomright
                        ctx.fill();

                    },

                    recalc_viewparams: function() {
                        var factor = Math.pow(2,layer.level);

                        //calculate number of tiles on current level
                        layer.xtilenum = Math.ceil(layer.info.width/factor/layer.info.tilesize);
                        layer.ytilenum = Math.ceil(layer.info.height/factor/layer.info.tilesize);

                        //calculate size of the last tile
                        layer.tilesize_xlast = layer.info.width/factor%layer.info.tilesize;
                        layer.tilesize_ylast = layer.info.height/factor%layer.info.tilesize;
                        if(layer.tilesize_xlast == 0) layer.tilesize_xlast = layer.info.tilesize;
                        if(layer.tilesize_ylast == 0) layer.tilesize_ylast = layer.info.tilesize;
                    },

                    //get current pixel coordinates of the canvas window
                    get_viewpos: function() {
                        var factor = Math.pow(2, layer.level)*layer.info.tilesize/layer.tilesize;
                        return {
                            x: -layer.xpos*factor,
                            y: -layer.ypos*factor,
                            width: view.canvas.clientWidth*factor,
                            height: view.canvas.clientHeight*factor
                        };
                    },

                    //calculate pixel position based on client x/y
                    client2pixel: function(client_x, client_y) {
                        var factor = Math.pow(2,layer.level) * layer.info.tilesize / layer.tilesize;
                        var pixel_x = Math.round((client_x - layer.xpos)*factor);
                        var pixel_y = Math.round((client_y - layer.ypos)*factor);
                        return {x: pixel_x, y: pixel_y};
                    },

                    //calculate pixel potision on the center
                    center_pixelpos: function() {
                        return view.client2pixel(view.canvas.clientWidth/2, view.canvas.clientHeight/2);
                    },

                    change_zoom: function(delta, x, y) {
						var w = jQuery(view.canvas).width();
						var h = jQuery(view.canvas).height();
						
                        
                        //ignore if we've reached min/max zoom
                        if(layer.level == 0 && layer.tilesize+delta > layer.info.tilesize*options.maximum_pixelsize) { return false; }
						
						
						// Don't allow zooming smaller than window
						if ((delta < 0) && (((layer.tilesize + delta) * (layer.xtilenum)) <= w) && (((layer.tilesize + delta) * (layer.ytilenum)) <= h)) { return false; }
								
                        //*before* changing tilesize, adjust offset so that we will zoom into where the cursor is
                        var dist_from_x0 = x - layer.xpos;
                        var dist_from_y0 = y - layer.ypos;
                    
                        layer.xpos -= dist_from_x0/layer.tilesize*delta;
                        layer.ypos -= dist_from_y0/layer.tilesize*delta;
                        
                        // Don't allow scrolling offscreen

                        if (
                        	(layer.xpos > w) 
                        	|| 
                        	(layer.ypos > h)
                        	||
                        	(layer.xpos < (-1 *(layer.tilesize * layer.xtilenum))) 
                        	|| 
                        	(layer.ypos < (-1 *(layer.tilesize * layer.ytilenum)))
                        ) { 
                        	view.pan.xdest = (layer.tilesize * layer.xtilenum)/2;
							view.pan.ydest = (layer.tilesize * layer.ytilenum)/2;
							view.pan.level = layer.level;
							view.needdraw = true;
							
							return;
                        }
                        
                        
						if ((layer.tilesize + delta) > 16) {
                  	      layer.tilesize += delta;
						}
						
                        //adjust level
                        if(layer.tilesize > layer.info.tilesize) { //level down
                            if(layer.level != 0) {
                                layer.level--;
                                layer.tilesize /= 2; //we can't use bitoperation here.. need to preserve floating point
                                view.recalc_viewparams();
                            }
                        }
                        if(layer.tilesize < layer.info.tilesize/2) { //level up
                            if(layer.level != layer.info._maxlevel) {
                                layer.level++;
                                layer.tilesize *= 2; //we can't use bitoperation here.. need to preserve floating point
                                view.recalc_viewparams();
                            }
                        }

                        view.needdraw = true;
                    },

                    pan: function() {
                        var factor = Math.pow(2,layer.level)*layer.info.tilesize/layer.tilesize;
                        var xdest_client = view.pan.xdest/factor + layer.xpos;
                        var ydest_client = view.pan.ydest/factor + layer.ypos;
                        var center = view.center_pixelpos();
                        var dx = center.x - view.pan.xdest;
                        var dy = center.y - view.pan.ydest;
                        var dist = Math.sqrt(dx*dx + dy*dy);

                            //Step 2a) Pan to destination
                        if(dist >= factor) {
                    		layer.xpos += dx / factor / 10;
                    		layer.ypos += dy / factor / 10;
						}

						if(dist < factor) { // && level_dist < 0.1) {
							//reached destination
							view.pan.xdest = null;
						}
						
                        view.needdraw = true;
                    },

                    inside: function(xt,yt,x,y,w,h) {
                        if(xt > x && xt < x + w && yt > y && yt < y + h) return true;
                        return false;
                    },

                    hittest_select_1d: function(x,y) {
                        if(view.inside(x,y, 
                            view.select.x-options.graber_size/2, 
                            view.select.y-options.graber_size/2,
                            options.graber_size, options.graber_size)) return "topleft";
                        if(view.inside(x,y,
                            view.select.x-options.graber_size/2+view.select.width, 
                            view.select.y-options.graber_size/2+view.select.height,
                            options.graber_size, options.graber_size)) return "bottomright";
                         return null;
                    },

                    hittest_select_2d: function(x,y) {
                        if(view.inside(x,y, 
                            view.select.x-options.graber_size/2, 
                            view.select.y-options.graber_size/2,
                            options.graber_size, options.graber_size)) return "topleft";
                        if(view.inside(x,y, 
                            view.select.x-options.graber_size/2, 
                            view.select.y-options.graber_size/2,
                            options.graber_size, options.graber_size)) return "topleft";
                        if(view.inside(x,y, 
                            view.select.x-options.graber_size/2+view.select.width, 
                            view.select.y-options.graber_size/2, 
                            options.graber_size, options.graber_size)) return "topright";
                        if(view.inside(x,y,
                            view.select.x-options.graber_size/2, 
                            view.select.y-options.graber_size/2+view.select.height,
                            options.graber_size, options.graber_size)) return "bottomleft";
                        if(view.inside(x,y,
                            view.select.x-options.graber_size/2+view.select.width, 
                            view.select.y-options.graber_size/2+view.select.height,
                            options.graber_size, options.graber_size)) return "bottomright";
                        if(view.inside(x,y, 
                            view.select.x, view.select.y,
                            view.select.width, view.select.height)) return "inside";
                         return null;
                    }
                };//view definition
                $this.data("view", view);

                //setup views
                $this.addClass("tileviewer");
              //  $(view.canvas).css("background-color", "#FFFFFF");

                $(view.canvas).css("width", "100%");
                $(view.canvas).css("height", "100%");

                $this.append(view.canvas);
                $(view.status).addClass("status");
                $this.append(view.status);
                
                $(view.controls).addClass("viewerControls");
                $this.append(view.controls);
                $(view.controls).mouseover(function() {
                	jQuery(this).animate({'opacity': 1.0}, { queue: false, duration: 300});
                });
                 $(view.controls).mouseout(function() {
                	jQuery(this).animate({'opacity': 0.5}, { queue: false, duration: 300});
                });
                

				layer.info = options.info;
				//calculate metadata
				var v1 = Math.max(layer.info.width, layer.info.height)/layer.info.tilesize;
				layer.info._maxlevel = Math.ceil(Math.log(v1)/Math.log(2));

				var w = jQuery(view.canvas).width();
				var h = jQuery(view.canvas).height(); 
				
				//set initial level/size to fit the entire view
				var min = Math.min(w, h)/layer.info.tilesize; //number of tiles that can fit
				layer.level = layer.info._maxlevel - Math.floor(min) - 1;
				if (layer.level < 1) { layer.level = 0; }	// level can't be less than zero
				layer.tilesize = layer.info.tilesize;

				view.recalc_viewparams();
				layer.tilesize = Math.min((w/layer.xtilenum), (h/layer.ytilenum));

				//center image
				var factor = Math.pow(2,layer.level) * layer.info.tilesize / layer.tilesize;
				layer.xpos = view.canvas.clientWidth/2-layer.info.width/2/factor;
				layer.ypos = view.canvas.clientHeight/2-layer.info.height/2/factor;
				
				
				//cache level0 image (so that we don't have to use the green rect too long..)
				var url = options.src + methods.getTilepicTileNum(layer.info._maxlevel, 0, layer);
				view.loader_request(url);

				view.recalc_viewparams();
				view.needdraw = true;
				
                //setup magnifier canvas
                view.magnifier_canvas.width = options.magnifier_view_area;
                view.magnifier_canvas.height = options.magnifier_view_area;
/*
                //load image
                view.icons.box = new Image();
                view.icons.box.src = "images/box.png";
*/
                //load thumbnail
                layer.thumb = new Image();
				layer.thumb.src = options.src+methods.getTilepicTileNum((layer.info._maxlevel), 0, layer)

                // http://paulirish.com/2011/requestanimationframe-for-smart-animating/
                // requestAnim shim layer by Paul Irish
                window.requestAnimFrame = (function(){
                  return  window.requestAnimationFrame       || 
                          window.webkitRequestAnimationFrame || 
                          window.mozRequestAnimationFrame    || 
                          window.oRequestAnimationFrame      || 
                          window.msRequestAnimationFrame     || 
                          function(/* function */ callback, /* DOMElement */ element){
                            window.setTimeout(callback, 1000 / 60);
                          };
                })();

                var draw_thread = function() {
                    requestAnimFrame(draw_thread);
                    if(view.pan.xdest) {
                        view.pan();
                    }

                    if(view.needdraw) {
                        view.draw();
                    }
                };
                draw_thread();

/*
                //redraw thread
                var draw_thread = function() {
                    if(view.pan.xdest) {
                        pan();
                    }

                    if(view.needdraw) {
                        draw();
                    }
                    //setTimeout(draw_thread, 30);
                }
                //read http://ejohn.org/blog/how-javascript-timers-work/
                setInterval(draw_thread, 30);
*/

                ///////////////////////////////////////////////////////////////////////////////////
                //event handlers
                $(view.canvas).mousedown(function(e) {
                    var offset = $(view.canvas).offset();
                    var x = e.pageX - offset.left;
                    var y = e.pageY - offset.top;

					if (options.thumbnail) {
						var tw = layer.thumb.width;
						var th = layer.thumb.height;
						
						if ((x <= tw) && (y <= th)) {	
							view.pan.xdest = ((x/tw) * layer.info.width);
							view.pan.ydest = ((y/th) * layer.info.height);
							view.pan.level = layer.level;
							view.needdraw = true;
							return;
						}
					}

                    view.mousedown = true;

                    //mode specific extra info
                    switch(view.mode) {
                    case "pan":
                        view.pan.xdest = null;//cancel pan
                        view.pan.xhot = x - layer.xpos;
                        view.pan.yhot = y - layer.ypos;
                        document.body.style.cursor="move";
                        break;
                    case "zoom_in":
                    	view.interval = setInterval(function() {
                    		if (!view.mousedown) {
                    			clearInterval(view.interval);
                    		}
                    		view.change_zoom(30, x, y);
                    	}, 50);
                    	break;
                    case "zoom_out":
                    	view.interval = setInterval(function() {
                    		if (!view.mousedown) {
                    			clearInterval(view.interval);
                    		}
                    		view.change_zoom(-30, x, y);
                    	}, 50);
                    	break;
                    case "select_1d":
                        view.select.item = view.hittest_select_1d(x,y);
                        break;
                    case "select_2d":
                        view.select.item = view.hittest_select_2d(x,y);
                    }
                    switch(view.mode) {
                    case "select_1d":
                    case "select_2d":
                        view.select.xhot = x - view.select.x;
                        view.select.yhot = y - view.select.y;
                        view.select.whot = x - view.select.width;
                        view.select.hhot = y - view.select.height;
                        view.select.xprev = view.select.x;
                        view.select.yprev = view.select.y;
                        view.select.wprev = view.select.width;
                        view.select.hprev = view.select.height;
                        break;
                    }
                    return false;
                });

                //we want to capture mouseup on whole doucument - not just canvas
                $(document).mouseup(function(){
                    document.body.style.cursor="auto";
                    view.mousedown = false;
                    return false;
                });

                $(view.canvas).mousemove(function(e) {
                    var offset = $(view.canvas).offset();
                    var x = e.pageX - offset.left;
                    var y = e.pageY - offset.top;
                    view.xnow = x;
                    view.ynow = y;

                    if(layer.info == null) { return false; }

                    if(options.magnifier) {
                        //need to redraw magnifier
                        view.needdraw = true;
                    }

                    if(view.mousedown) {
                        //dragging
                        switch(view.mode) {
                        case "pan":
                            layer.xpos = x - view.pan.xhot;
                            layer.ypos = y - view.pan.yhot;
                            view.draw();//TODO - should I call needdraw instead?
                            break;
                        case "select_1d":
                            switch(view.select.item) {
                            case "topleft":
                                view.select.x = x - view.select.xhot;
                                view.select.y = y - view.select.yhot;
                                view.select.width = view.select.wprev + (view.select.xprev - view.select.x);
                                view.select.height = view.select.hprev + (view.select.yprev - view.select.y);
                                break;
                            case "bottomright":
                                view.select.width = x - view.select.whot;
                                view.select.height = y - view.select.hhot;
                                break;
                            }
                            view.draw();
                            break;
                        case "select_2d":
                            switch(view.select.item) {
                            case "inside":
                                view.select.x = x - view.select.xhot;
                                view.select.y = y - view.select.yhot;
                                break;
                            case "topleft":
                                view.select.x = x - view.select.xhot;
                                view.select.y = y - view.select.yhot;
                                view.select.width = view.select.wprev + (view.select.xprev - view.select.x);
                                view.select.height = view.select.hprev + (view.select.yprev - view.select.y);
                                break;
                            case "topright":
                                view.select.y = y - view.select.yhot;
                                view.select.width = x - view.select.whot;
                                view.select.height = view.select.hprev + (view.select.yprev - view.select.y);
                                break;
                            case "bottomleft":
                                view.select.x = x - view.select.xhot;
                                view.select.height = y - view.select.hhot;
                                view.select.width = view.select.wprev + (view.select.xprev - view.select.x);
                                break;
                            case "bottomright":
                                view.select.width = x - view.select.whot;
                                view.select.height = y - view.select.hhot;
                                break;
                            }
                            view.draw();
                            break;
                        }
                    } else {
                        //just hovering
                        switch(view.mode) {
                        case "pan":
                            break;
                        case "select_1d":
                            view.select.item = view.hittest_select_1d(x,y);
                            break;
                        case "select_2d":
                            view.select.item = view.hittest_select_2d(x,y);
                            break;
                        }

                        switch(view.mode) {
                        case "select_1d":
                        case "select_2d":
                            switch(view.select.item) {
                            case "inside": 
                                document.body.style.cursor="move"; break;
                            case "topleft": 
                            case "bottomright": 
                                document.body.style.cursor="nw-resize"; break;
                            case "topright": 
                            case "bottomleft": 
                                document.body.style.cursor="ne-resize"; break;
                            default: document.body.style.cursor="auto";
                            }
                            break;
                        }
                    }

                    view.update_status(); //mouse position change doesn't cause view update.. so I have to call this 

                    return false;
                });

                $(view.canvas).bind("mousewheel.tileviewer", function(e, delta) {
                    view.pan.xdest = null;//cancel pan
                    //if(view.mode == "pan") {
                        delta = delta*options.zoom_sensitivity;
                        var offset = $(view.canvas).offset();
                        view.change_zoom(delta, e.pageX - offset.left, e.pageY - offset.top);
                    //}
                    return false;
                });
            } else {
               // console.log("already initialized");
            }

                methods.setmode.call($this, {mode: "pan"});
        }); //for each
    }, //public / init

/*
    ///////////////////////////////////////////////////////////////////////////////////
    // 
    zoom: function (options) {
        return this.each(function() {
            var view = $(this).data("view");
            view.change_zoom(options.delta,0,0,0,0);
        });
    },
*/

    ///////////////////////////////////////////////////////////////////////////////////
    // call this if everytime you resize the container (TODO - can't it be automated?)
    resize: function (options) {
        return this.each(function() {
            var view = $(this).data("view");
            view.canvas.width = options.width;
            view.canvas.height = options.height;
            view.needdraw = true;
        });
    },

    ///////////////////////////////////////////////////////////////////////////////////
    // Override current options
    options: function(options) {
        return this.each(function() {
            var current_options = $(this).data("options");
            $.extend(current_options, options);
            var view = $(this).data("view");
            view.needdraw = true;
        });
    },

	///////////////////////////////////////////////////////////////////////////////////
    // Get current options
    getOption: function(option) {
        return this.each(function() {
            var current_options = $(this).data("options");
           return current_options[option];
        });
    },

    ///////////////////////////////////////////////////////////////////////////////////
    // use this to animate the view (or zoom)
    pan: function (options) {
        return this.each(function() {
            var view = $(this).data("view");
            view.pan.xdest = options.x;
            view.pan.ydest = options.y;
            view.pan.leveldest = options.level;
        });
    },

	///////////////////////////////////////////////////////////////////////////////////
    // use this toggle thumbnail view on and off
    toggleThumbnail: function () {
        return this.each(function() {
        	var current_options = $(this).data("options");
            current_options.thumbnail = !current_options.thumbnail;
             
            var view = $(this).data("view");
            view.draw();
        });
    },
    
    ///////////////////////////////////////////////////////////////////////////////////
    // use this toggle magnifier view on and off
    toggleMagnifier: function () {
        return this.each(function() {
        	var current_options = $(this).data("options");
            current_options.magnifier = !current_options.magnifier;
             
            var view = $(this).data("view");
            view.draw();
        });
    },
/*
    ///////////////////////////////////////////////////////////////////////////////////
    // use this to jump to the destination pos / zoom
    setpos: function (options) {
        return this.each(function() {
            var layer = $(this).data("layer");
            var view = $(this).data("view");
            layer.xpos = options.x;
            layer.ypos = options.y;
            layer.level = Math.round(options.level); //TODO process sub decimal value
        });
    },
*/

    ///////////////////////////////////////////////////////////////////////////////////
    // use this to animate the view (or zoom)
    getpos: function () {
        //get current position
        var view = $(this).data("view");
        var layer = $(this).data("layer");
        var pos = view.center_pixelpos();
        pos.level = Math.round((layer.level + layer.info.tilesize/layer.tilesize-1)*1000)/1000;
        return pos;
    },

    ///////////////////////////////////////////////////////////////////////////////////
    // set current mouse mode
    setmode: function(options) {
        return this.each(function() {
        	var current_options = $(this).data("options");
            var view = $(this).data("view");

            switch(options.mode) {
            case "pan":
                break;
            case "zoom_in":
                break;
            case "zoom_out":
                break;
            case "select_1d":
            case "select_2d":
                view.select.x = 50;
                view.select.y = 50;
                view.select.width = view.canvas.clientWidth-100;
                view.select.height = view.canvas.clientHeight-100;
                break;
            default:
                console.log("unknown mode:" + options.mode);
                return;
            }

            view.mode = options.mode;
            view.needdraw = true;
        });
    },
    ///////////////////////////////////////////////////////////////////////////////////
    // Convert viewer level/tile specification into Tilepic tile number
    getTilepicTileNum: function(level, tile, layer) {
    	if (level < 0) { return; }
    	var w = layer.info.width;
    	var h = layer.info.height;
    	var ts = layer.info.tilesize;
    	var l = layer.info.levels;
    	
    	if (!methods.tileCounts) {
			var map = [];
			var lt = 0;
			var tc = 0;
			while ((w >= ts) || (h >= ts)) {
				var nx = Math.ceil(w/ts);
				var ny = Math.ceil(h/ts);
				map[lt] = nx*ny;
				tc += map[lt];
				
				w = Math.ceil(w/2.0);
				h = Math.ceil(h/2.0);
				lt++;
			}
			map[lt] = 1;
    		methods.tileCounts = map.reverse();	// tilepic layers are recorded in an order opposite tileviewer 
    		methods.tileTotal = tc;
    	}
    	var c = 1;	// our Tilepic parser always puts a tiny thumb as the first tile, which we want to skip since tileviewer doesn't encode a counterpart
    	alevel = l - level - 2;
    	for(i=0; i<alevel; i++) {
    		c += methods.tileCounts[i];
    	}
    	return c + tile + 1;
    }

};//end of public methods

//bootstrap
$.fn.tileviewer = function( method ) {
    if ( methods[method] ) {
        return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
        return methods.init.apply( this, arguments );
    } else {
        console.log( 'Method:' +  method + ' does not exist on jQuery.tileviewer' );
    }
};

})(jQuery);
