<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/user_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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

	$t_user = $this->getVar('t_user');
	$vn_user_id = $this->getVar('user_id');
	
	$va_roles = $this->getVar('roles');
	$va_groups = $this->getVar('groups');
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'UsersForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/access', 'Users', 'ListUsers', array('user_id' => 0)), 
		'', 
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/access', 'Users', 'Delete', array('user_id' => $vn_user_id))
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'UsersForm');

		// ca_users fields
		foreach($t_user->getFormFields() as $vs_f => $va_user_info) {
			print $t_user->htmlFormElement($vs_f, null, array('field_errors' => $this->request->getActionErrors('field_'.$vs_f)));

			if ($vs_f == 'password') {
				// display password confirmation
				print $t_user->htmlFormElement($vs_f, str_replace('^LABEL', _t("Confirm password"), $this->appconfig->get('form_element_display_format')), array('name' => 'password_confirm', 'LABEL' => 'Confirm password'));
			}
		}
?>
		<table style="width: 700px;">
			<tr valign="top">
				<td>
<?php
		// roles
		print $t_user->roleListAsHTMLFormElement(array('name' => 'roles', 'size' => 6));
?>
				</td>
				<td>
<?php
		// groups
		print $t_user->groupListAsHTMLFormElement(array('name' => 'groups', 'size' => 6));
?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
<?php
		// Output user profile settings if defined
		$va_user_profile_settings = $this->getVar('profile_settings');
		if (is_array($va_user_profile_settings) && sizeof($va_user_profile_settings)) {
			foreach($va_user_profile_settings as $vs_field => $va_info) {
				if($va_errors[$vs_field]){
					print "<div class='formErrors' style='text-align: left;'>".$va_errors[$vs_field]."</div>";
				}
				print $va_info['element']."\n";
			}
		}
?>				
				</td>
			</tr>
		</table>
	</form>
<?php
	print $vs_control_box;
?>
</div>