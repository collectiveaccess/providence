<?php
/* ----------------------------------------------------------------------
 * support/import/aat/import_tgn.php : Import Getty TGN XML-UTF8 files (2012 edition - should work for others as well)
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
 * ----------------------------------------------------------------------
 */
 	define("__CA_DONT_DO_SEARCH_INDEXING__", 1);
 	define("__CA_DONT_LOG_CHANGES__", 1);
 	
	require_once("../../../setup.php");
	
	if (!file_exists('./tgn_xml_12')) {
		die("ERROR: you must place the 'tgn_xml_12' data file directory in the same directory as this script.\n");
	}
	
	
	// ---------------------------------------------------------------------------
	// CHANGE THESE VALUES TO REFLECT YOUR CONFIGURATION
	// ---------------------------------------------------------------------------
	//
	// Code for metadata element to insert description for places into. 
	// Set to null to not import descriptions.
	$vs_description_element_code = 'generalNotes';
	
	// Code for metadata element to insert georeferences for places into. 
	// Set to null to not import georefs.
	$vs_georef_element_code = 'georeference';
	
	// Code for relationship type to relate place types to. 
	// Set to null to not import place types
	$vs_place_type_relationship_code = 'describes';
	 
	// ---------------------------------------------------------------------------

	require_once(__CA_LIB_DIR__.'/core/Db.php');
	require_once(__CA_LIB_DIR__.'/core/Utils/CLIProgressBar.php');
	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
	require_once(__CA_MODELS_DIR__.'/ca_places.php');
	require_once(__CA_MODELS_DIR__.'/ca_places_x_places.php');
	require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
	
	$_ = new Zend_Translate('gettext', __CA_APP_DIR__.'/locale/en_US/messages.mo', 'en_US');

	$t_locale = new ca_locales();
	$pn_en_locale_id = $t_locale->loadLocaleByCode('en_US');
	
	
	// create place hierarchy (if it doesn't exist already)
	$t_list = new ca_lists();
	if (!$t_list->load(array('list_code' => 'place_hierarchies'))) {
		$t_list->setMode(ACCESS_WRITE);
		$t_list->set('list_code', 'place_hierarchies');
		$t_list->set('is_system_list', 1);
		$t_list->set('is_hierarchical', 1);
		$t_list->set('use_as_vocabulary', 0);
		$t_list->insert();
		
		if ($t_list->numErrors()) {
			print "[Error] couldn't create ca_list row for place hierarchies: ".join('; ', $t_list->getErrors())."\n";
			die;
		}
		
		$t_list->addLabel(array('name' => 'Place hierarchies'), $pn_en_locale_id, null, true);
	}
	$vn_list_id = $t_list->getPrimaryKey();
	
	// create place hierarchy
	if (!($vn_tgn_id = caGetListItemID('place_hierarchies', 'tgn'))) {
		$t_tgn = $t_list->addItem('tgn', true, false, null, null, 'tgn');
		$t_tgn->addLabel(
			array('name_singular' => 'Thesaurus of Geographic Names', 'name_plural' => 'Thesaurus of Geographic Names'),
			$pn_en_locale_id, null, true
		);
		$vn_tgn_id = $t_tgn->getPrimaryKey();
	} else {
		$t_tgn = new ca_list_items($vn_tgn_id);
	}
	
	// Create list for place types (if it doesn't exist already)
	$t_place_types = new ca_lists();
	if (!$t_place_types->load(array('list_code' => 'tgn_place_types'))) {
		$t_place_types->setMode(ACCESS_WRITE);
		$t_place_types->set('list_code', 'tgn_place_types');
		$t_place_types->set('is_system_list', 1);
		$t_place_types->set('is_hierarchical', 1);
		$t_place_types->set('use_as_vocabulary', 1);
		$t_place_types->insert();
		
		if ($t_place_types->numErrors()) {
			print "[Error] couldn't create ca_list row for place types: ".join('; ', $t_place_types->getErrors())."\n";
			die;
		}
		
		$t_place_types->addLabel(array('name' => 'Getty TGN place types'), $pn_en_locale_id, null, true);
	}
	$vn_place_type_list_id = $t_place_types->getPrimaryKey();
	

	// load places
	$o_xml = new XMLReader();
	
	print "[Notice] READING TGN TERMS...\n";
	
	$vn_last_message_length = 0;

	$vn_term_count = 0;
	
	$t_place = new ca_places();
	$t_place->setMode(ACCESS_WRITE);
	$t_place->logChanges(false);		// Don't log changes to records during import – takes time and we don't need the logs
	
	
