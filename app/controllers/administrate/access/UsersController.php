<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/access/UserController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");

 	class UsersController extends ActionController {
 		# -------------------------------------------------------
 		private $pt_user;
 		private $opo_app_plugin_manager;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
			
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 		}
 		# -------------------------------------------------------
 		public function Edit() {
 			JavascriptLoadManager::register("bundleableEditor");
 			$t_user = $this->getUserObject();
			
			$va_profile_prefs = $t_user->getValidPreferences('profile');
 			if (is_array($va_profile_prefs) && sizeof($va_profile_prefs)) {
 				$va_elements = array();
				foreach($va_profile_prefs as $vs_pref) {
					$va_pref_info = $t_user->getPreferenceInfo($vs_pref);
					$va_elements[$vs_pref] = array('element' => $t_user->preferenceHtmlFormElement($vs_pref), 'info' => $va_pref_info, 'label' => $va_pref_info['label']);
				}
				
				$this->view->setVar("profile_settings", $va_elements);
			}
 			
 			$this->render('user_edit_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_user = $this->getUserObject();
 			
 			$this->opo_app_plugin_manager->hookBeforeUserSaveData(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user));
 			
 			$t_user->setMode(ACCESS_WRITE);
 			foreach($t_user->getFormFields() as $vs_f => $va_field_info) {
 				$t_user->set($vs_f, $_REQUEST[$vs_f]);
 				if ($t_user->numErrors()) {
 					$this->request->addActionErrors($t_user->errors(), 'field_'.$vs_f);
 				}
 			}
 			
 			if ($this->request->getParameter('entity_id', pInteger) == 0) {
 				$t_user->set('entity_id', null);
 			}

 			if ($this->request->getParameter('password', pString) != $this->request->getParameter('password_confirm', pString)) {
 				$this->request->addActionError(new Error(1050, _t("Password does not match confirmation. Please try again."), "administrate/UserController->Save()", '', false, false), 'field_password');
 			} 
 			
 			AppNavigation::clearMenuBarCache($this->request);	// clear menu bar cache since changes may affect content
 			
 			if($this->request->numActionErrors() == 0) {
				if (!$t_user->getPrimaryKey()) {
					$t_user->insert();
					$vs_message = _t("Added user");
				} else {
					$t_user->update();
					$vs_message = _t("Saved changes to user");
				}
				
 				$this->opo_app_plugin_manager->hookAfterUserSaveData(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user));
 			
				if ($t_user->numErrors()) {
					foreach ($t_user->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				} else {
					// Save roles
					$va_set_user_roles = $this->request->getParameter('roles', pArray);
					if(!is_array($va_set_user_roles)) { $va_set_user_roles = array(); }
					
					$va_existing_user_roles = $t_user->getUserRoles();
					$va_role_list = $t_user->getRoleList();
					
					foreach($va_role_list as $vn_role_id => $va_role_info) {
						if ($va_existing_user_roles[$vn_role_id] && !in_array($vn_role_id, $va_set_user_roles)) {
							// remove role
							$t_user->removeRoles($vn_role_id);
							continue;
						}
						
						if (!$va_existing_user_roles[$vn_role_id] && in_array($vn_role_id, $va_set_user_roles)) {
							// add role
							$t_user->addRoles($vn_role_id);
							continue;
						}
					}
					
					// Save groups
					$va_set_user_groups = $this->request->getParameter('groups', pArray);
					if(!is_array($va_set_user_groups)) { $va_set_user_groups = array(); }
					
					$va_existing_user_groups = $t_user->getUserGroups();
					$va_group_list = $t_user->getGroupList();
					
					foreach($va_group_list as $vn_group_id => $va_group_info) {
						if ($va_existing_user_groups[$vn_group_id] && !in_array($vn_group_id, $va_set_user_groups)) {
							// remove group
							$t_user->removeFromGroups($vn_group_id);
							continue;
						}
						
						if (!$va_existing_user_groups[$vn_group_id] && in_array($vn_group_id, $va_set_user_groups)) {
							// add group
							$t_user->addToGroups($vn_group_id);
							continue;
						}
					}
					
					// Save profile prefs
					$va_profile_prefs = $t_user->getValidPreferences('profile');
					if (is_array($va_profile_prefs) && sizeof($va_profile_prefs)) {
						
 						$this->opo_app_plugin_manager->hookBeforeUserSavePrefs(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user));
 						
 						$va_changed_prefs = array();
						foreach($va_profile_prefs as $vs_pref) {
							if ($this->request->getParameter('pref_'.$vs_pref, pString) != $t_user->getPreference($vs_pref)) {
								$va_changed_prefs[$vs_pref] = true;
							}
							$t_user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
						}
						
						$t_user->update();
						
 						$this->opo_app_plugin_manager->hookAfterUserSavePrefs(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user, 'modified_prefs' => $va_changed_prefs));
					}
					

					$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
				}
			} else {
				$this->notification->addNotification(_t("Your entry has errors. See below for details."), __NOTIFICATION_TYPE_ERROR__);
			}

			if ($this->request->numActionErrors()) {
				$this->render('user_edit_html.php');
			} else {
				// success
				
				// If we are editing the user record of the currently logged in user
				// we have a problem: the request object flushes out changes to its own user object
				// for the logged-in user at the end of the request overwriting any changes we've made.
				//
				// To avoid this we check here to see if we're editing the currently logged-in
				// user and reload the request's copy if needed.
				if ($t_user->getPrimaryKey() == $this->request->user->getPrimaryKey()) {
					$this->request->user->load($t_user->getPrimaryKey());
				}
				
				$this->ListUsers();
 			}
 		}
 		# -------------------------------------------------------
 		public function ListUsers() {
 			JavascriptLoadManager::register('tableList');
 			if (($vn_userclass = $this->request->getParameter('userclass', pInteger)) == '') {
 				$vn_userclass = $this->request->user->getVar('ca_users_default_userclass');
 			} else {
 				$this->request->user->setVar('ca_users_default_userclass', $vn_userclass);
 			}
 			if (($vn_userclass < 0) || ($vn_user_class >= 2)) { $vn_userclass = 0; }
 			$t_user = $this->getUserObject();
 			
 			$this->view->setVar('userclass', $vn_userclass);
 			$this->view->setVar('userclass_displayname', $t_user->getChoiceListValue('userclass', $vn_userclass));
 			
 			
 			$vs_sort_field = $this->request->getParameter('sort', pString);
 			$this->view->setVar('user_list', $t_user->getUserList(array('sort' => $vs_sort_field, 'sort_direction' => 'asc', 'userclass' => $vn_userclass)));

 			$this->render('user_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			$t_user = $this->getUserObject();
 			if ($this->request->getParameter('confirm', pInteger)) {
 				$t_user->setMode(ACCESS_WRITE);
 				$t_user->delete(false);

 				if ($t_user->numErrors()) {
 					foreach ($t_user->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
					}
 				} else {
 					$this->notification->addNotification(_t("Deleted user"), __NOTIFICATION_TYPE_INFO__);
 				}
 				$this->ListUsers();
 				return;
 			} else {
 				$this->render('user_delete_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		public function DownloadUserReport() {
 			$vs_download_format = $this->request->getParameter("download_format", pString);
 			if(!$vs_download_format){
 				$vs_download_format = "tab";
 			}
 			$this->view->setVar("download_format", $vs_download_format);
 			switch($vs_download_format){
 				default:
 				case "tab":
 					$this->view->setVar("file_extension", "txt");
 					$this->view->setVar("mimetype", "text/plain");
 					$vs_delimiter_col = "\t";
 					$vs_delimiter_row = "\n";
 				break;
 				# -----------------------------------
 				case "csv":
 					$this->view->setVar("file_extension", "txt");
 					$this->view->setVar("mimetype", "text/plain");
 					$vs_delimiter_col = ",";
 					$vs_delimiter_row = "\n";
 				break;
 				# -----------------------------------
 			}
 			
 			$o_db = new Db();
 			$t_user = new ca_users();
 			$va_fields = array("lname", "fname", "email", "user_name", "userclass", "active", "last_login");
 			$va_profile_prefs = $t_user->getValidPreferences('profile');
 			$va_profile_prefs_labels = array();
 			foreach($va_profile_prefs as $vs_pref) {
				$va_pref_info = $t_user->getPreferenceInfo($vs_pref);
				$va_profile_prefs_labels[$vs_pref] = $va_pref_info["label"];
			}
 			$qr_users = $o_db->query("SELECT * FROM ca_users ORDER BY user_id DESC");
 			if($qr_users->numRows()){
 				$va_rows = array();
 				# --- headings
 				$va_row = array();
 				# --- headings for field values
 				foreach($va_fields as $vs_field){
 					switch($vs_field){
 						case "last_login":
 							$va_row[] = _t("Last login");
 						break;
 						# --------------------
 						default:
 							$va_row[] = $t_user->getDisplayLabel("ca_users.".$vs_field);
 						break;
 						# --------------------
 					}
 				}
 				# --- headings for profile prefs
 				foreach($va_profile_prefs_labels as $vs_pref => $vs_pref_label){
 					$va_row[] = $vs_pref_label;
 				}
 				$va_rows[] = join($vs_delimiter_col, $va_row);
 				reset($va_fields);
 				reset($va_profile_prefs_labels);
 				$o_tep = new TimeExpressionParser();
 				while($qr_users->nextRow()){
 					$va_row = array();
 					# --- fields
 					foreach($va_fields as $vs_field){
						switch($vs_field){
							case "userclass":
								$va_row[] = $t_user->getChoiceListValue($vs_field, $qr_users->get("ca_users.".$vs_field));
							break;
							# -----------------------
							case "active":
								$va_row[] = ($qr_users->get("ca_users.".$vs_field) == 1) ? _t("active") : _t("not active");
							break;
							# -----------------------
							case "last_login":
								//if (!is_array($va_vars = $qr_users->getVars('vars'))) { $va_vars = array(); }
                                                                if (!is_array($va_vars = $qr_users->getVars('volatile_vars'))) { $va_vars = array(); }
                                                                
								if ($va_vars['last_login'] > 0) {
									$o_tep->setUnixTimestamps($va_vars['last_login'], $va_vars['last_login']);
									$va_row[] = $o_tep->getText();
								}else{
									$va_row[] = "-";
								}
								
							break;
							# -----------------------
							default:
								if($vs_download_format == "csv"){
									$va_row[] = str_replace(",", "-", $qr_users->get("ca_users.".$vs_field));
								}else{
									$va_row[] = $qr_users->get("ca_users.".$vs_field);
								}
							break;
							# -----------------------	
						}
					}
					# --- profile prefs
					foreach($va_profile_prefs_labels as $vs_pref => $vs_pref_label){
						$t_user->load($qr_users->get("ca_users.user_id"));
						$va_row[] = $t_user->getPreference($vs_pref);
					}
					$va_rows[] = join($vs_delimiter_col, $va_row);
 				}
 				$vs_file_contents = join($vs_delimiter_row, $va_rows);
 				$this->view->setVar("file_contents", $vs_file_contents);
 				return $this->render('user_report.php');
 			}else{
 				$this->notification->addNotification(_t("There are no users"), __NOTIFICATION_TYPE_INFO__);
 				$this->ListUsers();
 				return;
 			}
 		}
 		# -------------------------------------------------------
 		# Utilities
 		# -------------------------------------------------------
 		private function getUserObject($pb_set_view_vars=true, $pn_user_id=null) {
 			if (!($t_user = $this->pt_user)) {
				if (!($vn_user_id = $this->request->getParameter('user_id', pInteger))) {
					$vn_user_id = $pn_user_id;
				}
				$t_user = new ca_users($vn_user_id);
			}
 			if ($pb_set_view_vars){
 				$this->view->setVar('user_id', $vn_user_id);
 				$this->view->setVar('t_user', $t_user);
 			}
 			$this->pt_user = $t_user;
 			return $t_user;
 		}
 		# -------------------------------------------------------
 	}
 ?>