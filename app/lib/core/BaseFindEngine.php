<?php
/** ---------------------------------------------------------------------
 * app/lib/core/BaseFindEngine.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
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
			$this->opo_datamodel = Datamodel::load();
	
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
			$o_dm = Datamodel::load();
			$o_conf = Configuration::load();
			if (!($t_table = $o_dm->getInstanceByTableNum($pn_table_num, true))) { return $pa_hits; }

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
		 *
		 * @param array $pa_hits
		 * @param string $ps_table The table being sorted
		 * @param string $pm_field An array or semicolon-delimited string of fully qualified bundle names (Eg. ca_objects.idno;ca_objects.due_date)
		 * @param string $ps_key Key to use for temporary storage
		 * @param string $ps_direction Direction to sort
		 * @param array $pa_options
		 *
		 * @return array
		 */
		public function sortHits(&$pa_hits, $ps_table, $pm_field, $ps_direction='asc', $pa_options=null) {
			if (!$t_table = $this->opo_datamodel->getInstanceByTableName($ps_table, true)) { return null; } // invalid table
			$vs_table_pk = $t_table->primaryKey();
			$vn_table_num = $t_table->tableNum();
			
			// Are we sorting on a set?
			$vn_set_id = (is_string($pm_field) && preg_match("!^ca_sets.set_id:([\d]+)$!", $pm_field, $va_matches)) ? $va_matches[1] : null;
			
			// TODO: allow override of this with field-specific directions 
			// Default direction
			if (!in_array($ps_direction = strtolower($ps_direction), array('asc', 'desc'))) { $ps_direction = 'asc'; }
			
			// Don't try to sort empty results
			if (!is_array($pa_hits) || !sizeof($pa_hits)) { return $pa_hits; }
			
			// Get field list
			//$va_sort_tmp = explode('/', $pm_field);		// strip any relationship type
			//$pm_field = $va_sort_tmp[0];
			//$vs_rel_type = (sizeof($va_sort_tmp) > 1) ? $va_sort_tmp[1] : null;
			$va_bundles = is_array($pm_field) ? $pm_field : explode(';', $pm_field); // $va_sort_tmp[0]);
			$va_sorted_hits = [];
			$qr_sort = null;
			
			$vs_sort_tmp_table = null;
			$va_sort_key_values = array();
			foreach($va_bundles as $vs_bundle) {
				$va_sort_tmp = explode('/', $vs_bundle);		// strip any relationship type
				$vs_rel_type = (sizeof($va_sort_tmp) > 1) ? $va_sort_tmp[1] : null;	
				$vs_bundle = $va_sort_tmp[0];
				
				list($vs_field_table, $vs_field, $vs_subfield) = explode(".", $vs_bundle);
				if (!($t_instance = $this->opo_datamodel->getInstanceByTableName($vs_field_table, true))) { break; }
					
				// Transform preferred_labels
				if ($vs_field == 'preferred_labels') {
					$vs_field_table = $t_instance->getLabelTableName();
					$vs_field = $vs_subfield ? $vs_subfield : $t_instance->getLabelDisplayField();
					$vs_subfield = null;
				}
			
				if ($vn_set_id) {
					// sort by set ranks
					$va_sort_key_values[] = ca_sets::getRowIDRanksForSet($vn_set_id);
					continue;
				} elseif ($vs_field_table === $ps_table) {
					// sort in primary table
					if (!$t_table->hasField($vs_field)) {
						if($va_sort_keys = $this->_getSortKeysForElement($vs_subfield ? $vs_subfield : $vs_field, $vn_table_num, $pa_hits)) {
							$va_sort_key_values[] = $va_sort_keys;
						}
					} else {
						// is intrinsic
						$va_field_info = $t_table->getFieldInfo($vs_field);
						if ($va_field_info['START'] && $va_field_info['END']) {
							$vs_field = $va_field_info['START'];
						}
						
						$vs_sql = "
							SELECT {$vs_table_pk}, {$vs_field}
							FROM {$ps_table}
							WHERE
								{$vs_table_pk} IN (?)
						";
						$qr_sort = $this->opo_db->query($vs_sql, array($pa_hits));
						$va_sort_keys = array();
						while($qr_sort->nextRow()) {
							$va_row = $qr_sort->getRow();
							$va_sort_keys[$va_row[$vs_table_pk]] = $va_row[$vs_field];
						}
						$va_sort_key_values[] = $va_sort_keys;
					}
				} elseif (($vs_field_table == 'ca_set_items') && ($vs_field == 'rank') && ((int)$vs_rel_type > 0)) {
					// sort in related table
					// sort by ranks in specific set
					$vs_sql = "
						SELECT {$ps_table}.{$vs_table_pk}, ca_set_items.rank
						FROM ca_sets
						INNER JOIN ca_set_items ON ca_set_items.set_id = ca_sets.set_id
						INNER JOIN {$ps_table} ON {$ps_table}.{$vs_table_pk} = ca_set_items.row_id
						WHERE
							(ca_set_items.table_num = ?) AND
							(ca_set_items.set_id = ?) AND
							{$ps_table}.{$vs_table_pk} IN (?)
					";
				
					$qr_sort = $this->opo_db->query($vs_sql, array($vn_table_num, (int)$vs_rel_type, $pa_hits));
					
				} else {
					$t_rel = $this->opo_datamodel->getInstanceByTableName($vs_field_table, true);
					$va_path = $this->opo_datamodel->getPath($ps_table, $vs_field_table);
			
					$vs_is_preferred_sql = null;
					$va_joins = array();
					
					if (sizeof($va_path) > 2) {
						// many-many
						$vs_last_table = null;
						// generate related joins
						foreach($va_path as $vs_table => $va_info) {
							$t_instance = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
			
							$vs_rel_type_sql = null;
							if($t_instance->isRelationship() && $vs_rel_type) {
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
					} else {
						$va_rels = $this->opo_datamodel->getRelationships($ps_table, $vs_field_table);
						if (!$va_rels) { break; }		// field is not valid

						if ($t_rel->hasField($vs_field)) { // intrinsic in related table
							$va_joins[$vs_field_table] = 'INNER JOIN '.$vs_field_table.' ON '.$ps_table.'.'.$va_rels[$ps_table][$vs_field_table][0][0].' = '.$vs_field_table.'.'.$va_rels[$ps_table][$vs_field_table][0][1]."\n";

							// if the related supports preferred values (eg. *_labels tables) then only consider those in the sort
							if ($t_rel->hasField('is_preferred')) {
								$vs_is_preferred_sql = " {$vs_field_table}.is_preferred = 1";
							}
						} else { // something else in related table (attribute!?)
							// so we'll now be getting the values from a different table so we need a different set of primary ids to
							// build the SQL to do that. For instance, if we're pulling ca_objects_x_occurrences.my_field relative to ca_objects,
							// we need a list of ca_objects_x_occurrences.relation_id values that are related to the objects in $pa_hits.
							// that list can obviously get longer, so we need a "reverse" mapping too so that we can make sense of that
							// sorted ca_objects_x_occurrences.relation_id list and sort our objects accordingly
							$va_maps = $this->_mapRowIDsForPathLength2($vn_table_num, $t_rel->tableNum(), $pa_hits, $pa_options);
							if(is_array($va_maps['list']) && sizeof($va_maps['list'])) {
								if($va_sort_keys = $this->_getSortKeysForElement($vs_subfield ? $vs_subfield : $vs_field, $t_rel->tableNum(), $va_maps['list'])) {
									// translate those sort keys back to keys in the original table, i.e. ca_objects_x_occurrences.relation_id => ca_objects.object_id
									$va_rewritten_sort_keys = array();
									foreach($va_sort_keys as $vn_key_in_rel_table => $vs_sort_key) {

										// there can be multiple related keys for one key in the primary table. for now we just decide the first one "wins"
										// @todo: is there a better way to deal with this?
										if(!isset($va_rewritten_sort_keys[$va_maps['reverse'][$vn_key_in_rel_table]])) {
											$va_rewritten_sort_keys[$va_maps['reverse'][$vn_key_in_rel_table]] = $vs_sort_key;
										}
									}

									$va_sort_key_values[] = $va_rewritten_sort_keys;
								}
							}

							continue; // skip that related table code below, we already have our values
						}
					}
					
					$vs_join_sql = join("\n", $va_joins);
					$vs_sql = "
						SELECT {$ps_table}.{$vs_table_pk}, {$vs_field_table}.{$vs_field}
						FROM {$ps_table}
						{$vs_join_sql}
						WHERE
							{$vs_is_preferred_sql} ".($vs_is_preferred_sql ? ' AND ' : '')." {$ps_table}.{$vs_table_pk} IN (?)
					";
				
					$qr_sort = $this->opo_db->query($vs_sql, array($pa_hits));
				}
					
				if($qr_sort) {
					$va_sort_keys = array();
					while($qr_sort->nextRow()) {
						$va_row = $qr_sort->getRow();
						$va_sort_keys[$va_row[$vs_table_pk]] = $va_row[$vs_field];
					}
					$va_sort_key_values[] = $va_sort_keys;
				}
			}
			
			return $this->_doSort($pa_hits, $va_sort_key_values, $ps_direction, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Perform sort
		 * 
		 * @param array $pa_hits
		 * @param array $pa_sortable_values
		 * @param string $ps_direction 
		 * @param array $pa_options
		 *
		 * @return array
		 */
		private function _doSort(&$pa_hits, $pa_sortable_values, $ps_direction='asc', $pa_options=null) {
			if (!in_array($ps_direction = strtolower($ps_direction), array('asc', 'desc'))) { $ps_direction = 'asc'; }
			$va_sorted_rows = array();
			$vb_return_index = caGetOption('returnIndex', $pa_options, false);
			
			if (sizeof($pa_hits) < 1000000) {
				//
				// Perform sort in-memory
				//
				$va_sort_buffer = array();
				
				$vn_c = 0;
				foreach($pa_hits as $vn_idx => $vn_hit) {
					$vs_key = '';
					foreach($pa_sortable_values as $vn_i => $va_sortable_values) {
						$vs_v = preg_replace("![^\w_]+!", " ", $va_sortable_values[$vn_hit]);
						
						$vs_key .= str_pad(substr($vs_v,0,50), 50, ' ', is_numeric($vs_v) ? STR_PAD_LEFT : STR_PAD_RIGHT);
					}
					$va_sort_buffer[$vs_key.str_pad($vn_c, 8, '0', STR_PAD_LEFT)] = $vb_return_index ? $vn_idx . '/' . $vn_hit : $vn_hit;
					$vn_c++;
				}
				
				$o_conf = caGetSearchConfig();

				$vn_sort_mode = SORT_FLAG_CASE;
				if (!$o_conf->get('dont_use_natural_sort')) { $vn_sort_mode |= SORT_NATURAL; } else { $vn_sort_mode |= SORT_STRING; }
				
				ksort($va_sort_buffer, $vn_sort_mode);
				if ($ps_direction == 'desc') { $va_sort_buffer = array_reverse($va_sort_buffer); }

				if($vb_return_index) {
					$va_return = array();
					foreach($va_sort_buffer as $vs_val) {
						$va_tmp = explode('/', $vs_val);
						$va_return[$va_tmp[0]] = $va_tmp[1];
					}
					return $va_return;
				} else {
					$va_sort_buffer = array_values($va_sort_buffer);
				}

				return $va_sort_buffer;
			} else {
				//
				// Use mysql memory-based table to do sorting
				//
				$vs_sort_tmp_table = $this->loadListIntoTemporaryResultTable($pa_hits, caGenerateGUID(), array('sortableValues' => $pa_sortable_values));
				$vs_sql = "
					SELECT row_id, idx
					FROM {$vs_sort_tmp_table}
					ORDER BY sort_key1 {$ps_direction}, sort_key2 {$ps_direction}, sort_key3 {$ps_direction}, row_id
				";
				$qr_sort = $this->opo_db->query($vs_sql, array());
				$va_results = $qr_sort->getAllFieldValues(array('row_id', 'idx'));
				$this->cleanupTemporaryResultTable();
				if($vb_return_index) {
					$va_sorted_rows = array();
					foreach($va_results['row_id'] as $vn_i => $vm_row_id) {
						$va_sorted_rows[$va_results['idx'][$vn_i]] = $vm_row_id;
					}
					return $va_sorted_rows;
				} else {
					return $va_results['row_id'];
				}
			}
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

			// is metadata element
			$vn_element_id = $t_element->getPrimaryKey();
			if (!($vs_sortable_value_fld = Attribute::getSortFieldForDatatype($t_element->get('datatype')))) {
				return false;
			}

			$vs_sql = null;

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
				$va_sort_keys[$va_row['row_id']] = $va_row[$vs_sort_field];
			}
			return $va_sort_keys;
		}
		# ------------------------------------------------------------------
		private function _mapRowIDsForPathLength2($pn_original_table_num, $pn_target_table, $pa_hits, $pa_options=null) {
			if(!($t_original_table = $this->opo_datamodel->getInstance($pn_original_table_num, true))) { return false; }
			if(!($t_target_table = $this->opo_datamodel->getInstance($pn_target_table, true))) { return false; }

			$va_sql_params = [];

			$va_primary_ids = $vs_resolve_links_using = null;
			if($vs_resolve_links_using = caGetOption('resolveLinksUsing', $pa_options)) {
				$va_primary_ids = caGetOption('primaryIDs', $pa_options);
				$va_primary_ids = $va_primary_ids[$vs_resolve_links_using];
			}

			$vs_original_table = $t_original_table->tableName();
			$vs_target_table = $t_target_table->tableName();

			$va_path = $this->opo_datamodel->getPath($pn_original_table_num, $pn_target_table);
			if(sizeof($va_path) != 2) { return false; }

			// get relationships to build join
			$va_relationships = $this->opo_datamodel->getRelationships($vs_original_table, $vs_target_table);

			$vs_primary_id_sql = '';
			if(is_array($va_primary_ids) && (sizeof($va_primary_ids) > 0)) {
				// assuming this is being used to sort on interstitials, we just need a WHERE on the keys on the other side of that target table
				$va_tmp = $this->opo_datamodel->getRelationships($vs_target_table, $vs_resolve_links_using);
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