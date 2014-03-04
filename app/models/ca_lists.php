<?php
/** ---------------------------------------------------------------------
 * app/models/ca_lists.php : table access class for table ca_lists
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
require_once(__CA_APP_DIR__.'/models/ca_list_items.php');
require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/listHelpers.php');
require_once(__CA_MODELS_DIR__.'/ca_locales.php');
require_once(__CA_MODELS_DIR__.'/ca_list_item_labels.php');

define('__CA_LISTS_SORT_BY_LABEL__', 0);
define('__CA_LISTS_SORT_BY_RANK__', 1);
define('__CA_LISTS_SORT_BY_VALUE__', 2);
define('__CA_LISTS_SORT_BY_IDENTIFIER__', 3);


BaseModel::$s_ca_models_definitions['ca_lists'] = array(
 	'NAME_SINGULAR' 	=> _t('list'),
 	'NAME_PLURAL' 		=> _t('lists'),
 	'FIELDS' 			=> array(
 		'list_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this list')
		),
		'list_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 22, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('List code'), 'DESCRIPTION' => _t('Unique code for list; used to identify the list for configuration purposes.'),
				'BOUNDS_LENGTH' => array(1,100),
				'UNIQUE_WITHIN' => array()
		),
		'default_sort' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => __CA_LISTS_SORT_BY_LABEL__,
				'LABEL' => _t('Default sort order'), 'DESCRIPTION' => _t('Specifies the default method to employ to order items in this list.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('By label') => __CA_LISTS_SORT_BY_LABEL__,
					_t('By rank') => __CA_LISTS_SORT_BY_RANK__,
					_t('By value') => __CA_LISTS_SORT_BY_VALUE__,
					_t('By identifier') => __CA_LISTS_SORT_BY_IDENTIFIER__,
				),
				'REQUIRES' => array('is_administrator')
		),
		'is_system_list' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is system list'), 'DESCRIPTION' => _t('Set this if the list is a list used by the system to populate a specific field (as opposed to a user defined list or vocabulary). In general, system lists are defined by the system installer - you should not have to create system lists on your own.'),
				'BOUNDS_VALUE' => array(0,1),
				'REQUIRES' => array('is_administrator')
		),
		'is_hierarchical' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is hierarchical'), 'DESCRIPTION' => _t('Set this if the list is hierarchically structured; leave unset if you are creating a simple "flat" list.'),
				'BOUNDS_VALUE' => array(0,1)
		),
		'use_as_vocabulary' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Use as vocabulary'), 'DESCRIPTION' => _t('Set this if the list is to be used as a controlled vocabulary for cataloguing.'),
				'BOUNDS_VALUE' => array(0,1)
		)
 	)
);

class ca_lists extends BundlableLabelableBaseModelWithAttributes {
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
	protected $TABLE = 'ca_lists';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'list_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('list_code');

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
	protected $ORDER_BY = array('list_code');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

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
	# Attributes
	# ------------------------------------------------------
	protected $ATTRIBUTE_TYPE_ID_FLD = null;			// name of type field for this table - attributes system uses this to determine via ca_metadata_type_restrictions which attributes are applicable to rows of the given type
	protected $ATTRIBUTE_TYPE_LIST_CODE = null;			// list code (ca_lists.list_code) of list defining types for this table
	
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_list_labels';
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = 'ListSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'ListSearchResult';
	
	# ------------------------------------------------------
	# Self-relations
	# ------------------------------------------------------
	protected $SELF_RELATION_TABLE_NAME = null;
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	static $s_list_item_cache = array();
	static $s_list_id_cache = array();
	static $s_list_code_cache = array();
	static $s_list_item_display_cache = array();			// cache for results of getItemFromListForDisplayByItemID()
	static $s_list_item_value_display_cache = array();		// cache for results of getItemFromListForDisplayByItemValue()
	static $s_list_item_get_cache = array();				// cache for results of getItemFromList()
	static $s_item_id_cache = array();						// cache for ca_lists::getItemID()
	
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
	/**
	 *
	 */
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function insert($pa_options=null) {
		$vn_rc = parent::insert($pa_options);
		if ($this->getPrimaryKey()) {
			// create root in ca_list_items
			$t_item_root = new ca_list_items();
			$t_item_root->setMode(ACCESS_WRITE);
			$t_item_root->set('list_id', $this->getPrimaryKey());
			$t_item_root->set('idno', $vs_title = 'Root node for '.$this->get('list_code'));
			$t_item_root->set('is_enabled', 0);
			$t_item_root->set('item_value', 'Root');
			$t_item_root->insert();
			
			if ($t_item_root->numErrors()) {
				$this->delete();
				$this->errors = array_merge($this->errors, $t_item_root->errors);
				return false;
			}
			
			$vn_locale_id = ca_locales::getDefaultCataloguingLocaleID();
			$t_item_root->addLabel(
				array('name_singular' => $vs_title, 'name_plural' => $vs_title),
				$vn_locale_id, null, true
			);
			if ($t_item_root->numErrors()) {
				$this->delete();
				$this->errors = array_merge($this->errors, $t_item_root->errors);
				return false;
			}
		}
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	# List maintenance
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addItem($ps_value, $pb_is_enabled=true, $pb_is_default=false, $pn_parent_id=null, $pn_type_id=null, $ps_idno=null, $ps_validation_format='', $pn_status=0, $pn_access=0, $pn_rank=null) {
		if(!($vn_list_id = $this->getPrimaryKey())) { return null; }
		
		$t_item = new ca_list_items();
		$t_item->setMode(ACCESS_WRITE);
		
		$t_item->set('list_id', $vn_list_id);
		$t_item->set('item_value', $ps_value);
		$t_item->set('is_enabled', $pb_is_enabled ? 1 : 0);
		$t_item->set('is_default', $pb_is_default ? 1 : 0);
		$t_item->set('parent_id', $pn_parent_id);
		$t_item->set('type_id', $pn_type_id);
		$t_item->set('idno', $ps_idno);
		$t_item->set('validation_format', $ps_validation_format);
		$t_item->set('status', $pn_status);
		$t_item->set('access', $pn_access);
		if (!is_null($pn_rank)) { $t_item->set('rank', $pn_rank); }
		
		$vn_item_id = $t_item->insert();
		
		if ($t_item->numErrors()) { 
			$this->errors = array_merge($this->errors, $t_item->errors);
			return false;
		}
		
		return $t_item;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function editItem($pn_item_id, $ps_value, $pb_is_enabled=true, $pb_is_default=false, $pn_parent_id=null, $pn_type_id=null, $ps_idno=null, $ps_validation_format='', $pn_status=0, $pn_access=0, $pn_rank=null) {
		die("Not implemented");
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function removeItem($pn_item_id) {
		die("Not implemented");
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addLabelToItem($pn_item_id, $pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false, $pn_status=0, $ps_description='') {
		die("Not implemented");
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function removeLabelFromItem($pn_item_id, $pn_label_id) {
		die("Not implemented");
	}
	# ------------------------------------------------------
	/**
	 * Returns items contained in list, including their labels. This method will returned the list items sorted according to the default_sort field setting of
	 * the list they belong to. Note that correct order when sorting by label is only guaranteed if 'extractValuesByUserLocale' is set to true [default is false]. 
	 * This is due to the list return format: since each item is indexed by item_id first, it can only have a single position in the return structure. If multiple labels are returned
	 * for an item then the item will only be in the correct sort order for one of the labels in most cases. To ensure proper sort order by label text, labels must be restricted to a 
	 * single locale.
	 * 
	 * @param $pm_list_name_or_id mixed - list_code or list_id of desired list
	 * @param $pa_options array - optional array of options. Supported options include:
	 *			returnHierarchyLevels =		if true list is returned with 'LEVEL' field set to hierarchical level of item, and items are returned in order such that if you loop through the returned list and indent each item according to its level you get a nicely formatted hierarchical display. Default is false.
	 * 			extractValuesByUserLocale = if true then values are processed to be appropriate for current user locale; default is false:  return values for all locales
	 *			directChildrenOnly =	 	if true, only children immediately below the specified item are returned; [default is false]
	 * 			includeSelf =	if true, the specified item is included in the returned set of items; [default is false]
	 *			type_id = 		optional list item type to limit returned items by; default is to not limit by type (eg. type_id = null)
	 *			item_id =		optional item_id to use as root of hierarchy for returned items; if this is not set (the default) then all items in the list are returned
	 *			sort =			if set to a __CA_LISTS_SORT_BY_*__ constant, will force the list to be sorted by that criteria overriding the sort order set in the ca_lists.default_sort field
	 *			idsOnly = 		if true, only the primary key id values of the list items are returned
	 *			enabledOnly =	return only enabled list items [default=false]
	 *			labelsOnly = 	if true only labels in the current locale are returns in an array key'ed on item_id
	 *
	 * @return array List of items indexed first on item_id and then on locale_id of label
	 */
	public function getItemsForList($pm_list_name_or_id, $pa_options=null) {
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!isset($pa_options['returnHierarchyLevels'])) { $pa_options['returnHierarchyLevels'] = false; }
		if ((isset($pa_options['directChildrenOnly']) && $pa_options['directChildrenOnly'])) { $pa_options['returnHierarchyLevels'] = false; }
	
		$vb_enabled_only = caGetOption('enabledOnly', $pa_options, false);
	
		$vb_labels_only = false;
		if (isset($pa_options['labelsOnly']) && $pa_options['labelsOnly']) {
			$pa_options['extractValuesByUserLocale'] = true;
			$pa_options['returnHierarchyLevels'] = false;
			
			$vb_labels_only = true;
		}
	
		$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_options, array('list_id' => $vn_list_id)));
		
		if (is_array(ca_lists::$s_list_item_cache[$vs_cache_key])) {
			return(ca_lists::$s_list_item_cache[$vs_cache_key]);
		}
		$t_list = new ca_lists($vn_list_id);
		$pn_type_id = isset($pa_options['type_id']) ? (int)$pa_options['type_id'] : null;
		$pn_sort = isset($pa_options['sort']) ? (int)$pa_options['sort'] : $t_list->get('default_sort');
		
		if (!($pn_item_id = isset($pa_options['item_id']) ? (int)$pa_options['item_id'] : null)) {
			$pn_item_id = $t_list->getRootListItemID($vn_list_id);
		}
		
		$t_list_item = new ca_list_items($pn_item_id);
		if (!$t_list_item->getPrimaryKey() || ($t_list_item->get('list_id') != $vn_list_id)) { return null; }

		$vs_hier_sql = '';
		if ($t_list_item->getPrimaryKey()) {
			$vs_hier_sql = " AND ((cli.hier_left >= ".(floatval($t_list_item->get('hier_left'))).") AND (cli.hier_right <= ".(floatval($t_list_item->get('hier_right')))."))";
		}
		
		if (!isset($pa_options['returnHierarchyLevels']) || !$pa_options['returnHierarchyLevels']) {
			$vs_type_sql = '';
			if ($pn_type_id) {
				$vs_type_sql = ' AND (cli.type_id = '.intval($pn_type_id).')';
			}
			
			$vs_order_by = '';
			switch($pn_sort) {
				case __CA_LISTS_SORT_BY_LABEL__:	// by label
					$vs_order_by = 'clil.name_plural';
					break;
				case __CA_LISTS_SORT_BY_RANK__:	// by rank
					$vs_order_by = 'cli.rank';
					break;
				case __CA_LISTS_SORT_BY_VALUE__:	// by value
					$vs_order_by = 'cli.item_value';
					break;
				case __CA_LISTS_SORT_BY_IDENTIFIER__:	// by identifier
					$vs_order_by = 'cli.idno_sort';
					break;
			}
			
			if ($vs_order_by) {
				$vs_order_by = "ORDER BY {$vs_order_by}";
			}
			
			$vs_enabled_sql = '';
			if ($vb_enabled_only) { $vs_enabled_sql = ' AND (cli.is_enabled = 1)'; }
			
			$vs_direct_children_sql = '';
			if ((isset($pa_options['directChildrenOnly']) && $pa_options['directChildrenOnly'])) {
				$vs_direct_children_sql = " AND cli.parent_id = ".(int)$pn_item_id;
			}
			$o_db = $this->getDb();
			$vs_sql = "
				SELECT clil.*, cli.*
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id
				WHERE
					(cli.deleted = 0) AND (clil.is_preferred = 1) AND (cli.list_id = ?) {$vs_type_sql} {$vs_direct_children_sql} {$vs_hier_sql} {$vs_enabled_sql}
				{$vs_order_by}
			";
			//print $vs_sql;
			$qr_res = $o_db->query($vs_sql, (int)$vn_list_id);
			
			$va_seen_locales = array();
			$va_items = array();
			while($qr_res->nextRow()) {
				$vn_item_id = $qr_res->get('item_id');
				if ((isset($pa_options['idsOnly']) && $pa_options['idsOnly'])) {
					$va_items[] = $vn_item_id;
					continue;
				}
				if ((!isset($pa_options['includeSelf']) || !$pa_options['includeSelf']) && ($vn_item_id == $pn_item_id)) { continue; }
				if ((isset($pa_options['directChildrenOnly']) && $pa_options['directChildrenOnly']) && ($qr_res->get('parent_id') != $pn_item_id)) { continue; }
				
				$va_items[$vn_item_id][$vn_locale_id = $qr_res->get('locale_id')] = $qr_res->getRow();
				$va_seen_locales[$vn_locale_id] = true;
			}
			
			if ((isset($pa_options['idsOnly']) && $pa_options['idsOnly'])) {
				return $va_items;
			}
			
			if (isset($pa_options['extractValuesByUserLocale']) && $pa_options['extractValuesByUserLocale']) {
				$va_items = caExtractValuesByUserLocale($va_items);
				
				if (($pn_sort == 0) && (sizeof($va_seen_locales) > 1)) {	// do we need to resort list based upon labels? (will already be in correct order if there's only one locale)
					$va_labels = array();
					foreach($va_items as $vn_item_id => $va_row) {
						$va_labels[$va_row['name_plural'].$vn_item_id] = $va_row;
					}
					ksort($va_labels);
					
					$va_items = array();
					foreach($va_labels as $vs_key => $va_row) {
						$va_items[$va_row['item_id']] = $va_row;
					}
				}
			}
			
			if ($vb_labels_only) {
				$va_labels = array();
				foreach($va_items as $vn_item_id => $va_row) {
					$va_labels[$vn_item_id] = $va_row['name_plural'];
				}
				return $va_labels;
			}
		} else {
			// hierarchical output
			$va_list_items = $t_list_item->getHierarchyAsList($pn_item_id, array(
				'additionalTableToJoin' => 'ca_list_item_labels',
				'additionalTableSelectFields' => array('name_singular', 'name_plural', 'locale_id'),
				'additionalTableWheres' => array('ca_list_item_labels.is_preferred = 1')
			));
			
			foreach($va_list_items as $vn_i => $va_item) {
				if ($pn_type_id && $va_item['NODE']['type_id'] != $pn_type_id) { continue; }
				if ($vb_enabled_only && !$va_item['NODE']['is_enabled']) { continue; }
				
				$vn_item_id = $va_item['NODE']['item_id'];
				$vn_parent_id = $va_item['NODE']['parent_id'];
				
				if ((!isset($pa_options['includeSelf']) || !$pa_options['includeSelf']) && ($vn_item_id == $pn_item_id)) { continue; }
				if ((isset($pa_options['directChildrenOnly']) && $pa_options['directChildrenOnly']) && ($vn_parent_id != $pn_item_id)) { continue; }
				
				switch($pn_sort) {
					case __CA_LISTS_SORT_BY_LABEL__:			// label
					default:
						$vs_key = $va_item['NODE']['name_singular'];
						break;
					case __CA_LISTS_SORT_BY_RANK__:			// rank
						$vs_key = sprintf("%08d", (int)$va_item['NODE']['rank']);
						break;
					case __CA_LISTS_SORT_BY_VALUE__:			// value
						$vs_key = $va_item['NODE']['item_value'];
						break;
					case __CA_LISTS_SORT_BY_IDENTIFIER__:			// identifier
						$vs_key = $va_item['NODE']['idno_sort'];
						break;
				}
				if (isset($pa_options['extractValuesByUserLocale']) && $pa_options['extractValuesByUserLocale']) {
					$va_items[$vn_parent_id][$vn_item_id][$va_item['NODE']['locale_id']][$vs_key][$vn_item_id] = array_merge($va_item['NODE'], array('LEVEL' => $va_item['LEVEL']));
				} else {
					$va_items[$vn_parent_id][$va_item['NODE']['locale_id']][$vs_key][$vn_item_id] = array_merge($va_item['NODE'], array('LEVEL' => $va_item['LEVEL']));
				}
			}
			
			$pa_sorted_items = array();
			if (is_array($va_items) && (isset($pa_options['extractValuesByUserLocale']) && $pa_options['extractValuesByUserLocale'])) {
				//$va_items = caExtractValuesByUserLocale($va_items);
				$va_proc_items = array();
				foreach($va_items as $vn_parent_id => $va_item) {
					$va_item = caExtractValuesByUserLocale($va_item);
					
					foreach($va_item as $vn_item_id => $va_items_by_key) {
						foreach($va_items_by_key as $vs_key => $va_val) {
							foreach($va_val as $vn_item_id => $va_item_info) {
								$va_proc_items[$vn_parent_id][$vs_key][$vn_item_id] = $va_item_info;
							}
						}
					}
				}
				$this->_getItemsForListProcListLevel($pn_item_id, $va_proc_items, $pa_sorted_items, $pa_options);
			} else {
				$this->_getItemsForListProcListLevel($pn_item_id, $va_items, $pa_sorted_items, $pa_options);
			}
			$va_items = $pa_sorted_items;
		}
		
		ca_lists::$s_list_item_cache[$vs_cache_key] = $va_items;
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Recursive function that processes each level of hierarchical list
	 */
	private function _getItemsForListProcListLevel($pn_root_id, $pa_items, &$pa_sorted_items, $pa_options) {
		$va_items = $pa_items[$pn_root_id];
		if (!is_array($va_items)) { return; }
		if (isset($pa_options['extractValuesByUserLocale']) && $pa_options['extractValuesByUserLocale']) {
			uksort($va_items, "strnatcasecmp");
			foreach($va_items as $vs_key => $va_items_by_item_id) {
				foreach($va_items_by_item_id as $vn_item_id => $va_item_level) {
					// output this item
					// ...
					$pa_sorted_items[$vn_item_id] = $va_item_level;
					if (isset($pa_items[$vn_item_id])) {	// are there children?
						$this->_getItemsForListProcListLevel($vn_item_id, $pa_items, $pa_sorted_items, $pa_options);
					}
				}
			}
		} else {
			foreach($va_items as $vn_locale_id => $va_items_by_locale_id) {
				ksort($va_items_by_locale_id);
				foreach($va_items_by_locale_id as $vs_key => $va_items_by_item_id) {
					foreach($va_items_by_item_id as $vn_item_id => $va_item_level) {
						// output this item
						// ...
						$pa_sorted_items[$vn_item_id][$va_item_level['locale_id']] = $va_item_level;
						if (isset($pa_items[$vn_item_id])) {	// are there children?
							$this->_getItemsForListProcListLevel($vn_item_id, $pa_items, $pa_sorted_items, $pa_options);
						}
					}
				}
			}
		}
	}
	# ------------------------------------------------------
	/**
	 * Returns list items below the specified item in the specified list
	 *
	 * Options:
	 * 		includeSelf - if true, the specified item is included in the returned set of items; [default is false]
	 *		directChildrenOnly - if true, only children immediately below the specified item are returned; [default is false]
	 */
	public function getChildItemsForList($pm_list_name_or_id, $pn_item_id, $pa_options=null) {
		if ($pm_list_name_or_id) {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
			$this->load($vn_list_id);
		}
		
		if (!($vn_list_id = $this->getPrimaryKey())) { return null; }
		
		$o_db = $this->getDb();
		$t_item = new ca_list_items($pn_item_id);
		if (!$t_item->getPrimaryKey() || ($t_item->get('list_id') != $vn_list_id)) { return null; }
		
		$vs_order_by = '';
		switch($this->get('default_sort')) {
			case __CA_LISTS_SORT_BY_LABEL__:	// by label
				$vs_order_by = 'clil.name_plural';
				break;
			case __CA_LISTS_SORT_BY_RANK__:	// by rank
				$vs_order_by = 'cli.rank';
				break;
			case __CA_LISTS_SORT_BY_VALUE__:	// by value
				$vs_order_by = 'cli.item_value';
				break;
			case __CA_LISTS_SORT_BY_IDENTIFIER__:	// by identifier
				$vs_order_by = 'cli.idno_sort';
				break;
		}
		$vs_order_by = "ORDER BY {$vs_order_by}";
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_list_items cli
			INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id
			WHERE
				(cli.deleted = 0) AND (clil.is_preferred = 1) AND (cli.list_id = ?) AND (cli.hier_left >= ? AND cli.hier_right <= ?)
			{$vs_order_by}
		", (int)$vn_list_id, floatval($t_item->get('hier_left')), floatval($t_item->get('hier_right')));
		
		$va_items = array();
		while($qr_res->nextRow()) {
			$vn_item_id = $qr_res->get('item_id');
			if ((!isset($pa_options['includeSelf']) || !$pa_options['includeSelf']) && ($vn_item_id == $pn_item_id)) { continue; }
			if ((isset($pa_options['directChildrenOnly']) && $pa_options['directChildrenOnly']) && ($qr_res->get('parent_id') != $pn_item_id)) { continue; }
			$va_items[$vn_item_id][$qr_res->get('locale_id')] = $qr_res->getRow();
		}
		
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of items in specified list, not including the list root. If optional $pn_type_id parameter is passed then the count is 
	 * for items with the specified type in the specified list.
	 *
	 * @param mixed $pm_list_name_or_id
	 * @param int $pn_type_id
	 * @param array $pa_options Supported options are:
	 *		includeRoot - include root record for list in count; default is false
	 */
	public function numItemsInList($pm_list_name_or_id=null, $pn_type_id=null, $pa_options=null) {
		if (!$pm_list_name_or_id) {
			$vn_list_id = $this->getPrimaryKey();
		} else {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
		}
		
		if (!$vn_list_id) { return null; }
		
		
		if (isset($pa_options['includeRoot']) && $pa_options['includeRoot']) {
			$vs_include_root_sql = '';
		} else {
			$vs_include_root_sql = ' AND (cli.parent_id IS NOT NULL)';
		}
		
		$o_db = $this->getDb();
		
		$vs_type_sql = '';
		if ($pn_type_id) {
			$vs_type_sql = ' AND (cli.type_id = '.intval($pn_type_id).')';
		}
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_list_items cli
			WHERE
				(cli.deleted = 0) AND (cli.list_id = ?) {$vs_type_sql} {$vs_include_root_sql}
		", (int)$vn_list_id);
		
		if($qr_res->nextRow()) {
			return $qr_res->get('c');
		}
		
		return 0;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemInstanceFromList($pm_list_name_or_id, $ps_item_idno) {
		if (is_array($va_item = $this->getItemFromList($pm_list_name_or_id, $ps_item_idno))) {
			if($va_item['item_id']) {
				return new ca_list_items($va_item['item_id']);
			}
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemFromList($pm_list_name_or_id, $ps_item_idno) {
		
		if (isset(ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_item_idno])) {
			return ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_item_idno];
		}
	
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		
		if (isset(ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_item_idno])) {
			return ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_item_idno];
		}
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_list_items cli
			WHERE
				(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.idno = ?)
		", (int)$vn_list_id, (string)$ps_item_idno);
		
		if ($qr_res->nextRow()) {
			return  ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_item_idno] = ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_item_idno] = $qr_res->getRow();
		}
		return ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_item_idno] = ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_item_idno]  = null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemFromListByLabel($pm_list_name_or_id, $ps_label_name) {
		if (isset(ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_label_name])) {
			return ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_label_name];
		}
	
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		if (isset(ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_label_name])) {
			return ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_label_name];
		}
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_list_items cli
			INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id
			WHERE
				(cli.deleted = 0) AND (cli.list_id = ?) AND ((clil.name_singular = ?) OR (clil.name_plural = ?))
		", (int)$vn_list_id, (string)$ps_label_name, (string)$ps_label_name);
		
		if ($qr_res->nextRow()) {
			return ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_label_name] = ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_label_name] = $qr_res->getRow();
		}
		return ca_lists::$s_list_item_get_cache[$vn_list_id.'/'.$ps_label_name] = ca_lists::$s_list_item_get_cache[$pm_list_name_or_id.'/'.$ps_label_name]  = null;

	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemFromListForDisplay($pm_list_name_or_id, $ps_idno, $pb_return_plural=false) {
	
		if (isset(ca_lists::$s_list_item_display_cache[$ps_idno])) {
			$va_items = ca_lists::$s_list_item_display_cache[$ps_idno];
		} else {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
			
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT cli.item_id, clil.locale_id, clil.name_singular, clil.name_plural
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.idno = ?) AND (clil.is_preferred = 1)
			", (int)$vn_list_id, (string)$ps_idno);
			
			$va_items = array();
			while($qr_res->nextRow()) {
				 $va_items[$vn_item_id = $qr_res->get('item_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			ca_lists::$s_list_item_display_cache[$vn_item_id] = ca_lists::$s_list_item_display_cache[$vs_idno] = $va_items;
		}
		
		$va_tmp = caExtractValuesByUserLocale($va_items, null, null, array());
		$va_item = array_shift($va_tmp);
		
		return $va_item[$pb_return_plural ? 'name_plural' : 'name_singular'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemFromListForDisplayByItemID($pm_list_name_or_id, $pn_item_id, $pb_return_plural=false) {
	
		if (isset(ca_lists::$s_list_item_display_cache[$pn_item_id])) {
			$va_items = ca_lists::$s_list_item_display_cache[$pn_item_id];
		} else {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
			
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT cli.item_id, clil.locale_id, clil.name_singular, clil.name_plural
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.item_id = ?) AND (clil.is_preferred = 1)
			", (int)$vn_list_id, (int)$pn_item_id);
			
			$va_items = array();
			while($qr_res->nextRow()) {
				 $va_items[$qr_res->get('item_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			ca_lists::$s_list_item_display_cache[$pn_item_id] = $va_items;
		}
		
		$va_tmp = caExtractValuesByUserLocale($va_items, null, null, array());
		$va_item = array_shift($va_tmp);
		
		return $va_item[$pb_return_plural ? 'name_plural' : 'name_singular'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemForDisplayByItemID($pn_item_id, $pb_return_plural=false) {
		
		if (isset(ca_lists::$s_list_item_display_cache[$pn_item_id])) {
			$va_items = ca_lists::$s_list_item_display_cache[$pn_item_id];
		} else {
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT cli.item_id, clil.locale_id, clil.name_singular, clil.name_plural
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND (cli.item_id = ?) AND (clil.is_preferred = 1)
			", (int)$pn_item_id);
			
			$va_items = array();
			while($qr_res->nextRow()) {
				 $va_items[$qr_res->get('item_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			ca_lists::$s_list_item_display_cache[$pn_item_id] = $va_items;
		}
		
		$va_tmp = caExtractValuesByUserLocale($va_items, null, null, array());
		$va_item = array_shift($va_tmp);
		return $va_item[$pb_return_plural ? 'name_plural' : 'name_singular'];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemFromListByItemValue($pm_list_name_or_id, $pm_value) {
		
		if (isset(ca_lists::$s_list_item_value_display_cache[$pm_list_name_or_id.'/'.$pm_value])) {
			$va_items = ca_lists::$s_list_item_value_display_cache[$pm_list_name_or_id.'/'.$pm_value];
		} else {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
			
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT *
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.item_value = ?) AND (clil.is_preferred = 1)
			", (int)$vn_list_id, (string)$pm_value);
			
			$va_items = array();
			while($qr_res->nextRow()) {
				$pn_item_id = $qr_res->get('item_id');
				 $va_items[$pn_item_id][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			ca_lists::$s_list_item_display_cache[$pn_item_id] = ca_lists::$s_list_item_value_display_cache[$pm_list_name_or_id.'/'.$pm_value] =  $va_items;
		}
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemFromListForDisplayByItemValue($pm_list_name_or_id, $pm_value, $pb_return_plural=false) {
		if ($va_item = $this->getItemFromListByItemValue($pm_list_name_or_id, $pm_value)) {			
			$va_tmp = caExtractValuesByUserLocale($va_item, null, null, array());
			$va_item = array_shift($va_tmp);
			return $va_item[$pb_return_plural ? 'name_plural' : 'name_singular'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemIDFromListByItemValue($pm_list_name_or_id, $pm_value, $pb_return_plural=false) {
		if ($va_item = $this->getItemFromListByItemValue($pm_list_name_or_id, $pm_value)) {
			$va_tmp = caExtractValuesByUserLocale($va_item, null, null, array());
			$va_item = array_shift($va_tmp);
			return $va_item['item_id'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemIDFromListByLabel($pm_list_name_or_id, $ps_label_name) {
		if ($vn_list_id = $this->_getListID($pm_list_name_or_id)) {
			$t_item = new ca_list_items();
			$va_list_items = $t_item->getListItemIDsByName($vn_list_id, $ps_label_name);
			
			if (is_array($va_list_items) && (sizeof($va_list_items))) {
				return array_shift($va_list_items);
			}
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemIDFromList($pm_list_name_or_id, $ps_item_idno) {
		if ($va_list_item = $this->getItemFromList($pm_list_name_or_id, $ps_item_idno)) {
			return $va_list_item['item_id'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getRootItemIDForList($pm_list_name_or_id=null) {
		if ($pm_list_name_or_id) {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
		} else {
			$vn_list_id = $this->get('list_id');
		}
		if (!$vn_list_id) { return null; }
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT item_id
			FROM ca_list_items cli
			WHERE
				(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.parent_id IS NULL)
		", (int)$vn_list_id);
		
		$va_items = array();
		if($qr_res->nextRow()) {
			 return $qr_res->get('item_id');
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getItemFromListByItemID($pm_list_name_or_id, $pn_item_id) {
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query($x="
			SELECT *
			FROM ca_list_items cli
			WHERE
				(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.item_id = ?)
		", (int)$vn_list_id, (int)$pn_item_id);
		$va_items = array();
		while($qr_res->nextRow()) {
			 return $qr_res->getRow();
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function itemIsInList($pm_list_name_or_id, $ps_item_idno) {
		return $this->getItemFromList($pm_list_name_or_id, $ps_item_idno) ? true : false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function itemIDIsInList($pm_list_name_or_id, $pn_item_id) {
		return $this->getItemFromListByItemID($pm_list_name_or_id, $pn_item_id) ? true : false;
	}
	# ------------------------------------------------------
	/**
	 * Returns true if specified list item exists and has its' is_enabled flag set
	 * Returns null if item doesn't exist
	 */
	public function itemIsEnabled($pm_list_name_or_id, $pn_item_id) {
		if ($va_item = $this->getItemFromListByItemID($pm_list_name_or_id, $pn_item_id)) {
			return (intval($va_item['is_enabled'])) ? true : false;
		} 
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns item_id for default item in list. The list can be specified as either a list name or list_id.
	 * If no list is specified the currently loaded list is used.
	 *
	 * @param mixed $pm_list_name_or_id List code or list_id of list to return default item_id for. If omitted the currently loaded list will be used.
	 * @return int The item_id of the default element or null if no list was specified or loaded. If no default is set for the list in question the first item found is returned.
	 */
	public function getDefaultItemID($pm_list_name_or_id=null) {
		if($pm_list_name_or_id) {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
		} else {
			$vn_list_id = $this->getPrimaryKey();
		}
		if ((int)$vn_list_id < 1) {
			return null;
		}
		
		$t_list_item = new ca_list_items();
		if ($t_list_item->load(array('list_id' => (int)$vn_list_id, 'is_default' => 1))) {
			return $t_list_item->getPrimaryKey();
		}
		
		return array_shift($this->getItemsForList($vn_list_id, array('idsOnly' => true)));
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _getListID($pm_list_name_or_id) {
		return ca_lists::getListID($pm_list_name_or_id);
	}
	# ------------------------------------------------------
	/**
	 * Converts list specifier (code or list_id) into a list_id
	 *
	 * @param mixed $pm_list_name_or_id List code or list_id
	 * @return int list for the specified list, or null if the list does not exist
	 */
	static function getListID($pm_list_name_or_id) {
		if (ca_lists::$s_list_id_cache[$pm_list_name_or_id]) {
			return ca_lists::$s_list_id_cache[$pm_list_name_or_id];
		}
		if (is_numeric($pm_list_name_or_id)) {
			$vn_list_id = intval($pm_list_name_or_id);
		} else {
			$t_list = new ca_lists();
			if (!$t_list->load(array('list_code' => $pm_list_name_or_id))) {
				return null;
			}
			$vn_list_id = $t_list->getPrimaryKey();
		}
		
		return ca_lists::$s_list_id_cache[$pm_list_name_or_id] = $vn_list_id;
	}
	# ------------------------------------------------------
	/**
	 * Converts list specifier (code or list_id) into a list_id
	 *
	 * @param mixed $pm_list_name_or_id List code or list_id
	 * @return int list for the specified list, or null if the list does not exist
	 */
	static function getListCode($pm_list_name_or_id) {
		if (ca_lists::$s_list_code_cache[$pm_list_name_or_id]) {
			return ca_lists::$s_list_code_cache[$pm_list_name_or_id];
		}
		if (!is_numeric($pm_list_name_or_id)) {
			return $pm_list_name_or_id;
		} else {
			$t_list = new ca_lists();
			if (!$t_list->load((int)$pm_list_name_or_id)) {
				return null;
			}
			$vn_list_id = $t_list->getPrimaryKey();
			$vs_list_code = $t_list->get('list_code');
		}
		
		return ca_lists::$s_list_code_cache[$vn_list_id] = $vs_list_code;
	}
	# ------------------------------------------------------
	/**
	 * Returns HTML <select> element containing the specified list, or portion of the list.
	 *
	 * @param mixed $pm_list_name_or_id
	 * @param string $ps_name
	 * @param array $pa_attributes 
	 * @param array $pa_options Array of options. Valid options include:
	 * 	childrenOnlyForItemID = if set only items below item_id in the list item hierarchy are returned. Default (null) is to return all items in the list.
	 * 	directChildrenOnly = if set only items with item_id=childrenOnlyForItemID as parent in the list item hierarchy are returned. Default (null) is to return all items in the list.
	 *  nullOption = if set then a "null" (no value) option is available labeled with the value passed in this option
	 *  additionalOptions = an optional array of options that will be passed through to caHTMLSelect; keys are display labels and values are used as option values
	 *  value = if set, the <select> will have default selection set to the item whose *value* matches the option value. If none is set then the first item in the list will be selected
	 *  key = ca_list_item field to be used as value for the <select> element list; can be set to either item_id or item_value; default is item_id
	 *	width = the display width of the list in characters or pixels
	 *  limitToItemsWithID = An optional array of list item_ids. Item_ids not in the array will be omitted from the returned list.
	 *  omitItemsWithID = An optional array of list item_ids. Item_ids in the array will be omitted from the returned list.
	 *	
	 *	limitToItemsRelatedToCollections = an array of collection_ids or collection idno's; returned items will be restricted to those attached to the specified collections
	 *	limitToItemsRelatedToCollectionWithRelationshipTypes = array of collection type names or type_ids; returned items will be restricted to those attached to the specified collectionss with the specified relationship type
	 *	limitToListIDs = array of list_ids to restrict returned items to when using "limitToItemsRelatedToCollections"
	 * 
	 * @return string - HTML code for the <select> element; empty string if the list is empty
	 */
	static public function getListAsHTMLFormElement($pm_list_name_or_id, $ps_name, $pa_attributes=null, $pa_options=null) {
		$t_list = new ca_lists();
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!(isset($pa_options['limitToItemsRelatedToCollection']) && is_array($pa_options['limitToItemsRelatedToCollections']))) {
			$vn_list_id = $t_list->_getListID($pm_list_name_or_id);
			$t_list->load($vn_list_id);
		}
		$vn_root_id = (isset($pa_options['childrenOnlyForItemID']) && $pa_options['childrenOnlyForItemID']) ? $pa_options['childrenOnlyForItemID'] : null;
		
		$vs_render_as = isset($pa_options['render']) ? $pa_options['render'] : ''; 
		$vn_sort_type = $t_list->get('default_sort');
		if (($vs_render_as == 'yes_no_checkboxes') && ($vn_sort_type == __CA_LISTS_SORT_BY_LABEL__)) {
			$vn_sort_type = __CA_LISTS_SORT_BY_IDENTIFIER__;	// never allow sort-by-label when rendering as yes/no checkbox
		}
		
		if (!in_array($vs_render_as, array('lookup', 'horiz_hierbrowser', 'vert_hierbrowser'))) {
			if (isset($pa_options['limitToItemsRelatedToCollections']) && is_array($pa_options['limitToItemsRelatedToCollections'])) {
				$t_collection = new ca_collections();
				$va_collection_ids = array();
				foreach($pa_options['limitToItemsRelatedToCollections'] as $vn_collection_id) {
					if ($vn_collection_id && !is_numeric($vn_collection_id)) {
						if ($vn_collection_id = $t_collection->load(array('idno' => $vn_collection_id))) {
							$va_collection_ids[] = $vn_collection_id;
						}
					} else {
						if ($vn_collection_id) {
							$va_collection_ids[] = $vn_collection_id;
						}
					}
				}
				
				if (sizeof($va_collection_ids)) {
					$qr_collections = $t_list->makeSearchResult('ca_collections', $va_collection_ids, array('restrictToRelationshipTypes' => isset($pa_options['limitToItemsRelatedToCollectionWithRelationshipTypes']) ? $pa_options['limitToItemsRelatedToCollectionWithRelationshipTypes'] : null));
					
					$va_item_ids = array();
					while($qr_collections->nextHit()) {
						$va_list_items = $qr_collections->get('ca_list_items', array('returnAsArray' => true));
						foreach($va_list_items as $vn_rel_id => $va_list_item) {
							$va_item_ids[$vn_rel_id] = $va_list_item['item_id'];
						}
					}
					

					if ($va_limit_to_listIDs = ((isset($pa_options['limitToListIDs']) && is_array($pa_options['limitToListIDs'])) ? $pa_options['limitToListIDs'] : null)) {
						// for some reason the option comes back as array(0 => null) if no list is selected in UI
						// -> have to make sure to catch this case here
						if((sizeof($va_limit_to_listIDs)==1) && empty($va_limit_to_listIDs[0])){
							$va_limit_to_listIDs = null;
						}
					}


					if (is_array($va_limit_to_listIDs) && sizeof($va_item_ids)){
						// filter out items from tables we don't want
					
						$qr_list_items = $t_list->makeSearchResult("ca_list_items", array_values($va_item_ids));
						while($qr_list_items->nextHit()) {
							if (!in_array($qr_list_items->get('ca_list_items.list_id'), $va_limit_to_listIDs)) {
								if (is_array($va_k = array_keys($va_item_ids, $qr_list_items->get('ca_list_items.item_id')))) {
									foreach($va_k as $vs_k) {
										unset($va_list_items[$vs_k]);
									}
								}
							}
						}
					}
				}
			} else {
				$va_list_items = $t_list->getItemsForList($pm_list_name_or_id, array_merge($pa_options, array('returnHierarchyLevels' => true, 'item_id' => $vn_root_id, 'extractValuesByUserLocale' => true, 'sort' => $vn_sort_type)));
			}
		}
		
		if (!is_array($va_list_items)) { $va_list_items = array(); }
		
		$va_options = array();
		$va_disabled_options = array();
		
		if (!isset($pa_options['value'])) { $pa_options['value'] = null; }
		if (!isset($pa_options['key'])) { $pa_options['key'] = 'item_id'; }
		if (!in_array($pa_options['key'], array('item_id', 'item_value'))) {
			$pa_options['key'] = 'item_id';
		}
		
		if (!isset($pa_options['limitToItemsWithID']) || !is_array($pa_options['limitToItemsWithID']) || !sizeof($pa_options['limitToItemsWithID'])) { $pa_options['limitToItemsWithID'] = null; }
		if (!isset($pa_options['omitItemsWithID']) || !is_array($pa_options['omitItemsWithID']) || !sizeof($pa_options['omitItemsWithID'])) { $pa_options['omitItemsWithID'] = null; }
	
		if ((isset($pa_options['nullOption']) && $pa_options['nullOption']) && ($vs_render_as != 'checklist')) {
			$va_options[''] = $pa_options['nullOption'];
		}
		
		$va_colors = array();
		$vn_default_val = null;
		foreach($va_list_items as $vn_item_id => $va_item) {
			if (is_array($pa_options['limitToItemsWithID']) && !in_array($vn_item_id, $pa_options['limitToItemsWithID'])) { continue; }
			if (is_array($pa_options['omitItemsWithID']) && in_array($vn_item_id, $pa_options['omitItemsWithID'])) { continue; }
			
			$va_options[$va_item[$pa_options['key']]] = str_repeat('&nbsp;', intval($va_item['LEVEL']) * 3).' '.$va_item['name_singular'];
			if (!$va_item['is_enabled']) { $va_disabled_options[$va_item[$pa_options['key']]] = true; }
			$va_colors[$vn_item_id] = $va_item['color'];
			
			if ($va_item['is_default']) { $vn_default_val = $va_item[$pa_options['key']]; }		// get default value
			
			if ($va_item['is_default'] && !isset($pa_options['nullOption'])) {		// set default if needed, but only if there's not a null option set
				if (
					(!is_array($pa_options['value']) && (!isset($pa_options['value']) || !strlen($pa_options['value'])))
				) { 
					$pa_options['value'] = $vn_default_val; 
				} else {
					if (
						(is_array($pa_options['value']) && !sizeof($pa_options['value']))
					) {
						$pa_options['value'] = array(0 => $vn_default_val); 
					}
				}
			}
		}
		
		if (isset($pa_options['additionalOptions']) && is_array($pa_options['additionalOptions'])) {
			$va_options = array_merge($va_options, array_flip($pa_options['additionalOptions']));
		}
		
		$pa_options['disabledOptions'] = $va_disabled_options;
		
		switch($vs_render_as) {
			case 'radio_buttons':
				if (!sizeof($va_options)) { return ''; }	// return empty string if list has no values
				$vn_c = 0; $vn_i = 0;
				$vs_buf = "<table>\n";
				foreach($va_options as $vm_value => $vs_label) {
					if ($vn_c == 0) { $vs_buf .= "<tr>"; }
					
					$va_attributes = array('value' => $vm_value);
					if (isset($va_disabled_options[$vm_value]) && $va_disabled_options[$vm_value]) {
						$va_attributes['disabled'] = 1;
					}
					$va_attributes['value'] = $vm_value;
					$va_attributes['id'] = $ps_name.'_'.$vn_i;
					
					if ($pa_options['value'] == $vm_value) {
						$va_attributes['checked'] = '1';
					}
					if (isset($pa_options['readonly']) && ($pa_options['readonly'])) {
						$va_attributes['disabled'] = 1;
					}
					$vs_buf .= "<td>".caHTMLRadioButtonInput($ps_name, $va_attributes, $pa_options)." {$vs_label}</td>";
					$vn_c++;
					
					if ($vn_c >= $pa_options['maxColumns']) {
						$vn_c = 0;
						$vs_buf .= "</tr>\n";
					}
					$vn_i++;
				}
				if ($vn_c != 0) {
					$vs_buf .= "</tr>\n";
				}
				$vs_buf .= "</table>";
				return $vs_buf;
				break;
			case 'yes_no_checkboxes':
				if (!sizeof($va_options)) { return ''; }	// return empty string if list has no values
				$vn_c = 0;
				$vb_is_checked = false;
				if (!$pa_options['value']) { $pa_options['value'] = (string)$vn_default_val; }
				foreach($va_options as $vm_value => $vs_label) {
					if (strlen($vm_value) == 0) { continue; }	// don't count null values when calculating the first value for the yes/no
					switch($vn_c) {
						case 0:
							if ($pa_options['value'] === (string)$vm_value) {
								$vb_is_checked = true;
							}
							$pa_attributes['value'] = $pa_options['value'] = $vm_value;
							$pa_options['label'] = $vs_label;
							break;
						case 1:
							$pa_options['returnValueIfUnchecked'] = $vm_value;
							break;
						default:
							// exit
							break(2);
					}
					$vn_c++;
				}
				
				if ($vb_is_checked) {
					$pa_attributes['checked'] = 1;
				}
				if (isset($pa_options['readonly']) && ($pa_options['readonly'])) {
					$pa_attributes['disabled'] = 1;
				}
				return caHTMLCheckboxInput($ps_name, $pa_attributes, $pa_options);
				break;
			case 'checklist':
				if (!sizeof($va_options)) { return ''; }	// return empty string if list has no values
				$vn_c = 0;
				$vs_buf = "<table>\n";
				foreach($va_options as $vm_value => $vs_label) {
					if ($vn_c == 0) { $vs_buf .= "<tr>"; }
					
					$va_attributes = array('value' => $vm_value);
					if (isset($va_disabled_options[$vm_value]) && $va_disabled_options[$vm_value]) {
						$va_attributes['disabled'] = 1;
					}
					if (isset($pa_options['readonly']) && ($pa_options['readonly'])) {
						$va_attributes['disabled'] = 1;
					}
					if (is_array($pa_options['value']) && in_array($vm_value, $pa_options['value']) ) { $va_attributes['checked'] = '1'; }
					
					$vs_buf .= "<td>".caHTMLCheckboxInput($ps_name.'_'.$vm_value, $va_attributes, $pa_options)." {$vs_label}</td>";
					
					$vn_c++;
					
					if ($vn_c >= $pa_options['maxColumns']) {
						$vn_c = 0;
						$vs_buf .= "</tr>\n";
					}
				}
				if ($vn_c != 0) {
					$vs_buf .= "</tr>\n";
				}
				$vs_buf .= "</table>";
				return $vs_buf;
				break;
			case 'lookup':
				$vs_buf =
 				caHTMLTextInput(
 					$ps_name.'_autocomplete', 
					array(
						'width' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width']: 300, 
						'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1, 
						'value' => "{".$pa_options['element_id']."_label}", 
						'maxlength' => 512,
						'id' => $ps_name."_autocomplete",
						'class' => 'lookupBg'
					)
				).
				caHTMLHiddenInput(
					$ps_name,
					array(
						'value' => "{".$pa_options['element_id']."}", 
						'id' => $ps_name
					)
				);
				
				if ($pa_options['request']) {
					$vs_url = caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'Get', array('list' => ca_lists::getListCode($vn_list_id), 'noInline' => 1, 'noSymbols' => 1, 'max' => 100));
				} else {
					// hardcoded default for testing.
					$vs_url = '/index.php/lookup/ListItem/Get';	
				}
				
				$vs_buf .= '</div>';
				$vs_buf .= "
					<script type='text/javascript'>
						jQuery(document).ready(function() {
							jQuery('#{$ps_name}_autocomplete').autocomplete({
									source: '{$vs_url}', minLength: 3, delay: 800, html: true,
									select: function(event, ui) {
										
										if (parseInt(ui.item.id) > 0) {
											jQuery('#{$ps_name}').val(ui.item.id);
										} else {
											jQuery('#{$ps_name}_autocomplete').val('');
											jQuery('#{$ps_name}').val('');
											event.preventDefault();
										}
									}
								}
							);
						});
					</script>
				";
				return $vs_buf;
				break;
			case 'horiz_hierbrowser':
			case 'horiz_hierbrowser_with_search':
			case 'vert_hierbrowser':
				$va_width = caParseFormElementDimension($pa_options['width'] ? $pa_options['width'] : $pa_options['width']);
				if (($va_width['type'] != 'pixels') && ($va_width['dimension'] < 250)) { $va_width['dimension'] = 500; }
				$vn_width = $va_width['dimension'].'px';
				$va_height = caParseFormElementDimension($pa_options['height']);
				if (($va_height['type'] != 'pixels') && ($va_height['dimension'] < 100)) { $va_height['dimension'] = 200; }
				$vn_height = $va_height['dimension'].'px';
				
				$t_root_item = new ca_list_items();
				$t_root_item->load(array('list_id' => $vn_list_id, 'parent_id' => null));
				
				JavascriptLoadManager::register("hierBrowser");
				
				$vs_buf = "<div style='width: {$vn_width}; height: {$vn_height};'><div id='{$ps_name}_hierarchyBrowser{n}' style='width: 100%; height: 100%;' class='".(($vs_render_as == 'vert_hierbrowser') ? 'hierarchyBrowserVertical' : 'hierarchyBrowser')."'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->	</div>";
				
				$vs_buf .= "	<script type='text/javascript'>
		jQuery(document).ready(function() { 
			var oHierBrowser = caUI.initHierBrowser('{$ps_name}_hierarchyBrowser{n}', {
				uiStyle: '".(($vs_render_as == 'vert_hierbrowser') ? 'vertical' : 'horizontal')."',
				levelDataUrl: '".caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'GetHierarchyLevel', array('noSymbols' => 1))."',
				initDataUrl: '".caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'GetHierarchyAncestorList')."',
				
				selectOnLoad : true,
				browserWidth: ".(int)$va_width['dimension'].",
				
				className: '".(($vs_render_as == 'vert_hierbrowser') ? 'hierarchyBrowserLevelVertical' : 'hierarchyBrowserLevel')."',
				classNameContainer: '".(($vs_render_as == 'vert_hierbrowser') ? 'hierarchyBrowserContainerVertical' : 'hierarchyBrowserContainer')."',
				
				editButtonIcon: '<img src=\"".$pa_options['request']->getThemeUrlPath()."/graphics/buttons/arrow_grey_right.gif\" border=\"0\" title=\"Edit\"/>',
				
				initItemID: '{".$pa_options['element_id']."}',
				defaultItemID: '".$t_list->getDefaultItemID()."',
				useAsRootID: '".$t_root_item->getPrimaryKey()."',
				indicatorUrl: '".$pa_options['request']->getThemeUrlPath()."/graphics/icons/indicator.gif',
				
				currentSelectionDisplayID: '{$ps_name}_browseCurrentSelectionText{n}',
				onSelection: function(item_id, parent_id, name, display) {
					jQuery('#{$ps_name}').val(item_id);
				}
			});";
			
		if ($vs_render_as == 'horiz_hierbrowser_with_search') {
			$vs_buf .= "jQuery('#{$ps_name}_hierarchyBrowserSearch{n}').autocomplete(
					{
						source: '".caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'Get', array('list' => ca_lists::getListCode($vn_list_id), 'noSymbols' => 1))."', 
						minLength: 3, delay: 800,
						select: function(event, ui) {
							oHierBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
						}
					}
				);";
		}
		$vs_buf .= "});
	</script>";
	
				if ($vs_render_as == 'horiz_hierbrowser_with_search') {
					$vs_buf .= "<div class='hierarchyBrowserSearchBar'>"._t('Search').": <input type='text' id='{$ps_name}_hierarchyBrowserSearch{n}' class='hierarchyBrowserSearchBar' name='search' value='' size='20'/></div>";
				}
				
				if ($vs_render_as != 'vert_hierbrowser') {
					$vs_buf .= "<div id='{$ps_name}_browseCurrentSelection{n}' class='hierarchyBrowserCurrentSelection'>"._t("Current selection").": <span id='{$ps_name}_browseCurrentSelectionText{n}' class='hierarchyBrowserCurrentSelectionText'>?</span></div>";
				}
				$vs_buf .= caHTMLHiddenInput(
					$ps_name,
					array(
						'value' => "{".$pa_options['element_id']."}", 
						'id' => $ps_name
					)
				);
				return $vs_buf;
				break;
			case 'options':
				return $va_options;
				break;
			default:
				if (!sizeof($va_options)) { return ''; }	// return empty string if list has no values
				if (isset($pa_options['readonly']) && ($pa_options['readonly'])) {
					$pa_attributes['disabled'] = 1;
				}
				return caHTMLSelect($ps_name, $va_options, $pa_attributes, array_merge($pa_options, array('contentArrayUsesKeysForValues' => true, 'colors' => $va_colors, 'height' => null)));
				break;
		}
	}
	# ------------------------------------------------------
	# Vocabulary functions
	# ------------------------------------------------------
	/** 
	 * Returns all items in specified list in an hierarchical structure
	 */
	public function getListItemsAsHierarchy($pm_list_name_or_id=null, $pa_options=null) {
		if (!($vn_item_id = $this->getRootListItemID($pm_list_name_or_id))) { return null; }
		$t_items = new ca_list_items($vn_item_id);
		return $t_items->getHierarchyAsList(null, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Returns item_id of root node for list
	 */
	public function getRootListItemID($pm_list_name_or_id=null) {
		if($pm_list_name_or_id) {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
		} else {
			$vn_list_id = $this->getPrimaryKey();
		}
		if (!$vn_list_id) { return null; }
		
		$t_items = new ca_list_items();
		$t_items->load(array('list_id' => $vn_list_id, 'parent_id' => null));
		
		return $t_items->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Returns a flat list of all list items in the specified list referenced by items in the specified table
	 * (and optionally a search on that table)
	 */
	public function getReferencedItems($pm_table_num_or_name, $pm_list_name_or_id=null, $pa_reference_limit_ids=null, $ps_ref_table_fieldname=null, $pn_access=null) {
		if (is_numeric($pm_table_num_or_name)) {
			$vs_table_name = $this->getAppDataModel()->getTableName($pm_table_num_or_name);
		} else {
			$vs_table_name = $pm_table_num_or_name;
		}
		
		if (!($t_ref_table = $this->getAppDatamodel()->getInstanceByTableName($vs_table_name, true))) {
			return null;
		}
		
		
		if($pm_list_name_or_id) {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
		} else {
			$vn_list_id = $this->getPrimaryKey();
		}
		if (!$vn_list_id || !$vs_table_name) { return null; }
		
		$o_db = $this->getDb();
		
		if (!$ps_ref_table_fieldname) {
			$va_path = $this->getAppDatamodel()->getPath('ca_list_items', $vs_table_name);
			array_shift($va_path); // remove 'ca_list_items' from path
			
			$vs_last_table = 'ca_list_items';
			$va_joins = array();
			foreach($va_path as $vs_rel_table_name => $vn_rel_table_num) {
				$va_rels = $this->getAppDatamodel()->getRelationships($vs_last_table, $vs_rel_table_name);
				$va_rel = $va_rels[$vs_last_table][$vs_rel_table_name][0];
				
				
				$va_joins[] = "INNER JOIN {$vs_rel_table_name} ON {$vs_last_table}.".$va_rel[0]." = {$vs_rel_table_name}.".$va_rel[1];
				
				$vs_last_table = $vs_rel_table_name;
			}
		} else {
			$va_joins[] = "INNER JOIN {$vs_table_name} ON {$vs_table_name}.{$ps_ref_table_fieldname} = ca_list_items.item_id";
		}
		
		$va_sql_wheres = array();
		if (is_array($pa_reference_limit_ids) && sizeof($pa_reference_limit_ids)) {
			$va_sql_wheres[] = "({$vs_table_name}.".$t_ref_table->primaryKey()." IN (".join(',', $pa_reference_limit_ids)."))";
		}
		
		if (!is_null($pn_access)) {
			$va_sql_wheres[] = "({$vs_table_name}.access = ".intval($pn_access).")";
		}
		
		// get counts
		$vs_sql = "
			SELECT ca_list_items.item_id, count(*) cnt
			FROM ca_list_items
			".join("\n", $va_joins)."
			WHERE
				(ca_list_items.deleted = 0) AND (ca_list_items.list_id = ?)
				".(sizeof($va_sql_wheres) ? " AND ".join(' AND ', $va_sql_wheres) : "")."
			GROUP BY
				ca_list_items.item_id
		";
		$qr_items = $o_db->query($vs_sql, (int)$vn_list_id);
		
		$va_item_counts = array();
		while($qr_items->nextRow()) {
			$va_item_counts[$qr_items->get('item_id')] = $qr_items->get('cnt');
		}
		
		$vs_sql = "
			SELECT ca_list_items.item_id, ca_list_item_labels.*
			FROM ca_list_items
			INNER JOIN ca_list_item_labels ON ca_list_item_labels.item_id = ca_list_items.item_id
			".join("\n", $va_joins)."
			WHERE
				(ca_list_items.deleted = 0) AND (ca_list_items.list_id = ?) AND (ca_list_item_labels.is_preferred = 1)
				".(sizeof($va_sql_wheres) ? " AND ".join(' AND ', $va_sql_wheres) : "")."
				
			GROUP BY
				ca_list_item_labels.label_id
		";
		
		$qr_items = $o_db->query($vs_sql, (int)$vn_list_id);
		
		$va_items = array();
		while($qr_items->nextRow()) {
			$vn_item_id = $qr_items->get('item_id');
			$va_items[$vn_item_id][$qr_items->get('locale_id')] = array_merge($qr_items->getRow(), array('cnt' => $va_item_counts[$vn_item_id]));
		}
		
		return caExtractValuesByUserLocale($va_items);
	}
	# ------------------------------------------------------
	/**
	 * Returns list containing name and list_ids of all available lists. Names are indexed by locale_id - names for 
	 * all locales are returned.
	 *
	 * @return array - List of available lists, indexed by list_id and locale_id. Array values are arrays with list information including name, locale and list_id
	 */
	public function getListOfLists() {
		$o_db = $this->getDb();
		
		$qr_lists = $o_db->query("
			SELECT cl.*, cll.name, cll.locale_id
			FROM ca_lists cl
			LEFT JOIN ca_list_labels cll ON cl.list_id = cll.list_id
			ORDER BY
				cll.list_id
		");
		$va_lists = array();
		while($qr_lists->nextRow()) {
			$va_tmp =  $qr_lists->getRow();
			
			if (!$va_tmp['name']) { $va_tmp['name'] = $va_tmp['list_code']; }				// if there's no label then use the list_code as its' name
			$va_lists[$qr_lists->get('list_id')][$qr_lists->get('locale_id')] = $va_tmp;
		}
		
		return $va_lists;
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Overrides BundlableLabelableBaseModelWithAttributes:isSaveable to implement system list access restrictions
	 */
	public function isSaveable($po_request, $ps_bundle_name=null) {
		if(parent::isSaveable($po_request, $ps_bundle_name)){ // user could save this list
			if($this->getPrimaryKey()){
				if($this->get('is_system_list')){
					if(!$po_request->user->canDoAction('can_edit_system_lists')){
						return false;
					}
				}
			}

			return true;
		} else {
			// BundlableLabelableBaseModelWithAttributes:isSaveable returned false
			// => user can't edit this list at all, no matter how is_system_list is set
			return false;
		}
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Overrides BundlableLabelableBaseModelWithAttributes:isDeletable to implement system list access restrictions
	 */
	public function isDeletable($po_request) {

		if(parent::isDeletable($po_request)){ // user could delete this list
			if($this->getPrimaryKey()){
				if($this->get('is_system_list')){
					if(!$po_request->user->canDoAction('can_delete_system_lists')){
						return false;
					}
				}
			}

			return true;
		} else {
			// BundlableLabelableBaseModelWithAttributes:isDeletable returned false
			// => user can't delete this list at all, no matter how is_system_list is set
			return false;
		}
	}
	# ---------------------------------------------------------------------------------------------
	/**
	 * Converts the given list of list item idnos or item_ids into an expanded list of numeric item_ids. Processing
	 * includes expansion of items to include sub-items and conversion of any idnos to item_ids.
	 *
	 * @param mixed $pm_table_name_or_num Table name or number to which types apply
	 * @param array $pa_types List of item idnos and/or item_ids that are the basis of the list
	 * @param array $pa_options Array of options:
	 * 		dont_include_sub_items = if set, returned list is not expanded to include sub-items
	 *		dontIncludeSubItems = synonym for dont_include_sub_items
	 *
	 * @return array List of numeric item_ids
	 */
	static public function getItemIDsFromList($pm_list_name_or_id, $pa_idnos, $pa_options=null) {
		if(isset($pa_options['dontIncludeSubItems']) && (!isset($pa_options['dont_include_sub_items']) || !$pa_options['dont_include_sub_items'])) { $pa_options['dont_include_sub_items'] = $pa_options['dontIncludeSubItems']; }
	 	
		if (isset($pa_options['dont_include_sub_items']) && $pa_options['dont_include_sub_items']) {
			$pa_options['noChildren'] = true;
		}
		$t_list = new ca_lists();
		$t_item = new ca_list_items();
		$va_item_ids = array();
		foreach($pa_idnos as $vs_idno) {
			$vn_item_id = null;
			if (is_numeric($vs_idno)) { 
				$vn_item_id = (int)$vs_idno; 
			} else {
				$vn_item_id = (int)$t_list->getItemIDFromList($pm_list_name_or_id, $vs_idno);
			}
			
			if ($vn_item_id && !(isset($pa_options['noChildren']) || $pa_options['noChildren'])) {
				if ($qr_children = $t_item->getHierarchy($vn_item_id, array())) {
					while($qr_children->nextRow()) {
						$va_item_ids[$qr_children->get('item_id')] = true;
					}
				}
			} else {
				if ($vn_item_id) {
					$va_item_ids[$vn_item_id] = true;
				}
			}
		}
		return array_keys($va_item_ids);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getItemID($pm_list_name_or_id, $ps_idno, $pa_options=null) {
		if ((!isset($pa_options['noCache']) || !isset($pa_options['noCache'])) && isset(ca_lists::$s_item_id_cache[$pm_list_name_or_id][$ps_idno])) {
			return ca_lists::$s_item_id_cache[$pm_list_name_or_id][$ps_idno];
		}
		$vn_item_id = null;
		if ($vn_list_id = ca_lists::getListID($pm_list_name_or_id)) {
			$o_db = new Db();
			$qr_res = $o_db->query("SELECT item_id FROM ca_list_items WHERE deleted = 0 AND list_id = ? AND idno = ?", (int)$vn_list_id, (string)$ps_idno);
			
			if ($qr_res->nextRow()) {
				$vn_item_id = (int)$qr_res->get('item_id');
			}
			ca_lists::$s_item_id_cache[$vn_list_id][$ps_idno] = $vn_item_id;
		}
		ca_lists::$s_item_id_cache[$pm_list_name_or_id][$ps_idno] = $vn_item_id;
		return $vn_item_id;
	}
	# ------------------------------------------------------
}
?>