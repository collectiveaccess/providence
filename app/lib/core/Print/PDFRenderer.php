<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Print/PDFRenderer.php : 
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
 * @subpackage Dashboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
	require_once(__CA_LIB_DIR__.'/core/Parsers/dompdf/dompdf_config.inc.php');
 	
 	class PDFRenderer {
 		# --------------------------------------------------------------------------------
 		/**
 		 * 
 		 */
 		private $renderer = null;
 		
 		/**
 		 *
 		 */
 		static $s_plugin_codes;
 		
 		# --------------------------------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function __construct($ps_plugin_code=null) {
 			$this->renderer = null;
 			
 			$va_available_plugins = PDFRenderer::getAvailablePDFRendererPlugins();
 			if ($ps_plugin_code && in_array($ps_plugin_code, $va_available_plugins)) {
 				$this->renderer = $this->getPDFRendererPlugin($ps_plugin_code);
 			}
 			if (!$this->renderer) {
 				foreach($va_available_plugins as $vs_plugin_code) {
 					if ($o_renderer = $this->getPDFRendererPlugin($vs_plugin_code)) {
 						$va_status = $o_renderer->checkStatus();
 						if (caGetOption('available', $va_status, false)) { 
 							$this->renderer = $o_renderer;
 							break; 
 						}
 					}
 				}
 			}
 		}
 		# --------------------------------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function setPage($ps_size, $ps_orientation, $ps_margin_top="0mm", $ps_margin_right="0mm", $ps_margin_bottom="0mm", $ps_margin_left="0mm") {
 			if (!$this->renderer) { return null; }
 			return $this->renderer->setPage($ps_size, $ps_orientation, $ps_margin_top, $ps_margin_right, $ps_margin_bottom, $ps_margin_left);
 		}
 		# --------------------------------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function render($ps_content, $pa_options=null) {
 			if (!$this->renderer) { return null; }
 			return $this->renderer->render($ps_content, $pa_options);
 		}
 		# --------------------------------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function renderFile($ps_file_path, $pa_options=null) {
 			if (!$this->renderer) { return null; }
 			return $this->renderer->render($ps_file_path, $pa_options);
 		}
 		# --------------------------------------------------------------------------------
		/**
		 * Returns list of available visualization plugins
		 *
		 * @return array
		 */
		public static function getAvailablePDFRendererPlugins() {
			if (is_array(PDFRenderer::$s_plugin_codes)) { return PDFRenderer::$s_plugin_codes; }
			
			PDFRenderer::$s_plugin_codes = array();
			$r_dir = opendir(__CA_LIB_DIR__.'/core/Plugins/PDFRenderer');
			while (($vs_plugin = readdir($r_dir)) !== false) {
				if ($vs_plugin == "BasePDFRendererPlugin.php") { continue; }
				if (preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*).php$/", $vs_plugin, $va_matches)) {
					PDFRenderer::$s_plugin_codes[] = $va_matches[1];
				}
			}
		
			sort(PDFRenderer::$s_plugin_codes);
			return PDFRenderer::$s_plugin_codes;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns instance of specified plugin, or null if the plugin does not exist
		 *
		 * @param string $ps_plugin_code Unique code for plugin. The code is the same as the plugin's filename minus the .php extension.
		 *
		 * @return WLPlug BasePDFRendererPlugin Plugin instance
		 */
		public function getPDFRendererPlugin($ps_plugin_code) {
			if (preg_match('![^A-Za-z0-9_\-]+!', $ps_plugin_code)) { return null; }
			if (!file_exists(__CA_LIB_DIR__."/core/Plugins/PDFRenderer/{$ps_plugin_code}.php")) { return null; }
		
			require_once(__CA_LIB_DIR__."/core/Plugins/PDFRenderer/{$ps_plugin_code}.php");
			$vs_plugin_classname = "WLPlugPDFRenderer{$ps_plugin_code}";
			return new $vs_plugin_classname;
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		public function getCurrentRendererCode() {
			if (!$this->renderer) { return null; }
			return $this->renderer->get('CODE');
		}
		# --------------------------------------------------------------------------------
		# Utilities
		# --------------------------------------------------------------------------------
		/** 
		 *
		 */
		static public function getPageSize($ps_size, $ps_units='mm') {
			$va_page_size =	CPDF_Adapter::$PAPER_SIZES[$ps_size];
			$vn_page_width = caConvertMeasurement(($va_page_size[2] - $va_page_size[0]).'px', $ps_units);
			$vn_page_height = caConvertMeasurement(($va_page_size[3] - $va_page_size[1]).'px', $ps_units);
			
			return array('width' => $vn_page_width, 'height' => $vn_page_height);
		}
		# --------------------------------------------------------------------------------
		/** 
		 *
		 */
		static public function isValidPageSize($ps_size) {
			return array_key_exists(strtolower($ps_size), CPDF_Adapter::$PAPER_SIZES);
		}
		# --------------------------------------------------------------------------------
		/** 
		 *
		 */
		static public function isValidOrientation($ps_orientation) {
			return in_array(strtolower($ps_orientation), array('portrait', 'landscape'));
		}
		# --------------------------------------------------------------------------------
 	}