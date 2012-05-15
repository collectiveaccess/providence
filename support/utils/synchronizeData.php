<?php
/** ---------------------------------------------------------------------
 * support/utils/synchronizeData.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */
   require_once("../../setup.php");
   require_once(__CA_LIB_DIR__.'/core/Configuration.php');
   require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
   require_once(__CA_LIB_DIR__.'/ca/Service/RestClient.php');
   require_once(__CA_MODELS_DIR__.'/ca_lists.php');
   require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
   
   	// TODO: We need better logging. Printing out to console doesn't cut it.
   
   	$va_processed_records = array();
   
	$o_config = Configuration::load(__CA_CONF_DIR__."/synchronization.conf");
	$o_dm = Datamodel::load();
	
	$va_sources = $o_config->getAssoc('sources');
	foreach($va_sources as $vn_i => $va_source) {
	
		$vs_base_url 						= $va_source['baseUrl'];
		$vs_search_expression 		= $va_source['searchExpression'];
		$vs_username 						= $va_source['username'];
		$vs_password 						= $va_source['password'];
		$vs_table 								= $va_source['table'];
		if(!$vs_base_url){
			print "ERROR: You must pass a valid CollectiveAccess\n";
			exit(-1);
		}
		
		if (!($t_instance = $o_dm->getInstanceByTableName($vs_table, true))) {
			die("Invalid table '{$vs_table}'\n");
		}
		
		
		//
		// Set up HTTP client for REST calls
		//
		$o_client = new RestClient($vs_base_url."/service.php/search/Search/rest");
		
		
		//
		// Authenticate
		//
		$o_res = $o_client->auth($vs_username, $vs_password)->get();
			if (!$o_res->isSuccess()) {
				die("Could not authenticate to service for authentication\n");
			}
		
		//
		// Get userID
		//
		$o_res = $o_client->getUserID()->get();
			if (!$o_res->isSuccess()) {
				die("Could not fetch user_id\n");
			}
		
		// TODO: Add from/until support	
		$o_res = $o_client->queryRest($vs_table, $vs_search_expression,  array("ca_objects.status" => array("convertCodesToDisplayText" => 1)))->get();
		
		// TODO: check for errors here
		
		
		//parse results
			$vs_pk = $t_instance->primaryKey();
			$va_items = array();
			$o_xml = $o_res->CaSearchResult;
			foreach($o_xml->children() as $vn_i => $o_item) {
				$o_attributes = $o_item->attributes();
				$vn_id = (int)$o_attributes->{$vs_pk};
				
				$vs_idno = (string)$o_item->idno;
				$vs_label = (string)$o_item->ca_labels->ca_label[0];
				
				$va_items[$vs_table.'/'.$vn_id] = array(
					'table' => $vs_table,
					'id' => $vn_id,
					'idno' => $vs_idno
				);
			}
			
			//print_R($va_items);
		
			// Ok... now fetch and import each
			$o_client->setUri($vs_base_url."/service.php/iteminfo/ItemInfo/rest");
			fetchAndImport($va_items, $o_client, $va_source, array());
	}
	# ------------------------------------------------------------------------------------------
	// TODO: Add from/until support	
	function fetchAndImport($pa_item_queue, $po_client, $pa_config, $pa_tables) {
		if (!is_array($pa_tables)) { $pa_tables = array(); }
		global $va_processed_records;
		
		$t_rel_type = new ca_relationship_types();
		
		$vs_base_url = $pa_config['baseUrl'];
		$o_dm = Datamodel::load();
		$t_locale = new ca_locales();
		$t_list = new ca_lists();
		$vn_nhf_inst_id = $t_list->getItemIDFromList('CLIR2_Inst', 'nhf');
		
		$pn_rep_type_id = $t_list->getItemIDFromList('object_representation_types', 'front');
		
		foreach($pa_item_queue as $vn_i => $va_item) {
			$vs_table = $va_item['table'];
			$va_import_relationships_from = $pa_config['importRelatedFor'][$va_item['table']];
			
			$vn_id = $va_item['id'];
			if (!$vn_id) { print "SKIP CAUSE NO ID\n"; continue; }
			if(isset($va_processed_records[$vs_table.'/'.$vn_id])) { continue; }
			
			$vs_idno = trim((string)$va_item['idno']);
			
			$o_xml = $po_client->getItem($vs_table, $vn_id)->get();
			$o_item = $o_xml->getItem;
			
			$t_instance = $o_dm->getInstanceByTableName($vs_table, false);
			$t_instance_label = $t_instance->getLabelTableInstance();
			// Look for existing record
			$vb_skip = false;
			$vb_update = false;
			$vs_label_fld = $t_instance->getLabelDisplayField();
			$vs_label = (string)$o_item->preferred_labels->en_US->{$vs_label_fld};
			print "PROCESSING [{$vs_table}] {$vs_label} [{$vs_idno}]\n";
			
			if (
				($vs_idno && $t_instance->load(array('idno' => $vs_idno)))
				||
				($t_instance_label->load(array($vs_label_fld => $va_item[$vs_label])))
			) {
				if ($t_instance->hasField('deleted') && ($t_instance->get('deleted') == 1)) { 
					$t_instance->set('deleted', 0);
				}
				
				print "UPDATE [{$vs_idno}] for {$vs_table} 'Cause it already exists\n";
				//$vb_skip = true;
				if (!$t_instance->getPrimaryKey()) {
					$t_instance->load($t_instance_label->get($t_instance->primaryKey()));
				}
				if (!$t_instance->getPrimaryKey()) {
					$vb_skip = true;
					print "[ERROR] Could not load instance for [{$vs_idno}]\n";
				}
				$vb_update = true;
				$t_instance->setMode(ACCESS_WRITE);
				
				// Clear labels
				
				$t_instance->removeAllLabels();
				if ($t_instance->numErrors()) {
					print "[ERROR] Could not remove labels for updating: ".join("; ", $t_instance->getErrors())."\n";
				}
				
				// Clear attributes
				$t_instance->removeAttributes(null, array('dontCheckMinMax' => true));
				if ($t_instance->numErrors()) {
					print "[ERROR] Could not remove attributes for updating: ".join("; ", $t_instance->getErrors())."\n";
				}
				
				// Clear relationships
				if (is_array($va_import_relationships_from)) {
					foreach($va_import_relationships_from as $vs_rel_table => $va_table_info) {
						$t_instance->removeRelationships($vs_rel_table);
						if ($t_instance->numErrors()) {
							print "[ERROR] Could not remove {$vs_rel_table} relationships for updating: ".join("; ", $t_instance->getErrors())."\n";
						}
					}
				}
				$t_instance->update();
				if ($t_instance->numErrors()) {
					print "[ERROR] Could not clear record for updating: ".join("; ", $t_instance->getErrors())."\n";
				}
				
			}
			
			// create new one
			if (!$vb_update) { $t_instance->clear(); }
			$t_instance->setMode(ACCESS_WRITE);
			
			// add intrinsics
			switch($vs_table) {
				case 'ca_collections':
					$va_intrinsics = array('status', 'access', 'idno');
					break;
				case 'ca_occurrences':
					$va_intrinsics = array('status', 'access', 'idno');
					break;
				case 'ca_objects':
					$va_intrinsics = array('status', 'access', 'source_id', 'idno');
					break;
				case 'ca_entities':
					$va_intrinsics = array('status', 'access', 'lifespan', 'source_id', 'idno');
					break;
				case 'ca_object_lots':
					$va_intrinsics = array('status', 'access', 'idno_stub');
					break;
				default:
					$va_intrinsics = array('status', 'access', 'idno');
					break;
			}
			
			// TODO: Need to properly handle foreign-key intrinsics when the item they point to doesn't exist
			// eg. source_id fields, various ca_objects and ca_object_lots intrinsics, etc.
			if ($vs_table == 'ca_list_items' ) { 
				// does list exist?
				$vs_list_code = (string)$o_item->{'list_code'};
				$t_list = new ca_lists();
				if (!$t_list->load(array('list_code' => $vs_list_code))) {
					// create list
					$t_list->setMode(ACCESS_WRITE);
					
					// TODO: should we bother to replicate the is_hierarchical, use_as_vocabulary and default_sort settings via a service?
					// For now just set reasonable values
					$t_list->set('list_code', $vs_list_code);
					$t_list->set('is_hierarchical', 1);
					$t_list->set('use_as_vocabulary', 1);
					$t_list->set('default_sort', 0);
					$t_list->insert();
					
					if ($t_list->numErrors()) {
						print "[ERROR] Could not insert new list '{$vs_list_code}': ".join('; ', $t_list->getErrors())."\n";
					} else {
						$t_list->addLabel(array('name' => $vs_list_code), $pn_locale_id, null, true);
						if ($t_list->numErrors()) {
							print "[ERROR] Could not add label to new list '{$vs_list_code}': ".join('; ', $t_list->getErrors())."\n";
						}
					}
				}
				$t_instance->set('list_id', $t_list->getPrimaryKey());
			}
			foreach($va_intrinsics as $vs_f) {
				$t_instance->set($vs_f, $o_item->{$vs_f});
			}
			
			
			if (!$vb_update) {
				$vn_type_id = $t_instance->getTypeIDForCode((string)$o_item->type_id);
				
				if (!$vn_type_id) { print "NO TYPE FOR $vs_table/".$o_item->type_id."\n"; }
				$t_instance->set('type_id', $vn_type_id);
				
				
				// TODO: add hook onBeforeInsert()
				$t_instance->insert();
				// TODO: add hook onInsert()
				
				if ($t_instance->numErrors()) {
					print "[ERROR] Could not insert record: ".join('; ', $t_instance->getErrors())."\n";
				}
			}
			
			// add attributes
			$va_codes = $t_instance->getApplicableElementCodes();
			foreach($va_codes as $vs_code) {
				$t_element = $t_instance->_getElementInstance($vs_code);
				
				switch($t_element->get('datatype')) {
					case 0:		// container
						$va_elements = $t_element->getElementsInSet();
						
						$o_attr = $o_item->{'ca_attribute_'.$vs_code};
						foreach($o_attr as $va_tag => $o_tags) {
							foreach($o_tags as $vs_locale => $o_values) {
								if (!($vn_locale_id = $t_locale->localeCodeToID($vs_locale))) { $vn_locale_id = null; }
								$va_container_data = array('locale_id' => $vn_locale_id);
								foreach($o_values as $o_value) {
								
									foreach($va_elements as $vn_i => $va_element_info) {
										if ($va_element_info['datatype'] == 0) { continue; }	
								
										if ($vs_value = trim((string)$o_value->{$va_element_info['element_code']})) {
											switch($va_element_info['datatype']) {
												case 3:	//list
													$va_tmp = explode(":", $vs_value);		//<item_id>:<item_idno>
													//print "CONTAINER LIST CODE=".$va_tmp[1]."/$vs_value/".$va_element_info['list_id']."\n";
													$va_container_data[$va_element_info['element_code']] = $t_list->getItemIDFromList($va_element_info['list_id'], $va_tmp[1]);
													break;
												default:
													$va_container_data[$va_element_info['element_code']] = $vs_value;
													break;
											}
										}
									}
									
									$t_instance->replaceAttribute(
											$va_container_data,
											$vs_code);
								}
							}
						}
						break;
					case 3:		// list
						$o_attr = $o_item->{'ca_attribute_'.$vs_code};
						foreach($o_attr as $va_tag => $o_tags) {
							foreach($o_tags as $vs_locale => $o_values) {
								if (!($vn_locale_id = $t_locale->localeCodeToID($vs_locale))) { $vn_locale_id = null; }
								foreach($o_values as $o_value) {
									if ($vs_value = trim((string)$o_value->{$vs_code})) {
										$va_tmp = explode(":", $vs_value);		//<item_id>:<item_idno>
										
										// TODO: create lists and list items if they don't already exist
										if ($vn_item_id = $t_list->getItemIDFromList($t_element->get('list_id'), $va_tmp[1])) {
											$t_instance->replaceAttribute(
												array(
													$vs_code => $vn_item_id,
													'locale_id' => $vn_locale_id
												),
												$vs_code);
										}
									}
								}
							}
						}
						break;
					case 15:	// File
					case 16:	// Media						
						$t_instance->update();
						if ($t_instance->numErrors()) {
							print "[ERROR] Could not update record before media: ".join('; ', $t_instance->getErrors())."\n";
						}
						// TODO: detect if media has changes and only pull if it has
						$o_attr = $o_item->{'ca_attribute_'.$vs_code};
						foreach($o_attr as $va_tag => $o_tags) {
							foreach($o_tags as $vs_locale => $o_values) {
								if (!($vn_locale_id = $t_locale->localeCodeToID($vs_locale))) { $vn_locale_id = null; }
								foreach($o_values as $o_value) {
									if ($vs_value = trim((string)$o_value->{$vs_code})) {
										$t_instance->replaceAttribute(
											array(
												$vs_code => $vs_value,		// value is URL
												'locale_id' => $vn_locale_id
											),
											$vs_code);
									}
								}
							}
						}
						$t_instance->update();
						if ($t_instance->numErrors()) {
							print "[ERROR] Could not update record after media: ".join('; ', $t_instance->getErrors())."\n";
						}
						break;
					default:
						$o_attr = $o_item->{'ca_attribute_'.$vs_code};
						foreach($o_attr as $va_tag => $o_tags) {
							foreach($o_tags as $vs_locale => $o_values) {
								if (!($vn_locale_id = $t_locale->localeCodeToID($vs_locale))) { $vn_locale_id = null; }
								foreach($o_values as $o_value) {
									if ($vs_value = trim((string)$o_value->{$vs_code})) {
									$t_instance->replaceAttribute(
										array(
											$vs_code => $vs_value,
											'locale_id' => $vn_locale_id
										),
										$vs_code);
									}
								}
							}
						}
						break;
				}	
			}
			
			$t_instance->update();
			if ($t_instance->numErrors()) {
				print "[ERROR] Could not update [1] record: ".join('; ', $t_instance->getErrors())."\n";
			}
						
			//
			// TODO: get rid of this NHF-specific code
			//
			if ($vs_table == 'ca_occurrences') {
				$t_instance->replaceAttribute(
					array(
						'CLIR2_institution' => $vn_nhf_inst_id,
						'locale_id' => $vn_locale_id
					),
					'CLIR2_institution');
			}
			
			// TODO: add hook onBeforeUpdate()
			$t_instance->update();
			// TODO: add hook onUpdate()
			
			if ($t_instance->numErrors()) {
				print "[ERROR] Could not update [2] record: ".join('; ', $t_instance->getErrors())."\n";
			}
			
			// get label fields
			$va_label_data = array();
			foreach($t_instance->getLabelUIFields() as $vs_field) {
				if (!($va_label_data[$vs_field] = $o_item->preferred_labels->en_US->{$vs_field})) {
					$va_label_data[$vs_field] = $o_item->preferred_labels->en_NE->{$vs_field};
				}
			}
			
			// TODO: add hook onBeforeAddLabel()
			$t_instance->addLabel(
				$va_label_data, 1, null, true
			);
			// TODO: add hook onAddLabel()
			if ($t_instance->numErrors()) {
				print "ERROR adding label: ".join('; ', $t_instance->getErrors())."\n";
			}
			
			$va_processed_records[$va_item['table'].'/'.(int)$va_item['id']] = $t_instance->getPrimaryKey();
			if ($vb_skip) { continue; }
			if (!(is_array($va_import_relationships_from))) { continue; }
			
			$pa_tables[$va_item['table']] = true;
			
			// Are there relationships?
			foreach($va_import_relationships_from as $vs_rel_table => $va_table_info) {
				if (!$pa_tables[$vs_rel_table]) {
					// load related records recursively
					if ($o_item->{'related_'.$vs_rel_table}) {
						$t_rel = $o_dm->getInstanceByTableName($vs_rel_table, true);
						
						// TODO: add hook onBeforeAddRelationships()
						foreach($o_item->{'related_'.$vs_rel_table} as $vs_tag => $o_related_items) {
							foreach($o_related_items as $vs_i => $o_related_item) {
								if (is_array($pa_config['importRelatedFor'][$va_item['table']][$vs_rel_table])) {
									$va_rel_types = array_keys($pa_config['importRelatedFor'][$va_item['table']][$vs_rel_table]);
									if (is_array($va_rel_types) && sizeof($va_rel_types) && !in_array((string)$o_related_item->relationship_type_code, $va_rel_types)) {
										print "[INFO] Skipped relationship for {$vs_display_name} because type='".(string)$o_related_item->relationship_type_code."' is excluded\n";
										continue;
									}
								}
								
								
								$vs_pk = $t_rel->primaryKey();
								$vn_id = (int)$o_related_item->{$vs_pk};
								$va_queue = array(
									$vs_rel_table."/".$vn_id => array(
										'table' => $vs_rel_table,
										'id' => $vn_id,
										'idno' => (string)$o_related_item->idno
									)
								);
								
								
								// TODO: Add from/until support	
								fetchAndImport($va_queue, $po_client, $pa_config, $pa_tables);
								
								$t_instance->addRelationship($vs_rel_table, $va_processed_records[$vs_rel_table.'/'.(int)$vn_id], (int)$o_related_item->relationship_type_id);
								if ($t_instance->numErrors()) {
									print "[ERROR] Could not add relationship to {$vs_rel_table} for row_id=".$va_processed_records[$vs_rel_table.'/'.(int)$vn_id].": ".join('; ', $t_instance->getErrors())."\n";
								}
							}
						}
						
						// TODO: add hook onAddRelationships()
					}
				}
			}
			
			// Is there media?
			if ($t_instance->tableName() == 'ca_objects') {
				
				$o_rep_xml = $po_client->getObjectRepresentations((int)$va_item['id'], array('original'))->get();
				$va_existing_reps = $t_instance->getRepresentations(array('original'));
				$va_existing_md5s = array();
				foreach($va_existing_reps as $va_rep) {
					$va_existing_md5s[$va_rep['info']['original']['MD5']] = $va_rep['representation_id'];
				}
				foreach($o_rep_xml->getObjectRepresentations as $vs_x => $o_reps) {
					foreach($o_reps as $vs_key => $o_rep) {
						if ($vs_url = trim((string)$o_rep->urls->original)) {
							$vs_remote_md5 = (string)$o_rep->info->original->MD5;
							if (isset($va_existing_md5s[$vs_remote_md5]) && $va_existing_md5s[$vs_remote_md5]) { 
								print "[NOTICE] Skipping representation at {$vs_url} because it already exists (MD5={$vs_remote_md5})\n";
								unset($va_existing_md5s[$vs_remote_md5]);
								continue;
							}
							
							print "[Notice] Importing media from {$vs_url}\n";
							
							// TODO: add hook onBeforeAddMedia()
							$t_instance->addRepresentation(
								$vs_url, $pn_rep_type_id, 1, (int)$o_rep->status, (int)$o_rep->access, (bool)$o_rep->is_primary
							);
							
							// TODO: add hook onAddMedia()
							if ($t_instance->numErrors()) {
								print "[ERROR] Could not load object representation: ".join("; ", $t_instance->getErrors())."\n";
							}
						}
						
					}
				}
				
				foreach($va_existing_md5s as $vs_md5 => $vn_rep_id) {
					$t_obj_x_rep = new ca_objects_x_object_representations();
					while($t_obj_x_rep->load(array('object_id' => $t_instance->getPrimaryKey(), 'representation_id' => $vn_rep_id))) {
						$t_obj_x_rep->setMode(ACCESS_WRITE);
						$t_obj_x_rep->delete(true);
						
						if ($t_obj_x_rep->numErrors()) {
							print "[ERROR] Could not load remove object-to-representation link: ".join("; ", $t_obj_x_rep->getErrors())."\n";
							break;
						}
						
						if (!$t_obj_x_rep->load(array('representation_id' => $vn_rep_id))) {
							$t_rep = new ca_object_representations();
							if ($t_rep->load($vn_rep_id)) {
								$t_rep->setMode(ACCESS_WRITE);
								$t_rep->delete(true);
								if ($t_rep->numErrors()) {
									print "[ERROR] Could not load remove representation: ".join("; ", $t_rep->getErrors())."\n";
									break;
								}
							}
						}
					}
				}
				
			}
			unset($pa_tables[$va_item['table']]);
		}
	}
	# ------------------------------------------------------------------------------------------
?>