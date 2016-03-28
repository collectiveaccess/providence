<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/replication/ReplicationService.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_MODELS_DIR__.'/ca_change_log.php');
require_once(__CA_MODELS_DIR__.'/ca_replication_log.php');

require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Base.php');

class ReplicationService {
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 * @param string $ps_endpoint
	 * @param RequestHTTP $po_request
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch($ps_endpoint, $po_request) {

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
			default:
				throw new Exception('Unknown endpoint');

		}
		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @return array
	 */
	public static function getLog($po_request) {
		$pn_from = $po_request->getParameter('from', pInteger);
		if(!$pn_from) { $pn_from = 0; }

		$pn_limit = $po_request->getParameter('limit', pInteger);
		if(!$pn_limit) { $pn_limit = null; }

		$pa_options = array();
		if($ps_skip_if_expression = $po_request->getParameter('skipIfExpression', pString, null, array('retainBackslashes' => false))) {
			$pa_skip_if_expression = @json_decode($ps_skip_if_expression, true);
			if(is_array($pa_skip_if_expression) && sizeof($pa_skip_if_expression)) {
				$pa_options['skipIfExpression'] = $pa_skip_if_expression;
			}
		}

		return ca_change_log::getLog($pn_from, $pn_limit, $pa_options);
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
	public static function applyLog($po_request) {
		$vs_source_system_guid = trim($po_request->getParameter('system_guid', pString));
		if(!strlen($vs_source_system_guid)) { throw new Exception('must provide a system guid'); }
		if($po_request->getRequestMethod() !== 'POST') { throw new Exception('must be a post request'); }

		$pa_entry_options = array();
		if($ps_set_intrinsics = $po_request->getParameter('setIntrinsics', pString, null, array('retainBackslashes' => false))) {
			$pa_entry_options['setIntrinsics'] = @json_decode($ps_set_intrinsics, true);
		}

		$vn_last_applied_log_id = null;
		$va_log = json_decode($po_request->getRawPostData(), true);
		if(!is_array($va_log)) { throw new Exception('log must be array'); }
		$o_db = new Db();

		$va_warnings = $va_return = array(); $vs_error = null;
		foreach($va_log as $vn_log_id => $va_log_entry) {
			$o_tx = new \Transaction($o_db);
			try {
				$o_log_entry = CA\Sync\LogEntry\Base::getInstance($vs_source_system_guid, $vn_log_id, $va_log_entry, $o_tx);
				$o_log_entry->apply($pa_entry_options);
				$o_tx->commit();

				$vn_last_applied_log_id = $vn_log_id;
			} catch(CA\Sync\LogEntry\LogEntryInconsistency $e) {
				$o_tx->rollback();
				$va_warnings[$vn_log_id][] = $e->getMessage();
			} catch(CA\Sync\LogEntry\IrrelevantLogEntry $e) {
				$o_tx->rollback();
			} catch(\Exception $e) {
				$vs_error = $e->getMessage();
				$o_tx->rollback();
				break;
			}
		}

		$va_return['warnings'] = $va_warnings;

		if($vn_last_applied_log_id) {
			$va_return['replicated_log_id'] = $vn_last_applied_log_id;

			$t_replication_log = new ca_replication_log();
			$t_replication_log->setMode(ACCESS_WRITE);
			$t_replication_log->set('source_system_guid', $vs_source_system_guid);
			$t_replication_log->set('status', 'C');
			$t_replication_log->set('log_id', $vn_last_applied_log_id);
			$t_replication_log->insert();
		}

		if($vs_error) {
			throw new \Exception($vs_error);
		}

		return $va_return;
	}
}
