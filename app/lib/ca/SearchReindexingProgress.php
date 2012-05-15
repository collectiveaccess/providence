<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/SearchReindexingProgress.php : AppController plugin to add page shell around content
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
 
 	require_once(__CA_LIB_DIR__.'/core/Controller/AppController/AppControllerPlugin.php');
 
	class SearchReindexingProgress extends AppControllerPlugin {
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
			// Do reindexing
			//
			
			if ($req->isLoggedIn() && $req->user->canDoAction('can_do_search_reindex')) {
				set_time_limit(3600*8);
				$o_si = new SearchIndexer();
				$o_si->reindex(null, array('showProgress' => false, 'interactiveProgressDisplay' => false, 'callback' => "caIncrementSearchReindexProgress"));
			}
		}	
		# -------------------------------------------------------
	}
?>