<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/GeocodeAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/core/Parsers/KmlParser.php');
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 	require_once(__CA_LIB_DIR__.'/core/GeographicMap.php');
 	
 	require_once(__CA_APP_DIR__.'/helpers/gisHelpers.php');
 
 	global $_ca_attribute_settings;
 	
 	$_ca_attribute_settings['GeocodeAttributeValue'] = array(		// global
		'fieldWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 70,
			'width' => 5, 'height' => 1,
			'label' => _t('Width of data entry field in user interface'),
			'description' => _t('Width, in characters, of the field when displayed in a user interface.')
		),
		'fieldHeight' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 2,
			'width' => 5, 'height' => 1,
			'label' => _t('Height of data entry field in user interface'),
			'description' => _t('Height, in characters, of the field when displayed in a user interface.')
		),
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your georeferences to be locale-specific. (The default is to not be.)')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is not to be.)')
		),
		'canBeUsedInSearchForm' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in search form'),
			'description' => _t('Check this option if this attribute value can be used in search forms. (The default is to be.)')
		),
		'canBeUsedInDisplay' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in display'),
			'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
		),
		'mustNotBeBlank' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Must not be blank'),
			'description' => _t('Check this option if this attribute value must be set to some value - it must not be blank in other words. (The default is not to be.)')
		),
		'displayTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Display template'),
			'validForRootOnly' => 1,
			'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
		),
		'displayDelimiter' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => ',',
			'width' => 10, 'height' => 1,
			'label' => _t('Value delimiter'),
			'validForRootOnly' => 1,
			'description' => _t('Delimiter to use between multiple values when used in a display.')
		)
	);
 
	class GeocodeAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		private $ops_path_value;
 		private $opn_latitude;
 		private $opn_longitude;
 		
 		private $opo_geo_plugin;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 			$this->opo_geo_plugin = new GeographicMap();
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 			$this->ops_path_value = $pa_value_array['value_longtext2'];
 			
 			$this->opn_latitude = preg_replace('![0]+$!', '', $pa_value_array['value_decimal1']);
 			$this->opn_longitude = preg_replace('![0]+$!', '', $pa_value_array['value_decimal2']);
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Returns value of geocode suitable for display output. This consists of the user's entry + the geocoded coordinates surrounded by brackets ("[" and "]").
 		 * If you need to get the coordinates parsed out and suitable for mapping then pass the 'coordinates' option set to true; this will cause an array to be
 		 * returned with keys for latitude, longitude (for the first point, if a path), path (a string with all coordinates in the path) and label (the display string)
 		 *
 		 * @param $pa_options - options for generating display value. Supported options are:
 		 *			coordinates - if passed a representation of the geocode value with coordinates parse is returned as an array. This array has the following keys:
 		 *							latitude - the latitude of the first point in the geocode
 		 *							longitude - the longitude of the first point in the geocode
 		 *							path - a full path of coordinates (useful if the geocode is a path rather than a point) as a string with each coordinate pair separated with semicolons
 		 *							label - the display text for the geocode
 		 *
 		 * @return mixed - will return string with display value by default; array with parsed coordinate values if the "coordinates" option is passed
 		 */
		public function getDisplayValue($pa_options=null) {
			if(isset($pa_options['coordinates']) && $pa_options['coordinates']) {
				return array('latitude' => $this->opn_latitude, 'longitude' => $this->opn_longitude, 'path' => $this->ops_path_value, 'label' => $this->ops_text_value);
			}
			if (!$this->ops_text_value && $this->ops_path_value) {
				return "[".$this->ops_path_value."]";
			}
			if (!$this->ops_text_value && !$this->opn_latitude && !$this->opn_longitude) { return ''; }
			return trim(trim($this->ops_text_value). ' ['.trim($this->ops_path_value).']');
		}
		# ------------------------------------------------------------------
		public function getLatitude(){
			return $this->opn_latitude;
		}
 		# ------------------------------------------------------------------
		public function getLongitude(){
			return $this->opn_longitude;
		}
 		# ------------------------------------------------------------------
		public function getCoordinatePath(){
			return $this->ops_path_value;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info) {	
 			$va_settings = $this->getSettingValuesFromElementArray(
 				$pa_element_info, 
 				array('mustNotBeBlank')
 			);
 			
 			if (is_array($ps_value) && $ps_value['_uploaded_file']) {
 				$o_kml = new KmlParser($ps_value['tmp_name']);
 				$va_placemarks = $o_kml->getPlacemarks();
 				$va_features = array();
 				foreach($va_placemarks as $va_placemark) {
 					$va_coords = array();
 					switch($va_placemark['type']) {
 						case 'POINT':
 							$va_coords[] = $va_placemark['latitude'].','.$va_placemark['longitude'];
 							break;
 						case 'PATH':
 							foreach($va_placemark['coordinates'] as $va_coordinate) {
								$va_coords[] = $va_coordinate['latitude'].','.$va_coordinate['longitude'];
							}	
 							break;
 					}
 					if (sizeof($va_coords)) {
						$va_features[] = join(';', $va_coords);
					}
 				}
 				
 				if (sizeof($va_features)) {
 					$ps_value = '['.join(':', $va_features).']';
 				} else {
 					$ps_value = '';
 				}
 			}
 			$ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
 			
 			if (!trim($ps_value)) {
 				if ($va_settings['mustNotBeBlank']) {
 					$this->postError(1970, _t('Address or georeference was blank.'), 'GeocodeAttributeValue->parseValue()');
 					return false;
 				} else {
					return null;
				}
 			}

 			// is it direct input (decimal lat, decimal long)?
 			if(
 				preg_match("!^([^\[]*)[\[]{0,1}([\d,\-\.;]+)[\]]{0,1}$!", $ps_value, $va_matches)
 				||
 				preg_match("!^([^\[]*)[\[]{1}([^\]]+)[\]]{1}$!", $ps_value, $va_matches)
 			) {
 			
 				$va_feature_list = preg_split("/[:]+/", $va_matches[2]);
 				
 				$va_feature_list_proc = array();
 				foreach($va_feature_list as $vs_feature) {
 					$va_point_list = preg_split("/[;]+/", $vs_feature);
					$va_parsed_points = array();
					$vs_first_lat = $vs_first_long = '';
					foreach($va_point_list as $vs_point) {
						$va_tmp = preg_split("/[ ]*[,\/][ ]*/", $vs_point);
						
						// convert from degrees minutes seconds to decimal format
						if (caGISisDMS($va_tmp[0])) {
							$va_tmp[0] = caGISminutesToSignedDecimal($va_tmp[0]);
						} else {
							$va_tmp[0] = caGISDecimalToSignedDecimal($va_tmp[0]);
						}
						if (caGISisDMS($va_tmp[1])) {
							$va_tmp[1] = caGISminutesToSignedDecimal($va_tmp[1]);
						} else {
							$va_tmp[1] = caGISDecimalToSignedDecimal($va_tmp[1]);
						}
						
						$va_parsed_points[] = $va_tmp[0].','.$va_tmp[1];
						if (!$vs_first_lat) { $vs_first_lat = $va_tmp[0]; }
						if (!$vs_first_long) { $vs_first_long = $va_tmp[1]; }
					}
					$va_feature_list_proc[] = join(';', $va_parsed_points);
				}
 				return array(
					'value_longtext1' => $va_matches[1],
					'value_longtext2' => join(':', $va_feature_list_proc),
					'value_decimal1' => $vs_first_lat,		// latitude
					'value_decimal2' => $vs_first_long		// longitude
				);	
 			} else {
				$ps_value = preg_replace("!\[[\d,\-\.]+\]!", "", $ps_value);
				if ($ps_value) {
					if (!($r_fp = @fopen("http://maps.google.com/maps/geo?q=".urlencode($ps_value)."&key=$vs_google_map_key&sensor=false&output=csv&oe=utf8","r"))) {
						$this->postError(1970, _t('Could not connect to Google for geocoding'), 'GeocodeAttributeValue->parseValue()');
						return false;
					}
					$vs_geocoding = @fread($r_fp, 8192);

					$va_geocoding = explode(",", $vs_geocoding);
					if (($va_geocoding[0] == 200) && ($va_geocoding[2] != 0) && ($va_geocoding[3] != 0)) {
						return array(
							'value_longtext1' => $ps_value,
							'value_longtext2' => $va_geocoding[2].','.$va_geocoding[3],
							'value_decimal1' => $va_geocoding[2],		// latitude
							'value_decimal2' => $va_geocoding[3]		// longitude
						);
					} else {
						$this->postError(1970, _t('Could not geocode address: [%1] %2', $va_geocoding[0], $va_geocoding[1]), 'GeocodeAttributeValue->parseValue()');
						return false;
					}
				}
			}
			return array(
				'value_longtext1' => '',
				'value_longtext2' => '',
				'value_decimal1' => null,		// latitude
				'value_decimal2' => null		// longitude
			);
			
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * @param array $pa_element_info
 		 * @param array $pa_options Supported options are 
 		 *			forSearch = if true, elenent is returned for use in a search form
 		 *	@return string HTML for element		
 		 */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			if (isset($pa_options['forSearch']) && $pa_options['forSearch']) {
 				return caHTMLTextInput("{fieldNamePrefix}".$pa_element_info['element_id']."_{n}", array('id' => "{fieldNamePrefix}".$pa_element_info['element_id']."_{n}", 'value' => $pa_options['value']), $pa_options);
 			}
 			if ((!isset($pa_options['baseLayer']) || !$pa_options['baseLayer']) || (isset($pa_options['request']) && ($pa_options['request']))) {
 				if ($vs_base_layer_pref = $pa_options['request']->user->getPreference('maps_base_layer')) {
 					// Prefs don't have quotes in them, so we need to restore here
 					$vs_base_layer_pref = preg_replace("!\(([A-Za-z0-9_\-]+)\)!", "('\\1')", $vs_base_layer_pref);
 					$pa_options['baseLayer'] = $vs_base_layer_pref;
 				}
 			}
 			return $this->opo_geo_plugin->getAttributeBundleHTML($pa_element_info, $pa_options);
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings() {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['GeocodeAttributeValue'];
 		}
 		# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return 'value_decimal1';
		}
 		# ------------------------------------------------------------------
	}
 ?>