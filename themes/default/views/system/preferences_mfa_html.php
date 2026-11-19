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
<style>
	.mfaSection { max-width: 640px; margin: 10px 0 20px 0; line-height: 1.5; }
	.mfaSection p { margin: 0 0 10px 0; }
	.mfaSection form { margin: 0 0 20px 0; }
	.mfaField { margin: 0 0 10px 0; }
	.mfaField input { width: 200px; }
	.mfaSecret { font-family: monospace; font-size: 14px; letter-spacing: 1px; }
	.mfaQRCode { margin: 10px 0; }
	.mfaQRCode svg { background: #fff; padding: 8px; }
	.mfaRecoveryCodes { font-family: monospace; font-size: 15px; columns: 2; max-width: 320px; margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
	.mfaWarning { color: #b00; }
</style>
<div class="sectionBox">
	<h1><?= _t("Preferences").": "._t("Two-factor authentication"); ?></h1>
	<div class="mfaSection">
<?php
	if (!$available) {
?>
		<p><?= _t("Two-factor authentication is not available on this system. Please contact your administrator."); ?></p>
<?php
	} elseif ($this->getVar('uses_external_login') && !$status['enabled']) {
?>
		<p><?= _t("Your login is handled by your organization's single sign-on system. Contact your administrator about adding two-factor authentication there."); ?></p>
<?php
	} elseif ($mode === 'setup') {
?>
		<p><?= _t("<strong>Step 1:</strong> Open an authenticator app on your phone (such as Google Authenticator, Microsoft Authenticator, 1Password or Authy) and scan this code."); ?></p>
<?php
		if ($qr = $this->getVar('qr_code')) {
?>
		<div class="mfaQRCode"><?= $qr; ?></div>
		<p><?= _t("Can't scan it? Enter this key in the app instead:"); ?><br/><span class="mfaSecret"><?= htmlspecialchars($this->getVar('secret')); ?></span></p>
<?php
		} else {
?>
		<p><?= _t("Enter this key in the app:"); ?><br/><span class="mfaSecret"><?= htmlspecialchars($this->getVar('secret')); ?></span></p>
<?php
		}
?>
		<p><?= _t("<strong>Step 2:</strong> Enter the 6-digit code the app shows."); ?></p>
		<?= caFormTag($this->request, 'MFAConfirmSetup', 'mfaConfirmForm'); ?>
			<div class="mfaField"><input type="text" name="code" autocomplete="one-time-code" inputmode="numeric" autofocus /></div>
			<?= caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Turn on two-factor authentication"), 'mfaConfirmForm'); ?>
			<?= caNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'system', 'Preferences', 'EditMFAPrefs', []); ?>
		</form>
<?php
	} elseif ($mode === 'codes') {
?>
		<p><?= _t("These are your recovery codes. If you lose access to your authenticator app, you can log in with one of them instead of an app code. Each code works once."); ?></p>
		<p class="mfaWarning"><?= _t("Save them somewhere safe now, such as a password manager or a printed copy. They won't be shown again."); ?></p>
		<div class="mfaRecoveryCodes">
<?php
		foreach($this->getVar('recovery_codes') ?? [] as $code) {
			print htmlspecialchars($code)."<br/>\n";
		}
?>
		</div>
		<?= caNavButton($this->request, __CA_NAV_ICON_SAVE__, _t("I've saved my codes"), '', 'system', 'Preferences', 'EditMFAPrefs', []); ?>
<?php
	} elseif ($status['enabled']) {
?>
		<p><?= _t("Two-factor authentication is <strong>on</strong> (since %1). When you log in with your password, you'll also be asked for a code from your authenticator app.", caGetLocalizedDate($status['enabled_on'], ['dateFormat' => 'delimited', 'timeOmit' => true])); ?></p>
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
			<div class="mfaField"><?= _t("Password"); ?>:<br/><input type="password" name="password" autocomplete="current-password" /></div>
			<div class="mfaField"><?= _t("Code"); ?>:<br/><input type="text" name="code" autocomplete="one-time-code" /></div>
			<?= caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Generate new recovery codes"), 'mfaManageForm'); ?>
			<a href="#" class="form-button" onclick="if(confirm(<?= htmlspecialchars(json_encode(_t('Turn off two-factor authentication?')), ENT_QUOTES); ?>)) { jQuery('#mfaManageForm').attr('action', <?= htmlspecialchars(json_encode(caNavUrl($this->request, 'system', 'Preferences', 'MFADisable')), ENT_QUOTES); ?>).submit(); } return false;"><span class="form-button"><?= caNavIcon(__CA_NAV_ICON_DELETE__, '30px', ['class' => 'form-button-left', 'style' => 'padding-right: 5px']); ?><?= _t("Turn off two-factor authentication"); ?></span></a>
		</form>
<?php
	} else {
?>
		<p><?= _t("Two-factor authentication is <strong>off</strong>."); ?></p>
		<p><?= _t("With two-factor authentication, logging in requires both your password and a code from an authenticator app on your phone. Someone who learns your password still can't log in without your phone."); ?></p>
<?php
		if ($this->getVar('email_fallback')) {
?>
		<p><?= _t("If you don't have your phone, you can log in with a recovery code or have a code sent to the email address in your user profile."); ?></p>
<?php
		}
?>
		<?= caFormTag($this->request, 'MFABeginSetup', 'mfaBeginForm'); ?>
			<?= caFormSubmitButton($this->request, __CA_NAV_ICON_ADD__, _t("Set up two-factor authentication"), 'mfaBeginForm'); ?>
		</form>
<?php
	}
?>
	</div>
</div>
<div class="editorBottomPadding"><!-- empty --></div>
