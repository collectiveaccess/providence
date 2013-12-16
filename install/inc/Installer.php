<?php
/* ----------------------------------------------------------------------
 * install/inc/Installer.php : class that wraps installer functionality
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

class Installer {
	# --------------------------------------------------
	private $opa_errors;
	private $opb_debug;
	private $ops_profile_debug = "";
	# --------------------------------------------------
	private $ops_profile_dir;
	private $ops_profile_name;

	private $ops_admin_email;
	private $opb_overwrite;
	# --------------------------------------------------
	private $opo_profile;
	private $opo_base;
	private $ops_base_name;
	# --------------------------------------------------
	private $opa_locales;
	# --------------------------------------------------
	/**
	 * Constructor
	 *
	 * @param string $ps_profile_dir path to a directory containing profiles and XML schema
	 * @param string $ps_profile_name of the profile, as in <$ps_profile_dir>/<$ps_profile_name>.xml
	 * @param string $ps_admin_email e-mail address for the initial administrator account
	 * @param boolean $pb_overwrite overwrite existing install? optional, defaults to false
	 * @param boolean $pb_debug enable or disable debugging mode
	 */
	public function  __construct($ps_profile_dir,$ps_profile_name,$ps_admin_email=null,$pb_overwrite=false,$pb_debug=false) {
		$this->ops_profile_dir = $ps_profile_dir;
		$this->ops_profile_name = $ps_profile_name;
		$this->ops_admin_email = $ps_admin_email;
		$this->opb_overwrite = $pb_overwrite;
		$this->opb_debug = $pb_debug;

		$this->opa_locales = array();

		if($this->loadProfile($ps_profile_dir, $ps_profile_name)){
			$this->extractAndLoadBase();

			if(!$this->validateProfile()){
				$this->addError("Profile validation failed. Your profile doesn't conform to the required XML schema.");
			}
		} else {
			$this->addError("Could not read profile '{$ps_profile_name}'. Please check the file permissions.");
		}
	}
	# --------------------------------------------------
	/**
	 * @param string $ps_profile_dir path to a directory containing profiles and XML schema
	 * @param string $ps_profile_name of the profile, as in <$ps_profile_dir>/<$ps_profile_name>.xml
	 */
	static public function getProfileInfo($ps_profile_dir, $ps_profile_name) {
		$o_installer = new Installer($ps_profile_dir,$ps_profile_name);
		$o_installer->loadProfile($ps_profile_dir, $ps_profile_name);
		
		return array(
			'useForConfiguration' => $o_installer->getAttribute($o_installer->opo_profile, 'useForConfiguration'),
			'display' => (string)$o_installer->opo_profile->{'profileName'},
			'description' => (string)$o_installer->opo_profile->{'profileDescription'},
			'locales' => (string)$o_installer->opo_profile->{'locales'},
		);
	}
	# --------------------------------------------------
	private function validateProfile() {
		// simplexml doesn't support validation -> use DOMDocument
		$vo_profile = new DOMDocument();
		$vo_profile->load($this->ops_profile_dir."/".$this->ops_profile_name.".xml");
		
		if($this->opo_base){
			$vo_base = new DOMDocument();
			$vo_base->load($this->ops_profile_dir."/".$this->ops_base_name.".xml");

			if($this->opb_debug){
				ob_start();
				$vb_return = $vo_profile->schemaValidate($this->ops_profile_dir."/profile.xsd") && $vo_base->schemaValidate($this->ops_profile_dir."/profile.xsd");
				$this->ops_profile_debug .= ob_get_clean();
			} else {
				$vb_return = @$vo_profile->schemaValidate($this->ops_profile_dir."/profile.xsd") && @$vo_base->schemaValidate($this->ops_profile_dir."/profile.xsd");
			}
		} else {
			if($this->opb_debug){
				ob_start();
				$vb_return = $vo_profile->schemaValidate($this->ops_profile_dir."/profile.xsd");
				$this->ops_profile_debug .= ob_get_clean();
			} else {
				$vb_return = @$vo_profile->schemaValidate($this->ops_profile_dir."/profile.xsd");
			}
		}

		return $vb_return;
	}
	# --------------------------------------------------
	private function loadProfile($ps_profile_dir, $ps_profile_name) {
		$vs_file = $ps_profile_dir."/".$ps_profile_name.".xml";

		if(is_readable($vs_file)){
			$this->opo_profile = simplexml_load_file($vs_file);	
			return true;
		} else {
			return false;
		}
	}
	# --------------------------------------------------
	private function extractAndLoadBase(){
		$this->ops_base_name = self::getAttribute($this->opo_profile, "base");
		if($this->ops_base_name) {
			$this->opo_base = simplexml_load_file($this->ops_profile_dir."/".$this->ops_base_name.".xml");
		} else {
			$this->opo_base = null;
		}
	}
	# --------------------------------------------------
	# ERROR HANDLING / DEBUGGING
	# --------------------------------------------------
	private function addError($ps_error){
		$this->opa_errors[] = $ps_error;
	}
	# --------------------------------------------------
	/**
	 * Returns number of errors that occurred while processing
	 *
	 * @return int number of errors
	 */
	public function numErrors(){
		return sizeof($this->opa_errors);
	}
	# --------------------------------------------------
	/**
	 * Returns array of error messages
	 *
	 * @return array errors
	 */
	public function getErrors(){
		return $this->opa_errors;
	}
	# --------------------------------------------------
	/**
	 * Get profile debug info. Only has content if debug mode is enabled.
	 * WARNING: can lead to very verbose output, especially if the php
	 * extension xdebug is installed and enabled.
	 *
	 * @return string profile debug info
	 */
	public function getProfileDebugInfo(){
		return $this->ops_profile_debug;
	}
	# --------------------------------------------------
	# UTILITIES
	# --------------------------------------------------
	private static function getAttribute($po_simplexml, $ps_attr) {
		if(isset($po_simplexml[$ps_attr]))
			return (string) $po_simplexml[$ps_attr];
	}
	# --------------------------------------------------
	private static function getRandomPassword() {
		return substr(md5(uniqid(microtime())), 0, 6);
	}
	# --------------------------------------------------
	private static function createDirectoryPath($ps_path) {
		if (!file_exists($ps_path)) {
			if (!@mkdir($ps_path, 0777, true)) {
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}
	# --------------------------------------------------
	private static function addLabelsFromXMLElement($t_instance,$po_labels,$pa_locales){
		require_once(__CA_LIB_DIR__."/ca/LabelableBaseModelWithAttributes.php");

		if(!($t_instance instanceof LabelableBaseModelWithAttributes)){
			return false;
		}

		foreach($po_labels->children() as $vo_label){
			$va_label_values = array();
			$vs_locale = self::getAttribute($vo_label, "locale");
			$vn_locale_id = $pa_locales[$vs_locale];

			$vb_preferred = self::getAttribute($vo_label, "preferred");
			if((bool)$vb_preferred || is_null($vb_preferred)){
				$vb_preferred = true;
			} else {
				$vb_preferred = false;
			}

			foreach($vo_label->children() as $vo_field){
				$va_label_values[$vo_field->getName()] = (string) $vo_field;
			}

			$t_instance->addLabel($va_label_values, $vn_locale_id, false, $vb_preferred);
		}

		return true;
	}
	# --------------------------------------------------
	public function performPreInstallTasks(){
		$va_dir_creation_errors = array();
		$o_config = Configuration::load();

		// create Lucene dir
		if (($o_config->get('search_engine_plugin') == 'Lucene') && !file_exists($o_config->get('search_lucene_index_dir'))) {
			if (!self::createDirectoryPath($o_config->get('search_lucene_index_dir'))) {
				$this->addError("Couldn't create Lucene directory at ".$o_config->get('search_lucene_index_dir')." (only matters if you are using Lucene as your search engine)");
			}
		}

		// create tmp dir
		if (!file_exists($o_config->get('taskqueue_tmp_directory'))) {
			if (!self::createDirectoryPath($o_config->get('taskqueue_tmp_directory'))) {
				$this->addError("Couldn't create tmp directory at ".$o_config->get('taskqueue_tmp_directory'));
				return false;
			}
		} else {
			// if already exists then remove all contents to avoid stale cache
			caRemoveDirectory($o_config->get('taskqueue_tmp_directory'), false);
		}

		// Create media directories
		$o_media_volumes = new MediaVolumes();
		$va_media_volumes = $o_media_volumes->getAllVolumeInformation();

		$vs_base_dir = $o_config->get('ca_base_dir');
		$va_dir_creation_errors = array();
		foreach($va_media_volumes as $vs_label => $va_volume_info) {
			if (preg_match('!^'.$vs_base_dir.'!', $va_volume_info['absolutePath'])) {
				if (!self::createDirectoryPath($va_volume_info['absolutePath'])) {
					$this->addError("Couldn't create directory for media volume {$vs_label}");
					return false;
				}
			}
		}
		return true;
	}
	# --------------------------------------------------
	/**
	  * Loads CollectiveAccess schema into an empty database
	  *
	  * @param function $f_callback Function to be called for each SQL statement in the schema. Function is passed four parameters: the SQL code of the statement, the table name, the number of the table being loaded and the total number of tables.
	  * @return boolean Returns true on success, false if an error occurred
	  */
	public function loadSchema($f_callback=null){

		$vo_config = Configuration::load();
		$vo_dm = Datamodel::load();
		$vo_db = new Db();
		if (defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__') && __CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__ && ($this->opb_overwrite)) {
			$vo_db->query('DROP DATABASE IF EXISTS '.__CA_DB_DATABASE__);
			$vo_db->query('CREATE DATABASE '.__CA_DB_DATABASE__);
			$vo_db->query('USE '.__CA_DB_DATABASE__);
		}

		$va_ca_tables = $vo_dm->getTableNames();

		$qr_tables = $vo_db->query("SHOW TABLES");

		$vb_found_schema = false;
		while($qr_tables->nextRow()) {
			$vs_table = $qr_tables->getFieldAtIndex(0);
			if (in_array($vs_table, $va_ca_tables)) {
				$this->addError("Table ".$vs_table." already exists; have you already installed CollectiveAccess?");
				return false;
			}
		}

		// load schema

		if (!($vs_schema = file_get_contents(__CA_BASE_DIR__."/install/inc/schema_mysql.sql"))) {
			$this->addError("Could not open schema definition file");
			return false;
		}
		$va_schema_statements = explode(';', $vs_schema);
		
		$vn_num_tables = 0;
		foreach($va_schema_statements as $vs_statement) {
			if (!trim($vs_statement)) { continue; }
			if (preg_match('!create table!i', $vs_statement)) {
				$vn_num_tables++;
			}
		}
		
		$vn_i = 0;
		foreach($va_schema_statements as $vs_statement) {
			if (!trim($vs_statement)) { continue; }

			if ($f_callback && preg_match('!create[ ]+table[ ]+([A-Za-z0-9_]+)!i', $vs_statement, $va_matches)) {
				$vn_i++;
				if (file_exists(__CA_MODELS_DIR__.'/'.$va_matches[1].'.php')) {
					include_once(__CA_MODELS_DIR__.'/'.$va_matches[1].'.php');
					$vs_table = BaseModel::$s_ca_models_definitions[$va_matches[1]]['NAME_PLURAL'];
				} else {
					$vs_table = $va_matches[1];
				}
				$f_callback($vs_statement, $vs_table, $vn_i, $vn_num_tables);
			}
			$vo_db->query($vs_statement);
			if ($vo_db->numErrors()) {
				$this->addError("Error while loading the database schema: ".join("; ",$o_db->getErrors()));
				return false;
			}
		}
	}
	# --------------------------------------------------
	# PROFILE CONTENT PROCESSING
	# --------------------------------------------------
	public function processLocales(){
		require_once(__CA_MODELS_DIR__."/ca_locales.php");

		$t_locale = new ca_locales();
		$t_locale->setMode(ACCESS_WRITE);

		if($this->ops_base_name){
			$va_locales = array();
			foreach($this->opo_profile->locales->children() as $vo_locale){
				$va_locales[] = $vo_locale;
			}
			foreach($this->opo_base->locales->children() as $vo_locale){
				$va_locales[] = $vo_locale;
			}
		} else {
			$va_locales = $this->opo_profile->locales->children();
		}

		foreach($va_locales as $vo_locale){
			$vs_language = self::getAttribute($vo_locale, "lang");
			$vs_dialect = self::getAttribute($vo_locale, "dialect");
			$vs_country = self::getAttribute($vo_locale, "country");
			$vb_dont_use_for_cataloguing = self::getAttribute($vo_locale, "dontUseForCataloguing");
			
			if(isset($this->opa_locales[$vs_language."_".$vs_country])){ // don't insert duplicate locales
				continue;
			}
			$t_locale->set('name', (string)$vo_locale);
			$t_locale->set('country', $vs_country);
			$t_locale->set('language', $vs_language);
			if($vs_dialect) $t_locale->set('dialect', $vs_dialect);
			$t_locale->set('dont_use_for_cataloguing', (bool)$vb_dont_use_for_cataloguing);
			
			$t_locale->insert();

			if ($t_locale->numErrors()) {
				$this->addError("There was an error while inserting locale {$vs_language}_{$vs_country}: ".join(" ",$t_locale->getErrors()));
			}

			$this->opa_locales[$vs_language."_".$vs_country] = $t_locale->getPrimaryKey();
		}
		return true;
	}
	# --------------------------------------------------
	public function processLists($f_callback=null){
		require_once(__CA_MODELS_DIR__."/ca_lists.php");
		require_once(__CA_MODELS_DIR__."/ca_list_items.php");

		if($this->ops_base_name){ // "merge" profile and its base
			$va_lists = array();
			foreach($this->opo_base->lists->children() as $vo_list){
				$va_lists[self::getAttribute($vo_list, "code")] = $vo_list;
			}
			foreach($this->opo_profile->lists->children() as $vo_list){
				$va_lists[self::getAttribute($vo_list, "code")] = $vo_list;
			}
		} else {
			$va_lists = $this->opo_profile->lists->children();
		}

		$vn_i = 0;
		$vn_num_lists = sizeof($va_lists);
		foreach($va_lists as $vo_list){
			$t_list = new ca_lists();
			$t_list->setMode(ACCESS_WRITE);

			$vs_list_code = self::getAttribute($vo_list, "code");
			$vb_hierarchical = self::getAttribute($vo_list, "hierarchical");
			$vb_system = self::getAttribute($vo_list, "system");
			$vb_voc = self::getAttribute($vo_list, "vocabulary");
			$vn_def_sort = self::getAttribute($vo_list, "defaultSort");
			
			if ($f_callback) {
				$vn_i++;
				
				$f_callback($vs_list_code, $vn_i, $vn_num_lists);
			}

			$t_list->set("list_code",$vs_list_code);
			$t_list->set("is_system_list",intval($vb_system));
			$t_list->set("is_hierarchical",$vb_hierarchical);
			$t_list->set("use_as_vocabulary",$vb_voc);
			if($vn_def_sort) $t_list->set("default_sort",(int)$vn_def_sort);

			$t_list->insert();

			if ($t_list->numErrors()) {
				$this->addError("There was an error while inserting list {$vs_list_code}: ".join(" ",$t_list->getErrors()));
			} else {
				self::addLabelsFromXMLElement($t_list, $vo_list->labels, $this->opa_locales);
				if ($t_list->numErrors()) {
					$this->addError("There was an error while inserting list label for {$vs_list_code}: ".join(" ",$t_list->getErrors()));
				}
				if($vo_list->items){
					if(!$this->processListItems($t_list, $vo_list->items, null)){
						return false;
					}
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	private function processListItems($t_list, $po_items, $pn_parent_id){
		foreach($po_items->children() as $vo_item){
			$vs_item_value = self::getAttribute($vo_item, "value");
			$vs_item_idno = self::getAttribute($vo_item, "idno");
			$vs_type = self::getAttribute($vo_item, "type");
			$vs_status = self::getAttribute($vo_item, "status");
			$vs_access = self::getAttribute($vo_item, "access");
			$vs_rank = self::getAttribute($vo_item, "rank");
			$vn_enabled = self::getAttribute($vo_item, "enabled");
			$vn_default = self::getAttribute($vo_item, "default");

			if (!isset($vs_item_value) || !strlen(trim($vs_item_value))) {
				$vs_item_value = $vs_item_idno;
			}

			$vn_type_id = null;
			if ($vs_type) {
				$vn_type_id = $t_list->getItemIDFromList('list_item_types', $vs_type);
			}


			if (!isset($vs_status)) { $vs_status = 0; }
			if (!isset($vs_access)) { $vs_access = 0; }
			if (!isset($vs_rank)) { $vs_rank = 0; }

			$t_item = $t_list->addItem($vs_item_value, $vn_enabled, $vn_default, $pn_parent_id, $vn_type_id, $vs_item_idno, '', (int)$vs_status, (int)$vs_access, (int)$vs_rank);
			if ($t_list->numErrors()) {
				$this->addError("There was an error while inserting list item {$vs_item_idno}: ".join(" ",$t_list->getErrors()));
				return false;
			} else {
				$t_item->setMode(ACCESS_WRITE);
				self::addLabelsFromXMLElement($t_item, $vo_item->labels, $this->opa_locales);
				if ($t_item->numErrors()) {
					$this->addError("There was an error while inserting list item label for {$vs_item_idno}: ".join(" ",$t_item->getErrors()));
				}
			 }

			 if (isset($vo_item->items)) {
				if(!$this->processListItems($t_list, $vo_item->items, $t_item->getPrimaryKey())){
					return false;
				}
			 }
		}

		return true;
	}
	# --------------------------------------------------
	public function processMetadataElements(){
		require_once(__CA_MODELS_DIR__."/ca_lists.php");
		require_once(__CA_MODELS_DIR__."/ca_list_items.php");
		require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");

		$vo_dm = Datamodel::load();
		$t_rel_types = new ca_relationship_types();
		$t_list = new ca_lists();

		if($this->ops_base_name){ // "merge" profile and its base
			$va_elements = array();
			foreach($this->opo_base->elementSets->children() as $vo_element){
				$va_elements[self::getAttribute($vo_element, "code")] = $vo_element;
			}
			foreach($this->opo_profile->elementSets->children() as $vo_element){
				$va_elements[self::getAttribute($vo_element, "code")] = $vo_element;
			}
		} else {
			foreach($this->opo_profile->elementSets->children() as $vo_element){
				$va_elements[self::getAttribute($vo_element, "code")] = $vo_element;
			}
		}
		
		foreach($va_elements as $vs_element_code => $vo_element){
		
			if($vn_element_id = $this->processMetadataElement($vo_element, null)){
				// handle restrictions
				foreach($vo_element->typeRestrictions->children() as $vo_restriction){
					$vs_restriction_code = self::getAttribute($vo_restriction, "code");

					if (!($vn_table_num = $vo_dm->getTableNum((string)$vo_restriction->table))) {
						$this->addError("Invalid table specified for restriction $vs_restriction_code in element $vs_element_code");
						return false;
					}
					$t_instance = $vo_dm->getTableInstance((string)$vo_restriction->table);
					$vn_type_id = null;
					$vs_type = trim((string)$vo_restriction->type);

					// is this restriction further restricted on a specific type? -> get real id from code
					if (strlen($vs_type)>0) {
						// interstitial with type restriction -> code is relationship type code
						if($t_instance instanceof BaseRelationshipModel){
							$vn_type_id = $t_rel_types->getRelationshipTypeID($t_instance->tableName(),$vs_type);
						} else { // "normal" type restriction -> code is from actual type list
							$vs_type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());
							$vn_item_id = $t_list->getItemIDFromList($vs_type_list_name,$vs_type);
						}
					}

					// add restriction
					$t_restriction = new ca_metadata_type_restrictions();
					$t_restriction->setMode(ACCESS_WRITE);
					$t_restriction->set('table_num', $vn_table_num);
					$t_restriction->set('include_subtypes', (bool)$vo_restriction->includeSubtypes ? 1 : 0);
					$t_restriction->set('type_id', $vn_type_id);
					$t_restriction->set('element_id', $vn_element_id);
					
					$this->_processSettings($t_restriction, $vo_restriction->settings);
					$t_restriction->insert();

					if ($t_restriction->numErrors()) {
						$this->addError("There was an error while inserting type restriction {$vs_restriction_code} for metadata element {$vs_element_code}: ".join("; ",$t_restriction->getErrors()));
					}
				}
			}
		}
		return true;
	}
	# --------------------------------------------------
	private function processMetadataElement($po_element, $pn_parent_id){
		require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
		require_once(__CA_MODELS_DIR__."/ca_lists.php");

		if (($vn_datatype = ca_metadata_elements::getAttributeTypeCode(self::getAttribute($po_element, "datatype"))) === false) {
			return false; // should not happen due to XSD restrictions, but just in case
		}

		$vs_element_code = self::getAttribute($po_element, "code");

		$t_lists = new ca_lists();
		$t_md_element = new ca_metadata_elements();
		$t_md_element->setMode(ACCESS_WRITE);
		$t_md_element->set('element_code', $vs_element_code);
		$t_md_element->set('parent_id', $pn_parent_id);
		$t_md_element->set('documentation_url',(string)$po_element->documentationUrl);
		$t_md_element->set('datatype', $vn_datatype);

		$vs_list = self::getAttribute($po_element, "list");

		if (isset($vs_list) && $vs_list && $t_lists->load(array('list_code' => $vs_list))) {
			$vn_list_id = $t_lists->getPrimaryKey();
		} else {
			$vn_list_id = null;
		}
		$t_md_element->set('list_id', $vn_list_id);
		$this->_processSettings($t_md_element, $po_element->settings);

		$t_md_element->insert();

		if ($t_md_element->numErrors()) {
			$this->addError("There was an error while inserting metadata element {$ps_element_code}: ".join(" ",$t_md_element->getErrors()));
			return false;
		}

		$vn_element_id = $t_md_element->getPrimaryKey();

		// add element labels
		self::addLabelsFromXMLElement($t_md_element, $po_element->labels, $this->opa_locales);

		if ($po_element->elements) {
			foreach($po_element->elements->children() as $vo_child) {
				$this->processMetadataElement($vo_child, $vn_element_id);
			}
		}

		return $vn_element_id;
	}
	# --------------------------------------------------
	public function processUserInterfaces(){
		require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
		require_once(__CA_MODELS_DIR__."/ca_editor_ui_screens.php");
		require_once(__CA_MODELS_DIR__."/ca_lists.php");
		require_once(__CA_MODELS_DIR__."/ca_list_items.php");

		$vo_dm = Datamodel::load();

		$t_list = new ca_lists();
		$t_list_item = new ca_list_items();

		if($this->ops_base_name){ // "merge" profile and its base
			$va_uis = array();
			foreach($this->opo_base->userInterfaces->children() as $vo_ui){
				$va_uis[self::getAttribute($vo_ui, "code")] = $vo_ui;
			}
			foreach($this->opo_profile->userInterfaces->children() as $vo_ui){
				$va_uis[self::getAttribute($vo_ui, "code")] = $vo_ui;
			}
		} else {
			foreach($this->opo_profile->userInterfaces->children() as $vo_ui){
				$va_uis[self::getAttribute($vo_ui, "code")] = $vo_ui;
			}
		}

		foreach($va_uis as $vs_ui_code => $vo_ui) {
			$vs_type = self::getAttribute($vo_ui, "type");
			if (!($vn_type = $vo_dm->getTableNum($vs_type))) {
				$this->addError("Invalid type {$vs_type} for UI code {$vs_ui_code}");
				return false;
			}
			// create ui row
			$t_ui = new ca_editor_uis();
			$t_ui->setMode(ACCESS_WRITE);
			$t_ui->set('user_id', null);
			$t_ui->set('is_system_ui', 1);
			$t_ui->set('editor_code', $vs_ui_code);
			$t_ui->set('editor_type', $vn_type);
			$t_ui->insert();

			if ($t_ui->numErrors()) {
				$this->addError("Errors inserting UI {$vs_ui_code}: ".join("; ",$t_ui->getErrors()));
				return false;
			}

			$vn_ui_id = $t_ui->getPrimaryKey();

			self::addLabelsFromXMLElement($t_ui, $vo_ui->labels, $this->opa_locales);

			// create ui type restrictions
			if($vo_ui->typeRestrictions){
				foreach($vo_ui->typeRestrictions->children() as $vo_restriction){
					$vs_restriction_type = self::getAttribute($vo_restriction, "type");

					$t_instance = $vo_dm->getInstanceByTableNum($vn_type);
					$vs_type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());

					if(strlen($vs_restriction_type)>0){
						$vn_item_id = $t_list->getItemIDFromList($vs_type_list_name,$vs_restriction_type);
						if($vn_item_id){
							 $t_ui->addTypeRestriction($vn_item_id);	
						}
					}
				}
			}

			// create ui screens
			$t_ui_screens = new ca_editor_ui_screens();
			foreach($vo_ui->screens->children() as $vo_screen) {
				$vs_screen_idno = self::getAttribute($vo_screen, "idno");
				$vn_is_default = self::getAttribute($vo_screen, "default");

				$t_ui_screens = new ca_editor_ui_screens();
				$t_ui_screens->setMode(ACCESS_WRITE);
				$t_ui_screens->set("idno",$vs_screen_idno);
				$t_ui_screens->set('parent_id', null);
				$t_ui_screens->set('ui_id', $vn_ui_id);
				$t_ui_screens->set('is_default', $vn_is_default);
				$t_ui_screens->insert();

				if ($t_ui_screens->numErrors()) {
					$this->addError("Errors inserting UI screen {$vs_screen_idno} for UI {$vs_ui_code}: ".join("; ",$t_ui_screens->getErrors()));
					return false;
				}

				$vn_screen_id = $t_ui_screens->getPrimaryKey();

				self::addLabelsFromXMLElement($t_ui_screens, $vo_screen->labels, $this->opa_locales);

				$va_available_bundles = $t_ui_screens->getAvailableBundles($pn_type,array('dontCache' => true));

				// create ui bundle placements
				foreach($vo_screen->bundlePlacements->children() as $vo_placement) {
					$vs_placement_code = self::getAttribute($vo_placement, "code");
					$vs_bundle = trim((string)$vo_placement->bundle);
					
					$va_settings = $this->_processSettings(null, $vo_placement->settings);

					$t_ui_screens->addPlacement($vs_bundle, $vs_placement_code, $va_settings, null, array('additional_settings' => $va_available_bundles[$vs_bundle]['settings']));
				}

				// create ui screen type restrictions
				if($vo_screen->typeRestrictions){
					foreach($vo_screen->typeRestrictions->children() as $vo_restriction){
						$vs_restriction_type = self::getAttribute($vo_restriction, "type");

						$t_instance = $vo_dm->getInstanceByTableNum($vn_type);
						$vs_type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());

						if(strlen($vs_restriction_type)>0){
							$vn_item_id = $t_list->getItemIDFromList($vs_type_list_name,$vs_restriction_type);
							if($vn_item_id){
								$t_ui_screens->addTypeRestriction($vn_item_id);
							}
						}
					}
				}
			}
		}
		return true;
	}
	# --------------------------------------------------
	public function processRelationshipTypes() {
		require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");

		if($this->ops_base_name){ // "merge" profile and its base
			$va_rel_tables = array();
			foreach($this->opo_base->relationshipTypes->children() as $vo_rel_table){
				$va_rel_tables[self::getAttribute($vo_rel_table, "name")] = $vo_rel_table;
			}
			foreach($this->opo_profile->relationshipTypes->children() as $vo_rel_table){
				$va_rel_tables[self::getAttribute($vo_rel_table, "name")] = $vo_rel_table;
			}
		} else {
			foreach($this->opo_profile->relationshipTypes->children() as $vo_rel_table){
				$va_rel_tables[self::getAttribute($vo_rel_table, "name")] = $vo_rel_table;
			}
		}

		$ca_db = new Db('',null, false);
                $lists_result = $ca_db->query(" SELECT * FROM ca_lists");

		$list_names = array();
                $va_list_item_ids = array();
                while($lists_result->nextRow()) {
                        $list_names[$lists_result->get('list_id')] = $lists_result->get('list_code');
                }

                // get list items
                $list_items_result = $ca_db->query(" SELECT * FROM ca_list_items cli INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id ");
                while($list_items_result->nextRow()) {
                        $list_type_code = $list_names[$list_items_result->get('list_id')];
                        $va_list_item_ids[$list_type_code][$list_items_result->get('item_value')] = $list_items_result->get('item_id');
                }

		$vo_dm = Datamodel::load();
		
		$t_rel_type = new ca_relationship_types();
		$t_rel_type->setMode(ACCESS_WRITE);

		foreach($va_rel_tables as $vs_table => $vo_rel_table) {
			$vn_table_num = $vo_dm->getTableNum($vs_table);

			$t_rel_table = $vo_dm->getTableInstance($vs_table);

			if (!method_exists($t_rel_table, 'getLeftTableName')) {
				continue;
			}
			$vs_left_table = $t_rel_table->getLeftTableName();
			$vs_right_table = $t_rel_table->getRightTableName();

			// create relationship type root
			$t_rel_type->set('parent_id', null);
			$t_rel_type->set('type_code', 'root_for_'.$vn_table_num);
			$t_rel_type->set('sub_type_left_id', null);
			$t_rel_type->set('sub_type_right_id', null);
			$t_rel_type->set('table_num', $vn_table_num);
			$t_rel_type->set('rank', 10);
			$t_rel_type->set('is_default', 0);

			$t_rel_type->insert();

			if ($t_rel_type->numErrors()) {
				$this->addError("Errors inserting relationship root for {$vs_table}: ".join("; ",$t_rel_type->getErrors()));
				return false;
			}

			$vn_parent_id = $t_rel_type->getPrimaryKey();

			$this->processRelationshipTypesForTable($vo_rel_table->types, $vn_table_num, $vs_left_table, $vs_right_table, $vn_parent_id, $va_list_item_ids);
		}
		return true;
	}
	# --------------------------------------------------
	private function processRelationshipTypesForTable($po_relationship_types, $pn_table_num, $ps_left_table, $ps_right_table, $pn_parent_id, $pa_list_item_ids){
		$o_dm = Datamodel::load();

		$t_rel_type = new ca_relationship_types();
		$t_rel_type->setMode(ACCESS_WRITE);


		$vn_rank_default = (int)$t_rel_type->getFieldInfo('rank', 'DEFAULT');
		foreach($po_relationship_types->children() as $vo_type) {
			$vs_type_code = self::getAttribute($vo_type, "code");
			$vn_default = self::getAttribute($vo_type, "default");
			$vn_rank = (int)self::getAttribute($vo_type, "rank");

			$t_rel_type->set('table_num', $pn_table_num);
			$t_rel_type->set('type_code', $vs_type_code);
			$t_rel_type->set("parent_id", $pn_parent_id);
			
			if ($vn_rank > 0) {
				$t_rel_type->set("rank", $vn_rank);
			} else {
				$t_rel_type->set("rank", $vn_rank_default);
			}

			$t_rel_type->set('sub_type_left_id', null);
			$t_rel_type->set('sub_type_right_id', null);

			if (trim($vs_left_subtype_code = (string) $vo_type->subTypeLeft)) {
				$t_obj = $o_dm->getTableInstance($ps_left_table);
				$vs_list_code = $t_obj->getFieldListCode($t_obj->getTypeFieldName());

				if (isset($pa_list_item_ids[$vs_list_code][$vs_left_subtype_code])) {
					$t_rel_type->set('sub_type_left_id', $pa_list_item_ids[$vs_list_code][$vs_left_subtype_code]);
				}
			}
			if (trim($vs_right_subtype_code = (string) $vo_type->subTypeRight)) {
				$t_obj = $o_dm->getTableInstance($ps_right_table);
				$vs_list_code = $t_obj->getFieldListCode($t_obj->getTypeFieldName());
				if (isset($pa_list_item_ids[$vs_list_code][$vs_right_subtype_code])) {
					$t_rel_type->set('sub_type_right_id', $pa_list_item_ids[$vs_list_code][$vs_right_subtype_code]);
				}
			}

			$t_rel_type->set('is_default', $vn_default ? 1 : 0);
			$t_rel_type->insert();

			if ($t_rel_type->numErrors()) {
				$this->addError("Errors inserting relationship {$vs_type_code}: ".join("; ",$t_rel_type->getErrors()));
				return false;
			}


			self::addLabelsFromXMLElement($t_rel_type, $vo_type->labels, $this->opa_locales);

			if ($vo_type->types) {
				$this->processRelationshipTypesForTable($vo_type->types, $pn_table_num, $ps_left_table, $ps_right_table, $t_rel_type->getPrimaryKey(), $pa_list_item_ids);
			}
		}
	}
	# --------------------------------------------------
	public function processRoles(){
		require_once(__CA_MODELS_DIR__."/ca_user_roles.php");

		if($this->ops_base_name){ // "merge" profile and its base
			$va_roles = array();
			if($this->opo_base->roles){
				foreach($this->opo_base->roles->children() as $vo_role){
					$va_roles[self::getAttribute($vo_role, "code")] = $vo_role;
				}
			}
			if($this->opo_profile->roles){
				foreach($this->opo_profile->roles->children() as $vo_role){
					$va_roles[self::getAttribute($vo_role, "code")] = $vo_role;
				}
			}
		} else {
			if($this->opo_profile->roles){
				foreach($this->opo_profile->roles->children() as $vo_role){
					$va_roles[self::getAttribute($vo_role, "code")] = $vo_role;
				}
			}
		}

		$t_role = new ca_user_roles();
		$t_role->setMode(ACCESS_WRITE);

		foreach($va_roles as $vs_role_code => $vo_role) {
			$t_role->set('name', trim((string) $vo_role->name));
			$t_role->set('description', trim((string) $vo_role->description));
			$t_role->set('code', $vs_role_code);
			$va_actions = array();
			if($vo_role->actions){
				foreach($vo_role->actions->children() as $vo_action){
					$va_actions[] = trim((string) $vo_action);
				}
			}
			$t_role->setRoleActions($va_actions);
			$t_role->insert();

			if ($t_role->numErrors()) {
				$this->addError("Errors inserting access role {$vs_role_code}: ".join("; ",$t_role->getErrors()));
				return false;
			}
		}
		return true;
	}
	# --------------------------------------------------
	public function processDisplays(){
		require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
		require_once(__CA_MODELS_DIR__."/ca_bundle_display_placements.php");
		require_once(__CA_MODELS_DIR__."/ca_bundle_display_type_restrictions.php");
		
		$o_config = Configuration::load();

		$vo_dm = Datamodel::load();

		if($this->ops_base_name){ // "merge" profile and its base
			$va_displays = array();
			if($this->opo_base->displays) {
				foreach($this->opo_base->displays->children() as $vo_display){
					$va_displays[self::getAttribute($vo_display, "code")] = $vo_display;
				}
			}
			
			if($this->opo_profile->displays) {
				foreach($this->opo_profile->displays->children() as $vo_display){
					$va_displays[self::getAttribute($vo_display, "code")] = $vo_display;
				}
			}
		} else {
			if($this->opo_profile->displays){
				foreach($this->opo_profile->displays->children() as $vo_display){
					$va_displays[self::getAttribute($vo_display, "code")] = $vo_display;
				}
			}
		}
		
		if(!is_array($va_displays) || sizeof($va_displays) == 0) return true;

		foreach($va_displays as $vo_display){
			$vs_display_code = self::getAttribute($vo_display, "code");
			$vb_system = self::getAttribute($vo_display, "system");
			$vs_table = self::getAttribute($vo_display, "type");
			
			if ($o_config->get($vs_table.'_disable')) { continue; }
			
			$t_display = new ca_bundle_displays();
			$t_display->setMode(ACCESS_WRITE);

			$t_display->set("display_code", $vs_display_code);
			$t_display->set("is_system",$vb_system);
			$t_display->set("table_num",$vo_dm->getTableNum($vs_table));
			$t_display->set("user_id", 1);		// let administrative user own these
			
			$this->_processSettings($t_display, $vo_display->settings);

			$t_display->insert();

			if ($t_display->numErrors()) {
				$this->addError("There was an error while inserting display {$vs_display_code}: ".join(" ",$t_display->getErrors()));
			} else {
				self::addLabelsFromXMLElement($t_display, $vo_display->labels, $this->opa_locales);
				if ($t_display->numErrors()) {
					$this->addError("There was an error while inserting display label for {$vs_display_code}: ".join(" ",$t_display->getErrors()));
				}
				if(!$this->processDisplayPlacements($t_display, $vo_display->bundlePlacements, null)){
					return false;
				}
			}
			
			if ($vo_display->typeRestrictions) {
				foreach($vo_display->typeRestrictions->children() as $vo_restriction){
					$t_list = new ca_lists();
					$t_list_item = new ca_list_items();
					$vs_restriction_code = trim((string)self::getAttribute($vo_restriction, "code"));
					$vs_type = trim((string)self::getAttribute($vo_restriction, "type"));
					
					$t_instance = $vo_dm->getInstanceByTableNum($vn_table_num = $vo_dm->getTableNum($vs_table));
					$vs_type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());
					if ($vs_type) {
						$t_list->load(array('list_code' => $vs_type_list_name));
						$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'idno' => $vs_type));
					}
					$t_restriction = new ca_bundle_display_type_restrictions();
					$t_restriction->setMode(ACCESS_WRITE);
					$t_restriction->set('table_num', $vn_table_num);
					$t_restriction->set('include_subtypes', (bool)$vo_restriction->includeSubtypes ? 1 : 0);
					$t_restriction->set('type_id', ($vs_type) ? $t_list_item->getPrimaryKey(): null);
					$t_restriction->set('display_id', $t_display->getPrimaryKey());
				
					$this->_processSettings($t_restriction, $vo_restriction->settings);
					$t_restriction->insert();

					if ($t_restriction->numErrors()) {
						$this->addError("There was an error while inserting type restriction {$vs_restriction_code} in display {$vs_display_code}: ".join("; ",$t_restriction->getErrors()));
					}
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	private function processDisplayPlacements($t_display, $po_placements){
		$o_config = Configuration::load();
		$va_available_bundles = $t_display->getAvailableBundles(null, array('no_cache' => true));
		
		$vn_i = 1;
		foreach($po_placements->children() as $vo_placement){
			$vs_code = self::getAttribute($vo_item, "code");
			$vs_bundle = (string)$vo_placement->bundle;

			$va_settings = $this->_processSettings(null, $vo_placement->settings);
			$vn_placement_id = $t_display->addPlacement($vs_bundle, $va_settings, $vn_i, array('additional_settings' => $va_available_bundles[$vs_bundle]['settings']));
			if ($t_display->numErrors()) {
				$this->addError("There was an error while inserting display placement {$vs_code}: ".join(" ",$t_display->getErrors()));
				return false;
			}
			$vn_i++;
		}

		return true;
	}
	# --------------------------------------------------
	public function processSearchForms(){
		require_once(__CA_MODELS_DIR__."/ca_search_forms.php");
		require_once(__CA_MODELS_DIR__."/ca_search_form_placements.php");

		$o_config = Configuration::load();
		$vo_dm = Datamodel::load();

		if($this->ops_base_name){ // "merge" profile and its base
			$va_forms = array();
			if($this->opo_base->searchForms) {
				foreach($this->opo_base->searchForms->children() as $vo_form){
					$va_forms[self::getAttribute($vo_form, "code")] = $vo_form;
				}
			}
			
			if($this->opo_profile->searchForms) {
				foreach($this->opo_profile->searchForms->children() as $vo_form){
					$va_forms[self::getAttribute($vo_form, "code")] = $vo_form;
				}
			}
		} else {
			if($this->opo_profile->searchForms){
				foreach($this->opo_profile->searchForms->children() as $vo_form){
					$va_forms[self::getAttribute($vo_form, "code")] = $vo_form;
				}
			}
		}
		
		if(!is_array($va_forms) || sizeof($va_forms) == 0) return true;

		foreach($va_forms as $vo_form){
			$vs_form_code = self::getAttribute($vo_form, "code");
			$vb_system = self::getAttribute($vo_form, "system");
			$vs_table = self::getAttribute($vo_form, "type");
			if (!($t_instance = $vo_dm->getInstanceByTableName($vs_table, true))) { continue; }
			if (method_exists($t_instance, 'getTypeList') && !sizeof($t_instance->getTypeList())) { continue; } // no types configured
			if ($o_config->get($vs_table.'_disable')) { continue; }
			
			$t_form = new ca_search_forms();
			$t_form->setMode(ACCESS_WRITE);
			$t_form->set("form_code", (string)$vs_form_code);
			$t_form->set("is_system", (int)$vb_system);
			$t_form->set("table_num", (int)$vo_dm->getTableNum($vs_table));
			$t_form->set("user_id", 1);		// let administrative user own these
			
			$va_settings = $this->_processSettings($t_form, $vo_form->settings);

			$t_form->insert();

			if ($t_form->numErrors()) {
				$this->addError("There was an error while inserting search form {$vs_form_code}: ".join(" ",$t_form->getErrors()));
			} else {
				self::addLabelsFromXMLElement($t_form, $vo_form->labels, $this->opa_locales);
				if ($t_form->numErrors()) {
					$this->addError("There was an error while inserting search form label for {$vs_form_code}: ".join(" ",$t_form->getErrors()));
				}
				if(!$this->processSearchFormPlacements($t_form, $vo_form->bundlePlacements, null)){
					return false;
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	private function processSearchFormPlacements($t_form, $po_placements){
		$va_available_bundles = $t_form->getAvailableBundles();
		$vs_bundle = (string)$vo_placement->bundle;
		
		$vn_i = 0;
		foreach($po_placements->children() as $vo_placement){
			$vs_code = self::getAttribute($vo_item, "code");
			$vs_bundle = (string)$vo_placement->bundle;

			$va_settings = $this->_processSettings(null, $vo_placement->settings);

			$vn_placement_id = $t_form->addPlacement($vs_bundle, $va_settings, $vn_i, array('additional_settings' => $va_available_bundles[$vs_bundle]['settings']));
			if ($t_form->numErrors()) {
				$this->addError("There was an error while inserting search form placement {$vs_code}: ".join(" ",$t_form->getErrors()));
				return false;
			}
			$vn_i++;
		}

		return true;
	}
	# --------------------------------------------------
	public function processGroups(){
		require_once(__CA_MODELS_DIR__."/ca_user_groups.php");

		// Create root group		
		$t_user_group = new ca_user_groups();
		$t_user_group->setMode(ACCESS_WRITE);
		$t_user_group->set('name', 'Root');
		$t_user_group->set('code', 'Root');
		$t_user_group->set('parent_id', null);
		$t_user_group->insert();
		
		if ($t_user_group->numErrors()) {
			$this->addError("Errors creating root user group {$vs_group_code}: ".join("; ",$t_user_group->getErrors()));
			return false;
		}
		if($this->ops_base_name){ // "merge" profile and its base
			$va_groups = array();
			if($this->opo_base->groups){
				foreach($this->opo_base->groups->children() as $vo_group){
					$va_groups[self::getAttribute($vo_group, "code")] = $vo_group;
				}
			}
			if($this->opo_profile->groups){
				foreach($this->opo_profile->groups->children() as $vo_group){
					$va_groups[self::getAttribute($vo_group, "code")] = $vo_group;
				}
			}
		} else {
			if($this->opo_profile->groups){
				foreach($this->opo_profile->groups->children() as $vo_group){
					$va_groups[self::getAttribute($vo_group, "code")] = $vo_group;
				}
			}
		}

		$t_group = new ca_user_groups();
		$t_group->setMode(ACCESS_WRITE);
		if (is_array($va_groups)) {
			foreach($va_groups as $vs_group_code => $vo_group) {
				$t_group->set('name', trim((string) $vo_group->name));
				$t_group->set('description', trim((string) $vo_group->description));
				$t_group->set('code', $vs_group_code);
				$t_group->set('parent_id', null);
				$t_group->insert();
	
				$va_roles = array();
	
				if($vo_group->roles){
					foreach($vo_group->roles->children() as $vo_role){
						$va_roles[] = trim((string) $vo_role);
					}
				}
	
				$t_group->addRoles($va_roles);
	
				if ($t_group->numErrors()) {
					$this->addError("Errors inserting user group {$vs_group_code}: ".join("; ",$t_group->getErrors()));
					return false;
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	public function processLogins(){
		require_once(__CA_MODELS_DIR__."/ca_users.php");

		if($this->ops_base_name){ // "merge" profile and its base
			$va_logins = array();
			if($this->opo_base->logins){
				foreach($this->opo_base->logins->children() as $vo_login){
					$vs_logins[self::getAttribute($vo_login, "user_name")] = $vo_login;
				}
			}
			if($this->opo_profile->logins){
				foreach($this->opo_profile->logins->children() as $vo_login){
					$va_logins[self::getAttribute($vo_login, "user_name")] = $vo_login;
				}
			}
		} else {
			if($this->opo_profile->logins){
				foreach($this->opo_profile->logins->children() as $vo_login){
					$va_logins[self::getAttribute($vo_login, "user_name")] = $vo_login;
				}
			}
		}
		
		// If no logins are defined in the profile create an admin login with random password
		if (!sizeof($va_logins)) {
			$vs_password = $this->createAdminAccount();
			return array('administrator' => $vs_password);
		}

		$va_login_info = array();

		foreach($va_logins as $vs_user_name => $vo_login) {
			if (!($vs_password = trim((string) self::getAttribute($vo_login, "password")))) {
				$vs_password = $this->getRandomPassword();
			}
			
			$t_user = new ca_users();
			$t_user->setMode(ACCESS_WRITE);
			$t_user->set('user_name', $vs_user_name = trim((string) self::getAttribute($vo_login, "user_name")));
			$t_user->set('password', $vs_password);
			$t_user->set('fname',  trim((string) self::getAttribute($vo_login, "fname")));
			$t_user->set('lname',  trim((string) self::getAttribute($vo_login, "lname")));
			$t_user->set('email',  trim((string) self::getAttribute($vo_login, "email")));
			$t_user->set('active', 1);
			$t_user->set('userclass', 0);
			$t_user->insert();

			$va_roles = array();
			if($vo_login->role){
				foreach($vo_login->role as $vo_role){
					$va_roles[] = trim((string) self::getAttribute($vo_role, "code"));
				}
			}
			if (sizeof($va_roles)) { $t_user->addRoles($va_roles); }
			
			
			$va_groups = array();
			if($vo_login->group){
				foreach($vo_login->group as $vo_group){
					$va_groups[] = trim((string) self::getAttribute($vo_group, "code"));
				}
			}
			if (sizeof($va_groups)) { $t_user->addToGroups($va_groups); }

			if ($t_user->numErrors()) {
				$this->addError("Errors adding login {$vs_user_name}: ".join("; ",$t_user->getErrors()));
				return false;
			}
			
			$va_login_info[$vs_user_name] = $vs_password;
		}

		return $va_login_info;
	}
	# --------------------------------------------------
	public function processMiscHierarchicalSetup() {
		require_once(__CA_MODELS_DIR__."/ca_storage_locations.php");
		
		#
		# Create roots for storage locations hierarchies
		#
		$t_storage_location = new ca_storage_locations();
		$t_storage_location->setMode(ACCESS_WRITE);
		$t_storage_location->set('status', 0);
		$t_storage_location->set('parent_id', null);
		$t_storage_location->insert();
		
		if ($t_storage_location->numErrors()) {
			$this->addError("Errors inserting the storage location root: ".join("; ",$t_storage_location->getErrors()));
			return;
		}
	}
	# --------------------------------------------------
	public function createAdminAccount(){
		require_once(__CA_MODELS_DIR__."/ca_users.php");

		$ps_password = $this->getRandomPassword();
		$t_user = new ca_users();
		$t_user->setMode(ACCESS_WRITE);
		$t_user->set("user_name", 'administrator');
		$t_user->set("password", $ps_password);
		$t_user->set("email", $this->ops_admin_email);
		$t_user->set("fname", 'CollectiveAccess');
		$t_user->set("lname", 'Administrator');
		$t_user->set("userclass", 0);
		$t_user->set("active", 1);
		$t_user->insert();

		if ($t_user->numErrors()) {
			$this->addError("Errors while adding the default administrator account: ".join("; ",$t_user->getErrors()));
			return false;
		}

		return $ps_password;
	}
	# --------------------------------------------------
	private function _processSettings($pt_instance, $po_settings_node) {
		$va_settings = array();
		if($po_settings_node){ 
			foreach($po_settings_node->children() as $vo_setting) {
				$vs_locale = self::getAttribute($vo_setting, "locale");
				$vn_locale_id = $this->opa_locales[$vs_locale];
				$vs_setting_name = self::getAttribute($vo_setting, "name");
				$vs_option = self::getAttribute($vo_setting, "option");
				$vs_value = (string) $vo_setting;
				
				if ($vn_locale_id) { 
					$va_settings[$vs_setting_name][$vs_locale] = $vs_value;
				} else {
					if (!isset($va_settings[$vs_setting_name])) {
						$va_settings[$vs_setting_name] = $vs_value;
					} else {
						if (!is_array($va_settings[$vs_setting_name])) {
							$va_settings[$vs_setting_name] = array($va_settings[$vs_setting_name]);
						}
						$va_settings[$vs_setting_name][] = $vs_value;
					}
				}
			}
			
			if (is_object($pt_instance)) {
				foreach($va_settings as $vs_setting_name => $vm_setting_value) {
					$pt_instance->setSetting($vs_setting_name, $vm_setting_value);
				}
			}
		}
		return $va_settings;
	}
	# --------------------------------------------------
}
?>
