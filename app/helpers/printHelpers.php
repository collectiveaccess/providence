<?php
/** ---------------------------------------------------------------------
 * app/helpers/printHelpers.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2026 Whirl-i-Gig
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
use Com\Tecnick\Barcode;
use Laminas\Stdlib\Glob;

require_once(__CA_LIB_DIR__."/Print/PDFRenderer.php");

global $g_print_measurement_cache;
$g_print_measurement_cache = [];

# ---------------------------------------
/**
 *
 *
 * @return array|string
 */
function caGetPrintTemplateDirectoryPath($type) {
	if(caUseLegacyPrintTemplatesSystem()) { 
		$paths = [];
		switch($type) {
			case 'results':
				if (is_dir(__CA_THEME_DIR__.'/printTemplates/results')) { $paths[] = __CA_THEME_DIR__.'/printTemplates/results'; }
				$paths[] = __CA_APP_DIR__.'/printTemplates/results';
				break;
			case 'summary':
				if (is_dir(__CA_THEME_DIR__.'/printTemplates/summary')) { $paths[] = __CA_THEME_DIR__.'/printTemplates/summary'; }
				$paths[] = __CA_APP_DIR__.'/printTemplates/summary';
				break;
			case 'labels':
				if (is_dir(__CA_THEME_DIR__.'/printTemplates/labels')) { $paths[] = __CA_THEME_DIR__.'/printTemplates/labels'; } 
				$paths[] = __CA_APP_DIR__.'/printTemplates/labels';
				break;
			case 'bundles':
				if(is_dir(__CA_THEME_DIR__.'/printTemplates/bundles')) { $paths[] = __CA_THEME_DIR__.'/printTemplates/bundles'; }
				$paths[] = __CA_APP_DIR__.'/printTemplates/bundles';
				break;
			case 'sets':
				if(is_dir(__CA_THEME_DIR__.'/printTemplates/sets')) { $paths[] = __CA_THEME_DIR__.'/printTemplates/sets'; }
				$paths[] = __CA_APP_DIR__.'/printTemplates/sets';
				break;
		}
		return (sizeof($paths) > 0) ? $paths : null;
	} else {
		return [__CA_THEME_DIR__.'/printables/templates'];
	}
}
# ---------------------------------------
/**
 *
 * @param string $type
 * @param array $options Options include:
 *		table =
 *		type =
 * 		elementCode =
 *      showOnlyIn = 
 *      restrictToTypes = 
 *
 * @return array
 */
