<?php
/* ----------------------------------------------------------------------
 * app/views/manage/metadata_alert_triggers/ajax_rule_trigger_settings_form_html.php
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
 * ----------------------------------------------------------------------
 */

	/** @var ca_metadata_alert_triggers $t_trigger */
	$t_trigger = $this->getVar('t_trigger');
	$vs_prefix = $this->getVar('id_prefix');
	$vn_trigger_id = $t_trigger->getPrimaryKey();
	
	if(is_array($va_available_settings = $this->getVar('available_settings')) && sizeof($va_available_settings)) {
?>
		<div class='formLabel'><span><?php print _t("Trigger-specific options"); ?></span><br/></div>
		<div style="margin-left: 20px;">
<?php
		foreach($va_available_settings as $vs_code => $va_properties) {
			print $t_trigger->settingHTMLFormElement($vs_code, ['name' => $vs_prefix . '_setting_' . $vs_code]);
		}
?>
		</div>
<?php
	}

	print TooltipManager::getLoadHTML();
