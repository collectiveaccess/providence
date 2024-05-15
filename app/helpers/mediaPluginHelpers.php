<?php
/** ---------------------------------------------------------------------
 * app/helpers/mediaPluginHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2023 Whirl-i-Gig
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
 * @param string $ps_filepath file path
 * @param bool $pb_skip_unknown If set to true, exiftool won't try to extract unknown tags from the source file
 * 			Use this if metadata extraction fails for unknown reasons. Sometimes tools like Photoshop write weird
 *			binary data into the files that causes json_decode to barf.
 *
 * @return array|null Extracted metadata, null if exiftool is not installed or something went wrong
 */
function caExtractMetadataWithExifTool($ps_filepath, $pb_skip_unknown=false){
	if ($vs_path_to_exif_tool = caExifToolInstalled()) {
		$vs_unknown_param = ($pb_skip_unknown ? '' : '-u');
		caExec("{$vs_path_to_exif_tool} -json -a {$vs_unknown_param} -g1 ".caEscapeShellArg($ps_filepath)." 2> /dev/null", $va_output, $vn_return);

		if($vn_return == 0) {
			$va_data = json_decode(join("\n", $va_output), true);
			if(!is_array($va_data)) { return null; }
			$va_data = array_shift($va_data);
			
			// rewrite GPS entries to include ref
			if (isset($va_data['GPS']['GPSLatitude'])) { $va_data['GPS']['GPSLatitude'] .= " ".substr($va_data['GPS']['GPSLatitudeRef'], 0, 1); }
			if (isset($va_data['GPS']['GPSLongitude'])) { $va_data['GPS']['GPSLongitude'] .= " ".substr($va_data['GPS']['GPSLongitudeRef'], 0, 1); }

			if(sizeof($va_data)>0) {
				return $va_data;
			}
		}
	}
	return null;
}
# ------------------------------------------------------------------------------------------------
/**
 * Remove EXIF Orientation tag using ExifTool
 *
 * @param string $pfilepath file path
 *
 * @return bool True on success, false if operation failed
 */
function caExtractRemoveOrientationTagWithExifTool($filepath){
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

	if (!isset($pa_metadata['system']['filename']) && ($vs_original_filename = $po_instance->get('original_filename'))) {
		$pa_metadata['system']['filename'] = $vs_original_filename;
	}

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
							if ($t_element = ca_metadata_elements::getInstance($vs_sub_element)) {
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
				$po_instance->update();	// commit immediately and don't worry about errors (in case date is somehow invalid)
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
							if ($t_element = ca_metadata_elements::getInstance($vs_sub_element)) {
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
	if(!caExifToolInstalled()) { return false; } // we need exiftool for embedding
	$vs_path_to_exif_tool = caGetExternalApplicationPath('exiftool');

	global $file_cleanup_list;
	
	if (!@is_readable($ps_file)) { return false; }
	if (!preg_match("/^image\//", mime_content_type($ps_file))) { return false; } // Don't try to embed in files other than images

	// make a temporary copy (we won't touch the original)
	copy($ps_file, $vs_tmp_filepath = caGetTempDirPath()."/".time().md5($ps_file));
	$file_cleanup_list[] = $vs_tmp_filepath;

	//
	// SUBJECT TABLE
	//
	if($vs_subject_table_export = caExportMediaMetadataForRecord($ps_table, $ps_type_code, $pn_pk)) {
		$vs_export_filename = caGetTempFileName('mediaMetadataSubjExport','xml');
		if(@file_put_contents($vs_export_filename, $vs_subject_table_export) === false) { return false; }
		caExec("{$vs_path_to_exif_tool} -tagsfromfile {$vs_export_filename} -all:all ".caEscapeShellArg($vs_tmp_filepath), $va_output, $vn_return);
		@unlink($vs_export_filename);
		@unlink("{$vs_tmp_filepath}_original");
	}

	//
	// REPRESENTATION
	//

	if($vs_representation_export = caExportMediaMetadataForRecord('ca_object_representations', $ps_rep_type_code, $pn_rep_pk)) {
		$vs_export_filename = caGetTempFileName('mediaMetadataRepExport','xml');
		if(@file_put_contents($vs_export_filename, $vs_representation_export) === false) { return false; }
		caExec("{$vs_path_to_exif_tool} -tagsfromfile {$vs_export_filename} -all:all ".caEscapeShellArg($vs_tmp_filepath), $va_output, $vn_return);
		@unlink($vs_export_filename);
		@unlink("{$vs_tmp_filepath}_original");
	}

	return $vs_tmp_filepath;
}
# ------------------------------------------------------------------------------------------------
function caGetExifTagArgsForExport($data) {
	$xml = new SimpleXMLElement($data);	
	$xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
	$xml->registerXPathNamespace('et', 'http://ns.exiftool.ca/1.0/');
	$xml->registerXPathNamespace('ExifTool', 'http://ns.exiftool.ca/1.0/');
	$xml->registerXPathNamespace('System', 'http://ns.exiftool.ca/File/System/1.0/');
	$xml->registerXPathNamespace('File', 'http://ns.exiftool.ca/File/1.0/');
	$xml->registerXPathNamespace('JFIF', 'http://ns.exiftool.ca/JFIF/JFIF/1.0/');
	$xml->registerXPathNamespace('IFF0', 'http://ns.exiftool.ca/EXIF/IFD0/1.0/');
	$xml->registerXPathNamespace('ExifIFD', 'http://ns.exiftool.ca/EXIF/ExifIFD/1.0/');
	$xml->registerXPathNamespace('Apple', 'http://ns.exiftool.ca/MakerNotes/Apple/1.0/');
	$xml->registerXPathNamespace('XMP-x', 'http://ns.exiftool.ca/XMP/XMP-x/1.0/');
	$xml->registerXPathNamespace('XMP-xmp', 'http://ns.exiftool.ca/XMP/XMP-xmp/1.0/');
	$xml->registerXPathNamespace('XMP-photoshop', 'http://ns.exiftool.ca/XMP/XMP-photoshop/1.0/');
	$xml->registerXPathNamespace('Photoshop', 'http://ns.exiftool.ca/Photoshop/Photoshop/1.0/');
	$xml->registerXPathNamespace('ICC-header', 'http://ns.exiftool.ca/ICC_Profile/ICC-header/1.0/');
	$xml->registerXPathNamespace('ICC_Profile', 'http://ns.exiftool.ca/ICC_Profile/ICC_Profile/1.0/');
	$xml->registerXPathNamespace('ICC-view', 'http://ns.exiftool.ca/ICC_Profile/ICC-view/1.0/');
	$xml->registerXPathNamespace('IVV-meas', 'http://ns.exiftool.ca/ICC_Profile/ICC-meas/1.0/');
	$xml->registerXPathNamespace('Composite', 'http://ns.exiftool.ca/Composite/1.0/');
	
	$tags = $xml->xpath('rdf:Description/*');
	
	$tag_args = [];
	foreach($tags as $t) {
		$ns = array_shift(array_keys($t->getNamespaces()));
		$n = $t->getName();
		$v = $t->__toString();
		$tag_args[] = "-{$n}=\"$v\"";
	}
	return $tag_args;
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
