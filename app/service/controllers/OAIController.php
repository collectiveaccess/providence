<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/OAIPMHController.php :
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
 	require_once(__CA_LIB_DIR__.'/ca/Service/OAIPMHService.php');
	require_once(__CA_LIB_DIR__.'/ca/Service/BaseServiceController.php');

	class OAIController extends BaseServiceController {
		/**
		 * OAI-PMH service instance does most of the work
		 */
		private $service;
		
		# -------------------------------------------------------
		public function __construct(&$po_request, &$po_response, $pa_view_paths) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
		# -------------------------------------------------------
		/**
		 * Dispatch the request to the OAI-PMH service object
		 */
		public function __call($ps_provider, $pa_args){
			$this->service = new OAIPMHService($this->request, $ps_provider);
			$this->view->setVar('oaiData', $this->service->dispatch());
			
			$this->render('oai/oai_xml.php');
		}
		# -------------------------------------------------------
	}
?>
