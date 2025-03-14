<?php
/* ----------------------------------------------------------------------
 * app/views/manage/group_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2019 Whirl-i-Gig
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
	$va_group_list = $this->getVar('group_list');

?>
<script language="JavaScript" type="text/javascript">
	$(document).ready(function(){
		$('#caItemList').caFormatListTable();
	});
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caItemList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			caNavHeaderButton($this->request, __CA_NAV_ICON_ADD__, _t("New team"), 'manage', 'groups', 'Edit', array('group_id' => 0))
		); 
	?>
	
	<h1><?= _t('Your project teams'); ?></h1>
	
	<table id="caItemList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Team'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Join code'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Description'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('For public use?'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Members'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort listtableEdit">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_group_list)) {
		foreach($va_group_list as $va_group) {
?>
			<tr>
				<td>
					<?= $va_group['name']; ?>
				</td>
				<td>
					<?= $va_group['code']; ?>
				</td>
				<td>
					<?= $va_group['description']; ?>
				</td>
				<td>
					<?= ((bool)$va_group['for_public_use'] ? _t('Yes') : _t('No')); ?>
				</td>
				<td>
					<?= $va_group['member_list']; ?>
				</td>
				<td class="listtableEdit">
					<?= caNavButton($this->request, __CA_NAV_ICON_EDIT__, _t("Edit"), '', 'manage', 'groups', 'Edit', array('group_id' => $va_group['group_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					
					<?= caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'manage', 'groups', 'Delete', array('group_id' => $va_group['group_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
			<tr>
				<td colspan="6">
					<div align="center">
						<?= _t('You have not defined any teams'); ?>
					</div>
				</td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</div>
