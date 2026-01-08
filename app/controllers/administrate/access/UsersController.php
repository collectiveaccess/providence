<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/access/UserController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");

class UsersController extends ActionController {
	# -------------------------------------------------------
	private $pt_user;
	private $opo_app_plugin_manager;
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	public function __construct(&$request, &$response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
		
		$this->opo_app_plugin_manager = new ApplicationPluginManager();
	}
	# -------------------------------------------------------
	public function Edit() {
		AssetLoadManager::register("bundleableEditor");
		$t_user = $this->getUserObject();
		
		$auth_config = Configuration::load('authentication.conf');
		$this->view->setVar('password_policies', $auth_config->getAssoc('password_policies') ?? []);
		$this->view->setVar('requireMinimumPasswordScore', (int)$auth_config->get('require_minimum_password_score'));
		
		$profile_prefs = $t_user->getValidPreferences('profile');
		if (is_array($profile_prefs) && sizeof($profile_prefs)) {
			$elements = [];
			foreach($profile_prefs as $pref) {
				$pref_info = $t_user->getPreferenceInfo($pref);
				$elements[$pref] = array('element' => $t_user->preferenceHtmlFormElement($pref), 'info' => $pref_info, 'label' => $pref_info['label']);
			}
			
			$this->view->setVar("profile_settings", $elements);
		}
		
		$this->render('user_edit_html.php');
	}
	# -------------------------------------------------------
	public function Save() {
		AssetLoadManager::register('tableList');
		
		$t_user = $this->getUserObject();
		
		$this->opo_app_plugin_manager->hookBeforeUserSaveData(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user));
		
