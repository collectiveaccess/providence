<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseQuickAddController.php : 
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
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__."/ResultContext.php");
require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');
require_once(__CA_APP_DIR__.'/helpers/listHelpers.php');

class BaseQuickAddController extends ActionController {
	# -------------------------------------------------------
	protected $opo_app_plugin_manager;
	protected $opo_result_context;
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
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
		list($t_subject, $t_ui, $vn_parent_id, $vn_above_id) = $this->_initView($pa_options);
		$vs_field_name_prefix = $this->request->getParameter('fieldNamePrefix', pString);
		$vs_n = $this->request->getParameter('n', pString);
		
		$vn_subject_id = $t_subject->getPrimaryKey();
		
		// table name and row_id from calling record (what we're quick-adding on)
		// only set for ca_objects quick-adds
		$vs_source = $this->request->getParameter('source', pString);
		$vn_source_id = $this->request->getParameter('source_id', pInteger);
	
		// Is user allowed to quickadd?
		if (!(is_array($pa_options) && isset($pa_options['dontCheckQuickAddAction']) && (bool)$pa_options['dontCheckQuickAddAction']) && (!$this->request->user->canDoAction('can_quickadd_'.$t_subject->tableName()))) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2585?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		if ($vn_parent_id = $this->request->getParameter('parent_id', pInteger)) {
			$this->opo_result_context->setParameter($t_subject->tableName().'_last_parent_id', $vn_parent_id);
		}

		if ($hier_id = $this->request->getParameter('hier_id', pInteger)) {
			$this->view->setVar('hier_id', $hier_id);
		}
		
		// Get user query
		$this->view->setVar('q', preg_replace("!^[^:]*:!", "", $this->request->getParameter('q', pString)));
		
		// table name and row_id of caller
		$this->view->setVar('source', $vs_source);
		$this->view->setVar('source_id', $vn_source_id);
		
