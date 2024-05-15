<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/InformationServiceAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2024 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__", 20);

require_once(__CA_LIB_DIR__.'/InformationServiceManager.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

global $_ca_attribute_settings;

$_ca_attribute_settings['InformationServiceAttributeValue'] = array(		// global
	'service' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => '',
		'width' => 90, 'height' => 1,
		'refreshOnChange' => 1,
		'label' => _t('Service'),
		'description' => _t('The type of information service to be accessed.')
	),
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
		'description' => _t('Check this option if you don\'t want your values to be locale-specific. (The default is to not be.)')
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
	),
	'sortUsingList' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'showLists' => true, 'allowNull' => true,
		'width' => 40, 'height' => 1,
		'label' => _t('Sort using list'),
		'description' => _t('List code for list to sort information service values on. Each item in the referenced list should have an identifier that matches the information service item uri or id and a rank that reflects the desired sort order. Leave empty to sort information service items by their value.')
	)
);

class InformationServiceAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	private $ops_text_value;
	private $ops_uri_value;
	/**
	 * @var IWLPlugInformationService|null
	 */
	private $opo_plugin = null;
	/**
	 * Extra indexing info
	 * @var array
	 */
	private $opa_indexing_info  = array();
	/**
	 * Extra info (this is actually get-able)
	 * @var array
	 */
	private $opa_extra_info = array();
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function __construct($pa_value_array=null) {
		parent::__construct($pa_value_array);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function loadTypeSpecificValueFromRow($pa_value_array) {
		$this->ops_text_value = $pa_value_array['value_longtext1'];
		$this->ops_uri_value = $pa_value_array['value_longtext2'];

		$va_info = caUnserializeForDatabase($pa_value_array['value_blob']);
		$this->opa_indexing_info =
			(is_array($va_info) && isset($va_info['indexing_info']) && is_array($va_info['indexing_info'])) ? $va_info['indexing_info'] : array();

		$this->opa_extra_info =
			(is_array($va_info) && isset($va_info['extra_info']) && is_array($va_info['extra_info'])) ? $va_info['extra_info'] : array();
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function getDisplayValue($pa_options=null) {
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function getTextValue(){
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function getUri(){
		return $this->ops_uri_value;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
		$ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
		$vs_service = caGetOption('service', $this->getSettingValuesFromElementArray(
			$pa_element_info, ['service']
		));
		
		if (trim($ps_value)) {
			$va_tmp = explode('|', $ps_value);
			$va_info = array();
			if(sizeof($va_tmp) == 3) { /// value is already in desired format (from autocomplete lookup)
				if ($va_tmp[2]) {	// Skip if no url set (is "no match" message)
					// get extra indexing info for this uri from plugin implementation
					$this->opo_plugin = InformationServiceManager::getInformationServiceInstance($vs_service);
					$vs_display_text = $this->opo_plugin->getDisplayValueFromLookupText($va_tmp[0]);
					$va_info['indexing_info'] = $this->opo_plugin->getDataForSearchIndexing($pa_element_info['settings'], $va_tmp[2]);
					$va_info['extra_info'] = $this->opo_plugin->getExtraInfo($pa_element_info['settings'], $va_tmp[2]);

					return array(
						'value_longtext1' => $vs_display_text,	// text
						'value_longtext2' => $va_tmp[2],		// uri
						'value_decimal1' => is_numeric($va_tmp[1]) && ($va_tmp[1] < pow(2, 64))  ? $va_tmp[1] : null, 		// id
						'value_blob' => caSerializeForDatabase($va_info),
						'value_sortable' => $this->sortableValue($vs_display_text)
					);
				}
			} elseif((sizeof($va_tmp)==1) && (isURL($va_tmp[0], array('strict' => true)) || is_numeric($va_tmp[0]))) { // URI or ID -> try to look it up. we match hit when exactly 1 hit comes back
				// try lookup cache
				if(CompositeCache::contains($va_tmp[0], "InformationServiceLookup{$vs_service}")) {
					return CompositeCache::fetch($va_tmp[0], "InformationServiceLookup{$vs_service}");
				}

				// try lookup
				$this->opo_plugin = InformationServiceManager::getInformationServiceInstance($vs_service);
				$va_ret = $this->opo_plugin->lookup($pa_element_info['settings'], $va_tmp[0]);

				// only match exact results. at some point we might want to try to get fancy
				// and pick one (or rather, have the plugin pick one) if there's more than one
				if(is_array($va_ret['results']) && (sizeof($va_ret['results']) == 1)) {
					$va_hit = array_shift($va_ret['results']);

					$va_info['indexing_info'] = $this->opo_plugin->getDataForSearchIndexing($pa_element_info['settings'], $va_hit['url']);
					$va_info['extra_info'] = $this->opo_plugin->getExtraInfo($pa_element_info['settings'], $va_hit['url']);
					$vs_display_text = $this->opo_plugin->getDisplayValueFromLookupText($va_hit['label']);
					$va_return = array(
						'value_longtext1' => $vs_display_text,	// text
						'value_longtext2' => $va_hit['url'],	// url
						'value_decimal1' => $va_hit['id'], 	// id
						'value_blob' => caSerializeForDatabase($va_info),
						'value_sortable' => $this->sortableValue($vs_display_text)
					);
				} else {
					$this->postError(1970, _t('Value for InformationService lookup has to be an ID or URL that returns exactly 1 hit. We got more or no hits. Value was %1', $ps_value), 'ListAttributeValue->parseValue()');
					return false;
				}

				CompositeCache::save($va_tmp[0], $va_return, "InformationServiceLookup{$vs_service}");
				return $va_return;
			} elseif((sizeof($va_tmp) == 1) && (preg_match("!^\[([0-9]+)\][ ]*(.*)!", $va_tmp[0], $m))) {   // [ID] TEXT format string where ID is numeric
			    return [
			        'value_longtext1' => $m[2],
			        'value_longtext2' => '',
			        'value_decimal1' => is_numeric($m[1]) ? $m[1] : null,
			        'value_blob' => null,
			        'value_sortable' => $this->sortableValue($m[2])
			    ];
			    
			} else { // raw text
				$this->opo_plugin = InformationServiceManager::getInformationServiceInstance($vs_service);
				$res = $this->opo_plugin->lookup($pa_element_info['settings'], $ps_value);
				$selected_result = null;
				if(is_array($res['results'] ?? null) && sizeof($res['results'])) {
					$v = mb_strtolower($ps_value);
					foreach($res['results'] as $r) {
						if(mb_strtolower($r['label']) === $v) {
							$selected_result = $r;
							break;
						}
					}
					if(!$selected_result) { $selected_result = array_shift($res['results']); }
				}
				if($selected_result && !caGetOption('isRecursive', $pa_options, false)) {
					return self::parseValue($selected_result['url'], $pa_element_info, array_merge($pa_options, ['isRecursive' => true]));
				}
				if(!$selected_result) {
					return null;
				}
				return [
			        'value_longtext1' => $ps_value,
			        'value_longtext2' => '',
			        'value_decimal1' => null,
			        'value_blob' => null,
			        'value_sortable' => $this->sortableValue($ps_value)
			    ];
			}
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $pa_element_info An array of information about the metadata element being edited
	 * @param array $pa_options array Options include:
	 *			class = the CSS class to apply to all visible form elements [Default=lookupBg]
	 *			width = the width of the form element [Default=field width defined in metadata element definition]
	 *			height = the height of the form element [Default=field height defined in metadata element definition]
	 *			request = the RequestHTTP object for the current request; required for lookups to work [Default is null]
	 *
	 * @return string
	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight'));
		$ps_class = caGetOption('class', $pa_options, 'lookupBg');
		$pb_for_search = caGetOption('forSearch', $pa_options, false);
		
		$vs_service = caGetOption('service', $this->getSettingValuesFromElementArray(
			$pa_element_info, ['service']
		));
		
		if(!$this->opo_plugin) {
			$this->opo_plugin = InformationServiceManager::getInformationServiceInstance($vs_service);
		}
		
		$vs_element = '';
		
        if (!$pb_for_search) {
        	// Add additional UI elements for services that require them (Eg. Numishare)
        	$additional_ui_controls = method_exists($this->opo_plugin, 'getAdditionalFields') ? $this->opo_plugin->getAdditionalFields($pa_element_info) : [];
			$additional_ui_elements = trim(join(' ', array_map(function($v) { return $v['html']; }, $additional_ui_controls)));
			
    		$additional_ui_gets = array_map(function($v) { 
    			return "{$v['name']}: jQuery('#{$v['id']}').val()";
    		}, $additional_ui_controls);
    		
    		$additional_ui_gets_str = sizeof($additional_ui_gets) ? ','.join(',', $additional_ui_gets) : '';
    		
    		$additional_fields = $this->opo_plugin->getAdditionalFields($pa_element_info);
    		
    		$hidden_val = '{{'.$pa_element_info['element_id'].'}}';
    		if(is_array($additional_fields)) {
    			foreach($additional_fields as $f) {
    				switch($f['name']) {
    					case 'id':
    						$hidden_val .= "|{$vs_service}:{{id}}";
    						break;
    					default: 
    						$hidden_val .= "|{{{$f['name']}}}";
    						break;
    				}
    			}
    		}
    		
            $vs_element = '<div id="infoservice_'.$pa_element_info['element_id'].'_input{n}">'.
            	$additional_ui_elements.
                caHTMLTextInput(
                    '{fieldNamePrefix}'.$pa_element_info['element_id'].'_autocomplete{n}',
                    array(
                        'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
                        'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'],
                        'value' => '{{'.$pa_element_info['element_id'].'}}',
                        'maxlength' => 512,
                        'id' => "infoservice_".$pa_element_info['element_id']."_autocomplete{n}",
                        'class' => $ps_class
                    )
                ).
                caHTMLHiddenInput(
                    '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
                    array(
                        'value' => $hidden_val,
                        'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}'
                    )
                );

            if ($pa_options['request']) {
                $vs_url = caNavUrl($pa_options['request'], 'lookup', 'InformationService', 'Get', array('max' => 100, 'element_id' => $pa_element_info['element_id']));
                $vs_detail_url = caNavUrl($pa_options['request'], 'lookup', 'InformationService', 'GetDetail', array('element_id' => $pa_element_info['element_id']));
            } else {
                // hardcoded default for testing.
                $vs_url = '/index.php/lookup/InformationService/Get';
                $vs_detail_url = '/index.php/lookup/InformationService/GetDetail';
            }

            $vs_element .= " <a href='#' class='caInformationServiceMoreLink' id='{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}'>"._t("More &rsaquo;")."</a>";
            $vs_element .= "<div id='{fieldNamePrefix}".$pa_element_info['element_id']."_detail{n}' class='caInformationServiceDetail'>".($pa_options['request'] ? caBusyIndicatorIcon($pa_options['request']) : '')."</div></div>";
    				
            $vs_element .= "
                    <script type='text/javascript'>
                        jQuery(document).ready(function() {
                            jQuery('#infoservice_".$pa_element_info['element_id']."_autocomplete{n}').autocomplete(
                                {
                                    minLength: 3,delay: 800,
                                    source: function (request, response) {
										jQuery.get('{$vs_url}', {
												term: request.term{$additional_ui_gets_str}
											}, function (data) {
												response(JSON.parse(data));
											});
									},
                                    html: true,
                                    select: function(event, ui) {".((!$pb_for_search) ? "
                                        jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(ui.item.label + '|' + ui.item.idno + '|' + ui.item.url);" : 
                                        "jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(ui.item.label);"
                                    )."
                                    }
                                }
                            ).click(function() { this.select(); });
                    ";
                
            $vs_element .= "					if ('{{".$pa_element_info['element_id']."}}') {
                            jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}').css('display', 'inline').on('click', function(e) {
                                if (jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_detail{n}').css('display') == 'none') {
                                    jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_detail{n}').slideToggle(250, function() {
                                        jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_detail{n}').load('{$vs_detail_url}/id/{n}');
                                    });
                                    jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}').html('".addslashes(_t("Less &rsaquo;"))."');
                                } else {
                                    jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_detail{n}').slideToggle(250);
                                    jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}').html('".addslashes(_t("More &rsaquo;"))."');
                                }
                                return false;
                            });
                        }
            ";
        
            $vs_element .= "
                        });
                        </script>";
		} else {
		    $vs_element .= caHTMLTextInput(
                    '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
                    array(
                        'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
                        'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
                        'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'],
                        'value' => '{{'.$pa_element_info['element_id'].'}}',
                        'class' => $ps_class
                    )
                );
		}

		return $vs_element;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function getAvailableSettings($pa_element_info=null) {
		global $_ca_attribute_settings;
		if (!($vs_service = isset($pa_element_info['settings']['service']) ? $pa_element_info['settings']['service'] : null)) {
			$vs_service = isset($pa_element_info['service']) ? $pa_element_info['service'] : null;
		}
		
		$va_names = InformationServiceManager::getInformationServiceNames();
		if (!in_array($vs_service, $va_names)) {
			$vs_service = $va_names[0];
		}

		if ($this->opo_plugin = InformationServiceManager::getInformationServiceInstance($vs_service)) {
			$va_settings = $this->opo_plugin->getAvailableSettings() +  $_ca_attribute_settings['InformationServiceAttributeValue'] ;
			$va_settings['service']['options'] = InformationServiceManager::getInformationServiceNamesOptionList();
			$va_service = $va_settings['service'];
			unset($va_settings['service']);
			$va_settings = array('service' => $va_service) + $va_settings;
		} else {
			$va_settings = array();
		}
		return $va_settings;
	}
	# ------------------------------------------------------------------
	public function getDataForSearchIndexing() {
		return (is_array($this->opa_indexing_info) ? $this->opa_indexing_info : array());
	}
	# ------------------------------------------------------------------
	/**
	 * Get extra info
	 * @param null|string $ps_info_key Optional specific info key
	 * @return mixed
	 */
	public function getExtraInfo($ps_info_key=null) {
		if(!$ps_info_key) {
			return (is_array($this->opa_extra_info) ? $this->opa_extra_info : array());
		} else {
			return isset($this->opa_extra_info[$ps_info_key]) ? $this->opa_extra_info[$ps_info_key] : null;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Return list of additional values for display for information services such as Numisgare (and perhaps others)
	 * to support additional interface elements on-screen for a value.
	 *
	 * Returns an empty array for attribute services that don't support additional values.
	 *
	 * @return array
	 */
	public function getAdditionalDisplayValues() : array {
		$settings = ca_metadata_elements::getElementSettingsForId($this->opn_element_id);
		if ($this->opo_plugin = InformationServiceManager::getInformationServiceInstance($settings['service'])) {
			return $this->opo_plugin->getAdditionalFieldValues($this);
		}
		return [];
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
		return ['value_longtext1', 'value_longtext2', 'value_decimal1'];
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
	 * Returns constant for information service attribute value
	 *
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__;
	}
	# ------------------------------------------------------------------
}
