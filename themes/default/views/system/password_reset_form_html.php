<?php
/* ----------------------------------------------------------------------
 * app/views/system/password_reset_form_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2024 Whirl-i-Gig
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
$render = $this->getVar('renderForm');
$token = $this->getVar('token');
$username = $this->getVar('username');
?>
<html>
<head>
	<title><?= $this->request->config->get("app_display_name"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />

	<link href="<?= caGetThemeUrlPath() ?>/css/login.css" rel="stylesheet" type="text/css" />
	<?= AssetLoadManager::getLoadHTML($this->request); ?>

	<script type="text/javascript">
		// initialize CA Utils
		jQuery(document).ready(function() { 
			caUI.initUtils({});
			caUI.utils.disableUnsavedChangesWarning(true); 
		});
	</script>
</head>
<body>
<div align="center">
	<div id="loginBox">
		<div align="center">
			<?= caGetDefaultLogo(); ?>
		</div>
		<div id="systemTitle">

			<p class="content">
<?php
				if($render) {
					print _t("Please enter a new password");
				} else {
					print _t("Invalid user or token");
				}
?>
			</p>

			<?php
			if ($notifications = $this->getVar('notifications')) {
				?>
				<p class="notificationContent"><?php foreach($notifications as $notification) { print $notification['message']."<br/>\n"; }; ?></p>
			<?php
			}
			?>

		</div><!-- end  systemTitle -->
		<div id="loginForm">
<?php if($render) { ?>
			<?= caFormTag($this->request, 'DoReset', 'reset'); ?>
			<div class="loginFormElement"><?= _t("Password"); ?>:<br/>
				<input type="password" name="password" size="25"/>
			</div>
			<div class="loginFormElement"><?= _t("Re-type password"); ?>:<br/>
				<input type="password" name="password2" size="25"/>
			</div>
<?php 	if(strlen($token)>0){ ?>
			<input type="hidden" name="token" value="<?= $token; ?>"/>
			<input type="hidden" name="username" value="<?= $username; ?>"/>
<?php 	} ?>

			<div class="loginSubmitButton">
				<?= caFormSubmitButton($this->request, __CA_NAV_ICON_LOGIN__, _t("Submit"), "reset", array('icon_position' => __CA_NAV_ICON_ICON_POS_RIGHT__)); ?>
				<?= caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'system', 'auth', 'login', [], [], []); ?>
			</div>
			</form>
<?php } ?>
		</div><!-- end loginForm -->
	</div><!-- end loginBox -->
</div><!-- end center -->
</body>
</html>
