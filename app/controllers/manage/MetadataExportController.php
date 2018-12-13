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
require_once(__CA_LIB_DIR__."/Datamodel.php");
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__."/BatchProcessor.php");
require_once(__CA_LIB_DIR__."/BatchMetadataExportProgress.php");


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

		$this->opo_app_plugin_manager = new ApplicationPluginManager();

		$this->cleanOldExportFilesFromTmpDir();
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
		$this->getView()->setVar('exporter_list', $va_exporters);
		$this->render('export/exporter_list_html.php');
	}
	# -------------------------------------------------------
	public function UploadExporters() {
		$va_response = array('uploadMessage' => '', 'skippedMessage' => '');
		foreach($_FILES as $va_file) {
			foreach($va_file['name'] as $vn_i => $vs_name) {
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

		$this->getView()->setVar('response', $va_response);
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
		$t_subject = Datamodel::getInstanceByTableNum($t_exporter->get('table_num'), true);

		// Can user export records of this type?
		if (!$this->getRequest()->user->canDoAction('can_export_'.$t_subject->tableName())) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3430?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$this->getView()->setVar("t_subject", $t_subject);

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

		$t_subject = Datamodel::getInstanceByTableNum($t_exporter->get('table_num'), true);

		// Can user export records of this type?
		if (!$this->getRequest()->user->canDoAction('can_export_'.$t_subject->tableName())) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3430?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$va_errors = ca_data_exporters::checkMapping($t_exporter->get('exporter_code'));
		if(is_array($va_errors) && (sizeof($va_errors)>0)){

			$this->getView()->setVar("errors",$va_errors);
			$this->render('export/export_errors_html.php');

		} else {
			set_time_limit(3600);

			$o_config = $t_subject->getAppConfig();

			$vn_id = $this->getRequest()->getParameter('item_id', pInteger);
			$this->getView()->setVar("t_subject", $t_subject);

			// alternate destinations
			$va_alt_dest = $o_config->getAssoc('exporter_alternate_destinations');
			$this->getView()->setVar('exporter_alternate_destinations', $va_alt_dest);

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

			// pass to view as default value for form field
			$this->getView()->setVar('file_name', $vs_filename);

			// Can user read this particular item?
			if(!caCanRead($this->getRequest()->getUserID(), $t_exporter->get('table_num'), $vn_id)) {
				$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/2320?r='.urlencode($this->getRequest()->getFullUrlPath()));
				return;
			}

			$this->getView()->setVar('item_id',$vn_id);

			// do item export and dump into tmp file
			$vs_export = ca_data_exporters::exportRecord($t_exporter->get('exporter_code'), $vn_id, array('singleRecord' => true));
			$this->getView()->setVar("export", $vs_export);

			$vs_tmp_file = tempnam(__CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp', 'dataExport');
			file_put_contents($vs_tmp_file, $vs_export);

			// Store file name and exporter data in session for later retrieval. We don't want to have to pass that on through a bunch of requests.
			Session::setVar('export_file', $vs_tmp_file);
			Session::setVar('export_content_type', $t_exporter->getContentType());
			Session::setVar('exporter_id', $t_exporter->getPrimaryKey());

			$this->render('export/export_destination_html.php');
		}
	}
	# -------------------------------------------------------
	public function ProcessDestination() {
		$o_config = Configuration::load();
		$va_alt_dest = $o_config->get('exporter_alternate_destinations');
		$this->getView()->setVar('exporter_alternate_destinations', $va_alt_dest);

		$vs_filename = $this->getRequest()->getParameter('file_name', pString);
		$this->getView()->setVar('file_name', $vs_filename);

		if(!($vs_tmp_file = Session::getVar('export_file')) && !($vs_tmp_data = Session::getVar('export_data'))) {
			return; //@todo error handling
		}
		if(!($vs_content_type = Session::getVar('export_content_type'))) {
			return; // @todo error handling
		}

		$this->getView()->setVar('export_file', $vs_tmp_file);
		$this->getView()->setVar('export_content_type', $vs_content_type);

		$vs_dest_code = $this->getRequest()->getParameter('destination', pString);
		// catch plain old file download request and download as binary
		if($vs_dest_code == 'file_download') {
			$this->render('export/download_export_binary.php');
			return;
		}

		// other destination
		$vb_success = false;
		if(is_array($va_alt_dest) && sizeof($va_alt_dest)>0) {
			if(is_array($va_alt_dest[$vs_dest_code])) {
				$va_dest = $va_alt_dest[$vs_dest_code];
				// Github and ResourceSpace accepted formats
				if(isset($va_dest['type']) && ($va_dest['type'] == 'github')) {
					if(!isset($va_dest['display']) || !$va_dest['display']) { $va_dest['display'] = "???"; }
					$this->getView()->setVar('dest_display_name', $va_dest['display']);

					if(isset($va_dest['base_dir']) && strlen($va_dest['base_dir'])>0) {
						$vs_git_path = preg_replace('!/+!','/', $va_dest['base_dir'].'/'.$vs_filename);
					} else {
						$vs_git_path = $vs_filename;
					}

					if(caUploadFileToGitHub(
						$va_dest['username'], $va_dest['token'], $va_dest['owner'], $va_dest['repository'],
						$vs_git_path, $vs_tmp_file, $va_dest['branch'], (bool)$va_dest['update_existing']
					)) {
						$vb_success = true;
					}
				}
				if(isset($va_dest['type']) && ($va_dest['type'] == 'ResourceSpace')) {
					if(!isset($va_dest['display']) || !$va_dest['display']) { $va_dest['display'] = "???"; }
					$this->getView()->setVar('dest_display_name', $va_dest['display']);

					$vb_success = caExportDataToResourceSpace($va_dest['user'], $va_dest['api_key'], $va_dest['base_api_url'], $vs_tmp_file);
				}

			}
		}

		$this->getView()->setVar('alternate_destination_success', $vb_success);
		if (file_exists($vs_tmp_file)) { unlink($vs_tmp_file); }
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
	 * Prepare export generated by ExportData action
	 */
	public function SetupBatchExport() {
		$o_conf = Configuration::load();

		if(!($vn_exporter_id = Session::getVar('exporter_id'))) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3420?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$t_exporter = new ca_data_exporters($vn_exporter_id);
		if(!$t_exporter->getPrimaryKey()) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/3420?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$t_subject = Datamodel::getInstanceByTableNum($t_exporter->get('table_num'), true);

		// alternate destinations
		$va_alt_dest = $o_conf->getAssoc('exporter_alternate_destinations');
		$this->getView()->setVar('exporter_alternate_destinations', $va_alt_dest);

		// filename set via request wins
		$vs_filename = $this->getRequest()->getParameter('file_name', pString);

		// otherwise get from config file
		if(!$vs_filename) {
			if($vs_filename = $o_conf->get($t_subject->tableName()."_batch_export_filename")) {
				// config setting comes without file extension
				$vs_filename = $vs_filename.'.'.$t_exporter->getFileExtension();
			}
		}

		// still no filename? -> go for hardcoded default
		if(!$vs_filename) { $vs_filename = 'batch_export.'.$t_exporter->getFileExtension(); }

		// pass to view
		$this->getView()->setVar('file_name', $vs_filename);

		$this->render('export/export_destination_html.php');
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
			$this->getView()->setVar('exporter_id', $vn_exporter_id);
			$this->getView()->setVar('t_exporter', $t_exporter);
		}
		return $t_exporter;
	}
	# ------------------------------------------------------------------
	/**
	 * Cleans up temporary export files older than an hour
	 * By then everybody should have gotten everything they need from the export dest screen
	 */
	private function cleanOldExportFilesFromTmpDir() {
		$va_tmp_dir_contents = caGetDirectoryContentsAsList(__CA_APP_DIR__.DIRECTORY_SEPARATOR.'tmp', false);
		foreach($va_tmp_dir_contents as $vs_file) {
			if(preg_match("/^dataExport/", basename($vs_file))) {
				if((time() - filemtime($vs_file)) > 60*60) {
					@unlink($vs_file);
				}
			}
		}
	}
	# ------------------------------------------------------------------
	# Sidebar info handler
	# ------------------------------------------------------------------
	/**
	 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
	 *
	 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
	 * @return string rendered view ready for display
	 */
	public function Info($pa_parameters) {
		if(($this->getRequest()->getAction()=="Index") || ($this->getRequest()->getAction()=="Delete")){
			$t_exporter = $this->getExporterInstance(false);
			$this->getView()->setVar('t_item', $t_exporter);
			$this->getView()->setVar('exporter_count', ca_data_exporters::getExporterCount());

			return $this->render('export/widget_exporter_list_html.php', true);
		} else {
			$t_exporter = $this->getExporterInstance();
			$this->getView()->setVar('t_item', $t_exporter);
			return $this->render('export/widget_exporter_info_html.php', true);
		}
	}
	# ------------------------------------------------------------------
}
