<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/role_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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
	$va_role_list = $this->getVar('role_list');

?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caRoleList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormTag($this->request, 'ListRoles', 'caRoleListForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caRoleList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			caNavHeaderButton($this->request, __CA_NAV_BUTTON_ADD__, _t("New role"), 'administrate/access', 'Roles', 'Edit', array('role_id' => 0))
		); 
?>	
		<table id="caRoleList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
			<thead>
				<tr>
					<th class="list-header-unsorted">
						<?php print _t('Name'); ?>
					</th>
					<th class="list-header-unsorted">
						<?php print _t('Code'); ?>
					</th>
					<th class="list-header-unsorted">
						<?php print _t('Description'); ?>
					</th>
					<th class="{sorter: false} list-header-nosort">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
<?php
	if (sizeof($va_role_list)) {
		foreach($va_role_list as $va_role) {
?>
				<tr>
					<td>
						<?php print $va_role['name']; ?>
					</td>
					<td>
						<?php print $va_role['code']; ?>
					</td>
					<td>
						<?php print $va_role['description']; ?>
					</td>
					<td>
						<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'administrate/access', 'Roles', 'Edit', array('role_id' => $va_role['role_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
						
						<?php print caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/access', 'Roles', 'Delete', array('role_id' => $va_role['role_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					</td>
				</tr>
<?php
		}
	} else {
?>
				<tr>
					<td colspan='4'>
						<div align="center">
							<?php print _t('No roles have been configured'); ?>
						</div>
					</td>
				</tr>
<?php			
	}
?>
			</tbody>
		</table>
	</form>
</div>