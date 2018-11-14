<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Browse/BaseBrowsePlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
namespace BrowsePlugins;

require_once(__CA_LIB_DIR__.'/Db.php');
require_once(__CA_LIB_DIR__.'/Configuration.php');

abstract class BaseBrowsePlugin {
	# -------------------------------------------------------
	
	/**
	 * Application configuration (app.conf)
	 *
	 * @var Configuration
	 */
	protected $config;
	
	/**
	 * Browse configuration (browse.conf)
	 *
	 * @var Configuration
	 */
	protected $browse_config;
	
	/**
	 * List of plugin capabilities
	 *
	 * @var array
	 */
	protected $capabilities;
	
	/**
	 * Database connection
	 *
	 * @var Db
	 */
	protected $db;
	
	/**
	 * List of plugin options
	 *
	 * @var array
	 */
	protected $options;
	
	# -------------------------------------------------------
	/**
	 * @param Db $po_db Database connection to use. If omitted a new connection is created.
	 */
	public function __construct($db=null) {
		
		$this->config = \Configuration::load();
		$this->browse_config = \Configuration::load(__CA_CONF_DIR__.'/browse.conf');
		
		$this->db = $db ? $db : new \Db();
	}
	# -------------------------------------------------------
	/**
	 * Returns true/false indication of whether the plug-in has a capability
	 *
	 * @param string $ps_capability Name of capability
	 * @return bool True if plugin has capability
	 */
	public function can($ps_capability) {
		return $this->capabilities[$ps_capability];
	}
	# -------------------------------------------------------
	# Options
	# -------------------------------------------------------
	/**
	 * Set value for option
	 *
	 * @param string $ps_option Name of option
	 * @param mixed $pm_value option setting
	 * @return bool True on succes, false if option in not valid
	 */
	public function setOption($ps_option, $pm_value) {
		if ($this->isValidOption($ps_option)) {
			$this->options[$ps_option] = $pm_value;

			switch($ps_option) {
				case 'limit':
					// noop
					break;
			}
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Get current setting for option
	 *
	 * @param string $ps_option
	 * @return mixed Option setting or null if option is not valid
	 */
	public function getOption($ps_option) {
		return $this->options[$ps_option];
	}
	# -------------------------------------------------------
	/**
	 * Return list of options defined for plugin
	 *
	 * @return array 
	 */
	public function getAvailableOptions() {
		return array_keys($this->options);
	}
	# -------------------------------------------------------
	/**
	 * Check if option is defined for plugin
	 * 
	 * @param string $ps_option Option name
	 * @return true if option is valid
	 */
	public function isValidOption($ps_option) {
		return in_array($ps_option, $this->getAvailableOptions());
	}
	# -------------------------------------------------
	/**
	 * Set database connection
	 *
	 * @param DbDriverBase $po_db instance of the db driver you are using
	 */
	public function setDb($db) {
		$this->db = $db;
	}
	# --------------------------------------------------
	/**
	 * Return status information. Must be overridden by plugins.
	 *
	 * @return array Array with plugin status information or false if plugin did not load
	 */
	public function checkStatus() {
		return [];
	}
	# --------------------------------------------------
}