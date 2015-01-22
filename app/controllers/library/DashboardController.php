<?php
/* ----------------------------------------------------------------------
 * app/controllers/client/library/DashboardController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

	require_once(__CA_LIB_DIR__.'/ca/Search/ObjectCheckoutSearch.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_checkouts.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

 	class DashboardController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		private $opo_client_services_config;
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkin') || !$this->request->user->canDoAction('can_do_library_checkout') ) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Index() {
 			$this->view->setVar('stats', ca_object_checkouts::getDashboardStatistics());
 			$this->render('dashboard/index_html.php');
 		}
 		# -------------------------------------------------------
 	}