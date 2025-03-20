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
AssetLoadManager::register('sortableUI');

$settings 			= $this->getVar('settings');
$is_batch			= $this->getVar('batch');

$force_values = $this->getVar('forceValues');

$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');

$t_set 			= $this->getVar('t_set');		// set

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
//print_r($initial_values);
$errors = $failed_inserts = [];

$bundles_to_edit_proc = $this->getVar('bundles_to_edit');
$container_element_code = $this->getVar('container_element_code');
$found_element_code = $this->getVar('found_element_code');
?>
 <div id="<?= $id_prefix; ?>">
 	<div class="bundleContainer"> </div>

<?php
	$qr = $t_set->getItemsAsSearchResult();
	$count = $qr ? $qr->numHits() : 0;
	
	$found  = $not_found = $in_part = $not_checked = 0;
	
	if($qr && ($count > 0)) {
		while($qr->nextHit()) {
			switch($z=$qr->get('ca_set_items.inventory_cont.found_object', ['convertCodesToIdno' => true])) {
				case 'located':
					$found++;
					break;
				case 'not_located':
					$not_found++;
					break;
				default:
					$not_checked++;
					break;
			}
		}
	}
	
	//
	// Template to generate display for existing items
	//
?>
	
	<div class="bundleContainer">
<?php
	if(is_array($initial_values) && sizeof($initial_values)) {
?>
	    <div class='bundleSubLabel' style='text-align: center;'>
<?php
			//print caEditorBundleBatchEditorControls($this->request, $settings['placement_id'] ?? null, $t_set, $t_item->tableName(), $settings);
          //  print caEditorBundleSortControls($this->request, $id_prefix, $t_item->tableName(), $t_item->tableName(), array_merge($settings, ['sort' => $loaded_sort, 'sortDirection' => $loaded_sort_direction]));
?>
			<h3>
				<?= _t('<a href="#" class="inventoryItemShowFound">Found</a>: %1 (%2%)', $found, sprintf("%3.1f", $found/$count * 100));?> - 
				<?= _t('<a href="#" class="inventoryItemShowNotFound">Not found</a>: %1 (%2%)', $not_found, sprintf("%3.1f", $not_found/$count * 100));?> - 
				<?= _t('<a href="#" class="inventoryItemShowNotChecked">Not checked</a>: %1 (%2%)', $not_checked, sprintf("%3.1f", $not_checked/$count * 100));?>
				<?= _t('<a href="#" class="inventoryItemShowAll">All</a>: %1', $count);?>
			</h3>
		</div>
<?php
	}
	
?>
		<div class="caItemList">
<?php
	foreach($initial_values as $item_id => $item) {
?>
		<table style="margin: 15px 5px 15px 5px; width: 100%; border-bottom: 1px dotted #aaa;" class="inventoryItem inventoryItem_<?= $item["{$container_element_code}.{$found_element_code}_idno"] ?: 'not_checked'; ?>">
			<tr>
				<td colspan="2"><?= caEditorLink($this->request, $item['displayTemplate'], '', 'ca_objects', $item['row_id']); ?></td>
			</tr>
			<tr valign="top">
				<td style="width: 125px;"><?= $item['representation_tag']; ?></td>
				<td style="position: relative;">
<?php
		
			if($item["{$container_element_code}.{$found_element_code}"] ?? null) {
?>
<div style='font-size: 10px; font-weight: normal; font-style: italic;'>
<?php
				foreach($bundles_to_edit_proc as $f) {
					print $t_item->getDisplayLabel("ca_set_items.{$f}").": ".$item["{$f}_display"]."<br>\n";
				}
?>
</div>
<?php
			} else {
				foreach($bundles_to_edit_proc as $f) {
					print "<div style='font-size: 10px; font-weight: normal; font-style: italic;'>".$t_item->getDisplayLabel("ca_set_items.{$f}").
					"<br/>".
					$t_item->htmlFormElementForSimpleForm($this->request, "ca_set_items.{$f}", ['name' => "inventory_{$item_id}_{$f}", "id" => str_replace('.', '_', "inventory_{$item_id}_{$f}"), 'value' => $item[$f], 'width' =>'525px', 'height' => 1])."</div>\n";
				}
			}
?>
					<div style="position: absolute; right: 10px; bottom: 5px;"><?= caEditorLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, "20px"), '' , 'ca_set_items', $item_id); ?></div>
				</td>
			</tr>
		</table>
<?php	
	}
?>
		</div>

	</div>
</div>

<script>
	function caFilterInventoryByFoundStatus(s) {
		if(s) {
			jQuery('.inventoryItem').hide(0);
			jQuery('.inventoryItem_' + s).show(0);
		} else {
			jQuery('.inventoryItem').show(0);
		}
	}
	
	jQuery(document).ready(function() {
		jQuery('.inventoryItemShowFound').on('click', function(e) {
			caFilterInventoryByFoundStatus('located');
		});
		jQuery('.inventoryItemShowNotFound').on('click', function(e) {
			caFilterInventoryByFoundStatus('not_located');
		});
		jQuery('.inventoryItemShowNotChecked').on('click', function(e) {
			caFilterInventoryByFoundStatus('not_checked');
		});
		jQuery('.inventoryItemShowAll').on('click', function(e) {
			caFilterInventoryByFoundStatus(null);
		});
	});
</script>