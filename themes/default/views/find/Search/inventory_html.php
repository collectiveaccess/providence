<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_sets_html.php :
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
$t_subject 			= $this->getVar('t_subject');
$o_result_context 	= $this->getVar('result_context');
$t_list 			= new ca_lists();

$can_edit_inventories = (bool)(is_array($inventories = $this->getVar('inventories')) && sizeof($inventories) && $this->request->user->canDoAction('can_edit_inventories'));
$can_create_inventories = (bool)$this->request->user->canDoAction('can_create_inventories');

// Source list
$source_select = caHTMLSelect('source', 
	[
		_t('Add all') => 'from_results',
		_t('Add checked') => 'from_checked',
		_t('Add random') => 'from_random'
	], 
	[
		'id' => 'caInventorySource', 'class' => 'searchSetsSelect setSource', 
		'onChange' => 'return caInventoryUpdateForm();'
	],
	['value' => null]
);

// Existing inventory list
$options = [];
foreach($inventories as $set_id => $set_info) {
	$options[$set_info['name']] = $set_id;
}
$set_list = $can_edit_inventories ? caHTMLSelect('set_id', 
	$options, 
	['id' => 'caInventoryList', 'class' => 'searchSetsSelect setSource'], 
	['value' => null]
) : '';

// Text entry for new set
$new_set_input = $can_create_inventories ? caHTMLTextInput('set_name', 
	[
		'id' => 'caNewInventoryInput', 
		'style' => $can_edit_inventories ? 'display: none;' : '',
		'class' => 'searchSetsTextInput setSource', 
		'value' => '', 
		'placeholder' => _t('New inventory name')
	], 
	[]
) : '';

if ($can_edit_inventories || $can_create_inventories) {
?>
<div class='inventoryTools'>
	<a href="#" id='searchInventoryToolsShow' onclick="$('.inventoryTools').hide(); return caShowSearchInventoryTools(true);"><?= caNavIcon(__CA_NAV_ICON_INVENTORY__, 2).' '._t("Inventory"); ?></a>
</div><!-- end inventoryTools -->

<div id="searchInventoryTools">
	<div class="col">
		<span class='header'>
			<?= _t("Add to inventory"); ?>:
			<?= caBusyIndicatorIcon($this->request, ['id' => 'caInventoryRequestIndicator']); ?>
		</span>
		<br>
		<form id="caCreateInventoryFromResults">
			<?= _t("%1 to %2", 
				$source_select,
				($can_edit_inventories ? $set_list : '').($can_create_inventories ? $new_set_input : '')
			).
			caHTMLHiddenInput('mode', ['value' => 'U', 'id' => 'caInventorySaveMode']); ?>
			<div class="inventoryControlBlock">
<?php 
	if($can_create_inventories && $can_edit_inventories) { 
?>
				<a href='#' onclick="return caToggleNewSetControl(true);" id="caShowNewInventoryInput" class="button"><?= _t('%1 Create inventory', caNavIcon(__CA_NAV_ICON_DOT__, 1, ['class' => 'iconSmall', 'aria-description' => _t('Create inventory')])); ?></a>
				<a href='#' onclick="return caToggleNewSetControl(false);" id="caShowInventoryList" class="button" style="display: none;",><?= _t('%1 Choose inventory', caNavIcon(__CA_NAV_ICON_DOT__, 1, ['class' => 'iconSmall', 'aria-description' => _t('Add to existing inventory')])); ?></a>
<?php
	}
?>
			</div>
			<div class="inventoryControlBlock" id="caInventoryLimitInput"><?= _t('Limit to %1 %2', caHTMLTextInput("limit", ['id' => 'caInventoryResultsLimit', 'value' => 25], ['width' => '25px']), $t_subject->getProperty('NAME_PLURAL')); ?></div>
			<div class="inventoryControlBlock" id="caInventoryExcludeInput"><?= _t('%1 Exclude inventoried', caHTMLCheckboxInput("excludePreviouslyInventoried", ['id' => 'caExcludePreviouslyInventoried', 'class' => 'inventoryExclude', 'checked' => '1', 'value' => 1])); ?></div>
			<div class="inventorySaveBlock">
				<a href='#' onclick="return caCreateInventoryFromResults();" class="button"><?= _t('%1 Save', caNavIcon(__CA_NAV_ICON_SAVE__, 2, ['aria-description' => _t('Save to inventory')])); ?></a>
			</div>
		</form>
	</div>

	<a href='#' id='hideSets' onclick='caShowSearchInventoryTools(false); $(".inventoryTools").slideDown(250);'><?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, 1); ?></a>
	<br/>
	<div class="clear">&nbsp;</div>
</div><!-- end searchInventoryTools -->
<?php
	}
