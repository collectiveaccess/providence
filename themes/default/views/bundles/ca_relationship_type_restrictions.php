<?php
/* ----------------------------------------------------------------------
 * bundles/ca_relationship_type_restrictions.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
$element 			= $this->getVar('type_restrictions');

$errors = array();
if(is_array($action_errors = $this->getVar('errors'))) {
	foreach($action_errors as $o_error) {
		$errors[] = $o_error->getErrorDescription();
	}
}

$left_select = $this->getVar('left_select');
$right_select = $this->getVar('right_select');
$left_label = $this->getVar('left_label');
$right_label = $this->getVar('right_label');
$initial_values = $this->getVar('type_restrictions');

print caEditorBundleShowHideControl($this->request, $id_prefix);
?>
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">	
<?php
				if (is_array($errors) && sizeof($errors)) {
?>
					<span class="formLabelError"><?= join('; ', $errors); ?></span>
<?php
				}
?>
				<div id="<?= $id_prefix; ?>_type_restrictions">
					<textarea class='caItemTemplate' style='display: none;'>
						<div id="Item_{n}" class="labelInfo">
							<span class="formLabelError">{error}</span>
							
							<table style="width: 100%;">
								<tr valign="top"> 
									<td><?= "{$left_label}: {$left_select}"; ?>
									<?= "{$right_label}: {$right_select}"; ?></td>
									<td style="align: right;">
										<a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
									</td>
								</tr>
							</table>
						</div>
					</textarea>
					<div class="bundleContainer">
						<div class="caItemList">
						
						</div>
						<div class='button labelInfo caAddItemButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, 1); ?> <?= _t("Add type restriction"); ?> &rsaquo;</a></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	var caTypeOptions = <?= json_encode($this->getVar('type_list')); ?>;
		caUI.initBundle('#<?= $id_prefix; ?>_type_restrictions', {
			fieldNamePrefix: '<?= $id_prefix; ?>',
			templateValues: <?= json_encode(['type_id', 'sub_type_left_id', 'sub_type_right_id', 'include_subtypes_left', 'include_subtypes_right']); ?>, 
			initialValues: <?= json_encode($initial_values); ?>,
			itemID: 'Item_',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 0
		});
</script>
