<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/LCSHAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2016 Whirl-i-Gig
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
  	
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__."/core/Zend/Http/Client.php");
 	require_once(__CA_LIB_DIR__.'/core/Zend/Feed.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Feed/Atom.php');
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 	
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
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 			$this->ops_uri_value =  $pa_value_array['value_longtext2'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * @param array $pa_options Supported options are
 		 *		asHTML = if set URL is returned as an HTML link to the LOC definition of the term
 		 *		asText = if set only text portion, without LCSH identifier, is returned
 		 *		text = synonym for asText
 		 *		id = return LCSH identifer
 		 *		idno = synonym for id
 		 * @return string The term
 		 */
		public function getDisplayValue($pa_options=null) {
			if (isset($pa_options['asHTML']) && $pa_options['asHTML']) {
				if (preg_match('!sh([\d]+)!', $this->ops_text_value, $va_matches)) {
					$vs_value = preg_replace('!\[sh[\d]+\]!', '', $this->ops_text_value);
					return "<a href='http://id.loc.gov/authorities/sh".$va_matches[1]."' target='_lcsh_details'>".$vs_value.'</a>';
				}
			} 
			if (caGetOption(['asText', 'text'], $pa_options, false)) {
				return preg_replace('![ ]*\[[^\]]*\]!', '', $this->ops_text_value);
			}
			if (caGetOption(['id', 'idno'], $pa_options, false) && preg_match('!\[([^\]]*)!',$this->ops_text_value, $va_matches)) {
				return $va_matches[1];
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
 		 * @param string $ps_value
 		 * @param array $pa_element_info
 		 * @param array $pa_options Options include:
 		 *		matchUsingLOCLabel = Match term using LOC label data rather than LOC subject heading search. The former is much more restrictive. [Default is false]		
 		 *
 		 */
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			if (isset(LCSHAttributeValue::$s_term_cache[$ps_value])) {
 				if (LCSHAttributeValue::$s_term_cache[$ps_value] === false) { return null; }
 				return LCSHAttributeValue::$s_term_cache[$ps_value];
 			}
 			$o_config = Configuration::load();
 			
 			$ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
 			
 			// Try to convert LCSH display format into parse-able format, to avoid unwanted lookups
 			if(preg_match("!^([^\[]+)[ ]*\[(info:lc[^\]]+)\]!", $ps_value, $va_matches)) {
 				$ps_value = $va_matches[0]."|".$va_matches[1];
 			} elseif (preg_match("!^([^\[]+)[ ]*\[(sh[^\]]+)\]!", $ps_value, $va_matches)) {
 				// Convert old-style "[sh*]" format identifiers
 				$ps_value = $va_matches[0]."|".$va_matches[1];
 			}
 			
			if (trim($ps_value)) {
				// parse <text>|<url> format
				$va_tmp = explode('|', $ps_value);
				if (sizeof($va_tmp) > 1) {
				
					$vs_url = str_replace('info:lc/', 'http://id.loc.gov/authorities/', $va_tmp[1]);
				
					$va_tmp1 = explode('/', $va_tmp[1]);
					$vs_id = array_pop($va_tmp1);
					LCSHAttributeValue::$s_term_cache[$ps_value] = array(
						'value_longtext1' => trim($va_tmp[0]),						// text
						'value_longtext2' => trim($vs_url),							// uri
						'value_decimal1' => is_numeric($vs_id) ? $vs_id : null	// id
					);
				} elseif (preg_match('!\[(http://[^\]]+)\]!', $ps_value, $va_matches)) {
					// parse <text> [<url>] format
					$vs_uri = $va_matches[1];
					$vs_text = preg_replace('!\[http://([^\]]+)\]!', '', $ps_value);
					
					$va_tmp1 = explode('/', $vs_uri);
					$vs_id = array_pop($va_tmp1);
					
					LCSHAttributeValue::$s_term_cache[$ps_value] = array(
						'value_longtext1' => trim($vs_text),						// text
						'value_longtext2' => trim($vs_uri),							// uri
						'value_decimal1' => is_numeric($vs_id) ? $vs_id : null		// id
					);
				} else {
					// try to match on text using id.loc.gov service
					$ps_value = str_replace(array("‘", "’", "“", "”"), array("'", "'", '"', '"'), $ps_value);
					
					if (caGetOption('matchUsingLOCLabel', $pa_options, false)) {
						$vs_service_url = "http://id.loc.gov/authorities/label/".rawurlencode($ps_value);
						$o_client = new Zend_Http_Client($vs_service_url);
						$o_client->setConfig(array(
							'maxredirects' => 0,
							'timeout'      => 30));
					
						try {
							$o_response = $o_client->request(Zend_Http_Client::HEAD);
						} catch (Exception $e) {
							$this->postError(1970, _t('Could not connect to LCSH service for %1: %2', $ps_value, $e->getMessage()), 'LCSHAttributeValue->parseValue()');
							return false;
						}

						// $vn_status = $o_response->getStatus();
						$va_headers = $o_response->getHeaders();
				
						if (($vn_status >= 300) && ($vn_status <= 399) && (isset($va_headers['X-preflabel'])) && $va_headers['X-preflabel']) {
							$vs_url = $va_headers['Location'];
							$va_url = explode("/", $vs_url);
							$vs_id = array_pop($va_url);
							$vs_label = $va_headers['X-preflabel'];
					
							$vs_url = str_replace('http://id.loc.gov/', 'info:lc/', $vs_url);
					
							if ($vs_url) {
								LCSHAttributeValue::$s_term_cache[$ps_value] = array(
									'value_longtext1' => trim($vs_label)." [{$vs_url}]",						// text
									'value_longtext2' => trim($vs_url),							// uri
									'value_decimal1' => is_numeric($vs_id) ? $vs_id : null	// id
								);
							} else {
								$this->postError(1970, _t('Could not get results from LCSH service for %1 [%2]', $ps_value, $vs_service_url), 'LCSHAttributeValue->parseValue()');
								return false;
							}
						} else {
							$this->postError(1970, _t('Could not get results from LCSH service for %1 [%2]', $ps_value, $vs_service_url), 'LCSHAttributeValue->parseValue()');
							return false;
						}
					} else {
						$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('vocabulary'));
						
						$vs_feed_url = "http://id.loc.gov/search/?q=".rawurlencode($ps_value)."&start=1&format=atom";
						if ($vs_voc = $va_settings['vocabulary']) {
							$vs_feed_url .= '&q='.rawurlencode($vs_voc);
						}
					
						$vb_feed_error = false;
						try {
							$feed = Zend_Feed::import($vs_feed_url);
						} catch (Exception $e) {
							$vb_feed_error = true;
						}
					
						if (!$vb_feed_error) {
							foreach($feed as $item){
							
								$vs_title = trim($item->title());
								$va_links = $item->link();
								$o_url = is_array($va_links) ? array_shift($va_links) : $va_links;
								$vs_url = trim($o_url->getAttribute('href'));
							
								$va_url = explode("/", $vs_url);
								$vs_id = array_pop($va_url);
							
								LCSHAttributeValue::$s_term_cache[$ps_value] = array(
									'value_longtext1' => "{$vs_title} [{$vs_url}]",						// text
									'value_longtext2' => $vs_url,							// uri
									'value_decimal1' => is_numeric($vs_id) ? $vs_id : null	// id
								);
							
							}
						}
					}	
				}
			}
			if (!isset(LCSHAttributeValue::$s_term_cache[$ps_value])) {
				LCSHAttributeValue::$s_term_cache[$ps_value] = false;
				return null;		// not an error, just skip it
			}
			
			if(sizeof(LCSHAttributeValue::$s_term_cache > LCSHAttributeValue::$s_term_cache_max_size)) {
				LCSHAttributeValue::$s_term_cache = array($ps_value => LCSHAttributeValue::$s_term_cache[$ps_value]);
			}
			return LCSHAttributeValue::$s_term_cache[$ps_value];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return HTML form element for editing.
 		 *
 		 * @param array $pa_element_info An array of information about the metadata element being edited
 		 * @param array $pa_options array Options include:
 		 *			forSearch = settings and options regarding visual text editor are ignored [Default=false]
 		 *			class = the CSS class to apply to all visible form elements [Default=lookupBg]
 		 *			width = the width of the form element [Default=field width defined in metadata element definition]
 		 *			height = the height of the form element [Default=field height defined in metadata element definition]
 		 *			request = the RequestHTTP object for the current request; required for lookups to work [Default is null]
 		 *
 		 * @return string
 		 */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : '');
 			if (isset($pa_options['forSearch']) && $pa_options['forSearch']) {
 				return caHTMLTextInput("{fieldNamePrefix}".$pa_element_info['element_id']."_{n}", array('id' => "{fieldNamePrefix}".$pa_element_info['element_id']."_{n}", 'value' => $pa_options['value'], 'class' => $vs_class), $pa_options);
 			}
 			$o_config = Configuration::load();
 			
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight'));
 			
 			$vs_element = '<div id="lcsh_'.$pa_element_info['element_id'].'_input{n}">'.
 				caHTMLTextInput(
 					'{fieldNamePrefix}'.$pa_element_info['element_id'].'_autocomplete{n}', 
					array(
						'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'], 
						'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'], 
						'value' => '{{'.$pa_element_info['element_id'].'}}', 
						'maxlength' => 512,
						'id' => "lcsh_".$pa_element_info['element_id']."_autocomplete{n}",
						'class' => $vs_class ? $vs_class : 'lookupBg'
					)
				).
				caHTMLHiddenInput(
					'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
					array(
						'value' => '{{'.$pa_element_info['element_id'].'}}', 
						'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}'
					)
				);
				
			if ($pa_options['request']) {
				$vs_url = caNavUrl($pa_options['request'], 'lookup', 'LCSH', 'Get', array('max' => 100, 'element_id' => (int)$pa_element_info['element_id']));
			} else {
				// hardcoded default for testing.
				$vs_url = '/index.php/lookup/LCSH/Get';	
			}
			
			$vs_element .= " <a href='#' class='caLCSHServiceMoreLink' id='{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}' target='_lcsh_details'>"._t("More &rsaquo;")."</a>";
		
			$vs_element .= '</div>';
			$vs_element .= "
				<script type='text/javascript'>
					jQuery(document).ready(function() {
						jQuery('#lcsh_".$pa_element_info['element_id']."_autocomplete{n}').autocomplete(
							{ source: '{$vs_url}', minLength: 3, delay: 800, 
								select: function( event, ui ) {
									jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(ui.item.label + ' [' + ui.item.idno + ']|' + ui.item.url);
     							}
     						}
						).click(function() { this.select(); });
						
						if ('{{".$pa_element_info['element_id']."}}') {
							var re = /\[info:lc([^\]]+)\]/; 
							var r = re.exec('{{".$pa_element_info['element_id']."}}');
							var lcsh_id = (r) ? r[1] : null;
							
							if (!lcsh_id) {
								re = /\[sh([^\]]+)\]/; 
								var r = re.exec('{{".$pa_element_info['element_id']."}}');
								var lcsh_id = (r) ? '/authorities/subjects/sh' + r[1] : null;
							}
							
							if (lcsh_id) {
								jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}').css('display', 'inline').attr('href', 'http://id.loc.gov' + lcsh_id);
							}
						}
					});
				</script>
			";
 			
 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings($pa_element_info=null) {
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
			return 'value_longtext1';
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
 ?>