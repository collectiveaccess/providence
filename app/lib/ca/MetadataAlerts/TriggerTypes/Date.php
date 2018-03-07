<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/MetadataAlerts/TriggerTypes/Date.php
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
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 5, 'height' => 1,
				'default' => 0,
				'label' => _t('Number of days before or after the event'),
				'description' => _t('If set to a non-zero value, this trigger will fire X number of days before or after event. You can enter negative values to set up a warning before the scheduled event. Note that if the event is a date range, the trigger will fire X days before the beginning of the range or X days after the end of the range.')
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

		if(\ca_metadata_elements::getDataTypeForElementCode($vs_element_code) !== __CA_ATTRIBUTE_VALUE_DATERANGE__) {
			return false;
		}

		foreach($t_instance->get($vs_get_spec, ['returnAsArray' => true, 'dateFormat' => 'iso8601']) as $vs_val) {
			$o_tep->parse($vs_val);

			// offset should be in days -- convert to seconds
			$vn_offset = $this->getTriggerValues()['settings']['offset'] * 60 * 60 * 24;

			// @todo: impose some kind of limit. a trigger set to a date
			// @todo: should not fire every single day after that.
			if($vn_offset < 0) {
				if(time() > (($o_tep->getUnixTimestamps()['start']) + $vn_offset)) {
					return true;
				}
			} else {
				if((time() - $vn_offset) > $o_tep->getUnixTimestamps()['start']) {
					return true;
				}
			}
		}

		return false;
	}
	
	/**
	 * 
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
		
		$vn_offset = $pa_trigger_values['settings']['offset'] * 60 * 60 * 24;
		
		if ($vn_offset <= 0) {
			$vn_start = time() + $vn_offset;
			$vn_end = time();
		} else {
			$vn_start = time();
			$vn_end = time() + $vn_offset;
		}
		
		$va_criteria = [];
		
		$vs_date_range = caGetLocalizedDateRange($vn_start, $vn_end, ['timeOmit' => true]);
		
		if ($vs_parent_code) {
			$va_criteria[$vs_parent_code] = [$vs_element_code => $vs_date_range];
		} else {
			$va_criteria[$vs_element_code] = $vs_date_range;
		}
		return $va_criteria;
	}
}
