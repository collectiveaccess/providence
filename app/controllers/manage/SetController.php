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
 			$t_set = new ca_sets();
 			$this->view->setVar('t_set', $t_set);
 			$this->view->setVar('set_list', $va_set_list = caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__)), null, null, array()));
 			
 			// get content types for sets
 			$this->view->setVar('table_list', caFilterTableList($t_set->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST')));
 			//$t_set->htmlFormElement('table_num', '', array('id' => 'tableList'))
 			
 			$o_result_context = new ResultContext($this->request, 'ca_sets', 'basic_search');
 			$o_result_context->setAsLastFind();
 			$o_result_context->setResultList(array_keys($va_set_list));
			$o_result_context->saveContext();
				
 			$this->render('set_list_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_set = new ca_sets($vn_set_id = $this->request->getParameter('set_id', pInteger));
 			$this->view->setVar('sets', caExtractValuesByUserLocale($t_set->getSets(array('user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__)), null, null, array()));
 			
 			return $this->render('widget_set_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>