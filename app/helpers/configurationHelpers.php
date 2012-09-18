<?php
/** ---------------------------------------------------------------------
 * app/helpers/configurationHelpers.php : utility functions for setting database-stored configuration values
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
   	
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_type_restrictions.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
require_once(__CA_MODELS_DIR__.'/ca_editor_uis.php');
require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
require_once(__CA_MODELS_DIR__.'/ca_locales.php');
require_once(__CA_MODELS_DIR__.'/ca_user_roles.php');
require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');
require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');
require_once(__CA_MODELS_DIR__.'/ca_bundle_mappings.php');

	# ------------------------------------------------------------------------------------------------
	function caConfigAddLists($pa_lists_config) {
		if (!is_array($pa_lists_config)) { return null; }
		
		$o_dm = Datamodel::load();
		
		$t_list = new ca_lists();
		$t_list->setMode(ACCESS_WRITE);
		$va_list_ids = array();
		$va_list_item_ids = array();
		
		$va_locale_ids = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true));
		//$va_lists = $o_profile->getLists();
		foreach($pa_lists_config as $vs_list_code => $va_info) {
			$t_list->set('list_code', $vs_list_code);
			$t_list->set('is_system_list', 1);
			$t_list->set('is_hierarchical', $va_info['is_hierarchical']);
			$t_list->set('use_as_vocabulary', $va_info['use_as_vocabulary']);
			$t_list->set('default_sort', isset($va_info['default_sort']) ? (is_numeric($va_info['default_sort']) ? $va_info['default_sort'] : constant($va_info['default_sort'])) : null);
			$t_list->insert();
			
			if ($t_list->numErrors()) {
				print "error inserting list";
				print_r($t_list->getErrors());
				return false;
			}
			$va_list_ids[$vs_list_code] = $t_list->getPrimaryKey();
			$va_list_item_ids[$vs_list_code] = array();
			
			foreach($va_info['preferred_labels'] as $vs_locale => $va_label_info) {
				$t_list->addLabel(array('name' => $va_label_info['name']), $va_locale_ids[$vs_locale]['locale_id']);
				 if ($t_list->numErrors()) {
				 	print "error add list label";
				 	print_r($t_list->getErrors());
				 	return false;
				 }
			}
			
			// TODO: proper error checking
			
			// add list items
			if (!_caConfigProcessListItems($t_list, $va_info['items'], null, $va_locale_ids)) {
				print "error add list item";
				print_r($t_list->getErrors());
				return false;
			}
		}
		
		return true;
	}
	# ----------------------------------------------------------------
	function _caConfigProcessListItems($t_list, $pa_items, $pn_parent_id, $pa_locale_ids) {
		foreach($pa_items as $vs_item_code => $va_item_info) {
			if (strlen($vs_item_value = $va_item_info['item_value']) == 0) {
				$vs_item_value = $vs_item_code;
			}
			
			$vn_type_id = null;
			if ($va_item_info['type']) {
				$vn_type_id = $t_list->getItemIDFromList('list_item_types', $va_item_info['type']);
			}
			
			if (!isset($va_item_info['status'])) { $va_item_info['status'] = 0; }
			if (!isset($va_item_info['access'])) { $va_item_info['access'] = 0; }
			if (!isset($va_item_info['rank'])) { $va_item_info['rank'] = 0; }
			
			$t_item = $t_list->addItem($vs_item_value, $va_item_info['is_enabled'], $va_item_info['is_default'], $pn_parent_id, $vn_type_id, $vs_item_code, '', (int)$va_item_info['status'], (int)$va_item_info['access'], (int)$va_item_info['rank']);
			
			 if ($t_list->numErrors()) {
				return false;
			} else {
				$va_list_item_ids[$vs_list_code][$vs_item_code] = $t_item->getPrimaryKey();
				foreach($va_item_info['preferred_labels'] as $vs_locale => $va_label_info) {
					$t_item->addLabel(array('name_singular' => $va_label_info['name_singular'], 'name_plural' => $va_label_info['name_plural']), $pa_locale_ids[$vs_locale]['locale_id'], null, true, 0, '');
				}
				if ($t_item->numErrors()) {
					return false;
				}
			 }
			 
			 if (is_array($va_item_info['items'])) {
			 	if (!caConfigProcessListItems($t_list, $va_item_info['items'], $t_item->getPrimaryKey(), $pa_locale_ids)) {
			 		return false;
			 	}
			 }
		}
		return true;
	}
	# ------------------------------------------------------------------------------------------------
	function caConfigAddMetadataElementSets($pa_elements_config) {
		if (!is_array($pa_elements_config)) { return null; }
		$o_dm = Datamodel::load();
		
		$va_locale_ids = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true));
		
		$t_list = new ca_lists();
		$t_list_item = new ca_list_items();
		foreach($pa_elements_config as $vs_element_code => $va_element_info) {
			// add elements and sub-elements
			
			
			if ($vn_element_id = caConfigProcessMetadataElementConfig($vs_element_code, $va_element_info, null, $va_locale_ids)) {
			
				// add type restrictions
				foreach($va_element_info['type_restrictions'] as $vs_restriction_code => $va_restriction_info) {
					$vn_table_num = $o_dm->getTableNum($va_restriction_info['table']);
					$t_instance = $o_dm->getTableInstance($va_restriction_info['table']);
					$vs_type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());
					if (trim($va_restriction_info['type'])) {
						$t_list->load(array('list_code' => $vs_type_list_name));
						$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'idno' => $va_restriction_info['type']));
					}
					$t_restriction = new ca_metadata_type_restrictions();
					$t_restriction->setMode(ACCESS_WRITE);
					$t_restriction->set('table_num', $vn_table_num);
					$t_restriction->set('type_id', (trim($va_restriction_info['type'])) ? $t_list_item->getPrimaryKey(): null);
					$t_restriction->set('element_id', $vn_element_id);
					foreach($va_restriction_info['settings'] as $vs_setting => $vs_setting_value) {
						$t_restriction->setSetting($vs_setting, $vs_setting_value);
					}
					$t_restriction->insert();
					
					if ($t_restriction->numErrors()) {
						print_r($t_list->getErrors());
						return false;
					}
				}
			} else {
				// error
				return false;
			}
		}
		return true;
	}
	# ------------------------------------------------------------------------------------------------
	function caConfigAddUIs($pa_ui_config) {
		//$va_uis = $o_profile->getUIs();
		if (!is_array($pa_ui_config)) { return null; }
		
		$o_dm = Datamodel::load();
		
		$va_locale_ids = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true));
		
		foreach($pa_ui_config as $vs_ui_code => $va_ui_info) {
			if (!($vn_type = $o_dm->getTableNum($va_ui_info['type']))) { 
				print "Invalid UI editor type '".$va_ui_info['type']."'";
				continue;
			}
			// create ui row
			$t_ui = new ca_editor_uis();
			$t_ui->setMode(ACCESS_WRITE);
			$t_ui->set('user_id', null);
			$t_ui->set('is_system_ui', 1);
			$t_ui->set('editor_type', $vn_type);
			$t_ui->insert();
			
			if ($t_ui->numErrors()) {
				print_r($t_ui->getErrors());
				return false;
			}
			
			$vn_ui_id = $t_ui->getPrimaryKey();
			
			// create ui labels
			foreach($va_ui_info['preferred_labels'] as $vs_locale => $va_label_info) {
				$t_ui->addLabel(array('name' => $va_label_info['name']), $va_locale_ids[$vs_locale]['locale_id'], null, true, 0, '');
			}
			
			// create ui screens
			foreach($va_ui_info['screens'] as $vs_screen_code => $va_screen_info) {
				// TODO: support hierarchical screens (ie. screens w/subscreens - allows on to group screens); right now everything is single-level
				$t_ui_screens = new ca_editor_ui_screens();
				$t_ui_screens->setMode(ACCESS_WRITE);
				$t_ui_screens->set('parent_id', null);
				$t_ui_screens->set('ui_id', $vn_ui_id);
				$t_ui_screens->set('is_default', $va_screen_info['is_default']);
				$t_ui_screens->insert();
				
				if ($t_ui_screens->numErrors()) {
					print_r($t_ui_screens->getErrors());
					return false;
				}
				
				$vn_screen_id = $t_ui_screens->getPrimaryKey();
				
				// create ui screen labels
				foreach($va_screen_info['preferred_labels'] as $vs_locale => $va_label_info) {
					$t_ui_screens->addLabel(array('name' => $va_label_info['name']), $va_locale_ids[$vs_locale]['locale_id'], null, true, 0, '');
				}
				
				// create ui bundle placements
				foreach($va_screen_info['bundles'] as $vs_placement_code => $va_placement_info) {
					// TODO: Set bundle placement settings here!
					$t_ui_screens->addBundlePlacement($va_placement_info['bundle'], $vs_placement_code, $va_placement_info);
				}
				
				// create ui screen type restrictions
				if (isset($va_screen_info['type_restrictions']) && is_array($va_screen_info['type_restrictions'])) {
					foreach($va_screen_info['type_restrictions'] as $vs_restriction_code => $va_restriction_info) {
						if(!is_array($va_restriction_info)) { continue; }
						$vn_table_num = $o_dm->getTableNum($va_restriction_info['table']);
						$t_instance = $o_dm->getTableInstance($va_restriction_info['table']);
						$vs_type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());
						if (trim($va_restriction_info['type'])) {
							$t_list->load(array('list_code' => $vs_type_list_name));
							$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'idno' => $va_restriction_info['type']));
						}
						$t_ui_screens->addTypeRestriction($vn_type, (trim($va_restriction_info['type']) ? $t_list_item->getPrimaryKey(): null), array());
					}
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------------------------------------------------
	function caConfigAddRelationshipTypes($pa_relationship_types_config) {
		global $va_list_item_ids;
		if (!is_array($pa_relationship_types_config)) { return null; }
		$o_dm = Datamodel::load();

		// define va_list_item_ids
		$ca_db = new Db('',null, false);
                if (!$lists_result = $ca_db->query(" SELECT * FROM ca_lists  ")) {
                        printf("Errormessage: %s\n", $ca_db->error);
                        print("<br/>SQL:".$sql);
                        exit;
                }

                $list_names = array();
                $va_list_item_ids = array();
                while($lists_result->nextRow()) {
                        $list_names[$lists_result->get('list_id')] = $lists_result->get('list_code');
                }

                // get list items
                $list_items_result = $ca_db->query(" SELECT * FROM ca_list_items cli INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id ");
                while($list_items_result->nextRow()) {
                        $list_type_code = $list_names[$list_items_result->get('list_id')];
                        $va_list_item_ids[$list_type_code][$list_items_result->get('item_value')] = $list_items_result->get('item_id');
                }
		
		$va_locale_ids = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true));
		
		$t_rel_type = new ca_relationship_types();
		$t_rel_type->setMode(ACCESS_WRITE);
		
		foreach($pa_relationship_types_config as $vs_table => $va_typelist) {
			$vn_table_num = $o_dm->getTableNum($vs_table);
			
			$t_rel_table = $o_dm->getTableInstance($vs_table);
			
			if (!method_exists($t_rel_table, 'getLeftTableName')) {
				continue;
			}
			$vs_left_table = $t_rel_table->getLeftTableName();
			$vs_right_table = $t_rel_table->getRightTableName();
			
			// create relationship type root
			$t_rel_type->set('parent_id', null);
			$t_rel_type->set('type_code', 'root_for_'.$vn_table_num);
			$t_rel_type->set('sub_type_left_id', null);
			$t_rel_type->set('sub_type_right_id', null);
			$t_rel_type->set('table_num', $vn_table_num);
			$t_rel_type->set('rank', 10);
			$t_rel_type->set('is_default', 0);
			
			$t_rel_type->insert();
			
			if ($t_rel_type->numErrors()) {
				print "<div class='installTaskFailure'>failed! <span class='installTaskFailureInfo'>".join("; ",$t_rel_type->getErrors())." adding root relationship type '$vs_table'</p><p>Installation halted</p></span></div>\n";
				return false;
			}
			
			$vn_parent_id = $t_rel_type->getPrimaryKey();
			
			caConfigProcessRelationshipTypes($va_typelist['types'], $vn_table_num, $vs_left_table, $vs_right_table, $vn_parent_id, $va_locale_ids);
			
		}
		return true;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Add roles from profile
	 */
	function caConfigProcessRelationshipTypes($pa_relationship_types, $pn_table_num, $ps_left_table, $ps_right_table, $pn_parent_id, $pa_locale_ids) {
		global $va_list_item_ids;
		$o_dm = Datamodel::load();
	
		$t_rel_type = new ca_relationship_types();
		$t_rel_type->setMode(ACCESS_WRITE);
		
		foreach($pa_relationship_types as $vs_type_code => $va_type) {
			$t_rel_type->set('table_num', $pn_table_num);
			$t_rel_type->set('type_code', $vs_type_code);
			$t_rel_type->set("parent_id", $pn_parent_id);
			
			$t_rel_type->set('sub_type_left_id', null);
			$t_rel_type->set('sub_type_right_id', null);

			if (trim($vs_left_subtype_code = $va_type['subtype_left'])) {
				$t_obj = $o_dm->getTableInstance($ps_left_table);
				$vs_list_code = $t_obj->getFieldListCode($t_obj->getTypeFieldName());

				if (isset($va_list_item_ids[$vs_list_code][$vs_left_subtype_code])) {
					$t_rel_type->set('sub_type_left_id', $va_list_item_ids[$vs_list_code][$vs_left_subtype_code]);
				}
			}
			if (trim($vs_right_subtype_code = $va_type['subtype_right'])) {
				$t_obj = $o_dm->getTableInstance($ps_right_table);
				$vs_list_code = $t_obj->getFieldListCode($t_obj->getTypeFieldName());
				if (isset($va_list_item_ids[$vs_list_code][$vs_right_subtype_code])) {
					$t_rel_type->set('sub_type_right_id', $va_list_item_ids[$vs_list_code][$vs_right_subtype_code]);
				}
			}
			
			$t_rel_type->set('is_default', $va_type['is_default'] ? 1 : 0);
			$t_rel_type->insert();
			
			if ($t_rel_type->numErrors()) {
				print "ERROR INSERTING relationship type for [{$vs_type_code}]: ".join('; ', $t_rel_type->getErrors())."\n";
				return false;
			}
			
			
			foreach($va_type['preferred_labels'] as $vs_locale => $va_label_info) {
				$t_rel_type->addLabel(array(
					'typename' => $va_label_info['typename'],
					'typename_reverse' => $va_label_info['typename_reverse'],
					'description' => $va_label_info['description'],
					'description_reverse' => $va_label_info['description_reverse']
				), $pa_locale_ids[$vs_locale]['locale_id'], null, true);
				
				if ($t_rel_type->numErrors()) {
					print "ERROR INSERTING relationship type label for [{$vs_type_code}]: ".join('; ', $t_rel_type->getErrors())."\n";
					return false;
				}
			}
			
			if (isset($va_type['types']) && is_array($va_type['types'])) {
				caConfigProcessRelationshipTypes($va_type['types'], $vn_table_num, $ps_left_table, $ps_right_table, $t_rel_type->getPrimaryKey(), $pa_locale_ids);
			}
		}
	}		
	# ------------------------------------------------------------------------------------------------
	/**
	 * Add roles from profile
	 */
	function caConfigAddRoles($pa_roles_config) {
		if (!is_array($pa_roles_config)) { return null; }
		
		$t_role = new ca_user_roles();
		$t_role->setMode(ACCESS_WRITE);
		
		$vb_errors = false;
		foreach($pa_roles_config as $vs_role_code => $va_info) {
			$t_role->set('name', $va_info['name']);
			$t_role->set('description', $va_info['description']);
			$t_role->set('code', $vs_role_code);
			$t_role->setRoleActions($va_info['actions']);
			$t_role->insert();
			
			if ($t_role->numErrors()) {
				$vb_errors = true;
				continue;
			}
		}
		return !$vb_errors;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Add roles from profile
	 */
	function caConfigAddGroups($pa_groups_config) {
		if (!is_array($pa_groups_config)) { return null; }
		
		$t_group = new ca_user_groups();
		$t_group->setMode(ACCESS_WRITE);
		$vb_errors = false;
		foreach($pa_groups_config as $vs_group_code => $va_info) {
		
			$t_group->set('name', $va_info['name']);
			$t_group->set('description', $va_info['description']);
			$t_group->set('code', $vs_group_code);
			$t_group->set('parent_id', null);
			$t_group->insert();
			
			if ($t_group->numErrors()) {
				$vb_errors = true;
				continue;
			}
			
			$t_group->addRoles($va_info['roles']);
			if ($t_group->numErrors()) {
				$vb_errors = true;
				continue;
			}
		}
		
		return !$vb_errors;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Add bundle displays from profile
	 */
	function caConfigAddBundleDisplays($pa_displays_config, $pa_locale_ids) {
		if (!is_array($pa_displays_config)) { return null; }
		
		$o_dm = Datamodel::load();
		foreach($pa_displays_config as $vs_display_code => $va_info) {
			if (!$vs_display_code) { continue; }
			if (!($vn_type = $o_dm->getTableNum($va_info['type']))) { 
				print "Invalid bundle display type '".$va_ui_info['type']."'";
				continue;
			}
			
			$t_display = new ca_bundle_displays();
			$t_display->setMode(ACCESS_WRITE);
			
			$t_display->set('display_code', $vs_display_code);
			$t_display->set('user_id', null);
			$t_display->set('is_system', 1);
			$t_display->set('table_num', $vn_type);
			
			if (isset($va_info['settings']) && is_array($va_info['settings'])) {
				foreach($va_info['settings'] as $vs_key => $vs_value) {
					$t_display->setSetting($vs_key, $vs_value);
				}
			}
				
			$t_display->insert();
			
			if ($t_display->numErrors()) {
				print_r($t_display->getErrors());
				return false;
			}
			
			$vn_display_id = $t_display->getPrimaryKey();
			
			// create labels
			foreach($va_info['preferred_labels'] as $vs_locale => $va_label_info) {
				$t_display->addLabel(array('name' => $va_label_info['name'], 'description' => $va_label_info['description']), $pa_locale_ids[$vs_locale], null, true, 0, '');
			}
			
			// add placements
			foreach($va_info['bundles'] as $vs_code => $va_placement) {
				if (!$t_display->addPlacement($va_placement['bundle'], $va_placement)) {
					// TODO: handle errors nicely
					print_r($t_display->getErrors());
				}
			}
		}
		
		return !$vb_errors;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Add bundle mappings from profile
	 */
	function caConfigAddBundleMappings($pa_mappings_config, $pa_locale_ids) {
		if (!is_array($pa_mappings_config)) { return null; }
		
		$o_dm = Datamodel::load();
		
		foreach($pa_mappings_config as $vs_mapping_code => $va_info) {
			if (!$vs_mapping_code) { continue; }
			
			if (!($vn_table_num = $o_dm->getTableNum($va_info['table']))) { 
				print "Invalid bundle mapping table '".$va_info['table']."'";
				continue;
			}
			
			$t_mapping = new ca_bundle_mappings();
			$t_mapping->setMode(ACCESS_WRITE);
			
			$t_mapping->set('mapping_code', $vs_mapping_code);
			$t_mapping->set('direction', $va_info['direction']);
			$t_mapping->set('target', $va_info['target']);
			$t_mapping->set('table_num', $vn_table_num);
			
			if (isset($va_info['settings']) && is_array($va_info['settings'])) {
				foreach($va_info['settings'] as $vs_key => $vs_value) {
					$t_mapping->setSetting($vs_key, $vs_value);
				}
			}
				
			$t_mapping->insert();
			
			if ($t_mapping->numErrors()) {
				print_r($t_mapping->getErrors());
				return false;
			}
			
			$vn_mapping_id = $t_mapping->getPrimaryKey();
			
			// create labels
			foreach($va_info['preferred_labels'] as $vs_locale => $va_label_info) {
				$t_mapping->addLabel(array('name' => $va_label_info['name'], 'description' => $va_label_info['description']), $pa_locale_ids[$vs_locale]['locale_id'], null, true, 0, '');
			}
			
			// add relationships
			$t_instance = $o_dm->getInstanceByTableName($va_info['table']);
			$vn_type_id = null;
			if ($t_instance && method_exists($t_instance, "getTypeListCode") && ($vs_type_list_code = $t_instance->getTypeListCode())) {
				$t_list = new ca_lists();
				$vn_type_id = $t_list->getItemIDFromList($vs_type_list_code, $va_info['type']);
			}
			foreach($va_info['bundles'] as $vs_code => $va_relationship) {
				if (!$t_mapping->addRelationship($va_relationship['bundle'], $va_relationship['destination'], $va_relationship['group'], $vn_type_id, $va_relationship)) {
					// TODO: handle errors nicely
					print_r($t_mapping->getErrors());
				}
			}
		}
		
		return !$vb_errors;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Checks permissions on various directories where permissions are important and returns
	 * a list of error codes if things are amiss. Directories checked are:
	 * app/tmp
	 * app/lucene
	 * media
	 *
	 * Return an array error codes, key'ed with the constants defined below. The values of the keys
	 * are additional information. For app/tmp errors this is simply a boolean true 
	 * (nothing useful in other words). For media and lucene errors, this will be the path where the 
	 * permissions problem is.
	 */
	define('__CA_DIR_PERM_ERROR_APP_TMP__', 0);		// app/tmp has wrong permissions
	define('__CA_DIR_PERM_ERROR_APP_LUCENE__', 1);	// app/lucene has wrong permissions
	define('__CA_DIR_PERM_ERROR_MEDIA__', 2);			// media directory has wrong permissions
	
	function caCheckDirectoryPermissions() {
		$o_config = Configuration::load();
		
		$va_errors = array();
		
		//
		// Check app/tmp
		//
		if (!is_writeable(__CA_APP_DIR__.'/tmp')) {
			$va_errors[__CA_DIR_PERM_ERROR_APP_TMP__] = true;
		}
		
		//
		// Check app/lucene
		//
		if (($o_config->get('search_engine_plugin') == 'Lucene') && (!is_writeable($o_config->get('search_lucene_index_dir')))) {
			$va_errors[__CA_DIR_PERM_ERROR_APP_LUCENE__] = $o_config->get('search_lucene_index_dir');
		}
		
		//
		// Check media
		//
		$vs_media_root = $o_config->get('ca_media_root_dir');
                $vs_base_dir = $o_config->get('ca_base_dir');
		$va_tmp = explode('/', $vs_media_root);
		$vb_perm_media_error = false;
		$vs_perm_media_path = null;
		$vb_at_least_one_part_of_the_media_path_exists = false;
		while(sizeof($va_tmp)) {
			if (!file_exists(join('/', $va_tmp))) {
				array_pop($va_tmp);
				continue;
			}
			if (!is_writeable(join('/', $va_tmp))) {
				$vb_perm_media_error = true;
				$vs_perm_media_path = join('/', $va_tmp);
				break;
			}
			$vb_at_least_one_part_of_the_media_path_exists = true;
			break;
		}
		
		// check web root for write-ability
		if (!$vb_perm_media_error && !$vb_at_least_one_part_of_the_media_path_exists && !is_writeable($vs_web_root)) { 
			$vb_perm_media_error = true; 
			$vs_perm_media_path = $vs_base_dir;
		}
		
		if ($vb_perm_media_error) {
			$va_errors[__CA_DIR_PERM_ERROR_MEDIA__] = $vs_perm_media_path;
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Checks for PHP configuration issues
	 *
	 * Return an array error codes, key'ed with the constants defined below. The values of the keys
	 * are additional information. 
	 */
	define('__CA_PHP_ERROR_MEMORY_LIMIT__', 0);		// must be at least 32M
	
	function caCheckPHPConfiguration() {
		$va_errors = array();
		
		$vn_memory_limit = (int)ini_get('memory_limit');
		
		if ($vn_memory_limit < 32) {
			$va_errors[__CA_PHP_ERROR_MEMORY_LIMIT__] = _t("Installation requires the PHP memory_limit directive must be set to at least 32 megabytes. Check your php.ini configuration file. If the memory_limit setting is not increased installation may unexpectedly abort.");
		}
		
		if ($vn_memory_limit < 128) {
			$va_errors[__CA_PHP_ERROR_MEMORY_LIMIT__] = _t("Installation would like the PHP memory_limit directive must be set to at least 128 megabytes, just to be safe. Check your php.ini configuration file. If the memory_limit setting is not increased installation can unexpectedly abort.");
		}
		
		return $va_errors;
	}
	# ----------------------------------------------------------------

	define('__CA_MYSQL_ERROR_INNODB__', 0);

	function caCheckMySQLConfiguration() {
		$va_mysql_errors = array();
		$vo_db = new Db();
		$qr_engines = $vo_db->query("SHOW ENGINES");
		$vb_innodb_available = false;
		while($qr_engines->nextRow()){
			if(strtolower($qr_engines->get("Engine"))=="innodb"){
				$vb_innodb_available = true;
			}
		}
		if(!$vb_innodb_available){
			$va_mysql_errors[__CA_MYSQL_ERROR_INNODB__] = _t("Your MySQL installation doesn't support the InnoDB storage engine which is required by CollectiveAccess. For more information also see %1.","<a href='http://dev.mysql.com/doc/refman/5.1/en/innodb.html' target='_blank'>http://dev.mysql.com/doc/refman/5.1/en/innodb.html</a>");
		}
		return $va_mysql_errors;
	}
	# ----------------------------------------------------------------
	/**
	  * Returns a sorted list of old-style profiles. Keys are display names and values are profile codes (filename without .xml extension).
	  * NOTE: this function is deprecated and will be going away soon, along with the old-style profile installer
	  */
	function caGetAvailableProfiles() {
		$va_files = caGetDirectoryContentsAsList('./profiles');
		$va_profiles = array();
		
		$o_config = Configuration::load();
		foreach($va_files as $vs_filepath) {
			if (preg_match("!\.profile$!", $vs_filepath)) {
				$vs_file = array_shift(explode('.', array_pop(explode('/', $vs_filepath))));
				$o_config->loadFile($vs_filepath, false, 10);
				if (intval($o_config->get('profile_use_for_configuration'))) {
					$va_profiles[$o_config->get('profile_name')] = $vs_file; 
				}
			}
		}
		
		ksort($va_profiles);
		return $va_profiles;
	}
	# ----------------------------------------------------------------
	/**
	  * Returns a sorted list of XML profiles. Keys are display names and values are profile codes (filename without .xml extension).
	  *
	  * @return array List of available profiles
	  */
	function caGetAvailableXMLProfiles() {
		$va_files = caGetDirectoryContentsAsList('./profiles/xml', false);
		$va_profiles = array();
		
		foreach($va_files as $vs_filepath) {
			if (preg_match("!\.xml$!", $vs_filepath)) {
				$vs_file = array_shift(explode('.', array_pop(explode('/', $vs_filepath))));
				$va_profile_info = Installer::getProfileInfo("./profiles/xml", $vs_file);
				if (!$va_profile_info['useForConfiguration']) { continue; }
				$va_profiles[$va_profile_info['display']] = $vs_file; 
			}
		}
		
		ksort($va_profiles);
		return $va_profiles;
	}
	# ----------------------------------------------------------------
	function caCheckDatabaseConnection() {
		$o_db = new Db('',null, false);
		if(!$o_db->connected()) {
		 	return $o_db->getErrors();
		} else {
			return array();
		}
	}
	# ----------------------------------------------------------------
	function caFlushOutput() {
		echo str_pad('',4096)."\n";
		@ob_flush();
		flush();
	}
	# ----------------------------------------------------------------
	function caGetRandomPassword() {
		return substr(md5(uniqid(microtime())), 0, 6);
	}
	# ----------------------------------------------------------------
	function caConfigProcessMetadataElementConfig($vs_element_code, $va_element_info, $pn_parent_id, $pa_locale_ids) {
		if (($vn_datatype = ca_metadata_elements::getAttributeTypeCode($va_element_info['datatype'])) === false) {
			//print "<div class='installTaskFailure'>failed! <span class='installTaskFailureInfo'> invalid data type '".$va_element_info['datatype']."' while adding metadata element '{$vs_element_code}'</p><p>Installation halted</p></span></div>\n";
			//$vb_fatal_error = true;
			//break(3);
			// TODO Error checking
			print "datatype error [".$va_element_info['datatype']."]<br>\n"; return false;
		}
		$t_lists = new ca_lists();
		$t_md_element = new ca_metadata_elements();
		$t_md_element->setMode(ACCESS_WRITE);
		$t_md_element->set('element_code', $vs_element_code);
		$t_md_element->set('parent_id', $pn_parent_id);
		$t_md_element->set('documentation_url', $va_element_info['documentation_url']);
		$t_md_element->set('datatype', $vn_datatype);
		
		if (isset($va_element_info['list']) && $va_element_info['list'] && $t_lists->load(array('list_code' => $va_element_info['list']))) {
			$vn_list_id = $t_lists->getPrimaryKey();
		} else {
			$vn_list_id = null;
		}
		$t_md_element->set('list_id', $vn_list_id);
		if (isset($va_element_info['settings']) && is_array($va_element_info['settings'])) {
			foreach($va_element_info['settings'] as $vs_setting => $vs_setting_val) {
				$t_md_element->setSetting($vs_setting, $vs_setting_val);
			}
		}
		
		$t_md_element->insert();
		
		if ($t_md_element->numErrors()) {
			//	print "<div class='installTaskFailure'>failed! <span class='installTaskFailureInfo'>".join("; ",$t_md_element->getErrors())." adding metadata element '{$vs_element_code}'</p><p>Installation halted</p></span></div>\n";
			//	$vb_fatal_error = true;
			//	break(3);
			// TODO error checking
			print "ERR:".join("; ",$t_md_element->getErrors())." in [$vs_element_code]<br>"; 
			return false;
		}
		
		$vn_element_id = $t_md_element->getPrimaryKey();
		
		// add element labels
		if (is_array($va_element_info['preferred_labels'])) {
			foreach($va_element_info['preferred_labels'] as $vs_locale => $va_label_info) {
				$t_md_element->addLabel(array('name' => $va_label_info['name'], 'description' => isset($va_label_info['description']) ? $va_label_info['description'] : ''), $pa_locale_ids[$vs_locale], null, true, 0, '');
				// TODO error checking
			}
		}
		
		if (isset($va_element_info['elements']) && is_array($va_element_info['elements'])) {
			foreach($va_element_info['elements'] as $vs_child_code => $va_child_info) {
				caConfigProcessMetadataElementConfig($vs_child_code, $va_child_info, $vn_element_id, $pa_locale_ids);
				
				// TODO error checking
			}
		}
		
		
		return $vn_element_id;
	}
	# ----------------------------------------------------------------
	function caConfigProcessListItems(&$t_list, $pa_items, $pn_parent_id, $pa_locale_ids) {
		if (!is_array($pa_items)) {
			print "List is empty for"; $t_list->dump();
			return false;
		}
		foreach($pa_items as $vs_item_code => $va_item_info) {
			if (!isset($va_item_info['item_value']) || !strlen($vs_value = trim($va_item_info['item_value']))) {
				$vs_value = $vs_item_code;
			}
			
			$vn_type_id = null;
			if ($va_item_info['type']) {
				$vn_type_id = $t_list->getItemIDFromList('list_item_types', $va_item_info['type']);
			}
			
			
			if (!isset($va_item_info['status'])) { $va_item_info['status'] = 0; }
			if (!isset($va_item_info['access'])) { $va_item_info['access'] = 0; }
			if (!isset($va_item_info['rank'])) { $va_item_info['rank'] = 0; }
			
			$t_item = $t_list->addItem($vs_value, $va_item_info['is_enabled'], $va_item_info['is_default'], $pn_parent_id, $vn_type_id, $vs_item_code, '', (int)$va_item_info['status'], (int)$va_item_info['access'], (int)$va_item_info['rank']);
			if ($t_list->numErrors()) {
				return false;
			} else {
				foreach($va_item_info['preferred_labels'] as $vs_locale => $va_label_info) {
					$t_item->addLabel(array(
						'name_singular' => $va_label_info['name_singular'], 
						'name_plural' => $va_label_info['name_plural'],
						'description' => $va_label_info['description']
					), $pa_locale_ids[$vs_locale], null, true, 0, '');
				}
				if ($t_item->numErrors()) {
					$t_list->errors = $t_item->errors;
					return false;
				}
			 }
			 
			 if (isset($va_item_info['items']) && is_array($va_item_info['items'])) {
			 	if (!caConfigProcessListItems($t_list, $va_item_info['items'], $t_item->getPrimaryKey(), $pa_locale_ids)) {
			 		return false;
			 	}
			 }
		}
		return true;
	}
	# ----------------------------------------------------------------
	function caCreateDirectoryPath($ps_path) {
			if (!file_exists($ps_path)) {
				if (!@mkdir($ps_path, 0777, true)) {
					return false;
				}else{
					return true;
				}
			}else{
				return true;
			}
	}
	# ------------------------------------------------------------------------------------------------
?>
