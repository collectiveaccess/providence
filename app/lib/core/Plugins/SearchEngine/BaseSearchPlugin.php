<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/BaseSearchPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2015 Whirl-i-Gig
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
  
  
 require_once(__CA_LIB_DIR__.'/core/Db.php');
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/WLPlug.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/IWLPlugSearchEngine.php');

abstract class BaseSearchPlugin extends WLPlug implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	
	/**
	 * Application configuration (app.conf)
	 *
	 * @var Configuration
	 */
	protected $opo_config;
	
	/**
	 * Search configuration (search.conf)
	 *
	 * @var Configuration
	 */
	protected $opo_search_config;
	
	/**
	 * Datamodel (datamodel.conf)
	 *
	 * @var Datamodel
	 */
	protected $opo_datamodel;
	
	/**
	 *
	 */
	protected $ops_encoding;
	
	/**
	 * Current character encoding
	 *
	 * @var string
	 */
	protected $opa_filters;
	
	/**
	 * List of plugin capabilities
	 *
	 * @var array
	 */
	protected $opa_capabilities;
	
	/**
	 * Database connection
	 *
	 * @var Db
	 */
	protected $opo_db;
	
	/**
	 * List of plugin options
	 *
	 * @var array
	 */
	protected $opa_options;
	
	# -------------------------------------------------------
	/**
	 * @param Db $po_db Database connection to use. If omitted a new connection is created.
	 */
	public function __construct($po_db=null) {
		
		$this->opo_config = Configuration::load();
		$this->opo_search_config = Configuration::load(__CA_CONF_DIR__.'/search.conf');
		$this->opo_datamodel = Datamodel::load();
		$this->ops_encoding = $this->opo_config->get('character_set');
		
		$this->opo_db = $po_db ? $po_db : new Db();
		
		$this->init();
		parent::__construct();
	}
	# -------------------------------------------------------
	/**
	 * Returns true/false indication of whether the plug-in has a capability
	 *
	 * @param string $ps_capability Name of capability
	 * @return bool True if plugin has capability
	 */
	public function can($ps_capability) {
		return $this->opa_capabilities[$ps_capability];
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
			$this->opa_options[$ps_option] = $pm_value;

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
		return $this->opa_options[$ps_option];
	}
	# -------------------------------------------------------
	/**
	 * Return list of options defined for plugin
	 *
	 * @return array 
	 */
	public function getAvailableOptions() {
		return array_keys($this->opa_options);
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
	 * Set filtering of final search result
	 *
	 * @param string $ps_access_point
	 * @param string $ps_operator A valid comparison operator
	 * @param mixed $pm_value Value to filter on
	 */
	public function addFilter($ps_access_point, $ps_operator, $pm_value) {
		$this->opa_filters[] = array(
			'access_point' => $ps_access_point, 
			'operator' => $ps_operator, 
			'value ' => $pm_value
		);
	}
	# -------------------------------------------------
	/**
	 * Get list of currently applied filters
	 *
	 * @return array
	 */
	public function getFilters() {
		return $this->opa_filters;
	}
	# --------------------------------------------------
	/**
	 * Remove all currently set filters
	 */
	public function clearFilters() {
		$this->opa_filters = array();
	}
	# --------------------------------------------------
	/**
	 * Set database connection
	 *
	 * @param DbDriverBase $po_db instance of the db driver you are using
	 */
	public function setDb($po_db) {
		$this->opo_db = $po_db;
	}
	# --------------------------------------------------
}