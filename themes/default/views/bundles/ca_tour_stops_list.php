<?php
/* ----------------------------------------------------------------------
 * bundles/ca_tour_stops.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2022 Whirl-i-Gig
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

	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_tour 			= $this->getVar('t_tour');	
	$t_stop				= $this->getVar('t_stop');
	
	$settings 			= $this->getVar('settings');
	
	$va_initial_values 	= $this->getVar('stops');	// list of existing stops
	$va_errors = $va_failed_inserts = [];
 
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
	print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix, $settings);
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
				<tr >
					<td width="200">
						<div class="formLabel" id="{fieldNamePrefix}edit_name_{n}" style="display: none;">
							<?= _t("Name")." ".caHTMLTextInput('{fieldNamePrefix}name_{n}', array('id' => '{fieldNamePrefix}name_{n}', 'value' => '{name}'), array('width' => 40)); ?>
							<?= _t("Type")." ".$t_stop->getTypeListAsHTMLFormElement('{fieldNamePrefix}type_id_{n}'); ?>
						</div>
						
						<span id="{fieldNamePrefix}screen_name_{n}">
							{name}
							<span style="font-weight: normal;">{typename}</span>
						</span>
					</td>
					<td>
						<div style="float:right;">
							<span id="{fieldNamePrefix}edit_{n}"><?= urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 1), '', 'editor/tour_stops', 'TourStopEditor', 'Edit', array('stop_id' => '{stop_id}'))); ?></span>
							<a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
						</div>
					</td>
				</tr>
			</table>
		</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_tour_stops_list');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
		<div class='button labelInfo caAddItemButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= _t("Add stop"); ?> &rsaquo;</a></div>
	</div>
</div>

<input type="hidden" id="<?= $vs_id_prefix; ?>_StopBundleList" name="<?= $vs_id_prefix; ?>_StopBundleList" value=""/>
<?php
	// order element
?>
			
<script type="text/javascript">
	caUI.initBundle('#<?= $vs_id_prefix; ?>', {
		fieldNamePrefix: '<?= $vs_id_prefix; ?>_',
		templateValues: ['name', 'locale_id', 'rank', 'stop_id', 'typename'],
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
		hideOnNewIDList: ['<?= $vs_id_prefix; ?>_stop_info_', '<?= $vs_id_prefix; ?>_edit_'],
		showEmptyFormsOnLoad: 1,
		isSortable: true,
		listSortOrderID: '<?= $vs_id_prefix; ?>_StopBundleList',
		defaultLocaleID: <?= ca_locales::getDefaultCataloguingLocaleID(); ?>
	});
</script>
