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
 
 	$po_request			= $this->getVar('request');
	$va_comments			= $this->getVar('comment_list');
	$vs_widget_id			= $this->getVar('widget_id');
	$vb_unmoderated			= 0;
?>

<div class="dashboardWidgetContentContainer">
<?php
if(sizeof($va_comments) > 0){
	print "<div class='dashboardWidgetHeading'>"._t("Displaying the last %1 %2 comments", $this->getVar("limit"), $this->getVar("comment_type"))."</div>";
	?>
		<form id="commentListForm">
		<div class="dashboardWidgetScrollMedium" style="padding-left:10px;"><ul>
	<?php
		foreach($va_comments as $va_comment_info) {
			if(!$va_comment_info["moderated_on"]){
				$vb_unmoderated = 1;
			}
			print "<li>".(!$va_comment_info["moderated_on"] ? "<input type=\"checkbox\" name=\"comment_id[]\" value=\"".$va_comment_info["comment_id"]."\"> " : "");
			print "<span style='font-size:12px; font-weight:bold;'>".$va_comment_info["fname"]." ".$va_comment_info["lname"]." "._t("commented on")." <a href='".caEditorUrl($po_request, $va_comment_info['table_num'], $va_comment_info['row_id'])."'>".$va_comment_info["commented_on"]."</a></span><br/>\n";
			print ($va_comment_info["created_on"] ? _t("Created on").": ".$va_comment_info["created_on"] : "");
			print ($va_comment_info["moderated_on"] ? ",&nbsp;&nbsp;&nbsp;&nbsp;"._t("Approved on").": ".date("n/d/y", $va_comment_info["moderated_on"]) : "")."<br/>";
			print "<a href='#' id='more".$va_comment_info["comment_id"]."' onclick='jQuery(\"#more".$va_comment_info["comment_id"]."\").hide(); jQuery(\"#comment".$va_comment_info["comment_id"]."\").slideDown(250); return false;'>More Info &rsaquo;</a>";
			print "<div style='display:none;' id='comment".$va_comment_info["comment_id"]."'><ul>";
			// Print out comment info
			if(isset($va_comment_info["rating"])){
				print "<li><b>"._t("Rating")."</b>: ".$va_comment_info["rating"]."</li>";
			}
			if(strlen($va_comment_info["comment"])>0){
				print "<li><b>"._t("Comment")."</b>: ".$va_comment_info["comment"]."</li>";
			}
			
			print "</ul>";
			print "<a href='#' id='hide".$va_comment_info["comment_id"]."' style='padding-left:10px;' onclick='jQuery(\"#comment".$va_comment_info["comment_id"]."\").slideUp(250); jQuery(\"#more".$va_comment_info["comment_id"]."\").show(); return false;'>Hide &rsaquo;</a>";		
			print "</div></li>";
		}
	?>
		</ul></div>
	<?php
	if($vb_unmoderated):
	?>
			<div style="padding-top:10px; text-align:center; padding-right:20px;">
				<a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($po_request, 'manage', 'Comments', 'Approve'); ?>").submit();' class='form-button'><span class='form-button'>Approve</span></a>
				<a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($po_request, 'manage', 'Comments', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'>Delete</span></a>
			</div>
			<input type="hidden" name="mode" value="dashboard">
	<?php
	endif;
	?>
		</form>
	<?php
}else{
	print "<div class='dashboardWidgetHeading'>"._t("There are no %1 comments", $this->getVar("comment_type"))."</div>";
}
?>
</div>