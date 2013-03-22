<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BatchMetadataExportProgress.php : AppController plugin to add page shell around content
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 	require_once(__CA_LIB_DIR__.'/core/Controller/AppController/AppControllerPlugin.php');
 
	class BatchMetadataExportProgress extends AppControllerPlugin {
		# -------------------------------------------------------
		
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
			// Do export
			//
			
			if ($req->isLoggedIn()) {
				set_time_limit(3600*24); // if it takes more than 24 hours we're in trouble
				$vn_id = $req->getParameter('exporter_id',pInteger);
				$vs_search = $req->getParameter('search',pString);

				$t_exporter = new ca_data_exporters($vn_id);

				$vs_file = tempnam(caGetTempDirPath(), 'export');
				ca_data_exporters::exportRecordsFromSearchExpression($t_exporter->get('exporter_code'), $vs_search, $vs_file, array('request' => $req, 'progressCallback' => 'caIncrementBatchMetadataExportProgress'));
			}

			// export done, move file to application tmp dir and create download link (separate action in the export controller)
			if(filesize($vs_file)){
				$vs_new_filename = $vn_id."_".md5($vs_file);
				rename($vs_file, __CA_APP_DIR__.'/tmp/'.$vs_new_filename);

				caExportAddDownloadLink($req,$vs_new_filename);
			}
		}
		# -------------------------------------------------------
	}
?>