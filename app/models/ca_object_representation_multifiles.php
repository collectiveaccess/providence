<?php
/** ---------------------------------------------------------------------
 * app/models/ca_object_representation_multifiles.php : table access class for table ca_object_representation_multifiles
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/BaseModel.php");


BaseModel::$s_ca_models_definitions['ca_object_representation_multifiles'] = array(
 	'NAME_SINGULAR' 	=> _t('object representation multifile'),
 	'NAME_PLURAL' 		=> _t('object representation multifiles'),
 	'FIELDS' 			=> array(
 		'multifile_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Multifile id', 'DESCRIPTION' => 'Identifier for Multifile'
		),
		'representation_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Representation id', 'DESCRIPTION' => 'The representation to which this multifile is attached.'
		),
		'resource_path' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 80, 'DISPLAY_HEIGHT' => 2,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Resource path'), 'DESCRIPTION' => _t('Organizational path into which this file is placed.')
		),
		'media' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				
				"MEDIA_PROCESSING_SETTING" => 'ca_object_representation_multifiles',
				
				'LABEL' => _t('Media to upload'), 'DESCRIPTION' => _t('Use this control to select media from your computer to upload.')
		),
		'media_metadata' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Media metadata'), 'DESCRIPTION' => _t('Media metadata')
		),
		'media_content' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Media content'), 'DESCRIPTION' => _t('Media content')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Indicates how multifiles should be ordered in a list. Higher ranks are lower in the list. That is, rank is used to sort in ascending order.')
		)
 	)
);

class ca_object_representation_multifiles extends BaseModel {
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
	protected $TABLE = 'ca_object_representation_multifiles';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'multifile_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('media');

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
	protected $ORDER_BY = array('media');

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
			"representation_id"
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = null;			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = null;			// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	

	# ---------------------------------------------------------------------------------------------
	/**
 	 * Check if currently loaded row is readable
 	 *
 	 * @param RequestHTTP $po_request
 	 * @param string $ps_bundle_name Optional bundle name to test readability on. If omitted readability is considered for the item as a whole.
 	 * @return bool True if record can be read by the current user, false if not
 	 */
	function isReadable($po_request, $ps_bundle_name=null) {
		$t_rep = new ca_object_representations($this->get('representation_id'));
		
		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $po_request->user->getTypeAccessLevel('ca_object_representations', $t_rep->getTypeID());
			if ($vn_type_access < __CA_BUNDLE_ACCESS_READONLY__) {
				return false;
			}
		}
		
		// Check item level restrictions
		
		if (caACLIsEnabled($t_rep)) {
			$vn_item_access = $t_rep->checkACLAccessForUser($po_request->user);
			if ($vn_item_access < __CA_ACL_READONLY_ACCESS__) {
				return false;
			}
		}
		
		if ($ps_bundle_name) {
			if ($po_request->user->getBundleAccessLevel('ca_object_representations', $ps_bundle_name) < __CA_BUNDLE_ACCESS_READONLY__) { return false; }
		}
		
		if ((defined("__CA_APP_TYPE__") && (__CA_APP_TYPE__ == "PAWTUCKET"))) {
			$va_access = caGetUserAccessValues($po_request);
			if (is_array($va_access) && sizeof($va_access) && !in_array($t_rep->get('access'), $va_access)) { return false; }
		}
		
		return true;
	}
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is save-able
 	 *
 	 * @param RequestHTTP $po_request
 	 * @param string $ps_bundle_name Optional bundle name to test write-ability on. If omitted write-ability is considered for the item as a whole.
 	 * @return bool True if record can be saved, false if not
 	 */
 	public function isSaveable($po_request, $ps_bundle_name=null) {
		$t_rep = new ca_object_representations($this->get('representation_id'));
		
 		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $po_request->user->getTypeAccessLevel('ca_object_representations', $t_rep->getTypeID());
			if ($vn_type_access != __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if (caACLIsEnabled($t_rep) && $t_rep->getPrimaryKey()) {
			$vn_item_access = $t_rep->checkACLAccessForUser($po_request->user);
			if ($vn_item_access < __CA_ACL_EDIT_ACCESS__) {
				return false;
			}
		}
		
 		// Check actions
 		if (!$t_rep->getPrimaryKey() && !$po_request->user->canDoAction('can_create_ca_object_representations')) {
 			return false;
 		}
 		if ($t_rep->getPrimaryKey() && !$po_request->user->canDoAction('can_edit_ca_object_representations')) {
 			return false;
 		}
 		
		if ($ps_bundle_name) {
			if ($po_request->user->getBundleAccessLevel('ca_object_representations', $ps_bundle_name) < __CA_BUNDLE_ACCESS_EDIT__) { return false; }
		}
 		
 		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is deletable
 	 */
 	public function isDeletable($po_request) {
		$t_rep = new ca_object_representations($this->get('representation_id'));
		
 		// Is row loaded?
 		if (!$t_rep->getPrimaryKey()) { return false; }
 		
 		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $po_request->user->getTypeAccessLevel('ca_object_representations', $t_rep->getTypeID());
			if ($vn_type_access != __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if (caACLIsEnabled($t_rep) && $t_rep->getPrimaryKey()) {
			$vn_item_access = $t_rep->checkACLAccessForUser($po_request->user);
			if ($vn_item_access < __CA_ACL_EDIT_DELETE_ACCESS__) {
				return false;
			}
		}
		
 		// Check actions
 		if (!$t_rep->getPrimaryKey() || !$po_request->user->canDoAction('can_delete_ca_object_representations')) {
 			return false;
 		}
 		
 		return true;
 	}
	# ------------------------------------------------------
}
