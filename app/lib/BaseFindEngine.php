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
		
		/**
		 * List of temporary tables created
		 */
		private $temporary_tables = [];
		
		# ------------------------------------------------
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
		public function sortHits(array $hits, string $table, $sort_list, $sort_directions='asc', array $options=null) {
			if (!$t_table = Datamodel::getInstanceByTableName($table, true)) { return null; } // invalid table
			if (!is_array($hits) || !sizeof($hits)) { return $hits; } // Don't try to sort empty results
			$start = caGetOption('start', $options, 0);
			$limit = caGetOption('limit', $options, null);
			
			$limit_sql = '';
			if ($limit > 0) {
				$limit_sql = "LIMIT {$limit}";
			}
			if ($start > 0) {
				$limit_sql .= " OFFSET {$start}";
			}
			
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
			
			$primary_sort_field = array_shift($sort_fields);
			if ($primary_sort_field === '_natural') { return $hits; }
			$primary_sort_direction = (strtolower(array_shift($sort_directions)) === 'desc') ? 'desc' : 'asc';
					
			list($sort_table, $sort_field, $sort_subfield) = explode(".", $primary_sort_field);
			if (!($t_bundle = Datamodel::getInstanceByTableName($sort_table, true))) { 
				//throw new ApplicationException(_t('Invalid sort field: %1', $sort_table));
				return $pa_hits;
			}

			if ($sort_table === $table) {	// sort in primary table
				$hits_table = $this->_createTempTableForHits($hits);
				if ($t_table->hasField($sort_field)) {
					// is intrinsic
					$sql = "
						SELECT {$table}.{$table_pk}
						FROM {$table}
						INNER JOIN {$hits_table} ON {$hits_table}.row_id = {$table}.{$table_pk}
						ORDER BY {$table}.`{$sort_field}` {$primary_sort_direction}
						{$limit_sql}
					";
					$qr_sort = $this->opo_db->query($sql);
					$sort_keys = [];
					while($qr_sort->nextRow()) {
						$row = $qr_sort->getRow();
						$sort_keys[$row[$table_pk]] = true;
					}
					$sort_key_values[] = $sort_keys;
				} elseif($t_table->hasElement($sort_field)) {
					$hits_table = $this->_createTempTableForHits($hits);
					
					// is attribute
					$element_id = ca_metadata_elements::getElementID($sort_field);
					$attr_val_sort_field = ca_metadata_elements::getElementSortField($sort_field);

					$attr_tmp_table = $this->_createTempTableForAttributeIDs();
					$sql = "
						INSERT INTO {$attr_tmp_table} 
							SELECT a.attribute_id, a.row_id 
							FROM ca_attributes a  
							INNER JOIN {$hits_table} AS ht ON ht.row_id = a.row_id
							WHERE a.table_num = ? and a.element_id = ?
					";

					$qr_sort = $this->opo_db->query($sql, [$table_num, $element_id]);
					
					$sql = "SELECT attr_tmp.row_id 
								FROM ca_attribute_values cav FORCE INDEX(i_sorting)
								INNER JOIN {$attr_tmp_table} AS attr_tmp ON attr_tmp.attribute_id = cav.attribute_id
								WHERE cav.element_id = ? 
								ORDER BY cav.value_sortable {$primary_sort_direction}
								{$limit_sql}";
					
					$qr_sort = $this->opo_db->query($sql, [$element_id]);
					$sort_keys = [];
					while($qr_sort->nextRow()) {
						$row = $qr_sort->getRow();
						$sort_keys[$row['row_id']] = true;
					}
					$sort_key_values[] = $sort_keys;
						
						
				} elseif($sort_field === 'preferred_labels') {
					$hits_table = $this->_createTempTableForHits($hits);
					$sort_key_values[] = $this->_sortByLabels($t_table, $hits_table, $sort_subfield, $limit_sql, $primary_sort_direction);	
				} else {
					throw new ApplicationException(_t('Unhandled sort'));
				}
			} elseif($t_table->getLabelTableName() == $sort_table) {
				// is label?
				$hits_table = $this->_createTempTableForHits($hits);
				$sort_key_values[] = $this->_sortByLabels($t_table, $hits_table, $sort_field, $limit_sql, $primary_sort_direction);	
			} else {
				// is related field
				
				// 1. Gets joins
				$path = Datamodel::getPath($table, $sort_table);
				$path = array_keys($path);
				
				$t_rel_table = Datamodel::getInstance($sort_table, true);
				$sort_table_pk = $t_rel_table->primaryKey();
				
				$is_attribute = $t_rel_table->hasElement($sort_field);
				
				$joins = [];
				switch(sizeof($path)) {
					case 3:
						$hits_table = $this->_createTempTableForHits($hits);
						$linking_table = $path[1];
						
						if ($is_attribute) {
							$joins[] = "INNER JOIN {$linking_table} AS l ON attr_tmp.row_id = l.{$sort_table_pk}";
							$joins[] = "INNER JOIN {$table} AS t ON t.{$table_pk} = l.{$table_pk}";
						} else {							
							$joins[] = "INNER JOIN {$linking_table} AS l ON t.{$table_pk} = l.{$table_pk}";
							$joins[] = "INNER JOIN {$sort_table} AS s ON s.{$sort_table_pk} = l.{$sort_table_pk}";
						}
						
						break;
					case 2:
						$sort_table = array_pop($path);
						if ($is_attribute) {
							$joins[] = "INNER JOIN {$sort_table} AS s ON s.{$sort_table_pk} = t.{$sort_table_pk}";
							$joins[] = "INNER JOIN {$table} AS t ON t.{$sort_table_pk} = l.{$table_pk}";
						} else {	
							$joins[] = "INNER JOIN {$sort_table} AS s ON s.{$sort_table_pk} = t.{$sort_table_pk}";
						}
						
						break;
					default:
						throw new ApplicationException(_t('Invalid related sort'));
						break;
				}
				
				$join_sql = join("\n", $joins);
				if ($t_rel_table->hasField($sort_field)) {
					// is intrinsic
					$sql = "
						SELECT t.{$table_pk}
						FROM {$table} t
						INNER JOIN {$hits_table} AS ht ON ht.row_id = t.{$table_pk}
						{$joins_sql}
						ORDER BY s.`{$sort_field}` {$primary_sort_direction}
						{$limit_sql}
					";
					$qr_sort = $this->opo_db->query($sql);
					$sort_keys = [];
					while($qr_sort->nextRow()) {
						$row = $qr_sort->getRow();
						$sort_keys[$row[$table_pk]] = true;
					}
					$sort_key_values[] = $sort_keys;
				} elseif($sort_field === 'preferred_labels') {
					$hits_table = $this->_createTempTableForHits($hits);
					$sort_key_values[] = $this->_sortByRelatedLabels($t_table, $t_rel_table, $joins, $hits_table, $sort_subfield, $limit_sql, $primary_sort_direction);	
				} elseif($is_attribute) {
					$hits_table = $this->_createTempTableForHits($hits);
					
					// is attribute
					$element_id = ca_metadata_elements::getElementID($sort_field);
					$attr_val_sort_field = ca_metadata_elements::getElementSortField($sort_field);

					$attr_tmp_table = $this->_createTempTableForAttributeIDs();
					$sql = "
						INSERT INTO {$attr_tmp_table} 
							SELECT a.attribute_id, a.row_id 
							FROM ca_attributes a  
							INNER JOIN {$hits_table} AS ht ON ht.row_id = a.row_id
							WHERE a.table_num = ? and a.element_id = ?
					";

					$qr_sort = $this->opo_db->query($sql, [$t_rel_table->tableNum(), $element_id]);
					
					$sql = "SELECT t.{$table_pk} row_id
								FROM ca_attribute_values cav FORCE INDEX(i_sorting)
								INNER JOIN {$attr_tmp_table} AS attr_tmp ON attr_tmp.attribute_id = cav.attribute_id
								{$join_sql}
								WHERE cav.element_id = ? 
								ORDER BY cav.value_sortable {$primary_sort_direction}
								{$limit_sql}";
					
					$qr_sort = $this->opo_db->query($sql, [$element_id]);
					$sort_keys = [];
					while($qr_sort->nextRow()) {
						$row = $qr_sort->getRow();
						$sort_keys[$row['row_id']] = true;
					}
					$sort_key_values[] = $sort_keys;		
				} else {
					throw new ApplicationException(_t('Unhandled sort'));
				}
			}

			$hits = array_keys(array_shift($sort_key_values));
			
			return $hits;
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		private function _sortByLabels($t_table, string $hit_table, string $label_field=null, string $limit_sql=null, $direction='asc') {
			$table = $t_table->tableName();
			$table_pk = $t_table->primaryKey();
			$table_num = $t_table->tableNum();
			$label_table = $t_table->getLabelTableName();
			if (!$label_field) { $label_field = $t_table->getLabelSortField(); }
			
			$sort_direction = (strtolower($direction) === 'desc') ? 'desc' : 'asc';
			
			// TODO: validate $table_pk and $sort_subfield
			$sql = "
				SELECT t.{$table_pk}
				FROM {$table} t
				INNER JOIN {$label_table} AS l ON l.{$table_pk} = t.{$table_pk}
				INNER JOIN {$hit_table} AS ht ON ht.row_id = t.{$table_pk}
				ORDER BY l.`{$label_field}` {$sort_direction}
				{$limit_sql}
			";
			$qr_sort = $this->opo_db->query($sql);
			$sort_keys = $qr_sort->getAllFieldValues($table_pk);
			
			return array_flip($sort_keys);
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		private function _sortByRelatedLabels($t_table, $t_rel_table, array $joins, string $hit_table, string $rel_label_field=null, string $limit_sql=null, $direction='asc') {
			$table = $t_table->tableName();
			$table_pk = $t_table->primaryKey();
			$table_num = $t_table->tableNum();
			$rel_table = $t_rel_table->tableName();
			$rel_table_pk = $t_rel_table->primaryKey();
			$rel_table_num = $t_rel_table->tableNum();
			$rel_label_table = $t_rel_table->getLabelTableName();
			if (!$rel_label_field) { $rel_label_field = $t_rel_table->getLabelSortField(); }
			
			$sort_direction = (strtolower($direction) === 'desc') ? 'desc' : 'asc';
			
			$join_sql = join("\n", $joins);
			// TODO: validate $table_pk and $sort_subfield
			$sql = "
				SELECT t.{$table_pk}
				FROM {$table} t
				{$join_sql}
				INNER JOIN {$rel_label_table} AS rl ON rl.{$rel_table_pk} = s.{$rel_table_pk}
				INNER JOIN {$hit_table} AS ht ON ht.row_id = t.{$table_pk}
				ORDER BY rl.`{$rel_label_field}` {$sort_direction}
				{$limit_sql}
			";
			$qr_sort = $this->opo_db->query($sql);
			$sort_keys = $qr_sort->getAllFieldValues($table_pk);
			
			return array_flip($sort_keys);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private function _createTempTableForHits(array $hits) {
			$table_name = '_caSortTmp'.str_replace('-', '',caGenerateGUID());
			$this->opo_db->query("DROP TABLE IF EXISTS {$table_name}");
			$this->opo_db->query("
				CREATE TEMPORARY TABLE {$table_name} (
					row_id int unsigned not null primary key
				) engine=memory;
			");
			
			if ($this->opo_db->numErrors()) {
				return false;
			}
			
			while(sizeof($hits) > 0) {
				$hits_buf = array_splice($hits, 0, 250000, []);
				if (!$this->opo_db->query("INSERT IGNORE INTO {$table_name} VALUES ".join(',', array_map(function($v) { return '('.(int)$v.')'; }, $hits_buf)))) {
					$this->_dropTempTable($table_name);
					return false;
				}
			}
			
			$this->temporary_tables[$table_name] = true;
			return $table_name;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private function _createTempTableForAttributeIDs() {		
			$table_name = '_caAttrTmp_'.str_replace('-', '',caGenerateGUID());
			$this->opo_db->query("DROP TABLE IF EXISTS {$table_name}");
			$this->opo_db->query("CREATE TABLE {$table_name} (attribute_id int unsigned not null primary key, row_id int unsigned not null) engine=memory");
			$this->temporary_tables[$table_name] = true;
			
			return $table_name;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private function _dropTempTable($table_name) {
			$this->opo_db->query("
				DROP TABLE IF EXISTS {$table_name};
			");
			if ($this->opo_db->numErrors()) {
				return false;
			}
			unset($this->temporary_tables[$table_name]);
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Discards any existing temporary table on deallocation.
		 */
		public function __destruct() {
			if ($this->ops_tmp_table_name) {
				$this->cleanupTemporaryResultTable();
			}
			
			foreach(array_keys($this->temporary_tables) as $t) {
				$this->_dropTempTable($t);	
			}
		}	
		# -------------------------------------------------------
	}	
