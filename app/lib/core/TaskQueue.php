<?php
/** ---------------------------------------------------------------------
 * app/lib/core/TaskQueue.php : class for managing deferred tasks queue
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2012 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/core/BaseObject.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Error.php");
require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
require_once(__CA_LIB_DIR__."/core/ApplicationVars.php");
require_once(__CA_LIB_DIR__."/core/Utils/ProcessStatus.php");
require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
require_once(__CA_MODELS_DIR__."/ca_task_queue.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");

class TaskQueue extends BaseObject {

	private $opo_eventlog;
	private $opo_processes;
	private $opo_app_plugin_manager;
	private $opa_handler_plugin_dirs = array();
	private $opo_config;

	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function __construct() {
		parent::__construct();
		$this->opo_config = Configuration::load();
		if ($vs_default_dir = trim($this->opo_config->get("taskqueue_handler_plugins"))) {
 			$this->opa_handler_plugin_dirs[] = $vs_default_dir;
		}
		
		$this->opo_eventlog = new Eventlog();
		$this->opo_processes = new ProcessStatus();
 		$this->opo_app_plugin_manager = new ApplicationPluginManager();
 		
 		// Let application plugins add their own task queue plugin directories
 		$va_tmp = $this->opo_app_plugin_manager->hookRegisterTaskQueuePluginDirectories(array('handler_plugin_directories' => $this->opa_handler_plugin_dirs, 'instance' => $this));
		$this->opa_handler_plugin_dirs = $va_tmp['handler_plugin_directories'];
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function getHandlerName($ps_handler) {
		if (sizeof($this->opa_handler_plugin_dirs)) {
			foreach($this->opa_handler_plugin_dirs as $vs_handler_dir) {
				if (file_exists($vs_handler_dir."/".$ps_handler.".php")) {
					require_once($vs_handler_dir."/".$ps_handler.".php");
					eval("\$h = new WLPlugTaskQueueHandler".$ps_handler."();");
			
					return $h->getHandlerName();
				}
			}
		}
		return _t("Unknown");
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function getParametersForDisplay($pm_task_rec) {
		if(is_array($pm_task_rec)) {
			$va_rec = $pm_task_rec;
		} else {
			$vn_task_id = intval($pm_task_rec);
			$o_db = new Db();
			
			$qr_tasks = $o_db->query("
				SELECT * 
				FROM ca_task_queue
				WHERE 
					task_id = ?
			", $vn_task_id);
			if ($qr_tasks->nextRow()) {
				$va_rec = $qr_tasks->getRow();
			} else {
				return false;
			}
		}
		
		$vs_handler = $va_rec["handler"];
		
		if (sizeof($this->opa_handler_plugin_dirs)) {
			foreach($this->opa_handler_plugin_dirs as $vs_handler_dir) {
				if (file_exists($vs_handler_dir."/".$vs_handler.".php")) {
					require_once($vs_handler_dir."/".$vs_handler.".php");
					eval("\$h = new WLPlugTaskQueueHandler".$vs_handler."();");
					
					return $h->getParametersForDisplay($va_rec);
				}
			}
		}
		return false;
	}
	# ---------------------------------------------------------------------------
	/**
	 * Options for addTask()
	 *
	 *	"user_id" 		= user_id to associate task with; leave blank for "nobody" (aka. system)
	 *	"priority"		= priority to give task; lower numbers get processed first; default is 10
	 *	"entity_key"	= unique identifier for entity task operates on [optional]. Will be stored as md5 hash
	 *	"row_key"		= unique identifier for row task operates on. Will be stored as md5 hash
	 *	"notes"			= descriptive text for queued task
	 *
	 */
	function addTask($ps_handler, $pa_parameters, $pa_options) {
		# 
		# Check user_id
		#
		$vn_user_id = "";
		if (isset($pa_options["user_id"])) {
			$t_user = new ca_users($pa_options["user_id"]);
			$vn_user_id = $t_user->getPrimaryKey();
		}
		$vn_user_id = ($vn_user_id) ? intval($vn_user_id) : null;
		
		#
		# Check priority
		#
		$vn_priority = intval($pa_options["priority"]);
		if ($vn_priority < 1 || $vn_priority > 1000) { $vn_priority = 10; }
		
		#
		# Convert parameters to array if it is not one already
		#
		if (!is_array($pa_parameters)) { $pa_parameters = array($pa_parameters); }
		$o_db = new Db();
		$o_db->query("
			INSERT INTO ca_task_queue
			(user_id, entity_key, row_key, created_on, started_on, completed_on, priority, handler, parameters, notes)
			VALUES
			(?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?)
		", $vn_user_id, md5($pa_options["entity_key"]), md5($pa_options["row_key"]), time(), $vn_priority, $ps_handler, base64_encode(serialize($pa_parameters)), $pa_options["notes"] ? $pa_options["notes"] : '');
		if ($o_db->numErrors()) {
			$this->postError(503, join('; ', $o_db->getErrors()), "TaskQueue->addTask()");
			return false;
		}
		return $o_db->getLastInsertID();
	}
	# ---------------------------------------------------------------------------
	/**
	 * Copies file at $ps_source to temporary file in application TaskQueue tmp directory
	 * Returns empty string on failure, path to new tmp file on success
	 */
	function copyFileToQueueTmp($ps_handler, $ps_source) {
		if ($tmpdir = $this->opo_config->get("taskqueue_tmp_directory")) {
			if (!file_exists($tmpdir.'/'.$ps_handler)) {
				if(!mkdir($tmpdir.'/'.$ps_handler)) {		
					$this->postError(100, _t("Could not create tmp directory for handler '%1'", $ps_handler), "TaskQueue->copyFileToQueueTmp()");
					return "";
				}
			}
			$dest = tempnam($tmpdir.'/'.$ps_handler, $ps_handler);
			if (!copy($ps_source, $dest)) {
				$this->postError(505, _t("Could not copy '%1'", $ps_source), "TaskQueue->copyFileToQueueTmp()");
				return "";
			}
			return $dest;
		} else {
			$this->postError(507, _t("No tmp directory configured!"), "TaskQueue->copyFileToQueueTmp()");
			return "";
		}
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function processQueue($ps_handler="") {
		if (!($vn_proc_id = $this->registerProcess())) {
			return false;
		}
		
		$sql_handler_criteria = "";
		if ($ps_handler) { $sql_handler_criteria = " AND (handler = '".addslashes($ps_handler)."')"; }
		
		if (!sizeof($this->opa_handler_plugin_dirs)) {
			$this->opo_eventlog->log(array(
				"CODE" => "ERR", 
				"SOURCE" => "TaskQueue->processQueue()", 
				"MESSAGE" => "Queue processing failed because no handler directories are configured; queue was halted")
			);		
			$this->postError(510, _t("No handler directories are configured!"), "TaskQueue->processQueue()");
			$this->unregisterProcess($vn_proc_id);
			return false;
		}
		
		$o_db = new Db();
		
		$vn_num_rows = 1;
		$vn_processed_count = 0;
		if (($vn_max_process_count = $this->opo_config->get('taskqueue_max_items_processed_per_session')) <= 0) {
			$vn_max_process_count = 1000000;
		}
		while(($vn_num_rows > 0) && ($vn_processed_count <= $vn_max_process_count)) {
		
			$qr_tasks = $o_db->query("
					SELECT * 
					FROM ca_task_queue
					WHERE 
						completed_on IS NULL AND started_on IS NULL
						$sql_handler_criteria
					ORDER BY
						priority, created_on
					LIMIT 1
			");
			if (($vn_num_rows = $qr_tasks->numRows()) > 0) {
				$qr_tasks->nextRow();
				
				// lock task
				$o_db->query("
						UPDATE ca_task_queue 
						SET started_on = ?, error_code = 0
						WHERE task_id = ?"
					, time(), (int)$qr_tasks->get("task_id"));
					
				$this->updateRegisteredProcess($vn_proc_id, $qr_tasks->get('row_key'), $qr_tasks->get('entity_key'));
				
				$proc_handler = $qr_tasks->get("handler");
				
				$vb_found_handler = false;
				foreach($this->opa_handler_plugin_dirs as $vs_handler_dir) {
					if (file_exists($vs_handler_dir."/".$proc_handler.".php")) {
						$vb_found_handler = true;
						break;
					}
				}
				
				if (!$vb_found_handler) {
					$this->opo_eventlog->log(array("CODE" => "ERR", "SOURCE" => "TaskQueue->processQueue()", "MESSAGE" => "Queue processing failed because of invalid task queue handler '$proc_handler'; queue was halted"));
					$this->postError(500, _t("Invalid task queue handler '%1'", $proc_handler), "TaskQueue->processQueue()");
					
					$o_db->query("
						UPDATE ca_task_queue 
						SET started_on = NULL
						WHERE task_id = ?"
					, (int)$qr_tasks->get("task_id"));
					continue;
				}
				
				$proc_parameters = unserialize(base64_decode($qr_tasks->get("parameters")));
				
				# load handler
				require_once($vs_handler_dir."/".$proc_handler.".php");
				eval("\$h = new WLPlugTaskQueueHandler".$proc_handler."();");
				
				$vn_start_time = $this->_microtime2float();
				if ($va_report = $h->process($proc_parameters)) {
					$vn_processed_count++;
					$va_report['processing_time'] = sprintf("%6.3f", $this->_microtime2float() - $vn_start_time);
					
					$o_db->query("
						UPDATE ca_task_queue 
						SET completed_on = ?, error_code = 0, notes = ?
						WHERE task_id = ?"
					, time(), caSerializeForDatabase($va_report), (int)$qr_tasks->get("task_id"));
					
					
				} else {
					$this->opo_eventlog->log(array(
						"CODE" => "ERR", 
						"SOURCE" => "TaskQueue->processQueue()", 
						"MESSAGE" => "Queue processing failed using handler $proc_handler: ".$h->error->getErrorDescription()." [".$h->error->getErrorNumber()."]; queue was <b>NOT</b> halted")
					);
					$this->errors[] = $h->error;
					
					// Got error, so mark task as failed (non-zero error_code value)
					$o_db->query("
						UPDATE ca_task_queue 
						SET completed_on = ?, error_code = ? 
						WHERE task_id = ?", 
						time(), (int)$h->error->getErrorNumber(), (int)$qr_tasks->get("task_id"));
				}
				if ($o_db->numErrors()) {
					$this->opo_eventlog->log(array(
						"CODE" => "ERR", 
						"SOURCE" => "TaskQueue->processQueue()", 
						"MESSAGE" => "Queue processing failed while closing task record using {$proc_handler}: ".join('; ', $o_db->getErrors())."; queue was halted")
					);
					$this->postError(515, _t("Error while closing task record: %1", join('; ', $o_db->getErrors())), "TaskQueue->processQueue()");
					
					$this->unregisterProcess($vn_proc_id);
					return false;
				}
			}
		}
		
		#
		# Unlock queue processing
		#
		$this->unregisterProcess($vn_proc_id);
		return true;
	}
	# ---------------------------------------------------------------------------
	/**
	 * Cancels any pending tasks for given entity (entity key is set by caller)
	 */
	function cancelPendingTasksForEntity($ps_entity_key, $ps_handler="") {
		if ($this->entityKeyIsBeingProcessed($ps_entity_key)) {
			$this->opo_eventlog->log(array(
					"CODE" => "ERR", 
					"SOURCE" => "TaskQueue->cancelPendingTasksForEntity()", 
					"MESSAGE" => "Can't cancel pending tasks for entity key '{$ps_entity_key}' because an item associated with the entity is being processed"));
			//$this->error = "Can't cancel pending tasks for row entity '$ps_entity_key' because an item associated with the entity is being processed";
			return false;
		}
		$sql_handler_criteria = "";
		if ($ps_handler) { $sql_handler_criteria = " AND (handler = '".addslashes($ps_handler)."')"; }
		
		$o_db = new Db();
		$qr_tasks = $o_db->query("
				SELECT 
					task_id, user_id, entity_key, created_on, completed_on,
					priority, handler, parameters, notes
				FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					entity_key = ?
					$sql_handler_criteria
		", md5($ps_entity_key));
		
		
		$task_cancel_delete_failures = array();
		
		if (!sizeof($this->opa_handler_plugin_dirs)) {
			$this->opo_eventlog->log(array("CODE" => "ERR", "SOURCE" => "TaskQueue->cancelPendingTasks()", "MESSAGE" => "Cancelling of task failed because no handler directories are configured"));		
			$this->postError(510, _t("No handler directories are configured!"), "TaskQueue->cancelPendingTasks()");	

			return false;
		}
		
		while($qr_tasks->nextRow()) {
			$proc_handler = $qr_tasks->get("handler");
			
			$vb_found_handler = false;
			foreach($this->opa_handler_plugin_dirs as $vs_handler_dir) {
				if (file_exists($vs_handler_dir."/".$proc_handler.".php")) {
					$vb_found_handler = true;
					break;
				}
			}
			
			if(!$vb_found_handler) {
				$this->opo_eventlog->log(array("CODE" => "ERR", "SOURCE" => "TaskQueue->cancelPendingTasks()", "MESSAGE" => "Cancelling of task failed because of invalid task queue handler '{$proc_handler}'; plugin directories were ".join('; ', $this->opa_handler_plugin_dirs)));
				$this->postError(500, _t("Invalid task queue handler '%1'", $proc_handler), "TaskQueue->cancelPendingTasks()");

				continue;
			}
			
			# load handler
			require_once($vs_handler_dir."/".$proc_handler.".php");
			eval("\$h = new WLPlugTaskQueueHandler".$proc_handler."();");
			
			$proc_parameters = unserialize(base64_decode($qr_tasks->get("parameters")));
			if (!($h->cancel($qr_tasks->get("task_id"), $proc_parameters))) {
				$task_cancel_delete_failures[] = $qr_tasks->get("task_id");
			}
		}
		
		$sql_exclude_criteria = "";
		if (sizeof($task_cancel_delete_failures) > 0) {
			$sql_exclude_criteria = " AND (task_id NOT IN (".join(", ", $task_cancel_delete_failures)."))";
		}
		
		$o_db->query("
				DELETE FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					entity_key = ?
					$sql_handler_criteria
					$sql_exclude_criteria
		", md5($ps_entity_key));
		
		return true;
	}
	# --------------------------------------------------------------------------- 
	/**
	 * Resets error status of failed task so it can be run again on the next queue run
	 *
	 * @param int $pn_task_id The task_id of the task to be reset. Will only be reset if error_code value is non-zero
	 * @return boolean True on success, false on failure (eg. task doesn't exist or has an error_code of zero)
	 */
	function retryFailedTask($pn_task_id) {
		$t_task = new ca_task_queue($pn_task_id);
		if (!$t_task->getPrimaryKey()) { return false; }
		if ((int)$t_task->get('error_code') === 0) { return false; }
		
		$t_task->setMode(ACCESS_WRITE);
		$t_task->set('error_code', 0);
		$t_task->set('completed_on', null);
		$t_task->update();
		
		if ($t_task->numErrors()) {
			return false;
		}
		
		return true;
	} 
	# --------------------------------------------------------------------------- 
	/**
	 * Cancels any pending tasks for given row (row key is set by caller)
	 */
	function cancelPendingTasksForRow($ps_row_key, $ps_handler="") {
		if ($this->rowKeyIsBeingProcessed($ps_row_key)) {
			$this->opo_eventlog->log(array(
					"CODE" => "ERR", 
					"SOURCE" => "TaskQueue->cancelPendingTasksForRow()", 
					"MESSAGE" => "Can't cancel pending tasks for row key '$ps_row_key' because the row is being processed"));
				$this->error = "Can't cancel pending tasks for row key '$ps_row_key' because the queue is being processed";
			return false;
		}
		$sql_handler_criteria = "";
		if ($ps_handler) { $sql_handler_criteria = " AND (handler = '".addslashes($ps_handler)."')"; }
		
		$o_db = new Db();
		$qr_tasks = $o_db->query("
				SELECT 
					task_id, user_id, row_key, created_on, completed_on,
					priority, handler, parameters, notes
				FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					row_key = ?
					$sql_handler_criteria
		", md5($ps_row_key));
		
		$task_cancel_delete_failures = array();
		
		if (!sizeof($this->opa_handler_plugin_dirs)) {
			$this->opo_eventlog->log(array("CODE" => "ERR", "SOURCE" => "TaskQueue->cancelPendingTasks()", "MESSAGE" => "Queue processing failed because no handler directories are configured; queue was halted"));		
			$this->postError(510, _t("No handler directories are configured!"), "TaskQueue->cancelPendingTasks()");	
	
			return false;
		}
		while($qr_tasks->nextRow()) {
			$proc_handler = $qr_tasks->get("handler");
			
			$vb_found_handler = false;
			foreach($this->opa_handler_plugin_dirs as $vs_handler_dir) {
				if (file_exists($vs_handler_dir."/".$proc_handler.".php")) {
					$vb_found_handler = true;
					break;
				}
			}
			
			if ($vb_found_handler) {
				$this->opo_eventlog->log(array("CODE" => "ERR", "SOURCE" => "TaskQueue->cancelPendingTasks()", "MESSAGE" => "Queue processing failed because of invalid task queue handler '$proc_handler'; queue was halted"));
				$this->postError(500, _t("Invalid task queue handler '%1'", $proc_handler), "TaskQueue->cancelPendingTasks()");
				
				continue;
			}
			
			# load handler
			require_once($vs_handler_dir."/".$proc_handler.".php");
			eval("\$h = new WLPlugTaskQueueHandler".$proc_handler."();");
			
			$proc_parameters = unserialize(base64_decode($qr_tasks->get("parameters")));
			if (!($h->cancel($qr_tasks->get("task_id"), $proc_parameters))) {
				$task_cancel_delete_failures[] = $qr_tasks->get("task_id");
			}
		}
		
		$sql_exclude_criteria = "";
		if (sizeof($task_cancel_delete_failures) > 0) {
			$sql_exclude_criteria = " AND (task_id NOT IN (".join(", ", $task_cancel_delete_failures)."))";
		}
		
		$o_db->query("
				DELETE FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					row_key = ?
					$sql_handler_criteria
					$sql_exclude_criteria
		",md5($ps_row_key));
		
		return true;
	}
	# ---------------------------------------------------------------------------
	/**
	 * Runs periodic tasks within task queue process
	 * Periodic tasks are code that need to be run regularly, such as file cleanup processes or email alerts.
	 * You can set up tasks as plugins implementing the "PeriodicTask" hook. The plugins will be invoked every 
	 * time the TaskQueue::runPeriodicTasks() method is called. By calling this in the same cron job that runs
	 * the task queue you can centralize all tasks into a single job. Note that there is no scheduling of periodic
	 * tasks here. Every time you call runPeriodicTasks() all plugins implementing the PeriodicTask hook will be run.
	 * You should use standard cron scheduling to control when and how often periodic tasks are run.
	 */
	function runPeriodicTasks() {
		$this->opo_app_plugin_manager->hookPeriodicTask();
	}
	# ---------------------------------------------------------------------------
	# Process management
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function registerProcess() {
		$vn_max_opo_processes = intval($this->opo_config->get('taskqueue_max_opo_processes'));
		
		if ($vn_max_opo_processes < 1) { $vn_max_opo_processes = 1; }
		$o_appvars = new ApplicationVars();
		$va_opo_processes = $o_appvars->getVar("taskqueue_opo_processes");
	
		if (!is_array($va_opo_processes)) { $va_opo_processes = array(); }
		$va_opo_processes = $this->verifyProcesses($va_opo_processes);
		
		if (sizeof($va_opo_processes) >= $vn_max_opo_processes) {
			// too many opo_processes running
			return false;
		}
		
		$vn_proc_id = $this->opo_processes->getProcessID();
		if ($vn_proc_id) {
			$va_opo_processes[$vn_proc_id] = array('time' => time(), 'entity_key' => '', 'row_key' => '');
		} else {
			// will use fallback timeout method to manage opo_processes since
			// we cannot detect running opo_processes
			$vn_proc_id = sizeof($va_opo_processes) + 1;
			$va_opo_processes[$vn_proc_id] = array('time' => time(), 'entity_key' => '', 'row_key' => '');
		}
		$o_appvars->setVar("taskqueue_opo_processes", $va_opo_processes);
		$o_appvars->save();
		
		return $vn_proc_id;
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function updateRegisteredProcess($pn_proc_id, $ps_row_key='', $ps_entity_key='') {
		$o_appvars = new ApplicationVars();
		$va_opo_processes = $o_appvars->getVar("taskqueue_opo_processes");
		
		if (is_array($va_opo_processes[$pn_proc_id])) {
			$va_proc_info = $va_opo_processes[$pn_proc_id];
			$va_proc_info['row_key'] = $ps_row_key;
			$va_proc_info['entity_key'] = $ps_entity_key;
			
			$va_opo_processes[$pn_proc_id] = $va_proc_info;
			$o_appvars->setVar("taskqueue_opo_processes", $va_opo_processes);
			$o_appvars->save();
		}
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function rowKeyIsBeingProcessed($ps_row_key) {
		$o_appvars = new ApplicationVars();
		$va_opo_processes = $o_appvars->getVar("taskqueue_opo_processes");
		
		if (is_array($va_opo_processes)) {
			foreach($va_opo_processes as $va_proc_info) {
				if ($va_proc_info['row_key'] == $ps_row_key) {
					return true;
				} 
			}
		}
		return false;
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function entityKeyIsBeingProcessed($ps_row_key) {
		$o_appvars = new ApplicationVars();
		$va_opo_processes = $o_appvars->getVar("taskqueue_opo_processes");
		
		if (is_array($va_opo_processes)) {
			foreach($va_opo_processes as $va_proc_info) {
				if ($va_proc_info['entity_key'] == $ps_entity_key) {
					return true;
				} 
			}
		}
		return false;
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function unregisterProcess($pn_proc_id) {
		$o_appvars = new ApplicationVars();
		$va_opo_processes = $o_appvars->getVar("taskqueue_opo_processes");
		unset($va_opo_processes[$pn_proc_id]);
		$o_appvars->setVar("taskqueue_opo_processes", $va_opo_processes);
		$o_appvars->save();
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function &verifyProcesses($pa_opo_processes) {
		if (!is_array($pa_opo_processes)) { return array(); }
		$va_verified_opo_processes = array();
		
		if ($this->opo_processes->canDetectProcesses()) {
			foreach($pa_opo_processes as $vn_proc_id => $va_proc_info) {
				if ($this->opo_processes->processExists($vn_proc_id)) {
					$va_verified_opo_processes[$vn_proc_id] = $va_proc_info;
				}
			}
		} else {
			// use fallback timeout method
			$vn_timeout = intval($this->opo_config->get('taskqueue_process_timeout'));
			if ($vn_timeout < 60) { $vn_timeout = 3600; } 	// default is 1 hour
			foreach($pa_opo_processes as $vn_proc_id => $va_proc_info) {
				if ((time() - $va_proc_info['time']) < $vn_timeout) {
					$va_verified_opo_processes[$vn_proc_id] = $va_proc_info;
				}
			}
		}
		return $va_verified_opo_processes;	
	}
	# ---------------------------------------------------------------------------
	# Utilities
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function _microtime2float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	# ---------------------------------------------------------------------------
}
# --------------------------------------------------------------------------------------------

?>