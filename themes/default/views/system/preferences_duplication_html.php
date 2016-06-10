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
	/** @var ca_users $t_user */
	$t_user = 					$this->getVar('t_user');
	$vs_group = 				$this->getVar('group');
	$va_bundle_list =			$this->getVar('bundle_list');
	
	$va_prefs = 				$t_user->getValidPreferences($vs_group);
	$vb_duplicate_metadata = 	$t_user->getPreference("{$vs_current_table}_duplicate_attributes");
 ?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'PreferencesForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Reset"), '', 'system', 'Preferences', $this->request->getAction()."/".$this->request->getActionExtra(), array()), 
		'', 
		''
	);

	$va_group_info = $t_user->getPreferenceGroupInfo($vs_group);
	print "<h1>"._t("Preferences").": "._t($va_group_info['name'])."</h1>\n";
	
	print caFormTag($this->request, 'Save/'.$this->request->getActionExtra(), 'PreferencesForm');
	
	
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
		if($t_user->isValidPreference($vs_current_table.'_duplicate_element_settings')) {
			print "<table class='preferenceColumnSelector'>\n";
			print "<thead><tr><th>" . _t('Metadata element') . "</th><th><a href='#' class='preferenceColumnSelector' id='duplicationOn'>" . _t('Duplicate') . "</a></th><th><a href='#' class='preferenceColumnSelector' id='duplicationOff'>" . _t('Do not duplicate') . "</a></th></tr></thead>\n";

			$vs_pk = $t_instance->primaryKey();
			print "<tbody>";
			foreach ($va_bundle_list as $vs_bundle_name => $va_info) {
				print "<tr>";
				print "<td class='preferenceColumnSelectorLabel'>" . $va_info['bundle_info']['display'] . "</td>";

				$vn_duplication_setting = $va_info['duplication_setting'];
				print "<td>" . caHTMLRadioButtonInput("duplicate_element_settings[{$vs_bundle_name}]", array('value' => 1, 'class' => "duplication_setting_on"), array('checked' => ($vn_duplication_setting == 1), "disabled" => !$vb_duplicate_metadata)) . "</td>\n";
				print "<td>" . caHTMLRadioButtonInput("duplicate_element_settings[{$vs_bundle_name}]", array('value' => 0, 'class' => "duplication_setting_off"), array('checked' => ($vn_duplication_setting == 0), "disabled" => !$vb_duplicate_metadata)) . "</td>\n";
				print "</tr>\n";
			}
			print "<tbody>";
			print "</table>\n";
		}
		
		
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
	
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery("#duplicationOn").on('click', function (e) { jQuery(".duplication_setting_on").prop("checked", 1); return false; });
			jQuery("#duplicationOff").on('click', function (e) { jQuery(".duplication_setting_off").prop("checked", 1); return false; });

			//jQuery(".duplication_setting_on, .duplication_setting_off").attr('disabled', (jQuery("select[name='pref_<?php print $vs_current_table; ?>_duplicate_attributes']").val() == 0));		
			jQuery("select[name='pref_<?php print $vs_current_table; ?>_duplicate_attributes']").on('change', function() {
				jQuery(".duplication_setting_on, .duplication_setting_off").attr('disabled', (jQuery(this).val() == 0));
			});
			
		});
	</script>