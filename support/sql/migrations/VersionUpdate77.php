<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/VersionUpdate77.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 
 require_once(__CA_LIB_DIR__.'/ca/BaseVersionUpdater.php');
 require_once(__CA_LIB_DIR__."/core/Db.php");
 require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 
	class VersionUpdate77 extends BaseVersionUpdater {
		# -------------------------------------------------------
		protected $opn_schema_update_to_version_number = 77;
		private $ops_message = '';
		# -------------------------------------------------------
		/**
		 * Will contain error message for inclusion in post-update message if error occurred
		 */
		private $opa_error_messages = array();
		# -------------------------------------------------------
		/**
		 *
		 * @return int The number of the schema update
		 */
		public function __construct() {
		
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return array A list of tasks to execute before performing database update
		 */
		public function getPreupdateTasks() {
			return array('updateTable');
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return string HTML to display after update
		 */
		public function getPostupdateMessage() {
			if ($this->ops_message) {
				return $this->ops_message;
			} 
			return parent::getPostupdateMessage();
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return string HTML to display after update
		 */
		public function updateTable() {
			$o_db = new Db();
			$va_fields = $o_db->getFieldsFromTable('ca_list_items');
			
			$vb_found_deleted_field = false;
			foreach($va_fields as $va_field_info) {
				if ($va_field_info['fieldname'] == 'deleted') {
					$vb_found_deleted_field = true;
					break;
				}
			}
			
			if (!$vb_found_deleted_field) {
				$o_db->query("ALTER TABLE ca_list_items ADD COLUMN deleted tinyint unsigned not null default 0;");
				if ($o_db->numErrors()) {
					$this->ops_message = _t('Could not add missing "deleted" field to table ca_list_items');
					return false;
				}
				$this->ops_message = _t('Added missing "deleted" field to table ca_list_items');
			}
			
			return true;
		}
		# -------------------------------------------------------
	}
?>