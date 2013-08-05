<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/ListAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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
 	
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 
 	global $_ca_attribute_settings;
 	
 	$_ca_attribute_settings['ListAttributeValue'] = array(		// global
		'listWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 40,
			'width' => 5, 'height' => 1,
			'label' => _t('Width of list in user interface'),
			'description' => _t('Width, in characters or pixels, of the list when displayed in a user interface. When list is rendered as a hierarchy browser width must be in pixels.')
		),
		'listHeight' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => "200px",
			'width' => 5, 'height' => 1,
			'label' => _t('Height of list in user interface (hierarchy browser only)'),
			'description' => _t('Height, in pixels, of the list when displayed in a user interface as a hierarchy browser.')
		),
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your list values to be locale-specific. (The default is to not be.)')
		),
		'requireValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Require value'),
			'description' => _t('Check this option if you want to require that a list item be selected.')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
		),
		'render' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => 1,
			'width' => 40, 'height' => 1,
			'label' => _t('Render list as'),
			'description' => _t('Set the presentation of the list to select, checkboxes, radio buttons, look or browser.'),
			'options' => array(
				_t('Drop-down list') => 'select',
				_t('Yes/no checkbox') => 'yes_no_checkboxes',
				_t('Radio buttons') => 'radio_buttons',
				_t('Checklist') => 'checklist',
				_t('Type-ahead lookup') => 'lookup',
				_t('Horizontal hierarchy browser') => 'horiz_hierbrowser',
				_t('Horizontal hierarchy browser with search') => 'horiz_hierbrowser_with_search',
				_t('Vertical hierarchy browser') => 'vert_hierbrowser',
			)
		),
		'maxColumns' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 3,
			'width' => 5, 'height' => 1,
			'label' => _t('Number of columns to use for radio button or checklist display'),
			'description' => _t('Maximum number of columns to use when laying out radio buttons or checklist.')
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
 
	class ListAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		private $opn_item_id;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 			$this->opn_item_id = $pa_value_array['item_id'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Will return plural value of list item unless useSingular option is set to true, in which case singular version of list item label will be used.
 		 *
 		 * @param array Optional array of options. Support options are:
 		 * 			list_id = if set then the numeric item_id value is translated into label text in the current locale. If not set then the numeric item_id is returned.
 		 *			useSingular = If list_id is set then by default the returned text is the plural label. Setting this option to true will force use of the singular label.
 		 *			showHierarchy = If true then hierarchical parents of list item will be returned and hierarchical options described below will be used to control the output
 		 *			HIERARCHICAL OPTIONS: 
 		 *				direction - For hierarchy specifications (eg. ca_objects.hierarchy) this determines the order in which the hierarchy is returned. ASC will return the hierarchy root first while DESC will return it with the lowest node first. Default is ASC.
 		 *				top - For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the root down. Default is to not limit.
 		 *				bottom - For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the lowest node up. Default is to not limit.
 		 * 				hierarchicalDelimiter - Text to place between items in a hierarchy for a hierarchical specification (eg. ca_objects.hierarchy) when returning as a string
 		 *				removeFirstItems - If set to a non-zero value, the specified number of items at the top of the hierarchy will be omitted. For example, if set to 2, the root and first child of the hierarchy will be omitted. Default is zero (don't delete anything).

 		 * @return string The value
 		 */
		public function getDisplayValue($pa_options=null) {
			$vn_list_id = (is_array($pa_options) && isset($pa_options['list_id'])) ? (int)$pa_options['list_id'] : null;
			if ($vn_list_id > 0) {
				$t_list = new ca_lists();
				
				// do we need to get the hierarchy?
				if ($pa_options['showHierarchy']) {
					$t_item = new ca_list_items($this->ops_text_value);
					
					return $t_item->get('ca_list_items.hierarchy.'.((isset($pa_options['useSingular']) && $pa_options['useSingular']) ? 'name_singular' : 'name_plural'), $pa_options);
				}
				
				return $t_list->getItemFromListForDisplayByItemID($vn_list_id, $this->ops_text_value, (isset($pa_options['useSingular']) && $pa_options['useSingular']) ? false : true);
			}
			return $this->ops_text_value;
		}
 		# ------------------------------------------------------------------
		public function getItemID() {
			return $this->opn_item_id;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info) {
 			$vb_require_value = (is_null($pa_element_info['settings']['requireValue'])) ? true : (bool)$pa_element_info['settings']['requireValue'];
 			
 			if (preg_match('![^\d]+!', $ps_value)) {
 				// try to convert idno to item_id
 				if ($vn_id = ca_lists::getItemID($pa_element_info['list_id'], $ps_value)) {
 					$ps_value = $vn_id;
 				}
 			}
 			if (!$vb_require_value && !(int)$ps_value) {
 				return array(
					'value_longtext1' => null,
					'item_id' => null
				);
 			} 
 			if (strlen($ps_value) && !is_numeric($ps_value)) { 
 				$this->postError(1970, _t('Item_id %2 is not valid for element %1',$pa_element_info["element_code"], $ps_value), 'ListAttributeValue->parseValue()');
				return false;
			}
 			$t_item = new ca_list_items((int)$ps_value);
 			if (!$t_item->getPrimaryKey()) {
 				if ($ps_value) {
 					$this->postError(1970, _t('%1 is not a valid list item_id for %2 [%3]', $ps_value, $pa_element_info['displayLabel'], $pa_element_info['element_code']), 'ListAttributeValue->parseValue()');
 				} else {
 					//$this->postError(1970, _t('Value %1 [%2] cannot be blank', $pa_element_info['displayLabel'], $pa_element_info['element_code']), 'ListAttributeValue->parseValue()');
 					return null;
 				}
				return false;
 			}
 			if ((int)$t_item->get('list_id') != (int)$pa_element_info['list_id']) {
 				$this->postError(1970, _t('Item is not in the correct list'), 'ListAttributeValue->parseValue()');
				return false;
 			}
 			return array(
 				'value_longtext1' => $ps_value,
 				'item_id' => (int)$ps_value
 			);
 		}
 		# ------------------------------------------------------------------
 		/**
 		  * Generates HTML form widget for attribute value
 		  * 
 		  * @param $pa_element_info array Array with information about the metadata element with which this value is associated. Keys taken to be ca_metadata_elements field names and the 'settings' field must be deserialized into an array.
 		  * @param $pa_options array Array of options. Supported options are:
 		  *			width - The width of the list drop-down in characters unless suffixed with 'px' in which case width will be set in pixels.
 		  *			any option supported by ca_lists::getListAsHTMLFormElement with the exception of 'render' and 'maxColumns', which are set out of information in $pa_element_info
 		  * @return string HTML code for form element
 		  */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$vb_require_value = (is_null($pa_element_info['settings']['requireValue'])) ? true : (bool)$pa_element_info['settings']['requireValue'];
 			if (($pa_element_info['parent_id']) && ($pa_element_info['settings']['render'] == 'checklist')) { $pa_element_info['settings']['render'] = ''; }	// checklists can only be top-level
 			if ((!isset($pa_options['width']) || !strlen($pa_options['width'])) && isset($pa_element_info['settings']['listWidth']) && strlen($pa_element_info['settings']['listWidth']) > 0) { $pa_options['width'] = $pa_element_info['settings']['listWidth']; }
 			if ((!isset($pa_options['height']) || !strlen($pa_options['height'])) && isset($pa_element_info['settings']['listHeight']) && strlen($pa_element_info['settings']['listHeight']) > 0) { $pa_options['height'] = $pa_element_info['settings']['listHeight']; }
 
 			if (isset($pa_options['nullOption']) && strlen($pa_options['nullOption'])) {
 				$vb_null_option = $pa_options['nullOption'];
 			} else {
 				$vb_null_option = !$vb_require_value ? _t('-NONE-') : null;
 			}
 			return ca_lists::getListAsHTMLFormElement($pa_element_info['list_id'], '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', array('id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}'), array_merge($pa_options, array('render' => isset($pa_element_info['settings']['render']) ? $pa_element_info['settings']['render'] : '', 'maxColumns' => $pa_element_info['settings']['maxColumns'], 'element_id' => $pa_element_info['element_id'], 'nullOption' => $vb_null_option)));
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings() {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['ListAttributeValue'];
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
		 * Checks validity of setting value for attribute; used by ca_metadata_elements to
		 * validate settings before they are saved.
		 *
		 * @param array $pa_element_info Associative array containing data from a ca_metadata_elements row
		 * @param string $ps_setting_key Alphanumeric setting code
		 * @param string $ps_value Value of setting
		 * @param string $ps_error Variable to place error message in, if setting fails validation
		 * @return boolean True if value is valid for setting, false if not. If validation fails an error message is returned in $ps_error
		 */
		public function validateSetting($pa_element_info, $ps_setting_key, $ps_value, &$ps_error) {
			$ps_error = '';
			switch($ps_setting_key) {
				case 'render':
					switch($ps_value) {
						case 'yes_no_checkboxes':
							$t_list = new ca_lists((int)$pa_element_info['list_id']);
							
							// Yes/no must be used with lists that have exactly two items
							if ((int)$t_list->numItemsInList() != 2) {
								$ps_error = _t('The list must have exactly two items to be used as a yes/no checkbox');
								return false;
							}
							break;
						case 'checklist':
							// Check list is only valid for top-level elements
							if ((int)$pa_element_info['parent_id'] > 0) {
								$ps_error = _t('Sub-elements may not be used as checklists');
								return false;
							}
							break;
					}
					break;
			}
			
			return true;
		}
 		# ------------------------------------------------------------------
	}
 ?>