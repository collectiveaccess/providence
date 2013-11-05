<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 	require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
 	require_once(__CA_MODELS_DIR__."/ca_attributes.php");
 	require_once(__CA_MODELS_DIR__."/ca_attribute_values.php");
 	require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
	require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
 
 	class BaseEditorController extends ActionController {
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
 			JavascriptLoadManager::register('panel');
 			
 			list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id) = $this->_initView($pa_options);
 			$vs_mode = $this->request->getParameter('mode', pString);
 			
 			//
 			// Is record deleted?
 			//
 			if ($t_subject->hasField('deleted') && $t_subject->get('deleted')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2550?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Is record of correct type?
 			// 
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => $vn_subject_id ? __CA_BUNDLE_ACCESS_READONLY__ : __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking') && $vn_subject_id) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) == __CA_ACL_NO_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			//
 			// Are we duplicating?
 			//
 			if (($vs_mode == 'dupe') && $this->request->user->canDoAction('can_duplicate_'.$t_subject->tableName())) {
 				if (!($vs_type_name = $t_subject->getTypeName())) {
					$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
				}
				// Trigger "before duplicate" hook
				$this->opo_app_plugin_manager->hookBeforeDuplicateItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
			
				if ($t_dupe = $t_subject->duplicate(array(
					'user_id' => $this->request->getUserID(),
					'duplicate_nonpreferred_labels' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_nonpreferred_labels'),
					'duplicate_attributes' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_attributes'),
					'duplicate_relationships' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_relationships'),
					'duplicate_media' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_media'),
					'duplicate_subitems' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_subitems')
				))) {
 					$this->notification->addNotification(_t('Duplicated %1 "%2" (%3)', $vs_type_name, $t_subject->getLabelForDisplay(), $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD'))), __NOTIFICATION_TYPE_INFO__);
 					
					// Trigger duplicate hook
					$this->opo_app_plugin_manager->hookDuplicateItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'duplicate' => $t_dupe));
			
 					// redirect to edit newly created dupe.
 					$this->response->setRedirect(caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(), array($t_subject->primaryKey() => $t_dupe->getPrimaryKey())));
 					return;
 				} else {
 					$this->notification->addNotification(_t('Could not duplicate %1: %2', $vs_type_name, join('; ', $t_subject->getErrors())), __NOTIFICATION_TYPE_ERROR__);
 				}
 			}
 			
 			if($vn_above_id) {
 				// Convert "above" id (the id of the record we're going to make the newly created record parent of
 				// to parent_id, by getting the parent of the "above" record, so the inspector can display the name of the parent
 				if (($t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name)) && $t_instance->load($vn_above_id)) {
 					$vn_parent_id = $t_instance->get($vs_parent_id_fld = $t_instance->getProperty('HIERARCHY_PARENT_ID_FLD'));
 					$this->request->setParameter($vs_parent_id_fld, $vn_parent_id);
 					$this->view->setVar('parent_id', $vn_parent_id);
 				}
 			}
 			
 			if ((!$t_subject->getPrimaryKey()) && ($vn_subject_id)) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			if(is_array($pa_values)) {
 				foreach($pa_values as $vs_key => $vs_val) {
 					$t_subject->set($vs_key, $vs_val);
 				}
 			}
 			
 			// set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
 			if ($vs_idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
 				if ($vn_subject_id > 0) {
 					$this->view->setVar('_context_id', $t_subject->get($vs_idno_context_field));
 				} else {
 					if ($vn_parent_id > 0) {
 						$t_parent = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
 						if ($t_parent->load($vn_parent_id)) {
 							$this->view->setVar('_context_id', $t_parent->get($vs_idno_context_field));
 						}
 					}
 				}
 			}
 			
 			//
 			// get default screen
 			//
 			if (!($vn_type_id = $t_subject->getTypeID())) {
 				$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger);
 			}
 			
 			if (!$t_ui || !$t_ui->getPrimaryKey()) {
 				$this->notification->addNotification(_t('There is no configuration available for this editor. Check your system configuration and ensure there is at least one valid configuration for this type of editor.'), __NOTIFICATION_TYPE_ERROR__);
				
				$this->postError(1260, _t('There is no configuration available for this editor. Check your system configuration and ensure there is at least one valid configuration for this type of editor.'),"BaseEditorController->Edit()");
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
			
			if ($vn_subject_id) { $this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id); } 	// set last edited
			
			# trigger "EditItem" hook 
			$this->opo_app_plugin_manager->hookEditItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
			$this->render('screen_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
 		 */
 		public function Save($pa_options=null) {
 			list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id) = $this->_initView($pa_options);
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
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking') && $vn_subject_id) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) < __CA_ACL_EDIT_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 				
 			if($vn_above_id) {
 				// Convert "above" id (the id of the record we're going to make the newly created record parent of
 				if (($t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name)) && $t_instance->load($vn_above_id)) {
 					$vn_parent_id = $t_instance->get($vs_parent_id_fld = $t_instance->getProperty('HIERARCHY_PARENT_ID_FLD'));
 					$this->request->setParameter($vs_parent_id_fld, $vn_parent_id);
 					$this->view->setVar('parent_id', $vn_parent_id);
 				}
 			}
 			
 			$vs_auth_table_name = $this->ops_table_name;
 			if (in_array($this->ops_table_name, array('ca_representation_annotations'))) { $vs_auth_table_name = 'ca_objects'; }
 			 			
 			if(!sizeof($_POST)) {
 				$this->notification->addNotification(_t("Cannot save using empty request. Are you using a bookmark?"), __NOTIFICATION_TYPE_ERROR__);	
 				$this->render('screen_html.php');
 				return;
 			}
 			
 			// set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
 			$vn_context_id = null;
 			if ($vs_idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
 				if ($vn_subject_id > 0) {
 					$this->view->setVar('_context_id', $vn_context_id = $t_subject->get($vs_idno_context_field));
 				} else {
 					if ($vn_parent_id > 0) {
 						$t_parent = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
 						if ($t_parent->load($vn_parent_id)) {
 							$this->view->setVar('_context_id', $vn_context_id = $t_parent->get($vs_idno_context_field));
 						}
 					}
 				}
 				
 				if ($vn_context_id) { $t_subject->set($vs_idno_context_field, $vn_context_id); }
 			}
 			
 			if (!($vs_type_name = $t_subject->getTypeName())) {
 				$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
 			}
 			
 			if ($vn_subject_id && !$t_subject->getPrimaryKey()) {
 				$this->notification->addNotification(_t("%1 does not exist", $vs_type_name), __NOTIFICATION_TYPE_ERROR__);	
 				return;
 			}
 			
 			$vb_is_insert = !$t_subject->getPrimaryKey();
 			
 			# trigger "BeforeSaveItem" hook 
			$this->opo_app_plugin_manager->hookBeforeSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => $vb_is_insert));
 			
 			$vb_save_rc = false;
 			$va_opts = array_merge($pa_options, array('ui_instance' => $t_ui));
 			if ($this->_beforeSave($t_subject, $vb_is_insert)) {
 				if ($vb_save_rc = $t_subject->saveBundlesForScreen($this->request->getActionExtra(), $this->request, $va_opts)) {
 					$this->_afterSave($t_subject, $vb_is_insert);
 				}
 			}
			$this->view->setVar('t_ui', $t_ui);
		
			if(!$vn_subject_id) {
				$vn_subject_id = $t_subject->getPrimaryKey();
				if (!$vb_save_rc) {
					$vs_message = _t("Could not save %1", $vs_type_name);
				} else {
					$vs_message = _t("Added %1", $vs_type_name);
					$this->request->setParameter($t_subject->primaryKey(), $vn_subject_id, 'GET');
					$this->view->setVar($t_subject->primaryKey(), $vn_subject_id);
					$this->view->setVar('subject_id', $vn_subject_id);
					$this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id);	// set last edited
					
					// Set ACL for newly created record
					if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
						$t_subject->setACLUsers(array($this->request->getUserID() => __CA_ACL_EDIT_DELETE_ACCESS__));
						$t_subject->setACLWorldAccess($t_subject->getAppConfig()->get('default_item_access_level'));
					}
					
					// If "above_id" is set then, we want to load the record pointed to by it and set its' parent to be the newly created record
					// The newly created record's parent is already set to be the current parent of the "above_id"; the net effect of all of this
					// is to insert the newly created record between the "above_id" record and its' current parent.
					if ($vn_above_id && ($t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true)) && $t_instance->load($vn_above_id)) {
						$t_instance->setMode(ACCESS_WRITE);
						$t_instance->set('parent_id', $vn_subject_id);
						$t_instance->update();
						
						if ($t_instance->numErrors()) {
							$this->notification->addNotification($t_instance->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);	
						}
					}
				}
				
			} else {
 				$vs_message = _t("Saved changes to %1", $vs_type_name);
 			}
 			
 			$va_errors = $this->request->getActionErrors();							// all errors from all sources
 			$va_general_errors = $this->request->getActionErrors('general');		// just "general" errors - ones that are not attached to a specific part of the form
 			if (is_array($va_general_errors) && sizeof($va_general_errors) > 0) {
 				foreach($va_general_errors as $o_e) {
 					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
 				}
			}
 			if(sizeof($va_errors) - sizeof($va_general_errors) > 0) {
 				$va_error_list = array();
 				$vb_no_save_error = false;
 				foreach($va_errors as $o_e) {
 					$va_error_list[$o_e->getErrorDescription()] = "<li>".$o_e->getErrorDescription()."</li>\n";
 					
 					switch($o_e->getErrorNumber()) {
 						case 1100:	// duplicate/invalid idno
 							if (!$vn_subject_id) {		// can't save new record if idno is not valid (when updating everything but idno is saved if it is invalid)
 								$vb_no_save_error = true;
 							}
 							break;
 					}
 				}
 				if ($vb_no_save_error) {
 					$this->notification->addNotification(_t("There are errors preventing <strong>ALL</strong> information from being saved. Correct the problems and click \"save\" again.\n<ul>").join("\n", $va_error_list)."</ul>", __NOTIFICATION_TYPE_ERROR__);
 				} else {
 					$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);	
 					$this->notification->addNotification(_t("There are errors preventing information in specific fields from being saved as noted below.\n<ul>").join("\n", $va_error_list)."</ul>", __NOTIFICATION_TYPE_ERROR__);
 				}
 			} else {
				$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);	
 				$this->opo_result_context->invalidateCache();
  				$this->opo_result_context->saveContext();
 			}
 			# trigger "SaveItem" hook 
 		
			$this->opo_app_plugin_manager->hookSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => $vb_is_insert));
 			
 			if (method_exists($this, "postSave")) {
 				$this->postSave($t_subject, $vb_is_insert);
 			}
 			$this->render('screen_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Performs two-step delete of an existing record. The first step is a confirmation dialog, followed by actual deletion upon user confirmation
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function Delete($pa_options=null) {
 			list($vn_subject_id, $t_subject, $t_ui) = $this->_initView($pa_options);
 			
 			if (!$vn_subject_id) { return; }
 			
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
 			
 			if (!$vs_type_name = $t_subject->getTypeName()) {
 				$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) < __CA_ACL_EDIT_DELETE_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			// get parent_id, if it exists, prior to deleting so we can
 			// set the browse_last_id parameter to something sensible
 			$vn_parent_id = null;
 			if ($vs_parent_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD')) {
 				$vn_parent_id = $t_subject->get($vs_parent_fld);
 			}
 			
 			if ($vn_subject_id && !$t_subject->getPrimaryKey()) {
 				$this->notification->addNotification(_t("%1 does not exist", $vs_type_name), __NOTIFICATION_TYPE_ERROR__);	
 				return;
 			}
 			
 			// Don't allow deletion of roots in simple mono-hierarchies... that's bad.
 			if (!$vn_parent_id && (in_array($t_subject->getProperty('HIERARCHY_TYPE'), array(__CA_HIER_TYPE_SIMPLE_MONO__, __CA_HIER_TYPE_MULTI_MONO__)))) {
 				$this->notification->addNotification(_t("Cannot delete root of hierarchy"), __NOTIFICATION_TYPE_ERROR__);	
 				return;
 			}
 			
 			if ($vb_confirm = ($this->request->getParameter('confirm', pInteger) == 1) ? true : false) {
 				$vb_we_set_transation = false;
 				if (!$t_subject->inTransaction()) {
 					$o_t = new Transaction();
 					$t_subject->setTransaction($o_t);
 					$vb_we_set_transation = true;
 				}
 				
 				// Do we need to move relationships?
 				if (($vn_remap_id =  $this->request->getParameter('remapToID', pInteger)) && ($this->request->getParameter('referenceHandling', pString) == 'remap')) {
 					switch($t_subject->tableName()) {
 						case 'ca_relationship_types':
 							if ($vn_c = $t_subject->moveRelationshipsToType($vn_remap_id)) {
 								$t_target = new ca_relationship_types($vn_remap_id);
 								$this->notification->addNotification(($vn_c == 1) ? _t("Transferred %1 relationship to type <em>%2</em>", $vn_c, $t_target->getLabelForDisplay()) : _t("Transferred %1 relationships to type <em>%2</em>", $vn_c, $t_target->getLabelForDisplay()), __NOTIFICATION_TYPE_INFO__);	
 							}
 							break;
 						default:
							$va_tables = array(
								'ca_objects', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops', 'ca_object_representations'
							);
							
							$vn_c = 0;
							foreach($va_tables as $vs_table) {
								$vn_c += $t_subject->moveRelationships($vs_table, $vn_remap_id);
							}
							
							if ($vn_c > 0) {
								$t_target = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
								$t_target->load($vn_remap_id);
								$this->notification->addNotification(($vn_c == 1) ? _t("Transferred %1 relationship to <em>%2</em> (%3)", $vn_c, $t_target->getLabelForDisplay(), $t_target->get($t_target->getProperty('ID_NUMBERING_ID_FIELD'))) : _t("Transferred %1 relationships to <em>%2</em> (%3)", $vn_c, $t_target->getLabelForDisplay(), $t_target->get($t_target->getProperty('ID_NUMBERING_ID_FIELD'))), __NOTIFICATION_TYPE_INFO__);	
							}
						break;
					}
				}
 				
 				$t_subject->setMode(ACCESS_WRITE);
 				
 				$vb_rc = false;
 				if ($this->_beforeDelete($t_subject)) {
 					if ($vb_rc = $t_subject->delete(true)) {
 						$this->_afterDelete($t_subject);
 					}
 				}
 				
 				if ($vb_we_set_transation) {
 					if (!$vb_rc) {
 						$o_t->rollbackTransaction();	
 					} else {
 						$o_t->commitTransaction();
 					}
 				}
 			}
 			$this->view->setVar('confirmed', $vb_confirm);
 			if ($t_subject->numErrors()) {
 				foreach($t_subject->errors() as $o_e) {
 					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);	
 				}
 			} else {
 				if ($vb_confirm) {
 					$this->notification->addNotification(_t("%1 was deleted", caUcFirstUTF8Safe($vs_type_name)), __NOTIFICATION_TYPE_INFO__);
 					
 					// update result list since it has changed
 					$this->opo_result_context->removeIDFromResults($vn_subject_id);
 					$this->opo_result_context->invalidateCache();
  					$this->opo_result_context->saveContext();
  				
  				
 					// clear subject_id - it's no longer valid
 					$t_subject->clear();
 					$this->view->setVar($t_subject->primaryKey(), null);
 					$this->request->setParameter($t_subject->primaryKey(), null, 'PATH');
 					
 					// set last browse id for hierarchy browser
 					$this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_parent_id);

					// Clear out row_id so sidenav is disabled
					$this->request->setParameter($t_subject->primaryKey(), null, 'POST');

					# trigger "DeleteItem" hook 
					$this->opo_app_plugin_manager->hookDeleteItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
 				}
 			}
 			
			$this->view->setVar('subject_name', $t_subject->getLabelForDisplay(false));
 			
 			$this->render('delete_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generates display summary of record data based upon a bundle display for screen (HTML)
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function Summary($pa_options=null) {
 			JavascriptLoadManager::register('tableList');
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			
 			//
 			// Is record of correct type?
 			// 
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_READONLY__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) == __CA_ACL_NO_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			$t_display = new ca_bundle_displays();
 			$va_displays = $t_display->getBundleDisplays(array('table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'restrictToTypes' => array($t_subject->getTypeID())));
 			
 			if ((!($vn_display_id = $this->request->getParameter('display_id', pInteger))) || !isset($va_displays[$vn_display_id])) {
 				if ((!($vn_display_id = $this->request->user->getVar($t_subject->tableName().'_summary_display_id')))  || !isset($va_displays[$vn_display_id])) {
 					$va_tmp = array_keys($va_displays);
 					$vn_display_id = $va_tmp[0];
 				}
 			}
 			
			$this->view->setVar('bundle_displays', $va_displays);
			$this->view->setVar('t_display', $t_display);
			
			// Check validity and access of specified display
 			if ($t_display->load($vn_display_id) && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
				$this->view->setVar('display_id', $vn_display_id);
				
				$va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true));
				
				$va_display_list = array();
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_settings = caUnserializeForDatabase($va_display_item['settings']);
					
					// get column header text
					$vs_header = $va_display_item['display'];
					if (isset($va_settings['label']) && is_array($va_settings['label'])) {
						$va_tmp = caExtractValuesByUserLocale(array($va_settings['label']));
						if ($vs_tmp = array_shift($va_tmp)) { $vs_header = $vs_tmp; }
					}
					
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name'],
						'display' => $vs_header,
						'settings' => $va_settings
					);
				}
				
				$this->view->setVar('placements', $va_display_list);
				
				$this->request->user->setVar($t_subject->tableName().'_summary_display_id', $vn_display_id);
			} else {
				$this->view->setVar('display_id', null);
				$this->view->setVar('placements', array());
			}
 			$this->render('summary_html.php');
 		}
		# -------------------------------------------------------
		/**
 		 * Generates display summary of record data based upon a bundle display for print (PDF)
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
		public function PrintSummary($pa_options=null) {
			require_once(__CA_LIB_DIR__."/core/Print/html2pdf/html2pdf.class.php");

			JavascriptLoadManager::register('tableList');
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			
 			//
 			// Is record of correct type?
 			// 
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_READONLY__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) == __CA_ACL_NO_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			
 			$t_display = new ca_bundle_displays();
 			$va_displays = $t_display->getBundleDisplays(array('table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'restrictToTypes' => array($t_subject->getTypeID())));

 			if ((!($vn_display_id = $this->request->getParameter('display_id', pInteger))) || (!isset($va_displays[$vn_display_id]))) {
 				if ((!($vn_display_id = $this->request->user->getVar($t_subject->tableName().'_summary_display_id'))) || !isset($va_displays[$vn_display_id])) {
 					$va_tmp = array_keys($va_displays);
 					$vn_display_id = $va_tmp[0];
 				}
 			}

 			$this->view->setVar('t_display', $t_display);
 			$this->view->setVar('bundle_displays', $va_displays);
 			
 			// Check validity and access of specified display
 			if ($t_display->load($vn_display_id) && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {			
				$this->view->setVar('display_id', $vn_display_id);
				
				$va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true));
				$va_display_list = array();
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_settings = caUnserializeForDatabase($va_display_item['settings']);
					
					// get column header text
					$vs_header = $va_display_item['display'];
					if (isset($va_settings['label']) && is_array($va_settings['label'])) {
						if ($vs_tmp = array_shift(caExtractValuesByUserLocale(array($va_settings['label'])))) { $vs_header = $vs_tmp; }
					}
					
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name'],
						'display' => $vs_header,
						'settings' => $va_settings
					);
				}
				
				$this->view->setVar('placements', $va_display_list);
	
				$this->request->user->setVar($t_subject->tableName().'_summary_display_id', $vn_display_id);
				$vs_format = $this->request->config->get("summary_print_format");
			} else {
				$this->view->setVar('display_id', null);
				$this->view->setVar('placements', array());
			}
			
			try {
				$vs_content = $this->render('print_summary_html.php');
				$vo_html2pdf = new HTML2PDF('P',$vs_format,'en');
				$vo_html2pdf->setDefaultFont("dejavusans");
				$vo_html2pdf->WriteHTML($vs_content);
				$vo_html2pdf->Output('summary.pdf');
				$vb_printed_properly = true;
			} catch (Exception $e) {
				$vb_printed_properly = false;
				$o_event_log = new Eventlog();
				$o_event_log->log(array('CODE' => 'DEBG', 'MESSAGE' => $vs_msg = _t("Could not generate PDF: %1", preg_replace('![^A-Za-z0-9 \-\?\/\.]+!', ' ', $e->getMessage())), 'SOURCE' => 'BaseEditorController->PrintSummary()'));
				$this->postError(3100, $vs_msg,"BaseEditorController->PrintSummary()");
			}
		}
 		# -------------------------------------------------------
 		/**
 		 * Returns change log display for currently edited record in current view inherited from ActionController
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function Log($pa_options=null) {
 			JavascriptLoadManager::register('tableList');
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			
 			//
 			// Is record of correct type?
 			// 
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_READONLY__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) == __CA_ACL_NO_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			$this->render('log_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function Access($pa_options=null) {
 			JavascriptLoadManager::register('tableList');
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			
 			//
 			// Is record of correct type?
 			// 
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_READONLY__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) == __CA_ACL_NO_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			if ((!$this->request->user->canDoAction('can_change_acl_'.$t_subject->tableName()))) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2570?r='.urlencode($this->request->getFullUrlPath()));
 				return; 
 			}
 			
 			$this->render('access_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function SetAccess($pa_options=null) {
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			
 			if ((!$t_subject->isSaveable($this->request)) || (!$this->request->user->canDoAction('can_change_acl_'.$t_subject->tableName()))) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2570?r='.urlencode($this->request->getFullUrlPath()));
 				return; 
 			}
			$vs_form_prefix = $this->request->getParameter('_formName', pString);
			
			// Save user ACL's
			$va_users_to_set = array();
			foreach($_REQUEST as $vs_key => $vs_val) { 
				if (preg_match("!^{$vs_form_prefix}_user_id(.*)$!", $vs_key, $va_matches)) {
					$vn_user_id = (int)$this->request->getParameter($vs_form_prefix.'_user_id'.$va_matches[1], pInteger);
					$vn_access = $this->request->getParameter($vs_form_prefix.'_user_access_'.$va_matches[1], pInteger);
					if ($vn_access >= 0) {
						$va_users_to_set[$vn_user_id] = $vn_access;
					}
				}
			}
			$t_subject->setACLUsers($va_users_to_set);
 			
 			// Save group ACL's
 			$va_groups_to_set = array();
			foreach($_REQUEST as $vs_key => $vs_val) { 
				if (preg_match("!^{$vs_form_prefix}_group_id(.*)$!", $vs_key, $va_matches)) {
					$vn_group_id = (int)$this->request->getParameter($vs_form_prefix.'_group_id'.$va_matches[1], pInteger);
					$vn_access = $this->request->getParameter($vs_form_prefix.'_group_access_'.$va_matches[1], pInteger);
					if ($vn_access >= 0) {
						$va_groups_to_set[$vn_group_id] = $vn_access;
					}
				}
			}
			$t_subject->setACLUserGroups($va_groups_to_set);
			
			// Save "world" ACL
			$t_subject->setACLWorldAccess($this->request->getParameter("{$vs_form_prefix}_access_world", pInteger));
			
			// Propagate ACL settings to records that inherit from this one
			if ((bool)$t_subject->getProperty('SUPPORTS_ACL_INHERITANCE')) {
				ca_acl::applyACLInheritanceToChildrenFromRow($t_subject);
				if (is_array($va_inheritors = $t_subject->getProperty('ACL_INHERITANCE_LIST'))) {
					foreach($va_inheritors as $vs_inheritor_table) {
						ca_acl::applyACLInheritanceToRelatedFromRow($t_subject, $vs_inheritor_table);
					}
				}
			}
			
			// Set ACL-related intrinsic fields
			if ($t_subject->hasField('acl_inherit_from_ca_collections') || $t_subject->hasField('acl_inherit_from_parent')) {
				$t_subject->setMode(ACCESS_WRITE);
				if ($t_subject->hasField('acl_inherit_from_ca_collections')) {
					$t_subject->set('acl_inherit_from_ca_collections', $this->request->getParameter('acl_inherit_from_ca_collections', pString));
				}
				if ($t_subject->hasField('acl_inherit_from_parent')) {
					$t_subject->set('acl_inherit_from_parent', $this->request->getParameter('acl_inherit_from_parent', pString));
				}
				$t_subject->update();
				
				if ($t_subject->numErrors()) {
					$this->postError(1250, _t('Could not set ACL inheritance settings: %1', join("; ", $t_subject->getErrors())),"BaseEditorController->SetAccess()");
				}
			}
 			$this->Access();
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function ChangeType($pa_options=null) {
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			if ($this->request->user->canDoAction("can_change_type_".$t_subject->tableName())) {
				if (method_exists($t_subject, "changeType")) {
					$this->opo_app_plugin_manager->hookBeforeSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => false));
 			
					if (!$t_subject->changeType($vn_new_type_id = $this->request->getParameter('type_id', pInteger))) {
						// post error
						$this->notification->addNotification(_t('Could not set type to <em>%1</em>: %2', caGetListItemForDisplay($t_subject->getTypeListCode(), $vn_new_type_id), join("; ", $t_subject->getErrors())), __NOTIFICATION_TYPE_ERROR__);
					} else {
						$this->notification->addNotification(_t('Set type to <em>%1</em>', $t_subject->getTypeName()), __NOTIFICATION_TYPE_INFO__);
					}
					$this->opo_app_plugin_manager->hookSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => false));
 			
				}
			} else {
				$this->notification->addNotification(_t('Cannot change type'), __NOTIFICATION_TYPE_ERROR__);
			}
 			$this->Edit();
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
 			
 			$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
 			$vn_subject_id = $this->request->getParameter($t_subject->primaryKey(), pInteger);
 			
 			if (!$vn_subject_id || !$t_subject->load($vn_subject_id)) {
 				// empty (ie. new) rows don't have a type_id set, which means we'll have no idea which attributes to display
 				// so we get the type_id off of the request
 				if (!$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger)) {
 					$vn_type_id = null;
 				}
 				
 				// then set the empty row's type_id
 				$t_subject->set($t_subject->getTypeFieldName(), $vn_type_id);
 				
 				// then reload the definitions (which includes bundle specs)
 				$t_subject->reloadLabelDefinitions();
 			}
 			
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
 				$t_ui = ca_editor_uis::loadDefaultUI($this->ops_table_name, $this->request, $t_subject->getTypeID());
 			}
 			
 			$this->view->setVar($t_subject->primaryKey(), $vn_subject_id);
 			$this->view->setVar('subject_id', $vn_subject_id);
 			$this->view->setVar('t_subject', $t_subject);
 			
 			MetaTagManager::setWindowTitle(_t("Editing %1 : %2", ($vs_type = $t_subject->getTypeName()) ? $vs_type : $t_subject->getProperty('NAME_SINGULAR'), ($vn_subject_id) ? $t_subject->getLabelForDisplay(true) : _t('new %1', $t_subject->getTypeName())));
 			
 			if ($vs_parent_id_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD')) {
 				$this->view->setVar('parent_id', $vn_parent_id = $this->request->getParameter($vs_parent_id_fld, pInteger));
 				
 				// The "above_id" is set when the new record we're creating is to be inserted *above* an existing record (eg. made
 				// the parent of another record). It will be set to the record we're inserting above. We ignore it if set when editing
 				// an existing record since it is only relevant for newly created records.
 				if (!$vn_subject_id) {
 					$this->view->setVar('above_id', $vn_above_id = $this->request->getParameter('above_id', pInteger));
 					$t_subject->set($vs_parent_id_fld, $vn_parent_id);
 					
 					$t_parent = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
 					if ($t_parent->load($vn_parent_id)) {
 						$t_subject->set('idno', $t_parent->get('idno'));
 					}
 				}
 				return array($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id);
 			}
 			
 			return array($vn_subject_id, $t_subject, $t_ui);
 		}
 		# -------------------------------------------------------
 		# File attribute bundle download
 		# -------------------------------------------------------
 		/**
 		 * Initiates user download of file stored in a file attribute, returning file in response to request.
 		 * Adds download output to response directly. No view is used.
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function DownloadFile() {
 			if (!($pn_value_id = $this->request->getParameter('value_id', pInteger))) { return; }
 			$t_attr_val = new ca_attribute_values($pn_value_id);
 			if (!$t_attr_val->getPrimaryKey()) { return; }
 			$t_attr = new ca_attributes($t_attr_val->get('attribute_id'));
 		
 			$vn_table_num = $this->opo_datamodel->getTableNum($this->ops_table_name);
 			if ($t_attr->get('table_num') !=  $vn_table_num) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$t_element = new ca_metadata_elements($t_attr->get('element_id'));
 			$this->request->setParameter($this->opo_datamodel->getTablePrimaryKeyName($vn_table_num), $t_attr->get('row_id'));
 			
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			$ps_version = $this->request->getParameter('version', pString);
 			
 			//
 			// Does user have access to bundle?
 			//
 			if (($this->request->user->getBundleAccessLevel($this->ops_table_name, $t_element->get('element_code'))) < __CA_BUNDLE_ACCESS_READONLY__) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to type?
 			//
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) == __CA_ACL_NO_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			$t_attr_val->useBlobAsFileField(true);
 			
 			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
 			
 			// get value
 			$t_element = new ca_metadata_elements($t_attr_val->get('element_id'));
 			// check that value is a file attribute
 			if ($t_element->get('datatype') != 15) { 	// 15=file
 				return;
 			}
 			
 			$o_view->setVar('file_path', $t_attr_val->getFilePath('value_blob'));
 			$o_view->setVar('file_name', ($vs_name = trim($t_attr_val->get('value_longtext2'))) ? $vs_name : _t("downloaded_file"));
 			
 			// send download
 			$this->response->addContent($o_view->render('ca_attributes_download_file.php'));
 		}
 		# -------------------------------------------------------
 		# Media attribute bundle download
 		# -------------------------------------------------------
 		/**
 		 * Initiates user download of media stored in a media attribute, returning file in response to request.
 		 * Adds download output to response directly. No view is used.
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function DownloadMedia($pa_options=null) {
 			if (!($pn_value_id = $this->request->getParameter('value_id', pInteger))) { return; }
 			$t_attr_val = new ca_attribute_values($pn_value_id);
 			if (!$t_attr_val->getPrimaryKey()) { return; }
 			$t_attr = new ca_attributes($t_attr_val->get('attribute_id'));
 		
 			$vn_table_num = $this->opo_datamodel->getTableNum($this->ops_table_name);
 			if ($t_attr->get('table_num') !=  $vn_table_num) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$t_element = new ca_metadata_elements($t_attr->get('element_id'));
 			$this->request->setParameter($this->opo_datamodel->getTablePrimaryKeyName($vn_table_num), $t_attr->get('row_id'));
 			
 			list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
 			$ps_version = $this->request->getParameter('version', pString);
 			
 			//
 			// Does user have access to bundle?
 			//
 			if (($this->request->user->getBundleAccessLevel($this->ops_table_name, $t_element->get('element_code'))) < __CA_BUNDLE_ACCESS_READONLY__) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to type?
 			//
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Does user have access to row?
 			//
 			if ($t_subject->getAppConfig()->get('perform_item_level_access_checking')) {
 				if ($t_subject->checkACLAccessForUser($this->request->user) == __CA_ACL_NO_ACCESS__) {
 					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
 					return;
 				}
 			}
 			
 			$t_attr_val->useBlobAsMediaField(true);
 			if (!in_array($ps_version, $t_attr_val->getMediaVersions('value_blob'))) { $ps_version = 'original'; }
 			
 			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
 			
 			// get value
 			$t_element = new ca_metadata_elements($t_attr_val->get('element_id'));
 			
 			// check that value is a media attribute
 			if ($t_element->get('datatype') != 16) { 	// 16=media
 				return;
 			}
 			
 			$vs_path = $t_attr_val->getMediaPath('value_blob', $ps_version);
 			$vs_path_ext = pathinfo($vs_path, PATHINFO_EXTENSION);
 			if ($vs_name = trim($t_attr_val->get('value_longtext2'))) {
 				$vs_file_name = pathinfo($vs_name, PATHINFO_FILENAME);
 				$vs_name = "{$vs_file_name}.{$vs_path_ext}";
 			} else {
 				$vs_name = _t("downloaded_file.%1", $vs_path_ext);
 			}
 			
 			$o_view->setVar('file_path', $vs_path);
 			$o_view->setVar('file_name', $vs_name);
 			
 			// send download
 			$this->response->addContent($o_view->render('ca_attributes_download_media.php'));
 		}
 		# -------------------------------------------------------
 		# 
 		# -------------------------------------------------------
 		/**
 		 * Returns content for overlay containing details for media attribute
 		 *
 		 * Expects the following request parameters: 
 		 *		value_id = the id of the attribute value (ca_attribute_values) record to display
 		 *
 		 *	Optional request parameters:
 		 *		version = The version of the representation to display. If omitted the display version configured in media_display.conf is used
 		 *
 		 */ 
 		public function GetMediaInfo() {
 			$pn_value_id 	= $this->request->getParameter('value_id', pInteger);
 			
 			$this->response->addContent($this->getMediaAttributeViewerHTMLBundle($this->request, array('display' => 'media_overlay', 'value_id' => $pn_value_id, 'containerID' => 'caMediaPanelContentArea')));
 		}
		# ------------------------------------------------------
		/**
		 * 
		 */
		public function getMediaAttributeViewerHTMLBundle($po_request, $pa_options=null) {
			$va_access_values = (isset($pa_options['access']) && is_array($pa_options['access'])) ? $pa_options['access'] : array();	
			$vs_display_type = (isset($pa_options['display']) && $pa_options['display']) ? $pa_options['display'] : 'media_overlay';	
			$vs_container_dom_id = (isset($pa_options['containerID']) && $pa_options['containerID']) ? $pa_options['containerID'] : null;	
			
			$pn_value_id = (isset($pa_options['value_id']) && $pa_options['value_id']) ? $pa_options['value_id'] : null;
			
			$t_attr_val = new ca_attribute_values();
			$t_attr_val->load($pn_value_id);
			$t_attr_val->useBlobAsMediaField(true);
			
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
			
			$o_view->setVar('containerID', $vs_container_dom_id);
			
			$va_rep_display_info = caGetMediaDisplayInfo('media_overlay', $t_attr_val->getMediaInfo('value_blob', 'INPUT', 'MIMETYPE'));
			$va_rep_display_info['poster_frame_url'] = $t_attr_val->getMediaUrl('value_blob', $va_rep_display_info['poster_frame_version']);
			
			$o_view->setVar('display_options', $va_rep_display_info);
			$o_view->setVar('representation_id', $pn_representation_id);
			$o_view->setVar('t_attribute_value', $t_attr_val);
			$o_view->setVar('versions', $va_versions = $t_attr_val->getMediaVersions('value_blob'));
			
			$t_media = new Media();
	
			$ps_version 	= $po_request->getParameter('version', pString);
			if (!in_array($ps_version, $va_versions)) { 
				if (!($ps_version = $va_rep_display_info['display_version'])) { $ps_version = null; }
			}
			print "v=$ps_version";
			$o_view->setVar('version', $ps_version);
			$o_view->setVar('version_info', $t_attr_val->getMediaInfo('value_blob', $ps_version));
			$o_view->setVar('version_type', $t_media->getMimetypeTypename($t_attr_val->getMediaInfo('value_blob', $ps_version, 'MIMETYPE')));
			$o_view->setVar('version_mimetype', $t_attr_val->getMediaInfo('value_blob', $ps_version, 'MIMETYPE'));
			$o_view->setVar('mimetype', $t_attr_val->getMediaInfo('value_blob', 'INPUT', 'MIMETYPE'));			
			
			
			return $o_view->render('media_attribute_viewer_html.php');
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
 			list($vn_subject_id, $t_subject, $t_ui) = $this->_initView($pa_options);
 			if (!$this->request->isLoggedIn()) { return array(); }
 			
 			if (!($vn_type_id = $t_subject->getTypeID())) {
 				$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger);
 			}
 			$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $pa_params['default']['module'], $pa_params['default']['controller'], $pa_params['default']['action'],
 				isset($pa_params['parameters']) ? $pa_params['parameters'] : null,
 				isset($pa_params['requires']) ? $pa_params['requires'] : null,
 				($vn_subject_id > 0) ? false : true,
 				array('hideIfNoAccess' => isset($pa_params['hideIfNoAccess']) ? $pa_params['hideIfNoAccess'] : false)
 			);
 			
 			if (!$this->request->getActionExtra()) {
 				$this->request->setActionExtra($va_nav['defaultScreen']);
 			}
 			
 			return $va_nav['fragment'];
 		}
		# -------------------------------------------------------
		# Navigation (menu bar)
		# -------------------------------------------------------
		/**
 		 * Returns navigation fragment for types and subtypes of a given primary item type (Eg. ca_objects). Used to generate dynamic type menus 
 		 * from database by AppNavigation class. 
 		 *
 		 * @param array $pa_params Array of parameters used to generate menu
 		 * @return array List of types with subtypes ready for inclusion in a menu spec
 		 */
 		public function _genTypeNav($pa_params) {
 			$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name, true);
 			
 			$t_list = new ca_lists();
 			$t_list->load(array('list_code' => $t_subject->getTypeListCode()));
 			
 			$t_list_item = new ca_list_items();
 			$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
 			$va_hier = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
 			
 			$vn_sort_type = $t_list->get('default_sort');
 			
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			
 			$va_types = array();
 			if (is_array($va_hier)) {
 				
 				$va_types_by_parent_id = array();
 				$vn_root_id = $t_list->getRootItemIDForList($t_subject->getTypeListCode());

				foreach($va_hier as $vn_item_id => $va_item) {
					if ($vn_item_id == $vn_root_id) { continue; } // skip root
					$va_types_by_parent_id[$va_item['parent_id']][] = $va_item;
				}
				foreach($va_hier as $vn_item_id => $va_item) {
					if (is_array($va_restrict_to_types) && !in_array($vn_item_id, $va_restrict_to_types)) { continue; }
					if ($va_item['parent_id'] != $vn_root_id) { continue; }
					// does this item have sub-items?
					$va_subtypes = array();
					if (
						!(bool)$this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_shows_top_level_types_only')
						&&
						!(bool)$this->getRequest()->config->get($this->ops_table_name.'_enforce_strict_type_hierarchy')
					) {
						if (isset($va_item['item_id']) && isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
							$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id, $vn_sort_type, $va_restrict_to_types);
						}
					} 
					
					switch($vn_sort_type) {
						case 0:			// label
						default:
							$vs_key = $va_item['name_singular'];
							break;
						case 1:			// rank
							$vs_key = sprintf("%08d", (int)$va_item['rank']);
							break;
						case 2:			// value
							$vs_key = $va_item['item_value'];
							break;
						case 3:			// identifier
							$vs_key = $va_item['idno_sort'];
							break;
					}
					$va_types[$vs_key][] = array(
						'displayName' => $va_item['name_singular'],
						'parameters' => array(
							'type_id' => $va_item['item_id']
						),
						'is_enabled' => $va_item['is_enabled'],
						'navigation' => $va_subtypes
					);
				}
				ksort($va_types);
			}
			
			$va_types_proc = array();
			foreach($va_types as $vs_sort_key => $va_items) {
				foreach($va_items as $vn_i => $va_item) {
					$va_types_proc[] = $va_item;
				}
			}
			
 			return $va_types_proc;
 		}
		# ------------------------------------------------------------------
		/**
 		 * Returns navigation fragment for subtypes of a given primary item type (Eg. ca_objects). Used to generate dynamic type menus 
 		 * from database by AppNavigation class. Called via _genTypeNav(), which is in turn called by AppNavigation.
 		 *
 		 * @param array $pa_subtypes Array of subtypes
 		 * @param array $pa_types_by_parent_id Array of subtypes organized by parent
 		 * @param int $pn_sort_type Integer code indicating how to sort types in the menu
 		 * @return array List of subtypes ready for inclusion in a menu spec
 		 */
		private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id, $pn_sort_type, $pa_restrict_to_types=null) {
			$va_subtypes = array();
			foreach($pa_subtypes as $vn_i => $va_type) {
				if (is_array($pa_restrict_to_types) && !in_array($va_type['item_id'], $pa_restrict_to_types)) { continue; }
				if (isset($pa_types_by_parent_id[$va_type['item_id']]) && is_array($pa_types_by_parent_id[$va_type['item_id']])) {
					$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id, $pn_sort_type, $pa_restrict_to_types);
				} else {
					$va_subsubtypes = array();
				}
				
				switch($pn_sort_type) {
					case 0:			// label
					default:
						$vs_key = $va_type['name_singular'];
						break;
					case 1:			// rank
						$vs_key = sprintf("%08d", (int)$va_type['rank']);
						break;
					case 2:			// value
						$vs_key = $va_type['item_value'];
						break;
					case 3:			// identifier
						$vs_key = $va_type['idno_sort'];
						break;
				}
				
				$va_subtypes[$vs_key][$va_type['item_id']] = array(
					'displayName' => $va_type['name_singular'],
					'parameters' => array(
						'type_id' => $va_type['item_id']
					),
					'is_enabled' => $va_type['is_enabled'],
					'navigation' => $va_subsubtypes
				);
			}
			
			ksort($va_subtypes);
			$va_subtypes_proc = array();
			
			foreach($va_subtypes as $vs_sort_key => $va_type) {
				foreach($va_type as $vn_item_id => $va_item) {
					if (is_array($pa_restrict_to_types) && !in_array($vn_item_id, $pa_restrict_to_types)) { continue; }
					$va_subtypes_proc[$vn_item_id] = $va_item;
				}
			}
			
			
			return $va_subtypes_proc;
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
		/**
		 * Exports item using specified mapping. Export formats are typically, but not necessarily, XML
		 * Export data is rendered into the current view inherited from ActionController
		 */
		public function exportItem() {
 			list($vn_subject_id, $t_subject) = $this->_initView();
			$pn_mapping_id = $this->request->getParameter('mapping_id', pInteger);
			
			//$o_export = new DataExporter();
			//$this->view->setVar('export_mimetype', $o_export->exportMimetype($pn_mapping_id));
			//$this->view->setVar('export_data', $o_export->export($pn_mapping_id, $t_subject, null, array('returnOutput' => true, 'returnAsString' => true)));
			//$this->view->setVar('export_filename', preg_replace('![\W]+!', '_', substr($t_subject->getLabelForDisplay(), 0, 40).'_'.$o_export->exportTarget($pn_mapping_id)).'.'.$o_export->exportFileExtension($pn_mapping_id));
			
			$this->render('../generic/export_xml.php');
		}
		# ------------------------------------------------------------------
		# Watch list actions
 		# ------------------------------------------------------------------
 		/**
 		 * Add item to user's watch list. Intended to be called via ajax, and JSON response is returned in the current view inherited from ActionController
 		 */
 		public function toggleWatch() {
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			require_once(__CA_MODELS_DIR__.'/ca_watch_list.php');
 			
 			$va_errors = array();
			$t_watch_list = new ca_watch_list();
			$vn_user_id =  $this->request->user->get("user_id");
			
			if ($t_watch_list->isItemWatched($vn_subject_id, $t_subject->tableNum(), $vn_user_id)) {
				if($t_watch_list->load(array('row_id' => $vn_subject_id, 'user_id' => $vn_user_id, 'table_num' => $t_subject->tableNum()))){
					$t_watch_list->setMode(ACCESS_WRITE);
					$t_watch_list->delete();
					if ($t_watch_list->numErrors()) {
						$va_errors = $t_item->errors;
						$this->view->setVar('state', 'watched');
					} else {
						$this->view->setVar('state', 'unwatched');
					}
				}
			} else {
				$t_watch_list->setMode(ACCESS_WRITE);
				$t_watch_list->set('user_id', $vn_user_id);
				$t_watch_list->set('table_num', $t_subject->tableNum());
				$t_watch_list->set('row_id', $vn_subject_id);
				$t_watch_list->insert();
				
				if ($t_watch_list->numErrors()) {
					$this->view->setVar('state', 'unwatched');
					$va_errors = $t_item->errors;
				} else {
					$this->view->setVar('state', 'watched');
				}
			}
			
			$this->view->setVar('errors', $va_errors);
			
			$this->render('../generic/ajax_toggle_item_watch_json.php');
		}
		# -------------------------------------------------------
 		/**
 		 * xxx
 		 *
 		 * @param array $pa_options Array of options passed through to _initView 
 		 */
 		public function getHierarchyForDisplay($pa_options=null) {
 			list($vn_subject_id, $t_subject) = $this->_initView();
 			
 			$vs_hierarchy_display = $t_subject->getHierarchyNavigationHTMLFormBundle($this->request, 'caHierarchyOverviewPanelBrowser', array(), array('open_hierarchy' => true, 'no_close_button' => true, 'hierarchy_browse_tab_class' => 'foo'));
 			$this->view->setVar('hierarchy_display', $vs_hierarchy_display);
 			
 			$this->render("../generic/ajax_hierarchy_overview_html.php");
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
 			$t_item 			= $o_dm->getInstanceByTableName($this->ops_table_name, true);
 			$vs_pk 				= $t_item->primaryKey();
 			$vs_label_table 	= $t_item->getLabelTableName();
 			$t_label 			= $t_item->getLabelTableInstance();
 			$vs_display_field	= $t_label->getDisplayField();
 			
 			$vn_item_id 		= (isset($pa_parameters[$vs_pk])) ? $pa_parameters[$vs_pk] : null;
 			$vn_type_id 		= (isset($pa_parameters['type_id'])) ? $pa_parameters['type_id'] : null;
 			
 			$t_item->load($vn_item_id);
 			
 			if ($t_item->getPrimaryKey()) {
 				if (method_exists($t_item, "getRepresentations")) {
 					$this->view->setVar('representations', $t_item->getRepresentations(array('preview170', 'preview')));
 				} else {
 					if ($t_item->tableName() === 'ca_object_representations') {
 						$this->view->setVar('representations', array(
 							$t_item->getFieldValuesArray()
 						));
 					}
 				}
 				
 				if ($t_item->isHierarchical()) {
					// get parent objects
					$va_ancestors = array_reverse(caExtractValuesByUserLocaleFromHierarchyAncestorList(
						$t_item->getHierarchyAncestors(null, array(
							'additionalTableToJoin' => $vs_label_table,
							'additionalTableJoinType' => 'LEFT',
							'additionalTableSelectFields' => array($vs_display_field, 'locale_id'),
							'additionalTableWheres' => ($t_label->hasField('is_preferred')) ? array("({$vs_label_table}.is_preferred = 1 OR {$vs_label_table}.is_preferred IS NULL)") : array(),
							'includeSelf' => false
						)
					), $vs_pk, $vs_display_field, 'idno'));
					
					$this->view->setVar('object_collection_collection_ancestors', array()); // collections to display as object parents when ca_objects_x_collections_hierarchy_enabled is enabled
					if (($t_item->tableName() == 'ca_objects') && $t_item->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled')) {
						// Is object part of a collection?
						if(is_array($va_collections = $t_item->getRelatedItems('ca_collections'))) {
							$this->view->setVar('object_collection_collection_ancestors', $va_collections);
						}
					}
					
					$this->view->setVar('ancestors', $va_ancestors);
					
					$va_children = caExtractValuesByUserLocaleFromHierarchyChildList(
						$t_item->getHierarchyChildren(null, array(
							'additionalTableToJoin' => $vs_label_table,
							'additionalTableJoinType' => 'LEFT',
							'additionalTableSelectFields' => array($vs_display_field, 'locale_id'),
							'additionalTableWheres' => ($t_label->hasField('is_preferred')) ? array("({$vs_label_table}.is_preferred = 1 OR {$vs_label_table}.is_preferred IS NULL)") : array(),
							'includeSelf' => false
						)
					), $vs_pk, $vs_display_field, 'idno');
					$this->view->setVar('children', $va_children);
				}
 			} else {
 				$t_item->set('type_id', $vn_type_id);
 			}
 			$this->view->setVar('t_item', $t_item);
			$this->view->setVar('screen', $this->request->getActionExtra());						// name of screen
			$this->view->setVar('result_context', $this->getResultContext());
			
//			$t_mappings = new ca_bundle_mappings();
			$va_mappings = array(); //$t_mappings->getAvailableMappings($t_item->tableNum(), array('E', 'X'));
			
			$va_export_options = array();
			foreach($va_mappings as $vn_mapping_id => $va_mapping_info) {
				$va_export_options[$va_mapping_info['name']] = $va_mapping_info['mapping_id'];
			}
			$this->view->setVar('available_mappings', $va_mappings);
			$this->view->setVar('available_mappings_as_html_select', sizeof($va_export_options) ? caHTMLSelect('mapping_id', $va_export_options, array("style" => "width: 120px;")) : '');
 		}
 		# ------------------------------------------------------------------
		/**
		 * Called just prior to actual save of record; allows individual editor controllers to implement
		 * pre-save logic by overriding this method with their own implementaton. If your implementation needs
		 * to report errors to the user it should post them onto the passed instance.
		 *
		 * If the method returns true, the save will be performed; if false is returned then the save will be aborted.
		 *
		 * @param BaseModel $pt_subject Model instance of row being saved. The instance reflects changes to be saved
		 * @param bool $pb_is_insert True if row being saved will be newly created
		 * @return bool True if save should be performed, false if it should be aborted
		 */
		protected function _beforeSave($pt_subject, $pb_is_insert) {
			// override with your own behavior as required
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Called just after record is saved. Individual editor controllers can override this to implement their
		 * own post-save logic.
		 *
		 * @param BaseModel $pt_subject Model instance of row that was saved
		 * @param bool $pb_was_insert True if saved row was newly created
		 * @return bool True if post-save actions were successful, false if not
		 */
		protected function _afterSave($pt_subject, $pb_was_insert) {
			// override with your own behavior as required
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Called just prior to actual deletion of record; allows individual editor controllers to implement
		 * pre-deletion logic (eg. moving related records) by overriding this method with their own implementaton.
		 * If your implementation needs to report errors to the user it should post them onto the passed instance.
		 *
		 * If the method returns true, the deletion will be performed; if false is returned then the delete will be aborted.
		 *
		 * @param BaseModel $pt_subject Model instance of row being deleted
		 * @return bool True if delete should be performed, false if it should be aborted
		 */
		protected function _beforeDelete($pt_subject) {
			// override with your own behavior as required
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Called just after record is deleted. Individual editor controllers can override this to implement their
		 * own post-deletion cleanup logic.
		 *
		 * @param BaseModel $pt_subject Model instance of row that was deleted
		 * @return bool True if post-deletion cleanup was successful, false if not
		 */
		protected function _afterDelete($pt_subject) {
			// override with your own behavior as required
			return true;
		}
		# ------------------------------------------------------------------
 	}
 ?>