<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseEditorController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/printHelpers.php");
require_once(__CA_APP_DIR__."/helpers/themeHelpers.php");
require_once(__CA_APP_DIR__."/helpers/exportHelpers.php");
require_once(__CA_LIB_DIR__."/ResultContext.php");
require_once(__CA_LIB_DIR__.'/Print/PDFRenderer.php');
require_once(__CA_LIB_DIR__.'/Parsers/ZipStream.php');
require_once(__CA_LIB_DIR__.'/Media/MediaViewerManager.php');
require_once(__CA_LIB_DIR__.'/Logging/Downloadlog.php');

define('__CA_SAVE_AND_RETURN_STACK_SIZE__', 30);

class BaseEditorController extends ActionController {
	# -------------------------------------------------------
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
 		AssetLoadManager::register('leaflet');
 		AssetLoadManager::register('3dmodels');

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

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

		//
		// Are we duplicating?
		//
		if (($vs_mode == 'dupe') && $this->request->user->canDoAction('can_duplicate_'.$t_subject->tableName())) {
			if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
				throw new ApplicationException(_t('CSRF check failed'));
				return;
			}
			if (!($vs_type_name = $t_subject->getTypeName())) {
				$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
			}
			// Trigger "before duplicate" hook
			$this->opo_app_plugin_manager->hookBeforeDuplicateItem(
				[
					'id' => $vn_subject_id, 
					'table_num' => $t_subject->tableNum(),
					'table_name' => $t_subject->tableName(), 
					'instance' => $t_subject,
					'request' => $this->request
				]
			);

			if ($t_dupe = $t_subject->duplicate(array(
				'user_id' => $this->request->getUserID(),
				'duplicate_nonpreferred_labels' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_nonpreferred_labels'),
				'duplicate_attributes' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_attributes'),
				'duplicate_relationships' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_relationships'),
				'duplicate_current_relationships_only' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_current_relationships_only'),
				'duplicate_relationship_attributes' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_relationship_attributes'),
				'duplicate_media' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_media'),
				'duplicate_subitems' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_subitems'),
				'duplicate_element_settings' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_element_settings'),
				'duplicate_children' => $this->request->user->getPreference($t_subject->tableName().'_duplicate_children')
			))) {
				$this->notification->addNotification(_t('Duplicated %1 "%2" (%3)', $vs_type_name, $t_subject->getLabelForDisplay(), $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD'))), __NOTIFICATION_TYPE_INFO__);

				// Trigger duplicate hook
				$this->opo_app_plugin_manager->hookDuplicateItem(
					[
						'id' => $vn_subject_id, 
						'table_num' => $t_subject->tableNum(), 
						'table_name' => $t_subject->tableName(), 
						'instance' => $t_subject, 
						'duplicate' => $t_dupe,
						'request' => $this->request
					]
				);

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
			if (($t_instance = Datamodel::getInstanceByTableName($this->ops_table_name)) && $t_instance->load($vn_above_id)) {
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
					$t_parent = Datamodel::getInstanceByTableName($this->ops_table_name);
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
			[],
			[]
		);
		if (!$this->request->getActionExtra() || !isset($va_nav['fragment'][str_replace("Screen", "screen_", $this->request->getActionExtra())])) {
			if (($vs_bundle = $this->request->getParameter('bundle', pString)) && ($vs_bundle_screen = $t_ui->getScreenWithBundle($vs_bundle))) {
				// jump to screen containing url-specified bundle
				$this->request->setActionExtra($vs_bundle_screen);
			} elseif(isset($va_nav['defaultScreen'])) {
				$this->request->setActionExtra($va_nav['defaultScreen']);
			}
		}
		$this->view->setVar('t_ui', $t_ui);

		if ($vn_subject_id) {
			// set last edited
			Session::setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id);
		}

		// Trigger "EditItem" hook on form load
		$params = $this->opo_app_plugin_manager->hookEditItem(
			[
				'id' => $vn_subject_id, 
				'table_num' => $t_subject->tableNum(), 
				'table_name' => $t_subject->tableName(), 
				'instance' => $t_subject,
				'request' => $this->request
			]
		);
		
		// Pass any values for be forced into the form from plugins (Eg. prepopulate on a new record) 
		$this->view->setVar('forced_values', $params['forced_values'] ?? null);

		if (!($vs_view = caGetOption('view', $pa_options, null))) {
			$vs_view = 'screen_html';
		}

		// save where we are in session, for "Save and return" button
		if($vn_subject_id) { // don't save "empty" / new record editor location. pk has to be set
			$va_save_and_return = Session::getVar('save_and_return_locations');
			if(!is_array($va_save_and_return)) { $va_save_and_return = []; }

			$va_save = array(
				'table' => $t_subject->tableName(),
				'key' => $vn_subject_id,
				'url_path' => $this->getRequest()->getFullUrlPath()
			);

			Session::setVar('save_and_return_locations', caPushToStack($va_save, $va_save_and_return, __CA_SAVE_AND_RETURN_STACK_SIZE__));
		}

		// if we came here through a rel link, show save and return button
		$this->getView()->setVar('show_save_and_return', (bool) $this->getRequest()->getParameter('rel', pInteger));

		// Are there metadata dictionary alerts?
		$violations_to_prompt = $t_subject->getMetadataDictionaryRuleViolations(null, ['limitToShowAsPrompt' => true, 'screen_id' => $this->request->getActionExtra()]);
		$this->getView()->setVar('show_show_notifications', (is_array($violations_to_prompt) && (sizeof($violations_to_prompt) > 0)));

		$this->render("{$vs_view}.php");
	}
	# -------------------------------------------------------
	/**
	 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
	 *
	 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
	 */
	public function Save($pa_options=null) {
	    if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	    	$this->Edit();
	    	return;
	    }
	    
		$vb_no_save_error = false;
	    
		list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id, $vn_after_id, $vs_rel_table, $vn_rel_type_id, $vn_rel_id) = $this->_initView($pa_options);
		/** @var $t_subject BundlableLabelableBaseModelWithAttributes */
		if (!is_array($pa_options)) { $pa_options = []; }

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

		if($vn_above_id) {
			// Convert "above" id (the id of the record we're going to make the newly created record parent of
			if (($t_instance = Datamodel::getInstanceByTableName($this->ops_table_name)) && $t_instance->load($vn_above_id)) {
				$vn_parent_id = $t_instance->get($vs_parent_id_fld = $t_instance->getProperty('HIERARCHY_PARENT_ID_FLD'));
				$this->request->setParameter($vs_parent_id_fld, $vn_parent_id);
				$this->view->setVar('parent_id', $vn_parent_id);
			}
		}

