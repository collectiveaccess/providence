<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/Replicator.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2017 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
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
	 *		one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
	 */
	public function log($ps_msg, $pn_level) {
		Replicator::$s_logger->log($ps_msg, $pn_level);
	}
	
	public function replicate() {
		$o_dm = Datamodel::load();
		
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
				//$pn_replicated_log_id = 14356289;
print "START REP AT $pn_replicated_log_id\n";
				$pn_start_replicated_id = $pn_replicated_log_id;
				$vn_retry_count = 0;
				$vn_max_retry_count = 1;
				$pb_ok = true;
				while(true) { // use chunks of 10 entries until something happens (success/err)
					$vn_last_log_id = null;
					
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

					$va_prepend_log_entries = $va_filtered_log_entries = null;
					if (
						(bool)$this->opo_replication_conf->get('sources')[$vs_source_key]['push_missing']
						||
						(bool)$this->opo_replication_conf->get('sources')[$vs_target_key]['push_missing']
					) {
						// harvest guids used for updates
						
						$va_subject_guids = [];
						foreach($va_source_log_entries as $va_source_log_entry) {
							if (is_array($va_source_log_entry['subjects'])) {
								foreach($va_source_log_entry['subjects'] as $va_subject) {
									$va_subject_guids[$va_subject['guid']] = 1;
								}
							}
						}
						
						$o_guid_already_exists = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
											->setRequestBody(array_merge(caExtractArrayValuesFromArrayOfArrays($va_source_log_entries, 'guid'), array_keys($va_subject_guids)))
											->request();
						$va_guid_already_exists = $o_guid_already_exists->getRawData();
						
						
						$vs_access = is_array($pa_filter_on_access_settings) ? join(";", $pa_filter_on_access_settings) : "";
						$o_access_by_guid = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
											->addGetParameter('access', $vs_access)
											->setRequestBody($z=array_unique(array_merge(caExtractArrayValuesFromArrayOfArrays($va_source_log_entries, 'guid'), array_keys($va_subject_guids))))
											->request();
						$va_access_by_guid = $o_access_by_guid->getRawData();

						$va_filtered_log_entries = [];
						$va_source_log_entries_for_missing_guids = [];
						$va_source_log_entries_for_missing_guids_seen_guids = [];
						
						foreach($va_source_log_entries as $vn_log_id => $va_source_log_entry) {
							// $this->log(_t("PROCESS log_id %1", $vn_log_id), Zend_Log::DEBUG);
							$vn_last_log_id = $vn_log_id;
							$vb_logged_exists_on_target = is_array($va_guid_already_exists[$va_source_log_entry['guid']]);
							if ($pa_filter_on_access_settings && ($va_access_by_guid[$va_source_log_entry['guid']] !== '?') && !in_array($va_access_by_guid[$va_source_log_entry['guid']], $pa_filter_on_access_settings) && !$vb_logged_exists_on_target) {
								// skip rows for which we have no access
								continue;
							}
							
							
							$va_missing_guids = [];
							$va_direct_guids = [];
							if (!$vb_logged_exists_on_target) {
								$va_missing_guids[] = $va_source_log_entry['guid'];
								$va_direct_guids[$va_source_log_entry['guid']] = true;
							}
							// print_R($va_source_log_entry);
							if (is_array($va_source_log_entry['subjects'])) {
								foreach($va_source_log_entry['subjects'] as $va_source_log_subject) {
									
									$vb_subject_exists_on_target = is_array($va_guid_already_exists[$va_source_log_subject['guid']]);
									$vb_subject_is_relationship = $o_dm->isRelationship($va_source_log_subject['subject_table_num']);
									
									$vb_have_access_to_subject = true;
									if ($pa_filter_on_access_settings) {
										if ($va_access_by_guid[$va_source_log_subject['guid']] !== '?') {
											$vb_have_access_to_subject = in_array($va_access_by_guid[$va_source_log_subject['guid']], $pa_filter_on_access_settings);
										}
									}
									
									print $x="GUID=".$va_source_log_subject['guid']."/".$va_source_log_subject['subject_table_num'].":".$va_source_log_subject['subject_row_id']."; have access: ".($vb_have_access_to_subject ? "YES" : "NO")."; exists on target: ".($vb_subject_exists_on_target ? "YES" : "NO")."; is relationship: ".($vb_subject_is_relationship ? "YES" : "NO")."\n";
									$this->log($x, Zend_Log::DEBUG);
									
									//
									// Primary records
									//
									if (!$vb_have_access_to_subject && $vb_subject_exists_on_target) {
										// Should delete from target as it's not public any longer
										//print $x="DELETE ".$va_source_log_subject['guid']."\n";
										 //$this->log($x, Zend_Log::DEBUG);
										$va_filtered_log_entries[$vn_log_id] = $va_source_log_entry;
									} elseif($vb_subject_exists_on_target) {
										// Should update on server...
										// ... which means pushing change
										//print $x="UPDATE ".$va_source_log_subject['guid']."\n";
										 //$this->log($x, Zend_Log::DEBUG);
										$va_filtered_log_entries[$vn_log_id] = $va_source_log_entry;
									} elseif($vb_have_access_to_subject && !$vb_subject_exists_on_target) {
										// keep in filtered log
										
										$va_filtered_log_entries[$vn_log_id] = $va_source_log_entry;
										
										// Should insert on server...
										// ... which means synthesizing log from current state
										
										print $x="INSERT ".$va_source_log_subject['guid']."\n";
										$this->log($x, Zend_Log::DEBUG);
										
										//$this->log(print_R($va_source_log_entry, true), Zend_Log::DEBUG);
										
										$va_missing_guids[] = $va_source_log_subject['guid'];
										$va_direct_guids[$va_source_log_subject['guid']] = true;
										

										while((sizeof($va_missing_guids) > 0)) {
											$vs_missing_guid = array_shift($va_missing_guids);
											if ($va_source_log_entries_for_missing_guids_seen_guids[$vs_missing_guid]) {
												if (!$va_direct_guids[$vs_missing_guid]) {
													$t = $va_source_log_entries_for_missing_guids[$vs_missing_guid];
													unset($va_source_log_entries_for_missing_guids[$vs_missing_guid]);
													$va_source_log_entries_for_missing_guids[$vs_missing_guid] = $t;
													$this->log("MOVED $vs_missing_guid", Zend_Log::DEBUG);
												}
												continue;
											}
											
											$this->log(_t("Getting log for missing guid %1", $vs_missing_guid), Zend_Log::DEBUG);
											$va_log = $o_source->setEndpoint('getlog')
												->clearGetParameters()
												->addGetParameter('forGUID', $vs_missing_guid)
												->addGetParameter('skipIfExpression', $vs_skip_if_expression)
												->addGetParameter('ignoreTables', $vs_ignore_tables)
												->addGetParameter('onlyTables', $vs_only_tables)
												->addGetParameter('includeMetadata', $vs_include_metadata)
												->addGetParameter('excludeMetadata', $vs_exclude_metadata)
												->addGetParameter('pushMediaTo', $vs_push_media_to)
												->request()->getRawData();
											if (is_array($va_log)) {
												$va_dependent_guids = [];
												
												$o_access_for_dependent = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
																	->addGetParameter('access', $vs_access)
																	->setRequestBody(array_unique(caExtractArrayValuesFromArrayOfArrays($va_log, 'guid')))
																	->request();
												$va_access_for_dependent = $o_access_for_dependent->getRawData();
												$va_filtered_log = [];
												foreach($va_log as $va_missing_entry) {
													if ($va_missing_entry['log_id'] >= $pn_start_replicated_id) {
														//print "[".$va_missing_entry['guid']."] SKIP MISSING BECAUSE IN FUTURE [".$va_missing_entry['log_id'].";{$pn_start_replicated_id}]\n";
														continue;
													}
													if ($pa_filter_on_access_settings && ($va_access_for_dependent[$va_missing_entry['guid']] !== '?') && !in_array($va_access_for_dependent[$va_missing_entry['guid']], $pa_filter_on_access_settings)) {
														// skip rows for which we have no access
														//print "[".$va_missing_entry['guid']."] SKIP MISSING BECAUSE NO ACCESS!!\n";
														continue;
													}
													
													$va_filtered_log[$va_missing_entry['log_id']] = $va_missing_entry;
													
													if(is_array($va_missing_entry['snapshot'])) {
														$va_dependent_guids = array_unique(array_merge($va_dependent_guids, array_values(array_filter($va_missing_entry['snapshot'], function($v, $k) use ($va_missing_entry, $o_dm, $vs_missing_guid) {
															if ($v == $vs_missing_guid) { return false; }
															if(preg_match("!([A-Za-z0-9_]+)_guid$!", $k, $matches)) {
																if(
																	is_array($o_dm->getFieldInfo($va_missing_entry['logged_table_num'], $matches[1]))
																	||
																	is_array($o_dm->getFieldInfo($va_missing_entry['logged_table_num'], $matches[1].'_id'))
																) {
																	if (in_array($va_missing_entry['logged_table_num'], [3,4])) { // && (in_array($matches[1], ['row', 'row_id']))) {
																		 return false;
																	} else {
																		return true;
																	}
																}
															}
															return false;
														}, ARRAY_FILTER_USE_BOTH))));
													}
												}
												if(sizeof($va_dependent_guids) > 0) {
													$o_guids_exist_for_dependencies = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
																		->setRequestBody($va_dependent_guids)
																		->request();
													$va_guids_exist_for_dependencies = $o_guids_exist_for_dependencies->getRawData();
													$va_missing_guids = array_unique(array_merge($va_missing_guids, $va_dependent_guids=array_keys(array_filter($va_guids_exist_for_dependencies, function($v) { return !is_array($v); }))));
													$this->log("Added deps [".join("; ", $z)."] for $vs_missing_guid", Zend_Log::DEBUG);
													if (is_array($pa_filter_on_access_settings)) {
														$o_access_for_missing = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
																			->addGetParameter('access', $vs_access)
																			->setRequestBody($va_missing_guids)
																			->request();
														$va_access_for_missing = $o_access_for_missing->getRawData();
														$va_missing_guids = array_keys(array_filter($va_access_for_missing, function($v) use ($pa_filter_on_access_settings) { return (($v == '?') || (in_array($v, $pa_filter_on_access_settings))); }));
													}
												}
												//$va_missing_guids = array_filter($va_missing_guids, function($v) use ($va_source_log_entries_for_missing_guids_seen_guids) { return !isset($va_source_log_entries_for_missing_guids_seen_guids[$v]); });
												// print "missing =";print_R($va_missing_guids);
												
												if (!$pa_filter_on_access_settings) { $va_filtered_log = $va_log; }
					$this->log(_t("GOT %1 entries for %2", sizeof($va_filtered_log), $vs_missing_guid), Zend_Log::DEBUG);
					//$this->log(_t("%1", print_R($va_filtered_log, true)), Zend_Log::DEBUG);
												
												if(sizeof($va_dependent_guids) == 0) {
													// has no outstanding dependencies so push it now
													$this->log(_t("Immediately pushing %1 missing entries for %2", sizeof($va_filtered_log), $vs_missing_guid), Zend_Log::DEBUG);
													while(sizeof($va_filtered_log) > 0) {
														$va_entries = [];
														$vn_first_missing_log_id = null;
														
														while(sizeof($va_filtered_log) > 0) {
															$va_log_entry = array_shift($va_filtered_log);
															$vn_mlog_id = $va_log_entry['log_id'];
															if (!$vn_mlog_id) {
																$this->log(_t("Skipped entry because it lacks a log_id %1", print_R($va_log_entry, true)), Zend_Log::DEBUG);
																continue;
															}
															if ($vn_mlog_id >= $pn_start_replicated_id) {
																$this->log(_t("Skipped %1 because it's in the future", $vn_mlog_id),Zend_Log::DEBUG);
																continue;
															}
															if (!$vn_first_missing_log_id) { $vn_first_missing_log_id = $vn_mlog_id; }
															//print "pushing..."; print_R($va_log_entry);
															
															$va_entries[$vn_mlog_id] = $va_log_entry;
															if ((sizeof($va_entries) >= 10) || (sizeof($va_source_log_entries_for_missing_guid) == 0)) { break; }
														}
														
														$this->log(_t("Immediately pushing missing log entries starting with %1 for %2. %3", $vn_first_missing_log_id, $vs_missing_guid, print_R($va_entries, true)), Zend_Log::DEBUG);
														$o_backlog_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
															->addGetParameter('system_guid', $vs_source_system_guid)
															->setRequestBody($va_entries)
															->request();
														//print_R($va_entries);
														//print_r($o_backlog_resp);
													}
													
												} else {
													$va_source_log_entries_for_missing_guids[$vs_missing_guid] = $va_filtered_log;
												}
												//print "GOT ".sizeof($va_filtered_log)." Missing log entries for $vs_missing_guid\n";
											} else {
												$this->log(_t("No log for %1.", $vs_guid), Zend_Log::DEBUG);
											}
											
											$va_source_log_entries_for_missing_guids_seen_guids[$vs_missing_guid] = true;
										}
									}
								}
							}
						}
						//ksort($va_source_log_entries_for_missing_guids, SORT_NUMERIC);
						if(sizeof($va_source_log_entries_for_missing_guids)) {
							$va_source_log_entries_for_missing_guids = array_reverse($va_source_log_entries_for_missing_guids);
							
							//print_R($va_source_log_entries_for_missing_guids);
							foreach($va_source_log_entries_for_missing_guids as $vs_missing_guid => $va_source_log_entries_for_missing_guid) {
								//print_r($va_source_log_entries_for_missing_guids);
								$this->log(_t("Pushing %1 missing entries for %2", sizeof($va_source_log_entries_for_missing_guid), $vs_missing_guid), Zend_Log::DEBUG);
								while(sizeof($va_source_log_entries_for_missing_guid) > 0) {
									$va_entries = [];
									$vn_first_missing_log_id = null;
									
									ksort($va_source_log_entries_for_missing_guid, SORT_NUMERIC);
									while(sizeof($va_source_log_entries_for_missing_guid) > 0) {
										$va_log_entry = array_shift($va_source_log_entries_for_missing_guid);
										$vn_mlog_id = $va_log_entry['log_id'];
										if (!$vn_mlog_id) {
											$this->log(_t("Skipped entry because it lacks a log_id %1", print_R($va_log_entry, true)), Zend_Log::DEBUG);
											continue;
										}
										if ($vn_mlog_id >= $pn_start_replicated_id) {
											$this->log(_t("Skipped %1 because it's in the future", $vn_mlog_id),Zend_Log::DEBUG);
											continue;
										}
										if (!$vn_first_missing_log_id) { $vn_first_missing_log_id = $vn_mlog_id; }
										//print "pushing..."; print_R($va_log_entry);
										
										$va_entries[$vn_mlog_id] = $va_log_entry;
										if ((sizeof($va_entries) >= 10) || (sizeof($va_source_log_entries_for_missing_guid) == 0)) { break; }
									}
									
									$this->log(_t("Pushing missing log entries starting with %1 for %2.", $vn_first_missing_log_id, $vs_missing_guid), Zend_Log::DEBUG);
									$o_backlog_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
										->addGetParameter('system_guid', $vs_source_system_guid)
										->setRequestBody($va_entries)
										->request();
									//print_R($va_entries);
									//print_r($o_backlog_resp);
								}
							}
						}
					}
					
					if (!is_array($va_source_log_entries) || !sizeof($va_source_log_entries)) {
						$this->log(_t("No new log entries found for source %1 and target %2. Skipping this combination now.",
							$vs_source_key, $vs_target_key), Zend_Log::INFO);
						break;
					}
					
					if (is_array($va_filtered_log_entries)) {
						if (sizeof($va_filtered_log_entries) == 0) {
							$pn_replicated_log_id = $vn_last_log_id + 1;
							
							$va_last_log_entry = array_pop($va_source_log_entries);
							
							print $x="[NOTHING TO PUSH] SET LOG POINTER TO $pn_replicated_log_id ".date(DATE_RFC2822, $va_last_log_entry['log_datetime'])."\n";
							$this->log($x, Zend_Log::DEBUG);
							$vn_retry_count = 0;
							continue;
						}
						$va_source_log_entries = $va_filtered_log_entries;
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
					ksort($va_source_log_entries, SORT_NUMERIC);
					$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
						->addGetParameter('system_guid', $vs_source_system_guid)
						->addGetParameter('setIntrinsics', $vs_set_intrinsics)
						->setRequestBody($va_source_log_entries)
						->request();
					//print_R($va_source_log_entries);

					$this->log(_t("Pushed %1 primary entries", sizeof($va_source_log_entries)), Zend_Log::DEBUG);
					$va_response_data = $o_resp->getRawData();
//print_r($va_response_data);
					if (!$o_resp->isOk() || !isset($va_response_data['replicated_log_id'])) {
						print "[RETRY=$vn_retry_count] ERROR! Response was ";
						//print_R($va_response_data);
						//print_R($va_source_log_entries);
						//print_R($o_resp);
						$this->log(_t("There were errors while processing sync for source %1 and target %2: %3", $vs_source_key, $vs_target_key, join(' ', $o_resp->getErrors())), Zend_Log::ERR);
						
						$vn_retry_count++;
						
						if ($vn_retry_count >= $vn_max_retry_count) {
							$pb_ok = false;
							break;
						}
						continue;
					} else {
						$vn_retry_count = 0;
						$pn_replicated_log_id = ($vn_last_log_id > 0) ? ($vn_last_log_id + 1) : ((int) $va_response_data['replicated_log_id']) + 1;
						$this->log(_t("Chunk sync for source %1 and target %2 successful.", $vs_source_key, $vs_target_key), Zend_Log::DEBUG);
						$vn_num_log_entries = sizeof($va_source_log_entries);
						$va_last_log_entry = array_pop($va_source_log_entries);
						
						print $x="[PUSHED {$vn_num_log_entries}] SET LOG POINTER TO $pn_replicated_log_id ".date(DATE_RFC2822, $va_last_log_entry['log_datetime'])."\n";
						$this->log($x, Zend_Log::DEBUG);
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
	
	/**
	 * 
	 */
	public function compare() {
		$o_dm = Datamodel::load();
		
		if ($qr_res = ca_objects::find(['access' => [">", 0]], ['returnAs' => 'searchResult'])) {
			//if ($qr_res = ca_objects::find(['idno' => 'C.2007.27.1'], ['returnAs' => 'searchResult'])) {
			$va_guids = [];
			while($qr_res->nextHit()) {
				if ($vs_guid = ca_guids::getForRow(57, $qr_res->get('object_id'))) {
					$va_guids[] = $vs_guid;
				}
			}

			foreach($this->getSourcesAsServiceClients() as $vs_source_key => $o_source) {
				$pa_include_metadata = $this->opo_replication_conf->get('sources')[$vs_source_key]['includeMetadata'];
				$va_elements = [];
				foreach($pa_include_metadata as $vs_table => $va_fields) {
					if(in_array($vs_table, ['ca_objects', 'ca_entities'])) {
						$va_elements["{$vs_table}.preferred_labels"] = 1;
					}
					if ($vs_table == 'ca_objects') {
						$va_elements["{$vs_table}.idno"] = 1;
					}
					foreach($va_fields as $vs_field => $va_field_info) {
						$va_elements["{$vs_table}.{$vs_field}"] = 1;
					}
				}
				
				$vn_records_with_errors = 0;
				
				$vn_count = 0;
				while(sizeof($va_guids) > 0) {
					foreach($this->getTargetsAsServiceClients() as $vs_target_key => $o_target) {
						$va_guids_to_try = [array_shift($va_guids)];
						
						$o_data = $o_target->setRequestMethod('POST')->setEndpoint('getDataForGUID')
								->setRequestBody(['elements' => array_keys($va_elements), 'guids' => $va_guids_to_try])
								->request();
						$va_resp = $o_data->getRawData();
						
						foreach($va_guids_to_try as $vs_guid) {
							$vn_count++;
							if (!isset($va_resp[$vs_guid])) {
								$va_info = ca_guids::getInfoForGUID($vs_guid);
								print "[ERROR] Missing record for {$vs_guid} [".$o_dm->getTableName($va_info['table_num'])."/".$va_info['row_id']."]\n";
							} else {
								print "[$vn_count] GOT $vs_guid\n";
								
								$va_errors = [];
								foreach($va_resp[$vs_guid] as $vs_element => $va_value) {
									if ($vs_element == 'reps') {
										foreach($va_value as $vs_rep_guid => $vn_access) {
											if (($t_rep = SyncableBaseModel::GUIDToInstance($vs_rep_guid)) && ($t_rep->get('ca_object_representations.access') != $vn_access)) {
												print "REP $vs_rep_guid has incorrect access\n";
												$t_rep->setMode(ACCESS_WRITE);
												$t_rep->set('access', $vn_access ? 1 : 0);
												$t_rep->update();
												$t_rep->set('access', $vn_access ? 0 : 1);
												$t_rep->update();
											
											}
										}
										continue;
									}
									
									
									$va_tmp = explode('.', $vs_element);
									
									if (ca_metadata_elements::getElementID($va_tmp[1]) && (ca_metadata_elements::getElementDatatype($va_tmp[1]) == 0) && (sizeof($va_tmp) == 2)) { continue; }
									if (ca_metadata_elements::getElementID($va_tmp[1]) && (($vn_hier_id = ca_metadata_elements::getElementHierarchyID($va_tmp[1])) !== ca_metadata_elements::getElementID($va_tmp[1]))) {
										$vs_element = join(".", [$va_tmp[0], ca_metadata_elements::getElementCodeForId($vn_hier_id), $va_tmp[1]]);
									}
									
									$t_row = SyncableBaseModel::GUIDToInstance($vs_guid);
									$va_local_val = array_filter($t_row->get($vs_element, ['returnAsArray' => true, 'convertCodesToIdnos' => true]), "strlen");
									
									if (($vn_local = sizeof($va_local_val)) !== ($vn_target = sizeof($va_value))) {
										$va_errors[] = "\t[ERROR] Number of values for {$vs_element} does not match; local={$vn_local}; target={$vn_target}\n Local values are ".print_R($va_local_val, true)."\n Target values are ".print_R($va_value, true)."\n";
									} else {
										$va_local_val = array_map(function($v) { return html_entity_decode(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $v), " \r\n\t")); }, $va_local_val);
										
										foreach($va_local_val as $vs_local_val) {
											$vs_local_val = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $vs_local_val), " \r\n\t");
											
											$va_value = array_map(function($v) { return html_entity_decode(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $v), " \r\n\t")); }, $va_value);
											//if (!in_array(html_entity_decode(trim($vs_local_val)), $va_value)) {
											;
											if (!sizeof(array_filter($va_value, function($v) use ($vs_local_val) { return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $v), " \r\n\t") == $vs_local_val; }))) {
												$va_errors[] = "\t[ERROR] Value '{$vs_local_val}' for {$vs_element} is not present on target\n Local values are ".print_R($va_local_val, true)."\n Target values are ".print_R($va_value, true)."\n";
											}
										}
									}
									
									if (false) {
										if(sizeof($va_errors)) {
											if (ca_metadata_elements::getElementID($va_tmp[1])) {
												foreach($t_row->get($vs_element, ['returnWithStructure' => true, 'convertCodesToIdnos' => true]) as $vn_row_id => $va_attrs) {
													foreach($va_attrs as $vn_attr_id => $va_attr) {
														$t_attr = new ca_attributes($vn_attr_id);
														$t_attr->setMode(ACCESS_WRITE);
														print "FORce ATTR $vn_attr_id/".join(".", $va_tmp)."\n";
														$t_attr->update(['forceLogChange' => true]);
														//print_R($t_attr->getErrors());
														
														foreach($t_attr->getAttributeValues() as $va_vals) {
															//print_R($va_vals);
															//foreach($va_vals as $o_val) {
															$t_attr_val = new ca_attribute_values($va_vals->getValueID());
															$t_attr_val->setMode(ACCESS_WRITE);
															$t_attr_val->update(['forceLogChange' => true]);
															print_R($t_attr_val->getErrors());
															//}
														}
													}
												}
											} elseif (($va_tmp[0] == 'ca_objects') && ($va_tmp[1] == 'preferred_labels')) {
												$va_labels = $t_row->get($vs_element, ['returnWithStructure' => true]);
												foreach($va_labels as $vn_object_id => $va_label_list) {
													foreach($va_label_list as $vn_label_id => $va_label) {
														$t_row->editLabel($vn_label_id, ['name' => $va_label['name'].' '], 1, null, true);
													}
												}
											} elseif (($va_tmp[0] == 'ca_objects') && ($va_tmp[1] == 'idno')) {
												$t_row->set('idno', $t_row->get('idno').' ');
												$t_row->setMode(ACCESS_WRITE);
												$t_row->update(['forceLogChange' => true]);
											}
										}
									}
								}
								
								
								if (sizeof($va_errors)) {
									$vn_records_with_errors++;
									print "[ERRORS] Found errors for  {$vs_guid} [".$t_row->get('ca_objects.idno')." -- ".$t_row->get('ca_objects.object_id')."]\n";
									print join("\n", $va_errors);
								}
								
							}
						
						}
					}
				}
			}
		}
		
		print "{$vn_records_with_errors} WITH ERRORS\n";
	}
}
