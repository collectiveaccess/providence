<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/MetadataImportHelpers.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
namespace GraphQLServices\Helpers\Schema;

/**
 * Return primary table names
 */
function primaryTables() : array {
	return [
		'ca_objects', 'ca_collections', 'ca_entities', 
		'ca_occurrences', 'ca_places', 'ca_list_items', 
		'ca_storage_locations', 'ca_loans', 'ca_object_lots', 
		'ca_movements', 'ca_object_representations'
	];
}
