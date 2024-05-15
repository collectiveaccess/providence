<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/replication/ReplicationService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2024 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_MODELS_DIR__.'/ca_change_log.php');
require_once(__CA_MODELS_DIR__.'/ca_replication_log.php');
require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Base.php');
require_once(__CA_LIB_DIR__."/Logging/Logger.php");

class ReplicationService {
	# -------------------------------------------------------
	/**
	 *
	 */
	static $s_logger = null;
	
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $ps_endpoint
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch($ps_endpoint, $po_request) {
		if(!defined('__CA_IS_REPLICATION__')) { define('__CA_IS_REPLICATION__', true); }
		if (is_null(ReplicationService::$s_logger)) { 
			ReplicationService::$s_logger = new Logger('replication');
		}

		switch(strtolower($ps_endpoint)) {
			case 'getlog':
				$va_return = self::getLog($po_request);
				break;
			case 'getsysguid':
				$va_return = self::getSystemGUID($po_request);
				break;
			case 'getlastreplicatedlogid':
				$va_return = self::getLastReplicatedLogID($po_request);
				break;
			case 'getlogidfortimestamp':
				$va_return = self::getLogIDForTimestamp($po_request);
				break;
			case 'applylog';
				$va_return = self::applyLog($po_request);
				break;
			case 'dedup';
				$va_return = self::dedup($po_request);
				break;
			case 'pushmedia':
				$va_return = self::pushMedia($po_request);
				break;
			case 'hasguid':
				$va_return = self::hasGUID($po_request);
				break;
			case 'hasaccess':
				$va_return = self::hasAccess($po_request);
				break;
			case 'getguidsfortable':
				$va_return = self::getGUIDsForTable($po_request);
				break;
			case 'setlastlogid':	
				$va_return = self::setLastLogID($po_request);
				break;
			case 'getlastlogidsforguids':
				$va_return = self::getLastLogIDsForGUIDs($po_request);
				break;
			case 'getpublicguids':
				$va_return = self::getPublicGUIDs($po_request);
				break;
			case 'getcurrentvalue':
				$va_return = self::getCurrentValueForBundle($po_request);
				break;
			case 'setcurrentvalue':
				$va_return = self::setCurrentValueForBundle($po_request);
				break;
			default:
				throw new Exception('Unknown endpoint '.$ps_endpoint);

		}
		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 */
	public static function getLog($po_request) {
		$o_replication_conf = Configuration::load(__CA_CONF_DIR__.'/replication.conf');

		$pn_from = $po_request->getParameter('from', pInteger);
		if(!$pn_from) { $pn_from = 0; }

		$pn_limit = $po_request->getParameter('limit', pInteger);
		if(!$pn_limit) { $pn_limit = null; }
		
		$is_push_missing = $po_request->getParameter('push_missing', pInteger);
		
		if(($max_retries = (int)$o_replication_conf->get('max_media_upload_retries')) < 0) {
			$max_retries = 5;
		}
		
		$abort_sync_on_failed_media_upload = (bool)$o_replication_conf->get('abort_sync_on_failed_media_upload');
		$telescope = (bool)$o_replication_conf->get('telescope');

		$pa_options = array();
		if($ps_skip_if_expression = $po_request->getParameter('skipIfExpression', pString, null, array('retainBackslashes' => false))) {
			$pa_skip_if_expression = @json_decode($ps_skip_if_expression, true);
			if(is_array($pa_skip_if_expression) && sizeof($pa_skip_if_expression)) {
				$pa_options['skipIfExpression'] = $pa_skip_if_expression;
			}
		}

		if($ps_ignore_tables = $po_request->getParameter('ignoreTables', pString, null, array('retainBackslashes' => false))) {
			$pa_ignore_tables = @json_decode($ps_ignore_tables, true);
			if(is_array($pa_ignore_tables) && sizeof($pa_ignore_tables)) {
				$pa_options['ignoreTables'] = $pa_ignore_tables;
			}
		}
		
		if($ps_only_tables = $po_request->getParameter('onlyTables', pString, null, array('retainBackslashes' => false))) {
			$pa_only_tables = @json_decode($ps_only_tables, true);
			if(is_array($pa_only_tables) && sizeof($pa_only_tables)) {
				$pa_options['onlyTables'] = $pa_only_tables;
			}
		}
		
		if($ps_include_metadata = $po_request->getParameter('includeMetadata', pString, null, array('retainBackslashes' => false))) {
			$pa_include_metadata = @json_decode($ps_include_metadata, true);
			if(is_array($pa_include_metadata) && sizeof($pa_include_metadata)) {
				$pa_options['includeMetadata'] = $pa_include_metadata;
			}
		}
		if($ps_exclude_metadata = $po_request->getParameter('excludeMetadata', pString, null, array('retainBackslashes' => false))) {
			$pa_exclude_metadata = @json_decode($ps_exclude_metadata, true);
			if(is_array($pa_exclude_metadata) && sizeof($pa_exclude_metadata)) {
				$pa_options['excludeMetadata'] = $pa_exclude_metadata;
			}
		}
		if ($ps_for_guid = $po_request->getParameter('forGUID', pString)) {
			$pa_options['forGUID'] = $ps_for_guid;
		}
		if ($ps_for_logged_guid = $po_request->getParameter('forLoggedGUID', pString)) {
			$pa_options['forLoggedGUID'] = $ps_for_logged_guid;
		}

		// if log contains media, and pushMediaTo is set, copy media first before sending log. that way the
		// other side of the sync doesn't have to pull the media from us (which may not be possible due to networking
		// restrictions) but can use local file paths instead
		if(
			($ps_push_media_to = $po_request->getParameter('pushMediaTo', pString, null, ['retainBackslashes' => false]))
			&&
			(isset($o_replication_conf->get('targets')[$ps_push_media_to]))
		) {
			$va_target_conf = $o_replication_conf->get('targets')[$ps_push_media_to];

			$va_media = [];
			// passing a 4th param here changes the behavior slightly
			$va_log = ca_change_log::getLog($pn_from, $pn_limit, array_merge($pa_options, ['push_missing' => $is_push_missing, 'telescope' => $telescope, 'forceValuesForAllAttributeSLots' => true]), $va_media);

			if(sizeof($va_media) > 0) {
				$va_push_list = [];
				foreach($va_media as $vs_md5 => $vs_url) {
					if (!$vs_url) { continue; }
					if (isset($va_push_list[$vs_md5])) { continue; }

					// translate url to absolute media path
					$vs_path_from_url = parse_url($vs_url, PHP_URL_PATH);
					$vs_local_path = __CA_BASE_DIR__ . str_replace(__CA_URL_ROOT__, '', $vs_path_from_url);
					if (!file_exists(realpath($vs_local_path))) { continue; }
					
					ReplicationService::$s_logger->log("Push media {$vs_url}::{$vs_md5} [".caHumanFilesize($vn_filesize = @filesize($vs_local_path))."]");
					if ($vn_filesize > (1024 * 1024 * 750)) { continue; } // bail if file > 750megs
					// send media to remote service endpoint
					$o_curl = curl_init($va_target_conf['url'] . '/service.php/replication/pushMedia');
					$o_file = new CURLFile(realpath($vs_local_path));

					$vn_retries = $max_retries;
					while($vn_retries > 0) {
						$vn_retries--;
						
						curl_setopt($o_curl, CURLOPT_POST, true);
						curl_setopt(
							$o_curl,
							CURLOPT_POSTFIELDS,
							[
								'file' => $o_file,
								'url_checksum' => $vs_md5
							]
						);
					
						curl_setopt($o_curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($o_curl, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($o_curl, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($o_curl, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($o_curl, CURLOPT_CONNECTTIMEOUT, 60);
						curl_setopt($o_curl, CURLOPT_TIMEOUT, 7200);

						// basic auth
						curl_setopt($o_curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
						curl_setopt($o_curl, CURLOPT_USERPWD, $va_target_conf['service_user'].':'.$va_target_conf['service_key']);

						curl_exec($o_curl);

						$vn_code = curl_getinfo($o_curl, CURLINFO_HTTP_CODE);
						if($vn_code != 200) {
							if ($vn_retries == 0) {
								ReplicationService::$s_logger->log(_t("Could not upload file [%1] to target [%2] after %4 retries. HTTP response code was %3.", $vs_local_path, $ps_push_media_to, $vn_code, $max_retries));
								if($abort_sync_on_failed_media_upload) {
									throw new Exception(_t("Could not upload file [%1] to target [%2] after $4 retries. HTTP response code was %3.", $vs_local_path, $ps_push_media_to, $vn_code, $max_retries));
								}
								break;
							}
							ReplicationService::$s_logger->log(_t("Could not upload file [%1] to target [%2]. HTTP response code was %3. Retrying (%4 remaining)", $vs_local_path, $ps_push_media_to, $vn_code, $vn_retries));
							sleep(2);
							continue;
						}

						curl_close($o_curl);
						break;
					}
					
					$va_push_list[$vs_md5] = true;
				}
			}
		} else {
			$va_log = ca_change_log::getLog($pn_from, $pn_limit, array_merge($pa_options, ['push_missing' => $is_push_missing, 'telescope' => $telescope, 'forceValuesForAllAttributeSLots' => true]));
		}

        foreach($va_log as $i => $l) {
            unset($va_log[$i]['snapshot']['media_content']);
        }

		return $va_log;
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 */
	public static function getSystemGUID($po_request) {
		$o_vars = new ApplicationVars();
		return array('system_guid' => $o_vars->getVar('system_guid'));
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function getLastReplicatedLogID($po_request) {
		$vs_guid = trim($po_request->getParameter('system_guid', pString));
		if(!strlen($vs_guid)) { throw new Exception('must provide a system guid'); }

		return array('replicated_log_id' => ca_replication_log::getLastReplicatedLogID($vs_guid));
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function getLogIDForTimestamp($po_request) {
		$vn_timestamp = trim($po_request->getParameter('timestamp', pInteger));
		if(!strlen($vn_timestamp)) { throw new Exception('must provide a timestamp'); }

		$vn_log_id = ca_change_log::getLogIDForTimestamp($vn_timestamp);
		if(!$vn_log_id) { throw new Exception('could not figure out log_id for given timestamp'); }

		return array('log_id' => $vn_log_id);
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function setLastLogID($po_request) {
		$vs_source_system_guid = trim($po_request->getParameter('system_guid', pString));
		if(!strlen($vs_source_system_guid)) { throw new Exception('Must provide a system guid'); }
		$log_id = $po_request->getParameter('log_id', pInteger);
		if($log_id > 0) {
			$t_replication_log = new ca_replication_log();
			$t_replication_log->set('source_system_guid', $vs_source_system_guid);
			$t_replication_log->set('status', 'C');
			$t_replication_log->set('log_id', $log_id);
			$t_replication_log->insert();
			if ($t_replication_log->numErrors()) {
				throw new Exception(_t('Could not set last log_id: %1', join("; ", $t_replication_log->getErrors())));
			}
		} else {
			throw new Exception(_t('Log_id is not valid'));
		}
		return ['log_id' => $log_id];
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function applyLog($po_request) {
		$vs_source_system_guid = trim($po_request->getParameter('system_guid', pString));
		if(!strlen($vs_source_system_guid)) { throw new Exception('must provide a system guid'); }
		if($po_request->getRequestMethod() !== 'POST') { throw new Exception('must be a post request'); }

		$pa_entry_options = array();
		if($ps_set_intrinsics = $po_request->getParameter('setIntrinsics', pString, null, array('retainBackslashes' => false))) {
			$pa_entry_options['setIntrinsics'] = @json_decode($ps_set_intrinsics, true);
		}

		$vn_last_applied_log_id = null;
		
		$va_log = ($post_data = $po_request->getRawPostData()) ? json_decode($post_data, true) : [];
		if(!is_array($va_log)) { throw new \Exception('Log must be array: '.$post_data); }
		$o_db = new Db();

		// run
		$va_sanity_check_errors = array();
		$va_return = array(); $vs_error = null;

		foreach($va_log as $vn_log_id => $va_log_entry) {
			$o_tx = new \Transaction($o_db);
			try {
				if ($va_log_entry['SKIP']) { throw new CA\Sync\LogEntry\IrrelevantLogEntry(_t('Skip log entry')); }
			
				$o_log_entry = CA\Sync\LogEntry\Base::getInstance($vs_source_system_guid, $vn_log_id, $va_log_entry, $o_tx);
				$o_log_entry->sanityCheck();
			} catch (CA\Sync\LogEntry\IrrelevantLogEntry $e) {
				// skip log entry (still counts as "applied")
				$o_tx->rollback();
				$vn_last_applied_log_id = $vn_log_id;
				//ReplicationService::$s_logger->log("[IrrelevantLogEntry] Sanity check error: ".$e->getMessage());
				continue;
			} catch (CA\Sync\LogEntry\InvalidLogEntryException $e) {
				// skip log entry (still counts as "applied")
				$o_tx->rollback();
				$vn_last_applied_log_id = $vn_log_id;
				ReplicationService::$s_logger->log("[InvalidLogEntryException] Sanity check error: ".$e->getMessage());
				continue;
			} catch (\Exception $e) {
				// append log entry to message for easier debugging
				$va_sanity_check_errors[] = $e->getMessage() . ' ' . _t("Log entry was: %1", print_r($va_log_entry, true));
				ReplicationService::$s_logger->log("[ERROR] Sanity check error: ".$e->getMessage());
			}

			// if there were sanity check errors, return them here
			if(sizeof($va_sanity_check_errors)>0) {
				$o_tx->rollback();
				throw new \Exception(join("\n", $va_sanity_check_errors));
			}

			$o_tx = new \Transaction($o_db);
			try {
				$o_log_entry = CA\Sync\LogEntry\Base::getInstance($vs_source_system_guid, $vn_log_id, $va_log_entry, $o_tx);
				$o_log_entry->apply($pa_entry_options);

				$vn_last_applied_log_id = $vn_log_id;
			} catch(CA\Sync\LogEntry\IrrelevantLogEntry $e) {
				$o_tx->rollback();
				$vn_last_applied_log_id = $vn_log_id; // if we chose to ignore it, still counts as replicated! :-)
			
				ReplicationService::$s_logger->log("[IrrelevantLogEntry] Apply error: ".$e->getMessage());
			} catch(\Exception $e) {
				$vs_error = get_class($e) . ': ' . $e->getMessage() . $e->getTraceAsString() . ' ' . _t("Log entry was: %1", print_r($va_log_entry, true));
				$o_tx->rollback();
				ReplicationService::$s_logger->log("[ERROR] Apply error: ".$e->getMessage());
				break;
			}
			$o_tx->commit();
        }

		if($vn_last_applied_log_id) {
			$va_return['replicated_log_id'] = $vn_last_applied_log_id;

			$t_replication_log = new ca_replication_log();
			$t_replication_log->set('source_system_guid', $vs_source_system_guid);
			$t_replication_log->set('status', 'C');
			$t_replication_log->set('log_id', $vn_last_applied_log_id);
			$t_replication_log->insert();
		} else {
			$vn_last_applied_log_id = ca_replication_log::getLastReplicatedLogID($vs_source_system_guid);
			$va_return['replicated_log_id'] = $vn_last_applied_log_id;
		}

		if($vs_error) {
			throw new \Exception($vs_error);
		}

		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function dedup($po_request) {
		$pa_tables = [];
		if($ps_tables = $po_request->getParameter('tables', pString, null, array('retainBackslashes' => false))) {
			$pa_tables = @json_decode($ps_tables, true);
			if(!is_array($pa_tables) || !sizeof($pa_tables)) {
				throw new \Exception('You must define a list of tables for deduplication');
			}
		}

		$va_report = [];
		foreach($pa_tables as $vs_table) {
			// this makes sure the class is required/included
			$t_instance = Datamodel::getInstance($vs_table);
			if(!$t_instance) { continue; }

			if(class_exists($vs_table) && method_exists($vs_table, 'listPotentialDupes') && method_exists($vs_table, 'mergeRecords')) {
				$va_dupes = $vs_table::listPotentialDupes();
				if(sizeof($va_dupes)) {
					$va_report[$vs_table] = 0;
					foreach ($va_dupes as $vs_sha2 => $va_keys) {
						foreach ($va_keys as $vn_key) {
							$t_instance->load($vn_key);
						}

						$vn_entity_id = $vs_table::mergeRecords($va_keys);
						if (!$vn_entity_id) {
							throw new Exception(_t("It seems like there was an error while deduplicating records. Keys were: %1", print_r($va_keys, true)));
						}

						$va_report[$vs_table] += sizeof($va_keys);
					}
				}
			}
		}

		return ['report' => $va_report];
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function pushMedia($po_request) {
		if(!isset($_FILES['file'])) {
			throw new Exception('No file specified');
		}

		if(!($vs_checksum = $po_request->getParameter('url_checksum', pString))) {
			throw new Exception('No checksum specified');
		}

		$o_app_vars = new ApplicationVars();
		$va_files = $o_app_vars->getVar('pushMediaFiles');
		if(!is_array($va_files)) { $va_files = []; }

		$o_file_vols = new FileVolumes();
		$vs_workspace_path = $o_file_vols->getVolumeInformation('workspace')['absolutePath'];
		$vs_new_file_path = $vs_workspace_path . DIRECTORY_SEPARATOR . $_FILES['file']['name'];

		if(!@rename($_FILES['file']['tmp_name'], $vs_new_file_path)) {
			throw new Exception('Could not move temporary file. Please check the permissions for the workspace directory.');
		}


		// only stash 2000 files tops
		if(sizeof($va_files) > 2000) {
			$va_excess_files = array_splice($va_files, 0, 500);	// remove first 25% of list

			foreach($va_excess_files as $vs_file) {
				@unlink($vs_file);
			}
		}
		$va_files[$vs_checksum] = $vs_new_file_path;

		$o_app_vars->setVar('pushMediaFiles', $va_files);
		$o_app_vars->save();

		return true;
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function hasGUID($po_request) {
		if($po_request->getRequestMethod() === 'POST') { 
			$va_guids_to_check = json_decode($po_request->getRawPostData(), true);
		} else {
			$va_guids_to_check = explode(";", $po_request->getParameter('guids', pString));
		}
		if ((!is_array($va_guids_to_check) || !sizeof($va_guids_to_check)) && ($vs_guid = $po_request->getParameter('guid', pString))) {
			$va_guids_to_check = [$vs_guid];
		}
		
		$va_results = [];
		if(is_array($va_guids_to_check)) {
			foreach($va_guids_to_check as $vs_guid) {
				if(!trim($vs_guid)) { continue; }
				if (!($va_results[$vs_guid] = ca_guids::getInfoForGUID($vs_guid))) {
					$va_results[$vs_guid] = '???';
				}
			}
		}
		return $va_results;
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function hasAccess($po_request) {
		if($po_request->getRequestMethod() === 'POST') { 
			$va_guids_to_check = json_decode($po_request->getRawPostData(), true);
		} else {
			$va_guids_to_check = explode(";", $po_request->getParameter('guids', pString));
		}
		$va_access = explode(";", $po_request->getParameter('access', pString));
		if ((!is_array($va_guids_to_check) || !sizeof($va_guids_to_check)) && ($vs_guid = $po_request->getParameter('guid', pString))) {
			$va_guids_to_check = [$vs_guid];
		}
		
		$va_results = [];
		if(is_array($va_guids_to_check)) {
			foreach($va_guids_to_check as $vs_guid) {
			    $access_for_guid = ca_guids::getAccessForGUID($vs_guid, $va_access);
			    $va_results[$vs_guid] = is_null($access_for_guid) ? '?' : ($access_for_guid ? 1 : 0);
			}
		}
		return $va_results;
	}
	# -------------------------------------------------------
	/**
	 * Get GUIDs for table. Used to get full list of guids from base tables such as ca_locales 
	 *
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function getGUIDsForTable($po_request) {
		$table = $po_request->getParameter('table', pString);
		
		return ca_guids::guidsForTable($table, ['limit' => 500]);
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function getLastLogIDsForGUIDs($po_request) {
		if($po_request->getRequestMethod() === 'POST') { 
			$guids = json_decode($po_request->getRawPostData(), true);
		} else {
			$guids = explode(";", $po_request->getParameter('guids', pString));
		}
		if ((!is_array($guids) || !sizeof($guids)) && ($guid = $po_request->getParameter('guid', pString))) {
			$guids = [$guid];
		}
		
		$db = new Db();
		$ret = array_map(function($v) use ($db) {
			$qr = $db->query("SELECT max(log_id) log_id, logged_table_num, logged_row_id FROM ca_change_log WHERE logged_table_num = ? AND logged_row_id = ?", [$v['table_num'], $v['row_id']]);
			if($qr->nextRow()) {
				$v['log_id'] = $qr->get('log_id');	
			} else {
				$v['log_id'] = null;
			}
			return $v;
		}, ca_guids::getInfoForGUIDs($guids) ?? []);
		return $ret;
		
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function getPublicGUIDs($po_request) {
		$table = $po_request->getParameter('table', pString);
		if(!sizeof($access = explode(";", $po_request->getParameter('access', pString)))) {
			$access = [1];
		}
		
		if(!($table_num = Datamodel::getTableNum($table))) {
			return null;
		}
		
		$pk = Datamodel::primaryKey($table);
		$t = Datamodel::getInstance($table);
		
		$has_deleted = $t->hasField('deleted');
		
		$db = new Db();
		if($t->hasField('access')) {
			$qr = $db->query("
				SELECT g.guid 
				FROM ca_guids g
				INNER JOIN {$table} AS t ON t.{$pk} = g.row_id AND g.table_num = ? 
				WHERE t.access IN (?) ".($has_deleted ? 'AND t.deleted = 0' : '')."
			", [$table_num, $access]);
		} else {
			$qr = $db->query("
				SELECT g.guid 
				FROM ca_guids g
				INNER JOIN {$table} AS t ON t.{$pk} = g.row_id AND g.table_num = ? 
				".($has_deleted ? 'WHERE t.deleted = 0' : '')."
			", [$table_num]);
		}
		
		
		return $qr->getAllFieldValues('guid');	
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function getCurrentValueForBundle($po_request) {
		$bundle = $po_request->getParameter('bundle', pString);
	
		if($po_request->getRequestMethod() === 'POST') { 
			$guids = json_decode($po_request->getRawPostData(), true);
		} else {
			$guids = explode(";", $po_request->getParameter('guids', pString));
		}
		
		$acc = [];
		foreach($guids as $guid) {
			if($t_instance = ca_objects::getInstanceByGUID($guid)) {
				$v = $t_instance->get($bundle);
				$acc[$guid] = $v;
			}
		}	
		return $acc;
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function setCurrentValueForBundle($po_request) {
		$bundle = $po_request->getParameter('bundle', pString);
		$tmp = explode('.', $bundle);
		$element_code = array_pop($tmp);
		if($po_request->getRequestMethod() === 'POST') { 
			$guids = json_decode($po_request->getRawPostData(), true);
		} else {
			throw new ApplicationException(_t('No guids set in post'));
		}
		
		$acc = [];
		foreach($guids as $guid => $values) {
			if($t_instance = ca_objects::getInstanceByGUID($guid)) {
				$i = 0;
				foreach($values as $value) {
					if($i == 0) {
						$t_instance->replaceAttribute([$element_code => $value], $element_code);
					} else {
						$t_instance->addAttribute([$element_code => $value], $element_code);
					}
					$t_instance->update();
					$i++;
				}
			}
		}	
		return $acc;
	}
	# -------------------------------------------------------
}
