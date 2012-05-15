<?php
/* ----------------------------------------------------------------------
 * app/widgets/count/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
 	$po_request 			= $this->getVar('request');
	$va_instances 			= $this->getVar('instances');
	$va_settings 			= $this->getVar('settings');
	$vs_widget_id 			= $this->getVar('widget_id');
?>

<div class="dashboardWidgetContentContainer" style="font-size:13px; padding-right:10px;">
<?php
	print _t("There are ");
	$va_counts = array();
	$i = 1;
	foreach($this->getVar('counts') as $vs_table => $vn_count) {
		if((sizeof($this->getVar('counts')) > 1) && ($i == sizeof($this->getVar('counts')))){
			 $vs_and = _t(" and ");
		}else{
			$vs_and = "";
		}
		if ($vn_count == 1) {
			$va_counts[] = $vs_and."<b>".$vn_count.'</b> '._t($va_instances[$vs_table]->getProperty('NAME_SINGULAR'));
		} else {
			$va_counts[] = $vs_and."<b>".$vn_count.'</b> '._t($va_instances[$vs_table]->getProperty('NAME_PLURAL'));
		}
		$i++;
	}
	# --- only use a comma to join if there are more than 2 things
	if((sizeof($va_counts) > 2)){
		$vs_join = ", ";
	}else{
		$vs_join = " ";
	}
	print implode($va_counts, $vs_join).".";
?>
	
</div>