<?php
/* ----------------------------------------------------------------------
 * lookup/list_hierarchy_level_json.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 
 	$va_list = $this->getVar('list_item_list');
 	foreach($va_list as $vs_key => $va_info) {
 		if (!is_array($va_info)) { continue; }
 		if ($va_info['list_code']) {
 			$va_list[$vs_key]['name'] = $va_list[$vs_key]['name']." (".$va_info['list_code'].")";
 		}
 		if ($va_info['idno']) {
 			$va_list[$vs_key]['name'] = $va_list[$vs_key]['name']." (".$va_info['idno'].")";
 		}
 		
 		if (!$this->getVar('dontShowSymbols')) {
			if ($va_info['use_as_vocabulary']) {
				$va_list[$vs_key]['name'] .= ' ⧩';
			}
			if ($va_info['is_system_list']) {
				$va_list[$vs_key]['name'] .= ' ⟗';
			}
			if ($va_info['is_default']) {
				$va_list[$vs_key]['name'] .= ' ◉';
			}
			if (isset($va_info['is_enabled']) && !$va_info['is_enabled']) {
				$va_list[$vs_key]['name'] .= ' ⨂';
			}
		}
 	}
	print json_encode($va_list);
?>
