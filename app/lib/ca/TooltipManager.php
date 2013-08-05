<?php
/** ---------------------------------------------------------------------
 * TooltipManager.php : class to control loading of tooltips
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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

	class TooltipManager {
		# --------------------------------------------------------------------------------
		private static $opa_tooltips;
		private static $opa_namespace_classes = array();
		# --------------------------------------------------------------------------------
		/**
		 * Initialize 
		 *
		 * @return void
		 */
		static function init() {
			TooltipManager::$opa_tooltips = array();
		}
		# --------------------------------------------------------------------------------
		/**
		 * Add tooltip to current response.
		 *
		 * @param string $ps_dom_selector CSS-stlye DOM element selector of the element(s) to attach the tooltip to
		 * @param string $ps_content HTML content for the tooltip to display
		 * @param string $ps_namespace Optional namespace specifier; allows you to group tooltip code and output different tool tips at different times in the request cycle
		 * @return (bool) - Returns true if tooltip was successfully added, false if not
		 */
		static function add($ps_dom_selector, $ps_content, $ps_namespace='default') {
			if (!is_array(TooltipManager::$opa_tooltips)) { TooltipManager::init(); }
			if (!$ps_dom_selector) { return false; }
			if (!trim($ps_namespace)) { $ps_namespace = 'default'; }
			
			TooltipManager::$opa_tooltips[$ps_namespace][$ps_dom_selector] = $ps_content;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Clears all currently set tooltips from response
		 *
		 * @return void
		 */
		static function clearAll() {
			TooltipManager::$opa_tooltips = array();
		}
		# --------------------------------------------------------------------------------
		/**
		 * 
		 *
		 * @return void
		 */
		static function setNamespaceCSSClass($ps_namespace, $ps_class) {
			TooltipManager::$opa_namespace_classes[$ps_namespace] = $ps_class;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns HTML <script> block setting tooltips for response
		 *
		 * @param string $ps_namespace  Optional namespace specifier; allows you to group tooltip code and output different tool tips at different times in the request cycle
		 * @return string HTML <script> block setting up tooltips
		 */
		static function getLoadHTML($ps_namespace='default') {
			if (!$ps_namespace) { $ps_namespace = 'default'; }
			$vs_buf = '';
			if (!is_array(TooltipManager::$opa_tooltips)) { TooltipManager::init(); }
			if (isset(TooltipManager::$opa_tooltips[$ps_namespace]) && sizeof(TooltipManager::$opa_tooltips[$ps_namespace])) {
				$vs_buf = "<script type='text/javascript'>\njQuery(document).ready(function() {\n";
			
				foreach(TooltipManager::$opa_tooltips[$ps_namespace] as $vs_id => $vs_content) {
					$vs_class = (isset(TooltipManager::$opa_namespace_classes[$ps_namespace]) && TooltipManager::$opa_namespace_classes[$ps_namespace]) ? TooltipManager::$opa_namespace_classes[$ps_namespace] : "tooltipFormat";
					$vs_buf .= "jQuery('{$vs_id}').tooltip({ tooltipClass: '{$vs_class}', show: 150, hide: 150, items: '*', content: function() { return '".preg_replace('![\n\r]{1}!', ' ', addslashes($vs_content))."'; }});";
				}
				
				$vs_buf .= "});\n</script>\n";
				
			}
			return $vs_buf;
		}
		# --------------------------------------------------------------------------------
	}
?>