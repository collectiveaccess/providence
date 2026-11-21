<?php
/* ----------------------------------------------------------------------
 * app/views/system/preferences_mfa_html.php : two-factor authentication settings for current user
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
$mode = $this->getVar('mode');
$status = $this->getVar('status');
$available = $this->getVar('available');
?>
<div class="sectionBox">
<?php
	if(!$available) {
?>
		<h1><?= _t("Preferences").": "._t("Two-factor authentication"); ?></h1>
		<p class="mfaStatus"><?= _t("Two-factor authentication is not available on this system. Please contact your administrator."); ?></p>
<?php
	} elseif($this->getVar('uses_external_login') && !$status['enabled']) {
?>	
		<h1><?= _t("Preferences").": "._t("Two-factor authentication"); ?></h1>
		<p><?= _t("Your login is handled by your organization's single sign-on (SSO) system. If your organizational login does not provide two-factor authenication contact your SSO administrator."); ?></p>
<?php
	} elseif($mode === 'setup') {
		print $control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Turn on two-factor authentication"), 'mfaConfirmForm').' '.
			caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'system', 'Preferences', 'EditMFAPrefs', []), 
			'', 
			''
		);
?>
		<h1><?= _t("Preferences").": "._t("Two-factor authentication"); ?></h1>
		<p><?= _t("<strong>Step 1:</strong> Open an authenticator app on your phone (such as Google Authenticator, Duo or Microsoft Authenticator) and scan this code."); ?></p>
<?php
		if ($qr = $this->getVar('qr_code')) {
?>
		<div class="mfaQRCode"><?= $qr; ?></div>
		<p><?= _t("Can't scan it? Enter this key in the app instead"); ?><br/><span class="mfaSecret"><?= htmlspecialchars($this->getVar('secret')); ?></span></p>
<?php
		} else {
?>
		<p><?= _t("Enter this key in the app"); ?><br/><span class="mfaSecret"><?= htmlspecialchars($this->getVar('secret')); ?></span></p>
<?php
		}
?>
		<p><?= _t("<strong>Step 2:</strong> Enter the 6-digit code the app shows."); ?></p>
		<?= caFormTag($this->request, 'MFAConfirmSetup', 'mfaConfirmForm'); ?>
			<div class="mfaField"><input type="text" name="code" autocomplete="one-time-code" inputmode="numeric" autofocus /></div>
		</form>
<?php
		print $control_box;
	} elseif ($mode === 'codes') {
		print $control_box = caFormControlBox(
			caFormNavButton($this->request, __CA_NAV_ICON_SAVE__, _t("Done"), '', 'system', 'Preferences', 'EditMFAPrefs', []), 
			'', 
			''
		);
?>
		<h1><?= _t("Preferences").": "._t("Two-factor authentication"); ?></h1>
		<p><?= _t("These are your recovery codes. If you lose access to your authenticator app, you can log in with a recovery code instead of an app code. Each code works once."); ?></p>
		<p class="mfaWarning"><?= _t("Save them somewhere safe now, such as in a password manager or as a printed copy. They will not be shown again."); ?></p>
		<div class="mfaRecoveryCodes">
<?php
		foreach($this->getVar('recovery_codes') ?? [] as $code) {
			print htmlspecialchars($code)."<br/>\n";
		}
?>
		</div>
		<?= $control_box; ?>
<?php
	} elseif ($status['enabled']) {
		print $control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Generate new recovery codes"), 'mfaManageForm').' '.
			caFormJSButton($this->request, __CA_NAV_ICON_DELETE__, _t("Disable two-factor authentication"), 'MFADisableButton', ['onclick' => 'caDisableMFA()']),
			'', 
			''
		);
?>
		<h1><?= _t("Preferences").": "._t("Two-factor authentication"); ?></h1>
		<p class="mfaStatus"><?= _t("Two-factor authentication is <strong>on</strong> (since %1). When you log in with your password, you will also be asked for a code from your authenticator app.", caGetLocalizedDate($status['enabled_on'], ['dateFormat' => 'delimited', 'timeOmit' => true])); ?></p>
<?php
		if ($status['recovery_codes'] < 3) {
?>
		<p class="mfaWarning"><?= ($status['recovery_codes'] == 0) ? _t("You have no recovery codes left. Generate new ones below.") : _t("You have only %1 recovery code(s) left. Consider generating new ones.", $status['recovery_codes']); ?></p>
<?php
		} else {
?>
		<p><?= _t("You have %1 unused recovery codes.", $status['recovery_codes']); ?></p>
<?php
		}
?>
		<p><?= _t("To generate new recovery codes or turn off two-factor authentication, enter your password and a current code from your app (or a recovery code)."); ?></p>
		<?= caFormTag($this->request, 'MFARegenerateCodes', 'mfaManageForm'); ?>
			<div class="formLabel mfaField"><?= _t("Password"); ?><br/><input type="password" name="password" autocomplete="current-password" /></div>
			<div class="formLabel mfaField"><?= _t("Code"); ?><br/><input type="text" name="code" autocomplete="one-time-code" /></div>
		</form>
		<script>
			function caDisableMFA() {
				jQuery('#mfaManageForm').attr('action', <?= json_encode(caNavUrl($this->request, 'system', 'Preferences', 'MFADisable')); ?>).submit();
			}
		</script>
<?php
		print $control_box;
	} else {
		print $control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_GO__, _t("Set up two-factor authentication"), 'mfaBeginForm'),
			'', 
			''
		);
?>
		<h1><?= _t("Preferences").": "._t("Two-factor authentication"); ?></h1>
		<p class="mfaStatus"><?= _t("Two-factor authentication is <strong>off</strong>."); ?></p>
		<p><?= _t("With two-factor authentication, logging in requires both your password and a code from an authenticator app on your personal device (typically a phone or tablet). Someone who learns your password still cannot log in without your device."); ?></p>
<?php
		if ($this->getVar('email_fallback')) {
?>
		<p><?= _t("If you don't have your device, you can log in with a recovery code or have a code sent to the email address in your user profile."); ?></p>
<?php
		}
?>
		<?= caFormTag($this->request, 'MFABeginSetup', 'mfaBeginForm'); ?>
		</form>
<?php
		print $control_box;
	}
?>
</div>
<div class="editorBottomPadding"><!-- empty --></div>
