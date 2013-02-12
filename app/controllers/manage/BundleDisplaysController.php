<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/BundleDisplaysController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
	require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
	
 	class BundleDisplaysController extends ActionController {
 		# -------------------------------------------------------
 		public function ListDisplays() {
 			JavascriptLoadManager::register('tableList');
			
 			$t_display = new ca_bundle_displays();
 			$this->view->setVar('t_display', $t_display);
 			$this->view->setVar('display_list', $va_displays = caExtractValuesByUserLocale($t_display->getBundleDisplays(array('user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)), null, null, array()));
 		
 			$o_result_context = new ResultContext($this->request, 'ca_bundle_displays', 'basic_search');
			$o_result_context->setAsLastFind();
			$o_result_context->setResultList(is_array($va_displays) ? array_keys($va_displays) : array());
			$o_result_context->saveContext();
			
 			$this->view->setVar('table_list', caFilterTableList($t_display->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST')));
 			
 			$this->render('bundle_display_list_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns display_id of display to work with
 		 * Will use what it finds in request parameter 'display_id', and if that doesn't exist
 		 * then it will use the last used value of display_id pulled from the current login's state variables
 		 */
 		private function _getDisplayID() {
 			return $this->request->getParameter('display_id', pInteger);
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$o_dm = Datamodel::load();
 			
 			$t_display = new ca_bundle_displays($vn_display_id = $this->_getDisplayID());
 			$this->view->setVar('bundle_displays', caExtractValuesByUserLocale($t_display->getBundleDisplays(array('user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_EDIT_ACCESS__)), null, array()));
 			
 			return $this->render('widget_bundle_display_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>