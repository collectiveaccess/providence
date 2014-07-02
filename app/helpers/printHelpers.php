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

		$va_templates = array();
		foreach(array("{$vs_template_path}", "{$vs_template_path}/local") as $vs_path) {
			if(!file_exists($vs_path)) { continue; }
	
			if (is_resource($r_dir = opendir($vs_path))) {
				while (($vs_template = readdir($r_dir)) !== false) {
					if (in_array($vs_template, array(".", ".."))) { continue; }
					$vs_template_tag = pathinfo($vs_template, PATHINFO_FILENAME);
					if (is_array($va_template_info = caGetPrintTemplateDetails($ps_type, $vs_template_tag))) {
						if (caGetOption('type', $va_template_info, null) !== 'page')  { continue; }
						
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
		
		// TODO: add caching
		
		$vs_template = file_get_contents($vs_template_path);
		
		$va_info = array();
		foreach(array("@name", "@type", "@pageSize", "@pageOrientation", "@tables") as $vs_tag) {
			if (preg_match("!{$vs_tag}([^\n\n]+)!", $vs_template, $va_matches)) {
				$va_info[str_replace("@", "", $vs_tag)] = trim($va_matches[1]);
			} else {
				$va_info[str_replace("@", "", $vs_tag)] = null;
			}
		}
		$va_info['tables'] = preg_split("![,;]{1}!", $va_info['tables']);
		$va_info['path'] = $vs_template_path;
		
		return $va_info;
	}
	# ---------------------------------------
?>