<?php
/* ----------------------------------------------------------------------
 * app/widgets/count/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2023 Whirl-i-Gig
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
$hide_zero_counts 		= $this->getVar('hide_zero_counts');
?>
<div class="dashboardWidgetContentContainer" style="font-size:13px; padding-right:10px;">
<?php
	$va_counts = array();
	$i = 1;
	foreach($this->getVar('counts') as $vs_table => $count) {
		if(is_array($count)) { 
			foreach($count as $type_id => $info) {
				if($hide_zero_counts && ($info['count'] == 0)) { continue; }
				$typename = caGetListItemByIDForDisplay($type_id, ['return' => ($info['count'] == 1) ? 'singular' : 'plural']);
				$va_counts[] = "<b><a>".caSearchLink($po_request,$info['count'], '', $vs_table, "{$vs_table}.type_id:".caGetListItemIdno($type_id), ['type_id' => $type_id]).'</a></b>&nbsp;'.$typename;
			}
		} else {
			if($hide_zero_counts && ($count == 0)) { continue; }
			$link = caSearchLink($po_request, $count, '', $vs_table, '*', ['clearType' => 1]);
			if ($count == 1) {
				$va_counts[] = "<b>".$link.'</b>&nbsp;'._t($va_instances[$vs_table]->getProperty('NAME_SINGULAR'));
			} else {
				$va_counts[] = "<b>".$link.'</b>&nbsp;'._t($va_instances[$vs_table]->getProperty('NAME_PLURAL'));
			}
			$i++;
		}
	}
	
	# --- only make a list if there is more than 1 thing
	if((sizeof($va_counts) > 1)){
		print "<ul class='disc allowBold'>";
		foreach($va_counts as $vs_count){
			print "<li>".$vs_count."</li>";
		}
		print "</ul>";
		$vs_join = ", ";
	}else{
		print $va_counts[0];
	}
?>
</div>
