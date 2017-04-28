<?php
/** ---------------------------------------------------------------------
 * app/lib/core/View.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2016 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__."/core/BaseObject.php");
 
class View extends BaseObject {
	# -------------------------------------------------------
	private $opa_view_paths;
	
	private $opa_view_vars;
	private $opo_request;
	private $opo_appconfig;
	
	private $ops_character_encoding;
	
	private $ops_last_render = null;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($po_request, $pm_path=null, $ps_character_encoding='UTF8', $pa_options=null) {
		parent::__construct();
		
		$this->opo_request = $po_request;
		$this->opa_view_paths = array();
		$this->opa_view_vars = array();
		
		$this->opo_appconfig = Configuration::load();
		
		$this->ops_character_encoding = $ps_character_encoding;
		
		if (!$pm_path) { $pm_path = array(); }
		$this->setViewPath($pm_path, $pa_options);
	}
	# -------------------------------------------------------
	public function __get($ps_key) {
		switch($ps_key) {
			case 'request':
				return $this->opo_request;
				break;
			case 'appconfig':
				return $this->opo_appconfig;
				break;
			default:
				return $this->{$ps_key};
				break;
		}
	}
	# -------------------------------------------------------
	public function setRequest(&$po_request) {
		$this->opo_request = $po_request;
	}
	# -------------------------------------------------------
	public function setViewPath($pm_path, $pa_options=null) {
		if (!is_array($pm_path)) { 
			$pm_path = array($pm_path);
		}
		foreach($pm_path as $ps_path) {
			// Preserve any path suffix after "views"
			// Eg. if path is /web/myinstall/themes/mytheme/views/bundles then we want to retain "/bundles" on the default path
			$va_suffix_bits = array();
			$va_tmp = array_reverse(explode("/", $ps_path));
			foreach($va_tmp as $vs_path_element) {
				if ($vs_path_element == 'views') { break; }
				array_push($va_suffix_bits, $vs_path_element);
			}
			if ($vs_suffix = join("/", array_reverse($va_suffix_bits))) { $vs_suffix = '/'.$vs_suffix; break;}
		}
		
		if (caGetOption('includeDefaultThemePath', $pa_options, true)) {
				$vs_default_theme_path = $this->opo_request ? $this->opo_request->getDefaultThemeDirectoryPath().'/views'.$vs_suffix : __CA_THEME_DIR__."/default/views{$vs_suffix}";
				if (!in_array($vs_default_theme_path, $pm_path) && !in_array($vs_default_theme_path.'/', $pm_path)) {
					array_unshift($pm_path, $vs_default_theme_path);
			}
		}
		$this->opa_view_paths = $pm_path;
	}
	# -------------------------------------------------------
	public function addViewPath($pm_path) {
		if (is_array($pm_path)) {
			foreach($pm_path as $vs_path) {
				$this->opa_view_paths[] = $vs_path;
			}
		} else {
			$this->opa_view_paths[] = $pm_path;
		}
	}
	# -------------------------------------------------------
	public function getViewPaths() {
		return $this->opa_view_paths;
	}
	# -------------------------------------------------------
	public function setVar($ps_key, $pm_value) {
		$this->opa_view_vars[$ps_key] = $pm_value;
	}
	# -------------------------------------------------------
	public function getVar($ps_key) {
		return isset($this->opa_view_vars[$ps_key]) ? $this->opa_view_vars[$ps_key] : null;
	}
	# -------------------------------------------------------
	public function getAllVars() {
		return $this->opa_view_vars;
	}
	# -------------------------------------------------------
	public function assignVars($pa_values) {
		foreach($pa_values as $vs_key => $vm_value) {
			$this->opa_view_vars[$vs_key] = $vm_value;
		}
	}
	# -------------------------------------------------------
	/**
	 * Checks if the specified view exists in any of the configured view paths
	 *
	 * @param string $ps_filename Filename of view
	 * @param boolean - return true if view exists, false if not
	 */ 
	public function viewExists($ps_filename) {
		foreach(array_reverse($this->opa_view_paths) as $vs_path) {
			if (file_exists($vs_path.'/'.$ps_filename)) {
				return true;
			}
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function isCompiled($ps_filepath) {
		global $g_ui_locale;
		$vs_compiled_path = __CA_APP_DIR__."/tmp/caCompiledView".md5($ps_filepath.$g_ui_locale);
		if (!file_exists($vs_compiled_path)) { return false; }
		if (filesize($vs_compiled_path) === 0) { return false; }
		
		// Check if template change date is newer than compiled
		$va_view_stat = @stat($ps_filepath);
		$va_compiled_stat = @stat($vs_compiled_path);
		if ($va_view_stat['mtime'] > $va_compiled_stat['mtime']) { return false; }
		
		return $vs_compiled_path;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function compile($ps_filepath, $pb_force_recompile=false, $pa_options=null) {
		if (!$pb_force_recompile && ($vs_compiled_path = $this->isCompiled($ps_filepath))) { 
			$va_tags = json_decode(file_get_contents($vs_compiled_path), true);
			if (is_array($va_tags)) { return $va_tags; }
		}
		
		$pb_string = caGetOption('string', $pa_options, false);
		
		$vs_buf = $this->_render($ps_filepath);
		
		$vs_compiled_path = __CA_APP_DIR__."/tmp/caCompiledView".md5($ps_filepath);
		preg_match_all("!(?<=\{\{\{)(?s)(.*?)(?=\}\}\})!", $vs_buf, $va_matches);
		
		$va_tags = $va_matches[1];
		
		$vs_raw_buf = $pb_string ? $ps_filepath : file_get_contents($ps_filepath);
		preg_match_all("!(?<=\{\{\{)(?s)(.*?)(?=\}\}\})!", $vs_raw_buf, $va_matches);
		
		// Remove any tag that has embedded PHP - we can't cache those
		foreach($va_matches[1] as $vn_i => $vs_potential_tag) {
			if (strpos($vs_potential_tag, "<?php") !== false) { unset($va_matches[1][$vn_i]); }
		}
		$va_tags = array_merge($va_tags, $va_matches[1]);
		$va_tags = array_unique($va_tags);
		
		if (!is_array($va_tags)) { $va_tags = array(); }
		
		if($vs_tags = json_encode($va_tags)) {
			@file_put_contents($vs_compiled_path, $vs_tags);
		} else {
			@unlink($vs_compiled_path);
		}
		return $va_tags;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getTagList($ps_filename, $pa_options=null) {
		global $g_ui_locale;
		
		$vb_output = false;
		
		$va_tags = null;
		foreach(array_reverse($this->opa_view_paths) as $vs_path) {
			if (file_exists($vs_path.'/'.$ps_filename.".".$g_ui_locale)) {
				// if a l10ed view is at same path than normal but having the locale as last extension, display it (eg. splash_intro_text_html.php.fr_FR)
				$va_tags = $this->compile($vs_path.'/'.$ps_filename.".".$g_ui_locale, false, $pa_options);
				break;
			}
			elseif (file_exists($vs_path.'/'.$ps_filename)) {
				// if no l10ed version of the view, render the default one which has no locale as last extension (eg. splash_intro_text_html.php)
				$va_tags = $this->compile($vs_path.'/'.$ps_filename, false, $pa_options);
				break;
			} elseif (file_exists($ps_filename)) {
				$va_tags = $this->compile($ps_filename, false, $pa_options);
				break;
			}
		}
		
		return $va_tags;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function clearViewTagsVars($ps_filename) {
		$va_tags = $this->getTagList($ps_filename);
		
		foreach($va_tags as $vs_tag) {
			unset($this->opa_view_vars[$vs_tag]);
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function render($ps_filename, $pb_dont_do_var_replacement=false, $pa_options=null) {
		global $g_ui_locale;
		$this->ops_last_render = null;
		
		$vb_output = false;
		$vs_buf = null;
		if (caGetOption('string', $pa_options, false)) {
			$vs_buf = $ps_filename;
			$vb_output = true;
		} elseif (($ps_filename[0] == '/') || (preg_match("!^[A-Za-z]{1}:!", $ps_filename))) { 	// absolute path
			$vs_buf = $this->_render($ps_filename);
			$vb_output = true;
		} else {
			foreach(array_reverse($this->opa_view_paths) as $vs_path) {
				if (file_exists($vs_path.'/'.$ps_filename.".".$g_ui_locale)) {
					// if a l10ed view is at same path than normal but having the locale as last extension, display it (eg. splash_intro_text_html.php.fr_FR)
					$vs_buf = $this->_render($vs_path.'/'.$ps_filename.".".$g_ui_locale);
					$vb_output = true;
					break;
				}
				elseif (file_exists($vs_path.'/'.$ps_filename)) {
					// if no l10ed version of the view, render the default one which has no locale as last extension (eg. splash_intro_text_html.php)
					$vs_buf = $this->_render($vs_path.'/'.$ps_filename);
					$vb_output = true;
					break;
				}
			}
			if (!$vb_output) {
				$this->postError(2400, _t("View %1 was not found", $ps_filename), "View->render()");
			}
		}
		if (!$pb_dont_do_var_replacement && $vb_output) {
			$va_compile = $this->compile($vs_path.'/'.$ps_filename, false, $pa_options);
			
			$va_vars = $this->getAllVars();
			
			foreach($va_compile as $vs_var) {
				$vm_val = isset($va_vars[$vs_var]) ? $va_vars[$vs_var] : '';
				$vn_count = 0;
				$vs_buf = str_replace('{{{'.$vs_var.'}}}', $vm_val, $vs_buf, $vn_count);				
			}
		}
		
		return $vs_buf;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _render($ps_filename) {
		if ($this->ops_last_render) { return $this->ops_last_render; }
		if (!file_exists($ps_filename)) { return null; }
		ob_start();
		
		require($ps_filename);
			
		return $this->ops_last_render = ob_get_clean();
	}
	# -------------------------------------------------------
	# Character encodings
	# -------------------------------------------------------
	public function setEncoding($ps_character_encoding) {
		$this->ops_character_encoding = $ps_character_encoding;
	}
	# -------------------------------------------------------
	public function getEncoding($ps_character_encoding) {
		return $this->ops_character_encoding;
	}
	# -------------------------------------------------------
	# Utils
	# -------------------------------------------------------
	public function escape($ps_text) {
		return htmlspecialchars($ps_text, ENT_QUOTES, $this->ops_character_encoding, false);
	}
	# -------------------------------------------------------
}