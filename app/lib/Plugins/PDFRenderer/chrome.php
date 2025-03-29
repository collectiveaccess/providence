<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/PDFRenderer/chrome.php : renders HTML as PDF using domPDF
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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

use Spiritix\Html2Pdf\Converter;
use Spiritix\Html2Pdf\Input\StringInput;
use Spiritix\Html2Pdf\Output\DownloadOutput;
use Spiritix\Html2Pdf\Output\StringOutput;


class WLPlugPDFRendererchrome Extends BasePDFRendererPlugin Implements IWLPlugPDFRenderer {
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
		$this->info['NAME'] = 'Chrome';
		$this->set('CODE', 'chrome');
		
		$this->description = _t('Renders HTML as PDF using Chrome');
		
		
		$this->renderer = null;
	}
	# ------------------------------------------------
	/**
	 * 
	 */
	public function setOptions($converter, $options=null) {
		$converter->setOption('landscape', true);
		
		$converter->setOptions([
			'printBackground' => true,
			'displayHeaderFooter' => true,
			'headerTemplate' => '<p>I am a header</p>',
		]);
		
		$converter->setLaunchOptions([
			  'ignoreHTTPSErrors' => true, 
			  'headless' => false, 
			  'executablePath' => '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', 
			  'args' => [
				'--no-sandbox',
				'--disable-web-security',
				'--font-render-hinting=none',
				'--proxy-server="direct://"',
				'--proxy-bypass-list=*',
				'--media-cache-size=0',
				'--disk-cache-size=0',
				'--disable-application-cache',
				'--disk-cache-dir=/dev/null',
				'--media-cache-dir=/dev/null'
			  ]
		   ]
		);
		return $converter;
	}
	# ------------------------------------------------
	/**
	 * Render HTML formatted string as a PDF
	 *
	 * @param string $content A fully-formed HTML document to render as a PDF
	 * @param array $options Options include:
	 *		stream = Output the rendered PDF directly to the response [Default=false]
	 *		filename = The filename to set the PDF to when streams [Default=export_results.pdf]
	 *		writeFile = File path to write PDF to. [Default=false]
	 *
	 * @return string The rendered PDF content
	 * @seealso chrome::renderFile()
	 */
	public function render($content, $options=null) {
		$stream = caGetOption('stream', $options, false);
		$filename = caGetOption('filename', $options, 'export_results.pdf');
		
		$input = new StringInput();
		$input->setHtml($content);

		$converter = new Converter($input, new StringOutput());
		$this->setOptions($converter);
		

		$output = $converter->convert();

		$pdf =	$output->get();
		if ($stream) {
			print $pdf;
			return;
		}
		
		if($path = caGetOption('writeFile', $options, false)) {
			file_put_contents($path, $pdf);
		}
		
		return $pdf;
	}
	# ------------------------------------------------
	/**
	 * Render HTML file as a PDF
	 *
	 * @param string $file_path Path to fully-formed HTML file to render as a PDF
	 * @param array $options Options include:
	 *		stream = Output the rendered PDF directly to the response [Default=false]
	 *		filename = The filename to set the PDF to when streams [Default=export_results.pdf]
	 *		writeFile = File path to write PDF to. [Default=false]
	 *
	 * @return string The rendered PDF content
	 * @seealso chrome::render()
	 */
	public function renderFile($file_path, $options=null) {
		$content = file_get_contents($file_path);
		
		return $this->render($content, $options);
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
		//$this->renderer->set_paper($size, $orientation);
		
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
		
		$chrome = (!strlen($use) || (strtolower($use) === 'chrome'));
		
		if ($chrome) {
			$status['available'] = $chrome;
		} else {
			$status['available'] = false;
		}
		
		return $status;
	}
	# ------------------------------------------------
}
