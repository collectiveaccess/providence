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
$config = Configuration::load();

$type_singular 		= $this->getVar('type_singular');
$type_plural 		= $this->getVar('type_plural');

$settings 			= $this->getVar('settings');

$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');

$t_set 				= $this->getVar('t_set');		// set
$set_id 			= $t_set->getPrimaryKey();

$t_item 			= $this->getVar('t_item');			// ca_set_item
$t_row				= $this->getVar('t_item');
$table_num 			= $t_set->get('ca_sets.table_num');

$lookup_urls 		= $this->getVar('lookup_urls');
$read_only			= (isset($settings['readonly']) && $settings['readonly']);
$dont_show_add		= (isset($settings['dontShowAddButton']) && $settings['dontShowAddButton']);
$dont_show_delete	= (isset($settings['dontShowDeleteButton']) && $settings['dontShowDeleteButton']);

$num_per_page 		= caGetOption('numPerPage', $settings, 10);
$item_count			= $this->getVar('itemCount');
$initial_values 	= caSanitizeArray($this->getVar('initialValues'), ['removeNonCharacterData' => false]);

// Dyamically loaded sort ordering
$loaded_sort 			= $this->getVar('sort');
$loaded_sort_direction 	= $this->getVar('sortDirection');
$errors = $failed_inserts = [];

$bundles_to_edit_proc = $this->getVar('bundles_to_edit');
$container_element_code = $this->getVar('container_element_code');
$found_element_code = $this->getVar('found_element_code');

$inventory_found_options = $this->getVar('inventory_found_options');
$inventory_found_option_display_text = $this->getVar('inventory_found_option_display_text');
$inventory_found_icons = $this->getVar('inventory_found_icons');

$unsaved_edit_data = $this->getVar('unsavedEditData');
$scroll_position = $unsaved_edit_data['scrollPosition'] ?? 0;
?>
<div id="<?= $id_prefix; ?>" class="inventoryEditorContainer">
<?php	
	if(is_array($initial_values) && sizeof($initial_values)) {
?>
	<div class='bundleSubLabel inventoryControlPanel' style='text-align: center;'>
<?php
	if($settings['showExportOptions'] ?? true) {
?>
		<div style='float:right; '><?= caEditorPrintSetItemsControls($this); ?></div>
<?php
	}
?>		
		<div class="inventoryControls">
			<div><?= _t('Filter: %1', caHTMLTextInput('inventoryFilter', ['id' => "{$id_prefix}inventoryFilter"], ["width" => "150px"])); ?></div>
			<div><?= _t("Sort: %1", caHTMLSelect('sort', $this->getVar('sorts'), ['id' => "{$id_prefix}inventorySortControl"])); ?></div>
			<div class="inventoryUnsavedChanges" id='<?= $id_prefix; ?>unsavedChanges'><?= _t("%1 Unsaved changes", caNavIcon(__CA_NAV_ICON_ALERT__, '20px')); ?></div>
		</div>
		<div id="<?= $id_prefix; ?>inventoryCounts" class="inventoryStats"></div>
	</div>
<?php
	}
	
	if(!$dont_show_add) {
?>
	<div class="inventoryAddItemPanel" id="<?= $id_prefix; ?>addItemForm">
		<?= _t('Add to inventory').': '; ?>
		<input type="text" size="70" name="inventoryItemAutocompleter" id="<?= $id_prefix; ?>inventoryItemAutocompleter" class="lookupBg"/>
	</div>
<?php
	}
?>
	<input type="hidden" name="<?= $id_prefix; ?>inventoryToDelete" id="<?= $id_prefix; ?>inventoryToDelete"/>
	
 	<div id="<?= $id_prefix; ?>inventoryItemListContainer"> 
 		<div id="<?= $id_prefix; ?>inventoryItemList" class="inventoryList"> </div>
 	</div>
	
	<textarea class="<?= $id_prefix; ?>inventoryItemTemplate" style="display: none;">
		<div class="inventoryItem">
			<div class="inventoryItemContent" id="inventoryItemContent_{n}">
				<div class="inventoryItemNumber">{lnum}</div>
				<div style="width: 140px;">{representation_tag}</div>
				<div class="inventoryItemDescription" style="width: 394px;">
					{loadingMessage}
					{displayTemplate}
					{displayTemplateDescription}
					{violations}
				</div>
				<div style="display: none;" class="inventoryItemEditorContainer">
					
				</div>
				<a href="#" id="inventory_{item_id}_set_status" class="inventorySetStatusButton">{_INVENTORY_STATUS_ICON_}</a>
