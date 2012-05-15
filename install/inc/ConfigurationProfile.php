<?php
/* ----------------------------------------------------------------------
 * install/inc/ConfigurationProfile.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 
 class ConfigurationProfile {
 	# ------------------------------------------------------------------------------------------------
 	private $opa_profile_list;
 	# ------------------------------------------------------------------------------------------------
 	public function __construct($ps_profile_name, $ps_profile_dirpath) {
 		$this->loadProfile($ps_profile_name, $ps_profile_dirpath);
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function loadProfile($ps_profile_name, $ps_profile_dirpath) {
 		$this->opa_profile_list = array();
 		
 		do {
			$o_profile = Configuration::load($ps_profile_dirpath.'/'.$ps_profile_name.'.profile');
			array_push($this->opa_profile_list, $o_profile);
			
			$ps_profile_name = $o_profile->get('profile_base');
		} while($ps_profile_name);
		
		$this->opa_profile_list = array_reverse($this->opa_profile_list);
 		return true;
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getLocales() {
 		return $this->_mergeAssocValues('locales');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getLists() {
 		return $this->_mergeAssocValues('lists');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getMetadataElementSets() {
 		return $this->_mergeAssocValues('element_sets');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getUIs() {
 		return $this->_mergeAssocValues('uis');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getRelationshipTypes() {
 		return $this->_mergeAssocValues('relationship_types');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getRoles() {
 		return $this->_mergeAssocValues('roles');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getGroups() {
 		return $this->_mergeAssocValues('groups');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getBundleDisplays() {
 		return $this->_mergeAssocValues('bundle_displays');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function getBundleMappings() {
 		return $this->_mergeAssocValues('bundle_mappings');
 	}
 	# ------------------------------------------------------------------------------------------------
 	public function profileIsLoaded() {
 		return sizeof($this->opa_profile_list) ? true : false;
 	}
 	# ------------------------------------------------------------------------------------------------
 	private function _mergeAssocValues($ps_key) {
 		$va_value = array();
 		foreach($this->opa_profile_list as $o_profile) {
 			if (is_array($va_value_to_merge = $o_profile->getAssoc($ps_key))) {
 				$va_value = array_merge($va_value, $va_value_to_merge);
 			}
 		}
 		
 		return $va_value;
 	}
 	# ------------------------------------------------------------------------------------------------
 }
 ?>