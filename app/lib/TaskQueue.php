<?php
/** ---------------------------------------------------------------------
 * app/lib/TaskQueue.php : class for managing deferred tasks queue
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/BaseObject.php");
require_once(__CA_LIB_DIR__."/Utils/ProcessStatus.php");
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");

class TaskQueue extends BaseObject {
	private $log;
	private $processes;
	private $app_plugin_manager;
	private $handler_plugin_dirs = [];
	private $config;
	private $transaction = null;
	
	static $tasks_added = 0;

	# ---------------------------------------------------------------------------
	/**
	 * @param array $options Options include:
	 * 		transaction = Transaction to execute queue modification operations within [Default is null] 
	 */
	function __construct(?array $options=null) {
		parent::__construct();
		$this->config = Configuration::load();
		if ($vs_default_dir = trim($this->config->get('taskqueue_handler_plugins'))) {
 			$this->handler_plugin_dirs[] = $vs_default_dir;
		}
		
		$this->log = caGetLogger();
		$this->processes = new ProcessStatus();
 		$this->app_plugin_manager = new ApplicationPluginManager();
 		$this->transaction = caGetOption('transaction', $options, null);
 		
 		// Let application plugins add their own task queue plugin directories
 		$va_tmp = $this->app_plugin_manager->hookRegisterTaskQueuePluginDirectories(array('handler_plugin_directories' => $this->handler_plugin_dirs, 'instance' => $this));
		$this->handler_plugin_dirs = $va_tmp['handler_plugin_directories'];
	}
	# --------------------------------------------------------------------------
	/**
	 *
	 */
	private function getHandler(string $proc_handler) {
		$found_handler = false;
		foreach($this->handler_plugin_dirs as $handler_dir) {
			if (file_exists("{$handler_dir}/{$proc_handler}.php")) {
				$found_handler = true;
				break;
			}
		}
		
		if (!$found_handler) {
			return null;
		}
		if(!file_exists("{$handler_dir}/{$proc_handler}.php")) { return null; }
		require_once("{$handler_dir}/{$proc_handler}.php");
		$proc_handler_class = "WLPlugTaskQueueHandler{$proc_handler}";
		return new $proc_handler_class();
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function getHandlerName($handler) {
		if($h = $this->getHandler($handler)) {
			return $h->getHandlerName();
		}
		return _t('Unknown');
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function getParametersForDisplay($pm_task_rec) {
		if(is_array($pm_task_rec)) {
			$va_rec = $pm_task_rec;
		} else {
			$task_id = intval($pm_task_rec);
			$o_db = $this->getDb();
			
			$qr_tasks = $o_db->query('
				SELECT * 
				FROM ca_task_queue
				WHERE 
					task_id = ?
			', [$task_id]);
			if ($qr_tasks->nextRow()) {
				$va_rec = $qr_tasks->getRow();
			} else {
				return false;
			}
		}
		
		$vs_handler = $va_rec['handler'];
		
		if($h = $this->getHandler($vs_handler)) {
			return $h->getParametersForDisplay($va_rec);
		}
		return false;
	}
	# ---------------------------------------------------------------------------
	/**
	 * Add task to queue
	 *
	 * @param string $handler Handler to execute
	 * @param array $parameters Handler-specific task parameters
	 * @param array $options Options include:
	 *		user_id = user_id to associate task with; leave blank for nobody (aka. system) [Default is null]
	 *		priority = priority to give task; lower numbers get processed first [Default is 10]
	 *		entity_key = unique identifier for entity task operates on [optional]. Will be stored as md5 hash [Default is null]
	 *		row_key	= unique identifier for row task operates on. Will be stored as md5 hash [Default is null]
	 *		notes = descriptive text for queued task [Default is null]
	 *
	 * @return int|null ID for newly added task or null on error
	 */
	function addTask(string $handler, array $parameters, ?array $options=null) : ?int {
		# 
		# Check user_id
		#
		$user_id = '';
		if (isset($options['user_id'])) {
			$t_user = new ca_users($options['user_id']);
			$user_id = $t_user->getPrimaryKey();
		}
		$user_id = ($user_id) ? intval($user_id) : null;
		
		#
		# Check priority
		#
		$priority = intval($options['priority']);
		if ($priority < 1 || $priority > 1000) { $priority = 10; }
		
		#
		# Convert parameters to array if it is not one already
		#
		if (!is_array($parameters)) { $parameters = array($parameters); }
		$o_db = $this->getDb();
		$o_db->query('
			INSERT INTO ca_task_queue
			(user_id, entity_key, row_key, created_on, started_on, completed_on, priority, handler, parameters, notes)
			VALUES
			(?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?)
		', [$user_id, md5($options['entity_key']), md5($options['row_key']), time(), $priority, $handler, base64_encode(serialize($parameters)), $options['notes'] ?? '']);
		if ($o_db->numErrors()) {
			$this->postError(503, join('; ', $o_db->getErrors()), 'TaskQueue->addTask()');
			return null;
		}
		TaskQueue::$tasks_added++;
		return $o_db->getLastInsertID();
	}
	# ---------------------------------------------------------------------------
	/**
	 * Copies file at $ps_source to temporary file in application TaskQueue tmp directory
	 * Returns empty string on failure, path to new tmp file on success
	 */
	function copyFileToQueueTmp($handler, $ps_source) {
		if ($tmpdir = $this->config->get('taskqueue_tmp_directory')) {
			if (!file_exists($tmpdir.'/'.$handler)) {
				if(!mkdir($tmpdir.'/'.$handler)) {		
					$this->postError(100, _t('Could not create tmp directory for handler "%1"', $handler), 'TaskQueue->copyFileToQueueTmp()');
					return '';
				}
			}
			$dest = tempnam($tmpdir.'/'.$handler, $handler);
			if (!copy($ps_source, $dest)) {
				$this->postError(505, _t('Could not copy "%1"', $ps_source), 'TaskQueue->copyFileToQueueTmp()');
				return '';
			}
			return $dest;
		} else {
			$this->postError(507, _t('No tmp directory configured!'), 'TaskQueue->copyFileToQueueTmp()');
			return '';
		}
	}
	# ---------------------------------------------------------------------------
	/**
	 * Reset unfinished tasks, i.e. tasks with started_on!=null, completed_on==null and error_code=0
	 * This is useful when the task queue script (or the whole machine) crashed.
	 * It shouldn't interfere with any running handlers.
	 */
	public function resetUnfinishedTasks() {
		// verify registered processes
		$o_appvars = new ApplicationVars();
		$va_processes = $o_appvars->getVar('taskqueue_processes');
		if (!is_array($va_processes)) { $va_processes = []; }
		$va_verified_processes = $this->verifyProcesses($va_processes);
		$o_appvars->setVar('taskqueue_processes', $va_verified_processes);
		$o_appvars->save();

		$o_db = $this->getDb();
		$qr_unfinished = $o_db->query('
			SELECT *
			FROM ca_task_queue
			WHERE
				completed_on IS NULL AND
				started_on IS NOT NULL AND
				error_code = 0
		');

		// reset start datetime for zombie rows
		while($qr_unfinished->nextRow()) {
			// don't touch rows that are being processed right now
			if(
				$this->rowKeyIsBeingProcessed($qr_unfinished->get('row_key')) ||
				$this->entityKeyIsBeingProcessed($qr_unfinished->get('entity_key'))
			) {
				continue;
			}
			// reset started_on datetime
			$this->log->logNotice(_t('[TaskQueue] Reset start_date for unfinished task with task_id %1', $qr_unfinished->get('task_id')));
			$o_db->query('UPDATE ca_task_queue SET started_on = NULL WHERE task_id = ?', [$qr_unfinished->get('task_id')]);
		}
	}
	# ---------------------------------------------------------------------------
	/**
	 * Resets task that did not complete due to queue crash or error
	 */
	public function resetIncompleteTasks(array $task_ids) : bool {
		$task_ids = array_filter(array_map('intval', $task_ids), function($v) { return $v; });
		if(!sizeof($task_ids)) {
			return false;
		}
		// verify registered processes
		$o_appvars = new ApplicationVars();
		$va_processes = $o_appvars->getVar('taskqueue_processes');
		if (!is_array($va_processes)) { $va_processes = []; }
		$va_verified_processes = $this->verifyProcesses($va_processes);
		$o_appvars->setVar('taskqueue_processes', $va_verified_processes);
		$o_appvars->save();

		$o_db = $this->getDb();
		$qr_unfinished = $o_db->query('
			SELECT *
			FROM ca_task_queue
			WHERE
				(completed_on IS NULL OR error_code > 0) AND
				started_on IS NOT NULL AND
				task_id IN (?)
		', [$task_ids]);

		// reset start datetime for zombie rows
		while($qr_unfinished->nextRow()) {
			// don't touch rows that are being processed right now
			if(
				$this->rowKeyIsBeingProcessed($qr_unfinished->get('row_key')) ||
				$this->entityKeyIsBeingProcessed($qr_unfinished->get('entity_key'))
			) {
				continue;
			}
			// reset started_on datetime
			$this->log->logNotice(_t('[TaskQueue] Reset start_date and error status for unfinished task with task_id %1', $qr_unfinished->get('task_id')));
			$o_db->query('UPDATE ca_task_queue SET started_on = NULL, completed_on = NULL, error_code = 0 WHERE task_id = ?', [$qr_unfinished->get('task_id')]);
		}
		return true;
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function processQueue($handler='', ?array $options=null) {
		if (!($proc_id = $this->registerProcess())) {
			return false;
		}
		if(is_array($tasks = caGetOption('limit-to-tasks', $options, null))) {
			$tasks = array_filter($tasks, 'strlen');	
		}
		
		$sql_handler_criteria = '';
		$sql_params = [];
		if ($handler) { 
			$sql_handler_criteria = ' AND (handler = ?)'; 
			$sql_params[] = $handler; 
		}
		
		if (!sizeof($this->handler_plugin_dirs)) {
			$this->log->logError(_t('[TaskQueu] Queue processing failed because no handler directories are configured; queue was halted'));
			$this->postError(510, _t('No handler directories are configured!'), 'TaskQueue->processQueue()');
			$this->unregisterProcess($proc_id);
			return false;
		}
		
		$o_db = $this->getDb();
		
		$num_rows = 1;
		$processed_count = 0;
		if (($max_process_count = $this->config->get('taskqueue_max_items_processed_per_session')) <= 0) {
			$max_process_count = 1000000;
		}
		while(($num_rows > 0) && ($processed_count <= $max_process_count)) {
			$qr_tasks = $o_db->query("
					SELECT * 
					FROM ca_task_queue
					WHERE 
						completed_on IS NULL AND started_on IS NULL
						{$sql_handler_criteria}
					ORDER BY
						priority, created_on
					LIMIT 1
			", $sql_params);
			if (($num_rows = $qr_tasks->numRows()) > 0) {
				$qr_tasks->nextRow();
				$proc_handler = $qr_tasks->get('handler');
				
				if(is_array($tasks) && sizeof($tasks)) {
					if(!in_array($proc_handler, $tasks)) { 
						$num_rows--;
						continue; 
					}
				}
				
				// lock task
				$o_db->query('
						UPDATE ca_task_queue 
						SET started_on = ?, error_code = 0
						WHERE task_id = ?'
					, [time(), (int)$qr_tasks->get('task_id')]);
					
				$this->updateRegisteredProcess($proc_id, $qr_tasks->get('row_key'), $qr_tasks->get('entity_key'));
				
				$proc_parameters = unserialize(base64_decode($qr_tasks->get('parameters')));
				
				if(!($h = $this->getHandler($proc_handler))) {
					$this->log->logError(_t('[TaskQueue] Queue processing failed because of invalid task queue handler "%1"; queue was halted', $proc_handler));
					$this->postError(500, _t('Invalid task queue handler "%1"', $proc_handler), 'TaskQueue->processQueue()');
					
					$o_db->query('
						UPDATE ca_task_queue 
						SET started_on = NULL
						WHERE task_id = ?'
					, [(int)$qr_tasks->get('task_id')]);
					$this->unregisterProcess($proc_id);
					return false;
				}
				
				$start_time = $this->_microtime2float();
				if ($va_report = $h->process($proc_parameters)) {
					$processed_count++;
					$va_report['processing_time'] = sprintf('%6.3f', $this->_microtime2float() - $start_time);
					
					$o_db->query('
						UPDATE ca_task_queue 
						SET completed_on = ?, error_code = 0, notes = ?
						WHERE task_id = ?'
					, [time(), caSerializeForDatabase($va_report), (int)$qr_tasks->get('task_id')]);
					
					
				} else {
					$errorDescription = $h->error ? $h->error->getErrorDescription() : '';
					$errorNumber      = $h->error->getErrorNumber();
					$this->log->logError(_t('[TaskQueue] Queue processing failed using handler %1: %2 [%3]; queue was NOT halted', $proc_handler, $errorDescription, $errorNumber));
					$this->errors[] = $h->error;
					
					// Got error, so mark task as failed (non-zero error_code value)
					$o_db->query('
						UPDATE ca_task_queue 
						SET completed_on = ?, error_code = ? 
						WHERE task_id = ?', 
						[time(), (int) $errorNumber, (int)$qr_tasks->get('task_id')]);
				}
				if ($o_db->numErrors()) {
					$this->log->logError(_t('[TaskQueue] Queue processing failed while closing task record using %1: %2; queue was halted', $proc_handler, join('; ', $o_db->getErrors())));
					$this->postError(515, _t('Error while closing task record: %1', join('; ', $o_db->getErrors())), 'TaskQueue->processQueue()');
					
					$this->unregisterProcess($proc_id);
					return false;
				}
			}
		}
		
		#
		# Unlock queue processing
		#
		$this->unregisterProcess($proc_id);
		return true;
	}
	# ---------------------------------------------------------------------------
	/**
	 * Cancels any pending tasks for given entity (entity key is set by caller)
	 */
	function cancelPendingTasksForEntity($ps_entity_key, $handler='') {
		if ($this->entityKeyIsBeingProcessed($ps_entity_key)) {
			$this->log->logError(_t('[TaskQueue] Can\'t cancel pending tasks for entity key "%1" because an item associated with the entity is being processed', $ps_entity_key));
			return false;
		}
		$sql_handler_criteria = '';
		$sql_params = [md5($ps_entity_key)];
		if ($handler) { 
			$sql_handler_criteria = ' AND (handler = ?)'; 
			$sql_params[] = $handler;
		}
		
		$o_db = $this->getDb();
		$qr_tasks = $o_db->query("
				SELECT 
					task_id, user_id, entity_key, created_on, completed_on,
					priority, handler, parameters, notes
				FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					entity_key = ?
					{$sql_handler_criteria}
		", $sql_params);
		
		
		$task_cancel_delete_failures = [];
		
		if (!sizeof($this->handler_plugin_dirs)) {
			$this->log->logError(_t('[TaskQueue] Cancelling of task failed because no handler directories are configured'));
			$this->postError(510, _t('No handler directories are configured!'), 'TaskQueue->cancelPendingTasks()');	
			return false;
		}
		
		while($qr_tasks->nextRow()) {
			$proc_handler = $qr_tasks->get('handler');
			
			if(!($h = $this->getHandler($proc_handler))) {
				$this->log->logError(_t('[TaskQueue] Cancelling of task failed because of invalid task queue handler "%1"; plugin directories were %2', $proc_handler, join('; ', $this->handler_plugin_dirs)));
				$this->postError(500, _t('Invalid task queue handler "%1"', $proc_handler), 'TaskQueue->cancelPendingTasks()');
				continue;
			}
			
			$proc_parameters = unserialize(base64_decode($qr_tasks->get('parameters')));
			if (!($h->cancel($qr_tasks->get('task_id'), $proc_parameters))) {
				$task_cancel_delete_failures[] = $qr_tasks->get('task_id');
			}
		}
		
		$sql_exclude_criteria = '';
		if (sizeof($task_cancel_delete_failures) > 0) {
			$sql_exclude_criteria = ' AND (task_id NOT IN (?))';
			$sql_params[] = $task_cancel_delete_failures;
		}
		
		$o_db->query("
				DELETE FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					entity_key = ?
					{$sql_handler_criteria}
					{$sql_exclude_criteria}
		", $sql_params);
		
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
	function cancelPendingTasksForRow($ps_row_key, $handler='') {
		if ($this->rowKeyIsBeingProcessed($ps_row_key)) {
			$this->log->logError(_t('[TaskQueue] Can\'t cancel pending tasks for row key "%1" because the row is being processed', $ps_row_key));
			$this->postError(510, _t('Can\'t cancel pending tasks for row key "%1" because the queue is being processed', $ps_row_key), 'TaskQueue->cancelPendingTasks()');
			return false;
		}
		$sql_handler_criteria = '';
		$sql_params = [md5($ps_row_key)];
		if ($handler) { 
			$sql_handler_criteria = ' AND (handler = ?)'; 
			$sql_params[] = $handler;
		}
		
		$o_db = $this->getDb();
		$qr_tasks = $o_db->query("
				SELECT 
					task_id, user_id, row_key, created_on, completed_on,
					priority, handler, parameters, notes
				FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					row_key = ?
					{$sql_handler_criteria}
		", $sql_params);
		
		$task_cancel_delete_failures = [];
		
		if (!sizeof($this->handler_plugin_dirs)) {
			$this->log->logError(_t('[TaskQueue] Queue processing failed because no handler directories are configured; queue was halted'));		
			$this->postError(510, _t('No handler directories are configured!'), 'TaskQueue->cancelPendingTasks()');	
			return false;
		}
		while($qr_tasks->nextRow()) {
			$proc_handler = $qr_tasks->get('handler');
			
			if(!($h = $this->getHandler($proc_handler))) {
				$this->log->logError(_t('[TaskQueue] Queue processing failed because of invalid task queue handler "%1"; queue was halted', $proc_handler));
				$this->postError(500, _t('Invalid task queue handler "%1"', $proc_handler), 'TaskQueue->cancelPendingTasks()');
				continue;
			}
			
			$proc_parameters = unserialize(base64_decode($qr_tasks->get('parameters')));
			if (!($h->cancel($qr_tasks->get('task_id'), $proc_parameters))) {
				$task_cancel_delete_failures[] = $qr_tasks->get('task_id');
			}
		}
		
		$sql_exclude_criteria = '';
		if (sizeof($task_cancel_delete_failures) > 0) {
			$sql_exclude_criteria = ' AND (task_id NOT IN (?))';
			$sql_params[] = $task_cancel_delete_failures;
		}
		
		$o_db->query("
				DELETE FROM ca_task_queue
				WHERE 
					completed_on IS NULL AND started_on IS NULL AND
					row_key = ?
					{$sql_handler_criteria}
					{$sql_exclude_criteria}
		", $sql_params);
		
		return true;
	}
	# ---------------------------------------------------------------------------
	/**
	 * Runs periodic tasks within task queue process
	 * Periodic tasks are code that need to be run regularly, such as file cleanup processes or email alerts.
	 * You can set up tasks as plugins implementing the 'PeriodicTask' hook. The plugins will be invoked every 
	 * time the TaskQueue::runPeriodicTasks() method is called. By calling this in the same cron job that runs
	 * the task queue you can centralize all tasks into a single job. Note that there is no scheduling of periodic
	 * tasks here. Every time you call runPeriodicTasks() all plugins implementing the PeriodicTask hook will be run.
	 * You should use standard cron scheduling to control when and how often periodic tasks are run.
	 */
	function runPeriodicTasks(?array $options=null) {
		$this->app_plugin_manager->hookPeriodicTask($options);
	}
	# ---------------------------------------------------------------------------
	# Process management
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function registerProcess() {
		$max_processes = intval($this->config->get(['taskqueue_max_processes', 'taskqueue_max_processes']));
		
		if ($max_processes < 1) { $max_processes = 1; }
		$o_appvars = new ApplicationVars();
		$va_processes = $o_appvars->getVar('taskqueue_processes');
	
		if (!is_array($va_processes)) { $va_processes = []; }
		$va_processes = $this->verifyProcesses($va_processes);
		
		if (sizeof($va_processes) >= $max_processes) {
			// too many processes running
			return false;
		}
		
		$proc_id = $this->processes->getProcessID();
		if ($proc_id) {
			$va_processes[$proc_id] = array('time' => time(), 'entity_key' => '', 'row_key' => '');
		} else {
			// will use fallback timeout method to manage processes since
			// we cannot detect running processes
			$proc_id = sizeof($va_processes) + 1;
			$va_processes[$proc_id] = array('time' => time(), 'entity_key' => '', 'row_key' => '');
		}
		$o_appvars->setVar('taskqueue_processes', $va_processes);
		$o_appvars->save();
		
		return $proc_id;
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function updateRegisteredProcess($pn_proc_id, $ps_row_key='', $ps_entity_key='') {
		$o_appvars = new ApplicationVars();
		$va_processes = $o_appvars->getVar('taskqueue_processes');
		
		if (is_array($va_processes[$pn_proc_id])) {
			$va_proc_info = $va_processes[$pn_proc_id];
			$va_proc_info['row_key'] = $ps_row_key;
			$va_proc_info['entity_key'] = $ps_entity_key;
			
			$va_processes[$pn_proc_id] = $va_proc_info;
			$o_appvars->setVar('taskqueue_processes', $va_processes);
			$o_appvars->save();
		}
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function rowKeyIsBeingProcessed($ps_row_key) {
		$o_appvars = new ApplicationVars();
		$va_processes = $o_appvars->getVar('taskqueue_processes');
		
		if (is_array($va_processes)) {
			foreach($va_processes as $va_proc_info) {
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
		$va_processes = $o_appvars->getVar('taskqueue_processes');
		
		if (is_array($va_processes)) {
			foreach($va_processes as $va_proc_info) {
				if ($va_proc_info['entity_key'] == $ps_row_key) {
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
		$va_processes = $o_appvars->getVar('taskqueue_processes');
		unset($va_processes[$pn_proc_id]);
		$o_appvars->setVar('taskqueue_processes', $va_processes);
		$o_appvars->save();
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function &verifyProcesses($pa_processes) {
		if (!is_array($pa_processes)) { return []; }
		$va_verified_processes = [];
		
		if ($this->processes->canDetectProcesses()) {
			foreach($pa_processes as $proc_id => $va_proc_info) {
				if ($this->processes->processExists($proc_id)) {
					$va_verified_processes[$proc_id] = $va_proc_info;
				}
			}
		} else {
			// use fallback timeout method
			$timeout = intval($this->config->get('taskqueue_process_timeout'));
			if ($timeout < 60) { $timeout = 3600; } 	// default is 1 hour
			foreach($pa_processes as $proc_id => $va_proc_info) {
				if ((time() - $va_proc_info['time']) < $timeout) {
					$va_verified_processes[$proc_id] = $va_proc_info;
				}
			}
		}
		return $va_verified_processes;	
	}
	# ---------------------------------------------------------------------------
	# Utilities
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function _microtime2float() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	function getDb() {
		return $this->transaction ? $this->transaction->getDb() : new Db();
	}
	# ---------------------------------------------------------------------------
	/**
	 *
	 */
	public static function run(?array $options=null) : bool {
		$tq = new TaskQueue();
		$quiet = caGetOption('quiet', $options, true);
		if(!is_array($tasks = caGetOption('limit-to-tasks', $options, null)) && strlen($tasks)) { 
			$options['limit-to-tasks'] = preg_split('![,;]+!', $tasks);
		}
		if(is_array($options['limit-to-tasks'])) { $options['limit-to-tasks'] = array_filter($options['limit-to-tasks'], 'strlen'); }

		if(caGetOption('restart', $options, false))  { $tq->resetUnfinishedTasks(); }

		if(!caGetOption('recurring-tasks-only', $options, null)) {
			if (!$quiet) { CLIUtils::addMessage(_t("Processing queued tasks...")); }
			$tq->processQueue(null, $options);	
		}

		if(!caGetOption('skip-recurring-tasks', $options, null)) {
			if (!$quiet) { CLIUtils::addMessage(_t("Processing recurring tasks...")); }
			$tq->runPeriodicTasks($options);	// Process recurring tasks implemented in plugins
		}
		if (!$quiet) {  CLIUtils::addMessage(_t("Processing complete.")); }
		
		return true;
	}
	# ---------------------------------------------------------------------------
}
