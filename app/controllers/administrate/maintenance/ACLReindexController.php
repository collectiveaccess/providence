<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/maintenance/ACLReindexController.php :
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
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__."/core/Search/SearchEngine.php");
require_once(__CA_LIB_DIR__."/core/Media.php");
require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
require_once(__CA_LIB_DIR__.'/ca/ACLReindexingProgress.php');

class ACLReindexController extends ActionController {

	# ------------------------------------------------	
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		//if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_search_reindex')) {
		//	$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 		//	return;
		//}	
	}
	# ------------------------------------------------
	public function Index(){
		$this->render('acl_reindex_landing_html.php');
	}
	# ------------------------------------------------
	public function Reindex(){
		$this->render('acl_reindex_status_html.php');
	}
	# ------------------------------------------------
}
?>