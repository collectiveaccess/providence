<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_importers.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2018 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BundlableLabelableBaseModelWithAttributes.php');
require_once(__CA_LIB_DIR__.'/Import/DataReaderManager.php');
require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');
require_once(__CA_LIB_DIR__.'/ProgressBar.php');
require_once(__CA_MODELS_DIR__."/ca_data_importer_labels.php");
require_once(__CA_MODELS_DIR__."/ca_data_importer_groups.php");
require_once(__CA_MODELS_DIR__."/ca_data_importer_items.php");
require_once(__CA_MODELS_DIR__."/ca_data_import_events.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_sets.php");
require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');
require_once(__CA_LIB_DIR__.'/Parsers/ExpressionParser.php');
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__.'/Db/Transaction.php');


BaseModel::$s_ca_models_definitions['ca_data_importers'] = array(
 	'NAME_SINGULAR' 	=> _t('data importer'),
 	'NAME_PLURAL' 		=> _t('data importers'),
	'FIELDS' 			=> array(
		'importer_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this importer')
		),
		'importer_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Importer code'), 'DESCRIPTION' => _t('Unique alphanumeric identifier for this importer.'),
				'UNIQUE_WITHIN' => array()
				//'REQUIRES' => array('is_administrator')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
				'DONT_USE_AS_BUNDLE' => true,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Importer type'), 'DESCRIPTION' => _t('Indicates type of item importer is used for.'),
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
					_t('lists') => 36,
					_t('list items') => 33
				)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Importer settings')
		),
		'rules' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Rules'), 'DESCRIPTION' => _t('Importer rules')
		),
		'worksheet' => array(
				'FIELD_TYPE' => FT_FILE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				"FILE_VOLUME" => 'workspace',
				'DEFAULT' => '',
				'LABEL' => _t('Importer worksheet'), 'DESCRIPTION' => _t('Archived copy of worksheet used to create the importer.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the importer is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		)
	)
);
	
