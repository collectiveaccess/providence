<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/PDFRenderer/domPDF.php : renders HTML as PDF using domPDF
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
 * @subpackage Print
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Plugins/PDFRenderer/BasePDFRendererPlugin.php");
require_once(__CA_LIB_DIR__.'/core/Parsers/dompdf/dompdf_config.inc.php');

class WLPlugPDFRendererdomPDF Extends BasePDFRendererPlugIn Implements IWLPlugPDFRenderer {
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
		
		$this->description = _t('Renders HTML as PDF using domPDF');
		
		$this->renderer = new DOMPDF();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function render($ps_content, $pa_options=null) {
		$this->renderer->load_html($ps_content);
		$this->renderer->render();
		
		if (caGetOption('stream', $pa_options, false)) {
			$this->renderer->stream(caGetOption('filename', $pa_options, 'export_results.pdf'));
			return true;
		}
		
		return $this->renderer->output();
	}
	# ------------------------------------------------
	/**
	 *
	 */	
	public function renderFile($ps_file_path, $pa_options=null) {
		$this->renderer->load_html_file($load_html_file);
		
		$this->renderer->render();
		
		if (caGetOption('stream', $pa_options, false)) {
			$this->renderer->stream(caGetOption('filename', $pa_options, 'export_results.pdf'));
			return true;
		}
		
		return $this->renderer->output();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function setBasePath($ps_directory_path) {
		if(!is_dir($ps_directory_path)) { return false; }
		$this->renderer->set_base_path($ps_directory_path);
		
		return true;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function setPaper($ps_size, $ps_orientation) {
		$this->renderer->set_paper($ps_size, $ps_orientation);
		
		return true;
	}
	# ------------------------------------------------
}