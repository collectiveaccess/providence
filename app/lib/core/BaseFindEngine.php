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
		 * @param array $pa_hits Array with *keys* set to row_id values
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
				file_put_contents($this->ops_tmp_file_path, join("\n", array_keys($pa_hits)));
				chmod($this->ops_tmp_file_path, 0755);
				
				$this->opo_db->query("LOAD DATA INFILE '{$this->ops_tmp_file_path}' INTO TABLE {$this->ops_tmp_table_name} (row_id)");
			} else {
				// Fallback when database login does not have FILE priv
				$vs_sql = "INSERT IGNORE INTO {$this->ops_tmp_table_name} (row_id) VALUES ";
				foreach(array_keys($pa_hits) as $vn_row_id) {
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
		 * Discards any existing temporary table on deallocation.
		 */
		public function __destruct() {
			if ($this->ops_tmp_table_name) {
				$this->cleanupTemporaryResultTable();
			}
		}
		# ------------------------------------------------------------------
	}	
?>