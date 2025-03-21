<?php
/* ----------------------------------------------------------------------
 * bundles/ca_sets.php : 
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
$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
$t_instance 		= $this->getVar('t_instance');
$t_item 			= $this->getVar('t_item');			// set

/** @var ca_sets $t_item_rel */
$t_item_rel 		= $this->getVar('t_item_rel');
$t_subject 			= $this->getVar('t_subject');
$settings 			= $this->getVar('settings');
$add_label 			= $this->getVar('add_label');
$placement_code 	= $this->getVar('placement_code');
$vn_placement_id	= (int)$settings['placement_id'];
$batch				= $this->getVar('batch');

$sort				= ((isset($settings['sort']) && $settings['sort'])) ? $settings['sort'] : '';
$read_only			= ((isset($settings['readonly']) && $settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_sets') == __CA_BUNDLE_ACCESS_READONLY__));
$dont_show_del		= ((isset($settings['dontShowDeleteButton']) && $settings['dontShowDeleteButton'])) ? true : false;

$color 				= ((isset($settings['colorItem']) && $settings['colorItem'])) ? $settings['colorItem'] : '';
$first_color 		= ((isset($settings['colorFirstItem']) && $settings['colorFirstItem'])) ? $settings['colorFirstItem'] : '';
$last_color 		= ((isset($settings['colorLastItem']) && $settings['colorLastItem'])) ? $settings['colorLastItem'] : '';

$quick_add_enabled 	= $this->getVar('quickadd_enabled');

// Dyamically loaded sort ordering
$loaded_sort 			= $this->getVar('sort');
$loaded_sort_direction 	= $this->getVar('sortDirection');

// params to pass during object lookup
$lookup_params = array(
	'types' => isset($settings['restrict_to_types']) ? $settings['restrict_to_types'] : (isset($settings['restrict_to_type']) ? $settings['restrict_to_type'] : ''),
	'noSubtypes' => (int)$settings['dont_include_subtypes_in_type_restriction'],
	'noInline' => (!$quick_add_enabled || (bool) preg_match("/QuickAdd$/", $this->request->getController())) ? 1 : 0,
	'table_num' => $t_subject->tableNum()
);

$count = $this->getVar('relationship_count');
if(caGetOption('showCount', $settings, false)) { print $count ? "({$count})" : ''; }
$num_per_page = caGetOption('numPerPage', $settings, 10);

if ($batch) {
	print caBatchEditorRelationshipModeControl($t_item, $id_prefix);
} else {
	print caEditorBundleShowHideControl($this->request, $id_prefix, $settings, caInitialValuesArrayHasValue($id_prefix, $this->getVar('initialValues')));
}
print caEditorBundleMetadataDictionary($this->request, $id_prefix, $settings);

print "<div class='bundleSubLabel'>";	
if(sizeof($this->getVar('initialValues'))) {
	print caGetPrintFormatsListAsHTMLForRelatedBundles($id_prefix, $this->request, $t_instance, $t_item, $t_item_rel, $vn_placement_id);
}
if(sizeof($this->getVar('initialValues')) && !$read_only && !$sort && ($settings['list_format'] != 'list')) {
	print caEditorBundleSortControls($this->request, $id_prefix, $t_item->tableName(), $t_item_rel->tableName(), array_merge($settings, ['sort' => $loaded_sort, 'sortDirection' => $loaded_sort_direction]));
}
print "<div style='clear:both;'></div></div><!-- end bundleSubLabel -->";

$errors = array();
foreach($action_errors = $this->request->getActionErrors($placement_code) as $o_error) {
	$errors[] = $o_error->getErrorDescription();
}
?>
<div id="<?= $id_prefix; ?>" <?= $batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
<?php
	switch($settings['list_format'] ?? null) {
		case 'list':
?>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo listRel caRelatedItem">
<?php
	if (!$read_only && !$dont_show_del) {
?>				
			<a href="#" class="caDeleteItemButton listRelDeleteButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
<?php
	}
?>
			<a href="#" onclick='editSetItem("{set_id}", "{item_id}");' class="caInterstitialEditButton listRelEditButton"><?= caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?>
			
			<span id='<?= $id_prefix; ?>_BundleTemplateDisplay{n}'>
<?php
			print caGetRelationDisplayString($this->request, 'ca_sets', array('class' => 'caEditItemButton', 'id' => "{$id_prefix}_edit_related_{n}"), array('display' => '_display', 'makeLink' => false, 'relationshipTypeDisplayPosition' => 'none'));
?>
			</span>
			<input type="hidden" name="<?= $id_prefix; ?>_type_id{n}" id="<?= $id_prefix; ?>_type_id{n}" value="{type_id}"/>
			<input type="hidden" name="<?= $id_prefix; ?>_id{n}" id="<?= $id_prefix; ?>_id{n}" value="{id}"/>
		</div>
<?php
			break;
		case 'bubbles':
		default:
?>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo roundedRel caRelatedItem">
			<span id='<?= $id_prefix; ?>_BundleTemplateDisplay{n}'>
<?php
			print caGetRelationDisplayString($this->request, 'ca_sets', array('class' => 'caEditItemButton', 'id' => "{$id_prefix}_edit_related_{n}"), array('display' => '_display', 'makeLink' => false, 'relationshipTypeDisplayPosition' => 'none'));
?>
			</span>
			<input type="hidden" name="<?= $id_prefix; ?>_type_id{n}" id="<?= $id_prefix; ?>_type_id{n}" value="{type_id}"/>
			<input type="hidden" name="<?= $id_prefix; ?>_id{n}" id="<?= $id_prefix; ?>_id{n}" value="{id}"/>
<?php
	if (!$read_only && !$dont_show_del) {
?><a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a><?php
	}
?>			
			<div style="display: none;" class="itemName">{label}</div>
			<div style="display: none;" class="itemIdno">{idno_sort}</div>
		</div>
<?php
			break;
	}
?>
	</textarea>
<?php
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo caRelatedItem">
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?= $id_prefix; ?>_autocomplete{n}" value="{{label}}" id="<?= $id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<input type="hidden" name="<?= $id_prefix; ?>_id{n}" id="<?= $id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>

						<a href="<?= urldecode(caEditorUrl($this->request, 'ca_sets', '{set_id}')); ?>" class="caEditItemButton" id="<?= $id_prefix; ?>_edit_related_{n}"><?= caNavIcon(__CA_NAV_ICON_GO__, 1); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
<?php
	if (sizeof($errors)) {
?>
		<span class="formLabelError"><?= join("; ", $errors); ?><br class="clear"/></span>
<?php
	}
?>
		
		</div>
		<div class="caNewItemList"></div>
		<input type="hidden" name="<?= $id_prefix; ?>BundleList" id="<?= $id_prefix; ?>BundleList" value=""/>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
<?php
	if (!$read_only) {
?>	
		<div class='button labelInfo caAddItemButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $add_label ? $add_label : _t("Add relationship"); ?></a></div>
<?php
	}
?>
	</div>
</div>

<?php if($quick_add_enabled) { ?>
<div id="caRelationQuickAddPanel<?= $id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?= $id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?= _t('Quick Add', $t_item->getProperty('NAME_SINGULAR')); ?></div>
		
	</div>
</div>
<?php } ?>

<script type="text/javascript">
	var caSetItemEditPanel<?= $id_prefix; ?>;
	var caRelationBundle<?= $id_prefix; ?>;
	jQuery(document).ready(function() {
		jQuery('#<?= $id_prefix; ?>caItemListSortControlTrigger').click(function() { jQuery('#<?= $id_prefix; ?>caItemListSortControls').slideToggle(200); return false; });
		jQuery('#<?= $id_prefix; ?>caItemListSortControls a.caItemListSortControl').click(function() {jQuery('#<?= $id_prefix; ?>caItemListSortControls').slideUp(200); return false; });
		
<?php if($quick_add_enabled) { ?>
		if (caUI.initPanel) {
			caRelationQuickAddPanel<?= $id_prefix; ?> = caUI.initPanel({ 
				panelID: "caRelationQuickAddPanel<?= $id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRelationQuickAddPanel<?= $id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
				jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
		}
		
		
		if (caUI.initPanel) {
			caSetItemEditPanel<?= $id_prefix; ?> = caUI.initPanel({ 
				panelID: "caSetItemEditPanel<?= $id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caSetItemEditPanel<?= $id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
					jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
					if(caBundleUpdateManager) { 
						caBundleUpdateManager.reloadInspector(); 
					}
				}
			});
			console.log("feh", caSetItemEditPanel<?= $id_prefix; ?>);
		}
<?php } ?>		
		caRelationBundle<?= $id_prefix; ?> = caUI.initRelationBundle('#<?= $id_prefix; ?>', {
			fieldNamePrefix: '<?= $id_prefix; ?>_',
			initialValues: <?= json_encode($this->getVar('initialValues')); ?>,
			initialValueOrder: <?= json_encode(array_keys($this->getVar('initialValues'))); ?>,
			itemID: '<?= $id_prefix; ?>Item_',
			placementID: '<?= $vn_placement_id; ?>',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			newItemListClassName: 'caNewItemList',
			listItemClassName: 'caRelatedItem',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			hideOnNewIDList: ['<?= $id_prefix; ?>_edit_related_'],
			showEmptyFormsOnLoad: 1,
			minChars: <?= (int)$t_subject->getAppConfig()->get([$t_subject->tableName()."_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
			autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'Set', 'Get', $lookup_params); ?>',
			types: <?= json_encode($settings['restrict_to_types'] ?? null); ?>,
			restrictToAccessPoint: <?= json_encode($settings['restrict_to_access_point'] ?? null); ?>,
			restrictToSearch: <?= json_encode($settings['restrict_to_search'] ?? null); ?>,
			bundlePreview: <?= caGetBundlePreviewForRelationshipBundle($this->getVar('initialValues')); ?>,
			readonly: <?= $read_only ? "true" : "false"; ?>,
			isSortable: <?= ($read_only || $sort) ? "false" : "true"; ?>,
			listSortOrderID: '<?= $id_prefix; ?>BundleList',
			listSortItems: 'div.roundedRel',
			
			itemColor: '<?= $color; ?>',
			firstItemColor: '<?= $first_color; ?>',
			lastItemColor: '<?= $last_color; ?>',
			
			totalValueCount: <?= (int)$count; ?>,
			partialLoadUrl: '<?= caNavUrl($this->request, '*', '*', 'loadBundleValues', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $vn_placement_id, 'bundle' => 'ca_sets')); ?>',
			partialLoadIndicator: '<?= addslashes(caBusyIndicatorIcon($this->request)); ?>',
			loadSize: <?= $num_per_page; ?>,

<?php if($quick_add_enabled) { ?>		
			quickaddPanel: caRelationQuickAddPanel<?= $id_prefix; ?>,
			quickaddUrl: '<?= caNavUrl($this->request, 'manage/sets', 'SetQuickAdd', 'Form', array('set_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)($settings['dont_include_subtypes_in_type_restriction'] ?? 0), 'prepopulate_fields' => join(";", $settings['prepopulateQuickaddFields'] ?? []), 'table_num' => $t_subject->tableNum())); ?>',
<?php } ?>	

			minRepeats: <?= caGetOption('minRelationshipsPerRow', $settings, 0); ?>,
			maxRepeats: <?= caGetOption('maxRelationshipsPerRow', $settings, 65535); ?>,
			templateValues: ['label', 'set_code', 'id'],
			relationshipTypes: {}
		});
	});
	
	function editSetItem(set_id, item_id) {
		let setItemEditorBaseUrl = '<?= caNavUrl($this->request, 'manage/set_items', 'SetItemEditor', 'Edit'); ?>'
    	var u = setItemEditorBaseUrl + '/set_id/' + set_id + '/item_id/' + item_id; 
    	console.log(caSetItemEditPanel<?= $id_prefix; ?>);
       	caSetItemEditPanel<?= $id_prefix; ?>.showPanel(u);
	}
</script>


<div id="caSetItemEditPanel<?= $id_prefix; ?>" class="caSetItemEditPanel"> 
	<div id="caSetItemEditPanel<?= $id_prefix; ?>ContentArea">
	    <div class='dialogHeader'><?= _t('Edit'); ?></div>
	</div>
</div>
