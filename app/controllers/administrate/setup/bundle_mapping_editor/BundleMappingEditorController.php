<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/places/BundleMappingEditorController.php : 
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
 
 	require_once(__CA_MODELS_DIR__."/ca_bundle_mappings.php"); 
 	require_once(__CA_MODELS_DIR__."/ca_bundle_mapping_groups.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 
 	class BundleMappingEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_bundle_mappings';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		protected function _initView() {
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('sortableUI');
 			JavascriptLoadManager::register('bundleListEditorUI');
 			
 			$va_init = parent::_initView();
 			if (!$va_init[1]->getPrimaryKey()) {
 				$va_init[1]->set('table_num', $this->request->getParameter('table_num', pInteger));
 				$va_init[1]->set('target', $this->request->getParameter('target', pString));
 				$va_init[1]->set('direction', $this->request->getParameter('direction', pString));
 			}
 			return $va_init;
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
 			
 			$t_mapping = new ca_bundle_mappings();
 			$t_mapping->load(array('mapping_id' => $t_ui->getPrimaryKey(), 'parent_id' => null));
 			$this->view->setVar('t_mapping', $t_mapping);
 			
 			return $this->render('widget_mapping_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>