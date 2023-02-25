<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseFindEngine.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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
	private $tmp_file_path;
	
	/**
	 *
	 */
	private $tmp_table_name;
	
	/**
	 * List of temporary tables created
	 */
	private $temporary_tables = [];
	
	# ------------------------------------------------
	/**
	 *
	 */
	protected $db;
	
	# ------------------------------------------------
	/**
	 * @param Db $po_db A database client object to use rather than creating a new connection. [Default is to create a new database connection]
	 */
	public function __construct($po_db=null) {			
		$this->db = $po_db ? $po_db : new Db();
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
		if ($this->tmp_table_name == "caResultTmp{$ps_key}") {
			return $this->tmp_table_name;
		}
		
		if ($this->tmp_table_name) {
			$this->cleanupTemporaryResultTable();
		}
		$this->tmp_file_path = tempnam(caGetTempDirPath(), 'caResultTmp');
		$this->tmp_table_name = "caResultTmp{$ps_key}";
		
		
		if (is_array($va_sortable_values = caGetOption('sortableValues', $pa_options, false))) {
			$this->db->query("
				CREATE TEMPORARY TABLE {$this->tmp_table_name} (
					row_id int unsigned not null,
					idx int unsigned not null,
					sort_key1 varchar(255) not null default '',
					sort_key2 varchar(255) not null default '',
					sort_key3 varchar(255) not null default '',
					key (row_id)
				) engine=memory;
			");
		} else {
			$this->db->query("
				CREATE TEMPORARY TABLE {$this->tmp_table_name} (
					row_id int unsigned not null,
					key (row_id)
				) engine=memory;
			");
		}
		if (!sizeof($pa_hits)) { return $this->tmp_table_name; }
		
		if (is_null($g_mysql_has_file_priv)) {	// Figure out if user has FILE priv
			$qr_grants = $this->db->query("
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
			file_put_contents($this->tmp_file_path, $vs_data);
			chmod($this->tmp_file_path, 0755);
			
			if (is_array($va_sortable_values)) {
				$vs_sql = "LOAD DATA INFILE '{$this->tmp_file_path}' INTO TABLE {$this->tmp_table_name} FIELDS TERMINATED BY ',' (row_id, sort_key1, sort_key2, sort_key3)";
				$this->db->query($vs_sql);
			} else {
				$this->db->query("LOAD DATA INFILE '{$this->tmp_file_path}' INTO TABLE {$this->tmp_table_name} (row_id)");
			}
		} else {
			// Fallback when database login does not have FILE priv
			
			if (is_array($va_sortable_values)) {
				$vs_sql = "INSERT IGNORE INTO {$this->tmp_table_name} (row_id, idx, sort_key1, sort_key2, sort_key3) VALUES ";
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
				$vs_sql = "INSERT IGNORE INTO {$this->tmp_table_name} (row_id) VALUES ";
				foreach($pa_hits as $vn_hit) {
					$vs_sql .= "(".(int)$vn_hit."),";
				}
			}
			$this->db->query(substr($vs_sql, 0, strlen($vs_sql)-1));
		}
		return $this->tmp_table_name;
	}
	# ------------------------------------------------------------------
	/**
	 * Remove the current temporary table and cleans up any temporary files on disk
	 *
	 * @return boolean Always return true
	 */
	public function cleanupTemporaryResultTable() {
		if ($this->tmp_table_name) {
			if($this->db->connected()) {
				$this->db->query("DROP TEMPORARY TABLE {$this->tmp_table_name}");
			}
		}
		if ($this->tmp_file_path) { @unlink($this->tmp_file_path); }
		$this->tmp_file_path = null;
		$this->tmp_table_name = null;
		
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
		$qr_items = $this->db->query($vs_sql = "
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
		$qr_items = $this->db->query("
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
			$qr_sort = $this->db->query("
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
		if(!sizeof($hits)) { return []; }
		if(!is_array($options)) { $options = []; }
		
		// Expand field list into array
		$sort_fields = is_array($sort_list) ? $sort_list : explode(';', $sort_list); 
		$embedded_sort_directions = array_map(function($v) { $t = explode(':', $v); return (sizeof($t) == 2) ? strtolower(trim(array_pop($t))) : null; }, $sort_fields);
		$rel_types = array_map(function($v) { $t = explode('/', $v); return (sizeof($t) == 2) ? array_filter(explode(',',array_pop($t)), 'strlen') : null; }, $sort_fields);
		
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
		
		$parsed_sort_spec = self::_parseSortOpts(array_shift($sort_fields));
		$primary_sort_field = $parsed_sort_spec['sort'];
		$options = array_merge($options, $parsed_sort_spec['options']);
		if ($primary_sort_field === '_natural') { return $hits; }
		$primary_sort_direction = self::sortDirection(array_shift($sort_directions));
		
		$sorted_hits = $this->doSort($hits, $table, $primary_sort_field, $primary_sort_direction, array_merge($options, ['relationshipTypes' => array_shift($rel_types)]));
		
		// secondary sorts?
		if(is_array($sort_fields) && (sizeof($sort_fields) > 0)) {	
			foreach($sort_fields as $i => $s) {
				$parsed_sort_spec = self::_parseSortOpts($s);
				$sort_fields[$i] = $parsed_sort_spec['sort'];
				$options = array_merge($options, $parsed_sort_spec['options']);
			}
			$sorted_hits = $this->_secondarySortHits($hits, $sorted_hits, $table, $primary_sort_field, $primary_sort_direction, $sort_fields, $sort_directions, array_merge($options, ['relationshipTypes' => $rel_types]));
		}
		
		return $sorted_hits;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private static function _parseSortOpts(string $sort) : array {
		$tmp = explode('%', $sort);
		$spec = array_shift($tmp);
		$opts = join("%", $tmp);
		
		$tag_opt_tmp = array_filter(preg_split("![\%\&]{1}!", $opts), "strlen");
		
		$opts = [];
		foreach($tag_opt_tmp as $t) {
			$tmp2 = explode('=', $t);
			$opts[$tmp2[0]] = $tmp2[1];
		}
		return ['sort' => $spec, 'options' => $opts];
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function _secondarySortHits(array $hits, array $page_hits, string $table, string $primary_field, string $primary_sort_direction, array $sort_fields, array $sort_directions, array $options=null) {
		if(!sizeof($hits)) { return []; }
		$sort_spec = array_shift($sort_fields);
		$sort_direction = self::sortDirection(array_shift($sort_directions));
		list($sort_table, $sort_field, $sort_subfield) = array_pad(explode(".", $sort_spec), 3, null);
	
		// Extract sortable values present on results page ($page_hits)
		$values = $this->_getSortValues($page_hits, $table, $primary_field, $sort_direction);
		// Get all ids for each key value 
		$row_ids_by_value = $this->_getRowIDsForValues($hits, $table, $primary_field, array_keys($values));
	
		// Sort rows for each value
		$sorted_rows_by_value = [];	// only includes rows with frequency > 1
		foreach($row_ids_by_value as $value => $row_ids) {
			if(sizeof($row_ids) === 1) { continue; }
			if(sizeof($row_ids) < 1) { unset($row_ids_by_value[$value]); continue; }
			
			$s = $this->doSort($row_ids, $table, $sort_spec, $sort_direction, []);
			if (is_array($s) && sizeof($s)) { 
				$sorted_rows_by_value[$value] = $s; 
			} else {
				unset($sorted_rows_by_value[$value]);
				continue;
			}
			
			if(sizeof($sort_fields)) {
				$sorted_rows_by_value[$value] = $this->_secondarySortHits($sorted_rows_by_value[$value], $sorted_rows_by_value[$value], $table, $sort_spec, $sort_direction, $sort_fields, $sort_directions, []);
			}
		}
	
		// Splice secondary sorts into page
		$sorted_page_hits = $page_hits;
		
		$page_start = $s = caGetOption('start', $options, 0);
		$l = caGetOption('limit', $options, sizeof($page_hits));
		
		foreach($sorted_rows_by_value as $value => $row_ids) {
			foreach($page_hits as $index => $row_id) {
				if (($map_index = array_search($row_id, $row_ids)) !== false) {
					if((sizeof($hits) === sizeof($row_ids)) && (sizeof($sorted_rows_by_value) === 1)) {
						return $row_ids;
					} elseif ($index > 0) {	// starts after beginning of this page
						$lr = sizeof($row_ids);
						if (($lr + $index) >= sizeof($sorted_page_hits)) { $lr = sizeof($sorted_page_hits) - $index; }
						array_splice($sorted_page_hits, $index, $lr, array_slice($row_ids, 0, $lr));
					} else {
						// first result is at start of page, so we need to figure out if the sequence begins on a previous page
						$start = null;
						if ($page_start > 0) {
							$c = 0;
							do {			// loop back through pages until we find the beginning
								$c++;
								$s -= $l;
								$p_hits = $this->doSort($hits, $table, $primary_field, $primary_sort_direction, ['start' => $s, 'limit' => $l]); 
					
								foreach($p_hits as $p_index => $p_row_id) {
									if (($p_map_index = array_search($p_row_id, $row_ids)) !== false) {	// sorted rows present on this page
										if($p_index > 0) { 		// starts on this page
											$start = (($c-1) * $l) + $p_index;
											break(2); 
										} else {					// covers entire currage page. so we can't be sure this is the start... keep looking
											continue(2);
										}
									} 
								}
								
								// nothing found on page
								$start = $l * ($c-1);
								break;
							} while($s > 0);
						} else {
							$start = $map_index;
						}
					
						if(!is_null($start)) {
							$s_row_ids = array_slice($row_ids, $start, $l);
							array_splice($sorted_page_hits, 0, sizeof($s_row_ids), $s_row_ids);
						} else {
							continue;
						}
					}
					
					
					break;
				}
			}
		}
		return $sorted_page_hits;
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
	public function doSort(array $hits, string $table, string $sort_field, string $sort_direction='asc', array $options=null) {
		if(!sizeof($hits)) { return []; }
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

		list($sort_field, $sort_filter) = array_pad(explode('|', $sort_field), 2, null);
		list($sort_table, $sort_field, $sort_subfield) = array_pad(explode(".", $sort_field), 3, null);
		if (!($t_bundle = Datamodel::getInstanceByTableName($sort_table, true))) { 
			//throw new ApplicationException(_t('Invalid sort field: %1', $sort_table));
			return $hits;
		}
		
		list($sort_filter_field, $sort_field_values) = array_pad(explode('=', $sort_filter), 2, null);
		$sort_field_values = array_map('trim', preg_split('![,;]+!', $sort_field_values));
	
		$hit_table = $this->_createTempTableForHits($hits);
		if ($sort_table === $table) {	// sort in primary table
			if (in_array($sort_field, ['history_tracking_current_value', 'ca_objects_location'])) {
				$policy = caGetOption('policy', $options, $table::getDefaultHistoryTrackingCurrentValuePolicyForTable($sort_table));
				$sort_key_values = $this->_sortByHistoryTrackingCurrentValue($t_table, $hit_table, $policy, $limit_sql, $sort_direction, $hits);
			} elseif ($t_table->hasField($sort_field)) {			// sort key is intrinsic
				$sort_key_values = $this->_sortByIntrinsic($t_table, $hit_table, $sort_field, $limit_sql, $sort_direction);
			} elseif(method_exists($t_table, 'hasElement') && $t_table->hasElement($sort_field)) { // is attribute
				$sort_key_values = $this->_sortByAttribute($t_table, $hit_table, $sort_field, $sort_subfield, $limit_sql, $sort_direction, $hits, ['filter' => $sort_filter_field ,'filterValues' => $sort_field_values]);
			} elseif($sort_field === 'preferred_labels') {
				$sort_key_values = $this->_sortByLabels($t_table, $hit_table, $sort_subfield, $limit_sql, $sort_direction);	
			} else {
				//throw new ApplicationException(_t('Unhandled sort'));
				return $hits;
			}
		} elseif($t_table->getLabelTableName() == $sort_table) {
			// is label?
			$sort_key_values = $this->_sortByLabels($t_table, $hit_table, $sort_field, $limit_sql, $sort_direction);	
		} else {
			// is related field
			$t_rel_table = Datamodel::getInstance($sort_table, true);
			if($is_label = is_a($t_rel_table, 'BaseLabel')) {
				$sort_field = $t_rel_table->getSubjectTableName().'.preferred_labels.'.$sort_field.($sort_subfield ? ".{$sort_subfield}" : '');
				list($sort_table, $sort_field, $sort_subfield) = explode(".", $sort_field);
				$t_rel_table = Datamodel::getInstance($sort_table, true);
			}
			
			$is_attribute = method_exists($t_rel_table, 'hasElement') ? $t_rel_table->hasElement($sort_field) : false;
			if ($t_rel_table->hasField($sort_field)) {			// sort key is intrinsic
				$sort_key_values = $this->_sortByRelatedIntrinsic($t_table, $t_rel_table, $hit_table, $sort_field, $limit_sql, $sort_direction, $options);
			} elseif($sort_field === 'preferred_labels') {		// sort key is preferred labels
				$sort_key_values = $this->_sortByRelatedLabels($t_table, $t_rel_table, $hit_table, $sort_subfield, $limit_sql, $sort_direction, $options);	
			} elseif($is_attribute) {							// sort key is metadata attribute
				$sort_key_values = $this->_sortByRelatedAttribute($t_table, $t_rel_table, $hit_table, $sort_field, $sort_subfield, $limit_sql, $sort_direction, $hits, ['filter' => $sort_filter_field ,'filterValues' => $sort_field_values]);		
			} else {
				//throw new ApplicationException(_t('Unhandled sort'));
				return $hits;
			}
		}

		return array_keys($sort_key_values);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _sortByIntrinsic($t_table, string $hit_table, string $intrinsic=null, string $limit_sql=null, $direction='asc') {
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		
		$direction = self::sortDirection($direction);
		
		$sql = "
			SELECT {$table}.{$table_pk}
			FROM {$table}
			INNER JOIN {$hit_table} ON {$hit_table}.row_id = {$table}.{$table_pk}
			ORDER BY {$table}.`{$intrinsic}` {$direction}
			{$limit_sql}
		";
		
		$qr_sort = $this->db->query($sql);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row[$table_pk]] = true;
		}
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _sortByRelatedIntrinsic($t_table, $t_rel_table, string $hit_table, string $intrinsic=null, string $limit_sql=null, $direction='asc', array $options=null) {
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		
		$direction =  self::sortDirection($direction);
		
		$joins = $this->_getJoins($t_table, $t_rel_table, $intrinsic, caGetOption('relationshipTypes', $options, null));
		$join_sql = join("\n", $joins);
		
		$sql = "
			SELECT t.{$table_pk}
			FROM {$table} t
			INNER JOIN {$hit_table} AS ht ON ht.row_id = t.{$table_pk}
			{$join_sql}
			ORDER BY s.`{$intrinsic}` {$direction}
			{$limit_sql}
		";
		
		$qr_sort = $this->db->query($sql);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row[$table_pk]] = true;
		}
		return $sort_keys;
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
		
		$t_label = $t_table->getLabelTableInstance();
		if (!$label_field || !$t_label->hasField($label_field)) { $label_field = $t_table->getLabelSortField(); }
		
		$direction = self::sortDirection($direction);
		
		$sql = "
			SELECT l.{$table_pk}
			FROM {$label_table} l
			INNER JOIN {$hit_table} AS ht ON ht.row_id = l.{$table_pk}
			ORDER BY l.`{$label_field}` {$direction}
			{$limit_sql}
		";
		$qr_sort = $this->db->query($sql);
		$sort_keys = $qr_sort->getAllFieldValues($table_pk);
		
		return array_flip($sort_keys);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _sortByRelatedLabels($t_table, $t_rel_table, string $hit_table, string $rel_label_field=null, string $limit_sql=null, $direction='asc', array $options=null) {
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$rel_table = $t_rel_table->tableName();
		$rel_table_pk = $t_rel_table->primaryKey();
		$rel_table_num = $t_rel_table->tableNum();
		$rel_label_table = $t_rel_table->getLabelTableName();
		
		$t_label = $t_rel_table->getLabelTableInstance();
		if (!$rel_label_field || !$t_label->hasField($rel_label_field)) { $rel_label_field = $t_rel_table->getLabelSortField(); }
		
		$direction = self::sortDirection($direction);
		$rel_types = caGetOption('relationshipTypes', $options, null);
		$joins = $this->_getJoins($t_table, $t_rel_table, $rel_label_field, $rel_types);
		$join_sql = join("\n", $joins);
		
		$sql = "
			SELECT t.{$table_pk}
			FROM {$table} t
			{$join_sql}
			LEFT JOIN {$rel_label_table} AS rl ON rl.{$rel_table_pk} = s.{$rel_table_pk}
			INNER JOIN {$hit_table} AS ht ON ht.row_id = t.{$table_pk}
			ORDER BY rl.`{$rel_label_field}` {$direction}
			{$limit_sql}
		";
		
		$qr_sort = $this->db->query($sql);
		$sort_keys = $qr_sort->getAllFieldValues($table_pk);
		
		return array_flip($sort_keys);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _sortByAttribute($t_table, string $hit_table, string $element_code=null, string $subelement_code=null, string $limit_sql=null, $direction='asc', array $hits=null, ?array $options=null) {
		$table_num = $t_table->tableNum();
		
		if (!($element_id = ca_metadata_elements::getElementID($e=$subelement_code ? $subelement_code : $element_code))) { 
			throw new ApplicationException(_t('Invalid element: %1', $e));
		}
		$attr_val_sort_field = ca_metadata_elements::getElementSortField($subelement_code ? $subelement_code : $element_code);
		
		$direction = self::sortDirection($direction);
		
		$filter_join = $filter_where = null;
		if($filter_element = caGetOption('filter', $options, null)) {
			$filter_values = caGetOption('filterValues', $options, []);
			if ($t_filter_element = ca_metadata_elements::getInstance($filter_element)) {
				$values = $filter_attr_field = null;
				switch($t_filter_element->get('datatype')) {
					case __CA_ATTRIBUTE_VALUE_TEXT__:
						$values = array_filter($filter_values, 'strlen');
						$values = array_map(function($v) { return json_encode($v); }, $filter_values);
						$filter_attr_field = 'value_longtext1';
						break;
					case __CA_ATTRIBUTE_VALUE_LIST__:
						$list_id = $t_filter_element->get('list_id');
						$values = array_filter(array_map(function($v) use ($list_id) { return caGetListItemID($list_id, $v); }, $filter_values), 'strlen');
						$filter_attr_field = 'item_id';
						break;
				
				}
				if(is_array($values) && sizeof($values)) {
					$filter_element_id = $t_filter_element->getPrimaryKey();
					$filter_join = "INNER JOIN ca_attribute_values AS fltr ON fltr.attribute_id = a.attribute_id";
					$filter_where = " AND (fltr.{$filter_attr_field} IN (".join(',', $values)."))";
				}
			}
		}

		$attr_tmp_table = $this->_createTempTableForAttributeIDs();
		$sql = "
			INSERT INTO {$attr_tmp_table} 
				SELECT a.attribute_id, a.row_id 
				FROM ca_attributes a  
				INNER JOIN ca_attribute_values as cav ON cav.attribute_id = a.attribute_id
				INNER JOIN {$hit_table} AS ht ON ht.row_id = a.row_id
				{$filter_join}
				WHERE a.table_num = ? AND cav.element_id = ? {$filter_where}
		";

		$qr_sort = $this->db->query($sql, [$table_num, $element_id]);
		
		$sql = "SELECT attr_tmp.row_id, cav.value_sortable
					FROM ca_attribute_values cav FORCE INDEX(i_sorting)
					INNER JOIN {$attr_tmp_table} AS attr_tmp ON attr_tmp.attribute_id = cav.attribute_id
					WHERE cav.element_id = ? 
					ORDER BY cav.value_sortable {$direction}
					{$limit_sql}";
					
		$qr_sort = $this->db->query($sql, [$element_id]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			if(!isset($sort_keys[$row['row_id']]) || (isset($sort_keys[$row['row_id']]) && (mb_strlen($row['value_sortable']) > $sort_keys[$row['row_id']]))) {
				unset($sort_keys[$row['row_id']]);
				$sort_keys[$row['row_id']] = mb_strlen($row['value_sortable']);
			}
		}
		
		// Add any row without the attribute set to the end of the sort set
		foreach($hits as $h) {
			if (!$sort_keys[$h]) { $sort_keys[$h] = true; }
		}
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _sortByHistoryTrackingCurrentValue($t_table, string $hit_table, string $policy=null, string $limit_sql=null, $direction='asc', array $hits=null) {
		$table_num = $t_table->tableNum();
		$table_name = $t_table->tableName();
		
		if(!method_exists($t_table, 'getPolicyConfig')) { return []; }
		if(!is_array($policy_info = $table_name::getPolicyConfig($policy))) {
			throw new ApplicationException(_t('Invalid policy %1', $policy));
		}
		
		$direction = self::sortDirection($direction);

		
		$sql = "SELECT htcv.row_id
				FROM ca_history_tracking_current_values htcv
				INNER JOIN ca_history_tracking_current_value_labels AS l ON htcv.tracking_id = l.tracking_id
				INNER JOIN {$hit_table} AS ht ON ht.row_id = htcv.row_id
				WHERE 
					htcv.table_num = ? AND (is_future IS NULL OR is_future = 0) AND htcv.policy = ?
				ORDER BY l.value_sort {$direction}
					{$limit_sql}";
		$qr_sort = $this->db->query($sql, [$table_num, $policy]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row['row_id']] = true;
		}
		
		// Add any row without the attribute set to the end of the sort set
		foreach($hits as $h) {
			if (!$sort_keys[$h]) { $sort_keys[$h] = true; }
		}
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _sortByRelatedAttribute($t_table, $t_rel_table, string $hit_table, string $element_code=null, string $subelement_code=null, string $limit_sql=null, $direction='asc', array $hits=null, array $options=null) {
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$rel_table = $t_rel_table->tableName();
		$rel_table_pk = $t_rel_table->primaryKey();
		$rel_table_num = $t_rel_table->tableNum();
		
		if (!($element_id = ca_metadata_elements::getElementID($subelement_code ? $subelement_code : $element_code))) { 
			throw new ApplicationException(_t('Invalid element'));
		}
		$attr_val_sort_field = ca_metadata_elements::getElementSortField($subelement_code ? $subelement_code : $element_code);

		$joins = $this->_getJoins($t_table, $t_rel_table, $element_code, caGetOption('relationshipTypes', $options, null));
		$join_sql = join("\n", $joins);
		
		$filter_join = $filter_where = null;
		if($filter_element = caGetOption('filter', $options, null)) {
			$filter_values = caGetOption('filterValues', $options, []);
			if ($t_filter_element = ca_metadata_elements::getInstance($filter_element)) {
				$values = $filter_attr_field = null;
				switch($t_filter_element->get('datatype')) {
					case __CA_ATTRIBUTE_VALUE_TEXT__:
						$values = array_filter($filter_values, 'strlen');
						$values = array_map(function($v) { return json_encode($v); }, $filter_values);
						$filter_attr_field = 'value_longtext1';
						break;
					case __CA_ATTRIBUTE_VALUE_LIST__:
						$list_id = $t_filter_element->get('list_id');
						$values = array_filter(array_map(function($v) use ($list_id) { return caGetListItemID($list_id, $v); }, $filter_values), 'strlen');
						$filter_attr_field = 'item_id';
						break;
				
				}
				if(is_array($values) && sizeof($values)) {
					$filter_element_id = $t_filter_element->getPrimaryKey();
					$filter_join = "INNER JOIN ca_attribute_values AS fltr ON fltr.attribute_id = a.attribute_id";
					$filter_where = " AND (fltr.{$filter_attr_field} IN (".join(',', $values)."))";
				}
			}
		}
		
		$sql = "SELECT t.{$table_pk} row_id, cav.value_sortable
					FROM {$table} t
					{$join_sql}
					LEFT JOIN ca_attributes AS a ON a.row_id = s.{$rel_table_pk} AND a.table_num = {$rel_table_num}
					LEFT JOIN ca_attribute_values AS cav ON cav.attribute_id = a.attribute_id
					INNER JOIN {$hit_table} AS ht ON ht.row_id = t.{$table_pk}
					{$filter_join}
					WHERE cav.element_id = ? {$filter_where}
					ORDER BY cav.value_sortable {$direction}"; 
		$qr_sort = $this->db->query($sql, [$element_id]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row['row_id']] = true;
		}
		
		// Add any row without the attribute set to the end of the sort set
		foreach($hits as $h) {
			if (!$sort_keys[$h]) { $sort_keys[$h] = true; }
		}
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getSortValues(array $hits, string $table, string $sort_field, string $direction='asc') {
		if(!sizeof($hits)) { return []; }
		$t_table = Datamodel::getInstance($table, true);
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		
		list($sort_table, $sort_field, $sort_subfield) = array_pad(explode(".", $sort_field), 3, null);
		
		$values = [];
		
		// Extract sort key values + frequency from hits
		if ($sort_table === $table) {	// sort in primary table
			if ($t_table->hasField($sort_field)) {			// sort key is intrinsic
				$values = $this->_getSortValuesForIntrinsic($hits, $t_table, $sort_field, $direction);
			} elseif(method_exists($t_table, 'hasElement') && $t_table->hasElement($sort_field)) { // is attribute
				$values = $this->_getSortValuesForAttribute($hits, $t_table, $sort_field, $direction);
			} elseif($sort_field === 'preferred_labels') {
				$values = $this->_getSortValuesForLabel($hits, $t_table, $sort_subfield ? $sort_subfield : $sort_field, $direction);	
			} else {
				throw new ApplicationException(_t('Unhandled secondary sort'));
			}
		} elseif($t_table->getLabelTableName() == $sort_table) {
			// is label?
			$values = $this->_getSortValuesForLabel($hits, $t_table, $sort_field, $direction);	
		} else {
			// is related field
			$t_rel_table = Datamodel::getInstance($sort_table, true);
			if($is_label = is_a($t_rel_table, 'BaseLabel')) {
				$sort_field = $t_rel_table->getSubjectTableName().'.preferred_labels.'.$sort_field.($sort_subfield ? ".{$sort_subfield}" : '');
				list($sort_table, $sort_field, $sort_subfield) = $x=explode(".", $sort_field);
				$t_rel_table = Datamodel::getInstance($sort_table, true);
			}
 			$is_attribute = method_exists($t_rel_table, 'hasElement') ? $t_rel_table->hasElement($sort_field) : false;
 			
 			if ($t_rel_table->hasField($sort_field)) {			// sort key is intrinsic
 				$sort_key_values[] = $this->_getRelatedSortValuesForIntrinsic($hits, $t_table, $t_rel_table, $sort_field, $direction);
 			} elseif($sort_field === 'preferred_labels') {		// sort key is preferred lables
 				$sort_key_values[] = $this->_getRelatedSortValuesForLabel($hits, $t_table, $t_rel_table, $sort_subfield ? $sort_subfield : $sort_field, $direction);	
 			} elseif($is_attribute) {							// sort key is metadata attribute
 				$sort_key_values[] = $this->_getRelatedSortValuesForAttribute($hits, $t_table, $t_rel_table, $sort_subfield ? $sort_subfield : $sort_field, $direction);		
 			} else {
				throw new ApplicationException(_t('Unhandled secondary sort'));
			}
		}
		return $values;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getSortValuesForIntrinsic(array $hits, $t_table, string $intrinsic, string $direction) {
		if(!sizeof($hits)) { return []; }
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$sql = "
			SELECT `{$intrinsic}` val
			FROM {$table}
			WHERE {$table}.{$table_pk} IN (?)
			ORDER BY val {$direction}
		";
		$qr_sort = $this->db->query($sql, [$hits]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			
			if(!isset($sort_keys[$row['val']])) { $sort_keys[$row['val']] = 0; }
			$sort_keys[$row['val']]++;
		}
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedSortValuesForIntrinsic(array $hits, $t_table, $t_rel_table,  string $intrinsic, string $direction) {
		if(!sizeof($hits)) { return []; }
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$rel_table = $t_rel_table->tableName();		
		$rel_table_pk = $t_rel_table->primaryKey();
		
		$joins = $this->_getJoins($t_table, $t_rel_table, $intrinsic);
		$join_sql = join("\n", $joins);
		
		$sql = "
			SELECT s.`{$intrinsic}` val
			FROM {$table} t
			{$join_sql}
			WHERE t.{$table_pk} IN (?)
			ORDER BY val {$direction}
		";
		$qr_sort = $this->db->query($sql, [$hits]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row['val']]++;
		}
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getSortValuesForLabel(array $hits, $t_table, string $label_field, string $direction) {
		if (!sizeof($hits)) { return []; }
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$label_table = $t_table->getLabelTableName();
		
		$t_label = $t_table->getLabelTableInstance();
		if (!$label_field || !$t_label->hasField($label_field)) { $label_field = $t_table->getLabelSortField(); }
		
		$sql = "
			SELECT l.{$label_field} val
			FROM {$label_table} l
			WHERE l.{$table_pk} IN (?)
			ORDER BY val {$direction}
		";
		$qr_sort = $this->db->query($sql, [$hits]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			if(!isset($sort_keys[$row['val']])) { $sort_keys[$row['val']] = 0; }
			$sort_keys[$row['val']]++;
		}
		
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedSortValuesForLabel(array $hits, $t_table, $t_rel_table, string $label_field, string $direction) {
		if (!sizeof($hits)) { return []; }
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$label_table = $t_table->getLabelTableName();
		$rel_table = $t_rel_table->tableName();		
		$rel_table_pk = $t_rel_table->primaryKey();
		
		$rel_label_table = $t_rel_table->getLabelTableName();
		
		if(!($t_label = $t_table->getLabelTableInstance())) { return $hits; }
		if (!$label_field || !$t_label->hasField($label_field)) { $label_field = $t_table->getLabelSortField(); }
		
		$joins = $this->_getJoins($t_table, $t_rel_table, $label_field);
		$join_sql = join("\n", $joins);
		
		$sql = "
			SELECT rl.{$label_field} val
			FROM {$label_table} t
			{$join_sql}
			INNER JOIN {$rel_label_table} AS rl ON rl.{$rel_table_pk} = s.{$rel_table_pk}
			WHERE rl.{$rel_table_pk} IN (?)
			ORDER BY val {$direction}
		";
		$qr_sort = $this->db->query($sql, [$hits]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row['val']]++;
		}
		
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getSortValuesForAttribute(array $hits, $t_table, string $element_code, string $direction) {
		if (!sizeof($hits)) { return []; }
		$table_num = $t_table->tableNum();
		
		if (!($element_id = ca_metadata_elements::getElementID($element_code))) { 
			throw new ApplicationException(_t('Invalid element'));
		}

		$sql = "SELECT cav.value_sortable val
					FROM ca_attribute_values cav
					INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
					WHERE cav.element_id = ? AND a.row_id IN (?)
				ORDER BY val {$direction}
					";
		
		$qr_sort = $this->db->query($sql, [$element_id, $hits]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row['val']]++;
		}
		return $sort_keys;
	}
	
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedSortValuesForAttribute(array $hits, $t_table, $t_rel_table, string $label_field, string $direction) {
		if (!sizeof($hits)) { return []; }
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$label_table = $t_table->getLabelTableName();
		$rel_table = $t_rel_table->tableName();		
		$rel_table_num = $t_rel_table->tableNum();		
		$rel_table_pk = $t_rel_table->primaryKey();
		
		if (!($element_id = ca_metadata_elements::getElementID($element_code))) { 
			throw new ApplicationException(_t('Invalid element'));
		}
		
		$joins = $this->_getJoins($t_table, $t_rel_table, $label_field);
		$join_sql = join("\n", $joins);
		
		$sql = "SELECT cav.value_sortable val
					FROM {$table} pt
					{$join_sql}
					LEFT JOIN ca_attributes AS a ON a.row_id = t.{$rel_table_pk} AND a.table_num = {$rel_table_num}
					LEFT JOIN ca_attribute_values AS cav ON cav.attribute_id = a.attribute_id
					WHERE cav.element_id = ? AND pt.{$table_pk} IN (?)
				ORDER BY val {$direction}
					";
		$qr_sort = $this->db->query($sql, [$element_id, $hits]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row['val']]++;
		}
		
		return $sort_keys;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRowIDsForValues(array $hits, string $table, string $sort_field, array $values) {
		if(!sizeof($hits)) { return []; }
		$t_table = Datamodel::getInstance($table, true);
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		
		list($sort_table, $sort_field, $sort_subfield) = array_pad(explode(".", $sort_field), 3, null);
		
		$row_ids = [];
		
		$hit_table = $this->_createTempTableForHits($hits);
		
		// Extract sort key values + frequency from hits
		if ($sort_table === $table) {	// sort in primary table
			if ($t_table->hasField($sort_field)) {			// sort key is intrinsic
				$row_ids = $this->_getRowIDsForIntrinsic($values, $t_table, $hit_table, $sort_field);
			} elseif(method_exists($t_table, 'hasElement') && $t_table->hasElement($sort_field)) { // is attribute
				$row_ids = $this->_getRowIDsForAttribute($values, $t_table, $hit_table, $sort_field);
			} elseif($sort_field === 'preferred_labels') {
				$row_ids = $this->_getRowIDsForLabel($values, $t_table, $hit_table, $sort_subfield ? $sort_subfield : $sort_field);	
			} else {
				throw new ApplicationException(_t('Unhandled secondary sort'));
			}
		} elseif($t_table->getLabelTableName() == $sort_table) {
			// is label?
			$row_ids = $this->_getRowIDsForLabel($values, $t_table, $hit_table, $sort_field);	
		} else {
			// is related field
			$t_rel_table = Datamodel::getInstance($sort_table, true);
			if($is_label = is_a($t_rel_table, 'BaseLabel')) {
				$sort_field = $t_rel_table->getSubjectTableName().'.preferred_labels.'.$sort_field.($sort_subfield ? ".{$sort_subfield}" : '');
				list($sort_table, $sort_field, $sort_subfield) = $x=explode(".", $sort_field);
				$t_rel_table = Datamodel::getInstance($sort_table, true);
			}
			
 			$is_attribute = method_exists($t_rel_table, 'hasElement') ? $t_rel_table->hasElement($sort_field) : false;
 			
 			if ($t_rel_table->hasField($sort_field)) {			// sort key is intrinsic
 				$sort_key_values[] = $this->_getRelatedRowIDsForIntrinsic($values, $t_table, $t_rel_table, $hit_table, $sort_field);
 			} elseif($sort_field === 'preferred_labels') {		// sort key is preferred lables
 				$sort_key_values[] = $this->_getRelatedRowIDsForLabel($values, $t_table, $t_rel_table, $hit_table, $sort_subfield ? $sort_subfield : $sort_field);	
 			} elseif($is_attribute) {							// sort key is metadata attribute
 				$sort_key_values[] = $this->_getRelatedRowIDsForAttribute($values, $t_table, $t_rel_table, $hit_table, $sort_field);		
 			} else {
				throw new ApplicationException(_t('Unhandled secondary sort'));
			}
		}
		return $row_ids;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRowIDsForIntrinsic(array $values, $t_table, string $hit_table, string $intrinsic) {
		if(!sizeof($values)) { return []; }
		
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$sql = "
			SELECT {$table}.{$table_pk}, {$table}.{$intrinsic} val
			FROM {$table}
			INNER JOIN {$hit_table} ON {$hit_table}.row_id = {$table}.{$table_pk}
			WHERE
				{$table}.{$intrinsic} IN (?)
		";
		$qr_sort = $this->db->query($sql, [$values]);
		$values = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$values[$row['val']][] = $row[$table_pk];
		}
		return $values;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedRowIDsForIntrinsic(array $values, $t_table, $t_rel_table, string $hit_table, string $intrinsic) {
		if(!sizeof($values)) { return []; }
		
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$rel_table = $t_rel_table->tableName();		
		$rel_table_pk = $t_rel_table->primaryKey();
		
		$joins = $this->_getJoins($t_table, $t_rel_table, $intrinsic);
		$join_sql = join("\n", $joins);
		
		$sql = "
			SELECT s.{$rel_table_pk}, s.{$intrinsic} val
			FROM {$table}
			INNER JOIN {$hit_table} ON {$hit_table}.row_id = {$table}.{$table_pk}
			{$join_sql}
			WHERE
				s.{$intrinsic} IN (?)
		";
		$qr_sort = $this->db->query($sql, [$values]);
		$values = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$values[$row['val']][] = $row[$table_pk];
		}
		return $values;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRowIDsForLabel(array $values, $t_table, string $hit_table, string $label_field) {
		if (!is_array($values) || !sizeof($values)) { return []; }
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$label_table = $t_table->getLabelTableName();
		
		$t_label = $t_table->getLabelTableInstance();
		if (!$label_field || !$t_label->hasField($label_field)) { $label_field = $t_table->getLabelSortField(); }
		
		$sql = "
			SELECT l.{$table_pk}, l.{$label_field} val
			FROM {$label_table} l
			INNER JOIN {$hit_table} AS ht ON ht.row_id = l.{$table_pk}
			WHERE
				l.{$label_field} IN (?)
		";
		$qr_sort = $this->db->query($sql, [$values]);
		$values = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$values[$row['val']][] = $row[$table_pk];
		}
		return $values;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedRowIDsForLabel(array $values, $t_table, $t_rel_table, string $hit_table, string $label_field) {
		if(!sizeof($values)) { return []; }
		
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$table_num = $t_table->tableNum();
		$label_table = $t_table->getLabelTableName();
		$rel_table = $t_rel_table->tableName();		
		$rel_table_pk = $t_rel_table->primaryKey();
		
		if(!($t_label = $t_table->getLabelTableInstance())) { return $hits; }
		if (!$label_field || !$t_label->hasField($label_field)) { $label_field = $t_table->getLabelSortField(); }
		
		$rel_label_table = $t_rel_table->getLabelTableName();
		
		$joins = $this->_getJoins($t_table, $t_rel_table, $label_field);
		$join_sql = join("\n", $joins);
		
		$sql = "
			SELECT rl.{$table_pk}, rl.{$label_field} val
			FROM {$label_table} t
			{$join_sql}
			LEFT JOIN {$rel_label_table} AS rl ON rl.{$rel_table_pk} = s.{$rel_table_pk}
			INNER JOIN {$hit_table} AS ht ON ht.row_id = l.{$table_pk}
			WHERE
				rl.{$label_field} IN (?)
		";
		$qr_sort = $this->db->query($sql, [$values]);
		$values = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$values[$row['val']][] = $row[$table_pk];
		}
		return $values;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRowIDsForAttribute(array $values, $t_table, string $hit_table, string $element_code) {
		if(!sizeof($values)) { return []; }
		
		$table_num = $t_table->tableNum();
		
		if (!($element_id = ca_metadata_elements::getElementID($element_code))) { 
			throw new ApplicationException(_t('Invalid element'));
		}
		$attr_val_sort_field = ca_metadata_elements::getElementSortField($element_code);
		

		$sql = "SELECT a.row_id, cav.{$attr_val_sort_field} val
					FROM ca_attribute_values cav
					INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
					INNER JOIN {$hit_table} AS ht ON ht.row_id = a.row_id
					WHERE 
						a.table_num = ? AND cav.element_id = ? AND cav.{$attr_val_sort_field} IN (?)
		";
		
		$qr_sort = $this->db->query($sql, [$table_num, $element_id, $values]);
		$values = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$values[$row['val']][] = $row['row_id'];
		}
		return $values;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getRelatedRowIDsForAttribute(array $values, $t_table, $t_rel_table, string $hit_table, string $element_code=null) {
		if(!sizeof($values)) { return []; }
		
		$table_num = $t_table->tableNum();
		$rel_table_num = $t_rel_table->tableNum();
		
		if (!($element_id = ca_metadata_elements::getElementID($element_code))) { 
			throw new ApplicationException(_t('Invalid element'));
		}
		$attr_val_sort_field = ca_metadata_elements::getElementSortField($element_code);

		$sql = "SELECT a.row_id, cav.{$attr_val_sort_field} val
					FROM ca_attribute_values cav
					INNER JOIN ca_attributes AS a ON a.attribute_id = cav.attribute_id
					INNER JOIN {$hit_table} AS ht ON ht.row_id = a.row_id
					WHERE 
						a.table_num = ? AND cav.element_id = ? AND cav.{$attr_val_sort_field} IN (?)
		";
		$qr_sort = $this->db->query($sql, [$rel_table_num, $element_id, $values]);
		$sort_keys = [];
		while($qr_sort->nextRow()) {
			$row = $qr_sort->getRow();
			$sort_keys[$row['row_id']] = true;
		}
		return $sort_keys;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _createTempTableForHits(array $hits) {
		$table_name = '_caSortTmp'.str_replace('-', '',caGenerateGUID());
		$this->db->query("DROP TEMPORARY TABLE IF EXISTS {$table_name}");
		$this->db->query("
			CREATE TEMPORARY TABLE {$table_name} (
				row_id int unsigned not null primary key
			) engine=memory;
		");
		
		if ($this->db->numErrors()) {
			return false;
		}
		
		while(sizeof($hits) > 0) {
			$hits_buf = array_splice($hits, 0, 250000, []);
			$sql = "INSERT IGNORE INTO {$table_name} VALUES ".join(',', array_map(function($v) { return '('.(int)$v.')'; }, $hits_buf));
			if (!$this->db->query($sql)) {
				$this->_dropTempTable($table_name);
				return false;
			}
		}
		
		$this->temporary_tables[$table_name] = true;
		return $table_name;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private function _getJoins($t_table, $t_rel_table, string $sort_field, array $rel_types=null) {
		$table = $t_table->tableName();
		$table_pk = $t_table->primaryKey();
		$rel_table = $t_rel_table->tableName();		
		$rel_table_pk = $t_rel_table->primaryKey();
		
		$path = Datamodel::getPath($table, $rel_table);
		$path = array_keys($path);

		$is_attribute = method_exists($t_rel_table, 'hasElement') ? $t_rel_table->hasElement($sort_field) : false;
		
		$rel_type_sql = (is_array($rel_types) && (sizeof($rel_types) > 0)) ? " AND l.type_id IN (".join(',', array_map('intval', $rel_types)).")" : '';
	
		$joins = [];
		switch($psize = sizeof($path)) {
			case 3:
				$linking_table = $path[1];
				if ($table === $rel_table) {
					$t_relation = Datamodel::getInstance($linking_table, true);
					// self relation
					$joins[] = "LEFT JOIN {$linking_table} AS l ON t.{$table_pk} = l.{$table_pk}{$rel_type_sql}";
					$joins[] = "LEFT JOIN {$rel_table} AS s ON (s.{$rel_table_pk} = l.".$t_relation->getLeftTableFieldName().") OR (s.{$rel_table_pk} = l.".$t_relation->getRightTableFieldName().")";
				} elseif ($is_attribute) {
					$joins[] = "LEFT JOIN {$linking_table} AS l ON t.{$table_pk} = l.{$table_pk}{$rel_type_sql}";
					$joins[] = "LEFT JOIN {$rel_table} AS s ON s.{$rel_table_pk} = l.{$rel_table_pk}";
				} else {							
					$joins[] = "LEFT JOIN {$linking_table} AS l ON t.{$table_pk} = l.{$table_pk}{$rel_type_sql}";
					$joins[] = "LEFT JOIN {$rel_table} AS s ON s.{$rel_table_pk} = l.{$rel_table_pk}";
				}
				
				break;
			case 2:
				$t = Datamodel::getInstance($table, true);
			
				if(method_exists($t, 'isSelfRelationship') && ($t->isSelfRelationship())) {
					$joins[] = "INNER JOIN {$rel_table} AS s ON (s.{$rel_table_pk} = t.".$t->getLeftTableFieldName().") OR (s.{$rel_table_pk} = t.".$t->getRightTableFieldName().")";
				} elseif(is_array($rels = Datamodel::getRelationships($table, $rel_table))) {
					$lfield = $rels[$table][$rel_table][0][0];
					$rfield = $rels[$table][$rel_table][0][1];
					$joins[] = "INNER JOIN {$rel_table} AS s ON t.{$lfield} = s.{$rfield}";
				}
				//
				break;
			default:
				throw new ApplicationException(_t('Invalid related sort: %1', join('/', $path)));
				break;
		}
		return $joins;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _createTempTableForAttributeIDs() {		
		$table_name = '_caAttrTmp_'.str_replace('-', '',caGenerateGUID());
		$this->db->query("DROP TEMPORARY TABLE IF EXISTS {$table_name}");
		$this->db->query("CREATE TEMPORARY TABLE {$table_name} (attribute_id int unsigned not null primary key, row_id int unsigned not null) engine=memory");
		$this->temporary_tables[$table_name] = true;
		
		return $table_name;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _dropTempTable($table_name) {
		$this->db->query("
			DROP TEMPORARY TABLE IF EXISTS {$table_name};
		");
		if ($this->db->numErrors()) {
			return false;
		}
		unset($this->temporary_tables[$table_name]);
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function sortDirection($direction) {
		return (strtolower($direction) === 'desc') ? 'desc' : 'asc';
	}
	# -------------------------------------------------------
	/**
	 * Discards any existing temporary table on deallocation.
	 */
	public function __destruct() {
		if ($this->tmp_table_name) {
			$this->cleanupTemporaryResultTable();
		}
		
		foreach(array_keys($this->temporary_tables) as $t) {
			try {
				$this->_dropTempTable($t);	
			} catch(Exception $e) {
				// noop - is unrecoverable
			}
		}
	}	
	# -------------------------------------------------------
}	
