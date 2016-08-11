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

require_once(__CA_LIB_DIR__."/core/Logging/Logger.php");

use \CollectiveAccessService as CAS;

class Replicator {

	/**
	 * @var Configuration
	 */
	protected $opo_replication_conf;

	/**
	 * @var Logger
	 */
	static $s_logger = null;

	public function __construct() {
		$this->opo_replication_conf = Configuration::load(__CA_CONF_DIR__.'/replication.conf');
		Replicator::$s_logger = new Logger('replication');
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
			if(isset($va_conf['url']) && $va_conf['url']) {
				$o_service = new CAS\ReplicationService($va_conf['url'], 'getlog');
				$o_service->setCredentials($va_conf['service_user'], $va_conf['service_key']);

				$va_return[$vs_key] = $o_service;
			}
		}
		return $va_return;
	}
	
	/**
	 * Log message with given log level
	 * @param string $ps_msg
	 * @param int $pn_level log level as Zend_Log level integer:
	 *        one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
	 */
	public function log($ps_msg, $pn_level) {
		Replicator::$s_logger->log($ps_msg, $pn_level);
	}
	
	public function replicate() {
		foreach($this->getSourcesAsServiceClients() as $vs_source_key => $o_source) {
			/** @var CAS\ReplicationService $o_source */

			// get source guid // @todo cache this
			$vs_source_system_guid = $o_source->setEndpoint('getsysguid')->request()->getRawData()['system_guid'];
			if(!strlen($vs_source_system_guid)) {
				$this->log(
					"Could not get system GUID for one of the configured replication sources: {$vs_source_key}. Skipping source.",
					\Zend_Log::ERR
				);
				continue;
			}

			foreach($this->getTargetsAsServiceClients() as $vs_target_key => $o_target) {
				/** @var CAS\ReplicationService $o_target */

				$vs_push_media_to = null;
				if($this->opo_replication_conf->get('sources')[$vs_source_key]['push_media']) {
					$vs_push_media_to = $vs_target_key;
				}

				// get latest log id for this source at current target
				$o_result = $o_target->setEndpoint('getlastreplicatedlogid')
					->addGetParameter('system_guid', $vs_source_system_guid)
					->request();
				$pn_replicated_log_id = $o_result->getRawData()['replicated_log_id'];

				if($pn_replicated_log_id) {
					$pn_replicated_log_id = ((int) $pn_replicated_log_id) + 1;
				} else {
					$this->log(_t("Couldn't get last replicated log id for source %1 and target %2. Starting at the beginning.",
						$vs_source_key, $vs_target_key), Zend_Log::WARN);
					$pn_replicated_log_id = 1;
				}

				$this->log(_t("Starting replication for source %1 and target %2, log id is %3.",
					$vs_source_key, $vs_target_key, $pn_replicated_log_id), Zend_Log::INFO);

				// it's possible to configure a starting point in the replication config
				if($ps_min_log_timestamp = $this->opo_replication_conf->get('sources')[$vs_source_key]['from_log_timestamp']) {
					if(!is_numeric($ps_min_log_timestamp)) {
						$o_tep = new TimeExpressionParser($ps_min_log_timestamp);
						$ps_min_log_timestamp = $o_tep->getUnixTimestamps()['start'];
					}

					// get latest log id for this source at current target
					$o_result = $o_target->setEndpoint('getlogidfortimestamp')
						->addGetParameter('timestamp', $ps_min_log_timestamp)
						->request();
					$pn_min_log_id = $o_result->getRawData()['log_id'];
				} else {
					$pn_min_log_id = (int) $this->opo_replication_conf->get('sources')[$vs_source_key]['from_log_id'];
				}
				if($pn_min_log_id > $pn_replicated_log_id) { $pn_replicated_log_id = $pn_min_log_id; }

				// get skip if expression
				$pa_skip_if_expression = $this->opo_replication_conf->get('sources')[$vs_source_key]['skipIfExpression'];
				$vs_skip_if_expression = null;
				if(is_array($pa_skip_if_expression) && sizeof($pa_skip_if_expression)) {
					$vs_skip_if_expression = json_encode($pa_skip_if_expression);
				}

				// get ignore tables
				$pa_ignore_tables = $this->opo_replication_conf->get('sources')[$vs_source_key]['ignoreTables'];
				if(!is_array($pa_ignore_tables)) { $pa_ignore_tables = []; }
				if(is_array($pa_ignore_tables_global = $this->opo_replication_conf->get('sources')['ignoreTables'])) {
					$pa_ignore_tables = array_merge($pa_ignore_tables_global, $pa_ignore_tables);
				}
				$vs_ignore_tables = null;
				if(is_array($pa_ignore_tables) && sizeof($pa_ignore_tables)) {
					$vs_ignore_tables = json_encode(array_unique(array_values($pa_ignore_tables)));
				}

				$pb_ok = true;
				while(true) { // use chunks of 10 entries until something happens (success/err)
					// get change log from source, starting with the log id we got above
					$va_source_log_entries = $o_source->setEndpoint('getlog')
						->addGetParameter('from', $pn_replicated_log_id)
						->addGetParameter('skipIfExpression', $vs_skip_if_expression)
						->addGetParameter('limit', 10)
						->addGetParameter('ignoreTables', $vs_ignore_tables)
						->addGetParameter('pushMediaTo', $vs_push_media_to)
						->request()->getRawData();

					if (!is_array($va_source_log_entries) || !sizeof($va_source_log_entries)) {
						$this->log(_t("No new log entries found for source %1 and target %2. Skipping this combination now.",
							$vs_source_key, $vs_target_key), Zend_Log::INFO);
						break;
					}

					// get setIntrinsics -- fields that are set on the target side (e.g. to tag/mark
					// where records came from if multiple systems are being synced into one)
					$va_set_intrinsics_config = $this->opo_replication_conf->get('targets')[$vs_target_key]['setIntrinsics'];
					$va_set_intrinsics_default = is_array($va_set_intrinsics_config['__default__']) ? $va_set_intrinsics_config['__default__'] : array();
					$va_set_intrinsics_source = is_array($va_set_intrinsics_config[$vs_source_system_guid]) ? $va_set_intrinsics_config[$vs_source_system_guid] : array();
					$va_set_intrinsics = array_replace($va_set_intrinsics_default, $va_set_intrinsics_source);
					$vs_set_intrinsics = null;
					if (is_array($va_set_intrinsics) && sizeof($va_set_intrinsics)) {
						$vs_set_intrinsics = json_encode($va_set_intrinsics);
					}

					// apply that log at the current target
					$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
						->addGetParameter('system_guid', $vs_source_system_guid)
						->addGetParameter('setIntrinsics', $vs_set_intrinsics)
						->setRequestBody($va_source_log_entries)
						->request();

					$va_response_data = $o_resp->getRawData();

					if (!$o_resp->isOk() || !isset($va_response_data['replicated_log_id'])) {
						$this->log(_t("There were errors while processing sync for source %1 and target %2: %3", $vs_source_key, $vs_target_key, join(' ', $o_resp->getErrors())), Zend_Log::ERR);
						$pb_ok = false;
						break;
					} else {
						$pn_replicated_log_id = ((int) $va_response_data['replicated_log_id']) + 1;
						$this->log(_t("Chunk sync for source %1 and target %2 successful.", $vs_source_key, $vs_target_key), Zend_Log::DEBUG);
						$this->log(_t("Last replicated log ID is: %1", $va_response_data['replicated_log_id']), Zend_Log::DEBUG);
					}

					/*if (isset($va_response_data['warnings']) && is_array($va_response_data['warnings']) && sizeof($va_response_data['warnings'])) {
						foreach ($va_response_data['warnings'] as $vn_log_id => $va_warns) {
							$this->log(_t("There were warnings while processing sync for source %1, target %2, log id %3: %4",
								$vs_source_key, $vs_target_key, $vn_log_id, join(' ', $va_warns)), Zend_Log::WARN);
						}
					}*/
				}

				if($pb_ok) {
					$this->log(_t("Sync for source %1 and target %2 successful", $vs_source_key, $vs_target_key), Zend_Log::INFO);

					// run dedup if configured
					$va_dedup_after_replication = $this->opo_replication_conf->get('targets')[$vs_target_key]['deduplicateAfterReplication'];
					$vs_dedup_after_replication = null;
					if(is_array($va_dedup_after_replication) && sizeof($va_dedup_after_replication)) {
						$vs_dedup_after_replication = json_encode($va_dedup_after_replication);

						// apply that log at the current target
						$o_dedup_response = $o_target->setRequestMethod('POST')->setEndpoint('dedup')
							->addGetParameter('tables', $vs_dedup_after_replication)
							->request();

						$va_dedup_response = $o_dedup_response->getRawData();

						if (!$o_dedup_response->isOk()) {
							$this->log(_t("There were errors while processing deduplication for at target %1: %2", $vs_target_key, join(' ', $o_dedup_response->getErrors())), Zend_Log::ERR);
						} else {
							$this->log(_t("Dedup at target %1 successful.", $vs_target_key), Zend_Log::INFO);
							if(isset($va_dedup_response['report']) && is_array($va_dedup_response['report'])) {
								foreach($va_dedup_response['report'] as $vs_t => $vn_c) {
									$this->log(_t("De-duped %1 records for %2.", $vn_c, $vs_t), Zend_Log::DEBUG);
								}
							}
						}
					}


				} else {
					$this->log(_t("Sync for source %1 and target %2 finished, but there were errors", $vs_source_key, $vs_target_key), Zend_Log::ERR);
				}
			}
		}
	}
}
