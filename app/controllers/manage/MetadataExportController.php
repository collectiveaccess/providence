<?php
/** ---------------------------------------------------------------------
 * app/controllers/manage/MetadataExportController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 *
 */

require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");
require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
require_once(__CA_MODELS_DIR__."/ca_sets.php");
require_once(__CA_MODELS_DIR__."/ca_data_exporters.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__."/ca/BatchProcessor.php");
require_once(__CA_LIB_DIR__."/ca/BatchMetadataExportProgress.php");


class MetadataExportController extends ActionController {
	# -------------------------------------------------------
	protected $opo_datamodel;
	protected $opo_app_plugin_manager;
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		AssetLoadManager::register('bundleableEditor');
		AssetLoadManager::register('panel');

		parent::__construct($po_request, $po_response, $pa_view_paths);

		$this->opo_datamodel = Datamodel::load();
		$this->opo_app_plugin_manager = new ApplicationPluginManager();
	}
	# -------------------------------------------------------
	/**
	 * List
	 *
	 * @param array $pa_values An optional array of values to preset in the format, overriding any existing values in the model of the record being editing.
	 * @param array $pa_options Array of options passed through to _initView
	 *
	 */
	public function Index($pa_values=null, $pa_options=null) {
		AssetLoadManager::register('tableList');
		AssetLoadManager::register('fileupload');

		$va_exporters = ca_data_exporters::getExporters();
		$this->view->setVar('exporter_list', $va_exporters);
		$this->render('export/exporter_list_html.php');
	}
	# -------------------------------------------------------
	public function UploadExporters() {
		$va_response = array('uploadMessage' => '', 'skippedMessage' => '');
		foreach($_FILES as $va_file) {
			foreach($va_file['name'] as $vn_i => $vs_name) {
				file_put_contents("/tmp/uploadExp", print_r($va_file,true) ,FILE_APPEND);
				if ($t_importer = ca_data_exporters::loadExporterFromFile($va_file['tmp_name'][$vn_i], $va_errors)) {
					$va_response['copied'][$vs_name] = true;
				} else {
					$va_response['skipped'][$vs_name] = true;
				}
			}
		}

		$va_response['uploadMessage'] = (($vn_upload_count = sizeof($va_response['copied'])) == 1) ? _t('Uploaded %1 worksheet', $vn_upload_count) : _t('Uploaded %1 worksheets', $vn_upload_count);
		if (is_array($va_response['skipped']) && ($vn_skip_count = sizeof($va_response['skipped'])) && !$va_response['error']) {
			$va_response['skippedMessage'] = ($vn_skip_count == 1) ? _t('Skipped %1 worksheet', $vn_skip_count) : _t('Skipped %1 worksheet', $vn_skip_count);
		}

		$this->view->setVar('response', $va_response);
		$this->render('export/file_upload_response_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Export list/set of records via Batch processor
	 */
	public function ExportData() {
		// Can user batch export?
		if (!$this->getRequest()->user->canDoAction('can_batch_export_metadata')) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3440?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$t_exporter = $this->getExporterInstance();
		$t_subject = $t_exporter->getAppDatamodel()->getInstanceByTableNum($t_exporter->get('table_num'), true);

		// Can user export records of this type?
		if (!$this->getRequest()->user->canDoAction('can_export_'.$t_subject->tableName())) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3430?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$this->view->setVar("t_subject", $t_subject);

		// run now
		$app = AppController::getInstance();
		$app->registerPlugin(new BatchMetadataExportProgress($this->getRequest()));

		$this->render('export/export_results_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Export single record (usually via inspector)
	 */
	public function ExportSingleData() {
		$t_exporter = $this->getExporterInstance();

		if(!$t_exporter->getPrimaryKey()) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3420?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$t_subject = $t_exporter->getAppDatamodel()->getInstanceByTableNum($t_exporter->get('table_num'), true);

		// Can user export records of this type?
		if (!$this->getRequest()->user->canDoAction('can_export_'.$t_subject->tableName())) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3430?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$va_errors = ca_data_exporters::checkMapping($t_exporter->get('exporter_code'));
		if(is_array($va_errors) && (sizeof($va_errors)>0)){
			$this->view->setVar("errors",$va_errors);
		} else {
			set_time_limit(3600);

			$o_config = $t_subject->getAppConfig();

			$vn_id = $this->getRequest()->getParameter('item_id', pInteger);
			$this->view->setVar("t_subject", $t_subject);

			// alternate destinations
			$va_alt_dest = $o_config->getAssoc('exporter_alternate_destinations');
			$this->view->setVar('exporter_alternate_destinations', $va_alt_dest);

			// filename set via request wins
			$vs_filename = $this->getRequest()->getParameter('file_name', pString);

			// else run template from config
			if(!$vs_filename && ($vs_export_filename_template = $o_config->get($t_subject->tableName()."_single_item_export_filename"))) {
				if($vs_filename = caProcessTemplateForIDs($vs_export_filename_template, $t_subject->tableNum(), array($vn_id))) {
					// processed template comes without file extension
					$vs_filename = $vs_filename.'.'.$t_exporter->getFileExtension();
				}
			}

			// still no filename? use hardcoded default
			if(!$vs_filename) { $vs_filename = $vn_id.'.'.$t_exporter->getFileExtension(); }

			// pass to view
			$this->view->setVar('file_name', $vs_filename);

			// Can user read this particular item?
			if(!caCanRead($this->getRequest()->getUserID(), $t_exporter->get('table_num'), $vn_id)) {
				$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/2320?r='.urlencode($this->getRequest()->getFullUrlPath()));
				return;
			}

			$this->view->setVar('item_id',$vn_id);

			// do export and dump into tmp file
			$vs_export = ca_data_exporters::exportRecord($t_exporter->get('exporter_code'), $vn_id, array('singleRecord' => true));
			$this->view->setVar("export", $vs_export);

			$vs_tmp_file = tempnam(__CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp', 'singleItemExport');
			file_put_contents($vs_tmp_file, $vs_export);

			// Store file name and type in session for later retrieval. We don't want to have to pass that on through a bunch of requests.
			$o_session = $this->getRequest()->getSession();
			$o_session->setVar('export_file', $vs_tmp_file);
			$o_session->setVar('export_content_type', $t_exporter->getContentType());

			// show destination screen if configured and if we didn't just come here from there
			if($o_config->get('exporter_show_destination_screen')) {
				$this->render('export/export_destination_html.php');
				return;
			}
		}

		$this->render('export/export_single_results_html.php');
	}
	# -------------------------------------------------------
	public function ProcessDestination() {
		$o_config = Configuration::load();
		$va_alt_dest = $o_config->getAssoc('exporter_alternate_destinations');
		$this->view->setVar('exporter_alternate_destinations', $va_alt_dest);

		$vs_filename = $this->getRequest()->getParameter('file_name', pString);
		$this->view->setVar('file_name', $vs_filename);

		$o_session = $this->getRequest()->getSession();
		if(!($vs_tmp_file = $o_session->getVar('export_file'))) {
			return;
		}
		if(!($vs_content_type = $o_session->getVar('export_content_type'))) {
			return;
		}

		$this->view->setVar('export_file', $vs_tmp_file);
		$this->view->setVar('export_content_type', $vs_content_type);

		$vs_dest_code = $this->getRequest()->getParameter('destination', pString);

		// catch plain old download
		if($vs_dest_code == 'file_download') {
			$this->render('export/download_export_binary.php');
			return;
		}

		// other destination
		$vb_success = false;
		if(is_array($va_alt_dest) && sizeof($va_alt_dest)>0) {
			if(is_array($va_alt_dest[$vs_dest_code])) {
				$va_dest = $va_alt_dest[$vs_dest_code];
				// github is the only type we support atm
				if(!isset($va_dest['type']) || ($va_dest['type'] != 'github')) { return; }
				if(!isset($va_dest['display']) || !$va_dest['display']) { $va_dest['display'] = "???"; }
				$this->view->setVar('dest_display_name', $va_dest['display']);

				if(caUploadFileToGitHub(
					$va_dest['username'], $va_dest['token'], $va_dest['owner'], $va_dest['repository'],
					preg_replace('!/+!','/', $va_dest['base_dir'].'/'.$vs_filename),
					$vs_tmp_file, $va_dest['branch'], (bool)$va_dest['update_existing']
				)) {
					$vb_success = true;
				}
			}
		}

		$this->view->setVar('alternate_destination_success', $vb_success);

		$this->render('export/download_feedback_html.php');
	}
	# -------------------------------------------------------
	public function Delete() {
		$t_exporter = $this->getExporterInstance();
		if ($this->getRequest()->getParameter('confirm', pInteger)) {
			$t_exporter->setMode(ACCESS_WRITE);
			$t_exporter->delete(true);

			if ($t_exporter->numErrors()) {
				foreach ($t_exporter->errors() as $o_e) {
					$this->getRequest()->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			} else {
				$this->notification->addNotification(_t("Deleted importer"), __NOTIFICATION_TYPE_INFO__);
			}

			$this->Index();
			return;
		} else {
			$this->render('export/exporter_delete_html.php');
		}
	}
	# -------------------------------------------------------
	/**
	 * Process export generated by ExportData action
	 */
	public function DownloadExport(){
		$ps_file = trim($this->getRequest()->getParameter('file',pString));

		if(!($t_exporter = $this->getExporterFromFileParameter())) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3420?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$t_subject = $t_exporter->getAppDatamodel()->getInstanceByTableNum($t_exporter->get('table_num'), true);
		$o_conf = $t_subject->getAppConfig();
		$vs_file = __CA_APP_DIR__.'/tmp/'.$ps_file;

		$this->view->setVar('file', $vs_file);
		$this->view->setVar('file_base', $ps_file);
		$this->view->setVar('extension',$t_exporter->getFileExtension());
		$this->view->setVar('content_type',$t_exporter->getContentType());
		$this->view->setVar('t_exporter', $t_exporter);

		// filename set via request wins
		$vs_filename = $this->getRequest()->getParameter('file_name', pString);

		// otherwise get from config file
		if(!$vs_filename){
			if($vs_filename = $o_conf->get($t_subject->tableName()."_batch_export_filename")) {
				// config setting comes without file extension
				$vs_filename = $vs_filename.'.'.$t_exporter->getFileExtension();
			}
		}

		// still no filename? -> go for hardcoded default
		if(!$vs_filename) { $vs_filename = 'batch_export.'.$t_exporter->getFileExtension(); }

		// pass to view
		$this->view->setVar('file_name', $vs_filename);

		// go to export destination screen if configured and if we didn't just come here from there
		if($o_conf->get('exporter_show_destination_screen')) {
			if(!$this->getRequest()->getParameter('exportDestinationsSet', pInteger)) {
				$this->render('export/export_destination_html.php');
				return;
			}
		}

		// deal with alternate destinations before rendering download screen
		$va_alt_dest = $o_conf->getAssoc('exporter_alternate_destinations');
		$this->view->setVar('exporter_alternate_destinations', $va_alt_dest);

		if(is_array($va_alt_dest) && sizeof($va_alt_dest)>0) {
			foreach($va_alt_dest as $va_alt) {
				// github is the only type we support atm
				if(!isset($va_alt['type']) || ($va_alt['type'] != 'github')) { continue; }

				@caUploadFileToGitHub(
					$va_alt['username'], $va_alt['token'], $va_alt['owner'], $va_alt['repository'],
					preg_replace('!/+!','/', $va_alt['base_dir']."/".$vs_filename),
					$vs_file, $va_alt['branch'], (bool)$va_alt['update_existing']
				);
			}
		}

		$this->render('export/download_batch_html.php');
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function getExporterInstance($pb_set_view_vars=true, $pn_exporter_id=null) {
		if (!($vn_exporter_id = $this->getRequest()->getParameter('exporter_id', pInteger))) {
			$vn_exporter_id = $pn_exporter_id;
		}
		$t_exporter = new ca_data_exporters($vn_exporter_id);
		if ($pb_set_view_vars){
			$this->view->setVar('exporter_id', $vn_exporter_id);
			$this->view->setVar('t_exporter', $t_exporter);
		}
		return $t_exporter;
	}
	# ------------------------------------------------------------------
	/**
	 * @return ca_data_exporters|null
	 */
	private function getExporterFromFileParameter() {
		$ps_file = trim($this->getRequest()->getParameter('file',pString));
		$va_matches = array();
		if($ps_file && preg_match("/^([0-9]+)\_[0-9a-f]{32,32}$/", $ps_file, $va_matches)) {
			if(file_exists(__CA_APP_DIR__.'/tmp/'.$ps_file)) {
				if($va_matches[1]) {
					$t_exporter = new ca_data_exporters($va_matches[1]);
					if($t_exporter->getPrimaryKey()) {
						return $t_exporter;
					}
				}
			}
		}
		return null;
	}
	# ------------------------------------------------------------------
	# Sidebar info handler
	# ------------------------------------------------------------------
	/**
	 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
	 *
	 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
	 */
	public function Info($pa_parameters) {
		if(($this->getRequest()->getAction()=="Index") || ($this->getRequest()->getAction()=="Delete")){
			$t_exporter = $this->getExporterInstance(false);
			$this->view->setVar('t_item', $t_exporter);
			$this->view->setVar('exporter_count', ca_data_exporters::getExporterCount());

			return $this->render('export/widget_exporter_list_html.php', true);
		} else {
			$t_exporter = $this->getExporterInstance();
			$this->view->setVar('t_item', $t_exporter);
			return $this->render('export/widget_exporter_info_html.php', true);
		}
	}
	# ------------------------------------------------------------------
}