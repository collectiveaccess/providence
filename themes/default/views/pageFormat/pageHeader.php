<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/pageHeader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
 
 	if(!($vs_window_title = trim(MetaTagManager::getWindowTitle()))) {
 		$va_breadcrumb = $this->getVar('nav')->getDestinationAsBreadCrumbTrail();
 		if (is_array($va_breadcrumb) && sizeof($va_breadcrumb)) {
 			$vs_window_title = array_pop($va_breadcrumb);
 		}
 	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />

		<title><?php print $this->appconfig->get("window_title").($vs_window_title ? " : {$vs_window_title}" : ''); ?></title>
		<link rel="stylesheet" href="<?php print $this->request->getThemeUrlPath(); ?>/css/base.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getThemeUrlPath(); ?>/css/sets.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getThemeUrlPath(); ?>/css/jquery-ui-1.8.11.custom.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getBaseUrlPath(); ?>/js/videojs/video-js.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getBaseUrlPath(); ?>/js/jquery/jquery-jplayer/jplayer.blue.monday.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="<?php print $this->request->getBaseUrlPath(); ?>/js/jquery/jquery-autocomplete/jquery.autocomplete.css" type="text/css" media="screen" />

		<!--[if (!IE)|(gte IE 8)]><!-->
		<link href="<?php print $this->request->getBaseUrlPath(); ?>/js/DV/viewer-datauri.css" media="screen" rel="stylesheet" type="text/css" />
		<link href="<?php print $this->request->getBaseUrlPath(); ?>/js/DV/plain-datauri.css" media="screen" rel="stylesheet" type="text/css" />
		<link href="<?php print $this->request->getBaseUrlPath(); ?>/js/DV/plain.css" media="screen" rel="stylesheet" type="text/css" />
		<!--<![endif]-->
		<!--[if lte IE 7]>
		<link href="<?php print $this->request->getBaseUrlPath(); ?>/viewer.css" media="screen" rel="stylesheet" type="text/css" />
		<link href="<?php print $this->request->getBaseUrlPath(); ?>/plain.css" media="screen" rel="stylesheet" type="text/css" />
		<![endif]-->
		<link rel="stylesheet" href="<?php print $this->request->getBaseUrlPath(); ?>/js/jquery/jquery-tileviewer/jquery.tileviewer.css" type="text/css" media="screen" />

<?php
	print JavascriptLoadManager::getLoadHTML($this->request->getBaseUrlPath());
	print MetaTagManager::getHTML();
?>
	</head>
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
				
				jQuery('#caQuickSearchFormText').searchlight('<?php print caNavUrl($this->request, 'find', 'SearchObjects', 'lookup'); ?>', {showIcons: false, searchDelay: 100, minimumCharacters: 3, limitPerCategory: 3});
			});
			
			// initialize CA Utils
			caUI.initUtils({unsavedChangesWarningMessage: '<?php _p('You have made changes in this form that you have not yet saved. If you navigate away from this form you will lose your unsaved changes.'); ?>'});

		</script>
		<!--[if lte IE 6]>
			<style type="text/css">
			#container {
			height: 100%;
			}
			</style>
			<![endif]-->
		<!-- super fish end menus -->
		
	<body>
		<div align="center">