<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseRefineableSearchController.php : 
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
 * @package CollectiveAccess
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
	require_once(__CA_LIB_DIR__."/ca/BaseFindController.php");
	
	class BaseRefineableSearchController extends BaseFindController {
		# -------------------------------------------------------
 		/**
 		 * Browse engine used to wrap searches. The browse "wrapper" provides for "refine search" functionality
 		 */
 		protected $opo_browse;
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Facets() {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$this->opo_browse->loadFacetContent(array('checkAccess' => $va_access_values));
			$this->view->setVar('browse', $this->opo_browse);
			
 			$this->render("Search/ajax_refine_facets_html.php");
 		}
		# -------------------------------------------------------
 		public function getFacet() {
 			$va_access_values = caGetUserAccessValues($this->request);
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			
 			if ($this->request->getParameter('clear', pInteger)) {
 				$this->opo_browse->removeAllCriteria();
 				$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 				
 				$this->opo_result_context->setSearchExpression($this->opo_browse->getBrowseID());
 				$this->opo_result_context->saveContext();
 			} else {
 				if ($this->request->getParameter('modify', pString)) {
 					$vm_id = $this->request->getParameter('id', pString);
 					$this->opo_browse->removeCriteria($ps_facet_name, array($vm_id));
 					$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 					
 					$this->view->setVar('modify', $vm_id);
 				}
 			}
 			
 			$va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
 			
 			$this->view->setVar('facet', $va_facet); // leave as is for old pawtucket views
 			$this->view->setVar('facet_info', $va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name));
 			$this->view->setVar('facet_name', $ps_facet_name);
 			$this->view->setVar('grouping', $vs_grouping = $this->request->getParameter('grouping', pString));

 			// this should be 'facet' but we don't want to render all old 'ajax_refine_facet_html' views (pawtucket themes) unusable
 			$this->view->setVar('grouped_facet',$this->opo_browse->getFacetWithGroups($ps_facet_name, $va_facet_info["group_mode"], $vs_grouping, array('sort' => 'name', 'checkAccess' => $va_access_values)));
 			
 			// generate type menu and type value list for related authority table facet
 			if ($va_facet_info['type'] === 'authority') {
				$t_model = $this->opo_datamodel->getTableInstance($va_facet_info['table']);
				if (method_exists($t_model, "getTypeList")) {
					$this->view->setVar('type_list', $t_model->getTypeList());
				}
				
				$t_rel_types = new ca_relationship_types();
				$this->view->setVar('relationship_type_list', $t_rel_types->getRelationshipInfo($va_facet_info['relationship_table']));
				
				$this->view->setVar('t_item', $t_model);
				$this->view->setVar('t_subject', $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true));
			}
			
 			$this->render('Search/ajax_refine_facet_html.php');
 		}
 		# -------------------------------------------------------
 		public function addCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->addCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			
 			$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function modifyCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('mod_id', pString)));
 			$this->opo_browse->addCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			
 			$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		public function removeCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			
 			$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Callbacks:
 		 * 		hookAfterClearCriteria() is called after clearing criteria. The first parameter is the BrowseEngine object containing the search.
 		 */
 		public function clearCriteria() {
 			if(is_array($va_criteria = $this->opo_browse->getCriteria())) {
				foreach($va_criteria as $vs_facet_name => $va_facet_info) {
					if ($vs_facet_name === '_search') { continue; }		// never delete base search
					$this->opo_browse->removeCriteria($vs_facet_name, array_keys($va_facet_info));
				}
			}
 			if (method_exists($this, "hookAfterClearCriteria")) {
				$this->hookAfterClearCriteria($this->opo_browse);
			}
 			$this->view->setVar('open_refine_controls', true);
 			$this->Index();
 		}
 		# -------------------------------------------------------
	}
?>