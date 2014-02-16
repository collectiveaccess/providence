<?php
/* ----------------------------------------------------------------------
 * app/views/system/login_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
	print JavascriptLoadManager::getLoadHTML($this->request->getBaseUrlPath());
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
							
<?php 
			if ($va_notifications = $this->getVar('notifications')) {  
?>
				<p class="content"><?php foreach($va_notifications as $va_notification) { print $va_notification['message']."<br/>\n"; }; ?></p>
<?php
			}
?>
				</div><!-- end  systemTitle -->
				<div id="loginForm">
					<?php print caFormTag($this->request, 'DoLogin', 'login'); ?>
						<div class="loginFormElement"><?php print _t("User Name"); ?>:<br/>
							<input type="text" name="username" size="25"/>
						</div>
						<div class="loginFormElement"><?php print _t("Password"); ?>:<br/>
							<input type="password" name="password" size="25"/>
						</div>
						<div class="loginSubmitButton"><?php print caFormSubmitButton($this->request, __CA_NAV_BUTTON_LOGIN__, _t("Login"),"login", array('icon_position' => __CA_NAV_BUTTON_ICON_POS_RIGHT__)); ?></div>
						<script type="text/javascript">
							jQuery(document).ready(function() {
								var pdfInfo = caUI.utils.getAcrobatInfo();
								jQuery("#login").append(
									"<input type='hidden' name='_screen_width' value='"+ screen.width + "'/>" +
									"<input type='hidden' name='_screen_height' value='"+ screen.height + "'/>" +
									"<input type='hidden' name='_has_pdf_plugin' value='"+ ((pdfInfo && pdfInfo['acrobat'] && (pdfInfo['acrobat'] === 'installed')) ? 1 : 0) + "'/>"
								);
							});
						</script>
					</form>
				</div><!-- end loginForm -->
			</div><!-- end loginBox -->
		</div><!-- end center -->
	</body>
</html>