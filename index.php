<?php
/* ----------------------------------------------------------------------
 * index.php : primary application controller for cataloguing module
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
define("__CA_APP_TYPE__", "PROVIDENCE");
define("__CA_MICROTIME_START_OF_REQUEST__", microtime());
define("__CA_BASE_MEMORY_USAGE__", memory_get_usage(true));
require("./app/helpers/errorHelpers.php");

if (!file_exists('./setup.php')) {
	require_once("./themes/default/views/system/no_setup_html.php");
	exit; 
}
require('./setup.php');
require_once('./app/helpers/post-setup.php');

try {
	// connect to database
	$o_db = new Db(null, null, false);
	
	//
	// do a sanity check on application and server configuration before servicing a request
	//
	require_once(__CA_APP_DIR__.'/lib/ConfigurationCheck.php');
	ConfigurationCheck::performQuick();
	if(ConfigurationCheck::foundErrors()){
		if (defined('__CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__') && __CA_ALLOW_AUTOMATIC_UPDATE_OF_DATABASE__ && ($_REQUEST['updateSchema'] ?? false)) {
			ConfigurationCheck::updateDatabaseSchema();
		} else {
			ConfigurationCheck::renderErrorsAsHTMLOutput();
		}
		exit();
	}
	
	if(isset($_REQUEST['processIndexingQueue']) && (bool)$_REQUEST['processIndexingQueue'] && !Configuration::load()->get('disable_background_processing') && (Configuration::load()->get('background_process_mode') === 'socket')) {
		ca_search_indexing_queue::process();
		exit();
	}
	if(isset($_REQUEST['processTaskQueue']) && (bool)$_REQUEST['processTaskQueue'] && !Configuration::load()->get('disable_background_processing') && (Configuration::load()->get('background_process_mode') === 'socket')) {
		TaskQueue::run(['quiet' => true]);
		exit();
	}

	caGetSystemGuid();
	
	// run garbage collector
	GarbageCollection::gc();

	$g_errored = false;	// routing error?
	$app = AppController::getInstance();

	$g_request = $req = $app->getRequest();
	$g_response = $resp = $app->getResponse();

	// Prevent caching
	$resp->addHeader("Cache-Control", "no-cache, no-store, must-revalidate");
	$resp->addHeader("Expires", "Mon, 26 Jul 1997 05:00:00 GMT");
	
	
	// Security headers
	$resp->addHeader("X-XSS-Protection", "1; mode=block");
	$resp->addHeader("X-Frame-Options", "SAMEORIGIN");

	$vt_app_plugin_manager = new ApplicationPluginManager();
	if($vt_app_plugin_manager->hookAddDomainSecurityPolicy(null) && is_array($vt_app_plugin_manager->hookAddDomainSecurityPolicy(null))) {
		$vs_security_policy_domains = implode(" ",$vt_app_plugin_manager->hookAddDomainSecurityPolicy());
	} else {
		$vs_security_policy_domains = "";
	}

	$resp->addHeader("Content-Security-Policy", "script-src 'self' maps.googleapis.com cdn.knightlab.com nominatim.openstreetmap.org  ajax.googleapis.com tagmanager.google.com www.googletagmanager.com www.google-analytics.com www.google.com/recaptcha/ www.gstatic.com ".$vs_security_policy_domains." 'unsafe-inline' 'unsafe-eval';"); 
	$resp->addHeader("X-Content-Security-Policy", "script-src 'self' maps.googleapis.com cdn.knightlab.com nominatim.openstreetmap.org  ajax.googleapis.com  tagmanager.google.com www.googletagmanager.com www.google-analytics.com www.google.com/recaptcha/ www.gstatic.com ".$vs_security_policy_domains." 'unsafe-inline' 'unsafe-eval';"); 

	//
	// Don't try to authenticate when doing a login attempt or trying to access the 'forgot password' feature
	//
	if ((AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_USE_ADAPTER_LOGIN_FORM__) && !preg_match("/^[\/]{0,1}system\/auth\/callback/", strtolower($req->getPathInfo()))) || !preg_match("/^[\/]{0,1}system\/auth\/(dologin|login|forgot|requestpassword|initreset|doreset|callback)/", strtolower($req->getPathInfo()))) {
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

	//
	// PageFormat plug-in generates header/footer shell around page content
	//
	$app->registerPlugin(new PageFormat());

	//
	// Dispatch the request
	//
	$app->dispatch(true);

	//
	// Send output to client
	//
	$resp->sendResponse();
	$req->close();
} catch(DatabaseException $e) {
	$opa_error_messages = ["Could not connect to database. Check your database configuration in <em>setup.php</em>: ".$e->getMessage()];
	require_once(__CA_BASE_DIR__."/themes/default/views/system/configuration_error_html.php");
	exit();
} catch (Exception $e) {
	caDisplayException($e);
}
