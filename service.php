<?php
/* ----------------------------------------------------------------------
 * service.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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

	// Prevent caching
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
?>