<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/GroupsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

 	class GroupsController extends ActionController {
 		# -------------------------------------------------------
 		private $pt_group;
 		
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Edit() {
			$o_result_context = new ResultContext($this->request, 'ca_user_groups', 'basic_search');
			$o_result_context->setAsLastFind();
			
 			if (!($t_group = $this->getGroupObject())) {
 				
 				$this->notification->addNotification(_t("You cannot edit this group"), __NOTIFICATION_TYPE_ERROR__);	
 				$this->render('group_list_html.php');
 				return;
 			}
 			
 			if (!is_array($va_result_list = $o_result_context->getResultList())) { $va_result_list = array(); }
 			if (!in_array($t_group->getPrimaryKey(), $va_result_list)) {
				$va_groups = $t_group->getGroupList('name', 'asc', $this->request->user->getUserID());
				$o_result_context->setResultList(is_array($va_groups) ? array_keys($va_groups) : array());
			}
			$o_result_context->saveContext();
			
 			$this->render('group_edit_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			JavascriptLoadManager::register('tableList');
 			
 			if (!($t_group = $this->getGroupObject())) {
 				$this->notification->addNotification(_t("You cannot edit this group"), __NOTIFICATION_TYPE_ERROR__);	
 				$this->render('group_list_html.php');
 				return;
 			}
 			
 			$t_group->setMode(ACCESS_WRITE);
 			foreach($t_group->getFormFields() as $vs_f => $va_field_info) {
				if ($vs_f == 'code') { continue; }
				if ($vs_f == 'user_id') { continue; }
 				$t_group->set($vs_f, $_REQUEST[$vs_f]);
 				if ($t_group->numErrors()) {
 					$this->request->addActionErrors($t_group->errors(), 'field_'.$vs_f);
 				}
 			}
 			$t_group->set('user_id', $this->request->user->getUserID());
 			$t_group->set('code', $this->request->user->getUserID().'_'.substr(preg_replace('![^A-Za-z0-9]+!', '_', $_REQUEST['name']), 0, 10));

 			if ($this->request->getParameter('password', pString) != $this->request->getParameter('password_confirm', pString)) {
 				$this->request->addActionError(new Error(1050, _t("Password does not match confirmation. Please try again."), "manage/GroupsController->Save()", '', false, false), 'field_password');
 			} 
 			
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
					// Save users
					$va_users = $this->request->getParameter('group_users', pArray);
					if(!is_array($va_users)) { $va_users = array(); }
					
					$va_existing_group_users = $t_group->getGroupUsers();
					$va_user_list = $this->request->user->getUserList(array('sort' => 'lname', 'sort_direction' => 'asc', 'userclass' => array(0, 1)));	//userclass 0 ="back-end" users; 1=front-end users
					
					foreach($va_user_list as $vn_user_id => $va_user_info) {
						if ($va_existing_group_users[$vn_user_id] && !in_array($vn_user_id, $va_users)) {
							// remove user
							$t_group->removeUsers($vn_user_id);
							continue;
						}
						
						if (!$va_existing_group_users[$vn_user_id] && in_array($vn_user_id, $va_users)) {
							// add user
							$t_group->addUsers($vn_user_id);
							continue;
						}
					}
					
					
				}
			} else {
				$this->notification->addNotification(_t("Your entry has errors. See below for details."), __NOTIFICATION_TYPE_ERROR__);
			}

			if ($this->request->numActionErrors()) {
				$this->render('group_edit_html.php');
			} else {
				// success
 				$this->view->setVar('group_list', $t_group->getGroupList('name', 'asc', $this->request->user->getUserID()));

 				$this->render('group_list_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		public function ListGroups() {
 			JavascriptLoadManager::register('tableList');
 			$t_group = new ca_user_groups();
 			$vs_sort_field = $this->request->getParameter('sort', pString);
 			$this->view->setVar('group_list', $t_group->getGroupList('name', 'asc', $this->request->user->getUserID()));

 			$this->render('group_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			if (!($t_group = $this->getGroupObject())) {
 				$this->notification->addNotification(_t("You cannot delete this group"), __NOTIFICATION_TYPE_ERROR__);	
 				$this->render('group_list_html.php');
 				return;
 			}
 			
 			if ($this->request->getParameter('confirm', pInteger)) {
 				$t_group->setMode(ACCESS_WRITE);
 				$t_group->delete(true);

 				if ($t_group->numErrors()) {
 					foreach ($t_group->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
					}
 				}
 				
 				$o_result_context = new ResultContext($this->request, 'ca_user_groups', 'basic_search');
				$o_result_context->setAsLastFind();
				$o_result_context->setResultList(array());
				$o_result_context->saveContext();
 				
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
			
			// Check if user actually owns the specified object
			if ($t_group->getPrimaryKey() && ($t_group->get('user_id') != $this->request->user->getUserID())) { return false; }
			
 			if ($pb_set_view_vars){
 				$this->view->setVar('group_id', $vn_group_id);
 				$this->view->setVar('t_group', $t_group);
 			}
 			$this->pt_group = $t_group;
 			return $t_group;
 		}
 		# -------------------------------------------------------
 		# Info
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$o_dm = Datamodel::load();
 			
 			$t_group = new ca_user_groups();
 			$this->view->setVar('group_count', $t_group->getGroupCount($this->request->user->getUserID()));
 			
 			if ($t_group = $this->getGroupObject()) {
 				$this->view->setVar('t_item', $t_group);
 				$this->view->setVar('result_context', $o_result_context = new ResultContext($this->request, 'ca_user_groups', 'basic_search'));
 				
 			}
 			
 			return $this->render('widget_group_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>