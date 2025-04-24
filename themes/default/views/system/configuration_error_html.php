<?php
/** ---------------------------------------------------------------------
 * themes/default/views/system/configuration_error_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2025 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage Configuration
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */	
if (!is_array($opa_error_messages ?? null)) {
	$opa_error_messages = self::$opa_error_messages;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>CollectiveAccess configuration error display</title>
	<link href="<?= caGetThemeUrlPath() ?>/css/error.css" rel="stylesheet" type="text/css" media='all'/>
	<link href='../assets/fontawesome/css/all.min.css' rel='stylesheet' type='text/css' media='all'/>
	<link href='../assets/fontawesome/css/v4-shims.min.css' rel='stylesheet' type='text/css' media='all'/>
</head>
<body>
	<div id='box'>
		<div id="logo"><?= caGetDefaultLogo(); ?></div><!-- end logo -->
		<div id="content">
			<?= "<div class='error'>There are issues with your configuration</div>
				<div class='errorDescription'>General installation instructions can be found
				<a href='https://docs.collectiveaccess.org' target='_blank' rel='noopener noreferrer'>here</a>.
				or more specific information on detected issues review the messages below:</div>"; ?>
<?php
foreach ($opa_error_messages as $vs_message) {
?>
			<div class="permissionError">
				<?php if (function_exists("caNavIcon")) { print caNavIcon(__CA_NAV_ICON_ALERT__ , 2, array('class' => 'permissionErrorIcon')); } ?>
				<?= $vs_message; ?>
				<div style='clear:both; height:1px;'><!-- empty --></div>
			</div>
			<br/>
<?php
}
?>
		</div><!-- end content -->
	</div><!-- end box -->
</body>
</html>
