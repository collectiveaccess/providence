<?php
/** ---------------------------------------------------------------------
 * app/helpers/importHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   

	# ---------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_refinery_name
	 * @param string $ps_table
	 * @param array $pa_parents 
	 * @param array $pa_source_data
	 * @param array $pa_item
	 * @param string $ps_delimiter
	 * @param int $pn_c
	 * @param KLogger $o_log
	 * 
	 * @return int
	 */
	function caProcessRefineryParents($ps_refinery_name, $ps_table, $pa_parents, $pa_source_data, $pa_item, $ps_delimiter, $pn_c, $o_log=null) {
		global $g_ui_locale_id;
		
		if (!is_array($pa_parents)) { $pa_parents = array($pa_parents); }
		$vn_id = null;
		
		$pa_parents = array_reverse($pa_parents);
		foreach($pa_parents as $vn_i => $va_parent) {
			$vs_name = BaseRefinery::parsePlaceholder($va_parent['name'], $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' '));
			$vs_idno = BaseRefinery::parsePlaceholder($va_parent['idno'], $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' '));
			$vs_type = BaseRefinery::parsePlaceholder($va_parent['type'], $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' '));
			if (!$vs_name && !$vs_idno) { continue; }
			if (!$vs_name) { $vs_name = $vs_idno; }
			
			$va_attributes = (isset($va_parent['attributes']) && is_array($va_parent['attributes'])) ? $va_parent['attributes'] : array();
			
			foreach($va_attributes as $vs_element_code => $va_attrs) {
				if(is_array($va_attrs)) {
					foreach($va_attrs as $vs_k => $vs_v) {
						// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
						// DataMigrationUtils::getCollectionID(), which ca_data_importers::importDataFromSource() uses to create related collections
						// only supports non-repeating attribute values, so we join any values here and call it a day.
						$va_attributes[$vs_element_code][$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' '));
					}
				} else {
					$va_attributes[$vs_element_code] = array($vs_element_code => BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' ')));
				}
			}
			
			$va_attributes['idno'] = $vs_idno;
			$va_attributes['parent_id'] = $vn_id;
			
			if (isset($va_parent['rules']) && is_array($va_parent['rules'])) { 
				foreach($va_parent['rules'] as $va_rule) {
					$vm_ret = ExpressionParser::evaluate($va_rule['trigger'], $pa_source_data);
					if (!ExpressionParser::hadError() && (bool)$vm_ret) {
						foreach($va_rule['actions'] as $va_action) {
							switch($vs_action_code = strtolower($va_action['action'])) {
								case 'set':
									switch($va_action['target']) {
										case 'name':
											$vs_name = BaseRefinery::parsePlaceholder($va_action['value'], $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' '));
											break;
										case 'type':
											$vs_type = BaseRefinery::parsePlaceholder($va_action['value'], $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' '));
											break;
										default:
											$va_attributes[$va_action['target']] = BaseRefinery::parsePlaceholder($va_action['value'], $pa_source_data, $pa_item, $ps_delimiter, $pn_c, array('returnAsString' => true, 'delimiter' => ' '));
											break;
									}
									break;
								case 'skip':
								default:
									if ($o_log) { 
										if ($vs_action_code != 'skip') {
											$o_log->logInfo(_t('[%3] Parent was skipped using rule "%1" with default action because an invalid action ("%2") was specified', $va_rule['trigger'], $vs_action_code, $ps_refinery_name));
										} else {
											$o_log->logDebug(_t('[%3] Parent was skipped using rule "%1" with action"%2"', $va_rule['trigger'], $vs_action_code, $ps_refinery_name));
										}
									}
									continue(4);
									break;
							}
						}
					}
				}
			}
			
			
			switch($ps_table) {
				case 'ca_collections':
					$vn_id = DataMigrationUtils::getCollectionID($vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
					break;
				default:
					if ($o_log) { $o_log->logDebug(_t('[importHelpers:caProcessRefineryParents] Invalid table %1', $ps_table)); }
					return null;
					break;	
			}
			if ($o_log) { $o_log->logDebug(_t('[%6] Got parent %1 (%2) with collection_id %3 and type %4 for %5', $vs_name, $vs_idno, $vn_id, $vs_type, $vs_name, $ps_refinery_name)); }
		}
		return $vn_id;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_attributes 
	 * @param array $pa_source_data
	 * @param array $pa_item
	 * @param string $ps_delimiter
	 * @param int $pn_c
	 * @param KLogger $o_log
	 * 
	 * @return array
	 */
	function caProcessRefineryAttributes($pa_attributes, $pa_source_data, $pa_item, $ps_delimiter, $pn_c, $o_log=null) {
		if (is_array($pa_attributes)) {
			$va_attr_vals = array();
			foreach($pa_attributes as $vs_element_code => $va_attrs) {
				if(is_array($va_attrs)) {
					foreach($va_attrs as $vs_k => $vs_v) {
						// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
						// DataMigrationUtils::getCollectionID(), which ca_data_importers::importDataFromSource() uses to create related collections
						// only supports non-repeating attribute values, so we join any values here and call it a day.
						$va_attr_vals[$vs_element_code][$vs_k] = (is_array($vm_v = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $ps_delimiter, $pn_c))) ? join(" ", $vm_v) : $vm_v;
					}
				} else {
					$va_attr_vals[$vs_element_code][$vs_element_code] = (is_array($vm_v = BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $ps_delimiter, $pn_c))) ? join(" ", $vm_v) : $vm_v;
				}
			}
			return $va_attr_vals;
		}
		return null;
	}
	# ---------------------------------------
?>