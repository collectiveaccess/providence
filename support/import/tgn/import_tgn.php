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
	require_once("../../../setup.php");
	
	if (!file_exists('./tgn_xml_12')) {
		die("ERROR: you must place the 'tgn_xml_12' data file directory in the same directory as this script.\n");
	}

	require_once(__CA_LIB_DIR__.'/core/Db.php');
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
			print "ERROR: couldn't create ca_list row for place hierarchies: ".join('; ', $t_list->getErrors())."\n";
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

	// load places
	$o_xml = new XMLReader();
	
		print "READING TGN TERMS...\n";
	
	$va_parent_child_links = array();
	$va_item_item_links = array();
	//$va_tgn_id_to_place_id = array();
	$vn_last_message_length = 0;

	$vn_term_count = 0;
	
	$t_place = new ca_places();
	$t_place->setMode(ACCESS_WRITE);
if (false) {	
	for($vn_file_index=1; $vn_file_index <= 15; $vn_file_index++) {
		$o_xml->open("tgn_xml_12/TGN{$vn_file_index}.xml");
	
		print "\tREADING TERMS FROM TGN{$vn_file_index}.xml...\n";
	
		while($o_xml->read()) {
			switch($o_xml->name) {
				# ---------------------------
				case 'Subject':
					if ($o_xml->nodeType == XMLReader::END_ELEMENT) {
						//if ($va_subject['subject_id'] == '300000000') { break; }	// skip top-level root
					
						$vs_preferred_term = $va_subject['preferred_term'];
					
					
						switch($va_subject['record_type']) {
							default:
								$vn_type_id = null;
								$pb_is_enabled = true;
								break;
						}
					
						//print_r($va_subject);
						print str_repeat(chr(8), $vn_last_message_length);
						$vs_message = "\tIMPORTING #".($vn_term_count+1)." [".$va_subject['subject_id']."] ".$vs_preferred_term;
						if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
						$vs_message .= str_repeat(' ', $vn_l);
						$vn_last_message_length = strlen($vs_message);
						print $vs_message;
					
					
						$t_place->clear();
						$t_place->set('parent_id', $pn_parent_id);
						$t_place->set('type_id', $vn_type_id);
						$t_place->set('idno', $va_subject['subject_id']);
						$t_place->set('hierarchy_id', $vn_tgn_id);
						$t_place->set('parent_id', $pn_parent_id);
						
						if ($vn_place_id = $t_place->insert(array('dontSetHierarchicalIndexing' => true))) {
							//$va_tgn_id_to_place_id[$va_subject['subject_id']] = $vn_place_id;
						
							if (!($t_place->addLabel(
								array('name' => $vs_preferred_term, 'description' => ''),
								$pn_en_locale_id, null, true
							))) {
								print "ERROR: Could not add preferred label to TGN term [".$va_subject['subject_id']."] ".$vs_preferred_term.": ".join("; ", $t_place->getErrors())."\n";
							}
						
							//if ($va_subject['preferred_parent_subject_id'] != 300000000) {
								//$va_parent_child_links[$va_subject['subject_id']] = $va_subject['preferred_parent_subject_id'];
							//}
						
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
										print "ERROR: Could not add non-preferred label to TGN term [".$va_subject['subject_id']."] ".$vs_np_label.": ".join("; ", $t_place->getErrors())."\n";
									}
								}
							}
						
							// record item-item relations
							if (is_array($va_subject['related_subjects'])) {
								foreach($va_subject['related_subjects'] as $vs_rel_subject_id) {
									$va_item_item_links[$va_subject['subject_id']] = $vs_rel_subject_id;
								}
							}
						
							$vn_term_count++;
						} else {
							print "ERROR: Could not import TGN term [".$va_subject['subject_id']."] ".$vs_preferred_term.": ".join("; ", $t_list->getErrors())."\n";
						}
					} else {
						$va_subject = array('subject_id' => $o_xml->getAttribute('Subject_ID'));
					}
					break;
				# ---------------------------
				case 'Descriptive_Note':
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
				case 'Facet_Code':
					switch($o_xml->nodeType) {
						case XMLReader::ELEMENT:
							$o_xml->read();
							$va_subject['facet_code'] = $o_xml->value;
							break;
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
				case 'Preferred_Term':
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
								break;
							case 'Preferred_Term':
								break(2);
						}
					}
					break;
				# ---------------------------
				case 'Non-Preferred_Term':
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
			}
		}
	
		$o_xml->close();
	}
}

