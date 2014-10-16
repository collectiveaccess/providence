<?php
/* ----------------------------------------------------------------------
 * app/helpers/requestHelpers.php : utility functions for handling incoming requests
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2012 Whirl-i-Gig
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
 	
 
	 # --------------------------------------------------------------------------------------------
	 /**
	  * Returns theme for user agent of current request using supplied user agent ("device") mappings
	  *
	  * @param array $pa_theme_device_mappings Array of mappings; keys are Perl-compatible regexes to be applied to the user agent; values are the names of themes to do upon a regex match; the theme assigned to the special _default_ key is used if there are no matches
	  * @return string Name of theme to use. If there are no matches and there is no _default_ value set in the mappings, then the string "default" will be returned. (It is assumed there is always a theme named "default" available.)
	  */
	function caGetPreferredThemeForCurrentDevice($pa_theme_device_mappings) {
		$vs_default_theme = 'default';
		if (is_array($pa_theme_device_mappings)) {
			foreach($pa_theme_device_mappings as $vs_user_agent_regex => $vs_theme) {
				if ($vs_user_agent_regex === '_default_') {
					$vs_default_theme = $vs_theme; 
					continue;
				}
				if (preg_match('!'.$vs_user_agent_regex.'!i', $_SERVER['HTTP_USER_AGENT'])) {
					return $vs_theme;
				}
			}
		}

		return $vs_default_theme;
	}
	# --------------------------------------------------------------------------------------------
	 /**
	  * Return true if user agent of current request appears to be Mobile (Eg. iPhone) 
	  *
	  * @return bool
	  */
	function caDeviceIsMobile() {
		if (preg_match('!iPhone!i', $_SERVER['HTTP_USER_AGENT'])) {
			return true;
		}
				
		return false;
	}
	# --------------------------------------------------------------------------------------------
	 /**
	  * Return true if system is configured to use identifers (idno's) rather than internal numeric CA primary keys
	  * in urls when referring to a specific record
	  *
	  * @return bool
	  */
	function caUseIdentifiersInUrls() {
		$o_config = Configuration::load();
		return (bool)$o_config->get('use_identifiers_in_urls');
	}
	# --------------------------------------------------------------------------------------------
	 /**
	  * 
	  *
	  * @return string
	  */
	function caUrlNameToTable($ps_name) {
		$va_url_names_to_tables = array(
 			'objects' 		=> 'ca_objects',
 			'entities' 		=> 'ca_entities',
 			'places' 		=> 'ca_places',
 			'occurrences' 	=> 'ca_occurrences',
 			'collections' 	=> 'ca_collections'
 		);
 		
 		return isset($va_url_names_to_tables[$ps_name]) ? $va_url_names_to_tables[$ps_name] : null;
	}
	# ---------------------------------------------------------------------------------------------
 ?>