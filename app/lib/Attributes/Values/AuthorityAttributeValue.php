<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/AuthorityAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/BaseObject.php');
require_once(__CA_LIB_DIR__.'/Attributes/Attribute.php');
require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');

abstract class AuthorityAttributeValue extends AttributeValue {
	# ------------------------------------------------------------------
	/**
	 *
	 */
	protected $ops_text_value;

	/**
	 *
	 */
	protected $opn_id;

	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct($pa_value_array=null) {
		parent::__construct($pa_value_array);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function loadTypeSpecificValueFromRow($pa_value_array) {
		$this->ops_text_value = $pa_value_array['value_longtext1'];
		$this->opn_id = $pa_value_array['value_integer1'];
	}
	# ------------------------------------------------------------------
	/**
	 * Get string value of attribute for display.
	 *
	 * @param array Optional array of options. Supported options include:
	 * 			returnIdno = If true list item idno is returned rather than preferred label [Default is false]
	 *			idsOnly = Return numeric item_id only [Default is false]
	 *			alwaysReturnItemID = Synonym for idsOnly [Default is false]
	 *			output = Authority value to return. Valid values are text [display text], idno [identifier; same as returnIdno option], value [numeric id; same as idsOnly option]. [Default is value]
	 *			forDuplication = Forces value suitable duplication of the record. This is almost always the numeric primary key ID for the related authority record. [Default is false]
	 *			includeID = Include numeric primary key ID at end of display text, surrounded by brackets (Eg. [353]) [Default is false]
	 *			template =  Display template for format returned value with. Template is evaluated related to the related authority record. [Default is null]
	 *          checkAccess = Only return list items with a specified access value. [Default is null; no filtering performed]
	 * @return string The value
	 */
	public function getDisplayValue($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (caGetOption('forDuplication', $pa_options, false)) {
			return $this->opn_id;
		}
		if (isset($pa_options['output'])) {
			switch(strtolower($pa_options['output'])) {
				case 'idno':
					$pa_options['returnIdno'] = true;
					break;
				case 'text':
					$pa_options['returnIdno'] = false;
					$pa_options['idsOnly'] = false;
					break;
				default:
					$pa_options['idsOnly'] = true;
					break;
			}
		}
		
		$vs_idno = $this->elementTypeToInstance($this->getType())->getIdnoForID($this->opn_id, $pa_options);
		if($vs_idno === false) { return null; } // failed checkAccess checks
		
		if (caGetOption('returnIdno', $pa_options, false)) {
			return $vs_idno;
		}

		$o_config = Configuration::load();
		if(is_array($va_lookup_template = $o_config->getList($this->ops_table_name.'_lookup_settings'))) {
			$vs_default_template = join($o_config->get($this->ops_table_name.'_lookup_delimiter'), $va_lookup_template);
		} else {
			$vs_default_template = "^".$this->ops_table_name.".preferred_labels";
		}

		$ps_template = (string)caGetOption('template', $pa_options, $vs_default_template);
		$vb_include_id = (bool)caGetOption('includeID', $pa_options, false);
		$vb_ids_only = (bool)caGetOption('idsOnly', $pa_options, false);

		if ($vb_ids_only) { return $this->opn_id; }
		return $this->opn_id ? caProcessTemplateForIDs($ps_template, $this->ops_table_name, array($this->opn_id), array('returnAsArray' => false, 'returnAllLocales' => false)).($vb_include_id ? " [".$this->opn_id."]" : '') : "";
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getID() {
		return $this->opn_id;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
		if (!strlen($ps_value)) {
			// record truly empty values as null for now
			return array(
				'value_longtext1' => null,
				'value_integer1' => null
			);
		}
		$vb_require_value = (is_null($pa_element_info['settings']['requireValue'])) ? true : (bool)$pa_element_info['settings']['requireValue'];
        $vb_treat_value_as_idno = caGetOption('alwaysTreatValueAsIdno', $pa_options, false);
		$o_trans = caGetOption('transaction', $pa_options, null);

		$va_match_on = caGetOption('matchOn', $pa_options, null);
		if ($va_match_on && !is_array($va_match_on)){ $va_match_on = array($va_match_on); }
		if (!is_array($va_match_on) && $vb_treat_value_as_idno) { $va_match_on = array('idno'); }
		if ((!is_array($va_match_on) || !sizeof($va_match_on)) && preg_match('![^\d]+!', $ps_value)) { $va_match_on = array('idno'); }
		if (($vb_treat_value_as_idno) && (!in_array('idno', $va_match_on))) { array_push($va_match_on, 'idno'); }
		if (!is_array($va_match_on) || !sizeof($va_match_on)) { $va_match_on = array('row_id'); }

		$vn_id = null;

		$t_item = Datamodel::getInstanceByTableName($this->ops_table_name, true);

		foreach($va_match_on as $vs_match_on) {
			switch($vs_match_on) {
				case 'idno':
					// try to convert idno to row_id
					if ($vn_id = call_user_func($this->ops_table_name.'::find', array($t_item->getProperty('ID_NUMBERING_ID_FIELD') => $ps_value), array('transaction' => $o_trans, 'returnAs' => 'firstId'))) {
						break(2);
					}
					break;
				case 'label':
				case 'labels':
					// try to convert label to row_id
					if ($vn_id = call_user_func($this->ops_table_name.'::find', array('preferred_labels' => array($t_item->getLabelDisplayField() => $ps_value)), array('transaction' => $o_trans, 'returnAs' => 'firstId'))) {
						break(2);
					}
					break;
				case 'row_id':
				default:
					if ($vn_id = call_user_func($this->ops_table_name.'::find', array($t_item->primaryKey() => $ps_value), array('transaction' => $o_trans, 'returnAs' => 'firstId'))) {
						break(2);
					}
					break;
			}
		}
		
		
		if ((!$vn_id) && ($o_log = caGetOption('log', $pa_options, null))) {
			$o_log->logError(_t('Value %1 was not set for %2 because it does not refer to an existing %3', $ps_value, caGetOption('logIdno', $pa_options, '???'), $t_item->getProperty('name_singular')));
		}

		if (!$vb_require_value && !$vn_id) {
			return array(
				'value_longtext1' => null,
				'value_integer1' => null
			);
		}
		if (!$vn_id) {
			$this->postError(1970, _t('%1 id %2 is not valid for element %3', $this->ops_name_singular, $ps_value, $pa_element_info["element_code"]), $this->ops_name_plural.'AttributeValue->parseValue()');
			return false;
		}

		return array(
			'value_longtext1' => (int)$vn_id,
			'value_integer1' => (int)$vn_id
		);
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
		$t_instance = self::elementTypeToInstance($this->getType());

		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'restrictToTypes'));
		$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : 'lookupBg');