		//
		// Is record of correct type?
		// 
		$va_restrict_to_types = null;
		if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
			$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_EDIT__));
		}
		
		if (($vn_type_id = $t_subject->get('type_id')) && is_array($va_restrict_to_types) && !in_array($vn_type_id, $va_restrict_to_types)) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		//
		// Is record from correct source?
		// 
		$va_restrict_to_sources = null;
		if ($t_subject->getAppConfig()->get('perform_source_access_checking')) {
			$va_restrict_to_sources = caGetSourceRestrictionsForUser($this->ops_table_name, array('access' => $vn_subject_id ? __CA_BUNDLE_ACCESS_READONLY__ : __CA_BUNDLE_ACCESS_EDIT__));
		
			if (!$t_subject->get('source_id')) {
				$t_subject->set('source_id', $t_subject->getDefaultSourceID(array('request' => $this->request)));
			}
		
			if (is_array($va_restrict_to_sources) && !in_array($t_subject->get('source_id'), $va_restrict_to_sources)) {
				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2562?r='.urlencode($this->request->getFullUrlPath()));
				return;
			}
		}
		
		
		if (is_array($va_prepopulate_quickadd_fields = explode(";", $this->request->getParameter('prepopulate_fields', pString)))) {
			$va_prepopulate_quickadd_fields = array_filter($va_prepopulate_quickadd_fields, function($v) { return strlen($v); });
			foreach($va_prepopulate_quickadd_fields as $vs_field) {
				if ($t_subject->hasField($vs_field)) { $t_subject->set($vs_field, caUcFirstUTF8Safe($this->view->getVar('q'))); }
			}
		}
		
		if(is_array($pa_values)) {
			foreach($pa_values as $vs_key => $vs_val) {
				$t_subject->set($vs_key, $vs_val);
			}
		}
		
		// Set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
		if ($vs_idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
			if ($vn_parent_id > 0) {
				$t_parent = Datamodel::getInstanceByTableName($this->ops_table_name);
				if ($t_parent->load($vn_parent_id)) {
					$this->view->setVar('_context_id', $t_parent->get($vs_idno_context_field));
				}
			}
		}
		
		// Get type
		if (!($vn_type_id = $t_subject->getTypeID())) {
			if (!($vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pString))) {
				if ($vs_type = $this->request->config->get('quickadd_'.$t_subject->tableName().'_default_type')) {
					$va_tmp = caMakeTypeIDList($t_subject->tableName(), array($vs_type));
					$vn_type_id = array_shift($va_tmp);
				}
				if (!$vn_type_id) {
					$vn_type_id = $t_subject->getDefaultTypeID();
				}
			}
		}
		
		// Set type restrictions of bundle that spawned quickadd form
		if ($vs_restrict_to_types = trim($this->request->getParameter('types', pString))) {
			$this->view->setVar('restrict_to_types', $va_restrict_to_type_ids = caMakeTypeIDList($t_subject->tableName(), explode(',', $vs_restrict_to_types), array('dont_include_subtypes_in_type_restriction' => (bool)$this->request->getParameter('dont_include_subtypes_in_type_restriction', pString))));
			
			if (!in_array($vn_type_id, $va_restrict_to_type_ids)) {
				$vn_type_id = $va_restrict_to_type_ids[0];		// get first type on list since default isn't part of restriction
			}
		}
		
		if (!is_array($va_prepopulate_quickadd_fields) || !sizeof($va_prepopulate_quickadd_fields) || in_array('preferred_labels', $va_prepopulate_quickadd_fields)) {		
			global $g_ui_locale_id;
			$v = caUcFirstUTF8Safe($this->view->getVar('q'));
			$va_force_new_label = [
				'locale_id' => $g_ui_locale_id, 									// use default locale
				$t_subject->getLabelDisplayField() => $v					// query text is used for display field
			];
			foreach($t_subject->getLabelUIFields() as $vn_i => $vs_fld) {
				if ($vs_fld === $t_subject->getLabelDisplayField()) { continue; }
				$va_force_new_label[$vs_fld] = '';
			}
			
			switch($t_subject->tableName()) {
				case 'ca_list_items':
					// Populate secondary display fields for lists items (name_plural)
					if(is_array($sec = $t_subject->getSecondaryLabelDisplayFields())) {
						foreach($sec as $s) {
							$va_force_new_label[$s] = $v;
						}
					}	
					break;
				case 'ca_entities':
					if(caGetListItemSettingValue('entity_types', caGetListItemIdno($vn_type_id), 'entity_class') === 'ORG') {
						// Force surname to text to ensure organization name is visible
						$va_force_new_label['surname'] = $v;
					} elseif($this->request->config->get('ca_entities_split_name_on_quickadd_load')) {
						// Prepopulate with split name
						$va_force_new_label = array_merge($va_force_new_label, DataMigrationUtils::splitEntityName($v));
					}
					break;
			}				
			$this->view->setVar('forceLabel', $va_force_new_label);
		}
		
		$this->view->setVar('restrict_to_lists', $this->request->getParameter('lists', pString));
		
		$this->request->setParameter('type_id', $vn_type_id);
		if($t_subject->hasField('type_id')) {
			$t_subject->set('type_id', $vn_type_id);
		}
		
		$t_ui = ca_editor_uis::loadDefaultUI($this->ops_table_name, $this->request, $vn_type_id, array('editorPref' => 'quickadd'));
		if (!$t_ui) {
			$this->postError(1100, _t('No interface defined for %1', $this->ops_table_name), 'BaseQuickAddController->Form');
			return;
		}
		// Get default screen (this is all we show in quickadd, even if the UI has multiple screens)
		$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, $vn_type_id, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
			[],
			[]
		);
		
		$this->view->setVar('t_ui', $t_ui);
		$this->view->setVar('screen', $va_nav['defaultScreen']);
		
		$va_field_values = $t_subject->extractValuesFromRequest($va_nav['defaultScreen'], $this->request, array('ui_instance' => $t_ui, 'dontReturnSerialIdno' => true));
		
		// Set intrinsics
		if (is_array($va_field_values['intrinsic'])) {
			foreach($va_field_values['intrinsic'] as $vs_f => $vs_v) {
				$t_subject->set($vs_f, $vs_v);
			}
		}	
		
		// Set attributes
		if (is_array($va_field_values['attributes'])) {
			foreach($va_field_values['attributes'] as $vn_element_id => $va_attribute_values) {
				$t_subject->setFailedAttributeInserts($vn_element_id, $va_attribute_values);
			}		
		}
		
		// Set preferred labels
		if (is_array($va_field_values['preferred_label'])) {
			$t_subject->setFailedPreferredLabelInserts($va_field_values['preferred_label']);		
		}
		
		
		// Set nonpreferred labels
		if (is_array($va_field_values['nonpreferred_label'])) {
			$t_subject->setFailedNonPreferredLabelInserts($va_field_values['nonpreferred_label']);		
		}
		
		// Set annotation properties
		if (is_array($va_field_values['annotation_properties']) && method_exists($t_subject, 'setPropertyValue')) {
			foreach($va_field_values['annotation_properties'] as $vs_property => $vs_property_value) {
				$t_subject->setPropertyValue($vs_property, $vs_property_value);	
			}	
		}
		
		
		if (!$t_ui->getPrimaryKey()) {
			$this->notification->addNotification(_t('There is no configuration available for this editor. Check your system configuration and ensure there is at least one valid configuration for this type of editor.'), __NOTIFICATION_TYPE_ERROR__);
		}
		
		# Trigger "EditItem" hook 
		$this->opo_app_plugin_manager->hookEditItem(array('id' => null, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject));
		
		// Set form unique identifiers
		$this->view->setVar('fieldNamePrefix', $_REQUEST['_formName']);
		$this->view->setVar('n', $vs_n);
		
		
		$this->view->setVar('default_parent_id', $this->opo_result_context->getParameter($t_subject->tableName().'_last_parent_id'));
		
		$this->view->setVar('notifications', $this->notification->getNotifications());
		
		$this->render('quickadd_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
	 *
	 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
	 */
	public function Save($pa_options=null) {
		list($t_subject, $t_ui, $vn_parent_id, $vn_above_id) = $this->_initView($pa_options);
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		// Is user allowed to quickadd?
		if (!(is_array($pa_options) && isset($pa_options['dontCheckQuickAddAction']) && (bool)$pa_options['dontCheckQuickAddAction']) && (!$this->request->user->canDoAction('can_quickadd_'.$t_subject->tableName()))) {
			$va_response = array(
				'status' => 30,
				'id' => null,
				'table' => $t_subject->tableName(),
				'type_id' => null,
				'display' => null,
				'errors' => array(_t("You are not allowed to quickadd %1", $t_subject->getProperty('NAME_PLURAL')) => _t("You are not allowed to quickadd %1", $t_subject->getProperty('NAME_PLURAL')))
			);
			
			$this->view->setVar('response', $va_response);
			
			$this->render('quickadd_result_json.php');
			return;
		}
		
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
		// Is record from correct source?
		// 
		$va_restrict_to_sources = null;
		if ($t_subject->getAppConfig()->get('perform_source_access_checking')) {
			if (is_array($va_restrict_to_sources = caGetSourceRestrictionsForUser($this->ops_table_name, array('access' => __CA_BUNDLE_ACCESS_EDIT__)))) {
				if (
					(!$t_subject->get('source_id'))
					||
					($t_subject->get('source_id') && !in_array($t_subject->get('source_id'), $va_restrict_to_sources))
					||
					((strlen($vn_source_id = $this->request->getParameter('source_id', pInteger))) && !in_array($vn_source_id, $va_restrict_to_sources))
				) {
					$t_subject->set('source_id', $t_subject->getDefaultSourceID(array('request' => $this->request)));
				}
		
				if (is_array($va_restrict_to_sources) && !in_array($t_subject->get('source_id'), $va_restrict_to_sources)) {
					$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2562?r='.urlencode($this->request->getFullUrlPath()));
					return;
				}
			}
		}
		
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
			
			$this->render('quickadd_result_json.php');
			return;
		}
		
		// Set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
		$vn_context_id = null;
		if ($vs_idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
			if ($t_subject->getPrimaryKey() > 0) {
				$this->view->setVar('_context_id', $vn_context_id = $t_subject->get($vs_idno_context_field));
			} else {
				if ($vn_parent_id > 0) {
					$t_parent = Datamodel::getInstanceByTableName($this->ops_table_name);
					if ($t_parent->load($vn_parent_id)) {
						$this->view->setVar('_context_id', $vn_context_id = $t_parent->get($vs_idno_context_field));
					}
				}
			}
			
			if ($vn_context_id) { $t_subject->set($vs_idno_context_field, $vn_context_id); }
		}
		
		// Set type name for display
		if (!($vs_type_name = $t_subject->getTypeName())) {
			$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
		}
		
		# trigger "BeforeSaveItem" hook 
		$this->opo_app_plugin_manager->hookBeforeSaveItem(array('id' => null, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => true));
		
		$vn_parent_id = $this->request->getParameter('parent_id', pInteger);
		$t_subject->set('parent_id', $vn_parent_id);
		$this->opo_result_context->setParameter($t_subject->tableName().'_last_parent_id', $vn_parent_id);
		
		if($hier_id_fld = $t_subject->getProperty('HIERARCHY_ID_FLD')) {
			$t_subject->set($hier_id_fld, $this->request->getParameter('hier_id', pInteger));
		}
		
		// Set lot_id when quick-adding objects from lots
		if (($t_subject->tableName() == 'ca_objects') && ($vn_lot_id = $this->request->getParameter('lot_id', pInteger))) {
			$t_subject->set('lot_id', $vn_lot_id);
		}
		
		$va_opts = array_merge($pa_options, array('ui_instance' => $t_ui));
		$vb_save_rc = $t_subject->saveBundlesForScreen($this->request->getParameter('screen', pString), $this->request, $va_opts);
		$this->view->setVar('t_ui', $t_ui);
	
		if(!$t_subject->getPrimaryKey()) {
			$vn_subject_id = $t_subject->getPrimaryKey();
			if (!$vb_save_rc) {
				$vs_message = _t("Could not save %1", $vs_type_name);
			} else {
				$vs_message = _t("Added %1", $vs_type_name);
				$this->request->setParameter($t_subject->primaryKey(), $vn_subject_id, 'GET');
				$this->view->setVar($t_subject->primaryKey(), $vn_subject_id);
				$this->view->setVar('subject_id', $vn_subject_id);
				Session::setVar($this->ops_table_name.'_browse_last_id', $vn_subject_id);	// set last edited
				
				// Set ACL for newly created record
				if (caACLIsEnabled($t_subject)) {
					$t_subject->setACLUsers(array($this->request->getUserID() => __CA_ACL_EDIT_DELETE_ACCESS__));
					$t_subject->setACLWorldAccess($t_subject->getAppConfig()->get('default_item_access_level'));
				}
			}
			
		} else {
			$vs_message = _t("Saved changes to %1", $vs_type_name);
		}
		
		$va_errors = $this->request->getActionErrors();							// all errors from all sources
		$va_general_errors = $this->request->getActionErrors('general');		// just "general" errors - ones that are not attached to a specific part of the form
		
		if(($vn_num_errors = (sizeof($va_errors) - sizeof($va_general_errors))) > 0) {
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
		
		$vn_relation_id = null;
		if ($vn_id && !$vb_no_save_error) {
			$va_tmp = caProcessRelationshipLookupLabel($t_subject->makeSearchResult($t_subject->tableName(), array($vn_id)), $t_subject);
			$va_name = array_pop($va_tmp);
						
			// Add relationship to added item here?
			$pn_related_id = $this->request->getParameter('relatedID', pInteger);
			$ps_related_table = $this->request->getParameter('relatedTable', pString);
			$ps_relationship_type = $this->request->getParameter('relationshipType', pString);
			if ($pn_related_id && $ps_related_table && $ps_relationship_type) {
				if ($t_rel = $t_subject->addRelationship($ps_related_table, $pn_related_id, $ps_relationship_type)) {
					$vn_relation_id = $t_rel->getPrimaryKey();
				}
			}
		} else {
			$va_name = array();
		}
		
		$va_response = array(
			'status' => (is_array($va_error_list) && sizeof($va_error_list)) ? 10 : 0,
			'id' => $vn_id,
			'table' => $t_subject->tableName(),
			'type_id' => method_exists($t_subject, "getTypeID") ? $t_subject->getTypeID() : null,
			'relation_id' => $vn_relation_id,
			'display' => $va_name['label'],
			'errors' => $va_error_list
		);
		
		$this->view->setVar('response', $va_response);
		
		$this->render('quickadd_result_json.php');
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
		AssetLoadManager::register('ckeditor');
		
		$t_subject = Datamodel::getInstanceByTableName($this->ops_table_name);
		
		if (is_array($pa_options) && isset($pa_options['loadSubject']) && (bool)$pa_options['loadSubject'] && ($vn_subject_id = (int)$this->request->getParameter($t_subject->primaryKey(), pInteger))) {
			$t_subject->load($vn_subject_id);
		}
		if (is_array($pa_options) && isset($pa_options['forceSubjectValues']) && is_array($pa_options['forceSubjectValues'])) {
			foreach($pa_options['forceSubjectValues'] as $vs_f => $vs_v) {
				$t_subject->set($vs_f, $vs_v);
			}
		}
		
		// empty (ie. new) rows don't have a type_id set, which means we'll have no idea which attributes to display
		// so we get the type_id off of the request
		if (!$vn_type_id = $this->request->getParameter($t_subject->getTypeFieldName(), pString)) {
			$vn_type_id = null;
		}
		
		// then set the empty row's type_id
		if($vs_type_field_name = $t_subject->getTypeFieldName()) {
			$t_subject->set($vs_type_field_name, $vn_type_id);
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
		
		if($hier_id_fld = $t_subject->getProperty('HIERARCHY_ID_FLD')) {
			$this->view->setVar('hier_id', $hier_id = $this->request->getParameter($hier_id_fld, pInteger));
		}
		if ($vs_parent_id_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD')) {
			$this->view->setVar($vs_parent_id_fld, $vn_parent_id = $this->request->getParameter($vs_parent_id_fld, pInteger));

			return array($t_subject, $t_ui, $vn_parent_id, null);
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
