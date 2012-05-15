<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/group_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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

	$t_group = $this->getVar('t_group');
	$vn_group_id = $this->getVar('group_id');
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'RroupsForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/access', 'groups', 'ListGroups', array('group_id' => 0)), 
		'', 
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/access', 'groups', 'Delete', array('group_id' => $vn_group_id))
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'RroupsForm');

		foreach($t_group->getFormFields() as $vs_f => $va_group_info) {
			print $t_group->htmlFormElement($vs_f, null, array('field_errors' => $this->request->getActionErrors('field_'.$vs_f)));
		}
		
?>
		<table style="width: 700px;">
			<tr valign="top">
				<td>
<?php
		// roles
		print $t_group->roleListAsHTMLFormElement(array('name' => 'roles', 'size' => 6));
?>
				</td>
				<td>
					<div class='formLabel'><?php print _t('Group members'); ?></div>
					<div>
<?php
		// users
		if (is_array($va_users = $t_group->getGroupUsers()) && (sizeof($va_users))) {
			foreach($va_users as $vn_user_id => $va_user_info) {
				print $va_user_info['fname'].' '.$va_user_info['lname']."<br/>\n";
			}
		} else {
			print _t('No users are in this group');
		}
?>
					</div>
				</td>
			</tr>
		</table>	
	</form>
<?php
	print $vs_control_box;
?>
</div>