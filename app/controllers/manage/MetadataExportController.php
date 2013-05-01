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
 			
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('panel');
 			
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
			JavascriptLoadManager::register('tableList');
			JavascriptLoadManager::register('fileupload');
		
 			$va_exporters = ca_data_exporters::getExporters();
 			$this->view->setVar('exporter_list', $va_exporters);
 			$this->render('export/exporter_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function UploadExporters() {
 			$va_response = array('uploadMessage' => '', 'skippedMessage' => '');
				foreach($_FILES as $vs_param => $va_file) {
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
 		public function Run() {
 			$t_exporter = $this->getExporterInstance();
 			
 			$this->view->setVar('t_exporter', $t_exporter);
 			
			$this->render('export/exporter_run_html.php');
 		}
 		# -------------------------------------------------------
 		public function ExportData() {
 			// Can user batch export?
 			if (!$this->request->user->canDoAction('can_batch_export_metadata')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3210?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}

 			$t_exporter = $this->getExporterInstance();
 			
 			$this->view->setVar("t_subject", $t_exporter->getAppDatamodel()->getInstanceByTableNum($t_exporter->get('table_num'), true));
 			
			// run now
			$app = AppController::getInstance();
			$app->registerPlugin(new BatchMetadataExportProgress($this->request));

			$this->render('export/export_results_html.php');
 		}
 		# -------------------------------------------------------
 		public function ExportSingleData() { 	
 			$t_exporter = $this->getExporterInstance();
 			$t_subject = $t_exporter->getAppDatamodel()->getInstanceByTableNum($t_exporter->get('table_num'), true);

 			// Can user export records of this type?
 			if (!$this->request->user->canDoAction('can_export_'.$t_subject->tableName())) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3210?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}

 			$va_errors = ca_data_exporters::checkMapping($t_exporter->get('exporter_code'));
 			if(is_array($va_errors) && (sizeof($va_errors)>0)){
 				$this->view->setVar("errors",$va_errors);
 			} else {
 				set_time_limit(3600);

	 			$this->view->setVar("t_subject", $t_subject);
	 			$vn_id = $this->request->getParameter('item_id', pInteger);
	 			$this->view->setVar('item_id',$vn_id);

	 			$vs_export = ca_data_exporters::exportRecord($t_exporter->get('exporter_code'), $vn_id, array('singleRecord' => true));

	 			$this->view->setVar("export", $vs_export);
 			}

 			$this->render('export/export_single_results_html.php');
 		}
 		# -------------------------------------------------------
		public function Delete($pa_values=null) {
			$t_exporter = $this->getExporterInstance();
			if ($this->request->getParameter('confirm', pInteger)) {
				$t_exporter->setMode(ACCESS_WRITE);
				$t_exporter->delete(true);

				if ($t_exporter->numErrors()) {
					foreach ($t_exporter->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
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
		public function DownloadExport(){
			// this has to be bullet-proof, otherwise someone could use it to download
			// files from our tmp dir (and potentially elsewhere)

			$ps_file = trim($this->request->getParameter('file',pString));
			$va_matches = array();
			if($ps_file && preg_match("/^([0-9]+)\_[0-9a-f]{32,32}$/", $ps_file, $va_matches)){
				if(file_exists(__CA_APP_DIR__.'/tmp/'.$ps_file)){
					if($va_matches[1]){
						$t_exporter = new ca_data_exporters($va_matches[1]);
						if($t_exporter->getPrimaryKey()){
							$this->view->setVar('file',__CA_APP_DIR__.'/tmp/'.$ps_file);
							$this->view->setVar('extension',$t_exporter->getFileExtension());
							$this->view->setVar('content_type',$t_exporter->getContentType());
						}
					}
				}
			}

			$this->render('export/download_batch_html.php');
		}
		# -------------------------------------------------------
		# Utilities
		# -------------------------------------------------------
		private function getExporterInstance($pb_set_view_vars=true, $pn_exporter_id=null) {
			if (!($vn_exporter_id = $this->request->getParameter('exporter_id', pInteger))) {
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
 		# Sidebar info handler
 		# ------------------------------------------------------------------
 		/**
 		 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
 		 *
 		 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
 		 */
 		public function Info($pa_parameters) {
 			$o_dm = Datamodel::load();

 			if(($this->request->getAction()=="Index") || ($this->request->getAction()=="Delete")){
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
 ?>