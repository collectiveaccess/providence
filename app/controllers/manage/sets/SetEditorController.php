<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SetEditorController.php : 
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
 
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 
 	class SetEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_sets';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			// check access to set - if user doesn't have edit access we bail
 			$t_set = new ca_sets($po_request->getParameter('set_id', pInteger));
 			if (!$t_set->haveAccessToSet($po_request->getUserID(), __CA_SET_EDIT_ACCESS__)) {
 				$this->postError(2320, _t("Access denied"), "RequestDispatcher->dispatch()");
 			}
 		}
 		# -------------------------------------------------------
 		protected function _initView() {
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('sortableUI');
 			$va_init = parent::_initView();
 			if (!$va_init[1]->getPrimaryKey()) {
 				$va_init[1]->set('user_id', $this->request->getUserID());
 				$va_init[1]->set('table_num', $this->request->getParameter('table_num', pInteger));
 			}
 			return $va_init;
 		}
 		# -------------------------------------------------------
 		public function Edit() {
 			parent::Edit();
 		}
 		# -------------------------------------------------------
 		# Ajax handlers
 		# -------------------------------------------------------
 		public function GetItemInfo() {
 			if ($pn_set_id = $this->request->getParameter('set_id', pInteger)) {
				$t_set = new ca_sets($pn_set_id);
				
				if (!$t_set->getPrimaryKey()) {
					$this->notification->addNotification(_t("The set does not exist"), __NOTIFICATION_TYPE_ERROR__);	
					return;
				}
				
				// does user have edit access to set?
				if (!$t_set->haveAccessToSet($this->request->getUserID(), __CA_SET_EDIT_ACCESS__)) {
					$this->notification->addNotification(_t("You cannot edit this set"), __NOTIFICATION_TYPE_ERROR__);
					$this->Edit();
					return;
				}
				$pn_table_num = $t_set->get('table_num');
				$vn_set_item_count = $t_set->getItemCount(array('user_id' => $this->request->getUserID()));
			} else {
				$pn_table_num = $this->request->getParameter('table_num', pInteger);
				$vn_set_item_count = 0;
			}
 			
 			$pn_row_id = $this->request->getParameter('row_id', pInteger);
 			
 			$t_row = $this->opo_datamodel->getInstanceByTableNum($pn_table_num, true);
 			if (!($t_row->load($pn_row_id))) {
 				$va_errors[] = _t("Row_id is invalid");
 			}
 			
 			$this->view->setVar('errors', $va_errors);
 			$this->view->setVar('set_id', $pn_set_id);
 			$this->view->setVar('item_id', $pn_row_id);
 			$this->view->setVar('idno', $t_row->get($t_row->getProperty('ID_NUMBERING_ID_FIELD')));
 			$this->view->setVar('idno_sort', $t_row->get($t_row->getProperty('ID_NUMBERING_SORT_FIELD')));
 			$this->view->setVar('set_item_label', $t_row->getLabelForDisplay(false));
 			
 			$this->view->setVar('representation_tag', '');
 			if (method_exists($t_row, 'getRepresentations')) {
 				if ($vn_set_item_count > 50) {
					$vs_thumbnail_version = 'tiny';
				}else{
					$vs_thumbnail_version = "thumbnail";
				}
 				if(sizeof($va_reps = $t_row->getRepresentations(array($vs_thumbnail_version)))) {
 					$this->view->setVar('representation_tag', $va_reps[0]['tags'][$vs_thumbnail_version]);
 					$this->view->setVar('representation_url', $va_reps[0]['urls'][$vs_thumbnail_version]);
 					$this->view->setVar('representation_width', $va_reps[0]['info'][$vs_thumbnail_version]['WIDTH']);
 					$this->view->setVar('representation_height', $va_reps[0]['info'][$vs_thumbnail_version]['HEIGHT']);
 				}
 			}
 			$this->render('ajax_set_item_info_json.php');
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			return $this->render('widget_set_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>