<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/BaseInformationServicePlugin.php : base class for geographic map plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2026 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugInformationService.php");
include_once(__CA_LIB_DIR__."/Configuration.php");

abstract class BaseInformationServicePlugin Extends WLPlug {
	// properties for this plugin instance
	protected $properties = array(
		
	);
	
	// app config
	protected $opo_config;
	
	

	// plugin info
	protected $info = array(
		"NAME" => "InformationService",
		"PROPERTIES" => array(
			'id' => 'W'
		)
	);
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function init() {
	
	}
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
	# ----------------------------------------------------------
	/**
	 * Get map property
	 *
	 * @param $property - name of property - must be defined as a property in the plugin's 'PROPERTIES' array
	 * @return string - the value of the property or null if the property is not valid
	 */
	public function get($property) {
		if ($this->info["PROPERTIES"][$property]) {
			return $this->properties[$property];
		} else {
			//print "Invalid property";
			return null;
		}
	}
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function set($property, $value) {
		if ($this->info["PROPERTIES"][$property]) {
			switch($property) {
				default:
					if ($this->info["PROPERTIES"][$property] == 'W') {
						$this->properties[$property] = $value;
					} else {
						# read only
						return '';
					}
					break;
			}
		} else {
			# invalid property
			$this->postError(1650, _t("Can't set property %1", $property), "WLPlugInformationServiceGoogleMaps->set()");
			return '';
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return string
	 */
	public function getDisplayName() {
		return $this->info['NAME'];
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
	/**
	 * Should be overridden in implementation
	 * @param array $pa_settings
	 * @param string $ps_url
	 * @return array
	 */
	public function getDataForSearchIndexing($pa_settings, $ps_url) {
		return array();
	}
	# ------------------------------------------------
	/**
	 * Extract a display value from the lookup text
	 * @param string $ps_text
	 * @return string
	 */
	public function getDisplayValueFromLookupText($ps_text) {
		return $ps_text;
	}
	# ------------------------------------------------
	/**
	 * Can be overriden in implementation to store addition bits of
	 * information about the value which is then available via get()
	 * @param array $pa_settings element settings
	 * @param string $ps_url
	 * @return array
	 */
	public function getExtraInfo($pa_settings, $ps_url) {
		return array();
	}
	# ------------------------------------------------
	/** 
	 * Can be overridden in implementation to render additional form elements
	 * in user interface. Returns array of HTML form elements.
	 *
	 * @param array $pa_settings element settings
	 * @return array
	 */
	public function getAdditionalFields(array $pa_element_info) : array {
		return [];
	}
	# ------------------------------------------------
	/** 
	 * Can be overridden in implementation to return array of values for additiona fields.
	 *
	 * @param ca_attribute_values Attribute value
	 * @return array
	 */
	public function getAdditionalFieldValues($attribute_value) : array {
		return [];
	}
	# ------------------------------------------------
	/** 
	 *
	 */
    public function getAvailableSettings() {
        $settings = static::$s_settings ?? [];
        return $settings;
    }
	# ------------------------------------------------
	/** 
	 * Mirror data to a configured list to support hierarchical browse on external vocabularies
	 *
	 * @param array $settings Metadata element settings
	 * @param array $data Data to mirror as an array of arrays. Each value array should include the following keys:
	 *			id = Item identiifer
	 *			name_singular = Singular name for item
	 *			name_plural = Plural name for item
	 *			description = Description of item
	 *			locale = Locale for item
	 *			type = Type code or type_id of list item. If not set, will defalt
	 * @param array $options No options are current supported
	 *
	 * @return int item_id of lowest level in hierarchy
	 */
    protected function mirrorToList(array $settings, array $data, ?array $options=null) : ?int  {
    	$parent_id = null;
    	if(($settings['useMirrorList'] ?? false) && ($list_id = ($settings['mirrorToList'] ?? null))){
    		$default_type = caGetDefaultItemID('list_item_types');
    		$default_locale = defined('__CA_DEFAULT_LOCALE__') ? __CA_DEFAULT_LOCALE__ : null;
    		foreach($data as $d) {
    			$locales = array_keys($d['preferred_labels']);
    			$locale = array_shift($locales);
    			$label = array_shift($d['preferred_labels']);
    			$t_parent = DataMigrationUtils::getListItemID($list_id, $d['id'], $d['type'] ?? $default_type, $locale ?? $default_locale, [
    				'parent_id' => $parent_id,
    				'preferred_labels' => $label,
    				'idno' => $d['id'],
    				'access' => $d['access']
    			], ['outputErrors' => false, 'returnInstance' => true, 'matchOn' => ['idno']]);
    			if(!$t_parent) { return null; }

				foreach($d['preferred_labels'] as $locale => $l) {
					if(!$t_parent->preferredLabelExistsForLocale($locale)) {
						$t_parent->addLabel($l, $locale, null, true);
					}
				}
				
				if(sizeof($d['nonpreferred_labels'] ?? []) && !$t_parent->getNonPreferredLabelCount()) {
					foreach($d['nonpreferred_labels'] as $locale => $npl) {
						foreach($npl as $l) {
							$t_parent->addLabel($l, $locale, null, false);
						}
					}
				}
				
				
				$parent_id = $t_parent->getPrimaryKey();
    		}
    	}
    	
    	return $parent_id;
    }
	# ------------------------------------------------
}
