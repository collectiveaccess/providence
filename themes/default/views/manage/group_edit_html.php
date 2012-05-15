<?php
/* ----------------------------------------------------------------------
 * app/views/manage/group_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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

	$t_group 				= $this->getVar('t_group');
	$vn_group_id 		= $this->getVar('group_id');
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'GroupsForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'manage', 'groups', 'ListGroups', array('group_id' => 0)), 
		'', 
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'manage', 'groups', 'Delete', array('group_id' => $vn_group_id))
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'GroupsForm');

		foreach($t_group->getFormFields() as $vs_f => $va_group_info) {
			if ($vs_f == 'code') { continue; }
			print $t_group->htmlFormElement($vs_f, null, array('field_errors' => $this->request->getActionErrors('field_'.$vs_f)));
		}
		
		// users
		if (!is_array($va_group_users = $t_group->getGroupUsers())) { $va_group_users = array(); }
		print $this->request->user->userListAsHTMLFormElement(array('userclass' => array(0, 1), 'sort' => 'lname', 'sort_direction' => 'asc', 'name' => 'group_users', 'label' => 'Members', 'selected' => array_keys($va_group_users)));
?>	
	</form>
<?php
	print $vs_control_box;
?>
</div>