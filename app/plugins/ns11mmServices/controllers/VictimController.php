<?php
/* ----------------------------------------------------------------------
 * plugin/ns11mmServices/controllers/VictimController.php :
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
 	require_once(__CA_APP_DIR__.'/plugins/ns11mmServices/services/VictimService.php');
	require_once(__CA_LIB_DIR__.'/ca/Service/BaseServiceController.php');

	class VictimController extends BaseServiceController {
		/**
		 * Victim service instance does most of the work
		 */
		private $service;
		
		# -------------------------------------------------------
		public function __construct(&$po_request, &$po_response, $pa_view_paths) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
		# -------------------------------------------------------
		/**
		 * Dispatch the request to the service object
		 */
		public function __call($ps_method, $pa_args){
			$this->service = new VictimService($this->request);
			
			if ($ps_method != "auth") {
				// Do auth
				if (!$this->opo_request->isLoggedIn()) {
					$this->view->setVar('response', $this->service->makeResponse(array(), 403,"You must be logged in"));
					$this->render('victim_service_response_json.php');
					return;
				} 
				
				if (!$this->opo_request->user->canDoAction('can_use_ns11mm_memex_services')) {
					$this->view->setVar('response', $this->service->makeResponse(array(), 403,"You cannot access these services"));
					$this->render('victim_service_response_json.php');
					return;
				} 
			}
			
			//
			// Dispatch service call
			//
			if (!method_exists($this->service, $ps_method)) {
				$this->view->setVar('response', $this->service->makeResponse(array(), 404,"Method '{$ps_method}' does not exist"));
			} else {
				$this->view->setVar('response', call_user_func(array($this->service, $ps_method), $pa_args));
			}
			$this->render('victim_service_response_json.php');
		}
		# -------------------------------------------------------
	}
?>
