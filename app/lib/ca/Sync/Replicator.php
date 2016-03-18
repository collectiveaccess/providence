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

		return $this->getConfigAsServiceClients($va_sources);
	}

	protected function getTargetsAsServiceClients() {
		$va_targets = $this->opo_replication_conf->get('targets');
		if(!is_array($va_targets)) { throw new Exception('No sources configured'); }

		return $this->getConfigAsServiceClients($va_targets);
	}

	private function getConfigAsServiceClients($pa_config) {
		$va_return = array();
		foreach($pa_config as $vs_key => $va_conf) {
			$o_service = new CAS\ReplicationService($va_conf['url'], 'getlog');
			$o_service->setCredentials($va_conf['service_user'], $va_conf['service_key']);

			$va_return[$vs_key] = &$o_service;
		}
		return $va_return;
	}


	public function replicate() {

		foreach($this->getSourcesAsServiceClients() as $vs_source_key => $o_source) {
			/** @var CAS\ReplicationService $o_source */

			// get source guid
			$vs_source_system_guid = $o_source->setEndpoint('getsysguid')->request()->getRawData()['system_guid'];
			if(!strlen($vs_source_system_guid)) {
				throw new Exception('Could not get system GUID for one of the configured replication sources: ' . $vs_source_key);
			}

			foreach($this->getTargetsAsServiceClients() as $o_target) {
				/** @var CAS\ReplicationService $o_target */

				// get latest log id for this source at current target
				$o_result = $o_target->setEndpoint('getlastreplicatedlogid')
					->addGetParameter('system_guid', $vs_source_system_guid)
					->request();
				$pn_replicated_log_id = $o_result->getRawData()['replicated_log_id'];

				// it's possible to configure a starting point in the replication config. it's not pretty to do this as
				// raw id, @todo: maybe this should be a timestamp or even a date/time expression that we then translate?
				$pn_min_log_id = (int) $this->opo_replication_conf->get('sources')[$vs_source_key]['from_log_id'];
				if($pn_min_log_id > $pn_replicated_log_id) { $pn_replicated_log_id = $pn_min_log_id; }

				// get change log from source, starting with the log id we got above
				$va_source_log_entries = $o_source->setEndpoint('getlog')
					->addGetParameter('from', $pn_replicated_log_id)
					->request()->getRawData();

				// apply that log at the current target
				$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
					->addGetParameter('system_guid', $vs_source_system_guid)
					->setRequestBody($va_source_log_entries)
					->request();


				var_dump($o_resp);

				// the above call will return the id of the last successfull replication operation
				// we take that back to the source to record it there.
				$pn_last_replicated_log_id = $o_resp->getRawData()['replicated_log_id'];


			}
		}
	}
}