?>
<script type="text/javascript">
	function caShowSearchInventoryTools(show=true) {
		if(show) {
			jQuery('.inventoryTools').hide();
			jQuery("#searchInventoryTools").slideDown(250);
			
			jQuery('.setTools').show();
			jQuery("#searchSetTools").slideUp(250);
			
			jQuery("input.addItemToSetControl").show(); 
		} else {		
			jQuery('.inventoryTools').show();
			jQuery("#searchInventoryTools").slideUp(250);
			
			jQuery("input.addItemToSetControl").hide(); 
		}
	}
	
	function caToggleNewSetControl(show) {
		if(show) {
			jQuery('#caNewInventoryInput, #caShowInventoryList').show();
			jQuery('#caInventoryList, #caShowNewInventoryInput').hide();
			jQuery('#caInventorySaveMode').val('I');
		} else {
			jQuery('#caNewInventoryInput, #caShowInventoryList').hide();
			jQuery('#caInventoryList, #caShowNewInventoryInput').show();
			jQuery('#caInventorySaveMode').val('U');
		}
		return false;
	}
	
	function caInventoryUpdateForm() {
		const m = jQuery('#caInventorySource').val();
		
		if(m === 'from_random') {
			jQuery('#caInventoryLimitInput').show();
		} else {
			jQuery('#caInventoryLimitInput').hide();
		}
	}
	
	//
	// Find and return list of checked items to be added to set
	// item_ids are returned in a simple array
	//
	function caGetSelectedItemIDsToAddToInventory() {
		var selectedItemIDS = [];
		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
			if (jQuery(j).prop('checked')) {
				selectedItemIDS.push(jQuery(j).val());
			}
		});
		return selectedItemIDS;
	}
	
	function caToggleAddToInventory() {
		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
			jQuery(j).prop('checked', !jQuery(j).prop('checked'));
		});
		return false;
	}
	function caCreateInventoryFromResults() {
		jQuery("#caInventoryRequestIndicator").show();
		
		const is_update = (jQuery('#caInventorySaveMode').val() === 'U');
		jQuery.post(
			'<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addToInventory'); ?>', 
			{ 
				set_id: jQuery('#caInventoryList').val(), 
				set_name: !is_update ? jQuery('#caNewInventoryInput').val() : null,
				mode: jQuery('#caInventorySaveMode').val(),
				source: jQuery('#caInventorySource').val(),
				limit: jQuery('#caInventoryResultsLimit').val(),
				excludePreviouslyInventoried: jQuery('#caExcludePreviouslyInventoried').is(':checked') ? 1 : 0,
				item_ids: caGetSelectedItemIDsToAddToInventory().join(';'),
				csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>
			}, 
			function(res) {
				jQuery("#caInventoryRequestIndicator").hide();
				
				const header = is_update ? <?= json_encode(_t('Add to inventory')); ?> : <?= json_encode(_t('Create inventory')); ?>;
					
				if (res['status'] === 'ok') { 
					let item_type_name;
					if (res['num_items_added'] == 1) {
						item_type_name = <?= json_encode($t_subject->getProperty('NAME_SINGULAR')); ?>;
					} else {
						item_type_name = <?= json_encode($t_subject->getProperty('NAME_PLURAL')); ?>;
					}
					let msg = is_update ? <?= json_encode(_t('Added ^num_items ^item_type_name to <i>^set_name</i>'));?>
										: <?= json_encode(_t('Created inventory <i>^set_name</i> with ^num_items ^item_type_name'));?>;
	
					
					msg = msg.replace('^num_items', res['num_items_added']);
					msg = msg.replace('^item_type_name', item_type_name);
					msg = msg.replace('^set_name', res['set_name']);
					
					if(!is_update) {
						// add new set to "add to inventory" list
						jQuery('#caInventoryList').append($("<option/>", {
							value: res['set_id'],
							text: res['set_name'],
							selected: 1
						}));
						// add new set to search by set drop-down
						jQuery("form.caSearchSetsForm select.searchSetSelect").append($("<option/>", {
							value: 'set:"' + res['set_code'] + '"',
							text: res['set_name']
						}));
						jQuery("select.caInventoryList").append($("<option/>", {
							value: res['set_id'],
							text: res['set_name']
						}));
					}
					
					if (res['num_items_already_in_inventory'] > 0) { 
						msg += <?= json_encode(_t('<br/>(^num_dupes were already in the inventory.)')); ?>;
						msg = msg.replace('^num_dupes', res['num_items_already_in_inventory']);
					}
					if(res['num_items_wrong_type'] > 0) {
						sg += <?= json_encode(_t('<br/>(^num_wrong_type were incorrect type for inventory.)')); ?>;
						msg = msg.replace('^num_wrong_type', res['num_items_wrong_type']);
					}
					if(res['num_items_previously_inventoried'] > 0) {
						sg += <?= json_encode(_t('<br/>(^num_previously_inventoried were previously inventoried.)')); ?>;
						msg = msg.replace('^num_previously_inventoried', res['num_items_previously_inventoried']);
					}
					
					jQuery.jGrowl(msg, { header: header }); 
					jQuery('#caFindResultsForm .addItemToSetControl').attr('checked', false);
				} else { 
					jQuery.jGrowl(res['error'], { header: header });
				};
			},
			'json'
		);
	}
	
	jQuery(document).ready(function() {
		caInventoryUpdateForm();
	});
</script>
