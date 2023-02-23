<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrlParser/Deepl.php :
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
 * @subpackage MediaUrlParser
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\LanguageTranslation\Plugins;
 
 /**
  *
  */
  require_once(__CA_LIB_DIR__.'/Plugins/LanguageTranslation/BaseLanguageTranslationManagerPlugin.php');
 
class Deepl Extends BaseLanguageTranslationManagerPlugin {	
	# ------------------------------------------------
	/**
	 *
	 */
	
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->description = _t('Translate text using Deepl API');
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function register() {
		if(!defined('__DEEPL_API_KEY__')) { return false; }
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function checkStatus() {
		$status = parent::checkStatus();
		$status['available'] = is_array($this->register()); 
		return $status;
	}
	# ------------------------------------------------
	/**
	 * 
	 */
	public function translate(string $text, string $to_lang, ?array $options=null) : string {
		if(!defined('__DEEPL_API_KEY__')) { return null; }
		$translator = new \DeepL\Translator(__DEEPL_API_KEY__);

		$result = $translator->translateText($text, null, $to_lang);
		return $result;	
	}
	# ------------------------------------------------
}
