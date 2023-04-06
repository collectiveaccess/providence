<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/batchEditor.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2021 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/Db/Transaction.php");
require_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
require_once(__CA_LIB_DIR__."/Plugins/IWLPlugTaskQueueHandler.php");
require_once(__CA_LIB_DIR__."/Logging/Eventlog.php");
	
class WLPlugTaskQueueHandlerbatchEditor Extends WLPlug Implements IWLPlugTaskQueueHandler {
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
		return _t("Batch editor background processor");
	}
	# --------------------------------------------------------------------------------
	public function getParametersForDisplay($rec) {
		$parameters = caUnserializeForDatabase($rec["parameters"]);
		
		$params = [];
		
		$t_set = new ca_sets($parameters['set_id'] ?? null);
		$params['importing_from'] = array(
			'label' => _t("Applying batch edits to set"),
			'value' => $t_set->getLabelForDisplay()
		);
		$params['number_of_records'] = array(
			'label' => _t("Records to edit"),
			'value' => $parameters['record_selection']['itemCount'] ?? 0
		);
		
		$t_ui = new ca_editor_uis($parameters['ui_id']);
		
		$t_screen = new ca_editor_ui_screens();
		if ($t_screen->load(array('ui_id' => $t_ui->getPrimaryKey(), 'screen_id' => str_ireplace("screen", "", $parameters['screen'])))) {
			$params['ui'] = array(
				'label' => _t("Using interface"),
				'value' => $t_ui->getLabelForDisplay()." ➜ ".$t_screen->getLabelForDisplay()
			);
		}
		
		return $params;
	}
	# --------------------------------------------------------------------------------
	# Task processor function - all task queue handlers must implement this
	# 
	# Returns 1 on success, 0 on error
	public function process($parameters) {
		$o_response = new ResponseHTTP();
		$o_request = new RequestHTTP($o_response, array('simulateWith' => $x=array(
				'POST' => $parameters['values'],
				'SCRIPT_NAME' => join('/', [__CA_URL_ROOT__, 'index.php']), 'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => join('/', [__CA_URL_ROOT__, 'index.php', 'batch', 'Editor', 'Save', $parameters['screen'], 'set_id', $parameters['set_id']]), 
				'PATH_INFO' => '/'.join('/', ['batch', 'Editor', 'Save', $parameters['screen'], 'set_id', $parameters['set_id']]),
				'REMOTE_ADDR' => $parameters['ip_address'],
				'HTTP_USER_AGENT' => 'batchEditor',
				'user_id' => $parameters['user_id']
			)
		));
		
		$o_app = AppController::getInstance($o_request, $o_response);
	
		$rs = RecordSelection::restore($parameters['record_selection'], ['request' => $o_request]);
		if (!($t_subject = Datamodel::getInstance($rs->tableName()))) { return false; }
		
		if (isset($parameters['isBatchTypeChange']) && $parameters['isBatchTypeChange']) {
			$report = BatchProcessor::changeTypeBatch($o_request, $parameters['new_type_id'], $rs, $t_subject, ['sendMail' => (bool)$parameters['sendMail'], 'sendSMS' => (bool)$parameters['sendSMS']]);
		} else {
			$report = BatchProcessor::saveBatchEditorForm($o_request, $rs, $t_subject, ['sendMail' => (bool)$parameters['sendMail'], 'sendSMS' => (bool)$parameters['sendSMS']]);
		}
		
		return $report;
	}
	# --------------------------------------------------------------------------------
	# Cancel function - cancels queued task, doing cleanup and deleting task queue record
	# all task queue handlers must implement this
	#
	# Returns 1 on success, 0 on error
	public function cancel($task_id, $parameters) {
		return true;
	}
	# --------------------------------------------------------------------------------
}
