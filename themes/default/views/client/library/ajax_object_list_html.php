<?php
/* ----------------------------------------------------------------------
 * lookup/ajax_object_list_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 	$va_object_id_list = $this->getVar('object_id_list');
 	
	foreach($this->getVar('object_list') as $vn_item_id => $va_item) {
		$vn_due_date = (int)$va_object_id_list[$vn_item_id];	// unixtime stamp when item is due for return if already loaned
		$vs_localized_due_date = caGetLocalizedDate($vn_due_date, array('format' => 'delimited', 'timeOmit' => true));
		print str_replace("|", "-", $va_item['_display'])."|".$vn_item_id."|".$va_item['type_id']."|{$vn_due_date}|{$vs_localized_due_date}\n";
	}
?>