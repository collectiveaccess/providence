<?php
/* ----------------------------------------------------------------------
 * manage/user_sort_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

$vn_sort_id = $this->getVar('sort_id');
/** @var ca_user_sorts $t_sort */
$t_sort = $this->getVar('t_sort');
$va_sort_element_list = $this->getVar('sort_element_list');
$va_sort_bundle_names = $this->getVar('sort_bundle_names');

$vs_select_1_val = $vs_select_2_val = $vs_select_3_val = null;
if(is_array($va_sort_bundle_names) && (sizeof($va_sort_bundle_names)>0)) {
	$vs_select_1_val = array_shift($va_sort_bundle_names);
}
if(is_array($va_sort_bundle_names) && (sizeof($va_sort_bundle_names)>0)) {
	$vs_select_2_val = array_shift($va_sort_bundle_names);
}
if(is_array($va_sort_bundle_names) && (sizeof($va_sort_bundle_names)>0)) {
	$vs_select_3_val = array_shift($va_sort_bundle_names);
}

?>


<div id="caTypeChangePanelControlButtons">
	<table>
		<tr>
			<td align="right"><?php print caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'caUserSortForm'); ?></td>
			<td align="left"><?php print caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caChangeTypeFormCancelButton', array('onclick' => 'caTypeChangePanel.hidePanel(); return false;'), array()); ?></td>
		</tr>
	</table>
</div>
<?php print caFormTag($this->request, 'Save', 'caUserSortForm'); ?>

	<?php print $t_sort->htmlFormElement('name') ?>
	<?php if(!$vn_sort_id) { print $t_sort->htmlFormElement('table_num'); } ?>


	<table>
		<tr>
			<td><?php print _t("Sort field %1", 1); ?></td>
			<td><?php print caHTMLSelect('sort_item_1', $va_sort_element_list, ['style' => 'width: 370px'], array('value' => $vs_select_1_val, 'contentArrayUsesKeysForValues' => true)); ?></td>
		</tr>

		<tr>
			<td><?php print _t("Sort field %1", 2); ?></td>
			<td><?php print caHTMLSelect('sort_item_2', $va_sort_element_list, ['style' => 'width: 370px'], array('value' => $vs_select_2_val, 'contentArrayUsesKeysForValues' => true)); ?></td>
		</tr>
		<tr>
			<td><?php print _t("Sort field %1", 3); ?></td>
			<td><?php print caHTMLSelect('sort_item_3', $va_sort_element_list, ['style' => 'width: 370px'], array('value' => $vs_select_3_val, 'contentArrayUsesKeysForValues' => true)); ?></td>
		</tr>
	</table>
	<?php print caHTMLHiddenInput('sort_id', array('value' => $vn_sort_id)); ?>
</form>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('select#table_num').change(function() {
			var table_num = jQuery(this).val();
			jQuery.getJSON(
				'<?php print caNavUrl($this->request, 'manage', 'UserSort', 'GetBundlesForTable');?>/table_num/' + table_num,
				function(data) {
					var item1 = jQuery('select[name=sort_item_1]');
					var item2 = jQuery('select[name=sort_item_2]');
					var item3 = jQuery('select[name=sort_item_3]');
					item1.empty();
					item2.empty();
					item3.empty();
					jQuery.each(data, function(key, value) {
						item1.append(jQuery('<option></option>').attr('value', String(key)).text(value));
						item2.append(jQuery('<option></option>').attr('value', String(key)).text(value));
						item3.append(jQuery('<option></option>').attr('value', String(key)).text(value));
					});
				}
			);
		});
	});
</script>
