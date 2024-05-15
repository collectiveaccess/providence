<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/PDFRenderer/domPDF.php : renders HTML as PDF using domPDF
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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

use Dompdf\Dompdf;
use Dompdf\Options;

class WLPlugPDFRendererdomPDF Extends BasePDFRendererPlugin Implements IWLPlugPDFRenderer {
	# ------------------------------------------------
	/** 
	 *
	 */
	private $renderer;
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'domPDF';
		$this->set('CODE', 'domPDF');
		
		$this->description = _t('Renders HTML as PDF using domPDF');
		
		$chroot = [realpath(__CA_BASE_DIR__), realpath(__CA_BASE_DIR__.'/media'), realpath(__CA_BASE_DIR__.'/media/'.__CA_APP_NAME__)];
		if (($chroot_opt = Configuration::load()->get('dompdf_chroot_path'))) {
			$chroot[] = realpath($chroot_opt);
		}
		
		$options = new Options();
		$options->set('isRemoteEnabled', TRUE);
		$options->set('chroot', $chroot);
		$options->set('logOutputFile', __CA_APP_DIR__.'/tmp/log.htm');
    	$options->set('tempDir', __CA_APP_DIR__.'/tmp');
    	
    	// Look for theme and app-based font directories
    	if(file_exists(__CA_THEME_DIR__.'/fonts')) {
    		$options->set('fontDir', __CA_THEME_DIR__.'/fonts');
    	} elseif(file_exists(__CA_APP_DIR__.'/fonts')) {
    		$options->set('fontDir', __CA_APP_DIR__.'/fonts');
    	}
		$this->renderer = new DOMPDF($options);
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
	 * @seealso domPDF::renderFile()
	 */
	public function render($ps_content, $pa_options=null) {
		$this->renderer->load_html($ps_content);
		
		$this->renderer->render();
		
		if (caGetOption('stream', $pa_options, false)) {
			$this->renderer->stream(caGetOption('filename', $pa_options, 'export_results.pdf'));
		}
		
		$output = $this->renderer->output();
		if($path = caGetOption('writeFile', $pa_options, false)) {
			file_put_contents($path, $output);
		}
		
		return $this->renderer->output();
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
	 * @seealso domPDF::render()
	 */
	public function renderFile($ps_file_path, $pa_options=null) {
		$this->renderer->load_html_file($ps_file_path);
		
		$this->renderer->render();
		
		if (caGetOption('stream', $pa_options, false)) {
			$this->renderer->stream(caGetOption('filename', $pa_options, 'output.pdf'));
		}
		
		$output = $this->renderer->output();
		if($path = caGetOption('writeFile', $pa_options, false)) {
			file_put_contents($path, $output);
		}
		
		return $output;
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
		$this->renderer->set_paper($size, $orientation);
		
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
		
		$config = Configuration::load();
		$use = $config->get('use_pdf_renderer');
		
		$wkhtmltopdf = ((!strlen($use) || (strtolower($use) === 'wkhtmltopdf')) && caWkhtmltopdfInstalled());
		$dompdf = (!strlen($use) || (strtolower($use) === 'dompdf'));
		
		if (!$wkhtmltopdf) {
			$status['available'] = $dompdf;
		} else {
			$status['available'] = false;
			if ($wkhtmltopdf) {
				$status['unused'] = true;
				$status['warnings'][] = _t("Didn't load because wkhtmltopdf is available and preferred");
			} 
		}
		
		return $status;
	}
	# ------------------------------------------------
}
