<?php
/** ---------------------------------------------------------------------
 * app/lib/MetadataImportController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2024 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");
require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__."/ResultContext.php");
require_once(__CA_LIB_DIR__."/BatchProcessor.php");
require_once(__CA_LIB_DIR__."/BatchMetadataImportProgress.php");


class MetadataImportController extends ActionController {
	# -------------------------------------------------------
	protected $opo_app_plugin_manager;
	protected $opo_result_context;
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		if (!$po_request->user->canDoAction('can_batch_import_metadata')) {
			$po_response->setRedirect($po_request->config->get('error_display_url').'/n/3400?r='.urlencode($po_request->getFullUrlPath()));
			return;
		}
		
		AssetLoadManager::register('bundleableEditor');
		AssetLoadManager::register('panel');
		
		
		$this->opo_app_plugin_manager = new ApplicationPluginManager();
		$this->opo_result_context = new ResultContext($po_request, $this->ops_table_name, ResultContext::getLastFind($po_request, $this->ops_table_name));
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
	
		$va_importers = ca_data_importers::getImporters();
		$this->view->setVar('importer_list', $va_importers);
		$this->render('metadataimport/importer_list_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 *
	 * 
	 */
	public function Edit() {
		$this->render('metadataimport/importer_edit_html.php');
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	public function UploadImporters() {
		$va_response = array('uploadMessage' => '', 'skippedMessage' => '');
		$va_errors = array();
	
		$vn_upload_count = 0;
		foreach($_FILES as $va_file) {
			if(!is_array($va_file['name'])) {
				$va_file['name'] = [$va_file['name']];
				$va_file['tmp_name'] = [$va_file['tmp_name']];
			}
			foreach($va_file['name'] as $vn_i => $vs_name) {
				if ($t_importer = ca_data_importers::loadImporterFromFile($va_file['tmp_name'][$vn_i], $va_errors, array('logDirectory' => $this->request->config->get('batch_metadata_import_log_directory'), 'logLevel' => KLogger::INFO, 'originalFilename' => $vs_name))) {
					$va_response['copied'][$vs_name] = true;
					$vn_upload_count++;
				} else {
					$va_response['skipped'][$vs_name] = true;
				}
			}
		}
		
		$va_response['uploadMessage'] = (((is_array($va_response['copied']) && sizeof($va_response['copied'])) == 1)) ? _t('Uploaded %1 worksheet', $vn_upload_count) : _t('Uploaded %1 worksheets', $vn_upload_count);
		if (is_array($va_response['skipped']) && ($vn_skip_count = sizeof($va_response['skipped'])) && !$va_response['error']) {
			$va_response['skippedMessage'] = ($vn_skip_count == 1) ? _t('Skipped %1 worksheet', $vn_skip_count) : _t('Skipped %1 worksheet', $vn_skip_count);
		}
		
		$this->view->setVar('response', $va_response);
		$this->render('mediaimport/file_upload_response_json.php');
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	public function Run() {
		AssetLoadManager::register('fileupload');
		
		$t_importer = $this->getImporterInstance();
		
		$this->view->setVar('t_importer', $t_importer);
		$this->view->setVar('last_settings', $va_last_settings = $this->request->user->getVar('batch_metadata_last_settings'));
		
		$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');	
		$o_view->setVar('id', 'fileImportPath');	
		$o_view->setVar('defaultPath', caGetOption('fileImportPath', $va_last_settings, null));
		$this->view->setVar('file_browser', $o_view->render('settings_directory_browser_html.php'));
		
		$this->render('metadataimport/importer_run_html.php');
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	public function ImportData() {
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
			$this->Index();
			return;
		}
		global $g_ui_locale_id;
		$t_importer = $this->getImporterInstance();
		
		if (!$t_subject = Datamodel::getInstanceByTableNum($t_importer->get('table_num'), true)) {
			return $this->Index();
		}
		
		$options = [
			'sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger), 
			'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger), 
			'runInBackground' => (bool)$this->request->getParameter('run_in_background', pInteger),
			
			'locale_id' => $g_ui_locale_id,
			'user_id' => $this->request->getUserID(),
			
			'logLevel' => $this->request->getParameter("logLevel", pInteger),
			'limitLogTo' => $this->request->getParameter("limitLogTo", pArray),
			'dryRun' => $this->request->getParameter("dryRun", pInteger),
			
			'fileInput' => $this->request->getParameter("fileInput", pString), // where data is drawn from (uploaded file/import directory/GoogleDrive)
			'fileImportPath' => $this->request->getParameter("fileImportPath", pString), // path relative to import directory when fileInput='import'
			
			'importAllDatasets' => (bool)$this->request->getParameter("importAllDatasets", pInteger), 
			'originalFilename' => null,
			
			'importer_id' => $this->request->getParameter("importer_id", pInteger),
			'inputFormat' => $this->request->getParameter("inputFormat", pString),
			
			'sourceUrl' => $this->request->getParameter('sourceUrl', pString),
			'sourceText' => $this->request->getParameter('sourceText', pString)
		];

		if ($vs_file_input = $this->request->getParameter("fileInput", pString)) {
			$options['fileInput'] = $vs_file_input; 
		}
		if ($vs_file_import_path = $this->request->getParameter("fileImportPath", pString)) {
			$options['fileImportPath'] = $vs_file_import_path;
		}
		
		switch(strtolower($options['fileInput'])) {
			case 'googledrive':
				if ($google_url = caValidateGoogleSheetsUrl($google_url_orig = $this->request->getParameter('google_drive_url', pString, ['urldecode' => true]))) {
					try {
						$tmp_file = caFetchFileFromUrl($google_url);
					} catch (ApplicationException $e) {
						$this->notification->addNotification($e->getMessage(), __NOTIFICATION_TYPE_ERROR__);
						return $this->Run();
					}
					$options['googleDriveUrl'] = $google_url_orig;
					$options['sourceFile'] = $tmp_file;
					$options['sourceFileName'] = pathinfo($tmp_file, PATHINFO_FILENAME);
				} else {
					$this->notification->addNotification(_t("URL is invalid"), __NOTIFICATION_TYPE_ERROR__);
					return $this->Run();
				}
				break;
			case 'import':	// from import directory
				$base_import_dir = Configuration::load()->get('batch_media_import_root_directory');
				
				$options['sourceFile'] = "{$base_import_dir}/{$options['fileImportPath']}";
				$options['sourceFileName'] = pathinfo($options['sourceFile'], PATHINFO_FILENAME);
				break;
			default:		// directly uploaded file
				if(isset($_FILES['sourceFile']['tmp_name']) && $_FILES['sourceFile']['tmp_name']) {
					$options['sourceFile'] = $_FILES['sourceFile']['tmp_name'];
					$options['sourceFileName'] = $_FILES['sourceFile']['name'] ? $_FILES['sourceFile']['name'] : $_FILES['sourceFile']['tmp_name'];
				} elseif($options['sourceUrl']) {
					$options['sourceFile'] = $options['sourceUrl'];
					$options['sourceFileName'] = null;
				} elseif($options['sourceText']) {
					$options['sourceFile'] = $options['sourceText'];
					$options['sourceFileName'] = null;
				} else {
					$this->notification->addNotification(_t("No data specified"), __NOTIFICATION_TYPE_ERROR__);
					return $this->Run();
				}
				break;
		}
		
		$this->request->user->setVar('batch_metadata_last_settings', $options);

		$this->view->setVar("t_subject", $t_subject);
	
		if ((bool)$this->request->config->get('queue_enabled') && (bool)$this->request->getParameter('run_in_background', pInteger)) { 
			// queue for background processing
			$o_tq = new TaskQueue();
			
			$entity_key = join('-', array($this->request->getUserID(), $options['importFromDirectory'], time(), rand(1,999999)));
			
			if(file_exists($options['sourceFile'])) {
				// Copy upload tmp file to persistent tmp path
				// TaskQueue handle will delete this file when it's done with it
				if($ext = pathinfo($options['sourceFile'], PATHINFO_EXTENSION)) { $ext = '.'.$ext; }
				@copy($options['sourceFile'], $new_path = __CA_APP_DIR__.'/tmp/caMetadataImportTmp_'.$entity_key.$ext);
				$options['sourceFile'] = $new_path;
			}
			
			if (!$o_tq->addTask(
				'metadataImport',
				$options,
				["priority" => 100, "entity_key" => $entity_key, "row_key" => $entity_key, 'user_id' => $this->request->getUserID()]
			)) {
				//$this->postError(100, _t("Couldn't queue batch processing for"),"EditorContro->_processMedia()");	
			}
			$this->render('metadataimport/batch_queued_html.php');
		} else { 
			// run now
			$app = AppController::getInstance();
			$app->registerPlugin(new BatchMetadataImportProgress($this->request, $options));
			$this->render('metadataimport/batch_results_html.php');
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Delete($pa_values=null) {
		$t_importer = $this->getImporterInstance();
		if ($this->request->getParameter('confirm', pInteger)) {
			$t_importer->setMode(ACCESS_WRITE);
			$t_importer->delete(true);

			if ($t_importer->numErrors()) {
				foreach ($t_importer->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			} else {
				$this->notification->addNotification(_t("Deleted importer"), __NOTIFICATION_TYPE_INFO__);
			}

			$this->Index();
			return;
		} else {
			$this->render('metadataimport/importer_delete_html.php');
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	public function Load() {
		if(!($google_url = trim($this->request->getParameter('google_drive_url', pString, ['urlDecode' => true])))) {
			$this->notification->addNotification(_t('No url specified'), __NOTIFICATION_TYPE_ERROR__);
			return $this->Index();
		}
		$google_file = new \CA\MediaUrl();
		if (!$google_file->validate($google_url, ['format' => 'xlsx', 'limit' => ['GoogleDrive']])) {
			$this->notification->addNotification(_t("URL is invalid"), __NOTIFICATION_TYPE_ERROR__);
			return $this->Index();
		}
		
		try {
			$parsed_data = $google_file->fetch($google_url, ['format' => 'xlsx', 'limit' => ['GoogleDrive']]);
			if (!is_array($parsed_data) || !isset($parsed_data['file']) || !($tmp_file = $parsed_data['file'])) {
				$this->notification->addNotification(_t('Could not download data'), __NOTIFICATION_TYPE_ERROR__);
				return $this->Index();
			}
		} catch (UrlFetchException $e) {
			$this->notification->addNotification($e->getMessage(), __NOTIFICATION_TYPE_ERROR__);
			return $this->Index();
		}
		
		$errors = [];
		$is_new = true;
		try {
			$t_importer = ca_data_importers::loadImporterFromFile($tmp_file, $errors, ['logDirectory' => $this->request->config->get('batch_metadata_import_log_directory'), 'logLevel' => KLogger::INFO, 'sourceUrl' => $google_url], $is_new);
		} catch (Exception $e) {
			$t_importer = null; 
			$errors = [_t('Could not read Excel data')];
		}
		if ($t_importer) {
			$this->notification->addNotification($is_new ? _t("Added import worksheet %1", $t_importer->get('importer_code')) : _t("Updated import worksheet %1", $t_importer->get('importer_code')), __NOTIFICATION_TYPE_INFO__);
		} else {
			$this->notification->addNotification(_t("Could not add import worksheet: %1", join("; ", $errors)), __NOTIFICATION_TYPE_ERROR__);
		}
		unlink($tmp_file);
		$this->Index();
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	public function Download() {
		$t_importer = new ca_data_importers();
		if(($vn_importer_id = $this->request->getParameter("importer_id", pInteger)) && $t_importer->load($vn_importer_id) && $t_importer->getFileInfo('worksheet')) {
			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
			$o_view->setVar('archive_path', $t_importer->getFilePath('worksheet'));
			$o_view->setVar('archive_name', ($vs_importer_code = $t_importer->get('importer_code')) ? "{$vs_importer_code}.xlsx" : "Importer_{$vn_importer_id}.xlsx");
			$this->response->addContent($o_view->render('download_file_binary.php'));
			return;
		} else {
			$this->notification->addNotification(_t('Invalid importer'), __NOTIFICATION_TYPE_ERROR__);
			$this->Index();
		}
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function getImporterInstance($pb_set_view_vars=true, $pn_importer_id=null) {
		if (!($vn_importer_id = $this->request->getParameter('importer_id', pInteger))) {
			$vn_importer_id = $pn_importer_id;
		}
		$t_importer = new ca_data_importers($vn_importer_id);
		if ($pb_set_view_vars){
			$this->view->setVar('importer_id', $vn_importer_id);
			$this->view->setVar('t_importer', $t_importer);
		}
		return $t_importer;
	}
	# ------------------------------------------------------------------
	# Sidebar info handler
	# ------------------------------------------------------------------
	/**
	 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
	 *
	 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
	 */
	public function info($pa_parameters) {
		$t_importer = $this->getImporterInstance(false);
		$this->view->setVar('t_item', $t_importer);
		$this->view->setVar('result_context', $this->opo_result_context);
		$this->view->setVar('screen', $this->request->getActionExtra());	
		
		return $this->render('metadataimport/widget_importer_info_html.php', true);
	}
	# ------------------------------------------------------------------
}
