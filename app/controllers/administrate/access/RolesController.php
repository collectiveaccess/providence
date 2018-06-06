<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/access/RolesController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2014 Whirl-i-Gig
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

	require_once(__CA_MODELS_DIR__.'/ca_users.php');
 	require_once(__CA_MODELS_DIR__.'/ca_user_roles.php');
 	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');

 	class RolesController extends ActionController {
 		# -------------------------------------------------------
 		private $pt_role;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Edit() {
 			$t_role = $this->getRoleObject();
 			
 			$t_screen = new ca_editor_ui_screens();
			$t_list = new ca_lists();
			
			$va_role_vars = $t_role->get('vars');
			$va_bundle_access_settings = $va_role_vars['bundle_access_settings'];
	
			$vn_default_bundle_access_level = (int)$this->request->config->get('default_bundle_access_level');
	
			$va_bundle_list = $va_table_names = array(); 
			foreach(ca_users::$s_bundlable_tables as $vs_table) {
				$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
				
				$va_table_names[$vs_table] = caUcFirstUTF8Safe($t_instance->getProperty('NAME_PLURAL'));
				
				$va_available_bundles = $t_screen->getAvailableBundles($vs_table);
				foreach($va_available_bundles as $vs_bundle_name => $va_bundle_info) {
					
					$vn_access = isset($va_bundle_access_settings[$vs_table.'.'.$vs_bundle_name]) ? $va_bundle_access_settings[$vs_table.'.'.$vs_bundle_name] : $vn_default_bundle_access_level;
					$va_bundle_list[$vs_table][$vs_bundle_name] = array(
						'bundle_info' => $va_bundle_info,
						'access' => $vn_access
					);
				}
			}
			
			$this->view->setVar('bundle_list', $va_bundle_list);
			
			$vn_default_type_access_level = (int)$this->request->config->get('default_type_access_level');
			
			$va_type_list = array();
			$va_type_access_settings = $va_role_vars['type_access_settings'];
			
			foreach(ca_users::$s_bundlable_tables as $vs_table) {
				$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
				if (!($vs_list_code = $t_instance->getTypeListCode())) { continue; }
				
				$va_table_names[$vs_table] = caUcFirstUTF8Safe($t_instance->getProperty('NAME_PLURAL'));
				$va_types = $t_list->getListItemsAsHierarchy($vs_list_code, array('additionalTableToJoin' => 'ca_list_item_labels'));
				
				if (is_array($va_types)) {
					foreach($va_types as $vn_i => $va_type_info) {
						$vn_item_id = $va_type_info['NODE']['item_id'];
						$vn_access = isset($va_type_access_settings[$vs_table.'.'.$vn_item_id]) ? $va_type_access_settings[$vs_table.'.'.$vn_item_id] : $vn_default_type_access_level;
						$va_type_info['NODE']['level'] = $va_type_info['LEVEL'];
						
						$va_type_list[$vs_table][$vn_item_id] = array(
							'type_info' => $va_type_info['NODE'],
							'access' => $vn_access
						);
					}
				}
			}
			
			$vn_default_source_access_level = (int)$this->request->config->get('default_source_access_level');
			
			$va_source_list = array();
			$va_source_access_settings = $va_role_vars['source_access_settings'];
			
			foreach(ca_users::$s_bundlable_tables as $vs_table) {
				$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
				if (!($vs_list_code = $t_instance->getSourceListCode())) { continue; }
				
				$va_table_names[$vs_table] = caUcFirstUTF8Safe($t_instance->getProperty('NAME_PLURAL'));
				$va_sources = $t_list->getListItemsAsHierarchy($vs_list_code, array('additionalTableToJoin' => 'ca_list_item_labels'));
				
				if (is_array($va_sources)) {
					foreach($va_sources as $vn_i => $va_source_info) {
						$vn_item_id = $va_source_info['NODE']['item_id'];
						$vn_access = isset($va_source_access_settings[$vs_table.'.'.$vn_item_id]) ? $va_source_access_settings[$vs_table.'.'.$vn_item_id] : $vn_default_source_access_level;
						$va_source_info['NODE']['level'] = $va_source_info['LEVEL'];
						
						$va_source_list[$vs_table][$vn_item_id] = array(
							'source_info' => $va_source_info['NODE'],
							'access' => $vn_access,
							'default' => ($vn_item_id == $va_source_access_settings[$vs_table.'_default_id'])
						);
					}
				}
			}
			
			$va_access_status_list = array();
			if(is_array($va_access_statuses = $t_list->getListItemsAsHierarchy('access_statuses', array('additionalTableToJoin' => 'ca_list_item_labels')))) {
				$va_access_status_settings = $va_role_vars['access_status_settings'];
				foreach($va_access_statuses as $vn_i => $va_access_status_info) {
					if(!$va_access_status_info['NODE']['parent_id']) { continue; }
					$vn_item_id = $va_access_status_info['NODE']['item_id'];
					$va_access_status_info['NODE']['level'] = $va_access_status_info['LEVEL'];
						
					$vn_access = isset($va_access_status_settings[$vn_item_id]) ? $va_access_status_settings[$vn_item_id] : null;
					
					$va_access_status_list[$vn_item_id] = array(
						'access_status_info' => $va_access_status_info['NODE'],
						'access' => $vn_access
					);	
				}
			}
						
			$this->view->setVar('type_list', $va_type_list);
			$this->view->setVar('source_list', $va_source_list);
			$this->view->setVar('access_status_list', $va_access_status_list);
			$this->view->setVar('table_display_names', $va_table_names);
			
 			
 			$this->render('role_edit_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			AssetLoadManager::register('tableList');
 			
			$t_list = new ca_lists();
			
 			$t_role = $this->getRoleObject();
 			$t_role->setMode(ACCESS_WRITE);
 			foreach($t_role->getFormFields() as $vs_f => $va_field_info) {
 				$t_role->set($vs_f, $_REQUEST[$vs_f]);
 				if ($t_role->numErrors()) {
 					$this->request->addActionErrors($t_role->errors(), 'field_'.$vs_f);
 				}
 			}
 			
 			// get vars
 			$va_vars = $t_role->get('vars');
 			if (!is_array($va_vars)) { $va_vars = array(); }
 			
 			
 			// save bundle access settings
 			$t_screen = new ca_editor_ui_screens();
 			$va_bundle_access_settings = array();
 			foreach(ca_users::$s_bundlable_tables as $vs_table) {
				$va_available_bundles = $t_screen->getAvailableBundles($vs_table);
				foreach($va_available_bundles as $vs_bundle_name => $va_bundle_info) {
					$vs_bundle_name_proc = $vs_table.'_'.str_replace(".", "_", $vs_bundle_name);
					$vn_access = $this->request->getParameter($vs_bundle_name_proc, pInteger);
					
					$va_bundle_access_settings[$vs_table.'.'.$vs_bundle_name] = $vn_access;
				}
			}
			
 			$va_vars['bundle_access_settings'] = $va_bundle_access_settings;
 			
 			if ($t_role->getAppConfig()->get('perform_type_access_checking')) { 
				// save type access settings
				$va_type_access_settings = array();
				
				foreach(ca_users::$s_bundlable_tables as $vs_table) {
					if ((!caTableIsActive($vs_table)) && ($vs_table != 'ca_object_representations')) { continue; }
					$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
					if (!($vs_list_code = $t_instance->getTypeListCode())) { continue; }
					$va_type_ids = $t_list->getItemsForList($vs_list_code, array('idsOnly' => true));
					
					if (is_array($va_type_ids)) {
						foreach($va_type_ids as $vn_i => $vn_item_id) {
							$vn_access = $this->request->getParameter($vs_table.'_type_'.$vn_item_id, pInteger);
							
							$va_type_access_settings[$vs_table.'.'.$vn_item_id] = $vn_access;
						}
					}
				}
				
				$va_vars['type_access_settings'] = $va_type_access_settings;
			} 	
			
			if ($t_role->getAppConfig()->get('perform_source_access_checking')) { 
				// save source access settings
				$va_source_access_settings = array();
				
				foreach(ca_users::$s_bundlable_tables as $vs_table) {
					if ((!caTableIsActive($vs_table)) && ($vs_table != 'ca_object_representations')) { continue; }
					$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
					if (!($vs_list_code = $t_instance->getSourceListCode())) { continue; }
					$va_source_ids = $t_list->getItemsForList($vs_list_code, array('idsOnly' => true));
					
					if (is_array($va_source_ids)) {
						foreach($va_source_ids as $vn_i => $vn_item_id) {
							$vn_access = $this->request->getParameter($vs_table.'_source_'.$vn_item_id, pInteger);
							
							$va_source_access_settings[$vs_table.'.'.$vn_item_id] = $vn_access;
						}
					}
					$va_source_access_settings[$vs_table.'_default_id'] = $this->request->getParameter($vs_table.'_default_source', pInteger);
					
				}
				
				$va_vars['source_access_settings'] = $va_source_access_settings;
			} 	
			
			$va_access_status_settings = array();
			if(is_array($va_access_status_ids = $va_source_ids = $t_list->getItemsForList('access_statuses', array('idsOnly' => true)))) {
				foreach($va_access_status_ids as $vn_i => $vn_item_id) {
					$vs_access = $this->request->getParameter('access_status_'.$vn_item_id, pString);
					
					switch($vs_access) {
						case 0:
						case 1:
							$va_access_status_settings[$vn_item_id] = $vs_access;
							break;
						default:
							$va_access_status_settings[$vn_item_id] = null;
							break;
					}
				}
			}		
			$va_vars['access_status_settings'] = $va_access_status_settings;
 			
 			$t_role->set('vars', $va_vars);	
			
 			// save actions
			$va_role_action_list = $t_role->getRoleActionList();
			$va_new_role_action_settings = array();
			
			foreach($va_role_action_list as $vs_group => $va_group_info) {
				if ((caTableIsActive($vs_group) === false) && ($vs_group != 'ca_object_representations')) { continue; }		// will return null if group name is not a table name; true if it's an enabled table and false if it's a disabled table
			
				foreach($va_group_info['actions'] as $vs_action => $va_action_info) {
					if ($this->request->getParameter($vs_action, pInteger) > 0) {
						$va_new_role_action_settings[] = $vs_action;
					}
				}
			}
			$t_role->setRoleActions($va_new_role_action_settings);
			
			AppNavigation::clearMenuBarCache($this->request);	// clear menu bar cache since role changes may affect content
			
 			if($this->request->numActionErrors() == 0) {
				if (!$t_role->getPrimaryKey()) {
					$t_role->insert();
					$vs_message = _t("Added role");
				} else {
					$t_role->update();
					$vs_message = _t("Saved changes to role");
				}

				if ($t_role->numErrors()) {
					foreach ($t_role->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				} else {
					$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
				}
			} else {
				$this->notification->addNotification(_t("Your entry has errors. See below for details."), __NOTIFICATION_TYPE_ERROR__);
			}

			if ($this->request->numActionErrors()) {
				$this->render('role_edit_html.php');
			} else {
 				$this->view->setVar('role_list', $t_role->getRoleList());

 				$this->render('role_list_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		public function ListRoles() {
 			AssetLoadManager::register('tableList');
 			
 			$t_role = $this->getRoleObject();
 			$vs_sort_field = $this->request->getParameter('sort', pString);
 			$this->view->setVar('role_list', $t_role->getRoleList($vs_sort_field, 'asc'));

 			$this->render('role_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			$t_role = $this->getRoleObject();
 			if ($this->request->getParameter('confirm', pInteger)) {
 				$t_role->setMode(ACCESS_WRITE);
 				$t_role->delete(true);

 				if ($t_role->numErrors()) {
 					foreach ($t_role->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
					}
 				} else {
 					$this->notification->addNotification(_t("Deleted role"), __NOTIFICATION_TYPE_INFO__);
 				}
 				$this->ListRoles();
 				return;
 			} else {
 				$this->render('role_delete_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		# Utilities
 		# -------------------------------------------------------
 		private function getRoleObject($pb_set_view_vars=true, $pn_role_id=null) {
 			if (!($t_role = $this->pt_role)) {
				if (!($vn_role_id = $this->request->getParameter('role_id', pInteger))) {
					$vn_role_id = $pn_role_id;
				}
				$t_role = new ca_user_roles($vn_role_id);
			}
 			if ($pb_set_view_vars){
 				$this->view->setVar('role_id', $vn_role_id);
 				$this->view->setVar('t_role', $t_role);
 			}
 			$this->pt_role = $t_role;
 			return $t_role;
 		}
 		# -------------------------------------------------------
 	}