		// relate existing records via Save() link
		if($vn_subject_id && $vs_rel_table && $vn_rel_type_id && $vn_rel_id) {
			if(Datamodel::tableExists($vs_rel_table)) {
				Debug::msg("[Save()] Relating new record using parameters from request: $vs_rel_table / $vn_rel_type_id / $vn_rel_id");
				if(!$t_subject->relationshipExists($vs_rel_table, $vn_rel_id, $vn_rel_type_id)) { 
					$t_subject->addRelationship($vs_rel_table, $vn_rel_id, $vn_rel_type_id, _t('now'));
				}
			}
			$this->notification->addNotification(_t("Added relationship"), __NOTIFICATION_TYPE_INFO__);
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
					$t_parent = Datamodel::getInstanceByTableName($this->ops_table_name);
					if ($t_parent->load($vn_parent_id)) {
						$this->view->setVar('_context_id', $vn_context_id = $t_parent->get($vs_idno_context_field));
					}
				}
			}

			if ($vn_context_id && !$t_subject->get($vs_idno_context_field)) { $t_subject->set($vs_idno_context_field, $vn_context_id); }
		}

		if (!($vs_type_name = $t_subject->getTypeName())) {
			$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
		}

		if ($vn_subject_id && !$t_subject->getPrimaryKey()) {
			$this->notification->addNotification(_t("%1 does not exist", $vs_type_name), __NOTIFICATION_TYPE_ERROR__);
			return;
		}

		$vb_is_insert = !$t_subject->getPrimaryKey();
		
		$t_subject->isChild();	// sets idno "child" flag
		
		# trigger "BeforeSaveItem" hook
		$this->opo_app_plugin_manager->hookBeforeSaveItem(
			[
				'id' => $vn_subject_id, 
				'table_num' => $t_subject->tableNum(), 
				'table_name' => $t_subject->tableName(), 
				'instance' => &$t_subject, 
				'is_insert' => $vb_is_insert,
				'request' => $this->request
			]
		);

		$vb_save_rc = false;
		$va_opts = array_merge($pa_options, array('ui_instance' => $t_ui));
		if ($this->_beforeSave($t_subject, $vb_is_insert)) {
			if ($vb_save_rc = $t_subject->saveBundlesForScreen($this->request->getActionExtra(), $this->request, $va_opts)) {
				$this->_afterSave($t_subject, $vb_is_insert);
			} elseif($t_subject->hasErrorNumInRange(3600, 3699) || $t_subject->hasErrorNumInRange(2592, 2599)) {
				$vb_no_save_error = true;
				$this->view->setVar('forced_values', $va_opts['ifv']);
			}
			if($t_subject->numErrors() > 0) {
				$this->request->addActionErrors($t_subject->errors, 'saveBundlesForScreen');
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
				Session::setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id);	// set last edited

				// relate newly created record if requested
				if($vs_rel_table && $vn_rel_type_id && $vn_rel_id) {
					if(Datamodel::tableExists($vs_rel_table)) {
						Debug::msg("[Save()] Relating new record using parameters from request: $vs_rel_table / $vn_rel_type_id / $vn_rel_id");
						$t_subject->addRelationship($vs_rel_table, $vn_rel_id, $vn_rel_type_id);
					}
				}

				// Set ACL for newly created record
				if (caACLIsEnabled($t_subject)) {
					$t_subject->setACLUsers(array($this->request->getUserID() => __CA_ACL_EDIT_DELETE_ACCESS__));
					$t_subject->setACLWorldAccess($t_subject->getAppConfig()->get('default_item_access_level'));
				}

				// If "above_id" is set then, we want to load the record pointed to by it and set its' parent to be the newly created record
				// The newly created record's parent is already set to be the current parent of the "above_id"; the net effect of all of this
				// is to insert the newly created record between the "above_id" record and its' current parent.
				if ($vn_above_id && ($t_instance = Datamodel::getInstanceByTableName($this->ops_table_name, true)) && $t_instance->load($vn_above_id)) {
					$t_instance->set('parent_id', $vn_subject_id);
					$t_instance->update();

					if ($t_instance->numErrors()) {
						$this->notification->addNotification(join("; ", $t_instance->getErrors()), __NOTIFICATION_TYPE_ERROR__);
					}
				}
				
				// If "after_id" is set then reset ranks such that saved record follows immediately after
				if ($vn_after_id) {
					$t_subject->setRankAfter($vn_after_id);
					if ($t_subject->numErrors()) {
						$this->notification->addNotification(join("; ", $t_subject->getErrors()), __NOTIFICATION_TYPE_ERROR__);
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
			$va_error_list = [];
			foreach($va_errors as $o_e) {
				$error_num = (int)$o_e->getErrorNumber();
				if($error_num == 2592) { continue; } // don't show "relationship failed" error, as more specific errors will also be present
				$bundle = array_shift(explode('/', $o_e->getErrorSource()));
				$va_error_list[] = "<li><u>".$t_subject->getDisplayLabel($bundle).'</u>: '.$o_e->getErrorDescription()."</li>\n";

				switch($error_num) {
					case 1100:	// duplicate/invalid idno
						if (!$vn_subject_id) {		// can't save new record if idno is not valid (when updating everything but idno is saved if it is invalid)
							$vb_no_save_error = true;
						}
						break;
					default:
						if(($error_num >= 3600) && ($error_num <= 3699)) { 	// failed to create movement for storage location
							$vb_no_save_error = true;
						}
						break;
				}
			}
			if ($vb_no_save_error) {
				$this->notification->addNotification("<div class='heading'>"._t("There are errors preventing <strong>ALL</strong> information from being saved:")."</div><ul class='errorList'>".join("\n", $va_error_list)."</span></ul>", __NOTIFICATION_TYPE_ERROR__);
			} else {
				$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
				$this->notification->addNotification("<span class='heading'>"._t("There are errors preventing information in specific fields from being saved:")."</span><ul class='errorList'>".join("\n", $va_error_list)."</ul>", __NOTIFICATION_TYPE_ERROR__);
			}
		} else {
			$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
			$this->opo_result_context->invalidateCache();	// force new search in case changes have removed this item from the results
			$this->opo_result_context->saveContext();
		}
		
		# trigger "SaveItem" hook
		$this->opo_app_plugin_manager->hookSaveItem(
			[
				'id' => $vn_subject_id, 
				'table_num' => $t_subject->tableNum(), 
				'table_name' => $t_subject->tableName(), 
				'instance' => &$t_subject, 
				'is_insert' => $vb_is_insert, 
				'request' => $this->request
			]
		);

		if (method_exists($this, "postSave")) {
			$this->postSave($t_subject, $vb_is_insert);
		}

		// redirect back to previous item on stack if it's a valid "save and return" request
		$vb_has_errors = (is_array($va_errors) && (sizeof($va_errors) > 0)); // don't redirect back when there were form errors
		if(((bool) $this->getRequest()->getParameter('is_save_and_return', pInteger)) && !$vb_has_errors) {
			$va_save_and_return = Session::getVar('save_and_return_locations');
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
			$va_save_and_return = Session::getVar('save_and_return_locations');
			if(!is_array($va_save_and_return)) { $va_save_and_return = []; }

			$va_save = array(
				'table' => $t_subject->tableName(),
				'key' => $vn_subject_id,
				// dont't direct back to Save action
				'url_path' => str_replace('/Save/', '/Edit/', $this->getRequest()->getFullUrlPath())
			);
			Session::setVar('save_and_return_locations', caPushToStack($va_save, $va_save_and_return, __CA_SAVE_AND_RETURN_STACK_SIZE__));
		}

		// if we came here through a rel link, show save and return button
		$this->getView()->setVar('show_save_and_return', (bool) $this->getRequest()->getParameter('rel', pInteger));

		// Are there metadata dictionary alerts?
		$violations_to_prompt = $t_subject->getMetadataDictionaryRuleViolations(null, ['limitToShowAsPrompt' => true, 'screen_id' => $this->request->getActionExtra()]);
		$this->getView()->setVar('show_show_notifications', is_array($violations_to_prompt) && (sizeof($violations_to_prompt) > 0));
		
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


		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }


		if (!$vs_type_name = $t_subject->getTypeName()) {
			$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
		}

		//
		// Does user have access to row?
		//
		if (caACLIsEnabled($t_subject)) {
			if ($t_subject->checkACLAccessForUser($this->request->user) < __CA_ACL_EDIT_DELETE_ACCESS__) {
				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
				return;
			}
		}
		
		$subject_table = $t_subject->tableName();

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
	        if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	        	$this->Edit();
	        	return;
	        }
	        
			$vb_we_set_transaction = false;
			if (!$t_subject->inTransaction()) {
				$o_t = new Transaction();
				$t_subject->setTransaction($o_t);
				$vb_we_set_transaction = true;
			}
			
			// Do we need to merge content to another record?
			if (($remap_id =  $this->request->getParameter('caReferenceHandlingToRemapToID', pInteger)) && ($this->request->getParameter('caReferenceHandlingTo', pString) == 'remap')) {
				switch($subject_table) {
					case 'ca_relationship_types':
						if ($vn_c = $t_subject->moveRelationshipsToType($remap_id)) {
							$t_target = new ca_relationship_types($remap_id);
							$this->notification->addNotification(($vn_c == 1) ? _t("Transferred %1 relationship to type <em>%2</em>", $vn_c, $t_target->getLabelForDisplay()) : _t("Transferred %1 relationships to type <em>%2</em>", $vn_c, $t_target->getLabelForDisplay()), __NOTIFICATION_TYPE_INFO__);
						}
						break;
					default:
						$merge_opts = ['relationships'];
						if($this->request->getParameter('caReferenceHandlingMetadata', pInteger)) {
							$merge_opts = array_merge($merge_opts, ['preferred_labels', 'nonpreferred_labels', 'intrinsics', 'attributes']);
						}
						try {
							$t_target = $subject_table::merge([$remap_id, $vn_subject_id],['useID' => $remap_id, 'preferredLabelsMode' => 'base', 'intrinsicMode' => 'whenNotSet', 'notification' => $this->notification, 'merge' => $merge_opts]);
						} catch(MergeException $e) {
							$this->notification->addNotification(_t("Could not merge data: %1", $e->getMessage()), __NOTIFICATION_TYPE_ERROR__);
						}
						break;
				}
			} else {
				$t_subject->deleteAuthorityElementReferences();
				
				if ($t_subject->isHierarchical() && is_array($va_children = call_user_func("{$subject_table}::getHierarchyChildrenForIDs", [$t_subject->getPrimaryKey()]))) {
					$t_child = Datamodel::getInstance($this->ops_table_name, true);
					$vn_child_count = 0;
					foreach($va_children as $vn_child_id) {
						$t_child->load($vn_child_id);
						$t_child->delete(true);
						if ($t_child->numErrors() > 0) {
							continue;
						}
						$vn_child_count++;
					}
					if($vn_child_count > 0) {
						$this->notification->addNotification(($vn_child_count == 1) ? _t("Deleted %1 child", $vn_child_count) : _t("Deleted %1 children", $vn_child_count), __NOTIFICATION_TYPE_INFO__);
					}
				}
			}
		
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

				$this->view->setVar($t_subject->primaryKey(), null);
				$this->request->setParameter($t_subject->primaryKey(), null, 'PATH');

				// set last browse id for hierarchy browser
				Session::setVar($this->ops_table_name.'_browse_last_id', $vn_parent_id);

				// Clear out row_id so sidenav is disabled
				$this->request->setParameter($t_subject->primaryKey(), null, 'POST');

				# trigger "DeleteItem" hook
				$this->opo_app_plugin_manager->hookDeleteItem(
					[
						'id' => $vn_subject_id, 
						'table_num' => $t_subject->tableNum(), 
						'table_name' => $subject_table, 
						'instance' => $t_subject,
						'request' => $this->request
					]
				);

				# redirect
				$this->redirectAfterDelete($t_subject);
				return;
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
	protected function redirectAfterDelete($t_subject) {
		$this->getRequest()->close();
		
		$redirect_url = $this->opo_result_context->getResultsUrlForLastFind($this->getRequest(), $t_subject->tableName());
		if (($t_subject->getHierarchyType() === __CA_HIER_TYPE_ADHOC_MONO__) && ($parent_id = $t_subject->get('parent_id')) > 0) {
			$redirect_url = caEditorUrl($this->request, $t_subject->tableName(), $parent_id);
		} elseif(!$redirect_url) {
			$redirect_url = ResultContext::getResultsUrl($this->request, $t_subject->tableName(), 'basic_search');
		}
		
		caSetRedirect($redirect_url);
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


		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

		if((defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) || (bool)$this->request->config->get('display_template_debugger') || ($this->request->user->getPreference('show_template_debugger') !== 'hide')) {
			$this->render('../template_test_html.php');
		}
		

		$t_display = new ca_bundle_displays();
		$va_displays = caExtractValuesByUserLocale($t_display->getBundleDisplays(array('table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'restrictToTypes' => array($t_subject->getTypeID()), 'context' => 'editor_summary')));

		if ((!($vn_display_id = (int)$this->request->getParameter('display_id', pString))) || !isset($va_displays[$vn_display_id])) {
			$vn_display_id = $this->request->user->getVar($t_subject->tableName().'_summary_display_id');
		}
		if (!isset($va_displays[$vn_display_id]) || (is_array($va_displays[$vn_display_id]['settings']['show_only_in'] ?? null) && sizeof($va_displays[$vn_display_id]['settings']['show_only_in']) && !in_array('editor_summary', $va_displays[$vn_display_id]['settings']['show_only_in']))) {
		    $va_tmp = array_filter($va_displays, function($v) { return !isset($v['settings']['show_only_in']) || !is_array($v['settings']['show_only_in']) || in_array('editor_summary', $v['settings']['show_only_in']); });
		    $vn_display_id = sizeof($va_tmp) > 0 ? array_shift(array_keys($va_tmp)) : 0;
		}

		// save where we are in session, for "Save and return" button
		$va_save_and_return = Session::getVar('save_and_return_locations');
		if(!is_array($va_save_and_return)) { $va_save_and_return = []; }

		$va_save = array(
			'table' => $t_subject->tableName(),
			'key' => $vn_subject_id,
			'url_path' => $this->getRequest()->getFullUrlPath()
		);

		Session::setVar('save_and_return_locations', caPushToStack($va_save, $va_save_and_return, __CA_SAVE_AND_RETURN_STACK_SIZE__));

		$this->view->setVar('bundle_displays', $va_displays);
		$this->view->setVar('t_display', $t_display);

		// Check validity and access of specified display
		if ($t_display->load($vn_display_id) && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
			$this->view->setVar('display_id', $vn_display_id);

			$va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => $t_subject->tableNum(), 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true, 'omitEditingInfo' => true));
       
			$va_display_list = [];
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
            $va_display_list = $t_display->getDisplayListForResultsEditor($t_subject->tableName(), ['user_id' => $this->request->getUserID()]);
            
			$this->view->setVar('display_id', 0);
			$this->view->setVar('placements', $va_display_list['displayList']);
			
		}
		$this->view->setVar($t_subject->tableName().'_summary_last_settings', Session::getVar($t_subject->tableName().'_summary_last_settings'));
		
		$this->opo_app_plugin_manager->hookSummarizeItem(
			[
				'id' => $vn_subject_id, 
				'table_num' => $t_subject->tableNum(), 
				'table_name' => $t_subject->tableName(), 
				'instance' => $t_subject, 
				'request' => $this->request
			]
		);

		return $this->render('summary_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Generates display summary of record data based upon a bundle display for print (PDF)
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 */
	public function PrintSummary($pa_options=null) {
		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
		
		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

        if (!is_array($last_settings = Session::getVar($t_subject->tableName().'_summary_last_settings'))) { $last_settings = []; }
        
        $template = $this->request->getParameter('template', pString);
        $display_id = $this->request->getParameter('display_id', pString);
        if(preg_match("!^_pdf_!", $display_id)) {
        	$template = $display_id;
        	$display_id = 0;
        }

		$table = $t_subject->tableName();
		if(($this->request->getParameter('background', pInteger) === 1) && caTaskQueueIsEnabled()) {
			$o_tq = new TaskQueue();
			
			$idno_fld = $t_subject->getProperty('ID_NUMBERING_ID_FIELD');
			$exp_display = $t_subject->getWithTemplate("^{$table}.preferred_labels (^{$table}.{$idno_fld})");
			
			$t_download = new ca_user_export_downloads();
			$t_download->set([
				'created_on' => _t('now'),
				'user_id' => $this->request->getUserID(),
				'status' => 'QUEUED',
				'download_type' => 'SUMMARY',
				'metadata' => ['searchExpression' => $t_subject->primaryKey(true).":{$vn_subject_id}", 'searchExpressionForDisplay' => $exp_display, 'format' => caExportFormatForTemplate($table, $template), 'mode' => 'SUMMARY', 'table' => $table, 'findType' => 'summary']
			]);
			$download_id = $t_download->insert();
			
			if ($o_tq->addTask(
				'dataExport',
				[
					'request' => $_REQUEST,
					'mode' => 'SUMMARY',
					'findType' => 'summary',
					'table' => $table,
					'results' => [$vn_subject_id],
					'format' => caExportFormatForTemplate($table, $template),
					'sort' => null,
					'sortDirection' => null,
					'searchExpression' => $t_subject->primaryKey(true).":{$vn_subject_id}",
					'searchExpressionForDisplay' => $exp_display,
					'user_id' => $this->request->getUserID(),
					'download_id' => $download_id
				],
				["priority" => 100, "entity_key" => join(':', [$table, $vn_subject_id]), "row_key" => null, 'user_id' => $this->request->getUserID()]))
			{
				Session::setVar("{$table}_summary_export_in_background", true);
				caGetPrintTemplateParameters('summary', $template, ['view' => $this->view, 'request' => $this->request]);
				$this->request->isDownload(false);
				$this->notification->addNotification(_t("Summary is queued for processing and will be sent to %1 when ready.", $this->request->user->get('ca_users.email')), __NOTIFICATION_TYPE_INFO__);
				
				$this->Summary();
				return;
			} else {
				$this->postError(100, _t("Couldn't queue export"), "BaseFindController->export()");
			}
		}
		Session::setVar("{$table}_summary_export_in_background", false);
		
		caExportSummary($this->request, $t_subject, $template, $display_id, 'output.pdf', 'output.pdf', []);
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

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

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

		$va_extracted_values = [];
		foreach($va_values as $o_value) {
			$va_extracted_values[] = $o_value->getDisplayValues(null, ['output' => 'text']);
		}
		$this->view->setVar('valuesAsElementCodeArrays', $va_extracted_values);

		$va_barcode_files_to_delete = [];

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
	 * Generates options form for printable template
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 */
	public function PrintSummaryOptions(?array $options=null) {
		$form = $this->request->getParameter('form', pString);
		
		if(!preg_match("!^_([a-z]+)_(.*)$!", $form, $m)) {
			throw new ApplicationException(_t('Invalid template'));
		}
		
		$values = Session::getVar("print_summary_options_{$m[2]}");
		$form_options = caEditorPrintParametersForm('summary', $m[2], $values);
		
		$this->view->setVar('form', $m[2]);
		$this->view->setVar('options', $form_options);
		
		if(sizeof($form_options) === 0) {
			$this->response->setHTTPResponseCode(204, _t('No options available'));
		}
		
		$this->render("../generic/ajax_print_summary_options_form_html.php");
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


		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }


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
	public function Access(?array $options=null) {
		AssetLoadManager::register('tableList');
		list($subject_id, $t_subject) = $this->_initView($options);
		if(!method_exists($t_subject, 'supportsACL') || !$t_subject->supportsACL()) {  throw new ApplicationException(_t('ACL not enabled')); }

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

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
	public function SetAccess(?array $options=null) {
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	    	throw new ApplicationException(_t('CSRF check failed'));
	    	return;
	    }
		list($subject_id, $t_subject) = $this->_initView($options);
		if(!method_exists($t_subject, 'supportsACL') || !$t_subject->supportsACL()) {  throw new ApplicationException(_t('ACL not enabled')); }

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

		if ((!$t_subject->isSaveable($this->request)) || (!$this->request->user->canDoAction('can_change_acl_'.$t_subject->tableName()))) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2570?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		$subject_table = $t_subject->tableName();
		$subject_pk = $t_subject->primaryKey();
		
		$form_prefix = $this->request->getParameter('_formName', pString);

		$this->opo_app_plugin_manager->hookBeforeSaveItem(array(
			'id' => $subject_id,
			'table_num' => $t_subject->tableNum(),
			'table_name' => $subject_table, 
			'instance' => &$t_subject,
			'is_insert' => false)
		);
		
		// Force all?
		if(($set_all = $this->request->getParameter('set_all_acl_inherit_from_parent', pInteger)) || ($set_none = $this->request->getParameter('set_none_acl_inherit_from_parent', pInteger))) {
			if(!ca_acl::setInheritanceForAllChildRows($t_subject, $t_subject->getPrimaryKey(), $set_all)) {
				$this->postError(1250, _t('Could not set ACL inheritance settings on child items'),"BaseEditorController->SetAccess()");
			}
			$_REQUEST['form_timestamp'] = time();
		}
		if(
			($subject_table === 'ca_collections')
			&&
			($set_all = $this->request->getParameter('set_all_acl_inherit_from_ca_collections', pInteger)) || ($set_none = $this->request->getParameter('set_none_acl_inherit_from_ca_collections', pInteger))
		) {
			if(!ca_acl::setInheritanceForRelatedObjects($t_subject, $t_subject->getPrimaryKey(), $set_all)) {
				$this->postError(1250, _t('Could not set ACL inheritance settings on related objects'),"BaseEditorController->SetAccess()");
			}
			$_REQUEST['form_timestamp'] = time();
		}

		// Save user ACL's
		$users_to_set = [];
		foreach($_REQUEST as $key => $val) {
			if (preg_match("!^{$form_prefix}_user_id(.*)$!", $key, $matches)) {
				$user_id = (int)$this->request->getParameter($form_prefix.'_user_id'.$matches[1], pInteger);
				$access = $this->request->getParameter($form_prefix.'_user_access_'.$matches[1], pInteger);
				if ($access >= 0) {
					$users_to_set[$user_id] = $access;
				}
			}
		}
		$t_subject->setACLUsers($users_to_set, ['preserveInherited' => true]);

		// Save group ACL's
		$groups_to_set = [];
		foreach($_REQUEST as $key => $val) {
			if (preg_match("!^{$form_prefix}_group_id(.*)$!", $key, $matches)) {
				$group_id = (int)$this->request->getParameter($form_prefix.'_group_id'.$matches[1], pInteger);
				$access = $this->request->getParameter($form_prefix.'_group_access_'.$matches[1], pInteger);
				if ($access >= 0) {
					$groups_to_set[$group_id] = $access;
				}
			}
		}
		$t_subject->setACLUserGroups($groups_to_set, ['preserveInherited' => true]);

		// Save "world" ACL
		$t_subject->setACLWorldAccess($this->request->getParameter("{$form_prefix}_access_world", pInteger));

		// Set ACL-related intrinsic fields
		if ($t_subject->hasField('acl_inherit_from_ca_collections') || $t_subject->hasField('acl_inherit_from_parent')) {
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
		
		ca_acl::updateACLInheritanceForRow($t_subject);

		$this->opo_app_plugin_manager->hookSaveItem(
			[
				'id' => $subject_id,
				'table_num' => $t_subject->tableNum(),
				'table_name' => $subject_table,
				'instance' => &$t_subject,
				'is_insert' => false,
				'request' => $this->request
			]
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
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	    	throw new ApplicationException(_t('CSRF check failed'));
	    	return;
	    }
		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }


		if ($this->request->user->canDoAction("can_change_type_".$t_subject->tableName())) {
			if (method_exists($t_subject, "changeType")) {
				$this->opo_app_plugin_manager->hookBeforeSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => &$t_subject, 'is_insert' => false));

				if (!$t_subject->changeType($vn_new_type_id = $this->request->getParameter('type_id', pInteger))) {
					// post error
					$this->notification->addNotification(_t('Could not set type to <em>%1</em>: %2', caGetListItemForDisplay($t_subject->getTypeListCode(), $vn_new_type_id), join("; ", $t_subject->getErrors())), __NOTIFICATION_TYPE_ERROR__);
				} else {
					$this->notification->addNotification(_t('Set type to <em>%1</em>', $t_subject->getTypeName()), __NOTIFICATION_TYPE_INFO__);
				}
				$this->opo_app_plugin_manager->hookSaveItem(
					[
						'id' => $vn_subject_id, 
						'table_num' => $t_subject->tableNum(), 
						'table_name' => $t_subject->tableName(), 
						'instance' => &$t_subject, 
						'is_insert' => false,
						'request' => $this->request
					]
				);

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

		$vn_above_id = $vn_after_id = null;
		
		$t_subject = Datamodel::getInstanceByTableName($this->ops_table_name);
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

        if ($t_subject->getAppConfig()->get($t_subject->tableName().'_dont_use_labels')) {
            MetaTagManager::setWindowTitle(_t("Editing %1 : %2", ($vs_type = $t_subject->getTypeName()) ? $vs_type : $t_subject->getProperty('NAME_SINGULAR'), ($vn_subject_id) ? $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD')) : _t('new %1', $t_subject->getTypeName())));
        } else {
		    MetaTagManager::setWindowTitle(_t("Editing %1 : %2", ($vs_type = $t_subject->getTypeName()) ? $vs_type : $t_subject->getProperty('NAME_SINGULAR'), ($vn_subject_id) ? $t_subject->getLabelForDisplay(true) : _t('new %1', $t_subject->getTypeName())));
        }
        
		// pass relationship parameters to Save() action from Edit() so
		// that we can create a relationship for a newly created object
		$vs_rel_table = $vn_rel_type_id = $vn_rel_id = null;
		if($vs_rel_table = $this->getRequest()->getParameter('rel_table', pString)) {
			$vn_rel_type_id = $this->getRequest()->getParameter('rel_type_id', pString);
			$vn_rel_id = $this->getRequest()->getParameter('rel_id', pInteger);

			if($vs_rel_table && $vn_rel_type_id && $vn_rel_id) {
				$this->view->setVar('rel_table', $vs_rel_table);
				$this->view->setVar('rel_type_id', $vn_rel_type_id);
				$this->view->setVar('rel_id', $vn_rel_id);
			}
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

				if ($vn_above_id > 0) { 
				    Session::setVar('default_hierarchy_add_mode', 'above');
				} elseif($vn_after_id > 0) {
				    Session::setVar('default_hierarchy_add_mode', 'next_to');
				} else {
				    Session::setVar('default_hierarchy_add_mode', 'under');
				}

				$t_parent = Datamodel::getInstanceByTableName($this->ops_table_name);
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
			return array($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id, $vn_after_id, $vs_rel_table, $vn_rel_type_id, $vn_rel_id);
		}

		return array($vn_subject_id, $t_subject, $t_ui, null, null, null, $vs_rel_table, $vn_rel_type_id, $vn_rel_id);
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
		if (!$this->request->isLoggedIn()) { return []; }

		if (!($vn_type_id = $t_subject->getTypeID()) && !($vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pInteger))) {
		    $vn_type_id = $t_subject->getDefaultTypeID();
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
		$t_subject = Datamodel::getInstanceByTableName($this->ops_table_name, true);

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

		$va_types = [];
		if (is_array($va_hier)) {

			$va_types_by_parent_id = [];
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
			
			$limit_to_types = $this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_limit_types_to');
			$exclude_types = $this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_exclude_types');
			
			$show_top_level_types_only = (bool)$this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_shows_top_level_types_only');
			$enforce_strict_type_hierarchy = $this->getRequest()->config->get($this->ops_table_name.'_enforce_strict_type_hierarchy');
			
			foreach($va_hier as $vn_item_id => $va_item) {
			    if(is_array($limit_to_types) && sizeof($limit_to_types) && !in_array($va_item['idno'], $limit_to_types)) { continue; }
				if(is_array($exclude_types) && sizeof($exclude_types) && in_array($va_item['idno'], $exclude_types)) { continue; }
				
			    
				if (is_array($va_restrict_to_types) && !in_array($vn_item_id, $va_restrict_to_types)) { continue; }
				if ($va_item['parent_id'] != $vn_root_id) { continue; }
				
				// does this item have sub-items?
				$va_subtypes = [];
				
				if (
					(!$show_top_level_types_only && !(bool)$enforce_strict_type_hierarchy)
					||
					(!$show_top_level_types_only && (bool)$enforce_strict_type_hierarchy && !(bool)$va_item['is_enabled']) 	
						// If in strict mode and a top-level type is disabled, then show sub-types so user can select an enabled type
				) {
					if (isset($va_item['item_id']) && isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
						$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id, $vn_sort_type, $va_restrict_to_types, ['level' => 1, 'firstEnabled' => !$show_top_level_types_only && (bool)$enforce_strict_type_hierarchy && !(bool)$va_item['is_enabled']]);
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
				
				if($this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_use_indented_type_lists')) {
					$no_new_submenu = $this->getRequest()->config->get($this->ops_table_name.'_no_new_submenu'); 
					$va_types[$vs_key][] = array(
						'displayName' => $va_item['name_singular'],
						'parameters' => array(
							'type_id' => $va_item['item_id']
						),
						'is_enabled' => $va_item['is_enabled'],
						'navigation' => $no_new_submenu ? $va_subtypes : []
					);
					if(!$no_new_submenu) {
						foreach($va_subtypes as $sitem) {
							$va_types[$vs_key][] = $sitem;
						}
					}
				} else {
					$va_types[$vs_key][] = array(
						'displayName' => $va_item['name_singular'],
						'parameters' => array(
							'type_id' => $va_item['item_id']
						),
						'is_enabled' => $va_item['is_enabled'],
						'navigation' => $va_subtypes
					);
				}
			}
			ksort($va_types);
		}
			
		$va_types_proc = [];
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
	 * @param array $pa_restrict_to_types List of types to restrict returned type list to. [Default is null]
	 * @param array $options Supported options include:
	 *		 firstEnabled = Stop returning subtypes once an enabled item is found. [Default is false]
	 * @return array List of subtypes ready for inclusion in a menu spec
	 */
	private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id, $pn_sort_type, $pa_restrict_to_types=null, $options=null) {
		$va_subtypes = [];
		$first_enabled = caGetOption('firstEnabled', $options, false);
		$level = caGetOption('level', $options, 0);
		
		$use_indented_lists = $this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_use_indented_type_lists');
		
		foreach($pa_subtypes as $vn_i => $va_type) {
			if (is_array($pa_restrict_to_types) && !in_array($va_type['item_id'], $pa_restrict_to_types)) { continue; }
			
			if ($first_enabled && $va_type['is_enabled']) {
				$va_subsubtypes = [];	// in "first enabled" mode we don't pull subtypes when we encounter an enabled item
			} elseif (isset($pa_types_by_parent_id[$va_type['item_id']]) && is_array($pa_types_by_parent_id[$va_type['item_id']])) {
				$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id, $pn_sort_type, $pa_restrict_to_types, array_merge($options, ['level' => $level + 1]));
			} else {
				$va_subsubtypes = [];
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

			if($use_indented_lists) {
				$offset = $level * 16;
				$va_subtypes[$vs_key][$va_type['item_id']] = array(
					'displayName' => "<span style='margin-left:{$offset}px'>".$va_type['name_singular']."</span>",
					'parameters' => array(
						'type_id' => $va_type['item_id']
					),
					'is_enabled' => $va_type['is_enabled'],
					'navigation' => []
				);
				foreach($va_subsubtypes as $item_id => $item) {
					$va_subtypes[$vs_key][$item_id] = $item;
				}
			} else {
				$va_subtypes[$vs_key][$va_type['item_id']] = array(
					'displayName' => $va_type['name_singular'],
					'parameters' => array(
						'type_id' => $va_type['item_id']
					),
					'is_enabled' => $va_type['is_enabled'],
					'navigation' => $va_subsubtypes
				);
			}
		}

		ksort($va_subtypes);
		$va_subtypes_proc = [];
		        
		$limit_to_types = $this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_limit_types_to');
		$exclude_types = $this->getRequest()->config->get($this->ops_table_name.'_navigation_new_menu_exclude_types');
		
        foreach($va_subtypes as $vs_sort_key => $va_type) {
			foreach($va_type as $vn_item_id => $va_item) {
				if (is_array($pa_restrict_to_types) && !in_array($vn_item_id, $pa_restrict_to_types)) { continue; }
				if (is_array($limit_to_types) && sizeof($limit_to_types) && !in_array($va_item['parameters']['type_id'], $limit_to_types)) { continue; }
				if (is_array($exclude_types) && sizeof($exclude_types) && in_array($va_item['parameters']['type_id'], $limit_to_types)) { continue; }
			    
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
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	    	throw new ApplicationException(_t('CSRF check failed'));
	    	return;
	    }
		list($vn_subject_id, $t_subject) = $this->_initView();

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

		$pn_mapping_id = $this->request->getParameter('mapping_id', pInteger);

		$this->render('../generic/export_xml.php');
	}
	# ------------------------------------------------------------------
	# Watch list actions
	# ------------------------------------------------------------------
	/**
	 * Add item to user's watch list. Intended to be called via ajax, and JSON response is returned in the current view inherited from ActionController
	 */
	public function toggleWatch() {
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	    	throw new ApplicationException(_t('CSRF check failed'));
	    	return;
	    }
		list($vn_subject_id, $t_subject) = $this->_initView();
		require_once(__CA_MODELS_DIR__.'/ca_watch_list.php');

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }


		$va_errors = [];
		$t_watch_list = new ca_watch_list();
		$vn_user_id =  $this->request->user->get("user_id");

		if ($t_watch_list->isItemWatched($vn_subject_id, $t_subject->tableNum(), $vn_user_id)) {
			if($t_watch_list->load(array('row_id' => $vn_subject_id, 'user_id' => $vn_user_id, 'table_num' => $t_subject->tableNum()))){
				$t_watch_list->delete();
				if ($t_watch_list->numErrors()) {
					$va_errors = $t_item->errors;
					$this->view->setVar('state', 'watched');
				} else {
					$this->view->setVar('state', 'unwatched');
				}
			}
		} else {
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

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }


		$vs_hierarchy_display = $t_subject->getHierarchyNavigationHTMLFormBundle($this->request, 'caHierarchyOverviewPanelBrowser', [], array('open_hierarchy' => true, 'no_close_button' => true, 'hierarchy_browse_tab_class' => 'foo'));
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

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

		$ps_bundle = $this->request->getParameter("bundle", pString);
		$pn_placement_id = $this->request->getParameter("placement_id", pInteger);
		
		$ps_sort = $this->request->getParameter("sort", pString);
		$ps_sort_direction = $this->request->getParameter("sortDirection", pString);

		$form_name = $this->request->getParameter("formName", pString);

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
				
				if (!is_array($bundle_sort_defaults = $this->request->user->getVar('bundleSortDefaults'))) { 
					$bundle_sort_defaults = [];
				}
				$bundle_sort_defaults["P{$pn_placement_id}"] = ['sort' => $ps_sort, 'sortDirection' => $ps_sort_direction];
				$this->request->user->setVar('bundleSortDefaults', $bundle_sort_defaults);
				
				$bundle_label = null;
				$this->response->addContent($t_subject->getBundleFormHTML($ps_bundle, "P{$pn_placement_id}", array_merge($t_placement->get('settings'), ['placement_id' => $pn_placement_id]), ['formName' => $form_name, 'request' => $this->request, 'contentOnly' => true, 'sort' => $ps_sort, 'sortDirection' => $ps_sort_direction, 'userSetSort' => true], $bundle_label));
				break;
		}
	}
	# ------------------------------------------------------------------
	/**
	 * Return partial list of values for bundle. Used for incremental loading of relationship lists.
	 */
	public function loadBundleValues() {
		list($vn_subject_id, $t_subject) = $this->_initView();

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }
		
		$ps_bundle_name = $this->request->getParameter("bundle", pString);
		if ($this->request->user->getBundleAccessLevel($t_subject->tableName(), $ps_bundle_name) < __CA_BUNDLE_ACCESS_READONLY__) { return false; }

		$pn_placement_id = $this->request->getParameter("placement_id", pInteger);
		$pn_start = (int)$this->request->getParameter("start", pInteger);
		if (!($pn_limit = $this->request->getParameter("limit", pInteger))) { $pn_limit = null; }
		$sort = $this->request->getParameter("sort", pString);
		$sort_direction = $this->request->getParameter("sortDirection", pString);

		$t_placement = new ca_editor_ui_bundle_placements($pn_placement_id);
		
		$d = $t_subject->getBundleFormValues($ps_bundle_name, "{$pn_placement_id}", $t_placement->get('settings'), array('start' => $pn_start, 'limit' => $pn_limit, 'sort' => $sort, 'sortDirection' => $sort_direction, 'request' => $this->request, 'contentOnly' => true));

		$this->response->addContent(json_encode(['sort' => array_keys($d ?? []), 'data' => $d]));
	}
	# ------------------------------------------------------------------
	/**
	 * JSON service that returns a processed display template for the current record
	 * @return bool
	 */
	public function processTemplate() {
		list($vn_subject_id, $t_subject) = $this->_initView();

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

		// http://providence.dev/index.php/editor/objects/ObjectEditor/processTemplate/object_id/1/template/^ca_objects.idno
		$ps_template = $this->request->getParameter("template", pString);
		$this->view->setVar('processed_template', json_encode(caProcessTemplateForIDs($ps_template, $t_subject->tableNum(), array($vn_subject_id))));
		$this->render("../generic/ajax_process_template.php");

		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns formatted list of media in a media attribute or container attribute that includes at least one media attribute.
	 * Used by CKEditor media reference dialog.
	 */
	public function getMediaAttributeList() {
	    list($vn_subject_id, $t_subject) = $this->_initView();

		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }
		
		$ps_bundle_name = $this->request->getParameter("bundle", pString);
		if ($this->request->user->getBundleAccessLevel($t_subject->tableName(), $ps_bundle_name) < __CA_BUNDLE_ACCESS_READONLY__) { return false; }

        $va_bundle_name_bits = explode('.', $ps_bundle_name);
        $va_media_list = array_shift($t_subject->get($t_subject->tableName().".{$va_bundle_name_bits[0]}", ['returnAsArray' => true, 'returnWithStructure' => true]));
        if(!is_array($va_media_list)) { $va_media_list = []; }
        
        // add additional information about list
        $va_text_disp_fields = $va_media_fields = [];
        foreach($va_media_list as $vn_attribute_id => $va_attr) {
            $o_attr = $t_subject->getAttributeByID($vn_attribute_id);
            $va_vals = $o_attr->getValues();
            
            foreach($va_vals as $o_val) {
                $vs_element_code = $o_val->getElementCode();
                switch($o_val->getType()) {
                     case __CA_ATTRIBUTE_VALUE_MEDIA__:
                        $va_media_fields[$vs_element_code] = true;
                        $va_media_list[$vn_attribute_id][$vs_element_code] = [];
                        foreach($o_val->getVersions() as $vs_version) {
                            $va_media_list[$vn_attribute_id][$vs_element_code]['urls'][$vs_version] = $o_val->getDisplayValue(['return' => 'url', 'version' => $vs_version]);
                            $va_media_list[$vn_attribute_id][$vs_element_code]['tags'][$vs_version] = $o_val->getDisplayValue(['return' => 'tag', 'version' => $vs_version]);
                            $va_media_list[$vn_attribute_id][$vs_element_code]['value_id'] = $o_val->getValueID();
                        }
                        break;
                    case __CA_ATTRIBUTE_VALUE_TEXT__:
                    case __CA_ATTRIBUTE_VALUE_DATERANGE__:
                        $va_text_disp_fields[$vs_element_code] = true;
                        break;
                }
            }
        }

        $this->view->setVar('media_list', $va_media_list);
        $this->view->setVar('media', array_keys($va_media_fields));
        $this->view->setVar('text', array_keys($va_text_disp_fields));

	    $this->render("../generic/ajax_media_attribute_list_html.php");
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
		$t_item 			= Datamodel::getInstanceByTableName($this->ops_table_name, true);
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
							'additionalTableWheres' => ($t_label->hasField('is_preferred')) ? array("({$vs_label_table}.is_preferred = 1 OR {$vs_label_table}.is_preferred IS NULL)") : [],
							'includeSelf' => false
						)
					), $vs_pk, $vs_display_field, 'idno'));

				$this->view->setVar('object_collection_collection_ancestors', []); // collections to display as object parents when ca_objects_x_collections_hierarchy_enabled is enabled
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
							'additionalTableWheres' => ($t_label->hasField('is_preferred')) ? array("({$vs_label_table}.is_preferred = 1 OR {$vs_label_table}.is_preferred IS NULL)") : [],
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
			is_array($va_restrict_to_types) && sizeof($va_restrict_to_types)
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
		if (caACLIsEnabled($pt_subject) && $pt_subject->getPrimaryKey()) {
			if (method_exists($pt_subject, 'checkACLAccessForUser') && $pt_subject->checkACLAccessForUser($this->request->user) < __CA_BUNDLE_ACCESS_READONLY__) {
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
			$t_subject = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true);
			$t_subject->load($t_attr->get('row_id'));
						
			if (!$t_subject->isReadable($this->request)) { 
				throw new ApplicationException(_t('Cannot view media'));
			}

			if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('value_blob', 'INPUT', 'MIMETYPE')))) {
				throw new ApplicationException(_t('Invalid viewer'));
			}

			$va_display_info = caGetMediaDisplayInfo('media_overlay', $vs_mimetype);
			if(($t_instance->numFiles() > 1) && (caGetMediaClass($vs_mimetype) === 'image') && ($multipage_viewer = caGetOption('viewer_for_multipage_images', $va_display_info, null))) {
				$va_display_info['viewer'] = $vs_viewer_name = $multipage_viewer;
				unset($va_display_info['use_mirador_for_image_list_length_at_least']);
				unset($va_display_info['use_universal_viewer_for_image_list_length_at_least']);
			}

			$this->response->addContent($vs_viewer_name::getViewerHTML(
				$this->request, 
				"attribute:{$pn_value_id}", 
				['context' => 'media_overlay', 't_instance' => $t_instance, 't_subject' => $t_subject, 'display' => $va_display_info])
			);
		} elseif ($pn_representation_id = $this->request->getParameter('representation_id', pInteger)) {				
			if(!$t_subject) { 
				throw new ApplicationException(_t('Invalid id'));
			}	
			if (!$t_subject->isReadable($this->request)) { 
				throw new ApplicationException(_t('Cannot view media'));
			}
			
			$t_media = ($t_subject->tableName() == 'ca_set_items') ? $t_subject->getItemInstance() : $t_subject;
			
			//
			// View object representation
			//
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
			$t_instance = new ca_object_representations($pn_representation_id);
			
			if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'INPUT', 'MIMETYPE')))) {
				throw new ApplicationException(_t('Invalid viewer for '.$vs_mimetype));
			}
			
			$va_display_info = caGetMediaDisplayInfo('media_overlay', $vs_mimetype);
			
			if ((($vn_use_universal_viewer_for_image_list_length = caGetOption('use_universal_viewer_for_image_list_length_at_least', $va_display_info, null))
				||
				($vn_use_mirador_for_image_list_length = caGetOption('use_mirador_for_image_list_length_at_least', $va_display_info, null)))
			) {
				$vn_image_count = $t_media->numberOfRepresentationsOfClass('image');
				$vn_rep_count = $t_media->getRepresentationCount();
				
				// Are there enough representations? Are all representations images? 
				if ($vn_image_count == $vn_rep_count) {
					if (!is_null($vn_use_universal_viewer_for_image_list_length) && ($vn_image_count >= $vn_use_universal_viewer_for_image_list_length)) {
						$va_display_info['viewer'] = $vs_viewer_name = 'UniversalViewer';
					} elseif(!is_null($vn_use_mirador_for_image_list_length) && ($vn_image_count >= $vn_use_mirador_for_image_list_length)) {
						$va_display_info['viewer'] = $vs_viewer_name = 'Mirador';
					}
				}
			}
			
			if(($t_instance->numFiles() > 1) && (caGetMediaClass($vs_mimetype) === 'image') && ($multipage_viewer = caGetOption('viewer_for_multipage_images', $va_display_info, null))) {
				$va_display_info['viewer'] = $vs_viewer_name = $multipage_viewer;
				unset($va_display_info['use_mirador_for_image_list_length_at_least']);
				unset($va_display_info['use_universal_viewer_for_image_list_length_at_least']);
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
				['context' => 'media_overlay', 't_instance' => $t_instance, 't_subject' => $t_subject, 't_media' => $t_media, 'display' => $va_display_info])
			);
		} elseif ($pn_media_id = $this->request->getParameter('media_id', pInteger)) {			
			if(!$t_subject) { 
				throw new ApplicationException(_t('Invalid id'));
			}
		    //
			// View site page media
			//
			require_once(__CA_MODELS_DIR__."/ca_site_page_media.php");
			$t_instance = new ca_site_page_media($pn_media_id);
			
			if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'INPUT', 'MIMETYPE')))) {
				throw new ApplicationException(_t('Invalid viewer'));
			}
			
			$va_display_info = caGetMediaDisplayInfo('media_overlay', $vs_mimetype);
			
			if(!$vn_subject_id) {
				if (is_array($va_subject_ids = $t_instance->get($t_subject->tableName().'.'.$t_subject->primaryKey(), array('returnAsArray' => true))) && sizeof($va_subject_ids)) {
					$vn_subject_id = array_shift($va_subject_ids);
				} else {
					$this->postError(1100, _t('Invalid object/media'), 'ObjectEditorController->GetRepresentationInfo');
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
				
				if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'INPUT', 'MIMETYPE')))) {
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
				
				if(($t_instance->numFiles() > 1) && (caGetMediaClass($vs_mimetype) === 'image') && ($multipage_viewer = caGetOption('viewer_for_multipage_images', $va_display_info, null))) {
					$va_display_info['viewer'] = $vs_viewer_name = $multipage_viewer;
					unset($va_display_info['use_mirador_for_image_list_length_at_least']);
					unset($va_display_info['use_universal_viewer_for_image_list_length_at_least']);
				}
				
				$this->response->addContent($vs_viewer_name::getViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => $va_display_info]));
				return;
				break;
			case 'attribute':
				$t_instance = new ca_attribute_values($va_identifier['id']);
				$t_instance->useBlobAsMediaField(true);
				$t_attr = new ca_attributes($t_instance->get('attribute_id'));
				$t_subject = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true);
				$t_subject->load($t_attr->get('row_id'));
				
				if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('value_blob', 'INPUT', 'MIMETYPE')))) {
					throw new ApplicationException(_t('Invalid viewer'));
				}
				
				$va_display_info = caGetMediaDisplayInfo('media_overlay', $vs_mimetype);
				if(($t_instance->numFiles() > 1) && (caGetMediaClass($vs_mimetype) === 'image') && ($multipage_viewer = caGetOption('viewer_for_multipage_images', $va_display_info, null))) {
					$va_display_info['viewer'] = $vs_viewer_name = $multipage_viewer;
					unset($va_display_info['use_mirador_for_image_list_length_at_least']);
					unset($va_display_info['use_universal_viewer_for_image_list_length_at_least']);
				}
				
				$t_instance = new ca_attribute_values($va_identifier['id']);
				$t_instance->useBlobAsMediaField(true);
				$t_attr = new ca_attributes($t_instance->get('attribute_id'));
				$t_subject = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true);
				$t_subject->load($t_attr->get('row_id'));
				
				$this->response->addContent($vs_viewer_name::getViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => $va_display_info]));
				return;
				break;
		}
		
		throw new ApplicationException(_t('Invalid type'));
	}
	# -------------------------------------------------------
	/**
	 * Access to sidecar data (primarily used by 3d viewer)
	 * Will only return sidecars that are images (for 3d textures), MTL files (for 3d OBJ-format files) or 
	 * binary (for GLTF .bin buffer data)
	 */
	public function GetMediaSidecarData() {
		caReturnMediaSidecarData($this->request->getParameter('sidecar_id', pInteger), $this->request->user);
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
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'INPUT', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::searchViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => null]));
                return;
                break;
			case 'attribute':
                $t_instance = new ca_object_representations($va_identifier['id']);
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'INPUT', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::searchViewerData($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => null]));
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
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'INPUT', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::autocomplete($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => null]));
                return;
                break;
			case 'attribute':
                $t_instance = new ca_object_representations($va_identifier['id']);
                if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_instance->getMediaInfo('media', 'INPUT', 'MIMETYPE')))) {
                    throw new ApplicationException(_t('Invalid viewer'));
                }
                $this->response->addContent($vs_viewer_name::autocomplete($this->request, $ps_identifier, ['request' => $this->request, 't_subject' => $t_subject, 't_instance' => $t_instance, 'display' => null]));
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
		$va_annotations = [];

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
					'points' => 		caGetOption('points', $va_annotation, [], array('castTo' => 'array')),
					'label' => 			caGetOption('label', $va_annotation, '', array('castTo' => 'string')),
					'description' => 	caGetOption('description', $va_annotation, '', array('castTo' => 'string')),
					'type' => 			caGetOption('type', $va_annotation, 'rect', array('castTo' => 'string')),
					'locked' => 		caGetOption('locked', $va_annotation, '0', array('castTo' => 'string')),
					'options' => 		caGetOption('options', $va_annotation, [], array('castTo' => 'array')),
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
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	    	throw new ApplicationException(_t('CSRF check failed'));
	    	return;
	    }
		global $g_ui_locale_id;
		$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
		$t_rep = new ca_object_representations($pn_representation_id);

		$pa_annotations = $this->request->getParameter('save', pArray);

		$va_annotation_ids = [];
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
	# File download
	# -------------------------------------------------------
	/**
	 * Download all media attached to specified object (not necessarily open for editing)
	 * Includes all representation media attached to the specified object + any media attached to other
	 * objects in the same object hierarchy as the specified object. 
	 */
	public function DownloadMedia($pa_options=null) {
		list($vn_subject_id, $t_subject) = $this->_initView();
		if (!($pn_representation_id = $this->request->getParameter('representation_id', pInteger))) { 
		    $pn_representation_id = $this->request->getParameter('media_id', pInteger);
		}
		$pn_value_id = $this->request->getParameter('value_id', pInteger);
		if ($pn_value_id) {
			return $this->DownloadAttributeFile();
		}
		$ps_version = $this->request->getParameter('version', pString);
		if (!$vn_subject_id) { return; }

		$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');

		$di = [];
		if($pn_representation_id) {
			if (!($t_rep = ca_object_representations::findAsInstance(['representation_id' => $pn_representation_id]))) {
				throw new ApplicationException(_t('Invalid representation'));
			}
			if(!$t_rep->isReadable($this->request->user)) {
				throw new ApplicationException(_t('Access denied'));
			}
			
			$m = $t_rep->getMediaInfo('media', 'original', 'MIMETYPE'); 
			$di = caGetMediaDisplayInfo('media_overlay', $m);	
		}	
	
		if (!$ps_version) { $ps_version = caGetOption('download_version', $di, 'original'); }

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
		$va_file_names = [];
		$va_file_paths = [];
		$va_child_ids = array_unique($va_child_ids);
		
		$t_download_log = new Downloadlog();
		foreach($va_child_ids as $vn_child_id) {
			if (!$t_subject->load($vn_child_id)) { continue; }
			
			switch($t_subject->tableName()) {
			    case 'ca_object_representations':
                    $va_reps = [
                        $vn_child_id => [
                            'representation_id' => $vn_child_id,
                            'info' => [$ps_version => $t_subject->getMediaInfo('media', $ps_version)],
                            'paths' => [$ps_version => $t_subject->getMediaPath('media', $ps_version)]
                        ]
                    ];
                    break;
				case 'ca_site_pages':
				    $va_reps = $t_subject->getPageMedia([$ps_version]);
				    break;
				default:
				    if(!is_a($t_subject, 'RepresentableBaseModel')) { throw new ApplicationException(_t('No media to download for this type of record')); }
				    $va_reps = $t_subject->getRepresentations([$ps_version]);
				    break;
			}
			$vs_idno = $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD'));
	
			$vb_download_for_record = false;
			foreach($va_reps as $vn_representation_id => $va_rep) {
				if ($pn_representation_id && ($pn_representation_id != $vn_representation_id)) { continue; }
				$vb_download_for_record = true;
				$va_rep_info = $va_rep['info'][$ps_version];
				
				$vs_filename = caGetRepresentationDownloadFileName($t_subject->tableName(), ['idno' => $vs_idno, 'index' => $vn_c, 'version' => $ps_version, 'extension' => $va_rep_info['EXTENSION'], 'original_filename' => $va_rep['info']['original_filename'], 'representation_id' => $vn_representation_id]);				
				$va_file_names[$vs_filename] = true;
				$o_view->setVar('version_download_name', $vs_filename);
				
				//
				// Perform metadata embedding
				if (isset($va_rep['representation_id']) && ($va_rep['representation_id'] > 0)) {
                    $t_rep = new ca_object_representations($va_rep['representation_id']);
                    if(!$t_rep->isReadable($this->request->user)) { continue; }
                    
                    if(!($vs_path = caEmbedMediaMetadataIntoFile($t_rep->getMediaPath('media', $ps_version),
                        $t_subject->tableName(), $t_subject->getPrimaryKey(), $t_subject->getTypeCode(), // subject table info
                        $t_rep->getPrimaryKey(), $t_rep->getTypeCode() // rep info
                    ))) {
                        $vs_path = $va_rep['paths'][$ps_version];
                    }
                } else {
                    $vs_path = $va_rep['paths'][$ps_version];
                }

				$va_file_paths[$vs_path] = $vs_filename;

				$vn_c++;
			}
		
			if($vb_download_for_record){
				$t_download_log->log(array(
						"user_id" => $this->request->getUserID(), 
						"ip_addr" => RequestHTTP::ip(), 
						"table_num" => Datamodel::getTableNum($this->ops_table_name), 
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
			$names_set = [];
			foreach($va_file_paths as $vs_path => $vs_name) {
				if (isset($names_set[$vs_name])) {
					$names_set[$vs_name]++;
					$ext = pathinfo($vs_name, PATHINFO_EXTENSION);
					$vs_name = pathinfo($vs_name, PATHINFO_FILENAME).'-'.$names_set[$vs_name].'.'.$ext;
				} else {
					$names_set[$vs_name] = 1;
				}
				$o_zip->addFile($vs_path, $vs_name);
				
			}
			$o_view->setVar('zip_stream', $o_zip);
			$o_view->setVar('archive_name', caGetMediaDownloadArchiveName($t_subject->tableName(), $vn_subject_id, ['extension' => 'zip']));
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

		$vn_table_num = Datamodel::getTableNum($this->ops_table_name);
		if ($t_attr->get('table_num') !=  $vn_table_num) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2580?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		$t_element = new ca_metadata_elements($t_attr->get('element_id'));
		$this->request->setParameter(Datamodel::primaryKey($vn_table_num), $t_attr->get('row_id'));

		list($vn_subject_id, $t_subject) = $this->_initView($pa_options);
		$ps_version = $this->request->getParameter('version', pString);


		if (!$this->_checkAccess($t_subject)) { throw new ApplicationException(_t('Access denied')); }

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
					$vs_filename = pathinfo($vs_name, PATHINFO_FILENAME);
					$vs_name = "{$vs_filename}.{$vs_path_ext}";
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
				"ip_addr" => RequestHTTP::ip(), 
				"table_num" => Datamodel::getTableNum($this->ops_table_name), 
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
	public function UploadFiles($options=null) {
		if (!$this->request->isLoggedIn() || ((int)$this->request->user->get('userclass') !== 0)) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}

		if (!($user_id = $this->request->getUserID())) { return null; }
		caCleanUserMediaDirectory($user_id);

		$stored_files = [];
		
		$user_dir = caGetMediaUploadPathForUser($user_id);

		if(is_array($_FILES['files'])) {
			// used by ca_object_representations bundle file uploader and media importer drag-and-drop file uploads
			foreach($_FILES['files']['tmp_name'] as $i => $f) {
				if(!strlen($f)) { continue; }
				
				$dest_filename = preg_replace("![^A-Za-z0-9_\-\.]+!", "_", isset($_FILES['files']['name'][$i]) ? $_FILES['files']['name'][$i] : pathinfo($f, PATHINFO_FILENAME));
				if(!@copy($f, $dest_path = "{$user_dir}/{$dest_filename}")) { continue; }

				$stored_files[$dest_filename] = caGetUserDirectoryName($this->request->getUserID())."/{$dest_filename}"; // only return the user directory and file name, not the entire path
			}
			$this->response->addContent(json_encode(['files' => array_values($stored_files), 'msg' => _t('Uploaded %1 files', sizeof($stored_files))]));
		} else {
			// assume single file in each key (used by Quickadd file and media attribute upload process)
			foreach($_FILES as $k => $info) {
				if(!is_array($info) || !array_key_exists('tmp_name', $info) || !strlen($info['tmp_name'])) { continue; }
				
				$dest_filename = isset($info['name']) ? $info['name'] : pathinfo($info['tmp_name'], PATHINFO_FILENAME);
				if(!@copy($info['tmp_name'], $dest_path = "{$user_dir}/{$dest_filename}")) { continue; }

				$stored_files[$k] = caGetUserDirectoryName($this->request->getUserID())."/{$dest_filename}"; // only return the user directory and file name, not the entire path
			}
			
			$this->response->addContent(json_encode(['files' => $stored_files, 'msg' => _t('Uploaded %1 files', sizeof($stored_files))]));
		}

	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function MediaBrowser($options=null) {
		$this->view->setVar('lastPath', Session::getVar('lastMediaImportDirectoryPath'));
		$this->render('../generic/representation_media_browser_html.php');
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function SetHomeLocation($options=null) {
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
	    	throw new ApplicationException(_t('CSRF check failed'));
	    	return;
	    }
		list($vn_subject_id, $t_subject) = $this->_initView();
		if (!$t_subject->isLoaded()) { 
			throw new ApplicationException(_t('Invalid id %1', $vn_subject_id));
		}
		if (!$this->_checkAccess($t_subject)) { 
			throw new ApplicationException(_t('Access denied'));
		}
		$table = $t_subject->tableName();
		$location_id = $this->request->getParameter('location_id', pInteger);
		if (!($t_location = ca_storage_locations::find($location_id, ['returnAs' => 'firstModelInstance']))) { 
			$resp = ['ok' => 0, 'errors' => _t('No location set')];
		} else {
			if (!caHomeLocationsEnabled($table, $t_subject->getTypeCode())) { 
				throw new ApplicationException(_t('Home locations are not enabled'));
			}
			if (!$this->request->user->canDoAction("can_set_home_location_{$table}")) {
				throw new ApplicationException(_t('Access denied'));
			}
			$t_subject->set('home_location_id', $location_id);
			$t_subject->update();
		
			if ($t_subject->numErrors() > 0) {
				$resp = ['ok' => 0, 'errors' => $t_subject->getErrors()];
			} else {
				$resp = ['ok' => 1, 'label' => $t_location->getWithTemplate($this->request->config->get('ca_storage_locations_hierarchy_browser_display_settings')), 'timestamp' => time()];
			}
		}
		
		$this->view->setVar('response', $resp);
		$this->render('../generic/set_home_location_json.php');
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function BatchEdit($options=null) {
		if (!($placement_id = $this->getRequest()->getParameter('placement_id', pInteger))) {
			throw new ApplicationException(_t('Invalid placement_id'));
		}
		$placement = new ca_editor_ui_bundle_placements($placement_id);
		if (!$placement->isLoaded()) {
			throw new ApplicationException(_('Invalid placement_id'));
		}
		$editor_table = $placement->getEditorType();
		$t_instance = Datamodel::getInstance($editor_table, true);
		$vn_primary_id = $this->getRequest()->getParameter('primary_id', pInteger);
		if (!($t_instance->load($vn_primary_id))) { 
			throw new ApplicationException(_('Invalid id'));
		}
		
		$bundle_name = $placement->get('bundle_name');
		
		switch($bundle_name) {
			case 'history_tracking_current_contents':
				if(!($policy = $placement->getSetting('policy'))) {
					throw new ApplicationException(_('No policy set'));
				}
				if(!is_array($policy_config = $editor_table::getPolicyConfig($policy))) {
					throw new ApplicationException(_('Could not get policy configuration for policy %1', $policy));
				}
				if(!($table = $policy_config['table']) || !Datamodel::tableExists($table)) {
					throw new ApplicationException(_('Invalid table %1 in policy %2', $table, $policy));
				}
				$ids = $t_instance->getContents($policy, array_merge($placement->getSettings(), ['idsOnly' => true]));
				break;
			case 'ca_objects_components_list':
				$id = $this->request->getParameter('primary_id', pInteger);
				$t_object = ca_objects::findAsInstance($id);
				if(!$t_object || !$t_object->isSaveable($this->request) || !$t_object->canTakeComponents()) {
					throw new ApplicationException(_('Invalid item'));
				}
				$ids = $t_object->getComponents(['returnAs' => 'ids']);
				$table = "ca_objects";
				break;
			default:
				// relationship bundles
				$table = preg_replace("!(_related_list|_table)$!", "", $bundle_name);
				if($ids = $this->request->getParameter('ids', pString)) {
					$ids = explode(";", $ids);
				} else {
					$ids = $t_instance->getRelatedItems($table, ['showCurrentOnly' => $placement->getSetting('showCurrentOnly'), 'policy' => $placement->getSetting('policy'), 'returnAs' => 'ids', 'restrictToTypes' => $placement->getSetting('restrict_to_types'), 'restrictToRelationshipTypes' => $placement->getSetting('restrict_to_relationship_types'), ]);
				}
				break;
		}
	
		if(!$ids || !sizeof($ids)) { 
			throw new ApplicationException(_('No related items'));
		}
		$rc = new ResultContext($this->request, $table, 'BatchEdit');
		$rc->setResultList($ids);
		$rc->setParameter('primary_table', $this->ops_table_name);
		$rc->setParameter('primary_id', $this->getRequest()->getParameter('primary_id', pInteger));
		$rc->setParameter('screen', $this->getRequest()->getParameter('screen', pString));
		$rc->saveContext();
		$rc->invalidateCache();
		$this->getResponse()->setRedirect(caNavUrl($this->request, 'batch', 'Editor', 'Edit', ['id' => 'BatchEdit:'.$table]));
		return;
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function ReturnToHomeLocations($options=null) {
		$target = $this->request->getParameter('target', pString);
		if(in_array($target, ['ca_objects', 'ca_collections', 'ca_object_lots', 'ca_object_representations'], true)) {
			if(!$this->request->user->canDoAction("can_edit_{$target}")) {
				$resp = ['ok' => 0, 'message' => _t('Access denied'), 'updated' => [], 'errors' => [], 'timestamp' => time()];	
			} elseif(!is_array($policies = $target::getHistoryTrackingCurrentValuePolicies($target))) {
				$resp = ['ok' => 0, 'message' => _t('No policies available'), 'updated' => [], 'errors' => [], 'timestamp' => time()];	
			} else {
				$policies = array_filter($policies, function($v) { return array_key_exists('ca_storage_locations', $v['elements']); });
		
				$updated = $already_home = $errors = [];
				$msg = '';
				if(is_array($policies) && sizeof($policies)) {
					$primary_table = $this->request->getParameter('table', pString);
					$primary_id = $this->request->getParameter('id', pString);
		
					if (!($primary = Datamodel::getInstance($primary_table))) {
						throw new ApplicationException(_t('Invalid table'));
					}
					if (!$primary->load($primary_id)) {
						throw new ApplicationException(_t('Invalid id'));
					}
					if (!$primary->isReadable($this->request)) {
						throw new ApplicationException(_t('Access denied'));
					}
					if (!($t_pk = Datamodel::primaryKey($target))) { 
						throw new ApplicationException(_t('Invalid target'));
					}
					
 					$is_fk = false;
 					$target_id = null;
 					if ($primary->hasField($t_pk) && ($target_id = $primary->get($t_pk))) {
						$is_fk = true;
					}
					foreach($policies as $n => $p) {
						if(!isset($p['elements']) || !isset($p['elements']['ca_storage_locations'])) { continue; }
						
						
						if($is_fk) {
							$qr_res = caMakeSearchResult($target, [$target_id]);
						} else {
							if(!($qr_res = $primary->getContents($n))) { continue; }
						}
						while($qr_res->nextHit()) {
							$t_id = $qr_res->getPrimaryKey();
							
							$t_instance = $qr_res->getInstance();
							if (!($location_id = $t_instance->get('home_location_id'))) { continue; }
							if (!$t_instance->isSaveable($this->request)) { continue; }
							
							$pe = $p['elements']['ca_storage_locations'];
							$d = isset($pe[$t_instance->getTypeCode()]) ? $pe[$t_instance->getTypeCode()] : $pe['__default__'];
							if (!$is_fk && (!is_array($d) || !isset($d['trackingRelationshipType']))) { $errors[] = $t_id; continue; }
				
							// Interstitials?
							$effective_date = null;
							$interstitial_values = [];
							if (is_array($interstitial_elements = caGetOption("setInterstitialElementsOnAdd", $d, null))) {
								foreach($interstitial_elements as $e) {
									switch($e) {
										case 'effective_date':
											$effective_date = _t('today');
											break;
									}
								}
							}
							if (is_array($cv = $t_instance->getCurrentValue($n)) && (($cv['type'] == 'ca_storage_locations') && ($cv['id'] == $location_id))) {
								$already_home[] = $t_id;
								continue;
							}
				
							if (!($t_item_rel = $t_instance->addRelationship('ca_storage_locations', $location_id, $d['trackingRelationshipType'], $effective_date, null, null, null, ['allowDuplicates' => true]))) {
								$errors[] = $t_id;
								continue;
							}
							$target::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($this->request, null, null, $t_item_rel, null, $interstitial_elements, ['noTemplate' => true]);

							if($t_instance->numErrors() > 0) {
								$errors[] = $t_id;
							} else {
								$updated[] = $t_id;
							}
						}
					}
					
					$n = sizeof($updated);
					$h = sizeof($already_home);
			
					if($h > 0) {
						$msg =  _t('%1 %2 returned to home location; %3 already home', $n, Datamodel::getTableProperty($target, ($n == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL'), $h);
					} else {
						$msg = _t('%1 %2 returned to home location', $n, Datamodel::getTableProperty($target, ($n == 1)  ? 'NAME_SINGULAR' : 'NAME_PLURAL'));
					}
					if (sizeof($errors)) {
						$msg .= '; '._t('%1 errors', sizeof($errors));
					}
					
					$resp = ['ok' => 1, 'message' => $msg, 'updated' => array_unique($updated), 'errors' => array_unique($errors), 'timestamp' => time()];
				} else {
					$resp = ['ok' => 0, 'message' => _t('No policies available'), 'updated' => [], 'errors' => [], 'timestamp' => time()];	
				}
			}
		} else {
			$resp = ['ok' => 0, 'message' => _t('Invalid target'), 'updated' => [], 'errors' => [], 'timestamp' => time()];	
		}
		$this->view->setVar('response', $resp);
		$this->render('../generic/return_to_home_locations.php');
	}
	# -------------------------------------------------------
	/**
	 * Set media from 
	 */
	public function setRepresentation(?array $options=null) {
		list($vn_subject_id, $t_subject) = $this->_initView($options);
		
		if(!$t_subject->isSaveable($this->request)) {
			throw new ApplicationException(_t('Access denied'));
		}
		
		$id = $this->request->getParameter('id', pString);	// id of item to set as root media
		if(!$id) {
			throw new ApplicationException(_t('ID is not defined'));
		}
		$table = $this->request->getParameter('t', pString);
		$path = Datamodel::getPath($t_subject->tableName(), $table);
		
		if(!is_array($path) || (sizeof($path) < 2)) {
			throw new ApplicationException(_t('Invalid target'));
		}
		$path = array_keys($path);
		if(!($t_rel = Datamodel::getInstance($path[1])) && method_exists($t_rel, 'isRelationship') && $t_rel->isRelationship()) {
			throw new ApplicationException(_t('Relationship does not exist'));
		}
		if(!$t_rel->load($id)) {
			throw new ApplicationException(_t('ID does not exist'));
		}
		
		if($t_rel->isSelfRelationship()) {
			$rel_ids = $t_rel->getRelatedIDsForSelfRelationship([$t_subject->getPrimaryKey()]);
			$t_target = Datamodel::getInstance($table, true, $rel_ids[0]);
		} else {
			$t_target = Datamodel::getInstance($table, true);
			$rel_id = $t_rel->get($t_target->primaryKey());
			$t_target->load($rel_id);
		}
		
		$rel_type = null;
		
		if($t_subject->tableName() !== 'ca_objects') {
			$placement_id = $this->request->getParameter('placement_id', pInteger);	
			$t_placement = new ca_editor_ui_bundle_placements($placement_id);
			$rel_type = $t_placement->getSetting('useRepresentationRelationshipType');
			if(is_array($rel_type)) { $rel_type = array_shift($rel_type); }
			
			if(!$rel_type) {
				if(is_array($rel_type_ids = $t_rel->getRelationshipTypes(null, null, ['idsOnly' => true]))) {
					$rel_type = array_shift($rel_type_ids);
				}
			}
		}

		$selected_rep_id = $t_target->getPrimaryRepresentationID();
		if(!$selected_rep_id) {
			throw new ApplicationException(_t('ID has no associated media'));
		}
		$existing_reps = $t_subject->getRepresentations() ?? [];
		
		if(sizeof($selected_reps = array_filter($existing_reps, function($v) use ($selected_rep_id) {
			return $v['representation_id'] == $selected_rep_id;
		}))) {
			$selected_rep = array_shift($selected_reps);
			if($t_subject->removeRelationship('ca_object_representations', $selected_rep['relation_id'])) {
				$resp = ['ok' => true, 'errors' => [], 'message' => _t('Removed media')];
			} else {
				$resp = ['ok' => false, 'errors' => $t_subject->getErrors(), 'message' => _t('Could not unlink media: %1', join('; ', $t_subject->getErrors()))];
			}
		} elseif($t_subject->addRelationship('ca_object_representations', $selected_rep_id, $rel_type)) {
			$resp = ['ok' => true, 'errors' => [], 'message' => _t('Updated media')];
		} else {
			$resp = ['ok' => false, 'errors' => $t_subject->getErrors(),'message' => _t('Could not update media: %1', join('; ', $t_subject->getErrors()))];
		}
		
		$this->view->setVar('response', $resp);
		$this->render('../generic/return_to_home_locations.php');
	}
	# -------------------------------------------------------
	/**
	 * Handle sort requests from form editor.
	 * Gets passed a table name, a list of ids and a key to sort on. Will return a JSON list of the same IDs, just sorted.
	 *
	 * TODO: remove this
	 */
	public function Sort() {
		if (!$this->getRequest()->isLoggedIn() || ((int)$this->getRequest()->user->get('userclass') !== 0)) {
			$this->getResponse()->setRedirect($this->getRequest()->config->get('error_display_url').'/n/2320?r='.urlencode($this->getRequest()->getFullUrlPath()));
			return;
		}

		if ($placement_id = $this->getRequest()->getParameter('placement_id', pInteger)) {
			$vn_primary_table = $this->getRequest()->getParameter('primary_table', pString);
			$vn_primary_id = $this->getRequest()->getParameter('primary_id', pInteger);
			
			// Generate list of related items from editor UI placement when defined	...
			//
			// TODO: convert the entire related list UI mess to use passed placement_id's rather than giant lists of related IDs
			// For now support both placement_ids and passed ID lists
			$placement = new ca_editor_ui_bundle_placements($placement_id);
			if (!$placement->isLoaded()) {
				throw new ApplicationException(_('Invalid placement_id'));
			}
			$t_instance = Datamodel::getInstance($placement->getEditorType(), true);
	
			if (!($t_instance->load($vn_primary_id))) { 
				throw new ApplicationException(_('Invalid id'));
			}
			$va_ids = $t_instance->getRelatedItems($placement->get('bundle_name'), ['returnAs' => 'ids']);
		} else {
			$vs_table_name = $this->getRequest()->getParameter('table', pString);
			$t_instance = Datamodel::getInstance($vs_table_name, true);
			$va_ids = explode(',', $this->getRequest()->getParameter('ids', pString));
		}
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
