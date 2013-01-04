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
 			
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('panel');
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
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
 			list($t_ui) = $this->_initView($pa_options);
 			
 			$t_object = new ca_objects();
 			$t_rep = new ca_object_representations();
 			
 			// Can user batch import media?
 			if (!$this->request->user->canDoAction('can_batch_import_media')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3210?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
				array(),
				array()
			);
 			if (!$this->request->getActionExtra() || !isset($va_nav['fragment'][str_replace("Screen", "screen_", $this->request->getActionExtra())])) {
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
			$this->view->setVar('t_ui', $t_ui);
			
			// Get directory list
			$va_dir_list_with_file_counts = caGetSubDirectoryList($this->request->config->get('batch_media_import_root_directory'), true, false);
			$va_dir_list = array();
			
			foreach($va_dir_list_with_file_counts as $vs_dir => $vn_count) {
				if ($vn_count == 0) { continue; }
				$va_dir_list["{$vs_dir} ({$vn_count} file".(($vn_count == 1) ? '' : 's').')'] = $vs_dir;
			}
			$this->view->setVar('directory_list', caHTMLSelect('directory', $va_dir_list, array('id' => 'caMediaImportDirectoryList'), array('width' => '500px')));
			
			$this->view->setVar('import_mode', caHTMLSelect('import_mode', array(
				_t('Import all media, matching with existing records where possible') => 'TRY_TO_MATCH',
				_t('Import only media that can be matched with existing records') => 'ALWAYS_MATCH',
				_t('Import all media, creating new records for each') => 'DONT_MATCH'
			)));
 			
 			$this->view->setVar('ca_objects_type_list', $t_object->getTypeListAsHTMLFormElement('ca_objects_type_id'));
 			$this->view->setVar('ca_object_representations_type_list', $t_rep->getTypeListAsHTMLFormElement('ca_object_representations_type_id'));
 		
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
 			
 			if (!is_dir($vs_directory)) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$vs_batch_media_import_root_directory = $this->request->config->get('batch_media_import_root_directory');

 			if (!preg_match("!^{$vs_batch_media_import_root_directory}!", $vs_directory)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			if (preg_match("!/\.\.!", $vs_directory) || preg_match("!\.\./!", $vs_directory)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			// Can user batch import media?
 			if (!$this->request->user->canDoAction('can_batch_import_media')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3210?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_options = array(
 				'sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger), 
 				'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger), 
 				'runInBackground' => (bool)$this->request->getParameter('run_in_background', pInteger),
 				
 				'importFromDirectory' => $vs_directory,
 				'importMode' => $this->request->getParameter('import_mode', pString),
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
 				'locale_id' => $g_ui_locale_id,
 				'user_id' => $this->request->getUserID()
 			);
 
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
		# -------------------------------------------------------
 		# Dynamic navigation generation
 		# -------------------------------------------------------
 		/**
 		 * Generates side-navigation for current UI based upon screen structure in database. Called by AppNavigation class.
 		 *
 		 * @param array $pa_params Array of parameters used to generate navigation
 		 * @param array $pa_options Array of options passed through to _initView 
 		 * @return array Navigation specification ready for inclusion in a menu spec
 		 */
 		public function _genDynamicNav($pa_params, $pa_options=null) {
 			list($t_ui) = $this->_initView($pa_options);
 			if (!$this->request->isLoggedIn()) { return array(); }
 			
 			$vn_type_id = $this->request->getParameter('type_id', pInteger);
 	
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $pa_params['default']['module'], $pa_params['default']['controller'], $pa_params['default']['action'],
 				isset($pa_params['parameters']) ? $pa_params['parameters'] : null,
 				isset($pa_params['requires']) ? $pa_params['requires'] : null,
 				false,
 				array('hideIfNoAccess' => isset($pa_params['hideIfNoAccess']) ? $pa_params['hideIfNoAccess'] : false)
 			);
 			
 			if (!$this->request->getActionExtra()) {
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
 			
 			return $va_nav['fragment'];
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