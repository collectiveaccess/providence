<?php
/** ---------------------------------------------------------------------
 * app/models/ca_tours.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_tour_stops.php');


BaseModel::$s_ca_models_definitions['ca_tours'] = array(
 	'NAME_SINGULAR' 	=> _t('tour'),
 	'NAME_PLURAL' 		=> _t('tours'),
 	'FIELDS' 			=> array(
 		'tour_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this item')
		),
		'tour_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 22, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Tour code'), 'DESCRIPTION' => _t('Unique code for tour; used to identify the tour for configuration purposes.'),
				'BOUNDS_LENGTH' => array(1,100),
				'UNIQUE_WITHIN' => array()
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'DISPLAY_FIELD' => array('ca_list_stops.item_value'),
				'DISPLAY_ORDERBY' => array('ca_list_stops.item_value'),
				'IS_NULL' => true, 
				'LIST_CODE' => 'tour_types',
				'DEFAULT' => '',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates the type of the tour.')
		),
		'user_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => 'User id', 'DESCRIPTION' => 'Identifier for owner of tour'
		),
		'rank' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Sort order'), 'DESCRIPTION' => _t('Sort order'),
		),
		'color' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_COLORPICKER, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Color'), 'DESCRIPTION' => _t('Color to identify the tour with')
		),
		'icon' => array(
				'FIELD_TYPE' => FT_MEDIA, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				"MEDIA_PROCESSING_SETTING" => 'ca_icons',
				'LABEL' => _t('Icon'), 'DESCRIPTION' => _t('Optional icon to identify the tour with')
		),
		'access' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Not accessible to public') => 0,
					_t('Accessible to public') => 1
				),
				'LIST' => 'access_statuses',
				'LABEL' => _t('Access'), 'DESCRIPTION' => _t('Indicates if the list item is accessible to the public or not. ')
		),
		'status' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'BOUNDS_CHOICE_LIST' => array(
					_t('Newly created') => 0,
					_t('Editing in progress') => 1,
					_t('Editing complete - pending review') => 2,
					_t('Review in progress') => 3,
					_t('Completed') => 4
				),
				'LIST' => 'workflow_statuses',
				'LABEL' => _t('Status'), 'DESCRIPTION' => _t('Indicates the current state of the tour.')
		)
 	)
);

class ca_tours extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_tours';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'tour_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('tour_id');

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
	protected $ORDER_BY = array('tour_id');

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
	# Group-based access control
	# ------------------------------------------------------
	protected $USER_GROUPS_RELATIONSHIP_TABLE = null;
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_tour_labels';
	
	# ------------------------------------------------------
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = 'type_id';			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = 'tour_types';	// list code (ca_lists.list_code) of list defining types for this table

	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'tour_code';				// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;		// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'TourSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'TourSearchResult';

	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	
	static $s_stop_info_cache;
	
	# ----------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_tour_stops_list'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Tour stops'));
	}
	# ------------------------------------------------------
	/**
	 * @param array $pa_options
	 *		duplicate_subitems
	 */
	public function duplicate($pa_options=null) {
		
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction($o_t = new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			$o_t = $this->getTransaction();
		}
		
		if ($t_dupe = parent::duplicate($pa_options)) {
			$vb_duplicate_subitems = isset($pa_options['duplicate_subitems']) && $pa_options['duplicate_subitems'];
		
			if ($vb_duplicate_subitems) { 
				// Try to dupe related ca_tour_stops rows
				$o_db = $this->getDb();
				
				$qr_res = $o_db->query("
					SELECT *
					FROM ca_tour_stops
					WHERE 
						tour_id = ? AND deleted = 0
				", (int)$this->getPrimaryKey());
				
				$va_stops = array();
				while($qr_res->nextRow()) {
					//$va_stops[$qr_res->get('stop_id')] = $qr_res->getRow();
					$t_stop = new ca_tour_stops($qr_res->get('stop_id'));
					if ($t_dupe_stop = $t_stop->duplicate($pa_options)) {
						$t_dupe_stop->setMode(ACCESS_WRITE);
						$t_dupe_stop->set('tour_id', $t_dupe->getPrimaryKey());
						$t_dupe_stop->update(); 
						
						if ($t_dupe_stop->numErrors()) {
							$this->errors = $t_dupe_stop->errors;
							if ($vb_we_set_transaction) { $this->removeTransaction(false);}
							return false;
						}
					}
				}
			}
		}
		
		
		if ($vb_we_set_transaction) { $this->removeTransaction(true);}
		return $t_dupe;
	}
	# ------------------------------------------------------
	/**
	 * Returns list containing name and tour_ids of all available tours. Names are indexed by locale_id - names for 
	 * all locales are returned.
	 *
	 * @return array - List of available tours, indexed by tour_id and locale_id. Array values are arrays with list information including name, locale and tour_id
	 */
	public function getListOfTours() {
		$o_db = $this->getDb();
		
		$qr_lists = $o_db->query("
			SELECT cl.*, cll.name, cll.locale_id
			FROM ca_tours cl
			LEFT JOIN ca_tour_labels cll ON cl.tour_id = cll.tour_id
			ORDER BY
				cll.tour_id
		");
		$va_lists = array();
		while($qr_lists->nextRow()) {
			$va_tmp =  $qr_lists->getRow();
			
			if (!$va_tmp['name']) { $va_tmp['name'] = $va_tmp['tour_code']; }				// if there's no label then use the tour_code as its' name
			
			$va_tmp['stop_id'] = 1;
			$va_lists[$qr_lists->get('tour_id')][$qr_lists->get('locale_id')] = $va_tmp;
		}
		
		return $va_lists;
	}
	# ----------------------------------------
	#
	# ----------------------------------------
	/**
	 * Returns list of stops for a given tour. 
	 */
	public function getStops($po_request=null, $pa_options=null) {
		if (!$this->getPrimaryKey()) { return false; }
		if (ca_tours::$s_stop_info_cache[$this->getPrimaryKey()]) { return ca_tours::$s_stop_info_cache[$this->getPrimaryKey()]; }
		
		$o_db = $this->getDb();
		
		$va_bundles_to_return =  (isset($pa_options['bundles']) && is_array($pa_options['bundles'])) ? $pa_options['bundles'] : array();

		$qr_res = $o_db->query("
			SELECT ts.*, tsl.*
			FROM ca_tour_stops ts
			INNER JOIN ca_tour_stop_labels AS tsl ON ts.stop_id = tsl.stop_id
			WHERE
				(ts.tour_id = ?) AND (ts.deleted = 0)
			ORDER BY 
				ts.rank, ts.stop_id
		", (int)$this->getPrimaryKey());
		
		$va_stops = array();
		
		$t_list = new ca_lists();
		
		$t_stop = new ca_tour_stops();
		
		if (is_array($va_bundles_to_return)) {
			foreach($va_bundles_to_return as $vs_k => $vs_v) {
				$va_tmp = explode(".", $vs_v);
				$vs_tmp = array_pop($va_tmp);
				unset($va_bundles_to_return[$vs_k]);
				$va_bundles_to_return[$vs_tmp] = $vs_v;
			}
		}
		
		$va_bundles_to_return += array("stop_id" => "ca_tour_stops.stop_id", "tour_id" => "ca_tour_stops.tour_id", "idno" => "ca_tour_stops.idno", "name" => "ca_tour_stops.preferred_labels.name", "locale_id" => "ca_tour_stops.preferred_labels.locale_id", "parent_id" => "ca_tour_stops.parent_id");
		
		$va_stop_ids = $qr_res->getAllFieldValues("stop_id");
		
		if (is_array($va_stop_ids) && sizeof($va_stop_ids) > 0) {
			$qr_stops = $t_stop->makeSearchResult("ca_tour_stops", $va_stop_ids);
			while($qr_stops->nextHit()) {
				if (!$va_stops[$vn_stop_id = $qr_stops->get('stop_id')][$vn_screen_locale_id = $qr_stops->get('locale_id')]) {
					//$va_tmp =  $qr_res->getRow();
					//$va_tmp['typename'] = $t_list->getItemForDisplayByItemID($va_tmp['type_id'], false);
			
					if ($va_bundles_to_return) {
						foreach($va_bundles_to_return as $vs_fld_name => $vs_bundle) {
							$va_tmp[$vs_fld_name] = $qr_stops->get($vs_bundle, array('convertValueToDisplayText' => true));
						}
					}
				
				
					$va_stops[$vn_stop_id][$vn_screen_locale_id] = $va_tmp;
				}
			}
		} else {
			$va_stops = array();
		}
		return ca_tours::$s_stop_info_cache[$this->getPrimaryKey()] = caExtractValuesByUserLocale($va_stops);
	}
	# ----------------------------------------
	/**
	  * Return number of stops configured for currently loaded tour 
	  *
	  *@return int Number of stops configured for the current tour
	  */
	public function getStopCount() {
		if (!$this->getPrimaryKey()) { return false; }
		if (ca_tours::$s_stop_info_cache[$this->getPrimaryKey()]) { return sizeof(ca_tours::$s_stop_info_cache[$this->getPrimaryKey()]); }
		
		return sizeof($this->getStops(null));
	}
	# ------------------------------------------------------
	/**
 	 * Returns a list of stops for the current tour with ranks for each, in rank order
	 *
	 * @param array $pa_options An optional array of options. Supported options are:
	 *			NONE yet
	 * @return array Array keyed on stop_id with values set to ranks for each stop.
	 */
	public function getStopIDRanks($pa_options=null) {
		if(!($vn_tour_id = $this->getPrimaryKey())) { return null; }
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT tsl.stop_id, tsl.rank
			FROM ca_tour_stops tsl
			WHERE
				tsl.tour_id = ? AND tsl.deleted = 0
			ORDER BY 
				tsl.rank ASC
		", (int)$vn_tour_id);
		$va_stops = array();
		
		while($qr_res->nextRow()) {
			$va_stops[$qr_res->get('stop_id')] = $qr_res->get('rank');
		}
		return $va_stops;
	}
	# ------------------------------------------------------
	/**
	 * Sets order of stops in the currently loaded tour to the order of stop_ids as set in $pa_stop_ids
	 *
	 * @param array $pa_stop_ids A list of stop_ids in the tour, in the order in which they should be displayed in the ui
	 * @param array $pa_options An optional array of options. Supported options include:
	 *			NONE
	 * @return array An array of errors. If the array is empty then no errors occurred
	 */
	public function reorderStops($pa_stop_ids, $pa_options=null) {
		if (!($vn_tour_id = $this->getPrimaryKey())) {	
			return null;
		}
		
		$va_stop_ranks = $this->getStopIDRanks($pa_options);	// get current ranks
		
		$vn_i = 0;
		$o_trans = new Transaction();
		$t_stop = new ca_tour_stops();
		$t_stop->setTransaction($o_trans);
		$t_stop->setMode(ACCESS_WRITE);
		$va_errors = array();
		
		
		// delete rows not present in $pa_stop_ids
		$va_to_delete = array();
		foreach($va_stop_ranks as $vn_stop_id => $va_rank) {
			if (!in_array($vn_stop_id, $pa_stop_ids)) {
				if ($t_stop->load(array('tour_id' => $vn_tour_id, 'stop_id' => $vn_stop_id))) {
					$t_stop->delete(true);
				}
			}
		}
		
		
		// rewrite ranks
		foreach($pa_stop_ids as $vn_rank => $vn_stop_id) {
			if (isset($va_stop_ranks[$vn_stop_id]) && $t_stop->load(array('tour_id' => $vn_tour_id, 'stop_id' => $vn_stop_id))) {
				if ($va_stop_ranks[$vn_stop_id] != $vn_rank) {
					$t_stop->set('rank', $vn_rank);
					$t_stop->update();
				
					if ($t_stop->numErrors()) {
						$va_errors[$vn_stop_id] = _t('Could not reorder stop %1: %2', $vn_stop_id, join('; ', $t_stop->getErrors()));
					}
				}
			} 
		}
		
		if(sizeof($va_errors)) {
			$o_trans->rollback();
		} else {
			$o_trans->commit();
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function addStop($ps_name, $pn_type_id, $pn_locale_id, $ps_idno, $ps_color='000000') {
		if (!$this->getPrimaryKey()) { return false; }
		
		$t_stop = new ca_tour_stops();
		$t_stop->setMode(ACCESS_WRITE);
		$t_stop->set('idno', $ps_idno);
		$t_stop->set('type_id', $pn_type_id);
		$t_stop->set('tour_id', $this->getPrimaryKey());
		$t_stop->set('color', $ps_color);
		$t_stop->insert();
		
		if ($t_stop->numErrors()) {
			$this->errors = $t_stop->errors;
			return false;
		}
		
		$t_stop->addLabel(
			array('name' => $ps_name), $pn_locale_id, null, true
		);
		
		if ($t_stop->numErrors()) {
			$this->errors = $t_stop->errors;
			$t_stop->delete(true);
			return false;
		}
		
		return $t_stop;
	}
	# ------------------------------------------------------
	/** 
	 *
	 */
	public function removeStop($pn_stop_id) {
		if (!($vn_tour_id = $this->getPrimaryKey())) { return false; }
		$t_stop = new ca_tour_stops();
		
		if (!$t_stop->load(array('tour_id' => $vn_tour_id, 'stop_id' => $pn_stop_id))) { return false; }
		$t_stop->setMode(ACCESS_WRITE);
		return $t_stop->delete(true);
	}
	# ------------------------------------------------------
	# Bundles
	# ------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of stops in the currently loaded tour
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getTourStopHTMLFormBundle($po_request, $ps_form_name) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_tour', $this);		
		$o_view->setVar('t_stop', new ca_tour_stops());		
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('request', $po_request);
		
		if ($this->getPrimaryKey()) {
			$o_view->setVar('stops', $this->getStops($po_request));
		} else {
			$o_view->setVar('stops', array());
		}
		
		return $o_view->render('ca_tour_stops_list.php');
	}
	# ----------------------------------------
}
?>