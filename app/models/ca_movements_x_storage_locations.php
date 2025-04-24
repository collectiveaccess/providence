<?php
/** ---------------------------------------------------------------------
 * app/models/ca_movements_x_storage_locations.php : table access class for table ca_movements_x_storage_locations
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/HistoryTrackingCurrentValueTrait.php");
require_once(__CA_LIB_DIR__."/LocationRelationshipBaseModel.php");

BaseModel::$s_ca_models_definitions['ca_movements_x_storage_locations'] = array(
 	'NAME_SINGULAR' 	=> _t('movements ⇔ storage locations relationship'),
 	'NAME_PLURAL' 		=> _t('movements ⇔ storage locations relationships'),
 	'FIELDS' 			=> array(
 		'relation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Relation id', 'DESCRIPTION' => 'Identifier for Relation'
		),
		'movement_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Movement id', 'DESCRIPTION' => 'Identifier for movement'
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Type id', 'DESCRIPTION' => 'Identifier for Type',
				'BOUNDS_VALUE' => array(0,65535)
		),
		'location_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Location id', 'DESCRIPTION' => 'Identifier for location'
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Source information'
		),
		'effective_date' => array(
				'FIELD_TYPE' => FT_HISTORIC_DATERANGE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'START' => 'sdatetime', 'END' => 'edatetime',
				'LABEL' => _t('Effective date'), 'DESCRIPTION' => _t('Period of time for which this relationship was in effect. This is an option qualification for the relationship. If left blank, this relationship is implied to have existed for as long as the related items have existed.')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the relationship when displayed in a list with other relationships. Lower numbers indicate higher priority.')
		)
 	)
);

class ca_movements_x_storage_locations extends LocationRelationshipBaseModel {

	use HistoryTrackingCurrentValueTrait;
	
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
	protected $TABLE = 'ca_movements_x_storage_locations';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'relation_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('source_info');

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
	protected $ORDER_BY = array('source_info');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

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
			'movement_id', 'location_id'
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# --- Relationship info
	# ------------------------------------------------------
	protected $RELATIONSHIP_LEFT_TABLENAME = 'ca_movements';
	protected $RELATIONSHIP_RIGHT_TABLENAME = 'ca_storage_locations';
	protected $RELATIONSHIP_LEFT_FIELDNAME = 'movement_id';
	protected $RELATIONSHIP_RIGHT_FIELDNAME = 'location_id';
	protected $RELATIONSHIP_TYPE_FIELDNAME = 'type_id';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	/**
	 *
	 */
	public function insert($pa_options=null) {
		if (!$this->get('effective_date', array('getDirectDate' => true))) {  
			$this->set('effective_date', $this->_getMovementDate()); 
			$this->set('source_info', $this->_getStorageLocationInfo());
		}
		
		try {
			return parent::insert($pa_options);
		} catch (Exception $e) {
			// Dupes will throw exception
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function update($pa_options=null) {
		if (!$this->get('effective_date', array('getDirectDate' => true))) { 
			$this->set('effective_date',  $this->_getMovementDate()); 
			$this->set('source_info', $this->_getStorageLocationInfo());
		}
		
		try {
			return parent::update($pa_options);
		} catch (Exception $e) {
			// Dupes will throw exception
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _getMovementDate() {
	 	$date = null;
	 	if ($movement_storage_element = $this->getAppConfig()->get('movement_storage_location_date_element')) {
	 		$f = explode('.', $movement_storage_element);
	 		if ((sizeof($f) > 1) && ($f[0] === 'ca_movements')) { array_shift($f); }
	 		$movement_storage_element = join('.', $f);
			if ($t_movement = ca_movements::findAsInstance(['movement_id' => $this->get('movement_id')])) {
				$date = $t_movement->get("ca_movements.{$movement_storage_element}");
			}
		}
		return ($date) ? $date : _t('now');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _getStorageLocationInfo() {
		$t_loc = new ca_storage_locations($this->get('location_id'));
		if ($t_loc->getPrimaryKey()) {
			if(!($tmpl = Configuration::load()->get('original_storage_location_path_template'))) { $tmpl = '^ca_storage_locations.hierarchy.preferred_labels.name'; }
			return [
				'path' => $t_loc->get('ca_storage_locations.hierarchy.preferred_labels.name', array('returnAsArray' => true)),
				'display' => $t_loc->getWithTemplate($tmpl),
				'ids' => $t_loc->get('ca_storage_locations.hierarchy.location_id',  array('returnAsArray' => true))
			];
		} else {
			return ['path' => ['?'], 'display' => '?', 'ids' => [0]];
		}
	}	
	# ------------------------------------------------------
	/**
	 * Indicate custom bundles for movement-based location tracking
	 *
	 * @param string $bundle_name 
	 */
	public function isValidBundle($bundle_name) {
		switch($bundle_name) {
			case 'original_path':
				return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 * Handle "original_path" value for movement-based location tracking.
	 */
	public function renderBundleForDisplay($bundle_name, $row_id, $values, $options=null) {
		switch($bundle_name) {
			case 'original_path':
				$qr = ca_movements_x_storage_locations::findAsSearchResult(['relation_id' => $row_id]);
				if($qr->nextHit()) {
					$data = $qr->get('ca_movements_x_storage_locations.source_info', ['returnAsArray' => true]);
					$path = $data[0]['display'] ?? join(" - ", $data[0]['path'] ?? []);
					
					return $path;
				}
				break;
		}
		return null;
	}
	# ------------------------------------------------------
}
