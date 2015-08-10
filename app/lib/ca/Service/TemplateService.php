<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/TemplateService.php
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
 * @package CollectiveAccess
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

class TemplateService  {
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $ps_endpoint
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch($ps_endpoint, $po_request) {
		$va_endpoint_config = self::getEndpointConfig($ps_endpoint);

		return array();
	}
	# -------------------------------------------------------
	/**
	 * Get configuration for endpoint
	 * @param string $ps_endpoint
	 * @return array
	 * @throws Exception
	 */
	private static function getEndpointConfig($ps_endpoint) {
		$o_app_conf = Configuration::load();
		$o_service_conf = Configuration::load($o_app_conf->get('services_config'));

		$va_endpoints = $o_service_conf->get('template_api_endpoints');

		if(!is_array($va_endpoints) || !isset($va_endpoints[$ps_endpoint]) || !is_array($va_endpoints[$ps_endpoint])) {
			throw new Exception('Invalid service endpoint');
		}

		return $va_endpoints[$ps_endpoint];
	}
	# -------------------------------------------------------
}
