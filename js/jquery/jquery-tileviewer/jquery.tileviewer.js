/* 

TileViewer HTML5 client

    Version: 3.0.0

    This plugin is tested with following dependencies
    * JQuery 1.7
    * JQuery UI 1.9
    * JQuery.HotKeys
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
	ANNOTATIONS SUPPORT ADDED April 2013 by SK
	MORE ANNOTATIONS SUPPORT ADDED October-December 2013 by SK
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
            thumb_depth: 2, //level depth when thumbnail should appear
            
            toolbar: ['pan', 'rect', 'point', 'polygon', 'lock', 'separator',  'overview', 'expand', 'help'],
            
            annotationLoadUrl: null,
            annotationSaveUrl: null,
            helpLoadUrl:null,
            
            deleteButtonUrl: "/admin/themes/default/graphics/buttons/x.png",
            
            annotation_prefix_text: '',
            default_annotation_text: '',					// initial value for newly created annotations
			empty_annotation_label_text: "<span class='tileviewerAnnotationDefaultText'>Enter your annotation</span>", // text to display when there is no annotation text in label
			empty_annotation_editor_text: "Type text. Drag to position.",
			show_empty_annotation_label_text_in_text_boxes: true,
            
            /* functional options */
            use_annotations: true,							// display annotation tools + annotations
            display_annotations: true,						// display annotations on load
            lock_annotations: false,						// lock annotations on load - will display but cannot add, remove or drag existing annotations
            lock_annotation_text: false,					// lock annotation text on load - will display text but not be editable
            show_annotation_tools: true,					// show annotation tools on load
            annotation_text_display_mode: 'simultaneous',	// how to display annotation text: 'simultaneous' = show all annotation text all the time [DEFAULT]; 'mouseover' = show annotation text only when mouse is over the annotation or it is selected; 'selected' = show annotation text only when it is selected
			annotation_color: "000000", //"EE7B19",
			annotation_color_selected: "CC0000",
			highlight_points_with_circles: true,			// draw circles around point label locations?
			allow_draggable_text_boxes_for_rects: true,		// allow draggable text boxes for rectangular annotations?
			
			add_point_annotation_mode: false,
			add_rect_annotation_mode: false,
			add_polygon_annotation_mode: false,
			pan_mode: true
        };

        return this.each(function() {
            var $this = $(this);
            options = $.extend(defaults, options);//override defaults with options
            $this.data("options", options);

            ///////////////////////////////////////////////////////////////////////////////////
            // Now we can start initializing
            // If the plugin hasn't been initialized yet..
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
                    //status: document.createElement("p"),
                    controls: document.createElement("div"),
                    annotationTextBlocks: [],
                    annotationTextEditor: document.createElement("div"),
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
                    
                    annotations: [], // annotations list
                    changedAnnotations: [],		// indices of annotations that need to be saved
                    annotationAreas: [],
                    annotationsToSave: [],
                    annotationsToDelete: [],
                    
                    isSavingAnnotations: false,	// flag indicating save is pending
                    
                    magnifier_canvas: document.createElement("canvas"),
                    //current mouse position (client pos)
                    xnow: null,
                    ynow: null,
                    mousedown: false,
                    
                    dragAnnotation: null,			// index of annotation currently being dragged
                    selectedAnnotation: null,		// index of annotation currently selected
                    
                    framerate: null,//current framerate (1000 msec / drawtime msec)
                    needdraw: false, //flag used to request for frameredraw 
                    
                    polygon_in_progress_annotation_index: null,		// index of polygone being built; null if no polygon is being built currently

                    ///////////////////////////////////////////////////////////////////////////////////
                    // Internal functions
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
                        		view.draw_thumb(ctx);
                            }
                            break;
                        }

                        //calculate framerate
                        //var end = new Date().getTime();
                        //var time = end - start;
                        //view.framerate = Math.round(1000/time);

                        //view.update_status();
                        
                        
                        view.update_controls();
                        
              
                    	if (options.use_annotations) {
                       		view.draw_annotations();
                       	}
                        
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
                    
                    load_annotations: function() {     
                    	if (!options.use_annotations) { return; }
                    	
                    	jQuery.getJSON(options.annotationLoadUrl, function(data) {
                    		view.annotations = [];
                    		view.annotationTextBlocks = [];
                    		
                    		jQuery.each(data, function(k, v) {
                    			v['index'] = k;
                    			v['x'] = parseFloat(v['x']);
                    			v['y'] = parseFloat(v['y']);
                    			v['w'] = parseFloat(v['w']);
                    			v['h'] = parseFloat(v['h']);
                    			v['tx'] = parseFloat(v['tx']);
                    			v['ty'] = parseFloat(v['ty']);
                    			v['tw'] = parseFloat(v['tw']);
                    			v['th'] = parseFloat(v['th']);
                    			if (v['label'] == '[BLANK]') { v['label'] = ''; }
                    			
                    			// create text block
                    			var textBlock = document.createElement("div");
                    			jQuery(textBlock).attr('id', 'tileviewerAnnotationTextBlock_' + k).addClass("tileviewerAnnotationTextBlock").data("annotationIndex", k).html(options.annotation_prefix_text + (v['label'] ? v['label'] : (options.show_empty_annotation_label_text_in_text_boxes ? options.empty_annotation_label_text : '')));
								jQuery('#tileviewerAnnotationTextBlock_' + k).remove();
								$this.append(textBlock)
								if (options.annotation_text_display_mode == 'simultaneous') { 
									view._makeAnnotationTextBlockDraggable('#tileviewerAnnotationTextBlock_' + k);
								}
								v['textBlock'] = textBlock;
							
								view.annotations.push(v);
                    		});
                    		
                    		view.draw_annotations();
                    	});
                    },
                    
                    /**
                     * Record annotation changes for subsequent commit
                     */
                    save_annotations: function(toSave, toDelete) {
                    	if (!options.use_annotations) { return; }
                    	
                    	for(var i in toSave) {
                    		if (!jQuery.isNumeric(i)) { continue; }
                    		view.annotationsToSave.push(view.annotations[toSave[i]]);
                    	}
                    	
                    	for(var i in toDelete) {
                    		if (!jQuery.isNumeric(i)) { continue; }
                    		view.annotationsToDelete.push(view.annotations[toDelete[i]].annotation_id);
                    	}
                    	
                    	view.commit_annotation_changes();
                    },
                    
                    /**
                     * Write annotations to database
                     */
                	commit_annotation_changes: function() {
                    	if (!options.use_annotations) { return; }
                    	
                    	if (view.isSavingAnnotations) { 
                    		if (options.debug) { console.log("Cannot commit now; save is pending"); }
                    		return false; 
                    	}
                    	if ((view.annotationsToSave.length == 0) && (view.annotationsToDelete.length == 0)) {
                    		if (options.debug) { console.log("Cannot commit now; nothing to save"); }
                    		return false;
                    	}
                    	view.isSavingAnnotations = true;
                    	if (options.debug) { console.log("Commit " + view.annotationsToSave.length + " annotations to " + options.annotationSaveUrl, view.annotationsToSave, view.annotationsToDelete); }
                    	
                    	// strip out textBlock pointer because it causes jQuery errors with getJSON
                    	var annotationsToSave = [];
                    	jQuery.each(view.annotationsToSave, function(k, v) {
                    		var a = jQuery.extend({}, v);
                    		a['textBlock'] = null;
                    		annotationsToSave.push(a);
                    	});
                    	
                    	jQuery.getJSON(options.annotationSaveUrl, { save: annotationsToSave, delete: view.annotationsToDelete }, function(data) {
                    		if (data['annotation_ids']) {
                    			for(var index in data['annotation_ids']) {
                    				if (!jQuery.isNumeric(index)) { continue; }
                    				if (!view.annotations[index]) { continue; }
                    				view.annotations[index]['annotation_id'] = data['annotation_ids'][index];
                    				var i = view.changedAnnotations.indexOf(index);
                    				if (i !== -1) {
                    					view.changedAnnotations.splice(i, 1);
                    				}
                    			}
                    		
								// put new text into overlays
                    			for(var i in annotationsToSave) {
                    				var index = annotationsToSave[i]['index'];
                    				if (!jQuery.isNumeric(i)) { continue; }
                    				if (!jQuery.isNumeric(index)) { continue; }
                    				if (data['annotation_ids'][index]) {
                    					jQuery("#tileviewerAnnotationTextBlock_" + index).html(options.annotation_prefix_text + (annotationsToSave[i]['label'] ? annotationsToSave[i]['label'] : (options.show_empty_annotation_label_text_in_text_boxes ? options.empty_annotation_label_text : '')));
                    				}
                    			}
                    		}
                    		
                    		view.annotationsToSave = [];
                    		view.annotationsToDelete = [];
                    		
                    		view.isSavingAnnotations = false;
                    		view.commit_annotation_changes();
                    		
                    		view.needdraw = true;
                    		jQuery("#tileviewerAnnotationTextSave").fadeOut(250);	// hide save button
                    	});
                    },

					_makeAnnotationTextBlockDraggable: function(id) {
						jQuery(id).show().draggable({ drag: function(e) {
							if (options.lock_annotation_text) {
								e.preventDefault();
								return false;
							}
							var index = jQuery(this).data('annotationIndex');
							if(
								index != null
								&&
								view.annotations[index]
								&& 
								(
									(view.annotations[index].type == 'point')
									||
									(view.annotations[index].type == 'poly')
									||
									((view.annotations[index].type == 'rect') && options.allow_draggable_text_boxes_for_rects)
								)
							) {
								var pos = jQuery('#tileviewerAnnotationTextBlock_' +index).position();

								var factor = Math.pow(2,layer.level);
								view.annotations[index].tx = ((pos.left - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
								view.annotations[index].ty = ((pos.top - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
		
								view.draw();
							}
						}, stop: function(e) {
							var index = jQuery(this).data('annotationIndex');
							var pos = jQuery('#tileviewerAnnotationTextBlock_' + index).position();

							var factor = Math.pow(2,layer.level);
							view.annotations[index].tx = ((pos.left - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
							view.annotations[index].ty = ((pos.top - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
		
							view.save_annotations([index], []);
							view.commit_annotation_changes();
							view.draw();
						}}).mouseup(function(e) {
							view.isAnnotationResize = view.isAnnotationTransformation = view.mousedown = view.dragAnnotation = null;
						});
					},
					
					init_context_properties: function(ctx) {
						ctx.shadowOffsetX = 1;
                        ctx.shadowOffsetY = 1;
                        ctx.shadowBlur    = 2;
                        ctx.shadowColor   = 'rgba(255,255, 255, 1.0)';
                        ctx.lineWidth   = 1;
                        ctx.fillStyle = '#0c0';
					},
					
//
// Begin ANNOTATIONS: draw outlines
//                 
                    draw_annotations: function() {
                    	if (!options.use_annotations || !options.display_annotations) { 
                    		jQuery(".tileviewerAnnotationTextBlock").fadeOut(100);
                    		return; 
                    	}
                    	var ctx = view.canvas.getContext("2d");
                        
                        var factor = Math.pow(2,layer.level);
                         
						var canvasWidth = jQuery(view.canvas).width();
						var canvasHeight = jQuery(view.canvas).height();
						var layerWidth = layer.info.width/factor;		// The actual width of the layer on-screen
						var layerHeight = layer.info.height/factor;		// The actual height of the layer on-screen
						var layerMag =  layer.tilesize/256;				// Current layer magnification factor
						
                        view.init_context_properties(ctx);
                         
                        // draw annotations
                        view.annotationAreas = [];
                        
                        // Reorder annotations for drawing, putting selected on last (so it's on top)
                        var annotationsToDraw = jQuery.extend(true, [], view.annotations);
                        var selectedAnnotation = view.selectedAnnotation;
                        
                       // if ((selectedAnnotation !== null) && (annotationsToDraw.length > 0)) {
                       // 	selectedAnnotation = annotationsToDraw[annotationsToDraw.length - 1].index;
                       // }
                       
                        for(var i in annotationsToDraw) {
                        	if (!jQuery.isNumeric(i)) { continue; }
                        	var annotation = annotationsToDraw[i];
                        	if (!annotation) { continue; }
                        	
                        	// is annotation the current selection?
                        	if (selectedAnnotation == i) {
                        		ctx.strokeStyle = '#' + options.annotation_color_selected;
                        	} else {
                        		ctx.strokeStyle = '#' + options.annotation_color; 
                        	}
                        	//annotation_text_display_mode
                        	// do drawing
                        	var areaMultiplier = 1;
                        	switch(annotation.type) {
                        		case 'rect':
									ctx.strokeRect(
										x= ((annotation.x/100) * layerWidth * layerMag) + layer.xpos, 
										y= ((annotation.y/100) * layerHeight * layerMag) + layer.ypos, 
										w= (annotation.w/100) * layerWidth * layerMag,  
										h= (annotation.h/100) * layerHeight * layerMag
									);
									
									if (selectedAnnotation == i) {
										// draw drag knobs
										ctx.beginPath();					// Upper left
										ctx.arc(x,y, 3, 0, 2*Math.PI);
										ctx.stroke();
									
										ctx.beginPath();
										ctx.arc(x,y + h, 3, 0, 2*Math.PI);	// Lower left
										ctx.stroke();
									
										ctx.beginPath();					// Upper right
										ctx.arc(x + w,y, 3, 0, 2*Math.PI);
										ctx.stroke();
									
										ctx.beginPath();
										ctx.arc(x + w,y + h, 3, 0, 2*Math.PI);	// Lower right
										ctx.stroke();
										
										ctx.beginPath();					// Upper middle
										ctx.arc(x + (w/2), y, 3, 0, 2*Math.PI);
										ctx.stroke();
										
										ctx.beginPath();					// Left middle
										ctx.arc(x, y + (h/2), 3, 0, 2*Math.PI);
										ctx.stroke();
										
										ctx.beginPath();					// Right middle
										ctx.arc(x + w, y + (h/2), 3, 0, 2*Math.PI);
										ctx.stroke();
										
										ctx.beginPath();					// Lower middle
										ctx.arc(x + (w/2), y + h, 3, 0, 2*Math.PI);
										ctx.stroke();
									}
									
									areaMultiplier = 1.2;	// make rects easier to select
									
									// stick
									if (options.allow_draggable_text_boxes_for_rects) {	
										var tx = ((parseFloat(annotation.tx)/100) * layerWidth * layerMag) + layer.xpos;
										var ty = ((parseFloat(annotation.ty)/100) * layerHeight * layerMag) + layer.ypos;
										
										if (annotation.tx < annotation.x) {
											tx += $(annotation['textBlock']).width();
										}
										
										if (annotation.ty < annotation.y) {
											ty += $(annotation['textBlock']).height();
										}
									
										ctx.beginPath();
									
										if (tx >= x + (w/2)) { x += w; }
										if (ty > y + (h/2)) { y += h; }
									
										ctx.moveTo(x, y);
										ctx.lineTo(tx, ty);
										ctx.strokeStyle = '#444';
										ctx.stroke();
									}
									
									break;
								case 'point':
									// circle around point
									ctx.beginPath();
									
									x = (((parseFloat(annotation.x))/100) * layerWidth * layerMag) + layer.xpos;
									y = (((parseFloat(annotation.y))/100) * layerHeight * layerMag) + layer.ypos;
									r = (((annotation.w)/100) * layerWidth * layerMag);
									
									areaMultiplier = 3;	// make pins easier to select
									
									// Optionally draw circles around end of stick 
									if (options.highlight_points_with_circles) {
										ctx.arc(x, y, r * (areaMultiplier/2), 0,2*Math.PI);
										
										ctx.fillStyle = (selectedAnnotation == i)  ? "rgba(175, 0, 0, 0.30)" : "rgba(255, 160, 160, 0.15)";
      									ctx.fill();
									}
																		
									var tx = ((parseFloat(annotation.tx)/100) * layerWidth * layerMag) + layer.xpos;
									var ty = ((parseFloat(annotation.ty)/100) * layerHeight * layerMag) + layer.ypos;
									
									jQuery(view.annotationTextEditor).width(jQuery(annotation.textBlock).width());
									
									var width = (selectedAnnotation == i) ? jQuery(view.annotationTextEditor).width() : jQuery(annotation.textBlock).width();
									var height = (selectedAnnotation == i) ? jQuery(view.annotationTextEditor).height() : jQuery(annotation.textBlock).height();
								
									if (tx + (width/2) < x) { 		// to the left
										if (ty + (height/2) < y) {		// to the top
											tx += width;
											ty += height;
										} else {
															// to the bottom
											tx += width;
										}
									} else {
										// to the right
										if (ty + (height/2) < y) {		// to the top
											ty += height;
										} else {
															// to the bottom
											// noop		
										}
									}
									
									// is text for selected annotation off screen?
									if((view.selectedAnnotation != null) && view.annotations[view.selectedAnnotation]) {
										var sx = ((view.annotations[view.selectedAnnotation].tx/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos;
										var sy = ((view.annotations[view.selectedAnnotation].ty/100) * ((layer.info.height/factor) * (layer.tilesize/256))) + layer.ypos;
										if (sx < 0) { 
											tx += (Math.abs(sx) / ((layer.info.width/factor) * (layer.tilesize/256)));
										}
										if (sx >  jQuery($this).width()) { 
											tx += (Math.abs(jQuery($this).width()) / ((layer.info.width/factor) * (layer.tilesize/256))); 
										}
										if (sy < 0) { 
											tx += (Math.abs(sy) / ((layer.info.height/factor) * (layer.tilesize/256)));
										}
										if (sy >  jQuery($this).width()) { 
											ty += (Math.abs(jQuery($this).height()) / ((layer.info.height/factor) * (layer.tilesize/256))); 
										}
										//console.log("xy", sx, sy, tx, ty, jQuery($this).width(), jQuery($this).height());
									}
									
									
									// stick
									ctx.beginPath();
									var t = Math.atan((ty - y)/(tx - x));
									
									ctx.moveTo(x, y);
									ctx.lineTo(tx, ty);
									ctx.strokeStyle = '#444';
									ctx.stroke();
								
									break;
								case 'poly':
									x = (((parseFloat(annotation.x))/100) * layerWidth * layerMag) + layer.xpos;
									y = (((parseFloat(annotation.y))/100) * layerHeight * layerMag) + layer.ypos;
									
									if (annotation.points && jQuery.isArray(annotation.points)) {
									
										// Draw points
										for(var pointIndex in annotation.points) {
											if (!jQuery.isNumeric(pointIndex)) { continue; }
											
											var c = annotation.points[pointIndex];
											x = (((parseFloat(c.x))/100) * layerWidth * layerMag) + layer.xpos;
											y = (((parseFloat(c.y))/100) * layerHeight * layerMag) + layer.ypos;
											
											ctx.beginPath();
											ctx.arc(x,y, 3, 0, 2*Math.PI);
											ctx.stroke();
										}
										
										// Draw lines between points
										ctx.beginPath();
										var startX = x = (((parseFloat(annotation.points[0].x))/100) * layerWidth * layerMag) + layer.xpos;
										var startY = y = (((parseFloat(annotation.points[0].y))/100) * layerHeight * layerMag) + layer.ypos;
										ctx.moveTo(x, y);
										for(var pointIndex in annotation.points) {
											if (!jQuery.isNumeric(pointIndex)) { continue; }
											
											var c = annotation.points[pointIndex];
											x = (((parseFloat(c.x))/100) * layerWidth * layerMag) + layer.xpos;
											y = (((parseFloat(c.y))/100) * layerHeight * layerMag) + layer.ypos;
											ctx.lineTo(x, y);
										}
										if(annotation.points.length >= 3) {
											ctx.lineTo(startX, startY);
										}
										ctx.stroke();
										
										// Stick
										var tx = ((parseFloat(annotation.tx)/100) * layerWidth * layerMag) + layer.xpos;
										var ty = ((parseFloat(annotation.ty)/100) * layerHeight * layerMag) + layer.ypos;
										
										// find boundaries
										var minX = null, minY = null;
										var minD = null;
										var extents = {minX: null, minY: null, maxX: null, maxY: null};
										for(var pointIndex in annotation.points) {
											if (!jQuery.isNumeric(pointIndex)) { continue; }
											
											var c = annotation.points[pointIndex];
											
											var px = (((parseFloat(c.x))/100) * layerWidth * layerMag) + layer.xpos;
											var py = (((parseFloat(c.y))/100) * layerHeight * layerMag) + layer.ypos;
											
											var d = Math.sqrt(Math.pow(py - ty, 2) + Math.pow(px - tx, 2));
											if ((minD == null) || (minD > d)) { minD = d; minX = px; minY = py; }
											
											if ((extents.minX == null) || (c.x < extents.minX)) { extents.minX = c.x; }
											if ((extents.maxX == null) || (c.x > extents.maxX)) { extents.maxX = c.x; }
											if ((extents.minY == null) || (c.y < extents.minY)) { extents.minY = c.y; }
											if ((extents.maxY == null) || (c.y > extents.maxY)) { extents.maxY = c.y; }
											
										}
										view.annotations[annotation.index].x = extents.minX;
										view.annotations[annotation.index].y = extents.minY;
										view.annotations[annotation.index].w = extents.maxX - extents.minX;
										view.annotations[annotation.index].h = extents.maxY - extents.minY;
										
										if (annotation.tx < annotation.x) {
											tx += $(annotation['textBlock']).width();
										}
										
										if (annotation.ty < annotation.y) {
											ty += $(annotation['textBlock']).height();
										}
									
										ctx.beginPath();
									
										ctx.moveTo(minX, minY);
										ctx.lineTo(tx, ty);
										ctx.strokeStyle = '#444';
										ctx.stroke();
									}
									break;
								case 'circle':
									console.log("Circle annotations not supported (yet)");
									break;
								default:
									console.log("Invalid annotation type: " + annotation.type);
									break;
									
							}
						
                    		var a = {
                    			index: i,
                    			type: annotation.type,
                    			startX: parseFloat(annotation.x), endX: parseFloat(annotation.x) + parseFloat(annotation.w),
                    			startY: parseFloat(annotation.y), endY: parseFloat(annotation.y) + parseFloat(annotation.h),
                    			
                    			width: parseFloat(annotation.w), height: parseFloat(annotation.h),
                    			
                    			tstartX: parseFloat(annotation.tx), tendX: parseFloat(annotation.tx) + parseFloat(annotation.tw),
                    			tstartY: parseFloat(annotation.ty), tendY: parseFloat(annotation.ty) + parseFloat(annotation.th),
                    			
                    			twidth: parseFloat(annotation.tw), height: parseFloat(annotation.th),
                    			textBlock: annotation.textBlock
                    		};
                    		if (areaMultiplier > 1) {
                    			var dw = (annotation.w * (areaMultiplier - 1));
                    			var dh = (annotation.h * (areaMultiplier - 1));
                    			a.startX -= (dw/2);
                    			a.endX += (dw/2);
                    			a.startY -= (dh/2);
                    			a.endY += (dh/2);
                    		};
                    		view.annotationAreas[i] = a;
                       		view.update_textbox_position();
                        }
                    },
//
// End ANNOTATIONS: draw outlines
// 

//
// Begin ANNOTATIONS: draw text
//                     
                    update_textbox_position: function(e) {
                    	if (!options.use_annotations || !options.display_annotations) { return; }
                    	if (view.selectedAnnotation == null) {
							$(view.annotationTextEditor).css("display", "none").blur();
						}
						
                    	var offset = $(view.canvas).offset();
                    	if (!e) { e = { pageX:0, pageY: 0 }; }
						var x = e.pageX - offset.left;
						var y = e.pageY - offset.top;
					
						var factor = Math.pow(2,layer.level);
					
						var x_relative = ((x - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
						var y_relative = ((y - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
						var buttonDiameter = (15/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
						var inAnnotation = null;
						
						for(var i in view.annotationAreas) {
							if (!jQuery.isNumeric(i)) { continue; }
							
							var inAnnotation = view.annotationAreas[i];
							if(!inAnnotation) { continue; }
							var sx = ((inAnnotation['tstartX']/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos;
							var sy = ((inAnnotation['tstartY']/100) * ((layer.info.height/factor) * (layer.tilesize/256))) + layer.ypos;
							
							
							var w = ((inAnnotation['width']/100) * ((layer.info.width/factor) * (layer.tilesize/256)));
							var h = ((inAnnotation['height']/100) * ((layer.info.height/factor) * (layer.tilesize/256)));
							
							if (view.annotations[i] && (view.annotations[i].type == 'point')) {
								w = ((10/100) * ((layer.info.width/factor) * (layer.tilesize/256)));
							}
							
							if (view.annotations[i] && (view.annotations[i].type == 'rect') && !options.allow_draggable_text_boxes_for_rects) {
								sy += 3;	// add gutter between rect annotation and its text block
							}
							
							// Is it off screen?
							//console.log(i, inAnnotation, sx, sy);
							
							
							// update position live on scroll
							var mw = (w > 100) ? w :  100;
							var showAnnotation = false;
							if (inAnnotation['tendY'] < inAnnotation['endY']) {
								if ((inAnnotation.index == view.selectedAnnotation) && !options.lock_annotation_text) {
									$(view.annotationTextEditor).css("display", "block").css("left", sx + 'px').css('top', sy + 'px');
									//$(inAnnotation['textBlock']).css("display", "none");
								} else {
									$(inAnnotation['textBlock']).css("left", sx + 'px').css('top', sy + 'px').css('max-width', mw + 'px');
									showAnnotation = true;
								}
							} else {
								if ((inAnnotation.index == view.selectedAnnotation) && !options.lock_annotation_text) {
									$(view.annotationTextEditor).css("display", "block").css("left", sx + 'px').css('top', sy + 'px');
									//$(inAnnotation['textBlock']).css("display", "none");
								} else {
									$(inAnnotation['textBlock']).css("left", sx + 'px').css('top', sy + 'px').css('max-width', mw + 'px');
									showAnnotation = true;
								}
							}
						
							if (view.selectedAnnotation == null) {
								if (options.annotation_text_display_mode == 'simultaneous') { 
									//$(".tileviewerAnnotationTextBlock").css("display", "block");
									showAnnotation = true;
								}
							}
						
							// Is text box off screen?
							if (sx > jQuery($this).width()) { 
								showAnnotation = false;
							} else {
								if (sy > jQuery($this).height()) { 
									showAnnotation = false;
								} 
							}
						
							if (jQuery(inAnnotation['textBlock']).is(':visible') && !showAnnotation) { 
								$(inAnnotation['textBlock']).fadeOut(250);
							} else {
								if (!jQuery(inAnnotation['textBlock']).is(':visible') && showAnnotation) { 
									$(inAnnotation['textBlock']).fadeIn(250);
								}
							}
						}
					},
//
// End ANNOTATIONS: draw text
// 
                    
                    drag_annotation: function(i, dx, dy, clickX, clickY) {
                    	if (!options.use_annotations || !options.display_annotations || options.lock_annotations) { return; }
                    	
                    	var offset = $(view.canvas).offset();
                    	var factor = Math.pow(2,layer.level);
                    	
                    	clickX -= layer.xpos;
                    	clickY -= layer.ypos;
                    	
                    	var annotationX = (((layer.info.width/factor) * (layer.tilesize/256)) * (view.annotations[i].x/100));
                    	var annotationY = ((layer.info.height/factor) * (layer.tilesize/256)) * (view.annotations[i].y/100);
                    	
                    	var annotationW = ((layer.info.width/factor) * (layer.tilesize/256)) * (view.annotations[i].w/100);
                    	var annotationH = ((layer.info.height/factor) * (layer.tilesize/256)) * (view.annotations[i].h/100);
                    	
                    	
                    	var annotationtX = (((layer.info.width/factor) * (layer.tilesize/256)) * (view.annotations[i].tx/100));
                    	var annotationtY = ((layer.info.height/factor) * (layer.tilesize/256)) * (view.annotations[i].ty/100);
                    	
                    	var annotationtW = ((layer.info.width/factor) * (layer.tilesize/256)) * (view.annotations[i].tw/100);
                    	var annotationtH = ((layer.info.height/factor) * (layer.tilesize/256)) * (view.annotations[i].th/100);
                    	
                    	var rClickX = ((clickX)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
                    	var rClickY = ((clickY)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;

 if (view.annotations[i]['type'] == 'rect') { // rects resizing                   	
                
                // Scaling	
                var rMinAllowedWidth = 0.5;
                var rMinAllowedHeight = 0.5;	
                
                if(!view.isAnnotationTranslation) {
                    	if (((Math.abs(clickX - annotationX) < 5) && (Math.abs(clickY - annotationY) < 5)) || (view.isAnnotationResize == 'LU')) {
                    		view.isAnnotationResize = 'LU';
                    		var d = view.annotations[i].x - rClickX;
                    		if(view.annotations[i].w + d < rMinAllowedWidth) { return; }
                    		view.annotations[i].x = rClickX;
                    		//view.annotations[i].tx = rClickX;
                    		view.annotations[i].w += d;
                    		
                    		d = view.annotations[i].y - rClickY;
                    		if((d == 0) || (view.annotations[i].h + d < rMinAllowedHeight)) { return; }
                    		view.annotations[i].y = rClickY;
                    		view.annotations[i].h += d;
                    		view.annotations[i].ty = view.annotations[i].y + view.annotations[i].h;
                    		jQuery("body").css('cursor', 'nw-resize');
                    		
                    		view.make_annotation_dirty(i);
                    		view.draw();
                    		return;
                    	}
                    	
                    	if (((Math.abs(clickX - (annotationX + annotationW)) < 5) && (Math.abs(clickY - annotationY) < 5)) || (view.isAnnotationResize == 'RU')) {
                    		view.isAnnotationResize = 'RU';
                    		var d = rClickX - (view.annotations[i].x + view.annotations[i].w);
                    		if(view.annotations[i].w + d < rMinAllowedWidth) { return; }
                    		view.annotations[i].w += d;
                    		
                    		d = view.annotations[i].y - rClickY;
                    		if((d==0) || (view.annotations[i].h + d < rMinAllowedHeight)) { return; }
                    		view.annotations[i].y = rClickY;
                    		view.annotations[i].h += d;
                    		//view.annotations[i].ty = view.annotations[i].y + view.annotations[i].h;
                    		jQuery("body").css('cursor', 'ne-resize');
                    		
                    		view.make_annotation_dirty(i);
                    		view.draw();
                    		return;
                    	}
                    	
                    	if (((Math.abs(clickX - annotationX) < 5) && (Math.abs(clickY - (annotationY + annotationH)) < 5)) || (view.isAnnotationResize == 'LD')) {
                    		view.isAnnotationResize = 'LD';
                    		var d = view.annotations[i].x - rClickX;
                    		if(view.annotations[i].w + d < rMinAllowedWidth) { return; }
                    		view.annotations[i].x = rClickX;
                    		//view.annotations[i].tx = rClickX;
                    		view.annotations[i].w += d;
                    		
                    		d = rClickY - (view.annotations[i].y + view.annotations[i].h);
                    		if((d==0) || (view.annotations[i].h + d < rMinAllowedHeight)) { return; }
                    		view.annotations[i].h += d;
                    		//view.annotations[i].ty = view.annotations[i].y + view.annotations[i].h;
                    		jQuery("body").css('cursor', 'sw-resize');
                    		
                    		view.make_annotation_dirty(i);
                    		view.draw();
                    		return;
                    	}
                    	
                    	if (((Math.abs(clickX - (annotationX + annotationW)) < 5) && (Math.abs(clickY - (annotationY + annotationH)) < 5)) || (view.isAnnotationResize == 'RD')) {
                    		view.isAnnotationResize = 'RD';
                    		var d = rClickX - (view.annotations[i].x + view.annotations[i].w);
                    		if(view.annotations[i].w + d < rMinAllowedWidth) { return; }
                    		view.annotations[i].w += d;
                    		
                    		d = rClickY - (view.annotations[i].y + view.annotations[i].h);
                    		if((d==0) || (view.annotations[i].h + d < rMinAllowedHeight)) { return; }
                    		var b= view.annotations[i].h;
                    		view.annotations[i].h += d;
                    		//view.annotations[i].ty = view.annotations[i].y + view.annotations[i].h;
                    		jQuery("body").css('cursor', 'se-resize');
                    		
                    		view.make_annotation_dirty(i);
                    		view.draw();
                    		return;
                    	}
                    	
                    	if ((Math.abs(clickX - annotationX) < 5) || (view.isAnnotationResize == 'L')) {
                    		view.isAnnotationResize = 'L';
                    		var d = view.annotations[i].x - rClickX;
                    		if((d==0) || (view.annotations[i].w + d < rMinAllowedWidth)) { return; }
                    		view.annotations[i].x = rClickX;
                    		//view.annotations[i].tx = rClickX;
                    		view.annotations[i].w += d;
                    		jQuery("body").css('cursor', 'w-resize');
                    		
                    		view.make_annotation_dirty(i);
                    		view.draw();
                    		return;
                    	}
                    	
                    	if ((Math.abs(clickX - (annotationX + annotationW)) < 5) || (view.isAnnotationResize == 'R')) {
                    		view.isAnnotationResize = 'R';
                    		var d = rClickX - (view.annotations[i].x + view.annotations[i].w);
                    		if((d==0) || (view.annotations[i].w + d < rMinAllowedWidth)) { return; }
                    		view.annotations[i].w += d;
                    		jQuery("body").css('cursor', 'e-resize');
                    		
                    		view.make_annotation_dirty(i);
							view.draw();
                    		return;
                    	}
                    	
                    	if ((Math.abs(clickY - annotationY) < 5) || (view.isAnnotationResize == 'U')) {
                    		view.isAnnotationResize = 'U';
                    		var d = view.annotations[i].y - rClickY;
                    		if((d==0) || (view.annotations[i].h + d < rMinAllowedHeight)) { return; }
                    		view.annotations[i].y = rClickY;
                    		view.annotations[i].h += d;
                    		//view.annotations[i].ty = view.annotations[i].y + view.annotations[i].h;
                    		jQuery("body").css('cursor', 'n-resize');
                    		
                    		view.make_annotation_dirty(i);
							view.draw();
                    		return;
                    	}
                    	
                    	if ((Math.abs(clickY - (annotationY + annotationH)) < 5) || (view.isAnnotationResize == 'D')) {
                    		view.isAnnotationResize = 'D';
                    		var d = rClickY - (view.annotations[i].y + view.annotations[i].h);
                    		if((d==0) || (view.annotations[i].h + d < rMinAllowedHeight)) { return; }
                    		view.annotations[i].h += d;
                    		//view.annotations[i].ty = view.annotations[i].y + view.annotations[i].h;
                    		jQuery("body").css('cursor', 's-resize');
                    		
                    		view.make_annotation_dirty(i);
							view.draw();
                    		return;
                    	}
            }
 } 

 if (view.annotations[i]['type'] == 'poly') {
 	// Handle dragging of points to resize/reshape polygon
  	if((view.mouseClickedOnControlPoint != null) && (view.annotations[i].type == 'poly')) {
  		var dx = (parseFloat(rClickX) - parseFloat(view.annotations[i].points[view.mouseClickedOnControlPoint].x));
  		var dy = (parseFloat(rClickY) - parseFloat(view.annotations[i].points[view.mouseClickedOnControlPoint].y));
  		view.annotations[i].points[view.mouseClickedOnControlPoint].x += dx;
  		view.annotations[i].points[view.mouseClickedOnControlPoint].y += dy;
  		view.make_annotation_dirty(i);
  		view.isAnnotationResize = true;
  	}
 }
                   	
                
 if (!view.isAnnotationResize) {
 	// Translation of annotations across image
                		var origX = parseFloat(view.annotations[i].x);
                		var origY = parseFloat(view.annotations[i].y);
                		
                		// note offset of mouse from edge of annotation at start of drag
                		if (!view.isAnnotationTranslation) {
                			view.dragOffsetX = parseFloat((((clickX - ((view.annotations[i].x/100) * ((layer.info.width/factor) * (layer.tilesize/256)))))/((layer.info.width/factor) * (layer.tilesize/256))) * 100);
                			view.dragOffsetY = parseFloat((((clickY - ((view.annotations[i].y/100) * ((layer.info.height/factor) * (layer.tilesize/256)))))/((layer.info.height/factor) * (layer.tilesize/256))) * 100);
                		}
                		
						view.annotations[i].x = parseFloat(((view.dragAnnotationLastCoords.x - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100);
						view.annotations[i].y = parseFloat(((view.dragAnnotationLastCoords.y - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100);
						
						switch(view.annotations[i].type) {
							case 'rect':
								if ((view.dragOffsetX != null) && (view.dragOffsetY != null)) {
									view.annotations[i].x -= view.dragOffsetX;
									view.annotations[i].y -= view.dragOffsetY;
								}
								
								view.annotations[i].tx += (view.annotations[i].x - origX);
								view.annotations[i].ty += (view.annotations[i].y - origY);
								break;
							case 'poly':
								var dx = parseFloat((((view.dragAnnotationLastCoords.x - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100) - origX);
								var dy = parseFloat((((view.dragAnnotationLastCoords.y - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100) - origY);
								
								if ((view.dragOffsetX != null) && (view.dragOffsetY != null)) {
									view.annotations[i].x -= view.dragOffsetX;
									view.annotations[i].y -= view.dragOffsetY;
								}
								
								if (view.annotations[i].points && jQuery.isArray(view.annotations[i].points)) {
									for(var pointIndex in view.annotations[i].points) {
										if (!jQuery.isNumeric(pointIndex)) { continue; }
											
										view.annotations[i].points[pointIndex].x = parseFloat(view.annotations[i].points[pointIndex].x) + dx - view.dragOffsetX;
										view.annotations[i].points[pointIndex].y = parseFloat(view.annotations[i].points[pointIndex].y) + dy - view.dragOffsetY;
									}
								}
								view.annotations[i].tx += (view.annotations[i].x - origX);
								view.annotations[i].ty += (view.annotations[i].y - origY);
								
								break;
						}
						
						
						view.make_annotation_dirty(i);
						view.isAnnotationTranslation = true;
 }
						return;
                    },
                    
                    add_annotation: function(type, x, y) {
                    	if (!options.use_annotations || !options.display_annotations || options.lock_annotations) { return; }
                    	
                    	// create text block
						var textBlock = document.createElement("div");
						
						
						var t = options.annotation_prefix_text + (options.default_annotation_text ? options.default_annotation_text : (options.show_empty_annotation_label_text_in_text_boxes ? options.empty_annotation_label_text : ''));
						jQuery(textBlock).attr('id', 'tileviewerAnnotationTextBlock_' + view.annotations.length).addClass("tileviewerAnnotationTextBlock").data("annotationIndex", view.annotations.length).html(t);
						jQuery('#tileviewerAnnotationTextBlock_' + view.annotations.length).remove();
						$this.append(textBlock);
						if (options.annotation_text_display_mode == 'simultaneous') { jQuery(textBlock).show(); }
						
						var w = jQuery($this).width();
						var h = jQuery($this).height();
						
                    	switch(type) {
                    		default:
                    		case 'rect':
                    			var lw = w/((layer.info.tilesize * layer.xtilenum) + layer.tilesize_xlast);
                    			var defaultWidth = 0.20 * lw * 100;			// default width of rect is 20% of visible screen width
                    			if (defaultWidth <= 0) { defaultWidth = 10; }
                    			
                    			var lh = h/((layer.info.tilesize * layer.ytilenum) + layer.tilesize_ylast);
                    			var defaultHeight = 0.2 * lh * 100;			// default width of rect is 20% of visible screen height
                    			if (defaultHeight <= 0) { defaultHeight = 10; }
                    			 
                    			view.annotations.push({
									type: type, x: x, y: y, w: defaultWidth, h: defaultHeight, index: view.annotations.length,
									tx: x, ty: y + defaultHeight, tw: defaultWidth, th: (120/layer.info.width) * 100,
									label: options.default_annotation_text, textBlock: textBlock
								});
								break;
							case 'point':
								var lw = w/((layer.info.tilesize * layer.xtilenum) + layer.tilesize_xlast);
                    			var defaultWidth = 0.01 * lw * 100;			// default width of rect is 1% of visible screen width
                    			if (defaultWidth <= 0) { defaultWidth = 10; }
                    			
                    			var lh = h/((layer.info.tilesize * layer.ytilenum) + layer.tilesize_ylast);
                    			var defaultHeight = 0.10 * lh * 100;			// default width of rect is 10% of visible screen height
                    			if (defaultHeight <= 0) { defaultHeight = 10; }
                    			
								view.annotations.push({
									type: type, x: x, y: y, w: defaultWidth, h: defaultHeight, index: view.annotations.length,
									tx: x + 3, ty: y + 3, tw: 10, th: (120/layer.info.width) * 100,
									label: options.default_annotation_text, textBlock: textBlock
								});
								break;
							case 'poly':
								var lw = w/((layer.info.tilesize * layer.xtilenum) + layer.tilesize_xlast);
                    			var defaultWidth = 0.20 * lw * 100;			// default width of rect is 20% of visible screen width
                    			if (defaultWidth <= 0) { defaultWidth = 10; }
                    			var defaultTxOffset = 0.25 * lw * 100;			// default width of rect is 25% of visible screen width
                    			if (defaultTxOffset <= 0) { defaultTxOffset = 10; }
                    			
                    			var lh = h/((layer.info.tilesize * layer.ytilenum) + layer.tilesize_ylast);
                    			var defaultTyOffset = 0.10 * lh * 100;			// default width of rect is 10% of visible screen height
                    			if (defaultTyOffset <= 0) { defaultTyOffset = 10; }
                    			
								view.annotations.push({
									type: type, x: x, y: y, w: 0, h: 0, index: view.annotations.length,
									tx: x + defaultTxOffset, ty: y + defaultTyOffset, tw: defaultWidth, th: (120/layer.info.width) * 100,
									label: options.default_annotation_text, textBlock: textBlock,
									points:[{x: x, y: y}]
								});
								break;
						}
						view.save_annotations([view.annotations.length-1], []);
						
						if (options.annotation_text_display_mode == 'simultaneous') { 
							// Make just-created annotation text block draggable
							view._makeAnnotationTextBlockDraggable('#tileviewerAnnotationTextBlock_' + (view.annotations.length-1));
						}
						view.draw_annotations();
						
						
						// Select just-created annotation
						jQuery('#tileviewerAnnotationTextBlock_' + (view.annotations.length-1)).click();
						
						if (type != 'poly') {
							// Revert current tool to pan
							jQuery("#" + options.id + "ControlPanImage").click();
						}
						
						return view.annotations.length - 1;	// return index of newly added annotation
                    },
                    
                    add_annotation_point: function(type, x, y) {
                    	if (!options.use_annotations || !options.display_annotations || options.lock_annotations) { return; }
                    	if (view.polygon_in_progress_annotation_index === null) { return; }
                    	
						var w = jQuery($this).width();
						var h = jQuery($this).height();
						
                    	switch(type) {
                    		default:
							case 'poly':
								if(!view.annotations[view.polygon_in_progress_annotation_index]) { return; }
								view.annotations[view.polygon_in_progress_annotation_index].points.push({x: x, y: y});
								break;
						}
						
						if (view.annotations[view.polygon_in_progress_annotation_index].points.length >= 3) {
							view.save_annotations([view.polygon_in_progress_annotation_index], []);
						}
						
						view.draw_annotations();
                    },
                    
                    insert_annotation_point: function(i, pointIndex, x, y) {
                    	if (!options.use_annotations || !options.display_annotations || options.lock_annotations) { return; }
                    	if (!view.annotations[i]) return; 
                    	
                    	console.log(x, y, i, pointIndex);
                    	
                    	view.annotations[i].points.splice(pointIndex + 1, 0, {x: x, y: y});
                    	view.make_annotation_dirty(i);
                    	
                    	console.log(x, y, i, pointIndex, view.annotations[i]);
                    },
                    
                    delete_annotation: function(i) {
                    	if (!options.use_annotations || !options.display_annotations || options.lock_annotations) { return; }
                    	if (!view.annotations[i]) return; 
                    	
						view.save_annotations([], [i]);
						view.commit_annotation_changes();
						
						$('#tileviewerAnnotationTextBlock_' + i).remove();
                    	view.annotations[i] = null;
                    	if (view.selectedAnnotation == i) { view.selectedAnnotation = null; }
                    	
                    	// Hide any annotation text boxes
                    	if (options.annotation_text_display_mode != 'simultaneous') { jQuery('.tileviewerAnnotationTextBlock').css("display", "none"); }
                    	jQuery(view.annotationTextEditor).css("display", "none").blur();
                    	
                    	view.polygon_in_progress_annotation_index = null;
                    	view.needdraw = true;
                    },
                    
                	delete_annotation_point: function(i, pointIndex) {
                    	if (view.annotations[i] && view.annotations[i]['points'] && view.annotations[i]['points'][pointIndex]  && (view.annotations[i]['points'].length >= 4)) {
                    		view.annotations[i]['points'].splice(pointIndex, 1);
                    		view.make_annotation_dirty(i);
                    	}
                    },
                    
                    mouseIsInAnnotation: function(x,y,e) {
                    	var mX = parseFloat(x);
                    	var mY = parseFloat(y);
                
                    	var foundAnnotation = false;
                    	jQuery.each(view.annotationAreas, function(k, v) {
                    		if(!v) { return true; }
                    		
                    		view.mouseClickedOnControlPoint = null;
                    		switch(v['type']) {
                    			case 'poly':
                    				// are we clicking on a line or point?
                    				var points = view.annotations[k].points;
                    				
                    				if (view.isAnnotationTranslation && (k == view.selectedAnnotation)) { return true; }
                    				
                    				var segments = points.slice();
                    				segments.push({x: segments[0].x, y: segments[0].y});	// add end point so auto-added final segment can be clicked-upon
                    				for(var pointIndex in segments) {
										if (!jQuery.isNumeric(pointIndex)) { continue; }
										
                    					pointIndex = parseInt(pointIndex);
                    					if (pointIndex+1 > segments.length) { break; }
                    					var p1 = segments[pointIndex];
                    					var p2 = segments[pointIndex+1];
                    					if (!p1 || !p2) { continue; }
                    					
                    					p1.x = parseFloat(p1.x);
                    					p1.y = parseFloat(p1.y);
                    					p2.x = parseFloat(p2.x);
                    					p2.y = parseFloat(p2.y);
                    					
                    					// point?
                    					var pointTolerance = 0.9;
                    					if (
                    						(Math.abs(mX - parseFloat(p1.x)) < pointTolerance)
                    						&&
                    						(Math.abs(mY - parseFloat(p1.y)) < pointTolerance)
                    					) {
                    						foundAnnotation = v;
                    						
                    						if (e && e.altKey) {
                    						 	view.delete_annotation_point(v.index, pointIndex);
                    						}
                    						view.mouseClickedOnControlPoint = pointIndex;
											return false;
                    					}
                    					
                    					if (
                    						(Math.abs(mX - p2.x) < pointTolerance)
                    						&&
                    						(Math.abs(mY - p2.y) < pointTolerance)
                    					) {
                    						if (e && e.altKey) {
                    						 	view.delete_annotation_point(v.index, pointIndex + 1);
                    						}
                    						
                    						foundAnnotation = v;
                    						view.mouseClickedOnControlPoint = pointIndex + 1;
											return false;
                    					}
                    					
                    					// line?
                    					
                    					var lineTolerance = 0.5;
                    					var dx = (mX - p1.x)/(p2.x - p1.x);
                    					var dy = (mY - p1.y)/(p2.y - p1.y);
                    					if (
                    						(Math.abs(dx - dy) < lineTolerance)
                    						&&
                    						((mX >= p1.x) && (mX <= p2.x) || (mX >= p2.x) && (mX <= p1.x))
                    						&&
                    						((mY >= p1.y) && (mY <= p2.y) || (mY >= p2.y) && (mY <= p1.y))
                    					) { 
                    						if (e && e.altKey) {
                    						 	view.insert_annotation_point(v.index, pointIndex, mX, mY);
                    						}
                    						foundAnnotation = v;
											return false;
                    					}
                    				}
                    				
                    				break;
                    			default:
									if (
										(v['startX'] <= mX) && (v['endX'] >= mX)
										&&	
										(v['startY'] <= mY) && (v['endY'] >= mY)
									) {
										foundAnnotation = v;
										return false;
									} 
									break;
							}
                    		
                    	});
                    	
                    	return foundAnnotation;
                    },
                    
                    mouseIsInAnnotationText: function(x,y) {
                    	var mX = x;
                    	var mY = y;
                    	
                    	var foundAnnotation = false;
                    	jQuery.each(view.annotationAreas, function(k, v) {
                    		if (
                    			(v['tstartX'] <= mX) && (v['tendX'] >= mX)
                    			&&	
                    			(v['tstartY'] <= mY) && (v['tendY'] >= mY)
                    		) {
                    			foundAnnotation = v;
                    			return false;
                    		} 
                    		
                    	});
                    	
                    	return foundAnnotation;
                    },
                    
                    //
                    // Wrap up any in-progress annotation (only polygons can be "in-progress" currently)
                    //
                    completeInProgressAnnotation: function() {
                    	if (view.polygon_in_progress_annotation_index && view.annotations[view.polygon_in_progress_annotation_index]) {
							var p = view.annotations[view.polygon_in_progress_annotation_index];
							
							if (p.points.length < 3) {
								// Annotation must have at least 3 points to be saved
								view.delete_annotation(view.polygon_in_progress_annotation_index);
							}
						}
						view.polygon_in_progress_annotation_index = null;
                    },
                    
                    openAnnotationTextEditor: function(inAnnotation) {
                    	if (options.lock_annotation_text) { return; }
                    	if (!inAnnotation || !options.display_annotations) { console.log("failed to open annotation text editor"); return; }
						var sx = ((inAnnotation['tstartX']/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos;
						var sy = ((inAnnotation['tendY']/100) * ((layer.info.height/factor) * (layer.tilesize/256))) + layer.ypos;
						
						var sw = (((inAnnotation['tendX']/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos) - sx - 10;	// 10 = 2 * 5px padding
						
						// Set editing form
						var tText = (view.annotations[inAnnotation['index']]['label'] ?  view.annotations[inAnnotation['index']]['label'] : options.empty_annotation_editor_text);
						
						t = "<form><textarea id='tileviewerAnnotationTextLabel'>" + tText + "</textarea></form>";
					
                		$(view.annotationTextEditor).draggable();
						
						if (
							(view.annotations[inAnnotation['index']].type == 'point') 
							|| 
							(view.annotations[inAnnotation['index']].type == 'poly')
							||
							(options.allow_draggable_text_boxes_for_rects && (view.annotations[inAnnotation['index']].type == 'rect'))
						) {
							// Allow dragging of text for point and polygon annotations
							jQuery(view.annotationTextEditor).draggable("enable");
						} else {
							// All other annotation types are not draggable
							jQuery(view.annotationTextEditor).draggable("disable");
						}
					
						// Position text editor box, set text and make visible
						$(view.annotationTextEditor).css("left", sx + 'px').css('top', sy + 'px').html("<div class='textContentDeleteButton' id='tileviewerAnnotationDeleteButton'><img src='" + options.buttonUrlPath + "/x.png' alt='Delete'/></div><div class='textContent'>" + t + "</div>").css('width', sw + 'px').css("display", "block");
							
						
						if (!view.annotations[inAnnotation['index']]['label']) {
							jQuery("#tileviewerAnnotationTextLabel").on("focus", function(e) {
								jQuery(this).html("");
							});
						}
						
						$('#tileviewerAnnotationTextLabel').on("keydown", function(e) {		// Mark as needing to be saved
							jQuery(view.annotationTextEditor).data('dirty', inAnnotation);
						});
							
						$('#tileviewerAnnotationDeleteButton').on("click", function(e) {
							if (view.selectedAnnotation !== null) { view.delete_annotation(view.selectedAnnotation); }
						});
							
						$('#tileviewerAnnotationTextSave').on("click", function(e) {
							// Save annotation text
							if(!view.annotations[inAnnotation['index']]) { console.log("Could not save text; no annotation at index ", inAnnotation['index'], inAnnotation); }
							view.annotations[inAnnotation['index']]['label'] = jQuery('#tileviewerAnnotationTextLabel').val();
							view.make_annotation_dirty(inAnnotation['index']);
							view.save_annotations([inAnnotation['index']], []);
							view.draw();
						
							return false;
						}); 
					},

                   	make_annotation_dirty: function(i) {
                   	 	if (view.changedAnnotations.indexOf(i) === -1) { view.changedAnnotations.push(i); }
                   	},
                   	
                   	annotation_is_dirty: function(i) {
                   	 	return (view.changedAnnotations.indexOf(i) >= 0);
                   	},
                   	
                   	get_dirty_annotation_list: function(i) {
                   	 	return view.changedAnnotations;
                   	},
                   	
                   	clear_dirty_annotation_list: function(i) {
                   	 	view.changedAnnotations = [];
                   	},
                   	
                   	show_controls: function(s) {
                   		options.display_annotations = s;
                   		if (!s) {
                   			jQuery(view.controls).fadeOut(100);
                   		} else {
                   			jQuery(view.controls).fadeIn(100);
                   		}
                   		view.needdraw = true;
                   		view.draw();
                   	},
                    
                    update_controls: function() {
                        if (!$(view.controls).html()) {
                        	jQuery(view.controls).append("<div class='tileViewToolbarCol'> </div>");
                        	
                        	var d = $(view.controls).find(".tileViewToolbarCol");
                     	
					
					view.tools = {};
					view.tools['pan'] = "<a href='#' id='" + options.id + "ControlPanImage' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/pan_on.png' width='25' height='25'/></a>";
					if (options.use_annotations && options.show_annotation_tools) { 
						view.tools['point'] = "<a href='#' id='" + options.id + "ControlAddPointAnnotation' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/point.png' width='26' height='25'/></a>";		
						view.tools['rect'] = "<a href='#' id='" + options.id + "ControlAddRectAnnotation' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/rect.png' width='25' height='24'/></a>";
						view.tools['polygon'] = "<a href='#' id='" + options.id + "ControlAddPolygonAnnotation' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/polygon.png' width='28' height='25'/></a>";
						view.tools['lock'] = "<a href='#' id='" + options.id + "ControlLockAnnotations' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/locked.png' width='20' height='25'/></a>";
					}
					view.tools['overview'] = "<a href='#' id='" + options.id + "ControlOverview' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/navigator.png' width='27' height='23'/></a>";	
					view.tools['expand'] = "<a href='#' id='" + options.id + "ControlFitToScreen' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/expand.png' width='25' height='25'/></a>";	
					if (options.helpLoadUrl) {
						view.tools['help'] = "<a href='#' id='" + options.id + "ControlHelp' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/viewerhelp.png' width='25' height='25'/></a>";	
					}
					
					for(var k=0; k < options.toolbar.length; k++) {
						switch(options.toolbar[k]) {
							case 'separator':
								d.append("<div class='tileviewerControlDivider'> </div>");	
								break;
							default:
								d.append(view.tools[options.toolbar[k]]);
								break;
						}
					}
								
					//
					// Tools
					//
					jQuery("#" + options.id + "ControlPanImage").click(function() {
						options.add_point_annotation_mode = options.add_polygon_annotation_mode = options.add_rect_annotation_mode = false;
						options.pan_mode = !options.pan_mode;
						
						view.completeInProgressAnnotation();
						
						view.draw();
						jQuery(this).css("opacity", options.pan_mode ? 1.0 : 0.5).find('img').attr('src', options.pan_mode ? options.buttonUrlPath + '/pan_on.png' : options.buttonUrlPath + '/pan.png');
						jQuery("#" + options.id + "ControlAddRectAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/rect.png');
						jQuery("#" + options.id + "ControlAddPointAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/point.png');
						jQuery("#" + options.id + "ControlAddPolygonAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/polygon.png');
					});	
					
					if (options.use_annotations && options.show_annotation_tools) { 			
							//
							// Rectangular annotation
							//
							
							jQuery("#" + options.id + "ControlAddRectAnnotation").click(function() {
								if (!options.display_annotations || options.lock_annotations) { return; }
								
								options.add_rect_annotation_mode = !options.add_rect_annotation_mode;
								options.add_point_annotation_mode = options.add_polygon_annotation_mode = false;
								options.pan_mode = false;
								
								view.completeInProgressAnnotation();
								
								view.draw();
								jQuery(this).css("opacity", options.add_rect_annotation_mode ? 1.0 : 0.5).find('img').attr('src', options.add_rect_annotation_mode ? options.buttonUrlPath + '/rect_on.png' : options.buttonUrlPath + '/rect.png');
								jQuery("#" + options.id + "ControlPanImage").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/pan.png');
								jQuery("#" + options.id + "ControlAddPointAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/point.png');
								jQuery("#" + options.id + "ControlAddPolygonAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/polygon.png');
								
								if (!options.add_rect_annotation_mode) {
									jQuery("#" + options.id + "ControlPanImage").click();
								}
							}).css("opacity", 0.5);
					
							//
							// Point annotation
							//
								
							jQuery("#" + options.id + "ControlAddPointAnnotation").click(function() {
								if (!options.display_annotations || options.lock_annotations) { return; }
								
								options.add_point_annotation_mode = !options.add_point_annotation_mode;
								options.add_rect_annotation_mode = options.add_polygon_annotation_mode = false;
								options.pan_mode = false;
								
								view.completeInProgressAnnotation();
								
								view.draw();
								jQuery(this).css("opacity", options.add_point_annotation_mode ? 1.0 : 0.5).find('img').attr('src', options.add_point_annotation_mode ? options.buttonUrlPath + '/point_on.png' : options.buttonUrlPath + '/point.png');
								jQuery("#" + options.id + "ControlPanImage").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/pan.png');
								jQuery("#" + options.id + "ControlAddRectAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/rect.png');
								jQuery("#" + options.id + "ControlAddPolygonAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/polygon.png');
								
								if (!options.add_point_annotation_mode) {
									jQuery("#" + options.id + "ControlPanImage").click();
								}
							}).css("opacity", 0.5);	
							
							//
							// Polygon annotation
							//
								
							jQuery("#" + options.id + "ControlAddPolygonAnnotation").click(function() {
								if (!options.display_annotations || options.lock_annotations) { return; }
								
								options.add_polygon_annotation_mode = !options.add_polygon_annotation_mode;
								options.add_rect_annotation_mode = add_point_annotation_mode = false;
								options.pan_mode = false;
								
								view.completeInProgressAnnotation();
								
								view.draw();
								jQuery(this).css("opacity", options.add_polygon_annotation_mode ? 1.0 : 0.5).find('img').attr('src', options.add_polygon_annotation_mode ? options.buttonUrlPath + '/polygon_on.png' : options.buttonUrlPath + '/polygon.png');
								jQuery("#" + options.id + "ControlPanImage").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/pan.png');
								jQuery("#" + options.id + "ControlAddRectAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/rect.png');
								jQuery("#" + options.id + "ControlAddPointAnnotation").css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/point.png');
								
								if (!options.add_polygon_annotation_mode) {
									jQuery("#" + options.id + "ControlPanImage").click();
								}
							}).css("opacity", 0.5);	
							
							//
							// Lock annotations
							//
								
							jQuery("#" + options.id + "ControlLockAnnotations").click(function() {
								options.lock_annotations = options.lock_annotation_text = !options.lock_annotations;
								if (options.lock_annotations) {
									view.selectedAnnotation = null;
								}
								view.draw();
								jQuery(this).css("opacity", options.lock_annotations ? 1.0 : 0.5).find('img').attr('src', options.buttonUrlPath + (options.lock_annotations ? '/locked_on.png' : '/locked.png'));
								
							}).css("opacity", 0.5);	
					}
					
					//
					// Overview
					// 
					jQuery("#" + options.id + "ControlOverview").click(function() {
						options.thumbnail = !options.thumbnail;
						view.draw();
						jQuery(this).css("opacity", options.thumbnail ? 1.0 : 0.5).find('img').attr('src', options.buttonUrlPath + (options.thumbnail ? '/navigator_on.png' : '/navigator.png'));
					}).css("opacity", 0.5);
					
                    //
					// Fit to screen
					// 
					
					jQuery("#" + options.id + "ControlFitToScreen").click(function() {
						
						jQuery(this).css("opacity", 1.0);
						var w = jQuery(view.canvas).width();
						var h = jQuery(view.canvas).height(); 
				
						//set initial level/size to fit the entire view
						var min = Math.min(w, h)/layer.info.tilesize; //number of tiles that can fit
						layer.level = layer.info._maxlevel - Math.floor(min) - 1;
						if (layer.level < 1) { layer.level = 0; }	// level can't be less than zero
						layer.tilesize = layer.info.tilesize;

						view.recalc_viewparams();
						layer.tilesize = Math.min((w/layer.xtilenum), (h/layer.ytilenum));

						// center image
						var factor = Math.pow(2,layer.level) * layer.info.tilesize / layer.tilesize;
						layer.xpos = view.canvas.clientWidth/2-layer.info.width/2/factor;
						layer.ypos = view.canvas.clientHeight/2-layer.info.height/2/factor;
						
						view.draw();
						jQuery("#" + options.id + "ZoomSlider").slider({value: view.current_zoom() * 100});
						
						jQuery(this).css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/expand.png');
					}).mouseover(function() {
						jQuery(this).css("opacity", 1.0).find('img').attr('src', options.buttonUrlPath + '/expand_on.png');
					}).mouseleave(function() {
						jQuery(this).css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/expand.png');
					}).css("opacity", 0.5); 
					
					//
					// Help
					// 
					if (options.helpLoadUrl) {
						jQuery("#" + options.id + "ControlHelp").click(function() {
							if (!jQuery('#tileviewerHelpPanel').length) {
								jQuery($this).append("<div id='tileviewerHelpPanel'></div>");
								jQuery("#tileviewerHelpPanel").css("left", ((jQuery($this).width() - jQuery("#tileviewerHelpPanel").width())/2)+"px").css("top", ((jQuery($this).height() - jQuery("#tileviewerHelpPanel").height())/2) + "px").load(
									options.helpLoadUrl, function(e) {
										jQuery("#tileviewerHelpPanel div.close a").on('click', function(e) {
											jQuery("#" + options.id + "ControlHelp").click();
										});
									}
								);
							
								jQuery(this).css("opacity", 1.0).find('img').attr('src', options.buttonUrlPath + '/viewerhelp_on.png');
							} else {
								if(!jQuery('#tileviewerHelpPanel').is(":visible")) {
									jQuery('#tileviewerHelpPanel').fadeIn(250);
									jQuery(this).css("opacity", 1.0).find('img').attr('src', options.buttonUrlPath + '/viewerhelp_on.png');
								} else {
									jQuery('#tileviewerHelpPanel').fadeOut(250);
									jQuery(this).css("opacity", 0.5).find('img').attr('src', options.buttonUrlPath + '/viewerhelp.png');
								}
							}
						
						}).css("opacity", 0.5);
					}
						
					//
					// Magnfier (deprecated)
					// 
					//d.append("<a href='#' id='" + options.id + "ControlMagnify' class='tileviewerControl'><img src='" + options.buttonUrlPath + "/magnify.png' width='33' height='24'/></a>");	
					
					//jQuery("#" + options.id + "ControlMagnify").click(function() {
					//	options.magnifier = !options.magnifier;
					//	view.draw();
					//	jQuery(this).css("opacity", options.magnifier ? 1.0 : 0.5);
					//});
					
							
					//
					// Zooming
					//		
					jQuery(view.controls).append("<div class='tileViewToolbarZoom'> </div>");
					var z = $(view.controls).find(".tileViewToolbarZoom");
					
					// center it
					jQuery(z).css("left", ((jQuery($this).width() - 500)/2) + "px");
					
					z.append("<a href='#' id='" + options.id + "ControlZoomIn' class='tileviewerControlZoomIn'><img src='" + options.buttonUrlPath + "/zoom_in.png' width='20' height='20'/></a>");
					z.append("<a href='#' id='" + options.id + "ControlZoomOut' class='tileviewerControlZoomOut'><img src='" + options.buttonUrlPath + "/zoom_out.png' width='20' height='20'/></a>");
					z.append("<div id='" + options.id + "ZoomSlider' class='tileViewToolbarZoomSlider'></div>");
					jQuery("#" + options.id + "ControlZoomIn").css("opacity", 0.5);
					jQuery("#" + options.id + "ControlZoomOut").css("opacity", 0.5);
					
			
					view.minZoom = (jQuery($this).width()/layer.info.width);
					if (view.minZoom > (t = jQuery($this).height()/layer.info.height)) {
						view.minZoom = t;
					}
					t *= 0.8;
					
					jQuery("#" + options.id + "ZoomSlider").slider({ min: view.minZoom * 100, max: 100, slide: function(e, ui) {
						var w = jQuery(view.canvas).width();
						var h = jQuery(view.canvas).height();
						view.zoom(ui.value/100, w/2, h/2);
					}});
					
					
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
							
							
							jQuery(document).bind('keypress.] keypress.= keypress.Shift_+', function() { 	// zoom in using keyboard "]" or "+"						
								var w = jQuery(view.canvas).width();
								var h = jQuery(view.canvas).height();
								view.change_zoom(15, w/2, h/2); 
								return false;
							});
							
							jQuery(document).bind('keypress.[ keypress.- keypress.Shift__', function() { 	// zoom out using keyboard "[" or "-"						
								var w = jQuery(view.canvas).width();
								var h = jQuery(view.canvas).height();
								view.change_zoom(-15, w/2, h/2); 
								return false;
							});
							
							jQuery(document).bind('keydown.left', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x - 50;
								view.pan.ydest = p.y;
								view.pan.level = layer.level;
								view.pan(); 
								return false;
							});
							jQuery(document).bind('keydown.right', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x + 50;
								view.pan.ydest = p.y;
								view.pan.level = layer.level;
								view.pan(); 
								return false;
							});
							jQuery(document).bind('keydown.up', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x;
								view.pan.ydest = p.y - 50;
								view.pan.level = layer.level;
								view.pan(); 
								return false;
							});
							
							jQuery(document).bind('keydown.c keydown.Shift_c', function() { // show/hide controls	
								jQuery(view.controls).fadeToggle(100);
								return false;
							});
							
							jQuery(document).bind('keydown.down', function() { 	
								var p = view.center_pixelpos();
								view.pan.xdest = p.x;
								view.pan.ydest = p.y + 50;
								view.pan.level = layer.level;
								view.pan(); 
								return false;
							});
							
							jQuery(document).bind('keydown.d keydown.Shift_d', function() { 
								if (!options.use_annotations || options.lock_annotations || !options.display_annotations) { return; }
								if (view.selectedAnnotation !== null) {
									view.delete_annotation(view.selectedAnnotation);
									view.selectedAnnotation = null;
									view.draw();
								}
								return false;
							});
							
							jQuery(document).bind('keydown.n keydown.Shift_n', function() { 
								jQuery("#" + options.id + "ControlOverview").click();
								return false;
							});
							
							jQuery(document).bind('keydown.h keydown.Shift_h', function() { 
								jQuery("#" + options.id + "ControlFitToScreen").click();
								return false;
							});
							
							jQuery(document).bind('keydown.tab', function() { 
								view.show_controls(!options.display_annotations);
								return false;
							});
							
							jQuery(document).bind('keydown.r keydown.Shift_r', function() { 
								jQuery("#" + options.id + "ControlAddRectAnnotation").click();
								return false;
							});
							
							jQuery(document).bind('keydown.p keydown.Shift_p', function() { 
								jQuery("#" + options.id + "ControlAddPointAnnotation").click();
								return false;
							});
							
							jQuery(document).bind('keydown.space', function() { 
								jQuery("#" + options.id + "ControlPanImage").click();
								return false;
							});
							
							jQuery(document).bind('keydown.l keydown.Shift_l', function() { 
								jQuery("#" + options.id + "ControlLockAnnotations").click();
								return false;
							});
							
							//
							// Touch events
							//
							
							// TODO: "swipemove" event seems to sometimes causes problems because some mouse actions related to resizing of annotations are 
							// incorrectly interpreted as swipes. Not sure what to do as of this writing (12.13.2013).
							//
							jQuery(view.canvas).bind('swipemove', function(e, m) {
                				var offset = $(view.canvas).offset();
								var desc = m.description.split(/:/);
								if ((desc[0] != 'swipemove') || (desc[1] != '1')) {
									return false;
								}
								
								if (view.pan && view.pan.xdest) {
									view.pan.xdest = view.pan.ydest = view.pan.leveldest = null; //cancel pan
								}
							
								if (view.dragAnnotation) {
									view.drag_annotation(view.dragAnnotation, m.delta[0].lastX, m.delta[0].lastY, m.originalEvent.pageX - offset.left, m.originalEvent.pageY - offset.top);
								} else {
									layer.xpos += m.delta[0].lastX;
									layer.ypos += m.delta[0].lastY;
								}
								view.draw();
								
								e.preventDefault();
								m.originalEvent.preventDefault();
								return false;
							});
							
							jQuery(view.canvas).bind('pinch', function(e, m) {
								var desc = m.description.split(/:/);
								if (desc[0] != 'pinch') {
									return false;
								}
								if (view.pan && view.pan.xdest) {
									view.pan.xdest = view.pan.ydest = view.pan.leveldest = null; //cancel pan
								}
								
								if (m && (m.scale !== null)) {
									var x = jQuery(view.canvas).data("touchx");
									var y = jQuery(view.canvas).data("touchy");
								
									var scale = m.scale;
									var old_tilesize = layer.tilesize;
									if (scale < 1) { scale = -1 * (1/scale); }
									scale = scale * (options.zoom_sensitivity/4);
									
									view.change_zoom(scale, x, y);
								}
								e.preventDefault();
								m.originalEvent.preventDefault();
								return false;
							});
							
							jQuery(view.canvas).bind('touchstart', function(e) {
								var x = parseInt(e.originalEvent.targetTouches[0].clientX);
								var y = parseInt(e.originalEvent.targetTouches[0].clientY);
								if (e.originalEvent.targetTouches.length > 1) {		
									// Average location of multiple touches							
									var x2 = parseInt(e.originalEvent.targetTouches[1].clientX);
									var y2 = parseInt(e.originalEvent.targetTouches[1].clientY);
									x = (x + x2)/2;
									y = (y + y2)/2;
								}
								
								jQuery(view.canvas).data("touchx", x).data("touchy", y);
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
                     
                     	var vOffset = jQuery($this).height() - layer.thumb.height;
                     	var hOffset = jQuery($this).width() - layer.thumb.width;
                     	if (options.magnifier) { vOffset = options.magnifier_view_size + 5; }

                        //draw thumbnail image
                        ctx.drawImage(layer.thumb, hOffset, vOffset, layer.thumb.width, layer.thumb.height);

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
                        
                        y += vOffset;
                        
                        ctx.strokeRect(hOffset + x, y, w, h);
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
                        ctx.drawImage(view.magnifier_canvas, 1, 1, layer.thumb.width, options.magnifier_view_size);
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

                    //calculate pixel position on the center
                    center_pixelpos: function() {
                        return view.client2pixel(view.canvas.clientWidth/2, view.canvas.clientHeight/2);
                    },

					zoom: function(zoom, x, y) {
						var maxZoom = options.maximum_pixelsize;
						if (zoom < 0) { return; }
						if (zoom > maxZoom) { return; }
						
						var w = jQuery(view.canvas).width();
						var h = jQuery(view.canvas).height();
						
						var targetWidth = layer.info.width * zoom;	// effective width we want
						
						var l = 0;									// level 0 = full resolution
						var s = layer.info.width;					// The width at level 0
						
						// Find first level with resolution lower than our target
						while((s > targetWidth) && (l<=layer.info._maxlevel)) {
							s /= 2;
							l++;
						}
						if (l > 0) { l--; s *= 2; }
						var f = targetWidth/s;
						
						//*before* changing tilesize, adjust offset so that we will zoom into where the cursor is
                        
                        var dist_from_x0 = x - layer.xpos;
                        var dist_from_y0 = y - layer.ypos;
                        
                        var delta_zoom = zoom - (view.current_zoom());
                        var lw = (layer.tilesize * layer.xtilenum) + layer.tilesize_xlast;
                        var lh = (layer.tilesize * layer.ytilenum) + layer.tilesize_ylast;
                        var dlw = layer.info.width * delta_zoom;
                        var dlh = layer.info.height * delta_zoom;
                        
                        layer.xpos -= (dist_from_x0/lw) * dlw;
                        layer.ypos -= (dist_from_y0/lh) * dlh;
                        
						layer.level = l;
						layer.tilesize = layer.info.tilesize * f;
						view.recalc_viewparams();
						
                        
						jQuery("#" + options.id + "ZoomSlider").slider({value: view.current_zoom() * 100});
                        view.needdraw = true;
					},
					
					current_zoom: function() {
						var tw = layer.info.width;
						var cw = (layer.tilesize * layer.xtilenum) + layer.tilesize_xlast;
						
						var x = (100 - (100/(Math.pow(2, layer.level))))/100;
						return (1-x)*layer.tilesize/256;
					},

                    change_zoom: function(delta, x, y) {
						var w = jQuery(view.canvas).width();
						var h = jQuery(view.canvas).height();
						
						var cl = layer.level;
						var ctilesize = layer.tilesize;
						var cxtilenum = layer.xtilenum;
						var cytilenum = layer.ytilenum;
						var cxpos = layer.xpos;
						var cypos = layer.ypos;
                        
                        //ignore if we've reached min/max zoom
                        if(layer.level == 0 && layer.tilesize+delta > layer.info.tilesize*options.maximum_pixelsize) { return false; }
						
						// Don't allow zooming smaller than window
						var ts = ((layer.tilesize + delta) > 16) ? layer.tilesize + delta : layer.tilesize;
						if ((delta < 0) && ((ts * (layer.xtilenum)) < w) && ((ts * (layer.ytilenum)) < h)) { return false; }
								
                        //*before* changing tilesize, adjust offset so that we will zoom into where the cursor is
                        var dist_from_x0 = x - layer.xpos;
                        var dist_from_y0 = y - layer.ypos;
                    
                        layer.xpos -= (dist_from_x0/layer.tilesize)*delta;
                        layer.ypos -= (dist_from_y0/layer.tilesize)*delta;
                        
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
                        	layer.xpos = cxpos;
                        	layer.ypos = cypos;
                        	layer.level = cl;
                        	view.pan.xdest = 0;
							view.pan.ydest = 0; 
							view.pan.level = cl;
							view.needdraw = true;
							view.pan(); 
							return;
                        }
                        
                        
						if ((layer.tilesize + delta) > 16) {
                  	      layer.tilesize += delta;
						}
						
                        //adjust level
                        if(layer.tilesize > layer.info.tilesize) { //level down
                            if(layer.level > 0) {
                                layer.level--;
                                layer.tilesize /= 2; //we can't use bitoperation here.. need to preserve floating point
                                view.recalc_viewparams();
                            }
                        } else {
							if(layer.tilesize < layer.info.tilesize/2) { //level up
								if(layer.level < layer.info._maxlevel) {
									layer.level++;
									layer.tilesize *= 2; //we can't use bitoperation here.. need to preserve floating point
									view.recalc_viewparams();
								}
							}
						}

						jQuery("#" + options.id + "ZoomSlider").slider({value: view.current_zoom() * 100});
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
                    }
                };//view definition
    
                $this.data("view", view);

                //setup views
                $this.addClass("tileviewer");

                $(view.canvas).css("width", "100%");
                $(view.canvas).css("height", "100%");

                $this.append(view.canvas);
                $(view.status).addClass("status");
                $this.append(view.status);
                
                $(view.controls).addClass("viewerControls");
                $this.append(view.controls);
                
                // 
            	// Begin ANNOTATIONS: init text editor
            	//
                $(view.annotationTextEditor).addClass("tileviewerAnnotationTextEditor");
                
						
				$(view.annotationTextEditor).on("blur", function(e) {
					var inAnnotation;
					if(inAnnotation = jQuery(view.annotationTextEditor).data('dirty')) {
						// Save changed text label
						jQuery(view.annotationTextEditor).data('dirty', null);
						
						if(!view.annotations[inAnnotation['index']]) { console.log("Could not save text; no annotation at index ", inAnnotation['index'], inAnnotation); }
						view.annotations[inAnnotation['index']]['label'] = jQuery('#tileviewerAnnotationTextLabel').val();
						view.make_annotation_dirty(inAnnotation['index']);
						view.save_annotations([inAnnotation['index']], []);
						view.draw();
					}
				});
                
                $this.append(view.annotationTextEditor);
                $(view.annotationTextEditor).draggable();
                
                $(view.annotationTextEditor).draggable({ drag: function(e) {
                	if(
                		view.selectedAnnotation != null
                		&&
                		view.annotations[view.selectedAnnotation]
                		&& 
                		(
                			(view.annotations[view.selectedAnnotation].type == 'point')
							|| 
							(view.annotations[view.selectedAnnotation].type == 'poly')
                			||
                			((view.annotations[view.selectedAnnotation].type == 'rect') && options.allow_draggable_text_boxes_for_rects)
                		)
                	) {
                		var pos = jQuery(view.annotationTextEditor).position();
			
						var factor = Math.pow(2,layer.level);
						var i = view.selectedAnnotation;
						view.annotations[i].tx = ((pos.left - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
						view.annotations[i].ty = ((pos.top - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
                	
                		view.draw();
                	}
                //	e.preventDefault();
                }, stop: function(e) {
					var pos = jQuery(view.annotationTextEditor).position();
			
					var factor = Math.pow(2,layer.level);
					var i = view.selectedAnnotation;
					view.annotations[i].tx = ((pos.left - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
					view.annotations[i].ty = ((pos.top - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
					
					view.save_annotations([view.selectedAnnotation], []);
                    view.commit_annotation_changes();
					view.draw();
					//e.preventDefault();
				}}).mouseup(function(e) {
					view.isAnnotationResize = view.isAnnotationTransformation = view.mousedown = view.dragAnnotation = null;
				});
				
                // Add click handler for textBlocks
                $(document).on("click", "div.tileviewerAnnotationTextBlock", function(e) {
                	if(options.lock_annotation_text) { return; }
					var offset = $(view.canvas).offset();
					if (!e) { e = { pageX:0, pageY: 0 }; }
					var x = e.pageX - offset.left;
					var y = e.pageY - offset.top;
					
					view.selectedAnnotation = jQuery(this).data("annotationIndex");
					view.needdraw = true;
					
					view.openAnnotationTextEditor(view.annotationAreas[view.selectedAnnotation]);
				
					return false;
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

				// center image
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

                //load thumbnail
                layer.thumb = new Image();
				layer.thumb.src = options.src+methods.getTilepicTileNum((layer.info._maxlevel), 0, layer)
				
				// load delete button
				view.deleteButtonImg = new Image();
				view.deleteButtonImg.src = options.buttonUrlPath + "/x.png";	
				
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
                    	if (!view.isAnnotationResize && !view.isAnnotationTranslation) {
                       	 view.pan();
                       	}
                    }

                    if(view.needdraw) {
                        view.draw();
                    }
                };
                draw_thread();

				
				if (options.use_annotations) {
					// load annotations
					view.load_annotations();
				}

                ///////////////////////////////////////////////////////////////////////////////////
                // Event handlers 
            	$(view.canvas).dblclick(function(e) {
                	if(
                		(view.polygon_in_progress_annotation_index !== null)
                		&&
                		(view.annotations[view.polygon_in_progress_annotation_index])
                		&&
                		(view.annotations[view.polygon_in_progress_annotation_index].type == 'poly')
                		&&
                		(view.annotations[view.polygon_in_progress_annotation_index].points.length >= 2)
                	) {
                		//
                		// Complete polygon on double-click
                		//
						view.polygon_in_progress_annotation_index = null;
						jQuery("#" + options.id + "ControlPanImage").click();
					}
                });
                
                $(view.canvas).mousedown(function(e) {
                    var offset = $(view.canvas).offset();
                    var x = e.pageX - offset.left;
                    var y = e.pageY - offset.top;
                    
                    var factor = Math.pow(2,layer.level);
                    
                    var x_relative = ((x - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
                    var y_relative = ((y - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
                    var buttonDiameter = (15/((layer.info.height/factor) * (layer.tilesize/256))) * 100;
                     
                 	if (options.use_annotations) {                       
						//
						// Handle annotations
						//
						view.dragAnnotation = view.isAnnotationResize = view.isAnnotationTranslation = null;
						jQuery("body").css('cursor', 'auto');
						var curAnnotation = null;
					
						if (!options.lock_annotations && options.display_annotations) {
							
							if (curAnnotation = view.mouseIsInAnnotation(
								x_relative,
								y_relative,
								e
							)) {
								view.selectedAnnotation = view.dragAnnotation = curAnnotation.index;
								view.dragAnnotationLastCoords = {x: x, y: y};
								view.needdraw = true;
							} else {                    	
								// Add annotation?
								if (options.add_rect_annotation_mode) {
									view.add_annotation('rect', x_relative, y_relative);
									return;
								}
								if (options.add_point_annotation_mode) {
									view.add_annotation('point', x_relative, y_relative);
									return;
								}
								if (options.add_polygon_annotation_mode) {
									if(view.polygon_in_progress_annotation_index === null) {
										view.polygon_in_progress_annotation_index = view.add_annotation('poly', x_relative, y_relative);
									} else {
										view.add_annotation_point('poly', x_relative, y_relative);
									}
									return;
								}
								view.selectedAnnotation = null;	// deselect current annotation
								view.needdraw = true;
							}
						}
					}
                    
					if(options.magnifier) {
						y -= (options.magnifier_view_size + 5);
					}	

					if (options.thumbnail) {
						// Handle scrolling due to click on the overview
						var tw = layer.thumb.width;
						var th = layer.thumb.height;
						var hOffset = jQuery($this).width() - tw;
						var vOffset = jQuery($this).height() - th;
						if ((x >= hOffset) && (x <= (hOffset + tw)) && (y >= vOffset) && (y <= vOffset + th)) {
							view.pan.xdest = (((x - hOffset)/tw) * layer.info.width);
							view.pan.ydest = (((y - vOffset)/th) * layer.info.height);
							view.pan.level = layer.level;
							view.needdraw = true;
							return;
						}
					}

//
// Begin ANNOTATIONS: mousedown handler
// 
					if (options.use_annotations && !options.lock_annotation_text) {
						//
						// Adjust annotation visibility on mousedown
						//
						var inAnnotation = null;
						if (inAnnotation = view.mouseIsInAnnotation(x_relative, y_relative)) {
							var sx = ((inAnnotation['tstartX']/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos;
							var sy = ((inAnnotation['tendY']/100) * ((layer.info.height/factor) * (layer.tilesize/256))) + layer.ypos;
						
							var sw = (((inAnnotation['tendX']/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos) - sx - 10;	// 10 = 2 * 5px padding
						
						
							var t = '';
							if (view.selectedAnnotation == inAnnotation['index']) {
								view.openAnnotationTextEditor(inAnnotation);	
							}
						} else {
							if(!view.selectedAnnotation) {
								if (options.annotation_text_display_mode != 'simultaneous') { $('.tileviewerAnnotationTextBlock').css("display", "none"); }
							}
						}
					 
						// Adjust text box position to reflect current view
						view.update_textbox_position(e);
					}
//
// End ANNOTATIONS: mousedown handler
// 

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
                    }
                    
                    return false;
                });

                //we want to capture mouseup on whole doucument - not just canvas
                $(document).mouseup(function(){
                    document.body.style.cursor="auto";
                    view.mousedown = false;
//
// Begin ANNOTATIONS: mouseup handler
// 
                    view.dragAnnotation = view.isAnnotationResize = view.isAnnotationTranslation = null;
                    if(view.annotation_is_dirty(view.selectedAnnotation)) {
                    	view.save_annotations([view.selectedAnnotation], []);
                    	view.commit_annotation_changes();
                    }
//
// End ANNOTATIONS: mouseup handler
// 
            		jQuery("body").css('cursor', 'auto');
                });

                $(view.canvas).mousemove(function(e) {
                    var offset = $(view.canvas).offset();
                    var x = e.pageX - offset.left;
                    var y = e.pageY - offset.top;
                    
                    view.xnow = x;
                    view.ynow = y;
                    
                    var factor = Math.pow(2,layer.level);
                    var x_relative = ((x - layer.xpos)/((layer.info.width/factor) * (layer.tilesize/256))) * 100;
                    var y_relative = ((y - layer.ypos)/((layer.info.height/factor) * (layer.tilesize/256))) * 100;

                    if(layer.info == null) { return false; }
                    

                    if(options.magnifier) {
                        //need to redraw magnifier
                        view.needdraw = true;
                    }
 
//
// Begin ANNOTATIONS: mousemove handler
//                    
                    if (options.use_annotations) {
                    	//
                    	// Drag annotation?
                    	//
                    	if (!options.lock_annotations && options.display_annotations && view.dragAnnotation) {
							view.drag_annotation(view.dragAnnotation, Math.ceil(x - view.dragAnnotationLastCoords.x), Math.ceil(y - view.dragAnnotationLastCoords.y), x, y);
						
							view.dragAnnotationLastCoords.x = x;
							view.dragAnnotationLastCoords.y = y;
							return;
						}
						
						//
						// Are we over a label?
						//
						var inAnnotation = null;
						if ((options.annotation_text_display_mode != 'simultaneous') && (inAnnotation = view.mouseIsInAnnotation(x_relative, y_relative, e))) {
							var sx = ((inAnnotation['tstartX']/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos;
							var sy = ((inAnnotation['tendY']/100) * ((layer.info.height/factor) * (layer.tilesize/256))) + layer.ypos;
						
							var sw = (((inAnnotation['tendX']/100) * ((layer.info.width/factor) * (layer.tilesize/256))) + layer.xpos) - sx - 10;	// 10 = 2 * 5px padding
						
						
							if (view.selectedAnnotation == inAnnotation['index'] && !options.lock_annotation_text) {
								$(view.annotationTextEditor).css("display", "block");
								if (options.annotation_text_display_mode != 'simultaneous') { $(inAnnotation['textBlock']).fadeOut(250); }
							} else {
								// Mouseover non-selected annotation
								if (options.annotation_text_display_mode != 'simultaneous') { $(inAnnotation['textBlock']).fadeIn(250); }
								if (view.selectedAnnotation == null) { $(view.annotationTextEditor).fadeOut(250); }
							}
						} else {
							if (options.annotation_text_display_mode != 'simultaneous') { 
								$('.tileviewerAnnotationTextBlock').fadeOut(250);
								if((view.selectedAnnotation) && !options.lock_annotation_text) {
									$(view.annotationTextEditor).fadeIn(250);
								}
							}
						}
                    }
//
// End ANNOTATIONS: mousemove handler
//                    

                    if(view.mousedown) {
                        //dragging
                        switch(view.mode) {
                        case "pan":
                        	if (!options.pan_mode) { break; }		// don't allow panning if disabled
                            layer.xpos = x - view.pan.xhot;
                            layer.ypos = y - view.pan.yhot;
                            view.draw();//TODO - should I call needdraw instead?
                            break;        
                        }
                    } else {
                        //just hovering
                        switch(view.mode) {
                        case "pan":
                            break;
                        }

                    }

                    //view.update_status(); //mouse position change doesn't cause view update.. so we have to call this 

                    //return false;
                });

                $(view.canvas).bind("mousewheel.tileviewer", function(e, delta) {
                    view.pan.xdest =  view.pan.ydest =  view.pan.leveldest = null;//cancel pan
                    delta = delta*options.zoom_sensitivity;
                	var offset = $(view.canvas).offset();
            		view.change_zoom(delta, e.pageX - offset.left, e.pageY - offset.top);
                    return false;
                });
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
    		if (i < 0) { console.log("Negative i=" + i); continue;}
    		//if (i >= methods.tileCounts.length) { console.log("Excessive i=" + i); continue;}
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