<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/metadataImport.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2024 Whirl-i-Gig
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
	
class WLPlugTaskQueueHandlermetadataImport Extends WLPlug Implements IWLPlugTaskQueueHandler {
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
		return _t("Metadata import background processor");
	}
	# --------------------------------------------------------------------------------
	public function getParametersForDisplay($rec) {
		$parameters = caUnserializeForDatabase($rec["parameters"]);
		
		$o_config = Configuration::load();
		$vs_batch_media_import_root_directory = $o_config->get('batch_media_import_root_directory');
		$vs_relative_directory = preg_replace("!{$vs_batch_media_import_root_directory}[/]*!", "", $parameters["importFromDirectory"]); 
		
		$display_parameters = [
			'source' => [
				'label' => 'Importing from',
				'value' => $parameters['sourceFile']
			]
		];
		return $display_parameters;
	}
	# --------------------------------------------------------------------------------
	# Task processor function - all task queue handlers must implement this
	# 
	# Returns 1 on success, 0 on error
	public function process($parameters) {
		
		$resp = new ResponseHTTP();
		$req = new RequestHTTP($resp, array('simulateWith' => array(
				'POST' => $parameters['values'],
				'SCRIPT_NAME' => join('/', array(__CA_URL_ROOT__, 'index.php')), 'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => join('/', array(__CA_URL_ROOT__, 'index.php', 'batch', 'MetadataImport', 'Run')), 
				'PATH_INFO' => '/'.join('/', array('batch', 'MetadataImport', 'Run')),
				'REMOTE_ADDR' => $parameters['ip_address'],
				'HTTP_USER_AGENT' => 'metadataImport',
				'user_id' => $parameters['user_id']
			)
		));
		
		$o_app = AppController::getInstance($req, $resp);
		
		set_time_limit(3600*72); // if it takes more than 72 hours we're in trouble
	
		define('__CA_DONT_QUEUE_SEARCH_INDEXING__', true);
		$report = BatchProcessor::importMetadata(
			$req, 
			$parameters['sourceFile'],
			$parameters['importer_id'],
			$parameters['inputFormat'],
			array_merge($parameters, ['originalFilename' => $parameters['sourceFileName'], 'progressCallback' => 
				function($request, $file_number, $number_of_files, $file_path, $rows_complete, $total_rows, $message, $elapsed_time, $memory_used, $num_processed, $num_error) {
				// TODO: call back to update stats
				//print "PROCESSING ROW $file_number/$rows_complete/$message/$num_processed/$total_rows/$num_error\n";
			}])
		);
		// Clean up data file
		if(file_exists($parameters['sourceFile'])) { @unlink($parameters['sourceFile']); }
		
		return $report;
	}
	# --------------------------------------------------------------------------------
	# Cancel function - cancels queued task, doing cleanup and deleting task queue record
	# all task queue handlers must implement this
	#
	# Returns 1 on success, 0 on error
	public function cancel($pn_task_id, $parameters) {
		return true;
	}
	# --------------------------------------------------------------------------------
}
