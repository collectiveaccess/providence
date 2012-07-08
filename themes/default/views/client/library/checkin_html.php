<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/library/checkin_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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

	$vs_id_prefix = 'caClientLibraryCheckin';
	$t_order_item = new ca_commerce_order_items();
	$va_initial_values = array();
?>
<div class="sectionBox">
<?php
print $vs_control_box = caFormControlBox(
		(caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Commit"), 'caClientLibraryCheckinForm')).' '.
		(caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'client/library', 'CheckIn', 'Save', array())),
		'',
		''
	);
	
	print caFormTag($this->request, 'Save', 'caClientLibraryCheckinForm', null, 'post', 'multipart/form-data', '_top', array());

	print _t('Item: ').caHTMLTextInput('search', array('value' => '', 'width' => '100px', 'id' => 'caCheckInObjectSearch'));
	print " <a href='#' id='caCheckInButton'>"._t('Check in')."</a>";
?>

		<div id="<?php print $vs_id_prefix.'_item'; ?>">
		
<?php
	//
	// Template to generate controls for creating new item
	//
?>
			<textarea class='caNewItemTemplate' style='display: none;'>
				<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
					<a href="#" class="caDeleteItemButton" style="float: right;"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
					<table class="caListItem" width="95%">
						<tr valign='top'>
							<td>
								<strong>{object}</strong> [{idno}]
								<br/>
								{order_number}/{item_id}
								<br/>
								<?php print _t('Borrowed by: %1', '{user}'); ?>
								<br/>
								<?php print _t('Due: %1', '{loan_due_date}'); ?>
							</td>
							<td>
								<?php print str_replace('textarea', 'textentry', $t_order_item->htmlFormElement('notes', null, array('value' => '{notes}', 'name' => $vs_id_prefix.'_notes_{n}', 'id' => $vs_id_prefix.'_notes_{n}', 'width' => '160px', 'height' => '40px'))); ?>
							</td>
							<td>
								{thumbnail_tag}
							</td>
						</tr>
					</table>
				</div>
				<input type="hidden" name="<?php print $vs_id_prefix; ?>_item_id_{n}" id="<?php print $vs_id_prefix; ?>_item_id_{n}" value="{item_id}" class="caCheckoutItemID"/>
			</textarea>
			<div class="bundleContainer">
				<div class="caItemList">
				
				</div>
				<input type="hidden" name="<?php print $vs_id_prefix; ?>BundleList" id="<?php print $vs_id_prefix; ?>BundleList" value=""/>
			</div>
		</div>
	</form>
</div>
<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	var caRelationBundle<?php print $vs_id_prefix; ?>;
	
	jQuery(document).ready(function() {
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix.'_item'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['_display', 'id', 'object_id', 'object', 'item_id', 'idno', 'loan_checkout_date', 'loan_due_date', 'fee', 'tax', 'notes', 'restrictions', 'thumbnail_tag'],
			initialValues: <?php print json_encode($va_initial_values); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caNewItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 0,
			autocompleteUrl: null,
			isSortable: false,
			listSortOrderID: '<?php print $vs_id_prefix; ?>BundleList',
			listSortItems: 'div.sortableOrderItem',
			onItemCreate: function() {
				jQuery('#<?php print $vs_id_prefix.'_item'; ?> .dateBg').datepicker();
			}
		});
				
		jQuery('#caCheckInButton').click(function() {
			jQuery.getJSON('<?php print caNavUrl($this->request, 'client/library', 'CheckIn', 'getItemInfo'); ?>', { search: jQuery('#caCheckInObjectSearch').val()}, function(d) {
				if(!d['item_id']) { return false; }
				if (jQuery('#<?php print $vs_id_prefix; ?>Item_' + d['item_id']).length > 0) { return false; }	// don't add the same thing twice
				caRelationBundle<?php print $vs_id_prefix; ?>.addToBundle(d['item_id'], d);
			});
		});
	});
</script>