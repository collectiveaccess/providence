<?php
/** ---------------------------------------------------------------------
 * app/models/ca_objects_x_object_representations.php : table access class for table ca_objects_x_object_representations
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/core/BaseRelationshipModel.php');
require_once(__CA_MODELS_DIR__."/ca_objects.php");
require_once(__CA_LIB_DIR__."/core/Db/Transaction.php");


BaseModel::$s_ca_models_definitions['ca_objects_x_object_representations'] = array(
 	'NAME_SINGULAR' 	=> _t('object ⇔ object representation relationship'),
 	'NAME_PLURAL' 		=> _t('object ⇔ object representation relationships'),
 	'FIELDS' 			=> array(
 		'relation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Link id', 'DESCRIPTION' => 'Identifier for Link'
		),
		'object_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Object id', 'DESCRIPTION' => 'Identifier for Object'
		),
		'representation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Representation id', 'DESCRIPTION' => 'Identifier for representation'
		),
		'is_primary' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is primary?'), 'DESCRIPTION' => _t('Indicates that the representation should be used to depict the object is situations where only a single representation can be displayed (eg. search results).')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the representation when displayed in a list with other representations. Lower numbers indicate higher priority.')
		)
 	)
);

class ca_objects_x_object_representations extends BaseRelationshipModel {
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
	protected $TABLE = 'ca_objects_x_object_representations';
	      
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
	protected $LIST_FIELDS = array();

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
	protected $ORDER_BY = array();

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
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			'object_id', 'representation_id'
		),
		"RELATED_TABLES" => array(
			
		)
	);
	
	# ------------------------------------------------------
	# --- Relationship info
	# ------------------------------------------------------
	protected $RELATIONSHIP_LEFT_TABLENAME = 'ca_objects';
	protected $RELATIONSHIP_RIGHT_TABLENAME = 'ca_object_representations';
	protected $RELATIONSHIP_LEFT_FIELDNAME = 'object_id';
	protected $RELATIONSHIP_RIGHT_FIELDNAME = 'representation_id';
	protected $RELATIONSHIP_TYPE_FIELDNAME = 'type_id';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	# --- Constructor
	#
	# This is a function called when a new instance of this object is created. This
	# standard constructor supports three calling modes:
	#
	# 1. If called without parameters, simply creates a new, empty objects object
	# 2. If called with a single, valid primary key value, creates a new objects object and loads
	#    the record identified by the primary key value
	#
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	public function insert() {
		$t_object = new ca_objects();
		$o_trans = new Transaction();
		$o_db = $o_trans->getDb();
		$this->setTransaction($o_trans);
		
		$vn_object_id = $this->get('object_id');
		if (!$t_object->load($vn_object_id)) { 
			// invalid object
			$this->postError(720, _t("Related object does not exist"), "ca_objects_x_object_representations->insert()");
			return false;
		}
		if (!$this->get('is_primary')) {
			// force is_primary to be set if no other represention is so marked 
		
			// is there another rep for this object marked is_primary?
			$qr_res = $o_db->query("
				SELECT relation_id
				FROM ca_objects_x_object_representations
				WHERE
					object_id = ? AND is_primary = 1
			", (int)$vn_object_id);
			if(!$qr_res->nextRow()) {
				// nope - force this one to be primary
				$this->set('is_primary', 1);
			}
			
			$vb_rc = parent::insert();
			$o_trans->commitTransaction();
			return $vb_rc;
		} else {
			// unset other reps is_primary field
			//$o_db->beginTransaction();
			
			$o_db->query("
				UPDATE ca_objects_x_object_representations
				SET is_primary = 0
				WHERE
					object_id = ?
			", (int)$vn_object_id);
			
			if (!$vb_rc = parent::insert()) {
				$o_trans->rollbackTransaction();
			} else {
				$o_trans->commitTransaction();
			}
			
			return $vb_rc;
		}
	}
	# ------------------------------------------------------
	public function update() {
		$t_object = new ca_objects();
		$o_trans = new Transaction();
		$o_db = $o_trans->getDb();
		$this->setTransaction($o_trans);
		
		$vn_object_id = $this->get('object_id');
		if (!$t_object->load($vn_object_id)) { 
			// invalid object
			$this->postError(720, _t("Related object does not exist"), "ca_objects_x_object_representations->update()");
			return false;
		}
		
		if ($this->changed('is_primary')) {
			if (!$this->get('is_primary')) {
				
				// force is_primary to be set if no other represention is so marked 
			
				// is there another rep for this object marked is_primary?
				$qr_res = $o_db->query("
					SELECT relation_id
					FROM ca_objects_x_object_representations
					WHERE
						object_id = ? AND is_primary = 1 AND representation_id <> ?
				", (int)$vn_object_id, (int)$this->get('representation_id'));
				if(!$qr_res->nextRow()) {
					// nope - force this one to be primary
					$this->set('is_primary', 1);
				}
				
				return parent::update();
			} else {
				// unset other reps is_primary field
				$o_db->query("
					UPDATE ca_objects_x_object_representations
					SET is_primary = 0
					WHERE
						object_id = ?
				", (int)$vn_object_id);
				if (!($vb_rc = parent::update())) {
					$o_trans->rollbackTransaction();
				} else {
					$o_trans->commitTransaction();
				}
				return $vb_rc;
			}
		} else {
			$vb_rc = parent::update();
			$o_trans->commitTransaction();
			return $vb_rc;
		}
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related=false) {
		$t_object = new ca_objects();
		
		$vn_object_id = $this->get('object_id');
		if (!$t_object->load($vn_object_id)) { 
			// invalid object
			$this->postError(720, _t("Related object does not exist"), "ca_objects_x_object_representations->delete()");
			return false;
		}
		
		$o_trans = new Transaction();
		$this->setTransaction($o_trans);
		if ($this->get('is_primary')) {
			$o_db = $this->getDb();
			
			// make some other row primary
			$qr_res = $o_db->query("
				SELECT relation_id
				FROM ca_objects_x_object_representations
				WHERE
					object_id = ? AND is_primary = 0
				ORDER BY
					rank, relation_id
			", (int)$vn_object_id);
			if($qr_res->nextRow()) {
				// nope - force this one to be primary
				$t_rep_link = new ca_objects_x_object_representations();
				$t_rep_link->setTransaction($o_trans);
				if ($t_rep_link->load($qr_res->get('relation_id'))) {
					$t_rep_link->setMode(ACCESS_WRITE);
					$t_rep_link->set('is_primary', 1);
					$t_rep_link->update();
					
					if ($t_rep_link->numErrors()) {
						$this->postError(2700, _t('Could not update primary flag for representation: %1', join('; ', $t_rep_link->getErrors())), 'ca_objects_x_object_representations->delete()');
						return false;
					}
				} else {
					$this->postError(2700, _t('Could not load object-representation link'), 'ca_objects_x_object_representations->delete()');
					return false;
				}				
			}
		} 
		if($vb_rc = parent::delete($pb_delete_related)) {
			$o_trans->commitTransaction();
		} else {
			$o_trans->rollbackTransaction();
		}
		
		return $vb_rc;
	}
	# ------------------------------------------------------
}
?>