<?php
/* ----------------------------------------------------------------------
 * DashboardController.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/DashboardManager.php');
 
 	class DashboardController extends ActionController {
 		# -------------------------------------------------------
		
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_dashboard_manager = DashboardManager::load($po_request);
 		}
 		# -------------------------------------------------------
 		public function index() {
 			$this->render('dashboard/dashboard_html.php');
 		}
 		# -------------------------------------------------------
 		public function getAvailableWidgetList() {
 			$this->view->setVar('widget_manager', new WidgetManager());
 			$this->render('dashboard/available_widget_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function addWidget() {
 			if ($ps_widget = $this->request->getParameter('widget', pString)) {
 				if (!($pn_col = $this->request->getParameter('col', pInteger))) { $pn_col = 1; }
 				$this->opo_dashboard_manager->addWidget($ps_widget, (int)$pn_col);
 			}
 			$this->render('dashboard/dashboard_html.php');
 		}
 		# -------------------------------------------------------
 		public function removeWidget() {
 			if ($ps_widget_id = $this->request->getParameter('widget_id', pString)) {
 				$this->opo_dashboard_manager->removeWidget($ps_widget_id);
 			}
 			$this->render('dashboard/dashboard_html.php');
 		}
 		# -------------------------------------------------------
 		public function moveWidgets() {
 			$va_move_info = array(
 				1 => explode(';', $this->request->getParameter('sort_column1', pString)),
 				2 => explode(';', $this->request->getParameter('sort_column2', pString))
 			);
 			$this->opo_dashboard_manager->moveWidgets($va_move_info);
 			
 			$this->render('dashboard/move_widgets_json.php');
 		}
 		# -------------------------------------------------------
 		public function clear() {
 			$this->opo_dashboard_manager->clearDashboard();
 			$this->render('dashboard/dashboard_html.php');
 		}
 		# -------------------------------------------------------
 		# Ajax-based settings
 		# -------------------------------------------------------
 		public function getSettingsForm() {
 			if ($ps_widget_id = $this->request->getParameter('widget_id', pString)) {
 				$this->view->setVar('widget_id', $ps_widget_id);
 				
 				$this->view->setVar('form', $this->opo_dashboard_manager->getWidgetSettingsFormHTML($ps_widget_id));
 				
 				$this->render('dashboard/ajax_settings_html.php');			
 			}
 		}
 		# -------------------------------------------------------
 		public function getWidget() {		
			if ($ps_widget_id = $this->request->getParameter('widget_id', pString)) {
				$va_widget_info = $this->opo_dashboard_manager->getWidgetByID($ps_widget_id);
			}
 				
			if ($va_widget_info) {		
				$this->view->setVar('widget_id', $va_widget_info['widget_id']);
				$this->response->addContent($this->opo_dashboard_manager->renderWidget($va_widget_info['widget'], $va_widget_info['widget_id'], $va_widget_info['settings']));
			}
 		}
 		# -------------------------------------------------------
 		public function saveSettings() {
 			if ($ps_widget_id = $this->request->getParameter('widget_id', pString)) {
 				$this->view->setVar('widget_id', $ps_widget_id);
 				
 				$this->opo_dashboard_manager->saveWidgetSettings($ps_widget_id);
 				$this->getWidget();
 			}
 		}
 		# -------------------------------------------------------
 	}
 ?>