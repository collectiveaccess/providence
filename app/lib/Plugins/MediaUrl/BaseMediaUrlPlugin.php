<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrlParser/BaseMediaUrlParserPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2025 Whirl-i-Gig
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
namespace CA\MediaUrl\Plugins;

include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");

abstract class BaseMediaUrlPlugin extends \WLPlug  {
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
	abstract public function parse(string $url, ?array $options=null);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function fetch(string $url, ?array $options=null);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function fetchPreview(string $url, ?array $options=null);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function embedTag(string $url, ?array $options=null);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function icon(string $url, ?array $options=null);
	# ------------------------------------------------
	/**
	 *
	 */
	abstract public function service(string $url, ?array $options=null);
	# ------------------------------------------------
	/**
	 *
	 */
	protected function getConfiguredIcon(string $plugin, string $service, ?array $options=null) : ?string {
		$icon_config = \Configuration::load()->getAssoc('fetched_media_default_icons');
		if(!is_array($icon_config)) { return null; }
		if(!($icon = $icon_config[$plugin][$service] ?? null)) { return null; }
		
		$size = $options['size'] ?? null;
		
		if(!is_null($size)) {
			$icon = str_replace('^SIZE', $size, $icon);
		}
		return $icon;
	}
	# ------------------------------------------------
}
