<?php
/** ---------------------------------------------------------------------
 * app/lib/Sync/Replicator.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2023 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/Logging/Logger.php");
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
	static $s_log = null;

	/**
	 * @var Logger
	 */
	static $s_debug_log = null;
	
	/**
	 * Client for current replication source
	 */
	protected $source;
	
	/**
	 * GUID of current replication source
	 */
	protected $source_guid;
	
	/**
	 * Config key for current replication source
	 */
	protected $source_key;
	
	/**
	 * Client for current replicateion target
	 */
	protected $target;
	
	/**
	 * Config key for current replication target
	 */
	protected $target_key;
	
	/**
	 * Last log id processed for current sync
	 */
	protected $last_log_id;
	
	/**
	 * GUIDs to skip in current sync due to lack of access
	 */
	protected $guids_to_skip;
	
	/**
	 * List of log ids sent in this session for the source. Used to ensure
	 * that log entries for missing GUIDs are only sent once to the target.
	 */
	protected $sent_log_ids;
	
	/**
	 * Configured parameters defined by target for getlog service call
	 */
	protected $get_log_service_params;
	
	/**
	 * Maximum number of times to retry failed service connections
	 */
	protected $max_retries = 5;
	
	/**
	 * Delay in milliseconds between retries of failed service connections
	 */
	protected $retry_delay = 1000;
	
	/**
	 * Cached access values for guids
	 */
	protected $guid_access_cache = [];
	
	/**
	 *
	 */
	protected $missing_guids = [];
	
	/**
	 *
	 */
	protected $source_log_entries_for_missing_guids = [];
	
	/**
	 *
	 */
	protected $source_log_entries_for_missing_guids_seen_guids = [];
	
	/**
	 *
	 */
	protected $source_log_entries = [];
	
	/**
	 *
	 */
	protected $filtered_log_entries = [];
	
	# --------------------------------------------------------------------------------------------------------------
	public function __construct() {
		$this->opo_replication_conf = Configuration::load(__CA_CONF_DIR__.'/replication.conf');
		Replicator::$s_log = new Logger('replication');
		Replicator::$s_debug_log = new Logger('replication_debug');
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	protected function getSourcesAsServiceClients(?array $options=null) {
		$sources = $this->opo_replication_conf->get('sources');
		if(($enabled_sources = caGetOption('source', $options, null)) || ($enabled_sources = $this->opo_replication_conf->getList('enabled_sources'))) {
			if(!is_array($enabled_sources)) { $enabled_sources = [$enabled_sources]; }
			$filtered_sources = [];
			foreach($enabled_sources as $s) {
				if(isset($sources[$s])) {
					$filtered_sources[$s] = $sources[$s];
				}
			}
			$sources = $filtered_sources;
		}
		if(!is_array($sources)) { throw new Exception('No sources configured'); }

		return $this->getConfigAsServiceClients($sources);
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	protected function getTargetsAsServiceClients() {
		$targets = $this->opo_replication_conf->get('targets');
		if(!is_array($targets)) { throw new Exception('No sources configured'); }

		return $this->getConfigAsServiceClients($targets);
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function getConfigAsServiceClients(array $config) {
		$return = [];
		foreach($config as $key => $conf) {
			if(isset($conf['url']) && $conf['url']) {
				$o_service = new CAS\ReplicationService($conf['url'], 'getlog');
				$o_service->setCredentials($conf['service_user'], $conf['service_key']);

				$return[$key] = $o_service;
			}
		}
		return $return;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 * Write message to log with given log level
	 * @param string $msg
	 * @param int $level log level as Zend_Log level integer:
	 *        one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
	 */
	public function log(string $msg, int $level) : void {
		Replicator::$s_log->log($msg, $level);
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 * Write message to debug log with given log level
	 * @param string $msg
	 * @param int $level log level as Zend_Log level integer:
	 *        one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
	 */
	public function logDebug(string $msg, int $level) : void {
		Replicator::$s_debug_log->log($msg, $level);
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function replicate(?array $options=null) {
		foreach($this->getSourcesAsServiceClients($options) as $source_key => $o_source) {
			/** @var CAS\ReplicationService $o_source */
			
			// Sync a single log_id from a specific source?
			$single_log_id_mode = false;
			if(caGetOption('source', $options, null) && $single_log_id = caGetOption('log_id', $options, null)) {
				$single_log_id_mode = 1;
			}

			// Get GUID for data source
			$o_result = $o_source->setEndpoint('getsysguid')->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)->request();
			if(!$o_result || !($res = $o_result->getRawData()) || !(strlen($source_system_guid = $res['system_guid']))) {
				$this->log(
					"Could not get system GUID for one of the configured replication sources: {$source_key}. Skipping source.",
					\Zend_Log::ERR
				);
				continue;
			}
                
        	if(($chunk_size = (int)$this->opo_replication_conf->get('chunk_size')) <= 0) { $chunk_size = 100; }
			
			$this->source = $o_source;
			$this->source_key = $source_key;
			$this->source_guid = $source_system_guid;

			foreach($this->getTargetsAsServiceClients() as $target_key => $o_target) {
				/** @var CAS\ReplicationService $o_target */
				
				// get setIntrinsics -- fields that are set on the target side (e.g. to tag/mark
				// where records came from if multiple systems are being synced into one)
				$set_intrinsics_config = $this->opo_replication_conf->get('targets')[$target_key]['setIntrinsics'];
				$set_intrinsics_default = is_array($set_intrinsics_config['__default__']) ? $set_intrinsics_config['__default__'] : [];
				$set_intrinsics_source = is_array($set_intrinsics_config[$source_system_guid]) ? $set_intrinsics_config[$source_system_guid] : [];
				$set_intrinsics = array_replace($set_intrinsics_default, $set_intrinsics_source);
				$set_intrinsics_json = null;
				if (is_array($set_intrinsics) && sizeof($set_intrinsics)) {
					$set_intrinsics_json = json_encode($set_intrinsics);
				}
				
				$this->target = $o_target;
				$this->target_key = $target_key;

				$push_media_to = null;
				if($this->opo_replication_conf->get('sources')[$source_key]['push_media']) {
					$push_media_to = $target_key;
				}

				// get latest log id for this source at current target
				$o_result = $o_target->setEndpoint('getlastreplicatedlogid')
					->addGetParameter('system_guid', $source_system_guid)
					->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
					->request();
				;
				if (!$o_result || !is_array($res = $o_result->getRawData()) || isset($res['errors'])) {
				    $this->log(_t("There were errors getting last replicated log id for source %1 and target %2: %3.", $source_key, $target_key, join('; ', $res['errors'])), Zend_Log::ERR);
				    continue;
				}
				
				if($single_log_id_mode > 0) {
					$replicated_log_id = $single_log_id;
					$chunk_size = 1;
				} else {
					$replicated_log_id = (int)$res['replicated_log_id'];

					if($replicated_log_id > 0) {
						$replicated_log_id = ((int) $replicated_log_id) + 1;
					} else {
						$this->log(_t("Couldn't get last replicated log id for source %1 and target %2. Starting at the beginning.",
							$source_key, $target_key), Zend_Log::WARN);
						$replicated_log_id = 1;
					}
					
					// it's possible to configure a starting point in the replication config
					if($min_log_timestamp = $this->opo_replication_conf->get('sources')[$source_key]['from_log_timestamp']) {
						if(!is_numeric($min_log_timestamp)) {
							$o_tep = new TimeExpressionParser($min_log_timestamp);
							$min_log_timestamp = $o_tep->getUnixTimestamps()['start'];
						}

						// get latest log id for this source at current target
						$o_result = $o_target->setEndpoint('getlogidfortimestamp')
							->addGetParameter('timestamp', $min_log_timestamp)
							->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
							->request();
						$min_log_id = $o_result->getRawData()['log_id'];
					} else {
						$min_log_id = (int) $this->opo_replication_conf->get('sources')[$source_key]['from_log_id'];
					}
					if($min_log_id > $replicated_log_id) { 
						$replicated_log_id = $min_log_id; 
						$this->log(_t("[%1] Set log id to minimum (%2).", $source_key, $replicated_log_id), Zend_Log::INFO);
					}
				}
				
				// Check alignment of locales
				if(!$this->_checkLocaleAlignment($source_key, $o_source, $target_key, $o_target)) {
					continue;
				}

				$this->log(_t("Starting replication for source %1 and target %2, log id is %3.",
					$source_key, $target_key, $replicated_log_id), Zend_Log::INFO);

				// get skip if expression
				$skip_if_expression = $this->opo_replication_conf->get('sources')[$source_key]['skipIfExpression'];
				$skip_if_expression_json = null;
				if(is_array($skip_if_expression) && sizeof($skip_if_expression)) {
					$skip_if_expression_json = json_encode($skip_if_expression);
				}

				// get ignore tables
				$ignore_tables = $this->opo_replication_conf->get('sources')[$source_key]['ignoreTables'];
				if(!is_array($ignore_tables)) { $ignore_tables = []; }
				if(is_array($ignore_tables_global = $this->opo_replication_conf->get('sources')['ignoreTables'])) {
					$ignore_tables = array_merge($ignore_tables_global, $ignore_tables);
				}
				$ignore_tables_json = null;
				if(is_array($ignore_tables) && sizeof($ignore_tables)) {
					$ignore_tables_json = json_encode(array_unique(array_values($ignore_tables)));
				}
				
				// get only tables
				$only_tables = $this->opo_replication_conf->get('sources')[$source_key]['onlyTables'];
				if(!is_array($only_tables)) { $only_tables = []; }
				if(is_array($only_tables_global = $this->opo_replication_conf->get('sources')['onlyTables'])) {
					$only_tables = array_merge($only_tables_global, $only_tables);
				}
				$only_tables_json = null;
				if(is_array($only_tables) && sizeof($only_tables)) {
					$only_tables_json = json_encode(array_unique(array_values($only_tables)));
				}
				
				// get includeMetadata list
				$include_metadata = $this->opo_replication_conf->get('sources')[$source_key]['includeMetadata'];
				$include_metadata_json = null;
				if(is_array($include_metadata) && sizeof($include_metadata)) {
					$include_metadata_json = json_encode($include_metadata);
				}
				// get excludeMetadata list
				$exclude_metadata = $this->opo_replication_conf->get('sources')[$source_key]['excludeMetadata'];
				$exclude_metadata_json = null;
				if(is_array($exclude_metadata) && sizeof($exclude_metadata)) {
					$exclude_metadata_json = json_encode($exclude_metadata);
				}
				// get filter_on_access_settings
				if (!is_array($filter_on_access_settings = $this->opo_replication_conf->get('sources')[$source_key]['filter_on_access_settings']) || !sizeof($filter_on_access_settings)) {
					$filter_on_access_settings = null;
				} else {
					$filter_on_access_settings = array_map('intval', $filter_on_access_settings);
				}
				
				$this->get_log_service_params = [
					'skipIfExpression' => $skip_if_expression_json,
					'ignoreTables' => $ignore_tables_json,
					'onlyTables' => $only_tables_json,
					'includeMetadata' => $include_metadata_json,
					'excludeMetadata' => $exclude_metadata_json,
					'pushMediaTo' => $push_media_to
				];
				
				
				$start_replicated_id = $replicated_log_id;
				$is_ok = true;
				
				$this->missing_guids = []; // List of guids missing on target that we'll need to replicate to synthesize subject
				
                $this->source_log_entries_for_missing_guids = [];
                $this->source_log_entries_for_missing_guids_seen_guids = [];
                
                $deferred_guids = [];
                
                // Dictionary with log_ids sent from this source in this session
                $this->sent_log_ids = [];
                
				while(true) { // use chunks of 10 entries until something happens (success/err)
					if($single_log_id_mode) {
						if($single_log_id_mode > 1) { break; }
						$single_log_id_mode++;
					}
					
				    $this->last_log_id = null;
				    if (sizeof($this->sent_log_ids) > 1000000) {
				    	$this->logDebug(_t("Reset sent log list because it was over 1000000 entries. Memory usage was %1", caGetMemoryUsage()), Zend_Log::DEBUG);
				    	$this->sent_log_ids = [];
				    }
				    $this->logDebug(_t("Memory usage: %1", caGetMemoryUsage()), Zend_Log::DEBUG);
				
					// get change log from source, starting with the log id we got above
					$this->source_log_entries = $o_source->setEndpoint('getlog')->clearGetParameters()
						->addGetParameter('from', $replicated_log_id)
						->addGetParameter('skipIfExpression', $skip_if_expression_json)
						->addGetParameter('limit', $chunk_size)
						->addGetParameter('ignoreTables', $ignore_tables_json)
						->addGetParameter('onlyTables', $only_tables_json)
						->addGetParameter('includeMetadata', $include_metadata_json)
						->addGetParameter('excludeMetadata', $exclude_metadata_json)
						->addGetParameter('pushMediaTo', $push_media_to)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request()->getRawData();
									
					if (!is_array($this->source_log_entries) || !sizeof($this->source_log_entries)) {
						$this->logDebug(_t("No new log entries found for source %1 and target %2. Skipping this combination now.",
							$source_key, $target_key), Zend_Log::INFO);
						break;
					}
					
                    $log_ids = array_keys($this->source_log_entries);
                    $start_log_id = array_shift($log_ids);
                    $end_log_id = array_pop($log_ids);
                    if(!$end_log_id) { $end_log_id = $start_log_id; }
                    
                    $this->logDebug(_t("[%1] Found %2 source log entries starting at %3 [%4 - %5].", $this->source_key, sizeof($this->source_log_entries), $replicated_log_id, $start_log_id, $end_log_id), Zend_Log::DEBUG);
                    $filtered_log_entries = null;
					if (
						(bool)$this->opo_replication_conf->get('sources')[$source_key]['push_missing']
						||
						(bool)$this->opo_replication_conf->get('sources')[$target_key]['push_missing']
					) {
						// harvest guids used for updates
						
						$subject_guids = [];
						foreach($this->source_log_entries as $source_log_entry) {
						    if (is_array($source_log_entry['subjects'])) {
						        foreach($source_log_entry['subjects'] as $subject) {
						            $subject_guids[$subject['guid']] = 1;
						        }
						    }
						}
						
						$o_guid_already_exists = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
											->setRequestBody(array_merge(caExtractArrayValuesFromArrayOfArrays($this->source_log_entries, 'guid'), array_keys($subject_guids)))
											->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
											->request();
						$guid_already_exists = $o_guid_already_exists->getRawData();
						
						
						$access_list = is_array($filter_on_access_settings) ? join(";", $filter_on_access_settings) : "";
						
						$access_by_guid = $this->_hasAccess($this->source, $access_list, array_unique(array_merge(caExtractArrayValuesFromArrayOfArrays($this->source_log_entries, 'guid'), array_keys($subject_guids))));

                        // List of log entries to push
					    $this->filtered_log_entries = [];
					    
						$this->guids_to_skip = [];
						
						foreach($this->source_log_entries as $log_id => $source_log_entry) {
						    $this->last_log_id = $log_id;
						    if($this->sent_log_ids[$log_id]) { continue; }	// Don't send a source entry more than once (should never happen)
						   
						   	// TODO: does this make sense?
						    if($this->source_log_entries_for_missing_guids_seen_guids[$source_log_entry['guid']]) {
						    	$this->logDebug(_t("[%1] Skipping primary entry for guid %2 because it's already queued in missing queue.", $this->source_key, $source_log_entry['guid']), Zend_Log::DEBUG);
						    	continue;
						    }
						    
							$logged_exists_on_target = is_array($guid_already_exists[$source_log_entry['guid']]);
							
							// Skip because the one record we're trying to sync has already been sync'ed
							if($single_log_id_mode && $logged_exists_on_target) { continue; }
							
							
						    if ($filter_on_access_settings && ($access_by_guid[$source_log_entry['guid']] !== '?') && !in_array((int)$access_by_guid[$source_log_entry['guid']], $filter_on_access_settings, true) && !$logged_exists_on_target) {
						        continue;	// skip rows for which we have no access
						    }
						    
							if (is_array($source_log_entry['subjects'])) {
							    // Loop through subjects of source (changed) row looking for items to replicate
							    // (Eg. a change to an object-entity relationship should cause both object and entity to be replicated if they otherwise meet replication requirements)
								foreach($source_log_entry['subjects'] as $source_log_subject) {
								   
								    $subject_exists_on_target = is_array($guid_already_exists[$source_log_subject['guid']]);
								    
								    // TODO: do we need this?
								    $subject_is_relationship = Datamodel::isRelationship($source_log_subject['subject_table_num']);
								   
								   	// Check access
								    $have_access_to_subject = true;
								    if ($filter_on_access_settings) {
								        if ($access_by_guid[$source_log_subject['guid']] !== '?') {
								            $have_access_to_subject = in_array((int)$access_by_guid[$source_log_subject['guid']], $filter_on_access_settings, true);
								        }
								    }
								    
                                    //
                                    // Primary records
                                    //
                                    if (!$have_access_to_subject && $subject_exists_on_target) {
                                        // TODO: Should delete from target as it's not public any longer
                                        $this->filtered_log_entries[$log_id] = $source_log_entry;
                                    } elseif($have_access_to_subject && $subject_exists_on_target) {
                                        // Should update on server...
                                        // ... which means pushing change
                                        $this->filtered_log_entries[$log_id] = $source_log_entry;
                                    } elseif($have_access_to_subject && !$subject_exists_on_target) {
                                        // keep in filtered log
                                        //$this->filtered_log_entries[$log_id] = $source_log_entry;
        
										// Should insert on server...
										// ... which means synthesizing log from current state

                                		$this->_findMissingGUID($source_log_subject['guid'], $filter_on_access_settings, 0, $single_log_id_mode);
                                         	
										if(sizeof($this->source_log_entries_for_missing_guids)) {
											$this->logDebug(_t("[%1] Processing missing guid queue (in subject loop).", $source_key), Zend_Log::WARN);
											$this->_pushMissingGUIDs($set_intrinsics_json, $single_log_id_mode);
										}	
                                    }
                                }	// end subject loop							
							}
						}      // end source log entry loop
						
						if(sizeof($this->source_log_entries_for_missing_guids)) {
							$this->source_log_entries_for_missing_guids = array_reverse($this->source_log_entries_for_missing_guids);

							$this->logDebug(_t("[%1] Processing missing guid queue (after source loop).", $source_key), Zend_Log::WARN);
							$this->_pushMissingGUIDs($set_intrinsics_json, $single_log_id_mode);
						}	
					
						// process missing?
						if(sizeof($this->missing_guids)) {
							$missing_guids = $this->missing_guids;
							while(sizeof($missing_guids) > 0) {
								$missing_guid = array_shift($missing_guids);
								$this->logDebug(_t("[%1] Processing missing guid %2 (after source loop).", $this->source_key, $missing_guid), Zend_Log::WARN);
											
								$this->_findMissingGUID($missing_guid, $filter_on_access_settings, 0, $single_log_id_mode);
							}
						}
					}
					
					if (is_array($this->filtered_log_entries)) {
					    if (sizeof($this->filtered_log_entries) == 0) { 
                            $replicated_log_id = $this->last_log_id + 1;
                            
                            $last_log_entry = array_pop($this->source_log_entries);
                            
					        $this->logDebug(_t("[%1] Nothing to push. Incrementing log index to %2 (%3)", $this->source_key, $replicated_log_id, date(DATE_RFC2822, $last_log_entry['log_datetime'])), Zend_Log::DEBUG);
					        
					        $o_resp = $o_target->setRequestMethod('POST')->setEndpoint('setLastLogID')
								->addGetParameter('system_guid', $source_system_guid)
								->addGetParameter('log_id', $this->last_log_id)
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request();
					        continue; 
					    }
					    $this->source_log_entries = $this->filtered_log_entries;
					}
					
					if (!is_array($this->source_log_entries) || !sizeof($this->source_log_entries)) {
						$this->logDebug(_t("No new log entries found for source %1 and target %2. Will try pulling new ones.",
							$source_key, $target_key), Zend_Log::INFO);
					}

					// Process missing?
					if(sizeof($this->missing_guids)) {
						while(sizeof($this->missing_guids) > 0) {
							$missing_guid = array_shift($this->missing_guids);
							$this->logDebug(_t("[%1] Processing missing guid %2 (after source loop).", $this->source_key, $missing_guid), Zend_Log::WARN);
										
							$this->_findMissingGUID($missing_guid, $filter_on_access_settings, 0, $single_log_id_mode);
						}
					}

					// Apply that log at the current target
					ksort($this->source_log_entries, SORT_NUMERIC);
					
					// Remove anything that has already been sent
					foreach($this->source_log_entries as $mlog_id => $entry) {						
						if($this->sent_log_ids[$mlog_id]) {
							$this->logDebug(_t("[%1] Removing log_id %2 because it has already been sent via the missing guid queue", $this->source_key, $mlog_id), Zend_Log::DEBUG);
							$this->source_log_entries[$mlog_id]['SKIP'] = 1; 
						}
					}
					
					$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
						->addGetParameter('system_guid', $source_system_guid)
						->addGetParameter('setIntrinsics', $set_intrinsics_json)
						->setRequestBody($this->source_log_entries)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request();
						
                    $this->logDebug(_t("[%1] Pushed %2 primary entries.", $this->source_key, sizeof($this->source_log_entries)), Zend_Log::DEBUG);
					$response_data = $o_resp->getRawData();

					if (!$o_resp->isOk() || !isset($response_data['replicated_log_id'])) {
						$this->log(_t("There were errors while processing sync for source %1 and target %2: %3", $source_key, $target_key, join(' ', $o_resp->getErrors())), Zend_Log::ERR);
						break;
					} else {
						foreach($this->source_log_entries as $mlog_id => $entry) {						
							// Mark log entry as sent, to ensure we don't send it again in this session
							// (Double sending of a log entry can happen with attributes in some cases where they
							//  are pulled as part of the primary record and then as a dependency)
							$this->sent_log_ids[$mlog_id] = true;
						}
						$replicated_log_id = ($this->last_log_id > 0) ? ($this->last_log_id + 1) : ((int) $response_data['replicated_log_id']) + 1;
						$this->log(_t("Chunk sync for source %1 and target %2 successful.", $source_key, $target_key), Zend_Log::DEBUG);
						$num_log_entries = sizeof($this->source_log_entries);
						$last_log_entry = array_pop($this->source_log_entries);
					   
					   	$this->log(_t("[%1] Pushed %2 log entries. Incrementing log index to %3 (%4).", $source_key, $num_log_entries, $replicated_log_id, date(DATE_RFC2822, $last_log_entry['log_datetime'])), Zend_Log::DEBUG);
						$this->log(_t("[%1] Last replicated log ID is: %2 (%3).", $source_key, $response_data['replicated_log_id'], date(DATE_RFC2822, $last_log_entry['log_datetime'])), Zend_Log::DEBUG);
					}

					if (isset($response_data['warnings']) && is_array($response_data['warnings']) && sizeof($response_data['warnings'])) {
						foreach ($response_data['warnings'] as $log_id => $warns) {
							$this->log(_t("There were warnings while processing sync for source %1, target %2, log id %3: %4.",
								$source_key, $target_key, $log_id, join(' ', $warns)), Zend_Log::WARN);
						}
					}
					
					if(sizeof($this->source_log_entries_for_missing_guids)) {	// try to run missing queue
						$this->logDebug(_t("[%1] Running missing guid queue with %2 guids (after chunk loop).", $this->source_key, sizeof($this->source_log_entries_for_missing_guids)), Zend_Log::DEBUG);
						$this->source_log_entries_for_missing_guids = array_reverse($this->source_log_entries_for_missing_guids);
					
						$this->_pushMissingGUIDs($set_intrinsics_json, $single_log_id_mode);
					}
				}


				if(sizeof($this->source_log_entries_for_missing_guids)) {	// try to run missing queue
					$this->logDebug(_t("[%1] Running missing guid queue with %2 guids (at end of sync).", $this->source_key, sizeof($this->source_log_entries_for_missing_guids)), Zend_Log::DEBUG);
					$this->source_log_entries_for_missing_guids = array_reverse($this->source_log_entries_for_missing_guids);
				
					$this->_pushMissingGUIDs($set_intrinsics_json, $single_log_id_mode);
				}
				if($is_ok) {
					$this->log(_t("Sync for source %1 and target %2 successful.", $source_key, $target_key), Zend_Log::INFO);

					// run dedup if configured
					$dedup_after_replication = $this->opo_replication_conf->get('targets')[$target_key]['deduplicateAfterReplication'];
					$dedup_after_replication_json = null;
					if(is_array($dedup_after_replication) && sizeof($dedup_after_replication)) {
						$dedup_after_replication_json = json_encode($dedup_after_replication);

						// apply that log at the current target
						$o_dedup_response = $o_target->setRequestMethod('POST')->setEndpoint('dedup')
							->addGetParameter('tables', $dedup_after_replication_json)
							->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
							->request();

						$dedup_response = $o_dedup_response->getRawData();

						if (!$o_dedup_response->isOk()) {
							$this->log(_t("There were errors while processing deduplication for at target %1: %2.", $target_key, join(' ', $o_dedup_response->getErrors())), Zend_Log::ERR);
						} else {
							$this->log(_t("Dedup at target %1 successful.", $target_key), Zend_Log::INFO);
							if(isset($dedup_response['report']) && is_array($dedup_response['report'])) {
								foreach($dedup_response['report'] as $t => $c) {
									$this->log(_t("De-duped %1 records for %2.", $c, $t), Zend_Log::DEBUG);
								}
							}
						}
					}
				} else {
					$this->log(_t("Sync for source %1 and target %2 finished, but there were errors.", $source_key, $target_key), Zend_Log::ERR);
				}
			}
		}
	}
	
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _analyzeDependencies() {
		 //
		// Analyze missing guids for dependencies
		//
		$dependency_list = [];
		foreach($this->source_log_entries_for_missing_guids as $missing_guid => $source_log_entries_for_missing_guid) {
			if(!is_array($source_log_entries_for_missing_guid)) { continue; }
			
			foreach($source_log_entries_for_missing_guid as $entry) {
				if(is_array($entry['subjects'])) {
					foreach($entry['subjects'] as $dep_subject) {
						if (!isset($this->source_log_entries_for_missing_guids[$dep_subject['guid']])) { 
							continue; 
						}
						if ($dep_subject['subject_table_num'] == 4) { continue; }		// attributes should not be dependencies
						
						if ($entry['guid'] === $dep_subject['guid']) { continue; }		// don't make item dependency of itself
						$dependency_list[$entry['guid']][$dep_subject['guid']] = true;
					}
				}
				if(is_array($entry['snapshot'])) {
					foreach($entry['snapshot'] as $snk => $snv) {
						if(preg_match("!([A-Za-z0-9_]+)_guid$!", $snk, $matches)) {
							if(
								is_array(Datamodel::getFieldInfo($entry['logged_table_num'], $matches[1]))
								||
								is_array(Datamodel::getFieldInfo($entry['logged_table_num'], $matches[1].'_id'))
							) { 
								if (!in_array($matches[1], ['lot', 'lot_id', 'parent', 'parent_id', 'item', 'item_id'], true)) { 
									continue; 
								} 		// @TODO: just do dependencies for lots and parents for now; eventually we'll need to consider list items, relationship types, et al.
								if ($entry['guid'] === $snv) { continue; }										// don't make item dependency of itself
								
								if (!isset($this->source_log_entries_for_missing_guids[$snv])) { continue; }
								$this->logDebug(_t("[%1] Added %2 [%3] as dep from snapshot for %4.", $this->source_key, $snk, $snv, $entry['guid']),Zend_Log::DEBUG);
								$dependency_list[$entry['guid']][$snv] = true;
							}
						}
					}
				}
			}
		}
		return $dependency_list;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 * Find dependencies for $missing_guid that are not already present on the target
	 *
	 * @param string $missing_guid
	 * @param string $filter_on_access_settings
	 *
	 * @return 
	 */
	public function _findMissingGUID(string $missing_guid, $filter_on_access_settings, int $level=0, ?bool $single_log_id_mode=false) : ?bool {		
			
		if ($this->source_log_entries_for_missing_guids_seen_guids[$missing_guid]) { 
			$this->logDebug(_t("[%1] Skipped %2 because we've seen it already.", $this->source_key, $missing_guid), Zend_Log::DEBUG);
			return null; 
		} 
		
		$seen_list = [];
		
		// Pull log for "missing" guid we need to replicate on target
		$this->logDebug(_t("[%1] Fetching log for missing guid %2.", $this->source_key, $missing_guid), Zend_Log::DEBUG);
		$log_for_missing_guid = $this->source->setEndpoint('getlog')
			->clearGetParameters()
			->addGetParameter('forGUID', $missing_guid)
			->addGetParameter('skipIfExpression', $this->get_log_service_params['skipIfExpression'])
			->addGetParameter('ignoreTables', $this->get_log_service_params['ignoreTables'])
			->addGetParameter('onlyTables', $this->get_log_service_params['onlyTables'])
			->addGetParameter('includeMetadata', $this->get_log_service_params['includeMetadata'])
			->addGetParameter('excludeMetadata', $this->get_log_service_params['excludeMetadata'])
			->addGetParameter('pushMediaTo', $this->get_log_service_params['pushMediaTo'])
			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
			->request()->getRawData();
			
		if (is_array($log_for_missing_guid)) {
			$dependent_guids = [];

			// Check access settings for dependent rows; we only want to replicate rows that
			// meet the configured access requirements (Eg. public rows only)
			$access_for_dependent = $this->_hasAccess($this->source, $access_list, array_unique(caExtractArrayValuesFromArrayOfArrays($log_for_missing_guid, 'guid')));    
			
			$filtered_log_for_missing_guid = [];  
			ksort($log_for_missing_guid, SORT_NUMERIC);
		   
			//$this->logDebug(_t("[%1] Missing log for %2 is %3", $this->source_key, $missing_guid, print_R($log_for_missing_guid, true)), Zend_Log::DEBUG);
			
			foreach($log_for_missing_guid as $missing_entry) {
				if (($missing_entry['log_id'] != 1) && $this->sent_log_ids[$missing_entry['log_id']]) { 
					$this->logDebug(_t("[%1] Skipped missing log_id %2 because it has already been sent.", $this->source_key, $missing_entry['log_id']), Zend_Log::WARN);    
					continue; 
				}	
				
				if ($filter_on_access_settings && ($access_for_dependent[$missing_entry['guid']] !== '?') && !in_array((int)$access_for_dependent[$missing_entry['guid']], $filter_on_access_settings, true)) {
					continue; // Skip rows for which we have no access;
				}
				
				$filtered_log_for_missing_guid[$missing_entry['log_id']] = $missing_entry;
				
				if(isset($this->source_log_entries[$missing_entry['log_id']]) || isset($this->filtered_log_entries[$missing_entry['log_id']])) {
					$this->logDebug(_t("[%1] Remove log_id %2 from the primary source log because it part of a missing guid log for %3.", $this->source_key, $missing_entry['log_id'], $missing_entry['guid']),Zend_Log::DEBUG);
					unset($this->source_log_entries[$missing_entry['log_id']]); // make "missing" log entries aren't in the primary source log
					unset($this->filtered_log_entries[$missing_entry['log_id']]);
				}
				
				// Add guids for dependencies referenced by this log entry
				if(is_array($missing_entry['snapshot'])) {
					$dependent_guids = array_unique(array_merge($dependent_guids, $new_dependent_guids = array_values(array_filter($missing_entry['snapshot'], function($v, $k) use ($missing_entry, $missing_guid) { 
						if ($v == $missing_guid) { 
							return false; 
						}
						
						//if (Datamodel::isRelationship($missing_entry['logged_table_num'])) { return false; }
						
						if(preg_match("!([A-Za-z0-9_]+)_guid$!", $k, $matches) && preg_match("!^[a-z0-9]+\-[a-z0-9]+\-[a-z0-9]+\-[a-z0-9]+\-[a-z0-9]+$!i", $v)) {
							if(
								is_array(Datamodel::getFieldInfo($missing_entry['logged_table_num'], $matches[1]))
								||
								is_array(Datamodel::getFieldInfo($missing_entry['logged_table_num'], $matches[1].'_id'))
							) { 
								
								//if($matches[1] === 'object_id') { return false; }
								return true; 
							}
						}
						return false;
					}, ARRAY_FILTER_USE_BOTH))));
					//$this->logDebug(_t("[%1] Added %2 new dependent guids for %3: %4.", $this->source_key, sizeof($new_dependent_guids), $missing_entry['guid'], print_R($new_dependent_guids, true)),Zend_Log::DEBUG);
				}
			}
			if (sizeof($filtered_log_for_missing_guid) == 0) { 
				$this->logDebug(_t("[%1] Empty log for %2: %3.", $this->source_key, $missing_guid, $level),Zend_Log::DEBUG);
				return null; 
			}
			
			// Check for presence and access of dependencies on target
			// We will only replicate rows meeting access requirements and not already on the target
			//
			$dependent_guids = array_filter($dependent_guids, 'strlen');
			if(sizeof($dependent_guids) > 0) {
				$tries = 0; $guids_exist_for_dependencies = null;
				
				while(($tries < 5) && (!is_array($guids_exist_for_dependencies)))  {
					$o_guids_exist_for_dependencies = $this->target->setRequestMethod('POST')->setEndpoint('hasGUID')
										->setRequestBody($dependent_guids)
										->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
										->request();
					$guids_exist_for_dependencies = $o_guids_exist_for_dependencies->getRawData();
					$tries++;
				}  
				
				$dependent_guids = array_keys(array_filter($guids_exist_for_dependencies, function($v) { return !is_array($v); }));
			   
			   	// Add dependent guid lists to "missing" list; this will force dependencies to be processed through this loop
				//$this->missing_guids = array_unique(array_filter(array_merge($this->missing_guids, $dependent_guids)), 'strlen'); 
				
				if (is_array($filter_on_access_settings)) {
					// Filter missing guid list using access criteria
					$access_for_missing = $this->_hasAccess($this->source, $access_list, $dependent_guids);
					
					if (is_array($access_for_missing)) {
						$dependent_guids = array_unique(array_filter(array_keys(array_filter($access_for_missing, function($v) use ($filter_on_access_settings) { return (($v == '?') || (in_array((int)$v, $filter_on_access_settings, true))); })), 'strlen'));
						$this->guids_to_skip = array_filter(array_unique(array_merge($this->guids_to_skip, array_keys(array_filter($access_for_missing, function($v) use ($filter_on_access_settings) { return !in_array((int)$v, $filter_on_access_settings, true); })))), 'strlen');
					} else {
						$this->logDebug(_t("[%1] Failed to retrieve access values for missing GUID.", $this->source_key),Zend_Log::DEBUG);
					}
				}
			} 
			
			if($level < 1) {
				$new_dependent_guids = [];
				foreach($dependent_guids as $dep_guid) {
					$this->logDebug(_t("[%1] Run findMissingGUID for %2 / %3", $this->source_key, $dep_guid, $missing_guid),Zend_Log::DEBUG);
					if(!$this->_findMissingGUID($dep_guid, $filter_on_access_settings, $level+1, $single_log_id_mode)) {
						$new_dependent_guids[] = $dep_guid;
					}
					$dependent_guids = $new_dependent_guids;
				}	
			}
			
			$this->logDebug(_t("[%1] There are %2 dependent guids: %3", $this->source_key, sizeof($dependent_guids), print_r($this->_dumpDependencies($o_source, $dependent_guids), true)),Zend_Log::DEBUG);
			//$this->logDebug(_t("[%1] There are %2 missing guids: %3", $this->source_key, sizeof($this->missing_guids), print_r($this->_dumpDependencies($o_source, $this->missing_guids), true)),Zend_Log::DEBUG);
		 
			ksort($filtered_log_for_missing_guid, SORT_NUMERIC);   
			$this->logDebug(_t("[%1] Found %2 entries for %3.", $this->source_key, sizeof($filtered_log_for_missing_guid), $missing_guid), Zend_Log::DEBUG);

			if(sizeof($dependent_guids) == 0) {                                                    
				// Missing guid has no outstanding dependencies so push it immediately
				$this->logDebug(_t("[%1] Immediately pushing %2 missing entries for %3.", $this->source_key, sizeof($filtered_log_for_missing_guid), $missing_guid), Zend_Log::DEBUG);
				
				$has_attr_guids = [];
				while(sizeof($filtered_log_for_missing_guid) > 0) {
					$entries = [];
					$first_missing_log_id = null;

					while(sizeof($filtered_log_for_missing_guid) > 0) {
						$log_entry = array_shift($filtered_log_for_missing_guid);
						
						$mlog_id = $log_entry['log_id'];
						
						// Don't send log entry for missing guid more than once 
						// (can happen with attributes where they can be pulled as missing on the primary record 
						//  and then as dependencies of the primary record)
						if($this->sent_log_ids[$mlog_id]) { 
							$this->logDebug(_t("[%1] Skipped log_id %2 becaue it has already been sent.", $this->source_key, $mlog_id), Zend_Log::WARN);
							continue; 
						} 

						if (!$mlog_id) { 
							$this->logDebug(_t("[%1] Skipped entry because it lacks a log_id.", $this->source_key), Zend_Log::WARN);
							continue; 
						}
						
						if (!$single_log_id_mode && ($mlog_id > $this->last_log_id)) { 
							$this->logDebug(_t("[%1] Skipped entry (%2) because it's in the future.", $this->source_key, $mlog_id),Zend_Log::DEBUG);
							continue; 
						}
						
						// is this an attribute value? If so then check for existence of related attribute 
						// since CA didn't write log entries for this in some versions
						if (($log_entry['logged_table_num'] == 3) && ($attr_guid = caGetOption(['attribute_guid', 'attribute_id_guid'], $log_entry['snapshot'], null))) {
							$o_chk_attr_existence = $this->target->setRequestMethod('POST')->setEndpoint('hasGUID')
									->setRequestBody([$attr_guid])
									->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
									->request();
							$chk_attr_existence = $o_chk_attr_existence->getRawData();
							if ($chk_attr_existence[$attr_guid] == '???') {
								// need to push attribute
								$attr_log = $this->source->setEndpoint('getlog')
									->clearGetParameters()
									->addGetParameter('forGUID', $attr_guid)
									->addGetParameter('skipIfExpression', $this->get_log_service_params['skipIfExpression'])
									->addGetParameter('ignoreTables', $this->get_log_service_params['ignoreTables'])
									->addGetParameter('onlyTables', $this->get_log_service_params['onlyTables'])
									->addGetParameter('includeMetadata', $this->get_log_service_params['includeMetadata'])
									->addGetParameter('excludeMetadata', $this->get_log_service_params['excludeMetadata'])
									->addGetParameter('pushMediaTo', $this->get_log_service_params['pushMediaTo'])
									->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
									->request()->getRawData();
								if (is_array($attr_log)) {
									$acc = [];
									foreach($attr_log as $l => $x) {
										if (!$seen_list[$x['log_id']]) {
											$seen_list[$x['log_id']] = $x['guid'];
											$entries[$x['log_id']] = $acc[$x['log_id']] = $x;
											// mark as seen so we don't process the same thing twice
											//$this->source_log_entries_for_missing_guids_seen_guids[$x['guid']] = true;
										}
									}
									$size = sizeof($acc);
									if ($size > 0) {
										$this->logDebug(_t("[%1] Adding %4 unpushed attribute log entries starting with %2 for %3 for immediate push.", $this->source_key, $first_missing_log_id, $missing_guid, $size), Zend_Log::DEBUG);
									}
								}
							}
						}
						
						if (!$first_missing_log_id) { $first_missing_log_id = $mlog_id; }

						$entries[$mlog_id] = $log_entry;
						
						// mark as seen so we don't process the same thing twice
						//$this->source_log_entries_for_missing_guids_seen_guids[$log_entry['guid']] = true;
						if ((is_array($entries) && (sizeof($entries) >= 10)) || (is_array($source_log_entries_for_missing_guid) && (sizeof($source_log_entries_for_missing_guid) == 0))) { break; }
					}
					
					ksort($entries, SORT_NUMERIC);
					
					$size = sizeof($entries);
					if ($size > 0) {
						$this->logDebug(_t("[%1] Immediately pushing missing log entries starting with %2 for %3", $this->source_key, $first_missing_log_id, $missing_guid), Zend_Log::DEBUG);
			
					
						$o_backlog_resp = $this->target->setRequestMethod('POST')->setEndpoint('applylog')
							->addGetParameter('system_guid', $this->source_guid)
							->addGetParameter('setIntrinsics', $set_intrinsics_json)
							->setRequestBody($entries)
							->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
							->request();
					
						$response_data = $o_backlog_resp->getRawData();
						
						if(!isset($response_data['replicated_log_id'])) {
							$this->logDebug(_t("[%1] _findMissingGUID applylog with %3 entries failed: %2.", $this->source_key, print_R($response_data, true), sizeof($entries)), Zend_Log::DEBUG);
						} else {
							foreach($entries as $mlog_id => $entry) {                                                        	
								// Mark log entry as sent, to ensure we don't send it again in this session
								// (Double sending of a log entry can happen with attributes in some cases where they
								//  are pulled as part of the primary record and then as a dependency)
								$this->sent_log_ids[$mlog_id] = true;
							}
						}
					}
				}
			} else {
				// Missing guid has dependencies; queue it for replication
				$this->source_log_entries_for_missing_guids[$missing_guid] = $filtered_log_for_missing_guid;
				$this->logDebug(_t("[%1] Queued %2 for later replication because it has %3 dependencies.", $this->source_key, $missing_guid, sizeof($dependent_guids)), Zend_Log::WARN);
				
				// Mark as seen so we don't process the same thing twice
				$this->source_log_entries_for_missing_guids_seen_guids[$missing_guid] = true;
				return false;
			}
		} else {
			$this->logDebug(_t("[%1] No log for %2.", $this->source_key, $missing_guid), Zend_Log::WARN);
		}
		
		return true;
	}	
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _pushMissingGUIDs($set_intrinsics, ?bool $single_log_id_mode=false) {
		$dependency_list = $this->_analyzeDependencies($this->source_log_entries_for_missing_guids);
		//$this->logDebug(_t("[%1] Got dep list %2", $this->source_key, print_R($dependency_list, true)), Zend_Log::DEBUG);
		//
		// Attempt to push missing guids subject to dependencies
		//
		$seen_list = [];
		foreach($this->source_log_entries_for_missing_guids as $missing_guid => $source_log_entries_for_missing_guid) {     
			if (is_array($dependency_list[$missing_guid]) && (($s = sizeof($dependency_list[$missing_guid])) > 0)) { 
				$this->logDebug(_t("[%1] Holding push of %2 missing entries for %3 because it still has %4 dependencies: %5.", $this->source_key, sizeof($source_log_entries_for_missing_guid), $missing_guid, $s, print_R(array_keys($dependency_list[$missing_guid]), true)), Zend_Log::DEBUG);
				continue;
			}                          
			foreach(array_keys($dependency_list) as $k1) {
				unset($dependency_list[$k1][$missing_guid]);
			}
		
			$this->logDebug(_t("[%1] Pushing %2 missing entries for %3.", $this->source_key, sizeof($source_log_entries_for_missing_guid), $missing_guid), Zend_Log::DEBUG);
			
			ksort($source_log_entries_for_missing_guid, SORT_NUMERIC);
			while(sizeof($source_log_entries_for_missing_guid) > 0) {
				$entries = [];
				$first_missing_log_id = null;
			
				while(sizeof($source_log_entries_for_missing_guid) > 0) {
					$log_entry = array_shift($source_log_entries_for_missing_guid);
					$mlog_id = $log_entry['log_id'];
					
					if ($log_id == $mlog_id) { 
						$this->logDebug(_t("[%1] Skipped %2 because it is the current log entry.", $this->source_key, $mlog_id), Zend_Log::DEBUG);
						continue; 
					} // don't pull in current log entry
					
					if (isset($this->source_log_entries[$mlog_id])) { 
						$this->logDebug(_t("[%1] Skipped %2 because it is in the current source log.", $this->source_key, $mlog_id), Zend_Log::DEBUG);
						continue; 
					} // don't push source log entry for a second time
				
										
					// Don't send log entry for missing guid more than once 
					// (can happen with attributes where they can be pulled as missing on the primary record 
					//  and then as dependencies of the primary record)
					if($this->sent_log_ids[$mlog_id]) { 
						$this->logDebug(_t("[%1] Skipped %2 because it has already been sent.", $this->source_key, $mlog_id), Zend_Log::DEBUG);
						continue; 
					}
					if (!$mlog_id) { 
						$this->logDebug(_t("[%1] Skipped entry because it lacks a log_id %2.", $this->source_key, print_R($log_entry, true)), Zend_Log::DEBUG);
						continue; 
					}
					if (!$single_log_id_mode && ($mlog_id > $this->last_log_id)) { 
						$this->logDebug(_t("[%1] Skipped %2 (during push of missing?) because it's in the future.", $this->source_key, $mlog_id),Zend_Log::DEBUG);
						continue; 
					}
				
					foreach($log_entry['snapshot'] as $k => $v) {
						if (in_array($v, $this->guids_to_skip, true)) { 
							if(($k === 'user_id') || ($k === 'user_id_guid')) { continue; }
							if (preg_match("!parent!", $k)) {	
								// If no access to parent, remove dependency and import as standalone record
								// @TODO: maybe make this a configurable option?
								unset($log_entry['snapshot'][$k]);
								unset($log_entry['snapshot'][str_replace("_guid", "", $k)]);
								$this->logDebug(_t("[%1] Removed parent from snapshot in log_id %2 because parent dependency %3 is not available.", $this->source_key, $mlog_id, $v),Zend_Log::DEBUG);
							} else {
								// SKIP log entry because dependency is not available
								$this->logDebug(_t("[%1] Skipped %2 because dependency %3 is not available.", $this->source_key, $mlog_id, $v),Zend_Log::DEBUG);
								continue(2); 
							}
						}
					}
				
					if (is_array($dependency_list[$log_entry['guid']]) && (($s = sizeof($dependency_list[$log_entry['guid']])) > 0)) { 
						// Skip log entry because it still has dependencies
						$this->logDebug(_t("[%1] Skipped log_id %2 [%3] because it still has %4 dependencies: %5.", $this->source_key, $mlog_id, $log_entry['guid'], $s, print_R(array_keys($dependency_list[$log_entry['guid']]), true)),Zend_Log::DEBUG);
						continue;
					}
				
					if ($seen_list[$mlog_id]) { 
						// Skip log entry because it has already been pushed
						$this->logDebug(_t("[%1] Skipped %2 because it has already been pushed.", $this->source_key, $mlog_id), Zend_Log::DEBUG);
						continue; 
					}
			   
					if (!$first_missing_log_id) { $first_missing_log_id = $mlog_id; }
				
					 if (($log_entry['logged_table_num'] == 3) && ($attr_guid = caGetOption(['attribute_guid', 'attribute_id_guid'], $log_entry['snapshot'], null))) {
						$o_chk_attr_existence = $this->target->setRequestMethod('POST')->setEndpoint('hasGUID')
								->setRequestBody([$attr_guid])
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request();
						// TODO: check that client returned data
						$chk_attr_existence = $o_chk_attr_existence->getRawData();
						if ($chk_attr_existence[$attr_guid] == '???') {
							// need to push attribute
							$attr_log = $this->source->setEndpoint('getlog')
								->clearGetParameters()
								->addGetParameter('forGUID', $attr_guid)
								->addGetParameter('skipIfExpression', $this->get_log_service_params['skipIfExpression'])
								->addGetParameter('ignoreTables', $this->get_log_service_params['ignoreTables'])
								->addGetParameter('onlyTables', $this->get_log_service_params['onlyTables'])
								->addGetParameter('includeMetadata', $this->get_log_service_params['includeMetadata'])
								->addGetParameter('excludeMetadata', $this->get_log_service_params['excludeMetadata'])
								->addGetParameter('pushMediaTo', $this->get_log_service_params['pushMediaTo'])
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request()->getRawData();
							
							$attr_log = is_array($attr_log) ? array_filter($attr_log, function($v) { return !$v['SKIP']; }) : null;
							if (is_array($attr_log) && sizeof($attr_log)) {
								$acc = [];
								foreach($attr_log as $x) {
									if ($x['log_id'] == 1) {
										$synth_log_id = null;
										$co=0;
									
										// look for first log entry that defines row_guid
										$base_log_id = $mlog_id - 1; // If all else fails we'll map sync attr before current, as that has the best odds of working
										$sorted_missing_log = $attr_log;
										ksort($sorted_missing_log, SORT_NUMERIC);
										foreach($sorted_missing_log as $l) {
											if (($l['logged_table_num'] == 3) && ($l['snapshot']['attribute_guid'] === $x['guid'])) {
												$base_log_id = (int)$l['log_id'] - 1;    // insert before attribute value
												break;
											}
										}
									
										do {
											$co++;
										
											$synth_log_id = "{$base_log_id}.{$co}";
										} while(in_array((string)$synth_log_id, array_keys($entries), true));
									
										if ($synth_log_id) {
											$x['log_id'] = $x['i'] = $synth_log_id;
										} else {
											$this->logDebug("Unable to map synth attr $attr_guid log_id.", Zend_Log::DEBUG);
										}
									}
									if (!$seen_list[$x['log_id']] && ($x['log_id'] !== $missing_guid)) {
										$seen_list[$x['log_id']] = $x['guid'];
										$entries[$x['log_id']] = $acc[$x['log_id']] = $x;
									}
								}
								$size = sizeof($acc);
								if ($size > 0) {
									$this->logDebug(_t("[%1] Adding %4 unpushed attribute log entries starting with %2 for %3 [%5].", $this->source_key, $first_missing_log_id, $missing_guid, $size, $attr_guid), Zend_Log::DEBUG);
								}
							}
						}
					}
			
					if(!$seen_list[$mlog_id]) {
						$entries[$mlog_id] = $log_entry;
						$seen_list[$mlog_id] = $missing_guid;
					}
				
					if ((sizeof($entries) >= 200) || (sizeof($source_log_entries_for_missing_guid) == 0)) { break; }
				}
				ksort($entries, SORT_NUMERIC);
		
				$size = sizeof($entries);
			
				if ($size > 0) {
					$this->logDebug(_t("[%1] Pushing %4 missing queue log entries starting with %2 for %3", $this->source_key, $first_missing_log_id, $missing_guid, $size), Zend_Log::DEBUG);
				
					$o_backlog_resp = $this->target->setRequestMethod('POST')->setEndpoint('applylog')
						->addGetParameter('system_guid', $this->source_guid)
						->addGetParameter('setIntrinsics', $set_intrinsics)
						->setRequestBody($entries)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request();
					$response_data = $o_backlog_resp->getRawData();
					if(!isset($response_data['replicated_log_id'])) {
						$this->logDebug(_t("[%1]  _pushMissingGUIDs applylog with %3 entries failed: %2.", $this->source_key, print_R($response_data, true), sizeof($entries)), Zend_Log::DEBUG);
					} else {
						foreach($entries as $mlog_id => $entry) {					
							// Mark log entry as sent, to ensure we don't send it again in this session
							// (Double sending of a log entry can happen with attributes in some cases where they
							//  are pulled as part of the primary record and then as a dependency)
							$this->sent_log_ids[$mlog_id] = true;
						}
					}
				}
			}
		
			unset($this->source_log_entries_for_missing_guids[$missing_guid]);
			unset($this->source_log_entries_for_missing_guids_seen_guids[$missing_guid]);
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _hasAccess($o_source, $access, $guids) {
		// if(sizeof($this->guid_access_cache) > 100000) { 
// 			$this->guid_access_cache = array_slice($this->guid_access_cache, 75000);
// 		}
// 		// Check if guids are cached
// 		$cached_res = [];
// 		$filtered_guids = [];
// 		foreach($guids as $guid) {
// 			if(isset($this->guid_access_cache[$guid])) {
// 				$cached_res[$guid] = $this->guid_access_cache[$guid];
// 				continue;
// 			}
// 			$filtered_guids[] = $guid;
// 		}
// 		$filtered_guids = array_unique($filtered_guids);
		
		$r = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
			->addGetParameter('access', $access)
			->setRequestBody($guids)
			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
			->request();
			
		$res = $r->getRawData();
		if(is_array($res)) {
			// foreach($res as $guid => $access) {
// 				$this->guid_access_cache[$guid] = $access;
// 			}
		
			//$res = array_merge($res, $cached_res);
		} else {
			new ApplicationException(_t('Could not get access data'));
		}
		
		return $res;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _dumpDependencies($o_source, array $dependencies) {
		return $dependencies;
		$o_deps = $o_source->setRequestMethod('POST')->setEndpoint('hasGUID')
							->setRequestBody(array_merge(array_keys($dependencies)))
							->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
							->request();
		$deps = $o_deps->getRawData();
		return $deps;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _checkLocaleAlignment(string $source_key, $o_source, string $target_key, $o_target) {
		// Check alignment of locales
		$o_result = $o_source->setEndpoint('getGUIDsForTable')
			->addGetParameter('table', 'ca_locales')
			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
			->request();
		;
		if (!$o_result || !is_array($source_locales = $o_result->getRawData()) || isset($source_locales['errors'])) {
			$this->log(_t("There were errors getting locale list for source %1: %2.", $source_key, join('; ', $source_locales['errors'])), Zend_Log::ERR);
			return false;
		}
		$o_result = $o_target->setEndpoint('getGUIDsForTable')
			->addGetParameter('table', 'ca_locales')
			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
			->request();
		;
		if (!$o_result || !is_array($target_locales = $o_result->getRawData()) || isset($target_locales['errors'])) {
			$this->log(_t("There were errors getting locale list for target %1: %2.", $target_key, join('; ', $target_locales['errors'])), Zend_Log::ERR);
			return false;
		}
		if(!sizeof(array_intersect($source_locales, $target_locales))) {
			$this->log(_t("Locales appear to be mis-aligned for source %1 and target %2. Skipping target.",
				$source_key, $target_key), Zend_Log::WARN);
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function forceSyncOfLatest(string $source, string $guid, ?array $options=null) {
		if(!is_array($targets = caGetOption('targets'. $options, null))) { 
			return null;
		}
		
		// TODO
	}	
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function syncGUIDs(string $source, array $guids, ?array $options=null) {
		$o_source = $this->getSourcesAsServiceClients(['source' => $source]);
		if($o_source) {
			
			$resp = $o_source[$source]->setRequestMethod('POST')->setEndpoint('getLastLogIDsForGUIDs')
				->setRequestBody($guids)
				->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
				->request();
			
			$res = $resp->getRawData();
			
			foreach($res as $r) {
				$this->replicate(['source' => $source, 'log_id' => $r['log_id']]);
			}
			return true;
		}
		throw new ApplicationException(_t('Invalid source'));
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function syncPublic(string $source,  ?array $options=null) {
		$o_source = $this->getSourcesAsServiceClients(['source' => $source]);
		if($o_source) {
			
			$resp = $o_source[$source]->setRequestMethod('GET')->setEndpoint('getPublicGUIDs')
				->addGetParameter('table', caGetOption('table', $options, 'ca_objects'))
				->addGetParameter('access', '1')
				->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
				->request();
			
			$guids = $resp->getRawData();
			self::syncGUIDs($source, $guids);
			return true;
		}
		throw new ApplicationException(_t('Invalid source'));
	}
	# --------------------------------------------------------------------------------------------------------------
}