function caGetAvailablePrintTemplates($type, $options=null) {
	$config = Configuration::load();
	$use_legacy = caUseLegacyPrintTemplatesSystem();
	$no_cache = caGetOption('noCache', $options, false);
	$no_cache = true;
	
	if (!is_array($template_paths = caGetPrintTemplateDirectoryPath($type))) { $template_paths = []; }
	
	$restrict_to_types = [];
	if ($tablename = caGetOption('table', $options, null)) {
		if(($restrict_to_types = caGetOption('restrictToTypes', $options, false)) && !is_array($restrict_to_types)) {
			$restrict_to_types = [$restrict_to_types];
		}
		if (!is_array($restrict_to_types)) { $restrict_to_types = []; }
		$restrict_to_types = caMakeTypeList($tablename, $restrict_to_types);
	}
	if(!is_array($restrict_to_types)) { $restrict_to_types = []; }
	
	$ttype = caGetOption('type', $options, 'page');
	$element_code = caGetOption('elementCode', $options, null);
	$for_html_select = caGetOption('forHTMLSelect', $options, false);    
	if (!is_array($show_only_in = caGetOption('showOnlyIn', $options, null))) {
		$show_only_in = array_map(function($v) { return trim($v); }, explode(',', $show_only_in));
	}
	
	$cache_key = caMakeCacheKeyFromOptions($options ?? [], $type);
	
	$templates = [];
	$needs_caching = false;
	$template_rev = $local_rev = null;
	
	$cached_list = (ExternalCache::contains($cache_key, 'PrintTemplates')) ? ExternalCache::fetch($cache_key, 'PrintTemplates') : null;
		
	foreach($template_paths as $template_path) {
		foreach(array("{$template_path}", "{$template_path}/local") as $path) {
			if(!file_exists($path)) { continue; }
	
			if (is_array($cached_list) && !$no_cache) {
				$f = array_map("filemtime", Glob::glob("{$template_path}/*.{php,css}", Glob::GLOB_BRACE));
				sort($f);
				$template_rev = file_exists($template_path) ? array_pop($f) : 0;
				
				$f = array_map("filemtime", Glob::glob("{$template_path}/local/*.{php,css}", Glob::GLOB_BRACE));
				sort($f);
				$local_rev = file_exists("{$template_path}/local") ? array_pop($f) : 0;
				
				if(
					(ExternalCache::fetch("{$cache_key}_mtime", 'PrintTemplates') >= $template_rev) &&
					(ExternalCache::fetch("{$cache_key}_local_mtime", 'PrintTemplates') >= $local_rev)
				){
					$templates = array_merge($templates, $cached_list);
					continue;
				}
			}

			if (is_resource($r_dir = opendir($path))) {
				while (($template = readdir($r_dir)) !== false) {
					if (in_array($template, array(".", ".."))) { continue; }
					$template_tag = pathinfo($template, PATHINFO_FILENAME);
					if (is_array($template_info = caGetPrintTemplateDetails($type, $template_tag))) {
						if (caGetOption('type', $template_info, null) !== $ttype)  { continue; }
						if (caGetOption('disabled', $template_info, false, ['castTo' => 'bool'])) { continue; }
						
						if(!$use_legacy) {
							// filter themes "printables" templates using @contexts directive
							$contexts = caGetOption('contexts', $template_info, null);
							if(is_array($contexts) && !in_array($type, $contexts, true)) { continue; }
						}
						
						if (!is_array($template_restrict_to_types = caGetOption('restrictToTypes', $template_info, null))) { $template_restrict_to_types = []; }
						$c = (array_intersect($restrict_to_types, $template_restrict_to_types));
						
						if (
							sizeof($restrict_to_types) && sizeof($template_restrict_to_types) && 
							(is_array($c) && !sizeof($c)))
						{ 
							continue; 
						}
						$template_show_only_in = array_filter(array_map(function($v) { return trim($v); }, explode(",", caGetOption('showOnlyIn', $template_info, null))), function($v) { return (bool)strlen($v);});
						if(is_array($show_only_in) && (sizeof($show_only_in) > 0) && is_array($template_show_only_in) && (sizeof($template_show_only_in) > 0) && !sizeof(array_intersect($template_show_only_in, $show_only_in))) { continue; }
						
						if ($element_code && (caGetOption('elementCode', $template_info, null) !== $element_code)) { continue; }

						if ($tablename && (!in_array($tablename, $template_info['tables'])) && (!in_array('*', $template_info['tables']))) {
							continue;
						}
						if ($tablename && (!in_array($tablename, $template_info['tables'])) && (!in_array('*', $template_info['tables']))) {
							continue;
						}

						if (!is_dir($path.'/'.$template) && preg_match("/^[A-Za-z_\-]+[A-Za-z0-9_\-]*$/", $template_tag)) {
							if ($for_html_select && !isset($templates[$template_info['name']])) {
								$templates[$template_info['name']] = '_'.($template_info['fileFormat'] ?? 'pdf').'_'.$template_tag;
							} elseif (!isset($templates[$template_tag])) {
								$templates[$template_tag] = array(
									'name' => $template_info['name'],
									'code' => '_'.$template_info['fileFormat'].'_'.$template_tag,
									'type' => $template_info['fileFormat'],
									'generic' => $template_info['generic'] ? 1 : 0,
									'standalone' => $template_info['standalone'] ? 1 : 0
								);
							}
							
							$needs_caching = true;
						}
					}
				}
			}
			
			if(sizeof($templates) == 0) { $needs_caching = true; }

			asort($templates);
		}
	}
	
	if ($needs_caching) {	
		ExternalCache::save($cache_key, $templates, 'PrintTemplates');
		ExternalCache::save("{$cache_key}_mtime", $template_rev, 'PrintTemplates');
		ExternalCache::save("{$cache_key}_local_mtime", $local_rev, 'PrintTemplates');
	}
	return $templates;
}
# ------------------------------------------------------------------
/**
 * @param $type
 * @param $template
 * @param null $options
 * @return array|bool|false|mixed
 */
function caGetPrintTemplateDetails($type, $template, $options=null) {
	$use_legacy = caUseLegacyPrintTemplatesSystem();
	
	if (!is_array($template_paths = caGetPrintTemplateDirectoryPath($type))) { return null; }
	
	// strip format prefix if present
	$template = caProcessTemplateName($template);
	
	$info = [];
	foreach($template_paths as $template_path) {
		if (file_exists("{$template_path}/local/{$template}.php")) {
			$template_path = "{$template_path}/local/{$template}.php";
		} elseif(file_exists("{$template_path}/{$template}.php")) {
			$template_path = "{$template_path}/{$template}.php";
		} elseif(is_numeric($template) && file_exists("{$template_path}/display.php")) {
			$template_path = "{$template_path}/display.php";
		} elseif(is_numeric($template) && file_exists("{$template_path}/local/display.php")) {
			$template_path = "{$template_path}/local/display.php";
		} else {
			continue;
		}

		$cache_key = caMakeCacheKeyFromOptions($options ?? [], $type.'/'.$template_path);
		if (ExternalCache::contains($cache_key, 'PrintTemplateDetails')) {
			$list = ExternalCache::fetch($cache_key, 'PrintTemplateDetails');
			
			if(ExternalCache::fetch("{$cache_key}_mtime", 'PrintTemplateDetails') >= filemtime($template_path)) {
				return $list;
			}
		}

		$template = file_get_contents($template_path);

		$info = [];
		foreach(array(
			"@name", "@type", "@pageSize", "@pageOrientation", "@tables", "@restrictToTypes",
			"@marginLeft", "@marginRight", "@marginTop", "@marginBottom",
			"@horizontalGutter", "@verticalGutter", "@labelWidth", "@labelHeight",
			"@elementCode", "@showOnlyIn", "@filename", "@fileFormat", "@generic", "@standalone",
			"@disabled", "@param", "@backgroundThreshold", "@includeHeaderFooter", "@contexts"
		) as $tag) {
			$tag = str_replace("@", "", $tag);
			switch($tag) {
				case 'param':
					if (preg_match_all("!@{$tag}[ ]+([^ ]+)[ ]+([^\n\n]+)!", $template, $matches)) {
						foreach($matches[1] as $i => $param_name) {
							if(!is_array($options = json_decode($matches[2][$i], true))) { continue; }
							$info['params'][$param_name] = $options;
						}
					}
					break;
				case 'backgroundThreshold':
					// maximum number of items to process in output before forcing background processing
					if (preg_match("!@{$tag}([^\n\n]+)!", $template, $matches)) {
						$info[$tag] = (int)$matches[1];
					}
					break;
				default:
					if (preg_match("!@{$tag}([^\n\n]+)!", $template, $matches)) {
						$info[$tag] = trim($matches[1]);
					} else {
						$info[$tag] = null;
					}
					break;
			}
		}
		if (!$info['fileFormat']) { $info['fileFormat'] = 'pdf'; }    // pdf is assumed for templates without a specific file format
		$info['tables'] = preg_split("![,;]{1}!", trim($info['tables']));
		
		if (trim($info['restrictToTypes'])) {
			$info['restrictToTypes'] = preg_split("![,;]{1}!", trim($info['restrictToTypes']));
		}
		if (trim($info['contexts'])) {
			$info['contexts'] = preg_split("![,;]{1}!", trim($info['contexts']));
		}
		$info['path'] = $template_path;
		$info['identifier'] = $template;

		ExternalCache::save($cache_key, $info, 'PrintTemplateDetails');
		ExternalCache::save("{$cache_key}_mtime", filemtime($template_path), 'PrintTemplateDetails');
		
		return $info;
	}
	return null;
}
# ------------------------------------------------------------------
/**
 * Converts string quantity with units ($value parameter) to a numeric quantity in
 * points. Units are limited to inches, centimeters, millimeters, pixels and points as
 * this function is primarily used to switch between units used when generating PDFs.
 *
 * @param $value string The value to convert. Valid units are in, cm, mm, px and p. If units are invalid or omitted points are assumed.
 * @param $options array Options include:
 *		dpi = dots-per-inch factor to use when converting physical units (in, cm, etc.) to points [Default is 72dpi]
 *		ppi = synonym for dpi option
 * @return int Converted measurement in points.
 */
