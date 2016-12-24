<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SitePageEditorController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

 	require_once(__CA_MODELS_DIR__."/ca_site_pages.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");

 	class SitePageEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		/**
		 * name of "subject" table (what we're editing)
		 */
 		protected $ops_table_name = 'ca_site_pages';
 		# -------------------------------------------------------
 		/**
		 *
		 */
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);

 		}
 		# -------------------------------------------------------
 		/**
		 *
		 */
 		protected function _initView($pa_options=null) {
 			AssetLoadManager::register('bundleableEditor');
 			AssetLoadManager::register('sortableUI');
 			$va_init = parent::_initView($pa_options);
 			if (!$va_init[1]->getPrimaryKey()) {
 				$va_init[1]->set('user_id', $this->request->getUserID());
 				$va_init[1]->set('table_num', $this->request->getParameter('table_num', pInteger));
 			}
 			return $va_init;
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		/**
		 *
		 */
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			return $this->render('widget_site_page_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}