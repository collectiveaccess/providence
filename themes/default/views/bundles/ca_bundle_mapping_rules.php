<?php
/* ----------------------------------------------------------------------
 * bundles/ca_bundle_mapping_rules.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
 
	JavascriptLoadManager::register('sortableUI');

	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_group 			= $this->getVar('t_group');	
	$t_mapping 			= $this->getVar('t_mapping');	
	
	$va_initial_values = $this->getVar('rules');	// list of existing rules
	$va_errors = array();
	$va_failed_inserts = array();
 
 ?>
 <div id="<?php print $vs_id_prefix.$t_group->tableNum().'_rel'; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
			<table class="uiMappingItem" width="100%">
				<tr >
					<td>
						<div class="formLabel" id="{fieldNamePrefix}edit_name_{n}">
							<table width="100%">
								<tr>
									<td width="49%">
										<?php print "<span class='caMappingBase'>".$t_group->get('ca_base_path').'.'."</span>".caHTMLTextInput('{fieldNamePrefix}ca_path_suffix_{n}', array('id' => '{fieldNamePrefix}ca_path_suffix_{n}', 'value' => '{ca_path_suffix}', 'class' => 'caMappingBase'), array('width' => 30)); ?>
									</td>
									<td width="2%" class="uiMappingHeader">=</td>
									<td width="49%" align="right">
										<?php print "<span class='externalMappingBase'>".$t_group->get('external_base_path')."</span>".caHTMLTextInput('{fieldNamePrefix}external_path_suffix_{n}', array('id' => '{fieldNamePrefix}ca_path_suffix_{n}', 'value' => '{external_path_suffix}', 'class' => 'externalMappingBase'), array('width' => 30)); ?>
									</td>
								</tr>
								<tr>
									<td colspan="3">
										<div id='{fieldNamePrefix}settings_container_{n}' style='display: none;'>
											{settings}
											<span id='{fieldNamePrefix}default_settings_form_{n}' style='display: none;'><?php print $this->getVar('new_settings_form'); ?></span>
										</div>
									</td>
								</tr>
							</table>
						</div>
					</td>
					<td width="40">
						<div style="float:right;">
							<span id="{fieldNamePrefix}edit_settings_{n}"><a href="#" onclick="jQuery('#{fieldNamePrefix}settings_container_{n}').slideToggle(250); return false" name="<?php print _t("Edit settings"); ?>"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__, null, array('alt' => _t('Edit settings'), 'title' => _t('Edit settings'))); ?></a></span>
							<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						</div>
					</td>
				</tr>
			</table>
		</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_bundle_mapping_rules');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemListHeader">
			<div class="labelInfo">
				<table class="uiMappingItem" width="100%">
					<tr>
						<td>
							<table width="100%">
								<tr>
									<td width="49%"class="uiMappingHeader">CollectiveAccess</td>
									<td width="2%"> </td>
									<td width="49%" class="uiMappingHeader" align="right"><?php print $t_mapping->get('target'); ?></td>
								</tr>
							</table>
						</td>
						<td width="20">
						&nbsp;
						</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="caItemList">
		
		</div>
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add rule"); ?> &rsaquo;</a></div>
	</div>
</div>

<input type="hidden" id="<?php print $vs_id_prefix; ?>_RuleBundleList" name="<?php print $vs_id_prefix; ?>_RuleBundleList" value=""/>
<?php
	// order element
?>
			
<script type="text/javascript">
	caUI.initBundle('#<?php print $vs_id_prefix.$t_group->tableNum().'_rel'; ?>', {
		fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
		templateValues: ['ca_path_suffix', 'external_path_suffix', 'rank', 'group_id', 'settings'],
		initialValues: <?php print json_encode($va_initial_values); ?>,
		errors: <?php print json_encode($va_errors); ?>,
		forceNewValues: <?php print json_encode($va_failed_inserts); ?>,
		itemID: '<?php print $vs_id_prefix; ?>Item_',
		templateClassName: 'caItemTemplate',
		itemListClassName: 'caItemList',
		itemClassName: 'labelInfo',
		addButtonClassName: 'caAddItemButton',
		deleteButtonClassName: 'caDeleteItemButton',
		showEmptyFormsOnLoad: 1,
		isSortable: true,
		listSortOrderID: '<?php print $vs_id_prefix; ?>_RuleBundleList',
		defaultLocaleID: <?php print ca_locales::getDefaultCataloguingLocaleID(); ?>,
		showOnNewIDList: ['<?php print $vs_id_prefix; ?>_default_settings_form_'],
		onAddItem: function() {
			jQuery('.caMappingBase').html(jQuery('input#ca_base_path').val() + '.');
			jQuery('.externalMappingBase').html(jQuery('input#external_base_path').val());
		}
	});
<?php
	// Implement live-update of rules with base path values
?>
	jQuery(document).ready(function() {
		jQuery('input#ca_base_path').live('keyup',
			function() {
				jQuery('.caMappingBase').html(jQuery('input#ca_base_path').val() + '.');
			}
		).live('blur',
			function() {
				jQuery('.caMappingBase').html(jQuery('input#ca_base_path').val() + '.');
			}
		);
		jQuery('input#external_base_path').live('keyup',
			function() {
				jQuery('.externalMappingBase').html(jQuery('input#external_base_path').val());
			}
		).live('blur',
			function() {
				jQuery('.externalMappingBase').html(jQuery('input#external_base_path').val());
			}
		);
	});
</script>
