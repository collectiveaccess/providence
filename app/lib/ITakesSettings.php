<?php
/** ---------------------------------------------------------------------
 * app/interfaces/ITakesSettings.php: interface for classes that use settings (per-row configuration values)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
 interface ITakesSettings {
 	
	/**
	 * Returns associative array of setting descriptions (but *not* the setting values)
	 * The keys of this array are the setting codes, the values associative arrays containing
	 * info about the setting itself (label, description type of value, how to display an entry element for the setting in a form)
	 */
	public function getAvailableSettings();
	
	/**
	 * Returns an associative array with the setting values for this restriction
	 * The keys of the array are setting codes, the values are the setting values
	 */
	public function getSettings();
	
	/**
	 * Set setting value 
	 * (you must call insert() or update() to write the settings to the database)
	 */
	public function setSetting($ps_setting, $pm_value);
	
	/**
	 * Return setting value
	 */
	public function getSetting($ps_setting);
	
	/**
	 * Returns true if setting code exists for the current element's datatype
	 */ 
	public function isValidSetting($ps_setting);
	
	/**
	 * Returns HTML form element for editing of setting
	 */ 
	public function settingHTMLFormElement($ps_setting);
 }