if (true) {	
	for($vn_file_index=1; $vn_file_index <= 15; $vn_file_index++) {
		$o_xml->open("tgn_xml_12/TGN{$vn_file_index}.xml");
	
		print "\n[Notice] READING TERMS FROM TGN{$vn_file_index}.xml...\n";
	
		while($o_xml->read()) {
			switch($o_xml->name) {
				# ---------------------------
				case 'Subject':
					if ($o_xml->nodeType == XMLReader::END_ELEMENT) {
						
						if ($va_subject['subject_id'] == '100000000') { break; }	// skip top-level root
					
						$vs_preferred_term = $va_subject['preferred_term'];
					
					
						switch($va_subject['record_type']) {
							default:
								$vn_type_id = null;
								$pb_is_enabled = true;
								break;
						}
					
						print str_repeat(chr(8), $vn_last_message_length);
						$vs_message = "[Notice] IMPORTING #".($vn_term_count+1)." [".$va_subject['subject_id']."] ".$vs_preferred_term;
						if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
						$vs_message .= str_repeat(' ', $vn_l);
						$vn_last_message_length = strlen($vs_message);
						print $vs_message;
					
					
						$t_place->clear();
						$t_place->set('parent_id', null);
						$t_place->set('type_id', $vn_type_id);
						$t_place->set('idno', $va_subject['subject_id']);
						$t_place->set('hierarchy_id', $vn_tgn_id);
												
						// Add description
						if ($vs_description_element_code && $va_subject['description']) {
							$t_place->addAttribute(
								array($vs_description_element_code => $va_subject['description'], 'locale_id' => $pn_en_locale_id),
								$vs_description_element_code
							);
						}
						
							// Add georeference
						if ($vs_georef_element_code && ($va_coords['latitude']['decimal'] && $va_coords['longitude']['decimal'])) {
							
							$t_place->addAttribute(
								array($vs_georef_element_code => "[".$va_coords['latitude']['decimal'].$va_coords['latitude']['direction'].",".$va_coords['longitude']['decimal'].$va_coords['longitude']['direction']."]", 'locale_id' => $pn_en_locale_id),
								$vs_georef_element_code
							);
							DataMigrationUtils::postError($t_place, "[Error] While adding georeference to place");
						}
						
						
						if ($vn_place_id = $t_place->insert(array('dontSetHierarchicalIndexing' => true))) {
						
							if (!($t_place->addLabel(
								array('name' => $vs_preferred_term, 'description' => ''),
								$pn_en_locale_id, null, true
							))) {
								print "[Error] Could not add preferred label to TGN term [".$va_subject['subject_id']."] ".$vs_preferred_term.": ".join("; ", $t_place->getErrors())."\n";
							}
							
							// add alternate labels
							
							if(is_array($va_subject['non_preferred_terms'])) {
								for($vn_i=0; $vn_i < sizeof($va_subject['non_preferred_terms']); $vn_i++) {
									$vs_np_label = $va_subject['non_preferred_terms'][$vn_i];
									$vs_np_term_type = $va_subject['non_preferred_term_types'][$vn_i];
								
									switch($vs_np_term_type) {
										default:
											$vn_np_term_type_id = null;
											break;
									}
								
									if (!($t_place->addLabel(
										array('name' => $vs_np_label, 'description' => ''),
										$pn_en_locale_id, $vn_np_term_type_id, false
									))) {
										print "[Error] Could not add non-preferred label to TGN term [".$va_subject['subject_id']."] ".$vs_np_label.": ".join("; ", $t_place->getErrors())."\n";
									}
								}
							}
							
							// Add place types
							if ($vs_place_type_relationship_code && $va_subject['place_type_id']) {
								$va_tmp = explode('/', $va_subject['place_type_id']);
								if ($vn_item_id = DataMigrationUtils::getListItemID('tgn_place_types', $va_tmp[0], null, $pn_en_locale_id, array('name_singular' => $va_tmp[1], 'name_plural' => $va_tmp[1]), array())) {
									
									$t_place->addRelationship('ca_list_items', $vn_item_id, $vs_place_type_relationship_code, null, null, null, null, array('allowDuplicates' => true));
									
									DataMigrationUtils::postError($t_place, "[Error] While adding place type to place");
								}
							}
						
							$vn_term_count++;
						} else {
							print "[Error] Could not import TGN term [".$va_subject['subject_id']."] ".$vs_preferred_term.": ".join("; ", $t_list->getErrors())."\n";
						}
					} else {
						$va_subject = array('subject_id' => $o_xml->getAttribute('Subject_ID'));
						$va_coords = array();
					}
					break;
				# ---------------------------
				case 'Descriptive_Note':
					if ($o_xml->nodeType == XMLReader::ELEMENT) {
						while($o_xml->read()) {
							switch($o_xml->name) {
								case 'Note_Text':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['description'] = $o_xml->value;
											break;
									}
									break;
								case 'Descriptive_Note':
									break(2);
							}
						}
					}
					break;
				# ---------------------------
				case 'Preferred_Place_Type':
					if ($o_xml->nodeType == XMLReader::ELEMENT) {
						while($o_xml->read()) {
							switch($o_xml->name) {
								case 'Place_Type_ID':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['place_type_id'] = $o_xml->value;
											break(3);
									}
									break;
								case 'Preferred_Place_Type':
								
									break(2);
							}
						}
					}
					break;
				# ---------------------------
				case 'Record_Type':
					switch($o_xml->nodeType) {
						case XMLReader::ELEMENT:
							$o_xml->read();
							$va_subject['record_type'] = $o_xml->value;
							break;
					}
					break;
				# ---------------------------
				case 'Parent_Relationships':
					if ($o_xml->nodeType == XMLReader::ELEMENT) {
						$vn_parent_id = $vs_historic_flag = null;
						while($o_xml->read()) {
							switch($o_xml->name) {
								case 'Parent_Subject_ID':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$vn_parent_id = $o_xml->value;
											break;
									}
									break;
								case 'Historic_Flag':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$vs_historic_flag = $o_xml->value;
											break;
									}
									break;
								case 'Preferred_Parent':
									$va_subject['preferred_parent_subject_id'] = $vn_parent_id;
									break;
								case 'Parent_Relationships':
									break(2);
							}
						}
					}
					break;
				# ---------------------------
				case 'Preferred_Term':
					if ($o_xml->nodeType == XMLReader::ELEMENT) {
						while($o_xml->read()) {
							switch($o_xml->name) {
								case 'Term_Type':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['preferred_term_type'] = $o_xml->value;
											break;
									}
									break;
								case 'Term_Text':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['preferred_term'] = $o_xml->value;
											break;
									}
									break;
								case 'Term_ID':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['preferred_term_id'] = $o_xml->value;
											break;
									}
									break;
								case 'Preferred_Term':
									break(2);
							}
						}
					}
					break;
				# ---------------------------
				case 'Non-Preferred_Term':
					if ($o_xml->nodeType == XMLReader::ELEMENT) {
						while($o_xml->read()) {
							switch($o_xml->name) {
								case 'Term_Type':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['non_preferred_term_types'][] = $o_xml->value;
											break;
									}
									break;
								case 'Term_Text':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['non_preferred_terms'][] = $o_xml->value;
											break;
									}
									break;
								case 'Term_ID':
									switch($o_xml->nodeType) {
										case XMLReader::ELEMENT:
											$o_xml->read();
											$va_subject['non_preferred_term_ids'][] = $o_xml->value;
											break;
									}
									break;
								case 'Non-Preferred_Term':
									break(2);
							}
						}
					}
					break;
				# ---------------------------
				case 'VP_Subject_ID':
					switch($o_xml->nodeType) {
						case XMLReader::ELEMENT:
							$o_xml->read();
							$va_subject['related_subjects'][] = $o_xml->value;
							break;
					}
					break;
				# ---------------------------
				case 'Coordinates':
					if ($o_xml->nodeType == XMLReader::ELEMENT) {
						$va_coords = array();
						while($o_xml->read()) {
							switch($o_xml->name) {
								case 'Latitude':
									if ($o_xml->nodeType == XMLReader::ELEMENT) {
										$va_coords['latitude'] = array();
										while($o_xml->read()) {
											switch($o_xml->name) {
												case 'Decimal':
													switch($o_xml->nodeType) {
														case XMLReader::ELEMENT:
															$o_xml->read();
															$va_coords['latitude']['decimal'] = abs($o_xml->value);
															break;
													}
													break;
												case 'Direction':
													switch($o_xml->nodeType) {
														case XMLReader::ELEMENT:
															$o_xml->read();
															$va_coords['latitude']['direction'] = substr($o_xml->value,0, 1);
															break;
													}
													break;
												case 'Latitude':
													break(2);
											}
										}
									}
									break;
								case 'Longitude':
									if ($o_xml->nodeType == XMLReader::ELEMENT) {
										$va_coords['longitude'] = array();
										while($o_xml->read()) {
											switch($o_xml->name) {
												case 'Decimal':
													switch($o_xml->nodeType) {
														case XMLReader::ELEMENT:
															$o_xml->read();
															$va_coords['longitude']['decimal'] = abs($o_xml->value);
															break;
													}
													break;
												case 'Direction':
													switch($o_xml->nodeType) {
														case XMLReader::ELEMENT:
															$o_xml->read();
															$va_coords['longitude']['direction'] = substr($o_xml->value,0,1);
															break;
													}
													break;
												case 'Longitude':
													break(2);
											}
										}
									}
									break ;
								case 'Coordinates':
									
									break(2);
							}
						}
					}
					break;
				# ---------------------------
			}
		}
	
		$o_xml->close();
	}
}

	$t_place = new ca_places();
	$t_parent = new ca_places();
	$t_place->setMode(ACCESS_WRITE);
	$vn_tgn_root_id = $t_parent->getHierarchyRootID($vn_tgn_id);
	
