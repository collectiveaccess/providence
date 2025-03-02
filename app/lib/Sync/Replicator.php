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
	 * Starting log id of current chunk
	 */
	protected $start_log_id;
	
	/**
	 * Ending log id of current chunk
	 */
	protected $end_log_id;
	
	/**
	 * Last log id processed for current sync
	 */
	protected $last_log_id;
	
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
	
	/**
	 *
	 */
	protected $access_list = [];
	
	/**
	 *
	 */
	protected $missing_guid_created = [];
	
	/**
	 *
	 */
	protected $unresolved_guids = [];
	
	/**
	 *
	 */
	protected $set_intrinsics_json = null;
	
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
	protected function getTargetsAsServiceClients(?array $options=null) {
		$targets = $this->opo_replication_conf->get('targets');
		if(($enabled_targets = caGetOption('target', $options, null)) || ($enabled_targets = $this->opo_replication_conf->getList('enabled_targets'))) {
			if(!is_array($enabled_targets)) { $enabled_targets = [$enabled_targets]; }
			$filtered_targets = [];
			foreach($enabled_targets as $s) {
				if(isset($targets[$s])) {
					$filtered_targets[$s] = $targets[$s];
				}
			}
			$targets = $filtered_targets;
		}
		if(!is_array($targets)) { throw new Exception('No targets configured'); }

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
		$start_time = time();
		foreach($this->getSourcesAsServiceClients($options) as $source_key => $o_source) {
			/** @var CAS\ReplicationService $o_source */
			
			// Sync a single log_id from a specific source?
			$single_log_id_mode = false;
			if(caGetOption('source', $options, null) && $single_log_id = caGetOption('log_id', $options, null)) {
				$single_log_id_mode = 1;
				
				$this->logDebug(_t("[%1] Set single log mode.", $source_key), Zend_Log::INFO);
			}

			// Get GUID for data source
			$o_result = $o_source->setEndpoint('getsysguid')->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)->request();
			if(!$o_result || !($res = $o_result->getRawData()) || !(strlen($source_system_guid = $res['system_guid']))) {
				$this->log(
					_t("[%1] Could not get system GUID for one of the configured replication sources: {$source_key}. Skipping source.", $source_key),
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
				$this->set_intrinsics_json = null;
				if (is_array($set_intrinsics) && sizeof($set_intrinsics)) {
					$this->set_intrinsics_json = json_encode($set_intrinsics);
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
				    $this->log(_t("[%1] There were errors getting last replicated log id for source %1 and target %2: %3.", $source_key, $target_key, join('; ', $res['errors'])), Zend_Log::ERR);
				    continue;
				}
				
				$single_log_id_mode_max_log_id = null;
				
				if($single_log_id_mode > 0) {
					$replicated_log_id = $single_log_id;
					$single_log_id_mode_max_log_id = $res['replicated_log_id'] ?? null;
					$chunk_size = 1;
				} elseif($force_log_id = (int) $this->opo_replication_conf->get('sources')[$source_key]['force_from_log_id']) {
					$replicated_log_id = $force_log_id;
					$this->log(_t("[%1] Set log id to forced value (%2).", $source_key, $replicated_log_id), Zend_Log::INFO);
				} else {
					$replicated_log_id = (int)$res['replicated_log_id'];

					if($replicated_log_id > 0) {
						$replicated_log_id = ((int) $replicated_log_id) + 1;
					} else {
						$this->log(_t("[%1] Couldn't get last replicated log id for source %1 and target %2. Starting at the beginning.",
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

				$this->log(_t("[%1] Starting replication for source %1 and target %2, log id is %3.",
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
				
				$this->access_list = is_array($filter_on_access_settings) ? $filter_on_access_settings : null;
				
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
				
                //$this->source_log_entries_for_missing_guids = [];
               	$this->source_log_entries_for_missing_guids_seen_guids = [];
    
                // Dictionary with log_ids sent from this source in this session
                $this->sent_log_ids = [];
                
                $is_push_missing = ((bool)$this->opo_replication_conf->get('sources')[$source_key]['push_missing']
									||
									(bool)$this->opo_replication_conf->get('sources')[$target_key]['push_missing']) ? 1 : 0;
                
				while(true) { // use chunks of 10 entries until something happens (success/err)
					if($single_log_id_mode) {
						if($single_log_id_mode > 1) { break; }
						$single_log_id_mode++;
					}
					
				    $this->last_log_id = null;
				    if (sizeof($this->sent_log_ids) > 1000000) {
				    	$this->logDebug(_t("[%1] Reset sent log list because it was over 1000000 entries. Memory usage was %2", $source_key, caGetMemoryUsage()), Zend_Log::DEBUG);
				    	$this->sent_log_ids = [];
				    }
				    $this->logDebug(_t("[%1] Memory usage: %2", $source_key, caGetMemoryUsage()), Zend_Log::DEBUG);
				
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
						->addGetParameter('push_missing', $is_push_missing)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request()->getRawData();
									
					if (!is_array($this->source_log_entries) || !sizeof($this->source_log_entries)) {
						$this->logDebug(_t("[%1] No new log entries found for source %1 and target %2. Skipping this combination now.",
							$source_key, $target_key), Zend_Log::INFO);
						break;
					}
					
                    $log_ids = array_keys($this->source_log_entries);
                    $start_log_id = $this->start_log_id = array_shift($log_ids);
                    $end_log_id = $this->end_log_id = array_pop($log_ids);
                    if(!$end_log_id) { $end_log_id = $start_log_id; }
                    
                    $this->logDebug(_t("[%1] Found %2 source log entries starting at [%4 - %5].", $this->source_key, sizeof($this->source_log_entries), $replicated_log_id, $start_log_id, $end_log_id), Zend_Log::DEBUG);
                    //$this->logDebug(_t("[%1] %2", $this->source_key, print_r($this->source_log_entries,true)), Zend_Log::DEBUG);
                    
                    $filtered_log_entries = null;
					if ($is_push_missing) {
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
						
						$access_by_guid = $this->_hasAccess($this->source, array_unique(array_merge(caExtractArrayValuesFromArrayOfArrays($this->source_log_entries, 'guid'), array_keys($subject_guids))));

                        // List of log entries to push
					    $this->filtered_log_entries = [];
						
						foreach($this->source_log_entries as $log_id => $source_log_entry) {
						    $this->last_log_id = $log_id;
						    if($this->sent_log_ids[$log_id]) { continue; }	// Don't send a source entry more than once (should never happen)
						   	if($source_log_entry['SKIP'] ?? null) { 
						   		//$this->logDebug(_t("[%1] Skipping log_id %2 because is marked as SKIP", $this->source_key, $log_id), Zend_Log::DEBUG);
						   		continue; 
						   	}
						   	
							$logged_exists_on_target = is_array($guid_already_exists[$source_log_entry['guid']]);
							
							// Skip because the one record we're trying to sync has already been sync'ed
							if($single_log_id_mode && $logged_exists_on_target) { continue; }
							
							// Don't sync in single log_id mode past current replication id for target 
							if($single_log_id_mode && ($single_log_id_mode_max_log_id > 0) && ($log_id > $single_log_id_mode_max_log_id)) { continue; }
							
						    if ($this->access_list && ($access_by_guid[$source_log_entry['guid']] !== '?') && !in_array((int)$access_by_guid[$source_log_entry['guid']], $this->access_list, true) && !$logged_exists_on_target) {
						        continue;	// skip rows for which we have no access
						    }
						    
						    // *Really* old logs don't always have subjects when a log entry subject is the logged record
						    // so synthesize the subjects list for these here
						    if(!is_array($source_log_entry['subjects'])) {
						    	$source_log_entry['subjects'] = [
						    		[
						    			'log_id' => $source_log_entry['log_id'],
						    			'guid' => $source_log_entry['guid'],
						    			'subject_table_num' => $source_log_entry['logged_table_num'],
						    			'subject_row_id' => $source_log_entry['logged_row_id']
						    		]	
						    	];
						    }
						    
							if (is_array($source_log_entry['subjects'])) {
							    // Loop through subjects of source (changed) row looking for items to replicate
							    // (Eg. a change to an object-entity relationship should cause both object and entity to be replicated if they otherwise meet replication requirements)
								foreach($source_log_entry['subjects'] as $source_log_subject) {
								   
								    $subject_exists_on_target = is_array($guid_already_exists[$source_log_subject['guid']]);
								    
								   	// Check access
								    $have_access_to_subject = true;
								    if ($this->access_list) {
								        if ($access_by_guid[$source_log_subject['guid']] !== '?') {
								            $have_access_to_subject = in_array((int)$access_by_guid[$source_log_subject['guid']], $this->access_list, true);
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
                                    } elseif(!$have_access_to_subject && !$subject_exists_on_target) {
                                    	// Skip log entry entirely
                                    	continue;
                                    } elseif($have_access_to_subject && !$subject_exists_on_target) {
                                        // keep in filtered log
                                        $this->filtered_log_entries[$log_id] = $source_log_entry;
        
										// Should insert on server...
										//if(($source_log_entry['changetype'] !== 'I') || in_array((int)$source_log_entry['logged_table_num'], [3,4], true) || $single_log_id_mode){
											// ... which means synthesizing log from current state if update
                                			$this->_findMissingGUID($source_log_subject['guid'], 0, $single_log_id_mode, $is_push_missing);
                                			                            			
											// try to push unresolved guids
                                			$this->_processUnresolvedGUIDs($single_log_id_mode, $is_push_missing);    
                                		//}
                                    }
                                }	// end subject loop							
							}
						}      // end source log entry loop
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
						$this->logDebug(_t("[1] No new log entries found for source %1 and target %2. Will try pulling new ones.",
							$source_key, $target_key), Zend_Log::INFO);
					}

					// Apply that log at the current target
					ksort($this->source_log_entries, SORT_NUMERIC);
					
					// Remove anything that has already been sent
					foreach($this->source_log_entries as $mlog_id => $entry) {						
						if($this->sent_log_ids[$mlog_id]) {
							$this->logDebug(_t("[%1] Removing log_id %2 because it has already been sent via the missing guid queue", $this->source_key, $mlog_id), Zend_Log::DEBUG);
							unset($this->source_log_entries[$mlog_id]);
						}
					}
					
					if(sizeof($this->source_log_entries) > 0) {
						$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
							->addGetParameter('system_guid', $source_system_guid)
							->addGetParameter('setIntrinsics', $this->set_intrinsics_json)
							->addGetParameter('push_missing', $is_push_missing)
							->setRequestBody($this->source_log_entries)
							->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
							->request();
							
						$this->logDebug(_t("[%1] Pushed %2 primary entries: %3", $this->source_key, sizeof($this->source_log_entries), print_r($this->source_log_entries, true)), Zend_Log::DEBUG);
						$response_data = $o_resp->getRawData();
					} else {
						$o_resp = null;
						$response_data = ['replicated_log_id' => $end_log_id];
						$this->logDebug(_t("[%1] Nothing to send (all filtered) so incrementing to last log_id (%2)",
								$source_key, $end_log_id), Zend_Log::DEBUG);
					}
					
					if (($o_resp && !$o_resp->isOk()) || !isset($response_data['replicated_log_id'])) {
						$this->log(_t("[%1] There were errors while processing sync for source %1 and target %2: %3", $source_key, $target_key, join(' ', $o_resp->getErrors())), Zend_Log::ERR);
						break;
					} else {
						foreach($this->source_log_entries as $mlog_id => $entry) {						
							// Mark log entry as sent, to ensure we don't send it again in this session
							// (Double sending of a log entry can happen with attributes in some cases where they
							//  are pulled as part of the primary record and then as a dependency)
							$this->sent_log_ids[$mlog_id] = true;
						}
						$replicated_log_id = $end_log_id + 1; //($this->last_log_id > 0) ? ($this->last_log_id + 1) : ((int) $response_data['replicated_log_id']) + 1;
						$this->log(_t("[%1] Chunk sync for source %1 and target %2 successful.", $source_key, $target_key), Zend_Log::DEBUG);
						$num_log_entries = sizeof($this->source_log_entries);
						$last_log_entry = array_pop($this->source_log_entries);
					   
					   	$this->log(_t("[%1] Pushed %2 log entries. Incrementing log index to %3 (%4).", $source_key, $num_log_entries, $replicated_log_id, date(DATE_RFC2822, $last_log_entry['log_datetime'])), Zend_Log::DEBUG);
						$this->log(_t("[%1] Last replicated log ID is: %2 (%3).", $source_key, $response_data['replicated_log_id'], date(DATE_RFC2822, $last_log_entry['log_datetime'])), Zend_Log::DEBUG);
																					
						// try to push unresolved guids
						$this->_processUnresolvedGUIDs($single_log_id_mode, $is_push_missing);  
					}

					if (isset($response_data['warnings']) && is_array($response_data['warnings']) && sizeof($response_data['warnings'])) {
						foreach ($response_data['warnings'] as $log_id => $warns) {
							$this->log(_t("[%1] There were warnings while processing sync for source %1, target %2, log id %3: %4.",
								$source_key, $target_key, $log_id, join(' ', $warns)), Zend_Log::WARN);
						}
					}
				}

				if($is_ok) {															
					// try to push unresolved guids
					//$this->_processUnresolvedGUIDs($single_log_id_mode);  
					
					$this->log(_t("[%1] Sync for source %1 and target %2 successful.", $source_key, $target_key), Zend_Log::INFO);

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
							$this->log(_t("[%1] There were errors while processing deduplication for at target %2: %3.", $source_key, $target_key, join(' ', $o_dedup_response->getErrors())), Zend_Log::ERR);
						} else {
							$this->log(_t("[%1] Dedup at target %2 successful.", $source_key, $target_key), Zend_Log::INFO);
							if(isset($dedup_response['report']) && is_array($dedup_response['report'])) {
								foreach($dedup_response['report'] as $t => $c) {
									$this->log(_t("[%1] De-duped %2 records for %3.", $source_key, $c, $t), Zend_Log::DEBUG);
								}
							}
						}
					}
				} else {
					$this->log(_t("[%1] Sync for source %1 and target %2 finished, but there were errors.", $source_key, $target_key), Zend_Log::ERR);
				}
			}
			$this->log(_t("[%1] Sync for source %1 and target %2 took %3.", $source_key, $target_key, caFormatInterval(time() - $start_time)), Zend_Log::DEBUG);
		}
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 * Find dependencies for $missing_guid that are not already present on the target
	 *
	 * @param string $missing_guid
	 *
	 * @return 
	 */
	public function _findMissingGUID(string $missing_guid, int $level=0, ?bool $single_log_id_mode=false, ?bool $is_push_missing=false) : ?bool {		
		if ($this->source_log_entries_for_missing_guids_seen_guids[$missing_guid]) { 
			$this->logDebug(_t("[%1] Skipped %2 because we've seen it already.", $this->source_key, $missing_guid), Zend_Log::DEBUG);
			return $this->missing_guid_created[$missing_guid] ?? false; 
		} 
		
		$this->source_log_entries_for_missing_guids_seen_guids[$missing_guid] = true;
		
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
			->addGetParameter('push_missing', $is_push_missing)
			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
			->request()->getRawData();
			
		if (is_array($log_for_missing_guid)) {
			// Check access settings for dependent rows; we only want to replicate rows that
			// meet the configured access requirements (Eg. public rows only)
			$log_for_missing_guid_list = array_unique(caExtractArrayValuesFromArrayOfArrays($log_for_missing_guid, 'guid'));
			$access_for_dependent = $this->_hasAccess($this->source, $log_for_missing_guid_list);    
			
			$o_missing_guid_already_exists = $this->target->setRequestMethod('POST')->setEndpoint('hasGUID')
				->setRequestBody($log_for_missing_guid_list)
				->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
				->request();
			$missing_guid_already_exists = $o_missing_guid_already_exists->getRawData();
			
			$filtered_log_for_missing_guid = [];  
			ksort($log_for_missing_guid, SORT_NUMERIC);
		   
			$this->logDebug(_t("[%1] Missing log for %2 is %3", $this->source_key, $missing_guid, print_R($log_for_missing_guid, true)), Zend_Log::DEBUG);
			
			// was missing guid eventually deleted?
			foreach($log_for_missing_guid as $missing_entry) {
				if(($missing_entry['changetype'] === 'D') && ($missing_entry['guid'] === $missing_guid)) {
					$this->logDebug(_t("[%1] Skipped %2 because it was eventually deleted.", $this->source_key, $missing_guid), Zend_Log::DEBUG);
					return true;
				}
			}
			
			$skip_guids = [];
			$unresolved_dependent_guids = [];
			foreach($log_for_missing_guid as $missing_entry) {
				if (!$single_log_id_mode && ($missing_entry['log_id'] > 1) && ($missing_entry['log_id'] > $this->end_log_id)) {
					$this->logDebug(_t("[%1] Skipped missing log_id %2 because it is in the future; current log_id is %3", $this->source_key, $missing_entry['log_id'], $this->last_log_id), Zend_Log::WARN);    
					continue;
				}
				if (($missing_entry['log_id'] != 1) && $this->sent_log_ids[$missing_entry['log_id']]) { 
					$this->logDebug(_t("[%1] Skipped missing log_id %2 because it has already been sent.", $this->source_key, $missing_entry['log_id']), Zend_Log::WARN);    
					continue; 
				}	
				
				if ($this->access_list && ($access_for_dependent[$missing_entry['guid']] !== '?') && !in_array((int)$access_for_dependent[$missing_entry['guid']], $this->access_list, true)) {
					continue; // Skip rows for which we have no access;
				}
				
				if(isset($skip_guids[$missing_entry['guid']])) {
					$this->logDebug(_t("[%1] Skip log_id %2 for %3 in the missing log because the guid was previously skipped.", $this->source_key, $missing_entry['log_id'], $missing_entry['guid']),Zend_Log::DEBUG);
					continue;
				}
				
				// Add guids for dependencies referenced by this log entry
				if(is_array($missing_entry['snapshot'])) {
					// check parent
					if($parent_guid = ($missing_entry['snapshot']['parent_id_guid'] ?? null)) {
						$parent_access = $this->_hasAccess($this->source, [$parent_guid]);
						if(!in_array((int)$parent_access[$parent_guid], $this->access_list, true)) {
							$missing_entry['snapshot']['parent_id_guid'] = null;
							$missing_entry['snapshot']['parent_id'] = null;	
							$this->logDebug(_t("[%1] Removed parent_id_guid %2 in log_id %3 for %4 because it is not accessible.", $this->source_key, $parent_guid, $missing_entry['log_id'], $missing_entry['guid']),Zend_Log::DEBUG);
						}
					}
					
					if($user_guid = ($missing_entry['snapshot']['user_id_guid'] ?? null)) {
						$user_access = $this->_hasAccess($this->source, [$user_guid]);
						if(!in_array((int)$user_access[$user_guid], $this->access_list, true)) {
							$missing_entry['snapshot']['user_id_guid'] = null;
							$missing_entry['snapshot']['user_id'] = null;	
							$this->logDebug(_t("[%1] Removed user_id_guid %2 in log_id %3 for %4 because it is not accessible.", $this->source_key, $user_guid, $missing_entry['log_id'], $missing_entry['guid']),Zend_Log::DEBUG);
						}
					}
					
					$missing_log_entry_pk = Datamodel::primaryKey($missing_entry['logged_table_num']);
					
					$dependent_guids = array_values(array_filter($missing_entry['snapshot'], function($v, $k) use ($missing_entry, $missing_guid, $missing_log_entry_pk) { 
						if($v == $missing_guid) { return false; }
						if($v == $missing_entry['guid']) { return false; }
						
						if(isset($missing_entry['snapshot']['attribute_guid']) && ($missing_entry['logged_table_num'] == 3)) { return false; }	// don't check deps for attribute values on attributes - values always follow attributes
						
						if(preg_match("!([A-Za-z0-9_]+)_guid$!", $k, $matches) && ($matches[1] !== $missing_log_entry_pk) && ($matches[1].'_id' !== $missing_log_entry_pk) && preg_match("!^[a-z0-9]+\-[a-z0-9]+\-[a-z0-9]+\-[a-z0-9]+\-[a-z0-9]+$!i", $v)) {
							if(
								is_array(Datamodel::getFieldInfo($missing_entry['logged_table_num'], $matches[1]))
								||
								is_array(Datamodel::getFieldInfo($missing_entry['logged_table_num'], $matches[1].'_id'))
							) { 
								return true; 
							}
						}
						return false;
					}, ARRAY_FILTER_USE_BOTH));
					
					$o_dep_guid_already_exists = $this->target->setRequestMethod('POST')->setEndpoint('hasGUID')
						->setRequestBody($dependent_guids)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request();
					$dep_guid_already_exists = $o_dep_guid_already_exists->getRawData();
					
					foreach($dependent_guids as $dep_guid) {
						if($dep_guid === $missing_entry['guid']) { continue; }
						if(isset($missing_entry['snapshot']['attribute_guid']) && ($missing_entry['snapshot']['attribute_guid'] === $dep_guid)) { continue; }
						if(isset($missing_entry['snapshot']['value_guid']) && ($missing_entry['snapshot']['value_guid'] === $dep_guid)) { continue; }
						if(isset($missing_entry['snapshot']['parent_id_guid']) && ($missing_entry['snapshot']['parent_id_guid'] === $dep_guid)) { continue; }
						if(isset($missing_entry['snapshot']['lot_id_guid']) && ($missing_entry['snapshot']['lot_id_guid'] === $dep_guid)) { continue; }
						
						if(!is_array($dep_guid_already_exists[$dep_guid])) { 
							$this->logDebug(_t("[%1] Skipped log entry %2 because dependent guid %3 for %4 does not yet exist on target", $this->source_key, $missing_entry['log_id'], $dep_guid, $missing_entry['guid']),Zend_Log::DEBUG);
							$this->unresolved_guids[$dep_guid] = 1;
							continue(2);
						}
					}
					
					$dep_access = $this->_hasAccess($this->source, $new_dependent_guids);
					foreach($dep_access as $dep_guid => $dep_access_value) {
						if($dep_guid === $missing_entry['guid']) { continue; }
						if(isset($missing_entry['snapshot']['parent_id_guid']) && ($missing_entry['snapshot']['parent_id_guid'] === $dep_guid)) { continue; }
						if(isset($missing_entry['snapshot']['lot_id_guid']) && ($missing_entry['snapshot']['lot_id_guid'] === $dep_guid)) { continue; }
						if($this->access_list && ($dep_access_value !== '?') && !in_array((int)$dep_access_value, $this->access_list, true)){
							// dependency in this log entry is not accessible so skip it
							$this->logDebug(_t("[%1] Skipped log entry %2 because dependent guid %3 for %4 is not accessible.", $this->source_key, $missing_entry['log_id'], $dep_guid, $missing_entry['guid']),Zend_Log::DEBUG);
							$skip_guids[$dep_guid] = true;
							continue(2);
						}
					}
					
					$dependent_guids = array_unique(array_filter($dependent_guids, 'strlen'));
					//$this->logDebug(_t("[%1] Found %2 dependent guids for %3: %4.", $this->source_key, sizeof($dependent_guids), $missing_entry['guid'], print_R($dependent_guids, true)),Zend_Log::DEBUG);
				
				
					// Check for presence and access of dependencies on target
					// We will only replicate rows meeting access requirements and not already on the target
					//
					if(sizeof($dependent_guids) > 0) {
						$this->logDebug(_t("[%1] Processing %2 dependent guids for %3: %4.", $this->source_key, sizeof($dependent_guids), $missing_entry['guid'], print_R($dependent_guids, true)),Zend_Log::DEBUG);
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
						//$this->logDebug(_t("[%1] Processing %2 *existence filtered* dependent guids for %3: %4.", $this->source_key, sizeof($dependent_guids), $missing_entry['guid'], print_R($dependent_guids, true)),Zend_Log::DEBUG);
				
						if (is_array($this->access_list)) {
							// Filter missing guid list using access criteria
							$access_for_missing = $this->_hasAccess($this->source, $dependent_guids);
					
							if (is_array($access_for_missing)) {
								$filter_on_access_settings = $this->access_list;
								$dependent_guids = array_unique(array_filter(array_keys(array_filter($access_for_missing, function($v) use ($filter_on_access_settings) { return (($v == '?') || (in_array((int)$v, $filter_on_access_settings, true))); })), 'strlen'));
							} else {
								$this->logDebug(_t("[%1] Failed to retrieve access values for missing GUID.", $this->source_key),Zend_Log::DEBUG);
							}
					
							//$this->logDebug(_t("[%1] Processing %2 *access filtered* dependent guids for %3: %4.", $this->source_key, sizeof($dependent_guids), $missing_entry['guid'], print_R($dependent_guids, true)),Zend_Log::DEBUG);
						}
						$this->logDebug(_t("[%1] Processing %2 *filtered* dependent guids for %3: %4.", $this->source_key, sizeof($dependent_guids), $missing_entry['guid'], print_R($dependent_guids, true)),Zend_Log::DEBUG);
				
						
						if($level < 5) {	// TODO: do we need this level limit?
							$new_dependent_guids = [];
							foreach($dependent_guids as $dep_guid) {
								$this->logDebug(_t("[%1] Run _findMissingGUID for dependency %2 of %3", $this->source_key, $dep_guid, $missing_guid),Zend_Log::DEBUG);
								if($skip_guids[$dep_guid] || !$this->_findMissingGUID($dep_guid, $level+1, $single_log_id_mode, $is_push_missing)) {
									$new_dependent_guids[] = $dep_guid;
									$unresolved_dependent_guids[$dep_guid]++;
									
									if(!$skip_guids[$dep_guid]) {
										$skip_guids[$dep_guid] = true;
										$this->logDebug(_t("[%1] _findMissingGUID failed dependency %2 of %3", $this->source_key, $dep_guid, $missing_guid),Zend_Log::DEBUG);
									}
								}
							}	
						}
			
						if(sizeof($new_dependent_guids) > 0) {
							$this->logDebug(_t("[%1] %2 dependent guids remain after processing: %3", $this->source_key, sizeof($new_dependent_guids), print_r($this->_dumpDependencies($this->source, $new_dependent_guids), true)),Zend_Log::WARN);
						}
					} 
				}
				
				$filtered_log_for_missing_guid[$missing_entry['log_id']] = $missing_entry;
			}
			
			if (sizeof($filtered_log_for_missing_guid) == 0) { 
				$this->logDebug(_t("[%1] Empty missing log for %2 at level %3.", $this->source_key, $missing_guid, $level),Zend_Log::DEBUG);
				unset($this->source_log_entries_for_missing_guids_seen_guids[$missing_guid]);
				return false; 
			}
			
			// @TODO: bad idea?
			if (sizeof($unresolved_dependent_guids) > 0) { 
				$this->logDebug(_t("[%1] %2 unresolved dependent guids for %3: %4; skipping guid.", $this->source_key, sizeof($unresolved_dependent_guids), $missing_guid, print_r($this->_dumpDependencies($this->source, array_keys($unresolved_dependent_guids)), true)),Zend_Log::DEBUG);
				unset($this->source_log_entries_for_missing_guids_seen_guids[$missing_guid]);
				return null; 
			}
			
			ksort($filtered_log_for_missing_guid, SORT_NUMERIC);   
			                                     
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
						$this->logDebug(_t("[%1] Skipped log_id %2 because it has already been sent.", $this->source_key, $mlog_id), Zend_Log::WARN);
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
								->addGetParameter('push_missing', $is_push_missing)
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request()->getRawData();
							if (is_array($attr_log)) {
								$acc = [];
								foreach($attr_log as $l => $x) {
									if (!$seen_list[$x['log_id']]) {
										$seen_list[$x['log_id']] = $x['guid'];
										$entries[$x['log_id']] = $acc[$x['log_id']] = $x;
										
										// Mark as seen so we don't process the same thing twice
										$this->source_log_entries_for_missing_guids_seen_guids[$x['guid']] = true;
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
					$this->logDebug(_t("[%1] Immediately pushing missing log entries starting with %2 for %3: %4", $this->source_key, $first_missing_log_id, $missing_guid, print_r($entries, true)), Zend_Log::DEBUG);
		
					$o_backlog_resp = $this->target->setRequestMethod('POST')->setEndpoint('applylog')
						->addGetParameter('system_guid', $this->source_guid)
						->addGetParameter('setIntrinsics', $this->set_intrinsics_json)
						->addGetParameter('push_missing', $is_push_missing)
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
							unset($this->source_log_entries[$mlog_id]);
							unset($this->filtered_log_entries[$mlog_id]);
						}
					}
				}
			}
		} else {
			$this->logDebug(_t("[%1] No log for %2.", $this->source_key, $missing_guid), Zend_Log::WARN);
		}
		
		$this->missing_guid_created[$missing_guid] = true;
		return true;
	}	
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _processUnresolvedGUIDs(?bool $single_log_id_mode=false, ?bool $is_push_missing=false) {
		// try to push unresolved guids
		$pushed = $skipped = $failed = 0;
		if(is_array($this->unresolved_guids) && sizeof($this->unresolved_guids)) {
			$this->log(_t("[%1 Trying to push %2 unresolved guids", $this->source_key, sizeof($this->unresolved_guids)), Zend_Log::DEBUG);
			$o_guid_already_exists = $this->target->setRequestMethod('POST')->setEndpoint('hasGUID')
				->setRequestBody(array_keys($this->unresolved_guids))
				->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
				->request();
			$guid_already_exists = $o_guid_already_exists->getRawData();

			foreach($guid_already_exists as $guid => $guid_info) {
				if(!is_array($guid_info)) {
					if(is_null($this->_findMissingGUID($guid, 0, $single_log_id_mode, $is_push_missing))) {
						$this->log(_t("[%1] Could not push unresolved guid %2", $this->source_key, $guid), Zend_Log::DEBUG);
						$failed++;
					} else {
						$this->log(_t("[%1] Pushed unresolved guid %2", $this->source_key, $guid), Zend_Log::DEBUG);
						unset($this->unresolved_guids[$guid]);
						$pushed++;
					}
				} else {
					unset($this->unresolved_guids[$guid]);
					$skipped++;
				}
			}
		}
		if($pushed || $skipped || $failed) {
			$this->log(_t("[%1] Pushed %2; skipped %3; %4 failed unresolved guids; list is now %5", $this->source_key, $pushed, $skipped, $failed, print_R($this->unresolved_guids, true)), Zend_Log::DEBUG);
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _hasAccess($o_source, $guids) {
		if(sizeof($this->guid_access_cache) > 100000) { 
			$this->guid_access_cache = array_slice($this->guid_access_cache, 50000);
		}
		
 		// Check if guids are cached
		$cached_res = [];
		$filtered_guids = [];
		foreach($guids as $guid) {
			if(isset($this->guid_access_cache[$guid])) {
				$cached_res[$guid] = $this->guid_access_cache[$guid];
				continue;
			}
			$filtered_guids[] = $guid;
		}
		$filtered_guids = array_unique($filtered_guids);
		
		$r = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
			->addGetParameter('access', join(';', $this->access_list))
			->setRequestBody($guids)
			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
			->request();
			
		$res = $r->getRawData();
		if(is_array($res)) {
			foreach($res as $guid => $access) {
				$this->guid_access_cache[$guid] = $access;
 			}
		
			$res = array_merge($res, $cached_res);
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
		if(!($target = caGetOption('target', $options, null))) { 
			return null;
		}
		
		$o_source = array_shift($this->getSourcesAsServiceClients(['source' => $source]));
		$o_target = array_shift($this->getTargetsAsServiceClients(['target' => $targets[0]]));
		
		$o_result = $o_source->setEndpoint('getsysguid')->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)->request();
			if(!$o_result || !($res = $o_result->getRawData()) || !(strlen($source_system_guid = $res['system_guid']))) {
				$this->log(
					_t("[%1] Could not get system GUID for one of the configured replication sources: {$source_key}. Skipping source.", $source_key),
					\Zend_Log::ERR
				);
				return;
			}
			print "guid=$guid\n";
		$log = $o_source->setEndpoint('getlog')
			->clearGetParameters()
			->addGetParameter('forGUID', $guid)
			->addGetParameter('push_missing', 1)
			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
			->request()->getRawData();
		
		$acc = [];
		
		$elements = ['dimensions'];
		foreach($elements as $e) {
			$el = ca_metadata_elements::getElementsForSet($e);
			$elements = array_merge($elements, array_map(function($v) { return $v['element_code']; }, $el));
		}
		print_R($log);
		$elements = array_unique($elements);
		foreach($log as $log_id => $log_entry) {
			if(($log_entry['logged_table_num'] == 3) && ($log_entry['changetype'] == 'I')) {
				$log_entry['changetype'] = 'U';
			}
			
			$is_element = (($log_entry['snapshot']['element_code'] ?? null) && in_array($log_entry['snapshot']['element_code'], $elements, true)); 
			var_dump($is_element);
			if(!$is_element && in_array($log_entry['changetype'], ['U'])) { continue; }
			
			if(!isset($acc[$log_entry['guid']])) { $acc[$log_entry['guid']] = []; }
			$acc[$log_entry['guid']] = array_merge($acc[$log_entry['guid']], $log_entry);
		}
		print_R($acc);die;
		foreach($acc as $guid => $log_entry) {
			print_R($log_entry);
			$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
				->addGetParameter('system_guid', $source_system_guid)
				->addGetParameter('push_missing', 1)
				->setRequestBody([$log_entry])
				->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
				->request();
			
			if ($resp) {
				$res = $resp->getRawData();
				print_R($res);
			}
		}
		
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
				$this->log(_t("[%1] Replicating from %2.", $source, $r['log_id']), Zend_Log::INFO);
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
			if($guid = caGetOption('guid', $options, null)) {
				$guid_filter_list = array_map('trim', preg_split('![;,]+!', $guid));
				$guids = array_intersect($guids, $guid_filter_list);
			}
			$this->log(_t("[%1] There are %2 public records for source.", $source, sizeof($guids)), Zend_Log::INFO);
			
			$targets = $this->getTargetsAsServiceClients();
			
			foreach($targets as $o_target) {
				$i = 0;
				$guids_to_sync = [];
				
				do {
					$chk_guids = array_slice($guids, $i, 500);
				
					if(!is_array($chk_guids) || !sizeof($chk_guids)) { break; }
				
					$o_guid_already_exists = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
						->setRequestBody($chk_guids)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request();
					$guid_already_exists = $o_guid_already_exists->getRawData();
					
					foreach($guid_already_exists as $guid => $guid_info) {
						if(is_array($guid_info)) { continue; }
						$guids_to_sync[] = $guid;
					}
					
					$i += 500;
				} while($i < sizeof($guids));
				
				$guids_to_sync = array_unique($guids_to_sync);
			}
			$this->log(_t("[%1] Found %2 unsynced public records.", $source, sizeof($guids_to_sync)), Zend_Log::INFO);
			self::syncGUIDs($source, $guids_to_sync);
			return true;
		}
		throw new ApplicationException(_t('Invalid source'));
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function copyDataForPublic(?string $source,  ?array $options=null) {
		if(!is_null($source)) {
			$sources = $this->getSourcesAsServiceClients(['source' => $source]);
		} else {
			$sources = $this->getSourcesAsServiceClients();
		}
		if($sources) {
			foreach($sources as $source_name => $source) {
				print "Sync $source_name\n";
				$resp = $source->setRequestMethod('GET')->setEndpoint('getPublicGUIDs')
					->addGetParameter('table', caGetOption('table', $options, 'ca_objects'))
					->addGetParameter('access', '1')
					->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
					->request();
				
				$guids = $resp->getRawData();
				$this->log(_t("[%1] There are %2 public records for source.", $source_name, sizeof($guids)), Zend_Log::INFO);
				
				$targets = $this->getTargetsAsServiceClients();
				
				foreach($targets as $o_target) {
					$i = 0;
					$guids_to_sync = [];
					
					do {
						$chk_guids = array_slice($guids, $i, 500);
					
						if(!is_array($chk_guids) || !sizeof($chk_guids)) { break; }
					
						$o_values = $source->setRequestMethod('POST')->setEndpoint('getcurrentvalue')
							->setRequestBody($chk_guids)
							->addGetParameter('bundle', 'ca_objects.culture')
							->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
							->request();
						$values = $o_values->getRawData();
						
						$values = array_filter($values, 'strlen');
						
						$acc = [];
						foreach($values as $guid => $v) {
							$vlist =  explode(";", $v);
							
							foreach($vlist as $vx) {
								$vx = strtolower($vx);
								$vx = preg_replace('![^A-Za-z]+!', '', $vx);
								
								$c = null;
								switch($vx) {
									case 'acadian':
										$c = 'acadian';
										break;
									case 'gaelic':
										$c = 'gaelic';
										break;
									case 'african':
									case 'africannovascotia':
									case 'africannovascotian':
										$c = 'african_nova_scotian';
										break;
									case 'mikmaq':
										$c = 'mi_kmaq';
										break;
									default:
										if(preg_match('!africa!', $vx)) {
											$c = 'african_nova_scotian';
										} elseif(preg_match('!maq!', $vx)) {
											$c = 'mi_kmaq';
										}
										break;
								}
							}
							if($c) {
								$acc[$guid][] = $c;
							}
						}
						if(sizeof($acc) > 0) {
							$o_apply = $o_target->setRequestMethod('POST')->setEndpoint('setcurrentvalue')
								->setRequestBody($acc)
								->addGetParameter('bundle', 'ca_objects.culture')
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request();
							$resp = $o_apply->getRawData();
						}
						
						$i += 500;
					} while($i < sizeof($guids));	
				}
			}
			return true;
		}
		throw new ApplicationException(_t('Invalid source'));
	}
	# --------------------------------------------------------------------------------------------------------------
}
