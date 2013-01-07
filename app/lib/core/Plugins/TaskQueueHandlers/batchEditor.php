<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/TaskQueueHandlers/batchEditor.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ca/BatchProcessor.php');
require_once(__CA_MODELS_DIR__.'/ca_sets.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');
	
	class WLPlugTaskQueueHandlerbatchEditor Extends WLPlug Implements IWLPlugTaskQueueHandler {
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
			return _t("Batch editor background processor");
		}
		# --------------------------------------------------------------------------------
		public function getParametersForDisplay($pa_rec) {
			$va_parameters = caUnserializeForDatabase($pa_rec["parameters"]);
			
			$va_params = array();
			
			$t_set = new ca_sets($va_parameters['set_id']);
			$va_params['importing_from'] = array(
				'label' => _t("Applying batch edits to set"),
				'value' => $t_set->getLabelForDisplay()
			);
			$va_params['number_of_records'] = array(
				'label' => _t("Records to edit"),
				'value' => (int)$t_set->getItemCount(array('user_id' => $va_parameters['user_id']))
			);
			
			$t_ui = new ca_editor_uis($va_parameters['ui_id']);
			
			$t_screen = new ca_editor_ui_screens();
			if ($t_screen->load(array('ui_id' => $t_ui->getPrimaryKey(), 'screen_id' => str_ireplace("screen", "", $va_parameters['screen'])))) {
				$va_params['ui'] = array(
					'label' => _t("Using interface"),
					'value' => $t_ui->getLabelForDisplay()." ➜ ".$t_screen->getLabelForDisplay()
				);
			}
			
			return $va_params;
		}
		# --------------------------------------------------------------------------------
		# Task processor function - all task queue handlers must implement this
		# 
		# Returns 1 on success, 0 on error
		public function process($pa_parameters) {
			
			$o_response = new ResponseHTTP();
			$o_request = new RequestHTTP($o_response, array('simulateWith' => $x=array(
					'POST' => $pa_parameters['values'],
					'SCRIPT_NAME' => join('/', array(__CA_URL_ROOT__, 'index.php')), 'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => join('/', array(__CA_URL_ROOT__, 'index.php', 'batch', 'Editor', 'Save', $pa_parameters['screen'], 'set_id', $pa_parameters['set_id'])), 
					'PATH_INFO' => '/'.join('/', array('batch', 'Editor', 'Save', $pa_parameters['screen'], 'set_id', $pa_parameters['set_id'])),
					'REMOTE_ADDR' => $pa_parameters['ip_address'],
					'HTTP_USER_AGENT' => 'batchEditor',
					'user_id' => $pa_parameters['user_id']
				)
			));
			
			$o_app = AppController::getInstance($o_request, $o_response);
			
			$t_set = new ca_sets($pa_parameters['set_id']);
			$o_dm = Datamodel::load();
			$t_subject = $o_dm->getInstanceByTableNum($t_set->get('table_num'));
			$va_report = BatchProcessor::saveBatchEditorFormForSet($o_request, $t_set, $t_subject, array('sendMail' => (bool)$pa_parameters['sendMail'], 'sendSMS' => (bool)$pa_parameters['sendSMS']));

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