<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/relationship_type_editor/RelationshipTypeEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 	require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/RelationshipTypeSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
 	
 
 	class RelationshipTypeEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_relationship_types';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		public function Edit($pa_values=null, $pa_options=null) {
 			$o_result_context = new ResultContext($this->request, 'ca_relationship_types', 'basic_search');
 			
 			$va_cur_result = $o_result_context->getResultList();
 			$vn_id = $this->request->getParameter('type_id', pInteger);
 			$vn_parent_id = $this->request->getParameter('parent_id', pInteger);
 				
 			// If we're creating a new record we'll need to establish the table_num
 			// from the parent (there's always a parent)
 			if (!$vn_id) {
 				$t_parent = new ca_relationship_types($vn_parent_id);
 				if (!$t_parent->getPrimaryKey()) {
 					$this->postError(1230, _t("Invalid parent"),"RelationshipTypeEditorController->Edit()");
 					return;
 				}
 				$this->request->setParameter('table_num', $t_parent->get('table_num'));
 			}
 			
 			if (!is_array($va_cur_result) || !in_array($vn_id, $va_cur_result)) {
				//
				// Set "results list" navigation to all types in the same level as the currently selected type
				//
				$t_instance = new ca_relationship_types();
				if (is_array($va_siblings = $t_instance->getHierarchySiblings($this->request->getParameter('type_id', pInteger), array('idsOnly' => true)))) {
					$o_result_context->setResultList($va_siblings);
					$o_result_context->saveContext();
				}
			}
			
 			parent::Edit();
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			
			$o_dm = Datamodel::load();
 			
 			$t_item = $this->view->getVar('t_item');
 			$vn_item_id = $t_item->getPrimaryKey();
 			
 			// get parent items
			$va_ancestors = array_reverse(caExtractValuesByUserLocaleFromHierarchyAncestorList(
				$t_item->getHierarchyAncestors(null, array(
					'additionalTableToJoin' => 'ca_relationship_type_labels',
					'additionalTableJoinType' => 'LEFT',
					'additionalTableSelectFields' => array('typename', 'typename_reverse', 'description', 'description_reverse', 'locale_id'),
					'includeSelf' => false
				)
			), 'type_id', 'typename', 'type_code'));
			
			
			$vn_rel_table_num = $t_item->get('table_num');
			
 			array_shift($va_ancestors); // get rid of hierarchy root record, which should not be displayed
 			$this->view->setVar('ancestors', $va_ancestors);
 			
 			
 			return $this->render('widget_relationship_type_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>