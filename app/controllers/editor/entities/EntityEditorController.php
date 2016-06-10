<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/objects/EntityEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_entities.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 
 	class EntityEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_entities';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			return $this->render('widget_entity_info_html.php', true);
 		}
 		# -------------------------------------------------------
		public function checkForDupeLabels() {
			require_once(__CA_MODELS_DIR__.'/ca_entity_labels.php');
			$t_entity = new ca_entities();

			$vs_form_prefix = $this->getRequest()->getParameter('fieldNamePrefix', pString);
			$vs_label_id = $this->getRequest()->getParameter('label_id', pString);

			$va_values = [];
			$vb_value_set = false;
			foreach($t_entity->getLabelUIFields() as $vs_label_field) {
				if($vs_label_field == 'displayname') { continue; }
				if ($vs_val = $this->getRequest()->getParameter($vs_form_prefix.$vs_label_field.'_'.$vs_label_id, pString)) {
					$va_values[$vs_label_field] = $vs_val;
					$vb_value_set = true;
				} else {
					$va_values[$vs_label_field] = '';
				}
			}

			$vb_dupe = false;
			if($vb_value_set) {
				if($t_entity->checkForDupeLabel(null, $va_values, false)) {
					$vb_dupe = true;
				}
			}

			$this->getView()->setVar('dupe', $vb_dupe);

			return $this->render('lookup_dupe_labels_json.php');
		}
		# -------------------------------------------------------
 	}
