<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/ObjectLibraryServicesController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2026 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/BaseLookupController.php");
require_once(__CA_APP_DIR__.'/helpers/libraryServicesHelpers.php');

class ObjectLibraryServicesController extends BaseLookupController {
	# -------------------------------------------------------
	protected $opb_uses_hierarchy_browser = true;
	protected $ops_table_name = 'ca_objects';		// name of "subject" table (what we're editing)
	protected $ops_name_singular = 'object';
	protected $ops_search_class = 'ObjectSearch';
	# -------------------------------------------------------
	public function Get($additional_query_params=null, $options=null) {
		$o_library_conf = caGetLibraryServicesConfiguration();
		$restrict_to_circulation_statuses = $o_library_conf->get('restrict_to_circulation_statuses');
		if($restrict_to_circulation_statuses && is_array($restrict_to_circulation_statuses) && (sizeof($o_library_conf->get('restrict_to_circulation_statuses')) > 0)) {
			$status_ids = [];

			foreach($restrict_to_circulation_statuses as $vs_status) {
				$status_ids[] = caGetListItemID('object_circulation_statuses', $vs_status);
			}

			$options['filters'][] = array("ca_objects.circulation_status_id", "IN", join(',', $status_ids));
		}
		
		$options['template'] = $o_library_conf->get('checkout_item_lookup_settings') ?? null;

		return parent::Get($additional_query_params, $options);
	}
	# -------------------------------------------------------
}
