<?php
/** ---------------------------------------------------------------------
 * app/lib/BatchMetadataImportProgress.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2020 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Controller/AppController/AppControllerPlugin.php');

class BatchMetadataImportProgress extends AppControllerPlugin {
	# -------------------------------------------------------
	/**
	 * Current request
	 */
	private $request;
	
	/**
	 * Import options
	 */
	private $options;
	# -------------------------------------------------------
	public function __construct($request, $options=null) {
		$this->request = $request;
		$this->options = is_array($options) ? $options : [];
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
			set_time_limit(3600*72); // if it takes more than 72 hours we're in trouble
			
			$va_errors = BatchProcessor::importMetadata(
				$req, 
				$this->options['sourceFile'],
				$this->options['importer_id'],
				$this->options['inputFormat'],
				array_merge($this->options, ['originalFilename' => $this->options['sourceFileName'], 'progressCallback' => 'caIncrementBatchMetadataImportProgress', 'reportCallback' => 'caUpdateBatchMetadataImportResultsReport'])
			);
		}
	}	
	# -------------------------------------------------------
}
