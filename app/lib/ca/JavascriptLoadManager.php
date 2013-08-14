<?php
/** ---------------------------------------------------------------------
 * JavascriptLoadManager.php : class to control loading of Javascript libraries
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 * @subpackage Misc
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	require_once(__CA_LIB_DIR__.'/core/Configuration.php');

	/*
	 * Globals used by Javascript load manager
	 */
	 
	 /**
	  * Contains configuration object for javascript.conf file
	  */
	$g_javascript_config = null;
	
	/**
	 * Contains list of Javascript libraries to load
	 */
	$g_javascript_load_list = null;
	
	/**
	 * Contains array of complementary Javasscript code to load
	 */
	$g_javascript_complementary = null;

	class JavascriptLoadManager {
		# --------------------------------------------------------------------------------
		
		# --------------------------------------------------------------------------------
		static function init() {
			global $g_javascript_config, $g_javascript_load_list;
			
			$o_config = Configuration::load();
 			
			$g_javascript_config = Configuration::load($o_config->get('javascript_config'));
			$g_javascript_load_list = array();
			
			JavascriptLoadManager::register('_default');
		}
		# --------------------------------------------------------------------------------
		/**
		 * Causes the specified Javascript library (or libraries) to be loaded for the current request.
		 * There are two ways you can trigger loading of Javascript:
		 *		(1) If you pass via the $ps_package parameter a loadSet name defined in javascript.conf then all of the libraries in that loadSet will be loaded
		 * 		(2) If you pass a package and library name (via $ps_package and $ps_library) then the specified library will be loaded
		 *
		 * Whether you use a loadSet or a package/library combination, if it doesn't have a definition in javascript.conf nothing will be loaded.
		 *
		 * @param $ps_package (string) - The package name containing the library to be loaded *or* a loadSet name. LoadSets describe a set of libraries to be loaded as a unit.
		 * @param $ps_library (string) - The name of the library contained in $ps_package to be loaded.
		 * @return bool - false if load failed, true if load succeeded
		 */
		static function register($ps_package, $ps_library=null) {
			global $g_javascript_config, $g_javascript_load_list;
			
			if (!$g_javascript_config) { JavascriptLoadManager::init(); }
			$va_packages = $g_javascript_config->getAssoc('packages');
			if ($ps_library) {
				// register package/library
				$va_pack_path = explode('/', $ps_package);
				$vs_main_package = array_shift($va_pack_path);
				if (!($va_list = $va_packages[$vs_main_package])) { return false; }
				 
				while(sizeof($va_pack_path) > 0) {
					$vs_pack = array_shift($va_pack_path);
					$va_list = $va_list[$vs_pack];
					
				}
				if (isset($va_list[$ps_library]) && $va_list[$ps_library]) {
					$g_javascript_load_list[$ps_package.'/'.$va_list[$ps_library]] = true;
					return true;
				}
			} else {
				// register loadset
				$va_loadsets = $g_javascript_config->getAssoc('loadSets');
				
				if ($va_loadset = $va_loadsets[$ps_package]) {
					$vs_loaded_ok = true;
					foreach($va_loadset as $vs_path) {
						$va_tmp = explode('/', $vs_path);
						$vs_library = array_pop($va_tmp);
						$vs_package = join('/', $va_tmp);
						if (!JavascriptLoadManager::register($vs_package, $vs_library)) {
							$vs_loaded_ok = false;
						}
					}
					return $vs_loaded_ok;
				}
			}
			return false;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Causes the specified Javascript code to be loaded.
		 *
		 * @param $ps_scriptcontent (string) - script content to load
		 * @return (bool) - false if empty code, true if load succeeded
		 */
		static function addComplementaryScript($ps_content=null) {
			global $g_javascript_config, $g_javascript_load_list, $g_javascript_complementary;			

			if (!$g_javascript_config) { JavascriptLoadManager::init(); }
			if (!$ps_content) return false;
			$g_javascript_complementary[]=$ps_content;			
			return true;
		}
		# --------------------------------------------------------------------------------
		/** 
		 * Returns HTML to load registered libraries. Typically you'll output this HTML in the <head> of your page.
		 * 
		 * @param $ps_baseurlpath (string) - URL path containing the application's "js" directory.
		 * @return string - HTML loading registered libraries
		 */
		static function getLoadHTML($ps_baseurlpath) {
			global $g_javascript_config, $g_javascript_load_list, $g_javascript_complementary;
			
			if (!$g_javascript_config) { JavascriptLoadManager::init(); }
			$vs_buf = '';
			if (is_array($g_javascript_load_list)) {
				foreach($g_javascript_load_list as $vs_lib => $vn_x) { 
					if (preg_match('!(http[s]{0,1}://.*)!', $vs_lib, $va_matches)) { 
						$vs_url = $va_matches[1];
					} else {
						$vs_url = "{$ps_baseurlpath}/js/{$vs_lib}";
					}
					
					if (preg_match('!\.css$!', $vs_lib)) {
						$vs_buf .= "<link rel='stylesheet' href='{$vs_url}' type='text/css' media='screen'/>\n";
					} elseif(preg_match('!\.properties$!', $vs_lib)) {
						$vs_buf .= "<link rel='resource' href='{$vs_url}' type='application/l10n' />\n";
					} else {
						$vs_buf .= "<script src='{$vs_url}' type='text/javascript'></script>\n";
					}
				}
			}
			if (is_array($g_javascript_complementary)) {
				foreach($g_javascript_complementary as $vs_code) { 
					$vs_buf .= "<script type='text/javascript'>\n".$vs_code."</script>\n";
				}
			}
			return $vs_buf;
		}
		# --------------------------------------------------------------------------------
	}
?>