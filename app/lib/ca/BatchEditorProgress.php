<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BatchEditorProgress.php : 
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  * Implements reindexing of search indices invoked via the web UI
  * This application dispatcher plugin ensures that the indexing starts
  * after the web UI page has been sent to the client
  */
 
 	require_once(__CA_LIB_DIR__.'/core/Controller/AppController/AppControllerPlugin.php');
 	require_once(__CA_LIB_DIR__.'/ca/BatchProcessor.php');
 
	class BatchEditorProgress extends AppControllerPlugin {
		# -------------------------------------------------------
		private $request;
		private $ot_set;
		private $ot_subject;
		private $opa_options;
		# -------------------------------------------------------
		public function __construct($po_request, $t_set, $t_subject, $pa_options=null) {
			$this->request = $po_request;
			$this->ot_set = $t_set;
			$this->ot_subject = $t_subject;
			$this->opa_options = is_array($pa_options) ? $pa_options : array();
		}
		# -------------------------------------------------------
		public function dispatchLoopShutdown() {	
			//
			// Force output to be sent - we need the client to have the page before
			// we start flushing progress bar updates
			//	
			$app = AppController::getInstance();
			$req = $app->getRequest();
			$resp = $app->getResponse();
			$resp->sendResponse();
			$resp->clearContent();
			
			//
			// Do batch processing
			//
			if ($req->isLoggedIn()) {
				set_time_limit(3600*24); // if it takes more than 24 hours we're in trouble
				
				$va_errors = BatchProcessor::saveBatchEditorFormForSet($this->request, $this->ot_set, $this->ot_subject, array_merge($this->opa_options, array('progressCallback' => 'caIncrementBatchEditorProgress', 'reportCallback' => 'caCreateBatchEditorResultsReport')));
			}
		}	
		# -------------------------------------------------------
	}
?>