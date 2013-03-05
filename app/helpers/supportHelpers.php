<?php
/** ---------------------------------------------------------------------
 * app/helpers/supportHelpers.php : support functions
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
	 * @return string 
	 */
	function caLoadAAT($ps_path_to_aat_data=null, $pa_options=null) {
		if (!$ps_path_to_aat_data) { $ps_path_to_aat_data = "./AAT.xml"; }
		if (!file_exists($ps_path_to_aat_data)) {
			die("ERROR: cannot find AAT data.\n");
		}

		require_once(__CA_LIB_DIR__.'/core/Db.php');
		require_once(__CA_MODELS_DIR__.'/ca_locales.php');
		require_once(__CA_MODELS_DIR__.'/ca_lists.php');
		require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
		require_once(__CA_MODELS_DIR__.'/ca_list_items_x_list_items.php');
		require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
	
		$_ = new Zend_Translate('gettext', __CA_APP_DIR__.'/locale/en_US/messages.mo', 'en_US');

		$t_locale = new ca_locales();
		$pn_en_locale_id = $t_locale->loadLocaleByCode('en_US');
	
	
		// create vocabulary list record (if it doesn't exist already)
		$t_list = new ca_lists();
		if (!$t_list->load(array('list_code' => 'aat'))) {
			$t_list->setMode(ACCESS_WRITE);
			$t_list->set('list_code', 'aat');
			$t_list->set('is_system_list', 0);
			$t_list->set('is_hierarchical', 1);
			$t_list->set('use_as_vocabulary', 1);
			$t_list->insert();
		
			if ($t_list->numErrors()) {
				print "ERROR: couldn't create ca_list row for AAT: ".join('; ', $t_list->getErrors())."\n";
				die;
			}
		
			$t_list->addLabel(array('name' => 'Art & Architecture Thesaurus'), $pn_en_locale_id, null, true);
		}
		$vn_list_id = $t_list->getPrimaryKey();
	
		// get list item types (should be defined by base installation profile [base.profile])
		// if your installation didn't use a profile inheriting from base.profile then you should make sure
		// that a list with code='list_item_types' is defined and the following four item codes are defined.
		// If these are not defined then the AAT will still import, but without any distinction between 
		// terms, facets and guide terms
		$vn_list_item_type_concept = 					$t_list->getItemIDFromList('list_item_types', 'concept');
		$vn_list_item_type_facet = 						$t_list->getItemIDFromList('list_item_types', 'facet');
		$vn_list_item_type_guide_term = 				$t_list->getItemIDFromList('list_item_types', 'guide_term');
		$vn_list_item_type_hierarchy_name = 			$t_list->getItemIDFromList('list_item_types', 'hierarchy_name');
	
		// get list item label types (should be defined by base installation profile [base.profile])
		// if your installation didn't use a profile inheriting from base.profile then you should make sure
		// that a list with code='list_item_label_types' is defined and the following four item codes are defined.
		// If these are not defined then the AAT will still import, but without any distinction between 
		// terms, facets and guide terms
		$vn_list_item_label_type_uf = 					$t_list->getItemIDFromList('list_item_label_types', 'uf');
		$vn_list_item_label_type_alt = 					$t_list->getItemIDFromList('list_item_label_types', 'alt');

	
		// get list item-to-item relationship type (should be defined by base installation profile [base.profile])
		// if your installation didn't use a profile inheriting from base.profile then you should make sure
		// that a ca_list_items_x_list_items relationship type with code='related' is defined. Otherwise import of term-to-term
		// relationships will fail.
		$t_rel_types = new ca_relationship_types();
		$vn_list_item_relation_type_id_related = 		$t_rel_types->getRelationshipTypeID('ca_list_items_x_list_items', 'related');


		// load voc_terms
		$o_xml = new XMLReader();
		$o_xml->open($ps_path_to_aat_data);
	
		print "READING AAT TERMS...\n";
	
		$va_parent_child_links = array();
		$va_item_item_links = array();
		$va_aat_id_to_item_id = array();
		$vn_last_message_length = 0;
	
		$vn_term_count = 0;
	
		while($o_xml->read()) {
			switch($o_xml->name) {
				# ---------------------------
				case 'Subject':
					if ($o_xml->nodeType == XMLReader::END_ELEMENT) {
						if ($va_subject['subject_id'] == '300000000') { break; }	// skip top-level root
					
						$vs_preferred_term = $va_subject['preferred_term'];
					
					
						switch($va_subject['record_type']) {
							case 'Concept':
								$vn_type_id = $vn_list_item_type_hierarchy_name;
								$pb_is_enabled = true;
								break;
							case 'Facet':
								$vn_type_id = $vn_list_item_type_facet;
								$vs_preferred_term = '<'.$vs_preferred_term.'>';
								$pb_is_enabled = false;
								break;
							case 'Guide Term':
								$vn_type_id = $vn_list_item_type_guide_term;
								$vs_preferred_term = '<'.$vs_preferred_term.'>';
								$pb_is_enabled = false;
								break;
							case 'Hierarchy Name':
								$vn_type_id = $vn_list_item_type_hierarchy_name;
								$pb_is_enabled = false;
								break;
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
					
					
						if ($t_item = $t_list->addItem($va_subject['subject_id'], $pb_is_enabled, false, null, $vn_type_id, $va_subject['subject_id'], '', 4, 1)) {
							$va_aat_id_to_item_id[$va_subject['subject_id']] = $t_item->getPrimaryKey();
						
							if ($va_subject['preferred_parent_subject_id'] != 300000000) {
								$va_parent_child_links[$va_subject['subject_id']] = $va_subject['preferred_parent_subject_id'];
							}
						
							// add preferred labels
							if (!($t_item->addLabel(
								array('name_singular' => trim(htmlentities($vs_preferred_term, ENT_NOQUOTES)), 'name_plural' => trim(htmlentities($vs_preferred_term, ENT_NOQUOTES)), 'description' => $va_subject['description']),
								$pn_en_locale_id, null, true
							))) {
								print "ERROR: Could not add preferred label to AAT term [".$va_subject['subject_id']."] ".$vs_preferred_term.": ".join("; ", $t_item->getErrors())."\n";
							}
						
							// add alternate labels
							if(is_array($va_subject['non_preferred_terms'])) {
								for($vn_i=0; $vn_i < sizeof($va_subject['non_preferred_terms']); $vn_i++) {
									$vs_np_label = $va_subject['non_preferred_terms'][$vn_i];
									$vs_np_term_type = $va_subject['non_preferred_term_types'][$vn_i];
								
									switch($vs_np_term_type) {
										case 'Used For Term':
											$vn_np_term_type_id = $vn_list_item_label_type_uf;
											break;
										case 'Alternate Descriptor':
											$vn_np_term_type_id = $vn_list_item_label_type_alt;
											break;
										default:
											$vn_np_term_type_id = null;
											break;
									}
								
									if (!($t_item->addLabel(
										array('name_singular' => trim(htmlentities($vs_np_label, ENT_NOQUOTES)), 'name_plural' => trim(htmlentities($vs_np_label, ENT_NOQUOTES)), 'description' => ''),
										$pn_en_locale_id, $vn_np_term_type_id, false
									))) {
										print "ERROR: Could not add non-preferred label to AAT term [".$va_subject['subject_id']."] ".$vs_np_label."\n"; //: ".join("; ", $t_item->getErrors())."\n";
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
							print "ERROR: Could not import AAT term [".$va_subject['subject_id']."] ".$vs_preferred_term.": ".join("; ", $t_list->getErrors())."\n";
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
	
		print "\n\nLINKING TERMS IN HIERARCHY...\n";
		$vn_last_message_length = 0;
	
		$t_item = new ca_list_items();
		$t_item->setMode(ACCESS_WRITE);
		foreach($va_parent_child_links as $vs_child_id => $vs_parent_id) {
			print str_repeat(chr(8), $vn_last_message_length);
			$vs_message = "\tLINKING {$vs_child_id} to parent {$vs_parent_id}";
			if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
			$vs_message .= str_repeat(' ', $vn_l);
			$vn_last_message_length = strlen($vs_message);
			print $vs_message;
		
			if (!($vn_child_item_id = $va_aat_id_to_item_id[$vs_child_id])) {
				print "ERROR: no list item id for child_id {$vs_child_id} (were there previous errors?)\n";
				continue;
			}
			if (!($vn_parent_item_id = $va_aat_id_to_item_id[$vs_parent_id])) {
				print "ERROR: no list item id for parent_id {$vs_child_id} (were there previous errors?)\n";
				continue;
			}
		
			if(!$t_item->load($vn_child_item_id)) {
				print "ERROR: could not load item for {$vs_child_id} (was translated to item_id={$vn_child_item_id})\n";
				continue;
			}
		
			$t_item->set('parent_id', $vn_parent_item_id);
			$t_item->update();
		
			if ($t_item->numErrors()) {
				print "ERROR: could not set parent_id for {$vs_child_id} (was translated to item_id={$vn_child_item_id}): ".join('; ', $t_item->getErrors())."\n";
			}
		}
	
		if ($vn_list_item_relation_type_id_related > 0) {
			print "\n\nADDING RELATED TERM LINKS...\n";
			$vn_last_message_length = 0;
		
			$t_item = new ca_list_items();
			$t_link = new ca_list_items_x_list_items();
			$t_link->setMode(ACCESS_WRITE);
			foreach($va_item_item_links as $vs_left_id => $vs_right_id) {
				print str_repeat(chr(8), $vn_last_message_length);
				$vs_message = "\tLINKING {$vs_left_id} to {$vs_right_id}";
				if (($vn_l = 100-strlen($vs_message)) < 1) { $vn_l = 1; }
				$vs_message .= str_repeat(' ', $vn_l);
				$vn_last_message_length = strlen($vs_message);
				print $vs_message;
			
				if (!($vn_left_item_id = $va_aat_id_to_item_id[$vs_left_id])) {
					print "ERROR: no list item id for left_id {$vs_left_id} (were there previous errors?)\n";
					continue;
				}
				if (!($vn_right_item_id = $va_aat_id_to_item_id[$vs_right_id])) {
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
	}
	# ---------------------------------------
?>