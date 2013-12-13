<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/EditorController.php : 
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
 
 	class EditorController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_datamodel;
 		protected $opo_app_plugin_manager;
 		protected $opo_result_context;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			JavascriptLoadManager::register('bundleListEditorUI');
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('bundleListEditorUI');
 			JavascriptLoadManager::register('panel');
 			
 			$this->opo_datamodel = Datamodel::load();
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 			$this->opo_result_context = new ResultContext($po_request, $this->ops_table_name, ResultContext::getLastFind($po_request, $this->ops_table_name));
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generates a form for editing new or existing records. The form is rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_values An optional array of values to preset in the format, overriding any existing values in the model of the record being editing.
 		 * @param array $pa_options Array of options passed through to _initView
 		 *
 		 */
 		public function Edit($pa_values=null, $pa_options=null) {
 			list($vn_set_id, $t_set, $t_subject, $t_ui) = $this->_initView($pa_options);
 			
 			if (!$vn_set_id) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3200?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			// Can user batch edit this table?
 			if (!$this->request->user->canDoAction('can_batch_edit_'.$t_set->getAppDatamodel()->getTableName($t_set->get('table_num')))) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3210?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			if ($t_set->getItemCount(array('user_id' => $this->request->getUserID())) <= 0) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3220?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->view->setVar('batch_editor_last_settings', $va_last_settings = is_array($va_last_settings = $this->request->user->getVar('batch_editor_last_settings')) ? $va_last_settings : array());
 			
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
				array(),
				array(),
				false,
				array('restrictToTypes' => $t_set->getTypesForItems())
			);
 			if (!$this->request->getActionExtra() || !isset($va_nav['fragment'][str_replace("Screen", "screen_", $this->request->getActionExtra())])) {
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
			$this->view->setVar('t_ui', $t_ui);
 			
 			$this->render('editor/screen_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
 		 */
 		public function Save($pa_options=null) {
 			if (!is_array($pa_options)) { $pa_options = array(); }
 			list($vn_set_id, $t_set, $t_subject, $t_ui) = $this->_initView($pa_options);
 			
 			if (!$vn_set_id) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3200?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			// Can user batch edit this table?
 			if (!$this->request->user->canDoAction('can_batch_edit_'.$t_set->getAppDatamodel()->getTableName($t_set->get('table_num')))) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3210?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_last_settings = array(
				'set_id' => $vn_set_id,
				'ui_id' => $t_ui->getPrimaryKey(),
				'screen' => $this->request->getActionExtra(),
				'user_id' => $this->request->getUserID(),
				'values' => $_REQUEST,
				'sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger),
				'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger)
			);
 			
 			if ((bool)$this->request->config->get('queue_enabled') && (bool)$this->request->getParameter('run_in_background', pInteger)) { // queue for background processing
 				$o_tq = new TaskQueue();
 				
 				$vs_row_key = $vs_entity_key = join("/", array($this->request->getUserID(), $t_set->getPrimaryKey(), time(), rand(1,999999)));
				if (!$o_tq->addTask(
					'batchEditor',
					$va_last_settings,
					array("priority" => 100, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $this->request->getUserID())))
				{
					//$this->postError(100, _t("Couldn't queue batch processing for"),"EditorContro->_processMedia()");
					
				}
				$this->render('editor/batch_queued_html.php');
			} else { 
				// run now
				$app = AppController::getInstance();
				$app->registerPlugin(new BatchEditorProgress($this->request, $t_set, $t_subject, array('sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger), 'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger), 'runInBackground' => (bool)$this->request->getParameter('run_in_background', pInteger))));
				$this->render('editor/batch_results_html.php');
			}
			
 			$this->request->user->setVar('batch_editor_last_settings', $va_last_settings);
 
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			list($vn_set_id, $t_set, $t_subject, $t_ui) = $this->_initView($pa_options);

 			if (!$this->request->user->canDoAction('can_batch_delete_'.$t_set->getAppDatamodel()->getTableName($t_set->get('table_num')))) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3230?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}

 			if ($t_set->getItemCount(array('user_id' => $this->request->getUserID())) <= 0) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3220?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}

 			if ($vb_confirm = ($this->request->getParameter('confirm', pInteger) == 1) ? true : false) {
 				$this->view->setVar('confirmed',true);

 				// run now
				$app = AppController::getInstance();
				$app->registerPlugin(new BatchEditorProgress($this->request, $t_set, $t_subject, array('isBatchDelete' => true)));
 			}

 			$this->render('editor/delete_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function ChangeType($pa_options=null) {
 			if (!is_array($pa_options)) { $pa_options = array(); }
 			list($vn_set_id, $t_set, $t_subject, $t_ui) = $this->_initView($pa_options);
 			
 			if (!$vn_set_id) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3200?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			// Can user batch edit this table?
 			if (!$this->request->user->canDoAction('can_batch_edit_'.$t_set->getAppDatamodel()->getTableName($t_set->get('table_num')))) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3210?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			if (!$this->request->user->canDoAction("can_change_type_".$t_subject->tableName()) || !method_exists($t_subject, "getTypeList")) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3260?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$vn_new_type_id = $this->request->getParameter('new_type_id', pInteger);
 			$va_type_list = $t_subject->getTypeList();
 			if (!isset($va_type_list[$vn_new_type_id])) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3260?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_last_settings = array(
				'set_id' => $vn_set_id,
				'screen' => $this->request->getActionExtra(),
				'user_id' => $this->request->getUserID(),
				'values' => $_REQUEST,
				'sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger),
				'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger)
			);
 			
 			if ((bool)$this->request->config->get('queue_enabled') && (bool)$this->request->getParameter('run_in_background', pInteger)) { // queue for background processing
 				$o_tq = new TaskQueue();
 				
 				$vs_row_key = $vs_entity_key = join("/", array($this->request->getUserID(), $t_set->getPrimaryKey(), time(), rand(1,999999)));
				if (!$o_tq->addTask(
					'batchEditor',
					array_merge($va_last_settings, array('isBatchTypeChange' => true, 'new_type_id' => $vn_new_type_id)),
					array("priority" => 100, "entity_key" => $vs_entity_key, "row_key" => $vs_row_key, 'user_id' => $this->request->getUserID())))
				{
					//$this->postError(100, _t("Couldn't queue batch processing for"),"EditorContro->_processMedia()");
					
				}
				$this->render('editor/batch_queued_html.php');
			} else { 
				// run now
				$app = AppController::getInstance();
				$app->registerPlugin(new BatchEditorProgress($this->request, $t_set, $t_subject, array('type_id' => $vn_new_type_id, 'isBatchTypeChange' => true, 'sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger), 'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger), 'runInBackground' => (bool)$this->request->getParameter('run_in_background', pInteger))));
				$this->render('editor/batch_results_html.php');
			}
			
 			$this->request->user->setVar('batch_editor_last_settings', $va_last_settings);
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
 			
 			$vn_set_id = $this->request->getParameter('set_id', pInteger);
 			$t_set = new ca_sets();
 			
 			if (!$vn_set_id || !$t_set->load($vn_set_id)) {
 				// Bad set id
 				return array(null, null, null, null);
 			}
 			
 			// Does user have access to set?
 			if (!$t_set->haveAccessToSet($this->request->getUserID(), __CA_SET_READ_ACCESS__)) {
 				return array(null, null, null, null);
 			}
 			
 			
 			$t_subject = $this->opo_datamodel->getInstanceByTableNum($t_set->get('table_num'));
 			$t_ui = new ca_editor_uis();
 			if (!isset($pa_options['ui']) && !$pa_options['ui']) {
 				$t_ui->load($this->request->user->getPreference("batch_".$t_subject->tableName()."_editor_ui"));
 			}
 			if (!$t_ui->getPrimaryKey() && isset($pa_options['ui']['__all__']) && $pa_options['ui']['__all__']) {
 				if (is_numeric($pa_options['ui']['__all__'])) {
 					$t_ui->load((int)$pa_options['ui']['__all__']);
 				}
 				if (!$t_ui->getPrimaryKey()) {
 					$t_ui->load(array('editor_code' => $pa_options['ui']['__all__']));
 				}
 			}
 			
 			if (!$t_ui->getPrimaryKey()) {
 				$t_ui = ca_editor_uis::loadDefaultUI($t_subject->tableName(), $this->request, $t_subject->getTypeID());
 			}
 			
 			$this->view->setVar('set_id', $vn_set_id);
 			$this->view->setVar('t_set', $t_set);
 			$this->view->setVar('t_subject', $t_subject);
 			
 			$vn_item_count = $t_set->getItemCount(array('user_id' => $this->request->getUserID()));
 			$vs_item_name = ($vn_item_count == 1) ? $t_subject->getProperty("NAME_SINGULAR"): $t_subject->getProperty("NAME_PLURAL");
 			
 			MetaTagManager::setWindowTitle(_t("Batch editing %1 %2 with set %3", $vn_item_count, $vs_item_name, $t_set->getLabelForDisplay(true)));
 			
 			
 			return array($vn_set_id, $t_set, $t_subject, $t_ui);
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
 			list($vn_set_id, $t_set, $t_subject, $t_ui) = $this->_initView($pa_options);
 			if (!$this->request->isLoggedIn()) { return array(); }
 			
 			if (!($vn_type_id = $t_subject->getTypeID())) {
 				$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger);
 			}
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $pa_params['default']['module'], $pa_params['default']['controller'], $pa_params['default']['action'],
 				isset($pa_params['parameters']) ? $pa_params['parameters'] : null,
 				isset($pa_params['requires']) ? $pa_params['requires'] : null,
 				false,
 				array('hideIfNoAccess' => isset($pa_params['hideIfNoAccess']) ? $pa_params['hideIfNoAccess'] : false, 'returnTypeRestrictions' => true, 'restrictToTypes' => $t_set->getTypesForItems())
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
 			$vn_set_id = $this->request->getParameter('set_id', pInteger);
 		
 			$o_dm 				= Datamodel::load();
 			$t_set				= new ca_sets($vn_set_id);
 			
 			if (!$t_set->getPrimaryKey()) { 
 				die("Invalid set");
 			}
 			
 			// Does user have access to set?
 			if (!$t_set->haveAccessToSet($this->request->getUserID(), __CA_SET_READ_ACCESS__)) {
 				die("You don't have access to the set");
 			}
 			
 			$t_item 			= $o_dm->getInstanceByTableNum($t_set->get('table_num'), true);
 			
 		
 			$this->view->setVar('t_set', $t_set);
 			$this->view->setVar('t_item', $t_item);
 			
			$this->view->setVar('screen', $this->request->getActionExtra());						// name of screen
			$this->view->setVar('result_context', $this->getResultContext());
			
 			return $this->render('editor/widget_batch_info_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
 ?>