if (false) {	
	print "\n\nLINKING TERMS IN HIERARCHY...\n";
	$vn_last_message_length = 0;

	$t_place = new ca_places();
	$t_parent = new ca_places();
	$t_place->setMode(ACCESS_WRITE);
	
	
	for($vn_file_index=1; $vn_file_index <= 15; $vn_file_index++) {
		$o_xml->open("tgn_xml_12/TGN{$vn_file_index}.xml");
	
		print "\tREADING TERMS FROM TGN{$vn_file_index}.xml...\n";
	
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
						$vs_message = "\tLINKING {$vs_child_id} to parent {$vs_parent_id}";
						if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
						$vs_message .= str_repeat(' ', $vn_l);
						$vn_last_message_length = strlen($vs_message);
						print $vs_message;
						
						
			
						$t = new Timer();
						if(!$t_place->load(array('idno' => $vs_child_id))) {
							print "ERROR: could not load item for {$vs_child_id} (was translated to item_id={$vn_child_item_id})\n";
							continue;
						}
						if(!$t_parent->load(array('idno' => $vs_parent_id))) {
							print "ERROR: no list item id for parent_id {$vs_parent_id} (were there previous errors?)\n";
							continue;
						}
						//print "Get ids=".$t->getTime(4)."\n";
							$t = new Timer();
						$t_place->set('parent_id', $t_parent->getPrimaryKey());
						$t_place->update(array('dontSetHierarchicalIndexing' => true));
				///print "Do updaste=".$t->getTime(4)."\n";
						if ($t_place->numErrors()) {
							print "ERROR: could not set parent_id for {$vs_child_id} (was translated to item_id=".$t_place_id->getPrimaryKey()."): ".join('; ', $t_place->getErrors())."\n";
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
	print "Rebuilding hier indices for hierarchy_id={$vn_tgn_id}...\n";
	$t_place->rebuildHierarchicalIndex($vn_tgn_id);


	if ($vn_list_item_relation_type_id_related > 0) {
		print "\n\nADDING RELATED PLACE LINKS...\n";
		$vn_last_message_length = 0;
	
		$t_place = new ca_places();
		$t_link = new ca_places_x_places();
		$t_link->setMode(ACCESS_WRITE);
		foreach($va_item_item_links as $vs_left_id => $vs_right_id) {
			print str_repeat(chr(8), $vn_last_message_length);
			$vs_message = "\tLINKING {$vs_left_id} to {$vs_right_id}";
			if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
			$vs_message .= str_repeat(' ', $vn_l);
			$vn_last_message_length = strlen($vs_message);
			print $vs_message;
		
			if (!($vn_left_item_id = $va_tgn_id_to_place_id[$vs_left_id])) {
				print "ERROR: no list item id for left_id {$vs_left_id} (were there previous errors?)\n";
				continue;
			}
			if (!($vn_right_item_id = $va_tgn_id_to_place_id[$vs_right_id])) {
				print "ERROR: no list item id for right_id {$vs_right_id} (were there previous errors?)\n";
				continue;
			}
		
			$t_link->set('term_left_id', $vn_left_item_id);
			$t_link->set('term_right_id', $vn_right_item_id);
			$t_link->set('type_id', $vn_list_item_relation_type_id_related);
			$t_link->insert();
		
			if ($t_link->numErrors()) {
				print "ERROR: could not set link between {$vs_left_id} (was translated to item_id={$vn_left_item_id}) and {$vs_right_id} (was translated to item_id={$vn_right_item_id}): ".join('; ', $t_link->getErrors())."\n";
			}
		}
	} else {
		print "WARNING: Skipped import of term-term relationships because the ca_list_items_x_list_items 'related' relationship type is not defined for your installation\n";
	}
	
	
		print "\n\nIMPORT COMPLETE.\n";
?>