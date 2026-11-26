<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/OAIPMHController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Service/OAIPMHService.php');
require_once(__CA_LIB_DIR__.'/Service/BaseServiceController.php');

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
		$config = Configuration::load('oai_provider.conf');
		$providers = $config->getAssoc('providers');
		$provider_config = $providers[$ps_provider] ?? null;
		$allowed_ips = is_array($provider_config) ? ($provider_config['allowed_ips'] ?? null) : null;

		if ($allowed_ips !== null) {
			if (!is_array($allowed_ips)) {
				$this->response->setHTTPResponseCode(403, 'Forbidden');
				return;
			}

			if (sizeof($allowed_ips)) {
				$client_ip = $this->request->getClientIP();
				$client_ip_binary = filter_var($client_ip, FILTER_VALIDATE_IP) ? @inet_pton($client_ip) : false;
				$allowed = false;

				if ($client_ip_binary !== false) {
					foreach($allowed_ips as $allowed_ip) {
						if (filter_var($allowed_ip, FILTER_VALIDATE_IP) && @inet_pton($allowed_ip) === $client_ip_binary) {
							$allowed = true;
							break;
						}
					}
				}

				if (!$allowed) {
					$this->response->setHTTPResponseCode(403, 'Forbidden');
					return;
				}
			}
		}

		$this->service = new OAIPMHService($this->request, $ps_provider);
		$this->view->setVar('oaiData', $this->service->dispatch());

		$this->response->setContentType("application/xml");
		$this->render('oai/oai_xml.php');
	}
	# -------------------------------------------------------
}
