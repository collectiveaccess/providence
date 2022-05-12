<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_importer_groups.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2022 Whirl-i-Gig
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

BaseModel::$s_ca_models_definitions['ca_data_importer_groups'] = array(
 	'NAME_SINGULAR' 	=> _t('data importer group'),
 	'NAME_PLURAL' 		=> _t('data importer groups'),
	'FIELDS' 			=> array(
		'group_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this importer group')
		),
		'importer_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => 'Importer id', 'DESCRIPTION' => 'Identifier for importer'
		),
		'group_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Group code'), 'DESCRIPTION' => _t('Unique alphanumeric identifier for this importer group.'),
				'UNIQUE_WITHIN' => array()
				//'REQUIRES' => array('is_administrator')
		),
		'destination' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 70, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false,
				'DEFAULT' => '',
				'LABEL' => _t('External element'), 'DESCRIPTION' => _t('Name of CollectiveAccess bundle to map to.'),
				'BOUNDS_LENGTH' => array(0,1024)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Importer group settings')
		),
		'rank' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		)
	)
);
	
class ca_data_importer_groups extends BaseModel {
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
	protected $TABLE = 'ca_data_importer_groups';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'group_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('group_id');

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
	protected $ORDER_BY = array('group_id');

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
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);	
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'group_code';	// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;			// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct($id=null, ?array $options=null) {
		// Filter list of tables importers can be used for to those enabled in current config
		//BaseModel::$s_ca_models_definitions['ca_data_importer_groups']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caFilterTableList(BaseModel::$s_ca_models_definitions['ca_data_importer_groups']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);
		
		parent::__construct($id, $options);
		
		$this->setAvailableSettings([]);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addItem(string $source, string $destination, ?array $settings=null, ?array $options=null) {
		if(!$this->getPrimaryKey()) return false;
		
		$t_item = new ca_data_importer_items();
		$t_item->set('group_id', $this->getPrimaryKey());
		$t_item->set('importer_id', $this->get('importer_id'));
		$t_item->set('source', $source);
		$t_item->set('destination', $destination);
		
		if (is_array($settings)) {
			foreach($settings as $k => $v) {
				$t_item->setSetting($k, $v);
			}
		}
		
		$t_item->insert();
		
		if ($t_item->numErrors()) {
			$this->errors = $t_item->errors;
			return false;
		}
		
		if (isset($options['returnInstance']) && $options['returnInstance']) {
			return $t_item;
		}
		return $t_item->getPrimaryKey();
		
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function editItem(int $item_id, string $source, string $destination, ?array $settings=null, ?array $options=null) {
		if(!($t_item = ca_data_importer_items::find(['item_id' => $item_id], ['returnAs' => 'firstModelInstance']))) {
			return null;
		}
		$t_item->set('source', $source);
		$t_item->set('destination', $destination);
		
		if (is_array($settings)) {
			foreach($settings as $k => $v) {
				$t_item->setSetting($k, $v);
			}
		}
		
		$t_item->update();
		
		if ($t_item->numErrors()) {
			$this->errors = $t_item->errors;
			return false;
		}
		
		if (isset($options['returnInstance']) && $options['returnInstance']) {
			return $t_item;
		}
		return $t_item->getPrimaryKey();
		
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItems() {
		if(!$this->getPrimaryKey()) return false;
		
		$db = $this->getDb();
		
		$qr_items = $db->query("
			SELECT * 
			FROM ca_data_importer_items 
			WHERE group_id = ?
		", (int)$this->getPrimaryKey());
		
		$return = array();
		while($qr_items->nextRow()){
			$return[(int)$qr_items->get("item_id")] = $qr_items->getRow();
		}
		
		return $return;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemIDs() {
		if(is_array($items = $this->getItems())){
			return $items;
		} else {
			return array();
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function removeItem($pn_item_id){
		$t_item = new ca_data_importer_items();
		
		if(!in_array($pn_item_id, $this->getItemIDs())){
			return false; // don't delete unrelated items
		}
		
		if($t_item->load($pn_item_id)){
			$t_item->delete();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function removeAllItems(){
		foreach($this->getItemIDs() as $vn_item_id){
			$this->removeItem($vn_item_id);
		}
	}
	# ------------------------------------------------------
}
