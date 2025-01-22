<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/TextAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
define("__CA_ATTRIBUTE_VALUE_TEXT__", 1);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

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
		'width' => 10, 'height' => 1,
		'default' => 16777216,
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
		'description' => _t('Check this option if you don\'t want your text to be locale-specific. (The default is to be.)'),
		'hideOnSelect' => ['singleValuePerLocale', 'allowLocales'],
	),
	'singleValuePerLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow single value per locale'),
		'description' => _t('Check this option to restrict entry to a single value per-locale.')
	),
	'allowLocales' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => null,
		'showLocaleList' => true,
		'width' => '400px', 'height' => 10,
		'label' => _t('Allow locales'),
		'multiple' => true,
		'description' => _t('Specify specific locales to allow for this element.')
	],
	'allowDuplicateValues' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow duplicate values?'),
		'description' => _t('Check this option if you want to allow duplicate values to be set when element is not in a container and is repeating.'),
		'showOnSelect' => ['raiseErrorOnDuplicateValue']
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
		'description' => _t('Check this option if you want this attribute to suggest previously saved values as text is entered. This option is only effective if the display height of the text entry is equal to 1.'),
		'showOnSelect' => ['suggestExistingValueSort']
	),
	'suggestExistingValueSort' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => 'value',
		'width' => 20, 'height' => 1,
		'label' => _t('Sort suggested values by?'),
		'description' => _t('If suggestion of existing values is enabled this option determines how returned values are sorted. Choose <em>value</em> to sort alphabetically. Choose <em>most recently added </em> to sort with most recently entered values first.'),
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
		'description' => _t('Text to pre-populate a newly created attribute with. You may use the following tags to include information about the currently logged in user: <em>^currentuser.fname</em> (user first name), <em>^currentuser.lname</em> (user last name), <em>^currentuser.email</em> (user email address) and  <em>^currentuser.user_name</em> (login name),  ')
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
		'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <em>^my_element_code</em>.')
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
	),
	'referenceMediaIn' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'showMediaElementBundles' => true,
		'default' => '',
		'width' => "200px", 'height' => 1,
		'label' => _t('Reference media in'),
		'description' => _t('Allow in-line references in text to a media element.')
	),
	'moveArticles' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Omit leading definite and indefinite articles when sorting'),
		'description' => _t('Check this option to sort values wuthout definite and indefinite articles when they are at the beginning of the text.')
	),
);

class TextAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
	private $ops_text_value;
	# ------------------------------------------------------------------
	public function __construct($value_array=null) {
		parent::__construct($value_array);
	}
	# ------------------------------------------------------------------
	public function loadTypeSpecificValueFromRow($value_array) {
		$this->ops_text_value = $value_array['value_longtext1'] ?? null;
	}
	# ------------------------------------------------------------------
	/**
	 * @param array $options Options include:
	 *      doRefSubstitution = Parse and replace reference tags (in the form [table idno="X"]...[/table]). [Default is false in Providence; true in Pawtucket].
	 * @return string
	 */
	public function getDisplayValue($options=null) {
		global $g_request;
		
		// process reference tags
		if ($g_request && caGetOption('doRefSubstitution', $options, __CA_APP_TYPE__ == 'PAWTUCKET')) {
			return caProcessReferenceTags($g_request, $this->ops_text_value);
		}
	
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	public function parseValue($value, $element_info, $options=null) {
		$settings = $this->getSettingValuesFromElementArray(
			$element_info, 
			['minChars', 'maxChars', 'regex', 'mustBeUnique', 'moveArticles']
		);
		$strlen = mb_strlen($value);
		if ($strlen < $settings['minChars']) {
			// text is too short
			$err_msg = ($settings['minChars'] == 1) ? _t('%1 must be at least 1 character long', $element_info['displayLabel']) : _t('%1 must be at least %2 characters long', $element_info['displayLabel'], $settings['minChars']);
			$this->postError(1970, $err_msg, 'TextAttributeValue->parseValue()');
			return false;
		}
		if ($strlen > $settings['maxChars']) {
			// text is too short
			$err_msg = ($settings['maxChars'] == 1) ? _t('%1 must be no more than 1 character long', $element_info['displayLabel']) : _t('%1 must be no more than %2 characters long', $element_info['displayLabel'], $settings['maxChars']);
			$this->postError(1970, $err_msg, 'TextAttributeValue->parseValue()');
			return false;
		}
		
		if ($settings['regex'] && !preg_match("!".$settings['regex']."!", $value)) {
			// regex failed
			$this->postError(1970, _t('%1 does not conform to required format', $element_info['displayLabel']), 'TextAttributeValue->parseValue()');
			return false;
		}

		if(isset($settings['mustBeUnique']) && (bool)$settings['mustBeUnique'] && ($strlen > 0)) {
			
			if (BaseModelWithAttributes::valueExistsForElement($element_info['element_id'], $value, ['transaction' => $options['transaction'], 'value_id' => $this->getValueID()])) {
				$this->postError(1970, _t('%1 must be unique across all values. The value you entered already exists.', $element_info['displayLabel']), 'TextAttributeValue->parseValue()');
				return false;
			}
		}
		
		return [
			'value_longtext1' => $value,
			'value_sortable' => $this->sortableValue($value, $settings)
		];
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $element_info An array of information about the metadata element being edited
	 * @param array $options array Options include:
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
	public function htmlFormElement($element_info, $options=null) {
		global $g_request;
		$settings = $this->getSettingValuesFromElementArray($element_info, ['fieldWidth', 'fieldHeight', 'minChars', 'maxChars', 'suggestExistingValues', 'usewysiwygeditor', 'isDependentValue', 'dependentValueTemplate', 'mustBeUnique', 'referenceMediaIn']);

		if (isset($options['usewysiwygeditor'])) {
			$settings['usewysiwygeditor'] = $options['usewysiwygeditor'];
		}

		if (isset($options['forSearch']) && $options['forSearch']) {
			unset($settings['usewysiwygeditor']);
		}
		
		$width = trim((isset($options['width']) && $options['width'] > 0) ? $options['width'] : $settings['fieldWidth']);
		$height = trim((isset($options['height']) && $options['height'] > 0) ? $options['height'] : $settings['fieldHeight']);
		
		// Convert width and height settings to integer pixel values (Note: these do not include a "px" suffix) or percentages
		$width = caParseFormElementDimension($width, ['returnAs' => 'pixels', 'characterWidth' => 6]);
		$height = caParseFormElementDimension($height, ['returnAs' => 'pixels', 'characterWidth' => 14]);
		
		$class = trim((isset($options['class']) && $options['class']) ? $options['class'] : '');
		$element = '';
		
		$attr = [
			'width' => $width.(is_numeric($width) ? 'px' : ''), 
			'height' => $height.(is_numeric($height) ? 'px' : ''), 
			'value' => '{{'.$element_info['element_id'].'}}', 
			'id' => '{fieldNamePrefix}'.$element_info['element_id'].'_{n}',
			'class' => "{$class}"
		];
		$opts = [
			'textAreaTagName' => caGetOption('textAreaTagName', $options, null)
		];
		$attributes = caGetOption('attributes', $options, null);
		if(is_array($attributes)) { 
			$attr = array_merge($attributes, $opts);
		}
			
		if (caGetOption('readonly', $options, false)) { 
			$attr['disabled'] = 1;
		}
		
		if ($settings['usewysiwygeditor'] ?? null) {
			$o_config = Configuration::load();
			
			$use_editor = $o_config->get('wysiwyg_editor');
			
			if(is_numeric($width) && ($width < 200)) { $width = 200; } 	// force absolute minimum width	
			if(is_numeric($height) && ($height < 50)) { $height = 50; } 	// force absolute minimum height	

			$width_w_suffix = is_numeric($width) ? "{$width}px" : $width;
			$height_w_suffix = is_numeric($height) ? "{$height}px" : $height;
			
			switch($use_editor) {
				case 'ckeditor':
					AssetLoadManager::register("ck5");
					
					$toolbar = caGetCK5Toolbar();
					$element .= "
					<script type=\"module\">
						import {
						 ClassicEditor, BlockQuote, BlockToolbar, Bold, Code, Essentials, FontBackgroundColor, Font, FontColor, FontFamily, 
						 FontSize, GeneralHtmlSupport, Heading, Highlight, HtmlComment, ImageBlock, ImageCaption, ImageInline, 
						 ImageTextAlternative, Indent, IndentBlock, Italic, Link, List, ListProperties, MediaEmbed, 
						 Paragraph, PasteFromOffice, RemoveFormat, SelectAll, SourceEditing, SpecialCharacters, SpecialCharactersArrows, 
						 SpecialCharactersCurrency, SpecialCharactersEssentials, SpecialCharactersLatin, SpecialCharactersMathematical, 
						 SpecialCharactersText, Strikethrough, Subscript, Superscript, TextTransformation, TodoList, Underline, Undo, LinkImage
						} from 'ckeditor5';
						
						import { ResizableHeight} from 'ckresizeable';
					
						ClassicEditor
							.create( document.querySelector( '#{fieldNamePrefix}{$element_info['element_id']}_{n}' ), {
								plugins: [ 
									BlockQuote, BlockToolbar, Bold, Code, Essentials, FontBackgroundColor, FontColor, FontFamily, FontSize, 
									GeneralHtmlSupport, Heading, Highlight, HtmlComment, ImageBlock, ImageCaption, ImageInline, 
									ImageTextAlternative, Indent, IndentBlock, Italic, Link, List, ListProperties, MediaEmbed, 
									Paragraph, PasteFromOffice, RemoveFormat, SelectAll, SourceEditing, SpecialCharacters, 
									SpecialCharactersArrows, SpecialCharactersCurrency, SpecialCharactersEssentials, 
									SpecialCharactersLatin, SpecialCharactersMathematical, SpecialCharactersText, Strikethrough, 
									Subscript, Superscript, TextTransformation, TodoList, Underline, Undo, LinkImage, ResizableHeight
								],
								toolbar: {
									items: ".json_encode($toolbar).",
									shouldNotGroupWhenFull: true
								},
								ResizableHeight: {
									resize: true,
									height: '{$height_w_suffix}',
									minHeight: '50px',
									maxHeight: '1500px'
								}
							} ).then(editor => {
								// Don't let CKEditor pollute the top-level DOM with editor bits
								const body = editor.ui.view.body._bodyCollectionContainer
								body.remove()
								editor.ui.view.element.appendChild(body);
								
								// Add current instance to list of initialized editors
								if(!caUI) { caUI = {}; }
								if(!caUI.ckEditors) { caUI.ckEditors = []; }
								caUI.ckEditors.push(editor);
							}).catch((e) => console.log('Error initializing CKEditor: ' + e));
					</script>\n";
									
					$element .= "<div style='width: {$width_w_suffix}; overflow-y: auto;' class='{fieldNamePrefix}{$element_info['element_id']}_container_{n} ckeditor-wrapper'>".caHTMLTextInput(
						'{fieldNamePrefix}'.$element_info['element_id'].'_{n}', 
						$attr, $opts
					)."</div>\n";
					break;
				case 'quilljs';
				default:
					AssetLoadManager::register("quilljs");
					$quill_opts = [
						'viewSource' => true,
						'okText' => _t('OK'),
						'cancelText' => _t('Cancel'),
						'buttonHTML' => _t('HTML'),
						'buttonTitle' => _t('Show HTML source')
					];
					
					$element .= "<div id='{fieldNamePrefix}".$element_info['element_id']."_container_{n}' class='ql-ca-container' style='width: {$width_w_suffix};'>";
					$element .= "
						<script type='text/javascript'>
							caUI.newTextEditor(
								'{fieldNamePrefix}".$element_info['element_id']."_editor_{n}', 
								'{fieldNamePrefix}".$element_info['element_id']."_{n}',
								'{{".$element_info['element_id']."}}',
								".json_encode(caGetQuillToolbar()).",
								".json_encode($quill_opts)."
							);
						</script>\n";
					$attr['style'] = 'display: none;';
					$element .= "<div id='{fieldNamePrefix}".$element_info['element_id']."_editor_{n}' style='height: {$height_w_suffix};' class='ql-ca-editor'></div>";
							
					$element .= caHTMLTextInput(
						'{fieldNamePrefix}'.$element_info['element_id'].'_{n}', 
						$attr, $opts
					);
					$element .= "</div>\n";
					break;
			}
		} else {
			$element .= caHTMLTextInput(
				'{fieldNamePrefix}'.$element_info['element_id'].'_{n}', 
				$attr, $opts
			);
		}
		

		if (isset($settings['mustBeUnique']) && $settings['mustBeUnique']) {
			$element .= "
				<div id='{fieldNamePrefix}{$element_info['element_id']}_{n}_uniquenessWarning' class='caDupeAttributeMessageBox' style='display:none'>
					"._t("This field value already exists!")."
				</div>
			";
		}
		
		if (!caGetOption('forSearch', $options, false) && ($settings['isDependentValue'] ?? false || $options['isDependentValue'] ?? false)) {
			$t_element = new ca_metadata_elements($element_info['element_id']);
			$elements = $t_element->getElementsInSet($t_element->getHierarchyRootID());
			$element_dom_ids = [];
			foreach($elements as $i => $element) {
				if ($element['datatype'] == __CA_ATTRIBUTE_VALUE_CONTAINER__) { continue; }
				$element_dom_ids[$element['element_code']] = "#{fieldNamePrefix}".$element['element_id']."_{n}";
			}
			
			$o_dimensions_config = Configuration::load(__CA_APP_DIR__."/conf/dimensions.conf");
			$parser_opts = [];
			
			foreach([
					'inch_decimal_precision', 'feet_decimal_precision', 'mile_decimal_precision', 
					'millimeter_decimal_precision', 'centimeter_decimal_precision', 'meter_decimal_precision', 
					'kilometer_decimal_precision', 
					'use_unicode_fraction_glyphs_for', 'display_fractions_for', 
					'add_period_after_units', 
					'use_inches_for_display_up_to', 'use_feet_for_display_up_to', 'use_millimeters_for_display_up_to', 
					'use_centimeters_for_display_up_to', 'use_meters_for_display_up_to',
					'force_meters_for_all_when_dimension_exceeds', 'force_centimeters_for_all_when_dimension_exceeds', 'force_millimeters_for_all_when_dimension_exceeds',
					'force_feet_for_all_when_dimension_exceeds', 'force_inches_for_all_when_dimension_exceeds'
				] as $key) {
				$proc_key = caSnakeToCamel($key);
				$parser_opts[$proc_key] = $o_dimensions_config->get($key);
			}
			$omit_units = ((bool)$o_dimensions_config->get('omit_repeating_units_for_measurements_in_templates')) ? "true" : "false";
			$element .= "<script type='text/javascript'>jQuery(document).ready(function() {
				caDisplayTemplateParser.setOptions(".json_encode($parser_opts).");
				jQuery('#{fieldNamePrefix}".$element_info['element_id']."_{n}').val(caDisplayTemplateParser.processDependentTemplate('".addslashes(preg_replace("![\r\n]+!", " ", $settings['dependentValueTemplate']))."', ".json_encode($element_dom_ids, JSON_FORCE_OBJECT).", true, {$omit_units}));
			";
			$element .= "jQuery('".join(", ", $element_dom_ids)."').on('keyup change', function(e) { 
				jQuery('#{fieldNamePrefix}".$element_info['element_id']."_{n}').val(caDisplayTemplateParser.processDependentTemplate('".addslashes(preg_replace("![\r\n]+!", " ", $settings['dependentValueTemplate']))."', ".json_encode($element_dom_ids, JSON_FORCE_OBJECT).", true, {$omit_units}));
			});";
			
			$element .="});</script>";
		}
		
		$bundle_name = $lookup_url = null;
		if (isset($options['t_subject']) && is_object($options['t_subject'])) {
			$bundle_name = $options['t_subject']->tableName().'.'.$element_info['element_code'];
			
			if ($options['request']) {
				if (isset($options['lookupUrl']) && $options['lookupUrl']) {
					$lookup_url = $options['lookupUrl'];
				} else {
					$lookup_url	= caNavUrl($options['request'], 'lookup', 'AttributeValue', 'Get', ['max' => 500, 'bundle' => $bundle_name]);
				}
			}
		}
		
		if ($settings['suggestExistingValues'] && $lookup_url && $bundle_name) { 
			$element .= "<script type='text/javascript'>
				jQuery('#{fieldNamePrefix}".$element_info['element_id']."_{n}').autocomplete( 
					{ 
						source: '{$lookup_url}',
						minLength: 3, delay: 800
					}
				);
			</script>\n";
		}

		if (isset($settings['mustBeUnique']) && $settings['mustBeUnique']) {
			$unique_lookup_url = caNavUrl($options['request'], 'lookup', 'AttributeValue', 'ValueExists', ['bundle' => $bundle_name]);
			$element .= "<script type='text/javascript'>
				var warnSpan = jQuery('#{fieldNamePrefix}{$element_info['element_id']}_{n}_uniquenessWarning');
				jQuery('#{fieldNamePrefix}".$element_info['element_id']."_{n}').keyup(function() {
					jQuery.getJSON('{$unique_lookup_url}', {n: jQuery(this).val()}).done(function(data) {
						if(data.exists >= 1) {
							warnSpan.show();
						} else {
							warnSpan.hide();
						}
					});
				});
			</script>\n";
		}
		
		return $element;
	}
	# ------------------------------------------------------------------
	public function getAvailableSettings($element_info=null) {
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
		return 'value_sortable';
	}
	# ------------------------------------------------------------------
	/**
	 * Returns name of field in ca_attribute_values to use for query operations
	 *
	 * @return string Name of sort field
	 */
	public function queryFields() : ?array {
		return ['value_longtext1'];
	}
	# ------------------------------------------------------------------
	/**
	 * Returns sortable value for metadata value
	 *
	 * @param string $value
	 * 
	 * @return string
	 */
	public function sortableValue(?string $value, ?array $options=null) {
		return mb_strtolower(substr(trim(caSortableValue($value, $options)), 0, 100));
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