function caConvertMeasurementToPoints($value, $options=null) {
	global $g_print_measurement_cache;

	if (isset($g_print_measurement_cache[$value])) { return $g_print_measurement_cache[$value]; }

	if (!preg_match("/^([\d\.]+)[ ]*([A-Za-z]*)$/", $value, $matches)) {
		return $g_print_measurement_cache[$value] = $value;
	}
	
	$dpi = caGetOption('dpi', $options, caGetOption('ppi', $options, 72));

	switch(strtolower(trim($matches[2]))) {
		case 'in':
			$value_in_points = $matches[1] * $dpi;
			break;
		case 'cm':
			$value_in_points = $matches[1] * ($dpi/2.54);
			break;
		case 'mm':
			$value_in_points = $matches[1] * ($dpi/25.4);
			break;
		case '':
		case 'px':
		case 'p':
			$value_in_points = $matches[1];
			break;
		default:
			$value_in_points = $value;
			break;
	}

	return $g_print_measurement_cache[$value] = $value_in_points;
}
# ------------------------------------------------------------------
/**
 * Converts string quantity with units ($value parameter) to a numeric quantity in
 * the units specified by the $units parameter. Units are limited to inches, centimeters, millimeters, pixels and points as
 * this function is primarily used to switch between units used when generating PDFs.
 *
 * @param $value string The value to convert. Valid units are in, cm, mm, px and p. If units are invalid or omitted points are assumed.
 * @param $units string A valid measurement unit: in, cm, mm, px, p (inches, centimeters, millimeters, pixels, points) respectively.
 *
 * @return int Converted measurement. If the output units are omitted or otherwise not valid, pixels are assumed.
 */
function caConvertMeasurement($value, $units) {
	$in_points = caConvertMeasurementToPoints($value);
	
	if (!preg_match("/^([\d\.]+)[ ]*([A-Za-z]*)$/", $value, $matches)) {
		return $in_points;
	}
	
	switch(strtolower($units)) {
		case 'in':
			return $in_points/72;
			break;
		case 'cm':
			return $in_points/28.3465;
			break;
		case 'mm':
			return $in_points/2.83465;
			break;
		default:
		case 'px':
		case 'p':
			return $in_points;
			break;
	}
}
# ------------------------------------------------------------------
/**
 * Converts string quantity with units ($value parameter) to a numeric quantity in
 * the units specified by the $units parameter. Units are limited to inches, centimeters, millimeters, pixels and points as
 * this function is primarily used to switch between units used when generating PDFs.
 *
 * If the output units are omitted or otherwise not valid, pixels are assumed.
 *
 * @param $value string The value to convert. Valid units are in, cm, mm, px and p. If units are invalid or omitted points are assumed.
 * @param $units string A valid measurement unit: in, cm, mm, px, p (inches, centimeters, millimeters, pixels, points) respectively.
 *
 * @return array Converted measurement as array with two keys: value and units. 
 */
function caParseMeasurement($value, $options=null) {
	if (!preg_match("/^([\d\.]+)[ ]*([A-Za-z]*)$/", $value, $matches)) {
		return null;
	}

	switch(strtolower($matches[2])) {
		case 'in':
		case 'cm':
		case 'mm':
		case 'px':
		case 'p':
			return array('value' => $matches[1], 'units' => $matches[2]);
			break;
		default:
			return null;
			break;
	}

}
# ------------------------------------------------------------------
/**
 *
 */
