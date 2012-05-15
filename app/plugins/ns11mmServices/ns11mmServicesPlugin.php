<?php
/* ----------------------------------------------------------------------
 * ns11mmServicesPlugin.php : 
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
	
	class ns11mmServicesPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $opn_last_update_timestamp;
		private $opn_old_access_value;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Implements Memex services for National September 11th Museum.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/ns11mmServices.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the ns11mmServicesPlugin plugin always initializes ok
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
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_ns11mm_memex_services' => array(
					'label' => _t('Can use NS11mm MEMEX exhibition web services'),
					'description' => _t('User can access MEMEX exhibition web services ')
				)
			);
		}
		
		# -------------------------------------------------------
		/**
		 * Add plugin user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['plugin_ns11mmServices'] = array(
				'label' => _t('NS11mm MEMEX services plugin'),
				'description' => _t('Actions for NS11mm MEMEX services plugin'),
				'actions' => ns11mmServicesPlugin::getRoleActionList()
			);
	
			return $pa_role_list;
		}
		# -------------------------------------------------------
	}
?>