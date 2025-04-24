<?php
/* ----------------------------------------------------------------------
 * MetaTagManager.php : class to control loading of metatags in page headers
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2024 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
class MetaTagManager {
	# --------------------------------------------------------------------------------
	private static $opa_tags;
	private static $ops_window_title = '';
	# --------------------------------------------------------------------------------
	/**
	 * Initialize 
	 *
	 * @return void
	 */
	static function init() {
		MetaTagManager::$opa_tags = array('meta' => array(), 'link' => array());
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <meta> tag to response
	 *
	 * @param $ps_tag_name (string) - name attribute of <meta> tag
	 * @param $ps_content (string) - content of <meta> tag
	 * @return (bool) - Returns true if tooltip was successfully added, false if not
	 */
	static function addMeta($ps_tag_name, $ps_content) {			
		if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }
		if (!$ps_tag_name) { return false; }
		
		MetaTagManager::$opa_tags['meta'][$ps_tag_name] = $ps_content;
		
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <meta> tag to response with property
	 *
	 * @param $ps_tag_property (string) - name attribute of <meta> tag
	 * @param $ps_content (string) - content of <meta> tag
	 * @return (bool) - Returns true if was successfully added, false if not
	 */
	static function addMetaProperty($ps_tag_property, $ps_content) {			
		if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }
		if (!$ps_tag_property) { return false; }
		
		MetaTagManager::$opa_tags['meta_property'][$ps_tag_property] = $ps_content;
		
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <link> tag to response.
	 *
	 * @param $ps_rel (string) - rel attribute of <link> tag
	 * @param $ps_href (string) - href attribute of <link> tag
	 * @param $ps_type (string) - type attribute of <link> tag [optional]
	 * @return (bool) - Always return true
	 */
	static function addLink($ps_rel, $ps_href, $ps_type=null) {			
		if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }
		if (!$ps_rel) { return false; }
		
		MetaTagManager::$opa_tags['link'][] = array(
			'href' => $ps_href,
			'rel' => $ps_rel,
			'type' => $ps_type
		);
		
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <script> tag to response.
	 *
	 * @param $ps_src (string) - href attribute of <script> tag
	 * @param $ps_type (string) - type attribute of <link> tag [optional]
	 * @return (bool) - Always return true
	 */
	static function addScript($ps_src, $ps_type=null,$options=null) {
		if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }

		MetaTagManager::$opa_tags['script'][] = array(
			'src' => $ps_src,
			'type' => $ps_type
		);

		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Clears all currently set tags
	 *
	 * @return void
	 */
	static function clearAll() {
		MetaTagManager::init();
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns <meta> and <link> tags
	 *
	 * @return (string) - HTML <meta> and <link> tags
	 */
	static function getHTML() {
		$vs_buf = '';
		if (!is_array(MetaTagManager::$opa_tags)) { MetaTagManager::init(); }
		
		if(is_array(MetaTagManager::$opa_tags)) {
			if (is_array(MetaTagManager::$opa_tags['meta'] ?? null) && sizeof(MetaTagManager::$opa_tags['meta'])) {	
				foreach(MetaTagManager::$opa_tags['meta'] as $vs_tag_name => $vs_content) {
					$vs_buf .= "<meta name='".htmlspecialchars($vs_tag_name, ENT_QUOTES)."' content='".htmlspecialchars($vs_content, ENT_QUOTES)."'/>\n";
				}
			}
			if (is_array(MetaTagManager::$opa_tags['meta_property'] ?? null) && sizeof(MetaTagManager::$opa_tags['meta_property'])) {	
				foreach(MetaTagManager::$opa_tags['meta_property'] as $vs_tag_property => $vs_content) {
					$vs_buf .= "<meta property='".htmlspecialchars($vs_tag_property, ENT_QUOTES)."' content='".htmlspecialchars($vs_content, ENT_QUOTES)."'/>\n";
				}
			}
			if (is_array(MetaTagManager::$opa_tags['link'] ?? null) && sizeof(MetaTagManager::$opa_tags['link'])) {	
				foreach(MetaTagManager::$opa_tags['link'] as $vn_i => $va_link) {
					$vs_buf .= "<link rel='".htmlspecialchars($va_link['rel'], ENT_QUOTES)."' href='".htmlspecialchars($va_link['href'], ENT_QUOTES)."' ".($va_link['type'] ? " type='".$va_link['type']."'" : "")."/>\n";
				}
			}
			if (is_array(MetaTagManager::$opa_tags['script'] ?? null) && sizeof(MetaTagManager::$opa_tags['script'])) {
				foreach(MetaTagManager::$opa_tags['script'] as $vn_i => $va_link) {
					$vs_buf .= "<script src='".htmlspecialchars($va_link['src'], ENT_QUOTES)."' ".($va_link['type'] ? " type='".$va_link['type']."'" : "")."></script>\n";
				}
			}
		}
		return $vs_buf;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Set window title
	 *
	 * @param string $title The window title
	 * @return bool Always returns true
	 */
	static function setWindowTitle(string $title) : bool {
		MetaTagManager::$ops_window_title = $title;
		
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Get window title
	 *
	 * @return string
	 */
	static function getWindowTitle() : ?string {
		return MetaTagManager::$ops_window_title ? MetaTagManager::$ops_window_title : Configuration::load()->get('app_display_name');
	}
	# --------------------------------------------------------------------------------
	/**
	 * Set text highlight
	 *
	 * @param array $highlight_text List of strings to highlight
	 * @param array $options Options include:
	 *		persist = Persist highlight text in session. [Default is true]
	 * @return bool Always returns true
	 */
	static function setHighlightText(?array $highlight_text, ?array $options=null) : bool {
		global $g_highlight_text;
		$g_highlight_text = $highlight_text;
		
		if(caGetOption('persist', $options, true)) {
			Session::setVar('text_highlight', $highlight_text);
		}
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Get list of text to highlight
	 *
	 * @return array
	 */
	static function getHighlightText() : ?array {
		global $g_highlight_text;
		if(!is_null($g_highlight_text)) { return $g_highlight_text; }
		return $g_highlight_text = Session::getVar('text_highlight');
	}
	# --------------------------------------------------------------------------------
}
