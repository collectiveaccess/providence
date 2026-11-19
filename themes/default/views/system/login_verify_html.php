<?php
/* ----------------------------------------------------------------------
 * app/views/system/login_verify_html.php : second step of login for users with two-factor authentication
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
  AppController::getInstance()->removeAllPlugins();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?= $this->request->config->get("app_display_name"); ?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />

		<link href="<?= caGetThemeUrlPath() ?>/css/login.css" rel="stylesheet" type="text/css" />
		<?= AssetLoadManager::getLoadHTML($this->request); ?>

		<script type="text/javascript">
			// initialize CA Utils
			jQuery(document).ready(function() { caUI.initUtils({disableUnsavedChangesWarning: true}); jQuery('#mfaCode').focus(); });
		</script>
	</head>
	<body>
		<div align="center">
			<div id="loginBox">
				<div align="center">
					<?= caGetDefaultLogo(); ?>
				</div>
				<div id="systemTitle">
					<?= $this->request->config->get("app_display_name"); ?>

<?php
			if ($notifications = $this->getVar('notifications')) {
?>
				<p class="notificationContent"><?php foreach($notifications as $notification) { print $notification['message']."<br/>\n"; }; ?></p>
<?php
			}
?>
				</div><!-- end  systemTitle -->
				<div id="loginForm">
					<?= caFormTag($this->request, 'DoVerify', 'verify'); ?>
						<div class="loginFormElement"><?= _t("Enter the code from your authenticator app"); ?>:<br/>
							<input id="mfaCode" type="text" name="code" size="25" autocomplete="one-time-code" inputmode="numeric" />
						</div>
						<div class="loginFormElement" style="font-size: 11px;"><?= $this->getVar('can_email_code') ? _t("No access to your app? Enter one of your recovery codes, or have a code sent to your email.") : _t("No access to your app? Enter one of your recovery codes instead."); ?></div>
						<input name="local" type="hidden" value="<?= (bool)($_REQUEST['local'] ?? null) ? 1 : 0; ?>" />
						<div class="loginSubmitButton"><?= caFormSubmitButton($this->request, __CA_NAV_ICON_LOGIN__, _t("Verify"), "verify", array('icon_position' => __CA_NAV_ICON_ICON_POS_RIGHT__)); ?></div>
					</form>
<?php if($this->getVar('can_email_code')) { ?>
					<?= caFormTag($this->request, 'SendCode', 'sendCode'); ?>
						<input name="local" type="hidden" value="<?= (bool)($_REQUEST['local'] ?? null) ? 1 : 0; ?>" />
					</form>
<?php } ?>
					<div id="forgotLink">
<?php if($this->getVar('can_email_code')) { ?>
						<a href="#" onclick="jQuery('#sendCode').submit(); return false;"><?= _t("Email me a code"); ?></a> &nbsp;|&nbsp;
<?php } ?>
						<?= caNavLink($this->request, _t("Cancel"), '', 'system/auth', 'login', ''); ?>
					</div>
				</div><!-- end loginForm -->
			</div><!-- end loginBox -->
		</div><!-- end center -->
	</body>
</html>
