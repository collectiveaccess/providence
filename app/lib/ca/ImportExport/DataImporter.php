<?php
/** ---------------------------------------------------------------------
 * DataImporter.php : manages import of data
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * @subpackage ImportExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	require_once(__CA_LIB_DIR__.'/ca/ImportExport/DataMover.php');
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
	require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
	
	class DataImporter extends DataMover {
		# -------------------------------------------------------
		private $opo_format; 	// format parser (subclass of DataMover)
		private $ops_locale;
		private $opn_locale_id;
		# -------------------------------------------------------
		/**
		 *
		 */
		public function __construct() {
			parent::__construct();	
		}
		# -------------------------------------------------------
		/**
		 * Imports data in the specified format from the specified location using the specified mapping
		 *
		 * @param $pm_mapping_name_or_id - the mapping_code (string) or mapping_id (integer) of the mapping to apply when importing
		 * @param $pm_data mixed - a file path, URL or file resource from which to read the data to import
		 * @param $pa_options array - an array of options to use during import. Supported options include:
		 *	debug - if true, debugging information is output during import; default is false
		 *	skipElements - array of element names in the imported data to skip when importing
		 */
		public function import($pm_mapping_name_or_id, $pm_data, $pa_options=null) {
			if (!$o_mapping = $this->opo_bundle_mapping->mappingIsAvailable($pm_mapping_name_or_id)) {
				// mapping doesn't exist
				return false;
			}
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			$ps_format = $o_mapping->get('target');
			
			if (!(isset($pa_options['locale']) && $pa_options['locale'])) {
				$o_config = Configuration::load();
				if (is_array($va_locale_defaults = $o_config->getList('locale_defaults')) && sizeof($va_locale_defaults)) {
					$pa_options['locale'] = $va_locale_defaults[0];
				}
			}
			$t_locale = new ca_locales();
			if (!($pa_options['locale_id'] = $t_locale->loadLocaleByCode((string)$pa_options['locale']))) { return null; }
			
			// get format processor
			require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/'.$ps_format.'/'.$ps_format.'.php');
			$vs_classname = 'DataMover'.$ps_format;
			if (!($this->opo_format = new $vs_classname)) { 
				// error: invalid format
				return null; 
			}
			
			$va_mapping_rels = $o_mapping->getRelationships();
			
			$va_mapping_rels_by_group = array();
			foreach($va_mapping_rels as $vn_i => $va_rel_info) {
				$va_mapping_rels_by_group[$va_rel_info['group_code']][] = $va_rel_info;
			}
			
			$o_dm = Datamodel::load();
			$t_instance = $o_dm->getInstanceByTableNum($o_mapping->get('table_num'), true);
			
			$va_settings = $o_mapping->get('settings');
			$t_list = new ca_lists();
			$pa_options['type_id'] = $t_list->getItemIDFromList($t_instance->getTypeListCode(), $va_settings['type']);
			$pa_options['settings'] = $va_settings;
			$pa_options['mapping_code'] = $o_mapping->get('mapping_code');

			return $this->opo_format->import($pm_data, $this, $va_mapping_rels_by_group, $t_instance, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Options:
		 *	forceUpdate - if true, then all records are imported, even if their timestamp is newer than the source data
		 *	debug - if true, then debugging information is printed
		 *  skipElements - array of element names in the imported data to skip when importing
		 */
		static function importRecord($pa_record, $po_data_import_event, $pa_mappings_by_group, &$po_instance, array $pa_options=null) {
			global $g_ui_locale_id, $g_ui_locale;
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_errors = array();
			
			$vb_debug = (isset($pa_options['debug']) && (bool)$pa_options['debug']) ? true : false;
			$va_skip_elements = (isset($pa_options['skipElements']) && is_array($pa_options['skipElements'])) ? $pa_options['skipElements'] : array();
			$vb_debug = true;
			$t_element = new ca_metadata_elements();
			$t_rel_types = new ca_relationship_types();
			$t_list = new ca_lists();
			
			$po_instance->clear(); 
			
			$o_dm = Datamodel::load();
			
			$v_ui_locale_id = $g_ui_locale_id;
			$v_ui_locale = $g_ui_locale;
			$g_ui_locale_id = isset($pa_options['locale_id']) ? $pa_options['locale_id'] : $g_ui_locale_id;
			$g_ui_locale = isset($pa_options['locale']) ? $pa_options['locale'] : $g_ui_locale;
			
			$po_instance->setMode(ACCESS_WRITE);
			
			// --------------------------------
			// Extract data from record
			// --------------------------------
			//
			// Look for existing records with the same identifier.  
			//
			//
			// 	First we look for a mapping for the identifier field of the table we're importing into. 
			//		Just about anything worth importing into will have such a field (eg. ca_objects, ca_entities and friends)
			$vb_update_existing_record = false;
			$vs_idno_value = null;
			$vs_idno_fld = $po_instance->getProperty('ID_NUMBERING_ID_FIELD');
			$vs_pref_label_fld = $po_instance->getLabelDisplayField();
			
			foreach($pa_mappings_by_group as $vs_group => $va_mappings) {
				foreach($va_mappings as $vn_i => $va_mapping) {
					$va_tmp = explode('.', $va_mapping['bundle_name'].'.'.$va_mapping['element_name']);
					$va_dest_elements = explode('/', $va_mapping['destination']);
					
					if (($va_tmp[0] == $po_instance->tableName()) && ($va_tmp[1] == $vs_idno_fld)) {
						
						$va_b = $pa_record;
						foreach($va_dest_elements as $vs_x) {
							if (!$vs_x) { continue; }
							if (!($va_b = $va_b[$vs_x])) { break; }
						}
						$vs_idno_value = (string)$va_b;
						continue;
					}
					
					if (($va_tmp[0] == $po_instance->tableName()) && ($va_tmp[1] == 'preferred_labels') && ($va_tmp[2] == $vs_pref_label_fld)) {
						
						$va_b = $pa_record;
						foreach($va_dest_elements as $vs_x) {
							if (!$vs_x) { continue; }
							if (!($va_b = $va_b[$vs_x])) { break; }
						}
						$vs_pref_label_value = (string)$va_b;
						continue;
					}
				}
			}
			
			//
			// See if the record with the identifier we're about to import already exists
			// 	We also support the notion of an alternate identifier here - a value than can be used to lookup but will
			//		not actually be imported. If this exists, it should be set in the _alt_identifier key of the imported record.
			//		alt identifiers allow you to pull records with outdated identifiers and replace with new ones in the import;
			//		you'll typically set this value in the OAIPreprocessRecord hook of an OAI plugin
			//
			
			if (!$vs_idno_value && !$vs_pref_label_value && (!isset($pa_record['_alt_identifer']) || !$pa_record['_alt_identifer'])) { return null; }		// skip blanks
			
			$va_idno_value_array = array($vs_idno_fld => $vs_idno_value);
			$va_label_value_array = array($vs_pref_label_fld => $vs_pref_label_value);
			$va_alt_value_array = array($vs_idno_fld => $pa_record['_alt_identifer']);
			
			$va_filters = array();
			if (($va_tmp[0] == 'ca_list_items') && $va_mapping['settings']['list']) {		// list to add newly imported terms to
				if ($t_list->load(array('list_code' => $va_mapping['settings']['list']))) {
					$va_filters['list_id'] = $va_idno_value_array['list_id'] = $va_alt_value_array['list_id'] = $t_list->getPrimaryKey();
				}
			}
			
			$vb_update_on_matched_labels = $va_mapping['settings']['updateOnMatchedLabels'];	// if true then we merge all items with the same label into a single record
			if (
				(($vs_idno_value) && ($po_instance->load($va_idno_value_array)))
				||
				(($vb_update_on_matched_labels) && ($vs_pref_label_value) && ($po_instance->loadByLabel($va_label_value_array, $va_filters)))
				||
				((isset($pa_record['_alt_identifer']) && $pa_record['_alt_identifer']) && ($po_instance->load($va_alt_value_array)))
			) {
				$vb_update_existing_record = true;
				if (!isset($pa_options['forceUpdate']) || !$pa_options['forceUpdate']) {
					if (isset($pa_record['_last_record_update']) && ($pa_record['_last_record_update'] > 0)) {
						// Has this record been updated since the last time we imported?
						$vn_record_last_update = $po_data_import_event->getLastUpdateTimestamp($po_instance->tableNum(), $po_instance->getPrimaryKey());
						
						if ($pa_record['_last_record_update'] <= $vn_record_last_update) {
							if ($vb_debug) { print "[IMPORT DEBUG]: Skipped '[{$vs_idno_value}]' because it's up to date\n"; }
							return true;
						}
					}
				}
			} else {
				$po_instance->set('locale_id', $g_ui_locale_id);
				$po_instance->set('type_id', $pa_options['type_id']);
				$po_instance->set('access', 0);
				$po_instance->set('status', 0);
			}
			
			
			$va_preferred_labels = array();
			$va_nonpreferred_labels = array();
			$va_attributes = $va_optional_attributes = array();
			$va_media = array();
			$va_related = array();
			
			$vs_primary_table = $po_instance->tableName();
			
			foreach($pa_mappings_by_group as $vs_group => $va_mappings) {		// run through groups
				$va_fixed_attribute_values = array();
				$vb_actual_value_was_set = false;			// will be true if actual data (not static: or date:) is set in mapping
				foreach($va_mappings as $vn_i => $va_mapping) {								// run though mappings in groups
					$vb_is_fixed_value = false;
					
					// Don't process mapped elements set to be skipped in this import
					if (in_array($va_mapping['destination'], $va_skip_elements)) { continue; }
					
					
					$va_settings = $va_mapping['settings'];
					$vn_locale_id = $g_ui_locale_id; // Maybe set from per-mapping setting at some point?
				
				
					// Set preferred list_id for imported list items
					$vn_list_id = null;
					if (($vs_primary_table == 'ca_list_items') && (!$vn_list_id)) {
						if ($va_settings['list']) {
							if ($t_list->load(array('list_code' => $va_settings['list']))) {
								$po_instance->set('list_id', $vn_list_id = $t_list->getPrimaryKey());
							}
						}
					}
					
					$va_bundle_path_elements = explode('.', $va_mapping['bundle_name'].'.'.$va_mapping['element_name']);
					$va_dest_path_elements = explode('/', $va_mapping['destination']);
					
					$va_dest_name_parts = explode(':', $va_dest_path_elements[sizeof($va_dest_path_elements) - 1]);
					$va_val = null;
					if(sizeof($va_dest_name_parts) > 1) {				// destination has modifier
						switch($vs_mod = array_shift($va_dest_name_parts)) {
							case 'static':
								$va_val = join(':', $va_dest_name_parts);
								$va_dest_path_elements = null;
								$va_fixed_attribute_values[$va_bundle_path_elements[1]][$va_bundle_path_elements[2]] = $va_val;
								$vb_is_fixed_value = true;
								break;
							case 'date':
								if (!($vs_pattern = join(':', $va_dest_name_parts))) {
									$vs_pattern = 'c';	// default to ISO 8601 - everyone loves that one right?
								}
								$va_val = date($vs_pattern, time());
								$va_dest_path_elements = null;
								$va_fixed_attribute_values[$va_bundle_path_elements[1]][$va_bundle_path_elements[2]] = $va_val;
								$vb_is_fixed_value = true;
								break;
						}
					} 
					
					if (!$va_val) {
						$va_tmp = $pa_record;
						$vb_is_set = false;
						while(sizeof($va_dest_path_elements)) {
							if (!($vs_dest_path_element = array_shift($va_dest_path_elements))) { continue; }
							if (!isset($va_tmp[$vs_dest_path_element])) {
								if (!sizeof(array_keys($va_tmp))) { 
									$vb_is_set = false; 
								} else {
									// Is key an int? If so then we've got a list of repeating values
									$vm_key = array_shift(array_keys($va_tmp));
									if (!is_int($vm_key)) { 
										$vb_is_set = false; 
									}
								}
								array_unshift($va_dest_path_elements, $vs_dest_path_element);
								break;
							}
							
							$va_tmp = $va_tmp[$vs_dest_path_element];
							$vb_is_set = true;
						}
						
						if ($vb_is_set) { $va_val = $va_tmp; }
					}
					
					// $va_val now contains either a single  value or an indexed array for repeating values. 
					// The values may be scalar or arrays keyed on sub-element names.
					
					// $va_dest_path_elements will contain all remaining path elements and can be used to navigate into $va_val
					// For simple elements where $va_val is just a scalar, $va_dest_path_elements will be empty; for elements with 
					// sub-fields this will provide the keys needed to get to the required value for import
						
					
					// Convert single scalar values to an index array with a single index
					if (!is_array($va_val)) { $va_val = array(0 => $va_val); }
					
					// Apply filters
					// TODO: Need to support filtering on XML attributes?
					$vb_filter_failed = false;
					if (isset($va_settings['filters']) && is_array($va_settings['filters'])) {
						foreach($va_settings['filters'] as $vs_element => $vs_filter_value) {
							$va_filter_elements = explode('/', $vs_element);
							
							foreach($va_val as $vn_filter_index => $va_indexed_val) {
								$va_tmp = $va_indexed_val;
								$va_record_value = null;
								foreach($va_filter_elements as $vs_filter_element) {
									if (!($va_tmp = $va_tmp[$vs_filter_element])) {
										break;
									}
									
									$va_record_value = $va_tmp;
								}
								if (!is_array($va_record_value)) {
									if ($va_record_value != $vs_filter_value) {
										unset($va_val[$vn_filter_index]);
									}
								} else {
									if(!in_array($vs_filter_value, $va_record_value)) { 
										unset($va_val[$vn_filter_index]);
									}
								}
							}
						}
					}

					// Loop through values
					$va_cur_dest_path_elements = $va_dest_path_elements;
					foreach($va_val as $vn_index => $vm_val) {
						if (!$vm_val) { continue; }
						$va_dest_path_elements = $va_cur_dest_path_elements;
						if($va_bundle_path_elements[0] == $po_instance->tableName()) {
							// primary table
							if(sizeof($va_dest_path_elements)) {
								$vs_val = $vm_val;
								foreach($va_dest_path_elements as $vs_element) {
									if (!$vs_element) {  continue; }
									$vs_val = $vs_val[$vs_element];
								}
							} else {
								if (is_array($vm_val)) {
									continue;
								} else {
									$vs_val = $vm_val;
								}
							}
							
							if (!$vs_val) { continue; } // skip blanks
							
							// Do spliting of values if configured to do so
							if ($vs_split = trim($va_settings['split'])) {
								$va_values = explode($vs_split, $vs_val);
								$vn_split_index = (int)$va_settings['part'];
								if ($vn_split_index < 0) { $vn_split_index = 0; }
								if  ($vn_split_index >= sizeof($va_values)) { continue; }	// skip out-of-range values
								$vs_val = trim($va_values[$vn_split_index]);
							}
							
							
							if ($po_instance->hasField($va_bundle_path_elements[1])) {
								// intrinsic field
								$po_instance->set($va_bundle_path_elements[1], $vs_val);
							} else {
								switch($va_bundle_path_elements[1]) {
									case 'preferred_labels':
										$va_preferred_labels[$vn_locale_id][$va_bundle_path_elements[2]] = $vs_val;
										if (!$vb_is_fixed_value) { $vb_actual_value_was_set = true; }
										break;
									case 'nonpreferred_labels':
										$va_nonpreferred_labels[$vn_locale_id][$vn_index][$va_bundle_path_elements[2]] = $vs_val;
										if (!$vb_is_fixed_value) { $vb_actual_value_was_set = true; }
										break;
									default:
										// attribute?
										
										if (!$va_bundle_path_elements[2]) { $va_bundle_path_elements[2] = $va_bundle_path_elements[1]; }
										
										if ($t_element->load(array('element_code' =>$va_bundle_path_elements[2]))) {
										 
											switch($t_element->get('datatype')) {
												case 3:	// list
													$vn_item_id = null;
													if (!($vn_item_id = $t_list->getItemIDFromList($t_element->get('list_id'), $vs_val))) {
														$vn_item_id = $t_list->getItemIDFromListByItemValue($t_element->get('list_id'), $vs_val);
													}
													if ($vn_item_id) { $vs_val = $vn_item_id; }
													if (!$vn_item_id) { break(2); }
													break;
											}
											
											if (trim($vs_val)) {
												if(isset($va_settings['priority']) && ($va_settings['priority'] == 'optional')) {
													$va_optional_attributes[$va_bundle_path_elements[1]][$vn_index][$va_bundle_path_elements[2]] = $vs_val;
												} else {
													$va_attributes[$va_bundle_path_elements[1]][$vn_index][$va_bundle_path_elements[2]] = $vs_val;
												}
												if (!$vb_is_fixed_value) { $vb_actual_value_was_set = true; }
											}
										}
										break;
								}
							}
						} else {
							// related table
							// are we doing representations?
							if (($va_bundle_path_elements[0] == 'ca_object_representations') && ($va_bundle_path_elements[1] == 'media') && ($po_instance->tableName() == 'ca_objects')) {
								
								if ($vn_type_id = $t_list->getItemIDFromList('object_representation_types', $va_settings['type'])) {
									$va_media[] = array(
										'url' => $vs_val,
										'type_id' => $vn_type_id,
										'status' => (int)$va_settings['status'] ? (int)$va_settings['status'] : 0,
										'access' => (int)$va_settings['access'] ? (int)$va_settings['access'] : 0
									);
								}
							
								continue;
							}
							
							if (!$vm_val) { continue; }
							if($t_rel = $o_dm->getInstanceByTableName($va_bundle_path_elements[0], true)) {
								
								$vp_ptr =& $va_related[$vs_group][$va_mapping['bundle_name']][$vn_index];
								
								$va_all_dest_path_elements = explode('/', $va_mapping['destination']);
								foreach($va_all_dest_path_elements as $vs_element) {
									if (!$vs_element) { continue; }
									$vp_ptr =& $vp_ptr[$vs_element];
									
								}
								
								$vb_is_set = true;
								foreach($va_dest_path_elements as $vs_element) {
									if (!$vs_element) { continue; }
									
									if (is_array($vm_val) && (isset($vm_val[$vs_element]))) {
										$vm_val = $vm_val[$vs_element];
									} else {
										$vb_is_set = false;
										break;
									}
								}
								
								if (!$vm_val) { continue; }
								
								if ($vb_is_set) {
									$vp_ptr = $vm_val;
									if ($va_mapping['element_name'] == '_relationship_type') {
										$va_related[$vs_group][$va_mapping['bundle_name']][$vn_index]['_relationship_type'] = $vm_val;
									}
									$vb_actual_value_was_set = true;
								}
							}
						}
					}
				}
				
				//
				// If this group only has values set by a fixed value (static: or date:) mapping then let's skip it
				//
				if (!$vb_actual_value_was_set) {
					unset($va_attributes[$va_bundle_path_elements[1]]);
					continue;
				}
				
				//
				// carry forward static and date values from first attribute to subsequent values (if they exists); since static: and date: 
				// values are only calculated for the first value if we don't copy them here they won't be set for the other values
				//
				if ((sizeof($va_attributes[$va_bundle_path_elements[1]]) > 1) && (is_array($va_fixed_attribute_values)) && (sizeof($va_fixed_attribute_values) > 0)) {
					foreach($va_attributes[$va_bundle_path_elements[1]] as $vn_attr_index => $va_attr_data) {
						if ($vn_attr_index == 0) { continue; }
						
						foreach($va_fixed_attribute_values[$va_bundle_path_elements[1]] as $vs_k => $vs_v) {
							if (!isset($va_attributes[$va_bundle_path_elements[1]][$vn_attr_index][$vs_k])) {
								$va_attributes[$va_bundle_path_elements[1]][$vn_attr_index][$vs_k] = $va_attributes[$va_bundle_path_elements[1]][0][$vs_k];
							}
						}
						
					}
				}
				
			}
		//print_r($va_attributes);
		//	print_R($va_related); 
			// --------------------------------
			// Write data to database
			// --------------------------------
			foreach($va_attributes as $vs_parent_element => $va_value_list) {
				
				if (($vb_update_existing_record)  && isset($pa_options['settings']['removeExistingAttributesOnUpdate']) && $pa_options['settings']['removeExistingAttributesOnUpdate']) {
					$po_instance->removeAttributes($vs_parent_element);
				}
				foreach($va_value_list as $vn_index => $vm_value_array) {
					
					$po_instance->replaceAttribute(
								array_merge(
										array('locale_id' => $vn_locale_id),
										$vm_value_array
									), $vs_parent_element
								);
				}
			}
			
			if ($vb_update_existing_record) {
				$po_instance->update();
			} else {
				$po_instance->insert();
			}
			if ($po_instance->numErrors()) {
				if ($vb_debug) { print "[IMPORT DEBUG]: Errors while ".($vb_update_existing_record ? 'updating' : 'inserting')." ".$po_instance->tableName()." [".$po_instance->get($vs_idno_fld)."]: ".join('; ', $po_instance->getErrors())."\n"; }
				$va_errors = $po_instance->errors;
				
				// TODO: log failure to insert or update
				
				$g_ui_locale_id = $v_ui_locale_id;
				$g_ui_locale = $v_ui_locale;
				return $va_errors;
			}
			
			// Optional attributes
			foreach($va_optional_attributes as $vs_parent_element => $va_value_list) {
				foreach($va_value_list as $vn_index => $vm_value_array) {
					
					$po_instance->addAttribute(
								array_merge(
										array('locale_id' => $vn_locale_id),
										$vm_value_array
									), $vs_parent_element
								);
					$po_instance->update();
					
					// TODO: log errors
					
					$po_instance->clearErrors();
				}
			}
			
			// Add relations
			if (($vb_update_existing_record)  && isset($pa_options['settings']['removeExistingRelationshipsOnUpdate']) && $pa_options['settings']['removeExistingRelationshipsOnUpdate']) {
				foreach($va_related as $vs_group => $va_rels_by_group) {
					foreach($va_rels_by_group as $vs_table => $va_rels) {
						$po_instance->removeRelationships($vs_table);
					}	
				}
			}
			foreach($va_related as $vs_group => $va_rels_by_group) {
				foreach($va_rels_by_group as $vs_table => $va_rels) {
					$va_rel_mappings_by_group = array(
						$vs_group => $pa_mappings_by_group[$vs_group]
					);
					
					$o_rel_instance = $o_dm->getInstanceByTableName($vs_table, true);
					
					// get type
					if (!($vn_type_id = $o_rel_instance->getTypeIDForCode($pa_mappings_by_group[$vs_group][0]['settings']['type']))) {
						$vn_type = null;
					}
					
					foreach($va_rels as $vn_index => $va_rel) {
			
						// import related record
						$va_rel_errors=	DataImporter::importRecord(
							$va_rel, $po_data_import_event, $va_rel_mappings_by_group, $o_rel_instance, array_merge($pa_options, array('type_id' => $vn_type_id))
						);
						
						if(sizeof($va_rel_errors)) {
							// TODO: log error creating related item
							continue;
						}
						
						// link related record to this one
						if ($vn_rel_id = $o_rel_instance->getPrimaryKey()) {
							if (!($vs_relationship_type = $pa_mappings_by_group[$vs_group][0]['settings']['relationship_type'])) {
								$vs_relationship_type = $va_rel['_relationship_type'];
							}
							
							if ($vn_rel_type_id = $t_rel_types->getRelationshipTypeID($t_rel_types->getRelationshipTypeTable($po_instance->tableName(), $o_rel_instance->tableName()), $vs_relationship_type)) {
								$po_instance->addRelationship($vs_table, $vn_rel_id, $vn_rel_type_id);
								
								// TODO: log failure to create relationship
							}
						} else {
							// TODO: log missing related item
						}
					}
				}
			}
			
			// Log insert
			$po_data_import_event->addItem((int)$po_instance->tableNum(), (int)$po_instance->getPrimaryKey(), (string)($vb_update_existing_record ? 'U' : 'I'));
			
			
			// Needs to have a label if one is not defined
			if (!sizeof($va_preferred_labels)) { 
				if (!$vb_update_existing_record) {
					$va_preferred_labels[1] = array('name' => '???'); 
				}
			} else {
				if ($vb_update_existing_record) {
					$po_instance->removeAllLabels();
				}
			}
			
			foreach($va_preferred_labels as $vn_locale_id => $va_val) {
				$po_instance->addLabel(
					$va_val, $vn_locale_id, null, true
				);
				
				if ($po_instance->numErrors()) {
					if ($vb_debug) { print "[IMPORT DEBUG]: Errors while adding preferred labels to [".$po_instance->get($vs_idno_fld)."]: ".join('; ', $po_instance->getErrors())."\n"; }
				
					$va_errors = $po_instance->errors;
					
					// TODO: log failure to create preferred labels
					
					$g_ui_locale_id = $v_ui_locale_id;
					$g_ui_locale = $v_ui_locale;
					return $va_errors;
				}
			}
			foreach($va_nonpreferred_labels as $vn_locale_id => $va_vals) {
				foreach($va_vals as $vn_index => $va_val) {
					$po_instance->addLabel(
						$va_val, $vn_locale_id, null, false
					);
					
					if ($po_instance->numErrors()) {
						if ($vb_debug) { print "[IMPORT DEBUG]: Errors while adding non-preferred labels to [".$po_instance->get($vs_idno_fld)."]: ".join('; ', $po_instance->getErrors())."\n"; }
					
						$va_errors = $po_instance->errors;
						
						// TODO: log failure to create non-preferred labels
						
						$g_ui_locale_id = $v_ui_locale_id;
						$g_ui_locale = $v_ui_locale;
						return $va_errors;
					}
				}
			}
			
			if(sizeof($va_media) > 0) {
				if ($vb_update_existing_record) {
					// remove existing media
					if (method_exists($po_instance, 'removeAllRepresentations')) {
						$po_instance->removeAllRepresentations();
					}
				}
				
				foreach($va_media as $vn_i => $va_media_info) {
					$po_instance->addRepresentation(
						$va_media_info['url'], $va_media_info['type_id'], $v_ui_locale_id, $va_media_info['status'], $va_media_info['access'], true
					);
					
					if ($po_instance->numErrors()) {
						if ($vb_debug) { print "[IMPORT DEBUG]: Errors while adding media to [".$po_instance->get($vs_idno_fld)."]: ".join('; ', $po_instance->getErrors())."\n"; }
				
						$va_errors = $po_instance->errors;
						
						// TODO: log failure to import media
						
						$g_ui_locale_id = $v_ui_locale_id;
						$g_ui_locale = $v_ui_locale;
						return $va_errors;
					}
				}
			}
			
			$g_ui_locale_id = $v_ui_locale_id;
			$g_ui_locale = $v_ui_locale;
			
			return $va_errors;
		}
		# -------------------------------------------------------
	}
?>