<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_lots.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	$t_item 			= $this->getVar('t_item');			// object_lot
	$t_subject 			= $this->getVar('t_subject');		// object
	$va_settings 		= 	$this->getVar('settings');
	$vs_add_label 		= $this->getVar('add_label');
	$va_rel_types		= $this->getVar('relationship_types');
	
	$vb_read_only		=	((isset($va_settings['readonly']) && $va_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_object_lots') == __CA_BUNDLE_ACCESS_READONLY__));
	
	$t_item->load($vn_lot_id = $t_subject->get('lot_id'));
	
	
	$va_force_new_values = $this->getVar('forceNewValues');
	$va_initial_values = $this->getVar('initialValues');
	
	// put brackets around idno_stub for presentation
	foreach($va_initial_values as $vn_i => $va_lot_info) {
		if ($va_initial_values[$vn_i]['idno_stub']) {
			$va_initial_values[$vn_i]['idno_stub'] = '['.$va_initial_values[$vn_i]['idno_stub'].'] ';
		}
	}
	
	// put brackets around idno_stub for presentation
	foreach($va_force_new_values as $vn_i => $va_lot_info) {
		if ($va_force_new_values[$vn_i]['idno_stub']) {
			$va_force_new_values[$vn_i]['idno_stub'] = '['.$va_force_new_values[$vn_i]['idno_stub'].'] ';
		}
	}

	// params to pass during occurrence lookup
	$va_lookup_params = (isset($va_settings['restrict_to_type']) && $va_settings['restrict_to_type']) ? array('type' => $va_settings['restrict_to_type'], 'noSubtypes' => (int)$va_settings['dont_include_subtypes_in_type_restriction']) : array();
?>
<div id="<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>">
<?php
	//
	// Template to generate display for existing items
	//
	
	if ($t_subject->tableName() == 'ca_objects') {
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo roundedRel">
			<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_object_lots', '{lot_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}">{{_display}}</a>

			<input type="hidden" name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" value="{type_id}"/>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
<?php
	if (!$vb_read_only) {
?>				
			<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
<?php
	}
?>			
			<div style="display: none;" class="itemName">{label}</div>
			<div style="display: none;" class="itemIdno">{idno_stub_sort}</div>
		</div>
	</textarea>
<?php
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_autocomplete{n}" value="{{_display}}" id="<?php print $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
						
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						
						<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_object_lots', '{lot_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_GO__); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
<?php
	} else {
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo roundedRel">
			<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_object_lots', '{lot_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}">{{_display}}</a>
			({{relationship_typename}})
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" value="{type_id}"/>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
<?php
	if (!$vb_read_only) {
?>				
			<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
<?php
	}
?>		
			<div style="display: none;" class="itemName">{label}</div>
			<div style="display: none;" class="itemIdno">{idno_stub_sort}</div>
		</div>
	</textarea>
<?php
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_autocomplete{n}" value="{{_display}}" id="<?php print $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						
						<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_object_lots', '{lot_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_GO__); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
<?php
	}
?>
	
	<div class="bundleContainer">
<?php
	if((sizeof($this->getVar('initialValues'))) && ($t_subject->tableName() != 'ca_objects') && !$vb_read_only) {
?>
		<div class="caItemListSortControlTrigger" id="<?php print $vs_id_prefix; ?>caItemListSortControlTrigger">
			<?php print _t('Sort by'); ?>
			<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/bg.gif" alt='Sort'/>
		</div>
		<div class="caItemListSortControls" id="<?php print $vs_id_prefix; ?>caItemListSortControls">
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('name'); return false;" class='caItemListSortControl'><?php print _t('name'); ?></a><br/>
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('idno'); return false;" class='caItemListSortControl'><?php print _t('idno'); ?></a><br/>
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('type'); return false;" class='caItemListSortControl'><?php print _t('type'); ?></a><br/>
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('entry'); return false;" class='caItemListSortControl'><?php print _t('entry'); ?></a><br/>
		</div>
<?php
	}
?>
		<div class="caItemList">
		
		</div>
		
		<input type="hidden" name="<?php print $vs_id_prefix; ?>BundleList" id="<?php print $vs_id_prefix; ?>BundleList" value=""/>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
<?php
	if (!$vb_read_only) {
?>	
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print $vs_add_label ? $vs_add_label : _t("Add lot"); ?></a></div>
<?php
	}
?>
	</div>
</div>
			
<script type="text/javascript">
	var caRelationBundle<?php print $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
		jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControlTrigger').click(function() { jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls').slideToggle(200); });
		jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls a.caItemListSortControl').click(function() {jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls').slideUp(200); });
		
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			initialValues: <?php print json_encode($va_initial_values); ?>,
			forceNewValues: <?php print json_encode($va_force_new_values); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_edit_related_'],
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'ObjectLot', 'Get', $va_lookup_params); ?>',
<?php
	if ($t_subject->tableName() == 'ca_objects') {
?>
			minRepeats: 0,
			maxRepeats: 1,
			templateValues: ['_display', 'idno_stub', 'id'],
			relationshipTypes: {},
<?php
	} else {
?>
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			templateValues: ['_display', 'idno_stub', 'id', 'type_id'],
<?php
	}
?>
			showEmptyFormsOnLoad: 0,
			readonly: <?php print $vb_read_only ? "true" : "false"; ?>,
			isSortable: <?php print ($t_subject->tableName() != 'ca_objects') ? ($vb_read_only ? "false" : "true") : "false"; ?>,
			listSortOrderID: '<?php print $vs_id_prefix; ?>BundleList',
			listSortItems: 'div.roundedRel'
		});
	});
</script>