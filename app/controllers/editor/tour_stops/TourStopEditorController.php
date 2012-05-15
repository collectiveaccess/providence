<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/setup/tour_stops/TourStopEditorController.php : 
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
 	require_once(__CA_MODELS_DIR__."/ca_tour_stops.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 
 	class TourStopEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_tour_stops';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		public function Edit() {
 			$o_result_context = new ResultContext($this->request, 'ca_tour_stops', 'basic_search');
 			
 			$va_cur_result = $o_result_context->getResultList();
 			$vn_id = $this->request->getParameter('stop_id', pInteger);
 			
 			
 			if (is_array($va_cur_result) && !in_array($vn_id, $va_cur_result)) {
				//
				// Set "results list" navigation to all items in the same level as the currently selected item
				//
				$t_instance = new ca_list_items();
				if (is_array($va_siblings = $t_instance->getHierarchySiblings($vn_id, array('idsOnly' => true)))) {
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
 			if ($t_item = $this->view->getVar('t_item')) {
 				if (!($vn_tour_id = $t_item->get('tour_id'))) {
 					$t_parent = new ca_list_items($this->request->getParameter('parent_id', pInteger));
 					$vn_tour_id = $t_parent->get('tour_id');
 				}
 			}
 			$t_tour = new ca_tours($vn_tour_id);
 			$this->view->setVar('t_tour', $t_tour);
 			return $this->render('widget_tour_stop_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>