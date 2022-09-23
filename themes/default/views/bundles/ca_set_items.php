<?php
/* ----------------------------------------------------------------------
 * bundles/ca_set_items.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2022 Whirl-i-Gig
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
 
AssetLoadManager::register('setEditorUI');

$vs_id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
$va_items 				= caSanitizeArray($this->getVar('items'), ['removeNonCharacterData' => false]);
$t_set 					= $this->getVar('t_set');
$vn_set_id 				= $t_set->getPrimaryKey();
$t_row 					= $this->getVar('t_row');
$vs_type_singular 		= $this->getVar('type_singular');
$vs_type_plural 		= $this->getVar('type_plural');
$va_lookup_urls 		= $this->getVar('lookup_urls');
$va_settings			= $this->getVar('settings');
$vn_table_num 			= $t_set->get('table_num');

print caEditorBundleShowHideControl($this->request, $vs_id_prefix.'setItemEditor');
print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix.'setItemEditor', $va_settings);

if(caGetOption('showCount', $va_settings, false)) { print ($count = sizeof($items)) ? "({$count})" : ''; }

?>
<div id="<?= $vs_id_prefix; ?>" class='setItemEditor'>
<?php
	if (!$vn_table_num) {
?>
		<div id='<?= $vs_id_prefix; ?>setNoItemsWarning'>
			<?php
					print "<strong>"._t('You must save this set before you can add items to it.')."</strong>";
			?>
		</div>
<?php
	} else {
		print "<div class='bundleSubLabel'>";
		if(is_array($va_items) && sizeof($va_items)) {
			print caGetPrintFormatsListAsHTMLForSetItemBundles($vs_id_prefix, $this->request, $t_set, $t_set->getItemRowIDs());
		}
?>
    <div class="caItemListSortControls">
		<?= _t('Sort by'); ?>:
		<a href="#" onclick="setEditorOps.sort('name'); return false;"><?= _t('name'); ?></a>&nbsp;&nbsp;
		<a href="#" onclick="setEditorOps.sort('idno'); return false;"><?= _t('identifier'); ?></a>
	</div>
<?php
	print "<div style='clear:both;'></div></div><!-- end bundleSubLabel -->";
	
?>
	
	<div id="<?= $vs_id_prefix; ?>setItems" class="setItems">
		<div class="setEditorAddItemForm" id="<?= $vs_id_prefix; ?>addItemForm">
			<?= _t('Add %1', $vs_type_singular).': '; ?>
			<input type="text" size="70" name="setItemAutocompleter" id="<?= $vs_id_prefix; ?>setItemAutocompleter" class="lookupBg"/>
		</div>

		<ul id="<?= $vs_id_prefix; ?>setItemList" class="setItemList">

		</ul>
		<br style="clear: both;"/>
		<input type="hidden" id="<?= $vs_id_prefix; ?>setRowIDList" name="<?= $vs_id_prefix; ?>setRowIDList" value=""/>
			
		<script type="text/javascript">
			var setEditorOps = null;
			jQuery(document).ready(function() {
				setEditorOps = caUI.seteditor({
					setID: <?= (int)$vn_set_id; ?>,
					table_num: <?= (int)$t_set->get('table_num'); ?>,
					fieldNamePrefix: '<?= $vs_id_prefix; ?>',
					initialValues: <?= json_encode($va_items); ?>,
					initialValueOrder: <?= json_encode(array_keys($va_items)); ?>,
					setItemAutocompleteID: '<?= $vs_id_prefix; ?>setItemAutocompleter',
					rowIDListID: '<?= $vs_id_prefix; ?>setRowIDList',
					displayTemplate: <?= (isset($va_settings['displayTemplate']) ? json_encode($va_settings['displayTemplate']) : 'null'); ?>,
					
					editSetItemButton: '<?= addslashes(caNavIcon(__CA_NAV_ICON_EDIT__, "20px")); ?>',
					deleteSetItemButton: '<?= addslashes(caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, "20px")); ?>',
					
					lookupURL: '<?= $va_lookup_urls['search']; ?>',
					itemInfoURL: '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'GetItemInfo'); ?>',
					editSetItemsURL: '<?= caNavUrl($this->request, 'manage/set_items', 'SetItemEditor', 'Edit', array('set_id' => $vn_set_id)); ?>',
					editSetItemToolTip: '<?= _t("Edit set item metadata"); ?>'
				});
			});
		</script>
	</div>
<?php
	}
?>
</div>
