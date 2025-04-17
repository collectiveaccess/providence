<?php
/** ---------------------------------------------------------------------
 * app/helpers/pawtucketHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage helpers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
# ------------------------------------------------------------------------------------------------
 /**
  *
  */
function caGetPawtucketInstalltionList(?array $options=null) : array {
	$config = Configuration::load();
	return $config->get('pawtucket_installations') ?? [];
}
# ------------------------------------------------------------------------------------------------
 /**
  *
  */
function caGetPawtucketLightboxDownloadVersions(string $table, ?array $options=null) : ?array {
	if(defined('__CA_APP_TYPE__') && (__CA_APP_TYPE__ === 'PROVIDENCE')) {
		$config = Configuration::load();
		$lightbox_options = $config->get('pawtucket_lightbox_options') ?? [];
	} else {
		$config = Configuration::load(__CA_APP_DIR__.'/conf/lightbox.conf');
		$lightbox_options = $config->get('lightbox_options');
	}
	
	if(!isset($lightbox_options[$table])) { return null; }
	if(!isset($lightbox_options[$table]['downloads']) || !is_array($lightbox_options[$table]['downloads'])) { return null; }
	
	return $lightbox_options[$table]['downloads'];
}
# ------------------------------------------------------------------------------------------------
