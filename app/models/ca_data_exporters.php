<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_exporters.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/ModelSettings.php');
require_once(__CA_LIB_DIR__.'/ca/BundlableLabelableBaseModelWithAttributes.php');

require_once(__CA_LIB_DIR__.'/ca/Export/BaseExportFormat.php');
require_once(__CA_LIB_DIR__.'/ca/Export/BaseExportRefinery.php');
require_once(__CA_LIB_DIR__.'/ca/Export/ExportRefineryManager.php');

require_once(__CA_MODELS_DIR__."/ca_data_exporter_labels.php");
require_once(__CA_MODELS_DIR__."/ca_data_exporter_items.php");

require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel/IOFactory.php');
require_once(__CA_LIB_DIR__.'/core/Logging/KLogger/KLogger.php');

BaseModel::$s_ca_models_definitions['ca_data_exporters'] = array(
 	'NAME_SINGULAR' 	=> _t('data exporter'),
 	'NAME_PLURAL' 		=> _t('data exporters'),
	'FIELDS' 			=> array(
		'exporter_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this exporter')
		),
		'exporter_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('exporter code'), 'DESCRIPTION' => _t('Unique alphanumeric identifier for this exporter.'),
				'UNIQUE_WITHIN' => array()
				//'REQUIRES' => array('is_administrator')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
				'DONT_USE_AS_BUNDLE' => true,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('exporter type'), 'DESCRIPTION' => _t('Indicates type of item exporter is used for.'),
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
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('exporter settings')
		),
		'vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Variable storage', 'DESCRIPTION' => 'Storage area for exporter variables'
		),
	)
);
	
