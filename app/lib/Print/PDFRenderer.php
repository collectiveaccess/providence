<?php
/** ---------------------------------------------------------------------
 * app/lib/Print/PDFRenderer.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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
  use Dompdf\Dompdf;
 	
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
 		
 		/**
 		 *
 		 */
 		static $WLPDFRenderer_plugin_cache;
 		
 		/**
 		 *
 		 */
 		static $PAPER_SIZES = array(
                "4a0" => array(0,0,4767.87,6740.79),
                "2a0" => array(0,0,3370.39,4767.87),
                "a0" => array(0,0,2383.94,3370.39),
                "a1" => array(0,0,1683.78,2383.94),
                "a2" => array(0,0,1190.55,1683.78),
                "a3" => array(0,0,841.89,1190.55),
                "a4" => array(0,0,595.28,841.89),
                "a5" => array(0,0,419.53,595.28),
                "a6" => array(0,0,297.64,419.53),
                "a7" => array(0,0,209.76,297.64),
                "a8" => array(0,0,147.40,209.76),
                "a9" => array(0,0,104.88,147.40),
                "a10" => array(0,0,73.70,104.88),
                "b0" => array(0,0,2834.65,4008.19),
                "b1" => array(0,0,2004.09,2834.65),
                "b2" => array(0,0,1417.32,2004.09),
                "b3" => array(0,0,1000.63,1417.32),
                "b4" => array(0,0,708.66,1000.63),
                "b5" => array(0,0,498.90,708.66),
                "b6" => array(0,0,354.33,498.90),
                "b7" => array(0,0,249.45,354.33),
                "b8" => array(0,0,175.75,249.45),
                "b9" => array(0,0,124.72,175.75),
                "b10" => array(0,0,87.87,124.72),
                "c0" => array(0,0,2599.37,3676.54),
                "c1" => array(0,0,1836.85,2599.37),
                "c2" => array(0,0,1298.27,1836.85),
                "c3" => array(0,0,918.43,1298.27),
                "c4" => array(0,0,649.13,918.43),
                "c5" => array(0,0,459.21,649.13),
                "c6" => array(0,0,323.15,459.21),
                "c7" => array(0,0,229.61,323.15),
                "c8" => array(0,0,161.57,229.61),
                "c9" => array(0,0,113.39,161.57),
                "c10" => array(0,0,79.37,113.39),
                "ra0" => array(0,0,2437.80,3458.27),
                "ra1" => array(0,0,1729.13,2437.80),
                "ra2" => array(0,0,1218.90,1729.13),
                "ra3" => array(0,0,864.57,1218.90),
                "ra4" => array(0,0,609.45,864.57),
                "sra0" => array(0,0,2551.18,3628.35),
                "sra1" => array(0,0,1814.17,2551.18),
                "sra2" => array(0,0,1275.59,1814.17),
                "sra3" => array(0,0,907.09,1275.59),
                "sra4" => array(0,0,637.80,907.09),
                "letter" => array(0,0,612.00,792.00),
                "legal" => array(0,0,612.00,1008.00),
                "ledger" => array(0,0,1224.00, 792.00),
                "tabloid" => array(0,0,792.00, 1224.00),
                "executive" => array(0,0,521.86,756.00),
                "folio" => array(0,0,612.00,936.00),
                "commercial #10 envelope" => array(0,0,684,297),
                "catalog #10 1/2 envelope" => array(0,0,648,864),
                "4x6"  => array(0,0,288.00, 432.00),
                "8.5x11" => array(0,0,612.00,792.00),
                "8.5x14" => array(0,0,612.00,1008.0),
                "11x17"  => array(0,0,792.00, 1224.00),
              );
 		
 		# --------------------------------------------------------------------------------
 		/**
 		 * @param $ps_plugin_code string Code of plugin to use for rendering. If omitted the first available plugin is used.
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
 		 * Set page size, orientation and margins
 		 *
 		 * @param string $ps_size A valid page size (eg. A4, letter, legal)
 		 * @param string $ps_orientation A valid page orientation (eg. portrait, landscape)
 		 * @param string $ps_margin_top Top margin page with units (eg. 10mm) [Default=0mm]
 		 * @param string $ps_margin_right Right page margin with units (eg. 10mm) [Default=0mm]
 		 * @param string $ps_margin_bottom Bottom page margin with units (eg. 10mm) [Default=0mm]
 		 * @param string $ps_margin_left Left page margin with units (eg. 10mm) [Default=0mm]
 		 *
 		 * @return bool True on success
 		 */
 		public function setPage($ps_size, $ps_orientation, $ps_margin_top="0mm", $ps_margin_right="0mm", $ps_margin_bottom="0mm", $ps_margin_left="0mm") {
 			if (!$this->renderer) { return null; }
 			return $this->renderer->setPage($ps_size, $ps_orientation, $ps_margin_top, $ps_margin_right, $ps_margin_bottom, $ps_margin_left);
 		}
 		# --------------------------------------------------------------------------------
 		/**
 		 * Render HTML string as PDF
 		 *
 		 * @param string $ps_content  Valid HTML to render
 		 * @param array $pa_options Options include:
 		 *		stream = Send PDF output directly to browser [default=false]
 		 *		filename = If streaming, set filename of PDF [default=output.pdf]
 		 *
 		 * @return string PDF content
 		 */
 		public function render($ps_content, $pa_options=null) {
 			if (!$this->renderer) { return null; }
 			return $this->renderer->render($ps_content, $pa_options);
 		}
 		# --------------------------------------------------------------------------------
 		/**
 		 * Render HTML file as PDF
 		 *
 		 * @param string $ps_file_path  Path to valid HTML file
 		 * @param array $pa_options Options include:
 		 *		stream = Send PDF output directly to browser [default=false]
 		 *		filename = If streaming, set filename of PDF [default=output.pdf]
 		 *
 		 * @return string PDF content
 		 */
 		public function renderFile($ps_file_path, $pa_options=null) {
 			if (!$this->renderer) { return null; }
 			return $this->renderer->render($ps_file_path, $pa_options);
 		}
 		# --------------------------------------------------------------------------------
		/**
		 * Returns list of available PDF rendering plugins
		 *
		 * @return array
		 */
		public static function getAvailablePDFRendererPlugins() {
			if (is_array(PDFRenderer::$s_plugin_codes)) { return PDFRenderer::$s_plugin_codes; }
			
			PDFRenderer::$s_plugin_codes = array();
			$r_dir = opendir(__CA_LIB_DIR__.'/Plugins/PDFRenderer');
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
			if (!file_exists(__CA_LIB_DIR__."/Plugins/PDFRenderer/{$ps_plugin_code}.php")) { return null; }
		
			require_once(__CA_LIB_DIR__."/Plugins/PDFRenderer/{$ps_plugin_code}.php");
			$vs_plugin_classname = "WLPlugPDFRenderer{$ps_plugin_code}";
			return new $vs_plugin_classname;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Get identifying code for currently loaded PDF renderer
		 *
		 * @return string
		 */
		public function getCurrentRendererCode() {
			if (!$this->renderer) { return null; }
			return $this->renderer->get('CODE');
		}
		# ----------------------------------------------------------
		/**
		 * Return status of plugin
		 *
		 * @param string $ps_plugin_code
		 *
		 * @return array List of information about status of specified plugin
		 */
		public function checkPluginStatus($ps_plugin_code) {
			if ($p = $this->getPDFRendererPlugin($ps_plugin_code)) {
				return $p->checkStatus();
			}
			return null;
		}
		# --------------------------------------------------------------------------------
		# Utilities
		# --------------------------------------------------------------------------------
		/** 
		 * Get page width and height for a specific page type
		 *
		 * @param string $ps_size A valid page size (eg. A4, letter, legal)
		 * @param string $ps_units Units to return measurements in (eg. mm, cm, in)
		 * @param string $ps_orientation Orientation of page (eg. portrait, landscape)
		 *
		 * @return array Array with width and height keys
		 */
		static public function getPageSize($ps_size, $ps_units='mm', $ps_orientation='portrait') {
			$ps_orientation = strtolower($ps_orientation);
			if (!PDFRenderer::isValidOrientation($ps_orientation)) { $ps_orientation='portrait'; }
			
			$va_page_size =	self::$PAPER_SIZES[$ps_size];
			$vn_page_width = caConvertMeasurement(($va_page_size[2] - $va_page_size[0]).'px', $ps_units);
			$vn_page_height = caConvertMeasurement(($va_page_size[3] - $va_page_size[1]).'px', $ps_units);
			
			return ($ps_orientation == 'portrait') ? array('width' => $vn_page_width, 'height' => $vn_page_height) : array('width' => $vn_page_height, 'height' => $vn_page_width);
		}
		# --------------------------------------------------------------------------------
		/** 
		 * Determines if a string is a valid page size
		 *
		 * @param string $ps_size A page size (eg. A4, letter, legal)
		 *  
		 * @return bool True if page size is valid and has associated measurements
		 */
		static public function isValidPageSize($ps_size) {
			return array_key_exists(strtolower($ps_size), self::$PAPER_SIZES);
		}
		# --------------------------------------------------------------------------------
		/** 
		 * Determines if string is valid page orientation
		 *
		 * @param string $ps_orientation Orientation of page (eg. portrait, landscape);
		 *
		 * @return bool True if orientation is valid
		 */
		static public function isValidOrientation($ps_orientation) {
			return in_array(strtolower($ps_orientation), array('portrait', 'landscape'));
		}
		# --------------------------------------------------------------------------------
 	}
