<?php
/* ----------------------------------------------------------------------
 * bundles/ca_bundle_mapping_groups.php : 
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
	$t_mapping 			= $this->getVar('t_mapping');
	$t_group 			= $this->getVar('t_group');	
	$t_screen			= $this->getVar('t_screen');
	$vs_target			= $t_mapping->get('target');
	
	$va_initial_values = $this->getVar('groups');	// list of existing groups
	
	// process rules for display
	foreach($va_initial_values as $vn_group_id => $va_group) {
		if(!is_array($va_group['rules'])) { $va_group['rules'] = array(); }
		$vs_rule_display = '<table>';
		$vs_rule_display .= "<tr><td class='formLabel'>CollectiveAccess</td><td> </td><td class='formLabel'>{$vs_target}</td></tr>";
		foreach($va_group['rules'] as $vn_rule_id => $va_rule) {
			$vs_rule_display .= '<tr><td class="caMappingGroupInfo">'.$va_group['ca_base_path'].'.'.$va_rule['ca_path_suffix'].'</td><td class="formLabel">=</td><td class="caMappingGroupInfo">'.$va_group['external_base_path'].$va_rule['external_path_suffix']."</td></tr>";
		} 
		$vs_rule_display .= '</table>';
		$va_initial_values[$vn_group_id]['rules'] = sizeof($va_group['rules']) ? trim($vs_rule_display) : _t('No rules are configured');
		$va_initial_values[$vn_group_id]['numRules'] = sizeof($va_group['rules']);
	}
	
	$va_errors = array();
	$va_failed_inserts = array();
 
 ?>
 <div id="<?php print $vs_id_prefix.$t_mapping->tableNum().'_rel'; ?>">
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
							<table width="100%">
								<tr>
									<td class="formLabel"><?php print _t("Unit name"); ?></td>
									<td class="formLabel"><?php print $t_group->getFieldInfo('ca_base_path', 'LABEL'); ?></td>
									<td class="formLabel"><?php print _t("Base path for %1 target", $t_mapping->get('target')); ?></td>
								</tr>
								<tr>
									<td class="caMappingGroupInfo">
										<span id="<?php print $vs_id_prefix; ?>_group_name_{n}" style="display: none;"><?php print caHTMLTextInput('{fieldNamePrefix}name_{n}', array('id' => '{fieldNamePrefix}name_{n}', 'value' => '{name}'), array('width' => 30)); ?></span>
										<span id="<?php print $vs_id_prefix; ?>_name_display_{n}">{name}</span>
									</td>
									<td class="caMappingGroupInfo">
										<span id="<?php print $vs_id_prefix; ?>_ca_base_path_{n}" style="display: none;"><em><?php print _t('Not set'); ?></em></span>
										<span id="<?php print $vs_id_prefix; ?>_ca_base_path_display_{n}">{ca_base_path}</span>
									</td>
									<td class="caMappingGroupInfo">
										<span id="<?php print $vs_id_prefix; ?>_external_base_path_{n}" style="display: none;"><em><?php print _t('Not set'); ?></em></span>
										<span id="<?php print $vs_id_prefix; ?>_external_base_path_display_{n}">{external_base_path}</span>
									</td>
								</tr>
							</table>
							
							<div id="<?php print $vs_id_prefix; ?>_rules_container_{n}">
								<div style="float: right;">
									<a href="#" onclick="jQuery('#<?php print $vs_id_prefix; ?>_rules_{n}').slideDown(250); jQuery('#<?php print $vs_id_prefix; ?>_show_rules_{n}').hide(); jQuery('#<?php print $vs_id_prefix; ?>_hide_rules_{n}').show();" class="button" id="<?php print $vs_id_prefix; ?>_show_rules_{n}"><?php print _t('Show configured rules (%1)', '{numRules}'); ?>&rsaquo;</a>
									<a href="#" onclick="jQuery('#<?php print $vs_id_prefix; ?>_rules_{n}').slideUp(250); jQuery('#<?php print $vs_id_prefix; ?>_show_rules_{n}').show(); jQuery('#<?php print $vs_id_prefix; ?>_hide_rules_{n}').hide();" style="display: none;" class="button" id="<?php print $vs_id_prefix; ?>_hide_rules_{n}"><?php print _t('Hide configured rules (%1)', '{numRules}'); ?>&rsaquo;</a>
								</div>
								<br style="clear: both;"/>
								<div id="<?php print $vs_id_prefix; ?>_rules_{n}" class="caMappingGroupRulesDisplay">
									{rules}
								</div>
							</div>
						</div>
						
					</td>
					<td>
						<div style="float:right;">
							<span id="{fieldNamePrefix}edit_{n}"><?php print urldecode(caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__, null, array('alt' => _t('Edit unit'), 'title' => _t('Edit unit'))), '', 'administrate/setup/bundle_mapping_group_editor', 'BundleMappingGroupEditor', 'Edit', array('group_id' => '{group_id}'))); ?></span>
							<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						</div>
					</td>
				</tr>
			</table>
		</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_bundle_mapping_groups');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add unit"); ?> &rsaquo;</a></div>
	</div>
</div>

<input type="hidden" id="<?php print $vs_id_prefix; ?>_GroupBundleList" name="<?php print $vs_id_prefix; ?>_GroupBundleList" value=""/>
<?php
	// order element
?>
			
<script type="text/javascript">
	caUI.initBundle('#<?php print $vs_id_prefix.$t_mapping->tableNum().'_rel'; ?>', {
		fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
		templateValues: ['name', 'rank', 'group_id', 'rules', 'ca_base_path', 'external_base_path'],
		initialValues: <?php print json_encode($va_initial_values); ?>,
		errors: <?php print json_encode($va_errors); ?>,
		forceNewValues: <?php print json_encode($va_failed_inserts); ?>,
		itemID: '<?php print $vs_id_prefix; ?>Item_',
		templateClassName: 'caItemTemplate',
		itemListClassName: 'caItemList',
		itemClassName: 'labelInfo',
		addButtonClassName: 'caAddItemButton',
		deleteButtonClassName: 'caDeleteItemButton',
		showOnNewIDList: ['<?php print $vs_id_prefix; ?>_group_name_', '<?php print $vs_id_prefix; ?>_ca_base_path_', '<?php print $vs_id_prefix; ?>_external_base_path_'],
		hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_name_display_', '<?php print $vs_id_prefix; ?>_ca_base_path_display_', '<?php print $vs_id_prefix; ?>_external_base_path_display_', , '<?php print $vs_id_prefix; ?>_rules_container_'],
		showEmptyFormsOnLoad: 1,
		isSortable: true,
		listSortOrderID: '<?php print $vs_id_prefix; ?>_GroupBundleList',
		defaultLocaleID: <?php print ca_locales::getDefaultCataloguingLocaleID(); ?>
	});
</script>
