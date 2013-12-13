<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/MediaImportController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
 	require_once(__CA_LIB_DIR__."/ca/BatchProcessor.php");
 	require_once(__CA_LIB_DIR__."/ca/BatchEditorProgress.php");
 	require_once(__CA_LIB_DIR__."/ca/BatchMediaImportProgress.php");

 
 	class MediaImportController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_datamodel;
 		protected $opo_app_plugin_manager;
 		protected $opo_result_context;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			// Can user batch import media?
 			if (!$po_request->user->canDoAction('can_batch_import_media')) {
 				$po_response->setRedirect($po_request->config->get('error_display_url').'/n/3410?r='.urlencode($po_request->getFullUrlPath()));
 				return;
 			}
 			
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('panel');
 			
 			$this->opo_datamodel = Datamodel::load();
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 			$this->opo_result_context = new ResultContext($po_request, $this->ops_table_name, ResultContext::getLastFind($po_request, $this->ops_table_name));
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generates a form for specification of media import settings. The form is rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_values An optional array of values to preset in the format, overriding any existing values in the model of the record being editing.
 		 * @param array $pa_options Array of options passed through to _initView
 		 *
 		 */
 		public function Index($pa_values=null, $pa_options=null) {
 			JavascriptLoadManager::register("directoryBrowser");
 			list($t_ui) = $this->_initView($pa_options);
 			
 			$this->view->setVar('batch_mediaimport_last_settings', $va_last_settings = is_array($va_last_settings = $this->request->user->getVar('batch_mediaimport_last_settings')) ? $va_last_settings : array());
 			
 			$t_object = new ca_objects();
 			$t_object->set('status', $va_last_settings['ca_objects_status']);
 			$t_object->set('access', $va_last_settings['ca_objects_access']);
 			
 			$t_rep = new ca_object_representations();
 			$t_rep->set('status', $va_last_settings['ca_object_representations_status']);
 			$t_rep->set('access', $va_last_settings['ca_object_representations_access']);
 			
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
				array(),
				array()
			);
 			if (!$this->request->getActionExtra() || !isset($va_nav['fragment'][str_replace("Screen", "screen_", $this->request->getActionExtra())])) {
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
			$this->view->setVar('t_ui', $t_ui);
			
			$this->view->setVar('import_mode', caHTMLSelect('import_mode', array(
				_t('Import all media, matching with existing records where possible') => 'TRY_TO_MATCH',
				_t('Import only media that can be matched with existing records') => 'ALWAYS_MATCH',
				_t('Import all media, creating new records for each') => 'DONT_MATCH'
			), array(), array('value' => $va_last_settings['importMode'])));
			
			$this->view->setVar('match_mode', caHTMLSelect('match_mode', array(
				_t('Match using file name') => 'FILE_NAME',
				_t('Match using directory name') => 'DIRECTORY_NAME',
				_t('Match using directory name, then file name') => 'FILE_AND_DIRECTORY_NAMES'
			), array(), array('value' => $va_last_settings['matchMode'])));
 			
 			$this->view->setVar('ca_objects_type_list', $t_object->getTypeListAsHTMLFormElement('ca_objects_type_id', null, array('value' => $va_last_settings['ca_objects_type_id'])));
 			$this->view->setVar('ca_object_representations_type_list', $t_rep->getTypeListAsHTMLFormElement('ca_object_representations_type_id', null, array('value' => $va_last_settings['ca_object_representations_type_id'])));
 		
 			//
 			// Available sets
 			//
 			$t_set = new ca_sets();
 			$va_available_set_list = caExtractValuesByUserLocale($t_set->getSets(array('table' => 'ca_objects', 'user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__, 'omitCounts' => true)));
 			$va_available_sets = array();
 			foreach($va_available_set_list as $vn_set_id => $va_set) {
 				$va_available_sets[$va_set['name']] = $vn_set_id;
 			}
 			$this->view->setVar('available_sets', $va_available_sets);


 			$this->view->setVar('t_object', $t_object);
 			$this->view->setVar('t_rep', $t_rep);
 		
 			$this->render('mediaimport/import_options_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
 		 */
 		public function Save($pa_options=null) {
			global $g_ui_locale_id;
			
 			if (!is_array($pa_options)) { $pa_options = array(); }
 			list($t_ui) = $this->_initView($pa_options);
 			
 			$vs_directory = $this->request->getParameter('directory', pString);
 			
 			$vs_batch_media_import_root_directory = $this->request->config->get('batch_media_import_root_directory');
 			 			
 			if (preg_match("!/\.\.!", $vs_directory) || preg_match("!\.\./!", $vs_directory)) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			if (!is_dir($vs_batch_media_import_root_directory.'/'.$vs_directory)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_options = array(
 				'sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger), 
 				'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger), 
 				'runInBackground' => (bool)$this->request->getParameter('run_in_background', pInteger),
 				
 				'importFromDirectory' => $vs_batch_media_import_root_directory.'/'.$vs_directory,
 				'includeSubDirectories' => (bool)$this->request->getParameter('include_subdirectories', pInteger),
 				'deleteMediaOnImport' => (bool)$this->request->getParameter('delete_media_on_import', pInteger),
 				'importMode' => $this->request->getParameter('import_mode', pString),
 				'matchMode' => $this->request->getParameter('match_mode', pString),
 				'ca_objects_type_id' => $this->request->getParameter('ca_objects_type_id', pInteger),
 				'ca_object_representations_type_id' => $this->request->getParameter('ca_object_representations_type_id', pInteger),
 				'ca_objects_status' => $this->request->getParameter('ca_objects_status', pInteger),
 				'ca_object_representations_status' => $this->request->getParameter('ca_object_representations_status', pInteger),
 				'ca_objects_access' => $this->request->getParameter('ca_objects_access', pInteger),
 				'ca_object_representations_access' => $this->request->getParameter('ca_object_representations_access', pInteger),
 				'setMode' => $this->request->getParameter('set_mode', pString),
 				'setCreateName' => $this->request->getParameter('set_create_name', pString),
 				'set_id' => $this->request->getParameter('set_id', pInteger),
 				'idnoMode' => $this->request->getParameter('idno_mode', pString),
 				'idno' => $this->request->getParameter('idno', pString),
 				'idno' => $this->request->getParameter('idno', pString),
 				'locale_id' => $g_ui_locale_id,
 				'user_id' => $this->request->getUserID(),
 				'skipFileList' => $this->request->getParameter('skip_file_list', pString)
 			);
 			
 			if (is_array($va_create_relationships_for = $this->request->getParameter('create_relationship_for', pArray))) {
 				$va_options['create_relationship_for'] = $va_create_relationships_for;
 				foreach($va_create_relationships_for as $vs_rel_table) {
 					$va_options['relationship_type_id_for_'.$vs_rel_table] = $this->request->getParameter('relationship_type_id_for_'.$vs_rel_table, pString);
 				}
 			}
 			
 			$va_last_settings = $va_options;
 			$va_last_settings['importFromDirectory'] = preg_replace("!{$vs_batch_media_import_root_directory}[/]*!", "", $va_last_settings['importFromDirectory']); 
 			$this->request->user->setVar('batch_mediaimport_last_settings', $va_last_settings);
 
 			if ((bool)$this->request->config->get('queue_enabled') && (bool)$this->request->getParameter('run_in_background', pInteger)) { // queue for background processing
 				$o_tq = new TaskQueue();
 				
 				$vs_row_key = $vs_entity_key = join("/", array($this->request->getUserID(), $va_options['importFromDirectory'], time(), rand(1,999999)));
				if (!$o_tq->addTask(
					'mediaImport',
					$va_options,
					array("priority" => 100, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $this->request->getUserID())))
				{
					//$this->postError(100, _t("Couldn't queue batch processing for"),"EditorContro->_processMedia()");
					
				}
				$this->render('mediaimport/batch_queued_html.php');
			} else { 
				// run now
				$app = AppController::getInstance();
				$app->registerPlugin(new BatchMediaImportProgress($this->request, $va_options));
				$this->render('mediaimport/batch_results_html.php');
			}
 		}
		# ----------------------------------------
		/**
		 * Returns a list of files for the directory $dir 
		 *
		 * @param string $dir The path to the directory you wish to get the contents list for
		 * @param bool $pb_include_hidden_files Optional. By default caGetDirectoryContentsAsList() does not consider hidden files (files starting with a '.') when calculating file counts. Set this to true to include hidden files in counts. Note that the special UNIX '.' and '..' directory entries are *never* counted as files.
		 * @param int $pn_max_length_of_name Maximum length in characters of returned file names. Note that the full name is always returned in the 'fullname' value. Only 'name' is truncated.
		 * @return array An array of file names.
		 */
		private function _getDirectoryListing($dir, $pb_include_hidden_files=false, $pn_max_length_of_name=25, $pn_start_at=0, $pn_max_items_to_return=25) {
			if (!is_dir($dir)) { return array(); }
			$va_file_list = array();
			if(substr($dir, -1, 1) == "/"){
				$dir = substr($dir, 0, strlen($dir) - 1);
			}
			
			if($va_paths = scandir($dir, 0)) {
				$vn_i = $vn_c = 0;
				foreach($va_paths as $item) {
					if ($item != "." && $item != ".." && ($pb_include_hidden_files || (!$pb_include_hidden_files && $item{0} !== '.'))) {
						$vb_is_dir = is_dir("{$dir}/{$item}");
						$vs_k = preg_replace('![\:]+!', '|', $item);
						if ($vb_is_dir) { 
							$vn_i++;
							if (($pn_start_at > 0) && ($vn_i <= $pn_start_at)) { continue; }
							$va_child_counts = caGetDirectoryContentsCount("{$dir}/{$item}", false, false);
							$va_file_list[$vs_k] = array(
								'item_id' => $vs_k, 
								'name' => caTruncateStringWithEllipsis($item, $pn_max_length_of_name),
								'fullname' => $item,
								'type' => 'DIR',
								'children' => (int)$va_child_counts['files'] + (int)$va_child_counts['directories'],
								'files' => (int)$va_child_counts['files'],
								'subdirectories' => (int)$va_child_counts['directories']
							);
							$vn_c++;
						} else { 
							if (!$vb_is_dir) { 
								$vn_i++;
								if (($pn_start_at > 0) && ($vn_i <= $pn_start_at)) { continue; }
								$va_file_list[$vs_k] = array(
									'item_id' => $vs_k,
									'name' => caTruncateStringWithEllipsis($item, $pn_max_length_of_name),
									'fullname' => $item,
									'type' => 'FILE'
								);
								$vn_c++;
							}
						}
					}
					
					if ($vn_c >= $pn_max_items_to_return) { break; }
				}
			}
		
			return $va_file_list;
		}
 		# -------------------------------------------------------
 		/**
 		 * Initializes editor view with core set of values, loads model with record to be edited and selects user interface to use.
 		 *
 		 * @param $pa_options Array of options. Supported options are:
 		 *		ui = The ui_id or editor_code value for the user interface to use. If omitted the default user interface is used.
 		 */
 		protected function _initView($pa_options=null) {
 			// load required javascript
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('imageScroller');
 			JavascriptLoadManager::register('datePickerUI');
 			
 			$t_ui = new ca_editor_uis();
 			if (!isset($pa_options['ui']) && !$pa_options['ui']) {
 				$pa_options['ui'] = $this->request->user->getPreference("batch_ca_object_media_import_ui");
 			}
 			if (isset($pa_options['ui']) && $pa_options['ui']) {
 				if (is_numeric($pa_options['ui'])) {
 					$t_ui->load((int)$pa_options['ui']);
 				}
 				if (!$t_ui->getPrimaryKey()) {
 					$t_ui->load(array('editor_code' => $pa_options['ui']));
 				}
 			}
 			
 			if (!$t_ui->getPrimaryKey()) {
 				$t_ui = ca_editor_uis::loadDefaultUI('ca_objects', $this->request, null);
 			}
 			
 			MetaTagManager::setWindowTitle(_t("Batch import media"));
 			
 			
 			return array($t_ui);
 		}
		# ------------------------------------------------------------------
		/** 
		 * Returns current result contents
		 *
		 * @return ResultContext ResultContext instance.
		 */
		public function getResultContext() {
			return $this->opo_result_context;
		}
 		# ------------------------------------------------------------------
 		public function GetDirectoryLevel() {
 			$ps_id = $this->request->getParameter('id', pString);
 			$pn_max = $this->request->getParameter('max', pString);
 			$vs_root_directory = $this->request->config->get('batch_media_import_root_directory');
 			
 			$va_level_data = array();
 			
 			if ($this->request->getParameter('init', pInteger)) { 
 				//
 				// On first load (init) of browser load all levels in single request
 				//
 				$va_tmp = explode(";", $ps_id);
 				
 				$va_acc = array();
 				foreach($va_tmp as $vs_tmp) {
 					list($vs_directory, $vn_start) = explode(":", $vs_tmp);
 					if (!$vs_directory) { continue; }
 					
 					$va_tmp = explode('/', $vs_directory);
					$vs_k = array_pop($va_tmp);
					if(!$vs_k) { $vs_k = '/'; }
					
					$va_level_data[$vs_k] = $va_file_list = $this->_getDirectoryListing($vs_root_directory.'/'.$vs_directory, false, 20, (int)$vn_start, (int)$pn_max);
					$va_level_data[$vs_k]['_primaryKey'] = 'name';
					
					$va_counts = caGetDirectoryContentsCount($vs_root_directory.'/'.$vs_directory, false, false);
					$va_level_data[$vs_k]['_itemCount'] = $va_counts['files'] + $va_counts['directories'];
 				}
 			} else {
 				list($ps_directory, $pn_start) = explode(":", $ps_id);
				if (!$ps_directory) { 
					$va_level_data[$vs_k] = array('/' => 
							array(
								'item_id' => '/',
								'name' => 'Root',
								'type' => 'DIR',
								'children' => 1
							)
					);
					$va_level_data[$vs_k]['_primaryKey'] = 'name';
					$va_level_data[$vs_k]['_itemCount'] = 1;
				} else {
					$va_tmp = explode('/', $ps_directory);
					$vs_k = array_pop($va_tmp);
					if(!$vs_k) { $vs_k = '/'; }
					
					$va_level_data[$vs_k] = $va_file_list = $this->_getDirectoryListing($vs_root_directory.'/'.$ps_directory, false, 20, (int)$pn_start, (int)$pn_max);
					$va_level_data[$vs_k]['_primaryKey'] = 'name';
					
					$va_counts = caGetDirectoryContentsCount($vs_root_directory.'/'.$ps_directory, false, false);
					$va_level_data[$vs_k]['_itemCount'] = $va_counts['files'] + $va_counts['directories'];
				}
			}
 			
 			$this->view->setVar('directory_list', $va_level_data);
 			
 			
 			$this->render('mediaimport/directory_level_json.php');
 		}
 		# ------------------------------------------------------------------
 		public function GetDirectoryAncestorList() {
 			$ps_id = $this->request->getParameter('id', pString);
 			list($ps_directory, $pn_start) = explode(":", $ps_id);
 			
 			$va_ancestors = array();	
 			if ($ps_directory) {
 				$va_tmp = explode("/", $ps_directory);
 				$va_acc = array();
 				foreach($va_tmp as $vs_tmp) {
 					if (!$vs_tmp) { continue; }
 					$va_acc = array($vs_tmp);
 					$va_ancestors[] = join("/", $va_acc);
 				}
 			}
 			
 			$this->view->setVar("ancestors", $va_ancestors);
 			
 			$this->render('mediaimport/directory_ancestors_json.php');
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		public function UploadFiles() {
 			$ps_directory = $this->request->getParameter('path', pString);
 			$vs_batch_media_import_root_directory = $this->request->config->get('batch_media_import_root_directory');
 			
 			if (preg_match("!/\.\.!", $ps_directory) || preg_match("!\.\./!", $ps_directory)) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			if (!is_dir($vs_batch_media_import_root_directory.'/'.$ps_directory)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$o_media = new Media();
 			$va_extensions = Media::getImportFileExtensions();
 			
 			$va_response = array('path' => $ps_directory, 'uploadMessage' => '', 'skippedMessage' => '');
 			
 			if (!is_writeable($vs_batch_media_import_root_directory.$ps_directory)) {
 				$va_response['error'] = _t('Cannot write file: directory %1 is not accessible', $ps_directory);
 			} else {
				foreach($_FILES as $vs_param => $va_file) {
					foreach($va_file['name'] as $vn_i => $vs_name) {
						if (!in_array(pathinfo($vs_name, PATHINFO_EXTENSION), $va_extensions)) { 
							$va_response['skipped'][$vs_name] = true;
							continue; 
						}
						if (copy($va_file['tmp_name'][$vn_i], $vs_batch_media_import_root_directory.$ps_directory."/".$vs_name)) {
							$va_response['copied'][$vs_name] = true;
						} else {
							$va_response['skipped'][$vs_name] = true;
						}
					}
				}
			}
			
			$va_response['uploadMessage'] = (($vn_upload_count = sizeof($va_response['copied'])) == 1) ? _t('Uploaded %1 file', $vn_upload_count) : _t('Uploaded %1 files', $vn_upload_count);
			if (is_array($va_response['skipped']) && ($vn_skip_count = sizeof($va_response['skipped'])) && !$va_response['error']) {
				$va_response['skippedMessage'] = ($vn_skip_count == 1) ? _t('Skipped %1 file', $vn_skip_count) : _t('Skipped %1 files', $vn_skip_count);
			}
			
 			$this->view->setVar('response', $va_response);
 			$this->render('mediaimport/file_upload_response_json.php');
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
 			$o_dm 				= Datamodel::load();
 			
			$this->view->setVar('screen', $this->request->getActionExtra());						// name of screen
			$this->view->setVar('result_context', $this->getResultContext());
			
 			return $this->render('mediaimport/widget_batch_info_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
 ?>