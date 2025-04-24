<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/PDFRenderer/wkhtmltopdf.php : renders HTML as PDF using wkhtmltopdf
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2024 Whirl-i-Gig
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

class WLPlugPDFRendererwkhtmltopdf Extends BasePDFRendererPlugin Implements IWLPlugPDFRenderer {
	# ------------------------------------------------
	/** 
	 *
	 */
	private $ops_wkhtmltopdf_path;
	
	/** 
	 *
	 */
	private $ops_page_size="letter";
		
	/** 
	 *
	 */
	private $ops_page_orientation="portrait";
	
	/** 
	 *
	 */
	private $ops_margin_top="0mm";
	
	/** 
	 *
	 */
	private $ops_margin_right="0mm";
	
	/** 
	 *
	 */
	private $ops_margin_bottom="0mm";
	
	/** 
	 *
	 */
	private $ops_margin_left="0mm";
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'wkhtmltopdf';
		$this->set('CODE', 'wkhtmltopdf');
		
		$this->description = _t('Renders HTML as PDF using wkhtmltopdf');
		
		$this->ops_wkhtmltopdf_path = caGetExternalApplicationPath('wkhtmltopdf');
	}
	# ------------------------------------------------
	/**
	 * Render HTML formatted string as a PDF
	 *
	 * @param string $content A fully-formed HTML document to render as a PDF
	 * @param array $options Options include:
	 *		stream = Output the rendered PDF directly to the response [Default=false]
	 *		filename = The filename to set the PDF to when streams [Default=output.pdf]
	 *		writeFile = File path to write PDF to. [Default=false]
	 *
	 * @return string The rendered PDF content
	 * @seealso wkhtmltopdf::renderFile()
	 */
	public function render($content, $options=null) {
		global $file_cleanup_list;
		
	    // Force backtrack limit high to allow regex on very large strings
	    // If we don't do this preg_replace will fail silently on large PDFs
	    ini_set('pcre.backtrack_limit', '100000000');
	    
		// Extract header and footer
		$vs_header = preg_match("/<!--BEGIN HEADER-->(.*)<!--END HEADER-->/s", $content, $va_matches) ? $va_matches[1] : '';
		$vs_footer = preg_match("/<!--BEGIN FOOTER-->(.*)<!--END FOOTER-->/s", $content, $va_matches) ? $va_matches[1] : '';
		
		$content = preg_replace("/<!--BEGIN HEADER-->(.*)<!--END HEADER-->/s", "", $content);
		$content = preg_replace("/<!--BEGIN FOOTER-->(.*)<!--END FOOTER-->/s", "", $content);
		
		file_put_contents($vs_content_path = caMakeGetFilePath("wkhtmltopdf", "html"), $content); 
		file_put_contents($vs_header_path = caMakeGetFilePath("wkhtmltopdf", "html"), $vs_header); 
		file_put_contents($vs_footer_path = caMakeGetFilePath("wkhtmltopdf", "html"), $vs_footer);
		$vs_output_path = caMakeGetFilePath("wkhtmltopdf", "pdf", ['useAppTmpDir' => true]);
		if (PDFRenderer::isCustomPageSize($this->ops_page_size)){
			$vs_page_size_arg = '';
			$va_size = PDFRenderer::getPageSize($this->ops_page_size, null, $this->ops_page_orientation);
			foreach($va_size as $vs_name => $vs_value) {
				$vs_page_size_arg .= "--page-$vs_name $vs_value ";
			}
		} else {
			$vs_page_size_arg = "--page-size {$this->ops_page_size}";
		}
		caExec($this->ops_wkhtmltopdf_path." --log-level none --enable-local-file-access  --disable-smart-shrinking --print-media-type --dpi 96 --encoding UTF-8 --margin-top {$this->ops_margin_top} --margin-bottom {$this->ops_margin_bottom} --margin-left {$this->ops_margin_left} --margin-right {$this->ops_margin_right} {$vs_page_size_arg} --orientation {$this->ops_page_orientation} page ".caEscapeShellArg($vs_content_path)." --header-html {$vs_header_path} --footer-html {$vs_footer_path} {$vs_output_path}", $va_output, $vn_return);
		$vs_pdf_content = file_get_contents($vs_output_path);
		if (caGetOption('stream', $options, false)) {
			header("Cache-Control: private");
   			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename=".caGetOption('filename', $options, 'output.pdf'));
			print $vs_pdf_content;
		}
		
		$file_cleanup_list = array_merge($file_cleanup_list, [$vs_content_path, $vs_output_path, $vs_header_path, $vs_footer_path]);
		
		if($path = caGetOption('writeFile', $options, false)) {
			copy($vs_output_path, $path);
		}
		
		return $vs_pdf_content;
	}
	# ------------------------------------------------
	/**
	 * Render HTML file as a PDF
	 *
	 * @param string $file_path Path to fully-formed HTML file to render as a PDF
	 * @param array $options Options include:
	 *		stream = Output the rendered PDF directly to the response [Default=false]
	 *		filename = The filename to set the PDF to when streams [Default=export_results.pdf]
	 *
	 * @return string The rendered PDF content
	 * @seealso wkhtmltopdf::render()
	 */
	public function renderFile($file_path, $options=null) {
		if(!file_exists($file_path)) { return false; }
		$vs_content = file_get_contents($file_path);	
		return $this->render($vs_content, $options);
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
	public function setPage($size, $orientation, $margin_top=0, $margin_right=0, $margin_bottom=0, $margin_left=0) {
		if (!PDFRenderer::isValidPageSize($size) || !PDFRenderer::isValidOrientation($orientation)) {
			return false;
		}
		$this->ops_page_size = $size;
		$this->ops_page_orientation = $orientation;
		
		$this->ops_margin_top = caConvertMeasurement($margin_top, 'mm').'mm';
		$this->ops_margin_right = caConvertMeasurement($margin_right, 'mm').'mm';
		$this->ops_margin_bottom = caConvertMeasurement($margin_bottom, 'mm').'mm';
		$this->ops_margin_left = caConvertMeasurement($margin_left, 'mm').'mm';
		
		return true;
	}
	# ------------------------------------------------
	/**
	 * Returns status of plugin.
	 *
	 * @return array - status info array; 'available' key determines if the plugin should be loaded or not
	 */
	public function checkStatus() {
		$config = Configuration::load();
		$use = $config->get('use_pdf_renderer');

		$status = parent::checkStatus();
		$status['available'] = (!strlen($use) || (strtolower($use) === 'wkhtmltopdf')) && caWkhtmltopdfInstalled();
		
		return $status;
	}
	# ------------------------------------------------
}
