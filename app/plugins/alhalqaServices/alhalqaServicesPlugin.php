<?php
/* ----------------------------------------------------------------------
 * alhalqaServicesPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
	
	class alhalqaServicesPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Implements community services for Alhalqa Virtual.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/alhalqaServices.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the alhalqaServicesPlugin plugin always initializes ok
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
				'can_use_alhalqa_community_service' => array(
					'label' => "Can use Alhalqa JSON community service",
					'description' => "Allow user to use Alhalqa JSON community service"
				),
			);
		}

		# -------------------------------------------------------
		/**
		 * Add plugin user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['plugin_alhalqaServicesPlugin'] = array(
				'label' => _t('Alhalqa services plugin'),
				'description' => _t('Actions for Alhalqa plugin'),
				'actions' => alhalqaServicesPlugin::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
	}
