<?php
/* ----------------------------------------------------------------------
 * app/widgets/recentChanges/views/main_html.php : 
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
	$vs_widget_id 			= $this->getVar('widget_id');
	$va_watched_items		= $this->getVar('watched_items');
?>
<div class="dashboardWidgetContentContainer">
<?php
		if(sizeof($va_watched_items) > 0){
			print '<div class="dashboardWidgetScrollMedium"><ul>';
?>
			<form id="watchedItemsForm"><input type="hidden" name="mode" value="list">
<?php
			foreach($va_watched_items as $va_item){
				$vs_idno = "";
				if($va_item["idno"]){
					$vs_idno = "[".$va_item["idno"]."] "; 
				}
				print "<li><input type='checkbox' name='watch_id[]' value='".$va_item["watch_id"]."'> ";
				if($va_item["primary_key"]){					
					print "<span style='font-size:12px; font-weight:bold;'>".caEditorLink($po_request, $vs_idno.$va_item["displayName"], '', $va_item["table_name"], $va_item["row_id"])."</span>";
					print "<br/>&nbsp;&nbsp;&nbsp;<a href='#' id='moreWatchItem".$va_item["watch_id"]."' onclick='jQuery(\"#moreWatchItem".$va_item["watch_id"]."\").hide(); jQuery(\"#watchItem".$va_item["watch_id"]."\").slideDown(250); return false;'>"._t("Recent Changes")." &rsaquo;</a>";
					print "<div style='display:none; margin-right:5px;' id='watchItem".$va_item["watch_id"]."'>";
					print $va_item["change_log"];
					print "<a href='#' id='hideWatchItem".$va_item["watch_id"]."' style='padding-left:10px;' onclick='jQuery(\"#watchItem".$va_item["watch_id"]."\").slideUp(250); jQuery(\"#moreWatchItem".$va_item["watch_id"]."\").show(); return false;'>"._t('Hide')." &rsaquo;</a>";		
					print "</div>";
				}else{
					print _t("Item was deleted - row_id: %1; table number: %2", $va_item["row_id"], $va_item["table_num"]);
				}
				print "</li>";
			}
			print '</ul></div>';
?>
			<div style="padding-top:10px; text-align:center; padding-right:20px;">
				<a href='#' onclick='jQuery("#watchedItemsForm").attr("action", "<?php print caNavUrl($po_request, 'manage', 'WatchedItems', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'><?php print _t('Remove from watch list'); ?></span></a>
			</div>
			<input type="hidden" name="mode" value="dashboard"></form>
<?php
		}else{
			print _t("You have no items on your watch list.");
		}
?>
</div>
