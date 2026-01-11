<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/GeocodeAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
use Geocoder\Query\GeocodeQuery;
define("__CA_ATTRIBUTE_VALUE_GEOCODE__", 4);

require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/Parsers/KmlParser.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
require_once(__CA_LIB_DIR__.'/GeographicMap.php');

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
	'mapWidth' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => '695px',
		'width' => 5, 'height' => 1,
		'label' => _t('Width of map display in user interface'),
		'description' => _t('Width in pixels of the display map.')
	),
	'mapHeight' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => '400px',
		'width' => 5, 'height' => 1,
		'label' => _t('Height of map display in user interface'),
		'description' => _t('Height in pixels of the display map.')
	),
	'pointsAreDirectional' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Points are directional'),
		'description' => _t('Check this option to enable setting of directions for point locations. (The default is not to be.)')
	),
	'autoDropPin' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => '20px', 'height' => 1,
		'label' => _t('Drop pin at search site?'),
		'description' => _t('Check this option if you want a pin to be placed at the location of geo-searches.')
	),
	'minZoomLevel' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_SELECT,
		'default' => 1,
		'options' => [
			0 => 0,
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			5 => 5,
			6 => 6,
			7 => 7,
			8 => 8,
			9 => 9,
			10 => 10,
			11 => 11,
			12 => 12,
			13 => 13,
			14 => 14,
			15 => 15,
			16 => 16,
			17 => 17,
			18 => 18
		],
		'width' => '100px', 'height' => 1,
		'label' => _t('Minimum zoom level'),
		'description' => _t('Minimum allowable zoom level for map.')
	),
	'maxZoomLevel' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_SELECT,
		'default' => 16,
		'options' => [
			0 => 0,
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			5 => 5,
			6 => 6,
			7 => 7,
			8 => 8,
			9 => 9,
			10 => 10,
			11 => 11,
			12 => 12,
			13 => 13,
			14 => 14,
			15 => 15,
			16 => 16,
			17 => 17,
			18 => 18
		],
		'width' => '100px', 'height' => 1,
		'label' => _t('Maximum zoom level'),
		'description' => _t('Maximum allowed zoom level for map.')
	),
	'defaultZoomLevel' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_SELECT,
		'default' => null,
		'options' => [
			_t('Auto') => -1,
			0 => 0,
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			5 => 5,
			6 => 6,
			7 => 7,
			8 => 8,
			9 => 9,
			10 => 10,
			11 => 11,
			12 => 12,
			13 => 13,
			14 => 14,
			15 => 15,
			16 => 16,
			17 => 17,
			18 => 18
		],
		'width' => '100px', 'height' => 1,
		'label' => _t('Default zoom level'),
		'description' => _t('Default zoom level for newly opened maps.')
	),
	'defaultLocation' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 1,
		'label' => _t('Default map location (enter as decimal &lt;latitude&gt;,&lt;longitude&gt;)'),
		'description' => _t('Default center location for newly opened maps. Set as decimal latitude and longitude values, separated by a comma.')
	),
	'doesNotTakeLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Does not use locale setting'),
		'description' => _t('Check this option if you don\'t want your georeferences to be locale-specific. (The default is to not be.)')
	),
	'singleValuePerLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow single value per locale'),
		'description' => _t('Check this option to restrict entry to a single value per-locale.')
	),
	'allowDuplicateValues' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow duplicate values?'),
		'description' => _t('Check this option if you want to allow duplicate values to be set when element is not in a container and is repeating.')
	),
	'raiseErrorOnDuplicateValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Show error message for duplicate values?'),
		'description' => _t('Check this option to show an error message when value is duplicate and <em>allow duplicate values</em> is not set.')
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
	'canMakePDF' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow PDF output?'),
		'description' => _t('Check this option if this metadata element can be output as a printable PDF. (The default is not to be.)')
	),
	'canMakePDFForValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow PDF output for individual values?'),
		'description' => _t('Check this option if individual values for this metadata element can be output as a printable PDF. (The default is not to be.)')
	),
	'mustNotBeBlank' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Must not be blank'),
		'description' => _t('Check this option if this attribute value must be set to some value - it must not be blank in other words. (The default is not to be.)')
	),
	'tileServerURL' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 1,
		'label' => _t('Tile server URL'),
		'validForRootOnly' => 0,
		'description' => _t('URL for tileserver to load custom tiles from, with placeholders for X, Y and Z values in the format <em>${x}</em>. Ex. http://tileserver.net/maps/${z}/${x}/${y}.png. Leave blank if you do not wish to use custom map tiles.')
	),
	'tileLayerName' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 1,
		'label' => _t('Tile layer name'),
		'validForRootOnly' => 0,
		'description' => _t('Display name for layer containing tiles loaded from tile server specified in the <em>tile server URL</em> setting.')
	),
	'layerSwitcherControl' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Show layer switcher controls'),
		'description' => _t('Check this option you want to include layer switching controls in the map.')
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
		'default' => '; ',
		'width' => 10, 'height' => 1,
		'label' => _t('Value delimiter'),
		'validForRootOnly' => 1,
		'description' => _t('Delimiter to use between multiple values when used in a display.')
	)
);

class GeocodeAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	/**
	 *
	 */
	protected $ops_text_value;
	
	/**
	 *
	 */
	protected $ops_path_value;
	
	/**
	 *
	 */
	protected $opn_latitude;
	
	/**
	 *
	 */
	protected $opn_longitude;
	
	/**
	 *
	 */
	private $opo_geo_plugin;
	# ------------------------------------------------------------------
	public function __construct($value_array=null) {
		parent::__construct($value_array);
		$this->opo_geo_plugin = new GeographicMap();
	}
	# ------------------------------------------------------------------
	public function loadTypeSpecificValueFromRow($value_array) {
		$this->ops_text_value = $value_array['value_longtext1'];
		$this->ops_path_value = $value_array['value_longtext2'];
		
		$this->opn_latitude = preg_replace('![0]+$!', '', $value_array['value_decimal1']);
		$this->opn_longitude = preg_replace('![0]+$!', '', $value_array['value_decimal2']);
	}
	# ------------------------------------------------------------------
	/**
	 * Returns value of geocode suitable for display output. This consists of the user's entry + the geocoded coordinates surrounded by brackets ("[" and "]").
	 * If you need to get the coordinates parsed out and suitable for mapping then pass the 'coordinates' option set to true; this will cause an array to be
	 * returned with keys for latitude, longitude (for the first point, if a path), path (a string with all coordinates in the path) and label (the display string)
	 *
	 * @param $options - options for generating display value. Supported options are:
	 *			coordinates - if passed a representation of the geocode value with coordinates parse is returned as an array. This array has the following keys:
	 *							latitude - the latitude of the first point in the geocode
	 *							longitude - the longitude of the first point in the geocode
	 *							path - a full path of coordinates (useful if the geocode is a path rather than a point) as a string with each coordinate pair separated with semicolons
	 *							label - the display text for the geocode
	 * 			path - only return path as plain text (the path is a colon-delimited list of coordinates)
	 *
	 * @return mixed - will return string with display value by default; array with parsed coordinate values if the "coordinates" option is passed
	 */
	public function getDisplayValue($options=null) {
		if(isset($options['coordinates']) && $options['coordinates']) {
			return array('latitude' => $this->opn_latitude, 'longitude' => $this->opn_longitude, 'path' => $this->ops_path_value, 'label' => $this->ops_text_value);
		}
		if(caGetOption('path', $options, false)) {
			return trim($this->ops_path_value);
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
	/**
	 *
	 */
	public function parseValue($value, $element_info, $options=null) {
		$settings = $this->getSettingValuesFromElementArray(
			$element_info, 
			['mustNotBeBlank']
		);
		
		$point = $angle = null;
		
		if (is_array($value) && ($value['_uploaded_file'] ?? null)) {		// KML file upload
			$o_kml = new KmlParser($value['_uploaded_file']);
			$placemarks = $o_kml->getPlacemarks();
			$features = [];
			foreach($placemarks as $placemark) {
				$coords = [];
				switch($placemark['type'] ?? null) {
					case 'POINT':
						$coords[] = $placemark['latitude'].','.$placemark['longitude'];
						break;
					case 'PATH':
						foreach($placemark['coordinates'] as $coordinate) {
							$coords[] = $coordinate['latitude'].','.$coordinate['longitude'];
						}	
						break;
				}
				if (sizeof($coords)) {
					$features[] = join(';', $coords);
				}
			}
			
			if (sizeof($features)) {
				$value = '['.join(':', $features).']';
			} else {
				$value = '';
			}
		}
		$value = trim(preg_replace("![\t\n\r]+!", ' ', $value));
		
		if (!trim($value)) {
			if ($settings['mustNotBeBlank']) {
				$this->postError(1970, _t('Address or georeference was blank.'), 'GeocodeAttributeValue->parseValue()');
				return false;
			} else {
				return ['value_longtext1' => '', 'value_longtext2' => '', 'value_decimal1' => null, 'value_decimal2' => null];
			}
		}
		

		// is it direct input (decimal lat, decimal long)?
		if(
			preg_match("!^([^\[]*)[\[]{1}([\d,\-\.;~]+)[\]]{0,1}$!", $value, $matches)
			||
			preg_match("!^([^\[]*)[\[]{1}([^\]]+)[\]]{1}$!", $value, $matches)
		) {

			$feature_list = preg_split("/[:]+/", $matches[2]);
			$feature_list_proc = [];
			foreach($feature_list as $feature) {
				$point_list = preg_split("/[;]+/", $feature);
				$parsed_points = [];
				$first_lat = $first_long = '';
				
				foreach($point_list as $point) {
					list($point, $radius) = array_pad(explode('~', $point), 2, null);
					if (!$radius) {
						list($point, $angle) = array_pad(explode('*', $point), 2, null);
					}
					
					// is it UTM?
					if (is_array($utm_to_latlong = caGISUTMToSignedDecimals($point))) {
						$parsed_points[] = $utm_to_latlong['latitude'].','.$utm_to_latlong['longitude'].(($radius > 0) ? "~{$radius}" : "");
						if (!$first_lat) { $first_lat = $utm_to_latlong['latitude']; }
						if (!$first_long) { $first_long = $utm_to_latlong['longitude']; }
					} else {
						$tmp = preg_split("/[ ]*[,\/][ ]*/", $point);
					
						if(sizeof($tmp) && strlen($tmp[0])) {
							// convert from degrees minutes seconds to decimal format
							if (caGISisDMS($tmp[0])) {
								$tmp[0] = caGISminutesToSignedDecimal($tmp[0]);
							} else {
								$tmp[0] = caGISDecimalToSignedDecimal($tmp[0]);
							}
							if(isset($tmp[1]) && strlen($tmp[1])) {
								if (caGISisDMS($tmp[1])) {
									$tmp[1] = caGISminutesToSignedDecimal($tmp[1]);
								} else {
									$tmp[1] = caGISDecimalToSignedDecimal($tmp[1]);
								}
							} else {
								$tmp[1] = '';
							}
						
							$parsed_points[] = $tmp[0].','.$tmp[1].(($radius > 0) ? "~{$radius}" : "").(($angle > 0) ? "*{$angle}" : "");
							if (!$first_lat) { $first_lat = $tmp[0]; }
							if (!$first_long) { $first_long = $tmp[1]; }
						}
					}
				}
				$feature_list_proc[] = join(';', $parsed_points);
			}
			return [
				'value_longtext1' => $matches[1],
				'value_longtext2' => join(':', $feature_list_proc),
				'value_decimal1' => $first_lat,		// latitude
				'value_decimal2' => $first_long		// longitude
			];	
		} elseif(preg_match("!^([\-]{0,1}[\d]{1,3})[\D]+([\d]{1,2})[\D]+([\d\.]+)[^NSEW]+([NSEW]{1})[\D]+([\-]{0,1}[\d]{1,3})[\D]+([\d]{1,2})[\D]+([\d\.]+)[^NSEW]+([NSEW]{1})!", $value, $matches)) {
			// Catch EXIFtool georefs (Ex. 53 deg 25' 56.40" 113 deg 54' 55.20")
			$first_lat = caGISminutesToSignedDecimal(join(' ', array_slice($matches, 0, 4)));
			$first_long = caGISminutesToSignedDecimal(join(' ', array_slice($matches, 5, 4)));
			
			return [
				'value_longtext1' => $matches[1],
				'value_longtext2' => "{$first_lat},{$first_long}",
				'value_decimal1' => $first_lat,	
				'value_decimal2' => $first_long
			];	
		} elseif($value = preg_replace("!\[[\d,\-\.]+\]!", "", $value)) {
			$geocoder = new \Geocoder\ProviderAggregator();
			$client  = new \GuzzleHttp\Client();
			
			$provider_list = [];
			
			if(!is_array($provider_conf = Configuration::load()->getList('geocode_providers'))) {
				$provider_conf = ['Nominatim'];
			}
			foreach($provider_conf as $p) {
				switch(strtolower($p)) {
					case 'nominatim':
						$provider_list[] = \Geocoder\Provider\Nominatim\Nominatim::withOpenStreetMapServer($client, __CA_APP_NAME__);
						break;
					case 'geonames':
						$provider_list[] = new \Geocoder\Provider\Geonames\Geonames($client, __CA_APP_NAME__);
						break;
					case 'googlemaps':
						if(!defined('__CA_GOOGLE_MAPS_KEY__') || !__CA_GOOGLE_MAPS_KEY__) { break; }
						$provider_list[] = new \Geocoder\Provider\GoogleMaps\GoogleMaps($client, __CA_APP_NAME__, __CA_GOOGLE_MAPS_KEY__);
						break;
				}
			}
			if(sizeof($provider_list) > 0) {
				$chain = new \Geocoder\Provider\Chain\Chain($provider_list);
				$geocoder->registerProvider($chain);
				
				$result = $geocoder->geocodeQuery(GeocodeQuery::create($value));
				
				try {
					if(!$result || !$result->first()){
						$this->postError(1970, _t('Could not geocode address "%1"', $value), 'GeocodeAttributeValue->parseValue()');
						return false;
					}
				} catch(\Geocoder\Exception\CollectionIsEmpty $e) {
					$this->postError(1970, _t('Could not geocode address "%1"', $value), 'GeocodeAttributeValue->parseValue()');
					return false;
				}
	
				$coords = $result->first()->getCoordinates();
				$lat = $coords->getLatitude();
				$long = $coords->getLongitude();
	
				if($lat && $long) {
					$res = [
						'value_longtext1' => $value,
						'value_longtext2' => $lat.','.$long,
						'value_decimal1' => $lat,
						'value_decimal2' => $long
					];
					if(caGetOption('returnBounds', $options, false)) {
						if($bounds = $result->first()->getBounds()) {
							$res['bounds'] = [
								'north' => $bounds->getNorth(),
								'east' => $bounds->getEast(),
								'south' => $bounds->getSouth(),
								'west' => $bounds->getWest()
							];
						}
					} 
					return $res;
				} else {
					$this->postError(1970, _t('Could not geocode address "%1"', $value), 'GeocodeAttributeValue->parseValue()');
					return false;
				}
			}
		}
		
		return [
			'value_longtext1' => '',
			'value_longtext2' => '',
			'value_decimal1' => null,		// latitude
			'value_decimal2' => null		// longitude
		];
		
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $element_info An array of information about the metadata element being edited
	 * @param array $options array Options include:
	 *			forSearch = simple text entry is returned for use with search forms [Default=false]
	 *			class = the CSS class to apply to all visible form elements [Default=lookupBg]
	 *			width = the width of the form element [Default=field width defined in metadata element definition]
	 *			height = the height of the form element [Default=field height defined in metadata element definition]
	 *
	 * @return string
	 */
	public function htmlFormElement($element_info, $options=null) {
		$class = trim((isset($options['class']) && $options['class']) ? $options['class'] : '');
		
		if (isset($options['forSearch']) && $options['forSearch']) {
			return caHTMLTextInput("{fieldNamePrefix}".$element_info['element_id']."_{n}", ['id' => "{fieldNamePrefix}".$element_info['element_id']."_{n}", 'value' => $options['value'], 'class' => $class], $options);
		}
		if ((!isset($options['baseLayer']) || !$options['baseLayer']) || (isset($options['request']) && ($options['request']))) {
			if ($base_layer_pref = $options['request']->user->getPreference('maps_base_layer')) {
				// Prefs don't have quotes in them, so we need to restore here
				$base_layer_pref = preg_replace("!\(([A-Za-z0-9_\-]+)\)!", "('\\1')", $base_layer_pref);
				$options['baseLayer'] = $base_layer_pref;
			}
		}
		return $this->opo_geo_plugin->getAttributeBundleHTML($element_info, array_merge([
			'zoomLevel' => caGetOption('defaultZoomLevel', $element_info['settings'], null),
			'minZoomLevel' => caGetOption('minZoomLevel', $element_info['settings'], null),
			'maxZoomLevel' => caGetOption('maxZoomLevel', $element_info['settings'], null),
			'defaultLocation' => caGetOption('defaultLocation', $element_info['settings'], null),
			'mapWidth' => caGetOption('mapWidth', $element_info['settings'], '695px'), 
			'mapHeight' => caGetOption('mapHeight', $element_info['settings'], '400px')
		], $options));
	}
	# ------------------------------------------------------------------
	public function getAvailableSettings($element_info=null) {
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
	/**
	 * Returns name of field in ca_attribute_values to use for query operations
	 *
	 * @return string Name of sort field
	 */
	public function queryFields() : ?array {
		return ['value_longtext1', 'value_longtext2'];
	}
	# ------------------------------------------------------------------
	/**
	 * Returns constant for geocode attribute value
	 * 
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_GEOCODE__;
	}
	# ------------------------------------------------------------------
}
