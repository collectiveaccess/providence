<?php
/** ---------------------------------------------------------------------
 * app/helpers/printHelpers.php : 
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
	require_once(__CA_LIB_DIR__."/core/Print/Barcode.php");   
	require_once(__CA_LIB_DIR__."/core/Print/phpqrcode/qrlib.php");

	global $g_print_measurement_cache;
	$g_print_measurement_cache = array();
	
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return string
	 */
	function caGetPrintTemplateDirectoryPath($ps_type) {
		switch($ps_type) {
			case 'results': 
				return  __CA_APP_DIR__.'/printTemplates/results';
				break;
			case 'summary': 
				return  __CA_APP_DIR__.'/printTemplates/summary';
				break;
			case 'labels': 
				return  __CA_APP_DIR__.'/printTemplates/labels';
				break;
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return array
	 */
	function caGetAvailablePrintTemplates($ps_type, $pa_options=null) {
		$vs_template_path = caGetPrintTemplateDirectoryPath($ps_type);
		$vs_tablename = caGetOption('table', $pa_options, null);
		$vs_type = caGetOption('type', $pa_options, 'page');
		
		if ($o_cache = caGetCacheObject('caPrintTemplatesList_'.$ps_type)) {
			$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $ps_type);
			if (
				($va_list = $o_cache->load($vs_cache_key))
				&&
				(($vn_mtime = $o_cache->load("{$vs_cache_key}_mtime")) >= filemtime($vs_template_path))
				&&
				(($vn_mtime = $o_cache->load("{$vs_cache_key}_local_mtime")) >= filemtime("{$vs_template_path}/local"))
			) { return $va_list; }
		}

		$va_templates = array();
		foreach(array("{$vs_template_path}", "{$vs_template_path}/local") as $vs_path) {
			if(!file_exists($vs_path)) { continue; }
	
			if (is_resource($r_dir = opendir($vs_path))) {
				while (($vs_template = readdir($r_dir)) !== false) {
					if (in_array($vs_template, array(".", ".."))) { continue; }
					$vs_template_tag = pathinfo($vs_template, PATHINFO_FILENAME);
					if (is_array($va_template_info = caGetPrintTemplateDetails($ps_type, $vs_template_tag))) {
						if (caGetOption('type', $va_template_info, null) !== $vs_type)  { continue; }
						
						if ($vs_tablename && (!in_array($vs_tablename, $va_template_info['tables'])) && (!in_array('*', $va_template_info['tables']))) {
							continue;
						}
			
						if (!is_dir($vs_path.'/'.$vs_template) && preg_match("/^[A-Za-z_]+[A-Za-z0-9_]*$/", $vs_template_tag)) {
							$va_templates[] = array(
								'name' => $va_template_info['name'],
								'code' => '_pdf_'.$vs_template_tag
							);
						}
					}
				}
			}

			sort($va_templates);
			
			if ($o_cache) {
				$o_cache->save($va_templates, $vs_cache_key);
				$o_cache->save(filemtime($vs_template_path), "{$vs_cache_key}_mtime");
				$o_cache->save(filemtime("{$vs_template_path}/local"), "{$vs_cache_key}_local_mtime");
			}
		}

		return $va_templates;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 *
	 * @return array
	 */
	function caGetPrintTemplateDetails($ps_type, $ps_template, $pa_options=null) {
		$vs_template_path = caGetPrintTemplateDirectoryPath($ps_type);

		if (file_exists("{$vs_template_path}/local/{$ps_template}.php")) {
			$vs_template_path = "{$vs_template_path}/local/{$ps_template}.php";
		} elseif(file_exists("{$vs_template_path}/{$ps_template}.php")) {
			$vs_template_path = "{$vs_template_path}/{$ps_template}.php";
		} else {
			return false;
		}
		
		if ($o_cache = caGetCacheObject('caPrintTemplatesList_'.$ps_type)) {
			$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $ps_type.'/'.$vs_template_path);
			
			if (
				($va_info = $o_cache->load($vs_cache_key))
				&&
				(($vn_mtime = $o_cache->load("{$vs_cache_key}_mtime")) >= filemtime($vs_template_path))
			) { return $va_info; }
		}
		
		$vs_template = file_get_contents($vs_template_path);
		
		$va_info = array();
		foreach(array(
			"@name", "@type", "@pageSize", "@pageOrientation", "@tables", 
			"@marginLeft", "@marginRight", "@marginTop", "@marginBottom",
			"@horizontalGutter", "@verticalGutter", "@labelWidth", "@labelHeight"
		) as $vs_tag) {
			if (preg_match("!{$vs_tag}([^\n\n]+)!", $vs_template, $va_matches)) {
				$va_info[str_replace("@", "", $vs_tag)] = trim($va_matches[1]);
			} else {
				$va_info[str_replace("@", "", $vs_tag)] = null;
			}
		}
		$va_info['tables'] = preg_split("![,;]{1}!", $va_info['tables']);
		$va_info['path'] = $vs_template_path;
		
		if ($o_cache) {
			$o_cache->save($va_info, $vs_cache_key);
			$o_cache->save(filemtime($vs_template_path), "{$vs_cache_key}_mtime");
		}
		
		return $va_info;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function caConvertMeasurementToPoints($ps_value) {
		global $g_print_measurement_cache;
		
		if (isset($g_print_measurement_cache[$ps_value])) { return $g_print_measurement_cache[$ps_value]; }
		
		if (!preg_match("/^([\d\.]+)[ ]*([A-Za-z]*)$/", $ps_value, $va_matches)) {
			return $g_print_measurement_cache[$ps_value] = $ps_value;
		}
		
		switch(strtolower($va_matches[2])) {
			case 'in':
				$ps_value_in_points = $va_matches[1] * 72;
				break;
			case 'cm':
				$ps_value_in_points = $va_matches[1] * 28.346;
				break;
			case 'mm':
				$ps_value_in_points = $va_matches[1] * 2.8346;
				break;
			case '':
			case 'px':
			case 'p':
				$ps_value_in_points = $va_matches[1];
				break;
			default:
				$ps_value_in_points = $ps_value;
				break;
		}
		
		return $g_print_measurement_cache[$ps_value] = $ps_value_in_points;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function caGenerateBarcode($ps_value, $pa_options=null) {
		$ps_barcode_type = caGetOption('type', $pa_options, 'code128', array('forceLowercase' => true));
		$pn_barcode_height = caConvertMeasurementToPoints(caGetOption('height', $pa_options, '9px'));
		
		$vs_tmp = null;
		switch($ps_barcode_type) {
			case 'qr':
			case 'qrcode':
				$vs_tmp = tempnam(caGetTempDirPath(), 'caQRCode');
				$vs_tmp2 = tempnam(caGetTempDirPath(), 'caQRCodeResize');

				if (!defined('QR_LOG_DIR')) { define('QR_LOG_DIR', false); }
				
				if (($pn_barcode_height < 1) || ($pn_barcode_height > 8)) {
					$pn_barcode_height = 1;
				}
				QRcode::png($ps_value, "{$vs_tmp}.png", QR_ECLEVEL_H, $pn_barcode_height);	
				return $vs_tmp;
				break;
			case 'code128':
			case 'code39':
			case 'ean13':
			case 'int25':
			case 'postnet':
			case 'upca':
				$o_barcode = new Barcode();
				$vs_tmp = tempnam(caGetTempDirPath(), 'caBarCode');
				if(!($va_dimensions = $o_barcode->draw($ps_value, "{$vs_tmp}.png", $ps_barcode_type, 'png', $pn_barcode_height))) { return null; }
				return $vs_tmp;
				break;
			default:
				// invalid barcode
				break;
		}
		
		return null;
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	function caParseBarcodeViewTag($ps_tag, $po_view, $po_result, $pa_options=null) {
		$vs_tmp = null;
		if (substr($ps_tag, 0, 7) == 'barcode') {
			$o_barcode = new Barcode();
			
			// got a barcode
			$va_bits = explode(":", $ps_tag);
			if (is_numeric($va_bits[2])) { 
				$vn_size = (int)$va_bits[2]; 
				$vs_template = $va_bits[3];
			} else { 
				$vn_size = 16;
				$vs_template = $va_bits[2];
			}
			$vs_tmp = caGenerateBarcode($po_result->getWithTemplate($vs_template, $pa_options), array('type' => $va_bits[1], 'height' => $vn_size));
		
			$po_view->setVar($ps_tag, "<img src='{$vs_tmp}.png'/>");
		}
		return $vs_tmp;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function caDoPrintViewTagSubstitution($po_view, $po_result, $ps_template_path, $pa_options=null) {
	
		$va_tag_list = $po_view->getTagList($ps_template_path);				// get list of tags in view
		$po_view->clearViewTagsVars($ps_template_path);
		$va_defined_vars = array_keys($po_view->getAllVars());				// get list defined vars (we don't want to copy over them)
		
		$va_barcode_files_to_delete = array();
		//
		// Tag substitution
		//
		// Views can contain tags in the form {{{tagname}}}. Some tags, such as "itemType" and "detailType" are defined by
		// the detail controller. More usefully, you can pull data from the item being detailed by using a valid "get" expression
		// as a tag (Eg. {{{ca_objects.idno}}}. Even more usefully for some, you can also use a valid bundle display template
		// (see http://docs.collectiveaccess.org/wiki/Bundle_Display_Templates) as a tag. The template will be evaluated in the 
		// context of the item being detailed.
		//
		foreach($va_tag_list as $vs_tag) {
			if (in_array($vs_tag, $va_defined_vars)) { continue; }
			
			if ($vs_barcode_file = caParseBarcodeViewTag($vs_tag, $po_view, $po_result, $pa_options)) {
				$va_barcode_files_to_delete[] = $vs_barcode_file;
			} elseif ((strpos($vs_tag, "^") !== false) || (strpos($vs_tag, "<") !== false)) {
				$po_view->setVar($vs_tag, $po_result->getWithTemplate($vs_tag, $pa_options));
			} elseif (strpos($vs_tag, ".") !== false) {
				$po_view->setVar($vs_tag, $po_result->get($vs_tag, $pa_options));
			} else {
				$po_view->setVar($vs_tag, "?{$vs_tag}");
			}
		}
		
		return $va_barcode_files_to_delete;
	}
	# ---------------------------------------