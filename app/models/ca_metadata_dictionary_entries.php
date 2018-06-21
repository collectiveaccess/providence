<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_dictionary_entries.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */
 
require_once(__CA_LIB_DIR__.'/ModelSettings.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_dictionary_rules.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');

global $_ca_metadata_dictionary_entry_settings;
$_ca_metadata_dictionary_entry_settings = array(		// global
	'label' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 30, 'height' => 1,
		'takesLocale' => true,
		'label' => _t('Label to place on entry'),
		'description' => _t('Custom label text to use for this entry.')
	),
	'definition' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 30, 'height' => 5,
		'takesLocale' => true,
		'label' => _t('Descriptive text for bundle.'),
		'description' => _t('Definition text to display for this bundle.')
	),
	'mandatory' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'width' => "10", 'height' => "1",
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Bundle is mandatory'),
		'description' => _t('Bundle is mandatory and a valid value must be set before it can be saved.')
	),
	'restrict_to' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 35, 'height' => 5,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Restrict to'),
		'description' => _t('Restrict entry to specific table.')
	),
	'restrict_to_types' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 35, 'height' => 5,
		'takesLocale' => false,
		'multiple' => 1,
		'default' => '',
		'label' => _t('Restrict to types'),
		'description' => _t('Restricts entry to items of the specified type(s). Leave all unchecked for no restriction.')
	),
	'restrict_to_relationship_types' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 35, 'height' => 5,
		'takesLocale' => false,
		'multiple' => 1,
		'default' => '',
		'label' => _t('Restrict to relationship types'),
		'description' => _t('Restricts entry to items related using the specified relationship type(s). Leave all unchecked for no restriction.')
	)
	
);

BaseModel::$s_ca_models_definitions['ca_metadata_dictionary_entries'] = array(
 	'NAME_SINGULAR' 	=> _t('Metadata dictionary entry'),
 	'NAME_PLURAL' 		=> _t('Metadata dictionary entries'),
 	'FIELDS' 			=> array(
 		'entry_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Entry id', 'DESCRIPTION' => 'Identifier for metadata dictionary entry'
		),
		'bundle_name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Bundle name'), 'DESCRIPTION' => _t('Bundle name'),
				'BOUNDS_VALUE' => array(1,255)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Settings')
		)
 	)
);


class ca_metadata_dictionary_entries extends BaseModel {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_metadata_dictionary_entries';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'entry_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('bundle_name');

	# When the list of "list fields" above contains more than one field,
	# the LIST_DELIMITER text is displayed between fields as a delimiter.
	# This is typically a comma or space, but can be any string you like
	protected $LIST_DELIMITER = ' ';

	# What you'd call a single record from this table (eg. a "person")
	protected $NAME_SINGULAR;

	# What you'd call more than one record from this table (eg. "people")
	protected $NAME_PLURAL;

