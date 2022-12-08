<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/ca_metadata_dictionary_rules.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 
	AssetLoadManager::register('sortableUI');

	$vs_id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_entry 				= $this->getVar('t_entry');	
	$t_rule					= $this->getVar('t_rule');
	
	$settings_values_list	= $this->getVar('settings_values_list');
	$settings_tags			= $this->getVar('settings_tags');

	$va_initial_values = $this->getVar('rules');	// list of existing rules
	if(!is_array($va_initial_values)) { $va_initial_values = []; }
	$va_errors = $va_failed_inserts = [];
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
	print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix, $va_settings);
 ?>
 <div id="<?= $vs_id_prefix; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
			<table class="uiScreenItem">
				<tr>
					<td>
						<div class="formLabel" id="{fieldNamePrefix}edit_name_{n}" style="display: block;">
							<table>
								<tr>
									<td><?= $t_rule->htmlFormElement('rule_code', "^LABEL<br/>^ELEMENT", array_merge([], array('name' => "{fieldNamePrefix}rule_code_{n}", 'id' => "{fieldNamePrefix}rule_code_{n}", "value" => "{{rule_code}}", 'no_tooltips' => true, 'readonly' => $vb_read_only))); ?></td>
									<td><?= $t_rule->htmlFormElement('rule_level', "^LABEL<br/>^ELEMENT", array_merge([], array('name' => "{fieldNamePrefix}rule_level_{n}", 'id' => "{fieldNamePrefix}rule_level_{n}", "value" => "{{rule_level}}", 'no_tooltips' => true, 'readonly' => $vb_read_only))); ?></td>
								</tr>
								<tr>
									<td colspan="2">
										<?= $t_rule->htmlFormElement('expression', "^LABEL<br/>^ELEMENT", array_merge([], array('name' => "{fieldNamePrefix}expression_{n}", 'id' => "{fieldNamePrefix}expression_{n}", "value" => "{{expression}}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry', 'readonly' => $vb_read_only))); ?>
									</td>
								</tr>
							</table>
							<?= str_replace("textarea", "textentry", $t_rule->getHTMLSettingForm(array('settings' => $settings_values_list, 'format' => "{$vs_id_prefix}_^setting_name_{n}", 'no_tooltips' => true))); ?>
						</div>
					</td>
					<td valign="top">
						<div style="float:right;">
							<a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
						</div>
					</td>
				</tr>
			</table>
		</div>
<?php
	//print TooltipManager::getLoadHTML('bundle_ca_tour_stops_list');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
		<div class='button labelInfo caAddItemButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= _t("Add rule"); ?> &rsaquo;</a></div>
	</div>
</div>
<?php
	// order element
?>
			
<script type="text/javascript">
	caUI.initBundle('#<?= $vs_id_prefix; ?>', {
		fieldNamePrefix: '<?= $vs_id_prefix; ?>_',
		templateValues: ['rule_code', 'rule_level', 'expression', 'rule_id', 'typename', <?= join(", ", array_map(function($v) { return "'{$v}'"; }, $settings_tags)); ?>],
		initialValues: <?= json_encode($va_initial_values); ?>,
		initialValueOrder: <?= json_encode(array_keys($va_initial_values)); ?>,
		errors: <?= json_encode($va_errors); ?>,
		forceNewValues: <?= json_encode($va_failed_inserts); ?>,
		itemID: '<?= $vs_id_prefix; ?>Item_',
		templateClassName: 'caItemTemplate',
		itemListClassName: 'caItemList',
		itemClassName: 'labelInfo',
		addButtonClassName: 'caAddItemButton',
		deleteButtonClassName: 'caDeleteItemButton',
		showOnNewIDList: ['<?= $vs_id_prefix; ?>_edit_name_'],
		hideOnNewIDList: ['<?= $vs_id_prefix; ?>_rule_info_', '<?= $vs_id_prefix; ?>_edit_'],
		showEmptyFormsOnLoad: 1,
		isSortable: false,
		defaultLocaleID: <?= ca_locales::getDefaultCataloguingLocaleID(); ?>
	});
</script>

