<?php
/* ----------------------------------------------------------------------
 * lookup/ajax_taxonomy_list_list_html.php :
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
	$va_items = $this->getVar('taxonomy_list');
	if(is_array($va_items["error"])){
		print str_replace("|", "-", $va_items["error"]['msg'])."\n";
	}
	if(is_array($va_items["error_ubio"])){
		print str_replace("|", "-", $va_items["error_ubio"]['msg'])."\n";
	}
	unset($va_items["error_ubio"]); unset($va_items["error_itis"]);
	foreach($va_items as $va_item) {
		print str_replace("|", "-", $va_item['sci_name'].(strlen(trim($va_item['common_name']))>0 ? ' / '.trim($va_item['common_name']) : '').($va_item['idno'] ? ' ['.$va_item['idno'].']' : ''))."|".$va_item['idno']."\n";
	}
?>