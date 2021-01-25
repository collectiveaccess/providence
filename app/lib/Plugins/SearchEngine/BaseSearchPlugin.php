<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/BaseSearchPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2021 Whirl-i-Gig
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
  
 require_once(__CA_LIB_DIR__.'/Plugins/WLPlug.php');
 require_once(__CA_LIB_DIR__.'/Plugins/IWLPlugSearchEngine.php');

abstract class BaseSearchPlugin extends WLPlug implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	
	/**
	 * Application configuration (app.conf)
	 *
	 * @var Configuration
	 */
	protected $config;
	
	/**
	 * Search configuration (search.conf)
	 *
	 * @var Configuration
	 */
	protected $search_config;
	
	/**
	 *
	 */
	protected $encoding;
	
	/**
	 * Current character encoding
	 *
	 * @var string
	 */
	protected $filters;
	
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
	 * @param Db $db Database connection to use. If omitted a new connection is created.
	 */
	public function __construct($db=null) {
		
		$this->config = Configuration::load();
		$this->search_config = Configuration::load(__CA_CONF_DIR__.'/search.conf');
		$this->encoding = $this->config->get('character_set');
		
		$this->db = $db ? $db : new Db();
		
		$this->init();
		parent::__construct();
	}
	# -------------------------------------------------------
	/**
	 * Returns true/false indication of whether the plug-in has a capability
	 *
	 * @param string $capability Name of capability
	 * @return bool True if plugin has capability
	 */
	public function can($capability) {
		return $this->capabilities[$capability];
	}
	# -------------------------------------------------------
	# Options
	# -------------------------------------------------------
	/**
	 * Set value for option
	 *
	 * @param string $option Name of option
	 * @param mixed $value option setting
	 * @return bool True on succes, false if option in not valid
	 */
	public function setOption($option, $value) {
		if ($this->isValidOption($option)) {
			$this->options[$option] = $value;

			switch($option) {
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
	 * @param string $option
	 * @return mixed Option setting or null if option is not valid
	 */
	public function getOption($option) {
		return $this->options[$option];
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
	 * @param string $option Option name
	 * @return true if option is valid
	 */
	public function isValidOption($option) {
		return in_array($option, $this->getAvailableOptions());
	}
	# -------------------------------------------------
	/**
	 * Set filtering of final search result
	 *
	 * @param string $access_point
	 * @param string $operator A valid comparison operator
	 * @param mixed $value Value to filter on
	 */
	public function addFilter($access_point, $operator, $value) {
		$this->opa_filters[] = [
			'access_point' => $access_point, 
			'operator' => $operator, 
			'value ' => $value
		];
	}
	# -------------------------------------------------
	/**
	 * Get list of currently applied filters
	 *
	 * @return array
	 */
	public function getFilters() {
		return $this->filters;
	}
	# --------------------------------------------------
	/**
	 * Remove all currently set filters
	 */
	public function clearFilters() {
		$this->filters = [];
	}
	# --------------------------------------------------
	/**
	 * Set database connection
	 *
	 * @param DbDriverBase $db instance of the db driver you are using
	 */
	public function setDb(Db $db) {
		$this->db = $db;
	}
	# --------------------------------------------------
}
