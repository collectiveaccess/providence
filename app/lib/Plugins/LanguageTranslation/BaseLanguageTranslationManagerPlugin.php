<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrlParser/BaseLanguageTranslationManagerPlugin.php :
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
namespace CA\LanguageTranslation\Plugins;
 
include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");

abstract class BaseLanguageTranslationManagerPlugin extends \WLPlug  {
	# ------------------------------------------------
	/**
	 * 
	 */
	protected $info = [];
	
	/**
	 *
	 */
	protected $opo_config;
	
	# ------------------------------------------------
	public function __construct() {
		parent::__construct();
	}
	# ------------------------------------------------
	/** 
	 * Announce what kinds of media this plug-in supports for import and export
	 */
	public function register() {
		$this->opo_config = Configuration::load();
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}	
	# ------------------------------------------------
	/**
	 * 
	 */
	abstract public function getSourceLanguages() : array;
	# ------------------------------------------------
	/**
	 * 
	 */
	abstract public function getTargetLanguages() : array;
	# ------------------------------------------------
	/**
	 * 
	 */
	abstract public function translate(string $text, string $to_lang, ?array $options) : ?string;
	# ------------------------------------------------
	/**
	 * 
	 */
	abstract public function translateList(array $text, string $to_lang, ?array $options) : array;
	# ------------------------------------------------
}
