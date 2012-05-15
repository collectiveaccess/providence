<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/collections/ObjectLotEditorController.php : 
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
 
 	require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 
 	class ObjectLotEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_object_lots';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			return $this->render('widget_object_lot_info_html.php', true);
 		}
 		# -------------------------------------------------------
 		protected function _beforeDelete($pt_subject) {
			// Does this lot have any ca_object records associated?
			if ($vn_c = $pt_subject->numObjects()) {
				// Yes... we don't want the cascading delete of the lot to delete the objects
				// so we need to remove them from the lot *before* doing the lot deletion
				// Since we're going to be doing this in a transaction, if the lot delete fails out
				// unlinking of the lot's objects will be rolled back
				
				$pt_subject->removeAllObjects();
				
				if ($pt_subject->numErrors()) {
					foreach($pt_subject->errors() as $o_e) {
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);	
					}
				}
			}	
			return true;
		}
 		# -------------------------------------------------------
 		public function renumberObjects() {
 			if ((bool)$this->request->config->get('allow_automated_renumbering_of_objects_in_a_lot')) {
				$t_lot = new ca_object_lots($this->request->getParameter('lot_id', pInteger));
				if ($t_lot->getPrimaryKey()) {
					$t_lot->renumberObjects($this->opo_app_plugin_manager);
					
					if ($t_lot->numErrors()) {
						foreach($t_lot->getErrors() as $vs_error) {
							$this->notification->addNotification($vs_error, __NOTIFICATION_TYPE_ERROR__);
						}
					} else {
						$this->notification->addNotification(_t('Renumbered contents of lot'), __NOTIFICATION_TYPE_INFO__);
					}
				}
			}
 			
 			return $this->Edit();
 		}
 		# -------------------------------------------------------
 	}
 ?>