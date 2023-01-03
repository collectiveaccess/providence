<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/LCSHAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2022 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_LCSH__", 13);

require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;
	
$_ca_attribute_settings['LCSHAttributeValue'] = array(		// global
	'fieldWidth' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 60,
		'width' => 5, 'height' => 1,
		'label' => _t('Width of data entry field in user interface'),
		'description' => _t('Width, in characters, of the field when displayed in a user interface.')
	),
	'fieldHeight' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 1,
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
		'description' => _t('Check this option if you don\'t want your LCSH values to be locale-specific. (The default is to not be.)')
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
	'displayTemplate' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 4,
		'label' => _t('Display template'),
		'validForRootOnly' => 1,
		'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
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
	'displayDelimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '; ',
		'width' => 10, 'height' => 1,
		'label' => _t('Value delimiter'),
		'validForRootOnly' => 1,
		'description' => _t('Delimiter to use between multiple values when used in a display.')
	),
	'vocabulary' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => '',
		'width' => 90, 'height' => 1,
		'label' => _t('Vocabulary'),
		'description' => _t('Selects which vocabulary will be searched.'),
		'options' => array(
			_t('All vocabularies') => '',
			_t('LC Subject Headings') => 'cs:http://id.loc.gov/authorities/subjects',
			_t('LC Name Authority File') => 'cs:http://id.loc.gov/authorities/names',
			_t('LC Subject Headings for Children') => 'cs:http://id.loc.gov/authorities/childrensSubjects',
			_t('LC Genre/Forms File') => 'cs:http://id.loc.gov/authorities/genreForms',
			_t('Thesaurus of Graphic Materials') => 'cs:http://id.loc.gov/vocabulary/graphicMaterials',
			_t('Preservation Events') => 'cs:http://id.loc.gov/vocabulary/preservationEvents',
			_t('Preservation Level Role') => 'cs:http://id.loc.gov/vocabulary/preservationLevelRole',
			_t('Cryptographic Hash Functions') => 'cs:http://id.loc.gov/vocabulary/cryptographicHashFunctions',
			_t('Controlled Vocabulary for Rare Materials Cataloging') => 'cs:http://id.loc.gov/vocabulary/rbmscv',
			_t('MARC Relators') => 'cs:http://id.loc.gov/vocabulary/relators',
			_t('MARC Countries') => 'cs:http://id.loc.gov/vocabulary/countries',
			_t('MARC Geographic Areas') => 'cs:http://id.loc.gov/vocabulary/geographicAreas',
			_t('MARC Languages') => 'cs:http://id.loc.gov/vocabulary/languages',
			_t('ISO639-1 Languages') => 'cs:http://id.loc.gov/vocabulary/iso639-1',
			_t('ISO639-2 Languages') => 'cs:http://id.loc.gov/vocabulary/iso639-2',
			_t('ISO639-5 Languages') => 'cs:http://id.loc.gov/vocabulary/iso639-5',
		)
	)
);

class LCSHAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	private $ops_text_value;
	private $ops_uri_value;
	
	static $s_term_cache = array();
	static $s_term_cache_max_size = 2048;
	# ------------------------------------------------------------------
	public function __construct($value_array=null) {
		parent::__construct($value_array);
	}
	# ------------------------------------------------------------------
	public function loadTypeSpecificValueFromRow($value_array) {
		$this->ops_text_value = $value_array['value_longtext1'];
		$this->ops_uri_value =  $value_array['value_longtext2'];
	}
	# ------------------------------------------------------------------
	/**
	 * @param array $options Supported options are
	 *		asHTML = if set URL is returned as an HTML link to the LOC definition of the term
	 *		asText = if set only text portion, without LCSH identifier, is returned
	 *		text = synonym for asText
	 *		id = return LCSH identifer URI
	 *		idno = synonym for id
	 *      n = return LCSH id only 
	 * @return string The term
	 */
	public function getDisplayValue($options=null) {
		if (isset($options['asHTML']) && $options['asHTML']) {
			if (preg_match('!sh([\d]+)!', $this->ops_text_value, $matches)) {
				$value = preg_replace('!\[sh[\d]+\]!', '', $this->ops_text_value);
				return "<a href='http://id.loc.gov/authorities/sh".$matches[1]."' target='_lcsh_details'>".$value.'</a>';
			}
		} 
		if (caGetOption(['asText', 'text'], $options, false)) {
			return preg_replace('![ ]*\[[^\]]*\]!', '', $this->ops_text_value);
		}
		if (caGetOption(['id', 'idno'], $options, false) && preg_match('!\[([^\]]*)!',$this->ops_text_value, $matches)) {
			return $matches[1];
		}
		if (caGetOption('n', $options, false) && preg_match('!\[([^\]]*)!',$this->ops_text_value, $matches)) {
			$t = explode('/', $matches[1]);
			return array_pop($t);
		}
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	public function getTextValue(){
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	public function getUri(){
		return $this->ops_uri_value;
	}
	# ------------------------------------------------------------------
	/**
	 * @param string $value
	 * @param array $pelement_info
	 * @param array $options Options include:
	 *		matchUsingLOCLabel = Match term using LOC label data rather than LOC subject heading search. The former is much more restrictive. [Default is false]		
	 *
	 */
	public function parseValue($value, $element_info, $options=null) {
		if(isset(LCSHAttributeValue::$s_term_cache[$value])) {
			if (LCSHAttributeValue::$s_term_cache[$value] === false) { return null; }
			return LCSHAttributeValue::$s_term_cache[$value];
		}
		$o_config = Configuration::load();
		
		$value = trim(preg_replace("![\t\n\r]+!", ' ', $value));
		
		// Try to convert LCSH display format into parse-able format, to avoid unwanted lookups
		if(preg_match("!^([^\[]+)[ ]*\[(info:lc[^\]]+)\]!", $value, $matches)) {
			$value = $matches[0]."|".$matches[1];
		} elseif (preg_match("!^([^\[]+)[ ]*\[(sh[^\]]+)\]!", $value, $matches)) {
			// Convert old-style "[sh*]" format identifiers
			$value = $matches[0]."|".$matches[1];
		}
		
		if (trim($value)) {
			// parse <text>|<url> format
			$tmp = explode('|', $value);
			if (is_array($tmp) && (sizeof($tmp) > 1)) {
			
				$url = str_replace('info:lc/', 'http://id.loc.gov/authorities/', $tmp[1]);
			
				$tmp1 = explode('/', $tmp[1]);
				$id = array_pop($tmp1);
				LCSHAttributeValue::$s_term_cache[$value] = array(
					'value_longtext1' => trim($tmp[0]),						// text
					'value_longtext2' => trim($url),							// uri
					'value_decimal1' => is_numeric($id) ? $id : null	// id
				);
			} elseif (preg_match('!\[(http://[^\]]+)\]!', $value, $matches)) {
				// parse <text> [<url>] format
				$uri = $matches[1];
				$text = preg_replace('!\[http://([^\]]+)\]!', '', $value);
				
				$tmp1 = explode('/', $uri);
				$id = array_pop($tmp1);
				
				LCSHAttributeValue::$s_term_cache[$value] = array(
					'value_longtext1' => trim($text),						// text
					'value_longtext2' => trim($uri),							// uri
					'value_decimal1' => is_numeric($id) ? $id : null,		// id
					'value_sortable' => $this->sortableValue($tmp[0])
				);
			} else {
				// try to match on text using id.loc.gov service
				$value = str_replace(array("‘", "’", "“", "”"), array("'", "'", '"', '"'), $value);
				
				$service_url = null;
				if (caGetOption('matchUsingLOCLabel', $options, false)) {
					$service_url = "http://id.loc.gov/authorities/label/".rawurlencode($value);
				} elseif (preg_match("!http://id.loc.gov/authorities/[A-Za-z]+!", $value)) {
					$service_url = $value;
				}
				
				if($service_url) {
					$o_client = new Zend_Http_Client($service_url);
					$o_client->setConfig(array(
						'maxredirects' => 5,
						'timeout'      => 30));
				
					try {
						$o_response = $o_client->request(Zend_Http_Client::HEAD);
					} catch (Exception $e) {
						$this->postError(1970, _t('Could not connect to LCSH service for %1: %2', $value, $e->getMessage()), 'LCSHAttributeValue->parseValue()');
						return false;
					}

					$headers = $o_response->getHeaders();
		
					if ((isset($headers['X-preflabel'])) && $headers['X-preflabel']) {
						$url = $headers['X-uri'];
						$url_bits = explode("/", $url);
						$id = array_pop($url_bits);
						$label = $headers['X-preflabel'];
						
						$url = str_replace('http://id.loc.gov/', 'info:lc/', $url);
				
						if ($url) {
							LCSHAttributeValue::$s_term_cache[$value] = array(
								'value_longtext1' => trim($label)." [{$url}]",						// text
								'value_longtext2' => trim($url),							// uri
								'value_decimal1' => is_numeric($id) ? $id : null,	// id
								'value_sortable' => $this->sortableValue($label)
							);
						} else {
							$this->postError(1970, _t('Could not get results from LCSH service for %1 [%2]', $value, $service_url), 'LCSHAttributeValue->parseValue()');
							return false;
						}
					} else {
						$this->postError(1970, _t('Could not get results from LCSH service for %1 [%2]; status was %3', $value, $service_url, $vn_status), 'LCSHAttributeValue->parseValue()');
						return false;
					}
				} else {
					$settings = $this->getSettingValuesFromElementArray($element_info, array('vocabulary'));
					
					$feed_url = "https://id.loc.gov/search/?q=".rawurlencode($value)."&start=1&format=atom";
					if ($voc = $settings['vocabulary']) {
						$feed_url .= '&q='.rawurlencode($voc);
					}
				
					$feed = new SimpleXMLElement(file_get_contents($feed_url, false, stream_context_create([
					  'http'=> [
						'method'=>"GET",
						'header'=>"Accept-language: en\r\n" .
								  "User-Agent: CollectiveAccess/".__CollectiveAccess__." (Linux; en-us)\r\n" // i.e. An iPad 
					  ]
					])));
					
					if ($feed) {
						foreach($feed->entry as $item) {
							$title = trim($item->title);
							$links = $item->link;
							$o_url = is_array($links) ? array_shift($links) : $links;
							$url = trim($o_url->attributes()->href);
						
							$url_bits = explode("/", $url);
							$id = array_pop($url_bits);
						
							LCSHAttributeValue::$s_term_cache[$value] = array(
								'value_longtext1' => "{$title} [{$url}]",			// text
								'value_longtext2' => $url,							// uri
								'value_decimal1' => is_numeric($id) ? $id : null,	// id
								'value_sortable' => $this->sortableValue($title)
							);
							break;
						}
					}
				}	
			}
		}
		if (!isset(LCSHAttributeValue::$s_term_cache[$value])) {
			LCSHAttributeValue::$s_term_cache[$value] = false;
			return null;		// not an error, just skip it
		}
		
		if(is_array(LCSHAttributeValue::$s_term_cache ) && (sizeof(LCSHAttributeValue::$s_term_cache) > LCSHAttributeValue::$s_term_cache_max_size)) {
			LCSHAttributeValue::$s_term_cache = array($value => LCSHAttributeValue::$s_term_cache[$value]);
		}
		return LCSHAttributeValue::$s_term_cache[$value];
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $element_info An array of information about the metadata element being edited
	 * @param array $options array Options include:
	 *			forSearch = settings and options regarding visual text editor are ignored [Default=false]
	 *			class = the CSS class to apply to all visible form elements [Default=lookupBg]
	 *			width = the width of the form element [Default=field width defined in metadata element definition]
	 *			height = the height of the form element [Default=field height defined in metadata element definition]
	 *			request = the RequestHTTP object for the current request; required for lookups to work [Default is null]
	 *
	 * @return string
	 */
	public function htmlFormElement($element_info, $options=null) {
		$class = trim((isset($options['class']) && $options['class']) ? $options['class'] : '');
		if (isset($options['forSearch']) && $options['forSearch']) {
			return caHTMLTextInput("{fieldNamePrefix}".$element_info['element_id']."_{n}", array('id' => "{fieldNamePrefix}".$element_info['element_id']."_{n}", 'value' => $options['value'], 'class' => $class), $options);
		}
		$o_config = Configuration::load();
		
		$settings = $this->getSettingValuesFromElementArray($element_info, array('fieldWidth', 'fieldHeight'));
		
		$element = '<div id="lcsh_'.$element_info['element_id'].'_input{n}">'.
			caHTMLTextInput(
				'{fieldNamePrefix}'.$element_info['element_id'].'_autocomplete{n}', 
				array(
					'size' => (isset($options['width']) && $options['width'] > 0) ? $options['width'] : $settings['fieldWidth'], 
					'height' => (isset($options['height']) && $options['height'] > 0) ? $options['height'] : $settings['fieldHeight'], 
					'value' => '{{'.$element_info['element_id'].'}}', 
					'maxlength' => 512,
					'id' => "lcsh_".$element_info['element_id']."_autocomplete{n}",
					'class' => $class ? $class : 'lookupBg'
				)
			).
			caHTMLHiddenInput(
				'{fieldNamePrefix}'.$element_info['element_id'].'_{n}',
				array(
					'value' => '{{'.$element_info['element_id'].'}}', 
					'id' => '{fieldNamePrefix}'.$element_info['element_id'].'_{n}'
				)
			);
			
		if ($options['request']) {
			$url = caNavUrl($options['request'], 'lookup', 'LCSH', 'Get', array('max' => 100, 'element_id' => (int)$element_info['element_id']));
		} else {
			// hardcoded default for testing.
			$url = '/index.php/lookup/LCSH/Get';	
		}
		
		$element .= " <a href='#' class='caLCSHServiceMoreLink' id='{fieldNamePrefix}".$element_info['element_id']."_link{n}' target='_lcsh_details'>"._t("More &rsaquo;")."</a>";
	
		$element .= '</div>';
		$element .= "
			<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('#lcsh_".$element_info['element_id']."_autocomplete{n}').autocomplete(
						{ source: '{$url}', minLength: 3, delay: 800, 
							select: function( event, ui ) {
								jQuery('#{fieldNamePrefix}".$element_info['element_id']."_{n}').val(ui.item.label + ' [' + ui.item.idno + ']|' + ui.item.url);
							}
						}
					).click(function() { this.select(); });
					
					if ('{{".$element_info['element_id']."}}') {
						var re = /\[info:lc([^\]]+)\]/; 
						var r = re.exec('{{".$element_info['element_id']."}}');
						var lcsh_id = (r) ? r[1] : null;
						
						if (!lcsh_id) {
							re = /\[sh([^\]]+)\]/; 
							var r = re.exec('{{".$element_info['element_id']."}}');
							var lcsh_id = (r) ? '/authorities/subjects/sh' + r[1] : null;
						}
						
						if (lcsh_id) {
							jQuery('#{fieldNamePrefix}".$element_info['element_id']."_link{n}').css('display', 'inline').attr('href', 'http://id.loc.gov' + lcsh_id);
						}
					}
				});
			</script>
		";
		
		return $element;
	}
	# ------------------------------------------------------------------
	public function getAvailableSettings($element_info=null) {
		global $_ca_attribute_settings;
		
		return $_ca_attribute_settings['LCSHAttributeValue'];
	}
	# ------------------------------------------------------------------
	/**
	 * Returns name of field in ca_attribute_values to use for sort operations
	 * 
	 * @return string Name of sort field
	 */
	public function sortField() {
		return 'value_sortable';
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
	 * Returns sortable value for metadata value
	 *
	 * @param string $value
	 * 
	 * @return string
	 */
	public function sortableValue(?string $value) {
		return mb_strtolower(substr(trim($value), 0, 100));
	}
	# ------------------------------------------------------------------
	/**
	 * Returns constant for LCSH attribute value
	 * 
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_LCSH__;
	}
	# ------------------------------------------------------------------
}
