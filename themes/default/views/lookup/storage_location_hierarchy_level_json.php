<?php
/* ----------------------------------------------------------------------
 * lookup/storage_location_hierarchy_level_json.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2026 Whirl-i-Gig
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
$list = $this->getVar('storage_location_list');
foreach($list as $level => $level_content) {
	foreach($level_content as $key => $info) {
		if (!is_array($info)) { continue; }

		if (isset($info['is_enabled']) && !$info['is_enabled']) {
			$level_content[$key]['name'] .= ' ⨂';
		}
	}
	$list[$level] = $level_content;
}
print json_encode($list);
