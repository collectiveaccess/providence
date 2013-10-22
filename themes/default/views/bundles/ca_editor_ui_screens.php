<?php
/* ----------------------------------------------------------------------
 * bundles/ca_editor_ui_screens.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
	$t_ui 					= $this->getVar('t_ui');	
	$t_screen				= $this->getVar('t_screen');
	
	$va_initial_values = $this->getVar('screens');	// list of existing screens
	$va_errors = array();
	$va_failed_inserts = array();
 
 	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$t_ui->tableNum().'_rel');
?>
<div id="<?php print $vs_id_prefix.$t_ui->tableNum().'_rel'; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
			<table class="uiScreenItem">
				<tr >
					<td width="200">
						<div class="formLabel" id="{fieldNamePrefix}edit_name_{n}" style="display: none;">
							<?php print _t("Name")."<br/>".caHTMLTextInput('{fieldNamePrefix}name_{n}', array('id' => '{fieldNamePrefix}name_{n}', 'value' => '{name}'), array('width' => 40)); ?>
						</div>
						
						<span id="{fieldNamePrefix}screen_name_{n}">{name} ({numPlacements}) {isDefault}</span>
					</td>
					<td>
						<span id="{fieldNamePrefix}screen_info_{n}" class="uiScreenInfo">
							{typeRestrictionsForDisplay}
						</span>
					</td>
					<td>
						<div style="float:right;">
							<span id="{fieldNamePrefix}edit_{n}"><?php print urldecode(caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), '', 'administrate/setup/interface_screen_editor', 'InterfaceScreenEditor', 'Edit', array('screen_id' => '{screen_id}'))); ?></span>
							<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						</div>
					</td>
				</tr>
			</table>
		</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_editor_ui_screens');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add screen"); ?> &rsaquo;</a></div>
	</div>
</div>

<input type="hidden" id="<?php print $vs_id_prefix; ?>_ScreenBundleList" name="<?php print $vs_id_prefix; ?>_ScreenBundleList" value=""/>
<?php
	// order element
?>
			
<script type="text/javascript">
	caUI.initBundle('#<?php print $vs_id_prefix.$t_ui->tableNum().'_rel'; ?>', {
		fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
		templateValues: ['name', 'locale_id', 'rank', 'screen_id', 'numPlacements', 'typeRestrictionsForDisplay', 'isDefault'],
		initialValues: <?php print json_encode($va_initial_values); ?>,
		initialValueOrder: <?php print json_encode(is_array($va_initial_values) ? array_keys($va_initial_values) : null); ?>,
		errors: <?php print json_encode($va_errors); ?>,
		forceNewValues: <?php print json_encode($va_failed_inserts); ?>,
		itemID: '<?php print $vs_id_prefix; ?>Item_',
		templateClassName: 'caItemTemplate',
		itemListClassName: 'caItemList',
		itemClassName: 'labelInfo',
		addButtonClassName: 'caAddItemButton',
		deleteButtonClassName: 'caDeleteItemButton',
		showOnNewIDList: ['<?php print $vs_id_prefix; ?>_edit_name_'],
		hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_screen_info_', '<?php print $vs_id_prefix; ?>_screen_name_', '<?php print $vs_id_prefix; ?>_edit_'],
		showEmptyFormsOnLoad: 1,
		isSortable: true,
		listSortOrderID: '<?php print $vs_id_prefix; ?>_ScreenBundleList',
		defaultLocaleID: <?php print ca_locales::getDefaultCataloguingLocaleID(); ?>
	});
</script>
