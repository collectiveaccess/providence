<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/BaseSearchPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
	
	protected $opo_config;
	protected $opo_search_config;
	
	protected $opo_datamodel;
	protected $ops_encoding;
	
	protected $opa_filters;
	protected $opa_capabilities;
	
	protected $opo_db;			// db connection
	protected $opa_options;
	
	# -------------------------------------------------------
	public function __construct() {
		
		$this->opo_config = Configuration::load();
		$this->opo_search_config = Configuration::load($this->opo_config->get('search_config'));
		$this->opo_datamodel = Datamodel::load();
		$this->ops_encoding = $this->opo_config->get('character_set');
		
		$this->opo_db = new Db();
		
		$this->init();
		parent::__construct();
	}
	# -------------------------------------------------------
	/**
	 * Returns true/false indication of whether the plug-in has a capability
	 **/
	public function can($ps_capability) {
		return $this->opa_capabilities[$ps_capability];
	}
	# -------------------------------------------------------
	# Options
	# -------------------------------------------------------
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
	public function getOption($ps_option) {
		return $this->opa_options[$ps_option];
	}
	# -------------------------------------------------------
	public function getAvailableOptions() {
		return array_keys($this->opa_options);
	}
	# -------------------------------------------------------
	public function isValidOption($ps_option) {
		return in_array($ps_option, $this->getAvailableOptions());
	}
	# -------------------------------------------------
	public function addFilter($ps_access_point, $ps_operator, $pm_value) {
		$this->opa_filters[] = array(
			'access_point' => $ps_access_point, 
			'operator' => $ps_operator, 
			'value ' => $pm_value
		);
	}
	# -------------------------------------------------
	public function getFilters() {
		return $this->opa_filters;
	}
	# --------------------------------------------------
	public function clearFilters() {
		$this->opa_filters = array();
	}
	# --------------------------------------------------
}

?>