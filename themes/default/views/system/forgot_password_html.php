<?php
/* ----------------------------------------------------------------------
 * themes/default/views/system/forgot_password_html.php.php :
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
		jQuery(document).ready(function() { caUI.initUtils({disableUnsavedChangesWarning: true}); });
	</script>
</head>
<body>
<div align="center">
	<div id="loginBox">
		<div align="center">
			<img src="<?php print $this->request->getThemeUrlPath()."/graphics/logos/".$this->request->config->get('login_logo');?>" border="0">
		</div>
		<div id="systemTitle">
			<?php print $this->request->config->get("app_display_name"); ?>
			<p class="smallContent">
				<?php print _t("Enter your CollectiveAccess user name below to request a new password. We will send you an email with further instructions."); ?>
			</p>
		</div><!-- end  systemTitle -->
		<div id="loginForm">
			<?php print caFormTag($this->request, 'RequestPassword', 'forgot'); ?>
			<div class="loginFormElement"><?php print _t("User Name"); ?>:<br/>
				<input type="text" name="username" size="25"/>
			</div>
			<div class="loginSubmitButton"><?php print caFormSubmitButton($this->request, __CA_NAV_ICON_LOGIN__, _t("Submit"),'forgot', array('icon_position' => __CA_NAV_ICON_ICON_POS_RIGHT__)); ?></div>
			</form>
		</div><!-- end loginForm -->
	</div><!-- end loginBox -->
</div><!-- end center -->
</body>
</html>