class ca_data_exporters extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_data_exporters';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'exporter_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('exporter_id');

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
	protected $ORDER_BY = array('exporter_id');

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
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);	
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_data_exporter_labels';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'exporter_code';	// name of field containing user-defined identifier
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

	/**
	 * Caches
	 */
	public static $s_exporter_cache = array();
	public static $s_exporter_item_cache = array();
	
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		// Filter list of tables exporters can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_data_exporters']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caFilterTableList(BaseModel::$s_ca_models_definitions['ca_data_exporters']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);
		
		global $_ca_data_exporters_settings;
		parent::__construct($pn_id);
		
		//
		$this->initSettings();
		
	}
	# ------------------------------------------------------
	protected function initSettings(){
		$va_settings = array();

		if (!($vn_table_num = $this->get('table_num'))) { 
			$this->SETTINGS = new ModelSettings($this, 'settings', array());	
		}

		$va_settings['exporter_format'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => $this->getAvailableExporterFormats(),
			'label' => _t('Exporter format'),
			'description' => _t('Set exporter type, i.e. the format of the exported data.  Currently supported: XML and MARC')
		);

		$va_settings['wrap_before'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 6,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Wrapping text before export'),
			'description' => _t('If this exporter is used for an item set export (as opposed to a single item), the text set here will be inserted before the first item. This can for instance be used to wrap a repeating set of XML elements in a single global element. The text has to be valid for the current exporter format.')
		);

		$va_settings['wrap_after'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 70, 'height' => 6,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Wrapping text after export'),
			'description' => _t('If this exporter is used for an item set export (as opposed to a single item), the text set here will be inserted after the last item. This can for instance be used to wrap a repeating set of XML elements in a single global element. The text has to be valid for the current exporter format.')
		);

		$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);
	}
	# ------------------------------------------------------
	public function getAvailableExporterFormats() {
		return array(
			'XML' => 'XML',
			'MARC' => 'MARC',
		);
	}
	# ------------------------------------------------------
	/**
	 * Override BaseModel::set() to prevent setting of table_num field for existing records
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if ($this->getPrimaryKey()) {
			if(!is_array($pa_fields))  { $pa_fields = array($pa_fields => $pm_value); }
			$va_fields_proc = array();
			foreach($pa_fields as $vs_field => $vs_value) {
				if (!in_array($vs_field, array('table_num'))) {
					$va_fields_proc[$vs_field] = $vs_value;
				}
			}
			if (!sizeof($va_fields_proc)) { $va_fields_proc = null; }
			$vn_rc = parent::set($va_fields_proc, null, $pa_options);	
			
			$this->initSettings();
			return $vn_rc;
		}
		
		$vn_rc = parent::set($pa_fields, $pm_value, $pa_options);
		
		$this->initSettings();
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override BaseModel::delete() because the cascading delete implemented there
	 * doesn't properly remove related items if they're organized in a ad-hoc hierarchy.
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null){
		if($pb_delete_related){
			$this->removeAllItems();
		}

		return parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
	}
	# ------------------------------------------------------
	/**
	 * Get all exporter items items for this exporter
	 * @param array $pa_options available options are:
	 *                          onlyTopLevel
	 *                          includeSettingsForm
	 *                          id_prefix
	 *                          orderForDeleteCascade
	 * @return array array of items, keyed by their primary key
	 */
	public function getItems($pa_options=array()){
		if (!($vn_exporter_id = $this->getPrimaryKey())) { return null; }

		$vb_include_settings_form = isset($pa_options['includeSettingsForm']) ? (bool)$pa_options['includeSettingsForm'] : false;
		$vb_only_top_level = isset($pa_options['onlyTopLevel']) ? (bool)$pa_options['onlyTopLevel'] : false;
		$vb_order_for_delete_cascade = isset($pa_options['orderForDeleteCascade']) ? (bool)$pa_options['orderForDeleteCascade'] : false;

		$vs_id_prefix = isset($pa_options['id_prefix']) ? $pa_options['id_prefix'] : '';

		$vo_db = $this->getDb();

		$va_conditions = array();
		if($vb_only_top_level){
			$va_conditions[] ="AND parent_id IS NULL";
		}

		if($vb_order_for_delete_cascade){
			$vs_order = "parent_id DESC";
		} else {
			$vs_order = "rank ASC";
		}

		$qr_items = $vo_db->query("
			SELECT * FROM ca_data_exporter_items
			WHERE exporter_id = ?
			" . join(" ",$va_conditions) . " 
			ORDER BY ".$vs_order."
		",$vn_exporter_id);

		$va_items = array();
		while($qr_items->nextRow()) {
			$va_items[$vn_item_id = $qr_items->get('item_id')] = $qr_items->getRow();
			
			if ($vb_include_settings_form) {
				$t_item = new ca_data_exporter_items($vn_item_id);
				$va_items[$vn_item_id]['settings'] = $t_item->getHTMLSettingForm(array('settings' => $t_item->getSettings(), 'id' => "{$vs_id_prefix}_setting_{$vn_item_id}"));
			}
		}

		return $va_items;
	}
	# ------------------------------------------------------
	public function getTopLevelItems($pa_options=array()){
		return $this->getItems(array_merge(array('onlyTopLevel' => true),$pa_options));
	}
	# ------------------------------------------------------
	/**
	 * Add new exporter item to this exporter.
	 * @param int $pn_parent_id parent id for the new record. can be null
	 * @param string $ps_source value for 'source' field. this will typicall be a bundle name
	 * @param string $ps_element name of the target element
	 * @param array $pa_settings array of user settings
	 * @param array $pa_vars array of variables to store
	 * @return ca_data_exporter_items BaseModel representation of the new record
	 */
	public function addItem($pn_parent_id=null,$ps_source,$ps_element,$pa_settings,$pa_vars=null){
		if (!($vn_exporter_id = $this->getPrimaryKey())) { return null; }

		$t_item = new ca_data_exporter_items();
		$t_item->setMode(ACCESS_WRITE);
		$t_item->set('parent_id',$pn_parent_id);
		$t_item->set('exporter_id',$vn_exporter_id);
		$t_item->set('source',$ps_source);
		$t_item->set('element',$ps_element);
		$t_item->set('vars',$pa_vars);

		foreach($pa_settings as $vs_key => $vs_value){
			$t_item->setSetting($vs_key,$vs_value);
		}

		$t_item->insert();

		if ($t_item->numErrors()) {
			$this->errors = array_merge($this->errors, $t_item->errors);
			return false;
		}
		return $t_item;
	}
	# ------------------------------------------------------
	/**
	 * Remove item from this exporter and delete
	 * @param int $pn_item_id primary key of the item to remove
	 * @return boolean success state
	 */
	public function removeItem($pn_item_id) {
		if (!($vn_exporter_id = $this->getPrimaryKey())) { return null; }
		
		$t_item = new ca_data_exporter_items($pn_item_id);
		if ($t_item->getPrimaryKey() && ($t_item->get('exporter_id') == $vn_exporter_id)) {
			$t_item->setMode(ACCESS_WRITE);
			$t_item->delete(true);
			
			if ($t_item->numErrors()) {
				$this->errors = array_merge($this->errors, $t_item->errors);
				return false;
			}
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Remove all items from this exporter
	 * @return boolean success state
	 */
	public function removeAllItems() {
		if (!($vn_exporter_id = $this->getPrimaryKey())) { return null; }

		$va_items = $this->getItems(array('orderForDeleteCascade' => true));
		$t_item = new ca_data_exporter_items();
		$t_item->setMode(ACCESS_WRITE);

		foreach($va_items as $vn_item_id => $va_item){
			$t_item->load($vn_item_id);
			$t_item->delete(true);

			if ($t_item->numErrors()) {
				$this->errors = array_merge($this->errors, $t_item->errors);
				return false;
			}
		}
	}
	# ------------------------------------------------------
	# Settings
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
	 * Load exporter configuration from XLSX file
	 * @param string $ps_source file path for source XLSX
	 * @param array $pa_options options
	 * @return ca_data_exporters BaseModel representation of the new exporter. false/null if there was an error.
	 */
	public static function loadExporterFromFile($ps_source, $pa_options=null) {
		global $g_ui_locale_id;
		$vn_locale_id = (isset($pa_options['locale_id']) && (int)$pa_options['locale_id']) ? (int)$pa_options['locale_id'] : $g_ui_locale_id;

		$o_excel = PHPExcel_IOFactory::load($ps_source);
		//$o_excel->setActiveSheet(1);
		$o_sheet = $o_excel->getActiveSheet();

		$vn_row = 0;
		
		$va_settings = array();
		$va_mappings = array();
		$va_ids = array();

		foreach ($o_sheet->getRowIterator() as $o_row) {
			if ($vn_row == 0) {	// skip first row (headers)
				$vn_row++;
				continue;
			}

			$vn_row_num = $o_row->getRowIndex();
			$o_cell = $o_sheet->getCellByColumnAndRow(0, $vn_row_num);
			$vs_mode = (string)$o_cell->getValue();

			switch($vs_mode) {
				case 'Mapping':
					$o_id = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_parent = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					$o_element = $o_sheet->getCellByColumnAndRow(3, $o_row->getRowIndex());
					$o_source = $o_sheet->getCellByColumnAndRow(4, $o_row->getRowIndex());
					$o_options = $o_sheet->getCellByColumnAndRow(5, $o_row->getRowIndex());
					$o_refinery = $o_sheet->getCellByColumnAndRow(6, $o_row->getRowIndex());
					$o_refinery_settings = $o_sheet->getCellByColumnAndRow(7, $o_row->getRowIndex());

					if($vs_id = trim((string)$o_id->getValue())){
						$va_ids[] = $vs_id;
					}

					if($vs_parent_id = trim((string)$o_parent->getValue())){
						if(!in_array($vs_parent_id, $va_ids) && ($vs_parent_id != $vs_id)){
							print "Warning: skipped mapping at row {$vn_row_id} because parent id was invalid\n";
							continue(2);
						}
					}

					if (!($vs_element = trim((string)$o_element->getValue()))) { 
						print "Warning: skipped mapping at row {$vn_row_num} because element was not defined\n";
						continue(2);
					}

					$vs_source = trim((string)$o_source->getValue());

					$va_options = null;
					if ($vs_options_json = (string)$o_options->getValue()) { 
						if (is_null($va_options = @json_decode($vs_options_json, true))) {
							print "Warning: invalid options for group {$vs_group}/source {$vs_source}\n";
						}
					}

					$vs_refinery = trim((string)$o_refinery->getValue());
					
					$va_refinery_options = null;
					if ($vs_refinery && ($vs_refinery_options_json = (string)$o_refinery_options->getValue())) {
						// TODO: check refineries
					}

					$vs_key = (strlen($vs_id)>0 ? $vs_id : md5($vn_row_num));

					$va_mapping[$vs_key] = array(
						'parent_id' => $vs_parent_id,
						'element' => $vs_element,
						'source' => $vs_source,
						'options' => $va_options,
						'refinery' => $vs_refinery,
						'refinery_options' => $va_refinery_options,
					);

					break;
				case 'Setting':
					$o_setting_name = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_setting_value = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					$va_settings[(string)$o_setting_name->getValue()] = (string)$o_setting_value->getValue();
					break;
				default: // if 1st column is empty, skip
					continue(2);
					break;
			}

			$vn_row++;
		}

		//print_r($va_mapping);
		//print_r($va_settings);

		// Do checks on mapping
		if (!$va_settings['code']) { 
			print "You must set a code for your mapping!\n";
			return;
		}

		$o_dm = Datamodel::load();
		if (!($t_instance = $o_dm->getInstanceByTableName($va_settings['table']))) {
			print _t("Mapping target table %1 is invalid\n", $va_settings['table']);
			return;
		}

		if (!$va_settings['name']) { $va_settings['name'] = $va_settings['code']; }

		$t_exporter = new ca_data_exporters();
		$t_exporter->setMode(ACCESS_WRITE);

		// Remove any existing mapping with this code
		if ($t_exporter->load(array('exporter_code' => $va_settings['code']))) {
			$t_exporter->delete(true, array('hard' => true));
			if ($t_exporter->numErrors()) {
				print _t("Could not delete existing mapping for %1: %2", $va_settings['code'], join("; ", $t_exporter->getErrors()))."\n";
				return;
			}
		}

		// Create new mapping
		$t_exporter->set('exporter_code', $va_settings['code']);
		$t_exporter->set('table_num', $t_instance->tableNum());

		$vs_name = $va_settings['name'];

		unset($va_settings['code']);
		unset($va_settings['table']);
		unset($va_settings['name']);

		foreach($va_settings as $vs_k => $vs_v) {
			$t_exporter->setSetting($vs_k, $vs_v);
		}
		$t_exporter->insert();

		$t_exporter->addLabel(array('name' => $vs_name), $vn_locale_id, null, true);

		if ($t_exporter->numErrors()) {
			print _t("Error creating exporter: %1", join("; ", $t_exporter->getErrors()))."\n";
			return;
		}

		if ($t_exporter->numErrors()) {
			print _t("Error creating exporter name: %1", join("; ", $t_exporter->getErrors()))."\n";
			return;
		}

		$va_id_map = array();
		foreach($va_mapping as $vs_mapping_id => $va_info){
			$va_item_settings = array();

			if (is_array($va_info['options'])) {
				foreach($va_info['options'] as $vs_k => $vs_v) {
					$va_item_settings[$vs_k] = $vs_v;
				}
			}
			if($va_info['refinery']){
				$va_item_settings['refineries'] = array($va_info['refinery']);
			}
			if (is_array($va_info['refinery_options'])) {
				foreach($va_info['refinery_options'] as $vs_k => $vs_v) {
					$va_item_settings[$va_info['refinery'].'_'.$vs_k] = $vs_v;
				}
			}	
			
			$vn_parent_id = null;
			if($va_info['parent_id']){ $vn_parent_id = $va_id_map[$va_info['parent_id']]; }

			//caDebug($va_item_settings,"Settings for new exporter item");

			$t_item = $t_exporter->addItem($vn_parent_id,$va_info['source'],$va_info['element'],$va_item_settings);

			if ($t_exporter->numErrors()) {
				print _t("Error adding item to exporter: %1", join("; ", $t_exporter->getErrors()))."\n";
				return;
			}

			$va_id_map[$vs_mapping_id] = $t_item->getPrimaryKey();
		}

		$va_errors = ca_data_exporters::checkMapping($t_exporter->get('exporter_code'));

		if(is_array($va_errors) && sizeof($va_errors)>0){
			foreach($va_errors as $vs_error){ print $vs_error."\n"; }
			return;
		}

		return $t_exporter;
	}
	# ------------------------------------------------------
	/**
	 * Export a record set as defined by the given search expression and the table_num for this exporter.
	 * This function wraps the record-level exports using the settings 'wrap_before' and 'wrap_after' if they are set.
	 * @param string $ps_exporter_code defines the exporter to use
	 * @param string $ps_expression A valid search expression
	 * @param string $ps_filename Destination filename (we can't keep everything in memory here)
	 * @param array $pa_options
	 *		showCLIProgressBar = Show command-line progress bar. Default is false.
	 */
	static public function exportRecordsFromSearchExpression($ps_exporter_code, $ps_expression, $ps_filename, $pa_options=array()){
		ca_data_exporters::$s_exporter_cache = array();
		ca_data_exporters::$s_exporter_item_cache = array();

		$vb_show_cli_progress_bar 	= (isset($pa_options['showCLIProgressBar']) && ($pa_options['showCLIProgressBar']));

		$t_mapping = new ca_data_exporters();
		if(!$t_mapping->load(array('exporter_code' => $ps_exporter_code))){
			return array(_t("Invalid mapping code"));
		}

		$vs_wrap_before = $t_mapping->getSetting('wrap_before');
		$vs_wrap_after = $t_mapping->getSetting('wrap_after');

		$t_instance = $t_mapping->getAppDatamodel()->getInstanceByTableNum($t_mapping->get('table_num'));
		$o_search = caGetSearchInstance($t_mapping->get('table_num'));
		$o_result = $o_search->search($ps_expression);

		if($vs_wrap_before){
			file_put_contents($ps_filename, $vs_wrap_before."\n", FILE_APPEND);
		}

		if ($vb_show_cli_progress_bar){
			print CLIProgressBar::start($o_result->numHits(), _t('Processing search result'));
		}

		while($o_result->nextHit()){
			$vs_item_export = ca_data_exporters::exportRecord($ps_exporter_code,$o_result->get($t_instance->primaryKey()));
			file_put_contents($ps_filename, $vs_item_export."\n", FILE_APPEND);

			if ($vb_show_cli_progress_bar) {
				print CLIProgressBar::next(1, _t("Exporting records ..."));
			}
		}

		if($vs_wrap_after){
			file_put_contents($ps_filename, $vs_wrap_after."\n", FILE_APPEND);
		}

		if ($vb_show_cli_progress_bar) {
			print CLIProgressBar::finish();
		}

		return true;
	}
	# ------------------------------------------------------
	/**
	 * Check export mapping for format-specific errors
	 * @param string $ps_exporter_code code identifying the exporter
	 * @return array Array of errors. Array has size 0 if no errors occurred.
	 */
	static public function checkMapping($ps_exporter_code){
		$t_mapping = new ca_data_exporters();
		if(!$t_mapping->load(array('exporter_code' => $ps_exporter_code))){
			return array(_t("Invalid mapping code"));
		}

		switch($t_mapping->getSetting('exporter_format')){
			case 'XML':
				$o_export = new ExportXML();
				break;
			case 'MARC':
				$o_export = new ExportMARC();
				break;
			default:
				return array(_t("Invalid exporter format"));
		}

		return $o_export->getMappingErrors($t_mapping);
	}
	# ------------------------------------------------------
	/**
	 * Export a single record using the mapping defined by this exporter and return as string
	 * @param string $ps_exporter_code defines the exporter to use
	 * @param int $pn_record_id Primary key of the record to export. Record type is determined by the table_num field for this exporter.
	 * @param array $pa_options
	 *        singleRecord = Gives a signal to the export format implementation that this is a single record export. For certain formats
	 *        	this might trigger different behavior, for instance the XML export format prepends the item-level output with <?xml ... ?>
	 *        	in those cases.
	 * @return string Exported record as string
	 */
	static public function exportRecord($ps_exporter_code, $pn_record_id, $pa_options=array()){
		$pb_single_record = (isset($pa_options['singleRecord']) && $pa_options['singleRecord']);

		$t_exporter = ca_data_exporters::loadExporterByCode($ps_exporter_code);
		if(!$t_exporter) { return false; }

		$va_export = array();

		foreach($t_exporter->getTopLevelItems() as $va_item){
			$va_export = array_merge($va_export,$t_exporter->processExporterItem($va_item['item_id'],$t_exporter->get('table_num'),$pn_record_id,$pa_options));
		}

		switch($t_exporter->getSetting('exporter_format')){
			case 'XML':
				$o_export = new ExportXML();
				break;
			case 'MARC':
				$o_export = new ExportMARC();
				break;
			default:
				return;
		}

		return $o_export->processExport($va_export,$pa_options);
	}
	# ------------------------------------------------------
	/**
	 * @param array $pa_options
	 *		ignoreSource = don't switch context if source value is set
	 */
	public function processExporterItem($pn_item_id,$pn_table_num,$pn_record_id,$pa_options=array()){
		$vb_ignore_source = (isset($pa_options['ignoreSource']) && $pa_options['ignoreSource']);

		$t_exporter_item = ca_data_exporters::loadExporterItemByID($pn_item_id);
		$va_item_info = array();

		// switch context to different table if necessary and repeat current exporter item for all selected related records
		if(!$vb_ignore_source && ($vs_source = $t_exporter_item->get('source'))){
			$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($pn_table_num);
			if(!$t_instance->load($pn_record_id)){
				return;
			}

			$va_parsed_source = ca_data_exporters::_parseItemSource($vs_source);
			if(!$va_parsed_source){ return; }
			$va_related = $t_instance->getRelatedItems(
				$va_parsed_source['table_num'],
				array(
					'restrictToTypes' => $va_parsed_source['restrictToTypes'],
					'restrictToRelationshipTypes' => $va_parsed_source['restrictToRelationshipTypes'],
					'checkAccess' => $va_parsed_source['checkAccess'],
				)
			);

			$vs_key = $this->getAppDatamodel()->getTablePrimaryKeyName($va_parsed_source['table_num']);
			$va_info = array();
			foreach($va_related as $va_rel){
				$va_rel_export = $this->processExporterItem($pn_item_id,$va_parsed_source['table_num'],$va_rel[$vs_key],array_merge(array('ignoreSource' => true),$pa_options));
				$va_info = array_merge($va_info,$va_rel_export);
			}

			return $va_info;
		}
		// end switch context
		
		if($vs_template = $t_exporter_item->getSetting('template')){
			$va_item_info['text'] = caProcessTemplateForIDs($vs_template,$pn_table_num,array($pn_record_id));
		}
	
		$va_item_info['element'] = $t_exporter_item->get('element');

		foreach($t_exporter_item->getHierarchyChildren() as $va_child){
			$va_child_export = $this->processExporterItem($va_child['item_id'],$pn_table_num,$pn_record_id,$pa_options);
			$va_item_info['children'] = array_merge((array)$va_item_info['children'],$va_child_export);
		}

		// Add additional array level because this function is also used for self-repeating items where we 
		// return lists of items. To keep the return format consistent we make this a list of 1 item as well.
		return array($va_item_info);
	}
	# ------------------------------------------------------
	static public function _parseItemSource($vs_source){
		// example: ca_objects%restrictToTypes=image|print%restrictToRelationshipTypes=depicts|foobar%checkAccess=1
		$va_return = array();
		$o_dm = Datamodel::load();

		$va_tmp = explode('%',$vs_source);
		$vs_table = array_shift($va_tmp);

		if($vn_table_num = $o_dm->getTableNum($vs_table)){
			$va_return['table_num'] = $vn_table_num;
		} else {
			return false;
		}

		foreach($va_tmp as $vs_tmp){
			$va_keyval = explode('=',$vs_tmp);
			switch($va_keyval[0]){
				case 'restrictToTypes':
				case 'restrictToRelationshipTypes':
				case 'checkAccess':
					$va_return[$va_keyval[0]] = explode('|',$va_keyval[1]);
					break;
				default:
					return false;
			}
		}

		return $va_return;
	}
	# ------------------------------------------------------
	static public function loadExporterByCode($ps_exporter_code) {
		if(isset(ca_data_exporters::$s_exporter_cache[$ps_exporter_code])){
			return ca_data_exporters::$s_exporter_cache[$ps_exporter_code];
		} else {
			$t_exporter = new ca_data_exporters();
			if($t_exporter->load(array('exporter_code' => $ps_exporter_code))) {
				return ca_data_exporters::$s_exporter_cache[$ps_exporter_code] = $t_exporter;
			}
		}
		return false;
	}
	# ------------------------------------------------------
	static public function loadExporterItemByID($pn_item_id) {
		if(isset(ca_data_exporters::$s_exporter_item_cache[$pn_item_id])){
			return ca_data_exporters::$s_exporter_item_cache[$pn_item_id];
		} else {
			$t_item = new ca_data_exporter_items();
			if($t_item->load($pn_item_id)) {
				return ca_data_exporters::$s_exporter_item_cache[$pn_item_id] = $t_item;
			}
		}
		return false;
	}
	# ------------------------------------------------------
}
?>
