<?php
/* ----------------------------------------------------------------------
 * idnoArchiverPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 
	class idnoArchiverPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Archives past identifier values.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/idnoArchiver.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the idnoArchiverPlugin plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Generate title on save
		 */
		public function hookSaveItem(&$pa_params) {
			// get table name and value
			if ($t_instance = $pa_params['instance']) {
				$vs_table_name = $t_instance->tableName();
				
				$va_archive_settings = $this->opo_config->getAssoc('archive');
				
				if (isset($va_archive_settings[$vs_table_name]) && is_array($va_archive_settings[$vs_table_name])) {
					// ok we can archive...
					if ($vs_idno_field = $t_instance->getProperty('ID_NUMBERING_ID_FIELD')) {
						$vs_old_idno = $t_instance->getOriginalValue($vs_idno_field);
		 				$vs_new_idno = $t_instance->get($vs_idno_field);
		 				
		 				if ($vs_old_idno != $vs_new_idno) {
		 					$t_list = new ca_lists();
		 					
		 					$vn_item_id = null;
		 					if (isset($va_archive_settings[$vs_table_name]['type']) && isset($va_archive_settings[$vs_table_name]['type']['element_code'])) {
		 						$t_element = new ca_metadata_elements();
		 						if ($t_element->load(array('element_code' => $va_archive_settings[$vs_table_name]['type']['element_code']))) {
		 							$vn_item_id = $t_list->getItemIDFromList($t_element->get('list_id'), $va_archive_settings[$vs_table_name]['type']['list_item_idno']);
		 						}
		 					}
		 					
		 					$t_instance->addAttribute(
		 						array(
		 							 $va_archive_settings[$vs_table_name]['value']['element_code'] => $vs_old_idno,
		 							 $va_archive_settings[$vs_table_name]['type']['element_code'] => $vn_item_id,
		 							 $va_archive_settings[$vs_table_name]['notes']['element_code'] => _t('Identifier archived on %1', date('c')),
		 						),  $va_archive_settings[$vs_table_name]['element_code']
		 					);
		 					unset($_REQUEST['form_timestamp']);
		 					$t_instance->update();
		 				}
					}
				}
			}
			return $pa_params;
		}
		
		# -------------------------------------------------------
	}
?>