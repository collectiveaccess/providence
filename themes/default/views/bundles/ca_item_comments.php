<?php
/* ----------------------------------------------------------------------
 * bundles/ca_item_comments.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2022 Whirl-i-Gig
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
$t_subject 			= $this->getVar('t_subject');
$settings 			= $this->getVar('settings');
$vs_add_label 		= $this->getVar('add_label');
$vs_placement_code 	= $this->getVar('placement_code');
$vn_placement_id	= (int)$settings['placement_id'];
$vb_batch			= $this->getVar('batch');

$t_item = new ca_item_comments();

$vs_sort			=	((isset($settings['sort']) && $settings['sort'])) ? $settings['sort'] : '';
$vb_read_only		=	((isset($settings['readonly']) && $settings['readonly']));
$vb_dont_show_del	=	((isset($settings['dontShowDeleteButton']) && $settings['dontShowDeleteButton'])) ? true : false;

$vs_color 			= 	((isset($settings['colorItem']) && $settings['colorItem'])) ? $settings['colorItem'] : '';
$vs_first_color 	= 	((isset($settings['colorFirstItem']) && $settings['colorFirstItem'])) ? $settings['colorFirstItem'] : '';
$vs_last_color 		= 	((isset($settings['colorLastItem']) && $settings['colorLastItem'])) ? $settings['colorLastItem'] : '';

if ($vb_batch) {
	print caBatchEditorRelationshipModeControl($t_item, $vs_id_prefix);
} else {
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $settings, caInitialValuesArrayHasValue($vs_id_prefix.$t_item->tableNum().'_rel', $this->getVar('initialValues')));
}
print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $settings);


$va_errors = array();
foreach($va_action_errors = $this->request->getActionErrors($vs_placement_code) as $o_error) {
	$va_errors[] = $o_error->getErrorDescription();
}
?>
<div id="<?= $vs_id_prefix.$t_item->tableNum().'_rel'; ?>" <?= $vb_batch ? "class='editorBatchBundleContent'" : ''; ?>>
    <div class='bundleSubLabel'>
        <div style='clear:both;'></div></div><!-- end bundleSubLabel -->
<?php
	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $vs_id_prefix; ?>Item_{n}" class="labelInfo listRel caRelatedItem">
<?php
	if (!$vb_read_only && !$vb_dont_show_del) {
?>				
			<a href="#" class="caDeleteItemButton listRelDeleteButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
<?php
	}
?>
			<span id='<?= $vs_id_prefix; ?>_BundleTemplateDisplay{n}'>
			    {{created_on_display}} by {{name}} ({{email}})
                
                <blockquote>{{comment}}</blockquote>
				
				{{moderation_message}}
			</span>
			<input type="hidden" name="<?= $vs_id_prefix; ?>_id{n}" id="<?= $vs_id_prefix; ?>_comment_id{n}" value="{comment_id}"/>
		</div>

	</textarea>

	<div class="bundleContainer">
		<div class="caItemList">
<?php
	if (sizeof($va_errors)) {
?>
		    <span class="formLabelError"><?= join("; ", $va_errors); ?><br class="clear"/></span>
<?php
	}
?>
		</div>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
	</div>
</div>

<script type="text/javascript">
	var caRelationBundle<?= $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
		jQuery('#<?= $vs_id_prefix; ?>caItemListSortControlTrigger').click(function() { jQuery('#<?= $vs_id_prefix; ?>caItemListSortControls').slideToggle(200); return false; });
		jQuery('#<?= $vs_id_prefix; ?>caItemListSortControls a.caItemListSortControl').click(function() {jQuery('#<?= $vs_id_prefix; ?>caItemListSortControls').slideUp(200); return false; });
		
		caRelationBundle<?= $vs_id_prefix; ?> = caUI.initRelationBundle('#<?= $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?= $vs_id_prefix; ?>_',
			initialValues: <?= json_encode($this->getVar('initialValues')); ?>,
			initialValueOrder: <?= json_encode(array_keys($this->getVar('initialValues'))); ?>,
			itemID: '<?= $vs_id_prefix; ?>Item_',
			placementID: '<?= $vn_placement_id; ?>',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			listItemClassName: 'caRelatedItem',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 0,
			returnTextValues: true,
			restrictToAccessPoint: <?= json_encode($settings['restrict_to_access_point'] ?? null); ?>,
			restrictToSearch: <?= json_encode($settings['restrict_to_search'] ?? null); ?>,
			bundlePreview: <?= caGetBundlePreviewForRelationshipBundle($this->getVar('initialValues')); ?>,
			readonly: <?= $vb_read_only ? "true" : "false"; ?>,
			isSortable: false,

			templateValues: ['comment', 'id', 'access', 'moderation_message', 'rank', 'created_on', 'created_on_display', 'fname', 'lname', 'email']
		});
	});
</script>
