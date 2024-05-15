<?php
/** ---------------------------------------------------------------------
 * app/models/ca_lists.php : table access class for table ca_lists
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BundlableLabelableBaseModelWithAttributes.php');
require_once(__CA_APP_DIR__.'/models/ca_list_items.php');
require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/listHelpers.php');

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
		),
		'deleted' => array(
 				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
 				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
 				'IS_NULL' => false, 
 				'DEFAULT' => 0,
 				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if list item is deleted or not.'),
 				'DONT_INCLUDE_IN_SEARCH_FORM' => true
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
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'list_code';		// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;		    // name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field
	
	
	# ------------------------------------------------------
	# ACL
	# ------------------------------------------------------
	protected $SUPPORTS_ACL = true;
	
	static $s_list_id_cache = array();
	static $s_list_code_cache = array();
	static $s_list_item_display_cache = array();			// cache for results of getItemFromListForDisplayByItemID()
	static $s_list_item_value_display_cache = array();		// cache for results of getItemFromListForDisplayByItemValue()
	static $s_list_item_get_cache = array();				// cache for results of getItemFromList()
	static $s_item_id_cache = array();						// cache for ca_lists::getItemID()
	static $s_item_id_to_code_cache = array();				// cache for ca_lists::itemIDsToIDNOs()
	static $s_item_id_to_value_cache = array();				// cache for ca_lists::itemIDsToItemValues()
	static $s_code_to_item_id_cache = array();				// cache for ca_lists::IDNOsToItemIDs()
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
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
			if ($this->inTransaction()) { $t_item_root->setTransaction($this->getTransaction()); }
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
			
			ExternalCache::flush('listItems');
		}
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function update($pa_options=null) {
		if ($vn_rc = parent::update($pa_options)) {
			ExternalCache::flush('listItems');
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override delete() to scramble the list_code before we soft-delete. This is useful
	 * because the database field has a unique key that really enforces uniqueneness
	 * and we might wanna reuse a code of a set we previously deleted.
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		if($vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list)) {
			if(!caGetOption('hard', $pa_options, false)) { // only applies if we don't hard-delete
				$vb_we_set_transaction = false;
				if (!$this->inTransaction()) {
					$o_t = new Transaction($this->getDb());
					$this->setTransaction($o_t);
					$vb_we_set_transaction = true;
				}

				$this->set('list_code', $this->get('list_code') . '_' . time());
				$this->update(array('force' => true));

				if ($vb_we_set_transaction) { $this->removeTransaction(true); }
			}
			
			ExternalCache::flush('listItems');
		}

		return $vn_rc;
	}
	# ------------------------------------------------------
	# List maintenance
	# ------------------------------------------------------
	/**
	 * Add an item to the currently loaded list
	 *
	 * @param string $ps_value
	 * @param bool $pb_is_enabled
	 * @param bool $pb_is_default
	 * @param int $pn_parent_id
	 * @param int $pn_type_id
	 * @param string $ps_idno
	 * @param string $ps_validation_format
	 * @param int $pn_status
	 * @param int $pn_access
	 * @param int $pn_rank 
	 * @param string $ps_color Color of list item, in hex without leading "#" (ex. FF0000). [Default is null]
	 *
	 * @return bool|ca_list_items
	 */
	public function addItem($ps_value, $pb_is_enabled=true, $pb_is_default=false, $pn_parent_id=null, $pn_type_id=null, $ps_idno=null, $ps_validation_format='', $pn_status=0, $pn_access=0, $pn_rank=null, $ps_color=null) {
		if(!($vn_list_id = $this->getPrimaryKey())) { return null; }
		
		$t_item = new ca_list_items();
		$t_item->setMode(ACCESS_WRITE);
		
		if ($this->inTransaction()) { $t_item->setTransaction($this->getTransaction()); }
		
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
		$t_item->set('color', $ps_color);
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
	 * Edit an existing list item
	 *
	 * @param int $pn_item_id
	 * @param string $ps_value
	 * @param bool $pb_is_enabled
	 * @param bool $pb_is_default
	 * @param int $pn_parent_id
	 * @param int $pn_type_id
	 * @param string $ps_idno
	 * @param string $ps_validation_format
	 * @param int $pn_status
	 * @param int $pn_access
	 * @param int $pn_rank
	 * @param string $ps_color Color of list item, in hex without leading "#" (ex. FF0000). [Default is null]
	 *
	 * @return bool|ca_list_items
	 */
	public function editItem($pn_item_id, $ps_value, $pb_is_enabled=true, $pb_is_default=false, $pn_parent_id=null, $ps_idno=null, $ps_validation_format='', $pn_status=0, $pn_access=0, $pn_rank=null, $ps_color=null) {
		if(!($vn_list_id = $this->getPrimaryKey())) { return false; }

		$t_item = new ca_list_items($pn_item_id);
		if(!$t_item->getPrimaryKey()) { return false; }
		if($t_item->get('list_id') != $this->getPrimaryKey()) { return false; } // don't allow editing items in other lists

		$t_item->setMode(ACCESS_WRITE);
		if ($this->inTransaction()) { $t_item->setTransaction($this->getTransaction()); }

		if(is_null($pn_parent_id)) { $pn_parent_id = $this->getRootItemIDForList($this->getPrimaryKey()); }

		$t_item->set('item_value', $ps_value);
		$t_item->set('is_enabled', $pb_is_enabled ? 1 : 0);
		$t_item->set('is_default', $pb_is_default ? 1 : 0);
		$t_item->set('parent_id', $pn_parent_id);
		$t_item->set('idno', $ps_idno);
		$t_item->set('validation_format', $ps_validation_format);
		$t_item->set('status', $pn_status);
		$t_item->set('access', $pn_access);
		$t_item->set('color', $ps_color);
		if (!is_null($pn_rank)) { $t_item->set('rank', $pn_rank); }

		$t_item->update();

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
	 *		returnHierarchyLevels =		if true list is returned with 'LEVEL' field set to hierarchical level of item, and items are returned in order such that if you loop through the returned list and indent each item according to its level you get a nicely formatted hierarchical display. Default is false.
	 * 		extractValuesByUserLocale = if true then values are processed to be appropriate for current user locale; default is false:  return values for all locales
	 *		directChildrenOnly =	 	if true, only children immediately below the specified item are returned; [default is false]
	 * 		includeSelf =	if true, the specified item is included in the returned set of items; [default is false]
	 *		type_id = 		optional list item type to limit returned items by; default is to not limit by type (eg. type_id = null)
	 *		item_id =		optional item_id to use as root of hierarchy for returned items; if this is not set (the default) then all items in the list are returned
	 *		sort =			if set to a __CA_LISTS_SORT_BY_*__ constant, will force the list to be sorted by that criteria overriding the sort order set in the ca_lists.default_sort field
	 *		idsOnly = 		if true, only the primary key id values of the list items are returned
	 *		idnosOnly = 	if true, only idno values of list items are returned
	 *		enabledOnly =	return only enabled list items [default=false]
	 *		omitRoot =		don't include root node [Default=false]
	 *		labelsOnly = 	if true only labels in the current locale are returns in an array key'ed on item_id
	 *		start = 		offset to start returning records from [default=0; no offset]
	 *		limit = 		maximum number of records to return [default=null; no limit]
	 * 		dontCache =		don't cache
	 *		checkAccess =   Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *		filterExpression = expression to filter returned items on. Eg. ^ca_list_items.preferred_labels.name_plural ~= /Puppy/i [Default is null].
	 *
	 * @return array List of items indexed first on item_id and then on locale_id of label
	 */
	public function getItemsForList($pm_list_name_or_id, $pa_options=null) {
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		if (!is_array($pa_options)) { $pa_options = array(); }
		if (!isset($pa_options['returnHierarchyLevels'])) { $pa_options['returnHierarchyLevels'] = false; }
		if ((isset($pa_options['directChildrenOnly']) && $pa_options['directChildrenOnly'])) { $pa_options['returnHierarchyLevels'] = false; }
	
		$va_seen_locales = $va_items = [];
		
		$pn_start = caGetOption('start', $pa_options, 0);
		$pn_limit = caGetOption('limit', $pa_options, null);
		$pb_dont_cache = caGetOption('dontCache', $pa_options, false);
		
		$pb_omit_root = caGetOption('omitRoot', $pa_options, false);
		$vb_enabled_only = caGetOption('enabledOnly', $pa_options, false);
		
		$pa_check_access = caGetOption('checkAccess', $pa_options, null); 
		if(!is_array($pa_check_access) && $pa_check_access) { $pa_check_access = [$pa_check_access]; }
		if(is_array($pa_check_access)) { $pa_check_access = array_map('intval', $pa_check_access); }
	
		$vb_labels_only = false;
		if (isset($pa_options['labelsOnly']) && $pa_options['labelsOnly']) {
			$pa_options['extractValuesByUserLocale'] = true;
			$pa_options['returnHierarchyLevels'] = false;
			
			$vb_labels_only = true;
		}
	
		$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_options, array('list_id' => $vn_list_id)));

		if (!$pb_dont_cache && ExternalCache::contains($vs_cache_key, 'listItems')) { 
			return ExternalCache::fetch($vs_cache_key, 'listItems');
		}
		
		$t_list = new ca_lists($vn_list_id);
		$pn_type_id = isset($pa_options['type_id']) ? (int)$pa_options['type_id'] : null;
		$pn_sort = isset($pa_options['sort']) ? (int)$pa_options['sort'] : $t_list->get('default_sort');
		
		if (!($pn_item_id = isset($pa_options['item_id']) ? (int)$pa_options['item_id'] : null)) {
			$pn_item_id = $t_list->getRootListItemID($vn_list_id);
		}
		
		$t_list_item = new ca_list_items($pn_item_id);
		if (!$t_list_item->getPrimaryKey() || ($t_list_item->get('list_id') != $vn_list_id)) { 
			ExternalCache::save($vs_cache_key, null, 'listItems');
			return null; 
		}

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
					$vs_order_by = 'cli.`rank`';
					break;
				case __CA_LISTS_SORT_BY_VALUE__:	// by value
					$vs_order_by = 'cli.item_value';
					break;
				case __CA_LISTS_SORT_BY_IDENTIFIER__:	// by identifier
					$vs_order_by = 'cli.idno_sort';
					break;
			}
			
			$va_params = [(int)$vn_list_id];
			
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
			
			$vs_limit_sql = '';
			if ($pn_limit > 0) {
				$vs_limit_sql = ($pn_start > 0) ? "LIMIT {$pn_start}, {$pn_limit}" : "LIMIT {$pn_limit}";
			} 
			
			$vs_access_sql = '';
			if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
				$vs_access_sql = " AND cli.access in (?)";
				$va_params[] = $pa_check_access;
			}
			
			$vs_sql = "
				SELECT clil.*, cli.*
				FROM ca_list_items cli
				LEFT JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND ((clil.is_preferred = 1) OR (clil.is_preferred IS NULL)) AND (cli.list_id = ?) {$vs_type_sql} {$vs_direct_children_sql} {$vs_hier_sql} {$vs_enabled_sql} {$vs_access_sql}
				{$vs_order_by}
				{$vs_limit_sql}
			";
			//print $vs_sql;
			$qr_res = $o_db->query($vs_sql, $va_params);
			
			if ($pn_start > 0) { $qr_res->seek($pn_start); }
			$vn_c = 0;
			while($qr_res->nextRow()) {
				if ($pb_omit_root && !$qr_res->get('parent_id')) { continue; }
				$vn_item_id = $qr_res->get('item_id');
				$vn_c++;
				if (($pn_limit > 0) && ($vn_c > $pn_limit)) { break; }
				if ((isset($pa_options['idsOnly']) && $pa_options['idsOnly'])) {
					$va_items[] = $vn_item_id;
					continue;
				}
				if ((isset($pa_options['idnosOnly']) && $pa_options['idnosOnly'])) {
					$va_items[] = $qr_res->get('idno');
					continue;
				}
				if ((!isset($pa_options['includeSelf']) || !$pa_options['includeSelf']) && ($vn_item_id == $pn_item_id)) { continue; }
				if ((isset($pa_options['directChildrenOnly']) && $pa_options['directChildrenOnly']) && ($qr_res->get('parent_id') != $pn_item_id)) { continue; }
				
				if($filter_expr && ($t_item = ca_list_items::find($vn_item_id)) && !caEvaluateExpression($t_item, $filter_expr)) {
					continue;
				}
				
				$va_items[$vn_item_id][$vn_locale_id = $qr_res->get('locale_id')] = $qr_res->getRow();
				$va_seen_locales[$vn_locale_id] = true;
			}
			
			if ((isset($pa_options['idsOnly']) && $pa_options['idsOnly'])) {
				ExternalCache::save($vs_cache_key, $va_items, 'listItems');
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
				ExternalCache::save($vs_cache_key, $va_labels, 'listItems');
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
				if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0) && in_array((int)$va_item['access'], $pa_check_access, true)) { continue; }
				
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
		
		if (is_array($va_items) && (sizeof($va_items) < 1000)) {
			ExternalCache::save($vs_cache_key, $va_items, 'listItems');
		}
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Recursive function that processes each level of hierarchical list
	 */
	private function _getItemsForListProcListLevel($pn_root_id, $pa_items, &$pa_sorted_items, $pa_options) {
		$va_items = $pa_items[$pn_root_id] ?? null;
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
	 *		checkAccess =   Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return array
	 */
	public function getChildItemsForList($pm_list_name_or_id, $pn_item_id, $pa_options=null) {
		if ($pm_list_name_or_id) {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
			$this->load($vn_list_id);
		}
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
		
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
				$vs_order_by = 'cli.`rank`';
				break;
			case __CA_LISTS_SORT_BY_VALUE__:	// by value
				$vs_order_by = 'cli.item_value';
				break;
			case __CA_LISTS_SORT_BY_IDENTIFIER__:	// by identifier
				$vs_order_by = 'cli.idno_sort';
				break;
		}
		$vs_order_by = "ORDER BY {$vs_order_by}";
		
		$va_params = [(int)$vn_list_id, floatval($t_item->get('hier_left')), floatval($t_item->get('hier_right'))];
		$vs_access_sql = '';
        if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
            $pa_check_access = array_map("intval", $pa_check_access);
            $vs_access_sql = " AND cli.access IN (?)";
            $va_params[] = $pa_check_access;
        }
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_list_items cli
			INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id
			WHERE
				(cli.deleted = 0) AND (clil.is_preferred = 1) AND (cli.list_id = ?) AND (cli.hier_left >= ? AND cli.hier_right <= ?) {$vs_access_sql}
			{$vs_order_by}
		", $va_params);
		
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
	 *		includeRoot =   Include root record for list in count; default is false
	 *		checkAccess =   Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return int
	 */
	public function numItemsInList($pm_list_name_or_id=null, $pn_type_id=null, $pa_options=null) {
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
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
		
		$va_params = [(int)$vn_list_id];
		$vs_access_sql = '';
        if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
            $pa_check_access = array_map("intval", $pa_check_access);
            $vs_access_sql = " AND cli.access IN (?)";
            $va_params[] = $pa_check_access;
        }
        
		$vs_type_sql = '';
		if ($pn_type_id) {
			$vs_type_sql = ' AND (cli.type_id = ?)';
			$va_params[] = (int)$pn_type_id;
		}
		$qr_res = $o_db->query("
			SELECT count(*) c
			FROM ca_list_items cli
			WHERE
				(cli.deleted = 0) AND (cli.list_id = ?) {$vs_include_root_sql} {$vs_access_sql} {$vs_type_sql} 
		", $va_params);
		
		if($qr_res->nextRow()) {
			return $qr_res->get('c');
		}
		
		return 0;
	}
	# ------------------------------------------------------
	/**
	 * Return ca_list_items instance for list item with given idno in a list
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param string $ps_idno The list item identifer
	 * @params array $pa_options Options include:
	 *      checkAccess =   Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return ca_list_items 
	 */
	public function getItemInstanceFromList($pm_list_name_or_id, $ps_idno, $pa_options=null) {
		if (is_array($va_item = $this->getItemFromList($pm_list_name_or_id, $ps_idno, $pa_options))) {
			if($va_item['item_id']) {
				return new ca_list_items($va_item['item_id']);
			}
		}	
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Return array of field information for a list item with given idno in a list
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param string $ps_idno The list item identifer
	 * @params array $pa_options Options include:
	 *      checkAccess =   Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return array 
	 */
	public function getItemFromList($pm_list_name_or_id, $ps_idno, $pa_options=null) {
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options ?? [], "{$pm_list_name_or_id}/{$ps_idno}");
		if (isset(ca_lists::$s_list_item_get_cache[$vs_cache_key])) {
			return ca_lists::$s_list_item_get_cache[$vs_cache_key];
		}
	
		$vs_deleted_sql = caGetOption('includeDeleted', $pa_options, false) ? "" : "(cli.deleted = 0) AND ";
	
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		$vs_alt_key = caMakeCacheKeyFromOptions($pa_options ?? [], "{$vn_list_id}/{$ps_idno}");
		if (isset(ca_lists::$s_list_item_get_cache[$vs_alt_key])) {
			return ca_lists::$s_list_item_get_cache[$vs_alt_key];
		}
		
		$o_db = $this->getDb();
		
		$va_params = [(int)$vn_list_id, (string)$ps_idno];
		$vs_access_sql = '';
        if (is_array($pa_options['checkAccess'] ?? null) && (sizeof($pa_options['checkAccess']) > 0)) {
            $vs_access_sql = " AND cli.access IN (?)";
            $va_params[] = array_map("intval", $pa_options['checkAccess']);
        }
        
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_list_items cli
			WHERE
                 {$vs_deleted_sql} (cli.list_id = ?) AND (cli.idno = ?) {$vs_access_sql}
		", $va_params);
		
		if ($qr_res->nextRow()) {
			return  ca_lists::$s_list_item_get_cache[$vs_alt_key] = ca_lists::$s_list_item_get_cache[$vs_cache_key] = $qr_res->getRow();
		}
		return ca_lists::$s_list_item_get_cache[$vs_alt_key] = ca_lists::$s_list_item_get_cache[$vs_cache_key]  = null;
	}
	# ------------------------------------------------------
	/**
	 * Return array of field information for a list item with given item_id in a list
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param int $pn_item_id An item_id
	 * @params array $pa_options Options include:
	 *      checkAccess =   Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return array 
	 */
	public function getItemFromListByItemID($pm_list_name_or_id, $pn_item_id, $pa_options=null) {
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
		
		$vs_deleted_sql = caGetOption('includeDeleted', $pa_options, false) ? "" : "(cli.deleted = 0) AND ";
		
		$va_params = [(int)$vn_list_id, (int)$pn_item_id];
        $vs_access_sql = '';
        if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
            $pa_check_access = array_map("intval", $pa_check_access);
            $vs_access_sql = " AND cli.access IN (?)";
            $va_params[] = $pa_check_access;
        }
		
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_list_items cli
			WHERE
				{$vs_deleted_sql} (cli.list_id = ?) AND (cli.item_id = ?) {$vs_access_sql}
		", $va_params);
		$va_items = array();
		while($qr_res->nextRow()) {
			 return $qr_res->getRow();
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns data for list item(s) with a given item value. Item value is a text or numeric value associated with a list item. Unlike
	 * idno, it need not be unique.
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $pm_value The item value
	 * @param mixed $pa_options Options include:
	 *                        checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 * @return array
	 */
	public function getItemFromListByItemValue($pm_list_name_or_id, $pm_value, $pa_options=null) {
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options ?? [], "{$pm_list_name_or_id}/{$pm_value}");
		
		if (isset(ca_lists::$s_list_item_value_display_cache[$vs_cache_key])) {
			$va_items = ca_lists::$s_list_item_value_display_cache[$vs_cache_key];
		} else {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
			
			$va_params = [(int)$vn_list_id, (string)$pm_value];
		    $vs_access_sql = '';
		    if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
		        $pa_check_access = array_map("intval", $pa_check_access);
		        $vs_access_sql = " AND cli.access IN (?)";
		        $va_params[] = $pa_check_access;
		    }
		    
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT *
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.item_value = ?) AND (clil.is_preferred = 1) {$vs_access_sql}
			", $va_params);
			
			$va_items = array();
			while($qr_res->nextRow()) {
				$pn_item_id = $qr_res->get('item_id');
				 $va_items[$pn_item_id][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			ca_lists::$s_list_item_value_display_cache[$vs_cache_key] =  $va_items;
		}
		return $va_items;
	}
	# ------------------------------------------------------
	/**
	 * Return array of field information for a list item with given label in a list
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param string $ps_label_name A preferred or nonpreferred list item label
	 * @params array $pa_options Options include:
	 *      checkAccess =   Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *
	 * @return array 
	 */
	public function getItemFromListByLabel($pm_list_name_or_id, $ps_label_name, $pa_options=null) {
	    $vs_cache_key = caMakeCacheKeyFromOptions($pa_options ?? [], "{$pm_list_name_or_id}/{$ps_label_name}");
		if (isset(ca_lists::$s_list_item_get_cache[$vs_cache_key])) {
			return ca_lists::$s_list_item_get_cache[$vs_cache_key];
		}
		
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
	
		$vn_list_id = $this->_getListID($pm_list_name_or_id);
		$vs_alt_key = caMakeCacheKeyFromOptions($pa_options ?? [], "{$vn_list_id}/{$ps_label_name}");
		if (isset(ca_lists::$s_list_item_get_cache[$vs_alt_key])) {
			return ca_lists::$s_list_item_get_cache[$vs_alt_key];
		}
		
		$va_params = [(int)$vn_list_id, (string)$ps_label_name, (string)$ps_label_name];
        $vs_access_sql = '';
        if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
            $pa_check_access = array_map("intval", $pa_check_access);
            $vs_access_sql = " AND cli.access IN (?)";
            $va_params[] = $pa_check_access;
        }
        
		$o_db = $this->getDb();
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_list_items cli
			INNER JOIN ca_list_item_labels AS clil ON clil.item_id = cli.item_id
			WHERE
				(cli.deleted = 0) AND (cli.list_id = ?) AND ((clil.name_singular = ?) OR (clil.name_plural = ?)) {$vs_access_sql}
		", $va_params);
		
		if ($qr_res->nextRow()) {
			return ca_lists::$s_list_item_get_cache[$vs_cache_key] = ca_lists::$s_list_item_get_cache[$vs_alt_key] = $qr_res->getRow();
		}
		return ca_lists::$s_list_item_get_cache[$vs_cache_key] = ca_lists::$s_list_item_get_cache[$vs_alt_key]  = null;

	}	
	# ------------------------------------------------------
	/**
	 * Returns name of list item for a given list item identifer (aka "idno")
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param string $ps_idno The list item identifer
	 * @param mixed $pa_options If set to a boolean, controls whether the singular (false) or plural (true) sense are returned. 
	 *                          This allows an older calling signature, where the second parameter was a boolean to be preserved. 
	 *                          If set to an options array, available options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *                              return = Set to "singular" or "plural" to return singular or plural sense. [Default is "singular"]
	 * @return string
	 */
	public function getItemFromListForDisplay($pm_list_name_or_id, $ps_idno, $pa_options=null) {
	    $pb_return_plural = !is_array($pa_options) ? (bool)$pa_options : (caGetOption('return', $pa_options, 'singular') == 'plural');
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options ?? [], "{$pm_list_name_or_id}/{$ps_idno}");
		
		if (isset(ca_lists::$s_list_item_display_cache[$ps_idno])) {
			$va_items = ca_lists::$s_list_item_display_cache[$vs_cache_key];
		} else {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
			
			$va_params = [(int)$vn_list_id, (string)$ps_idno];
		    $vs_access_sql = '';
		    if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
		        $pa_check_access = array_map("intval", $pa_check_access);
		        $vs_access_sql = " AND cli.access IN (?)";
		        $va_params[] = $pa_check_access;
		    }
		    
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT cli.item_id, clil.locale_id, clil.name_singular, clil.name_plural
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND (cli.list_id = ?) AND (cli.idno = ?) AND (clil.is_preferred = 1) {$vs_access_sql}
			", $va_params);
			
			$va_items = array();
			while($qr_res->nextRow()) {
				 $va_items[$vn_item_id = $qr_res->get('item_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			ca_lists::$s_list_item_display_cache[$vs_cache_key] = $va_items;
		}
		
		$va_tmp = caExtractValuesByUserLocale($va_items, null, null, array());
		$va_item = array_shift($va_tmp);
		
		return $va_item[$pb_return_plural ? 'name_plural' : 'name_singular'];
	}
	# ------------------------------------------------------
	/**
	 * Deprecated call. Forwarded to ca_lists::getItemForDisplayByItemID()
	 * @seeAlso getItemForDisplayByItemID()
	 */
	public function getItemFromListForDisplayByItemID($pm_list_name_or_id, $pn_item_id, $pa_options=null) {
	    return $this->getItemForDisplayByItemID($pn_item_id, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Returns name of list item for a given numeric item_id.
	 *
	 * @param int $pn_item_id Numeric id for list item
	 * @param mixed $pa_options If set to a boolean, controls whether the singular (false) or plural (true) sense are returned. 
	 *                          This allows an older calling signature, where the second parameter was a boolean to be preserved. 
	 *                          If set to an options array, available options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *                              return = Set to "singular" or "plural" to return singular or plural sense. [Default is "singular"]
	 * @return string
	 */
	public function getItemForDisplayByItemID($pn_item_id, $pa_options=null) {
	    $pb_return_plural = !is_array($pa_options) ? (bool)$pa_options : (caGetOption('return', $pa_options, 'singular') == 'plural');
		$pa_check_access = caGetOption('checkAccess', $pa_options, null);
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options ?? [], "{$pn_item_id}");
		
		if (isset(ca_lists::$s_list_item_display_cache[$vs_cache_key])) {
			$va_items = ca_lists::$s_list_item_display_cache[$vs_cache_key];
		} else {
		    $va_params = [(int)$pn_item_id];
		    $vs_access_sql = '';
		    if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0)) {
		        $pa_check_access = array_map("intval", $pa_check_access);
		        $vs_access_sql = " AND cli.access IN (?)";
		        $va_params[] = $pa_check_access;
		    }
			$o_db = $this->getDb();
			$qr_res = $o_db->query("
				SELECT cli.item_id, clil.locale_id, clil.name_singular, clil.name_plural
				FROM ca_list_items cli
				INNER JOIN ca_list_item_labels AS clil ON cli.item_id = clil.item_id
				WHERE
					(cli.deleted = 0) AND (cli.item_id = ?) AND (clil.is_preferred = 1) {$vs_access_sql}
			", $va_params);
			
			$va_items = array();
			while($qr_res->nextRow()) {
				 $va_items[$qr_res->get('item_id')][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			ca_lists::$s_list_item_display_cache[$vs_cache_key] = $va_items;
		}
		
		$va_tmp = caExtractValuesByUserLocale($va_items, null, isset($pa_options['locale']) ? [$pa_options['locale']] : null, array());
		$va_item = array_shift($va_tmp);
		
		return is_array($va_item) ? $va_item[$pb_return_plural ? 'name_plural' : 'name_singular'] ?? null : null;
	}
	# ------------------------------------------------------
	/**
	 * Returns name for first list item found in a list with a given item value. Item value is a text or numeric value 
	 * associated with a list item. Unlike idno, it need not be unique.
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $pm_value The item value
	 * @param mixed $pa_options If set to a boolean, controls whether the singular (false) or plural (true) sense are returned. 
	 *                          This allows an older calling signature, where the second parameter was a boolean to be preserved. 
	 *                          If set to an options array, available options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *                              return = Set to "singular" or "plural" to return singular or plural sense. [Default is "singular"]
	 * @return string
	 */
	public function getItemFromListForDisplayByItemValue($pm_list_name_or_id, $pm_value, $pa_options=null) {
	    $pb_return_plural = !is_array($pa_options) ? (bool)$pa_options : (caGetOption('return', $pa_options, 'singular') == 'plural');
		
		if ($va_item = $this->getItemFromListByItemValue($pm_list_name_or_id, $pm_value, $pa_options)) {			
			$va_tmp = caExtractValuesByUserLocale($va_item, null, null, array());
			$va_item = array_shift($va_tmp);
			return $va_item[$pb_return_plural ? 'name_plural' : 'name_singular'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns the item_id for list item in a list with a given identifer (aka "idno"). 
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $ps_idno The list item identifier (idno)
	 * @param mixed $pa_options Options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 * @return int The item_id of the first matching item, or null if no matching item was found
	 */
	public function getItemIDFromList($pm_list_name_or_id, $ps_idno, $pa_options=null) {
		if ($va_list_item = $this->getItemFromList($pm_list_name_or_id, $ps_idno, $pa_options)) {
			return (int)$va_list_item['item_id'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns the item_id for first list item found in a list with a given item value. Item value is a text or numeric value 
	 * associated with a list item. Unlike idno, it need not be unique.
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $pm_value The item value
	 * @param mixed $pa_options Options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 * @return string
	 */
	public function getItemIDFromListByItemValue($pm_list_name_or_id, $pm_value, $pa_options=null) {
		if ($va_item = $this->getItemFromListByItemValue($pm_list_name_or_id, $pm_value, $pa_options)) {
			$va_tmp = caExtractValuesByUserLocale($va_item, null, null, array());
			$va_item = array_shift($va_tmp);
			return $va_item['item_id'];
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns the item_id for first list item found in a list with a given preferred or non-preferred label value.
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $ps_label_name The label value
	 * @param mixed $pa_options Options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 * @return int The item_id of the first matching item, or null if no matching item was found
	 */
	public function getItemIDFromListByLabel($pm_list_name_or_id, $ps_label_name, $pa_options=null) {
		if ($vn_list_id = $this->_getListID($pm_list_name_or_id)) {
			if ($vn_id = ca_list_items::find(array('list_id' => $vn_list_id, 'preferred_labels' => array('name_plural' => $ps_label_name)), array('returnAs' => 'firstId', 'checkAccess' => caGetOption('checkAccess', $pa_options, null)))) {
				return $vn_id;
			} elseif ($vn_id = ca_list_items::find(array('list_id' => $vn_list_id, 'preferred_labels' => array('name_singular' => $ps_label_name)), array('returnAs' => 'firstId', 'checkAccess' => caGetOption('checkAccess', $pa_options, null)))) {
				return $vn_id;
			}
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Return the item_id of the root for a given list
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list. If omitted the list for the currently loaded item is used.
	 *
	 * @return int The item_id of the root item, or null if no list could be found.
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
	 * Check if list has an item with a given identifier (aka "idno")
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $ps_idno The list item identifier (idno)
	 * @param mixed $pa_options Options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 * @return bool True if the item exists
	 */
	public function itemIsInList($pm_list_name_or_id, $ps_idno, $pa_options=null) {
		return $this->getItemFromList($pm_list_name_or_id, $ps_idno, $pa_options) ? true : false;
	}
	# ------------------------------------------------------
	/**
	 * Check if list has an item with a given item_id
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $pn_item_id The list item_id
	 * @param mixed $pa_options Options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 * @return bool True if the item exists
	 */
	public function itemIDIsInList($pm_list_name_or_id, $pn_item_id, $pa_options=null) {
		return $this->getItemFromListByItemID($pm_list_name_or_id, $pn_item_id, $pa_options) ? true : false;
	}
	# ------------------------------------------------------
	 /**
	 * Check if specified list item exists and has its is_enabled flag set
	 *
	 * @param mixed $pm_list_name_or_id The list code or list_id for the list to search
	 * @param mixed $pn_item_id The list item_id
	 * @param mixed $pa_options Options include:
	 *                              checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 * @return bool True if the item exists and is enabled
	 */
	public function itemIsEnabled($pm_list_name_or_id, $pn_item_id, $pa_options=null) {
		if ($va_item = $this->getItemFromListByItemID($pm_list_name_or_id, $pn_item_id, $pa_options)) {
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
	 * @param array $pa_options Options include options for @see ca_list_items::getItemsForList() as well as:
	 *		useFirstElementAsDefaultDefault = return first item in list if not explicit default is set for the list. [Default is false]
	 *
	 * @return int The item_id of the default element or null if no list was specified or loaded. If no default is set for the list in question null is returned
	 */
	public function getDefaultItemID($pm_list_name_or_id=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
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
		
		return caGetOption('useFirstElementAsDefaultDefault', $pa_options, false) ? array_shift($this->getItemsForList($vn_list_id, array_merge($pa_options, array('idsOnly' => true)))) : null; 
	}
	# ------------------------------------------------------
	/**
	 * Convert list code to list_id
	 */
	private function _getListID($pm_list_name_or_id) {
		return ca_lists::getListID($pm_list_name_or_id, array('transaction' => $this->getTransaction()));
	}
	# ------------------------------------------------------
	/**
	 * Converts list specifier (code or list_id) into a list_id
	 *
	 * @param mixed $pm_list_name_or_id List code or list_id
	 * @param array $pa_options Options include:
	 *      transaction = Transaction to perform database operations within. [Default is null]
	 *
	 * @return int list for the specified list, or null if the list does not exist
	 */
	static function getListID($pm_list_name_or_id, $pa_options=null) {
	    $vs_cache_key = caMakeCacheKeyFromOptions($pa_options ?? [], $pm_list_name_or_id);
		if (ca_lists::$s_list_id_cache[$vs_cache_key] ?? null) {
			return ca_lists::$s_list_id_cache[$vs_cache_key];
		}
		if (is_numeric($pm_list_name_or_id)) {
			$vn_list_id = (int)$pm_list_name_or_id;
		} else {
			$t_list = new ca_lists();
			$o_trans = caGetOption('transaction', $pa_options, null);
			if ($o_trans) { $t_list->setTransaction($o_trans); }
			if (!$t_list->load(array('list_code' => $pm_list_name_or_id))) {
				return null;
			}
			$vn_list_id = $t_list->getPrimaryKey();
		}
		
		return ca_lists::$s_list_id_cache[$vs_cache_key] = $vn_list_id;
	}
	# ------------------------------------------------------
	/**
	 * Converts list specifier (code or list_id) into a list_id
	 *
	 * @param mixed $pm_list_name_or_id List code or list_id
	 * @param array $pa_options Options include:
	 *      transaction = Transaction to perform database operations within. [Default is null]
	 *
	 * @return int list for the specified list, or null if the list does not exist
	 */
	static function getListCode($pm_list_name_or_id, $pa_options=null) {
		if (ca_lists::$s_list_code_cache[$pm_list_name_or_id] ?? null) {
			return ca_lists::$s_list_code_cache[$pm_list_name_or_id];
		}
		if (!is_numeric($pm_list_name_or_id)) {
			return $pm_list_name_or_id;
		} else {
			$t_list = new ca_lists();
			$o_trans = caGetOption('transaction', $pa_options, null);
			if ($o_trans) { $t_list->setTransaction($o_trans); }
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
	 *  disableItemsWithID = An optional array of list item_ids. Item_ids in the array will be disabled in the returned list.	
	 *	maxItemCount = Don't return an element if the list has more elements than limit. [Default is null – no limit]
	 *
	 *	limitToItemsRelatedToCollections = an array of collection_ids or collection idno's; returned items will be restricted to those attached to the specified collections
	 *	limitToItemsRelatedToCollectionWithRelationshipTypes = array of collection type names or type_ids; returned items will be restricted to those attached to the specified collectionss with the specified relationship type
	 *	limitToListIDs = array of list_ids to restrict returned items to when using "limitToItemsRelatedToCollections"
	 *
	 *  indentForHierarchy = indicate hierarchy with indentation. [Default is true]
	 * 	transaction = transaction to perform database operations within. [Default is null]
	 *
	 *  useDefaultWhenNull = if a list has a null value the default value is typically ignored and null used as the initial value; set this option to use the default in all cases [Default=false]
	 *	checkAccess = Array of access values to filter returned values on. If omitted no filtering is performed. [Default is null]
	 *	exclude = array of item idnos to omit from the returned list. [Default is null]
	 *
	 *	separateDisabledValues = Separate disabled values, placing them after enabled items. [Default is false]
	 *  hideDisabledValues = Omit disabled values from list. [Default is false]
	 *  forceEnabled = enable all list items regardless of the value of the item's is_enabled value [Default is false]
	 *
	 *  deferHierarchyLoad = defer hierarchy browser loads until user clicks on expand button. [Default is false]
	 *
	 *  useSingular = Return singular name. [Default is true] 
	 *	 
	 * @return string - HTML code for the <select> element; empty string if the list is empty
	 */
	static public function getListAsHTMLFormElement($pm_list_name_or_id, $ps_name, $pa_attributes=null, $pa_options=null) {
		$t_list = new ca_lists();
		if($o_trans = caGetOption('transaction', $pa_options, null)) {
			$t_list->setTransaction($o_trans);
		}
		
		$singular = caGetOption('useSingular', $pa_options, true);
		
		$va_list_items = null;
		$defer_hierarchy_load = caGetOption('deferHierarchyLoad', $pa_options, false);
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		if (($vn_limit = caGetOption('maxItemCount', $pa_options, null)) && ($t_list->numItemsInList($pm_list_name_or_id, null, $pa_options) > $vn_limit)) {
			return null;
		}
		
		if (!(isset($pa_options['limitToItemsRelatedToCollection']) && is_array($pa_options['limitToItemsRelatedToCollections']))) {
			$vn_list_id = $t_list->_getListID($pm_list_name_or_id);
			$t_list->load($vn_list_id);
		}
		$root_id = (isset($pa_options['childrenOnlyForItemID']) && $pa_options['childrenOnlyForItemID']) ? $pa_options['childrenOnlyForItemID'] : null;
		
		$va_disabled_item_ids = caGetOption('disableItemsWithID', $pa_options, null);
		
		$render_as = isset($pa_options['render']) ? $pa_options['render'] : ''; 
		$vb_is_vertical_hier_browser = in_array($render_as, ['vert_hierbrowser', 'vert_hierbrowser_up', 'vert_hierbrowser_down']);
				
		$vn_sort_type = $t_list->get('default_sort');
		if (($render_as == 'yes_no_checkboxes') && ($vn_sort_type == __CA_LISTS_SORT_BY_LABEL__)) {
			$vn_sort_type = __CA_LISTS_SORT_BY_IDENTIFIER__;	// never allow sort-by-label when rendering as yes/no checkbox
		}
		
		if (!in_array($render_as, array('lookup', 'horiz_hierbrowser', 'vert_hierbrowser', 'vert_hierbrowser_up', 'vert_hierbrowser_down'))) {
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
						$va_list_items = $qr_collections->get('ca_list_items', array('returnAsArray' => true, 'returnWithStructure' => true, 'checkAccess' => caGetOption('checkAccess', $pa_options, null)));
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
				$va_list_items = $t_list->getItemsForList($pm_list_name_or_id, array_merge($pa_options, array('returnHierarchyLevels' => caGetOption('indentForHierarchy', $pa_options, true), 'item_id' => $root_id, 'extractValuesByUserLocale' => true, 'sort' => $vn_sort_type)));
			}
		}
		
		if (!is_array($va_list_items)) { $va_list_items = array(); }
		
		$va_options = $va_disabled_options = [];
		
		if (!isset($pa_options['value'])) { $pa_options['value'] = null; }
		if (!isset($pa_options['key'])) { $pa_options['key'] = 'item_id'; }
		if (!in_array($pa_options['key'], array('item_id', 'item_value'))) {
			$pa_options['key'] = 'item_id';
		}
		
		if (!isset($pa_options['limitToItemsWithID']) || !is_array($pa_options['limitToItemsWithID']) || !sizeof($pa_options['limitToItemsWithID'])) { $pa_options['limitToItemsWithID'] = null; }
		if (!isset($pa_options['omitItemsWithID']) || !is_array($pa_options['omitItemsWithID']) || !sizeof($pa_options['omitItemsWithID'])) { $pa_options['omitItemsWithID'] = null; }
		$pa_exclude_items = caGetOption('exclude', $pa_options, null);
	
		if ((isset($pa_options['nullOption']) && $pa_options['nullOption']) && (($render_as !== 'checklist') && !($pa_options['requireValue'] ?? false) )) {
			$va_options[''] = $pa_options['nullOption'];
		}
		
		
		if (is_array($pa_options['limitToItemsWithID'] ?? null) && sizeof($pa_options['limitToItemsWithID'])) {
		    $pa_options['limitToItemsWithID'] = caMakeItemIDList($pm_list_name_or_id, $pa_options['limitToItemsWithID']);
			// expand limit list to include parents of items that are included
			$va_to_add = array();
			foreach($va_list_items as $vn_item_id => $va_item) {
				if (($vn_parent_id = $va_item['parent_id']) && in_array($vn_item_id, $pa_options['limitToItemsWithID'])) {
					$va_to_add[$vn_parent_id] = true;
					while($vn_parent_id = ($va_list_items[$vn_parent_id]['parent_id'] ?? null)) {
						if($va_list_items[$vn_parent_id]['parent_id'] ?? null) { $va_to_add[$va_list_items[$vn_parent_id]['parent_id']] = true; }
					}
				}
			}	
			$pa_options['limitToItemsWithID'] += array_keys($va_to_add);
		}
		if (is_array($pa_options['omitItemsWithID'] ?? null) && sizeof($pa_options['omitItemsWithID'])) {
		     $pa_options['omitItemsWithID'] = caMakeItemIDList($pm_list_name_or_id, $pa_options['omitItemsWithID']);
		}
		
		$pa_check_access = caGetOption('checkAccess', $pa_options, null); 
		if(!is_array($pa_check_access) && $pa_check_access) { $pa_check_access = array($pa_check_access); }
		
		$va_in_use_list = null;
		if (($pa_options['inUse'] ?? false) && (int)($pa_options['element_id'] ?? 0) && ($pa_options['table'] ?? null)) {
			if ($t_instance = Datamodel::getInstance($pa_options['table'], true)) {
				$va_params = array((int)($pa_options['element_id'] ?? null));
				if(is_array($pa_check_access) && sizeof($pa_check_access)) {
					$va_params[] = $pa_check_access;
				}
				
				$qr_in_use = $t_list->getDb()->query("
					SELECT DISTINCT cav.item_id
					FROM ca_attribute_values cav
					INNER JOIN ca_attributes AS ca ON ca.attribute_id = cav.attribute_id
					INNER JOIN ".$t_instance->tableName()." AS t ON t.".$t_instance->primaryKey()." = ca.row_id
					WHERE 
						(cav.element_id = ?) AND 
						(ca.table_num = ".$t_instance->tableNum().") 
						".(($t_instance->hasField('deleted') ? " AND (t.deleted = 0)" : ""))."
						".((is_array($pa_check_access) && sizeof($pa_check_access)) ? " AND t.access IN (?)" : "")."
				", $va_params);
				$va_in_use_list = $qr_in_use->getAllFieldValues('item_id');
			}
		}
		
		$va_colors = array();
		$vn_default_val = null;
		
		$separate_disabled = caGetOption('separateDisabledValues', $pa_options, false);
		$hide_disabled_values = caGetOption('hideDisabledValues', $pa_options, false);
		
		$disabled_option_list = [];
		foreach($va_list_items as $vn_item_id => $va_item) {
			if (is_array($pa_options['limitToItemsWithID']) && !in_array($vn_item_id, $pa_options['limitToItemsWithID'])) { continue; }
			if (is_array($pa_options['omitItemsWithID']) && in_array($vn_item_id, $pa_options['omitItemsWithID'])) { continue; }
			if ($hide_disabled_values && !$va_item['is_enabled'] && ($va_item['item_id'] != $pa_options['value'])) { continue; }
			if (is_array($va_in_use_list) && !in_array($vn_item_id, $va_in_use_list)) { continue; }
			if (is_array($pa_check_access) && (sizeof($pa_check_access) > 0) && !in_array($va_item['access'], $pa_check_access)) { continue; }
			if (is_array($pa_exclude_items) && (sizeof($pa_exclude_items) > 0) && in_array($va_item['idno'], $pa_exclude_items)) { continue; }
			
			if (!caGetOption('forceEnabled', $pa_options, false) && (!$va_item['is_enabled'] || (is_array($va_disabled_item_ids) && in_array($vn_item_id, $va_disabled_item_ids)))) { $va_disabled_options[$va_item[$pa_options['key']]] = true; }
			
			if($separate_disabled && !$va_item['is_enabled']) {
				$disabled_option_list[$va_item[$pa_options['key']]] = str_repeat('&nbsp;', intval($va_item['LEVEL']) * 3).' '.$va_item[$singular ? 'name_singular' : 'name_plural'];
				continue;
			}

			$va_options[$va_item[$pa_options['key'] ?? null] ?? null] = str_repeat('&nbsp;', intval(($va_item['LEVEL'] ?? 0)) * 3).' '.$va_item['name_singular'];

			$va_colors[$vn_item_id] = $va_item['color'];
			
			if ($va_item['is_default']) { $vn_default_val = $va_item[$pa_options['key']]; }		// get default value
			
			if ($va_item['is_default'] && (!isset($pa_options['nullOption']) || (isset($pa_options['useDefaultWhenNull']) && (bool)$pa_options['useDefaultWhenNull']))) {		// set default if needed, but only if there's not a null option set
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
		if($separate_disabled) {
			$va_options += $disabled_option_list;
		}
		
		$pa_options['disabledOptions'] = $va_disabled_options;
		
		// For "access_statuses" list we enforce per-table/type option visibility restrictions
		// here using specification in app.conf "omit_access_statuses" setting
		//
		if (
			($t_list->get('ca_lists.list_code') === 'access_statuses') && 
			($omit_table = caGetOption('table', $pa_options, null))			// table to omit values for
		) {
			$omit_type = caGetOption('type', $pa_options, null);			// optional type to omit values for
			$config = Configuration::load();
			$omit_map = $config->getAssoc('omit_access_statuses');
			
			if (isset($omit_map[$omit_table]) && ($omit_map = $omit_map[$omit_table])) {
				if ($omit_type && is_array($omit_map[$omit_type])) { 
					$omit_map = $omit_map[$omit_type];						// type specific policy
				} else {
					$omit_map = $omit_map['__default__'];					// default (all type) policy
				}
				
				if (is_array($omit_map)) {
					$v = array_shift(caExtractValuesByUserLocale(caGetListItemForValue('access_statuses', $pa_options['value'])));
					$value_idno = $v['idno'];
					$default_value = caGetOption('default', $omit_map, null);
					if(is_array($omit_map['omit'])) { $omit_map = $omit_map['omit']; }
					if (in_array($value_idno, $omit_map, true)) {
						$pa_options['value']= caGetListItemValueForIdno('access_statuses', $default_value);
					}
					foreach($omit_map as $i => $value) {
						if(!strlen(trim($value))) { continue; }
						if (!is_numeric($value)) { 
							$value = caGetListItemValueForIdno('access_statuses', $value); 	// convert status identifiers to integer code values
							if(!strlen(trim($value))) { continue; }
						}
						unset($va_options[(int)$value]);
					}
				}
			}
		}
		
		if (($max_columns = caGetOption('maxColumns', $pa_options, 1, ['castTo' => 'integer'])) < 1) { $max_columns = 1; }
		switch($render_as) {
			case 'radio_buttons':
				if (!sizeof($va_options)) { return ''; }	// return empty string if list has no values
				$vn_i = 0;
				
				$p = floor(670/$max_columns);
				if ($p > 150) { $p = 150; }
				$buf = ($max_columns > 1) ? "<div class='checklist' style='grid-template-columns: ".str_repeat("fit-content(175px) ", $max_columns).";'>\n" : "<div>\n";
	
				foreach($va_options as $vm_value => $vs_label) {
					$vs_label = str_replace("&nbsp;", "", trim($vs_label));
					$va_attributes = array('value' => $vm_value);
					if (isset($va_disabled_options[$vm_value]) && $va_disabled_options[$vm_value]) {
						$va_attributes['disabled'] = 1;
					}
					$va_attributes['value'] = $vm_value;
					$va_attributes['id'] = $ps_name.'_'.$vn_i;
					
					if (is_array($pa_options['value']) ? in_array($vm_value, $pa_options['value']) : ($pa_options['value'] == $vm_value)) {
						$va_attributes['checked'] = '1';
					}
					if (isset($pa_options['readonly']) && ($pa_options['readonly'])) {
						$va_attributes['disabled'] = 1;
					}
					$buf .=  "<div class='checklistItem'>".caHTMLRadioButtonInput($ps_name, $va_attributes, $pa_options)." {$vs_label}</div>\n";
					
					$vn_i++;
				}
				$buf .= "</div>";
				return $buf;
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
							if (is_array($pa_options['value']) ? in_array($vm_value, $pa_options['value']) : ($pa_options['value'] == $vm_value)) {
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
				
				$p = floor(100/$max_columns);
				$buf = ($max_columns > 1) ? "<div class='checklist' style='grid-template-columns: ".str_repeat("fit-content(175px) ", $max_columns).";'>\n" : "<div>\n";
			
				foreach($va_options as $vm_value => $vs_label) {
					$va_attributes = array('value' => $vm_value);
					if (isset($va_disabled_options[$vm_value]) && $va_disabled_options[$vm_value]) {
						$va_attributes['disabled'] = 1;
					}
					if (isset($pa_options['readonly']) && ($pa_options['readonly'])) {
						$va_attributes['disabled'] = 1;
					}
					if (is_array($pa_options['value']) ? in_array($vm_value, $pa_options['value']) : ($pa_options['value'] == $vm_value)) {
						$va_attributes['checked'] = '1';
					}
					
					$buf .= "<div class='checklistItem'>".caHTMLCheckboxInput($ps_name.'_'.$vm_value, $va_attributes, $pa_options)." ".str_replace('&nbsp;', '', $vs_label)."</div>\n";				
				}
				$buf .= "</div>";
				return $buf;
				break;
			case 'lookup':
				$vs_value = $vs_hidden_value = "";
				if(caGetOption('forSearch',$pa_options)) {
					if($vs_val_id = caGetOption('value',$pa_options)) {
						$vs_value = $t_list->getItemFromListForDisplayByItemID($pm_list_name_or_id, $vs_val_id);
						$vs_hidden_value = $vs_val_id;
					}
				} else {
					$vs_value = "{{".$pa_options['element_id']."_label}}";
					$vs_hidden_value = "{{".$pa_options['element_id']."}}";
				}
				$buf =
 				caHTMLTextInput(
 					$ps_name.'_autocomplete', 
					array(
						'width' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width']: "300px", 
						'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1, 
						'value' => $vs_value, 
						'maxlength' => 512,
						'id' => $ps_name."_autocomplete",
						'class' => 'lookupBg'
					)
				).
				caHTMLHiddenInput(
					$ps_name,
					array(
						'value' => $vs_hidden_value, 
						'id' => $ps_name
					)
				);
				
				if ($pa_options['request']) {
					$vs_url = caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'Get', array('list' => ca_lists::getListCode($vn_list_id), 'noInline' => 1, 'noSymbols' => 1, 'max' => 100));
				} else {
					// hardcoded default for testing.
					$vs_url = '/index.php/lookup/ListItem/Get';	
				}
				
				$buf .= '</div>';
				$buf .= "
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
				return $buf;
				break;
			case 'horiz_hierbrowser':
			case 'horiz_hierbrowser_with_search':
			case 'vert_hierbrowser':
			case 'vert_hierbrowser_up':
			case 'vert_hierbrowser_down':
				$vn_width = $vn_height = null;
				
				if(is_array($va_width = caParseFormElementDimension($pa_options['width'] ? $pa_options['width'] : $pa_options['width']))) {
					if (($va_width['type'] != 'pixels') && ($va_width['dimension'] < 250)) { $va_width['dimension'] = 500; }
					$vn_width = $va_width['dimension'].'px';
				}
				if(is_array($va_height = caParseFormElementDimension($pa_options['height']))) {
					if (($va_height['type'] != 'pixels') && ($va_height['dimension'] < 100)) { $va_height['dimension'] = 200; }
					$vn_height = $va_height['dimension'].'px';
				}
				
				$t_root_item = new ca_list_items();
				$t_root_item->load(['list_id' => $vn_list_id, 'parent_id' => null]);

				// don't set fixed container height when autoshrink is turned on, because we want it to grow/shrink with the
				// hier browser element ... set max height for autoshrink instead
				$vn_autoshrink_height = 180;
				if(caGetOption('auto_shrink', $pa_options, false)) {
					$vn_height = null;
					$vn_autoshrink_height = (int) $va_height['dimension'];
				}
				
				AssetLoadManager::register("hierBrowser");
				
				$buf = "<div id='{$ps_name}_hierarchyBrowser_wrapper{n}'><div style='width: {$vn_width}; ".($vn_height ? "height: {$vn_height}" : "").";'><div id='{$ps_name}_hierarchyBrowser{n}' style='width: 100%; height: 100%;' class='".($vb_is_vertical_hier_browser ? 'hierarchyBrowserVertical' : 'hierarchyBrowser')."'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->	</div>";
				
				if (!($current_selection_display_format = caGetOption('currentSelectionDisplayFormat', $pa_options, null))) {
					$current_selection_display_format = '<ifdef code="hierarchy">^hierarchy%delimiter=_➜_ ➜ </ifdef>^current';
				}
				
				$buf .= "
	<script type='text/javascript'>
		var  _init{$ps_name}_hierarchyBrowser{n}, {$ps_name}_hierarchyBrowser{n};
		var defer = ".($defer_hierarchy_load ? 'true' : 'false').";
		if('{n}'.match(/^new_/)) { defer = false; }
		jQuery(document).ready(function() { 
			 if(defer) { jQuery('#{$ps_name}_hierarchyBrowser_wrapper{n}').hide(); }
			 _init{$ps_name}_hierarchyBrowser{n} = function() {
			 	jQuery('#{$ps_name}_hierarchyBrowser_wrapper{n}').show();
			 	
			 	var p = jQuery('#{$ps_name}_hierarchyBrowser_wrapper{n}').parents('.caItemList');
			 	var st = jQuery('#{$ps_name}_hierarchyBrowser_wrapper{n}').offset().top;
			 	if(st < 0) { st = 0; }
			 	p.animate({
			 		scrollTop: p.scrollTop() + st - p.offset().top - 100
			 	});
			 	
			 	jQuery('#{$ps_name}_hierarchyBrowser_edit{n}').hide();
				{$ps_name}_hierarchyBrowser{n} = caUI.initHierBrowser('{$ps_name}_hierarchyBrowser{n}', {
					uiStyle: '".($vb_is_vertical_hier_browser ? 'vertical' : 'horizontal')."',
					uiDirection: '".(($render_as == 'vert_hierbrowser_down') ? 'down' : 'up')."',
					levelDataUrl: ".json_encode(caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'GetHierarchyLevel', array('noSymbols' => 1))).",
					initDataUrl: ".json_encode(caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'GetHierarchyAncestorList')).",
				
					selectOnLoad : true,
					browserWidth: ".(int)($va_width['dimension'] ?? 670).",
				
					className: '".($vb_is_vertical_hier_browser ? 'hierarchyBrowserLevelVertical' : 'hierarchyBrowserLevel')."',
					classNameContainer: '".($vb_is_vertical_hier_browser ? 'hierarchyBrowserContainerVertical' : 'hierarchyBrowserContainer')."',
				
					editButtonIcon: ".json_encode(caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1)).",
					disabledButtonIcon: ".json_encode(caNavIcon(__CA_NAV_ICON_DOT__, 1)).",
					initItemID: '{".$pa_options['element_id']."}',
					defaultItemID: ".json_encode($t_list->getDefaultItemID()).",
					useAsRootID: ".json_encode($t_root_item->getPrimaryKey()).",
					indicatorUrl: ".json_encode(caNavIcon(__CA_NAV_ICON_SPINNER__, 1)).",
					autoShrink: '".(caGetOption('auto_shrink', $pa_options, false) ? 'true' : 'false')."',
					autoShrinkAnimateID: '{$ps_name}_hierarchyBrowser{n}',
					autoShrinkMaxHeightPx: {$vn_autoshrink_height},
				
					currentSelectionDisplayFormat: ".json_encode($current_selection_display_format).",
				
					currentSelectionDisplayID: '{$ps_name}_browseCurrentSelectionText{n}',
					onSelection: function(item_id, parent_id, name, display) {
						jQuery('#{$ps_name}').val(item_id);
					}
				});
			}
";
			
		if ($render_as == 'horiz_hierbrowser_with_search') {
			$buf .= "jQuery('#{$ps_name}_hierarchyBrowserSearch{n}').autocomplete(
					{
						source: '".caNavUrl($pa_options['request'], 'lookup', 'ListItem', 'Get', array('list' => ca_lists::getListCode($vn_list_id), 'noSymbols' => 1, 'noInline' => 1))."', 
						minLength: 3, delay: 800,
						select: function(event, ui) {
							{$ps_name}_hierarchyBrowser{n}.setUpHierarchy(ui.item.id);	// jump browser to selected item
						}
					}
				);";
		}
		$buf .= "
			if(!defer) { _init{$ps_name}_hierarchyBrowser{n}(); }
		});
	</script>";
	
				if ($render_as == 'horiz_hierbrowser_with_search') {
					$buf .= "<div class='hierarchyBrowserSearchBar'>"._t('Search').": <input type='text' id='{$ps_name}_hierarchyBrowserSearch{n}' class='hierarchyBrowserSearchBar' name='search' value='' size='20'/></div>";
				}
				
				if (!$vb_is_vertical_hier_browser) {
					$buf .= "<div id='{$ps_name}_browseCurrentSelection{n}' class='hierarchyBrowserCurrentSelection'>"._t("Current selection").": <span id='{$ps_name}_browseCurrentSelectionText{n}' class='hierarchyBrowserCurrentSelectionText'>-</span></div>";
				}
				$buf .= caHTMLHiddenInput(
					$ps_name,
					array(
						'value' => "{".$pa_options['element_id']."}", 
						'id' => $ps_name
					)
				);
				$buf .= "</div>\n";
				
				// deferred load control
				$buf .= "<div id='{$ps_name}_hierarchyBrowser_edit{n}'><a href='#' id='{$ps_name}_hierarchyBrowser_edit_link{n}' onclick='_init{$ps_name}_hierarchyBrowser{n}(); return false;'>".caNavIcon(__CA_NAV_ICON_HIER__, '18px')."</a> <span class='formLabelPlain'>{{".$pa_options['element_id']."_label}}</span></div>";
				return $buf;
				break;
			case 'text':
				return caHTMLTextInput($ps_name, $pa_attributes, $pa_options);
				break;
			case 'options':
				return $va_options;
				break;
			case 'multiple':
			default:
				if (!sizeof($va_options)) { return ''; }	// return empty string if list has no values
				if (isset($pa_options['readonly']) && ($pa_options['readonly'])) { $pa_attributes['disabled'] = 1; }
				if ($render_as == 'multiple') {  
					$pa_attributes['multiple'] = 1; unset($pa_options['value']); 
				} elseif(is_array($pa_options['value'])) {
					$pa_options['value'] = array_shift($pa_options['value']);
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
		//if (ExternalCache::contains($pm_list_name_or_id, 'listRootIDs')) { return ExternalCache::fetch($pm_list_name_or_id, 'listRootIDs'); }
		if($pm_list_name_or_id) {
			$vn_list_id = $this->_getListID($pm_list_name_or_id);
		} else {
			$vn_list_id = $this->getPrimaryKey();
		}
		if (!$vn_list_id) { return null; } 
		
		$t_items = new ca_list_items();
		$t_items->load(array('list_id' => $vn_list_id, 'parent_id' => null));
		$vn_id = $t_items->getPrimaryKey();
		
		if ($pm_list_name_or_id && $vn_list_id) {
			ExternalCache::save($pm_list_name_or_id, $vn_id, 'listRootIDs');
			ExternalCache::save($vn_list_id, $vn_id, 'listRootIDs');
		}
		
		return $vn_id;
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
			SELECT cl.*, cll.name, cll.locale_id, cli.item_id root_id
			FROM ca_lists cl
			LEFT JOIN ca_list_labels AS cll ON cl.list_id = cll.list_id
			INNER JOIN ca_list_items AS cli ON cli.list_id = cl.list_id
			WHERE
				cli.parent_id IS NULL
			ORDER BY
				cll.list_id
		");
		$va_lists = array();
		while($qr_lists->nextRow()) {
			$va_tmp =  $qr_lists->getRow();
			
			if (!$va_tmp['name']) { $va_tmp['name'] = $va_tmp['list_code']; }				// if there's no label then use the list_code as its name
			$va_lists[$qr_lists->get('list_id')][$qr_lists->get('locale_id')] = $va_tmp;
		}
		
		return $va_lists;
	}
	
	# ------------------------------------------------------
	/**
	 * Returns list codes and list_ids of all available lists. 
	 *
	 * @param array $pa_options Options include:
	 *		transaction = Transaction to execute list query within. [Default=null]
	 * @return array
	 */
	static public function getListCodes($pa_options=null) {		
		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options ?? []);
		if (ExternalCache::contains($vs_cache_key, 'listCodes') && is_array($v = ExternalCache::fetch($vs_cache_key, 'listCodes'))) { 
			return $v; 
		}
		
		$t_list = new ca_lists();
		if ($o_trans = caGetOption('transaction', $pa_options, null)) { $t_list->setTransaction($o_trans); }
		$o_db = $t_list->getDb();

		$qr_lists = $o_db->query("
			SELECT cl.list_id, cl.list_code
			FROM ca_lists cl
			WHERE
				deleted = 0
		");
		$va_lists = [];
		while($qr_lists->nextRow()) {
			$va_lists[$qr_lists->get('list_id')] = $qr_lists->get('list_code');
		}
		ksort($va_lists);
		
		ExternalCache::save($vs_cache_key, $va_lists, 'listCodes');
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
	 * 		transaction = transaction to perform database operations within. [Default is null]
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
		if($o_trans = caGetOption('transaction', $pa_options, null)) {
			$t_list->setTransaction($o_trans);
			$t_item->setTransaction($o_trans);
		}
		
		$va_tmp = $va_item_ids = array();
		foreach($pa_idnos as $vs_idno) {
			$vn_item_id = null;
			if (is_numeric($vs_idno)) { 
				$va_tmp = array((int)$vs_idno); 
			} else {
				$va_tmp = ca_list_items::find(array('idno' => $vs_idno, 'deleted' => 0), array('returnAs' => 'ids', 'transaction' => $o_trans));
			}
			
			if (sizeof($va_tmp) && !(isset($pa_options['noChildren']) || $pa_options['noChildren'])) {
				foreach($va_tmp as $vn_item_id) {
					if ($qr_children = $t_item->getHierarchy($vn_item_id, array())) {
						while($qr_children->nextRow()) {
							$va_item_ids[$qr_children->get('item_id')] = true;
						}
					}
				}
			} else {
				foreach($va_tmp as $vn_item_id) {
					$va_item_ids[$vn_item_id] = true;
				}
			}
		}
		return array_keys($va_item_ids);
	}
	# ------------------------------------------------------
	/**
	 *
	 * 	transaction = transaction to perform database operations within. [Default is null]
	 */
	static public function getItemID($pm_list_name_or_id, $ps_idno, $pa_options=null) {
		if ((!isset($pa_options['noCache']) || !isset($pa_options['noCache'])) && isset(ca_lists::$s_item_id_cache[$pm_list_name_or_id][$ps_idno])) {
			return ca_lists::$s_item_id_cache[$pm_list_name_or_id][$ps_idno];
		}
		if($o_trans = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_trans->getDb();
		} else {
			$o_db = new Db();
		}
		$vn_item_id = null;
		if ($vn_list_id = ca_lists::getListID($pm_list_name_or_id)) {
			
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
	/**
	 * Converts a list of item_id's to a list of idno strings. The conversion is literal without hierarchical expansion.
	 *
	 * @param array $pa_list A list of relationship numeric item_ids
	 * @param array $pa_options Options include:
	 * 		transaction = transaction to perform database operations within. [Default is null]
	 * @return array A list of corresponding idnos 
	 */
	 static public function itemIDsToIDNOs($pa_ids, $pa_options=null) {
	 	if (!is_array($pa_ids) || !sizeof($pa_ids)) { return null; }
	 	
	 	$vs_cache_key = caMakeCacheKeyFromOptions(['ids' => $pa_ids, 'opts' => $pa_options]);
	 	if (isset(ca_lists::$s_item_id_to_code_cache[$vs_cache_key])) {
	 		return ca_lists::$s_item_id_to_code_cache[$vs_cache_key];
	 	}
	 	
	 	$va_ids = $va_non_numerics = array();
	 	foreach($pa_ids as $pn_id) {
	 		if (!is_numeric($pn_id)) {
	 			$va_non_numerics[] = $pn_id;
	 		} else {
	 			$va_ids[] = (int)$pn_id;
	 		}
	 	}
	 	
	 	if($o_trans = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_trans->getDb();
		} else {
			$o_db = new Db();
		}
		
	 	$qr_res = $o_db->query("
	 		SELECT item_id, idno 
	 		FROM ca_list_items
	 		WHERE
	 			item_id IN (?)
	 	", array($va_ids));
	 	
	 	$va_item_ids_to_codes = array();
	 	while($qr_res->nextRow()) {
	 		$va_item_ids_to_codes[$qr_res->get('item_id')] = $qr_res->get('idno');
	 	}
	 	return ca_lists::$s_item_id_to_code_cache[$vs_cache_key] = $va_item_ids_to_codes + $va_non_numerics;
	}
	# ------------------------------------------------------
	/**
	 * Converts a list of item_id's to a list of value strings. The conversion is literal without hierarchical expansion.
	 *
	 * @param array $pa_list A list of relationship numeric item_ids
	 * @param array $pa_options Options include:
	 * 		transaction = transaction to perform database operations within. [Default is null]
	 * @return array A list of corresponding item values 
	 */
	 static public function itemIDsToItemValues($pa_ids, $pa_options=null) {
	 	if (!is_array($pa_ids) || !sizeof($pa_ids)) { return null; }
	 	
	 	$vs_cache_key = caMakeCacheKeyFromOptions(['ids' => $pa_ids, 'opts' => $pa_options]);
	 	
	 	if (isset(ca_lists::$s_item_id_to_value_cache[$vs_cache_key])) {
	 		return ca_lists::$s_item_id_to_value_cache[$vs_cache_key];
	 	}
	 	
	 	$va_ids = $va_non_numerics = array();
	 	foreach($pa_ids as $pn_id) {
	 		if (!is_numeric($pn_id)) {
	 			$va_non_numerics[] = $pn_id;
	 		} else {
	 			$va_ids[] = (int)$pn_id;
	 		}
	 	}
	 	
	 	if($o_trans = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_trans->getDb();
		} else {
			$o_db = new Db();
		}
		
	 	$qr_res = $o_db->query("
	 		SELECT item_id, item_value 
	 		FROM ca_list_items
	 		WHERE
	 			item_id IN (?)
	 	", array($va_ids));
	 	
	 	$va_item_ids_to_values = array();
	 	while($qr_res->nextRow()) {
	 		$va_item_ids_to_values[$qr_res->get('item_id')] = $qr_res->get('item_value');
	 	}
	 	return ca_lists::$s_item_id_to_value_cache[$vs_cache_key] = $va_item_ids_to_values + $va_non_numerics;
	}
	# ------------------------------------------------------
	/**
	 * Converts a list of item idno's to a list of numeric item_id's. The conversion is literal without hierarchical expansion.
	 *
	 * @param array $pa_list A list of relationship numeric item_ids
	 * @param array $pa_options Options include:
	 * 		transaction = transaction to perform database operations within. [Default is null]
	 *		list_id = restrict lookup to a specific list by list_id. [Default is null; no filtering]
	 * @return array A list of corresponding item_ids with item_id as key and idno as value 
	 */
	 static public function IDNOsToItemIDs($pa_item_ids, $pa_options=null) {
	 	if (!is_array($pa_item_ids) || !sizeof($pa_item_ids)) { return null; }
	 	
	 	$vs_cache_key = caMakeCacheKeyFromOptions(['ids' => $pa_item_ids, 'opts' => $pa_options]);
	 	if (isset(ca_lists::$s_code_to_item_id_cache[$vs_cache_key])) {
	 		return ca_lists::$s_code_to_item_id_cache[$vs_cache_key];
	 	}
	 	
	 	if($o_trans = caGetOption('transaction', $pa_options, null)) {
			$o_db = $o_trans->getDb();
		} else {
			$o_db = new Db();
		}
		
		$va_params = [$pa_item_ids];
		$vs_list_sql = '';
		if ($vn_list_id = caGetOption('list_id', $pa_options, null, ['castTo' => 'int'])) {
			$vs_list_sql = " AND list_id = ?";
			$va_params[] = $vn_list_id;
		}
		
	 	$qr_res = $o_db->query("
	 		SELECT item_id, idno 
	 		FROM ca_list_items
	 		WHERE
	 			idno IN (?) {$vs_list_sql}
	 	", array($pa_item_ids));
	 	
	 	$va_item_ids = array();
	 	while($qr_res->nextRow()) {
	 		$va_item_ids_to_codes[$qr_res->get('item_id')] = $qr_res->get('idno');
	 	}
	 	return ca_lists::$s_code_to_item_id_cache[$vs_cache_key] = $va_item_ids_to_codes;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getAdditionalChecksumComponents() {
		return [$this->get('list_code')];
	}
	# ------------------------------------------------------
	/**
	 * Quickly return access value for list item 
	 *
	 * @param int $item_id 
	 * 
	 * @return bool or null if item does not exist
	 */
	static public function getAccessForItemID($pn_item_id) {
		$o_db = new Db();
		$q = $o_db->query("SELECT access FROM ca_list_items WHERE item_id = ? AND deleted = 0", [(int)$pn_item_id]);
		while($q->nextRow()) {
			return $q->get('access');
		}
		return null;
	}
	# ------------------------------------------------------
}