		if ($pa_options['request']) {
			if($va_restrict_to_types = array_filter(caGetOption('restrictToTypes', $va_settings, [], ['castTo' => 'array']), function($v) { return strlen($v); })) { 
				$va_params = array('max' => 50, 'types' => join(";", $va_restrict_to_types));
			} elseif($vs_restrict_to_type = caGetOption('restrictTo'.$this->ops_name_singular.'TypeIdno', $pa_element_info['settings'], null)) {
				$va_params = array('max' => 50, 'type' => $vs_restrict_to_type);
			} else {
				$va_params = array('max' => 50);
			}
			$vs_url = caNavUrl($pa_options['request'], 'lookup', $this->ops_name_singular, 'Get', $va_params);
		} else {
			// no lookup is possible
			return $this->getDisplayValue();
		}

		$va_pieces = caEditorUrl($pa_options['request'], $t_instance->tableName(), 0, true);
		$va_pieces['controller'] = str_replace('Editor', 'QuickAdd', $va_pieces['controller']);
		$va_pieces['action'] = 'Form';

		$vs_quickadd_url = caNavUrl(
			$pa_options['request'], $va_pieces['module'], $va_pieces['controller'], $va_pieces['action'], array($t_instance->primaryKey() => 0)
		);

		$o_view = new View($pa_options['request'], $pa_options['request']->getViewsDirectoryPath()."/bundles/");
		$o_view->setVar('field_name_prefix', "{fieldNamePrefix}{$pa_element_info['element_id']}");
		$o_view->setVar('quickadd_url', $vs_quickadd_url);
		$o_view->setVar('lookup_url', $vs_url);
		$o_view->setVar('options', $pa_options);
		$o_view->setVar('settings', $va_settings);
		$o_view->setVar('element_info', $pa_element_info);
		$o_view->setVar('class', $vs_class);
		
		$o_view->setVar('allowQuickadd', (strpos($pa_options['request']->getController(), 'Interstitial') === false));

