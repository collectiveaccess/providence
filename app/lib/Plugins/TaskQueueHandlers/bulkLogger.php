<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/bulkLogger.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024-2025 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/ApplicationError.php");

class WLPlugTaskQueueHandlerbulkLogger Extends WLPlug Implements IWLPlugTaskQueueHandler {
	# --------------------------------------------------------------------------------
	
	public $error;

	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->error = new ApplicationError();
		$this->error->setErrorOutput(0);
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns name/short description for this particular task queue plugin
	 *
	 * @return string Name - actually more of a short description - of this task queue plugin
	 */
	public function getHandlerName() {
		return _t("Background change logger");
	}
	# --------------------------------------------------------------------------------
	/**
	 * Extracts and returns printable parameters for the queue record passed in $rec
	 * This is used by utilties that present information on the task queue to show details of each queued task
	 * without having to know specifics about the type of task.
	 *
	 * @param array $rec A raw database record array for the queued task (eg. each key a field in ca_task_queue and the values are raw database data that has not been manipulated or unserialized)
	 * @return array An array of printable parameters for the task; array keys are parameter codes, values are arrays with two keys: 'label' = a printable parameter description'; 'value' is a printable parameter setting
	 */
	public function getParametersForDisplay($rec) {
		$parameters = caUnserializeForDatabase($rec["parameters"]);
		$params = [];
		
		
		$n = sizeof($parameters['logEntries'] ?? []);
		$params['logEntries'] = [
			'label' => ($n === 1) ? _t('Log entry') : _t('Log entries'),
			'value' => $n
		];
		
		return $params;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Method invoked when the task queue needs to actually execute the task. For mediaproc this means
	 * actually doing the processing of media!
	 *
	 * Return false on failure/error and sets the error property with an error description. Returns an array
	 * with processing details on success.
	 *
	 * @param array $parameters An unserialized parameters array for the current task (eg. unserialized data from ca_task_queue.parameters)
	 * @return array Returns false on error, or an array with processing details on success
	 */
	public function process($parameters) {
		$logger = caGetLogger();
		
		$report = ['errors' => [], 'notes' => [], 'count' => 0];
		
		
		$log_entries = $parameters['logEntries'] ?? null;
		if(!is_array($log_entries) || !sizeof($log_entries)) {
			$report['errors'][] = _t('No log entries set for %1::%2', $table, $row_id);
			return $report;
		}
		$c = 0;
		foreach($log_entries as $entry) {
			if(!($row_id = ($entry['row_id'] ?? null))) { 
				$report['errors'][] = _t('Empty row_id');
				continue;
			}
			if(!in_array($entry['type'], ['I', 'U', 'D'])) { 
				$report['errors'][] = _t('Invalid log entry type %1', $entry['type']);
				continue;
			}
			if(!is_array($entry['snapshot'])) { 
				$report['errors'][] = _t('No snapshot set');
				continue;
			}
			$table = $entry['table'] ?? null;
			
			if(!($t_subject = Datamodel::getInstance($table, true))) { 
				$report['errors'][] = _t('Invalid table: %1', $table);
				continue;
			}
			if($t_subject->logChange($entry['type'], $entry['user_id'] ?? null, ['datetime' => $entry['datetime'] ?? time(), 'row_id' => $row_id, 'snapshot' => $entry['snapshot']])) {
				$c++;
			}
		}
		$report['count'] = $c;
		return $report;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Cancel function - cancels queued task, doing cleanup and deleting task queue record
	 * all task queue handlers must implement this
	 *
	 * Returns true on success, false on error
	 */
	public function cancel($task_id, $parameters) {
		return true;
	}
	# --------------------------------------------------------------------------------
}
