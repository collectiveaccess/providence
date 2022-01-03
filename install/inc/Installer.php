<?php
/* ----------------------------------------------------------------------
 * install/inc/Installer.php : class that wraps installer functionality
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2021 Whirl-i-Gig
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

namespace Installer;

require_once(__CA_LIB_DIR__.'/Media/MediaVolumes.php');
require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch.php');
require_once(__CA_APP_DIR__.'/helpers/configurationHelpers.php');

class Installer {
	# --------------------------------------------------
	protected $errors;
	protected $debug;
	protected $profile_debug = "";
	
	protected $profile_dir;
	protected $profile_name;

	protected $admin_email;
	protected $overwrite;
	
	/** @var  bool */
	protected $logging_status = false;
	/** @var KLogger */
	protected $log;
	
	/** @var  SimpleXMLElement */
	protected $profile;
	/** @var  SimpleXMLElement */
	protected $base;
	/** @var  string */
	protected $base_name;
	
	/** @var array  */
	protected $locales;
	
	/** 
	 * Parsed profile data for insertion
	 */
	protected $parsed_data = [];
	
	/**
	 * @var Db
	 */
	protected $db;
	
	/**
	 * @var array
	 */
	protected $metadata_element_deferred_settings_processing = [];
	
	/**
	 * Application configuration
	 *
	 * @var Configuration
	 */
	protected $config; 
	# --------------------------------------------------
	/**
	 * Constructor
	 *
	 * @param string $profile_dir path to a directory containing profiles and XML schema
	 * @param string $profile_name of the profile, as in <$profile_dir>/<$profile_name>.xml
	 * @param string $admin_email e-mail address for the initial administrator account
	 * @param boolean $overwrite overwrite existing install? optional, defaults to false
	 * @param boolean $debug enable or disable debugging mode
	 * @param boolean $skip_load dont actually load profile (useful if you want to fill in some gaps by hand)
	 * @param boolean $log_output log output using Klogger
	 */
	public function  __construct(string $directory, string $profile, ?string $admin_email=null, ?bool $overwrite=false, ?bool $debug=false, ?bool $skip_load=false, ?bool $log_output=false) {
		$this->profile_dir = $directory;
		$this->profile_name = $profile;
		$this->admin_email = $admin_email;
		$this->overwrite = $overwrite;
		$this->debug = $debug;
		
		$this->config = \Configuration::load();

		$this->locales = [];

		$this->db = new \Db();
		
		// Process selected profile into data structure for insertion
		$data = $this->parseProfile($directory, $profile);
		
		if(!$skip_load) {
			if(!is_array($data)) {
				// TODO: get error from parser
				$this->addError("Could not load profile.");
				return false;
			}
			$this->parsed_data = $data;
		}

		if($log_output) {
			require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');
			// @todo make this configurable or get from app.conf?
			$this->log = new \KLogger(__CA_BASE_DIR__ . '/app/log', \KLogger::DEBUG);
			$this->logging_status = true;
		}
	}
	# --------------------------------------------------
	/**
	 * Parse a profile, returning a data structure will profile content ready for insertion into the database
	 *
	 * @param string $directory path to a directory containing profiles
	 * @param string $profile Name of the profile, with or without file extension
	 *
	 * @return array
	 */
	public function parseProfile(string $directory, string $profile) : ?array {
		if(!($path = \caGetProfilePath($directory, $profile))) {
			return null;
		}
		
		if(!($parser = self::profileParser($path))) { return null; }
		
		return $parser->parse($directory, $profile);
	}
	# --------------------------------------------------
	/**
	 * Return metadata (name, description) for a profile
	 *
	 * @param string $directory path to a directory containing profiles
	 * @param string $profile Name of the profile, with or without file extension
	 *
	 * @return array
	 */
	static public function getProfileInfo(string $directory, string $profile) : ?array {
		if(!($path = \caGetProfilePath($directory, $profile))) {
			return null;
		}
		
		if(!($parser = self::profileParser($path))) { return null; }
		
		return $parser->profileInfo($path);
	}
	# --------------------------------------------------
	/**
	 * Return instance of profile parser for selected file
	 *
	 * @param string $profile_path path to a profile file
	 *
	 * @return array
	 */
	static public function profileParser(string $profile_path) : ?\Installer\Parsers\BaseProfileParser {
		$extension = strtolower(pathinfo($profile_path, PATHINFO_EXTENSION));
		
		switch($extension) {
			case 'xlsx':
				require_once(__CA_BASE_DIR__.'/install/inc/Parsers/XLSXProfileParser.php');
				return new \Installer\Parsers\XLSXProfileParser();
				break;
			case 'xml':
				require_once(__CA_BASE_DIR__.'/install/inc/Parsers/XMLProfileParser.php');
				return new \Installer\Parsers\XMLProfileParser();
				break;
		}
		return null;
	}
	# --------------------------------------------------
	# ERROR HANDLING / DEBUGGING
	# --------------------------------------------------
	/**
	 *
	 */
	protected function addError($error) {
		$this->logStatus($error);
		$this->errors[] = $error;
	}
	# --------------------------------------------------
	/**
	 * Returns number of errors that occurred while processing
	 *
	 * @return int number of errors
	 */
	public function numErrors() {
		return is_array($this->errors) ? sizeof($this->errors) : 0;
	}
	# --------------------------------------------------
	/**
	 * Returns array of error messages
	 *
	 * @return array errors
	 */
	public function getErrors() {
		return $this->errors;
	}
	# --------------------------------------------------
	/**
	 * Get profile debug info. Only has content if debug mode is enabled.
	 * WARNING: can lead to very verbose output, especially if the php
	 * extension xdebug is installed and enabled.
	 *
	 * @return string profile debug info
	 */
	public function getProfileDebugInfo() {
		return $this->profile_debug;
	}
	# --------------------------------------------------
	# UTILITIES
	# --------------------------------------------------
	/**
	 *
	 */
	private static function createDirectoryPath($path) {
		if (!file_exists($path)) {
			if (!@mkdir($path, 0777, true)) {
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}
	# --------------------------------------------------
	/**
	 * 
	 *
	 * @param LabelableBaseModelWithAttributes $t_instance
	 * @param bool $force_preferred
	 * @return bool
	 */
	protected static function addLabels($t_instance, $labels, $force_preferred=false) {
		require_once(__CA_LIB_DIR__."/LabelableBaseModelWithAttributes.php");

		if(!($t_instance instanceof \LabelableBaseModelWithAttributes)) {
			return false;
		}
		
		/** @var LabelableBaseModelWithAttributes $t_instance */
		if (!$labels || !is_array($labels) || !sizeof($labels)) {
			$t_instance->addLabel(array($t_instance->getLabelDisplayField() => "???"), \ca_locales::getDefaultCataloguingLocaleID(), false, true);
			return true; 
		}

		$old_label_ids = array_flip($t_instance->getLabelIDs());

		foreach($labels as $label) {
			$label_values = [];
			$locale = $label["locale"];
			$locale_id = \ca_locales::codeToID($locale);

			$preferred = $label["preferred"];
			if($force_preferred || (bool)$preferred || is_null($preferred)) {
				$preferred = true;
			} else {
				$preferred = false;
			}

			foreach($label as $name => $value) {
				$label_values[$name] = (string) $value;
			}
			$existing_labels = $preferred ? $t_instance->getPreferredLabels([$locale_id]) : $t_instance->getNonPreferredLabels([$locale_id]);
			if(
				is_array($existing_labels) &&
				(sizeof($existing_labels) > 0) &&
				($label_id = $existing_labels[(int)$t_instance->getPrimaryKey()][(int)$locale_id][0]['label_id'])
			) {
				$label_id = $t_instance->editLabel($label_id, $label_values, $locale_id, null, $preferred);
			} else {
				$label_id = $t_instance->addLabel($label_values, $locale_id, false, $preferred);
			}

			unset($old_label_ids[$label_id]);
		}

		// remove all old labels that are not present in the XML!
		foreach($old_label_ids as $label_id => $_) {
			$t_instance->removeLabel($label_id);
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function performPreInstallTasks() {
		\CompositeCache::flush(); // avoid stale cache

		// create tmp dir
		if (!file_exists($this->config->get('taskqueue_tmp_directory'))) {
			if (!self::createDirectoryPath($this->config->get('taskqueue_tmp_directory'))) {
				$this->addError("Couldn't create tmp directory at ".$this->config->get('taskqueue_tmp_directory'));
				return false;
			}
		} else {
			// if already exists then remove all contents to avoid stale cache
			caRemoveDirectory($this->config->get('taskqueue_tmp_directory'), false);
		}

		// Create media directories
		$o_media_volumes = new \MediaVolumes();
		$media_volumes = $o_media_volumes->getAllVolumeInformation();

		$base_dir = $this->config->get('ca_base_dir');
		foreach($media_volumes as $label => $volume_info) {
			if (preg_match('!^'.$base_dir.'!', $volume_info['absolutePath'])) {
				if (!self::createDirectoryPath($volume_info['absolutePath'])) {
					$this->addError("Couldn't create directory for media volume {$label}");
					return false;
				}
			}
		}

		if (($this->config->get('search_engine_plugin') == 'ElasticSearch') && (!$this->isAlreadyInstalled() || (defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__') && __CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__ && $this->overwrite))) {
			$o_es = new \WLPlugSearchEngineElasticSearch();
			try {
				$o_es->truncateIndex();
			} catch(DatabaseException $e) {
				// noop. this can happen when we operate on an empty database where ca_application_vars doesn't exist yet
			}
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function performPostInstallTasks() {
	    // process metadata element settings that couldn't be processed during install
	    // (Eg. those for hideIfSelected_*)
	    if (sizeof($this->metadata_element_deferred_settings_processing)) {
	        foreach($this->metadata_element_deferred_settings_processing as $element_code => $settings) {
	            if (!($t_element = \ca_metadata_elements::getInstance($element_code))) { continue; }
	            $available_settings = $t_element->getAvailableSettings();
	            foreach($settings as $setting_name => $setting_values) {
	                if (!isset($available_settings[$setting_name])) { continue; }
	                
	                if (isset($available_settings[$setting_name]['multiple']) && $available_settings[$setting_name]['multiple']) {
	                    $t_element->setSetting($setting_name, $setting_values);
	                } else {
	                    $t_element->setSetting($setting_name, array_shift($setting_values));
	                }
	            }
	        }
	        $t_element->update();
	    } 
	    
		// generate system GUID -- used to identify systems in data sync protocol
		$o_vars = new \ApplicationVars();
		$o_vars->setVar('system_guid', caGenerateGUID());
		$o_vars->save();

		// refresh mapping if ElasticSearch is used
		if ($this->config->get('search_engine_plugin') == 'ElasticSearch') {
			$o_es = new \WLPlugSearchEngineElasticSearch();
			$o_es->refreshMapping(true);
			\CompositeCache::flush();
		}
	}
	# --------------------------------------------------
	/**
	 * Checks if CollectiveAccess tables already exist in the database
	 *
	 * @return boolean Returns true if CA is already installed
	 */
	public function isAlreadyInstalled() {
		$ca_tables = \Datamodel::getTableNames();

		$qr = $this->db->query("SHOW TABLES");

		while($qr->nextRow()) {
			$table = $qr->getFieldAtIndex(0);
			if (in_array($table, $ca_tables)) {
				return true;
			}
		}
		return false;
	}
	# --------------------------------------------------
	/**
	 * Loads CollectiveAccess schema into an empty database
	 *
	 * @param callable $f_callback Function to be called for each SQL statement in the schema. Function is passed four parameters: the SQL code of the statement, the table name, the number of the table being loaded and the total number of tables.
	 * @return boolean Returns true on success, false if an error occurred
	 */
	public function loadSchema($f_callback=null) {
		if (defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__') && __CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__ && ($this->overwrite)) {
			$this->db->query('DROP DATABASE IF EXISTS `'.__CA_DB_DATABASE__.'`');
			$this->db->query('CREATE DATABASE `'.__CA_DB_DATABASE__.'`');
			$this->db->query('USE `'.__CA_DB_DATABASE__.'`');
		}
		
		if($this->isAlreadyInstalled()) {
			throw new \Exception("Cannot install because an existing CollectiveAccess installation has been detected.");
		}
		
		// load schema
		if (!($schema = file_get_contents(__CA_BASE_DIR__."/install/inc/schema_mysql.sql"))) {
			throw new \Exception("Could not open schema definition file");
		}
		$schema_statements = explode(';', $schema);

		$num_tables = 0;
		foreach($schema_statements as $statement) {
			if (!trim($statement)) { continue; }
			if (preg_match('!create table!i', $statement)) {
				$num_tables++;
			}
		}

		$i = 0;
		foreach($schema_statements as $statement) {
			if (!trim($statement)) { continue; }

			if (is_callable($f_callback) && preg_match('!create[ ]+table[ ]+([A-Za-z0-9_]+)!i', $statement, $matches)) {
				$i++;
				if (file_exists(__CA_MODELS_DIR__.'/'.$matches[1].'.php')) {
					include_once(__CA_MODELS_DIR__.'/'.$matches[1].'.php');
					$table = \BaseModel::$s_ca_models_definitions[$matches[1]]['NAME_PLURAL'];
				} else {
					$table = $matches[1];
				}
				$f_callback($statement, $table, $i, $num_tables);
			}
			$this->db->query($statement);
			if ($this->db->numErrors()) {
				throw new \Exception("Error while loading the database schema: ".join("; ",$this->db->getErrors()));
			}
		}
	}
	# --------------------------------------------------
	# PROFILE CONTENT PROCESSING
	# --------------------------------------------------
	/**
	 *
	 */
	public function processLocales() {
		global $g_ui_locale_id, $g_ui_locale;

		$t_locale = new \ca_locales();
		// Find any existing locales
		$locales = $t_locale->getLocaleList(array('index_by_code' => true));
		foreach($locales as $code => $locale) {
			$this->locales[$code] = $locale['locale_id'];
		}
		
		$locales = $this->parsed_data['locales'];

		foreach($locales as $locale) {
			$t_locale->clear();
			$name = $locale["name"];
			$language = $locale["language"];
			$dialect = $locale["dialect"];
			$country = $locale["country"];
			$dont_use_for_cataloguing = $locale["dontUseForCataloguing"];
			$locale_code = $dialect ? $language."_".$country.'_'.$dialect : $language."_".$country;

			if(isset($this->locales[$locale_code]) && ($locale_id = $this->locales[$locale_code])) { // don't insert duplicate locales
				$t_locale->load($locale_id); // load locale so that we can 'overwrite' any existing attributes/fields
			}
			$t_locale->set('name', $name);
			$t_locale->set('country', $country);
			$t_locale->set('language', $language);
			if($dialect) $t_locale->set('dialect', $dialect);
			
			if (!is_null($dont_use_for_cataloguing)) {
				$t_locale->set('dont_use_for_cataloguing', (bool)$dont_use_for_cataloguing);
			}
			($t_locale->getPrimaryKey() > 0) ? $t_locale->update() : $t_locale->insert();

			if ($t_locale->numErrors()) {
				$this->addError("There was an error while inserting locale {$locale_code}: ".join(" ",$t_locale->getErrors()));
			}
			if ($locale_code === $g_ui_locale && $t_locale->getPrimaryKey()){
				$g_ui_locale_id = $t_locale->getPrimaryKey();
			}
			$this->locales[$locale_code] = $t_locale->getPrimaryKey();
		}

		$locales = $t_locale->getAppConfig()->getList('locale_defaults');
		$locale_id = $t_locale->localeCodeToID($locales[0]);

		if(!$locale_id) {
			throw new \Exception("The locale default is set to a non-existing locale. Try adding '". $locales[0] . "' to your profile.");
		}
		// Ensure the default locale comes first.
		uksort($this->locales, function($a) use ( $locale_id ) {
			return $a === $locale_id;
		});

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processLists($f_callback=null) {
		$lists = $this->parsed_data['lists'];

		$i = 0;
		$num_lists = sizeof($lists);
		foreach($lists as $list) {
			$list_code = $list['code'];
			$this->logStatus(_t('Processing list with code %1', $list_code));
			if(!($t_list = \ca_lists::find(array('list_code' => $list_code), array('returnAs' => 'firstModelInstance')))) {
				$t_list = new \ca_lists();
			}

			if($t_list->getPrimaryKey()) {
				$this->logStatus(_t('List %1 already exists', $list_code));
			} else {
				$this->logStatus(_t('%1 is a new list', $list_code));
			}


			if($list["deleted"] && $t_list->getPrimaryKey()) {
				$this->logStatus(_t('Deleting list %1', $list_code));
				$t_list->delete(true);
				continue;
			}

			if (is_callable($f_callback)) {
				$i++;

				$f_callback($list_code, $i, $num_lists);
			}

			$t_list->set("list_code", $list_code);
			$t_list->set("is_system_list", $list['system'] ? 1 : 0);
			$t_list->set("is_hierarchical", $list['hierarchical']);
			$t_list->set("use_as_vocabulary", $list['vocabulary']);
			if((int)$list['defaultSort'] >= 0) $t_list->set("default_sort",(int)$list['defaultSort']);
			if($t_list->getPrimaryKey()) {
				$t_list->update();
			} else {
				$t_list->insert();
			}
			
			if ($t_list->numErrors()) {
				$this->addError("There was an error while inserting list {$list_code}: ".join(" ",$t_list->getErrors()));
			} else {
				$this->logStatus(_t('Successfully inserted or updated list %1', $list_code));
				
				$this->addLabels($t_list, $list['labels']);
				if ($t_list->numErrors()) {
					$this->addError("There was an error while inserting list label for {$list_code}: ".join(" ",$t_list->getErrors()));
				}
				if($list['items']) {
					if(!$this->processListItems($t_list, $list['items'], null)) {
						return false;
					}
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 * @param $t_list ca_lists
	 * @param $items SimpleXMLElement
	 * @param $parent_id int
	 * @return bool
	 */
	protected  function processListItems($t_list, $items, $parent_id) {
		foreach($items as $item) {
			$item_value = $item["value"];
			$item_idno = $item["idno"];
			$type = $item["type"];
			$status = $item["status"];
			$access = $item["access"];
			$rank = $item["rank"];
			$enabled = $item["enabled"];
			$default = $item["default"];
			$color = $item["color"];

			$type_id = null;
			if ($type) {
				$type_id = $t_list->getItemIDFromList('list_item_types', $type);
			}

			if (!isset($status)) { $status = 0; }
			if (!isset($access)) { $access = 0; }
			if (!isset($rank)) { $rank = 0; }

			$this->logStatus(_t('Processing list item with idno %1', $item_idno));
			$deleted = $item["deleted"];
			
			if($item_id = caGetListItemID($t_list->get('list_code'), $item_idno, ['dontCache' => true])) {
				$this->logStatus(_t('List item with idno %1 already exists', $item_idno));
				if($deleted) {
					$this->logStatus(_t('Deleting list item with idno %1', $item_idno));
					$t_item = new \ca_list_items($item_id);
					$t_item->delete();
					continue;
				}
				$t_item = $t_list->editItem($item_id, $item_value, $enabled, $default, $parent_id, $item_idno, '', (int)$status, (int)$access, (int)$rank, $color);
			} else {
				$this->logStatus(_t('List item with idno %1 is a new item', $item_idno));
				if ($deleted) {
					continue;
				} else {
					$t_item = $t_list->addItem($item_value, $enabled, $default, $parent_id, $type_id, $item_idno, '', (int)$status, (int)$access, (int)$rank, $color);
				}
			}

			if (($t_list->numErrors() > 0) || !is_object($t_item)) {
				$this->addError("There was an error while inserting list item {$item_idno}: ".join(" ",$t_list->getErrors()));
				return false;
			} else {
				$this->logStatus(_t('Successfully updated/inserted list item with idno %1', $item_idno));
				if($item['settings']) {
					$this->_processSettings($t_item, $item['settings']);
					$t_item->update();
					if ($t_item->numErrors()) {
						$this->addError("There was an error while adding a setting for list item with idno {$item_idno}: ".join(" ",$t_item->getErrors()));
					}
				}
				self::addLabels($t_item, $item['labels']);
				if ($t_item->numErrors()) {
					$this->addError("There was an error while inserting list item label for {$item_idno}: ".join(" ",$t_item->getErrors()));
				}
			}

			if (isset($item['items'])) {
				if(!$this->processListItems($t_list, $item['items'], $t_item->getPrimaryKey())) {
					return false;
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processMetadataElements() {
		$t_rel_types = new \ca_relationship_types();
		$t_list = new \ca_lists();

		$elements = $this->parsed_data['metadataElements'];
		

		foreach($elements as $element_code => $element) {
			if($element_id = $this->processMetadataElement($element, null)) {
				// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
				// if we're updating, we expect the list of restrictions to include all restrictions!
				if(sizeof($element['typeRestrictions'])) {
					$this->db->query('DELETE FROM ca_metadata_type_restrictions WHERE element_id = ?', $element_id);
				}

				$this->logStatus(_t('Successfully nuked all type restrictions for element %1', $element_code));

				// handle restrictions
				foreach($element['typeRestrictions'] as $restriction) {
					$restriction_code = $restriction["code"];

					if (!($table_num = \Datamodel::getTableNum($restriction['table']))) {
						$this->addError("Invalid table specified for restriction $restriction_code in element $element_code");
						return false;
					}
					$t_instance = \Datamodel::getInstance((string)$restriction['table']);
					$type_id = null;
					$type = trim((string)$restriction['type']);

					// is this restriction further restricted on a specific type? -> get real id from code
					if (strlen($type)>0) {
						// interstitial with type restriction -> code is relationship type code
						if($t_instance instanceof \BaseRelationshipModel) {
							$type_id = $t_rel_types->getRelationshipTypeID($t_instance->tableName(),$type);
						} else { // "normal" type restriction -> code is from actual type list
							$type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());
							$type_id = $t_list->getItemIDFromList($type_list_name, $type);
						}
					}

					// add restriction
					$t_restriction = new \ca_metadata_type_restrictions();
					$t_restriction->set('table_num', $table_num);
					$t_restriction->set('include_subtypes', (bool)$restriction['includeSubtypes'] ? 1 : 0);
					$t_restriction->set('type_id', $type_id);
					$t_restriction->set('element_id', $element_id);

					$this->_processSettings($t_restriction, $restriction['settings']);
					$t_restriction->insert();

					if ($t_restriction->numErrors()) {
						$this->addError("There was an error while inserting type restriction {$restriction_code} for metadata element {$element_code}: ".join("; ",$t_restriction->getErrors()));
					}

					$this->logStatus(_t('Successfully added type restriction %1 for element %2', $restriction_code, $element_code));
				}
			}
		}
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function processMetadataElement($element, $parent_id) {
		$element_code = $element["code"];

		$this->logStatus(_t('Processing metadata element with code %1', $element_code));

		// try to load element by code for potential update. codes are unique, globally
		if(!($t_md_element = \ca_metadata_elements::getInstance($element_code))) {
			$t_md_element = new \ca_metadata_elements();
		}

		if($t_md_element->getPrimaryKey()) {
			$this->logStatus(_t('Metadata element with code %1 already exists', $element_code));
		} else {
			$this->logStatus(_t('Metadata element with code %1 is new', $element_code));
		}

		if($element['deleted'] && $t_md_element->getPrimaryKey()) {
			$this->logStatus(_t('Deleting metadata element with code %1', $element_code));
			$t_md_element->delete(true, ['hard' => true]);
			return false; // we don't want the postprocessing to kick in. our work here is done.
		}

		if (($datatype = \ca_metadata_elements::getAttributeTypeCode($element["datatype"])) === false) {
			return false; // should not happen due to XSD restrictions, but just in case
		}

		$t_lists = new \ca_lists();

		$t_md_element->set('element_code', $element_code);
		$t_md_element->set('parent_id', $parent_id);
		$t_md_element->set('documentation_url',$element['documentationUrl']);
		$t_md_element->set('datatype', $datatype);

		$list = $element["list"];

		if (isset($list) && $list && $t_lists->load(['list_code' => $list])) {
			$list_id = $t_lists->getPrimaryKey();
		} else {
			$list_id = null;
		}
		$t_md_element->set('list_id', $list_id);
		$this->_processSettings($t_md_element, $element['settings'], ['settingsInfo' => $t_md_element->getAvailableSettings()]);

		if($t_md_element->getPrimaryKey()) {
			$t_md_element->update(['noFlush' => true]);
		}else{
			$t_md_element->insert(['noFlush' => true]);
		}

		if ($t_md_element->numErrors()) {
			$this->addError("There was an error while inserting metadata element {$element_code}: ".join(" ",$t_md_element->getErrors()));
			return false;
		}

		$this->logStatus(_t('Successfully inserted/updated metadata element with code %1', $element_code));

		$element_id = $t_md_element->getPrimaryKey();

		// add element labels
		self::addLabels($t_md_element, $element['labels']);

		if ($element['elements']) {
			foreach($element['elements'] as $child) {
				$this->processMetadataElement($child, $element_id);
			}
		}

		return $element_id;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processMetadataDictionary() {
		$dict = $this->parsed_data['metadataDictionary'];
		
		// dictionary entries don't have a code or any other attribute that could be used for
		// identification so we won't support setting them in a base profile, for now ...

		foreach($dict as $i => $entry) {
			if(strlen($entry['bundle'])<1) {
				$this->addError("No bundle specified in a metadata dictionary entry. Skipping row.");
				continue;
			}
			
			if(!($table_num = \Datamodel::getTableNum($entry['table']))) {
				$this->addError("Table {$entry['table']} is invalid for metadata dictionary entry. Skipping row.");
				continue;
			}
			
			// insert dictionary entry
			$t_entry = new \ca_metadata_dictionary_entries();
			$t_entry->set('bundle_name', $entry['bundle']);
			$t_entry->set('table_num', $table_num);
			$this->_processSettings($t_entry, $entry['settings']);
			
			$t_entry->insert();

			if($t_entry->numErrors() > 0 || !($t_entry->getPrimaryKey()>0)) {
				$this->addError("There were errors while adding dictionary entry: " . join(';', $t_entry->getErrors()));
				return false;
			}
			
			if(is_array($entry['rules'])) {
				foreach($entry['rules'] as $rule) {
					$t_rule = new \ca_metadata_dictionary_rules();
					$t_rule->set('entry_id', $t_entry->getPrimaryKey());
					$t_rule->set('rule_code', $rule['code']);
					$t_rule->set('rule_level', $rule['level']);
					$t_rule->set('expression', $rule['expression']);
					$this->_processSettings($t_rule, $rule['settings']);

					$t_rule->insert();
					if ($t_rule->numErrors()) {
						$this->addError("There were errors while adding dictionary rule: " . join(';', $t_rule->getErrors()));
						continue;
					}
				}
			}
		}
		
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processUserInterfaces() {
		$o_annotation_type_conf = \Configuration::load(\Configuration::load()->get('annotation_type_config'));
		$t_placement = new \ca_editor_ui_bundle_placements();
		$t_list = new \ca_lists();
		$t_rel_types = new \ca_relationship_types();
		
		$uis = $this->parsed_data['userInterfaces'];
	
		foreach($uis as $ui_code => $ui) {
			$type = $ui["type"];
			if (!($type = \Datamodel::getTableNum($type))) {
				$this->addError("Invalid type {$type} for UI code {$ui_code}");
				return false;
			}

			$this->logStatus(_t('Processing user interface with code %1', $ui_code));

			// model instance of UI type
			$t_instance = \Datamodel::getInstanceByTableNum($type);

			// create ui row
			if(!($t_ui = \ca_editor_uis::find(['editor_code' => $ui_code, 'editor_type' =>  $type], ['returnAs' => 'firstModelInstance']))) {
				$t_ui = new \ca_editor_uis();
				$this->logStatus(_t('User interface with code %1 is new', $ui_code));
			} else {
				$this->logStatus(_t('User interface with code %1 already exists', $ui_code));
			}

			if($ui['deleted'] && $t_ui->getPrimaryKey()) {
				$this->logStatus(_t('Deleting user interface with code %1', $ui_code));
				$t_ui->delete(true, ['hard' => true]);
				continue;
			}

			$t_ui->set('user_id', null);
			$t_ui->set('is_system_ui', 1);
			$t_ui->set('editor_code', $ui_code);
			$t_ui->set('editor_type', $type);
			if ($color = $ui["color"]) { $t_ui->set('color', $color); }

			if($t_ui->getPrimaryKey()) {
				$t_ui->update();
			}else{
				$t_ui->insert();
			}

			if ($t_ui->numErrors()) {
				$this->addError("Errors inserting UI {$ui_code}: ".join("; ",$t_ui->getErrors()));
				return false;
			}

			$this->logStatus(_t('Successfully inserted/updated user interface with code %1', $ui_code));

			$ui_id = $t_ui->getPrimaryKey();

			self::addLabels($t_ui, $ui['labels']);

			$annotation_types = $o_annotation_type_conf->get('types');
			
			// create ui type restrictions
			if($ui['typeRestrictions']) {
				// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
				// if we're updating, we expect the list of restrictions to include all restrictions!
				if(sizeof($ui['typeRestrictions'])) {
					$this->db->query('DELETE FROM ca_editor_ui_type_restrictions WHERE ui_id = ?', $ui_id);
				}

				$this->logStatus(_t('Successfully nuked all type restrictions for user interface with code %1', $ui_code));

				foreach($ui['typeRestrictions'] as $restriction) {
					$restriction_type = $restriction["type"];

					if (strlen($restriction_type)>0) {
						// interstitial with type restriction -> code is relationship type code
						if($t_instance instanceof \BaseRelationshipModel) {
							$type_id = $t_rel_types->getRelationshipTypeID($t_instance->tableName(), $restriction_type);
						} elseif($t_instance instanceof \ca_representation_annotations) {
							// representation annotation -> code is annotation type from annotation_types.conf
							$type_id = $annotation_types[$restriction_type]['typeID'];
						} else { // "normal" type restriction -> code is from actual type list
							$type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());
							$type_id = $t_list->getItemIDFromList($type_list_name, $restriction_type);
						}
						
						$t_ui->addTypeRestriction($type_id, ['includeSubtypes' => $restriction["includeSubtypes"]]);
					
						$this->logStatus(_t('Successfully added type restriction %1 for user interface with code %2', $restriction_type, $ui_code));
					}
				}
			}

			// create ui screens
			foreach($ui['screens'] as $screen) {
				$screen_idno = $screen["idno"];
				$is_default = $screen["default"];

				$this->logStatus(_t('Processing screen with code %1 for user interface with code %2', $screen_idno, $ui_code));

				$t_ui_screens = \ca_editor_ui_screens::find(array(
					'idno' => $screen_idno,
					'ui_id' => $ui_id
				), ['returnAs' => 'firstModelInstance']);

				$t_ui_screens = $t_ui_screens ? $t_ui_screens : new \ca_editor_ui_screens();

				if($t_ui_screens->getPrimaryKey()) {
					$this->logStatus(_t('Screen with code %1 for user interface with code %2 already exists', $screen_idno, $ui_code));
				} else {
					$this->logStatus(_t('Screen with code %1 for user interface with code %2 is new', $screen_idno, $ui_code));
				}

				if($screen['deleted'] && $t_ui_screens->getPrimaryKey()) {
					$this->logStatus(_t('Deleting screen with code %1 for user interface with code %2', $screen_idno, $ui_code));
					$t_ui_screens->delete(true, ['hard' => true]);
					continue;
				}

				$t_ui_screens->set('idno',$screen_idno);
				$t_ui_screens->set('ui_id', $ui_id);
				$t_ui_screens->set('is_default', $is_default);
				if ($color = $screen["color"]) { $t_ui_screens->set('color', $color); }

				if($t_ui_screens->getPrimaryKey()) {
					$t_ui_screens->update();
				}else{
					$t_ui_screens->set('parent_id', null);
					$t_ui_screens->insert();
				}

				if ($t_ui_screens->numErrors()) {
					$this->addError("Errors inserting UI screen {$screen_idno} for UI {$ui_code}: ".join("; ",$t_ui_screens->getErrors()));
					return false;
				}

				$this->logStatus(_t('Successfully updated/inserted screen with code %1 for user interface with code %2', $screen_idno, $ui_code));

				self::addLabels($t_ui_screens, $screen['labels']);
			
				$available_bundles = $t_ui_screens->getAvailableBundles(null, ['dontCache' => true]);

				// nuke previous placements. there shouldn't be any if we're installing from scratch.
				// if we're updating, we expect the list of placements to include all of them!
				if(sizeof($screen['bundles'])) {
					$this->db->query('DELETE FROM ca_editor_ui_bundle_placements WHERE screen_id = ?', $t_ui_screens->getPrimaryKey());
				}

				$this->logStatus(_t('Successfully nuked all bundle placements for screen with code %1 for user interface with code %2', $screen_idno, $ui_code));

                // set user and group access
                if($screen['userAccess']) {
                    $t_user = new \ca_users();
                    $ui_screen_users = [];
                    foreach($screen['userAccess'] as $permission) {
                        $user = trim((string)$permission["user"]);
                        $access = $this->_convertUserGroupAccessStringToInt($permission['access']);

                        if(!$t_user->load(['user_name' => $user])) { continue; }
                        if($access) {
                            $ui_screen_users[$t_user->getUserID()] = $access;
                        } else {
                            $this->addError("User name or access value invalid for UI screen {$screen_idno} (permission item with user name '{$user}')");
                        }
                    }

                    if(sizeof($ui_screen_users) > 0) {
                        $t_ui_screens->addUsers($ui_screen_users);
                    }
                }

                if($screen['groupAccess']) {
                    $t_group = new \ca_user_groups();
                    $ui_screen_groups = [];
                    foreach($screen['groupAccess'] as $permission) {
                        $group = trim((string)$permission["group"]);
                        $access = $this->_convertUserGroupAccessStringToInt($permission['access']);

                        if(!$t_group->load(['code' => $group])) { continue; }
                        if($access) {
                            $ui_screen_groups[$t_group->getPrimaryKey()] = $access;
                        } else {
                            $this->addError("Group code or access value invalid for UI screen {$screen_idno} (permission item with group code '{$group}')");
                        }
                    }

                    if(sizeof($ui_screen_groups) > 0) {
                        $t_ui_screens->addUserGroups($ui_screen_groups);
                    }
                }
                
                if($screen['roleAccess']) {
                    $t_role = new \ca_user_roles();
                    $ui_screen_roles = [];
                    foreach($screen['roleAccess'] as $permission) {
                        $role = trim((string)$permission["role"]);
                        $access = $this->_convertUserGroupAccessStringToInt($permission['access']);

                        if(!$t_role->load(['code' => $role])) { continue; }
                        if(!is_null($access)) {
                            $ui_screen_roles[$t_role->getPrimaryKey()] = $access;
                        } else {
                            $this->addError("Role code or access value invalid for UI screen {$screen_idno} (permission item with role code '{$role}')");
                        }
                    }
                    if(sizeof($ui_screen_roles)>0) {   
						$all_roles = $t_role->getRoleList();
						foreach($all_roles as $role_id => $role_info) {
							if (!isset($ui_screen_roles[$role_id])) { $ui_screen_roles[$role_id] = 0; }
						}
                        $t_ui_screens->addUserRoles($ui_screen_roles);
                    }
                }

				// create ui bundle placements
				foreach($screen['bundles'] as $placement) {
					$placement_code = $placement["code"];
					$bundle_type_restrictions = $placement["typeRestrictions"];
					$bundle = trim((string)$placement['bundle']);

					if (is_array($bundle_type_restrictions) && sizeof($bundle_type_restrictions)) {
						// Copy type restrictions listed on the <placement> tag into numeric type_ids stored
						// as settings on the placement record.
						if ($t_instance instanceof \BaseRelationshipModel) {
							$ids = caMakeRelationshipTypeIDList($t_instance->tableNum(), $bundle_type_restrictions);
						} elseif($t_instance instanceof \ca_representation_annotations) {
							$ids = [];
							foreach($bundle_type_restrictions as $annotation_type) {
								if(isset($annotation_types[$annotation_type]['typeID'])) {
									$ids[] = $annotation_types[$annotation_type]['typeID'];
								}
							}
						} else {
							$ids = caMakeTypeIDList($t_instance->tableNum(), $bundle_type_restrictions, ['dontIncludeSubtypesInTypeRestriction' => true]);
						}
						
						foreach($ids as $id) {
							$o_setting = $placement['settings']['bundleTypeRestrictions'][''][] = $id;
						}
						
						if ($include_subtypes = (bool)$placement["includeSubtypes"]) {
							$o_setting = $placement['settings']['bundleTypeRestrictionsIncludeSubtypes'][''][] = 1;
						}
					}
					
					$settings = $this->_processSettings(null, $placement['settings'], ['settingsInfo' => array_merge($t_placement->getAvailableSettings(), is_array($available_bundles[$bundle]['settings']) ? $available_bundles[$bundle]['settings'] : [])]);
					$this->logStatus(_t('Adding bundle %1 with code %2 for screen with code %3 and user interface with code %4', $bundle, $placement_code, $screen_idno, $ui_code));

					if (!$t_ui_screens->addPlacement($bundle, $placement_code, $settings, null, ['additional_settings' => $available_bundles[$bundle]['settings']])) {
						$this->logStatus(join("; ", $t_ui_screens->getErrors()));
					}
				}

				// create ui screen type restrictions
				if($screen['typeRestrictions']) {
					// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
					// if we're updating, we expect the list of restrictions to include all of them!
					if(sizeof($screen['typeRestrictions'])) {
						$this->db->query('DELETE FROM ca_editor_ui_screen_type_restrictions WHERE screen_id = ?', $t_ui_screens->getPrimaryKey());
					}

					$this->logStatus(_t('Successfully nuked all type restrictions for screen with code %1 for user interface with code %2', $screen_idno, $ui_code));

					foreach($screen['typeRestrictions'] as $restriction) {
						$restriction_type = $restriction["type"];

						if (strlen($restriction_type) > 0) {
							// interstitial with type restriction -> code is relationship type code
							if($t_instance instanceof \BaseRelationshipModel) {
								$type_id = $t_rel_types->getRelationshipTypeID($t_instance->tableName(),$restriction_type);
							} elseif($t_instance instanceof \ca_representation_annotations) {
								// representation annotation -> code is annotation type from annotation_types.conf
								$type_id = $annotation_types[$restriction_type]['typeID'];
							} else { // "normal" type restriction -> code is from actual type list
								$type_list_name = $t_instance->getFieldListCode($t_instance->getTypeFieldName());
								$type_id = $t_list->getItemIDFromList($type_list_name, $restriction_type);
							}

							if($type_id) {
								$t_ui_screens->addTypeRestriction($type_id, ['includeSubtypes' => $restriction["includeSubtypes"]]);
							}

							$this->logStatus(_t('Successfully added type restriction %1 for screen with code %2 for user interface with code %3', $restriction_type, $screen_idno, $ui_code));
						}
					}
				}
			}

			// set user and group access
			if($ui['userAccess']) {
				$t_user = new \ca_users();
				$ui_users = [];
				foreach($ui['userAccess'] as $permission) {
					$user = trim((string)$permission["user"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_user->load(['user_name' => $user])) { continue; }
					if($access) {
						$ui_users[$t_user->getUserID()] = $access;
					} else {
						$this->addError("User name or access value invalid for UI {$ui_code} (permission item with user name '{$user}')");
					}
				}

				if(sizeof($ui_users)>0) {
					$t_ui->addUsers($ui_users);
				}
			}

			if($ui['groupAccess']) {
				$t_group = new \ca_user_groups();
				$ui_groups = [];
				foreach($ui['groupAccess'] as $permission) {
					$group = trim((string)$permission["group"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_group->load(['code' => $group])) { continue; }
					if($access) {
						$ui_groups[$t_group->getPrimaryKey()] = $access;
					} else {
						$this->addError("Group code or access value invalid for UI {$ui_code} (permission item with group code '{$group}')");
					}
				}

				if(sizeof($ui_groups)>0) {
					$t_ui->addUserGroups($ui_groups);
				}
			}
			
			if($ui['roleAccess']) {
				$t_role = new \ca_user_roles();
				$ui_roles = [];
				foreach($ui['roleAccess'] as $permission) {
					$role = trim((string)$permission["role"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_role->load(['code' => $role])) { continue; }
					if(!is_null($access)) {
						$ui_roles[$t_role->getPrimaryKey()] = $access;
					} else {
						$this->addError("Role code or access value invalid for UI {$ui_code} (permission item with role code '{$role}')");
					}
				}

				if(sizeof($ui_roles)>0) {
					$all_roles = $t_role->getRoleList();
					foreach($all_roles as $role_id => $role_info) {
						if (!isset($ui_roles[$role_id])) { $ui_roles[$role_id] = 0; }
					}
					$t_ui->addUserRoles($ui_roles);
				}
			}
		}
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processRelationshipTypes() {
		$relationship_types = $this->parsed_data['relationshipTypes'];
		
		$qr_lists = $this->db->query("SELECT * FROM ca_lists");

		$list_names = $list_item_ids = [];
		while($qr_lists->nextRow()) {
			$list_names[$qr_lists->get('list_id')] = $qr_lists->get('list_code');
		}

		// get list items
		$qr_list_item_result = $this->db->query("
			SELECT * 
			FROM ca_list_items cli 
			INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id
		");
		while($qr_list_item_result->nextRow()) {
			$type_code = $list_names[$qr_list_item_result->get('list_id')];
			$list_item_ids[$type_code][$qr_list_item_result->get('item_value')] = $qr_list_item_result->get('item_id');
		}

		foreach($relationship_types as $rel_table => $types) {
			$table_num = \Datamodel::getTableNum($rel_table);
			$this->logStatus(_t('Processing relationship types for table %1', $rel_table));

			$t_rel_table = \Datamodel::getInstance($rel_table);

			if (!method_exists($t_rel_table, 'getLeftTableName')) {
				continue;
			}
			$left_table = $t_rel_table->getLeftTableName();
			$right_table = $t_rel_table->getRightTableName();


			$root_type_code = "root_for_{$table_num}";

			/** @var ca_relationship_types $t_rel_type */
			$t_rel_type = \ca_relationship_types::find(
				['type_code' => $root_type_code, 'table_num' => $table_num, 'parent_id' => null],
				['returnAs' => 'firstModelInstance']
			);

			$t_rel_type = $t_rel_type ? $t_rel_type : new \ca_relationship_types();
			// create relationship type root if necessary
			$t_rel_type->set('parent_id', null);
			$t_rel_type->set('type_code', $root_type_code);
			$t_rel_type->set('sub_type_left_id', null);
			$t_rel_type->set('sub_type_right_id', null);
			$t_rel_type->set('table_num', $table_num);
			$t_rel_type->set('rank', 10);
			$t_rel_type->set('is_default', 0);
			if(!$t_rel_type->getPrimaryKey()) { // do nothing if find() found that very root
				$t_rel_type->insert();
			}

			if ($t_rel_type->numErrors()) {
				$this->addError("Errors inserting relationship root for {$rel_table}: ".join("; ",$t_rel_type->getErrors()));
				return false;
			}

			$parent_id = $t_rel_type->getPrimaryKey();

			$this->processRelationshipTypesForTable($types, $table_num, $left_table, $right_table, $parent_id, $list_item_ids);
		}
		return true;
	}
	# --------------------------------------------------
	/** 
	 *
	 */
	private function processRelationshipTypesForTable($relationship_types, $table_num, $left_table, $right_table, $parent_id, $list_item_ids) {
		// nuke caches to be safe
		\ca_relationship_types::$s_relationship_type_id_cache = [];
		\ca_relationship_types::$s_relationship_type_table_cache = [];
		\ca_relationship_types::$s_relationship_type_id_to_code_cache = [];

		$t_rel_type = new \ca_relationship_types();

		$rank_default = (int)$t_rel_type->getFieldInfo('rank', 'DEFAULT');
		foreach($relationship_types as $type) {
			$type_code = $type["code"];
			$default = $type["default"];
			$rank = (int)$type["rank"];

			$this->logStatus(_t('Processing relationship type with code %1', $type_code));

			$t_rel_type = \ca_relationship_types::find(
				['type_code' => $type_code, 'table_num' => $table_num, 'parent_id' => $parent_id],
				['returnAs' => 'firstModelInstance']
			);

			$t_rel_type = $t_rel_type ? $t_rel_type : new \ca_relationship_types();

			if($t_rel_type->getPrimaryKey()) {
				$this->logStatus(_t('Relationship type with code %1 already exists', $type_code));
			} else {
				$this->logStatus(_t('Relationship type with code %1 is new', $type_code));
			}

			if($type["deleted"] && $t_rel_type->getPrimaryKey()) {
				$this->logStatus(_t('Deleting relationship type with code %1', $type_code));
				$t_rel_type->delete(true);
				continue;
			}

			$t_rel_type->set('table_num', $table_num);
			$t_rel_type->set('type_code', $type_code);
			$t_rel_type->set('parent_id', $parent_id);
			$t_rel_type->set('is_default', $default ? 1 : 0);

			if ($rank > 0) {
				$t_rel_type->set("rank", $rank);
			} else {
				$t_rel_type->set("rank", $rank_default);
			}

			if($t_rel_type->getPrimaryKey()) {
				$t_rel_type->update();
			} else {
				$t_rel_type->insert();
			}

			// As of February 2017 "typeRestrictionLeft" is preferred over "subTypeLeft"
			if(
				($left_subtype_code = $type["typeRestrictionLeft"])
			) {
				$t_obj = \Datamodel::getInstance($left_table);
				$list_code = $t_obj->getFieldListCode($t_obj->getTypeFieldName());

				$this->logStatus(_t('Adding left type restriction %1 for relationship type with code %2', $left_subtype_code, $type_code));

				if (isset($list_item_ids[$list_code][$left_subtype_code])) {
					$t_rel_type->set('sub_type_left_id', $list_item_ids[$list_code][$left_subtype_code]);
					
					if(
						($include_subtypes = $type["includeSubtypesLeft"])
					) {
						$t_rel_type->set('include_subtypes_left', (bool)$include_subtypes ? 1 : 0);
					}
					$t_rel_type->update();
				}
			}
			
			if(
				($right_subtype_code = $type["typeRestrictionRight"])
			) {
				$t_obj = \Datamodel::getInstance($right_table);
				$list_code = $t_obj->getFieldListCode($t_obj->getTypeFieldName());

				$this->logStatus(_t('Adding right type restriction %1 for relationship type with code %2', $right_subtype_code, $type_code));

				if (isset($list_item_ids[$list_code][$right_subtype_code])) {
					$t_rel_type->set('sub_type_right_id', $list_item_ids[$list_code][$right_subtype_code]);
					
					if(
						($include_subtypes = $type["includeSubtypesRight"])
					) {
						$t_rel_type->set('include_subtypes_right', (bool)$include_subtypes ? 1 : 0);
					}
					$t_rel_type->update();
				}
			}

			if ($t_rel_type->numErrors()) {
				$this->addError("Errors inserting relationship {$type_code}: ".join("; ",$t_rel_type->getErrors()));
				return false;
			}

			$this->logStatus(_t('Successfully updated/inserted relationship type with code %1', $type_code));

			self::addLabels($t_rel_type, $type['labels']);

			if ($type['types']) {
				$this->processRelationshipTypesForTable($type['types'], $table_num, $left_table, $right_table, $t_rel_type->getPrimaryKey(), $list_item_ids);
			}
		}
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processRoles() {
		$roles = $this->parsed_data['roles'];
		
		foreach($roles as $role_code => $role) {
			$this->logStatus(_t('Processing user role with code %1', $role_code));

			if(!($t_role = \ca_user_roles::find(array('code' => (string)$role_code), ['returnAs' => 'firstModelInstance']))) {
				$this->logStatus(_t('User role with code %1 is new', $role_code));
				$t_role = new \ca_user_roles();
			} else {
				$this->logStatus(_t('User role with code %1 already exists', $role_code));
			}

			if($role["deleted"] && $t_role->getPrimaryKey()) {
				$this->logStatus(_t('Deleting user role with code %1', $role_code));
				$t_role->delete(true);
				continue;
			}

			$t_role->set('name', $role['name']);
			$t_role->set('description', $role['description']);
			$t_role->set('code', $role_code);

			// add actions
			if(is_array($role['actions']) && sizeof($role['actions'])) {
				$t_role->setRoleActions($role['actions']);
				$this->logStatus(_t('Role actions for user role with code %1 are: %2', $role_code, join(',', $role['actions'])));
			}
			
			if($t_role->getPrimaryKey()) {
				$t_role->update();
			} else {
				$t_role->insert();
			}

			if ($t_role->numErrors()) {
				$this->addError("Errors inserting access role {$role_code}: ".join("; ",$t_role->getErrors()));
				return false;
			}

			$this->logStatus(_t('Successfully updated/inserted user role with code %1', $role_code));

			// add bundle level ACL items
			if($role['bundleLevelAccessControl']) {
				// nuke old items
				if(sizeof($role['bundleLevelAccessControl']) > 0) {
					$t_role->removeAllBundleAccessSettings();
					$this->logStatus(_t('Successfully nuked all bundle level access control items for user role with code %1', $role_code));
				}

				foreach($role['bundleLevelAccessControl'] as $permission) {
					$permission_table = $permission['table'];
					$permission_bundle = $permission['bundle'];
					$permission_access = $this->_convertACLStringToConstant($permission['access']);

					if(!$t_role->setAccessSettingForBundle($permission_table, $permission_bundle, $permission_access)) {
						$this->addError("Could not add bundle level access control for table '{$permission_table}' and bundle '{$permission_bundle}'. Check the table and bundle names.");
					}

					$this->logStatus(_t('Added bundle level access control item for user role with code %1: table %2, bundle %3, access %4', $role_code, $permission_table, $permission_bundle, $permission_access));
				}
			}

			// add type level ACL items
			if($role['typeLevelAccessControl']) {
				// nuke old items
				if(sizeof($role['typeLevelAccessControl']) > 0) {
					$t_role->removeAllTypeAccessSettings();
					$this->logStatus(_t('Successfully nuked all type level access control items for user role with code %1', $role_code));
				}

				foreach($role['typeLevelAccessControl'] as $permission) {
					$permission_table = $permission['table'];
					$permission_type = $permission['type'];
					$permission_access = $this->_convertACLStringToConstant($permission['access']);

					if(!$t_role->setAccessSettingForType($permission_table, $permission_type, $permission_access)) {
						$this->addError("Could not add type level access control for table '{$permission_table}' and type '{$permission_type}'. Check the table name and the type code.");
					}

					$this->logStatus(_t('Added type level access control item for user role with code %1: table %2, type %3, access %4', $role_code, $permission_table, $permission_type, $permission_access));
				}
			}

			// add source level ACL items
			if($role['sourceLevelAccessControl']) {
				// nuke old items
				if(sizeof($role['sourceLevelAccessControl']) > 0) {
					$t_role->removeAllSourceAccessSettings();
					$this->logStatus(_t('Successfully nuked all source level access control items for user role with code %1', $role_code));
				}

				foreach($role['sourceLevelAccessControl'] as $permission) {
					$permission_table = $permission['table'];
					$permission_source = $permission['source'];
					$permission_default = $permission['default'];
					$permission_access = $this->_convertACLStringToConstant($permission['access']);

					if(!$t_role->setAccessSettingForSource($permission_table, $permission_source, $permission_access, (bool)$permission_default)) {
						$this->addError("Could not add source level access control for table '{$permission_table}' and source '{$permission_source}'. Check the table name and the source code.");
					}

					$this->logStatus(_t('Added source level access control item for user role with code %1: table %2, source %3, access %4', $role_code, $permission_table, $permission_source, $permission_access));
				}
			}
		}
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processDisplays() {
		$displays = $this->parsed_data['displays'];

		if(sizeof($displays) == 0) { return true; }

		foreach($displays as $display) {
			$display_code = $display["code"];
			$system = $display["system"];
			$table = $display["type"];
			$table_num = \Datamodel::getTableNum($table);

			$this->logStatus(_t('Processing display with code %1', $display_code));

			if ($this->config->get("{$table}_disable")) { continue; }

			if(!($t_display = \ca_bundle_displays::find(['display_code' => $display_code], ['returnAs' => 'firstModelInstance']))) {
				$this->logStatus(_t('Display with code %1 is new', $display_code));
				$t_display = new \ca_bundle_displays();
			} else {
				$this->logStatus(_t('Display with code %1 already exists', $display_code));
			}

			if($display["deleted"] && $t_display->getPrimaryKey()) {
				$t_display->delete(true);
				$this->logStatus(_t('Deleting display with code %1', $display_code));
				continue;
			}

			$t_display->set("display_code", $display_code);
			$t_display->set("is_system", $system ? 1 : 0);
			$t_display->set("table_num",\Datamodel::getTableNum($table));
			$t_display->set("user_id", 1);		// let administrative user own these

			$this->_processSettings($t_display, $display['settings']);

			if($t_display->getPrimaryKey()) {
				$t_display->update();
			} else {
				$t_display->insert();
			}

			if ($t_display->numErrors()) {
				$this->addError("There was an error while inserting display {$display_code}: ".join(" ",$t_display->getErrors()));
			} else {
				$this->logStatus(_t('Successfully updated/inserted display with code %1', $display_code));

				self::addLabels($t_display, $display['labels']);
				if ($t_display->numErrors()) {
					$this->addError("There was an error while inserting display label for {$display_code}: ".join(" ",$t_display->getErrors()));
				}
				if(!$this->processDisplayPlacements($t_display, $display['bundles'], null)) {
					return false;
				}
			}

			if ($display['typeRestrictions']) {
				// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
				// if we're updating, we expect the list of restrictions to include all restrictions!
				if(sizeof($display['typeRestrictions'])) {
					$this->db->query('DELETE FROM ca_bundle_display_type_restrictions WHERE display_id = ?', $t_display->getPrimaryKey());
					$this->logStatus(_t('Successfully nuked all type restrictions for display with code %1', $display_code));
				}

				foreach($display['typeRestrictions'] as $restriction) {
					$restriction_code = trim((string)$restriction["code"]);
					$type = trim((string)$restriction["type"]);
					
					$t_display->addTypeRestriction(array_pop(caMakeTypeIDList($table_num, [$type], ['dontIncludeSubtypesInTypeRestriction' => true])), ['includeSubtypes' => (bool)$restriction['includeSubtypes'] ? 1 : 0]);
					
					if ($t_display->numErrors()) {
						$this->addError("There was an error while inserting type restriction {$restriction_code} in display {$display_code}: ".join("; ",$t_display->getErrors()));
					}

					$this->logStatus(_t('Added type restriction with code %1 and type %2 for display with code %3', $restriction_code, $type, $display_code));
				}
			}

			if($display['userAccess']) {
				$t_user = new \ca_users();
				$display_users = [];
				foreach($display['userAccess'] as $permission) {
					$user = trim((string)$permission["user"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_user->load(['user_name' => $user])) { continue; }
					if($access) {
						$display_users[$t_user->getUserID()] = $access;
					} else {
						$this->addError("User name or access value invalid for display {$display_code} (permission item with user name '{$user}')");
					}
				}

				if(sizeof($display_users)>0) {
					$t_display->addUsers($display_users);
				}
			}

			if($display['groupAccess']) {
				$t_group = new \ca_user_groups();
				$display_groups = [];
				foreach($display['groupAccess'] as $permission) {
					$group = trim((string)$permission["group"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_group->load(['code' => $group])) { continue; }
					if($access) {
						$display_groups[$t_group->getPrimaryKey()] = $access;
					} else {
						$this->addError("Group code or access value invalid for display {$display_code} (permission item with group code '{$group}')");
					}
				}

				if(sizeof($display_groups)>0) {
					$t_display->addUserGroups($display_groups);
				}
			}
			
			if($display['roleAccess']) {
				$t_role = new \ca_user_roles();
				$display_roles = [];
				foreach($display['roleAccess'] as $permission) {
					$role = trim((string)$permission["role"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_role->load(['code' => $role])) { continue; }
					if(!is_null($access)) {
						$display_roles[$t_role->getPrimaryKey()] = $access;
					} else {
						$this->addError("Role code or access value invalid for display {$display_code} (permission item with role code '{$role}')");
					}
				}

				if(sizeof($display_roles)>0) {
					$all_roles = $t_role->getRoleList();
					foreach($all_roles as $role_id => $role_info) {
						if (!isset($display_roles[$role_id])) { $display_roles[$role_id] = 0; }
					}
					$t_display->addUserRoles($display_roles);
				}
			}

		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function processDisplayPlacements($t_display, $placements) {
		$available_bundles = $t_display->getAvailableBundles(null, ['no_cache' => true]);

		// nuke previous placements. there shouldn't be any if we're installing from scratch.
		// if we're updating, we expect the list of restrictions to include all restrictions!
		if(sizeof($placements)) {
			$this->logStatus(_t('Successfully nuked all placements for display with code %1', $t_display->get('display_code')));
			$this->db->query('DELETE FROM ca_bundle_display_placements WHERE display_id = ?', $t_display->getPrimaryKey());
		}

		$i = 1;
		foreach($placements as $placement) {
			$code = $placement["code"];
			$bundle = (string)$placement['bundle'];

			$settings = $this->_processSettings(null, $placement['settings']);
			$t_display->addPlacement($bundle, $settings, $i, ['additional_settings' => $available_bundles[$bundle]['settings']]);
			if ($t_display->numErrors()) {
				$this->addError("There was an error while inserting display placement {$code}: ".join(" ",$t_display->getErrors()));
				return false;
			}

			$this->logStatus(_t('Added bundle placement %1 with code %2 for display with code %3', $bundle, $code, $t_display->get('display_code')));
			$i++;
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processSearchForms() {
		
		$forms = $this->parsed_data['searchForms'];

		if(sizeof($forms) == 0) { return true; }

		foreach($forms as $form) {
			$form_code = $form["code"];
			$system = $form["system"];
			$table = $form["type"];
			if (!($t_instance = \Datamodel::getInstanceByTableName($table, true))) { continue; }
			if (method_exists($t_instance, 'getTypeList') && !sizeof($t_instance->getTypeList())) { continue; } // no types configured
			if ($this->config->get($table.'_disable')) { continue; }
			$table_num = (int)\Datamodel::getTableNum($table);

			$this->logStatus(_t('Processing search form with code %1', $form_code));

			if(!($t_form = \ca_search_forms::find(['form_code' => (string)$form_code, 'table_num' => $table_num], ['returnAs' => 'firstModelInstance']))) {
				$t_form = new \ca_search_forms();
				$this->logStatus(_t('Search form with code %1 is new', $form_code));
			} else {
				$this->logStatus(_t('Search form with code %1 already exists', $form_code));
			}

			if($form["deleted"] && $t_form->getPrimaryKey()) {
				$this->logStatus(_t('Deleting search form with code %1', $form_code));
				$t_form->delete(true);
				continue;
			}

			$t_form->set("form_code", (string)$form_code);
			$t_form->set("is_system", $system ? 1 : 0);
			$t_form->set("table_num", $table_num);

			$this->_processSettings($t_form, $form['settings']);

			if($t_form->getPrimaryKey()) {
				$t_form->update();
			} else {
				$t_form->set("user_id", 1);		// let administrative user own these
				$t_form->insert();
			}

			if ($t_form->numErrors()) {
				$this->addError("There was an error while inserting search form {$form_code}: ".join(" ", $t_form->getErrors()));
			} else {
				$this->logStatus(_t('Successfully updated/inserted form with code %1', $form_code));

				self::addLabels($t_form, $form['labels']);
				if ($t_form->numErrors()) {
					$this->addError("There was an error while inserting search form label for {$form_code}: ".join(" ", $t_form->getErrors()));
				}
				if(!$this->processSearchFormPlacements($t_form, $form['bundles'])) {
					return false;
				}
			}
			
			if ($form['typeRestrictions']) {
				// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
				// if we're updating, we expect the list of restrictions to include all restrictions!
				if(sizeof($form['typeRestrictions'])) {
					$this->db->query('DELETE FROM ca_search_form_type_restrictions WHERE form_id = ?', $t_form->getPrimaryKey());
					$this->logStatus(_t('Successfully nuked all type restrictions for form with code %1', $form_code));
				}

				foreach($form['typeRestrictions'] as $restriction) {
					$restriction_code = trim($restriction["code"]);
					$type = trim($restriction["type"]);
					
					$t_form->addTypeRestriction(array_pop(caMakeTypeIDList($table_num, [$type], ['dontIncludeSubtypesInTypeRestriction' => true])), ['includeSubtypes' => (bool)$restriction['includeSubtypes'] ? 1 : 0]);

					if ($t_form->numErrors()) {
						$this->addError("There was an error while inserting type restriction {$restriction_code} in form {$form_code}: ".join("; ",$t_form->getErrors()));
					}

					$this->logStatus(_t('Added type restriction with code %1 and type %2 for form with code %3', $restriction_code, $type, $form_code));
				}
			}

			// set user and group access
			if($form['userAccess']) {
				$t_user = new \ca_users();
				$form_users = [];
				foreach($form['userAccess'] as $permission) {
					$user = trim($permission["user"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_user->load(['user_name' => $user])) { continue; }
					if($access) {
						$form_users[$t_user->getUserID()] = $access;
					} else {
						$this->addError("User name or access value invalid for search form {$form_code} (permission item with user name '{$user}')");
					}
				}

				if(sizeof($form_users)>0) {
					$t_form->addUsers($form_users);
				}
			}

			if($form['groupAccess']) {
				$t_group = new \ca_user_groups();
				$form_groups = [];
				foreach($form['groupAccess'] as $permission) {
					$group = trim($permission["group"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_group->load(['code' => $group])) { continue; }
					if($access) {
						$form_groups[$t_group->getPrimaryKey()] = $access;
					} else {
						$this->addError("Group code or access value invalid for search form {$form_code} (permission item with group code '{$group}')");
					}
				}

				if(sizeof($form_groups)>0) {
					$t_form->addUserGroups($form_groups);
				}
			}
			
			if($form['roleAccess']) {
				$t_role = new \ca_user_roles();
				$form_roles = [];
				foreach($form['roleAccess'] as $permission) {
					$role = trim($permission["role"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_role->load(['code' => $role])) { continue; }
					if(!is_null($access)) {
						$form_roles[$t_role->getPrimaryKey()] = $access;
					} else {
						$this->addError("Role code or access value invalid for form {$form_code} (permission item with role code '{$role}')");
					}
				}

				if(sizeof($form_roles)>0) {
					$all_roles = $t_role->getRoleList();
					foreach($all_roles as $role_id => $role_info) {
						if (!isset($form_roles[$role_id])) { $form_roles[$role_id] = 0; }
					}
					$t_form->addUserRoles($form_roles);
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function processSearchFormPlacements($t_form, $placements) {
		$available_bundles = $t_form->getAvailableBundles();

		// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
		// if we're updating, we expect the list of restrictions to include all restrictions!
		if(sizeof($placements)) {
			$this->logStatus(_t('Successfully nuked all placements for form with code %1', $t_form->get('form_code')));
			$this->db->query('DELETE FROM ca_search_form_placements WHERE form_id = ?', $t_form->getPrimaryKey());
		}

		$i = 1;
		foreach($placements as $placement) {
			$code = $placement["code"];
			$bundle = $placement['bundle'];

			$settings = $this->_processSettings(null, $placement['settings']);
			$t_form->addPlacement($bundle, $settings, $i, ['additional_settings' => $available_bundles[$bundle]['settings']]);
			if ($t_form->numErrors()) {
				$this->addError("There was an error while inserting search form placement {$code}: ".join(" ",$t_form->getErrors()));
				return false;
			}

			$this->logStatus(_t('Added bundle placement %1 with code %2 for form with code %3', $bundle, $code, $t_form->get('form_code')));
			$i++;
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processGroups() {
		// Create root group
		$t_user_group = \ca_user_groups::find(['code' => 'Root', 'parent_id' => null], ['returnAs' => 'firstModelInstance']);
		$t_user_group = $t_user_group ? $t_user_group : new \ca_user_groups();

		$t_user_group->set('name', 'Root');
		if($t_user_group->getPrimaryKey()) {
			$t_user_group->update();
		} else {
			$t_user_group->set('code', 'Root');
			$t_user_group->set('parent_id', null);
			$t_user_group->insert();
		}

		if ($t_user_group->numErrors()) {
			$this->addError("Errors creating root user group 'Root': ".join("; ",$t_user_group->getErrors()));
			return false;
		}
		
		$groups = $this->parsed_data['groups'];
		
		if (is_array($groups)) {
			foreach($groups as $group_code => $group) {
				if(!($t_group = \ca_user_groups::find(['code' => $group_code], ['returnAs' => 'firstModelInstance']))) {
					$t_group = new \ca_user_groups();
				}

				if($group["deleted"] && $t_group->getPrimaryKey()) {
					$t_group->delete(true);
					continue;
				}

				$t_group->set('name', trim($group['name']));
				$t_group->set('description', trim($group['description']));
				if($t_group->getPrimaryKey()) {
					$t_group->update();
				} else {
					$t_group->set('code', $group_code);
					$t_group->set('parent_id', null);
					$t_group->insert();
				}
				
				if($group['roles']) {
					$t_group->addRoles($group['roles']);
				}

				if ($t_group->numErrors()) {
					$this->addError("Errors inserting user group {$group_code}: ".join("; ",$t_group->getErrors()));
					return false;
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processLogins($create_admin_account=true) {
		$logins = $this->parsed_data['logins'];

		// If no logins are defined in the profile create an admin login with random password
		if (!sizeof($logins) && $create_admin_account) {
			$password = $this->createAdminAccount();
			return ['administrator' => $password];
		}
		

		$login_info = [];
		foreach($logins as $user_name => $login) {
			if (!($password = trim((string)$login["password"]))) {
				$password = caGenerateRandomPassword(8);
			}

			$t_user = new \ca_users();
			$t_user->set('user_name', $user_name = trim((string)$login["user_name"]));
			$t_user->set('password', $password);
			$t_user->set('fname',  trim((string)$login["fname"]));
			$t_user->set('lname',  trim((string)$login["lname"]));
			$t_user->set('email',  trim((string)$login["email"]));
			$t_user->set('active', 1);
			$t_user->set('userclass', 0);
			$t_user->insert();

			$roles = [];
			if($login['roles']) {
				foreach($login['roles'] as $role) {
					$roles[] = trim((string)$role);
				}
			}
			if (sizeof($roles)) { $t_user->addRoles($roles); }


			$groups = [];
			if($login['groups']) {
				foreach($login['groups'] as $group) {
					$groups[] = trim((string)$group);
				}
			}
			if (sizeof($groups)) { $t_user->addToGroups($groups); }

			if ($t_user->numErrors()) {
				$this->addError("Errors adding login {$user_name}: ".join("; ",$t_user->getErrors()));
				return false;
			}

			$login_info[$user_name] = $password;
		}

		return $login_info;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processMetadataAlerts() {
		$alerts = $this->parsed_data['metadataAlerts'];

		if(sizeof($alerts) == 0) { return true; }

		foreach($alerts as $alert) {
			$alert_code = $alert["code"];
			$table = $alert["type"];
			if (!($t_instance = \Datamodel::getInstanceByTableName($table, true))) { continue; }
			if (method_exists($t_instance, 'getTypeList') && !sizeof($t_instance->getTypeList())) { continue; } // no types configured
			if ($this->config->get($table.'_disable')) { continue; }
			$table_num = (int)\Datamodel::getTableNum($table);

			$this->logStatus(_t('Processing metadata alert with code %1', $alert_code));

			if(!($t_alert = \ca_metadata_alert_rules::find(['code' => (string)$alert_code, 'table_num' => $table_num], ['returnAs' => 'firstModelInstance']))) {
				$t_alert = new \ca_metadata_alert_rules();
				$this->logStatus(_t('Metadata alert with code %1 is new', $alert_code));
			} else {
				$this->logStatus(_t('Metadata alert with code %1 already exists', $alert_code));
			}

			if($alert["deleted"] && $t_alert->getPrimaryKey()) {
				$this->logStatus(_t('Deleting metadata alert with code %1', $alert_code));
				$t_alert->delete(true);
				continue;
			}

			$t_alert->set("code", $alert_code);
			$t_alert->set("table_num", $table_num);

			$this->_processSettings($t_alert, $alert['settings']);

			if($t_alert->getPrimaryKey()) {
				$t_alert->update();
			} else {
				$t_alert->set("user_id", 1);		// let administrative user own these
				$t_alert->insert();
			}
			
			if ($t_alert->numErrors()) {
				$this->addError("There was an error while inserting metadata alert {$alert_code}: ".join(" ",$t_alert->getErrors()));
			} else {
				$this->logStatus(_t('Successfully updated/inserted metadata alert with code %1', $alert_code));

				self::addLabels($t_alert, $alert['labels']);
				if ($t_alert->numErrors()) {
					$this->addError("There was an error while inserting metadata alert label for {$alert_code}: ".join(" ",$t_alert->getErrors()));
				}
				if(!$this->processMetadataAlertTriggers($t_alert, $alert['triggers'])) {
					return false;
				}
			}
			
			if ($alert['typeRestrictions']) {
				// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
				// if we're updating, we expect the list of restrictions to include all restrictions!
				if(sizeof($alert['typeRestrictions'])) {
					$this->db->query('DELETE FROM ca_metadata_alert_rule_type_restrictions WHERE rule_id = ?', [$t_alert->getPrimaryKey()]);
					$this->logStatus(_t('Successfully nuked all type restrictions for metadata alert with code %1', $alert_code));
				}

				foreach($alert['typeRestrictions'] as $restriction) {
					$restriction_code = trim($restriction["code"]);
					$type = trim($restriction["type"]);
					
					$t_alert->addTypeRestriction(array_pop(caMakeTypeIDList($table_num, [$type], ['dontIncludeSubtypesInTypeRestriction' => true])), ['includeSubtypes' => (bool)$restriction['includeSubtypes'] ? 1 : 0]);
					
					if ($t_alert->numErrors()) {
						$this->addError("There was an error while inserting type restriction {$restriction_code} in metadata alert {$alert_code}: ".join("; ",$t_alert->getErrors()));
					}

					$this->logStatus(_t('Added type restriction with code %1 and type %2 for metadata alert with code %3', $restriction_code, $type, $alert_code));
				}
			}

			// set user and group access
			if($alert['userAccess']) {
				$t_user = new \ca_users();
				$form_users = [];
				foreach($alert['userAccess'] as $permission) {
					$user = trim($permission["user"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if($access && $t_user->load(['user_name' => $user])) {
						$form_users[$t_user->getUserID()] = $access;
					} else {
						$this->addError("User name or access value invalid for metadata alert {$alert_code} (permission item with user name '{$user}')");
					}
				}

				if(sizeof($form_users)>0) {
					$t_alert->addUsers($form_users);
				}
			}

			if($alert['groupAccess']) {
				$t_group = new \ca_user_groups();
				$form_groups = [];
				foreach($alert['groupAccess'] as $permission) {
					$group = trim($permission["group"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if($access && $t_group->load(['code' => $group])) {
						$form_groups[$t_group->getPrimaryKey()] = $access;
					} else {
						$this->addError("Group code or access value invalid for metadata alert {$alert_code} (permission item with group code '{$group}')");
					}
				}

				if(sizeof($form_groups)>0) {
					$t_alert->addUserGroups($form_groups);
				}
			}
			
			if($alert['roleAccess']) {
				$t_role = new \ca_user_roles();
				$form_roles = [];
				foreach($alert['roleAccess'] as $permission) {
					$role = trim($permission["role"]);
					$access = $this->_convertUserGroupAccessStringToInt($permission['access']);

					if(!$t_role->load(['code' => $role])) { continue; }
					if(!is_null($access)) {
						$form_roles[$t_role->getPrimaryKey()] = $access;
					} else {
						$this->addError("Role code or access value invalid for metadata alert {$alert_code} (permission item with role code '{$role}')");
					}
				}

				if(sizeof($form_roles)>0) {
					$all_roles = $t_role->getRoleList();
					foreach($all_roles as $role_id => $role_info) {
						if (!isset($form_roles[$role_id])) { $form_roles[$role_id] = 0; }
					}
					$t_alert->addUserRoles($form_roles);
				}
			}
		}

		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function processMetadataAlertTriggers($t_alert, $triggers) {
		$available_triggers = $t_alert->getTriggers();

		// nuke previous restrictions. there shouldn't be any if we're installing from scratch.
		// if we're updating, we expect the list of restrictions to include all restrictions!
		if(sizeof($triggers)) {
			$this->logStatus(_t('Successfully nuked all triggers for metadata alert with code %1', $t_alert->get('code')));
			$this->db->query('DELETE FROM ca_metadata_alert_triggers WHERE rule_id = ?', [$t_alert->getPrimaryKey()]);
		}

		$i = 0;
		foreach($triggers as $trigger) {
			$code = $trigger["code"];
			$type = $trigger["type"];
			$metadata_element = $trigger["metadataElement"];
			$metadata_element_filter = $trigger["metadataElementFilter"];
			
			if (!($element_id = \ca_metadata_elements::getElementID($metadata_element))) { 
				$this->logStatus(_t('Skipped trigger %1 for metadata alert %2 because element code %3 is invalid', $code, $t_alert->get('code'), $metadata_element));
				continue; 
			}

			$settings = $this->_processSettings(null, $trigger['settings']);
			
			//<element code1>:<list item code>|<list item code>|...;<element code2>:<list item code>|<list item code>
			$metadata_element_filters = [];
			if(is_array($metadata_element_filters_raw = explode(';', $metadata_element_filter)) && sizeof($metadata_element_filters_raw)) {
				foreach($metadata_element_filters_raw as $metadata_element_filters_raw) {
					if(is_array($tmp = explode(":", $metadata_element_filters_raw)) && (sizeof($tmp) > 1)) {
						if ($t_element = \ca_metadata_elements::getInstance($tmp[0])) {
							if (is_array($item_ids = \ca_lists::IDNOsToItemIDs(explode("|", $tmp[1]))) && sizeof($item_ids)) {
								$item_ids = array_keys($item_ids);
								$metadata_element_filters[$tmp[0]] = $item_ids;
							}
						}
					}
				}
			}
			$t_trigger = new \ca_metadata_alert_triggers();
			$t_trigger->set('rule_id', $t_alert->get('rule_id'));
			$t_trigger->set('element_id', $element_id);
			$t_trigger->set('element_filters', $metadata_element_filters);
			$t_trigger->set('trigger_type', $type);
			
			foreach($settings as $setting => $setting_value) {
				switch($setting) {
					case 'notificationDeliveryOptions':
						$v = explode('|', $setting_value);
						break;
					default:
						$v = $setting_value;
						break;
				}
				if (!$t_trigger->setSetting($setting, $v)) {
					$this->logStatus(_t('Skipped setting %1 for trigger %2 on metadata alert %3 because value %4 is invalid', $setting, $code, $t_alert->get('code'), $setting_value));	
				}
			}
			
			$t_trigger->insert();
			
			if ($t_trigger->numErrors()) {
				$this->addError("There was an error while inserting metadata alert trigger {$code}: ".join(" ",$t_trigger->getErrors()));
				return false;
			}

			$this->logStatus(_t('Added trigger %1 for metadata alert %2', $code, $t_alert->get('code')));
			$i++;
		}
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processMiscHierarchicalSetup() {
		#
		# Create roots for storage locations hierarchies
		#
		$t_storage_location = new \ca_storage_locations();
		$t_storage_location->set('status', 0);
		$t_storage_location->set('parent_id', null);
		$t_storage_location->insert();

		if ($t_storage_location->numErrors()) {
			$this->addError("Errors inserting the storage location root: ".join("; ",$t_storage_location->getErrors()));
			return;
		}
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function createAdminAccount() {

		$password = caGenerateRandomPassword(8);
		$t_user = new \ca_users();
		$t_user->set("user_name", 'administrator');
		$t_user->set("password", $password);
		$t_user->set("email", $this->admin_email);
		$t_user->set("fname", 'CollectiveAccess');
		$t_user->set("lname", 'Administrator');
		$t_user->set("userclass", 0);
		$t_user->set("active", 1);
		$t_user->insert();

		if ($t_user->numErrors()) {
			$this->addError("Errors while adding the default administrator account: ".join("; ",$t_user->getErrors()));
			return false;
		}
		
		if(!$t_user->addRoles(['admin'])) {
			$this->addError("Could not add the <em>admin</em> role to the default administrator account: ".join("; ",$t_user->getErrors()));
			return false;
		}

		return $password;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function _processSettings($t_instance, $settings, $options=null) {
		$settings_list = [];
		
		$settings_info = caGetOption('settingsInfo', $options, []);

		if($settings) {
			foreach($settings as $setting_name => $values_by_locale) {
				foreach($values_by_locale as $locale => $values) {
					// some settings like 'label' or 'add_label' have 'locale' as sub-setting
					if($locale && isset($this->parsed_data['locales'][$locale])) {
						$locale_id = $this->locales[$locale];
					} else {
						$locale_id = null;
					}

					foreach($values as $setting_value) {
						if (isset($settings_info[$setting_name]) && isset($settings_info[$setting_name]['deferred']) && $settings_info[$setting_name]['deferred']) {
							$this->metadata_element_deferred_settings_processing[$t_instance->get('element_code')][$setting_name][] = $setting_value;
							continue;
						}

						if((strlen($setting_name)>0) && (strlen($setting_value)>0)) { // settings need at least name and value
							$datatype = (int)$t_instance ? $t_instance->get('datatype') : null;
							if ($setting_name === 'restrictToTypes' && $t_authority_instance = \AuthorityAttributeValue::elementTypeToInstance($datatype)){
								if ($t_authority_instance instanceof \BaseModelWithAttributes && is_string($setting_value)){
									$type_id = $t_authority_instance->getTypeIDForCode($setting_value);
									if ($type_id){
										$setting_value = $type_id;
									} else {
										$this->addError(
											_t('Failed to lookup type id for type restriction %1 in element %2 as could not retrieve type record. ',
												$setting_value,
												$t_instance->get('element_code')
											)
										);
									}
								}
							}
							if ($locale) { // settings with locale (those can't repeat)
								$settings_list[$setting_name][$locale] = $setting_value;
							} else {
								// some settings allow multiple values under the same key, for instance restrict_to_types.
								// in those cases $settings[$setting_name] becomes an array of values
								if (isset($settings_list[$setting_name]) && (!isset($settings_info[$setting_name]) || ($settings_info[$setting_name]['multiple']))) {
									if (!is_array($settings_list[$setting_name])) {
										$settings_list[$setting_name] = array($settings_list[$setting_name]);
									}
									$settings_list[$setting_name][] = $setting_value;
								} else {
									$settings_list[$setting_name] = $setting_value;
								}
							}
						}
					}

					if (is_object($t_instance)) {
						foreach($settings_list as $setting_name => $setting_value) {
							$t_instance->setSetting($setting_name, $setting_value);
						}
					}
				}
			}
		}

		return $settings_list;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function _convertACLStringToConstant($name) {
		switch($name) {
			case 'edit':
				return __CA_BUNDLE_ACCESS_EDIT__;
			case 'read':
				return __CA_BUNDLE_ACCESS_READONLY__;
			case 'none':
			default:
				return __CA_BUNDLE_ACCESS_NONE__;
		}
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function _convertUserGroupAccessStringToInt($name) {
		switch($name) {
			case 'read':
				return 1;
			case 'edit':
				return 2;
			default:
				return 0;
		}
	}
	# --------------------------------------------------
	/**
	 * @return bool
	 */
	protected function loggingStatus() {
		return $this->logging_status;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function logStatus($msg) {
		if($this->loggingStatus()) {
			$this->log->logInfo($msg);
		}
	}
	# --------------------------------------------------
}
