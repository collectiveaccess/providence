<?php
/** ---------------------------------------------------------------------
 * themes/default/views/manage/Pawtucket/edit_global_values_html.php : Edit global values
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
	$va_form_elements = $this->getVar('form_elements');
?>
	<div>
		<h1><?php print _t('Global value editor'); ?></h1>
	</div>
	<div class="searchReindexHelpText">
		<?php print _t('Global values are editable text values that may be displayed in any view template in your Pawtucket theme. They are especially useful for managing semi-static text embedded in a web site, such as upcoming holiday hours or planned maintenance. You may edit globals using the form below. Configure additional global values by adding them to your theme app.conf configuration file.'); ?>
<?php
	if (sizeof($va_form_elements) == 0) {
?>
		<div style="text-align: center;">
			<h2><?php print _t('No global values for Pawtucket are configured'); ?></h2>
		</div>
<?php
	}
?>
	</div>
	<div style="clear:both; height:1px;"><!-- empty --></div>
<?php	
	print caFormTag($this->request, 'saveGlobalValues', 'globalValuesForm', null, 'post', 'multipart/form-data', '_top', ['disableUnsavedChangesWarning' => true]);
	
	if (sizeof($va_form_elements) > 0) {
		foreach($va_form_elements as $vs_name => $va_info) {
?>
			<div>
				<div class="formLabel">
					<?php print $va_info['label']; ?><br/>
					<?php print $va_info['element']; ?>
				</div>
			</div>
<?php
		}
		print "<div style='text-align: center'>".caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'globalValuesForm', array())."</div>";	
	}
?>
</form>