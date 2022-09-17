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
	if (!RequestHTTP::isAjax()) {
		if ($batch) {
			print caBatchEditorIntrinsicModeControl($t_subject, $id_prefix);
		} else {
			print caEditorBundleShowHideControl($this->request, $id_prefix, $bundle_settings, false, $bundle_preview);
		}
		print caEditorBundleMetadataDictionary($this->request, $id_prefix, $bundle_settings);
	}
	$initial_values = $this->getVar('items');
	$total_count = $this->getVar('itemCount');
?>
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
		<div class="hierarchyToolsMessage"></div>
		<div class="hierarchyTools">
			<div class="hierarchyToolsControlTransferItems button labelInfo">
				<?= _t('Move to'); ?>
				<input type="text" style="width: 100px;" name="<?= $id_prefix; ?>_transfer_autocomplete" value="" id="<?= $id_prefix; ?>_transfer_autocomplete" class="lookupBg"  placeholder=<?= json_encode(_t('Album name')); ?>/>
				<a href="#"><?= caNavIcon(__CA_NAV_ICON_MOVE__, '15px'); ?></a>
				<input type="hidden" name="<?= $id_prefix; ?>_transfer_id" id="<?= $id_prefix; ?>_transfer_id" value=""/>
			</div>
			<div class="hierarchyToolsControlCreateWithItems button labelInfo">
				<?= _t('Create'); ?>
				<input type="text" style="width: 100px;" name="<?= $id_prefix; ?>_create_with_name" value="" id="<?= $id_prefix; ?>_create_with_name" placeholder=<?= json_encode(_t('Album name')); ?>/>
				<a href="#"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?></a>
			</div>
			<div class="hierarchyToolsControlRemoveItems button labelInfo"><?= caNavIcon(__CA_NAV_ICON_DELETE__, '15px'); ?> <a href="#"><?= _t('Extract'); ?></a></div>
			<div class="hierarchyToolsControlSetImage button labelInfo"><?= caNavIcon(__CA_NAV_ICON_IMAGE__, '15px'); ?> <a href="#"><?= _t('Set image'); ?></a></div>
			<div class="hierarchyToolsControlDownloadMedia button labelInfo"><?= caNavIcon(__CA_NAV_ICON_DOWNLOAD__, '15px'); ?><a href="#"><?= _t('Download'); ?></a></div>
			
			<div class="hierarchyToolsControlSelect button labelInfo">
				<a href="#" class="hierarchyToolsControlSelectAll"><?= _t('all'); ?></a> / <a href="#" class="hierarchyToolsControlSelectNone"><?= _t('none'); ?></a>
			</div>
		</div>
		<br class="clear"/>
		<div class="caItemList hierarchyTools"> </div>
		<input type="hidden" name="<?= $id_prefix; ?>_selection" id="<?= $id_prefix; ?>_selection" style="width: 670px"/>
	</div><!-- bundleContainer -->

	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo hierarchyToolsItem {selected}">
			<div class="hierarchyToolsItemImage">{media}</div>
			<div class="hierarchyToolsItemSelect"><input type="checkbox" value="{n}" name="<?= $id_prefix; ?>_selection_{n}" id="<?= $id_prefix; ?>_selection_{n}"/></div>
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
			showEmptyFormsOnLoad: false,
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
			if(selectedList.length >= 1) {
				jQuery('.hierarchyToolsControlRemoveItems, .hierarchyToolsControlTransferItems, .hierarchyToolsControlCreateWithItems, .hierarchyToolsControlDownloadMedia').show();
			} else {
				jQuery('.hierarchyToolsControlRemoveItems, .hierarchyToolsControlTransferItems, .hierarchyToolsControlCreateWithItems, .hierarchyToolsControlDownloadMedia').hide();
			}
			jQuery('#<?= $id_prefix; ?>_selection').val(selectedList.join(';'));
		});
		
		// Transfer lookup
		jQuery('#<?= $id_prefix; ?>_transfer_autocomplete').autocomplete({
				source: '<?= caNavUrl($this->request, 'lookup', 'Object', 'Get', ['noInline' => 1, 'noSubtypes' => 1, 'root' => 1, 'types' => ['album']]); ?>',
				minLength: <?= (int)$t_subject->getAppConfig()->get(["ca_objects_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>, delay: 800, html: true,
				select: function(event, ui) {
					if (parseInt(ui.item.id) > 0) {
						jQuery('#<?= $id_prefix; ?>_transfer_id').val(parseInt(ui.item.id));
					}
					event.preventDefault();
					//jQuery('#<?= $id_prefix; ?>_transfer_autocomplete').val('');
				}
			}
		);
		
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlSetImage').on('click', function(e) {
			let id = jQuery('#<?= $id_prefix; ?>_selection').val().split(';')[0];
			jQuery.getJSON('<?= caNavUrl($this->request, 'editor', 'HierarchyTools', 'setRootMedia'); ?>/t/<?= $t_subject->tableName(); ?>', {id: id}, function(d) {
				if(d && d['ok'] && caBundleUpdateManager) { 
					caBundleUpdateManager.reloadInspector(); 
				} 
				let e = jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsMessage').html(d.message).slideDown(250);
				
				setTimeout(function() { 
					jQuery(e).slideUp(250);
				}, 5000);
			});
		});
		
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlRemoveItems').on('click', function(e) {
			let ids = jQuery('#<?= $id_prefix; ?>_selection').val().split(';');
			jQuery.getJSON('<?= caNavUrl($this->request, 'editor', 'HierarchyTools', 'removeItems'); ?>/t/<?= $t_subject->tableName(); ?>',{ids: ids}, function(d) {
				if(d && d['ok'] && caBundleUpdateManager) { 
					caBundleUpdateManager.reloadInspector(); 
					caBundleUpdateManager.reloadBundle('hierarchy_tools'); 
				} 
				
				setTimeout(function() { 
					jQuery(e).slideUp(250);
				}, 5000);
			});
		});
		
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlTransferItems').on('click', function(e) {
			let ids = jQuery('#<?= $id_prefix; ?>_selection').val().split(';');
			let transfer_id = jQuery('#<?= $id_prefix; ?>_transfer_id').val();
			jQuery.getJSON('<?= caNavUrl($this->request, 'editor', 'HierarchyTools', 'transferItems'); ?>/t/<?= $t_subject->tableName(); ?>',{id: transfer_id, ids: ids}, function(d) {
				if(d && d['ok'] && caBundleUpdateManager) { 
					caBundleUpdateManager.reloadInspector(); 
					caBundleUpdateManager.reloadBundle('hierarchy_tools'); 
				} 
				
				setTimeout(function() { 
					jQuery(e).slideUp(250);
				}, 5000);
			});
		});
		
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlCreateWithItems').on('click', function(e) {
			let ids = jQuery('#<?= $id_prefix; ?>_selection').val().split(';');
			let name = jQuery('#<?= $id_prefix; ?>_create_with_name').val();
			jQuery.getJSON('<?= caNavUrl($this->request, 'editor', 'HierarchyTools', 'createWith'); ?>/t/<?= $t_subject->tableName(); ?>',{name: name, ids: ids}, function(d) {
				if(d && d['ok'] && caBundleUpdateManager) { 
					caBundleUpdateManager.reloadInspector(); 
					caBundleUpdateManager.reloadBundle('hierarchy_tools'); 
				} 
				
				setTimeout(function() { 
					jQuery(e).slideUp(250);
				}, 5000);
			});
		});
		
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlDownloadMedia a').on('click', function(e) {
			let ids = jQuery('#<?= $id_prefix; ?>_selection').val().split(';');
			window.location.href = '<?= caNavUrl($this->request, 'editor', 'HierarchyTools', 'downloadMedia'); ?>/t/<?= $t_subject->tableName(); ?>/download/1/ids/' + ids.join(';');
		});
		
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlSelectAll').on('click', function(e) {
			jQuery('#<?= "{$id_prefix}"; ?>').find('.hierarchyToolsItemSelect').find('input').attr('checked', true);
			jQuery('.hierarchyToolsControlRemoveItems, .hierarchyToolsControlTransferItems, .hierarchyToolsControlCreateWithItems').show();
		});
		
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlSelectNone').on('click', function(e) {
			jQuery('#<?= "{$id_prefix}"; ?>').find('.hierarchyToolsItemSelect').find('input').attr('checked', false);
			jQuery('.hierarchyToolsControlRemoveItems, .hierarchyToolsControlTransferItems, .hierarchyToolsControlCreateWithItems').hide();
		});
<?php 
	if($total_count === 0) {
?>
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsMessage').html(<?= json_encode(_t('No %1 in hierarchy', $t_subject->getProperty('NAME_PLURAL'))); ?>).show();
<?php
	} else {
?>
		jQuery('#<?= $id_prefix; ?>').find('.hierarchyToolsControlSelect').show();
<?php
	}
?>

	});
</script>
