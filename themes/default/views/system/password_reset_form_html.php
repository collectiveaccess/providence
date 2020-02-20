<?php
/* ----------------------------------------------------------------------
 * app/views/system/password_reset_form_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
$vb_render = $this->getVar('renderForm');
$vs_token = $this->getVar('token');
$vs_username = $this->getVar('username');
?>
<html>
<head>
	<title><?php print $this->request->config->get("app_display_name"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />

	<link href="<?php print $this->request->getThemeUrlPath(); ?>/css/login.css" rel="stylesheet" type="text/css" />
	<?php
	print AssetLoadManager::getLoadHTML($this->request);
	?>

	<script type="text/javascript">
		// initialize CA Utils
		jQuery(document).ready(function() { caUI.utils.disableUnsavedChangesWarning(true); });
	</script>
</head>
<body>
<div align="center">
	<div id="loginBox">
		<div align="center">
			<img src="<?php print $this->request->getThemeUrlPath()."/graphics/logos/".$this->request->config->get('login_logo');?>" border="0">
		</div>
		<div id="systemTitle">

			<p class="content">
<?php
				if($vb_render) {
					print _t("Please enter a new password");
				} else {
					print _t("Invalid user or token");
				}
?>
			</p>

			<?php
			if ($va_notifications = $this->getVar('notifications')) {
				?>
				<p class="notificationContent"><?php foreach($va_notifications as $va_notification) { print $va_notification['message']."<br/>\n"; }; ?></p>
			<?php
			}
			?>

		</div><!-- end  systemTitle -->
		<div id="loginForm">
<?php if($vb_render) { ?>
			<?php print caFormTag($this->request, 'DoReset', 'reset'); ?>
			<div class="loginFormElement"><?php print _t("Password"); ?>:<br/>
				<input type="password" name="password" size="25"/>
			</div>
			<div class="loginFormElement"><?php print _t("Re-type password"); ?>:<br/>
				<input type="password" name="password2" size="25"/>
			</div>
<?php 	if(strlen($vs_token)>0){ ?>
			<input type="hidden" name="token" value="<?php print $vs_token; ?>"/>
			<input type="hidden" name="username" value="<?php print $vs_username; ?>"/>
<?php 	} ?>

			<div class="loginSubmitButton"><?php print caFormSubmitButton($this->request, __CA_NAV_ICON_LOGIN__, _t("Submit"),"reset", array('icon_position' => __CA_NAV_ICON_ICON_POS_RIGHT__)); ?></div>
			</form>
<?php } ?>
			<?php print caNavLink($this->request, _t("Back to login"), 'loginLink', 'system/auth', 'login', ''); ?>
		</div><!-- end loginForm -->
	</div><!-- end loginBox -->
</div><!-- end center -->
</body>
</html>