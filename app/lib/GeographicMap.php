<?php
/** ---------------------------------------------------------------------
 * app/lib/GeographicMap.php : generates maps with user-provided data
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2022 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage Geographic
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
  
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/GeographicMapItem.php');
 
 class GeographicMap {
 	# -------------------------------------------------------------------
 	private $opo_mapping_engine;
 	# -------------------------------------------------------------------
 	public function __construct($pn_width=null, $pn_height=null, $ps_id="map") {
 		// Get name of plugin to use
 		$o_config = Configuration::load();
 		$vs_plugin_name = $o_config->get('mapping_plugin');
 		
 		if (!file_exists(__CA_LIB_DIR__.'/Plugins/GeographicMap/'.$vs_plugin_name.'.php')) { throw new ApplicationException("Mapping plugin {$vs_plugin_name} does not exist"); }
 		
 		require_once(__CA_LIB_DIR__.'/Plugins/GeographicMap/'.$vs_plugin_name.'.php');
 		$vs_plugin_classname = 'WLPlugGeographicMap'.$vs_plugin_name;
 		$this->opo_mapping_engine = new $vs_plugin_classname;
 		$this->opo_mapping_engine->setDimensions($pn_width, $pn_height);
 		
 		$this->opo_mapping_engine->set('id', $ps_id);
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * 
 	 */
 	public function addMapItem($po_map_item) {
 		return $this->opo_mapping_engine->addMapItem($po_map_item);
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * 
 	 */
 	public function addMapItems($pa_map_items) {
 		return $this->opo_mapping_engine->addMapItems($pa_map_items);
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * 
 	 */
 	public function clearMapItems() {
 		return $this->opo_mapping_engine->clearMapItems();
 	}
 	# -------------------------------------------------------------------
	/**
	 *
	 */
	public function fitExtentsToMapItems($pa_options=null) {
		return $this->opo_mapping_engine->fitExtentsToMapItems($pa_options);
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	public function setExtent($pn_north, $pn_south, $pn_east, $pn_west) {
		return $this->opo_mapping_engine->setExtent($pn_north, $pn_south, $pn_east, $pn_west);
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	public function getExtent() {
		return $this->opo_mapping_engine->getExtent();
	}
	# -------------------------------------------------------------------
 	/**
 	 * Extract geographic data from a data object (model instance or search result) and load it for rendering
 	 *
 	 * @param $po_data_object BaseModel|SearchResult A model instance or search result object from which to extract data for the map
 	 * @param $ps_georeference_field_name string The name of the georeference or geonames attribute to plot map items with; should be in <table>.<element_code> format (eg. "ca_objects.map_coords")
 	 * @param $pa_options array Optional array of options; supported options include:
 	 *			label - attribute (or field) to use for a short label for the map point, in <table>.<element_code> format (eg. "ca_objects.idno" or "ca_objects.preferred_labels.name")
 	 *			content - attribute (or field) to use for info balloon content shown when map item is clicked, in <table>.<element_code> format (eg. "ca_objects.description"). The content of the field is used as-is, so you must apply any styling to the data before it is stored in the database. If you want to style content "on-the-fly" use contentView or contentTemplate
 	 *			contentTemplate - text template to use for info balloon content shown when map item is clicked; attributes in <table>.<element_code> format will be substituted when prefixed with a caret ("^"). Eg. "The title of this is ^ca_objects.preferred_labels.name and the date is ^ca_objects.creation_date"
 	 *			contentView - view to use to render info balloon content shown when map item is clicked; specify the view filename with path relative to the main view directory (eg. "Splash/splash_html.php"); the view will be passed a single variable, "data", containing the data object
 	 *			ajaxContentUrl - URL to use to load via AJAX in info balloon. The primary key of the item will be added to the URL as the "id" parameter. The AJAX handler referenced by the URL must return ready-to-display HTML suitable for display in an info balloon. Unlike all other balloon content options, ajaxContentUrl defers rendering of balloon content until viewed, and does not embed content in the initial HTML load. For these reasons it is usually the best performing and most scaleable option.
 	 *			checkAccess - array of access field values to filter data (item and representation level); omit or pass empty array to do no filtering
 	 *			viewPath - path to views; will use standard system view path if not defined
 	 *			request = current request; required for generation of editor links
 	 *			color = hex color to use for item marker; can include bundle display template tags for inclusion of colors stored in metadata elements
 	 * @return array Returns an array with two keys: 'points' = number of unique markers added to map; 'items' = number of result hits than were plotted at least once on the map
 	 */
 	public function mapFrom($po_data_object, $ps_georeference_field_name, $pa_options=null) {
		$po_request = caGetOption('request', $pa_options, null);
 		$pa_options['label'] = caGetOption('label', $pa_options, null);
 		$pa_options['content'] = caGetOption('content', $pa_options, null);
 		$vs_color = caGetOption('color', $pa_options, null);
 		$vb_render_label_as_link = caGetOption('renderLabelAsLink', $pa_options, false);
 		
 		
 		$vn_point_count = 0;
 		$vn_item_count = 0;
 
 		$va_tmp = explode('.', $ps_georeference_field_name);
 		$t_georef_instance = Datamodel::getInstance($va_tmp[0], true);
 		$vs_field_name = array_pop($va_tmp);
 		$vs_container_field_name = array_pop($va_tmp);
 		
 		$chk_related_for_access = null;
 		if ($is_related = ($t_georef_instance && ($t_georef_instance->tableName() !== $po_data_object->tableName()))) {
 		    if($t_georef_instance->hasField('access')) { $chk_related_for_access = $t_georef_instance->tableName().".access"; }
 		}
 			
 		
 		//
 		// Data object is a model instance?
 		//
 		if (is_subclass_of($po_data_object, 'BaseModel')) {
 		    $po_data_object = caMakeSearchResult($po_data_object->tableName(), [$po_data_object->getPrimaryKey()]);
		}
		
		//
 		// Data object is a search result?
 		//
 		if (is_subclass_of($po_data_object, 'SearchResult')) {
 		
 		    // If pulling coordinates from a related record (Eg. pulling coordinates from ca_places record related to ca_objects records)
 		    // then then attempt here to shift the context from the subject to the relationship. If we leave context on subject
 		    // then we're get labels for all places attached to a given object tagged on each coordinate, rather than having
 		    // each label linked to its specific coordinate.
 		                
            $t_instance = $po_data_object->getResultTableInstance();
            $vs_table = $t_instance->tableName();
            $vs_pk = $t_instance->primaryKey();
 		   
 		    if($is_related && is_array($path = Datamodel::getPath($t_georef_instance->tableName(), $po_data_object->tableName())) && (sizeof($path) === 3)) {
 		        $path = array_keys($path);
 		        
 		        $rel_ids = [];
 		        while($po_data_object->nextHit()) {
                    if(is_array($rel_ids_for_row = $po_data_object->get($path[1].".relation_id", ['returnAsArray' => true]))) {
                       $rel_ids = array_merge($rel_ids, $rel_ids_for_row);
                    }
                }
                if (sizeof($rel_ids)) {
                    $po_data_object = caMakeSearchResult($path[1], $rel_ids);
                }
 		    }
 		    
 			//$po_data_object->setOption('prefetch', 1000);
 			$va_access_values = null;
 			if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
 				$va_access_values = $pa_options['checkAccess'];
 			}
 			
 			
 			while($po_data_object->nextHit()) {
				if (is_array($va_access_values) && !in_array($po_data_object->get($chk_related_for_access ? $chk_related_for_access : "{$vs_table}.access"), $va_access_values)) {
					continue;
				}
 				if ($va_coordinates = $po_data_object->get($ps_georeference_field_name, array('coordinates' => true, 'returnWithStructure' => true, 'returnAllLocales' => false))) {
 					$vn_id = $po_data_object->get("{$vs_table}.{$vs_pk}");
 					
 					foreach($va_coordinates as $vn_element_id => $va_coord_list) {
                        foreach($va_coord_list as $vn_attribute_id => $va_geoname) {
                            if(isset($va_geoname[$vs_field_name])) {
                                $va_coordinate = $va_geoname[$vs_field_name];
                            } elseif(isset($va_geoname[$vs_container_field_name])) {
                                $va_coordinate =  $va_geoname[$vs_container_field_name];
                            } else {
                                $va_coordinate = $va_geoname;
                            }
                        
                            $vs_label = $vs_content = $vs_ajax_content = null;
                            
                            if (strlen($label_template = caGetOption('labelTemplate', $pa_options, null))) {
                                $vs_label = caProcessTemplateForIDs($label_template, $vs_table, array($vn_id), array());
                            } elseif (strlen($label = caGetOption('label', $pa_options, null))) {
								$vs_label = $po_data_object->get($label, array('returnAsLink' => $vb_render_label_as_link || (strpos($pa_options['contentTemplate'], "<l>") !== false)));
							} elseif(strlen($va_coordinate['label'])) {
								$vs_label = $va_coordinate['label'];
							} else {
								$vs_label = $va_coordinate['path'];
							}
                            
                            if (!is_null($vs_color) && $vs_color && (strpos($vs_color, '^') !== false)) {
                                $vs_color = caProcessTemplateForIDs($pa_options['color'], $vs_table, [$vn_id], ['returnAsLink' => false]);
                            } 
                            
                            if (isset($pa_options['ajaxContentUrl']) && $pa_options['ajaxContentUrl']) {
                                $vs_ajax_content = $pa_options['ajaxContentUrl'];
                            } else {
                                if (!is_null($pa_options['contentView']) && $pa_options['request']) {	
                                    $o_view = new View($pa_options['request'], (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $pa_options['request']->getViewsDirectoryPath());
                                    $o_view->setVar('data', $po_data_object);
                                    $o_view->setVar('access_values', $pa_options['checkAccess']);
                                    $vs_content = $o_view->render($pa_options['contentView']);
                                } else {
                                    if (!is_null($pa_options['contentTemplate'])) {
                                        $vs_content = caProcessTemplateForIDs($pa_options['contentTemplate'], $vs_table, [$vn_id], []);
                                    } else {
                                        if (!is_null($pa_options['content'])) {
                                            if ($pa_options['content']){ 
                                                $vs_content = $po_data_object->get($pa_options['content']);
                                            }
                                        } else {
                                            $vs_content = $va_coordinate['label'];
                                        }
                                    }
                                }
                            }
                    
                    
                            $va_path_items = preg_split("/[:]/", $va_coordinate['path']);
                        
                            foreach($va_path_items as $vs_path_item) {
                                $radius = $angle = null;
                                
                                $va_path = preg_split("/[;]/", $vs_path_item);
                                if (sizeof($va_path) > 1) {
                                    $va_coordinate_pairs = [];
                                    foreach($va_path as $vs_pair) {
                                        $va_pair = explode(',', $vs_pair);
                                        $va_coordinate_pairs[] = ['latitude' => $va_pair[0], 'longitude' => $va_pair[1]];
                                    }
                                    $this->addMapItem(new GeographicMapItem(['coordinates' => $va_coordinate_pairs, 'label' => $vs_label, 'content' => $vs_content, 'ajaxContentUrl' => $vs_ajax_content, 'ajaxContentID' => $vn_id, 'color' => $vs_color]));
                                } else {
                                    $va_coord = explode(',', $va_path[0]);
                                    list($lng, $radius) = explode('~', $va_coord[1]);
                                    if (!$radius) { list($lng, $angle) = explode('*', $va_coord[1]); }
                                    $d = ['latitude' => $va_coord[0], 'longitude' => $lng, 'label' => $vs_label, 'content' => $vs_content, 'ajaxContentUrl' => $vs_ajax_content, 'ajaxContentID' => $vn_id, 'color' => $vs_color];
                                    if ($radius) { $d['radius'] = $radius; }
                                    if ($angle) { $d['angle'] = $angle; }
                                    $this->addMapItem(new GeographicMapItem($d));
                                }
                            
                                //if (!$va_point_buf[$va_coordinate['latitude'].'/'.$va_coordinate['longitude']]) { $vn_point_count++;}
                                //$va_point_buf[$va_coordinate['latitude'].'/'.$va_coordinate['longitude']]++;
                            }
                        }
                        $vn_item_count++;
                        //if ($vn_item_count> 50){ break(3); }
                    }
				}
 			}	
		}
		
		return ['items' => $vn_item_count, 'points' => $vn_point_count];
	}
 	# -------------------------------------------------------------------
 	/**
 	 * Render map for output
 	 *
 	 * @param $ps_format - the format in which to render the map. Use 'HTML' for html output (no other formats are currently supported)
 	 * @param $pa_options - optional array of options, passed through to the render() method of the underlying mapping plugin. Options support will depend upon the plugin.
 	 * @return string - map output in specified format
 	 */
 	public function render($ps_format='HTML', $pa_options=null) {
 		return $this->opo_mapping_engine->render($ps_format, $pa_options);
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * Render map for output
 	 *
 	 * @param $ps_format - the format in which to render the map. Use 'HTML' for html output (no other formats are currently supported)
 	 * @param $pa_options - optional array of options, passed through to the render() method of the underlying mapping plugin. Options support will depend upon the plugin.
 	 * @return string - map output in specified format
 	 */
 	public function getAttributeBundleHTML($pa_element_info, $pa_options=null) {
 		return $this->opo_mapping_engine->getAttributeBundleHTML($pa_element_info, $pa_options);
 	}
 	# -------------------------------------------------------------------
 }
