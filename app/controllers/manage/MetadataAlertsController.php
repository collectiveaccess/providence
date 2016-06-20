<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/MetadataAlertsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2015 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/core/Controller/ActionController.php");
require_once(__CA_MODELS_DIR__.'/ca_metadata_alert_rules.php');

class MetadataAlertsController extends ActionController {
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}
	# -------------------------------------------------------
	public function ListAlerts() {
		AssetLoadManager::register('tableList');

		$va_list = ca_metadata_alert_rules::getList($this->getRequest()->getUserID());
		$this->getView()->setVar('rule_list', $va_list);

		$this->render('metadata_alert_list_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Info() {
		return $this->render('widget_metadata_alerts_info_html.php', true);
	}
	# -------------------------------------------------------
}
