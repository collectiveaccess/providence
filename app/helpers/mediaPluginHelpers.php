<?php
/** ---------------------------------------------------------------------
 * app/helpers/mediaPluginHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__."/Parsers/MediaMetadata/XMPParser.php");

# ------------------------------------------------------------------------------------------------
/**
 * Get path in external_applications.conf for specified application. A path is only returned if it exists on the system.
 * If none of the configured paths for an application exist on the system, null is returned.
 *
 * @param string $ps_application_name The name of the application. This is the same as the relevant entry in external_applications.conf without the trailing "_app" (Ex. dcraw, ffmpeg)
 * @param array $options Options inclide:
 * 		executableName = Name of executable to test for when app path is a directory (Ex. for ImageMagick)
 *		returnAsArray = Return full list of configured paths. Paths are not checked for existence.
 *
 * @return string The first path to application, as defined in external_applications.conf, that exists.
 */
function caGetExternalApplicationPath($ps_application_name, $options=null) {
	if (!($o_ext_app_config = Configuration::load(__CA_CONF_DIR__ . '/external_applications.conf'))) {
		return null;
	}
	$app_paths = $o_ext_app_config->get(["{$ps_application_name}_app", "{$ps_application_name}_path", $ps_application_name]);
	if (!$app_paths) { return null; }
	if (!is_array($app_paths)) { $app_paths = [$app_paths]; }
	if (caGetOption('returnAsArray', $options, false)) { return $app_paths; }
	foreach($app_paths as $p) {
		if ($e = caGetOption('executableName', $options, false)) { $p .= "/{$e}"; }
		if(file_exists($p)) { return $p; }
	}
	return null;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if ImageMagick executables is available within specified directory path
 *
 * @param string $ps_imagemagick_path - path to directory containing ImageMagick executables
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaPluginImageMagickInstalled($ps_imagemagick_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_imagemagick_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_imagemagick_installed", "mediaPluginInfo"); }
	if(!$ps_imagemagick_path) { $ps_imagemagick_path = caGetExternalApplicationPath('imagemagick', ['executableName' => 'identify']); }

	if (!caIsValidFilePath($ps_imagemagick_path)) { 
		CompositeCache::save("mediahelper_imagemagick_installed", false, "mediaPluginInfo");
		return false; 
	}

	if ((caGetOSFamily() == OS_WIN32) && $ps_imagemagick_path) { 
		CompositeCache::save("mediahelper_imagemagick_installed", $ps_imagemagick_path, "mediaPluginInfo");
		return $ps_imagemagick_path; 
	}	// don't try exec test on Windows
	
	caExec($ps_imagemagick_path.' 2> /dev/null', $va_output, $vn_return);
	
	$vb_ret =  (($vn_return >= 0) && ($vn_return < 127));
	
	CompositeCache::save("mediahelper_imagemagick_installed", $ps_imagemagick_path, "mediaPluginInfo");
	
	return $vb_ret ? $ps_imagemagick_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if GraphicsMagick is available in specified directory path
 *
 * @param string $ps_graphicsmagick_path - path to directory containing GraphicsMagick executables
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaPluginGraphicsMagickInstalled($ps_graphicsmagick_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_graphicsmagick_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_graphicsmagick_installed", "mediaPluginInfo"); }
	if(!$ps_graphicsmagick_path) { $ps_graphicsmagick_path = caGetExternalApplicationPath('graphicsmagick'); }

	if (!caIsValidFilePath($ps_graphicsmagick_path)) { 
		CompositeCache::save("mediahelper_graphicsmagick_installed", false, "mediaPluginInfo");
		return false; 
	}

	if ((caGetOSFamily() == OS_WIN32) && $ps_graphicsmagick_path) { 
		CompositeCache::save("mediahelper_graphicsmagick_installed", $ps_graphicsmagick_path, "mediaPluginInfo");
		return $ps_graphicsmagick_path; 
	} // don't try exec test on Windows
	
	caExec($ps_graphicsmagick_path.' 2> /dev/null', $va_output, $vn_return);
	
	$vb_ret = (($vn_return >= 0) && ($vn_return < 127));
	
	CompositeCache::save("mediahelper_graphicsmagick_installed", $ps_graphicsmagick_path, "mediaPluginInfo");
	
	return $vb_ret ? $ps_graphicsmagick_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if dcraw executable is available at specified path
 *
 * @param string $ps_path_to_dcraw - full path to dcraw including executable name
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaPluginDcrawInstalled($ps_path_to_dcraw=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_dcraw_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_dcraw_installed", "mediaPluginInfo"); }
	if(!$ps_path_to_dcraw) { $ps_path_to_dcraw = caGetExternalApplicationPath('dcraw'); }

	if (!caIsValidFilePath($ps_path_to_dcraw)) { 
		CompositeCache::save("mediahelper_dcraw_installed", false, "mediaPluginInfo");
		return false; 
	}

	caExec($ps_path_to_dcraw.' -i 2> /dev/null', $va_output, $vn_return);
	
	$vb_ret = (($vn_return >= 0) && ($vn_return < 127));
	
	CompositeCache::save("mediahelper_dcraw_installed", $ps_path_to_dcraw, "mediaPluginInfo");
	
	return $vb_ret ? $ps_path_to_dcraw : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if ffmpeg executable is available at specified path
 *
 * @param string $ps_path_to_ffmpeg - full path to ffmpeg including executable name
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaPluginFFmpegInstalled($ps_path_to_ffmpeg=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_ffmpeg_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_ffmpeg_installed", "mediaPluginInfo"); }
	if(!$ps_path_to_ffmpeg) { $ps_path_to_ffmpeg = caGetExternalApplicationPath('ffmpeg'); }

	if (!caIsValidFilePath($ps_path_to_ffmpeg)) { 
		CompositeCache::save("mediahelper_ffmpeg_installed", false, "mediaPluginInfo");
		return false; 
	}

	if ((caGetOSFamily() == OS_WIN32) && $ps_path_to_ffmpeg) { 
		CompositeCache::save("mediahelper_ffmpeg_installed", $ps_path_to_ffmpeg, "mediaPluginInfo");
		return $ps_path_to_ffmpeg; 
	}	// don't try exec test on Windows
	
	caExec($ps_path_to_ffmpeg.'> /dev/null 2>&1', $va_output, $vn_return);
	
	$vb_ret = (($vn_return >= 0) && ($vn_return < 127));
	
	CompositeCache::save("mediahelper_ffmpeg_installed", $ps_path_to_ffmpeg, "mediaPluginInfo");
	
	return $vb_ret ? $ps_path_to_ffmpeg : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if Ghostscript (gs) executable is available at specified path
 *
 * @param string $ps_path_to_ghostscript - full path to Ghostscript including executable name
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaPluginGhostscriptInstalled($ps_path_to_ghostscript=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_ghostscript_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_ghostscript_installed", "mediaPluginInfo"); }
	if(!$ps_path_to_ghostscript) { $ps_path_to_ghostscript = caGetExternalApplicationPath('ghostscript'); }

	if (!caIsValidFilePath($ps_path_to_ghostscript)) { 
		CompositeCache::save("mediahelper_ghostscript_installed", false, "mediaPluginInfo");
		return false; 
	}
	
	if ((caGetOSFamily() == OS_WIN32) && $ps_path_to_ghostscript) { 
		CompositeCache::save("mediahelper_ghostscript_installed", $ps_path_to_ghostscript, "mediaPluginInfo");
		return $ps_path_to_ghostscript; 
	} // don't try exec test on Windows
	
	caExec($ps_path_to_ghostscript." -v 2> /dev/null", $va_output, $vn_return);
	
	$vb_ret = (($vn_return >= 0) && ($vn_return < 127));
	
	CompositeCache::save("mediahelper_ghostscript_installed", $ps_path_to_ghostscript, "mediaPluginInfo");
	
	return $vb_ret ? $ps_path_to_ghostscript : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if PdfToText executable is available at specified path
 *
 * @param string $ps_path_to_pdf_to_text - full path to PdfToText including executable name
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaPluginPdftotextInstalled($ps_path_to_pdf_to_text=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_pdftotext_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_pdftotext_installed", "mediaPluginInfo"); }
	if(!$ps_path_to_pdf_to_text) { $ps_path_to_pdf_to_text = caGetExternalApplicationPath('pdftotext'); }
	
	if (!caIsValidFilePath($ps_path_to_pdf_to_text)) { 
		CompositeCache::save("mediahelper_pdftotext_installed", false, "mediaPluginInfo");
		return false; 
	}
	
	caExec($ps_path_to_pdf_to_text." -v 2> /dev/null", $va_output, $vn_return);
	
	$vb_ret = (($vn_return >= 0) && ($vn_return < 127));
	
	CompositeCache::save("mediahelper_pdftotext_installed", $ps_path_to_pdf_to_text, "mediaPluginInfo");
	
	return $vb_ret ? $ps_path_to_pdf_to_text : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if LibreOffice executable is available at specified path
 *
 * @param string $ps_path_to_libreoffice - full path to LibreOffice including executable name
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaPluginLibreOfficeInstalled($ps_path_to_libreoffice=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_libreoffice_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_libreoffice_installed", "mediaPluginInfo"); }
	if(!$ps_path_to_libreoffice) { $ps_path_to_libreoffice = caGetExternalApplicationPath('libreoffice'); }
	if (!caIsValidFilePath($ps_path_to_libreoffice)) { 
		CompositeCache::save("mediahelper_libreoffice_installed", false, "mediaPluginInfo");
		return false;
	}
	
	if ((caGetOSFamily() == OS_WIN32) && $ps_path_to_libreoffice) { 
		CompositeCache::save("mediahelper_libreoffice_installed", $ps_path_to_libreoffice, "mediaPluginInfo");
		return $ps_path_to_libreoffice; 
	} // don't try exec test on Windows
	
	caExec($ps_path_to_libreoffice." --version 2> /dev/null", $va_output, $vn_return);
	
	$vb_ret = (($vn_return >= 0) && ($vn_return < 127));
	
	CompositeCache::save("mediahelper_libreoffice_installed", $ps_path_to_libreoffice, "mediaPluginInfo");
	
	return $vb_ret ? $ps_path_to_libreoffice : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if Imagick PHP extension is available
 *
 * @param array $options No option are currently available.
 *
 * @return boolean - true if available, false if not
 */
function caMediaPluginImagickInstalled($options=null) {
	$o_config = Configuration::load();
	if ($o_config->get('dont_use_imagick')) { return false; }
	return class_exists('Imagick') ? true : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if Gmagick PHP extension is available
 *
 * @param array $options No option are currently available.
 *
 * @return boolean - true if available, false if not
 */
function caMediaPluginGmagickInstalled($options=null) {
	return class_exists('Gmagick') ? true : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if GD PHP extension is available. Return false if GD is installed but lacks JPEG support unless "don't worry about JPEGs" parameter is set to true.
 *
 * @param boolean $pb_dont_worry_about_jpegs If set will return true if GD is installed without JPEG support; default is to consider JPEG-less GD worthless.
 * @param array $options No option are currently available.
 *
 * @return boolean true if available, false if not
 */
function caMediaPluginGDInstalled($pb_dont_worry_about_jpegs=false, $options=null) {
	if ($pb_dont_worry_about_jpegs) {
		return function_exists('imagecreatefromgif') ? true : false;
	} else {
		return function_exists('imagecreatefromjpeg') ? true : false;
	}
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if mediainfo is installed in the given path.
 *
 * @param string $ps_mediainfo_path - full path to MediaInfo executable 
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMediaInfoInstalled($ps_mediainfo_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_mediainfo_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_mediainfo_installed", "mediaPluginInfo"); }
	if(!$ps_mediainfo_path) { $ps_mediainfo_path = caGetExternalApplicationPath('mediainfo'); }
	if (!caIsValidFilePath($ps_mediainfo_path)) { 
		CompositeCache::save("mediahelper_mediainfo_installed", false, "mediaPluginInfo");
		return false; 
	}
	if ((caGetOSFamily() == OS_WIN32) && $ps_mediainfo_path) { 
		CompositeCache::save("mediahelper_mediainfo_installed", $ps_mediainfo_path, "mediaPluginInfo");
		return $ps_mediainfo_path; 
	} // don't try exec test on Windows
	caExec($ps_mediainfo_path." --Help > /dev/null",$va_output,$vn_return);
	$vb_ret = ($vn_return == 255) || ($vn_return == 0);
	
	CompositeCache::save("mediahelper_mediainfo_installed", $ps_mediainfo_path, "mediaPluginInfo");
	
	return $vb_ret ? $ps_mediainfo_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if Meshlab (http://meshlab.sourceforge.net), and specifically the meshlabserver command line tool, is installed in the given path.
 *
 * @param string $ps_meshlabserver_path path to the meshlabserver binary
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caMeshlabServerInstalled($ps_meshlabserver_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_meshlabserver_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_meshlabserver_installed", "mediaPluginInfo"); }
	if(!$ps_meshlabserver_path) { $ps_meshlabserver_path = caGetExternalApplicationPath('meshlabserver'); }

	if (!caIsValidFilePath($ps_meshlabserver_path)) { 
		CompositeCache::save("mediahelper_meshlabserver_installed", false, "mediaPluginInfo");
		return false; 
	}
	if ((caGetOSFamily() == OS_WIN32) && $ps_meshlabserver_path) { 
		CompositeCache::save("mediahelper_meshlabserver_installed", $ps_meshlabserver_path, "mediaPluginInfo");
		return $ps_meshlabserver_path; 
	}	// don't try exec test on Windows
	
	putenv("DISPLAY=:0");
	chdir('/usr/local/bin');
	caExec($ps_meshlabserver_path." --help > /dev/null",$va_output,$vn_return);
	
	$vb_ret = ($vn_return == 1);
	
	CompositeCache::save("mediahelper_meshlabserver_installed", $ps_meshlabserver_path, "mediaPluginInfo");
	
	return $vb_ret ? $ps_meshlabserver_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if wkhtmltopdf (http://www.wkhtmltopdf.org) is installed in the given path.
 *
 * @param string $ps_wkhtmltopdf_path path to wkhtmltopdf executable
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caWkhtmltopdfInstalled($ps_wkhtmltopdf_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_wkhtmltopdf_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_wkhtmltopdf_installed", "mediaPluginInfo"); }
	if(!$ps_wkhtmltopdf_path) { $ps_wkhtmltopdf_path = caGetExternalApplicationPath('wkhtmltopdf'); }
	
	if (!trim($ps_wkhtmltopdf_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_wkhtmltopdf_path)) || !@is_readable($ps_wkhtmltopdf_path)) { 
		CompositeCache::save("mediahelper_wkhtmltopdf_installed", false, "mediaPluginInfo");
		return false; 
	}
	
	if (!@is_readable($ps_wkhtmltopdf_path)) { 
		CompositeCache::save("mediahelper_wkhtmltopdf_installed", false, "mediaPluginInfo");
		return false; 
	}
	if ((caGetOSFamily() == OS_WIN32) && $ps_wkhtmltopdf_path){ 
		CompositeCache::save("mediahelper_wkhtmltopdf_installed", $ps_wkhtmltopdf_path, "mediaPluginInfo");
		return $ps_wkhtmltopdf_path; 
	} // don't try exec test on Windows
	
	caExec($ps_wkhtmltopdf_path." > /dev/null 2> /dev/null",$va_output,$vn_return);
	
	$vb_ret = (($vn_return == 0) || ($vn_return == 1));
	
	CompositeCache::save("mediahelper_wkhtmltopdf_installed", $ps_wkhtmltopdf_path, "mediaPluginInfo");
	
	return $vb_ret ? $ps_wkhtmltopdf_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if youtube-dl (http://www.youtube-dl.org) is installed in the given path.
 *
 * @param string $youtube_dl_path path to youtube-dl executable
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caYouTubeDlInstalled($youtube_dl_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_youtube_dl_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_youtube_dl_installed", "mediaPluginInfo"); }
	if(!$youtube_dl_path) { $youtube_dl_path = caGetExternalApplicationPath('youtube-dl'); }
	
	if (!trim($youtube_dl_path) || (preg_match("/[^\/A-Za-z0-9\.:\-]+/", $youtube_dl_path)) || !@is_readable($youtube_dl_path)) { 
		CompositeCache::save("mediahelper_youtube_dl_installed", false, "mediaPluginInfo");
		return false; 
	}
	if (!@is_readable($youtube_dl_path)) { 
		CompositeCache::save("mediahelper_youtube_dl_installed", false, "mediaPluginInfo");
		return false; 
	}
	if ((caGetOSFamily() == OS_WIN32) && $youtube_dl_path){ 
		CompositeCache::save("mediahelper_youtube_dl_installed", $youtube_dl_path, "mediaPluginInfo");
		return $youtube_dl_path; 
	} // don't try exec test on Windows
	
	caExec($youtube_dl_path." > /dev/null", $output, $return);
	
	$ret = (($return == 0) || ($return == 1) || ($return == 2));
	
	CompositeCache::save("mediahelper_youtube_dl_installed", $youtube_dl_path, "mediaPluginInfo");
	
	return $ret ? $youtube_dl_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if ExifTool (http://www.sno.phy.queensu.ca/~phil/exiftool/) is installed in the given path.
 *
 * @param string $ps_exiftool_path path to ExifTool
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caExifToolInstalled($ps_exiftool_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_exiftool_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_exiftool_installed", "mediaPluginInfo"); }
	if(!$ps_exiftool_path) { $ps_exiftool_path = caGetExternalApplicationPath('exiftool'); }
	
	if (!trim($ps_exiftool_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_exiftool_path)) || !@is_readable($ps_exiftool_path)) { 
		CompositeCache::save("mediahelper_exiftool_installed", false, "mediaPluginInfo");
		return false; 
	}
	
	if (!@is_readable($ps_exiftool_path)) { 
		CompositeCache::save("mediahelper_exiftool_installed", false, "mediaPluginInfo");
		return false; 
	}
	if ((caGetOSFamily() == OS_WIN32) && $ps_exiftool_path) { 
		CompositeCache::save("mediahelper_exiftool_installed", $ps_exiftool_path, "mediaPluginInfo");
		return $ps_exiftool_path; 
	} // don't try exec test on Windows
	
	caExec($ps_exiftool_path." > /dev/null",$va_output,$vn_return);

	$vb_ret = ($vn_return == 0);
	
	CompositeCache::save("mediahelper_exiftool_installed", $ps_exiftool_path, "mediaPluginInfo");
	
	return $vb_ret ? $ps_exiftool_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if Whisper (https://github.com/openai/whisper) Python module is installed 
 *
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *		returnPathToDetect = Return path to whisper_detect.py (language detection utility). [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caWhisperInstalled(array $options=null) {
	$detect = caGetOption('returnPathToDetect', $options, false);
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains($detect ? "mediahelper_whisper_detect_installed" : "mediahelper_whisper_installed", "mediaPluginInfo")) { return CompositeCache::fetch($detect ? "mediahelper_whisper_detect_installed" : "mediahelper_whisper_installed", "mediaPluginInfo"); }
	
	$path = __CA_BASE_DIR__.($detect ? '/support/scripts/whisper_detect.py' : '/support/scripts/whisper_transcribe.py');
	caExec("{$path} 2>&1", $output, $return);
	
	if($return === 2) {
		CompositeCache::save($detect ? "mediahelper_whisper_detect_installed" : "mediahelper_whisper_installed", $path, "mediaPluginInfo");
		return $path;
	}
	$logger = caGetLogger();
	$logger->logError(_t('[mediaPluginHelpers::caWhisperInstalled] Whisper is not installed. Return code was %1; message was %2', $return, join("; ", $output)));	
	return false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Detects if PDFMiner (http://www.unixuser.org/~euske/python/pdfminer/index.html) is installed in the given path.
 *
 * @param string $ps_pdfminer_path path to PDFMiner
 * @param array $options Options include:
 *		noCache = Don't cached path value. [Default is false]
 *
 * @return mixed Path to executable if installed, false if not installed
 */
function caPDFMinerInstalled($ps_pdfminer_path=null, $options=null) {
	if (!caGetOption('noCache', $options, defined('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__')) && CompositeCache::contains("mediahelper_pdfminer_installed", "mediaPluginInfo")) { return CompositeCache::fetch("mediahelper_pdfminer_installed", "mediaPluginInfo"); }
	if(!$ps_pdfminer_path) { $ps_pdfminer_path = caGetExternalApplicationPath('pdfminer'); }

	if (!caIsValidFilePath($ps_pdfminer_path)) { 
		CompositeCache::save("mediahelper_pdfminer_installed", false, "mediaPluginInfo");
		return false; 
	}

	if (!@is_readable($ps_pdfminer_path)) { 
		CompositeCache::save("mediahelper_pdfminer_installed", false, "mediaPluginInfo");
		return false; 
	}
	if ((caGetOSFamily() == OS_WIN32) && $ps_pdfminer_path) { 
		CompositeCache::save("mediahelper_pdfminer_installed", $ps_pdfminer_path, "mediaPluginInfo");
		return $ps_pdfminer_path; 
	} // don't try exec test on Windows

	caExec($ps_pdfminer_path." --version > /dev/null",$va_output,$vn_return);
	
	$vb_ret = ($vn_return == 100 || $vn_return == 0);

	CompositeCache::save("mediahelper_pdfminer_installed", $ps_pdfminer_path, "mediaPluginInfo");
	
	return $vb_ret ? $ps_pdfminer_path : false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Extracts media metadata using ExifTool
 *
 * @param string $filepath file path
 * @param bool $skip_unknown If set to true, exiftool won't try to extract unknown tags from the source file
 * 			Use this if metadata extraction fails for unknown reasons. Sometimes tools like Photoshop write weird
 *			binary data into the files that causes json_decode to barf.
 *
 * @return array|null Extracted metadata, null if exiftool is not installed or something went wrong
 */
function caExtractMetadataWithExifTool(string $filepath, ?bool $skip_unknown=false) : ?array {
	if ($path_to_exif_tool = caExifToolInstalled()) {
		$unknown_param = ($skip_unknown ? '' : '-u');
		caExec("{$path_to_exif_tool} -json -a {$unknown_param} -g1 ".caEscapeShellArg($filepath)." 2> /dev/null", $output, $vn_return);

		if($vn_return == 0) {
			$data = json_decode(join("\n", $output), true);
			if(!is_array($data)) { return null; }
			$data = array_shift($data);
			
			// rewrite GPS entries to include ref
			if (isset($data['GPS']['GPSLatitude'])) { $data['GPS']['GPSLatitude'] .= " ".substr($data['GPS']['GPSLatitudeRef'], 0, 1); }
			if (isset($data['GPS']['GPSLongitude'])) { $data['GPS']['GPSLongitude'] .= " ".substr($data['GPS']['GPSLongitudeRef'], 0, 1); }

			if(sizeof($data)>0) {
				return $data;
			}
		}
	}
	return null;
}
# ------------------------------------------------------------------------------------------------
/**
 * Remove EXIF Orientation tag using ExifTool
 *
 * @param string $filepath file path
 *
 * @return bool True on success, false if operation failed
 */
function caExtractRemoveOrientationTagWithExifTool(string $filepath) : bool {
	if(!file_exists($filepath)) { return false; }
	if ($path_to_exif_tool = caExifToolInstalled()) {
		caExec("{$path_to_exif_tool} -overwrite_original_in_place -P -fast -Orientation= ".caEscapeShellArg($filepath)." 2> /dev/null", $output, $return);

		if($return == 0) {
			return true;
		}
	}
	return false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Remove all embedded metadata using ExifTool
 *
 * @param string $filepath file path
 *
 * @return bool True on success, false if operation failed
 */
function caRemoveAllMediaMetadata(string $filepath) : bool {
	if(!file_exists($filepath)) { return false; }
	if ($path_to_exif_tool = caExifToolInstalled()) {
		caExec("{$path_to_exif_tool} -all= ".caEscapeShellArg($filepath)." 2> /dev/null", $output, $return);

		if($return == 0) {
			return true;
		}
	}
	return false;
}
# ------------------------------------------------------------------------------------------------
/**
 * Embed media metadata into given file. Embedding is performed on a copy of the file and placed into the
 * system tmp directory. The original file is never modified.
 *
 * @param string $file The file to embed metadata into
 * @param string $table Table name of the subject record. This is used to figure out the appropriate mapping to use from media_metadata.conf
 * @param int $pk Primary key of the subject record. This is used to run the export for the right record.
 * @param string $type_code Optional type code for the subject record
 * @param int $rep_pk Primary key of the subject representation.
 * 		If there are export mapping for object representations, we run them after the mapping for the subject table.
 * 		Fields that get exported here should overwrite fields from the subject table export.
 * @param string $rep_type_code type code for object representation
 * @return string File name of a temporary file with the embedded metadata, false on failure
 */
function caEmbedMediaMetadataIntoFile($t_instance, string $version, ?array $options=null) {
	global $file_cleanup_list;
	
	if(!caExifToolInstalled()) { return false; } // we need exiftool for embedding
	$path_to_exif_tool = caGetExternalApplicationPath('exiftool');
	
	if(!($file = caGetOption('path', $options, null))) {
		if(is_a($t_instance, 'ca_object_representations')) {
			$file = $t_instance->getMediaPath('media', $version);
		} elseif(is_a($t_instance, 'RepresentableBaseModel')) {
			$file = $t_instance->get("ca_object_representations.media.{$version}.path");
		}
	}
	
	if (!@is_readable($file)) { return false; }
	if (!preg_match("/^image\//", mime_content_type($file))) { return false; } // Don't try to embed in files other than images

	// make a temporary copy (we won't touch the original)
	$tmp_filepath = __CA_APP_DIR__."/tmp/".time().md5($file);
	if(!copy($file, $tmp_filepath)) {
		return false;
	}
	$file_cleanup_list[] = $tmp_filepath;

	$o_config = Configuration::load(__CA_CONF_DIR__.'/media_metadata.conf');
	
	$table = $t_instance->tableName();
	$typecode = $t_instance->get("{$table}.type_id", ['convertCodesToIdno' => true]);
	
	$mappings = $o_config->get('export_mappings');
	if(isset($mappings[$table])) {
		$map = $mappings[$table][$typecode] ?? $mappings[$table]['__default__'] ?? null;
		if(!is_array($map)) { return null; }
		
		$acc = [];
		foreach($map as $tag => $template) {
			$tmp = explode(':', $tag);
			
			switch($standard = strtolower($tmp[0])) {
				case 'iptc':
					if($code = caIPTCTagNameToCode($tmp[1])) {
						$v = strip_tags(br2nl($t_instance->getWithTemplate($template)));
						$acc[$standard][$tag] = $v;
					}
					break;
				case 'xmp':
					if($code = caXMPTagNameToCode($tmp[1])) {
						$v = strip_tags(br2nl($t_instance->getWithTemplate($template)));
						$acc[$standard][$tag] = $v;
					}
					break;
			}
		}
		
		if(isset($acc['iptc'])) {
			$exif_tool_params = [];

			foreach($acc['iptc'] as $tag => $value) {
				$exif_tool_params[] = "-{$tag}=".caEscapeShellArg($value);
			}
			
			caExec("{$path_to_exif_tool} ".join(' ', $exif_tool_params)." {$tmp_filepath}", $output, $return);
		}
		if(isset($acc['xmp'])) {
			$exif_tool_params = [];

			foreach($acc['xmp'] as $tag => $value) {
				$exif_tool_params[] = "-{$tag}=".caEscapeShellArg($value);
			}
			
			caExec("{$path_to_exif_tool} ".join(' ', $exif_tool_params)." {$tmp_filepath}", $output, $return);
		}
	}
	
	return $tmp_filepath;
}
# ------------------------------------------------------------------------------------------------
/**
 *
 */
function caXMPTagNameToCode(string $name) : ?string {	
	$xmp_code = [
		'owner' => 'xmpRights:Owner',
		'usageterms' => 'xmpRights:UsageTerms',
		'webstatement' => 'xmpRights:WebStatement',
		'copyright' => 'crs:Copyright',
		'advisory' => 'xmp:Advisory',
		'createdate' => 'xmp:CreateDate',
		'description' => 'xmp:Description',
		'format' => 'xmp:Format',
		'identifier' => 'xmp:Identifier',
		'title' => "xmp:Title",
		'keywords' => "xmp:Keywords",
		'label' => "xmp:Label",
		'metadatadate' => "xmp:MetadataDate",
	];
	if(!($code = $xmp_code[mb_strtolower($name)] ?? null)) {
		return null;
	}
	return $code;
}
# ------------------------------------------------------------------------------------------------
/**
 *
 */
function caIPTCTagNameToCode(string $name) : ?int {	
	$iptc_codes = [
		'keywords' => 25,
		'datecreated' => 55,
		'timecreated' => 60,
		'digitalcreationdate' => 62,
		'digitalcreationtime' => 63,
		'by-line' => 80,
		'byline' => 80,
		'by-linetitle' => 85,
		'bylinetitle' => 85,
		'city' => 90,
		'sub-location' => 92,
		'sublocation' => 92,
		'country-primarylocationcode' => 100,
		'countrycode' => 100,
		'country-primarylocationname' => 101,
		'country' => 101,
		'headline' => 105,
		'credit' => 110,
		'source' => 115,
		'copyrightnotice' => 116,
		'contact' => 118,
		'caption-abstract' => 120,
		'captionabstract' => 120,
		'caption' => 120,
	];
	
	if(!($code = $iptc_codes[mb_strtolower($name)] ?? null)) {
		if(is_numeric($name) && in_array((int)$name, array_values($iptc_codes))) {
			return (int)$name;
		}
		return null;
	}
	return $code;
}
# ------------------------------------------------------------------------------------------------
/**
 * Get HTML tag for default media icon
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
		$alt_text_by_type = $o_icon_config->getAssoc('alt_text');
		$alt_text = $alt_text_by_type[$ps_type] ?? _t('Default media icon');
		return caHTMLImage($o_icon_config->get('icon_folder_url').'/'.$va_icons[$va_selected_size['size']], ['alt' => $alt_text, 'width' => $va_selected_size['width'], 'height' => $va_selected_size['height']]);
	}

	return null;
}
# ------------------------------------------------------------------------------------------------
/**
 * Get URL for default media icon
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
 * Get file path for default media icon
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
/**
 * Resolve media identifiers in the form <type>:<id> or <type>:<id>:<page> into references suitable for using with the IIIF service.
 * Identifier types include: "representation", "attribute", "object", "entity", "place", "occurrence", "collection" and "location"
 * 
 * "representation" reference to a representation by representation_id
 * "attribute" refers to a media attribute by value_id
 * "object", "entity" and other table referencing types refer to the primary representation attached the specified row.
 *
 * For valid identifiers an array is returned with the following information:
 *
 * type = the "type" portion of resolved IIIF identifier. This will always be either "representation" or "attribute", no matter what the type in the original identifer is.
 * id = the "id" portion of the resolved identifier. This will be a representation_id or attribute value_id
 * page = the "page" portion of the identifer, if specified. This will be unchanged from the original identifier.
 * subject = the table name of the subject of the original media identifier. For representations this will be the representation itself. For attributes this will be the table name of the record it is bonud to. For table-related types this will be the table name of the type (Eg. for "objects", this will be "ca_objects")
 * subject_id = the primary key of the subject row. For representations this will be the representation_id. For attributes this will be the id of the row to which the attribute is bound. For table-related types this will be the id of the referenced row.
 * instance = An instance of the resolved media (ca_object_representations or ca_attribute_values) when the "includeInstance" option is set.
 * subject_instance = An instance of the resolved subject (ca_object_representations, ca_objects, or other primary table) when the "includeInstance" option is set.
 *
 * @param string $ps_identifier The identifier
 * @param array $pa_options Options include:
 *      includeInstance = Include resolved media and subject instances in return value. [Default is false]
 *      checkAccess = check resolved media and subject against provided access values and return null if access is not allowed. [Default is null]
 *
 * @return array
 */
function caParseMediaIdentifier($ps_identifier, $pa_options=null) {
	$pb_include_instance = caGetOption('includeInstance', $pa_options, false);
	$va_tmp = explode(':', $ps_identifier);
	
	$va_ret = null;
	switch($vs_type = strtolower($va_tmp[0])) {
		case 'representation':
			Datamodel::getInstance('ca_object_representations', true);
			$va_ret = ['type' => $vs_type, 'id' => (int)$va_tmp[1], 'page' => isset($va_tmp[2]) ? (int)$va_tmp[2] : null, 'subject' => 'ca_object_representations', 'subject_id' => (int)$va_tmp[1]];
			if (!($t_rep = ca_object_representations::find((int)$va_tmp[1], $pa_options))) { return null; } // ca_object_representations::find() performs checkAccess
			if ($pb_include_instance) {
				$va_ret['instance'] = $t_rep;
			}
			break;
		case 'attribute':
			Datamodel::getInstance('ca_attribute_values', true);
			$t_val = new ca_attribute_values((int)$va_tmp[1]);
			if (!$t_val->isLoaded()) { return null; }
			$t_attr = new ca_attributes($t_val->get('attribute_id'));
			$vs_table_name  = Datamodel::getTableName($t_attr->get('table_num'));
			$vn_subject_id = (int)$t_attr->get('row_id');
			if (!($t_subject = $vs_table_name::find($vn_subject_id, $pa_options))) { return null; } // table::find() performs checkAccess
			
			$va_ret = ['type' => $vs_type, 'id' => (int)$va_tmp[1], 'page' => isset($va_tmp[2]) ? (int)$va_tmp[2] : null, 'subject' => $vs_table_name, 'subject_id' => $vn_subject_id];
			
			if ($pb_include_instance) {
				$va_ret['instance'] = $t_val;
				$va_ret['subject_instance'] = $t_subject;
			}
			break;
		default:
			if (($vs_table = caMediaIdentifierTypeToTable($vs_type)) && Datamodel::getInstance($vs_table, true) && ($t_instance = $vs_table::find((int)$va_tmp[1], $pa_options)) && ($vn_rep_id = $t_instance->getPrimaryRepresentationID($pa_options))) {
				// return primary representation (access checkAccess performed by table::find() )
				$va_ret = ['type' => 'representation', 'id' => (int)$vn_rep_id, 'page' => null, 'subject' => $vs_table, 'subject_id' => (int)$va_tmp[1]];
				
				if ($pb_include_instance) {
					$va_ret['subject_instance'] = $t_instance;
				}
			} elseif (is_numeric($va_tmp[0])) {
				Datamodel::getInstance('ca_object_representations', true);
				if (!($t_rep = ca_object_representations::find((int)$va_tmp[1], $pa_options))) { return null; }     // ca_object_representations::find() performs checkAccess
				
				$va_ret = ['type' => 'representation', 'id' => (int)$va_tmp[0], 'page' => isset($va_tmp[1]) ? (int)$va_tmp[1] : null, 'subject' => 'ca_object_representations', 'subject_id' => (int)$va_tmp[0]];
			}
			if ($va_ret && $pb_include_instance) {
				$va_ret['instance'] = $t_rep;
			}
			break;
	}
	return $va_ret;
}
# ------------------------------------------------------------------------------------------------
/**
 * Transform table-related media identifier types (Eg. object, entity, place) to table names
 * 
 * @param string $ps_type A table-related media type
 * @param array $pa_options Options include:
 *      returnInstance = Return instance of table rather than name. [Default is false]
 *
 * @return mixed
 */
function caMediaIdentifierTypeToTable($ps_type, $pa_options=null) {
	$va_map = [
		'object' => 'ca_objects', 'objects' => 'ca_objects',
		'entity' => 'ca_entities', 'entities' => 'ca_entities',
		'place' => 'ca_places', 'places' => 'ca_places',
		'occurrence' => 'ca_occurrences', 'occurrences' => 'ca_occurrences',
		'collection' => 'ca_collections', 'collections' => 'ca_collections',
		'location' => 'ca_storage_locations', 'locations' => 'ca_storage_locations'
	];
	
	$vs_table = isset($va_map[strtolower($ps_type)]) ? $va_map[strtolower($ps_type)] : null;
	if ($vs_table && caGetOption('returnInstance', $pa_options, false)) {
		return Datamodel::getInstanceByTableName($vs_table, true);
	} 

	return $vs_table;
}
# ------------------------------------------------------------------------------------------------
/**
 *
 *
 */
function caGetPDFInfo($ps_filepath) {
	$o_config = Configuration::load();
	
	// try ZendPDF
	if(!$o_config->get('dont_use_zendpdf_to_identify_pdfs')) {
		try {
			$o_pdf = Zend_Pdf::load($ps_filepath);
		} catch(Exception $e){
			$o_pdf = null;
		}
		if ($o_pdf && (sizeof($o_pdf->pages) > 0)) { 
			$o_page = $o_pdf->pages[0];
			return [
				'title' => $o_pdf->properties['Title'] ?? null,
				'author' => $o_pdf->properties['Author'] ?? null,
				'producer' => $o_pdf->properties['Producer'] ?? null,
				'pages' => sizeof($o_pdf->pages),
				'width' => $o_page->getWidth(),
				'height' => $o_page->getHeight()
			];
		} 
	}
	
	// try graphicsmagick
	if ((!$o_config->get('dont_use_graphicsmagick_to_identify_pdfs')) && caMediaPluginGraphicsMagickInstalled()) {
		$vs_graphicsmagick_path = caGetExternalApplicationPath('graphicsmagick');
		caExec($vs_graphicsmagick_path.' identify -format "%m;%w;%h;%p\n" '.caEscapeShellArg($ps_filepath).(caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);

		array_pop($va_output); // last line is blank
		if (is_array($va_output) && (sizeof($va_output) > 0)) {
			do {
				$va_tmp = explode(';', array_shift($va_output));
				if ($va_tmp[0] === 'PDF') {
					return array(
						'width' => intval($va_tmp[1]),
						'height' => intval($va_tmp[2]),
						'pages' => sizeof($va_output) + 1
					);
				}
			} while((sizeof($va_output) > 0) && ($va_tmp[0] !== 'PDF'));
		}
	}
	
	// try imagemagick
	if ((!$o_config->get('dont_use_imagemagick_to_identify_pdfs')) && caMediaPluginImageMagickInstalled()) {
		$vs_imagemagick_path = caGetExternalApplicationPath('imagemagick');
		caExec($vs_imagemagick_path.'/identify -format "%m;%w;%h;%p\n" '.caEscapeShellArg($ps_filepath).(caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
	
		array_pop($va_output); // last line is blank
		if (is_array($va_output) && (sizeof($va_output) > 0)) {
			do {
				$va_tmp = explode(';', array_shift($va_output));
				if ($va_tmp[0] === 'PDF') {
					return array(
						'width' => intval($va_tmp[1]),
						'height' => intval($va_tmp[2]),
						'pages' => sizeof($va_output) + 1
					);
				}
			} while((sizeof($va_output) > 0) && ($va_tmp[0] !== 'PDF'));
		}
	}
	
	// try pdfinfo
	if (caMediaPluginPdftotextInstalled()) {
		$vs_path_to_pdf_to_text = str_replace("pdftotext", "pdfinfo", caGetExternalApplicationPath('pdftotext'));
		
		caExec("{$vs_path_to_pdf_to_text} ".caEscapeShellArg($ps_filepath).(caIsPOSIX() ? " 2> /dev/null" : ""), $va_output, $vn_return);
		
		if (($vn_return == 0) && sizeof($va_output) > 0) {
			$va_info = [];
			foreach($va_output as $vs_line) {
				$va_line = explode(":", $vs_line);
				
				$vs_tag = strtolower(trim(array_shift($va_line)));
				$vs_value = trim(join(":", $va_line));
			
				switch($vs_tag) {
					case 'pages':
						$va_info['pages'] = (int)$vs_value;
						break;
					case 'page size':
						if (preg_match_all("!([\d\.]+)!", $vs_value, $va_dims)) {
							$va_info['width'] = ceil((float)$va_dims[1][0]);
							$va_info['height'] = ceil((float)$va_dims[1][1]);
						}
						break;
					case 'pdf version':
						$va_info['version'] = (float)$vs_value;
						break;
					case 'producer':
						$va_info['software'] = $vs_value;
						break;
					case 'author':
					case 'title':
						$va_info[$vs_tag] = $vs_value;
						break;
				}
			}
			return $va_info;
		}
	}
	
	// ok, we're stumped
	return null;
}
# ------------------------------------------------------------------------------------------------
/**
 * Determine if permissions are set properly for the media directory
 *
 * @return bool True if permissions are set correctly, false if error
 */
function caCheckMediaDirectoryPermissions() {
	$o_config = Configuration::load();
	
	$vs_media_root = $o_config->get('ca_media_root_dir');
	$vs_base_dir = $o_config->get('ca_base_dir');
	$va_tmp = explode('/', $vs_media_root);
	
	$vb_perm_media_error = false;
	$vs_perm_media_path = null;
	$vb_at_least_one_part_of_the_media_path_exists = false;
	while(sizeof($va_tmp)) {
		if (!file_exists(join('/', $va_tmp))) {
			array_pop($va_tmp);
			continue;
		}
		if (!is_writeable(join('/', $va_tmp))) {
			$vb_perm_media_error = true;
			$vs_perm_media_path = join('/', $va_tmp);
			break;
		}
		$vb_at_least_one_part_of_the_media_path_exists = true;
		break;
	}

	// check web root for write-ability
	if (!$vb_perm_media_error && !$vb_at_least_one_part_of_the_media_path_exists && !is_writeable($vs_base_dir)) {
		$vb_perm_media_error = true;
		$vs_perm_media_path = $vs_base_dir;
	}

	return !$vb_perm_media_error;
}
# ------------------------------------------------------------------------------------------------
/**
 *
 */
function caTranscribeAVMedia(string $mimetype) : bool {
	$config = Configuration::load();
	if(!$config->get('create_transcriptions')) { return false; }
	
	// Check mimetype
	$types = $config->getList('transcription_media_types') ?? ['audio', 'video'];	// assume all AV is transcribable if specific types are not configured
	if(!caMimetypeIsValid($mimetype, $types)) { return false; }
	
	// Check that Whisper is installed
	if(!caWhisperInstalled()) { return false; }
	return true;
}
# ------------------------------------------------------------------------------------------------
/**
 *
 */
function caExtractTextFromPDF(string $filepath, ?array $options=null) : ?string {
	if(!($pdf_to_text_path = caMediaPluginPdftotextInstalled())) { return null; }
	
	$page_start = caGetOption('start', $options, 1);
	if($page_start < 1) { $page_start = 1; }
	$num_pages = caGetOption('pages', $options, 0);
	$num_chars = caGetOption('chars', $options, 0);
	
	$page_limits = " -f {$page_start} ";
	if($num_pages > 0) { $page_limits .= "-l ".($page_start + $num_pages)." "; }
	
	$tmp_filename = tempnam('/tmp', 'CA_PDF_TEXT');
	caExec($pdf_to_text_path.' -q -enc UTF-8 '.$page_limits.caEscapeShellArg($filepath).' '.caEscapeShellArg($tmp_filename).(caIsPOSIX() ? " 2> /dev/null" : ""));
	$extracted_text = file_get_contents($tmp_filename);
	
	if($num_chars > 0) { $extracted_text = mb_substr($extracted_text, 0, $num_chars, 'UTF-8'); }
	@unlink($tmp_filename);
	return $extracted_text;
}
# ------------------------------------------------------------------------------------------------
