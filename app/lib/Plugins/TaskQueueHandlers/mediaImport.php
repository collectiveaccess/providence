<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/mediaImport.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2026 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Db/Transaction.php");
require_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/Plugins/IWLPlugTaskQueueHandler.php");
require_once(__CA_LIB_DIR__.'/Db.php');
require_once(__CA_LIB_DIR__.'/BatchProcessor.php');
	
class WLPlugTaskQueueHandlermediaImport Extends WLPlug Implements IWLPlugTaskQueueHandler {
	# --------------------------------------------------------------------------------
	public $error;
	public $debug = 0;

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
	 *
	 */
	public function getHandlerName() {
		return _t("Media import background processor");
	}
	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function getParametersForDisplay($rec) {
		$parameters = caUnserializeForDatabase($rec["parameters"]);
		$params_for_display = [];

		$relative_directories = [];
		foreach(caGetAvailableMediaUploadPaths() as $d) {
			$relative_directories[] = preg_replace("!{$d}[/]*!", "", '/'.$parameters["importFromDirectory"]);;
		}
		$params_for_display['importFromDirectory'] = array(
			'label' => _t("Import media from"),
			'values' => $relative_directories
		);
		
		if (file_exists($parameters["importFromDirectory"])) {
			$counts = caGetDirectoryContentsCount($parameters["importFromDirectory"], $parameters["includeSubDirectories"], false);
			$params_for_display['number_of_files'] = array(
				'label' => _t("File count"),
				'value' => (int)$counts['files']
			);
		}
		return $params_for_display;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Task processor function - all task queue handlers must implement this
	 *
	 * Returns 1 on success, 0 on error
	 */
	public function process($parameters) {
		$o_response = new ResponseHTTP();
		$o_request = new RequestHTTP($o_response, array('simulateWith' => array(
				'POST' => $parameters['values'],
				'SCRIPT_NAME' => join('/', array(__CA_URL_ROOT__, 'index.php')), 'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => join('/', array(__CA_URL_ROOT__, 'index.php', 'batch', 'MediaImport', 'Save', $parameters['screen'])), 
				'PATH_INFO' => '/'.join('/', array('batch', 'MediaImport', 'Save', $parameters['screen'])),
				'REMOTE_ADDR' => $parameters['ip_address'],
				'HTTP_USER_AGENT' => 'mediaImport',
				'user_id' => $parameters['user_id']
			)
		));
		
		$o_app = AppController::getInstance($o_request, $o_response);
		
		$report = BatchProcessor::importMediaFromDirectory($o_request, $parameters);
		
		return $report;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Cancel function - cancels queued task, doing cleanup and deleting task queue record
	 * all task queue handlers must implement this
	 *
	 * Returns 1 on success, 0 on error
	 */
	public function cancel($task_id, $parameters) {
		return true;
	}
	# --------------------------------------------------------------------------------
}
