<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/TextAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2017 Whirl-i-Gig
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
 	define("__CA_ATTRIBUTE_VALUE_TEXT__", 1);
 	
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 
 	global $_ca_attribute_settings;
 	$_ca_attribute_settings['TextAttributeValue'] = array(		// global
		'minChars' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 5, 'height' => 1,
			'default' => 0,
			'label' => _t('Minimum number of characters'),
			'description' => _t('The minimum number of characters to allow. Input shorter than required will be rejected.')
		),
		'maxChars' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 5, 'height' => 1,
			'default' => 65535,
			'label' => _t('Maximum number of characters'),
			'description' => _t('The maximum number of characters to allow. Input longer than required will be rejected.')
		),
		'regex' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 60, 'height' => 1,
			'default' => '',
			'label' => _t('Regular expression to validate input with'),
			'description' => _t('A Perl-format regular expression with which to validate the input. Input not matching the expression will be rejected. Do not include the leading and trailling delimiter characters (typically "/") in your expression. Leave blank if you don\'t want to use regular expression-based validation.')
		),
		'fieldWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 40,
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
		'usewysiwygeditor' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Use rich text editor'),
			'description' => _t('Check this option if you want to use a word-processor like editor with this text field. If you expect users to enter rich text (italic, bold, underline) then you might want to enable this.')
		),
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your text to be locale-specific. (The default is to be.)')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
		),
		'suggestExistingValues' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'width' => 1, 'height' => 1,
			'default' => 0,
			'label' => _t('Suggest existing values?'),
			'description' => _t('Check this option if you want this attribute to suggest previously saved values as text is entered. This option is only effective if the display height of the text entry is equal to 1.')
		),
		'suggestExistingValueSort' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => 'value',
			'width' => 20, 'height' => 1,
			'label' => _t('Sort suggested values by?'),
			'description' => _t('If suggestion of existing values is enabled this option determines how returned values are sorted. Choose <i>value</i> to sort alphabetically. Choose <i>most recently added </i> to sort with most recently entered values first.'),
			'options' => array(
				_t('Value') => 'value',
				_t('Most recently added') => 'recent'
			)
		),
		'default_text' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 4,
			'default' => '',
			'label' => _t('Default text'),
			'description' => _t('Text to pre-populate a newly created attribute with')
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
		'isDependentValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Is dependent value?'),
			'validForNonRootOnly' => 1,
			'description' => _t('If set then this element is set using values in other fields in the same container. This is only relevant when the element is in a container and is ignored otherwise.')
		),
		'dependentValueTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Dependent value template'),
			'validForNonRootOnly' => 1,
			'description' => _t('Template to be used to format content for dependent values. Template should reference container values using their bare element code prefixed with a caret (^). Do not include the table or container codes.')
		),
		'mustBeUnique' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Must be unique'),
			'description' => _t('Check this option to enforce uniqueness across all values for this attribute.')
		)
	);
 
	class TextAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 		}
 		# ------------------------------------------------------------------
		public function getDisplayValue($pa_options=null) {
			return $this->ops_text_value;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray(
 				$pa_element_info, 
 				array('minChars', 'maxChars', 'regex', 'mustBeUnique')
 			);
 			$vn_strlen = unicode_strlen($ps_value);
 			if ($vn_strlen < $va_settings['minChars']) {
 				// text is too short
 				$vs_err_msg = ($va_settings['minChars'] == 1) ? _t('%1 must be at least 1 character long', $pa_element_info['displayLabel']) : _t('%1 must be at least %2 characters long', $pa_element_info['displayLabel'], $va_settings['minChars']);
				$this->postError(1970, $vs_err_msg, 'TextAttributeValue->parseValue()');
				return false;
 			}
 			if ($vn_strlen > $va_settings['maxChars']) {
 				// text is too short
 				$vs_err_msg = ($va_settings['maxChars'] == 1) ? _t('%1 must be no more than 1 character long', $pa_element_info['displayLabel']) : _t('%1 must be no more than %2 characters long', $pa_element_info['displayLabel'], $va_settings['maxChars']);
				$this->postError(1970, $vs_err_msg, 'TextAttributeValue->parseValue()');
				return false;
 			}
 			
 			if ($va_settings['regex'] && !preg_match("!".$va_settings['regex']."!", $ps_value)) {
 				// regex failed
				$this->postError(1970, _t('%1 does not conform to required format', $pa_element_info['displayLabel']), 'TextAttributeValue->parseValue()');
				return false;
 			}

			if(isset($va_settings['mustBeUnique']) && (bool)$va_settings['mustBeUnique'] && ($vn_strlen > 0)) {
				
				if (BaseModelWithAttributes::valueExistsForElement($pa_element_info['element_id'], $ps_value, ['transaction' => $pa_options['transaction'], 'value_id' => $this->getValueID()])) {
					$this->postError(1970, _t('%1 must be unique across all values. The value you entered already exists.', $pa_element_info['displayLabel']), 'TextAttributeValue->parseValue()');
					return false;
				}
			}
 			
 			return array(
 				'value_longtext1' => $ps_value
 			);
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return HTML form element for editing.
 		 *
 		 * @param array $pa_element_info An array of information about the metadata element being edited
 		 * @param array $pa_options array Options include:
 		 *			usewysiwygeditor = overrides element level setting for visual text editor [Default=false]
 		 *			forSearch = settings and options regarding visual text editor are ignored [Default=false]
 		 *			class = the CSS class to apply to all visible form elements [Default=null]
 		 *			width = the width of the form element [Default=field width defined in metadata element definition]
 		 *			height = the height of the form element [Default=field height defined in metadata element definition]
 		 *			t_subject = an instance of the model to which the attribute belongs; required if suggestExistingValues lookups are enabled [Default is null]
 		 *			request = the RequestHTTP object for the current request; required if suggestExistingValues lookups are enabled [Default is null]
 		 *			suggestExistingValues = suggest values based on existing input for this element as user types [Default is false]		
 		 *
 		 * @return string
 		 */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'minChars', 'maxChars', 'suggestExistingValues', 'usewysiwygeditor', 'isDependentValue', 'dependentValueTemplate', 'mustBeUnique'));

 			if (isset($pa_options['usewysiwygeditor'])) {
 				$va_settings['usewysiwygeditor'] = $pa_options['usewysiwygeditor'];
 			}

 			if (isset($pa_options['forSearch']) && $pa_options['forSearch']) {
 				unset($va_settings['usewysiwygeditor']);
 			}
 			
 			$vs_width = trim((isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth']);
 			$vs_height = trim((isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight']);
 			$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : '');
			$vs_element = '';
 			
 			
 			if (!preg_match("!^[\d\.]+px$!i", $vs_width)) {
 				$vs_width = ((int)$vs_width * 6)."px";
 			}
 			if (!preg_match("!^[\d\.]+px$!i", $vs_height) && ((int)$vs_height > 1)) {
 				$vs_height = ((int)$vs_height * 16)."px";
 			}
 			
 			if ($va_settings['usewysiwygeditor']) {
 				$o_config = Configuration::load();
 				if (!is_array($va_toolbar_config = $o_config->getAssoc('wysiwyg_editor_toolbar'))) { $va_toolbar_config = array(); }
 				AssetLoadManager::register("ckeditor");
 				
 				$vs_element = "<script type='text/javascript'>jQuery(document).ready(function() {
						var ckEditor = CKEDITOR.replace( '{fieldNamePrefix}".$pa_element_info['element_id']."_{n}',
						{
							toolbar : ".json_encode(array_values($va_toolbar_config)).", /* this does the magic */
							width: '{$vs_width}',
							height: '{$vs_height}',
							toolbarLocation: 'top',
							enterMode: CKEDITOR.ENTER_BR
						});
						
						ckEditor.on('instanceReady', function(){ 
							 ckEditor.document.on( 'keydown', function(e) {if (caUI && caUI.utils) { caUI.utils.showUnsavedChangesWarning(true); } });
						});
 	});									
</script>";
			}
 			
 			$va_opts = array(
 					'size' => $vs_width, 
 					'height' => $vs_height, 
 					'value' => '{{'.$pa_element_info['element_id'].'}}', 
 					'maxlength' => $va_settings['maxChars'],
 					'class' => $vs_class,
 					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 'class' => "{$vs_class}".($va_settings['usewysiwygeditor'] ? " ckeditor" : '')
 				);
 			if (caGetOption('readonly', $pa_options, false)) { 
 				$va_opts['disabled'] = 1;
 			}
 			$vs_element .= caHTMLTextInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
 				$va_opts
 			);

			if (isset($va_settings['mustBeUnique']) && $va_settings['mustBeUnique']) {
				$vs_element .= "
					<div id='{fieldNamePrefix}{$pa_element_info['element_id']}_{n}_uniquenessWarning' class='caDupeAttributeMessageBox' style='display:none'>
						"._t("This field value already exists!")."
					</div>
				";
			}
 			
 			if (!caGetOption('forSearch', $pa_options, false) && ($va_settings['isDependentValue'] || $pa_options['isDependentValue'])) {
 				$t_element = new ca_metadata_elements($pa_element_info['element_id']);
 				$va_elements = $t_element->getElementsInSet($t_element->getHierarchyRootID());
 				$va_element_dom_ids = array();
 				foreach($va_elements as $vn_i => $va_element) {
 					if ($va_element['datatype'] == __CA_ATTRIBUTE_VALUE_CONTAINER__) { continue; }
 					$va_element_dom_ids[$va_element['element_code']] = "#{fieldNamePrefix}".$va_element['element_id']."_{n}";
 				}
 				
 				$vs_element .= "<script type='text/javascript'>jQuery(document).ready(function() {
 					jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(caDisplayTemplateParser.processDependentTemplate('".addslashes($va_settings['dependentValueTemplate'])."', ".json_encode($va_element_dom_ids, JSON_FORCE_OBJECT).", true));
 				";
 				$vs_element .= "jQuery('".join(", ", $va_element_dom_ids)."').bind('keyup', function(e) { 
 					jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').val(caDisplayTemplateParser.processDependentTemplate('".addslashes($va_settings['dependentValueTemplate'])."', ".json_encode($va_element_dom_ids, JSON_FORCE_OBJECT)."));
 				});";
 				
 				$vs_element .="});</script>";
 			}
 			
 			$vs_bundle_name = $vs_lookup_url = null;
 			if (isset($pa_options['t_subject']) && is_object($pa_options['t_subject'])) {
 				$vs_bundle_name = $pa_options['t_subject']->tableName().'.'.$pa_element_info['element_code'];
 				
 				if ($pa_options['request']) {
 					if (isset($pa_options['lookupUrl']) && $pa_options['lookupUrl']) {
 						$vs_lookup_url = $pa_options['lookupUrl'];
 					} else {
 						$vs_lookup_url	= caNavUrl($pa_options['request'], 'lookup', 'AttributeValue', 'Get', array('max' => 500, 'bundle' => $vs_bundle_name));
 					}
 				}
 			}
 			
 			if ($va_settings['suggestExistingValues'] && $vs_lookup_url && $vs_bundle_name) { 
 				$vs_element .= "<script type='text/javascript'>
 					jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').autocomplete( 
						{ 
							source: '{$vs_lookup_url}',
							minLength: 3, delay: 800
						}
					);
 				</script>\n";
 			}

			if (isset($va_settings['mustBeUnique']) && $va_settings['mustBeUnique']) {
				$vs_unique_lookup_url = caNavUrl($pa_options['request'], 'lookup', 'AttributeValue', 'ValueExists', array('bundle' => $vs_bundle_name));
				$vs_element .= "<script type='text/javascript'>
					var warnSpan = jQuery('#{fieldNamePrefix}{$pa_element_info['element_id']}_{n}_uniquenessWarning');
 					jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').keyup(function() {
						jQuery.getJSON('{$vs_unique_lookup_url}', {n: jQuery(this).val()}).done(function(data) {
							if(data.exists >= 1) {
								warnSpan.show();
							} else {
								warnSpan.hide();
							}
						});
					});
 				</script>\n";
			}
 			
 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings($pa_element_info=null) {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['TextAttributeValue'];
 		}
 		# ------------------------------------------------------------------
 		public function getDefaultValueSetting() {
 			return 'default_text';
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
		 * Returns constant for text attribute value
		 * 
		 * @return int Attribute value type code
		 */
		public function getType() {
			return __CA_ATTRIBUTE_VALUE_TEXT__;
		}
 		# ------------------------------------------------------------------
	}
 ?>