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
		 *
		 */
		private $opo_app_plugin_manager;
		# -------------------------------------------------------
		public function __construct() {
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
		}
		# -------------------------------------------------------
		/**
		 * Return possible commands for CLI caUtils
		 */
		public function getToolCommands() {
			return $this->getToolCommandList();
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function getTools() {
			$va_tools = $this->opo_app_plugin_manager->hookGetToolInstances();
			$va_tool_list = array_shift($va_tools);
			return  $va_tool_list;
		}
		# -------------------------------------------------------
		/**
		 * Return possible commands for CLI caUtils
		 */
		public function getToolCommandList() {
			return $this->opo_app_plugin_manager->hookCLICaUtilsGetCommands();
		}
		# -------------------------------------------------------
	}
?>