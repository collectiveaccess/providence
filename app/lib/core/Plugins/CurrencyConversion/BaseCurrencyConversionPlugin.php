<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Visualizer/BaseCurrencyConversionPlugin.php : 
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
 * MERCHANTABILITY or FITNESSs FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Geographic
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugCurrencyConversion.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");

abstract class BaseCurrencyConversionPlugin Extends WLPlug {
	# ------------------------------------------------
	// properties for this plugin instance
	protected $properties = array(
		
	);
	
	// app config
	protected $opo_config;
	
	// plugin info
	protected $info = array(
		"NAME" => "?",
		"PROPERTIES" => array(
			'id' => 'W'
		)
	);
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->opo_config = Configuration::load();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function register() {
		$this->opo_config = Configuration::load();
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	/**
	 * Returns status of plugin. Normally this is overriden by the plugin subclass
	 *
	 * @return array - status info array; 'available' key determines if the plugin should be loaded or not
	 */
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	public static function normalizeCurrencySpecifier($ps_currency_specifier) {		
		switch($ps_currency_specifier) {
			case '$':
				$o_config = Configuration::load();
				$ps_currency_specifier = ($vs_dollars_are_this = $o_config->get('default_dollar_currency')) ? $vs_dollars_are_this : 'USD';
				break;
			case '¥':
				$ps_currency_specifier = 'JPY';
				break;
			case '£':
				$ps_currency_specifier = 'GBP';
				break;
			case '€':
				$ps_currency_specifier = 'EUR';
				break;
			default:
				$ps_currency_specifier = strtoupper($ps_currency_specifier);
				break;
		}
		return $ps_currency_specifier;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function init() {
		return;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
?>