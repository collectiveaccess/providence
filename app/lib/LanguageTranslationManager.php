<?php
/** ---------------------------------------------------------------------
 * app/lib/LanguageTranslationManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA;

 /**
  *
  */
require_once(__CA_LIB_DIR__.'/Plugins/PluginConsumer.php');

class LanguageTranslationManager extends \CA\Plugins\PluginConsumer {
	# ----------------------------------------------------------
	# Properties
	# ----------------------------------------------------------
	
	
	# ----------------------------------------------------------
	# Methods
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function __construct($no_cache=false) { 
		if (!self::$plugin_path) { self::$plugin_path = __CA_LIB_DIR__.'/Plugins/LanguageTranslation'; }
		self::$exclusion_list = ['BaseLanguageTranslationManagerPlugin.php'];
		
		self::$name = 'LanguageTranslationManager';
		self::$plugin_prefix = '\\CA\\LanguageTranslation\\Plugins\\';
	}
	
	# ----------------------------------------------------------
	/**
	 * Translate
	 *
	 * @param string $text
	 * @paran string $to_lang
	 * @param array $options Options include:
	 *		... none yet ...
	 *
	 * @return string Translated text
	 */
	function translate(string $text, string $to_lang, ?array $options=null) : ?string {
		$tmp = preg_split("/[_\-]{1}/", $to_lang);
		if(sizeof($tmp) > 1) { $to_lang = $tmp[0]; }
	
		$plugin_names = $this->getPluginNames($options);
		foreach ($plugin_names as $plugin_name) {
			if (!($plugin_info = $this->getPlugin($plugin_name))) { continue; }
			if ($translated_text = $plugin_info['INSTANCE']->translate($text, $to_lang, $options)) {
				return $translated_text;
			}
		}
		return false;
	}
	# ----------------------------------------------------------
}
