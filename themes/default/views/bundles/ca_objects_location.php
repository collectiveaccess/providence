<?php
/* ----------------------------------------------------------------------
 * bundles/ca_objects_location.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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
 
 	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject					= $this->getVar('t_subject');
	$va_settings 				= $this->getVar('settings');

	$vb_read_only				= (isset($va_settings['readonly']) && $va_settings['readonly']);
	
	if (!($vs_add_label 		= $this->getVar('add_label'))) { $vs_add_label = _t('Update location'); }
	$vs_display_template		= caGetOption('displayTemplate', $va_settings, _t('No template defined'));
	$vs_history_template		= caGetOption('historyTemplate', $va_settings, $vs_display_template);

	$vs_current_location		= $this->getVar('current_location');
	$va_history					= $this->getVar('location_history');
	
	$vs_mode					= $this->getVar('mode');
	$vs_relationship_type		= $this->getVar('location_relationship_type');
	$vs_change_location_url		= $this->getVar('location_change_url');
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div id="<?php print $vs_id_prefix; ?>Container" class="editorHierarchyBrowserContainer">		
				<div  id="<?php print $vs_id_prefix; ?>Tabs">
					<ul>
						<li><a href="#<?php print $vs_id_prefix; ?>Tabs-location"><span><?php print _t('Current location'); ?></span></a></li>
						<li><a href="#<?php print $vs_id_prefix; ?>Tabs-history"><span><?php print _t('History'); ?></span></a></li>
					</ul>
					<div id="<?php print $vs_id_prefix; ?>Tabs-location" class="hierarchyBrowseTab">	
<?php
						if ($vs_current_location) { 
							print "<div class='caCurrentLocation' style='background-color:#".$va_settings['currentLocationColor']."'>{$vs_current_location}</div>";	 
						} else {
?>
							<?php print _t('No location set'); ?>
<?php
						}
?>
					</div>
					<div id="<?php print $vs_id_prefix; ?>Tabs-history" class="hierarchyBrowseTab caLocationHistoryTab">	
<?php
						if (is_array($va_history) && sizeof($va_history)) {
							foreach($va_history as $vn_id => $va_relation) {
								switch($va_relation['status']) {
									case 'FUTURE':
										print "<div class='caLocationHistory' style='background-color:#".$va_settings['futureLocationColor']."'>".$va_relation['display']."</div>\n";
										break;
									case 'PRESENT':
										print "<div class='caCurrentLocation' style='background-color:#".$va_settings['currentLocationColor']."'>".$va_relation['display']."</div>\n";
										break;
									case 'PAST':
									default:
										print "<div class='caLocationHistory' style='background-color:#".$va_settings['pastLocationColor']."'>".$va_relation['display']."</div>\n";
										break;	
								}
							}
						} else {
?>
							<?php print _t('No location history set'); ?>
<?php
						}
?>
					</div>
<?php
	// get current location
?>
				</div>
			</div>
		</div>
		
		<div class='button labelInfo caAddItemButton'><a href="#" id="<?php print $vs_id_prefix; ?>ChangeLocation"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print $vs_add_label; ?></a></div>
		
	</div>
<?php
	if ($vs_mode == 'ca_storage_locations') {
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo caRelatedItem">
<?php
	if (!(bool)$va_settings['useHierarchicalBrowser']) {
?>
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_autocomplete{n}" value="{{label}}" id="<?php print $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_location_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
					</td>
				</tr>
			</table>
<?php
	} else {
?>
			<div style="float: right;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			<div style='width: 690px; height: 160px;'>
				
				<div id='<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}' style='width: 100%; height: 100%;' class='hierarchyBrowser'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->	</div>
				
			<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
			<div style="float: right;">
				<div class='hierarchyBrowserSearchBar'><?php print _t('Search'); ?>: <input type='text' id='<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}' class='hierarchyBrowserSearchBar' name='search' value='' size='40'/></div>
			</div>
			<div style="float: left;" class="hierarchyBrowserCurrentSelectionText">
				<input type="hidden" name="<?php print $vs_id_prefix; ?>_location_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
				
				<span class="hierarchyBrowserCurrentSelectionText" id="<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}"> </span>
			</div>	
			<br class='clear'/>
			
			<script type='text/javascript'>
				jQuery(document).ready(function() { 
					var <?php print $vs_id_prefix; ?>oHierBrowser{n} = caUI.initHierBrowser('<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}', {
						uiStyle: 'horizontal',
						levelDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyLevel', array()); ?>',
						initDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyAncestorList'); ?>',
						
						selectOnLoad : true,
						browserWidth: '690px',
						
						dontAllowEditForFirstLevel: false,
						
						className: 'hierarchyBrowserLevel',
						classNameContainer: 'hierarchyBrowserContainer',
						
						editButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
						disabledButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
						
						indicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
						
						displayCurrentSelectionOnLoad: false,
						currentSelectionDisplayID: '<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}',
						onSelection: function(item_id, parent_id, name, display, type_id) {
							caRelationBundle<?php print $vs_id_prefix; ?>.select('{n}', {id: item_id, type_id: type_id}, display);
						}
					});
					
					jQuery('#<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').autocomplete({
							source: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', array('noInline' => 1)); ?>',
							minLength: 3, delay: 800, html: true,
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									<?php print $vs_id_prefix; ?>oHierBrowser{n}.setUpHierarchy(ui.item.id);	// jump browser to selected item
								}
								event.preventDefault();
								jQuery('#<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').val('');
							}
						}
					);
				});
			</script>
<?php
	}
?>
		</div>
	</textarea>
<?php
}
?>
</div>

<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Change location'); ?></div>
		
	</div>
</div>	

<script type="text/javascript">
<?php
	if ($vs_mode == 'ca_storage_locations') {
?>
	var caRelationBundle<?php print $vs_id_prefix; ?>;

	jQuery(document).ready(function() {
		jQuery("#<?php print $vs_id_prefix; ?>Tabs").tabs({ selected: 0 });	
		
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['label', 'type_id', 'id'],
			initialValues: [],
			initialValueOrder: [],
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			placementID: '<?php print $vn_placement_id; ?>',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			listItemClassName: 'caRelatedItem',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 0,
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', $va_lookup_params); ?>',
			minChars:1,
			readonly: false,
			isSortable: false,
			listSortItems: 'div.roundedRel',			
			autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
			minRepeats: 0,
			maxRepeats: 1
		});
	});
<?php
	} else {
?>
	var caRelationQuickAddPanel<?php print $vs_id_prefix; ?>;
	
	jQuery(document).ready(function() {
		jQuery("#<?php print $vs_id_prefix; ?>Tabs").tabs({ selected: 0 });	
		
		if (caUI.initPanel) {
			caRelationQuickAddPanel<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caRelationQuickAddPanel<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
					jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
			
			var panelContentID = '#' + caRelationQuickAddPanel<?php print $vs_id_prefix; ?>.getPanelContentID();
			jQuery(panelContentID)
				.data('relatedID', <?php print (int)$t_subject->getPrimaryKey(); ?>)
				.data('relatedTable', 'ca_objects')
				.data('relationshipType', '<?php print $vs_relationship_type; ?>')
				.data('panel', caRelationQuickAddPanel<?php print $vs_id_prefix; ?>); 
			
			jQuery("#<?php print $vs_id_prefix; ?>ChangeLocation").on("click", function() { 
				caRelationQuickAddPanel<?php print $vs_id_prefix; ?>.showPanel('<?php print $vs_change_location_url; ?>'); 
				return false;
			});
		}
		
	});
<?php
	}
?>
</script>