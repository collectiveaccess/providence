<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseRelationshipModel.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BundlableLabelableBaseModelWithAttributes.php');
require_once(__CA_LIB_DIR__.'/IRelationshipModel.php');
require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 
class BaseRelationshipModel extends BundlableLabelableBaseModelWithAttributes implements IRelationshipModel {
	# ------------------------------------------------------
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	//
	// We use a single search class for all relationship tables
	//
	protected $SEARCH_CLASSNAME = 'InterstitialSearch';
	protected $SEARCH_RESULT_CLASSNAME = 'InterstitialSearchResult';
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function insert($pa_options=null) {
		if ($vn_rc = parent::insert($pa_options)) {
			$t_left = Datamodel::getInstanceByTableNum($this->getLeftTableNum());
			$t_right = Datamodel::getInstanceByTableNum($this->getRightTableNum());
			
			foreach(array($this->getRightTableName() => $t_left, $this->getLeftTableName() => $t_right) as $vs_other_table_name => $t_instance) {
				if ((bool)$t_instance->getProperty('SUPPORTS_ACL_INHERITANCE')) {
					if (is_array($va_inheritors = $t_instance->getProperty('ACL_INHERITANCE_LIST')) && in_array($vs_other_table_name, $va_inheritors)) {
						ca_acl::applyACLInheritanceToRelatedRowFromRow($t_instance, ($vs_other_table_name == $this->getLeftTableName()) ? $this->get($this->getRightTableFieldName()) : $this->get($this->getLeftTableFieldName()), $vs_other_table_name, ($vs_other_table_name == $this->getLeftTableName()) ? $this->get($this->getLeftTableFieldName()) : $this->get($this->getRightTableFieldName()));
					}
				}
			}
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function update($pa_options=null) {
		if ($vn_rc = parent::update($pa_options)) {
			$t_left = Datamodel::getInstanceByTableNum($this->getLeftTableNum());
			$t_right = Datamodel::getInstanceByTableNum($this->getRightTableNum());
			
			foreach(array($this->getRightTableName() => $t_left, $this->getLeftTableName() => $t_right) as $vs_other_table_name => $t_instance) {
				if ((bool)$t_instance->getProperty('SUPPORTS_ACL_INHERITANCE')) {
					if (is_array($va_inheritors = $t_instance->getProperty('ACL_INHERITANCE_LIST')) && in_array($vs_other_table_name, $va_inheritors)) {
						ca_acl::applyACLInheritanceToRelatedRowFromRow($t_instance, ($vs_other_table_name == $this->getLeftTableName()) ? $this->get($this->getRightTableFieldName()) : $this->get($this->getLeftTableFieldName()), $vs_other_table_name, ($vs_other_table_name == $this->getLeftTableName()) ? $this->get($this->getLeftTableFieldName()) : $this->get($this->getRightTableFieldName()));
					}
				}
			}
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		$t_left = Datamodel::getInstanceByTableNum($this->getLeftTableNum());
		$vn_left_id = $this->get($this->getLeftTableFieldName());
		$vn_right_id = $this->get($this->getRightTableFieldName());
		
		$t_right = Datamodel::getInstanceByTableNum($this->getRightTableNum());
		if ($vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list)) {
			foreach(array($this->getRightTableName() => $t_left, $this->getLeftTableName() => $t_right) as $vs_other_table_name => $t_instance) {
				if ((bool)$t_instance->getProperty('SUPPORTS_ACL_INHERITANCE')) {
					if (is_array($va_inheritors = $t_instance->getProperty('ACL_INHERITANCE_LIST')) && in_array($vs_other_table_name, $va_inheritors)) {
						ca_acl::applyACLInheritanceToRelatedRowFromRow($t_instance, ($vs_other_table_name == $this->getLeftTableName()) ? $vn_right_id : $vn_left_id, $vs_other_table_name, ($vs_other_table_name == $this->getLeftTableName()) ? $vn_left_id : $vn_right_id, array('deleteACLOnly' => true));
					}
				}
			}
		}
		return $vn_rc;
	}
    # ------------------------------------------------------------------
    /**
     * 
     *
     */
    public function set($pa_fields, $pm_value="", $pa_options=null) {
        if($pa_fields && $pm_value && !is_array($pa_fields)) { $pa_fields = [$pa_fields => $pm_value]; }
        if(is_array($pa_fields)) {
            if (isset($pa_fields['type_id']) && !is_numeric($pa_fields['type_id'])) {
                $t_rel_type = new ca_relationship_types();
                if ($vn_type_id = $t_rel_type->getRelationshipTypeID($this->tableNum(), $pa_fields['type_id'])) {
                    $pa_fields['type_id'] = $vn_type_id;
                }
            }
        } 
        return parent::set($pa_fields, $pm_value, $pa_options);
    }
	# ------------------------------------------------------
	/**
	 * Returns name of the "left" table (by convention the table mentioned first in the relationship table name)
	 * (eg. if the table name is ca_objects_x_entities then the "left" name is ca_objects)
	 *
	 * @return string
	 */
	public function getLeftTableName() {
		return $this->RELATIONSHIP_LEFT_TABLENAME;
	}
	# ------------------------------------------------------
	/**
	 * Returns name of the "right" table (by convention the table mentioned second in the relationship table name)
	 * (eg. if the table name is ca_objects_x_entities then the "right" name is ca_entities)
	 *
	 * @return string
	 */
	public function getRightTableName() {
		return $this->RELATIONSHIP_RIGHT_TABLENAME;
	}
	# ------------------------------------------------------
	/**
	 * Returns name of the table in the relationship that is not the specified one.
	 * (eg. if the table name passed is ca_objects and the relationship is ca_objects_x_entities then the "other" name is ca_entities)
	 *
	 * @param string $ps_tablename A table name that is part of the relationship the model represents
	 * @return string The name of the table in the relationship that is not the specified table. If the specified table is not part of the relationship null is returned.
	 */
	public function getOtherTableName($ps_tablename) {
		if (!in_array($ps_tablename, array($this->RELATIONSHIP_LEFT_TABLENAME, $this->RELATIONSHIP_RIGHT_TABLENAME))) { return null; }
		return ($ps_tablename == $this->RELATIONSHIP_LEFT_TABLENAME) ? $this->RELATIONSHIP_RIGHT_TABLENAME : $this->RELATIONSHIP_LEFT_TABLENAME;
	}
	# ------------------------------------------------------
	/**
	 * Returns table number of the "left" table (by convention the table mentioned first in the relationship table name)
	 * (eg. if the table name is ca_objects_x_entities then the "left" number corresponds to ca_objects)
	 *
	 * @return int
	 */
	public function getLeftTableNum() {
		return Datamodel::getTableNum($this->getLeftTableName());
	}
	# ------------------------------------------------------
	/**
	 * Returns table number of the "right" table (by convention the table mentioned second in the relationship table name)
	 * (eg. if the table name is ca_objects_x_entities then the "right" number corresponds to ca_entities)
	 *
	 * @return int
	 */
	public function getRightTableNum() {
		return Datamodel::getTableNum($this->getRightTableName());
	}
	# ------------------------------------------------------
	/**
	 * Returns name of the "left" table (by convention the table mentioned first in the relationship table name) field name
	 * (eg. if the table name is ca_objects_x_entities then the "left" name is ca_objects)
	 *
	 * @return string
	 */
	public function getLeftTableFieldName() {
		return $this->RELATIONSHIP_LEFT_FIELDNAME;
	}
	# ------------------------------------------------------
	/**
	 * Returns name of the "right" table (by convention the table mentioned first in the relationship table name) field name
	 * (eg. if the table name is ca_objects_x_entities then the "left" name is ca_objects)
	 *
	 * @return string
	 */
	public function getRightTableFieldName() {
		return $this->RELATIONSHIP_RIGHT_FIELDNAME;
	}
	# ------------------------------------------------------
	/**
	 * Returns name of the foreign key pointing to ca_relationship_types (typically = 'type_id')
	 *
	 * @return string
	 */
	public function getTypeFieldName() {
		return $this->RELATIONSHIP_TYPE_FIELDNAME;
	}
	# ------------------------------------------------------
	/**
	 * Returns true if relationship relates two records in the same table
	 *
	 * @return bool
	 */
	public function isSelfRelationship() {
		return (bool)($this->getLeftTableNum() == $this->getRightTableNum());
	}
	# ------------------------------------------------------
	/**
	 * Returns true if model is a relationship
	 *
	 * @return bool
	 */
	public function isRelationship() {
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Returns an array of relationship types for this relation, subject to sub type restrictions. Each array entry
	 * is an array with all fields from the ca_relationship_types record 
	 *
	 * @param int $pn_sub_type_left_id
	 * @param int $pn_sub_type_right_id
	 * @param array $pa_options Options include:
	 *		idsOnly = Return list of relationship type_ids only. [Default is false]
	 *
	 * @return array
	 */
	public function getRelationshipTypes($pn_sub_type_left_id=null, $pn_sub_type_right_id=null, $pa_options=null) {
		$pb_ids_only = caGetOption('idsOnly', $pa_options, false);
		
		$o_db = $this->getDb();
		
		$vs_sub_type_left_sql = '';
		
		$va_params = [$this->tableNum()];
		if ($pn_sub_type_left_id) {
			// Support hierarchical subtypes - if the subtype restriction is a type with parents then include those as well
			// Allows subtypes to "inherit" bindings from parent types
			$t_list_item = new ca_list_items($pn_sub_type_left_id);
			$va_ancestor_ids = $t_list_item->getHierarchyAncestors(null, array('idsOnly' => true, 'includeSelf' => true));
		
			$va_params[] = $pn_sub_type_left_id; $va_params[] = $va_ancestor_ids;
			$vs_sub_type_left_sql = '(crt.sub_type_left_id IS NULL OR crt.sub_type_left_id = ? OR (crt.include_subtypes_left = 1 AND crt.sub_type_left_id IN (?)))';
		}
		$vs_sub_type_right_sql = '';
		if ($pn_sub_type_right_id) {
			// Support hierarchical subtypes - if the subtype restriction is a type with parents then include those as well
			// Allows subtypes to "inherit" bindings from parent types
			$t_list_item = new ca_list_items($pn_sub_type_right_id);
			$va_ancestor_ids = $t_list_item->getHierarchyAncestors(null, array('idsOnly' => true, 'includeSelf' => true));
			
			$va_params[] = $pn_sub_type_right_id; $va_params[] = $va_ancestor_ids;
			$vs_sub_type_right_sql = '(crt.sub_type_right_id IS NULL OR crt.sub_type_right_id = ? OR (crt.include_subtypes_right = 1 AND crt.sub_type_right_id IN (?)))';
		}
		
		$add_parents = [];
		$vs_restrict_to_relationship_type_sql = '';
		if (isset($pa_options['restrict_to_relationship_types']) && $pa_options['restrict_to_relationship_types']) {
			if (!is_array($pa_options['restrict_to_relationship_types'])) {
				$pa_options['restrict_to_relationship_types'] = array($pa_options['restrict_to_relationship_types']);
			}
			if (sizeof($pa_options['restrict_to_relationship_types'])) {
				$va_restrict_to_type_list = [];
				$t_rel_type = new ca_relationship_types();
				
				foreach($pa_options['restrict_to_relationship_types'] as $vs_type_code) {
					$va_criteria = array('table_num' => $this->tableNum());
					if (is_numeric($vs_type_code)) {
						$va_criteria['type_id'] = (int)$vs_type_code;
					} else {
						$va_criteria['type_code'] = $vs_type_code;
					}
					if ($t_rel_type->load($va_criteria)) {
						$va_restrict_to_type_list[] = "(crt.hier_left >= ".$t_rel_type->get('hier_left')." AND crt.hier_right <= ".$t_rel_type->get('hier_right').")";
					}
				}
				
				if(sizeof($add_parents = $this->_expandRestrictionWithParents($pa_options['restrict_to_relationship_types']))) {
					$va_restrict_to_type_list[] = "(crt.type_id IN (".join(',', $add_parents)."))";
				}
				
				if (sizeof($va_restrict_to_type_list)) {
					$vs_restrict_to_relationship_type_sql = " AND (".join(' OR ', $va_restrict_to_type_list).")";
				}
			}
		}
		
		$vs_sql = "
			SELECT *
			FROM ca_relationship_types crt
			INNER JOIN ca_relationship_type_labels AS crtl ON crt.type_id = crtl.type_id
			WHERE
				(crt.table_num = ?) ".(($vs_sub_type_left_sql || $vs_sub_type_right_sql) ? ' AND ' : '')." 
				{$vs_sub_type_left_sql} ".(($vs_sub_type_left_sql && $vs_sub_type_right_sql) ? ' AND ' : '')." {$vs_sub_type_right_sql}
				AND (crt.parent_id IS NOT NULL)
				{$vs_restrict_to_relationship_type_sql}
		";
		$qr_res = $o_db->query($vs_sql, $va_params);
		
		$va_types = [];
		while($qr_res->nextRow()) {
			if ($pb_ids_only) {
				$va_types[] = (int)$qr_res->get('type_id');
			} else {
				$vs_key = '';
				if (strlen($vs_rank = $qr_res->get('rank'))) {
					$vs_key .= (int)sprintf("%08d", (int)$qr_res->get('rank'));
				}
				$vs_key .= $qr_res->get('typename_forward');
				$va_types[$vs_key][(int)$qr_res->get('type_id')][(int)$qr_res->get('locale_id')] = $qr_res->getRow();
			}
		}
		if ($pb_ids_only) { return $va_types; }
		
		ksort($va_types, SORT_NUMERIC);
		$va_sorted_types = [];
		foreach($va_types as $vs_k => $va_v) {
			foreach($va_v as $vn_type_id => $va_types_by_locale) {
				$va_sorted_types[$vn_type_id] = $va_types_by_locale;
			}
		}
		return caExtractValuesByUserLocale($va_sorted_types, null, null, []);
	}
	# ------------------------------------------------------
	/**
	 * Returns an HTML <select> element of relationship types with values=type_id and option text = to the typename
	 */
	public function getRelationshipTypesAsHTMLSelect($ps_orientation, $pn_sub_type_left_id=null, $pn_sub_type_right_id=null, $pa_attributes=null, $pa_options=null) {
		$vs_left_table_name = $this->getLeftTableName();
		$vs_right_table_name = $this->getRightTableName();
		if (!in_array($ps_orientation, array($vs_left_table_name, $vs_right_table_name))) { $ps_orientation = $vs_left_table_name; }
		
		$va_types = $this->getRelationshipTypes($pn_sub_type_left_id, $pn_sub_type_right_id, $pa_options);
		$va_options = [];
		
		$va_parent_ids = [];
		$vn_l = 0;
		foreach($va_types as $va_type) {
			if (($vn_i = array_search($va_type['parent_id'], $va_parent_ids)) === false) {
				$va_parent_ids[] = $va_type['parent_id']; 
				$vn_l++;
			} else {
				if ($vn_i < sizeof($va_parent_ids) - 1) {
					$vn_l = sizeof($va_parent_ids) - 1 - $vn_i;
				}
			}
		
			$va_options[str_repeat("&#160;&#160;&#160;", $vn_l-1).(($ps_orientation == $vs_left_table_name) ? $va_type['typename'] : $va_type['typename_reverse'])] = $va_type['type_id'];
		}
		
		$vs_name = caGetOption('name', $pa_attributes, 'type_id');
		
		return caHTMLSelect($vs_name, $va_options, $pa_attributes, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function _expandRestrictionWithParents(array $types) : array {
		$t_rel_type = new ca_relationship_types();
		$type_info = $t_rel_type->getRelationshipInfo($this->tableName());
		$expanded_types = [];
		foreach($types as $type_id) {
			$ancestor_types = $t_rel_type->getHierarchyAncestors($type_id,['idsOnly' => true, 'omitRoot' => true, 'includeSelf' => false]);
			if(is_array($ancestor_types) && sizeof($ancestor_types)) {
				$expanded_types = array_merge($expanded_types, $ancestor_types);
			}
		}
		return array_unique(array_filter($expanded_types, 'is_numeric'));
	}
	# ------------------------------------------------------
	/**
	 * Returns an associative array of relationship types for the relationship
	 * organized by the sub_type_id specified by $ps_orientation. If $ps_orientation is the name of the "right" table
	 * then sub_type_left_id is used for keys in the array, if $ps_orientation is the name of the "left" table
	 * then sub_type_right_id is used for keys.
	 *
	 * For example, for ca_objects_x_entities, if $ps_orientation is ca_objects then then sub_type_right_id is
	 * used as the key; if ca_entities is passed then sub_type_left_id is used; if a table name is passed that
	 * is not either side of the relation then an empty array is returned
	 *
	 */
	public function getRelationshipTypesBySubtype($ps_orientation, $pn_type_id, $pa_options=null) {
		unset($pa_options['request']);
		if (!$this->hasField('type_id')) { return []; }
		$vs_left_table_name = $this->getLeftTableName();
		$vs_right_table_name = $this->getRightTableName();
		
		$restrict_to_relationship_types = caGetOption(['restrictToRelationshipTypes', 'restrict_to_relationship_types'], $pa_options, null);
		$vb_dont_include_subtypes_in_type_restriction = (bool)caGetOption('dont_include_subtypes_in_type_restriction', $pa_options, false);
		
		$o_db = $this->getDb();
		$t_rel_type = new ca_relationship_types();
		
		// Expand restriction to include parent types
		$add_parents = [];
		$vs_restrict_to_relationship_type_sql = '';
		if (isset($restrict_to_relationship_types) && $restrict_to_relationship_types) {
			if(!is_array($restrict_to_relationship_types)) {
				$restrict_to_relationship_types = [$restrict_to_relationship_types];
			}
			if(sizeof($restrict_to_relationship_types)) {
				$va_restrict_to_type_list = [];
				foreach($restrict_to_relationship_types as $vs_type_code) {
					if (!strlen(trim($vs_type_code))) { continue; }
					
					$va_criteria = array('table_num' => $this->tableNum());
					if (is_numeric($vs_type_code)) {
						$va_criteria['type_id'] = (int)$vs_type_code;
					} else {
						$va_criteria['type_code'] = $vs_type_code;
					}
					if ($t_rel_type->load($va_criteria)) {
						$va_restrict_to_type_list[] = "(crt.hier_left >= ".$t_rel_type->get('hier_left')." AND crt.hier_right <= ".$t_rel_type->get('hier_right').")";
					}
				}
				
				if(sizeof($add_parents = $this->_expandRestrictionWithParents($restrict_to_relationship_types))) {
					$va_restrict_to_type_list[] = "(crt.type_id IN (".join(',', $add_parents)."))";
				}
				
				if (sizeof($va_restrict_to_type_list)) {
					$vs_restrict_to_relationship_type_sql = " AND (".join(' OR ', $va_restrict_to_type_list).")";
				}
			}
		}
		
		$qr_res = $o_db->query("
			SELECT *
			FROM ca_relationship_types crt
			INNER JOIN ca_relationship_type_labels AS crtl ON crt.type_id = crtl.type_id
			WHERE
				(crt.table_num = ?)
				{$vs_restrict_to_relationship_type_sql}
		", $this->tableNum());		
		
		// Support hierarchical subtypes - if the subtype restriction is a type with parents then include those as well
		// Allows subtypes to "inherit" bindings from parent types
		
		$t_list_item = new ca_list_items($pn_type_id);
		
		if (!$vb_dont_include_subtypes_in_type_restriction) {
			if (!is_array($va_ancestor_ids = $t_list_item->getHierarchyAncestors(null, array('idsOnly' => true, 'includeSelf' => true)))) {
				$va_ancestor_ids = [];
			}
			// remove hierarchy root from ancestor list, otherwise invalid bindings 
			// from root nodes (which are not "real" rel types) may be inherited
			array_pop($va_ancestor_ids);
		} else {
			$va_ancestor_ids = array($pn_type_id);
		}
		
		$va_types = [];
		$va_parent_ids = [];
		$vn_l = 0;
						
		$vn_root_id = ($t_rel_type->load(array('parent_id' => null, 'table_num' =>  $this->tableNum()))) ? $t_rel_type->getPrimaryKey() : null;
		$va_hier = [];
		
		if ($vs_left_table_name === $vs_right_table_name) {
			// ----------------------------------------------------------------------------------------
			// self relationship
			while($qr_res->nextRow()) {
				$va_row = $qr_res->getRow();
				$vn_parent_id = $va_row['parent_id'];
				$va_hier[$vn_parent_id][] = $va_row['type_id'];
				
				$va_left_subtype_candidates = $va_row['include_subtypes_left'] ? $va_ancestor_ids : [$pn_type_id];
				$va_right_subtype_candidates = $va_row['include_subtypes_right'] ? $va_ancestor_ids : [$pn_type_id];
				
				$va_left_x = $va_right_x = [];
				
				// skip type if it has a subtype set and it's not in our list
				$vs_subtype_orientation = 'none';
				$vs_subtype = null;
				if ($pn_type_id) {
					if (!$va_row['sub_type_left_id']) {
						// no left restriction
						$vs_subtype_orientation = 'none';
					} elseif($va_row['sub_type_left_id'] && in_array($va_row['sub_type_left_id'], $va_left_subtype_candidates)) {
					    // is left
					    $vs_subtype_orientation = "ltor";
						$vs_subtype = $va_row['sub_type_right_id'];	
						$va_right_x = $va_row['sub_type_right_id'] ? [$va_row['sub_type_right_id']] : null;
					} else {
					    // is right
					    $vs_subtype_orientation = "rtol";
						$vs_subtype = $va_row['sub_type_left_id'];	
						$va_left_x = $va_row['sub_type_left_id'] ? [$va_row['sub_type_left_id']] : null;
					}
					if (!$va_row['sub_type_right_id']) {
					    // no right restriction
					} elseif($va_row['sub_type_right_id'] && in_array($va_row['sub_type_right_id'], $va_right_subtype_candidates)) {
						// is right
						if ($vs_subtype_orientation == 'ltor') {
							// left and right
							$vs_subtype = $va_row['sub_type_right_id'];
							$vs_subtype_orientation = 'none';
						} else {
							$vs_subtype_orientation = "rtol";
							$vs_subtype = $va_row['sub_type_left_id'];	
						    $va_left_x = $vs_subtype ? [$va_row['sub_type_left_id']] : null;
						}
					} else {
					    // is left
					    $vs_subtype_orientation = "ltor";
						$vs_subtype = $va_row['sub_type_right_id'];	
						$va_right_x = $va_row['sub_type_right_id'] ? [$va_row['sub_type_right_id']] : null;
					}
					if (($va_row['sub_type_left_id'] || $va_row['sub_type_right_id']) && !$vs_subtype_orientation) {
						continue;
					}
				}
				if (!$vs_subtype) { $vs_subtype = 'NULL'; }
			
				switch($vs_subtype_orientation) {
					case 'rtol':
						$va_tmp = $va_row;
						$vs_key = ((strlen($va_tmp['rank']) > 0)  ? sprintf("%08d", (int)$va_tmp['rank']) : "").preg_replace('![^A-Za-z0-9_]+!', '_', $va_tmp['typename_reverse']);
						
						$va_tmp['typename'] = $va_tmp['typename_reverse'];
						unset($va_tmp['typename_reverse']);		// we pass the typename adjusted for direction in 'typename', so there's no need to include typename_reverse in the returned values
						$va_tmp['direction'] = 'rtol';
						
                        if(!$va_left_x) {
                             $va_types[$vn_parent_id]['NULL'][$vs_key][$va_row['type_id']][$va_row['locale_id']] = $va_tmp;
                        } else {
                            foreach($va_left_x as $vs_left_subtype) {
                                $va_types[$vn_parent_id][$vs_left_subtype][$vs_key][$va_row['type_id']][$va_row['locale_id']] = $va_tmp;	
                            }
                        }
						break;
					case 'ltor':
						$va_tmp = $va_row;
						$vs_key = ((strlen($va_tmp['rank']) > 0)  ? sprintf("%08d", (int)$va_tmp['rank']) : "").preg_replace('![^A-Za-z0-9_]+!', '_', $va_tmp['typename']);
					
						unset($va_tmp['typename_reverse']);		// we pass the typename adjusted for direction in 'typename', so there's no need to include typename_reverse in the returned values
						$va_tmp['direction'] = 'ltor';
						
                        if (!$va_right_x) { 
                            $va_types[$vn_parent_id]['NULL'][$vs_key][$va_row['type_id']][$va_row['locale_id']] = $va_tmp;
                        } else {
                            foreach($va_right_x as $vs_right_subtype) {
                                $va_types[$vn_parent_id][$vs_right_subtype][$vs_key][$va_row['type_id']][$va_row['locale_id']] = $va_tmp;
                            }
                        }
						break;
					default:
						$va_tmp = $va_row;
						if (trim($va_tmp['typename']) == trim($va_tmp['typename_reverse'])) {
							//
							// If the sides of the self-relationship are the same then treat it like a normal relationship type: one entry in the
							// list and a plain type_id value
							//
							unset($va_tmp['typename_reverse']);		// we pass the typename adjusted for direction in 'typename', so there's no need to include typename_reverse in the returned values

							$va_tmp['direction'] = null;
							
							$vs_key = ((strlen($va_tmp['rank']) > 0)  ? sprintf("%08d", (int)$va_tmp['rank']) : "").preg_replace('![^A-Za-z0-9_]+!', '_', $va_tmp['typename']);
							
							if(is_array($va_left_x)) { 
                                foreach($va_left_x as $vs_left_subtype) {
                                    $va_types[$vn_parent_id][$vs_left_subtype][$vs_key][$va_row['type_id']][$va_row['locale_id']] = $va_tmp;
                                }
                            }
							if (is_array($va_right_x)) {
                                foreach($va_right_x as $vs_right_subtype) {
                                    $va_types[$vn_parent_id][$vs_right_subtype][$vs_key][$va_row['type_id']][$va_row['locale_id']] = $va_tmp;
                                }
                            }
                            
							if(in_array($va_tmp['type_id'], $add_parents)) {
								$va_tmp['disabled'] = true;
							}
							
							if (!isset($va_types[$vn_parent_id][$vs_subtype][$vs_key][$va_row['type_id']][$va_row['locale_id']])) {
								$va_types[$vn_parent_id][$vs_subtype][$vs_key][$va_row['type_id']][$va_row['locale_id']] = $va_tmp;	
							}
						} else {
							//
							// If each side of the self-relationship type are different then add both to the list with special type_id values that
							// indicate the directionality of the typename (ltor = left to right = "typename"; rtor = right to left = "typename_reverse")
							//
							$va_tmp = $va_row;
							unset($va_tmp['typename_reverse']);		// we pass the typename adjusted for direction in 'typename', so there's no need to include typename_reverse in the returned values

							$vs_key = ((strlen($va_tmp['rank']) > 0)  ? sprintf("%08d", (int)$va_tmp['rank']) : "").preg_replace('![^A-Za-z0-9_]+!', '_', $va_tmp['typename']);
							$va_tmp['direction'] = 'ltor';
							
							if(is_array($va_left_x)) {
                                foreach($va_left_x as $vs_left_subtype) {
                                    $va_types[$vn_parent_id][$vs_left_subtype][$vs_key]['ltor_'.$va_row['type_id']][$va_row['locale_id']] = $va_tmp;
                                }
                            }
							
							if (!isset($va_types[$vn_parent_id][$vs_subtype][$vs_key]['ltor_'.$va_row['type_id']][$va_row['locale_id']])) {
								$va_types[$vn_parent_id][$vs_subtype][$vs_key]['ltor_'.$va_row['type_id']][$va_row['locale_id']] = $va_tmp;	
							}
							
							$va_tmp = $va_row;
							$va_tmp['typename'] =  $va_tmp['typename_reverse'];
							unset($va_tmp['typename_reverse']);		// we pass the typename adjusted for direction in 'typename', so there's no need to include typename_reverse in the returned values
	
							$vs_key = ((strlen($va_tmp['rank']) > 0)  ? sprintf("%08d", (int)$va_tmp['rank']) : "").preg_replace('![^A-Za-z0-9_]+!', '_', $va_tmp['typename']);
							$va_tmp['direction'] = 'rtol';
							
							if(in_array($va_tmp['type_id'], $add_parents)) {
								$va_tmp['disabled'] = true;
							}
							
							if(is_array($va_right_x)) {
                                foreach($va_right_x as $vs_right_subtype) {
                                    $va_types[$vn_parent_id][$vs_right_subtype][$vs_key]['rtol_'.$va_row['type_id']][$va_row['locale_id']] = $va_tmp;
                                }
                            }
							
							if (!isset($va_types[$vn_parent_id][$vs_subtype][$vs_key]['rtol_'.$va_row['type_id']][$va_row['locale_id']])) {
								$va_types[$vn_parent_id][$vs_subtype][$vs_key]['rtol_'.$va_row['type_id']][$va_row['locale_id']] = $va_tmp;	
							}
						}
						
						break;
				}
			}
			
			$va_types = $this->_processRelationshipHierarchy($vn_root_id, $va_hier, $va_types, 1);
			$va_processed_types = array('_type_map' => []);
			$va_subtype_lookups = [];

			foreach($va_types as $vs_subtype => $va_types_by_subtype) {
				$va_types_by_locale = [];
				foreach($va_types_by_subtype as $vs_key => $va_types_by_key) {
					foreach($va_types_by_key as $vs_k => $va_v) {
						foreach($va_v as $vs_k2 => $vs_v2) {
							$va_types_by_locale[$vs_k][$vs_k2] = $vs_v2;
						}
					}
				}
			
				if (!$vb_dont_include_subtypes_in_type_restriction) {
					// include mapping from parent type used in restriction to child types that inherit the binding
					if (($vs_subtype != 'NULL') && (!isset($va_subtype_lookups[$vs_subtype]) || !$va_subtype_lookups[$vs_subtype])) {
						$va_children = $t_list_item->getHierarchyChildren($vs_subtype, array('idsOnly' => true));
						foreach($va_children as $vn_child) {
							$va_processed_types['_type_map'][$vn_child] = $vs_subtype;
						}
						$va_subtype_lookups[$vs_subtype] = true;
					}
				}
				$va_processed_types[$vs_subtype] = caExtractValuesByUserLocale($va_types_by_locale, null, null, array('returnList' => true));
			}
		
			
		} else {
			// ----------------------------------------------------------------------------------------
			// regular relationship
			if (!in_array($ps_orientation, array($vs_left_table_name, $vs_right_table_name))) { return []; }
			while($qr_res->nextRow()) {
				$va_row = $qr_res->getRow();
				
				$vn_parent_id = $va_row['parent_id'];
				$va_hier[$vn_parent_id][] = $va_row['type_id'];
				
				$vb_expand_types = false;
				if ($ps_orientation == $vs_left_table_name) {
					// right-to-left
					
					// expand subtype
					$va_subtypes_to_check = ($va_row['sub_type_left_id'] > 0) ? ((bool)$va_row['include_subtypes_left'] ? caMakeTypeIDList($vs_left_table_name, [$va_row['sub_type_left_id']]) : [$va_row['sub_type_left_id']]) : null;
					
					// skip type if record type is set, it has a subtype set and it's not in our list
					if ($pn_type_id && ($va_subtypes_to_check && !sizeof(array_intersect($va_subtypes_to_check, $va_ancestor_ids)))) { continue; }
					$vs_subtype = $va_row['sub_type_right_id'];
					
					$vs_key = ((strlen($va_row['rank']) > 0)  ? sprintf("%08d", (int)$va_row['rank']) : "").preg_replace('![^A-Za-z0-9_]+!', '_', mb_strtolower($va_row['typename']));
					
				} else {
					// left-to-right
					
					// expand subtype
					$va_subtypes_to_check = ($va_row['sub_type_right_id'] > 0) ? ((bool)$va_row['include_subtypes_right'] ? caMakeTypeIDList($vs_right_table_name, [$va_row['sub_type_right_id']]) : [$va_row['sub_type_right_id']]) : null;
					
					// skip type if record type is set, it has a subtype set and it's not in our list
					if ($pn_type_id && ($va_subtypes_to_check && !sizeof(array_intersect($va_subtypes_to_check, $va_ancestor_ids)))) { continue; }
					$vs_subtype = $va_row['sub_type_left_id'];	
					
					$va_row['typename'] = $va_row['typename_reverse'];
					
					$vs_key = ((strlen($va_row['rank']) > 0)  ? sprintf("%08d", (int)$va_row['rank']) : "").preg_replace('![^A-Za-z0-9_]+!', '_', mb_strtolower($va_row['typename_reverse']));
				
				}
				
				if(in_array($va_row['type_id'], $add_parents)) {
					$va_row['disabled'] = true;
				}
				unset($va_row['typename_reverse']);		// we pass the typename adjusted for direction in '_display', so there's no need to include typename_reverse in the returned values
				if (!$vs_subtype) { $vs_subtype = 'NULL'; }
				
				$vn_type_id = $va_row['type_id'];
				$va_types[$vn_parent_id][$vs_subtype][$vs_key][$vn_type_id][$va_row['locale_id']] = $va_row;
			}
			
			$va_types = $this->_processRelationshipHierarchy($vn_root_id, $va_hier, $va_types, 1);
		
			$va_processed_types = array('_type_map' => []);
			$va_subtype_lookups = [];
		
			foreach($va_types as $vs_subtype => $va_types_by_subtype) {
				$va_types_by_locale = [];
				foreach($va_types_by_subtype as $vs_key => $va_types_by_key) {
					foreach($va_types_by_key as $vn_locale_id => $va_t) {
						if (!is_array($va_types_by_locale[$vn_locale_id] ?? null)) { $va_types_by_locale[$vn_locale_id] = []; }
						$va_types_by_locale[$vn_locale_id] += $va_t;
					}
				}
				
				if (!$vb_dont_include_subtypes_in_type_restriction) {
					// include mapping from parent type used in restriction to child types that inherit the binding
					if (($vs_subtype != 'NULL') && (!isset($va_subtype_lookups[$vs_subtype]) || !$va_subtype_lookups[$vs_subtype])) {
						$va_children = $t_list_item->getHierarchyChildren($vs_subtype, array('idsOnly' => true));
						foreach($va_children as $vn_child) {
							$va_processed_types['_type_map'][$vn_child] = $vs_subtype;
						}
						$va_subtype_lookups[$vs_subtype] = true;
					}
				}
				
				$extracted_values = caExtractValuesByUserLocale($va_types_by_locale, null, null, ['returnList' => true]);					
				$va_processed_types[$vs_subtype] = array_values(caSortArrayByKeyInValue($extracted_values, ['typename']));
			}
		}
		return $va_processed_types;
	}
	# ------------------------------------------------------
	private function _processRelationshipHierarchy($pn_id, $pa_hier, $pa_types, $pn_level) {
		$va_types_to_return = [];
		if(!is_array($pa_hier[$pn_id] ?? null)) { return []; }
		if (!is_array($pa_types[$pn_id] ?? null) ) { return [];}
		foreach($pa_types[$pn_id] as $vs_sub_types => $va_list) {	// items in this level
			ksort($va_list);
			foreach($va_list as $vs_key => $va_list_by_type_id) {
				foreach($va_list_by_type_id as $vn_type_id => $va_item) {
					$va_types_to_return[$vs_sub_types][$vs_key][$vn_type_id] = $va_item;		// output item
				
					// look for sub items
					if (is_array($pa_hier[$vn_type_id] ?? null)) {
						if (is_array($va_tmp = $this->_processRelationshipHierarchy($vn_type_id, $pa_hier, $pa_types, $pn_level + 1))) {
							foreach($va_tmp as $x_vs_sub_types => $x_va_list) {
								foreach($x_va_list as $x_vs_key => $x_va_list_by_type_id) {
									foreach($x_va_list_by_type_id as $x_vn_type_id => $x_va_item) {
										foreach($x_va_item as $vn_i => $x_va_item_entry) {
											$x_va_item[$vn_i]['typename'] = '&#160;&#160;&#160;&#160;'.$x_va_item_entry['typename'] ;
										}
										$va_types_to_return[$x_vs_sub_types][$x_vs_key][$x_vn_type_id] = $x_va_item;
									}
								}
							}
						}
					}
				}
			}
		}
		
		return $va_types_to_return;
	}
	
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function getLeftTableInstance() {
		$t_left = Datamodel::getInstanceByTableName($this->RELATIONSHIP_LEFT_TABLENAME, false);

		if ($t_left) {
			$t_left->load($this->get($this->getLeftTableFieldName()));
		}
		return $t_left;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function getRightTableInstance() {
		$t_right = Datamodel::getInstanceByTableName($this->RELATIONSHIP_RIGHT_TABLENAME, false);
		
		if ($t_right) {
			$t_right->load($this->get($this->getRightTableFieldName()));
		}
		return $t_right;
	}
	# ------------------------------------------------------
	/**
	 * 
	 */
	public function getTypeID($pn_id = NULL) {
		if ($pn_id && BaseModel::hasField('type_id')) {
			$qr_res = $this->getDb()->query("SELECT type_id FROM ".$this->tableName()." WHERE ".$this->primaryKey()." = ?", array((int)$pn_id));
			if($qr_res->nextRow()) {
				return $qr_res->get('type_id');
			}
			return null;
		}
		return (BaseModel::hasField('type_id')) ? BaseModel::get('type_id') : null;
	}
	# ------------------------------------------------------
	/**
	 * Get instance for related table opposite the one specified by $pm_tablename_or_num.
	 * For example, if the relationship is ca_objects_x_entities, passing $pm_tablename_or_num = ca_objects will
	 * result in a ca_entities instance being returned. If $pm_tablename_or_num is not referenced by the relationship 
	 * null is returned.
	 *
	 * @param mixed $pm_tablename_or_num Table name of number
	 * @return BaseModel
	 */
	public function getInstanceOpposite($pm_tablename_or_num) {
		if ($t_one_side = Datamodel::getInstanceByTableName($pm_tablename_or_num, true)) {
			if ($this->getLeftTableName() == $t_one_side->tableName()) {
				// other side is right
				return $this->getRightTableInstance();
			} elseif ($this->getRightTableName() == $t_one_side->tableName()) {
				// other side is left
				return $this->getLeftTableInstance();
			}
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Get name for related table opposite the one specified by $pm_tablename_or_num.
	 * For example, if the relationship is ca_objects_x_entities, passing $pm_tablename_or_num = ca_objects will
	 * result in "ca_entities" being returned. If $pm_tablename_or_num is not referenced by the relationship 
	 * null is returned.
	 *
	 * @param mixed $pm_tablename_or_num Table name of number
	 * @return string
	 */
	public function getOppositeTableName($pm_tablename_or_num) {
		if ($t_one_side = Datamodel::getInstanceByTableName($pm_tablename_or_num, true)) {
			if ($this->getLeftTableName() == $t_one_side->tableName()) {
				// other side is right
				return $this->getRightTableName();
			} elseif ($this->getRightTableName() == $t_one_side->tableName()) {
				// other side is left
				return $this->getLeftTableName();
			}
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns relationship type name for the currently loaded row. Directionality of the type name can be controlled using the $ps_direction parameter.
	 *
	 * @param string $ps_direction Determines the reading direction of the relationship. Possible values are 'ltor' (left-to-right) and 'rtol' (right-to-left). Default value is ltor.
	 * @return string Type name or null if no row is loaded.
	 */
	public function getRelationshipTypename($ps_direction='ltor', $pn_type_id=null) {
		if (($vn_type_id = $pn_type_id) || ($vn_type_id = $this->getTypeID())) {
			$t_rel_type = new ca_relationship_types($vn_type_id);
			return ($ps_direction == 'ltor') ? $t_rel_type->get('ca_relationship_types.preferred_labels.typename') : $t_rel_type->get('ca_relationship_types.preferred_labels.typename_reverse');
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Returns relationship type code for the currently loaded row.
	 *
	 * @return string The relationship type code
	 */
	public function getRelationshipTypeCode() {
		if ($vn_type_id = $this->getTypeID()) {
			$t_rel_type = new ca_relationship_types($vn_type_id);
			return $t_rel_type->get('ca_relationship_types.type_code');
		}
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Get row_ids linked to by this self-relationship. By default this method will return
	 * row_ids for linked records on either side of the relationship for the currently loaded relationship.
	 * You can omit specific row_ids from the list of returned ids by including them in the $pa_primary_ids parameters array.
	 * You can obtain linked row_ids for multiple relationships, not necessarily including the currently loaded relationship,
	 * by passing a list of relation_ids in the optional $pa_relationship_ids parameter.
	 *
	 * This method only returns values for self-relationships (Ex. ca_objects_x_objects, ca_entities_x_entities). It will return null
	 * when called for non-self-relationships. The returned values are primary keys (row_ids) for the related record - Eg. ca_objects object_id's
	 * for ca_objects_x_objects, ca_entities entity_id's for ca_entities_x_entities.
	 *
	 * @param array $pa_primary_ids List of row_ids to not include in the returned list. [Default is to include all row_ids]
	 * @param array $pa_relationship_ids List of relationship relation_ids to return related row_ids for. [Default is to return row_ids for the currently loaded relationship only.]
	 *
	 * @return array An array of row_ids. If called on a non-self-relationship or with no loaded relationship and and empty $pa_relationship_ids parameter, will return null.
	 */
	public function getRelatedIDsForSelfRelationship($pa_primary_ids=null, $pa_relationship_ids=null) {
		if (!$this->isSelfRelationship()) { return null; }
		
		if (!is_array($pa_primary_ids)) { $pa_primary_ids = []; }
		
		if (!is_array($pa_relationship_ids) || !sizeof($pa_relationship_ids)) { 
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			$pa_relationship_ids = array($vn_id);
		}
		
		$o_db = $this->getDb();
		
		$qr_res = $o_db->query("
			SELECT * FROM ".$this->tableName()." WHERE relation_id IN (?)
		", array($pa_relationship_ids));
		
		$va_ids = [];
		$vs_left_fld = $this->getLeftTableFieldName();
		$vs_right_fld = $this->getRightTableFieldName();
		while($qr_res->nextRow()) {
			if (!in_array($vn_id = $qr_res->get($vs_left_fld), $pa_primary_ids)) { $va_ids[$vn_id] = 1; }
			if (!in_array($vn_id = $qr_res->get($vs_right_fld), $pa_primary_ids)) { $va_ids[$vn_id] = 1; }
		}
	
		return array_keys($va_ids);
	}
	# ------------------------------------------------------
	/**
	 * Updates the ranks for a list of given relation_ids. list/array keys will be the actual rank values.
	 *
	 * @param array $pa_ids
	 * @return bool
	 */
	public function updateRanksForList($pa_ids) {
		if(!is_array($pa_ids)) { return false; }

		foreach($pa_ids as $i => $vn_id) {
			$this->getDb()->query("UPDATE ".$this->tableName() . " SET `rank` = ? WHERE relation_id = ?", $i, $vn_id);
		}
		
		return true;
	}
	# ------------------------------------------------------
	public function getAdditionalChecksumComponents() {
		if(!$this->getPrimaryKey()) { return []; }
		$t_left_instance = $this->getLeftTableInstance();
		$t_right_instance = $this->getRightTableInstance();

		return [
			$t_left_instance->getGUID(),
			$t_right_instance->getGUID(),
			$this->getRelationshipTypeCode(),
			$this->get('effective_date'),
			$this->get('source_info')
		];
	}
	# ------------------------------------------------------
	/**
	 * Return orientation of relationship relative to ids in "primary" table as defined in the
	 * primary_ids parameter. primary_ids is an array with a key set to the table name containing the
	 * primary ids. The value is a list of ids to evaluate the relationship relative to. 
	 * The string 'LTOR' is returned for left-to-right relationships (primary id on left of relationship)
	 * and 'RTOL' for right-to-left relationships. Null is returned is the currently loaded relationship
	 * does not reference any id in the primary_ids array. 
	 *
	 * This method is usually used to determine relationship orientation for relationships between the same 
	 * table (eg. ca_entities <=> ca_entities) where orientation cannot be derived from the structure of the
	 * relationship alone. 
	 *
	 * @param array $primary_ids
	 *
	 * @return ?string LTOR, RTOL or null
	 */
	public function getOrientationForRelationship(array $primary_ids) : ?string {
		if($this->isSelfRelationship()) {
			$t = $this->getLeftTableName();
			if(is_array($primary_ids[$t])) {
				$left_val = $this->get($this->getLeftTableFieldName());
				$right_val = $this->get($this->getRightTableFieldName());
				foreach($primary_ids[$t] as $id) {
					if($id == $left_val) {
						return 'LTOR';
					} elseif($id == $right_val) {
						return 'RTOL';
					}
				}
			}
		} else {
			$fld = $dir = $t = null;
			if(is_array($primary_ids[$t = $this->getLeftTableName()])) {
				$dir = 'LTOR';
				$fld = $this->getLeftTableFieldName();
			} elseif(is_array($primary_ids[$t = $this->getRightTableName()])) {
				$dir = 'RTOL';
				$fld = $this->getRightTableFieldName();
			} else {
				$t = null;
			}
			if($t) {
				foreach($primary_ids[$t] as $id) {
					if($id == $this->get($fld)) {
						return $dir;
					} 
				}
			}
		}
		
		return null;
	}
	# ------------------------------------------------------
	/**
	 * Return orientation of relationship between two tables. Tables can be two primary tables (eg. ca_objects and ca_entities)
	 * or a primary table and a relationship table (eg. ca_objects and ca_objects_x_entities). This method will only return correct orientation
	 * for relationships between different tables where orientation can be derived from the structure of the relationship alone. For "self"
	 * relationships (relationships between two records in the same table) the id of the record from whence the related value is being pulled
	 * (the "primary id") must also be defined. Use getOrientationForRelationship() for these cases.
	 *
	 * @param string $table1
	 * @param string $table2
	 *
	 * @return string LTOR, RTOL or null if orientation cannot be determined.
	 */
	public static function getRelationshipOrientationForTables(string $table1, string $table2) : ?string {
		if($table1 === $table2) { return null; }
		$t_instance1 = Datamodel::getInstance($table1, true);
		$t_instance2 = Datamodel::getInstance($table2, true);
		
		if(is_a($t_instance1, 'BundlableLabelableBaseModelWithAttributes') && is_a($t_instance2, 'BaseRelationshipModel')) {
			if($t_instance2->getLeftTableName() === $table1) {
				return 'LTOR';
			} else {
				return 'RTOL';
			}
		} elseif(is_a($t_instance2, 'BundlableLabelableBaseModelWithAttributes') && is_a($t_instance1, 'BaseRelationshipModel')) {
			if($t_instance1->getLeftTableName() === $table2) {
				return 'LTOR';
			} else {
				return 'RTOL';
			}
		} elseif(is_a($t_instance1, 'BundlableLabelableBaseModelWithAttributes') && is_a($t_instance2, 'BundlableLabelableBaseModelWithAttributes') && is_array($path = Datamodel::getPath($table1, $table2))) {
			$path = array_keys($path);
			switch(sizeof($path)) {
				case 3:
					$rel = Datamodel::getInstance($path[1], true);
					if($rel->getLeftTableName() === $path[0]) {
						return 'LTOR';
					} else {
						return 'RTOL';
					}
					break;
				default:
					return null;
			
			}
		}
		return null;
	}
	# ------------------------------------------------------
}