		$send_activation_email = false;
		if($t_user->get("user_id") && $this->request->config->get("email_user_when_account_activated") && ($_REQUEST["active"] != $t_user->get("active"))){
			$send_activation_email = true;
		}
		foreach($t_user->getFormFields() as $f => $field_info) {
			// dont get/set password if backend doesn't support it
			if($f == 'password') {
				if(!strlen($_REQUEST[$f]) || !AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__)) {
					continue;
				}
			}
			$t_user->set($f, $_REQUEST[$f]);
			if ($t_user->numErrors()) {
				$this->request->addActionErrors($t_user->errors(), 'field_'.$f);
			}
		}
		
		if ($this->request->getParameter('entity_id', pInteger) == 0) {
			$t_user->set('entity_id', null);
		}

		if(AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__)) {
			if ($this->request->getParameter('password', pString) != $this->request->getParameter('password_confirm', pString)) {
				$this->request->addActionError(new ApplicationError(1050, _t("Password does not match confirmation. Please try again."), "administrate/UserController->Save()", '', false, false), 'field_password');
			}
		}
		
		AppNavigation::clearMenuBarCache($this->request);	// clear menu bar cache since changes may affect content
		
		if($this->request->numActionErrors() == 0) {
			if (!$t_user->getPrimaryKey()) {
				$t_user->insert();
				$message = _t("Added user");
			} else {
				$t_user->update();
				$message = _t("Saved changes to user");
			}
			
			$this->opo_app_plugin_manager->hookAfterUserSaveData(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user));
		
			if ($t_user->numErrors()) {
				foreach ($t_user->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			} else {
				// Save roles
				$set_user_roles = $this->request->getParameter('roles', pArray);
				if(!is_array($set_user_roles)) { $set_user_roles = []; }
				$existing_user_roles = $t_user->getUserRoles();
				$role_list = $t_user->getRoleList();
				
				foreach($role_list as $role_id => $role_info) {
					if (($existing_user_roles[$role_id] ?? null) && !in_array($role_id, $set_user_roles)) {
						// remove role
						$t_user->removeRoles($role_id);
						continue;
					}
					
					if (!($existing_user_roles[$role_id] ?? null) && in_array($role_id, $set_user_roles)) {
						// add role
						$t_user->addRoles($role_id);
						continue;
					}
				}
				
				// Save groups
				$set_user_groups = $this->request->getParameter('groups', pArray);
				if(!is_array($set_user_groups)) { $set_user_groups = []; }
				
				$existing_user_groups = $t_user->getUserGroups();
				$group_list = $t_user->getGroupList();
				
				foreach($group_list as $group_id => $group_info) {
					if (($existing_user_groups[$group_id] ?? null) && !in_array($group_id, $set_user_groups)) {
						// remove group
						$t_user->removeFromGroups($group_id);
						continue;
					}
					
					if (!($existing_user_groups[$group_id] ?? null) && in_array($group_id, $set_user_groups)) {
						// add group
						$t_user->addToGroups($group_id);
						continue;
					}
				}
				
				// Save profile prefs
				$profile_prefs = $t_user->getValidPreferences('profile');
				if (is_array($profile_prefs) && sizeof($profile_prefs)) {
					
					$this->opo_app_plugin_manager->hookBeforeUserSavePrefs(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user));
					
					$changed_prefs = [];
					foreach($profile_prefs as $pref) {
						if ($this->request->getParameter('pref_'.$pref, pString) != $t_user->getPreference($pref)) {
							$changed_prefs[$pref] = true;
						}
						$t_user->setPreference($pref, $this->request->getParameter('pref_'.$pref, pString));
					}
					
					$t_user->update();
					
					$this->opo_app_plugin_manager->hookAfterUserSavePrefs(array('user_id' => $t_user->getPrimaryKey(), 'instance' => $t_user, 'modified_prefs' => $changed_prefs));
				}
				
				if($send_activation_email){
					# --- send email confirmation
					$o_view = new View($this->request, array($this->request->getViewsDirectoryPath()));
	
					# -- generate email subject line from template
					$subject_line = $o_view->render("mailTemplates/account_activation_subject.tpl");
	
					# -- generate mail text from template - get both the text and the html versions
					$mail_message_text = $o_view->render("mailTemplates/account_activation.tpl");
					$mail_message_html = $o_view->render("mailTemplates/account_activation_html.tpl");
					caSendmail($t_user->get('email'), $this->request->config->get("ca_admin_email"), $subject_line, $mail_message_text, $mail_message_html, null, null, null, ['source' => 'Account activation']);						
				}

				$this->notification->addNotification($message, __NOTIFICATION_TYPE_INFO__);
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
		AssetLoadManager::register('tableList');
		if (!strlen($userclass = $this->request->getParameter('userclass', pString))) {
			$userclass = $this->request->user->getVar('ca_users_default_userclass');
		} else {
			$userclass = (int)$userclass;
			$this->request->user->setVar('ca_users_default_userclass', $userclass);
		}
		if ((!$userclass) || ($userclass < 0) || ($userclass > 255)) { $userclass = 0; }
		$t_user = $this->getUserObject();
		$this->view->setVar('userclass', $userclass);
		$this->view->setVar('userclass_displayname', $t_user->getChoiceListValue('userclass', $userclass));
		
		$sort_field = $this->request->getParameter('sort', pString) ?: 'lname';
		$limit = 25;
		
		$num_pages = $num_pages = ceil(($count = $t_user->getUserList(['count' => true, 'userclass' => $userclass]))/$limit);
		$page = $this->request->getParameter('page', pInteger) ?: 0;
		if($page > ($num_pages - 1)) { $page = 0; }
		if($page < 0) { $page = 0; }
		
		$this->view->setVar('num_pages', $num_pages);
		$this->view->setVar('page', $page);
		$this->view->setVar('count', $count);
	
		$this->view->setVar('user_list', $t_user->getUserList(['sort' => $sort_field, 'sort_direction' => 'asc', 'start' => $page * $limit, 'limit' => $limit, 'userclass' => $userclass]));

		$this->render('user_list_html.php');
	}
	# -------------------------------------------------------
	public function Delete() {
		$t_user = $this->getUserObject();
		if ($this->request->getParameter('confirm', pInteger)) {
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
		$download_format = $this->request->getParameter("download_format", pString);
		if(!$download_format){
			$download_format = "tab";
		}
		$this->view->setVar("download_format", $download_format);
		switch($download_format){
			default:
			case "tab":
				$this->view->setVar("file_extension", "txt");
				$this->view->setVar("mimetype", "text/plain");
				$delimiter_col = "\t";
				$delimiter_row = "\n";
			break;
			# -----------------------------------
			case "csv":
				$this->view->setVar("file_extension", "txt");
				$this->view->setVar("mimetype", "text/plain");
				$delimiter_col = ",";
				$delimiter_row = "\n";
			break;
			# -----------------------------------
		}
		
		$o_db = new Db();
		$t_user = new ca_users();
		$fields = array("lname", "fname", "email", "user_name", "userclass", "active", "last_login", "roles", "groups");
		$profile_prefs = $t_user->getValidPreferences('profile');
		$profile_prefs_labels = [];
		foreach($profile_prefs as $pref) {
			$pref_info = $t_user->getPreferenceInfo($pref);
			$profile_prefs_labels[$pref] = $pref_info["label"];
		}
		$qr_users = $o_db->query("
			SELECT * 
			FROM ca_users u
			ORDER BY u.user_id DESC
		");
		if($qr_users->numRows()){
			$rows = [];
			# --- headings
			$row = [];
			# --- headings for field values
			foreach($fields as $field){
				switch($field){
					# --------------------
					case "roles":
						$row[] = _t("Roles");
						break;
					# --------------------
					case "groups":
						$row[] = _t("Groups");
						break;
					# --------------------
					case "last_login":
						$row[] = _t("Last login");
						break;
					# --------------------
					default:
						$row[] = $t_user->getDisplayLabel("ca_users.{$field}");
						break;
					# --------------------
				}
			}
			# --- headings for profile prefs
			foreach($profile_prefs_labels as $pref => $pref_label){
				$row[] = $pref_label;
			}
			$rows[] = join($delimiter_col, $row);
			reset($fields);
			reset($profile_prefs_labels);
			$o_tep = new TimeExpressionParser();
			while($qr_users->nextRow()){
				$row = [];
				# --- fields
				foreach($fields as $field){
					switch($field){
						case "userclass":
							$row[] = $t_user->getChoiceListValue($field, $qr_users->get("ca_users.".$field));
							break;
						# -----------------------
						case "active":
							$row[] = ($qr_users->get("ca_users.{$field}") == 1) ? _t("active") : _t("not active");
							break;
						# -----------------------
						case "roles":
							$qr_roles = $o_db->query("SELECT r.name, r.code FROM ca_user_roles r INNER JOIN ca_users_x_roles AS cuxr ON cuxr.role_id = r.role_id WHERE cuxr.user_id = ?", [$qr_users->get('user_id')]);
							$row[] = join("; ", $qr_roles->getAllFieldValues("name"));
							break;
						# -----------------------
						case "groups":
							$qr_groups = $o_db->query("SELECT g.name, g.code FROM ca_user_groups g INNER JOIN ca_users_x_groups AS cuxg ON cuxg.group_id = g.group_id WHERE cuxg.user_id = ?", [$qr_users->get('user_id')]);
							$row[] = join("; ", $qr_groups->getAllFieldValues("name"));
							break;
						# -----------------------
						case "last_login":
							if (!is_array($vars = $qr_users->getVars('volatile_vars'))) { $vars = []; }
															
							if ($vars['last_login'] > 0) {
								$o_tep->setUnixTimestamps($vars['last_login'], $vars['last_login']);
								$row[] = $o_tep->getText();
							} else {
								$row[] = "-";
							}
							
							break;
						# -----------------------
						default:
							if($download_format == "csv"){
								$row[] = str_replace(",", "-", $qr_users->get("ca_users.".$field));
							} else {
								$row[] = $qr_users->get("ca_users.".$field);
							}
							break;
						# -----------------------	
					}
				}
				# --- profile prefs
				foreach($profile_prefs_labels as $pref => $pref_label){
					$t_user->load($qr_users->get("ca_users.user_id"));
					$row[] = $t_user->getPreference($pref);
				}
				$rows[] = join($delimiter_col, $row);
			}
			$file_contents = join($delimiter_row, $rows);
			$this->view->setVar("file_contents", $file_contents);
			return $this->render('user_report.php');
		} else {
			$this->notification->addNotification(_t("There are no users"), __NOTIFICATION_TYPE_INFO__);
			$this->ListUsers();
			return;
		}
	}
	# -------------------------------------------------------
	public function Approve() {
		$errors = [];
		$user_ids = $this->request->getParameter('user_id', pArray);
		$mode = $this->request->getParameter('mode', pString);
		if(is_array($user_ids) && (sizeof($user_ids) > 0)){
			$t_user = new ca_users();
			$send_activation_email = false;
			if($this->request->config->get("email_user_when_account_activated")){
				$send_activation_email = true;
			}
		
			foreach($user_ids as $user_id){
				$t_user->load($user_id);
				
				if (!$t_user->getPrimaryKey()) {
					$errors[] = _t("The user does not exist");
				}
			
				$t_user->set("active", 1);
				if($t_user->numErrors()){
					$errors[] = join("; ", $t_user->getErrors());
				} else {
					$t_user->update();
					if($t_user->numErrors()){
						$errors[] = join("; ", $t_user->getErrors());
					} else {
						# --- does a notification email need to be sent to the user to let them know account is active?
						if($send_activation_email){
							# --- send email confirmation
							$o_view = new View($this->request, array($this->request->getViewsDirectoryPath()));
	
							# -- generate email subject line from template
							$subject_line = $o_view->render("mailTemplates/account_activation_subject.tpl");
	
							# -- generate mail text from template - get both the text and the html versions
							$mail_message_text = $o_view->render("mailTemplates/account_activation.tpl");
							$mail_message_html = $o_view->render("mailTemplates/account_activation_html.tpl");
							caSendmail($t_user->get('email'), $this->request->config->get("ca_admin_email"), $subject_line, $mail_message_text, $mail_message_html, null, null, null, ['source' => 'Account activation']);						
						}
						
					}
				}
			
			}
			if(sizeof($errors) > 0){
				$this->notification->addNotification(implode("; ", $errors), __NOTIFICATION_TYPE_ERROR__);
			} else {
				$this->notification->addNotification(_t("The registrations have been approved"), __NOTIFICATION_TYPE_INFO__);
			}
		} else {
			$this->notification->addNotification(_t("Please use the checkboxes to select registrations for approval"), __NOTIFICATION_TYPE_WARNING__);
		}
		switch($mode){
			case "dashboard":
				$this->response->setRedirect(caNavUrl($this->request, "", "Dashboard", "Index"));
				break;
			# -----------------------
			default:
				$this->ListUsers();
				break;
			# -----------------------
		}
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function getUserObject($set_view_vars=true, $user_id=null) {
		$user_id = null;
		if (!($t_user = $this->pt_user)) {
			if (!($user_id = $this->request->getParameter('user_id', pInteger))) {
				$user_id = $user_id;
			}
			$t_user = new ca_users($user_id);
		}
		if ($set_view_vars){
			$this->view->setVar('user_id', $user_id);
			$this->view->setVar('t_user', $t_user);
		}
		$this->pt_user = $t_user;
		return $t_user;
	}
	# -------------------------------------------------------
}
