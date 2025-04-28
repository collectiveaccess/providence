<?php
/* ----------------------------------------------------------------------
 * bundles/inventory_list.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
AssetLoadManager::register('inventoryEditorUI');

$settings 			= $this->getVar('settings');
$is_batch			= $this->getVar('batch');

$force_values 		= $this->getVar('forceValues');

$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');

$t_set 				= $this->getVar('t_set');		// set
$set_id 			= $t_set->getPrimaryKey();

$t_item 			= $this->getVar('t_item');			// ca_set_item
$t_row				= $this->getVar('t_item');
$table_num 			= $t_set->get('ca_sets.table_num');

$add_label 			= $this->getVar('add_label');

$read_only			= (isset($settings['readonly']) && $settings['readonly']);
$dont_show_add		= (isset($settings['dontShowAddButton']) && $settings['dontShowAddButton']);
$dont_show_delete	= (isset($settings['dontShowDeleteButton']) && $settings['dontShowDeleteButton']);

$num_per_page 		= caGetOption('numPerPage', $settings, 10);
$initial_values 	= caSanitizeArray($this->getVar('initialValues'), ['removeNonCharacterData' => false]);

// Dyamically loaded sort ordering
$loaded_sort 			= $this->getVar('sort');
$loaded_sort_direction 	= $this->getVar('sortDirection');
$errors = $failed_inserts = [];

$bundles_to_edit_proc = $this->getVar('bundles_to_edit');
$container_element_code = $this->getVar('container_element_code');
$found_element_code = $this->getVar('found_element_code');

$config = Configuration::load();
$inventory_found_options = $this->getVar('inventory_found_options');

?>
 <div id="<?= $id_prefix; ?>">
<?php	
	if(is_array($initial_values) && sizeof($initial_values)) {
?>
	<div class='bundleSubLabel inventoryStats' style='text-align: center;'>
<?php
		print "<div style='float:right; '>".caEditorPrintSetItemsControls($this)."</div>";
		
		print _t("Sort by %1", caHTMLSelect('sort', $this->getVar('sorts'), ['id' => "{$id_prefix}inventorySortControl"]));
?>
		<!--<a href="#" onclick='inventoryEditorOps.showGrid(); return false;'>Show Grid</a>-->
		<div id="<?= $id_prefix; ?>inventoryCounts" class="inventoryCounts"></div>
	</div>
	<br style="clear: both">
<?php
	}
	
?>
 	<div id="<?= $id_prefix; ?>inventoryItemEditor"> 
 		<div id="<?= $id_prefix; ?>inventoryItemList" class="inventoryList"> </div>
 	</div>

	<div class="inventoryItemEditorOverlay" id="<?= $id_prefix; ?>inventoryItemEditorOverlay">
		Grid here
	</div>
	
	<textarea class="<?= $id_prefix; ?>inventoryItemTemplate" style="display: none;">
		<div class="inventoryItem">
			{representation_tag}
			{displayTemplate} Status: {_INVENTORY_STATUS_}
			
			<a href="#" id="inventory_{item_id}_set_status" class="inventorySetStatusButton">Set status</a>
		</div>
	</textarea>
	
	<textarea class="<?= $id_prefix; ?>inventoryEditorTemplate" style="display: none;">
<?php
	foreach($bundles_to_edit_proc as $f) {
		print "<div style='font-size: 10px; font-weight: normal; font-style: italic;'>".$t_item->getDisplayLabel("ca_set_items.{$f}").
		"<br/>".
		$t_item->htmlFormElementForSimpleForm($this->request, "ca_set_items.{$f}", ['name' => "inventory_{item_id}_{$f}", "id" => str_replace('.', '_', "inventory_{item_id}_{$f}"), 'value' => "{".str_replace('.', '_', $f)."}", 'width' =>'525px', 'height' => 1, 'textAreaTagName' => 'textentry'])."</div>\n";
	}
?>
	</textarea>
</div>

<script>
	var inventoryEditorOps = null;
	jQuery(document).ready(function() {
		inventoryEditorOps = caUI.inventoryeditor({
			container: '<?= $id_prefix; ?>',
			inventoryID: <?= (int)$set_id; ?>,
			table_num: <?= (int)$t_set->get('table_num'); ?>,
			fieldNamePrefix: '<?= $id_prefix; ?>',
			initialValues: <?= json_encode(array_values($initial_values)); ?>,
			
			inventoryItemListID: '<?= $id_prefix; ?>inventoryItemList',
			inventoryItemAutocompleteID: '<?= $id_prefix; ?>inventoryItemAutocompleter',
			inventoryCountsID: '<?= $id_prefix; ?>inventoryCounts',
			sortControlID: '<?= $id_prefix; ?>inventorySortControl',
			lookupURL: '<?= $lookup_urls['search']; ?>',
			itemInfoURL: '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'GetItemInfo'); ?>',
			itemListURL: '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'GetItemList', ['placement_id' => $this->getVar('placement_code')]); ?>',
			
			itemTemplateClass: '<?= $id_prefix; ?>inventoryItemTemplate',
			editorTemplateClass: '<?= $id_prefix; ?>inventoryEditorTemplate',
			
			editSetItemsURL: '<?= caNavUrl($this->request, 'manage/set_items', 'SetItemEditor', 'Edit', ['set_id' => $set_id]); ?>',
			editSetItemToolTip: <?= json_encode(_t('Edit inventory item information')); ?>,
			
			editSetItemButton: <?= json_encode(caNavIcon(__CA_NAV_ICON_EDIT__, "20px")); ?>,
			deleteSetItemButton: <?= json_encode(caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, "20px")); ?>,
			
			inventorySetStatusButtonClass: 'inventorySetStatusButton',
			inventoryFoundOptions: <?= json_encode($inventory_found_options); ?>,
			inventoryFoundBundle: <?= json_encode("{$container_element_code}.{$found_element_code}"); ?>,
			
			displayTemplate: <?= (isset($settings['displayTemplate']) ? json_encode($settings['displayTemplate']) : 'null'); ?>,
			sorts: <?= json_encode($this->getVar('sorts')); ?>
		});
	});
</script>