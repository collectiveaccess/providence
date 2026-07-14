<?php
/* ----------------------------------------------------------------------
 * app/plugins/helpMenu/controllers/ShowController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2023 Whirl-i-Gig
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
class ShowController extends ActionController {
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		// Set view path for plugin views directory
		if (!is_array($pa_view_paths)) { $pa_view_paths = array(); }
		$pa_view_paths[] = __CA_APP_DIR__."/plugins/helpMenu/themes/".__CA_THEME__."/views";

		// Load plugin configuration file
		$this->config = Configuration::load(__CA_APP_DIR__.'/plugins/helpMenu/conf/helpMenu.conf');


		parent::__construct($po_request, $po_response, $pa_view_paths);

		// Load plugin stylesheet
		//MetaTagManager::addLink('stylesheet', __CA_URL_ROOT__."/app/plugins/ArtefactsCanada/themes/".__CA_THEME__."/css/ArtefactsCanada.css",'text/css');     
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __call($ps_method, $pa_path) {
		$this->view->setVar('response', $this->response);
		
		$page = new ca_site_pages((int)$this->request->getAction());
		if (!$page->isLoaded()) {
			throw new ApplicationException(_t('Cannot load page'));
		}
		if ($page->get('path') !== 'PROVIDENCE_HELP_MENU') {
			throw new ApplicationException(_t('Is not help menu page'));
		}
		if ($vs_content = $page->render($this, ['incrementViewCount' => true])) {
			$this->response->addContent($vs_content);
			return;
		}
	}
	# -------------------------------------------------------
	public function Info(){
		$current_page = new ca_site_pages($this->request->getAction());

		$this->view->setVar('current_page', $current_page );

		return $this->render('widget_helpmenu_info_html.php',true);

	}
}
