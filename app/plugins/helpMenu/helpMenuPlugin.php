<?php
/* ----------------------------------------------------------------------
 * helpMenuPlugin.php : implements editing activity menu - a list of recently edited items
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 
	class helpMenuPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Adds a "help" menu');
			$this->opo_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'helpMenu.conf');

			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the historyMenu plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => true
			);
		}
		# -------------------------------------------------------
		/**
		 * Insert activity menu
		 */
		public function hookRenderMenuBar($pa_menu_bar) {
			if ($o_req = $this->getRequest()) {
				$va_help_topics = array();
					
                $va_activity_menu_list['meow'] = array(
                    'default' => '#',
                    'displayName' => 'zzz',
                    'is_enabled' => 1,
                    'requires' => array(
                        #'action:'.$vs_priv_name => 'OR'
                    ),
                    'parameters' => array(
                       # $va_editor_url_info['_pk'] => $vn_id
                    )
                );
                
                $va_help_topics['test'] = array(
                    'displayName' => 'Testing',
                    'submenu' => array(
                        "type" => 'static',
                        'navigation' => $va_activity_menu_list
                    )
                );
					
				if(sizeof($va_help_topics)) {	// only show history menu if there's some history...
					$va_help_menu = array(
						'displayName' => _t('Help'),
						'navigation' => $va_help_topics
					);
					$pa_menu_bar['help_menu'] = $va_help_menu;
				}
			}
			return $pa_menu_bar;
		}
		# -------------------------------------------------------
	}