class ca_data_importers extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_data_importers';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'importer_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('importer_id');

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
	protected $ORDER_BY = array('importer_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
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
	protected $LABEL_TABLE_NAME = 'ca_data_importer_labels';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'importer_code';	// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;			// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	
	public static $s_num_import_errors = 0;
	public static $s_num_records_processed = 0;
	public static $s_num_records_skipped = 0;
	public static $s_import_error_list = array();
	
	
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		// Filter list of tables importers can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_data_importers']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = (BaseModel::$s_ca_models_definitions['ca_data_importers']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);
		
		parent::__construct($pn_id);
		
		$this->initSettings();
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
	}
	# ------------------------------------------------------
	protected function initSettings() {
		$va_settings = array();
		
		$va_settings['inputFormats'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 5,
			'takesLocale' => false,
			'default' => '',
			'options' => ca_data_importers::getAvailableInputFormats(),
			'label' => _t('Importer data types'),
			'description' => _t('Set data types for which this importer is usable.  Ex. XLSX, XLS, MYSQL')
		);
		$va_settings['type'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Record type'),
			'description' => _t('Type to set all imported records to. If import includes a mapping to type_id, that will be privileged and the type setting will be ignored.')
		);
		$va_settings['numInitialRowsToSkip'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 4, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Initial rows to skip'),
			'description' => _t('The number of rows at the top of the data set to skip. Use this setting to skip over column headers in spreadsheets and similar data.')
		);
		$va_settings['name'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Mapping name'),
			'description' => _t('Human readable name of the import mapping.  Pending implementation.')
		);
		$va_settings['code'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Mapping identifier'),
			'description' => _t('Arbitrary alphanumeric code for the import mapping (no special characters or spaces).  Pending implementation.')
		);
		$va_settings['table'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Map to table'),
			'description' => _t('Sets the CollectiveAccess table for the imported data.  Pending implementation.')
		);
		$va_settings['existingRecordPolicy'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('none') => 'none',
				_t('skip_on_idno') => 'skip_on_idno',
				_t('merge_on_idno') => 'merge_on_idno',
				_t('merge_on_idno_with_replace') => 'merge_on_idno_with_replace',
				_t('overwrite_on_idno') => 'overwrite_on_idno',
				_t('skip_on_preferred_labels') => 'skip_on_preferred_labels',
				_t('merge_on_preferred_labels') => 'merge_on_preferred_labels',
				_t('merge_on_preferred_labels_with_replace') => 'merge_on_preferred_labels_with_replace',
				_t('overwrite_on_preferred_labels') => 'overwrite_on_preferred_labels',
				_t('merge_on_idno_and_preferred_labels') => 'merge_on_idno_and_preferred_labels',
				_t('merge_on_idno_and_preferred_labels_with_replace') => 'merge_on_idno_and_preferred_labels_with_replace',
				_t('overwrite_on_idno_and_preferred_labels') => 'overwrite_on_idno_and_preferred_labels'
			),
			'label' => _t('Existing record policy'),
			'description' => _t('Determines how existing records are checked for and handled by the import mapping.  Pending implementation.')
		);
		$va_settings['dontDoImport'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 4, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Do not do import'),
			'description' => _t('If set then the mapping will be evaluated but no rows actually imported. This can be useful when you want to run a refinery over the rows of a data set but not actually perform the primary import.')
		);
		$va_settings['archiveMapping'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Archive mapping?'),
			'description' => _t('Set to yes to save the mapping spreadsheet; no to delete it from the server after import.  Pending implementation.')
		);
		$va_settings['archiveDataSets'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Archive data sets?'),
			'description' => _t('Set to yes to save the data spreadsheet or no to delete it from the server after import.  Pending implementation.')
		);
		$va_settings['errorPolicy'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('ignore') => "ignore",
				_t('stop') => "stop"
			),
			'label' => _t('Error policy'),
			'description' => _t('Determines how errors are handled for the import.  Options are to ignore the error, stop the import when an error is encountered and to receive a prompt when the error is encountered.')
		);
		
		$va_settings['basePath'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 60, 'height' => 2,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Base path'),
			'description' => _t('For XML data formats, an XPath expression selecting nodes to be treated as individual records. If left blank, each XML document will be treated as a single record.')
		);
		
		$va_settings['locale'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Locale'),
			'description' => _t('Set the locale used for all imported data. Leave on "DEFAULT" to use the system default locale. Otherwise set it to a valid locale code (Ex. en_US, es_MX, fr_CA).')
		);
		
		$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getAvailableInputFormats() {
		$va_readers = DataReaderManager::getDataReaderNames();
		$va_types = array();
		
		foreach($va_readers as $vs_reader) {
			if ((DataReaderManager::checkDataReaderStatus($vs_reader)) && ($o_reader = DataReaderManager::getDataReaderInstance($vs_reader))) {
				if ($va_formats = $o_reader->getSupportedFormats()) {
					foreach($va_formats as $vs_format) {
						$va_types[$o_reader->getDisplayName()." ({$vs_format})"] = $vs_format;
					}
				}
			}
		}
		return $va_types;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getInfoForAvailableInputFormats() {
		$va_readers = DataReaderManager::getDataReaderNames();
		$va_types = array();
		
		foreach($va_readers as $vs_reader) {
			if ((DataReaderManager::checkDataReaderStatus($vs_reader)) && ($o_reader = DataReaderManager::getDataReaderInstance($vs_reader))) {
				if ($va_formats = $o_reader->getSupportedFormats()) {
					$va_types[$vs_reader] = array(
						'title' => $o_reader->getTitle(),
						'displayName' => $o_reader->getDisplayName(),
						'description' => $o_reader->getDescription(),
						'title' => $o_reader->getTitle(),
						'inputType' => $o_reader->getInputType(),
						'hasMultipleDatasets' => $o_reader->hasMultipleDatasets(),
						'formats' => $va_formats
					);
				}
			}
		}
		return $va_types;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getInputFormatListAsHTMLFormElement($ps_name, $pa_attributes=null, $pa_options=null) {
		$va_input_formats = ca_data_importers::getAvailableInputFormats();
		
		return caHTMLSelect($ps_name, $va_input_formats, $pa_attributes, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Return list of available data importers
	 *
	 * @param int $pn_table_num
	 * @param array $pa_options
	 *		countOnly = return number of importers available rather than a list of importers
	 *		formats = array of input formats to limit returned importers to. Only importers that accept at least one of the specified formats will be returned.
	 *		dontIncludeWorksheet = don't include compressed metadata describing import worksheet in return value.  If you are serializing the array to JSON this data can cause problems. [Default is false]
	 * 
	 * @return mixed List of importers, or integer count of importers if countOnly option is set
	 */
	static public function getImporters($pn_table_num=null, $pa_options=null) {
		$o_db = new Db();
		
		$vb_dont_include_worksheet = caGetOption('dontIncludeWorksheet', $pa_options, false);
		
		$t_importer = new ca_data_importers();
		
		$va_formats = caGetOption('formats', $pa_options, null, array('forceLowercase' => true));
		
		$va_sql_wheres = array("(deleted = 0)");
		$va_sql_params = array();
		if((int)$pn_table_num) {
			$va_sql_wheres[] = "(di.table_num = ?)";
			$va_sql_params[] = (int)$pn_table_num;
		}
		
		
		$vs_sql_wheres = sizeof($va_sql_wheres) ? " WHERE ".join(" AND ", $va_sql_wheres) : "";
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_data_importers di
			{$vs_sql_wheres}
		", $va_sql_params);
	
		$va_importers = array();
		$va_ids = array();
		
		if (isset($pa_options['countOnly']) && $pa_options['countOnly']) {
			return (int)$qr_res->numRows();
		}
		
		while($qr_res->nextRow()) {
			$va_row = $qr_res->getRow();
			if ($vb_dont_include_worksheet) { unset($va_row['worksheet']); }
			$va_settings = caUnserializeForDatabase($va_row['settings']);
			
			if (isset($va_settings['inputFormats']) && is_array($va_settings['inputFormats']) && is_array($va_formats)) {
				$va_settings['inputFormats'] = array_map('strtolower', $va_settings['inputFormats']);
				if(!sizeof(array_intersect($va_settings['inputFormats'], $va_formats))) {
					continue;
				}
			}
			
			$va_ids[] = $vn_id = $va_row['importer_id'];
			$va_importers[$vn_id] = $va_row;
			
			$t_instance = Datamodel::getInstanceByTableNum($va_row['table_num'], true);
			$va_importers[$vn_id]['importer_type'] = $t_instance->getProperty('NAME_PLURAL');
			$va_importers[$vn_id]['type'] = $va_settings['type'];
			$va_importers[$vn_id]['type_for_display'] = $t_instance->getTypeName($va_settings['type']);
			
			$va_importers[$vn_id]['settings'] = $va_settings;
			$va_importers[$vn_id]['last_modified_on'] = $t_importer->getLastChangeTimestamp($vn_id, array('timestampOnly' => true));
			
		}
		
		$va_labels = $t_importer->getPreferredDisplayLabelsForIDs($va_ids);
		foreach($va_labels as $vn_id => $vs_label) {
			$va_importers[$vn_id]['label'] = $vs_label;
		}
		return $va_importers;
	}
	# ------------------------------------------------------
	/**
	 * Returns list of available data importers as HTML form element
	 *
	 * @param int $pn_table_num
	 * 
	 * @return int
	 */
	static public function getImporterListAsHTMLFormElement($ps_name, $pn_table_num=null, $pa_attributes=null, $pa_options=null) {
		$va_importers = ca_data_importers::getImporters($pn_table_num, $pa_options);
		
		$va_opts = array();
		foreach($va_importers as $vn_importer_id => $va_importer_info) {
			$va_opts[$va_importer_info['label']." (".$va_importer_info['importer_code'].")"] = $va_importer_info['importer_id'];
		}
		ksort($va_opts);
		return caHTMLSelect($ps_name, $va_opts, $pa_attributes, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Returns count of available data importers
	 *
	 * @param int $pn_table_num
	 * 
	 * @return int
	 */
	static public function getImporterCount($pn_table_num=null) {
		return ca_data_importers::getImporters($pn_table_num, array('countOnly' => true));
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addImportItem($pa_values){
		
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addGroup($ps_group_code, $ps_destination, $pa_settings=null, $pa_options=null){
		if(!$this->getPrimaryKey()) return false;
		
		$t_group = new ca_data_importer_groups();
		$t_group->setMode(ACCESS_WRITE);
		$t_group->set('importer_id', $this->getPrimaryKey());
		$t_group->set('group_code', $ps_group_code);
		$t_group->set('destination', $ps_destination);
		
		if (is_array($pa_settings)) {
			foreach($pa_settings as $vs_k => $vs_v) {
				$t_group->setSetting($vs_k, $vs_v);
			}
		}
		$t_group->insert();
		
		if ($t_group->numErrors()) {
			$this->errors = $t_group->errors;
			return false;
		}
		
		if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
			return $t_group;
		}
		return $t_group->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getGroups(){
		if(!$this->getPrimaryKey()) return false;
		
		$vo_db = $this->getDb();
		
		$qr_groups = $vo_db->query("
			SELECT * 
			FROM ca_data_importer_groups 
			WHERE importer_id = ?
		",$this->getPrimaryKey());
		
		$va_return = array();
		while($qr_groups->nextRow()){
			$va_return[(int)$qr_groups->get("group_id")] = $qr_groups->getRow();
		}
		
		return $va_return;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getGroupIDs(){
		if(is_array($va_groups = $this->getGroups())){
			return $va_groups;
		} else {
			return array();
		}
	}
	# ------------------------------------------------------
	public function getItems(){
		if(!$this->getPrimaryKey()) return false;
		
		$vo_db = $this->getDb();
		
		$qr_items = $vo_db->query("
			SELECT * 
			FROM ca_data_importer_items 
			WHERE importer_id = ?
		",$this->getPrimaryKey());
		
		$va_return = array();
		while($qr_items->nextRow()){
			$va_return[$qr_items->get("item_id")] = $qr_items->getRow();
			$va_return[$qr_items->get("item_id")]['settings'] = caUnserializeForDatabase($va_return[$qr_items->get("item_id")]['settings']);
		}
		
		return $va_return;
	}
	# ------------------------------------------------------
	public function getItemIDs(){
		if(is_array($va_items = $this->getItems())){
			return $va_items;
		} else {
			return array();
		}
	}
	# ------------------------------------------------------
	/**
	 * Remove group and all associated items
	 * 
	 * @param type $ps_group_code
	 */
	public function removeGroup($pn_group_id){
		$t_group = new ca_data_importer_groups();
		
		if(!in_array($pn_group_id, $this->getGroupIDs())){
			return false; // don't delete groups from other importers
		}
		
		if($t_group->load($pn_group_id)){
			$t_group->setMode(ACCESS_WRITE);
			$t_group->removeItems();
			$t_group->delete();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	public function removeAllGroups(){
		foreach($this->getGroupIDs() as $vn_group_id){
			$this->removeGroup($vn_group_id);
		}
	}
	# ------------------------------------------------------
	/**
	 * Remove importer group using its group code
	 * 
	 * @param string $ps_group_code
	 */
	public function removeGroupByCode($ps_group_code){
		$t_group = new ca_data_importer_groups();
		
		if($t_group->load(array("code" => $ps_group_code))){
			$t_group->setMode(ACCESS_WRITE);
			$t_group->removeItems();
			$t_group->delete();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	public function removeItem($pn_item_id){
		$t_item = new ca_data_importer_items();
		
		if(!in_array($pn_item_id, $this->getItemIDs())){
			return false; // don't delete items from other importers
		}
		
		if($t_item->load($pn_item_id)){
			$t_item->setMode(ACCESS_WRITE);
			$t_item->delete();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getRules(){
		$va_rules = $this->get('rules');
		if (!is_array($va_rules)) { return array(); }
		if (!is_array($va_rules['rules'])) { $va_rules = array('rules' => $va_rules); }
		return $va_rules['rules'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getEnvironment(){
		$va_rules = $this->get('rules');
		if (!is_array($va_rules)) { return array(); }
		if (!is_array($va_rules['environment'])) { return array(); }
		return $va_rules['environment'];
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
	/**
	 *
	 */
	public static function loadImporterFromFile($ps_source, &$pa_errors, $pa_options=null) {
		global $g_ui_locale_id;
		$vn_locale_id = (isset($pa_options['locale_id']) && (int)$pa_options['locale_id']) ? (int)$pa_options['locale_id'] : $g_ui_locale_id;
		$pa_errors = array();
		
		$o_config = Configuration::load();
		
		if (!is_array($pa_options) || !isset($pa_options['logDirectory']) || !$pa_options['logDirectory'] || !file_exists($pa_options['logDirectory'])) {
			if (!($pa_options['logDirectory'] = $o_config->get('batch_metadata_import_log_directory'))) {
				$pa_options['logDirectory'] = ".";
			}
		}
		
		if (!($o_log = (is_writable($pa_options['logDirectory'])) ? new KLogger($pa_options['logDirectory'], $pa_options['logLevel']) : null)) {
			throw new ApplicationException(_t("Cannot write log to %1. Please check the directory's permissions and retry loading.", $pa_options['logDirectory']));
		}
		
		$o_excel = PHPExcel_IOFactory::load($ps_source);
		//$o_excel->setActiveSheet(1);
		$o_sheet = $o_excel->getActiveSheet();
		
		$vn_row = 0;
		
		$va_settings = array();
		$va_rules = array();
		$va_environment = array();
		$va_mappings = array();
		
		$va_refineries = RefineryManager::getRefineryNames();
		
		$va_refinery_ci_map = array();
		foreach($va_refineries as $vs_refinery) {
			$va_refinery_ci_map[strtolower($vs_refinery)] = $vs_refinery;
		}
		
		foreach ($o_sheet->getRowIterator() as $o_row) {
			if ($vn_row == 0) {	// skip first row
				$vn_row++;
				continue;
			}
			
			$vn_row_num = $o_row->getRowIndex();
			$o_cell = $o_sheet->getCellByColumnAndRow(0, $vn_row_num);
			$vs_mode = (string)$o_cell->getValue();
			
			switch(strtolower($vs_mode)) {
				default:
				case 'skip':
					continue(2);
					break;
				case 'mapping':
				case 'constant':
					$o_source = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_dest = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					
					$o_group = $o_sheet->getCellByColumnAndRow(3, $o_row->getRowIndex());
					$o_options = $o_sheet->getCellByColumnAndRow(4, $o_row->getRowIndex());
					$o_refinery = $o_sheet->getCellByColumnAndRow(5, $o_row->getRowIndex());
					$o_refinery_options = $o_sheet->getCellByColumnAndRow(6, $o_row->getRowIndex());
					$o_orig_values = $o_sheet->getCellByColumnAndRow(7, $o_row->getRowIndex());
					$o_replacement_values = $o_sheet->getCellByColumnAndRow(8, $o_row->getRowIndex());
					$o_source_desc = $o_sheet->getCellByColumnAndRow(9, $o_row->getRowIndex());
					$o_notes = $o_sheet->getCellByColumnAndRow(10, $o_row->getRowIndex());
					
					if (!($vs_group = trim((string)$o_group->getValue()))) {
						$vs_group = substr('_group_'.(string)$o_source->getValue()."_{$vn_row}", 0, 100);
					}
					
					$vs_source = trim((string)$o_source->getValue());
					
					if ($vs_mode == 'Constant') {
						$vs_source = "_CONSTANT_:{$vn_row_num}:{$vs_source}";
					}
					$vs_destination = trim((string)$o_dest->getValue());
					
					if (!$vs_source) { 
						$pa_errors[] = _t("Warning: skipped mapping at row %1 because source was not defined", $vn_row_num);
						if ($o_log) { $o_log->logWarn(_t("[loadImporterFromFile:%1] Skipped mapping at row %2 because source was not defined", $ps_source, $vn_row_num)); }
						continue(2);
					}
					if (!$vs_destination) { 
						$pa_errors[] = _t("Warning: skipped mapping at row %1 because destination was not defined", $vn_row_num);
						if ($o_log) { $o_log->logWarn(_t("[loadImporterFromFile:%1] Skipped mapping at row %2 because destination was not defined", $ps_source, $vn_row_num)); }
						continue(2);
					}
					
					$va_options = null;
					if ($vs_options_json = (string)$o_options->getValue()) { 
						
						// Test whether the JSON is valid
						json_decode($vs_options_json, TRUE);
						if(json_last_error()){
							// try encode newlines
							$vs_options_json = preg_replace("![\r\n]!", "\\\\n", $vs_options_json);	
						}
						if (is_null($va_options = @json_decode($vs_options_json, true))) {
							// Error while json decode
							$pa_errors[] = _t("Warning: invalid json in \"options\" column for group %1/source %2. Json was: %3", $vs_group, $vs_source, $vs_options_json);
							if ($o_log) { $o_log->logWarn(_t("[loadImporterFromFile:%1] Invalid json in \"options\" column for group %2/source %3. Json was: %4.", $ps_source, $vs_group, $vs_source, $vs_options_json)); }
							return;
						}
					}
                    $vs_refinery = $va_refinery_ci_map[strtolower(trim((string)$o_refinery->getValue()))];
                
                    $va_refinery_options = null;
                    if ($vs_refinery && ($vs_refinery_options_json = (string)$o_refinery_options->getValue())) {
                        if (!in_array($vs_refinery, $va_refineries)) {
                            $pa_errors[] = _t("Warning: refinery %1 does not exist", $vs_refinery)."\n";
                            if ($o_log) { $o_log->logWarn(_t("[loadImporterFromFile:%1] Invalid options for group %2/source %3", $ps_source, $vs_group, $vs_source)); }
                        } else {
                            if (is_null($va_refinery_options = json_decode($vs_refinery_options_json, true))) {
                                // Error while json decode
                                $pa_errors[] = _t("invalid json for refinery options for group %1/source %2 = %3", $vs_group, $vs_source, $vs_refinery_options_json);
                                if ($o_log) { $o_log->logError( _t("[loadImporterFromFile:%1] invalid json for refinery options for group %2/source %3 = %4", $ps_source, $vs_group, $vs_source, $vs_refinery_options_json)); }
                                return;
                            }
                        }
                    }
					
					$va_original_values = $va_replacement_values = array();
					if ($va_options && is_array($va_options) && isset($va_options['transformValuesUsingWorksheet']) && $va_options['transformValuesUsingWorksheet']) {
						if ($o_opt_sheet = $o_excel->getSheetByName($va_options['transformValuesUsingWorksheet'])) {
							foreach ($o_opt_sheet->getRowIterator() as $o_sheet_row) {
								if (!$vs_original_value = trim(mb_strtolower((string)$o_opt_sheet->getCellByColumnAndRow(0, $o_sheet_row->getRowIndex())))) { continue; }
								$vs_replacement_value = trim((string)$o_opt_sheet->getCellByColumnAndRow(1, $o_sheet_row->getRowIndex()));
								$va_original_values[] = $vs_original_value;
								$va_replacement_values[] = $vs_replacement_value;
							}
						}
					} else {
						$va_original_values = preg_split("![\n\r]{1}!", mb_strtolower((string)$o_orig_values->getValue()));
						array_walk($va_original_values, function(&$v) { $v = trim($v); });
						$va_replacement_values = preg_split("![\n\r]{1}!", (string)$o_replacement_values->getValue());
						array_walk($va_replacement_values, function(&$v) { $v = trim($v); });
					}

					// Strip excess space from keys
					if (is_array($va_options)) {
						foreach($va_options as $vs_k => $vm_v) {
							if ($vs_k !== trim($vs_k)) {
								unset($va_options[$vs_k]);
								$va_options[trim($vs_k)] = $vm_v;
							}
						}
					}
					if (is_array($va_refinery_options)) {
						foreach($va_refinery_options as $vs_k => $vm_v) {
							if ($vs_k !== trim($vs_k)) {
								unset($va_refinery_options[$vs_k]);
								$va_refinery_options[trim($vs_k)] = $vm_v;
							}
						}
					}
					
					$va_mapping[$vs_group][$vs_source][] = array(
						'destination' => $vs_destination,
						'options' => $va_options,
						'refinery' => $vs_refinery,
						'refinery_options' => $va_refinery_options,
						'source_description' => (string)$o_source_desc->getValue(),
						'notes' => (string)$o_notes->getValue(),
						'original_values' => $va_original_values,
						'replacement_values' => $va_replacement_values
					);
					break;
				case 'setting':
					$o_setting_name = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_setting_value = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					
					switch($vs_setting_name = (string)$o_setting_name->getValue()) {
						case 'inputTypes':		// older mapping worksheets use "inputTypes" instead of the preferred "inputFormats"
						case 'inputFormats':
							$vs_setting_name = 'inputFormats'; // force to preferrened "inputFormats"
							$va_settings[$vs_setting_name] = preg_split("![ ]*;[ ]*!", (string)$o_setting_value->getValue());
							break;
						default:
							$va_settings[$vs_setting_name] = (string)$o_setting_value->getValue();
							break;
					}
					break;
				case 'rule':
					$o_rule_trigger = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_rule_action = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					
					$vs_action_string = (string)$o_rule_action->getValue();
					if (!($va_actions = json_decode($vs_action_string, true))) {
						$va_actions = [];
						foreach(preg_split("/[\n\r]+/", $vs_action_string) as $vs_action) {
							$va_actions[] = [
								'action' => $vs_action
							];
						}
					}
					$va_rules[] = array(
						'trigger' => (string)$o_rule_trigger->getValue(),
						'actions' => $va_actions
					);
					
					break;
				case 'environment':
					$o_source = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_env_var = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					$o_options = $o_sheet->getCellByColumnAndRow(4, $o_row->getRowIndex());
					
					$va_options = array();
					if ($vs_options_json = (string)$o_options->getValue()) { 
						json_decode($vs_options_json, TRUE);
						if(json_last_error()){
							// try encode newlines
							$vs_options_json = preg_replace("![\r\n]!", "\\\\n", $vs_options_json);	
						}
						if (is_null($va_options = @json_decode($vs_options_json, true))) {
							$pa_errors[] = _t("Warning: invalid options for environment %1.", (string)$o_source->getValue());
							if ($o_log) { $o_log->logWarn(_t("[loadImporterFromFile:environment %1] Invalid options for environment value %1. Options were: %2.", (string)$o_source->getValue(), $vs_options_json)); }
						}
					}
					
					$va_environment[] = array(
						'name' => (string)$o_env_var->getValue(),
						'value' => (string)$o_source->getValue(),
						'options' => $va_options
					);
					break;
			}
			$vn_row++;
		}
		
		// Do checks on mapping
		if (!$va_settings['code']) { 
			$pa_errors[] = _t("You must set a code for your mapping!");
			if ($o_log) { $o_log->logError(_t("[loadImporterFromFile:%1] You must set a code for your mapping!", $ps_source)); }
			return;
		}

		// don't import exporter mappings
		if (isset($va_settings['exporter_format'])) {
			$pa_errors[] = _t("It looks like this is a mapping for the data export framework and you're trying to add it as import mapping!");
			if ($o_log) { $o_log->logError(_t("[loadImporterFromFile:%1] It looks like this is a mapping for the data export framework and you're trying to add it as import mapping!", $ps_source)); }
			return;
		}
		
		// If no formats then default to everything
		if (!isset($va_settings['inputFormats']) || !is_array($va_settings['inputFormats']) || !sizeof($va_settings['inputFormats'])) {
			$va_settings['inputFormats'] = array_values(ca_data_importers::getAvailableInputFormats());
		}
		
		if (!($t_instance = Datamodel::getInstance($va_settings['table']))) {
			$pa_errors[] = _t("Mapping target table %1 is invalid\n", $va_settings['table']);
			if ($o_log) {  $o_log->logError(_t("[loadImporterFromFile:%1] Mapping target table %2 is invalid\n", $ps_source, $va_settings['table'])); }
			return;
		}
		
		if (!$va_settings['name']) { $va_settings['name'] = $va_settings['code']; }
		
		
		$t_importer = new ca_data_importers();
		$t_importer->setMode(ACCESS_WRITE);
		
		// Remove any existing mapping
		if ($t_importer->load(array('importer_code' => $va_settings['code']))) {
			$t_importer->delete(true, array('hard' => true));
			if ($t_importer->numErrors()) {
				$pa_errors[] = _t("Could not delete existing mapping for %1: %2", $va_settings['code'], join("; ", $t_importer->getErrors()))."\n";
				if ($o_log) { $o_log->logError(_t("[loadImporterFromFile:%1] Could not delete existing mapping for %2: %3", $ps_source, $va_settings['code'], join("; ", $t_importer->getErrors()))); }
				return null;
			}
		}
		
		// Create new mapping
		$t_importer->set('importer_code', $va_settings['code']);
		$t_importer->set('table_num', $t_instance->tableNum());
		$t_importer->set('rules', array('rules' => $va_rules, 'environment' => $va_environment));
		
		unset($va_settings['code']);
		unset($va_settings['table']);
		foreach($va_settings as $vs_k => $vs_v) {
			$t_importer->setSetting($vs_k, $vs_v);
		}
		$t_importer->insert();
		
		if ($t_importer->numErrors()) {
			$pa_errors[] = _t("Error creating mapping: %1", join("; ", $t_importer->getErrors()))."\n";
			if ($o_log) { $o_log->logError(_t("[loadImporterFromFile:%1] Error creating mapping: %2", $ps_source, join("; ", $t_importer->getErrors()))); }
			return null;
		}
		
		
		$t_importer->addLabel(array('name' => $va_settings['name']), $vn_locale_id, null, true);
		
		if ($t_importer->numErrors()) {
			$pa_errors[] = _t("Error creating mapping name: %1", join("; ", $t_importer->getErrors()))."\n";
			if ($o_log) {  $o_log->logError(_t("[loadImporterFromFile:%1] Error creating mapping: %2", $ps_source, join("; ", $t_importer->getErrors()))); }
			return null;
		}
		
		$t_importer->set('worksheet', $ps_source);
		$t_importer->update();
		
		if ($t_importer->numErrors()) {
			$pa_errors[] = _t("Could not save worksheet for future download: %1", join("; ", $t_importer->getErrors()))."\n";
			if ($o_log) {  $o_log->logError(_t("[loadImporterFromFile:%1] Error saving worksheet for future download: %2", $ps_source, join("; ", $t_importer->getErrors()))); }
		}
		
		foreach($va_mapping as $vs_group => $va_mappings_for_group) {
			$vs_group_dest = ca_data_importers::_getGroupDestinationFromItems($va_mappings_for_group);
			if (!$vs_group_dest) { 
				$va_item = array_shift(array_shift($va_mappings_for_group));
				$pa_errors[] = _t("Skipped items for %1 because no common grouping could be found", $va_item['destination'])."\n";
				if ($o_log) { $o_log->logWarn(_t("[loadImporterFromFile:%1] Skipped items for %2 because no common grouping could be found", $ps_source, $va_item['destination'])); }
				continue;
			}
			
			$t_group = $t_importer->addGroup($vs_group, $vs_group_dest, array(), array('returnInstance' => true));
			if(!$t_group) {
				$pa_errors[] = _t("There was an error when adding group %1", $vs_group);
				if ($o_log) { $o_log->logError(_t("[loadImporterFromFile:%1] There was an error when adding group %2", $ps_source, $vs_group)); }
				return;
			}
			
			// Add items
			foreach($va_mappings_for_group as $vs_source => $va_mappings_for_source) {
				foreach($va_mappings_for_source as $va_row) {
					$va_item_settings = array();
					$va_item_settings['refineries'] = array($va_row['refinery']);
				
					$va_item_settings['original_values'] = $va_row['original_values'];
					$va_item_settings['replacement_values'] = $va_row['replacement_values'];
				
					if (is_array($va_row['options'])) {
						foreach($va_row['options'] as $vs_k => $vs_v) {
							if ($vs_k == 'restrictToRelationshipTypes') { $vs_k = 'filterToRelationshipTypes'; }	// "restrictToRelationshipTypes" is now "filterToRelationshipTypes" but we want to support old mappings so we translate here
							$va_item_settings[$vs_k] = $vs_v;
						}
					}
					if (is_array($va_row['refinery_options'])) {
						foreach($va_row['refinery_options'] as $vs_k => $vs_v) {
							$va_item_settings[$va_row['refinery'].'_'.$vs_k] = $vs_v;
						}
					}
					
					$t_group->addItem($vs_source, $va_row['destination'], $va_item_settings, array('returnInstance' => true));
				}
			}
		}
		
		if(sizeof($pa_errors)) {
			foreach($pa_errors as $vs_error) {
				$t_importer->postError(1100, $vs_error, 'ca_data_importers::loadImporterFromFile');
			}
		}
		
		return $t_importer;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function _getGroupDestinationFromItems($pa_items) {
		$va_acc = null;
		foreach($pa_items as $vn_item_id => $va_items_for_source) {
			foreach($va_items_for_source as $va_item) {
				if (is_null($va_acc)) { 
					$va_acc = explode(".", $va_item['destination']);
				} else {
					$va_tmp = explode(".", $va_item['destination']);
				
					$vn_len = sizeof($va_acc);
					for($vn_i=$vn_len - 1; $vn_i >= 0; $vn_i--) {
						if (!isset($va_tmp[$vn_i]) || ($va_acc[$vn_i] != $va_tmp[$vn_i])) {
							for($vn_x = $vn_i; $vn_x < $vn_len; $vn_x++) {
								unset($va_acc[$vn_x]);
							}
						}
					}
				}
			}
		}
		
		return join(".", $va_acc);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function mappingExists($ps_mapping) {
		$t_importer = new ca_data_importers();
		if (is_numeric($ps_mapping)) {
			if($t_importer->load((int)$ps_mapping)) {
				return $t_importer;
			}
		}
		if($t_importer->load(array('importer_code' => $ps_mapping))) {
			return $t_importer;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function logImportError($ps_message, $pa_options=null) {
		ca_data_importers::$s_num_import_errors++;
		
		if ($vb_skipped = (isset($pa_options['skip']) && ($pa_options['skip'])) ? true : false) {
			ca_data_importers::$s_num_records_skipped++;
		}
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$o_log = (isset($pa_options['log']) && $pa_options['log']) ? $pa_options['log'] : null;
		
		$vb_dont_output = (!isset($pa_options['dontOutput']) || !$pa_options['dontOutput']) ?  true : false;
		
		$vs_display_message = "(".date("Y-m-d H:i:s").") {$ps_message}";
		array_unshift(ca_data_importers::$s_import_error_list, preg_replace("![\r\n\t]+!", " ", $vs_display_message));
		
		// 
		// Output to screen as text
		//
		if (!$vb_dont_output) { print "{$ps_message}\n"; }
		
		
		$po_request = (isset($pa_options['request']) && $pa_options['request']) ? $pa_options['request'] : null;
		if ($po_request && isset($pa_options['reportCallback']) && ($ps_callback = $pa_options['reportCallback'])) {
			$va_general = array(
				'elapsedTime' => time() - caGetOption('startTime', $pa_options, 0),
				'numErrors' => ca_data_importers::$s_num_import_errors,
				'numProcessed' => ca_data_importers::$s_num_records_processed
			);
			$ps_callback($po_request, $va_general, ca_data_importers::$s_import_error_list);
		}
		
		//
		// Log message
		//
		if ($o_log) { $o_log->logError($ps_message); }
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function formatValuesForLog($pa_values) {
		$va_list = array();
		
		if (is_array($pa_values)) {
			foreach($pa_values as $vs_element => $va_values) {
				if (!is_array($va_values)) {
					$va_list[] = "{$vs_element} = {$va_values}";
				} else {
					$va_list[] = "{$vs_element} = ".ca_data_importers::formatValuesForLog($va_values);
				}
			}
		} else {
			$va_list[] = $pa_values;;
		}
		return join("; ", $va_list);
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_source
	 * @param string $ps_mapping
	 * @param array $pa_options
	 *		user_id = user to execute import for
	 *		description = Text describing purpose of import to be logged.
	 *		showCLIProgressBar = Show command-line progress bar. Default is false.
	 *		format = Format of data being imported. MANDATORY
	 *		logDirectory = path to directory where logs should be written
	 *		logLevel = KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
	 *			KLogger::EMERG = Emergency messages (system is unusable)
	 *			KLogger::ALERT = Alert messages (action must be taken immediately)
	 *			KLogger::CRIT = Critical conditions
	 *			KLogger::ERR = Error conditions
	 *			KLogger::WARN = Warnings
	 *			KLogger::NOTICE = Notices (normal but significant conditions)
	 *			KLogger::INFO = Informational messages
	 *			KLogger::DEBUG = Debugging messages
	 *		logToTempDirectoryIfLogDirectoryIsNotWritable = use system temporary directory for log location if specified logDirectory is not writable. [Default is false]
	 *		dryRun = do import but don't actually save data
	 *		environment = an array of environment values to provide to the import process. The keys manifest themselves as mappable tags.
	 *		forceImportForPrimaryKeys = list of primary key ids to force mapped source data into. The number of keys passed should equal or exceed the number of rows in the source data. [Default is empty] 
	 *		transaction = transaction to perform import within. Will not be used if noTransaction option is set. [Default is to create a new transaction]
	 *		noTransaction = don't wrap the import in a transaction. [Default is false]
	 *		importAllDatasets = for data formats (such as Excel/XLSX) that support multiple data sets in a single file (worksheets in Excel), indicated that all data sets should be imported; otherwise only the default data set is imported [Default=false]
	 *		addToSet = identifier for set to add all imported items to. [Default is null]
	 */
	static public function importDataFromSource($ps_source, $ps_mapping, $pa_options=null) {
		ca_data_importers::$s_num_import_errors = 0;
		ca_data_importers::$s_num_records_processed = 0;
		ca_data_importers::$s_num_records_skipped = 0;
		ca_data_importers::$s_import_error_list = array();
		
		$o_config = Configuration::load();
		
		$opa_app_plugin_manager = new ApplicationPluginManager();
		
		$va_notices = $va_errors = array();
		
		$pb_no_transaction 	= caGetOption('noTransaction', $pa_options, false, array('castTo' => 'bool'));
		$pa_force_import_for_primary_keys = caGetOption('forceImportForPrimaryKeys', $pa_options, null);

		if (!($t_mapping = ca_data_importers::mappingExists($ps_mapping))) {
			return null;
		}
		
		$o_event = ca_data_import_events::newEvent(isset($pa_options['user_id']) ? $pa_options['user_id'] : null, $pa_options['format'], $ps_source, isset($pa_options['description']) ? $pa_options['description'] : '');
		
		$o_trans = null;
		
		if (!$pb_no_transaction) { 
			if(!($o_trans = caGetOption('transaction', $pa_options, null))) { $o_trans = new Transaction(); }
			$t_mapping->setTransaction($o_trans); 
		}
		
		$po_request 	= caGetOption('request', $pa_options, null);
		$pb_dry_run 	= caGetOption('dryRun', $pa_options, false);
		
		$pn_file_number 		= caGetOption('fileNumber', $pa_options, 0);
		$pn_number_of_files 	= caGetOption('numberOfFiles', $pa_options, 1);
		
		$t_add_to_set = null;
		if ($ps_add_to_set 	= caGetOption('addToSet', $pa_options, null)) {
			if (!($t_add_to_set = ca_sets::find(['set_code' => $ps_add_to_set], ['returnAs' => 'firstModelInstance']))) {
				// Set doesn't exist
				ca_data_importers::logImportError(_t("Set %1 does not exist. Create it and try the import again.", $ps_add_to_set));
				return false;
			}
			if ((int)$t_mapping->get('table_num') !== (int)$t_add_to_set->get('table_num')) {
				// Set doesn't take type of item mapping imports
				ca_data_importers::logImportError(_t("Set %1 is not the correct type for this import.", $ps_add_to_set));
				return false;
			}
			
			if (!$pb_no_transaction) { $t_add_to_set->setTransaction($o_trans); }
		}
		
		if (!is_array($pa_options) || !isset($pa_options['logLevel']) || !$pa_options['logLevel']) {
			$pa_options['logLevel'] = KLogger::INFO;
		}
		
		if (!is_array($pa_options) || !isset($pa_options['logDirectory']) || !$pa_options['logDirectory'] || !file_exists($pa_options['logDirectory'])) {
			if (!($pa_options['logDirectory'] = $o_config->get('batch_metadata_import_log_directory'))) {
				$pa_options['logDirectory'] = ".";
			}
		}
		
		if (!($o_log = (is_writable($pa_options['logDirectory'])) ? new KLogger($pa_options['logDirectory'], $pa_options['logLevel']) : null)) {
			if (!caGetOption('logToTempDirectoryIfLogDirectoryIsNotWritable', $pa_options, false)) {
				ca_data_importers::logImportError(_t("Cannot write log to %1. Please check the directory permissions and retry the import.", $pa_options['logDirectory']));
				return false;
			} else {
				$vs_original_log_directory = $pa_options['logDirectory'];
				ca_data_importers::logImportError(_t("Cannot write log to %1. Writing log to %2 instead.", $vs_original_log_directory, $pa_options['logDirectory'] = caGetTempDirPath()));
				if (!($o_log = (is_writable($pa_options['logDirectory'])) ? new KLogger($pa_options['logDirectory'], $pa_options['logLevel']) : null)) {
					ca_data_importers::logImportError(_t("Cannot write log to either %1 or %2. Please check the directory permissions and retry the import.", $vs_original_log_directory, $pa_options['logDirectory']));
					return false;
				}
			}
		}
		$o_log = new KLogger($pa_options['logDirectory'], $pa_options['logLevel']);
		
		$vb_show_cli_progress_bar 	= (isset($pa_options['showCLIProgressBar']) && ($pa_options['showCLIProgressBar'])) ? true : false;
		
		$o_progress = caGetOption('progressBar', $pa_options, new ProgressBar('WebUI'));
		if ($vb_show_cli_progress_bar) { $o_progress->setMode('CLI'); $o_progress->set('outputToTerminal', true); }
		
		$o_log->logInfo(_t('Started import of %1 using mapping %2', $ps_source, $t_mapping->get("importer_code")));		
		

		$t = new Timer();
		$vn_start_time = time();
		$va_log_import_error_opts = array('startTime' => $vn_start_time, 'window' => $r_errors, 'log' => $o_log, 'request' => $po_request, 'progressCallback' => (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) ? $ps_callback : null, 'reportCallback' => (isset($pa_options['reportCallback']) && ($ps_callback = $pa_options['reportCallback'])) ? $ps_callback : null);
	
		global $g_ui_locale_id;	// constant locale set by index.php for web requests
		if (!($vn_locale_id = caGetOption('locale_id', $pa_options, null))) {	// set as option?	
			if (!($vn_locale_id = ca_locales::codeToID($t_mapping->getSetting('locale')))) {	// set in mapping?
				$vn_locale_id = $g_ui_locale_id;												// use global
			}
		}
		
		$vb_import_all_datasets = caGetOption('importAllDatasets', $pa_options, false);
		
		$o_progress->start(_t('Reading %1', $ps_source));
		
		if ($po_request && isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
			$ps_callback($po_request, $pn_file_number, $pn_number_of_files, $ps_source, 0, 100, _t('Reading %1', $ps_source), (time() - $vn_start_time), memory_get_usage(true), 0, ca_data_importers::$s_num_import_errors);
		}
	
		// Open file 
		$ps_format = (isset($pa_options['format']) && $pa_options['format']) ? $pa_options['format'] : null;	
		if (!($o_reader = $t_mapping->getDataReader($ps_source, $ps_format))) {
			ca_data_importers::logImportError(_t("Could not open source %1 (format=%2)", $ps_source, $ps_format), $va_log_import_error_opts);
			if ($o_trans) { $o_trans->rollback(); }
			return false;
		}
		
		$va_reader_opts = array('basePath' => $t_mapping->getSetting('basePath'), 'originalFilename' => caGetOption('originalFilename', $pa_options, null));
		
		if (!$o_reader->read($ps_source, $va_reader_opts)) {
			ca_data_importers::logImportError(_t("Could not read source %1 (format=%2)", $ps_source, $ps_format), $va_log_import_error_opts);
			if ($o_trans) { $o_trans->rollback(); }
			return false;
		}
		
		$o_log->logDebug(_t('Finished reading input source at %1 seconds', $t->getTime(4)));
		
	$vn_dataset_count = $vb_import_all_datasets ? (int)$o_reader->getDatasetCount() : 1;

	for($vn_dataset=0; $vn_dataset < $vn_dataset_count; $vn_dataset++) {
		if (!$o_reader->setCurrentDataset($vn_dataset)) { continue; }
		
		$vn_num_items = $o_reader->numRows();
		$o_log->logDebug(_t('Found %1 rows in input source', $vn_num_items));

		$o_progress->setTotal($vn_num_items);
		$o_progress->start(_t('Importing from %1', $ps_source));

		if ($po_request && isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
			$ps_callback($po_request, $pn_file_number, $pn_number_of_files, $ps_source, 0, $vn_num_items, _t('Importing from %1', $ps_source), (time() - $vn_start_time), memory_get_usage(true), 0, ca_data_importers::$s_num_import_errors);
		}
		
		// What are we importing?
		$vn_table_num = $t_mapping->get('table_num');
		if (!($t_subject = Datamodel::getInstanceByTableNum($vn_table_num))) {
			// invalid table
			$o_log->logError(_t('Mapping uses invalid table %1 as target', $vn_table_num));
			if ($o_trans) { $o_trans->rollback(); }
			return false;
		}
		$t_subject->setTransaction($o_trans);
		$vs_subject_table_name = $t_subject->tableName();
		
		$t_label = $t_subject->getLabelTableInstance();
		$t_label->setTransaction($o_trans);
		
		$vs_label_display_fld = $t_subject->getLabelDisplayField();
		
		$vs_subject_table = $t_subject->tableName();
		$vs_type_id_fld = $t_subject->getTypeFieldName();
		$vs_idno_fld = $t_subject->getProperty('ID_NUMBERING_ID_FIELD');
		
		$va_subject_type_list = $t_subject->getTypeList();
		
		// get mapping rules
		$va_mapping_rules = $t_mapping->getRules();
		
		// get mapping groups
		$va_mapping_groups = $t_mapping->getGroups();
		$va_mapping_items = $t_mapping->getItems();
		
		//
		// Mapping-level settings
		//
		$vs_type_mapping_setting = $t_mapping->getSetting('type');
		$vn_num_initial_rows_to_skip = $t_mapping->getSetting('numInitialRowsToSkip');
		if (!in_array($vs_import_error_policy = $t_mapping->getSetting('errorPolicy'), array('ignore', 'stop'))) {
			$vs_import_error_policy = 'ignore';
		}
		
		if (!in_array(	
			$vs_existing_record_policy = $t_mapping->getSetting('existingRecordPolicy'),
			array(
				'none', 'skip_on_idno', 'skip_on_preferred_labels',
				'merge_on_idno', 'merge_on_preferred_labels', 'merge_on_idno_and_preferred_labels',
				'merge_on_idno_with_replace', 'merge_on_preferred_labels_with_replace', 'merge_on_idno_and_preferred_labels_with_replace',
			 	'overwrite_on_idno', 'overwrite_on_preferred_labels', 'overwrite_on_idno_and_preferred_labels'
			)
		)) {
			$vs_existing_record_policy = 'none';
		}		
		
		
		// Analyze mapping for figure out where type, idno, preferred label and other mandatory fields are coming from
		$vn_type_id_mapping_item_id = $vn_idno_mapping_item_id = null;
		$va_preferred_label_mapping_ids = $va_nonpreferred_label_mapping_ids = array();
		$va_mandatory_field_mapping_ids = array();
		
		$va_mandatory_fields = $t_subject->getMandatoryFields();
		
		foreach($va_mapping_items as $vn_item_id => $va_item) {
			$vs_destination = $va_item['destination'];
			
			if (sizeof($va_dest_tmp = explode(".", $vs_destination)) >= 2) {
				if (($va_dest_tmp[0] == $vs_subject_table) && ($va_dest_tmp[1] == 'preferred_labels')) {
					if (isset($va_dest_tmp[2])) {
						$va_preferred_label_mapping_ids[$vn_item_id] = $va_dest_tmp[2];
					} else {
						$va_preferred_label_mapping_ids[$vn_item_id] = $vs_label_display_fld;
					}
					continue;
				}
				if (($va_dest_tmp[0] == $vs_subject_table) && ($va_dest_tmp[1] == 'nonpreferred_labels')) {
					if (isset($va_dest_tmp[2])) {
						$va_nonpreferred_label_mapping_ids[$vn_item_id] = $va_dest_tmp[2];
					} else {
						$va_nonpreferred_label_mapping_ids[$vn_item_id] = $vs_label_display_fld;
					}
					continue;
				}
			}
			
			switch($vs_destination) {
				case 'representation_id':
					if ($vs_subject_table == 'ca_representation_annotations') {
						$vn_type_id_mapping_item_id = $vn_item_id;
					}
					break;
				case "{$vs_subject_table}.{$vs_type_id_fld}":
					$vn_type_id_mapping_item_id = $vn_item_id;
					break;
				case "{$vs_subject_table}.{$vs_idno_fld}":
					$vn_idno_mapping_item_id = $vn_item_id;
					break;
			}
			
			foreach($va_mandatory_fields as $vs_mandatory_field) {
				if ($vs_mandatory_field == $vs_type_id_fld) { continue; }	// type is handled separately
				if ($vs_destination == "{$vs_subject_table}.{$vs_mandatory_field}") {
					$va_mandatory_field_mapping_ids[$vs_mandatory_field] = $vn_item_id;
				}
			}
		}
		
		$va_items_by_group = array();
		foreach($va_mapping_items as $vn_item_id => $va_item) {
			$va_items_by_group[$va_item['group_id']][$va_item['item_id']] = $va_item;
		}
		
		
		$o_log->logDebug(_t('Finished analyzing mapping at %1 seconds', $t->getTime(4)));
		
		//
		// Set up environment
		//
		$va_environment = caGetOption('environment', $pa_options, array(), array('castTo' => 'array'));
		if (is_array($va_environment_config = $t_mapping->getEnvironment())) {
			foreach($va_environment_config as $vn_i => $va_environment_item) {
				$va_env_tmp = explode("|", $va_environment_item['value']);
			
				if (!($o_env_reader = $t_mapping->getDataReader($ps_source, $ps_format))) { break; }
				if(!$o_env_reader->read($ps_source, array('basePath' => '', 'originalFilename' => caGetOption('originalFilename', $pa_options, null)))) { break; }
				$o_env_reader->setCurrentDataset($vn_dataset);
				$o_env_reader->nextRow();
				switch(sizeof($va_env_tmp)) {
					case 1:
						$vs_env_value = $o_env_reader->get($va_environment_item['value'], array('returnAsArray' => false));
						break;
					case 2:
						$vn_seek = (int)$va_env_tmp[0];
						$o_env_reader->seek(($vn_seek > 0) ? $vn_seek - 1 : $vn_seek); $o_env_reader->nextRow();
						$vs_env_value = $o_env_reader->get($va_env_tmp[1], array('returnAsArray' => false));
						$o_env_reader->seek(0);
						break;
					default:
						$vs_env_value = $va_environment_item['value'];
						break;
				}
				$va_environment[$va_environment_item['name']] = $vs_env_value;
			}
		}
		
		// 
		// Run through rows
		//
		$vn_row = 0;
		ca_data_importers::$s_num_records_processed = 0;
		while ($o_reader->nextRow()) {
			$va_mandatory_field_values = array();
			$vs_preferred_label_for_log = null;
		
			if ($vn_row < $vn_num_initial_rows_to_skip) {	// skip over initial header rows
				$o_log->logDebug(_t('Skipped initial row %1 of %2', $vn_row, $vn_num_initial_rows_to_skip));
				$vn_row++;
				continue;
			}
			
			$vn_row++;
			
			$t->startTimer();
			$o_log->logDebug(_t('Started reading row %1 at %2 seconds', $vn_row, $t->getTime(4)));
			
			//
			// Get data for current row
			//
			$va_row = array_replace($o_reader->getRow(), $va_environment);
			
			// replace values (EXPERIMENTAL)
			$va_row_with_replacements = $va_row;
			foreach($va_mapping_items as $vn_item_id => $va_item) {
				$vs_key = $va_item['source'];
				
				if (!isset($va_row[$vs_key])) { 
					$va_tmp = explode('.', $vs_key); array_shift($va_tmp);
					$vs_key = join('.', $va_tmp);
				}
				if(!isset($va_row[$vs_key])) { continue; }
				
				if(is_array($va_row[$vs_key])) {
				    $va_row_with_replacements[$vs_key] = array_map(function($v) use ($va_item) { return ca_data_importers::replaceValue($v, $va_item, []); }, $va_row[$vs_key]);
				} else {
				    $va_row_with_replacements[$vs_key] = ca_data_importers::replaceValue($va_row[$vs_key], $va_item, []);
			    }
			}
			
			//
			// Apply rules
			//
			$va_rule_set_values = [];
			foreach($va_mapping_rules as $va_rule) {
				if (!isset($va_rule['trigger']) || !$va_rule['trigger']) { continue; }
				if (!isset($va_rule['actions']) || !is_array($va_rule['actions']) || !sizeof($va_rule['actions'])) { continue; }

				try {
					if(ExpressionParser::evaluate($va_rule['trigger'], $va_row)) {
						foreach($va_rule['actions'] as $va_action) {
							if (!is_array($va_action)) {
								$va_action = array('action' => 'skip');
							}
							
							switch($vs_action_code = strtolower($va_action['action'])) {
								case 'set':
									$va_rule_set_values[$va_action['target']] = $va_action['value']; // TODO: transform value using mapping rules?
									
									break;
								case 'skip':
								default:
									if ($vs_action_code != 'skip') {
										$o_log->logInfo(_t('Row was skipped using rule "%1" with default action because an invalid action ("%2") was specified', $va_rule['trigger'], $vs_action_code));
									} else {
										$o_log->logDebug(_t('Row was skipped using rule "%1" with action "%2"', $va_rule['trigger'], $vs_action_code));
									}
									continue(4);
									break;
							}
						}
					} else {
						foreach($va_rule['actions'] as $va_action) {
							if (!is_array($va_action)) {
								$va_action = array('action' => 'skip');
							}

							switch($vs_action_code = strtolower($va_action['action'])) {
								case 'set':
									if(isset($va_action['else'])) {
										$va_rule_set_values[$va_action['target']] = $va_action['else']; // TODO: transform value using mapping rules?
									}
									break;
							}
						}
					}
				} catch(Exception $e) {
					$o_log->logError(_t("Expression trigger could not be parsed. Please double-check the syntax! Expression was: %1 [%2]", $va_rule['trigger'], $e->getMessage()));
				}
			}
			
			//
			// Perform mapping and insert
			//
			
			// Get minimal info for imported row (type_id, idno, label)
			// Get type
			if ($vn_type_id_mapping_item_id) {
				// Type is specified in row
				$vs_type = ca_data_importers::getValueFromSource($va_mapping_items[$vn_type_id_mapping_item_id], $o_reader, array('otherValues' => $va_rule_set_values, 'environment' => $va_environment));
			    if (isset($va_mapping_items[$vn_type_id_mapping_item_id]['settings']['default']) && strlen($va_mapping_items[$vn_type_id_mapping_item_id]['settings']['default']) && !strlen($vs_type)) {
					$vs_type = $va_mapping_items[$vn_type_id_mapping_item_id]['settings']['default'];
				}
			} else {
				// Type is constant for all rows
				$vs_type = $vs_type_mapping_setting;	
			}
			
			// Set default when set type is invalid
			if (isset($va_mapping_items[$vn_type_id_mapping_item_id]['settings']['default']) && strlen($va_mapping_items[$vn_type_id_mapping_item_id]['settings']['default']) && (sizeof(array_filter($va_subject_type_list, function($v) use ($vs_type) { return ($vs_type === $v['idno']);  })) == 0)) {
                $vs_type = $va_mapping_items[$vn_type_id_mapping_item_id]['settings']['default'];
            }
            if (!$vs_type) { $vs_type = $vs_type_mapping_setting; }		// fallback to mapping default if necessary
			
			// Get idno
			$vs_idno = $va_idnos_for_row = null;
			if ($vn_idno_mapping_item_id) {
				// idno is specified in row
				$vs_idno = ca_data_importers::getValueFromSource($va_mapping_items[$vn_idno_mapping_item_id], $o_reader, array('otherValues' => $va_rule_set_values, 'environment' => $va_environment));				
				
				if (isset($va_mapping_items[$vn_idno_mapping_item_id]['settings']['default']) && strlen($va_mapping_items[$vn_idno_mapping_item_id]['settings']['default']) && !strlen($vs_idno)) {
					$vs_idno = $va_mapping_items[$vn_idno_mapping_item_id]['settings']['default'];
				}
				if (!is_array($vs_idno) && ($vs_idno[0] == '^') && preg_match("!^\^[^ ]+$!", $vs_idno)) {
					// Parse placeholder when it's at the beginning of the value

					if (!is_null($vm_parsed_val = BaseRefinery::parsePlaceholder($vs_idno, $va_row_with_replacements, $va_item, null, array('reader' => $o_reader, 'returnAsString' => true)))) {
						$vs_idno = $vm_parsed_val;
					}
				}
				// Apply prefix/suffix *AFTER* setting default
				if ($vs_idno && isset($va_mapping_items[$vn_idno_mapping_item_id]['settings']['prefix']) && strlen($va_mapping_items[$vn_idno_mapping_item_id]['settings']['prefix'])) {
					$vs_idno = $va_mapping_items[$vn_idno_mapping_item_id]['settings']['prefix'].$vs_idno;
				}
				if ($vs_idno && isset($va_mapping_items[$vn_idno_mapping_item_id]['settings']['suffix']) && strlen($va_mapping_items[$vn_idno_mapping_item_id]['settings']['suffix'])) {
					$vs_idno .= $va_mapping_items[$vn_idno_mapping_item_id]['settings']['suffix'];
				}
				
				if (isset($va_mapping_items[$vn_idno_mapping_item_id]['settings']['formatWithTemplate']) && strlen($va_mapping_items[$vn_idno_mapping_item_id]['settings']['formatWithTemplate'])) {
					$vs_idno = DisplayTemplateParser::processTemplate($va_mapping_items[$vn_idno_mapping_item_id]['settings']['formatWithTemplate'], $va_row_with_replacements, ['getFrom' => $o_reader]);
				}
										
				if (isset($va_mapping_items[$vn_idno_mapping_item_id]['settings']['applyRegularExpressions']) && is_array($va_mapping_items[$vn_idno_mapping_item_id]['settings']['applyRegularExpressions'])) {
					if(is_array($va_mapping_items[$vn_idno_mapping_item_id]['settings']['applyRegularExpressions'])) {
						foreach($va_mapping_items[$vn_idno_mapping_item_id]['settings']['applyRegularExpressions'] as $vn_regex_index => $va_regex) {
							if (!strlen($va_regex['match'])) { continue; }
							$vs_idno = preg_replace("!".str_replace("!", "\\!", $va_regex['match'])."!".((isset($va_regex['caseSensitive']) && (bool)$va_regex['caseSensitive']) ? '' : 'i') , $va_regex['replaceWith'], $vs_idno);
						}
					}
				}
				
				if ($va_mapping_items[$vn_idno_mapping_item_id]['settings']['delimiter'] && $va_mapping_items[$vn_idno_mapping_item_id]['settings']['treatAsIdentifiersForMultipleRows']) {
					$va_idnos_for_row = explode($va_mapping_items[$vn_idno_mapping_item_id]['settings']['delimiter'], $vs_idno);
				}
				
			} else {
				$vs_idno = "%";
			}
			$vb_idno_is_template = (bool)preg_match('![%]+!', $vs_idno);
			
			if (!$vb_idno_is_template) {
				$va_idnos_for_row = !$va_idnos_for_row ? array($vs_idno) : $va_idnos_for_row;
			} else {
				$va_idnos_for_row = array($vs_idno);
			}
			
			foreach($va_idnos_for_row as $vs_idno) {
				$t_subject = Datamodel::getInstanceByTableNum($vn_table_num);
				if ($o_trans) { $t_subject->setTransaction($o_trans); }
				$t_subject->setMode(ACCESS_WRITE);
			
			
				// get preferred labels
				$va_pref_label_values = array();
				foreach($va_preferred_label_mapping_ids as $vn_preferred_label_mapping_id => $vs_preferred_label_mapping_fld) {
					$vs_label_val = ca_data_importers::getValueFromSource($va_mapping_items[$vn_preferred_label_mapping_id], $o_reader, array('otherValues' => $va_rule_set_values, 'environment' => $va_environment, 'log' => $o_log, 'logIdno' => $vs_idno));
				
					// If a template is specified format the label value with it so merge-on-preferred_label doesn't fail
					if (isset($va_mapping_items[$vn_preferred_label_mapping_id]['settings']['formatWithTemplate']) && strlen($va_mapping_items[$vn_preferred_label_mapping_id]['settings']['formatWithTemplate'])) {
						$vs_label_val = DisplayTemplateParser::processTemplate($va_mapping_items[$vn_preferred_label_mapping_id]['settings']['formatWithTemplate'], $va_row_with_replacements);
					}
					if ($vs_opt = $va_mapping_items[$vn_preferred_label_mapping_id]['settings']['displaynameFormat']) {
						$va_label_val = DataMigrationUtils::splitEntityName($vs_label_val, array('displaynameFormat' => $vs_opt, 'doNotParse' => $va_mapping_items[$vn_preferred_label_mapping_id]['settings']['doNotParse']));
						$vs_label_val = $va_label_val['displayname'];
					}
					$va_pref_label_values[$vs_preferred_label_mapping_fld] = $vs_label_val;
				}
				$vs_display_label = isset($va_pref_label_values[$vs_label_display_fld]) ? $va_pref_label_values[$vs_label_display_fld] : $vs_idno;
			
				//
				// Look for existing record?
				//
				
				$vb_was_preferred_label_match = false;
				
				$va_base_criteria = ($vs_type) ? ['type_id' => $vs_type] : [];
				
				if (is_array($pa_force_import_for_primary_keys) && sizeof($pa_force_import_for_primary_keys) > 0) {
					$vn_id = array_shift($pa_force_import_for_primary_keys);
					if (!$t_subject->load($vn_id)) { 
						$o_log->logInfo(_t('[%1] Skipped import because forced primary key \'%1\' does not exist', $vn_id));
						ca_data_importers::$s_num_records_skipped++;
						continue;	// skip because primary key does not exist
					}
				} elseif ($vs_existing_record_policy != 'none') {
					switch($vs_existing_record_policy) {
						case 'skip_on_idno':
							if (!$vb_idno_is_template) {
								$va_ids = call_user_func_array($t_subject->tableName()."::find", array(
									array_merge($va_base_criteria, array($t_subject->getProperty('ID_NUMBERING_ID_FIELD') => $vs_idno, 'deleted' => 0)),
									array('returnAs' => 'ids', 'purifyWithFallback' => true, 'transaction' => $o_trans)
								));
								if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
									$o_log->logInfo(_t('[%1] Skipped import because of existing record matched on identifier by policy %2', $vs_idno, $vs_existing_record_policy));
									ca_data_importers::$s_num_records_skipped++;
									continue(2);	// skip because idno matched
								}
							}
							break;
						case 'skip_on_preferred_labels':
							$va_ids = call_user_func_array($t_subject->tableName()."::find", array(
								array_merge($va_base_criteria, array('preferred_labels' => $va_pref_label_values, 'deleted' => 0)),
								array('returnAs' => 'ids', 'purifyWithFallback' => true, 'transaction' => $o_trans)
							));
							if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
								$o_log->logInfo(_t('[%1] Skipped import because of existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
								ca_data_importers::$s_num_records_skipped++;
								continue(2);	// skip because label matched
							}
							break;
						case 'merge_on_idno_and_preferred_labels':
						case 'merge_on_idno':
						case 'merge_on_idno_and_preferred_labels_with_replace':
						case 'merge_on_idno_with_replace':
							if (!$vb_idno_is_template) {
								$va_ids = call_user_func_array($t_subject->tableName()."::find", array(
									array_merge($va_base_criteria, array($t_subject->getProperty('ID_NUMBERING_ID_FIELD') => $vs_idno, 'deleted' => 0)),
									array('returnAs' => 'ids', 'purifyWithFallback' => true, 'transaction' => $o_trans)
								));
								if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
									$t_subject->load($va_ids[0]);
									$o_log->logInfo(_t('[%1] Merged with existing record matched on identifer by policy %2', $vs_idno, $vs_existing_record_policy));
									break;
								}
							}
							if (in_array($vs_existing_record_policy, array('merge_on_idno', 'merge_on_idno_with_replace'))) { break; }	// fall through if merge_on_idno_and_preferred_labels
						case 'merge_on_preferred_labels':
						case 'merge_on_preferred_labels_with_replace':
							$va_ids = call_user_func_array($t_subject->tableName()."::find", array(
								array_merge($va_base_criteria, array('preferred_labels' => $va_pref_label_values, 'deleted' => 0)),
								array('returnAs' => 'ids', 'purifyWithFallback' => true, 'transaction' => $o_trans)
							));
							if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
								$t_subject->load($va_ids[0]);
								$o_log->logInfo(_t('[%1] Merged with existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
								$vb_was_preferred_label_match = true;
							}
							break;	
						case 'overwrite_on_idno_and_preferred_labels':
						case 'overwrite_on_idno':
							if (!$vb_idno_is_template && $vs_idno) {
								$va_ids = call_user_func_array($t_subject->tableName()."::find", array(
									array_merge($va_base_criteria, array($t_subject->getProperty('ID_NUMBERING_ID_FIELD') => $vs_idno, 'deleted' => 0)),
									array('returnAs' => 'ids', 'purifyWithFallback' => true, 'transaction' => $o_trans)
								));
								if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
									$t_subject->load($va_ids[0]);
									$t_subject->setMode(ACCESS_WRITE);
									$t_subject->delete(true, array('hard' => true));
									if ($t_subject->numErrors()) {
										ca_data_importers::logImportError(_t('[%1] Could not delete existing record matched on identifier by policy %2', $vs_idno, $vs_existing_record_policy));
										// Don't stop?
									} else {
										$o_log->logInfo(_t('[%1] Overwrote existing record matched on identifier by policy %2', $vs_idno, $vs_existing_record_policy));
										$t_subject->clear();
										break;
									}
								}
							}
							if ($vs_existing_record_policy == 'overwrite_on_idno') { break; }	// fall through if overwrite_on_idno_and_preferred_labels
						case 'overwrite_on_preferred_labels':
							$va_ids = call_user_func_array($t_subject->tableName()."::find", array(
								array_merge($va_base_criteria, array('preferred_labels' => $va_pref_label_values, 'deleted' => 0)),
								array('returnAs' => 'ids', 'purifyWithFallback' => true, 'transaction' => $o_trans)
							));
							if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
								$t_subject->load($va_ids[0]);
								$t_subject->setMode(ACCESS_WRITE);
								$t_subject->delete(true, array('hard' => true));
							
								if ($t_subject->numErrors()) {
									ca_data_importers::logImportError(_t('[%1] Could not delete existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
									// Don't stop?
								} else {
									$o_log->logInfo(_t('[%1] Overwrote existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
									break;
								}
								$t_subject->clear();
							}
							break;
					}
				}
				
				$o_progress->next(_t("Importing %1", $vs_idno));
			
				if ($po_request && isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
					$ps_callback($po_request, $pn_file_number, $pn_number_of_files, $ps_source, ca_data_importers::$s_num_records_processed, $vn_num_items, _t("[%3/%4] Processing %1 (%2)", caTruncateStringWithEllipsis($vs_display_label, 50), $vs_idno, ca_data_importers::$s_num_records_processed, $vn_num_items), (time() - $vn_start_time), memory_get_usage(true), ca_data_importers::$s_num_records_processed, ca_data_importers::$s_num_import_errors); 
				}
			
				$vb_output_subject_preferred_label = false;
				$va_content_tree = array();
			
				foreach($va_items_by_group as $vn_group_id => $va_items) {
					$vb_use_parent_as_subject = false;	
					
					$va_group = $va_mapping_groups[$vn_group_id];
					$vs_group_destination = $va_group['destination'];
				
					$va_group_tmp = explode(".", $vs_group_destination);
					if ((sizeof($va_items) < 2) && (sizeof($va_group_tmp) > 2)) { array_pop($va_group_tmp); }
					$vs_target_table = $va_group_tmp[0];
					if (!($t_target = Datamodel::getInstance($vs_target_table, true))) {
						// Invalid target table
						$o_log->logWarn(_t('[%1] Skipped group %2 because target %3 is invalid', $vs_idno, $vn_group_id, $vs_target_table));
						continue;
					}
					if ($o_trans) { $t_target->setTransaction($o_trans); }
				
					$va_group_buf = array();
				
					foreach($va_items as $vn_item_id => $va_item) {
						if ($vb_use_as_single_value = caGetOption('useAsSingleValue', $va_item['settings'], false)) {
							// Force repeating values to be imported as a single value
							$va_vals = array(ca_data_importers::getValueFromSource($va_item, $o_reader, array('otherValues' => $va_rule_set_values, 'delimiter' => caGetOption('delimiter', $va_item['settings'], ''), 'returnAsArray' => false, 'lookahead' => caGetOption('lookahead', $va_item['settings'], 0), 'filterToTypes' => caGetOption('filterToTypes', $va_item['settings'], null), 'filterToRelationshipTypes' => caGetOption('filterToRelationshipTypes', $va_item['settings'], null), 'restrictToRelationshipTypes' => caGetOption('restrictToRelationshipTypes', $va_item['settings'], null), 'log' => $o_log, 'logIdno' => $vs_idno)));
						} else {
							$va_vals = ca_data_importers::getValueFromSource($va_item, $o_reader, array('otherValues' => $va_rule_set_values, 'returnAsArray' => true, 'environment' => $va_environment, 'lookahead' => caGetOption('lookahead', $va_item['settings'], 0), 'filterToTypes' => caGetOption('filterToTypes', $va_item['settings'], null), 'filterToRelationshipTypes' => caGetOption('filterToRelationshipTypes', $va_item['settings'], null), 'restrictToRelationshipTypes' => caGetOption('restrictToRelationshipTypes', $va_item['settings'], null), 'log' => $o_log, 'logIdno' => $vs_idno));
						}
					
						if (!sizeof($va_vals)) { $va_vals = array(0 => null); }	// consider missing values equivalent to blanks
				
				
						// Get location in content tree for addition of new content
						$va_item_dest = explode(".",  $va_item['destination']);
						$vs_item_terminal = $va_item_dest[sizeof($va_item_dest)-1];
						
						if (isset($va_item['settings']['filterEmptyValues']) && (bool)$va_item['settings']['filterEmptyValues'] && is_array($va_vals)) {
						    $va_vals = array_filter($va_vals, function($v) { return strlen($v); });
						}
						
						// Do value conversions
						foreach($va_vals as $vn_i => $vm_val) {
							// Evaluate skip-if-empty options before setting default value, addings prefix/suffix or formatting with templates
							// because "empty" refers to the source value before this sort of additive processing.
							if (isset($va_item['settings']['skipRowIfEmpty']) && (bool)$va_item['settings']['skipRowIfEmpty'] && !strlen($vm_val)) {
								$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 is empty', $vs_idno, $vn_row, $va_item['destination'], $vn_group_id));
								continue(4);
							}
							if (isset($va_item['settings']['skipGroupIfEmpty']) && (bool)$va_item['settings']['skipGroupIfEmpty'] && !strlen($vm_val)) {
								$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 is empty', $vs_idno, $vn_group_id, $va_item['destination']));
								continue(3);
							}
							if (isset($va_item['settings']['skipIfEmpty']) && (bool)$va_item['settings']['skipIfEmpty'] && !strlen($vm_val)) {
								$o_log->logInfo(_t('[%1] Skipped mapping because value for %2 is empty', $vs_idno, $va_item['destination']));
								continue(2);
							}
							if ($va_item['settings']['skipIfValue'] && !is_array($va_item['settings']['skipIfValue'])) { $va_item['settings']['skipIfValue'] = array($va_item['settings']['skipIfValue']); }
							if (isset($va_item['settings']['skipIfValue']) && is_array($va_item['settings']['skipIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipIfValue'])) {
								$o_log->logInfo(_t('[%1] Skipped mapping %2 because value for %3 matches value %4', $vs_idno, $vn_row, $vs_item_terminal, $vm_val));
								continue(2);
							}
							if ($va_item['settings']['skipIfNotValue'] && !is_array($va_item['settings']['skipIfNotValue'])) { $va_item['settings']['skipIfNotValue'] = array($va_item['settings']['skipIfNotValue']); }
							if (isset($va_item['settings']['skipIfNotValue']) && is_array($va_item['settings']['skipIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipIfNotValue'])) {
								$o_log->logInfo(_t('[%1] Skipped mapping %2 because value %4 for %3 is not in list of values', $vs_idno, $vn_row, $vs_item_terminal, $vm_val));
								continue(2);
							}
							if ($va_item['settings']['skipRowIfValue'] && !is_array($va_item['settings']['skipRowIfValue'])) { $va_item['settings']['skipRowIfValue'] = array($va_item['settings']['skipRowIfValue']); }
							if (isset($va_item['settings']['skipRowIfValue']) && is_array($va_item['settings']['skipRowIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipRowIfValue'])) {
								$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 matches value %5', $vs_idno, $vn_row, $vs_item_terminal, $vn_group_id, $vm_val));
								continue(4);
							}
							if ($va_item['settings']['skipRowIfNotValue'] && !is_array($va_item['settings']['skipRowIfNotValue'])) { $va_item['settings']['skipRowIfNotValue'] = array($va_item['settings']['skipRowIfNotValue']); }
							if (isset($va_item['settings']['skipRowIfNotValue']) && is_array($va_item['settings']['skipRowIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipRowIfNotValue'])) {
								$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 is not in list of values', $vs_idno, $vn_row, $vs_item_terminal, $vn_group_id, $vm_val));
								continue(4);
							}
							if ($va_item['settings']['skipGroupIfValue'] && !is_array($va_item['settings']['skipGroupIfValue'])) { $va_item['settings']['skipGroupIfValue'] = array($va_item['settings']['skipGroupIfValue']); }
							if (isset($va_item['settings']['skipGroupIfValue']) && is_array($va_item['settings']['skipGroupIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipGroupIfValue'])) {
								$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 matches value %4', $vs_idno, $vn_group_id, $vs_item_terminal, $vm_val));
								continue(3);
							}
						
							if ($va_item['settings']['skipGroupIfNotValue'] && !is_array($va_item['settings']['skipGroupIfNotValue'])) { $va_item['settings']['skipGroupIfNotValue'] = array($va_item['settings']['skipGroupIfNotValue']); }
							if (isset($va_item['settings']['skipGroupIfNotValue']) && is_array($va_item['settings']['skipGroupIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipGroupIfNotValue'])) {
								$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 matches is not in list of values', $vs_idno, $vn_group_id, $vs_item_terminal));
								continue(3);
							}
						
							if (isset($va_item['settings']['default']) && strlen($va_item['settings']['default']) && !strlen($vm_val)) {
								$vm_val = $va_item['settings']['default'];
							}
						
							// Apply prefix/suffix *AFTER* setting default
							if ($vm_val && isset($va_item['settings']['prefix']) && strlen($va_item['settings']['prefix'])) {
								$vm_val = $va_item['settings']['prefix'].$vm_val;
							}
							if ($vm_val && isset($va_item['settings']['suffix']) && strlen($va_item['settings']['suffix'])) {
								$vm_val .= $va_item['settings']['suffix'];
							}
						
							if (!is_array($vm_val) && ($vm_val[0] == '^') && preg_match("!^\^[^ ]+$!", $vm_val)) {
								// Parse placeholder
								if (!is_null($vm_parsed_val = BaseRefinery::parsePlaceholder($vm_val, $va_row_with_replacements, $va_item, $vn_i, array('reader' => $o_reader, 'returnAsString' => true)))) {
									$vm_val = $vm_parsed_val;
								}
							}
							if (isset($va_item['settings']['formatWithTemplate']) && strlen($va_item['settings']['formatWithTemplate'])) {
								$vm_val = DisplayTemplateParser::processTemplate($va_item['settings']['formatWithTemplate'], array_replace($va_row_with_replacements, array((string)$va_item['source'] => ca_data_importers::replaceValue($vm_val, $va_item, ['log' => $o_log, 'logIdno' => $vs_idno]))), array('getFrom' => $o_reader));
							}
						
							if (isset($va_item['settings']['applyRegularExpressions']) && is_array($va_item['settings']['applyRegularExpressions'])) {
								if(is_array($va_item['settings']['applyRegularExpressions'])) {
									foreach($va_item['settings']['applyRegularExpressions'] as $vn_regex_index => $va_regex) {
										if (!strlen($va_regex['match'])) { continue; }
										$vm_val = preg_replace("!".str_replace("!", "\\!", $va_regex['match'])."!".((isset($va_regex['caseSensitive']) && (bool)$va_regex['caseSensitive']) ? '' : 'i') , $va_regex['replaceWith'], $vm_val);
									}
								}
							}
						
							$va_vals[$vn_i] = $vm_val;
							if ($o_reader->valuesCanRepeat()) {
								$va_row_with_replacements[$va_item['source']][$vn_i] = $va_row[$va_item['source']][$vn_i] = $va_row[mb_strtolower($va_item['source'])][$vn_i] = $vm_val;
							} else {
								$va_row_with_replacements[$va_item['source']] = $va_row[$va_item['source']] = $va_row[mb_strtolower($va_item['source'])] = $vm_val;
							}
						}
						
						// Process each value
						$vn_c = -1;
						foreach($va_vals as $vn_i => $vm_val) {
							$vn_c++;
						
							if (isset($va_item['settings']['convertNewlinesToHTML']) && (bool)$va_item['settings']['convertNewlinesToHTML'] && is_string($vm_val)) {
								$vm_val = nl2br($vm_val);
							}
							if (isset($va_item['settings']['collapseSpaces']) && (bool)$va_item['settings']['collapseSpaces'] && is_string($vm_val)) {
								$vm_val = preg_replace("![ ]+!", " ", $vm_val);
							}
					
							if (isset($va_item['settings']['restrictToTypes']) && is_array($va_item['settings']['restrictToTypes']) && !in_array($vs_type, $va_item['settings']['restrictToTypes'])) {
								$o_log->logInfo(_t('[%1] Skipped mapping %2 because of type restriction for type %3', $vs_idno, $vn_row, $vs_type));
								continue(2);
							}
						
							if ($va_item['settings']['skipRowIfValue'] && !is_array($va_item['settings']['skipRowIfValue'])) { $va_item['settings']['skipRowIfValue'] = array($va_item['settings']['skipRowIfValue']); }
							if (isset($va_item['settings']['skipRowIfValue']) && is_array($va_item['settings']['skipRowIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipRowIfValue'])) {
								$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 matches value %5', $vs_idno, $vn_row, $vs_item_terminal, $vn_group_id, $vm_val));
								continue(4);
							}
						
							if ($va_item['settings']['skipRowIfNotValue'] && !is_array($va_item['settings']['skipRowIfNotValue'])) { $va_item['settings']['skipRowIfNotValue'] = array($va_item['settings']['skipRowIfNotValue']); }
							if (isset($va_item['settings']['skipRowIfNotValue']) && is_array($va_item['settings']['skipRowIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipRowIfNotValue'])) {
								$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 is not in list of values', $vs_idno, $vn_row, $vs_item_terminal, $vn_group_id, $vm_val));
								continue(4);
							}
						
							if (isset($va_item['settings']['skipRowIfExpression']) && strlen(trim($va_item['settings']['skipRowIfExpression']))) {
									
								try {
									if($vm_ret = ExpressionParser::evaluate($va_item['settings']['skipRowIfExpression'], $va_row_with_replacements)) {
										$o_log->logInfo(_t('[%1] Skipped row %2 because expression %3 is true', $vs_idno, $vn_row, $va_item['settings']['skipRowIfExpression']));
										continue(4);
									}
								} catch (Exception $e) {
									$o_log->logDebug("[%1] Could not evaluate skipRowIfExpression %2: %3", $vs_idno, $va_item['settings']['skipRowIfExpression'], $e->getMessage());
								}
							}

							if ($va_item['settings']['skipIfValue'] && !is_array($va_item['settings']['skipIfValue'])) { $va_item['settings']['skipIfValue'] = array($va_item['settings']['skipIfValue']); }
							if (isset($va_item['settings']['skipIfValue']) && is_array($va_item['settings']['skipIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipIfValue'])) {
								$o_log->logInfo(_t('[%1] Skipped mapping %2 because value for %3 matches value %4', $vs_idno, $vn_row, $vs_item_terminal, $vm_val));
								continue(2);
							}
						
							if ($va_item['settings']['skipIfNotValue'] && !is_array($va_item['settings']['skipIfNotValue'])) { $va_item['settings']['skipIfNotValue'] = array($va_item['settings']['skipIfNotValue']); }
							if (isset($va_item['settings']['skipIfNotValue']) && is_array($va_item['settings']['skipIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipIfNotValue'])) {
								$o_log->logInfo(_t('[%1] Skipped mapping %2 because value %4 for %3 is not in list of values', $vs_idno, $vn_row, $vs_item_terminal, $vm_val));
								continue(2);
							}
						
							if (isset($va_item['settings']['skipGroupIfExpression']) && strlen(trim($va_item['settings']['skipGroupIfExpression']))) {
								try {
								   if($vm_ret = ExpressionParser::evaluate($va_item['settings']['skipGroupIfExpression'], $va_row_with_replacements)) {
										$o_log->logInfo(_t('[%1] Skipped group %2 because skipRowIfExpression %3 is true', $vs_idno, $vn_group_id, $va_item['settings']['skipGroupIfExpression']));
										continue(3);
									}
								} catch (Exception $e) {
									$o_log->logDebug("[%1] Could not evaluate expression %2: %3", $vs_idno, $va_item['settings']['skipGroupIfExpression'], $e->getMessage());
								}
							}
						
							if ($va_item['settings']['skipGroupIfValue'] && !is_array($va_item['settings']['skipGroupIfValue'])) { $va_item['settings']['skipGroupIfValue'] = array($va_item['settings']['skipGroupIfValue']); }
							if (isset($va_item['settings']['skipGroupIfValue']) && is_array($va_item['settings']['skipGroupIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipGroupIfValue'])) {
								$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 matches value %4', $vs_idno, $vn_group_id, $vs_item_terminal, $vm_val));
								continue(3);
							}
						
							if ($va_item['settings']['skipGroupIfNotValue'] && !is_array($va_item['settings']['skipGroupIfNotValue'])) { $va_item['settings']['skipGroupIfNotValue'] = array($va_item['settings']['skipGroupIfNotValue']); }
							if (isset($va_item['settings']['skipGroupIfNotValue']) && is_array($va_item['settings']['skipGroupIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipGroupIfNotValue'])) {
								$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 matches is not in list of values', $vs_idno, $vn_group_id, $vs_item_terminal));
								continue(3);
							}
						
							if (isset($va_item['settings']['skipIfExpression']) && strlen(trim($va_item['settings']['skipIfExpression']))) {
								try {
									if($vm_ret = ExpressionParser::evaluate($va_item['settings']['skipIfExpression'], $va_row_with_replacements)) {
										$o_log->logInfo(_t('[%1] Skipped mapping because skipIfExpression %2 is true', $vs_idno, $va_item['settings']['skipIfExpression']));
										continue(2);
									}
								} catch (Exception $e) {
									$o_log->logDebug("[%1] Could not evaluate expression %2: %3", $vs_idno, $va_item['settings']['skipIfExpression'], $e->getMessage());
								}
							}
						
							if (($vn_type_id_mapping_item_id && ($vn_item_id == $vn_type_id_mapping_item_id))) {
								continue; 
							}
					
							if($vn_idno_mapping_item_id && ($vn_item_id == $vn_idno_mapping_item_id)) { 
								continue; 
							}
							if (is_null($vm_val)) { continue; }
					
							// Get mapping error policy
							$vb_item_error_policy_is_default = false;
							if (!isset($va_item['settings']['errorPolicy']) || !in_array($vs_item_error_policy = $va_item['settings']['errorPolicy'], array('ignore', 'stop'))) {
								$vs_item_error_policy = $vs_import_error_policy;
								$vb_item_error_policy_is_default = true;
							}
					
							//
							if (isset($va_item['settings']['relationshipType']) && strlen($vs_rel_type = $va_item['settings']['relationshipType']) && ($vs_target_table != $vs_subject_table)) {
								$va_group_buf[$vn_c]['_relationship_type'] = $vs_rel_type;
							}
						
							if (isset($va_item['settings']['matchOn'])) {
								$va_group_buf[$vn_c]['_matchOn'] = $va_item['settings']['matchOn'];
							}
							
							if ($va_item['settings']['skipIfDataPresent']) {
								$va_group_buf[$vn_c]['_skipIfDataPresent'] = true;
							}
						
					
							// Is it a constant value?
							if (preg_match("!^_CONSTANT_:[\d]+:(.*)!", $va_item['source'], $va_matches)) {
							    $va_group_buf[$vn_c][$vs_item_terminal] = $vm_val = $va_matches[1];		// Set it and go onto the next item
						
								if (($vs_target_table == $vs_subject_table_name) && (($vs_k =array_search($vn_item_id, $va_mandatory_field_mapping_ids)) !== false)) {
									$va_mandatory_field_values[$vs_k] = $vm_val;
								}
							}
					
							// Perform refinery call (if required) per value
							if (isset($va_item['settings']['refineries']) && is_array($va_item['settings']['refineries'])) {
								foreach($va_item['settings']['refineries'] as $vs_refinery) {
									if (!$vs_refinery) { continue; }
								
									if ($o_refinery = RefineryManager::getRefineryInstance($vs_refinery)) {
										$va_refined_values = $o_refinery->refine($va_content_tree, $va_group, $va_item, $va_row_with_replacements, array('mapping' => $t_mapping, 'source' => $ps_source, 'subject' => $t_subject, 'locale_id' => $vn_locale_id, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'reader' => $o_reader, 'valueIndex' => $vn_i));

										if (!$va_refined_values || (is_array($va_refined_values) && !sizeof($va_refined_values))) { continue(2); }
									
										if ($o_refinery->returnsMultipleValues()) {
											foreach($va_refined_values as $va_refined_value) {
											    if ($o_refinery->returnsRowIDs()) { $va_refined_value['_treatNumericValueAsID'] = true; }
												$va_refined_value['_errorPolicy'] = $vs_item_error_policy;
												if (!is_array($va_group_buf[$vn_c])) { $va_group_buf[$vn_c] = array(); }
												$va_group_buf[$vn_c] = array_merge($va_group_buf[$vn_c], $va_refined_value);
												$vn_c++;
											}
										} else {
											$va_group_buf[$vn_c]['_errorPolicy'] = $vs_item_error_policy;
											if ($o_refinery->returnsRowIDs()) { $va_group_buf[$vn_c]['_treatNumericValueAsID'] = true; }
											$va_group_buf[$vn_c][$vs_item_terminal] = $va_refined_values;
											$vn_c++;
										}
								
										if (($vs_target_table == $vs_subject_table_name) && (($vs_k =array_search($vn_item_id, $va_mandatory_field_mapping_ids)) !== false)) {
											$va_mandatory_field_values[$vs_k] = $vm_val;
										}
										continue(2);
									} else {
										ca_data_importers::logImportError(_t('[%1] Invalid refinery %2 specified', $vs_idno, $vs_refinery));
									}
								}
							}
					
							if (($vs_target_table == $vs_subject_table_name) && (($vs_k =array_search($vn_item_id, $va_mandatory_field_mapping_ids)) !== false)) {
								$va_mandatory_field_values[$vs_k] = $vm_val;
							}
							
							$vn_max_length = (!is_array($vm_val) && isset($va_item['settings']['maxLength']) && (int)$va_item['settings']['maxLength']) ? (int)$va_item['settings']['maxLength'] : null;
					
							if(isset($va_item['settings']['delimiter']) && $va_item['settings']['delimiter']) {
								if (!is_array($va_item['settings']['delimiter'])) { $va_item['settings']['delimiter'] = array($va_item['settings']['delimiter']); }
							
								if (sizeof($va_item['settings']['delimiter'])) {
									foreach($va_item['settings']['delimiter'] as $vn_index => $vs_delim) {
										$va_item['settings']['delimiter'][$vn_index] = preg_quote($vs_delim, "!");
									}
									$va_val_list = preg_split("!(".join("|", $va_item['settings']['delimiter']).")!", $vm_val);
						
									// Add delimited values
									$vn_orig_c = $vn_c;
									foreach($va_val_list as $vs_list_val) {
										$vs_list_val = trim(ca_data_importers::replaceValue($vs_list_val, $va_item, ['log' => $o_log, 'logIdno' => $vs_idno]));
										if ($vn_max_length && (mb_strlen($vs_list_val) > $vn_max_length)) {
											$vs_list_val = mb_substr($vs_list_val, 0, $vn_max_length);
										}
										if (!is_array($va_group_buf[$vn_c])) { $va_group_buf[$vn_c] = array(); }
										
										
										switch($vs_item_terminal) {
											case 'preferred_labels':
											case 'nonpreferred_labels':
												if ($t_instance = Datamodel::getInstance($vs_target_table, true)) {
													$va_group_buf[$vn_c][$t_instance->getLabelDisplayField()] = $vs_list_val;
												}
												
												if (!$vb_item_error_policy_is_default || !isset($va_group_buf[$vn_c]['_errorPolicy'])) {
													if (is_array($va_group_buf[$vn_c])) { $va_group_buf[$vn_c]['_errorPolicy'] = $vs_item_error_policy; }
												}
								
												if ($vs_item_terminal == 'preferred_labels') { $vs_preferred_label_for_log = $vm_val; }
												break;
											default:
												$va_group_buf[$vn_c] = array_merge($va_group_buf[$vn_c], array($vs_item_terminal => $vs_list_val, '_errorPolicy' => $vs_item_error_policy));
												if (!$vb_item_error_policy_is_default || !isset($va_group_buf[$vn_c]['_errorPolicy'])) {
													if (is_array($va_group_buf[$vn_c])) { $va_group_buf[$vn_c]['_errorPolicy'] = $vs_item_error_policy; }
												}
												break;
										}
										$vn_c++;
									}
									$vn_c = $vn_orig_c;
									
									// Replicate constants as needed: constant is already set for first constant in group, 
									// but will not be set for repeats so we set them here
									
									if (sizeof($va_group_buf) > 1) {
                                        foreach($va_items_by_group[$vn_group_id] as $va_gitem) {
                                            if (preg_match("!^_CONSTANT_:[\d]+:(.*)!", $va_gitem['source'], $va_gmatches)) {
                                                
                                                $va_gitem_dest = explode(".",  $va_gitem['destination']);
                                                $vs_gitem_terminal = $va_gitem_dest[sizeof($va_gitem_dest)-1];
                                                for($vn_gc=1; $vn_gc < sizeof($va_group_buf); $vn_gc++) {
                                                    $va_group_buf[$vn_gc][$vs_gitem_terminal] = $va_gmatches[1];		// Set it and go onto the next item
                                                }
                                            }
                                    
                                        }
                                    }
						
									continue;	// Don't add "regular" value below
								}
							}
					
							if ($vn_max_length && (mb_strlen($vm_val) > $vn_max_length)) {
								$vm_val = mb_substr($vm_val, 0, $vn_max_length);
							}
						
						
							if (in_array('preferred_labels', $va_item_dest) || in_array('nonpreferred_labels', $va_item_dest)) {	
								if (isset($va_item['settings']['truncateLongLabels']) && $va_item['settings']['truncateLongLabels']) {
									$va_group_buf[$vn_c]['_truncateLongLabels'] = true;
								}
							}
							
							switch($vs_item_terminal) {
								case 'preferred_labels':
								case 'nonpreferred_labels':
									if ($t_instance = Datamodel::getInstance($vs_target_table, true)) {
										$va_group_buf[$vn_c][$t_instance->getLabelDisplayField()] = $vm_val;
									}
									if (!$vb_item_error_policy_is_default || !isset($va_group_buf[$vn_c]['_errorPolicy'])) {
										if (is_array($va_group_buf[$vn_c])) { $va_group_buf[$vn_c]['_errorPolicy'] = $vs_item_error_policy; }
									}
								
									if ($vs_item_terminal == 'preferred_labels') { $vs_preferred_label_for_log = $vm_val; }
							
									break;
								default:
									$va_group_buf[$vn_c][$vs_item_terminal] = $vm_val;
									if (!$vb_item_error_policy_is_default || !isset($va_group_buf[$vn_c]['_errorPolicy'])) {
										if (is_array($va_group_buf[$vn_c])) { $va_group_buf[$vn_c]['_errorPolicy'] = $vs_item_error_policy; }
									}
									break;
							}
						} // end foreach($va_vals as $vm_val)
					}
					
					
					if (sizeof($va_group_buf) > 1) {
                        foreach($va_items_by_group[$vn_group_id] as $va_gitem) {
                            if (preg_match("!^_CONSTANT_:[\d]+:(.*)!", $va_gitem['source'], $va_gmatches)) {
                                $va_gitem_dest = explode(".",  $va_gitem['destination']);
                                $vs_gitem_terminal = $va_gitem_dest[sizeof($va_gitem_dest)-1];
                                for($vn_gc=1; $vn_gc < sizeof($va_group_buf); $vn_gc++) {
                                    $va_group_buf[$vn_gc][$vs_gitem_terminal] = $va_gmatches[1];		// Set it and go onto the next item
                                }
                            }
                
                        }
                    }
				
                    // Replicate constants as needed: constant is already set for first constant in group, 
                    // but will not be set for repeats so we set them here
					foreach($va_group_buf as $vn_group_index => $va_group_data) {	
						$va_ptr =& $va_content_tree;
						foreach($va_group_tmp as $vs_tmp) {
							if(!is_array($va_ptr[$vs_tmp])) { $va_ptr[$vs_tmp] = array(); }
							$va_ptr =& $va_ptr[$vs_tmp];
							if ($vs_tmp == $vs_target_table) {	// add numeric index after table to ensure repeat values don't overwrite each other
								$va_parent =& $va_ptr;
								$va_ptr[] = array();
								$va_ptr =& $va_ptr[sizeof($va_ptr)-1];
							}
						}
						$va_ptr = $va_group_data;
					}
				
				
			
					if ($va_item['settings']['useParentAsSubject']) {
						$vb_use_parent_as_subject = true;
					}
				}
			
				//
				// Process out self-relationships
				//
				if(is_array($va_content_tree[$vs_subject_table])) {
					$va_self_related_content = array();
					foreach($va_content_tree[$vs_subject_table] as $vn_i => $va_element_data) {
						if (isset($va_element_data['_relationship_type'])) {
							$va_self_related_content[] = $va_element_data;
							unset($va_content_tree[$vs_subject_table][$vn_i]);
						}
					}
					if (sizeof($va_self_related_content) > 0) {
						$va_content_tree["related.{$vs_subject_table}"] = $va_self_related_content;
					}
				}
			

				$o_log->logDebug(_t('Finished building content tree for %1 at %2 seconds [%3]', $vs_idno, $t->getTime(4), $vn_row));
				$o_log->logDebug(_t("Content tree is\n%1", print_R($va_content_tree, true)));
			
				if ((bool)$t_mapping->getSetting('dontDoImport')) {
					$o_log->logDebug(_t("Skipped import of row because dontDoImport was set"));
					continue;
				}
			
				//
				// Process data in subject record
				//
				// print_r($va_content_tree);
				// die("END\n\n");
				//continue;
				if (!($opa_app_plugin_manager->hookDataImportContentTree(array('mapping' => $t_mapping, 'content_tree' => &$va_content_tree, 'idno' => &$vs_idno, 'type_id' => &$vs_type, 'transaction' => &$o_trans, 'log' => &$o_log, 'reader' => $o_reader, 'environment' => $va_environment,'importEvent' => $o_event, 'importEventSource' => $vn_row)))) {
					continue;
				}
			
				//print_r($va_content_tree);
			    //die("done\n");
			
				if (!sizeof($va_content_tree) && !str_replace("%", "", $vs_idno)) { continue; }
	
				if ($vb_use_parent_as_subject) { 
					foreach($va_content_tree[$vs_subject_table] as $vn_i => $va_element_data) {
						foreach($va_element_data as $vs_element => $va_element_value) {
							if ($vs_element == 'parent_id') {
								if (($vn_parent_id = (int)$va_element_value['parent_id']) > 0) {
									if ($t_subject->load($vn_parent_id)) {
										$va_content_tree[$vs_subject_table][$vn_i]['parent_id']['parent_id'] = $t_subject->get('parent_id');
										$va_content_tree[$vs_subject_table][$vn_i]['idno']['idno'] = $t_subject->get('idno');
									}
								}
							}
						}
					}
				}
			
				if (!$t_subject->getPrimaryKey()) {
					$o_event->beginItem($vn_row, $t_subject->tableNum(), 'I') ;
					$t_subject->setMode(ACCESS_WRITE);
					$t_subject->set($vs_type_id_fld, $vs_type);
					if ($vb_idno_is_template) {
						$t_subject->setIdnoWithTemplate($vs_idno);
					} else {
						$t_subject->set($vs_idno_fld, $vs_idno, array('assumeIdnoForRepresentationID' => true, 'assumeIdnoStubForLotID' => true, 'tryObjectIdnoForRepresentationID' => true));	// assumeIdnoStubForLotID forces ca_objects.lot_id values to always be considered as a potential idno_stub first, before use as a ca_objects.lot_id
					}
				
					// Look for parent_id in the content tree
					$vs_parent_id_fld = $t_subject->getProperty('HIERARCHY_PARENT_ID_FLD');
					foreach ($va_content_tree as $vs_table_name => $va_content) {
						if ($vs_table_name == $vs_subject_table) {
							foreach ($va_content as $va_element_data) {
								foreach ($va_element_data as $vs_element => $va_element_content) {
									switch ($vs_element) {
										case $vs_parent_id_fld:
											if ($va_element_content[$vs_parent_id_fld]) {
												$t_subject->set($vs_parent_id_fld, $va_element_content[$vs_parent_id_fld], array('treatParentIDAsIdno' => true));
											}
											break;
									}
								}
							}
						}
					}
				
					foreach($va_mandatory_field_mapping_ids as $vs_mandatory_field => $vn_mandatory_mapping_item_id) {
						$va_field_info = $t_subject->getFieldInfo($vs_mandatory_field);
						$va_opts = array('assumeIdnoForRepresentationID' => true, 'assumeIdnoStubForLotID' => true, 'tryObjectIdnoForRepresentationID' => true, 'treatParentIDAsIdno' => true);
						if($va_field_info['FIELD_TYPE'] == FT_MEDIA) {
							$va_opts['original_filename'] = basename($va_mandatory_field_values[$vs_mandatory_field]);
						}
						$t_subject->set($vs_mandatory_field, $va_mandatory_field_values[$vs_mandatory_field], $va_opts);
					}
					try {
						$t_subject->insert(['queueIndexing' => true]);
					} catch (DatabaseException $e) {
						ca_data_importers::logImportError($e->getMessage(), $va_log_import_error_opts);
						if ($vs_import_error_policy == 'stop') {
							$o_log->logAlert(_t('Import stopped due to import error policy'));
						
							$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
							if ($o_trans) { $o_trans->rollback(); }
							return false;
						}
						continue;
					}
					if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could not insert new record for %1: ", $t_subject->getProperty('NAME_SINGULAR')), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
					
						ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
						if ($vs_import_error_policy == 'stop') {
							$o_log->logAlert(_t('Import stopped due to import error policy'));
						
							$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
							if ($o_trans) { $o_trans->rollback(); }
							return false;
						}
						continue;
					}
					$va_ids_created[] = $t_subject->getPrimaryKey();
					$o_log->logDebug(_t('Created idno %1 at %2 seconds [id=%3]', $vs_idno, $t->getTime(4), $t_subject->getPrimaryKey()));
				} else {
					$o_event->beginItem($vn_row, $t_subject->tableNum(), 'U') ;
					// update
					$t_subject->setMode(ACCESS_WRITE);
					
					if ($vn_idno_mapping_item_id || !$t_subject->get($vs_idno_fld)) {
						if ($vb_idno_is_template) {
							$t_subject->setIdnoWithTemplate($vs_idno);
						} else {
							$t_subject->set($vs_idno_fld, $vs_idno, array('assumeIdnoForRepresentationID' => true, 'assumeIdnoStubForLotID' => true, 'tryObjectIdnoForRepresentationID' => true, 'treatParentIDAsIdno' => true));	// assumeIdnoStubForLotID forces ca_objects.lot_id values to always be considered as a potential idno_stub first, before use as a ca_objects.lot_di
						}
					}
				
					$t_subject->update(['queueIndexing' => true]);
					if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could not update matched record"), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
						ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
						if ($vs_import_error_policy == 'stop') {
							$o_log->logAlert(_t('Import stopped due to import error policy'));
						
							$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
							if ($o_trans) { $o_trans->rollback(); }
							return false;
						}
						continue;
					}
					$va_ids_updated[] = $t_subject->getPrimaryKey();
					$t_subject->clearErrors();
					if (sizeof($va_preferred_label_mapping_ids) && ($t_subject->getPreferredLabelCount() > 0) && (!$vb_was_preferred_label_match)) {
						$vb_remove_labels = true;
						foreach($va_preferred_label_mapping_ids as $vn_preferred_label_mapping_id => $vs_fld) {
							if ($va_mapping_items[$vn_preferred_label_mapping_id]['settings']['skipIfDataPresent']) { $vb_remove_labels = false; break; }
						}
						
						if ($vb_remove_labels) {
							$t_subject->removeAllLabels(__CA_LABEL_TYPE_PREFERRED__, ['locales' => [$vn_locale_id]]);
							if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could not remove preferred labels from matched record"), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
								ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
								if ($vs_import_error_policy == 'stop') {
									$o_log->logAlert(_t('Import stopped due to import error policy'));
							
									$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
									if ($o_trans) { $o_trans->rollback(); }
									return false;
								}
							}
						}
					}
					if (sizeof($va_nonpreferred_label_mapping_ids) && ($t_subject->getNonPreferredLabelCount() > 0)) {
						if (preg_match("!^merge!", $vs_existing_record_policy)) {
						    $vb_remove_labels = false;
						} else {
							$vb_remove_labels = true;
							foreach($va_nonpreferred_label_mapping_ids as $vn_nonpreferred_label_mapping_id => $vs_fld) {
								if ($va_mapping_items[$vn_nonpreferred_label_mapping_id]['settings']['skipIfDataPresent']) { $vb_remove_labels = false; break; }
							}
							if ($vb_remove_labels) {
								$t_subject->removeAllLabels(__CA_LABEL_TYPE_NONPREFERRED__, ['locales' => [$vn_locale_id]]);
								if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could not remove nonpreferred labels from matched record"), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
									ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
									if ($vs_import_error_policy == 'stop') {
										$o_log->logAlert(_t('Import stopped due to import error policy'));
							
										$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
										if ($o_trans) { $o_trans->rollback(); }
										return false;
									}
								}
							}
						}
					}
				
					$o_log->logDebug(_t('Updated idno %1 at %2 seconds', $vs_idno, $t->getTime(4)));
				}
			
			
				
					if ($vs_idno_fld && ($o_idno = $t_subject->getIDNoPlugInInstance())) {
						$va_values = $o_idno->htmlFormValuesAsArray($vs_idno_fld, $t_subject->get($vs_idno_fld));
						if (!is_array($va_values)) { $va_values = array($va_values); }
						if (($vs_proc_idno = join($o_idno->getSeparator(), $va_values)) && ($vs_proc_idno != $vs_idno)) {
							$t_subject->set($vs_idno_fld, $vs_proc_idno);
							$t_subject->update(['queueIndexing' => true]);
						
							if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could update idno"), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
								ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
								if ($vs_import_error_policy == 'stop') {
									$o_log->logAlert(_t('Import stopped due to import error policy'));
								
									$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
									if ($o_trans) { $o_trans->rollback(); }
									return false;
								}
								continue;
							}
						}
					}
		
				$o_log->logDebug(_t('Started insert of content tree for idno %1 at %2 seconds [id=%3]', $vs_idno, $t->getTime(4), $t_subject->getPrimaryKey()));
				$va_elements_set_for_this_record = array();
				foreach($va_content_tree as $vs_table_name => $va_content) {
					if ($vs_table_name == $vs_subject_table) {		
						foreach($va_content as $vn_i => $va_element_data) {
							foreach($va_element_data as $vs_element => $va_element_content) {	
								$o_log->logDebug(_t('Started insert of %1.%2 for idno %3 at %4 seconds [id=%3]', $vs_table_name, $vs_element, $vs_idno, $t->getTime(4), $t_subject->getPrimaryKey()));
				
								try {
									if (is_array($va_element_content)) { 														
										$vb_truncate_long_labels = caGetOption('_truncateLongLabels', $va_element_content, false);
										unset($va_element_content['_truncateLongLabels']);
									
										$vs_item_error_policy = $va_element_content['_errorPolicy'];
										unset($va_element_content['_errorPolicy']); 
										
										$vb_skip_if_data_present = $va_element_content['_skipIfDataPresent'];
										unset($va_element_content['_skipIfDataPresent']);
																					
										$vb_treat_numeric_value_as_id = caGetOption('_treatNumericValueAsID', $va_element_content, false);
										unset($va_element_content['_treatNumericValueAsID']);
									} else {
										$vb_truncate_long_labels = false;
										$vb_skip_if_data_present = false;
										$vb_treat_numeric_value_as_id = false;
										$vs_item_error_policy = null;
									}
								
									$t_subject->clearErrors();
									$t_subject->setMode(ACCESS_WRITE);
									switch($vs_element) {
										case 'preferred_labels':
											if (!$vb_was_preferred_label_match) {
												if (
													(!isset($va_element_content[$vs_disp_field = $t_subject->getLabelDisplayField()]) || !strlen($va_element_content[$vs_disp_field]))
													&&
													(sizeof(array_filter($t_subject->getSecondaryLabelDisplayFields(), function($v) use ($va_element_content) { return isset($va_element_content[$v]) && strlen($va_element_content[$v]); })) == 0)
												) { 
													$va_element_content[$vs_disp_field] = '['.caGetBlankLabelText().']'; 
												}
												
												if ($vb_skip_if_data_present && ($t_subject->getLabelCount(true, $vn_locale_id) > 0)) { continue(2); }
												
												$t_subject->replaceLabel(
													$va_element_content, $vn_locale_id, isset($va_element_content['type_id']) ? $va_element_content['type_id'] : null, true, array('truncateLongLabels' => $vb_truncate_long_labels)
												);
												if ($t_subject->numErrors() == 0) {
													$vb_output_subject_preferred_label = true;
												}
										
												if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add preferred label to %2. Record was deleted because no preferred label could be applied: ", $vs_idno, $t_subject->tableName()), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
													ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
													$t_subject->delete(true, array('hard' => false));
											
													if ($vs_import_error_policy == 'stop') {
														$o_log->logAlert(_t('Import stopped due to import error policy %1', $vs_import_error_policy));
												
														$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
														if ($o_trans) { $o_trans->rollback(); }
														return false;
													}
													if ($vs_item_error_policy == 'stop') {
														$o_log->logAlert(_t('Import stopped due to mapping error policy'));
														if ($o_trans) { $o_trans->rollback(); }
														return false;
													}
													continue(3);
												}
											}
											break;
										case 'nonpreferred_labels':
											if ($vb_skip_if_data_present && ($t_subject->getLabelCount(false, $vn_locale_id) > 0)) { continue(2); }
											$t_subject->addLabel(
												$va_element_content, $vn_locale_id, isset($va_element_content['type_id']) ? $va_element_content['type_id'] : null, false, array('truncateLongLabels' => $vb_truncate_long_labels)
											);
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add non-preferred label to %2:", $vs_idno, $t_subject->tableName()), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
												continue(3);
											}

											break;
										case '_children':	// child records (usually generated by a refinery) 
											self::_processChildren($t_subject->tableName(), $t_subject->getPrimaryKey(), $va_element_content, ['log' => $o_log, 'transaction' => $o_trans, 'matchOn' => ['idno']]);
											break;
										default:
											if ($t_subject->hasField($vs_element) && (($vs_element != $t_subject->primaryKey(true)) && ($vs_element != $t_subject->primaryKey()))) {
												if ($vb_skip_if_data_present && strlen($t_subject->get($vs_element))) { continue(2); } 
												$va_field_info = $t_subject->getFieldInfo($vs_element);
												$va_opts = array('assumeIdnoForRepresentationID' => !$vb_treat_numeric_value_as_id, 'assumeIdnoStubForLotID' => !isset($va_element_content['_matchOn']) && !$vb_treat_numeric_value_as_id, 'tryObjectIdnoForRepresentationID' => !$vb_treat_numeric_value_as_id, 'treatParentIDAsIdno' => !$vb_treat_numeric_value_as_id);
												if($va_field_info['FIELD_TYPE'] == FT_MEDIA) {
													$va_opts['original_filename'] = basename($va_element_content[$vs_element]);
												}
												
												$vs_content = isset($va_element_content[$vs_element]) ? $va_element_content[$vs_element] : $va_element_content;
												$t_subject->set($vs_element, $vs_content, $va_opts);
												$t_subject->update(['queueIndexing' => true]);
												if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add intrinsic %2 to %3:", $vs_idno, $vs_elenent, $t_subject->tableName()), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
													ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
													if ($vs_item_error_policy == 'stop') {
														$o_log->logAlert(_t('Import stopped due to mapping error policy'));
													
														$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
												
														if ($o_trans) { $o_trans->rollback(); }
														return false;
													}
													continue(3);
												}
												
												try {
													if ((is_array($va_rel_info = Datamodel::getManyToOneRelations($vs_table_name, $vs_element)) && (sizeof($va_rel_info) > 0)) && is_array($va_element_data[$vs_element]['_related_related']) && sizeof($va_element_data[$vs_element]['_related_related'])) {
														foreach($va_element_data[$vs_element]['_related_related'] as $vs_rel_rel_table => $va_rel_rels) {
															foreach($va_rel_rels as $vn_i => $va_rel_rel) {
																if (!($t_rel_instance = Datamodel::getInstance($va_rel_info['one_table']))) { 
																	$o_log->logWarn(_t("[%1] Could not instantiate related table %2", $vs_idno, $vs_table_name));
																	continue; 
																}
																if ($o_trans) { $t_rel_instance->setTransaction($o_trans); }
																if ($t_rel_instance->load($va_element_content[$vs_element])) {
																	if ($t_rel_rel = $t_rel_instance->addRelationship($vs_rel_rel_table, $va_rel_rel['id'], $va_rel_rel['_relationship_type'])) {
																		$o_log->logInfo(_t('[%1] Related %2 (%3) to related %4 with relationship %5', $vs_idno, Datamodel::getTableProperty($vs_rel_rel_table, 'NAME_SINGULAR'), $va_rel_rel['id'], $t_rel_instance->getProperty('NAME_SINGULAR'), trim($va_rel_rel['_relationship_type'])));
																	} else {
																		if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related %2 (%3) to related %4 with relationship %5:", $vs_idno, Datamodel::getTableProperty($vs_rel_rel_table, 'NAME_SINGULAR'), $va_rel_rel['id'], $t_rel_instance->getProperty('NAME_SINGULAR'), trim($va_rel_rel['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
																			ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
																		}
																	}
																}
															}
														}
													 }
												} catch (Exception $e) {
													// noop
												}
												break;
											}
										
											if (($vs_subject_table == 'ca_representation_annotations') && ($vs_element == 'properties')) {
												foreach($va_element_content as $vs_prop => $vs_prop_val) {
													if ($vb_skip_if_data_present && strlen($t_subject->getPropertyValue($vs_prop))) { continue; } 
													$t_subject->setPropertyValue($vs_prop, $vs_prop_val);
												}
												break;
											}
									
											if (is_array($va_element_content)) { $va_element_content['locale_id'] = $vn_locale_id; }
										
											if ($vb_skip_if_data_present && ($t_subject->getAttributeCountByElement($vs_element) > 0)) { continue(2); } 
											if (!isset($va_elements_set_for_this_record[$vs_element]) && !$va_elements_set_for_this_record[$vs_element] && in_array($vs_existing_record_policy, array('merge_on_idno_with_replace', 'merge_on_preferred_labels_with_replace', 'merge_on_idno_and_preferred_labels_with_replace'))) {
												$t_subject->removeAttributes($vs_element, array('force' => true));
											} 
											$va_elements_set_for_this_record[$vs_element] = true;
										
											$va_opts = array('showRepeatCountErrors' => true, 'alwaysTreatValueAsIdno' => true, 'log' => $o_log, 'logIdno' => $vs_idno);
											if ($va_match_on = caGetOption('_matchOn', $va_element_content, null)) {
												$va_opts['matchOn'] = $va_match_on;
											}
											$t_subject->addAttribute($va_element_content, $vs_element, null, $va_opts);
											$t_subject->update(['queueIndexing' => true]);
											
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Failed to add value for %2; values were %3: ", $vs_idno, $vs_element, ca_data_importers::formatValuesForLog($va_element_content)), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}

											break;
									}
								} catch(Exception $e) {
									// noop
								}
							}
						} 
					
						$t_subject->update(['queueIndexing' => true]);

						if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Invalid %2; values were %3: ", $vs_idno, $vs_element, ca_data_importers::formatValuesForLog($va_element_content)), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
							ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
							if ($vs_item_error_policy == 'stop') {
								$o_log->logAlert(_t('Import stopped due to mapping error policy'));
							
								$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
							
								if ($o_trans) { $o_trans->rollback(); }
								return false;
							}
						}
					} else {
						// related
						$vs_table_name = preg_replace('!^related\.!', '', $vs_table_name);
					
						$o_log->logDebug(_t('Started insert of %1.%2 for idno %3 at %4 seconds [id=%3]', $vs_table_name, $vs_element, $vs_idno, $t->getTime(4), $t_subject->getPrimaryKey()));
				
						foreach($va_content as $vn_i => $va_element_data) {
						        // Importing tags?
						        if ($vs_table_name === 'ca_item_tags') {
						            if(isset($va_element_data['ca_item_tags']['tag'])) {
						                $t_subject->addTag($va_element_data['ca_item_tags']['tag'], null, null, $va_element_data['ca_item_tags']['access'], null, ['forceModerated' => true]);
						            } elseif(isset($va_element_data['ca_item_tags']) && is_string($va_element_data['ca_item_tags'])) {
						                $t_subject->addTag($va_element_data['ca_item_tags'], null, null, 1, null, ['forceModerated' => true]);
						            }
						            continue;
						        }
						
						
								$va_match_on = caGetOption('_matchOn', $va_element_data, null);
								$vb_dont_create = caGetOption('_dontCreate', $va_element_data, false);
								$vb_ignore_parent = caGetOption('_ignoreParent', $va_element_data, false);
								$vb_treat_numeric_value_as_id = caGetOption('_treatNumericValueAsID', $va_element_data, false);
								
								$va_data_for_rel_table = $va_element_data;
								$va_nonpreferred_labels = isset($va_data_for_rel_table['nonpreferred_labels']) ? $va_data_for_rel_table['nonpreferred_labels'] : null;
								unset($va_data_for_rel_table['preferred_labels']);
								unset($va_data_for_rel_table['_relationship_type']);
								unset($va_data_for_rel_table['_type']);
								unset($va_data_for_rel_table['_parent_id']);
								unset($va_data_for_rel_table['_errorPolicy']);
								unset($va_data_for_rel_table['_matchOn']);
							
								$va_data_for_rel_table = array_merge($va_data_for_rel_table, ca_data_importers::_extractIntrinsicValues($va_data_for_rel_table, $vs_table_name));
			
								$t_subject->clearErrors();
						try {
						    
						        $vs_rel_label_display_fld = 'name';
						        if ($t_rel = Datamodel::getInstance($vs_table_name, true)) {
						            $vs_rel_label_display_fld = $t_rel->getLabelDisplayField();
						        }
						
						        $vs_name = '['.caGetBlankLabelText().']';
						        if(isset($va_element_data['preferred_labels'][$vs_rel_label_display_fld]) ) { $vs_name = $va_element_data['preferred_labels'][$vs_rel_label_display_fld]; }
						        elseif(isset($va_element_data['preferred_labels']) ) { $vs_name = $va_element_data['preferred_labels']; }
						        elseif(isset($va_element_data[$vs_rel_label_display_fld][$vs_rel_label_display_fld]) ) { $vs_name = $va_element_data[$vs_rel_label_display_fld][$vs_rel_label_display_fld]; }
						        elseif(isset($va_element_data[$vs_rel_label_display_fld]) ) { $vs_name = $va_element_data[$vs_rel_label_display_fld]; }
						        elseif(isset($va_element_data) && !is_array($va_element_data) && strlen($va_element_data)) { $vs_name = $va_element_data; }

								switch($vs_table_name) {
									case 'ca_objects':
										if ($vn_rel_id = DataMigrationUtils::getObjectID($vs_name, $va_element_data['_parent_id'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {

											// kill it if no relationship type is set ... unless its objects_x_representations
											// (from the representation side), where the rel type is optional
											if (
												!($vs_rel_type = $va_element_data['_relationship_type'])
												&&
												!($vs_rel_type = $va_element_data['idno']['_relationship_type'])
												&&
												($t_subject->tableName() != 'ca_object_representations')
											) {
												$o_log->logError(_t('Relationship type is missing for ca_objects relationship'));
												break;
											}

											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related object with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_object_lots':
										$vs_idno_stub = null;
										if (is_array($va_element_data['idno_stub'])) {
											$vs_idno_stub = isset($va_element_data['idno_stub']['idno_stub']) ? $va_element_data['idno_stub']['idno_stub'] : '';
										} else {
											$vs_idno_stub = isset($va_element_data['idno_stub']) ? $va_element_data['idno_stub'] : '';
										}
										if ($vn_rel_id = DataMigrationUtils::getObjectLotID($vs_idno_stub, $vs_name, $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !(is_array($va_element_data['idno_stub']) && ($vs_rel_type = $va_element_data['idno_stub']['_relationship_type']))) { break; }
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related object lot with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_entities':
										if ($vn_rel_id = DataMigrationUtils::getEntityID($va_element_data['preferred_labels'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
										
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related entity with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_places':
										if ($vn_rel_id = DataMigrationUtils::getPlaceID($vs_name, $va_element_data['_parent_id'], $va_element_data['_type'], $vn_locale_id, null, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));

											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related place with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
											
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_collections':
										if ($vn_rel_id = DataMigrationUtils::getCollectionID($vs_name, $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
										
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, $vs_rel_type, null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related collection with relationship %2:", $vs_idno, $vs_rel_type), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_occurrences':
										if ($vn_rel_id = DataMigrationUtils::getOccurrenceID($vs_name, $va_element_data['_parent_id'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, $vs_rel_type, null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related occurrence with relationship %2:", $vs_idno, $vs_rel_type), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_storage_locations':
										if ($vn_rel_id = DataMigrationUtils::getStorageLocationID($vs_name, $va_element_data['_parent_id'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related storage location with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_list_items':
										$va_data_for_rel_table['is_enabled'] = 1;
										$va_data_for_rel_table['preferred_labels'] = $va_element_data['preferred_labels'];
										
										if ($vn_rel_id = DataMigrationUtils::getListItemID($va_element_data['_list'], $va_element_data['idno'] ? $va_element_data['idno'] : null, $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related list item with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_object_representations':
										if ($vn_rel_id = DataMigrationUtils::getObjectRepresentationID($vs_name, $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels, 'matchMediaFilesWithoutExtension' => true))) {
											$t_subject->linkRepresentation($vn_rel_id, null, null, null, null, array('type_id' => trim($va_element_data['_relationship_type']), 'is_primary' => true));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related object representation with:", $vs_idno), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_loans':
										if ($vn_rel_id = DataMigrationUtils::getLoanID($vs_name, $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related loan with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
									case 'ca_movements':
										if ($vn_rel_id = DataMigrationUtils::getMovementID($vs_name, $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('forceUpdate' => true, 'dontCreate' => $vb_dont_create, 'ignoreParent' => $vb_ignore_parent, 'matchOn' => $va_match_on, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $vn_row, 'nonPreferredLabels' => $va_nonpreferred_labels))) {
											if (!($vs_rel_type = $va_element_data['_relationship_type']) && !($vs_rel_type = $va_element_data['idno']['_relationship_type'])) { break; }
											$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']), null, null, null, null, array('interstitialValues' => $va_element_data['_interstitial']));
										
											if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related movement with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
												ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
												if ($vs_item_error_policy == 'stop') {
													$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												
													if ($o_trans) { $o_trans->rollback(); }
													return false;
												}
											}
										}
										break;
								 }
							} catch (Exception $e) {
								// noop
								$o_log->logError(_t('Error while processing related content: %1', $e->getMessage()));
							}
							try {
								 if(is_array($va_element_data['_related_related']) && sizeof($va_element_data['_related_related'])) {
									foreach($va_element_data['_related_related'] as $vs_rel_rel_table => $va_rel_rels) {
										foreach($va_rel_rels as $vn_i => $va_rel_rel) {
											if (!($t_rel_instance = Datamodel::getInstance($vs_table_name))) { 
												$o_log->logWarn(_t("[%1] Could not instantiate related table %2", $vs_idno, $vs_table_name));
												continue; 
											}
											if ($o_trans) { $t_rel_instance->setTransaction($o_trans); }
											if ($vn_rel_id && $t_rel_instance->load($vn_rel_id)) {
												if ($t_rel_rel = $t_rel_instance->addRelationship($vs_rel_rel_table, $va_rel_rel['id'], $va_rel_rel['_relationship_type'])) {
													$o_log->logInfo(_t('[%1] Related %2 (%3) to related %4 with relationship %5', $vs_idno, Datamodel::getTableProperty($vs_rel_rel_table, 'NAME_SINGULAR'), $va_rel_rel['id'], $t_rel_instance->getProperty('NAME_SINGULAR'), trim($va_rel_rel['_relationship_type'])));
												} else {
													if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related %2 (%3) to related %4 with relationship %5:", $vs_idno, Datamodel::getTableProperty($vs_rel_rel_table, 'NAME_SINGULAR'), $va_rel_rel['id'], $t_rel_instance->getProperty('NAME_SINGULAR'), trim($va_rel_rel['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
														ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
													}
												}
											}
										}
									}
								 }
							} catch (Exception $e) {
								// noop
								$o_log->logError(_t('Error while processing related-to-related content: %1', $e->getMessage()));
							}
							 
						}
					}
				}
			
				// $t_subject->update(['queueIndexing' => true]);
	// 
	// 			if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Invalid %2; values were %3: ", $vs_idno, 'attributes', ca_data_importers::formatValuesForLog($va_element_content)), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
	// 				ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
	// 				if ($vs_item_error_policy == 'stop') {
	// 					$o_log->logAlert(_t('Import stopped due to mapping error policy'));
	// 					
	// 					
	// 					$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
	// 					
	// 					if ($o_trans) { $o_trans->rollback(); }
	// 					return false;
	// 				}
	// 			}
	// 										
				$o_log->logDebug(_t('Finished inserting content tree for %1 at %2 seconds into database', $vs_idno, $t->getTime(4)));
			
				if(!$vb_output_subject_preferred_label && ($t_subject->getPreferredLabelCount() == 0)) {
					try {
						$t_subject->addLabel(
							array($vs_label_display_fld => '???'), $vn_locale_id, null, true
						);
					} catch (Exception $e) {
						// noop
					}
				
					if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add default label", $vs_idno), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
						ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
						if ($vs_import_error_policy == 'stop') {
							$o_log->logAlert(_t('Import stopped due to import error policy'));
						
							$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
							if ($o_trans) { $o_trans->rollback(); }
							return false;
						}
					}
				}
			
				$opa_app_plugin_manager->hookDataPostImport(array('subject' => $t_subject, 'mapping' => $t_mapping, 'content_tree' => &$va_content_tree, 'idno' => &$vs_idno, 'transaction' => &$o_trans, 'log' => &$o_log, 'reader' => $o_reader, 'environment' => $va_environment,'importEvent' => $o_event, 'importEventSource' => $vn_row));
			
			
				$o_log->logInfo(_t('[%1] Imported %2 as %3 ', $vs_idno, $vs_preferred_label_for_log, $vs_subject_table_name));
				$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_SUCCESS__, _t('Imported %1', $vs_idno));
				
				if ($t_add_to_set) {
					if (!$t_add_to_set->addItem($t_subject->getPrimaryKey(), null, $pa_options['user_id'])) {
						$o_log->logError(_t('Could not add imported item %1 to set %2: %3', $vs_idno, $ps_add_to_set, join('; ', $t_set->getErrors())));
					}
				}
				
				ca_data_importers::$s_num_records_processed++;
			}
		}
	}
	
		$o_log->logInfo(_t('Import of %1 completed using mapping %2: %3 imported/%4 skipped/%5 errors', $ps_source, $t_mapping->get('importer_code'), ca_data_importers::$s_num_records_processed, ca_data_importers::$s_num_records_skipped, ca_data_importers::$s_num_import_errors));
		
		//if ($vb_show_cli_progress_bar) {
		$o_progress->setDataForJobID(null, _t('Import complete'), ['numRecordsProcessed' => ca_data_importers::$s_num_records_processed, 'table' => $t_subject->tableName(), 'created' => $va_ids_created, 'updated' => $va_ids_updated]);
		$o_progress->finish();
		//}
		if ($po_request && isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
			$ps_callback($po_request, $pn_file_number, $pn_number_of_files, $ps_source, $vn_num_items, $vn_num_items, _t('Import completed'), (time() - $vn_start_time), memory_get_usage(true), ca_data_importers::$s_num_records_processed, ca_data_importers::$s_num_import_errors);
		}
		
		if (isset($pa_options['reportCallback']) && ($ps_callback = $pa_options['reportCallback'])) {
			$va_general = array(
				'elapsedTime' => time() - $vn_start_time,
				'numErrors' => ca_data_importers::$s_num_import_errors,
				'numProcessed' => ca_data_importers::$s_num_records_processed
			);
			$ps_callback($po_request, $va_general, ca_data_importers::$s_import_error_list, true);
		}
		
		if ($pb_dry_run) {
			if ($o_trans) { $o_trans->rollback(); }
			$o_log->logInfo(_t('Rollback successful import run in "dry run" mode'));
		} else {
			if ($o_trans) { $o_trans->commit(); }
		}
		return true;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	private static function _processChildren($parent_table, $parent_id, $children, $options=null) {
		$log = caGetOption('log', $options, null);
		$trans = caGetOption('transaction', $options, null);
		$config = Configuration::load();
		foreach($children as $child) {
			$vals = $child; unset($vals['_table']); unset($vals['_type']); unset($vals['preferred_labels']); unset($vals['_children']);
			
			if (caIsIndexedArray($child['preferred_labels'])) { $child['preferred_labels'] = array_shift($child['preferred_labels']); }
			
			$id = null;
			$attrs = $vals; unset($attr['_related_related']);
			if ($parent_table == $child['_table']) {	// direct child to parent (both are same table)
				$id = DataMigrationUtils::getIDFor($child['_table'], $child['preferred_labels'], $parent_id, $child['_type'], 1, $attrs, $options);
			} elseif($config->get('ca_objects_x_collections_hierarchy_enabled') && ($parent_table == 'ca_collections') && ($child['_table'] == 'ca_objects')) {	// collection-object hierarchy
				if (($id = DataMigrationUtils::getIDFor($child['_table'], $child['preferred_labels'], null, $child['_type'], 1, $attrs, $options)) && ($parent = Datamodel::getInstance($parent_table, true)) && $parent->load($parent_id)) {
					if($trans) { $parent->setTransaction($trans); }
					$parent->addRelationship('ca_objects', $id, $config->get('ca_objects_x_collections_hierarchy_relationship_type'));
					
					if($parent->numErrors() > 0) {
						if ($log) { $log->logInfo(_t('Could not create object-collection hierarchy child record relationship: %1', join("; ", $parent->getErrors()))); }
						continue;
					}
				}
			}
			
			if (!$id) { 
				if ($log) { $log->logInfo(_t('Could not create hierarchy child record for %1', $child['_table'])); }
				continue; 
			}
			try {
				 if(is_array($vals['_related_related']) && sizeof($vals['_related_related'])) {
					foreach($vals['_related_related'] as $vs_rel_rel_table => $va_rel_rels) {
						foreach($va_rel_rels as $vn_i => $va_rel_rel) {
							if (!($t_rel_instance = Datamodel::getInstance($child['_table']))) { 
								if ($log) { $log->logWarn(_t("[%1] Could not instantiate related table %2", $vs_idno, $child['_table'])); }
								continue; 
							}
							if ($trans) { $t_rel_instance->setTransaction($trans); }
							if ($t_rel_instance->load($id)) {
								if ($t_rel_rel = $t_rel_instance->addRelationship($vs_rel_rel_table, $va_rel_rel['id'], $va_rel_rel['_relationship_type'])) {
									if ($log) { $log->logInfo(_t('[%1] Related %2 (%3) to related %4 with relationship %5', $vs_idno, Datamodel::getTableProperty($vs_rel_rel_table, 'NAME_SINGULAR'), $va_rel_rel['id'], $t_rel_instance->getProperty('NAME_SINGULAR'), trim($va_rel_rel['_relationship_type']))); }
								} else {
									if ($vs_error = DataMigrationUtils::postError($t_rel_instance, _t("[%1] Could not add related %2 (%3) to related %4 with relationship %5:", $vs_idno, Datamodel::getTableProperty($vs_rel_rel_table, 'NAME_SINGULAR'), $va_rel_rel['id'], $t_rel_instance->getProperty('NAME_SINGULAR'), trim($va_rel_rel['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
										ca_data_importers::logImportError($vs_error, $va_log_import_error_opts);
									}
								}
							}
						}
					}
				 }
			} catch (Exception $e) {
				// noop
			}
			
			if (is_array($child['_children']) && sizeof($child['_children'])) {
				self::_processChildren($child['_table'], $id, $child['_children'], $options);
			}
		}
	}
	# ------------------------------------------------------
	/**
	 * Return list of errors from last import
	 *
	 * @return array
	 */
	public static function getErrorList() {
		return ca_data_importers::$s_import_error_list;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getDataReader($ps_source, $ps_format=null) {
		//$o_reader_manager = new DataReaderManager();
		
		return DataReaderManager::getDataReaderForFormat($ps_format, array('noCache' => true));
		
		if (!$ps_format) {
			// TODO: try to figure out format from source
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function guessSourceFormat($ps_source) {
		// TODO: implement	
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getValueFromSource($pa_item, $po_reader, $pa_options=null) {
		$pb_return_as_array = caGetOption('returnAsArray', $pa_options, false);
		$va_filter_to_types = caGetOption('filterToTypes', $pa_options, false); 	// supported by CollectiveAccessDataReader
		$va_filter_to_relationship_types = caGetOption('filterToRelationshipTypes', $pa_options, false); 	// supported by CollectiveAccessDataReader
		$pa_environment = caGetOption('environment', $pa_options, array(), array('castTo' => 'array'));
		$pa_other_values = caGetOption('otherValues', $pa_options, array(), array('castTo' => 'array'));
		$ps_delimiter = caGetOption('delimiter', $pa_options, ';');
		$pn_lookahead = caGetOption('lookahead', $pa_options, 0, array('castTo' => 'int'));
		
		if (preg_match('!^_CONSTANT_:[^:]+:(.*)$!', $pa_item['source'], $va_matches)) {
			$vm_value = $va_matches[1];
		} elseif(isset($pa_environment[$pa_item['source']])) {
			$vm_value = $pa_environment[$pa_item['source']];
		} elseif(isset($pa_other_values[$pa_item['source']])) {
			$vm_value = $pa_other_values[$pa_item['source']];
		} else {
			$vn_cur_pos = $po_reader->currentRow();
			$vb_did_seek = false;
			if ($pn_lookahead > 0) {
				$vn_seek_to = ($po_reader->currentRow() + $pn_lookahead);
				$po_reader->seek($vn_seek_to);
				$vb_did_seek = true;
			}
			if ($po_reader->valuesCanRepeat()) {
				$vm_value = $po_reader->get($pa_item['source'], array('returnAsArray' => true, 'filterToTypes' => $va_filter_to_types, 'filterToRelationshipTypes' => $va_filter_to_relationship_types));
				if (!is_array($vm_value)) { return $pb_return_as_array ? array() : null; }
				foreach($vm_value as $vs_k => $vs_v) {
					if (is_array($vs_v)) { continue; }	// skip odd mappings that return arrays of arrays
					//$vs_v = stripslashes($vs_v);
					$vs_v = str_replace('\\\\', '\\', $vs_v);
					$vm_value[$vs_k] = ca_data_importers::replaceValue(trim($vs_v), $pa_item, $pa_options);
				}
				if ($pb_return_as_array) {
					return $vm_value;
				} else {
					return join($ps_delimiter, $vm_value);
				}
			} else {
				$vm_value = trim($po_reader->get($pa_item['source'], array('filterToTypes' => $va_filter_to_types, 'filterToRelationshipTypes' => $va_filter_to_relationship_types)));
			}
			
			if ($vb_did_seek) { $po_reader->seek($vn_cur_pos); }
		}
		
		$vm_value = ca_data_importers::replaceValue(stripslashes($vm_value), $pa_item, $pa_options);
		
		if ($pb_return_as_array) {
			return is_array($vm_value) ? $vm_value : array($vm_value);
		}
		return $vm_value;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function replaceValue($pm_value, $pa_item, $pa_options=null) {
		if (strlen($pm_value) && is_array($pa_item['settings']['original_values'])) {
			if (($vn_index = array_search(trim(mb_strtolower($pm_value)), $pa_item['settings']['original_values'])) !== false) {
				$vs_replaced_display_value = $vs_replaced_value = $pa_item['settings']['replacement_values'][$vn_index];
				if ($o_log = caGetOption('log', $pa_options, null)) { 
					$va_element_tmp = explode('.', $pa_item['destination']);
					
					$vs_original_display_value = $pm_value;
					
					if (($vn_list_id = ca_metadata_elements::getElementListID(array_pop($va_element_tmp)))) {
						$vs_original_display_value = caGetListItemForDisplay($vn_list_id, $pm_value);
						$vs_replaced_display_value = caGetListItemForDisplay($vn_list_id, $vs_replaced_value);
					}
					
					if ($pm_value !== $vs_replaced_value) {
						$o_log->logInfo(
							_t('Replaced value "%1" (%2) with "%3" (%4) for %5 using replacement values', $vs_original_display_value, $pm_value, $vs_replaced_display_value, $vs_replaced_value, caGetOption('logIdno', $pa_options, '???'))
						);
					} 
				}
				$pm_value = $vs_replaced_value;
			}
		}
		
		$pm_value = trim($pm_value);
		
		if (!$pm_value && isset($pa_item['settings']['default']) && strlen($pa_item['settings']['default'])) {
			$pm_value = $pa_item['settings']['default'];
		}
		
		return $pm_value;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function _extractIntrinsicValues($pa_values, $ps_table) {
		$t_instance = Datamodel::getInstance($ps_table, true);
		$va_form_fields = $t_instance->getFormFields();
		
		$va_extracted_values = array();
		foreach($va_form_fields as $vs_field => $va_field_info) {
			if (is_array($pa_values[$vs_field]) && isset($pa_values[$vs_field][$vs_field])) {
				$va_extracted_values[$vs_field] = $pa_values[$vs_field][$vs_field];
			}
		}
		return $va_extracted_values;
	}
	# ------------------------------------------------------
}
