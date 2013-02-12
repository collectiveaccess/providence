<?php
/** ---------------------------------------------------------------------
 * app/interfaces/IBundleProvider.php : interface for entities that can provide editor UI bundles
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
 * @package CollectiveAccess
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 interface IBundleProvider {
 	// returns list of available bundles
 	public function getBundleList();
 	
 	// returns associative array with descriptive information about the bundle
 	public function getBundleInfo($ps_bundle_name);
 	
 	// returns HTML implementing the bundle in an HTML form
 	public function getBundleFormHTML($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options);
 	
 	// returns a list of HTML fragments implementing all bundles in an HTML form for the specified screen
 	// $pm_screen can be a screen tag (eg. "Screen5") or a screen_id (eg. 5)
 	public function getBundleFormHTMLForScreen($pm_screen, $pa_options);
 	
 	// saves all bundles on the specified screen in the database by extracting 
 	// required data from the supplied request
 	// $pm_screen can be a screen tag (eg. "Screen5") or a screen_id (eg. 5)
 	public function saveBundlesForScreen($pm_screen, $po_request, &$pa_options);
 }