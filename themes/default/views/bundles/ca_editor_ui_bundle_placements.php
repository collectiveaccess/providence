<?php
/* ----------------------------------------------------------------------
 * bundles/ca_editor_ui_bundle_placements.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2020 Whirl-i-Gig
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
$t_screen 			= $this->getVar('t_screen');
$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
$settings			= $this->getVar('settings');

$va_available_display_items 			= array_filter($t_screen->getAvailableBundles(), function($v) { return !$v['deprecated']; });

foreach($va_available_display_items as $vs_bundle => $va_item) {
	unset($va_available_display_items[$vs_bundle]['settings']);	// strip lists of valid settings - we don't need to send them to the client and they can be fairly large
}

$va_to_display_items  = $t_screen->getPlacementsInScreen(array('noCache' => true));

print caEditorBundleShowHideControl($this->request, $vs_id_prefix.'UIEditorBundlePlacements');
print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix.'UIEditorBundlePlacements', $settings);
?>
<div class="bundleDisplayPlacementEditorContainer" id="<?= $vs_id_prefix; ?>UIEditorBundlePlacements">
	<div id="bundleDisplayPlacementEditor" class="bundleDisplayPlacementEditor">
		<div class="bundleDisplayPlacementEditorHelpText"><?= _t("Drag your selection from column to column to edit the contents of the screen."); ?></div>
		<table>
			<tr valign="top">
				<td>
					<div><?= _t("Available editor elements"); ?></div>
		
					<div id="bundleDisplayEditorAvailableList" class="bundleDisplayEditorPlacementList"><!-- empty --></div>
				</td>
				<td>
					<div><?= _t("Elements to display on this screen"); ?></div>
					
					<div id="bundleDisplayEditorToDisplayList" class="bundleDisplayEditorPlacementList"><!-- empty --></div>
				</td>
			</tr>
		</table>
		
		
		<input type="hidden" id="<?= $vs_id_prefix; ?>displayBundleList" name="<?= $vs_id_prefix; ?>displayBundleList" value=""/>
	</div>
	
	<script type="text/javascript">
		var bundleDisplayOps = null;
		jQuery(document).ready(function() {
			bundleDisplayOps = caUI.bundlelisteditor({
				availableListID: 'bundleDisplayEditorAvailableList',
				toDisplayListID: 'bundleDisplayEditorToDisplayList',
				
				availableDisplayList: <?= json_encode($va_available_display_items); ?>,
				initialDisplayList: 	<?= json_encode($va_to_display_items); ?>,
				initialDisplayListOrder : <?= json_encode(array_keys($va_to_display_items)); ?>,
				
				displayBundleListID: '<?= $vs_id_prefix; ?>displayBundleList',
				
				settingsIcon: "<?= caNavIcon(__CA_NAV_ICON_INFO__, 1); ?>",
				saveSettingsIcon: "<?= caNavIcon(__CA_NAV_ICON_GO__, 1); ?>"
			});		
		});
	</script>
</div>
