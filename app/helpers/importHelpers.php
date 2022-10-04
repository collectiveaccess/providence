<?php
/** ---------------------------------------------------------------------
 * app/helpers/importHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2022 Whirl-i-Gig
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

	require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');
	require_once(__CA_LIB_DIR__.'/Import/BaseDataReader.php');

	require_once(__CA_LIB_DIR__.'/Plugins/InformationService/TGN.php');
	require_once(__CA_LIB_DIR__.'/Plugins/InformationService/AAT.php');
	require_once(__CA_LIB_DIR__.'/Plugins/InformationService/ULAN.php');
	require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
	
	
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_refinery_name
	 * @param string $ps_table
	 * @param array $pa_parents 
	 * @param array $pa_source_data
	 * @param array $pa_item
	 * @param int $pn_c
	 * @param KLogger $o_log
	 * 
	 * @return int
	 */
	function caProcessRefineryParents($ps_refinery_name, $ps_table, $pa_parents, $pa_source_data, $pa_item, $pn_c, $pa_options=null) {
		global $g_ui_locale_id;
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$o_log = caGetOption('log', $pa_options, null);
		$o_reader = caGetOption('reader', $pa_options, null);
		$o_trans = caGetOption('transaction', $pa_options, null);
		$o_refinery_instance = caGetOption('refinery', $pa_options, null);
		$source_value = caGetOption('sourceValue', $pa_options, null);
		
		$va_delimiter = $pa_item['settings']["{$ps_refinery_name}_delimiter"];
		
		$apply_import_item_settings = $pa_item['settings']["{$ps_refinery_name}_applyImportItemSettings"];
		$hier_delimiter = $pa_item['settings']["{$ps_refinery_name}_hierarchicalDelimiter"];
		
		if($hier_delimiter && !is_array($hier_delimiter)) { $hier_delimiter = [$hier_delimiter]; }
		
		$vn_list_id = caGetOption('list_id', $pa_options, null);
		$vb_hierarchy_mode = caGetOption('hierarchyMode', $pa_options, false);
		
		if (!is_array($pa_parents)) { $pa_parents = array($pa_parents); }
		$vn_id = null;
		
		$id_numberer = IDNumbering::newIDNumberer($ps_table);
		
		$pa_parents = array_reverse($pa_parents);
		$c = -1;
		foreach($pa_parents as $vn_i => $va_parent) {
			$vs_name = BaseRefinery::parsePlaceholder($va_parent['name'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
			if (($vs_idno = BaseRefinery::parsePlaceholder($va_parent['idno'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings))) && $va_parent['idnoPrefix']) {
				$vs_idno = BaseRefinery::parsePlaceholder($va_parent['idnoPrefix'].$va_parent['idno'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
			}
			$vs_type = BaseRefinery::parsePlaceholder($va_parent['type'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));

			if (!$vs_name && !$vs_idno) { 
				continue; 
			}
			
			if(is_array($hier_delimiter) && sizeof($hier_delimiter)) {
				foreach($hier_delimiter as $index => $delim) {
					if (!trim($delim, "\t ")) { unset($hier_delimiter[$index]); continue; }
					$hier_delimiter[$index] = preg_quote($delim, "!");
				}
			}
						
			if(is_array($hier_delimiter)) {	
				$name_hier = array_map(function($v) { return trim($v); }, preg_split("!(".join("|", $hier_delimiter).")!", $vs_name));
				$idno_hier = array_map(function($v) { return trim($v); }, preg_split("!(".join("|", $hier_delimiter).")!", $vs_idno));							
			} else {
				$name_hier = [$vs_name];
				$idno_hier = [$vs_idno];
			}
			foreach($name_hier as $x => $vs_name) {
				$vs_idno = $idno_hier[$x] ?? $idno_hier[0];
				$c++;
				$pa_source_data["LEVEL_".($c + 1)] = '';
				if (!is_array($va_parent)) {
					$o_log->logWarn(_t('[%2] Parents options invalid. Did you forget to pass a list? Parents list passed was: %1', print_r($pa_parents, true), $ps_refinery_name));
					break;
				}
			
			
				if ((get_class($id_numberer) === 'MultipartIDNumber') && caGetOption('normalizeIdno', $va_parent, false)) {
					$id_numberer->setType($vs_type);
					$id_number_elements = method_exists($id_numberer, 'getElements') ? $id_numberer->getElements($vs_type) : null;
			
					// Make sure multilevel multipart number conforms to configured format
					// If we're missing a level we'll fill it with a "1"; if the last element in the format is SERIAL 
					// we'll add a placeholder. Note that we only handle SERIAL elements at the end of a number. If it's an oddball
					// format with a SERIAL followed by static numbers we don't touch it, on the assumption the user knows what they're doing.
					//
					// Eg. if the number is 2020.1.2 and the formats is YEAR.FREE.FREE.FREE.SERIAL we'll rewrite it as 2020.1.2.1.%
					if(is_array($id_number_elements) && (sizeof($id_number_elements) > 1)) {		// don't bother normalizing single-element formats
						$sep = $id_numberer->getSeparator();
						$n = explode($sep, $vs_idno);
				
						$last = array_pop($n);
						if ($last !== '%') { array_push($n, $last); }	// remove trailing SERIAL placeholder if present
				
						if (sizeof($n) < sizeof($id_number_elements)) {
							while(sizeof($n) < (sizeof($id_number_elements) - 1)) {
								$n[] = 1;	
							}
					
							$last = array_pop($id_number_elements);
							if ($last['type'] == 'SERIAL') {
								$n[] = '%';
							} else {
								$n[] = isset($last['default']) ? $last['default'] : '1';
							}
						}
						$vs_idno = join($sep, $n);
					
					}
				}
			
			
				if (!$vs_name && isset($va_parent['name'])) { continue; }
			
				$va_attributes_spec = (isset($va_parent['attributes']) && is_array($va_parent['attributes'])) ? $va_parent['attributes'] : [];
			
				// Process "attributes" block
				$va_attributes = array_merge(
					caProcessRefineryAttributes($va_attributes_spec, $pa_source_data, $pa_item, $pn_c, array('delimiter' => $va_delimiter, 'log' => $o_log, 'reader' => $o_reader, 'refineryName' => $ps_refinery_name)),
					['idno' => $vs_idno, 'parent_id' => $vn_id, '_treatNumericValueAsID' => true]
				);
			
				$pa_source_data['PARENT_IDNO'] = $vs_idno;
				$pa_source_data["LEVEL_".($c + 1)] = $vs_idno;
			
				if (isset($va_parent['rules']) && is_array($va_parent['rules'])) {
					foreach($va_parent['rules'] as $va_rule) {
						try {
							if ((bool)ExpressionParser::evaluate($va_rule['trigger'], $pa_source_data)) {
								foreach($va_rule['actions'] as $va_action) {
									if (!is_array($va_action) && (strtolower($va_action) == 'skip')) {
										$va_action = array('action' => 'skip');
									}
									switch($vs_action_code = strtolower($va_action['action'])) {
										case 'set':
											switch($va_action['target']) {
												case 'name':
													$vs_name = BaseRefinery::parsePlaceholder($va_action['value'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
													break;
												case 'type':
													$vs_type = BaseRefinery::parsePlaceholder($va_action['value'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
													break;
												default:
													$va_attributes[$va_action['target']] = BaseRefinery::parsePlaceholder($va_action['value'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
													break;
											}
											break;
										case 'skip':
										default:
											if ($o_log) {
												if ($vs_action_code != 'skip') {
													$o_log->logInfo(_t('[%3] Parent was skipped using rule "%1" with default action because an invalid action ("%2") was specified', $va_rule['trigger'], $vs_action_code, $ps_refinery_name));
												} else {
													$o_log->logDebug(_t('[%3] Parent was skipped using rule "%1" with action "%2"', $va_rule['trigger'], $vs_action_code, $ps_refinery_name));
												}
											}
											continue(4);
											break;
									}
								}
							}
						} catch (Exception $o_error) {
							$o_log->logError(_t('[%3] Error processing rule "%1" as an error occurred. Error number was "%2"', $va_rule['trigger'], $o_error->getMessage(), $ps_refinery_name));
						}
					}
				}
			
				if (!($va_match_on = caGetOption("{$ps_refinery_name}_matchOn", $pa_item['settings'], false))) {
					$va_match_on = caGetOption("{$ps_refinery_name}_dontMatchOnLabel", $pa_item['settings'], false) ? array('idno') : array('idno', 'label');
				}
				$vb_ignore_type = caGetOption("{$ps_refinery_name}_ignoreType", $pa_item['settings'], false);
				$vb_ignore_parent = caGetOption("{$ps_refinery_name}_ignoreParent", $pa_item['settings'], false);
				$pa_options = array_merge(array('matchOn' => $va_match_on, 'ignoreParent' => $vb_ignore_parent, 'ignoreType' => $vb_ignore_type), $pa_options);
			
				$vn_hierarchy_id = null;
				switch($ps_table) {
					case 'ca_objects':
						$vn_id = DataMigrationUtils::getObjectID($vs_name, $vn_id, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
						$va_attributes['preferred_labels']['name'] = $va_attributes['_preferred_labels'] = $vs_name;
						break;
					case 'ca_entities':
						// Try to match on display name alone, then parsed name
						$va_entity_label = DataMigrationUtils::splitEntityName($vs_name, array_merge($pa_options, ['doNotParse' => $pa_item['settings']["{$ps_refinery_name}_doNotParse"]]));
					
						foreach([['displayname' => $vs_name], $va_entity_label] as $e) {
							if($vn_id = DataMigrationUtils::getEntityID($e, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options)) {
								break;
							}
						}
						$va_attributes['preferred_labels'] = $va_entity_label;
						$va_attributes['_preferred_labels'] = $vs_name;
						break;
					case 'ca_places':
						if(!$vn_id) {	// get place hierarchy root
							require_once(__CA_MODELS_DIR__."/ca_places.php");
							$t_place = new ca_places();
							if ($o_trans) { $t_place->setTransaction($o_trans); }
							$vn_hierarchy_id = $pa_options['hierarchyID'];
							$vn_id = $pa_options['defaultParentID'];
							if(!$vn_id){
								$vn_id = $t_place->getHierarchyRootID($pa_options['hierarchyID']);
							}
							$va_attributes['parent_id'] = $vn_id;
						}
						$vn_id = DataMigrationUtils::getPlaceID($vs_name, $vn_id, $vs_type, $g_ui_locale_id, $vn_hierarchy_id, $va_attributes, $pa_options);
						$va_attributes['preferred_labels']['name'] = $va_attributes['_preferred_labels'] = $vs_name;
						break;
					case 'ca_occurrences':
						$vn_id = DataMigrationUtils::getOccurrenceID($vs_name, $vn_id, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
						$va_attributes['preferred_labels']['name'] = $va_attributes['_preferred_labels'] = $vs_name;
						break;
					case 'ca_collections':
						$vn_id = DataMigrationUtils::getCollectionID($vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
						$va_attributes['preferred_labels']['name'] = $va_attributes['_preferred_labels'] = $vs_name;
						break;
					case 'ca_loans':
						$vn_id = DataMigrationUtils::getLoanID($vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
						$va_attributes['preferred_labels']['name'] = $va_attributes['_preferred_labels'] = $vs_name;
						break;
					case 'ca_movements':
						$vn_id = DataMigrationUtils::getMovementID($vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
						$va_attributes['preferred_labels']['name'] = $va_attributes['_preferred_labels'] = $vs_name;
						break;
					case 'ca_list_items':
						if (!$vn_list_id) {
							if ($o_log) { $o_log->logDebug(_t('[importHelpers:caProcessRefineryParents] List was not specified')); }
							return null;
						}
						if(!$vn_id) {	// get place hierarchy root
							require_once(__CA_MODELS_DIR__."/ca_lists.php");
							$t_list = new ca_lists();
							if ($o_trans) { $t_list->setTransaction($o_trans); }
							$vn_id = $t_list->getRootItemIDForList($vn_list_id);
							$va_attributes['parent_id'] = $vn_id;
						}
					
						if (!$vs_idno) { $vs_idno = $vs_name; }
						if (!$vs_name) { $vs_name = $vs_idno; }
						if (!isset($va_attributes['is_enabled'])) { $va_attributes['is_enabled'] = 1; }
						$va_attributes['preferred_labels']['name_singular'] = $va_attributes['preferred_labels']['name_plural'] = $vs_name;
						$vn_id = DataMigrationUtils::getListItemID($vn_list_id, $vs_idno, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
						break;
					case 'ca_storage_locations':
						if(!$vn_id) {	// get storage location hierarchy root
							require_once(__CA_MODELS_DIR__."/ca_storage_locations.php");
							$t_loc = new ca_storage_locations();
							if ($o_trans) { $t_loc->setTransaction($o_trans); }
							$vn_id = $t_loc->getHierarchyRootID();
							$va_attributes['parent_id'] = $vn_id;
						}
						$vn_id = DataMigrationUtils::getStorageLocationID($vs_name, $vn_id, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
						$va_attributes['preferred_labels']['name'] = $va_attributes['_preferred_labels'] = $vs_name;
						break;
					default:
						if ($o_log) { $o_log->logDebug(_t('[importHelpers:caProcessRefineryParents] Invalid table %1', $ps_table)); }
						return null;
						break;	
				}
				$va_attributes['locale_id'] = $g_ui_locale_id;
				if ($o_log) { $o_log->logDebug(_t('[%6] Got parent %1 (%2) with id %3 and type %4 for %5', $vs_name, $vs_idno, $vn_id, $vs_type, $vs_name, $ps_refinery_name)); }
		
				// Set relationships on the related table
				$va_val = [];
				$va_attr_vals = [];
				$va_item = $pa_item;
			
				$vn_i_flip = sizeof($pa_parents) - $c - 1;		// parent list is reversed but parent settings are not... to calculate "inverse" index here
				$va_item['settings']["{$ps_refinery_name}_relationships"] = $pa_item['settings']["{$ps_refinery_name}_parents"][$vn_i_flip]['relationships'];
				unset($va_item['settings']["{$ps_refinery_name}_parents"]);
		
				caProcessRefineryRelatedMultiple($o_refinery_instance, $va_item, $pa_source_data, null, $o_log, $o_reader, $va_val, $va_attr_vals, $pa_options);
			
				$t_subject = Datamodel::getInstanceByTableName($ps_table, true);
				if ($t_subject->load($vn_id)) {
					$pa_source_data['PARENT_IDNO'] = $pa_source_data["LEVEL_".($c + 1)] =$t_subject->get('idno'); 
					if (is_array($va_val['_related_related'])) {
						foreach($va_val['_related_related'] as $vs_table => $va_rels) { 
							foreach($va_rels as $va_rel) {
								if (!$t_subject->addRelationship($vs_table, $va_rel['id'], $va_rel['_relationship_type'], null, null, null, null, ['setErrorOnDuplicate' => true])) {
									if ($o_log) { $o_log->logDebug(_t('[%6] Could not create relationship between parent %1 and %2 for ids %3 and %4 with type %5: %7', $ps_table, $vs_table, $vn_id, $va_rel['id'], $va_rel['_relationship_type'], $ps_refinery_name, join("; ", $t_subject->getErrors()))); }
								}
							}
						}
					}
				}
			}
		}
		
		if ($vb_hierarchy_mode) {
			return $va_attributes;
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
	 * @param int $pn_c
	 * @param KLogger $o_log
	 * 
	 * @return array
	 */
	function caProcessRefineryAttributes($pa_attributes, $pa_source_data, $pa_item, $pn_c, $pa_options=null) {
		$o_log = caGetOption('log', $pa_options, null);
		$o_reader = caGetOption('reader', $pa_options, null);
		$o_trans = caGetOption('transaction', $pa_options, null);
		$ps_refinery_name = caGetOption('refineryName', $pa_options, null);
		
		$apply_import_item_settings = $pa_item['settings']["{$ps_refinery_name}_applyImportItemSettings"];
		
		if (is_array($pa_attributes)) {
			$va_attr_vals = array();
			foreach($pa_attributes as $vs_element_code => $va_attrs) {
				$vs_prefix = '';
				$va_prefix_file_list = [];

				// Add details for file and media types.
				$va_prefix_file_list = [];
				$media_directories = caGetAvailableMediaUploadPaths();
				if (in_array(ca_metadata_elements::getElementDatatype($vs_element_code), [__CA_ATTRIBUTE_VALUE_FILE__, __CA_ATTRIBUTE_VALUE_MEDIA__]) && sizeof($media_directories) && isset($pa_item['settings']["{$ps_refinery_name}_mediaPrefix"]) && $pa_item['settings']["{$ps_refinery_name}_mediaPrefix"]) {
					foreach($media_directories as $d) {
						$vs_prefix = preg_replace("![/]+!", "/", "{$d}/".$pa_item['settings']["{$ps_refinery_name}_mediaPrefix"]."/");
						$va_prefix_file_list = array_merge($va_prefix_file_list, caGetDirectoryContentsAsList($vs_prefix, true));
					}
				}
			
				$vb_is_repeating = false;
				$vn_num_repeats = null;
				if(caIsIndexedArray($va_attrs)) {
					// multiple mappings
					$vn_offset = 0;
					foreach($va_attrs as $va_attrs_i) {
						if (!$va_attrs_i) { continue; }
                        if (!is_array($va_attrs_i)) { $va_attrs_i = [$va_attrs_i]; }
						foreach($va_attrs_i as $vs_k => $vs_v) {
							// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
						
							$va_vals = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $pn_c, array('delimiter' => caGetOption('delimiter', $pa_options, null), 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));

							if (sizeof($va_vals) > 1) { $vb_is_repeating = true; }
						
							if ($vb_is_repeating) {
								if (is_null($vn_num_repeats)) { $vn_num_repeats = sizeof($va_vals); }
							
								$vn_c = 0;
								foreach($va_vals as $vn_x => $va_v) {
									if (!$va_v || (!is_array($va_v) && !trim($va_v))) { continue; }
									if ($vs_prefix && is_array($va_v)) {
										$va_v = array_map(function($v) use ($vs_prefix) { return $vs_prefix.$v; });

										foreach($va_v as $vn_y => $vm_val_to_import) {
											$vm_val_to_import = preg_replace("![\\]+!", "/", $vm_val_to_import);
											foreach($media_directories as $d) {
												if ( ! file_exists( $vs_path = $d . $vm_val_to_import )
												     && ( $va_candidates = array_filter( $va_prefix_file_list,
														function ( $v ) use ( $vs_path ) {
															return preg_match( "!^{$vs_path}!", $v );
														} ) )
												     && is_array( $va_candidates )
												     && sizeof( $va_candidates )
												) {
													$va_v[ $vn_y ] = array_shift( $va_candidates );
													break;
												} elseif(file_exists( $vs_path = $d . $vm_val_to_import )) {
													$va_v[ $vn_y ] = $vs_path;
													break;
												}
											}
										}
									}
									$va_attr_vals[$vs_element_code][$vn_offset + $vn_x][$vs_k] = $va_v;
									
									if($source_value) {
										$va_attr_vals[$vs_element_code][$vn_offset + $vn_x]['_source'] = $source_value;
									}
									$vn_c++;
									if ($vn_c >= $vn_num_repeats) { break; }
								}
							} else {
								if ($vm_val_to_import = trim((is_array($vm_v = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $pn_c, array('delimiter' => caGetOption('delimiter', $pa_options, null), 'returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings)))) ? join(" ", $vm_v) : $vm_v)) {
									if(!file_exists($vs_path = $vs_prefix.$vm_val_to_import) && ($va_candidates = array_filter($va_prefix_file_list, function($v) use ($vs_path) { return preg_match("!^{$vs_path}!", $v); })) && is_array($va_candidates) && sizeof($va_candidates)){
										$vs_path = array_shift($va_candidates);
									}
									$va_attr_vals[$vs_element_code][$vn_offset] = $vs_path;
									$vn_c = 1;
								}
							}
						}
						$vn_offset += $vn_c;
					}
				}elseif(caIsAssociativeArray($va_attrs)) {
					// single mapping
					foreach($va_attrs as $vs_k => $vs_v) {
						// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
						
						$va_vals = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $pn_c, array('delimiter' => caGetOption('delimiter', $pa_options, null), 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));

						if (sizeof($va_vals) > 1) { $vb_is_repeating = true; }
						
						if ($vb_is_repeating) {
							if (is_null($vn_num_repeats)) { $vn_num_repeats = sizeof($va_vals); }
							
							$vn_c = 0;
							foreach($va_vals as $vn_x => $va_v) {
								if (!$va_v || (!is_array($va_v) && !trim($va_v))) { continue; }
								if ($vs_prefix && is_array($va_v)) {
									$va_v = array_map(function($v) use ($vs_prefix) { return $vs_prefix.$v; }, $va_v);
									
									foreach($va_v as $vn_y => $vm_val_to_import) {
										if(!file_exists($vs_path = $vs_prefix.$vm_val_to_import) && ($va_candidates = array_filter($va_prefix_file_list, function($v) use ($vs_path) { return preg_match("!^{$vs_path}!", $v); })) && is_array($va_candidates) && sizeof($va_candidates)){
											$va_v[$vn_y] = array_shift($va_candidates);
										} else {
											$va_v[$vn_y] = $vs_path;
										}
									}
								}
								$va_attr_vals[$vs_element_code][$vn_x][$vs_k] = $va_v;
								if($source_value) {
									$va_attr_vals[$vs_element_code][$vn_x]['_source'] = $source_value;
								}
								$vn_c++;
								if ($vn_c >= $vn_num_repeats) { break; }
							}
						} else {
							if ($vm_val_to_import = trim((is_array($vm_v = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $pn_c, array('delimiter' => caGetOption('delimiter', $pa_options, null), 'returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings)))) ? join(" ", $vm_v) : $vm_v)) {
								if(!file_exists($vs_path = $vs_prefix.$vm_val_to_import) && ($va_candidates = array_filter($va_prefix_file_list, function($v) use ($vs_path) { return preg_match("!^{$vs_path}!", $v); })) && is_array($va_candidates) && sizeof($va_candidates)){
									$vs_path = array_shift($va_candidates);
								}
								$va_attr_vals[$vs_element_code][$vs_k] = $vs_path;
							}
						}
					}
				} else {
					if ($vm_val_to_import = trim((is_array($vm_v = BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $pn_c, array('returnDelimitedValueAt' => $pn_c, 'returnAsString' => true, 'delimiter' => caGetOption('delimiter', $pa_options, null), 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings)))) ? join(" ", $vm_v) : $vm_v)) {
						if(!file_exists($vs_path = $vs_prefix.$vm_val_to_import) && ($va_candidates = array_filter($va_prefix_file_list, function($v) use ($vs_path) { return preg_match("!^{$vs_path}!", $v); })) && is_array($va_candidates) && sizeof($va_candidates)){
							$vs_path = array_shift($va_candidates);
						}
						
						if (in_array($vs_element_code, array_merge(caGetPreferredLabelNameKeyList(), caGetIdnoNameKeyList()))) {
							$va_attr_vals[$vs_element_code] = $vs_path;
						} else {
							$va_attr_vals[$vs_element_code][$vs_element_code] = $vs_path;
						}
					}
				}
			}
			return $va_attr_vals;
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_refinery_name 
	 * @param mixed $pm_import_tablename_or_num 
	 * @param mixed $pm_target_tablename_or_num 
	 * @param array $pa_source_data
	 * @param array $pa_item
	 * @param int $pn_c
	 * @param KLogger $o_log
	 * 
	 * @return array
	 */
	function caProcessInterstitialAttributes($ps_refinery_name, $pm_import_tablename_or_num, $pm_target_tablename_or_num, $pa_source_data, $pa_item, $pn_c, $pa_options=null) {
		$o_reader = caGetOption('reader', $pa_options, null);
		$o_log = caGetOption('log', $pa_options, null);
		$o_trans = caGetOption('transaction', $pa_options, null);
		$source_value = caGetOption('sourceValue', $pa_options, null);
		
		$apply_import_item_settings = $pa_item['settings']["{$ps_refinery_name}_applyImportItemSettings"];
		
		if (is_array($pa_item['settings']["{$ps_refinery_name}_interstitial"])) {
			if (!($ps_import_tablename = Datamodel::getTableName($pm_import_tablename_or_num))) { return null; }
			if (!($ps_target_tablename = Datamodel::getTableName($pm_target_tablename_or_num))) { return null; }
			if (!($t_target = Datamodel::getInstanceByTableName($ps_target_tablename, true))) { return null; }
			if ($o_trans) { $t_target->setTransaction($o_trans); }
			$va_attr_vals = array();
					
			// What is the relationship table?
			if ($ps_import_tablename && $ps_target_tablename) {
				
				$vs_linking_table = null;
				if ($ps_import_tablename != $ps_target_tablename) {
					$va_path = Datamodel::getPath($ps_import_tablename, $ps_target_tablename);
					$va_path_tables = array_keys($va_path);
					$vs_linking_table = $va_path_tables[1];
				} else {
					$vs_linking_table = $t_target->getSelfRelationTableName();
				}
				
				if ($vs_linking_table) {
					foreach($pa_item['settings']["{$ps_refinery_name}_interstitial"] as $vs_element_code => $va_attrs) {
						if(!is_array($va_attrs)) { 
							$va_attr_vals['_interstitial'][$vs_element_code] = BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $pn_c, array('returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));
							
							if($source_value) {
								$va_attr_vals['_interstitial'][$vs_element_code]['_source'] = $source_value; 
							}
						} else {
							foreach($va_attrs as $vs_k => $vs_v) {
								$va_attr_vals['_interstitial'][$vs_element_code][$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $pn_c, array('returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));
							}
							if($source_value) {
								$va_attr_vals['_interstitial'][$vs_element_code]['_source'] = $source_value;
							}
						}
					}
					if (is_array($va_attr_vals['_interstitial']) && sizeof($va_attr_vals['_interstitial'])) { 
						$va_attr_vals['_interstitial_table'] = $vs_linking_table;
					}
				}
			}
		}
		return $va_attr_vals;
	}
	# ---------------------------------------
/**
 * Process relationships on the refinery
 *
 * @param $ps_related_table
 * @param $pa_related_option_list
 * @param array $pa_source_data
 * @param array $pa_item
 * @param int $pn_c
 * @param null $pa_options
 *
 * @return array
 */
	function caProcessRefineryRelated($po_refinery_instance, $ps_related_table, $pa_related_option_list, $pa_source_data, $pa_item, $pn_c, $pa_options=null) {
		$o_reader = caGetOption('reader', $pa_options, null);
		$o_log = caGetOption('log', $pa_options, null);
		$o_trans = caGetOption('transaction', $pa_options, null);
		
		$t_rel_instance = Datamodel::getInstanceByTableName($ps_related_table, true);

		$refinery_name = $po_refinery_instance->getName();
		
		$apply_import_item_settings = $pa_item['settings']["{$ps_refinery_name}_applyImportItemSettings"];
		
		global $g_ui_locale_id;
		$va_attr_vals = array();
		
		if (!$pa_related_option_list || !is_array($pa_related_option_list)) {
			return $va_attr_vals;
		}
		
		foreach($pa_related_option_list as $vn_i => $pa_related_options) {
			$vn_id = null;
		
			$va_name = null;
			$vs_delimiter = caGetOption('delimiter', $pa_related_options, null);
			
			$vs_name_spec = caGetOption(caGetPreferredLabelNameKeyList(), $pa_related_options, caGetOption(caGetPreferredLabelNameKeyList(), $pa_related_options['attributes'], null));
			$vs_idno_spec = caGetOption(caGetIdnoNameKeyList(), $pa_related_options, caGetOption(caGetIdnoNameKeyList(), $pa_related_options['attributes'], null));
			$vs_type_spec = caGetOption(['type', 'type_id'], $pa_related_options, caGetOption(['type', 'type_id'], $pa_related_options['attributes'], null));
			$vs_parent_id_spec = caGetOption('parent_id', $pa_related_options, caGetOption('parent_id', $pa_related_options['attributes'], null));
	
			if (!is_array($va_name = BaseRefinery::parsePlaceholder($vs_name_spec, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => false, 'delimiter' => $vs_delimiter, 'applyImportItemSettings' => $apply_import_item_settings)))) { $va_name = [$va_name]; }
			if (!is_array($va_idno = BaseRefinery::parsePlaceholder($vs_idno_spec, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => false, 'delimiter' => $vs_delimiter, 'applyImportItemSettings' => $apply_import_item_settings)))) { $va_idno = [$va_idno]; }
			if (!is_array($va_type = BaseRefinery::parsePlaceholder($vs_type_spec, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => false, 'delimiter' => $vs_delimiter, 'applyImportItemSettings' => $apply_import_item_settings)))) { $va_type = [$va_type]; }
			if (!is_array($va_parent_id = BaseRefinery::parsePlaceholder($vs_parent_id_spec, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => false, 'delimiter' => $vs_delimiter, 'applyImportItemSettings' => $apply_import_item_settings)))) { $va_parent_id = [$va_parent_id]; }

			if ($vb_ignore_parent = caGetOption('ignoreParent', $pa_related_options, false)) {
				$pa_options['ignoreParent'] = $vb_ignore_parent;
			}

		    foreach($va_name as $i => $vs_name) {
                $vs_idno = $va_idno[$i];
               	$vs_type = $va_type[$i];
                $vs_rel_type = null;
                
                if ($vs_rel_type_opt = $pa_related_options['extractRelationshipTypes']) {
                	switch($vs_rel_type_opt) {
						default:
							$pat = "\\(([^\\)]+)\\)";
							break;
						case '[]':
						case '[':
							$pat = "\\[([^\\)]+)\\]";
							break;
						case '{}':
						case '{':
							$pat = "\\{([^\\)]+)\\}";
							break;
					}
					if (preg_match("!{$pat}!", $vs_name, $m)) { 
						$vs_rel_type = $m[1];
						$vs_name = str_replace($m[0], "", $vs_name);
					}
                }
                
                $vs_parent_id = $va_parent_id[$i];
                if (!$vs_name && (strpos($vs_idno, '%') === false)) { $vs_name = $vs_idno; }
                if (!$vs_name) { continue; }

                if(!$vs_type) { $vs_type = $va_type[sizeof($va_type) - 1]; }
                if(!$vs_parent_id) { $vs_parent_id = $va_parent_id[sizeof($va_parent_id) - 1]; }
                
                if ($ps_related_table == 'ca_entities') {
                    $t_entity = new ca_entities();
                    if ($o_trans) { $t_entity->setTransaction($o_trans); }
                    if (!$vs_name) {
                        $va_name = [];
                        foreach($t_entity->getLabelUIFields() as $vs_label_fld) {
                            if (!isset($pa_related_options[$vs_label_fld])) { $pa_related_options[$vs_label_fld] = ''; }
                            $va_name[$vs_label_fld] = BaseRefinery::parsePlaceholder($pa_related_options[$vs_label_fld], $pa_source_data, $pa_item, $pn_c, array('returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));
                        }
                    } else {
                        $va_name = DataMigrationUtils::splitEntityName($vs_name, array_merge($pa_options, ['type' => $vs_type, 'doNotParse' => $pa_item['settings']["{$refinery_name}_doNotParse"]]));
                    } 
            
                    if (!is_array($va_name) || !$va_name) { 
                        if ($o_log) { $o_log->logDebug(_t('[importHelpers:caProcessRefineryRelated] No name specified for table %1', $ps_related_table)); }
                        return null;
                    }
                } 
        
                if (!$vs_name) { 
                    if ($o_log) { $o_log->logDebug(_t('[importHelpers:caProcessRefineryRelated] No name specified for table %1', $ps_related_table)); }
                    return null;
                } 
        
                $va_attributes = (isset($pa_related_options['attributes']) && is_array($pa_related_options['attributes'])) ? $pa_related_options['attributes'] : array();
            
                foreach($va_attributes as $vs_element_code => $va_attrs) {
                    if(is_array($va_attrs)) {
                        foreach($va_attrs as $vs_k => $vs_v) {
                            // BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
                            // DataMigrationUtils::getCollectionID(), which ca_data_importers::importDataFromSource() uses to create related collections
                            // only supports non-repeating attribute values, so we join any values here and call it a day.
                            $va_attributes[$vs_element_code][$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
                        }
                    } else {
                        $va_attributes[$vs_element_code] = array($vs_element_code => BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings)));
                    }
                }
            
                if ($ps_related_table != 'ca_object_lots') {
                    $va_attributes['idno'] = $vs_idno;
                    $va_attributes['parent_id'] = $vs_parent_id;
                } else {
                    $vs_idno_stub = BaseRefinery::parsePlaceholder($pa_related_options['idno_stub'], $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));	
                }	
            
                // Set nonpreferred labels
                if (is_array($va_non_preferred_labels = $pa_related_options["nonPreferredLabels"])) {
                    $pa_options['nonPreferredLabels'] = array();
                    $vb_is_set = false;
                    foreach($va_non_preferred_labels as $va_label) {
                        foreach($va_label as $vs_k => $vs_v) {
                            if (!$vb_is_set && strlen(trim($vs_v))) { $vb_is_set = true; }
                            $va_label[$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $i, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
                        }
                        if ($vb_is_set) { $pa_options['nonPreferredLabels'][] = $va_label; }
                    }
                } elseif($vs_non_preferred_label = trim($pa_related_options["nonPreferredLabels"])) {
                    if ($ps_refinery_name == 'entitySplitter') {
                        if (is_array($va_npl = DataMigrationUtils::splitEntityName(BaseRefinery::parsePlaceholder($vs_non_preferred_label, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings)), array_merge(['type' => $vs_type], $pa_options)))) { 
                        	$pa_options['nonPreferredLabels'][] = $va_npl; 
                        }
                    } else {
                        if ($vs_npl = trim(BaseRefinery::parsePlaceholder($vs_non_preferred_label, $pa_source_data, $pa_item, $pn_c, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings)))) {
                            $pa_options['nonPreferredLabels'][] = [
                                $t_rel_instance->getLabelDisplayField() => $vs_npl
                            ];
                        }
                    }
                }
            
                $pa_options = array_merge(array('transaction' => $o_trans, 'matchOn' => array('idno', 'label')), $pa_options);

                switch($ps_related_table) {
                    case 'ca_objects':
                        $vn_id = DataMigrationUtils::getObjectID($vs_name, $vs_parent_id, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_object_lots':
                        $vn_id = DataMigrationUtils::getObjectLotID($vs_idno_stub, $vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_entities':
                        $vn_id = DataMigrationUtils::getEntityID($va_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_places':
                        $vn_id = DataMigrationUtils::getPlaceID($vs_name, $vs_parent_id, $vs_type, $g_ui_locale_id, $pa_options['hierarchyID'], $va_attributes, $pa_options);
                        break;
                    case 'ca_occurrences':
                        $vn_id = DataMigrationUtils::getOccurrenceID($vs_name, $vs_parent_id, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_collections':
                        $vn_id = DataMigrationUtils::getCollectionID($vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_loans':
                        $vn_id = DataMigrationUtils::getLoanID($vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_movements':
                        $vn_id = DataMigrationUtils::getMovementID($vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_list_items':
                        if (!($vn_list_id = caGetOption(['list_id', 'list'], $pa_options, null))) {
                            if ($o_log) { $o_log->logDebug(_t('[importHelpers:caProcessRefineryRelated] List was not specified')); }
                            return null;
                        }
					    if (!isset($va_attributes['is_enabled'])) { $va_attributes['is_enabled'] = 1; }
                        $vn_id = DataMigrationUtils::getListItemID($vn_list_id, $vs_name, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    case 'ca_storage_locations':
                        $vn_id = DataMigrationUtils::getStorageLocationID($vs_name, $vs_parent_id, $vs_type, $g_ui_locale_id, $va_attributes, $pa_options);
                        break;
                    default:
                        if ($o_log) { $o_log->logDebug(_t('[importHelpers:caProcessRefineryRelated] Invalid table %1', $ps_related_table)); }
                        return null;
                        break;	
                }
        
                if ($vn_id) {
                    $va_attr_vals['_related_related'][$ps_related_table][] = array(
                        'id' => $vn_id,
                        '_relationship_type' => $vs_rel_type ? $vs_rel_type : $pa_related_options['relationshipType']
                    );
                } 
            }
		}
		return $va_attr_vals;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_refinery_name 
	 * 
	 * @return array
	 */
	function caGenericImportSplitter($ps_refinery_name, $ps_item_prefix, $ps_table, $po_refinery_instance, &$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options) {
		global $g_ui_locale_id;
		$pa_source_data['PARENT_IDNO'] = '';
		
		$po_refinery_instance->setReturnsMultipleValues(true);
		
		$o_log = caGetOption('log', $pa_options, null);
		$o_reader = caGetOption('reader', $pa_options, null);
		$o_trans = caGetOption('transaction', $pa_options, null);
		$vn_hierarchy_id = null;
		
		$pn_value_index = caGetOption('valueIndex', $pa_options, 0);
		$source_value = caGetOption('sourceValue', $pa_options, null);
		$apply_import_item_settings = $pa_item['settings']["{$ps_refinery_name}_applyImportItemSettings"];
		
		if(($use_raw_values = (bool)$pa_item['settings']["{$ps_refinery_name}_useRawValues"]) && ($raw_data = caGetOption('raw', $pa_options, null))) {
			$pa_source_data = $raw_data;
		}
		
		// We can probably always use the item destination – using group destination is a vestige of older code and no longer is used
		// but we're leaving it in for now as a fallback it item dest is not set for some reason
		$va_group_dest = (isset($pa_item['destination']) && $pa_item['destination']) ? explode(".", $pa_item['destination']) : explode(".", $pa_group['destination']);
		
		$vs_terminal = array_pop($va_group_dest);
		$vs_dest_table = $va_group_dest[0];
		$va_group_dest[] = $vs_terminal;
		
		if (isset($pa_item['settings']['formatWithTemplate']) && strlen($pa_item['settings']['formatWithTemplate'])) {
			// Transform mapped value with template, if specified. This provides a way to rewrite values prior to use by the refinery.
			if($o_reader && is_array($tags = caGetTemplateTags($pa_item['settings']['formatWithTemplate']))) {
				foreach($tags as $t) {
					$pa_source_data[$t] = $o_reader->get($t);
				}
			}
			$pm_value = DisplayTemplateParser::processTemplate($pa_item['settings']['formatWithTemplate'], $pa_source_data);
		} else {
			$pm_value = (!isset($pa_source_data[$pa_item['source']]) && $o_reader) ? caProcessImportItemSettingsForValue($o_reader->get($pa_item['source'], array('returnAsArray'=> true)), $pa_item['settings']) : $pa_source_data[$pa_item['source']];
		}
		if (is_array($pm_value)) {
			if (isset($pm_value[$pn_value_index])) {
				$va_delimited_items = $pm_value[$pn_value_index];	// for input formats that support repeating values
			} else {
				$va_delimited_items = array_shift($pm_value);
			}
		} else {
			$va_delimited_items = [$pm_value];
		}
		
		if (!is_array($va_delimited_items)) { $va_delimited_items = [$va_delimited_items]; }
		$va_delimiter = $pa_item['settings']["{$ps_refinery_name}_delimiter"];
		if (!is_array($va_delimiter)) { $va_delimiter = [$va_delimiter]; }
		if (sizeof($va_delimiter)) {
			foreach($va_delimiter as $vn_index => $vs_delim) {
				if (!trim($vs_delim, "\t ")) { unset($va_delimiter[$vn_index]); continue; }
				$va_delimiter[$vn_index] = preg_quote($vs_delim, "!");
			}
		}
		
		$va_match_on = caGetOption('matchOn', $pa_options, null);
		if (!is_array($va_match_on) && $va_match_on) { 
			$va_match_on = array($va_match_on); 
		} elseif (is_array($va_match_on = $pa_item['settings']["{$ps_refinery_name}_matchOn"]) || is_array($va_match_on = $pa_item['settings']["matchOn"])) {
			$pa_options['matchOn'] = $va_match_on;
		}
		
		if (!isset($pa_options['matchOn'])) { $pa_options['matchOn'] = array('idno', 'label'); }
		
		if (isset($pa_item['settings']["{$ps_refinery_name}_ignoreParent"])) {
			$pa_options['ignoreParent'] = $pa_item['settings']["{$ps_refinery_name}_ignoreParent"];
		}
		if (isset($pa_item['settings']["{$ps_refinery_name}_ignoreType"])) {
			$pa_options['ignoreType'] = $pa_item['settings']["{$ps_refinery_name}_ignoreType"];
		}
		
		$text_transform = $pa_item['settings']["{$ps_refinery_name}_textTransform"] ?? null;
		
		$pa_options['dontCreate'] = $pb_dont_create = caGetOption('dontCreate', $pa_options, (bool)$pa_item['settings']["{$ps_refinery_name}_dontCreate"]);
		
		$va_vals = [];  // value list for all items
	
		if (!($t_instance = Datamodel::getInstanceByTableName($ps_table, true))) { return array(); }
		if ($o_trans) { $t_instance->setTransaction($o_trans); }
		
		$vs_label_fld = $t_instance->getLabelDisplayField();
		if (
			((sizeof($va_group_dest) == 1) && ($vs_terminal == $ps_table))
			||
			(($vs_terminal != $ps_table) && (sizeof($va_group_dest) > 1))
		) {		
			foreach($va_delimited_items as $vn_x => $vs_delimited_item) {
				$va_items = sizeof($va_delimiter) ? preg_split("!(".join("|", $va_delimiter).")!", $vs_delimited_item) : array($vs_delimited_item);

                $va_items = array_map("trim", $va_items);
                
                if($text_transform) {
                	$va_items = array_map(function ($v) use ($text_transform) { 
                		return caApplyTextTransforms($v, $text_transform);
                	}, $va_items);
                }
				foreach($va_items as $vn_i => $vs_item) {
					$va_parents = $pa_item['settings']["{$ps_refinery_name}_parents"];
					
					// Set label
					$va_val = [];       // values for current item
					
					$vs_laddered_type = null;
					if (!($vs_item = trim($vs_item))) { 
						if (is_array($va_parents) && (sizeof($va_parents) > 0)) {
							// try to ladder up the parents hierarchy since the base value is blank (see PROV-972)
							$vs_display_field = $t_instance->getLabelDisplayField();
							while(sizeof($va_parents) > 0) {
								$va_p = array_shift($va_parents);
								if (!isset($va_p[$vs_display_field])) { $vs_display_field = 'name'; }
								
								if ($vs_laddered_val = BaseRefinery::parsePlaceholder($va_p[$vs_display_field], $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'delimiter' => $va_delimiter, 'returnAsString' => true, 'returnDelimitedValueAt' => $vn_x, 'applyImportItemSettings' => $apply_import_item_settings))) {
									$vs_item = $vs_laddered_val;
									if ($o_log) { $o_log->logDebug(_t("[{$ps_refinery_name}] Used parent value %1 because the mapped value was blank", $vs_item)); }
									$va_val['_type'] = BaseRefinery::parsePlaceholder($va_p['type'], $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'delimiter' => $va_delimiter, 'returnAsString' => true, 'returnDelimitedValueAt' => $vn_x, 'applyImportItemSettings' => $apply_import_item_settings));
									if ($vs_idno_fld = $t_instance->getProperty('ID_NUMBERING_ID_FIELD')) {
									    $va_val[$vs_idno_fld] = BaseRefinery::parsePlaceholder($va_p[$vs_idno_fld], $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'delimiter' => $va_delimiter, 'returnAsString' => true, 'returnDelimitedValueAt' => $vn_x, 'applyImportItemSettings' => $apply_import_item_settings));
									}
									break;
								}
							}
						}
						if (!$vs_item) { 
							continue; 
						}
					}
					if (is_array($va_skip_values = $pa_item['settings']["{$ps_refinery_name}_skipIfValue"]) && in_array($vs_item, $va_skip_values)) {
						if ($o_log) { $o_log->logDebug(_t('[%1] Skipped %2 because it was in the skipIfValue list', $ps_refinery_name, $vs_item)); }
						continue;
					}
				
					// Set value as hierarchy
					if ($va_hierarchy_setting = $pa_item['settings']["{$ps_refinery_name}_hierarchy"]) {
						$va_val = array_merge($va_val, caProcessRefineryParents($ps_refinery_name, $ps_table, $va_hierarchy_setting, $pa_source_data, $pa_item, $pn_value_index, array_merge($pa_options, array('hierarchyMode' => true, 'refinery' => $po_refinery_instance))));
						$vs_item = $va_val['_preferred_label'];
					} else {
		
						// Set type
						if (
							(!isset($va_val['_type']) || !$va_val['_type'])
							&&
							($vs_type_opt = $pa_item['settings']["{$ps_refinery_name}_{$ps_item_prefix}Type"])
						) {
							$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));
						}
			
						if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']["{$ps_refinery_name}_{$ps_item_prefix}TypeDefault"])) {
							if (!($va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('returnAsString' => true, 'reader' => $o_reader, 'delimiter' => $va_delimiter, 'returnDelimitedValueAt' => $vn_x, 'applyImportItemSettings' => $apply_import_item_settings)))) {
								$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));
							}
						}
				
						// Set lot_status
						if (
							($vs_type_opt = $pa_item['settings']["{$ps_refinery_name}_{$ps_item_prefix}Status"])
						) {
							$va_val['_status'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));
						}
						if((!isset($va_val['_status']) || !$va_val['_status']) && ($vs_type_opt = $pa_item['settings']["{$ps_refinery_name}_{$ps_item_prefix}StatusDefault"])) {
							$va_val['_status'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('returnAsString' => true, 'reader' => $o_reader, 'applyImportItemSettings' => $apply_import_item_settings));
						}
			
						if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
							$o_log->logWarn(_t("[{$ps_refinery_name}] No %2 type is set for %2 \"%1\"", $vs_item, $ps_item_prefix));
						}
				
						//
						// Storage location/list item specific options
						//
						if (
							(($ps_refinery_name == 'storageLocationSplitter') && ($va_hier_delimiter = $pa_item['settings']['storageLocationSplitter_hierarchicalDelimiter']))
							||
							(($ps_refinery_name == 'listItemSplitter') && ($va_hier_delimiter = $pa_item['settings']['listItemSplitter_hierarchicalDelimiter']))
						) {
							$key_prefix = str_replace('Splitter', '', $ps_refinery_name);
							if (!is_array($va_hier_delimiter)) { $va_hier_delimiter = array($va_hier_delimiter); }
							
							if (sizeof($va_hier_delimiter)) {
								foreach($va_hier_delimiter as $vn_index => $vs_delim) {
									if (!trim($vs_delim, "\t ")) { unset($va_hier_delimiter[$vn_index]); continue; }
									$va_hier_delimiter[$vn_index] = preg_quote($vs_delim, "!");
								}
							}
							
							$va_item_hier = array_map(function($v) { return trim($v); }, preg_split("!(".join("|", $va_hier_delimiter).")!", $vs_item));
							
							if (sizeof($va_item_hier) >= 1) {
					
								$vn_hier_item_id = null;
				
								if (!is_array($va_types = $pa_item['settings']["{$ps_refinery_name}_hierarchical".ucfirst($key_prefix)."Types"])) {
									$va_types = array();
								}
						
								$vs_item = array_pop($va_item_hier);
								if (!($va_val['_type'] = array_pop($va_types))) {
									$va_val['_type'] = $pa_item['settings']["{$ps_refinery_name}_{$key_prefix}TypeDefault"];
								}
								
								
								// Set parents
								if ($va_parents) {
									$vn_hier_item_id = caProcessRefineryParents($ps_refinery_name, $ps_table, $va_parents, $pa_source_data, $pa_item, $pn_value_index, array_merge($pa_options, ['refinery' => $po_refinery_instance]));
								}
								foreach($va_item_hier as $vn_ix => $vs_parent) {
									if (sizeof($va_types) > 0)  { 
										$vs_type = array_shift($va_types); 
									} else { 
										if (!($vs_type = $pa_item['settings']["{$ps_refinery_name}_{$key_prefix}Type"])) {
											$vs_type = $pa_item['settings']["{$ps_refinery_name}_{$key_prefix}TypeDefault"];
										}
									}
									if (!$vs_type) { break; }
									
									switch($ps_refinery_name) {
										case 'storageLocationSplitter':
											$vn_hier_item_id = DataMigrationUtils::getStorageLocationID($vs_parent, $vn_hier_item_id, $vs_type, $g_ui_locale_id, ['idno' => $vs_parent], array_merge($pa_options, ['ignoreType' => true, 'ignoreParent' => is_null($vn_hier_item_id)]));
											break;
										case 'listItemSplitter':
										    $vals = ['idno' => $vs_parent];
										    if(!is_null($vn_hier_item_id)) { $vals['parent_id'] = $vn_hier_item_id; }
											$vn_hier_item_id = DataMigrationUtils::getListItemID($pa_item['settings']['listItemSplitter_list'], $vs_parent, $vs_type, $g_ui_locale_id, $vals, array_merge($pa_options, ['ignoreType' => true, 'ignoreParent' => is_null($vn_hier_item_id)]));
											break;
									}
								}
								$va_val['parent_id'] = $va_val['_parent_id'] = $vn_hier_item_id;
							}
						} else {
							// Set parents
							if ($va_parents) {
								$va_val['parent_id'] = $va_val['_parent_id'] = caProcessRefineryParents($ps_refinery_name, $ps_table, $va_parents, $pa_source_data, $pa_item, $pn_value_index, array_merge($pa_options, ['refinery' => $po_refinery_instance]));
							}
				
							if (isset($pa_options['defaultParentID']) && (!isset($va_val['parent_id']) || !$va_val['parent_id'])) {
								$va_val['parent_id'] = $va_val['_parent_id'] = $pa_options['defaultParentID'];
							}
							
							$pa_source_data['PARENT_IDNO'] = $va_val['parent_id'] ? $ps_table::getIdnoForId($va_val['parent_id'], ['transaction' => $o_trans]) : 'o_trans';
						}
				
						if(isset($pa_options['hierarchyID']) && $pa_options['hierarchyID'] && ($vs_hier_id_fld = $t_instance->getProperty('HIERARCHY_ID_FLD'))) {
							$vn_hierarchy_id = $va_val[$vs_hier_id_fld] = $pa_options['hierarchyID'];
						}
		
						// Set attributes
						//      $va_attr_vals = directly attached attributes for item
						if (is_array($va_attr_vals = caProcessRefineryAttributes($pa_item['settings']["{$ps_refinery_name}_attributes"], $pa_source_data, $pa_item, $pn_value_index, array('delimiter' => $va_delimiter, 'log' => $o_log, 'reader' => $o_reader, 'refineryName' => $ps_refinery_name)))) {
							$va_val = array_merge($va_val, $va_attr_vals);
							unset($va_val[$vs_label_fld]);
						}
						
						// Allow setting of preferred labels in attributes block
						if ($l = caGetOption(caGetPreferredLabelNameKeyList(), $va_val, null)) {
							if(is_array($l) && ($ps_table !== 'ca_entities')) {
								if (isset($l[$vs_label_fld]) && $l[$vs_label_fld]) {
									$va_val['preferred_labels'] = $l[$vs_label_fld];
								}
							} else {
								$va_val['preferred_labels'] = $l;
							}
						}
			
						// Set interstitials
						//      $va_interstitial_attr_vals = interstitial attributes for item
						if (isset($pa_options['mapping']) && is_array($va_interstitial_attr_vals = caProcessInterstitialAttributes($ps_refinery_name, $pa_options['mapping']->get('table_num'), $ps_table, $pa_source_data, $pa_item, $pn_value_index, array('log' => $o_log, 'reader' => $o_reader)))) {
							$va_val = array_merge($va_val, $va_interstitial_attr_vals);
						}

						// Set relationships on the related table
						caProcessRefineryRelatedMultiple($po_refinery_instance, $pa_item, $pa_source_data, $vn_i, $o_log, $o_reader, $va_val, $va_attr_vals, $pa_options);

						// Set nonpreferred labels
						if (is_array($va_non_preferred_labels = $pa_item['settings']["{$ps_refinery_name}_nonPreferredLabels"])) {
							$pa_options['nonPreferredLabels'] = array();
							foreach($va_non_preferred_labels as $va_label) {
								foreach($va_label as $vs_k => $vs_v) {
									$va_label[$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
								}
								$pa_options['nonPreferredLabels'][] = $va_label;
							}
						} elseif($vs_non_preferred_label = trim($pa_item['settings']["{$ps_refinery_name}_nonPreferredLabels"])) {
							if ($ps_refinery_name == 'entitySplitter') {
								$pa_options['nonPreferredLabels'][] = DataMigrationUtils::splitEntityName(BaseRefinery::parsePlaceholder($vs_non_preferred_label, $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings)), array_merge(['type' => $va_val['_type']], $pa_options));
							} else {
								$pa_options['nonPreferredLabels'][] = [
									$vs_label_fld => BaseRefinery::parsePlaceholder($vs_non_preferred_label, $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings))
								];
							}
						}
					}
					
					// Try to pull idno from reader if it's not explicitly set to give us something to match on
					if (($o_reader instanceof CollectiveAccessDataReader) && ($vs_table_idno_fld = Datamodel::getTableProperty($ps_table, 'ID_NUMBERING_ID_FIELD')) && in_array($vs_table_idno_fld, $pa_options['matchOn']) && !isset($va_val[$vs_table_idno_fld])) { 
						$va_idno_value_list = $o_reader->get("{$ps_table}.{$vs_table_idno_fld}", ['returnAsArray' => true]);
						if (isset($va_idno_value_list[$vn_i]) && strlen($va_idno_value_list[$vn_i])) { $va_val[$vs_table_idno_fld] = $va_idno_value_list[$vn_i]; }
					}
					
					if (
						(($vs_dest_table != $ps_table) && (sizeof($va_group_dest) > 1))
						||
						(($vs_dest_table == $ps_table) && (sizeof($va_group_dest) > 1) && $va_group_dest[1] == 'parent_id')
					) {	
				
						$vs_item = BaseRefinery::parsePlaceholder($vs_item, $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'returnAsString' => true, 'delimiter' => null, 'applyImportItemSettings' => $apply_import_item_settings));
						$va_attr_vals_with_parent = array_merge($va_val, array('parent_id' => $va_val['parent_id'] ? $va_val['parent_id'] : $va_val['_parent_id']));						
						
						switch($ps_table) {
							case 'ca_objects':
								$vn_item_id = DataMigrationUtils::getObjectID($va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['parent_id'], $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_object_lots':
								if (isset($va_val['_status'])) {
									$va_attr_vals['lot_status_id'] = $va_val['_status'];
								}
								unset($va_val['_status']);
								$vn_item_id = DataMigrationUtils::getObjectLotID($va_val['idno_stub'] ? $va_val['idno_stub'] : $vs_item, $va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['_type'], $g_ui_locale_id, $va_attr_vals, $pa_options);
								break;
							case 'ca_entities':
								$n = $va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item;
								$va_val['preferred_labels'] = ca_entity_labels::normalizeLabel(is_array($n) ? $n : DataMigrationUtils::splitEntityName($n, array_merge($pa_options, ['type' => $va_val['_type'], 'doNotParse' => $pa_item['settings']["{$ps_refinery_name}_doNotParse"]])));
								
								$vn_item_id = DataMigrationUtils::getEntityID($va_val['preferred_labels'], $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_places':
								$vn_item_id = DataMigrationUtils::getPlaceID($va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['parent_id'], $va_val['_type'], $g_ui_locale_id, $vn_hierarchy_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_occurrences':
								$vn_item_id = DataMigrationUtils::getOccurrenceID($va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['parent_id'], $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_collections':
								$vn_item_id = DataMigrationUtils::getCollectionID($va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_loans':
								$vn_item_id = DataMigrationUtils::getLoanID($va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_movements':
								$vn_item_id = DataMigrationUtils::getMovementID($va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_list_items':
								if (!$pa_options['list_id']) {
									if ($o_log) { $o_log->logDebug(_t('[importHelpers:caGenericImportSplitter] List was not specified')); }
									continue(2);
								}
								
					            if (!isset($va_attr_vals_with_parent['is_enabled'])) { $va_attr_vals_with_parent['is_enabled'] = 1; }
								if (is_array($vs_idno = caGetOption('idno', $va_attr_vals_with_parent, null))) { $vs_idno = caGetOption('idno', $vs_idno, null); }
								if (!$vs_idno) { $vs_idno = $vs_item; }
								
								$va_attr_vals_with_parent['preferred_labels']['name_singular'] = $va_attr_vals_with_parent['preferred_labels']['name_plural'] = $vs_item;
								
								$vn_item_id = DataMigrationUtils::getListItemID($pa_options['list_id'], $vs_idno, $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_storage_locations':
								$vn_item_id = DataMigrationUtils::getStorageLocationID($va_val['preferred_labels'] ? $va_val['preferred_labels'] : $vs_item, $va_val['parent_id'], $va_val['_type'], $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
								break;
							case 'ca_object_representations':
								if ($o_log) { $o_log->logDebug(_t('[importHelpers:caGenericImportSplitter] Only media paths can be mapped for object representations.')); }
								continue(2);
							default:
								if ($o_log) { $o_log->logDebug(_t('[importHelpers:caGenericImportSplitter] Invalid table %1', $ps_table)); }
								continue(2);
								break;	
						}
					
						if ($vn_item_id) {
							$va_val = [$vs_terminal => $vn_item_id, '_related_related' => $va_val['_related_related'], '_matchOn' => ['row_id']];
							if ($pb_dont_create) { $va_val['_dontCreate'] = 1; }
							$va_vals[] = $va_val;
							continue;
						} else {
							if ($o_log && !$pb_dont_create) { $o_log->logError(_t("[{$ps_refinery_name}Refinery] Could not add %2 %1", $vs_item, $ps_item_prefix)); }
						}
					} elseif ((sizeof($va_group_dest) == 1) && ($vs_terminal == $ps_table)) {
						// Set relationship type
						if (
							($vs_rel_type_opt = $pa_item['settings']["{$ps_refinery_name}_extractRelationshipType"])
						) {
							switch($vs_rel_type_opt) {
								default:
									$pat = "\\(([^\\)]+)\\)";
									break;
								case '[]':
								case '[':
									$pat = "\\[([^\\)]+)\\]";
									break;
								case '{}':
								case '{':
									$pat = "\\{([^\\)]+)\\}";
									break;
							}
							if (preg_match("!{$pat}!", $vs_item, $m)) { 
								$va_val['_relationship_type'] = $m[1];
								$vs_item = str_replace($m[0], "", $vs_item);
							}
						}
						
						if($vs_rel_type_delimiter_opt = caGetOption("{$ps_refinery_name}_relationshipTypeDelimiter", $pa_item['settings'], null)) {
							$va_val['_relationship_type_delimiter'] = $vs_rel_type_delimiter_opt;
						}
						
						if (
							(!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) 
							&&
							($vs_rel_type_opt = $pa_item['settings']["{$ps_refinery_name}_relationshipType"])
						) {
							$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'returnAsString' => true, 'applyImportItemSettings' => $apply_import_item_settings, 'delimiter' => $vs_rel_type_delimiter_opt ?? $va_delimiter, 'returnDelimitedValueAt' => $vn_i));
						}
			
						if (
							($vs_rel_type_opt = $pa_item['settings']["{$ps_refinery_name}_relationshipTypeDefault"])	
						) {
							if(!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) {
								if (!($va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'delimiter' => $va_delimiter, 'returnAsString' => true,  'returnDelimitedValueAt' => $vn_x, 'applyImportItemSettings' => $apply_import_item_settings)))) {
									$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $pn_value_index, array('reader' => $o_reader, 'returnAsString' => true, 'applyImportItemSettings' => $apply_import_item_settings));
								}
							}
							$va_val['_relationship_type_default'] = $vs_rel_type_opt;
						}

						if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log && ($ps_refinery_name !== 'objectRepresentationSplitter')) {
							$o_log->logWarn(_t("[{$ps_refinery_name}Refinery] No relationship type is set for %2 \"%1\"", $vs_item, $ps_item_prefix));
						}
	
						$label_is_not_set = (!isset($va_val['preferred_labels']) || (is_array($va_val['preferred_labels']) && !sizeof($va_val['preferred_labels'])) || (!is_array($va_val['preferred_labels']) && !strlen($va_val['preferred_labels'])));
						switch($ps_table) {
							case 'ca_entities':
								if(!isset($va_val['preferred_labels']) || !is_array($va_val['preferred_labels'])) { 
									$va_val['preferred_labels'] = DataMigrationUtils::splitEntityName($vs_item, array_merge($pa_options, ['type' => $va_val['_type'], 'doNotParse' => $pa_item['settings']["{$ps_refinery_name}_doNotParse"]])); 
								}
								$va_val['preferred_labels'] = ca_entity_labels::normalizeLabel($va_val['preferred_labels'], array_merge($pa_options, ['type' => $va_val['_type']]));
								if(!isset($va_val['idno'])) { $va_val['idno'] = $vs_item; }
								break;
							case 'ca_list_items':
								if($label_is_not_set) { 
									$va_val['preferred_labels'] = ['name_singular' => str_replace("_", " ", $vs_item), 'name_plural' => str_replace("_", " ", $vs_item)];
								}
								$va_val['_list'] = $pa_options['list_id'];
								if(!isset($va_val['idno'])) { $va_val['idno'] = $vs_item; }
								break;
							case 'ca_storage_locations':
							case 'ca_movements':
							case 'ca_loans':
							case 'ca_collections':
							case 'ca_occurrences':
							case 'ca_places':
							case 'ca_objects':
								if($label_is_not_set) { 
									$va_val['preferred_labels'] = ['name' => $vs_item];
								}
								if(!isset($va_val['idno'])) { $va_val['idno'] = $vs_item; }
								break;
							case 'ca_object_lots':
								if($label_is_not_set) { 
									$va_val['preferred_labels'] = ['name' => $vs_item];
								}
								if(!isset($va_val['idno_stub'])) { $va_val['idno_stub'] = $vs_item; }
								
								if (isset($va_val['_status'])) {
									$va_val['lot_status_id'] = $va_val['_status'];
								}
								unset($va_val['_status']);
								break;
							case 'ca_object_representations':
							    if (isset($va_val['name']) && is_array($va_val['name']) && isset($va_val['name']['name']) && $va_val['name']['name']) { 
							        $vs_name = $va_val['name']['name'];
							    } elseif((isset($va_val['name']) && $va_val['name'])) {
							        $vs_name = $va_val['name'];
							    } else {
							        $vs_name = pathinfo($vs_item, PATHINFO_FILENAME);
							    }
							    
							    $is_primary = caGetOption( 'objectRepresentationSplitter_isPrimary', $pa_item['settings'], false);
							    
								if($label_is_not_set) { 
									 $va_val['preferred_labels'] = $vs_name ? $vs_name : '['.caGetBlankLabelText('ca_object_representations').']';
								}
					
								if ($va_val['media']['media'] || $vs_item) {
									// Search for files in import directory (or subdirectory of import directory specified by mediaPrefix)
									$vs_media_dir_prefix = isset($pa_item['settings']['objectRepresentationSplitter_mediaPrefix']) ? '/'.$pa_item['settings']['objectRepresentationSplitter_mediaPrefix'] : '';

									foreach(caGetAvailableMediaUploadPaths() as $d) {
										$va_files = caBatchFindMatchingMedia( $d
										                                      . $vs_media_dir_prefix, pathinfo($vs_item, PATHINFO_BASENAME), [
											'matchMode' => caGetOption( 'objectRepresentationSplitter_matchMode',
												$pa_item['settings'], 'FILE_NAME' ),
											'matchType' => caGetOption( 'objectRepresentationSplitter_matchType',
												$pa_item['settings'], null ),
											'log'       => $o_log
										] );

										$files_added = 0;
										foreach ( $va_files as $vs_file ) {
											if ( preg_match( "!(SynoResource|SynoEA)!", $vs_file ) ) {
												continue;
											} // skip Synology res files

											$va_media_val = $va_val;
											if ( ! isset( $va_media_val['idno'] ) ) {
												$va_media_val['idno'] = pathinfo( $vs_file, PATHINFO_FILENAME );
											}
											$va_media_val['media']['media'] = $vs_file;
											if ( $pb_dont_create ) {
												$va_media_val['_dontCreate'] = 1;
											}
											if ( isset( $pa_options['nonPreferredLabels'] )
											     && is_array( $pa_options['nonPreferredLabels'] )
											) {
												$va_media_val['nonpreferred_labels']
													= $pa_options['nonPreferredLabels'];
											}
											
											if($is_primary) {
												$va_media_val['is_primary'] = true;
												$is_primary = false;
											}

											$va_media_val['_matchOn'] = $va_match_on;
											$va_vals[]                = $va_media_val;
							            	$files_added++;
										}
										if($files_added > 0) {	// if we found matching files we're done
											break(2);
										}
									}
							        $vn_c++;
							        continue(2);
								} else {
								    if (preg_match("!^http[s]{0,1}://!", strtolower($vs_item))) {
								        $va_val['media']['media'] = $vs_item;
								    } else {
									    foreach(caGetAvailableMediaUploadPaths() as $d) {
									    	if(!file_exists($d . '/' . $vs_item)) { continue; }
										    $va_val['media']['media'] = $d . '/' . $vs_item;
									    }
									}
								}
								
								// Default idno for rperesentation is the file name
								if(!isset($va_val['idno'])) { $va_val['idno'] = pathinfo($vs_item, PATHINFO_FILENAME); }
								break;
							default:
								if ($o_log) { $o_log->logDebug(_t('[importHelpers:caGenericImportSplitter] Invalid table %1', $ps_table)); }
								continue(2);
								break;	
						}
						
						if ($pb_dont_create) { $va_val['_dontCreate'] = 1; }
						if (isset($pa_options['nonPreferredLabels']) && is_array($pa_options['nonPreferredLabels'])) {
							$va_val['nonpreferred_labels'] = $pa_options['nonPreferredLabels'];
						}
					} elseif ((sizeof($va_group_dest) == 2) && ($vs_terminal == 'preferred_labels')) {
					
						switch($ps_table) {
							case 'ca_entities':
								$va_val = DataMigrationUtils::splitEntityName($vs_item, array_merge($pa_options, ['doNotParse' => $pa_item['settings']["{$ps_refinery_name}_doNotParse"]]));
								break;
							case 'ca_list_items':
								$va_val = array('name_singular' => $vs_item, 'name_plural' => $vs_item);
								break;
							case 'ca_storage_locations':
							case 'ca_movements':
							case 'ca_loans':
							case 'ca_collections':
							case 'ca_occurrences':
							case 'ca_places':
							case 'ca_objects':
							case 'ca_object_lots':
								$va_val = ['name' => $vs_item];
								break;
							case 'ca_object_representations':
								if ($o_log) { $o_log->logDebug(_t('[importHelpers:caGenericImportSplitter] Cannot map preferred labels to object representations. Only media paths can be mapped.')); }
								continue(2);
							default:
								if ($o_log) { $o_log->logDebug(_t('[importHelpers:caGenericImportSplitter] Invalid table %1', $ps_table)); }
								continue(2);
								break;	
						}
					} else {
						if ($o_log) { $o_log->logError(_t("[{$ps_refinery_name}Refinery] Could not add %2 %1: cannot map %3 using %1", $vs_item, $ps_item_prefix, join(".", $va_group_dest))); }
					}
					$va_val['_matchOn'] = $va_match_on;
					if ($pb_dont_create) { $va_val['_dontCreate'] = 1; }
					if (isset($pa_options['ignoreParent']) && $pa_options['ignoreParent']) { $va_val['_ignoreParent'] = 1; }
					$va_vals[] = $va_val;
				}
			}
		} else {
			if ($o_log) { $o_log->logError(_t("[{$ps_refinery_name}Refinery] Cannot map %1 using this refinery", $pa_group['destination'])); }
			return [];
		}
		
		// Remove values without content
		$va_vals = array_filter($va_vals, function($v) {
			$keys = array_keys($v);
			foreach($keys as $k) {
				if($k[0] !== '_') { return true; }	// directive keys start with "_"; if there are any keys starting something else, we have data
			}
			return false;
		});
		
		return $va_vals;
	}
# ---------------------------------------
/**
 * Uses caProcessRefineryRelated to set a list of relationships on related records. Also takes legacy relatedEntities into account
 * @param $po_refinery_instance BaseRefinery
 * @param $pa_item array
 * @param $pa_source_data array
 * @param $pn_value_index int
 * @param $o_log KLogger
 * @param $o_reader BaseDataReader
 * @param $va_val array
 * @param $va_attr_vals array
 */
function caProcessRefineryRelatedMultiple($po_refinery_instance, &$pa_item, $pa_source_data, $pn_value_index, $o_log, $o_reader, &$va_val, &$va_attr_vals, $pa_options=null) {
	$o_trans = caGetOption('transaction', $pa_options, null);

	$vs_relationship_settings_key = $po_refinery_instance->getName() . '_relationships';
	// Set relatedEntities to support legacy mappings
	if (is_array($va_related_entities_settings = $pa_item['settings'][$po_refinery_instance->getName() . '_relatedEntities'])) {
		$pa_item['settings'][] = is_array($pa_item['settings'][$vs_relationship_settings_key]) ? $pa_item['settings'][$vs_relationship_settings_key] : array();
		foreach ($va_related_entities_settings as $va_related_entity_setting) {
			$va_related_entity_setting['relatedTable'] = isset($va_related_entity_setting['relatedTable']) ? $va_related_entity_setting['relatedTable'] : 'ca_entities';
			$pa_item['settings'][$vs_relationship_settings_key][] = $va_related_entity_setting;
		}
	}
	// Set relationships
	if (is_array($va_relationships = $pa_item['settings'][$vs_relationship_settings_key])) {
		foreach ($va_relationships as $va_relationship_settings) {
			if ($vs_table_name = caGetOption('relatedTable', $va_relationship_settings)) {
				if($skip_if_all_empty = caGetOption('skipIfAllEmpty', $va_relationship_settings, null, [])) {
					if(!is_array($skip_if_all_empty)) { $skip_if_all_empty = [$skip_if_all_empty]; }
					$skip_if_all_empty = array_map(function($v) { return preg_replace('!^\^!', '', $v); }, $skip_if_all_empty);
					$is_empty = true;
					foreach($skip_if_all_empty as $source) {
						if($o_reader->get($source)) {
							$is_empty = false;
							break;
						}
					}
					if($is_empty) { continue; }
				}
				if($skip_if_any_empty = caGetOption('skipIfAnyEmpty', $va_relationship_settings, null)) {
					$skip_if_any_empty = array_map(function($v) { return preg_replace('!^\^!', '', $v); }, $skip_if_any_empty);
					$is_empty = false;
					foreach($skip_if_any_empty as $source) {
						if(!$o_reader->get($source)) {
							$is_empty = true;
							break;
						}
					}
					if($is_empty) { continue; }
				}
				if (is_array($va_rels = caProcessRefineryRelated($po_refinery_instance, $vs_table_name, array($va_relationship_settings), $pa_source_data, $pa_item, $pn_value_index, array_merge($pa_options, ['dontCreate' => caGetOption('dontCreate', $va_relationship_settings, false), 'list_id' => caGetOption('list', $va_relationship_settings, null)])))) {
					$va_rel_rels = $va_rels['_related_related'];
					unset($va_rels['_related_related']);
					
					$va_val = array_merge($va_val, $va_rels);
					
					if(is_array($va_rel_rels)) {
						if (!is_array($va_val['_related_related'])) { $va_val['_related_related'] = []; }
						foreach($va_rel_rels as $vs_rel_table => $va_rel_info) {
							if(!is_array($va_val['_related_related'][$vs_rel_table])) { $va_val['_related_related'][$vs_rel_table] = []; }
							$va_val['_related_related'][$vs_rel_table] = array_merge($va_val['_related_related'][$vs_rel_table], $va_rel_info);
						}
					}
				}
			}
		}
	}
}

# ---------------------------------------
	/**
	 * Apply item settings (applyRegularExpressions and replacement values to value; 
	 * used by refineries to apply regular expressions to values get()'ed from reader class
	 *
	 * @param mixed $pm_value
	 * @param array $pa_item_settings
	 *
	 * @return mixed
	 */
	function caProcessImportItemSettingsForValue($pm_value, $pa_item_settings) {
		if (isset($pa_item_settings['applyRegularExpressions']) && is_array($pa_item_settings['applyRegularExpressions'])) {
			if(is_array($pa_item_settings['applyRegularExpressions'])) {
				if (is_array($pm_value)) {
					foreach($pm_value as $vn_i => $vs_value) {
						foreach($pa_item_settings['applyRegularExpressions'] as $vn_c => $va_regex) {
                            if (!strlen($va_regex['match'])) {
                                continue;
                            }
							$vs_value = preg_replace(caMakeDelimitedRegexp($va_regex['match'],'!').((isset($va_regex['caseSensitive']) && (bool)$va_regex['caseSensitive']) ? '' : 'i'), $va_regex['replaceWith'], $vs_value);
						}
						$pm_value[$vn_i] = $vs_value;
					}
				} else {
					foreach($pa_item_settings['applyRegularExpressions'] as $vn_i => $va_regex) {
                        if (!strlen($va_regex['match'])) {
                            continue;
                        }
						$pm_value = preg_replace(caMakeDelimitedRegexp($va_regex['match'],'!').((isset($va_regex['caseSensitive']) && (bool)$va_regex['caseSensitive']) ? '' : 'i'), $va_regex['replaceWith'], $pm_value);
					}
				}
			}
		}
		
		if(is_array($pm_value)) {
			foreach($pm_value as $vn_i => $vs_value) {
				$pm_value[$vn_i] = ca_data_importers::replaceValue($vs_value, $pa_item_settings, []);
			}
		} else {
			$pm_value = ca_data_importers::replaceValue($pm_value, $pa_item_settings, []);
		}
		return $pm_value;
	}
	# ---------------------------------------
	/**
	 * Returns array of valid importer logging levels. Keys of array are display names for levels, values are KLogger integer log-level constants
	 *
	 * @return array
	 */
	function caGetLogLevels() {
		return array(
			_t('Errors') => KLogger::ERR,
			_t('Warnings') => KLogger::WARN,
			_t('Alerts') => KLogger::NOTICE,
			_t('Informational messages') => KLogger::INFO,
			_t('Debugging messages') => KLogger::DEBUG
		);
	}
	# ---------------------------------------
	/**
	 * Loads the given file into a \PhpOffice\PhpSpreadsheet\Spreadsheet object using common settings for preserving memory and performance
	 * @param string $ps_xlsx file name
	 * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	function caPhpExcelLoadFile($ps_xlsx){
		if(MemoryCache::contains($ps_xlsx, 'CAPHPExcel')) {
			return MemoryCache::fetch($ps_xlsx, 'CAPHPExcel');
		} else {
			if(!file_exists($ps_xlsx)) { return false; }

			// check mimetype
			if(function_exists('mime_content_type')) { // function is deprecated
				$vs_mimetype = mime_content_type($ps_xlsx);
				if(!in_array($vs_mimetype, array(
					'application/vnd.ms-office',
					'application/octet-stream',
					'application/vnd.oasis.opendocument.spreadsheet',
					'application/zip',
					'application/vnd.ms-excel',
					'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
				))){
					return false;
				}
			}

			/**  Identify the type  **/
			$vs_input_filetype = \PhpOffice\PhpSpreadsheet\IOFactory::identify($ps_xlsx);
			/**  Create a new Reader of that very type  **/
			$o_reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($vs_input_filetype);
			$o_reader->setReadDataOnly(true);
			$o_excel = $o_reader->load($ps_xlsx);

			MemoryCache::save($ps_xlsx, $o_excel, 'CAPHPExcel');
			return $o_excel;
		}
	}
	# ---------------------------------------------------------------------
	/**
	 * Counts non-empty rows in \PhpOffice\PhpSpreadsheet\Spreadsheet spreadsheet
	 *
	 * @param string $ps_xlsx absolute path to spreadsheet
	 * @param null|string $ps_sheet optional sheet name to use for counting
	 * @return int row count
	 */
	function caPhpExcelCountNonEmptyRows($ps_xlsx,$ps_sheet=null) {
		if(MemoryCache::contains($ps_xlsx, 'CAPHPExcelRowCounts')) {
			return MemoryCache::fetch($ps_xlsx, 'CAPHPExcelRowCounts');
		} else {
			$o_excel = caPhpExcelLoadFile($ps_xlsx);
			if($ps_sheet){
				$o_sheet = $o_excel->getSheetByName($ps_sheet);
			} else {
				$o_sheet = $o_excel->getActiveSheet();
			}

			$vn_highest_row = intval($o_sheet->getHighestRow());
			MemoryCache::save($ps_xlsx, $vn_highest_row, 'CAPHPExcelRowCounts');
			return $vn_highest_row;
		}
	}
	# ---------------------------------------------------------------------
	/**
	 * Get content from cell as trimmed string
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $po_sheet
	 * @param int $pn_row_num row number (zero indexed)
	 * @param string|int $pm_col either column number (zero indexed) or column letter ('A', 'BC')
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @return string the trimmed cell content
	 */
	function caPhpExcelGetCellContentAsString($po_sheet, $pn_row_num, $pm_col) {
		if(!is_numeric($pm_col)) {
			$pm_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($pm_col);
		}

		$vs_cache_key = spl_object_hash($po_sheet)."/{$pm_col}/{$pn_row_num}";

		if(MemoryCache::contains($vs_cache_key, 'PHPExcelCellContents')) {
			return MemoryCache::fetch($vs_cache_key, 'PHPExcelCellContents');
		} else {
			$vs_return = trim((string)$po_sheet->getCellByColumnAndRow($pm_col, $pn_row_num));
			MemoryCache::save($vs_cache_key, $vs_return, 'PHPExcelCellContents');
			return $vs_return;
		}
	}
	# ---------------------------------------------------------------------
	/**
	 * Get date from Excel sheet for given column and row. Convert Excel date to format acceptable by TimeExpressionParser if necessary.
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $po_sheet The work sheet
	 * @param int $pn_row_num row number (zero indexed)
	 * @param string|int $pm_col either column number (zero indexed) or column letter ('A', 'BC')
	 * @param int $pn_offset Offset to adf to the timestamp (can be used to fix timezone issues or simple to move dates around a little bit)
	 * @return string|null the date, if a value exists
	 */
	function caPhpExcelGetDateCellContent($po_sheet, $pn_row_num, $pm_col, $pn_offset=1) {
		if(!is_int($pn_offset)) { $pn_offset = 1; }

		if(!is_numeric($pm_col)) {
			$pm_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($pm_col);
		}

		$o_val = $po_sheet->getCellByColumnAndRow($pm_col, $pn_row_num);
		$vs_val = trim((string)$o_val);

		if(strlen($vs_val)>0) {
			$vn_timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(trim((string)$o_val->getValue())) + $pn_offset;
			if (!($vs_return = caGetLocalizedDate($vn_timestamp, array('dateFormat' => 'iso8601', 'timeOmit' => false)))) {
				$vs_return = $vs_val;
			}
		} else {
			$vs_return = null;
		}

		return $vs_return;
	}
	# ---------------------------------------------------------------------
	/**
	 * Get raw cell from Excel sheet for given column and row
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $po_sheet The work sheet
	 * @param int $pn_row_num row number (zero indexed)
	 * @param string|int $pm_col either column number (zero indexed) or column letter ('A', 'BC')
	 * @return \PhpOffice\PhpSpreadsheet\Cell\Cell|null the cell, if a value exists
	 */
	function caPhpExcelGetRawCell($po_sheet, $pn_row_num, $pm_col) {
		if(!is_numeric($pm_col)) {
			$pm_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($pm_col);
		}

		return $po_sheet->getCellByColumnAndRow($pm_col, $pn_row_num);
	}
	# ---------------------------------------------------------------------
    /**
     * Try to match given (partial) hierarchy path to a single subject in getty linked data AAT service
     *
     * @param array $pa_hierarchy_path
     * @param int   $pn_threshold
     * @param array $pa_options
     *        removeParensFromLabels = Remove parens from labels for search and string comparison. This can improve results in specific cases.
     * @param null  $o_service
     *
     * @return bool|string
     * @throws MemoryCacheInvalidParameterException
     */
	function caMatchAAT($pa_hierarchy_path, $pn_threshold=180, $pa_options = array(), $o_service=null) {
		$vs_cache_key = md5(print_r($pa_hierarchy_path, true));
		if(MemoryCache::contains($vs_cache_key, 'AATMatches')) {
			return MemoryCache::fetch($vs_cache_key, 'AATMatches');
		}

		if(!is_array($pa_hierarchy_path)) { return false; }

		$pb_remove_parens_from_labels = caGetOption('removeParensFromLabels', $pa_options, false);

		// search the bottom-most component (the actual term)
		$vs_bot = trim(array_pop($pa_hierarchy_path));

		if($pb_remove_parens_from_labels) {
			$vs_lookup = trim(preg_replace("/\([\p{L}\-\_\s]+\)/", '', $vs_bot));
		} else {
			$vs_lookup = $vs_bot;
		}

		if (is_null($o_service)) {
		    $o_service = new WLPlugInformationServiceAAT();
        }

		$va_hits = $o_service->lookup(array(), $vs_lookup, array('phrase' => true, 'raw' => true, 'limit' => 2000));
		if(!is_array($va_hits)) { return false; }

		$vn_best_distance = 0;
		$vn_pick = -1;
		foreach($va_hits as $vn_i => $va_hit) {
			if(stripos($va_hit['TermPrefLabel']['value'], $vs_lookup) !== false) { // only consider terms that match what we searched

				// calculate similarity as a number by comparing both the term and the parent string
				$vs_label_with_parens = $va_hit['TermPrefLabel']['value'];
				$vs_label_without_parens = trim(preg_replace("/\([\p{L}\s]+\)/", '', $vs_label_with_parens));
				$va_label_percentages = array();

				// we try every combination with and without parens on both sides
				// unfortunately this code gets rather ugly because getting the similarity
				// as percentage is only possible by passing a reference parameter :-(
				similar_text($vs_label_with_parens, $vs_bot, $vn_label_percent);
				$va_label_percentages[] = $vn_label_percent;
				similar_text($vs_label_with_parens, $vs_lookup, $vn_label_percent);
				$va_label_percentages[] = $vn_label_percent;
				similar_text($vs_label_without_parens, $vs_bot, $vn_label_percent);
				$va_label_percentages[] = $vn_label_percent;
				similar_text($vs_label_without_parens, $vs_lookup, $vn_label_percent);
				$va_label_percentages[] = $vn_label_percent;

				// similarity to parent path
				similar_text($va_hit['ParentsFull']['value'], join(' ', array_reverse($pa_hierarchy_path)), $vn_parent_percent);

				// it's a weighted sum because the term label is more important than the exact path
				$vn_tmp = 2*max($va_label_percentages) + $vn_parent_percent;
				//var_dump($va_hit); var_dump($vn_tmp);
				if($vn_tmp > $vn_best_distance) {
					$vn_best_distance = $vn_tmp;
					$vn_pick = $vn_i;
				}
			}
		}

		if($vn_pick >= 0 && ($vn_best_distance > $pn_threshold)) {
			$va_pick = $va_hits[$vn_pick];

			if($vs_value = trim($va_pick['ID']['value'])) {
				MemoryCache::save($vs_cache_key, $vs_value, 'AATMatches');
				return $vs_value;
			}
		}

		return false;
	}
	# ---------------------------------------------------------------------
	/**
	 * Return list of key values to try when looking for "preferred labels" option in splitter opts.
	 */
	function caGetPreferredLabelNameKeyList() { 
		return ['preferredLabels','preferred_labels', 'name'];
	}
	# ---------------------------------------------------------------------
	/**
	 * Return list of key values to try when looking for "identifier" option in splitter opts.
	 */
	function caGetIdnoNameKeyList() { 
		return ['idno','idno_stub'];
	}
	# ---------------------------------------------------------------------
	/**
	 *
	 */
	function caValidateGoogleSheetsUrl($url) {
		if (!is_array($parsed_url = parse_url(urldecode($url)))) { return null; }
 			
		$tmp = explode('/', $parsed_url['path']);
		array_pop($tmp); $tmp[] = 'export';
		$path = join("/", $tmp);
		$transformed_url = $parsed_url['scheme']."://".$parsed_url['host'].$path."?format=xlsx";
		if (!isUrl($transformed_url) || !preg_match('!^https://(docs|drive).google.com/(spreadsheets|file)/d/!', $transformed_url)) {
			return null;
		}
		return $transformed_url;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	function caApplyTextTransforms(?string $value, ?string $transform) : ?string{
		if (strlen($value)) {
			switch(strtolower($transform)) {
				case 'touppercase':
					$value = mb_strtoupper($value);
					break;
				case 'tolowercase':
					$value = mb_strtolower($value);
					break;
				case 'uppercasefirst':
					$value = caUcFirstUTF8Safe($value);
					break;
			}
		}
		return $value;
	}
	# ------------------------------------------------------
	/**
	 * Transform $value using specified transformation. Used by data importer applyTransformations option.
	 *
	 * Supported transformations:
	 *		filesize = Transform integer filesize in bytes to display text with scaled suffix (KiB, MiB, GiB, etc.)
	 *		           Options: decimal = number of decimal places to display. [Default is 2]
	 * 
	 * @param mixed $value Value to transform.
	 * @param string $transform Code for transform to apply.
	 * @param array $options Options for selected transform. [Default is null]
	 *
	 * @return mixed Transformed value
	 */
	function caApplyDataTransforms($value, string $transform, ?array $options){
		switch(strtolower($transform)) {
			case 'filesize':
				$value = caHumanFilesize(caParseHumanFilesize($value), caGetOption('decimals', $options, 2));
				break;
		}
		return $value;
	}
	# ---------------------------------------------------------------------
