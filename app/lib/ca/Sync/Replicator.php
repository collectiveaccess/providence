<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/Replicator.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2016 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__."/ca_change_log.php");

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
				$va_backlog = [];

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
				if($pn_min_log_id > $pn_replicated_log_id) { 
					$pn_replicated_log_id = $pn_min_log_id; 
					$this->log(_t("Set log id to minimum ({$pn_replicated_log_id})"), Zend_Log::INFO);
				}

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
				
				// get only tables
				$pa_only_tables = $this->opo_replication_conf->get('sources')[$vs_source_key]['onlyTables'];
				if(!is_array($pa_only_tables)) { $pa_only_tables = []; }
				if(is_array($pa_only_tables_global = $this->opo_replication_conf->get('sources')['onlyTables'])) {
					$pa_only_tables = array_merge($pa_only_tables_global, $pa_only_tables);
				}
				$vs_only_tables = null;
				if(is_array($pa_only_tables) && sizeof($pa_only_tables)) {
					$vs_only_tables = json_encode(array_unique(array_values($pa_only_tables)));
				}
				
				// get includeMetadata list
				$pa_include_metadata = $this->opo_replication_conf->get('sources')[$vs_source_key]['includeMetadata'];
				$vs_include_metadata = null;
				if(is_array($pa_include_metadata) && sizeof($pa_include_metadata)) {
					$vs_include_metadata = json_encode($pa_include_metadata);
				}
				// get excludeMetadata list
				$pa_exclude_metadata = $this->opo_replication_conf->get('sources')[$vs_source_key]['excludeMetadata'];
				$vs_exclude_metadata = null;
				if(is_array($pa_exclude_metadata) && sizeof($pa_exclude_metadata)) {
					$vs_exclude_metadata = json_encode($pa_exclude_metadata);
				}
				// get filter_on_access_settings
				if (!is_array($pa_filter_on_access_settings = $this->opo_replication_conf->get('sources')[$vs_source_key]['filter_on_access_settings']) || !sizeof($pa_filter_on_access_settings)) {
					$pa_filter_on_access_settings = null;
				}
				

				$pn_start_replicated_id = $pn_replicated_log_id;
				
				$va_back_log = [];
				$pb_ok = true;
				while(true) { // use chunks of 10 entries until something happens (success/err)
					// get change log from source, starting with the log id we got above
					$va_source_log_entries = $o_source->setEndpoint('getlog')->clearGetParameters()
						->addGetParameter('from', $pn_replicated_log_id)
						->addGetParameter('skipIfExpression', $vs_skip_if_expression)
						->addGetParameter('limit', 10)
						->addGetParameter('ignoreTables', $vs_ignore_tables)
						->addGetParameter('onlyTables', $vs_only_tables)
						->addGetParameter('includeMetadata', $vs_include_metadata)
						->addGetParameter('excludeMetadata', $vs_exclude_metadata)
						->addGetParameter('pushMediaTo', $vs_push_media_to)
						->request()->getRawData();

					if (
						(bool)$this->opo_replication_conf->get('sources')[$vs_source_key]['push_missing']
						||
						(bool)$this->opo_replication_conf->get('sources')[$vs_target_key]['push_missing']
					) {
						// harvest guids used for updates
						$va_guid_list = [];
						$va_source_log_entries_for_missing_guids = [];
						
						$o_guid_already_exists = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
											->setRequestBody(caExtractArrayValuesFromArrayOfArrays($va_source_log_entries, 'guid'))
											->request();
						$va_guid_already_exists = $o_guid_already_exists->getRawData();
					
						foreach($va_source_log_entries as $vn_log_id => $va_source_log_entry) {
							if (is_array($va_source_log_entry['subjects'])) {
								$vb_have_access = false;
								foreach($va_source_log_entry['subjects'] as $va_source_log_subject) {
									if (!($vb_have_access = (is_array($va_guid_already_exists[$va_source_log_subject['guid']])))) {									
										// TODO: this assumes the source is where we're running replication from (eg. PUSH)
										// should replace with a service call to the source
										$vb_have_access = (($pa_filter_on_access_settings && !ca_change_log::rowHasAccess($va_source_log_subject['subject_table_num'], $va_source_log_subject['subject_row_id'], $pa_filter_on_access_settings))) ? 0 : 1;
									}
									if ($vb_have_access) {
										$va_guid_list[$va_source_log_subject['guid']] = 1;
									}
								}
								
								if (!$vb_have_access) {
									$va_source_log_entries[$vn_log_id]['SKIP'] = 1;
								}
								
								if (isset($va_source_log_entry['snapshot']['parent_id_guid'])) {
									$va_guid_list[$va_source_log_entry['snapshot']['parent_id_guid']] = 1;
								}
							}
						}
						
						// are any of these guids not present on the target?
						$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
								->setRequestBody(array_keys($va_guid_list))
								->request();
						$va_guid_presence_map = $o_resp->getRawData();
					
						if (is_array($va_guid_presence_map) && sizeof($va_guid_presence_map)) {
							// run log entries for those guids
							foreach($va_guid_presence_map as $vs_guid => $va_guid_info) {
								if ($vs_guid && !is_array($va_guid_info) && ($va_guid_list[$vs_guid])) {	// Only process related if the guid is not present and access is set
									if ($va_back_log[$vs_guid]) { continue; }
									$va_back_log[$vs_guid] = true;
									$this->log(_t("Getting log for missing guid %1", $vs_guid), Zend_Log::DEBUG);
									$va_log = $o_source->setEndpoint('getlog')
										->clearGetParameters()
										->addGetParameter('forGUID', $vs_guid)
										->addGetParameter('skipIfExpression', $vs_skip_if_expression)
										->addGetParameter('ignoreTables', $vs_ignore_tables)
										->addGetParameter('onlyTables', $vs_only_tables)
										->addGetParameter('includeMetadata', $vs_include_metadata)
										->addGetParameter('excludeMetadata', $vs_exclude_metadata)
										->addGetParameter('pushMediaTo', $vs_push_media_to)
										->request()->getRawData();
									if (is_array($va_log)) {
										$va_source_log_entries_for_missing_guids = array_replace($va_source_log_entries_for_missing_guids, $va_log);
									} else {
										$this->log(_t("No log for %1.", $vs_guid), Zend_Log::DEBUG);
									}
								}
							}
							
							// expand to related subjects
							$va_expanded_guid_list = [];
							foreach($va_source_log_entries_for_missing_guids as $vn_log_id => $va_source_log_entry) {
								if ($vn_log_id >= $pn_start_replicated_id) { continue; }
								if (is_array($va_source_log_entry['subjects'])) {
									foreach($va_source_log_entry['subjects'] as $va_source_log_subject) {
										if (!($va_guid_list[$va_source_log_subject['guid']] = ($pa_filter_on_access_settings && !ca_change_log::rowHasAccess($va_source_log_subject['subject_table_num'], $va_source_log_subject['subject_row_id'], $pa_filter_on_access_settings)) ? 0 : 1)) {
											continue; 	 // skip entry because no access
										}
										$va_expanded_guid_list[$va_source_log_subject['guid']]++;
									}
								}
							}
							$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
								->setRequestBody(array_keys($va_expanded_guid_list))
								->request();
							$va_expanded_guid_presence_map = $o_resp->getRawData();
							
							foreach($va_expanded_guid_presence_map as $vs_guid => $va_guid_info) {
								if ($vs_guid && !is_array($va_guid_info)) {
									if ($va_back_log[$vs_guid]) { continue; }
									$va_back_log[$vs_guid] = true;
									$this->log(_t("Getting log for related subject guid %1", $vs_guid), Zend_Log::DEBUG);
									$va_log = $o_source->setEndpoint('getlog')
										->clearGetParameters()
										->addGetParameter('forGUID', $vs_guid)
										->addGetParameter('skipIfExpression', $vs_skip_if_expression)
										->addGetParameter('ignoreTables', $vs_ignore_tables)
										->addGetParameter('onlyTables', $vs_only_tables)
										->addGetParameter('includeMetadata', $vs_include_metadata)
										->addGetParameter('excludeMetadata', $vs_exclude_metadata)
										->addGetParameter('pushMediaTo', $vs_push_media_to)
										->request()->getRawData();
									if(is_array($va_log)) {
										$va_source_log_entries_for_missing_guids = array_replace($va_source_log_entries_for_missing_guids, $va_log);
									}
								}
							}
						
							ksort($va_source_log_entries_for_missing_guids, SORT_NUMERIC);
							
							if(sizeof($va_source_log_entries_for_missing_guids)) {
								while(sizeof($va_source_log_entries_for_missing_guids) > 0) {
									$va_entries = [];
									while(sizeof($va_source_log_entries_for_missing_guids) > 0) {
										$va_log_entry = array_shift($va_source_log_entries_for_missing_guids);
										$vn_log_id = $va_log_entry['log_id'];
										if (!$vn_log_id) { continue; }
										if ($vn_log_id >= $pn_start_replicated_id) { continue; }
										
										$va_entries[$vn_log_id] = $va_log_entry;
										if ((sizeof($va_entries) >= 10) || (sizeof($va_source_log_entries_for_missing_guids) == 0)) { break; }
									}
									
									$this->log(_t("Pushing missing log entries starting with %1.", $vn_log_id), Zend_Log::DEBUG);
									$o_backlog_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
										->addGetParameter('system_guid', $vs_source_system_guid)
										->setRequestBody($va_entries)
										->request();
								}
							}

						}
					}
					
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
						$va_last_log_entry = array_pop($va_source_log_entries);
						$this->log(_t("Last replicated log ID is: %1 (%2)", $va_response_data['replicated_log_id'], date(DATE_RFC2822, $va_last_log_entry['log_datetime'])), Zend_Log::DEBUG);
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
