<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SearchFormController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
	require_once(__CA_MODELS_DIR__."/ca_search_forms.php");
	require_once(__CA_MODELS_DIR__.'/ca_bundle_display_placements.php'); 
	require_once(__CA_MODELS_DIR__.'/ca_bundle_displays_x_user_groups.php'); 
 	
 	class SearchFormController extends ActionController {
 		# -------------------------------------------------------
 		public function ListForms() {
 			JavascriptLoadManager::register('tableList');
			
 			$t_form = new ca_search_forms();
 			$this->view->setVar('t_form', $t_form);
 			$this->view->setVar('form_list', $va_forms = caExtractValuesByUserLocale($t_form->getForms(array('user_id' => $this->request->getUserID(), 'access' => __CA_SEARCH_FORM_EDIT_ACCESS__)), null, null, array()));
 		
 			$o_result_context = new ResultContext($this->request, 'ca_search_forms', 'basic_search');
			$o_result_context->setAsLastFind();
			$o_result_context->setResultList(is_array($va_forms) ? array_keys($va_forms) : array());
			$o_result_context->saveContext();
			
 			$this->view->setVar('table_list', caFilterTableList($t_form->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST')));
 			
 			$this->render('search_form_list_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$o_dm = Datamodel::load();
 			$t_form = new ca_search_forms();
 			$this->view->setVar('form_count', $t_form->getFormCount(array('user_id' => $this->request->getUserID(), 'access' => __CA_SEARCH_FORM_EDIT_ACCESS__)));
 			
			
 			return $this->render('widget_search_form_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>