function caGenerateBarcode(string $value, $options=null) {
	$barcode_type = caGetOption('type', $options, 'code128', array('forceLowercase' => true));
	$barcode_height = caConvertMeasurementToPoints(caGetOption('height', $options, '9px'));

	if ($barcode_height < 10) { $barcode_height *= 3; }
	
	$barcode_width = null;
	if($info = caBarcodeInfo($barcode_type)) {
		if($info[2]) { $barcode_width = $barcode_height; }	// is square format
		$barcode = new \Com\Tecnick\Barcode\Barcode();
		$b = $barcode->getBarcodeObj(
			$info[3],			// barcode type and additional comma-separated parameters
			$value,				// data string to encode
			$barcode_width,		// bar width (use absolute or negative value as multiplication factor)
			$barcode_height,	// bar height (use absolute or negative value as multiplication factor)
			'black',			// foreground color
			[0,0,0,0]			// padding (use absolute or negative values as multiplication factors)
			)->setBackgroundColor('white'); // background color

		return $b->getHtmlDiv();
	}
	return null;
}
# ------------------------------------------------------------------
/**
 *
 */
function caBarcodeInfo(string $type, ?array $options=null) : ?array {
	$syns = [
		'QR' => 'QRCODE',
		'CODE128' => 'C128',
		'INT25' => 'I25',
	];
	$type = $syns[strtoupper($type)] ?? $type;
	$codes = [
		'C128A'      => ['0123456789', 'CODE 128 A', false],
		'C128B'      => ['0123456789', 'CODE 128 B', false],
		'C128C'      => ['0123456789', 'CODE 128 C', false],
		'C128'       => ['0123456789', 'CODE 128', false],
		'C39E+'      => ['0123456789', 'CODE 39 EXTENDED + CHECKSUM', false],
		'C39E'       => ['0123456789', 'CODE 39 EXTENDED', false],
		'C39+'       => ['0123456789', 'CODE 39 + CHECKSUM', false],
		'C39'        => ['0123456789', 'CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9', false],
		'C93'        => ['0123456789', 'CODE 93 - USS-93', false],
		'CODABAR'    => ['0123456789', 'CODABAR', false],
		'CODE11'     => ['0123456789', 'CODE 11', false],
		'EAN13'      => ['0123456789', 'EAN 13', false],
		'EAN2'       => ['12',         'EAN 2-Digits UPC-Based Extension', false],
		'EAN5'       => ['12345',      'EAN 5-Digits UPC-Based Extension', false],
		'EAN8'       => ['1234567',    'EAN 8', false],
		'I25+'       => ['0123456789', 'Interleaved 2 of 5 + CHECKSUM', false],
		'I25'        => ['0123456789', 'Interleaved 2 of 5', false],
		'IMB'        => ['01234567094987654321-01234567891', 'IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200', false],
		'IMBPRE'     => ['AADTFFDFTDADTAADAATFDTDDAAADDTDTTDAFADADDDTFFFDDTTTADFAAADFTDAADA', 'IMB pre-processed', false],
		'KIX'        => ['0123456789', 'KIX (Klant index - Customer index)', false],
		'MSI+'       => ['0123456789', 'MSI + CHECKSUM (modulo 11)', false],
		'MSI'        => ['0123456789', 'MSI (Variation of Plessey code)', false],
		'PHARMA2T'   => ['0123456789', 'PHARMACODE TWO-TRACKS', false],
		'PHARMA'     => ['0123456789', 'PHARMACODE', false],
		'PLANET'     => ['0123456789', 'PLANET', false],
		'POSTNET'    => ['0123456789', 'POSTNET', false],
		'RMS4CC'     => ['0123456789', 'RMS4CC (Royal Mail 4-state Customer Bar Code)', false],
		'S25+'       => ['0123456789', 'Standard 2 of 5 + CHECKSUM', false],
		'S25'        => ['0123456789', 'Standard 2 of 5', false],
		'UPCA'       => ['72527273070', 'UPC-A', false],
		'UPCE'       => ['725277', 'UPC-E', false],
		'LRAW'             => ['0101010101', '1D RAW MODE (comma-separated rows of 01 strings)', false],
		'SRAW'             => ['0101,1010',  '2D RAW MODE (comma-separated rows of 01 strings)', false],
		'PDF417'           => ['0123456789', 'PDF417 (ISO/IEC 15438:2006)', false],
		'QRCODE'           => ['0123456789', 'QR-CODE', true],
		'QRCODE,H,ST,0,0'  => ['abcdefghijklmnopqrstuvwxy0123456789', 'QR-CODE WITH PARAMETERS', true],
		'DATAMATRIX'       => ['0123456789', 'DATAMATRIX (ISO/IEC 16022) SQUARE', true],
		'DATAMATRIX,R'     => ['0123456789012345678901234567890123456789', 'DATAMATRIX Rectangular (ISO/IEC 16022) RECTANGULAR', false],
		'DATAMATRIX,S,GS1' => [chr(232).'01095011010209171719050810ABCD1234'.chr(232).'2110', 'GS1 DATAMATRIX (ISO/IEC 16022) SQUARE GS1', true],
		'DATAMATRIX,R,GS1' => [chr(232).'01095011010209171719050810ABCD1234'.chr(232).'2110', 'GS1 DATAMATRIX (ISO/IEC 16022) RECTANGULAR GS1', false],
	];
	
	if($info = ($codes[strtoupper($type)] ?? null)) {
		$info[] = strtoupper($type);
		return $info;
	}
	return null;
}
# -------------------------------------------------------
/**
 *
 */
