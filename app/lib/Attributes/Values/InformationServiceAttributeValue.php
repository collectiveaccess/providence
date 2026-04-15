<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/InformationServiceAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2026 Whirl-i-Gig
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
	/**
	 *
	 */
	protected $ops_text_value;
	
	/**
	 *
	 */
	protected $ops_uri_value;
	
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
	public function __construct($value_array=null) {
		parent::__construct($value_array);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function loadTypeSpecificValueFromRow($value_array) {
		global $g_ui_locale;
		$this->ops_text_value = $value_array['value_longtext1'];
		$this->ops_uri_value = $value_array['value_longtext2'];

		$info = caUnserializeForDatabase($value_array['value_blob']);
		
		// Are there locale-specific display values?
		if(isset($info['extra_info']['values_by_locale']) && isset($info['extra_info']['values_by_locale'][$g_ui_locale])) {
			$this->ops_text_value = $info['extra_info']['values_by_locale'][$g_ui_locale];
		}
		
		$this->opa_indexing_info =
			(is_array($info) && isset($info['indexing_info']) && is_array($info['indexing_info'])) ? $info['indexing_info'] : array();

		$this->opa_extra_info =
			(is_array($info) && isset($info['extra_info']) && is_array($info['extra_info'])) ? $info['extra_info'] : array();
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function getDisplayValue($options=null) {
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
	public function parseValue($value, $element_info, $options=null) {
		if(!is_array($options)) { $options = []; }
		$value = trim(preg_replace("![\t\n\r]+!", ' ', $value));
		$service = caGetOption('service', $this->getSettingValuesFromElementArray(
			$element_info, ['service']
		));
		
		if (trim($value)) {
			$tmp = explode('|', $value);
			$info = array();
			if(sizeof($tmp) == 3) { /// value is already in desired format (from autocomplete lookup)
				if ($tmp[2]) {	// Skip if no url set (is "no match" message)
					// get extra indexing info for this uri from plugin implementation
					if(!($this->opo_plugin = InformationServiceManager::getInformationServiceInstance($service))) { 
						return null; 
					}
					$display_text = $this->opo_plugin->getDisplayValueFromLookupText($tmp[0]);
					$info['indexing_info'] = $this->opo_plugin->getDataForSearchIndexing($element_info['settings'], $tmp[2]);
					$info['extra_info'] = $this->opo_plugin->getExtraInfo($element_info['settings'], $tmp[2]);

					return array(
						'value_longtext1' => $display_text,	// text
						'value_longtext2' => $tmp[2],		// uri
						'item_id' => $info['extra_info']['item_id'] ?? null,
						'value_decimal1' => is_numeric($tmp[1]) && ($tmp[1] < pow(2, 64))  ? $tmp[1] : null, 		// id
						'value_blob' => caSerializeForDatabase($info),
						'value_sortable' => $this->sortableValue($display_text)
					);
				}
			} elseif((sizeof($tmp)==1) && (isURL($tmp[0], array('strict' => true)) || is_numeric($tmp[0]))) { // URI or ID -> try to look it up. we match hit when exactly 1 hit comes back
				// try lookup cache
				if(CompositeCache::contains($tmp[0], "InformationServiceLookup{$service}")) {
					return CompositeCache::fetch($tmp[0], "InformationServiceLookup{$service}");
				}

				// try lookup
				$this->opo_plugin = InformationServiceManager::getInformationServiceInstance($service);
				$ret = $this->opo_plugin->lookup($element_info['settings'], $tmp[0]);

				// only match exact results. at some point we might want to try to get fancy
				// and pick one (or rather, have the plugin pick one) if there's more than one
				if(is_array($ret['results']) && (sizeof($ret['results']) > 0)) {
					$hit = array_shift($ret['results']);

					$info['indexing_info'] = $this->opo_plugin->getDataForSearchIndexing($element_info['settings'], $hit['url']);
					$info['extra_info'] = $this->opo_plugin->getExtraInfo($element_info['settings'], $hit['url']);
					$display_text = $this->opo_plugin->getDisplayValueFromLookupText($hit['label']);
					$return = array(
						'value_longtext1' => $display_text,	// text
						'value_longtext2' => $hit['url'],	// url
						'item_id' => $info['extra_info']['item_id'] ?? null,
						'value_decimal1' => $hit['id'], 	// id
						'value_blob' => caSerializeForDatabase($info),
						'value_sortable' => $this->sortableValue($display_text)
					);
				} else {
					$this->postError(1970, _t('Value for InformationService lookup has to be an ID or URL that returns exactly 1 hit. We got more or no hits. Value was %1', $value), 'ListAttributeValue->parseValue()');
					return false;
				}

				CompositeCache::save($tmp[0], $return, "InformationServiceLookup{$service}");
				return $return;
			} elseif((sizeof($tmp) == 1) && (preg_match("!^\[([0-9]+)\][ ]*(.*)!", $tmp[0], $m))) {   // [ID] TEXT format string where ID is numeric
			    return [
			        'value_longtext1' => $m[2],
			        'value_longtext2' => '',
					'item_id' => null,
			        'value_decimal1' => is_numeric($m[1]) ? $m[1] : null,
			        'value_blob' => null,
			        'value_sortable' => $this->sortableValue($m[2])
			    ];
			    
			} else { // raw text
				$this->opo_plugin = InformationServiceManager::getInformationServiceInstance($service);
				$res = $this->opo_plugin->lookup($element_info['settings'], $value);
				$selected_result = null;
				if(is_array($res['results'] ?? null) && sizeof($res['results'])) {
					$v = mb_strtolower($value);
					foreach($res['results'] as $r) {
						if(mb_strtolower($r['label']) === $v) {
							$selected_result = $r;
							break;
						}
					}
					if(!$selected_result) { $selected_result = array_shift($res['results']); }
				}
				if($selected_result && !caGetOption('isRecursive', $options, false)) {
					return self::parseValue($selected_result['url'], $element_info, array_merge($options, ['isRecursive' => true]));
				}
				if(!$selected_result) {
					return null;
				}
				return [
			        'value_longtext1' => $value,
			        'value_longtext2' => '',
			        'item_id' => null,
			        'value_decimal1' => null,
			        'value_blob' => null,
			        'value_sortable' => $this->sortableValue($value)
			    ];
			}
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $element_info An array of information about the metadata element being edited
	 * @param array $options array Options include:
	 *			class = the CSS class to apply to all visible form elements [Default=lookupBg]
	 *			width = the width of the form element [Default=field width defined in metadata element definition]
	 *			height = the height of the form element [Default=field height defined in metadata element definition]
	 *			request = the RequestHTTP object for the current request; required for lookups to work [Default is null]
	 *
	 * @return string
	 */
	public function htmlFormElement($element_info, $options=null) {
		$settings = $this->getSettingValuesFromElementArray($element_info, array('fieldWidth', 'fieldHeight'));
		$class = caGetOption('class', $options, 'lookupBg');
		$pb_for_search = caGetOption('forSearch', $options, false);
		
		$service = caGetOption('service', $this->getSettingValuesFromElementArray(
			$element_info, ['service']
		));
		
		if(!$this->opo_plugin) {
			$this->opo_plugin = InformationServiceManager::getInformationServiceInstance($service);
		}
		
		$element = '';
		
        if (!$pb_for_search) {
        	// Add additional UI elements for services that require them (Eg. Numishare)
        	$additional_ui_controls = method_exists($this->opo_plugin, 'getAdditionalFields') ? $this->opo_plugin->getAdditionalFields($element_info) : [];
			$additional_ui_elements = trim(join(' ', array_map(function($v) { return $v['html']; }, $additional_ui_controls)));
			
    		$additional_ui_gets = array_map(function($v) { 
    			return "{$v['name']}: jQuery('#{$v['id']}').val()";
    		}, $additional_ui_controls);
    		
    		$additional_ui_gets_str = sizeof($additional_ui_gets) ? ','.join(',', $additional_ui_gets) : '';
    		
    		$additional_fields = $this->opo_plugin->getAdditionalFields($element_info);
    		
    		$hidden_val = '{{'.$element_info['element_id'].'}}';
    		if(is_array($additional_fields)) {
    			foreach($additional_fields as $f) {
    				switch($f['name']) {
    					case 'id':
    						$hidden_val .= "|{$service}:{{id}}";
    						break;
    					default: 
    						$hidden_val .= "|{{{$f['name']}}}";
    						break;
    				}
    			}
    		}
    		
            $element = '<div id="{fieldNamePrefix}'.$element_info['element_id'].'_input{n}">'.
            	$additional_ui_elements.
                caHTMLTextInput(
                    '{fieldNamePrefix}'.$element_info['element_id'].'_autocomplete{n}',
                    array(
                        'size' => (isset($options['width']) && $options['width'] > 0) ? $options['width'] : $settings['fieldWidth'],
                        'height' => (isset($options['height']) && $options['height'] > 0) ? $options['height'] : $settings['fieldHeight'],
                        'value' => '{{'.$element_info['element_id'].'}}',
                        'maxlength' => 512,
                        'id' => "{fieldNamePrefix}".$element_info['element_id']."_autocomplete{n}",
                        'class' => $class
                    )
                ).
                caHTMLHiddenInput(
                    '{fieldNamePrefix}'.$element_info['element_id'].'_{n}',
                    array(
                        'value' => $hidden_val,
                        'id' => '{fieldNamePrefix}'.$element_info['element_id'].'_{n}'
                    )
                );

            if ($options['request']) {
                $url = caNavUrl($options['request'], 'lookup', 'InformationService', 'Get', array('max' => 100, 'element_id' => $element_info['element_id']));
                $detail_url = caNavUrl($options['request'], 'lookup', 'InformationService', 'GetDetail', array('element_id' => $element_info['element_id']));
            } else {
                // hardcoded default for testing.
                $url = '/index.php/lookup/InformationService/Get';
                $detail_url = '/index.php/lookup/InformationService/GetDetail';
            }

            $element .= " <a href='#' class='caInformationServiceMoreLink' id='{fieldNamePrefix}".$element_info['element_id']."_link{n}'>"._t("More &rsaquo;")."</a>";
            $element .= "<div id='{fieldNamePrefix}".$element_info['element_id']."_detail{n}' class='caInformationServiceDetail'>".($options['request'] ? caBusyIndicatorIcon($options['request']) : '')."</div></div>";
    				
            $element .= "
                    <script type='text/javascript'>
                        jQuery(document).ready(function() {
                            jQuery('#{fieldNamePrefix}".$element_info['element_id']."_autocomplete{n}').autocomplete(
                                {
                                    minLength: 3,delay: 800,
                                    source: function (request, response) {
										jQuery.get('{$url}', {
												term: request.term{$additional_ui_gets_str}
											}, function (data) {
												response(JSON.parse(data));
											});
									},
                                    html: true,
                                    select: function(event, ui) {".((!$pb_for_search) ? "
                                        jQuery('#{fieldNamePrefix}".$element_info['element_id']."_{n}').val(ui.item.label + '|' + ui.item.idno + '|' + ui.item.url);" : 
                                        "jQuery('#{fieldNamePrefix}".$element_info['element_id']."_{n}').val(ui.item.label);"
                                    )."
                                    }
                                }
                            ).click(function() { this.select(); });
                    ";
                
            $element .= "					if ('{{".$element_info['element_id']."}}') {
                            jQuery('#{fieldNamePrefix}".$element_info['element_id']."_link{n}').css('display', 'inline').on('click', function(e) {
                                if (jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').css('display') == 'none') {
                                    jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').slideToggle(250, function() {
                                        jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').load('{$detail_url}/id/{n}');
                                    });
                                    jQuery('#{fieldNamePrefix}".$element_info['element_id']."_link{n}').html('".addslashes(_t("Less &rsaquo;"))."');
                                } else {
                                    jQuery('#{fieldNamePrefix}".$element_info['element_id']."_detail{n}').slideToggle(250);
                                    jQuery('#{fieldNamePrefix}".$element_info['element_id']."_link{n}').html('".addslashes(_t("More &rsaquo;"))."');
                                }
                                return false;
                            });
                        }
            ";
        
            $element .= "
                        });
                        </script>";
		} else {
		    $element .= caHTMLTextInput(
                    '{fieldNamePrefix}'.$element_info['element_id'].'_{n}',
                    array(
                        'id' => '{fieldNamePrefix}'.$element_info['element_id'].'_{n}',
                        'size' => (isset($options['width']) && $options['width'] > 0) ? $options['width'] : $settings['fieldWidth'],
                        'height' => (isset($options['height']) && $options['height'] > 0) ? $options['height'] : $settings['fieldHeight'],
                        'value' => '{{'.$element_info['element_id'].'}}',
                        'class' => $class
                    )
                );
		}

		return $element;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 */
	public function getAvailableSettings($element_info=null) {
		global $_ca_attribute_settings;
		if (!($service = isset($element_info['settings']['service']) ? $element_info['settings']['service'] : null)) {
			$service = isset($element_info['service']) ? $element_info['service'] : null;
		}
		
		$names = InformationServiceManager::getInformationServiceNames();
		if (!in_array($service, $names)) {
			$service = $names[0];
		}

		if ($this->opo_plugin = InformationServiceManager::getInformationServiceInstance($service)) {
			$settings = $this->opo_plugin->getAvailableSettings() +  $_ca_attribute_settings['InformationServiceAttributeValue'] ;
			$settings['service']['options'] = InformationServiceManager::getInformationServiceNamesOptionList();
			$service = $settings['service'];
			unset($settings['service']);
			$settings = array('service' => $service) + $settings;
		} else {
			$settings = array();
		}
		return $settings;
	}
	# ------------------------------------------------------------------
	public function getDataForSearchIndexing() {
		return (is_array($this->opa_indexing_info) ? $this->opa_indexing_info : array());
	}
	# ------------------------------------------------------------------
	/**
	 * Get extra info
	 * @param null|string $info_key Optional specific info key
	 * @return mixed
	 */
	public function getExtraInfo($info_key=null) {
		if(!$info_key) {
			return (is_array($this->opa_extra_info) ? $this->opa_extra_info : array());
		} else {
			return isset($this->opa_extra_info[$info_key]) ? $this->opa_extra_info[$info_key] : null;
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
