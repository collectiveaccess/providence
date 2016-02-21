<?php
/** ---------------------------------------------------------------------
 * app/helpers/mediaPluginHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2015 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
   *
   */

 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__."/core/Parsers/MediaMetadata/XMPParser.php");

	# ------------------------------------------------------------------------------------------------
	/**
	 * Get path in external_applications.conf for specified application
	 *
	 * @param string $ps_application_name The name of the application. This is the same as the relevant entry in external_applications.conf without the trailing "_app" (Ex. pdfminer, dcraw, ffmpeg)
	 * @return string Path to application as defined in external_applications.conf
	 */
	function caGetExternalApplicationPath($ps_application_name) {
		$o_config = Configuration::load();
		if (!($o_ext_app_config = Configuration::load(__CA_CONF_DIR__.'/external_applications.conf'))) { return null; }

		return $o_ext_app_config->get($ps_application_name.'_app');
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if ImageMagick executables is available within specified directory path
	 *
	 * @param $ps_imagemagick_path - path to directory containing ImageMagick executables
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginImageMagickInstalled($ps_imagemagick_path=null) {
		if(!$ps_imagemagick_path) { $ps_imagemagick_path = caGetExternalApplicationPath('imagemagick'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK = array();
		}
		if (!caIsValidFilePath($ps_imagemagick_path)) { return false; }

		if (caGetOSFamily() == OS_WIN32) { return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path] = true; }	// don't try exec test on Windows
		
		exec($ps_imagemagick_path.'/identify 2> /dev/null', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if GraphicsMagick is available in specified directory path
	 *
	 * @param $ps_graphicsmagick_path - path to directory containing GraphicsMagick executables
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginGraphicsMagickInstalled($ps_graphicsmagick_path=null) {
		if(!$ps_graphicsmagick_path) { $ps_graphicsmagick_path = caGetExternalApplicationPath('graphicsmagick'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK[$ps_graphicsmagick_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK[$ps_graphicsmagick_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK = array();
		}
		if (!caIsValidFilePath($ps_graphicsmagick_path)) { return false; }

		if (caGetOSFamily() == OS_WIN32) { return $_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK[$ps_graphicsmagick_path] = true; }		// don't try exec test on Windows
		
		exec($ps_graphicsmagick_path.' 2> /dev/null', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK[$ps_graphicsmagick_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK[$ps_graphicsmagick_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if dcraw executable is available at specified path
	 *
	 * @param $ps_path_to_dcraw - full path to dcraw including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginDcrawInstalled($ps_path_to_dcraw=null) {
		if(!$ps_path_to_dcraw) { $ps_path_to_dcraw = caGetExternalApplicationPath('dcraw'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_DCRAW;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_DCRAW = array();
		}
		if (!caIsValidFilePath($ps_path_to_dcraw)) { return false; }

		exec($ps_path_to_dcraw.' -i 2> /dev/null', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if ffmpeg executable is available at specified path
	 *
	 * @param $ps_path_to_ffmpeg - full path to ffmpeg including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginFFmpegInstalled($ps_path_to_ffmpeg=null) {
		if(!$ps_path_to_ffmpeg) { $ps_path_to_ffmpeg = caGetExternalApplicationPath('ffmpeg'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_FFMPEG = array();
		}
		if (!caIsValidFilePath($ps_path_to_ffmpeg)) { return false; }

		if (caGetOSFamily() == OS_WIN32) { return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg] = true; }		// don't try exec test on Windows
		
		exec($ps_path_to_ffmpeg.'> /dev/null 2>&1', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if Ghostscript (gs) executable is available at specified path
	 *
	 * @param $ps_path_to_ghostscript - full path to Ghostscript including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginGhostscriptInstalled($ps_path_to_ghostscript=null) {
		if(!$ps_path_to_ghostscript) { $ps_path_to_ghostscript = caGetExternalApplicationPath('ghostscript'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT = array();
		}
		if (!caIsValidFilePath($ps_path_to_ghostscript)) { return false; }
		
		if (caGetOSFamily() == OS_WIN32) { return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript] = true; }		// don't try exec test on Windows
		
		exec($ps_path_to_ghostscript." -v 2> /dev/null", $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if PdfToText executable is available at specified path
	 *
	 * @param $ps_path_to_pdf_to_text - full path to PdfToText including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginPdftotextInstalled($ps_path_to_pdf_to_text=null) {
		if(!$ps_path_to_pdf_to_text) { $ps_path_to_pdf_to_text = caGetExternalApplicationPath('pdftotext'); }

		if (!caIsValidFilePath($ps_path_to_pdf_to_text)) { return false; }
		exec($ps_path_to_pdf_to_text." -v 2> /dev/null", $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if LibreOffice executable is available at specified path
	 *
	 * @param $ps_path_to_libreoffice - full path to LibreOffice including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginLibreOfficeInstalled($ps_path_to_libreoffice=null) {
		if(!$ps_path_to_libreoffice) { $ps_path_to_libreoffice = caGetExternalApplicationPath('libreoffice'); }

		if (!caIsValidFilePath($ps_path_to_libreoffice)) { return false; }
		
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		
		exec($ps_path_to_libreoffice." --version 2> /dev/null", $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if Imagick PHP extension is available
	 *
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginImagickInstalled() {
		$o_config = Configuration::load();
		if ($o_config->get('dont_use_imagick')) { return false; }
		return class_exists('Imagick') ? true : false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if Gmagick PHP extension is available
	 *
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginGmagickInstalled() {
		return class_exists('Gmagick') ? true : false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if GD PHP extension is available. Return false if GD is installed but lacks JPEG support unless "don't worry about JPEGs" parameter is set to true.
	 *
	 * @param boolean $pb_dont_worry_about_jpegs If set will return true if GD is installed without JPEG support; default is to consider JPEG-less GD worthless.
	 *
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginGDInstalled($pb_dont_worry_about_jpegs=false) {
		if ($pb_dont_worry_about_jpegs) {
			return function_exists('imagecreatefromgif') ? true : false;
		} else {
			return function_exists('imagecreatefromjpeg') ? true : false;
		}
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if mediainfo is installed in the given path.
	 * @param string $ps_mediainfo_path path to mediainfo
	 */
	function caMediaInfoInstalled($ps_mediainfo_path=null) {
		if(!$ps_mediainfo_path) { $ps_mediainfo_path = caGetExternalApplicationPath('mediainfo'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO = array();
		}
		if (!caIsValidFilePath($ps_mediainfo_path)) { return false; }
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		exec($ps_mediainfo_path." --Help > /dev/null",$va_output,$vn_return);
		if($vn_return == 255) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if OpenCTM (http://openctm.sourceforge.net) is installed in the given path.
	 * @param string $ps_openctm_path path to OpenCTM ctmconv binary
	 * @return bool
	 */
	function caOpenCTMInstalled($ps_openctm_ctmconv_path=null) {
		if(!$ps_openctm_ctmconv_path) { $ps_openctm_ctmconv_path = caGetExternalApplicationPath('openctm'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_OPENCTM;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_OPENCTM[$ps_openctm_ctmconv_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_OPENCTM[$ps_openctm_ctmconv_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_OPENCTM = array();
		}
		if (!caIsValidFilePath($ps_openctm_ctmconv_path)) { return false; }
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		exec($ps_openctm_ctmconv_path." --help > /dev/null",$va_output,$vn_return);
		if($vn_return == 0) {
			return $_MEDIAHELPER_PLUGIN_CACHE_OPENCTM[$ps_openctm_ctmconv_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_OPENCTM[$ps_openctm_ctmconv_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if Meshlab (http://meshlab.sourceforge.net), and specifically the meshlabserver command line tool, is installed in the given path.
	 * @param string $ps_meshlabserver_path path to the meshlabserver binary
	 * @return bool
	 */
	function caMeshlabServerInstalled($ps_meshlabserver_path=null) {
		if(!$ps_meshlabserver_path) { $ps_meshlabserver_path = caGetExternalApplicationPath('meshlabserver'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_MESHLABSERVER;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_MESHLABSERVER[$ps_meshlabserver_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MESHLABSERVER[$ps_meshlabserver_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_MESHLABSERVER = array();
		}
		if (!caIsValidFilePath($ps_meshlabserver_path)) { return false; }
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		putenv("DISPLAY=:0");
		chdir('/usr/local/bin');
		exec($ps_meshlabserver_path." --help > /dev/null",$va_output,$vn_return);
		if($vn_return == 1) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MESHLABSERVER[$ps_meshlabserver_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_MESHLABSERVER[$ps_meshlabserver_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if PDFMiner (http://www.unixuser.org/~euske/python/pdfminer/index.html) is installed in the given path.
	 * @param string $ps_pdfminer_path path to PDFMiner
	 * @return boolean
	 */
	function caPDFMinerInstalled($ps_pdfminer_path=null) {
		if(!$ps_pdfminer_path) { $ps_pdfminer_path = caGetExternalApplicationPath('pdfminer'); }

		global $_MEDIAHELPER_PLUGIN_CACHE_PDFMINER;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_PDFMINER[$ps_pdfminer_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_PDFMINER[$ps_pdfminer_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_PDFMINER = array();
		}
		if (!caIsValidFilePath($ps_pdfminer_path)) { return false; }

		if (!file_exists($ps_pdfminer_path)) { return $_MEDIAHELPER_PLUGIN_CACHE_PDFMINER[$ps_pdfminer_path] = false; }
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		exec($ps_pdfminer_path." > /dev/null",$va_output,$vn_return);
		if($vn_return == 100) {
			return $_MEDIAHELPER_PLUGIN_CACHE_PDFMINER[$ps_pdfminer_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_PDFMINER[$ps_pdfminer_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if PhantomJS (http://www.phantomjs.org) is installed in the given path.
	 * @param string $ps_phantomjs_path path to PhantomJS executable
	 * @return boolean 
	 */
	function caPhantomJSInstalled($ps_phantomjs_path=null) {
		if(!$ps_phantomjs_path) { $ps_phantomjs_path = caGetExternalApplicationPath('phantomjs'); }
		
		global $_MEDIAHELPER_PLUGIN_CACHE_PHANTOMJS;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_PHANTOMJS[$ps_phantomjs_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_PHANTOMJS[$ps_phantomjs_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_PHANTOMJS = array();
		}
		if (!trim($ps_phantomjs_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_phantomjs_path)) || !file_exists($ps_phantomjs_path)) { return false; }
		
		if (!file_exists($ps_phantomjs_path)) { return $_MEDIAHELPER_PLUGIN_CACHE_PHANTOMJS[$ps_phantomjs_path] = false; }
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		exec($ps_phantomjs_path." > /dev/null",$va_output,$vn_return);
		if($vn_return == 0) {
			return $_MEDIAHELPER_PLUGIN_CACHE_PHANTOMJS[$ps_phantomjs_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_PHANTOMJS[$ps_phantomjs_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if wkhtmltopdf (http://www.wkhtmltopdf.org) is installed in the given path.
	 * @param string $ps_wkhtmltopdf_path path to wkhtmltopdf executable
	 * @return boolean 
	 */
	function caWkhtmltopdfInstalled($ps_wkhtmltopdf_path=null) {
		if(!$ps_wkhtmltopdf_path) { $ps_wkhtmltopdf_path = caGetExternalApplicationPath('wkhtmltopdf'); }
		
		global $_MEDIAHELPER_PLUGIN_CACHE_WKHTMLTOPDF;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_WKHTMLTOPDF[$ps_wkhtmltopdf_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_WKHTMLTOPDF[$ps_wkhtmltopdf_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_WKHTMLTOPDF = array();
		}
		if (!trim($ps_wkhtmltopdf_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_wkhtmltopdf_path)) || !file_exists($ps_wkhtmltopdf_path)) { return false; }
		
		if (!file_exists($ps_wkhtmltopdf_path)) { return $_MEDIAHELPER_PLUGIN_CACHE_WKHTMLTOPDF[$ps_wkhtmltopdf_path] = false; }
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		exec($ps_wkhtmltopdf_path." > /dev/null",$va_output,$vn_return);
		if(($vn_return == 0) || ($vn_return == 1)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_WKHTMLTOPDF[$ps_wkhtmltopdf_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_WKHTMLTOPDF[$ps_wkhtmltopdf_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if ExifTool (http://www.sno.phy.queensu.ca/~phil/exiftool/) is installed in the given path.
	 *
	 * @param string $ps_exiftool_path path to ExifTool
	 * @return boolean 
	 */
	function caExifToolInstalled($ps_exiftool_path=null) {
		if(!$ps_exiftool_path) { $ps_exiftool_path = caGetExternalApplicationPath('exiftool'); }
		
		global $_MEDIAHELPER_PLUGIN_CACHE_EXIFTOOL;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_EXIFTOOL[$ps_exiftool_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_EXIFTOOL[$ps_exiftool_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_EXIFTOOL = array();
		}
		if (!trim($ps_exiftool_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_exiftool_path)) || !file_exists($ps_exiftool_path)) { return false; }
		
		if (!file_exists($ps_exiftool_path)) { return $_MEDIAHELPER_PLUGIN_CACHE_EXIFTOOL[$ps_exiftool_path] = false; }
		if (caGetOSFamily() == OS_WIN32) { return true; }		// don't try exec test on Windows
		exec($ps_exiftool_path." > /dev/null",$va_output,$vn_return);
	
		if($vn_return == 0) {
			return $_MEDIAHELPER_PLUGIN_CACHE_EXIFTOOL[$ps_exiftool_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_EXIFTOOL[$ps_exiftool_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Extracts media metadata using ExifTool
	 *
	 * @param string $ps_filepath file path
	 * @param bool $pb_skip_unknown If set to true, exiftool won't try to extract unknown tags from the source file
	 * 			Use this if metadata extraction fails for unknown reasons. Sometimes tools like Photoshop write weird
	 *			binary data into the files that causes json_decode to barf.
	 *
	 * @return array|null Extracted metadata, null if exiftool is not installed or something went wrong
	 */
	function caExtractMetadataWithExifTool($ps_filepath, $pb_skip_unknown=false){
		if (caExifToolInstalled()) {
			$vs_unknown_param = ($pb_skip_unknown ? '' : '-u');
			$vs_path_to_exif_tool = caGetExternalApplicationPath('exiftool');
			exec("{$vs_path_to_exif_tool} -json -a {$vs_unknown_param} -g1 ".caEscapeShellArg($ps_filepath)." 2> /dev/null", $va_output, $vn_return);

			if($vn_return == 0) {
				$va_data = json_decode(join("\n", $va_output), true);
				if(!is_array($va_data)) { return null; }
				$va_data = array_shift($va_data);

				if(sizeof($va_data)>0) {
					return $va_data;
				}
			}
		}
		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Perform mapping of extracted media metadata to CollectiveAccess bundles.
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $po_instance Model instance to insert extracted metadata into
	 * @param array $pa_metadata Extracted metadata
	 * @param int $pn_locale_id The current locale as a numeric locale_id
	 * @return bool True extracted metadata was mapped and the model changed, false if no change was made to the model
	 */
	function caExtractEmbeddedMetadata($po_instance, $pa_metadata, $pn_locale_id) {
		if (!is_array($pa_metadata)) { return false; }
		$vb_did_mapping = false;
		if (!($vs_media_metadata_config = __CA_CONF_DIR__.'/media_metadata.conf')) { return false; }
		$o_metadata_config = Configuration::load($vs_media_metadata_config);

		$va_mappings = $o_metadata_config->getAssoc('import_mappings');
		$vs_tablename = $po_instance->tableName();


		// set extracted georef?
 		$va_georef_elements = $o_metadata_config->getList('extract_embedded_exif_georeferencing_to');
 		$va_georef_containers = $o_metadata_config->getAssoc('extract_embedded_exif_georeferencing_to_container');
 		$va_date_elements = $o_metadata_config->getList('extract_embedded_exif_creation_date_to');
 		$va_date_containers = $o_metadata_config->getAssoc('extract_embedded_exif_creation_date_to_container');

 		if (isset($pa_metadata['EXIF']) && is_array($pa_metadata['EXIF']) && ((is_array($va_georef_elements) && sizeof($va_georef_elements)) || (is_array($va_georef_containers) && sizeof($va_georef_containers))  || (is_array($va_date_elements) && sizeof($va_date_elements))  || (is_array($va_date_containers) && sizeof($va_date_containers)))) {
			$va_exif_data = $pa_metadata['EXIF'];

			if (is_array($va_georef_elements)) {
				if (is_array($va_coords = caParseEXIFLatLong($va_exif_data))) {
					foreach($va_georef_elements as $vs_element) {
						$va_tmp = explode('.', $vs_element);
						$po_instance->addAttribute(array($va_tmp[1] => "[".$va_coords['latitude'].", ".$va_coords['longitude']."]", 'locale_id' => $pn_locale_id), $va_tmp[1]);
					}
					$vb_did_mapping = true;
				}
			}

			if (is_array($va_georef_containers)) {
				if (is_array($va_coords = caParseEXIFLatLong($va_exif_data))) {
					foreach($va_georef_containers as $vs_container => $va_info) {
						$va_tmp = explode('.', $vs_container);
						$vs_value_element = array_pop(explode('.', $va_info['value']));

						$va_data = array($vs_value_element => "[".$va_coords['latitude'].", ".$va_coords['longitude']."]", 'locale_id' => $pn_locale_id);
						if(isset($va_info['map']) && is_array($va_info['map'])) {
							foreach($va_info['map'] as $vs_sub_element => $vs_value) {
								$va_tmp2 = explode('.', $vs_sub_element);
								$vs_sub_element = array_pop($va_tmp2);
								if ($t_element = $po_instance->_getElementInstance($vs_sub_element)) {
									switch($t_element->get('datatype')) {
										case 3:	// List
											$t_list = new ca_lists();
											$va_data[$vs_sub_element] = $t_list->getItemIDFromList($t_element->get('list_id'), $vs_value);
											break;
										default:
											$va_data[$vs_sub_element] = $vs_value;
											break;
									}
								}
							}
						}
						$po_instance->addAttribute($va_data, $va_tmp[1]);
					}
					$vb_did_mapping = true;
				}
			}

			if (is_array($va_date_elements)) {
				if (($vs_raw_date = $va_exif_data['IFD0']['DateTimeOriginal']) || ($vs_raw_date = $va_exif_data['EXIF']['DateTimeOriginal']) || ($vs_raw_date = $va_exif_data['ExifIFD']['DateTimeOriginal'])) {
					$va_date_tmp = preg_split('![: ]+!', $vs_raw_date);
					$vs_date = 	$va_date_tmp[0].'-'.$va_date_tmp[1].'-'.$va_date_tmp[2].'T'.$va_date_tmp[3].':'.$va_date_tmp[4].':'.$va_date_tmp[5];
					foreach($va_date_elements as $vs_element) {
						$va_tmp = explode('.', $vs_element);
						if(strlen($po_instance->get($vs_element))>0) {
							$po_instance->addAttribute(array($va_tmp[1] => $vs_date, 'locale_id' => $pn_locale_id), $va_tmp[1]);
						} else {
							$po_instance->replaceAttribute(array($va_tmp[1] => $vs_date, 'locale_id' => $pn_locale_id), $va_tmp[1]);
						}
					}
					$vb_did_mapping = true;
				}
			}

			if (is_array($va_date_containers)) {
				$t_list = new ca_lists();
				if (($vs_raw_date = $va_exif_data['IFD0']['DateTimeOriginal']) || ($vs_raw_date = $va_exif_data['EXIF']['DateTimeOriginal']) || ($vs_raw_date = $va_exif_data['ExifIFD']['DateTimeOriginal'])) {
					$va_date_tmp = preg_split('![: ]+!', $vs_raw_date);
					$vs_date = 	$va_date_tmp[0].'-'.$va_date_tmp[1].'-'.$va_date_tmp[2].'T'.$va_date_tmp[3].':'.$va_date_tmp[4].':'.$va_date_tmp[5];
					foreach($va_date_containers as $vs_container => $va_info) {
						$va_tmp = explode('.', $vs_container);
						$vs_value_element = array_pop(explode('.', $va_info['value']));

						$va_data = array($vs_value_element => $vs_date, 'locale_id' => $pn_locale_id);
						if(isset($va_info['map']) && is_array($va_info['map'])) {
							foreach($va_info['map'] as $vs_sub_element => $vs_value) {
								$va_tmp2 = explode('.', $vs_sub_element);
								$vs_sub_element = array_pop($va_tmp2);
								if ($t_element = $po_instance->_getElementInstance($vs_sub_element)) {
									switch($t_element->get('datatype')) {
										case 3:	// List
											$va_data[$vs_sub_element] = $t_list->getItemIDFromList($t_element->get('list_id'), $vs_value);
											break;
										default:
											$va_data[$vs_sub_element] = $vs_value;
											break;
									}
								}
							}
						}
						$po_instance->addAttribute($va_data, $va_tmp[1]);
					}
					$vb_did_mapping = true;
				}
			}
		}


		if (!isset($va_mappings[$po_instance->tableName()])) { return $vb_did_mapping; }
		$va_mapping = $va_mappings[$vs_tablename];

		$vs_type = $po_instance->getTypeCode();
		if (isset($va_mapping[$vs_type]) && is_array($va_mapping[$vs_type])) {
			$va_mapping = $va_mapping[$vs_type];
		} else {
			if (isset($va_mapping['__default__']) && is_array($va_mapping['__default__'])) {
				$va_mapping = $va_mapping['__default__'];
			} else {
				return $vb_did_mapping;
			}
		}

		foreach($va_mapping as $vs_metadata => $va_attr) {
			$va_tmp = explode(":", $vs_metadata);
			$vs_delimiter = caGetOption('delimiter', $va_attr, false);

			foreach($va_attr as $vs_attr) {
				if($vs_attr == 'delimiter') { continue; }

				$va_metadata =& $pa_metadata;
				foreach($va_tmp as $vs_el) {
					if (isset($va_metadata[$vs_el])) {
						$va_metadata =& $va_metadata[$vs_el];
					} else {
						continue(2);
					}
				}

				if(is_array($va_metadata)) { $va_metadata = join(";", $va_metadata); }
				if(!is_int($va_metadata)){ // pass ints through for values like WhiteBalance = 0
					if (!trim($va_metadata)) { continue(2); }
				}
				if(!caSeemsUTF8($va_metadata)) { $va_metadata = caEncodeUTF8Deep($va_metadata); }

				$va_tmp2 = explode(".", $vs_attr);

				switch($va_tmp2[0]) {
					case 'preferred_labels':
						$po_instance->replaceLabel(array($va_tmp2[1] => $va_metadata), $pn_locale_id, null, true);
						break;
					case 'nonpreferred_labels':
						$po_instance->replaceLabel(array($va_tmp2[1] => $va_metadata), $pn_locale_id, null, false);
						break;
					default:
						if($po_instance->hasField($vs_attr)) {
							$po_instance->set($vs_attr, $va_metadata);
						} else {
							// try as attribute
							if(sizeof($va_tmp2)==2){ // format ca_objects.foo, we only want "foo"
								if($vs_delimiter) {
									$va_m = explode($vs_delimiter, $va_metadata);
									$po_instance->removeAttributes($va_tmp2[1]);
									foreach($va_m as $vs_m) {
										$po_instance->addAttribute(array(
											$va_tmp2[1] => trim($vs_m),
											'locale_id' => $pn_locale_id
										),$va_tmp2[1]);
									}
								} else {
									$po_instance->replaceAttribute(array(
										$va_tmp2[1] => $va_metadata,
										'locale_id' => $pn_locale_id
									),$va_tmp2[1]);
								}
							}
						}
				}
				$vb_did_mapping = true;
			}
		}

		return $vb_did_mapping;
	}

	# ------------------------------------------------------------------------------------------------
	/**
	 * Embed media metadata into given file. Embedding is performed on a copy of the file and placed into the
	 * system tmp directory. The given file is never modified.
	 *
	 * @param string $ps_file The file to embed metadata into
	 * @param string $ps_table Table name of the subject record. This is used to figure out the appropriate mapping to use from media_metadata.conf
	 * @param int $pn_pk Primary key of the subject record. This is used to run the export for the right record.
	 * @param string $ps_type_code Optional type code for the subject record
	 * @param int $pn_rep_pk Primary key of the subject representation.
	 * 		If there are export mapping for object representations, we run them after the mapping for the subject table.
	 * 		Fields that get exported here should overwrite fields from the subject table export.
	 * @param string $ps_rep_type_code type code for object representation
	 * @return string File name of a temporary file with the embedded metadata, false on failure
	 */
	function caEmbedMediaMetadataIntoFile($ps_file, $ps_table, $pn_pk, $ps_type_code, $pn_rep_pk, $ps_rep_type_code) {
		require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
		if(!caExifToolInstalled()) { return false; } // we need exiftool for embedding
		$vs_path_to_exif_tool = caGetExternalApplicationPath('exiftool');

		if (!file_exists($ps_file)) { return false; }
		if (!preg_match("/^image\//", mime_content_type($ps_file))) { return false; } // Don't try to embed in files other than images

		// make a temporary copy (we won't touch the original)
		copy($ps_file, $vs_tmp_filepath = caGetTempDirPath()."/".time().md5($ps_file));

		//
		// SUBJECT TABLE
		//

		if($vs_subject_table_export = caExportMediaMetadataForRecord($ps_table, $ps_type_code, $pn_pk)) {
			$vs_export_filename = caGetTempFileName('mediaMetadataSubjExport','xml');
			if(@file_put_contents($vs_export_filename, $vs_subject_table_export) === false) { return false; }
			exec("{$vs_path_to_exif_tool} -tagsfromfile {$vs_export_filename} -all:all ".caEscapeShellArg($vs_tmp_filepath), $va_output, $vn_return);
			@unlink($vs_export_filename);
			@unlink("{$vs_tmp_filepath}_original");
		}

		//
		// REPRESENTATION
		//

		if($vs_representation_Export = caExportMediaMetadataForRecord('ca_object_representations', $ps_rep_type_code, $pn_rep_pk)) {
			$vs_export_filename = caGetTempFileName('mediaMetadataRepExport','xml');
			if(@file_put_contents($vs_export_filename, $vs_representation_Export) === false) { return false; }
			exec("{$vs_path_to_exif_tool} -tagsfromfile {$vs_export_filename} -all:all ".caEscapeShellArg($vs_tmp_filepath), $va_output, $vn_return);
			@unlink($vs_export_filename);
			@unlink("{$vs_tmp_filepath}_original");
		}

		return $vs_tmp_filepath;
	}
	# ------------------------------------------------------------------------------------------------
	function caExportMediaMetadataForRecord($ps_table, $ps_type_code, $pn_id) {
		$o_app_config = Configuration::load();

		if (!($vs_media_metadata_config = $o_app_config->get('media_metadata'))) { return false; }
		$o_metadata_config = Configuration::load($vs_media_metadata_config);

		$va_mappings = $o_metadata_config->getAssoc('export_mappings');
		if(!isset($va_mappings[$ps_table])) { return false; }

		if(isset($va_mappings[$ps_table][$ps_type_code])) {
			$vs_export_mapping = $va_mappings[$ps_table][$ps_type_code];
		} elseif(isset($va_mappings[$ps_table]['__default__'])) {
			$vs_export_mapping = $va_mappings[$ps_table]['__default__'];
		} else {
			$vs_export_mapping = false;
		}

		if($vs_export_mapping) {
			return ca_data_exporters::exportRecord($vs_export_mapping, $pn_id);
		}

		return false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Attempt to detect faces in image files (TIFF, JPEG, PNG) using OpenCV and the php-facedetect module
	 * If php-facedetect and/or OpenCV are not installed then function will return an empty array
	 *
	 * @param string $ps_filepath Path to image file to analyze
	 * @param int $pn_width  Width of image
	 * @param int $pn_height Height of image
	 * @param array $pa_training_files Array of names of OpenCV facial recognition training file to use. Files are stored in <base_dir>/support/opencv. Default is a good general selection of training files. Omit the ".xml" extension when passing names.
	 *
	 * @return array An array of detected faces. Each entry is an array with x & y coordinate, and area width and heights.
	 */
	function caDetectFaces($ps_filepath, $pn_width, $pn_height, $pa_training_files=null) {
		$o_config = Configuration::load();
		if (!$o_config->get('enable_face_detection_for_images')) { return array(); }
		if(!function_exists("face_detect")) { return null; } // is php-facedetect installed? (http://www.xarg.org/project/php-facedetect/)

		if (!$pa_training_files || !is_array($pa_training_files) || !sizeof($pa_training_files)) {
			$pa_training_files = array(
				'haarcascade_profileface',
				'haarcascade_frontalface_alt'
			);
		}
		foreach($pa_training_files as $vs_training_file) {
			$va_faces = face_detect($ps_filepath, __CA_BASE_DIR__."/support/opencv/{$vs_training_file}.xml");
			if (!is_array($va_faces) || !sizeof($va_faces)) { continue; }

			$va_filtered_faces = array();

			if (($vn_width_threshold = (int)($pn_width * 0.10)) < 50) { $vn_width_threshold = 50; }
			if (($vn_height_threshold = (int)($pn_height * 0.10)) < 50) { $vn_height_threshold = 50; }

			foreach($va_faces as $vn_i => $va_info) {
				if (($va_info['w'] > $vn_width_threshold) && ($va_info['h'] > $vn_height_threshold)) {
					$va_filtered_faces[(($va_info['w'] * $va_info['h']) + ($vn_i * 0.1))] = $va_info;	// key is total area of feature
				}
			}

			if (!sizeof($va_filtered_faces)) { continue; }

			// Sort so largest area is first; most probably feature of interest
			ksort($va_filtered_faces, SORT_NUMERIC);
			$va_filtered_faces = array_reverse($va_filtered_faces);
			return $va_filtered_faces;
		}
		return array();
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Attempt to detect faces in image files (TIFF, JPEG, PNG) using OpenCV and the php-facedetect module
	 * If php-facedetect and/or OpenCV are not installed then function will return an empty array
	 *
	 * @param string $ps_type
	 * @param int $pn_width  Width of media
	 * @param int $pn_height Height of media
	 * @param array $pa_options
	 *
	 * @return string Media ICON <img> tag
	 */
	function caGetDefaultMediaIconTag($ps_type, $pn_width, $pn_height, $pa_options=null) {
		if (is_array($va_selected_size = caGetMediaIconForSize($ps_type, $pn_width, $pn_height, $pa_options))) {
			$o_config = Configuration::load();
			$o_icon_config = Configuration::load(__CA_CONF_DIR__.'/default_media_icons.conf');
			$va_icons = $o_icon_config->getAssoc($ps_type);
			return caHTMLImage($o_icon_config->get('icon_folder_url').'/'.$va_icons[$va_selected_size['size']], array('width' => $va_selected_size['width'], 'height' => $va_selected_size['height']));
		}

		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Attempt to detect faces in image files (TIFF, JPEG, PNG) using OpenCV and the php-facedetect module
	 * If php-facedetect and/or OpenCV are not installed then function will return an empty array
	 *
	 * @param string $ps_type
	 * @param int $pn_width  Width of media
	 * @param int $pn_height Height of media
	 * @param array $pa_options
	 *
	 * @return string Media ICON <img> tag
	 */
	function caGetDefaultMediaIconUrl($ps_type, $pn_width, $pn_height, $pa_options=null) {
		if (is_array($va_selected_size = caGetMediaIconForSize($ps_type, $pn_width, $pn_height, $pa_options))) {
			$o_config = Configuration::load();
			$o_icon_config = Configuration::load(__CA_CONF_DIR__.'/default_media_icons.conf');
			$va_icons = $o_icon_config->getAssoc($ps_type);
			return $o_icon_config->get('icon_folder_url').'/'.$va_icons[$va_selected_size['size']];
		}

		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Attempt to detect faces in image files (TIFF, JPEG, PNG) using OpenCV and the php-facedetect module
	 * If php-facedetect and/or OpenCV are not installed then function will return an empty array
	 *
	 * @param string $ps_type
	 * @param int $pn_width  Width of media
	 * @param int $pn_height Height of media
	 * @param array $pa_options
	 *
	 * @return string Media ICON <img> tag
	 */
	function caGetDefaultMediaIconPath($ps_type, $pn_width, $pn_height, $pa_options=null) {
		if (is_array($va_selected_size = caGetMediaIconForSize($ps_type, $pn_width, $pn_height, $pa_options))) {
			$o_config = Configuration::load();
			$o_icon_config = Configuration::load(__CA_CONF_DIR__.'/default_media_icons.conf');
			$va_icons = $o_icon_config->getAssoc($ps_type);
			return $o_icon_config->get('icon_folder_path').'/'.$va_icons[$va_selected_size['size']];
		}

		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 *
	 */
	function caGetMediaIconForSize($ps_type, $pn_width, $pn_height, $pa_options=null) {
		$o_config = Configuration::load();
		$o_icon_config = Configuration::load(__CA_CONF_DIR__.'/default_media_icons.conf');

		$vs_selected_size = null;
		if (is_array($va_icons = $o_icon_config->getAssoc($ps_type))) {
			$vn_min_diff_x = $vn_min_diff_y = 1000000;
			$vs_selected_size = null;
			foreach($va_icons as $vs_size => $vs_filename) {
				$va_tmp = explode('x', $vs_size);

				if (
					((($vn_diff_x = ((int)$pn_width - (int)$va_tmp[0])) >= 0) && ($vn_diff_x <= $vn_min_diff_x))
					&&
					((($vn_diff_y = ((int)$pn_height - (int)$va_tmp[1])) >= 0) && ($vn_diff_y <= $vn_min_diff_y))
				) {
					$vn_min_diff_x = $vn_diff_x;
					$vn_min_diff_y = $vn_diff_y;
					$vs_selected_size = $vs_size;
				}
			}
			if (!$vs_selected_size) {
				$va_tmp = array_keys($va_icons);
				$vs_selected_size = array_shift($va_tmp);
			}
		}
		$va_tmp = explode('x', $vs_selected_size);
		return array('size' => $vs_selected_size, 'width' => $va_tmp[0], 'height' => $va_tmp[1]);
	}
	# ------------------------------------------------------------------------------------------------
