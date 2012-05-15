<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchConfigurationSettings.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
# ------------------------------------------------
define('__CA_SEARCH_CONFIG_OK__',1000);
define('__CA_SEARCH_CONFIG_ERROR__',1001);
define('__CA_SEARCH_CONFIG_WARNING__',1002);
# ------------------------------------------------

/**
 * Using external applications (e.g. search engines) in conjunction with a PHP
 * driven software that is usually interpreted by a web server typically
 * results in a set of problems that the system administrator needs to fix.
 * Typical examples are:
 *  * is the search engine configured properly to work with CA?
 *  * is the web server user able to write the search engine configuration?
 *  * is the external application running?
 *  * was the external application started by the "wrong" user?
 *
 * The CollectiveAccess search engine and its plugins shall provide facilities
 * for system administrators to locate those configuration errors quickly and
 * fix them. This class encapsulates a set of configuration settings and shall be
 * implemented by the underlying plugins to tell the search engine and the
 * layers above if something is going wrong (or not).
 */
abstract class ASearchConfigurationSettings {
	# ------------------------------------------------
	protected $opa_possible_errors;
	# ------------------------------------------------
	private $opa_settings;
	private $opn_current_setting;
	# ------------------------------------------------
	protected function __construct(){
		$this->setSettings();
		/* child should've set that */
		if(is_array($this->opa_possible_errors)){
			$this->opn_current_setting=0;
			$this->opa_settings = array();
			foreach($this->opa_possible_errors as $vn_setting_num){
				$this->opa_settings[$this->opn_current_setting] = array();
				$this->opa_settings[$this->opn_current_setting]['status'] = $this->checkSetting($vn_setting_num);
				$this->opa_settings[$this->opn_current_setting]['description'] = $this->getSettingDescription($vn_setting_num);
				$this->opa_settings[$this->opn_current_setting]['name'] = $this->getSettingName($vn_setting_num);
				$this->opa_settings[$this->opn_current_setting]['hint'] = $this->getSettingHint($vn_setting_num);
				$this->opn_current_setting++;
			}
		}
		$this->reset();
	}
	# ------------------------------------------------
	public function __destruct(){
		unset($this->opa_possible_errors);
		unset($this->opa_errors);
		unset($this->opn_current_error);
	}
	# ------------------------------------------------
	public function nextSetting(){
		if($this->opn_current_setting < sizeof($this->opa_settings)-1){
			$this->opn_current_setting++;
			return true;
		} else {
			return false;
		}
	}
	# ------------------------------------------------
	public function numSettings(){
		if(is_array($this->opa_settings)){
			return sizeof($this->opa_settings);
		} else {
			return 0;
		}
	}
	# ------------------------------------------------
	public function reset(){
		$this->opn_current_setting=-1;
	}
	# ------------------------------------------------
	public function getCurrentStatus(){
		return isset($this->opa_settings[$this->opn_current_setting]['status']) ?
			$this->opa_settings[$this->opn_current_setting]['status'] : null;
	}
	# ------------------------------------------------
	public function getCurrentDescription(){
		return isset($this->opa_settings[$this->opn_current_setting]['description']) ?
			$this->opa_settings[$this->opn_current_setting]['description'] : null;
	}
	# ------------------------------------------------
	public function getCurrentName(){
		return isset($this->opa_settings[$this->opn_current_setting]['name']) ?
			$this->opa_settings[$this->opn_current_setting]['name'] : null;
	}
	# ------------------------------------------------
	public function getCurrentHint(){
		return isset($this->opa_settings[$this->opn_current_setting]['hint']) ?
			$this->opa_settings[$this->opn_current_setting]['hint'] : null;
	}
	# ------------------------------------------------
	abstract function setSettings(); // is supposed to have side-effect on opa_possible_errors property!
	# ------------------------------------------------
	abstract function checkSetting($pn_setting_num);
	# ------------------------------------------------
	abstract function getSettingDescription($pn_setting_num);
	abstract function getSettingName($pn_setting_num);
	abstract function getSettingHint($pn_setting_num);
	# ------------------------------------------------
}
# ------------------------------------------------

