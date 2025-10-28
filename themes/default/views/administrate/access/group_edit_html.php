<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/group_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
$group_id = $this->getVar('group_id');
?>
<div class="sectionBox">
<?php
	print $control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'GroupsForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'administrate/access', 'groups', 'ListGroups', array('group_id' => 0)), 
		'', 
		($group_id > 0) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'administrate/access', 'groups', 'Delete', array('group_id' => $group_id)) : ''
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'GroupsForm');
?>
	
		<h2><?= _t('Group information'); ?></h2>
<?php
		foreach($t_group->getFormFields() as $f => $group_info) {
			print $t_group->htmlFormElement($f, null, ['field_errors' => $this->request->getActionErrors('field_'.$f)]);
		}
?>

		<div class="roles">
			<h2><?= _t('Roles'); ?></h2>
			
			<div class="roleList">
				<?= $t_group->roleListAsHTMLFormElement(['name' => 'roles', 'size' => 6, 'renderAs' => DT_CHECKBOXES, 'includeLabel' => false]); ?>
			</div>
		</div>
		<div class="users">
			<h2><?= _t('Group members'); ?></h2>
			<div class="userList">
<?php
			// users
			if (is_array($users = $t_group->getGroupUsers()) && (sizeof($users))) {
				$users = array_map(function($user_info) {
					return '<div>'.caNavLink($this->request, $user_info['fname'].' '.$user_info['lname'], '', 'administrate/access', 'Users', 'Edit', ['user_id' => $user_info['user_id']]).'</div>';
				}, $users);
				print join(" ", $users);
			} else {
				print _t('No users are in this group');
			}
?>
			</div>
		</div>
	</form>
<?php
	print $control_box;
?>
</div>