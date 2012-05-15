<?php
/* ----------------------------------------------------------------------
 * views/manage/search_forms/ajax_element_settings_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
	$t_form 			= $this->getVar('t_form');
	$vn_form_id			= $t_form->getPrimaryKey();
	$vn_group_code 		= $this->getVar('group_code');
	$vs_element_code 	= $this->getVar('element_code');
	$va_element_info 	= $t_form->getInfoForElementInFormGroup($vn_group_code, $vs_element_code);
	$va_settings 		= $t_form->getAvailableElementSettings();
	
?>
<div id="caSearchFormSettingsMessage" class="searchFormElementSettingsMessage notification-info-box rounded" style="display: none;"><!-- empty --></div>
<h3><?php print _t('Settings for Group #%1/%2', ($vn_group_code + 1), $va_element_info['name']); ?><h3>
<?php
	print caFormTag($this->request, 'setSettingsForElement', 'searchFormElementSettingsForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
?>
<?php
	foreach($va_settings as $vs_setting => $va_setting_info) {
		print $t_form->elementSettingHTMLFormElement($vn_group_code, $vs_element_code, $vs_setting);
	}
	
	print caHTMLHiddenInput('form_id', array('value' => $vn_form_id));
	print caHTMLHiddenInput('group', array('value' => $vn_group_code));
	print caHTMLHiddenInput('element', array('value' => $vs_element_code));
	
	print caJSButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save settings"), 'searchFormElementSettingsForm', array(), array('onclick' => 'jQuery.getJSON("'.caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "setSettingsForElement").'", jQuery("#searchFormElementSettingsForm").serialize(), function(data, status) { jQuery("#caSearchFormSettingsMessage").show().html("<ul class=\"notification-info-box\"><li class=\"notification-info-box\">" + data.message + "</li></ul>"); });'));
?>

</form>

<?php
	print TooltipManager::getLoadHTML();
?>