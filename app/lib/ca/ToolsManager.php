<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ToolsManager.php : 
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
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");

 
	class ToolsManager {
		# -------------------------------------------------------
		/**
		 * Instance of application plugin manager, used to query available tools
		 */
		private $opo_app_plugin_manager;
		# -------------------------------------------------------
		/**
		 * Constructor
		 */
		public function __construct() {
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
		}
		# -------------------------------------------------------
		/**
		 * Return possible commands for CLI caUtils
		 *
		 * @return array
		 */
		public function getToolCommands() {
			return $this->getToolCommandList();
		}
		# -------------------------------------------------------
		/**
		 * Return list of availble tools
		 *
		 * @return array List of available tools
		 */
		public function getTools() {
			$va_tools = $this->opo_app_plugin_manager->hookGetToolInstances();
			if(!is_array($va_tools) || !sizeof($va_tools)) { return array(); }
			$va_tool_list = array_shift($va_tools);
			return  $va_tool_list;
		}
		# -------------------------------------------------------
		/**
		 * Return number of available tools
		 *
		 * @return int Number of tools
		 */
		public function getToolCount() {
			return sizeof($this->getTools());
		}
		# -------------------------------------------------------
		/**
		 * Return instance of tool
		 *
		 * @param string $ps_tool_name The name or identifier of the tool
		 * @return BaseApplicationTool Instance of tool or null if name/identifier is invalid
		 */
		public function getTool($ps_tool_name) {
			$va_tool_list = $this->getTools();
			
			// Get by name
			if (isset($va_tool_list[$ps_tool_name])) {
				return $va_tool_list[$ps_tool_name];
			}
			
			// Get by identifier
			foreach($va_tool_list as $vs_tool_name => $o_tool) {
				if ($o_tool->getToolIdentifier() == $ps_tool_name) {
					return $o_tool;
				}
			}
			
			return null;	// Bad tool
		}
		# -------------------------------------------------------
		/**
		 * Return possible commands for CLI caUtils
		 *
		 * @return array List of commands grouped by tool
		 */
		public function getToolCommandList() {
			return $this->opo_app_plugin_manager->hookCLICaUtilsGetCommands();
		}
		# -------------------------------------------------------
	}
?>