function caParseBarcodeViewTag($tag, $view, $result, $options=null) {
	$tag = null;
	if (substr($tag, 0, 7) == 'barcode') {
		// got a barcode
		$bits = explode(":", $tag);
		array_shift($bits); // remove "barcode" identifier
		$type = array_shift($bits);
		if (is_numeric($bits[0]) || caParseMeasurement($bits[0])) {
			$size = array_shift($bits);
			$template = join(":", $bits);
		} else {
			$size = 16;
			$template = join(":", $bits);
		}

		$btag = caGenerateBarcode($result->getWithTemplate($template, $options), ['type' => $type, 'height' => $size]);

		$view->setVar($tag, $btag);
	}
	return $tag;
}
# ------------------------------------------------------------------
/**
 *
 */
function caDoPrintViewTagSubstitution($view, $result, $template_path, $options=null) {
	return caDoTemplateTagSubstitution($view, $result, $template_path, ['render' => false, 'barcodes' => true, 'clearVars' => true]);
}
# ---------------------------------------
/** 
 *
 */
function caGetPrintFormatsListAsHTMLForRelatedBundles($ps_id_prefix, $po_request, $pt_primary, $pt_related, $pt_relation, $placement_id) {
	$t_placement = new ca_editor_ui_bundle_placements($placement_id);

	$va_formats = caGetAvailablePrintTemplates('results', ['table' => $pt_related->tableName(), 'restrictToTypes' => $t_placement->getSetting('restrict_to_types'), 'showOnlyIn' => 'editor_relationship_bundle']);
	if(!is_array($va_formats)) { $va_formats = []; }
	$vs_pk = $pt_related->primaryKey();
	
	$va_ids = [];
	
	$va_options = [];
	if (sizeof($va_formats) > 0) {
		foreach($va_formats as $vn_ => $va_form_info) {
			if($va_form_info['type'] === 'omit') { continue; }
			$va_options[$va_form_info['name']] = $va_form_info['code'];
		}
	}
	
	// Get current display list
	$t_display = new ca_bundle_displays();

	foreach(caExtractValuesByUserLocale($t_display->getBundleDisplays(['table' => $table = $pt_related->tableName(), 'restrictToTypes' => $t_placement->getSetting('restrict_to_types')])) as $va_display_info) {
		if (
			(
				(!is_array($va_display_info['settings']['show_only_in'] ?? null) || !sizeof($va_display_info['settings']['show_only_in']))
				&&
				!$t_placement->getAppConfig()->get(['show_unrestricted_displays_in_relationship_bundles', "{$table}_show_unrestricted_displays_in_relationship_bundles"])
			)
			|| 
			(is_array($va_display_info['settings']['show_only_in'] ?? null) && !in_array('editor_relationship_bundle', $va_display_info['settings']['show_only_in']))
		) { continue; }        
		$n = !isset($va_options[$va_display_info['name']]) ? $va_display_info['name'] : $va_display_info['name'].' (display)';
		$va_options[$n] = '_display_'.$va_display_info['display_id']; 
	}
	
	if (sizeof($va_options) == 0) { return ''; }
	
	uksort($va_options, 'strnatcasecmp');
	
	$vs_buf = "<div class='editorBundlePrintControl'>"._t("Export as")." ";
	$vs_buf .= caHTMLSelect('export_format', $va_options, array('id' => "{$ps_id_prefix}_reportList", 'class' => 'caItemListSortControlTrigger dontTriggerUnsavedChangeWarning'), array('value' => Session::getVar("P{$placement_id}_last_export_format"), 'width' => '150px'))."\n";
	
	$vs_buf .= caJSButton($po_request, __CA_NAV_ICON_GO__, '', "{$ps_id_prefix}_report", ['onclick' => "caGetExport{$ps_id_prefix}(); return false;"], ['size' => '15px']);
	
	$vs_url = caNavUrl($po_request, 'find', 'RelatedList', 'Export', ['relatedRelTable' => $pt_relation->tableName(), 'primaryTable' => $pt_primary->tableName(), 'primaryID' => $pt_primary->getPrimaryKey(), 'download' => 1, 'relatedTable' => $pt_related->tableName()]);
	$vs_buf .= "</div>";
	$vs_buf .= "
		<script type='text/javascript'>
			function caGetExport{$ps_id_prefix}() {
				var s = jQuery('#{$ps_id_prefix}_reportList').val();
				var sort =  jQuery('#{$ps_id_prefix}_RelationBundleSortControl').val();
				var sort_direction =  jQuery('#{$ps_id_prefix}_RelationBundleSortDirectionControl').val();
				
				var f = jQuery('<form id=\"caTempExportForm\" action=\"{$vs_url}/export_format/' + s + '/sort/' + sort + '/direction/' + sort_direction + '\" method=\"post\" style=\"display:none;\"><input type=\"hidden\" name=\"placement_id\" value=\"{$placement_id}\"></form>');
				jQuery('body #caTempExportForm').replaceWith(f).hide();
				f.submit();
			}
		</script>
	";
	return $vs_buf;
}
# ---------------------------------------
/**
 *
 */
