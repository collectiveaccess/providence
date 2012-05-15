<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/VersionUpdate49.php : 
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
 
 require_once(__CA_LIB_DIR__.'/ca/BaseVersionUpdater.php');
 require_once(__CA_LIB_DIR__."/core/Db.php");
 require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
 require_once(__CA_MODELS_DIR__.'/ca_locales.php');
 
	class VersionUpdate49 extends BaseVersionUpdater {
		# -------------------------------------------------------
		protected $opn_schema_update_to_version_number = 49;
		
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
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return array A list of tasks to execute after performing database update
		 */
		public function getPostupdateTasks() {
			return array('updateRelationshipTypes');
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
			if (sizeof($this->opa_error_messages)) {
				return _t("Errors occurred while applying migration 49: %1", join("; ", $this->opa_error_messages));
			} 
			return parent::getPostupdateMessage();
		}
		# -------------------------------------------------------
		/**
		 *
		 * @return string HTML to display after update
		 */
		public function updateRelationshipTypes() {
			$t_locale = new ca_locales();
			$o_config = Configuration::load();
			$pn_locale_id = $t_locale->loadLocaleByCode($o_config->get('locale_default'));		// default locale_id
			
			$o_db = new Db();
			$o_dm = Datamodel::load();
			
			$va_tables = $o_dm->getTableNames();
		
			foreach($va_tables as $vs_table) {
				if (!preg_match('!_x_!', $vs_table)) { continue; }
				require_once(__CA_MODELS_DIR__."/{$vs_table}.php");
				if (!($t_table = new $vs_table)) { continue; }
				$vs_pk = $t_table->primaryKey();
				$vn_table_num = $t_table->tableNum();
				
				// Create root ca_relationship_types row for table
				$t_root = new ca_relationship_types();
				if (!$t_root->load(array('type_code' => 'root_for_table_'.$vn_table_num))) {
					$t_root->setMode(ACCESS_WRITE);
					$t_root->set('table_num', $vn_table_num);
					$t_root->set('type_code', 'root_for_table_'.$vn_table_num);
					$t_root->set('rank', 1);
					$t_root->set('is_default', 0);
					$t_root->set('parent_id', null);
					$t_root->insert();
					
					if ($t_root->numErrors()) {
						$this->opa_error_messages[] = _t("Could not create root for relationship %1: %2", $vs_table,join('; ', $t_root->getErrors()));
						continue;
					}
					$t_root->addLabel(
						array(
							'typename' => 'Root for table '.$vn_table_num,
							'typename_reverse' => 'Root for table '.$vn_table_num
						), $pn_locale_id, null, true
					);
					if ($t_root->numErrors()) {
						$this->opa_error_messages[] = _t("Could not add label to root for relationship %1: %2", $vs_table,join('; ', $t_root->getErrors()));
					}
				}
				
				$vn_root_id = $t_root->getPrimaryKey();
				
				// Move existing types under root
				$qr_types = $o_db->query("
					UPDATE ca_relationship_types
					SET parent_id = ?, hier_type_id = ?
					WHERE
						(table_num = ?) AND (type_id <> ?)
				", (int)$vn_root_id, (int)$vn_root_id, (int)$vn_table_num, (int)$vn_root_id);
			}
			
			$t_root->rebuildAllHierarchicalIndexes();
			
			return (sizeof($this->opa_error_messages)) ? false : true;
		}
		# -------------------------------------------------------
	}
?>