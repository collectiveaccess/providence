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
	$va_users			= $this->getVar('users_list');
	$vs_widget_id			= $this->getVar('widget_id');
	$vb_unmoderated = 0;
	$va_profile_prefs	= $this->getVar('profile_prefs');
?>

<div class="dashboardWidgetContentContainer">
<?php
if(sizeof($va_users) > 0){
	print "<div class='dashboardWidgetHeading'>"._t("Displaying the last %1 %2 public registrations", $this->getVar("limit"), $this->getVar("registration_type"))."</div>";
?>
		<form id="registrationListForm">
		<div class="dashboardWidgetScrollMedium" style="padding-left:10px;"><ul>
<?php
		foreach($va_users as $va_user_info) {
			$o_tep = new TimeExpressionParser();
			$o_tep->setUnixTimestamps($va_user_info["registered_on"], $va_user_info["registered_on"]);
			$vs_registered_on = $o_tep->getText();
			print "<li>".(!$va_user_info["active"] ? "<input type=\"checkbox\" name=\"user_id[]\" value=\"".$va_user_info['user_id']."\"> " : "");
			print "<span style='font-size:12px; font-weight:bold;'>".$va_user_info["fname"]." ".$va_user_info["lname"].", [".$va_user_info["email"]."]</span><br/>\n";
			print ($vs_registered_on ? _t("Registered on").": ".$vs_registered_on : "");
			print ($va_user_info["active"]) ? ",&nbsp;&nbsp;&nbsp;&nbsp;<b>"._t("Approved")."</b>" : "";
			print "<br/>";
			if(is_array($va_user_info["user_preferences"]) && sizeof($va_user_info["user_preferences"])){
				print "<b>"._t("Preferences").":</b> ".join("; ", $va_user_info["user_preferences"]);
			}
			print "</li>";
			if(!$va_user_info["active"]){
				$vb_unmoderated = 1;
			}
		}
?>
			</ul></div>
<?php
	if($vb_unmoderated){
?>
			<div style="padding-top:10px; text-align:center; padding-right:20px;">
				<a href='#' onclick='jQuery("#registrationListForm").attr("action", "<?php print caNavUrl($po_request, 'administrate/access', 'Users', 'Approve'); ?>").submit();' class='form-button'><span class='form-button'>Approve</span></a>
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