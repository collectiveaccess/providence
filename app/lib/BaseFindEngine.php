<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseFindEngine.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2020 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
  *
  */
 	require_once(__CA_LIB_DIR__.'/BaseObject.php');
 	require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');
 	
	class BaseFindEngine extends BaseObject {
		# ------------------------------------------------------------------
		/**
		 *
		 */
		private $ops_tmp_file_path;
		
		/**
		 *
		 */
		private $ops_tmp_table_name;
		# ------------------------------------------------
		protected $opo_datamodel;
		protected $opo_db;
		
		# ------------------------------------------------
		/**
		 * @param Db $po_db A database client object to use rather than creating a new connection. [Default is to create a new database connection]
		 */
		public function __construct($po_db=null) {			
	
			$this->opo_db = $po_db ? $po_db : new Db();
		}
		# ------------------------------------------------------------------
		/**
		 * Quickly loads list of row_ids in $pa_hits into a temporary database table uniquely identified by $ps_key
		 * Only one temporary table can exist at a time for a given instance. If you call loadListIntoTemporaryResultTable() 
		 * on an instance for which a temporary table has already been loaded the previously created table will be discarded
		 * before the new one is created.
		 *
		 * @param array $pa_hits Array of row_id values
		 * @param string $ps_key Unique alphanumeric identifier for the temporary table. Should only contain letters, numbers and underscores.
		 * @param array $pa_options
		 * @return string The name of the temporary table created
		 */
		public function loadListIntoTemporaryResultTable($pa_hits, $ps_key, $pa_options=null) {
			global $g_mysql_has_file_priv;
			
			$ps_key = preg_replace('![^A-Za-z0-9_]+!', '_', $ps_key);
			if ($this->ops_tmp_table_name == "caResultTmp{$ps_key}") {
				return $this->ops_tmp_table_name;
			}
			
			if ($this->ops_tmp_table_name) {
				$this->cleanupTemporaryResultTable();
			}
			$this->ops_tmp_file_path = tempnam(caGetTempDirPath(), 'caResultTmp');
			$this->ops_tmp_table_name = "caResultTmp{$ps_key}";
			
			
			if (is_array($va_sortable_values = caGetOption('sortableValues', $pa_options, false))) {
				$this->opo_db->query("
					CREATE TEMPORARY TABLE {$this->ops_tmp_table_name} (
						row_id int unsigned not null,
						idx int unsigned not null,
						sort_key1 varchar(255) not null default '',
						sort_key2 varchar(255) not null default '',
						sort_key3 varchar(255) not null default '',
						key (row_id)
					) engine=memory;
				");
			} else {
				$this->opo_db->query("
					CREATE TEMPORARY TABLE {$this->ops_tmp_table_name} (
						row_id int unsigned not null,
						key (row_id)
					) engine=memory;
				");
			}
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
		
			if ((($g_mysql_has_file_priv === true) && (sizeof($pa_hits) > 500))) {
				// Benchmarking has shown that using "LOAD DATA INFILE" with an on-disk tmp file performs best with large result sets
				// The downside is that it requires the MySQL global FILE priv, which often is not granted, especially in shared environments
				$vs_data = '';
				if (is_array($va_sortable_values)) {
					foreach($pa_hits as $vn_hit) {
						if(!($vs_sort_key_1 = $va_sortable_values[0][$vn_hit])) { $vs_sort_key_1 = ''; }
						if(!($vs_sort_key_2 = $va_sortable_values[1][$vn_hit])) { $vs_sort_key_2 = ''; }
						if(!($vs_sort_key_3 = $va_sortable_values[2][$vn_hit])) { $vs_sort_key_3 = ''; }
						if (is_numeric($vs_sort_key_1)) { $vs_sort_key_1 = str_pad($vs_sort_key_1, 12, '0', STR_PAD_LEFT); }
						if (is_numeric($vs_sort_key_2)) { $vs_sort_key_2 = str_pad($vs_sort_key_2, 12, '0', STR_PAD_LEFT); }
						if (is_numeric($vs_sort_key_3)) { $vs_sort_key_3 = str_pad($vs_sort_key_3, 12, '0', STR_PAD_LEFT); }
						$vs_data .= $vn_hit.','.$vs_sort_key_1.','.$vs_sort_key_2.','.$vs_sort_key_3."\n";
					}
				} else {
					$vs_data = join("\n", $pa_hits);
				}
				file_put_contents($this->ops_tmp_file_path, $vs_data);
				chmod($this->ops_tmp_file_path, 0755);
				
				if (is_array($va_sortable_values)) {
					$vs_sql = "LOAD DATA INFILE '{$this->ops_tmp_file_path}' INTO TABLE {$this->ops_tmp_table_name} FIELDS TERMINATED BY ',' (row_id, sort_key1, sort_key2, sort_key3)";
					$this->opo_db->query($vs_sql);
				} else {
					$this->opo_db->query("LOAD DATA INFILE '{$this->ops_tmp_file_path}' INTO TABLE {$this->ops_tmp_table_name} (row_id)");
				}
			} else {
				// Fallback when database login does not have FILE priv
				
				if (is_array($va_sortable_values)) {
					$vs_sql = "INSERT IGNORE INTO {$this->ops_tmp_table_name} (row_id, idx, sort_key1, sort_key2, sort_key3) VALUES ";
					foreach($pa_hits as $vn_index => $vn_hit) {
						if(!($vs_sort_key_1 = $va_sortable_values[0][$vn_hit])) { $vs_sort_key_1 = ''; } else { $vs_sort_key_1 = preg_replace("/[^[:alnum:][:space:]]/ui", '', $vs_sort_key_1); }
						if(!($vs_sort_key_2 = $va_sortable_values[1][$vn_hit])) { $vs_sort_key_2 = ''; } else { $vs_sort_key_2 = preg_replace("/[^[:alnum:][:space:]]/ui", '', $vs_sort_key_2); }
						if(!($vs_sort_key_3 = $va_sortable_values[2][$vn_hit])) { $vs_sort_key_3 = ''; } else { $vs_sort_key_3 = preg_replace("/[^[:alnum:][:space:]]/ui", '', $vs_sort_key_3); }
						if (is_numeric($vs_sort_key_1)) { $vs_sort_key_1 = str_pad($vs_sort_key_1, 12, '0', STR_PAD_LEFT); }
						if (is_numeric($vs_sort_key_2)) { $vs_sort_key_2 = str_pad($vs_sort_key_2, 12, '0', STR_PAD_LEFT); }
						if (is_numeric($vs_sort_key_3)) { $vs_sort_key_3 = str_pad($vs_sort_key_3, 12, '0', STR_PAD_LEFT); }
						
						$vs_sql .= "(".(int)$vn_hit.",".(int)$vn_index.",'".$vs_sort_key_1."','".$vs_sort_key_2."','".$vs_sort_key_3."'),";
					}
				} else {
					$vs_sql = "INSERT IGNORE INTO {$this->ops_tmp_table_name} (row_id) VALUES ";
					foreach($pa_hits as $vn_hit) {
						$vs_sql .= "(".(int)$vn_hit."),";
					}
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
			if ($this->ops_tmp_table_name) {
				if($this->opo_db->connected()) {
					$this->opo_db->query("DROP TABLE {$this->ops_tmp_table_name}");
				}
			}
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
			$o_conf = Configuration::load();
			if (!($t_table = Datamodel::getInstanceByTableNum($pn_table_num, true))) { return $pa_hits; }

			$t_user = new ca_users($pn_user_id);
			if ($t_user->canDoAction('is_administrator')) { return $pa_hits; }
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

			return array_values(array_unique($va_hits));
		}
		# ------------------------------------------------------------------
		/**
		 * Sort results hits.
		 *
		 * @param array $hits Array of row_ids to be sorted
		 * @param string $table The table for the results being sorted
		 * @param string $field_list An array or semicolon-delimited string of fully qualified bundle names (Eg. ca_objects.idno;ca_objects.due_date)
		 * @param string $directions An array or semicolon-delimited string of sort directions corresponding to sort criteria specified in $field_list. Values for direction may be either 'asc' (ascending order) or 'desc' (descending order). If not specified 'asc' is assumed.
		 * @param array $options No options are currently defined.
		 *
		 * @return array
		 */
		public function sortHits(&$hits, $table, $sort_list, $sort_directions='asc', $options=null) {
			if (!$t_table = Datamodel::getInstanceByTableName($table, true)) { return null; } // invalid table
			if (!is_array($hits) || !sizeof($hits)) { return $hits; } // Don't try to sort empty results
			
			$table_pk = $t_table->primaryKey();
			$table_num = $t_table->tableNum();
			
			// Expand field list into array
			$sort_fields = is_array($sort_list) ? $sort_list : explode(';', $sort_list); 
			$embedded_sort_directions = array_map(function($v) { $t = explode(':', $v); return (sizeof($t) == 2) ? strtolower(trim(array_pop($t))) : null; }, $sort_fields);
		
			$sort_directions = is_array($sort_directions) ? $sort_directions : explode(';', $sort_directions); 
			if(sizeof($sort_directions) < sizeof($sort_fields)) {
				$sort_directions = array_pad($sort_directions, sizeof($sort_fields), $sort_directions[0]);
			}
			foreach($embedded_sort_directions as $i => $d) {
				if(in_array($d, ['asc', 'desc'])) { 
					$sort_fields[$i] = array_shift(explode(':', $sort_fields[$i]));
					$sort_directions[$i] = $d; 
				}
			}
			if (sizeof($sort_directions) !== sizeof($sort_fields)) {
				$sort_directions = array_pad($sort_directions, sizeof($sort_fields), "asc");
			}
			
			$sort_directions = array_map(function($v) { return strtolower($v); }, $sort_directions);
			
			$sorted_hits = $sort_key_values = [];
			$qr_sort = $set_id = null;
			
			foreach($sort_fields as $i => $sort_field) {
				$sort_direction = $sort_directions[$i];
				$sort_tmp = preg_split('![/\|]+!', $sort_field);		// isolate relationship type (and/or item type) if present
				$sort_rel_type = (sizeof($sort_tmp) > 1) ? $sort_tmp[1] : null;	
				$sort_item_type = (sizeof($sort_tmp) > 2) ? $sort_tmp[2] : null;	
				
				$sort_field = $sort_tmp[0];	// strip relationship type (and/or item type)
				
				list($sort_table, $sort_field, $sort_subfield) = explode(".", $sort_field);
				if (!($t_bundle = Datamodel::getInstanceByTableName($sort_table, true))) { break; }
					
				// Transform preferred_labels bundles into label-table references
				// Eg. ca_objects.preferred_labels.name => ca_object_labels.name
				if ($sort_field == 'preferred_labels') {
					$sort_table = $t_bundle->getLabelTableName();
					$sort_field = $sort_subfield ? $sort_subfield : $t_bundle->getLabelDisplayField();
					$sort_subfield = null;
				}
								
				// Are we sorting on a set? That's handled specially below...
				$set_id = null;
				if (!($set_id = preg_match("!^ca_sets.set_id:([\d]+)$!", $sort_field, $va_matches) ? $va_matches[1] : null)) {	// sort on set_id (Eg. ca_sets.set_id:23)
					if (($sort_table == 'ca_set_items') && ($sort_field == 'rank') && ((int)$sort_rel_type > 0)) { $set_id = (int)$sort_rel_type; }	// sort of ca_set_items.rank with set_id expressed as relationship type (Eg. ca_set_items.rank/23)
				}
			
				if ($set_id) {	// sort by set ranks
					$sort_key_values[] = ca_sets::getRowIDRanksForSet($set_id);
					continue;
				} elseif ($sort_table === $table) {	// sort in primary table
					
					if (!$t_table->hasField($sort_field)) {
						if($sort_keys = $this->_getSortKeysForElement($sort_subfield ? $sort_subfield : $sort_field, $table_num, $hits)) {
							$sort_key_values[] = $sort_keys;
						}
					} else {
						// is intrinsic
						$va_field_info = $t_table->getFieldInfo($sort_field);
						if ($va_field_info['START'] && $va_field_info['END']) {
							$sort_field = $va_field_info['START'];
						}
						
						$sql = "
							SELECT {$table_pk}, `{$sort_field}`
							FROM {$table}
							WHERE
								{$table_pk} IN (?)
						";
						$qr_sort = $this->opo_db->query($sql, array($hits));
						$sort_keys = [];
						while($qr_sort->nextRow()) {
							$row = $qr_sort->getRow();
							$sort_keys[$row[$table_pk]] = $row[$sort_field];
						}
						$sort_key_values[] = $sort_keys;
						continue;
					}
				} elseif($table !== $sort_table) { // sort in related table
					$t_sort_field_table = Datamodel::getInstanceByTableName($sort_table, true);
					$path = Datamodel::getPath($table, $sort_table);
					
					$field_table_pk = $t_sort_field_table->primaryKey();
			
					$is_preferred_sql = null;
					$joins = [];
					
					if (sizeof($path) > 1) {
						// many-many relationship
						$last_table = null;
						
						// generate joins for related tables
						foreach(array_keys($path) as $join_table) {
							//if ($sort_table === $join_table) { continue; }
							$t_join = Datamodel::getInstanceByTableName($join_table, true);
							$join_pk = $t_join->primaryKey();
			                $has_deleted = $t_join->hasField('deleted');
							
							$sort_rel_type_sql = $sort_item_type_sql = null;
							if($t_join->isRelationship() && $sort_rel_type) {
								if(is_array($va_rel_types = caMakeRelationshipTypeIDList($join_table, explode(",", $sort_rel_type))) && sizeof($va_rel_types)) {
									$sort_rel_type_sql = " AND {$join_table}.type_id IN (".join(",", $va_rel_types).")";
								}
							} elseif (method_exists($t_join, "getTypeFieldName") && ($vs_type_fld_name = $t_join->getTypeFieldName())) {
							    if(($join_table !== $table) && is_array($va_item_types = caMakeTypeIDList($join_table, explode(",", $sort_item_type))) && sizeof($va_item_types)) {
									$sort_item_type_sql = " AND {$join_table}.{$vs_type_fld_name} IN (".join(",", $va_item_types).")";
								}
							}
							
							$deleted_sql = $has_deleted ? " AND {$join_table}.deleted = 0 " : "";
							
							if ($last_table) {
								$rels = Datamodel::getOneToManyRelations($last_table, $join_table);
								if (!sizeof($rels)) {
									$rels = Datamodel::getOneToManyRelations($join_table, $last_table);
								}
								if ($join_table == $rels['one_table']) {
									$joins[$join_table] = "INNER JOIN ".$rels['one_table']." ON ".$rels['one_table'].".".$rels['one_table_field']." = ".$rels['many_table'].".".$rels['many_table_field'].$sort_rel_type_sql.$sort_item_type_sql.$deleted_sql;
								} else {
									$joins[$join_table] = "INNER JOIN ".$rels['many_table']." ON ".$rels['many_table'].".".$rels['many_table_field']." = ".$rels['one_table'].".".$rels['one_table_field'].$sort_rel_type_sql.$sort_item_type_sql.$deleted_sql;
								}
							}
							if ($join_table !== $sort_table) { $last_table = $join_table; }
						}
						
						$rels = Datamodel::getRelationships($last_table, $sort_table);
						if (!$rels) { break; }		// field is not valid

						if ($t_sort_field_table->hasField($sort_field)) { // sorting on intrinsic in related table
							$joins[$sort_table] = 'INNER JOIN '.$sort_table.' ON '.$last_table.'.'.$rels[$last_table][$sort_table][0][0].' = '.$sort_table.'.'.$rels[$last_table][$sort_table][0][1]."\n";

							// if the related supports preferred values (eg. *_labels tables) then only consider those in the sort
							if ($t_sort_field_table->hasField('is_preferred')) {
								$is_preferred_sql = " {$sort_table}.is_preferred = 1";
							}
						} else {
							// non-intrinsic
							$sort_table_full_pk = $t_sort_field_table->primaryKey(true);
							$join_sql = join("\n", $joins);
							
							// Get list of related sortables against hits
							$sql = "
								SELECT {$sort_table_full_pk}, {$table}.{$table_pk}
								FROM {$table}
								{$join_sql}
								WHERE
									{$is_preferred_sql} ".($is_preferred_sql ? ' AND ' : '')." {$table}.{$table_pk} IN (?)
							";
						
							$qr_sort = $this->opo_db->query($sql, array($hits));
							
							$acc = $rel_ids = [];
							while($qr_sort->nextRow()) {
								$rel_id = $qr_sort->get($sort_table_full_pk);
								$acc[$qr_sort->get("{$table}.{$table_pk}")][$rel_id] = true;	// list of sortables per hit
								$rel_ids[] = $rel_id;	// list of sortables
							}
							if(!sizeof($rel_ids)) { return $hits; }
							if (!($qr_keys = caMakeSearchResult($sort_table, $rel_ids))) { return $hits; }
							
							$keys = [];	// sortable values by sort id
							while($qr_keys->nextHit()) {	// Loop on sortables
								$sort_id = $qr_keys->get($sort_table_full_pk);
								$sort_values = $qr_keys->get("{$sort_table}.{$sort_field}", ['sortable' => true, 'returnAsArray' => true]);
								
								if(!is_array($keys[$sort_id])) { $keys[$sort_id] = []; }
								foreach($sort_values as $i => $sort_value) {
									$keys[$sort_id][$sort_value] = true;
								}
							}
							$sort_keys = [];
							foreach($acc as $id => $rel_ids) {
								if(!is_array($sort_keys[$id])) { $sort_keys[$id] = []; }
								foreach(array_keys($rel_ids) as $rel_id) {
									$sort_keys[$id] = array_filter(array_merge($sort_keys[$id], array_keys($keys[$rel_id])), "strlen");
								}
							}
							
							$sort_keys = array_map(
								function($v) use ($sort_direction) { 
									sort($v); 
									$v = array_map(function($x) { 
										return is_numeric($x) ? str_pad(substr($x, 0, 50), 50,  '0', STR_PAD_LEFT) : str_pad(substr($x, 0, 50), 50,  ' ', STR_PAD_RIGHT); 
									}, $v);
									sort($v);
									if($sort_direction === 'desc') { $v = array_reverse($v); }
									return join("; ", $v); 
								}, $sort_keys);
								
							$sort_key_values[] = $sort_keys;
							continue;
						}
					} else {
						continue;
					}
					
					$vs_join_sql = join("\n", $joins);
					$sql = "
						SELECT {$table}.{$table_pk}, {$sort_table}.`{$sort_field}`
						FROM {$table}
						{$vs_join_sql}
						WHERE
							{$is_preferred_sql} ".($is_preferred_sql ? ' AND ' : '')." {$table}.{$table_pk} IN (?)
					";
					
					$qr_sort = $this->opo_db->query($sql, array($hits));
				}
					
				if($qr_sort) {
					$va_sort_keys = array();
					while($qr_sort->nextRow()) {
						$va_row = $qr_sort->getRow();
						$va_sort_keys[$va_row[$table_pk]] = $va_row[$sort_field];
					}
					$sort_key_values[] = $va_sort_keys;
				}
			}
			
			return $this->_doSort($hits, $sort_key_values, $sort_directions, $options);
		}
		# ------------------------------------------------------------------
		/**
		 * Perform sort hits using sortable values as keys
		 * 
		 * @param array $hits
		 * @param array $sortable_values
		 * @param string $directions
		 * @param array $options
		 *
		 * @return array
		 */
		private function _doSort(&$hits, $sortable_values, $sort_directions, $options=null) {
			$sorted_rows = [];
			$return_index = caGetOption('returnIndex', $options, false);
			
			if(!is_array($sort_directions)) {
				$sort_directions = array_pad($sort_directions, sizeof($sortable_values), (strtolower($sort_directions) == 'desc') ? 'desc' : 'asc');
			}
			
			$o_conf = caGetSearchConfig();
			if (!($max_hits_for_in_memory_sort = $o_conf->get('max_hits_for_in_memory_sort'))) { $max_hits_for_in_memory_sort = 1000000; }
			if (sizeof($hits) < $max_hits_for_in_memory_sort) {
				$sort_mode = SORT_FLAG_CASE;
				if (!$o_conf->get('dont_use_natural_sort')) { $sort_mode |= SORT_NATURAL; } else { $sort_mode |= SORT_STRING; }
				
				//
				// Perform sort in-memory
				//
				$sort_buffer = [];
				
				foreach($hits as $idx => $hit) {
					
					$keys = [];
					foreach($sortable_values as $i => $sortable_values_level) {
						if(!sizeof($sortable_values_level)) { continue; }
						$v = preg_replace("![^\w_]+!u", " ", caRemoveAccents($sortable_values_level[$hit]));
						$keys[] = str_pad(substr($v, 0, 50), 50, ' ', is_numeric($v) ? STR_PAD_LEFT : STR_PAD_RIGHT);
					}
					$ptr = &$sort_buffer;
					
					foreach($keys as $key) {
						if (!is_array($ptr[$key])) { $ptr[$key] = []; }
						$ptr = &$ptr[$key];
					}
					$ptr[] = $return_index ? $idx . '/' . $hit : $hit;
				}
				
				$sort_buffer = self::_sortKeys($sort_buffer, $sort_mode, $sort_directions, 0);
				
				if($return_index) {
					$return = [];
					foreach($sort_buffer as $val) {
						$tmp = explode('/', $val);
						$return[$tmp[0]] = $tmp[1];
					}
					return $return;
				} else {
					$sort_buffer = array_values($sort_buffer);
				}

				return $sort_buffer;
			} else {
				//
				// Use mysql memory-based table to do sorting
				//
				$sort_tmp_table = $this->loadListIntoTemporaryResultTable($hits, caGenerateGUID(), array('sortableValues' => $sortable_values));
				$vs_sql = "
					SELECT row_id, idx
					FROM {$sort_tmp_table}
					ORDER BY sort_key1 {$sort_directions[0]}, sort_key2 {$sort_directions[1]}, sort_key3 {$sort_directions[2]}, row_id
				";
				$qr_sort = $this->opo_db->query($vs_sql, []);
				$results = $qr_sort->getAllFieldValues(array('row_id', 'idx'));
				$this->cleanupTemporaryResultTable();
				if($return_index) {
					$sorted_rows = [];
					foreach($results['row_id'] as $vn_i => $vm_row_id) {
						$sorted_rows[$results['idx'][$vn_i]] = $vm_row_id;
					}
					return $sorted_rows;
				} else {
					return $results['row_id'];
				}
			}
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		static private function _sortKeys($sort_buffer, $sort_mode, $sort_directions, $level=0) {
			ksort($sort_buffer, $sort_mode);
			if(strtolower($sort_directions[$level]) === 'desc') { $sort_buffer = array_reverse($sort_buffer); }
			
			$ret = [];
			foreach($sort_buffer as $key => $subkeys) {
				if(is_array($subkeys)) {
					$ret = array_merge($ret, self::_sortKeys($subkeys, $sort_mode, $sort_directions, $level + 1));
				} else {
					return $sort_buffer;
				}
			}
			return $ret;
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
		/**
		 * Get sort keys for list of hits from a given table
		 *
		 * @param string $ps_element_code
		 * @param int $pn_table_num
		 * @param array $pa_hits
		 * @return array|bool
		 */
		private function _getSortKeysForElement($ps_element_code, $pn_table_num, $pa_hits) {
			if (!($t_element = ca_metadata_elements::getInstance($ps_element_code))) {
				return false;
			}
			
			$datatype = $t_element->get('datatype');
			if ($datatype == 0) {
			    // is container
			    $elements = $t_element->getElementsInSet();
			    $concatenated_keys = [];
			    foreach($elements as $e) {
			        if ($e['datatype'] == 0) { continue; }
			        $keys = $this->_getSortKeysForElement($e['element_code'], $pn_table_num, $pa_hits);
			        foreach($keys as $id => $val) {
			            $sv = mb_substr($val, 0, 30);
			            $concatenated_keys[$id] = str_pad($sv, 30, " ", is_numeric($sv) ? STR_PAD_LEFT : STR_PAD_RIGHT);
			        }
			    }
			    return $concatenated_keys;
			}

			// is metadata element
			$vn_element_id = $t_element->getPrimaryKey();
			if (!($vs_sortable_value_fld = Attribute::getSortFieldForDatatype($datatype))) {
				return false;
			}

			$vs_sql = null;
			$pad_keys = false;

			switch($vn_datatype = (int)$t_element->get('datatype')) {
				case __CA_ATTRIBUTE_VALUE_LIST__:
				case __CA_ATTRIBUTE_VALUE_OBJECTS__:
				case __CA_ATTRIBUTE_VALUE_ENTITIES__:
				case __CA_ATTRIBUTE_VALUE_PLACES__:
				case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:
				case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:
				case __CA_ATTRIBUTE_VALUE_LOANS__:
				case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:
				case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:
				case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:
					if (!($t_auth_instance = AuthorityAttributeValue::elementTypeToInstance($vn_datatype))) { break; }
					$vs_sortable_value_fld = $t_auth_instance->getLabelSortField();
					$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));
					$vs_sql = "
							SELECT attr.row_id, lower(lil.{$vs_sortable_value_fld}) {$vs_sortable_value_fld}
							FROM ca_attributes attr
							INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
							INNER JOIN ".$t_auth_instance->getLabelTableName()." AS lil ON lil.".$t_auth_instance->primaryKey()." = attr_vals.item_id
							WHERE
								(attr_vals.element_id = ?) AND
								(attr.table_num = ?) AND
								(lil.{$vs_sortable_value_fld} IS NOT NULL) AND
								(attr.row_id IN (?))
						";
					break;
				case __CA_ATTRIBUTE_VALUE_DATERANGE__:
					$vs_sortable_value_fld = 'attr_vals.'.$vs_sortable_value_fld;
					$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));

					$vs_sql = "
							SELECT attr.row_id, {$vs_sortable_value_fld}
							FROM ca_attributes attr
							INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
							WHERE
								(attr_vals.element_id = ?) AND
								(attr.table_num = ?) AND
								(attr_vals.{$vs_sort_field} IS NOT NULL) AND
							(attr.row_id IN (?))
						";
					break;
				case __CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__:
				    if (
				        ($list_code = $t_element->getSetting('sortUsingList'))
				        &&
				        (($list_id = (int)caGetListID($list_code)) > 0)
				    ) {
                        $vs_sortable_value_fld = 'attr_vals.value_longtext2';
                        $vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));
				        
				        $vs_sql = "
							SELECT attr.row_id, LPAD(li.`rank`,9, '0') {$vs_sort_field}
							FROM ca_attributes attr
							INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
							LEFT JOIN ca_list_items AS li ON attr_vals.value_longtext2 = li.idno
							WHERE
								(attr_vals.element_id = ?) AND
								(attr.table_num = ?) AND
								(attr_vals.{$vs_sort_field} IS NOT NULL) AND
								(attr.row_id IN (?)) AND
								(li.list_id = {$list_id})
						";
						
						$pad_keys = true;
				    }
				    break;
				default:
					$vs_sortable_value_fld = 'attr_vals.'.$vs_sortable_value_fld;
					$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));

					$vs_sql = "
							SELECT attr.row_id, lower({$vs_sortable_value_fld}) {$vs_sort_field}
							FROM ca_attributes attr
							INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
							WHERE
								(attr_vals.element_id = ?) AND
								(attr.table_num = ?) AND
								(attr_vals.{$vs_sort_field} IS NOT NULL) AND
								(attr.row_id IN (?))
						";
					break;
			}
			if(!$vs_sql) { return false; }

			$qr_sort = $this->opo_db->query($vs_sql, array((int)$vn_element_id, (int)$pn_table_num, $pa_hits));

			$va_sort_keys = array();
			while($qr_sort->nextRow()) {
				$va_row = $qr_sort->getRow();
				$va_sort_keys[$va_row['row_id']][] = $va_row[$vs_sort_field];
			}
			foreach($pa_hits as $id) {
			    if(!isset($va_sort_keys[$id])) { $va_sort_keys[$id] = [$pad_keys ? '000000000' : '']; }
			}
			foreach($va_sort_keys as $row_id => $keys) {
			    if (sizeof(array_filter($keys, function($v) { return is_numeric($v); })) > 0) {
			        sort($keys, SORT_NUMERIC);
			    } else {
			        sort($keys, SORT_REGULAR);
			    }
			    $va_sort_keys[$row_id] = join(";", $keys);
			}
			return $va_sort_keys;
		}
		# ------------------------------------------------------------------
		private function _mapRowIDsForPathLength2($pn_original_table_num, $pn_target_table, $pa_hits, $pa_options=null) {
			if(!($t_original_table = Datamodel::getInstanceByTableNum($pn_original_table_num, true))) { return false; }
			if(!($t_target_table = Datamodel::getInstanceByTableNum($pn_target_table, true))) { return false; }

			$va_sql_params = [];

			$va_primary_ids = $vs_resolve_links_using = null;
			if($vs_resolve_links_using = caGetOption('resolveLinksUsing', $pa_options)) {
				$va_primary_ids = caGetOption('primaryIDs', $pa_options);
				$va_primary_ids = $va_primary_ids[$vs_resolve_links_using];
			}

			$vs_original_table = $t_original_table->tableName();
			$vs_target_table = $t_target_table->tableName();

			$va_path = Datamodel::getPath($pn_original_table_num, $pn_target_table);
			if(sizeof($va_path) != 2) { return false; }

			// get relationships to build join
			$va_relationships = Datamodel::getRelationships($vs_original_table, $vs_target_table);

			$vs_primary_id_sql = '';
			if(is_array($va_primary_ids) && (sizeof($va_primary_ids) > 0)) {
				// assuming this is being used to sort on interstitials, we just need a WHERE on the keys on the other side of that target table
				$va_tmp = Datamodel::getRelationships($vs_target_table, $vs_resolve_links_using);
				if(isset($va_tmp[$vs_resolve_links_using][$vs_target_table][0][1])) {
					$vs_primary_id_sql = "AND {$vs_target_table}.{$va_tmp[$vs_resolve_links_using][$vs_target_table][0][1]} IN (?)";
					$va_sql_params[] = $va_primary_ids;
				}

			}

			$va_sql = $va_params = [];
			foreach($va_relationships[$vs_original_table][$vs_target_table] as $va_rel) {
					$va_sql[] = "
						SELECT * 
						FROM {$vs_target_table}
						INNER JOIN {$vs_original_table} AS o ON
							o.{$va_rel[0]} = {$vs_target_table}.{$va_rel[1]}
						WHERE 
							o.{$t_original_table->primaryKey()} IN (?)
							{$vs_primary_id_sql}
					";
					array_unshift($va_sql_params, $pa_hits);
			}

			$qr_rel = $this->opo_db->query(join(" UNION ", $va_sql), $va_sql_params);
			$va_return = [];
			while($qr_rel->nextRow()) {
				$va_return['list'][] = $qr_rel->get("{$vs_target_table}.{$t_target_table->primaryKey()}");

				$va_return['reverse'][$qr_rel->get("{$vs_target_table}.{$t_target_table->primaryKey()}")] =
					$qr_rel->get("{$vs_original_table}.{$t_original_table->primaryKey()}");
			}

			return $va_return;
		}
	}	
