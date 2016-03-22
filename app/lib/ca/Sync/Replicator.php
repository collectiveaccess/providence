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

require_once(__CA_LIB_DIR__."/core/Zend/Log/Writer/Stream.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log/Writer/Syslog.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log/Formatter/Simple.php");

use \CollectiveAccessService as CAS;

class Replicator {

	/**
	 * @var Configuration
	 */
	protected $opo_replication_conf;

	/**
	 * @var Zend_Log
	 */
	protected $opo_replication_log = null;

	public function __construct() {
		$this->opo_replication_conf = Configuration::load(__CA_CONF_DIR__.'/replication.conf');

		$o_writer = null;
		if($vs_log = $this->opo_replication_conf->get('replication_log')) {
			try {
				$o_writer = new Zend_Log_Writer_Stream($vs_log);
				$o_writer->setFormatter(new Zend_Log_Formatter_Simple('%timestamp% %priorityName%: %message%' . PHP_EOL));
			} catch (Zend_Log_Exception $e) { // error while opening the file (usually permissions)
				$o_writer = null;
				print CLIUtils::textWithColor("Couldn't open log file. Now logging via system log.", "bold_red") . PHP_EOL . PHP_EOL;
			}
		}

			// default: log everything to syslog
		if(!$o_writer) {
			$o_writer = new Zend_Log_Writer_Syslog(array('application' => 'CollectiveAccess Replicator', 'facility' => LOG_USER));
			// no need for timespamps in syslog ... the syslog itsself provides that
			$o_writer->setFormatter(new Zend_Log_Formatter_Simple('%priorityName%: %message%'.PHP_EOL));
		}

		$this->opo_replication_log = new Zend_Log($o_writer);
		$this->opo_replication_log->setTimestampFormat('D Y-m-d H:i:s');
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

	/**
	 * Log message with given log level
	 * @param string $ps_msg
	 * @param int $pn_level log level as Zend_Log level integer:
	 *        one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
	 */
	public function log($ps_msg, $pn_level) {
		if(!in_array($pn_level, array(Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR))){
			$pn_level = Zend_Log::INFO;
		}

		$this->opo_replication_log->log($ps_msg, $pn_level);

		switch($pn_level) {
			case Zend_Log::DEBUG:
				if(defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) {
					print CLIUtils::textWithColor($ps_msg, 'purple') . PHP_EOL;
				}
				break;
			case Zend_Log::INFO:
				print CLIUtils::textWithColor($ps_msg, 'green') . PHP_EOL;
				break;
			case Zend_Log::WARN:
				print CLIUtils::textWithColor($ps_msg, 'yellow') . PHP_EOL;
				break;
			case Zend_Log::ERR:
				CLIUtils::addError($ps_msg);
				break;
		}
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
			}

			foreach($this->getTargetsAsServiceClients() as $vs_target_key => $o_target) {
				/** @var CAS\ReplicationService $o_target */

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
				}

				$this->log(_t("Starting replication for source %1 and target %2, log id is %3.",
					$vs_source_key, $vs_target_key, $pn_replicated_log_id), Zend_Log::INFO);

				// it's possible to configure a starting point in the replication config. it's not pretty to do this as
				// raw id, @todo: maybe this should be a timestamp or even a date/time expression that we then translate?
				$pn_min_log_id = (int) $this->opo_replication_conf->get('sources')[$vs_source_key]['from_log_id'];
				if($pn_min_log_id > $pn_replicated_log_id) { $pn_replicated_log_id = $pn_min_log_id; }

				// get change log from source, starting with the log id we got above
				$va_source_log_entries = $o_source->setEndpoint('getlog')
					->addGetParameter('from', $pn_replicated_log_id)
					->request()->getRawData();

				if(!sizeof($va_source_log_entries)) {
					$this->log(_t("No new log entries found for source %1 and target %2. Skipping this combination now.",
							$vs_source_key, $vs_target_key), Zend_Log::INFO);
					continue;
				}

				// apply that log at the current target
				$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
					->addGetParameter('system_guid', $vs_source_system_guid)
					->setRequestBody($va_source_log_entries)
					->request();

				$va_response_data = $o_resp->getRawData();

				if(!$o_resp->isOk()) {
					$this->log(_t("There were errors while processing sync for source %1 and target %2: %3",$vs_source_key, $vs_target_key, join(' ', $o_resp->getErrors())), Zend_Log::ERR);
				} else {
					$this->log(_t("Sync for source %1 and target %2 successful", $vs_source_key, $vs_target_key), Zend_Log::INFO);
					if(isset($va_response_data['replicated_log_id'])) {
						$this->log(_t("Last replicated log ID is: %1", $va_response_data['replicated_log_id']), Zend_Log::INFO);
					}
				}

				if(isset($va_response_data['warnings']) && is_array($va_response_data['warnings']) && sizeof($va_response_data['warnings'])) {
					foreach($va_response_data['warnings'] as $vn_log_id => $va_warns) {

						$this->log(_t("There were warnings while processing sync for source %1, target %2, log id %3: %4",
							$vs_source_key, $vs_target_key, $vn_log_id, join(' ', $va_warns)), Zend_Log::WARN);
					}
				}
			}
		}
	}
}
