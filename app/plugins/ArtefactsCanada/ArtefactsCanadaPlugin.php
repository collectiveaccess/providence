<?php
/* ----------------------------------------------------------------------
 * app/plugins/ArtefactsCanadaPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 
	class ArtefactsCanadaPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		/**
		 *
		 */
		protected $description = null;
		
		/**
		 *
		 */
		private $opo_config;
		
		/**
		 *
		 */
		private $ops_plugin_path;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->ops_plugin_path = $ps_plugin_path;
			$this->description = _t('Export data to Artefacts Canada');
			
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/artefactsCanada.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the statisticsViewerPlugin always initializes ok... (part to complete)
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
		 * Insert activity menu
		 */
		public function hookRenderMenuBar($pa_menu_bar) {
			if ($o_req = $this->getRequest()) {
				if (!$o_req->user->canDoAction('can_export_artefacts_canada')) { return $pa_menu_bar; }
				if(!(bool)$this->opo_config->get('enabled')) { return $pa_menu_bar; }
				
				if (isset($pa_menu_bar['Export'])) {
					$va_menu_items = $pa_menu_bar['Export']['navigation'];
					if (!is_array($va_menu_items)) { $va_menu_items = array(); }
				} else {
					$va_menu_items = array();
				}
				
				$va_menu_items['ArtefactsCanada_export'] = array(
					'displayName' => _t('Artefacts Canada'),
					"default" => array(
						'module' => 'ArtefactsCanada', 
						'controller' => 'Export', 
						'action' => 'Index'
					)
				);	
				
				if (isset($pa_menu_bar['Export'])) {
					$pa_menu_bar['Export']['navigation'] = $va_menu_items;
				} else {
					$pa_menu_bar['Export'] = array(
						'displayName' => _t('Export'),
						'navigation' => $va_menu_items
					);
				}
			} 
			
			return $pa_menu_bar;
		}
		# -------------------------------------------------------
		/**
		 * Add plugin user actions
		 */
		static function getRoleActionList() {
			return array(
				'can_export_artefacts_canada' => array(
						'label' => _t('Can use Artefacts Canada export functions'),
						'description' => _t('User can export data to Artefacts Canada.')
					)
			);
		}
		# -------------------------------------------------------
	}
