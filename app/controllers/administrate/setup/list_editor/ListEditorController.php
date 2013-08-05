<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/list_editor/ListEditorController.php : 
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
 
 	require_once(__CA_MODELS_DIR__."/ca_lists.php");
 	require_once(__CA_MODELS_DIR__."/ca_list_items.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/ListSearch.php");
 	
 
 	class ListEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_lists';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		public function Edit($pa_values = NULL, $pa_options = NULL) {
 			parent::Edit($pa_values, $pa_options);
 			
 			// Set last browse ID so when we return to the ca_list_items hierarchy browser from 
 			// the editing screen it defaults to the list we just edited
 			$vn_list_id = $this->request->getParameter('list_id', pInteger);
 			$t_list_item = new ca_list_items();
 			if ($t_list_item->load(array('list_id' => $vn_list_id, 'parent_id' => null))) {		// root ca_list_item record for this ca_list record
 				$this->request->session->setVar('ca_list_items_browse_last_id', $t_list_item->getPrimaryKey());
 			}
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			$t_list = $this->view->getVar('t_item');
 			
 			if ($t_list->getPrimaryKey()) {
 			
 				$va_labels = $t_list->getDisplayLabels();
 				$this->view->setVar('labels', $t_list->getPrimaryKey() ? $va_labels : array());
 				$this->view->setVar('idno', $t_list->get('idno'));
 			}
 			
 			$t_list_item = new ca_list_items();
 			$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
 			$this->view->setVar('t_list_item', $t_list_item);
 			
 			if ($vn_item_id = $t_list_item->getPrimaryKey()) {
 				$va_children = caExtractValuesByUserLocaleFromHierarchyChildList(
 					$t_list_item->getHierarchyChildren(null, array(
 						'additionalTableToJoin' => 'ca_list_item_labels',
						'additionalTableJoinType' => 'LEFT',
						'additionalTableSelectFields' => array('name_plural', 'locale_id'),
						'additionalTableWheres' => array('(ca_list_item_labels.is_preferred = 1 OR ca_list_item_labels.is_preferred IS NULL)')
 					)
 				), 'item_id', 'name_plural', 'item_value');
 				$this->view->setVar('children', $va_children);
 			} 
 			
 			return $this->render('widget_list_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>
