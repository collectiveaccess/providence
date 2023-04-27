<?php
/* ----------------------------------------------------------------------
 * bundles/related_list.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2023 Whirl-i-Gig
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
 
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	/** @var BundlableLabelableBaseModelWithAttributes $t_item */
	$t_item 			= $this->getVar('t_item');			// related item
	/** @var BaseRelationshipModel $t_item_rel */
	$t_item_rel 		= $this->getVar('t_item_rel');
	$t_subject 			= $this->getVar('t_subject');
	$va_settings 		= $this->getVar('settings');
	$vs_add_label 		= $this->getVar('add_label');
	$va_rel_types		= $this->getVar('relationship_types');
	$vs_placement_code 	= $this->getVar('placement_code');
	/** @var BaseSearchResult $vo_result */
	$vo_result			= $this->getVar('result');
	$vn_placement_id	= (int)$va_settings['placement_id'];
	$vb_batch			= $this->getVar('batch');
	$t_display 			= $this->getVar('display');
	$va_display_list	= $this->getVar('display_list');
	$va_initial_values	= $this->getVar('initialValues');
	$vs_bundle_name		= $this->getVar('bundle_name');
	$vs_interstitial_selector = $vs_id_prefix . 'Item_';

	$va_ids = array();
	foreach($va_initial_values as $vn_rel_id => $va_rel_info) {
		if(array_search($va_rel_info['id'], $va_ids, true)) { continue; }
		$va_ids[$vn_rel_id] = $va_rel_info['id'];
	}

	$va_additional_search_controller_params = array(
		'ids' => json_encode($va_ids),
		'interstitialPrefix' => $vs_interstitial_selector,
		'relatedRelTable' => $t_item_rel->tableName(),
		'primaryTable' => $t_subject->tableName(),
		'primaryID' => $t_subject->getPrimaryKey(),
		'relatedTable' => $t_item->tableName(),
		'idPrefix' => $vs_id_prefix
	);

	$vs_url_string = '';
	foreach($va_additional_search_controller_params as $vs_key => $vs_val) {
		if ($vs_key == 'ids') { continue; }
		$vs_url_string .= '/' . $vs_key . '/' . urlencode($vs_val);
	}

	$vb_read_only		=	((isset($va_settings['readonly']) && $va_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), $vs_bundle_name) == __CA_BUNDLE_ACCESS_READONLY__));
	$vb_dont_show_del	=	((isset($va_settings['dontShowDeleteButton']) && $va_settings['dontShowDeleteButton'])) ? true : false;
	
	// params to pass during related item lookup
	$va_lookup_params = array(
		'type' => isset($va_settings['restrict_to_type']) ? $va_settings['restrict_to_type'] : '',
		'noSubtypes' => (int)$va_settings['dont_include_subtypes_in_type_restriction'],
		'noInline' => (bool) preg_match("/QuickAdd$/", $this->request->getController()) ? 1 : 0
	);
		
	if ($vb_batch) {
		print caBatchEditorRelationshipModeControl($t_item, $vs_id_prefix.$t_item->tableNum().'_rel');
	} else {
		print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $va_settings, caInitialValuesArrayHasValue($vs_id_prefix.$t_item->tableNum().'_rel', $this->getVar('initialValues')));
	}
	print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $va_settings);
	
	$va_errors = array();
	foreach($va_action_errors = $this->request->getActionErrors($vs_placement_code) as $o_error) {
		$va_errors[] = $o_error->getErrorDescription();
	}
?>
<script type="text/javascript">
	function caAsyncSearchResultForm<?= $vs_id_prefix; ?>(data) {
		let tableContent = jQuery('#tableContent<?= $vs_id_prefix; ?>');
		let bundle = jQuery('#<?= $vs_id_prefix.$t_item->tableNum().'_rel'; ?>');

		if(data) { tableContent.html(data); }

		// have to re-init the relation bundle because the interstitial buttons have only now been loaded
		caRelationBundle<?= $vs_id_prefix; ?> = caUI.initRelationBundle('#<?= $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', initiRelationBundleOptions<?= $vs_id_prefix; ?>);

		jQuery('#tableContent<?= $vs_id_prefix; ?> .list-header-unsorted a, #tableContent<?= $vs_id_prefix; ?> .list-header-sorted-desc a, #tableContent<?= $vs_id_prefix; ?> .list-header-sorted-asc a').click(function(event) {
			event.preventDefault();
			jQuery.post(event.target, <?= json_encode($va_additional_search_controller_params); ?>, caAsyncSearchResultForm<?= $vs_id_prefix; ?>);
		});

		tableContent.find('form').each(function() {
			jQuery(this).submit(function(event) {
				event.preventDefault();
				jQuery.ajax({
					type: 'POST',
					url: event.target.action + '<?= $vs_url_string; ?>',
					data: jQuery(this).serialize(),
					success: caAsyncSearchResultForm<?= $vs_id_prefix; ?>
				});
			});
		});
		bundle.find('.batchEditPanel').show();
	}

