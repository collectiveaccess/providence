<?php
/* ----------------------------------------------------------------------
 * bundles/ca_set_items.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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

$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
$items 				= caSanitizeArray($this->getVar('items'), ['removeNonCharacterData' => false]);
$t_set 				= $this->getVar('t_set');
$set_id 			= $t_set->getPrimaryKey();
$t_row 				= $this->getVar('t_row');
$type_singular 		= $this->getVar('type_singular');
$type_plural 		= $this->getVar('type_plural');
$lookup_urls 		= $this->getVar('lookup_urls');
$settings			= $this->getVar('settings');
$table_num 			= $t_set->get('table_num');

print caEditorBundleShowHideControl($this->request, $id_prefix.'setItemEditor');
print caEditorBundleMetadataDictionary($this->request, $id_prefix.'setItemEditor', $settings);

if(caGetOption('showCount', $settings, false)) { print ($count = sizeof($items)) ? "({$count})" : ''; }
?>
<div id="<?= $id_prefix; ?>" class='setItemEditor'>
<?php
	if (!$table_num) {
?>
		<div id='<?= $id_prefix; ?>setNoItemsWarning'>
			<?php
					print "<strong>"._t('You must save this set before you can add items to it.')."</strong>";
			?>
		</div>
<?php
	} else {
		print "<div class='bundleSubLabel'>";
		if(is_array($items) && sizeof($items)) {
			print "<div style='float:right; '>".caEditorPrintSetItemsControls($this)."</div>";
		}
		
?>
   <div class="caItemListSortControls">
		<?= _t('Sort by'); ?>:
		<a href="#" onclick="setEditorOps.sort('name'); return false;"><?= _t('name'); ?></a>&nbsp;&nbsp;
		<a href="#" onclick="setEditorOps.sort('idno'); return false;"><?= _t('identifier'); ?></a>
	</div>
<?php
	print "<div style='clear:both;'></div></div><!-- end bundleSubLabel -->";
	
	$qr = $t_set->getItemsAsSearchResult();
	$count = $qr ? $qr->numHits() : 0;
	
	$found  = $not_found = $in_part = $not_checked = 0;
	
	if($qr && ($count > 0)) {
		while($qr->nextHit()) {
			switch($qr->get('ca_set_items.inventory_cont.found_object')) {
				case 13654:
					$found++;
					break;
				case 13655:
					$not_found++;
					break;
				case 13656:
					$in_part++;
					break;
				default:
					$not_checked++;
					break;
			}
		}
?>
	<div class='bundleSubLabel'>
		<h3><?= _t('Found: %1 (%2%)', $found, sprintf("%3.1f", $found/$count * 100));?> - <?= _t('Not found: %1 (%2%)', $not_found, sprintf("%3.1f", $not_found/$count * 100));?> - <?= _t('Found in part: %1 (%2%)', $in_part, sprintf("%3.1f", $in_part/$count * 100));?> - <?= _t('Not checked: %1 (%2%)', $not_checked, sprintf("%3.1f", $not_checked/$count * 100));?></h3>
	</div>
<?php
	}
	//print 'zzz='.$t_set->getWithTemplate($settings['summaryTemplate']);
?>
	<div id="<?= $id_prefix; ?>setItems" class="setItems">
		<div class="setEditorAddItemForm" id="<?= $id_prefix; ?>addItemForm">
			<?= _t('Add %1', $type_singular).': '; ?>
			<input type="text" size="70" name="setItemAutocompleter" id="<?= $id_prefix; ?>setItemAutocompleter" class="lookupBg"/>
		</div>

		<ul id="<?= $id_prefix; ?>setItemList" class="setItemList">

		</ul>
		<br style="clear: both;"/>
		<input type="hidden" id="<?= $id_prefix; ?>setRowIDList" name="<?= $id_prefix; ?>setRowIDList" value=""/>
			
		<script type="text/javascript">
			var setEditorOps = null;
			jQuery(document).ready(function() {
				var caSetItemEditPanel<?= $id_prefix; ?>;
				
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
								//caBundleUpdateManager.reloadBundle('<?= $bundle_name; ?>'); 
								caBundleUpdateManager.reloadInspector(); 
							}
						}
					});
				}
				setEditorOps = caUI.seteditor({
					setID: <?= (int)$set_id; ?>,
					table_num: <?= (int)$t_set->get('table_num'); ?>,
					fieldNamePrefix: '<?= $id_prefix; ?>',
					initialValues: <?= json_encode($items); ?>,
					initialValueOrder: <?= json_encode(array_keys($items)); ?>,
					setItemAutocompleteID: '<?= $id_prefix; ?>setItemAutocompleter',
					rowIDListID: '<?= $id_prefix; ?>setRowIDList',
					displayTemplate: <?= (isset($settings['displayTemplate']) ? json_encode($settings['displayTemplate']) : 'null'); ?>,
					
					editSetItemButton: '<?= addslashes(caNavIcon(__CA_NAV_ICON_EXPORT_SMALL__, "20px")); ?>',
					editSetItemToolTip: <?= json_encode(_t('Edit full set item')); ?>,
					deleteSetItemButton: '<?= addslashes(caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, "20px")); ?>',
					
					editSetItemInlineButton: '<?= addslashes(caNavIcon(__CA_NAV_ICON_EDIT__, "20px")); ?>',
					editSetItemInlineToolTip: <?= json_encode(_t('Edit set item inline')); ?>,
					
					lookupURL: '<?= $lookup_urls['search']; ?>',
					itemInfoURL: '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'GetItemInfo'); ?>',
					editSetItemsURL: '<?= caNavUrl($this->request, 'manage/set_items', 'SetItemEditor', 'Edit', array('set_id' => $set_id)); ?>',
					editSetItemToolTip: '<?= _t("Edit set item metadata"); ?>',
					
					setItemEditorPanel: caSetItemEditPanel<?= $id_prefix; ?>,
					setItemEditorBaseUrl: <?= json_encode(caNavUrl($this->request, '*', 'SetItem', 'Form')); ?>
				});
			});
		</script>
	</div>
<?php
	}
?>
</div>
<div id="caSetItemEditPanel<?= $id_prefix; ?>" class="caSetItemEditPanel"> 
	<div id="caSetItemEditPanel<?= $id_prefix; ?>ContentArea">
	    <div class='dialogHeader'><?= _t('Edit'); ?></div>
	</div>
</div>
