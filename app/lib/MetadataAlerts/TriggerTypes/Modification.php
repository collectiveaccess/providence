<?php
/** ---------------------------------------------------------------------
 * app/lib/MetadataAlerts/TriggerTypes/Modification.php
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

class Modification extends Base {

	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	public function getTypeSpecificSettings() {
		return [];
	}

	public function getTriggerType() {
		return __CA_MD_ALERT_CHECK_TYPE_SAVE__;
	}

	/**
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @return bool
	 */
	public function check(&$t_instance) {
		$va_values = $this->getTriggerValues();
		if(!sizeof($va_values)) { return false; }
		if (is_array($va_filters = $va_values['element_filters']) && !sizeof($va_filters)) { $va_filters = null; }
		unset($va_filters['_non_element_filter']);
		
		$vs_non_element_filter = $va_values['element_filters']['_non_element_filter'];
		
		if(!$va_values['element_id'] && !$vs_non_element_filter) {
			// Trigger on any change
			return $t_instance->hasChangedSinceLoad();
		}
		
		if ($vs_non_element_filter) {
			switch($vs_non_element_filter) {
				case '_intrinsic_idno':
					return $t_instance->didChange($t_instance->getProperty('ID_NUMBERING_ID_FIELD'));
					break;
				case '_preferred_labels':
					return $t_instance->changed('preferred_labels');
					break;
				case '_nonpreferred_labels':
					return $t_instance->changed('nonpreferred_labels');
					break;
				default:
					return false;
					break;
			}
		} else {
			// Trigger on specific element
			$vs_code = \ca_metadata_elements::getElementCodeForId($va_values['element_id']);
			if (is_array($va_filter_vals = caGetOption($vs_code, $va_filters, null)) && sizeof($va_filter_vals)) {
				if(!in_array($t_instance->get($t_instance->tableName().".{$vs_code}"), $va_filter_vals)) { return false; }
			}
			return $t_instance->elementDidChange($vs_code);
		}
	}
	
	/**
	 * Return additional filter values for specified metadata element. Return null for no filtering.
	 *
	 * @return string
	 */
	public function getElementFilters($pn_element_id, $ps_prefix_id, array $pa_options=[]) {
		if ($t_element = \ca_metadata_elements::getInstance($pn_element_id)) {
			// filter on list elements in containers
			if($t_element->get('datatype') == __CA_ATTRIBUTE_VALUE_LIST__) {
				$va_html = [];
				
				$va_values = caGetOption('values', $pa_options, []);
				$vs_element_code = $t_element->get('element_code');
				if ($vs_list = \ca_lists::getListAsHTMLFormElement(
					$t_element->get('list_id'), "{$ps_prefix_id}_element_filter_{$vs_element_code}"."[]", 
					['id' => "{$ps_prefix_id}_element_filter_{$vs_element_code}"], 
					['maxItemCount' => 100, 'render' => 'multiple', 'values' => caGetOption($vs_element_code, $va_values, null)]
				)) {
					$va_html[] = "<span class='formLabelPlain'>".$t_element->get('ca_metadata_elements.preferred_labels.name').':</span><br/>'.$vs_list;
				}
				return $va_html;
			}
		}
		return null;
	}
	
	/**
	 * List of elements to add to standard list of element. Return null if
	 * no elements are to be added.
	 *
	 * @return array
	 */
	public function getAdditionalElementList() {
		return [
			'_preferred_labels' => _t('Preferred labels'),
			'_nonpreferred_labels' => _t('Non-preferred labels'),
			'_intrinsic_idno' => _t('Identifer (idno)')
		];
	}
}