if (true) {	
	print "[Notice] LINKING TERMS IN HIERARCHY...\n";
	$vn_last_message_length = 0;

	

	$va_place_id_cache = array();
	
	for($vn_file_index=1; $vn_file_index <= 15; $vn_file_index++) {
		$o_xml->open("tgn_xml_12/TGN{$vn_file_index}.xml");
	
		print "[Notice] READING TERMS FROM TGN{$vn_file_index}.xml...\n";
	
		$va_subject = array();
		while($o_xml->read()) {
			switch($o_xml->name) {
				# ---------------------------
				case 'Subject':
					if ($o_xml->nodeType == XMLReader::END_ELEMENT) {
					
						$vs_child_id = $va_subject['subject_id'];
						$vs_parent_id = $va_subject['preferred_parent_subject_id'];
						if (!$vs_parent_id) { continue; }
						print str_repeat(chr(8), $vn_last_message_length);
						$vs_message = "[Error] LINKING {$vs_child_id} to parent {$vs_parent_id}";
						if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
						$vs_message .= str_repeat(' ', $vn_l);
						$vn_last_message_length = strlen($vs_message);
						print $vs_message;
						
						if(!$t_place->load(array('idno' => $vs_child_id))) {
							print "[Error] could not load item for {$vs_child_id} (was translated to item_id={$vn_child_item_id})\n";
							continue;
						}
						
						if ($vs_parent_id == '100000000') {
							//$t_parent->load($vn_tgn_root_id);	// get root_id
							$vn_parent_id = $vn_tgn_root_id;
						} else {
							if (!isset($va_place_id_cache[$vs_parent_id])) {
								if(!$t_parent->load(array('idno' => $vs_parent_id))) {
									print "[Error] No list item id for parent_id {$vs_parent_id} (were there previous errors?)\n";
									continue;
								}
								$vn_parent_id = $va_place_id_cache[$vs_parent_id] = $t_parent->getPrimaryKey();
							} else {
								$vn_parent_id = $va_place_id_cache[$vs_parent_id];
							}
						
							$t_place->set('parent_id', $vn_parent_id);
							$t_place->update(array('dontSetHierarchicalIndexing' => true, 'dontCheckCircularReferences' => true));
		
							if ($t_place->numErrors()) {
								print "[Error] could not set parent_id for {$vs_child_id} (was translated to item_id=".$t_place->getPrimaryKey()."): ".join('; ', $t_place->getErrors())."\n";
							}
						}
					} else {
						$va_subject = array('subject_id' => $o_xml->getAttribute('Subject_ID'));
					}
					break;
				# ---------------------------
				case 'Parent_Relationships':
					$vn_parent_id = $vs_historic_flag = null;
					while($o_xml->read()) {
						switch($o_xml->name) {
							case 'Preferred_Parent':
								while($o_xml->read()) {
									switch($o_xml->name) {
										case 'Parent_Subject_ID':
											switch($o_xml->nodeType) {
												case XMLReader::ELEMENT:
													$o_xml->read();
													$vn_parent_id = $o_xml->value;
													break;
											}
											break;
										case 'Historic_Flag':
											switch($o_xml->nodeType) {
												case XMLReader::ELEMENT:
													$o_xml->read();
													$vs_historic_flag = $o_xml->value;
													break;
											}
											break;
										case 'Preferred_Parent':
											$va_subject['preferred_parent_subject_id'] = $vn_parent_id;
											break(2);
									}
								}
								break;
							case 'Parent_Relationships':
								break(2);
						}
					}
					break;
					# ---------------------------
			}
		}
	}
}
	// TODO: Fix self-referencing root problem (TGN imports "top of hierarchy" record as having itself as a parent)

	print "[Notice] Rebuilding hier indices for hierarchy_id={$vn_tgn_id}...\n";
	$t_place->rebuildHierarchicalIndex($vn_tgn_id);


