<?php
/* ----------------------------------------------------------------------
 * app/widgets/recentTags/views/main_html.php : 
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
 
 	$po_request			= $this->getVar('request');
	$va_tags			= $this->getVar('tags_list');
	$vs_widget_id			= $this->getVar('widget_id');
	$vb_unmoderated = 0;
?>

<div class="dashboardWidgetContentContainer">
<?php
if(sizeof($va_tags) > 0){
	print "<div class='dashboardWidgetHeading'>"._t("Displaying the last %1 %2 tags", $this->getVar("limit"), $this->getVar("tag_type"))."</div>";
?>
		<form id="tagListForm"><input type="hidden" name="mode" value="list">
		<div class="dashboardWidgetScrollMedium" style="padding-left:10px;"><ul>
<?php
		foreach($va_tags as $va_tag_info) {
			print "<li>".(!$va_tag_info["moderated_on"] ? "<input type=\"checkbox\" name=\"tag_relation_id[]\" value=\"".$va_tag_info['relation_id']."\"> " : "");
			print "<span style='font-size:12px; font-weight:bold;'>"._t("%1 tagged %2 with \"%3\"", $va_tag_info["fname"]." ".$va_tag_info["lname"], "<a href='".caEditorUrl($po_request, $va_tag_info['table_num'], $va_tag_info['row_id'])."'>".$va_tag_info["item_tagged"]."</a>", $va_tag_info["tag"])."</span><br/>\n";
			print ($va_tag_info["created_on"] ? _t("Created on").": ".$va_tag_info["created_on"] : "");
			print ($va_tag_info["moderated_on"] ? ",&nbsp;&nbsp;&nbsp;&nbsp;"._t("Approved on").": ".date("n/d/y", $va_tag_info["moderated_on"]) : "")."<br/>";
			print "</li>";
			if(!$va_tag_info["moderated_on"]){
				$vb_unmoderated = 1;
			}
		}
?>
			</ul></div>
<?php
	if($vb_unmoderated){
?>
			<div style="padding-top:10px; text-align:center; padding-right:20px;">
				<a href='#' onclick='jQuery("#tagListForm").attr("action", "<?php print caNavUrl($po_request, 'manage', 'Tags', 'Approve'); ?>").submit();' class='form-button'><span class='form-button'>Approve</span></a>
				<a href='#' onclick='jQuery("#tagListForm").attr("action", "<?php print caNavUrl($po_request, 'manage', 'Tags', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'>Delete</span></a>
			</div>
			<input type="hidden" name="mode" value="dashboard">
<?php
	}
?>
		</form>
<?php
}else{
	print "<div class='dashboardWidgetHeading'>"._t("There are no %1 tags.", $this->getVar("tag_type"))."</div>";
}
?>
</div>