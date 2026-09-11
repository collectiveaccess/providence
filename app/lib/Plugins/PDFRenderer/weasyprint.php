<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/PDFRenderer/weasyprint.php : renders HTML as PDF using weasyprint
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @subpackage Print
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
include_once(__CA_LIB_DIR__."/Plugins/PDFRenderer/BasePDFRendererPlugin.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");


class WLPlugPDFRendererweasyprint Extends BasePDFRendererPlugin Implements IWLPlugPDFRenderer {
	# ------------------------------------------------
	/**
	 * Path to Weasyprint executable
	 */
	protected $app_path = null;
	
	/** 
	 *
	 */
	private $page_size="letter";
		
	/** 
	 *
	 */
	private $page_orientation="portrait";
	
	/** 
	 *
	 */
	private $margin_top="0mm";
	
	/** 
	 *
	 */
	private $margin_right="0mm";
	
	/** 
	 *
	 */
	private $margin_bottom="0mm";
	
	/** 
	 *
	 */
	private $margin_left="0mm";
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'weasyprint';
		$this->set('CODE', 'weasyprint');
		
		$this->app_path = caGetExternalApplicationPath('weasyprint');
		
		$this->description = _t('Renders HTML as PDF using weasyprint');
	}
	# ------------------------------------------------
	/**
	 * Render HTML formatted string as a PDF
	 *
	 * @param string $ps_content A fully-formed HTML document to render as a PDF
	 * @param array $pa_options Options include:
	 *		stream = Output the rendered PDF directly to the response [Default=false]
	 *		filename = The filename to set the PDF to when streams [Default=export_results.pdf]
	 *		writeFile = File path to write PDF to. [Default=false]
	 *
	 * @return string The rendered PDF content
	 * @seealso weasyprint::renderFile()
	 */
	public function render(string $content, ?array $options=null) {
		$path = caGetOption('writeFile', $options, false);
		
		$tmp_file = caGetTempFileName('weasyprint', 'html');
		file_put_contents($tmp_file, $content);
		$output = $path ?: caGetTempFileName('weasyprint', 'pdf');
		
		$cli = new \CA\Process\CLI();
		$cli->execute($this->app_path , [$p, $output], ['async' => false, 'background' => false]);
		$content = file_get_contents($output);
		
		if(!$path) { @unlink($output); }
		@unlink($tmp_file);
		if(caGetOption('stream', $options, false)) { 
			header("Cache-Control: private");
   			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename=".caGetOption('filename', $options, 'output.pdf'));
			
			print $content;
		}
		
		return $content;
	}
	# ------------------------------------------------
	/**
	 * Render HTML file as a PDF
	 *
	 * @param string $ps_file_path Path to fully-formed HTML file to render as a PDF
	 * @param array $pa_options Options include:
	 *		stream = Output the rendered PDF directly to the response [Default=false]
	 *		filename = The filename to set the PDF to when streams [Default=export_results.pdf]
	 *		writeFile = File path to write PDF to. [Default=false]
	 *
	 * @return string The rendered PDF content
	 * @seealso weasyprint::render()
	 */
	public function renderFile(string $file_path, ?array $options=null) {
		$output = $path ?: caGetTempFileName('weasyprint', 'pdf');
		
		$cli = new \CA\Process\CLI();
		$cli->execute($this->app_path , [$file_path, $output], ['async' => false, 'background' => false]);
		
		$content = file_get_contents($output);
		
		if(!$path) { @unlink($output); }
		@unlink($tmp_file);
		if(caGetOption('stream', $options, false)) { 
			header("Cache-Control: private");
   			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename=".caGetOption('filename', $options, 'output.pdf'));
			
			print $content;
		}
		return $content;
	}
	# ------------------------------------------------
	/**
	 * Set page size and orientation
	 *
	 * @param string Page size (ex. A4, letter, legal)
	 * @param string Page orientation (ex. portrait, landscape)
	 *
	 * @return bool True on success, false if parameters are invalid
	 */
	public function setPage(string $size, string $orientation, $margin_top=0, $margin_right=0, $margin_bottom=0, $margin_left=0) {
		if (PDFRenderer::isCustomPageSize($size)){
			
			$this->page_size = $size;
			$this->page_orientation = $orientation;
			
			$this->margin_top = caConvertMeasurement($margin_top, 'mm').'mm';
			$this->margin_right = caConvertMeasurement($margin_right, 'mm').'mm';
			$this->margin_bottom = caConvertMeasurement($margin_bottom, 'mm').'mm';
			$this->margin_left = caConvertMeasurement($margin_left, 'mm').'mm';
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Returns status of plugin.
	 *
	 * @return array - status info array; 'available' key determines if the plugin should be loaded or not
	 */
	public function checkStatus() {
		$status = parent::checkStatus();
		
		$use_renderer = caUsePDFRenderer();
		
		if ($use_renderer === 'weasyprint') {
			$status['available'] = true;
		} else {
			$status['available'] = false;
			if ($use_renderer) {
				$status['unused'] = true;
				$status['warnings'][] = _t("Didn't load because %1 is available and preferred", $use_renderer);
			} 
		}
		
		return $status;
	}
	# ------------------------------------------------
}
