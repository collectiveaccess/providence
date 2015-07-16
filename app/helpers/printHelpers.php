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
			case 'bundles':
				return  __CA_APP_DIR__.'/printTemplates/bundles';
				break;
		}
		return null;
	}
	# ---------------------------------------
	/**
	 *
	 * @param string $ps_type
	 * @param array $pa_options Options include:
	 *		table =
	 *		type =
	 * 		elementCode =
	 *
	 * @return array
	 */
	function caGetAvailablePrintTemplates($ps_type, $pa_options=null) {
		$vs_template_path = caGetPrintTemplateDirectoryPath($ps_type);
		$vs_tablename = caGetOption('table', $pa_options, null);
		$vs_type = caGetOption('type', $pa_options, 'page');
		$vs_element_code = caGetOption('elementCode', $pa_options, null);
		$vb_for_html_select = caGetOption('forHTMLSelect', $pa_options, false);


		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $ps_type);
		if (ExternalCache::contains($vs_cache_key, 'PrintTemplates')) {
			$va_list = ExternalCache::fetch($vs_cache_key, 'PrintTemplates');
			if(
				(ExternalCache::fetch("{$vs_cache_key}_mtime", 'PrintTemplates') >= filemtime($vs_template_path)) &&
				(ExternalCache::fetch("{$vs_cache_key}_local_mtime", 'PrintTemplates') >= filemtime("{$vs_template_path}/local"))
			){
				//Debug::msg('[caGetAvailablePrintTemplates] cache hit');
				return $va_list;
			}
		}
		//Debug::msg('[caGetAvailablePrintTemplates] cache miss');

		$va_templates = array();
		foreach(array("{$vs_template_path}", "{$vs_template_path}/local") as $vs_path) {
			if(!file_exists($vs_path)) { continue; }

			if (is_resource($r_dir = opendir($vs_path))) {
				while (($vs_template = readdir($r_dir)) !== false) {
					if (in_array($vs_template, array(".", ".."))) { continue; }
					$vs_template_tag = pathinfo($vs_template, PATHINFO_FILENAME);
					if (is_array($va_template_info = caGetPrintTemplateDetails($ps_type, $vs_template_tag))) {
						if (caGetOption('type', $va_template_info, null) !== $vs_type)  { continue; }

						if ($vs_element_code && (caGetOption('elementCode', $va_template_info, null) !== $vs_element_code)) { continue; }

						if ($vs_tablename && (!in_array($vs_tablename, $va_template_info['tables'])) && (!in_array('*', $va_template_info['tables']))) {
							continue;
						}

						if (!is_dir($vs_path.'/'.$vs_template) && preg_match("/^[A-Za-z_]+[A-Za-z0-9_]*$/", $vs_template_tag)) {
							if ($vb_for_html_select) {
								$va_templates[$va_template_info['name']] = '_pdf_'.$vs_template_tag;
							} else {
								$va_templates[] = array(
									'name' => $va_template_info['name'],
									'code' => '_pdf_'.$vs_template_tag
								);
							}
						}
					}
				}
			}

			asort($va_templates);

			ExternalCache::save($vs_cache_key, $va_templates, 'PrintTemplates');
			ExternalCache::save("{$vs_cache_key}_mtime", filemtime($vs_template_path), 'PrintTemplates');
			ExternalCache::save("{$vs_cache_key}_local_mtime", filemtime("{$vs_template_path}/local"), 'PrintTemplates');
		}

		return $va_templates;
	}
	# ------------------------------------------------------------------
	/**
	 * @param $ps_type
	 * @param $ps_template
	 * @param null $pa_options
	 * @return array|bool|false|mixed
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

		$vs_cache_key = caMakeCacheKeyFromOptions($pa_options, $ps_type.'/'.$vs_template_path);
		if (ExternalCache::contains($vs_cache_key, 'PrintTemplateDetails')) {
			$va_list = ExternalCache::fetch($vs_cache_key, 'PrintTemplateDetails');
			if(ExternalCache::fetch("{$vs_cache_key}_mtime", 'PrintTemplateDetails') >= filemtime($vs_template_path)) {
				//Debug::msg('[caGetPrintTemplateDetails] cache hit');
				return $va_list;
			}
		}
		//Debug::msg('[caGetPrintTemplateDetails] cache miss');


		$vs_template = file_get_contents($vs_template_path);

		$va_info = array();
		foreach(array(
			"@name", "@type", "@pageSize", "@pageOrientation", "@tables",
			"@marginLeft", "@marginRight", "@marginTop", "@marginBottom",
			"@horizontalGutter", "@verticalGutter", "@labelWidth", "@labelHeight",
			"@elementCode"
		) as $vs_tag) {
			if (preg_match("!{$vs_tag}([^\n\n]+)!", $vs_template, $va_matches)) {
				$va_info[str_replace("@", "", $vs_tag)] = trim($va_matches[1]);
			} else {
				$va_info[str_replace("@", "", $vs_tag)] = null;
			}
		}
		$va_info['tables'] = preg_split("![,;]{1}!", $va_info['tables']);
		$va_info['path'] = $vs_template_path;

		ExternalCache::save($vs_cache_key, $va_info, 'PrintTemplateDetails');
		ExternalCache::save("{$vs_cache_key}_mtime", filemtime($vs_template_path), 'PrintTemplateDetails');

		return $va_info;
	}
	# ------------------------------------------------------------------
	/**
	 * Converts string quantity with units ($ps_value parameter) to a numeric quantity in
	 * points. Units are limited to inches, centimeters, millimeters, pixels and points as
	 * this function is primarily used to switch between units used when generating PDFs.
	 *
	 * @param $ps_value string The value to convert. Valid units are in, cm, mm, px and p. If units are invalid or omitted points are assumed.
	 *
	 * @return int Converted measurement in points.
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
	 * Converts string quantity with units ($ps_value parameter) to a numeric quantity in
	 * the units specified by the $ps_units parameter. Units are limited to inches, centimeters, millimeters, pixels and points as
	 * this function is primarily used to switch between units used when generating PDFs.
	 *
	 * @param $ps_value string The value to convert. Valid units are in, cm, mm, px and p. If units are invalid or omitted points are assumed.
	 * @param $ps_units string A valid measurement unit: in, cm, mm, px, p (inches, centimeters, millimeters, pixels, points) respectively.
	 *
	 * @return int Converted measurement. If the output units are omitted or otherwise not valid, pixels are assumed.
	 */
	function caConvertMeasurement($ps_value, $ps_units) {
		$vn_in_points = caConvertMeasurementToPoints($ps_value);
		
		if (!preg_match("/^([\d\.]+)[ ]*([A-Za-z]*)$/", $ps_value, $va_matches)) {
			return $vn_in_points;
		}
		
		switch(strtolower($ps_units)) {
			case 'in':
				return $vn_in_points/72;
				break;
			case 'cm':
				return $vn_in_points/28.346;
				break;
			case 'mm':
				return $vn_in_points/2.8346;
				break;
			default:
			case 'px':
			case 'p':
				return $vn_in_points;
				break;
		}
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
			array_shift($va_bits); // remove "barcode" identifier
			$vs_type = array_shift($va_bits);
			if (is_numeric($va_bits[0])) {
				$vn_size = (int)array_shift($va_bits);
				$vs_template = join(":", $va_bits);
			} else {
				$vn_size = 16;
				$vs_template = join(":", $va_bits);
			}

			$vs_tmp = caGenerateBarcode($po_result->getWithTemplate($vs_template, $pa_options), array('type' => $vs_type, 'height' => $vn_size));

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