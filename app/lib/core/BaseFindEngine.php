<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseFindController.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
  *
  */
 	require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
 	require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');
 	
	class BaseFindEngine extends BaseObject {
		# ------------------------------------------------------------------
		private $ops_tmp_file_path;
		private $ops_tmp_table_name;
		# ------------------------------------------------------------------
		/**
		 * Quickly loads list of row_ids in $pa_hits into a temporary database table uniquely identified by $ps_key
		 * Only one temporary table can exist at a time for a given instance. If you call loadListIntoTemporaryResultTable() 
		 * on an instance for which a temporary table has already been loaded the previously created table will be discarded
		 * before the new one is created.
		 *
		 * @param array $pa_hits Array of row_id values
		 * @param string $ps_key Unique alphanumeric identifier for the temporary table. Should only contain letters, numbers and underscores.
		 * @return string The name of the temporary table created
		 */
		public function loadListIntoTemporaryResultTable($pa_hits, $ps_key) {
			global $g_mysql_has_file_priv;
			
			$ps_key = preg_replace('![^A-Za-z0-9_]+!', '_', $ps_key);
			if ($this->ops_tmp_table_name == "caResultTmp{$ps_key}") {
				return $this->ops_tmp_table_name;
			}
			
			if ($this->ops_tmp_file_path) {
				$this->cleanupTemporaryResultTable();
			}
			$this->ops_tmp_file_path = tempnam(caGetTempDirPath(), 'caResultTmp');
			$this->ops_tmp_table_name = "caResultTmp{$ps_key}";
			$this->opo_db->query("
				CREATE TEMPORARY TABLE {$this->ops_tmp_table_name} (
					row_id int unsigned not null,
					key (row_id)
				) engine=memory;
			");
			if (!sizeof($pa_hits)) { return $this->ops_tmp_table_name; }
			
			if (is_null($g_mysql_has_file_priv)) {	// Figure out if user has FILE priv
				$qr_grants = $this->opo_db->query("
					SHOW GRANTS;
				");
				$g_mysql_has_file_priv = false;
				while($qr_grants->nextRow()) {
					$va_grants = array_values($qr_grants->getRow());
					$vs_grant = array_shift($va_grants);
					if (preg_match('!^GRANT FILE!', $vs_grant)) {
						$g_mysql_has_file_priv = true;
						break;
					}
				}
			}
			
			if ($g_mysql_has_file_priv === true) {
				// Benchmarking has show that using "LOAD DATA INFILE" with an on-disk tmp file performs best
				// The downside is that it requires the MySQL global FILE priv, which often is not granted, especially in shared environments
				file_put_contents($this->ops_tmp_file_path, join("\n", $pa_hits));
				chmod($this->ops_tmp_file_path, 0755);
				
				$this->opo_db->query("LOAD DATA INFILE '{$this->ops_tmp_file_path}' INTO TABLE {$this->ops_tmp_table_name} (row_id)");
			} else {
				// Fallback when database login does not have FILE priv
				$vs_sql = "INSERT IGNORE INTO {$this->ops_tmp_table_name} (row_id) VALUES ";
				foreach($pa_hits as $vn_row_id) {
					$vs_sql .= "(".(int)$vn_row_id."),";
				}
				$this->opo_db->query(substr($vs_sql, 0, strlen($vs_sql)-1));
			}
			return $this->ops_tmp_table_name;
		}
		# ------------------------------------------------------------------
		/**
		 * Remove the current temporary table and cleans up any temporary files on disk
		 *
		 * @return boolean Always return true
		 */
		public function cleanupTemporaryResultTable() {
			if ($this->ops_tmp_table_name) { $this->opo_db->query("DROP TABLE {$this->ops_tmp_table_name}"); }
			if ($this->ops_tmp_file_path) { @unlink($this->ops_tmp_file_path); }
			$this->ops_tmp_file_path = null;
			$this->ops_tmp_table_name = null;
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Filter list of hits by ACL
		 * @param array $pa_hits
		 * @param int $pn_table_num
		 * @param int $pn_user_id
		 * @param int $pn_access
		 * @return array
		 */
		public function filterHitsByACL($pa_hits, $pn_table_num, $pn_user_id, $pn_access=__CA_ACL_READONLY_ACCESS__) {
			if (!sizeof($pa_hits)) { return $pa_hits; }
			if (!(int)$pn_user_id) { $pn_user_id = 0; }
			$o_dm = Datamodel::load();
			$o_conf = Configuration::load();
			if (!($t_table = $o_dm->getInstanceByTableNum($pn_table_num, true))) { return $pa_hits; }

			$t_user = new ca_users($pn_user_id);
			if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
				$va_group_ids = array_keys($va_groups);
				$vs_group_sql = 'OR (ca_acl.group_id IN ('.join(',',$va_group_ids).'))';
			} else {
				$vs_group_sql = '';
			}

			$vs_search_tmp_table = $this->loadListIntoTemporaryResultTable($pa_hits, md5(rand(1,100000)));

			// first get all items where user has an exception that grants him access.
			// those trump everything and are definitely part of the result set
			$qr_items = $this->opo_db->query($vs_sql = "
				SELECT row_id
				FROM ca_acl
				WHERE
					row_id IN (SELECT * FROM {$vs_search_tmp_table})
					AND table_num = ? AND access >= ?
					AND ((ca_acl.user_id = ?) {$vs_group_sql})
			", (int)$pn_table_num, (int)$pn_access, (int)$pn_user_id);
			$va_hits = $qr_items->getAllFieldValues('row_id');

			// then get all items that have sufficient global access on item-level,
			// minus those with an exception that prevents the current user from accessing
			$qr_items = $this->opo_db->query("
				SELECT row_id
				FROM ca_acl
				WHERE
					row_id IN (SELECT row_id FROM {$vs_search_tmp_table})
					AND table_num = ? AND user_id IS NULL AND group_id IS NULL AND access >= ?
					AND row_id NOT IN (
						SELECT row_id FROM ca_acl
						WHERE
							row_id IN (?)
							AND table_num = ? AND access < ?
							AND (user_id = ? {$vs_group_sql})
					)
			", (int)$pn_table_num, (int)$pn_access, $pa_hits, (int)$pn_table_num, (int)$pn_access, (int)$pn_user_id);
			$va_hits = array_merge($va_hits, $qr_items->getAllFieldValues('row_id'));

			// If requested access is less restrictive than default access,
			// add items with no ACL that don't have an exception for this user and his groups
			if ($pn_access <= $o_conf->get('default_item_access_level')) {
				// Find records with default ACL for this user/group
				$qr_sort = $this->opo_db->query("
					SELECT {$vs_search_tmp_table}.row_id
					FROM {$vs_search_tmp_table}
					LEFT JOIN (SELECT * FROM ca_acl WHERE ((ca_acl.user_id = ?) {$vs_group_sql}) OR (ca_acl.user_id IS NULL)) AS ca_acl ON {$vs_search_tmp_table}.row_id = ca_acl.row_id AND ca_acl.table_num = ?
					WHERE
						ca_acl.row_id IS NULL
				", array($pn_user_id, (int)$pn_table_num));

				$va_hits = array_merge($va_hits, $qr_sort->getAllFieldValues('row_id'));
			}

			$this->cleanupTemporaryResultTable();

			return array_unique($va_hits);
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function sortHits(&$pa_hits, $ps_table, $ps_field, $ps_key, $ps_direction='asc', $pa_options=null) {
			//$t= new Timer();
			$vs_browse_tmp_table = $this->loadListIntoTemporaryResultTable($pa_hits, $ps_key);
			
			if (!in_array(strtolower($ps_direction), array('asc', 'desc'))) { $ps_direction = 'asc'; }
			if (!is_array($pa_hits) || !sizeof($pa_hits)) { return $pa_hits; }
				
			$t_table = $this->opo_datamodel->getInstanceByTableName($ps_table, true);
			$vs_table_pk = $t_table->primaryKey();
			$vn_table_num = $t_table->tableNum();
			
			$va_sort_tmp = explode('/', $ps_field);
			$ps_field = $va_sort_tmp[0];
			$vs_rel_type = (sizeof($va_sort_tmp) > 1) ? $va_sort_tmp[1] : null;
			
			$va_fields = explode(';', $ps_field);
			$va_sorted_hits = array();
			
			$va_joins = array();
			$vs_is_preferred_sql = '';
			
			$vs_primary_sort_field = array_shift($va_fields);
			$va_primary_sort_field = explode('.', $vs_primary_sort_field);
			
			$va_additional_sort_fields = array();
			foreach($va_fields as $vs_additional_sort_field) {
				$va_tmp = explode('.', $vs_additional_sort_field);
				if ($va_tmp[0] == $va_primary_sort_field[0]) {
					$va_additional_sort_fields[] = $va_tmp;
				}
			}
			
			if ($va_primary_sort_field[0] == $ps_table) {
				//
				// sort field is in search table
				//
				if (!$t_table->hasField($va_primary_sort_field[1])) { 
					//
					// is it an attribute?
					//
					$t_element = new ca_metadata_elements();
					$vs_sort_element_code = array_pop($va_primary_sort_field);
					if ($t_element->load(array('element_code' => $vs_sort_element_code))) {
						$vn_element_id = $t_element->getPrimaryKey();
						if (!($vs_sortable_value_fld = Attribute::getSortFieldForDatatype($t_element->get('datatype')))) {
							return $pa_hits;
						}
						
						if ((int)$t_element->get('datatype') == __CA_ATTRIBUTE_VALUE_LIST__) {
							$vs_sortable_value_fld = 'lil.name_plural';
							
							$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));
				
							$vs_sql = "
								SELECT attr.row_id, lil.locale_id, lower({$vs_sortable_value_fld}) {$vs_sort_field}
								FROM ca_attributes attr
								INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
								INNER JOIN ca_list_item_labels AS lil ON lil.item_id = attr_vals.item_id
								INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = attr.row_id
								WHERE
									(attr_vals.element_id = ?) AND (attr.table_num = ?) AND (lil.{$vs_sort_field} IS NOT NULL)
								ORDER BY lil.{$vs_sort_field} {$ps_direction}
							";
						} elseif ((int)$t_element->get('datatype') == __CA_ATTRIBUTE_VALUE_DATERANGE__) {
							$vs_sortable_value_fld = 'attr_vals.'.$vs_sortable_value_fld;
							$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));
							
							$vs_sql = "
								SELECT attr.row_id, attr.locale_id, {$vs_sortable_value_fld}
								FROM ca_attributes attr
								INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
								INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = attr.row_id
								WHERE
									(attr_vals.element_id = ?) AND (attr.table_num = ?) AND (attr_vals.{$vs_sort_field} IS NOT NULL)
								ORDER BY attr_vals.{$vs_sort_field} {$ps_direction}, attr.row_id
							";
						} else {
							$vs_sortable_value_fld = 'attr_vals.'.$vs_sortable_value_fld;
							$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));
							
							$vs_sql = "
								SELECT attr.row_id, attr.locale_id, lower({$vs_sortable_value_fld}) {$vs_sort_field}
								FROM ca_attributes attr
								INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
								INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = attr.row_id
								WHERE
									(attr_vals.element_id = ?) AND (attr.table_num = ?) AND (attr_vals.{$vs_sort_field} IS NOT NULL)
								ORDER BY attr_vals.{$vs_sort_field} {$ps_direction}, attr.row_id
							";
						}
						$qr_sort = $this->opo_db->query($vs_sql, (int)$vn_element_id, (int)$vn_table_num);
						$va_sorted_hits = array_unique($qr_sort->getAllFieldValues('row_id'));
			
						// Add on hits that aren't sorted because they don't have an attribute associated
						$va_missing_items = array_diff($pa_hits, $va_sorted_hits);
						$va_sorted_hits = array_merge($va_sorted_hits, $va_missing_items);
						return $va_sorted_hits;
					}
					// fallback for invalid field specs => don't return empty result sets
					return $pa_hits;
				} else {	
					$va_field_info = $t_table->getFieldInfo($va_primary_sort_field[1]);
					if ($va_field_info['START'] && $va_field_info['END']) {
						$vs_sortable_value_fld = $vs_primary_sort_field;
						$va_additional_sort_fields[] = $va_field_info['END'];
					} else {
						$vs_sortable_value_fld = $vs_primary_sort_field;
					}
				}
			} else {
				// sort field is in related table 
				$va_path = $this->opo_datamodel->getPath($ps_table, $va_primary_sort_field[0]);
				
				if (sizeof($va_path) > 2) {
					// many-many
					$vs_last_table = null;
					// generate related joins
					foreach($va_path as $vs_table => $va_info) {
						$t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
						
						$vs_rel_type_sql = null;
						if($t_table->isRelationship() && $vs_rel_type) {
							if(is_array($va_rel_types = caMakeRelationshipTypeIDList($vs_table, array($vs_rel_type))) && sizeof($va_rel_types)) {
								$vs_rel_type_sql = " AND {$vs_table}.type_id IN (".join(",", $va_rel_types).")";
							}
						}
						if ($vs_last_table) {
							$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_last_table, $vs_table);
							if (!sizeof($va_rels)) {
								$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_table, $vs_last_table);
							}
							if ($vs_table == $va_rels['one_table']) {
								$va_joins[$vs_table] = "INNER JOIN ".$va_rels['one_table']." ON ".$va_rels['one_table'].".".$va_rels['one_table_field']." = ".$va_rels['many_table'].".".$va_rels['many_table_field'].$vs_rel_type_sql;
							} else {
								$va_joins[$vs_table] = "INNER JOIN ".$va_rels['many_table']." ON ".$va_rels['many_table'].".".$va_rels['many_table_field']." = ".$va_rels['one_table'].".".$va_rels['one_table_field'].$vs_rel_type_sql;
							}
						}
						$vs_last_table = $vs_table;
					}
					
					$vs_sortable_value_fld = $vs_primary_sort_field;
				} else {
					$va_rels = $this->opo_datamodel->getRelationships($ps_table, $va_primary_sort_field[0]);
					if (!$va_rels) { return $pa_hits; }							// return hits unsorted if field is not valid
					$t_rel = $this->opo_datamodel->getInstanceByTableName($va_primary_sort_field[0], true);
					if (!$t_rel->hasField($va_primary_sort_field[1])) { return $pa_hits; }
					$va_joins[$va_primary_sort_field[0]] = 'LEFT JOIN '.$va_primary_sort_field[0].' ON '.$ps_table.'.'.$va_rels[$ps_table][$va_primary_sort_field[0]][0][0].' = '.$va_primary_sort_field[0].'.'.$va_rels[$ps_table][$va_primary_sort_field[0]][0][1]."\n";
					
					// if the related supports preferred values (eg. *_labels tables) then only consider those in the sort
					if ($t_rel->hasField('is_preferred')) {
						$vs_is_preferred_sql = " ".$va_primary_sort_field[0].".is_preferred = 1";
					}
					
					$vs_sortable_value_fld = $vs_primary_sort_field;
				}
			}	
				
			//
			// Grab values and index for sorting later
			//
			//Debug::msg("sort pre query ".$t->getTime(4));
			$va_primary_sort_field = explode('.', $vs_sortable_value_fld);
			$vs_join_sql = join("\n", $va_joins);
			
			$va_sort_fields = array("{$vs_sortable_value_fld} {$ps_direction}");
			foreach($va_additional_sort_fields as $va_additional_sort_field) {
				$va_sort_fields[] = "{$va_additional_sort_field[0]}.{$va_additional_sort_field[1]} {$ps_direction}";
			}
			
			$vs_sql = "
				SELECT {$ps_table}.{$vs_table_pk}
				FROM {$ps_table}
				{$vs_join_sql}
				INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = {$ps_table}.{$vs_table_pk}
				".($vs_is_preferred_sql ? 'WHERE' : '')."
					{$vs_is_preferred_sql}
				ORDER BY ".join(',', $va_sort_fields).", {$ps_table}.{$vs_table_pk}
			";
			
			$qr_sort = $this->opo_db->query($vs_sql);
			
			$va_sorted_hits = array_merge(array_unique($qr_sort->getAllFieldValues($vs_table_pk)));
			
			return $va_sorted_hits;
		}
		# ------------------------------------------------------------------
		/**
		 * Discards any existing temporary table on deallocation.
		 */
		public function __destruct() {
			if ($this->ops_tmp_table_name) {
				$this->cleanupTemporaryResultTable();
			}
		}
		# ------------------------------------------------------------------
	}	