<?php
/** ---------------------------------------------------------------------
 * app/lib/Sync/Replicator.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2018 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Datamodel.php");
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
				$res = $o_result->getRawData();
				if (is_array($res) && isset($res['errors'])) {
				    $this->log(_t("There were errors getting last replicated log id for source %1 and target %2: %3", $vs_source_key, $vs_target_key, join('; ', $res['errors'])), Zend_Log::ERR);
				    continue;
				}
				
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
				$vn_retry_count = 0;
				$vn_max_retry_count = 1;
				$pb_ok = true;
				
				$va_seen_primaries = [];
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
											->request();
						$va_guid_already_exists = $o_guid_already_exists->getRawData();
						
						
						$vs_access = is_array($pa_filter_on_access_settings) ? join(";", $pa_filter_on_access_settings) : "";
						$o_access_by_guid = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
						                    ->addGetParameter('access', $vs_access)
											->setRequestBody($z=array_unique(array_merge(caExtractArrayValuesFromArrayOfArrays($va_source_log_entries, 'guid'), array_keys($va_subject_guids))))
											->request();
						$va_access_by_guid = $o_access_by_guid->getRawData();

                        // List of log entries to push
					    $va_filtered_log_entries = [];
					    
						$va_source_log_entries_for_missing_guids = [];
						$va_source_log_entries_for_missing_guids_seen_guids = [];
						$va_guids_to_skip = [];
						
						foreach($va_source_log_entries as $vn_log_id => $va_source_log_entry) {
						    $vn_last_log_id = $vn_log_id;
							$vb_logged_exists_on_target = is_array($va_guid_already_exists[$va_source_log_entry['guid']]);
						    if ($pa_filter_on_access_settings && ($va_access_by_guid[$va_source_log_entry['guid']] !== '?') && !in_array($va_access_by_guid[$va_source_log_entry['guid']], $pa_filter_on_access_settings) && !$vb_logged_exists_on_target) {
						        // skip rows for which we have no access
						        continue;
						    }
						    
						    $va_missing_guids = []; // List of guids missing on target that we'll need to replicate to synthesize subject
						    if (!$vb_logged_exists_on_target) {
						        $va_missing_guids[] = $va_source_log_entry['guid']; // add changed "source" row to replication list
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
								            $vb_have_access_to_subject = in_array($va_access_by_guid[$va_source_log_subject['guid']], $pa_filter_on_access_settings);
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
                                    } elseif($vb_have_access_to_subject && $va_seen_primaries[$va_source_log_subject['guid']]) {
                                        // already seen this one
                                        $this->log(_t("Passing on ".$va_source_log_subject['guid']. " because it has already been seen: %1/%2", ($vb_have_access_to_subject ? "HAVE ACCESS" : "NO ACCESS"), ($vb_subject_exists_on_target ? "EXISTS ON TARGET" : "DOES NOT EXIST ON TARGET")), Zend_Log::DEBUG);
                                        $va_filtered_log_entries[$vn_log_id] = $va_source_log_entry;
                                    } elseif($vb_have_access_to_subject && !$vb_subject_exists_on_target) {
                                        // keep in filtered log
                                        
                                        $va_filtered_log_entries[$vn_log_id] = $va_source_log_entry;
                                        
                                        // Should insert on server...
                                        // ... which means synthesizing log from current state
                                        
                                        $va_seen_primaries[$va_source_log_subject['guid']] = true;
                                        
                                        $va_missing_guids[] = $va_source_log_subject['guid'];   // add subject to list of guids to replicate
                
                                        // find dependencies for rows to replicate that are not already present on the target
                                        while((sizeof($va_missing_guids) > 0)) { 
                                            $vs_missing_guid = array_shift($va_missing_guids);
                                            if ($va_source_log_entries_for_missing_guids_seen_guids[$vs_missing_guid]) { 
                                                // If we've already seen this guid then move it to the end of the list
                                                // Since the queue is pushed in reverse we're actually pushing this towards
                                                // the *beginning*, allowing it to be in place for those rows which depend upon it
                                               //  $va_entry_tmp = $va_source_log_entries_for_missing_guids[$vs_missing_guid];
//                                                 unset($va_source_log_entries_for_missing_guids[$vs_missing_guid]);
//                                                 $va_source_log_entries_for_missing_guids[$vs_missing_guid] = $va_entry_tmp;
                                                $this->log(_t("Skipped %1 because we've seen it already ", $vs_missing_guid), Zend_Log::DEBUG);
                                                continue; 
                                            } 
                                            
                                            // Pull log for "missing" guid we need to replicate on target
                                            $this->log(_t("Getting log for missing guid %1", $vs_missing_guid), Zend_Log::DEBUG);
                                            $va_log_for_missing_guid = $o_source->setEndpoint('getlog')
                                                ->clearGetParameters()
                                                ->addGetParameter('forGUID', $vs_missing_guid)
                                                ->addGetParameter('skipIfExpression', $vs_skip_if_expression)
                                                ->addGetParameter('ignoreTables', $vs_ignore_tables)
                                                ->addGetParameter('onlyTables', $vs_only_tables)
                                                ->addGetParameter('includeMetadata', $vs_include_metadata)
                                                ->addGetParameter('excludeMetadata', $vs_exclude_metadata)
                                                ->addGetParameter('pushMediaTo', $vs_push_media_to)
                                                ->request()->getRawData();
                                                
                                            if (is_array($va_log_for_missing_guid)) {
                                                $va_dependent_guids = [];
                        
                                                // Check access settings for dependent rows; we only want to replicate rows that
                                                // meet the configured access requirements (Eg. public rows only)
                                                $o_access_for_dependent = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
                                                                    ->addGetParameter('access', $vs_access)
                                                                    ->setRequestBody(array_unique(caExtractArrayValuesFromArrayOfArrays($va_log_for_missing_guid, 'guid')))
                                                                    ->request();
                                                $va_access_for_dependent = $o_access_for_dependent->getRawData();
                                                
                                                // Check for existance on target
                                                $o_guids_exist_for_missing = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
                                                                        ->setRequestBody(array_unique(caExtractArrayValuesFromArrayOfArrays($va_log_for_missing_guid, 'guid')))
                                                                        ->request();
                                                $va_guids_exist_for_missing = $o_guids_exist_for_missing->getRawData();
                                                
                                                $va_guids_that_exist_for_missing = array_keys(array_filter($va_guids_exist_for_missing, function($v) { return !is_array($v); }));
                                                
                                                $va_filtered_log_for_missing_guid = [];  
                                                foreach($va_log_for_missing_guid as $va_missing_entry) {
                                                    if ($va_missing_entry['log_id'] >= $pn_start_replicated_id) { 
                                                        // Skip rows in the future - the regular sync process will take care of those. 
                                                        // All we do here is bring the target "up to date"
                                                        continue; 
                                                    }
                                                    if ($pa_filter_on_access_settings && ($va_access_for_dependent[$va_missing_entry['guid']] !== '?') && !in_array($va_access_for_dependent[$va_missing_entry['guid']], $pa_filter_on_access_settings)) {
                                                        // skip rows for which we have no access
                                                        $this->log(_t("SKIP %1 because we have no access: %2", $va_missing_entry['guid'], print_R($va_missing_entry, true)), Zend_Log::DEBUG);
                                                        continue;
                                                    }
                                                    if ($va_guids_that_exist_for_missing && ($va_guids_that_exist_for_missing[$va_missing_entry['guid']])) {
                                                        // skip rows which already exist on target
                                                        $this->log(_t("SKIP %1 because it already exists on target", $va_missing_entry['guid']), Zend_Log::DEBUG);
                                                        continue;
                                                    }
                                                    
                                                    $va_filtered_log_for_missing_guid[$va_missing_entry['log_id']] = $va_missing_entry;
                            
                                                    $x=$this;
                                                    // Add guids for dependencies referenced by this log entry
                                                    if(is_array($va_missing_entry['snapshot'])) {
                                                        $va_dependent_guids = array_unique(array_merge($va_dependent_guids, array_values(array_filter($va_missing_entry['snapshot'], function($v, $k) use ($va_missing_entry, $x, $vs_missing_guid) { 
                                                            if ($v == $vs_missing_guid) { 
                                                                $x->log(_t("SKIP dependent %1 because log entry matches guid: %3", $v, $matches[1], print_R($va_missing_entry, true)), Zend_Log::DEBUG);
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
                                                            //$x->log(_t("SKIP %1 because field %2 in snapshot doesn't exist: %3", $v, $matches[1], print_R($va_missing_entry, true)), Zend_Log::DEBUG);
                                                        
                                                            return false;
                                                        }, ARRAY_FILTER_USE_BOTH))));
                                                    }
                                                }
                                                
                                                // Check for presence and access of dependencies on target
                                                //      We will only replicate rows meeting access requirements and not already on the target
                                                //
                                                if(sizeof($va_dependent_guids) > 0) {
                                                    $o_guids_exist_for_dependencies = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
                                                                        ->setRequestBody($va_dependent_guids)
                                                                        ->request();
                                                    $va_guids_exist_for_dependencies = $o_guids_exist_for_dependencies->getRawData();
                                                    
                                                    $va_dependent_guids = array_keys(array_filter($va_guids_exist_for_dependencies, function($v) { return !is_array($v); }));
                                                   
                                                    //$this->log("Added deps [".join("; ", array_diff($va_missing_guids, $va_dependent_guids))."] for $vs_missing_guid", Zend_Log::DEBUG);
                                                    
                                                    //$va_missing_guids = array_filter($va_missing_guids, function($v) use ($va_dependent_guids) { return !in_array($v, $va_dependent_guids);});  // remove dependencies from "missing" list
                                                    $va_missing_guids = array_unique(array_merge($va_missing_guids, $va_dependent_guids));  // add dependent guid lists to "missing" list; this will forcing dependencies to be processed through this loop
                                          
                                                    if (is_array($pa_filter_on_access_settings)) {
                                                        // Filter missing guid list using access criteria
                                                        $o_access_for_missing = $o_source->setRequestMethod('POST')->setEndpoint('hasAccess')
                                                                            ->addGetParameter('access', $vs_access)
                                                                            ->setRequestBody($va_missing_guids)
                                                                            ->request();
                                                        $va_access_for_missing = $o_access_for_missing->getRawData();
                                                        $va_missing_guids = array_keys(array_filter($va_access_for_missing, function($v) use ($pa_filter_on_access_settings) { return (($v == '?') || (in_array($v, $pa_filter_on_access_settings))); }));
                                                        $va_guids_to_skip = array_merge($va_guids_to_skip, array_keys(array_filter($va_access_for_missing, function($v) use ($pa_filter_on_access_settings) { return !in_array($v, $pa_filter_on_access_settings); })));
                                                    }
                                                } 
                                                
                    $this->log(_t("GOT %1 entries for %2", sizeof($va_filtered_log_for_missing_guid), $vs_missing_guid), Zend_Log::DEBUG);
                    $this->log(_t("%1", print_R($va_filtered_log_for_missing_guid, true)), Zend_Log::DEBUG);
                    
                                                if(sizeof($va_dependent_guids) == 0) {                                                    
                                                    // Missing guid has no outstanding dependencies so push it immediately
                                                    $this->log(_t("Immediately pushing %1 missing entries for %2", sizeof($va_filtered_log_for_missing_guid), $vs_missing_guid), Zend_Log::DEBUG);
                                                    
                                                    $va_has_attr_guids = [];
                                                    while(sizeof($va_filtered_log_for_missing_guid) > 0) {
                                                        $va_entries = [];
                                                        $vn_first_missing_log_id = null;
                            
                                                        while(sizeof($va_filtered_log_for_missing_guid) > 0) {
                                                            $va_log_entry = array_shift($va_filtered_log_for_missing_guid);
                                                            $vn_mlog_id = $va_log_entry['log_id'];
                                                            if (!$vn_mlog_id) { 
                                                                $this->log(_t("Skipped entry because it lacks a log_id %1", print_R($va_log_entry, true)), Zend_Log::DEBUG);
                                                                continue; 
                                                            }
                                                            if ($vn_mlog_id >= $pn_start_replicated_id) { 
                                                                $this->log(_t("Skipped %1 because it's in the future", $vn_mlog_id),Zend_Log::DEBUG);
                                                                continue; 
                                                            }
                                                            
                                                            // is this an attribute value? If so then check for existence of related attribute 
                                                            // since CA didn't write log entries for this in some versions
                                                            if (($va_log_entry['logged_table_num'] == 3) && ($vs_attr_guid = caGetOption(['attribute_guid', 'attribute_id_guid'], $va_log_entry['snapshot'], null))) {
                                                                $o_chk_attr_existence = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
                                                                        ->setRequestBody([$vs_attr_guid])
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
                                                                        ->request()->getRawData();
                                                                    if (is_array($va_attr_log)) {
                                                                        //$va_entries = array_merge($va_entries, $va_attr_log);
                                                                        $acc = [];
                                                                        foreach($va_attr_log as $l => $x) {
                                                                            if (!$va_seen[$x['log_id']]) {
                                                                                $va_seen[$x['log_id']] = $x['guid'];
                                                                                $va_entries[$x['log_id']] = $acc[$x['log_id']] = $x;
                                                                                // mark as seen so we don't process the same thing twice
                                                                                $va_source_log_entries_for_missing_guids_seen_guids[$x['guid']] = true;
                                                                            }
                                                                        }
                                                                        $this->log(_t("Adding %3 unpushed attribute log entries starting with %1 for %2 for immediate push.", $vn_first_missing_log_id, $vs_missing_guid, sizeof($acc)), Zend_Log::DEBUG);
                                                                    }
                                                                }
                                                            }
                                                            
                                                            if (!$vn_first_missing_log_id) { $vn_first_missing_log_id = $vn_mlog_id; }
                            
                                                            $va_entries[$vn_mlog_id] = $va_log_entry;
                                                            
                                                            // mark as seen so we don't process the same thing twice
                                                            $va_source_log_entries_for_missing_guids_seen_guids[$va_log_entry['guid']] = true;
                                                            if ((sizeof($va_entries) >= 10) || (sizeof($va_source_log_entries_for_missing_guid) == 0)) { break; }
                                                        }
                                                        
                                                        ksort($va_entries);
                        
                                                        $this->log(_t("Immediately pushing missing log entries starting with %1 for %2. %3", $vn_first_missing_log_id, $vs_missing_guid, print_R($va_entries, true)), Zend_Log::DEBUG);
                                                        $o_backlog_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
                                                            ->addGetParameter('system_guid', $vs_source_system_guid)
                                                            ->setRequestBody($va_entries)
                                                            ->request();
                                                    }
                                                } else {
                                                    // Missing guid has dependencies 
                                                    //      Queue it for replication
                                                    $va_source_log_entries_for_missing_guids[$vs_missing_guid] = $va_filtered_log_for_missing_guid;
                                                    
                                                    // mark as seen so we don't process the same thing twice
                                                    $va_source_log_entries_for_missing_guids_seen_guids[$vs_missing_guid] = true;
                                                }
                                            } else {
                                                $this->log(_t("No log for %1.", $vs_guid), Zend_Log::DEBUG);
                                            }
                                            
                                        }   // end missing guid loop
                                    }
                                }	// end subject loop							
							}
						}       // end source log entry loop

                        if(sizeof($va_source_log_entries_for_missing_guids)) {
                            $va_source_log_entries_for_missing_guids = array_reverse($va_source_log_entries_for_missing_guids);
						    
						    // analyze for dependencies
						    $va_dependency_list = [];
						    foreach($va_source_log_entries_for_missing_guids as $vs_missing_guid => $va_source_log_entries_for_missing_guid) {
						        if(!is_array($va_source_log_entries_for_missing_guid)) { continue; }
						        foreach($va_source_log_entries_for_missing_guid as $x) {
                                    if(is_array($x['subjects'])) {
                                        foreach($x['subjects'] as $dep_subject) {
                                            if (!isset($va_source_log_entries_for_missing_guids[$dep_subject['guid']])) { 
                                                continue; 
                                            }
                                            if ($dep_subject['subject_table_num'] == 4) { continue ; }
                                            $va_dependency_list[$x['guid']][$dep_subject['guid']] = true;
                                        }
                                    }
                                }
						    }
						    
						    $va_seen = [];
                            foreach($va_source_log_entries_for_missing_guids as $vs_missing_guid => $va_source_log_entries_for_missing_guid) {
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
                                        
                                        foreach($va_log_entry['snapshot'] as $k => $v) {
                                            if (caIsGUID($v) && in_array($v, $va_guids_to_skip, true)) { 
                                                if (preg_match("!parent!", $k)) {
                                                    unset($va_log_entry['snapshot'][$k]);
                                                    unset($va_log_entry['snapshot'][str_replace("_guid", "", $k)]);
                                                } else {
                                                    // SKIP log entry because dependency is not available
                                                    $this->log(_t("Skipped %1 because dependency %2 is not available", $vn_mlog_id, $v),Zend_Log::DEBUG);
                                                    continue(2); 
                                                }
                                            }
                                        }
                                        
                                        unset($va_dependency_list[$va_log_entry['guid']][$vs_missing_guid]);
                                        if (sizeof($va_dependency_list[$va_log_entry['guid']]) > 0) { 
                                            // Skip log entry because it still has dependencies
                                            $this->log(_t("Skipped %1 because it still has dependencies: %2", $vn_mlog_id, print_R($va_dependency_list[$va_log_entry['guid']], true)),Zend_Log::DEBUG);
                                            continue;
                                        }
                                        
                                        if ($va_seen[$vn_mlog_id]) { 
                                            // Skip log entry because it has already been pushed
                                            $this->log(_t("Skipped %1 because it has already been pushed", $vn_mlog_id), Zend_Log::DEBUG);
                                            continue; 
                                        }
                                        $va_seen[$vn_mlog_id] = $vs_missing_guid;
                                       
                                        if (!$vn_first_missing_log_id) { $vn_first_missing_log_id = $vn_mlog_id; }
                                        
                                         if (($va_log_entry['logged_table_num'] == 3) && ($vs_attr_guid = caGetOption(['attribute_guid', 'attribute_id_guid'], $va_log_entry['snapshot'], null))) {
                                            $o_chk_attr_existence = $o_target->setRequestMethod('POST')->setEndpoint('hasGUID')
                                                    ->setRequestBody([$vs_attr_guid])
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
                                                    ->request()->getRawData();
                                                $va_attr_log = array_filter($va_attr_log, function($v) { return !$v['SKIP']; });
                                                if (is_array($va_attr_log) && sizeof($va_attr_log)) {
                                                    $acc = [];
                                                    foreach($va_attr_log as $x) {
                                                        if ($x['log_id'] == 1) {
                                                            $synth_log_id = null;
                                                            $co=0;
                                                            
                                                            // look for first log entry that defines row_guid
                                                            $base_log_id = $vn_mlog_id - 1; // If all else fails we'll map sync attr before current, as that has the best odds of working
                                                            $sorted_missing_log = $va_attr_log;
                                                            ksort($sorted_missing_log);
                                                            foreach($sorted_missing_log as $l) {
                                                                if (($l['logged_table_num'] == 3) && ($l['snapshot']['attribute_guid'] === $x['guid'])) {
                                                                    $base_log_id = (int)$l['log_id'] - 1;    // insert before attribute value
                                                                    //$this->log("Base log id for synth log entry for attr $vs_attr_guid is $base_log_id (".$l['log_id'].")", Zend_Log::DEBUG);
                                                                    break;
                                                                }
                                                            }
                                                            
                                                            
                                                            do {
                                                                $co++;
                                                                
                                                                $synth_log_id = "{$base_log_id}.{$co}";
                                                                //$this->log("Try sync log_id as $synth_log_id", Zend_Log::DEBUG);
                                                            } while(in_array($synth_log_id, array_keys($va_entries)));
                                                            
                                                            if ($synth_log_id) {
                                                                $x['log_id'] = $x['i'] = $synth_log_id;
                                                                //$this->log("Mapped synth log entry for attr $vs_attr_guid to $synth_log_id", Zend_Log::DEBUG);
                                                            } else {
                                                                $this->log("Unable to map synth attr $vs_attr_guid log_id", Zend_Log::DEBUG);
                                                            }
                                                        }
                                                        if (!$va_seen[$x['log_id']]) {
                                                            $va_seen[$x['log_id']] = $x['guid'];
                                                            $va_entries[$x['log_id']] = $acc[$x['log_id']] = $x;
                                                        }
                                                    }
                                                    $this->log(_t("Adding %3 unpushed attribute log entries starting with %1 for %2 [%4].", $vn_first_missing_log_id, $vs_missing_guid, sizeof($acc), $vs_attr_guid), Zend_Log::DEBUG);
                                                }
                                            }
                                        }
                                    
                                        $va_entries[$vn_mlog_id] = $va_log_entry;
                                        if ((sizeof($va_entries) >= 10) || (sizeof($va_source_log_entries_for_missing_guid) == 0)) { break; }
                                    }
                                    ksort($va_entries);
                                
                                    $this->log(_t("Pushing %3 missing log entries starting with %1 for %2. [%4]", $vn_first_missing_log_id, $vs_missing_guid, sizeof($va_entries), print_R($va_entries, true)), Zend_Log::DEBUG);
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
					
					if (is_array($va_filtered_log_entries)) {
					    if (sizeof($va_filtered_log_entries) == 0) { 
                            $pn_replicated_log_id = $vn_last_log_id + 1;
                            
                            $va_last_log_entry = array_pop($va_source_log_entries);
                            
					        $this->log(_t("Nothing to push. Incrementing log index to %1 (%2)", $pn_replicated_log_id, date(DATE_RFC2822, $va_last_log_entry['log_datetime'])), Zend_Log::DEBUG);
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
					//print_R($va_source_log_entries);
					$o_resp = $o_target->setRequestMethod('POST')->setEndpoint('applylog')
						->addGetParameter('system_guid', $vs_source_system_guid)
						->addGetParameter('setIntrinsics', $vs_set_intrinsics)
						->setRequestBody($va_source_log_entries)
						->request();

                    $this->log(_t("Pushed %1 primary entries", sizeof($va_source_log_entries)), Zend_Log::DEBUG);
					$va_response_data = $o_resp->getRawData();

					if (!$o_resp->isOk() || !isset($va_response_data['replicated_log_id'])) {
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
					   
					    $this->log(_t("Pushed %1 log entries. Incrementing log index to %2 (%3)", $vn_num_log_entries, $pn_replicated_log_id, date(DATE_RFC2822, $va_last_log_entry['log_datetime'])), Zend_Log::DEBUG);
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
