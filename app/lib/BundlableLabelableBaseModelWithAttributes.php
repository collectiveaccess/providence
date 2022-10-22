<?php
/** ---------------------------------------------------------------------
 * app/lib/BundlableLabelableBaseModelWithAttributes.php : base class for models that take application of bundles
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2022 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__."/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/SyncableBaseModel.php");
require_once(__CA_LIB_DIR__."/DeduplicateBaseModel.php");
require_once(__CA_LIB_DIR__."/LabelableBaseModelWithAttributes.php");
require_once(__CA_LIB_DIR__."/Plugins/SearchEngine/CachedResult.php");
require_once(__CA_LIB_DIR__."/Search/SearchResult.php");

require_once(__CA_LIB_DIR__."/IDNumbering.php");
require_once(__CA_APP_DIR__."/helpers/accessHelpers.php");
require_once(__CA_APP_DIR__."/helpers/searchHelpers.php");

if(!defined('__CA_BUNDLE_ACCESS_NONE__')) { define('__CA_BUNDLE_ACCESS_NONE__', 0); }
if(!defined('__CA_BUNDLE_ACCESS_READONLY__')) { define('__CA_BUNDLE_ACCESS_READONLY__', 1); }
if(!defined('__CA_BUNDLE_ACCESS_READONLY__')) { define('__CA_BUNDLE_ACCESS_READONLY__', 2); }

/**
 * Returned by BundlableLabelableBaseModelWithAttributes::saveBundlesForScreenWillChangeParent() when parent will not be changed
 */
define('__CA_PARENT_UNCHANGED__', 0);

/**
 * Returned by BundlableLabelableBaseModelWithAttributes::saveBundlesForScreenWillChangeParent() when parent will be changed
 */
define('__CA_PARENT_CHANGED__', 1);

/**
 * Returned by BundlableLabelableBaseModelWithAttributes::saveBundlesForScreenWillChangeParent() when parent will be changed to a related collection in a object-collection hierarchy
 */
define('__CA_PARENT_COLLECTION_CHANGED__', 2);

class BundlableLabelableBaseModelWithAttributes extends LabelableBaseModelWithAttributes implements IBundleProvider {
	# ------------------------------------------------------
	use SyncableBaseModel;
	use DeduplicateBaseModel;
	# ------------------------------------------------------
	/**
	 *
	 */
	protected $BUNDLES = [];
	
	/**
	 *
	 */
	protected $opo_idno_plugin_instance;
	
	/**
	 * ca_locales model
	 */
	static $s_locales = null;
	
	/**
	 * TimeExpressionParser 
	 */
	static $s_tep = null;
	
	/**
	 *
	 */
	protected $_rowAsSearchResult;
	# ------------------------------------------------------
	public function __construct($id=null, ?array $options=null) {
		parent::__construct($id, $options);	# call superclass constructor
		
		if ($pn_id) {
			if ($this->_rowAsSearchResult = $this->makeSearchResult($this->tableName(), [$id])) {
				$this->_rowAsSearchResult->nextHit();
			}
		}
		$this->initLabelDefinitions($options);
	}
	# ------------------------------------------------------
	/**
	 * Overrides load() to initialize bundle specifications
	 */
	public function load($pm_id=null, $pb_use_cache=true) {
		global $AUTH_CURRENT_USER_ID;
		
		$vn_rc = parent::load($pm_id, $pb_use_cache);
		
		if ($vn_id = $this->getPrimaryKey()) {
			if ($this->_rowAsSearchResult = $this->makeSearchResult($this->tableName(), array($vn_id))) {
				$this->_rowAsSearchResult->nextHit();
			}
		}
		
		if ($this->getAppConfig()->get('perform_item_level_access_checking')) {
			if ($this->checkACLAccessForUser(new ca_users($AUTH_CURRENT_USER_ID)) == __CA_ACL_NO_ACCESS__) {
				$this->clear();
				return false;
			}
		}
		$this->initLabelDefinitions();
		
		if ($this->isHierarchical() && $this->opo_idno_plugin_instance) {
			$this->opo_idno_plugin_instance->isChild($this->isChild());
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override insert() to check type_id (or whatever the type key is called in the table as returned by getTypeFieldName())
	 * against the ca_lists list for the table (as defined by getTypeListCode())
	 */ 
	public function insert($pa_options=null) {
		if (!is_a($this, "BaseRelationshipModel")) {
			global $AUTH_CURRENT_USER_ID;

			$this->opo_app_plugin_manager->hookBeforeBundleInsert(array('id' => null, 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));

			$vb_we_set_transaction = false;

			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));
				$vb_we_set_transaction = true;
			}

			$we_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
		
			// check that type_id is valid for this table
			$t_list = new ca_lists();
			$vn_type_id = $this->get($this->getTypeFieldName());
			$va_field_info = $this->getFieldInfo($this->getTypeFieldName());
		
			$vb_error = false;
			if ($this->getTypeFieldName() && !(!$vn_type_id && $va_field_info['IS_NULL'])) {
				if (!($vn_ret = $t_list->itemIsEnabled($this->getTypeListCode(), $vn_type_id))) {
					$va_type_list = $this->getTypeList(array('directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'item_id' => null));
					if (!isset($va_type_list[$vn_type_id])) {
						$this->postError(2510, _t("A valid type must be specified"), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
					} else {
						if(is_null($vn_ret)) {
							$this->postError(2510, _t("<em>%1</em> is invalid", $va_type_list[$vn_type_id]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
						} else {
							$this->postError(2510, _t("<em>%1</em> is not enabled", $va_type_list[$vn_type_id]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
						}
					}
					$vb_error = true;
				}
		
				if ($this->HIERARCHY_PARENT_ID_FLD && (bool)$this->getAppConfig()->get($this->tableName().'_enforce_strict_type_hierarchy')) {
					// strict means if it has a parent is can only have types that are direct sub-types of the parent's type
					// and if it is the root of the hierarchy it can only take a top-level type
					if ($vn_parent_id = $this->get($this->HIERARCHY_PARENT_ID_FLD)) {
						// is child
						$t_parent = Datamodel::getInstanceByTableName($this->tableName());
						if ($t_parent->load($vn_parent_id)) {
							$vn_parent_type_id = $t_parent->getTypeID();
							$va_type_list = $t_parent->getTypeList(array('directChildrenOnly' => ($this->getAppConfig()->get($this->tableName().'_enforce_strict_type_hierarchy') === '~') ? false : true, 'childrenOfCurrentTypeOnly' => true, 'returnHierarchyLevels' => true));

							if (!isset($va_type_list[$this->getTypeID()])) {
								$va_type_list = $this->getTypeList(array('directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'item_id' => null));

								$this->postError(2510, _t("<em>%1</em> is not a valid type for a child record of type <em>%2</em>", $va_type_list[$this->getTypeID()]['name_singular'], $va_type_list[$vn_parent_type_id]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
								$vb_error = true;
							}
						} else {
							// error - no parent?
							$this->postError(2510, _t("No parent was found when verifying type of new child"), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
							$vb_error = true;
						}
					} else {
						// is root
						$va_type_list = $this->getTypeList(array('directChildrenOnly' => true, 'item_id' => null));
						if (!isset($va_type_list[$this->getTypeID()]) && ($t = $this->getTypeInstance())) {
							// if all ancestors for type are not enabled then we can allow this type even in strict mode
							$ancestors = $t->getHierarchyAncestors(null, ['includeSelf' => false]);
							if (sizeof(array_filter($ancestors, function($v) {
								return (!is_null($v['NODE']['parent_id']) && (bool)$v['NODE']['is_enabled']);
							})) > 0) {
								$va_type_list = $this->getTypeList(array('directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'item_id' => null));
						
								$this->postError(2510, _t("<em>%1</em> is not a valid type for a top-level record", $va_type_list[$this->getTypeID()]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
								$vb_error = true;
							}
						}
					}
				}
			}
		
			// If validateAllIdnos is set we validate idnos for all records, including hierarchy roots
			// Default is to force root idno's to conform to validation requirements
			if (!$this->_validateIncomingAdminIDNo(true, !caGetOption('validateAllIdnos', $pa_options, false))) { $vb_error =  true; }
		
			if ($vb_error) {			
				// push all attributes onto errored list
				$va_inserted_attributes_that_errored = [];
				foreach($this->opa_attributes_to_add as $va_info) {
					$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
				}
				foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
					$this->setFailedAttributeInserts($vs_element, $va_list);
				}
			
				if ($we_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				if ($vb_we_set_transaction) { $this->removeTransaction(false); }
				$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
				return false;
			}
		
			$this->_generateSortableIdentifierValue();
			
			// Process "access_inherit_from_parent" flag where supported
			if ((bool)$this->getAppConfig()->get($this->tableName().'_allow_access_inheritance') && $this->hasField('access_inherit_from_parent')) {
				// Child record with inheritance set
				if ((bool)$this->get('access_inherit_from_parent') && (($vn_parent_id = $this->get('parent_id')) > 0)) {
					$t_parent = Datamodel::getInstanceByTableNum($this->tableNum(), false);
					if ($t_parent->load($vn_parent_id)) {
						$this->set('access', $t_parent->set('access'));
					}
				}
			}
		}
		
		// stash attributes to add
		$va_attributes_added = $this->opa_attributes_to_add;
		if (!($vn_rc = parent::insert($pa_options))) {	
			// push all attributes onto errored list
			$va_inserted_attributes_that_errored = [];
			foreach($va_attributes_added as $va_info) {
				if (isset($this->opa_failed_attribute_inserts[$va_info['element']])) { continue; }
				$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
			}
			foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeInserts($vs_element, $va_list);
			}
			
			if ($we_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
			return false;
		}

		$this->setGUID($pa_options);
		
		if ($we_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
	
		$this->opo_app_plugin_manager->hookAfterBundleInsert(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		if ($vb_we_set_transaction) { $this->removeTransaction(true); }
		
		
		if ($vn_id = $this->getPrimaryKey()) {
			if ($this->_rowAsSearchResult = $this->makeSearchResult($this->tableName(), array($vn_id))) {
				$this->_rowAsSearchResult->nextHit();
			}
		}
		
		if(caGetOption('hooks', $pa_options, true)) {
			$this->opo_app_plugin_manager->hookInsertItem(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'is_insert' => true, 'for_duplication' => caGetOption('forDuplication', $pa_options, true)));
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override update() to generate sortable version of user-defined identifier field
	 */ 
	public function update($pa_options=null) {
		global $AUTH_CURRENT_USER_ID;
		if ($this->getAppConfig()->get('perform_item_level_access_checking')) {
			if ($this->checkACLAccessForUser(new ca_users($AUTH_CURRENT_USER_ID)) < __CA_ACL_EDIT_ACCESS__) {
				$this->postError(2580, _t("You do not have edit access for this item: %1/%2", $this->tableName(), $this->getPrimaryKey()), "BundlableLabelableBaseModelWithAttributes->update()");
				return false;
			}
		}

		$this->opo_app_plugin_manager->hookBeforeBundleUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));

		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		}

		$we_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
		
		if ($this->HIERARCHY_PARENT_ID_FLD && (bool)$this->getAppConfig()->get($this->tableName().'_enforce_strict_type_hierarchy')) {
			if ($vn_parent_id = $this->get($this->HIERARCHY_PARENT_ID_FLD)) {
				// is child
				$t_parent = Datamodel::getInstanceByTableName($this->tableName());
				if ($t_parent->load($vn_parent_id)) {
					$vn_parent_type_id = $t_parent->getTypeID();
					$va_type_list = $t_parent->getTypeList(array('directChildrenOnly' => ($this->getAppConfig()->get($this->tableName().'_enforce_strict_type_hierarchy') === '~') ? false : true, 'childrenOfCurrentTypeOnly' => true, 'returnHierarchyLevels' => true));

					if (!isset($va_type_list[$this->getTypeID()])) {
						$va_type_list = $this->getTypeList(array('directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'item_id' => null));

						$this->postError(2510, _t("<em>%1</em> is not a valid type for a child record of type <em>%2</em>", $va_type_list[$this->getTypeID()]['name_singular'], $va_type_list[$vn_parent_type_id]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
								
						if ($we_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
						if ($vb_we_set_transaction) { $this->removeTransaction(false); }
						return false;
					}
				} else {
					// error - no parent?
					$this->postError(2510, _t("No parent was found when verifying type of new child"), "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$this->getTypeFieldName());
													
					if ($we_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
					if ($vb_we_set_transaction) { $this->removeTransaction(false); }
					return false;
				}	
			}		
		}

		$va_errors = [];
		if (!$this->_validateIncomingAdminIDNo(true, false)) { 
			 $va_errors = $this->errors();
			 // don't save number if it's invalid
			 if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			 	$this->set($vs_idno_field, $this->getOriginalValue($vs_idno_field));
			 }
		} else {
			$this->_generateSortableIdentifierValue();
		}
		
		// Process "access_inherit_from_parent" flag where supported
		if ((bool)$this->getAppConfig()->get($this->tableName().'_allow_access_inheritance') && $this->hasField('access_inherit_from_parent')) {
			// Child record with inheritance set
			if ((bool)$this->get('access_inherit_from_parent') && (($vn_parent_id = $this->get('parent_id')) > 0)) {
				$t_parent = Datamodel::getInstanceByTableNum($this->tableNum(), false);
				if ($t_parent->load($vn_parent_id)) {
					$this->set('access', $t_parent->set('access'));
				}
			}
			
			// Parent record for which access is changing
			if ($this->changed('access')) {
				$this->getDb()->query("
					UPDATE ".$this->tableName()." SET access = ? 
					WHERE
						parent_id = ? AND access_inherit_from_parent = 1
				", array((int)$this->get('access'), $this->getPrimaryKey()));
			}
		}
	
		$vn_rc = parent::update($pa_options);
		$this->errors = array_merge($this->errors, $va_errors);
		
		$this->opo_app_plugin_manager->hookAfterBundleUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		if ($we_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
		
		if ($vb_we_set_transaction) { $this->removeTransaction($vn_rc); }
						
		SearchResult::clearResultCacheForRow($this->tableName(), $this->getPrimaryKey());

		if(caGetOption('hooks', $pa_options, true)) {
			$this->opo_app_plugin_manager->hookUpdateItem(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'is_insert' => false, 'for_duplication' => caGetOption('forDuplication', $pa_options, true)));
		}
		return $vn_rc;
	}	
	# ------------------------------------------------------------------
	/**
	 * Check user's item level access before passing delete to lower level libraries
	 *
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		global $AUTH_CURRENT_USER_ID;
		if ($this->getAppConfig()->get('perform_item_level_access_checking')) {
			if ($this->checkACLAccessForUser(new ca_users($AUTH_CURRENT_USER_ID)) < __CA_ACL_EDIT_DELETE_ACCESS__) {
				$this->postError(2580, _t("You do not have delete access for this item"), "BundlableLabelableBaseModelWithAttributes->delete()");
				return false;
			}
		}

		$vn_primary_key = $this->getPrimaryKey();
		SearchResult::clearResultCacheForRow($this->tableName(), $this->getPrimaryKey());
		
		$vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);

		return $vn_rc;
	}
	# ------------------------------------------------------------------
	/**
	 * Duplicates record, including labels, attributes and relationships. "Special" bundles - those
	 * specific to a model - should be duplicated by the model by overriding BundlableLabelablleBaseModelWithAttributes::duplicate()
	 * and doing any required work after BundlableLabelablleBaseModelWithAttributes::duplicate() has finished
	 * 
	 * @param array $pa_options
	 *		duplicate_nonpreferred_labels = duplicate nonpreferred labels. [Default is false]
	 *		duplicate_attributes = duplicate all content fields (intrinsics and attributes). [Default is false]
	 *		duplicate_relationships = if set to an array of table names, all relationships to be specified tables will be duplicated. [Default is null - no relationships duplicated]
	 *		duplicate_current_relationships_only = duplicate only current relationships, as determined by configured history tracking policies. [Default is false]
	 *		duplicate_relationship_attributes = duplicate metadata attributes attached to duplicated relationships. [Default is false]
	 *		duplicate_element_settings = per-metdata element duplication settings; keys are element names, values are 1 (duplicate) or 0 (don't duplicate); if element is not set then it will be duplicated. [Default is null]
	 *		duplicate_children = duplicate child records. [Default is false]
	 *		user_id = User ID of the user to make owner of the newly duplicated record (for records that support ownership by a user like ca_bundle_displays) [Default is null]
	 *		
	 * @return BundlableLabelablleBaseModelWithAttributes instance of newly created duplicate item
	 */
	public function duplicate($pa_options=null) {
		if (!$this->getPrimaryKey()) { return false; }
		$table = $this->tableName();
		$vs_idno_fld = $this->getProperty('ID_NUMBERING_ID_FIELD');
		$vs_idno_sort_fld = $this->getProperty('ID_NUMBERING_SORT_FIELD');
		$vs_parent_id_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD');
		$vs_pk = $this->primaryKey();
		
		$vb_duplicate_nonpreferred_labels = isset($pa_options['duplicate_nonpreferred_labels']) && $pa_options['duplicate_nonpreferred_labels'];
		$vb_duplicate_attributes = isset($pa_options['duplicate_attributes']) && $pa_options['duplicate_attributes'];
		$vb_duplicate_relationship_attributes = isset($pa_options['duplicate_relationship_attributes']) && $pa_options['duplicate_relationship_attributes'];
		$va_duplicate_relationships = (isset($pa_options['duplicate_relationships']) && is_array($pa_options['duplicate_relationships']) && sizeof($pa_options['duplicate_relationships'])) ? $pa_options['duplicate_relationships'] : [];
		$vb_duplicate_current_relationships_only = isset($pa_options['duplicate_current_relationships_only']) && $pa_options['duplicate_current_relationships_only'];
		$va_duplicate_element_settings = (isset($pa_options['duplicate_element_settings']) && is_array($pa_options['duplicate_element_settings']) && sizeof($pa_options['duplicate_element_settings'])) ? $pa_options['duplicate_element_settings'] : [];
		$vb_duplicate_relationship_attributes = isset($pa_options['duplicate_relationship_attributes']) && $pa_options['duplicate_relationship_attributes'];
		$vb_duplicate_children = isset($pa_options['duplicate_children']) && $pa_options['duplicate_children'];
		
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction($o_t = new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			$o_t = $this->getTransaction();
		}
		
		// create new instance
		if (!($t_dupe = Datamodel::getInstanceByTableName($table))) { 
			if ($vb_we_set_transaction) { $o_t->rollback();}
			return null;
		}
		$t_dupe->purify($this->purify());
		$t_dupe->setTransaction($o_t);
		
		if($this->isHierarchical()) {
			if (!$this->get($vs_parent_id_fld)) {
				if ($this->getHierarchyType() == __CA_HIER_TYPE_ADHOC_MONO__) {	// If we're duping the root of an adhoc hierarchy then we need to set the HIERARCHY_ID_FLD to null
					$this->set($this->getProperty('HIERARCHY_ID_FLD'), null);
				} else {
					// Don't allow duping of hierarchy roots for non-adhoc hierarchies
					if ($vb_we_set_transaction) { $o_t->rollback();}
					$this->postError(2055, _t("Cannot duplicate root of hierarchy"), "BundlableLabelableBaseModelWithAttributes->duplicate()");
					return null;
				}
			}
		}

		// duplicate primary record + intrinsics
		$va_field_list = $this->getFormFields(true, true);
		foreach($va_field_list as $vn_i => $vs_field) {
			if (in_array($vs_field, array($vs_idno_fld, $vs_idno_sort_fld, $vs_pk))) { continue; }		// skip idno fields
			$va_fld_info = $t_dupe->getFieldInfo($vs_field);
			
			switch($va_fld_info['FIELD_TYPE']) {
				case FT_MEDIA:		// media deserves special treatment
					$t_dupe->set($vs_field, $this->getMediaPath($vs_field, 'original'));
					break;
				case FT_VARS:
					$t_dupe->set($vs_field, $this->get($table.'.'.$vs_field, array('unserialize' => true)));
					break;
				default:
					if (!isset($va_duplicate_element_settings[$vs_field]) || (isset($va_duplicate_element_settings[$vs_field]) && ($va_duplicate_element_settings[$vs_field] == 1))) {
						$t_dupe->set($vs_field, $this->get($table.'.'.$vs_field));
					}
					break;
			}
		}
		
		$t_dupe->set($this->getTypeFieldName(), $this->getTypeID());
		
		// Calculate identifier using numbering plugin
		if ($vs_idno_fld && (!isset($va_duplicate_element_settings[$vs_idno_fld]) || (isset($va_duplicate_element_settings[$vs_idno_fld]) && ($va_duplicate_element_settings[$vs_idno_fld] == 1)))) {
			$vb_needs_suffix_generated = false;
			$vs_sep = null;
			if (method_exists($this, "getIDNoPlugInInstance") && ($o_numbering_plugin = $this->getIDNoPlugInInstance())) {
				$vs_sep = $o_numbering_plugin->getSeparator();
				
				$vs_idno_template = $o_numbering_plugin->makeTemplateFromValue($this->get($vs_idno_fld), 1);	// make template out of original idno by replacing last SERIAL element with "%"
				if (!preg_match("!%$!", $vs_idno_template)) { $vb_needs_suffix_generated = true; }
				
				if (!is_array($va_idno_values = $o_numbering_plugin->htmlFormValuesAsArray($vs_idno_fld, $vs_idno_template, false, false, false))) { $va_idno_values = []; }

				$t_dupe->set($vs_idno_fld, $vs_idno = join($vs_sep, $va_idno_values));	
			} 
			
			if (!($vs_idno_stub = trim($t_dupe->get($vs_idno_fld)))) {
				$vs_idno_stub = trim($this->get($vs_idno_fld));
			}
	
			if ($vs_idno_stub) {
				if ($vb_needs_suffix_generated) {
					$t_lookup = Datamodel::getInstance($table, true);
				
					$va_tmp = $vs_sep ? preg_split("![{$vs_sep}]+!", $vs_idno_stub) : [$vs_idno_stub];
					$vs_suffix = (is_array($va_tmp) && (sizeof($va_tmp) > 1)) ? array_pop($va_tmp) : '';
					if (!is_numeric($vs_suffix)) { 
						$vs_suffix = 0; 
					} else {
						$vs_idno_stub = preg_replace("!{$vs_suffix}$!", '', $vs_idno_stub);	
					}
					do {
						$vs_suffix = (int)$vs_suffix + 1;
						$vs_idno = trim($vs_idno_stub).($vs_sep ?? '-').trim($vs_suffix);	// force separator if none defined otherwise you end up with agglutinative numbers
					} while($t_lookup->load([$vs_idno_fld => $vs_idno]));
				} else {
					$vs_idno = $vs_idno_stub;
				}
			} else {
				$vs_idno = "???";
			}
			if ($vs_idno == $this->get($vs_idno_fld)) { $vs_idno .= " ["._t('DUP')."]"; }
			$t_dupe->set($vs_idno_fld, $vs_idno);
		}
		
		if (isset($pa_options['user_id']) && $pa_options['user_id'] && $t_dupe->hasField('user_id')) { $t_dupe->set('user_id', $pa_options['user_id']); }
		
		$t_dupe->insert(['forDuplication' => true]);
		
		if ($t_dupe->numErrors()) {
			$this->errors = $t_dupe->errors;
			if ($vb_we_set_transaction) { $o_t->rollback();}
			return false;
		}
		
		// duplicate labels
		$va_labels = $this->getLabels();
		$vs_label_display_field = $t_dupe->getLabelDisplayField();
		foreach($va_labels as $vn_label_id => $va_labels_by_locale) {
			foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
				foreach($va_label_list as $vn_i => $va_label_info) {
					unset($va_label_info['source_info']);
					if (!$vb_duplicate_nonpreferred_labels && key_exists('is_preferred', $va_label_info) && !$va_label_info['is_preferred']) { continue; }
					if (!$this->getAppConfig()->get('dont_mark_duplicated_records_in_preferred_label')) { $va_label_info[$vs_label_display_field] .= " ["._t('Duplicate')."]"; }
					$t_dupe->addLabel(
						$va_label_info, $va_label_info['locale_id'], $va_label_info['type_id'], isset($va_label_info['is_preferred']) ? (bool)$va_label_info['is_preferred'] : false
					);
					if ($t_dupe->numErrors()) {
						$this->errors = $t_dupe->errors;
						if ($vb_we_set_transaction) { $o_t->rollback();}
						return false;
					}
				}
			}
		}
		
		// duplicate attributes
		if ($vb_duplicate_attributes) {
			$va_attrs_to_duplicate = null;
			if (sizeof($va_duplicate_element_settings)) {
				$va_attrs_to_duplicate = [];
				foreach($va_duplicate_element_settings as $vs_bundle => $vn_duplication_setting) {
					if ((substr($vs_bundle, 0, 13) == 'ca_attribute_') && ($vn_duplication_setting)) {
						$va_attrs_to_duplicate[] = substr($vs_bundle, 13);
					}
				}
			}

			$va_options = ['restrictToAttributesByCodes' => $va_attrs_to_duplicate];

			if($va_dont_duplicate_codes = $this->getAppConfig()->get("{$table}_dont_duplicate_element_codes")) {
				if(is_array($va_dont_duplicate_codes)) {
					$va_options['excludeAttributesByCodes'] = $va_dont_duplicate_codes;
				}
			}
	
			if (!$t_dupe->copyAttributesFrom($this->getPrimaryKey(), $va_options)) {
				$this->errors = $t_dupe->errors;
				if ($vb_we_set_transaction) { $o_t->rollback();}
				return false;
			}
		}
		
		// duplicate relationships
		$history_tracking_policies = method_exists($table, "getHistoryTrackingCurrentValuePolicies") ? array_keys($table::getHistoryTrackingCurrentValuePolicies($table)) : [];
		foreach(array(
			'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 
			'ca_collections', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_storage_locations', 'ca_tour_stops'
		) as $vs_rel_table) {
			if (!in_array($vs_rel_table, $va_duplicate_relationships)) { continue; }
			
			$limit_to_relation_ids = null;
			if ($vb_duplicate_current_relationships_only) {
				
				if(is_array($history_tracking_policies) && sizeof($history_tracking_policies)) {
					// is this relationship part of a policy?
					foreach($history_tracking_policies as $policy) {
						if (!$table::historyTrackingPolicyUses($policy, $vs_rel_table)) { continue; }
						if (!is_array($h = $this->getHistory(['currentOnly' => true, 'limit' => 1, 'policy' => $policy])) || !sizeof($h)) { continue; }
					
						$current = array_shift(array_shift($h));
						if ($current['current_table_num'] === Datamodel::getTableNum($vs_rel_table)) {
							$limit_to_relation_ids = [$current['tracked_row_id']];
						}
					}
				}
			}
			if ($this->copyRelationships($vs_rel_table, $t_dupe->getPrimaryKey(), array('copyAttributes' => $vb_duplicate_relationship_attributes, 'relationIds' => $limit_to_relation_ids)) === false) {
				$this->errors = $t_dupe->errors;
				if ($vb_we_set_transaction) { $o_t->rollback();}
				return false;
			}
		}
		
		// duplicate sets
		if (in_array('ca_sets', $va_duplicate_relationships) || (isset($va_duplicate_element_settings['ca_sets_checklist']) && $va_duplicate_element_settings['ca_sets_checklist'])) {
			global $g_request;
			$user_id = $g_request ? $g_request->user->getUserID() : null;
			
			$t_set = new ca_sets();
			$set_ids = $t_set->getSets(['setIDsOnly' => true, 'table' => $this->tableName(), 'row_id' => $this->getPrimaryKey(), 'user_id' => $user_id, 'access' => __CA_SET_EDIT_ACCESS__]);
			if(is_array($set_ids)) {
				foreach($set_ids as $set_id) {
					if($t_set->load($set_id)) {
						$t_set->addItem($t_dupe->getPrimaryKey(), null, $user_id);
					}
				}
			}
		}
		
		// Set rank of duplicated record such that it immediately follows its original
		if($t_dupe->getProperty('RANK') && $this->isHierarchical() && ($vn_parent_id = $this->get($vs_parent_id_fld) > 0)) {
			$t_dupe->setRankAfter($this->getPrimaryKey());
		}
		
		if ($vb_duplicate_children && $this->isHierarchical() && ($child_ids = $this->getHierarchyChildren(null, ['idsOnly' => true]))) {
		    $dupe_id = $t_dupe->getPrimaryKey();
			foreach($child_ids as $child_id) {
				if ($t_child = $table::find([$vs_pk => $child_id], ['returnAs' => 'firstModelInstance'])) {
					$t_child_dupe = $t_child->duplicate($pa_options);
					if ($t_child->numErrors()) {
						$this->errors = $t_child->errors;
						if ($vb_we_set_transaction) { $o_t->rollback();}
						return false;
					}
					$t_child_dupe->set($vs_parent_id_fld, $dupe_id);
					$t_child_dupe->update(['forDuplication' => true]);
					if ($t_child_dupe->numErrors()) {
						$this->errors = $t_child_dupe->errors;
						if ($vb_we_set_transaction) { $o_t->rollback();}
						return false;
					}
				}
			}
		}
		
		if ($vb_we_set_transaction) { $o_t->commit();}
		return $t_dupe;
	}	
	# ------------------------------------------------------
	/**
	 * Overrides set() to check that the type field is not being set improperly
	 *
	 * @param array $pa_fields
	 * @param mixed $pm_value
	 * @param array $pa_options Options are passed directly to parent::set(); options specifically defined here are:
	 *		allowSettingOfTypeID = if true then type_id may be set for existing rows; default is to not allow type_id to be set for existing rows.
	 */
	public function set($pa_fields, $pm_value="", $pa_options=null) {
		if (!is_array($pa_fields)) {
			$pa_fields = array($pa_fields => $pm_value);
		}
		
		if (($vs_type_list_code = $this->getTypeListCode()) && ($vs_type_field_name = $this->getTypeFieldName())) {
			if (isset($pa_fields[$vs_type_field_name]) && !is_numeric($pa_fields[$vs_type_field_name])) {
				if ($vn_id = ca_lists::getItemID($vs_type_list_code, $pa_fields[$vs_type_field_name])) {
					$pa_fields[$vs_type_field_name] = $vn_id;
				}
			}
		}
		if ($this->getPrimaryKey() && !$this->isRelationship() && isset($pa_fields[$this->getTypeFieldName()]) && !(isset($pa_options['allowSettingOfTypeID']) && $pa_options['allowSettingOfTypeID'])) {
			$this->postError(2520, _t("Type id cannot be set after insert"), "BundlableLabelableBaseModelWithAttributes->set()", $this->tableName().'.'.$this->getTypeFieldName());
			return false;
		}
		
		if ($this->opo_idno_plugin_instance) {
			// If attempting to set parent_id, then flag record as child for id numbering purposes
			$this->opo_idno_plugin_instance->isChild(((($vs_parent_id_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD')) && isset($pa_fields[$vs_parent_id_fld]) && ($pa_fields[$vs_parent_id_fld] > 0)) || ($this->isChild())) ? true : null);
		
			if (in_array($this->getProperty('ID_NUMBERING_ID_FIELD'), $pa_fields)) {
				if (!$this->_validateIncomingAdminIDNo(true, false)) { 
					if (!$this->get($vs_parent_id_fld) && isset($pa_fields[$vs_parent_id_fld]) && ($pa_fields[$vs_parent_id_fld] > 0)) {
						// If we failed to set parent_id and there wasn't a parent_id set already then revert child status in id numbering
						$this->opo_idno_plugin_instance->isChild(false); 
					}
					return false; 
				}
			}
		}

		if ($vn_rc = parent::set($pa_fields, "", $pa_options)) {
			// Set type for idno purposes
			if (in_array($vs_type_field_name = $this->getTypeFieldName(), array_keys($pa_fields)) && $this->opo_idno_plugin_instance) {
				$this->opo_idno_plugin_instance->setType($this->getTypeCode());
			}
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Overrides get() to support bundleable-level fields (relationships)
	 *
	 * Options:
	 *		All supported by BaseModelWithAttributes::get() plus:
	 *		retrictToRelationshipTypes - array of ca_relationship_types.type_id values to filter related items on. *MUST BE INTEGER TYPE_IDs, NOT type_code's* This limitation is for performance reasons. You have to convert codes to integer type_id's before invoking get
	 *		sort = optional array of bundles to sort returned values on. Currently only supported when getting related values via simple related <table_name> and <table_name>.related invokations. Eg. from a ca_objects results you can use the 'sort' option got get('ca_entities'), get('ca_entities.related') or get('ca_objects.related'). The bundle specifiers are fields with or without tablename. Only those fields returned for the related tables (intrinsics and label fields) are sortable. You cannot sort on attributes.
  	 *		returnAsLink = if true and $ps_field is set to a specific field in a related table, or $ps_field is set to a related table (eg. ca_entities or ca_entities.related) AND the template option is set and returnAllLocales is not set, then returned values will be links. The destination of the link will be the appropriate editor when executed within Providence or the appropriate detail page when executed within Pawtucket or another front-end. Default is false.
 	 *		returnAsLinkText = text to use a content of HTML link. If omitted the url itself is used as the link content.
 	 *		returnAsLinkAttributes = array of attributes to include in link <a> tag. Use this to set class, alt and any other link attributes.
	 *		returnAsLinkTarget = Optional link target. If any plugin implementing hookGetAsLink() responds to the specified target then the plugin will be used to generate the links rather than CA's default link generator.
	 *		filterNonPrimaryRepresentations = Set filtering of non-primary representations in those models that support representations [Default is true]
	 */
	public function get($ps_field, $pa_options=null) {
		$vn_s = sizeof($va_tmp = explode('.', $ps_field));
		if ((($vn_s == 1) && ($vs_field = $ps_field)) || (($vn_s == 2) && ($va_tmp[0] == $this->TABLE) && ($vs_field = $va_tmp[1]))) {
			if ($this->hasField($vs_field) || (in_array(strtolower($vs_field), ['created', 'lastmodified']))) { return BaseModel::get($vs_field, $pa_options); }
		}
		if($this->_rowAsSearchResult) {
			if (method_exists($this->_rowAsSearchResult, "filterNonPrimaryRepresentations")) {
				$this->_rowAsSearchResult->filterNonPrimaryRepresentations(caGetOption('filterNonPrimaryRepresentations', $pa_options, true));
			}
			return $this->_rowAsSearchResult->get($ps_field, $pa_options);
		}
		return parent::get($ps_field, $pa_options);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getWithTemplate($ps_template, $pa_options=null) {
		if(!$this->getPrimaryKey()) { return null; }
		$vs_table_name = $this->tableName();	
		return caProcessTemplateForIDs($ps_template, $vs_table_name, array($this->getPrimaryKey()), $pa_options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _validateIncomingAdminIDNo($pb_post_errors=true, $pb_dont_validate_idnos_for_parents_in_mono_hierarchies=false) {
	
		// we should not bother to validate
		$vn_hier_type = $this->getHierarchyType();
		if ($pb_dont_validate_idnos_for_parents_in_mono_hierarchies && in_array($vn_hier_type, array(__CA_HIER_TYPE_SIMPLE_MONO__, __CA_HIER_TYPE_MULTI_MONO__)) && ($this->get('parent_id') == null)) { return true; }
		
		if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			$va_idno_errors = $this->validateAdminIDNo($this->get($vs_idno_field));
			if (sizeof($va_idno_errors) > 0) {
				if ($pb_post_errors) {
					foreach($va_idno_errors as $vs_e) {
						$this->postError(1100, $vs_e, "BundlableLabelableBaseModelWithAttributes->insert()", $this->tableName().'.'.$vs_idno_field);
					}
				}
				return false;
			}
		}
		return true;
	}	
	# --------------------------------------------------------------------------------
	/**
	 * Returns true if bundle is valid for this model
	 * 
	 * @access public
	 * @param string $ps_bundle bundle name
     * @param int $pn_type_id Optional record type
	 * @return bool
	 */ 
	public function hasBundle ($ps_bundle, $pn_type_id=null) {
		$va_bundle_bits = explode(".", $ps_bundle);
		$vn_num_bits = sizeof($va_bundle_bits);
	
		if (in_array($va_bundle_bits[1], array('hierarchy', 'parent', 'children', 'related'))) {
			unset($va_bundle_bits[1]);
			$va_bundle_bits = array_merge($va_bundle_bits);
			$vn_num_bits = sizeof($va_bundle_bits);
			$ps_bundle = join('.', $va_bundle_bits);
		}
		
		if (($va_bundle_bits[0] != $this->tableName()) && ($t_rel = Datamodel::getInstanceByTableName($va_bundle_bits[0], true))) {
			return ($vn_num_bits == 1) ? true : $t_rel->hasBundle($ps_bundle, $pn_type_id);
		} 
		return parent::hasBundle($ps_bundle, $pn_type_id);
	}
	# ------------------------------------------------------
	/** 
	 * Check if currently loaded row is a hierarchical child of another row (object or collection)
	 *
	 * @return bool
	 */
	public function isChild() : bool {
		$ret = parent::isChild();
		
		if(($parent_id_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD')) && method_exists($this, 'getIDNoPlugInInstance') && ($o_idno = $this->getIDNoPlugInInstance())) {
			$o_idno->isChild($ret, $ret ? self::getIdnoForID($this->get($parent_id_fld)) : null);
		}
		
		return $ret;
	}
	# ------------------------------------------------------------------
	/**
	  *
	  */
	public function getValuesForExport($options=null) {
		$va_data = parent::getValuesForExport($options);		// get intrinsics, attributes and labels
		
		if(caGetOption('includeRelationships', $options, true)) {
			$t_locale = new ca_locales();
			$t_list = new ca_lists();
		
			// get related items
			foreach(array('ca_objects', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations',  'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops',  'ca_list_items') as $vs_table) {
				$va_related_items = $this->getRelatedItems($vs_table, array('returnAsArray' => true, 'returnAllLocales' => true));
				if(is_array($va_related_items) && sizeof($va_related_items)) {
					$va_related_for_export = [];
					$vn_i = 0;
					foreach($va_related_items as $vs_key => $va_related_item) {
						$va_related_for_export['related_'.$vn_i] = $va_related_item;
						$vn_i++;
					}
				
					$va_data['related_'.$vs_table] = $va_related_for_export;
				}
			}
		}
		
		
		return $va_data;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function checkForDupeAdminIdnos($ps_idno=null, $pb_dont_remove_self=false) {
		if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			$o_db = $this->getDb();
			
			if (!$ps_idno) { $ps_idno = $this->get($vs_idno_field); }
			
			$params = [$ps_idno];
			$vs_remove_self_sql = '';
			if (!$pb_dont_remove_self) {
				$vs_remove_self_sql = ' AND ('.$this->primaryKey().' <> ?)';
				$params[] = (int)$this->getPrimaryKey();
			}
			
			$idno_context_fields = $this->getProperty('ID_NUMBERING_CONTEXT_FIELD') ? [$this->getProperty('ID_NUMBERING_CONTEXT_FIELD')] : [];
			$idno_context_values = [];
			
			if ($o_idno = $this->getIDNoPlugInInstance()) {
				if(is_array($elements = $o_idno->getElements($this->tableName(), ($t = $this->getTypeName()) ? $t : '__default__'))) {
			
					$seq_by_type = array_filter($elements, function($v) { return isset($v['sequence_by_type']) && (bool)$v['sequence_by_type']; });
					if(sizeof($seq_by_type) > 0) {
						$idno_context_fields[] = $type_fld_name = $this->getTypeFieldName();
						
						$sequence_by_type_ids = [];
						foreach($seq_by_type as $e) {
							$stypes = is_array($e['sequence_by_type']) ? $e['sequence_by_type'] : [$e['sequence_by_type']];
							if(is_array($t = caMakeTypeIDList($this->tableName(), $stypes, ['dontIncludeSubtypesInTypeRestriction' => (bool)$e['dont_include_subtypes']]))) {
								$sequence_by_type_ids = array_merge($sequence_by_type_ids, $t);
							}
						}
						
						if(sizeof($sequence_by_type_ids) > 0) {
							$idno_context_values[$type_fld_name] = $sequence_by_type_ids;
						}
					}
				}
				
			}
			
			$vs_idno_context_sql = '';
			if (sizeof($idno_context_fields) > 0) {
				foreach($idno_context_fields as $f) {
					if(isset($idno_context_values[$f])) {
						if(is_array($idno_context_values[$f]) && sizeof($idno_context_values[$f])) {
							$vs_idno_context_sql .= " AND ({$f} IN (?))";
						} else {
							$vs_idno_context_sql .= " AND ({$f} = ?)";
						}
						$params[] = $idno_context_values[$f];
					} elseif ($vn_context_id = $this->get($f)) {
						$vs_idno_context_sql .= " AND ({$f} = ?)";
						$params[] = $vn_context_id;
					} elseif ($this->getFieldInfo($f, 'IS_NULL')) {
						$vs_idno_context_sql .= " AND ({$f} IS ?)";
						$params[] = null;
					}
				}
			}
			
			$vs_deleted_sql = '';
			if ($this->hasField('deleted')) {
				$vs_deleted_sql = " AND (".$this->tableName().".deleted = 0)";
			}
			
			$qr_idno = $o_db->query("
				SELECT ".$this->primaryKey()." 
				FROM ".$this->tableName()." 
				WHERE {$vs_idno_field} = ? {$vs_remove_self_sql} {$vs_idno_context_sql} {$vs_deleted_sql}
			", $params);
			
			$va_ids = [];
			while($qr_idno->nextRow()) {
				$va_ids[] = $qr_idno->get($this->primaryKey());
			}
			if($o_idno && ($additional_tables = $o_idno->getFormatProperty('compare_against_values_in'))) {
				// other tables to check?
				if(is_array($additional_tables)) {
					foreach($additional_tables as $additional_table) {
						if($additional_table === $this->tableName()) { continue; }
						if(!($t = Datamodel::getInstance($additional_table, true))) { continue; }
						if(!($idno_field = $t->getProperty('ID_NUMBERING_ID_FIELD'))) { continue; }
						
						$deleted_sql = '';
						if ($t->hasField('deleted')) {
							$deleted_sql = " AND ({$additional_table}.deleted = 0)";
						}
						$qr_idno = $o_db->query("
							SELECT ".$t->primaryKey()." 
							FROM {$additional_table}
							WHERE {$idno_field} = ? {$deleted_sql}
						", [$ps_idno]);
			
						while($qr_idno->nextRow()) {
							$va_ids[] = "{$additional_table}-".$qr_idno->get($t->primaryKey());
						}
					}
				}
			}
			return $va_ids;
		} 
		
		return [];
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _generateSortableIdentifierValue() {
		if (($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) && ($vs_idno_sort_field = $this->getProperty('ID_NUMBERING_SORT_FIELD'))) {
			
			if (($o_idno = $this->getIDNoPlugInInstance()) && (method_exists($o_idno, 'getSortableValue'))) {	// try to use plug-in's sort key generator if defined
				$this->isChild();
				$this->set($vs_idno_sort_field, $o_idno->getSortableValue($this->get($vs_idno_field)));
				
				if($this->hasField("{$vs_idno_sort_field}_num") && (method_exists($o_idno, 'getSortableNumericValue'))) {
					if(($n = $o_idno->getSortableNumericValue($this->get($vs_idno_field))) < 0) { return; } 
					$this->set("{$vs_idno_sort_field}_num", $n);
				}
				return;
			}
			
			// Create reasonable facsimile of sortable value since 
			// idno plugin won't do it for us
			$va_tmp = preg_split('![^A-Za-z0-9]+!',  $this->get($vs_idno_field));
			
			$va_output = [];
			$va_zeroless_output = [];
			foreach($va_tmp as $vs_piece) {
				if (preg_match('!^([\d]+)!', $vs_piece, $va_matches)) {
					$vs_piece = $va_matches[1];
				}
				$vn_pad_len = 12 - mb_strlen($vs_piece);
				
				if ($vn_pad_len >= 0) {
					if (is_numeric($vs_piece)) {
						$va_output[] = str_repeat(' ', $vn_pad_len).$va_matches[1];
					} else {
						$va_output[] = $vs_piece.str_repeat(' ', $vn_pad_len);
					}
				} else {
					$va_output[] = $vs_piece;
				}
				if ($vs_tmp = preg_replace('!^[0]+!', '', $vs_piece)) {
					$va_zeroless_output[] = $vs_tmp;
				} else {
					$va_zeroless_output[] = $vs_piece;
				}
			}
		
			$this->set($vs_idno_sort_field, join('', $va_output).' '.join('.', $va_zeroless_output));
		}
		
		return;
	}
	# ------------------------------------------------------
	/**
	 * Check if a record already exists with the specified label
	 *
	 * @param int $pn_locale_id
	 * @param array $pa_label_values
	 * @param bool $pb_preferred_only
	 * @return bool
	 */
	public function checkForDupeLabel($pn_locale_id, $pa_label_values, $pb_preferred_only=true) {
		$o_db = $this->getDb();
		$t_label = $this->getLabelTableInstance();
		if(!empty($pa_label_values['surname']) || !empty($pa_label_values['forename'])) { unset($pa_label_values['displayname']); }
		
		$va_wheres = [];
		foreach($pa_label_values as $vs_field => $vs_value) {
			$va_wheres[] = "(l.{$vs_field} = ?)";
		}
		if($pb_preferred_only) {
			if ($t_label->hasField('is_preferred')) {
				$va_wheres[] = "(l.is_preferred = 1)";
			}
		}
		if($pn_locale_id && $t_label->hasField('locale_id')) {
			$va_wheres[] = "(l.locale_id = ?)";
		}
		if ($this->hasField('deleted')) { $va_wheres[] = "(t.deleted = 0)"; }
		if ($this->getPrimaryKey()) {
			$va_wheres[] = "(l.".$this->primaryKey()." <> ?)";
		}
		$vs_sql = "SELECT ".$t_label->primaryKey()."
			FROM ".$t_label->tableName()." l
			INNER JOIN ".$this->tableName()." AS t ON t.".$this->primaryKey()." = l.".$this->primaryKey()."
			WHERE ".join(' AND ', $va_wheres);
			
		// normalize nulls to blanks for comparison purposes
		$va_values = array_map(function($v) { return is_null($v) ? '' : $v; }, array_values($pa_label_values));	
		
		if($pn_locale_id && $t_label->hasField('locale_id')) {
			$va_values[] = (int)$pn_locale_id;
		}
		if ($this->getPrimaryKey()) {
			$va_values[] = (int)$this->getPrimaryKey();
		}
		$qr_res = $o_db->query($vs_sql, $va_values);
		if ($qr_res->numRows() > 0) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function reloadLabelDefinitions() {
		$this->initLabelDefinitions(array('dontCache' => true));
	}
	# ------------------------------------------------------
	/**
	 * @param array $options Options include:
	 *		dontInitMetadataElements = Don't add metadata elements to definition list. Used by installer when checking bundles to avoid hitting the database for metadata element definitions before database tables are created. [Default is false]
	 */
	protected function initLabelDefinitions($pa_options=null) {
		$pb_dont_cache = caGetOption('dontCache', $pa_options, false);

		$this->BUNDLES = (is_subclass_of($this, "BaseRelationshipModel")) ? [] : array(
			'preferred_labels' 			=> array('type' => 'preferred_label', 'repeating' => true, 'label' => _t("Preferred labels")),
			'nonpreferred_labels' 		=> array('type' => 'nonpreferred_label', 'repeating' => true,  'label' => _t("Non-preferred labels")),
		);
		
		// add form fields to bundle list
		foreach($this->getFormFields() as $vs_f => $va_info) {
			$vs_type_id_fld = isset($this->ATTRIBUTE_TYPE_ID_FLD) ? $this->ATTRIBUTE_TYPE_ID_FLD : null;
			if ($vs_f === $vs_type_id_fld) { continue; } 	// don't allow type_id field to be a bundle (isn't settable in a form)
			if (isset($va_info['DONT_USE_AS_BUNDLE']) && $va_info['DONT_USE_AS_BUNDLE']) { continue; }
			$this->BUNDLES[$vs_f] = array(
				'type' => 'intrinsic', 'repeating' => false, 'label' => $this->getFieldInfo($vs_f, 'LABEL')
			);
		}
		
		$vn_type_id = $this->getTypeID();
		
		// Create instance of idno numbering plugin (if table supports it)
		if ($vs_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			if (!$vn_type_id) { $vn_type_id = null; }
			//$va_types = [];
			$va_types = $vn_type_id ? caMakeTypeList($this->tableName(), [$vn_type_id]) : null;
			$this->opo_idno_plugin_instance = IDNumbering::newIDNumberer($this->tableName(), $va_types, null, $o_db);
		} else {
			$this->opo_idno_plugin_instance = null;
		}
		
		// add metadata elements
		if(!caGetOption('dontInitMetadataElements', $pa_options, false)) {
			foreach($this->getApplicableElementCodes($vn_type_id, false, $pb_dont_cache) as $vs_code) {
				$this->BUNDLES['ca_attribute_'.$vs_code] = array(
					'type' => 'attribute', 'repeating' => false, 'label' => $vs_code
				);
			}
		}
	}
	# ---------------------------------------------------------------------------------------------
	/**
 	 * Check if currently loaded row is readable
 	 *
 	 * @param RequestHTTP|ca_user $po_request
 	 * @param string $ps_bundle_name Optional bundle name to test readability on. If omitted readability is considered for the item as a whole.
 	 * @return bool True if record can be read by the current user, false if not
 	 */
	function isReadable($po_request, $ps_bundle_name=null) {
 		$t_user = is_a($po_request, 'ca_users') ? $po_request : $po_request->user;
 		
		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $t_user->getTypeAccessLevel($this->tableName(), $this->getTypeID());
			if ($vn_type_access < __CA_BUNDLE_ACCESS_READONLY__) {
				return false;
			}
		}
		
		// Check source restrictions
 		if ((bool)$this->getAppConfig()->get('perform_source_access_checking')) {
			$vn_source_access = $t_user->getSourceAccessLevel($this->tableName(), $this->getSourceID());
			if ($vn_source_access < __CA_BUNDLE_ACCESS_READONLY__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking')) {
			$vn_item_access = $this->checkACLAccessForUser($t_user);
			if ($vn_item_access < __CA_ACL_READONLY_ACCESS__) {
				return false;
			}
		}
		
		if ($ps_bundle_name) {
			if ($t_user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) < __CA_BUNDLE_ACCESS_READONLY__) { return false; }
		}
		
		if ((defined("__CA_APP_TYPE__") && (__CA_APP_TYPE__ == "PAWTUCKET") && ($this->hasField('access')))) {
			$va_access = caGetUserAccessValues($po_request);
			if (is_array($va_access) && sizeof($va_access) && !in_array($this->get('access'), $va_access)) { return false; }
		}
		
		return true;
	}
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is save-able
 	 *
 	 * @param RequestHTTP|ca_user $po_request
 	 * @param string $ps_bundle_name Optional bundle name to test write-ability on. If omitted write-ability is considered for the item as a whole.
 	 * @return bool True if record can be saved, false if not
 	 */
 	public function isSaveable($po_request, $ps_bundle_name=null) {
 		$t_user = is_a($po_request, 'ca_users') ? $po_request : $po_request->user;
 		if (!$t_user) { return false; }
 		
 		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $t_user->getTypeAccessLevel($this->tableName(), $this->getTypeID());
			if ($vn_type_access != __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check source restrictions
 		if ((bool)$this->getAppConfig()->get('perform_source_access_checking')) {
			$vn_source_access = $t_user->getSourceAccessLevel($this->tableName(), $this->getSourceID());
			if ($vn_source_access < __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking') && $this->getPrimaryKey()) {
			$vn_item_access = $this->checkACLAccessForUser($t_user);
			if ($vn_item_access < __CA_ACL_EDIT_ACCESS__) {
				return false;
			}
		}
		
 		// Check actions
 		if (!$this->getPrimaryKey() && !$t_user->canDoAction('can_create_'.$this->tableName())) {
 			return false;
 		}
 		if ($this->getPrimaryKey() && !$t_user->canDoAction('can_edit_'.$this->tableName())) {
 			return false;
 		}
 		
		if ($ps_bundle_name) {
			if ($t_user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) < __CA_BUNDLE_ACCESS_EDIT__) { return false; }
		}
 		
 		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is deletable
 	 *
 	 * @param RequestHTTP|ca_user $po_request
 	 * @return bool True if record can be deleted, false if not
 	 */
 	public function isDeletable($po_request) {
 		$t_user = is_a($po_request, 'ca_users') ? $po_request : $po_request->user;
 		
 		// Is row loaded?
 		if (!$this->getPrimaryKey()) { return false; }
 		
 		$table = $this->tableName();
 		$config = $this->getAppConfig();
 		
 		if($config->get("{$table}_disable_delete")) { return false; }
 		if($config->get("{$table}_".$this->getTypeCode()."_disable_delete")) { return false; }
 		
 		// Check type restrictions
 		if ((bool)$config->get('perform_type_access_checking')) {
			$vn_type_access = $t_user->getTypeAccessLevel($table, $this->getTypeID());
			if ($vn_type_access != __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check source restrictions
 		if ((bool)$config->get('perform_source_access_checking')) {
			$vn_source_access = $t_user->getSourceAccessLevel($table, $this->getSourceID());
			if ($vn_source_access < __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if ((bool)$config->get('perform_item_level_access_checking') && $this->getPrimaryKey()) {
			$vn_item_access = $this->checkACLAccessForUser($t_user);
			if ($vn_item_access < __CA_ACL_EDIT_DELETE_ACCESS__) {
				return false;
			}
		}
		
 		// Check actions
 		if (!$this->getPrimaryKey() || !$t_user->canDoAction("can_delete_{$table}")) {
 			return false;
 		}
 		
 		return true;
 	}
	# ------------------------------------------------------
	/**
	 * Returns values for bundle. Can be used to set initial state of bundle as well as to grab partial value sets for
	 * progressive loading of bundles.
	 *
	 * NOTE: Currently only support ca_object_representations bundle
	 *
	 */
	public function getBundleFormValues($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options=null) {
		global $g_ui_locale;
		
		// Check if user has access to this bundle
		if ($pa_options['request']->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) == __CA_BUNDLE_ACCESS_NONE__) {
			return;
		}
		
		// Check if user has access to this type
		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $pa_options['request']->user->getTypeAccessLevel($this->tableName(), $this->getTypeID());
			if ($vn_type_access == __CA_BUNDLE_ACCESS_NONE__) {
				return;
			}
			if ($vn_type_access == __CA_BUNDLE_ACCESS_READONLY__) {
				$pa_bundle_settings['readonly'] = true;
			}
		}
		
		// Check if user has access to this source
		if ((bool)$this->getAppConfig()->get('perform_source_access_checking')) {
			$vn_source_access = $pa_options['request']->user->getSourceAccessLevel($this->tableName(), $this->getSourceID());
			if ($vn_source_access == __CA_BUNDLE_ACCESS_NONE__) {
				return;
			}
			if ($vn_source_access == __CA_BUNDLE_ACCESS_READONLY__) {
				$pa_bundle_settings['readonly'] = true;
			}
		}
		
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking') && $this->getPrimaryKey()) {
			$vn_item_access = $this->checkACLAccessForUser($pa_options['request']->user);
			if ($vn_item_access == __CA_ACL_NO_ACCESS__) {
				return; 
			}
			if ($vn_item_access == __CA_ACL_READONLY_ACCESS__) {
				$pa_bundle_settings['readonly'] = true;
			}
		}
		
		$va_info = $this->getBundleInfo($ps_bundle_name);
		if (!($vs_type = $va_info['type'])) { return null; }
		
		
		if (isset($pa_options['config']) && is_object($pa_options['config'])) {
			$o_config = $pa_options['config'];
		} else {
			$o_config = $this->getAppConfig();
		}
		
		
		// start and count
		$pn_start = caGetOption('start', $pa_options, 0);
		$pn_limit = caGetOption('limit', $pa_options, null);
		$vs_element = '';
		switch($vs_type) {
			# -------------------------------------------------
			case 'preferred_label':
			case 'nonpreferred_label':
				
				break;
			# -------------------------------------------------
			case 'intrinsic':
				
				break;
			# -------------------------------------------------
			case 'attribute':
				
				break;
			# -------------------------------------------------
			case 'related_table':
				switch($ps_bundle_name) {
					# -------------------------------
					case 'ca_object_representations':
						return parent::getRelatedBundleFormValues($pa_options['request'], $pa_options['formName'], $ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					case 'ca_entities':
					case 'ca_places':
					case 'ca_occurrences':
					case 'ca_objects':
					case 'ca_collections':
					case 'ca_list_items':
					case 'ca_storage_locations':
					case 'ca_loans':
					case 'ca_movements':
					case 'ca_tour_stops':
					case 'ca_sets':
					case 'ca_objects_table': // for compatibility with first version
					case 'ca_objects_related_list':
					case 'ca_object_representations_related_list':
					case 'ca_entities_related_list':
					case 'ca_places_related_list':
					case 'ca_occurrences_related_list':
					case 'ca_collections_related_list':
					case 'ca_list_items_related_list':
					case 'ca_storage_locations_related_list':
					case 'ca_loans_related_list':
					case 'ca_movements_related_list':
					case 'ca_object_lots_related_list':
						return $this->getRelatedBundleFormValues($pa_options['request'], $pa_options['formName'], $ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					case 'ca_object_lots':
						
						break;
					# -------------------------------
					case 'ca_representation_annotations':
						
						break;
					# -------------------------------
					default:
						return null;
						break;
					# -------------------------------
				}
				
				break;
			
			# -------------------------------------------------
			case 'special':
				switch($ps_bundle_name) {
				    case 'ca_site_page_media':
				        return parent::getBundleFormValues($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);
				        break;
				}
				break;
			# -------------------------------------------------
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * @param string $ps_bundle_name
	 * @param string $ps_placement_code
	 * @param array $pa_bundle_settings
	 * @param array $pa_options Supported options are:
	 *		config
	 *		viewPath
	 *		graphicsPath
	 *		request
	 */
	public function getBundleFormHTML($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options=null, &$ps_bundle_label=null) {
		global $g_ui_locale, $g_ui_locale_id;
		if (!is_array($pa_bundle_settings)) { $pa_bundle_settings = []; }
		
		$vb_batch = (isset($pa_options['batch']) && $pa_options['batch']) ? true : false;
		
		// Check if user has access to this bundle
		if ($pa_options['request']->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) == __CA_BUNDLE_ACCESS_NONE__) {
			return;
		}
		
		$bundle_code = $ps_bundle_name;
		
		$vb_read_only_because_deaccessioned = ($this->hasField('is_deaccessioned') && (bool)$this->getAppConfig()->get('deaccession_dont_allow_editing') && (bool)$this->get('is_deaccessioned'));
		if($vb_read_only_because_deaccessioned && ($ps_bundle_name != 'ca_objects_deaccession')) {
			$pa_bundle_settings['readonly'] = true;
		}
		
		// Check if user has access to this type
		// The type id is not set for batch edits so skip this check for those.
		if (!$vb_batch && (bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $pa_options['request']->user->getTypeAccessLevel($this->tableName(), $this->getTypeID());
			if ($vn_type_access == __CA_BUNDLE_ACCESS_NONE__) {
				return;
			}
			if ($vn_type_access == __CA_BUNDLE_ACCESS_READONLY__) {
				$pa_bundle_settings['readonly'] = true;
			}
		}
		
		// Check if user has access to this source
		if ((bool)$this->getAppConfig()->get('perform_source_access_checking')) {
			$vn_source_access = $pa_options['request']->user->getSourceAccessLevel($this->tableName(), $this->getSourceID());
			if ($vn_source_access == __CA_BUNDLE_ACCESS_NONE__) {
				return;
			}
			if ($vn_source_access == __CA_BUNDLE_ACCESS_READONLY__) {
				$pa_bundle_settings['readonly'] = true;
			}
		}
		
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking') && $this->getPrimaryKey()) {
			$vn_item_access = $this->checkACLAccessForUser($pa_options['request']->user);
			if ($vn_item_access == __CA_ACL_NO_ACCESS__) {
				return; 
			}
			if ($vn_item_access == __CA_ACL_READONLY_ACCESS__) {
				$pa_bundle_settings['readonly'] = true;
			}
		}
		
		// convert intrinsics to bare field names if they include tablename (eg. ca_objects.idno => idno)
		$va_tmp = explode('.', $ps_bundle_name);
		if (($this->tableName() === $va_tmp[0]) && $this->hasField($va_tmp[1])) {
			$ps_bundle_name = $va_tmp[1];
		}
		
		$va_info = $this->getBundleInfo($ps_bundle_name);
		if (!($vs_type = $va_info['type'])) { return null; }
		
		
		if (isset($pa_options['config']) && is_object($pa_options['config'])) {
			$o_config = $pa_options['config'];
		} else {
			$o_config = $this->getAppConfig();
		}
		
		if (!($vs_required_marker = $o_config->get('required_field_marker'))) {
			$vs_required_marker = '['._t('REQUIRED').']';
		}
		
		$vs_label = $vs_label_text = null;
		
		
		// Bundle names for attributes are element codes. They may be prefixed with 'ca_attribute_' in older installations.
		// Since various functions take straight element codes we have to strip the prefix here
		$ps_bundle_name_proc = str_replace("ca_attribute_", "", $ps_bundle_name);
		$va_violations = null;
		
		if (
			($va_dictionary_entry = ca_metadata_dictionary_entries::getEntry($dict_bundle_spec = $ps_bundle_name_proc, $this, $pa_bundle_settings))
			||
			($va_dictionary_entry = ca_metadata_dictionary_entries::getEntry($dict_bundle_spec = $this->tableName().'.'.$ps_bundle_name_proc, $this, $pa_bundle_settings))
		) {
			# Grab definition out of dictionary entry settings: if it was created in a system with multiple locales the available definitions 
			# will be key'ed by locale code or locale_id (argh). If it was created in an older system with only a single active locale it may
			# be a simple string. In the future settings should be normalized such that any value that may be localized is an array key'ed by locale code,
			# but since we're in the present we check for and handle all three current possibilities here.
			if (!($pa_bundle_settings['definition'][$g_ui_locale] = caExtractSettingsValueByUserLocale('definition', $va_dictionary_entry['settings']))) {
			    $pa_bundle_settings['definition'][$g_ui_locale] = $va_dictionary_entry['settings']['definition'];
			}
			if (caGetOption('mandatory', $va_dictionary_entry['settings'], false)) {
				$def = caExtractSettingsValueByUserLocale('definition', $pa_bundle_settings);
				$pa_bundle_settings['definition'][$g_ui_locale] = $this->getAppConfig()->get('required_field_marker').(is_array($def) ? $def[$g_ui_locale] : $def);
			}
			
			$va_violations = $this->getMetadataDictionaryRuleViolations($dict_bundle_spec, ['placement_id' => (int)str_replace("P", "", $ps_placement_code)]);
			if (is_array($va_violations) && sizeof($va_violations)) {
				$va_violation_text = [];
				foreach($va_violations as $va_violation) {
					$va_violation_text[] = "<li class='caMetadataDictionaryViolation'><span class='caMetadataDictionaryViolation".(ucfirst(strtolower($va_violation['level'])))."'>".$va_violation['levelDisplay'].'</span>: '.caExtractSettingsValueByUserLocale('violationMessage', $va_violation)."</li>";
				}
				$pa_bundle_settings['definition'][$g_ui_locale] = "<div class='caMetadataDictionaryViolationsList'><div class='caMetadataDictionaryViolationsListHeading'>"._t('These problems require attention:')."</div><ol>".join("\n", $va_violation_text)."</ol></div>\n".$pa_bundle_settings['definition'][$g_ui_locale]."<br style='clear: both;'/>";
			}
		}
		
		// is label for this bundle forced in bundle settings?
		$vs_label = $vs_label_text = caExtractSettingsValueByUserLocale('label', $pa_bundle_settings);
		
		// Set bundle level documentation URL
		$vs_documentation_url =  trim((isset($pa_bundle_settings['documentation_url']) && $pa_bundle_settings['documentation_url']) ? $pa_bundle_settings['documentation_url']  : '');
		
		$vs_element = '';
		$va_errors = [];
		switch($vs_type) {
			# -------------------------------------------------
			case 'preferred_label':
			case 'nonpreferred_label':
				if (is_array($va_error_objects = $pa_options['request']->getActionErrors($ps_bundle_name)) && sizeof($va_error_objects)) {
					$vs_display_format = $o_config->get('bundle_element_error_display_format');
					foreach($va_error_objects as $o_e) {
						$va_errors[] = $o_e->getErrorDescription();
					}
				} else {
					$vs_display_format = $o_config->get('bundle_element_display_format');
				}
				
				$pa_options['dontCache'] = true;	// we *don't* want to cache labels here
				$vs_element = ($vs_type === 'preferred_label') ? $this->getPreferredLabelHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options) : $this->getNonPreferredLabelHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
			
				$vs_field_id = "ca_{$vs_type}_".$pa_options['formName']."_{$ps_placement_code}";
				if (!$vs_label_text) {  $vs_label_text = $va_info['label']; } 
				$vs_label = '<span class="formLabelText" id="'.$vs_field_id.'">'.$vs_label_text.'</span>'; 
				
				if (($vs_type == 'preferred_label') && $o_config->get('show_required_field_marker') && $o_config->get('require_preferred_label_for_'.$this->tableName())) {
					$vs_label .= ' '.$vs_required_marker;
				}
				
				$vs_description = caExtractSettingValueByLocale($pa_bundle_settings, 'description', $g_ui_locale);
				if (($vs_label) && ($vs_description)) { 
					TooltipManager::add('#'.$vs_field_id, "<h3>{$vs_label_text}</h3>{$vs_description}");
				}
				
				$bundle_code = $this->tableName().".{$bundle_code}";
				break;
			# -------------------------------------------------
			case 'intrinsic':
				if (!($pa_options['label'] = caExtractSettingValueByLocale($pa_bundle_settings, 'label', $g_ui_locale))) {
					$pa_options['label'] = $this->getFieldInfo($ps_bundle_name, 'LABEL');
				}
				
				$bundle_code = $this->tableName().".{$bundle_code}";
						
				$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $pa_options['request']->getViewsDirectoryPath();
				$o_view = new View($pa_options['request'], "{$vs_view_path}/bundles/");
			
			
				$custom_view_exists = ($o_view->viewExists($s = $this->tableName()."_{$ps_bundle_name}.php"));
					
				$va_lookup_url_info = caJSONLookupServiceUrl($pa_options['request'], $this->tableName());
				
				if ($this->getFieldInfo($ps_bundle_name, 'IDENTITY')) {
					$o_view->setVar('form_element', ($vn_id = (int)$this->get($ps_bundle_name)) ? $vn_id : "&lt;"._t('Not yet issued')."&gt;");
				} else {
					$vb_read_only = ($pa_bundle_settings['readonly'] || ($pa_options['request']->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) == __CA_BUNDLE_ACCESS_READONLY__)) ? true : false;

					$va_additional_field_options = [];
					if($vn_width = caGetOption('width', $pa_bundle_settings)){
						$va_additional_field_options['width'] = $vn_width;
					}
					if($vn_height = caGetOption('height', $pa_bundle_settings)){
						$va_additional_field_options['height'] = $vn_height;
					}
					
					$o_view->setVar('form_element', $this->htmlFormElement($ps_bundle_name, ($this->getProperty('ID_NUMBERING_ID_FIELD') == $ps_bundle_name) ? $o_config->get('idno_element_display_format_without_label') : $o_config->get('bundle_element_display_format_without_label'), 
						array_merge(
							[	
								'readonly' 					=> $vb_read_only,						
								'error_icon' 				=> caNavIcon(__CA_NAV_ICON_ALERT__, 1),
								'progress_indicator'		=> caNavIcon(__CA_NAV_ICON_SPINNER__, 1),
								'lookup_url' 				=> $va_lookup_url_info['intrinsic'],
								
								'name'						=> $ps_placement_code.$pa_options['formName'].$ps_bundle_name,
								'usewysiwygeditor' 			=> $pa_bundle_settings['usewysiwygeditor']
							],
							$pa_options,
							$va_additional_field_options
						)
					));
					
					if ($custom_view_exists) {
						$o_view->setVar('form_element_raw', $this->htmlFormElement($ps_bundle_name, '^ELEMENT', 
							array_merge(
								array(	
									'readonly' 					=> $vb_read_only,						
									'error_icon' 				=> caNavIcon(__CA_NAV_ICON_ALERT__, 1),
									'progress_indicator'		=> caNavIcon(__CA_NAV_ICON_SPINNER__, 1),
									'lookup_url' 				=> $va_lookup_url_info['intrinsic'],
								
									'name'						=> $ps_placement_code.$pa_options['formName'].$ps_bundle_name,
									'usewysiwygeditor' 			=> $pa_bundle_settings['usewysiwygeditor']
								),
								$pa_options,
								$va_additional_field_options
							)
						));
					}
				}
				$o_view->setVar('errors', $pa_options['request']->getActionErrors($ps_bundle_name));
				if (method_exists($this, "getDefaultMediaPreviewVersion")) {
					$o_view->setVar('display_media', $this->getMediaTag($ps_bundle_name, $this->getDefaultMediaPreviewVersion($ps_bundle_name)));
				}
				
				$vs_field_id = 'ca_intrinsic_'.$pa_options['formName'].'_'.$ps_placement_code;
				$vs_label = '<span class="formLabelText" id="'.$vs_field_id.'">'.$pa_options['label'].'</span>'; 
				
				if ($o_config->get('show_required_field_marker')) {
					if (($this->getFieldInfo($ps_bundle_name, 'FIELD_TYPE') == FT_TEXT) && is_array($va_bounds =$this->getFieldInfo($ps_bundle_name, 'BOUNDS_LENGTH')) && ($va_bounds[0] > 0)) {
						$vs_label .= ' '.$vs_required_marker;
					} else {
						if ((in_array($this->getFieldInfo($ps_bundle_name, 'FIELD_TYPE'), array(FT_NUMBER, FT_HISTORIC_DATERANGE, FT_DATERANGE)) && !$this->getFieldInfo($ps_bundle_name, 'IS_NULL'))) {
							$vs_label .= ' '.$vs_required_marker;
						}
					}
				}
				
				// Set access inheritance default
				if ((bool)$this->getAppConfig()->get($this->tableName().'_allow_access_inheritance') && $this->hasField('access_inherit_from_parent') && !$this->getPrimaryKey()) {
					$this->set('access_inherit_from_parent', (bool)$this->getAppConfig()->get($this->tableName().'_access_inheritance_default') ? 1 : 0);
				}
				
				$o_view->setVar('bundle_name', $ps_bundle_name);
				
				$o_view->setVar('id_prefix', $pa_options['formName']);
				$o_view->setVar('placement_code', $ps_placement_code);
				
				$o_view->setVar('settings', $pa_bundle_settings);
				$o_view->setVar('t_instance', $this);
				$o_view->setVar('batch', (bool)(isset($pa_options['batch']) && $pa_options['batch']));
				
				$vs_element = $custom_view_exists ? $o_view->render($s, true) : $o_view->render('intrinsic.php', true);
				
				
				if(!($vs_description =  caExtractSettingValueByLocale($pa_bundle_settings, 'description', $g_ui_locale))) {
					$vs_description = $this->getFieldInfo($ps_bundle_name, 'DESCRIPTION');
				}
				
				if (($pa_options['label']) && ($vs_description)) {
					TooltipManager::add('#'.$vs_field_id, "<h3>".$pa_options['label']."</h3>{$vs_description}");
				}
				
				if (isset($pa_bundle_settings['forACLAccessScreen']) && $pa_bundle_settings['forACLAccessScreen']) {
					$vs_display_format = '^ELEMENT';
				} else {
					$vs_display_format = $o_config->get('bundle_element_display_format');
				}
				break;
			# -------------------------------------------------
			case 'attribute':
				// Bundle names for attributes are element codes. They may be prefixed with 'ca_attribute_' in older installations.
				// Since getAttributeHTMLFormBundle() takes a straight element code we have to strip the prefix here
				$vs_attr_element_code = str_replace('ca_attribute_', '', $ps_bundle_name);
				$vs_display_format = $o_config->get('bundle_element_display_format');
				
				$bundle_code = $this->tableName().".{$vs_attr_element_code}";
				
				if(!strlen($vs_element = $this->getAttributeHTMLFormBundle($pa_options['request'], $pa_options['formName'], $vs_attr_element_code, $ps_placement_code, $pa_bundle_settings, $pa_options))) {
					// No bundle?
					return null; 
				}
				$vs_field_id = 'ca_attribute_'.$pa_options['formName'].'_'.$vs_attr_element_code;
				
				if (!$vs_label_text) { $vs_label_text = $this->getAttributeLabel($vs_attr_element_code); }
				
				if ($vb_batch) {
					$t_element = ca_metadata_elements::getInstance($vs_attr_element_code);
					$va_type_restrictions = $t_element->getTypeRestrictionsForDisplay($this->tableNum());
					if (sizeof($va_type_restrictions)) {
						$vs_restriction_list = join("; ", $va_type_restrictions);
						$vs_label = '<span class="formLabelText" id="'.$vs_field_id.'">'.$vs_label_text.'</span> <span class="formLabelSubtext" id="subtext_'.$vs_field_id.'">('.caTruncateStringWithEllipsis($vs_restriction_list, 75).')</span>'; 
						TooltipManager::add("#subtext_{$vs_field_id}", "<h3>"._t("Restricted to types")."</h3>".join("<br/>", $va_type_restrictions));
					} else {
						$vs_label = '<span class="formLabelText" id="'.$vs_field_id.'">'.$vs_label_text.'</span>'; 
					}
				} else {
					$vs_label = '<span class="formLabelText" id="'.$vs_field_id.'">'.$vs_label_text.'</span>'; 
				}

				// Fall back to element description if applicable
				if(!($vs_description =  caExtractSettingValueByLocale($pa_bundle_settings, 'description', $g_ui_locale))) {
					$vs_description = $this->getAttributeDescription($vs_attr_element_code);
				}

                $vs_documentation_url =  trim((isset($pa_bundle_settings['documentation_url']) && $pa_bundle_settings['documentation_url']) ? $pa_bundle_settings['documentation_url']  : $vs_documentation_url = $this->getAttributeDocumentationUrl($vs_attr_element_code));

				if ($t_element = ca_metadata_elements::getInstance($vs_attr_element_code)) {
					if ($o_config->get('show_required_field_marker') && (($t_element->getSetting('minChars') > 0) || ((bool)$t_element->getSetting('mustNotBeBlank')) || ((bool)$t_element->getSetting('requireValue')))) { 
						$vs_label .= ' '.$vs_required_marker;
					}
				}
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$vs_field_id, "<h3>{$vs_label_text}</h3>{$vs_description}");
				}
		
				break;
			# -------------------------------------------------
			case 'related_table':
				if (is_array($va_error_objects = $pa_options['request']->getActionErrors($ps_bundle_name, 'general')) && sizeof($va_error_objects)) {
					$vs_display_format = $o_config->get('bundle_element_error_display_format');
					foreach($va_error_objects as $o_e) {
						$va_errors[] = $o_e->getErrorDescription();
					}
				} else {
					$vs_display_format = $o_config->get('bundle_element_display_format');
				}
				
				$bundle_code = $ps_bundle_name;
				
				switch($ps_bundle_name) {
					# -------------------------------
					case 'ca_object_representations':
					case 'ca_entities':
					case 'ca_places':
					case 'ca_occurrences':
					case 'ca_objects':
					case 'ca_collections':
					case 'ca_list_items':
					case 'ca_storage_locations':
					case 'ca_loans':
					case 'ca_movements':
					case 'ca_tour_stops':
					case 'ca_sets':
						if (($this->_CONFIG->get($ps_bundle_name.'_disable')) && ($ps_bundle_name !== 'ca_object_representations')) { return ''; }		// don't display if master "disable" switch is set
						$pa_options['start'] = 0; $pa_options['limit'] = caGetOption('numPerPage', $pa_bundle_settings, 10);
						$vs_element = $this->getRelatedHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);	
						break;
					# -------------------------------
					case 'ca_object_lots':
						if ($this->_CONFIG->get($ps_bundle_name.'_disable')) { break; }		// don't display if master "disable" switch is set
						
						$pa_lot_options = array('batch' => $vb_batch);
						if (($this->tableName() != 'ca_object_lots') && ($vn_lot_id = $pa_options['request']->getParameter('lot_id', pInteger))) {
							$pa_lot_options['force'][] = $vn_lot_id;
						}
						$vs_element = $this->getRelatedHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_bundle_name, $ps_placement_code, $pa_bundle_settings, array_merge($pa_options, $pa_lot_options));	
						break;
					# -------------------------------
					case 'ca_representation_annotations':
						//if (!method_exists($this, "getAnnotationType") || !$this->getAnnotationType()) { continue; }	// don't show bundle if this representation doesn't support annotations
						//if (!method_exists($this, "useBundleBasedAnnotationEditor") || !$this->useBundleBasedAnnotationEditor()) { continue; }	// don't show bundle if this representation doesn't use bundles to edit annotations
						
						$pa_options['fields'] = array('ca_representation_annotations.status', 'ca_representation_annotations.access', 'ca_representation_annotations.props', 'ca_representation_annotations.representation_id');
						
						$vs_element = $this->getRepresentationAnnotationHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);	

						break;
					# -------------------------------
					case 'ca_objects_table': // for compatibility with first version
					case 'ca_objects_related_list':
					case 'ca_object_representations_related_list':
					case 'ca_entities_related_list':
					case 'ca_places_related_list':
					case 'ca_occurrences_related_list':
					case 'ca_collections_related_list':
					case 'ca_list_items_related_list':
					case 'ca_storage_locations_related_list':
					case 'ca_loans_related_list':
					case 'ca_movements_related_list':
					case 'ca_tour_stops_related_list':
					case 'ca_object_lots_related_list':
						$vs_table_name = preg_replace("/_related_list|_table$/", '', $ps_bundle_name);
						if (($this->_CONFIG->get($vs_table_name. '_disable'))) { return ''; }		// don't display if master "disable" switch is set
						$pa_options['start'] = 0;
						$vs_element = $this->getRelatedListHTMLFormBundle($pa_options['request'], $ps_bundle_name, $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					default:
						$vs_element = "'{$ps_bundle_name}' is not a valid related-table bundle name";
						break;
					# -------------------------------
				}
				
				if (!$vs_label_text) { $vs_label_text = $va_info['label']; }				
				$vs_label = '<span class="formLabelText" id="'.$pa_options['formName'].'_'.$ps_placement_code.'">'.$vs_label_text.'</span>'; 
				
				$vs_description = caExtractSettingValueByLocale($pa_bundle_settings, 'description', $g_ui_locale);
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$pa_options['formName'].'_'.$ps_placement_code, "<h3>{$vs_label}</h3>{$vs_description}");
				}
				break;
			# -------------------------------------------------
			case 'special':
				if (is_array($va_error_objects = $pa_options['request']->getActionErrors($ps_bundle_name, 'general')) && sizeof($va_error_objects)) {
					$vs_display_format = $o_config->get('bundle_element_error_display_format');
					foreach($va_error_objects as $o_e) {
						$va_errors[] = $o_e->getErrorDescription();
					}
				} else {
					$vs_display_format = $o_config->get('bundle_element_display_format');
				}
				
				$vb_read_only = ($pa_options['request']->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) == __CA_BUNDLE_ACCESS_READONLY__) ? true : false;
				if (!$pa_bundle_settings['readonly']) { $pa_bundle_settings['readonly'] = (!isset($pa_bundle_settings['readonly']) || !$pa_bundle_settings['readonly']) ? $vb_read_only : true;	}
		
				
				switch($ps_bundle_name) {
					# -------------------------------
					// 
					case 'generic':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element = $this->getGenericFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options, $pa_bundle_settings);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_representation_annotations
					case 'ca_representation_annotation_properties':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$this->useInEditor()) { return null; }
						foreach($this->getPropertyList() as $vs_property) {
							$vs_element .= $this->getPropertyHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $vs_property, $pa_options);
						}
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_sets
					case 'ca_set_items':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getSetItemHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options, $pa_bundle_settings);
						break;
					# -------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_sets_checklist':
						require_once(__CA_MODELS_DIR__."/ca_sets.php");	// need to include here to avoid dependency errors on parse/compile
						$t_set = new ca_sets();
						$vs_element .= $t_set->getItemSetMembershipHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_editor_uis
					case 'ca_editor_ui_screens':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getScreenHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_editor_ui_screens
					case 'ca_editor_ui_bundle_placements':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getPlacementsHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_tours
					case 'ca_tour_stops_list':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getTourStopHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options);
						break;
					# -------------------------------
					// Hierarchy navigation bar for hierarchical tables
					case 'hierarchy_navigation':
						if ($vb_batch) { return null; } // not supported in batch mode
						if ($this->isHierarchical()) {
							$vs_element .= $this->getHierarchyNavigationHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options, $pa_bundle_settings);
						}
						break;
					# -------------------------------
					// Hierarchical item location control
					case 'hierarchy_location':
						if ($this->isHierarchical()) {
							$vs_element .= $this->getHierarchyLocationHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options, $pa_bundle_settings);
						}
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_search_forms
					case 'ca_search_form_placements':
						if ($vb_batch) { return null; } // not supported in batch mode
						//if (!$this->getPrimaryKey()) { return ''; }
						$vs_element .= $this->getSearchFormHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_bundle_displays
					case 'ca_bundle_display_placements':
						if ($vb_batch) { return null; } // not supported in batch mode
						//if (!$this->getPrimaryKey()) { return ''; }
						$vs_element .= $this->getBundleDisplayHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_bundle_displays
					case 'ca_bundle_display_type_restrictions':
					case 'ca_search_form_type_restrictions':
					case 'ca_editor_ui_screen_type_restrictions':
					case 'ca_editor_ui_type_restrictions':
						$vs_element .= $this->getTypeRestrictionsHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_bundle_displays
					case 'ca_metadata_alert_rule_type_restrictions':
						$vs_element .= $this->getTypeRestrictionsHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options);
						break;
					# -------------------------------
					// 
					case 'ca_users':
						if (!$pa_options['request']->user->canDoAction('is_administrator') && ($pa_options['request']->getUserID() != $this->get('user_id'))) { return ''; }	// don't allow setting of per-user access if user is not owner
						$vs_element .= $this->getUserHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_options);
						break;
					# -------------------------------
					// 
					case 'ca_user_groups':
						if (!$pa_options['request']->user->canDoAction('is_administrator') && ($pa_options['request']->getUserID() != $this->get('user_id'))) { return ''; }	// don't allow setting of group access if user is not owner
						$vs_element .= $this->getUserGroupHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_options);
						break;
					# -------------------------------
					// 
					case 'ca_user_roles':
						if (!$pa_options['request']->user->canDoAction('is_administrator') && ($pa_options['request']->getUserID() != $this->get('user_id'))) { return ''; }	// don't allow setting of group access if user is not owner
						$vs_element .= $this->getUserRoleHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_options);
						break;
					# -------------------------------
					case 'settings':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getHTMLSettingFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $this->tableNum(), $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representations_media_display':
						AssetLoadManager::register('3dmodels');
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getMediaDisplayHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representation_captions':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$this->representationIsOfClass("video") && !$this->representationIsOfClass("audio")) { return ''; }
						$vs_element .= $this->getCaptionHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representation_sidecars':
						AssetLoadManager::register('3dmodels');
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getSidecarHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available for objects
					case 'ca_objects_components_list':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$pa_options['request']->user->canDoAction('can_edit_ca_objects')) { break; }
						if (!$this->canTakeComponents()) { return null; }
						$vs_element .= $this->getComponentListHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						
						break;
					# -------------------------------s
					case 'ca_objects_history':		// summary of object accession, movement, exhibition and deaccession
					case 'ca_objects_location':		// storage location via ca_objects_x_storage_locations or ca_movements_x_objects
					case 'history_tracking_chronology':
						if (!$this->getPrimaryKey() && !$vb_batch) { return null; }	// not supported for new records
						if (!$pa_options['request']->user->canDoAction('can_edit_ca_objects')) { break; }
					
					    if (strlen($pb_show_child_history = $pa_options['request']->getParameter("showChildHistory", pInteger))) {
					        Session::setVar("{$ps_bundle_name}_showChildHistory", (bool)$pb_show_child_history);
					    }
						$vs_element .= $this->getHistoryTrackingChronologyHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, array_merge($pa_options, ['bundleName' => $ps_bundle_name, 'showChildHistory' => Session::getVar("{$ps_bundle_name}_showChildHistory")]));
						
						break;
					# -------------------------------
					// This bundle is available for objects, object lots and collections
					case 'ca_objects_deaccession':		// deaccession information
						if (!$vb_batch && !$this->getPrimaryKey()) { return null; }	// not supported for new records
						if (!$pa_options['request']->user->canDoAction('can_edit_ca_objects')) { break; }
					
						$vs_element .= $this->getDeaccessionHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						
						break;
					# -------------------------------
					// This bundle is only available for objects
					case 'ca_object_checkouts':		// object checkout information
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$vb_batch && !$this->getPrimaryKey()) { return null; }	// not supported for new records
						if (!$pa_options['request']->user->canDoAction('can_edit_ca_objects')) { break; }
					
						$vs_element .= $this->getObjectCheckoutsHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						
						break;
					# -------------------------------
					// This bundle is only available for relationships that include an object on one end
					case 'ca_object_representation_chooser':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getRepresentationChooserHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available for storage locations
					case 'ca_storage_locations_current_contents':		// objects in storage location via ca_objects_x_storage_locations or ca_movements_x_objects
					case 'history_tracking_current_contents':
						if ($vb_batch) { return null; } 				// not supported in batch mode
						if (!$this->getPrimaryKey()) { return null; }	// not supported for new records
						if (!$pa_options['request']->user->canDoAction('can_edit_ca_storage_locations')) { break; }
					
						$vs_element .= $this->getHistoryTrackingCurrentContentsHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						
						break;
					# -------------------------------
					// This bundle is only available for objects
					case 'ca_object_circulation_status':		// circulation status for objects (part of the library module)
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$pa_options['request']->user->canDoAction('can_edit_ca_objects')) { break; }

						$vs_element .= $this->getObjectCirculationStatusHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);

						break;
					# -------------------------------
					// This bundle is only available for items that can be used as authority references (object, entities, occurrences, list items, etc.)
					case 'authority_references_list':
						$vs_element .= $this->getAuthorityReferenceListHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available items for batch editing on representable models
					case 'ca_object_representations_access_status':
						if (($vb_batch) && (is_a($this, 'RepresentableBaseModel'))) {
							$vs_element .= $this->getObjectRepresentationAccessStatusHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						} else {
							return null;
						}
						break;
					# -------------------------------
					// This bundle is only available for md alert rules
					case 'ca_metadata_alert_triggers':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!($this instanceof ca_metadata_alert_rules)) { return null; }

						$vs_element .= $this->getTriggerHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available for ca_metadata_dictionary_entries
					case 'ca_metadata_dictionary_rules':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!($this instanceof ca_metadata_dictionary_entries)) { return null; }

						$vs_element .= $this->getRulesHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available items for ca_site_pages
					case 'ca_site_pages_content':
						$vs_element .= $this->getPageContentHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available items for ca_site_pages
					case 'ca_site_page_media':
						$vs_element .= $this->getPageMediaHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					//
					case 'ca_item_tags':
						$vs_element .= $this->getItemTagHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options, $pa_bundle_settings);
						break;
					# -------------------------------
					//
					case 'ca_item_comments':
						$vs_element .= $this->getItemCommentHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options, $pa_bundle_settings);
						break;
					# -------------------------------
					// This bundle is only available items for ca_object_representations
					case 'ca_representation_transcriptions':
						$vs_element = $this->getTranscriptionHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_options, $pa_bundle_settings);
						break;
					# -------------------------------
					default:
						$vs_element = "'{$ps_bundle_name}' is not a valid bundle name";
						break;
					# -------------------------------
				}
				
				
				if (!$vs_label_text) { 
					$vs_label_text = $va_info['label']; 
				}
				$vs_label = '<span class="formLabelText" id="'.$pa_options['formName'].'_'.$ps_placement_code.'">'.$vs_label_text.'</span>'; 
				
				$vs_description = caExtractSettingValueByLocale($pa_bundle_settings, 'description', $g_ui_locale);
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$pa_options['formName'].'_'.$ps_placement_code, "<h3>{$vs_label}</h3>{$vs_description}");
				}
				
				break;
			# -------------------------------------------------
			default:
				return "'{$ps_bundle_name}' is not a valid bundle name";
				break;
			# -------------------------------------------------
		}

		if ($vs_documentation_url) {
			// catch doc URL without protocol aka starting with letters but not http/https
			if(preg_match("!^[a-z]!",$vs_documentation_url) && !preg_match("!^http[s]?://!",$vs_documentation_url)) {
				$vs_documentation_url = "http://".$vs_documentation_url;
			}
			$vs_documentation_link = "<a class='bundleDocumentationLink' href='$vs_documentation_url' target='_blank' >".caNavIcon(__CA_NAV_ICON_INFO__, '15px')."</a>";
		}
		
		
		if (is_array($va_violations) && sizeof($va_violations)) {
			$vs_label .= caNavIcon(__CA_NAV_ICON_ALERT__, "12px", ['style' => 'margin: 0 0 5px 5px;', 'onclick' => 'jQuery(this).parent().find(\'.caMetadataDictionaryDefinitionToggle\').click();  return false;']);
		} 
		
		$show_bundle_codes = $pa_options['request']->user->getPreference('show_bundle_codes_in_editor');
		$bundle_code_control = ($show_bundle_codes !== 'hide') ? "<span class='developerBundleCode'>(<a href='#' class='developerBundleCode'>{$bundle_code}</a>)</span>" : "";

		$vs_output = str_replace("^ELEMENT", $vs_element, $vs_display_format);
		$vs_output = str_replace("^ERRORS", join('; ', $va_errors), $vs_output);
		$vs_output = str_replace("^LABEL", $vs_label, $vs_output);
		$vs_output = str_replace("^DOCUMENTATIONLINK", $vs_documentation_link, $vs_output);
		$vs_output = str_replace("^BUNDLECODE", $bundle_code_control, $vs_output);

		$ps_bundle_label = $vs_label_text;
		
		// TODO: document this
		$prompt = '';
		$violations_to_prompt = [];
		if (is_array($va_violations)) {
			foreach($va_violations as $v) {
				if(is_array($v) && isset($v['showasprompt']) && (bool)$v['showasprompt'] && ($v['bundle_name'] == $dict_bundle_spec)) {
					$violations_to_prompt[] = $v;
				}
			}
		}
		
		if (is_array($violations_to_prompt) && sizeof($violations_to_prompt)) {
			$prompt_id = $pa_options['bundle_id'].'_bundle';
			$violations_text = array_map(function($v) { return caExtractSettingsValueByUserLocale('violationMessage', $v); }, $violations_to_prompt);
			$prompt = "<script type='text/javascript'>caPromptManager.addPrompt('{$prompt_id}', '".addslashes(preg_replace("![\n\r\t ]+!", " ", join("<br/>", $violations_text)))."');</script>";
		}
		return (caGetOption('contentOnly', $pa_options, false) ? $vs_element : $vs_output).$prompt;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getBundleList($options=null) {
		$bundles = $this->BUNDLES;
		if (caGetOption('rewriteKeys', $options, false)) {
		    $keys = array_keys($bundles);
			foreach($keys as $k) {
				if(substr($k, 0, 13) === 'ca_attribute_') {
					$bundles[substr($k, 13)] = $bundles[$k];
					unset($bundles[$k]);
				}		
			}
		}
		if (caGetOption('includeBundleInfo', $options, false)) { 
			return $bundles;
		}
		return array_keys($bundles);
	}
	# ------------------------------------------------------
	public function isValidBundle($ps_bundle_name) {
		return (isset($this->BUNDLES[$ps_bundle_name]) && is_array($this->BUNDLES[$ps_bundle_name])) ? true : false;
	}
	# ------------------------------------------------------
 	/** 
 	  * Returns associative array with descriptive information about the bundle
 	  */
 	public function getBundleInfo($ps_bundle_name) {
 		if (isset($this->BUNDLES[$ps_bundle_name])) { return $this->BUNDLES[$ps_bundle_name]; }
 		if (isset($this->BUNDLES["ca_attribute_{$ps_bundle_name}"])) { return $this->BUNDLES["ca_attribute_{$ps_bundle_name}"]; }
 		$ps_bundle_name = str_replace($this->tableName().".", "ca_attribute_", $ps_bundle_name);
 		if (isset($this->BUNDLES[$ps_bundle_name])) { return $this->BUNDLES[$ps_bundle_name]; }
 		$ps_bundle_name = str_replace("ca_attribute_", "", $ps_bundle_name);
 		return isset($this->BUNDLES[$ps_bundle_name]) ? $this->BUNDLES[$ps_bundle_name] : null;
 	}
 	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display label for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  *
	  * @param string $ps_field
	  * @param array $options Optipns include:
	  *		includeSourceSuffix = Include "(from <table>)" suffix on returned labels. [Default is true]
	  *
	  * @return string
	  */
	public function getDisplayLabel($ps_field, $options=null) {
		$va_tmp = explode('.', $ps_field);
		if ((sizeof($va_tmp) == 2) && ($va_tmp[0] == $this->getLabelTableName()) && ($va_tmp[1] == $this->getLabelDisplayField())) {
			$va_tmp[0] = $this->tableName();
			$va_tmp[1] = 'preferred_labels';
			$ps_field = join('.', $va_tmp);
		}

		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true)) {
					return _t("Related %1", $t_instance->getProperty('NAME_PLURAL'));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { break; }
				$vs_prefix = $vs_suffix = '';
				if (caGetOption('includeSourceSuffix', $options, true)) { $vs_suffix_string = ' ('._t('from %1', $t_instance->getProperty('NAME_PLURAL')).')'; }
				if ($va_tmp[0] !== $this->tableName()) {
					$vs_suffix = $vs_suffix_string;
				}
				switch($va_tmp[1]) {
					# --------------------
					case '_generic_bundle_':
						return _t('Generic bundle');
						break;
					# --------------------
					case 'related':
						unset($va_tmp[1]);
						$vs_label = $this->getDisplayLabel(join('.', $va_tmp), $options);
						if ($va_tmp[0] != $this->tableName()) {
							return $vs_label.$vs_suffix_string;
						} 
						return $vs_label;
						break;
					# --------------------
					case 'preferred_labels':		
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return caUcFirstUTF8Safe($t_label_instance->getProperty('NAME_PLURAL')).$vs_suffix;
							} else {
								return caUcFirstUTF8Safe($t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2], $options)).$vs_suffix;
							}
						}
						break;
					# --------------------
					case 'nonpreferred_labels':
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if ($va_tmp[0] !== $this->tableName()) {
								$vs_suffix = ' ('._t('alternates from %1', $t_instance->getProperty('NAME_PLURAL')).')';
							} else {
								$vs_suffix = ' ('._t('alternates').')';
							}
							if (!isset($va_tmp[2])) {
								return caUcFirstUTF8Safe($t_label_instance->getProperty('NAME_PLURAL')).$vs_suffix;
							} else {
								return caUcFirstUTF8Safe($t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2], $options)).$vs_suffix;
							}
						}
						break;
					# --------------------
					case 'media':		
						if ($va_tmp[0] === 'ca_object_representations') {
							if ($va_tmp[2]) {
								return _t('Object media representation (%1)', $va_tmp[2]);
							} else {
								return _t('Object media representation (default)');
							}
						}
						break;
					# --------------------
					default:
						if ($va_tmp[0] !== $this->tableName()) {
							return caUcFirstUTF8Safe($t_instance->getDisplayLabel($ps_field, $options)).$vs_suffix;
						}
						break;
					# --------------------
				}	
					
				break;
			# -------------------------------------
		}
		
		// maybe it's a special bundle name?
		if (($va_tmp[0] === $this->tableName()) && isset($this->BUNDLES[$va_tmp[1]]) && $this->BUNDLES[$va_tmp[1]]['label']) {
			return $this->BUNDLES[$va_tmp[1]]['label'];
		}
		
		return parent::getDisplayLabel($ps_field, $options);
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display description for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  */
	public function getDisplayDescription($ps_field, $options=null) {
		$va_tmp = explode('.', $ps_field);
		if ((sizeof($va_tmp) == 2) && ($va_tmp[0] == $this->getLabelTableName()) && ($va_tmp[1] == $this->getLabelDisplayField())) {
			$va_tmp[0] = $this->tableName();
			$va_tmp[1] = 'preferred_labels';
			$ps_field = join('.', $va_tmp);
		}

		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true)) {
					return _t("A list of related %1", $t_instance->getProperty('NAME_PLURAL'));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { return null; }
				
				$vs_suffix = '';
				if ($va_tmp[0] !== $this->tableName()) {
					$vs_suffix = ' '._t('from %1', $t_instance->getProperty('NAME_PLURAL'));
				}
				switch($va_tmp[1]) {
					# --------------------
					case 'related':
						unset($va_tmp[1]);
						return _t('A list of related %1', $t_instance->getProperty('NAME_PLURAL'));
						break;
					# --------------------
					case 'preferred_labels':								
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return _t('A list of %1 %2', $t_label_instance->getProperty('NAME_PLURAL'), $vs_suffix);
							} else {
								return _t('A list of %1 %2', $t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2], $options), $vs_suffix);
							}
						}
						break;
					# --------------------
					case 'nonpreferred_labels':						
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return _t('A list of alternate %1 %2', $t_label_instance->getProperty('NAME_PLURAL'), $vs_suffix);
							} else {
								return _t('A list of alternate %1 %2', $t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2], $options), $vs_suffix);
							}
						}
						break;
					# --------------------
					case 'media':		
						if ($va_tmp[0] === 'ca_object_representations') {
							if ($va_tmp[2]) {
								return _t('A list of related media representations using version "%1"', $va_tmp[2]);
							} else {
								return _t('A list of related media representations using the default version');
							}
						}
						break;
					# --------------------
					default:
						if ($va_tmp[0] !== $this->tableName()) {
							return _t('A list of %1 %2', $t_instance->getDisplayLabel($ps_field, $options), $vs_suffix);
						}
						break;
					# --------------------
				}	
					
				break;
			# -------------------------------------
		}
		
		return parent::getDisplayDescription($ps_field, $options);
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns HTML search form input widget for bundle specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  * This method handles generation of search form widgets for (1) related tables (eg. ca_places),  preferred and non-preferred labels for both the 
	  * primary and related tables, and all other types of elements for related tables. If this method can't handle the bundle it will pass the request to the 
	  * superclass implementation of htmlFormElementForSearch()
	  *
	  * @param $po_request HTTPRequest
	  * @param $ps_field string
	  * @param $pa_options array
	  * @return string HTML text of form element. Will return null (from superclass) if it is not possible to generate an HTML form widget for the bundle.
	  * 
	  */
	public function htmlFormElementForSearch($po_request, $ps_field, $pa_options=null) {
		$vb_as_array_element = (bool)caGetOption('asArrayElement', $pa_options, false);
		$va_tmp = explode('.', $ps_field);
		
		switch($va_tmp[0]) {
			case '_fulltext':
				if (!isset($pa_options['width'])) { $pa_options['width'] = 30; }
				if (!isset($pa_options['height'])) { $pa_options['height'] = 30; }
				if (!isset($pa_options['values'])) { $pa_options['values'] = []; }
				if (!isset($pa_options['id'])) { $pa_options['id'] = str_replace('.', '_', $ps_field); }
				if (!isset($pa_options['values']['_fulltext'])) { $pa_options['values'][$ps_field] = ''; }
				return caHTMLTextInput("_fulltext".($vb_as_array_element ? "[]" : ""), array(
								'value' => $pa_options['values']['_fulltext'],
								'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => $pa_options['id']
							), $pa_options);
				break;
			case '_fieldlist':
				if (!isset($pa_options['width'])) { $pa_options['width'] = 30; }
				if (!isset($pa_options['height'])) { $pa_options['height'] = 30; }
				if (!isset($pa_options['values'])) { $pa_options['values'] = []; }
				if (!isset($pa_options['id'])) { $pa_options['id'] = str_replace('.', '_', $ps_field); }
				
				
				$va_filter = $va_alt_names = $va_relationship_restricted_searches = null;
				if(is_array($va_fields = preg_split("![;,]+!", caGetOption('fields', $pa_options, null))) && sizeof($va_fields)) {
					$va_filter = $va_alt_names = $va_relationship_restricted_searches = [];
					
					foreach($va_fields as $vs_field_raw) {
						$va_tmp = explode(':', $vs_field_raw);
						$va_tmp2 = explode('/', $va_tmp[0]);
						
						// If there's a "/" separator then this is a relationship type-restricted search (Eg. ca_entities.preferred_labels.displayname/artist:"Isamu Noguchi")
						if (sizeof($va_tmp2) > 1) { 
							$va_relationship_restricted_searches[$va_tmp2[0]][] = $va_tmp[0]; 
						} else {
							$va_filter[] = $va_tmp2[0];
						}
						
						if (isset($va_tmp[1]) && $va_tmp[1]) { $va_alt_names[$va_tmp[0]] = $va_tmp[1]; }
					}
				}
				
				$va_options = caGetBundlesAvailableForSearch($this->tableName(), array('forSelect' => true, 'filter' => $va_filter));
				
				// We need to add any relationship-restricted searh qualifiers here since they're not free-standing bundles but
				// rather variants on an unqualified relationship bundle
				foreach($va_relationship_restricted_searches as $vs_without_rel_restriction => $va_with_rel_restrictions) {
					foreach($va_with_rel_restrictions as $vs_with_rel_restriction) {
						$vs_label = (isset($va_alt_names[$vs_with_rel_restriction])) ? $va_alt_names[$vs_with_rel_restriction] : $vs_with_rel_restriction;
						$va_options[$vs_label] = $vs_with_rel_restriction;
					}
				}
				
				if (is_array($va_alt_names)) {
					foreach($va_options as $vs_l => $vs_fld) {
						if (isset($va_alt_names[$vs_fld])) { 
							unset($va_options[$vs_l]);
							$va_options[$va_alt_names[$vs_fld]] = $vs_fld;
						}
					}
				}
				
				if(is_array($va_filter) && sizeof($va_filter)) {
					// reorder options to keep field list order (sigh)
					$va_options_tmp = [];
					foreach($va_filter as $vs_filter) {
						if (($vs_k = array_search($vs_filter, $va_options)) !== false) {
							$va_options_tmp[$vs_k] = $va_options[$vs_k];
						}
					}
					$va_options = $va_options_tmp;
				}
				
				return caHTMLSelect("_fieldlist_field".($vb_as_array_element ? "[]" : ""), $va_options, array(
								'size' => $pa_options['fieldListWidth'], 'class' => $pa_options['class']
							), array_merge($pa_options, array('value' => $pa_options['values']['_fieldlist_field'][0]))).
						caHTMLTextInput("_fieldlist_value".($vb_as_array_element ? "[]" : ""), array(
								'value' => $pa_options['values']['_fieldlist_value'],
								'size' => $pa_options['width'], 'class' => $pa_options['class']
							), $pa_options);
				break;
		}
		
		if ($vs_rel_types = join(";", caGetOption('restrictToRelationshipTypes', $pa_options, []))) { $vs_rel_types = "/{$vs_rel_types}"; }
	
		if (!in_array($va_tmp[0], array('created', 'modified'))) {
		    $is_current_value_element = false;
		    if ((sizeof($va_tmp) == 4) && ($va_tmp[1] == 'current_value')) {
		        $is_current_value_element = $va_tmp[2]; // 2=policy
		        $va_tmp = [$va_tmp[0], $va_tmp[3]];
		        $ps_field = join(".", $va_tmp);
		    }
			switch(sizeof($va_tmp)) {
				# -------------------------------------
				case 1:		// table_name
					if ($va_tmp[0] != $this->tableName()) {
						if (!is_array($pa_options)) { $pa_options = []; }
						if (!isset($pa_options['width'])) { $pa_options['width'] = 30; }
						if (!isset($pa_options['values'])) { $pa_options['values'] = []; }
						if (!isset($pa_options['values'][$ps_field])) { $pa_options['values'][$ps_field] = ''; }
					
						return caHTMLTextInput($ps_field.$vs_rel_types.($vb_as_array_element ? "[]" : ""), array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => str_replace('.', '_', $ps_field)));
					}
					break;
				# -------------------------------------
				case 2:		// table_name.field_name
				case 3:		// table_name.field_name.sub_element	
					if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { return null; }
					if (!isset($pa_options['id'])) { $pa_options['id'] = str_replace('.', '_', $ps_field); }
										
					if (($va_tmp[0] != $this->tableName()) || ($va_tmp[1] == 'related')) {
						switch(sizeof($va_tmp)) {
							case 1:
								return caHTMLTextInput($ps_field.($vb_as_array_element ? "[]" : ""), array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => $pa_options['id']));
							case 2:
							case 3:
								if ($ps_render = caGetOption('render', $pa_options, null)) {
									switch($ps_render) {
										case 'is_set':
											return caHTMLCheckboxInput($ps_field.$vs_rel_types, array('value' => '['._t('SET').']'));
											break;
										case 'is':
											return caHTMLCheckboxInput($ps_field.$vs_rel_types, array('value' => caGetOption('value', $pa_options, null)));
											break;
									}
								}
								
								// Autocompletion for fields from related tables
								if (caGetOption('autocomplete', $pa_options, false)) {
									$pa_options['asArrayElement'] = false;
									
									return caGetAdvancedSearchFormAutocompleteJS($po_request, $ps_field, $t_instance, $pa_options);
								} elseif (caGetOption('select', $pa_options, false)) {
									$va_access = caGetOption('checkAccess', $pa_options, null);
									if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { return null; }
								
									$vs_label_display_field = $t_instance->getLabelDisplayField();
									$va_find_params = array('parent_id' => null);
								
									switch($va_tmp[0]) {
										case 'ca_list_items':
											if ($vs_list = caGetOption('list', $pa_options, null)) {
												if ($vn_list_id = caGetListID($vs_list)) { $va_find_params = array('list_id' => $vn_list_id); }
											}
											break;
									}
									
									$vs_sort = caGetOption('sort', $pa_options, $t_instance->tableName().'.preferred_labels.'.$vs_label_display_field);
									$qr_res = call_user_func_array($va_tmp[0].'::find', array($va_find_params, array('restrictToTypes' => caGetOption('restrictToTypes', $pa_options, null), 'sort' => $vs_sort, 'returnAs' => 'searchResult')));

									$vs_pk = $t_instance->primaryKey();
									$va_opts = array('-' => '');
									
									$va_in_use_list = $vs_rel_pk = null;
									if (caGetOption('inUse', $pa_options, false)) {
										if (is_array($va_path = Datamodel::getPath($this->tableName(), $va_tmp[0]))) {
											$va_path = array_keys($va_path);
											if (sizeof($va_path) == 3) {
												if ($t_rel = Datamodel::getInstanceByTableName($va_tmp[0], true)) {
													$vs_table = $this->tableName();
													$vs_pk = $this->primaryKey();
											
													$va_sql_wheres = [];
													$va_sql_params = [];
													if ($this->hasField('deleted')) { $va_sql_wheres[] = "(t.deleted = 0)"; }
													if ($this->hasField('access') && is_array($va_access) && sizeof($va_access)) { $va_sql_wheres[] = "(t.access IN (?))"; $va_sql_params[] = $va_access; }
											
													$vs_rel_pk = $t_rel->primaryKey();
													$qr_in_use = $this->getDb()->query("
														SELECT DISTINCT l.{$vs_rel_pk}
														FROM {$va_path[1]} l
														INNER JOIN {$vs_table} AS t ON t.{$vs_pk} = l.{$vs_pk}
														".((sizeof($va_sql_wheres) > 0) ? "WHERE ".join(" AND ", $va_sql_wheres) : "")."		
													", $va_sql_params);
													$va_in_use_list = $qr_in_use->getAllFieldValues($vs_rel_pk);
												}
											}
										}
									}
									
									if ($qr_res) {
									    $vs_field = ($va_tmp[1] == 'related') ? $va_tmp[0].'.'.$va_tmp[2] : $ps_field;
										while($qr_res->nextHit()) {
											if (($va_tmp[0] == 'ca_list_items') && (!$qr_res->get('parent_id'))) { continue; }
											if (is_array($va_access) && sizeof($va_access) && !in_array($qr_res->get($va_tmp[0].'.access'), $va_access)) { continue; }
											if (is_array($va_in_use_list) && !in_array($vn_item_id = $qr_res->get($vs_rel_pk), $va_in_use_list)) { continue; }
											$va_opts[$qr_res->get($va_tmp[0].".preferred_labels.{$vs_label_display_field}")] = $qr_res->get($vs_field);
										}
									}
									
									if (!isset($pa_options['sort'])) { uksort($va_opts, "strnatcasecmp"); }
									return caHTMLSelect($ps_field.$vs_rel_types.($vb_as_array_element ? "[]" : ""), $va_opts, array('value' => $pa_options['values'][$ps_field], 'class' => $pa_options['class'], 'id' => $pa_options['id']));
								} else {
									return $t_instance->htmlFormElementForSearch($po_request, $ps_field, $pa_options);
								}
								break;
						}
					}
						
					break;
				# -------------------------------------
			}
		}
		
		return parent::htmlFormElementForSearch($po_request, $ps_field, $pa_options);
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns HTML form input widget for bundle specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format) suitable
	  * for use in a simple data entry form, such as the front-end "contribute" user-provided content submission form
	  *
	  * This method handles generation of search form widgets for (1) related tables (eg. ca_places),  preferred and non-preferred labels for both the 
	  * primary and related tables, and all other types of elements for related tables. If this method can't handle the bundle it will pass the request to the 
	  * superclass implementation of htmlFormElementForSearch()
	  *
	  * @param $po_request HTTPRequest
	  * @param $ps_field string
	  * @param $pa_options array Options include
	  *		asArrayElement = 
	  *		relationshipType = 
	  *		type = 
	  *		values = 
	  *		useCurrentRowValueAsDefault = 
	  *		
	  * @return string HTML text of form element. Will return null (from superclass) if it is not possible to generate an HTML form widget for the bundle.
	  * 
	  */
	public function htmlFormElementForSimpleForm($po_request, $ps_field, $pa_options=null) {
		$vb_as_array_element = (bool)caGetOption('asArrayElement', $pa_options, false);
		$va_tmp = explode('.', $ps_field);
		
		$vs_buf = '';
		if($vs_rel_type = caGetOption('relationshipType', $pa_options, null)) {
			$vs_buf .= caHTMLHiddenInput($ps_field.'_relationship_type'.($vb_as_array_element ? "[]" : ""), array('value' => $vs_rel_type));
		}
		if($vs_type = caGetOption('type', $pa_options, null)) {
			$vs_buf .= caHTMLHiddenInput($ps_field.'_type'.($vb_as_array_element ? "[]" : ""), array('value' => $vs_type));
		}
		
		$use_current_row_value = caGetOption('useCurrentRowValueAsDefault', $pa_options, false);
		
		
		if (!isset($pa_options['values'])) { $pa_options['values'] = []; }
		if (!isset($pa_options['values'][$ps_field])) { $pa_options['values'][$ps_field] = null; }
		if (is_null($value = $pa_options['values'][$ps_field]) && $use_current_row_value) {
			$value  = $this->get($ps_field);
		}
		
		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($va_tmp[0] != $this->tableName()) {
					if (!is_array($pa_options)) { $pa_options = []; }
					if (!isset($pa_options['width'])) { $pa_options['width'] = 30; }
					
					if (caGetOptions('autocomplete', $pa_options, false)) {
				        if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { return null; }
				        if (!is_null($index = caGetOption('index', $pa_options, null))) {
				            $value_index = caGetOption('valueIndex', $pa_options, $index);
				            $values = $this->get($ps_field, ['returnAsArray' => true, 'restrictToRelationshipTypes' => [$vs_rel_type]]);
				            $related_ids = $this->get($t_instance->primaryKey(true), ['returnAsArray' => true, 'restrictToRelationshipTypes' => [$vs_rel_type]]);
				            $value = $values[$value_index];
				            $related_id = $related_ids[$value_index];
				        }
					    return $vs_buf.caGetAdvancedSearchFormAutocompleteJS($po_request, $ps_field, $t_instance, array('index' => $index, 'value' => $value, 'id_value' => $related_id, 'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => str_replace('.', '_', $ps_field), 'asArrayElement' => $vb_as_array_element, 'restrictToRelationshipTypes' => [$vs_rel_type]));
					}
					return $vs_buf.caHTMLTextInput($ps_field.($vb_as_array_element ? "[]" : ""), array('value' => $value, 'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => str_replace('.', '_', $ps_field)));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { return null; }
				
				switch($va_tmp[1]) {
					# --------------------
					case 'preferred_labels':		
					case 'nonpreferred_labels':
						return $vs_buf.caHTMLTextInput($ps_field.($vb_as_array_element ? "[]" : ""), array('value' => $value, 'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => str_replace('.', '_', $ps_field)));
						break;
					# --------------------
					default:
						if (($va_tmp[0] != $this->tableName()) || ($va_tmp[1] == 'related')) {
							switch(sizeof($va_tmp)) {
								case 1:
									return $vs_buf.caHTMLTextInput($ps_field.($vb_as_array_element ? "[]" : ""), array('value' => $value, 'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => str_replace('.', '_', $ps_field)));
								case 2:
								case 3:
								    if (caGetOption('autocomplete', $pa_options, false)) {
                                        if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0], true))) { return null; }
                                        if (!is_null($index = caGetOption('index', $pa_options, null))) {
                                            $values = $this->get($ps_field, ['returnAsArray' => true]);
                                            $related_ids = $this->get($ps_field.".".$t_instance->primaryKey(), ['returnAsArray' => true]);
                                            $value = $values[$index];
                                            $related_id = $related_ids[$index];
                                        }
                                        return $vs_buf.caGetAdvancedSearchFormAutocompleteJS($po_request, $ps_field, $t_instance, array('index' => $index, 'value' => $value, 'id_value' => $related_id, 'size' => $pa_options['width'], 'class' => $pa_options['class'], 'id' => str_replace('.', '_', $ps_field), 'asArrayElement' => $vb_as_array_element));
                                    }
									return $vs_buf.$t_instance->htmlFormElementForSearch($po_request, $ps_field, $pa_options);
									break;
							}
						}
						break;
					# --------------------
				}	
					
				break;
			# -------------------------------------
		}
		
		return parent::htmlFormElementForSimpleForm($po_request, $ps_field, $pa_options);
	}
 	# ------------------------------------------------------
 	/**
 	 * Returns a list of HTML fragments implementing all bundles in an HTML form for the specified screen
 	 * $pm_screen can be a screen tag (eg. "Screen5") or a screen_id (eg. 5) 
 	 *
 	 * @param mixed $pm_screen screen_id or code in default UI to return bundles for
 	 * @param array $pa_options Array of options. Supports any option getBundleFormHTML() supports plus:
 	 *		request = the current request object; used to apply user privs to bundle generation
 	 *		force = list of bundles to force onto form if they are not included in the UI; forced bundles will be included at the bottom of the form
 	 *		forceHidden = list of *intrinsic* fields to force onto form as hidden <input> elements if they are not included in the UI; NOTE: only intrinsic fields may be specified
 	 *		omit = list of bundles to omit from form in the event they are included in the UI
 	 *		restrictToTypes = 
 	 *		bundles = 
 	 *		dontAllowBundleShowHide = Do not provide per-bundle show/hide control. [Default is false]
 	 *		policy = history tracking policy to apply relationship type restrictions from. Used to auto-restrict relationship bundles to specific relationship types to conform to policy when triggered from the history tracking chronology bundle. [Default is null]
 	 *	@return array List of bundle HTML to display in form, keyed on placement code
 	 */
 	public function getBundleFormHTMLForScreen($pm_screen, $pa_options, &$pa_placements=null) {
 		$va_omit_bundles = (isset($pa_options['omit']) && is_array($pa_options['omit'])) ? $pa_options['omit'] : [];
 		$vb_dont_allow_bundle_show_hide = caGetOption('dontAllowBundleShowHide', $pa_options, false);
 		$policy = caGetOption('policy', $pa_options, null);
 		
 		// Get policy info
 		$pinfo = null;
 		if($policy && get_called_class()::historyTrackingPolicyUses($policy, $t = $this->tableName())) {
			$pinfo = get_called_class()::getHistoryTrackingCurrentValuePolicyElement($policy, $t);
 		}
 		
 		$vs_table_name = $this->tableName();
 		
 		if (isset($pa_options['ui_instance']) && ($pa_options['ui_instance'])) {
 			$t_ui = $pa_options['ui_instance'];
 		} else {
 			$t_ui = ca_editor_uis::loadDefaultUI($vs_table_name, $pa_options['request'], $this->getTypeID(), ['editorPref' => caGetOption('quickadd', $pa_options, false) ? 'quickadd' : null]);
 		}
 		if (!$t_ui) { return false; }
 		
 		$pa_options['ui'] = $t_ui; 	// stash UI object and screen in options for use by getBundleFormHTML()
 		$pa_options['screen'] = $pm_screen;
 		
 		if (($vn_ui_access = ca_editor_uis::getAccessForUI($pa_options['request'], $t_ui->getPrimaryKey())) === __CA_BUNDLE_ACCESS_NONE__) {
 			// no access to UI
 			$this->postError(2320, _t('Access denied to UI %1', $t_ui->get('editor_code')), "BundlableLabelableBaseModelWithAttributes->getBundleFormHTMLForScreen()");
			return false;
 		}
 		
 		if (isset($pa_options['bundles']) && is_array($pa_options['bundles'])) {
 			$va_bundles = $pa_options['bundles'];
 			$vn_screen_access = __CA_BUNDLE_ACCESS_EDIT__;
 		} else {
 			$va_bundles = $t_ui->getScreenBundlePlacements($pm_screen, $this->getTypeID());
 		
			if (($vn_screen_access = ca_editor_uis::getAccessForScreen($pa_options['request'], $pm_screen)) === __CA_BUNDLE_ACCESS_NONE__) {
				// no access to screen
				$this->postError(2320, _t('Access denied to screen %1', $pm_screen), "BundlableLabelableBaseModelWithAttributes->getBundleFormHTMLForScreen()");				
				return false;
			}
 		}
 		
 		$vs_form_name = caGetOption('formName', $pa_options, '');
 
 		$va_bundle_html = [];
 		
 		$vn_pk_id = $this->getPrimaryKey();
		
		$va_bundles_present = [];
		if (is_array($va_bundles)) {
			
			$va_definition_bundle_names = [];
			foreach($va_bundles as $va_bundle) {
				if ($va_bundle['bundle_name'] === $vs_type_id_fld) { continue; }	// skip type_id
				if ((!$vn_pk_id) && ($va_bundle['bundle_name'] === $vs_hier_parent_id_fld)) { continue; }
				if (in_array($va_bundle['bundle_name'], $va_omit_bundles)) { continue; }
				
				$va_definition_bundle_names[((!Datamodel::tableExists($va_bundle['bundle_name']) && !preg_match("!^{$vs_table_name}\.!", $va_bundle['bundle_name'])) ? "{$vs_table_name}." : "").str_replace("ca_attribute_", "", $va_bundle['bundle_name'])] = 1;
			}
			ca_metadata_dictionary_entries::preloadDefinitions(array_keys($va_definition_bundle_names));
		
			if (is_subclass_of($this, 'BaseRelationshipModel')) {
				$vs_type_id_fld = $this->getTypeFieldName();
				if(isset($pa_options['restrictToTypes']) && is_array($pa_options['restrictToTypes'])) {
					$va_valid_element_codes = $this->getApplicableElementCodesForTypes(caMakeRelationshipTypeIDList($vs_table_name, $pa_options['restrictToTypes']));
				} else {
					$va_valid_element_codes = null;
				}
			} else {
				$vs_type_id_fld = isset($this->ATTRIBUTE_TYPE_ID_FLD) ? $this->ATTRIBUTE_TYPE_ID_FLD : null;
			
			
				$vs_hier_parent_id_fld = isset($this->HIERARCHY_PARENT_ID_FLD) ? $this->HIERARCHY_PARENT_ID_FLD : null;
			
				if(isset($pa_options['restrictToTypes']) && is_array($pa_options['restrictToTypes'])) {
					$va_valid_element_codes = $this->getApplicableElementCodesForTypes(caMakeTypeIDList($vs_table_name, $pa_options['restrictToTypes']));
				} else {
					$va_valid_element_codes = null;
				}
			}
			
			$vn_c = 0;
			foreach($va_bundles as $va_bundle) {
				if ($va_bundle['bundle_name'] === $vs_type_id_fld) { continue; }	// skip type_id
				if ((!$vn_pk_id) && ($va_bundle['bundle_name'] === $vs_hier_parent_id_fld)) { continue; }
				if (in_array($va_bundle['bundle_name'], $va_omit_bundles)) { continue; }
				
				if (!is_array($va_bundle['settings'])) { $va_bundle['settings'] = []; }
				
				
				if ($vs_element_set_code = preg_replace("/^(ca_attribute_|".$this->tableName()."\.)/", "", $va_bundle['bundle_name'])) {
					if (($o_element = ca_metadata_elements::getInstance($vs_element_set_code)) && ($this->hasElement($vs_element_set_code))) {
						$va_bundle['bundle_name'] = "ca_attribute_{$vs_element_set_code}";
					}
				}
				
				if ($va_valid_element_codes && (substr($va_bundle['bundle_name'],0, 13) == 'ca_attribute_')) {
					if (!in_array(substr($va_bundle['bundle_name'],13), $va_valid_element_codes)) {
						continue;
					}
				}
				
				// Test for user action restrictions on intrinsic fields
				$vb_output_bundle = true;
				if ($this->hasField($va_bundle['bundle_name'])) {
					if (is_array($va_requires = $this->getFieldInfo($va_bundle['bundle_name'], 'REQUIRES'))) {
						foreach($va_requires as $vs_required_action) {
							if (!$pa_options['request']->user->canDoAction($vs_required_action)) { 
								$vb_output_bundle = false;
								break;
							}
						}
					}
				}
				if (!$vb_output_bundle) { continue; }
			
				if ($vn_screen_access === __CA_BUNDLE_ACCESS_READONLY__) {
					$va_bundle['settings']['readonly'] = true;	// force all bundles to read-only when user has "Read" access to screen
				} elseif ($vn_screen_access !== __CA_BUNDLE_ACCESS_EDIT__) {
					// no edit access so bail
					//$this->postError(2320, _t('Access denied to screen %1', $pm_screen), "BundlableLabelableBaseModelWithAttributes->getBundleFormHTMLForScreen()");				
					continue;
				}
				
				// Apply policy relationship type restriction for related, if set
				if(is_array($pinfo) && Datamodel::tableExists($va_bundle['bundle_name'])) {
					if(($r = caGetOption('useRelated', $pinfo, null)) && ($rt = caGetOption('useRelatedRelationshipType', $pinfo, null))) {
						$va_bundle['settings']['restrictToRelationshipTypes'] = [$rt];
					}
				}
				
				if ($vb_dont_allow_bundle_show_hide) {
					$va_bundle['settings']['dont_allow_bundle_show_hide'] = true;
				}
				$va_bundle['settings']['placement_id'] = $va_bundle['placement_id'];
				
				if ($vs_bundle_form_html = $this->getBundleFormHTML($va_bundle['bundle_name'], 'P'.$va_bundle['placement_id'], $va_bundle['settings'], array_merge($pa_options, ['bundle_id' => "{$pm_screen}_{$va_bundle['placement_id']}"]), $vs_bundle_display_name)) {
					$va_bundle_html[$va_bundle['placement_code']] = "<a name=\"{$pm_screen}_{$va_bundle['placement_id']}\"></a><span id=\"{$pm_screen}_{$va_bundle['placement_id']}_bundle\">{$vs_bundle_form_html}</span>";
					$va_bundles_present[$va_bundle['bundle_name']] = true;
					
					$pa_placements["{$pm_screen}_{$va_bundle['placement_id']}"] = array(
						'name' => $vs_bundle_display_name ? $vs_bundle_display_name : $this->getDisplayLabel($vs_table_name.".".$va_bundle['bundle_name']),
						'placement_id' => $va_bundle['placement_id'],
						'bundle' => $va_bundle['bundle_name'],
						'id' => 'P'.$va_bundle['placement_id'].caGetOption('formName', $pa_options, '')
					);
				}
			}
		}

		// is this a form to create a new item?
		if (!$vn_pk_id) {
			// auto-add mandatory fields if this is a new object
			$va_mandatory_fields = $this->getMandatoryFields();
			foreach($va_mandatory_fields as $vs_field) {
				if (!isset($va_bundles_present[$vs_field]) || !$va_bundles_present[$vs_field]) {
					$va_bundle_html[$vs_field] = $this->getBundleFormHTML($vs_field, 'mandatory_'.$vs_field, [], $pa_options);
				}
			}
			
			// add type_id
			if (isset($this->ATTRIBUTE_TYPE_ID_FLD) && $this->ATTRIBUTE_TYPE_ID_FLD && !in_array('type_id', $va_omit_bundles)) {
				$va_bundle_html[$this->ATTRIBUTE_TYPE_ID_FLD] = caHTMLHiddenInput($this->ATTRIBUTE_TYPE_ID_FLD, array('value' => $pa_options['request']->getParameter($this->ATTRIBUTE_TYPE_ID_FLD, pString)));
			}
			
			// add parent_id
			if (isset($this->HIERARCHY_PARENT_ID_FLD) && $this->HIERARCHY_PARENT_ID_FLD && !in_array('parent_id', $va_omit_bundles)) {
				$va_bundle_html[$this->HIERARCHY_PARENT_ID_FLD] = caHTMLHiddenInput($this->HIERARCHY_PARENT_ID_FLD, array('value' => $pa_options['request']->getParameter($this->HIERARCHY_PARENT_ID_FLD, pInteger)));
			}
			
			// add forced bundles
			if (isset($pa_options['force']) && $pa_options['force']) {
				if (!is_array($pa_options['force'])) { $pa_options['force'] = array($pa_options['force']); }
				foreach($pa_options['force'] as $vn_x => $vs_bundle) {
					if (!isset($va_bundles_present[$vs_bundle]) || !$va_bundles_present[$vs_bundle]) {
						$va_bundle_html['_force_'.$vs_bundle] = $this->getBundleFormHTML($vs_bundle, 'force_'.$vs_field, [], $pa_options);
					}
				}
			}
			
			// add forced hidden intrinsic fields
			if (isset($pa_options['forceHidden']) && $pa_options['forceHidden']) {
				if (!is_array($pa_options['forceHidden'])) { $pa_options['forceHidden'] = array($pa_options['forceHidden']); }
				foreach($pa_options['forceHidden'] as $vn_x => $vs_field) {
					if (!isset($va_bundles_present[$vs_field]) || !$va_bundles_present[$vs_field]) {
						$va_bundle_html['_force_hidden_'.$vs_field] = caHTMLHiddenInput($vs_field, array('value' => $pa_options['request']->getParameter($vs_field, pString)));
					}
				}
			}
		}

		$va_plugin_data = $this->opo_app_plugin_manager->hookBundleFormHTML(
			[
				'bundles' => $va_bundle_html,
				'subject' => $this,
				'options' => $pa_options
			]
		);
		return $va_plugin_data['bundles'] ?? $va_bundle_html;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
	public function getHierarchyNavigationHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null, $pa_bundle_settings=null) {
	
 		$o_view = $this->_getHierarchyLocationHTMLFormBundleInfo($po_request, $ps_form_name, $ps_placement_code, $pa_options, $pa_bundle_settings);
		return $o_view->render('hierarchy_navigation.php');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getHierarchyLocationHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null, $pa_bundle_settings=null) {
		
 		$o_view = $this->_getHierarchyLocationHTMLFormBundleInfo($po_request, $ps_form_name, $ps_placement_code, $pa_options, $pa_bundle_settings);
		return $o_view->render('hierarchy_location.php');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _getHierarchyLocationHTMLFormBundleInfo($po_request, $ps_form_name, $ps_placement_code, $pa_options=null, $pa_bundle_settings=null) {
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		$pb_batch = caGetOption('batch', $pa_options, false);
		
		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = []; }
		
		if (!($vs_label_table_name = $this->getLabelTableName())) { return ''; }
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);
		$o_view->setVar('t_subject', $this);
		if (!($vn_id = $this->getPrimaryKey())) {
			$vn_parent_id = $vn_id = $po_request->getParameter($this->HIERARCHY_PARENT_ID_FLD, pString);
		} else {
			$vn_parent_id = $this->get($this->HIERARCHY_PARENT_ID_FLD);
		}
		$vs_display_fld = $this->getLabelDisplayField();
		
		if ($this->supportsPreferredLabelFlag()) {
			if (!($va_ancestor_list = $this->getHierarchyAncestors($vn_id, array(
				'additionalTableToJoin' => $vs_label_table_name, 
				'additionalTableJoinType' => 'LEFT',
				'additionalTableSelectFields' => array($vs_display_fld, 'locale_id'),
				'additionalTableWheres' => array('('.$vs_label_table_name.'.is_preferred = 1 OR '.$vs_label_table_name.'.is_preferred IS NULL)'),
				'includeSelf' => true
			)))) {
				$va_ancestor_list = [];
			}
		} else {
			if (!($va_ancestor_list = $this->getHierarchyAncestors($vn_id, array(
				'additionalTableToJoin' => $vs_label_table_name, 
				'additionalTableJoinType' => 'LEFT',
				'additionalTableSelectFields' => array($vs_display_fld, 'locale_id'),
				'includeSelf' => true
			)))) {
				$va_ancestor_list = [];
			}
		}
		
		
		$va_ancestors_by_locale = [];
		$vs_pk = $this->primaryKey();
		$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
		
		$vs_hierarchy_type = $this->getProperty('HIERARCHY_TYPE');
		foreach($va_ancestor_list as $vn_ancestor_id => $va_info) {
			switch($vs_hierarchy_type) {
				case __CA_HIER_TYPE_SIMPLE_MONO__:
					if (!$va_info['NODE']['parent_id']) { continue(2); }
					break;
				case __CA_HIER_TYPE_MULTI_MONO__:
					if (!$va_info['NODE']['parent_id']) {
						$vn_item_id = $va_info['NODE'][$vs_pk];
						$va_ancestors_by_locale[$vn_item_id][$vn_locale_id] = array(
							'item_id' => $vn_item_id,
							'parent_id' => $va_info['NODE']['parent_id'],
							'label' => $this->getHierarchyName($vn_item_id),
							'idno' => $va_info['NODE'][$vs_idno_field],
							'locale_id' => null,
							'table' => $this->tableName()
				
						);
						continue(2);
					}
					break;
			}
			if (!$va_info['NODE']['parent_id'] && $vb_dont_show_root) { continue; }
			
			$vn_locale_id = isset($va_info['NODE']['locale_id']) ? $va_info['NODE']['locale_id'] : null;
			$va_ancestor = array(
				'item_id' => $vn_item_id = $va_info['NODE'][$vs_pk],
				'parent_id' => $va_info['NODE']['parent_id'],
				'label' => $va_info['NODE'][$vs_display_fld] ? $va_info['NODE'][$vs_display_fld] : $va_info['NODE'][$vs_idno_field],
				'idno' => $va_info['NODE'][$vs_idno_field],
				'locale_id' => $vn_locale_id,
				'table' => $this->tableName()
				
			);
			$va_ancestors_by_locale[$vn_item_id][$vn_locale_id] = $va_ancestor;
		}
		
		$va_ancestor_list = array_reverse(caExtractValuesByUserLocale($va_ancestors_by_locale), true);

		if (!$this->getPrimaryKey()) {
			$va_ancestor_list[null] = array(
				$this->primaryKey() => '',
				$this->getLabelDisplayField() => _t('New %1', $this->getProperty('NAME_SINGULAR'))
			);
		}
		
		$vs_object_collection_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type');
		$vb_strict_type_hierarchy = $po_request->config->get($this->tableName().'_enforce_strict_type_hierarchy');
	
		$t_object = new ca_objects();
		$t_rel = ca_relationship_types::findAsInstance(['table_num' => Datamodel::getTableNum('ca_objects_x_collections'), 'type_code' => $vs_object_collection_rel_type]);
	
		$o_view->setVar('objectTypeList', trim($t_object->getTypeListAsHTMLFormElement("{$ps_placement_code}{$ps_form_name}object_type_id", 
			['id' => "{$ps_placement_code}{$ps_form_name}objectTypeList"], 
			[	'childrenOfCurrentTypeOnly' => (bool)$vb_strict_type_hierarchy, 
				'includeSelf' => !(bool)$vb_strict_type_hierarchy, 
				'directChildrenOnly' => $vb_strict_type_hierarchy && ($vb_strict_type_hierarchy !== '~'),
				'restrictToTypes' => $t_rel ? [$t_rel->get('ca_relationship_types.sub_type_left_id')] : null,
				'dontIncludeSubtypesInTypeRestriction' => $t_rel ? !$t_rel->get('ca_relationship_types.include_subtypes_left') : null
			])));
					
		
		$o_view->setVar('object_collection_collection_ancestors', []); // collections to display as object parents when ca_objects_x_collections_hierarchy_enabled is enabled
		if (($this->tableName() == 'ca_objects') && $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled')) {
				
			// Is object part of a collection?
			
			$va_object_ids = array_keys($va_ancestor_list);
			$vn_top_object_id = array_shift($va_object_ids);
			if ($vn_top_object_id != $this->getPrimaryKey()) { 
				$t_object = Datamodel::getInstanceByTableName("ca_objects", true);
				$t_object->load($vn_top_object_id); 
			} else { 
				$t_object = $this;
			}
			if(is_array($va_collections = $t_object->getRelatedItems('ca_collections', array('restrictToRelationshipTypes' => array($vs_object_collection_rel_type))))) {
				$va_related_collections_by_level = [];
				foreach($va_collections as $vs_key => $va_collection) {
					$va_related_collections_by_level[$va_collection['collection_id']] = array(
						'item_id' => $va_collection['collection_id'],
						'parent_id' => $va_collection['parent_id'],
						'label' => $va_collection['label'],
						'idno' => $va_collection['idno'],
						'table' => 'ca_collections'
					);
					$t_collection = new ca_collections();
					if (!($va_collection_ancestor_list = $t_collection->getHierarchyAncestors($va_collection['collection_id'], array(
						'additionalTableToJoin' => 'ca_collection_labels', 
						'additionalTableJoinType' => 'LEFT',
						'additionalTableSelectFields' => array('name', 'locale_id'),
						'additionalTableWheres' => array('(ca_collection_labels.is_preferred = 1 OR ca_collection_labels.is_preferred IS NULL)'),
						'includeSelf' => false
					)))) {
						$va_collection_ancestor_list = [];
					}
					$vn_i = 1;
					foreach($va_collection_ancestor_list as $vn_id => $va_collection_ancestor) {
						$va_related_collections_by_level[$va_collection_ancestor['NODE']['collection_id']] = array(
							'item_id' => $va_collection_ancestor['NODE']['collection_id'],
							'parent_id' => $va_collection_ancestor['NODE']['parent_id'],
							'label' => $va_collection_ancestor['NODE']['name'],
							'idno' => $va_collection_ancestor['NODE']['idno'],
							'table' => 'ca_collections'
						);
						$vn_i++;
					}
					break; // only process the first collection (for now)
				}
				$o_view->setVar('object_collection_collection_ancestors', array_reverse($va_related_collections_by_level, true));
			}
		}
		
		$vn_first_id = null;
		if ($pb_batch && ($pn_set_id = caGetOption('set_id', $pa_options, null))) { 
			$t_set = new ca_sets($pn_set_id); 
			if (is_array($va_ids = $t_set->getItemRowIDs()) && sizeof($va_ids)) {
				$vn_first_id = array_shift($va_ids);
			}
		}
		
		$o_view->setVar('batch', $pb_batch);
		$o_view->setVar('parent_id', $vn_parent_id);
		$o_view->setVar('ancestors', $va_ancestor_list);
		$o_view->setVar('id', $pb_batch && $vn_first_id ? $vn_first_id : $this->getPrimaryKey());
		$o_view->setVar('settings', $pa_bundle_settings);
		
		return $o_view;
	}
	# ------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @param string $ps_form_name
	 * @param string $ps_related_table
	 * @param null|string $ps_placement_code
	 * @param null|array $pa_bundle_settings
	 * @param null|arrau $pa_options
	 * @return array|mixed
	 */
	public function getRelatedBundleFormValues($po_request, $ps_form_name, $ps_related_table, $ps_placement_code=null, $pa_bundle_settings=null, $pa_options=null) {
		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = []; }
		if(!is_array($pa_options)) { $pa_options = []; }

		/** @var BundlableLabelableBaseModelWithAttributes $t_item */
		$t_item = Datamodel::getInstance($ps_related_table);
		$vb_is_many_many = false;
		
		$start = caGetOption('start', $pa_options, 0);
		$limit = caGetOption('limit', $pa_options, caGetOption('numPerPage', $pa_bundle_settings, null));
		
		$va_path = array_keys(Datamodel::getPath($this->tableName(), $ps_related_table));
		if ($this->tableName() == $ps_related_table) {
			// self relationship
			$t_item_rel = Datamodel::getInstance($va_path[1]);
			$vb_is_many_many = true;
		} else {
			switch(sizeof($va_path)) {
				case 3:
					// many-many relationship
					$t_item_rel = Datamodel::getInstance($va_path[1]);
					$vb_is_many_many = true;
					break;
				case 2:
					// many-one relationship
					$t_item_rel = Datamodel::getInstance($va_path[1]);
					break;
				default:
					$t_item_rel = null;
					break;
			}
		}
		
		$va_get_related_opts = array_merge($pa_options, $pa_bundle_settings);
		if (isset($pa_bundle_settings['restrictToTermsRelatedToCollection']) && $pa_bundle_settings['restrictToTermsRelatedToCollection']) {
			$va_get_related_opts['restrict_to_relationship_types'] = $pa_bundle_settings['restrictToTermsOnCollectionUseRelationshipType'];
		} 
		
		$sort_direction = caGetOption('sortDirection', $pa_options, caGetOption('sortDirection', $pa_bundle_settings, null));	
		if(!($sort = caGetOption('sort', $pa_options, caGetOption('sort', $pa_bundle_settings, null)))) {
		 	$default_sorts = $this->getAppConfig()->get("{$ps_related_table}_default_bundle_display_sorts");
 			$sort = is_array($default_sorts) ? caGetOption('sort', $default_sorts, null) : null;
 			$sort_direction = is_array($default_sorts) ? caGetOption('direction', $default_sorts, 'ASC') : 'ASC';
		}
		
		if ($sort) {
			$va_get_related_opts['sort'] = $sort;
			$va_get_related_opts['sortDirection'] = $sort_direction;
		}
		
		$t_rel = Datamodel::getInstance($ps_related_table, true);
		$va_opts = [
			'table' => $vb_is_many_many ? $t_rel->tableName() : null,
			'primaryKey' => $vb_is_many_many ? $t_rel->primaryKey() : null,
			'template' => caGetBundleDisplayTemplate($this, $ps_related_table, $pa_bundle_settings),
			'primaryIDs' => array($this->tableName() => array($this->getPrimaryKey())),
			'request' => $po_request,
			'stripTags' => true,
			
			'start' => $start,
			'limit' => $limit
		];


		$policy = caGetOption('showCurrentOnly', $pa_bundle_settings, false) ? caGetOption('policy', $pa_bundle_settings, null) : null;

		if($ps_related_table == 'ca_sets') {
			// sets special case
			
			$t_set = new ca_sets();
			$va_items = caExtractValuesByUserLocale($t_set->getSetsForItem($this->tableNum(), $this->getPrimaryKey(), $va_get_related_opts));

			// sort
			if($ps_sort = caGetOption('sort', $va_get_related_opts, null)) {
				$va_items = caSortArrayByKeyInValue($va_items, array($ps_sort), caGetOption('sortDirection', $va_get_related_opts, 'ASC'));
			}

			$va_vals = [];
			$vs_template = caGetBundleDisplayTemplate($this, 'ca_sets', $pa_bundle_settings);
			if(is_array($va_items) && sizeof($va_items)) {
				foreach($va_items as $vn_id => $va_item) {
					$va_item['_display'] = caProcessTemplateForIDs($vs_template, 'ca_sets', array($vn_id));
					$va_vals[$vn_id] = $va_item;
				}
			}

			return $va_vals;
		} else {
			// if (($policy) && is_array($h = $this->getContents($policy, ['returnHistoryTrackingData' => true]))) {	
// 				$rel_table_num = (int)$t_rel->tableNum(); 
// 				$rel_pk = $t_rel->primaryKey();
// 				
// 				$va_items = array_map(function($v) use ($rel_pk) { 
// 					return ['relation_id' => $v['tracked_row_id'], $rel_pk => $v['row_id']];	// Convert history list to item list format required below
// 				}, array_filter($h, function($v) use ($rel_table_num) {
// 					return ((int)$v['table_num'] === $rel_table_num);		// Filter out any current values thar aren't for the the related table
// 				}));
// 			} else {
				$va_items = $this->getRelatedItems($ps_related_table, array_merge($va_get_related_opts, ['returnAs' => 'data', 'sort' => $sort, 'sortDirection' => $sort_direction, 'start' => $start, 'limit' => $limit]));
			//}
			
			if ($vb_is_many_many) {
				$va_ids = caExtractArrayValuesFromArrayOfArrays($va_items, 'relation_id');
				$qr_rel_items = $t_item->makeSearchResult($t_item_rel->tableNum(), $va_ids, ['sort' => $sort, 'sortDirection' => $sort_direction]);
			} else {
				$va_ids = caExtractArrayValuesFromArrayOfArrays($va_items, $t_rel->primaryKey());
				$qr_rel_items = $t_item->makeSearchResult($t_rel->tableNum(), $va_ids, ['sort' => $sort, 'sortDirection' => $sort_direction]);
			}

			$va_opts['relatedItems'] = $va_items;
			
			if (!$sort) {
				$va_opts['sortOrder'] = array_keys(caSortArrayByKeyInValue($va_items, ['rank']));
			} else {
				$va_opts['sortOrder'] = array_keys($va_items);
			}
			return caProcessRelationshipLookupLabel($qr_rel_items, $t_item_rel, $va_opts);
		}

		return [];
	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
	public function getRelatedHTMLFormBundle($po_request, $ps_form_name, $ps_related_table, $ps_placement_code=null, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		AssetLoadManager::register('sortableUI');
		
		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = []; }
		if(!is_array($pa_options)) { $pa_options = []; }
		
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		$t_item = Datamodel::getInstance($ps_related_table);
		$vb_is_many_many = false;
		$va_path = array_keys(Datamodel::getPath($this->tableName(), $ps_related_table));
		if ($this->tableName() == $ps_related_table) {
			// self relationship
			$t_item_rel = Datamodel::getInstance($va_path[1]);
			$vb_is_many_many = true;
		} else {
			switch(sizeof($va_path)) {
				case 3:
					// many-many relationship
					$t_item_rel = Datamodel::getInstance($va_path[1]);
					$vb_is_many_many = true;
					break;
				case 2:
					// many-one relationship
					$t_item_rel = Datamodel::getInstance($va_path[1]);
					break;
				default:
					if($ps_related_table == 'ca_sets') {
						$t_item_rel = new ca_sets();
					} else {
						$t_item_rel = null;
					}
					break;
			}
		}
		
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('t_instance', $this);
		$o_view->setVar('t_item', $t_item);
		$o_view->setVar('t_item_rel', $t_item_rel);
		$o_view->setVar('bundle_name', $ps_related_table);
		
		list('defaultSorts' => $default_sorts, 'typeSpecificSorts' => $type_specific_sorts, 'sortOptions' => $sort_options) = caGetDefaultEditorBundleSortConfiguration($this->tableName(), $ps_related_table, $pa_bundle_settings);

		
		// Look for sort settings in parameter options, bundle settings and then app.conf defaults
		foreach([$pa_options, $pa_bundle_settings, $type_specific_sorts, $default_sorts] as $i => $opts) {
			if($sort = caGetOption('sort', $opts, null)) {
				$sort_direction = caGetOption(['sortDirection', 'direction'], $opts, 'ASC');
				break;
			}
		}
		
		$o_view->setVar('sort', $sort);
		$o_view->setVar('sortDirection', $sort_direction, null);
		
		// Copy sort settings over bundle defaults in case we're using app.conf defaults, as we'll
		// want the bunde to reflect the settings actually used
		$pa_bundle_settings['sort'] = $sort;
		$pa_bundle_settings['sortDirection'] = $sort_direction;
		
		$o_view->setVar('ui', caGetOption('ui', $pa_options, null));
		$o_view->setVar('screen', caGetOption('screen', $pa_options, null));
		
		$vb_read_only = ($po_request->user->getBundleAccessLevel($this->tableName(), $ps_related_table) == __CA_BUNDLE_ACCESS_READONLY__) ? true : false;
		if (!$pa_bundle_settings['readonly']) { $pa_bundle_settings['readonly'] = (!isset($pa_bundle_settings['readonly']) || !$pa_bundle_settings['readonly']) ? $vb_read_only : true;	}
		
		// pass bundle settings
		if(!is_array($pa_bundle_settings['prepopulateQuickaddFields'])) { $pa_bundle_settings['prepopulateQuickaddFields'] = []; }
		
		if (method_exists($t_item, 'policy2bundleconfig') && ($default_policy = $t_item->getDefaultHistoryTrackingCurrentValuePolicy())) {	// TODO: make policy configurable?
			$o_view->setVar('history_tracking_policy', $default_policy);
			$o_view->setVar('history_tracking_policy_info', $t_item::policy2bundleconfig(['policy' => $default_policy]));
		}
		
		if ($po_request->config->get('always_show_counts_for_relationship_bundles_in_editor')) { 
			$pa_bundle_settings['showCount'] = true;
		}
		$o_view->setVar('settings', $pa_bundle_settings);
		$o_view->setVar('graphicsPath', $pa_options['graphicsPath']);
		
		// pass placement code
		$o_view->setVar('placement_code', $ps_placement_code);
		
		// quickadd available?
		$vb_quickadd_enabled = (bool)$po_request->user->canDoAction("can_quickadd_{$ps_related_table}");
		if ($pa_bundle_settings['disableQuickadd']) { $vb_quickadd_enabled = false; }
		$o_view->setVar('quickadd_enabled', $vb_quickadd_enabled);
		
		$o_view->setVar('add_label', caExtractSettingValueByLocale($pa_bundle_settings, 'add_label', $g_ui_locale));
		
		$t_label = null;
		if ($t_item->getLabelTableName()) {
			$t_label = Datamodel::getInstanceByTableName($t_item->getLabelTableName(), true);
		}
		if (method_exists($t_item_rel, 'getRelationshipTypes')) {
			$o_view->setVar('relationship_types', $t_item_rel->getRelationshipTypes(null, null,  array_merge($pa_options, $pa_bundle_settings)));
			$o_view->setVar('relationship_types_by_sub_type', $t_item_rel->getRelationshipTypesBySubtype($this->tableName(), $this->get('type_id'),  array_merge($pa_options, $pa_bundle_settings)));
		}

		$o_view->setVar('t_subject', $this);
		$va_initial_values = $this->getRelatedBundleFormValues($po_request, $ps_form_name, $ps_related_table, $ps_placement_code, $pa_bundle_settings, $pa_options);

		$c = (caGetOption('limit', $pa_options, caGetOption('numPerPage', $pa_bundle_settings, null)) > 0) ? $this->getRelatedItems($ps_related_table, array_merge($pa_bundle_settings,['returnAs' => 'count'])) : sizeof($va_initial_values);
		$o_view->setVar('relationship_count', $c);
		
		$va_force_new_values = [];
		if (isset($pa_options['force']) && is_array($pa_options['force'])) {
			foreach($pa_options['force'] as $vn_id) {
				if ($t_item->load($vn_id)) {
					$va_item = $t_item->getFieldValuesArray();
					if ($t_label) {
						$va_item[$t_label->getDisplayField()] =  $t_item->getLabelForDisplay();
					}
					$va_force_new_values[$vn_id] = array_merge(
						$va_item, 
						array(
							'id' => $vn_id, 
							'idno' => ($vn_idno = $t_item->get('idno')) ? $vn_idno : null, 
							'idno_stub' => ($vn_idno_stub = $t_item->get('idno_stub')) ? $vn_idno_stub : null, 
							'item_type_id' => $t_item->getTypeID(),
							'relationship_type_id' => null
						)
					);
				}
			}
		}
		
		$o_view->setVar('defaultRepresentationUploadType', $po_request->user->getVar('defaultRepresentationUploadType'));

		$o_view->setVar('initialValues', $va_initial_values);
		$o_view->setVar('forceNewValues', $va_force_new_values);
		$o_view->setVar('batch', (bool)(isset($pa_options['batch']) && $pa_options['batch']));
		
		return $o_view->render($ps_related_table.'.php');
	}
	# ------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @param string $ps_bundle_name
	 * @param string $ps_form_name
	 * @param null|string $ps_placement_code
	 * @param null|array $pa_bundle_settings
	 * @param null|array $pa_options
	 * @return mixed|null|string
	 */
	public function getRelatedListHTMLFormBundle($po_request, $ps_bundle_name, $ps_form_name, $ps_placement_code=null, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;

		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = []; }
		if(!is_array($pa_options)) { $pa_options = []; }

		$vs_table_name = preg_replace("/_related_list|_table$/", '', $ps_bundle_name);
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");

		$va_path = array_keys(Datamodel::getPath($this->tableName(), $vs_table_name));
		require_once(__CA_MODELS_DIR__."/{$vs_table_name}.php");
		$t_item = new $vs_table_name;
		/** @var BaseRelationshipModel $t_item_rel */
		$t_item_rel = Datamodel::getInstance($va_path[1]);

		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('bundle_name', $ps_bundle_name);
		$o_view->setVar('t_instance', $this);
		$o_view->setVar('t_subject', $this);
		$o_view->setVar('t_item', $t_item);
		$o_view->setVar('t_item_rel', $t_item_rel);

		$vb_read_only = ($po_request->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) == __CA_BUNDLE_ACCESS_READONLY__) ? true : false;
		if (!$pa_bundle_settings['readonly']) { $pa_bundle_settings['readonly'] = (!isset($pa_bundle_settings['readonly']) || !$pa_bundle_settings['readonly']) ? $vb_read_only : true;	}

		if(!is_array($pa_bundle_settings['prepopulateQuickaddFields'])) { $pa_bundle_settings['prepopulateQuickaddFields'] = []; }
		$o_view->setVar('settings', $pa_bundle_settings);
		$o_view->setVar('placement_code', $ps_placement_code);
		$o_view->setVar('add_label', caExtractSettingValueByLocale($pa_bundle_settings, 'add_label', $g_ui_locale));

		$o_view->setVar('relationship_types', method_exists($t_item_rel, 'getRelationshipTypes') ? $t_item_rel->getRelationshipTypes(null, null,  array_merge($pa_options, $pa_bundle_settings)) : []);
		$o_view->setVar('relationship_types_by_sub_type',  method_exists($t_item_rel, 'getRelationshipTypesBySubtype') ? $t_item_rel->getRelationshipTypesBySubtype($this->tableName(), $this->get('type_id'),  array_merge($pa_options, $pa_bundle_settings)) : []);

		$va_initial_values = $this->getRelatedBundleFormValues($po_request, $ps_form_name, $vs_table_name, $ps_placement_code, $pa_bundle_settings, $pa_options);

		$o_view->setVar('initialValues', $va_initial_values);
		$o_view->setVar('result', caMakeSearchResult($vs_table_name, array_keys($va_initial_values)));
		$o_view->setVar('batch', (bool)(isset($pa_options['batch']) && $pa_options['batch']));

		return $o_view->render('related_list.php');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	protected function getGenericFormBundle(RequestHTTP $request, string $form_name, string $placement_code, ?array $options=null, ?array $bundle_settings=null) {
		$view_path = (isset($options['viewPath']) && $options['viewPath']) ? $options['viewPath'] : $request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$view_path}/bundles/");

		$o_view->setVar('t_subject', $this);
		$o_view->setVar('settings', $bundle_settings);
		
		return $o_view->render('generic.php');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	protected function getBundleListsForScreen($pm_screen, $po_request, $t_ui, $pa_options=null) {
		if (!(($va_bundles = caGetOption('bundles', $pa_options, null)) && is_array($va_bundles))) {
			if(!$t_ui) { return; }
			$va_bundles = $t_ui->getScreenBundlePlacements($pm_screen);
		}
		
		// sort fields by type
		$va_fields_by_type = [];
		if (is_array($va_bundles)) {
			foreach($va_bundles as $vn_i => $va_tmp) {
				if (isset($va_tmp['settings']['readonly']) && (bool)$va_tmp['settings']['readonly']) { continue; }			// don't attempt to save "read-only" bundles
				
				if (($po_request->user->getBundleAccessLevel($this->tableName(), $va_tmp['bundle_name'])) < __CA_BUNDLE_ACCESS_EDIT__) {	// don't save bundles use doesn't have edit access for
					continue;
				}
				$va_info = $this->getBundleInfo($va_tmp['bundle_name']);
				$va_fields_by_type[$va_info['type']]['P'.$va_tmp['placement_id']] = $va_tmp['bundle_name'];
			}
		}
			
		// auto-add mandatory fields if this is a new object
		if (!is_array($va_fields_by_type['intrinsic'])) { $va_fields_by_type['intrinsic'] = []; }
		if (!$this->getPrimaryKey()) {
			if (is_array($va_mandatory_fields = $this->getMandatoryFields())) {
				foreach($va_mandatory_fields as $vs_field) {
					if (!in_array($vs_field, $va_fields_by_type['intrinsic'])) {
						$va_fields_by_type['intrinsic']['mandatory_'.$vs_field] = $vs_field;
					}
				}
			}
			
			// add parent_id
			if ($this->HIERARCHY_PARENT_ID_FLD) {
				$va_fields_by_type['intrinsic'][] = $this->HIERARCHY_PARENT_ID_FLD;
			}
		}
		
		// auto-add lot_id if it's set in the request and not already on the list (supports "add new object to lot" functionality)
		if (($this->tableName() == 'ca_objects') && (!in_array('lot_id', $va_fields_by_type['intrinsic'])) && ($po_request->getParameter('lot_id', pInteger))) {
			$va_fields_by_type['intrinsic'][] = 'lot_id';
		}
		
		return array('bundles' => $va_bundles, 'fields_by_type' => $va_fields_by_type);
	}
	# ------------------------------------------------------
	/**
	 *  
	 *
	 * @param mixed $pm_screen
	 * @param RequestHTTP $po_request The current request
	 * @param array $pa_options Options are:
	 *		dontReturnSerialIdno = if set idno's are always returned as set in the request rather than being returns as SERIAL values if so configured.
	 *
	 * @return array
	 */
	public function extractValuesFromRequest($pm_screen, $po_request, $pa_options) {
		
		// get items on screen
		if (isset($pa_options['ui_instance']) && ($pa_options['ui_instance'])) {
 			$t_ui = $pa_options['ui_instance'];
 		} else {
			$t_ui = ca_editor_uis::loadDefaultUI($this->tableName(), $po_request, $this->getTypeID());
 		}
		
		$va_bundle_lists = $this->getBundleListsForScreen($pm_screen, $po_request, $t_ui, $pa_options);
		$table_name = $this->tableName();
		
		
		$va_values = [];
		
		// Get intrinsics
		$va_values['intrinsic'] = [];
		if (is_array($va_bundle_lists['fields_by_type']['intrinsic'])) {
			$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
			foreach($va_bundle_lists['fields_by_type']['intrinsic'] as $vs_f) {
				if (!isset($_REQUEST[$vs_f])) { continue; }
				switch($vs_f) {
					case $vs_idno_field:
						if (($this->opo_idno_plugin_instance) && !$pa_options['dontReturnSerialIdno']) {
							$this->opo_idno_plugin_instance->setDb($this->getDb());
							$va_values['intrinsic'][$vs_f] = $this->opo_idno_plugin_instance->htmlFormValue($vs_idno_field, null, true);
						} else {
							$va_values['intrinsic'][$vs_f] = $po_request->getParameter($vs_f, pString);
						}
						break;
					default:
						$va_values['intrinsic'][$vs_f] = $po_request->getParameter($vs_f, pString);
						break;
				}
			}
		}
		
		// Get attributes
		$va_attributes_by_element = $va_attributes = [];
		$vs_form_prefix = $po_request->getParameter('_formName', pString);
		
		if (is_array($va_bundle_lists['fields_by_type']['attribute'])) {
			foreach($va_bundle_lists['fields_by_type']['attribute'] as $vs_placement_code => $vs_f) {
				
				$va_attributes_to_insert = [];
				$va_attributes_to_delete = [];
				$va_locales = [];
				foreach($_REQUEST as $vs_key => $vs_val) {
					$vs_element_set_code = preg_replace("/^ca_attribute_/", "", $vs_f);
					$vs_element_set_code = preg_replace("/^{$table_name}\./", "", $vs_element_set_code);
					
					if (!($vn_element_id = ca_metadata_elements::getElementID($vs_element_set_code))) { continue; }
					
					if (
						preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\-_]+)_new_([\d]+)/', $vs_key, $va_matches)
						||
						preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\-_]+)_([\d]+)/', $vs_key, $va_matches)
					) { 
						$vn_c = intval($va_matches[2]);
						// yep - grab the locale and value
						$vn_locale_id = isset($_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_'.$vn_c]) ? $_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_'.$vn_c] : null;
						
						$va_attributes_by_element[$vn_element_id][$vn_c]['locale_id'] = $va_attributes[$vn_c]['locale_id'] = $vn_locale_id; 
						$va_attributes_by_element[$vn_element_id][$vn_c][$va_matches[1]] = $va_attributes[$vn_c][$va_matches[1]] = $vs_val;
					} 
				}
			}
		}
		$va_values['attributes'] = $va_attributes_by_element;
		
		// Get preferred labels
		$va_preferred_labels = [];
		if (is_array($va_bundle_lists['fields_by_type']['preferred_label'])) {
			foreach($va_bundle_lists['fields_by_type']['preferred_label'] as $vs_placement_code => $vs_f) {
				foreach($_REQUEST as $vs_key => $vs_value ) {
					if (
						!preg_match('/'.$vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_(new_[\d]+)/', $vs_key, $va_matches)
						&&
						!preg_match('/'.$vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_([\d]+)/', $vs_key, $va_matches)
					) { continue; }
					$vn_c = $va_matches[1];
					if (
						($vn_label_locale_id = $vs_value)
					) {
						if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, $vn_c, true))) {
							$va_label_values['locale_id'] = $vn_label_locale_id;
							$va_preferred_labels[$vn_label_locale_id] = $va_label_values;
						}
					}
				}
			}
		}
		$va_values['preferred_label'] = $va_preferred_labels;
		
		// Get annotation properties
		if (method_exists($this, "getPropertyList")) {
			foreach($this->getPropertyList() as $vs_property) {
				$va_values['annotation_properties'][$vs_property] = $po_request->getParameter($vs_property, pString);
			}
		}
		
		return $va_values;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function saveBundlesForScreenWillChangeParent($pm_screen, $po_request, &$pa_options) {
		$vs_form_prefix = caGetOption('formName', $pa_options, $po_request->getParameter('_formName', pString));
			
		foreach($_REQUEST as $vs_k => $vs_v) {
			if (!preg_match("!{$vs_form_prefix}_new_parent_id$!", $vs_k)) { continue; }
			
			$va_parent_tmp = explode("-", $po_request->getParameter($vs_k, pString));
	
			// Hierarchy browser sets new_parent_id param to "X" if user wants to extract item from hierarchy
			$vn_parent_id = (($vn_parent_id = array_pop($va_parent_tmp)) == 'X') ? -1 : (int)$vn_parent_id;
			if (sizeof($va_parent_tmp) > 0) { $vs_parent_table = array_pop($va_parent_tmp); } else { $vs_parent_table = $this->tableName(); }
	
			if ($this->getPrimaryKey() && $this->HIERARCHY_PARENT_ID_FLD && ($vn_parent_id > 0)) {
		
				if ($vs_parent_table == $this->tableName()) {
					if ($vn_parent_id != $this->getPrimaryKey()) { return __CA_PARENT_CHANGED__; }
				} else {
					if ((bool)$this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && ($vs_parent_table == 'ca_collections') && ($this->tableName() == 'ca_objects') && ($vs_coll_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type'))) {
						return __CA_PARENT_COLLECTION_CHANGED__;
					}
				}
			} else {
				if ($this->getPrimaryKey() && $this->HIERARCHY_PARENT_ID_FLD && ($this->HIERARCHY_TYPE == __CA_HIER_TYPE_ADHOC_MONO__) && isset($_REQUEST[$vs_k]) && ($vn_parent_id <= 0)) {
					return __CA_PARENT_COLLECTION_CHANGED__;
				}
			}
			break;
		}
		return __CA_PARENT_UNCHANGED__;
	}
	# ------------------------------------------------------
	/**
	* Saves all bundles on the specified screen in the database by extracting 
	* required data from the supplied request
	* $pm_screen can be a screen tag (eg. "Screen5") or a screen_id (eg. 5)
	*
	* Calls processBundlesBeforeBaseModelSave() method in subclass right before invoking insert() or update() on
	* the BaseModel, if the method is defined. Passes the following parameters to processBundlesBeforeBaseModelSave():
	*		array $pa_bundles An array of bundles to be saved
	*		string $ps_form_prefix The form prefix
	*		RequestHTTP $po_request The current request
	*		array $pa_options Optional array of parameters; expected to be the same as that passed to saveBundlesForScreen()
	*
	* The processBundlesBeforeBaseModelSave() is useful for those odd cases where you need to do some processing before the basic
	* database record defined by the model (eg. intrinsic fields and hierarchy coding) is inserted or updated. You usually don't need 
	* to use it.
	*
	* @param mixed $pm_screen
	* @param RequestHTTP $po_request
	* @param array $pa_options Options are:
	*		formName = 
	*		dryRun = Go through the motions of saving but don't actually write information to the database
	*		batch = Process save in "batch" mode. Specifically this means honoring batch mode settings (add, replace, remove), skipping bundles that are not supported in batch mode and ignoring updates
	*		existingRepresentationMap = an array of representation_ids key'ed on file path. If set saveBundlesForScreen() use link the specified representation to the row it is saving rather than processing the uploaded file. saveBundlesForScreen() will build the map as it goes, adding newly uploaded files. If you want it to process a file in a batch situation where it should be processed the first time and linked subsequently then pass an empty array here. saveBundlesForScreen() will use the empty array to build the map.
	 * @return mixed
	*/
	public function saveBundlesForScreen($pm_screen, $po_request, &$pa_options) {
	    global $g_ui_locale_id;
	    $table = $this->tableName();
										
		$vb_we_set_transaction = false;
		$vs_form_prefix = caGetOption('formName', $pa_options, $po_request->getParameter('_formName', pString));
		
		$vb_dryrun = caGetOption('dryRun', $pa_options, false);
		$vb_batch = caGetOption('batch', $pa_options, false); 
		
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			if ($vb_dryrun) {
				$this->postError(799, _t('Cannot do dry run save when in transaction. Try again without setting a transaction.'), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");				
				
				return false;
			}
		}
		
		$va_errors = [];
			
		$vb_read_only_because_deaccessioned = ($this->hasField('is_deaccessioned') && (bool)$this->getAppConfig()->get('deaccession_dont_allow_editing') && (bool)$this->get('is_deaccessioned'));

		BaseModel::setChangeLogUnitID();
		// get items on screen
		$t_ui = caGetOption('ui_instance', $pa_options, ca_editor_uis::loadDefaultUI($table, $po_request, $this->getTypeID()));
		
		$va_bundle_lists = $this->getBundleListsForScreen($pm_screen, $po_request, $t_ui, $pa_options);

		// 
		// Filter bundles to save if deaccessioned - only allow editing of the ca_objects_deaccession bundle
		//
		if ($vb_read_only_because_deaccessioned) {
			foreach($va_bundle_lists['bundles'] as $vn_i => $va_bundle) {
				if ($va_bundle['bundle_name'] !== 'ca_objects_deaccession') {
					unset($va_bundle_lists['bundles'][$vn_i]);
				}
			}
			foreach($va_bundle_lists['fields_by_type'] as $vs_type => $va_bundles) {
				foreach($va_bundles as $vs_id => $vs_bundle_name) {
					if ($vs_bundle_name !== 'ca_objects_deaccession') {
						unset($va_bundle_lists['fields_by_type'][$vs_type][$vs_id]);
					}
				}
			}
		}
		
		$va_bundles = $va_bundle_lists['bundles'];
		$va_fields_by_type = $va_bundle_lists['fields_by_type'];

		// save intrinsic fields
		if (is_array($va_fields_by_type['intrinsic'])) {
			$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
			$seen_fields = [];
			foreach($va_fields_by_type['intrinsic'] as $vs_placement_code => $vs_f) {
				
				// convert intrinsics to bare field names if they include tablename (eg. ca_objects.idno => idno)
				$va_tmp = explode('.', $vs_f);
				if (($this->tableName() === $va_tmp[0]) && $this->hasField($va_tmp[1])) {
					$vs_f = $va_tmp[1];
				}
				
				if(isset($seen_fields[$vs_f])) { continue; }
				$seen_fields[$vs_f] = true;
		
				if ($vb_batch) { 
					$vs_batch_mode = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_batch_mode", pString);
					if($vs_batch_mode == '_disabled_') { continue; }
				}
				if (isset($_FILES["{$vs_placement_code}{$vs_form_prefix}{$vs_f}"]) && $_FILES["{$vs_placement_code}{$vs_form_prefix}{$vs_f}"]) {
					// media field
					$this->set($vs_f, $_FILES["{$vs_placement_code}{$vs_form_prefix}{$vs_f}"]['tmp_name'], array('original_filename' => $_FILES["{$vs_placement_code}{$vs_form_prefix}{$vs_f}"]['name']));
				} else {
					switch($vs_f) {
						case 'access':
							if ((bool)$this->getAppConfig()->get($this->tableName().'_allow_access_inheritance') && $this->hasField('access_inherit_from_parent')) {	
								$this->set('access_inherit_from_parent', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}access_inherit_from_parent", pInteger));
							}
							if (!(bool)$this->getAppConfig()->get($this->tableName().'_allow_access_inheritance') || !$this->hasField('access_inherit_from_parent') || !(bool)$this->get('access_inherit_from_parent')) {	
								$this->set('access', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}access", pString));
							}
							break;
						case $vs_idno_field:
							if(!(bool)$this->getAppConfig()->get($this->tableName().'_dont_allow_editing_of_codes_when_in_use') || !$this->getPrimaryKey()) {
								if ($this->opo_idno_plugin_instance) {
									$this->opo_idno_plugin_instance->setDb($this->getDb());
									if (isset($va_fields_by_type['intrinsic']['mandatory_type_id'])) {
										$this->set('type_id', $_REQUEST['type_id']);
									}
									$this->set($vs_f, $vs_tmp = $this->opo_idno_plugin_instance->htmlFormValue($vs_idno_field));
								} else {
									$this->set($vs_f, $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}{$vs_f}", pString));
								}
							}
							break;
						default:
							// Look for fully qualified intrinsic
							if(!strlen($vs_v = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}{$vs_f}", pString))) {
								// fall back to simple field name intrinsic spec - still used for "mandatory" fields such as type_id and parent_id
								$vs_v = $po_request->getParameter("{$vs_f}", pString);
							}
							$this->set($vs_f, $vs_v);
							break;
					}
				}
				if ($this->numErrors() > 0) {
					foreach($this->errors() as $o_e) {
						switch($o_e->getErrorNumber()) {
							case 795:
								// field conflicts
								foreach($this->getFieldConflicts() as $vs_conflict_field) {
									$po_request->addActionError($o_e, $vs_conflict_field);
								}
								break;
							default:
								$po_request->addActionError($o_e, $vs_f);
								break;
						}
					}
				}
			}
		}

		// save attributes
		$va_inserted_attributes_by_element = [];
		if (isset($va_fields_by_type['attribute']) && is_array($va_fields_by_type['attribute'])) {
			//
			// name of attribute request parameters are:
			// 	For new attributes
			// 		{$vs_form_prefix}_attribute_{element_set_id}_{element_id|'locale_id'}_new_{n}
			//		ex. ObjectBasicForm_attribute_6_locale_id_new_0 or ObjectBasicForm_attribute_6_desc_type_new_0
			//
			// 	For existing attributes:
			// 		{$vs_form_prefix}_attribute_{element_set_id}_{element_id|'locale_id'}_{attribute_id}
			//
			
			// look for newly created attributes; look for attributes to delete
			$va_inserted_attributes = [];
			$reserved_elements = [];
			foreach($va_fields_by_type['attribute'] as $vs_placement_code => $vs_f) {
				$vs_element_set_code = preg_replace("/^(ca_attribute_|".$this->tableName()."\.)/", "", $vs_f);
				
				// does the attribute's datatype have a saveElement method? If so, use that instead
				$t_element = ca_metadata_elements::getInstance($vs_element_set_code);

				$vn_element_id = $t_element->getPrimaryKey();
				$vs_element_datatype = $t_element->get('datatype');
				$include_source_data = $t_element->getSetting('includeSourceData');
				
				$vs_datatype = Attribute::getValueInstance($vs_element_datatype);
				if(method_exists($vs_datatype,'saveElement')) {
					$reserved_elements[] = $t_element;
					continue;
				}
				
				$va_attributes_to_insert = [];
				$va_attributes_to_delete = [];
				$va_locales = [];
				
				$vs_batch_mode = $_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_batch_mode'];
				
				if ($vb_batch && ($vs_batch_mode == '_delete_')) {		// Remove all attributes and continue
					$this->removeAttributes($vn_element_id, array('force' => true));
					continue;
				}
				
				foreach($_REQUEST as $vs_key => $vs_val) {
					// is it a newly created attribute?
					if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\d\-_]+)_new_([\d]+)/', $vs_key, $va_matches)) { 
						if ($vb_batch) {
							switch($vs_batch_mode) {
								case '_disabled_':		// skip
									continue(2);
									break;
								case '_add_':			// just try to add attribute as in normal non-batch save
									// noop
									break;
								case '_replace_':		// remove all existing attributes before trying to save
									$this->removeAttributes($vn_element_id, array('force' => true));
									break;
							}
						}
						
						$vn_c = intval($va_matches[2]);
						// yep - grab the locale and value
						$vn_locale_id = $_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_'.$vn_c] ?? null;
						
						$va_inserted_attributes_by_element[$vn_element_id][$vn_c]['locale_id'] = $va_attributes_to_insert[$vn_c]['locale_id'] = $vn_locale_id; 
						
						if ($include_source_data) { 
							$value_source = $_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_value_source_new_'.$vn_c] ?? null;
							$va_inserted_attributes_by_element[$vn_element_id][$vn_c]['value_source'] = $va_attributes_to_insert[$vn_c]['value_source'] = $value_source; 
						}
						$va_inserted_attributes_by_element[$vn_element_id][$vn_c][$va_matches[1]] = $va_attributes_to_insert[$vn_c][$va_matches[1]] = $vs_val;
						
					} else {
						// is it a delete key?
						if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\d]+)_delete/', $vs_key, $va_matches)) {
							$vn_attribute_id = intval($va_matches[1]);
							$va_attributes_to_delete[$vn_attribute_id] = true;
						}
					}
				}
				
				// look for uploaded files as attributes
				foreach($_FILES as $vs_key => $va_val) {
					if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_([\d]+)/', $vs_key, $va_locale_matches)) { 
						$vn_locale_c = intval($va_locale_matches[1]);
						$va_locales[$vn_locale_c] = $vs_val;
						continue; 
					}
					// is it a newly created attribute?
					if (preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\d\-_]+)_new_([\d]+)/', $vs_key, $va_matches)) { 
						if (!$va_val['size']) { continue; }	// skip empty files
						
						// yep - grab the value
						$vn_c = intval($va_matches[2]);
						$va_inserted_attributes_by_element[$vn_element_id][$vn_c]['locale_id'] = $va_attributes_to_insert[$vn_c]['locale_id'] = $va_locales[$vn_c]; 
						$va_val['_uploaded_file'] = true;
						$va_inserted_attributes_by_element[$vn_element_id][$vn_c][$va_matches[1]] = $va_attributes_to_insert[$vn_c][$va_matches[1]] = $va_val;
					}
				}
				
if (!$vb_batch) {				
				// do deletes
				$this->clearErrors();
				foreach($va_attributes_to_delete as $vn_attribute_id => $vb_tmp) {
					$this->removeAttribute($vn_attribute_id, $vs_f, array('pending_adds' => $va_attributes_to_insert));
				}
}				
				// do inserts
				foreach($va_attributes_to_insert as $va_attribute_to_insert) {
					$this->clearErrors();
					if(!$this->addAttribute($va_attribute_to_insert, $vn_element_id, null, ['batch' => $vb_batch])) {
						$po_request->addActionErrors($this->errors);
					}
				}
				
if (!$vb_batch) {					
				// check for attributes to update
				if (is_array($va_attrs = $this->getAttributesByElement($vn_element_id))) {
					$t_element = new ca_metadata_elements();
							
					$va_attrs_update_list = [];
					foreach($va_attrs as $o_attr) {
						$this->clearErrors();
						$vn_attribute_id = $o_attr->getAttributeID();
						if (isset($va_inserted_attributes[$vn_attribute_id]) && $va_inserted_attributes[$vn_attribute_id]) { continue; }
						if (isset($va_attributes_to_delete[$vn_attribute_id]) && $va_attributes_to_delete[$vn_attribute_id]) { continue; }
						
						$vn_element_set_id = $o_attr->getElementID();

						// skip element
						if($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_dont_save_'.$vn_attribute_id, pInteger)) {
							continue;
						}
						
						$va_attr_update = [
							'locale_id' =>  $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_locale_id_'.$vn_attribute_id, pString),
						];
						if ($include_source_data) {
							$va_attr_update['value_source'] = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_value_source_'.$vn_attribute_id, pString);
						}
						
						//
						// Check to see if there are any values in the element set that are not in the  attribute we're editing
						// If additional sub-elements were added to the set after the attribute we're updating was created
						// those sub-elements will not have corresponding values returned by $o_attr->getValues() above.
						// Because we use the element_ids in those values to pull request parameters, if an element_id is missing
						// it effectively becomes invisible and cannot be set. This is a fairly unusual case but it happens, and when it does
						// it's really annoying. It would be nice and efficient to simply create the missing values at configuration time, but we wouldn't
						// know what to set the values to. So what we do is, after setting all of the values present in the attribute from the request, grab
						// the configuration for the element set and see if there are any elements in the set that we didn't get values for.
						//
						$va_sub_elements = $t_element->getElementsInSet($vn_element_set_id);
						$isset = false;
						foreach($va_sub_elements as $vn_i => $va_element_info) {
							if ($va_element_info['datatype'] == 0) { continue; }
							$vn_element_id = $va_element_info['element_id'];
							
							$vs_k = $vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_'.$vn_element_id.'_'.$vn_attribute_id;
							if(is_null($po_request->parameterExists($vs_k)) && !isset($_FILES[$vs_k])) { continue; }
							$vm_v = $po_request->getParameter($vs_k, pArray);
							$vs_attr_val = is_array($vm_v) ? array_pop($vm_v) : null;
							if (isset($_FILES[$vs_k]) && ($va_val = $_FILES[$vs_k])) {
								if ($va_val['size'] > 0) {	// is there actually a file?
									$va_val['_uploaded_file'] = true;
									$va_attr_update[$vn_element_id] = $va_val;
									continue;
								}
							} 
							$va_attr_update[$vn_element_id] = $vs_attr_val;
							$isset = true;
						}
						if (!$isset) { continue; }
						
						$this->clearErrors();
						if(!$this->editAttribute($vn_attribute_id, $vn_element_set_id, $va_attr_update, null, ['batch' => $vb_batch])) {
							$po_request->addActionErrors($this->errors);
						}
					}
				}
			}
		}
}
		
	if (is_array($va_fields_by_type['special'])) {
		foreach($va_fields_by_type['special'] as $vs_placement_code => $vs_bundle) {
			if ($vs_bundle !== 'hierarchy_location') { continue; }
			
			if ($vb_batch && ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_batch_mode', pString) !== '_replace_')) { continue; }
			
			$parent_tmp = explode("-", $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_new_parent_id", pString));
			$multiple_move_selection = array_filter(explode(";", $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_move_selection", pString)), 'is_numeric');
		
			// Hierarchy browser sets new_parent_id param to "X" if user wants to extract item from hierarchy
			$vn_parent_id = (($vn_parent_id = array_pop($parent_tmp)) == 'X') ? -1 : (int)$vn_parent_id;
			
			$target_table = $this->tableName();
			$parent_table = (sizeof($parent_tmp) > 0) ? array_pop($parent_tmp) : $target_table;
			
			
			if ($this->getPrimaryKey() && $this->HIERARCHY_PARENT_ID_FLD && ($vn_parent_id > 0)) {
			
				if ($parent_table == $target_table) {	
					// moving a record under another record in the same table
					if(sizeof($multiple_move_selection) > 0) {
						if($qr = $table::findAsSearchResult([$this->primaryKey() => ['IN', $multiple_move_selection]])) {
							if($qr->getPrimaryKey() ==  $this->getPrimaryKey()) { continue; }
							while($qr->nextHit()) {
								$t = $qr->getInstance();
								if(!$t->isSaveable($po_request)) { continue; }
								
								$t->set($this->HIERARCHY_PARENT_ID_FLD, $vn_parent_id); 
								if(!$t->update()) {
									$this->postError(2510, _t('Could not move item [%1]: %2', $t->getPrimaryKey(), join("; ", $this->getErrors())), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
									$po_request->addActionErrors($this->errors());
								}
							}
						}
					} else {
						$this->set($this->HIERARCHY_PARENT_ID_FLD, $vn_parent_id); 
					}
				} elseif(
					(bool)$this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') &&
					($parent_table == 'ca_collections') && ($target_table == 'ca_objects') &&
					($coll_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type'))
				) { 
					if(sizeof($multiple_move_selection) > 0) {
						$object_selection = array_map(
							function($v) { return (int)substr($v, 11); },
							array_filter($multiple_move_selection, function($v) { return preg_match("!^ca_objects\-!", $v); })
						);
						
						if($qr = ca_objects::findAsSearchResult(['object_id' => ['IN', $object_selection]])) {
							while($qr->nextHit()) {
								$t = $qr->getInstance();
								$t->removeRelationships('ca_collections', $coll_rel_type);
								$t->set($t->HIERARCHY_PARENT_ID_FLD, null);
								$t->set($t->HIERARCHY_ID_FLD, $t->getPrimaryKey());
								if(!$t->update()) {
									$this->postError(2510, _t('Could not move object under collection: %1', join("; ", $this->getErrors())), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
									$po_request->addActionErrors($this->errors());
								}
								if (!($t->addRelationship('ca_collections', $vn_parent_id, $coll_rel_type))) {
									$this->postError(2510, _t('Could not move object under collection: %1', join("; ", $this->getErrors())), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
									$po_request->addActionErrors($this->errors());
								}
							}
						}
					} else {
						// link object to collection
						$this->removeRelationships('ca_collections', $coll_rel_type);
						$this->set($this->HIERARCHY_PARENT_ID_FLD, null);
						$this->set($this->HIERARCHY_ID_FLD, $this->getPrimaryKey());
						if (!($this->addRelationship('ca_collections', $vn_parent_id, $coll_rel_type))) {
							$this->postError(2510, _t('Could not move object under collection: %1', join("; ", $this->getErrors())), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
							$po_request->addActionErrors($this->errors());
						}
					}
				}
			} else {
				// Move record to root of adhoc hierarchy (Eg. Take object out of hierarchy and make top-level)
				if (
					$this->getPrimaryKey() && 
					$this->HIERARCHY_PARENT_ID_FLD && 
					($this->HIERARCHY_TYPE == __CA_HIER_TYPE_ADHOC_MONO__) && 
					isset($_REQUEST["{$vs_placement_code}{$vs_form_prefix}_new_parent_id"]) && 
					($vn_parent_id <= 0)
				) {
					$this->set($this->HIERARCHY_PARENT_ID_FLD, null);
					$this->set($this->HIERARCHY_ID_FLD, $this->getPrimaryKey());
				
					// Support for collection-object cross-table hierarchies
					if ((bool)$this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && ($target_table == 'ca_objects') && ($vs_coll_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type')) && ($vn_parent_id == -1)) {	// -1 = extract from hierarchy
						$this->removeRelationships('ca_collections', $vs_coll_rel_type);
					}
				}
			}
			break;
		}
	}
		
		//
		// Call processBundlesBeforeBaseModelSave() method in sub-class, if it is defined. The method is passed
		// a list of bundles, the form prefix, the current request and the options passed to saveBundlesForScreen() –
		// everything needed to perform custom processing using the incoming form content that is being saved.
		// 
		// A processBundlesBeforeBaseModelSave() method is rarely needed, but can be handy when you need to do something model-specific
		// right before the basic database record is committed via insert() (for new records) or update() (for existing records).
		// For example, the media in ca_object_representations is set in a "special" bundle, which provides a specialized media upload UI. Unfortunately "special's" 
		// are handled after the basic database record is saved via insert() or update(), while the actual media must be set prior to the save.
		// processBundlesBeforeBaseModelSave() allows special logic in the ca_object_representations model to be invoked to set the media before the insert() or update().
		// The "special" takes care of other functions after the insert()/update()
		//
		if (method_exists($this, "processBundlesBeforeBaseModelSave")) {
			$this->processBundlesBeforeBaseModelSave($va_bundles, $vs_form_prefix, $po_request, $pa_options);
		}
			
		$vb_is_insert = false;
		
		if ($this->getPrimaryKey()) {
			$this->update(array('queueIndexing' => true));
		} else {
			$this->insert(array('queueIndexing' => true));
			$vb_is_insert = true;
		}
		if ($this->numErrors() > 0) {
			foreach($this->errors() as $o_e) {
				switch($o_e->getErrorNumber()) {
					case 2010:
						$po_request->addActionErrors(array($o_e), 'hierarchy_location');
						break;
					case 795:
						// field conflict
						foreach($this->getFieldConflicts() as $vs_conflict_field) {
							$po_request->addActionError($o_e, $vs_conflict_field);
						}
						break;
					case 1100:
						if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
							$po_request->addActionError($o_e, $this->getProperty('ID_NUMBERING_ID_FIELD'));
						}
						break;
					default:
						$va_errors[] = $o_e;
						break;
				}
			}
			
			$po_request->addActionErrors($va_errors);
			
			if ($vb_is_insert) {
			 	BaseModel::unsetChangeLogUnitID();
			 	if ($vb_we_set_transaction) {
					$this->removeTransaction(false);
				}
				return false;	// bail on insert error
			}
		}
		
		if (!$this->getPrimaryKey()) { 
			BaseModel::unsetChangeLogUnitID(); 
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			return false; 
		}	// bail if insert failed
		
		$this->clearErrors();
		
		//save reserved elements -  those with a saveElement method
		if (isset($reserved_elements) && is_array($reserved_elements)) {
			foreach($reserved_elements as $res_element) {
				$res_element_id = $res_element->getPrimaryKey();
				$res_element_datatype = $res_element->get('datatype');
				$res_datatype = Attribute::getValueInstance($res_element_datatype);
				$res_datatype->saveElement($this,$res_element,$vs_form_prefix,$po_request);
			}
		}

		// save preferred labels
if ($this->getProperty('LABEL_TABLE_NAME')) {
		$vb_check_for_dupe_labels = $this->_CONFIG->get('allow_duplicate_labels_for_'.$this->tableName()) ? false : true;
		$vb_error_inserting_pref_label = false;
		if (is_array($va_fields_by_type['preferred_label'])) {
		    $vs_label_must_be_in_list = $this->_CONFIG->get('preferred_label_for_'.$this->tableName().'_must_be_in_list');
		    
			foreach($va_fields_by_type['preferred_label'] as $vs_placement_code => $vs_f) {

if (!$vb_batch) {	
				// check for existing labels to update (or delete)
				$va_preferred_labels = $this->getPreferredLabels(null, false);
				foreach($va_preferred_labels as $vn_item_id => $va_labels_by_locale) {
					foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
						foreach($va_label_list as $va_label) {
							if ($vn_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_'.$va_label['label_id'], pString)) {
								$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'type_id_'.$va_label['label_id'], pInteger);
								if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, $va_label['label_id'], true))) {
									if ($vb_check_for_dupe_labels && $this->checkForDupeLabel($vn_label_locale_id, $va_label_values)) {
										$this->postError(1125, _t('Value <em>%1</em> is already used and duplicates are not allowed', join('', array_map(function($v) { return "<code>{$v}</code>"; }, array_filter($va_label_values, function($v) { return (bool)strlen($v); })))), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()", $this->tableName().'.preferred_labels');
										$po_request->addActionErrors($this->errors(), 'preferred_labels');
										continue;
									}
									if ($vs_label_must_be_in_list && !caGetListItemIDForLabel($vs_label_must_be_in_list, $vs_label_val = $va_label_values[$this->getLabelDisplayField()])) {
									    $this->postError(1100, _t('Value <em>%1</em> is not in <em>%2</em>', $vs_label_val, caGetListName($vs_label_must_be_in_list)), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()", $this->tableName().'.preferred_labels');
										$po_request->addActionErrors($this->errors(), 'preferred_labels');
										continue;
									}
									$effective_date = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'effective_date_'.$va_label['label_id'], pString);
									$label_access = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'access_'.$va_label['label_id'], pInteger);
									$source_info = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'source_info_'.$va_label['label_id'], pString);
							
									$this->editLabel($va_label['label_id'], $va_label_values, $vn_label_locale_id, $vn_label_type_id, true, ['queueIndexing' => true, 'effectiveDate' => $effective_date, 'access' => $label_access, 'sourceInfo' => $source_info]);
									if ($this->numErrors()) {
										foreach($this->errors() as $o_e) {
											switch($o_e->getErrorNumber()) {
												case 795:
													// field conflicts
													$po_request->addActionError($o_e, 'preferred_labels');
													break;
												default:
													$po_request->addActionError($o_e, $vs_f);
													break;
											}
										}
									}
								} else {
									$this->editLabel($va_label['label_id'],
										array($this->getLabelDisplayField() => '['.caGetBlankLabelText($table).']'),
										$vn_label_locale_id,
										$vn_label_type_id,
										true, array('queueIndexing' => true)
									);
								}
							} else {
								if ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_PrefLabel_'.$va_label['label_id'].'_delete', pString)) {
									// delete
									$this->removeLabel($va_label['label_id'], array('queueIndexing' => true));
								}
							}
						}
					}
				}
}				
				// check for new labels to add
				foreach($_REQUEST as $vs_key => $vs_value ) {
					if (!preg_match('/'.$vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
					
					if ($vb_batch) {
						$vs_batch_mode = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref_batch_mode', pString);
						switch($vs_batch_mode) {
							case '_disabled_':		// skip
								continue(2);
								break;
							case '_replace_':		// remove all existing preferred labels before trying to save
								$this->removeAllLabels(__CA_LABEL_TYPE_PREFERRED__);
								break;
							case '_delete_':		// remove all existing preferred labels
								$this->removeAllLabels(__CA_LABEL_TYPE_PREFERRED__);
								continue(2);
							case '_add_':
								break;
						}
					}
					$vn_c = intval($va_matches[1]);
					if ($vn_new_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_new_'.$vn_c, pString)) {
						
						if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, 'new_'.$vn_c, true))) {

							// make sure we don't add multiple pref labels for one locale in batch mode
							if($vb_batch && ($vs_batch_mode == '_add_')) {

								// first remove [BLANK] labels for this locale if there are any, as we are about to add a new one
								$va_labels_for_this_locale = $this->getPreferredLabels(array($vn_new_label_locale_id));
								if(is_array($va_labels_for_this_locale)) {
									foreach($va_labels_for_this_locale as $vn_id => $va_labels_by_locale) {
						 				foreach($va_labels_by_locale as $vn_locale_id => $va_labels) {
						 					foreach($va_labels as $vn_i => $va_label) {
						 						if(isset($va_label[$this->getLabelDisplayField()]) && ($va_label[$this->getLabelDisplayField()] == '['.caGetBlankLabelText($table).']')) {
						 							$this->removeLabel($va_label['label_id'], array('queueIndexing' => true));
						 						}
						 					}
						 				}
						 			}
								}

								// if there are non-[BLANK] labels for this locale, don't add this new one
								$va_labels_for_this_locale = $this->getPreferredLabels(array($vn_new_label_locale_id),true,array('forDisplay' => true));
								if(is_array($va_labels_for_this_locale) && (sizeof($va_labels_for_this_locale)>0)){
									$this->postError(1125, _t('A preferred label for this locale already exists. Only one preferred label per locale is allowed.'), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()", $this->tableName().'.preferred_labels');
									$po_request->addActionErrors($this->errors(), $vs_f);
									$vb_error_inserting_pref_label = true;
									continue;
								}
							}
							
							if ($vb_check_for_dupe_labels && $this->checkForDupeLabel($vn_new_label_locale_id, $va_label_values)) {
								$this->postError(1125, _t('Value <em>%1</em> is already used and duplicates are not allowed', join('', array_map(function($v) { return "<code>{$v}</code>"; }, array_filter($va_label_values, function($v) { return (bool)strlen($v); })))), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()", $this->tableName().'.preferred_labels');
								$po_request->addActionErrors($this->errors(), 'preferred_labels');
								$vb_error_inserting_pref_label = true;
								continue;
							}
							if ($vs_label_must_be_in_list && !caGetListItemIDForLabel($vs_label_must_be_in_list, $vs_label_val = $va_label_values[$this->getLabelDisplayField()])) {
                                $this->postError(1100, _t('Value <em>%1</em> is not in <em>%2</em>', $vs_label_val, caGetListName($vs_label_must_be_in_list)), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()", $this->tableName().'.preferred_labels');
                                $po_request->addActionErrors($this->errors(), 'preferred_labels');
                                $vb_error_inserting_pref_label = true;
                                continue;
                            }
                            
							$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'type_id_new_'.$vn_c, pInteger);
							$effective_date = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'effective_date_new_'.$vn_c, pString);
							$label_access = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'access_new_'.$vn_c, pInteger);
							$source_info = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'source_info_new_'.$vn_c, pString);
							
							$this->addLabel($va_label_values, $vn_new_label_locale_id, $vn_label_type_id, true, ['queueIndexing' => true, 'effectiveDate' => $effective_date, 'access' => $label_access, 'sourceInfo' => $source_info]);
							if ($this->numErrors()) {
								$po_request->addActionErrors($this->errors(), $vs_f);
								$vb_error_inserting_pref_label = true;
							}
						}
					}
				}
			}
		}
	}	
		// Add default label if needed (ie. if the user has failed to set at least one label or if they have deleted all existing labels)
		// This ensures at least one label is present for the record. If no labels are present then the 
		// record may not be found in queries
	if ($this->getProperty('LABEL_TABLE_NAME')) {
		if ($vb_error_inserting_pref_label || !$this->addDefaultLabel($vn_new_label_locale_id)) {
			if (!$vb_error_inserting_pref_label) { $po_request->addActionErrors($this->errors(), 'preferred_labels'); }
			
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			if ($vb_is_insert) { 
				$this->_FIELD_VALUES[$this->primaryKey()] = null; 											// clear primary key, which doesn't actually exist since we rolled back the transaction
				foreach($va_inserted_attributes_by_element as $vn_element_id => $va_failed_inserts) {		// set attributes as "failed" (but with no error messages) so they stay set
					$this->setFailedAttributeInserts($vn_element_id, $va_failed_inserts);
				}
			}
			return false;
		}
	}
		unset($va_inserted_attributes_by_element);

	
		// save non-preferred labels
		if ($this->getProperty('LABEL_TABLE_NAME') && isset($va_fields_by_type['nonpreferred_label']) && is_array($va_fields_by_type['nonpreferred_label'])) {
if (!$vb_batch) {	
			foreach($va_fields_by_type['nonpreferred_label'] as $vs_placement_code => $vs_f) {
				// check for existing labels to update (or delete)
				$va_nonpreferred_labels = $this->getNonPreferredLabels(null, false);
				foreach($va_nonpreferred_labels as $vn_item_id => $va_labels_by_locale) {
					foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
						foreach($va_label_list as $va_label) {
							if ($vn_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'locale_id_'.$va_label['label_id'], pString)) {
								$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'type_id_'.$va_label['label_id'], pInteger);
								if (is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, $va_label['label_id'], false))) {
									
									$effective_date = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'effective_date_'.$va_label['label_id'], pString);
									$label_access = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'access_'.$va_label['label_id'], pInteger);
									$source_info = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'source_info_'.$va_label['label_id'], pString);
							
									$this->editLabel($va_label['label_id'], $va_label_values, $vn_label_locale_id, $vn_label_type_id, false, ['queueIndexing' => true, 'effectiveDate' => $effective_date, 'access' => $label_access, 'sourceInfo' => $source_info]);
									if ($this->numErrors()) {
										foreach($this->errors() as $o_e) {
											switch($o_e->getErrorNumber()) {
												case 795:
													// field conflicts
													$po_request->addActionError($o_e, 'nonpreferred_labels');
													break;
												default:
													$po_request->addActionError($o_e, $vs_f);
													break;
											}
										}
									}
								} else {
									$this->editLabel($va_label['label_id'],
										array($this->getLabelDisplayField() => '['.caGetBlankLabelText($table).']'),
										$vn_label_locale_id,
										$vn_label_type_id,
										false, array('queueIndexing' => true)
									);
								}
							} else {
								if ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPrefLabel_'.$va_label['label_id'].'_delete', pString)) {
									// delete
									$this->removeLabel($va_label['label_id'], array('queueIndexing' => true));
								}
							}
						}
					}
				}
			}		
}		
			// check for new labels to add
			foreach($va_fields_by_type['nonpreferred_label'] as $vs_placement_code => $vs_f) {
				if ($vb_batch) {
					$vs_batch_mode = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref_batch_mode', pString);
			
					switch($vs_batch_mode) {
						case '_disabled_':		// skip
							continue(2);
							break;
						case '_add_':			// just try to add attribute as in normal non-batch save
							// noop
							break;
						case '_replace_':		// remove all existing nonpreferred labels before trying to save
							$this->removeAllLabels(__CA_LABEL_TYPE_NONPREFERRED__);
							break;
						case '_delete_':		// remove all existing nonpreferred labels
							$this->removeAllLabels(__CA_LABEL_TYPE_NONPREFERRED__);
							continue(2);
							break;
					}
				}
				
				foreach($_REQUEST as $vs_key => $vs_value ) {
					if (!preg_match('/^'.$vs_placement_code.$vs_form_prefix.'_NPref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
					$vn_c = intval($va_matches[1]);
					if ($vn_new_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'locale_id_new_'.$vn_c, pString)) {
						if (is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, 'new_'.$vn_c, false))) {
							$vn_new_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'type_id_new_'.$vn_c, pInteger);
							$effective_date = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'effective_date_new_'.$vn_c, pString);
							$label_access = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'access_new_'.$vn_c, pInteger);
							$source_info = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'source_info_new_'.$vn_c, pString);
							
							$this->addLabel($va_label_values, $vn_new_label_locale_id, $vn_new_label_type_id, false, ['queueIndexing' => true, 'effectiveDate' => $effective_date, 'access' => $label_access, 'sourceInfo' => $source_info]);
						
							if ($this->numErrors()) {
								$po_request->addActionErrors($this->errors(), $vs_f);
							}
						}
					}
				}
			}
		}
		
		
		// save data in related tables
		if (isset($va_fields_by_type['related_table']) && is_array($va_fields_by_type['related_table'])) {
			foreach($va_fields_by_type['related_table'] as $vs_placement_code => $vs_f) {
				// replace ca_objects_table or ca_objects_related_list w/ ca_objects
				if(preg_match("/^([a-z_]+)_(related_list|table)$/", $vs_f, $va_matches)) {
					$vs_f = $va_matches[1];
				}
				
				// get settings
				$va_bundle_settings = [];
				foreach($va_bundles as $va_bundle_info) {
					if ('P'.$va_bundle_info['placement_id'] == $vs_placement_code) {
						$va_bundle_settings = $va_bundle_info['settings'];
						break;
					}
				}
				
				switch($vs_f) {
					# -------------------------------------
					case 'ca_object_representations':
						// check for existing representations to update (or delete)
						$vs_prefix_stub = $vs_placement_code.$vs_form_prefix.'_';
						$vb_allow_fetching_of_urls = (bool)$this->_CONFIG->get('allow_fetching_of_media_from_remote_urls');
						$vb_allow_existing_rep = (bool)$this->_CONFIG->get('ca_objects_allow_relationships_to_existing_representations') && !(bool)caGetOption('dontAllowRelationshipsToExistingRepresentations', $va_bundle_settings, false);
						$dont_allow_access_to_import_directory = caGetOption('dontAllowAccessToImportDirectory', $va_bundle_settings, false);
	
						$import_directory_paths = caGetAvailableMediaUploadPaths($po_request->getUserID());
						
						$va_rep_ids_sorted = $va_rep_sort_order = explode(';',$po_request->getParameter($vs_prefix_stub.'ObjectRepresentationBundleList', pString));
						sort($va_rep_ids_sorted, SORT_NUMERIC);
						
						$va_reps = $this->getRepresentations();
						
						if (!$vb_batch && is_array($va_reps)) {
							if ($va_bundle_settings['uiStyle'] === 'CLASSIC') {
								$bundles_to_save = ['rep_label', 'idno', 'type_id', 'access', 'status', 'is_transcribable'];
							} else {
								$bundles_to_save = caGetOption('showBundlesForEditing', $va_bundle_settings, [], ['castTo' => 'array']);
							}
							if(!in_array('is_primary', $bundles_to_save)) { $bundles_to_save[] = 'is_primary'; }
							if(!in_array('rep_type_id', $bundles_to_save)) { $bundles_to_save[] = 'rep_type_id'; }
							
							if(in_array('ca_object_representations.preferred_labels.name', $bundles_to_save)) { 
								$bundles_to_save[] = 'rep_label'; 
							}
							$bundles_on_screen_proc = array_map(function($v) { return array_pop(explode('.', $v)); }, $bundles_to_save);
							foreach($va_reps as $vn_i => $va_rep) {
								$this->clearErrors();
								
								
								if(is_array($bundles_on_screen_proc) && sizeof($bundles_on_screen_proc)) {
									if ($vb_allow_fetching_of_urls && ($vs_path = $_REQUEST[$vs_prefix_stub.'media_url_'.$va_rep['relation_id']])) {
										$va_tmp = explode('/', $vs_path);
										$vs_original_name = array_pop($va_tmp);
									} else {
										$vs_path = $_FILES[$vs_prefix_stub.'media_'.$va_rep['relation_id']]['tmp_name'];
										$vs_original_name = $_FILES[$vs_prefix_stub.'media_'.$va_rep['relation_id']]['name'];
									}
									
									$vals = ['is_primary' => $va_rep['is_primary'], 'rep_type_id' => $va_rep['type_id']];
                                	if(is_array($bundles_on_screen_proc)) {
                                   		foreach($bundles_on_screen_proc as $b) {
                                   			$f = array_pop(explode('.', $b));
                                   			if (!is_null($po_request->parameterExists($vs_prefix_stub.$f.'_'.$va_rep['relation_id']))) {
                                   				$vals[$f] = $po_request->getParameter($vs_prefix_stub.$f.'_'.$va_rep['relation_id'], pString);
                                   			} 
                                   		}
                                	}
                                	
									// Get user-specified center point (images only)
									$vn_center_x = $po_request->getParameter($vs_prefix_stub.'center_x_'.$va_rep['relation_id'], pString);
									$vn_center_y = $po_request->getParameter($vs_prefix_stub.'center_y_'.$va_rep['relation_id'], pString);
									
									$vs_rep_label = $po_request->getParameter($vs_prefix_stub.'rep_label_'.$va_rep['relation_id'], pString);
									
									$vn_rank = null;
									if (($vn_rank_index = array_search($va_rep['relation_id'], $va_rep_sort_order)) !== false) {
										$vn_rank = $va_rep_ids_sorted[$vn_rank_index];
									}
									
									// import embedded metadata?
									$vn_object_representation_mapping_id = $po_request->getParameter($vs_prefix_stub.'importer_id_'.$va_rep['relation_id'], pInteger);
										
									$vn_rel_type_id = $po_request->getParameter($vs_prefix_stub.'rel_type_id_'.$va_rep['relation_id'], pString);
									$t_rep = $this->editRepresentation($va_rep['representation_id'], $vs_path, $vals['locale_id'], $vals['status'], $vals['access'], $vals['is_primary'], $vals, array('original_filename' => $vs_original_name, 'rank' => $vn_rank, 'centerX' => $vn_center_x, 'centerY' => $vn_center_y, 'type_id' => $vals['type_id'], 'rel_type_id' => $vn_rel_type_id, 'mapping_id' => $vn_object_representation_mapping_id));
									if ($this->numErrors()) {
										//$po_request->addActionErrors($this->errors(), $vs_f, $va_rep['relation_id']);
										foreach($this->errors() as $o_e) {
											switch($o_e->getErrorNumber()) {
												case 795:
													// field conflicts
													$po_request->addActionError($o_e, $vs_f, $va_rep['relation_id']);
													break;
												default:
													$po_request->addActionError($o_e, $vs_f, $va_rep['relation_id']);
													break;
											}
										}
									}
									
									if ($vs_rep_label) {
										//
										// Set representation label
										//
										$t_rep = new ca_object_representations();
										if ($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction()); }
										if ($t_rep->load($va_rep['representation_id'])) {
											$t_rep->replaceLabel(array('name' => $vs_rep_label), $g_ui_locale_id, null, true, array('queueIndexing' => true));
											if ($t_rep->numErrors()) {
												$po_request->addActionErrors($t_rep->errors(), $vs_f, $va_rep['relation_id']);
											}
										}
									}
								} 
								// is it a delete key?
								$this->clearErrors();
								if (($po_request->getParameter($vs_prefix_stub.$va_rep['relation_id'].'_delete', pInteger)) > 0) {
									// delete!
									$this->removeRepresentation($va_rep['representation_id']);
									if ($this->numErrors()) {
										$po_request->addActionErrors($this->errors(), $vs_f, $va_rep['relation_id']);
									}
								}
							}
						}

						if ($vb_batch) {
							$vs_batch_mode = $_REQUEST[$vs_prefix_stub.'batch_mode'];
	
							if ($vs_batch_mode == '_disabled_') { break;}
							if (($vs_batch_mode == '_replace_') && sizeof($va_reps)) { 
							    foreach($va_reps as $va_rep) {
									$pn_access = $po_request->getParameter("{$vs_prefix_stub}access_new_0_enabled", pInteger) ? $po_request->getParameter("{$vs_prefix_stub}access_new_0", pInteger) : null;
									$pn_status = $po_request->getParameter("{$vs_prefix_stub}status_new_0_enabled", pInteger) ? $po_request->getParameter("{$vs_prefix_stub}status_new_0", pInteger) : null;
									$pn_type_id = $po_request->getParameter("{$vs_prefix_stub}rep_type_id_new_0_enabled", pInteger) ? $po_request->getParameter("{$vs_prefix_stub}rep_type_id_new_0", pInteger) : null;
									$ps_rep_label = $po_request->getParameter("{$vs_prefix_stub}rep_label_new_0_enabled", pInteger) ? trim($po_request->getParameter("{$vs_prefix_stub}rep_label_new_0", pString)) : null;
									$pn_is_transcribable = $po_request->getParameter("{$vs_prefix_stub}is_transcribable_new_0_enabled", pInteger);
									$this->editRepresentation($va_rep['representation_id'], null, $g_ui_locale_id, $pn_status, $pn_access, null, ['is_transcribable' => $pn_is_transcribable], ['label' => $ps_rep_label, 'type_id' => $pn_type_id]);
							    }
							}
							if ($vs_batch_mode == '_delete_') { $this->removeAllRepresentations(); break; }
						} else {
					
                            // check for new representations to add 
                            
							$file_index = 0;
                            $va_file_list = array_map(function($v) { $v['is_form_upload'] = 1; return $v; }, $_FILES);
                            foreach($_REQUEST as $vs_key => $vs_value) {
                                if (preg_match('/^'.$vs_prefix_stub.'media_url_new_([\d]+)$/', $vs_key, $va_matches)) {
                                	if(!isURL($vs_value, ['strict' => true, 'schemes' => ['http', 'https']])) { continue; }
                                    $va_file_list[$vs_key] = array(
                                        'url' => $vs_value,
                                        'index' => (int)$va_matches[1]
                                        
                                    );
                                } elseif(preg_match('/^'.$vs_prefix_stub.'media_new_([\d]+)$/', $vs_key, $va_matches)) {
                                    $va_file_list[$vs_key] = array(
                                        'tmp_name' => escapeshellcmd($vs_value),
                                        'name' => $vs_value,
                                        'index' => (int)$va_matches[1]
                                    );
                                } elseif(preg_match('/^'.$vs_prefix_stub.'autocompletenew_([\d]+)$/', $vs_key, $va_matches)){
									$va_file_list[$vs_key] = array(
										'tmp_name' => escapeshellcmd($vs_value),
										'name' => $vs_value,
                                        'index' => (int)$va_matches[1]
									);
								} elseif(preg_match('/^'.$vs_prefix_stub.'mediarefsnew_([\d]+)$/', $vs_key, $va_matches)) {
									$files = explode(";", $vs_value);
									foreach($files as $f) {
										if(!$f) { continue; }
										$va_file_list["{$vs_key}_{$file_index}"] = [		// Add numeric suffix to allow for multiple uploads in a single request
											'tmp_name' => escapeshellcmd($f),
											'name' => $f,
                                        	'index' => (int)$va_matches[1]
										];
										$file_index++;
									}
								} elseif($po_request->getAppConfig()->get('allow_representations_without_media') && preg_match('/^'.$vs_prefix_stub.'no_media_new_([\d]+)$/', $vs_key, $va_matches)) {
									$index = (int)$va_matches[1];
									$va_file_list['empty'] = ['index' => $index];
								}
                            }
                            
                            if(isset($va_file_list['empty'])) {
                            	$index = $va_file_list['empty']['index'];
								$va_file_list = array_filter($va_file_list, function($v) use ($index) { return (($v['index'] !== $index) || (sizeof($v) === 1)); });
                            }
                            
                            // Sort files such that they are inserted in file name order
                        	$va_file_list = caSortArrayByKeyInValue($va_file_list, ['tmp_name'], 'ASC', ['mode' => SORT_NATURAL]);
                            
                            foreach($va_file_list as $vs_key => $va_values) {
                            	$is_form_upload = caGetOption('is_form_upload', $va_values, false, ['castTo' => 'boolean']);
                            	$va_values['tmp_name'] = stripslashes($va_values['tmp_name']);
                                $this->clearErrors();
                            
								if (
									($vs_key !== 'empty') &&
									!preg_match('/^'.$vs_prefix_stub.'media_new_([\d]+)$/', $vs_key, $va_matches) && 
									!preg_match('/^'.$vs_prefix_stub.'mediarefsnew_([\d]+)_([\d]+)$/', $vs_key, $va_matches) && 
									(($vb_allow_fetching_of_urls && !preg_match('/^'.$vs_prefix_stub.'media_url_new_([\d]+)$/', $vs_key, $va_matches)) || !$vb_allow_fetching_of_urls) && 
									(($vb_allow_existing_rep && !preg_match('/^'.$vs_prefix_stub.'autocompletenew_([\d]+)$/', $vs_key, $va_matches))||!$vb_allow_existing_rep) ) {  
										continue; 
								} elseif($vs_key === 'empty') {
									$va_matches = [null, $va_values['index']];
								}
								
                                if($vs_upload_type = $po_request->getParameter($vs_prefix_stub.'upload_typenew_'.$va_matches[1], pString)) {
                                    $po_request->user->setVar('defaultRepresentationUploadType', $vs_upload_type);
                                }
                            
								if($vs_upload_type === "search") {
									$vn_type_id = $po_request->getParameter($vs_prefix_stub.'rep_type_id_new_'.$va_matches[1], pInteger);
								} else {
									$vn_type_id = $po_request->getParameter($vs_prefix_stub.'rel_type_id_new_'.$va_matches[1], pInteger);
								}
								
								if ($vn_existing_rep_id = $po_request->getParameter($vs_prefix_stub.'idnew_'.$va_matches[1], pInteger)) {
                                    $this->addRelationship('ca_object_representations', $vn_existing_rep_id, $vn_type_id);
                                } else {
                                    if ($vb_allow_fetching_of_urls && ($vs_path = $va_values['url'])) {
                                    	// Is remote URL
                                        $va_tmp = explode('/', $vs_path);
                                        $vs_original_name = array_pop($va_tmp);
                                    } elseif(preg_match("!^".($u = caGetUserDirectoryName($po_request->getUserID()))."/(.*)$!", $va_values['tmp_name'], $m)) {
                                    	foreach($import_directory_paths as $p) {
                                    		if(file_exists($vs_path = "{$p}/{$m[1]}")) {
                                    			$vs_original_name = pathinfo($va_values['tmp_name'], PATHINFO_BASENAME);
                                    			break;
                                    		}
                                    	}
                                    } elseif(!$is_form_upload && !$dont_allow_access_to_import_directory && ($vs_key !== 'empty') && sizeof($import_directory_paths) && strlen($va_values['tmp_name'])) {
                                    	// Is user-selected file from s media import directory
                                    	foreach($import_directory_paths as $p) {
											if(file_exists($vs_path = "{$p}/{$va_values['tmp_name']}")) {
												$vs_original_name = pathinfo($va_values['name'], PATHINFO_BASENAME);
												break;
											}
										}
                                    } elseif(isset($va_values['tmp_name']) && $is_form_upload && file_exists($va_values['tmp_name'])) {
                                    	$vs_path = $va_values['tmp_name'];
                                        $vs_original_name = $va_values['name'];
                                    } else {
                                    	// Path is not valid
                                    	$vs_path = $vs_original_name = null;
                                    }
                                    
                                    if (($vs_key !== 'empty') && !$vs_path) { continue; }
                            
                                    $vn_rep_type_id = $po_request->getParameter([$vs_prefix_stub.'rep_type_id_new_'.$va_matches[1], $vs_prefix_stub.'type_id_new_'.$va_matches[1]], pInteger);
                                    if(!$vn_rep_type_id && !($vn_rep_type_id = caGetDefaultItemID('object_representation_types'))) {
                                        $t_list = new ca_lists();
                                        if (is_array($va_rep_type_ids = $t_list->getItemsForList('object_representation_types', array('idsOnly' => true, 'enabledOnly' => true)))) {
                                            $vn_rep_type_id = array_shift($va_rep_type_ids);
                                        }
                                    }
                        
                                    if (is_array($pa_options['existingRepresentationMap']) && isset($pa_options['existingRepresentationMap'][$vs_path]) && $pa_options['existingRepresentationMap'][$vs_path]) {
                                        $this->addRelationship('ca_object_representations', $pa_options['existingRepresentationMap'][$vs_path], $vn_type_id);
                                        break;
                                    }
                                    
                                	$vals = [];
                                	if(is_array($bundles_on_screen_proc)) {
                                   		foreach($bundles_on_screen_proc as $b) {
                                   			$f = array_pop(explode('.', $b));
                                   			if ($v = $po_request->getParameter($vs_prefix_stub.$f.'_new_'.$va_matches[1], pString)) {
                                   				$vals[$f] = $v;
                                   			} 
                                   		}
                                	}
                                	
									// import embedded metadata?
									$vn_object_representation_mapping_id = $po_request->getParameter($vs_prefix_stub.'importer_id_new_'.$va_matches[1], pInteger);
                                	
                                    // Get user-specified center point (images only)
                                    $vn_center_x = $po_request->getParameter($vs_prefix_stub.'center_x_new_'.$va_matches[1], pString);
                                    $vn_center_y = $po_request->getParameter($vs_prefix_stub.'center_y_new_'.$va_matches[1], pString);
                        			
                        			if ($vs_path = trim($vs_path)) {
                        				
										$files = is_dir($vs_path) ? caGetDirectoryContentsAsList($vs_path) : [$vs_path];
										foreach($files as $f) {
											// Final check of path before import attempt
											if (
												!$is_form_upload
												&&
												((($is_url = isUrl($f)) && !$vb_allow_fetching_of_urls)
												||
												(!$is_url && !preg_match("!^{$ajax_import_directory_path}!", $f) && !preg_match("!^{$import_directory_path}!", $f)))
											) {
												continue;
											}
											if ($t_rep = $this->addRepresentation($f, $vn_rep_type_id, $vals['locale_id'], $vals['status'], $vals['access'], $vn_is_primary, array_merge($vals, ['name' => $vals['rep_label']]), ['original_filename' => $vs_original_name, 'returnRepresentation' => true, 'centerX' => $vn_center_x, 'centerY' => $vn_center_y, 'type_id' => $vn_type_id, 'mapping_id' => $vn_object_representation_mapping_id])) {	// $vn_type_id = *relationship* type_id (as opposed to representation type)
												@unlink($f);
											}
										}
									} elseif($vs_key === 'empty') {
										$t_rep = $this->addRepresentation(null, $vn_rep_type_id, $vals['locale_id'], $vals['status'], $vals['access'], $vn_is_primary, array_merge($vals, ['name' => $vals['rep_label']]), array('original_filename' => $vs_original_name, 'returnRepresentation' => true, 'centerX' => $vn_center_x, 'centerY' => $vn_center_y, 'type_id' => $vn_type_id, 'mapping_id' => $vn_object_representation_mapping_id));	// $vn_type_id = *relationship* type_id (as opposed to representation type)
									}
                                    
                                    if ($this->numErrors()) {
                                        $po_request->addActionErrors($this->errors(), $vs_f, 'new_'.$va_matches[1]);
                                    } else {
                                        if ($t_rep && is_array($pa_options['existingRepresentationMap'])) { 
                                            $pa_options['existingRepresentationMap'][$vs_path] = $t_rep->getPrimaryKey();
                                        }
                                    }
                                }
                            }
						}
						break;
					# -------------------------------------
					case 'ca_entities':
					case 'ca_places':
					case 'ca_objects':
					case 'ca_collections':
					case 'ca_occurrences':
					case 'ca_list_items':
					case 'ca_object_lots':
					case 'ca_storage_locations':
					case 'ca_loans':
					case 'ca_movements':
					case 'ca_tour_stops':
						$this->_processRelated($po_request, $vs_f, $vs_form_prefix, $vs_placement_code, array('batch' => $vb_batch, 'settings' => $va_bundle_settings));
						break;
					# -------------------------------------
					case 'ca_sets':
						$this->_processRelatedSets($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
					case 'ca_representation_annotations':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->_processRepresentationAnnotations($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
				}
			}	
		}
		
		
		// save data for "specials"

		if (isset($va_fields_by_type['special']) && is_array($va_fields_by_type['special'])) {
			foreach($va_fields_by_type['special'] as $vs_placement_code => $vs_f) {
				
				// get settings
				$va_bundle_settings = [];
				foreach($va_bundles as $va_bundle_info) {
					if ('P'.$va_bundle_info['placement_id'] == $vs_placement_code) {
						$va_bundle_settings = $va_bundle_info['settings'];
						break;
					}
				}
			
				switch($vs_f) {
					# -------------------------------------
					// This bundle is only available when editing objects of type ca_representation_annotations
					case 'ca_representation_annotation_properties':
						if ($vb_batch) { break; } // not supported in batch mode
						if (!$this->useInEditor()) { break; }
						foreach($this->getPropertyList() as $vs_property) {
							$this->setPropertyValue($vs_property, $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}{$vs_property}", pString));
						}
						if (!$this->validatePropertyValues()) {
							$po_request->addActionErrors($this->errors(), 'ca_representation_annotation_properties', 'general');
						}
						$this->update();
						break;
					# -------------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_sets_checklist':
						// check for existing labels to delete (no updating supported)
						require_once(__CA_MODELS_DIR__.'/ca_sets.php');
						require_once(__CA_MODELS_DIR__.'/ca_set_items.php');
	
						$t_set = new ca_sets();
if (!$vb_batch) {
						$va_sets = caExtractValuesByUserLocale($t_set->getSetsForItem($this->tableNum(), $this->getPrimaryKey(), array('user_id' => $po_request->getUserID()))); 

						foreach($va_sets as $vn_set_id => $va_set_info) {
							$vn_item_id = $va_set_info['item_id'];
							
							if ($po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_set_id_{$vn_item_id}_delete", pString)) {
								// delete
								$t_set->load($va_set_info['set_id']);
								$t_set->removeItem($this->getPrimaryKey(), $po_request->getUserID());	// remove *all* instances of the item in the set, not just the specified id
								if ($t_set->numErrors()) {
									$po_request->addActionErrors($t_set->errors(), $vs_f);
								}
							}
						}
}
						if ($vb_batch) {
							$vs_batch_mode = $_REQUEST["{$vs_placement_code}{$vs_form_prefix}_batch_mode"];
	
							if ($vs_batch_mode == '_disabled_') { break;}
							if ($vs_batch_mode == '_replace_') { $t_set->removeItemFromAllSets($this->tableNum(), $this->getPrimaryKey());}
							if ($vs_batch_mode == '_delete_') { $t_set->removeItemFromAllSets($this->tableNum(), $this->getPrimaryKey()); break; }
						}	
										
						foreach($_REQUEST as $vs_key => $vs_value) {
							if (!preg_match("/{$vs_placement_code}{$vs_form_prefix}_set_id_new_([\d]+)/", $vs_key, $va_matches)) { continue; }
							$vn_c = intval($va_matches[1]);
							if ($vn_new_set_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_set_id_new_{$vn_c}", pString)) {
								$t_set->load($vn_new_set_id);
								$t_set->addItem($this->getPrimaryKey(), null, $po_request->getUserID());
								if ($t_set->numErrors()) {
									$po_request->addActionErrors($t_set->errors(), $vs_f);
								}
							}
						}
						break;
					# -------------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_set_items':
						if ($vb_batch) { break; } // not supported in batch mode
						// check for existing labels to delete (no updating supported)
						require_once(__CA_MODELS_DIR__.'/ca_sets.php');
						require_once(__CA_MODELS_DIR__.'/ca_set_items.php');
						
						$va_rids = explode(';', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}setRowIDList", pString));
						
						$this->reorderItems($va_rids, array('user_id' => $po_request->getUserID(), 'treatRowIDsAsRIDs' => true, 'deleteExcludedItems' => true));
						break;
					# -------------------------------------
					// This bundle is only available for ca_search_forms 
					case 'ca_search_form_elements':
						if ($vb_batch) { break; } // not supported in batch mode
						// save settings
						$va_settings = $this->getAvailableSettings();
						foreach($va_settings as $vs_setting => $va_setting_info) {
							if(isset($_REQUEST['setting_'.$vs_setting])) {
								$vs_setting_val = $po_request->getParameter('setting_'.$vs_setting, pString);
								$this->setSetting($vs_setting, $vs_setting_val);
								$this->update();
							}
						}
						break;
					# -------------------------------------
					// This bundle is only available for ca_bundle_displays 
					case 'ca_bundle_display_placements':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->savePlacementsFromHTMLForm($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
					// This bundle is only available for ca_bundle_displays 
					case 'ca_bundle_display_type_restrictions':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->saveTypeRestrictionsFromHTMLForm($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
					// This bundle is only available for ca_bundle_displays
					case 'ca_metadata_alert_rule_type_restrictions':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->saveTypeRestrictionsFromHTMLForm($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
					// This bundle is only available for ca_search_forms 
					case 'ca_search_form_placements':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->savePlacementsFromHTMLForm($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
					// This bundle is only available for ca_editor_ui
					case 'ca_editor_ui_screens':
						if ($vb_batch) { break; } // not supported in batch mode
						global $g_ui_locale_id;
						require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
						$va_screen_ids = explode(';', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ScreenBundleList", pString));
					
						
						foreach($_REQUEST as $vs_key => $vs_val) {
							if (is_array($vs_val)) { continue; }
							if (!($vs_val = trim($vs_val))) { continue; }
							
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_name_new_([\d]+)$!", $vs_key, $va_matches)) {
								if (!($t_screen = $this->addScreen($vs_val, $g_ui_locale_id, 'screen_'.$this->getPrimaryKey().'_'.$va_matches[1]))) { break; }
								
								if ($vn_fkey = array_search("new_".$va_matches[1], $va_screen_ids)) {
									$va_screen_ids[$vn_fkey] = $t_screen->getPrimaryKey();
								} else {
									$va_screen_ids[] = $t_screen->getPrimaryKey();
								}
								continue;
							}
							
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_([\d]+)_delete$!", $vs_key, $va_matches)) {
								$this->removeScreen($va_matches[1]);
								if ($vn_fkey = array_search($va_matches[1], $va_screen_ids)) { unset($va_screen_ids[$vn_fkey]); }
							}
						}
						$this->reorderScreens($va_screen_ids);
						break;
					# -------------------------------------
					// This bundle is only available for ca_editor_ui_screens
					case 'ca_editor_ui_bundle_placements':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->savePlacementsFromHTMLForm($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
					case 'ca_editor_ui_type_restrictions':
					case 'ca_bundle_display_type_restrictions':
					case 'ca_editor_ui_screen_type_restrictions':
					case 'ca_search_form_type_restrictions':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->saveTypeRestrictionsFromHTMLForm($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------------
					// This bundle is only available for ca_tours
					case 'ca_tour_stops_list':
						if ($vb_batch) { break; } // not supported in batch mode
						global $g_ui_locale_id;
						require_once(__CA_MODELS_DIR__.'/ca_tour_stops.php');
						$va_stop_ids = explode(';', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_StopBundleList", pString));
					
						foreach($_REQUEST as $vs_key => $vs_val) {
							if (!($vs_val = trim($vs_val))) { continue; }
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_name_new_([\d]+)$!", $vs_key, $va_matches)) {
								$vn_type_id = $_REQUEST["{$vs_placement_code}{$vs_form_prefix}_type_id_new_".$va_matches[1]];
								if (!($t_stop = $this->addStop($vs_val, $vn_type_id, $g_ui_locale_id, mb_substr(preg_replace('![^A-Za-z0-9_]+!', '_', $vs_val),0, 255)))) { break; }
								
								if ($vn_fkey = array_search("new_".$va_matches[1], $va_stop_ids)) {
									$va_stop_ids[$vn_fkey] = $t_stop->getPrimaryKey();
								} else {
									$va_stop_ids[] = $t_stop->getPrimaryKey();
								}
								continue;
							}
							
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_([\d]+)_delete$!", $vs_key, $va_matches)) {
								$this->removeStop($va_matches[1]);
								if ($vn_fkey = array_search($va_matches[1], $va_stop_ids)) { unset($va_stop_ids[$vn_fkey]); }
							}
						}
						$this->reorderStops($va_stop_ids);
						break;
					# -------------------------------------
					case 'ca_user_groups':
						if ($vb_batch) { break; } // not supported in batch mode
						if (!$po_request->user->canDoAction('is_administrator') && ($po_request->getUserID() != $this->get('user_id'))) { break; }	// don't save if user is not owner
						require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');
	
						$va_groups_to_set = $va_group_effective_dates = [];
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_id(.*)$!", $vs_key, $va_matches)) {
								$vs_effective_date = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_effective_date_".$va_matches[1], pString);
								$vn_group_id = (int)$po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_id".$va_matches[1], pInteger);
								$vn_access = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_access_".$va_matches[1], pInteger);
								if ($vn_access >= 0) {
									$va_groups_to_set[$vn_group_id] = $vn_access;
									$va_group_effective_dates[$vn_group_id] = $vs_effective_date;
								}
							}
						}
												
						$this->setUserGroups($va_groups_to_set, $va_group_effective_dates, array('user_id' => $po_request->getUserID()));
						
						break;
					# -------------------------------------
					case 'ca_users':
						if ($vb_batch) { break; } // not supported in batch mode
						if (!$po_request->user->canDoAction('is_administrator') && ($po_request->getUserID() != $this->get('user_id'))) { break; }	// don't save if user is not owner
						require_once(__CA_MODELS_DIR__.'/ca_users.php');
	
						$va_users_to_set = $va_user_effective_dates = [];
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_id(.*)$!", $vs_key, $va_matches)) {
								$vs_effective_date = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_effective_date_".$va_matches[1], pString);
								$vn_user_id = (int)$po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_id".$va_matches[1], pInteger);
								$vn_access = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_access_".$va_matches[1], pInteger);
								if ($vn_access > 0) {
									$va_users_to_set[$vn_user_id] = $vn_access;
									$va_user_effective_dates[$vn_user_id] = $vs_effective_date;
								}
							}
						}
						
						$this->setUsers($va_users_to_set, $va_user_effective_dates);
						
						break;
					# -------------------------------------
					case 'ca_user_roles':
						if ($vb_batch) { break; } // not supported in batch mode
						if (!$po_request->user->canDoAction('is_administrator') && ($po_request->getUserID() != $this->get('user_id'))) { break; }	// don't save if user is not owner
						require_once(__CA_MODELS_DIR__.'/ca_user_roles.php');
	
						$va_roles_to_set = $va_roles_to_remove = [];
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_access_([\d]+)$!", $vs_key, $va_matches)) {
								$vn_role_id = $va_matches[1];
								$vn_access = $po_request->getParameter($vs_key, pInteger);
								if ($vn_access > 0) {
									$va_roles_to_set[$vn_role_id] = $vn_access;
								} else {
									$va_roles_to_remove[$vn_role_id] = true;
								}
							}
						}
						
						$this->removeUserRoles(array_keys($va_roles_to_remove));
						$this->setUserRoles($va_roles_to_set, array('user_id' => $po_request->getUserID()));
						
						break;
					# -------------------------------------
					case 'settings':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->setSettingsFromHTMLForm($po_request, array('id' => $vs_form_prefix.'_', 'placement_code' => $vs_placement_code));
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representations_media_display':
						if ($vb_batch) { break; } // not supported in batch mode
						$va_versions_to_process = null;
						
						if ($vb_use_options = (bool)$po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_derivative_options_selector", pInteger)) {
							// update only specified versions
							$va_versions_to_process =  $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_set_versions", pArray);
						} 
					
						if (!is_array($va_versions_to_process) || !sizeof($va_versions_to_process)) {
							$va_versions_to_process = array('_all');	
						}
						
						if ($vb_use_options && ($po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_derivative_options_mode", pString) == 'timecode')) {
							// timecode
							if (!(string)($vs_timecode = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_derivative_options_mode_timecode_value", pString))) {
								$vs_timecode = "1s";
							}
							//
							$o_media = new Media();
							if ($o_media->read($this->getMediaPath('media', 'original'))) {
								$va_files = $o_media->writePreviews(array('force' => true, 'outputDirectory' => $this->_CONFIG->get("taskqueue_tmp_directory"), 'minNumberOfFrames' => 1, 'maxNumberOfFrames' => 1, 'startAtTime' => $vs_timecode, 'endAtTime' => $vs_timecode, 'width' => 720, 'height' => 540));
						
								if(sizeof($va_files)) { 
									$this->set('media', array_shift($va_files));
								}
							}
							
						} else {
							if ($vb_use_options && ($po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_derivative_options_mode", pString) == 'page')) {
								if (!(int)($vn_page = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_derivative_options_mode_page_value", pInteger))) {
									$vn_page = 1;
								}
								//
								$o_media = new Media();
								if ($o_media->read($this->getMediaPath('media', 'original'))) {
									$va_files = $o_media->writePreviews(array('force' => true, 'outputDirectory' => $this->_CONFIG->get("taskqueue_tmp_directory"), 'numberOfPages' => 1, 'startAtPage' => $vn_page, 'width' => 2048, 'height' => 2048));
							
									if(is_array($va_files) && sizeof($va_files)) { 
										$this->set('media', array_shift($va_files));
									}
								}
							} else {
								// process file as new upload
								$vs_key = "{$vs_placement_code}{$vs_form_prefix}_url";
								if (($vs_media_url = trim($po_request->getParameter($vs_key, pString))) && isURL($vs_media_url)) {
									$this->set('media', $vs_media_url);
								} else {
									$vs_key = "{$vs_placement_code}{$vs_form_prefix}_media";
									if (isset($_FILES[$vs_key])) {
										$this->set('media', $_FILES[$vs_key]['tmp_name'], array('original_filename' => $_FILES[$vs_key]['name']));
									}
								}
							}
						}
						
						if ($this->changed('media')) {
							try {
								$this->update(($vs_version != '_all') ? array('updateOnlyMediaVersions' => $va_versions_to_process, 'startAtTime' => $vs_timecode, 'startAtPage' => $vn_page) : []);
								if ($this->numErrors()) {
									$po_request->addActionErrors($this->errors(), 'ca_object_representations_media_display', 'general');
								}
							} catch(MediaExistsException $e) {
								$this->postError(2730, caGetReferenceToExistingRepresentationMedia($e->getRepresentation()), 'ca_object_representations->insert()');
								$po_request->addActionErrors($this->errors(), $vs_f, 'general');
								return false;
							}
						}
						
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representation_captions':
						if ($vb_batch) { return null; } // not supported in batch mode
				
						foreach($_REQUEST as $vs_key => $vs_val) { 
							// any to delete?
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_([\d]+)_delete$!", $vs_key, $va_matches)) {
								$this->removeCaptionFile((int)$va_matches[1]);
							}
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_locale_id(.*)$!", $vs_key, $va_matches)) {
								$vn_locale_id = (int)$po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_locale_id".$va_matches[1], pInteger);
								$this->addCaptionFile($_FILES["{$vs_placement_code}{$vs_form_prefix}_caption_file".$va_matches[1]]['tmp_name'], $vn_locale_id, array('originalFilename' => $_FILES["{$vs_placement_code}{$vs_form_prefix}_caption_file".$va_matches[1]]['name']));
							} 
						}
						
						break;
						
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representation_sidecars':
						if ($vb_batch) { return null; } // not supported in batch mode
			
						foreach($_REQUEST as $vs_key => $vs_val) { 
							// any to delete?
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_([\d]+)_delete$!", $vs_key, $va_matches)) {
								$this->removeSidecarFile((int)$va_matches[1]);
							}
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_id(.*)$!", $vs_key, $va_matches)) {
								$this->addSidecarFile(
									$_FILES["{$vs_placement_code}{$vs_form_prefix}_sidecar_file".$va_matches[1]]['tmp_name'], 			
									$_REQUEST["{$vs_placement_code}{$vs_form_prefix}_notes".$va_matches[1]], 
									['originalFilename' => $_FILES["{$vs_placement_code}{$vs_form_prefix}_sidecar_file".$va_matches[1]]['name']]
								);
							} 
						}
						
						break;
					# -------------------------------
					// This bundle is only available for relationships that include an object on one end
					case 'ca_object_representation_chooser':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!is_array($va_rep_ids = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}", pArray))) { $va_rep_ids = []; }
								
						if ($vs_element_code = caGetOption(array('elementCode', 'element_code'), $va_bundle_settings, null)) {
							if (!is_array($va_current_rep_ids = $this->get($this->tableName().".".$vs_element_code, array('returnAsArray' => true, 'idsOnly' => true)))) { 
								$va_current_rep_ids = $va_current_rep_id_with_structure = [];
							} else {
								$va_current_rep_id_with_structure = $this->get($this->tableName().".".$vs_element_code, array('returnWithStructure' => true, 'idsOnly' => true));
							}
							
							$va_rep_to_attr_id = [];
							
							foreach($va_rep_ids as $vn_rep_id) {
								if (in_array($vn_rep_id, $va_current_rep_ids)) { continue; }
								$this->addAttribute(array($vs_element_code => $vn_rep_id), $vs_element_code);
							}
							
							foreach($va_current_rep_id_with_structure as $vn_id => $va_vals_by_attr_id) {
								foreach($va_vals_by_attr_id as $vn_attribute_id => $va_val) {
									if (!in_array($va_val[$vs_element_code], $va_rep_ids)) {
										$this->removeAttribute($vn_attribute_id);
									}
								}
							}
						
							$this->update();
						}
						break;
					# -------------------------------
					case 'ca_objects_history':
					case 'ca_objects_location':
					case 'history_tracking_chronology':
						//if ($vb_batch) { return null; } // not supported in batch mode
						if (!$po_request->user->canDoAction('can_edit_ca_objects')) { break; }
								
					    if (strlen($pb_show_child_history = $po_request->getParameter("showChildHistory", pInteger))) {
					        Session::setVar("{$vs_f}_showChildHistory", (bool)$pb_show_child_history);
					    }
					    
					    $policy = caGetOption('policy', $va_bundle_settings, $this::getDefaultHistoryTrackingCurrentValuePolicyForTable());
					    
					    $change_has_been_made = false;
					    
						$processed_bundle_settings = $this->_processHistoryBundleSettings($va_bundle_settings);
					    
					    if (!caGetOption('hide_value_delete', $va_bundle_settings, false)) {
							// handle deletes
							$refs = $this->getHistoryReferences(['policy' => $policy]);	// get all references present in this history
							foreach($refs as $t => $r) {
								if (is_array($rp = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_delete_{$t}", pArray))) {
									if (is_array($rows_to_delete = array_intersect($r, $rp))) {	// only attempt to delete values passed that are actually in the history
										if (!($t_instance = Datamodel::getInstance($t, true))) { continue; }
										if ($this->inTransaction()) { $t_instance->setTransaction($this->getTransaction()); }
										foreach($rows_to_delete as $row_id) {
											if ($t_instance->isRelationship() && $t_instance->load($row_id)) {
												if(!$t_instance->delete()) {	// TODO: check error handling
													$po_request->addActionErrors($t_instance->errors(), $vs_f, 'general');
												}
											} elseif(($t === 'ca_object_lots') && ($this->tableName() === 'ca_objects')) {
												$this->set('lot_id', null);
												if (!$this->update()) {	// TODO: check error handling
													$po_request->addActionErrors($t_instance->errors(), $vs_f, 'general');
												}
											} elseif(($t === 'ca_objects') && ($this->tableName() === 'ca_objects') && ($this->get('is_deaccessioned'))) {
												$this->set('is_deaccessioned', 0);
												if (!$this->update()) {	// TODO: check error handling
													$po_request->addActionErrors($t_instance->errors(), $vs_f, 'general');
												}
											}
											$change_has_been_made = true;
											SearchResult::clearResultCacheForRow($t, $row_id);
										}
									}
								}
							}
						}
					    
					    if (!caGetOption('hide_update_location_controls', $va_bundle_settings, false) || !caGetOption('hide_return_to_home_location_controls', $va_bundle_settings, false)) {
							// set storage location
							if ($this->hasField('home_location_id') && $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_storage_locations_return_homenew_0", pInteger) == 1) {
								$vn_location_id = $this->get('home_location_id');
							} else {
								$vn_location_id = $vn_location_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_storage_locations_idnew_0", pInteger);
							}
							
							if ($vn_location_id) {
								$t_loc = Datamodel::getInstance('ca_storage_locations', true);
								
								if ($this->inTransaction()) { $t_loc->setTransaction($this->getTransaction()); }
								if ($t_loc->load($vn_location_id)) {
									if ($policy) {
										$policy_info = $table::getHistoryTrackingCurrentValuePolicyElement($policy, 'ca_storage_locations', $t_loc->getTypeCode());
										$vn_relationship_type_id = caGetOption('trackingRelationshipType', $policy_info, null);
									}
									if (!$vn_relationship_type_id) { $vn_relationship_type_id = $this->getAppConfig()->get('object_storage_location_tracking_relationship_type'); }
									
									if ($vn_relationship_type_id) {
										// is effective date set?
										$vs_effective_date = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_storage_locations__effective_datenew_0", pString);
						
										$t_item_rel = $this->addRelationship('ca_storage_locations', $vn_location_id, $vn_relationship_type_id, $vs_effective_date);
										if ($this->numErrors()) {
											$po_request->addActionErrors($this->errors(), $vs_f, 'general');
										} else {
											ca_storage_locations::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($po_request, $vs_placement_code, $vs_form_prefix.'_ca_storage_locations', $t_item_rel, $vn_location_id, $processed_bundle_settings);							
										}									
								
										$change_has_been_made = true;
										SearchResult::clearResultCacheForRow('ca_storage_locations', $vn_location_id);
										if ($t_item_rel) { SearchResult::clearResultCacheForRow($t_item_rel->tableName(), $t_item_rel->getPrimaryKey()); }
									}
								}
							}
						}
						
						if (!caGetOption('hide_add_to_loan_controls', $va_bundle_settings, false)) {
							// set loan
							if ($vn_loan_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_loans_idnew_0", pInteger)) {
								if ($vn_loan_type_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_loans_type_idnew_0", pInteger)) {
									$t_item_rel = $this->addRelationship('ca_loans', $vn_loan_id, $vn_loan_type_id);
									if ($this->numErrors()) {
										$po_request->addActionErrors($this->errors(), $vs_f, 'general');
									} else {
										ca_loans::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($po_request, $vs_placement_code, $vs_form_prefix.'_ca_loans', $t_item_rel, $vn_loan_id, $processed_bundle_settings);								
									}		
									
									$change_has_been_made = true;
									SearchResult::clearResultCacheForRow('ca_loans', $vn_loan_id);
									if ($t_item_rel) { SearchResult::clearResultCacheForRow($t_item_rel->tableName(), $t_item_rel->getPrimaryKey()); }
								}
							}
						}
						
						if (!caGetOption('hide_add_to_movement_controls', $va_bundle_settings, false)) {
							// set movement
							if ($vn_movement_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_movements_idnew_0", pInteger)) {
							
								if ($vn_movement_type_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_movements_type_idnew_0", pInteger)) {
									
									$t_item_rel = $this->addRelationship('ca_movements', $vn_movement_id, $vn_movement_type_id);
									if ($this->numErrors()) {
										$po_request->addActionErrors($this->errors(), $vs_f, 'general');
									} else {
										ca_movements::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($po_request, $vs_placement_code, $vs_form_prefix.'_ca_movements', $t_item_rel, $vn_movement_id, $processed_bundle_settings);								
									}		
									
									$change_has_been_made = true;
									SearchResult::clearResultCacheForRow('ca_movements', $vn_movement_id);
									if ($t_item_rel) { SearchResult::clearResultCacheForRow($t_item_rel->tableName(), $t_item_rel->getPrimaryKey()); }
								}
							}
						}
						
						if (!caGetOption('hide_add_to_occurrence_controls', $va_bundle_settings, false)) {
							// set occurrence
							require_once(__CA_MODELS_DIR__."/ca_occurrences.php");
							$t_occ = new ca_occurrences();
							$va_occ_types = $t_occ->getTypeList(); 
							foreach($va_occ_types as $vn_type_id => $vn_type_info) {
								if ($vn_occurrence_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_occurrences_{$vn_type_id}_idnew_0", pInteger)) {
									if ($vn_occ_type_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_occurrences_{$vn_type_id}_type_idnew_0", pInteger)) {
										$t_item_rel = $this->addRelationship('ca_occurrences', $vn_occurrence_id, $vn_occ_type_id);
										if ($this->numErrors()) {
											$po_request->addActionErrors($this->errors(), $vs_f, 'general');
										} else {
											ca_occurrences::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($po_request, $vs_placement_code, $vs_form_prefix.'_ca_occurrences', $t_item_rel, $vn_occurrence_id, $processed_bundle_settings);					
										}	
										$change_has_been_made = true;
										SearchResult::clearResultCacheForRow('ca_occurrences', $vn_occurrence_id);
										if ($t_item_rel) { SearchResult::clearResultCacheForRow($t_item_rel->tableName(), $t_item_rel->getPrimaryKey()); }
									}
								}
							}
						}
						
						if (!caGetOption('hide_add_to_collection_controls', $va_bundle_settings, false)) {
							// set collection
							require_once(__CA_MODELS_DIR__."/ca_collections.php");
							$t_coll = new ca_collections();
							$va_coll_types = $t_coll->getTypeList();
							foreach($va_coll_types as $vn_type_id => $vn_type_info) {
								if ($vn_collection_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_collections_{$vn_type_id}_idnew_0", pInteger)) {
									if ($vn_coll_type_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_collections_{$vn_type_id}_type_idnew_0", pInteger)) {
										$t_item_rel = $this->addRelationship('ca_collections', $vn_collection_id, $vn_coll_type_id);
										if ($this->numErrors()) {
											$po_request->addActionErrors($this->errors(), $vs_f, 'general');
										} else {
											ca_collections::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($po_request, $vs_placement_code, $vs_form_prefix.'_ca_collections', $t_item_rel, $vn_collection_id, $processed_bundle_settings);
										}
										$change_has_been_made = true;
										SearchResult::clearResultCacheForRow('ca_collections', $vn_collection_id);
										if ($t_item_rel) { SearchResult::clearResultCacheForRow($t_item_rel->tableName(), $t_item_rel->getPrimaryKey()); }
									}
								}
							}
						}
						
						if (!caGetOption('hide_add_to_entity_controls', $va_bundle_settings, false)) {
							// set entity
							require_once(__CA_MODELS_DIR__."/ca_entities.php");
							$t_entity = new ca_entities();
							$va_entity_types = $t_entity->getTypeList();
							foreach($va_entity_types as $vn_type_id => $vn_type_info) {
								if ($vn_entity_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_entities_{$vn_type_id}_idnew_0", pInteger)) {
									if ($vn_entity_type_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_entities_{$vn_type_id}_type_idnew_0", pInteger)) {
										$t_item_rel = $this->addRelationship('ca_entities', $vn_entity_id, $vn_entity_type_id);
										if ($this->numErrors()) {
											$po_request->addActionErrors($this->errors(), $vs_f, 'general');
										} else {
											ca_entities::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($po_request, $vs_placement_code, $vs_form_prefix.'_ca_entities', $t_item_rel, $vn_entity_id, $processed_bundle_settings);
										}
										$change_has_been_made = true;
										SearchResult::clearResultCacheForRow('ca_entities', $vn_entity_id);
										if ($t_item_rel) { SearchResult::clearResultCacheForRow($t_item_rel->tableName(), $t_item_rel->getPrimaryKey()); }
									}
								}
							}
						}
						
						if (!caGetOption('hide_add_to_object_controls', $va_bundle_settings, false)) {
							// set object
							if ($vn_object_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_objects_idnew_0", pInteger)) {
								if ($vn_object_type_id = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_ca_objects_type_idnew_0", pInteger)) {
									$t_item_rel = $this->addRelationship('ca_objects', $vn_object_id, $vn_object_type_id);
									if ($this->numErrors()) {
										$po_request->addActionErrors($this->errors(), $vs_f, 'general');
									} else {
										ca_objects::setHistoryTrackingChronologyInterstitialElementsFromHTMLForm($po_request, $vs_placement_code, $vs_form_prefix.'_ca_objects', $t_item_rel, $vn_object_id, $processed_bundle_settings);								
									}		
									
									$change_has_been_made = true;
									SearchResult::clearResultCacheForRow('ca_objects', $vn_object_id);
									if ($t_item_rel) { SearchResult::clearResultCacheForRow($t_item_rel->tableName(), $t_item_rel->getPrimaryKey()); }
								}
							}
						}
						
						if ($change_has_been_made) { SearchResult::clearResultCacheForRow($this->tableName(), $this->getPrimaryKey()); }
						break;
					# -------------------------------
					// This bundle is only available for objects
					case 'ca_objects_deaccession':		// object deaccession information
						if (!$vb_batch && !$this->getPrimaryKey()) { return null; }	// not supported for new records
						if (!$po_request->user->canDoAction('can_edit_ca_objects')) { break; }
						
						if($vb_batch) {
							$vs_batch_mode = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_batch_mode", pString);
							
							if($vs_batch_mode == '_disabled_') { break; }
							
							if($vs_batch_mode == '_delete_') { 
								$this->set('is_deaccessioned', 0);
								$this->update();
								break; 
							}
						}
					
						$this->set('is_deaccessioned', $vb_is_deaccessioned = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}is_deaccessioned", pInteger));
						$this->set('deaccession_notes', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}deaccession_notes", pString));
						$this->set('deaccession_authorized_by', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}deaccession_authorized_by", pString));
						$this->set('deaccession_type_id', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}deaccession_type_id", pString));
	
						$this->set('deaccession_date', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}deaccession_date", pString));
						if ($po_request->config->get('deaccession_use_disposal_date')) {
						    $this->set('deaccession_disposal_date', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}deaccession_disposal_date", pString));
						}
						if ($vb_is_deaccessioned && (bool)$this->getAppConfig()->get('deaccession_force_access_private')) { $this->get('access', 0); }	// set access to private for accessioned items
						$this->update();
						break;
					# -------------------------------
					// This bundle is only available for objects
					case 'ca_object_checkouts':		// object checkout information
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$vb_batch && !$this->getPrimaryKey()) { return null; }	// not supported for new records
						if (!$po_request->user->canDoAction('can_edit_ca_objects')) { break; }
					
						// Save checkout/return note edits
					    $edits = [];
						foreach($_REQUEST as $k => $v) {
						    if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}(checkout|return)_notes_([\d]+)$!", $k, $m)) {
						        $edits[$m[2]][$m[1]] = $v;
						    }
						}
					
					    foreach($edits as $checkout_id => $data) {
					        if ($tc = ca_object_checkouts::find(['checkout_id' => $checkout_id], ['returnAs' => 'firstModelInstance'])) {
                                if((int)$tc->get('object_id') === (int)$this->getPrimaryKey()) {
                                    $tc->setMode(ACCESS_WRITE);
                                    $tc->set('checkout_notes', $data['checkout']);
                                    $tc->set('return_notes', $data['return']);
                                    $tc->update();
                                    if ($tc->numErrors()) {
                                        $po_request->addActionErrors($tc->errors(), 'ca_object_checkouts', 'general');
                                    }
                                }
                            }
                        }
                        
                        // Cancel reservations
                        foreach($_REQUEST as $k => $v) {
						    if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}cancelReservation_([\d]+)$!", $k, $m)) {
						    	if(($t_checkout = new ca_object_checkouts((int)$m[1])) && ($t_checkout->get('object_id') == $this->getPrimaryKey()) && $t_checkout->isReservation()){
						    		if (!$t_checkout->delete(true)) {
                                        $po_request->addActionErrors($t_checkout->errors(), 'ca_object_checkouts', 'general');
                                    }
						    	}
						    }
						}
					
						break;
					# -------------------------------
					case 'ca_object_circulation_status':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$po_request->user->canDoAction('can_edit_ca_objects')) { break; }
						$this->set('circulation_status_id', $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}ca_object_circulation_status", pInteger));
						break;
					# -------------------------------
					// This bundle is only available items for batch editing on representable models
					case 'ca_object_representations_access_status':		
						if (($vb_batch) && (is_a($this, 'RepresentableBaseModel')) && ($vs_batch_mode = $_REQUEST[$vs_prefix_stub.'batch_mode']) && ($vs_batch_mode === '_replace_')) {
							$vn_access = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_access", pString);
							$vn_status = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_status", pString);
							
							$t_rep = new ca_object_representations();
							if ($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction()); }
							if(is_array($va_rep_ids = $this->getRepresentationIDs())) {
								foreach(array_keys($va_rep_ids) as $vn_rep_id) {
									if ($t_rep->load($vn_rep_id)) {
										$t_rep->set('access', $vn_access);
										$t_rep->set('status', $vn_status);
										$t_rep->update();
										
										if ($t_rep->numErrors()) {
											$po_request->addActionErrors($t_rep->errors(), 'ca_object_representations_access_status', 'general');
										}
									}
								}
							}
							
						}
						break;
					# -------------------------------
					// This bundle is only available for ca_metadata_alert_rules
					case 'ca_metadata_alert_triggers':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!($this instanceof ca_metadata_alert_rules)) { return null; }
						if (!$po_request->user->canDoAction('can_use_metadata_alerts')) { break; }
						$this->saveTriggerHTMLFormBundle($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------
					// This bundle is only available for ca_metadata_dictionary_entries
					case 'ca_metadata_dictionary_rules':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!($this instanceof ca_metadata_dictionary_entries)) { return null; }
						if (!$po_request->user->canDoAction('can_configure_data_dictionary')) { break; }

						$this->saveRuleHTMLFormBundle($po_request, $vs_form_prefix, $vs_placement_code);
						break;
					# -------------------------------
					// This bundle is only available items for ca_site_pages
					case 'ca_site_pages_content':
						if(is_array($va_field_list = $this->getHTMLFormElements())) {
							if (!is_array($va_content = $this->get('content'))) { $va_content = []; }
							foreach($va_field_list as $vs_field => $va_element_info) {
								$vs_value = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_{$va_element_info['code']}", pString);
				
								$va_content[$va_element_info['code']] = $vs_value;
							}
				
							$this->set('content', $va_content);
							$this->update();
						}
						break;
					# -------------------------------
					// This bundle is only available items for ca_site_pages
					case 'ca_site_page_media':
					    if ($vb_batch) { break; }   // no batch mode
						// check for existing representations to update (or delete)
						
						$vs_prefix_stub = $vs_placement_code.$vs_form_prefix.'_';
						$vb_allow_fetching_of_urls = (bool)$this->_CONFIG->get('allow_fetching_of_media_from_remote_urls');
						$va_media_ids_sorted = $va_mediasort_order = explode(';',$po_request->getParameter($vs_prefix_stub.'MediaBundleList', pString));
						sort($va_media_ids_sorted, SORT_NUMERIC);
						$va_media_list = $this->getPageMedia();
						
						if (is_array($va_media_ids_sorted)) {
							foreach($va_media_list as $vn_i => $va_media) {
								$this->clearErrors();
								if (strlen($po_request->getParameter($vs_prefix_stub.'access_'.$va_media['media_id'], pInteger)) > 0) {
									if ($vb_allow_fetching_of_urls && ($vs_path = $_REQUEST[$vs_prefix_stub.'media_url_'.$va_media['media_id']])) {
										$va_tmp = explode('/', $vs_path);
										$vs_original_name = array_pop($va_tmp);
									} else {
										$vs_path = $_FILES[$vs_prefix_stub.'media_'.$va_media['media_id']]['tmp_name'];
										$vs_original_name = $_FILES[$vs_prefix_stub.'media_'.$va_media['media_id']]['name'];
									}
									
									$vs_idno = $po_request->getParameter($vs_prefix_stub.'idno_'.$va_media['media_id'], pString);
									$vn_access = $po_request->getParameter($vs_prefix_stub.'access_'.$va_media['media_id'], pInteger);
									$vs_title = trim($po_request->getParameter($vs_prefix_stub.'title_'.$va_media['media_id'], pString));
									$vs_caption = trim($po_request->getParameter($vs_prefix_stub.'caption_'.$va_media['media_id'], pString));
									
									$vn_rank = null;
									if (($vn_rank_index = array_search($va_media['media_id'], $va_mediasort_order)) !== false) {
										$vn_rank = $va_media_ids_sorted[$vn_rank_index];
									}
									
									$this->editMedia($va_media['media_id'], $vs_path, $vs_title, $vs_caption, $vs_idno, $vn_access, ['original_filename' => $vs_original_name, 'rank' => $vn_rank]);
									if ($this->numErrors()) {
										foreach($this->errors() as $o_e) {
											switch($o_e->getErrorNumber()) {
												case 795:
													// field conflicts
													$po_request->addActionError($o_e, $vs_f, $va_media['media_id']);
													break;
												default:
													$po_request->addActionError($o_e, $vs_f, $va_media['media_id']);
													break;
											}
										}
									}
									
									if ($vs_title) {
										//
										// Set media title
										//
										$t_rep = new ca_object_representations();
										if ($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction()); }
										global $g_ui_locale_id;
										if ($t_rep->load($va_media['media_id'])) {
											$t_rep->replaceLabel(array('name' => $vs_title), $g_ui_locale_id, null, true, array('queueIndexing' => true));
											if ($t_rep->numErrors()) {
												$po_request->addActionErrors($t_rep->errors(), $vs_f, $va_media['media_id']);
											}
										}
									}
								} else {
									// is it a delete key?
									$this->clearErrors();
									if (($po_request->getParameter($vs_prefix_stub.$va_media['media_id'].'_delete', pInteger)) > 0) {
										$this->removeMedia($va_media['media_id']);
										if ($this->numErrors()) {
											$po_request->addActionErrors($this->errors(), $vs_f, $va_media['media_id']);
										}
									}
								}
							}
						}

					
						// check for new media to add 
						$va_file_list = $_FILES;
						foreach($_REQUEST as $vs_key => $vs_value) {
						    
							if (preg_match('/^'.$vs_prefix_stub.'media_url_new_([\d]+)$/', $vs_key, $va_matches)) {
								$va_file_list[$vs_key] = array(
									'url' => $vs_value
								);
							} elseif(preg_match('/^'.$vs_prefix_stub.'media_new_([\d]+)$/', $vs_key, $va_matches)) {
								$va_file_list[$vs_key] = array(
									'tmp_name' => $vs_value,
									'name' => $vs_value
								);
							}
						}
						
						foreach($va_file_list as $vs_key => $va_values) {
							$this->clearErrors();
							
							if (!preg_match('/^'.$vs_prefix_stub.'media_new_([\d]+)$/', $vs_key, $va_matches) && (($vb_allow_fetching_of_urls && !preg_match('/^'.$vs_prefix_stub.'media_url_new_([\d]+)$/', $vs_key, $va_matches)) || !$vb_allow_fetching_of_urls)) { continue; }
							
							if($vs_upload_type = $po_request->getParameter($vs_prefix_stub.'upload_typenew_'.$va_matches[1], pString)) {
								$po_request->user->setVar('defaultRepresentationUploadType', $vs_upload_type);
							}
							
                            if ($vb_allow_fetching_of_urls && ($vs_path = $va_values['url'])) {
                                $va_tmp = explode('/', $vs_path);
                                $vs_original_name = array_pop($va_tmp);
                            } else {
                                $vs_path = $va_values['tmp_name'];
                                $vs_original_name = $va_values['name'];
                            }
                            if (!$vs_path) { continue; }
                            
                            $vs_title = trim($po_request->getParameter($vs_prefix_stub.'title_new_'.$va_matches[1], pString));	
                            $vs_caption = $po_request->getParameter($vs_prefix_stub.'caption_new_'.$va_matches[1], pString);
                            $vs_idno = $po_request->getParameter($vs_prefix_stub.'idno_new_'.$va_matches[1], pString);
                            $vn_access = $po_request->getParameter($vs_prefix_stub.'access_new_'.$va_matches[1], pInteger);
                            $this->addMedia($vs_path, $vs_title, $vs_caption, $vs_idno, $vn_access, ['original_filename' => $vs_original_name]);
                            if ($this->numErrors()) {
                                $po_request->addActionErrors($this->errors(), $vs_f, 'new_'.$va_matches[1]);
                            } 
						}
						break;
					# -------------------------------
					//
					case 'ca_item_tags':
						if ($vb_batch) { 
							$vs_batch_mode = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_batch_mode", pString);
							
							if($vs_batch_mode == '_disabled_') { break; }
							
							if (in_array($vs_batch_mode, ['_replace_', '_delete_'])) {
								$this->removeAllTags();
							}
						}
						
						if (!$vb_batch || ($vb_batch && in_array($vs_batch_mode, ['_add_', '_replace_']))) {
							foreach($_REQUEST as $vs_key => $vs_val) {
								if (is_array($vs_val)) { continue; }
								if (!($vs_val = trim($vs_val))) { continue; }
								if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_autocompletenew_([\d]+)$!", $vs_key, $va_matches)) {
								
									foreach(preg_split("![,;]+!", $vs_val) as $v) {
										if (!($v = trim($v))) { continue; }
										$this->addTag($v, $po_request->getUserID(), null, 1, $po_request->getUserID());
										continue;
									}
								}
							
								if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_([\d]+)_delete$!", $vs_key, $va_matches)) {
									$this->removeTag($va_matches[1]);
								}
							}
						}
						
						if (!$vb_batch && is_array($ids_sorted = $va_rel_sort_order = explode(';',$po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}BundleList", pString)))) {
							$tags = $this->getTags();
							$tag_ids = array_map(function($v) { return $v['relation_id']; }, $tags);
							$current_tag_ranks = array_map(function($v) { return $v['rank']; }, $tags);
							foreach($ids_sorted as $i => $id) {
								$this->changeTagRank($id, $current_tag_ranks[$i]);
							}
						}
						
						break;
					# -------------------------------
					//
					case 'ca_item_comments':
						if ($vb_batch) { 
							$vs_batch_mode = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_batch_mode", pString);
							
							if($vs_batch_mode == '_disabled_') { break; }
							
							if (in_array($vs_batch_mode, ['_replace_', '_delete_'])) {
								$this->removeAllComments();
							}
						}
						
						if (!$vb_batch || ($vb_batch && in_array($vs_batch_mode, ['_add_', '_replace_']))) {
							foreach($_REQUEST as $vs_key => $vs_val) {
								if (is_array($vs_val)) { continue; }
								if (!($vs_val = trim($vs_val))) { continue; }
								if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_([\d]+)_delete$!", $vs_key, $va_matches)) {
									$this->removeComment($va_matches[1], null, ['force' => true]);
								}
							}
						}
						
						
						break;
					# -------------------------------
					// This bundle is only available items for ca_object_representations
					case 'ca_representation_transcriptions':
						if ($vb_batch) { 
							$vs_batch_mode = $po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_batch_mode", pString);
							
							if($vs_batch_mode == '_disabled_') { break; }
							
							if (in_array($vs_batch_mode, ['_replace_', '_delete_'])) {
								$this->removeAllTranscriptions();
							}
						}
						
						if (!$vb_batch || ($vb_batch && in_array($vs_batch_mode, ['_add_', '_replace_']))) {
							foreach($_REQUEST as $vs_key => $vs_val) {
								if (is_array($vs_val)) { continue; }
								if (!($vs_val = trim($vs_val))) { continue; }
								if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_([\d]+)_delete$!", $vs_key, $va_matches)) {
									$this->removeTranscription($va_matches[1], null, ['force' => true]);
								}
							}
						}
						break;
					# -------------------------------
				}
			}
		}
	
		BaseModel::unsetChangeLogUnitID();
		$va_bundle_names = [];
		foreach($va_bundles as $va_bundle) {
			$vs_bundle_name = str_replace('ca_attribute_', '', $va_bundle['bundle_name']);
			if (!Datamodel::getInstanceByTableName($vs_bundle_name, true) && !preg_match("!^".$this->tableName()."\.!", $vs_bundle_name)) {
				$vs_bundle_name = $this->tableName().'.'.$vs_bundle_name;
			}
			
			$va_bundle_names[] = $vs_bundle_name;
		}

		// validate metadata dictionary rules
		try {
			$va_violations = $this->validateUsingMetadataDictionaryRules(array('bundles' => $va_bundle_names));
			if (sizeof($va_violations)) {
				if ($vb_we_set_transaction && isset($va_violations['ERR']) && is_array($va_violations['ERR']) && (sizeof($va_violations['ERR']) > 0)) { 
					BaseModel::unsetChangeLogUnitID();
					$this->removeTransaction(false); 
					if ($vb_is_insert) { $this->_FIELD_VALUES[$this->primaryKey()] = null; }	// clear primary key since transaction has been rolled back
				
					foreach($va_violations['ERR'] as $vs_bundle => $va_errs_by_bundle) {
						foreach($va_errs_by_bundle as $vn_i => $va_rule) {
							$vs_bundle = str_replace($this->tableName().".", "", $vs_bundle);
							$po_request->addActionErrors(array(new ApplicationError(1100, caExtractSettingsValueByUserLocale('violationMessage', $va_rule['rule_settings']), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()", 'MetadataDictionary', false,false)), $vs_bundle, 'general');
						}
					}
					return false; 
				}		
			}
		} catch (Exception $e) {
			// TODO: change to specific exception type to allow user to set the specific bundle where the error occurred
			$po_request->addActionErrors(array(new ApplicationError(1100, _t('Invalid rule expression: %1', $e->getMessage()), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()", 'MetadataDictionary', false,false)), $vs_bundle, 'general');
		}
		if ($vb_dryrun) { $this->removeTransaction(false); }
		if ($vb_we_set_transaction) { $this->removeTransaction(true); }
		
		$this->triggerMetadataAlerts();
		return true;
	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	private function _processRelated($po_request, $ps_bundle_name, $ps_form_prefix, $ps_placement_code, $pa_options=null) {
 		$pa_settings = caGetOption('settings', $pa_options, []);
 		$vb_batch = caGetOption('batch', $pa_options, false);
 		
 		if ($bundle_type_restrictions = caGetOption('bundleTypeRestrictions', $pa_settings, null)) {
 			if(!is_array($bundle_type_restrictions)) { $bundle_type_restrictions = [$bundle_type_restrictions]; }
 			if(!in_array($this->getTypeID(), $bundle_type_restrictions)) { return; }
 		}
		
		$vn_min_relationships = caGetOption('minRelationshipsPerRow', $pa_settings, 0);
		$vn_max_relationships = caGetOption('maxRelationshipsPerRow', $pa_settings, 65535);
		if ($vn_max_relationships == 0) { $vn_max_relationships = 65535; }
		
 		$va_rel_ids_sorted = $va_rel_sort_order = explode(';',$po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}BundleList", pString));
		sort($va_rel_ids_sorted, SORT_NUMERIC);
						
 		$va_rel_items = $this->getRelatedItems($ps_bundle_name, $pa_settings);
 		
 		$va_rels_to_add = $va_rels_to_delete = [];
 if(!$vb_batch) {	
		foreach($va_rel_items as $va_rel_item) {
			$vs_key = $va_rel_item['_key'];
			
			$vn_rank = null;
			if ((($vn_rank_index = array_search($va_rel_item['relation_id'], $va_rel_sort_order)) !== false) && (!isset($pa_settings['disableSorts']) || !$pa_settings['disableSorts'])) {
				$vn_rank = $va_rel_ids_sorted[$vn_rank_index];
			}
			
			$this->clearErrors();
			$vn_id = $po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_id".$va_rel_item[$vs_key], pString);
			if ($vn_id) {
				$vn_type_id = $po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_type_id".$va_rel_item[$vs_key], pString);
				$vs_direction = null;
				if (sizeof($va_tmp = explode('_', $vn_type_id)) == 2) {
					$vn_type_id = (int)$va_tmp[1];
					$vs_direction = $va_tmp[0];
				}
				
				//$vs_effective_daterange = $po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_effective_date".$va_rel_item[$vs_key], pString);
				$this->editRelationship($ps_bundle_name, $va_rel_item[$vs_key], $vn_id, $vn_type_id, null, null, $vs_direction, $vn_rank);	
					
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), $ps_bundle_name);
				}
			} else {
				// is it a delete key?
				$this->clearErrors();
				if (($po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_".$va_rel_item[$vs_key].'_delete', pInteger)) > 0) {
					$va_rels_to_delete[] = array('bundle' => $ps_bundle_name, 'relation_id' => $va_rel_item[$vs_key]);
				}
			}
		}
}
 		
 		// process batch remove
 		if ($vb_batch) {
			$vs_batch_mode = $_REQUEST["{$ps_placement_code}{$ps_form_prefix}_batch_mode"];
 			if ($vs_batch_mode == '_disabled_') { return true; }
			if ($vs_batch_mode == '_delete_') {				// remove all relationships and return
				$this->removeRelationships($ps_bundle_name, caGetOption('restrict_to_relationship_types', $pa_settings, null), ['restrictToTypes' => caGetOption('restrict_to_types', $pa_settings, null)]);
				return true;
			}
			if ($vs_batch_mode == '_replace_') {			// remove all existing relationships and then add new ones
				$this->removeRelationships($ps_bundle_name, caGetOption('restrict_to_relationship_types', $pa_settings, null), ['restrictToTypes' => caGetOption('restrict_to_types', $pa_settings, null)]);
			}
		}
		
 		// check for new relations to add
 		foreach($_REQUEST as $vs_key => $vs_value ) {
			if (preg_match("/^{$ps_placement_code}{$ps_form_prefix}_idnew_([\d]+)/", $vs_key, $va_matches)) { 
				$vn_c = intval($va_matches[1]);
				if ($vn_new_id = $po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_idnew_{$vn_c}", pString)) {
					$vn_new_type_id = $po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_type_idnew_{$vn_c}", pString);
				
					$vs_direction = null;
					if (sizeof($va_tmp = explode('_', $vn_new_type_id)) == 2) {
						$vn_new_type_id = (int)$va_tmp[1];
						$vs_direction = $va_tmp[0];
					}
				
					$va_rels_to_add[] = array(
						'bundle' => $ps_bundle_name, 'row_id' => $vn_new_id, 'type_id' => $vn_new_type_id, 'direction' => $vs_direction
					);
				}
			}
			
			// check for checklist mode ca_list_items
			if ($ps_bundle_name == 'ca_list_items') {
				if (preg_match("/^{$ps_placement_code}{$ps_form_prefix}_item_id_new_([\d]+)/", $vs_key, $va_matches)) { 
					if ($vn_rel_type_id = $po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_type_idchecklist", pInteger)) {
						if ($vn_item_id = $po_request->getParameter($vs_key, pInteger)) {
							$va_rels_to_add[] = array(
								'bundle' => $ps_bundle_name, 'row_id' => $vn_item_id, 'type_id' => $vn_rel_type_id, 'direction' => null
							);
						}
					}
				}
				
				if (preg_match("/^{$ps_placement_code}{$ps_form_prefix}_item_id_([\d]+)_delete/", $vs_key, $va_matches)) { 
					if ($po_request->getParameter($vs_key, pInteger)) {
						$va_rels_to_delete[] = array('bundle' => $ps_bundle_name, 'relation_id' => $va_matches[1]);
					}
				}
			}
		}
		
		$bundle_label = caExtractSettingsValueByUserLocale('label', $pa_settings);

		// Check min/max
		$vn_total_rel_count = (sizeof($va_rel_items) + sizeof($va_rels_to_add) - sizeof($va_rels_to_delete));
		if ($vn_min_relationships && ($vn_total_rel_count < $vn_min_relationships)) {
			$po_request->addActionErrors(array(new ApplicationError(2590, ($vn_min_relationships == 1) ? _t('There must be at least %1 relationship for %2', $vn_min_relationships, $bundle_label) : _t('There must be at least %1 relationships for %2', $vn_min_relationships, $bundle_label), 'BundleableLabelableBaseModelWithAttributes::_processRelated()', null, null, false, false)), $ps_bundle_name);
			return false;
		}
		if ($vn_max_relationships && ($vn_total_rel_count > $vn_max_relationships)) {
			$po_request->addActionErrors(array(new ApplicationError(2590, ($vn_max_relationships == 1) ? _t('There must be no more than %1 relationship for %2', $vn_max_relationships, $bundle_label) : _t('There must be no more than %1 relationships for %2', $vn_max_relationships, $bundle_label), 'BundleableLabelableBaseModelWithAttributes::_processRelated()', null, null, false, false)), $ps_bundle_name);
			return false;
		}
		
		// Process relationships
		foreach($va_rels_to_delete as $va_rel_to_delete) {
			$this->removeRelationship($va_rel_to_delete['bundle'], $va_rel_to_delete['relation_id']);
			if ($this->numErrors()) {
				$po_request->addActionErrors($this->errors(), $ps_bundle_name);
			}
		}
		foreach($va_rels_to_add as $va_rel_to_add) {
			$this->addRelationship($va_rel_to_add['bundle'], $va_rel_to_add['row_id'], $va_rel_to_add['type_id'], _t(caGetOption('effectiveDateDefault', $pa_settings, null)), null, $va_rel_to_add['direction']);
			if ($this->numErrors()) {
				$po_request->addActionErrors($this->errors(), $ps_bundle_name);
			}
		}
		
		return true;
 	}
 	# ------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @param string $ps_form_prefix
	 * @param string $ps_placement_code
	 */
	public function _processRelatedSets($po_request, $ps_form_prefix, $ps_placement_code) {
		require_once(__CA_MODELS_DIR__ . '/ca_sets.php');

		foreach($_REQUEST as $vs_key => $vs_value ) {
			// check for new relationships to add
			if (preg_match("/^{$ps_placement_code}{$ps_form_prefix}_idnew_([\d]+)/", $vs_key, $va_matches)) {
				$vn_c = intval($va_matches[1]);
				if ($vn_new_id = $po_request->getParameter("{$ps_placement_code}{$ps_form_prefix}_idnew_{$vn_c}", pString)) {
					$t_set = new ca_sets($vn_new_id);
					$t_set->addItem($this->getPrimaryKey(), null, $po_request->getUserID());
				}
			}

			// check for delete keys
			if (preg_match("/^{$ps_placement_code}{$ps_form_prefix}_([\d]+)_delete/", $vs_key, $va_matches)) {
				$vn_c = intval($va_matches[1]);
				$t_set = new ca_sets($vn_c);
				$t_set->removeItem($this->getPrimaryKey());
			}


		}
	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getRelatedItemsAsSearchResult($pm_rel_table_name_or_num, $pa_options=null) {
 		if (is_array($va_related_ids = $this->getRelatedItems($pm_rel_table_name_or_num, array_merge($pa_options, array('idsOnly' => true, 'sort' => null))))) {
 			
 			$va_ids = array_filter($va_related_ids, function($pn_v) {
 				return ($pn_v > 0) ? true : false;
 			});
 			
 			return $this->makeSearchResult($pm_rel_table_name_or_num, $va_ids, $pa_options);
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns list of items in the specified table related to the currently loaded row or rows specified in options.
 	 * 
 	 * @param mixed $pm_rel_table_name_or_num The table name or table number of the item type you want to get a list of (eg. if you are calling this on an ca_objects instance passing 'ca_entities' here will get you a list of entities related to the object)
 	 * @param array $pa_options Array of options. Supported options are:
 	 *
 	 *		[Options controlling rows for which data is returned]
 	 *			row_ids = Array of primary key values to use when fetching related items. If omitted or set to a null value the 'row_id' option will be used. [Default is null]
 	 *			row_id = Primary key value to use when fetching related items. If omitted or set to a false value (null, false, 0) then the primary key value of the currently loaded row is used. [Default is currently loaded row]
 	 *			start = Zero-based index to begin return set at. [Default is 0]
 	 *			limit = Maximum number of related items to return. [Default is 1000]
 	 *			showDeleted = Return related items that have been deleted. [Default is false]
 	 *			primaryIDs = array of primary keys in related table to exclude from returned list of items. Array is keyed on table name for compatibility with the parameter as used in the caProcessTemplateForIDs() helper [Default is null - nothing is excluded].
 	 *			restrictToBundleValues = Restrict returned items to those with specified bundle values. Specify an associative array with keys set to bundle names and key values set to arrays of values to filter on (eg. [bundle_name1 => [value1, value2, ...]]). [Default is null]
 	 *			where = Restrict returned items to specified field values. The fields must be intrinsic and in the related table. This option can be useful when you want to efficiently fetch specific rows from a related table. Note that multiple fields/values are logically AND'ed together – all must match for a row to be returned - and that only equivalence is supported. [Default is null]			
 	 *			criteria = Restrict returned items using SQL criteria appended directly onto the query. Criteria is used as-is and must be compatible with the generated SQL query. [Default is null]
 	 *			showCurrentOnly = Returns the relationship with the latest effective date for the row_id that is not greater than the current date. This option is only supported for standard many-many self and non-self relations and is ignored for all other kinds of relationships. [Default is false]
 	 *			currentOnly = Synonym for showCurrentOnly
 	 *			policy = History tracking current value policy to filter by, if returned result set is subject to a policy. [Default is null]
 	 *			filterNonPrimaryRepresentations = Set filtering of non-primary representations in those models that support representations [Default is false]
 	 *		
 	 *		[Options controlling scope of data in return value]
 	 *			restrictToTypes = Restrict returned items to those of the specified types. An array or comma/semicolon delimited string of list item idnos and/or item_ids may be specified. [Default is null]
 	 *			restrictToRelationshipTypes =  Restrict returned items to those related using the specified relationship types. An array or comma/semicolon delimited string of relationship type idnos and/or type_ids may be specified. [Default is null]
 	 *			excludeTypes = Restrict returned items to those *not* of the specified types. An array or comma/semicolon delimited string of list item idnos and/or item_ids may be specified. [Default is null]
 	 *			excludeRelationshipTypes = Restrict returned items to those *not* related using the specified relationship types. An or comma/semicolon delimited string array of relationship type idnos and/or type_ids may be specified. [Default is null]
 	 *			restrictToType = Synonym for restrictToTypes. [Default is null]
 	 *			restrictToRelationshipType = Synonym for restrictToRelationshipTypes. [Default is null]
 	 *			excludeType = Synonym for excludeTypes. [Default is null]
 	 *			excludeRelationshipType = Synonym for excludeRelationshipTypes. [Default is null]
 	 *			restrictToLists = Restrict returned items to those that are in one or more specified lists. This option is only relevant when fetching related ca_list_items. An array or comma/semicolon delimited string of list list_codes or list_ids may be specified. [Default is null]
 	 * 			fields = array of fields (in table.fieldname format) to include in returned data. [Default is null]
 	 *			returnNonPreferredLabels = Return non-preferred labels in returned data. [Default is false]
 	 *			returnLabelsAsArray = Return all labels associated with row in an array, rather than as a text value in the current locale. [Default is false]
 	 *			dontReturnLabels = Don't include labels in returned data. [Default is false]
 	 *			idsOnly = Return one-dimensional array of related primary key values only. [Default is false]
 	 *
 	 *		[Options controlling format of data in return value]
 	 *			useLocaleCodes = Return locale values as codes (Ex. en_US) rather than numeric database-specific locale_ids. [Default is false]
 	 *			sort = Array list of bundles to sort returned values on. The sortable bundle specifiers are fields with or without tablename. Only those fields returned for the related table (intrinsics, attributes and label fields) are sortable. [Default is null]
	 *			sortDirection = Direction of sort. Use "asc" (ascending) or "desc" (descending). [Default is asc]
 	 *			groupFields = Groups together fields in an arrangement that is easier for import to another system. Used by the ItemInfo web service when in "import" mode. [Default is false]
 	 *
 	 *		[Front-end access control]	
 	 *			checkAccess = Array of access values to filter returned values on. Available for any related table with an "access" field (ca_objects, ca_entities, etc.). If omitted no filtering is performed. [Default is null]
 	 *			user_id = Perform item level access control relative to specified user_id rather than currently logged in user. [Default is user_id for currently logged in user]
 	 *
 	 *		[Options controlling format of data in return value]
 	 *			returnAs = format of return value; possible values are:
 	 *				data					= return array of data about each related item [default]
	 *				searchResult			= a search result instance (aka. a subclass of BaseSearchResult) 
	 *				ids						= an array of ids (aka. primary keys); same as setting the 'idsOnly' option
	 *				modelInstances			= an array of instances, one for each match. Each instance is the  class of the related item, a subclass of BaseModel 
	 *				firstId					= the id (primary key) of the first match. This is the same as the first item in the array returned by 'ids'
	 *				firstModelInstance		= the instance of the first match. This is the same as the first instance in the array returned by 'modelInstances'
	 *				count					= the number of related items
	 *
	 *					Default is "data" - returns a list of arrays with data about each related item
 	 *
 	 * @param int $pn_count Variable to return number of related items. The count reflects the absolute number of related items, independent of how the start and limit options are set, and may differ from the number of items actually returned.
 	 *
 	 * @return array List of related items
 	 */
	public function getRelatedItems($pm_rel_table_name_or_num, $options=null, &$pn_count=null) {
		global $AUTH_CURRENT_USER_ID;    
		$vn_user_id = (isset($options['user_id']) && $options['user_id']) ? $options['user_id'] : (int)$AUTH_CURRENT_USER_ID;
		$vb_show_if_no_acl = (bool)($this->getAppConfig()->get('default_item_access_level') > __CA_ACL_NO_ACCESS__);

		if (caGetOption('idsOnly', $options, false)) { $options['returnAs'] = 'ids'; }		// 'idsOnly' is synonym for returnAs => 'ids'

		$ps_return_as = caGetOption('returnAs', $options, 'data', array('forceLowercase' => true, 'validValues' => array('data', 'searchResult', 'ids', 'modelInstances', 'firstId', 'firstModelInstance', 'count', 'relationids')));

		// convert options
		if (($options['restrictToTypes'] = caGetOption(array('restrictToTypes', 'restrict_to_types', 'restrictToType', 'restrict_to_type'), $options, null)) && !is_array($options['restrictToTypes'])) {
			$options['restrictToTypes'] = preg_split("![;,]{1}!", $options['restrictToTypes']);
		}
		if (($options['restrictToRelationshipTypes'] = caGetOption(array('restrictToRelationshipTypes', 'restrict_to_relationship_types', 'restrictToRelationshipType', 'restrict_to_relationship_type'), $options, null)) && !is_array($options['restrictToRelationshipTypes'])) {
			$options['restrictToRelationshipTypes'] = preg_split("![;,]{1}!", $options['restrictToRelationshipTypes']);
		}
		if (($options['excludeTypes'] = caGetOption(array('excludeTypes', 'exclude_types', 'excludeType', 'exclude_type'), $options, null)) && !is_array($options['excludeTypes'])) {
			$options['excludeTypes'] = preg_split("![;,]{1}!", $options['excludeTypes']);
		}
		if (($options['excludeRelationshipTypes'] = caGetOption(array('excludeRelationshipTypes', 'exclude_relationship_types', 'excludeRelationshipType', 'exclude_relationship_type'), $options, null)) && !is_array($options['excludeRelationshipTypes'])) {
			$options['excludeRelationshipTypes'] = preg_split("![;,]{1}!", $options['excludeRelationshipTypes']);
		}
		
		if (!isset($options['dontIncludeSubtypesInTypeRestriction']) && (isset($options['dont_include_subtypes_in_type_restriction']) && $options['dont_include_subtypes_in_type_restriction'])) { $options['dontIncludeSubtypesInTypeRestriction'] = $options['dont_include_subtypes_in_type_restriction']; }
		if (!isset($options['returnNonPreferredLabels']) && (isset($options['restrict_to_type']) && $options['restrict_to_type'])) { $options['returnNonPreferredLabels'] = $options['restrict_to_type']; }
		if (!isset($options['returnLabelsAsArray']) && (isset($options['return_labels_as_array']) && $options['return_labels_as_array'])) { $options['returnLabelsAsArray'] = $options['return_labels_as_array']; }
		if (!isset($options['restrictToLists']) && (isset($options['restrict_to_lists']) && $options['restrict_to_lists'])) { $options['restrictToLists'] = $options['restrict_to_lists']; }
		
		if (($options['restrictToLists'] = caGetOption(array('restrictToLists', 'restrict_to_lists'), $options, null)) && !is_array($options['restrictToLists'])) {
			$options['restrictToLists'] = preg_split("![;,]{1}!", $options['restrictToLists']);
		}
		$pb_group_fields = isset($options['groupFields']) ? $options['groupFields'] : false;
		$pa_primary_ids = (isset($options['primaryIDs']) && is_array($options['primaryIDs'])) ? $options['primaryIDs'] : null;
		
		$show_current = caGetOption(['showCurrentOnly', 'currentOnly'], $options, false);
		$policy = caGetOption('policy', $options, method_exists($this, "getDefaultHistoryTrackingCurrentValuePolicy") ? $this->getDefaultHistoryTrackingCurrentValuePolicy() : null);
		
		if (!isset($options['useLocaleCodes']) && (isset($options['returnLocaleCodes']) && $options['returnLocaleCodes'])) { $options['useLocaleCodes'] = $options['returnLocaleCodes']; }
		$pb_use_locale_codes = isset($options['useLocaleCodes']) ? $options['useLocaleCodes'] : false;
		
		$pa_get_where = (isset($options['where']) && is_array($options['where']) && sizeof($options['where'])) ? $options['where'] : null;

		$pa_row_ids = (isset($options['row_ids']) && is_array($options['row_ids'])) ? $options['row_ids'] : null;
		$pn_row_id = (isset($options['row_id']) && $options['row_id']) ? $options['row_id'] : $this->getPrimaryKey();

		$o_db = $this->getDb();
		$t_locale = $this->getLocaleInstance();
		$o_tep = $this->getTimeExpressionParser();
		
		$vb_uses_effective_dates = false;
		$vn_current_date = TimeExpressionParser::now();

		if(isset($options['sort']) && !is_array($options['sort'])) { $options['sort'] = array($options['sort']); }
		$pa_sort_fields = (isset($options['sort']) && is_array($options['sort'])) ? array_filter($options['sort'], "strlen") : null;
		$ps_sort_direction = (isset($options['sortDirection']) && $options['sortDirection']) ? $options['sortDirection'] : null;

		if (!$pa_row_ids && ($pn_row_id > 0)) {
			$pa_row_ids = [$pn_row_id];
		}

		if (!$pa_row_ids || !is_array($pa_row_ids) || !sizeof($pa_row_ids)) { return []; }

		$pb_return_labels_as_array = (isset($options['returnLabelsAsArray']) && $options['returnLabelsAsArray']) ? true : false;
		$pn_limit = (isset($options['limit']) && ((int)$options['limit'] > 0)) ? (int)$options['limit'] : 4000;
		$pn_start = (isset($options['start']) && ((int)$options['start'] > 0)) ? (int)$options['start'] : 0;
		

		if (is_numeric($pm_rel_table_name_or_num)) {
			if(!($vs_related_table_name = Datamodel::getTableName($pm_rel_table_name_or_num))) { return null; }
		} else {
			if (sizeof($va_tmp = explode(".", $pm_rel_table_name_or_num)) > 1) {
				$pm_rel_table_name_or_num = array_shift($va_tmp);
			}
			if (!($o_instance = Datamodel::getInstanceByTableName($pm_rel_table_name_or_num, true))) { return null; }
			$vs_related_table_name = $pm_rel_table_name_or_num;
		}

		if (!is_array($options)) { $options = []; }

		$vb_is_combo_key_relation = false; // indicates relation is via table_num/row_id combination key
		
		$vs_subject_table_name = $this->tableName();
		$vs_item_rel_table_name = $vs_rel_item_table_name = null;
		
		switch(sizeof($va_path = array_keys(Datamodel::getPath($vs_subject_table_name, $vs_related_table_name)))) {
			case 3:
			
				$t_item_rel = Datamodel::getInstance($va_path[1], true);
				$vs_item_rel_table_name = $va_path[1];
				
				$t_rel_item = Datamodel::getInstance($va_path[2], true);
				$vs_rel_item_table_name = $va_path[2];
				
				$vs_key = $t_item_rel->primaryKey(); //'relation_id';
				
				break;
			case 2:
				$t_item_rel = $this->isRelationship() ? $this : null;
				$vs_item_rel_table_name = $t_item_rel ? $t_item_rel->tableName() : null;
				
				if (!($t_rel_item = Datamodel::getInstance($va_path[1], true))) { return null; }
				$vs_rel_item_table_name = $va_path[1];
				
				$vs_key = $t_rel_item->primaryKey();
				break;
			default:
				// is this related with row_id/table_num combo?
				if ($vs_related_table_name == 'ca_item_tags') {
					$va_path = [$this->tableName(), 'ca_items_x_tags', 'ca_item_tags'];
					$vb_is_combo_key_relation = true;
					
					$t_item_rel = Datamodel::getInstance($va_path[1], true);
					$vs_item_rel_table_name = $va_path[1];
					$t_rel_item = Datamodel::getInstance($va_path[2], true);
					$vs_rel_item_table_name = $va_path[2];
					$vs_key = $t_item_rel->primaryKey();
					break;
				}
				
				
				if (
					($t_rel_item = Datamodel::getInstance($vs_related_table_name, true))
					&&
					$t_rel_item->hasField('table_num') && $t_rel_item->hasField('row_id')
				) {
					$vs_key = $t_rel_item->primaryKey();
					$vs_rel_item_table_name = $t_rel_item->tableName();
					
					$vb_is_combo_key_relation = true;
					$va_path = array($vs_subject_table_name, $vs_rel_item_table_name);
				} else {
					// bad related table
					return null;
				}
				break;
		}

		// check for self relationship
		$vb_self_relationship = false;
		if($vs_subject_table_name == $vs_related_table_name) {
			$vb_self_relationship = true;
			$t_item_rel = Datamodel::getInstance($va_path[1], true);
			$vs_item_rel_table_name = $va_path[1];
			
			$t_rel_item = Datamodel::getInstance($va_path[0], true);
			$vs_rel_item_table_name = $va_path[0];
		}

		$va_wheres = [];
		$va_selects = [];
		$va_joins_post_add = [];

		$vs_related_table = $vs_rel_item_table_name;
		if ($t_rel_item->hasField('type_id')) { $va_selects[] = "{$vs_related_table}.type_id item_type_id"; }
		if ($t_rel_item->hasField('source_id')) { $va_selects[] = "{$vs_related_table}.source_id item_source_id"; }

		// TODO: get these field names from models
		if (($t_tmp = $t_item_rel) || ($t_rel_item->isRelationship() && ($t_tmp = $t_rel_item))) {
			// define table names
			$vs_linking_table = $t_tmp->tableName();

			$va_selects[] = "{$vs_related_table}.".$t_rel_item->primaryKey();

			// include dates in returned data
			if ($t_tmp->hasField('effective_date')) {
				$va_selects[] = $vs_linking_table.'.sdatetime';
				$va_selects[] = $vs_linking_table.'.edatetime';

				$vb_uses_effective_dates = true;
			}

			if ($t_rel_item->hasField('is_enabled')) {
				$va_selects[] = "{$vs_related_table}.is_enabled";
			}


			if ($t_tmp->hasField('type_id')) {
				$va_selects[] = $vs_linking_table.'.type_id relationship_type_id';

				require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
				$t_rel = new ca_relationship_types();

				$vb_uses_relationship_types = true;
			}

			// limit related items to a specific type
			if ($vb_uses_relationship_types && isset($options['restrictToRelationshipTypes']) && $options['restrictToRelationshipTypes']) {
				if (!is_array($options['restrictToRelationshipTypes'])) {
					$options['restrictToRelationshipTypes'] = array($options['restrictToRelationshipTypes']);
				}

				if (sizeof($options['restrictToRelationshipTypes'])) {
					$va_rel_types = [];
					foreach($options['restrictToRelationshipTypes'] as $vm_type) {
						if (!$vm_type) { continue; }
						if (!($vn_type_id = $t_rel->getRelationshipTypeID($vs_linking_table, $vm_type))) {
							$vn_type_id = (int)$vm_type;
						}
						if ($vn_type_id > 0) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}

					if (sizeof($va_rel_types)) {
						$va_wheres[] = '('.$vs_linking_table.'.type_id IN ('.join(',', $va_rel_types).'))';
					}
				}
			}

			if ($vb_uses_relationship_types && isset($options['excludeRelationshipTypes']) && $options['excludeRelationshipTypes']) {
				if (!is_array($options['excludeRelationshipTypes'])) {
					$options['excludeRelationshipTypes'] = array($options['excludeRelationshipTypes']);
				}

				if (sizeof($options['excludeRelationshipTypes'])) {
					$va_rel_types = [];
					foreach($options['excludeRelationshipTypes'] as $vm_type) {
						if ($vn_type_id = $t_rel->getRelationshipTypeID($vs_linking_table, $vm_type)) {
							$va_rel_types[] = $vn_type_id;
							if (is_array($va_children = $t_rel->getHierarchyChildren($vn_type_id, array('idsOnly' => true)))) {
								$va_rel_types = array_merge($va_rel_types, $va_children);
							}
						}
					}

					if (sizeof($va_rel_types)) {
						$va_wheres[] = '('.$vs_linking_table.'.type_id NOT IN ('.join(',', $va_rel_types).'))';
					}
				}
			}
		}

		// limit related items to a specific type
		$va_type_ids = caMergeTypeRestrictionLists($t_rel_item, $options);

		if (is_array($va_type_ids) && (sizeof($va_type_ids) > 0)) {
			$va_wheres[] = "({$vs_related_table}.type_id IN (".join(',', $va_type_ids).')'.($t_rel_item->getFieldInfo('type_id', 'IS_NULL') ? " OR ({$vs_related_table}.type_id IS NULL)" : '').')';
		}

		$va_source_ids = caMergeSourceRestrictionLists($t_rel_item, $options);
		if (method_exists($t_rel_item, "getSourceFieldName") && ($vs_source_id_fld = $t_rel_item->getSourceFieldName()) && is_array($va_source_ids) && (sizeof($va_source_ids) > 0)) {
			$va_wheres[] = "({$vs_related_table}.{$vs_source_id_fld} IN (".join(',', $va_source_ids)."))";
		}

		if (isset($options['excludeType']) && $options['excludeType']) {
			if (!isset($options['excludeTypes']) || !is_array($options['excludeTypes'])) {
				$options['excludeTypes'] = [];
			}
			$options['excludeTypes'][] = $options['excludeType'];
		}

		if (isset($options['excludeTypes']) && is_array($options['excludeTypes'])) {
			$va_type_ids = caMakeTypeIDList($vs_related_table, $options['excludeTypes']);

			if (is_array($va_type_ids) && (sizeof($va_type_ids) > 0)) {
				$va_wheres[] = "({$vs_related_table}.type_id NOT IN (".join(',', $va_type_ids)."))";
			}
		}

		if ($this->getAppConfig()->get('perform_item_level_access_checking')) {
			$t_user = new ca_users($vn_user_id);
			if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
				$va_group_ids = array_keys($va_groups);
			} else {
				$va_group_ids = [];
			}

			// Join to limit what browse table items are used to generate facet
			$va_joins_post_add[] = 'LEFT JOIN ca_acl ON '.$vs_related_table_name.'.'.$t_rel_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_rel_item->tableNum()."\n";
			$va_wheres[] = "(
				((
					(ca_acl.user_id = ".(int)$vn_user_id.")
					".((sizeof($va_group_ids) > 0) ? "OR
					(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
					OR
					(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
				) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
				".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
			)";
		}

		if (is_array($pa_get_where)) {
			foreach($pa_get_where as $vs_fld => $vm_val) {
				if ($t_rel_item->hasField($vs_fld)) {
					$va_wheres[] = "({$vs_related_table_name}.{$vs_fld} = ".(!is_numeric($vm_val) ? "'".$this->getDb()->escape($vm_val)."'": $vm_val).")";
				}
			}
		}

		if ($vs_idno_fld = $t_rel_item->getProperty('ID_NUMBERING_ID_FIELD')) { $va_selects[] = "{$vs_related_table}.{$vs_idno_fld}"; }
		if ($vs_idno_sort_fld = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD')) { $va_selects[] = "{$vs_related_table}.{$vs_idno_sort_fld}"; }

		$va_selects[] = $va_path[1].'.'.$vs_key;

		if (isset($options['fields']) && is_array($options['fields'])) {
			$va_selects = array_merge($va_selects, $options['fields']);
		}

		// if related item is labelable then include the label table in the query as well
		$vs_label_display_field = null;
		if (method_exists($t_rel_item, "getLabelTableName") && (!isset($options['dontReturnLabels']) || !$options['dontReturnLabels'])) {
			if($vs_label_table_name = $t_rel_item->getLabelTableName()) {           // make sure it actually has a label table...
				$va_path[] = $vs_label_table_name;
				$t_rel_item_label = Datamodel::getInstance($vs_label_table_name);
				$vs_label_display_field = $t_rel_item_label->getDisplayField();

				if($pb_return_labels_as_array || (is_array($pa_sort_fields) && sizeof($pa_sort_fields))) {
					$va_selects[] = $vs_label_table_name.'.*';
				} else {
					$va_selects[] = $vs_label_table_name.'.'.$vs_label_display_field;
					$va_selects[] = $vs_label_table_name.'.locale_id';

					if ($t_rel_item_label->hasField('surname')) {	// hack to include fields we need to sort entity labels properly
						$va_selects[] = $vs_label_table_name.'.surname';
						$va_selects[] = $vs_label_table_name.'.forename';
					}
				}

				if ($t_rel_item_label->hasField('is_preferred') && (!isset($options['returnNonPreferredLabels']) || !$options['returnNonPreferredLabels'])) {
					$va_wheres[] = "(".$vs_label_table_name.'.is_preferred = 1)';
				}
			}
		}

		// return source info in returned data
		if ($t_item_rel && $t_item_rel->hasField('source_info')) {
			$va_selects[] = $vs_linking_table.'.source_info';
		}

		if (isset($options['checkAccess']) && is_array($options['checkAccess']) && sizeof($options['checkAccess']) && $t_rel_item->hasField('access')) {
			$va_wheres[] = "({$vs_related_table}.access IN (".join(',', $options['checkAccess'])."))";
		}

		if ((!isset($options['showDeleted']) || !$options['showDeleted']) && $t_rel_item->hasField('deleted')) {
			$va_wheres[] = "({$vs_related_table}.deleted = 0)";
		}
		
		if (($va_criteria = (isset($options['criteria']) ? $options['criteria'] : null)) && (is_array($va_criteria)) && (sizeof($va_criteria))) {
			$va_wheres[] = "(".join(" AND ", $va_criteria).")"; 
		}
		
		if(caGetOption('filterNonPrimaryRepresentations', $options, false)) {
			if ($t_item_rel && $t_item_rel->hasField('is_primary')) {
				$va_wheres[] = "({$vs_item_rel_table_name}.is_primary = 1)";
			}
		
			if ($t_rel_item && $t_rel_item->hasField('is_primary')) {
				$va_wheres[] = "({$vs_related_table}.is_primary = 1)";
			}
		}

		if($vb_self_relationship) {
			//
			// START - traverse self relation
			//
			$va_rel_info = Datamodel::getRelationships($va_path[0], $va_path[1]);
			if ($vs_label_table_name) {
				$va_label_rel_info = Datamodel::getRelationships($va_path[0], $vs_label_table_name);
			}
	
			$va_rels = $va_rels_by_date = [];

			$vn_i = 0;
			$vn_c = 0;
			foreach($va_rel_info[$va_path[0]][$va_path[1]] as $va_possible_keys) {
				$va_joins = [];
				$va_joins[] = "INNER JOIN ".$va_path[1]." ON ".$va_path[1].'.'.$va_possible_keys[1].' = '.$va_path[0].'.'.$va_possible_keys[0]."\n";

				if ($vs_label_table_name) {
					$va_joins[] = "INNER JOIN ".$vs_label_table_name." ON ".$vs_label_table_name.'.'.$va_label_rel_info[$va_path[0]][$vs_label_table_name][0][1].' = '.$va_path[0].'.'.$va_label_rel_info[$va_path[0]][$vs_label_table_name][0][0]."\n";
				}

				$vs_other_field = ($vn_i == 0) ? $va_rel_info[$va_path[0]][$va_path[1]][1][1] : $va_rel_info[$va_path[0]][$va_path[1]][0][1];
				$vs_direction =  (preg_match('!left!', $vs_other_field)) ? 'ltor' : 'rtol';

				$va_selects['row_id'] = $va_path[1].'.'.$vs_other_field.' AS row_id';

				$vs_order_by = '';
				$vs_sort_fld = '';
				if ($t_item_rel && $t_item_rel->hasField('rank')) {
					$vs_order_by = " ORDER BY {$vs_item_rel_table_name}.`rank`";
					$vs_sort_fld = 'rank';
					$va_selects[] = "{$vs_item_rel_table_name}.`rank`";
				} else {
					if ($t_rel_item && ($vs_sort = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD'))) {
						$vs_order_by = " ORDER BY {$vs_related_table}.{$vs_sort}";
						$vs_sort_fld = $vs_sort;
						$va_selects[] = "{$vs_related_table}.{$vs_sort}";
					}
				}

				if($show_current) {
					$va_wheres[] = "
						(
							".$this->primaryKey(true)." IN (
								SELECT row_id
								FROM ca_history_tracking_current_values
								WHERE 
									current_table_num = ".$this->tableNum()." AND 
									current_row_id = ".$this->getPrimaryKey()." AND 
									table_num = ".$t_rel_item->tableNum()."
							)
						)
				"	;
				} 
				$vs_sql = "
					SELECT ".join(', ', $va_selects)."
					FROM ".$va_path[0]."
					".join("\n", array_merge($va_joins, $va_joins_post_add))."
					WHERE
						".join(' AND ', array_merge($va_wheres, array('('.$va_path[1].'.'.$vs_other_field .' IN ('.join(',', $pa_row_ids).'))')))."
					{$vs_order_by}";

				$qr_res = $o_db->query($vs_sql);
				
				if (!is_null($pn_count)) { $pn_count = $qr_res->numRows(); }

				if ($vb_uses_relationship_types) { $va_rel_types = $t_rel->getRelationshipInfo($va_path[1]); }
				if (($pn_start > 0) && !is_array($pa_sort_fields)) { $qr_res->seek($pn_start); }
				while($qr_res->nextRow()) {
					if (($vn_c >= $pn_limit) && !is_array($pa_sort_fields)) { break; }
					
					if (is_array($pa_primary_ids) && is_array($pa_primary_ids[$vs_related_table])) {
						if (in_array($qr_res->get($vs_key), $pa_primary_ids[$vs_related_table])) { continue; }
					}
					
					$va_row = $qr_res->getRow();
					$vn_id = $va_row[$vs_key].'/'.$va_row['row_id'];
					$vs_sort_key = $qr_res->get($vs_sort_fld);

					$vs_display_label = $va_row[$vs_label_display_field];

					if (!$va_rels[$vs_sort_key][$vn_id]) {
						$va_rels[$vs_sort_key][$vn_id] = $qr_res->getRow();
					}

					if ($vb_uses_effective_dates) {	// return effective dates as display/parse-able text
						if ($va_rels[$vs_sort_key][$vn_id]['sdatetime'] || $va_rels[$vs_sort_key][$vn_id]['edatetime']) {
							$o_tep->setHistoricTimestamps($va_rels[$vs_sort_key][$vn_id]['sdatetime'], $va_rels[$vs_sort_key][$vn_id]['edatetime']);
							$va_rels[$vs_sort_key][$vn_id]['effective_date'] = $o_tep->getText();
						}
					}

					$vn_locale_id = $qr_res->get('locale_id');
					if ($pb_use_locale_codes) {
						$va_rels[$vs_sort_key][$vn_id]['locale_id'] = $vn_locale_id = $t_locale->localeIDToCode($vn_locale_id);
					}

					$va_rels[$vs_sort_key][$vn_id]['labels'][$vn_locale_id] =  ($pb_return_labels_as_array) ? $va_row : $vs_display_label;
					$va_rels[$vs_sort_key][$vn_id]['_key'] = $vs_key;
					$va_rels[$vs_sort_key][$vn_id]['direction'] = $vs_direction;

					$vn_c++;
					if ($vb_uses_relationship_types) {
						$va_rels[$vs_sort_key][$vn_id]['relationship_typename'] = ($vs_direction == 'ltor') ? $va_rel_types[$va_row['relationship_type_id']]['typename'] : $va_rel_types[$va_row['relationship_type_id']]['typename_reverse'];
						$va_rels[$vs_sort_key][$vn_id]['relationship_type_code'] = $va_rel_types[$va_row['relationship_type_id']]['type_code'];
					}

					//
					// Return data in an arrangement more convenient for the data importer 
					//
					if ($pb_group_fields) {
						$vs_rel_pk = $t_rel_item->primaryKey();
						if ($t_rel_item_label) {
							foreach($t_rel_item_label->getFormFields() as $vs_field => $va_field_info) {
								if (!isset($va_rels[$vs_sort_key][$vn_id][$vs_field]) || ($vs_field == $vs_rel_pk)) { continue; }
								$va_rels[$vs_sort_key][$vn_id]['preferred_labels'][$vs_field] = $va_rels[$vs_sort_key][$vn_id][$vs_field];
								unset($va_rels[$vs_sort_key][$vn_id][$vs_field]);
							}
						}
						foreach($t_rel_item->getFormFields() as $vs_field => $va_field_info) {
							if (!isset($va_rels[$vs_sort_key][$vn_id][$vs_field]) || ($vs_field == $vs_rel_pk)) { continue; }
							$va_rels[$vs_sort_key][$vn_id]['intrinsic'][$vs_field] = $va_rels[$vs_sort_key][$vn_id][$vs_field];
							unset($va_rel[$vs_sort_key][$vn_id][$vs_field]);
						}
						unset($va_rels[$vs_sort_key][$vn_id]['_key']);
						unset($va_rels[$vs_sort_key][$vn_id]['row_id']);
					}
				}
				$vn_i++;
			}
			ksort($va_rels);	// sort by sort key... we'll remove the sort key in the next loop while we add the labels

			// Set 'label' entry - display label in current user's locale
			$va_sorted_rels = [];
			foreach($va_rels as $vs_sort_key => $va_rels_by_sort_key) {
				foreach($va_rels_by_sort_key as $vn_id => $va_rel) {
					$va_tmp = array(0 => $va_rel['labels']);
					$va_sorted_rels[$vn_id] = $va_rel;
					$va_values_filtered_by_locale = caExtractValuesByUserLocale($va_tmp);
					$va_sorted_rels[$vn_id]['label'] = array_shift($va_values_filtered_by_locale);
				}
			}
			$va_rels = $va_sorted_rels;
	
			if ($ps_return_as !== 'data') {
				$va_rels = caExtractArrayValuesFromArrayOfArrays($va_rels, ($ps_return_as === 'relationids') ? 'relation_id' : $t_rel_item->primaryKey());
			}

			//
			// END - traverse self relation
			//
		} else if (method_exists($this, 'isSelfRelationship') && $this->isSelfRelationship()) {
			//
			// START - from self relation itself (Eg. get related ca_objects from ca_objects_x_objects); in this case there are two possible paths (keys) to check, "left" and "right"
			//
			
			$show_current = false;	// show current not supported in this context
			
			$va_wheres[] = "({$vs_subject_table_name}.".$this->primaryKey()." IN (".join(",", $pa_row_ids)."))";
			$vs_cur_table = array_shift($va_path);
			$vs_rel_table = array_shift($va_path);
			
			$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_rel_table);

			$va_rels = [];
			foreach($va_rel_info[$vs_cur_table][$vs_rel_table] as $vn_i => $va_rel) {
				$va_joins = array(
					'INNER JOIN '.$vs_rel_table.' ON '.$vs_cur_table.'.'.$va_rel[0].' = '.$vs_rel_table.'.'.$va_rel[1]."\n"	
				);
				
				$vs_base_table = $vs_rel_table;
				foreach($va_path as $vs_join_table) {
					$va_label_rel_info = Datamodel::getRelationships($vs_base_table, $vs_join_table);
					$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_base_table.'.'.$va_label_rel_info[$vs_base_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_label_rel_info[$vs_base_table][$vs_join_table][0][1]."\n";
					$vs_base_table = $vs_join_table;
				}
				
				$va_selects[] = $vs_subject_table_name.'.'.$this->primaryKey().' AS row_id';

                $vb_use_is_primary = false;
                if ($t_item_rel && $t_item_rel->hasField('is_primary')) {
                    $va_selects[] = $t_item_rel->tableName().'.is_primary';
                    $vb_use_is_primary = true;
                }

				$vs_order_by = '';
				if ($t_item_rel && $t_item_rel->hasField('rank')) {
					$vs_order_by = " ORDER BY {$vs_item_rel_table_name}.`rank`";
					$va_selects[] = $t_item_rel->tableName().'.`rank`';
				} else {
					if ($t_rel_item && ($vs_sort = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD'))) {
						$vs_order_by = " ORDER BY {$vs_related_table}.{$vs_sort}";
						$va_selects[] = "{$vs_related_table}.{$vs_sort}";
					}
				}

				$vs_sql = "
					SELECT DISTINCT ".join(', ', $va_selects)."
					FROM {$vs_subject_table_name}
					".join("\n", array_merge($va_joins, $va_joins_post_add))."
					WHERE
						".join(' AND ', $va_wheres)."
					{$vs_order_by}
				";

				//print "<pre>$vs_sql</pre>\n";
				$qr_res = $o_db->query($vs_sql);
				
				if (!is_null($pn_count)) { $pn_count = $qr_res->numRows(); }
				
				if ($vb_uses_relationship_types)  {
					$va_rel_types = $t_rel->getRelationshipInfo($this->tableName());
					$vs_left_table = $this->getLeftTableName();
					$vs_direction = ($vs_left_table == $vs_subject_table_name) ? 'ltor' : 'rtol';
				}
				
				$vn_c = 0;
				if (($pn_start > 0) && !is_array($pa_sort_fields)) { $qr_res->seek($pn_start); }
				while($qr_res->nextRow()) {
					if (($vn_c >= $pn_limit) && !is_array($pa_sort_fields)) { break; }
					
					if (is_array($pa_primary_ids) && is_array($pa_primary_ids[$vs_related_table])) {
						if (in_array($qr_res->get($vs_key), $pa_primary_ids[$vs_related_table])) { continue; }
					}
			
					$va_row = $qr_res->getRow();
					$vs_v = $va_row['row_id'].'/'.$va_row[$vs_key];

					$vs_display_label = $va_row[$vs_label_display_field];

					if (!isset($va_rels[$vs_v]) || !$va_rels[$vs_v]) {
						$va_rels[$vs_v] = $va_row;
					}

					if ($vb_uses_effective_dates) {	// return effective dates as display/parse-able text
						if ($va_rels[$vs_v]['sdatetime'] || $va_rels[$vs_v]['edatetime']) {
							$o_tep->setHistoricTimestamps($va_rels[$vs_v]['sdatetime'], $va_rels[$vs_v]['edatetime']);
							$va_rels[$vs_v]['effective_date'] = $o_tep->getText();
						}
					}

					$vn_locale_id = $qr_res->get('locale_id');

					if ($pb_use_locale_codes) {
						$va_rels[$vs_v]['locale_id'] = $vn_locale_id = $t_locale->localeIDToCode($vn_locale_id);
					}

					$va_rels[$vs_v]['labels'][$vn_locale_id] =  ($pb_return_labels_as_array) ? $va_row : $vs_display_label;

					$va_rels[$vs_v]['_key'] = $vs_key;
					$va_rels[$vs_v]['direction'] = $vs_direction;
					
                    if ($vb_use_is_primary) {
                        $va_rels_for_id[$vs_v]['is_primary'] = $qr_res->get('is_primary');
                    }

					$vn_c++;
					if ($vb_uses_relationship_types) {
						$va_rels[$vs_v]['relationship_typename'] = ($vs_direction == 'ltor') ? $va_rel_types[$va_row['relationship_type_id']]['typename'] : $va_rel_types[$va_row['relationship_type_id']]['typename_reverse'];
						$va_rels[$vs_v]['relationship_type_code'] = $va_rel_types[$va_row['relationship_type_id']]['type_code'];
					}

					if ($pb_group_fields) {
						$vs_rel_pk = $t_rel_item->primaryKey();
						if ($t_rel_item_label) {
							foreach($t_rel_item_label->getFormFields() as $vs_field => $va_field_info) {
								if (!isset($va_rels[$vs_v][$vs_field]) || ($vs_field == $vs_rel_pk)) { continue; }
								$va_rels[$vs_v]['preferred_labels'][$vs_field] = $va_rels[$vs_v][$vs_field];
								unset($va_rels[$vs_v][$vs_field]);
							}
						}
						foreach($t_rel_item->getFormFields() as $vs_field => $va_field_info) {
							if (!isset($va_rels[$vs_v][$vs_field]) || ($vs_field == $vs_rel_pk)) { continue; }
							$va_rels[$vs_v]['intrinsic'][$vs_field] = $va_rels[$vs_v][$vs_field];
							unset($va_rels[$vs_v][$vs_field]);
						}
						unset($va_rels[$vs_v]['_key']);
						unset($va_rels[$vs_v]['row_id']);
					}
				}

				if ($ps_return_as === 'data') {
					// Set 'label' entry - display label in current user's locale
					foreach($va_rels as $vs_v => $va_rel) {
						$va_tmp = array(0 => $va_rel['labels']);
						$va_tmp2 = caExtractValuesByUserLocale($va_tmp);
						$va_rels[$vs_v]['label'] = array_shift($va_tmp2);
					}
				}
			}
			
			if ($ps_return_as !== 'data') {
				$va_rels = caExtractArrayValuesFromArrayOfArrays($va_rels, ($ps_return_as === 'relationids') ? 'relation_id' : $t_rel_item->primaryKey());
			}
			
			//
			// END - from self relation itself
			//
		} else {
			//
			// BEGIN - non-self relation
			//
			$va_wheres[] = "({$vs_subject_table_name}.".$this->primaryKey()." IN (".join(",", $pa_row_ids)."))";
			$vs_cur_table = array_shift($va_path);
			$va_joins = [];

			// Enforce restrict_to_lists for related list items
			if (($vs_related_table_name == 'ca_list_items') && is_array($options['restrictToLists'])) {
				$va_list_ids = [];
				foreach($options['restrictToLists'] as $vm_list) {
					if ($vn_list_id = ca_lists::getListID($vm_list)) { $va_list_ids[] = $vn_list_id; }
				}
				if (sizeof($va_list_ids)) {
					$va_wheres[] = "(ca_list_items.list_id IN (".join(",", $va_list_ids)."))";
				}
			}

			if ($vb_is_combo_key_relation) {
				if ($vs_item_rel_table_name) {
					$va_joins = ["INNER JOIN {$vs_item_rel_table_name} ON {$vs_item_rel_table_name}.row_id = ".$this->primaryKey(true)." AND {$vs_item_rel_table_name}.table_num = ".$this->tableNum()];
					$va_joins[] = "INNER JOIN {$vs_related_table_name} ON {$vs_related_table_name}.".$t_rel_item->primaryKey()." = {$vs_item_rel_table_name}.".$t_rel_item->primaryKey();
				} else {
					$va_joins = ["INNER JOIN {$vs_related_table_name} ON {$vs_related_table_name}.row_id = ".$this->primaryKey(true)." AND {$vs_related_table_name}.table_num = ".$this->tableNum()];
				}
				if(method_exists($t_rel_item, "getLabelTableInstance") && ($t_rel_label = $t_rel_item->getLabelTableInstance())) {
				    $vs_related_label_table_name = $t_rel_label->tableName();
				    $vs_rel_pk = $t_rel_item->primaryKey();
				    $va_joins[] = "INNER JOIN {$vs_related_label_table_name} ON {$vs_related_label_table_name}.{$vs_rel_pk} = {$vs_related_table_name}.{$vs_rel_pk}";
			    }
			} else {
				foreach($va_path as $vs_join_table) {
					$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
					$vs_join = 'INNER JOIN '.$vs_join_table.' ON ';
				
					$va_tmp = [];
					foreach($va_rel_info[$vs_cur_table][$vs_join_table] as $vn_i => $va_rel) {
						$va_tmp[] = $vs_cur_table.".".$va_rel_info[$vs_cur_table][$vs_join_table][$vn_i][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][$vn_i][1]."\n";
					}
					$va_joins[] = $vs_join.' ('.join(' OR ', $va_tmp).')'.(Datamodel::getFieldNum($vs_join_table, 'deleted') ? " AND ({$vs_join_table}.deleted = 0)" : '');
					$vs_cur_table = $vs_join_table;
				}
				
				
			
				// When related item is a relationship filter filter "restrictToTypes" as relationship types
				// (if specified) and also filter out deleted target rows (eg. don't include relationships pointing to deleted rows).
                if (method_exists($t_rel_item, 'isRelationship') && $t_rel_item->isRelationship()) {
                    $va_rels = Datamodel::getManyToOneRelations($t_rel_item->tableName());
                    
                    // Derive filter for deleted target rows
                    $deleted_filter = null;
                    foreach($va_rels as $vs_rel_pk => $va_rel_info) {
						if(!in_array($va_rel_info['one_table'], [$this->tableName(), 'ca_relationship_types'])) {
							$deleted_filter = (Datamodel::getFieldNum($va_rel_info['one_table'], 'deleted') ? " AND (r.deleted = 0)" : '');
							break;
						}
					}   
                    
                    if(is_array($options['restrictToTypes']) && sizeof($options['restrictToTypes'])) {
                        foreach($va_rels as $vs_rel_pk => $va_rel_info) {
                            if (!in_array($va_rel_info['one_table'], [$this->tableName()])) {
                                $va_type_ids = caMakeTypeIDList($va_rel_info['one_table'], $options['restrictToTypes']);
                    
                                if (is_array($va_type_ids) && sizeof($va_type_ids)) { 
                                    $va_wheres[] = "(r.type_id IN (".join(",", $va_type_ids)."))";
                                    $va_joins[] = "INNER JOIN {$va_rel_info['one_table']} AS r ON r.{$va_rel_info['one_table_field']} = {$vs_related_table_name}.".($vs_rel_pk ?? $t_rel_item->primaryKey()).$deleted_filter;
                                }
                                break;
                            }
                        }
                    }elseif(is_array($options['excludeTypes']) && sizeof($options['excludeTypes'])) {
                        foreach($va_rels as $vs_rel_pk => $va_rel_info) {
                            if (!in_array($va_rel_info['one_table'], [$this->tableName()])) {
                                $va_type_ids = caMakeTypeIDList($va_rel_info['one_table'], $options['excludeTypes']);
                                
                                if (is_array($va_type_ids) && sizeof($va_type_ids)) { 
                                    $va_wheres[] = "(r.type_id NOT IN (".join(",", $va_type_ids)."))";
                                    $va_joins[] = "INNER JOIN {$va_rel_info['one_table']} AS r ON r.{$va_rel_info['one_table_field']} = {$vs_related_table_name}.".($vs_rel_pk ?? $t_rel_item->primaryKey()).$deleted_filter;
                                }
                                break;
                            }
                        }
                    } elseif($deleted_filter) {
                    	$va_joins[] = "INNER JOIN {$va_rel_info['one_table']} AS r ON r.{$va_rel_info['one_table_field']} = {$vs_related_table_name}.".($vs_rel_pk ?? $t_rel_item->primaryKey()).(Datamodel::getFieldNum($va_rel_info['one_table'], 'deleted') ? " AND (r.deleted = 0)" : '');
                    }   
                }
			}

			// If we're getting ca_set_items, we have to rename the intrinsic row_id field because the pk is named row_id below. Hence, this hack.
			if($vs_related_table_name == 'ca_set_items') {
				$va_selects[] = 'ca_set_items.row_id AS record_id';
			}
			
			$vb_use_is_primary = false;
			if ($t_item_rel && $t_item_rel->hasField('is_primary')) {
			    $va_selects[] = $t_item_rel->tableName().'.is_primary';
			    $vb_use_is_primary = true;
			}

			$va_selects[] = $vs_subject_table_name.'.'.$this->primaryKey().' AS row_id';

			$vs_order_by = '';
			if ($t_item_rel && $t_item_rel->hasField('rank')) {
				$vs_order_by = " ORDER BY {$vs_item_rel_table_name}.`rank`";
				$va_selects[] = $t_item_rel->tableName().'.`rank`';
			} else {
				if ($t_rel_item && ($vs_sort = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD'))) {
					$vs_order_by = " ORDER BY {$vs_related_table}.{$vs_sort}";
					$va_selects[] = "{$vs_related_table}.{$vs_sort}";
				}
			}
			
			if($show_current && $this->getPrimaryKey()) {
				$policy_info = $this::getPolicyConfig($policy);
				if(
					($policy_info['table'] === $this->tableName()) && 
					$this::historyTrackingPolicyUses($policy, $t_rel_item->tableName())
				) {
					$va_wheres[] = "
						(
							".$t_rel_item->primaryKey(true)." IN (
								SELECT current_row_id
								FROM ca_history_tracking_current_values
								WHERE 
									table_num = ".$this->tableNum()." AND 
									row_id = ".$this->getPrimaryKey()." AND 
									current_table_num = ".$t_rel_item->tableNum()."
							)
						)
					";
				} elseif(
					($policy_info['table'] === $t_rel_item->tableName()) && 
					$this::historyTrackingPolicyUses($policy, $this->tableName())
				) {
						$va_wheres[] = "
							(
								".$t_rel_item->primaryKey(true)." IN (
									SELECT row_id
									FROM ca_history_tracking_current_values
									WHERE 
										current_table_num = ".$this->tableNum()." AND 
										current_row_id = ".$this->getPrimaryKey()." AND 
										table_num = ".$t_rel_item->tableNum()."
								)
							)
						";
				}
			}
			
			$vs_sql = "
				SELECT DISTINCT ".join(', ', $va_selects)."
				FROM {$vs_subject_table_name}
				".join("\n", array_merge($va_joins, $va_joins_post_add))."
				WHERE
					".join(' AND ', $va_wheres)."
				{$vs_order_by}
			";
			
			$qr_res = $o_db->query($vs_sql);
			
			if (!is_null($pn_count)) { $pn_count = $qr_res->numRows(); }
			
			if ($vb_uses_relationship_types)  {
				$va_rel_types = $t_rel->getRelationshipInfo($t_tmp->tableName());
				if(method_exists($t_tmp, 'getLeftTableName')) {
					$vs_left_table = $t_tmp->getLeftTableName();
					$vs_direction = ($vs_left_table == $vs_subject_table_name) ? 'ltor' : 'rtol';
				}
			}
			
			$va_rels = [];
			$va_rels_by_date = [];
			
			$vn_c = 0;
			if (($pn_start > 0) && !is_array($pa_sort_fields)) { $qr_res->seek($pn_start); }
			$va_seen_row_ids = [];
			$va_relation_ids = $va_rels_for_id_by_date = [];
			while($qr_res->nextRow()) {
				$va_rels_for_id = [];
				if (($vn_c >= $pn_limit) && !is_array($pa_sort_fields)) { break; }
				
				if (is_array($pa_primary_ids) && is_array($pa_primary_ids[$vs_related_table])) {
					if (in_array($qr_res->get($vs_key), $pa_primary_ids[$vs_related_table])) { continue; }
				}

				$va_row = $qr_res->getRow();
				$vs_v = (sizeof($va_path) <= 2) ? $va_row['row_id'].'/'.$va_row[$vs_key] : $va_row[$vs_key];

				$vs_display_label = $va_row[$vs_label_display_field];

				if (!isset($va_rels_for_id[$vs_v]) || !$va_rels_for_id[$vs_v]) {
					$va_rels_for_id[$vs_v] = $va_row;
				}

				if ($vb_uses_effective_dates) {	// return effective dates as display/parse-able text
					if ($va_rels_for_id[$vs_v]['sdatetime'] || $va_rels_for_id[$vs_v]['edatetime']) {
						$o_tep->setHistoricTimestamps($va_rels_for_id[$vs_v]['sdatetime'], $va_rels_for_id[$vs_v]['edatetime']);
						$va_rels_for_id[$vs_v]['effective_date'] = $o_tep->getText();
					}
				}

				$vn_locale_id = $qr_res->get('locale_id');
				if ($pb_use_locale_codes) {
					$va_rels_for_id[$vs_v]['locale_id'] = $vn_locale_id = $t_locale->localeIDToCode($vn_locale_id);
				}

				$va_rels_for_id[$vs_v]['labels'][$vn_locale_id] =  ($pb_return_labels_as_array) ? $va_row : $vs_display_label;

				$va_rels_for_id[$vs_v]['_key'] = $vs_key;
				$va_rels_for_id[$vs_v]['direction'] = $vs_direction;
				
				if ($vb_use_is_primary) {
				    $va_rels_for_id[$vs_v]['is_primary'] = $qr_res->get('is_primary');
                }
                
				$vn_c++;
				if ($vb_uses_relationship_types) {
					$va_rels_for_id[$vs_v]['relationship_typename'] = ($vs_direction == 'ltor') ? $va_rel_types[$va_row['relationship_type_id']]['typename'] : $va_rel_types[$va_row['relationship_type_id']]['typename_reverse'];
					$va_rels_for_id[$vs_v]['relationship_type_code'] = $va_rel_types[$va_row['relationship_type_id']]['type_code'];
				}

				if ($pb_group_fields) {
					$vs_rel_pk = $t_rel_item->primaryKey();
					if ($t_rel_item_label) {
						foreach($t_rel_item_label->getFormFields() as $vs_field => $va_field_info) {
							if (!isset($va_rels_for_id[$vs_v][$vs_field]) || ($vs_field == $vs_rel_pk)) { continue; }
							$va_rels_for_id[$vs_v]['preferred_labels'][$vs_field] = $va_rels_for_id[$vs_v][$vs_field];
							unset($va_rels_for_id[$vs_v][$vs_field]);
						}
					}
					foreach($t_rel_item->getFormFields() as $vs_field => $va_field_info) {
						if (!isset($va_rels_for_id[$vs_v][$vs_field]) || ($vs_field == $vs_rel_pk)) { continue; }
						$va_rels_for_id[$vs_v]['intrinsic'][$vs_field] = $va_rels_for_id[$vs_v][$vs_field];
						unset($va_rels_for_id[$vs_v][$vs_field]);
					}
					unset($va_rels_for_id[$vs_v]['_key']);
					unset($va_rels_for_id[$vs_v]['row_id']);
				}
				
				$va_rels = array_replace($va_rels, $va_rels_for_id);
				
				$va_seen_row_ids[$va_row['row_id']] = true;
			}
							
			if ($ps_return_as !== 'data') {
				$va_rels = caExtractArrayValuesFromArrayOfArrays($va_rels, ($ps_return_as === 'relationids') ? 'relation_id' : $t_rel_item->primaryKey());
			}
			

			if ($ps_return_as === 'data') {
				// Set 'label' entry - display label in current user's locale
				foreach($va_rels as $vs_v => $va_rel) {
					$va_tmp = array(0 => $va_rel['labels']);
					$va_tmp2 = caExtractValuesByUserLocale($va_tmp);
					$va_rels[$vs_v]['label'] = array_shift($va_tmp2);
				}
			} 
			
			//
			// END - non-self relation
			//
		}
	
		// Apply restrictToBundleValues
		$va_filters = isset($options['restrictToBundleValues']) ? $options['restrictToBundleValues'] : null;
		if(is_array($va_filters) && (sizeof($va_filters)>0)) {
			foreach($va_rels as $vn_pk => $va_related_item) {
				foreach($va_filters as $vs_filter => $va_filter_vals) {
					if(!$vs_filter) { continue; }
					if (!is_array($va_filter_vals)) { $va_filter_vals = array($va_filter_vals); }

					foreach($va_filter_vals as $vn_index => $vs_filter_val) {
						// is value a list attribute idno?
						$va_tmp = explode('.',$vs_filter);
						$vs_element = array_pop($va_tmp);
						if (!is_numeric($vs_filter_val) && (($t_element = ca_metadata_elements::getInstance($vs_element)) && ($t_element->get('datatype') == 3))) {
							$va_filter_vals[$vn_index] = caGetListItemID($t_element->get('list_id'), $vs_filter_val);
						}
					}

					$t_rel_item->load($va_related_item[$t_rel_item->primaryKey()]);
					$va_filter_values = $t_rel_item->get($vs_filter, array('returnAsArray' => true, 'alwaysReturnItemID' => true));

					$vb_keep = false;
					if (is_array($va_filter_values)) {
						foreach($va_filter_values as $vm_filtered_val) {
							if(!is_array($vm_filtered_val)) { $vm_filtered_val = array($vm_filtered_val); }

							foreach($vm_filtered_val as $vs_val) {
								if (in_array($vs_val, $va_filter_vals)) {	// one match is enough to keep it
									$vb_keep = true;
								}
							}
						}
					}

					if(!$vb_keep) {
						unset($va_rels[$vn_pk]);
					}
				}
			}
		}

		//
		// Sort on fields if specified
		//
		if (is_array($pa_sort_fields) && sizeof($pa_sort_fields) && sizeof($va_rels)) {
			$va_ids = $va_ids_to_rel_ids = [];
			$vs_rel_pk = $t_rel_item->primaryKey();
			foreach($va_rels as $vn_i => $va_rel) {
				if(is_array($va_rel)) {
					$va_ids[$vn_i] = $va_rel[$vs_rel_pk];
					$va_ids_to_rel_ids[$va_rel[$vs_rel_pk]][] = $vn_i;
				} else {
					$va_ids[$vn_i] = $va_rel;
					$va_ids_to_rel_ids[$va_rel][] = $vn_i;
				}
			}
			if (sizeof($va_ids) > 0) {
				$qr_sort = caMakeSearchResult($vs_related_table_name, array_values($va_ids), array('sort' => $pa_sort_fields, 'sortDirection' => $ps_sort_direction));
				
				$va_rels_sorted = [];
				
				$vs_rel_pk_full = $t_rel_item->primaryKey(true);
				while($qr_sort->nextHit()) {
					foreach($va_ids_to_rel_ids[$qr_sort->get($vs_rel_pk_full)] as $vn_rel_id) {
						$va_rels_sorted[$vn_rel_id] = $va_rels[$vn_rel_id];
					}
				}
				$va_rels = $va_rels_sorted;
			}
		}
		if ((($pn_start > 0) || ($pn_limit > 0)) && is_array($pa_sort_fields)) { 
			$va_rels = array_slice($va_rels, $pn_start, $pn_limit);
		}
		
		switch($ps_return_as) {
			case 'firstmodelinstance':
				foreach($va_rels as $vn_id) {
					$o_instance = new $vs_related_table_name;
					if ($o_instance->load($vn_id)) {
						return $o_instance;
					}
				}
				return null;
				break;
			case 'modelinstances':
				$va_instances = [];
				foreach($va_rels as $vn_id) {
					$o_instance = new $vs_related_table_name;
					if ($o_instance->load($vn_id)) {
						$va_instances[] = $o_instance;
					}
				}
				return $va_instances;
				break;
			case 'firstid':
				if(sizeof($va_rels)) {
					return array_shift($va_rels);
				}
				return null;
				break;
			case 'count':
				return sizeof(array_unique($va_rels));
				break;
			case 'searchresult':
				if (sizeof($va_rels) > 0) {
					return caMakeSearchResult($vs_related_table_name, $va_rels);
				}
				return null;
				break;
			case 'relationids':
				return $va_rels;	// contains relation_id values
				break;
			default:
				return $va_rels;	// contains related item primary key values or data
				break;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function getTypeMenu() {
		$t_list = new ca_lists();
		$t_list->load(array('list_code' => $this->getTypeListCode()));
		
		$t_list_item = new ca_list_items();
		$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
		$va_hierarchy = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
		
		$va_types = [];
		if (is_array($va_hierarchy)) {
			
			$va_types_by_parent_id = [];
			$vn_root_id = null;
			foreach($va_hierarchy as $vn_item_id => $va_item) {
				if (!$vn_root_id) { $vn_root_id = $va_item['parent_id']; continue; }
				$va_types_by_parent_id[$va_item['parent_id']][] = $va_item;
			}
			foreach($va_hierarchy as $vn_item_id => $va_item) {
				if ($va_item['parent_id'] != $vn_root_id) { continue; }
				// does this item have sub-items?
				if (isset($va_types_by_parent_id[$va_item['item_id']]) && is_array($va_types_by_parent_id[$va_item['item_id']])) {
					$va_subtypes = $this->_getSubTypes($va_types_by_parent_id[$va_item['item_id']], $va_types_by_parent_id);
				} else {
					$va_subtypes = [];
				}
				$va_types[] = array(
					'displayName' =>$va_item['name_singular'],
					'parameters' => array(
						'type_id' => $va_item['item_id']
					),
					'navigation' => $va_subtypes
				);
			}
		}
		return $va_types;
	}
	# ------------------------------------------------------------------
	/**
	 * Override's BaseModel method to intercept calls for field 'idno'; uses the specified IDNumbering
	 * plugin to generate HTML for idno. If no plugin is specified then the call is passed on to BaseModel::htmlFormElement()
	 * Calls for fields other than idno are passed to BaseModel::htmlFormElement()
	 */
	public function htmlFormElement($ps_field, $ps_format=null, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = []; }
		foreach (array(
				'name', 'form_name', 'request', 'field_errors', 'display_form_field_tips', 'no_tooltips', 'label', 'readonly'
				) 
			as $vs_key) {
			if(!isset($pa_options[$vs_key])) { $pa_options[$vs_key] = null; }
		}
		
		if (!$this->opo_idno_plugin_instance) {
			$this->loadIDNoPlugInInstance($pa_options);
		}
		if (
			($ps_field == $this->getProperty('ID_NUMBERING_ID_FIELD')) 
			&& 
			($this->opo_idno_plugin_instance)
			&&
			$pa_options['request']
		) {
			
			$vs_idno_fld = $this->getProperty('ID_NUMBERING_ID_FIELD');
			
			if (($this->tableName() == 'ca_objects') && $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && !$this->getAppConfig()->get('ca_objects_x_collections_hierarchy_disable_object_collection_idno_inheritance') && $pa_options['request']) {
				if(!($vn_collection_id = $pa_options['request']->getParameter('collection_id', pInteger)) && ($obj_coll_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type'))) {
					if(is_array($coll_ids = $this->get('ca_collections.collection_id', ['restrictToRelationshipTypes' => $obj_coll_rel_type, 'returnAsArray' => true]))) {
						$vn_collection_id = array_shift($coll_ids);
					}
				}
				
				// Parent will be set to collection
				$t_coll = new ca_collections($vn_collection_id);
				if ($this->inTransaction()) { $t_coll->setTransaction($this->getTransaction()); }
				if ($t_coll->getPrimaryKey()) {
					$this->opo_idno_plugin_instance->isChild(true, $t_coll->get('idno'));
					if (!$this->opo_idno_plugin_instance->formatHas('PARENT') && !$this->opo_idno_plugin_instance->getFormatProperty('dont_inherit_from_parent_collection')) { $this->set($vs_idno_fld, $t_coll->get('idno')); }
				}
			} elseif ($vn_parent_id = $this->get($vs_parent_id_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD'))) { 
				// Parent will be set
				$t_parent = Datamodel::getInstanceByTableName($this->tableName(), false);
				if ($this->inTransaction()) { $t_parent->setTransaction($this->getTransaction()); }
				
				if (!$this->opo_idno_plugin_instance->getFormatProperty('dont_inherit_from_parent')) {
                    if ($t_parent->load($vn_parent_id)) {
                    	$pidno = $t_parent->get($this->tableName().".{$vs_idno_fld}");
                        $this->opo_idno_plugin_instance->isChild(true, $pidno); 
                        if (!$this->getPrimaryKey() && !$this->opo_idno_plugin_instance->formatHas('PARENT')) {
                            $this->set($vs_idno_fld, 
                                ($pidno) ? 
                                    $this->opo_idno_plugin_instance->makeTemplateFromValue($pidno, 1, true)
                                    :
                                    ''
                            );
                        }
                    }
                } elseif(!$this->getPrimaryKey()) {
                    $this->set($this->tableName().".{$vs_idno_fld}", '');
                }
			}	// if it has a parent_id then set the id numbering plugin using "child_only" numbering schemes (if defined)
			
			
			$this->opo_idno_plugin_instance->setValue($this->get($ps_field));
			if (method_exists($this, "getTypeCode")) { $this->opo_idno_plugin_instance->setType($this->getTypeCode()); }
			$vs_element = $this->opo_idno_plugin_instance->htmlFormElement(
										$ps_field,  
										$va_errors, 
										array_merge(
											$pa_options,
											array(
												'error_icon' 				=> caNavIcon(__CA_NAV_ICON_ALERT__, 1),
												'progress_indicator'		=> caNavIcon(__CA_NAV_ICON_SPINNER__, 1),
												'show_errors'				=> ($this->getPrimaryKey()) ? true : false,
												'context_id'				=> isset($pa_options['context_id']) ? $pa_options['context_id'] : null,
												'table' 					=> $this->tableName(),
												'row_id' 					=> $this->getPrimaryKey(),
												'type_id' 					=> $this->getTypeID(),
												'check_for_dupes'			=> true,
												'search_url'				=> caSearchUrl($pa_options['request'], $this->tableName(), '')
											)
										)
			);
			
			if (is_null($ps_format)) {
				if (isset($pa_options['field_errors']) && is_array($pa_options['field_errors']) && sizeof($pa_options['field_errors'])) {
					$ps_format = $this->_CONFIG->get('bundle_element_error_display_format');
					$va_field_errors = [];
					foreach($pa_options['field_errors'] as $o_e) {
						$va_field_errors[] = $o_e->getErrorDescription();
					}
					$vs_errors = join('; ', $va_field_errors);
				} else {
					$ps_format = $this->_CONFIG->get('bundle_element_display_format');
					$vs_errors = '';
				}
			}
			if ($ps_format != '') {
				$ps_formatted_element = $ps_format;
				$ps_formatted_element = str_replace("^ELEMENT", $vs_element, $ps_formatted_element);

				$va_attr = $this->getFieldInfo($ps_field);
				
				foreach (array(
						'DISPLAY_DESCRIPTION', 'DESCRIPTION', 'LABEL', 'DESCRIPTION', 
						) 
					as $vs_key) {
					if(!isset($va_attr[$vs_key])) { $va_attr[$vs_key] = null; }
				}
				
// TODO: should be in config file
$pa_options["display_form_field_tips"] = true;
				if (
					$pa_options["display_form_field_tips"] ||
					(!isset($pa_options["display_form_field_tips"]) && $va_attr["DISPLAY_DESCRIPTION"]) ||
					(!isset($pa_options["display_form_field_tips"]) && !isset($va_attr["DISPLAY_DESCRIPTION"]) && $vb_fl_display_form_field_tips)
				) {
					if (preg_match("/\^DESCRIPTION/", $ps_formatted_element)) {
						$ps_formatted_element = str_replace("^LABEL", isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"], $ps_formatted_element);
						$ps_formatted_element = str_replace("^DESCRIPTION",$va_attr["DESCRIPTION"], $ps_formatted_element);
					} else {
						// no explicit placement of description text, so...
						$vs_field_id = '_'.$this->tableName().'_'.$this->getPrimaryKey().'_'.$pa_options["name"].'_'.$pa_options['form_name'];
						$ps_formatted_element = str_replace("^LABEL",'<span id="'.$vs_field_id.'">'.(isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"]).'</span>', $ps_formatted_element);

						if (!$pa_options['no_tooltips']) {
							TooltipManager::add('#'.$vs_field_id, "<h3>".(isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"])."</h3>".$va_attr["DESCRIPTION"]);
						}
					}
				} else {
					$ps_formatted_element = str_replace("^LABEL", (isset($pa_options['label']) ? $pa_options['label'] : $va_attr["LABEL"]), $ps_formatted_element);
					$ps_formatted_element = str_replace("^DESCRIPTION", "", $ps_formatted_element);
				}

				$ps_formatted_element = str_replace("^ERRORS", $vs_errors, $ps_formatted_element);
				$vs_element = $ps_formatted_element;
			}
			
			
			return $vs_element;
		} else {
			return parent::htmlFormElement($ps_field, $ps_format, $pa_options);
		}
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function getIDNoPlugInInstance() {
		if (!$this->opo_idno_plugin_instance) { return null; }
		$this->opo_idno_plugin_instance->setDb($this->getDb());	// Make sure returned instance is using current transaction database handle
		return $this->opo_idno_plugin_instance;
	}
	# ----------------------------------------
	/**
	 * 
	 */
	public function loadIDNoPlugInInstance($pa_options=null) {
		if ($this->opo_idno_plugin_instance) { return $this->opo_idno_plugin_instance; }
		return $this->opo_idno_plugin_instance = IDNumbering::newIDNumberer($this->tableName(), $this->getTypeCode(), null, $this->getDb(), caGetOption('IDNumberingConfig', $pa_options, null));
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function validateAdminIDNo($ps_admin_idno) {
		$va_errors = [];
		if ($this->_CONFIG->get('require_valid_id_number_for_'.$this->tableName()) && sizeof($va_admin_idno_errors = $this->opo_idno_plugin_instance->isValidValue($ps_admin_idno))) {
			$va_errors[] = join('; ', $va_admin_idno_errors);
		} elseif (!$this->_CONFIG->get('allow_duplicate_id_number_for_'.$this->tableName()) && sizeof($this->checkForDupeAdminIdnos($ps_admin_idno))) {
			$va_errors[] = _t("Identifier %1 already exists and duplicates are not permitted", $ps_admin_idno);
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id) {
		$va_subtypes = [];
		foreach($pa_subtypes as $vn_i => $va_type) {
			if (is_array($pa_types_by_parent_id[$va_type['item_id']])) {
				$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id);
			} else {
				$va_subsubtypes = [];
			}
			$va_subtypes[$va_type['item_id']] = array(
				'displayName' => $va_type['name_singular'],
				'parameters' => array(
					'type_id' => $va_type['item_id']
				),
				'navigation' => $va_subsubtypes
			);
		}
		
		return $va_subtypes;
	}
	# ------------------------------------------------------------------
	/**
	 * Creates a search result instance for the specified table containing the specified keys as if they had been returned by a search or browse.
	 * This method is useful when you need to efficiently retrieve data from an arbitrary set of records since you get all of the lazy loading functionality
	 * of a standard search result without having to actually perform a search.
	 *
	 * @param mixed $pm_rel_table_name_or_num The name or table number of the table for which to create the result set. Must be a searchable/browsable table
	 * @param array $pa_ids List of primary key values to create result set for. Result set will contain specified keys in the order in which that are passed in the array.
	 * @param array $pa_options Optional array of options to pass through to SearchResult::init(). If you need the search result to connect to the database with a specific database transaction you should pass the Db instance used by the transaction here in the 'db' option. Other options include:
	 *		sort = field or attribute to sort on in <table name>.<field or attribute name> format (eg. ca_objects.idno); default is to sort on relevance (aka. sort='_natural')
	 *		sortDirection = direction to sort results by, either 'asc' for ascending order or 'desc' for descending order; default is 'asc'
	 *		instance = An instance of the model for $pm_rel_table_name_or_num; if passed makeSearchResult can use it to directly extract model information potentially saving time [Default is null]
	 *		returnIndex = Return array with result instance (key "result") and list of sorted primary key values (key "index"). [Default is false]
	 *
	 * @return SearchResult A search result of for the specified table
	 */
	public function makeSearchResult($pm_rel_table_name_or_num, $pa_ids, $pa_options=null) {
		if (!is_array($pa_ids)) { return null; }
		
		if (!isset($pa_options['instance']) || !($t_instance = $pa_options['instance'])) {
			if (!($t_instance = Datamodel::getInstance($pm_rel_table_name_or_num, true))) { return null; }
		}
		$va_ids = [];
		foreach($pa_ids as $vn_k => $vn_id) {
			if (is_numeric($vn_id)) { 
				$va_ids[$vn_k] = $vn_id;
			}
		}
		// sort?
		if ($pa_sort = caGetOption('sort', $pa_options, null)) {
			if (!is_array($pa_sort)) { $pa_sort = [$pa_sort]; }
			
			$vo_sort = new BaseFindEngine($this->getDb());
			$va_ids = $vo_sort->sortHits($va_ids, $t_instance->tableName(), join(';', $pa_sort), caGetOption('sortDirection', $pa_options, 'asc'), $pa_options);
		}
		if (!($vs_search_result_class = $t_instance->getProperty('SEARCH_RESULT_CLASSNAME'))) { return null; }
		$o_data = new WLPlugSearchEngineCachedResult($va_ids, $t_instance->tableNum());
		/** @var BaseSearchResult $o_res */
		$o_res = new $vs_search_result_class($t_instance->tableName());	// we pass the table name here so generic multi-table search classes such as InterstitialSearch know what table they're operating over
		$o_res->init($o_data, [], $pa_options);

		if(caGetOption('returnIndex', $pa_options, false)) {
			return ['result' => $o_res, 'index' => $va_ids];
		}

		return $o_res;
	}
	# ------------------------------------------------------------------
	/**
	 * Creates a search result instance for the specified table containing the specified keys as if they had been returned by a search or browse.
	 * This method is useful when you need to efficiently retrieve data from an arbitrary set of records since you get all of the lazy loading functionality
	 * of a standard search result without having to actually perform a search.
	 *
	 * Requires PHP 5.3 since it uses the get_called_class() function
	 *
	 * @param array $pa_ids List of primary key values to create result set for. Result set will contain specified keys in the order in which that are passed in the array.
	 * @return SearchResult A search result of for the specified table
	 */
	static public function createResultSet($pa_ids) {
		if (!is_array($pa_ids) || !sizeof($pa_ids)) { return null; }
		$pn_table_num = Datamodel::getTableNum(get_called_class());
		if (!($t_instance = Datamodel::getInstanceByTableNum($pn_table_num))) { return null; }
		
		foreach($pa_ids as $vn_id) {
			if (!is_numeric($vn_id)) { 
				return false;
			}
		}
	
		if (!($vs_search_result_class = $t_instance->getProperty('SEARCH_RESULT_CLASSNAME'))) { return null; }
		require_once(__CA_LIB_DIR__.'/Search/'.$vs_search_result_class.'.php');
		$o_data = new WLPlugSearchEngineCachedResult($pa_ids, $t_instance->tableNum());
		$o_res = new $vs_search_result_class();
		$o_res->init($o_data, []);
		
		return $o_res;
	}
	# --------------------------------------------------------------------------------------------
	# Access control lists
	# --------------------------------------------------------------------------------------------		
	/**
	 * 
	 */
	public function getACLUserHTMLFormBundle($po_request, $ps_form_name, $pa_options=null) {
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		require_once(__CA_MODELS_DIR__.'/ca_users.php');
		$t_user = new ca_users();
		
		
		$o_view->setVar('t_instance', $this);
		$o_view->setVar('table_num', $pn_table_num);
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('request', $po_request);	
		$o_view->setVar('t_user', $t_user);
		$o_view->setVar('initialValues', $this->getACLUsers(array('returnAsInitialValuesForBundle' => true)));
		
		return $o_view->render('ca_acl_users.php');
	}
	# --------------------------------------------------------------------------------------------	
	/**
	 * Returns array of user-based ACL entries associated with the currently loaded row. The array
	 * is key'ed on user user user_id; each value is an  array containing information about the user. Array keys are:
	 *			user_id			[user_id for user]
	 *			user_name		[name of user]
	 *			email			[email address of user]
	 *			fname			[first name of user]
	 *			lname			[last name of user]
	 *
	 * @param array $pa_options Supported options:
	 *		returnAsInitialValuesForBundle = if set array is returned suitable for use with the ACLUsers bundle; the array is key'ed by acl_id and includes the _display entry required by the bundle
	 *
	 * @return array List of user-base ACL entries associated with the currently loaded row
	 */ 
	public function getACLUsers($pa_options=null) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		if (!is_array($pa_options)) { $pa_options = []; }
		$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;

		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT u.*, acl.*
			FROM ca_acl acl
			INNER JOIN ca_users AS u ON u.user_id = acl.user_id
			WHERE
				acl.table_num = ? AND acl.row_id = ? AND acl.user_id IS NOT NULL
		", $this->tableNum(), $vn_id);
		
		$va_users = [];
		$va_user_ids = $qr_res->getAllFieldValues("user_id");
		if ($qr_users = $this->makeSearchResult('ca_users', $va_user_ids)) {
			$va_initial_values = caProcessRelationshipLookupLabel($qr_users, new ca_users(), array('stripTags' => true));
		} else {
			$va_initial_values = [];
		}
		$qr_res->seek(0);
		
		$t_acl = new ca_acl();
		while($qr_res->nextRow()) {
			$va_row = [];
			foreach(array('user_id', 'fname', 'lname', 'email', 'access') as $vs_f) {
				$va_row[$vs_f] = $qr_res->get($vs_f);
			}
			
			if ($vb_return_for_bundle) {
				$va_row['label'] = $va_initial_values[$va_row['user_id']]['label'];
				$va_row['id'] = $va_row['user_id'];
				$va_row['access_display'] = $t_acl->getChoiceListValue('access', $va_row['access']);
				$va_users[(int)$qr_res->get('acl_id')] = $va_row;
			} else {
				$va_users[(int)$qr_res->get('user_id')] = $va_row;
			}
		}
		
		return $va_users;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */ 
	public function addACLUsers($pa_user_ids) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		
		$vn_table_num = $this->tableNum();
		
		$t_acl = new ca_acl();
		foreach($pa_user_ids as $vn_user_id => $vn_access) {
			$t_acl->clear();
			$t_acl->load(array('user_id' => $vn_user_id, 'table_num' => $vn_table_num, 'row_id' => $vn_id));		// try to load existing record
			
			$t_acl->set('table_num', $vn_table_num);
			$t_acl->set('row_id', $vn_id);
			$t_acl->set('user_id', $vn_user_id);
			$t_acl->set('access', $vn_access);
			
			if ($t_acl->getPrimaryKey()) {
				$t_acl->update();
			} else {
				$t_acl->insert();
			}
			
			if ($t_acl->numErrors()) {
				$this->errors = $t_acl->errors;
				return false;
			}
		}
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */ 
	public function setACLUsers($pa_user_ids) {
		$this->removeAllACLUsers();
		if (!$this->addACLUsers($pa_user_ids)) { return false; }
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */ 
	public function removeACLUsers($pa_user_ids) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		
		$vn_table_num = $this->tableNum();
		
		$va_current_users = $this->getACLUsers();
		
		$t_acl = new ca_acl();
		foreach($pa_user_ids as $vn_user_id) {
			if (!isset($va_current_users[$vn_user_id]) && $va_current_users[$vn_user_id]) { continue; }
			
			if ($t_acl->load(array('table_num' => $vn_table_num, 'row_id' => $vn_id, 'user_id' => $vn_user_id))) {
				$t_acl->delete(true);
				
				if ($t_acl->numErrors()) {
					$this->errors = $t_acl->errors;
					return false;
				}
			}
		}
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Removes all user-based ACL entries from currently loaded row
	 *
	 * @return bool True on success, false on failure
	 */ 
	public function removeAllACLUsers() {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			DELETE FROM ca_acl
			WHERE
				table_num = ? AND row_id = ? AND user_id IS NOT NULL
		", $this->tableNum(), $vn_id);
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors;
			return false;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------		
	/**
	 * 
	 */
	public function getACLGroupHTMLFormBundle($po_request, $ps_form_name, $pa_options=null) {
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');
		$t_group = new ca_user_groups();
		
		
		$o_view->setVar('t_instance', $this);
		$o_view->setVar('table_num', $pn_table_num);
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('request', $po_request);	
		$o_view->setVar('t_group', $t_group);
		$o_view->setVar('initialValues', $this->getACLUserGroups(array('returnAsInitialValuesForBundle' => true)));
		
		return $o_view->render('ca_acl_user_groups.php');
	}
	# ------------------------------------------------------------------
	/**
	 * Returns array of user group ACL entries associated with the currently loaded row. The array
	 * is key'ed on user group group_id; each value is an  array containing information about the group. Array keys are:
	 *			group_id		[group_id for group]
	 *			name			[name of group]
	 *			code			[short alphanumeric code identifying the group]
	 *			description		[text description of group]
	 *
	 * @param array $pa_options Supported options:
	 *		returnAsInitialValuesForBundle = if set array is returned suitable for use with the ACLUsers bundle; the array is key'ed by acl_id and includes the _display entry required by the bundle
	 *
	 * @return array List of user group ACL-entries associated with the currently loaded row
	 */ 
	public function getACLUserGroups($pa_options=null) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		if (!is_array($pa_options)) { $pa_options = []; }
		$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT g.*, acl.*
			FROM ca_acl acl
			INNER JOIN ca_user_groups AS g ON g.group_id = acl.group_id
			WHERE
				acl.table_num = ? AND acl.row_id = ? AND acl.group_id IS NOT NULL
		", $this->tableNum(), $vn_id);
		
		$va_groups = [];
		$va_group_ids = $qr_res->getAllFieldValues("group_id");
		
		if (($qr_groups = $this->makeSearchResult('ca_user_groups', $va_group_ids))) {
			$va_initial_values = caProcessRelationshipLookupLabel($qr_groups, new ca_user_groups(), array('stripTags' => true));
		} else {
			$va_initial_values = [];
		}
		
		$t_acl = new ca_acl();
		
		$qr_res->seek(0);
		while($qr_res->nextRow()) {
			$va_row = [];
			foreach(array('group_id', 'name', 'code', 'description', 'access') as $vs_f) {
				$va_row[$vs_f] = $qr_res->get($vs_f);
			}
			
			if ($vb_return_for_bundle) {
				$va_row['label'] = $va_initial_values[$va_row['group_id']]['label'];
				$va_row['id'] = $va_row['group_id'];
				$va_row['access_display'] = $t_acl->getChoiceListValue('access', $va_row['access']);
				
				$va_groups[(int)$qr_res->get('acl_id')] = $va_row;
			} else {
				$va_groups[(int)$qr_res->get('group_id')] = $va_row;
			}
		}
		
		return $va_groups;
	}
	# ------------------------------------------------------------------
	/**
	*
	*
	 * @param array $pa_group_ids
	 * @param array $pa_options Supported options are:
	 *		user_id - if set, only user groups owned by the specified user_id will be added
	 */ 
	public function addACLUserGroups($pa_group_ids, $pa_options=null) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		
		$vn_table_num = $this->tableNum();
		
		$vn_user_id = (isset($pa_options['user_id']) && $pa_options['user_id']) ? $pa_options['user_id'] : null;
		
		$va_current_groups = $this->getACLUserGroups();
		
		$t_acl = new ca_acl();
		foreach($pa_group_ids as $vn_group_id => $vn_access) {
			if ($vn_user_id) {	// verify that group we're linking to is owned by the current user
				$t_group = new ca_user_groups($vn_group_id);
				if (($t_group->get('user_id') != $vn_user_id) && $t_group->get('user_id')) { continue; }
			}
			$t_acl->clear();
			$t_acl->load(array('group_id' => $vn_group_id, 'table_num' => $vn_table_num, 'row_id' => $vn_id));		// try to load existing record
			
			$t_acl->set('table_num', $vn_table_num);
			$t_acl->set('row_id', $vn_id);
			$t_acl->set('group_id', $vn_group_id);
			$t_acl->set('access', $vn_access);
			
			if ($t_acl->getPrimaryKey()) {
				$t_acl->update();
			} else {
				$t_acl->insert();
			}
			
			if ($t_acl->numErrors()) {
				$this->errors = $t_acl->errors;
				return false;
			}
		}
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */ 
	public function setACLUserGroups($pa_group_ids, $pa_options=null) {
		if (is_array($va_groups = $this->getACLUserGroups())) {
			$this->removeAllACLUserGroups();
			if (!$this->addACLUserGroups($pa_group_ids, $pa_options)) { return false; }
			
			return true;
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 * 
	 */ 
	public function removeACLUserGroups($pa_group_ids) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		
		$vn_table_num = $this->tableNum();
		
		$va_current_groups = $this->getUserGroups();
		
		$t_acl = new ca_acl();
		foreach($pa_group_ids as $vn_group_id) {
			if (!isset($va_current_groups[$vn_group_id]) && $va_current_groups[$vn_group_id]) { continue; }
			
			if ($t_acl->load(array('table_num' => $vn_table_num, 'row_id' => $vn_id, 'group_id' => $vn_group_id))) {
				$t_acl->delete(true);
				
				if ($t_acl->numErrors()) {
					$this->errors = $t_acl->errors;
					return false;
				}
			}
		}
		
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Removes all user group-based ACL entries from currently loaded row
	 *
	 * @return bool True on success, false on failure
	 */ 
	public function removeAllACLUserGroups() {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			DELETE FROM ca_acl
			WHERE
				table_num = ? AND row_id = ? AND group_id IS NOT NULL
		", $this->tableNum(), (int)$vn_id);
		
		if ($o_db->numErrors()) {
			$this->errors = $o_db->errors;
			return false;
		}
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Returns an array containing the ACL world access setting for the currently load row. The array
	 * Array keys are:
	 *			access				[access level as integer value]
	 *			access_display		[access level as display text]
	 *
	 * @param array $pa_options Supported options:
	 *		No options currently supported
	 *
	 * @return array Information about current ACL world setting
	 */ 
	public function getACLWorldAccess($pa_options=null) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		if (!is_array($pa_options)) { $pa_options = []; }
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT acl.*
			FROM ca_acl acl
			WHERE
				acl.table_num = ? AND acl.row_id = ? AND acl.group_id IS NULL AND acl.user_id IS NULL
		", $this->tableNum(), $vn_id);
		
		$t_acl = new ca_acl();
		$va_row = [];
		if($qr_res->nextRow()) {
			foreach(array('access') as $vs_f) {
				$va_row[$vs_f] = $qr_res->get($vs_f);
			}
			$va_row['access_display'] = $t_acl->getChoiceListValue('access', $va_row['access']);
		}
		if (!strlen($va_row['access_display'])) {	// show default
			$va_row['access_display'] = $t_acl->getChoiceListValue('access', $this->getAppConfig()->get('default_item_access_level'));
		}
		
		return $va_row;
	}
	# --------------------------------------------------------------------------------------------		
	/**
	 * 
	 */
	public function getACLWorldHTMLFormBundle($po_request, $ps_form_name, $pa_options=null) {
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		$t_acl = new ca_acl();
		
		$vn_access = 0;
		if ($t_acl->load(array('group_id' => null, 'user_id' => null, 'table_num' => $this->tableNum(), 'row_id' => $this->getPrimaryKey()))) {		// try to load existing record
			$vn_access = $t_acl->get('access');
		} else {
			$vn_access = $this->getAppConfig()->get('default_item_access_level');
		}
		
		$o_view->setVar('t_instance', $this);
		$o_view->setVar('table_num', $pn_table_num);
		$o_view->setVar('id_prefix', $ps_form_name);		
		$o_view->setVar('request', $po_request);	
		$o_view->setVar('t_group', $t_group);
		$o_view->setVar('initialValue', $vn_access);
		
		return $o_view->render('ca_acl_world.php');
	}
	# --------------------------------------------------------------------------------------------		
	/**
	 * 
	 */
	public function setACLWorldAccess($pn_world_access) {
		if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
		
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		
		$vn_table_num = $this->tableNum();
		
		
		$t_acl = new ca_acl();	
		$t_acl->load(array('group_id' => null, 'user_id' => null, 'table_num' => $vn_table_num, 'row_id' => $vn_id));		// try to load existing record
		
		$t_acl->set('table_num', $vn_table_num);
		$t_acl->set('row_id', $vn_id);
		$t_acl->set('user_id', null);
		$t_acl->set('group_id', null);
		$t_acl->set('access', $pn_world_access);
		
		if ($t_acl->getPrimaryKey()) {
			$t_acl->update();
		} else {
			$t_acl->insert();
		}
		
		if ($t_acl->numErrors()) {
			$this->errors = $t_acl->errors;
			return false;
		}
		
		return true;
	}
	# --------------------------------------------------------------------------------------------		
	/**
	 * Checks access control list for currently loaded row for the specified user and returns an access value. Values are:
	 *
	 * __CA_ACL_NO_ACCESS__   (0)
	 * __CA_ACL_READONLY_ACCESS__ (1)
     * __CA_ACL_EDIT_ACCESS__ (2)
     * __CA_ACL_EDIT_DELETE_ACCESS__ (3)
	 *
	 * @param ca_users $t_user A ca_users object
	 * @param int $pn_id Optional row_id to check ACL for; if omitted currently loaded row_id is used
	 * @return int An access value 
	 */
	public function checkACLAccessForUser($t_user, $pn_id=null) {
		if (!$this->supportsACL()) { return __CA_ACL_EDIT_DELETE_ACCESS__; }
		if (!$pn_id) { 
			$pn_id = (int)$this->getPrimaryKey(); 
			if (!$pn_id) { return null; }
		}
		if ($t_user->canDoAction('is_administrator')) { return __CA_ACL_EDIT_DELETE_ACCESS__; }
		require_once(__CA_MODELS_DIR__.'/ca_acl.php');
		
		return ca_acl::accessForRow($t_user, $this->tableNum(), $pn_id);
	}
	# --------------------------------------------------------------------------------------------		
	/**
	 * Temporarily disable ACL item-based access control for this instance
	 *
	 * @return bool True if model supports ACL, false if not
	 */
	public function disableACL($disabled=true) {
		return $this->disable_acl = $disabled;
	}
	# --------------------------------------------------------------------------------------------		
	/**
	 * Checks if model supports ACL item-based access control
	 *
	 * @return bool True if model supports ACL, false if not
	 */
	public function supportsACL() {
		if($this->disable_acl) { return false; }
		if ($this->getAppConfig()->get($this->tableName().'_dont_do_item_level_access_control')) { return false; }
		return (bool)$this->getProperty('SUPPORTS_ACL');
	}
	# --------------------------------------------------------------------------------------------	
	/**
	 * Change type of record, removing any metadata that is invalid for the new type
	 *
     * @param mixed $pm_type The type_id or code to change the current type to
	 * @return bool True if change succeeded, false if error
	 */
	public function changeType($pm_type) {
		if (!$this->getPrimaryKey()) { return false; }					// row must be loaded
		if (!method_exists($this, 'getTypeID')) { return false; }		// model must be type-able
		
		unset($_REQUEST['form_timestamp']);
		
		if (!($vb_already_in_transaction = $this->inTransaction())) {
			$this->setTransaction($o_t = new Transaction($this->getDb()));
		}
		
		$vn_old_type_id = $this->getTypeID();
		$this->set($this->getTypeFieldName(), $pm_type, array('allowSettingOfTypeID' => true));
		
		// remove attributes that are not valid for new type
		$va_old_elements = $this->getApplicableElementCodes($vn_old_type_id);
		$va_new_elements = $this->getApplicableElementCodes($this->getTypeID());
		
		foreach($va_old_elements as $vn_old_element_id => $vs_old_element_code) {
			if (!isset($va_new_elements[$vn_old_element_id])) {
				$this->removeAttributes($vn_old_element_id, array('force' => true));
			}
		}
		
		if ($this->update(['queueIndexing' => true])) {
			if (!$vb_already_in_transaction) { $o_t->commit(); }
			return true;
		}
		
		if (!$vb_already_in_transaction) { $o_t->rollback(); }
		return false;
	}
	# --------------------------------------------------------------------------------------------	
	/**
	 * Set ID numbering field (aka. idno or idno_stub) to a value using a template based upon the 
	 * ID numbering configuration (typically multipart_id_numbering.conf for the MultipartIDNumbering system). 
	 * The template is simply a text value conforming to the configured format with any auto-generated SERIAL values 
	 * replaced with % characters. Eg. 2012.% will set the idno value for the current row to 2012.121 assuming that 121
	 * is the next number in the serial sequence.
	 *
	 * @param string $ps_template_value Template to use. If omitted or left blank the template defaults to a single "%" This is useful for the common case of assigning simple auto-incrementing integers as idno values.
	 * @param array $pa_options Options are:
	 *		dontSetValue = The template will be processed and the idno value generated but not actually set for the current row. Default is false.
	 *		serialOnly = Only set idno if the idno is configured to be SERIAL (auto-incrementing numbers). [Default is true]
	 * @return mixed The processed template value set as the idno, or false if the model doesn't support id numbering
	 */
	public function setIdnoWithTemplate($ps_template_value=null, $pa_options=null) {
		if (!$this->opo_idno_plugin_instance) {
			$this->loadIDNoPlugInInstance($pa_options);
		}
		if (($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) && $this->opo_idno_plugin_instance) {
			$pb_dont_set_value = (bool)(isset($pa_options['dontSetValue']) && $pa_options['dontSetValue']);
			$pb_serial_only = (bool)(isset($pa_options['serialOnly']) && $pa_options['serialOnly']);
			
			if ($pb_serial_only && !$this->opo_idno_plugin_instance->isSerialFormat()) {
				return false;
			}
		
			if (!$ps_template_value) {
				$ps_template_value = '%';
			}
		
			$this->opo_idno_plugin_instance->setDb($this->getDb());
			$vs_gen_idno = $this->opo_idno_plugin_instance->htmlFormValue($vs_idno_field, $ps_template_value);
			
			if (!$pb_dont_set_value) {
				$this->set($vs_idno_field, $vs_gen_idno);
			}
			
			return $vs_gen_idno;
		}
		return false;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Creates a relationship between the currently loaded row and the specified row.
	 *
	 * @param mixed $pm_rel_table_name_or_num Table name (eg. "ca_entities") or number as defined in datamodel.conf of table containing row to creation relationship to.
	 * @param int $pn_rel_id primary key value of row to creation relationship to.
	 * @param mixed $pm_type_id Relationship type type_code or type_id, as defined in the ca_relationship_types table. This is required for all relationships that use relationship types. This includes all of the most common types of relationships.
	 * @param string $ps_effective_date Optional date expression to qualify relation with. Any expression that the TimeExpressionParser can handle is supported here.
	 * @param string $ps_source_info Text field for storing information about source of relationship. Not currently used.
	 * @param string $ps_direction Optional direction specification for self-relationships (relationships linking two rows in the same table). Valid values are 'ltor' (left-to-right) and  'rtol' (right-to-left); the direction determines which "side" of the relationship the currently loaded row is on: 'ltor' puts the current row on the left
     * @param null $pn_rank
side. For many self-relations the direction determines the nature and display text for the relationship.
	 * @param array $pa_options Array of additional options:
	 *		allowDuplicates = if set to true, attempts to add a relationship that already exists will succeed. Default is false – duplicate relationships will not be created
	 *		setErrorOnDuplicate = if set to true, an error will be set if an attempt is made to add a duplicate relationship. Default is false – don't set error. addRelationship() will always return false when creation of a duplicate relationship fails, no matter how the setErrorOnDuplicate option is set.
	 * @return bool|BaseRelationshipModel Loaded relationship model instance on success, false on error.
	 */
	public function addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $ps_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {

		$this->opo_app_plugin_manager->hookAddRelationship(array(
			'table_name' => $this->tableName(), 
			'instance' => &$this,
			'related_table' => &$pm_rel_table_name_or_num,
			'rel_id' => &$pn_rel_id,
			'type_id' => &$pm_type_id,
			'edate' => &$ps_effective_date,
			'source_info' => &$ps_source_info,
			'direction' => &$ps_direction,
			'rank' => &$pn_rank,
			'options' => &$pa_options,
		));

		if(is_null($ps_effective_date) && is_array($pa_options) && is_array($pa_options['interstitialValues'])) {
			$ps_effective_date = caGetOption('effective_date', $pa_options['interstitialValues'], null);
			if(is_array($ps_effective_date)) { $ps_effective_date = $ps_effective_date['effective_date'] ?? null; }
			unset($pa_options['interstitialValues']['effective_date']);
		}

		if(is_null($ps_source_info) && is_array($pa_options) && is_array($pa_options['interstitialValues'])) {
			$ps_source_info = caGetOption('source_info', $pa_options['interstitialValues'], null);
			unset($pa_options['interstitialValues']['source_info']);
		}

		if ($t_rel = parent::addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id, $ps_effective_date, $ps_source_info, $ps_direction, $pn_rank, $pa_options)) {
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
			$this->_processInterstitials($pa_options, $t_rel, false);
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
		}
		return $t_rel;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Edits the data in an existing relationship between the currently loaded row and the specified row.
	 *
	 * @param mixed $pm_rel_table_name_or_num Table name (eg. "ca_entities") or number as defined in datamodel.conf of table containing row to create relationships to.
	 * @param int $pn_relation_id primary key value of the relation to edit.
	 * @param int $pn_rel_id primary key value of row to creation relationship to.
	 * @param mixed $pm_type_id Relationship type type_code or type_id, as defined in the ca_relationship_types table. This is required for all relationships that use relationship types. This includes all of the most common types of relationships.
	 * @param string $ps_effective_date Optional date expression to qualify relation with. Any expression that the TimeExpressionParser can handle is supported here.
	 * @param mixed $pa_source_info Array or text for storing information about source of relationship. Not currently used.
	 * @param string $ps_direction Optional direction specification for self-relationships (relationships linking two rows in the same table). Valid values are 'ltor' (left-to-right) and  'rtol' (right-to-left); the direction determines which "side" of the relationship the currently loaded row is on: 'ltor' puts the current row on the left side. For many self-relations the direction determines the nature and display text for the relationship.
	 * @param null|int $pn_rank
	 * @param array $pa_options Array of additional options:
	 *		allowDuplicates = if set to true, attempts to edit a relationship to match one that already exists will succeed. Default is false – duplicate relationships will not be created.
	 *		setErrorOnDuplicate = if set to true, an error will be set if an attempt is made to create a duplicate relationship. Default is false – don't set error. editRelationship() will always return false when editing of a relationship fails, no matter how the setErrorOnDuplicate option is set.
	 * @return BaseRelationshipModel Loaded relationship model instance on success, false on error.
	 */
	public function editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $pa_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {
		if ($t_rel = parent::editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id, $ps_effective_date, $pa_source_info, $ps_direction, $pn_rank, $pa_options)) {
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
			$this->_processInterstitials($pa_options, $t_rel, true);
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
		}
		return $t_rel;
	}
	# --------------------------------------------------------------------------------------------
	public function moveRelationships($pm_rel_table_name_or_num, $pn_to_id, $pa_options=null) {
		$vb_we_set_transaction = false;

		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		}

		$this->opo_app_plugin_manager->hookBeforeMoveRelationships(array(
			'table_name' => $this->tableName(),
			'instance' => &$this,
			'related_table' => &$pm_rel_table_name_or_num,
			'to_id' => &$pn_to_id,
			'options' => &$pa_options,
		));

		$vn_rc = parent::moveRelationships($pm_rel_table_name_or_num, $pn_to_id, $pa_options=null);

		$this->opo_app_plugin_manager->hookAfterMoveRelationships(array(
			'table_name' => $this->tableName(),
			'instance' => &$this,
			'related_table' => &$pm_rel_table_name_or_num,
			'to_id' => &$pn_to_id,
			'options' => &$pa_options,
		));

		if ($this->numErrors()) {
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			return false;
		} else {
			if ($vb_we_set_transaction) { $this->removeTransaction(true); }
		}

		return $vn_rc;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Return array containing information about all hierarchies, including their root_id's
	 * For non-adhoc hierarchies such as places, this call returns the contents of the place_hierarchies list
	 * with some extra information such as the # of top-level items in each hierarchy.
	 *
	 * For an ad-hoc hierarchy like that of an collection, there is only ever one hierarchy to display - that of the current collection.
	 * So for adhoc hierarchies we just return a single entry corresponding to the root of the current collection hierarchy
	 */
	 public function getHierarchyList($pb_dummy=false) {
		$vs_hier_fld = $this->getProperty('HIERARCHY_ID_FLD');
		$vs_parent_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD');
		if (!$vs_parent_fld) { return; }
		
	 
	 	$vs_pk = $this->primaryKey();
	 	$vn_id = $this->getPrimaryKey();
	 	$vs_table = $this->tableName();
	 	$vs_template = $this->getAppConfig()->get($vs_table.'_hierarchy_browser_display_settings');
	 	
	 	$va_type_ids = caMergeTypeRestrictionLists($this, []);
	 	$va_source_ids = caMergeSourceRestrictionLists($this, []);
	 	
	 	
	 	
		$vs_type_fld = $this->getTypeFieldName();
		$vs_source_fld = $this->getSourceFieldName();
	 		
	 	if (!$vn_id) { 
	 		$o_db = $this->getDb();
	 		
	 		$va_wheres = array("(o.{$vs_parent_fld} IS NULL)");
	 		$va_params = [];
	 		if (is_array($va_type_ids) && sizeof($va_type_ids)) {
	 			$va_params[] = $va_type_ids;
	 			$va_wheres[] = "(o.{$vs_type_fld} IN (?)".($this->getFieldInfo($vs_type_fld, 'IS_NULL') ? " OR o.{$vs_type_fld} IS NULL" : "").")";
	 		}
	 		if (is_array($va_source_ids) && sizeof($va_source_ids)) {
	 			$va_params[] = $va_source_ids;
	 			$va_wheres[] = "(o.{$vs_source_fld} IN (?))";
	 		}
			$qr_res = $o_db->query("
				SELECT o.{$vs_pk}, count(*) c
				FROM {$vs_table} o
				LEFT JOIN {$vs_table} AS p ON o.{$vs_pk} = p.{$vs_parent_fld} 
				WHERE ".(join(" AND ", $va_wheres))."
				GROUP BY o.{$vs_pk}
			", $va_params);
			
			$qr_childless = $o_db->query("
				SELECT o.{$vs_pk}
				FROM {$vs_table} o
				LEFT JOIN {$vs_table} AS p ON o.{$vs_pk} = p.{$vs_parent_fld} 
				WHERE ".(join(" AND ", $va_wheres))." AND p.{$vs_pk} is NULL
				GROUP BY o.{$vs_pk}
			", $va_params);
			$childless_ids = $qr_childless->getAllFieldValues($vs_pk);
	 		$va_hiers = [];
	 		
	 		$va_ids = $qr_res->getAllFieldValues($vs_pk);
	 		$qr_res->seek(0);
	 		$va_labels = $this->getPreferredDisplayLabelsForIDs($va_ids);
	 		while($qr_res->nextRow()) {
	 			$va_hiers[$vn_id = $qr_res->get($vs_pk)] = array(
	 				'item_id' => $vn_id,
	 				$vs_pk => $vn_id,
	 				'name' => caProcessTemplateForIDs($vs_template, $vs_table, array($vn_id)),
	 				'hierarchy_id' => $vn_id,
	 				'children' => in_array($vn_id, $childless_ids) ? 0 : (int)$qr_res->get('c')
	 			);
	 		}
	 		
	 		return $va_hiers;
	 	} else {
	 		// return specific collection as root of hierarchy
			$vs_label = $this->getLabelForDisplay(false);
			$vn_hier_id = $this->get($vs_hier_fld);
			
			if ($this->get($vs_parent_fld)) { 
				// currently loaded row is not the root so get the root
				$va_ancestors = $this->getHierarchyAncestors();
				if (!is_array($va_ancestors) || sizeof($va_ancestors) == 0) { return null; }
				$t_instance = Datamodel::getInstanceByTableName($this->tableName(), true);
				$t_instance->load($va_ancestors[0]["NODE"][$this->primaryKey()]);
			} else {
				$t_instance =& $this;
			}
			
			$va_children = $t_instance->getHierarchyChildren(null, array('idsOnly' => true));
			$va_hierarchy_root = array(
				$t_instance->get($vs_hier_fld) => array(
					'item_id' => $vn_id,
					$vs_pk => $vn_id,
					'name' => caProcessTemplateForIDs($vs_template, $vs_table, array($vn_id)),
					'hierarchy_id' => $vn_hier_id,
					'children' => sizeof($va_children)
				)
			);
				
	 		return $va_hierarchy_root;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns name of hierarchy for currently loaded row or, if specified, row identified by optional $pn_id parameter
	 */
	 public function getHierarchyName($pn_id=null) {
	 	if (!$pn_id) { $pn_id = $this->getPrimaryKey(); }
	 	
	 	$t_instance = Datamodel::getInstanceByTableName($this->tableName(), true);
	 	
		$va_ancestors = $this->getHierarchyAncestors($pn_id, array('idsOnly' => true));
		if (is_array($va_ancestors) && sizeof($va_ancestors)) {
			$vn_parent_id = array_pop($va_ancestors);
			$t_instance->load($vn_parent_id);
			return $t_instance->getLabelForDisplay(false);
		} else {			
			if ($pn_id == $this->getPrimaryKey()) {
				return $this->getLabelForDisplay(true);
			} else {
				$t_instance->load($pn_id);
				return $t_instance->getLabelForDisplay(false);
			}
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * @param array $pa_options
	 * @param $t_rel
	 * @param bool $pb_update
	 */
	private function _processInterstitials($pa_options, $t_rel, $pb_update) {
		global $g_ui_locale_id;
		// Are there interstitials to add?
		if (isset($pa_options['interstitialValues']) && is_array($pa_options['interstitialValues'])) {
			foreach ($pa_options['interstitialValues'] as $vs_element => $va_value) {
				if ($t_rel->hasField($vs_element)) {
					if(is_array($va_value)) { $va_value = $va_value[$vs_element] ?? null; }
					$t_rel->set($vs_element, $va_value);
					continue;
				}
				// Convert a scalar or key-value array to an indexed array with a single element
				if (!is_array($va_value) || array_keys($va_value) !== range(0, sizeof($va_value) - 1)) {
					$va_value = array($va_value);
				}
				// Iterate through indexed array
				foreach ($va_value as $va_value_instance) {
					// Convert scalar to key-value array
					if (!is_array($va_value_instance)) {
						$va_value_instance = array($vs_element => $va_value_instance);
					}
					// Ensure we have a locale
					if (!isset($va_value_instance['locale_id'])) {
						$va_value_instance['locale_id'] = $g_ui_locale_id ? $g_ui_locale_id : ca_locales::getDefaultCataloguingLocaleID();
					}
					
					$opts = [];
					if($source_value = caGetOption('_source', $va_value_instance, null)) {
						unset($va_value_instance['_source']);
						$opts['source'] = $source_value;
					}
					// Create or update the attribute
					if ($pb_update) {
						$t_rel->editAttribute($va_value_instance, $vs_element, null, $opts);
					} else {
						$t_rel->addAttribute($va_value_instance, $vs_element, null, $opts);
					}
				}
			}
			$t_rel->update();
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Fetch metadata dictionary rule violations for this instance and (optionally) a given bundle
	 * @param null|string $ps_bundle_name
	 * @param array $options Options include:
	 *		limitToShowAsPrompt = 
	 *		screen_id = 
	 *		placement_id = 
	 *
	 * @return array|null
	 */
	public function getMetadataDictionaryRuleViolations($ps_bundle_name=null, $options=null) {
	 	if (!($vn_id = $this->getPrimaryKey())) { return null; }
	 	
	 	$limit_to_show_as_prompt = caGetOption('limitToShowAsPrompt', $options, false);
	 	
	 	$bundles_on_screen = null;
	 	if (($screen_id = caGetOption('screen_id', $options, null)) || ($placement_id = caGetOption('placement_id', $options, null))) {
	 		$t_screen = Datamodel::getInstance('ca_editor_ui_screens', true);
	 		if (is_array($screen_placements = $t_screen->getPlacements($placement_id ? ['placement_id' => $placement_id] : ['screen_id' => $screen_id]))) {
	 			$bundles_on_screen = array_map(function($v) { return preg_replace("!^ca_attribute_!", "", $v['bundle_name']); }, $screen_placements);
	 		}
	 	} 
	 	$o_db = $this->getDb();
	 	
	 	$va_sql_params = array($vn_id, $this->tableNum());
	 	$vs_bundle_sql = '';
	 	
	 	if ($ps_bundle_name = str_replace("ca_attribute_", "", $ps_bundle_name)) {	 
	 	    if(!Datamodel::tableExists($ps_bundle_name) && (!preg_match('!^'.$this->tableName().'.!', $ps_bundle_name))) {
                $ps_bundle_name = $this->tableName().".{$ps_bundle_name}";
            }
			$vs_bundle_sql = "AND cmde.bundle_name = ?";
			$va_sql_params[] = $ps_bundle_name;
	 	} 
	 	
	 	
	 	$qr_res = $o_db->query("
	 		SELECT 
	 		    cmdr.rule_code, cmdr.rule_id,
	 		    cmdrv.violation_id, cmdr.rule_level,
	 		    cmdrv.created_on, cmdrv.last_checked_on, cmde.bundle_name, cmde.settings
	 		FROM ca_metadata_dictionary_rule_violations cmdrv
	 		INNER JOIN ca_metadata_dictionary_rules AS cmdr ON cmdr.rule_id = cmdrv.rule_id
	 		INNER JOIN ca_metadata_dictionary_entries AS cmde ON cmde.entry_id = cmdr.entry_id
	 		WHERE
	 			cmdrv.row_id = ? AND cmdrv.table_num = ? {$vs_bundle_sql}
	 	", $va_sql_params);
	 	
	 	$va_violations = $va_rule_instances = [];
	 	while($qr_res->nextRow()) {
	 		$bundle_name = $qr_res->get('bundle_name'); 
	 		$bundle_elements = explode('.', $bundle_name);
	 		$rule_settings = caUnserializeForDatabase($qr_res->get('settings'));
	 		
			$vn_violation_id = $qr_res->get('violation_id');
	 		$vn_rule_id = $qr_res->get('rule_id');
	 		
	 		$t_rule = (isset($va_rule_instances[$vn_rule_id])) ? $va_rule_instances[$vn_rule_id] : ($va_rule_instances[$vn_rule_id] = new ca_metadata_dictionary_rules($vn_rule_id));
	 		if (!$t_rule || !$t_rule->getPrimaryKey()) { continue; }
	 		
	 		$show_as_prompt = $t_rule->getSetting('showasprompt');
	 		
	 		if ($limit_to_show_as_prompt && !$show_as_prompt) { continue; }
	 		
	 		if (is_array($bundles_on_screen)) {
	 			if (!in_array(join(".", $bundle_elements), $bundles_on_screen)) {
	 				if (sizeof($bundle_elements) > 1) { array_shift($bundle_elements); }
	 				if (is_array($bundles_on_screen) && !in_array(join(".", $bundle_elements), $bundles_on_screen)) { continue; }
	 			}
	 			
	 			$p = join(".", $bundle_elements);
	 				 			
	 			$rule_settings_restrict_to_types = array_filter(caGetOption(['restrict_to_types', 'restrictToTypes'], $rule_settings, []), function($v) { return strlen($v); });
	 			$rule_settings_restrict_to_relationship_types = array_filter(caGetOption(['restrict_to_relationship_types', 'restrictToRelationshipTypes'], $rule_settings, []), function($v) { return strlen($v); });
	 			
	 			foreach($bundles_on_screen as $placement_id => $placement_bundle_name) {
	 				if ($placement_bundle_name == $p) {
	 					// Compare bundle type and relationship type restrictions; only show errors when the are set and equal
	 					$placement_restrict_to_types = array_filter(caGetOption(['restrict_to_types', 'restrictToTypes'], $screen_placements[$placement_id]['settings'], []), function($v) { return strlen($v); });
	 					$placement_restrict_to_relationship_types = array_filter(caGetOption(['restrict_to_relationship_types', 'restrictToRelationshipTypes'], $screen_placements[$placement_id]['settings'], []), function($v) { return strlen($v); });
	 					
	 					if (sizeof($placement_restrict_to_types) && !sizeof(array_intersect($placement_restrict_to_types, $rule_settings_restrict_to_types))) {
	 						continue;
	 					}
	 						
	 					if (sizeof($placement_restrict_to_relationship_types) && !sizeof(array_intersect($placement_restrict_to_relationship_types, $rule_settings_restrict_to_relationship_types))) {
	 						continue;
	 					}
	 					$va_violations[$vn_violation_id] = array(
							'violation_id' => $vn_violation_id,
							'bundle_name' => $bundle_name,
							'placement_code' => $screen_placements[$placement_id]['placement_code'],
							'label' => $t_rule->getSetting('label', ['flatten' => true]),
							'violationMessage' => $t_rule->getSetting('violationMessage', ['flatten' => true]),
							'code' => $qr_res->get('rule_code'),
							'level' => $vs_level = $qr_res->get('rule_level'),
							'levelDisplay' => $t_rule->getChoiceListValue('rule_level', $vs_level),
							'description' => $t_rule->getSetting('description', ['flatten' => true]),
							'showasprompt' => $show_as_prompt,
							'created_on' => $qr_res->get('created_on'),
							'last_checked_on' => $qr_res->get('last_checked_on')
						);
	 				}
	 			}
	 		} else {
	 			$va_violations[$vn_violation_id] = array(
	 				'violation_id' => $vn_violation_id,
	 				'bundle_name' => $bundle_name,
	 				'label' => $t_rule->getSetting('label', ['flatten' => true]),
	 				'violationMessage' => $t_rule->getSetting('violationMessage', ['flatten' => true]),
	 				'code' => $qr_res->get('rule_code'),
	 				'level' => $vs_level = $qr_res->get('rule_level'),
	 				'levelDisplay' => $t_rule->getChoiceListValue('rule_level', $vs_level),
	 				'description' => $t_rule->getSetting('description', ['flatten' => true]),
	 				'showasprompt' => $show_as_prompt,
	 				'created_on' => $qr_res->get('created_on'),
	 				'last_checked_on' => $qr_res->get('last_checked_on')
	 			);
	 		}
	 	}
	 	
	 	return $va_violations;
	 }
	 # --------------------------------------------------------------------------------------------
	/**
	 * 
	 */
	 public function validateUsingMetadataDictionaryRules($pa_options=null) {
	 	if(!$this->getPrimaryKey()) { return null; }
			
		$t_violation = new ca_metadata_dictionary_rule_violations();
		if ($this->inTransaction()) { $t_violation->setTransaction($this->getTransaction()); }
		
		$va_rules = ca_metadata_dictionary_rules::getRules(array('db' => $o_db, 'table' => $this->tableNum(), 'bundles' => caGetOption('bundles', $pa_options, null)));
		$vn_violation_count = 0;
		$va_violations = [];
		foreach($va_rules as $va_rule) {
			$va_expression_tags = caGetTemplateTags($va_rule['expression']);
		
			$t_violation->clear();
			
			$vb_skip = !$this->hasBundle($va_rule['bundle_name'], $this->getTypeID());
				
			
			if (!$vb_skip) {
				// create array of values present in rule
				$va_row = array($va_rule['bundle_name'] => $vs_val = $this->get($va_rule['bundle_name']));
				foreach($va_expression_tags as $vs_tag) {
					$t = caParseTagOptions($vs_tag);
					$va_row[$vs_tag] = $this->get($t['tag'], $t['options']);
				}
			}
			
			// is there a violation recorded for this rule and row?
			if ($t_found = ca_metadata_dictionary_rule_violations::find(array('rule_id' => $va_rule['rule_id'], 'row_id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum()), array('returnAs' => 'firstModelInstance', 'transaction' => $this->getTransaction()))) {
				$t_violation = $t_found;
			}
					
			if (!$vb_skip && ExpressionParser::evaluate(html_entity_decode($va_rule['expression']), $va_row)) {
				// violation
				if ($t_violation->getPrimaryKey()) {
					$t_violation->update();
				} else {
					$t_violation->set('rule_id', $va_rule['rule_id']);
					$t_violation->set('table_num', $this->tableNum());
					$t_violation->set('row_id', $this->getPrimaryKey());
					$t_violation->insert();
				}
				
				$va_violations[$va_rule['rule_level']][$va_rule['bundle_name']][] = $va_rule;
				$vn_violation_count++;
			} else {
				if ($t_violation->getPrimaryKey()) {
					$t_violation->delete(true);		// remove violation
				}
			}
		}
		
		return $va_violations;
	 }
	# --------------------------------------------------------------------------------------------
	/**
	 * Trigger metadata alerts if there are any set up
	 * @param array $pa_options
	 */
	public function triggerMetadataAlerts(array $pa_options=[]) {
		ca_metadata_alert_triggers::fireApplicableTriggers($this, __CA_MD_ALERT_CHECK_TYPE_SAVE__);
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Method calls by SearchResult::get() on models when bundle to fetch is not an intrinsic but is listed in the 
	 * models bundle list. This is typically employed to let the model render bundle data in a custom manner.
	 *
	 * This method implementation is just a stub and always returns null. Models implementing custom rendering will override this method.
	 *
	 * @param string $ps_bundle_name Name of bundle
	 * @param int $pn_row_id The primary key of the row from which the bundle is being rendered
	 * @param array $pa_values The row value array
	 * @param array $pa_options Options passed to SearchResult::get()
	 *
	 * @return null
	 */
	public function renderBundleForDisplay($ps_bundle_name, $pn_row_id, $pa_values, $pa_options=null) {
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Get cached ca_locales instance. Instance is shared across all models.
	 */
	public function getLocaleInstance() {
		if (!BundlableLabelableBaseModelWithAttributes::$s_locales) {
			BundlableLabelableBaseModelWithAttributes::$s_locales = new ca_locales();
		}
		return BundlableLabelableBaseModelWithAttributes::$s_locales;
	}
	# ------------------------------------------------------
	/**
	 * Get cached time expression parser instance. Instance is shared across all models.
	 */
	public function getTimeExpressionParser() {
		if (!BundlableLabelableBaseModelWithAttributes::$s_tep) {
			BundlableLabelableBaseModelWithAttributes::$s_tep = new TimeExpressionParser();
		}
		return BundlableLabelableBaseModelWithAttributes::$s_tep;
	}
	# ------------------------------------------------------
	# Bundles
	# ------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of tags in the currently loaded record
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getItemTagHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null, $pa_bundle_settings=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_subject', $this);		
		$o_view->setVar('id_prefix', $ps_form_name);	
		$o_view->setVar('placement_code', $ps_placement_code);		
		$o_view->setVar('request', $po_request);
		$o_view->setVar('batch', caGetOption('batch', $pa_options, false));
		
		$initial_values = [];
		foreach(($this->getPrimaryKey() ? $this->getTags() : []) as $v) {
			$initial_values[$v['relation_id']] = $v;
		}
		
		$o_view->setVar('initialValues', $initial_values);
		$o_view->setVar('settings', $pa_bundle_settings);
		
		
		$o_view->setVar('lookup_urls', caJSONLookupServiceUrl($po_request, Datamodel::getTableName($this->get('table_num'))));
		
		return $o_view->render('ca_item_tags.php');
	}
	# -------------------------------------------------------
	/**
	 * Renders and returns HTML form bundle for management of user comments in the currently loaded record
	 * 
	 * @param object $po_request The current request object
	 * @param string $ps_form_name The name of the form in which the bundle will be rendered
	 *
	 * @return string Rendered HTML bundle for display
	 */
	public function getItemCommentHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_options=null, $pa_bundle_settings=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
		$o_view->setVar('t_subject', $this);		
		$o_view->setVar('id_prefix', $ps_form_name);	
		$o_view->setVar('placement_code', $ps_placement_code);		
		$o_view->setVar('request', $po_request);
		$o_view->setVar('batch', caGetOption('batch', $pa_options, false));
		
		$initial_values = [];
		foreach(($this->getPrimaryKey() ? $this->getComments(null, null, ['request' => $po_request, 'sortDirection' => $pa_bundle_settings['sortDirection']]) : []) as $v) {
			$initial_values[$v['comment_id']] = $v;
		}
		
		$o_view->setVar('initialValues', $initial_values);
		$o_view->setVar('settings', $pa_bundle_settings);
		
		
		$o_view->setVar('lookup_urls', caJSONLookupServiceUrl($po_request, Datamodel::getTableName($this->get('table_num'))));
		
		return $o_view->render('ca_item_comments.php');
	}
	# -------------------------------------------------------
	/**
	 * Merge records into single "most complete" record using heuristic. Given a list of two or more row_ids 
	 * the record with the most content (by length) will be selected as the merge target, and all other will be merged
	 * to it. If the useID option is set to one of the ids in the ids list, it will be used as the merge target regardless of 
	 * content.
	 *
	 * @param array $ids
	 * @param array $options Options include:
	 *		useID = id of record to use as merge target. If omitted the record with the most content by length will be used. [Default is null]
	 * 		merge = list of data categories to merge. Valid values include 'preferred_labels', 'nonpreferred_labels', 'intrinisics', 'attributes', 'relationships'. If omitted all categories are merged. [Default is null]
	 *		preferredLabelsMode = Merge mode for preferred labels. [Default is "longest"]
	 *		intrinsicMode = Merge mode for intrinisic values. [Default is "whenNotSet"]
	 *
	 * @return BundlableLabelableBaseModelWithAttributes Instance of merged record
	 *
	 * @throws MergeException
	 */
	static public function merge(array $ids, ?array $options=null) : ?BundlableLabelableBaseModelWithAttributes {
		$notification = caGetOption('notification', $options, null);
		
		$table = get_called_class();
		if(sizeof($ids) < 2) { 
			throw new MergeException(_t('Must specify at least two ids'));
		}
		
		$merge_opts = ['preferred_labels', 'nonpreferred_labels', 'intrinsics', 'attributes', 'relationships'];
		$merge = caGetOption('merge', $options, null);
		if(is_array($merge)) {
			$merge = array_filter(array_map(function($v) { return strtolower($v); }, $merge), function($v) use ($merge_opts) { return in_array($v, $merge_opts); });
		}
		if(!is_array($merge) || !sizeof($merge)) { $merge = $merge_opts; }
		
		// verify ids exist
		$instances = [];
		foreach($ids as $id) {
			if($t_instance = Datamodel::getInstance($table, false, $id)) {
				$instances[$id] = $t_instance;
			}
		}
		
		if(sizeof($instances) < 2) { 
			throw new MergeException(_t('Must specify at least two valid ids'));
		}
		
		$use_preferred_id = caGetOption('useID', $options, null);
		$rel_tables = self::_relationshipTablesToMerge($options);
		
		$content = [];
		
		$use_id = $use_preferred_id;
		$max_count = 0;
		foreach($instances as $id => $t_instance) {
			// Labels
			$content[$id]['pref_labels'] = $pref_labels = $t_instance->get("{$table}.preferred_labels", ['returnWithStructure' => true]);
			$content[$id]['npref_labels'] = $npref_labels = $t_instance->get("{$table}.nonpreferred_labels", ['returnWithStructure' => true]);
			
			// Intrinsics
			$intrinsics = [];
			foreach($t_instance->getFormFields() as $f => $finfo) {
				if(strlen($v = $t_instance->get("{$table}.{$f}"))) {
					$intrinsics[$f] = $v;
				}
			}
			$content[$id]['intrinsics'] = $intrinsics;
			
			// Metadata elements
			$attributes = [];
			foreach($t_instance->getAttributes() as $attr) {
				$attributes[$attr->getElementID()][] = $attr;
			}
			$content[$id]['attributes'] = $attributes;
			
			// Relationships
			$relationships = [];
			$rel_count = 0;
			foreach($rel_tables as $rel_table) {
				$rel_ids = $t_instance->get("{$rel_table}.related.".Datamodel::primaryKey($rel_table), ['returnAsArray' => true]);
				if(!is_array($rel_ids) || !sizeof($rel_ids)) { continue; }
				$relationships[$rel_table] = $rel_ids;
				$rel_count += sizeof($rel_ids);
			}
			$content[$id]['relationships'] = $relationships;
			
			if(!$use_preferred_id) {
				// which record has the most fields?
				$count = sizeof($pref_labels) + sizeof($npref_labels) + sizeof($intrinsics) + sizeof($attributes) + $rel_count;
				if ($count > $max_count) { 
					$max_count = $count;
					$use_id = $id;
				}	
			}		
		}
		if(!$use_id) {
			$use_id = array_shift($ids);
		}
		if(!($t_base = Datamodel::getInstance($table, false, $use_id))) {
			throw new MergeException(_t("Could not load base record (id=%1) for merge", $use_id));
		}
		$base_content = $content[$use_id];
		unset($content[$use_id]);
		
		$t_base->setTransaction($trans = new Transaction());
		
		// Perform merge
		try {
			// 1. Preferred label?
			if(in_array('preferred_labels', $merge, true)) {
				self::_mergePrefLabels($t_base, $base_content['pref_labels'], array_map(function($v) { return $v['pref_labels']; }, $content), $options);
			}
			// 2. Nonpreferred labels?
			if(in_array('nonpreferred_labels', $merge, true)) {
				self::_mergeNPrefLabels($t_base, $base_content['npref_labels'], array_map(function($v) { return $v['npref_labels']; }, $content), $options);
			}
		
			// 3. Intrinsics
			if(in_array('intrinsics', $merge, true)) {
				self::_mergeIntrinsics($t_base, $base_content['intrinsics'], array_map(function($v) { return $v['intrinsics']; }, $content), $options);
			}
		
			// 4. Attributes
			if(in_array('attributes', $merge, true)) {
				self::_mergeAttributes($t_base, $base_content['attributes'], array_map(function($v) { return $v['attributes']; }, $content), $options);
			}
		
			if($notification && (sizeof(array_intersect($merge, ['perferred_labels', 'nonpreferred_labels', 'intrinsics', 'attributes'])) > 0)) {
				$notification->addNotification(_t("Merged metadata from %1 %2 to <em>%3</em> (%3)", sizeof($ids), $t_base->getProperty('NAME_PLURAL'), $t_base->getLabelForDisplay()), __NOTIFICATION_TYPE_INFO__);
			}
		
			// 5. Relationships
			if(in_array('relationships', $merge, true)) {
				self::_mergeRelationships($t_base, $ids, $options);
			}
		
			// Delete now-merged records
			foreach($ids as $id) {
				if($id == $use_id) { continue; }
				$t_instance = Datamodel::getInstance($table, false, $id);
				$t_instance->setTransaction($trans);
				if(!$t_instance->delete(true)) {
					throw new MergeException(_t("Could not delete old record: %1", join("; ", $t_instance->getErrors())));
				}
			}
		} catch (MergeException $e) {
			$trans->rollback();
			throw $e;
		}
		$trans->commit();
		return $t_base;
	}
	# -------------------------------------------------------
	/**
	 * Merge preferred labels from several rows
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_base Instance to merge preferred labels into
	 * @param array $base An array of preferred label data from the base instance to merge labels into 
	 * @param array $label_list An array of arrays, each containing label data from a record to merge
	 * @param array $options Options include:
	 *		notification = A NotificationManager instance to post notifications regarding the merge to. [Default is null]
	 *		mode = Merge mode to use: "merged" to always use labels from a merged record when defined for a locale; "base" to always preserve labels from the base record (the record into which data is being merged); "longest" to use the longest label defined for the locale. [Default is "longest"]
	 *		preferredLabelsMode = Synonym for "mode" option
	 *
	 * @return void
	 * @throws MergeException
	 */
	static private function _mergePrefLabels($t_base, $base, $label_list, $options=null) : void {
		$notification = caGetOption('notification', $options, null);
		$table = $t_base->tableName();
		$label_fields = $t_base->getLabelUIFields();
	
		$mode = caGetOption(['mode', 'preferredLabelsMode'], $options, null);
		
		$labels_by_locale = [];
		
		$max_lengths_by_locale_id = [];
		$use_label_by_locale_id = [];
		$use_label_id_by_locale_id = [];
		
		foreach(array_merge([$base], $label_list) as $labels_by_row) {
			foreach($labels_by_row as $labels) {
				foreach($labels as $label_id => $label) {
					$locale_id = $label['locale_id'];
					
					switch(strtolower($mode)) {
						case 'merged':
							// Always use label from base, if set for the locale
							if(!in_array($label_id, array_keys($base))) {
								$use_label_by_locale_id[$locale_id] = array_filter($label, function($k) use ($label_fields) { return in_array($k, $label_fields); }, ARRAY_FILTER_USE_KEY);
								break(2);
							}
						case 'base':
							// Always use label from base, if set for the locale
							foreach(reset($base) as $blabel) {
								if($label['locale_id'] == $blabel['locale_id']) {
									$use_label_by_locale_id[$locale_id] = array_filter($blabel, function($k) use ($label_fields) { return in_array($k, $label_fields); }, ARRAY_FILTER_USE_KEY);
									break(2);
								}
							}
						case 'longest':
						default:
							// Use longest available label 
							$total_length = 0;
							foreach($label_fields as $f) {
								$total_length =+ mb_strlen($label[$f]);
							}
							if($total_length > (int)$max_lengths_by_locale_id[$locale_id]) { 
								$max_lengths_by_locale_id[$locale_id] = $total_length;
					
								$use_label_by_locale_id[$locale_id] = array_filter($label, function($k) use ($label_fields) { return in_array($k, $label_fields); }, ARRAY_FILTER_USE_KEY);
							}
							break;
					}
				}
			}
		}
		
		foreach($use_label_by_locale_id as $locale_id => $label) {
			foreach($base as $row_id => $blabels) {
				foreach($blabels as $blabel_id => $blabel) {
					if($label['label_id'] === $blabel_id) {
						unset($use_label_by_locale_id[$locale_id]);
						break(2);
					}
					
					if(
						($label['locale_id'] === $blabel['locale_id'])
					) {
						$labels_are_equal = true;
						foreach($label_fields as $f) {
							if($label[$f] !== $blabel[$f]) {
							    $labels_are_equal = false;
								break;
							}
						}
						if($labels_are_equal) {
							unset($use_label_by_locale_id[$locale_id]);
							break(2);
						}
					}
				}
			}
		}
				
		if(sizeof($use_label_by_locale_id) > 0) {
			// rewrite labels
			$t_base->removeAllLabels(__CA_LABEL_TYPE_PREFERRED__);
			foreach($use_label_by_locale_id as $locale_id => $label) {
				if(!$t_base->addLabel($label, $locale_id, null, true)) {
					throw new MergeException(_t("Could not add merged preferred label: %1", join('; ', $t_base->getErrors())));
				}
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Merge nonpreferred labels from several rows
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_base Instance to merge relationships into
	 * @param array $row_ids A list of record ids to merge into the base record
	 * @param array $options Options include:
	 *		notification = A NotificationManager instance to post notifications regarding the merge to. [Default is null]s
	 *
	 * @return void
	 * @throws MergeException
	 */
	static private function _mergeNPrefLabels($t_base, $base, $label_list, $options=null) : void {
		$notification = caGetOption('notification', $options, null);
		$table = $t_base->tableName();
		$label_fields = $t_base->getLabelUIFields();
		
		$labels_by_locale_id = [];
		foreach(array_merge([$base], $label_list) as $labels_by_row) {
			foreach($labels_by_row as $row_id => $labels) {
				foreach($labels as $label_id => $label) {
					$locale_id = $label['locale_id'];
					$label_exists = false;
					if(!is_array($labels_by_locale_id[$locale_id])) { 
						$labels_by_locale_id[$locale_id] = []; 
					} else {
						foreach($labels_by_locale_id[$locale_id] as $elabel) {
							$labels_are_equal = true;
							foreach($label_fields as $f) {
								if($label[$f] !== $elabel[$f]) {
									$labels_are_equal = false;
									break;
								}
							}
							if($labels_are_equal) { $label_exists = true; break; }
						}
					}
					
					if(!$label_exists) {
						$labels_by_locale_id[$locale_id][] = array_filter($label, function($k) use ($label_fields) { return (in_array($k, $label_fields) || ($k === 'type_id')); }, ARRAY_FILTER_USE_KEY);
					}
				}
			}
		}
		
		$t_base->removeAllLabels(__CA_LABEL_TYPE_NONPREFERRED__);
		foreach($labels_by_locale_id as $locale_id => $labels) {
			foreach($labels as $label) {
				if(!$t_base->addLabel($label, $locale_id, $label['type_id'], false)) {
					throw new MergeException(_t("Could not add merged non-preferred label: %1", join('; ', $t_base->getErrors())));
				}
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Merge intrinsic fields from several rows
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_base Instance to merge relationships into
	 * @param array $row_ids A list of record ids to merge into the base record
	 * @param array $options Options include:
	 *		notification = A NotificationManager instance to post notifications regarding the merge to. [Default is null]
	 *		mode = Merge mode to use: "merged" to always use values from a merged record; "base" to always preserve labels from the base record (the record into which data is being merged); "whenNotSet" to use merged value when one isn't already set in the base. [Default is "whenNotSet"]
	 *		intrinsicMode = Synonym for "mode" option
	 * @return void
	 * @throws MergeException
	 */
	static private function _mergeIntrinsics($t_base, $base, $rows, $options=null) : void {
		$notification = caGetOption('notification', $options, null);
		$ret = $base;
		
		$mode = caGetOption(['mode', 'intrinsicMode'], $options, null);
		
		$pk = $t_base->primaryKey();
		foreach($rows as $id => $ivalues) {
			if($id == $base[$pk]) { continue; }
			foreach($ivalues as $f => $v) {
				if(in_array($f, [$pk, 'parent_id', 'type_id', 'source_id', 'locale_id', 'rank', 'access', 'status'])) { continue; }
				if(preg_match("!^hier_!", $f)) { continue; }
				
				switch(strtolower($mode)) {
					case 'base':
						// noop
						break;
					case 'merged':
						if(strlen($v) > 0) { $ret[$f] = $v; }
						break;
					case 'whennotset':
					default:
						if((strlen($v) > 0) && (!array_key_exists($f, $ret) || !strlen($ret[$f]) || !(bool)$ret[$f])) {
							$ret[$f] = $v;
						}
						break;
				}
			}
		}
		
		// rewrite intrinsics
		foreach($ret as $f => $v) {
			$ov = $t_base->get("{$table}.{$f}");
			if(($ov != $v) && (bool)$v) {
				$t_base->set($f, $v);
			}
		}	
		if(!$t_base->update()) {
			throw new MergeException(_t("Could not set merged intrinsic values: %1", join('; ', $t_base->getErrors())));
		}
	}
	# -------------------------------------------------------
	/**
	 * Merge metadata attributes from several rows
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_base Instance to merge relationships into
	 * @param array $row_ids A list of record ids to merge into the base record
	 * @param array $options Options include:
	 *		notification = A NotificationManager instance to post notifications regarding the merge to. [Default is null]
	 *
	 * @return void
	 * @throws MergeException
	 */
	static private function _mergeAttributes($t_base, $base, $rows, $options=null) : void {
		$notification = caGetOption('notification', $options, null);
		
		foreach($rows as $row_id => $attrs) {
			foreach($attrs as $attrs_for_element) {
				foreach($attrs_for_element as $attr) {
					$element_id = $attr->getElementID();
					$datatype = ca_metadata_elements::getElementDatatype($element_id);
					$element_code = ca_metadata_elements::getElementCodeForID($element_id);
					$t_element = ca_metadata_elements::getInstance($element_id);
					
					$values = $attr->getDisplayValues(false, ['forDuplication' => true]);
					if(!sizeof(array_filter($values, function($v) {
						return (bool)strlen($v);
					}))) {
						continue;	// all empty
					}
					$locale_id = $attr->getLocaleID();
					
					$t_restriction = $t_element->getTypeRestrictionInstanceForElement($t_base->tableNum(), $t_base->getTypeID());
					if (!$t_restriction) { return; }		// attribute not bound to this type
					$max = $t_restriction->getSetting('maxAttributesPerRow');
					
					$c = $t_base->getAttributeCountByElement($element_id);
					if($c >= $max) {
						if($datatype == 1) { // try to append for text fields
							$existing_vals = $t_base->getAttributesByElement($element_id);
							$existing_val = $existing_vals[0]->getDisplayValues(false, ['forDuplication' => true]);
							
							if(sizeof(array_diff_assoc($existing_val, $values))) {
								$values[$element_code] = $existing_val[$element_code]."\n".$values[$element_code];
								$t_base->replaceAttribute(array_merge($values, ['locale_id' => $locale_id]), $element_code);
							}
						} else {
							// skip
						}
					} else {
						$t_base->addAttribute(array_merge($values, ['locale_id' => $locale_id]), $element_code, null, ['skipExistingValues' => true]);
					}
					if(!$t_base->update()) {
						throw new MergeException(_t("Could not set merged attribute values for %1: %2", $element_code, join('; ', $t_base->getErrors())));
					}
				}
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Merge relationships from several rows into a single record
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_base Instance to merge relationships into
	 * @param array $row_ids A list of record ids to merge into the base record
	 * @param array $options Options include:
	 *		notification = A NotificationManager instance to post notifications regarding the merge to. [Default is null]
	 *		skipRelationships = A list of related tables to skip relationship merging for. [Default is null]
	 *
	 * @return void
	 * @throws MergeException
	 */
	static private function _mergeRelationships(BundlableLabelableBaseModelWithAttributes $t_base, array $row_ids, ?array $options=null) : void {
		$base_id = $t_base->getPrimaryKey();
		$table = $t_base->tableName();
		$notification = caGetOption('notification', $options, null);
		
		$tables = self::_relationshipTablesToMerge($options);

		$c = 0;
		foreach($row_ids as $row_id) {
			if($row_id == $base_id) { continue; }
			$t_subject = Datamodel::getInstance($table, false, $row_id);
			$t_subject->setTransaction($t_base->getTransaction());
			foreach($tables as $t) {
				$c += $t_subject->moveRelationships($t, $base_id);
			}

			if ($t_subject->tableName() == 'ca_object_lots') { 		// move objects to new target
				$object_ids = $t_subject->get('ca_objects.object_id', ['returnAsArray' => true]);
				if(is_array($object_ids) && sizeof($object_ids)) {
					$t_object = Datamodel::getInstance('ca_objects', true);
					$t_object->setTransaction($t_base->getTransaction());
					foreach($object_ids as $object_id) {
						if ($t_object->load($object_id)) {
							$t_object->setMode(ACCESS_WRITE);
							$t_object->set('lot_id', $base_id);
							$t_object->update();
							if ($t_object->numErrors() > 0) { continue; }
							$c++;
						}
					}
				}
			}
			
			if($notification && ($c > 0)) {
				$notification->addNotification(($c == 1) ? _t("Transferred %1 relationship to <em>%2</em> (%3)", $v, $t_base->getLabelForDisplay(), $t_base->get($t_base->getProperty('ID_NUMBERING_ID_FIELD'))) : _t("Transferred %1 relationships to <em>%2</em> (%3)", $c, $t_base->getLabelForDisplay(), $t_base->get($t_base->getProperty('ID_NUMBERING_ID_FIELD'))), __NOTIFICATION_TYPE_INFO__);
			}
			
			// update existing metadata attributes to use remapped value
			$t_subject->moveAuthorityElementReferences($base_id);
			
			// Do we need to move references contained in attributes bound to this item?
			try {
				$t_subject->moveAttributes($base_id, $t_subject->getAuthorityElementList(['idsOnly' => true]));
			} catch(ApplicationException $e) {
				if ($notification) {
					$notification->addNotification(_t("Could not move references to other items in metadata before delete: %1", $e->getErrorDescription()), __NOTIFICATION_TYPE_ERROR__);
				}
				throw new MergeException(_t("Could not move references to other items in metadata: %1", $e->getErrorDescription()));
			}
		
			// move children
			if ($t_subject->isHierarchical() && is_array($children = call_user_func("{$table}::getHierarchyChildrenForIDs", [$t_subject->getPrimaryKey()]))) {
				$t_child = Datamodel::getInstance($table, true);
				$t_child->setTransaction($t_base->getTransaction());
				$child_count = 0;
				foreach($children as $child_id) {
					$t_child->load($child_id);
					$t_child->set('parent_id', $base_id);
					$t_child->update();
					if ($t_child->numErrors() > 0) { continue; }
					$child_count++;
				}
			}
		}
		
		if($notification && ($child_count > 0)) {
			$notification->addNotification(($child_count == 1) ? _t("Transferred %1 children to <em>%2</em> (%3)", $child_count, $t_base->getLabelForDisplay(), $t_base->get($t_base->getProperty('ID_NUMBERING_ID_FIELD'))) : _t("Transferred %1 children to <em>%2</em> (%3)", $child_count, $t_base->getLabelForDisplay(), $t_base->get($t_base->getProperty('ID_NUMBERING_ID_FIELD'))), __NOTIFICATION_TYPE_INFO__);
		}
	}
	# -------------------------------------------------------
	/**
	 * Returns list of related tables for which relationships should be merged. The list is generated subject
	 * to values in the skipRelationships option.
	 *
	 * @param array $options Options include:
	 *		skipRelationships = A list of related tables to skip relationship merging for. [Default is null]
	 *
	 * @return array List of tables
	 */
	static private function _relationshipTablesToMerge(?array $options=null) : array {
		$skip_relationships = caGetOption('skipRelationships', $options, null);
		$rel_tables = [
			'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 
			'ca_storage_locations', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours', 
			'ca_tour_stops', 'ca_object_representations', 'ca_list_items'
		];
		if(is_array($skip_relationships)) {
			$rel_tables = array_filter($rel_tables, function($v) use ($skip_relationships) { return !in_array($v, $skip_relationships); } );
		}
		return $rel_tables;
	}
	# -------------------------------------------------------
}
