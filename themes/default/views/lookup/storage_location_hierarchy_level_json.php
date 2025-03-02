<?php
/* ----------------------------------------------------------------------
 * lookup/storage_location_hierarchy_level_json.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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

	$va_list = $this->getVar('storage_location_list');
	foreach($va_list as $vs_level => $va_level_content) {
		foreach($va_level_content as $vs_key => $va_info) {
			if (!is_array($va_info)) { continue; }

			if (isset($va_info['is_enabled']) && !$va_info['is_enabled']) {
				$va_level_content[$vs_key]['name'] .= ' ⨂';
			}
		}
		$va_list[$vs_level] = $va_level_content;
	}
	print json_encode($va_list);
