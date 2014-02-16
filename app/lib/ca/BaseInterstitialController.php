<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseInterstitialController.php : 
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
 
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
 	require_once(__CA_MODELS_DIR__."/ca_editor_ui_bundle_placements.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
	require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
 	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 
 	class BaseInterstitialController extends ActionController {
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
 		public function Form($pa_values=null, $pa_options=null) {
 			if(!is_array($pa_options)) { $pa_options = array(); }
 			list($t_subject, $t_ui, $vn_parent_id, $vn_above_id) = $this->_initView(array_merge($pa_options, array('loadSubject' => true)));
 			
 			if (!$t_subject) {
				$this->postError(1220, _t('Invalid table %1', $this->ops_table_name),"BaseInterstitalController->Edit()");
				return false;
 			}
 			
 			$ps_field_name_prefix = 	$this->request->getParameter('field_name_prefix', pString);
 			$pn_placement_id = 			$this->request->getParameter('placement_id', pInteger);		// placement_id of bundle that launched interstitial editor
 			$pn_n =			 			$this->request->getParameter('n', pInteger);				// index of bundle that launched interstitial editor
 			
 			$ps_primary_table = 		$this->request->getParameter('primary', pString);			// table name for item from which the interstitial editor was launched
 			$pn_primary_id = 			$this->request->getParameter('primary_id', pInteger);		// row_id of item from which the interstitial editor was launched
 			$this->view->setVar('primary_table', $ps_primary_table);
 			$this->view->setVar('primary_id', $pn_primary_id);
 			
 			if(is_array($pa_values)) {
 				foreach($pa_values as $vs_key => $vs_val) {
 					$t_subject->set($vs_key, $vs_val);
 				}
 			}
 			
 			$t_ui = ca_editor_uis::loadDefaultUI($this->ops_table_name, $this->request, null, array('editorPref' => 'interstitial'));
 			
 			
			
			if (!$t_ui || !$t_ui->getPrimaryKey()) {
				$this->notification->addNotification(_t('There is no configuration available for this editor. Check your system configuration and ensure there is at least one valid configuration for this type of editor.'), __NOTIFICATION_TYPE_ERROR__);
				$va_field_values = array();
			} else {
				// Get default screen (this is all we show in quickadd, even if the UI has multiple screens)
				$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
					array(),
					array()
				);
 			
				$this->view->setVar('t_ui', $t_ui);
				$this->view->setVar('screen', $va_nav['defaultScreen']);
				
				$va_field_values = $t_subject->extractValuesFromRequest($va_nav['defaultScreen'], $this->request, array('ui_instance' => $t_ui, 'dontReturnSerialIdno' => true));
			}
		
		
			# Trigger "EditItem" hook 
			$this->opo_app_plugin_manager->hookEditItem(array('id' => null, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
		
			// Set form unique identifiers
			$this->view->setVar('fieldNamePrefix', $_REQUEST['_formName']);
			$this->view->setVar('n', $pn_n);
		
			$this->view->setVar('q', $this->request->getParameter('q', pString));
		
			$this->view->setVar('default_parent_id', $this->opo_result_context->getParameter($t_subject->tableName().'_last_parent_id'));
			$this->view->setVar('placement_id', $pn_placement_id);
			$this->view->setVar('field_name_prefix', $ps_field_name_prefix);
			
			$this->render('interstitial/interstitial_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
 		 */
 		public function Save($pa_options=null) {
 			if(!is_array($pa_options)) { $pa_options = array(); }
 			list($t_subject, $t_ui, $vn_parent_id, $vn_above_id) = $this->_initView(array_merge($pa_options, array('loadSubject' => true)));
 			
 			if (!$t_subject) {
				$this->postError(1220, _t('Invalid table %1', $this->ops_table_name),"BaseInterstitalController->Edit()");
				return false;
 			}
 			
 			if (!is_array($pa_options)) { $pa_options = array(); }
 			
 			//
 			// Is record of correct type?
 			// 
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$pn_placement_id = 			$this->request->getParameter('placement_id', pInteger);		// placement_id of bundle that launched interstitial editor
 			
 			$ps_primary_table = 		$this->request->getParameter('primary', pString);	
 			$pn_primary_id = 			$this->request->getParameter('primary_id', pInteger);	
 			
 			// Make sure request isn't empty
 			if(!sizeof($_POST)) {
 				$va_response = array(
					'status' => 20,
					'id' => null,
					'table' => $t_subject->tableName(),
					'type_id' => null,
					'display' => null,
					'errors' => array(_t("Cannot save using empty request. Are you using a bookmark?") => _t("Cannot save using empty request. Are you using a bookmark?"))
				);
				
				$this->view->setVar('response', $va_response);
				
				$this->render('interstitial/interstitial_result_json.php');
				return;
 			}
 			
 			// Set type name for display
 			if (!($vs_type_name = $t_subject->getTypeName())) {
 				$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
 			}
 			
 			# trigger "BeforeSaveItem" hook 
			$this->opo_app_plugin_manager->hookBeforeSaveItem(array('id' => null, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => true));
 			

 			$t_placement = new ca_editor_ui_bundle_placements($pn_placement_id);
 			
 			$pa_bundle_settings = $t_placement->getSettings();
 			$va_opts = array_merge($pa_options, array('ui_instance' => $t_ui));
 			$vb_save_rc = $t_subject->saveBundlesForScreen($this->request->getParameter('screen', pString), $this->request, $va_opts);
			$this->view->setVar('t_ui', $t_ui);
		
 			$vs_message = _t("Saved changes to %1", $vs_type_name);
 		
 			//
 			// Regenerate display template for bundle that launched the interstitial editor so it will reflect any changes
 			//
 			$vs_related_table = $t_placement->getEditorType();
 			$vs_template = caGetBundleDisplayTemplate($t_subject, $vs_related_table, $pa_bundle_settings);
		
 			$qr_rel_items = caMakeSearchResult($t_subject->tableName(), array($t_subject->getPrimaryKey()));
 			
 			//
 			// Handle case of self relationships where we need to figure out which direction things are going in
 			// 		
			$va_bundle_values = array_shift(caProcessRelationshipLookupLabel($qr_rel_items, $t_subject, array('template' => $vs_template, 'primaryIDs' => array($ps_primary_table => array($pn_primary_id)))));

			if ($t_subject->hasField('type_id')) {
				if (method_exists($t_subject, "isSelfRelationship") && $t_subject->isSelfRelationship()) {
					$vn_left_id = $t_subject->get($t_subject->getLeftTableFieldName());
					$vn_right_id = $t_subject->get($t_subject->getRightTableFieldName());
					
					$va_bundle_values['relationship_typename'] = $t_subject->getRelationshipTypename(($vn_left_id == $pn_primary_id) ? 'ltol' : 'rtol');
				} else {
					$va_bundle_values['relationship_typename'] = $t_subject->getRelationshipTypename(($t_subject->getLeftTableFieldName() == $vs_related_table) ? 'rtol' : 'ltor');
				}
				$va_bundle_values['relationship_type_code'] = $t_subject->getRelationshipTypeCode();
			}
 			
 			//
 			// Report errors
 			//
 			$va_errors = $this->request->getActionErrors();							// all errors from all sources
 			$va_general_errors = $this->request->getActionErrors('general');		// just "general" errors - ones that are not attached to a specific part of the form
 			
 			if(sizeof($va_errors) - sizeof($va_general_errors) > 0) {
 				$va_error_list = array();
 				$vb_no_save_error = false;
 				foreach($va_errors as $o_e) {
 					$va_error_list[$o_e->getErrorDescription()] = $o_e->getErrorDescription()."\n";
 					
 					switch($o_e->getErrorNumber()) {
 						case 1100:	// duplicate/invalid idno
 							if (!$vn_subject_id) {		// can't save new record if idno is not valid (when updating everything but idno is saved if it is invalid)
 								$vb_no_save_error = true;
 							}
 							break;
 					}
 				}
 			} else {
 				$this->opo_result_context->invalidateCache();
 			}
  			$this->opo_result_context->saveContext();
 			
 			# trigger "SaveItem" hook 
			$this->opo_app_plugin_manager->hookSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => true));
 			
 			$vn_id = $t_subject->getPrimaryKey();
 			
 			$va_response = array(
 				'status' => sizeof($va_error_list) ? 10 : 0,
 				'id' => $vn_id,
 				'table' => $t_subject->tableName(),
				'type_id' => method_exists($t_subject, "getTypeID") ? $t_subject->getTypeID() : null,
 				'display' => 'relation',
 				'bundleDisplay' => $va_bundle_values,
 				'errors' => $va_error_list
 			);
 			
 			$this->view->setVar('response', $va_response);
 			
 			$this->render('interstitial/interstitial_result_json.php');
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
 			JavascriptLoadManager::register('ckeditor');

 			if (!($t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name))) { return null; }
 			
 			if (is_array($pa_options) && isset($pa_options['loadSubject']) && (bool)$pa_options['loadSubject'] && ($vn_subject_id = (int)$this->request->getParameter($t_subject->primaryKey(), pInteger))) {
 				$t_subject->load($vn_subject_id);
 			}
 			
 			if (is_array($pa_options) && isset($pa_options['forceSubjectValues']) && is_array($pa_options['forceSubjectValues'])) {
				foreach($pa_options['forceSubjectValues'] as $vs_f => $vs_v) {
					$t_subject->set($vs_f, $vs_v);
				}
			}

			// then reload the definitions (which includes bundle specs)

			$t_subject->reloadLabelDefinitions();

 			
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
 				$t_ui = ca_editor_uis::loadDefaultUI($this->ops_table_name, $this->request, $t_subject->getTypeID(), array('editorPref' => 'quickadd'));
 			}
 			
 			$this->view->setVar($t_subject->primaryKey(), $t_subject->getPrimaryKey());
 			$this->view->setVar('subject_id', $t_subject->getPrimaryKey());
 			$this->view->setVar('t_subject', $t_subject);
 			
 			
 			if ($vs_parent_id_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD')) {
 				$this->view->setVar('parent_id', $vn_parent_id = $this->request->getParameter($vs_parent_id_fld, pInteger));

 				return array($t_subject, $t_ui, $vn_parent_id, $vn_above_id);
 			}
 			
 			return array($t_subject, $t_ui);
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
 	}
 ?>