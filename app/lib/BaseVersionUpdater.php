<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseVersionUpdater.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @subpackage Installer
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 
	class BaseVersionUpdater {
		# -------------------------------------------------------
		/**
		 *
		 */
		protected $opn_schema_update_to_version_number = null;
		# -------------------------------------------------------
		/**
		 *
		 * @return int The number of the schema update
		 */
		public function getVersion() {
			return $this->opn_schema_update_to_version_number;
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return array A list of tasks to execute before performing database update
		 */
		public function getPreupdateTasks() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return array A list of tasks to execute after performing database update
		 */
		public function getPostupdateTasks() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return string HTML to display prior to update
		 */
		public function getPreupdateMessage() {
			return null;
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return string HTML to display after update
		 */
		public function getPostupdateMessage() {
			return null;
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return bool
		 */
		public function applyDatabaseUpdate($pa_options=null) {
			return BaseVersionUpdater::performDatabaseUpdate($this->getVersion(), $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Applies specified update to system
		 *
		 * @param int $pn_version Version number of update to apply
		 * @param array $pa_options Array of options. Supported options are:
		 *		cleanCache = Remove all application caches after database update if true. Default is false.
		 * @return bool
		 */
		static function performDatabaseUpdate($pn_version, $pa_options=null) {
			if (($pn_version = (int)$pn_version) <= 0) { return null; }
			
			$va_messages = array();
			$t = new Transaction();
			$o_db = $t->getDb();
			
			if (!file_exists(__CA_BASE_DIR__."/support/sql/migrations/{$pn_version}.sql")) { return null; }
			$o_handle = fopen(__CA_BASE_DIR__."/support/sql/migrations/{$pn_version}.sql", "r");
			
			$vn_query_total_nb = 0;
			$vs_query = '';
			while (!feof($o_handle)) { 
				$vs_line = fgets($o_handle,8128);
				
				// $vs_query gets a concat of lines while a ; is not met
				$vs_query.= $vs_line;
				// Reading 1 character before the EOL
				$vs_before_eol=substr(rtrim($vs_line),-1);
				if ($vs_before_eol == ';') {
					//If the line ends with a ; then we will take all what's before and execute it
					
					//Counts the number of requests contained in the SQL file
					$vn_query_total_nb++;
					
					//Launching the query
					$o_db->query($vs_query);
					if ($o_db->numErrors() > 0) {
						// temp array to catch the errors
						$va_error_array_for_printing =$o_db->getErrors(); 
						
						$va_messages['error_'.$pn_version] = _t("Error applying database migration %1: %2; error was at query %3; query was %4", $pn_version, join('; ', $va_error_array_for_printing), $vn_query_total_nb, $vs_query);

						$o_db->clearErrors();
						
						$t->rollback();
						return $va_messages;
					}
					
					// Reset variable
					$vs_query = ''; 
				} 
				
				$t->commit();
			}
			
			if (isset($pa_options['cleanCache']) && $pa_options['cleanCache']) {
				// Clean cache
				caRemoveDirectory(__CA_APP_DIR__.'/tmp', false);
			}
		
			return $va_messages;
		}
		# -------------------------------------------------------
	}	
?>