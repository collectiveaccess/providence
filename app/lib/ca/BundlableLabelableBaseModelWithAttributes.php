<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BundlableLabelableBaseModelWithAttributes.php : base class for models that take application of bundles
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__."/ca/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/ca/LabelableBaseModelWithAttributes.php");
require_once(__CA_LIB_DIR__."/core/Plugins/SearchEngine/CachedResult.php");

require_once(__CA_LIB_DIR__."/ca/IDNumbering.php");
require_once(__CA_APP_DIR__."/helpers/accessHelpers.php");
require_once(__CA_APP_DIR__."/helpers/searchHelpers.php");

define('__CA_BUNDLE_ACCESS_NONE__', 0);
define('__CA_BUNDLE_ACCESS_READONLY__', 1);
define('__CA_BUNDLE_ACCESS_EDIT__', 2);

class BundlableLabelableBaseModelWithAttributes extends LabelableBaseModelWithAttributes implements IBundleProvider {
	# ------------------------------------------------------
	protected $BUNDLES = array();
	
	protected $opo_idno_plugin_instance;
	
	static $s_idno_instance_cache = array();
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
		require_once(__CA_MODELS_DIR__."/ca_acl.php");
		parent::__construct($pn_id);	# call superclass constructor
		
		$this->initLabelDefinitions();
	}
	# ------------------------------------------------------
	/**
	 * Overrides load() to initialize bundle specifications
	 */
	public function load ($pm_id=null, $pb_use_cache=true) {
		global $AUTH_CURRENT_USER_ID;
		
		$vn_rc = parent::load($pm_id);
		
		if ($this->getAppConfig()->get('perform_item_level_access_checking')) {
			if ($this->checkACLAccessForUser(new ca_users($AUTH_CURRENT_USER_ID)) == __CA_ACL_NO_ACCESS__) {
				$this->clear();
				return false;
			}
		}
		$this->initLabelDefinitions();
		
		if ($this->isHierarchical() && $this->opo_idno_plugin_instance) {
			$this->opo_idno_plugin_instance->isChild((($vs_parent_id_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD')) && $this->get($vs_parent_id_fld) > 0) ? true : false);
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
			$vb_we_set_transaction = false;
		
			if (!$this->inTransaction()) {
				$this->setTransaction(new Transaction($this->getDb()));
				$vb_we_set_transaction = true;
			}
		
			$this->opo_app_plugin_manager->hookBeforeBundleInsert(array('id' => null, 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
			$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
		
			// check that type_id is valid for this table
			$t_list = new ca_lists();
			$vn_type_id = $this->get($this->getTypeFieldName());
			$va_field_info = $this->getFieldInfo($this->getTypeFieldName());
		
			$vb_error = false;
			if ($this->getTypeFieldName() && !(!$vn_type_id && $va_field_info['IS_NULL'])) {
				if (!($vn_ret = $t_list->itemIsEnabled($this->getTypeListCode(), $vn_type_id))) {
					$va_type_list = $this->getTypeList(array('directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'item_id' => null));
					if (!isset($va_type_list[$vn_type_id])) {
						$this->postError(2510, _t("Type must be specified"), "BundlableLabelableBaseModelWithAttributes->insert()");
					} else {
						if(is_null($vn_ret)) {
							$this->postError(2510, _t("<em>%1</em> is invalid", $va_type_list[$vn_type_id]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()");
						} else {
							$this->postError(2510, _t("<em>%1</em> is not enabled", $va_type_list[$vn_type_id]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()");
						}
					}
					$vb_error = true;
				}
		
				if ($this->HIERARCHY_PARENT_ID_FLD && (bool)$this->getAppConfig()->get($this->tableName().'_enforce_strict_type_hierarchy')) {
					// strict means if it has a parent is can only have types that are direct sub-types of the parent's type
					// and if it is the root of the hierarchy it can only take a top-level type
					if ($vn_parent_id = $this->get($this->HIERARCHY_PARENT_ID_FLD)) {
						// is child
						$t_parent = $this->_DATAMODEL->getInstanceByTableName($this->tableName());
						if ($t_parent->load($vn_parent_id)) {
							$vn_parent_type_id = $t_parent->getTypeID();
							$va_type_list = $t_parent->getTypeList(array('directChildrenOnly' => ($this->getAppConfig()->get($this->tableName().'_enforce_strict_type_hierarchy') == '~') ? false : true, 'childrenOfCurrentTypeOnly' => true, 'returnHierarchyLevels' => true));

							if (!isset($va_type_list[$this->getTypeID()])) {
								$va_type_list = $this->getTypeList(array('directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'item_id' => null));

								$this->postError(2510, _t("<em>%1</em> is not a valid type for a child record of type <em>%2</em>", $va_type_list[$this->getTypeID()]['name_singular'], $va_type_list[$vn_parent_type_id]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()");
								$vb_error = true;
							}
						} else {
							// error - no parent?
							$this->postError(2510, _t("No parent was found when verifying type of new child"), "BundlableLabelableBaseModelWithAttributes->insert()");
							$vb_error = true;
						}
					} else {
						// is root
						$va_type_list = $this->getTypeList(array('directChildrenOnly' => true, 'item_id' => null));
						if (!isset($va_type_list[$this->getTypeID()])) {
							$va_type_list = $this->getTypeList(array('directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'item_id' => null));
						
							$this->postError(2510, _t("<em>%1</em> is not a valid type for a top-level record", $va_type_list[$this->getTypeID()]['name_singular']), "BundlableLabelableBaseModelWithAttributes->insert()");
							$vb_error = true;
						}
					}
				}
			}
		
			if (!$this->_validateIncomingAdminIDNo(true, true)) { $vb_error =  true; }
		
			if ($vb_error) {			
				// push all attributes onto errored list
				$va_inserted_attributes_that_errored = array();
				foreach($this->opa_attributes_to_add as $va_info) {
					$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
				}
				foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
					$this->setFailedAttributeInserts($vs_element, $va_list);
				}
			
				if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
				if ($vb_we_set_transaction) { $this->removeTransaction(false); }
				$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
				return false;
			}
		
			$this->_generateSortableIdentifierValue();
		}
		
		// stash attributes to add
		$va_attributes_added = $this->opa_attributes_to_add;
		if (!($vn_rc = parent::insert($pa_options))) {	
			// push all attributes onto errored list
			$va_inserted_attributes_that_errored = array();
			foreach($va_attributes_added as $va_info) {
				if (isset($this->opa_failed_attribute_inserts[$va_info['element']])) { continue; }
				$va_inserted_attributes_that_errored[$va_info['element']][] = $va_info['values'];
			}
			foreach($va_inserted_attributes_that_errored as $vs_element => $va_list) {
				$this->setFailedAttributeInserts($vs_element, $va_list);
			}
			
			if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
			if ($vb_we_set_transaction) { $this->removeTransaction(false); }
			$this->_FIELD_VALUES[$this->primaryKey()] = null;		// clear primary key set by BaseModel::insert()
			return false;
		}
		
		if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
	
		$this->opo_app_plugin_manager->hookAfterBundleInsert(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		if ($vb_we_set_transaction) { $this->removeTransaction(true); }
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
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		}
		
		$vb_web_set_change_log_unit_id = BaseModel::setChangeLogUnitID();
		
		$this->opo_app_plugin_manager->hookBeforeBundleUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		$va_errors = array();
		if (!$this->_validateIncomingAdminIDNo(true, false)) { 
			 $va_errors = $this->errors();
			 // don't save number if it's invalid
			 if ($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
			 	$this->set($vs_idno_field, $this->getOriginalValue($vs_idno_field));
			 }
		} else {
			$this->_generateSortableIdentifierValue();
		}
	
		$vn_rc = parent::update($pa_options);
		$this->errors = array_merge($this->errors, $va_errors);
		
		$this->opo_app_plugin_manager->hookAfterBundleUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this));
		
		if ($vb_web_set_change_log_unit_id) { BaseModel::unsetChangeLogUnitID(); }
		
		if ($vb_we_set_transaction) { $this->removeTransaction($vn_rc); }
		return $vn_rc;
	}	
	# ------------------------------------------------------------------
	/**
	 * Check user's item level access before passing delete to lower level libraries
	 *
	 */
	public function delete ($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		global $AUTH_CURRENT_USER_ID;
		if ($this->getAppConfig()->get('perform_item_level_access_checking')) {
			if ($this->checkACLAccessForUser(new ca_users($AUTH_CURRENT_USER_ID)) < __CA_ACL_EDIT_DELETE_ACCESS__) {
				$this->postError(2580, _t("You do not have delete access for this item"), "BundlableLabelableBaseModelWithAttributes->delete()");
				return false;
			}
		}
		return parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
	}
	# ------------------------------------------------------------------
	/**
	 * Duplicates record, including labels, attributes and relationships. "Special" bundles - those
	 * specific to a model - should be duplicated by the model by overriding BundlableLabelablleBaseModelWithAttributes::duplicate()
	 * and doing any required work after BundlableLabelablleBaseModelWithAttributes::duplicate() has finished
	 * 
	 * @param array $pa_options
	 *		duplicate_nonpreferred_labels = if set nonpreferred labels will be duplicated. Default is false.
	 *		duplicate_attributes = if set all content fields (intrinsics and attributes) will be duplicated. Default is false.
	 *		duplicate_relationships = if set to an array of table names, all relationships to be specified tables will be duplicated. Default is null - no relationships duplicated.
	 *		user_id = User ID of the user to make owner of the newly duplicated record (for records that support ownership by a user like ca_bundle_displays)
	 *		
	 * @return BundlableLabelablleBaseModelWithAttributes instance of newly created duplicate item
	 */
	public function duplicate($pa_options=null) {
		if (!$this->getPrimaryKey()) { return false; }
		$vs_idno_fld = $this->getProperty('ID_NUMBERING_ID_FIELD');
		$vs_idno_sort_fld = $this->getProperty('ID_NUMBERING_SORT_FIELD');
		$vs_pk = $this->primaryKey();
		
		$vb_duplicate_nonpreferred_labels = isset($pa_options['duplicate_nonpreferred_labels']) && $pa_options['duplicate_nonpreferred_labels'];
		$vb_duplicate_attributes = isset($pa_options['duplicate_attributes']) && $pa_options['duplicate_attributes'];
		$va_duplicate_relationships = (isset($pa_options['duplicate_relationships']) && is_array($pa_options['duplicate_relationships']) && sizeof($pa_options['duplicate_relationships'])) ? $pa_options['duplicate_relationships'] : array();
		
		
		$vb_we_set_transaction = false;
		if (!$this->inTransaction()) {
			$this->setTransaction($o_t = new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			$o_t = $this->getTransaction();
		}
		
		// create new instance
		if (!($t_dupe = $this->_DATAMODEL->getInstanceByTableName($this->tableName()))) { 
			if ($vb_we_set_transaction) { $o_t->rollback();}
			return null;
		}
		$t_dupe->purify($this->purify());
		$t_dupe->setTransaction($o_t);
		
		if($this->isHierarchical()) {
			if (!$this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD'))) {
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
			$t_dupe->set($vs_field, $this->get($this->tableName().'.'.$vs_field));
		}
		$t_dupe->set($this->getTypeFieldName(), $this->getTypeID());
		
		// Calculate identifier using numbering plugin
		if ($vs_idno_fld) {
			$vb_needs_suffix_generated = false;
			if (method_exists($this, "getIDNoPlugInInstance") && ($o_numbering_plugin = $this->getIDNoPlugInInstance())) {
				if (!($vs_sep = $o_numbering_plugin->getSeparator())) { $vs_sep = '-'; }	// Must have a separator or you can get inexplicable numbers as numeric increments are appended string-style
				
				$vs_idno_template = $o_numbering_plugin->makeTemplateFromValue($this->get($vs_idno_fld), 1);	// make template out of original idno by replacing last SERIAL element with "%"
				if (!preg_match("!%$!", $vs_idno_template)) { $vb_needs_suffix_generated = true; }
				
				if (!is_array($va_idno_values = $o_numbering_plugin->htmlFormValuesAsArray($vs_idno_fld, $vs_idno_template, false, false, false))) { $va_idno_values = array(); }

				$t_dupe->set($vs_idno_fld, $vs_idno = join($vs_sep, $va_idno_values));	
			} 
			
			if (!($vs_idno_stub = trim($t_dupe->get($vs_idno_fld)))) {
				$vs_idno_stub = trim($this->get($vs_idno_fld));
			}
	
			if ($vs_idno_stub) {
				if ($vb_needs_suffix_generated) {
					$t_lookup = $this->_DATAMODEL->getInstanceByTableName($this->tableName());
				
					$va_tmp = $vs_sep ? preg_split("![{$vs_sep}]+!", $vs_idno_stub) : array($vs_idno_stub);
					$vs_suffix = (is_array($va_tmp) && (sizeof($va_tmp) > 1)) ? array_pop($va_tmp) : '';
					if (!is_numeric($vs_suffix)) { 
						$vs_suffix = 0; 
					} else {
						$vs_idno_stub = preg_replace("!{$vs_suffix}$!", '', $vs_idno_stub);	
					}
					do {
						$vs_suffix = (int)$vs_suffix + 1;
						$vs_idno = trim($vs_idno_stub).$vs_sep.trim($vs_suffix);	// force separator if none defined otherwise you end up with agglutinative numbers
					} while($t_lookup->load(array($vs_idno_fld => $vs_idno)));
				}
			} else {
				$vs_idno = "???";
			}
			if ($vs_idno == $this->get($vs_idno_fld)) { $vs_idno .= " ["._t('DUP')."]"; }
			$t_dupe->set($vs_idno_fld, $vs_idno);
		}
		
		$t_dupe->setMode(ACCESS_WRITE);
		
		if (isset($pa_options['user_id']) && $pa_options['user_id'] && $t_dupe->hasField('user_id')) { $t_dupe->set('user_id', $pa_options['user_id']); }
		
		$t_dupe->insert();
		
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
					if (!$vb_duplicate_nonpreferred_labels && !$va_label_info['is_preferred']) { continue; }
					$va_label_info[$vs_label_display_field] .= " ["._t('Duplicate')."]";
					$t_dupe->addLabel(
						$va_label_info, $va_label_info['locale_id'], $va_label_info['type_id'], $va_label_info['is_preferred']
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
			if (!$t_dupe->copyAttributesFrom($this->getPrimaryKey())) {
				$this->errors = $t_dupe->errors;
				if ($vb_we_set_transaction) { $o_t->rollback();}
				return false;
			}
		}
		
		// duplicate relationships
		foreach(array(
			'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_storage_locations', 'ca_tour_stops'
		) as $vs_rel_table) {
			if (!in_array($vs_rel_table, $va_duplicate_relationships)) { continue; }
			if ($this->copyRelationships($vs_rel_table, $t_dupe->getPrimaryKey()) === false) {
				$this->errors = $t_dupe->errors;
				if ($vb_we_set_transaction) { $o_t->rollback();}
				return false;
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
		if ($this->getPrimaryKey() && isset($pa_fields[$this->getTypeFieldName()]) && !(isset($pa_options['allowSettingOfTypeID']) && $pa_options['allowSettingOfTypeID'])) {
			$this->postError(2520, _t("Type id cannot be set after insert"), "BundlableLabelableBaseModelWithAttributes->set()");
			return false;
		}
		
		if ($this->opo_idno_plugin_instance) {
			// If attempting to set parent_id, then flag record as child for id numbering purposes
			$this->opo_idno_plugin_instance->isChild(((($vs_parent_id_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD')) && isset($pa_fields[$vs_parent_id_fld]) && ($pa_fields[$vs_parent_id_fld] > 0)) || ($this->get($vs_parent_id_fld))) ? true : false);
		
			if (in_array($this->getProperty('ID_NUMBERING_ID_FIELD'), $pa_fields)) {
				if (!$this->_validateIncomingAdminIDNo(true, true)) { 
					if (!$this->get($vs_parent_id_fld) && isset($pa_fields[$vs_parent_id_fld]) && ($pa_fields[$vs_parent_id_fld] > 0)) {
						// If we failed to set parent_id and there wasn't a parent_id set already then revert child status in id numbering
						$this->opo_idno_plugin_instance->isChild(false); 
					}
					return false; 
				}
			}
		}
		
		return parent::set($pa_fields, "", $pa_options);
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
	 */
	public function get($ps_field, $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		$vs_template = 				(isset($pa_options['template'])) ? $pa_options['template'] : null;
		$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
		
		$vb_return_as_link = 		(isset($pa_options['returnAsLink'])) ? (bool)$pa_options['returnAsLink'] : false;
		$vs_return_as_link_text = 	(isset($pa_options['returnAsLinkText'])) ? (string)$pa_options['returnAsLinkText'] : '';
		$vs_return_as_link_target = (isset($pa_options['returnAsLinkTarget'])) ? (string)$pa_options['returnAsLinkTarget'] : '';
		$vs_return_as_link_attributes = (isset($pa_options['returnAsLinkAttributes']) && is_array($pa_options['returnAsLinkAttributes'])) ? $pa_options['returnAsLinkAttributes'] : array();
		
		$vb_return_all_locales = 	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
		$vs_delimiter = 			(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
		$va_restrict_to_rel_types = (isset($pa_options['restrictToRelationshipTypes']) && is_array($pa_options['restrictToRelationshipTypes'])) ? $pa_options['restrictToRelationshipTypes'] : false;
		
		if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
		$va_get_where = 			(isset($pa_options['where']) && is_array($pa_options['where']) && sizeof($pa_options['where'])) ? $pa_options['where'] : null;
		
		
		// does get refer to an attribute?
		$va_tmp = explode('.', $ps_field);
		
		if(sizeof($va_tmp) > 1) {
			if ($va_tmp[0] != $this->tableName()) {
				$vs_access_chk_key  = $ps_field;
			} else {
				$va_tmp2 = $va_tmp;
				array_shift($va_tmp2);
				$vs_access_chk_key  = join(".", $va_tmp2); 
			}
		} else {
			$vs_access_chk_key = $ps_field;
		}
		
		
		if (!$this->hasField($va_tmp[sizeof($va_tmp)-1]) || $this->getFieldInfo($va_tmp[sizeof($va_tmp)-1], 'ALLOW_BUNDLE_ACCESS_CHECK')) {
			if (caGetBundleAccessLevel($this->tableName(), $vs_access_chk_key) == __CA_BUNDLE_ACCESS_NONE__) {
				return null;
			}
		}
		
		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true)) {
					$vs_pk = $t_instance->primaryKey();
					$va_related_items = $this->getRelatedItems($va_tmp[0], $pa_options);
					if (!is_array($va_related_items)) { return null; }
					
					if (!$vs_template && !$vb_return_all_locales && !$vb_return_as_array) {
						$vs_template = "^preferred_labels";
					}
					
					if ($vb_return_all_locales) { 
						$va_related_tmp = array();
						foreach($va_related_items as $vn_i => $va_related_item) {
							$va_related_tmp[$vn_i][$va_related_item['locale_id']] = $va_related_item;
						}
						$va_related_items = $va_related_tmp;
					} else {
						if ($vs_template) {
							$va_template_opts = $pa_options;
							unset($va_template_opts['request']);
							unset($va_template_opts['template']);
							$va_template_opts['returnAsLink'] = false;
							$va_template_opts['returnAsArray'] = true;
							$va_template_opts['requireLinkTags'] = true;
						
							$va_ids = array();
							if (is_array($va_rel_items = $this->get($va_tmp[0], $va_template_opts))) {
								foreach($va_rel_items as $vn_rel_id => $va_rel_item) {
									$va_ids[] = $va_rel_item[$vs_pk];
									$va_template_opts['relationshipValues'][$va_rel_item[$vs_pk]][$va_rel_item['relation_id']]['relationship_typename'] = $va_rel_item['relationship_typename'];
									$va_template_opts['relationshipValues'][$va_rel_item[$vs_pk]][$va_rel_item['relation_id']]['relationship_type_id'] = $va_rel_item['relationship_type_id'];
									$va_template_opts['relationshipValues'][$va_rel_item[$vs_pk]][$va_rel_item['relation_id']]['label'] = $va_rel_item['label'];
								}
							} else {
								$va_rel_items = array();
							}
						
							$va_text = caProcessTemplateForIDs($vs_template, $va_tmp[0], $va_ids, $va_template_opts);
						
							if ($vb_return_as_link) {
								$va_text = caCreateLinksFromText($va_text, $va_tmp[0], $va_ids, $vs_return_as_link_class, $vs_return_as_link_target);
							} 
						
							if ($vb_return_as_array) {
								return $va_text;
							}
							return join($vs_delimiter, $va_text);
						}
					}
					
					return $va_related_items;
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name || table_name.related
			case 3:		// table_name.field_name.sub_element || table_name.related.field_name
			case 4:		// table_name.related.field_name.sub_element
				//
				// TODO: this code is compact, relatively simple and works but is slow since it
				// generates a lot more identical database queries than we'd like
				// We will need to add some notion of caching so that multiple calls to get() 
				// for various fields in the same list of related items don't cause repeated queries
				//
				$vb_is_related = false;
				$vb_is_hierarchy = false;
				if ($va_tmp[1] === 'related') {
					array_splice($va_tmp, 1, 1);
					$vb_is_related = true;
				}
				
				if ($vb_is_related || ($va_tmp[0] !== $this->tableName())) {		// must be related table			
					$t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true);
					$vs_pk = $t_instance->primaryKey();
					
					if ($vs_template && !$vb_return_all_locales) {
						$va_template_opts = $pa_options;
						unset($va_template_opts['request']);
						unset($va_template_opts['template']);
						$va_template_opts['returnAsLink'] = false;
						$va_template_opts['returnAsArray'] = true;
						$va_template_opts['requireLinkTags'] = true;
						
						$va_ids = array();
						if (is_array($va_rel_items = $this->get($va_tmp[0], $va_template_opts))) {
							foreach($va_rel_items as $vn_rel_id => $va_rel_item) {
								$va_ids[] = $va_rel_item[$vs_pk];
								$va_template_opts['relationshipValues'][$va_rel_item[$vs_pk]][$va_rel_item['relation_id']]['relationship_typename'] = $va_rel_item['relationship_typename'];
							}
						} else {
							$va_rel_items = array();
						}
						
						$va_text = caProcessTemplateForIDs($vs_template, $va_tmp[0], $va_ids, $va_template_opts);
						
						if ($vb_return_as_link) {
							$va_text = caCreateLinksFromText($va_text, $va_tmp[0], $va_ids, $vs_return_as_link_class, $vs_return_as_link_target);
						} 
						
						if ($vb_return_as_array) {
							return $va_text;
						}
						return join($vs_delimiter, $va_text);
					}
					
					$va_related_items = $this->getRelatedItems($va_tmp[0], array_merge($pa_options, array('returnLabelsAsArray' => true)));
				
					if (is_array($va_restrict_to_rel_types) && sizeof($va_restrict_to_rel_types)) {
						require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
						$t_rel_types = new ca_relationship_types();
						
						$va_restrict_to_rel_types = $t_rel_types->relationshipTypeListToIDs($t_rel_types->getRelationshipTypeTable($this->tableName(), $va_tmp[0]), $va_restrict_to_rel_types, array('includeChildren' => true));
					}
					
				 	$vb_field_is_specified = (
				 		((sizeof($va_tmp) == 2) && (!$vb_is_hierarchy && ($va_tmp[1] != 'related')))
				 		||
				 		((sizeof($va_tmp) >= 3))
				 	);
					
					$va_ids = array();
					$va_items = array();
					if(is_array($va_related_items) && (sizeof($va_related_items) > 0)) {
						foreach($va_related_items as $vn_rel_id => $va_related_item) {
							$va_ids[] = $va_related_item[$vs_pk];
							if (is_array($va_restrict_to_rel_types) && !in_array($va_related_item['relationship_type_id'], $va_restrict_to_rel_types)) { continue; }
							
							if ($va_tmp[1] == 'relationship_typename') {
								$va_items[] = $va_related_item['relationship_typename'];
								continue;
							}
							
							if ($va_tmp[1] == 'hierarchy') {
								$vb_is_hierarchy = true;
								if ($t_instance->load($va_related_item[$t_instance->primaryKey()])) {
									$va_items[] = $t_instance->get(join('.', $va_tmp), $pa_options);
								}
								continue;
							}
							
							// is field directly returned by getRelatedItems()?
							if (isset($va_tmp[1]) && isset($va_related_item[$va_tmp[1]]) && $t_instance->hasField($va_tmp[1])) {
								if ($vb_return_as_array) {
									if ($vb_return_all_locales) {
										// for return as locale-index array
										$va_items[$va_related_item['relation_id']][$va_related_item['locale_id']][] = $va_related_item[$va_tmp[1]];
									} else {
										// for return as simple array
										$va_items[] = $va_related_item[$va_tmp[1]];
									}
								} else {
									// for return as string
									$va_items[] = $va_related_item[$va_tmp[1]];
								}
								continue;
							}
							
							// is field preferred labels?
							if ($va_tmp[1] === 'preferred_labels') {
								if (!isset($va_tmp[2])) {
									if ($vb_return_as_array || $vb_return_all_locales) {
										if ($vb_return_all_locales) {
											// for return as locale-index array
											$va_items[$va_related_item['relation_id']][] = $va_related_item['labels'];
										} else {
											// for return as simple array
											$va_item_list = caExtractValuesByUserLocale(array($va_related_item['labels']));
											foreach($va_item_list as $vn_x => $va_item) {
												$va_items[] = $va_item[$t_instance->getLabelDisplayField()];
											}
										}
									} else {
										// for return as string
										$va_items[] = $va_related_item['label'][$t_instance->getLabelDisplayField()];
									}
								} else {
									if ($vb_return_all_locales) {
										// for return as locale-index array
										foreach($va_related_item['labels'] as $vn_locale_id => $va_label) {
											$va_items[$va_related_item['relation_id']][$vn_locale_id][] = $va_label[$va_tmp[2]];
										}
									} else {
										foreach(caExtractValuesByUserLocale(array($va_related_item['labels'])) as $vn_i => $va_label) {
											// for return as string or simple array
											$va_items[] = $va_label[$va_tmp[2]];
										}
									}
								}
								
								continue;
							}
							
							// TODO: add support for nonpreferred labels
							
							
							
							// Get related attributes
							if ($t_instance->load($va_related_item[$t_instance->primaryKey()])) {
								if (isset($va_tmp[1])) {
									if ($vm_val = $t_instance->get(join(".", $va_tmp), $pa_options)) {
										if ($vb_return_as_link && !$vb_return_as_array && !$vb_return_all_locales) {
											$va_items[] = $vm_val;
										} else {
											return $vm_val;
										}
									}
								} else {
									$va_items[]  = $this->get($va_tmp[0], $pa_options);
								}
							} else {
								$va_items[] = null;
							}
						}
					}
			
					if ($vb_return_as_link && !$vb_return_all_locales) {
						$va_items = caCreateLinksFromText($va_items, $va_tmp[0], $va_ids, $vs_return_as_link_class, $vs_return_as_link_target);
					}
					
					if($vb_return_as_array) {
						return $va_items;
					} else {
						return join($vs_delimiter, $va_items);
					}
				}
				break;
			# -------------------------------------
		}
		
			
		return parent::get($ps_field, $pa_options);
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
						$this->postError(1100, $vs_e, "BundlableLabelableBaseModelWithAttributes->insert()");
					}
				}
				return false;
			}
		}
		return true;
	}
	# ------------------------------------------------------------------
	/**
	  *
	  */
	public function getValuesForExport($pa_options=null) {
		$va_data = parent::getValuesForExport($pa_options);		// get intrinsics, attributes and labels
		
		$t_locale = new ca_locales();
		$t_list = new ca_lists();
		
		// get related items
		foreach(array('ca_objects', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations',  'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops',  'ca_list_items') as $vs_table) {
			$va_related_items = $this->getRelatedItems($vs_table, array('returnAsArray' => true, 'returnAllLocales' => true));
			if(is_array($va_related_items) && sizeof($va_related_items)) {
				$va_related_for_export = array();
				$vn_i = 0;
				foreach($va_related_items as $vn_id => $va_related_item) {
					$va_related_for_export['related_'.$vn_i] = $va_related_item;
					$vn_i++;
				}
				
				$va_data['related_'.$vs_table] = $va_related_for_export;
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
			
			$vs_remove_self_sql = '';
			if (!$pb_dont_remove_self) {
				$vs_remove_self_sql = ' AND ('.$this->primaryKey().' <> '.intval($this->getPrimaryKey()).')';
			}
			
			$vs_idno_context_sql = '';
			if ($vs_idno_context_field = $this->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
				if ($vn_context_id = $this->get($vs_idno_context_field)) {
					$vs_idno_context_sql = ' AND ('.$vs_idno_context_field.' = '.$this->quote($vs_idno_context_field, $vn_context_id).')';
				} else {
					if ($this->getFieldInfo($vs_idno_context_field, 'IS_NULL')) {
						$vs_idno_context_sql = ' AND ('.$vs_idno_context_field.' IS NULL)';
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
			", $ps_idno);
			
			$va_ids = array();
			while($qr_idno->nextRow()) {
				$va_ids[] = $qr_idno->get($this->primaryKey());
			}
			return $va_ids;
		} 
		
		return array();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _generateSortableIdentifierValue() {
		if (($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) && ($vs_idno_sort_field = $this->getProperty('ID_NUMBERING_SORT_FIELD'))) {
			
			if (($o_idno = $this->getIDNoPlugInInstance()) && (method_exists($o_idno, 'getSortableValue'))) {	// try to use plug-in's sort key generator if defined
				$this->set($vs_idno_sort_field, $o_idno->getSortableValue($this->get($vs_idno_field)));
				return;
			}
			
			// Create reasonable facsimile of sortable value since 
			// idno plugin won't do it for us
			$va_tmp = preg_split('![^A-Za-z0-9]+!',  $this->get($vs_idno_field));
			
			$va_output = array();
			$va_zeroless_output = array();
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
	 */
	 public function checkForDupeLabel($pn_locale_id, $pa_label_values) {
	 	$o_db = $this->getDb();
	 	$t_label = $this->getLabelTableInstance();
	 	
	 	unset($pa_label_values['displayname']);
	 	$va_sql = array();
	 	foreach($pa_label_values as $vs_field => $vs_value) {
	 		$va_sql[] = "(l.{$vs_field} = ?)";
	 	}
	 	
	 	if ($t_label->hasField('is_preferred')) { $va_sql[] = "(l.is_preferred = 1)"; }
	 	if ($t_label->hasField('locale_id')) { $va_sql[] = "(l.locale_id = ?)"; }
	 	if ($this->hasField('deleted')) { $va_sql[] = "(t.deleted = 0)"; }
	 	$va_sql[] = "(l.".$this->primaryKey()." <> ?)";
	 	
	 	$vs_sql = "SELECT ".$t_label->primaryKey()."
	 	FROM ".$t_label->tableName()." l
	 	INNER JOIN ".$this->tableName()." AS t ON t.".$this->primaryKey()." = l.".$this->primaryKey()."
	 	WHERE ".join(' AND ', $va_sql);
	
	 	$va_values = array_values($pa_label_values);
	 	$va_values[] = (int)$pn_locale_id;
	 	$va_values[] = (int)$this->getPrimaryKey();
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
	 *
	 */
	protected function initLabelDefinitions($pa_options=null) {
		$pb_dont_cache = caGetOption('dontCache', $pa_options, false);

		$this->BUNDLES = (is_subclass_of($this, "BaseRelationshipModel")) ? array() : array(
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
			if (!isset(BundlableLabelableBaseModelWithAttributes::$s_idno_instance_cache[$this->tableNum()."/".$vn_type_id])) {
				$va_types = array();
				$o_db = $this->getDb();		// have to do direct query here... if we use ca_list_items model we'll just endlessly recurse
				
				if ($vn_type_id) {
					
					$qr_res = $o_db->query("
						SELECT idno, list_id, hier_left, hier_right 
						FROM ca_list_items 
						WHERE 
							item_id = ?"
						, (int)$vn_type_id);
						
					if ($qr_res->nextRow()) {
						if ($vn_list_id = $qr_res->get('list_id')) {
							$vn_hier_left 		= $qr_res->get('hier_left');
							$vn_hier_right 		= $qr_res->get('hier_right');
							$vs_idno 			= $qr_res->get('idno');
							$qr_res = $o_db->query("
								SELECT idno, parent_id
								FROM ca_list_items 
								WHERE 
									(list_id = ? AND hier_left < ? AND hier_right > ?)", 
							(int)$vn_list_id, (int)$vn_hier_left, (int)$vn_hier_right);
							
							while($qr_res->nextRow()) {
								if (!$qr_res->get('parent_id')) { continue; }
								$va_types[] = $qr_res->get('idno');
							}
							$va_types[] = $vs_idno;
							$va_types = array_reverse($va_types);
						}
					}
				}
				BundlableLabelableBaseModelWithAttributes::$s_idno_instance_cache[$this->tableNum()."/".$vn_type_id] = $this->opo_idno_plugin_instance = IDNumbering::newIDNumberer($this->tableName(), $va_types, null, $o_db);
			} else {
				$this->opo_idno_plugin_instance = BundlableLabelableBaseModelWithAttributes::$s_idno_instance_cache[$this->tableNum()."/".$vn_type_id];
			}
		} else {
			$this->opo_idno_plugin_instance = null;
		}
		
		// add metadata elements
		foreach($this->getApplicableElementCodes($vn_type_id, false, $pb_dont_cache) as $vs_code) {
			$this->BUNDLES['ca_attribute_'.$vs_code] = array(
				'type' => 'attribute', 'repeating' => false, 'label' => $vs_code
			);
		}
	}
	# ---------------------------------------------------------------------------------------------
	/**
 	 * Check if currently loaded row is readable
 	 *
 	 * @param RequestHTTP $po_request
 	 * @param string $ps_bundle_name Optional bundle name to test readability on. If omitted readability is considered for the item as a whole.
 	 * @return bool True if record can be read by the current user, false if not
 	 */
	function isReadable($po_request, $ps_bundle_name=null) {
		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $po_request->user->getTypeAccessLevel($ps_table_name, $this->getTypeID());
			if ($vn_type_access < __CA_BUNDLE_ACCESS_READONLY__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking')) {
			$vn_item_access = $this->checkACLAccessForUser($po_request->user);
			if ($vn_item_access < __CA_ACL_READONLY_ACCESS__) {
				return false;
			}
		}
		
		if ($ps_bundle_name) {
			if ($po_request->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) < __CA_BUNDLE_ACCESS_READONLY__) { return false; }
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
 		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $po_request->user->getTypeAccessLevel($this->tableName(), $this->getTypeID());
			if ($vn_type_access != __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking') && $this->getPrimaryKey()) {
			$vn_item_access = $this->checkACLAccessForUser($po_request->user);
			if ($vn_item_access < __CA_ACL_EDIT_ACCESS__) {
				return false;
			}
		}
		
 		// Check actions
 		if (!$this->getPrimaryKey() && !$po_request->user->canDoAction('can_create_'.$this->tableName())) {
 			return false;
 		}
 		if ($this->getPrimaryKey() && !$po_request->user->canDoAction('can_edit_'.$this->tableName())) {
 			return false;
 		}
 		
		if ($ps_bundle_name) {
			if ($po_request->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) < __CA_BUNDLE_ACCESS_EDIT__) { return false; }
		}
 		
 		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Check if currently loaded row is deletable
 	 */
 	public function isDeletable($po_request) {
 		// Is row loaded?
 		if (!$this->getPrimaryKey()) { return false; }
 		
 		// Check type restrictions
 		if ((bool)$this->getAppConfig()->get('perform_type_access_checking')) {
			$vn_type_access = $po_request->user->getTypeAccessLevel($this->tableName(), $this->getTypeID());
			if ($vn_type_access != __CA_BUNDLE_ACCESS_EDIT__) {
				return false;
			}
		}
		
		// Check item level restrictions
		if ((bool)$this->getAppConfig()->get('perform_item_level_access_checking') && $this->getPrimaryKey()) {
			$vn_item_access = $this->checkACLAccessForUser($po_request->user);
			if ($vn_item_access < __CA_ACL_EDIT_DELETE_ACCESS__) {
				return false;
			}
		}
		
 		// Check actions
 		if (!$this->getPrimaryKey() && !$po_request->user->canDoAction('can_delete_'.$this->tableName())) {
 			return false;
 		}
 		
 		return true;
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
	public function getBundleFormHTML($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options, &$ps_bundle_label=null) {
		global $g_ui_locale;
		
		$vb_batch = (isset($pa_options['batch']) && $pa_options['batch']) ? true : false;
		
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
		
		if (!($vs_required_marker = $o_config->get('required_field_marker'))) {
			$vs_required_marker = '['._t('REQUIRED').']';
		}
		
		$vs_label = $vs_label_text = null;
		
		// is label for this bundle forced in bundle settings?
		if (isset($pa_bundle_settings['label']) && isset($pa_bundle_settings['label'][$g_ui_locale]) && ($pa_bundle_settings['label'][$g_ui_locale])) {
			$vs_label = $vs_label_text = $pa_bundle_settings['label'][$g_ui_locale];
		}
		
		$vs_element = '';
		$va_errors = array();
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
			
				if (!$vs_label_text) {  $vs_label_text = $va_info['label']; } 
				$vs_label = '<span class="formLabelText" id="'.$pa_options['formName'].'_'.$ps_placement_code.'">'.$vs_label_text.'</span>'; 
				
				if (($vs_type == 'preferred_label') && $o_config->get('show_required_field_marker') && $o_config->get('require_preferred_label_for_'.$this->tableName())) {
					$vs_label .= ' '.$vs_required_marker;
				}
				
				$vs_description = isset($pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale] : null;
				
				if (($vs_label_text) && ($vs_description)) {
					TooltipManager::add('#'.$pa_options['formName'].'_'.$ps_placement_code, "<h3>{$vs_label}</h3>{$vs_description}");
				}
				break;
			# -------------------------------------------------
			case 'intrinsic':
				if (isset($pa_bundle_settings['label'][$g_ui_locale]) && $pa_bundle_settings['label'][$g_ui_locale]) {
					$pa_options['label'] = $pa_bundle_settings['label'][$g_ui_locale];
				}
				if (!$pa_options['label']) {
					$pa_options['label'] = $this->getFieldInfo($ps_bundle_name, 'LABEL');
				}
				
				$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $pa_options['request']->getViewsDirectoryPath();
				$o_view = new View($pa_options['request'], "{$vs_view_path}/bundles/");
			
					
				$va_lookup_url_info = caJSONLookupServiceUrl($pa_options['request'], $this->tableName());
				
				if ($this->getFieldInfo($ps_bundle_name, 'IDENTITY')) {
					$o_view->setVar('form_element', ($vn_id = (int)$this->get($ps_bundle_name)) ? $vn_id : "&lt;"._t('Not yet issued')."&gt;");
				} else {
					$vb_read_only = ($pa_bundle_settings['readonly'] || ($pa_options['request']->user->getBundleAccessLevel($this->tableName(), $ps_bundle_name) == __CA_BUNDLE_ACCESS_READONLY__)) ? true : false;
					
					$o_view->setVar('form_element', $this->htmlFormElement($ps_bundle_name, ($this->getProperty('ID_NUMBERING_ID_FIELD') == $ps_bundle_name) ? $o_config->get('idno_element_display_format_without_label') : $o_config->get('bundle_element_display_format_without_label'), 
						array_merge(
							array(	
								'readonly' 					=> $vb_read_only,						
								'error_icon' 				=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/warning_small.gif",
								'progress_indicator'		=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/indicator.gif",
								'lookup_url' 				=> $va_lookup_url_info['intrinsic']
							),
							$pa_options
						)
					));
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
				
				$o_view->setVar('bundle_name', $ps_bundle_name);
				$o_view->setVar('settings', $pa_bundle_settings);
				$o_view->setVar('t_instance', $this);
				$o_view->setVar('batch', (bool)(isset($pa_options['batch']) && $pa_options['batch']));
				
				$vs_element = $o_view->render('intrinsic.php', true);
				
				
				$vs_description =  (isset($pa_bundle_settings['description'][$g_ui_locale]) && $pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale]  : $this->getFieldInfo($ps_bundle_name, 'DESCRIPTION');
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
				// bundle names for attributes are simply element codes prefixed with 'ca_attribute_'
				// since getAttributeHTMLFormBundle() takes a straight element code we have to strip the prefix here
				$vs_attr_element_code = str_replace('ca_attribute_', '', $ps_bundle_name);
				$vs_display_format = $o_config->get('bundle_element_display_format');
				
				$vs_element = $this->getAttributeHTMLFormBundle($pa_options['request'], $pa_options['formName'], $vs_attr_element_code, $ps_placement_code, $pa_bundle_settings, $pa_options);
				
				$vs_field_id = 'ca_attribute_'.$pa_options['formName'].'_'.$vs_attr_element_code;
				
				if (!$vs_label_text) { $vs_label_text = $this->getAttributeLabel($vs_attr_element_code); }
				
				if ($vb_batch) {
					$t_element = $this->_getElementInstance($vs_attr_element_code);
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
				$vs_description =  (isset($pa_bundle_settings['description'][$g_ui_locale]) && $pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale]  : $this->getAttributeDescription($vs_attr_element_code);

                $vs_documentation_url =  (isset($pa_bundle_settings['documentation_url']) && $pa_bundle_settings['documentation_url']) ? $pa_bundle_settings['documentation_url']  : $vs_documentation_url = $this->getAttributeDocumentationUrl($vs_attr_element_code);

				if ($t_element = $this->_getElementInstance($vs_attr_element_code)) {
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
						if (($ps_bundle_name != 'ca_object_representations') && ($this->_CONFIG->get($ps_bundle_name.'_disable'))) { return ''; }		// don't display if master "disable" switch is set
						$vs_element = $this->getRelatedHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);	
						break;
					# -------------------------------
					case 'ca_object_lots':
						if ($this->_CONFIG->get($ps_bundle_name.'_disable')) { break; }		// don't display if master "disable" switch is set
						
						$pa_lot_options = array('batch' => $vb_batch);
						if (($this->tableName() != 'ca_object_lots') && ($vn_lot_id = $pa_options['request']->getParameter('lot_id', pInteger))) {
							$pa_lot_options['force'][] = $vn_lot_id;
						}
						$vs_element = $this->getRelatedHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_lot_options);	
						break;
					# -------------------------------
					case 'ca_representation_annotations':
						//if (!method_exists($this, "getAnnotationType") || !$this->getAnnotationType()) { continue; }	// don't show bundle if this representation doesn't support annotations
						//if (!method_exists($this, "useBundleBasedAnnotationEditor") || !$this->useBundleBasedAnnotationEditor()) { continue; }	// don't show bundle if this representation doesn't use bundles to edit annotations
						
						$pa_options['fields'] = array('ca_representation_annotations.status', 'ca_representation_annotations.access', 'ca_representation_annotations.props', 'ca_representation_annotations.representation_id');
						
						$vs_element = $this->getRepresentationAnnotationHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);	

						break;
					# -------------------------------
					default:
						$vs_element = "'{$ps_bundle_name}' is not a valid related-table bundle name";
						break;
					# -------------------------------
				}
				
				if (!$vs_label_text) { $vs_label_text = $va_info['label']; }				
				$vs_label = '<span class="formLabelText" id="'.$pa_options['formName'].'_'.$ps_placement_code.'">'.$vs_label_text.'</span>'; 
				
				$vs_description = (isset($pa_bundle_settings['description'][$g_ui_locale]) && $pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale] : null;
				
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
					// This bundle is only available when editing objects of type ca_representation_annotations
					case 'ca_representation_annotation_properties':
						if ($vb_batch) { return null; } // not supported in batch mode
						foreach($this->getPropertyList() as $vs_property) {
							$vs_element .= $this->getPropertyHTMLFormBundle($pa_options['request'], $vs_property, $pa_options);
						}
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_sets
					case 'ca_set_items':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getSetItemHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_sets':
						require_once(__CA_MODELS_DIR__."/ca_sets.php");	// need to include here to avoid dependency errors on parse/compile
						$t_set = new ca_sets();
						$vs_element .= $t_set->getItemSetMembershipHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_editor_uis
					case 'ca_editor_ui_screens':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getScreenHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_editor_uis
					case 'ca_editor_ui_type_restrictions':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (($t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->get('editor_type'), true)) && (is_subclass_of($t_instance, 'BaseRelationshipModel'))) { return null; } // interstitial forms don't support UI type restrictions
						$vs_element .= $this->getTypeRestrictionsHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_editor_ui_screens
					case 'ca_editor_ui_screen_type_restrictions':
						$t_editor = new ca_editor_uis($this->get('ui_id'));
						if (
							$t_editor && ($t_instance = $this->getAppDatamodel()->getInstanceByTableNum($t_editor->get('editor_type'), true)) && (is_subclass_of($t_instance, 'BaseRelationshipModel'))) { return null; } // interstitial forms don't support UI type restrictions
						$vs_element .= $this->getTypeRestrictionsHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_editor_ui_screens
					case 'ca_editor_ui_bundle_placements':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getPlacementsHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_tours
					case 'ca_tour_stops_list':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getTourStopHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// Hierarchy navigation bar for hierarchical tables
					case 'hierarchy_navigation':
						if ($vb_batch) { return null; } // not supported in batch mode
						if ($this->isHierarchical()) {
							$vs_element .= $this->getHierarchyNavigationHTMLFormBundle($pa_options['request'], $pa_options['formName'], array(), $pa_bundle_settings, $pa_options);
						}
						break;
					# -------------------------------
					// Hierarchical item location control
					case 'hierarchy_location':
						if ($vb_batch) { return null; } // not supported in batch mode
						if ($this->isHierarchical()) {
							$vs_element .= $this->getHierarchyLocationHTMLFormBundle($pa_options['request'], $pa_options['formName'], array(), $pa_bundle_settings, $pa_options);
						}
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_search_forms
					case 'ca_search_form_placements':
						if ($vb_batch) { return null; } // not supported in batch mode
						//if (!$this->getPrimaryKey()) { return ''; }
						$vs_element .= $this->getSearchFormHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_bundle_displays
					case 'ca_bundle_display_placements':
						if ($vb_batch) { return null; } // not supported in batch mode
						//if (!$this->getPrimaryKey()) { return ''; }
						$vs_element .= $this->getBundleDisplayHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_bundle_displays
					case 'ca_bundle_display_type_restrictions':
						$vs_element .= $this->getTypeRestrictionsHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// 
					case 'ca_users':
						if (!$pa_options['request']->user->canDoAction('is_administrator') && ($pa_options['request']->getUserID() != $this->get('user_id'))) { return ''; }	// don't allow setting of per-user access if user is not owner
						$vs_element .= $this->getUserHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_options);
						break;
					# -------------------------------
					// 
					case 'ca_user_groups':
						if (!$pa_options['request']->user->canDoAction('is_administrator') && ($pa_options['request']->getUserID() != $this->get('user_id'))) { return ''; }	// don't allow setting of group access if user is not owner
						$vs_element .= $this->getUserGroupHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $this->tableNum(), $this->getPrimaryKey(), $pa_options['request']->getUserID(), $pa_options);
						break;
					# -------------------------------
					case 'settings':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getHTMLSettingFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representations_media_display':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getMediaDisplayHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representation_captions':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$this->representationIsOfClass("video")) { return ''; }
						$vs_element .= $this->getCaptionHTMLFormBundle($pa_options['request'], $pa_options['formName'], $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available for objects
					case 'ca_commerce_order_history':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!$pa_options['request']->user->canDoAction('can_manage_clients')) { break; }
						$vs_label_text = ($pa_bundle_settings['order_type'][0] == 'O') ? _t('Order history') : _t('Loan history');
						$vs_element .= $this->getCommerceOrderHistoryHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);
						break;
					# -------------------------------
					// This bundle is only available for relationships that include an object on one end
					case 'ca_object_representation_chooser':
						if ($vb_batch) { return null; } // not supported in batch mode
						$vs_element .= $this->getRepresentationChooserHTMLFormBundle($pa_options['request'], $pa_options['formName'].'_'.$ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);
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
				
				$vs_description = (isset($pa_bundle_settings['description'][$g_ui_locale]) && $pa_bundle_settings['description'][$g_ui_locale]) ? $pa_bundle_settings['description'][$g_ui_locale] : null;
				
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
			$vs_documentation_link = "<a class='bundleDocumentationLink' href='$vs_documentation_url' target='_blank'>documentation</a>";
		}

		$vs_output = str_replace("^ELEMENT", $vs_element, $vs_display_format);
		$vs_output = str_replace("^ERRORS", join('; ', $va_errors), $vs_output);
		$vs_output = str_replace("^LABEL", $vs_label, $vs_output);
		$vs_output = str_replace("^DOCUMENTATIONLINK", $vs_documentation_link, $vs_output);

		$ps_bundle_label = $vs_label_text;
		
		return $vs_output;
	}
	# ------------------------------------------------------
	public function getBundleList($pa_options=null) {
		if (isset($pa_options['includeBundleInfo']) && $pa_options['includeBundleInfo']) { 
			return $this->BUNDLES;
		}
		return array_keys($this->BUNDLES);
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
 		return isset($this->BUNDLES[$ps_bundle_name]) ? $this->BUNDLES[$ps_bundle_name] : null;
 	}
 	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display label for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  */
	public function getDisplayLabel($ps_field) {
		$va_tmp = explode('.', $ps_field);
		if ((sizeof($va_tmp) == 2) && ($va_tmp[0] == $this->getLabelTableName()) && ($va_tmp[1] == $this->getLabelDisplayField())) {
			$va_tmp[0] = $this->tableName();
			$va_tmp[1] = 'preferred_labels';
			$ps_field = join('.', $va_tmp);
		}

		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true)) {
					return _t("Related %1", $t_instance->getProperty('NAME_PLURAL'));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true))) { break; }
				$vs_prefix = $vs_suffix = '';
				$vs_suffix_string = ' ('._t('from related %1', $t_instance->getProperty('NAME_PLURAL')).')';
				if ($va_tmp[0] !== $this->tableName()) {
					$vs_suffix = $vs_suffix_string;
				}
				switch($va_tmp[1]) {
					# --------------------
					case 'related':
						unset($va_tmp[1]);
						$vs_label = $this->getDisplayLabel(join('.', $va_tmp));
						if ($va_tmp[0] != $this->tableName()) {
							return $vs_label.$vs_suffix_string;
						} 
						return $vs_label;
						break;
					# --------------------
					case 'preferred_labels':		
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return unicode_ucfirst($t_label_instance->getProperty('NAME_PLURAL')).$vs_suffix;
							} else {
								return unicode_ucfirst($t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2])).$vs_suffix;
							}
						}
						break;
					# --------------------
					case 'nonpreferred_labels':
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if ($va_tmp[0] !== $this->tableName()) {
								$vs_suffix = ' ('._t('alternates from related %1', $t_instance->getProperty('NAME_PLURAL')).')';
							} else {
								$vs_suffix = ' ('._t('alternates').')';
							}
							if (!isset($va_tmp[2])) {
								return unicode_ucfirst($t_label_instance->getProperty('NAME_PLURAL')).$vs_suffix;
							} else {
								return unicode_ucfirst($t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2])).$vs_suffix;
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
							return unicode_ucfirst($t_instance->getDisplayLabel($ps_field)).$vs_suffix;
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
		
		return parent::getDisplayLabel($ps_field);
	}
	# --------------------------------------------------------------------------------------------
	/**
	  * Returns display description for element specified by standard "get" bundle code (eg. <table_name>.<bundle_name> format)
	  */
	public function getDisplayDescription($ps_field) {
		$va_tmp = explode('.', $ps_field);
		if ((sizeof($va_tmp) == 2) && ($va_tmp[0] == $this->getLabelTableName()) && ($va_tmp[1] == $this->getLabelDisplayField())) {
			$va_tmp[0] = $this->tableName();
			$va_tmp[1] = 'preferred_labels';
			$ps_field = join('.', $va_tmp);
		}

		switch(sizeof($va_tmp)) {
			# -------------------------------------
			case 1:		// table_name
				if ($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true)) {
					return _t("A list of related %1", $t_instance->getProperty('NAME_PLURAL'));
				}
				break;
			# -------------------------------------
			case 2:		// table_name.field_name
			case 3:		// table_name.field_name.sub_element	
				if (!($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true))) { return null; }
				
				$vs_suffix = '';
				if ($va_tmp[0] !== $this->tableName()) {
					$vs_suffix = ' '._t('from related %1', $t_instance->getProperty('NAME_PLURAL'));
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
								return _t('A list of %1 %2', $t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2]), $vs_suffix);
							}
						}
						break;
					# --------------------
					case 'nonpreferred_labels':						
						if (method_exists($t_instance, 'getLabelTableInstance') && ($t_label_instance = $t_instance->getLabelTableInstance())) {
							if (!isset($va_tmp[2])) {
								return _t('A list of alternate %1 %2', $t_label_instance->getProperty('NAME_PLURAL'), $vs_suffix);
							} else {
								return _t('A list of alternate %1 %2', $t_label_instance->getDisplayLabel($t_label_instance->tableName().'.'.$va_tmp[2]), $vs_suffix);
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
							return _t('A list of %1 %2', $t_instance->getDisplayLabel($ps_field), $vs_suffix);
						}
						break;
					# --------------------
				}	
					
				break;
			# -------------------------------------
		}
		
		return parent::getDisplayDescription($ps_field);
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
		$va_tmp = explode('.', $ps_field);
		
		if (!in_array($va_tmp[0], array('created', 'modified'))) {
			switch(sizeof($va_tmp)) {
				# -------------------------------------
				case 1:		// table_name
					if ($va_tmp[0] != $this->tableName()) {
						if (!is_array($pa_options)) { $pa_options = array(); }
						if (!isset($pa_options['width'])) { $pa_options['width'] = 30; }
						if (!isset($pa_options['values'])) { $pa_options['values'] = array(); }
						if (!isset($pa_options['values'][$ps_field])) { $pa_options['values'][$ps_field] = ''; }
					
						return caHTMLTextInput($ps_field, array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'id' => str_replace('.', '_', $ps_field)));
					}
					break;
				# -------------------------------------
				case 2:		// table_name.field_name
				case 3:		// table_name.field_name.sub_element	
					if (!($t_instance = $this->_DATAMODEL->getInstanceByTableName($va_tmp[0], true))) { return null; }
					
					switch($va_tmp[1]) {
						# --------------------
						case 'preferred_labels':		
						case 'nonpreferred_labels':
							return caHTMLTextInput($ps_field, array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'id' => str_replace('.', '_', $ps_field)));
							break;
						# --------------------
						default:
							if ($va_tmp[0] != $this->tableName()) {
								switch(sizeof($va_tmp)) {
									case 1:
										return caHTMLTextInput($ps_field, array('value' => $pa_options['values'][$ps_field], 'size' => $pa_options['width'], 'id' => str_replace('.', '_', $ps_field)));
									case 2:
									case 3:
										return $t_instance->htmlFormElementForSearch($po_request, $ps_field, $pa_options);
										break;
								}
							}
							break;
						# --------------------
					}	
						
					break;
				# -------------------------------------
			}
		}
		
		return parent::htmlFormElementForSearch($po_request, $ps_field, $pa_options);
	}
 	# ------------------------------------------------------
 	/**
 	 * Returns a list of HTML fragments implementing all bundles in an HTML form for the specified screen
 	 * $pm_screen can be a screen tag (eg. "Screen5") or a screen_id (eg. 5) 
 	 *
 	 * @param mixed $pm_screen screen_id or code in default UI to return bundles for
 	 * @param array $pa_options Array of options. Supports and option getBundleFormHTML() supports plus:
 	 *		request = the current request object; used to apply user privs to bundle generation
 	 *		force = list of bundles to force onto form if they are not included in the UI; forced bundles will be included at the bottom of the form
 	 *		forceHidden = list of *intrinsic* fields to force onto form as hidden <input> elements if they are not included in the UI; NOTE: only intrinsic fields may be specified
 	 *		omit = list of bundles to omit from form in the event they are included in the UI
 	 *		restrictToTypes = 
 	 *	@return array List of bundle HTML to display in form, keyed on placement code
 	 */
 	public function getBundleFormHTMLForScreen($pm_screen, $pa_options, &$pa_placements=null) {
 		$va_omit_bundles = (isset($pa_options['omit']) && is_array($pa_options['omit'])) ? $pa_options['omit'] : array();
 		
 		if (isset($pa_options['ui_instance']) && ($pa_options['ui_instance'])) {
 			$t_ui = $pa_options['ui_instance'];
 		} else {
 			$t_ui = ca_editor_uis::loadDefaultUI($this->tableName(), $pa_options['request'], $this->getTypeID());
 		}
 		if (!$t_ui) { return false; }
 		if (isset($pa_options['bundles']) && is_array($pa_options['bundles'])) {
 			$va_bundles = $pa_options['bundles'];
 		} else {
 			$va_bundles = $t_ui->getScreenBundlePlacements($pm_screen);
 		}
 
 		$va_bundle_html = array();
 		
 		$vn_pk_id = $this->getPrimaryKey();
		
		$va_bundles_present = array();
		if (is_array($va_bundles)) {
			if (is_subclass_of($this, 'BaseRelationshipModel')) {
				$vs_type_id_fld = $this->getTypeFieldName();
				if(isset($pa_options['restrictToTypes']) && is_array($pa_options['restrictToTypes'])) {
					$va_valid_element_codes = $this->getApplicableElementCodesForTypes(caMakeRelationshipTypeIDList($this->tableName(), $pa_options['restrictToTypes']));
				} else {
					$va_valid_element_codes = null;
				}
			} else {
				$vs_type_id_fld = isset($this->ATTRIBUTE_TYPE_ID_FLD) ? $this->ATTRIBUTE_TYPE_ID_FLD : null;
			
			
				$vs_hier_parent_id_fld = isset($this->HIERARCHY_PARENT_ID_FLD) ? $this->HIERARCHY_PARENT_ID_FLD : null;
			
				if(isset($pa_options['restrictToTypes']) && is_array($pa_options['restrictToTypes'])) {
					$va_valid_element_codes = $this->getApplicableElementCodesForTypes(caMakeTypeIDList($this->tableName(), $pa_options['restrictToTypes']));
				} else {
					$va_valid_element_codes = null;
				}
			}
			
			$vn_c = 0;
			foreach($va_bundles as $va_bundle) {
				if ($va_bundle['bundle_name'] === $vs_type_id_fld) { continue; }	// skip type_id
				if ((!$vn_pk_id) && ($va_bundle['bundle_name'] === $vs_hier_parent_id_fld)) { continue; }
				if (in_array($va_bundle['bundle_name'], $va_omit_bundles)) { continue; }
				
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
				
				$va_bundle['settings']['placement_id'] = $va_bundle['placement_id'];
				if ($vs_bundle_form_html = $this->getBundleFormHTML($va_bundle['bundle_name'], $va_bundle['placement_code'], $va_bundle['settings'], $pa_options, $vs_bundle_display_name)) {
					$va_bundle_html[$va_bundle['placement_code']] = "<a name=\"{$pm_screen}_{$vn_c}\"></a>{$vs_bundle_form_html}";
					$va_bundles_present[$va_bundle['bundle_name']] = true;
					
					$pa_placements["{$pm_screen}_{$vn_c}"] = array(
						'name' => $vs_bundle_display_name ? $vs_bundle_display_name : $this->getDisplayLabel($this->tableName().".".$va_bundle['bundle_name'])
					);
				}
				$vn_c++;
			}
		}
		
		// is this a form to create a new item?
		if (!$vn_pk_id) {
			// auto-add mandatory fields if this is a new object
			$va_mandatory_fields = $this->getMandatoryFields();
			foreach($va_mandatory_fields as $vs_field) {
				if (!isset($va_bundles_present[$vs_field]) || !$va_bundles_present[$vs_field]) {
					$va_bundle_html[$vs_field] = $this->getBundleFormHTML($vs_field, 'mandatory_'.$vs_field, array(), $pa_options);
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
						$va_bundle_html['_force_'.$vs_bundle] = $this->getBundleFormHTML($vs_bundle, 'force_'.$vs_field, array(), $pa_options);
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
		
 		return $va_bundle_html;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
	public function getHierarchyNavigationHTMLFormBundle($po_request, $ps_form_name, $pa_options=null, $pa_bundle_settings=null) {
	
 		$o_view = $this->_getHierarchyLocationHTMLFormBundleInfo($po_request, $ps_form_name, $pa_options, $pa_bundle_settings);
		return $o_view->render('hierarchy_navigation.php');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getHierarchyLocationHTMLFormBundle($po_request, $ps_form_name, $pa_options=null, $pa_bundle_settings=null) {
		
 		$o_view = $this->_getHierarchyLocationHTMLFormBundleInfo($po_request, $ps_form_name, $pa_options, $pa_bundle_settings);
		return $o_view->render('hierarchy_location.php');
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _getHierarchyLocationHTMLFormBundleInfo($po_request, $ps_form_name, $pa_options=null, $pa_bundle_settings=null) {
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }
		
		if (!($vs_label_table_name = $this->getLabelTableName())) { return ''; }
		
		$o_view->setVar('id_prefix', $ps_form_name);
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
				$va_ancestor_list = array();
			}
		} else {
			if (!($va_ancestor_list = $this->getHierarchyAncestors($vn_id, array(
				'additionalTableToJoin' => $vs_label_table_name, 
				'additionalTableJoinType' => 'LEFT',
				'additionalTableSelectFields' => array($vs_display_fld, 'locale_id'),
				'includeSelf' => true
			)))) {
				$va_ancestor_list = array();
			}
		}
		
		
		$va_ancestors_by_locale = array();
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
		
		$o_view->setVar('object_collection_collection_ancestors', array()); // collections to display as object parents when ca_objects_x_collections_hierarchy_enabled is enabled
		if (($this->tableName() == 'ca_objects') && $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled')) {
			$vs_object_collection_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type');
			// Is object part of a collection?
			
			$va_object_ids = array_keys($va_ancestor_list);
			$vn_top_object_id = array_shift($va_object_ids);
			if ($vn_top_object_id != $this->getPrimaryKey()) { 
				$t_object = $this->getAppDatamodel()->getInstanceByTableName("ca_objects", true);
				$t_object->load($vn_top_object_id); 
			} else { 
				$t_object = $this;
			}
			if(is_array($va_collections = $t_object->getRelatedItems('ca_collections', array('restrictToRelationshipTypes' => array($vs_object_collection_rel_type))))) {
				$va_related_collections_by_level = array();
				foreach($va_collections as $vn_relation_id => $va_collection) {
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
						$va_collection_ancestor_list = array();
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
		
		
		$o_view->setVar('parent_id', $vn_parent_id);
		$o_view->setVar('ancestors', $va_ancestor_list);
		$o_view->setVar('id', $this->getPrimaryKey());
		$o_view->setVar('settings', $pa_bundle_settings);
		
		return $o_view;
	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
	public function getRelatedHTMLFormBundle($po_request, $ps_form_name, $ps_related_table, $ps_placement_code=null, $pa_bundle_settings=null, $pa_options=null) {
		global $g_ui_locale;
		
		JavascriptLoadManager::register('sortableUI');
		
		if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }
		if(!is_array($pa_options)) { $pa_options = array(); }
		
		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		$t_item = $this->getAppDatamodel()->getTableInstance($ps_related_table);
		
		$vb_is_many_many = false;
		
		$va_path = array_keys($this->getAppDatamodel()->getPath($this->tableName(), $ps_related_table));
		if ($this->tableName() == $ps_related_table) {
			// self relationship
			$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
			$vb_is_many_many = true;
		} else {
			switch(sizeof($va_path)) {
				case 3:
					// many-many relationship
					$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
					$vb_is_many_many = true;
					break;
				case 2:
					// many-one relationship
					$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
					break;
				default:
					$t_item_rel = null;
					break;
			}
		}
	
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('t_instance', $this);
		$o_view->setVar('t_item', $t_item);
		$o_view->setVar('t_item_rel', $t_item_rel);
		
		$vb_read_only = ($po_request->user->getBundleAccessLevel($this->tableName(), $ps_related_table) == __CA_BUNDLE_ACCESS_READONLY__) ? true : false;
		if (!$pa_bundle_settings['readonly']) { $pa_bundle_settings['readonly'] = (!isset($pa_bundle_settings['readonly']) || !$pa_bundle_settings['readonly']) ? $vb_read_only : true;	}
		
		// pass bundle settings
		$o_view->setVar('settings', $pa_bundle_settings);
		$o_view->setVar('graphicsPath', $pa_options['graphicsPath']);
		
		// pass placement code
		$o_view->setVar('placement_code', $ps_placement_code);
		
		$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
		
		$t_label = null;
		if ($t_item->getLabelTableName()) {
			$t_label = $this->_DATAMODEL->getInstanceByTableName($t_item->getLabelTableName(), true);
		}
		if (method_exists($t_item_rel, 'getRelationshipTypes')) {
			$o_view->setVar('relationship_types', $t_item_rel->getRelationshipTypes(null, null,  array_merge($pa_options, $pa_bundle_settings)));
			$o_view->setVar('relationship_types_by_sub_type', $t_item_rel->getRelationshipTypesBySubtype($this->tableName(), $this->get('type_id'),  array_merge($pa_options, $pa_bundle_settings)));
		}
		$o_view->setVar('t_subject', $this);
		
		
		$va_initial_values = array();
		$va_get_related_opts = array_merge($pa_options, $pa_bundle_settings);
		if (isset($pa_bundle_settings['restrictToTermsRelatedToCollection']) && $pa_bundle_settings['restrictToTermsRelatedToCollection']) {
			$va_get_related_opts['restrict_to_relationship_types'] = $pa_bundle_settings['restrictToTermsOnCollectionUseRelationshipType'];
		}
		
		if ($pa_bundle_settings['sort']) {
			$va_get_related_opts['sort'] = $pa_bundle_settings['sort'];
			$va_get_related_opts['sortDirection'] = $pa_bundle_settings['sortDirection'];
		}
		if (sizeof($va_items = $this->getRelatedItems($ps_related_table, $va_get_related_opts))) {
			$t_rel = $this->getAppDatamodel()->getInstanceByTableName($ps_related_table, true);
			$vs_rel_pk = $t_rel->primaryKey();
			
			$va_opts = array('relatedItems' => $va_items, 'stripTags' => true);
			if ($vb_is_many_many) {
				$va_ids = caExtractArrayValuesFromArrayOfArrays($va_items, 'relation_id');
				$qr_rel_items = $t_item->makeSearchResult($t_item_rel->tableNum(), $va_ids);
				$va_opts['table'] = $t_rel->tableName();
				$va_opts['primaryKey'] = $t_rel->primaryKey();
			} else {
				$va_ids = caExtractArrayValuesFromArrayOfArrays($va_items, $vs_rel_pk);
				$qr_rel_items = $t_item->makeSearchResult($t_rel->tableNum(), $va_ids);	
			}
				
			$va_opts['template'] = caGetBundleDisplayTemplate($this, $ps_related_table, $pa_bundle_settings);
			$va_opts['primaryIDs'] = array($this->tableName() => array($this->getPrimaryKey()));
			$va_initial_values = caProcessRelationshipLookupLabel($qr_rel_items, $t_item_rel, $va_opts);
		}

		$va_force_new_values = array();
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
	 *
	 */
	protected function getBundleListsForScreen($pm_screen, $po_request, $t_ui, $pa_options=null) {
		if(!$t_ui) { return; }
		$va_bundles = $t_ui->getScreenBundlePlacements($pm_screen);
		
		// sort fields by type
		$va_fields_by_type = array();
		if (is_array($va_bundles)) {
			foreach($va_bundles as $vn_i => $va_tmp) {
				if (isset($va_tmp['settings']['readonly']) && (bool)$va_tmp['settings']['readonly']) { continue; }			// don't attempt to save "read-only" bundles
				
				if (($po_request->user->getBundleAccessLevel($this->tableName(), $va_tmp['bundle_name'])) < __CA_BUNDLE_ACCESS_EDIT__) {	// don't save bundles use doesn't have edit access for
					continue;
				}
				$va_info = $this->getBundleInfo($va_tmp['bundle_name']);
				$va_fields_by_type[$va_info['type']][$va_tmp['placement_code']] = $va_tmp['bundle_name'];
			}
		}
			
		// auto-add mandatory fields if this is a new object
		if (!is_array($va_fields_by_type['intrinsic'])) { $va_fields_by_type['intrinsic'] = array(); }
		if (!$this->getPrimaryKey()) {
			if (is_array($va_mandatory_fields = $this->getMandatoryFields())) {
				foreach($va_mandatory_fields as $vs_field) {
					if (!in_array($vs_field, $va_fields_by_type['intrinsic'])) {
						$va_fields_by_type['intrinsic'][] = $vs_field;
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
		
		$va_values = array();
		
		// Get intrinsics
		$va_values['intrinsic'] = array();
		if (is_array($va_bundle_lists['fields_by_type']['intrinsic'])) {
			$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
			foreach($va_bundle_lists['fields_by_type']['intrinsic'] as $vs_f) {
				
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
		$va_attributes_by_element = $va_attributes = array();
		$vs_form_prefix = $po_request->getParameter('_formName', pString);
		
		if (is_array($va_bundle_lists['fields_by_type']['attribute'])) {
			foreach($va_bundle_lists['fields_by_type']['attribute'] as $vs_placement_code => $vs_f) {
				
				$va_attributes_to_insert = array();
				$va_attributes_to_delete = array();
				$va_locales = array();
				foreach($_REQUEST as $vs_key => $vs_val) {
					$vs_element_set_code = preg_replace("/^ca_attribute_/", "", $vs_f);
					
					$t_element = $this->_getElementInstance($vs_element_set_code);
					$vn_element_id = $t_element->getPrimaryKey();
					
					if (
						preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\d\-_]+)_new_([\d]+)/', $vs_key, $va_matches)
						||
						preg_match('/'.$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_([\w\d\-_]+)_([\d]+)/', $vs_key, $va_matches)
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
		$va_preferred_labels = array();
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
		
		return $va_values;
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
	* @param RequestHTTP $ps_request
	* @param array $pa_options Options are:
	*		dryRun = Go through the motions of saving but don't actually write information to the database
	*		batch = Process save in "batch" mode. Specifically this means honoring batch mode settings (add, replace, remove), skipping bundles that are not supported in batch mode and ignoring updates
	*		existingRepresentationMap = an array of representation_ids key'ed on file path. If set saveBundlesForScreen() use link the specified representation to the row it is saving rather than processing the uploaded file. saveBundlesForScreen() will build the map as it goes, adding newly uploaded files. If you want it to process a file in a batch situation where it should be processed the first time and linked subsequently then pass an empty array here. saveBundlesForScreen() will use the empty array to build the map.
	*/
	public function saveBundlesForScreen($pm_screen, $po_request, &$pa_options) {
		$vb_we_set_transaction = false;
		
		$vs_form_prefix = $po_request->getParameter('_formName', pString);
		
		$vb_dryrun = (isset($pa_options['dryRun']) && $pa_options['dryRun']) ? true : false;
		$vb_batch = (isset($pa_options['batch']) && $pa_options['batch']) ? true : false;
		
		if (!$this->inTransaction()) {
			$this->setTransaction(new Transaction($this->getDb()));
			$vb_we_set_transaction = true;
		} else {
			if ($vb_dryrun) {
				$this->postError(799, _t('Cannot do dry run save when in transaction. Try again without setting a transaction.'), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");				
				
				return false;
			}
		}

		BaseModel::setChangeLogUnitID();
		// get items on screen
		if (isset($pa_options['ui_instance']) && ($pa_options['ui_instance'])) {
 			$t_ui = $pa_options['ui_instance'];
 		} else {
			$t_ui = ca_editor_uis::loadDefaultUI($this->tableName(), $po_request, $this->getTypeID());
 		}
		
		$va_bundle_lists = $this->getBundleListsForScreen($pm_screen, $po_request, $t_ui, $pa_options);
		
		$va_bundles = $va_bundle_lists['bundles'];
		$va_fields_by_type = $va_bundle_lists['fields_by_type'];
	
		// save intrinsic fields
		if (is_array($va_fields_by_type['intrinsic'])) {
			$vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD');
			foreach($va_fields_by_type['intrinsic'] as $vs_f) {
				if ($vb_batch) { 
					$vs_batch_mode = $po_request->getParameter("{$vs_f}_batch_mode", pString);
					if($vs_batch_mode == '_disabled_') { continue; }
				}
				if (isset($_FILES[$vs_f]) && $_FILES[$vs_f]) {
					// media field
					$this->set($vs_f, $_FILES[$vs_f]['tmp_name'], array('original_filename' => $_FILES[$vs_f]['name']));
				} else {
					switch($vs_f) {
						case $vs_idno_field:
							if ($this->opo_idno_plugin_instance) {
								$this->opo_idno_plugin_instance->setDb($this->getDb());
								$this->set($vs_f, $vs_tmp = $this->opo_idno_plugin_instance->htmlFormValue($vs_idno_field));
							} else {
								$this->set($vs_f, $po_request->getParameter($vs_f, pString));
							}
							break;
						default:
							$this->set($vs_f, $po_request->getParameter($vs_f, pString));
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
		$va_inserted_attributes_by_element = array();
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
			$va_inserted_attributes = array();
			$reserved_elements = array();
			foreach($va_fields_by_type['attribute'] as $vs_placement_code => $vs_f) {
				$vs_element_set_code = preg_replace("/^ca_attribute_/", "", $vs_f);
				//does the attribute's datatype have a saveElement method - if so, use that instead
				$vs_element = $this->_getElementInstance($vs_element_set_code);
				$vn_element_id = $vs_element->getPrimaryKey();
				$vs_element_datatype = $vs_element->get('datatype');
				$vs_datatype = Attribute::getValueInstance($vs_element_datatype);
				if(method_exists($vs_datatype,'saveElement')) {
					$reserved_elements[] = $vs_element;
					continue;
				}
				
				$va_attributes_to_insert = array();
				$va_attributes_to_delete = array();
				$va_locales = array();
				
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
									continue;
									break;
							}
						}
						
						$vn_c = intval($va_matches[2]);
						// yep - grab the locale and value
						$vn_locale_id = isset($_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_'.$vn_c]) ? $_REQUEST[$vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_id.'_locale_id_new_'.$vn_c] : null;
						
						$va_inserted_attributes_by_element[$vn_element_id][$vn_c]['locale_id'] = $va_attributes_to_insert[$vn_c]['locale_id'] = $vn_locale_id; 
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
					$this->addAttribute($va_attribute_to_insert, $vn_element_id, $vs_f);
				}
				
if (!$vb_batch) {					
				// check for attributes to update
				if (is_array($va_attrs = $this->getAttributesByElement($vn_element_id))) {
					$t_element = new ca_metadata_elements();
							
					$va_attrs_update_list = array();
					foreach($va_attrs as $o_attr) {
						$this->clearErrors();
						$vn_attribute_id = $o_attr->getAttributeID();
						if (isset($va_inserted_attributes[$vn_attribute_id]) && $va_inserted_attributes[$vn_attribute_id]) { continue; }
						if (isset($va_attributes_to_delete[$vn_attribute_id]) && $va_attributes_to_delete[$vn_attribute_id]) { continue; }
						
						$vn_element_set_id = $o_attr->getElementID();
						
						$va_attr_update = array(
							'locale_id' =>  $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_locale_id_'.$vn_attribute_id, pString) 
						);
						
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
						foreach($va_sub_elements as $vn_i => $va_element_info) {
							if ($va_element_info['datatype'] == 0) { continue; }
							//$vn_element_id = $o_attr_val->getElementID();
							$vn_element_id = $va_element_info['element_id'];
							
							$vs_k = $vs_placement_code.$vs_form_prefix.'_attribute_'.$vn_element_set_id.'_'.$vn_element_id.'_'.$vn_attribute_id;
							if (isset($_FILES[$vs_k]) && ($va_val = $_FILES[$vs_k])) {
								if ($va_val['size'] > 0) {	// is there actually a file?
									$va_val['_uploaded_file'] = true;
									$va_attr_update[$vn_element_id] = $va_val;
									continue;
								}
							} 
							$vs_attr_val = $po_request->getParameter($vs_k, pString);
							$va_attr_update[$vn_element_id] = $vs_attr_val;
						}
						
						$this->clearErrors();
						$this->editAttribute($vn_attribute_id, $vn_element_set_id, $va_attr_update, $vs_f);
					}
				}
			}
		}
}
		
if (!$vb_batch) {		// hierarchy moves are not supported in batch mode

		$va_parent_tmp = explode("-", $po_request->getParameter($vs_form_prefix.'HierLocation_new_parent_id', pString));
		
		// Hierarchy browser sets new_parent_id param to "X" if user wants to extract item from hierarchy
		$vn_parent_id = (($vn_parent_id = array_pop($va_parent_tmp)) == 'X') ? -1 : (int)$vn_parent_id;
		if (sizeof($va_parent_tmp) > 0) { $vs_parent_table = array_pop($va_parent_tmp); } else { $vs_parent_table = $this->tableName(); }
		
		if ($this->getPrimaryKey() && $this->HIERARCHY_PARENT_ID_FLD && ($vn_parent_id > 0)) {
			
			if ($vs_parent_table == $this->tableName()) {
				$this->set($this->HIERARCHY_PARENT_ID_FLD, $vn_parent_id);
			} else {
				if ((bool)$this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && ($vs_parent_table == 'ca_collections') && ($this->tableName() == 'ca_objects') && ($vs_coll_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type'))) {
					// link object to collection
					$this->removeRelationships('ca_collections', $vs_coll_rel_type);
					$this->set($this->HIERARCHY_PARENT_ID_FLD, null);
					$this->set($this->HIERARCHY_ID_FLD, $this->getPrimaryKey());
					if (!($this->addRelationship('ca_collections', $vn_parent_id, $vs_coll_rel_type))) {
						$this->postError(2510, _t('Could not move object under collection: %1', join("; ", $this->getErrors())), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
					}
				}
			}
		} else {
			if ($this->getPrimaryKey() && $this->HIERARCHY_PARENT_ID_FLD && ($this->HIERARCHY_TYPE == __CA_HIER_TYPE_ADHOC_MONO__) && isset($_REQUEST[$vs_form_prefix.'HierLocation_new_parent_id']) && ($vn_parent_id <= 0)) {
				$this->set($this->HIERARCHY_PARENT_ID_FLD, null);
				$this->set($this->HIERARCHY_ID_FLD, $this->getPrimaryKey());
				
				// Support for collection-object cross-table hierarchies
				if ((bool)$this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && ($this->tableName() == 'ca_objects') && ($vs_coll_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type')) && ($vn_parent_id == -1)) {	// -1 = extract from hierarchy
					$this->removeRelationships('ca_collections', $vs_coll_rel_type);
				}
			}
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
		
		$this->setMode(ACCESS_WRITE);
			
		$vb_is_insert = false;
		
		if ($this->getPrimaryKey()) {
			$this->update();
		} else {
			$this->insert();
			$vb_is_insert = true;
		}
		if ($this->numErrors() > 0) {
			$va_errors = array();
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
			 	if ($vb_we_set_transaction) { $this->removeTransaction(false); }
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
			foreach($va_fields_by_type['preferred_label'] as $vs_placement_code => $vs_f) {

if (!$vb_batch) {	
				// check for existing labels to update (or delete)
				$va_preferred_labels = $this->getPreferredLabels(null, false);
				foreach($va_preferred_labels as $vn_item_id => $va_labels_by_locale) {
					foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
						foreach($va_label_list as $va_label) {
							if ($vn_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_'.$va_label['label_id'], pString)) {
							
								if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, $va_label['label_id'], true))) {
									
									if ($vb_check_for_dupe_labels && $this->checkForDupeLabel($vn_label_locale_id, $va_label_values)) {
										$this->postError(1125, _t('Value <em>%1</em> is already used and duplicates are not allowed', join("/", $va_label_values)), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
										$po_request->addActionErrors($this->errors(), 'preferred_labels');
										continue;
									}
									$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'type_id_'.$va_label['label_id'], pInteger);
									$this->editLabel($va_label['label_id'], $va_label_values, $vn_label_locale_id, $vn_label_type_id, true);
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
								}
							} else {
								if ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_PrefLabel_'.$va_label['label_id'].'_delete', pString)) {
									// delete
									$this->removeLabel($va_label['label_id']);
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
								continue;
							case '_delete_':		// remove all existing preferred labels
								$this->removeAllLabels(__CA_LABEL_TYPE_PREFERRED__);
								continue(2);
								break;
						}
					}
					$vn_c = intval($va_matches[1]);
					if ($vn_new_label_locale_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'locale_id_new_'.$vn_c, pString)) {
						if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, 'new_'.$vn_c, true))) {
							if ($vb_check_for_dupe_labels && $this->checkForDupeLabel($vn_new_label_locale_id, $va_label_values)) {
								$this->postError(1125, _t('Value <em>%1</em> is already used and duplicates are not allowed', join("/", $va_label_values)), "BundlableLabelableBaseModelWithAttributes->saveBundlesForScreen()");
								$po_request->addActionErrors($this->errors(), 'preferred_labels');
								$vb_error_inserting_pref_label = true;
								continue;
							}
							$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_Pref'.'type_id_new_'.$vn_c, pInteger);
							$this->addLabel($va_label_values, $vn_new_label_locale_id, $vn_label_type_id, true);	
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
								if (is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $vs_placement_code.$vs_form_prefix, $va_label['label_id'], false))) {
									$vn_label_type_id = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPref'.'type_id_'.$va_label['label_id'], pInteger);
									$this->editLabel($va_label['label_id'], $va_label_values, $vn_label_locale_id, $vn_label_type_id, false);
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
								}
							} else {
								if ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_NPrefLabel_'.$va_label['label_id'].'_delete', pString)) {
									// delete
									$this->removeLabel($va_label['label_id']);
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
							continue;
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
							$this->addLabel($va_label_values, $vn_new_label_locale_id, $vn_new_label_type_id, false);	
						
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
				$vn_table_num = $this->_DATAMODEL->getTableNum($vs_f);
				$vs_prefix_stub = $vs_placement_code.$vs_form_prefix.'_'.$vs_f.'_';
				
				// get settings
				$va_bundle_settings = array();
				foreach($va_bundles as $va_bundle_info) {
					if ($va_bundle_info['placement_code'] == $vs_placement_code) {
						$va_bundle_settings = $va_bundle_info['settings'];
						break;
					}
				}
				
				switch($vs_f) {
					# -------------------------------------
					case 'ca_object_representations':
						// check for existing representations to update (or delete)
						
						$vb_allow_fetching_of_urls = (bool)$this->_CONFIG->get('allow_fetching_of_media_from_remote_urls');
						$va_rep_ids_sorted = $va_rep_sort_order = explode(';',$po_request->getParameter($vs_prefix_stub.'ObjectRepresentationBundleList', pString));
						sort($va_rep_ids_sorted, SORT_NUMERIC);
						
						
						$va_reps = $this->getRepresentations();
						
						if (!$vb_batch && is_array($va_reps)) {
							foreach($va_reps as $vn_i => $va_rep) {
								$this->clearErrors();
								
								if (strlen($po_request->getParameter($vs_prefix_stub.'access_'.$va_rep['representation_id'], pInteger)) > 0) {
									if ($vb_allow_fetching_of_urls && ($vs_path = $_REQUEST[$vs_prefix_stub.'media_url_'.$va_rep['representation_id']])) {
										$va_tmp = explode('/', $vs_path);
										$vs_original_name = array_pop($va_tmp);
									} else {
										$vs_path = $_FILES[$vs_prefix_stub.'media_'.$va_rep['representation_id']]['tmp_name'];
										$vs_original_name = $_FILES[$vs_prefix_stub.'media_'.$va_rep['representation_id']]['name'];
									}
									
									$vn_is_primary = ($po_request->getParameter($vs_prefix_stub.'is_primary_'.$va_rep['representation_id'], pString) != '') ? $po_request->getParameter($vs_prefix_stub.'is_primary_'.$va_rep['representation_id'], pInteger) : null;
									$vn_locale_id = $po_request->getParameter($vs_prefix_stub.'locale_id_'.$va_rep['representation_id'], pInteger);
									$vn_access = $po_request->getParameter($vs_prefix_stub.'access_'.$va_rep['representation_id'], pInteger);
									$vn_status = $po_request->getParameter($vs_prefix_stub.'status_'.$va_rep['representation_id'], pInteger);
									$vs_rep_label = trim($po_request->getParameter($vs_prefix_stub.'rep_label_'.$va_rep['representation_id'], pString));
									//$vn_rep_type_id = $po_request->getParameter($vs_prefix_stub.'rep_type_id'.$va_rep['representation_id'], pInteger);
									
									$vn_rank = null;
									if (($vn_rank_index = array_search($va_rep['representation_id'], $va_rep_sort_order)) !== false) {
										$vn_rank = $va_rep_ids_sorted[$vn_rank_index];
									}
									
									$this->editRepresentation($va_rep['representation_id'], $vs_path, $vn_locale_id, $vn_status, $vn_access, $vn_is_primary, array(), array('original_filename' => $vs_original_name, 'rank' => $vn_rank));
									if ($this->numErrors()) {
										//$po_request->addActionErrors($this->errors(), $vs_f, $va_rep['representation_id']);
										foreach($this->errors() as $o_e) {
											switch($o_e->getErrorNumber()) {
												case 795:
													// field conflicts
													$po_request->addActionError($o_e, $vs_f, $va_rep['representation_id']);
													break;
												default:
													$po_request->addActionError($o_e, $vs_f, $va_rep['representation_id']);
													break;
											}
										}
									}
									
									if ($vs_rep_label) {
										//
										// Set representation label
										//
										$t_rep = new ca_object_representations();
										global $g_ui_locale_id;
										if ($t_rep->load($va_rep['representation_id'])) {
											$t_rep->setMode(ACCESS_WRITE);
											$t_rep->replaceLabel(array('name' => $vs_rep_label), $g_ui_locale_id, null, true);
											if ($t_rep->numErrors()) {
												$po_request->addActionErrors($t_rep->errors(), $vs_f, $va_rep['representation_id']);
											}
										}
									}
								} else {
									// is it a delete key?
									$this->clearErrors();
									if (($po_request->getParameter($vs_prefix_stub.$va_rep['representation_id'].'_delete', pInteger)) > 0) {
										// delete!
										$this->removeRepresentation($va_rep['representation_id']);
										if ($this->numErrors()) {
											$po_request->addActionErrors($this->errors(), $vs_f, $va_rep['representation_id']);
										}
									}
								}
							}
						}

						if ($vb_batch) {
							$vs_batch_mode = $_REQUEST[$vs_prefix_stub.'batch_mode'];
	
							if ($vs_batch_mode == '_disabled_') { break;}
							if ($vs_batch_mode == '_replace_') { $this->removeAllRepresentations();}
							if ($vs_batch_mode == '_delete_') { $this->removeAllRepresentations(); break; }
						}
					
						// check for new representations to add 
						foreach($_FILES as $vs_key => $vs_value) {
							$this->clearErrors();
							
							if (!preg_match('/^'.$vs_prefix_stub.'media_new_([\d]+)$/', $vs_key, $va_matches)) { continue; }
							
							if($vs_upload_type = $po_request->getParameter($vs_prefix_stub.'upload_typenew_'.$va_matches[1], pString)) {
								$po_request->user->setVar('defaultRepresentationUploadType', $vs_upload_type);
							}
							
							$vn_type_id = $po_request->getParameter($vs_prefix_stub.'type_id_new_'.$va_matches[1], pInteger);
							if ($vn_existing_rep_id = $po_request->getParameter($vs_prefix_stub.'idnew_'.$va_matches[1], pInteger)) {
								$this->addRelationship('ca_object_representations', $vn_existing_rep_id, $vn_type_id);
							} else {
								if ($vb_allow_fetching_of_urls && ($vs_path = $_REQUEST[$vs_prefix_stub.'media_url_new_'.$va_matches[1]])) {
									$va_tmp = explode('/', $vs_path);
									$vs_original_name = array_pop($va_tmp);
								} else {
									$vs_path = $_FILES[$vs_prefix_stub.'media_new_'.$va_matches[1]]['tmp_name'];
									$vs_original_name = $_FILES[$vs_prefix_stub.'media_new_'.$va_matches[1]]['name'];
								}
								if (!$vs_path) { continue; }
							
								$vn_rep_type_id = $po_request->getParameter($vs_prefix_stub.'rep_type_id_new_'.$va_matches[1], pInteger);
								if(!$vn_rep_type_id && !($vn_rep_type_id = caGetDefaultItemID('object_representation_types'))) {
									require_once(__CA_MODELS_DIR__.'/ca_lists.php');
									$t_list = new ca_lists();
									if (is_array($va_rep_type_ids = $t_list->getItemsForList('object_representation_types', array('idsOnly' => true, 'enabledOnly' => true)))) {
										$vn_rep_type_id = array_shift($va_rep_type_ids);
									}
								}
						
								if (is_array($pa_options['existingRepresentationMap']) && isset($pa_options['existingRepresentationMap'][$vs_path]) && $pa_options['existingRepresentationMap'][$vs_path]) {
									$this->addRelationship('ca_object_representations', $pa_options['existingRepresentationMap'][$vs_path], $vn_type_id);
									break;
								}
							
								$vs_rep_label = trim($po_request->getParameter($vs_prefix_stub.'rep_label_new_'.$va_matches[1], pString));	
								$vn_locale_id = $po_request->getParameter($vs_prefix_stub.'locale_id_new_'.$va_matches[1], pInteger);
								$vn_status = $po_request->getParameter($vs_prefix_stub.'status_new_'.$va_matches[1], pInteger);
								$vn_access = $po_request->getParameter($vs_prefix_stub.'access_new_'.$va_matches[1], pInteger);
								$vn_is_primary = $po_request->getParameter($vs_prefix_stub.'is_primary_new_'.$va_matches[1], pInteger);
						
								$t_rep = $this->addRepresentation($vs_path, $vn_rep_type_id, $vn_locale_id, $vn_status, $vn_access, $vn_is_primary, array('name' => $vs_rep_label), array('original_filename' => $vs_original_name, 'returnRepresentation' => true, 'type_id' => $vn_type_id));	// $vn_type_id = *relationship* type_id (as opposed to representation type)
								if ($this->numErrors()) {
									$po_request->addActionErrors($this->errors(), $vs_f, 'new_'.$va_matches[1]);
								} else {
									if ($t_rep && is_array($pa_options['existingRepresentationMap'])) { 
										$pa_options['existingRepresentationMap'][$vs_path] = $t_rep->getPrimaryKey();
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
						$this->_processRelated($po_request, $vs_f, $vs_placement_code.$vs_form_prefix, array('batch' => $vb_batch, 'settings' => $va_bundle_settings));
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
				$va_bundle_settings = array();
				foreach($va_bundles as $va_bundle_info) {
					if ($va_bundle_info['placement_code'] == $vs_placement_code) {
						$va_bundle_settings = $va_bundle_info['settings'];
						break;
					}
				}
			
				switch($vs_f) {
					# -------------------------------------
					// This bundle is only available when editing objects of type ca_representation_annotations
					case 'ca_representation_annotation_properties':
						if ($vb_batch) { break; } // not supported in batch mode
						foreach($this->getPropertyList() as $vs_property) {
							$this->setPropertyValue($vs_property, $po_request->getParameter($vs_property, pString));
						}
						if (!$this->validatePropertyValues()) {
							$po_request->addActionErrors($this->errors(), 'ca_representation_annotation_properties', 'general');
						}
						$this->update();
						break;
					# -------------------------------------
					// This bundle is only available for types which support set membership
					case 'ca_sets':
						// check for existing labels to delete (no updating supported)
						require_once(__CA_MODELS_DIR__.'/ca_sets.php');
						require_once(__CA_MODELS_DIR__.'/ca_set_items.php');
	
						$t_set = new ca_sets();
if (!$vb_batch) {
						$va_sets = caExtractValuesByUserLocale($t_set->getSetsForItem($this->tableNum(), $this->getPrimaryKey(), array('user_id' => $po_request->getUserID()))); 
	
						foreach($va_sets as $vn_set_id => $va_set_info) {
							$vn_item_id = $va_set_info['item_id'];
							
							if ($po_request->getParameter($vs_form_prefix.'_ca_sets_set_id_'.$vn_item_id.'_delete', pString)) {
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
							$vs_batch_mode = $_REQUEST[$vs_form_prefix.'_ca_sets_batch_mode'];
	
							if ($vs_batch_mode == '_disabled_') { break;}
							if ($vs_batch_mode == '_replace_') { $t_set->removeItemFromAllSets($this->tableNum(), $this->getPrimaryKey());}
							if ($vs_batch_mode == '_delete_') { $t_set->removeItemFromAllSets($this->tableNum(), $this->getPrimaryKey()); break; }
						}	
											
						foreach($_REQUEST as $vs_key => $vs_value) {
							if (!preg_match('/'.$vs_form_prefix.'_ca_sets_set_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
							$vn_c = intval($va_matches[1]);
							if ($vn_new_set_id = $po_request->getParameter($vs_form_prefix.'_ca_sets_set_id_new_'.$vn_c, pString)) {
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
						
						$va_row_ids = explode(';', $po_request->getParameter($vs_form_prefix.'_ca_set_itemssetRowIDList', pString));
						$this->reorderItems($va_row_ids, array('user_id' => $po_request->getUserID()));
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
						$this->savePlacementsFromHTMLForm($po_request, $vs_form_prefix);
						break;
					# -------------------------------------
					// This bundle is only available for ca_bundle_displays 
					case 'ca_bundle_display_type_restrictions':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->saveTypeRestrictionsFromHTMLForm($po_request, $vs_form_prefix);
						break;
					# -------------------------------------
					// This bundle is only available for ca_search_forms 
					case 'ca_search_form_placements':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->savePlacementsFromHTMLForm($po_request, $vs_form_prefix);
						break;
					# -------------------------------------
					// This bundle is only available for ca_editor_ui
					case 'ca_editor_ui_screens':
						if ($vb_batch) { break; } // not supported in batch mode
						global $g_ui_locale_id;
						require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
						$va_screen_ids = explode(';', $po_request->getParameter($vs_form_prefix.'_ca_editor_ui_screens_ScreenBundleList', pString));
						
						foreach($_REQUEST as $vs_key => $vs_val) {
							if (is_array($vs_val)) { continue; }
							if (!($vs_val = trim($vs_val))) { continue; }
							if (preg_match("!^{$vs_form_prefix}_ca_editor_ui_screens_name_new_([\d]+)$!", $vs_key, $va_matches)) {
								if (!($t_screen = $this->addScreen($vs_val, $g_ui_locale_id, 'screen_'.$this->getPrimaryKey().'_'.$va_matches[1]))) { break; }
								
								if ($vn_fkey = array_search("new_".$va_matches[1], $va_screen_ids)) {
									$va_screen_ids[$vn_fkey] = $t_screen->getPrimaryKey();
								} else {
									$va_screen_ids[] = $t_screen->getPrimaryKey();
								}
								continue;
							}
							
							if (preg_match("!^{$vs_form_prefix}_ca_editor_ui_screens_([\d]+)_delete$!", $vs_key, $va_matches)) {
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
						$this->savePlacementsFromHTMLForm($po_request, $vs_form_prefix);
						break;
					# -------------------------------------
					// This bundle is only available for ca_editor_uis
					case 'ca_editor_ui_type_restrictions':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->saveTypeRestrictionsFromHTMLForm($po_request, $vs_form_prefix);
						break;
					# -------------------------------------
					// This bundle is only available for ca_editor_ui_screens
					case 'ca_editor_ui_screen_type_restrictions':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->saveTypeRestrictionsFromHTMLForm($po_request, $vs_form_prefix);
						break;
					# -------------------------------------
					// This bundle is only available for ca_tours
					case 'ca_tour_stops_list':
						if ($vb_batch) { break; } // not supported in batch mode
						global $g_ui_locale_id;
						require_once(__CA_MODELS_DIR__.'/ca_tour_stops.php');
						$va_stop_ids = explode(';', $po_request->getParameter($vs_form_prefix.'_ca_tour_stops_list_StopBundleList', pString));
						
						foreach($_REQUEST as $vs_key => $vs_val) {
							if (!($vs_val = trim($vs_val))) { continue; }
							if (preg_match("!^{$vs_form_prefix}_ca_tour_stops_list_name_new_([\d]+)$!", $vs_key, $va_matches)) {
								$vn_type_id = $_REQUEST["{$vs_form_prefix}_ca_tour_stops_list_type_id_new_".$va_matches[1]];
								if (!($t_stop = $this->addStop($vs_val, $vn_type_id, $g_ui_locale_id, mb_substr(preg_replace('![^A-Za-z0-9_]+!', '_', $vs_val),0, 255)))) { break; }
								
								if ($vn_fkey = array_search("new_".$va_matches[1], $va_stop_ids)) {
									$va_stop_ids[$vn_fkey] = $t_stop->getPrimaryKey();
								} else {
									$va_stop_ids[] = $t_stop->getPrimaryKey();
								}
								continue;
							}
							
							if (preg_match("!^{$vs_form_prefix}_ca_tour_stops_list_([\d]+)_delete$!", $vs_key, $va_matches)) {
								$this->removeStop($va_matches[1]);
								if ($vn_fkey = array_search($va_matches[1], $va_stop_ids)) { unset($va_stop_ids[$vn_fkey]); }
							}
						}
						$this->reorderStops($va_stop_ids);
						break;
					# -------------------------------------
					// This bundle is only available for ca_bundle_mappings
					case 'ca_bundle_mapping_groups':
						if ($vb_batch) { break; } // not supported in batch mode
						global $g_ui_locale_id;
						require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_groups.php');
						$va_group_ids = explode(';', $po_request->getParameter($vs_form_prefix.'_ca_bundle_mapping_groups_GroupBundleList', pString));
						
						foreach($_REQUEST as $vs_key => $vs_val) {
							if (is_array($vs_val) || !($vs_val = trim($vs_val))) { continue; }
							if (preg_match("!^{$vs_form_prefix}_ca_bundle_mapping_groups_name_new_([\d]+)$!", $vs_key, $va_matches)) {
								if (!($t_group = $this->addGroup($vs_val, $vs_val, '', $g_ui_locale_id))) { break; }
								
								if ($vn_fkey = array_search("new_".$va_matches[1], $va_group_ids)) {
									$va_group_ids[$vn_fkey] = $t_group->getPrimaryKey();
								} else {
									$va_group_ids[] = $t_group->getPrimaryKey();
								}
								continue;
							}
							
							if (preg_match("!^{$vs_form_prefix}_ca_bundle_mapping_groups_([\d]+)_delete$!", $vs_key, $va_matches)) {
								$this->removeGroup($va_matches[1]);
								if ($vn_fkey = array_search($va_matches[1], $va_group_ids)) { unset($va_group_ids[$vn_fkey]); }
							}
						}
						$this->reorderGroups($va_group_ids);
						break;
					# -------------------------------------
					// This bundle is only available for ca_bundle_mapping_groups
					case 'ca_bundle_mapping_rules':
						if ($vb_batch) { break; } // not supported in batch mode
						require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_rules.php');
						$va_rule_ids = explode(';', $po_request->getParameter($vs_form_prefix.'_ca_bundle_mapping_rules_RuleBundleList', pString));
						
						foreach($_REQUEST as $vs_key => $vs_val) {
							if (!$vs_val) { continue; }
							//MappingGroupEditorForm_ca_bundle_mapping_rules_ca_path_suffix_new_0
							if (preg_match("!^{$vs_form_prefix}_ca_bundle_mapping_rules_ca_path_suffix_new_([\d]+)$!", $vs_key, $va_matches)) {
								
								$vs_ca_path_suffix = $po_request->getParameter("{$vs_form_prefix}_ca_bundle_mapping_rules_ca_path_suffix_new_".$va_matches[1], pString);
								$vs_external_path_suffix = $po_request->getParameter("{$vs_form_prefix}_ca_bundle_mapping_rules_external_path_suffix_new_".$va_matches[1], pString);
								if (!($t_rule = $this->addRule($vs_ca_path_suffix, $vs_external_path_suffix, '', array()))) { break; }
								
								if ($vn_fkey = array_search("new_".$va_matches[1], $va_rule_ids)) {
									$va_rule_ids[$vn_fkey] = $t_rule->getPrimaryKey();
								} else {
									$va_rule_ids[] = $t_rule->getPrimaryKey();
								}
								
								// save settings
								foreach($t_rule->getAvailableSettings() as $vs_setting => $va_setting_info) {
									$vs_setting_value = $po_request->getParameter("{$vs_form_prefix}_ca_bundle_mapping_rules_setting_new_".$va_matches[1]."_{$vs_setting}", pString);
									$t_rule->setSetting($vs_setting, $vs_setting_value);
								}
								$t_rule->update();
								continue;
							}
							
							if (preg_match("!^{$vs_form_prefix}_ca_bundle_mapping_rules_ca_path_suffix_([\d]+)$!", $vs_key, $va_matches)) {
								$t_rule = new ca_bundle_mapping_rules($va_matches[1]);
								$t_rule->setMode(ACCESS_WRITE);
								$t_rule->set('ca_path_suffix', $po_request->getParameter($vs_key, pString));
								$t_rule->set('external_path_suffix', $po_request->getParameter("{$vs_form_prefix}_ca_bundle_mapping_rules_external_path_suffix_".$va_matches[1], pString));
								
								// save settings
								foreach($t_rule->getAvailableSettings() as $vs_setting => $va_setting_info) {
									$vs_setting_value = $po_request->getParameter("{$vs_form_prefix}_ca_bundle_mapping_rules_setting_".$t_rule->getPrimaryKey()."_{$vs_setting}", pString);
									$t_rule->setSetting($vs_setting, $vs_setting_value);
								}
								
								$t_rule->update();
								continue;
							}
							
							if (preg_match("!^{$vs_form_prefix}_ca_bundle_mapping_rules_([\d]+)_delete$!", $vs_key, $va_matches)) {
								$this->removeRule($va_matches[1]);
								if ($vn_fkey = array_search($va_matches[1], $va_rule_ids)) { unset($va_rule_ids[$vn_fkey]); }
							}
						}
						$this->reorderRules($va_rule_ids);
						break;
					# -------------------------------------
					case 'ca_user_groups':
						if ($vb_batch) { break; } // not supported in batch mode
						if (!$po_request->user->canDoAction('is_administrator') && ($po_request->getUserID() != $this->get('user_id'))) { break; }	// don't save if user is not owner
						require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');
	
						$va_groups_to_set = $va_group_effective_dates = array();
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_form_prefix}_ca_user_groups_id(.*)$!", $vs_key, $va_matches)) {
								$vs_effective_date = $po_request->getParameter($vs_form_prefix.'_ca_user_groups_effective_date_'.$va_matches[1], pString);
								$vn_group_id = (int)$po_request->getParameter($vs_form_prefix.'_ca_user_groups_id'.$va_matches[1], pInteger);
								$vn_access = $po_request->getParameter($vs_form_prefix.'_ca_user_groups_access_'.$va_matches[1], pInteger);
								if ($vn_access > 0) {
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
	
						$va_users_to_set = $va_user_effective_dates = array();
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_form_prefix}_ca_users_id(.*)$!", $vs_key, $va_matches)) {
								$vs_effective_date = $po_request->getParameter($vs_form_prefix.'_ca_users_effective_date_'.$va_matches[1], pString);
								$vn_user_id = (int)$po_request->getParameter($vs_form_prefix.'_ca_users_id'.$va_matches[1], pInteger);
								$vn_access = $po_request->getParameter($vs_form_prefix.'_ca_users_access_'.$va_matches[1], pInteger);
								if ($vn_access > 0) {
									$va_users_to_set[$vn_user_id] = $vn_access;
									$va_user_effective_dates[$vn_user_id] = $vs_effective_date;
								}
							}
						}
						
						$this->setUsers($va_users_to_set, $va_user_effective_dates);
						
						break;
					# -------------------------------------
					case 'settings':
						if ($vb_batch) { break; } // not supported in batch mode
						$this->setSettingsFromHTMLForm($po_request);
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representations_media_display':
						if ($vb_batch) { break; } // not supported in batch mode
						$va_versions_to_process = null;
						
						if ($vb_use_options = (bool)$po_request->getParameter($vs_placement_code.$vs_form_prefix.'_media_display_derivative_options_selector', pInteger)) {
							// update only specified versions
							$va_versions_to_process =  $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_media_display_set_versions', pArray);
						} 
					
						if (!is_array($va_versions_to_process) || !sizeof($va_versions_to_process)) {
							$va_versions_to_process = array('_all');	
						}
						
						if ($vb_use_options && ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_media_display_derivative_options_mode', pString) == 'timecode')) {
							// timecode
							if (!(string)($vs_timecode = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_media_display_derivative_options_mode_timecode_value', pString))) {
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
							if ($vb_use_options && ($po_request->getParameter($vs_placement_code.$vs_form_prefix.'_media_display_derivative_options_mode', pString) == 'page')) {
								if (!(int)($vn_page = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_media_display_derivative_options_mode_page_value', pInteger))) {
									$vn_page = 1;
								}
								//
								$o_media = new Media();
								if ($o_media->read($this->getMediaPath('media', 'original'))) {
									$va_files = $o_media->writePreviews(array('force' => true, 'outputDirectory' => $this->_CONFIG->get("taskqueue_tmp_directory"), 'numberOfPages' => 1, 'startAtPage' => $vn_page, 'width' => 2048, 'height' => 2048));
							
									if(sizeof($va_files)) { 
										$this->set('media', array_shift($va_files));
									}
								}
							} else {
								// process file as new upload
								$vs_key = $vs_placement_code.$vs_form_prefix.'_media_display_url';
								if (($vs_media_url = trim($po_request->getParameter($vs_key, pString))) && isURL($vs_media_url)) {
									$this->set('media', $vs_media_url);
								} else {
									$vs_key = $vs_placement_code.$vs_form_prefix.'_media_display_media';
									if (isset($_FILES[$vs_key])) {
										$this->set('media', $_FILES[$vs_key]['tmp_name'], array('original_filename' => $_FILES[$vs_key]['name']));
									}
								}
							}
						}
						
						if ($this->changed('media')) {
							$this->update(($vs_version != '_all') ? array('updateOnlyMediaVersions' => $va_versions_to_process) : array());
							if ($this->numErrors()) {
								$po_request->addActionErrors($this->errors(), 'ca_object_representations_media_display', 'general');
							}
						}
						
						break;
					# -------------------------------
					// This bundle is only available when editing objects of type ca_object_representations
					case 'ca_object_representation_captions':
						if ($vb_batch) { return null; } // not supported in batch mode
				
						$va_users_to_set = array();
						foreach($_REQUEST as $vs_key => $vs_val) { 
							if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_captions_locale_id(.*)$!", $vs_key, $va_matches)) {
								$vn_locale_id = (int)$po_request->getParameter("{$vs_placement_code}{$vs_form_prefix}_captions_locale_id".$va_matches[1], pInteger);
								$this->addCaptionFile($_FILES["{$vs_placement_code}{$vs_form_prefix}_captions_caption_file".$va_matches[1]]['tmp_name'], $vn_locale_id, array('originalFilename' => $_FILES["{$vs_placement_code}{$vs_form_prefix}_captions_caption_file".$va_matches[1]]['name']));
							} else {
								// any to delete?
								if (preg_match("!^{$vs_placement_code}{$vs_form_prefix}_captions_([\d]+)_delete$!", $vs_key, $va_matches)) {
									$this->removeCaptionFile((int)$va_matches[1]);
								}
							}
						}
						
						break;
					# -------------------------------
					// This bundle is only available for relationships that include an object on one end
					case 'ca_object_representation_chooser':
						if ($vb_batch) { return null; } // not supported in batch mode
						if (!is_array($va_rep_ids = $po_request->getParameter($vs_placement_code.$vs_form_prefix.'_ca_object_representation_chooser', pArray))) { $va_rep_ids = array(); }
									
						if ($vs_element_code = caGetOption('element_code', $va_bundle_settings, null)) {
							if (!is_array($va_current_rep_ids = $this->get($vs_element_code, array('returnAsArray' => true, 'idsOnly' => true)))) { $va_current_rep_ids = array(); }
							$va_current_rep_ids = caExtractValuesFromArrayList($va_current_rep_ids, $vs_element_code, array('preserveKeys' => true));
						
							foreach($va_rep_ids as $vn_rep_id) {
								if (in_array($vn_rep_id, $va_current_rep_ids)) { continue; }
								$this->addAttribute(array($vs_element_code => $vn_rep_id), $vs_element_code);
							}
							foreach($va_current_rep_ids as $vn_attribute_id => $vn_current_rep_id) {
								if (!in_array($vn_current_rep_id, $va_rep_ids)) {
									$this->removeAttribute($vn_attribute_id);
								}
							}
						
							$this->update();
						}
						break;
					# -------------------------------------
				}
			}
		}
	
		BaseModel::unsetChangeLogUnitID();
		
		if ($vb_dryrun) { $this->removeTransaction(false); }
		if ($vb_we_set_transaction) { $this->removeTransaction(true); }
		return true;
	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	private function _processRelated($po_request, $ps_bundlename, $ps_form_prefix, $pa_options=null) {
 		$pa_settings = caGetOption('settings', $pa_options, array());
 		$vb_batch = caGetOption('batch', $pa_options, false);
		
		$vn_min_relationships = caGetOption('minRelationshipsPerRow', $pa_settings, 0);
		$vn_max_relationships = caGetOption('maxRelationshipsPerRow', $pa_settings, 65535);
		if ($vn_max_relationships == 0) { $vn_max_relationships = 65535; }
		
 		$va_rel_ids_sorted = $va_rel_sort_order = explode(';',$po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'BundleList', pString));
		sort($va_rel_ids_sorted, SORT_NUMERIC);
						
 		$va_rel_items = $this->getRelatedItems($ps_bundlename, $pa_settings);
 		
 		$va_rels_to_add = $va_rels_to_delete = array();
 
 if(!$vb_batch) {	
		foreach($va_rel_items as $va_rel_item) {
			$vs_key = $va_rel_item['_key'];
			
			$vn_rank = null;
			if (($vn_rank_index = array_search($va_rel_item['relation_id'], $va_rel_sort_order)) !== false) {
				$vn_rank = $va_rel_ids_sorted[$vn_rank_index];
			}
			
			$this->clearErrors();
			$vn_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_id'.$va_rel_item[$vs_key], pString);
			if ($vn_id) {
				$vn_type_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_type_id'.$va_rel_item[$vs_key], pString);
				$vs_direction = null;
				if (sizeof($va_tmp = explode('_', $vn_type_id)) == 2) {
					$vn_type_id = (int)$va_tmp[1];
					$vs_direction = $va_tmp[0];
				}
				
				$vs_effective_daterange = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_effective_date'.$va_rel_item[$vs_key], pString);
				$this->editRelationship($ps_bundlename, $va_rel_item[$vs_key], $vn_id, $vn_type_id, null, null, $vs_direction, $vn_rank);	
					
				if ($this->numErrors()) {
					$po_request->addActionErrors($this->errors(), $ps_bundlename);
				}
			} else {
				// is it a delete key?
				$this->clearErrors();
				if (($po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_'.$va_rel_item[$vs_key].'_delete', pInteger)) > 0) {
					$va_rels_to_delete[] = array('bundle' => $ps_bundlename, 'relation_id' => $va_rel_item[$vs_key]);
				}
			}
		}
}
 		
 		// check for new relations to add
 		if ($vb_batch) {
			$vs_batch_mode = $_REQUEST[$ps_form_prefix.'_'.$ps_bundlename.'_batch_mode'];
 			if ($vb_batch_mode == '_disabled_') { return true; }
			if ($vs_batch_mode == '_delete_') {				// remove all relationships and return
				$this->removeRelationships($ps_bundlename);
				return true;
			}
			if ($vs_batch_mode == '_replace_') {			// remove all existing relationships and then add new ones
				$this->removeRelationships($ps_bundlename);
			}
		}
 		foreach($_REQUEST as $vs_key => $vs_value ) {
			if (preg_match('/^'.$ps_form_prefix.'_'.$ps_bundlename.'_idnew_([\d]+)/', $vs_key, $va_matches)) { 
				$vn_c = intval($va_matches[1]);
				if ($vn_new_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_idnew_'.$vn_c, pString)) {
					$vn_new_type_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_type_idnew_'.$vn_c, pString);
				
					$vs_direction = null;
					if (sizeof($va_tmp = explode('_', $vn_new_type_id)) == 2) {
						$vn_new_type_id = (int)$va_tmp[1];
						$vs_direction = $va_tmp[0];
					}
				
					$va_rels_to_add[] = array(
						'bundle' => $ps_bundlename, 'row_id' => $vn_new_id, 'type_id' => $vn_new_type_id, 'direction' => $vs_direction
					);
				}
			}
			
			// check for checklist mode ca_list_items
			if ($ps_bundlename == 'ca_list_items') {
				if (preg_match('/^'.$ps_form_prefix.'_'.$ps_bundlename.'_item_id_new_([\d]+)/', $vs_key, $va_matches)) { 
					if ($vn_rel_type_id = $po_request->getParameter($ps_form_prefix.'_'.$ps_bundlename.'_type_idchecklist', pInteger)) {
						if ($vn_item_id = $po_request->getParameter($vs_key, pInteger)) {
							$va_rels_to_add[] = array(
								'bundle' => $ps_bundlename, 'row_id' => $vn_item_id, 'type_id' => $vn_rel_type_id, 'direction' => null
							);
						}
					}
				}
				
				if (preg_match('/^'.$ps_form_prefix.'_'.$ps_bundlename.'_item_id_([\d]+)_delete/', $vs_key, $va_matches)) { 
					if ($po_request->getParameter($vs_key, pInteger)) {
						$va_rels_to_delete[] = array('bundle' => $ps_bundlename, 'relation_id' => $va_matches[1]);
					}
				}
			}
		}
		
		// Check min/max
		$vn_total_rel_count = (sizeof($va_rel_items) + sizeof($va_rels_to_add) - sizeof($va_rels_to_delete));
		if ($vn_min_relationships && ($vn_total_rel_count < $vn_min_relationships)) {
			$po_request->addActionErrors(array(new Error(2590, ($vn_min_relationships == 1) ? _t('There must be at least %1 relationship for %2', $vn_min_relationships, $this->getAppDatamodel()->getTableProperty($ps_bundlename, 'NAME_PLURAL')) : _t('There must be at least %1 relationships for %2', $vn_min_relationships, $this->getAppDatamodel()->getTableProperty($ps_bundlename, 'NAME_PLURAL')), 'BundleableLabelableBaseModelWithAttributes::_processRelated()', null, null, false, false)), $ps_bundlename);
			return false;
		}
		if ($vn_max_relationships && ($vn_total_rel_count > $vn_max_relationships)) {
			$po_request->addActionErrors(array(new Error(2590, ($vn_max_relationships == 1) ? _t('There must be no more than %1 relationship for %2', $vn_max_relationships, $this->getAppDatamodel()->getTableProperty($ps_bundlename, 'NAME_PLURAL')) : _t('There must be no more than %1 relationships for %2', $vn_max_relationships, $this->getAppDatamodel()->getTableProperty($ps_bundlename, 'NAME_PLURAL')), 'BundleableLabelableBaseModelWithAttributes::_processRelated()', null, null, false, false)), $ps_bundlename);
			return false;
		}
		
		// Process relationships
		foreach($va_rels_to_delete as $va_rel_to_delete) {
			$this->removeRelationship($va_rel_to_delete['bundle'], $va_rel_to_delete['relation_id']);
			if ($this->numErrors()) {
				$po_request->addActionErrors($this->errors(), $ps_bundlename);
			}
		}
		foreach($va_rels_to_add as $va_rel_to_add) {
			$this->addRelationship($va_rel_to_add['bundle'], $va_rel_to_add['row_id'], $va_rel_to_add['type_id'], null, null, $va_rel_to_add['direction']);
			if ($this->numErrors()) {
				$po_request->addActionErrors($this->errors(), $ps_bundlename);
			}
		}
		
		return true;
 	}
 	# ------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getRelatedItemsAsSearchResult($pm_rel_table_name_or_num, $pa_options=null) {
 		if (is_array($va_related_items = $this->getRelatedItems($pm_rel_table_name_or_num, $pa_options))) {
 			$vs_pk = $this->getAppDataModel()->getTablePrimaryKeyName($pm_rel_table_name_or_num);
 			
 			$va_ids = array();
 			foreach($va_related_items as $vn_relation_id => $va_item) {
 				$va_ids[] = $va_item[$vs_pk];
 			}
 			
 			return $this->makeSearchResult($pm_rel_table_name_or_num, $va_ids);
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns list of items in the specified table related to the currently loaded row.
 	 * 
 	 * @param $pm_rel_table_name_or_num - the table name or table number of the item type you want to get a list of (eg. if you are calling this on an ca_objects instance passing 'ca_entities' here will get you a list of entities related to the object)
 	 * @param $pa_options - array of options. Supported options are:
 	 *
 	 * 		restrict_to_type = restricts returned items to those of the specified type; only supports a single type which can be specified as a list item_code or item_id
 	 *		restrictToType = synonym for restrict_to_type
 	 *		restrict_to_types = restricts returned items to those of the specified types; pass an array of list item_codes or item_ids
 	 *		restrictToTypes = synonym for restrict_to_types
 	 *		dont_include_subtypes_in_type_restriction = if set subtypes are not included when enforcing restrict_to_types. Note that restrict_to_relationship_types always includes subtypes in its restriction.
 	 *		dontIncludeSubtypesInTypeRestriction = synonym for dont_include_subtypes_in_type_restriction
 	 *		restrict_to_relationship_types = restricts returned items to those related to the current row by the specified relationship type(s). You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		restrictToRelationshipTypes = synonym for restrict_to_relationship_types
 	 *
 	 *		exclude_relationship_types = omits any items related to the current row with any of the specified types from the returned set of ids. You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		excludeRelationshipTypes = synonym for exclude_relationship_types
 	 * 		exclude_type = excludes returned items of the specified type; only supports a single type which can be specified as a list item_code or item_id
 	 *		excludeType = synonym for exclude_type
 	 *		exclude_types = omits any items related to the current row that are of any of the specified types from the returned set of ids. You can pass either an array of types or a single type. The types can be type_code's or type_id's.
 	 *		excludeTypes = synonym for exclude_types
 	 *
 	 *		restrict_to_lists = when fetching related ca_list_items restricts returned items to those that are in the specified lists; pass an array of list list_codes or list_ids
 	 *		restrictToLists = synonym for restrict_to_lists
 	 *
 	 *		fields = array of fields (in table.fieldname format) to include in returned data
 	 *		return_non_preferred_labels = if set to true, non-preferred labels are included in returned data
 	 *		returnNonPreferredLabels = synonym for return_non_preferred_labels
 	 *		checkAccess = array of access values to filter results by; if defined only items with the specified access code(s) are returned
 	 *		return_labels_as_array = if set to true then all labels associated with row are returned in an array, otherwise only a text value in the current locale is returned; default is false - return single label in current locale
 	 *		returnLabelsAsArray = synonym for return_labels_as_array
 	 * 		row_ids = array of primary key values to use when fetching related items; if omitted or set to a null value the 'row_id' option (single value) will be used; if row_id is also not set then the currently loaded primary key value will be used
 	 *		row_id = primary key value to use when fetching related items; if omitted or set to a false value (eg. null, false, 0) then the currently loaded primary key value is used [default]
 	 *		start = item to start return set at; first item is numbered zero; default is 0
 	 *		limit = number of items to limit return set to; default is 1000
 	 *		sort = optional array of bundles to sort returned values on. Currently only supported when getting related values via simple related <table_name> and <table_name>.related invokations. Eg. from a ca_objects results you can use the 'sort' option got get('ca_entities'), get('ca_entities.related') or get('ca_objects.related'). The bundle specifiers are fields with or without tablename. Only those fields returned for the related tables (intrinsics, label fields and attributes) are sortable.
 	 *		sortDirection = direction of sort. Valid values as "ASC" (ascending) and "DESC" (descending). Default is ASC.
 	 *		showDeleted = if set to true, related items that have been deleted are returned. Default is false.
	 *		where = optional array of fields and field values to filter returned values on. The fields must be intrinsic and in the same table as the field being "get()'ed" Can be used to filter returned values from primary and related tables. This option can be useful when you want to fetch certain values from a related table. For example, you want to get the relationship source_info values, but only for relationships going to a specific related record. Note that multiple fields/values are effectively AND'ed together - all must match for a row to be returned - and that only equivalence is supported (eg. field equals value).
 	 *		user_id = If set item level access control is performed relative to specified user_id, otherwise defaults to logged in user
 	 *		groupFields = Groups together fields in an arrangement that is easier for import to another system. Used by the ItemInfo web service when in "import" mode. Default is false.
 	 *		returnLocaleCodes = Return locale values as codes (Ex. en_US) rather than numeric database-specific locale_ids. Default is false.
 	 * @return array - list of related items
 	 */
	 public function getRelatedItems($pm_rel_table_name_or_num, $pa_options=null) {
		global $AUTH_CURRENT_USER_ID;
		$vn_user_id = (isset($pa_options['user_id']) && $pa_options['user_id']) ? $pa_options['user_id'] : (int)$AUTH_CURRENT_USER_ID;
		$vb_show_if_no_acl = (bool)($this->getAppConfig()->get('default_item_access_level') > __CA_ACL_NO_ACCESS__);
			
	 	// convert options
	 	if(isset($pa_options['restrictToType']) && (!isset($pa_options['restrict_to_type']) || !$pa_options['restrict_to_type'])) { $pa_options['restrict_to_type'] = $pa_options['restrictToType']; }
	 	if(isset($pa_options['restrictToTypes']) && (!isset($pa_options['restrict_to_types']) || !$pa_options['restrict_to_types'])) { $pa_options['restrict_to_types'] = $pa_options['restrictToTypes']; }
	 	if(isset($pa_options['restrictToRelationshipTypes']) && (!isset($pa_options['restrict_to_relationship_types']) || !$pa_options['restrict_to_relationship_types'])) { $pa_options['restrict_to_relationship_types'] = $pa_options['restrictToRelationshipTypes']; }
	 	if(isset($pa_options['excludeType']) && (!isset($pa_options['exclude_type']) || !$pa_options['exclude_type'])) { $pa_options['exclude_type'] = $pa_options['excludeType']; }
	 	if(isset($pa_options['excludeTypes']) && (!isset($pa_options['exclude_types']) || !$pa_options['exclude_types'])) { $pa_options['exclude_types'] = $pa_options['excludeTypes']; }
	 	if(isset($pa_options['excludeRelationshipTypes']) && (!isset($pa_options['exclude_relationship_types']) || !$pa_options['exclude_relationship_types'])) { $pa_options['exclude_relationship_types'] = $pa_options['excludeRelationshipTypes']; }
	 	if(isset($pa_options['dontIncludeSubtypesInTypeRestriction']) && (!isset($pa_options['dont_include_subtypes_in_type_restriction']) || !$pa_options['dont_include_subtypes_in_type_restriction'])) { $pa_options['dont_include_subtypes_in_type_restriction'] = $pa_options['dontIncludeSubtypesInTypeRestriction']; }
	 	if(isset($pa_options['returnNonPreferredLabels']) && (!isset($pa_options['return_non_preferred_labels']) || !$pa_options['return_non_preferred_labels'])) { $pa_options['return_non_preferred_labels'] = $pa_options['returnNonPreferredLabels']; }
	 	if(isset($pa_options['returnLabelsAsArray']) && (!isset($pa_options['return_labels_as_array']) || !$pa_options['return_labels_as_array'])) { $pa_options['return_labels_as_array'] = $pa_options['returnLabelsAsArray']; }
		if(isset($pa_options['restrictToLists']) && (!isset($pa_options['restrict_to_lists']) || !$pa_options['restrict_to_lists'])) { $pa_options['restrict_to_lists'] = $pa_options['restrictToLists']; }
	 	if(isset($pa_options['groupFields'])) { $pa_options['groupFields'] = (bool)$pa_options['groupFields']; } else { $pa_options['groupFields'] = false; }
	 	
		$o_db = $this->getDb();
		$t_locale = new ca_locales();
		$o_tep = new TimeExpressionParser();
		$vb_uses_effective_dates = false;
		
		$va_get_where = 			(isset($pa_options['where']) && is_array($pa_options['where']) && sizeof($pa_options['where'])) ? $pa_options['where'] : null;
		
		$va_row_ids = (isset($pa_options['row_ids']) && is_array($pa_options['row_ids'])) ? $pa_options['row_ids'] : null;
		$vn_row_id = (isset($pa_options['row_id']) && $pa_options['row_id']) ? $pa_options['row_id'] : $this->getPrimaryKey();
		
		if(isset($pa_options['sort']) && !is_array($pa_options['sort'])) { $pa_options['sort'] = array($pa_options['sort']); }
		$va_sort_fields = (isset($pa_options['sort']) && is_array($pa_options['sort'])) ? $pa_options['sort'] : null;
		$vs_sort_direction = (isset($pa_options['sortDirection']) && $pa_options['sortDirection']) ? $pa_options['sortDirection'] : null;
		
		if (!$va_row_ids && ($vn_row_id > 0)) {
			$va_row_ids = array($vn_row_id);
		}
		
		if (!$va_row_ids || !is_array($va_row_ids) || !sizeof($va_row_ids)) { return array(); }
		
		$vb_return_labels_as_array = (isset($pa_options['return_labels_as_array']) && $pa_options['return_labels_as_array']) ? true : false;
		$vn_limit = (isset($pa_options['limit']) && ((int)$pa_options['limit'] > 0)) ? (int)$pa_options['limit'] : 1000;
		$vn_start = (isset($pa_options['start']) && ((int)$pa_options['start'] > 0)) ? (int)$pa_options['start'] : 0;
              
		if (is_numeric($pm_rel_table_name_or_num)) {
			$vs_related_table_name = $this->getAppDataModel()->getTableName($pm_rel_table_name_or_num);
		} else {
			$vs_related_table_name = $pm_rel_table_name_or_num;
		}
		
		if (!is_array($pa_options)) { $pa_options = array(); }

		switch(sizeof($va_path = array_keys($this->getAppDatamodel()->getPath($this->tableName(), $vs_related_table_name)))) {
			case 3:
				$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
				$t_rel_item = $this->getAppDatamodel()->getTableInstance($va_path[2]);
				$vs_key = $t_item_rel->primaryKey(); //'relation_id';
				break;
			case 2:
				$t_item_rel = null;
				$t_rel_item = $this->getAppDatamodel()->getTableInstance($va_path[1]);
				$vs_key = $t_rel_item->primaryKey();
				break;
			default:
				// bad related table
				return null;
				break;
		}
		
		// check for self relationship
		$vb_self_relationship = false;
		if($this->tableName() == $vs_related_table_name) {
			$vb_self_relationship = true;
			$t_rel_item = $this->getAppDatamodel()->getTableInstance($va_path[0]);
			$t_item_rel = $this->getAppDatamodel()->getTableInstance($va_path[1]);
		}
		
		$va_wheres = array();
		$va_selects = array();
		$va_joins_post_add = array();
		
		$vs_related_table = $t_rel_item->tableName();
		if ($t_rel_item->hasField('type_id')) { $va_selects[] = $vs_related_table.'.type_id item_type_id'; }
		
		// TODO: get these field names from models
		if ($t_item_rel) {
			//define table names
			$vs_linking_table = $t_item_rel->tableName();
			
			$va_selects[] = $vs_related_table.'.'.$t_rel_item->primaryKey();
			
			// include dates in returned data
			if ($t_item_rel->hasField('effective_date')) {
				$va_selects[] = $vs_linking_table.'.sdatetime';
				$va_selects[] = $vs_linking_table.'.edatetime';
				
				$vb_uses_effective_dates = true;
			}
		
			if ($t_rel_item->hasField('is_enabled')) {
				$va_selects[] = $vs_related_table.'.is_enabled';
			}
			
			
			if ($t_item_rel->hasField('type_id')) {
				$va_selects[] = $vs_linking_table.'.type_id relationship_type_id';
				
				require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
				$t_rel = new ca_relationship_types();
				
				$vb_uses_relationship_types = true;
			}
			
			// limit related items to a specific type
			if ($vb_uses_relationship_types && isset($pa_options['restrict_to_relationship_types']) && $pa_options['restrict_to_relationship_types']) {
				if (!is_array($pa_options['restrict_to_relationship_types'])) {
					$pa_options['restrict_to_relationship_types'] = array($pa_options['restrict_to_relationship_types']);
				}
				
				if (sizeof($pa_options['restrict_to_relationship_types'])) {
					$va_rel_types = array();
					foreach($pa_options['restrict_to_relationship_types'] as $vm_type) {
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
			
			if ($vb_uses_relationship_types && isset($pa_options['exclude_relationship_types']) && $pa_options['exclude_relationship_types']) {
				if (!is_array($pa_options['exclude_relationship_types'])) {
					$pa_options['exclude_relationship_types'] = array($pa_options['exclude_relationship_types']);
				}
				
				if (sizeof($pa_options['exclude_relationship_types'])) {
					$va_rel_types = array();
					foreach($pa_options['exclude_relationship_types'] as $vm_type) {
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
		if (isset($pa_options['restrict_to_type']) && $pa_options['restrict_to_type']) {
			if (!isset($pa_options['restrict_to_types']) || !is_array($pa_options['restrict_to_types'])) {
				$pa_options['restrict_to_types'] = array();
			}
			$pa_options['restrict_to_types'][] = $pa_options['restrict_to_type'];
		}
		
		$va_ids = caMergeTypeRestrictionLists($t_rel_item, $pa_options);
		
		if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
			$va_wheres[] = '('.$vs_related_table.'.type_id IN ('.join(',', $va_ids).'))';
		}
		
		if (isset($pa_options['exclude_type']) && $pa_options['exclude_type']) {
			if (!isset($pa_options['exclude_types']) || !is_array($pa_options['exclude_types'])) {
				$pa_options['exclude_types'] = array();
			}
			$pa_options['exclude_types'][] = $pa_options['exclude_type'];
		}
		if (isset($pa_options['exclude_types']) && is_array($pa_options['exclude_types'])) {
			$va_ids = caMakeTypeIDList($t_rel_item->tableName(), $pa_options['exclude_types']);
			
			if (is_array($va_ids) && (sizeof($va_ids) > 0)) {
				$va_wheres[] = '('.$vs_related_table.'.type_id NOT IN ('.join(',', $va_ids).'))';
			}
		}
		
		if ($this->getAppConfig()->get('perform_item_level_access_checking')) {
			$t_user = new ca_users($vn_user_id, true);
			if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
				$va_group_ids = array_keys($va_groups);
			} else {
				$va_group_ids = array();
			}
			
			// Join to limit what browse table items are used to generate facet
			$va_joins_post_add[] = 'LEFT JOIN ca_acl ON '.$t_rel_item->tableName().'.'.$t_rel_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_rel_item->tableNum()."\n";
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
				
		if (is_array($va_get_where)) {
			foreach($va_get_where as $vs_fld => $vm_val) {
				if ($t_rel_item->hasField($vs_fld)) {
					$va_wheres[] = "({$vs_related_table_name}.{$vs_fld} = ".(!is_numeric($vm_val) ? "'".$this->getDb()->escape($vm_val)."'": $vm_val).")";
				}
			}
		}
		
		if ($vs_idno_fld = $t_rel_item->getProperty('ID_NUMBERING_ID_FIELD')) { $va_selects[] = $t_rel_item->tableName().'.'.$vs_idno_fld; }
		if ($vs_idno_sort_fld = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD')) { $va_selects[] = $t_rel_item->tableName().'.'.$vs_idno_sort_fld; }

		$va_selects[] = $va_path[1].'.'.$vs_key;	
		
		if (isset($pa_options['fields']) && is_array($pa_options['fields'])) {
			$va_selects = array_merge($va_selects, $pa_options['fields']);
		}
		
		
		 // if related item is labelable then include the label table in the query as well
		$vs_label_display_field = null;
		if (method_exists($t_rel_item, "getLabelTableName")) {
			if($vs_label_table_name = $t_rel_item->getLabelTableName()) {           // make sure it actually has a label table...
				$va_path[] = $vs_label_table_name;
				$t_rel_item_label = $this->getAppDatamodel()->getTableInstance($vs_label_table_name);
				$vs_label_display_field = $t_rel_item_label->getDisplayField();

				if($vb_return_labels_as_array || (is_array($va_sort_fields) && sizeof($va_sort_fields))) {
					$va_selects[] = $vs_label_table_name.'.*';
				} else {
					$va_selects[] = $vs_label_table_name.'.'.$vs_label_display_field;
					$va_selects[] = $vs_label_table_name.'.locale_id';
					
					if ($t_rel_item_label->hasField('surname')) {	// hack to include fields we need to sort entity labels properly
						$va_selects[] = $vs_label_table_name.'.surname';
						$va_selects[] = $vs_label_table_name.'.forename';
					}
				}
				
				if ($t_rel_item_label->hasField('is_preferred') && (!isset($pa_options['return_non_preferred_labels']) || !$pa_options['return_non_preferred_labels'])) {
					$va_wheres[] = "(".$vs_label_table_name.'.is_preferred = 1)';
				}
			}
		}
				
		// return source info in returned data
		if ($t_item_rel && $t_item_rel->hasField('source_info')) {
			$va_selects[] = $vs_linking_table.'.source_info';
		}
		
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
			$va_wheres[] = "(".$t_rel_item->tableName().'.access IN ('.join(',', $pa_options['checkAccess']).'))';
		}
		
		if ((!isset($pa_options['showDeleted']) || !$pa_options['showDeleted']) && $t_rel_item->hasField('deleted')) {
			$va_wheres[] = "(".$t_rel_item->tableName().'.deleted = 0)';
		}

		if($vb_self_relationship) {
			//
			// START - self relation
			//
			$va_rel_info = $this->getAppDatamodel()->getRelationships($va_path[0], $va_path[1]);
			if ($vs_label_table_name) {
				$va_label_rel_info = $this->getAppDatamodel()->getRelationships($va_path[0], $vs_label_table_name);
			}
			
			$va_rels = array();
			
			$vn_i = 0;
			foreach($va_rel_info[$va_path[0]][$va_path[1]] as $va_possible_keys) {
				$va_joins = array();
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
					$vs_order_by = ' ORDER BY '.$t_item_rel->tableName().'.rank';
					$vs_sort_fld = 'rank';
					$va_selects[] = $t_item_rel->tableName().".rank";
				} else {
					if ($t_rel_item && ($vs_sort = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD'))) {
						$vs_order_by = " ORDER BY ".$t_rel_item->tableName().".{$vs_sort}";
						$vs_sort_fld = $vs_sort;
						$va_selects[] = $t_rel_item->tableName().".{$vs_sort}";
					}
				}
				
				$vs_sql = "
					SELECT ".join(', ', $va_selects)."
					FROM ".$va_path[0]."
					".join("\n", array_merge($va_joins, $va_joins_post_add))."
					WHERE
						".join(' AND ', array_merge($va_wheres, array('('.$va_path[1].'.'.$vs_other_field .' IN ('.join(',', $va_row_ids).'))')))."
					{$vs_order_by}";
				//print "<pre>$vs_sql</pre>\n";
		
				$qr_res = $o_db->query($vs_sql);
				
				if ($vb_uses_relationship_types) { $va_rel_types = $t_rel->getRelationshipInfo($va_path[1]); }
				$vn_c = 0;
				if ($vn_start > 0) { $qr_res->seek($vn_start); }
				while($qr_res->nextRow()) {
					if ($vn_c >= $vn_limit) { break; }
					$va_row = $qr_res->getRow();
					$vn_id = $va_row[$vs_key].'/'.$va_row['row_id'];
					$vs_sort_key = $qr_res->get($vs_sort_fld);
					
					$vs_display_label = $va_row[$vs_label_display_field];
					//unset($va_row[$vs_label_display_field]);
					
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
					if (isset($pa_options['returnLocaleCodes']) && $pa_options['returnLocaleCodes']) {
						$va_rels[$vs_v]['locale_id'] = $vn_locale_id = $t_locale->localeIDToCode($vn_locale_id);
					}
					
					$va_rels[$vs_sort_key][$vn_id]['labels'][$vn_locale_id] =  ($vb_return_labels_as_array) ? $va_row : $vs_display_label;
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
					if ($pa_options['groupFields']) {
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
				};
				$vn_i++;
			}
			
			ksort($va_rels);	// sort by sort key... we'll remove the sort key in the next loop while we add the labels
			
			// Set 'label' entry - display label in current user's locale
			$va_sorted_rels = array();
			foreach($va_rels as $vs_sort_key => $va_rels_by_sort_key) {
				foreach($va_rels_by_sort_key as $vn_id => $va_rel) {
					$va_tmp = array(0 => $va_rel['labels']);
					$va_sorted_rels[$vn_id] = $va_rel;
					$va_values_filtered_by_locale = caExtractValuesByUserLocale($va_tmp);
					$va_sorted_rels[$vn_id]['label'] = array_shift($va_values_filtered_by_locale);
				}
			}
			$va_rels = $va_sorted_rels;
			
			//
			// END - self relation
			//
		} else {
			//
			// BEGIN - non-self relation
			//
			
			
			$va_wheres[] = "(".$this->tableName().'.'.$this->primaryKey()." IN (".join(",", $va_row_ids)."))";
			$vs_cur_table = array_shift($va_path);
			$va_joins = array();
			
			// Enforce restrict_to_lists for related list items
			if (($vs_related_table_name == 'ca_list_items') && is_array($pa_options['restrict_to_lists'])) {
				$va_list_ids = array();
				foreach($pa_options['restrict_to_lists'] as $vm_list) {
					if ($vn_list_id = ca_lists::getListID($vm_list)) { $va_list_ids[] = $vn_list_id; }
				}
				if (sizeof($va_list_ids)) {
					$va_wheres[] = "(ca_list_items.list_id IN (".join(",", $va_list_ids)."))";
				}
			}
			
			foreach($va_path as $vs_join_table) {
				$va_rel_info = $this->getAppDatamodel()->getRelationships($vs_cur_table, $vs_join_table);

				if($vs_join_table=="ca_set_item_labels"){ // hack to have label-less set items appear in "ca_set_items" queries for ca_sets
					$va_joins[] = 'LEFT JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
				} else {
					$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
				}

				$vs_cur_table = $vs_join_table;
			}
			
			$va_selects[] = $this->tableName().'.'.$this->primaryKey().' AS row_id';
			
			$vs_order_by = '';
			if ($t_item_rel && $t_item_rel->hasField('rank')) {
				$vs_order_by = ' ORDER BY '.$t_item_rel->tableName().'.rank';
			} else {
				if ($t_rel_item && ($vs_sort = $t_rel_item->getProperty('ID_NUMBERING_SORT_FIELD'))) {
					$vs_order_by = " ORDER BY ".$t_rel_item->tableName().".{$vs_sort}";
				}
			}
			
			$vs_sql = "
				SELECT ".join(', ', $va_selects)."
				FROM ".$this->tableName()."
				".join("\n", array_merge($va_joins, $va_joins_post_add))."
				WHERE
					".join(' AND ', $va_wheres)."
				{$vs_order_by}
			";
			
			//print "<pre>$vs_sql</pre>\n";
			$qr_res = $o_db->query($vs_sql);
			
			if ($vb_uses_relationship_types)  { 
				$va_rel_types = $t_rel->getRelationshipInfo($t_item_rel->tableName()); 
				$vs_left_table = $t_item_rel->getLeftTableName();
				$vs_direction = ($vs_left_table == $this->tableName()) ? 'ltor' : 'rtol';
			}
			$va_rels = array();
			$vn_c = 0;
			if ($vn_start > 0) { $qr_res->seek($vn_start); }
			while($qr_res->nextRow()) {
				if ($vn_c >= $vn_limit) { break; }
				if (isset($pa_options['returnAsSearchResult']) && $pa_options['returnAsSearchResult']) {
					$va_rels[] = $qr_res->get($t_rel_item->primaryKey());
					continue;
				}
				
				$va_row = $qr_res->getRow();
				$vs_v = $va_row[$vs_key];
				
				$vs_display_label = $va_row[$vs_label_display_field];
				//unset($va_row[$vs_label_display_field]);
				
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
				if (isset($pa_options['returnLocaleCodes']) && $pa_options['returnLocaleCodes']) {
					$va_rels[$vs_v]['locale_id'] = $vn_locale_id = $t_locale->localeIDToCode($vn_locale_id);
				}
				
				$va_rels[$vs_v]['labels'][$vn_locale_id] =  ($vb_return_labels_as_array) ? $va_row : $vs_display_label;
				
				$va_rels[$vs_v]['_key'] = $vs_key;
				$va_rels[$vs_v]['direction'] = $vs_direction;
				
				$vn_c++;
				if ($vb_uses_relationship_types) {
					$va_rels[$vs_v]['relationship_typename'] = ($vs_direction == 'ltor') ? $va_rel_types[$va_row['relationship_type_id']]['typename'] : $va_rel_types[$va_row['relationship_type_id']]['typename_reverse'];
					$va_rels[$vs_v]['relationship_type_code'] = $va_rel_types[$va_row['relationship_type_id']]['type_code'];
				}
				
				if ($pa_options['groupFields']) {
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
			
			if (!isset($pa_options['returnAsSearchResult']) || !$pa_options['returnAsSearchResult']) {
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
		
		//
		// Sort on fields if specified
		//
		if (is_array($va_sort_fields) && sizeof($va_rels)) {
			$va_ids = array();
			$vs_rel_pk = $t_rel_item->primaryKey();
			foreach($va_rels as $vn_i => $va_rel) {
				$va_ids[] = $va_rel[$vs_rel_pk];
			}
			
			// Handle sorting on attribute values
			$vs_rel_pk = $t_rel_item->primaryKey();
			foreach($va_sort_fields as $vn_x => $vs_sort_field) {
				if ($vs_sort_field == 'relation_id') { // sort by relationship primary key
					if ($t_item_rel) { 
						$va_sort_fields[$vn_x] = $vs_sort_field = $t_item_rel->tableName().'.'.$t_item_rel->primaryKey();
					} 
					continue;
				}
				$va_tmp = explode('.', $vs_sort_field);
				if ($va_tmp[0] == $vs_related_table_name) {
					$qr_rel = $t_rel_item->makeSearchResult($va_tmp[0], $va_ids);
					
					$vs_table = array_shift($va_tmp);
					$vs_key = join(".", $va_tmp);
					while($qr_rel->nextHit()) {
						$vn_pk_val = $qr_rel->get($vs_table.".".$vs_rel_pk);
						foreach($va_rels as $vn_rel_id => $va_rel) {
							if ($va_rel[$vs_rel_pk] == $vn_pk_val) {
								$va_rels[$vn_rel_id][$vs_key] = $qr_rel->get($vs_sort_field, array("delimiter" => ";", 'sortable' => 1));
								break;
							}
						}
					}
				}
			}
			
			// Perform sort
			$va_rels = caSortArrayByKeyInValue($va_rels, $va_sort_fields, $vs_sort_direction);
		}
		
		return $va_rels;
	}
	# --------------------------------------------------------------------------------------------
	public function getTypeMenu() {
		$t_list = new ca_lists();
		$t_list->load(array('list_code' => $this->getTypeListCode()));
		
		$t_list_item = new ca_list_items();
		$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
		$va_hierarchy = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
		
		$va_types = array();
		if (is_array($va_hierarchy)) {
			
			$va_types_by_parent_id = array();
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
					$va_subtypes = array();
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
		if (!is_array($pa_options)) { $pa_options = array(); }
		foreach (array(
				'name', 'form_name', 'request', 'field_errors', 'display_form_field_tips', 'no_tooltips', 'label', 'readonly'
				) 
			as $vs_key) {
			if(!isset($pa_options[$vs_key])) { $pa_options[$vs_key] = null; }
		}
		
		if (
			($ps_field == $this->getProperty('ID_NUMBERING_ID_FIELD')) 
			&& 
			($this->opo_idno_plugin_instance)
			&&
			$pa_options['request']
		) {
			
			if ($this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD'))) { $this->opo_idno_plugin_instance->isChild(true); }	// if it has a parent_id then set the id numbering plugin using "child_only" numbering schemes (if defined)
			if (!$this->getPrimaryKey() && $this->opo_idno_plugin_instance->isChild()) {
				$this->set('idno', $this->opo_idno_plugin_instance->makeTemplateFromValue($this->get('idno'), 1, true));	// chop off last serial element
			}
			$this->opo_idno_plugin_instance->setValue($this->get($ps_field));
			if (method_exists($this, "getTypeCode")) { $this->opo_idno_plugin_instance->setType($this->getTypeCode()); }
			$vs_element = $this->opo_idno_plugin_instance->htmlFormElement(
										$ps_field,  
										$va_errors, 
										array_merge(
											$pa_options,
											array(
												'error_icon' 				=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/warning_small.gif",
												'progress_indicator'		=> $pa_options['request']->getThemeUrlPath()."/graphics/icons/indicator.gif",
												'show_errors'				=> ($this->getPrimaryKey()) ? true : false,
												'context_id'				=> isset($pa_options['context_id']) ? $pa_options['context_id'] : null,
												'table' 					=> $this->tableName(),
												'row_id' 					=> $this->getPrimaryKey(),
												'check_for_dupes'			=> true,
												'search_url'				=> caSearchUrl($pa_options['request'], $this->tableName(), '')
											)
										)
			);
			
			if (is_null($ps_format)) {
				if (isset($pa_options['field_errors']) && is_array($pa_options['field_errors']) && sizeof($pa_options['field_errors'])) {
					$ps_format = $this->_CONFIG->get('bundle_element_error_display_format');
					$va_field_errors = array();
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
		return $this->opo_idno_plugin_instance;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function validateAdminIDNo($ps_admin_idno) {
		$va_errors = array();
		if ($this->_CONFIG->get('require_valid_id_number_for_'.$this->tableName()) && sizeof($va_admin_idno_errors = $this->opo_idno_plugin_instance->isValidValue($ps_admin_idno))) {
			$va_errors[] = join('; ', $va_admin_idno_errors);
		} else {
			if (!$this->_CONFIG->get('allow_duplicate_id_number_for_'.$this->tableName()) && sizeof($this->checkForDupeAdminIdnos($ps_admin_idno))) {
				$va_errors[] = _t("Identifier %1 already exists and duplicates are not permitted", $ps_admin_idno);
			}
		}
		
		return $va_errors;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getSubTypes($pa_subtypes, $pa_types_by_parent_id) {
		$va_subtypes = array();
		foreach($pa_subtypes as $vn_i => $va_type) {
			if (is_array($pa_types_by_parent_id[$va_type['item_id']])) {
				$va_subsubtypes = $this->_getSubTypes($pa_types_by_parent_id[$va_type['item_id']], $pa_types_by_parent_id);
			} else {
				$va_subsubtypes = array();
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
	 * @return SearchResult A search result of for the specified table
	 */
	public function makeSearchResult($pm_rel_table_name_or_num, $pa_ids) {
		if (!is_array($pa_ids) || !sizeof($pa_ids)) { return null; }
		$pn_table_num = $this->getAppDataModel()->getTableNum($pm_rel_table_name_or_num);
		if (!($t_instance = $this->getAppDataModel()->getInstanceByTableNum($pn_table_num))) { return null; }
	
		$va_ids = array();
		foreach($pa_ids as $vn_i => $vn_id) {
			if (is_numeric($vn_id)) { 
				$va_ids[] = $vn_id;
			}
		}
	
		if (!($vs_search_result_class = $t_instance->getProperty('SEARCH_RESULT_CLASSNAME'))) { return null; }
		require_once(__CA_LIB_DIR__.'/ca/Search/'.$vs_search_result_class.'.php');
		$o_data = new WLPlugSearchEngineCachedResult($va_ids, $t_instance->tableNum());
		$o_res = new $vs_search_result_class($t_instance->tableName());	// we pass the table name here so generic multi-table search classes such as InterstitialSearch know what table they're operating over
		$o_res->init($o_data, array());
		
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
		$o_dm = Datamodel::load();
		$pn_table_num = $o_dm->getTableNum(get_called_class());
		if (!($t_instance = $o_dm->getInstanceByTableNum($pn_table_num))) { return null; }
		
		foreach($pa_ids as $vn_id) {
			if (!is_numeric($vn_id)) { 
				return false;
			}
		}
	
		if (!($vs_search_result_class = $t_instance->getProperty('SEARCH_RESULT_CLASSNAME'))) { return null; }
		require_once(__CA_LIB_DIR__.'/ca/Search/'.$vs_search_result_class.'.php');
		$o_data = new WLPlugSearchEngineCachedResult($pa_ids, $t_instance->tableNum());
		$o_res = new $vs_search_result_class();
		$o_res->init($o_data, array());
		
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
		
		$o_dm = Datamodel::load();
		
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
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;

		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT u.*, acl.*
			FROM ca_acl acl
			INNER JOIN ca_users AS u ON u.user_id = acl.user_id
			WHERE
				acl.table_num = ? AND acl.row_id = ? AND acl.user_id IS NOT NULL
		", $this->tableNum(), $vn_id);
		
		$va_users = array();
		$va_user_ids = $qr_res->getAllFieldValues("user_id");
		if ($qr_users = $this->makeSearchResult('ca_users', $va_user_ids)) {
			$va_initial_values = caProcessRelationshipLookupLabel($qr_users, new ca_users(), array('stripTags' => true));
		} else {
			$va_initial_values = array();
		}
		$qr_res->seek(0);
		
		$t_acl = new ca_acl();
		while($qr_res->nextRow()) {
			$va_row = array();
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
			
			$t_acl->setMode(ACCESS_WRITE);
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
			
			$t_acl->setMode(ACCESS_WRITE);
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
		
		$o_dm = Datamodel::load();
		
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
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT g.*, acl.*
			FROM ca_acl acl
			INNER JOIN ca_user_groups AS g ON g.group_id = acl.group_id
			WHERE
				acl.table_num = ? AND acl.row_id = ? AND acl.group_id IS NOT NULL
		", $this->tableNum(), $vn_id);
		
		$va_groups = array();
		$va_group_ids = $qr_res->getAllFieldValues("group_id");
		
		if (($qr_groups = $this->makeSearchResult('ca_user_groups', $va_group_ids))) {
			$va_initial_values = caProcessRelationshipLookupLabel($qr_groups, new ca_user_groups(), array('stripTags' => true));
		} else {
			$va_initial_values = array();
		}
		
		$t_acl = new ca_acl();
		
		$qr_res->seek(0);
		while($qr_res->nextRow()) {
			$va_row = array();
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
			
			$t_acl->setMode(ACCESS_WRITE);
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
			
			$t_acl->setMode(ACCESS_WRITE);
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
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT acl.*
			FROM ca_acl acl
			WHERE
				acl.table_num = ? AND acl.row_id = ? AND acl.group_id IS NULL AND acl.user_id IS NULL
		", $this->tableNum(), $vn_id);
		
		$t_acl = new ca_acl();
		$va_row = array();
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
		
		$t_acl->setMode(ACCESS_WRITE);
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
	 * Checks if model supports ACL item-based access control
	 *
	 * @return bool True if model supports ACL, false if not
	 */
	public function supportsACL() {
		if ($this->getAppConfig()->get($this->tableName().'_dont_do_item_level_access_control')) { return false; }
		return (bool)$this->getProperty('SUPPORTS_ACL');
	}
	# --------------------------------------------------------------------------------------------	
	/**
	 * Change type of record, removing any metadata that is invalid for the new type
	 *
	 * @return bool True if change succeeded, false if error
	 */
	public function changeType($pm_type) {
		if (!$this->getPrimaryKey()) { return false; }					// row must be loaded
		if (!method_exists($this, 'getTypeID')) { return false; }		// model must be type-able
		
		unset($_REQUEST['form_timestamp']);
		
		if (!($vb_already_in_transaction = $this->inTransaction())) {
			$this->setTransaction($o_t = new Transaction());
		}
		
		$vn_old_type_id = $this->getTypeID();
		$this->setMode(ACCESS_WRITE);
		$this->set($this->getTypeFieldName(), $pm_type, array('allowSettingOfTypeID' => true));
		
		// remove attributes that are not valid for new type
		$va_old_elements = $this->getApplicableElementCodes($vn_old_type_id);
		$va_new_elements = $this->getApplicableElementCodes($this->getTypeID());
		
		foreach($va_old_elements as $vn_old_element_id => $vs_old_element_code) {
			if (!isset($va_new_elements[$vn_old_element_id])) {
				$this->removeAttributes($vn_old_element_id, array('force' => true));
			}
		}
		
		if ($this->update()) {
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
	 *		dontSetValue = The template will be processed and the idno value generated but not actually set for the current row if this option is set. Default is false.
	 * @return mixed The processed template value set as the idno, or false if the model doesn't support id numbering
	 */
	public function setIdnoWithTemplate($ps_template_value=null, $pa_options=null) {
		if (($vs_idno_field = $this->getProperty('ID_NUMBERING_ID_FIELD')) && $this->opo_idno_plugin_instance) {
			$pb_dont_set_value = (bool)(isset($pa_options['dontSetValue']) && $pa_options['dontSetValue']);
		
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
	 * @param string $ps_direction Optional direction specification for self-relationships (relationships linking two rows in the same table). Valid values are 'ltor' (left-to-right) and  'rtol' (right-to-left); the direction determines which "side" of the relationship the currently loaded row is on: 'ltor' puts the current row on the left side. For many self-relations the direction determines the nature and display text for the relationship.
	 * @param array $pa_options Array of additional options:
	 *		allowDuplicates = if set to true, attempts to add a relationship that already exists will succeed. Default is false – duplicate relationships will not be created.
	 *		setErrorOnDuplicate = if set to true, an error will be set if an attempt is made to add a duplicate relationship. Default is false – don't set error. addRelationship() will always return false when creation of a duplicate relationship fails, no matter how the setErrorOnDuplicate option is set.
	 * @return boolean BaseRelationshipModel Loaded relationship model instance on success, false on error.
	 */
	public function addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $ps_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {
		global $g_ui_locale_id;
		
		if ($t_rel = parent::addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id, $ps_effective_date, $ps_source_info, $ps_direction, $pn_rank, $pa_options)) {
			if ($t_rel->numErrors()) {
				$this->errors = $t_rel->errors;
				return false;
			}
			// are there interstitials to add?
			if (isset($pa_options['interstitialValues']) && is_array($pa_options['interstitialValues'])) {
				$t_rel->setMode(ACCESS_WRITE);
				
				foreach($pa_options['interstitialValues'] as $vs_element => $va_value) { 	
					if ($t_rel->hasField($vs_element)) {
						$t_rel->set($vs_element, $va_value);
						continue;
					}				
					if (is_array($va_value)) {
						if (!isset($va_value['locale_id'])) {
							$va_value['locale_id'] = $g_ui_locale_id ? $g_ui_locale_id : ca_locales::getDefaultCataloguingLocaleID();
						}
						// array of values (complex multi-valued attribute)
						$t_rel->addAttribute($va_value, $vs_element);
					} else {
						// scalar value (simple single value attribute)
						if ($va_value) {
							$t_rel->addAttribute(array(
								'locale_id' => $pn_locale_id,
								$vs_element => $va_value
							), $vs_element);
						}
					}
				}
				$t_rel->update();
				
				if ($t_rel->numErrors()) {
					$this->errors = $t_rel->errors;
					return false;
				}
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
	 * @param string $ps_source_info Text field for storing information about source of relationship. Not currently used.
	 * @param string $ps_direction Optional direction specification for self-relationships (relationships linking two rows in the same table). Valid values are 'ltor' (left-to-right) and  'rtol' (right-to-left); the direction determines which "side" of the relationship the currently loaded row is on: 'ltor' puts the current row on the left side. For many self-relations the direction determines the nature and display text for the relationship.
	 * @param array $pa_options Array of additional options:
	 *		allowDuplicates = if set to true, attempts to edit a relationship to match one that already exists will succeed. Default is false – duplicate relationships will not be created.
	 *		setErrorOnDuplicate = if set to true, an error will be set if an attempt is made to create a duplicate relationship. Default is false – don't set error. editRelationship() will always return false when editing of a relationship fails, no matter how the setErrorOnDuplicate option is set.
	 * @return BaseRelationshipModel Loaded relationship model instance on success, false on error.
	 */
	public function editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $pa_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {
		global $g_ui_locale_id;
		
		if ($t_rel = parent::editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id, $ps_effective_date, $ps_source_info, $ps_direction, $pn_rank, $pa_options)) {
			// are there interstitials to add?
			if (isset($pa_options['interstitialValues']) && is_array($pa_options['interstitialValues'])) {
				$t_rel->setMode(ACCESS_WRITE);
				
				foreach($pa_options['interstitialValues'] as $vs_element => $va_value) { 	
					if ($t_rel->hasField($vs_element)) {
						$t_rel->set($vs_element, $va_value);
						continue;
					}				
					if (is_array($va_value)) {
						if (!isset($va_value['locale_id'])) {
							$va_value['locale_id'] = $g_ui_locale_id ? $g_ui_locale_id : ca_locales::getDefaultCataloguingLocaleID();
						}
						// array of values (complex multi-valued attribute)
						$t_rel->replaceAttribute($va_value, $vs_element);
					} else {
						// scalar value (simple single value attribute)
						if ($va_value) {
							$t_rel->replaceAttribute(array(
								'locale_id' => $pn_locale_id,
								$vs_element => $va_value
							), $vs_element);
						}
					}
				}
				$t_rel->update();
				if ($t_rel->numErrors()) {
					$this->errors = $t_rel->errors;
					return false;
				}
			}
		}
		return $t_rel;
	}
	# --------------------------------------------------------------------------------------------
}
?>