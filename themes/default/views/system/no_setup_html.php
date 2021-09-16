<?php
/* ----------------------------------------------------------------------
 * views/system/no_setup_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>CollectiveAccess error</title>
	<link href="<?= caGetThemeUrlPath(); ?>/css/error.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<div id='errorDetails'>
		<div id="logo"><img src='<?= caGetThemeUrlPath(); ?>/graphics/logos/logo.svg' alt='CollectiveAccess logo' width='327px' height='45px' /></div><!-- end logo -->
		<div id="content">
			<div class='error'>No <code>setup.php</code> file found!</div>
			<div id="errorLocation" class="errorPanel">
				<div class="errorDescription">
						In the main directory of your Providence install there is a file called <code>setup.php-dist</code>. 
						Make a copy of this file and rename it to <code>setup.php</code>. For your CollectiveAccess system to work you MUST add 
						values for your database server hostname, user name, password, database, and administrative e-email. More more information see the 
						<a href="https://manual.collectiveaccess.org/setup/Installation.html">installation instructions</a> and the manual page for <a href="https://manual.collectiveaccess.org/setup/setup.php.html">setup.php</a>.
				</div>
			</div>
		</div><!-- end content -->
	</div><!-- end box -->
</body>
</html>
