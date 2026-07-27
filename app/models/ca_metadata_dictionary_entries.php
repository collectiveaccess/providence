<?php
/** ---------------------------------------------------------------------
 * app/models/ca_metadata_dictionary_entries.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2026 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ModelSettings.php');
 
BaseModel::$s_ca_models_definitions['ca_metadata_dictionary_entries'] = array(
 	'NAME_SINGULAR' 	=> _t('Data dictionary entry'),
 	'NAME_PLURAL' 		=> _t('Data dictionary entries'),
 	'FIELDS' 			=> array(
 		'entry_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Entry id', 'DESCRIPTION' => 'Identifier for metadata dictionary entry'
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT,
				'DISPLAY_WIDTH' => 100, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Entry type'), 'DESCRIPTION' => _t('Type of item this entry displays for.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('objects') => 57,
					_t('object lots') => 51,
					_t('entities') => 20,
					_t('places') => 72,
					_t('occurrences') => 67,
					_t('collections') => 13,
					_t('storage locations') => 89,
					_t('loans') => 133,
					_t('movements') => 137,
					_t('tours') => 153,
					_t('tour stops') => 155,
					_t('object representations') => 56,
					_t('representation annotations') => 82,
					_t('sets') => 103,
					_t('set items') => 105,
					_t('lists') => 36,
					_t('list items') => 33,
					_t('search forms') => 121,
					_t('displays') => 124,
					_t('relationship types') => 79,
					_t('site pages') => 235
				)
		),
		'bundle_name' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => "300px", 'DISPLAY_HEIGHT' => 1,
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


class ca_metadata_dictionary_entries extends BundlableLabelableBaseModelWithAttributes {
	use ModelSettings;
	
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
	protected $LABEL_TABLE_NAME = 'ca_metadata_dictionary_entry_labels';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
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
	
	/**
	 *
	 */
	private $additional_settings = null;
	
	# ------------------------------------------------------
	/**
	 *
	 * @param int $pn_id Optional entry_id to load
	 * @param array $additional_settings Optional array of additional entry-level settings to support.
	 * @param array $setting_values Optional array of setting values to set.
	 */
	function __construct($id=null, ?array $options=null, $additional_settings=null, $setting_values=null) {
		parent::__construct($id, $options);
		
		//
		if (!is_array($additional_settings)) { $additional_settings = array(); }
		$this->additional_settings = $additional_settings;
		$this->setSettingDefinitionsForEntry($additional_settings);
		
		if (is_array($setting_values)) {
			$this->setSettings($setting_values);
		}
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($options=null) {
		parent::initLabelDefinitions($options);

		$this->BUNDLES['settings'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Data dictionary entry settings'));
		
		$this->BUNDLES['ca_metadata_dictionary_rules'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Rules'));
	}
	# ------------------------------------------------------
	/**
	  * Preload metadata dictionary entries for later use. You must preload the entries
	  * you require via ca_metadata_dictionary_entries::getEntry() before use, unless you 
	  * bypass the cache which will result in reduced performance.
	  *
	  * @param array $bundles List of bundles to preload dictionary entries for
	  * @return int The number of dictionary entries that were preloaded
	  */
	static public function preloadDefinitions($bundles) {
		if(!is_array($bundles) || !sizeof($bundles)) { return null; }
		
		$o_db = new Db();
		$qr_res = $o_db->query("
			SELECT * 
			FROM ca_metadata_dictionary_entries
			WHERE
				bundle_name IN (?)
		", [$bundles]);
		
		$c = 0;
		
		$idx = array_flip($bundles);
		while($qr_res->nextRow()) {
			$row = $qr_res->getRow();
			$row['settings'] = caUnserializeForDatabase($row['settings']);
			ca_metadata_dictionary_entries::$s_definition_cache[$row['entry_id']] = $row;
			ca_metadata_dictionary_entries::$s_definition_cache_index[$row['bundle_name']][$row['entry_id']] = 1;
			unset($idx[$row['bundle_name']]);
			$c++;
		}
		
		foreach($idx as $k => $d) {
			ca_metadata_dictionary_entries::$s_definition_cache_index[$k] = null;
		}
		return $c;
	}
	# ------------------------------------------------------
	/**
	 * Get list of entries 
	 *
	 * @return array|null
	 */
	static public function getEntries(?array $options=null) {
		if (!($o_db = caGetOption('db', $options, null))) { $o_db = new Db(); }
		
		$t = new ca_metadata_dictionary_entries();
		$qr = $o_db->query("
			SELECT entry_id
			FROM ca_metadata_dictionary_entries
		");
		
		$label_cache = $t->getPreferredDisplayLabelsForIDs($qr->getAllFieldValues('entry_id'));
		$qr = $o_db->query("
			SELECT entry_id, count(*) c
			FROM ca_metadata_dictionary_rules 
			GROUP BY entry_id
		");
		$rule_counts = [];
		while($qr->nextRow()) {
			$rule_counts[$qr->get('entry_id')] = $qr->get('c');
		}
		
		$qr = $o_db->query("
			SELECT cmde.*
			FROM ca_metadata_dictionary_entries cmde
		");
		
		$entries = [];
		while($qr->nextRow()) {
			$row = $qr->getRow();
			$row['settings'] = caUnserializeForDatabase($row['settings']);
			$row['label'] = $label_cache[$entry_id = $row['entry_id']];
			$row['bundle_label'] = caGetOption('label', $row['settings'], caGetDisplayLabelForBundle($row['bundle_name']), ['defaultOnEmptyString' => true]);
			$row['numRules'] = $rule_counts[$entry_id];
			$entries[$entry_id] = $row;
		}
		
		return $entries;
	}
	# ------------------------------------------------------
	/**
	  * Sets setting definitions for to use for the current entry. Note that these definitions persist no matter what row is loaded
	  * (or even if no row is loaded). You can set the definitions once and reuse the instance for many entries. All will have the set definitions.
	  *
	  * @param $additional_settings array Array of settings definitions
	  *
	  * @return bool Always returns true
	  */
	public function setSettingDefinitionsForEntry($additional_settings) {
		if (!is_array($additional_settings)) { $additional_settings = []; }
		
		$standard_settings = [	
			'mandatory' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_CHECKBOXES,
				'width' => "10", 'height' => "1",
				'takesLocale' => false,
				'default' => 0,
				'label' => _t('Is mandatory?'),
				'description' => _t('If checked data entry bundle is mandatory and a valid value must be set before it can be saved.')
			),
			'definition' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => "660px", 'height' => "400px",
				'takesLocale' => true,
				'usewysiwygeditor' => (bool)Configuration::load()->get('use_wysiwyg_editor_for_dictionary_definitions'),
				'label' => _t('Definition'),
				'description' => _t('Extended text describing standards for entry in this data entry bundle.')
			),
			'restrict_to_types' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'useList' => null,
				'width' => "200px", 'height' => 5,
				'takesLocale' => false,
				'multiple' => 1,
				'default' => '',
				'label' => _t('Restrict to types'),
				'description' => _t('Restricts entry to items of the specified type(s). Leave all unchecked for no restriction.')
			),
			'restrict_to_relationship_types' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'useRelationshipTypeList' => null,
				'width' => "200px", 'height' => 5,
				'takesLocale' => false,
				'multiple' => 1,
				'default' => '',
				'label' => _t('Restrict to relationship types'),
				'description' => _t('Restricts entry to items related using the specified relationship type(s). Leave all unchecked for no restriction.')
			)
		];
		
		if ($bundle = $this->get('bundle_name')) {
			$tmp = explode('.', $bundle);
			if (($t = Datamodel::getInstance($tmp[0], true)) && method_exists($t, 'getTypeListCode')) {
				$standard_settings['restrict_to_types']['useList'] = $t->getTypeListCode();
				
				$path = Datamodel::getPath($tmp[0], $this->get('table_num'));
				$path = array_keys($path);
				$standard_settings['restrict_to_relationship_types']['useRelationshipTypeList'] = $path[1];
			}
		}
		$this->setAvailableSettings(array_merge($standard_settings, $additional_settings));
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function load($id=null, $use_cache=true) {
		if($r = parent::load($id, $use_cache)) {
			$this->setSettingDefinitionsForEntry($this->additional_settings);
		}
		return $r;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function set($fields, $value="", $options=null) {
		$r = parent::set($fields, $value, $options);
		if ($this->changed('bundle_name')) { 
			$this->setSettingDefinitionsForEntry($this->additional_settings);
		}
		return $r;
	}
	# ------------------------------------------------------
	/**
	 * Get list of rules for currently loaded row
	 * @return array|null
	 */
	public function getRules($options=null) {
		if(!is_array($options)) { $options = []; }
		if(!($id = $this->getPrimaryKey())) { return null; }
		
		$for_editing_form = caGetOption('forEditingForm', $options, false);
		
		if(!caGetOption('noCache', $options, false) && ($cache_key = caMakeCacheKeyFromOptions($options, $id)) && MemoryCache::contains($cache_key, 'MDDictRuleList')) {
			return MemoryCache::fetch($cache_key, 'MDDictRuleList');
		}

		$o_db = $this->getDb();

		$qr_rules = $o_db->query("
			SELECT * 
			FROM ca_metadata_dictionary_rules 
			WHERE
				entry_id = ?
			ORDER BY rule_id
		", [$id]);

		$return = [];

		while($qr_rules->nextRow()) {
			$rule_id = $qr_rules->get('rule_id');
			$return[$rule_id] = $qr_rules->getRow();
			
			$settings = caUnserializeForDatabase($qr_rules->get('settings'));
			if ($for_editing_form) {
				if (is_array($settings)) {
					foreach($settings as $setting => $v) {
						if(is_array($v)) { 
							foreach($v as $locale => $vl) {
								$return[$rule_id]["{$setting}_{$locale}"] = $vl;	
							}
						} else {
							$return[$rule_id][$setting] = $v;		
						}
					}
					unset($return[$rule_id]['settings']);
				}
			} else {
				$return[$rule_id]['settings'] = $settings;
			}
		}

		MemoryCache::save($cache_key, $return, 'MDDictRuleList');

		return $return;
	}
	# ------------------------------------------------------
	/**
	 * Check for existence of a dictionary entry for a bundle and return cache indices if it exists.
	 *
	 * @param string $bundle_name The bundle name to find a dictionary entry for. 
	 * @param array $options Options include:
	 *		noCache = Bypass cache (typically loaded using ca_metadata_dictionary_entries::preloadDefinitions()) and check entry directly. [Default=false]
	 *
	 * @return array List of entry_ids for the specified bundle if it exists. These can be plugged into the ca_metadata_dictionary_entries::$s_definition_cache cache array to get entry data. Returns false if the bundle does not exist.
	 */
	public static function entryExists($bundle_name, $options=null) {
		if (caGetOption('noCache', $options, false) || (array_key_exists($bundle_name, ca_metadata_dictionary_entries::$s_definition_cache_index ?? []) && is_null(ca_metadata_dictionary_entries::$s_definition_cache_index[$bundle_name]))) {
			ca_metadata_dictionary_entries::preloadDefinitions(array($bundle_name));
		}
		
		if (
			isset(ca_metadata_dictionary_entries::$s_definition_cache_index[$bundle_name]) 
			&& 
			is_array(ca_metadata_dictionary_entries::$s_definition_cache_index[$bundle_name])
			&&
			sizeof(ca_metadata_dictionary_entries::$s_definition_cache_index[$bundle_name])
		) {
			return ca_metadata_dictionary_entries::$s_definition_cache_index[$bundle_name];
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Get a dictionary entry for a bundle. Entries are matched first on bundle name, and then filtered on any restrict_to_types
	 * and restrict_to_relationship_types settings in the $settings parameter. This allows you to have different dictionary entries
	 * for the same bundle name subject to type restrictions set in the user interface. For example, if you have a ca_entities bundle (related
	 * entities) you can have different dictionary entries return when ca_entities is restricted to authors vs. publishers.
	 *
	 * @param string $bundle_name The bundle name to find a dictionary entry for. 
	 * @param BaseModel $t_subject 
	 * @param array $settings Bundle settings to use when matching definitions. The bundle settings restrict_to_types and restrict_to_relationship_types will be used, when present, to find type-restricted dictionary entries.
	 * @param array $options Options include:
	 *		noCache = Bypass cache (typically loaded using ca_metadata_dictionary_entries::preloadDefinitions()) and check entry directly. [Default=false]
	 *
	 * @return array An array with entry data. Keys are entry field names. The 'settings' key contains the label, definition text and any type restrictions. Returns null if no entry is defined.
	 */
	public static function getEntry($bundle_name, $t_subject, $settings=null, $options=null) {
		$subject_table_name = $t_subject->tableName();
		$subject_table_num = $t_subject->tableNum();
		
		if(!is_array($types = caGetOption(['restrict_to_types', 'restrictToTypes'], $settings, null)) && $types) {
			$types = [$types];
		}
		if(!is_array($types)) { $types = [$t_subject->getTypeID()]; }
		if(sizeof($types = array_filter($types, 'strlen')) && ($t_instance = Datamodel::getInstance($bundle_name)) && method_exists($bundle_name, 'getTypeCode') ) {
			$types = array_merge($types, caMakeTypeIDList($bundle_name, $types, ['dontIncludeSubtypesInTypeRestriction' => true]) ?? []);
		}
		
		if(!is_array($relationship_types = caGetOption(['restrict_to_relationship_types', 'restrictToRelationshipTypes'], $settings, null)) && $relationship_types) {
			$relationship_types = [$relationship_types];
		}
		if(!is_array($relationship_types)) { $relationship_types = []; }
		if (sizeof($relationship_types = array_filter($relationship_types, 'strlen'))) {
			$relationship_types = array_merge($relationship_types, ca_relationship_types::relationshipTypeIDsToTypeCodes($relationship_types) ?? []);
		}
		if ($entry_list = ca_metadata_dictionary_entries::entryExists($bundle_name)) {
			$entry_id = null;
			
			foreach(array_keys($entry_list) as $id) {
				$entry = ca_metadata_dictionary_entries::$s_definition_cache[$id];
				
				if($entry['table_num'] != $subject_table_num) { continue; }
				
				if (is_array($tables = ($entry['settings']['restrict_to'] ?? null)) && sizeof($tables)) {
					if(in_array($subject_table_name, $tables)) { 
						$entry_id = $id;
					} else {
						$entry_id = null;
					}
				} else {
					$entry_id = $id;
				}
				
				if(!is_array($res_types = $entry['settings']['restrict_to_types'] ?? [])) {
					$res_types = [$res_types];
				}
				$entry_types = array_filter($res_types ?? [], 'strlen');
				if(!is_array($rel_types = $entry['settings']['restrict_to_relationship_types'] ?? [])) {
					$rel_types = [$rel_types];
				}
				$entry_relationship_types = array_filter($rel_types, 'strlen');
		
				if($entry_id) {
					if ((sizeof($types) || sizeof($relationship_types))) {
						if (sizeof($relationship_types)) {
							if(is_array($entry_relationship_types) && sizeof($entry_relationship_types)) {
								if (sizeof(array_intersect($relationship_types, $entry_relationship_types))) {
									$entry_id = $id;
								} else {
									$entry_id = null;
									continue;
								}
							}
						}
						if (sizeof($types)) {
							if(is_array($entry_types) && sizeof($entry_types)) {
								if (sizeof(array_intersect($types, $entry_types))) {
									$entry_id = $id;
								} else {
									$entry_id = null;
									continue;
								}
							}
						}
					} elseif((sizeof($types) && !sizeof($entry_types)) || (!sizeof($types) && sizeof($entry_types))) {
						$entry_id = null;
					} elseif((sizeof($relationship_types) && !sizeof($entry_relationship_types)) || (!sizeof($relationship_types) && sizeof($entry_relationship_types))) {
						$entry_id = null;
					}
				}
				if ($entry_id) { break; }
			}
			
			if (!$entry_id)  { return null; }
			return ca_metadata_dictionary_entries::$s_definition_cache[$entry_id];
		}
		
		return null;
	}
	
	# ------------------------------------------------------
	/**
	 * Render editor bundle for dictionary entry rules
	 *
	 * @param RequestHTTP $request
	 * @param string $form_name
	 * @param string $placement_code
	 * @param array $bundle_settings
	 * @param array $options
	 * @return string
	 */
	public function getRulesHTMLFormBundle($request, $form_name, $placement_code, ?array $bundle_settings=[], ?array $options=[]) {
		$o_view = new View($request, $request->getViewsDirectoryPath().'/bundles/');

		$o_view->setVar('id_prefix', $form_name);
		$o_view->setVar('placement_code', $placement_code);
		$o_view->setVar('request', $request);

		if(!($table_num = $this->get('table_num'))) { return null; }

		$o_view->setVar('table_num', $table_num);

		$t_rule = new ca_metadata_dictionary_rules();
		
		$o_view->setVar('rules', $this->getRules(['forEditingForm' => true]));
		
		$o_view->setVar('t_entry', $this);
		$o_view->setVar('t_rule', $t_rule);
		
		$settings_values = $settings_tags = [];
		foreach($t_rule->getAvailableSettings() as $setting => $setting_info) {
			if (isset($setting_info['takesLocale'])) {
				foreach($locales = ca_locales::getCataloguingLocaleCodes() as $locale) {
					$settings_values[$setting][$locale] = "{{"."{$setting}_{$locale}"."}}";	
					$settings_tags[] = "{$setting}_{$locale}";
				}
				
			} else {
				$settings_values[$setting] = "{{".$setting."}}";
				$settings_tags[] = $setting;
			}
		}
		$o_view->setVar('settings_values_list', $settings_values);
		$o_view->setVar('settings_tags', $settings_tags);

		return $o_view->render('ca_metadata_dictionary_rules.php');
	}
	# ------------------------------------------------------
	/**
	 * Save trigger bundle
	 *
	 * @param $request
	 * @param $form_prefix
	 * @param $placement_code
	 */
	public function saveRuleHTMLFormBundle($request, $form_prefix, $placement_code) {
		if (!($entry_id = $this->getPrimaryKey())) { return null; }
		$id_prefix = $placement_code.$form_prefix;

		$rules = $this->getRules();
		
		$t_rule = new ca_metadata_dictionary_rules();
		$available_settings = $t_rule->getAvailableSettings();
		$settings_list = array_keys($available_settings);
		
		// find settings keys in request and set them
		$adds = $edits = $deletes = $settings = [];
		
		foreach($_REQUEST as $k => $vm_v) {
			if(
				preg_match("/^{$id_prefix}_(.+?)_(new_[\d]+|[\d]+)_([A-Za-z]{2}_[A-Za-z]{2})$/u", $k, $matches)
				||
				preg_match("/^{$id_prefix}_(.+?)_(new_[\d]+|[\d]+)$/u", $k, $matches)	
			) {
				$rule_id = $matches[2];
				if (!isset($rules[$rule_id]) || !is_array($rules[$rule_id])) {
				    $rules[$rule_id] = [];
				}
                $setting = $matches[1];
                if (in_array($setting, $settings_list)) {
                    if ($locale = isset($matches[3]) ? $matches[3] : null) {
                        $settings[$rule_id][$setting][$locale] = $vm_v;
                    } else {
                        $settings[$rule_id][$setting] = $vm_v;
                    }
                    continue;
                }
			}
			if(preg_match("/^{$id_prefix}_(.+?)_new_([\d]+)$/u", $k, $matches)) {
				$adds[$matches[2]][$matches[1]] = $vm_v;
			} elseif(preg_match("/^{$id_prefix}_(.+)_([\d]+)$/u", $k, $matches)) {
				$edits[$matches[2]][$matches[1]] = $vm_v;
			} elseif(preg_match("/^{$id_prefix}_([\d]+)_delete$/u", $k, $matches)) {
				$deletes[$matches[1]] = true;
			}
		}
		
		$t_rule = new ca_metadata_dictionary_rules();
		
		foreach(array_keys($deletes) as $rule_id) {
			if (!isset($rules[$rule_id])) { continue; }
			if (!$t_rule->load($rule_id)) { continue; }
			
			$t_rule->delete(true);
			if($t_rule->numErrors() > 0) {
				$this->errors = $t_rule->errors;
				return false;
			}
		}

		foreach($adds as $i => $content) {
			$t_rule = new ca_metadata_dictionary_rules();
			$t_rule->set('entry_id', $entry_id);
			foreach(['rule_code', 'rule_level', 'expression'] as $f) {
				if(!isset($content[$f])) { continue; }
				$t_rule->set($f, $content[$f]);
			}
			if(is_array($settings["new_{$i}"])) {
				foreach($settings["new_{$i}"] as $setting => $by_locale) {
					$t_rule->setSetting($setting, $by_locale);
				}
			}
			
			$t_rule->insert();
			if($t_rule->numErrors() > 0) {
				$this->errors = $t_rule->errors;
				return false;
			}
		}
		foreach($edits as $rule_id => $content) {
			if (!isset($rules[$rule_id])) { continue; }
			if (!$t_rule->load($rule_id)) { continue; }
			foreach(['rule_code', 'rule_level', 'expression'] as $f) {
				if(!isset($content[$f])) { continue; }
				$t_rule->set($f, $content[$f]);
			}
			
			if(is_array($settings[$rule_id])) {
				foreach($settings[$rule_id] as $setting => $by_locale) {
					$t_rule->setSetting($setting, $by_locale);
				}
			}
			
			$t_rule->update();
			if($t_rule->numErrors() > 0) {
				$this->errors = $t_rule->errors;
				return false;
			}
		}

		
		MemoryCache::flush('MDDictRuleList');
		return true;
	}
	# ------------------------------------------------------
}