<?php
	if(sizeof($va_initial_values)) {
		// when ready, pull in the result list via the RelatedList search controller and the JS helper caAsyncSearchResultForm() above
?>
		jQuery(document).ready(function() {
			jQuery.post('<?= caNavUrl($this->request, 'find', 'RelatedList', 'Index', []); ?>', <?= json_encode($va_additional_search_controller_params); ?>, caAsyncSearchResultForm<?= $vs_id_prefix; ?>);
		});
<?php
	}
?>
</script>
<div id="<?= $vs_id_prefix.$t_item->tableNum().'_rel'; ?>" <?= $vb_batch ? "class='editorBatchBundleContent'" : ''; ?>>
	<div class='bundleSubLabel batchEditPanel'>
		<?= caEditorBundleBatchEditorControls($this->request, $vn_placement_id, $t_subject, $t_instance->tableName(), $va_settings); ?>
		
		<div class="button batchEdit batchEditSelected" id="batchEditSelected<?= $vs_id_prefix; ?>"><a href="#"><?= caNavIcon(__CA_NAV_ICON_BATCH_EDIT__, '15px')._t(' Batch edit selected'); ?></a></div>
	</div>
	<div id="tableContent<?= $vs_id_prefix; ?>" class="labelInfo relatedListTableContent">
		<?= sizeof($va_initial_values) ? caBusyIndicatorIcon($this->request).' '._t('Loading') : _t('No related %1', $t_item->getProperty('NAME_PLURAL')); ?>
	</div>
<?php
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?= $vs_id_prefix; ?>Item_{n}" class="labelInfo caRelatedItem">
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?= $vs_id_prefix; ?>_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
<?php
	if (sizeof($this->getVar('relationship_types_by_sub_type'))) {
?>
						<select name="<?= $vs_id_prefix; ?>_type_id{n}" id="<?= $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
<?php
	}
?>
						<input type="hidden" name="<?= $vs_id_prefix; ?>_id{n}" id="<?= $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
						
						<a href="<?= urldecode(caEditorUrl($this->request, $t_item->tableName(), '{'.$t_item->primaryKey().'}')); ?>" class="caEditItemButton" id="<?= $vs_id_prefix; ?>_edit_related_{n}"><?= caNavIcon(__CA_NAV_ICON_GO__, 1); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
	
	<div class="bundleContainerRelatedList">
		<div class="caItemList">
<?php
	if (sizeof($va_errors)) {
?>
		<span class="formLabelError"><?= join("; ", $va_errors); ?><br class="clear"/></span>
<?php
	}
?>
		
		</div>
		<input type="hidden" name="<?= $vs_id_prefix; ?>BundleList" id="<?= $vs_id_prefix; ?>BundleList" value=""/>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
<?php
	if (!$vb_read_only) {
?>	
		<div class='button labelInfo caAddItemButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $vs_add_label ? $vs_add_label : _t("Add relationship"); ?></a></div>
<?php
	}
?>
	</div>
</div>

<div id="caRelationQuickAddPanel<?= $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?= $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?= _t('Quick Add', $t_item->getProperty('NAME_SINGULAR')); ?></div>
		
	</div>
</div>
<div id="caRelationEditorPanel<?= $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationEditorPanel<?= $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?= _t('Relation editor', $t_item->getProperty('NAME_SINGULAR')); ?></div>
		
	</div>
	
	<textarea class='caBundleDisplayTemplate' style='display: none;'>
		<?= caGetRelationDisplayString($this->request, $t_item->tableName(), array(), array('display' => '_display', 'makeLink' => false)); ?>
	</textarea>
</div>			
	
