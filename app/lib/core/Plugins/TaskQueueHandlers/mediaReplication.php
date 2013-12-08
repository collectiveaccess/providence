<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/TaskQueueHandlers/mediaReplication.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @subpackage TaskQueue
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__."/core/Db/Transaction.php");
require_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugTaskQueueHandler.php");
require_once(__CA_LIB_DIR__.'/core/Db.php');
require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__.'/core/Zend/Mail.php');
require_once(__CA_LIB_DIR__.'/ca/MediaReplicator.php');
	
	class WLPlugTaskQueueHandlermediaReplication Extends WLPlug Implements IWLPlugTaskQueueHandler {
		# --------------------------------------------------------------------------------
		public $error;
		public $debug = 0;
	
		# --------------------------------------------------------------------------------
		# Constructor - all task queue handlers must implement this
		#
		public function __construct() {
			$this->error = new Error();
			$this->error->setErrorOutput(0);
		}
		# --------------------------------------------------------------------------------
		public function getHandlerName() {
			return _t("Media Replication background processor");
		}
		# --------------------------------------------------------------------------------
		public function getParametersForDisplay($pa_rec) {
			$va_parameters = caUnserializeForDatabase($pa_rec["parameters"]);
			
			$va_params = array();
			
			// TODO: add params
			
			return $va_params;
		}
		# --------------------------------------------------------------------------------
		# Task processor function - all task queue handlers must implement this
		# 
		# Returns 1 on success, 0 on error
		public function process($pa_parameters) {
			// Check status of replication and either (a) start replication (b) check replication
			// if checking replication and it's done then write data back to database
			
			$va_report = array();
			
			$o_eventlog = new EventLog();
			$o_replicator = new MediaReplicator();
			
			$o_dm = Datamodel::load();
			if (!($t_instance = $o_dm->getInstanceByTableName($pa_parameters['TABLE'], true))) {
				$o_eventlog->log(array(
					"CODE" => "ERR",
					"SOURCE" => "TaskQueue->mediaReplication->process()",
					"MESSAGE" => _t("Table %1 is invalid", $pa_parameters['TABLE'])
				));
				$va_report['errors'][] = _t("Table %1 is invalid", $pa_parameters['TABLE']);
				return false;
			}
			
			if (!$t_instance->load($pa_parameters['PK_VAL'])) {
				$o_eventlog->log(array(
					"CODE" => "ERR",
					"SOURCE" => "TaskQueue->mediaReplication->process()",
					"MESSAGE" => _t("Row_id %1 is invalid for table %2", $pa_parameters['PK_VAL'], $pa_parameters['TABLE'])
				));
				$va_report['errors'][] = _t("Row_id %1 is invalid for table %2", $pa_parameters['PK_VAL'], $pa_parameters['TABLE']);
				return false;
			}
		
			$ps_field 			= $pa_parameters['FIELD'];
			$ps_target 			= $pa_parameters['TARGET'];
			$ps_version 		= $pa_parameters['VERSION'];
			$pa_target_info 	= $pa_parameters['TARGET_INFO'];
			$pa_data 			= $pa_parameters['DATA'];
			
			$va_media_info = $t_instance->getMediaInfo($ps_field);
			$va_media_info['REPLICATION_STATUS'][$ps_target] = __CA_MEDIA_REPLICATION_STATE_UPLOADING__;
			$va_media_info['REPLICATION_LOG'][$ps_target][] = array('STATUS' => __CA_MEDIA_REPLICATION_STATE_UPLOADING__, 'DATETIME' => time());
			$va_media_info['REPLICATION_KEYS'][$ps_target] = null;
			
			$t_instance->setMediaInfo($ps_field, $va_media_info);
			$t_instance->setMode(ACCESS_WRITE);
			$t_instance->update(array('processingMediaForReplication' => true));
			
			if (!is_array($va_media_desc = $t_instance->_FIELD_VALUES[$ps_field])) {
				$o_eventlog->log(array(
					"CODE" => "ERR",
					"SOURCE" => "TaskQueue->mediaReplication->process()",
					"MESSAGE" => _t("Could not record replication status: %1 has no media description data", $ps_field)
				));
				$va_report['errors'][] = _t("Could not record replication status: %1 has no media description data", $ps_field);
				return false;
			}
			
			try {
				if ($vs_key = $o_replicator->replicateMedia($t_instance->getMediaPath($ps_field, $ps_version), $pa_target_info, $pa_data)) {
					$va_media_info['REPLICATION_STATUS'][$ps_target] = __CA_MEDIA_REPLICATION_STATE_PROCESSING__;
					$va_media_info['REPLICATION_LOG'][$ps_target][] = array('STATUS' => __CA_MEDIA_REPLICATION_STATE_PROCESSING__, 'DATETIME' => time());
					$va_media_info['REPLICATION_KEYS'][$ps_target] = $vs_key;
					$t_instance->setMediaInfo($ps_field, $va_media_info);
					$t_instance->update(array('processingMediaForReplication' => true));
					
					$o_eventlog->log(array(
						"CODE" => "DEBG",
						"SOURCE" => "TaskQueue->mediaReplication->process()",
						"MESSAGE" => _t('Media replicated to %1 for processing', $pa_target_info['name'])
					));
					$va_report['notes'][] = _t('Media replicated to %1 for processing', $pa_target_info['name']);
				} else {
					$va_media_info['REPLICATION_STATUS'][$ps_target] = __CA_MEDIA_REPLICATION_STATE_ERROR__;
					$va_media_info['REPLICATION_LOG'][$ps_target][] = array('STATUS' => __CA_MEDIA_REPLICATION_STATE_ERROR__, 'DATETIME' => time());
					$va_media_info['REPLICATION_KEYS'][$ps_target] = null;
					$t_instance->setMediaInfo($ps_field, $va_media_info);
					$t_instance->update(array('processingMediaForReplication' => true));
					
					$o_eventlog->log(array(
						"CODE" => "ERR",
						"SOURCE" => "TaskQueue->mediaReplication->process()",
						"MESSAGE" => _t('Media replication for %1 failed', $pa_target_info['name'])
					));
					$va_report['errors'][] = _t('Media replication for %1 failed', $pa_target_info['name']);
				}
			} catch (Exception $e) {
				$va_media_info['REPLICATION_STATUS'][$ps_target] = __CA_MEDIA_REPLICATION_STATE_ERROR__;
				$va_media_info['REPLICATION_LOG'][$ps_target][] = array('STATUS' => __CA_MEDIA_REPLICATION_STATE_ERROR__, 'DATETIME' => time());
				$va_media_info['REPLICATION_KEYS'][$ps_target] = null;
				$t_instance->setMediaInfo($ps_field, $va_media_info);
				$t_instance->update(array('processingMediaForReplication' => true));
				
				$o_eventlog->log(array(
					"CODE" => "ERR",
					"SOURCE" => "TaskQueue->mediaReplication->process()",
					"MESSAGE" => _t('Media replication for %1 failed: %2', $pa_target_info['name'], $e->getMessage())
				));
				$va_report['errors'][] = _t('Media replication for %1 failed: %2', $pa_target_info['name'], $e->getMessage());
			}
			
			return $va_report;
		}
		# --------------------------------------------------------------------------------
		# Cancel function - cancels queued task, doing cleanup and deleting task queue record
		# all task queue handlers must implement this
		#
		# Returns 1 on success, 0 on error
		public function cancel($pn_task_id, $pa_parameters) {
			return true;
		}
		# --------------------------------------------------------------------------------
	}
?>