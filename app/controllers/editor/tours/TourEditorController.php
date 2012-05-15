<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/tors/TourEditorController.php : 
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
 
 	require_once(__CA_MODELS_DIR__."/ca_tours.php");
 	require_once(__CA_MODELS_DIR__."/ca_tour_stops.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/TourSearch.php");
 	
 
 	class TourEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_tours';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			$t_tour = $this->view->getVar('t_item');
 			
 			if ($t_tour->getPrimaryKey()) {
 			
 				$va_labels = $t_tour->getDisplayLabels();
 				$this->view->setVar('labels', $t_tour->getPrimaryKey() ? $va_labels : array());
 				$this->view->setVar('idno', $t_tour->get('idno'));
 			}
 			
 			$t_stop = new ca_tour_stops();
 			$t_stop->load(array('tour_id' => $t_tour->getPrimaryKey(), 'parent_id' => null));
 			$this->view->setVar('t_stop', $t_stop);
 			
 			if ($vn_stop_id = $t_stop->getPrimaryKey()) {
 				$va_children = caExtractValuesByUserLocaleFromHierarchyChildList(
 					$t_stop->getHierarchyChildren(null, array(
 						'additionalTableToJoin' => 'ca_tour_stop_labels',
						'additionalTableJoinType' => 'LEFT',
						'additionalTableSelectFields' => array('name', 'locale_id')
 					)
 				), 'stop_id', 'name', 'idno');
 				$this->view->setVar('children', $va_children);
 			} 
 			
 			return $this->render('widget_tour_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>