<script type="text/javascript">
	var caRelationBundle<?= $vs_id_prefix; ?>;
	var initiRelationBundleOptions<?= $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caRelationQuickAddPanel<?= $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caRelationQuickAddPanel<?= $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRelationQuickAddPanel<?= $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
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
			caRelationEditorPanel<?= $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caRelationEditorPanel<?= $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRelationEditorPanel<?= $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
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

		initiRelationBundleOptions<?= $vs_id_prefix; ?> = {
			fieldNamePrefix: '<?= $vs_id_prefix; ?>_',
			initialValues: <?= json_encode($this->getVar('initialValues')); ?>,
			initialValueOrder: <?= json_encode(array_keys($this->getVar('initialValues'))); ?>,
			itemID: '<?= $vs_id_prefix; ?>Item_',
			placementID: '<?= $vn_placement_id; ?>',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			listItemClassName: 'caRelatedItem',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			hideOnNewIDList: ['<?= $vs_id_prefix; ?>_edit_related_'],
			showEmptyFormsOnLoad: 1,
			autocompleteUrl: '<?= $vs_navurl = caNavUrl($this->request, 'lookup', ucfirst(str_replace(" ", "", ucwords($t_item->getProperty('NAME_SINGULAR'), ' '))), 'Get', $va_lookup_params); ?>',
			types: <?= json_encode($va_settings['restrict_to_types']); ?>,
			restrictToAccessPoint: <?= json_encode($va_settings['restrict_to_access_point']); ?>,
			restrictToSearch: <?= json_encode($va_settings['restrict_to_search']); ?>,
			bundlePreview: <?= caGetBundlePreviewForRelationshipBundle($this->getVar('initialValues')); ?>,
			readonly: <?= $vb_read_only ? "true" : "false"; ?>,
			isSortable: false,

			quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
			quickaddUrl: '<?= caEditorUrl($this->request, $t_item->tableName(), null, false, null, array('quick_add' => true)); ?>',

			interstitialButtonClassName: 'caInterstitialEditButton',
			interstitialPanel: caRelationEditorPanel<?= $vs_id_prefix; ?>,
			interstitialUrl: '<?= caNavUrl($this->request, 'editor', 'Interstitial', 'Form', array('t' => $t_item_rel->tableName())); ?>',
			interstitialPrimaryTable: '<?= $t_instance->tableName(); ?>',
			interstitialPrimaryID: <?= (int)$t_instance->getPrimaryKey(); ?>,

			relationshipTypes: <?= json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			templateValues: ['label', 'id', 'type_id', , 'typename', 'idno_sort'],

			minRepeats: <?= caGetOption('minRelationshipsPerRow', $va_settings, 0); ?>,
			maxRepeats: <?= caGetOption('maxRelationshipsPerRow', $va_settings, 65535); ?>,
			isSelfRelationship:<?= ($t_item_rel && $t_item_rel->isSelfRelationship()) ? 'true' : 'false'; ?>,
			subjectTypeID: <?= (int)$t_subject->getTypeID(); ?>
		};

		// only init bundle if there are no values, otherwise we do it after the content is loaded
<?php
		if(!sizeof($va_initial_values)) {
?>
			caRelationBundle<?= $vs_id_prefix; ?> = caUI.initRelationBundle('#<?= $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', initiRelationBundleOptions<?= $vs_id_prefix; ?>);
<?php
		}
?>
		jQuery('#tableContent<?= $vs_id_prefix; ?>').on('click', 'input.addItemToBatchControl', function(e) {
			let ids = caGetSelectedItemIDsForRelatedList<?= $vs_id_prefix; ?>();
			let be = jQuery('#<?= $vs_id_prefix.$t_item->tableNum().'_rel'; ?>').find('.batchEditSelected');
			if(ids.length > 1) {
				be.show();
			} else {
				be.hide();
			}
		});
		jQuery('#batchEditSelected<?= $vs_id_prefix; ?>').on('click', function(e) {
			let ids = caGetSelectedItemIDsForRelatedList<?= $vs_id_prefix; ?>();
			if(ids.length > 0) {
				window.location = '<?= caNavUrl($this->request, '*', '*', 'BatchEdit', ['placement_id' => $vn_placement_id, 'primary_id' => $t_instance->getPrimaryKey(), 'screen' => $this->request->getActionExtra()]);?>/ids/' + ids.join(";");
			}
			e.preventDefault();
			return false;
		});
		function caGetSelectedItemIDsForRelatedList<?= $vs_id_prefix; ?>() {
			var ids = [];
			jQuery('#tableContent<?= $vs_id_prefix; ?>').find('input.addItemToBatchControl:checked').each(function(i, j) {
				if (jQuery(j).prop('checked')) {
					ids.push(jQuery(j).val());
				}
			});
			return ids;
		}
	});
</script>
