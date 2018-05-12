<?php
/** ---------------------------------------------------------------------
 * app/lib/MetadataAlerts/TriggerTypes/Date.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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
 * @subpackage MetadataAlerts
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\MetadataAlerts\TriggerTypes;

require_once(__CA_MODELS_DIR__ . '/ca_metadata_elements.php');

class Date extends Base {

	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	public function getTypeSpecificSettings() {
		return [
			'offset' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_INTERVAL,
				'width' => 5, 'height' => 1,
				'default' => '0|HOURS|AFTER',
				'label' => _t('Notify user'),
				'suffix' => _t('date'),
				'description' => _t('Set an interval before or after an event in which to trigger a notification. Note that if the event is a date range, the trigger will fire before the beginning of the range or after the end of the range.')
			),
		];
	}

	public function getTriggerType() {
		// date triggers are always periodic
		return __CA_MD_ALERT_CHECK_TYPE_PERIODIC__;
	}

	/**
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @return bool
	 */
	public function check(&$t_instance) {
		$o_tep = new \TimeExpressionParser();

		$va_values = $this->getTriggerValues();
		if(!sizeof($va_values)) { return false; }
		if(!$va_values['element_id']) { return false; }

		$va_spec = $this->_getSpec($t_instance);
		$vs_element_code = $va_spec['element_code'];
		$vs_get_spec = $va_spec['spec'];
		
		if (is_array($va_filters = $va_values['element_filters']) && !sizeof($va_filters)) { $va_filters = null; }

		if(\ca_metadata_elements::getDataTypeForElementCode($vs_element_code) !== __CA_ATTRIBUTE_VALUE_DATERANGE__) {
			return false;
		}
		
		foreach($t_instance->get($vs_get_spec, ['returnAsArray' => true, 'dateFormat' => 'iso8601', 'filters' => $va_filters]) as $vs_val) {
			$o_tep->parse($vs_val);
			
			// offset should be in seconds
			$vn_offset = self::offsetToSeconds($this->getTriggerValues()['settings']['offset']);
			if(($vn_offset <= 0) && (time() > (($o_tep->getUnixTimestamps()['start']) - abs($vn_offset)))) {
				return true;
			} elseif(time() >= ($o_tep->getUnixTimestamps()['end'] + abs($vn_offset))) {
				return true;
			}
		}

		return false;
	}
	
	/**
	 * Limit elements this trigger applies to to date ranges
	 *
	 * @return array
	 */
	public function getElementDataTypeFilters() {
		return [__CA_ATTRIBUTE_VALUE_DATERANGE__];
	}
	
	/**
	 * Return additional filter values for specified metadata element. Return null for no filtering.
	 *
	 * @return string
	 */
	public function getElementFilters($pn_element_id, $ps_prefix_id, array $pa_options=[]) {
		if ($t_root = \ca_metadata_elements::getInstance(\ca_metadata_elements::getElementHierarchyID($pn_element_id))) {
			// filter on list elements in containers
			if($t_root->get('datatype') == __CA_ATTRIBUTE_VALUE_CONTAINER__) {
				$va_html = [];
				
				$va_values = caGetOption('values', $pa_options, []);
				foreach($va_elements = $t_root->getElementsInSet() as $va_element) {
					if ($va_element['datatype'] == __CA_ATTRIBUTE_VALUE_LIST__) {
						if ($vs_list = \ca_lists::getListAsHTMLFormElement(
							$va_element['list_id'], "{$ps_prefix_id}_element_filter_".$va_element['element_code']."[]", 
							['id' => "{$ps_prefix_id}_element_filter_".$va_element['element_code']], 
							['maxItemCount' => 100, 'render' => 'multiple', 'values' => caGetOption($va_element['element_code'], $va_values, null)]
						)) {
							$va_html[] = "<span class='formLabelPlain'>".$va_element['display_label'].':</span><br/>'.$vs_list;
						}
					}
				}
				return $va_html;
			}
		}
		return null;
	}
	
	/**
	 * Unique key for trigger event
	 *
	 * @param BaseModel $t_instance
	 *
	 * @return string Always returns null
	 */
	public function getEventKey($t_instance) {
		$va_spec = $this->_getSpec($t_instance);
		return md5($t_instance->tableName().'/'.$t_instance->getPrimaryKey().'/'.$t_instance->get($va_spec['spec']));
	}
	
	/**
	 * Extra data to attach to notification for triggered event
	 *
	 * @param BaseModel $t_instance
	 *
	 * @return string Always returns null
	 */
	public function getData($t_instance) {
		return ['table_num' => $t_instance->tableNum(), 'table_name' => $t_instance->tableName(), 'row_id' => $t_instance->getPrimaryKey()];
	}
	
	/** 
	 *
	 */
	public function _getSpec($t_instance) {
		$va_values = $this->getTriggerValues();
		$vs_element_code = \ca_metadata_elements::getElementCodeForId($va_values['element_id']);
		if($vs_parent_code = \ca_metadata_elements::getParentCode($vs_element_code)) {
			$vs_get_spec = $t_instance->tableName().'.'.$vs_parent_code.'.'.$vs_element_code;
		} else {
			$vs_get_spec = $t_instance->tableName().'.'.$vs_element_code;
		}
		return ['table' => $t_instance->tableName(), 'parent_code' => $vs_parent_code, 'element_code' => $vs_element_code, 'spec' => $vs_get_spec];
	}
	
	/**
	 * Return query criteria for BundlableLabelableBaseModelWithAttributes::find() to generate a set of records that may require 
	 * notifications. For dates, we take the offset and generate a date range around the current date/time to search on.
	 *
	 * @param array $pa_trigger_values
	 *
	 * @return array Query parameters for use with BundlableLabelableBaseModelWithAttributes::find() or false if trigger is invalid.
	 */
	public function getTriggerQueryCriteria($pa_trigger_values) {
		$o_tep = new \TimeExpressionParser();
		
		// Get element code
		if (!($vs_element_code = \ca_metadata_elements::getElementCodeForId($pa_trigger_values['element_id']))) { return false; }
		
		// Bail if element is not a date
		if(\ca_metadata_elements::getDataTypeForElementCode($vs_element_code) !== __CA_ATTRIBUTE_VALUE_DATERANGE__) {
			return false;
		}
		$vs_parent_code = \ca_metadata_elements::getElementCodeForId(\ca_metadata_elements::getElementHierarchyID($vs_element_code));
		
		$vn_offset = self::offsetToSeconds($pa_trigger_values['settings']['offset']);		// in seconds
		
		// Windows are always a day wide on the assumption notifications will be checked at least once a day
		// If they're not, some notifications will be missed
		
		if ($vn_offset <= 0) {
			// Notifications where user wants to know *before* the event happens of *when* it happens (offset = 0)
			$vn_start = time();										// start: now
			$vn_end = time() + abs($vn_offset) + (12 * 60 * 60);	// end: offset seconds + 12 hours in the future
		} else {
			// Notifications where user wants to know *after* the event happens
			$vn_start = time() - abs($vn_offset) - (12 * 60 * 60);					// start: offset seconds in the past
			$vn_end = time();										// end:  now
		}
		
		$va_criteria = [];
		
		$vs_date_range = caGetLocalizedDateRange($vn_start, $vn_end, ['timeOmit' => false]);
		
		if ($vs_parent_code) {
			$va_criteria[$vs_parent_code] = [$vs_element_code => $vs_date_range];
		} else {
			$va_criteria[$vs_element_code] = $vs_date_range;
		}
		return $va_criteria;
	}
	
	/**
	 *
	 */
	public static function offsetToSeconds($ps_offset) {
		$pa_offset = explode('|', $ps_offset);
		if (sizeof($pa_offset) != 3) { return 0; }
		
		$n = (float)$pa_offset[0];
		switch(strtolower($pa_offset[1])) {
			case 'hours':
				$n = $n * 60 * 60;	
				break;
			case 'minutes':
				$n = $n * 60;	
				break;
			case 'seconds':
				// noop
				break;
			case 'weeks':
				$n = $n * 60 * 60 * 24 * 7;	
				break;
			default:
			case 'days':
				$n = $n * 60 * 60 * 24;	
				break;
		}
		
		if (strtolower($pa_offset[2]) == 'before') {
			$n = $n * -1;
		}
		return $n;
	}
}
