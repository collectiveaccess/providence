<?php
/* ----------------------------------------------------------------------
 * app/plugins/ULAN/controllers/ImportController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Plugins/InformationService/ULAN.php');
require_once(__CA_MODELS_DIR__.'/ca_data_importers.php');
require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');


class ImportController extends ActionController {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $opo_config;		// plugin configuration file

	# -------------------------------------------------------
	# Constructor
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		// Set view path for plugin views directory
		if (!is_array($pa_view_paths)) { $pa_view_paths = array(); }
		$pa_view_paths[] = __CA_APP_DIR__."/plugins/ULAN/themes/".__CA_THEME__."/views";

		// Load plugin configuration file
		$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/ULAN/conf/ulan.conf');

		parent::__construct($po_request, $po_response, $pa_view_paths);

		if (!$this->request->user->canDoAction('can_import_ulan')) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}

		// Load plugin stylesheet
		MetaTagManager::addLink('stylesheet', __CA_URL_ROOT__."/app/plugins/ULAN/themes/".__CA_THEME__."/css/ULAN.css",'text/css');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Index() {
		if (!$this->request->user->canDoAction('can_import_ulan')) { return; }

		$this->view->setVar('importer_list', $va_importer_list = ca_data_importers::getImporters(null, array('formats' => array('ULAN'))));

		$va_importer_options = array();
		foreach($va_importer_list as $vn_importer_id => $va_importer_info) {
			$va_importer_options[$va_importer_info['label'].' (creates '.($va_importer_info['type_for_display'] ? $va_importer_info['type_for_display'].' ' : '').$va_importer_info['importer_type'].')'] = $vn_importer_id;
		}
		$this->view->setVar('importer_list', $va_importer_options);
		$this->view->setVar('importer_list_select', caHTMLSelect('importer_id', $va_importer_options, array()));
		$this->view->setVar('log_level', $this->request->user->getVar('ulan_log_level'));

		$this->render("import_settings_html.php");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Run() {
		if (!$this->request->user->canDoAction('can_import_ulan')) { return; }

		$pa_ulan_ids = $this->request->getParameter('ULANID', pArray);
		$pn_importer_id = $this->request->getParameter('importer_id', pInteger);
		$vs_job_id = md5('U'.$this->request->getUserID().'_'.$pn_importer_id.'_'.join(';', $pa_ulan_ids).'_'.uniqid('', true));

		$this->view->setVar('importer_id', $pn_importer_id);
		$this->view->setVar('job_id', $vs_job_id);
		$this->view->setVar('ulan_ids', $pa_ulan_ids);

		$this->request->user->setVar('ulan_log_level', $vn_log_level = $this->request->getParameter('log_level', pInteger));

		$this->view->setVar('log_level', $vn_log_level);

		$this->render("import_run_html.php");
	}
	# ------------------------------------------------------------------
	# Ajax
	# ------------------------------------------------------------------
	/**
	 * Ajax-invoked execution of import process. This is where the import is actually run.
	 */
	public function RunImport() {
		if (!$this->request->user->canDoAction('can_import_ulan')) { return; }

		$pa_ulan_ids = $this->request->getParameter('ULANID', pArray);

		if(!is_array($pa_ulan_ids) || sizeof($pa_ulan_ids) < 1) { return; }

		$pn_importer_id = $this->request->getParameter('importer_id', pInteger);
		$ps_job_id = $this->request->getParameter('job_id', pString);
		$pn_log_level = $this->request->getParameter('log_level', pInteger);

		$o_progress = new ProgressBar('WebUI', 0, $ps_job_id);
		$o_progress->setJobID($ps_job_id);
		$o_progress->setMode('WebUI');
		$o_progress->setTotal(sizeof($pa_ulan_ids));

		$vn_status = ca_data_importers::importDataFromSource(join(",", $pa_ulan_ids), $pn_importer_id, array('progressBar' => $o_progress, 'format' => 'ULAN', 'logLevel' => $pn_log_level));

		$this->view->setVar('info', array(
			'status' => $vn_status,
			'job_id' => $ps_job_id,
			'importer_id' => $pn_importer_id,
			'ulan_ids' => $pa_ulan_ids
		));

		$this->render('import_run_json.php');
	}
	# ------------------------------------------------------------------
	/**
	 * Return via Ajax current status of running import job
	 */
	public function GetImportStatus() {
		if (!$this->request->user->canDoAction('can_import_ulan')) { return; }

		$ps_job_id = $this->request->getParameter('job_id', pString);
		$o_progress = new ProgressBar('WebUI', null, $ps_job_id);

		$va_data = $o_progress->getDataForJobID();
		$va_data['elapsedTime'] = caFormatInterval(time()-$va_data['start']);

		$this->view->setVar('info', $va_data);
		$this->render('import_run_json.php');
	}
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Lookup() {
		if (!$this->request->user->canDoAction('can_import_ulan')) { return; }

		$o_wc = new WLPlugInformationServiceULAN();

		$this->view->setVar('results', $o_wc->lookup(
			array(),
			$this->request->getParameter('term', pString),
			array(
				'start' => (int)$this->request->getParameter('start', pInteger),
				'count' => (int)$this->request->getParameter('count', pInteger)
			)
		));

		$this->render("ajax_ulan_lookup_json.php");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Detail() {
		if (!$this->request->user->canDoAction('can_import_ulan')) { return; }

		$o_wc = new WLPlugInformationServiceULAN();

		$this->view->setVar('detail', $o_wc->getExtendedInformation(array(), $this->request->getParameter('url', pString)));

		$this->render("ajax_ulan_detail_json.php");
	}
	# -------------------------------------------------------
}
