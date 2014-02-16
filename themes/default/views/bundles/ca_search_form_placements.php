<?php
/* ----------------------------------------------------------------------
 * bundles/ca_search_form_elements.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
	$t_form 								= $this->getVar('t_form');
	$vs_id_prefix 							= $this->getVar('placement_code').$this->getVar('id_prefix');
	
	$va_available_display_items 		= $t_form->getAvailableBundles();
	
	foreach($va_available_display_items as $vs_bundle => $va_item) {
		unset($va_available_display_items[$vs_bundle]['settings']);	// strip lists of valid settings - we don't need to send them to the client and they can be fairly large
	}
	
	//getTemplatePlaceholderListForBundle
	$va_to_display_items  				= $t_form->getPlacementsInForm(array('noCache' => true));
	
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.'searchFormPlacements');
?>
<div class="bundleDisplayPlacementEditorContainer" id="<?php print $vs_id_prefix; ?>searchFormPlacements">
	<div id="bundleDisplayPlacementEditor" class="bundleDisplayPlacementEditor">
		<div class="bundleDisplayPlacementEditorHelpText"><?php print _t("Drag your selection from column to column to edit the contents of the search form."); ?></div>
		<table>
			<tr valign="top">
				<td>
					<div><?php print _t("Available search items"); ?></div>
		
					<div id="bundleDisplayEditorAvailableList" class="bundleDisplayEditorPlacementList"><!-- empty --></div>
				</td>
				<td>
					<div><?php print _t("Items to search"); ?></div>
					
					<div id="bundleDisplayEditorToDisplayList" class="bundleDisplayEditorPlacementList"><!-- empty --></div>
				</td>
			</tr>
		</table>
		
		
		<input type="hidden" id="<?php print $vs_id_prefix; ?>displayBundleList" name="<?php print $vs_id_prefix; ?>displayBundleList" value=""/>
	</div>
	
	<script type="text/javascript">
		var bundleDisplayOps = null;
		jQuery(document).ready(function() {
			bundleDisplayOps = caUI.bundlelisteditor({
				availableListID: 'bundleDisplayEditorAvailableList',
				toDisplayListID: 'bundleDisplayEditorToDisplayList',
				
				availableDisplayList: <?php print json_encode($va_available_display_items); ?>,
				initialDisplayList: 	<?php print json_encode($va_to_display_items); ?>,
				initialDisplayListOrder : <?php print json_encode(array_keys($va_to_display_items)); ?>,
				
				displayBundleListID: '<?php print $vs_id_prefix; ?>displayBundleList',
				
				settingsIcon: "<img src='<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/edit.gif' alt='<?php print _t('Settings'); ?>' border='0' width='16' height='16'/>"
			});		
		});
	</script>
</div>