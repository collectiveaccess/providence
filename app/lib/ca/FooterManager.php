<?php
/** ---------------------------------------------------------------------
 * FooterManager.php : class to control loading of page footer
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 * @subpackage Misc
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	class FooterManager {
		# --------------------------------------------------------------------------------
		private static $opa_code;
		# --------------------------------------------------------------------------------
		/**
		 * Initialize 
		 *
		 * @return void
		 */
		static function init() {
			FooterManager::$opa_code = array();
		}
		# --------------------------------------------------------------------------------
		/**
		 * Add content to current response footer.
		 *
		 * @param string $ps_content HTML content to insert into footer
		 * @return (bool) - Returns true if content was successfully added, false if not
		 */
		static function add($ps_content) {
			if (!is_array(FooterManager::$opa_code)) { FooterManager::init(); }
			
			FooterManager::$opa_code[] = $ps_content;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Clears all currently set tooltips from response
		 *
		 * @return void
		 */
		static function clearAll() {
			FooterManager::$opa_code = array();
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns HTML <script> block setting tooltips for response
		 *
		 * @param string $ps_namespace  Optional namespace specifier; allows you to group tooltip code and output different tool tips at different times in the request cycle
		 * @return string HTML <script> block setting up tooltips
		 */
		static function getLoadHTML() {
			$vs_buf = '';
			if (!is_array(FooterManager::$opa_code)) { FooterManager::init(); }
			if (isset(FooterManager::$opa_code) && is_array(FooterManager::$opa_code) && sizeof(FooterManager::$opa_code)) {
				$vs_buf = join("\n", FooterManager::$opa_code);
			}
			return $vs_buf;
		}
		# --------------------------------------------------------------------------------
	}
?>