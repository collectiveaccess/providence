<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/access/user_reset_mfa_html.php : confirm turning off two-factor authentication for a user
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
$user_id = (int)$this->getVar('user_id');
?>
<div class="sectionBox">
		<div class='delete-control-box'>
			<?= caFormTag($this->request, 'ResetMFA', 'resetMFAForm'); ?>
<?php
			print caFormControlBox(
				"<div class='delete_warning_box'>"._t('Disable two-factor authentication?')."</div>".
				 _t("Two-factor authentication will be disabled for <strong>%1</strong> (%2).<br>Their authenticator app setup and recovery codes will be removed. They will continue to be able to log in using their password.", $t_user->getName(), $t_user->get('user_name')),
				'',
				caFormSubmitButton($this->request, __CA_NAV_ICON_DISABLE__, _t("Disable"), 'resetMFAForm').
				caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'administrate/access', 'Users', 'Edit', ['user_id' => $user_id])
			)."\n";
?>
			<input type="hidden" name="user_id" value="<?= $user_id; ?>"/>
			<input type="hidden" name="confirm" value="1"/>
		</form>
	</div>
</div>
