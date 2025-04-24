<?php
/* ----------------------------------------------------------------------
 * bundles/ca_storage_locations_contents.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2024 Whirl-i-Gig
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
if(!($qr_result = $this->getVar('qr_result'))) { return; }

$id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
$t_subject 		= $this->getVar('t_subject');				// ca_storage_locations
$settings 		= $this->getVar('settings');
$placement_code = $this->getVar('placement_code');
$placement_id	= $settings['placement_id'] ?? null;
$color 			= $settings['colorItem'] ?? '';
$policy			= $this->getVar('policy');
$target			= $this->getVar('target');

$rel_table 		= $qr_result->tableName();
$path 			= array_keys(Datamodel::getPath($t_subject->tableName(), $rel_table) ?? []);
$linking_table 	= $path[1] ?? null;
$errors 		= [];

// Dyamically loaded sort ordering
$loaded_sort 			= $this->getVar('sort');
$loaded_sort_direction 	= $this->getVar('sortDirection');

$initial_values 		= $this->getVar('initialValues');
$num_per_page 			= $settings['numPerPage'] ?? 10;
$count 					= $this->getVar('total');

if (!$this->request->isAjax()) {
	print caEditorBundleShowHideControl($this->request, $id_prefix, $settings, caInitialValuesArrayHasValue($id_prefix.$t_subject->tableNum().'_rel', $this->getVar('initialValues')));
	print caEditorBundleMetadataDictionary($this->request, $id_prefix.$t_subject->tableNum().'_rel', $settings);
}	
	foreach($action_errors = $this->request->getActionErrors($placement_code) as $o_error) {
		$errors[] = $o_error->getErrorDescription();
	}
?>
<div id="<?= $id_prefix; ?>">

	<textarea class='caItemTemplate' style='display: none;' <?= $batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	switch($settings['list_format'] ?? null) {
		case 'list':
?>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo listRel caRelatedItem">
			{_display}
		</div>
<?php
			break;
		case 'bubbles':
		default:
?>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo roundedRel caRelatedItem">
			{_display}
		</div>
<?php
	}
?>
	</textarea>
	<div class="bundleContainer">
<?php
	if ($qr_result && ($qr_result->tableName() == 'ca_objects') && $qr_result->numHits() > 0) {
?>
		<div class="caHistoryTrackingCurrentContentsControls">
			
			<?= caEditorBundleSortControls($this->request, $id_prefix, $rel_table, $t_subject->tableName(), array_merge($settings, ['sort' => $loaded_sort, 'sortDirection' => $loaded_sort_direction])); ?>
			<?php if($linking_table) { print caGetPrintFormatsListAsHTMLForRelatedBundles($id_prefix, $this->request, $t_subject, new $rel_table, new $linking_table, $placement_id); } ?>
			
			<?= caReturnToHomeLocationControlForRelatedBundle($this->request, $id_prefix, $t_subject, $policy, $qr_result); ?>
			<?= caEditorBundleBatchEditorControls($this->request, $placement_id, $t_subject, $qr_result->tableName(), $settings); ?>
			
		</div>
<?php
	}
?>
		<div class="caItemList">

		</div>
	</div>
</div>
<?php
	//
	// Template to generate display for existing items
	//
?>
<script type="text/javascript">
	var caRelationBundle<?= $id_prefix; ?>;
	jQuery(document).ready(function() {
		jQuery('#<?= $id_prefix; ?>caItemListSortControlTrigger').click(function() { jQuery('#<?= $id_prefix; ?>caItemListSortControls').slideToggle(200); return false; });
		jQuery('#<?= $id_prefix; ?>caItemListSortControls a.caItemListSortControl').click(function() {jQuery('#<?= $id_prefix; ?>caItemListSortControls').slideUp(200); return false; });
				
		caRelationBundle<?= $id_prefix; ?> = caUI.initRelationBundle('#<?= $id_prefix; ?>', {
			fieldNamePrefix: '<?= $id_prefix; ?>_',
			formName: '<?= $this->getVar('id_prefix'); ?>',
			templateValues: ['label', 'type_id', 'id'],
			initialValues: <?= json_encode($initial_values); ?>,
			initialValueOrder: <?= json_encode(array_keys($initial_values)); ?>,
			itemID: '<?= $id_prefix; ?>Item_',
			placementID: '<?= $placement_id; ?>',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			hideOnNewIDList: ['<?= $id_prefix; ?>_edit_related_'],
			bundlePreview: <?= caGetBundlePreviewForRelationshipBundle($this->getVar('initialValues')); ?>,
			readonly: false,
			isSortable: false,
			listSortOrderID: '<?= $id_prefix; ?>BundleList',
			listSortItems: 'div.roundedRel',			
			autocompleteInputID: '<?= $id_prefix; ?>_autocomplete',
			sortUrl: '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Sort', array('table' => $rel_table)); ?>',
			
			loadedSort: <?= json_encode($loaded_sort); ?>,
			loadedSortDirection: <?= json_encode($loaded_sort_direction); ?>,
			
			itemColor: '<?= $color; ?>',
			firstItemColor: '<?= $first_color; ?>',
			lastItemColor: '<?= $last_color; ?>',
			
			totalValueCount: <?= (int)$count; ?>,
			partialLoadUrl: '<?= caNavUrl($this->request, '*', '*', 'loadBundleValues', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $placement_id, 'bundle' => 'history_tracking_current_contents')); ?>',
			partialLoadIndicator: '<?= addslashes(caBusyIndicatorIcon($this->request)); ?>',
			loadSize: <?= $num_per_page; ?>,
			subjectTypeID: <?= (int)$t_subject->getTypeID(); ?>
		});
	});
</script>
