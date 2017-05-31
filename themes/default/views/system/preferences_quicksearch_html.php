<?php
/* ----------------------------------------------------------------------
 * app/views/system/preferences_quicksearch_html.php : 
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
 
	$t_user = $this->getVar('t_user');
	$vs_group = $this->getVar('group'); 
 ?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'PreferencesForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Reset"), '', 'system', 'Preferences', $this->request->getAction(), array()), 
		'', 
		''
	);

	$va_group_info = $t_user->getPreferenceGroupInfo($vs_group);
	print "<h1>"._t("Preferences").": "._t($va_group_info['name'])."</h1>\n";
	
	print caFormTag($this->request, 'Save', 'PreferencesForm');
	
	$va_prefs = $t_user->getValidPreferences($vs_group);
	
	
	$va_available_items = $this->getVar('available_searches');
	$va_to_display_items = $this->getVar('selected_searches');
?>
	<div class="bundleDisplayPlacementEditorContainer" id="<?php print $vs_id_prefix; ?>">
	<div id="bundleDisplayPlacementEditor" class="bundleDisplayPlacementEditor">
		<div class="bundleDisplayPlacementEditorHelpText"><?php print _t("Drag your selection from column to column to edit the content and order of results in the quick search interface."); ?></div>
		<table>
			<tr valign="top">
				<td>
					<div class="preferenceColumnHeader"><?php print _t("Available searches"); ?></div>
		
					<div id="bundleDisplayEditorAvailableList" class="preferencePlacementList"><!-- empty --></div>
				</td>
				<td>
					<div class="preferenceColumnHeader"><?php print _t("Searches to display"); ?></div>
					
					<div id="bundleDisplayEditorToDisplayList" class="preferencePlacementList"><!-- empty --></div>
				</td>
			</tr>
		</table>
		
		
		<input type="hidden" id="displayBundleList" name="displayBundleList" value=""/>
	</div>
	
	<script type="text/javascript">
		var bundleDisplayOps = null;
		jQuery(document).ready(function() {
			bundleDisplayOps = caUI.bundlelisteditor({
				availableListID: 'bundleDisplayEditorAvailableList',
				toDisplayListID: 'bundleDisplayEditorToDisplayList',
				
				availableDisplayList: <?php print json_encode($va_available_items); ?>,
				initialDisplayList: 	<?php print json_encode($va_to_display_items); ?>,
				initialDisplayListOrder : <?php print json_encode(array_keys($va_to_display_items)); ?>,
				
				displayBundleListID: 'displayBundleList',
				
				allowSettings: false,
				settingsIcon: "<?php print caNavIcon(__CA_NAV_ICON_INFO__, 1); ?>"
			});		
		});
	</script>
</div>
<?php
	
	$o_dm = Datamodel::load();
	print "<div class='preferenceSectionDivider'><!-- empty --></div>\n"; 
	
	
?>
		<input type="hidden" name="action" value="EditQuickSearchPrefs"/>
	</form>
<?php
	print $vs_control_box;
?>
</div>

	<div class="editorBottomPadding"><!-- empty --></div>