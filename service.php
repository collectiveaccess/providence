<?php
/* ----------------------------------------------------------------------
 * service.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2020 Whirl-i-Gig
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

	if (!file_exists('./setup.php')) { print "No setup.php file found!"; exit; }
	require('./setup.php');

	// connect to database
	$o_db = new Db(null, null, false);

	$app = AppController::getInstance();

	$req = $app->getRequest();
	$resp = $app->getResponse();
	
	// TODO: move this into a library so $_, $g_ui_locale_id and $g_ui_locale gets set up automatically
	$g_ui_locale_id = $req->user->getPreferredUILocaleID();			// get current UI locale as locale_id	 			(available as global)
	$g_ui_locale = $req->user->getPreferredUILocale();				// get current UI locale as locale string 			(available as global)
	$g_ui_units_pref = $req->user->getPreference('units');			// user's selected display units for measurements 	(available as global)
	
	if((!isset($_locale)) || ($g_ui_locale != $_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) {
		if(!initializeLocale($g_ui_locale)) die("Error loading locale ".$g_ui_locale);
		$req->reloadAppConfig();
	}

	// Prevent caching
	$resp->addHeader('Access-Control-Allow-Origin', '*');
	$resp->addHeader("Cache-Control", "no-cache, must-revalidate");
	$resp->addHeader("Expires", "Mon, 26 Jul 1997 05:00:00 GMT");
	
	$vb_auth_success = $req->doAuthentication(array('noPublicUsers' => true, "dont_redirect" => true, "no_headers" => true));
	//
	// Dispatch the request
	//
	$app->dispatch(true);

	//
	// Send output to client
	//
	$resp->sendResponse();

	$req->close();
