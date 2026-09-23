<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/pageHeader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2026 Whirl-i-Gig
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
<!DOCTYPE html>
<html lang="en" class="h-100">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
		<?= MetaTagManager::getHTML(); ?>
		<?= AssetLoadManager::getLoadHTML($this->request); ?>
		
<?php
		// @TODO: use relative paths 
?>
		<script type="text/javascript" src="/themes/default/dist/assets/main.js"></script>
		<link rel='stylesheet' href='/themes/default/dist/assets/main.css' type='text/css' media='all'></link>
		
		<title><?= (MetaTagManager::getWindowTitle()) ?: $this->request->config->get("app_display_name"); ?></title>
</head>
<body id="pawtucketApp" class="d-flex flex-column h-100">
	<a href="#page-content" id="skip" class="visually-hidden"><?= _t('Skip to main content'); ?></a>
	<nav class="navbar navbar-expand-lg shadow-sm py-1">
		<div class="container-xl">
			<div class="navbar-brand black shippori-mincho-medium"><?= caNavlink($this->request, caGetMenuBarLogo(), "navbar-brand  img-fluid", "", "", ""); ?></div>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			  <span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
<?php
			if($this->request->isLoggedIn() && $this->request->user->hasRole("admin")){
?>
				<ul class="navbar-nav ms-auto mb-2 mb-lg-0 me-4">	
<?php
					// Menus here
?>
				</ul>
				<form action="<?= caNavUrl($this->request, '', 'Search', 'Objects'); ?>" role="search">
					<div class="input-group">
						<label for="nav-search-input" class="form-label visually-hidden">Search</label>
						<input type="text" name="search" class="form-control rounded-0 border-black" id="nav-search-input" placeholder="Search">
						<button type="submit" class="btn rounded-0" id="nav-search-btn" aria-label="Submit Search"><i class="bi bi-search"></i></button>
					</div>
				</form>
<?php
			}
?>
			</div>
		</div>
	</nav>	

	<main <?= caGetPageCSSClasses(); ?>><a name="page-content"></a>
	<div class='container-xl pt-4'>
