<?php
/** ---------------------------------------------------------------------
 * app/lib/BatchMetadataExportProgress.php : AppController plugin to add page shell around content
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Datamodel.php');
require_once(__CA_LIB_DIR__.'/Controller/AppController/AppControllerPlugin.php');

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
		
		if (!$req->isLoggedIn()) {
			return;
		}

		set_time_limit(3600*24); // if it takes more than 24 hours we're in trouble
		$code = $req->getParameter('exporter_id', pString);
		$t_exporter = ca_data_exporters::findAsInstance(['exporter_code' => $code]);

		$file = tempnam(__CA_TEMP_DIR__, 'dataExport');

		// we have 3 different sources for batch exports: search/browse result, sets and search expressions (deprecated)
		// they all operate on different parameters and on different static functions in ca_data_exporters
		if($req->getParameter('caIsExportFromSearchOrBrowseResult', pInteger)) { // batch export from search or browse result
			$find_type = $req->getParameter('find_type',pString);
			$result_context = new ResultContext($req, $t_exporter->getTargetTableName(), $find_type);
			$o_result = caMakeSearchResult($t_exporter->getTargetTableName(), $result_context->getResultList());
			ca_data_exporters::exportRecordsFromSearchResult($t_exporter->get('exporter_code'), $o_result, $file, ['request' => $req, 'progressCallback' => 'caIncrementBatchMetadataExportProgress']);
		} else if($set_id = $req->getParameter('set_id',pInteger)) { // batch export from set
			ca_data_exporters::exportRecordsFromSet($t_exporter->get('exporter_code'), $set_id, $file, ['request' => $req, 'progressCallback' => 'caIncrementBatchMetadataExportProgress']);
		} else { // batch export from search expression (deprecated)
			$search = $req->getParameter('search',pString);
			ca_data_exporters::exportRecordsFromSearchExpression($t_exporter->get('exporter_code'), $search, $file, ['request' => $req, 'progressCallback' => 'caIncrementBatchMetadataExportProgress']);
		}

		// export done, record it in session for later usage in download/destination action
		if(filesize($file)){
			Session::setVar('export_file', $file);
			Session::setVar('export_content_type', $t_exporter->getContentType());
			Session::setVar('exporter_id', $t_exporter->getPrimaryKey());

			caExportAddDownloadLink($req);
		}
	}
	# -------------------------------------------------------
}
