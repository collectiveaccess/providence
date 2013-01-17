<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/maintenance/ExportConfigurationController.php :
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

require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
require_once(__CA_LIB_DIR__."/ca/ConfigurationExporter.php");

class ExportConfigurationController extends ActionController {

	# ------------------------------------------------	
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		if (!$this->request->isLoggedIn()) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 			return;
		}	
	}
	# ------------------------------------------------
	public function Index(){
		$this->render('export_configuration_landing_html.php');
	}
	# ------------------------------------------------
	public function Export(){
		set_time_limit(3600);
		$vs_xml = ConfigurationExporter::exportConfigurationAsXML($this->request->config->get('app_name'), _t('Profile created on %1 by %2', caGetLocalizedDate(), $this->request->user->get('fname').' '.$this->request->user->get('lname')), 'base', '');
		
		$this->view->setVar('profile', $vs_xml);
		$this->view->setVar('profile_file_name', $this->request->config->get('app_name').'_config.xml');
		$this->render('export_configuration_binary.php');
		
		return;
	}
	# ------------------------------------------------
}
?>