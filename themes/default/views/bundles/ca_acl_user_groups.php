<?php
/* ----------------------------------------------------------------------
 * bundles/ca_acl_user_groups.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2025 Whirl-i-Gig
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
$id_prefix 		= $this->getVar('id_prefix').'_group';
$t_instance 	= $this->getVar('t_instance');
$t_item 		= $this->getVar('t_group');
$t_subject 		= $this->getVar('t_subject');		
$settings 		= $this->getVar('settings');
$add_label 		= $this->getVar('add_label') ?? _t("Add group exception");

$pawtucket_only_acl_enabled 	= caACLIsEnabled($t_instance, ['forPawtucketOnly' => true]);

$read_only = false;

$t_acl = new ca_acl();

$initial_values = $this->getVar('initialValues');
if (!is_array($initial_values)) { $initial_values = []; }
?>
<div id="<?= $id_prefix.$t_item->tableNum().'_rel'; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo">
			<table class="caListItem">
				<tr>
					<td class="formLabel">
						<input type="text" size="60" name="<?= $id_prefix; ?>_autocomplete{n}" value="{{label}}" id="<?= $id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
						<?= $t_acl->htmlFormElement('access', '^ELEMENT', ['name' => "{$id_prefix}_access_{n}", 'id' => "{$id_prefix}_access_{n}", 'value' => '{{access}}', 'no_tooltips' => true, 'forPawtucket' => $pawtucket_only_acl_enabled]); ?>
						<input type="hidden" name="<?= $id_prefix; ?>_id{n}" id="<?= $id_prefix; ?>_id{n}" value="{id}"/>
						<div class="inheritName">{inheritance_link}</div>
					</td>
					<td>
<?php
	if (!$read_only) {
?>	
		<a href="#" class="caDeleteItemButton" aria-label='<?= htmlspecialchars(_t('Delete')); ?>'><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
<?php
	}
?>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class="control">
			<?= _t('Groups'); ?> 	
		</div>
		<div class="caItemList">
		
		</div>
		<div class="control">
<?php
	if (!$read_only) {
?>	
		<div class='button labelInfo caAddItemButton' aria-label='<?= htmlspecialchars($add_label); ?>'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px').' '.$add_label; ?></a></div>
<?php
	}
?>	
		</div>
	</div>
</div>
			
<script type="text/javascript">
	jQuery(document).ready(function() {
		caUI.initRelationBundle('#<?= $id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?= $id_prefix; ?>_',
			templateValues: ['label', 'effective_date', 'access', 'id', 'inheritance_link'],
			initialValues: <?= json_encode($initial_values); ?>,
			initialValueOrder: <?= json_encode(array_keys($initial_values)); ?>,
			itemID: '<?= $id_prefix; ?>Item_',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 0,
			readonly: <?= $read_only ? "true" : "false"; ?>,
			autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'UserGroup', 'Get', array()); ?>'
		});
	});
</script>
