<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/representation_annotations/RepresentationAnnotationEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__."/core/Media.php");
 	require_once(__CA_LIB_DIR__."/core/Media/MediaProcessingSettings.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 	require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
 
 	class RepresentationAnnotationEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_representation_annotations';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			JavascriptLoadManager::register('panel');
 			parent::info($pa_parameters);
 			
 			$vn_annotation_id = (isset($pa_parameters['annotation_id'])) ? $pa_parameters['annotation_id'] : null;
 			$t_annotation = new ca_representation_annotations($vn_annotation_id);
 			$t_rep = new ca_object_representations($t_annotation->get('representation_id'));
 			
 			if ($vn_annotation_id) {
				$this->view->setVar('screen', $this->request->getActionExtra());	// name of screen
 				
				// find object editor screen with media bundle
				$t_ui = ca_editor_uis::loadDefaultUI('ca_object_representations', $this->request, $t_rep->getTypeID());
				$this->view->setVar('representation_editor_screen', $t_ui->getScreenWithBundle('ca_representation_annotations', $this->request));
 			}
 			
 			return $this->render('widget_representation_annotation_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>