<?php
if(!$dont_show_delete) {
?>				
				<a href="#" id="inventory_{item_id}_delete" class="inventoryItemDeleteButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
<?php
}
?>
			</div>
		</div>
	</textarea>
	
	<textarea class="<?= $id_prefix; ?>inventoryItemEditorTemplate" style="display: none;">
		<div id="inventoryItemEditor{item_id}" class="inventoryItemEditor">
			{displayTemplate}
<?php
			foreach($bundles_to_edit_proc as $f) {
				print "<div style='font-size: 10px; font-weight: normal; font-style: italic;'>".$t_item->getDisplayLabel("ca_set_items.{$f}").
				"<br>".
				$t_item->htmlFormElementForSimpleForm($this->request, "ca_set_items.{$f}", ['name' => "inventory_{item_id}_{$f}", "id" => str_replace('.', '_', "inventory_{item_id}_{$f}"), 'value' => "{".str_replace('.', '_', $f)."}", 'width' =>'425px', 'height' => 1, 'textAreaTagName' => 'textentry', 'forSearch' => false])."</div>\n";
			}			
?>				
			<div class="inventoryItemEditorDoneButton"><?= caNavIcon(__CA_NAV_ICON_SAVE__, '20px');?> Done</div>
		</div>
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
			numPerPage: <?= (int)$num_per_page; ?>,
			itemCount: <?= (int)$item_count; ?>,
			
			currentSort: <?= json_encode($this->getVar('currentSort')); ?>,
			currentSortDirection: <?= json_encode($this->getVar('currentSortDirection')); ?>,
			
			inventoryToDeleteID: '<?= $id_prefix; ?>inventoryToDelete',
			
			inventoryItemListID: '<?= $id_prefix; ?>inventoryItemList',
			inventoryItemAutocompleteID: '<?= $id_prefix; ?>inventoryItemAutocompleter',
			inventoryCountsID: '<?= $id_prefix; ?>inventoryCounts',
			sortControlID: '<?= $id_prefix; ?>inventorySortControl',
			unsavedChangesID: '<?= $id_prefix; ?>unsavedChanges',
			lookupURL: <?= json_encode($lookup_urls['search']); ?>,
			addItemToInventoryURL: <?= json_encode(caNavUrl($this->request, 'manage/sets', 'SetEditor', 'addItemToInventory')); ?>,
			removeItemFromInventoryURL: <?= json_encode(caNavUrl($this->request, 'manage/sets', 'SetEditor', 'removeItemFromInventory')); ?>,
			
			itemListURL: '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'GetInventoryItemList', ['placement_id' => $this->getVar('placement_code')]); ?>',
			
			itemTemplateClass: '<?= $id_prefix; ?>inventoryItemTemplate',
			editorTemplateClass: '<?= $id_prefix; ?>inventoryItemEditorTemplate',
			
			loadingMessage: <?= json_encode(_t('%1 Loading...', caBusyIndicatorIcon($this->request))); ?>,
			
			inventorySetStatusButtonClass: 'inventorySetStatusButton',
			inventoryFoundOptions: <?= json_encode($inventory_found_options); ?>,
			inventoryFoundIcons: <?= json_encode($inventory_found_icons); ?>,
			inventoryFoundOptionsDisplayText: <?= json_encode($inventory_found_option_display_text); ?>,
			inventoryContainerElementCode: <?= json_encode($container_element_code); ?>,
			inventoryFoundBundle: <?= json_encode("{$container_element_code}.{$found_element_code}"); ?>,
			inventoryContainerSubElementCodes: <?= json_encode($bundles_to_edit_proc); ?>,
			
			inventoryFilterInputID: <?= json_encode("{$id_prefix}inventoryFilter"); ?>,
			
			presetInventoryBundles: <?= json_encode($this->request->user ? $this->request->user->getPreference('inventory_preset_bundles') : []); ?>,
			
			unsavedEditsPersistenceURL: <?= json_encode(caNavUrl($this->request, 'manage/sets', 'SetEditor', 'persistUnsavedEdits', ['bundle' => 'inventory_list', 'table' => 'ca_sets', 'id' => $t_set->getPrimaryKey()])); ?>,
			unsavedEditData: <?= json_encode($unsaved_edit_data); ?>,
			displayTemplate: <?= (isset($settings['displayTemplate']) ? json_encode($settings['displayTemplate']) : 'null'); ?>,
			sorts: <?= json_encode($this->getVar('sorts')); ?>,
			scrollPosition: <?= (int)$scroll_position; ?>
		});
	});
</script>
