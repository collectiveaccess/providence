<?php
/** ---------------------------------------------------------------------
 * app/lib/Sync/Replicator.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2022 Whirl-i-Gig
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
	static $s_logger = null;
	
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
	
	protected $max_retries = 5;
	protected $retry_delay = 1000;
	
	# --------------------------------------------------------------------------------------------------------------
	public function __construct() {
		$this->opo_replication_conf = Configuration::load(__CA_CONF_DIR__.'/replication.conf');
		Replicator::$s_logger = new Logger('replication');
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	protected function getSourcesAsServiceClients() {
		$va_sources = $this->opo_replication_conf->get('sources');
		if(!is_array($va_sources)) { throw new Exception('No sources configured'); }

		return $this->getConfigAsServiceClients($va_sources);
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	protected function getTargetsAsServiceClients() {
		$va_targets = $this->opo_replication_conf->get('targets');
		if(!is_array($va_targets)) { throw new Exception('No sources configured'); }

		return $this->getConfigAsServiceClients($va_targets);
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
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
	# --------------------------------------------------------------------------------------------------------------
	/**
	 * Log message with given log level
	 * @param string $ps_msg
	 * @param int $pn_level log level as Zend_Log level integer:
	 *        one of Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::WARN, Zend_Log::ERR
	 */
	public function log($ps_msg, $pn_level) {
		Replicator::$s_logger->log($ps_msg, $pn_level);
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function replicate() {
		foreach($this->getSourcesAsServiceClients() as $vs_source_key => $o_source) {
			/** @var CAS\ReplicationService $o_source */

			// Get GUID for data source
			$o_result = $o_source->setEndpoint('getsysguid')->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)->request();
			if(!$o_result || !($res = $o_result->getRawData()) || !(strlen($source_system_guid = $res['system_guid']))) {
				$this->log(
					"Could not get system GUID for one of the configured replication sources: {$vs_source_key}. Skipping source.",
					\Zend_Log::ERR
				);
				continue;
			}
			
			$this->source = $o_source;
			$this->source_key = $vs_source_key;
			$this->source_guid = $source_system_guid;

			foreach($this->getTargetsAsServiceClients() as $vs_target_key => $o_target) {
				/** @var CAS\ReplicationService $o_target */
				
				$this->target = $o_target;
				$this->target_key = $vs_target_key;

				$vs_push_media_to = null;
				if($this->opo_replication_conf->get('sources')[$vs_source_key]['push_media']) {
					$vs_push_media_to = $vs_target_key;
				}

				// get latest log id for this source at current target
				$o_result = $o_target->setEndpoint('getlastreplicatedlogid')
					->addGetParameter('system_guid', $source_system_guid)
					->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
					->request();
				;
				if (!$o_result || !is_array($res = $o_result->getRawData()) || isset($res['errors'])) {
				    $this->log(_t("There were errors getting last replicated log id for source %1 and target %2: %3.", $vs_source_key, $vs_target_key, join('; ', $res['errors'])), Zend_Log::ERR);
				    continue;
				}
				
				$replicated_log_id = (int)$res['replicated_log_id'];

				if($replicated_log_id > 0) {
					$replicated_log_id = ((int) $replicated_log_id) + 1;
				} else {
					$this->log(_t("Couldn't get last replicated log id for source %1 and target %2. Starting at the beginning.",
						$vs_source_key, $vs_target_key), Zend_Log::WARN);
					$replicated_log_id = 1;
				}

				$this->log(_t("Starting replication for source %1 and target %2, log id is %3.",
					$vs_source_key, $vs_target_key, $replicated_log_id), Zend_Log::INFO);

				// it's possible to configure a starting point in the replication config
				if($ps_min_log_timestamp = $this->opo_replication_conf->get('sources')[$vs_source_key]['from_log_timestamp']) {
					if(!is_numeric($ps_min_log_timestamp)) {
						$o_tep = new TimeExpressionParser($ps_min_log_timestamp);
						$ps_min_log_timestamp = $o_tep->getUnixTimestamps()['start'];
					}

					// get latest log id for this source at current target
					$o_result = $o_target->setEndpoint('getlogidfortimestamp')
						->addGetParameter('timestamp', $ps_min_log_timestamp)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request();
					$pn_min_log_id = $o_result->getRawData()['log_id'];
				} else {
					$pn_min_log_id = (int) $this->opo_replication_conf->get('sources')[$vs_source_key]['from_log_id'];
				}
				if($pn_min_log_id > $replicated_log_id) { 
					$replicated_log_id = $pn_min_log_id; 
					$this->log(_t("[%1] Set log id to minimum (%2).", $vs_source_key, $replicated_log_id), Zend_Log::INFO);
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
				} else {
					$pa_filter_on_access_settings = array_map('intval', $pa_filter_on_access_settings);
				}
				
				$this->get_log_service_params = [
					'skipIfExpression' => $vs_skip_if_expression,
					'ignoreTables' => $vs_ignore_tables,
					'onlyTables' => $vs_only_tables,
					'includeMetadata' => $vs_include_metadata,
					'excludeMetadata' => $vs_exclude_metadata,
					'pushMediaTo' => $vs_push_media_to
				];
				
				
				$pn_start_replicated_id = $replicated_log_id;
				$pb_ok = true;
				
				$missing_guids = []; // List of guids missing on target that we'll need to replicate to synthesize subject
				
                $source_log_entries_for_missing_guids = [];
                $source_log_entries_for_missing_guids_seen_guids = [];
                
                $deferred_guids = [];
                
                // Dictionary with log_ids sent from this source in this session
                $this->sent_log_ids = [];
                
                if(($chunk_size = (int)$this->opo_replication_conf->get('chunk_size')) <= 0) { $chunk_size = 100; }
                
				while(true) { // use chunks of 10 entries until something happens (success/err)
				    $this->last_log_id = null;
				    if (sizeof($this->sent_log_ids) > 1000000) {
				    	$this->log(_t("Reset sent log list because it was over 1000000 entries. Memory usage was %1", caGetMemoryUsage()), Zend_Log::DEBUG);
				    	$this->sent_log_ids = [];
				    }
				    $this->log(_t("Memory usage: %1", caGetMemoryUsage()), Zend_Log::DEBUG);
				
					// get change log from source, starting with the log id we got above
					$va_source_log_entries = $o_source->setEndpoint('getlog')->clearGetParameters()
						->addGetParameter('from', $replicated_log_id)
						->addGetParameter('skipIfExpression', $vs_skip_if_expression)
						->addGetParameter('limit', $chunk_size)
						->addGetParameter('ignoreTables', $vs_ignore_tables)
						->addGetParameter('onlyTables', $vs_only_tables)
						->addGetParameter('includeMetadata', $vs_include_metadata)
						->addGetParameter('excludeMetadata', $vs_exclude_metadata)
						->addGetParameter('pushMediaTo', $vs_push_media_to)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request()->getRawData();

					if(!is_array($va_source_log_entries)) { break; }
                    $log_ids = array_keys($va_source_log_entries);
                    $start_log_id = array_shift($log_ids);
                    $end_log_id = array_pop($log_ids);
                    if(!$end_log_id) { $end_log_id = $start_log_id; }
                    
                    $this->log(_t("[%1] Found %2 source log entries starting at %3 [%4 - %5].", $vs_source_key, sizeof($va_source_log_entries), $replicated_log_id, $start_log_id, $end_log_id), Zend_Log::DEBUG);
                    $va_filtered_log_entries = null;
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
											->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
											->request();
						$va_guid_already_exists = $o_guid_already_exists->getRawData();
						
						
						$vs_access = is_array($pa_filter_on_access_settings) ? join(";", $pa_filter_on_access_settings) : "";
						$o_access_by_guid = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
						                    ->addGetParameter('access', $vs_access)
											->setRequestBody(array_unique(array_merge(caExtractArrayValuesFromArrayOfArrays($va_source_log_entries, 'guid'), array_keys($va_subject_guids))))
											->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
											->request();
						$va_access_by_guid = $o_access_by_guid->getRawData();

                        // List of log entries to push
					    $va_filtered_log_entries = [];
					    
						$this->guids_to_skip = [];
						
						foreach($va_source_log_entries as $vn_log_id => $va_source_log_entry) {
						    $this->last_log_id = $vn_log_id;
						    if ($this->sent_log_ids[$vn_log_id]) { continue; }	// Don't send a source entry more than once (should never happen)
						    
							$vb_logged_exists_on_target = is_array($va_guid_already_exists[$va_source_log_entry['guid']]);
						    if ($pa_filter_on_access_settings && ($va_access_by_guid[$va_source_log_entry['guid']] !== '?') && !in_array((int)$va_access_by_guid[$va_source_log_entry['guid']], $pa_filter_on_access_settings, true) && !$vb_logged_exists_on_target) {
						        // skip rows for which we have no access
						        continue;
						    }
						    
						    if (!$vb_logged_exists_on_target) {
						        $missing_guids[] = $va_source_log_entry['guid']; // add changed "source" row to replication list
						    }
						    
							if (is_array($va_source_log_entry['subjects'])) {
							    // Loop through subjects of source (changed) row looking for items to replicate
							    // (Eg. a change to an object-entity relationship should cause both object and entity to be replicated if they otherwise meet replication requirements)
								foreach($va_source_log_entry['subjects'] as $va_source_log_subject) {
								   
								    $vb_subject_exists_on_target = is_array($va_guid_already_exists[$va_source_log_subject['guid']]);
								    $vb_subject_is_relationship = Datamodel::isRelationship($va_source_log_subject['subject_table_num']);
								   
								    $vb_have_access_to_subject = true;
								    if ($pa_filter_on_access_settings) {
								        if ($va_access_by_guid[$va_source_log_subject['guid']] !== '?') {
								            $vb_have_access_to_subject = in_array((int)$va_access_by_guid[$va_source_log_subject['guid']], $pa_filter_on_access_settings, true);
								        }
								    }
								    
                                    //
                                    // Primary records
                                    //
                                    if (!$vb_have_access_to_subject && $vb_subject_exists_on_target) {
                                        // Should delete from target as it's not public any longer
                                        $va_filtered_log_entries[$vn_log_id] = $va_source_log_entry;
                                    } elseif($vb_subject_exists_on_target) {
                                        // Should update on server...
                                        // ... which means pushing change
                                        $va_filtered_log_entries[$vn_log_id] = $va_source_log_entry;
                                    } elseif($vb_have_access_to_subject && !$vb_subject_exists_on_target) {
                                        // DON'T keep in filtered log
                                        
                                        // Should insert on server...
                                        // ... which means synthesizing log from current state
                                        
                                        $missing_guids[] = $va_source_log_subject['guid'];   // add subject to list of guids to replicate
                
                                        // find dependencies for rows to replicate that are not already present on the target
                                        $missing_guids = array_unique($missing_guids);
                                        while((sizeof($missing_guids) > 0)) { 
                                            $vs_missing_guid = array_shift($missing_guids);
                                            if ($source_log_entries_for_missing_guids_seen_guids[$vs_missing_guid]) { 
                                                $this->log(_t("[%1] Skipped %2 because we've seen it already.", $vs_source_key, $vs_missing_guid), Zend_Log::DEBUG);
                                                continue; 
                                            } 
                                            
                                            // Pull log for "missing" guid we need to replicate on target
                                            $this->log(_t("[%1] Fetching log for missing guid %2.", $vs_source_key, $vs_missing_guid), Zend_Log::DEBUG);
                                            $va_log_for_missing_guid = $o_source->setEndpoint('getlog')
                                                ->clearGetParameters()
                                                ->addGetParameter('forGUID', $vs_missing_guid)
                                                ->addGetParameter('skipIfExpression', $vs_skip_if_expression)
                                                ->addGetParameter('ignoreTables', $vs_ignore_tables)
                                                ->addGetParameter('onlyTables', $vs_only_tables)
                                                ->addGetParameter('includeMetadata', $vs_include_metadata)
                                                ->addGetParameter('excludeMetadata', $vs_exclude_metadata)
                                                ->addGetParameter('pushMediaTo', $vs_push_media_to)
                                                ->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
                                                ->request()->getRawData();
                                                
                                            if (is_array($va_log_for_missing_guid)) {
                                                $va_dependent_guids = [];
                        
                                                // Check access settings for dependent rows; we only want to replicate rows that
                                                // meet the configured access requirements (Eg. public rows only)
                                                $o_access_for_dependent = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
                                                                    ->addGetParameter('access', $vs_access)
                                                                    ->setRequestBody(array_unique(caExtractArrayValuesFromArrayOfArrays($va_log_for_missing_guid, 'guid')))
                                                                    ->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
                                                                    ->request();
                                                $va_access_for_dependent = $o_access_for_dependent->getRawData();
                                                
                                                $va_filtered_log_for_missing_guid = [];  
                                                $this->log(_t("[%1] Missing log for %2 is %3", $vs_source_key, $vs_missing_guid, print_R($va_log_for_missing_guid, true)), Zend_Log::DEBUG);
                                                foreach($va_log_for_missing_guid as $va_missing_entry) {
                                                	if ($this->sent_log_ids[$va_missing_entry['log_id']]) { 
                                                		$this->log(_t("[%1] Skipped missing log_id %2 becaue it has already been sent.", $vs_source_key, $va_missing_entry['log_id']), Zend_Log::WARN);
                                                                
                                                		continue; 
                                                	}	
                                                	
                                                    if ($pa_filter_on_access_settings && ($va_access_for_dependent[$va_missing_entry['guid']] !== '?') && !in_array((int)$va_access_for_dependent[$va_missing_entry['guid']], $pa_filter_on_access_settings, true)) {
                                                        // skip rows for which we have no access
                                                        $this->log(_t("[%1] Skip %2 because we have no access.", $vs_source_key, $va_missing_entry['guid']), Zend_Log::DEBUG);
                                                        continue;
                                                    }
                                                    
                                                    $va_filtered_log_for_missing_guid[$va_missing_entry['log_id']] = $va_missing_entry;
                            
                                                    // Add guids for dependencies referenced by this log entry
                                                    if(is_array($va_missing_entry['snapshot'])) {
                                                        $va_dependent_guids = array_unique(array_merge($va_dependent_guids, array_values(array_filter($va_missing_entry['snapshot'], function($v, $k) use ($va_missing_entry, $vs_missing_guid) { 
                                                            if ($v == $vs_missing_guid) { 
                                                                return false; 
                                                            }
                                                            if(preg_match("!([A-Za-z0-9_]+)_guid$!", $k, $matches)) {
                                                                if(
                                                                    is_array(Datamodel::getFieldInfo($va_missing_entry['logged_table_num'], $matches[1]))
                                                                    ||
                                                                    is_array(Datamodel::getFieldInfo($va_missing_entry['logged_table_num'], $matches[1].'_id'))
                                                                ) { 
                                                                    return true; 
                                                                }
                                                            }
                                                            return false;
                                                        }, ARRAY_FILTER_USE_BOTH))));
                                                    }
                                                }
                                                if (sizeof($va_filtered_log_for_missing_guid) == 0) { continue; }
                                                
                                                // Check for presence and access of dependencies on target
                                                // We will only replicate rows meeting access requirements and not already on the target
                                                //
                                                $va_dependent_guids = array_filter($va_dependent_guids, 'strlen');
                                                if(sizeof($va_dependent_guids) > 0) {
                                                	$tries = 0; $va_guids_exist_for_dependencies = null;
                                                	
                                                	while(($tries < 5) && (!is_array($va_guids_exist_for_dependencies)))  {
														$o_guids_exist_for_dependencies = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
																			->setRequestBody($va_dependent_guids)
																			->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
																			->request();
														$va_guids_exist_for_dependencies = $o_guids_exist_for_dependencies->getRawData();
														$tries++;
													}  
                                                    
                                                    $va_dependent_guids = array_keys(array_filter($va_guids_exist_for_dependencies, function($v) { return !is_array($v); }));
                                                   
                                                    $missing_guids = array_filter(array_unique(array_merge($va_dependent_guids, $missing_guids)), 'strlen');  // add dependent guid lists to "missing" list; this will force dependencies to be processed through this loop
                                          
                                                    if (is_array($pa_filter_on_access_settings)) {
                                                        // Filter missing guid list using access criteria
                                                        $o_access_for_missing = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
                                                                            ->addGetParameter('access', $vs_access)
                                                                            ->setRequestBody($missing_guids)
                                                                            ->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
                                                                            ->request();
                                                        $va_access_for_missing = $o_access_for_missing->getRawData();
                                                        
                                                        if (is_array($va_access_for_missing)) {
                                                        	$missing_guids = array_filter(array_keys(array_filter($va_access_for_missing, function($v) use ($pa_filter_on_access_settings) { return (($v == '?') || (in_array((int)$v, $pa_filter_on_access_settings, true))); })), 'strlen');
                                                        	$this->guids_to_skip = array_filter(array_unique(array_merge($this->guids_to_skip, array_keys(array_filter($va_access_for_missing, function($v) use ($pa_filter_on_access_settings) { return !in_array((int)$v, $pa_filter_on_access_settings, true); })))), 'strlen');
                                                    	} else {
                                                    		$this->log(_t("[%1] Failed to retrieve access values for missing GUID.", $vs_source_key),Zend_Log::DEBUG);
                                                    	}
                                                    }
                                                } 
                                             
												ksort($va_filtered_log_for_missing_guid, SORT_NUMERIC);   
												$this->log(_t("[%1] Found %2 entries for %3.", $vs_source_key, sizeof($va_filtered_log_for_missing_guid), $vs_missing_guid), Zend_Log::DEBUG);
                    
                                                if(sizeof($va_dependent_guids) == 0) {                                                    
                                                    // Missing guid has no outstanding dependencies so push it immediately
                                                    $this->log(_t("[%1] Immediately pushing %2 missing entries for %3.", $vs_source_key, sizeof($va_filtered_log_for_missing_guid), $vs_missing_guid), Zend_Log::DEBUG);
                                                    
                                                    $va_has_attr_guids = [];
                                                    while(sizeof($va_filtered_log_for_missing_guid) > 0) {
                                                        $va_entries = [];
                                                        $vn_first_missing_log_id = null;
                            
                                                        while(sizeof($va_filtered_log_for_missing_guid) > 0) {
                                                            $va_log_entry = array_shift($va_filtered_log_for_missing_guid);
                                                            
                                                            $vn_mlog_id = $va_log_entry['log_id'];
                                                            
                                                            // Don't send log entry for missing guid more than once 
                                                            // (can happen with attributes where they can be pulled as missing on the primary record 
                                                            //  and then as dependencies of the primary record)
															if($this->sent_log_ids[$vn_mlog_id]) { 
																$this->log(_t("[%1] Skipped log_id %2 becaue it has already been sent.", $vs_source_key, $vn_mlog_id), Zend_Log::WARN);
                                                                continue; 
															} 
															
                                                            if (!$vn_mlog_id) { 
                                                                $this->log(_t("[%1] Skipped entry because it lacks a log_id.", $vs_source_key), Zend_Log::WARN);
                                                                continue; 
                                                            }
                                                            
                                                            if ($vn_mlog_id > $this->last_log_id) { 
                                                                $this->log(_t("[%1] Skipped entry (%2) because it's in the future.", $vs_source_key, $vn_mlog_id),Zend_Log::DEBUG);
                                                                continue; 
                                                            }
                                                            
                                                            // is this an attribute value? If so then check for existence of related attribute 
                                                            // since CA didn't write log entries for this in some versions
                                                            if (($va_log_entry['logged_table_num'] == 3) && ($vs_attr_guid = caGetOption(['attribute_guid', 'attribute_id_guid'], $va_log_entry['snapshot'], null))) {
                                                                $o_chk_attr_existence = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
                                                                        ->setRequestBody([$vs_attr_guid])
                                                                        ->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
                                                                        ->request();
                                                                $va_chk_attr_existence = $o_chk_attr_existence->getRawData();
                                                                if ($va_chk_attr_existence[$vs_attr_guid] == '???') {
                                                                    // need to push attribute
                                                                    $va_attr_log = $o_source->setEndpoint('getlog')
                                                                        ->clearGetParameters()
                                                                        ->addGetParameter('forGUID', $vs_attr_guid)
                                                                        ->addGetParameter('skipIfExpression', $vs_skip_if_expression)
                                                                        ->addGetParameter('ignoreTables', $vs_ignore_tables)
                                                                        ->addGetParameter('onlyTables', $vs_only_tables)
                                                                        ->addGetParameter('includeMetadata', $vs_include_metadata)
                                                                        ->addGetParameter('excludeMetadata', $vs_exclude_metadata)
                                                                        ->addGetParameter('pushMediaTo', $vs_push_media_to)
                                                                        ->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
                                                                        ->request()->getRawData();
                                                                    if (is_array($va_attr_log)) {
                                                                        $acc = [];
                                                                        foreach($va_attr_log as $l => $x) {
                                                                            if (!$va_seen[$x['log_id']]) {
                                                                                $va_seen[$x['log_id']] = $x['guid'];
                                                                                $va_entries[$x['log_id']] = $acc[$x['log_id']] = $x;
                                                                                // mark as seen so we don't process the same thing twice
                                                                                $source_log_entries_for_missing_guids_seen_guids[$x['guid']] = true;
                                                                            }
                                                                        }
                                                                        $size = sizeof($acc);
                        												if ($size > 0) {
                                                                        	$this->log(_t("[%1] Adding %4 unpushed attribute log entries starting with %2 for %3 for immediate push.", $vs_source_key, $vn_first_missing_log_id, $vs_missing_guid, $size), Zend_Log::DEBUG);
                                                                   		}
                                                                    }
                                                                }
                                                            }
                                                            
                                                            if (!$vn_first_missing_log_id) { $vn_first_missing_log_id = $vn_mlog_id; }
                            
                                                            $va_entries[$vn_mlog_id] = $va_log_entry;
                                                            
                                                            // mark as seen so we don't process the same thing twice
                                                            $source_log_entries_for_missing_guids_seen_guids[$va_log_entry['guid']] = true;
                                                            if ((is_array($va_entries) && (sizeof($va_entries) >= 10)) || (is_array($va_source_log_entries_for_missing_guid) && (sizeof($va_source_log_entries_for_missing_guid) == 0))) { break; }
                                                        }
                                                        
                                                        ksort($va_entries, SORT_NUMERIC);
                                                        
                                                        $size = sizeof($va_entries);
                        								if ($size > 0) {
                                                       		$this->log(_t("[%1] Immediately pushing missing log entries starting with %2 for %3.", $vs_source_key, $vn_first_missing_log_id, $vs_missing_guid), Zend_Log::DEBUG);
                                                        }
                                                        
                                                        $o_backlog_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
                                                            ->addGetParameter('system_guid', $source_system_guid)
                                                            ->setRequestBody($va_entries)
                                                            ->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
                                                            ->request();
                                                        foreach($va_entries as $mlog_id => $entry) {                                                        	
															// Mark log entry as sent, to ensure we don't send it again in this session
															// (Double sending of a log entry can happen with attributes in some cases where they
															//  are pulled as part of the primary record and then as a dependency)
                                                        	$this->sent_log_ids[$mlog_id] = true;
                                                        }
                                                    }
                                                } else {
                                                    // Missing guid has dependencies 
                                                    //      Queue it for replication
                                                    $source_log_entries_for_missing_guids[$vs_missing_guid] = $va_filtered_log_for_missing_guid;
                                                    
                                                    // mark as seen so we don't process the same thing twice
                                                    $source_log_entries_for_missing_guids_seen_guids[$vs_missing_guid] = true;
                                                }
                                            } else {
                                                $this->log(_t("[%1] No log for %2.", $vs_source_key, $vs_guid), Zend_Log::WARN);
                                            }
                                            
                                        }   // end missing guid loop
                                    }
                                }	// end subject loop							
							}
						}      // end source log entry loop

                        if(sizeof($source_log_entries_for_missing_guids)) {
                            $source_log_entries_for_missing_guids = array_reverse($source_log_entries_for_missing_guids);
						
						    list($source_log_entries_for_missing_guids, $source_log_entries_for_missing_guids_seen_guids) = $this->_pushMissingGUIDs($source_log_entries_for_missing_guids, $source_log_entries_for_missing_guids_seen_guids);
                        }
					}
					
					if (!is_array($va_source_log_entries) || !sizeof($va_source_log_entries)) {
						$this->log(_t("No new log entries found for source %1 and target %2. Skipping this combination now.",
							$vs_source_key, $vs_target_key), Zend_Log::INFO);
						break;
					}
					
					if (is_array($va_filtered_log_entries)) {
					    if (sizeof($va_filtered_log_entries) == 0) { 
                            $replicated_log_id = $this->last_log_id + 1;
                            
                            $va_last_log_entry = array_pop($va_source_log_entries);
                            
					        $this->log(_t("[%1] Nothing to push. Incrementing log index to %2 (%3)", $vs_source_key, $replicated_log_id, date(DATE_RFC2822, $va_last_log_entry['log_datetime'])), Zend_Log::DEBUG);
					        
					        $o_resp = $o_target->setRequestMethod('POST')->setEndpoint('setLastLogID')
								->addGetParameter('system_guid', $source_system_guid)
								->addGetParameter('log_id', $this->last_log_id)
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request();
					        continue; 
					    }
					    $va_source_log_entries = $va_filtered_log_entries;
					}

					// get setIntrinsics -- fields that are set on the target side (e.g. to tag/mark
					// where records came from if multiple systems are being synced into one)
					$va_set_intrinsics_config = $this->opo_replication_conf->get('targets')[$vs_target_key]['setIntrinsics'];
					$va_set_intrinsics_default = is_array($va_set_intrinsics_config['__default__']) ? $va_set_intrinsics_config['__default__'] : array();
					$va_set_intrinsics_source = is_array($va_set_intrinsics_config[$source_system_guid]) ? $va_set_intrinsics_config[$source_system_guid] : array();
					$va_set_intrinsics = array_replace($va_set_intrinsics_default, $va_set_intrinsics_source);
					$vs_set_intrinsics = null;
					if (is_array($va_set_intrinsics) && sizeof($va_set_intrinsics)) {
						$vs_set_intrinsics = json_encode($va_set_intrinsics);
					}

					// apply that log at the current target
					ksort($va_source_log_entries, SORT_NUMERIC);
					
					// remove anything that has already been sent
					foreach($va_source_log_entries as $mlog_id => $entry) {						
						if($this->sent_log_ids[$mlog_id]) {
							 $this->log(_t("[%1] Removing log_id %2 because it has already been sent via the missing guid queue", $vs_source_key, $mlog_id), Zend_Log::DEBUG);
							$va_source_log_entries[$mlog_id]['SKIP'] = 1; 
						}
					}
					
					$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
						->addGetParameter('system_guid', $source_system_guid)
						->addGetParameter('setIntrinsics', $vs_set_intrinsics)
						->setRequestBody($va_source_log_entries)
						->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
						->request();
					foreach($va_source_log_entries as $mlog_id => $entry) {						
						// Mark log entry as sent, to ensure we don't send it again in this session
						// (Double sending of a log entry can happen with attributes in some cases where they
						//  are pulled as part of the primary record and then as a dependency)
						$this->sent_log_ids[$mlog_id] = true;
					}

                    $this->log(_t("[%1] Pushed %2 primary entries.", $vs_source_key, sizeof($va_source_log_entries)), Zend_Log::DEBUG);
					$va_response_data = $o_resp->getRawData();

					if (!$o_resp->isOk() || !isset($va_response_data['replicated_log_id'])) {
						$this->log(_t("There were errors while processing sync for source %1 and target %2: %3", $vs_source_key, $vs_target_key, join(' ', $o_resp->getErrors())), Zend_Log::ERR);
						break;
					} else {
						$replicated_log_id = ($this->last_log_id > 0) ? ($this->last_log_id + 1) : ((int) $va_response_data['replicated_log_id']) + 1;
						$this->log(_t("Chunk sync for source %1 and target %2 successful.", $vs_source_key, $vs_target_key), Zend_Log::DEBUG);
						$vn_num_log_entries = sizeof($va_source_log_entries);
						$va_last_log_entry = array_pop($va_source_log_entries);
					   
					   	$this->log(_t("[%1] Pushed %2 log entries. Incrementing log index to %3 (%4).", $vs_source_key, $vn_num_log_entries, $replicated_log_id, date(DATE_RFC2822, $va_last_log_entry['log_datetime'])), Zend_Log::DEBUG);
						$this->log(_t("[%1] Last replicated log ID is: %2 (%3).", $vs_source_key, $va_response_data['replicated_log_id'], date(DATE_RFC2822, $va_last_log_entry['log_datetime'])), Zend_Log::DEBUG);
					}

					if (isset($va_response_data['warnings']) && is_array($va_response_data['warnings']) && sizeof($va_response_data['warnings'])) {
						foreach ($va_response_data['warnings'] as $vn_log_id => $va_warns) {
							$this->log(_t("There were warnings while processing sync for source %1, target %2, log id %3: %4.",
								$vs_source_key, $vs_target_key, $vn_log_id, join(' ', $va_warns)), Zend_Log::WARN);
						}
					}
					
					if(sizeof($source_log_entries_for_missing_guids)) {	// try to run missing queue
						$this->log(_t("[%1] Running missing guid queue with %2 guids.", $vs_source_key, sizeof($source_log_entries_for_missing_guids)), Zend_Log::DEBUG);
						$source_log_entries_for_missing_guids = array_reverse($source_log_entries_for_missing_guids);
					
						list($source_log_entries_for_missing_guids, $source_log_entries_for_missing_guids_seen_guids) = $this->_pushMissingGUIDs($source_log_entries_for_missing_guids, $source_log_entries_for_missing_guids_seen_guids);
					}
				}

				if($pb_ok) {
					$this->log(_t("Sync for source %1 and target %2 successful.", $vs_source_key, $vs_target_key), Zend_Log::INFO);

					// run dedup if configured
					$va_dedup_after_replication = $this->opo_replication_conf->get('targets')[$vs_target_key]['deduplicateAfterReplication'];
					$vs_dedup_after_replication = null;
					if(is_array($va_dedup_after_replication) && sizeof($va_dedup_after_replication)) {
						$vs_dedup_after_replication = json_encode($va_dedup_after_replication);

						// apply that log at the current target
						$o_dedup_response = $o_target->setRequestMethod('POST')->setEndpoint('dedup')
							->addGetParameter('tables', $vs_dedup_after_replication)
							->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
							->request();

						$va_dedup_response = $o_dedup_response->getRawData();

						if (!$o_dedup_response->isOk()) {
							$this->log(_t("There were errors while processing deduplication for at target %1: %2.", $vs_target_key, join(' ', $o_dedup_response->getErrors())), Zend_Log::ERR);
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
					$this->log(_t("Sync for source %1 and target %2 finished, but there were errors.", $vs_source_key, $vs_target_key), Zend_Log::ERR);
				}
			}
		}
	}
	
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _analyzeDependencies($source_log_entries_for_missing_guids) {
		 //
		// Analyze missing guids for dependencies
		//
		$va_dependency_list = [];
		foreach($source_log_entries_for_missing_guids as $vs_missing_guid => $va_source_log_entries_for_missing_guid) {
			if(!is_array($va_source_log_entries_for_missing_guid)) { continue; }
			
			foreach($va_source_log_entries_for_missing_guid as $entry) {
				if(is_array($entry['subjects'])) {
					foreach($entry['subjects'] as $dep_subject) {
						if (!isset($source_log_entries_for_missing_guids[$dep_subject['guid']])) { 
							continue; 
						}
						if ($dep_subject['subject_table_num'] == 4) { continue; }		// attributes should not be dependencies
						
						if ($entry['guid'] === $dep_subject['guid']) { continue; }		// don't make item dependency of itself
						$va_dependency_list[$entry['guid']][$dep_subject['guid']] = true;
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
								if (!in_array($matches[1], ['lot', 'lot_id', 'parent', 'parent_id'], true)) { 
									continue; 
								} 		// @TODO: just do dependencies for lots and parents for now; eventually we'll need to consider list items, relationship types, et al.
								if ($entry['guid'] === $snv) { continue; }										// don't make item dependency of itself
								
								if (!isset($source_log_entries_for_missing_guids[$snv])) { continue; }
								$this->log(_t("[%1] Added %2 [%3] as dep from snapshot for %4.", $this->source_key, $snk, $snv, $entry['guid']),Zend_Log::DEBUG);
								$va_dependency_list[$entry['guid']][$snv] = true;
							}
						}
					}
				}
			}
		}
		return $va_dependency_list;
	}
	# --------------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private function _pushMissingGUIDs($source_log_entries_for_missing_guids, $source_log_entries_for_missing_guids_seen_guids) {
		$va_dependency_list = $this->_analyzeDependencies($source_log_entries_for_missing_guids);
		
		//
		// Attempt to push missing guids subject to dependencies
		//
		$va_seen = [];
		foreach($source_log_entries_for_missing_guids as $vs_missing_guid => $va_source_log_entries_for_missing_guid) {     
			if (is_array($va_dependency_list[$vs_missing_guid]) && (($s = sizeof($va_dependency_list[$vs_missing_guid])) > 0)) { 
				$this->log(_t("[%1] Holding push of %2 missing entries for %3 because it still has %4 dependencies: %5.", $this->source_key, sizeof($va_source_log_entries_for_missing_guid), $vs_missing_guid, $s, print_R(array_keys($va_dependency_list[$vs_missing_guid]), true)), Zend_Log::DEBUG);
				continue;
			}                          
			foreach(array_keys($va_dependency_list) as $k1) {
				unset($va_dependency_list[$k1][$vs_missing_guid]);
			}
		
			$this->log(_t("[%1] Pushing %2 missing entries for %3.", $this->source_key, sizeof($va_source_log_entries_for_missing_guid), $vs_missing_guid), Zend_Log::DEBUG);
			
			ksort($va_source_log_entries_for_missing_guid, SORT_NUMERIC);
			while(sizeof($va_source_log_entries_for_missing_guid) > 0) {
				$va_entries = [];
				$vn_first_missing_log_id = null;
			
				while(sizeof($va_source_log_entries_for_missing_guid) > 0) {
					$va_log_entry = array_shift($va_source_log_entries_for_missing_guid);
					$vn_mlog_id = $va_log_entry['log_id'];
					
					if ($vn_log_id == $vn_mlog_id) { continue; } // don't pull in current log entry
					if (isset($va_source_log_entries[$vn_mlog_id])) { continue; } // don't push source log entry for a second time
				
										
					// Don't send log entry for missing guid more than once 
					// (can happen with attributes where they can be pulled as missing on the primary record 
					//  and then as dependencies of the primary record)
					if($this->sent_log_ids[$vn_mlog_id]) { continue; }
					if (!$vn_mlog_id) { 
						$this->log(_t("[%1] Skipped entry because it lacks a log_id %2.", $this->source_key, print_R($va_log_entry, true)), Zend_Log::DEBUG);
						continue; 
					}
					if ($vn_mlog_id > $this->last_log_id) { 
						$this->log(_t("[%1] Skipped %2 (during push of missing?) because it's in the future.", $this->source_key, $vn_mlog_id),Zend_Log::DEBUG);
						continue; 
					}
				
					foreach($va_log_entry['snapshot'] as $k => $v) {
						if (in_array($v, $this->guids_to_skip, true)) { 
							if(($k === 'user_id') || ($k === 'user_id_guid')) { continue; }
							if (preg_match("!parent!", $k)) {	
								// If no access to parent, remove dependency and import as standalone record
								// @TODO: maybe make this a configurable option?
								unset($va_log_entry['snapshot'][$k]);
								unset($va_log_entry['snapshot'][str_replace("_guid", "", $k)]);
								$this->log(_t("[%1] Removed parent from snapshot in log_id %2 because parent dependency %3 is not available.", $this->source_key, $vn_mlog_id, $v),Zend_Log::DEBUG);
							} else {
								// SKIP log entry because dependency is not available
								$this->log(_t("[%1] Skipped %2 because dependency %3 is not available.", $this->source_key, $vn_mlog_id, $v),Zend_Log::DEBUG);
								continue(2); 
							}
						}
					}
				
					if (is_array($va_dependency_list[$va_log_entry['guid']]) && (($s = sizeof($va_dependency_list[$va_log_entry['guid']])) > 0)) { 
						// Skip log entry because it still has dependencies
						$this->log(_t("[%1] Skipped log_id %2 [%3] because it still has %4 dependencies: %5.", $this->source_key, $vn_mlog_id, $va_log_entry['guid'], $s, print_R(array_keys($va_dependency_list[$va_log_entry['guid']]), true)),Zend_Log::DEBUG);
						continue;
					}
				
					if ($va_seen[$vn_mlog_id]) { 
						// Skip log entry because it has already been pushed
						$this->log(_t("[%1] Skipped %2 because it has already been pushed.", $this->source_key, $vn_mlog_id), Zend_Log::DEBUG);
						continue; 
					}
			   
					if (!$vn_first_missing_log_id) { $vn_first_missing_log_id = $vn_mlog_id; }
				
					 if (($va_log_entry['logged_table_num'] == 3) && ($vs_attr_guid = caGetOption(['attribute_guid', 'attribute_id_guid'], $va_log_entry['snapshot'], null))) {
						$o_chk_attr_existence = $this->target->setRequestMethod('POST')->setEndpoint('hasGUID')
								->setRequestBody([$vs_attr_guid])
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request();
						$va_chk_attr_existence = $o_chk_attr_existence->getRawData();
						if ($va_chk_attr_existence[$vs_attr_guid] == '???') {
							// need to push attribute
							$va_attr_log = $this->source->setEndpoint('getlog')
								->clearGetParameters()
								->addGetParameter('forGUID', $vs_attr_guid)
								->addGetParameter('skipIfExpression', $this->get_log_service_params['skipIfExpression'])
								->addGetParameter('ignoreTables', $this->get_log_service_params['ignoreTables'])
								->addGetParameter('onlyTables', $this->get_log_service_params['onlyTables'])
								->addGetParameter('includeMetadata', $this->get_log_service_params['includeMetadata'])
								->addGetParameter('excludeMetadata', $this->get_log_service_params['excludeMetadata'])
								->addGetParameter('pushMediaTo', $this->get_log_service_params['pushMediaTo'])
								->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
								->request()->getRawData();
							
							$va_attr_log = is_array($va_attr_log) ? array_filter($va_attr_log, function($v) { return !$v['SKIP']; }) : null;
							if (is_array($va_attr_log) && sizeof($va_attr_log)) {
								$acc = [];
								foreach($va_attr_log as $x) {
									if ($x['log_id'] == 1) {
										$synth_log_id = null;
										$co=0;
									
										// look for first log entry that defines row_guid
										$base_log_id = $vn_mlog_id - 1; // If all else fails we'll map sync attr before current, as that has the best odds of working
										$sorted_missing_log = $va_attr_log;
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
										} while(in_array((string)$synth_log_id, array_keys($va_entries), true));
									
										if ($synth_log_id) {
											$x['log_id'] = $x['i'] = $synth_log_id;
										} else {
											$this->log("Unable to map synth attr $vs_attr_guid log_id.", Zend_Log::DEBUG);
										}
									}
									if (!$va_seen[$x['log_id']] && ($x['log_id'] !== $vs_missing_guid)) {
										$va_seen[$x['log_id']] = $x['guid'];
										$va_entries[$x['log_id']] = $acc[$x['log_id']] = $x;
									}
								}
								$size = sizeof($acc);
								if ($size > 0) {
									$this->log(_t("[%1] Adding %4 unpushed attribute log entries starting with %2 for %3 [%5].", $this->source_key, $vn_first_missing_log_id, $vs_missing_guid, $size, $vs_attr_guid), Zend_Log::DEBUG);
								}
							}
						}
					}
			
					if(!$va_seen[$vn_mlog_id]) {
						$va_entries[$vn_mlog_id] = $va_log_entry;
						$va_seen[$vn_mlog_id] = $vs_missing_guid;
					}
				
					if ((sizeof($va_entries) >= 200) || (sizeof($va_source_log_entries_for_missing_guid) == 0)) { break; }
				}
				ksort($va_entries, SORT_NUMERIC);
		
				$size = sizeof($va_entries);
			
				if ($size > 0) {
					$this->log(_t("[%1] Pushing %4 missing log entries starting with %2 for %3.", $this->source_key, $vn_first_missing_log_id, $vs_missing_guid, $size), Zend_Log::DEBUG);
				}
				
				
				$o_backlog_resp = $this->target->setRequestMethod('POST')->setEndpoint('applylog')
					->addGetParameter('system_guid', $this->source_guid)
					->setRequestBody($va_entries)
					->setRetries($this->max_retries)->setRetryDelay($this->retry_delay)
					->request();
				foreach($va_entries as $mlog_id => $entry) {					
					// Mark log entry as sent, to ensure we don't send it again in this session
					// (Double sending of a log entry can happen with attributes in some cases where they
					//  are pulled as part of the primary record and then as a dependency)
					$this->sent_log_ids[$mlog_id] = true;
				}
			}
		
			unset($source_log_entries_for_missing_guids[$vs_missing_guid]);
			unset($source_log_entries_for_missing_guids_seen_guids[$vs_missing_guid]);
		}
		return [$source_log_entries_for_missing_guids, $source_log_entries_for_missing_guids_seen_guids];
	}
	# --------------------------------------------------------------------------------------------------------------
}
