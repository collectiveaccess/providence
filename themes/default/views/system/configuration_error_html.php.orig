<?php
/** ---------------------------------------------------------------------
 * themes/default/views/system/configuration_error_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
		$va_tmp = explode("/", str_replace("\\", "/", $_SERVER['SCRIPT_NAME']));
		array_pop($va_tmp);
		$vs_path = join("/", $va_tmp);
		
		if (!is_array($opa_error_messages)) {
			$opa_error_messages = self::$opa_error_messages;
		}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>CollectiveAccess configuration error display</title>
	<link href="<?php print $vs_path; ?>/themes/default/css/error.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<div id='box'>
	<div id="logo"><img src="<?php print $vs_path ?>/themes/default/graphics/logos/ca_logo.png"/></div><!-- end logo -->
	<div id="content">
		<?php print "<div class='error'>An error in your system configuration has been detected</div>
			General installation instructions can be found
			<a href='http://wiki.collectiveaccess.org/index.php?title=Installation_(Providence)' target='_blank'>here</a>.
			For more specific hints on the existing issues please have a look at the messages below."; ?>
		<br/><br/>
<?php
foreach ($opa_error_messages as $vs_message):
?>
		<div class="permissionError">
			<img src='<?php print $vs_path; ?>/themes/default/graphics/vorsicht.gif' class="permissionErrorIcon"/>
			<?php print $vs_message; ?>
			<div style='clear:both; height:1px;'><!-- empty --></div>
		</div>
		<br/>
<?php
endforeach;
?>
	
</div><!-- end content --></div><!-- end box -->
</body>
</html>
