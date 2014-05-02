<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseToolsPlugin.php : 
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
 * @package CollectiveAccess
 * @subpackage AppPlugin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__.'/ca/BaseApplicationPlugin.php');
 
	abstract class BaseToolsPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		/**
		 * Plugin description
		 */
		protected $description = '';
		
		/**
		 * Instance of tool this plugin wraps
		 */
		protected $tool;
		
		/**
		 * Title of tool. This should be formatted for display and unique to the tool.
		 */
		protected $tool_title = 'NO_CLASS_SET';
		
		/**
		 * Plugin configuration
		 */
		protected $config;
		
		# -------------------------------------------------------
		public function __construct() {
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function hookGetToolInstances(&$pa_instances) {
			if(!is_array($pa_instances['instances'])) { $pa_instances['instances'] = array(); }
			$pa_instances['instances'][$this->tool_title] = $this->tool;
			
			return $pa_instances;
		}
		# -------------------------------------------------------
		/**
		 * Return possible commands for CLI caUtils
		 */
		public function hookCLICaUtilsGetCommands(&$pa_commands) {
			if (!$this->tool) { return null; }
			
			$va_settings = $this->tool->getAvailableSettings();
			
			$va_options = array();
			$vn_i = 1;
			foreach($va_settings as $vs_setting => $va_setting_info) {
				$va_options["{$vs_setting}|{$vn_i}-".(($va_setting_info['formatType'] == FT_NUMBER) ? 'n' : 's')] = $va_setting_info['description'];
				$vn_i++;
			}
			
			foreach($this->tool->getCommands() as $vs_command) {
				$pa_commands[$this->tool_title][$vs_command] = array(
					'Command' => $vs_command,
					'ShortHelp' => $this->tool->getShortHelpText($vs_command),
					'Help' => $this->tool->getHelpText($vs_command),
					'Options' => $va_options
				);
			}
			
			return $pa_commands;
		}
		# -------------------------------------------------------
		/**
		 * Run commands from CLI caUtils
		 */
		public function hookCLICaUtilsGetToolWithSettings(&$pa_params) {
			if ($pa_params[0] == $this->tool_title) {
				$this->tool->setSettings($pa_params[1]);
				$this->tool->setMode($pa_params[2]);
				
				$pa_params['tool'] = $this->tool;
			}  
			return $pa_params;
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			if ($this->config) {
				$vb_enabled = (bool)$this->config->get('enabled');
			} else {
				$vb_enabled = true;
			}
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => $vb_enabled
			);
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