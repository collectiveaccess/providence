<?php
/** ---------------------------------------------------------------------
 * themes/default/views/system/configuration_error_intstall_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2021 Whirl-i-Gig
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
?>
<?= _t("<div class='error'>There are issues with your configuration</div>
	    <div class='errorDescription'>General installation instructions can be found
	    <a href='https://manual.collectiveaccess.org/setup/Installation.html' target='_blank'>here</a>.
	    For more specific information on detected issues review the messages below:</div>"); ?>
	<br/><br/>
<?php
foreach (self::$opa_error_messages as $vs_message) {
?>
	<div class="permissionError">
		<?= caNavIcon(__CA_NAV_ICON_ALERT__ , 2, ['class' => 'permissionErrorIcon']); ?>
		<?= $vs_message; ?>
		<div style='clear:both; height:1px;'><!-- empty --></div>
	</div>
	<br/>
<?php
}
