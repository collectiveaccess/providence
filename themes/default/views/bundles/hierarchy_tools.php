<?php
/* ----------------------------------------------------------------------
 * bundles/hierarchy_tools.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 
	AssetLoadManager::register('tabUI');
	
	$t_subject 			= $this->getVar('t_subject');
	$subject_label	= $t_subject->getLabelForDisplay();
	if (($priv_table = $t_subject->tableName()) == 'ca_list_items') { $priv_table = 'ca_lists'; }		// actions happen to be on names for ca_lists for ca_list_items
	
	$batch 			= $this->getVar('batch');
	$parent_id 		= $this->getVar('parent_id');
	$ancestors 		= $this->getVar('ancestors');
	$id 				= $this->getVar('id');
	$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
	$items_in_hier 	= $t_subject->getHierarchySize();
	$bundle_preview	= '('.$items_in_hier. ') '. caProcessTemplateForIDs("^preferred_labels", $t_subject->tableName(), array($t_subject->getPrimaryKey()));
	
	$bundle_settings = $this->getVar('settings');

	$num_per_page = caGetOption('numPerPage', $bundle_settings, 10);
	$errors = []
?>	

<?php
	if ($batch) {
		print caBatchEditorIntrinsicModeControl($t_subject, $id_prefix);
	} else {
		print caEditorBundleShowHideControl($this->request, $id_prefix, $bundle_settings, false, $bundle_preview);
	}
	print caEditorBundleMetadataDictionary($this->request, $id_prefix, $bundle_settings);
	
	$initial_values = $this->getVar('items');
	$total_count = $this->getVar('itemCount');
?>
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
		<div class="hierarchyTools">
			<b>Controls<br/>
			<div class="hierarchyToolsControlSetImage"><a href="#">Set image for album</a></div>
		</div>
		<div class="caItemList hierarchyTools"> </div>
		<input type="text" name="<?= $id_prefix; ?>_selection" id="<?= $id_prefix; ?>_selection" style="width: 670px"/>
	</div><!-- bundleContainer -->

	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo hierarchyToolsItem {selected}">
			<div class="hierarchyToolsItemSelect"><input type="checkbox" value="{n}" name="<?= $id_prefix; ?>_selection_{n}" id="<?= $id_prefix; ?>_selection_{n}"/></div>
			<div class="hierarchyToolsItemImage"><a href='{url}'>{media}</a></div>
			<div class="hierarchyToolsItemText"><a href='{url}'>{label}</a> ({idno})</div>
		</div>
	</textarea>
</div>
<script>
	jQuery(document).ready(function() {
		var caHierarchyToolsBundle<?= $id_prefix; ?>;
		caHierarchyToolsBundle<?= $id_prefix; ?> = caUI.initBundle('#<?= "{$id_prefix}"; ?>', {
			fieldNamePrefix: '<?= $id_prefix; ?>_',
			templateValues: ['id', 'label', 'idno', 'media', 'url', 'selected'],
			initialValues: <?= json_encode($initial_values); ?>,
			initialValueOrder: <?= json_encode(array_keys($initial_values)); ?>,
			errors: <?= json_encode($errors); ?>,
			itemID: '<?= $id_prefix; ?>Item_',
			templateClassName: 'caItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			itemClassName: 'labelInfo',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			placementID: <?= json_encode($settings['placement_id']); ?>,
			showEmptyFormsOnLoad: 1,
			readonly: <?= $read_only ? "true" : "false"; ?>,
			isSortable: <?= !$read_only && !$batch ? "true" : "false"; ?>,
			listSortOrderID: '<?= $id_prefix; ?>_ObjectRepresentationBundleList',
			defaultLocaleID: <?= ca_locales::getDefaultCataloguingLocaleID(); ?>,
			
			
			totalValueCount: <?= (int)$total_count; ?>,
			
			partialLoadUrl: '<?= caNavUrl($this->request, '*', '*', 'loadBundleValues', [$t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $bundle_settings['placement_id'], 'bundle' => 'hierarchy_tools']); ?>',
			loadSize: <?= (int)$num_per_page; ?>,
			partialLoadMessage: <?= json_encode(_t('Load next %num of %total')); ?>,
			partialLoadIndicator: <?= json_encode(caBusyIndicatorIcon($this->request)); ?>,
			onPartialLoad: function(d) {				
				// NOOP
			}
		
		});
		
		jQuery('#<?= $id_prefix; ?>').on('click', '.hierarchyToolsItemSelect input', function(e) {
			let selectedList = []
			jQuery('#<?= "{$id_prefix}"; ?>').find('.hierarchyToolsItemSelect').find('input').each(function(k, v) {
				if(jQuery(v).attr('checked')) { 
					selectedList.push(parseInt(v.id.match(/[\d]+$/)[0]));
				}
			});
			if(selectedList.length == 1) {
				jQuery('.hierarchyToolsControlSetImage').show();
			} else {
				jQuery('.hierarchyToolsControlSetImage').hide();
			}
			jQuery('#<?= $id_prefix; ?>_selection').val(selectedList.join(';'));
		});
	});
</script>