function caGetPrintFormatsListAsHTMLForSetItemBundles($ps_id_prefix, $po_request, $pt_set, $pa_row_ids) {
	require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");

	$vs_set_table = Datamodel::getTableName($pt_set->get("table_num"));
	$va_formats = caGetAvailablePrintTemplates('sets', ['showOnlyIn' => 'set_item_bundle', 'table' => $vs_set_table, 'type' => null]);
	
	if(!is_array($va_formats) || (sizeof($va_formats) == 0)) { return ''; }
	$vs_pk = $pt_set->primaryKey();
	
	$va_options = [];
	foreach($va_formats as $vn_ => $va_form_info) {
		$va_options[$va_form_info['name']] = $va_form_info['code'];
	}
	if (sizeof($va_options) == 0) { return ''; }
	
	$t_display = new ca_bundle_displays();
	if(is_array($va_displays = caExtractValuesByUserLocale($t_display->getBundleDisplays(['access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'user_id' => $po_request->getUserID(), 'table' => $vs_set_table])))) {
		foreach($va_displays as $vn_display_id => $va_display_info) {
			if (
				(is_array($va_display_info['settings']['show_only_in'] ?? null) && 
				sizeof($va_display_info['settings']['show_only_in']) && 
				!in_array('set_item_bundle', $va_display_info['settings']['show_only_in'])) 
				|| 
				(!is_array($va_display_info['settings']['show_only_in'] ?? null) && 
				($va_display_info['settings']['show_only_in'] ?? null) && 
				($va_display_info['settings']['show_only_in'] != 'set_item_bundle'))
			) { continue; }
			if(!isset($va_options[$va_display_info['name']])) { $va_options[$va_display_info['name']] = '_display_'.$va_display_info['display_id']; }
		}
	}
	
	
	uksort($va_options, 'strnatcasecmp');
	
	$vs_buf = "<div class='editorBundlePrintControl'>"._t("Export as")." ";
	$vs_buf .= caHTMLSelect('export_format', $va_options, array('id' => "{$ps_id_prefix}_reportList", 'class' => 'dontTriggerUnsavedChangeWarning'), array('value' => null, 'width' => '150px'))."\n";
	
	$vs_buf .= caJSButton($po_request, __CA_NAV_ICON_GO__, '', "{$ps_id_prefix}_report", ['onclick' => "caGetExport{$ps_id_prefix}(); return false;"], ['size' => '15px']);
	
	$vs_url = caNavUrl($po_request, 'manage', 'sets', 'setEditor/exportSetItems', ['set_id' => $pt_set->get("set_id"), 'download' => 1]);
	$vs_buf .= "</div>";
	$vs_buf .= "
		<script type='text/javascript'>
			function caGetExport{$ps_id_prefix}() {
				var s = jQuery('#{$ps_id_prefix}_reportList').val();
				var f = jQuery('<form id=\"caTempExportForm\" action=\"{$vs_url}/export_format/' + s + '\" method=\"post\" style=\"display:none;\"></form>');
				jQuery('body #caTempExportForm').replaceWith(f).hide();
				f.submit();
			}
		</script>
	";
	return $vs_buf;
}
# ---------------------------------------
/**
 * Return HTML/Javascript for "print summmary" controls on item editor summary screen
 *
 * @param View $po_view The view into which the control will be rendered
 * 
 * @return string
 */
function caEditorPrintSummaryControls($view) {
	$t_display = $view->getVar('t_display');
	$t_item = $view->getVar('t_subject');
	$request = $view->request;
	
	$defalt_display_id = $request->user->getVar($t_item->tableName().'_print_display_id');
	
	$item_id = $t_item->getPrimaryKey();
	
	$config = Configuration::load();
	$table = $t_item->tableName();
	
	$available_displays = caExtractValuesByUserLocale($t_display->getBundleDisplays([
		'table' => $t_item->tableNum(), 
		'value' => $t_display->getPrimaryKey(), 
		'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 
		'user_id' => $request->getUserID(), 'restrictToTypes' => [$t_item->getTypeID()], 
		'context' => 'editor_summary'
	]));
	
	$display_opts = [];
	// Opts for on-screen display list (only displays; no PDF print templates)
	foreach($available_displays as $d) {
		$display_opts[$d['name']] = $d['display_id'];
	}
	ksort($display_opts);
	
	// HTML <select> for on-screen display list
	$display_select_html = caHTMLSelect(
		'display_id', 
		$display_opts, 
		['onchange' => 'jQuery("#caSummaryDisplaySelectorForm").submit();', 'class' => 'searchFormSelector dontTriggerUnsavedChangeWarning'],
		['value' => $t_display->getPrimaryKey()]
	);
	
	if(
		$config->get("{$table}_dont_use_displays_for_pdf_summary_output")
		||
		($view->request->user->getPreference('use_displays_for_pdf_summary_output') === 'no')
	) {
		$display_opts = [];
	}
	// Opts for print templates (displays + PDF templates)
	$print_templates = caGetAvailablePrintTemplates('summary', ['table' => $t_item->tableName(), 'restrictToTypes' => $t_item->getTypeID()]);

	// Add PDF templates to existing display list
	foreach($print_templates as $pt) {
		if((bool)$pt['standalone']) {	// templates marked standalone are shown as printable options in the display list 
			$display_opts[$pt['name']] = $pt['code'];
		}
	}
	ksort($display_opts);
	
	// HTML <select> for print template list
	$print_display_select_html = caHTMLSelect(
		'display_id', 
		$display_opts, 
		['onchange' => 'return caSummaryUpdateOptions();', 'id' => 'caSummaryDisplaySelector', 'class' => 'searchFormSelector'],
		['value' => $defalt_display_id ? $defalt_display_id : $t_display->getPrimaryKey()]
	);
	$view->setVar('print_display_select_html', $print_display_select_html);
	
	// Opts for print formats list (PDF, DOCX, etc – any template that is not marked as standalone is a format)
	$formats = [];
	if(is_array($print_templates)) {
		$num_available_templates = sizeof($print_templates);
		foreach($print_templates as $k => $v) {
			if (($num_available_templates > 1) && (bool)$v['generic']) { continue; }    // omit generics from list when specific templates are available
			if ((bool)$v['standalone']) { continue; }
			$formats[$v['name']] = $v['code'];
		}
	}
	$view->setVar('formats', $formats);

	$view->setViewPath('bundles');
	
	// Generate print options overlay
	$buf = $view->render('summary_download_options_html.php');

	// Add on-screen display list
	if ($display_select_html) {
		$buf .= "<div id='printButton'>
			<a href='#' onclick='return caShowSummaryDownloadOptionsPanel();'>".caNavIcon(__CA_NAV_ICON_PDF__, 2)."</a>
				<script type='text/javascript'>
						function caShowSummaryDownloadOptionsPanel() {
							caSummaryDownloadOptionsPanel.showPanel();
							return false;
						}
				</script>
</div>\n";
		$buf .= caFormTag($request, 'Summary', 'caSummaryDisplaySelectorForm', null, 'post', 'multipart/form-data', '_top', ['noCSRFToken' => true, 'disableUnsavedChangesWarning' => true]).
		"<div class='searchFormSelector' style='float:right;'>". _t('Display').": {$display_select_html}</div>
		<input type='hidden' name='".$t_item->primaryKey()."' value='{$item_id}'/>
		</form>\n";
	}

	return $buf;
}
# ---------------------------------------
/**
 * Return HTML/Javascript for "print set" controls on item ca_set_items bundle
 *
 * @param View $po_view The view into which the control will be rendered
 * 
 * @return string
 */
function caEditorPrintSetItemsControls($view) {
	$t_display = new ca_bundle_displays(); //$view->getVar('t_display');
	$t_set = $view->getVar('t_set');
	$t_item = $view->getVar('t_row');
	$request = $view->request;
	
	$item_id = $t_item->getPrimaryKey();
	
	$available_displays = caExtractValuesByUserLocale($t_display->getBundleDisplays([
		'table' => $t_item->tableNum(), 
		'value' => $t_display->getPrimaryKey(), 
		'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 
		'user_id' => $request->getUserID(), 'restrictToTypes' => [$t_item->getTypeID()], 
		'context' => 'set_item_bundle'
	]));
	
	// Opts for on-screen display list (only displays; no PDF print templates)
	$display_opts = [];
	foreach($available_displays as $d) {
		$display_opts[$d['name']] = $d['display_id'];
	}
	ksort($display_opts);
	
	// Opts for print templates (displays + PDF templates)
	$print_templates = caGetAvailablePrintTemplates('sets', ['table' => $t_item->tableName(), 'restrictToTypes' => $t_item->getTypeID()]);

	// Add PDF templates to existing display list
	foreach($print_templates as $pt) {
		if((bool)$pt['standalone']) {	// templates marked standalone are shown as printable options in the display list 
			$display_opts[$pt['name']] = $pt['code'];
		}
	}
	ksort($display_opts);
	
	// HTML <select> for print template list
	$print_display_select_html = caHTMLSelect(
		'display_id', 
		$display_opts, 
		['onchange' => 'return caSummaryUpdateOptions();', 'id' => 'caSummaryDisplaySelector', 'class' => 'searchFormSelector dontTriggerUnsavedChangeWarning'],
		['value' => $t_display->getPrimaryKey()]
	);
	$view->setVar('print_display_select_html', $print_display_select_html);
	
	// Opts for print formats list (PDF, DOCX, etc – any template that is not marked as standalone is a format)
	$formats = [];
	if(is_array($print_templates)) {
		$num_available_templates = sizeof($print_templates);
		foreach($print_templates as $k => $v) {
			if (($num_available_templates > 1) && (bool)$v['generic']) { continue; }    // omit generics from list when specific templates are available
			if ((bool)$v['standalone']) { continue; }
			$formats[$v['name']] = $v['code'];
		}
	}
	$view->setVar('formats', $formats);

	$view->setViewPath('bundles');
	
	// Generate print options overlay
	$buf = $view->render('set_download_options_html.php');    
	$buf .= "<div id='printButton'>
		<a href='#' onclick='return caShowSummaryDownloadOptionsPanel();'>".caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 2)."</a>
			<script type='text/javascript'>
					function caShowSummaryDownloadOptionsPanel() {
						caSummaryDownloadOptionsPanel.showPanel();
						return false;
					}
			</script>
</div>\n";

	return $buf;
}
# ---------------------------------------
/**
 * Return form elements for print summary options form
 * 
 * @return array
 */
function caEditorPrintParametersForm(string $type, string $template, ?array $values=null, ?array $options=null) : ?array {
	if(!is_array($info = caGetPrintTemplateDetails($type, $template, $options))) { return null; }
	if(!is_array($info['params']) || (sizeof($info['params']) === 0)) { return []; }
	
	$form_elements = [];
	foreach($info['params'] as $n => $p) {
		$default = $p['default'] ?? null;
		
		$label = $p['label'];
		
		if(is_array($label)) {
			// Label is localized in the form:
			// "label": {"en": "Include logo?", "de_DE": "Logo entschließen?" }
			$label = caExtractSettingsValueByUserLocale('label', $p);
		}
		
		switch(strtolower($p['type'] ?? null)) {
			case 'list':
				$attr = ['class' => 'dontTriggerUnsavedChangeWarning'];
				if($p['multiple'] ?? false) { $attr['multiple'] = 1; }
			
				$list_options = null;
				if(caIsAssociativeArray($p['options'])) {
					$list_options = $p['options'];
					foreach($list_options as $k => $v) {
						if(is_array($v)) {
							// Options are localized in form:
							// "options": { "en": {"One": 1, "Two": 2, "Thress": 3 },  "de": {"Eins": 1, "Zwei": 2, "Drei": 3 }
							$list_options = caExtractSettingsValueByUserLocale('options', $p);
							break;
						}
					}
				}
				$dv = $values[$n] ?? $default;
				if(!is_array($dv)) { $dv = [$dv]; }
				
				$e = caHTMLSelect($n.((isset($attr['multiple']) && $attr['multiple']) ? '[]' : '') , $list_options, $attr, ['values' => $dv, 'width' => caGetOption('width', $p, null), 'height' => caGetOption('height', $p, null)]);
				$form_elements[$n] = ['label' => $label, 'element' => $e];
				break;
			case 'checkbox':
				$e = caHTMLCheckboxInput($n, ['value' => $p['value'] ?? 1, 'checked' => $values[$n] ?? $default, 'class' => 'dontTriggerUnsavedChangeWarning']);
				$form_elements[$n] = ['label' => $label, 'element' => $e];
				break;
			case 'text':
				$e = caHTMLTextInput($n, ['placeholder' => caGetOption('placeholder', $p, ''), 'value' => $values[$n] ?? $default, 'class' => 'dontTriggerUnsavedChangeWarning'], ['width' => caGetOption('width', $p, '200px'), 'height' => caGetOption('height', $p, '200px')]);
				$form_elements[$n] = ['label' => $label, 'element' => $e];
				break;
		}
	}
	
	return $form_elements;
}
# ---------------------------------------
/**
 * Get configured paramweters for template
 *
 * @param string $type
 * @param string $template
 * @param array $options Options include:
 * 		view = Optional view to set parameters data into. Parameters will be set as view variables with the prefix "param_". [Default is null]
 *		request = The current request. If set the returned array is key'ed on parameter name, with values set. If not set a simple list of template parameter names is returned. [Default is null]
 * @return array Array with template paramet
 */
function caGetPrintTemplateParameters(string $type, string $template, ?array $options=null) : array {
	$template = preg_replace("!^(_pdf_|_display_)!", "", $template);
	
	$view = caGetOption('view', $options, null);
	$request = caGetOption('request', $options, null);
	
	$tinfo = caGetPrintTemplateDetails($type, $template);
	if($view) {
		$view->setVar('template_info', $tinfo);
	}
	$values = [];
	
	if(is_array($tinfo) && is_array($tinfo['params'] ?? null)) {
		$values = [];
		foreach($tinfo['params'] as $n => $p) {
			if($request) {
				if((bool)($p['multiple'] ?? false)) {
					$values[$n] = $request->getParameter($n, pArray);
				} else {
					$values[$n] = $request->getParameter($n, pString);
				}
			} else {
				$values[] = $n;
			}
			if($view) {
				$view->setVar("param_{$n}", $values[$n]);
			}
		}
	}
	
	Session::setVar("print_template_{$type}_options_".pathinfo($tinfo['path'] ?? null, PATHINFO_FILENAME), $values);
	return $values;
}
# ---------------------------------------
/**
 * Process template name, stripping type prefix if present. Eg. "_pdf_my_first_report" will be returned as "my_first_report"
 *
 * If the returnDetails option is set an array will be returned with keys for name ("my_first_report") and extracted type if present ("pdf")
 *
 * @param string $template_name
 * @param array $options Options include:
 *		returnDetails = return array with template name and type in "name" and "type" keys. [Default is false]
 *	
 * @return mixed Returns string with processed name, or an array if the returnDetails option is set. 
 */
function caProcessTemplateName(string $template_name, ?array $options=null) {
	// strip format prefix if present
	$type = null;
	if(preg_match("!^_(pdf|display|docx|xlsx|csv|tab)_!", $template_name, $m)) {
		$type = $m[1];
		$template_name = preg_replace("!^_{$type}_!", "", $template_name);
	}
	
	if(caGetOption('returnDetails', $options, false)) {
		return [
			'name' => $template_name,
			'type' => $type
		];
	} else {
		return $template_name;
	}
}
# ---------------------------------------
/**
 * Check if legacy print templates system used prior to version 2.1 should be used)
 * 
 * @return bool
 */
function caUseLegacyPrintTemplatesSystem() : bool {
	$config = Configuration::load();
	return (bool)$config->get('use_legacy_print_templates_system');
}
# ---------------------------------------
