<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/ListAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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

define("__CA_ATTRIBUTE_VALUE_LIST__", 3);

require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AuthorityAttributeValue.php');

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
	'singleValuePerLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow single value per locale'),
		'description' => _t('Check this option to restrict entry to a single value per-locale.')
	),
	'requireValue' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Require value'),
		'description' => _t('Check this option if you want to require that a list item be selected.')
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
	'implicitNullOption' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Allow implicit null values?'),
		'description' => _t('Check this option if you want to accept an empty selection as a null value.')
	),
	'nullOptionText' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => 'Not set',
		'width' => 90, 'height' => 1,
		'label' => _t('No value text'),
		'description' => _t('Text to use as label for the "no value" option when a value is not required.')
	),
	'useDefaultWhenNull' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Use list default when value is null?'),
		'description' => _t('Check this option if the list default value should be used when the item value is null. (The default is to disregard the default value and show the null value.)')
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
		'label' => _t('Render list in editor as'),
		'description' => _t('Set the presentation of the list when editing to select, checkboxes, radio buttons, type-ahead lookup or browser.'),
		'options' => array(
			_t('Drop-down list') => 'select',
			_t('Yes/no checkbox') => 'yes_no_checkboxes',
			_t('Radio buttons') => 'radio_buttons',
			_t('Checklist') => 'checklist',
			_t('Type-ahead lookup') => 'lookup',
			_t('Horizontal hierarchy browser') => 'horiz_hierbrowser',
			_t('Horizontal hierarchy browser with search') => 'horiz_hierbrowser_with_search',
			_t('Vertical hierarchy browser (upward)') => 'vert_hierbrowser',
			_t('Vertical hierarchy browser (downward)') => 'vert_hierbrowser_down',
		)
	),
	'renderInSearchBuilder' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => 1,
		'width' => 40, 'height' => 1,
		'label' => _t('Render list in search builder as'),
		'description' => _t('Set the presentation of the list to a select list or text box.'),
		'options' => array(
			_t('Drop-down list') => 'select',
			_t('Text box') => 'text',
		)
	),
	'useSingular' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => 0,
		'width' => 40, 'height' => 1,
		'label' => _t('Display sense'),
		'description' => _t('Sets whether singular or plural sense of term is displayed.'),
		'options' => array(
			_t('Singular') => 1,
			_t('Plural') => 0,
		)
	),
	'useTextEntryInSearchBuilderWhenListLongerThan' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 500,
		'width' => 4, 'height' => 1,
		'label' => _t('Use text entry for list in search builder when list is longer than'),
		'description' => _t('Forces use of text entry for list in search builder when the list length exceeds the specified number of items.')
	),
	'auto_shrink' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => "4", 'height' => "1",
		'takesLocale' => false,
		'default' => '0',
		'label' => _t('Automatically shrink horizontal hierarchy browser'),
		'description' => _t('Check this option if you want the hierarchy browser to automatically shrink or expand based on the height of the column with the most data. Only affects the horizontal browser!')
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
	'restrictToTypes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'useList' => 'list_item_types',
		'width' => 35, 'height' => 5,
		'takesLocale' => false,
		'multiple' => 1,
		'default' => '',
		'label' => _t('Restrict to types'),
		'description' => _t('Restricts display to items of the specified type(s). Leave all unchecked for no restriction.')
	),
	'currentSelectionDisplayFormat' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 4,
		'label' => _t('Current selection template'),
		'validForRootOnly' => 1,
		'description' => _t('Template formatting current selection text. You may use the following tags: ^current (the currently selected list item), ^parent (the parent of the currently selected list item), ^hierarchy (the full hierarchal path to the list item).')
	),
	'minimizeExistingValues' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Minimize existing values?'),
		'description' => _t('Check this option if existing values should displayed in a minimized, non-editable format.')
	),
	'deferHierarchyLoad' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Defer loading of hierarchy browser?'),
		'description' => _t('Check this option to defer loading of hierarachy browser for existing values using hierarchical render modes when not minimized until user clicks.')
	),
	'separateDisabledValues' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Separate disabled values?'),
		'description' => _t('Group disabled entries after active entries.')
	),
	'hideDisabledValues' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Hide disabled values?'),
		'description' => _t('Omit disabled entries from list.')
	),
);



