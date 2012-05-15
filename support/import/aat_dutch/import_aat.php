<?php
/* ----------------------------------------------------------------------
 * support/import/aat_dutch/import_aat.php : Import Dutch-language AAT XML files (2000 edition - should work for others as well?)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
	require_once("../../../setup.php");
	
	if (!file_exists('./AAT.xml')) {
		die("ERROR: you must place the AAT.xml data file in the same directory as this script.\n");
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
	
	if (!$pn_nl_locale_id = $t_locale->loadLocaleByCode('nl_NL')) {
		$pn_nl_locale_id = $t_locale->loadLocaleByCode('nl_BE');
	}
	
	if (!$pn_nl_locale_id) {
		die("ERROR: You can only import the Dutch-language AAT into an installation configured to support the nl_NL (Netherlands) or nl_BE (Vlaams Belgium) locale. Add one of these locales to your system and try again.\n");
	}
	
	
	// create vocabulary list record (if it doesn't exist already)
	$t_list = new ca_lists();
	if (!$t_list->load(array('list_code' => 'aat_nl'))) {
		$t_list->setMode(ACCESS_WRITE);
		$t_list->set('list_code', 'aat_nl');
		$t_list->set('is_system_list', 0);
		$t_list->set('is_hierarchical', 1);
		$t_list->set('use_as_vocabulary', 1);
		$t_list->insert();
		
		if ($t_list->numErrors()) {
			print "ERROR: couldn't create ca_list row for AAT: ".join('; ', $t_list->getErrors())."\n";
			die;
		}
		
		$t_list->addLabel(array('name' => 'Art & Architecture Thesaurus [Nederlands]'), $pn_en_locale_id, null, true);
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
	$o_xml->open('AAT.xml');
	
	print "READING AAT TERMS...\n";
	
	$va_parent_child_links = array();
	$va_item_item_links = array();
	$va_aat_id_to_item_id = array();
	$vn_last_message_length = 0;
	
	$vn_term_count = 0;
	
	while($o_xml->read()) {
		switch($o_xml->name) {
			# ---------------------------
			case 'record':
				if ($o_xml->nodeType == XMLReader::END_ELEMENT) {
					$vs_preferred_term_english = $va_subject['preferred_term_english'];
					$vs_preferred_term_dutch = $va_subject['preferred_term_dutch'];
					
					
					switch($va_subject['record_type']) {
						case 'guide term':
							$vn_type_id = $vn_list_item_type_guide_term;
							$vs_preferred_term_english = '<'.$vs_preferred_term_english.'>';
							$vs_preferred_term_dutch = '<'.$vs_preferred_term_dutch.'>';
							$pb_is_enabled = false;
							break;
						default:
							$vn_type_id = null;
							$pb_is_enabled = true;
							break;
					}
					
					//print_r($va_subject);
					print str_repeat(chr(8), $vn_last_message_length);
					$vs_message = "\tIMPORTING #".($vn_term_count+1)." [".$va_subject['term_number']."] ".$vs_preferred_term_dutch.'/'.$vs_preferred_term_english;
					if (($vn_l = 200-strlen($vs_message)) < 1) { $vn_l = 1; }
					$vs_message .= str_repeat(' ', $vn_l);
					$vn_last_message_length = strlen($vs_message);
					print $vs_message;
			
					if ($t_item = $t_list->addItem($va_subject['term_number'], $pb_is_enabled, false, null, $vn_type_id, $va_subject['term_number'], '', 4, 1)) {
						
						// add preferred labels
						if($vs_preferred_term_dutch) {
							if (!($t_item->addLabel(
								array('name_singular' => $vs_preferred_term_dutch, 'name_plural' => $vs_preferred_term_dutch, 'description' => $va_subject['description_dutch']),
								$pn_nl_locale_id, null, true
							))) {
								print "ERROR: Could not add Dutch preferred label to AAT term [".$va_subject['term_number']."] ".$vs_preferred_term_dutch.": ".join("; ", $t_item->getErrors())."\n";
							}
						}
						
						if ($vs_preferred_term_english) {
							if (!($t_item->addLabel(
								array('name_singular' => $vs_preferred_term_english, 'name_plural' => $vs_preferred_term_english, 'description' => $va_subject['description_english']),
								$pn_nl_locale_id, null, true
							))) {
								print "ERROR: Could not add English preferred label to AAT term [".$va_subject['term_number']."] ".$vs_preferred_term_english.": ".join("; ", $t_item->getErrors())."\n";
							}
						}
						
						$va_aat_id_to_item_id[$vs_preferred_term_dutch] = $va_aat_id_to_item_id[$vs_preferred_term_english] = $t_item->getPrimaryKey();
						
						if (!$va_parent_child_links[$vs_pref_key] = $va_subject['parent_dutch']) {
							$va_parent_child_links[$vs_pref_key] = $va_subject['parent_english'];
						}
						
						
						// add alternate labels
						if(is_array($va_subject['use_for_english'])) {
							for($vn_i=0; $vn_i < sizeof($va_subject['use_for_english']); $vn_i++) {
								$vs_np_label = $va_subject['use_for_english'][$vn_i];
								
								if (!($t_item->addLabel(
									array('name_singular' => $vs_np_label, 'name_plural' => $vs_np_label, 'description' => ''),
									$pn_en_locale_id, $vn_list_item_label_type_uf, false
								))) {
									print "ERROR: Could not add English non-preferred label to AAT term [".$va_subject['term_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n";
								}
							}
						}
						if(is_array($va_subject['use_for_dutch'])) {
							for($vn_i=0; $vn_i < sizeof($va_subject['use_for_dutch']); $vn_i++) {
								$vs_np_label = $va_subject['use_for_dutch'][$vn_i];
								
								if (!($t_item->addLabel(
									array('name_singular' => $vs_np_label, 'name_plural' => $vs_np_label, 'description' => ''),
									$pn_nl_locale_id, $vn_list_item_label_type_uf, false
								))) {
									print "ERROR: Could not add Dutch non-preferred label to AAT term [".$va_subject['term_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n";
								}
							}
						}
						
						// record item-item relations
						if (is_array($va_subject['related_term'])) {
							foreach($va_subject['related_term'] as $vs_rel_subject) {
								$va_item_item_links[$vs_preferred_term_dutch] = $vs_rel_subject;
							}
						}
						
						$vn_term_count++;
					} else {
						print "ERROR: Could not import AAT term [".$va_subject['term_number']."] ".$vs_preferred_term_dutch.'/'.$vs_preferred_term_english.": ".join("; ", $t_list->getErrors())."\n";
					}
				} else {
					$va_subject = array();
				}
				break;
			# ---------------------------
			case 'english_note':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['description_english'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'scope_note':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['description_dutch'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'term.domain':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['record_type'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'term.number':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['term_number'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'term':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['preferred_term_dutch'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'english_term':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['preferred_term_english'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'broader_term':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['parent_dutch'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'english_broader_term':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['parent_english'] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'related_term':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['related_terms'][] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'used_for':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['use_for_dutch'][] = $o_xml->value;
						break;
				}
				break;
			# ---------------------------
			case 'english_used_for':
				switch($o_xml->nodeType) {
					case XMLReader::ELEMENT:
						$o_xml->read();
						$va_subject['use_for_english'][] = $o_xml->value;
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
		if (($vn_l = 200-strlen($vs_message)) < 1) { $vn_l = 1; }
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
			if (($vn_l = 200-strlen($vs_message)) < 1) { $vn_l = 1; }
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
?>