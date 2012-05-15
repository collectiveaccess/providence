<?php
/* ----------------------------------------------------------------------
 * manage/watched_items_list_html.php :
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
 	$va_watched_items = $this->getVar('watched_items');
	if(sizeof($va_watched_items) > 0){
?>
		<script language="JavaScript" type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function(){
				jQuery('#caWatchedItemsList').caFormatListTable();
			});
		/* ]]> */
		</script>
		<div class="sectionBox">
			<form id="WatchedItemsListForm">
			
			<table id="caWatchedItemsList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
				<div style="text-align:right;">
					<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#WatchedItemsListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'WatchedItems', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'>Delete</span></a>
				</div>
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Item'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Item Type'); ?>
						</th>
						<th class="{sorter: false} list-header-nosort"><input type='checkbox' name='record' value='' id='watchItemSelectAllControl' class='watchItemControl' onchange="jQuery('.watchItemControl').attr('checked', jQuery('#watchItemSelectAllControl').attr('checked'));"/></th>
					</tr>
				</thead>
				<tbody>
<?php
			foreach($va_watched_items as $va_item){
				$vs_idno = "";
				if($va_item["idno"]){
					$vs_idno = "[".$va_item["idno"]."] "; 
				}
?>
				<tr>
					<td>
<?php
						if($va_item["primary_key"]){
							print caEditorLink($this->request, $vs_idno.$va_item["displayName"], '', $va_item["table_name"], $va_item["row_id"])." "."<a href='#' style='font-weight:bold; margin-left:5px; text-decoration:none;' id='changeLogLink".$va_item["watch_id"]."' onclick='jQuery(\"#changeLogLink".$va_item["watch_id"]."\").hide(); jQuery(\"#changeLogHide".$va_item["watch_id"]."\").show(); jQuery(\"#changeLog".$va_item["watch_id"]."\").slideDown(250); return false;'>"._t("Recent Changes")." &rsaquo;</a><a href='#' style='display:none; font-weight:bold; margin-left:5px; text-decoration:none;' id='changeLogHide".$va_item["watch_id"]."' onclick='jQuery(\"#changeLogHide".$va_item["watch_id"]."\").hide(); jQuery(\"#changeLogLink".$va_item["watch_id"]."\").show(); jQuery(\"#changeLog".$va_item["watch_id"]."\").slideUp(250); return false;'>"._t("Hide Recent Changes")." &rsaquo;</a>";
						}else{
							print "item was deleted.  Row id: ".$va_item["row_id"]." table_num: ".$va_item["table_num"];
						}
?>
					</td>
					<td style="white-space:nowrap; width:75px;">
<?php
						print $va_item["item_type"];
?>
					</td>
					<td style="width:15px;">
						<input type="checkbox" class="watchItemControl" name="watch_id[]" value="<?php print $va_item["watch_id"]; ?>">
					</td>
				</tr>			
				<tr style="display:none;" id="changeLog<?php print $va_item["watch_id"]?>">
				<td>
<?php				
						print $va_item["change_log"];
?>
				</td>
				<td></td>
				<td></td>
				</tr>
<?php

			}
?>
				</tbody>
			</table></form>
		</div><!-- end sectionBox -->
<?php
	}
?>