<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/BundleDisplayEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
 	require_once(__CA_MODELS_DIR__."/ca_bundle_display_placements.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	
 
 	class BundleDisplayEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_bundle_displays';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		protected function _initView($pa_options=null) {
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('sortableUI');
 			JavascriptLoadManager::register('bundleListEditorUI');
 			
 			$va_init = parent::_initView($pa_options);
 			if (!$va_init[1]->getPrimaryKey()) {
 				$va_init[1]->set('user_id', $this->request->getUserID());
 				$va_init[1]->set('table_num', $this->request->getParameter('table_num', pInteger));
 			}
 			return $va_init;
 		}
 		# -------------------------------------------------------
 		protected function _isDisplayEditable() {
 			$pn_display_id = $this->request->getParameter('display_id', pInteger);
 			if ($pn_display_id == 0) { return true; }		// allow creation of new displays
 			$t_display = new ca_bundle_displays();
 			if (!$t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_EDIT_ACCESS__, $pn_display_id)) {		// is user allowed to edit display?
 				$this->notification->addNotification(_t("You cannot edit that display"), __NOTIFICATION_TYPE_ERROR__);
 				$this->response->setRedirect(caNavUrl($this->request, 'manage', 'BundleDisplays', 'ListDisplays'));
 				return false; 
 			} else {
 				return true;
 			}
 		}
 		# -------------------------------------------------------
 		public function Edit($pa_values=null, $pa_options=null) {
 			if ($this->_isDisplayEditable()) { return parent::Edit($pa_values, $pa_options); } 
 			return false;
 		}
 		# -------------------------------------------------------
 		public function Delete($pa_options=null) {
 			if ($this->_isDisplayEditable()) { return parent::Delete($pa_options); } 
 			return false;
 		}
 		# -------------------------------------------------------
 		/**
 		 * If instance was just saved grant current user access
 		 */
 		public function _afterSave($pt_subject, $pb_is_insert) {
 			if ($pb_is_insert && $pt_subject->getPrimaryKey()) {
 				$pt_subject->addUsers(array($this->request->getUserID() => __CA_BUNDLE_DISPLAY_EDIT_ACCESS__));
 			}
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Info($pa_parameters) {
 			parent::info($pa_parameters);
 			
 			
 			return $this->render('widget_bundle_display_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>