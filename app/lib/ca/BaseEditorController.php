<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseEditorController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2017 Whirl-i-Gig
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

require_once(__CA_APP_DIR__."/helpers/printHelpers.php");
require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
require_once(__CA_MODELS_DIR__."/ca_attributes.php");
require_once(__CA_MODELS_DIR__."/ca_attribute_values.php");
require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
require_once(__CA_LIB_DIR__.'/core/Print/PDFRenderer.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/ZipStream.php');
require_once(__CA_LIB_DIR__.'/core/Media/MediaViewerManager.php');
require_once(__CA_LIB_DIR__.'/core/Logging/Downloadlog.php');

define('__CA_SAVE_AND_RETURN_STACK_SIZE__', 30);

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

		AssetLoadManager::register('bundleListEditorUI');
		AssetLoadManager::register('panel');
		AssetLoadManager::register('maps');
		AssetLoadManager::register('openlayers');
		AssetLoadManager::register('3dmodels');

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
		AssetLoadManager::register('panel');

		list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id, $vn_after_id) = $this->_initView($pa_options);
		$vs_mode = $this->request->getParameter('mode', pString);

		if (!$this->_checkAccess($t_subject)) { return false; }

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
				'duplicate_relationship_attributes' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_relationship_attributes'),
				'duplicate_media' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_media'),
				'duplicate_subitems' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_subitems'),
				'duplicate_element_settings' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_element_settings')
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
			if (($vs_bundle = $this->request->getParameter('bundle', pString)) && ($vs_bundle_screen = $t_ui->getScreenWithBundle($vs_bundle))) {
				// jump to screen containing url-specified bundle
				$this->request->setActionExtra($vs_bundle_screen);
			} else {
				$this->request->setActionExtra($va_nav['defaultScreen']);
			}
		}
		$this->view->setVar('t_ui', $t_ui);

		if ($vn_subject_id) {
			// set last edited
			$this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id);
		}

		# trigger "EditItem" hook
		$this->opo_app_plugin_manager->hookEditItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));

		if (!($vs_view = caGetOption('view', $pa_options, null))) {
			$vs_view = 'screen_html';
		}

		// save where we are in session, for "Save and return" button
		if($vn_subject_id) { // don't save "empty" / new record editor location. pk has to be set
			$va_save_and_return = $this->getRequest()->session->getVar('save_and_return_locations');
			if(!is_array($va_save_and_return)) { $va_save_and_return = array(); }

			$va_save = array(
				'table' => $t_subject->tableName(),
				'key' => $vn_subject_id,
				'url_path' => $this->getRequest()->getFullUrlPath()
			);

			$this->getRequest()->session->setVar('save_and_return_locations', caPushToStack($va_save, $va_save_and_return, __CA_SAVE_AND_RETURN_STACK_SIZE__));
		}

		// if we came here through a rel link, show save and return button
		$this->getView()->setVar('show_save_and_return', (bool) $this->getRequest()->getParameter('rel', pInteger));

		$this->render("{$vs_view}.php");
	}
	# -------------------------------------------------------
	/**
	 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
	 *
	 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
	 */
	public function Save($pa_options=null) {
		list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id, $vn_after_id, $vs_rel_table, $vn_rel_type_id, $vn_rel_id) = $this->_initView($pa_options);
		/** @var $t_subject BundlableLabelableBaseModelWithAttributes */
		if (!is_array($pa_options)) { $pa_options = array(); }

		if (!$this->_checkAccess($t_subject)) { return false; }

		if($vn_above_id) {
			// Convert "above" id (the id of the record we're going to make the newly created record parent of
			if (($t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name)) && $t_instance->load($vn_above_id)) {
				$vn_parent_id = $t_instance->get($vs_parent_id_fld = $t_instance->getProperty('HIERARCHY_PARENT_ID_FLD'));
				$this->request->setParameter($vs_parent_id_fld, $vn_parent_id);
				$this->view->setVar('parent_id', $vn_parent_id);
			}
		}

		// relate existing records via Save() link
		if($vn_subject_id && $vs_rel_table && $vn_rel_type_id && $vn_rel_id) {
			if($this->opo_datamodel->tableExists($vs_rel_table)) {
				Debug::msg("[Save()] Relating new record using parameters from request: $vs_rel_table / $vn_rel_type_id / $vn_rel_id");
				$t_subject->addRelationship($vs_rel_table, $vn_rel_id, $vn_rel_type_id, _t('now'));
			}
			$this->notification->addNotification(_t("Added relationship"), __NOTIFICATION_TYPE_INFO__);
			$this->render('screen_html.php');
			return;
		}

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

		if(!$vn_subject_id) { // this was an insert
			$vn_subject_id = $t_subject->getPrimaryKey();
			if (!$vb_save_rc) { // failed insert
				$vs_message = _t("Could not save %1", $vs_type_name);
			} else { // ok insert
				$vs_message = _t("Added %1", $vs_type_name);
				$this->request->setParameter($t_subject->primaryKey(), $vn_subject_id, 'GET');
				$this->view->setVar($t_subject->primaryKey(), $vn_subject_id);
				$this->view->setVar('subject_id', $vn_subject_id);
				$this->request->session->setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id);	// set last edited

				// relate newly created record if requested
				if($vs_rel_table && $vn_rel_type_id && $vn_rel_id) {
					if($this->opo_datamodel->tableExists($vs_rel_table)) {
						Debug::msg("[Save()] Relating new record using parameters from request: $vs_rel_table / $vn_rel_type_id / $vn_rel_id");
						$t_subject->addRelationship($vs_rel_table, $vn_rel_id, $vn_rel_type_id);
					}
				}

				// Set ACL for newly created record
				if ($t_subject->getAppConfig()->get('perform_item_level_access_checking') && !$t_subject->getAppConfig()->get("{$this->ops_table_name}_dont_do_item_level_access_control")) {
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
				
				// If "after_id" is set then reset ranks such that saved record follows immediately after
				if ($vn_after_id) {
					$t_subject->setRankAfter($vn_after_id);
					if ($t_subject->numErrors()) {
						$this->notification->addNotification($t_subject->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
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
			$this->opo_result_context->invalidateCache();	// force new search in case changes have removed this item from the results
			$this->opo_result_context->saveContext();
		}
		# trigger "SaveItem" hook

		$this->opo_app_plugin_manager->hookSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => $vb_is_insert));

		if (method_exists($this, "postSave")) {
			$this->postSave($t_subject, $vb_is_insert);
		}

		// redirect back to previous item on stack if it's a valid "save and return" request
		$vb_has_errors = (is_array($va_errors) && (sizeof($va_errors) > 0)); // don't redirect back when there were form errors
		if(((bool) $this->getRequest()->getParameter('is_save_and_return', pInteger)) && !$vb_has_errors) {
			$va_save_and_return = $this->getRequest()->session->getVar('save_and_return_locations');
			if(is_array($va_save_and_return)) {
				// get rid of all the navigational steps in the current item
				do {
					$va_pop = array_pop($va_save_and_return);
				} while (
					(sizeof($va_save_and_return)>0) && // only keep going if there are more saved locations
					(
						!$va_pop['key'] || // keep going if key is empty (i.e. it was a "create new record" screen)
						(($va_pop['table'] == $t_subject->tableName()) && ($va_pop['key'] == $vn_subject_id)) // keep going if the record is the current one
					)
				);

				// the last pop must be from a different table or record for the redirect to kick in
				// (which might not be the case because $va_save_and_return might have just run out of items for some reason)
				if(($va_pop['table'] != $t_subject->tableName()) || ($va_pop['key'] != $vn_subject_id)) {
					if(isset($va_pop['url_path']) && (strlen($va_pop['url_path']) > 0)) {
						$this->getResponse()->setRedirect($va_pop['url_path']);
					} else {
						$this->getResponse()->setRedirect(caEditorUrl($this->getRequest(), $va_pop['table'], $va_pop['key']));
					}
				}
			}
		}

		// save where we are in session for "Save and return" button
		if($vn_subject_id) {
			$va_save_and_return = $this->getRequest()->session->getVar('save_and_return_locations');
			if(!is_array($va_save_and_return)) { $va_save_and_return = array(); }

			$va_save = array(
				'table' => $t_subject->tableName(),
				'key' => $vn_subject_id,
				// dont't direct back to Save action
				'url_path' => str_replace('/Save/', '/Edit/', $this->getRequest()->getFullUrlPath())
			);
			$this->getRequest()->session->setVar('save_and_return_locations', caPushToStack($va_save, $va_save_and_return, __CA_SAVE_AND_RETURN_STACK_SIZE__));
		}

		// if we came here through a rel link, show save and return button
		$this->getView()->setVar('show_save_and_return', (bool) $this->getRequest()->getParameter('rel', pInteger));

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


		if (!$this->_checkAccess($t_subject)) { return false; }


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
			$vb_we_set_transaction = false;
			if (!$t_subject->inTransaction()) {
				$t_subject->setTransaction($o_t = new Transaction());
				$vb_we_set_transaction = true;
			}

			// Do we need to move relationships?
			if (($vn_remap_id =  $this->request->getParameter('caReferenceHandlingToRemapToID', pInteger)) && ($this->request->getParameter('caReferenceHandlingTo', pString) == 'remap')) {
				switch($t_subject->tableName()) {
					case 'ca_relationship_types':
						if ($vn_c = $t_subject->moveRelationshipsToType($vn_remap_id)) {
							$t_target = new ca_relationship_types($vn_remap_id);
							$this->notification->addNotification(($vn_c == 1) ? _t("Transferred %1 relationship to type <em>%2</em>", $vn_c, $t_target->getLabelForDisplay()) : _t("Transferred %1 relationships to type <em>%2</em>", $vn_c, $t_target->getLabelForDisplay()), __NOTIFICATION_TYPE_INFO__);
						}
						break;
					default:
						// update relationships
						$va_tables = array(
							'ca_objects', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops', 'ca_object_representations', 'ca_list_items'
						);

						$vn_c = 0;
						foreach($va_tables as $vs_table) {
							$vn_c += $t_subject->moveRelationships($vs_table, $vn_remap_id);
						}

						// update existing metadata attributes to use remapped value
						$t_subject->moveAuthorityElementReferences($vn_remap_id);

						if ($vn_c > 0) {
							$t_target = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
							$t_target->load($vn_remap_id);
							$this->notification->addNotification(($vn_c == 1) ? _t("Transferred %1 relationship to <em>%2</em> (%3)", $vn_c, $t_target->getLabelForDisplay(), $t_target->get($t_target->getProperty('ID_NUMBERING_ID_FIELD'))) : _t("Transferred %1 relationships to <em>%2</em> (%3)", $vn_c, $t_target->getLabelForDisplay(), $t_target->get($t_target->getProperty('ID_NUMBERING_ID_FIELD'))), __NOTIFICATION_TYPE_INFO__);
						}
						break;
				}
			} else {
				$t_subject->deleteAuthorityElementReferences();
			}
			
			// Do we need to move references contained in attributes bound to this item?
			if (($vn_remap_id =  $this->request->getParameter('caReferenceHandlingToRemapFromID', pInteger)) && ($this->request->getParameter('caReferenceHandlingFrom', pString) == 'remap')) {
				try {
					$t_subject->moveAttributes($vn_remap_id, $t_subject->getAuthorityElementList(['idsOnly' => true]));
				} catch(ApplicationException $o_e) {
					$this->notification->addNotification(_t("Could not move references to other items in metadata before delete: %1", $o_e->getErrorDescription()), __NOTIFICATION_TYPE_ERROR__);
				}
			}
			
			$t_subject->setMode(ACCESS_WRITE);

			$vb_rc = false;
			if ($this->_beforeDelete($t_subject)) {
				if ($vb_rc = $t_subject->delete(true)) {
					$this->_afterDelete($t_subject);
				}
			}

			if ($vb_we_set_transaction) {
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

				# redirect
				$this->redirectAfterDelete($t_subject->tableName());
			}
		}

		$this->view->setVar('subject_name', $t_subject->getLabelForDisplay(false));

		$this->render('delete_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Redirects to a sensible location after a record delete. Defaults to the last find action
	 * for the current table, which depending on the table may not be available. Can be
	 * overridden in subclasses/implementations.
	 * @param string $ps_table table name
	 */
	protected function redirectAfterDelete($ps_table) {
		$this->getRequest()->close();
		caSetRedirect($this->opo_result_context->getResultsUrlForLastFind($this->getRequest(), $ps_table));
	}
	# -------------------------------------------------------
	/**
	 * Generates display summary of record data based upon a bundle display for screen (HTML)
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 * @return bool
	 */
	public function Summary($pa_options=null) {
		AssetLoadManager::register('tableList');
		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);


		if (!$this->_checkAccess($t_subject)) { return false; }

		if(defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) {
			$this->render(__CA_THEME_DIR__.'/views/editor/template_test_html.php');
		}

		$t_display = new ca_bundle_displays();
		$va_displays = $t_display->getBundleDisplays(array('table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'restrictToTypes' => array($t_subject->getTypeID())));

		if ((!($vn_display_id = $this->request->getParameter('display_id', pInteger))) || !isset($va_displays[$vn_display_id])) {
			if ((!($vn_display_id = $this->request->user->getVar($t_subject->tableName().'_summary_display_id')))  || !isset($va_displays[$vn_display_id])) {
				$va_tmp = array_keys($va_displays);
				$vn_display_id = $va_tmp[0];
			}
		}

		// save where we are in session, for "Save and return" button
		$va_save_and_return = $this->getRequest()->session->getVar('save_and_return_locations');
		if(!is_array($va_save_and_return)) { $va_save_and_return = array(); }

		$va_save = array(
			'table' => $t_subject->tableName(),
			'key' => $vn_subject_id,
			'url_path' => $this->getRequest()->getFullUrlPath()
		);

		$this->getRequest()->session->setVar('save_and_return_locations', caPushToStack($va_save, $va_save_and_return, __CA_SAVE_AND_RETURN_STACK_SIZE__));

		$this->view->setVar('bundle_displays', $va_displays);
		$this->view->setVar('t_display', $t_display);

		// Check validity and access of specified display
		if ($t_display->load($vn_display_id) && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
			$this->view->setVar('display_id', $vn_display_id);

			$va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true, 'omitEditingInfo' => true));

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
		AssetLoadManager::register('tableList');
		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);


		if (!$this->_checkAccess($t_subject)) { return false; }


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

			$va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true, 'omitEditingInfo' => true));
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
		} else {
			$vn_display_id = $t_display = null;
			$this->view->setVar('display_id', null);
			$this->view->setVar('placements', array());
		}

		//
		// PDF output
		//
		if(!$vn_display_id || !$t_display || !is_array($va_template_info = caGetPrintTemplateDetails('summary', "{$this->ops_table_name}_".$t_display->get('display_code')."_summary"))) {
			if(!is_array($va_template_info = caGetPrintTemplateDetails('summary', "{$this->ops_table_name}_summary"))) {
				if(!is_array($va_template_info = caGetPrintTemplateDetails('summary', "summary"))) {
					$this->postError(3110, _t("Could not find view for PDF"),"BaseEditorController->PrintSummary()");
					return;
				}
			}
		}

		$va_barcode_files_to_delete = array();

		try {
			$this->view->setVar('base_path', $vs_base_path = pathinfo($va_template_info['path'], PATHINFO_DIRNAME));
			$this->view->addViewPath(array($vs_base_path, "{$vs_base_path}/local"));

			$va_barcode_files_to_delete += caDoPrintViewTagSubstitution($this->view, $t_subject, $va_template_info['path'], array('checkAccess' => $this->opa_access_values));

			$o_pdf = new PDFRenderer();

			$this->view->setVar('PDFRenderer', $o_pdf->getCurrentRendererCode());

			$va_page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $va_template_info, 'letter'), 'mm', caGetOption('pageOrientation', $va_template_info, 'portrait'));
			$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];
			$this->view->setVar('pageWidth', "{$vn_page_width}mm");
			$this->view->setVar('pageHeight', "{$vn_page_height}mm");
			$this->view->setVar('marginTop', caGetOption('marginTop', $va_template_info, '0mm'));
			$this->view->setVar('marginRight', caGetOption('marginRight', $va_template_info, '0mm'));
			$this->view->setVar('marginBottom', caGetOption('marginBottom', $va_template_info, '0mm'));
			$this->view->setVar('marginLeft', caGetOption('marginLeft', $va_template_info, '0mm'));

			$vs_content = $this->render($va_template_info['path']);

			$o_pdf->setPage(caGetOption('pageSize', $va_template_info, 'letter'), caGetOption('pageOrientation', $va_template_info, 'portrait'), caGetOption('marginTop', $va_template_info, '0mm'), caGetOption('marginRight', $va_template_info, '0mm'), caGetOption('marginBottom', $va_template_info, '0mm'), caGetOption('marginLeft', $va_template_info, '0mm'));
			$o_pdf->render($vs_content, array('stream'=> true, 'filename' => caGetOption('filename', $va_template_info, 'print_summary.pdf')));

			$vb_printed_properly = true;

			foreach($va_barcode_files_to_delete as $vs_tmp) { @unlink($vs_tmp);}
			exit;
		} catch (Exception $e) {
			foreach($va_barcode_files_to_delete as $vs_tmp) { @unlink($vs_tmp);}
			$vb_printed_properly = false;
			$this->postError(3100, _t("Could not generate PDF"),"BaseEditorController->PrintSummary()");
		}
		return;
	}
	# -------------------------------------------------------
	/**
	 * Generates display for specific bundle or (optionally) a specific repetition in a bundle
	 * ** Right now only attribute bundles are supported for printing **
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 */
	public function PrintBundle($pa_options=null) {
		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);

		if (!$this->_checkAccess($t_subject)) { return false; }

		//
		// PDF output
		//
		$vs_template = substr($this->request->getParameter('template', pString), 5); // get rid of _pdf_ prefix
		if(!is_array($va_template_info = caGetPrintTemplateDetails('bundles', $vs_template))) {
			$this->postError(3110, _t("Could not find view for PDF"),"BaseEditorController->PrintBundle()");
			return;
		}

		// Element code to display
		$vs_element = $this->request->getParameter('element_code', pString);

		$vn_attribute_id = $this->request->getParameter('attribute_id', pString);

		// Does user have access to this element?
		if ($this->request->user->getBundleAccessLevel($t_subject->tableName(), $vs_element) == __CA_BUNDLE_ACCESS_NONE__) {
			$this->postError(2320, _t("No access to element"),"BaseEditorController->PrintBundle()");
			return;
		}

		// Add raw array of values to view
		if ($vn_attribute_id > 0) {
			$o_attr = $t_subject->getAttributeByID($vn_attribute_id);
			if (((int)$o_attr->getRowID() !== (int)$vn_subject_id) || ((int)$o_attr->getTableNum() !== (int)$t_subject->tableNum())) {
				$this->postError(2320, _t("Element is not part of current item"),"BaseEditorController->PrintBundle()");
				return;
			}
			$this->view->setVar('valuesAsAttributeInstances', $va_values = array($o_attr));
		} else {
			$this->view->setVar('valuesAsAttributeInstances', $va_values = $t_subject->getAttributesByElement($vs_element));
		}
		
		$this->view->setVar('t_subject', $t_subject);

		// Extract values into array for easier view processing

		$va_extracted_values = array();
		foreach($va_values as $o_value) {
			$va_extracted_values[] = $o_value->getDisplayValues(null, ['output' => 'text']);
		}
		$this->view->setVar('valuesAsElementCodeArrays', $va_extracted_values);

		$va_barcode_files_to_delete = array();

		try {
			$this->view->setVar('base_path', $vs_base_path = pathinfo($va_template_info['path'], PATHINFO_DIRNAME));
			$this->view->addViewPath(array($vs_base_path, "{$vs_base_path}/local"));

			$va_barcode_files_to_delete += caDoPrintViewTagSubstitution($this->view, $t_subject, $va_template_info['path'], array('checkAccess' => $this->opa_access_values));

			$o_pdf = new PDFRenderer();

			$this->view->setVar('PDFRenderer', $o_pdf->getCurrentRendererCode());

			$va_page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $va_template_info, 'letter'), 'mm', caGetOption('pageOrientation', $va_template_info, 'portrait'));
			$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];
			$this->view->setVar('pageWidth', "{$vn_page_width}mm");
			$this->view->setVar('pageHeight', "{$vn_page_height}mm");
			$this->view->setVar('marginTop', caGetOption('marginTop', $va_template_info, '0mm'));
			$this->view->setVar('marginRight', caGetOption('marginRight', $va_template_info, '0mm'));
			$this->view->setVar('marginBottom', caGetOption('marginBottom', $va_template_info, '0mm'));
			$this->view->setVar('marginLeft', caGetOption('marginLeft', $va_template_info, '0mm'));

			$vs_content = $this->render($va_template_info['path']);

			$o_pdf->setPage(caGetOption('pageSize', $va_template_info, 'letter'), caGetOption('pageOrientation', $va_template_info, 'portrait'), caGetOption('marginTop', $va_template_info, '0mm'), caGetOption('marginRight', $va_template_info, '0mm'), caGetOption('marginBottom', $va_template_info, '0mm'), caGetOption('marginLeft', $va_template_info, '0mm'));
			$o_pdf->render($vs_content, array('stream'=> true, 'filename' => caGetOption('filename', $va_template_info, 'print_bundles.pdf')));

			$vb_printed_properly = true;

			foreach($va_barcode_files_to_delete as $vs_tmp) { @unlink($vs_tmp); @unlink("{$vs_tmp}.png");}
			exit;
		} catch (Exception $e) {
			foreach($va_barcode_files_to_delete as $vs_tmp) { @unlink($vs_tmp); @unlink("{$vs_tmp}.png");}
			$vb_printed_properly = false;
			$this->postError(3100, _t("Could not generate PDF"),"BaseEditorController->PrintBundle()");
		}
	}
	# -------------------------------------------------------
	/**
	 * Returns change log display for currently edited record in current view inherited from ActionController
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 */
	public function Log($pa_options=null) {
		AssetLoadManager::register('tableList');
		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);


		if (!$this->_checkAccess($t_subject)) { return false; }


		if (ca_user_roles::isValidAction('can_view_change_log_'.$t_subject->tableName()) && (!$this->request->user->canDoAction('can_view_change_log_'.$t_subject->tableName()))) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2575?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		$this->view->setVar('log', $t_subject->getChangeLogForDisplay('caLog', $this->request->getUserID()));

		$this->render('log_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 */
	public function Access($pa_options=null) {
		AssetLoadManager::register('tableList');
		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);


		if (!$this->_checkAccess($t_subject)) { return false; }

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


		if (!$this->_checkAccess($t_subject)) { return false; }

		if ((!$t_subject->isSaveable($this->request)) || (!$this->request->user->canDoAction('can_change_acl_'.$t_subject->tableName()))) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2570?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		$vs_form_prefix = $this->request->getParameter('_formName', pString);

		$this->opo_app_plugin_manager->hookBeforeSaveItem(array(
			'id' => $vn_subject_id,
			'table_num' => $t_subject->tableNum(),
			'table_name' => $t_subject->tableName(), 
			'instance' => $t_subject,
			'is_insert' => false)
		);

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
				$t_subject->set('acl_inherit_from_ca_collections', $this->request->getParameter('acl_inherit_from_ca_collections', pInteger));
			}
			if ($t_subject->hasField('acl_inherit_from_parent')) {
				$t_subject->set('acl_inherit_from_parent', $this->request->getParameter('acl_inherit_from_parent', pInteger));
			}
			$t_subject->update();

			if ($t_subject->numErrors()) {
				$this->postError(1250, _t('Could not set ACL inheritance settings: %1', join("; ", $t_subject->getErrors())),"BaseEditorController->SetAccess()");
			}
		}

		$this->opo_app_plugin_manager->hookSaveItem(array(
			'id' => $vn_subject_id,
			'table_num' => $t_subject->tableNum(),
			'table_name' => $t_subject->tableName(),
			'instance' => $t_subject,
			'is_insert' => false)
		);

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

		if (!$this->_checkAccess($t_subject)) { return false; }


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
	 *
	 */
	protected function _getUI($pn_type_id=null, $pa_options=null) {
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
			$t_ui = ca_editor_uis::loadDefaultUI($this->ops_table_name, $this->request, $pn_type_id);
		}

		return $t_ui;
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
		AssetLoadManager::register('bundleableEditor');
		AssetLoadManager::register('imageScroller');
		AssetLoadManager::register('datePickerUI');

		$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
		$vn_subject_id = $this->request->getParameter($t_subject->primaryKey(), pInteger);

		if (!$vn_subject_id || !$t_subject->load($vn_subject_id)) {
			// empty (ie. new) rows don't have a type_id set, which means we'll have no idea which attributes to display
			// so we get the type_id off of the request
			if (!$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger)) {
				$vn_type_id = null;
			}

			// then set the empty row's type_id
			if($t_subject->hasField($t_subject->getTypeFieldName())) {
				$t_subject->set($t_subject->getTypeFieldName(), $vn_type_id);
			}

			// then reload the definitions (which includes bundle specs)
			$t_subject->reloadLabelDefinitions();
		} else {
			$vn_type_id = $t_subject->getTypeID();
		}

		$t_ui = $this->_getUI($vn_type_id, $pa_options);

		$this->view->setVar($t_subject->primaryKey(), $vn_subject_id);
		$this->view->setVar('subject_id', $vn_subject_id);
		$this->view->setVar('t_subject', $t_subject);

		MetaTagManager::setWindowTitle(_t("Editing %1 : %2", ($vs_type = $t_subject->getTypeName()) ? $vs_type : $t_subject->getProperty('NAME_SINGULAR'), ($vn_subject_id) ? $t_subject->getLabelForDisplay(true) : _t('new %1', $t_subject->getTypeName())));

		// pass relationship parameters to Save() action from Edit() so
		// that we can create a relationship for a newly created object
		if($vs_rel_table = $this->getRequest()->getParameter('rel_table', pString)) {
			$vn_rel_type_id = $this->getRequest()->getParameter('rel_type_id', pString);
			$vn_rel_id = $this->getRequest()->getParameter('rel_id', pInteger);

			if($vs_rel_table && $vn_rel_type_id && $vn_rel_id) {
				$this->view->setVar('rel_table', $vs_rel_table);
				$this->view->setVar('rel_type_id', $vn_rel_type_id);
				$this->view->setVar('rel_id', $vn_rel_id);
			}

			return array($vn_subject_id, $t_subject, $t_ui, null, null, null, $vs_rel_table, $vn_rel_type_id, $vn_rel_id);
		}

		if ($vs_parent_id_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD')) {
			$this->view->setVar('parent_id', $vn_parent_id = $this->request->getParameter($vs_parent_id_fld, pInteger));

			// The "above_id" is set when the new record we're creating is to be inserted *above* an existing record (eg. made
			// the parent of another record). It will be set to the record we're inserting above. We ignore it if set when editing
			// an existing record since it is only relevant for newly created records.
			if (!$vn_subject_id) {
				$this->view->setVar('above_id', $vn_above_id = $this->request->getParameter('above_id', pInteger));
				$this->view->setVar('after_id', $vn_after_id = $this->request->getParameter('after_id', pInteger));
				$t_subject->set($vs_parent_id_fld, $vn_parent_id);

				$t_parent = $this->opo_datamodel->getInstanceByTableName($this->ops_table_name);
				if (
					$t_parent->load($vn_parent_id)
					&&
					($t_parent->get('parent_id') || ($t_parent->getHierarchyType() == __CA_HIER_TYPE_ADHOC_MONO__))
					&&
					((!method_exists($t_parent, "getIDNoPlugInInstance") || !($o_numbering_plugin = $t_parent->getIDNoPlugInInstance())) || ($o_numbering_plugin->formatHas('FREE', 0)))
				) {
					$t_subject->set('idno', $t_parent->get('idno'));
				}
			}
			return array($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id, $vn_after_id);
		}

		return array($vn_subject_id, $t_subject, $t_ui);
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
				if($va_item['settings']) {
					$va_settings = caUnserializeForDatabase($va_item['settings']);
					if(is_array($va_settings) && isset($va_settings['render_in_new_menu']) && !((bool) $va_settings['render_in_new_menu'])) {
						unset($va_hier[$vn_item_id]);
						continue;
					}
				}
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

		if (!$this->_checkAccess($t_subject)) { return false; }

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

		if (!$this->_checkAccess($t_subject)) { return false; }


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

		if (!$this->_checkAccess($t_subject)) { return false; }


		$vs_hierarchy_display = $t_subject->getHierarchyNavigationHTMLFormBundle($this->request, 'caHierarchyOverviewPanelBrowser', array(), array('open_hierarchy' => true, 'no_close_button' => true, 'hierarchy_browse_tab_class' => 'foo'));
		$this->view->setVar('hierarchy_display', $vs_hierarchy_display);

		$this->render("../generic/ajax_hierarchy_overview_html.php");
	}
	# ------------------------------------------------------------------
	/**
	 * Returns content for a bundle or the inspector. Used to dynamically and selectively
	 * reload an editing form.
	 */
	public function reload() {
		list($vn_subject_id, $t_subject) = $this->_initView();

		if (!$this->_checkAccess($t_subject)) { return false; }

		$ps_bundle = $this->request->getParameter("bundle", pString);
		$pn_placement_id = $this->request->getParameter("placement_id", pInteger);


		switch($ps_bundle) {
			case '__inspector__':
				$this->response->addContent($this->info(array($t_subject->primaryKey() => $vn_subject_id, 'type_id' => $this->request->getParameter("type_id", pInteger))));
				break;
			default:
				$t_placement = new ca_editor_ui_bundle_placements($pn_placement_id);

				if (!$t_placement->getPrimaryKey()) {
					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
					return;
				}

				if ($t_placement->get('bundle_name') != $ps_bundle) {
					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
					return;
				}

				$this->response->addContent($t_subject->getBundleFormHTML($ps_bundle, $pn_placement_id, $t_placement->get('settings'), array('request' => $this->request, 'contentOnly' => true), $vs_label));
				break;
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function loadBundles() {
		list($vn_subject_id, $t_subject) = $this->_initView();

		if (!$this->_checkAccess($t_subject)) { return false; }

		$ps_bundle_name = $this->request->getParameter("bundle", pString);
		$pn_placement_id = $this->request->getParameter("placement_id", pInteger);
		$pn_start = (int)$this->request->getParameter("start", pInteger);
		if (!($pn_limit = $this->request->getParameter("limit", pInteger))) { $pn_limit = null; }

		$t_placement = new ca_editor_ui_bundle_placements($pn_placement_id);

		$this->response->addContent(json_encode($t_subject->getBundleFormValues($ps_bundle_name, "{$pn_placement_id}", $t_placement->get('settings'), array('start' => $pn_start, 'limit' => $pn_limit, 'request' => $this->request, 'contentOnly' => true))));
	}
	# ------------------------------------------------------------------
	/**
	 * JSON service that returns a processed display template for the current record
	 * @return bool
	 */
	public function processTemplate() {
		list($vn_subject_id, $t_subject) = $this->_initView();

		if (!$this->_checkAccess($t_subject)) { return false; }

		// http://providence.dev/index.php/editor/objects/ObjectEditor/processTemplate/object_id/1/template/^ca_objects.idno
		$ps_template = $this->request->getParameter("template", pString);
		$this->view->setVar('processed_template', json_encode(caProcessTemplateForIDs($ps_template, $t_subject->tableNum(), array($vn_subject_id))));
		$this->render("../generic/ajax_process_template.php");

		return true;
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
		if ($vs_label_table 	= $t_item->getLabelTableName()) {
			$t_label 			= $t_item->getLabelTableInstance();
			$vs_display_field	= $t_label->getDisplayField();
		}
		
		$vn_item_id 		= (isset($pa_parameters[$vs_pk])) ? $pa_parameters[$vs_pk] : null;
		$vn_type_id 		= (isset($pa_parameters['type_id'])) ? $pa_parameters['type_id'] : null;

		$t_item->load($vn_item_id);

		if (!$this->_checkAccess($t_item, array('dontRedirectOnDelete' => true))) {
			$t_item->clear();
			$t_item->clearErrors();
		}

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
					if(is_array($va_collections = $t_item->getRelatedItems('ca_collections', array('restrictToRelationshipTypes' => array($t_item->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type')))))) {
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
			if($t_item->hasField('type_id')) {
				$t_item->set('type_id', $vn_type_id);
			}
		}
		$this->view->setVar('t_item', $t_item);
		$this->view->setVar('screen', $this->request->getActionExtra());						// name of screen
		$this->view->setVar('result_context', $this->getResultContext());

		$this->view->setVar('t_ui', $t_ui = $this->_getUI($vn_type_id));
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
	/**
	 * Called just after record is deleted. Individual editor controllers can override this to implement their
	 * own post-deletion cleanup logic.
	 *
	 * @param BaseModel $pt_subject Model instance of row that was deleted
	 * @return bool True if post-deletion cleanup was successful, false if not
	 */
	protected function _checkAccess($pt_subject, $pa_options=null) {
		//
		// Is record deleted?
		//
		if ($pt_subject->hasField('deleted') && $pt_subject->get('deleted')) {
			if (!caGetOption('dontRedirectOnDelete', $pa_options, false)) {
				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2550?r='.urlencode($this->request->getFullUrlPath()));
			}
			return false;
		}

		//
		// Is record of correct type?
		//
		$va_restrict_to_types = null;
		if ($pt_subject->getAppConfig()->get('perform_type_access_checking')) {
			$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_READONLY__));
		}
		if (
			is_array($va_restrict_to_types)
			&&
			(
				($pt_subject->get('type_id')) && ($pt_subject->getPrimaryKey() && !in_array($pt_subject->get('type_id'), $va_restrict_to_types))
				//||
				//(!$pt_subject->getPrimaryKey() && !in_array($this->request->getParameter('type_id', pInteger), $va_restrict_to_types))
			)
		) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
			return false;
		}

		//
		// Is record from correct source?
		//
		$va_restrict_to_sources = null;
		if ($pt_subject->getAppConfig()->get('perform_source_access_checking') && $pt_subject->hasField('source_id')) {
			if (is_array($va_restrict_to_sources = caGetSourceRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_READONLY__)))) {
				if (is_array($va_restrict_to_sources) && $pt_subject->get('source_id') && !in_array($pt_subject->get('source_id'), $va_restrict_to_sources)) {
					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2562?r='.urlencode($this->request->getFullUrlPath()));
					return;
				}
				if (
					(!$pt_subject->get('source_id'))
					||
					($pt_subject->get('source_id') && !in_array($pt_subject->get('source_id'), $va_restrict_to_sources))
					||
					((strlen($vn_source_id = $this->request->getParameter('source_id', pInteger))) && !in_array($vn_source_id, $va_restrict_to_sources))
				) {
					$pt_subject->set('source_id', $pt_subject->getDefaultSourceID(array('request' => $this->request)));
				}
			}
		}

		//
		// Does user have access to row?
		//
		if ($pt_subject->getAppConfig()->get('perform_item_level_access_checking') && $vn_subject_id) {
			if ($pt_subject->checkACLAccessForUser($this->request->user) < __CA_BUNDLE_ACCESS_READONLY__) {
				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
				return false;
			}
		}

		return true;
	}
	# -------------------------------------------------------
	# AJAX handlers
	# -------------------------------------------------------
	/**
	 * Returns content for overlay containing details for object representation or attribute values of type "media"
	 *
	 * Expects the following request parameters:
	 *		representation_id = the id of the ca_object_representations record to display; the representation must belong to the specified object
	 *		value_id =
	 *
	 *	Optional request parameters:
	 *		version = The version of the representation to display. If omitted the display version configured in media_display.conf is used
	 *
	 */
	public function GetMediaOverlay() {
		list($vn_subject_id, $t_subject) = $this->_initView();
			
		if ($pn_value_id = $this->request->getParameter('value_id', pInteger)) {
			//
			// View FT_MEDIA attribute media 
			//
			$t_instance = new ca_attribute_values($pn_value_id);
			$t_instance->useBlobAsMediaField(true);
			$t_attr = new ca_attributes($t_instance->get('attribute_id'));
			$t_subject = $this->opo_datamodel->getInstanceByTableNum($t_attr->get('table_num'), true);
			$t_subject->load($t_attr->get('row_id'));
						
			if (!$t_subject->isReadable($this->request)) { 
				throw new ApplicationException(_t('Cannot view media'));
			}

			if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('value_blob', 'original', 'MIMETYPE')))) {
				throw new ApplicationException(_t('Invalid viewer'));
			}

			$this->response->addContent($vs_viewer_name::getViewerHTML(
				$this->request, 
				"attribute:{$pn_value_id}", 
				['context' => 'media_overlay', 't_instance' => $t_instance, 't_subject' => $t_subject, 'display' => caGetMediaDisplayInfo('media_overlay', $vs_mimetype)])
			);
		} elseif ($pn_representation_id = $this->request->getParameter('representation_id', pInteger)) {			
			if (!$t_subject->isReadable($this->request)) { 
				throw new ApplicationException(_t('Cannot view media'));
			}
			//
			// View object representation
			//
			$t_instance = new ca_object_representations($pn_representation_id);
			
			if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'original', 'MIMETYPE')))) {
				throw new ApplicationException(_t('Invalid viewer'));
			}
			
			$va_display_info = caGetMediaDisplayInfo('media_overlay', $vs_mimetype);
			
			if ((($vn_use_universal_viewer_for_image_list_length = caGetOption('use_universal_viewer_for_image_list_length_at_least', $va_display_info, null))
				||
				($vn_use_mirador_for_image_list_length = caGetOption('use_mirador_for_image_list_length_at_least', $va_display_info, null)))
			) {
				$vn_image_count = $t_subject->numberOfRepresentationsOfClass('image');
				$vn_rep_count = $t_subject->getRepresentationCount();
				
				// Are there enough representations? Are all representations images? 
				if ($vn_image_count == $vn_rep_count) {
					if (!is_null($vn_use_universal_viewer_for_image_list_length) && ($vn_image_count >= $vn_use_universal_viewer_for_image_list_length)) {
						$va_display_info['viewer'] = $vs_viewer_name = 'UniversalViewer';
					} elseif(!is_null($vn_use_mirador_for_image_list_length) && ($vn_image_count >= $vn_use_mirador_for_image_list_length)) {
						$va_display_info['viewer'] = $vs_viewer_name = 'Mirador';
					}
				}
			}
			
			if(!$vn_subject_id) {
				if (is_array($va_subject_ids = $t_instance->get($t_subject->tableName().'.'.$t_subject->primaryKey(), array('returnAsArray' => true))) && sizeof($va_subject_ids)) {
					$vn_subject_id = array_shift($va_subject_ids);
				} else {
					$this->postError(1100, _t('Invalid object/representation'), 'ObjectEditorController->GetRepresentationInfo');
					return;
				}
			}

			$this->response->addContent($vs_viewer_name::getViewerHTML(
				$this->request, 
				"representation:{$pn_representation_id}", 
				['context' => 'media_overlay', 't_instance' => $t_instance, 't_subject' => $t_subject, 'display' => $va_display_info])
			);
		} else {
			throw new ApplicationException(_t('Invalid id'));
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function GetMediaData() {
		list($vn_subject_id, $t_subject) = $this->_initView();
		
		if (!$t_subject->isReadable($this->request)) { 
			throw new ApplicationException(_t('Cannot view media'));
		}
		
		$ps_identifier = $this->request->getParameter('identifier', pString);
		if (!($va_identifier = caParseMediaIdentifier($ps_identifier))) {
			throw new ApplicationException(_t('Invalid identifier %1', $ps_identifier));
		}
		
		$app = AppController::getInstance();
		$app->removeAllPlugins();
		
		switch($va_identifier['type']) {
			case 'representation':
				$t_instance = new ca_object_representations($va_identifier['id']);
				
				if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'original', 'MIMETYPE')))) {
					throw new ApplicationException(_t('Invalid viewer'));
				}
				
				$va_display_info = caGetMediaDisplayInfo('media_overlay', $vs_mimetype);
				if ($t_subject && 
					(($vn_use_universal_viewer_for_image_list_length = caGetOption('use_universal_viewer_for_image_list_length_at_least', $va_display_info, null))
					||
					($vn_use_mirador_for_image_list_length = caGetOption('use_mirador_for_image_list_length_at_least', $va_display_info, null)))
				) {
					$vn_image_count = $t_subject->numberOfRepresentationsOfClass('image');
					$vn_rep_count = $t_subject->getRepresentationCount();
				
					// Are there enough representations? Are all representations images? 
					if ($vn_image_count == $vn_rep_count) {
						if(!is_null($vn_use_universal_viewer_for_image_list_length) && ($vn_image_count >= $vn_use_universal_viewer_for_image_list_length)) {
							$va_display_info['viewer'] = $vs_viewer_name = 'UniversalViewer';
						} elseif(!is_null($vn_use_mirador_for_image_list_length) && ($vn_image_count >= $vn_use_mirador_for_image_list_length)) {
							$va_display_info['viewer'] = $vs_viewer_name = 'Mirador';
						}
					}
				}
				
				$this->response->addContent($vs_viewer_name::getViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => $va_display_info]));
				return;
				break;
			case 'attribute':
				$t_instance = new ca_attribute_values($va_identifier['id']);
				$t_instance->useBlobAsMediaField(true);
				$t_attr = new ca_attributes($t_instance->get('attribute_id'));
				$t_subject = $this->opo_datamodel->getInstanceByTableNum($t_attr->get('table_num'), true);
				$t_subject->load($t_attr->get('row_id'));
				
				if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('value_blob', 'original', 'MIMETYPE')))) {
					throw new ApplicationException(_t('Invalid viewer'));
				}
				
				$t_instance = new ca_attribute_values($va_identifier['id']);
				$t_instance->useBlobAsMediaField(true);
				$t_attr = new ca_attributes($t_instance->get('attribute_id'));
				$t_subject = $this->opo_datamodel->getInstanceByTableNum($t_attr->get('table_num'), true);
				$t_subject->load($t_attr->get('row_id'));
				
				$this->response->addContent($vs_viewer_name::getViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => caGetMediaDisplayInfo('media_overlay', $vs_mimetype)]));
				return;
				break;
		}
		
		throw new ApplicationException(_t('Invalid type'));
	}
	# -------------------------------------------------------
	/**
	 * Provide in-viewer search for those that support it (Eg. UniversalViewer)
	 */
	public function SearchMediaData() {
	    list($vn_subject_id, $t_subject) = $this->_initView();
		
		if (!$t_subject->isReadable($this->request)) { 
			throw new ApplicationException(_t('Cannot view media'));
		}
		
		$ps_identifier = $this->request->getParameter('identifier', pString);
		if (!($va_identifier = caParseMediaIdentifier($ps_identifier))) {
			throw new ApplicationException(_t('Invalid identifier %1', $ps_identifier));
		}
		
		$app = AppController::getInstance();
		$app->removeAllPlugins();
		
		switch($va_identifier['type']) {
			case 'representation':
                $t_instance = new ca_object_representations($va_identifier['id']);
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'original', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::searchViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => $va_display_info]));
                return;
                break;
			case 'attribute':
                $t_instance = new ca_object_representations($va_identifier['id']);
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'original', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::searchViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => $va_display_info]));
                return;
                break;
        }
	}
	# -------------------------------------------------------
	/**
	 * Provide in-viewer search for those that support it (Eg. UniversalViewer)
	 */
	public function MediaDataAutocomplete() {
	    list($vn_subject_id, $t_subject) = $this->_initView();
		
		if (!$t_subject->isReadable($this->request)) { 
			throw new ApplicationException(_t('Cannot view media'));
		}
		
		$ps_identifier = $this->request->getParameter('identifier', pString);
		if (!($va_identifier = caParseMediaIdentifier($ps_identifier))) {
			throw new ApplicationException(_t('Invalid identifier %1', $ps_identifier));
		}
		
		$app = AppController::getInstance();
		$app->removeAllPlugins();
		
		switch($va_identifier['type']) {
			case 'representation':
                $t_instance = new ca_object_representations($va_identifier['id']);
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'original', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::autocomplete($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => $va_display_info]));
                return;
                break;
			case 'attribute':
                $t_instance = new ca_object_representations($va_identifier['id']);
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'original', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::autocomplete($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => $va_display_info]));
                return;
                break;
        }
	}
	# -------------------------------------------------------
	/**
	 * Returns JSON feed of annotations on an object representation
	 *
	 * Expects the following request parameters:
	 *		representation_id = the id of the ca_object_representations record to display; the representation must belong to the specified object
	 *
	 */
	public function GetAnnotations() {
		$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
		$t_rep = new ca_object_representations($pn_representation_id);

		$va_annotations_raw = $t_rep->getAnnotations();
		$va_annotations = array();

		if(is_array($va_annotations_raw)) {
			foreach($va_annotations_raw as $vn_annotation_id => $va_annotation) {
				$va_annotations[] = array(
					'annotation_id' => $va_annotation['annotation_id'],
					'x' => 				caGetOption('x', $va_annotation, 0, array('castTo' => 'float')),
					'y' => 				caGetOption('y', $va_annotation, 0, array('castTo' => 'float')),
					'w' => 				caGetOption('w', $va_annotation, 0, array('castTo' => 'float')),
					'h' => 				caGetOption('h', $va_annotation, 0, array('castTo' => 'float')),
					'tx' => 			caGetOption('tx', $va_annotation, 0, array('castTo' => 'float')),
					'ty' => 			caGetOption('ty', $va_annotation, 0, array('castTo' => 'float')),
					'tw' => 			caGetOption('tw', $va_annotation, 0, array('castTo' => 'float')),
					'th' => 			caGetOption('th', $va_annotation, 0, array('castTo' => 'float')),
					'points' => 		caGetOption('points', $va_annotation, array(), array('castTo' => 'array')),
					'label' => 			caGetOption('label', $va_annotation, '', array('castTo' => 'string')),
					'description' => 	caGetOption('description', $va_annotation, '', array('castTo' => 'string')),
					'type' => 			caGetOption('type', $va_annotation, 'rect', array('castTo' => 'string')),
					'locked' => 		caGetOption('locked', $va_annotation, '0', array('castTo' => 'string')),
					'options' => 		caGetOption('options', $va_annotation, array(), array('castTo' => 'array')),
					'key' =>			caGetOption('key', $va_annotation, null)
				);
			}
			
			if (is_array($va_media_scale = $t_rep->getMediaScale('media'))) {
				$va_annotations[] = $va_media_scale;
			}
		}

		$this->view->setVar('annotations', $va_annotations);
		$this->render('ajax_representation_annotations_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Saves annotations to an object representation
	 *
	 * Expects the following request parameters:
	 *		representation_id = the id of the ca_object_representations record to save annotations to; the representation must belong to the specified object
	 *
	 */
	public function SaveAnnotations() {
		global $g_ui_locale_id;
		$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
		$t_rep = new ca_object_representations($pn_representation_id);

		$pa_annotations = $this->request->getParameter('save', pArray);

		$va_annotation_ids = array();
		if (is_array($pa_annotations)) {
			foreach($pa_annotations as $vn_i => $va_annotation) {
				$vs_label = (isset($va_annotation['label']) && ($va_annotation['label'])) ? $va_annotation['label'] : '';
				if (isset($va_annotation['annotation_id']) && ($vn_annotation_id = $va_annotation['annotation_id'])) {
					// edit existing annotation
					$t_rep->editAnnotation($vn_annotation_id, $g_ui_locale_id, $va_annotation, 0, 0);
					$va_annotation_ids[$va_annotation['index']] = $vn_annotation_id;
				} else {
					// new annotation
					$va_annotation_ids[$va_annotation['index']] = $t_rep->addAnnotation($vs_label, $g_ui_locale_id, $this->request->getUserID(), $va_annotation, 0, 0);
				}
			}
		}
		$va_annotations = array(
			'error' => $t_rep->numErrors() ? join("; ", $t_rep->getErrors()) : null,
			'annotation_ids' => $va_annotation_ids
		);

		$pa_annotations = $this->request->getParameter('delete', pArray);

		if (is_array($pa_annotations)) {
			foreach($pa_annotations as $vn_to_delete_annotation_id) {
				$t_rep->removeAnnotation($vn_to_delete_annotation_id);
			}
		}

		// save scale if set
		if (
			($vs_measurement = $this->request->getParameter('measurement', pString))
			&&
			(strlen($vn_width = $this->request->getParameter('width', pFloat)))
			&&
			(strlen($vn_height = $this->request->getParameter('height', pFloat)))
		) {
			$t_rep = new ca_object_representations($pn_representation_id);
			$vn_image_width = (int)$t_rep->getMediaInfo('media', 'original', 'WIDTH');
			$vn_image_height = (int)$t_rep->getMediaInfo('media', 'original', 'HEIGHT');
			$t_rep->setMediaScale('media', $vs_measurement, sqrt(pow($vn_width * $vn_image_width, 2) + pow($vn_height * $vn_image_height, 2))/$vn_image_width);
			$va_annotations = array_merge($va_annotations, $t_rep->getMediaScale('media'));
		}

		$this->view->setVar('annotations', $va_annotations);
		$this->render('ajax_representation_annotations_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Returns media viewer help text for display
	 */
	public function ViewerHelp() {
		$this->render('../objects/viewer_help_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Apply changes made in representation editor to representation media
	 *
	 */
	public function ProcessMedia() {
		list($vn_object_id, $t_object) = $this->_initView();
		$pn_representation_id 	= $this->request->getParameter('representation_id', pInteger);
		$ps_op 					= $this->request->getParameter('op', pString);
		$pn_angle 				= $this->request->getParameter('angle', pInteger);
		$pb_revert 				= (bool)$this->request->getParameter('revert', pInteger);

		$t_rep = new ca_object_representations($pn_representation_id);
		if (!$t_rep->getPrimaryKey()) {
			$va_response = array(
				'action' => 'process', 'status' => 20, 'message' => _t('Invalid representation_id')
			);
		} else {
			if ($t_rep->applyMediaTransformation('media', $ps_op, array('angle' => $pn_angle), array('revert' => $pb_revert))) {
				$va_response = array(
					'action' => 'process', 'status' => 0, 'message' => 'OK', 'op' => $ps_op, 'angle' => $pn_angle
				);
			} else {
				$va_response = array(
					'action' => 'process', 'status' => 10, 'message' => _t('Transformation failed')
				);
			}
		}

		$this->view->setVar('response', $va_response);
		$this->render('object_representation_process_media_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Undo changes made in representation editor to representation media
	 *
	 */
	public function RevertMedia() {
		list($vn_object_id, $t_object) = $this->_initView();
		$pn_representation_id 	= $this->request->getParameter('representation_id', pInteger);
		if(!$vn_object_id) { $vn_object_id = 0; }
		$t_rep = new ca_object_representations($pn_representation_id);
		if ($t_rep->removeMediaTransformations('media')) {
			$va_response = array(
				'action' => 'revert', 'status' => 0
			);
		} else {
			$va_response = array(
				'action' => 'revert', 'status' => 10
			);
		}
		$this->view->setVar('response', $va_response);
		$this->render('object_representation_process_media_json.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function MediaReplicationControls($pt_representation=null) {
		if ($pt_representation) {
			$pn_representation_id = $pt_representation->getPrimaryKey();
			$t_rep = $pt_representation;
		} else {
			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
			$t_rep = new ca_object_representations($pn_representation_id);
		}
		$this->view->setVar('target_list', $t_rep->getAvailableMediaReplicationTargetsAsHTMLFormElement('target', 'media'));
		$this->view->setVar('representation_id', $pn_representation_id);
		$this->view->setVar('t_representation', $t_rep);

		$this->render('object_representation_media_replication_controls_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function StartMediaReplication() {
		$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
		$ps_target = $this->request->getParameter('target', pString);
		$t_rep = new ca_object_representations($pn_representation_id);

		$this->view->setVar('target_list', $t_rep->getAvailableMediaReplicationTargetsAsHTMLFormElement('target', 'media'));
		$this->view->setVar('representation_id', $pn_representation_id);
		$this->view->setVar('t_representation', $t_rep);
		$this->view->setVar('selected_target', $ps_target);

		$t_rep->replicateMedia('media', $ps_target);

		$this->MediaReplicationControls($t_rep);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function RemoveMediaReplication() {
		$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
		$ps_target = $this->request->getParameter('target', pString);
		$ps_key = urldecode($this->request->getParameter('key', pString));
		$t_rep = new ca_object_representations($pn_representation_id);

		$this->view->setVar('target_list', $t_rep->getAvailableMediaReplicationTargetsAsHTMLFormElement('target', 'media'));
		$this->view->setVar('representation_id', $pn_representation_id);
		$this->view->setVar('t_representation', $t_rep);
		$this->view->setVar('selected_target', $ps_target);

		$t_rep->removeMediaReplication('media', $ps_target, $ps_key);

		$this->MediaReplicationControls($t_rep);
	}
	# -------------------------------------------------------
	# File download
	# -------------------------------------------------------
	/**
	 * Download all media attached to specified object (not necessarily open for editing)
	 * Includes all representation media attached to the specified object + any media attached to other
	 * objects in the same object hierarchy as the specified object. 
	 */
	public function DownloadMedia($pa_options=null) {
		list($vn_subject_id, $t_subject) = $this->_initView();
		$pn_representation_id 	= $this->request->getParameter('representation_id', pInteger);
		$pn_value_id = $this->request->getParameter('value_id', pInteger);
		if ($pn_value_id) {
			return $this->DownloadAttributeFile();
		}
		$ps_version = $this->request->getParameter('version', pString);
		if (!$vn_subject_id) { return; }

		$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');

		if (!$ps_version) { $ps_version = 'original'; }
		$o_view->setVar('version', $ps_version);

		$va_ancestor_ids = ($t_subject->isHierarchical()) ? $t_subject->getHierarchyAncestors(null, array('idsOnly' => true, 'includeSelf' => true)) : array($vn_subject_id);
		if ($vn_parent_id = array_pop($va_ancestor_ids)) {
			$t_subject->load($vn_parent_id);
			array_unshift($va_ancestor_ids, $vn_parent_id);
		}

		$va_child_ids = ($t_subject->isHierarchical()) ? $t_subject->getHierarchyChildren(null, array('idsOnly' => true)) : array($vn_subject_id);

		foreach($va_ancestor_ids as $vn_id) {
			array_unshift($va_child_ids, $vn_id);
		}

		$vn_c = 1;
		$va_file_names = array();
		$va_file_paths = array();
		$va_child_ids = array_unique($va_child_ids);
		
		$t_download_log = new Downloadlog();
		foreach($va_child_ids as $vn_child_id) {
			if (!$t_subject->load($vn_child_id)) { continue; }
			if ($t_subject->tableName() == 'ca_object_representations') {
				$va_reps = array(
					$vn_child_id => array(
						'representation_id' => $vn_child_id,
						'info' => array($ps_version => $t_subject->getMediaInfo('media', $ps_version))
					)
				);
			} else {
				$va_reps = $t_subject->getRepresentations(array($ps_version));
			}
			$vs_idno = $t_subject->get('idno');
			
			$vb_download_for_record = false;
			foreach($va_reps as $vn_representation_id => $va_rep) {
				if ($pn_representation_id && ($pn_representation_id != $vn_representation_id)) { continue; }
				$vb_download_for_record = true;
				$va_rep_info = $va_rep['info'][$ps_version];
				$vs_idno_proc = preg_replace('![^A-Za-z0-9_\-]+!', '_', $vs_idno);
				switch($this->request->user->getPreference('downloaded_file_naming')) {
					case 'idno':
						$vs_file_name = $vs_idno_proc.'_'.$vn_c.'.'.$va_rep_info['EXTENSION'];
						break;
					case 'idno_and_version':
						$vs_file_name = $vs_idno_proc.'_'.$ps_version.'_'.$vn_c.'.'.$va_rep_info['EXTENSION'];
						break;
					case 'idno_and_rep_id_and_version':
						$vs_file_name = $vs_idno_proc.'_representation_'.$vn_representation_id.'_'.$ps_version.'.'.$va_rep_info['EXTENSION'];
						break;
					case 'original_name':
					default:
						if ($va_rep['info']['original_filename']) {
							$va_tmp = explode('.', $va_rep['info']['original_filename']);
							if (sizeof($va_tmp) > 1) {
								if (strlen($vs_ext = array_pop($va_tmp)) < 3) {
									$va_tmp[] = $vs_ext;
								}
							}
							$vs_file_name = join('_', $va_tmp);
						} else {
							$vs_file_name = $vs_idno_proc.'_representation_'.$vn_representation_id.'_'.$ps_version;
						}

						if (isset($va_file_names[$vs_file_name.'.'.$va_rep_info['EXTENSION']])) {
							$vs_file_name.= "_{$vn_c}";
						}
						$vs_file_name .= '.'.$va_rep_info['EXTENSION'];
						break;
				}
				
				$va_file_names[$vs_file_name] = true;
				$o_view->setVar('version_download_name', $vs_file_name);

				//
				// Perform metadata embedding
				$t_rep = new ca_object_representations($va_rep['representation_id']);
				if(!($vs_path = caEmbedMediaMetadataIntoFile($t_rep->getMediaPath('media', $ps_version),
					$t_subject->tableName(), $t_subject->getPrimaryKey(), $t_subject->getTypeCode(), // subject table info
					$t_rep->getPrimaryKey(), $t_rep->getTypeCode() // rep info
				))) {
					$vs_path = $t_rep->getMediaPath('media', $ps_version);
				}

				$va_file_paths[$vs_path] = $vs_file_name;

				$vn_c++;
			}
			if($vb_download_for_record){
				$t_download_log->log(array(
						"user_id" => $this->request->getUserID(), 
						"ip_addr" => $_SERVER['REMOTE_ADDR'] ?  $_SERVER['REMOTE_ADDR'] : null, 
						"table_num" => $this->opo_datamodel->getTableNum($this->ops_table_name), 
						"row_id" => $vn_child_id, 
						"representation_id" => $pn_representation_id ? $pn_representation_id : null, 
						"download_source" => "providence"
				));
			}
		}

		if (!($vn_limit = ini_get('max_execution_time'))) { $vn_limit = 30; }
		set_time_limit($vn_limit * 2);
		
		if (sizeof($va_file_paths) > 1) {
			$o_zip = new ZipStream();
			foreach($va_file_paths as $vs_path => $vs_name) {
				$o_zip->addFile($vs_path, $vs_name);
			}
			$o_view->setVar('zip_stream', $o_zip);
			$o_view->setVar('archive_name', preg_replace('![^A-Za-z0-9\.\-]+!', '_', $t_subject->get('idno')).'.zip');
		} else {
			foreach($va_file_paths as $vs_path => $vs_name) {
				$o_view->setVar('archive_path', $vs_path);
				$o_view->setVar('archive_name', $vs_name);
				break;
			}
		}


		$this->response->addContent($o_view->render('download_file_binary.php'));
		set_time_limit($vn_limit);
	}
	# -------------------------------------------------------
	/**
	 * Initiates user download of file stored in a file attribute, returning file in response to request.
	 * Adds download output to response directly. No view is used.
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 */
	public function DownloadAttributeFile($pa_options=null) {
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


		if (!$this->_checkAccess($t_subject)) { return false; }

		//
		// Does user have access to bundle?
		//
		if (($this->request->user->getBundleAccessLevel($this->ops_table_name, $t_element->get('element_code'))) < __CA_BUNDLE_ACCESS_READONLY__) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');

		// get value
		$t_element = new ca_metadata_elements($t_attr_val->get('element_id'));
		switch($t_element->get('datatype')) {
			case __CA_ATTRIBUTE_VALUE_FILE__:
				$t_attr_val->useBlobAsFileField(true);
			
				if (!($vs_name = trim($t_attr_val->get('value_longtext2')))) { $vs_name = _t("downloaded_file"); }
				$vs_path = $t_attr_val->getFilePath('value_blob');
				break;
			case __CA_ATTRIBUTE_VALUE_MEDIA__:
				$t_attr_val->useBlobAsMediaField(true);
				if (!in_array($ps_version, $t_attr_val->getMediaVersions('value_blob'))) { $ps_version = 'original'; }
				
				$vs_path = $t_attr_val->getMediaPath('value_blob', $ps_version);
				$vs_path_ext = pathinfo($vs_path, PATHINFO_EXTENSION);
				if ($vs_name = trim($t_attr_val->get('value_longtext2'))) {
					$vs_file_name = pathinfo($vs_name, PATHINFO_FILENAME);
					$vs_name = "{$vs_file_name}.{$vs_path_ext}";
				} else {
					$vs_name = _t("downloaded_file.%1", $vs_path_ext);
				}
				break;
			default:
				// invalid element type
				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode(_t('Invalid file')));
				break;		
		}
		$t_download_log = new Downloadlog();
		$t_download_log->log(array(
				"user_id" => $this->request->getUserID(), 
				"ip_addr" => $_SERVER['REMOTE_ADDR'] ?  $_SERVER['REMOTE_ADDR'] : null, 
				"table_num" => $this->opo_datamodel->getTableNum($this->ops_table_name), 
				"row_id" => $vn_subject_id, 
				"representation_id" => null, 
				"download_source" => "providence"
		));

		$o_view->setVar('archive_path', $vs_path);
		$o_view->setVar('archive_name', $vs_name);
		
		// send download
		$this->response->addContent($o_view->render('download_file_binary.php'));
	}
	# -------------------------------------------------------
	/**
	 * Handle ajax media uploads from editor
	 */
	public function UploadFiles($pa_options=null) {
		if (!$this->request->isLoggedIn() || ((int)$this->request->user->get('userclass') !== 0)) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}

		// use configured directory to dump media with fallback to standard tmp directory
		if (!is_writeable($vs_tmp_directory = $this->request->config->get('ajax_media_upload_tmp_directory'))) {
			$vs_tmp_directory = caGetTempDirPath();
		}

		$vn_user_id = $this->request->getUserID();
		$vs_user_dir = $vs_tmp_directory."/userMedia{$vn_user_id}";
		if(!file_exists($vs_user_dir)) {
			@mkdir($vs_user_dir);
		}
		if (!($vn_timeout = (int)$this->request->config->get('ajax_media_upload_tmp_directory_timeout'))) {
			$vn_timeout = 24 * 60 * 60;
		}


		// Cleanup any old files here
		$va_files_to_delete = caGetDirectoryContentsAsList($vs_user_dir, true, false, false, true, array('modifiedSince' => time() - $vn_timeout));
		foreach($va_files_to_delete as $vs_file_to_delete) {
			@unlink($vs_file_to_delete);
		}

		$va_stored_files = array();
		foreach($_FILES as $vn_i => $va_file) {
			$vs_dest_filename = pathinfo($va_file['tmp_name'], PATHINFO_FILENAME);
			copy($va_file['tmp_name'], $vs_dest_path = $vs_tmp_directory."/userMedia{$vn_user_id}/{$vs_dest_filename}");

			// write file metadata
			file_put_contents("{$vs_dest_path}_metadata", json_encode(array(
				'original_filename' => $va_file['name'],
				'size' => filesize($vs_dest_path)
			)));
			$va_stored_files[$vn_i] = "userMedia{$vn_user_id}/{$vs_dest_filename}"; // only return the user directory and file name, not the entire path
		}

		$this->response->addContent(json_encode($va_stored_files));
	}
	# -------------------------------------------------------
	/**
	 * Handle sort requests from form editor.
	 * Gets passed a table name, a list of ids and a key to sort on. Will return a JSON list of the same IDs, just sorted.
	 */
	public function Sort() {
		if (!$this->getRequest()->isLoggedIn() || ((int)$this->getRequest()->user->get('userclass') !== 0)) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/2320?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		$vs_table_name = $this->getRequest()->getParameter('table', pString);
		$t_instance = $this->getAppDatamodel()->getInstance($vs_table_name, true);

		$va_ids = explode(',', $this->getRequest()->getParameter('ids', pString));
		$va_sort_keys = explode(',', $this->getRequest()->getParameter('sortKeys', pString));

		if(!($vs_sort_direction = strtolower($this->getRequest()->getParameter('sortDirection', pString))) || !in_array($vs_sort_direction, array('asc', 'desc'))) {
			$vs_sort_direction = 'asc';
		}

		if(!$t_instance) { return; }
		if(!is_array($va_ids) || !sizeof($va_ids)) { return; }
		if(!is_array($va_sort_keys) || !sizeof($va_sort_keys)) { return; }

		$o_res = caMakeSearchResult($t_instance->tableName(), $va_ids, array('sort' => $va_sort_keys, 'sortDirection' => $vs_sort_direction));
		$va_sorted_ids = $o_res->getAllFieldValues($t_instance->primaryKey());

		$this->response->addContent(json_encode($va_sorted_ids));
	}
	# -------------------------------------------------------
}
