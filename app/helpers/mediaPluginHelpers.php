<?php
/** ---------------------------------------------------------------------
 * app/helpers/mediaPluginHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
	 * Detects if CoreImageTool executable is available at specified path
	 * 
	 * @param $ps_path_to_coreimage - full path to CoreImageTool including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginCoreImageInstalled($ps_path_to_coreimage) {
		global $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE = array();
		}
		if (!$ps_path_to_coreimage || (preg_match("/[^\/A-Za-z0-9]+/", $ps_path_to_coreimage)) || !file_exists($ps_path_to_coreimage)) { return false; }
		
		exec($ps_path_to_coreimage.' 2> /dev/null', $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_COREIMAGE[$ps_path_to_coreimage] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if ImageMagick executables is available within specified directory path
	 * 
	 * @param $ps_imagemagick_path - path to directory containing ImageMagick executables
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginImageMagickInstalled($ps_imagemagick_path) {
		global $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK[$ps_imagemagick_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_IMAGEMAGICK = array();
		}
		if (!$ps_imagemagick_path || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_imagemagick_path)) || !file_exists($ps_imagemagick_path) || !is_dir($ps_imagemagick_path)) { return false; }
		
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
	function caMediaPluginGraphicsMagickInstalled($ps_graphicsmagick_path) {
		global $_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK[$ps_graphicsmagick_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK[$ps_graphicsmagick_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_GRAPHICSMAGICK = array();
		}
		if (!$ps_graphicsmagick_path || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_graphicsmagick_path)) || !file_exists($ps_graphicsmagick_path)) { return false; }
		
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
	function caMediaPluginDcrawInstalled($ps_path_to_dcraw) {
		global $_MEDIAHELPER_PLUGIN_CACHE_DCRAW;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_DCRAW[$ps_path_to_dcraw];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_DCRAW = array();
		}
		if (!$ps_path_to_dcraw || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_dcraw)) || !file_exists($ps_path_to_dcraw)) { return false; }
		
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
	function caMediaPluginFFfmpegInstalled($ps_path_to_ffmpeg) {
		global $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_FFMPEG[$ps_path_to_ffmpeg];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_FFMPEG = array();
		}
		if (!$ps_path_to_ffmpeg || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_ffmpeg)) || !file_exists($ps_path_to_ffmpeg)) { return false; }

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
	function caMediaPluginGhostscriptInstalled($ps_path_to_ghostscript) {
		global $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT[$ps_path_to_ghostscript];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_GHOSTSCRIPT = array();
		}
		if (!trim($ps_path_to_ghostscript) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_ghostscript)) || !file_exists($ps_path_to_ghostscript)) { return false; }
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
	function caMediaPluginPdftotextInstalled($ps_path_to_pdf_to_text) {
		if (!trim($ps_path_to_pdf_to_text) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_pdf_to_text))  || !file_exists($ps_path_to_pdf_to_text)) { return false; }
		exec($ps_path_to_pdf_to_text." -v 2> /dev/null", $va_output, $vn_return);
		if (($vn_return >= 0) && ($vn_return < 127)) {
			return true;
		}
		return false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if AbiWord executable is available at specified path
	 * 
	 * @param $ps_path_to_abiword - full path to AbiWord including executable name
	 * @return boolean - true if available, false if not
	 */
	function caMediaPluginAbiwordInstalled($ps_path_to_abiword) {
		if (!trim($ps_path_to_abiword) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_abiword)) || !file_exists($ps_path_to_abiword)) { return false; }
		exec($ps_path_to_abiword." --version 2> /dev/null", $va_output, $vn_return);
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
	function caMediaPluginLibreOfficeInstalled($ps_path_to_libreoffice) {
		if (!trim($ps_path_to_libreoffice) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_path_to_libreoffice)) || !file_exists($ps_path_to_libreoffice)) { return false; }
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
	function caMediaInfoInstalled($ps_mediainfo_path) {
		global $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO = array();
		}
		if (!trim($ps_mediainfo_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_mediainfo_path)) || !file_exists($ps_mediainfo_path)) { return false; }
		exec($ps_mediainfo_path." --Help > /dev/null",$va_output,$vn_return);
		if($vn_return == 255) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_mediainfo_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Detects if PDFMiner (http://www.unixuser.org/~euske/python/pdfminer/index.html) is installed in the given path.
	 * @param string $ps_pdfminer_path path to PDFMiner
	 */
	function caPDFMinerInstalled($ps_pdfminer_path) {
		global $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO;
		if (isset($_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_pdfminer_path])) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_pdfminer_path];
		} else {
			$_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO = array();
		}
		if (!trim($ps_pdfminer_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_pdfminer_path)) || !file_exists($ps_pdfminer_path)) { return false; }
		
		if (!file_exists($ps_pdfminer_path."/pdf2txt.py")) { return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_pdfminer_path] = false; }
		exec($ps_pdfminer_path."/pdf2txt.py > /dev/null",$va_output,$vn_return);
		if($vn_return == 100) {
			return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_pdfminer_path] = true;
		}
		return $_MEDIAHELPER_PLUGIN_CACHE_MEDIAINFO[$ps_pdfminer_path] = false;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Extracts media metadata using "mediainfo"
	 * @param string $ps_mediainfo_path path to mediainfo binary
	 * @param string $ps_filepath file path
	 */
	function caExtractMetadataWithMediaInfo($ps_mediainfo_path,$ps_filepath){
		if (!trim($ps_mediainfo_path) || (preg_match("/[^\/A-Za-z0-9\.:]+/", $ps_mediainfo_path)) || !file_exists($ps_mediainfo_path)) { return false; }
		exec($ps_mediainfo_path." ".caEscapeShellArg($ps_filepath),$va_output,$vn_return);
		$vs_cat = "GENERIC";
		$va_return = array();
		foreach($va_output as $vs_line){
			$va_split = explode(":",$vs_line);
			$vs_left = trim($va_split[0]);
			$vs_right = trim($va_split[1]);
			if(strlen($vs_right)==0){ // category line
				$vs_cat = strtoupper($vs_left);
				continue;
			}
			if(strlen($vs_left) && strlen($vs_right)) {
				if($vs_left!="Complete name"){ // we probably don't want to display temporary filenames
					$va_return[$vs_cat][$vs_left] = $vs_right;
				}
			}
		}

		return $va_return;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Perform mapping of extracted media metadata to CollectiveAccess bundles.
	 *
	 * @param BaseModel $po_instance Model instance to insert extracted metadata into
	 * @param array $pa_metadata Extracted metadata
	 * @param int $pn_locale_id The current locale as a numeric locale_id
	 * @return bool True extracted metadata was mapped and the model changed, false if no change was made to the model
	 */
	function caExtractEmbeddedMetadata($po_instance, $pa_metadata, $pn_locale_id) {
		if (!is_array($pa_metadata)) { return false; }
		$vb_did_mapping = false;
		if (!($vs_media_metadata_config = $po_instance->getAppConfig()->get('media_metadata'))) { return false; }
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
				if ($vs_raw_date = $va_exif_data['IFD0']['DateTime']) {
					$va_date_tmp = preg_split('![: ]+!', $vs_raw_date); 
					$vs_date = 	$va_date_tmp[0].'-'.$va_date_tmp[1].'-'.$va_date_tmp[2].'T'.$va_date_tmp[3].':'.$va_date_tmp[4].':'.$va_date_tmp[5];
					foreach($va_date_elements as $vs_element) {
						$va_tmp = explode('.', $vs_element);
						$po_instance->addAttribute(array($va_tmp[1] => $vs_date, 'locale_id' => $pn_locale_id), $va_tmp[1]);
					}
					$vb_did_mapping = true;
				}
			}
			
			if (is_array($va_date_containers)) {
				$t_list = new ca_lists();
				if ($vs_raw_date = $va_exif_data['IFD0']['DateTime']) {
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
			
			foreach($va_attr as $vs_attr) {
				$va_metadata =& $pa_metadata;
				foreach($va_tmp as $vs_el) {	
					if (isset($va_metadata[$vs_el])) {
						$va_metadata =& $va_metadata[$vs_el];
					} else {
						continue(2);
					}
				}
				
				if(is_array($va_metadata)) { $va_metadata = join(";", $va_metadata); }
				if (!trim($va_metadata)) { continue(2); }
				
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
							$po_instance->replaceAttribute(
								array(	'locale_id' => $pn_locale_id, 
										$vs_attr => $va_metadata),
								$vs_attr);
						}
						
				}
				$vb_did_mapping = true;
			}
		}
			
		return $vb_did_mapping;
	}
	
	# ------------------------------------------------------------------------------------------------
	/**
	 * Embed XMP metadata into representation media. Embedding is performed on a copy of the representation media and placed
	 * into the system tmp directory. The original media is never modified.
	 *
	 * @param BaseModel $po_object ca_objects instance to pull metadata from for embedding
	 * @param BaseModel $po_representation ca_object_representations instance to pull metadata from for embedding
	 * @param string $ps_version Version of media to embed into. If omitted "original" version is used.
	 * @return string Path to copy of media with embedded metadata. False is returned in the embedding failed.
	 */
	function caEmbedMetadataIntoRepresentation($po_object, $po_representation, $ps_version="original") {
		if (!($vs_media_metadata_config = $po_representation->getAppConfig()->get('media_metadata'))) { return false; }
		$o_metadata_config = Configuration::load($vs_media_metadata_config);
		
		$vs_mimetype = $po_representation->getMediaInfo('media', $ps_version, 'MIMETYPE'); 
		if (!in_array($vs_mimetype, array('image/jpeg'))) { return false; }		// Don't try to embed in files other than JPEGs
		$vs_filepath = $po_representation->getMediaPath('media', $ps_version);
		if (!file_exists($vs_filepath)) { return false; }
		
		$va_mappings = $o_metadata_config->getAssoc('export_mappings');
		$o_xmp = new XMPParser();
		
		copy($vs_filepath, $vs_tmp_filepath = caGetTempDirPath()."/".time().md5($vs_filepath));
		
		$o_xmp->parse($vs_tmp_filepath);
		$o_xmp->initMetadata();
		
		if (is_object($po_object) && isset($va_mappings['ca_objects']) && is_array($va_mappings['ca_objects'])) {
			$va_mapping = $va_mappings['ca_objects'];
			$vs_type = $po_object->getTypeCode();
			if (isset($va_mapping[$vs_type]) && is_array($va_mapping[$vs_type])) {
				$va_mapping = $va_mapping[$vs_type];
			} else {
				if (isset($va_mapping['__default__']) && is_array($va_mapping['__default__'])) {
					$va_mapping = $va_mapping['__default__'];
				} else {
					return null;
				}
			}
			
			if (is_array($va_mapping)) {
				foreach($va_mapping as $vs_xmp => $va_ca) {
					$va_tmp = explode(':', $vs_xmp);
					if (sizeof($va_tmp) > 1) { $vs_xmp = $va_tmp[1];} 
					foreach($va_ca as $vs_ca => $va_opts) {
						if (preg_match('!^static:!', $vs_ca)) {
							$vs_val = preg_replace('!^static:!', '', $vs_ca);
						} else {
							$vs_val = $po_object->get($vs_ca, $va_opts);
						}
						if ($vs_val) { $o_xmp->set($vs_xmp, $vs_val); }
					}
				}
			}
		}
		
		if (is_object($po_representation) && isset($va_mappings['ca_object_representations']) && is_array($va_mappings['ca_object_representations'])) {
			$va_mapping = $va_mappings['ca_object_representations'];
			$vs_type = $po_representation->getTypeCode();
			if (isset($va_mapping[$vs_type]) && is_array($va_mapping[$vs_type])) {
				$va_mapping = $va_mapping[$vs_type];
			} else {
				if (isset($va_mapping['__default__']) && is_array($va_mapping['__default__'])) {
					$va_mapping = $va_mapping['__default__'];
				} else {
					return null;
				}
			}
			
			if (is_array($va_mapping)) {
				foreach($va_mapping as $vs_xmp => $va_ca) {
					$va_tmp = explode(':', $vs_xmp);
					if (sizeof($va_tmp) > 1) { $vs_xmp = $va_tmp[1];} 
					foreach($va_ca as $vs_ca => $va_opts) {
						if (preg_match('!^static:!', $vs_ca)) {
							$vs_val = preg_replace('!^static:!', '', $vs_ca);
						} else {
							$vs_val = $po_representation->get($vs_ca, $va_opts);
						}
						if ($vs_val) { $o_xmp->set($vs_xmp, $vs_val); }
					}
				}
			}
		}
		$o_xmp->write();
		return $vs_tmp_filepath;
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
			$o_icon_config = Configuration::load($o_config->get('default_media_icons'));
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
			$o_icon_config = Configuration::load($o_config->get('default_media_icons'));
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
			$o_icon_config = Configuration::load($o_config->get('default_media_icons'));
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
		$o_icon_config = Configuration::load($o_config->get('default_media_icons'));
		
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
?>
