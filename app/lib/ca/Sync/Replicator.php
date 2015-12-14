<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/Replicator.php :
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
 * @subpackage Sync
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

use \CollectiveAccessService as CAS;

class Replicator {

	protected $opo_replication_conf;

	public function __construct() {
		$this->opo_replication_conf = Configuration::load(__CA_CONF_DIR__.'/replication.conf');
	}

	protected function getSourcesAsServiceClients() {
		$va_sources = $this->opo_replication_conf->get('sources');
		if(!is_array($va_sources)) { throw new Exception('No sources configured'); }

		$va_return = array();
		foreach($va_sources as $va_source) {
			$o_service = new CAS\ReplicationService($va_source['url'], 'getlog');
			$o_service->setCredentials($va_source['service_user'], $va_source['service_key']);

			$va_return[] = &$o_service;
		}
		return $va_return;
	}


	public function replicate() {
		//@todo maybe break this out into several, easier to maintain functions?

		foreach($this->getSourcesAsServiceClients() as $o_client) {
			/** @var CAS\ReplicationService $o_client */
			$o_result = $o_client->request();
			//var_dump($o_result->getRawData());
		}
	}
}
