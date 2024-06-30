<?php
/* ----------------------------------------------------------------------
 * app/views/system/preferences_find_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
$t_user = $this->getVar('t_user');
$group = $this->getVar('group');
?>
<div class="sectionBox">
<?php
	print $control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'PreferencesForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Reset"), '', 'system', 'Preferences', $this->request->getAction(), array()), 
		'', 
		''
	);

	$group_info = $t_user->getPreferenceGroupInfo($group);
	print "<h1>"._t("Preferences").": "._t($group_info['name'])."</h1>\n";
	
	print caFormTag($this->request, 'Save', 'PreferencesForm');
	
	$prefs = $t_user->getValidPreferences($group);
	
	print "<div class='preferenceSectionDivider'><!-- empty --></div>\n"; 
	
	foreach(array(
		'ca_objects'
	) as $table) {
		if (!caTableIsActive($table)) { continue; }
		$t_instance = Datamodel::getInstanceByTableName($table, true);
?>
		<h2><?= _t('Available results views for %1', $t_instance->getProperty('NAME_PLURAL')); ?></h2>
	
		<table width='100%'><tr valign='top'><td width='250'>
<?php
		foreach($prefs as $pref) {
			print $t_user->preferenceHtmlFormElement($pref, '^ELEMENT', []);
		}
?>
		</td></tr></table>
		<br/>
		<div class='preferenceSectionDivider'><!-- empty --></div>
<?php
	}
?>
	<input type="hidden" name="action" value="<?= $this->request->getAction(); ?>"/>
</form>
<?= $control_box; ?>
	<div class="editorBottomPadding"><!-- empty --></div>
</div>