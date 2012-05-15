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
 
 	$po_request				= $this->getVar('request');
	$va_item_list			= $this->getVar('item_list');
	$vs_table_num			= $this->getVar('table_num');
	$vs_table_display		= $this->getVar('table_display');
	$vs_widget_id			= $this->getVar('widget_id');
	$vn_height_px			= $this->getVar('height_px');
	$idno_display			= $this->getVar('idno_display');
?>

<div class="dashboardWidgetContentContainer">
	<div class="dashboardWidgetHeading"><?php print _t("Showing the last %1 <span style='font-weight:bold; text-transform:lowercase;'>%2</span> created",sizeof($va_item_list),$vs_table_display); ?></div>
	<div class="dashboardWidgetScrollMedium"><ul>
<?php
	foreach($va_item_list as $vn_id => $va_record) {
		print "<li>".
			"<a href=\"".caEditorUrl($po_request, $vs_table_num, $vn_id)."\">".
			(strlen($va_record["display"])>0 ? $va_record["display"] : _t("[BLANK]")).
			((($idno_display) && (strlen($va_record["idno"])>0)) ? " [".$va_record["idno"]."]" : "").
			((($idno_display) && (strlen($va_record["idno_stub"])>0)) ? " [".$va_record["idno_stub"]."]" : "").			
			"</a> - ".($va_record["datetime"] ? date("n/d/y, g:iA T", $va_record["datetime"]) : "")."</li>\n";
		
	}
?>
	</ul></div>
</div>