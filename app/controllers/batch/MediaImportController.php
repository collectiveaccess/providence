<?php
/** ---------------------------------------------------------------------
 * app/lib/MediaImportController.php : 
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
require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");
require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__."/ResultContext.php");
require_once(__CA_LIB_DIR__."/BatchProcessor.php");
require_once(__CA_LIB_DIR__."/BatchEditorProgress.php");
require_once(__CA_LIB_DIR__."/BatchMediaImportProgress.php");


class MediaImportController extends ActionController {
	# -------------------------------------------------------
	protected $opo_app_plugin_manager;
	protected $opo_result_context;

	protected $opa_importable_tables = array();
	
	protected $user_can_delete_media_on_import = false;
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
		
		$this->user_can_delete_media_on_import = (bool)$po_request->user->canDoAction('allow_delete_media_after_import');

		
		AssetLoadManager::register('bundleableEditor');
		AssetLoadManager::register('panel');
		
		$this->opo_app_plugin_manager = new ApplicationPluginManager();
		$this->opo_result_context = new ResultContext($po_request, $this->ops_table_name, ResultContext::getLastFind($po_request, $this->ops_table_name));

		$this->opa_importable_tables = array(
			caGetTableDisplayName('ca_objects') => 'ca_objects',
			caGetTableDisplayName('ca_entities') => 'ca_entities',
			caGetTableDisplayName('ca_places') => 'ca_places',
			caGetTableDisplayName('ca_collections') => 'ca_collections',
			caGetTableDisplayName('ca_occurrences') => 'ca_occurrences',
			caGetTableDisplayName('ca_storage_locations') => 'ca_storage_locations',
			caGetTableDisplayName('ca_object_lots') => 'ca_object_lots',
			caGetTableDisplayName('ca_movements') => 'ca_movements',
			caGetTableDisplayName('ca_loans') => 'ca_loans',
		);

		foreach($this->opa_importable_tables as $vs_key => $vs_table) {
			if($this->getRequest()->getAppConfig()->get($vs_table.'_disable')) {
				unset($this->opa_importable_tables[$vs_key]);
			}
		}
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
		AssetLoadManager::register("directoryBrowser");
		list($t_ui) = $this->_initView($pa_options);
		
		$o_config = Configuration::load();
		
		$this->view->setVar('batch_mediaimport_last_settings', $va_last_settings = is_array($va_last_settings = $this->request->user->getVar('batch_mediaimport_last_settings')) ? $va_last_settings : array());

		// get import type from request
		$vs_import_target = $this->getRequest()->getParameter('target', pString);
		$t_instance = Datamodel::getInstance($vs_import_target);
		// if that failed, try last settings
		if(!$t_instance) {
			$vs_import_target = $va_last_settings['importTarget'] ?? null;
			$t_instance = Datamodel::getInstance($vs_import_target);
		}
		// if that too failed, go back to objects
		if(!$t_instance) {
			$t_instance = new ca_objects();
			$vs_import_target = 'ca_objects';
		}
		$this->getView()->setVar('import_target', $vs_import_target);

		$t_instance->set('status', $va_last_settings[$vs_import_target.'_status'] ?? null);
		$t_instance->set('access', $va_last_settings[$vs_import_target.'_access'] ?? null);
		
		$t_rep = new ca_object_representations();
		$t_rep->set('status', $va_last_settings['ca_object_representations_status'] ?? null);
		$t_rep->set('access', $va_last_settings['ca_object_representations_access'] ?? null);
		
		$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, null, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
			[],
			[]
		);
		if (!$this->request->getActionExtra() || !isset($va_nav['fragment'][str_replace("Screen", "screen_", $this->request->getActionExtra())])) {
			$this->request->setActionExtra($va_nav['defaultScreen'] ?? null);
		}
		$this->view->setVar('t_ui', $t_ui);

		$this->view->setVar('import_target', caHTMLSelect('import_target', $this->opa_importable_tables, array(
			'id' => 'caImportTargetSelect',
			'onchange' => 'window.location.replace("'.caNavUrl($this->getRequest(), $this->getRequest()->getModulePath(), $this->getRequest()->getController(), $this->getRequest()->getAction()) . '/target/" + jQuery("#caImportTargetSelect").val()); return false;'
		), array('value' => $vs_import_target)));
		
		if(!sizeof($import_modes = caGetAvailableMediaUploadModes())) {
			throw new ApplicationException(_t('No import modes are configured. Check the application configuration <em>media_importer_allowed_modes</em> setting and make sure at least one valid mode is set.'));
		}
		
		$this->view->setVar('import_mode', caHTMLSelect('import_mode', $import_modes, ['id' => 'importMode'], ['value' => $va_last_settings['importMode'] ?? null]));
		
		$this->view->setVar('match_mode', caHTMLSelect('match_mode', [
			_t('Match using file name') => 'FILE_NAME',
			_t('Match using directory name') => 'DIRECTORY_NAME',
			_t('Match using directory name, then file name') => 'FILE_AND_DIRECTORY_NAMES'
		], [], ['value' => $va_last_settings['matchMode'] ?? null]));
		
		$this->view->setVar('match_type', caHTMLSelect('match_type', [
			_t('matches exactly') => 'EXACT',
			_t('starts with') => 'STARTS',
			_t('ends with') => 'ENDS',
			_t('contains') => 'CONTAINS'
		], [], ['value' => $va_last_settings['matchType'] ?? null]));
		
		$this->view->setVar('user_can_delete_media_on_import', $this->user_can_delete_media_on_import);
		
		$this->view->setVar($vs_import_target.'_type_list', $t_instance->getTypeListAsHTMLFormElement($vs_import_target.'_type_id', ['id' => 'primary_type_id'], array('value' => $va_last_settings[$vs_import_target.'_type_id'] ?? null)));
		$this->view->setVar($vs_import_target.'_parent_type_list', $t_instance->getTypeListAsHTMLFormElement($vs_import_target.'_parent_type_id', ['id' => 'parent_type_id'], array('value' => $va_last_settings[$vs_import_target.'_parent_type_id'] ?? $t_instance->getTypeIDForCode($o_config->get('media_importer_hierarchy_parent_type')))));
		$this->view->setVar($vs_import_target.'_child_type_list', $t_instance->getTypeListAsHTMLFormElement($vs_import_target.'_child_type_id', ['id' => 'child_type_id'], array('value' => $va_last_settings[$vs_import_target.'_child_type_id'] ?? $t_instance->getTypeIDForCode($o_config->get('media_importer_hierarchy_child_type')))));
		$this->view->setVar($vs_import_target.'_limit_to_types_list', $t_instance->getTypeListAsHTMLFormElement($vs_import_target.'_limit_matching_to_type_ids[]', array('multiple' => 1), array('height' => '100px', 'values' => $va_last_settings[$vs_import_target.'_limit_matching_to_type_ids'] ?? null)));
		$this->view->setVar('ca_object_representations_type_list', $t_rep->getTypeListAsHTMLFormElement('ca_object_representations_type_id', null, array('value' => $va_last_settings['ca_object_representations_type_id'] ?? null)));

		if($vs_import_target != 'ca_objects') { // non-object representations have relationship types
			$t_rel = ca_relationship_types::getRelationshipTypeInstance($t_instance->tableName(), 'ca_object_representations');
			$this->getView()->setVar($vs_import_target.'_representation_relationship_type', $t_rel->getRelationshipTypesAsHTMLSelect('ltor',null,null, array('name' => $vs_import_target.'_representation_relationship_type'), array('value' => $va_last_settings[$vs_import_target.'_representation_relationship_type'])));
		}
		if((bool)$this->request->config->get('allow_user_selection_of_embedded_metadata_extraction_mapping')) {
			$add_null_opt = (bool)$this->request->config->get('allow_user_embedded_metadata_extraction_mapping_null_option');
			$va_object_importer_options = ca_data_importers::getImportersAsHTMLOptions(['formats' => ['exif', 'mediainfo'], 'tables' => [$t_instance->tableName()], 'nullOption' => $add_null_opt ? '-' : null]);
			$va_object_representation_importer_options = ca_data_importers::getImportersAsHTMLOptions(['formats' => ['exif', 'mediainfo'], 'tables' => ['ca_object_representations'], 'nullOption' => $add_null_opt ? '-' : null]);
			
			$this->view->setVar($vs_import_target.'_mapping_list', caHTMLSelect($vs_import_target.'_mapping_id', $va_object_importer_options, array(), array('value' => $va_last_settings[$vs_import_target.'_mapping_id'])));
			
			$c = sizeof($va_object_importer_options);
			if ($add_null_opt) { $c--;}
			$this->view->setVar($vs_import_target.'_mapping_list_count', $c);
			$this->view->setVar('ca_object_representations_mapping_list', caHTMLSelect('ca_object_representations_mapping_id', $va_object_representation_importer_options, array(), array('value' => $va_last_settings['ca_object_representations_mapping_id'])));
			
			$c = sizeof($va_object_representation_importer_options);
			if ($add_null_opt) { $c--;}
			$this->view->setVar('ca_object_representations_mapping_list_count', $c);
		} else {
			$va_object_importer_options = $va_object_representation_importer_options = null;
		}
		
		//
		// Available sets
		//
		$t_set = new ca_sets();
		$va_available_set_list = caExtractValuesByUserLocale($t_set->getSets(array('table' => $vs_import_target, 'user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__, 'omitCounts' => true)));
		$va_available_sets = array();
		foreach($va_available_set_list as $vn_set_id => $va_set) {
			$va_available_sets[$va_set['name']] = $vn_set_id;
		}
		$this->view->setVar('available_sets', $va_available_sets);


		$this->view->setVar('t_instance', $t_instance);
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
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
			$this->Index();
			return;
		}
		global $g_ui_locale_id;
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		list($t_ui) = $this->_initView($pa_options);

		$vs_import_target = $this->getRequest()->getParameter('import_target', pString);
		if(!Datamodel::tableExists($vs_import_target)) {
			$vs_import_target = 'ca_objects';
		}
		$directory = $this->request->getParameter('directory', pString);

		if (!caIsValidMediaImportDirectory($directory, ['user_id' => $this->request->getUserID()])) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		$va_options = array(
			'sendMail' => (bool)$this->request->getParameter('send_email_when_done', pInteger), 
			'sendSMS' => (bool)$this->request->getParameter('send_sms_when_done', pInteger), 
			'runInBackground' => (bool)$this->request->getParameter('run_in_background', pInteger),
			
			'importFromDirectory' => $directory,
			'includeSubDirectories' => (bool)$this->request->getParameter('include_subdirectories', pInteger),
			'deleteMediaOnImport' => $this->user_can_delete_media_on_import && (bool)$this->request->getParameter('delete_media_on_import', pInteger),
			'importMode' => $this->request->getParameter('import_mode', pString),
			'matchMode' => $this->request->getParameter('match_mode', pString),
			'matchType' => $this->request->getParameter('match_type', pString),
			$vs_import_target.'_limit_matching_to_type_ids' => $this->request->getParameter($vs_import_target.'_limit_matching_to_type_ids', pArray),
			$vs_import_target.'_type_id' => $this->request->getParameter($vs_import_target.'_type_id', pInteger),
			$vs_import_target.'_parent_type_id' => $this->request->getParameter($vs_import_target.'_parent_type_id', pInteger),
			$vs_import_target.'_child_type_id' => $this->request->getParameter($vs_import_target.'_child_type_id', pInteger),
			'ca_object_representations_type_id' => $this->request->getParameter('ca_object_representations_type_id', pInteger),
			$vs_import_target.'_status' => $this->request->getParameter($vs_import_target.'_status', pInteger),
			'ca_object_representations_status' => $this->request->getParameter('ca_object_representations_status', pInteger),
			$vs_import_target.'_access' => $this->request->getParameter($vs_import_target.'_access', pInteger),
			'ca_object_representations_access' => $this->request->getParameter('ca_object_representations_access', pInteger),
			$vs_import_target.'_mapping_id' => $this->request->getParameter($vs_import_target.'_mapping_id', pInteger),
			'ca_object_representations_mapping_id' => $this->request->getParameter('ca_object_representations_mapping_id', pInteger),
			'setMode' => $this->request->getParameter('set_mode', pString),
			'setCreateName' => $this->request->getParameter('set_create_name', pString),
			'set_id' => $this->request->getParameter('set_id', pInteger),
			'idnoMode' => $this->request->getParameter('idno_mode', pString),
			'labelMode' => $this->request->getParameter('label_mode', pString),
			'labelText' => $this->request->getParameter('label_text', pString),
			'idno' => $this->request->getParameter('idno', pString),
			'representationIdnoMode' => $this->request->getParameter('representation_idno_mode', pString),
			'representation_idno' => $this->request->getParameter('idno_representation_number', pString),
			'logLevel' => $this->request->getParameter('log_level', pString),
			'allowDuplicateMedia' => $this->request->getParameter('allow_duplicate_media', pInteger),
			'replaceExistingMedia' => $this->request->getParameter('replace_existing_media', pInteger),
			'locale_id' => $g_ui_locale_id,
			'user_id' => $this->request->getUserID(),
			'skipFileList' => $this->request->getParameter('skip_file_list', pString),
			'importTarget' => $vs_import_target
		);

		if($vn_rel_type = $this->request->getParameter($vs_import_target.'_representation_relationship_type', pInteger)) {
			$va_options[$vs_import_target.'_representation_relationship_type'] = $vn_rel_type;
		}

		if (is_array($va_create_relationships_for = $this->request->getParameter('create_relationship_for', pArray))) {
			$va_options['create_relationship_for'] = $va_create_relationships_for;
			foreach($va_create_relationships_for as $vs_rel_table) {
				$va_options['relationship_type_id_for_'.$vs_rel_table] = $this->request->getParameter('relationship_type_id_for_'.$vs_rel_table, pString);
			}
		}
		$va_last_settings = $va_options;
		$va_last_settings['importFromDirectory'] = $va_last_settings['importFromDirectory'];
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
	 * @param string|array $dirs The path to the directory you wish to get the contents list for
	 * @param bool $pb_include_hidden_files Optional. By default caGetDirectoryContentsAsList() does not consider hidden files (files starting with a '.') when calculating file counts. Set this to true to include hidden files in counts. Note that the special UNIX '.' and '..' directory entries are *never* counted as files.
	 * @param int $pn_max_length_of_name Maximum length in characters of returned file names. Note that the full name is always returned in the 'fullname' value. Only 'name' is truncated.
	 * @return array An array of file names.
	 */
	private function _getDirectoryListing($dirs, $pb_include_hidden_files=false, $pn_max_length_of_name=25, $pn_start_at=0, $pn_max_items_to_return=25) {
		if (!is_array($dirs)) { $dirs = [$dirs]; }

		$va_file_list = [];
		foreach($dirs as $dir) {
			if (!is_dir($dir)) { continue; }
			if(substr($dir, -1, 1) == "/"){
				$dir = substr($dir, 0, strlen($dir) - 1);
			}

			if($va_paths = @scandir($dir, 0)) {
				$vn_i = $vn_c = 0;
				foreach($va_paths as $item) {
					if (($item != ".") && ($item != "..") && ($pb_include_hidden_files || (!$pb_include_hidden_files && ($item[0] !== '.')))) {
						$vb_is_dir = is_dir("{$dir}/{$item}");
						$vs_k = preg_replace('![@]{2,}!', '|', $item);
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
		AssetLoadManager::register('bundleableEditor');
		AssetLoadManager::register('imageScroller');
		AssetLoadManager::register('datePickerUI');
		
		$t_ui = new ca_editor_uis();
		if (!isset($pa_options['ui']) || !$pa_options['ui']) {
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
		$user_import_root_directory = caGetMediaUploadPathForUser($this->request->getUserID());
		$shared_import_root_directory = caGetSharedMediaUploadPath();
		
		$va_level_data = [];
		
		if ($this->request->getParameter('init', pInteger)) { 
			//
			// On first load (init) of browser load all levels in single request
			//
			$va_tmp = explode(";", $ps_id);
			
			$va_acc = array();
			$vn_i = 0;

			foreach($va_tmp as $vs_tmp) {
				list($vs_directory, $vn_start) = explode("@@", $vs_tmp);
				if (!$vs_directory) { continue; }
				
				$va_tmp = array_filter(explode('/', $vs_directory), function($v) { 
					$v = trim($v);
					if(!strlen($v)) { return false; }
					return !in_array($v[0], ['.', '~']);
				});
				$vs_directory = join('/', $va_tmp);
				$vs_k = array_pop($va_tmp);
				if(!$vs_k) { $vs_k = '/'; }
				
				$va_level_data["{$vs_k}|{$vn_i}"] = $va_file_list = $this->_getDirectoryListing([$user_import_root_directory.'/'.$vs_directory, $shared_import_root_directory.'/'.$vs_directory], false, 20, (int)$vn_start, (int)$pn_max);
				$va_level_data["{$vs_k}|{$vn_i}"]['_primaryKey'] = 'name';
				
				$va_counts = caGetDirectoryContentsCount($user_import_root_directory.'/'.$vs_directory, false, false);
				$va_shared_counts = caGetDirectoryContentsCount($shared_import_root_directory.'/'.$vs_directory, false, false);
				$va_level_data["{$vs_k}|{$vn_i}"]['_itemCount'] = $va_counts['files'] + $va_counts['directories'] + $va_shared_counts['files'] + $va_shared_counts['directories'];
				$vn_i++;
			}
		} else {
			list($ps_directory, $pn_start) = explode("@@", $ps_id);
			
			Session::setVar('lastMediaImportDirectoryPath', $ps_directory);

			$va_tmp = ($ps_directory === '/') ? [''] : explode('/', $ps_directory);
			$va_tmp = array_filter(explode('/', $ps_directory), function($v) { 
				$v = trim($v);
				if(!strlen($v)) { return false; }
				return !in_array($v[0], ['.', '~']);
			});
			$ps_directory = join('/', $va_tmp);
				
			$vn_level = sizeof($va_tmp);
			if ($ps_directory[0] == '/') { $vn_level--; }
			
			if (!$ps_directory) { 
				$va_level_data["{$vs_k}|{$vn_level}"] = array('/' => 
						array(
							'item_id' => '/',
							'name' => 'Root',
							'type' => 'DIR',
							'children' => 1
						)
				);
				$va_level_data["{$vs_k}|{$vn_level}"]['_primaryKey'] = 'name';
				$va_level_data["{$vs_k}|{$vn_level}"]['_itemCount'] = 1;
			} else {
				$va_tmp = explode('/', $ps_directory);
				$vs_k = array_pop($va_tmp);
				if(!$vs_k) { $vs_k = '/'; }

				$va_file_list = $this->_getDirectoryListing([$user_import_root_directory.'/'.$ps_directory, $shared_import_root_directory.'/'.$ps_directory], false, 20, (int)$pn_start, (int)$pn_max);
				$va_level_data["{$vs_k}|{$vn_level}"] = $va_file_list;
				$va_level_data["{$vs_k}|{$vn_level}"]['_primaryKey'] = 'name';
				
				$va_counts = caGetDirectoryContentsCount($user_import_root_directory.'/'.$ps_directory, false, false);
				$va_shared_counts = caGetDirectoryContentsCount($shared_import_root_directory.'/'.$ps_directory, false, false);
				$va_level_data["{$vs_k}|{$vn_level}"]['_itemCount'] = $va_counts['files'] + $va_counts['directories'] + $va_shared_counts['files'] + $va_shared_counts['directories'];
			}
		}
		
		$this->view->setVar('directory_list', caSanitizeArray($va_level_data));
		
		
		$this->render('mediaimport/directory_level_json.php');
	}
	# ------------------------------------------------------------------
	public function GetDirectoryAncestorList() {
		$ps_id = $this->request->getParameter('id', pString);
		list($ps_directory, $pn_start) = explode("@@", $ps_id);
		
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
		$directory = $this->request->getParameter('path', pString);

		$upload_path = caIsValidMediaImportDirectory($directory, ['user_id' => $this->request->getUserID()]);
		if (!$upload_path) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3250?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}

		$extensions = Media::getImportFileExtensions();
		$response = array('path' => $directory, 'uploadMessage' => '', 'skippedMessage' => '', 'copied' => []);

		if (!is_writeable($upload_path)) {
			$response['error'] = _t('Cannot write file: directory %1 is not accessible', $directory);
		} else {
			foreach($_FILES as $param => $file) {
				foreach($file['name'] as $i => $name) {
					if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extensions)) {
						$response['skipped'][$name] = true;
						continue;
					}
					if (copy($file['tmp_name'][$i], $upload_path."/".$name)) {
						$response['copied'][$name] = true;
					} else {
						$response['skipped'][$name] = true;
					}
				}
			}
		}

		$response['uploadMessage'] = (($upload_count = sizeof($response['copied'])) == 1) ? _t('Uploaded %1 file', $upload_count) : _t('Uploaded %1 files', $upload_count);
		if (is_array($response['skipped']) && ($skip_count = sizeof($response['skipped'])) && !$response['error']) {
			$response['skippedMessage'] = ($skip_count == 1) ? _t('Skipped %1 file', $skip_count) : _t('Skipped %1 files', $skip_count);
		}

		$this->view->setVar('response', $response);
		$this->render('mediaimport/file_upload_response_json.php');
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function DownloadLog() {
		$tmp_dir = caGetTempDirPath(['useAppTmpDir' => true]);
		$file = preg_replace("![^A-Za-z0-9_]+!", "", $this->request->getParameter('file', pString));
		if(file_exists($path = "{$tmp_dir}/{$file}.csv")) {
			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
			$o_view->setVar('archive_path', $path);
			
			$file_name = 'error_log.csv';
			if (strpos($path, "SkipLog") !== false) {
				$file_name = 'skipped_files_log.csv';
			} else if(strpos($path, "ProcessingLog") !== false) {
				$file_name = 'processing_log.csv';
			} 
			$o_view->setVar('archive_name', $file_name);
			$this->response->addContent($o_view->render('download_file_binary.php'));
			return;
		} else {
			$this->notification->addNotification(_t('Invalid log'), __NOTIFICATION_TYPE_ERROR__);
			$this->Index();
		}
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
		
		$this->view->setVar('screen', $this->request->getActionExtra());						// name of screen
		$this->view->setVar('result_context', $this->getResultContext());
		
		return $this->render('mediaimport/widget_batch_info_html.php', true);
	}
	# ------------------------------------------------------------------
}