		return $o_view->render('authority_attribute.php');
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getAvailableSettings($pa_element_info=null) {
		global $_ca_attribute_settings;

		return $_ca_attribute_settings[$this->ops_name_plural.'AttributeValue'];
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
	 * Returns name of table
	 *
	 * @return string 
	 */
	public function tableName() {
		return $this->ops_table_name;
	}
	# ------------------------------------------------------------------
	/**
	 * Intercept calls to get*ID, where * = the singular name of the authority attribute (Eg. "Entity")
	 * and reroute to getID(). The provides support for legacy table-specific getID() calls.
	 */
	public function __call($ps_method, $pa_params) {
		if ($ps_method == 'get'.$this->ops_name_singular.'ID') {
			return $this->getID($pa_params[0]);
		}
		throw new Exception(_t('Method %1 does not exist for %2 attributes', $ps_method, $this->ops_name_singular));
	}
	# ------------------------------------------------------
	/**
	 * Returns a model instance for the table  associated with the specified attribute datatype number.
	 * Eg. If you pass the __CA_ATTRIBUTE_VALUE_ENTITIES__ constant then a "ca_entities" instance will be returned.
	 *
	 * @param mixed $pn_type The name or number of the table
	 * @return BaseModel A table instance or null if the datatype number is not an authority attribute datatype.
	 */
	public static function elementTypeToInstance($pn_type) {
		switch($pn_type) {
			case __CA_ATTRIBUTE_VALUE_LIST__:
				return Datamodel::getInstanceByTableName('ca_list_items', true);
				break;
			case __CA_ATTRIBUTE_VALUE_OBJECTS__:
				return Datamodel::getInstanceByTableName('ca_objects', true);
				break;
			case __CA_ATTRIBUTE_VALUE_ENTITIES__:
				return Datamodel::getInstanceByTableName('ca_entities', true);
				break;
			case __CA_ATTRIBUTE_VALUE_PLACES__:
				return Datamodel::getInstanceByTableName('ca_places', true);
				break;
			case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:
				return Datamodel::getInstanceByTableName('ca_occurrences', true);
				break;
			case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:
				return Datamodel::getInstanceByTableName('ca_collections', true);
				break;
			case __CA_ATTRIBUTE_VALUE_LOANS__:
				return Datamodel::getInstanceByTableName('ca_loans', true);
				break;
			case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:
				return Datamodel::getInstanceByTableName('ca_movements', true);
				break;
			case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:
				return Datamodel::getInstanceByTableName('ca_storage_locations', true);
				break;
			case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:
				return Datamodel::getInstanceByTableName('ca_object_lots', true);
				break;
			case __CA_ATTRIBUTE_VALUE_OBJECTREPRESENTATIONS__:
				return Datamodel::getInstanceByTableName('ca_object_representations', true);
				break;
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns attribute datatype number associated with the authority attribute type for the specified table.
	 * Eg. If you pass "ca_entities" then the __CA_ATTRIBUTE_VALUE_ENTITIES__ constant will be returned.
	 *
	 * @param mixed $pm_table_name_or_num The name or number of the table
	 * @return int An attribute datatype number or null if the table does not have an associated attribute type
	 */
	public static function tableToElementType($pm_table_name_or_num) {
		$vs_table = Datamodel::getTableName($pm_table_name_or_num);
		switch($vs_table) {
			case 'ca_list_items':
				require_once(__CA_LIB_DIR__."/Attributes/Values/ListAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_LIST__;
				break;
			case 'ca_objects':
				require_once(__CA_LIB_DIR__."/Attributes/Values/ObjectsAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_OBJECTS__;
				break;
			case 'ca_entities':
				require_once(__CA_LIB_DIR__."/Attributes/Values/EntitiesAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_ENTITIES__;
				break;
			case 'ca_places':
				require_once(__CA_LIB_DIR__."/Attributes/Values/PlacesAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_PLACES__;
				break;
			case 'ca_occurrences':
				require_once(__CA_LIB_DIR__."/Attributes/Values/OccurrencesAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_OCCURRENCES__;
				break;
			case 'ca_collections':
				require_once(__CA_LIB_DIR__."/Attributes/Values/CollectionsAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_COLLECTIONS__;
				break;
			case 'ca_loans':
				require_once(__CA_LIB_DIR__."/Attributes/Values/LoansAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_LOANS__;
				break;
			case 'ca_movements':
				require_once(__CA_LIB_DIR__."/Attributes/Values/MovementsAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_MOVEMENTS__;
				break;
			case 'ca_storage_locations':
				require_once(__CA_LIB_DIR__."/Attributes/Values/StorageLocationsAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__;
				break;
			case 'ca_object_lots':
				require_once(__CA_LIB_DIR__."/Attributes/Values/ObjectLotsAttributeValue.php");
				return __CA_ATTRIBUTE_VALUE_OBJECTLOTS__;
				break;
		}
		return null;
	}
	# ------------------------------------------------------------------
}
