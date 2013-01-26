<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SetController.php : 
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
 	require_once(__CA_LIB_DIR__."/core/Controller/ActionController.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	
 	class SetController extends ActionController {
 		# -------------------------------------------------------
 		public function ListSets() {
 			JavascriptLoadManager::register('tableList');
 			$o_result_context = new ResultContext($this->request, 'ca_sets', 'basic_search');
 			
 			$t_set = new ca_sets();
			$this->view->setVar('t_set', $t_set);
			
 			if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction('can_administrate_sets')) {
				$ps_mode = $this->request->getParameter('mode', pString);
				if (strlen($ps_mode) > 0) {
					$pn_mode = (int)$ps_mode;
					$o_result_context->setParameter('set_display_mode', $pn_mode);
				} else {
					$pn_mode = (int)$o_result_context->getParameter('set_display_mode');
				}
				
				switch($pn_mode) {
					case 0:
					default:
						$va_set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__)), null, null, array());
						break;
					case 1:
						$va_set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'allUsers' => true)), null, null, array());
						break;
					case 2:
						$va_set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'publicUsers' => true)), null, null, array());
						break;
				}
			} else {
				$va_set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__)), null, null, array());
			}
			
			if ($va_set_list) {
				foreach ($va_set_list as $id => $va_set) {
					$va_set_list[$id]['can_delete'] = $this->UserCanDeleteSet($va_set['user_id']);
				}
			}
			$this->view->setVar('mode', $pn_mode);
			$this->view->setVar('set_list', $va_set_list);
 			
 			// get content types for sets
 			$this->view->setVar('table_list', caFilterTableList($t_set->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST')));
 			
 			$o_result_context->setAsLastFind();
 			$o_result_context->setResultList(array_keys($va_set_list));
			$o_result_context->saveContext();
				
 			$this->render('set_list_html.php');
 		}
 		# -------------------------------------------------------
		private function UserCanDeleteSet($user_id) {
			if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction('can_administrate_sets')) {
				return true;
			}
			$vb_can_delete = false;
			// If users can delete all sets, show Delete button
			if ($this->request->user->canDoAction('can_delete_sets')) {
				$vb_can_delete = true;
			}
			
			// If users can delete own sets, and this set belongs to them, show Delete button
			if ($this->request->user->canDoAction('can_delete_own_sets')) {
				if ($user_id == $this->request->getUserID()) {
					$vb_can_delete = true;
				}
			}
			return $vb_can_delete;
		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_set = new ca_sets($vn_set_id = $this->request->getParameter('set_id', pInteger));
 			
 			$va_set_stats = array('mine' => caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__)), null, null, array()));
 			if ($this->request->user->canDoAction('is_administrator') || $this->request->user->canDoAction('can_administrate_sets')) {
 				$va_set_stats['user'] = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'allUsers' => true)), null, null, array());
 				$va_set_stats['public'] = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'publicUsers' => true)), null, null, array());
 			}
 			
 			$o_result_context = new ResultContext($this->request, 'ca_sets', 'basic_search');
 			$pn_mode = (int)$o_result_context->getParameter('set_display_mode');
			$this->view->setVar('mode', $pn_mode);
 			$this->view->setVar('sets', $va_set_stats);
 			
 			return $this->render('widget_set_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>