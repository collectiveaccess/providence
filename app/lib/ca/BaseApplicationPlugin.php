<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseApplicationPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/ca/IApplicationPlugin.php');
 
	abstract class BaseApplicationPlugin implements IApplicationPlugin {
		# -------------------------------------------------------
		protected $description = '';
		# -------------------------------------------------------
		public function __construct() {
		
		}
		# -------------------------------------------------------
		/**
		 * Get request object for current request. Returns null if no request is available 
		 * (if, for example, the plugin is being run in a batch script - scripts don't use the request/response model)
		 *
		 * @return Request object or null if no request object is available
		 */
		public function getRequest() {
			if (($o_app = AppController::getInstance()) && ($o_req = $o_app->getRequest())) {
				return $o_req;	
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns description of the current plugin. So long as your plugin sets its description property
		 * you shouldn't need to override this method.
		 *
		 * @return string description of plugin for display to end-users
		 */
		public function getDescription() {
			return isset($this->description) ? $this->description : '';
		}
		# -------------------------------------------------------
		/**
		 * Returns current status of plugin. Your plugin needs to override this. The default
		 * implementation returns a status without errors but with the 'available' flag set to false (ie. plug isn't functional)
		 *
		 * @return array associative array indicating availability of plugin, and any initialization errors and warnings. Also includes text description of plugin for display.
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => false
			);
		}
		# -------------------------------------------------------
	}
?>