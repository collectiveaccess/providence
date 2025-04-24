<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/pageHeader.php : 
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
if(!($window_title = trim(MetaTagManager::getWindowTitle()))) {
	$breadcrumb = $this->getVar('nav')->getDestinationAsBreadCrumbTrail();
	if (is_array($breadcrumb) && sizeof($breadcrumb)) {
		$window_title = array_pop($breadcrumb);
	}
}
$window_title = strip_tags($window_title);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=EDGE" />
	    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0"/>

		<title><?= $this->appconfig->get("window_title").($window_title ? " : {$window_title}" : ''); ?></title>

		<script type="text/javascript">window.caBasePath = '<?= $this->request->getBaseUrlPath(); ?>';</script>
<?php
	print AssetLoadManager::getLoadHTML($this->request, ['outputTarget' => 'header']);
	print MetaTagManager::getHTML();
	
	if ($local_css_url_path = $this->request->getUrlPathForThemeFile("css/local.css")) {
		print "<link rel='stylesheet' href='{$local_css_url_path}' type='text/css' media='screen' />
";
	}
	
	//
	// Pull in JS and CSS for debug bar
	// 
	if(Debug::isEnabled()) {
		$o_debugbar_renderer = Debug::$bar->getJavascriptRenderer();
		$o_debugbar_renderer->setBaseUrl(__CA_URL_ROOT__.$o_debugbar_renderer->getBaseUrl());
		print $o_debugbar_renderer->renderHead();
	}
?>
		<script type="text/javascript">
			// initialise plugins
			jQuery(document).ready(function() {
				jQuery('ul.sf-menu').superfish(
					{
						delay: 350,
						speed: 150,
						disableHI: true,
						animation: { opacity: 'show' }
					}
				);
			});
			
			// initialize CA Utils
			caUI.initUtils({unsavedChangesWarningMessage: '<?php _p('You have made changes in this form that you have not yet saved. If you navigate away from this form you will lose your unsaved changes.'); ?>'});

			var caPromptManager = caUI.initPromptManager();
			let providenceUIApps = {};
		</script>
	</head>	
	<body id="providenceApp">
		<div align="center">
