<?php
/* ----------------------------------------------------------------------
 * install/install.php : Controller for CollectiveAccess application installer
 *
 * NOTE: This is a standalone HTML page
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
//
// Send headers before any other content.
// Disable gzip or any compression to allow showing progressbar.
//
header('Content-Encoding: none');
define('__CollectiveAccess_Installer__', 1);

error_reporting(E_ALL ^ E_NOTICE);
set_time_limit(7200);
ini_set("memory_limit", "512M");	

// Check existence of setup.php
if (!file_exists('../setup.php')) {
	die("You don't have a setup.php file present in the web root directory of your CollectiveAccess set up. Please copy setup.php-dist to setup.php, customize it and <a href='index.php'>re-run the installer</a>.");
}
require_once('../setup.php');
require_once('../app/helpers/configurationHelpers.php');
require_once('../app/helpers/htmlFormHelpers.php');
require_once("inc/Installer.php");

$ps_instance = 	$_REQUEST['instance'];
$ps_action = 	$_REQUEST['action'];
$pn_page = 		$_REQUEST['page'];

if (defined('__CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__') && __CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__) {
	if ($ps_action == 'profileUpload') {
		$va_response = ['STATUS' => 'OK'];
		
		$vs_profile_dir = pathinfo(__FILE__, PATHINFO_DIRNAME).'/profiles';
		if (is_array($_FILES['files']['tmp_name'])) { 
			foreach($_FILES['files']['tmp_name'] as $vn_i => $vs_tmp_name) {
				if ($_FILES['files']['size'][$vn_i] <= 0) { 
					$va_response['skippedMessage'][] = _t('%1 is empty', $_FILES['files']['name'][$vn_i]);
					continue; 
				}
				// check if file looks like a profile
				if(!($format = \Installer\Installer::validateProfile(pathinfo($vs_tmp_name, PATHINFO_DIRNAME), pathinfo($vs_tmp_name, PATHINFO_BASENAME), pathinfo($_FILES['files']['name'][$vn_i], PATHINFO_EXTENSION)))) {
					$va_response['skippedMessage'][] = _t('%1 is not a valid profile', $_FILES['files']['name'][$vn_i]);
					continue; 
				}
				$format = strtolower($format);
			
				$vb_exists = file_exists($vs_profile_dir.'/'.$format.'/'.$_FILES['files']['name'][$vn_i]);
			
				// attempt to write to profile dir
				if (@copy($vs_tmp_name, $vs_profile_dir."/{$format}/".$_FILES['files']['name'][$vn_i])) {
					$va_response['added'][] = pathinfo($_FILES['files']['name'][$vn_i], PATHINFO_FILENAME);
					$va_response['uploadMessage'][] = $vb_exists ? _t('Updated %1', $_FILES['files']['name'][$vn_i]) : _t('Added %1', $_FILES['files']['name'][$vn_i]);
				} else {
					$va_response['skippedMessage'][] = _t('%1 could not be written', $_FILES['files']['name'][$vn_i]);
					continue; 
				}
				
			}
		}
		// return list of profiles
		$va_response['profiles'] = caGetAvailableProfiles();
		
		print json_encode($va_response);
		return;
	}
}
	
	$ps_email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
	$ps_profile = isset($_REQUEST['profile']) ? $_REQUEST['profile'] : '';
	$pb_overwrite = (isset($_REQUEST['overwrite']) && defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__')) ? (bool)$_REQUEST['overwrite'] : false;
	$pb_debug = (isset($_REQUEST['debug']) && defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__')) ? (bool)$_REQUEST['debug'] : false;

	$va_errors = array();
	
	$va_tmp = explode("/", str_replace("\\", "/", $_SERVER['SCRIPT_NAME']));
	array_pop($va_tmp);
	$vs_url_path = join("/", $va_tmp);
	
	// get current locale
	$locale = 'en_US';
	
	// get current theme
	$theme = 'default';
	
	$_ = new Zend_Translate('gettext', __CA_APP_DIR__.'/locale/'.$locale.'/messages.mo', $locale, ['disableNotices' => true]);
	
	// Check setup.php settings
	// ...
	
	if ($pn_page == 2) {
		$ps_email = $_REQUEST['email'];
		if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._\-\+])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/" , $ps_email)) {
			$va_errors[] = 'Administrator e-mail address is invalid';
			$pn_page = 1;
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<title>CollectiveAccess: Installer</title>
	<link href="css/site.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="../assets/fontawesome/css/all.min.css" />
	<link rel="stylesheet" href="../assets/fontawesome/css/v4-shims.min.css" />
	<link rel="stylesheet" href="../assets/jquery/jquery-ui-1.14.0/jquery-ui.min.css" />  
	<script src='../assets/jquery/jquery-3.7.1.js' type='text/javascript'></script>
	<script src='../assets/jquery/jquery-ui-1.14.0/jquery-ui.min.js ' type='text/javascript'></script></head>
<?php
if (defined('__CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__') && __CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__) {
?>
	<script src='../assets/jquery/jquery.iframe-transport.js' type='text/javascript'></script></head>
	<script src='../assets/jquery/jquery.ui.widget.js' type='text/javascript'></script></head>
	<script src='../assets/jquery/jquery.fileupload.js' type='text/javascript'></script></head>
<?php
}
?>
<body>
	<div class='content'>
<?php 
		switch($pn_page) {
			case 1:
			default:
				require_once("./inc/page1.php");
				break;
			case 2:
				require_once("./inc/page2.php");
				break;
		}
?>
	</div>
</body>
</html>
