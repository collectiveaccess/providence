<?php
/* ----------------------------------------------------------------------
 * aboutDrawingServicesPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
	
	class aboutDrawingServicesPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Implements search servicesfor About Drawing.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/aboutDrawingServices.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the aboutDrawingServicesPlugin plugin always initializes ok
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
				'can_use_about_drawing_search_service' => array(
					'label' => "Can use AboutDrawing JSON search service",
					'description' => "Allow user to use About Drawing JSON search service"
				),
			);
		}

		# -------------------------------------------------------
		/**
		 * Add plugin user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['plugin_aboutDrawingServicesPlugin'] = array(
				'label' => _t('About Drawing services plugin'),
				'description' => _t('Actions for About Drawing plugin'),
				'actions' => aboutDrawingServicesPlugin::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
	}
