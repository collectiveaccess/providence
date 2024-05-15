<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/mediaImport.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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
	# Constructor - all task queue handlers must implement this
	#
	public function __construct() {
		$this->error = new ApplicationError();
		$this->error->setErrorOutput(0);
	}
	# --------------------------------------------------------------------------------
	public function getHandlerName() {
		return _t("Media import background processor");
	}
	# --------------------------------------------------------------------------------
	public function getParametersForDisplay($pa_rec) {
		$va_parameters = caUnserializeForDatabase($pa_rec["parameters"]);
		$va_params_for_display = [];

		$relative_directories = [];
		foreach(caGetAvailableMediaUploadPaths() as $d) {
			$relative_directories[] = preg_replace("!{$d}[/]*!", "", '/'.$va_parameters["importFromDirectory"]);;
		}
		$va_params_for_display['importFromDirectory'] = array(
			'label' => _t("Import media from"),
			'values' => $relative_directories
		);
		
		if (file_exists($va_parameters["importFromDirectory"])) {
			$va_counts = caGetDirectoryContentsCount($va_parameters["importFromDirectory"], $va_parameters["includeSubDirectories"], false);
			$va_params_for_display['number_of_files'] = array(
				'label' => _t("File count"),
				'value' => (int)$va_counts['files']
			);
		}
		return $va_params_for_display;
	}
	# --------------------------------------------------------------------------------
	# Task processor function - all task queue handlers must implement this
	# 
	# Returns 1 on success, 0 on error
	public function process($pa_parameters) {
		$o_response = new ResponseHTTP();
		$o_request = new RequestHTTP($o_response, array('simulateWith' => array(
				'POST' => $pa_parameters['values'],
				'SCRIPT_NAME' => join('/', array(__CA_URL_ROOT__, 'index.php')), 'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => join('/', array(__CA_URL_ROOT__, 'index.php', 'batch', 'MediaImport', 'Save', $pa_parameters['screen'])), 
				'PATH_INFO' => '/'.join('/', array('batch', 'MediaImport', 'Save', $pa_parameters['screen'])),
				'REMOTE_ADDR' => $pa_parameters['ip_address'],
				'HTTP_USER_AGENT' => 'mediaImport',
				'user_id' => $pa_parameters['user_id']
			)
		));
		
		$o_app = AppController::getInstance($o_request, $o_response);
		
		$va_report = BatchProcessor::importMediaFromDirectory($o_request, $pa_parameters);
		
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
