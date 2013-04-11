<?php
/* ----------------------------------------------------------------------
 * bundles/ca_set_items.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 
 	JavascriptLoadManager::register('setEditorUI');
 
	$vs_id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
	$va_items 				= $this->getVar('items');
	$t_set 					= $this->getVar('t_set');
	$vn_set_id 				= $t_set->getPrimaryKey();
	$t_row 					= $this->getVar('t_row');
	$vs_type_singular 		= $this->getVar('type_singular');
	$vs_type_plural 		= $this->getVar('type_plural');
	$va_lookup_urls 		= $this->getVar('lookup_urls');
	$vn_table_num 			= $t_set->get('table_num');
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.'setItemEditor');
?>
<div id="<?php print $vs_id_prefix; ?>setItemEditor" class='setItemEditor'>
<?php
	if (!$vn_table_num) {
?>
		<div id='<?php print $vs_id_prefix; ?>setNoItemsWarning'>
			<?php
					print "<strong>"._t('You must save this set before you can add items to it.')."</strong>";
			?>
		</div>
<?php
	} else {
?>
	<div class="caItemListSortControls">
		<?php print _t('Sort by'); ?>:<br/>
		<a href="#" onclick="setEditorOps.sort('name'); return false;"><?php print _t('name'); ?></a>&nbsp;&nbsp;
		<a href="#" onclick="setEditorOps.sort('idno'); return false;"><?php print _t('identifier'); ?></a>
	</div>
	<div id="<?php print $vs_id_prefix; ?>setItems" class="setItems">
		<div class="setEditorAddItemForm" id="<?php print $vs_id_prefix; ?>addItemForm">
			<?php print _t('Add %1', $vs_type_singular).': '; ?>
			<input type="text" size="70" name="setItemAutocompleter" id="<?php print $vs_id_prefix; ?>setItemAutocompleter" class="lookupBg"/>
		</div>

		<ul id="<?php print $vs_id_prefix; ?>setItemList" class="setItemList">

		</ul>
		<br style="clear: both;"/>
		<input type="hidden" id="<?php print $vs_id_prefix; ?>setRowIDList" name="<?php print $vs_id_prefix; ?>setRowIDList" value=""/>
			
		<script type="text/javascript">
			var setEditorOps = null;
			jQuery(document).ready(function() {
				setEditorOps = caUI.seteditor({
					setID: <?php print (int)$vn_set_id; ?>,
					table_num: <?php print (int)$t_set->get('table_num'); ?>,
					fieldNamePrefix: '<?php print $vs_id_prefix; ?>',
					initialValues: <?php print json_encode($va_items); ?>,
					initialValueOrder: <?php print json_encode(array_keys($va_items)); ?>,
					setItemAutocompleteID: '<?php print $vs_id_prefix; ?>setItemAutocompleter',
					rowIDListID: '<?php print $vs_id_prefix; ?>setRowIDList',
					
					editSetItemButton: '<?php print addslashes(caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__)); ?>',
					
					lookupURL: '<?php print $va_lookup_urls['search']; ?>',
					itemInfoURL: '<?php print caNavUrl($this->request, 'manage/sets', 'SetEditor', 'GetItemInfo'); ?>',
					editSetItemsURL: '<?php print caNavUrl($this->request, 'manage/set_items', 'SetItemEditor', 'Edit', array('set_id' => $vn_set_id)); ?>',
					editSetItemToolTip: '<?php print _t("Edit set item metadata"); ?>'
				});
			});
		</script>
	</div>
<?php
	}
?>
</div>