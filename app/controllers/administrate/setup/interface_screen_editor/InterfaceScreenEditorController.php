<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/places/InterfaceScreenEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php"); 
 	require_once(__CA_MODELS_DIR__."/ca_editor_ui_screens.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 
 	class InterfaceScreenEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_editor_ui_screens';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		protected function _initView($pa_options=null) {
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('sortableUI');
 			JavascriptLoadManager::register('bundleListEditorUI');
 			
 			
 			if ($vn_rc =  parent::_initView()) { 		
 				$t_screen = $this->view->getVar('t_subject');
 				$t_ui = new ca_editor_uis($t_screen->get('ui_id'));
 				if (!$t_ui->getPrimaryKey()) { die("Invalid ui"); }
 				$va_screens = $t_ui->getScreens(null, null, array('showAll' => true));
 				
				$o_result_context = new ResultContext($this->request, 'ca_editor_ui_screens', 'basic_search');
				$o_result_context->setResultList(array_keys($va_screens));
				$o_result_context->setAsLastFind();
				$o_result_context->saveContext();
 			}
 			return $vn_rc;
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			$t_ui = $this->view->getVar('t_item');
 			
 			if ($t_ui->getPrimaryKey()) {
 			
 				$va_labels = $t_ui->getDisplayLabels();
 				$this->view->setVar('labels', $t_ui->getPrimaryKey() ? $va_labels : array());
 				$this->view->setVar('idno', $t_ui->get('idno'));
 			}
 			
 			$t_ui_item = new ca_editor_ui_screens();
 			$t_ui_item->load(array('ui_id' => $t_ui->getPrimaryKey(), 'parent_id' => null));
 			$this->view->setVar('t_ui_item', $t_ui_item);
 			
 			return $this->render('widget_interface_screen_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>