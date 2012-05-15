<?php
/* ----------------------------------------------------------------------
 * mediaImportPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
	class mediaImportPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $ops_plugin_path;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->ops_plugin_path = $ps_plugin_path;
			$this->description = _t('Provides media import services.');
			parent::__construct();
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/mediaImport.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the ampasFrameImporterPlugin plugin always initializes ok
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
				if (!$o_req->user->canDoAction('can_use_media_import_plugin')) { return true; }
				
				if (isset($pa_menu_bar['mediaImport_menu'])) {
					$va_menu_items = $pa_menu_bar['mediaImport_menu']['navigation'];
					if (!is_array($va_menu_items)) { $va_menu_items = array(); }
				} else {
					$va_menu_items = array();
				}
				$va_menu_items['import_media'] = array(
					'displayName' => _t('Import media...'),
					"default" => array(
						'module' => 'mediaImport', 
						'controller' => 'Import', 
						'action' => 'Index'
					)
				);
				
				$pa_menu_bar['mediaImport_menu'] = array(
					'displayName' => _t('Import'),
					'navigation' => $va_menu_items
				);
			} 
			
			return $pa_menu_bar;
		}
		# -------------------------------------------------------
		/**
		 * Add plugin user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['plugin_mediaImport'] = array(
				'label' => _t('Media Import plugin'),
				'description' => _t('Actions for media import plugin'),
				'actions' => mediaImportPlugin::getRoleActionList()
			);
	
			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_media_import_plugin' => array(
					'label' => _t('Can use media import plugin functions'),
					'description' => _t('User can use all media import plugin functionality, including batch media import.')
				)
			);	
		}
		# -------------------------------------------------------
		/**
		 * Add plugin task queue directories
		 */
		public function hookRegisterTaskQueuePluginDirectories($pa_task_queue_info) {
			$pa_task_queue_info['handler_plugin_directories'][] = $this->ops_plugin_path.'/plugins/TaskQueueHandlers';
			
			return $pa_task_queue_info;
		}
		# -------------------------------------------------------
	}
?>