<?php
/* ----------------------------------------------------------------------
 * app/views/system/preferences_duplication_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2016 Whirl-i-Gig
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
 
 	$vs_current_table = 		$this->getVar('current_table');
	$t_user = 					$this->getVar('t_user');
	$vs_group = 				$this->getVar('group');
	$va_bundle_list =			$this->getVar('bundle_list');
	
 
 ?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'PreferencesForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Reset"), '', 'system', 'Preferences', $this->request->getAction()."/".$this->request->getActionExtra(), array()), 
		'', 
		''
	);

	$va_group_info = $t_user->getPreferenceGroupInfo($vs_group);
	print "<h1>"._t("Preferences").": "._t($va_group_info['name'])."</h1>\n";
	
	print caFormTag($this->request, 'Save/'.$this->request->getActionExtra(), 'PreferencesForm');
	
	$va_prefs = $t_user->getValidPreferences($vs_group);
	
	
	$o_dm = Datamodel::load();
	print "<div class='preferenceSectionDivider'><!-- empty --></div>\n"; 
	
	if (caTableIsActive($vs_current_table) && $this->request->user->canDoAction('can_duplicate_'.$vs_current_table)) {
		$t_instance = $o_dm->getInstanceByTableName($vs_current_table, true);
		print "<h2>"._t('Settings for %1', $t_instance->getProperty('NAME_PLURAL'))."</h2>";
	
		print "<table width='100%'><tr valign='top'><td width='250'>";
		foreach($va_prefs as $vs_pref) {
			if ($vs_pref == 'duplicate_relationships') { continue; }
			print $t_user->preferenceHtmlFormElement("{$vs_current_table}_{$vs_pref}", null, array());
		}
		print "</td>";
		if (in_array("duplicate_relationships", $va_prefs)) {
			print "<td>".$t_user->preferenceHtmlFormElement("{$vs_current_table}_duplicate_relationships", null, array('useTable' => true, 'numTableColumns' => 3))."</td>";
		}
	
		print "</tr></table>\n";
		
		// metadata elements
		print "<table>\n";			
		print "<tr align='center' valign='middle'><th width='180' align='left'>"._t('Element')."</th><th><a href='#' onclick='jQuery(\".{$vs_current_table}_duplication_setting_on\").prop(\"checked\", 1); return false;'>"._t('Duplicate')."</a></th><th width='180'><a href='#' onclick='jQuery(\".{$vs_current_table}_duplication_setting_off\").prop(\"checked\", 1); return false;'>"._t('Do not duplicate')."</a></th></tr>\n";
	
		$vs_pk = $t_instance->primaryKey();
		foreach($va_bundle_list as $vs_bundle_name => $va_info) {
			print "<tr align='center' valign='middle'>";
			print "<td align='left'>".$va_info['bundle_info']['display']."</td>";
		
			$vn_duplication_setting = $va_info['duplication_setting'];
			print "<td>".caHTMLRadioButtonInput("duplicate_element_settings[{$vs_bundle_name}]", array('value' => 1, 'class' => "{$vs_current_table}_duplication_setting_on"), array('checked' => ($vn_duplication_setting == 1)))."</td>\n";
			print "<td>".caHTMLRadioButtonInput("duplicate_element_settings[{$vs_bundle_name}]", array('value' => 0, 'class' => "{$vs_current_table}_duplication_setting_off"), array('checked' => ($vn_duplication_setting == 0)))."</td>\n";

		}
		print "</tr>\n";
		print "</table>\n";
		
		
		print "<div class='preferenceSectionDivider'><!-- empty --></div>\n"; 
	} else {
		print "<h3>"._t('No preferences available')."</h3>";
	}

?>
		<input type="hidden" name="action" value="EditDuplicationPrefs"/>
	</form>
<?php
	print $vs_control_box;
?>
</div>

	<div class="editorBottomPadding"><!-- empty --></div>