class ListAttributeValue extends AuthorityAttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------	
	/**
	 * Name of table this attribute references
	 */
	protected $ops_table_name = 'ca_list_items';
	
	/**
	 * Display name, in singular sense, of table this attribute references. The name should be capitalized.
	 */
	protected $ops_name_singular = 'List item';
	
	/**
	 * Display name, in plural sense, of table this attribute references. The name should be capitalized.
	 */
	protected $ops_name_plural = 'List items';
	
	/**
	 *
	 */
	protected $opn_item_id;
	
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
	 * Get string value of list item attribute value for display. When returning text will return plural value of list item unless 
	 * useSingular option is set to true, in which case singular version of list item label will be used.
	 *
	 * @param array Optional array of options. Supported options include:
	 * 			list_id = if set then the numeric item_id value is translated into label text in the current locale. If not set then the numeric item_id is returned.
	 *			useSingular = If list_id is set then by default the returned text is the plural label. Setting this option to true will force use of the singular label. [Default is false]
	 *			returnIdno = If true list item idno is returned rather than preferred label [Default is false]
	 *			idsOnly = Return numeric item_id only [Default is false]
	 *			alwaysReturnItemID = Synonym for idsOnly [Default is false]
	 *			output = List item value return. Valid values are text [display text], idno [identifier; same as returnIdno option], value [numeric item_id; same as idsOnly option]. [Default is "value"]
	 *			transaction = transaction to get list item information in the context of [Default is false]
	 *          checkAccess = Only return list items with a specified access value. [Default is null; no filtering performed]
	 *
	 *			HIERARCHICAL OPTIONS:
	 *			    showHierarchy = Return hierarchical parents of list item. Hierarchical options described below are used to control the output. [Default is false]
	 *				direction = For hierarchy specifications (eg. ca_objects.hierarchy) this determines the order in which the hierarchy is returned. ASC will return the hierarchy root first while DESC will return it with the lowest node first. Default is ASC.
	 *				top = For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the root down. Default is to not limit.
	 *				bottom = For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the lowest node up. Default is to not limit.
	 * 				hierarchicalDelimiter = Text to place between items in a hierarchy for a hierarchical specification (eg. ca_objects.hierarchy) when returning as a string
	 *				removeFirstItems = If set to a non-zero value, the specified number of items at the top of the hierarchy will be omitted. For example, if set to 2, the root and first child of the hierarchy will be omitted. Default is zero (don't delete anything).
	 *				transaction = The transaction to execute database actions within. [Default is null]
	 *              filterTypes = Filter hierarchy showing only item with specified types. Implies showHierarchy option. [Default is null]
	 *
	 * @return string The value
	 */
	public function getDisplayValue($pa_options=null) {
		$use_singular = caGetOption('useSingular', $pa_options, false);
		
		if (isset($pa_options['output'])) {
			switch(strtolower($pa_options['output'])) {
				case 'idno':
					$pa_options['returnIdno'] = true;
					break;
				case 'text':
					$pa_options['returnIdno'] = false;
					$pa_options['idsOnly'] = false;
					$pa_options['returnDisplayText'] = true;
					break;
				default:
					$pa_options['idsOnly'] = true;
					break;
			}
		}
		
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
			if (!in_array(ca_lists::getAccessForItemID($this->opn_item_id), $pa_options['checkAccess'])) { return null; }
		}
		
		if (isset($pa_options['filterTypes']) && $pa_options['filterTypes']) {
			if(!is_array($pa_options['filterTypes'])) { $pa_options['filterTypes'] = [$pa_options['filterTypes']]; }
			$pa_options['showHierarchy'] = true;
		}

        if(!($pa_options['showHierarchy'] ?? false)) {
            if($vb_return_idno = ((isset($pa_options['returnIdno']) && (bool)$pa_options['returnIdno']))) {
                return caGetListItemIdno($this->opn_item_id, $pa_options);
            }
            if($vb_return_idno = ((isset($pa_options['returnDisplayText']) && (bool)$pa_options['returnDisplayText']))) {
                return caGetListItemByIDForDisplay($this->opn_item_id, array_merge($pa_options, ['return' => $use_singular ? 'singular' : 'plural']));
            }

            if(is_null($vb_ids_only = isset($pa_options['idsOnly']) ? (bool)$pa_options['idsOnly'] : null)) {
                $vb_ids_only = isset($pa_options['alwaysReturnItemID']) ? (bool)$pa_options['alwaysReturnItemID'] : false;
            }

            if ($vb_ids_only) { return (int)$this->opn_item_id; }
        }

		$vn_list_id = (is_array($pa_options) && isset($pa_options['list_id'])) ? (int)$pa_options['list_id'] : null;
		
		$t_item = null;
		if (($pa_options['showHierarchy'] ?? false) && !$vn_list_id) {
			$t_item = new ca_list_items();
		    $t_item->load((int)$this->opn_item_id);
		    $vn_list_id = $t_item->get('list_id');
		}
		
		if ($vn_list_id > 0) {
			$t_list = new ca_lists();

			if ($o_trans = (isset($pa_options['transaction']) ? $pa_options['transaction'] : null)) {
				$t_list->setTransaction($o_trans);
			}
			if (!$t_item) { $t_item = new ca_list_items(); }
			if (($pa_options['showHierarchy'] ?? false) || $vb_return_idno) {
				if ($o_trans) { $t_item->setTransaction($o_trans); }
			}

			// do we need to get the hierarchy?
			if ($pa_options['showHierarchy'] ?? false) {
				$vs_get_spec = ((isset($pa_options['useSingular']) && $pa_options['useSingular']) ? 'preferred_labels.name_singular' : 'preferred_labels.name_plural');

				if (!$t_item->isLoaded()) { $t_item->load((int)$this->opn_item_id); }
				
				if (is_array($pa_options['filterTypes'])) {
				    return $t_item->get('ca_list_items.hierarchy.'.$vs_get_spec, array_merge(array('filterTypes' => $pa_options['filterTypes'], 'delimiter' => ' ➔ ', $pa_options)));
				} 
				return $t_item->get('ca_list_items.hierarchy.'.$vs_get_spec, array_merge(array('delimiter' => ' ➔ ', $pa_options)));
			}
			return $t_list->getItemForDisplayByItemID($this->opn_item_id, array_merge($pa_options, ['return' => caGetOption('useSingular', $pa_options, false) ? 'singular' : 'plural']));
		}
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	public function getID() {
		return $this->getItemID();
	}
	# ------------------------------------------------------------------
	public function getItemID() {
		return $this->opn_item_id;
	}
	# ------------------------------------------------------------------
	/**
	 * @param mixed $ps_value
	 * @param array $pa_element_info
	 * @param array $pa_options Options are:
	 *		alwaysTreatValueAsIdno = Always try to convert $ps_value to a list idno value, even if it is numeric
	 *		matchOn =
	 *		serializeCommaSeparatedValues = Convert values in the form "Print, Photo" to a serialized version ("Photo Print") if the provided value fails to match. [Default is true]
	 *
	 * @return array
	 */
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
		$vb_treat_value_as_idno = caGetOption('alwaysTreatValueAsIdno', $pa_options, false);

		if (is_array($ps_value)) { $ps_value = array_pop($ps_value); }

		$serialize_comma_separated_values = caGetOption('serializeCommaSeparatedValues', $pa_options, true);
		$va_match_on = caGetOption('matchOn', $pa_options, null);
		if ($va_match_on && !is_array($va_match_on)){ $va_match_on = array($va_match_on); }
		if (!is_array($va_match_on) && $vb_treat_value_as_idno) { $va_match_on = array('idno', 'label', 'item_id'); }
		if ((!is_array($va_match_on) || !sizeof($va_match_on)) && preg_match('![^\d]+!', $ps_value)) { $va_match_on = array('idno', 'label', 'item_id'); }
		if (($vb_treat_value_as_idno) && (!in_array('idno', $va_match_on))) { array_push($va_match_on, 'idno'); }
		if (!is_array($va_match_on) || !sizeof($va_match_on)) { $va_match_on = ['item_id', 'idno']; }

		$o_trans = caGetOption('transaction', $pa_options, null);

		$vb_require_value = (is_null($pa_element_info['settings']['requireValue'] ?? null)) ? false : (bool)($pa_element_info['settings']['requireValue'] ?? false);

		$ps_orig_value = $ps_value;

		$vn_id = null;

		$t_item = new ca_list_items();
		if($o_trans) { $t_item->setTransaction($o_trans); }

		foreach($va_match_on as $vs_match_on) {
			switch($vs_match_on) {
				case 'idno':
					// try to convert idno to item_id
					if ($vn_id = caGetListItemID($pa_element_info['list_id'], $ps_value, $pa_options)) {
						break(2);
					}
					if($serialize_comma_separated_values && strpos($ps_value, ',')) {
						if ($vn_id = caGetListItemID($pa_element_info['list_id'], caSerializeCommaSeparatedName($ps_value), $pa_options)) {
							break(2);
						}
					}
					break;
				case 'label':
				case 'labels':
					// try to convert label to item_id
					if ($vn_id = caGetListItemIDForLabel($pa_element_info['list_id'], $ps_value, $pa_options)) {
						break(2);
					}
					if($serialize_comma_separated_values && strpos($ps_value, ',')) {
						if ($vn_id = caGetListItemIDForLabel($pa_element_info['list_id'], caSerializeCommaSeparatedName($ps_value), $pa_options)) {
							break(2);
						}
					}
					break;
				case 'item_id':
				default:
					if ($vn_id = ca_list_items::find(array('item_id' => (int)$ps_value, 'list_id' => $pa_element_info['list_id']), array('returnAs' => 'firstId', 'transaction' => $o_trans))) {
						break(2);
					}
					break;
			}
		}
		
		if ((!$vn_id) && ($o_log = caGetOption('log', $pa_options, null)) && (strlen($ps_value) > 0)) {
			$o_log->logError(_t('Value %1 was not set for %2 because it does not exist in list %3', $ps_value, caGetOption('logReference', $pa_options, '???'), caGetListCode($pa_element_info['list_id'])), 
				[	'bundle' => $pa_element_info['element_code'], 
					'values' => $ps_value, 
					'dataset' => caGetOption('dataset', $pa_options, null),
					'idno' => caGetOption('idno', $pa_options, null),
					'row' => caGetOption('row', $pa_options, null),
					'notes' => caGetOption('notes', $pa_options, null)
				]);
		}
		
		if (!$vb_require_value && !$vn_id) {
			if(
				(is_null($ps_value) || !strlen($ps_value ?? '')) 
				&& 
				(!in_array(caGetOption('render', $pa_element_info['settings'] ?? [], null), ['horiz_hierbrowser', 'horiz_hierbrowser_with_search', 'vert_hierbrowser', 'vert_hierbrowser_down'], true))) {
				// value is blank
				return [
					'value_longtext1' => null,
					'item_id' => null
				];
			} else {
				// value was set and is invalid
				return null;
			}
		} elseif ($vb_require_value && !$vn_id && !strlen($ps_value)) {
			$this->postError(1970, _t('Value for %1 [%2] cannot be blank', $pa_element_info['displayLabel'], $pa_element_info['element_code']), 'ListAttributeValue->parseValue()');
			return false;
		} elseif ($vb_require_value && !$vn_id) {
			$this->postError(1970, _t('Value %3 for %1 [%2] is invalid', $pa_element_info['displayLabel'], $pa_element_info['element_code'], $ps_value), 'ListAttributeValue->parseValue()');
			return false;
		}

		return array(
			'value_longtext1' => (int)$vn_id,
			'item_id' => (int)$vn_id,
			'value_sortable' => $this->sortableValue((int)$vn_id)
		);
	}
	# ------------------------------------------------------------------
	/**
	 * Generates HTML form widget for attribute value
	 *
	 * @param $pa_element_info array Array with information about the metadata element with which this value is associated. Keys taken to be ca_metadata_elements field names and the 'settings' field must be deserialized into an array.
	 * @param $pa_options array Array of options. Supported options are:
	 *			width - The width of the list drop-down in characters unless suffixed with 'px' in which case width will be set in pixels.
	 *			any option supported by ca_lists::getListAsHTMLFormElement with the exception of 'maxColumns', which is set out of information in $pa_element_info
	 * @return string HTML code for form element
	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		/** @var RequestHTTP $o_request */
		$o_request = $pa_options['request'];

		$vb_require_value = (is_null($pa_element_info['settings']['requireValue'] ?? false)) ? false : (bool)($pa_element_info['settings']['requireValue'] ?? false);
		
		$render_as = $pa_element_info['settings']['render'] ?? null;
		
		if (($pa_element_info['parent_id']) && ($render_as == 'checklist')) { $render_as = ''; }	// checklists can only be top-level
		
		if ((!isset($pa_options['width']) || !strlen($pa_options['width'])) && isset($pa_element_info['settings']['listWidth']) && strlen($pa_element_info['settings']['listWidth']) > 0) { $pa_options['width'] = $pa_element_info['settings']['listWidth']; }
		if ((!isset($pa_options['height']) || !strlen($pa_options['height'])) && isset($pa_element_info['settings']['listHeight']) && strlen($pa_element_info['settings']['listHeight']) > 0) { $pa_options['height'] = $pa_element_info['settings']['listHeight']; }
		$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : '');

		if (isset($pa_options['nullOption']) && strlen($pa_options['nullOption'])) {
			$vb_null_option = $pa_options['nullOption'];
		} else {
			$vb_null_option = !$vb_require_value ? ($pa_element_info['settings']['nullOptionText'] ?? _t('Not set')) : null;
		}
		
		$vb_implicit_nulls = caGetOption('implicitNullOption', $pa_element_info['settings'], false);
		$use_singular = caGetOption('useSingular', $pa_element_info['settings'], false);

        $for_search = caGetOption('forSearch', $pa_options, false);

		$vs_render = $for_search ? "" : caGetOption('render', $pa_options, caGetOption('render', $pa_element_info['settings'], ''));
		$vb_auto_shrink = (bool) caGetOption('auto_shrink', $pa_options, caGetOption('auto_shrink', $pa_element_info['settings'], false));
		
		$current_selection_display_format = caGetOption('currentSelectionDisplayFormat', $pa_options, caGetOption('currentSelectionDisplayFormat', $pa_element_info['settings'], null));
		$separate_disabled_values = caGetOption('separateDisabledValues', $pa_options, caGetOption('separateDisabledValues', $pa_element_info['settings'], false));
		$hide_disabled_values = caGetOption('hideDisabledValues', $pa_options, caGetOption('hideDisabledValues', $pa_element_info['settings'], false));
		
		$vn_max_columns = $pa_element_info['settings']['maxColumns'] ?? 1;

		if(!isset($pa_options['useDefaultWhenNull'])) {
			$pa_options['useDefaultWhenNull'] = isset($pa_element_info['settings']['useDefaultWhenNull']) ? (bool)$pa_element_info['settings']['useDefaultWhenNull'] : false;
		}

		$vs_element = ca_lists::getListAsHTMLFormElement(
			$pa_element_info['list_id'],
			'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
			array(
				'class' => $vs_class,
				'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}'
			),
			array_merge(
				$pa_options,
				[
					'render' => $vs_render, 'maxColumns' => $vn_max_columns, 
					'element_id' => $pa_element_info['element_id'], 'nullOption' => $vb_null_option, 
					'implicitNullOption' => $vb_implicit_nulls, 'auto_shrink' => $vb_auto_shrink, 
					'currentSelectionDisplayFormat' => $current_selection_display_format,
					'separateDisabledValues' => $separate_disabled_values,
					'hideDisabledValues' => $hide_disabled_values,
					'useSingular' => $use_singular,
					'deferHierarchyLoad' => (bool)($pa_element_info['settings']['deferHierarchyLoad'] ?? false)
				]
			)
		);

		// dependant field visibility
		$vs_show_hide_js = '';
		$cases = $all_ids = [];
		if(Configuration::load()->get('enable_dependent_field_visibility')) {
			switch($render_as) {
				case 'radio_buttons':
					$vs_select = "jQuery('[name={fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}]')";
					break;
				default:
		    		$vs_select = "jQuery('[id^={fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}]')";
		    		break;
		    }
			// only get into outputting all the JS below if hideIfSelected is set for at least one list item for this element
			$vb_print_js = false;
			if(is_array($pa_element_info['settings'])) {
				foreach($pa_element_info['settings'] as $vs_setting_key => $vm_setting_val) {
					if(preg_match('/^hideIfSelected/', $vs_setting_key)) {
						$vb_print_js = true;
					}
				}
			}

			if($vb_print_js) {
				$t_list = new ca_lists();
				$vb_yes_was_set = false;
				
				if(!($pa_element_info['settings']['requireValue'] ?? false) && is_array($pa_element_info['settings']['hideIfSelected___null__'] ?? null) && sizeof($pa_element_info['settings']['hideIfSelected___null__'])) {
				    $va_hideif_for_null = $pa_element_info['settings']['hideIfSelected___null__'];
				    foreach($va_hideif_for_null as $vs_key) {
                        $va_tmp = self::resolveHideIfSelectedKey($vs_key);
                        if(!is_array($va_tmp)) { continue; }

                        $all_ids[] = "Screen".$va_tmp[0]."_".$va_tmp[1].'_bundle';
                    }
                    
                    switch($render_as) {
						case 'radio_buttons':
							$cases[] = [
								'condition' => "((jQuery('[name={fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}]:checked').length == 0) || (jQuery('[name={fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}]:checked').val() == ''))",
								'ids' => $all_ids
							]; 
							break;
						default:
							$cases[] = [
								'condition' => "{$vs_select}.val() == ''",
								'ids' => $all_ids
							]; 
							break;
					}
				}
				if (is_array($va_list_items = $t_list->getItemsForList($pa_element_info['list_id']))) {
					foreach($va_list_items as $va_items_by_locale) {
						foreach ($va_items_by_locale as $vn_locale_id => $va_item) {
							$vs_hide_js = $vs_show_js = $vs_condition = '';
						
							$ids = [];

							if(isset($pa_element_info['settings']['hideIfSelected_'.$va_item['idno']])) {
								$va_hideif_for_idno = $pa_element_info['settings']['hideIfSelected_'.$va_item['idno']];
								if(!is_array($va_hideif_for_idno)) { $va_hideif_for_idno = [$va_hideif_for_idno]; }

								// @todo maybe only generate JS for bundles on current screen? could figure that out from request
								foreach($va_hideif_for_idno as $vs_key) {
									$va_tmp = self::resolveHideIfSelectedKey($vs_key);
									if(!is_array($va_tmp)) { continue; }

									$ids[] = $all_ids[] = "Screen".$va_tmp[0]."_".$va_tmp[1].'_bundle';
								}
							}

							switch($render_as) {
								case 'radio_buttons':
									$vs_condition = "jQuery('input[name={fieldNamePrefix}".$pa_element_info['element_id']."_{n}]:checked').val() === '".$va_item['item_id']."'";
									break;
								case 'yes_no_checkboxes':
									if($vb_yes_was_set) {
										$vs_condition = "!(jQuery('input[name={fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}]').is(':checked'))";
									} else {
										$vb_yes_was_set = true;
										$vs_condition = "jQuery('input[name={fieldNamePrefix}" . $pa_element_info['element_id'] . "_{n}]').is(':checked')";
									}
									break;
								case 'select':
								case null:
									$vs_condition = "{$vs_select}.val() === '" . $va_item['item_id'] . "'";
									break;
								default:
									continue(2);
							}
						
							if (sizeof($ids) > 0) {
								$cases[] = [
									'condition' => $vs_condition,
									'ids' => $ids
								]; 
							}
						}

					}
				}
			}
			
		    if(is_array($cases) && sizeof($cases)) {
			    $all_ids = array_unique($all_ids);
			    $case_switch = array_map(function($v)
			        { 
			            $id_list = array_map(function($x) { return "#{$x}"; }, $v['ids']);
			            
			            $buf = '';
			            foreach($id_list as $id) {
			            	$buf .= "if(xmap['{$id}'] === undefined) { xmap['{$id}'] = false; }";
			            	$buf .= "if(({$v['condition']}) && !xmap['{$id}']) { xmap['{$id}'] = true; }";
			            }
			    		return $buf;
			        }, $cases);
			        
                $vs_show_hide_js = "
<script type='text/javascript'>jQuery(document).ready(function() { 
    var select = {$vs_select};
	select.change(function(e, p) {
		let isInit = (p && p['init']);
		let t = isInit ? 0 : 250;
		let xmap = {};
	    ".join("\n", $case_switch)."
	    for(var k in xmap) {
	    	if(xmap[k]) {
	    		jQuery(k).slideUp(t);
	    	} else {
	    		jQuery(k).width('100%').slideDown(t);
	    	}
	    }
	});
	select.trigger('change', {'init': true});
	caUI.utils.showUnsavedChangesWarning(false);
  });</script>\n";
            }
		}

		return $vs_element.$vs_show_hide_js;
	}
	# ------------------------------------------------------------------
	public function getAvailableSettings($pa_element_info=null) {
		global $_ca_attribute_settings, $g_request;
		$va_element_settings = $_ca_attribute_settings['ListAttributeValue'];

		
		if(
			Configuration::load()->get('enable_dependent_field_visibility') &&
			is_array($pa_element_info) &&
			isset($pa_element_info['list_id']) &&
			// select is the default, so empty does count
			(!( $pa_element_info['settings']['render']) || in_array($pa_element_info['settings']['render'], array('select', 'radio_buttons', 'yes_no_checkboxes'))) &&
			$g_request && ($g_request instanceof RequestHTTP)
		) {
			/*
			 * For the dependent field visibility feature we need to add a select-able list of all applicable
			 * UI bundle placements here ... for each item in that list!
			 */
			$vs_cache_key = "ca_metadata_elements_available_settings_".$pa_element_info['element_id'];
		 
			if (CompositeCache::contains($vs_cache_key, 'ca_metadata_elements_available_settings')) {
				return CompositeCache::fetch($vs_cache_key, 'ca_metadata_elements_available_settings');
			}
			$va_options_for_settings = array();

			$t_mde = new ca_metadata_elements($pa_element_info['element_id']);
			$va_restrictions = $t_mde->getTypeRestrictions();

			$va_tables = array();
			if(is_array($va_restrictions) && sizeof($va_restrictions)) {
				foreach($va_restrictions as $va_restriction) {
					$va_tables[] = $va_restriction['table_num'];
				}
			}

			$t_ui = new ca_editor_uis();
			foreach(array_unique($va_tables) as $vn_table_num) {
				// get UIs
				$va_ui_list = ca_editor_uis::getAvailableUIs($vn_table_num, $g_request);
				foreach($va_ui_list as $vn_ui_id => $vs_ui_name) {
					$t_ui->load($vn_ui_id);
					// get screens
					foreach($t_ui->getScreens() as $va_screen) {
						// get placements
						foreach($t_ui->getScreenBundlePlacements($va_screen['screen_id']) as $va_placement) {
						    $m = [];
						    if (preg_match("!^ca_attribute_([A-Za-z0-9_]+)$!", $va_placement['bundle_name'], $m) && ($m[1] === $pa_element_info['element_code'])) { continue; }
						
							$va_options_for_settings[$t_ui->get('editor_code') . '/'. $va_screen['idno'] . '/' . (isset($m[1]) ? $m[1] : $va_placement['placement_code'])] = $t_ui->get('editor_code') . '/'. $va_screen['idno'] . '/' . $va_placement['placement_code'];
						}
					}
				}
			}

			$t_list = new ca_lists();
			$va_list = $t_list->getItemsForList($pa_element_info['list_id']);
			
			// Only allow dependent visibility on lists with 250 or less items; if we don't impose a limit
			// then large vocabularies will cause things to hang by generating thousands of setting elements
			if (sizeof($va_list) <= 250) {
				foreach($t_list->getItemsForList($pa_element_info['list_id']) as $va_items_by_locale) {
				    if(!$pa_element_info['settings']['requireValue']) {
				        $va_element_settings['hideIfSelected___null__'] = array(
							'formatType' => FT_TEXT,
							'displayType' => DT_SELECT,
							'multiple' => true,
							'options' => $va_options_for_settings,
							'takesLocale' => false,
							'default' => '',
							'width' => "400px", 'height' => 10,
							'label' => _t('Hide bundles if value is not set'),
							'description' => _t('Select bundles from the list below to be hidden when no value is set for this element.')
						);
				    }
					foreach($va_items_by_locale as $vn_locale_id => $va_item) {
						$va_element_settings['hideIfSelected_'.$va_item['idno']] = array(
							'formatType' => FT_TEXT,
							'displayType' => DT_SELECT,
							'multiple' => true,
							'options' => $va_options_for_settings,
							'takesLocale' => false,
							'default' => '',
							'width' => "400px", 'height' => 10,
							'label' => _t('Hide bundles if "%1" is selected', $va_item['name_singular']),
							'description' => _t('Select bundles from the list below.')
						);
					}
				}
			}
            CompositeCache::save($vs_cache_key, $va_element_settings, 'ca_metadata_elements_available_settings');
		} elseif(defined('__CollectiveAccess_Installer__') && Configuration::load()->get('enable_dependent_field_visibility')) {
			// when installing, UIs, screens and placements are not yet available when we process elementSets, so
			// we just add the hideIfSelected_* as available settings (without actual settings) so that the validation doesn't fail
			$t_list = new ca_lists();
			$va_list_items = $t_list->getItemsForList($pa_element_info['list_id']);
			if(is_array($va_list_items) && (sizeof($va_list_items) > 0)) {
				foreach($va_list_items as $va_items_by_locale) {
					foreach($va_items_by_locale as $vn_locale_id => $va_item) {
						$va_element_settings['hideIfSelected_'.$va_item['idno']] = ['deferred' => true, 'multiple' => true];
					}
				}
				$va_element_settings['hideIfSelected___null__'] = ['deferred' => true, 'multiple' => true];
			}
		}
		
		return $va_element_settings;
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
		return ['value_longtext1', 'item_id'];
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
		return mb_strtolower(substr(trim(preg_replace('![^A-Za-z0-9 ]+!', '', caGetListItemIdno((int)$value))), 0, 100));
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

		if(preg_match("/^hideIfSelected/", $ps_setting_key)) {
			if(isset($pa_element_info['settings']['render']) && !is_null($pa_element_info['settings']['render'])) {
				if (!in_array($pa_element_info['settings']['render'], array('radio_buttons', 'select', 'yes_no_checkboxes'))) {
					$ps_error = _t("dependent field visibility is only supported for radio buttons and drop-down (select) menus");
					return false;
				}
			}
		}

		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns constant for list attribute value
	 *
	 * @return int Attribute value type code
	 */
	public function getType() {
		return __CA_ATTRIBUTE_VALUE_LIST__;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns a list of ui id, screen id and placement id for a given setting key (editor_code/screen_idno/placement_code)
	 * @param string $ps_key
	 * @return array|bool
	 */
	public static function resolveHideIfSelectedKey($ps_key) {
		if(CompositeCache::contains($ps_key, 'ListAttrHideIfSelected')) {
			return CompositeCache::fetch($ps_key, 'ListAttrHideIfSelected');
		}

		$va_tmp = explode('/', $ps_key);
		if(!sizeof($va_tmp) == 3) { return false; }

		// ui
		$t_ui = new ca_editor_uis();
		if(!$t_ui->load(array('editor_code' => $va_tmp[0]))) {
			return false;
		}

		// screen
		$t_screen = new ca_editor_ui_screens();
		if(!$t_screen->load(array('ui_id' => $t_ui->getPrimaryKey(), 'idno' => $va_tmp[1]))) {
			return false;
		}

		// placement
		$t_placement = new ca_editor_ui_bundle_placements();
		if(!$t_placement->load(array('screen_id' => $t_screen->getPrimaryKey(), 'placement_code' => $va_tmp[2]))) {
			return false;
		}

		$va_ret = array(
			$t_screen->getPrimaryKey(),
			$t_placement->getPrimaryKey()
		);

		CompositeCache::save($ps_key, $va_ret, 'ListAttrHideIfSelected');
		return $va_ret;
	}
	# ------------------------------------------------------------------
}
