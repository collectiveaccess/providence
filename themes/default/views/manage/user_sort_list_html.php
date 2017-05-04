<?php
/* ----------------------------------------------------------------------
 * manage/user_sort_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2017 Whirl-i-Gig
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
 	$va_user_sorts = $this->getVar('user_sorts');
?>
<div class="control-box rounded">
	<div style='float:left;margin-top:2px;'>
		<a href='#' onclick="caTypeChangePanel.showPanel('<?php print caNavUrl($this->request, 'manage', 'UserSort', 'Edit'); ?>'); return false;" class='form-button'><span class='form-button'><?php print caNavIcon(__CA_NAV_ICON_ADD__, 2, array('style' => 'padding-right:5px;')); print _t("Create New Sort"); ?></span></a>
	</div>
	<div style='float:right;'>
		<a href='#' onclick='jQuery("#UserSortsListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'UserSort', 'Delete'); ?>").submit();' class='form-button'><span class='form-button delete' style='padding-top:5px;'><?php print caNavIcon(__CA_NAV_ICON_DELETE__, 2, array('style' => 'padding-right:5px;')); print _t("Delete Selected"); ?></span></a>
	</div>
</div>
<?php
	if(sizeof($va_user_sorts) > 0) {
?>
		<script language="JavaScript" type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('#caItemList').caFormatListTable();
			});
		</script>
		<div class="sectionBox">
			<form id="UserSortsListForm">

			<table id="caItemList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Name'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Record type'); ?>
						</th>
						<th class="{sorter: false} list-header-nosort listtableEdit"><?php print _t('Edit'); ?></th>
						<th class="{sorter: false} list-header-nosort"><input type='checkbox' name='record' value='' id='userSortSelectAllControl' class='userSortControl' onchange="jQuery('.userSortControl').attr('checked', jQuery('#userSortSelectAllControl').attr('checked'));"/></th>
					</tr>
				</thead>
				<tbody>
<?php
			foreach($va_user_sorts as $va_sort) {
?>
				<tr>
					<td>
<?php
						print $va_sort['name']
?>
					</td>
					<td>
<?php
						print Datamodel::load()->getInstance($va_sort['table_num'])->getProperty('NAME_PLURAL');
?>
					</td>
					<td class="listtableEdit">
						<a href="#" onclick="caTypeChangePanel.showPanel('<?php print caNavUrl($this->request, 'manage', 'UserSort', 'Edit', array('sort_id' => $va_sort['sort_id'])); ?>'); return false;"><?php print caNavIcon(__CA_NAV_ICON_EDIT__, 2, array('style' => 'padding-right:5px;')); ?></a>
					</td>
					<td style="width:15px;">
						<input type="checkbox" class="userSortControl" name="sort_id[]" value="<?php print $va_sort["sort_id"]; ?>">
					</td>
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

<script type="text/javascript">
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caTypeChangePanel = caUI.initPanel({
				panelID: "caTypeChangePanel", /* DOM ID of the <div> enclosing the panel */
				panelContentID: "caTypeChangePanelContentArea", /* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",
				exposeBackgroundOpacity: 0.6,
				panelTransitionSpeed: 400,
				closeButtonSelector: ".close",
				center: false,
				centerVertical : false,
				centerHorizontal : true
			});
		}
	});
</script>

<div id="caTypeChangePanel" class="caTypeChangePanel" style='top:170px;'>
	<div id="caTypeChangePanelContentArea"></div>
</div>
