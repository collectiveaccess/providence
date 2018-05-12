<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BasePluginController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  
 	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	
	class BasePluginController extends ActionController {
		# -------------------------------------------------------
		/**
		 * Application datamodel
		 */
 		protected $datamodel;
 		
 		/**
 		 * Plugin configuration
 		 */
		protected $config;
		
 		/**
 		 * Application configuration
 		 */
		protected $appConfig;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			$this->datamodel = Datamodel::load();
 			
 			$this->appConfig = Configuration::load();
 			
 			$vs_plugin_name = $po_request->getModulePath();
 			
 			// Load plugin config
 			$this->config = Configuration::load(__CA_APP_DIR__."/plugins/{$vs_plugin_name}/conf/{$vs_plugin_name}.conf");
 		
 			// Load plugin view paths
 			if (!is_array($pa_view_paths)) { $pa_view_paths = array(); }
 			
 			// Paths are evaluated back-to-front; we first look in a directory with the name of the plugin in the current theme. If
 			// we don't find a view there then we fall back to the old locations in the plugin itself (themes/<theme name>/views and themes/default/views)
 			$pa_view_paths[] = __CA_APP_DIR__."/plugins/{$vs_plugin_name}/themes/default/views";
 			$pa_view_paths[] = __CA_APP_DIR__."/plugins/{$vs_plugin_name}/themes/".__CA_THEME__."/views";
 			if (defined("__CA_THEME_DIR__")) {
 				$pa_view_paths[] = __CA_THEME_DIR__."/views/{$vs_plugin_name}";
 			}
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
	}