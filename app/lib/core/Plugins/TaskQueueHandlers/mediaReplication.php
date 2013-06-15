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