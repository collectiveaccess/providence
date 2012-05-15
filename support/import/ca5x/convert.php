<?php
/* ----------------------------------------------------------------------
 * support/import/oc5x/convert.php : conversion script for CollectiveAccess 0.5x databases
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
	
	set_time_limit(24 * 60 * 60 * 7); /* maximum time: 7 days :-) */
	
	if (!$argv[1]) {
		die("\nconvert.php: Copies and converts CollectiveAccess 0.55 database to version 0.6.\n\nUSAGE: convert.php 'instance_name' 'name_of_CA_0.55_database'\nExample: ./convert.php 'www.mycollection.org' 'collectiveaccess_05'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	
	$ps_ca5x_database = $argv[2];		// name of CollectiveAccess 0.5x database (Providence database login must have SELECT access to it)
	if (!$ps_ca5x_database) {
		die("ERROR: Must specify the name of the CollectiveAccess 0.55 database\n");
	}
	
	require_once("../../../setup.php");

	require_once(__CA_LIB_DIR__.'/core/Db.php');
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
	require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
	require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
	require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
	require_once(__CA_MODELS_DIR__.'/ca_entities.php');
	require_once(__CA_MODELS_DIR__.'/ca_places.php');
	require_once(__CA_MODELS_DIR__.'/ca_collections.php');
	require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
	require_once(__CA_MODELS_DIR__.'/ca_storage_locations.php');
	require_once(__CA_MODELS_DIR__.'/ca_object_events.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_uis.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
	require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
	
	//
	// Set up
	//
	$o_db = new Db();
	$o_config = Configuration::load();
	$t_list = new ca_lists();
	
	// --------------------------------------------------------------------------------------------------
	// TODO: This should automatically configure itself
	//
	// Path to media in CA 0.5x database
	$vs_media_path = "/web/production/opencollection/web/media/durst";
	
	// The first path of the url returned by the getMediaUrl() functions will use the URL path to the 0.6 installation
	// so we need to strip it out using the string to match below
	$vs_discard_path = "http://durst2.whirl-i-gig.com/media/durst";
	// --------------------------------------------------------------------------------------------------
	
	//
	// Get default locale
	//
	$pa_locale_defaults = $o_config->getList('locale_defaults');
	$ps_locale = $pa_locale_defaults[0];
	$t_locale = new ca_locales();
	$pn_locale_id = $t_locale->localeCodeToID($ps_locale);
	
	
	
	$o_tep = new TimeExpressionParser();
	$t_rel_types = new ca_relationship_types();
	$vn_object_event_movement_id = $t_list->getItemIDFromList('object_events', 'movement');
	$vn_storage_location_destination_relationship_type_id = $t_rel_types->getRelationshipTypeID('ca_object_events_x_storage_locations', 'is_destination');
	
	
	//
	// Get schema elements from old db
	//	
		// Convert storage locations
			
		// Convert object types
			print "CONVERTING object types\n";
			print "\t\tImporting types...\n";
			$qr_ca05_object_types = $o_db->query("
				SELECT *
				FROM {$ps_ca5x_database}.object_phys_types
			");
			if (!$t_list->load(array('list_code' => 'object_types'))) { die("CONVERSION ERROR: no object_type list defined\n"); }
			
			$va_object_type_id_conv = array();
			while($qr_ca05_object_types->nextRow()) {
				if (!$qr_ca05_object_types->get('parent_id')) { continue; }
				$t_item = $t_list->addItem(
					$qr_ca05_object_types->get('typename_plural'), true, false, null, null, $qr_ca05_object_types->get('type_id'), null, 4, 1
				);
				
				$t_item->addLabel(
					array(
						'name_singular' => $qr_ca05_object_types->get('typename_plural'),
						'name_plural' => $qr_ca05_object_types->get('typename_plural')
					), $pn_locale_id, null, true
				);
				
				$va_object_type_id_conv[$qr_ca05_object_types->get('type_id')] = $t_item->getPrimaryKey();
			}
		
			// relink hierarchy
			print "\t\tRe-linking type hierarchy...\n";
			$t_item = new ca_list_items();
			$qr_ca05_object_types->seek(0);
			while($qr_ca05_object_types->nextRow()) {
				$vn_id = $va_object_type_id_conv[$qr_ca05_object_types->get('type_id')];
				if ($vn_parent_id = $va_object_type_id_conv[$qr_ca05_object_types->get('parent_id')]) {
					if (!$t_item->load($vn_id)) {
						print "\tCONVERSION ERROR: Couldn't find object type [".$qr_ca05_object_types->get('typename_plural')."]\n";
					} else {
						$t_item->setMode(ACCESS_WRITE);
						$t_item->set('parent_id', $vn_parent_id);
						$t_item->update();
						
						if ($t_item->numErrors()) {
							print "\tCONVERSION ERROR: Couldn't place object type [".$qr_ca05_object_types->get('typename_plural')."] into hierarchy: ".join('; ', $t_item->getErrors())."\n";
						}
					}
				}
			}
		
		// Convert object sources
			print "CONVERTING object sources\n";
			print "\t\tImporting sources...\n";
			$qr_ca05_object_sources = $o_db->query("
				SELECT *
				FROM {$ps_ca5x_database}.object_data_sources
			");
			if (!$t_list->load(array('list_code' => 'object_sources'))) { die("CONVERSION ERROR: no object_sources list defined\n"); }
			
			$va_object_source_id_conv = array();
			while($qr_ca05_object_sources->nextRow()) {
				if (!$qr_ca05_object_sources->get('parent_id')) { continue; }
				$t_item = $t_list->addItem(
					$qr_ca05_object_sources->get('name'), true, false, null, null, $qr_ca05_object_sources->get('source_id'), null, 4, 1
				);
				
				$t_item->addLabel(
					array(
						'name_singular' => $qr_ca05_object_sources->get('name'),
						'name_plural' => $qr_ca05_object_sources->get('name')
					), $pn_locale_id, null, true
				);
				
				$va_object_source_id_conv[$qr_ca05_object_sources->get('source_id')] = $t_item->getPrimaryKey();
			}
		
			// relink hierarchy
			print "\t\tRe-linking source hierarchy...\n";
			$t_item = new ca_list_items();
			$qr_ca05_object_sources->seek(0);
			while($qr_ca05_object_sources->nextRow()) {
				$vn_id = $va_object_source_id_conv[$qr_ca05_object_sources->get('source_id')];
				if ($vn_parent_id = $va_object_source_id_conv[$qr_ca05_object_sources->get('parent_id')]) {
					if (!$t_item->load($vn_id)) {
						print "\tCONVERSION ERROR: Couldn't find object source [".$qr_ca05_object_sources->get('name')."]\n";
					} else {
						$t_item->setMode(ACCESS_WRITE);
						$t_item->set('parent_id', $vn_parent_id);
						$t_item->update();
						
						if ($t_item->numErrors()) {
							print "\tCONVERSION ERROR: Couldn't place object source [".$qr_ca05_object_sources->get('name')."] into hierarchy: ".join('; ', $t_item->getErrors())."\n";
						}
					}
				}
			}
		
		// Place hierarchies
			print "CONVERTING place hierarchies\n";
			print "\t\tImporting hierarchies...\n";
			$qr_ca05_place_hierarchies = $o_db->query("
				SELECT *
				FROM {$ps_ca5x_database}.place_hierarchies
			");
			if (!$t_list->load(array('list_code' => 'place_hierarchies'))) { die("CONVERSION ERROR: no place_hierarchies list defined\n"); }
			
			$va_place_hierarchy_id_conv = array();
			while($qr_ca05_place_hierarchies->nextRow()) {
				$t_item = $t_list->addItem(
					$qr_ca05_place_hierarchies->get('name'), true, false, null, null, $qr_ca05_place_hierarchies->get('name_short') ?  $qr_ca05_place_hierarchies->get('name_short') :  $qr_ca05_place_hierarchies->get('name'), null, 4, 1
				);
				
				$t_item->addLabel(
					array(
						'name_singular' => $qr_ca05_place_hierarchies->get('name'),
						'name_plural' => $qr_ca05_place_hierarchies->get('name'),
						'description' => $qr_ca05_place_hierarchies->get('description')
					), $pn_locale_id, null, true
				);
				
				$va_place_hierarchy_id_conv[$qr_ca05_place_hierarchies->get('place_hierarchy_id')] = $t_item->getPrimaryKey();
			}
		
		// Vocabulary hierarchies
			print "CONVERTING vocabulary hierarchies\n";
			print "\t\tImporting hierarchies...\n";
			$qr_ca05_voc_hierarchies = $o_db->query("
				SELECT *
				FROM {$ps_ca5x_database}.voc_vocabularies
			");
			
			$va_voc_hierarchy_id_conv = array();
			while($qr_ca05_voc_hierarchies->nextRow()) {
				$t_list->setMode(ACCESS_WRITE);
				$t_list->set('list_code', 'voc_'.$qr_ca05_voc_hierarchies->get('vocabulary_id'));
				$t_list->set('is_system_list', 0);
				$t_list->set('is_hierarchical', 1);
				$t_list->set('use_as_vocabulary', 1);
				
				$t_list->insert();
				
				if($t_list->numErrors()) {
					print "CONVERSION ERROR: couldn't insert new vocabulary '".$qr_ca05_voc_hierarchies->get('name')."': ".join('; ', $t_list->numErrors())."\n";
					continue;
				}
				
				$t_list->addLabel(
					array('name' => $qr_ca05_voc_hierarchies->get('name')), 
					$pn_locale_id, null, true
				);
				
				$va_voc_hierarchy_id_conv[$qr_ca05_voc_hierarchies->get('vocabulary_id')] = $t_list->getPrimaryKey();
			}
		
		// Convert place/entity/collection/occurrence types
			$va_types_to_import = array(
				'place_types' => array(
					'list_code' => 'place_types'
				),
				'entity_types' => array(
					'list_code' => 'entity_types'
				),
				'collection_types' => array(
					'list_code' => 'entity_types'
				),
				'occurrence_types' => array(
					'list_code' => 'occurrence_types'
				),
				'storage_location_types' => array(
					'list_code' => 'storage_location_types'
				)
			);
			
			$va_authority_type_id_conv = array();
			
			foreach($va_types_to_import as $vs_type => $va_type_info) {
				print "CONVERTING {$vs_type}\n";
				print "\t\tImporting types...\n";
				$qr_ca05_auth_types = $o_db->query("
					SELECT *
					FROM {$ps_ca5x_database}.{$vs_type}
				");
				if (!$t_list->load(array('list_code' => $va_type_info['list_code']))) { die("CONVERSION ERROR: no ".$va_type_info['list_code']." list defined\n"); }
				
				$va_authority_type_id_conv[$vs_type] = array();
				while($qr_ca05_auth_types->nextRow()) {
					$t_item = $t_list->addItem(
						$qr_ca05_auth_types->get('name_plural'), true, false, null, null, $qr_ca05_auth_types->get('type_id') , null, 4, 1
					);
					
					$t_item->addLabel(
						array(
							'name_singular' => $qr_ca05_auth_types->get('typename_singular'),
							'name_plural' => $qr_ca05_auth_types->get('typename_plural'),
							'description' => $qr_ca05_auth_types->get('description')
						), $pn_locale_id, null, true
					);
					
					$va_authority_type_id_conv[$vs_type][$qr_ca05_auth_types->get('type_id')] = $t_item->getPrimaryKey();
				}
			}
		
		// Convert attribute types (elements)
		print "CONVERTING attribute elements\n";
			print "\t\tImporting attribute elements...\n";
			$qr_ca05_md_elements= $o_db->query("
				SELECT *
				FROM {$ps_ca5x_database}.weblib_md_elements
			");
		
			$va_element_list = array();
			$va_element_id_conv = array();
			while($qr_ca05_md_elements->nextRow()) {
				$vs_element_code = preg_replace('/[ ]+/', '_', strtolower($qr_ca05_md_elements->get('name_short')));
				
				$t_md_element = new ca_metadata_elements();
				$t_md_element->setMode(ACCESS_WRITE);
				$t_md_element->set('element_code', $vs_element_code);
				$t_md_element->set('parent_id', null);
				$t_md_element->set('documentation_url', $qr_ca05_md_elements->get('documentation_url'));
				
				$va_settings = unserialize(base64_decode($qr_ca05_md_elements->get('rules')));
				switch($qr_ca05_md_elements->get('datatype')) {
					case 3:
						$t_md_element->set('datatype', 2);	// date
						$t_md_element->setSetting('minChars', $va_settings['min_length']);
						$t_md_element->setSetting('maxChars', $va_settings['max_length']);
						break;
					default:
						$t_md_element->set('datatype', 1);	// text
						$t_md_element->setSetting('minChars', $va_settings['min_length']);
						$t_md_element->setSetting('maxChars', $va_settings['max_length']);
						$t_md_element->setSetting('regex', $va_settings['pattern']);
						break;
				}
								
				$t_md_element->setSetting('fieldWidth', $va_settings['field_width']);
				$t_md_element->setSetting('fieldHeight', $va_settings['field_height']);
				
				$t_md_element->insert();
				
				$va_element_list[] = $vs_element_code;
				$va_element_id_conv[$qr_ca05_md_elements->get('element_id')] = $t_md_element->getPrimaryKey();
				
				$t_md_element->addLabel(
					array('name' => $qr_ca05_md_elements->get('name'), 'description' => $qr_ca05_md_elements->get('description')),
					$pn_locale_id, null, true
				);
			}
			
			print "\t\tConverting type restrictions...\n";
			
			$qr_ca05_md_elements= $o_db->query("
				SELECT *
				FROM {$ps_ca5x_database}.weblib_md_template_elements
			");
			
			while($qr_ca05_md_elements->nextRow()) {
				$t_res = new ca_metadata_type_restrictions();
				$t_res->setMode(ACCESS_WRITE);
				
				switch($qr_ca05_md_elements->get('table_num')) {
					case 1:	// objects
						$t_res->set('table_num', 57);
						if ($qr_ca05_md_elements->get('template_id')) {
							$t_res->set('type_id', $va_authority_type_id_conv['object_types'][$qr_ca05_md_elements->get('template_id')]);
						} else {
							$t_res->set('type_id', null);
						}
						$t_res->set('element_id', $va_element_id_conv[$qr_ca05_md_elements->get('element_id')]);
						break;
					// TODO: handle entity, place, occurrence and collection attributes
					default:
						continue;
				}
				
				$t_res->setSetting('minAttributesPerRow', 0);
				$t_res->setSetting('maxAttributesPerRow', 65535);
				$t_res->setSetting('minimumAttributeBundlesToDisplay', 1);
				
				$t_res->insert();
			}
		
		// Convert relationship types
		print "CONVERTING relationship types\n";
			print "\t\tImporting types...\n";
			$qr_ca05_rel_types = $o_db->query("
				SELECT *
				FROM {$ps_ca5x_database}.relationship_types
			");
		
			$va_05x_table_num_to_06_table_num = array(
				14 => 75,		// place_names_x_place_names
				25 => 58,		// objects_x_collections
				26 => 14,		// collection_x_collection
				27 => 62, 	// objects_x_objects
				28 => 59,		// objects_x_entities,
				29 => 64, 	// objects_x_places
				30 => 63,		// objects_x_occurrences
				32 => 69,		// occurrence_x_occurrence
				34 => 26,		// entity_x_entity
				42 => 34,		// voc_terms_x_voc_terms
				43 => 65,		// objects_x_voc_terms
				44 => 23,		// entities_x_places
				45 => 22,		// entities_x_occurrences
				46 => 74,		// places_x_occurrences
				47 => 24,		// entities_x_voc_terms
				48 => 76,		// places_x_voc_terms
				49 => 70,		// occurrences_x_voc_terms
				50 => 53,		// entities_x_lots
				122 => 15,	// collections_x_voc_terms
				123 => 21,	// entities_x_collections
				124 => 68,	// occurrences_x_collections
				125 => 73	// places_x_collections
			);
		
			$va_relationship_type_conv = array();
			while($qr_ca05_rel_types->nextRow()) {
				$vn_new_table_num = $va_05x_table_num_to_06_table_num[$qr_ca05_rel_types->get('table_num')];
				if (!$vn_new_table_num) { continue; }
				
				$t_rel = new ca_relationship_types();
				$t_rel->setMode(ACCESS_WRITE);
				$t_rel->set('table_num', $vn_new_table_num);
				$t_rel->set('type_code', 'rel_'.$qr_ca05_rel_types->get('type_id'));
				$t_rel->set('is_default', 0);
				
				$vn_occ_type_id = $va_authority_type_id_conv['occurrence_types'][$qr_ca05_rel_types->get('sub_type_id')];
				
				switch($qr_ca05_rel_types->get('table_num')) {
					case 32:	// occurrence on left
					case 49:
					case 125:
						$t_rel->set('sub_type_left_id', $vn_occ_type_id);
						$t_rel->set('sub_type_right_id', null);
						break;
					case 30:	// occurrence on right
					case 45:
					case 46:
						$t_rel->set('sub_type_left_id', null);
						$t_rel->set('sub_type_right_id', $vn_occ_type_id);
						break;
					default:
						$t_rel->set('sub_type_left_id', null);
						$t_rel->set('sub_type_right_id', null);
						break;
				}
				
				$t_rel->insert();
				
				$va_relationship_type_conv[$qr_ca05_rel_types->get('type_id')] = $t_rel->getPrimaryKey();
				
				$t_rel->addLabel(
					array(
						'typename' => $qr_ca05_rel_types->get('typename'), 'typename_reverse'=> $qr_ca05_rel_types->get('typename_reverse'),
						'description' => $qr_ca05_rel_types->get('description'), 'description_reverse'=> $qr_ca05_rel_types->get('description_reverse'),
					),
					$pn_locale_id, null, true
				);
			}

			# Add representation type list default entry

			if (!$t_list->load(array('list_code' => 'object_representation_types'))) { die("CONVERSION ERROR: no object_representation_type list defined\n"); }

			$t_item = $t_list->addItem(
				"default", true, true, null, null, "default", null, 4, 1, null
			);

			$t_item->addLabel(
				array(
					'name_singular' => "Default",
					'name_plural' => "Default"
				), $pn_locale_id, null, true
			);

			$vn_representation_type_id = $t_item->getPrimaryKey();
		
		// TODO: convert entity_sources, place_sources
		
		
	//
	// Convert data
	//
	
	# ----------------------------------------------------------------------------------------
	# Importing storage locations
	#
	print "CONVERTING storage locations\n";
	print "\t\tImporting locations...\n";
	$qr_ca05_storage_locations = $o_db->query("
		SELECT *
		FROM {$ps_ca5x_database}.storage_locations
	");

	$va_storage_location_id_conv = array();
	while($qr_ca05_storage_locations->nextRow()) {
		if (!$qr_ca05_storage_locations->get('parent_id')) { continue; }
		
		$t_loc = new ca_storage_locations();
		$t_loc->setMode(ACCESS_WRITE);
		$t_loc->set('parent_id', $t_loc->getHierarchyRootID());
		$t_loc->set('type_id', $va_authority_type_id_conv['storage_locations_types'][$qr_ca05_storage_locations->get('type_id')]);
		$t_loc->set('status', 4);
		
		$t_loc->addAttribute(array(
			'description' => $qr_ca05_storage_locations->get('description'),
			'locale_id' => $pn_locale_id
		), 'description');
		
		$t_loc->insert();
		
		$t_loc->addLabel(
			array(
				'name' => $qr_ca05_storage_locations->get('name')
			), $pn_locale_id, null, true
		);
		
		$va_storage_location_id_conv[$qr_ca05_storage_locations->get('location_id')] = $t_loc->getPrimaryKey();
	}

	// relink hierarchy
	print "\t\tRe-linking type hierarchy...\n";
	$t_loc = new ca_storage_locations();
	$qr_ca05_storage_locations->seek(0);
	while($qr_ca05_storage_locations->nextRow()) {
		$vn_id = $va_storage_location_id_conv[$qr_ca05_storage_locations->get('location_id')];
		if ($vn_parent_id = $va_storage_location_id_conv[$qr_ca05_storage_locations->get('parent_id')]) {
			if (!$t_loc->load($vn_id)) {
				print "\tCONVERSION ERROR: Couldn't find storage location [".$qr_ca05_storage_locations->get('name')."]\n";
			} else {
				$t_loc->setMode(ACCESS_WRITE);
				$t_loc->set('parent_id', $vn_parent_id);
				$t_loc->update();
				
				if ($t_loc->numErrors()) {
					print "\tCONVERSION ERROR: Couldn't place storage location [".$qr_ca05_storage_locations->get('name')."] into hierarchy: ".join('; ', $t_loc->getErrors())."\n";
				}
			}
		}
	}
	
	
	# ----------------------------------------------------------------------------------------
	# Importing vocabularies
	#
	print "IMPORTING VOCABULARY TERMS...\n";
	$t_list = new ca_lists();
	$vn_list_item_label_type_uf = $t_list->getItemIDFromList('list_item_label_types', 'uf');
	$vn_list_item_label_type_alt = $t_list->getItemIDFromList('list_item_label_types', 'alt');
	
	
	print "\t\tConverting terms and synonyms\n";
	$va_term_id_conv = array();
	foreach($va_voc_hierarchy_id_conv as $vn_05x_hier_id => $vn_06_hier_id) {
		$qr_old_terms = $o_db->query("
			SELECT * 
			FROM {$ps_ca5x_database}.voc_terms
			WHERE 
				vocabulary_id = ?
		", $vn_05x_hier_id);
		
		while($qr_old_terms->nextRow()) {
			$t_term = new ca_list_items();
			$t_term->setMode(ACCESS_WRITE);
			$t_term->set('list_id', $vn_06_hier_id);
			$t_term->set('idno', $qr_old_terms->get('idno'));
			$t_term->set('item_value', $qr_old_terms->get('idno'));
			$t_term->set('access', 1);
			$t_term->set('status', 4);
			
			$t_term->insert();
			
			if($t_term->numErrors()) {
				print "CONVERSION ERROR: Can't import voc term: ".join('; ', $t_term->getErrors())."\n";
				continue;
			}
			
			$t_term->addLabel(
				array(
					'name_singular' => $qr_old_terms->get('term'),
					'name_plural' => $qr_old_terms->get('term'),
					'description' => $qr_old_terms->get('scope_notes')
				), 
				$pn_locale_id, null, true
			);
			
			$qr_old_term_syns = $o_db->query("
				SELECT * 
				FROM {$ps_ca5x_database}.voc_term_synonyms
				WHERE 
					term_id = ?
			", $qr_old_terms->get('term_id'));
			
			while($qr_old_term_syns->nextRow()) {
				$t_term->addLabel(
				array(
						'name_singular' => $qr_old_term_syns->get('synonym'),
						'name_plural' => $qr_old_term_syns->get('synonym')
					), 
					$pn_locale_id, ($qr_old_term_syns->get('typecode') == 1) ? $vn_list_item_label_type_uf : $vn_list_item_label_type_alt, false
				);
			}
			
			$va_term_id_conv[$qr_old_terms->get('term_id')] = $t_term->getPrimaryKey();
		}
		
		print "\t\tlinking hierarchy\n";
		$qr_old_terms->seek(0);
		while($qr_old_terms->nextRow()) {
			$vn_old_term_id = $qr_old_terms->get('term_id');
			$vn_old_parent_id = $qr_old_terms->get('parent_id');
			
			$vn_term_id = $va_term_id_conv[$vn_old_term_id];
			$vn_parent_id = $va_term_id_conv[$vn_old_parent_id];
			
			if (!$vn_term_id || !$vn_parent_id) {
				print "\tSkipping ".print_r($qr_old_terms->getRow(), true)."\n\n"; 
				continue;
			}
			
			$t_term->load($vn_term_id);
			$t_term->setMode(ACCESS_WRITE);
			$t_term->set('parent_id', $vn_parent_id);
			$t_term->update();
			
			if($t_term->numErrors()) {
				print "CONVERSION ERROR: Can't link voc term into hierarchy: ".join('; ', $t_term->getErrors())."\n";
				continue;
			}
			
		}
	}
	
	# ----------------------------------------------------------------------------------------
	# Importing entities
	#
	$vn_entity_type_not_specified = $t_list->getItemIDFromList('entity_types', 'not_specified');
	$qr_old_entities = $o_db->query("
		SELECT * FROM {$ps_ca5x_database}.entities
	");
	
	print "CONVERTING ENTITY AUTHORITY...\n";
	$t_entity = new ca_entities();
	$t_entity->setMode(ACCESS_WRITE);
	while($qr_old_entities->nextRow()) {
		$vn_old_entity_id =  $qr_old_entities->get('entity_id');
		
		if (!($vn_type_id = $va_authority_type_id_conv['entity_types'][$qr_old_entities->get('type_id')])) {
			$vn_type_id = $vn_entity_type_not_specified;
		}
		
		$t_entity->set('idno', $qr_old_entities->get('idno'));
		$t_entity->set('locale_id', $pn_locale_id);
		$t_entity->set('type_id', $vn_type_id);
		$t_entity->set('access', 1);
		$t_entity->set('status', 4);
		
		if (trim($qr_old_entities->get('scope_notes'))) {
			$t_entity->addAttribute(array(
				'biography' => $qr_old_entities->get('scope_notes'),
				'locale_id' => $pn_locale_id
			), 'biography');
		}
		if (trim($qr_old_entities->get('source_notes'))) {
			$t_entity->addAttribute(array(
				'biography_source' => $qr_old_entities->get('source_notes'),
				'locale_id' => $pn_locale_id
			), 'biography_source');
		}
		
		// TODO: convert other entity fields
		
		$t_entity->insert();
		
		if($t_entity->numErrors()) {
			print "CONVERSION ERROR: Can't import entity: ".join('; ', $t_entity->getErrors())."\n";
			continue;
		}
		$t_entity->addLabel(array(
			'forename' => $qr_old_entities->get('forename'),
			'middlename' => $qr_old_entities->get('middlename'),
			'other_forenames' => $qr_old_entities->get('other_forenames'),
			'surname' => $qr_old_entities->get('surname'),
			'suffix' => $qr_old_entities->get('suffix'),
			'surname' => $qr_old_entities->get('surname'),
			'prefix' => $qr_old_entities->get('salutation'),
		), $pn_locale_id, null, true);
		$va_entity_id_conv[$vn_old_entity_id] = $t_entity->getPrimaryKey();
	}
	
	
	# ----------------------------------------------------------------------------------------
	# Importing collections
	#
	
	$vn_collection_type_not_specified = $t_list->getItemIDFromList('collection_types', 'not_specified');
	
	$qr_old_collections = $o_db->query("
		SELECT * FROM {$ps_ca5x_database}.collections
	");
	
	print "CONVERTING COLLECTIONS AUTHORITY...\n";
	$t_collection = new ca_collections();
	$t_collection->setMode(ACCESS_WRITE);
	while($qr_old_collections->nextRow()) {
		$vn_old_collection_id =  $qr_old_collections->get('collection_id');
		
		if (!($vn_type_id = $va_authority_type_id_conv['collection_types'][$qr_old_collections->get('type_id')])) {
			$vn_type_id = $vn_collection_type_not_specified;
		}
		
		$t_collection->set('idno', $qr_old_collections->get('idno'));
		$t_collection->set('locale_id', $pn_locale_id);
		$t_collection->set('type_id',  $vn_type_id);
		$t_collection->set('access', 1);
		$t_collection->set('status', 4);
		
		if (trim($qr_old_collections->get('description'))) {
			$t_collection->addAttribute(array(
				'description' => $qr_old_collections->get('description'),
				'locale_id' => $pn_locale_id
			), 'description');
		}
		
		$t_collection->insert();
		
		if($t_collection->numErrors()) {
			print "CONVERSION ERROR: Can't import collection: ".join('; ', $t_collection->getErrors())."\n";
			continue;
		}
		$t_collection->addLabel(array(
			'name' => $qr_old_collections->get('name'),
		), $pn_locale_id, null, true);
		$va_collection_id_conv[$vn_old_collection_id] = $t_collection->getPrimaryKey();
	}
	
	
	# ----------------------------------------------------------------------------------------
	# Importing places
	#
	
	
	$vn_place_type_not_specified = $t_list->getItemIDFromList('place_types', 'not_specified');
	
	print "CONVERTING PLACE AUTHORITY...\n";
	print "\tconverting georeferences...\n";
	$qr_georefs = $o_db->query("
		SELECT * FROM {$ps_ca5x_database}.place_georeferences
	");
	$va_georefs = array();
	while($qr_georefs->nextRow()) {
		$va_georefs[$qr_georefs->get('place_id')] = $qr_georefs->getRow();
		$va_georefs[$qr_georefs->get('place_id')]['coordinates'] = unserialize(base64_decode($va_georefs[$qr_georefs->get('place_id')]['coordinates']));
	}
	
	$va_place_id_conv = array();
	foreach($va_place_hierarchy_id_conv as $vn_05x_hier_id => $vn_06_hier_id) {
		$qr_old_places = $o_db->query("
			SELECT * FROM {$ps_ca5x_database}.place_names WHERE place_hierarchy_id = ?;
		", $vn_05x_hier_id);
	
		
		print "\tconverting places...\n";
		$t_place = new ca_places();
		$t_place->setMode(ACCESS_WRITE);
		while($qr_old_places->nextRow()) {
			$vn_old_place_id =  $qr_old_places->get('place_id');
			
			if(!($vn_type_id = $va_authority_type_id_conv['place_types'][$qr_old_places->get('type_id')])) {
				$vn_type_id = $vn_place_type_not_specified;
			}
			
			$t_place->set('idno', $qr_old_places->get('idno'));
			$t_place->set('locale_id', $pn_locale_id);
			$t_place->set('type_id', $vn_type_id);
			$t_place->set('hierarchy_id', $vn_06_hier_id);
			
			if ($va_georefs[$vn_old_place_id] && is_array($va_georefs[$vn_old_place_id]['coordinates']) && sizeof($va_georefs[$vn_old_place_id]['coordinates'])) {		
				$va_coords = array();
				foreach($va_georefs[$vn_old_place_id]['coordinates'] as $va_coord) {
					$va_coords[] = $va_coord['latitude'].','.$va_coord['longitude'];
				}
				$t_place->addAttribute(array(
					'georeference' => '['.join(';',$va_coords).']',
					'locale_id' => $pn_locale_id
				), 'georeference');
			}
			
			$t_place->insert();
			
			if($t_place->numErrors()) {
				print "CONVERSION ERROR: Can't import place: ".join('; ', $t_place->getErrors())."\n";
				continue;
			}
			$t_place->addLabel(array('name' => $qr_old_places->get('name')), $pn_locale_id, null, true);
			$va_place_id_conv[$vn_old_place_id] = $t_place->getPrimaryKey();
		}
	}
	
	print "\tlinking hierarchy...\n";
	$qr_old_places->seek(0);
	while($qr_old_places->nextRow()) {
		$vn_old_place_id = $qr_old_places->get('place_id');
		$vn_old_parent_id = $qr_old_places->get('parent_id');
		
		$vn_place_id = $va_place_id_conv[$vn_old_place_id];
		$vn_parent_id = $va_place_id_conv[$vn_old_parent_id];
		
		if (!$vn_place_id || !$vn_parent_id) {
			print "Skipping ".print_r($qr_old_places->getRow(), true)."\n\n"; 
			continue;
		}
		
		$t_place->load($vn_place_id);
		$t_place->setMode(ACCESS_WRITE);
		$t_place->set('parent_id', $vn_parent_id);
		$t_place->update();
		
		if($t_place->numErrors()) {
			print_r($t_place->getErrors());
			print "CONVERSION ERROR: Can't link place into hierarchy: ".join('; ', $t_place->getErrors())."\n";
			continue;
		}
		
	}
	
	# ----------------------------------------------------------------------------------------
	# Importing occurrences
	#
	
	$qr_old_occurrences = $o_db->query("
		SELECT * FROM {$ps_ca5x_database}.occurrences
	");
	
	print "IMPORTING OCCURRENCES...\n";
	$t_occurrence = new ca_occurrences();
	$t_occurrence->setMode(ACCESS_WRITE);
	while($qr_old_occurrences->nextRow()) {
		$vn_old_occurrence_id =  $qr_old_occurrences->get('occurrence_id');
		
		$t_occurrence->set('idno', $qr_old_occurrences->get('idno'));
		$t_occurrence->set('locale_id', $pn_locale_id);
		$t_occurrence->set('type_id',  $va_authority_type_id_conv['occurrence_types'][$qr_old_occurrences->get('type_id')]);
		$t_occurrence->set('access', 1);
		$t_occurrence->set('status', 4);
		
		if (trim($qr_old_occurrences->get('description'))) {
			$t_occurrence->addAttribute(array(
				'description' => $qr_old_occurrences->get('description'),
				'locale_id' => $pn_locale_id
			), 'description');
		}
		
		$t_occurrence->insert();
		
		if($t_occurrence->numErrors()) {
			print "CONVERSION ERROR: Can't import occurrence: ".join('; ', $t_occurrence->getErrors())."\n";
			continue;
		}
		$t_occurrence->addLabel(array(
			'name' => $qr_old_occurrences->get('name'),
		), $pn_locale_id, null, true);
		$va_occurrence_id_conv[$vn_old_occurrence_id] = $t_occurrence->getPrimaryKey();
	}
	
	
	# ----------------------------------------------------------------------------------------
	# Importing lots
	#
	# TODO
	
	# ----------------------------------------------------------------------------------------
	# Importing objects
	#
	$qr_old_objects = $o_db->query("
		SELECT * FROM {$ps_ca5x_database}.objects
	");
	print "CONVERTING OBJECTS\n";
	
	while($qr_old_objects->nextRow()) {
		print "\tconverting object: ".$qr_old_objects->get('title')." [".$qr_old_objects->get('admin_idno')."]\n";
		if (!($vn_type_id = $va_object_type_id_conv[$qr_old_objects->get('phys_type_id')])) {
			print "\tCONVERSION ERROR: type_id [".$qr_old_objects->get('phys_type_id')."] is invalid for object [".$qr_old_objects->get('object_id')."]\n";
			continue;
		}
		
		if (!($vn_source_id = $va_object_source_id_conv[$qr_old_objects->get('admin_source_id')])) {
			//print "\tERROR: source [{$vs_source}] is invalid for object [".$qr_old_objects->get('object_id')."]\n";
			//continue;
			$vn_source_id = null;
		}
		
		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('status', 4);
		$t_object->set('access', 1);
		
		
		$t_object->set('type_id', $vn_type_id);
		$t_object->set('source_id', $vn_source_id);
		$t_object->set('locale_id', $pn_locale_id);
		$t_object->set('extent', $qr_old_objects->get('admin_extent'));
		$t_object->set('extent_units', $qr_old_objects->get('admin_extent_units'));
		
		$t_object->set('idno', $qr_old_objects->get('admin_idno'));
		
		$t_object->addAttribute(array(
			'description' => $qr_old_objects->get('content_description'),
			'locale_id' => $pn_locale_id
		), 'description');
		
		$t_object->addAttribute(array(
			'description_source' => $qr_old_objects->get('content_remarks'),
			'locale_id' => $pn_locale_id
		), 'description_source');
		
		$t_object->addAttribute(array(
			'physical_description' => $qr_old_objects->get('phys_description'),
			'locale_id' => $pn_locale_id
		), 'physical_description');
		
		$t_object->addAttribute(array(
			'physical_description_source' => $qr_old_objects->get('phys_remarks'),
			'locale_id' => $pn_locale_id
		), 'physical_description_source');
		
		$t_object->addAttribute(array(
			'dimensions_text' => $qr_old_objects->get('phys_dimensions'),
			'locale_id' => $pn_locale_id
		), 'dimensions_text');
		
		$t_object->addAttribute(array(
			'caption' => $qr_old_objects->get('admin_label_caption'),
			'locale_id' => $pn_locale_id
		), 'caption');
		
		if ($vs_tmp = $qr_old_objects->get('admin_accession_date')) {
			$t_object->addAttribute(array(
				'accession_date' => $vs_tmp,
				'locale_id' => $pn_locale_id
			), 'accession_date');
		}
		if ($vs_tmp = $qr_old_objects->get('admin_deaccession_date')) {
			$t_object->addAttribute(array(
				'deaccession_date' => $vs_tmp,
				'locale_id' => $pn_locale_id
			), 'deaccession_date');
		}
		$t_object->addAttribute(array(
			'deaccession_notes' => $qr_old_objects->get('admin_deaccession_notes'),
			'locale_id' => $pn_locale_id
		), 'deaccession_notes');
		
		$t_object->addAttribute(array(
			'deaccession_notes' => $qr_old_objects->get('admin_condition'),
			'locale_id' => $pn_locale_id
		), 'condition');
		
		$t_object->addAttribute(array(
			'internal_notes' => $qr_old_objects->get('admin_remarks'),
			'locale_id' => $pn_locale_id
		), 'internal_notes');
		
		$t_object->addAttribute(array(
			'artifact_needs' => $qr_old_objects->get('admin_artifact_needs'),
			'locale_id' => $pn_locale_id
		), 'artifact_needs');
		
		$t_object->addAttribute(array(
			'custodial_notes' => $qr_old_objects->get('admin_custodial_notes'),
			'locale_id' => $pn_locale_id
		), 'custodial_notes');
		
		$t_object->addAttribute(array(
			'special_handling' => $qr_old_objects->get('admin_special_handling'),
			'locale_id' => $pn_locale_id
		), 'special_handling');
		
		if ($qr_old_objects->get('admin_valuation')) {
			$t_object->addAttribute(array(
				'valuation' => $qr_old_objects->get('admin_valuation'),
				'locale_id' => $pn_locale_id
			), 'valuation');
		}
		
		$t_object->addAttribute(array(
			'valuation_notes' => $qr_old_objects->get('admin_valuation_notes'),
			'locale_id' => $pn_locale_id
		), 'valuation_notes');
		
		$t_object->addAttribute(array(
			'historical_notes' => $qr_old_objects->get('admin_historical_notes'),
			'locale_id' => $pn_locale_id
		), 'historical_notes');
		
		$t_object->addAttribute(array(
			'historical_notes_source' => $qr_old_objects->get('admin_historical_source_notes'),
			'locale_id' => $pn_locale_id
		), 'historical_notes_source');
		
		$t_object->addAttribute(array(
			'provenance' => $qr_old_objects->get('admin_provenance'),
			'locale_id' => $pn_locale_id
		), 'provenance');
		
		
		# Copy attributes
		$qr_attrs = $o_db->query("
			SELECT * FROM {$ps_ca5x_database}.weblib_md_attributes
			WHERE
				row_id = ? AND table_num = 1
		", $qr_old_objects->get('object_id'));
		while($qr_attrs->nextRow()) {
			if ($va_element_id_conv[$qr_attrs->get('element_id')]) {
				$t_object->addAttribute(array(
					$va_element_id_conv[$qr_attrs->get('element_id')] => $qr_attrs->get('value_text'),
					'locale_id' => $pn_locale_id
				), $va_element_id_conv[$qr_attrs->get('element_id')]);
			}
		}
		
		
		# Add object_numbers to alternate_idnos  attribute
		$qr_numbers = $o_db->query("
			SELECT * FROM {$ps_ca5x_database}.object_numbers
			WHERE
				object_id = ? AND type_id = 4
		", $qr_old_objects->get('object_id')); 
		while($qr_numbers->nextRow()) {
			$t_object->addAttribute(array(
				'alternate_idnos' => $qr_numbers->get('number'),
				'locale_id' => $pn_locale_id
			), 'alternate_idnos');
		}
		
		$t_object->insert();
		
		if ($t_object->numErrors()) {
			print "\tCONVERSION ERROR: couldn't insert object: ".join('; ', $t_object->getErrors())."\n";
			continue;
		}
		
		$va_object_id_conv[$qr_old_objects->get('object_id')] = $t_object->getPrimaryKey();
		
		$t_object->addLabel(
			array(
				'name' => $qr_old_objects->get('title')
			), $pn_locale_id, null, true		
		);
		
		if ($t_object->numErrors()) {
			print "\tCONVERSION ERROR: error adding label to object: ".join('; ', $t_object->getErrors())."\n";
			continue;
		}
		
		# Link storage locations to objects
		$qr_links = $o_db->query("
			SELECT * FROM 
			{$ps_ca5x_database}.objects_x_storage_locations
			WHERE
				object_id = ?
		", $qr_old_objects->get('object_id'));
		
		while($qr_links->nextRow()){ 
			if (!($vn_location_id = $va_storage_location_id_conv[$qr_links->get('location_id')])) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to STORAGE LOCATION [".$va_storage_location_id_conv[$qr_links->get('location_id')]."]\n";
				continue;
			}
			
			$t_event = new ca_object_events();
			$t_event->setMode(ACCESS_WRITE);
			$t_event->set('type_id', $vn_object_event_movement_id);
			
			if ($qr_links->get('planned_date')) {
				$o_tep->setHistoricTimestamps($qr_links->get('planned_sdate'), $qr_links->get('planned_edate'));
				$t_event->set('planned_datetime', $o_tep->getText());
			}
			if ($vn_tmp = $qr_links->get('executed_on')) {
				$o_tep->setUnixTimestamps($vn_tmp, $vn_tmp);
				$t_event->set('event_datetime', $o_tep->getText());
			}
			
			$t_event->addAttribute(array(
				'object_movement_requested_by' => $qr_links->get('requested_by'),
				'locale_id' => $pn_locale_id
			), 'object_movement_requested_by');
			
			$t_event->addAttribute(array(
				'object_movement_authorized_by' => $qr_links->get('authorized_by'),
				'locale_id' => $pn_locale_id
			), 'object_movement_authorized_by');
			
			$t_event->addAttribute(array(
				'object_movement_executed_by' => $qr_links->get('executed_by'),
				'locale_id' => $pn_locale_id
			), 'object_movement_executed_by');
		
			$t_event->addAttribute(array(
				'object_movement_notes' => $qr_links->get('notes'),
				'locale_id' => $pn_locale_id
			), 'object_movement_notes');
			
			$t_event->insert();
			
			if ($t_event->numErrors()) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to STORAGE LOCATION [".$va_storage_location_id_conv[$qr_links->get('location_id')]."]: ".join('; ', $t_object->getErrors())."\n";
				continue;
			}
			
			$t_object->addRelationship('ca_storage_locations', $vn_location, $vn_storage_location_destination_relationship_type_id);
		}
		
		
		# Link places to objects
		$qr_links = $o_db->query("
			SELECT * FROM 
			{$ps_ca5x_database}.objects_x_places
			WHERE
				object_id = ?
		", $qr_old_objects->get('object_id'));
		
		while($qr_links->nextRow()){ 
			if (!($vn_place_id = $va_place_id_conv[$qr_links->get('place_id')])) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to PLACE [".$va_place_id_conv[$qr_links->get('place_id')]."]\n";
				continue;
			}
			
			if (!($vn_type_id = $va_relationship_type_conv[$qr_links->get('type_id')])) {
				print "\t\tCONVERSION ERROR: type_id for ".$qr_links->get('type_id')." doesn't exist\n";
				continue;
			}
			
			$t_object->addRelationship('ca_places', $vn_place_id, $vn_type_id);
			
			if ($t_object->numErrors()) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to PLACE [".$va_place_id_conv[$qr_links->get('place_id')]."]: ".join('; ', $t_object->getErrors())."\n";
			}
		}
		
		
		# Link entities to objects
		$qr_links = $o_db->query("
			SELECT * FROM 
			{$ps_ca5x_database}.objects_x_entities
			WHERE
				object_id = ?
		", $qr_old_objects->get('object_id'));
		
		while($qr_links->nextRow()){ 
			if (!($vn_entity_id = $va_entity_id_conv[$qr_links->get('entity_id')])) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to ENTITY [".$va_entity_id_conv[$qr_links->get('entity_id')]."]\n";
				continue;
			}
			if (!($vn_type_id = $va_relationship_type_conv[$qr_links->get('type_id')])) {
				print "\t\tCONVERSION ERROR: type_id for ".$qr_links->get('type_id')." doesn't exist\n";
				continue;
			}
			
			$t_object->addRelationship('ca_entities', $vn_entity_id, $vn_type_id);
			
			if ($t_object->numErrors()) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to ENTITY [".$va_entity_id_conv[$qr_links->get('entity_id')]."]: ".join('; ', $t_object->getErrors())."\n";
			}
		}
		
		# Link collections to objects
		$qr_links = $o_db->query("
			SELECT * FROM 
			{$ps_ca5x_database}.objects_x_collections
			WHERE
				object_id = ?
		", $qr_old_objects->get('object_id'));
		
		while($qr_links->nextRow()){ 
			if (!($vn_collection_id = $va_collection_id_conv[$qr_links->get('collection_id')])) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to COLLECTION [".$va_collection_id_conv[$qr_links->get('collection_id')]."]\n";
				continue;
			}			
			
			if (!($vn_type_id = $va_relationship_type_conv[$qr_links->get('type_id')])) {
				print "\t\tCONVERSION ERROR: type_id for ".$qr_links->get('type_id')." doesn't exist\n";
				continue;
			}
			
			$t_object->addRelationship('ca_collections', $vn_collection_id, $vn_type_id);
			
			if ($t_object->numErrors()) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to COLLECTION [".$va_collection_id_conv[$qr_links->get('collection_id')]."]: ".join('; ', $t_object->getErrors())."\n";
			}
		}
		
		# Link vocabulary terms to objects
		$qr_links = $o_db->query("
			SELECT * FROM 
			{$ps_ca5x_database}.objects_x_voc_terms
			WHERE
				object_id = ?
		", $qr_old_objects->get('object_id'));
		
		while($qr_links->nextRow()){ 
			if (!($vn_item_id = $va_term_id_conv[$qr_links->get('term_id')])) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to VOC TERM [".$va_term_id_conv[$qr_links->get('term_id')]."]\n";
				continue;
			}
			if (!($vn_type_id = $va_relationship_type_conv[$qr_links->get('type_id')])) {
				print "\t\tCONVERSION ERROR: type_id for ".$qr_links->get('type_id')." doesn't exist\n";
				continue;
			}
			
			$t_object->addRelationship('ca_list_items', $vn_item_id, $vn_type_id);
			
			if ($t_object->numErrors()) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to VOC TERM [".$va_term_id_conv[$qr_links->get('term_id')]."]: ".join('; ', $t_object->getErrors())."\n";
			}
		}
	
		
		# Add representations
		$qr_reps = $o_db->query("
			SELECT * FROM 
			{$ps_ca5x_database}.object_representations
			WHERE
				object_id = ?
		", $qr_old_objects->get('object_id'));
		while($qr_reps->nextRow()){ 
			$vs_path = $qr_reps->getMediaUrl('media', 'original');
			$vs_path = str_replace($vs_discard_path, '', $vs_path);
			$vs_path = $vs_media_path.$vs_path;
			print "TRYING REP: $vs_path\n";
			$vn_link_id = $t_object->addRepresentation($vs_path, $vn_representation_type_id, $pn_locale_id, 4, 1, 1, null, null);
			
			if ($t_object->numErrors()) {
				print "\tCONVERSION ERROR: ERROR LINKING OBJECT [".$qr_old_objects->get('object_id')."] to MEDIA [".$vs_path."]: ".join('; ', $t_object->getErrors())."\n";
				continue;
			}
			
			# get clips
			if (preg_match("!mp3$!i", $vs_path)) {
				$t_oxor = new ca_objects_x_object_representations($vn_link_id);
				$t_rep = new ca_object_representations($t_oxor->get('representation_id'));
				
				$qr_clips = $o_db->query("
					SELECT * FROM 
					{$ps_ca5x_database}.object_clips
					WHERE
						object_id = ?
				", $qr_old_objects->get('object_id'));
				
				while($qr_clips->nextRow()) {
					$vn_annotation_id = $t_rep->addAnnotation($pn_locale_id, 1, array(
						'startTimecode' => $qr_clips->get('start_time'),
						'endTimecode' => $qr_clips->get('end_time')
					), 4, 1);
					
					$t_annotation = new ca_representation_annotations($vn_annotation_id);
					$t_annotation->addLabel(
						array('name' => $qr_clips->get('title')),
						$pn_locale_id,
						null,
						true
					);
				}
			}
		}
	}
	
	# Relink objects into hierarchies
	print "\tlinking object hierarachies...\n";
	$qr_old_objects->seek(0);
	$t_object = new ca_objects();
	while($qr_old_objects->nextRow()) {
		$vn_old_object_id = $qr_old_objects->get('object_id');
		$vn_old_parent_id = $qr_old_objects->get('parent_id');
		
		$vn_object_id = $va_object_id_conv[$vn_old_object_id];
		$vn_parent_id = $va_object_id_conv[$vn_old_parent_id];
		
		if (!$vn_object_id || !$vn_parent_id) {
			//print "Skipping ".print_r($qr_old_objects->getRow(), true)."\n\n"; 
			continue;
		}
		
		$t_object->load($vn_object_id);
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('parent_id', $vn_parent_id);
		$t_object->update();
		
		if($t_object->numErrors()) {
			print_r($t_object->getErrors());
			continue;
		}
		
	}

	
	# ----------------------------------------------------------------------------------------
	# Add UI elements
	
	# add metadata elements to object "attribute" tab
	$t_ui = new ca_editor_uis();
	if ($t_ui->load(array('editor_type' => 57))) {
		$t_ui_screen = new ca_editor_ui_screens();
		if ($t_ui_screen->load(array('ui_id' => $t_ui->getPrimaryKey(), 'idno' => 'attributes'))) {
			foreach($va_element_list as $vs_element) {
				$t_ui_screen->addBundlePlacement('ca_attribute_'.$vs_element, 'ca_attribute_'.$vs_element, array());
			}
		}
	}
	
	
	# ----------------------------------------------------------------------------------------
	# Wir sind am Ende!
	print "CONVERSION COMPLETE!\n";	
?>