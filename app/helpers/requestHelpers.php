<?php
/* ----------------------------------------------------------------------
 * app/helpers/requestHelpers.php : utility functions for handling incoming requests
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2018 Whirl-i-Gig
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
    if(isset($_GET['current_theme'])){
        $_COOKIE['current_theme'] = preg_replace("![^A-Za-z0-9\-\_]+!", "", $_GET['current_theme']);
    }
    if(isset($_COOKIE['current_theme']) && file_exists(__CA_THEMES_DIR__.'/'.$_COOKIE['current_theme'])){
        $vs_theme = $_COOKIE['current_theme'];
        return $vs_theme;
    }
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
  * Return true if user agent of current request appears to be a GoogleCloud health check
  *
  * @return bool
  */
function caRequestIsHealthCheck() {
	if (preg_match('!GoogleHC!i', $_SERVER['HTTP_USER_AGENT'])) {
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
  * Return true if system is configured to alternate identifers (a metadata element) rather than internal numeric CA primary keys
  * in urls when referring to a specific record
  *
  * @return bool
  */
function caUseAltIdentifierInUrls($ps_table) {
	$o_config = Configuration::load();
	return (bool)$o_config->get('use_alternate_identifiers_in_urls_for_'.$ps_table) ? $o_config->get('use_alternate_identifiers_in_urls_for_'.$ps_table) : false;
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
# --------------------------------------------------------------------------------------------
 /**
  * Check if composer-managed vendor libraries are installed
  *
  * @return bool
  */
function caCheckVendorLibraries() {
	if (!file_exists(__CA_BASE_DIR__."/vendor/autoload.php")) {
		if (((int)$_REQUEST['updateVendor'] === 1) && (defined('__CA_ALLOW_AUTOMATIC_UPDATE_OF_VENDOR_DIR__') && __CA_ALLOW_AUTOMATIC_UPDATE_OF_VENDOR_DIR__)) {
			if (sizeof($errors = caInstallVendorLibraries())) {
				$opa_error_messages = ["Automatic installation of the required vendor libraries failed: ".join("; ", $errors)];
				require_once(__CA_THEME_DIR__."/views/system/configuration_error_html.php");
				exit;
			}
		} else {
			if (defined('__CA_ALLOW_AUTOMATIC_UPDATE_OF_VENDOR_DIR__') && __CA_ALLOW_AUTOMATIC_UPDATE_OF_VENDOR_DIR__) {
				$opa_error_messages = ["Your installation is missing required vendor libraries. This is normal if you have just installed from a Git branch. <a href='index.php?updateVendor=1'><strong>Click here</strong></a> to automatically load the required libraries, or see the <a href=\"http://docs.collectiveaccess.org/wiki/Installing_Vendor_Libraries\">wiki</a> for instructions on installing the required libraries manually. <strong>NOTE: Installation may take some time.</strong>"];
			} else {
				$opa_error_messages = ["Your installation is missing required vendor libraries. This is normal if you have just installed from a Git branch. See the <a href=\"http://docs.collectiveaccess.org/wiki/Installing_Vendor_Libraries\">wiki</a> for instructions on installing the required libraries manually."];
			}
			require_once(__CA_THEME_DIR__."/views/system/configuration_error_html.php");
			exit;
		}
	}
	return true;
}
# --------------------------------------------------------------------------------------------
 /**
  * Install composer-managed vendor libraries if neccessary
  *
  * @return array
  */
function caInstallVendorLibraries() {
	$errors = [];
	if (!file_exists(__CA_BASE_DIR__."/vendor/autoload.php")) {
		if (!file_exists(__CA_BASE_DIR__."/vendor")) { 
			if (!(@mkdir(__CA_BASE_DIR__."/vendor")) || !file_exists(__CA_BASE_DIR__."/vendor")) {
				return ["Could not create the vendor directory. Make sure ".__CA_BASE_DIR__." is writable or create the directory manually and make sure it is writeable by the user which runs the webserver."];
			}
		}
		if (!is_writable(__CA_BASE_DIR__."/vendor")) { 
			return ["The vendor directory is not writable. Please change the permissions of ".__CA_BASE_DIR__."/vendor and enable the user which runs the webserver to write to this directory."];
		}
		if (!copy(__CA_BASE_DIR__.'/support/scripts/install_composer.sh.txt', __CA_APP_DIR__.'/tmp/install_composer.sh')) {
			return ["Could not copy composer installer to app/tmp"];
		}
		$output = [];
		putenv("COLLECTIVEACCESS_HOME=".__CA_BASE_DIR__);
		exec('sh '.__CA_APP_DIR__.'/tmp/install_composer.sh 2>&1', $output, $ret);
		if ($ret > 0) {
			return ["Composer installation failed: ".join("; ", $output)];
		}
		
		$output = [];
		putenv("COMPOSER_HOME=".__CA_BASE_DIR__."/app/tmp");
		chdir(__CA_BASE_DIR__);
		exec("php ".__CA_APP_DIR__.'/tmp/composer.phar -n install 2>&1', $output, $ret);
		if ($ret > 0) {
			return ["Library installation failed: ".join("; ", $output)];
		}
		
		unlink(__CA_APP_DIR__.'/tmp/composer.phar');
		unlink(__CA_APP_DIR__.'/tmp/install_composer.sh');
		return [];
	}
	
	return $errors;
}
# ---------------------------------------------------------------------------------------------
