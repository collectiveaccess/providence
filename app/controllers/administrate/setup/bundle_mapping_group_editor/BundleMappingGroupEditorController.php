<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/places/BindleMappingGroupEditorController.php : 
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
 	
 
 	class BundleMappingGroupEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_bundle_mapping_groups';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		protected function _initView() {
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('sortableUI');
 			JavascriptLoadManager::register('bundleListEditorUI');
 			
 			
 			if ($vn_rc =  parent::_initView()) { 		
 				$t_group = $this->view->getVar('t_subject');
 				$t_mapping = new ca_bundle_mappings($t_group->get('mapping_id'));
 				if (!$t_mapping->getPrimaryKey()) { die("Invalid mapping"); }
 				$va_groups = $t_mapping->getGroups();
 				
				$o_result_context = new ResultContext($this->request, 'ca_bundle_mapping_groups', 'basic_search');
				$o_result_context->setResultList(array_keys($va_groups));
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
 			$t_mapping = $this->view->getVar('t_item');
 			
 			if ($t_mapping->getPrimaryKey()) {
 			
 				$va_labels = $t_mapping->getDisplayLabels();
 				$this->view->setVar('labels', $t_mapping->getPrimaryKey() ? $va_labels : array());
 				$this->view->setVar('mapping_code', $t_mapping->get('mapping_code'));
 			}
 			
 			$t_group = new ca_bundle_mapping_groups();
 			$t_group->load(array('mapping_id' => $t_mapping->getPrimaryKey(), 'parent_id' => null));
 			$this->view->setVar('t_group', $t_group);
 			
 			return $this->render('widget_mapping_group_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>