if (true) {	
	print "[Notice] ADDING RELATED PLACE LINKS...\n";
	$vn_last_message_length = 0;

	$t_place = new ca_places();
	$t_place->setMode(ACCESS_WRITE);
	
	$t_link = new ca_places_x_places();
	$t_link->setMode(ACCESS_WRITE);
	
	$t_rel_type = new ca_relationship_types();
	
	for($vn_file_index=1; $vn_file_index <= 15; $vn_file_index++) {
		$o_xml->open("tgn_xml_12/TGN{$vn_file_index}.xml");
	
		print "[Notice] READING TERMS FROM TGN{$vn_file_index}.xml...\n";
	
		$va_subject = array();
		while($o_xml->read()) {
			switch($o_xml->name) {
				# ---------------------------
				case 'Subject':
					if ($o_xml->nodeType == XMLReader::END_ELEMENT) {
						// noop
						$vs_child_id = $va_subject['subject_id'];
						$vs_parent_id = $va_subject['preferred_parent_subject_id'];
						if (!$vs_parent_id) { continue; }
						print str_repeat(chr(8), $vn_last_message_length);
						$vs_message = "[Error] LINKING {$vs_child_id} to parent {$vs_parent_id}";
						if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
						$vs_message .= str_repeat(' ', $vn_l);
						$vn_last_message_length = strlen($vs_message);
						print $vs_message;
						
						if(!$t_place->load(array('idno' => $va_subject['subject_id']))) {
							print "[Error] could not load place for ".$va_subject['subject_id']."\n";
							continue;
						}
						if(!$t_related_place->load(array('idno' => $$va_subject['related_subject_id']))) {
							print "[Error] could not load related place ".$va_subject['related_subject_id']."\n";
							continue;
						}
						
						$va_tmp = explode("/", $va_subject['relationship_type']);
						$vn_rel_type_id = $t_rel_type->getRelationshipTypeID($t_link->tableNum(), $va_tmp[0], $pn_en_locale_id, array('typename' => $va_tmp[1]), array('create' => true));
						
						$t_link->set('term_left_id', $t_place->getPrimaryKey());
						$t_link->set('term_right_id', $t_related_place->getPrimaryKey());
						$t_link->set('type_id', $vn_rel_type_id);
						$t_link->insert();
		
						if ($t_link->numErrors()) {
							print "[Error] could not link ".$va_subject['subject_id']." to ".$va_subject['related_subject_id'].": ".join('; ', $t_place->getErrors())."\n";
						}
					} else {
						$va_subject = array('subject_id' => $o_xml->getAttribute('Subject_ID'));
					}
					break;
				# ---------------------------
				case 'Associative_Relationships':
					$vn_parent_id = $vs_historic_flag = null;
					while($o_xml->read()) {
						switch($o_xml->name) {
							case 'Associative_Relationship':
								while($o_xml->read()) {
									switch($o_xml->name) {
										case 'Historic_Flag':
											switch($o_xml->nodeType) {
												case XMLReader::ELEMENT:
													$o_xml->read();
													$va_subject['historic_flag'] = $o_xml->value;
													break;
											}
											break;
										case 'Relationship_Type':
											switch($o_xml->nodeType) {
												case XMLReader::ELEMENT:
													$o_xml->read();
													$va_subject['relationship_type'] = $o_xml->value;
													break;
											}
											break;
										case 'VP_Subject_ID':
											switch($o_xml->nodeType) {
												case XMLReader::ELEMENT:
													$o_xml->read();
													$va_subject['related_subject_id'] = $o_xml->value;
													break;
											}
											break;
									}
								}
								break;
						}
					}
					break;
					# ---------------------------
			}
		}
	}
}	

	if ($vn_list_item_relation_type_id_related > 0) {
		print "[Notice] ADDING RELATED PLACE LINKS...\n";
		$vn_last_message_length = 0;
	
		$t_place = new ca_places();
		$t_link = new ca_places_x_places();
		$t_link->setMode(ACCESS_WRITE);
		foreach($va_item_item_links as $vs_left_id => $vs_right_id) {
			print str_repeat(chr(8), $vn_last_message_length);
			$vs_message = "[Notice] LINKING {$vs_left_id} to {$vs_right_id}";
			if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
			$vs_message .= str_repeat(' ', $vn_l);
			$vn_last_message_length = strlen($vs_message);
			print $vs_message;
		
			if (!($vn_left_item_id = $va_tgn_id_to_place_id[$vs_left_id])) {
				print "[Error] no list item id for left_id {$vs_left_id} (were there previous errors?)\n";
				continue;
			}
			if (!($vn_right_item_id = $va_tgn_id_to_place_id[$vs_right_id])) {
				print "[Error] no list item id for right_id {$vs_right_id} (were there previous errors?)\n";
				continue;
			}
		
			$t_link->set('term_left_id', $vn_left_item_id);
			$t_link->set('term_right_id', $vn_right_item_id);
			$t_link->set('type_id', $vn_list_item_relation_type_id_related);
			$t_link->insert();
		
			if ($t_link->numErrors()) {
				print "[Error] could not set link between {$vs_left_id} (was translated to item_id={$vn_left_item_id}) and {$vs_right_id} (was translated to item_id={$vn_right_item_id}): ".join('; ', $t_link->getErrors())."\n";
			}
		}
	} else {
		print "[Warning] Skipped import of term-term relationships because the ca_list_items_x_list_items 'related' relationship type is not defined for your installation\n";
	}

	print "[Notice] IMPORT COMPLETE.\n";
?>