	# List of fields to sort listing of records by; you can use 
	# SQL 'ASC' and 'DESC' here if you like.
	protected $ORDER_BY = array('entry_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = 'rank';
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	null;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_PARENT_ID_FLD		=	null;
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = true;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	/**
	 * Array of preloaded definitions, indexed by entry_id
	 */
	static $s_definition_cache;
	
	/**
	 * Index array converting bundle names to entry_id's
	 */
	static $s_definition_cache_index;
	
	/**
	 * Index array converting bundle names to entry_id's
	 */
	static $s_definition_cache_relationship_type_ids;
	
	# ------------------------------------------------------
	/**
	 *
	 * @param int $pn_id Optional entry_id to load
	 * @param array $pa_additional_settings Optional array of additional entry-level settings to support.
	 * @param array $pa_setting_values Optional array of setting values to set.
	 */
	function __construct($pn_id=null, $pa_additional_settings=null, $pa_setting_values=null) {
		parent::__construct($pn_id);
		
		//
		if (!is_array($pa_additional_settings)) { $pa_additional_settings = array(); }
		$this->setSettingDefinitionsForEntry($pa_additional_settings);
		
		if (is_array($pa_setting_values)) {
			$this->setSettings($pa_setting_values);
		}
	}
	# ------------------------------------------------------
	/**
	  * Preload metadata dictionary entries for later use. You must preload the entries
	  * you require via ca_metadata_dictionary_entries::getEntry() before use, unless you 
	  * bypass the cache which will result in reduced performance.
	  *
	  * @param array $pa_bundles List of bundles to preload dictionary entries for
	  * @return int The number of dictionary entries that were preloaded
	  */
	static public function preloadDefinitions($pa_bundles) {
		if(!is_array($pa_bundles) || !sizeof($pa_bundles)) { return null; }
		
		
		$o_db = new Db();
		$qr_res = $o_db->query("
			SELECT * 
			FROM ca_metadata_dictionary_entries
			WHERE
				bundle_name IN (?)
		", array($pa_bundles));
		
		$vn_c = 0;
		
		$va_type_ids = array();
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			$va_row['settings'] = caUnserializeForDatabase($va_row['settings']);
			ca_metadata_dictionary_entries::$s_definition_cache[$va_row['entry_id']] = $va_row;
			ca_metadata_dictionary_entries::$s_definition_cache_index[$va_row['bundle_name']][$va_row['entry_id']] = 1;
			
			$vn_c++;
		}
		
		return $vn_c;
	}
	# ------------------------------------------------------
	/**
	  * Sets setting definitions for to use for the current entry. Note that these definitions persist no matter what row is loaded
	  * (or even if no row is loaded). You can set the definitions once and reuse the instance for many entries. All will have the set definitions.
	  *
	  * @param $pa_additional_settings array Array of settings definitions
	  *
	  * @return bool Always returns true
	  */
	public function setSettingDefinitionsForEntry($pa_additional_settings) {
		if (!is_array($pa_additional_settings)) { $pa_additional_settings = array(); }
		global $_ca_metadata_dictionary_entry_settings;
		$this->SETTINGS = new ModelSettings($this, 'settings', array_merge($_ca_metadata_dictionary_entry_settings, $pa_additional_settings));
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Get list of rules for currently loaded row
	 * @return array|null
	 */
	public function getRules() {
		if(!$this->getPrimaryKey()) { return null; }

		if(MemoryCache::contains($this->getPrimaryKey(), 'MDDictRuleList')) {
			return MemoryCache::fetch($this->getPrimaryKey(), 'MDDictRuleList');
		}

		$o_db = $this->getDb();

		$qr_rules = $o_db->query("
			SELECT * FROM ca_metadata_dictionary_rules ORDER BY rule_id
		");

		$va_return = array();

		while($qr_rules->nextRow()) {
			$va_return[$qr_rules->get('rule_id')] = $qr_rules->getRow();
			$va_return[$qr_rules->get('rule_id')]['settings'] = caUnserializeForDatabase($qr_rules->get('settings'));
		}

		MemoryCache::save($this->getPrimaryKey(), $va_return, 'MDDictRuleList');

		return $va_return;
	}
	# ------------------------------------------------------
	/**
	 * Check for existence of a dictionary entry for a bundle and return cache indices if it exists.
	 *
	 * @param string $ps_bundle_name The bundle name to find a dictionary entry for. 
	 * @param array $pa_options Options include:
	 *		noCache = Bypass cache (typically loaded using ca_metadata_dictionary_entries::preloadDefinitions()) and check entry directly. [Default=false]
	 *
	 * @return array List of entry_ids for the specified bundle if it exists. These can be plugged into the ca_metadata_dictionary_entries::$s_definition_cache cache array to get entry data. Returns false if the bundle does not exist.
	 */
	public static function entryExists($ps_bundle_name, $pa_options=null) {
		if (caGetOption('noCache', $pa_options, false)) {
			ca_metadata_dictionary_entries::preloadDefinitions(array($ps_bundle_name));
		}
		
		if (
			isset(ca_metadata_dictionary_entries::$s_definition_cache_index[$ps_bundle_name]) 
			&& 
			is_array(ca_metadata_dictionary_entries::$s_definition_cache_index[$ps_bundle_name])
			&&
			sizeof(ca_metadata_dictionary_entries::$s_definition_cache_index[$ps_bundle_name])
		) {
			return ca_metadata_dictionary_entries::$s_definition_cache_index[$ps_bundle_name];
		}
		
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Get a dictionary entry for a bundle. Entries are matched first on bundle name, and then filtered on any restrict_to_types
	 * and restrict_to_relationship_types settings in the $pa_settings parameter. This allows you to have different dictionary entries
	 * for the same bundle name subject to type restrictions set in the user interface. For example, if you have a ca_entities bundle (related
	 * entities) you can have different dictionary entries return when ca_entities is restricted to authors vs. publishers.
	 *
	 * @param string $ps_bundle_name The bundle name to find a dictionary entry for. 
	 * @param BaseModel $pt_subject 
	 * @param array $pa_settings Bundle settings to use when matching definitions. The bundle settings restrict_to_types and restrict_to_relationship_types will be used, when present, to find type-restricted dictionary entries.
	 * @param array $pa_options Options include:
	 *		noCache = Bypass cache (typically loaded using ca_metadata_dictionary_entries::preloadDefinitions()) and check entry directly. [Default=false]
	 *
	 * @return array An array with entry data. Keys are entry field names. The 'settings' key contains the label, definition text and any type restrictions. Returns null if no entry is defined.
	 */
	public static function getEntry($ps_bundle_name, $pt_subject, $pa_settings=null, $pa_options=null) {
		if (caGetOption('noCache', $pa_options, false)) {
			ca_metadata_dictionary_entries::preloadDefinitions(array($ps_bundle_name));
		}
		
		if(!is_array($va_types = caGetOption('restrict_to_types', $pa_settings, null)) && $va_types) {
			$va_types = array($va_types);
		}
		if(!is_array($va_types)) { $va_types = array(); }
		if(sizeof($va_types = array_filter($va_types, 'strlen'))) {
			$va_types = ca_lists::itemIDsToIDNOs($va_types);
		}
		
		if(!is_array($va_relationship_types = caGetOption('restrict_to_relationship_types', $pa_settings, null)) && $va_relationship_types) {
			$va_relationship_types = array($va_relationship_types);
		}
		if(!is_array($va_relationship_types)) { $va_relationship_types = array(); }
		if (sizeof($va_relationship_types = array_filter($va_relationship_types, 'strlen'))) {
			$va_relationship_types = ca_relationship_types::relationshipTypeIDsToTypeCodes($va_relationship_types);
		}
		
		if ($va_entry_list = ca_metadata_dictionary_entries::entryExists($ps_bundle_name)) {
			$vn_entry_id = null;
			
			//if (sizeof($va_types) || sizeof($va_relationship_types)) {
				foreach(array_keys($va_entry_list) as $vn_id) {
					$va_entry = ca_metadata_dictionary_entries::$s_definition_cache[$vn_id];
					if (is_array($va_tables = $va_entry['settings']['restrict_to']) && sizeof($va_tables)) {
						if(in_array($pt_subject->tableName(), $va_tables)) { 
							$vn_entry_id = $vn_id;
						} else {
							$vn_entry_id = null;
							continue;
						}
					}
					if (sizeof($va_relationship_types)) {
						if(
							is_array($va_entry_types = $va_entry['settings']['restrict_to_relationship_types'])
						) {
							if (sizeof(array_intersect($va_relationship_types, $va_entry_types))) {
								$vn_entry_id = $vn_id;
							} else {
								$vn_entry_id = null;
								continue;
							}
						}
					}
					if (sizeof($va_types)) {
						if(
							is_array($va_entry_types = $va_entry['settings']['restrict_to_types'])
						) {
							if (sizeof(array_intersect($va_types, $va_entry_types))) {
								$vn_entry_id = $vn_id;
							} else {
								$vn_entry_id = null;
								continue;
							}
						}
					}
					
					if ($vn_entry_id) { break; }
				}
			//}
			
			if (!$vn_entry_id)  { $vn_entry_id = array_pop(array_keys($va_entry_list)); }
			return ca_metadata_dictionary_entries::$s_definition_cache[$vn_entry_id];
		}
		
		return null;
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->SETTINGS);
	}
	# ------------------------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 */
	public function __call($ps_name, $pa_arguments) {
		if (method_exists($this->SETTINGS, $ps_name)) {
			return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
		}
		die($this->tableName()." does not implement method {$ps_name}");
	}
	# ------------------------------------------------------
}