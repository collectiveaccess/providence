<?php
/** ---------------------------------------------------------------------
 * app/lib/MetadataAlerts/TriggerTypes/Modification.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2022 Whirl-i-Gig
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

class Modification extends Base {
	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	public function getTypeSpecificSettings() {
		return [
			'expression' => [
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => '670px', 'height' => 3,
				'default' => '',
				'label' => _t('Expression'),
				'suffix' => _t(''),
				'description' => _t('Expression to evaluate when record is saved.')
			]
		];
	}

	public function getTriggerType() {
		return __CA_MD_ALERT_CHECK_TYPE_SAVE__;
	}

	/**
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @return bool
	 */
	public function check(&$t_instance) {
		$values = $this->getTriggerValues();
		if(!sizeof($values)) { return false; }
		if (is_array($filters = $values['element_filters']) && !sizeof($filters)) { $filters = null; }
		unset($filters['_non_element_filter']);
		
		$non_element_filter = $values['element_filters']['_non_element_filter'] ?? null;
		
		$is_modified = null;
		if(!$values['element_id'] && !$non_element_filter) {
			// Trigger on any change
			$is_modified = $t_instance->hasChangedSinceLoad();
		}
		
		if(is_null($is_modified)) {
			if ($non_element_filter) {
				switch($non_element_filter) {
					case '_intrinsic_idno':
						$is_modified = $t_instance->didChange($t_instance->getProperty('ID_NUMBERING_ID_FIELD'));
						break;
					case '_preferred_labels':
						$is_modified = $t_instance->changed('preferred_labels');
						break;
					case '_nonpreferred_labels':
						$is_modified = $t_instance->changed('nonpreferred_labels');
						break;
					default:
						$is_modified = false;
						break;
				}
			} else {
				// Trigger on specific element
				$code = \ca_metadata_elements::getElementCodeForId($values['element_id']);
				$parent_code = \ca_metadata_elements::getParentCode($values['element_id']);
				$get_spec = $code;
				if ($parent_code && $parent_code !== $code){
					$get_spec = "$parent_code.$get_spec";
				}
				if (is_array($filter_vals = caGetOption($code, $filters, null)) && sizeof($filter_vals)) {
					$values = $t_instance->get($t_instance->tableName().".{$get_spec}", ['returnAsArray' => true]);
					if (! ( array_intersect( $values, $filter_vals ) ) ) {
						$is_modified = false;
					}
				}
				
				if(!is_null($is_modified)) {
					$is_modified = $t_instance->attributeDidChange($code);
				}
			}
		}
		
		$expression = $values['settings']['expression'] ?? null;
		if((bool)$is_modified && strlen($expression)) {
			$tags = caGetTemplateTags($expression) ?? [];
			$exp_values = [];
			foreach($tags as $t) {
				$exp_values[$t] = $t_instance->get($t);
			}
		
			try {
				if (\ExpressionParser::evaluate($expression, $exp_values)) {
					return true;
				}
			} catch(Exception $e) {
				// Invalid expression
				throw new MetadataAlertExpressionException(_t('Invalid expression specified for alert: %1', $e->getMessage()), $expression);
			}
		}
		return (bool)$is_modified;
	}
	
	/**
	 * Return additional filter values for specified metadata element. Return null for no filtering.
	 *
	 * @return string
	 */
	public function getElementFilters($element_id, $prefix_id, array $options=[]) {
		if ($t_element = \ca_metadata_elements::getInstance($element_id)) {
			// filter on list elements in containers
			if($t_element->get('datatype') == __CA_ATTRIBUTE_VALUE_LIST__) {
				$html = [];
				
				$values = caGetOption('values', $options, []);
				$element_code = $t_element->get('element_code');
				if ($list = \ca_lists::getListAsHTMLFormElement(
					$t_element->get('list_id'), "{$prefix_id}_element_filter_{$element_code}"."[]", 
					['id' => "{$prefix_id}_element_filter_{$element_code}"], 
					['maxItemCount' => 100, 'render' => 'multiple', 'values' => caGetOption($element_code, $values, null)]
				)) {
					$html[] = "<span class='formLabelPlain'>".$t_element->get('ca_metadata_elements.preferred_labels.name').':</span><br/>'.$list;
				}
				return $html;
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
