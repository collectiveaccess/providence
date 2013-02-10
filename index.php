<?php
/* ----------------------------------------------------------------------
 * index.php : primary application controller for cataloguing module
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
	define("__CA_MICROTIME_START_OF_REQUEST__", microtime());
	define("__CA_BASE_MEMORY_USAGE__", memory_get_usage(true));
	define("__CA_APP_TYPE__", "PROVIDENCE");
	
	if (!file_exists('./setup.php')) { print "No setup.php file found!"; exit; }
	require('./setup.php');
	
	caWriteServerConfigHints();
	// connect to database
	$o_db = new Db(null, null, false);
	if (!$o_db->connected()) {
		$opa_error_messages = array("Could not connect to database. Check your database configuration in <em>setup.php</em>.");
		require_once(__CA_BASE_DIR__."/themes/default/views/system/configuration_error_html.php");
		exit();
	}
	$g_monitor = new ApplicationMonitor();
	if ($g_monitor->isEnabled()) { $o_db->setMonitor($g_monitor); }
	
	//
	// do a sanity check on application and server configuration before servicing a request
	//
	require_once(__CA_APP_DIR__.'/lib/ca/ConfigurationCheck.php');
	ConfigurationCheck::performQuick();
	if(ConfigurationCheck::foundErrors()){
		if (defined('__CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__') && __CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__ && $_REQUEST['updateSchema']) { 
			ConfigurationCheck::updateDatabaseSchema();
		} else {
			ConfigurationCheck::renderErrorsAsHTMLOutput();
		}
		exit();
	}
	
	$app = AppController::getInstance();
	
	$g_request = $req = $app->getRequest();
	$g_response = $resp = $app->getResponse();
	
	// Prevent caching
	$resp->addHeader("Cache-Control", "no-cache, must-revalidate");
	$resp->addHeader("Expires", "Mon, 26 Jul 1997 05:00:00 GMT");
	
	//
	// Don't try to authenticate when doing a login attempt (will be done in the action, of course)
	//
	if (!preg_match("/^\/system\/auth\/(dologin|login)/i", $req->getPathInfo())) {
		$vb_auth_success = $req->doAuthentication(array('noPublicUsers' => true));

		if(!$vb_auth_success) {
			$resp->sendResponse();
			$req->close();
			exit;
		}
	}

	// TODO: move this into a library so $_, $g_ui_locale_id and $g_ui_locale gets set up automatically
	$g_ui_locale_id = $req->user->getPreferredUILocaleID();			// get current UI locale as locale_id	 			(available as global)
	$g_ui_locale = $req->user->getPreferredUILocale();				// get current UI locale as locale string 			(available as global)
	$g_ui_units_pref = $req->user->getPreference('units');			// user's selected display units for measurements 	(available as global)
	
	if((!isset($_locale)) || ($g_ui_locale != $_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) {
		if(!initializeLocale($g_ui_locale)) die("Error loading locale ".$g_ui_locale);
		$req->reloadAppConfig();
	}

	global $ca_translation_cache;
	$ca_translation_cache = array();
	
	//
	// PageFormat plug-in generates header/footer shell around page content
	//
	require_once(__CA_APP_DIR__.'/lib/ca/PageFormat.php');
	if (!$req->isAjax() && !$req->isDownload()) {
		$app->registerPlugin(new PageFormat());
	}
	
	//
	// Dispatch the request
	//
	$app->dispatch(true);
	
	//
	// Send output to client
	//
	$resp->sendResponse();
	$req->close();
	
?>