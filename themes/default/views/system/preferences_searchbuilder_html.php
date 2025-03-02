<?php
/* ----------------------------------------------------------------------
 * app/views/system/preferences_searchbuilder_html.php : 
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

$prefs = $t_user->getValidPreferences($group);

$group_info = $t_user->getPreferenceGroupInfo($group);
$available_items = $this->getVar('available_bundles');
$to_display_items = $this->getVar('selected_bundles');
$priority_available_items = $this->getVar('available_priority_bundles');
$priority_to_display_items = $this->getVar('selected_priority_bundles');
?>
<div class="sectionBox">
<?php
	print $control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'PreferencesForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Reset"), '', 'system', 'Preferences', $this->request->getAction()."/".$this->request->getActionExtra(), array()), 
		'', 
		''
	);

	print "<h1>"._t("Preferences").": "._t($group_info['name'])."</h1>\n";
	
	print caFormTag($this->request, 'Save/'.$this->request->getActionExtra(), 'PreferencesForm');
?>
	<h3><?= _t("Frequently used search options"); ?></h3>
	<div class="bundleDisplayPlacementEditorContainer">
		<div id="bundlPriorityPlacementEditor" class="bundleDisplayPlacementEditor">
			<div class="bundleDisplayPlacementEditorHelpText"><?= _t("Drag your selection from column to column to edit the search options in the <em>frequently used</em> options list. The list includes oft-used options shown at the top of the options list."); ?></div>
			<table>
				<tr valign="top">
					<td>
						<div class="bundleDisplayEditorSearchForm">
							<input type="text" name="priority_available_search" id="bundlePriorityEditorAvailableListSearch" placeholder="<?= _t('Filter'); ?>"/>
							<i class="caIcon fas fa-search fa-1x"></i>
						</div>
						
						<div class="preferenceColumnHeader"><?= _t("Available options"); ?></div>
						
						<div id="bundlePriorityEditorAvailableList" class="preferencePlacementList"><!-- empty --></div>
					</td>
					<td>
						<div class="preferenceColumnHeader"><?= _t("Options in <em>frequently used</em> list"); ?></div>
						
						<div id="bundlePriorityEditorToDisplayList" class="preferencePlacementList"><!-- empty --></div>
					</td>
				</tr>
			</table>
			
			
			<input type="hidden" id="useBundleList" name="useBundleList" value=""/>
		</div>
	</div>
	<h3><?= _t("Search options"); ?></h3>
	<div class="bundleDisplayPlacementEditorContainer">
		<div id="bundleDisplayPlacementEditor" class="bundleDisplayPlacementEditor">
			<div class="bundleDisplayPlacementEditorHelpText"><?= _t("Drag your selection from column to column to modify available search options."); ?></div>
			<table>
				<tr valign="top">
					<td>
						<div class="bundleDisplayEditorSearchForm">
							<input type="text" name="available_search" size="15"  id="bundleEditorAvailableListSearch" placeholder="<?= _t('Filter'); ?>"/>
							<i class="caIcon fas fa-search fa-1x"></i>
						</div>
						<div class="preferenceColumnHeader"><?= _t("Available options"); ?></div>
			
						<div id="bundleDisplayEditorAvailableList" class="preferencePlacementList"><!-- empty --></div>
					</td>
					<td>
						<div class="preferenceColumnHeader"><?= _t("Used options"); ?></div>
						
						<div id="bundleDisplayEditorToDisplayList" class="preferencePlacementList"><!-- empty --></div>
					</td>
				</tr>
			</table>
			
			<input type="hidden" id="usePriorityBundleList" name="usePriorityBundleList" value=""/>
		</div>
	</div>
	<script type="text/javascript">
		let bundleDisplayOps = null;
		let bundlePriorityOps = null;
		jQuery(document).ready(function() {
			bundleDisplayOps = caUI.bundlelisteditor({
				availableListID: 'bundleDisplayEditorAvailableList',
				toDisplayListID: 'bundleDisplayEditorToDisplayList',
				
				availableDisplayList: <?= json_encode($available_items); ?>,
				initialDisplayList: <?= json_encode($to_display_items); ?>,
				initialDisplayListOrder : <?= json_encode(array_keys($to_display_items)); ?>,
				
				availableSearchID: 'bundleEditorAvailableListSearch',
				
				displayBundleListID: 'useBundleList',
				
				allowSettings: false,
				settingsIcon: "<?= caNavIcon(__CA_NAV_ICON_INFO__, 1); ?>",
				saveSettingsIcon: "<?= caNavIcon(__CA_NAV_ICON_GO__, 1); ?>"
			});		
			
			bundlePriorityOps = caUI.bundlelisteditor({
				availableListID: 'bundlePriorityEditorAvailableList',
				toDisplayListID: 'bundlePriorityEditorToDisplayList',
				
				availableDisplayList: <?= json_encode($priority_available_items); ?>,
				initialDisplayList: <?= json_encode($priority_to_display_items); ?>,
				initialDisplayListOrder : <?= json_encode(array_keys($priority_to_display_items)); ?>,
				
				availableSearchID: 'bundlePriorityEditorAvailableListSearch',
				
				displayBundleListID: 'usePriorityBundleList',
				
				allowSettings: false,
				settingsIcon: "<?= caNavIcon(__CA_NAV_ICON_INFO__, 1); ?>",
				saveSettingsIcon: "<?= caNavIcon(__CA_NAV_ICON_GO__, 1); ?>"
			});		
		});
	</script>
</div>
	<div class='preferenceSectionDivider'><!-- empty --></div>
	<input type="hidden" name="action" value="EditSearchBuilderPrefs"/>
</form>
<?= $control_box; ?>
</div>
<div class="editorBottomPadding"><!-- empty --></div>

