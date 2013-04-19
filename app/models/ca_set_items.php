<?php
/** ---------------------------------------------------------------------
 * app/models/ca_set_items.php : table access class for table ca_set_items
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/ca/BundlableLabelableBaseModelWithAttributes.php');


BaseModel::$s_ca_models_definitions['ca_set_items'] = array(
 	'NAME_SINGULAR' 	=> _t('set item'),
 	'NAME_PLURAL' 		=> _t('set items'),
 	'FIELDS' 			=> array(
 		'item_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this set item')
		),
		'set_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Set'), 'DESCRIPTION' => _t('Set item belongs to')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Set content'), 'DESCRIPTION' => _t('Determines what kind of items (objects, entities, places, etc.) are stored by the set.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('Objects') => 57,
					_t('Object lots') => 51,
					_t('Entities') => 20,
					_t('Places') => 72,
					_t('Occurrences') => 67,
					_t('Collections') => 13,
					_t('Storage locations') => 89,
					_t('Object representations') => 56,
					_t('Loans') => 133,
					_t('Movements') => 137,
					_t('List items') => 33,
					_t('Tours') => 153,
					_t('Tour stops') => 155
				)
		),
		'row_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Row_id', 'DESCRIPTION' => 'Primary key value of item in set. Table primary key is of is determined by the table_num field in ca_sets.'
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LIST_CODE' => 'set_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('The type of the set determines what sorts of information the set and each item in the set can have associated with them.')
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('The relative priority of the set when displayed in a list with other sets. Lower numbers indicate higher priority.'),
		),
		'vars' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Set item variable storage', 'DESCRIPTION' => 'Storage area for set item variables'
		)
 	)
);

class ca_set_items extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_set_items';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'item_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('row_id');

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
	protected $ORDER_BY = array('rank');

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
			'set_id'
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_set_item_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'set_types';		// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	
	/** 
	 * Container for persistent set item-specific variables
	 */
	private $opa_set_item_vars;
	private $opa_set_item_vars_have_changed = false;
	
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
	# ----------------------------------------
	/**
	 * Loads record.
	 *
	 * @access public
	 * @param int $pn_set_item_id Set item id to load. 
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function load($pn_set_item_id=null, $pb_use_cache=true) {
		$vn_rc = parent::load($pn_set_item_id, $pb_use_cache);
		
		# load set item vars (the get() method automatically unserializes the data)
		$this->opa_set_item_vars = $this->get("vars");
		$this->opa_set_item_vars_have_changed = false;
		
		if (!isset($this->opa_set_item_vars) || !is_array($this->opa_set_item_vars)) {
			$this->opa_set_item_vars = array();
		}
		return $vn_rc;
	}
	# ----------------------------------------
	/**
	 * Creates new set item record. You must set all required fields before calling this method. 
	 * If errors occur you can use the standard BaseModel class error handling methods to figure out what went wrong.
	 *
	 * @access public 
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function insert($pa_options=null) {
		
		# set vars (the set() method automatically serializes the vars array)
		$this->set("vars",$this->opa_set_item_vars);
		
		return parent::insert($pa_options);
	}
	# ----------------------------------------
	/**
	 * Saves changes to set item record. 
	 * You must make sure all required fields are set before calling this method. 
	 If errors occur you can use the standard BaseModel class error handling methods to figure out what went wrong.
	 *
	 * If you do not call this method at the end of your request changed vars will not be saved! 
	 *
	 * @access public
	 * @return bool Returns true if no error, false if error occurred
	 */	
	public function update($pa_options=null) {
		$this->clearErrors();
		
		# set user vars (the set() method automatically serializes the vars array)
		if ($this->opa_set_item_vars_have_changed) {
			$this->set("vars",$this->opa_set_item_vars);
		}
		return parent::update($pa_options);
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
		$this->BUNDLES['preferred_labels'] = array('type' => 'preferred_label', 'repeating' => true, 'label' => _t("Item captions"));
	}
	# ------------------------------------------------------
 	/**
 	 * Matching method to ca_objects::getRepresentations(), except this one only returns a single representation - the currently loaded one
 	 */
 	public function getRepresentations($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
 		if (!($this->getPrimaryKey())) { return null; }
 		if ($this->get('table_num') != 57) { return array(); } 	// 57=ca_objects
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!is_array($pa_versions)) { 
 			$pa_versions = array('preview170');
 		}
 		if (is_array($pa_options['return_with_access']) && sizeof($pa_options['return_with_access']) > 0) {
 			$vs_access_sql = ' AND (caor.access IN ('.join(", ", $pa_options['return_with_access']).'))';
 		} else {
 			$vs_access_sql = '';
 		}
 		$o_db = $this->getDb();
 		
 		$qr_reps = $o_db->query("
 			SELECT caor.representation_id, caor.media, caor.access, caor.status, l.name, caor.locale_id, caor.media_metadata, caor.type_id
 			FROM ca_object_representations caor
 			INNER JOIN ca_objects_x_object_representations AS coxor ON coxor.representation_id = caor.representation_id
 			LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
 			INNER JOIN ca_set_items AS csi ON csi.row_id = coxor.object_id
 			WHERE
 				(csi.item_id = ?) AND (csi.table_num = 57)
 				{$vs_is_primary_sql}
 				{$vs_access_sql}
 			ORDER BY
 				l.name ASC 
 		", (int)$this->getPrimaryKey());
 		$va_reps = array();
 		while($qr_reps->nextRow()) {
 			$va_tmp = $qr_reps->getRow();
 			$va_tmp['tags'] = array();
 			$va_tmp['urls'] = array();
 			
 			$va_info = $qr_reps->getMediaInfo('media');
 			$va_tmp['info'] = array('original_filename' => $va_info['ORIGINAL_FILENAME']);
 			foreach ($pa_versions as $vs_version) {
 				if (is_array($pa_version_sizes) && isset($pa_version_sizes[$vs_version])) {
 					$vn_width = $pa_version_sizes[$vs_version]['width'];
 					$vn_height = $pa_version_sizes[$vs_version]['height'];
 				} else {
 					$vn_width = $vn_height = 0;
 				}
 				
 				if ($vn_width && $vn_height) {
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, array_merge($pa_options, array('viewer_width' => $vn_width, 'viewer_height' => $vn_height)));
 				} else {
 					$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, $pa_options);
 				}
 				$va_tmp['urls'][$vs_version] = $qr_reps->getMediaUrl('media', $vs_version);
 				$va_tmp['paths'][$vs_version] = $qr_reps->getMediaPath('media', $vs_version);
 				$va_tmp['info'][$vs_version] = $qr_reps->getMediaInfo('media', $vs_version);
 				
 				$va_dimensions = array();
 				if (isset($va_tmp['info'][$vs_version]['WIDTH']) && isset($va_tmp['info'][$vs_version]['HEIGHT'])) {
					if (($vn_w = $va_tmp['info'][$vs_version]['WIDTH']) && ($vn_h = $va_tmp['info'][$vs_version]['WIDTH'])) {
						$va_dimensions[] = $va_tmp['info'][$vs_version]['WIDTH'].'p x '.$va_tmp['info'][$vs_version]['HEIGHT'].'p';
					}
				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth']) && ($vn_depth = $va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth'])) {
 					$va_dimensions[] = intval($vn_depth).' bpp';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['colorspace']) && ($vs_colorspace = $va_tmp['info'][$vs_version]['PROPERTIES']['colorspace'])) {
 					$va_dimensions[] = $vs_colorspace;
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['resolution']) && is_array($va_resolution = $va_tmp['info'][$vs_version]['PROPERTIES']['resolution'])) {
 					if (isset($va_resolution['x']) && isset($va_resolution['y']) && $va_resolution['x'] && $va_resolution['y']) {
 						// TODO: units for resolution? right now assume pixels per inch
 						if ($va_resolution['x'] == $va_resolution['y']) {
 							$va_dimensions[] = $va_resolution['x'].'ppi';
 						} else {
 							$va_dimensions[] = $va_resolution['x'].'x'.$va_resolution['y'].'ppi';
 						}
 					}
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['duration']) && ($vn_duration = $va_tmp['info'][$vs_version]['PROPERTIES']['duration'])) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_duration).'s';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['pages']) && ($vn_pages = $va_tmp['info'][$vs_version]['PROPERTIES']['pages'])) {
 					$va_dimensions[] = $vn_pages.' '.(($vn_pages == 1) ? _t('page') : _t('pages'));
 				}
 				if (!isset($va_tmp['info'][$vs_version]['PROPERTIES']['filesize']) || !($vn_filesize = $va_tmp['info'][$vs_version]['PROPERTIES']['filesize'])) {
 					$vn_filesize = @filesize($qr_reps->getMediaPath('media', $vs_version));
 				}
 				if ($vn_filesize) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_filesize/(1024*1024)).'mb';
 				}
 				$va_tmp['dimensions'][$vs_version] = join('; ', $va_dimensions);
 			}
 			
 				
			if (isset($va_info['INPUT']['FETCHED_FROM']) && ($vs_fetched_from_url = $va_info['INPUT']['FETCHED_FROM'])) {
				$va_tmp['fetched_from'] = $vs_fetched_from_url;
				$va_tmp['fetched_on'] = (int)$va_info['INPUT']['FETCHED_ON'];
			}
 			
 			$va_reps[] = $va_tmp;
 		}
 		
 		return $va_reps;
 	}
 	# ----------------------------------------
	# --- Set item variables
	# ----------------------------------------
	/**
	 * Sets set item variable. Set item variables are names ("keys") with associated values (strings, numbers or arrays).
	 * Once a set item variable is set its value persists across instantiations until deleted or changed.
	 *
	 * Changes to set item variables are saved when the insert() (for new item records) or update() (for existing item records)
	 * method is called. If you do not call either of these any changes will be lost when the request completes.
	 *
	 * @access public
	 * @param string $ps_key Name of set item variable
	 * @param mixed $pm_val Value of set item variable. Can be string, number or array.
	 * @param array $pa_options Associative array of options. Supported options are:
	 *		- ENTITY_ENCODE_INPUT = Convert all "special" HTML characters in variable value to entities; default is true
	 *		- URL_ENCODE_INPUT = Url encodes variable value; default is  false
	 * @return bool Returns true on successful save, false if the variable name or value was invalid
	 */	
	public function setVar ($ps_key, $pm_val, $pa_options=null) {
		if (is_object($pm_val)) { return false; }
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$this->clearErrors();
		if ($ps_key) {			
			
			$va_vars =& $this->opa_set_item_vars;
			$vb_has_changed =& $this->opa_set_item_vars_have_changed;	
			
			if (isset($pa_options["ENTITY_ENCODE_INPUT"]) && $pa_options["ENTITY_ENCODE_INPUT"]) {
				if (is_string($pm_val)) {
					$vs_proc_val = htmlentities(html_entity_decode($pm_val));
				} else {
					$vs_proc_val = $pm_val;
				}
			} else {
				if (isset($pa_options["URL_ENCODE_INPUT"]) && $pa_options["URL_ENCODE_INPUT"]) {
					$vs_proc_val = urlencode($pm_val);
				} else {
					$vs_proc_val = $pm_val;
				}
			}
			
			if (
				(
					(is_array($vs_proc_val) && !is_array($va_vars[$ps_key]))
					||
					(!is_array($vs_proc_val) && is_array($va_vars[$ps_key]))
					||
					(is_array($vs_proc_val) && (is_array($va_vars[$ps_key])) && (sizeof($vs_proc_val) != sizeof($va_vars[$ps_key])))
					||
					(md5(print_r($vs_proc_val, true)) != md5(print_r($va_vars[$ps_key], true)))
				)
			) {
				$vb_has_changed = true;
				$va_vars[$ps_key] = $vs_proc_val;
			} else {
				if ((string)$vs_proc_val != (string)$va_vars[$ps_key]) {
					$vb_has_changed = true;
					$va_vars[$ps_key] = $vs_proc_val;
				}
			}
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Deletes set item variable. Once deleted, you must call insert() (for new item records) or update() (for existing item records)
	 * to make the deletion permanent.
	 *
	 * @access public
	 * @param string $ps_key Name of set item variable
	 * @return bool Returns true if variable was defined, false if it didn't exist
	 */	
	public function deleteVar ($ps_key) {
		$this->clearErrors();
		
		if (isset($this->opa_set_item_vars[$ps_key])) {
			unset($this->opa_set_item_vars[$ps_key]);
			$this->opa_set_item_vars_have_changed = true;
			return true;
		} 
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns value of set item variable. Returns null if variable does not exist.
	 *
	 * @access public
	 * @param string $ps_key Name of set item variable
	 * @return mixed Value of variable (string, number or array); null is variable is not defined.
	 */	
	public function getVar ($ps_key) {
		$this->clearErrors();
		if (isset($this->opa_set_item_vars[$ps_key])) {
			return (is_array($this->opa_set_item_vars[$ps_key])) ? $this->opa_set_item_vars[$ps_key] : stripSlashes($this->opa_set_item_vars[$ps_key]);
		}
		return null;
	}
	# ----------------------------------------
	/**
	 * Returns list of set item variable names
	 *
	 * @access public
	 * @return array Array of set item names, or empty array if none are defined
	 */	
	public function getVarKeys() {
		$va_keys = array();
		if (isset($this->opa_set_item_vars) && is_array($this->opa_set_item_vars)) {
			$va_keys = array_keys($this->opa_set_item_vars);
		}
		
		return $va_keys;
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function getSelectedRepresentationIDs() {
		if ($this->get('table_num') != 57) { return null; }
		
		return is_array($va_reps = $this->getVar('selected_representations')) ? $va_reps : array();
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function getRepresentationCount() {
		if ($this->get('table_num') != 57) { return null; }
		
		$t_object = new ca_objects($this->get('row_id'));
		return (int)$t_object->getRepresentationCount();
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function getSelectedRepresentationCount() {
		if ($this->get('table_num') != 57) { return null; }
		
		return sizeof($this->getSelectedRepresentationIDs());
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function addSelectedRepresentation($pn_representation_id) {
		if ($this->get('table_num') != 57) { return null; }
		
		$va_reps = $this->getSelectedRepresentationIDs();
		$va_reps[$pn_representation_id] = 1;
		$this->setMode(ACCESS_WRITE);
		$this->setVar('selected_representations', $va_reps);
	}
	# ----------------------------------------
	/**
	 * 
	 */	
	public function removeSelectedRepresentation($pn_representation_id) {
		if ($this->get('table_num') != 57) { return null; }
		
		$va_reps = $this->getSelectedRepresentationIDs();
		unset($va_reps[$pn_representation_id]);
		$this->setMode(ACCESS_WRITE);
		$this->setVar('selected_representations', $va_reps);
	}
	# ----------------------------------------
}
?>
