<?php
/** ---------------------------------------------------------------------
 * app/lib/core/GeographicMap.php : generates maps with user-provided data
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 
 require_once(__CA_LIB_DIR__.'/core/GeographicMapItem.php');
 
 class GeographicMap {
 	# -------------------------------------------------------------------
 	private $opo_mapping_engine;
 	# -------------------------------------------------------------------
 	public function __construct($pn_width=null, $pn_height=null, $ps_id="map") {
 		// Get name of plugin to use
 		$o_config = Configuration::load();
 		$vs_plugin_name = $o_config->get('mapping_plugin');
 		
 		if (!file_exists(__CA_LIB_DIR__.'/core/Plugins/GeographicMap/'.$vs_plugin_name.'.php')) { die("Mapping plugin {$vs_plugin_name} does not exist"); }
 		
 		require_once(__CA_LIB_DIR__.'/core/Plugins/GeographicMap/'.$vs_plugin_name.'.php');
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
 		$vs_field_name = array_pop($va_tmp);
 			
 		$va_point_buf = array();	
 		
 		//
 		// Data object is a model instance?
 		//
 		if (is_subclass_of($po_data_object, 'BaseModel')) {
 			if ($po_data_object->hasField('access') && isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
 				if (!in_array($po_data_object->get('access'), $pa_options['checkAccess'])) {
 					return array('items' => 0, 'points' => 0);
 				}
 			}
 			
 			$vn_id = $po_data_object->getPrimaryKey();
 			if (is_array($va_coordinates = $po_data_object->get($ps_georeference_field_name, array('coordinates' => true, 'returnAsArray' => true)))) {
				foreach($va_coordinates as $vn_i => $va_geoname) {
					$va_coordinate = isset($va_geoname[$vs_field_name]) ? $va_geoname[$vs_field_name] : $va_geoname;
					
					$vs_label = $vs_content = $vs_ajax_content = null;
							
					if (!is_null($pa_options['labelTemplate'])) {
						$vs_label = caProcessTemplateForIDs($pa_options['labelTemplate'], $vs_table, array($vn_id), array('returnAsLink' => $vb_render_label_as_link || (strpos($pa_options['contentTemplate'], "<l>") !== false)));
					} else {
						if (!is_null($pa_options['label'])) {
							$vs_label = $po_data_object->get($pa_options['label'], array('returnAsLink' => $vb_render_label_as_link || (strpos($pa_options['contentTemplate'], "<l>") !== false)));
						} else {
							$vs_label = $va_coordinate['label'];
						}
					} 
					
					if (!is_null($vs_color)) {
						$vs_color = caProcessTemplateForIDs($vs_color, $vs_table, array($vn_id), array('returnAsLink' => false));
					} else {
						$vs_color = null;
					}
					
					if (isset($pa_options['ajaxContentUrl']) && $pa_options['ajaxContentUrl']) {
						$vs_ajax_content = $pa_options['ajaxContentUrl'];
					} else {
						if (!is_null($pa_options['contentView']) && $pa_options['request']) {	
							$o_view = new View($pa_options['request'],(isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $pa_options['request']->getViewsDirectoryPath());
							$o_view->setVar('data', $po_data_object);
							$o_view->setVar('access_values', $pa_options['checkAccess']);
							$vs_content = $o_view->render($pa_options['contentView']);
						} else {
							if (!is_null($pa_options['contentTemplate'])) {
								$vs_content = caProcessTemplateForIDs($pa_options['contentTemplate'], $po_data_object->tableName(), $po_data_object->get($po_data_object->tableName().".".$po_data_object->primaryKey(), array('returnAsArray' => true)), array('returnAsLink' => (strpos($pa_options['contentTemplate'], "<l>") !== false)));
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
					$va_path = explode(";", $va_coordinate['path']);
					
					if (sizeof($va_path) > 1) {
						$va_coordinate_pairs = array();
						foreach($va_path as $vs_pair) {
							$va_pair = explode(',', $vs_pair);
							$va_coordinate_pairs[] = array('latitude' => $va_pair[0], 'longitude' => $va_pair[1]);
						}
						$this->addMapItem(new GeographicMapItem(array('coordinates' => $va_coordinate_pairs, 'label' => $vs_label, 'content' => $vs_content, 'ajaxContentUrl' => $vs_ajax_content, 'ajaxContentID' => $vn_id, 'color' => $vs_color)));
					} else {
						$this->addMapItem(new GeographicMapItem(array('latitude' => $va_coordinate['latitude'], 'longitude' => $va_coordinate['longitude'], 'label' => $vs_label, 'content' => $vs_content, 'ajaxContentUrl' => $vs_ajax_content, 'ajaxContentID' => $vn_id, 'color' => $vs_color)));
					}
					if (!$va_point_buf[$va_coordinate['latitude'].'/'.$va_coordinate['longitude']]) { $vn_point_count++;}
					$va_point_buf[$va_coordinate['latitude'].'/'.$va_coordinate['longitude']]++;
				}
				
				$vn_item_count++;
			}
			
			return array('items' => $vn_item_count, 'points' => $vn_point_count);
		}
		
		//
 		// Data object is a search result?
 		//
 		if (is_subclass_of($po_data_object, 'SearchResult')) {
 			$po_data_object->setOption('prefetch', 1000);
 			$va_access_values = null;
 			if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
 				$va_access_values = $pa_options['checkAccess'];
 			}
 			
 			$t_instance = $po_data_object->getResultTableInstance();
 			$vs_table = $t_instance->tableName();
 			$vs_pk = $t_instance->primaryKey();
 			while($po_data_object->nextHit()) {
 				if ($va_access_values) {
 					if (!in_array($po_data_object->get('access'), $va_access_values)) {
 						continue;
 					}
 				}
 				
 				if ($va_coordinates = $po_data_object->get($ps_georeference_field_name, array('coordinates' => true, 'returnAsArray' => true, 'returnAllLocales' => true))) {
 					$vn_id = $po_data_object->get("{$vs_table}.{$vs_pk}");
 					$vs_table = $po_data_object->tableName();
 					foreach($va_coordinates as $vn_element_id => $va_geonames_by_locale) {
 						foreach($va_geonames_by_locale as $vn_locale_id => $va_coord_list) {
 							foreach($va_coord_list as $vn_attribute_id => $va_geoname) {
								$va_coordinate = isset($va_geoname[$vs_field_name]) ? $va_geoname[$vs_field_name] : $va_geoname;
							
								$vs_label = $vs_content = $vs_ajax_content = null;
					
								
								if (!is_null($pa_options['labelTemplate'])) {
									$vs_label = caProcessTemplateForIDs($pa_options['labelTemplate'], $vs_table, array($vn_id), array('returnAsLink' => $vb_render_label_as_link || (strpos($pa_options['contentTemplate'], "<l>") !== false)));
								} else {
									if (!is_null($pa_options['label'])) {
										$vs_label = $po_data_object->get($pa_options['label'], array('returnAsLink' => $vb_render_label_as_link || (strpos($pa_options['contentTemplate'], "<l>") !== false)));
									} else {
										$vs_label = $va_coordinate['label'];
									}
								} 
								
								if (!is_null($pa_options['color'])) {
									$vs_color = caProcessTemplateForIDs($pa_options['color'], $vs_table, array($vn_id), array('returnAsLink' => false));
								} else {
									$vs_color = null;
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
											$vs_content = caProcessTemplateForIDs($pa_options['contentTemplate'], $po_data_object->tableName(), $po_data_object->get($po_data_object->tableName().".".$po_data_object->primaryKey(), array('returnAsArray' => true)), array('returnAsLink' => (strpos($pa_options['contentTemplate'], "<l>") !== false)));
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
						
								$va_path = explode(";", $va_coordinate['path']);
							
								if (sizeof($va_path) > 1) {
									$va_coordinate_pairs = array();
									foreach($va_path as $vs_pair) {
										$va_pair = explode(',', $vs_pair);
										$va_coordinate_pairs[] = array('latitude' => $va_pair[0], 'longitude' => $va_pair[1]);
									}
									$this->addMapItem(new GeographicMapItem(array('coordinates' => $va_coordinate_pairs, 'label' => $vs_label, 'content' => $vs_content, 'ajaxContentUrl' => $vs_ajax_content, 'ajaxContentID' => $vn_id, 'color' => $vs_color)));
								} else {
									$this->addMapItem(new GeographicMapItem(array('latitude' => $va_coordinate['latitude'], 'longitude' => $va_coordinate['longitude'], 'label' => $vs_label, 'content' => $vs_content, 'ajaxContentUrl' => $vs_ajax_content, 'ajaxContentID' => $vn_id, 'color' => $vs_color)));
								}
								
								if (!$va_point_buf[$va_coordinate['latitude'].'/'.$va_coordinate['longitude']]) { $vn_point_count++;}
								$va_point_buf[$va_coordinate['latitude'].'/'.$va_coordinate['longitude']]++;
							}
							$vn_item_count++;
						}
					}
				}
 			}	
		}
		
		return array('items' => $vn_item_count, 'points' => $vn_point_count);
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
 ?>