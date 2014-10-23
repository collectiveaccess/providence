<?php
/* ----------------------------------------------------------------------
 * vimeoPlugin.php : 
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
 

	
	class vimeoPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $opn_last_update_timestamp;
		private $opn_old_access_value;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Handles authentication for vimeo media replication');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/vimeo.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the twitterPlugin plugin always initializes ok
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
		 * Insert Vimeo configuration option into "manage" menu
		 */
		public function hookRenderMenuBar($pa_menu_bar) {
			if ($o_req = $this->getRequest()) {
				if (isset($pa_menu_bar['manage'])) {
					$va_menu_items = $pa_menu_bar['manage']['navigation'];
					if (!is_array($va_menu_items)) { $va_menu_items = array(); }
				} else {
					$va_menu_items = array();
				}
				$va_menu_items['vimeo_auth'] = array(
					'displayName' => _t('Vimeo integration'),
					"default" => array(
						'module' => 'vimeo', 
						'controller' => 'Auth', 
						'action' => 'Index'
					)
				);
				
				$pa_menu_bar['manage']['navigation'] = $va_menu_items;
			} 
			return $pa_menu_bar;
		}
		# -------------------------------------------------------
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}

?>
