<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/access/GroupsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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

 	require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');

 	class GroupsController extends ActionController {
 		# -------------------------------------------------------
 		private $pt_group;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Edit() {
 			$t_group = $this->getGroupObject();

 			$this->render('group_edit_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_group = $this->getGroupObject();
 			$t_group->setMode(ACCESS_WRITE);
 			foreach($t_group->getFormFields() as $vs_f => $va_field_info) {
 				$t_group->set($vs_f, $_REQUEST[$vs_f]);
 				if ($t_group->numErrors()) {
 					$this->request->addActionErrors($t_group->errors(), 'field_'.$vs_f);
 				}
 			}
 			
 			$t_group->set('user_id', null);

 			if ($this->request->getParameter('password', pString) != $this->request->getParameter('password_confirm', pString)) {
 				$this->request->addActionError(new Error(1050, _t("Password does not match confirmation. Please try again."), "administrate/GroupsController->Save()", '', false, false), 'field_password');
 			} 
 			
 			AppNavigation::clearMenuBarCache($this->request);	// clear menu bar cache since changes may affect content
 			
 			if($this->request->numActionErrors() == 0) {
				if (!$t_group->getPrimaryKey()) {
					$t_group->insert();
					$vs_message = _t("Added group");
				} else {
					$t_group->update();
					$vs_message = _t("Saved changes to group");
				}

				if ($t_group->numErrors()) {
					foreach ($t_group->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				} else {
					// Save roles
					$va_set_group_roles = $this->request->getParameter('roles', pArray);
					if(!is_array($va_set_group_roles)) { $va_set_group_roles = array(); }
					
					$va_existing_group_roles = $t_group->getGroupRoles();
					$va_role_list = $t_group->getRoleList();
					
					foreach($va_role_list as $vn_role_id => $va_role_info) {
						if ($va_existing_group_roles[$vn_role_id] && !in_array($vn_role_id, $va_set_group_roles)) {
							// remove role
							$t_group->removeRoles($vn_role_id);
							continue;
						}
						
						if (!$va_existing_group_roles[$vn_role_id] && in_array($vn_role_id, $va_set_group_roles)) {
							// add role
							$t_group->addRoles($vn_role_id);
							continue;
						}
					}
					
					$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
				}
			} else {
				$this->notification->addNotification(_t("Your entry has errors. See below for details."), __NOTIFICATION_TYPE_ERROR__);
			}

			if ($this->request->numActionErrors()) {
				$this->render('group_edit_html.php');
			} else {
				// success
 				$this->view->setVar('group_list', $t_group->getGroupList());

 				$this->render('group_list_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		public function ListGroups() {
 			JavascriptLoadManager::register('tableList');
 			
 			$t_group = $this->getGroupObject();
 			$vs_sort_field = $this->request->getParameter('sort', pString);
 			$this->view->setVar('group_list', $t_group->getGroupList($vs_sort_field, 'asc'));

 			$this->render('group_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			$t_group = $this->getGroupObject();
 			if ($this->request->getParameter('confirm', pInteger)) {
 				$t_group->setMode(ACCESS_WRITE);
 				$t_group->delete(true);

 				if ($t_group->numErrors()) {
 					foreach ($t_group->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
					}
 				}
 				$this->ListGroups();
 				return;
 			} else {
 				$this->render('group_delete_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		# Utilities
 		# -------------------------------------------------------
 		private function getGroupObject($pb_set_view_vars=true, $pn_group_id=null) {
 			if (!($t_group = $this->pt_group)) {
				if (!($vn_group_id = $this->request->getParameter('group_id', pInteger))) {
					$vn_group_id = $pn_group_id;
				}
				$t_group = new ca_user_groups($vn_group_id);
			}
 			if ($pb_set_view_vars){
 				$this->view->setVar('group_id', $vn_group_id);
 				$this->view->setVar('t_group', $t_group);
 			}
 			$this->pt_group = $t_group;
 			return $t_group;
 		}
 		# -------------------------------------------------------
 	}
 ?>