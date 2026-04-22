<?php
/* ----------------------------------------------------------------------
 * MetaTagManager.php : class to control loading of metatags in page headers
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2026 Whirl-i-Gig
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
	/**
	 * List of tags to render
	 
	 * @array
	 */
	private static $tags;
	
	/**
	 * Window title text, overriding application default
	 *
	 * @string
	 */
	private static $window_title = '';
	# --------------------------------------------------------------------------------
	/**
	 * Initialize tag lists
	 *
	 * @return void
	 */
	static function init() {
		MetaTagManager::$tags = ['meta' => [], 'link' => []];
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <meta> tag to response
	 *
	 * @param string $tag_name Name attribute of <meta> tag
	 * @param string|array $content Content of <meta> tag
	 *
	 * @return bool True if tag was added
	 */
	static function addMeta(string $tag_name, string|array $content) : bool {			
		if (!is_array(MetaTagManager::$tags)) { MetaTagManager::init(); }
		if (!$tag_name) { return false; }
		
		if(!is_array(MetaTagManager::$tags['meta'][$tag_name])) { MetaTagManager::$tags['meta'][$tag_name] = []; }
		if(is_array($content)) {
			MetaTagManager::$tags['meta'][$tag_name] = array_merge(MetaTagManager::$tags['meta'][$tag_name], $content);
		} else {
			MetaTagManager::$tags['meta'][$tag_name][] = $content;
		}
		
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <meta> tag to response with property
	 *
	 * @param string $tag_property Name attribute of <meta> tag
	 * @param string|array $content Content of <meta> tag
	 *
	 * @return bool True if tag was added
	 */
	static function addMetaProperty(string $tag_property, string|array $content) : bool {			
		if (!is_array(MetaTagManager::$tags)) { MetaTagManager::init(); }
		if (!$tag_property) { return false; }
		
		if(!is_array(MetaTagManager::$tags['meta_property'][$tag_property])) { MetaTagManager::$tags['meta_property'][$tag_property] = []; }
		if(is_array($content)) {
			MetaTagManager::$tags['meta_property'][$tag_property] = array_merge(MetaTagManager::$tags['meta_property'][$tag_property], $content);
		} else {
			MetaTagManager::$tags['meta_property'][$tag_property][] = $content;
		}
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <link> tag to response.
	 *
	 * @param string $rel Rel attribute of <link> tag
	 * @param string $href Href attribute of <link> tag
	 * @param string $type string Type attribute of <link> tag (optional)
	 *
	 * @return bool True if link was added
	 */
	static function addLink(string $rel, string $href, ?string $type=null) : bool {			
		if (!is_array(MetaTagManager::$tags)) { MetaTagManager::init(); }
		if (!$rel) { return false; }
		
		MetaTagManager::$tags['link'][] = [
			'href' => $href,
			'rel' => $rel,
			'type' => $type
		];
		
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Add <script> tag to response.
	 *
	 * @param string $src Href attribute of <script> tag
	 * @param string $type Type attribute of <link> tag [optional]
	 * @return bool True if script ref was added
	 */
	static function addScript(string $src, ?string $type=null, ?array $options=null) : bool {
		if (!is_array(MetaTagManager::$tags)) { MetaTagManager::init(); }

		MetaTagManager::$tags['script'][] = [
			'src' => $src,
			'type' => $type
		];

		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Clears all set tags
	 *
	 * @return void
	 */
	static function clearAll() {
		MetaTagManager::init();
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns set <meta> and <link> tags for inclusion in response <head>
	 *
	 * @return string HTML <meta> and <link> tags
	 */
	static function getHTML() : string {
		$buf = '';
		if (!is_array(MetaTagManager::$tags)) { MetaTagManager::init(); }
		
		if(is_array(MetaTagManager::$tags)) {
			if (is_array(MetaTagManager::$tags['meta'] ?? null) && sizeof(MetaTagManager::$tags['meta'])) {	
				foreach(MetaTagManager::$tags['meta'] as $tag_name => $content) {
					if(!is_array($content)) { $content = [$content]; }
					
					foreach($content as $c) {
						$buf .= "<meta name='".htmlspecialchars($tag_name, ENT_QUOTES)."' content='".htmlspecialchars($c, ENT_QUOTES)."'/>\n";
					}
				}
			}
			if (is_array(MetaTagManager::$tags['meta_property'] ?? null) && sizeof(MetaTagManager::$tags['meta_property'])) {	
				foreach(MetaTagManager::$tags['meta_property'] as $tag_property => $content) {
					if(!is_array($content)) { $content = [$content]; }
					foreach($content as $c) {
						$buf .= "<meta property='".htmlspecialchars($tag_property, ENT_QUOTES)."' content='".htmlspecialchars($c, ENT_QUOTES)."'/>\n";
					}
				}
			}
			if (is_array(MetaTagManager::$tags['link'] ?? null) && sizeof(MetaTagManager::$tags['link'])) {	
				foreach(MetaTagManager::$tags['link'] as $i => $link) {
					$buf .= "<link rel='".htmlspecialchars($link['rel'], ENT_QUOTES)."' href='".htmlspecialchars($link['href'], ENT_QUOTES)."' ".($link['type'] ? " type='".$link['type']."'" : "")."/>\n";
				}
			}
			if (is_array(MetaTagManager::$tags['script'] ?? null) && sizeof(MetaTagManager::$tags['script'])) {
				foreach(MetaTagManager::$tags['script'] as $i => $link) {
					$buf .= "<script src='".htmlspecialchars($link['src'], ENT_QUOTES)."' ".($link['type'] ? " type='".$link['type']."'" : "")."></script>\n";
				}
			}
		}
		return $buf;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Set window title text, overriding application default
	 *
	 * @param string $title Window title text
	 *
	 * @return bool True if window title text was set
	 */
	static function setWindowTitle(string $title) : bool {
		MetaTagManager::$window_title = $title;
		
		return true;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Get window title
	 *
	 * @return string
	 */
	static function getWindowTitle() : ?string {
		return MetaTagManager::$window_title ? MetaTagManager::$window_title : Configuration::load()->get('app_display_name');
	}
	# --------------------------------------------------------------------------------
	/**
	 * Set text highlight
	 *
	 * @param array $highlight_text List of strings to highlight
	 * @param array $options Options include:
	 *		persist = Persist highlight text in session. [Default is true]
	 *		removeWildcards = Strip asterisks from highlight text. [Default is true]
	 *
	 * @return bool Always returns true
	 */
	static function setHighlightText(?array $highlight_text, ?array $options=null) : bool {
		global $g_highlight_text;
		if(is_array($highlight_text) && caGetOption('removeWildcards', $options, true)) {
			$highlight_text = array_map(function($v) { 
				return str_replace('*', '', $v);
			}, $highlight_text);
		}
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
	 * @return array Return text to highlight, or null if no highlighting is required
	 */
	static function getHighlightText() : ?array {
		global $g_highlight_text;
		if(!is_null($g_highlight_text)) { return $g_highlight_text; }
		return $g_highlight_text = Session::getVar('text_highlight');
	}
	# --------------------------------------------------------------------------------
}
