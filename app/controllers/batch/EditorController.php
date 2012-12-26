<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/EditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
	require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
	require_once(__CA_LIB_DIR__."/core/Logging/Batchlog.php");
 
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
 			
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
				array(),
				array()
			);
 			if (!$this->request->getActionExtra() || !isset($va_nav['fragment'][str_replace("Screen", "screen_", $this->request->getActionExtra())])) {
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
			$this->view->setVar('t_ui', $t_ui);
 			
 			$this->render('screen_html.php');
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
 			
 			print_R($_REQUEST);
 			
 			$this->render('screen_html.php');
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
 				die("Invalid set_id");
 			}
 			
 			// Does user have access to set?
 			if (!$t_set->haveAccessToSet($this->request->getUserID(), __CA_SET_READ_ACCESS__)) {
 				die("You don't have access to the set");
 			}
 			
 			
 			$t_subject = $this->opo_datamodel->getInstanceByTableNum($t_set->get('table_num'));
 			$t_ui = new ca_editor_uis();
 			if (isset($pa_options['ui']) && $pa_options['ui']) {
 				if (is_numeric($pa_options['ui'])) {
 					$t_ui->load((int)$pa_options['ui']);
 				}
 				if (!$t_ui->getPrimaryKey()) {
 					$t_ui->load(array('editor_code' => $pa_options['ui']));
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
 			//list($vn_subject_id, $t_subject, $t_ui) = $this->_initView($pa_options);
 			list($vn_set_id, $t_set, $t_subject, $t_ui) = $this->_initView($pa_options);
 			if (!$this->request->isLoggedIn()) { return array(); }
 			
 			if (!($vn_type_id = $t_subject->getTypeID())) {
 				$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger);
 			}
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
			
 			return $this->render('widget_batch_info_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
 ?>