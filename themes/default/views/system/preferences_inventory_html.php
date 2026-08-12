<?php
/* ----------------------------------------------------------------------
 * app/views/system/preferences_batch_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Reset"), '', 'system', 'Preferences', $this->request->getAction(), []), 
		'', 
		''
	);

	$group_info = $t_user->getPreferenceGroupInfo($group);
	print "<h1>"._t("Preferences").": "._t($group_info['name'])."</h1>\n";
	
	print caFormTag($this->request, 'Save', 'PreferencesForm');
	
	$prefs = $t_user->getValidPreferences($group);
	
	foreach($prefs as $pref) {
		print "<div>".$t_user->preferenceHtmlFormElement($pref, "<div><div class='formLabel'>^EXTRA^LABEL</div><div class='formLabelText'>^ELEMENT</div></div>", array())."</div>\n";
	}
?>
		<input type="hidden" name="action" value="<?= $this->request->getAction(); ?>"/>
	</form>
<?php
	print $control_box;
?>

	<div class="editorBottomPadding"><!-- empty --></div>
</div>